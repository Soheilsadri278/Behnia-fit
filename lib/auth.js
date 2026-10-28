"use strict";
/**
 * احراز هویت: هش رمز با scrypt، نشست با توکن تصادفی در دیتابیس.
 * رمز خام هیچ‌جا ذخیره نمی‌شود و مقایسه با timingSafeEqual انجام می‌شود.
 */

const crypto = require("crypto");
const { promisify } = require("util");
const config = require("./config");
const store = require("./store");

const scrypt = promisify(crypto.scrypt);

const COOKIE = "bf_session";
const SCRYPT = { N: 16384, r: 8, p: 1, keylen: 64 };

/* ---------------------------------------------------------------- رمز عبور */

async function hashPassword(password) {
  const salt = crypto.randomBytes(16).toString("hex");
  const buf = await scrypt(password, salt, SCRYPT.keylen, {
    N: SCRYPT.N,
    r: SCRYPT.r,
    p: SCRYPT.p,
  });
  return `scrypt$${SCRYPT.N}$${SCRYPT.r}$${SCRYPT.p}$${salt}$${buf.toString("hex")}`;
}

async function verifyPassword(password, stored) {
  try {
    const [alg, N, r, p, salt, hex] = String(stored).split("$");
    if (alg !== "scrypt") return false;
    const expected = Buffer.from(hex, "hex");
    const actual = await scrypt(password, salt, expected.length, {
      N: Number(N),
      r: Number(r),
      p: Number(p),
    });
    return crypto.timingSafeEqual(expected, actual);
  } catch {
    return false;
  }
}

/** قانون حداقلی رمز: ۸ کاراکتر */
function validPassword(pw) {
  return typeof pw === "string" && pw.length >= 8 && pw.length <= 200;
}

/* ------------------------------------------------------------------ کوکی‌ها */

function parseCookies(header) {
  const out = {};
  if (!header) return out;
  for (const part of header.split(";")) {
    const i = part.indexOf("=");
    if (i < 0) continue;
    const k = part.slice(0, i).trim();
    const v = part.slice(i + 1).trim();
    if (k) out[k] = decodeURIComponent(v);
  }
  return out;
}

function cookieHeader(token, maxAgeDays) {
  const parts = [
    `${COOKIE}=${token}`,
    "Path=/",
    "HttpOnly",
    "SameSite=Lax",
    `Max-Age=${Math.round(maxAgeDays * 86400)}`,
  ];
  if (config.trustProxy) parts.push("Secure");
  return parts.join("; ");
}

function clearCookieHeader() {
  const parts = [`${COOKIE}=`, "Path=/", "HttpOnly", "SameSite=Lax", "Max-Age=0"];
  if (config.trustProxy) parts.push("Secure");
  return parts.join("; ");
}

/* ------------------------------------------------------------------ نشست‌ها */

function startSession(userId) {
  const { token } = store.sessions.create(userId, config.sessionDays);
  return { token, cookie: cookieHeader(token, config.sessionDays) };
}

function currentUser(req) {
  const cookies = parseCookies(req.headers.cookie);
  return store.sessions.user(cookies[COOKIE]);
}

function endSession(req) {
  const cookies = parseCookies(req.headers.cookie);
  store.sessions.destroy(cookies[COOKIE]);
  return clearCookieHeader();
}

/* -------------------------------------------------- محدودیت تلاش ورود */

const attempts = new Map();
const WINDOW_MS = 15 * 60 * 1000;
const MAX_ATTEMPTS = 8;

function tooManyAttempts(ip) {
  const rec = attempts.get(ip);
  if (!rec) return false;
  if (Date.now() - rec.first > WINDOW_MS) {
    attempts.delete(ip);
    return false;
  }
  return rec.count >= MAX_ATTEMPTS;
}

function noteAttempt(ip, ok) {
  if (ok) {
    attempts.delete(ip);
    return;
  }
  const rec = attempts.get(ip);
  if (!rec || Date.now() - rec.first > WINDOW_MS) {
    attempts.set(ip, { first: Date.now(), count: 1 });
  } else {
    rec.count++;
  }
}

/* ------------------------------------------------------------ اعتبارسنجی */

/** شمارهٔ موبایل ایران را به شکل یکدست 09xxxxxxxxx درمی‌آورد */
function normalizePhone(raw) {
  if (typeof raw !== "string") return null;
  // ارقام فارسی/عربی به لاتین
  const s = raw
    .replace(/[۰-۹]/g, (d) => String(d.charCodeAt(0) - 0x06f0))
    .replace(/[٠-٩]/g, (d) => String(d.charCodeAt(0) - 0x0660))
    .replace(/[\s\-()]/g, "")
    .replace(/^\+98/, "0")
    .replace(/^0098/, "0")
    .replace(/^98(?=9\d{9}$)/, "0");
  if (!/^09\d{9}$/.test(s)) return null;
  return s;
}

function normalizeName(raw) {
  if (typeof raw !== "string") return null;
  const s = raw.trim().replace(/\s+/g, " ");
  if (s.length < 2 || s.length > 60) return null;
  return s;
}

module.exports = {
  COOKIE,
  hashPassword,
  verifyPassword,
  validPassword,
  normalizePhone,
  normalizeName,
  parseCookies,
  currentUser,
  startSession,
  endSession,
  tooManyAttempts,
  noteAttempt,
};
