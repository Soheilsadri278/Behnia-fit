"use strict";
/**
 * مسیرهای API.
 *
 * قاعدهٔ امنیتی: هر کوئری به برنامه‌ها با شناسهٔ کاربرِ نشست محدود می‌شود.
 * هیچ مسیری برنامهٔ کاربر دیگر را برنمی‌گرداند، حتی با حدس زدن id.
 */

const fs = require("fs");
const path = require("path");
const crypto = require("crypto");

const config = require("./config");
const store = require("./store");
const auth = require("./auth");
const { readBody, parseMultipart, detectFile, safeStoredName, cleanOriginalName } = require("./upload");

/* ------------------------------------------------------------ ابزار متن */

/** متن را برای گذاشتن داخل HTML امن می‌کند (پیام‌های خطای مسیر فایل). */
function escapeHtml(value) {
  return String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

/* ------------------------------------------------------------ ابزار پاسخ */

function json(res, status, data) {
  const body = JSON.stringify(data);
  res.writeHead(status, {
    "Content-Type": "application/json; charset=utf-8",
    "Content-Length": Buffer.byteLength(body),
    "Cache-Control": "no-store",
  });
  res.end(body);
}

const ok = (res, data = {}) => json(res, 200, { ok: true, ...data });
const fail = (res, status, message) => json(res, status, { ok: false, error: message });

function redirect(res, location) {
  res.writeHead(302, { Location: location, "Cache-Control": "no-store" });
  res.end();
}

/* ------------------------------------------------------------ ضد CSRF */

function sameOrigin(req) {
  const origin = req.headers.origin;
  if (!origin) return true; // درخواست‌های غیرمرورگری Origin ندارند
  try {
    return new URL(origin).host === req.headers.host;
  } catch {
    return false;
  }
}

/** بدنهٔ JSON با محدودیت حجم */
async function readJson(req, limit = 256 * 1024) {
  const buf = await readBody(req, limit);
  if (!buf.length) return {};
  try {
    return JSON.parse(buf.toString("utf8"));
  } catch {
    const err = new Error("دادهٔ ارسالی معتبر نیست");
    err.code = "BAD_JSON";
    throw err;
  }
}

function clientIp(req) {
  const fwd = req.headers["x-forwarded-for"];
  if (config.trustProxy && fwd) return String(fwd).split(",")[0].trim();
  return req.socket.remoteAddress || "unknown";
}

/* --------------------------------------------------------- ساخت برنامه */

const MAX_DAYS = 14;
const MAX_ITEMS_PER_DAY = 40;

/** اعتبارسنجی ساختار برنامه‌ای که پنل مربی می‌فرستد */
function cleanProgramPayload(raw) {
  const days = Array.isArray(raw.days) ? raw.days.slice(0, MAX_DAYS) : [];
  const cleanDays = [];
  for (const d of days) {
    const items = Array.isArray(d.items) ? d.items.slice(0, MAX_ITEMS_PER_DAY) : [];
    const cleanItems = [];
    for (const it of items) {
      const exercise = String(it.exercise || "").trim().slice(0, 120);
      if (!exercise) continue;
      cleanItems.push({
        exercise,
        sets: String(it.sets || "").trim().slice(0, 20),
        reps: String(it.reps || "").trim().slice(0, 30),
        rest: String(it.rest || "").trim().slice(0, 30),
        note: String(it.note || "").trim().slice(0, 200),
      });
    }
    const label = String(d.label || "").trim().slice(0, 80);
    if (!label && !cleanItems.length) continue;
    cleanDays.push({
      label: label || "روز بدون عنوان",
      note: String(d.note || "").trim().slice(0, 300),
      items: cleanItems,
    });
  }
  return cleanDays;
}

/** ذخیرهٔ فایل‌های آپلودی روی دیسک — فقط نوع‌های تأییدشده */
function saveFiles(files) {
  const saved = [];
  for (const f of files) {
    if (f.data.length > config.maxUpload) {
      const err = new Error("حجم فایل بیش از حد مجاز است");
      err.code = "TOO_LARGE";
      throw err;
    }
    const kind = detectFile(f.data);
    if (!kind) {
      const err = new Error("فقط تصویر (JPG/PNG/WebP) یا PDF پذیرفته می‌شود");
      err.code = "BAD_TYPE";
      throw err;
    }
    const storedName = safeStoredName(kind.ext);
    fs.writeFileSync(path.join(config.uploadDir, storedName), f.data);
    saved.push({
      storedName,
      originalName: cleanOriginalName(f.filename),
      mime: kind.mime,
      size: f.data.length,
    });
  }
  return saved;
}

/* -------------------------------------------------------------- زرین‌پال */

/** سفارش را به مبلغ تومان تبدیل می‌کند (نرخ از تنظیمات) */
function tomanAmount(order) {
  const rate = Number(process.env.ZARINPAL_RATE || 0);
  if (!rate) return 0;
  return Math.round(order.amount_usd * rate);
}

async function zarinpalRequest(order, description) {
  const amount = tomanAmount(order);
  if (!amount) throw new Error("نرخ تبدیل برای درگاه تنظیم نشده است");

  const res = await fetch(`${config.zarinpal.base}/pg/v4/payment/request.json`, {
    method: "POST",
    headers: { "Content-Type": "application/json", Accept: "application/json" },
    body: JSON.stringify({
      merchant_id: config.zarinpal.merchantId,
      amount,
      currency: "IRT",
      description,
      callback_url: `${config.siteUrl}/api/payments/zarinpal/callback`,
    }),
  });
  const data = await res.json().catch(() => ({}));
  if (!data || !data.data || data.data.code !== 100) {
    throw new Error("درگاه پرداخت پاسخ درست نداد");
  }
  return data.data.authority;
}

async function zarinpalVerify(order) {
  const amount = tomanAmount(order);
  const res = await fetch(`${config.zarinpal.base}/pg/v4/payment/verify.json`, {
    method: "POST",
    headers: { "Content-Type": "application/json", Accept: "application/json" },
    body: JSON.stringify({
      merchant_id: config.zarinpal.merchantId,
      amount,
      authority: order.authority,
    }),
  });
  const data = await res.json().catch(() => ({}));
  // ۱۰۰ = پرداخت موفق تازه، ۱۰۱ = قبلاً تایید شده
  return Boolean(data && data.data && (data.data.code === 100 || data.data.code === 101));
}

/* ------------------------------------------------------------ مسیرها */

async function handle(req, res, url) {
  const method = req.method;
  const p = url.pathname;
  const seg = p.split("/").filter(Boolean); // ["api", ...]

  const user = auth.currentUser(req);
  const isCoach = Boolean(user && user.role === "coach");
  const requireUser = () => {
    if (!user) {
      fail(res, 401, "برای این کار باید وارد حساب شوی");
      return false;
    }
    return true;
  };
  const requireCoach = () => {
    if (!user) {
      fail(res, 401, "برای این کار باید وارد حساب شوی");
      return false;
    }
    if (!isCoach) {
      fail(res, 403, "دسترسی به این بخش فقط برای مربی است");
      return false;
    }
    return true;
  };

  /* ---------------- ثبت‌نام ---------------- */
  if (p === "/api/auth/register" && method === "POST") {
    if (!sameOrigin(req)) return fail(res, 403, "درخواست معتبر نیست");
    const ip = clientIp(req);
    if (auth.tooManyAttempts(ip)) {
      return fail(res, 429, "تلاش زیاد. چند دقیقه بعد دوباره امتحان کن.");
    }
    const body = await readJson(req);
    const phone = auth.normalizePhone(body.phone);
    const name = auth.normalizeName(body.name);
    const password = body.password;

    if (!phone) return fail(res, 400, "شمارهٔ موبایل را درست وارد کن (مثل ۰۹۱۲۳۴۵۶۷۸۹)");
    if (!name) return fail(res, 400, "نام را وارد کن");
    if (!auth.validPassword(password)) return fail(res, 400, "رمز باید حداقل ۸ کاراکتر باشد");
    if (store.users.byPhone(phone)) return fail(res, 409, "این شماره قبلاً ثبت‌نام کرده است");

    const hash = await auth.hashPassword(password);
    const id = store.users.create({ phone, name, passwordHash: hash, role: "student" });
    auth.noteAttempt(ip, true);
    const session = auth.startSession(id);
    res.setHeader("Set-Cookie", session.cookie);
    return ok(res, { user: { id, name, phone, role: "student" }, redirect: "/account.html" });
  }

  /* ---------------- ورود ---------------- */
  if (p === "/api/auth/login" && method === "POST") {
    if (!sameOrigin(req)) return fail(res, 403, "درخواست معتبر نیست");
    const ip = clientIp(req);
    if (auth.tooManyAttempts(ip)) {
      return fail(res, 429, "تلاش زیاد. چند دقیقه بعد دوباره امتحان کن.");
    }
    const body = await readJson(req);
    const phone = auth.normalizePhone(body.phone);
    const password = String(body.password || "");
    if (!phone) return fail(res, 400, "شمارهٔ موبایل را درست وارد کن");

    const found = store.users.byPhone(phone);
    const passOk = found ? await auth.verifyPassword(password, found.password_hash) : false;
    // حتی وقتی کاربر نیست، زمان پاسخ را یکسان نگه می‌داریم
    if (!found || !passOk) {
      auth.noteAttempt(ip, false);
      return fail(res, 401, "شماره یا رمز اشتباه است");
    }
    auth.noteAttempt(ip, true);
    const session = auth.startSession(found.id);
    res.setHeader("Set-Cookie", session.cookie);
    return ok(res, {
      user: { id: found.id, name: found.name, phone: found.phone, role: found.role },
      redirect: found.role === "coach" ? "/admin.html" : "/account.html",
    });
  }

  /* ---------------- خروج ---------------- */
  if (p === "/api/auth/logout" && method === "POST") {
    if (!sameOrigin(req)) return fail(res, 403, "درخواست معتبر نیست");
    res.setHeader("Set-Cookie", auth.endSession(req));
    return ok(res);
  }

  /* ---------------- من ---------------- */
  if (p === "/api/me" && method === "GET") {
    if (!user) return json(res, 200, { ok: true, user: null });
    return ok(res, {
      user: { id: user.id, name: user.name, phone: user.phone, role: user.role },
    });
  }

  /* ---------------- کاتالوگ ---------------- */
  if (p === "/api/plans" && method === "GET") {
    return ok(res, {
      plans: store.plans.all(),
      zarinpal: config.payments.zarinpal,
      // شمارهٔ کارت کارت‌به‌کارت — صفحهٔ حساب آن را در جعبهٔ پرداخت نشان می‌دهد
      card: config.cardNumber,
    });
  }

  /* ---------------- خرید ---------------- */
  if (p === "/api/orders" && method === "POST") {
    if (!sameOrigin(req)) return fail(res, 403, "درخواست معتبر نیست");
    if (!requireUser()) return;
    const body = await readJson(req);
    const plan = store.plans.byKey(String(body.planKey || ""));
    if (!plan) return fail(res, 400, "برنامهٔ انتخابی پیدا نشد");

    const method = body.method === "zarinpal" ? "zarinpal" : "manual";
    if (method === "zarinpal" && !config.payments.zarinpal) {
      return fail(res, 400, "درگاه پرداخت هنوز فعال نشده است");
    }

    const orderId = store.orders.create({
      userId: user.id,
      planKey: plan.key,
      amount: plan.price_usd,
      method,
      note: String(body.note || "").slice(0, 300),
    });

    if (method === "zarinpal") {
      try {
        const order = store.orders.byId(orderId);
        const authority = await zarinpalRequest(order, `${plan.title} — بهنیا فیت`);
        store.orders.setAuthority(orderId, authority);
        return ok(res, {
          orderId,
          redirect: `${config.zarinpal.base}/pg/StartPay/${authority}`,
        });
      } catch (e) {
        store.orders.setStatus(orderId, "canceled");
        return fail(res, 502, e.message || "اتصال به درگاه ممکن نشد");
      }
    }

    return ok(res, { orderId, method: "manual" });
  }

  /* ---------------- آپلود رسید ---------------- */
  if (/^\/api\/orders\/\d+\/receipt$/.test(p) && method === "POST") {
    if (!sameOrigin(req)) return fail(res, 403, "درخواست معتبر نیست");
    if (!requireUser()) return;
    const orderId = Number(seg[2]);
    const order = store.orders.byId(orderId);
    if (!order || order.user_id !== user.id) return fail(res, 404, "سفارش پیدا نشد");
    if (order.status !== "pending") return fail(res, 400, "این سفارش دیگر قابل تغییر نیست");

    const body = await readBody(req, config.maxUpload + 512 * 1024);
    const { files } = parseMultipart(body, req.headers["content-type"]);
    if (!files.length) return fail(res, 400, "تصویر رسید را انتخاب کن");
    const saved = saveFiles(files.slice(0, 1));
    if (!saved.length) return fail(res, 400, "فایل پذیرفته نشد");

    store.orders.setReceipt(orderId, user.id, saved[0].storedName);
    return ok(res, { status: "awaiting_confirm" });
  }

  /* ---------------- برنامه‌های من ---------------- */
  if (p === "/api/me/programs" && method === "GET") {
    if (!requireUser()) return;
    return ok(res, {
      programs: store.programs.forUser(user.id),
      orders: store.orders.forUser(user.id),
    });
  }

  /* ---------------- مشاهدهٔ یک برنامه ---------------- */
  if (/^\/api\/programs\/\d+$/.test(p) && method === "GET") {
    if (!requireUser()) return;
    const program = store.programs.full(Number(seg[2]), user.id, isCoach);
    if (!program) return fail(res, 404, "برنامه پیدا نشد");
    return ok(res, { program });
  }

  /* ---------------- دانلود فایل برنامه ---------------- */
  if (/^\/api\/files\/[\w.-]+$/.test(p) && method === "GET") {
    const storedName = path.basename(decodeURIComponent(seg[2]));
    const fullPath = path.join(config.uploadDir, storedName);

    /**
     * خطای این مسیر به شکل خوانا.
     *
     * نشانی فایل در تب تازهٔ مرورگر باز می‌شود (دکمهٔ «دیدن رسید»)؛ آنجا JSON
     * خام بدترین شکل پیام است. اگر مرورگر باشد یک برگهٔ ساده می‌فرستیم، وگرنه
     * همان JSON قبلی تا رفتار برنامه‌ای عوض نشود.
     */
    const fileFail = (status, message) => {
      const wantsHtml = String(req.headers.accept || "").includes("text/html");
      if (!wantsHtml) return fail(res, status, message);

      const html = `<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="robots" content="noindex, nofollow" />
<title>${escapeHtml(message)}</title>
<style>
body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f7f4ee;color:#14234b;font-family:Tahoma,system-ui,sans-serif;padding:24px}
.box{max-width:26rem;background:#fff;border:1px solid #e3dbcd;border-radius:16px;padding:28px;text-align:center;box-shadow:0 12px 32px rgba(20,35,75,.06)}
.icon{width:44px;height:44px;border-radius:50%;background:#f3efe4;color:#8a6d2f;display:grid;place-items:center;margin:0 auto 14px;font-size:22px}
p{margin:0 0 20px;line-height:2;font-size:.95rem;color:#4a5568}
.links{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
a{display:inline-block;padding:10px 20px;border-radius:999px;text-decoration:none;font-size:.9rem;font-weight:600;border:1px solid #14234b}
.main{background:#14234b;color:#f7f4ee}.alt{color:#14234b}
</style>
</head>
<body>
<div class="box">
<div class="icon">!</div>
<p>${escapeHtml(message)}</p>
<div class="links">
<a class="main" href="/account.html">برگشت به حساب من</a>
<a class="alt" href="/">صفحهٔ اصلی</a>
</div>
</div>
</body>
</html>`;
      res.writeHead(status, {
        "Content-Type": "text/html; charset=utf-8",
        "Cache-Control": "no-store",
        "X-Robots-Tag": "noindex, nofollow",
      });
      res.end(html);
    };

    if (!user) {
      return fileFail(401, "برای دیدن این فایل باید وارد حساب شوی.");
    }

    const send = (mime, name, disposition) => {
      const stat = fs.statSync(fullPath);
      res.writeHead(200, {
        "Content-Type": mime || "application/octet-stream",
        "Content-Length": stat.size,
        "Content-Disposition": `${disposition}; filename*=UTF-8''${encodeURIComponent(name || "file")}`,
        "Cache-Control": "private, no-store",
        "X-Content-Type-Options": "nosniff",
      });
      fs.createReadStream(fullPath).pipe(res);
    };

    // ۱) پیوست برنامه
    const file = store.programs.file(storedName);
    if (file) {
      const program = store.programs.byId(file.program_id);
      if (!program || (!isCoach && program.user_id !== user.id)) {
        return fileFail(403, "این فایل به حساب تو مربوط نیست.");
      }
      if (!fs.existsSync(fullPath)) return fileFail(404, "فایل روی سرور نیست. با پشتیبانی هاست تماس بگیر.");
      send(file.mime, file.original_name, "attachment");
      return;
    }

    // ۲) رسید کارت‌به‌کارت — روی خود سفارش است، نه در جدول فایل‌های برنامه
    const order = store.orders.byReceipt(storedName);
    if (!order) {
      return fileFail(404, "این فایل پیدا نشد. ممکن است حذف شده باشد یا نشانی آن کامل کپی نشده باشد.");
    }
    if (!isCoach && order.user_id !== user.id) {
      return fileFail(403, "این فایل به حساب تو مربوط نیست.");
    }
    if (!fs.existsSync(fullPath)) return fileFail(404, "فایل روی سرور نیست. با پشتیبانی هاست تماس بگیر.");

    const ext = path.extname(storedName).toLowerCase().replace(".", "");
    const MIME = { jpg: "image/jpeg", jpeg: "image/jpeg", png: "image/png", webp: "image/webp", pdf: "application/pdf" };
    // «دیدن رسید» باید تصویر را نشان بدهد، نه اینکه دانلودش کند
    send(MIME[ext] || "application/octet-stream", `رسید-سفارش-${order.id}.${ext}`, "inline");
    return;
  }

  /* ---------------- بازگشت از درگاه ---------------- */
  if (p === "/api/payments/zarinpal/callback" && method === "GET") {
    const authority = url.searchParams.get("Authority") || "";
    const status = url.searchParams.get("Status") || "";
    const order = authority ? store.orders.byAuthority(authority) : null;
    if (!order) return redirect(res, "/account.html?pay=failed");
    if (status !== "OK") {
      store.orders.setStatus(order.id, "canceled");
      return redirect(res, "/account.html?pay=canceled");
    }
    const verified = await zarinpalVerify(order).catch(() => false);
    if (!verified) {
      store.orders.setStatus(order.id, "canceled");
      return redirect(res, "/account.html?pay=failed");
    }
    store.orders.setStatus(order.id, "paid");
    return redirect(res, "/account.html?pay=ok");
  }

  /* ======================= مسیرهای مربی ======================= */

  if (p === "/api/admin/students" && method === "GET") {
    if (!requireCoach()) return;
    return ok(res, { students: store.users.listStudents() });
  }

  if (p === "/api/admin/orders" && method === "GET") {
    if (!requireCoach()) return;
    return ok(res, { orders: store.orders.all(), awaiting: store.orders.awaitingCount() });
  }

  if (/^\/api\/admin\/orders\/\d+\/status$/.test(p) && method === "POST") {
    if (!sameOrigin(req)) return fail(res, 403, "درخواست معتبر نیست");
    if (!requireCoach()) return;
    const orderId = Number(seg[3]);
    const body = await readJson(req);
    const status = body.status === "paid" ? "paid" : body.status === "canceled" ? "canceled" : null;
    if (!status) return fail(res, 400, "وضعیت نامعتبر است");
    const order = store.orders.byId(orderId);
    if (!order) return fail(res, 404, "سفارش پیدا نشد");
    store.orders.setStatus(orderId, status);
    return ok(res);
  }

  /* ساخت برنامه (چندبخشی: JSON + فایل‌ها) */
  if (p === "/api/admin/programs" && method === "POST") {
    if (!sameOrigin(req)) return fail(res, 403, "درخواست معتبر نیست");
    if (!requireCoach()) return;

    const body = await readBody(req, config.maxUpload * 4 + 1024 * 1024);
    const { fields, files } = parseMultipart(body, req.headers["content-type"]);

    let payload;
    try {
      payload = JSON.parse(fields.data || "{}");
    } catch {
      return fail(res, 400, "دادهٔ برنامه معتبر نیست");
    }

    const studentId = Number(payload.userId);
    const student = store.users.byId(studentId);
    if (!student || student.role !== "student") return fail(res, 400, "شاگرد پیدا نشد");

    const title = String(payload.title || "").trim().slice(0, 120);
    if (!title) return fail(res, 400, "عنوان برنامه را بنویس");

    const orderId = payload.orderId ? Number(payload.orderId) : null;
    if (orderId) {
      const order = store.orders.byId(orderId);
      if (!order || order.user_id !== studentId) return fail(res, 400, "سفارش با این شاگرد هم‌خوان نیست");
      if (order.status !== "paid") return fail(res, 400, "این سفارش هنوز پرداخت نشده است");
    }

    const saved = saveFiles(files.slice(0, 6));
    const programId = store.programs.create({
      userId: studentId,
      orderId,
      planKey: payload.planKey || null,
      title,
      summary: String(payload.summary || "").slice(0, 500),
      startDate: payload.startDate ? String(payload.startDate).slice(0, 10) : null,
      days: cleanProgramPayload(payload),
      files: saved,
    });

    return ok(res, { programId });
  }

  /* مشاهدهٔ برنامه‌های یک شاگرد از طرف مربی */
  if (/^\/api\/admin\/students\/\d+\/programs$/.test(p) && method === "GET") {
    if (!requireCoach()) return;
    const studentId = Number(seg[3]);
    return ok(res, {
      programs: store.programs.forUser(studentId),
      orders: store.orders.forUser(studentId),
    });
  }

  /* آمار سریع پنل */
  if (p === "/api/admin/summary" && method === "GET") {
    if (!requireCoach()) return;
    return ok(res, {
      students: store.users.count() - 1 > 0 ? store.users.count() - 1 : 0,
      awaiting: store.orders.awaitingCount(),
    });
  }

  return fail(res, 404, "این مسیر وجود ندارد");
}

module.exports = { handle, json, fail };
