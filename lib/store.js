"use strict";
/**
 * لایهٔ داده — SQLite داخلی Node (بدون هیچ پکیج npm).
 *
 * نکتهٔ مهم دربارهٔ «برنامهٔ قبلی حذف نشود»:
 *   جدول programs فقط INSERT می‌شود. هیچ مسیری در کل برنامه دستور DELETE
 *   روی programs ندارد. با هر خرید جدید یک ردیف تازه اضافه می‌شود و
 *   قدیمی‌ها برای همیشه می‌مانند.
 */

const fs = require("fs");
const path = require("path");
const config = require("./config");

let DatabaseSync;
try {
  ({ DatabaseSync } = require("node:sqlite"));
} catch (e) {
  console.error(
    "\n[خطا] این نسخهٔ Node از SQLite داخلی پشتیبانی نمی‌کند.\n" +
      "        Node نسخهٔ ۲۲٫۵ یا بالاتر لازم است. نسخهٔ فعلی: " +
      process.version +
      "\n"
  );
  process.exit(1);
}

fs.mkdirSync(config.dataDir, { recursive: true });
fs.mkdirSync(config.uploadDir, { recursive: true });

/**
 * اگر فایل دیتابیس سر جایش نباشد (پاک شده یا هنوز ساخته نشده)، بدون خطا
 * از نو ساخته می‌شود. اگر سرور هنوز در حال اجراست، سرورِ در حال اجرا از
 * فایلِ پاک‌شده چیزی نمی‌داند و باید ری‌استارت شود — این طبیعی است.
 */
const db = new DatabaseSync(config.dbPath);

db.exec("PRAGMA journal_mode = WAL");
db.exec("PRAGMA foreign_keys = ON");
db.exec("PRAGMA busy_timeout = 5000");

/* ---------------------------------------------------------------- اسکیما */

db.exec(`
CREATE TABLE IF NOT EXISTS users (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  phone         TEXT NOT NULL UNIQUE,
  name          TEXT NOT NULL,
  password_hash TEXT NOT NULL,
  role          TEXT NOT NULL DEFAULT 'student' CHECK (role IN ('student', 'coach')),
  created_at    TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS sessions (
  token      TEXT PRIMARY KEY,
  user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  expires_at TEXT NOT NULL,
  created_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_sessions_user ON sessions(user_id);

CREATE TABLE IF NOT EXISTS plans (
  key         TEXT PRIMARY KEY,
  title       TEXT NOT NULL,
  description TEXT,
  price_usd   INTEGER NOT NULL,
  period      TEXT NOT NULL,
  sort        INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS orders (
  id           INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id      INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  plan_key     TEXT NOT NULL REFERENCES plans(key),
  amount_usd   INTEGER NOT NULL,
  status       TEXT NOT NULL DEFAULT 'pending'
               CHECK (status IN ('pending', 'awaiting_confirm', 'paid', 'canceled')),
  method       TEXT NOT NULL CHECK (method IN ('manual', 'zarinpal')),
  authority    TEXT,
  receipt_file TEXT,
  note         TEXT,
  created_at   TEXT NOT NULL,
  paid_at      TEXT
);
CREATE INDEX IF NOT EXISTS idx_orders_user ON orders(user_id);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);

CREATE TABLE IF NOT EXISTS programs (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  order_id   INTEGER REFERENCES orders(id),
  plan_key   TEXT,
  title      TEXT NOT NULL,
  summary    TEXT,
  start_date TEXT,
  created_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_programs_user ON programs(user_id);

CREATE TABLE IF NOT EXISTS program_days (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  program_id INTEGER NOT NULL REFERENCES programs(id) ON DELETE CASCADE,
  label      TEXT NOT NULL,
  note       TEXT,
  sort       INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS idx_days_program ON program_days(program_id);

CREATE TABLE IF NOT EXISTS program_items (
  id       INTEGER PRIMARY KEY AUTOINCREMENT,
  day_id   INTEGER NOT NULL REFERENCES program_days(id) ON DELETE CASCADE,
  exercise TEXT NOT NULL,
  sets     TEXT,
  reps     TEXT,
  rest     TEXT,
  note     TEXT,
  sort     INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS idx_items_day ON program_items(day_id);

CREATE TABLE IF NOT EXISTS program_files (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  program_id    INTEGER NOT NULL REFERENCES programs(id) ON DELETE CASCADE,
  stored_name   TEXT NOT NULL,
  original_name TEXT NOT NULL,
  mime          TEXT,
  size          INTEGER,
  created_at    TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_files_program ON program_files(program_id);
`);

/* ------------------------------------------------------------ ابزار پایه */

const q = {
  all: (sql, ...p) => db.prepare(sql).all(...p).map((r) => ({ ...r })),
  get: (sql, ...p) => {
    const r = db.prepare(sql).get(...p);
    return r ? { ...r } : null;
  },
  run: (sql, ...p) => db.prepare(sql).run(...p),
};

const now = () => new Date().toISOString();

/* --------------------------------------------------------- کاتالوگ پلن‌ها */

// قیمت‌ها همان چیزی است که روی صفحهٔ اصلی سایت نوشته شده (دلار)
const PLAN_SEED = [
  ["nutrition", "برنامهٔ تغذیه", "برنامهٔ غذایی متناسب با هدف و سبک زندگی تو", 4, "once", 1],
  ["training", "برنامهٔ تمرینی", "برنامهٔ اختصاصی تمرین با اصلاح فرم و پیشرفت هفتگی", 8, "once", 2],
  ["online", "کوچینگ آنلاین", "پیگیری کامل تمرین و تغذیه با بازبینی ماهانه", 15, "month", 3],
  ["inperson", "تمرین حضوری ۱:۱", "جلسهٔ حضوری در کرج، مهرشهر با اصلاح فرم زنده", 30, "session", 4],
];

function seedPlans() {
  const stmt = db.prepare(
    `INSERT INTO plans (key, title, description, price_usd, period, sort)
     VALUES (?, ?, ?, ?, ?, ?)
     ON CONFLICT(key) DO NOTHING`
  );
  for (const p of PLAN_SEED) stmt.run(...p);
}
seedPlans();

/* ------------------------------------------------------------------ users */

const users = {
  create({ phone, name, passwordHash, role = "student" }) {
    const info = q.run(
      `INSERT INTO users (phone, name, password_hash, role, created_at)
       VALUES (?, ?, ?, ?, ?)`,
      phone,
      name,
      passwordHash,
      role,
      now()
    );
    return Number(info.lastInsertRowid);
  },

  byPhone(phone) {
    return q.get(`SELECT * FROM users WHERE phone = ?`, phone);
  },

  byId(id) {
    return q.get(`SELECT * FROM users WHERE id = ?`, id);
  },

  /** فهرست شاگردها برای پنل مربی، همراه با تعداد برنامه و آخرین خرید */
  listStudents() {
    return q.all(
      `SELECT u.id, u.phone, u.name, u.created_at,
              (SELECT COUNT(*) FROM programs p WHERE p.user_id = u.id)   AS program_count,
              (SELECT MAX(p.created_at) FROM programs p WHERE p.user_id = u.id) AS last_program_at,
              (SELECT COUNT(*) FROM orders o
                WHERE o.user_id = u.id AND o.status IN ('pending','awaiting_confirm')) AS open_orders
         FROM users u
        WHERE u.role = 'student'
        ORDER BY u.created_at DESC`
    );
  },

  count() {
    return q.get(`SELECT COUNT(*) AS n FROM users`).n;
  },
};

/* --------------------------------------------------------------- sessions */

const sessions = {
  create(userId, days) {
    const token = require("crypto").randomBytes(32).toString("hex");
    const exp = new Date(Date.now() + days * 86400000).toISOString();
    q.run(
      `INSERT INTO sessions (token, user_id, expires_at, created_at) VALUES (?, ?, ?, ?)`,
      token,
      userId,
      exp,
      now()
    );
    return { token, expiresAt: exp };
  },

  /** کاربر معتبر برمی‌گرداند یا null */
  user(token) {
    if (!token) return null;
    const row = q.get(
      `SELECT s.expires_at, u.*
         FROM sessions s JOIN users u ON u.id = s.user_id
        WHERE s.token = ?`,
      token
    );
    if (!row) return null;
    if (new Date(row.expires_at).getTime() < Date.now()) {
      sessions.destroy(token);
      return null;
    }
    delete row.expires_at;
    return row;
  },

  destroy(token) {
    if (token) q.run(`DELETE FROM sessions WHERE token = ?`, token);
  },

  prune() {
    q.run(`DELETE FROM sessions WHERE expires_at < ?`, now());
  },
};

/* ------------------------------------------------------------------ plans */

const plans = {
  all() {
    return q.all(`SELECT * FROM plans ORDER BY sort`);
  },
  byKey(key) {
    return q.get(`SELECT * FROM plans WHERE key = ?`, key);
  },
};

/* ----------------------------------------------------------------- orders */

const orders = {
  create({ userId, planKey, amount, method, note }) {
    const info = q.run(
      `INSERT INTO orders (user_id, plan_key, amount_usd, status, method, note, created_at)
       VALUES (?, ?, ?, 'pending', ?, ?, ?)`,
      userId,
      planKey,
      amount,
      method,
      note || null,
      now()
    );
    return Number(info.lastInsertRowid);
  },

  byId(id) {
    return q.get(`SELECT * FROM orders WHERE id = ?`, id);
  },

  forUser(userId) {
    return q.all(
      `SELECT o.*, p.title AS plan_title, p.period
         FROM orders o JOIN plans p ON p.key = o.plan_key
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC`,
      userId
    );
  },

  /** همهٔ سفارش‌های شاگردان برای پنل مربی */
  all(limit = 200) {
    return q.all(
      `SELECT o.*, p.title AS plan_title, u.name AS student_name, u.phone AS student_phone
         FROM orders o
         JOIN plans p ON p.key = o.plan_key
         JOIN users u ON u.id = o.user_id
        ORDER BY CASE o.status
                   WHEN 'awaiting_confirm' THEN 0
                   WHEN 'pending' THEN 1
                   WHEN 'paid' THEN 2
                   ELSE 3
                 END, o.created_at DESC
        LIMIT ?`,
      limit
    );
  },

  awaitingCount() {
    return q.get(
      `SELECT COUNT(*) AS n FROM orders WHERE status IN ('pending','awaiting_confirm')`
    ).n;
  },

  setReceipt(id, userId, fileName) {
    return q.run(
      `UPDATE orders SET receipt_file = ?, status = 'awaiting_confirm'
        WHERE id = ? AND user_id = ? AND status = 'pending'`,
      fileName,
      id,
      userId
    );
  },

  setStatus(id, status) {
    const paidAt = status === "paid" ? now() : null;
    return q.run(
      `UPDATE orders SET status = ?, paid_at = COALESCE(?, paid_at) WHERE id = ?`,
      status,
      paidAt,
      id
    );
  },

  setAuthority(id, authority) {
    return q.run(`UPDATE orders SET authority = ? WHERE id = ?`, authority, id);
  },

  byAuthority(authority) {
    return q.get(`SELECT * FROM orders WHERE authority = ?`, authority);
  },

  /** سفارش بر اساس نام فایل رسید — برای دیدن رسید در پنل مربی */
  byReceipt(storedName) {
    return q.get(`SELECT * FROM orders WHERE receipt_file = ?`, storedName);
  },
};

/* -------------------------------------------------------------- programs */

const programs = {
  /**
   * ساخت برنامه. days: [{ label, note, items: [{exercise, sets, reps, rest, note}] }]
   * files: [{ storedName, originalName, mime, size }]
   * همه در یک تراکنش، تا برنامهٔ نیمه‌ساخته ساخته نشود.
   */
  create({ userId, orderId, planKey, title, summary, startDate, days = [], files = [] }) {
    q.run("BEGIN");
    try {
      const info = q.run(
        `INSERT INTO programs (user_id, order_id, plan_key, title, summary, start_date, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?)`,
        userId,
        orderId || null,
        planKey || null,
        title,
        summary || null,
        startDate || null,
        now()
      );
      const programId = Number(info.lastInsertRowid);

      days.forEach((d, di) => {
        const dinfo = q.run(
          `INSERT INTO program_days (program_id, label, note, sort) VALUES (?, ?, ?, ?)`,
          programId,
          d.label || `روز ${di + 1}`,
          d.note || null,
          di
        );
        const dayId = Number(dinfo.lastInsertRowid);
        (d.items || []).forEach((it, ii) => {
          q.run(
            `INSERT INTO program_items (day_id, exercise, sets, reps, rest, note, sort)
             VALUES (?, ?, ?, ?, ?, ?, ?)`,
            dayId,
            it.exercise,
            it.sets || null,
            it.reps || null,
            it.rest || null,
            it.note || null,
            ii
          );
        });
      });

      for (const f of files) {
        q.run(
          `INSERT INTO program_files (program_id, stored_name, original_name, mime, size, created_at)
           VALUES (?, ?, ?, ?, ?, ?)`,
          programId,
          f.storedName,
          f.originalName,
          f.mime || null,
          f.size || null,
          now()
        );
      }

      q.run("COMMIT");
      return programId;
    } catch (e) {
      q.run("ROLLBACK");
      throw e;
    }
  },

  /** فهرست برنامه‌های یک شاگرد — جدیدترین اول، همه حفظ می‌شوند */
  forUser(userId) {
    return q.all(
      `SELECT p.*, pl.title AS plan_title,
              (SELECT COUNT(*) FROM program_days d WHERE d.program_id = p.id) AS day_count,
              (SELECT COUNT(*) FROM program_files f WHERE f.program_id = p.id) AS file_count
         FROM programs p
         LEFT JOIN plans pl ON pl.key = p.plan_key
        WHERE p.user_id = ?
        ORDER BY p.created_at DESC, p.id DESC`,
      userId
    );
  },

  /** برنامهٔ کامل با روزها، حرکت‌ها و فایل‌ها — فقط برای صاحب برنامه یا مربی */
  full(id, userId, isCoach) {
    const p = q.get(`SELECT * FROM programs WHERE id = ?`, id);
    if (!p) return null;
    if (!isCoach && p.user_id !== userId) return null;

    const days = q.all(
      `SELECT * FROM program_days WHERE program_id = ? ORDER BY sort, id`,
      id
    );
    for (const d of days) {
      d.items = q.all(
        `SELECT * FROM program_items WHERE day_id = ? ORDER BY sort, id`,
        d.id
      );
    }
    p.days = days;
    p.files = q.all(
      `SELECT id, stored_name, original_name, mime, size FROM program_files
        WHERE program_id = ? ORDER BY id`,
      id
    );
    return p;
  },

  byId(id) {
    return q.get(`SELECT * FROM programs WHERE id = ?`, id);
  },

  file(storedName) {
    return q.get(`SELECT * FROM program_files WHERE stored_name = ?`, storedName);
  },

  countForUser(userId) {
    return q.get(`SELECT COUNT(*) AS n FROM programs WHERE user_id = ?`, userId).n;
  },
};

module.exports = { db, q, now, users, sessions, plans, orders, programs };
