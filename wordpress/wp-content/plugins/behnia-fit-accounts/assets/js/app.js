"use strict";
/**
 * ابزارهای مشترک صفحه‌های حساب کاربری.
 * بدون هیچ کتابخانه‌ای — فقط fetch و DOM.
 */

/* ------------------------------------------------------------- درخواست‌ها */

async function api(path, options = {}) {
  const opts = {
    method: options.method || "GET",
    headers: { ...(options.headers || {}) },
    credentials: "same-origin",
  };

  if (options.json !== undefined) {
    opts.headers["Content-Type"] = "application/json";
    opts.body = JSON.stringify(options.json);
  } else if (options.form) {
    opts.body = options.form;
  }

  let res;
  try {
    res = await fetch(path, opts);
  } catch {
    const err = new Error("اتصال به سرور برقرار نشد");
    err.offline = true;
    throw err;
  }

  let data = null;
  try {
    data = await res.json();
  } catch {
    /* پاسخ غیر JSON */
  }

  if (!res.ok || (data && data.ok === false)) {
    const err = new Error((data && data.error) || "خطایی رخ داد");
    err.status = res.status;
    throw err;
  }
  return data || {};
}

/* ----------------------------------------------------------------- امنیت */

/** همهٔ متن‌ها قبل از رفتن به innerHTML از این عبور می‌کنند */
function esc(value) {
  return String(value == null ? "" : value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

/* ----------------------------------------------------------------- تاریخ */

const FA_MONTHS = [
  "فروردین", "اردیبهشت", "خرداد", "تیر", "مرداد", "شهریور",
  "مهر", "آبان", "آذر", "دی", "بهمن", "اسفند",
];

/** تاریخ ISO به شمسی خوانا: ۱۰ آبان ۱۴۰۵ */
function faDate(iso) {
  if (!iso) return "";
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return "";
  try {
    const parts = new Intl.DateTimeFormat("fa-IR-u-ca-persian", {
      year: "numeric",
      month: "numeric",
      day: "numeric",
    }).formatToParts(d);
    const get = (t) => (parts.find((p) => p.type === t) || {}).value || "";
    const month = Number(get("month"));
    if (month >= 1 && month <= 12) {
      return `${get("day")} ${FA_MONTHS[month - 1]} ${get("year")}`;
    }
    return new Intl.DateTimeFormat("fa-IR").format(d);
  } catch {
    return new Intl.DateTimeFormat("fa-IR").format(d);
  }
}

/** عدد لاتین به فارسی */
function faNum(n) {
  return String(n).replace(/[0-9]/g, (d) => "۰۱۲۳۴۵۶۷۸۹"[Number(d)]);
}

/** مبلغ تومانِ بدون اعشار، با جداکنندهٔ هزارگان فارسی */
function faToman(n) {
  const value = Math.round(Number(n) || 0);
  if (!value) return "";
  try {
    return `${new Intl.NumberFormat("fa-IR", { maximumFractionDigits: 0 }).format(value)} تومان`;
  } catch {
    return `${faNum(String(value).replace(/\B(?=(\d{3})+(?!\d))/g, "٬"))} تومان`;
  }
}

/** قیمت نمایشی پلن؛ تومان اگر سرور فرستاده باشد، وگرنه دلار پایه */
function planPriceText(plan) {
  if (plan && Number(plan.price_toman) > 0) {
    // والد این عدد برای قیمت دلاری چپ‌به‌راست است؛ مبلغ تومان باید راست‌به‌چپ بماند.
    return `<span dir="rtl">${faToman(plan.price_toman)}</span>`;
  }
  return `$${faNum(plan ? plan.price_usd : 0)}`;
}

/** مبلغ نمایشی سفارش؛ مبلغ تومانیِ ذخیره‌شده در لحظهٔ ثبت سفارش */
function orderAmountText(order) {
  if (order && Number(order.amount_toman) > 0) return faToman(order.amount_toman);
  return `$${faNum(order ? order.amount_usd : 0)}`;
}

/* ------------------------------------------------------------------ پیام */

let toastTimer = null;
function toast(message) {
  let el = document.querySelector(".toast");
  if (!el) {
    el = document.createElement("div");
    el.className = "toast";
    el.setAttribute("role", "status");
    document.body.appendChild(el);
  }
  el.textContent = message;
  // بازنشانی انیمیشن
  el.classList.remove("is-on");
  void el.offsetWidth;
  el.classList.add("is-on");
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => el.classList.remove("is-on"), 3200);
}

/* --------------------------------------------------------------- نوار بالا */

function initAppbar(user) {
  const box = document.getElementById("appbarActions");
  if (!box) return;

  if (!user) {
    box.innerHTML = `<a class="btn btn--ghost btn--small" href="login.html">ورود / ثبت‌نام</a>`;
    return;
  }

  const label = user.role === "coach" ? "پنل مربی" : "حساب من";
  box.innerHTML = `
    <span class="appbar__user">${esc(user.name)}</span>
    <a class="btn btn--ghost btn--small" href="${
      user.role === "coach" ? "admin.html" : "account.html"
    }">${label}</a>
    <button class="btn btn--ghost btn--small" type="button" id="logoutBtn">خروج</button>
  `;

  const out = box.querySelector("#logoutBtn");
  if (out) {
    out.addEventListener("click", async () => {
      try {
        await api("/api/auth/logout", { method: "POST" });
      } catch {}
      location.href = "index.html";
    });
  }
}

/** نشست را می‌گیرد؛ اگر لازم بود به صفحهٔ ورود می‌فرستد */
async function requireUser(redirectTo) {
  const data = await api("/api/me");
  if (!data.user) {
    const here = location.pathname.split("/").pop() + location.search;
    const next = encodeURIComponent(redirectTo || here);
    location.replace(`login.html?next=${next}`);
    return null;
  }
  return data.user;
}

/** پیام خطا را زیر فیلد نمایش می‌دهد */
function fieldError(input, message) {
  if (!input) return;
  const box = document.getElementById(`${input.id}-error`);
  input.setAttribute("aria-invalid", message ? "true" : "false");
  if (!box) return;
  box.textContent = message || "";
  box.classList.toggle("is-on", Boolean(message));
}

function setBusy(button, busy, labelWhenBusy) {
  if (!button) return;
  if (busy) {
    button.dataset.label = button.innerHTML;
    button.setAttribute("aria-busy", "true");
    button.innerHTML = `<span class="spinner"></span> ${esc(labelWhenBusy || "کمی صبر کن…")}`;
  } else {
    button.removeAttribute("aria-busy");
    if (button.dataset.label) button.innerHTML = button.dataset.label;
  }
}

/** آیکون‌های SVG مشترک */
const ICONS = {
  chevron: `<svg class="program__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>`,
  dumbbell: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6.5 6.5v11M3.5 9v5M17.5 6.5v11M20.5 9v5M6.5 12h11"/></svg>`,
  download: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>`,
  print: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-2M6 14h12v7H6z"/></svg>`,
  box: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg>`,
  info: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>`,
  check: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m5 13 4 4L19 7"/></svg>`,
  warn: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 9v4m0 4h.01M10.3 4.3 2.6 17a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L14.7 4.3a2 2 0 0 0-3.4 0Z"/></svg>`,
};
