"use strict";
/**
 * صفحهٔ حساب شاگرد: برنامه‌ها، خریدها و خرید جدید.
 *
 * همهٔ متن‌ها با esc() امن می‌شوند و هیچ innerHTML خامی از دادهٔ سرور نمی‌آید.
 */

let STATE = { user: null, plans: [], programs: [], orders: [], zarinpal: false };
let PICKED = null;

/* ------------------------------------------------------------ بارگذاری */

async function boot() {
  let user;
  try {
    user = await requireUser();
  } catch (err) {
    renderFatal(err.message);
    return;
  }
  if (!user) return;
  if (user.role === "coach") {
    location.replace("admin.html");
    return;
  }

  STATE.user = user;
  initAppbar(user);
  document.getElementById("greeting").textContent = `سلام ${user.name}`;

  showPayNotice();
  await Promise.all([loadPlans(), loadData()]);
}

function renderFatal(message) {
  const box = document.getElementById("programsBox");
  if (!box) return;
  box.innerHTML = `
    <div class="notice notice--err">
      ${ICONS.warn}
      <div>
        <b>ارتباط با سرور برقرار نشد.</b><br>
        ${esc(message)}
        <br><span style="font-size:.8rem">اگر این صفحه را روی GitHub Pages می‌بینی، بخش حساب کاربری به سرور نیاز دارد.</span>
      </div>
    </div>`;
}

function showPayNotice() {
  const flag = new URLSearchParams(location.search).get("pay");
  if (!flag) return;
  const box = document.getElementById("payNotice");
  const map = {
    ok: ["notice--ok", ICONS.check, "پرداخت با موفقیت انجام شد. برنامه‌ات را مربی به‌زودی اینجا می‌گذارد."],
    failed: ["notice--err", ICONS.warn, "پرداخت انجام نشد. اگر مبلغی کم شده، با واتساپ تماس بگیر."],
    canceled: ["notice--warn", ICONS.info, "پرداخت لغو شد. سفارش باز نشد."],
  };
  const item = map[flag];
  if (!item) return;
  box.innerHTML = `<div class="notice ${item[0]}">${item[1]}<div>${esc(item[2])}</div></div>`;
  history.replaceState(null, "", "account.html");
}

async function loadPlans() {
  try {
    const data = await api("/api/plans");
    STATE.plans = data.plans || [];
    STATE.zarinpal = Boolean(data.zarinpal);
    renderPlans();

    // اگر از دکمهٔ قیمت‌های صفحهٔ اصلی آمده باشد، همان پلن انتخاب شود
    const wanted = new URLSearchParams(location.search).get("plan");
    if (wanted && STATE.plans.some((p) => p.key === wanted)) pickPlan(wanted);
  } catch {
    document.getElementById("plansBox").innerHTML =
      `<p class="field__hint">کاتالوگ برنامه‌ها بارگذاری نشد.</p>`;
  }
}

async function loadData() {
  try {
    const data = await api("/api/me/programs");
    STATE.programs = data.programs || [];
    STATE.orders = data.orders || [];
  } catch (err) {
    renderFatal(err.message);
    return;
  }
  renderPrograms();
  renderOrders();
}

/* ---------------------------------------------------------- پلن‌ها و خرید */

function renderPlans() {
  const box = document.getElementById("plansBox");
  if (!STATE.plans.length) {
    box.innerHTML = `<p class="field__hint">فعلاً برنامه‌ای برای فروش تعریف نشده است.</p>`;
    return;
  }

  box.innerHTML = STATE.plans
    .map((p) => {
      const period = p.period === "once" ? "یک‌بار" : p.period === "month" ? "ماهانه" : "هر جلسه";
      return `
      <div class="plan" data-key="${esc(p.key)}">
        <div class="plan__top">
          <h3 class="plan__title">${esc(p.title)}</h3>
          <div class="plan__price">${planPriceText(p)}<small>${period}</small></div>
        </div>
        <p class="plan__desc">${esc(p.description || "")}</p>
        <button class="btn btn--ghost btn--small" type="button" data-pick="${esc(p.key)}">انتخاب و خرید</button>
      </div>`;
    })
    .join("");

  box.querySelectorAll("[data-pick]").forEach((btn) => {
    btn.addEventListener("click", () => pickPlan(btn.dataset.pick));
  });
}

function pickPlan(key) {
  PICKED = STATE.plans.find((p) => p.key === key) || null;
  document.querySelectorAll(".plan").forEach((el) => {
    el.classList.toggle("is-picked", el.dataset.key === key);
  });
  renderCheckout();
  document.getElementById("checkout").scrollIntoView({ behavior: "smooth", block: "nearest" });
}

function renderCheckout() {
  const box = document.getElementById("checkout");
  if (!PICKED) {
    box.hidden = true;
    return;
  }
  box.hidden = false;

  const period = PICKED.period === "once" ? "یک‌بار پرداخت" : PICKED.period === "month" ? "ماهانه" : "هر جلسه";

  box.innerHTML = `
    <div class="plan__top" style="margin-bottom:.9rem">
      <h3 class="plan__title">${esc(PICKED.title)}</h3>
      <div class="plan__price">${planPriceText(PICKED)}<small>${period}</small></div>
    </div>
    <div class="notice notice--info" style="margin-bottom:1rem">
      ${ICONS.info}
      <div>
        پس از تایید پرداخت، مربی برنامه‌ات را در همین صفحه می‌گذارد.
        برنامه‌های قبلی هم دست‌نخورده می‌مانند.
      </div>
    </div>
    ${
      STATE.zarinpal
        ? `<div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem">
             <button class="btn btn--ghost btn--small" type="button" id="mManual" aria-pressed="true">کارت‌به‌کارت</button>
             <button class="btn btn--ghost btn--small" type="button" id="mGateway" aria-pressed="false">پرداخت آنلاین</button>
           </div>`
        : ""
    }
    <div id="manualBox">
      <p style="margin:0 0 .6rem;font-size:.9rem">
        بعد از کارت‌به‌کارت، تصویر رسید را بفرست.
      </p>
      <label class="file-drop" id="receiptDrop" for="receiptInput">
        انتخاب تصویر رسید — یا فایل را همین‌جا رها کن
        <input type="file" id="receiptInput" accept="image/jpeg,image/png,image/webp" hidden />
      </label>
      <p class="field__hint" id="receiptName" style="margin-top:.5rem"></p>
      <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1rem">
        <button class="btn btn--accent" type="button" id="sendReceiptBtn" disabled>ثبت سفارش و ارسال رسید</button>
        <button class="btn btn--ghost" type="button" id="cancelCheckout">لغو</button>
      </div>
    </div>
    <div id="gatewayBox" hidden>
      <button class="btn btn--accent btn--block" type="button" id="gatewayBtn">پرداخت آنلاین با زرین‌پال</button>
      <button class="btn btn--ghost btn--block" type="button" id="cancelCheckout2" style="margin-top:.5rem">لغو</button>
    </div>
  `;

  // کارت‌به‌کارت
  const input = box.querySelector("#receiptInput");
  const drop = box.querySelector("#receiptDrop");
  const nameEl = box.querySelector("#receiptName");
  const sendBtn = box.querySelector("#sendReceiptBtn");

  function setFile(file) {
    if (!file) return;
    if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
      toast("فقط تصویر JPG/PNG/WebP پذیرفته می‌شود");
      return;
    }
    if (file.size > 8 * 1024 * 1024) {
      toast("حجم تصویر باید کمتر از ۸ مگابایت باشد");
      return;
    }
    sendBtn.dataset.file = "";
    input._file = file;
    nameEl.textContent = `انتخاب شد: ${file.name}`;
    sendBtn.disabled = false;
  }

  input.addEventListener("change", () => setFile(input.files[0]));
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
  drop.addEventListener("drop", (e) => setFile(e.dataTransfer.files[0]));

  sendBtn.addEventListener("click", async () => {
    if (!input._file) return;
    setBusy(sendBtn, true, "در حال ارسال…");
    try {
      const order = await api("/api/orders", {
        method: "POST",
        json: { planKey: PICKED.key, method: "manual" },
      });
      const form = new FormData();
      form.append("receipt", input._file, input._file.name);
      await api(`/api/orders/${order.orderId}/receipt`, { method: "POST", form });

      toast("رسید ثبت شد. پس از تایید مربی، برنامه‌ات اضافه می‌شود.");
      PICKED = null;
      box.hidden = true;
      document.querySelectorAll(".plan").forEach((el) => el.classList.remove("is-picked"));
      await loadData();
    } catch (err) {
      setBusy(sendBtn, false);
      toast(err.message);
    }
  });

  const cancel = () => {
    PICKED = null;
    box.hidden = true;
    document.querySelectorAll(".plan").forEach((el) => el.classList.remove("is-picked"));
  };
  box.querySelector("#cancelCheckout").addEventListener("click", cancel);

  // درگاه
  if (STATE.zarinpal) {
    const mManual = box.querySelector("#mManual");
    const mGateway = box.querySelector("#mGateway");
    const manualBox = box.querySelector("#manualBox");
    const gatewayBox = box.querySelector("#gatewayBox");

    function method(which) {
      const isGateway = which === "gateway";
      manualBox.hidden = isGateway;
      gatewayBox.hidden = !isGateway;
      mManual.setAttribute("aria-pressed", String(!isGateway));
      mGateway.setAttribute("aria-pressed", String(isGateway));
    }
    mManual.addEventListener("click", () => method("manual"));
    mGateway.addEventListener("click", () => method("gateway"));
    box.querySelector("#cancelCheckout2").addEventListener("click", cancel);

    box.querySelector("#gatewayBtn").addEventListener("click", async (e) => {
      setBusy(e.currentTarget, true, "در حال اتصال به درگاه…");
      try {
        const res = await api("/api/orders", {
          method: "POST",
          json: { planKey: PICKED.key, method: "zarinpal" },
        });
        location.href = res.redirect;
      } catch (err) {
        setBusy(e.currentTarget, false);
        toast(err.message);
      }
    });
  }
}

/* -------------------------------------------------------------- برنامه‌ها */

function renderPrograms() {
  const box = document.getElementById("programsBox");

  if (!STATE.programs.length) {
    box.innerHTML = `
      <div class="card">
        <div class="empty">
          <div class="empty__icon">${ICONS.box}</div>
          <b style="display:block;color:var(--ink);margin-bottom:.3rem">هنوز برنامه‌ای نداری</b>
          یکی از برنامه‌های پایین را انتخاب کن. بعد از تایید پرداخت،
          مربی برنامه‌ات را همین‌جا می‌گذارد.
        </div>
      </div>`;
    return;
  }

  box.innerHTML = STATE.programs
    .map((p, i) => {
      const isNew = i === 0;
      return `
      <article class="program" data-id="${p.id}">
        <button class="program__head" type="button" aria-expanded="false" aria-controls="prog-${p.id}">
          <span class="program__icon">${ICONS.dumbbell}</span>
          <span class="program__titles">
            <span class="program__title">${esc(p.title)}</span>
            <span class="program__meta">
              ${isNew ? `<span class="badge badge--new">جدیدترین</span>` : ""}
              <span>${faNum(p.day_count)} روز</span>
              ${p.file_count ? `<span>${faNum(p.file_count)} فایل</span>` : ""}
              <span>${esc(faDate(p.created_at))}</span>
            </span>
          </span>
          ${ICONS.chevron}
        </button>
        <div class="program__body" id="prog-${p.id}" hidden></div>
      </article>`;
    })
    .join("");

  // اول شنونده‌ها، بعد باز کردن خودکار — وگرنه کلیک اول بی‌اثر است
  box.querySelectorAll(".program__head").forEach((head) => {
    head.addEventListener("click", () => toggleProgram(head));
  });

  // جدیدترین برنامه به‌صورت پیش‌فرض باز است
  const first = box.querySelector(".program__head");
  if (first) first.click();
}

async function toggleProgram(head) {
  const card = head.closest(".program");
  const body = card.querySelector(".program__body");
  const open = head.getAttribute("aria-expanded") === "true";

  if (open) {
    head.setAttribute("aria-expanded", "false");
    body.hidden = true;
    return;
  }

  head.setAttribute("aria-expanded", "true");
  body.hidden = false;

  if (body.dataset.loaded) return;
  body.innerHTML = `<div class="loading-line" style="width:70%"></div>
                    <div class="loading-line" style="width:45%;margin-top:.5rem"></div>`;

  try {
    const data = await api(`/api/programs/${card.dataset.id}`);
    body.innerHTML = renderProgramBody(data.program);
    body.dataset.loaded = "1";

    const printBtn = body.querySelector("[data-print]");
    if (printBtn) printBtn.addEventListener("click", () => window.print());
  } catch (err) {
    body.innerHTML = `<div class="notice notice--err">${ICONS.warn}<div>${esc(err.message)}</div></div>`;
  }
}

function renderProgramBody(program) {
  const days = (program.days || [])
    .map((d) => {
      const rows = (d.items || [])
        .map(
          (it) => `
          <tr>
            <td>${esc(it.exercise)}${it.note ? `<span class="ex-note">${esc(it.note)}</span>` : ""}</td>
            <td data-label="ست">${esc(it.sets || "—")}</td>
            <td data-label="تکرار">${esc(it.reps || "—")}</td>
            <td data-label="استراحت">${esc(it.rest || "—")}</td>
          </tr>`
        )
        .join("");

      return `
      <div class="day">
        <h4 class="day__title">${esc(d.label)}</h4>
        ${d.note ? `<p class="day__note">${esc(d.note)}</p>` : ""}
        ${
          rows
            ? `<table class="ex-table">
                 <thead><tr><th>حرکت</th><th>ست</th><th>تکرار</th><th>استراحت</th></tr></thead>
                 <tbody>${rows}</tbody>
               </table>`
            : `<p class="field__hint">حرکتی برای این روز ثبت نشده.</p>`
        }
      </div>`;
    })
    .join("");

  const files = (program.files || [])
    .map(
      (f) => `
      <a class="btn btn--ghost btn--small" href="/api/files/${encodeURIComponent(f.stored_name)}">
        ${ICONS.download} ${esc(f.original_name)}
      </a>`
    )
    .join("");

  return `
    ${program.summary ? `<p style="margin:0 0 1rem;color:var(--muted);font-size:.88rem">${esc(program.summary)}</p>` : ""}
    ${days || `<p class="field__hint">جزئیاتی برای این برنامه ثبت نشده.</p>`}
    ${files ? `<div class="program__files">${files}</div>` : ""}
    <div style="margin-top:1.25rem">
      <button class="btn btn--ghost btn--small" type="button" data-print>${ICONS.print} چاپ برنامه</button>
    </div>
  `;
}

/* ---------------------------------------------------------------- خریدها */

function renderOrders() {
  const box = document.getElementById("ordersBox");

  if (!STATE.orders.length) {
    box.innerHTML = `<p class="field__hint" style="margin:0">هنوز خریدی ثبت نکرده‌ای.</p>`;
    return;
  }

  const STATUS = {
    pending: ["badge--pending", "در انتظار پرداخت"],
    awaiting_confirm: ["badge--waiting", "در انتظار تایید مربی"],
    paid: ["badge--paid", "پرداخت شده"],
    canceled: ["badge--canceled", "لغو شده"],
  };

  box.innerHTML = STATE.orders
    .map((o) => {
      const [cls, label] = STATUS[o.status] || ["badge--canceled", o.status];
      return `
      <div class="order-row">
        <div class="order-row__main">
          <div class="order-row__name">${esc(o.plan_title)}</div>
          <div class="order-row__sub">
            ${orderAmountText(o)} · ${esc(faDate(o.created_at))}
            · ${o.method === "zarinpal" ? "درگاه آنلاین" : "کارت‌به‌کارت"}
          </div>
        </div>
        <span class="badge ${cls}">${esc(label)}</span>
      </div>`;
    })
    .join("");
}

boot();
