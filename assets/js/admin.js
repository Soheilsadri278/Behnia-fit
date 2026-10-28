"use strict";
/**
 * پنل مربی: تایید سفارش‌ها و ساخت برنامه برای شاگردها.
 */

let A = { user: null, students: [], orders: [], files: [] };
let DAY_COUNT = 0;

/* ------------------------------------------------------------ بارگذاری */

async function boot() {
  let user;
  try {
    user = await requireUser("admin.html");
  } catch (err) {
    document.getElementById("ordersBox").innerHTML =
      `<div class="notice notice--err">${ICONS.warn}<div>${esc(err.message)}</div></div>`;
    return;
  }
  if (!user) return;
  if (user.role !== "coach") {
    location.replace("account.html");
    return;
  }

  A.user = user;
  initAppbar(user);
  addDay();
  await refresh();
}

async function refresh() {
  try {
    const [students, orders] = await Promise.all([
      api("/api/admin/students"),
      api("/api/admin/orders"),
    ]);
    A.students = students.students || [];
    A.orders = orders.orders || [];
  } catch (err) {
    document.getElementById("ordersBox").innerHTML =
      `<div class="notice notice--err">${ICONS.warn}<div>${esc(err.message)}</div></div>`;
    return;
  }
  renderStats();
  renderOrders();
  renderStudents();
  fillStudentSelect();
}

/* ------------------------------------------------------------------ آمار */

function renderStats() {
  const open = A.orders.filter((o) => o.status === "pending" || o.status === "awaiting_confirm").length;
  const paid = A.orders.filter((o) => o.status === "paid").length;
  document.getElementById("stats").innerHTML = `
    <div class="stat"><span class="stat__n">${faNum(A.students.length)}</span><span class="stat__label">شاگرد</span></div>
    <div class="stat"><span class="stat__n">${faNum(open)}</span><span class="stat__label">سفارش در انتظار</span></div>
    <div class="stat"><span class="stat__n">${faNum(paid)}</span><span class="stat__label">پرداخت تاییدشده</span></div>
  `;
}

/* --------------------------------------------------------------- سفارش‌ها */

const STATUS = {
  pending: ["badge--pending", "در انتظار پرداخت"],
  awaiting_confirm: ["badge--waiting", "رسید فرستاده شده"],
  paid: ["badge--paid", "پرداخت شده"],
  canceled: ["badge--canceled", "لغو شده"],
};

function renderOrders() {
  const box = document.getElementById("ordersBox");

  if (!A.orders.length) {
    box.innerHTML = `<p class="field__hint" style="margin:0">هنوز سفارشی ثبت نشده است.</p>`;
    return;
  }

  box.innerHTML = A.orders
    .map((o) => {
      const [cls, label] = STATUS[o.status] || ["badge--canceled", o.status];
      const canApprove = o.status === "awaiting_confirm" || o.status === "pending";
      return `
      <div class="order-row" data-order="${o.id}">
        <div class="order-row__main">
          <div class="order-row__name">${esc(o.student_name)} <span style="font-weight:400;color:var(--muted);font-size:.78rem" dir="ltr">${esc(o.student_phone)}</span></div>
          <div class="order-row__sub">
            ${esc(o.plan_title)} · $${faNum(o.amount_usd)} · ${esc(faDate(o.created_at))}
            ${o.receipt_file ? " · رسید ارسال شده" : ""}
          </div>
        </div>
        <span class="badge ${cls}">${esc(label)}</span>
        <div class="order-row__actions">
          ${o.receipt_file ? `<a class="btn btn--ghost btn--small" href="/api/files/${encodeURIComponent(o.receipt_file)}" target="_blank" rel="noopener">دیدن رسید</a>` : ""}
          ${canApprove ? `<button class="btn btn--accent btn--small" type="button" data-approve="${o.id}">تایید پرداخت</button>` : ""}
          ${o.status === "paid" ? `<button class="btn btn--ghost btn--small" type="button" data-make="${o.id}">ساخت برنامه</button>` : ""}
        </div>
      </div>`;
    })
    .join("");

  box.querySelectorAll("[data-approve]").forEach((btn) =>
    btn.addEventListener("click", () => setOrderStatus(Number(btn.dataset.approve), "paid", btn))
  );
  box.querySelectorAll("[data-make]").forEach((btn) =>
    btn.addEventListener("click", () => prefillFromOrder(Number(btn.dataset.make)))
  );
}

async function setOrderStatus(id, status, btn) {
  setBusy(btn, true, "…");
  try {
    await api(`/api/admin/orders/${id}/status`, { method: "POST", json: { status } });
    toast(status === "paid" ? "پرداخت تایید شد" : "سفارش لغو شد");
    await refresh();
  } catch (err) {
    setBusy(btn, false);
    toast(err.message);
  }
}

/* ---------------------------------------------------------------- شاگردها */

function renderStudents() {
  const box = document.getElementById("studentsBox");
  if (!A.students.length) {
    box.innerHTML = `<p class="field__hint" style="margin:0">هنوز کسی ثبت‌نام نکرده است.</p>`;
    return;
  }
  box.innerHTML = A.students
    .map(
      (s) => `
      <div class="order-row">
        <div class="order-row__main">
          <div class="order-row__name">${esc(s.name)}</div>
          <div class="order-row__sub" dir="ltr">${esc(s.phone)}</div>
        </div>
        <span class="badge badge--new">${faNum(s.program_count)} برنامه</span>
        ${s.open_orders ? `<span class="badge badge--waiting">${faNum(s.open_orders)} سفارش باز</span>` : ""}
        <div class="order-row__actions">
          <button class="btn btn--ghost btn--small" type="button" data-build="${s.id}">برنامهٔ جدید</button>
        </div>
      </div>`
    )
    .join("");

  box.querySelectorAll("[data-build]").forEach((btn) =>
    btn.addEventListener("click", () => {
      const sel = document.getElementById("bStudent");
      sel.value = btn.dataset.build;
      updateOrderOptions();
      document.getElementById("builderForm").scrollIntoView({ behavior: "smooth", block: "start" });
    })
  );
}

function fillStudentSelect() {
  const sel = document.getElementById("bStudent");
  const current = sel.value;
  sel.innerHTML = A.students
    .map((s) => `<option value="${s.id}">${esc(s.name)} — ${esc(s.phone)}</option>`)
    .join("");
  if (current) sel.value = current;
  updateOrderOptions();
}

function updateOrderOptions() {
  const studentId = Number(document.getElementById("bStudent").value);
  const sel = document.getElementById("bOrder");
  const mine = A.orders.filter((o) => o.user_id === studentId && o.status === "paid");
  sel.innerHTML =
    `<option value="">— بدون سفارش —</option>` +
    mine
      .map(
        (o) =>
          `<option value="${o.id}">#${o.id} ${esc(o.plan_title)} — $${faNum(o.amount_usd)}</option>`
      )
      .join("");
}

function prefillFromOrder(orderId) {
  const order = A.orders.find((o) => o.id === orderId);
  if (!order) return;
  document.getElementById("bStudent").value = order.user_id;
  updateOrderOptions();
  document.getElementById("bOrder").value = String(orderId);
  const title = document.getElementById("bTitle");
  if (!title.value) title.value = order.plan_title;
  document.getElementById("builderForm").scrollIntoView({ behavior: "smooth", block: "start" });
  document.getElementById("bTitle").focus();
}

/* ------------------------------------------------------------ ساخت برنامه */

function addDay() {
  const box = document.getElementById("daysBox");
  const id = ++DAY_COUNT;

  const el = document.createElement("div");
  el.className = "builder__day";
  el.dataset.day = String(id);
  el.innerHTML = `
    <div style="display:flex;gap:.5rem;align-items:flex-end;flex-wrap:wrap">
      <label class="field" style="flex:1;min-width:180px;margin:0">
        <span class="field__label">عنوان روز</span>
        <input class="input" data-role="label" type="text" placeholder="روز ۱ — سینه و پشت" />
      </label>
      <button class="btn btn--ghost btn--small" type="button" data-remove-day>حذف روز</button>
    </div>
    <div data-rows style="margin-top:.6rem"></div>
    <button class="btn btn--ghost btn--small" type="button" data-add-row style="margin-top:.5rem">+ حرکت</button>
  `;
  box.appendChild(el);

  el.querySelector("[data-add-row]").addEventListener("click", () => addRow(el));
  el.querySelector("[data-remove-day]").addEventListener("click", () => {
    el.remove();
    if (!box.children.length) addDay();
  });

  addRow(el);
  return el;
}

function addRow(dayEl) {
  const rows = dayEl.querySelector("[data-rows]");
  const row = document.createElement("div");
  row.className = "builder__row";
  row.innerHTML = `
    <input class="input" data-role="exercise" type="text" placeholder="حرکت" />
    <input class="input" data-role="sets" type="text" placeholder="ست" />
    <input class="input" data-role="reps" type="text" placeholder="تکرار" />
    <input class="input" data-role="rest" type="text" placeholder="استراحت" />
    <input class="input" data-role="note" type="text" placeholder="توضیح" />
    <button class="btn btn--ghost btn--small" type="button" data-remove>×</button>
  `;
  rows.appendChild(row);
  row.querySelector("[data-remove]").addEventListener("click", () => row.remove());
}

function collectDays() {
  return [...document.querySelectorAll("#daysBox .builder__day")]
    .map((day) => {
      const label = day.querySelector('[data-role="label"]').value.trim();
      const items = [...day.querySelectorAll(".builder__row")]
        .map((row) => ({
          exercise: row.querySelector('[data-role="exercise"]').value.trim(),
          sets: row.querySelector('[data-role="sets"]').value.trim(),
          reps: row.querySelector('[data-role="reps"]').value.trim(),
          rest: row.querySelector('[data-role="rest"]').value.trim(),
          note: row.querySelector('[data-role="note"]').value.trim(),
        }))
        .filter((it) => it.exercise);
      return { label, items };
    })
    .filter((d) => d.label || d.items.length);
}

/* ------------------------------------------------------------------ فایل */

function initFiles() {
  const input = document.getElementById("bFiles");
  const drop = document.getElementById("fileDrop");
  const nameEl = document.getElementById("filesName");

  function setFiles(list) {
    A.files = [...list].slice(0, 6);
    if (!A.files.length) {
      nameEl.textContent = "";
      return;
    }
    nameEl.textContent = A.files.map((f) => f.name).join(" · ");
  }

  input.addEventListener("change", () => setFiles(input.files));
  ["dragenter", "dragover"].forEach((ev) =>
    drop.addEventListener(ev, (e) => {
      e.preventDefault();
      drop.classList.add("is-over");
    })
  );
  ["dragleave", "drop"].forEach((ev) =>
    drop.addEventListener(ev, (e) => {
      e.preventDefault();
      drop.classList.remove("is-over");
    })
  );
  drop.addEventListener("drop", (e) => setFiles(e.dataTransfer.files));
}

/* ----------------------------------------------------------------- ارسال */

async function submitProgram(e) {
  e.preventDefault();
  const btn = document.getElementById("bSubmit");
  const errBox = document.getElementById("builderError");
  errBox.innerHTML = "";

  const studentId = Number(document.getElementById("bStudent").value);
  const title = document.getElementById("bTitle").value.trim();
  const summary = document.getElementById("bSummary").value.trim();
  const startDate = document.getElementById("bStart").value.trim();
  const orderId = document.getElementById("bOrder").value;

  fieldError(document.getElementById("bTitle"), "");
  if (!studentId) {
    errBox.innerHTML = `<div class="notice notice--err">${ICONS.warn}<div>اول شاگرد را انتخاب کن.</div></div>`;
    return;
  }
  if (!title) {
    fieldError(document.getElementById("bTitle"), "عنوان برنامه لازم است");
    document.getElementById("bTitle").focus();
    return;
  }

  const days = collectDays();
  if (!days.length) {
    errBox.innerHTML = `<div class="notice notice--warn">${ICONS.info}<div>حداقل یک روز با یک حرکت وارد کن.</div></div>`;
    return;
  }

  const payload = {
    userId: studentId,
    orderId: orderId ? Number(orderId) : null,
    title,
    summary,
    startDate,
    days,
  };

  const form = new FormData();
  form.append("data", JSON.stringify(payload));
  A.files.forEach((f, i) => form.append(`file${i}`, f, f.name));

  setBusy(btn, true, "در حال ثبت…");
  try {
    await api("/api/admin/programs", { method: "POST", form });
    toast("برنامه ثبت شد و در حساب شاگرد ظاهر شد");

    // پاک‌سازی فرم
    document.getElementById("builderForm").reset();
    document.getElementById("daysBox").innerHTML = "";
    DAY_COUNT = 0;
    A.files = [];
    document.getElementById("filesName").textContent = "";
    addDay();
    await refresh();
  } catch (err) {
    setBusy(btn, false);
    errBox.innerHTML = `<div class="notice notice--err">${ICONS.warn}<div>${esc(err.message)}</div></div>`;
  }
}

/* ------------------------------------------------------------------ شروع */

document.getElementById("addDay").addEventListener("click", addDay);
document.getElementById("bStudent").addEventListener("change", updateOrderOptions);
initFiles();
document.getElementById("builderForm").addEventListener("submit", submitProgram);

boot();
