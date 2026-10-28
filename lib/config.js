"use strict";
/**
 * تنظیمات سرور.
 *
 * همه چیز از متغیر محیطی خوانده می‌شود تا روی هاست بدون تغییر کد قابل تنظیم باشد.
 * هیچ مقدار حساسی داخل کد نیست.
 */

const path = require("path");

const ROOT = path.resolve(__dirname, "..");

function int(v, fallback) {
  const n = parseInt(v, 10);
  return Number.isFinite(n) ? n : fallback;
}

const config = {
  root: ROOT,

  // پورت پیش‌فرض همان ۸۰۰۰ است که پیش‌نمایش محلی استفاده می‌کرد
  port: int(process.env.PORT, 8000),
  host: process.env.HOST || "0.0.0.0",

  // آدرس عمومی سایت — در بازنشانی رمز و بازگشت از درگاه ضروری است
  siteUrl: (process.env.SITE_URL || "http://localhost:8000").replace(/\/+$/, ""),

  // مسیر ذخیرهٔ دیتابیس و فایل‌های آپلودی (خارج از git)
  dataDir: process.env.DATA_DIR || path.join(ROOT, "data"),

  // درگاه زرین‌پال: تا وقتی این خالی باشد، دکمهٔ درگاه نمایش داده نمی‌شود
  zarinpal: {
    merchantId: process.env.ZARINPAL_MERCHANT || "",
    // محیط تست: https://sandbox.zarinpal.com — محیط واقعی: https://payment.zarinpal.com
    base: process.env.ZARINPAL_BASE || "https://payment.zarinpal.com",
  },

  // حساب مربی — فقط بار اول ساخته می‌شود. بعد از ساخت، رمز را از پنل عوض کنید.
  coach: {
    phone: process.env.COACH_PHONE || "",
    password: process.env.COACH_PASSWORD || "",
    name: process.env.COACH_NAME || "بهنیا",
  },

  // شمارهٔ کارت برای پرداخت کارت‌به‌کارت (متن آزاد، در صفحهٔ پرداخت نشان داده می‌شود)
  cardNumber: process.env.CARD_NUMBER || "",

  // سقف حجم آپلود (بایت) — برای رسید و فایل برنامه
  maxUpload: int(process.env.MAX_UPLOAD, 8 * 1024 * 1024),

  // عمر نشست کاربر (روز)
  sessionDays: int(process.env.SESSION_DAYS, 30),

  // پشت پروکسی HTTPS هاست (مثل لیارا/کلودفلر) کوکی باید Secure شود
  trustProxy: process.env.TRUST_PROXY === "1" || process.env.NODE_ENV === "production",
};

config.uploadDir = path.join(config.dataDir, "uploads");
config.dbPath = path.join(config.dataDir, "app.db");

/** درگاه فعال است یا نه */
config.payments = {
  get manual() {
    return true; // کارت‌به‌کارت همیشه هست
  },
  get zarinpal() {
    return Boolean(config.zarinpal.merchantId);
  },
};

module.exports = config;
