"use strict";
/**
 * خواندن درخواست‌های multipart/form-data بدون هیچ پکیج خارجی.
 * بدنه یک‌جا در حافظه خوانده می‌شود (سقف حجم از config می‌آید) و بعد
 * به بخش‌ها شکسته می‌شود. برای آپلود رسید و فایل برنامه کافی است.
 */

const crypto = require("crypto");
const path = require("path");

/** خواندن بدنهٔ خام با سقف حجم */
function readBody(req, limit) {
  return new Promise((resolve, reject) => {
    const chunks = [];
    let size = 0;
    let done = false;

    const fail = (err) => {
      if (done) return;
      done = true;
      reject(err);
    };

    req.on("data", (chunk) => {
      size += chunk.length;
      if (size > limit) {
        fail(Object.assign(new Error("حجم فایل بیش از حد مجاز است"), { code: "TOO_LARGE" }));
        req.destroy();
        return;
      }
      chunks.push(chunk);
    });
    req.on("end", () => {
      if (done) return;
      done = true;
      resolve(Buffer.concat(chunks));
    });
    req.on("error", fail);
  });
}

function splitBuffer(buf, delim) {
  const parts = [];
  let start = 0;
  let idx;
  while ((idx = buf.indexOf(delim, start)) !== -1) {
    parts.push(buf.subarray(start, idx));
    start = idx + delim.length;
  }
  parts.push(buf.subarray(start));
  return parts;
}

/** پارس هدرهای یک بخش */
function parseHeaders(text) {
  const out = {};
  text.split("\r\n").forEach((line) => {
    const i = line.indexOf(":");
    if (i < 1) return;
    out[line.slice(0, i).trim().toLowerCase()] = line.slice(i + 1).trim();
  });
  return out;
}

function getParam(header, key) {
  if (!header) return null;
  // filename*=UTF-8''name  یا  filename="name"
  const star = new RegExp(`${key}\\*=([^;]+)`, "i").exec(header);
  if (star) {
    let val = star[1].trim().replace(/^["']|["']$/g, "");
    const m = /^[\w-]+''(.+)$/.exec(val);
    if (m) {
      try {
        return decodeURIComponent(m[1]);
      } catch {
        return m[1];
      }
    }
  }
  const plain = new RegExp(`${key}="([^"]*)"`, "i").exec(header);
  if (plain) return plain[1];
  const bare = new RegExp(`${key}=([^;\\r\\n]+)`, "i").exec(header);
  return bare ? bare[1].trim() : null;
}

/**
 * خروجی: { fields: {name: string}, files: [{ field, filename, mime, data }] }
 */
function parseMultipart(body, contentType) {
  const m = /boundary=(?:"([^"]+)"|([^;]+))/i.exec(contentType || "");
  const boundary = m ? (m[1] || m[2]).trim() : null;
  if (!boundary) {
    const err = new Error("نوع درخواست پشتیبانی نمی‌شود");
    err.code = "BAD_FORM";
    throw err;
  }

  const delim = Buffer.from(`--${boundary}`);
  const raw = splitBuffer(body, delim);
  const fields = {};
  const files = [];

  for (let i = 1; i < raw.length; i++) {
    let part = raw[i];
    // بخش پایانی با -- شروع می‌شود
    if (part.length >= 2 && part[0] === 0x2d && part[1] === 0x2d) break;
    // \r\n ابتدای هر بخش و \r\n انتهای آن را حذف کن
    if (part.length >= 2 && part[0] === 0x0d && part[1] === 0x0a) part = part.subarray(2);
    if (part.length >= 2 && part[part.length - 2] === 0x0d && part[part.length - 1] === 0x0a) {
      part = part.subarray(0, part.length - 2);
    }

    const sep = part.indexOf("\r\n\r\n");
    if (sep < 0) continue;
    const headers = parseHeaders(part.subarray(0, sep).toString("utf8"));
    const data = part.subarray(sep + 4);

    const name = getParam(headers["content-disposition"], "name");
    if (!name) continue;

    const filename = getParam(headers["content-disposition"], "filename");
    if (filename) {
      if (data.length === 0) continue; // ورودی خالی نادیده گرفته می‌شود
      files.push({
        field: name,
        filename,
        mime: headers["content-type"] || "application/octet-stream",
        data,
      });
    } else {
      fields[name] = data.toString("utf8");
    }
  }

  return { fields, files };
}

/* ------------------------------------------------------- تشخیص نوع فایل */

const SIGNATURES = [
  { ext: "jpg", mime: "image/jpeg", test: (b) => b[0] === 0xff && b[1] === 0xd8 && b[2] === 0xff },
  {
    ext: "png",
    mime: "image/png",
    test: (b) => b[0] === 0x89 && b[1] === 0x50 && b[2] === 0x4e && b[3] === 0x47,
  },
  {
    ext: "webp",
    mime: "image/webp",
    test: (b) => b.subarray(0, 4).toString() === "RIFF" && b.subarray(8, 12).toString() === "WEBP",
  },
  { ext: "pdf", mime: "application/pdf", test: (b) => b.subarray(0, 4).toString() === "%PDF" },
  {
    ext: "gif",
    mime: "image/gif",
    test: (b) => b.subarray(0, 3).toString() === "GIF",
  },
];

/**
 * نوع واقعی فایل را از محتوایش تشخیص می‌دهد — به mime ارسالی مرورگر اعتماد نمی‌کنیم.
 * پسوندِ خروجی هم از همین‌جا می‌آید، نه از نام فایل کاربر.
 */
function detectFile(data) {
  if (!data || data.length < 12) return null;
  for (const s of SIGNATURES) {
    if (s.test(data)) return { ext: s.ext, mime: s.mime };
  }
  return null;
}

/** نام تصادفی و امن روی دیسک */
function safeStoredName(ext) {
  return `${Date.now().toString(36)}-${crypto.randomBytes(8).toString("hex")}.${ext}`;
}

/** نام اصلی فایل را برای نمایش تمیز می‌کند (بدون مسیر و کاراکترهای کنترلی) */
function cleanOriginalName(name) {
  const base = path.basename(String(name || "file"))
    .replace(/[\u0000-\u001f\u007f]/g, "")
    .trim();
  return base.slice(0, 120) || "file";
}

module.exports = {
  readBody,
  parseMultipart,
  detectFile,
  safeStoredName,
  cleanOriginalName,
};
