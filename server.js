"use strict";
/**
 * سرور بهنیا فیت — Node خالص، بدون هیچ پکیج npm.
 *
 * اجرا:  node server.js
 * لازم:  Node نسخهٔ ۲۲٫۵ یا بالاتر (برای SQLite داخلی)
 *
 * کارها: سرو فایل‌های ایستا + API حساب کاربری و برنامه‌ها.
 */

// هشدار «آزمایشی» بودن SQLite داخلی را خفه می‌کند؛ بقیهٔ هشدارها می‌مانند
process.removeAllListeners("warning");
process.on("warning", (w) => {
  if (w.name !== "ExperimentalWarning") console.warn(`[${w.name}] ${w.message}`);
});

const http = require("http");
const fs = require("fs");
const path = require("path");

/* --- خواندن .env پیش از هر require دیگری که به تنظیمات وابسته است --- */
(function loadEnv() {
  const envPath = path.join(__dirname, ".env");
  if (!fs.existsSync(envPath)) return;
  for (const line of fs.readFileSync(envPath, "utf8").split("\n")) {
    const m = /^\s*([A-Z0-9_]+)\s*=\s*(.*)\s*$/.exec(line);
    if (!m || line.trim().startsWith("#")) continue;
    let val = m[2].trim();
    if (
      (val.startsWith('"') && val.endsWith('"')) ||
      (val.startsWith("'") && val.endsWith("'"))
    ) {
      val = val.slice(1, -1);
    }
    if (!(m[1] in process.env)) process.env[m[1]] = val;
  }
})();

const config = require("./lib/config");
const store = require("./lib/store");
const api = require("./lib/api");
const auth = require("./lib/auth");

/* ------------------------------------------------- راه‌اندازی حساب مربی */

async function bootstrapCoach() {
  if (!config.coach.phone || !config.coach.password) return;
  const phone = auth.normalizePhone(config.coach.phone);
  if (!phone) {
    console.warn("[هشدار] COACH_PHONE معتبر نیست؛ حساب مربی ساخته نشد.");
    return;
  }
  const existing = store.users.byPhone(phone);
  if (existing) {
    if (existing.role !== "coach") {
      console.warn("[هشدار] شمارهٔ COACH_PHONE قبلاً به‌عنوان شاگرد ثبت شده است.");
    }
    return;
  }
  const hash = await auth.hashPassword(config.coach.password);
  store.users.create({ phone, name: config.coach.name, passwordHash: hash, role: "coach" });
  console.log(`[راه‌اندازی] حساب مربی ساخته شد: ${phone}`);
}

/* ------------------------------------------------------------ MIME types */

const MIME = {
  ".html": "text/html; charset=utf-8",
  ".css": "text/css; charset=utf-8",
  ".js": "text/javascript; charset=utf-8",
  ".json": "application/json; charset=utf-8",
  ".svg": "image/svg+xml",
  ".png": "image/png",
  ".jpg": "image/jpeg",
  ".jpeg": "image/jpeg",
  ".webp": "image/webp",
  ".ico": "image/x-icon",
  ".woff2": "font/woff2",
  ".txt": "text/plain; charset=utf-8",
  ".pdf": "application/pdf",
  ".md": "text/plain; charset=utf-8",
  ".map": "application/json; charset=utf-8",
};

/** پوشه‌هایی که هرگز سرو نمی‌شوند */
const BLOCKED = new Set(["lib", "data", "tools", "node_modules", ".git", ".github"]);

const SECURITY_HEADERS = {
  "X-Content-Type-Options": "nosniff",
  "X-Frame-Options": "SAMEORIGIN",
  "Referrer-Policy": "strict-origin-when-cross-origin",
  "Permissions-Policy": "camera=(), microphone=(), geolocation=()",
};

/* --------------------------------------------------------- سرو فایل ایستا */

function serveStatic(req, res, pathname) {
  let rel = decodeURIComponent(pathname);

  if (rel === "/" || rel === "") rel = "/index.html";
  // آدرس بدون پسوند مثل /account را به account.html نگاشت می‌کند
  if (!path.extname(rel) && !rel.endsWith("/")) rel += ".html";

  const segments = rel.split("/").filter(Boolean);
  if (segments.some((s) => BLOCKED.has(s))) {
    return notFound(res);
  }

  const fullPath = path.join(config.root, path.normalize(rel));
  // محافظت از path traversal
  if (!fullPath.startsWith(config.root + path.sep)) return notFound(res);

  fs.stat(fullPath, (err, stat) => {
    if (err || !stat.isFile()) return notFound(res);

    const ext = path.extname(fullPath).toLowerCase();
    const type = MIME[ext] || "application/octet-stream";

    // CSS و JS هرگز کش نشوند تا باگ استایل کهنه تکرار نشود
    let cache = "no-cache";
    if (ext === ".css" || ext === ".js") cache = "no-store, must-revalidate";
    else if (/\.(png|jpe?g|webp|svg|woff2|ico)$/.test(ext)) cache = "public, max-age=86400";

    res.writeHead(200, {
      "Content-Type": type,
      "Content-Length": stat.size,
      "Cache-Control": cache,
      "X-Content-Type-Options": "nosniff",
    });

    if (req.method === "HEAD") return res.end();
    fs.createReadStream(fullPath).pipe(res);
  });
}

function notFound(res) {
  const file = path.join(config.root, "404.html");
  fs.readFile(file, (err, buf) => {
    if (err) {
      res.writeHead(404, { "Content-Type": "text/plain; charset=utf-8" });
      return res.end("صفحه پیدا نشد");
    }
    res.writeHead(404, {
      "Content-Type": "text/html; charset=utf-8",
      "Content-Length": buf.length,
    });
    res.end(buf);
  });
}

/* ------------------------------------------------------------------ سرور */

const server = http.createServer(async (req, res) => {
  for (const [k, v] of Object.entries(SECURITY_HEADERS)) res.setHeader(k, v);

  let url;
  try {
    url = new URL(req.url, `http://${req.headers.host || "localhost"}`);
  } catch {
    res.writeHead(400).end("درخواست نامعتبر");
    return;
  }

  const pathname = url.pathname;

  // مسیرهای API
  if (pathname.startsWith("/api/")) {
    try {
      await api.handle(req, res, url);
    } catch (e) {
      if (res.headersSent) return;
      const status =
        e.code === "TOO_LARGE" ? 413 : e.code === "BAD_JSON" || e.code === "BAD_FORM" ? 400 : 500;
      const message =
        e.code === "TOO_LARGE"
          ? "حجم فایل بیش از حد مجاز است"
          : e.code === "BAD_TYPE"
          ? e.message
          : "خطای سرور";
      if (status === 500) console.error("[API]", e);
      api.fail(res, status, message);
    }
    return;
  }

  if (pathname === "/healthz") {
    const body = JSON.stringify({ ok: true, node: process.version });
    res.writeHead(200, { "Content-Type": "application/json", "Content-Length": body.length });
    return res.end(body);
  }

  if (req.method !== "GET" && req.method !== "HEAD") {
    res.writeHead(405, { Connection: "close" }).end("روش مجاز نیست");
    return;
  }

  serveStatic(req, res, pathname);
});

/* هر ۶ ساعت نشست‌های منقضی پاک می‌شوند */
setInterval(() => {
  try {
    store.sessions.prune();
  } catch {}
}, 6 * 3600 * 1000).unref();

bootstrapCoach().then(() => {
  server.listen(config.port, config.host, () => {
    console.log("");
    console.log("  بهنیا فیت — سرور بالا آمد");
    console.log("  ─────────────────────────────────────");
    console.log(`  نشانی        ${config.siteUrl}`);
    console.log(`  پورت         ${config.port}`);
    console.log(`  دیتابیس      ${config.dbPath}`);
    console.log(`  درگاه زرین‌پال  ${config.payments.zarinpal ? "فعال" : "غیرفعال (کد مرچنت تنظیم نشده)"}`);
    console.log(`  Node         ${process.version}`);
    console.log("");
    if (!config.coach.phone) {
      console.log("  ⚠ حساب مربی ساخته نشده. COACH_PHONE و COACH_PASSWORD را در .env بگذارید.");
      console.log("");
    }
  });
});

process.on("SIGTERM", () => server.close(() => process.exit(0)));
process.on("SIGINT", () => server.close(() => process.exit(0)));
