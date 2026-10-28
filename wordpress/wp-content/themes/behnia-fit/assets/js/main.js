/* ==========================================================================
   Behnia Fit — تعامل‌های سایت
   --------------------------------------------------------------------------
   همهٔ اطلاعات تماس در پایین همین فایل و در بخش CONFIG متمرکز شده است.
   برای تغییر شماره واتساپ، ایمیل یا اینستاگرام فقط همان بخش را ویرایش کنید.
   ========================================================================== */

(function () {
  "use strict";

  /* ---------------------------------------------------------------------
     CONFIG — تنظیمات تماس
     phone: فقط رقم‌ها، با کد کشور و بدون + و بدون ۰ اضافه
            ایران: 98 + شماره بدون صفر اول  → "989361701801"
     telegram: همان شماره، برای لینک t.me/+...
     instagram: نام کاربری بدون @
     --------------------------------------------------------------------- */
  var CONFIG = {
    phone: "989361701801",
    whatsapp: "989361701801",
    telegram: "989361701801",
    instagram: "behniafit"
  };

  var prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* ---------- کمکی‌ها ---------- */
  function $(sel, root) {
    return (root || document).querySelector(sel);
  }
  function $$(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }

  /* ---------- هدر چسبان ---------- */
  var header = $("#header");
  var toTop = $("#toTop");
  var mobileBar = $("#mobileBar");

  function onScroll() {
    var y = window.scrollY || document.documentElement.scrollTop;
    if (header) header.classList.toggle("is-stuck", y > 8);
    if (toTop) toTop.classList.toggle("is-visible", y > 600);
    if (mobileBar) mobileBar.classList.toggle("is-visible", y > 420);
  }
  window.addEventListener("scroll", onScroll, { passive: true });
  onScroll();

  if (toTop) {
    toTop.addEventListener("click", function () {
      window.scrollTo({ top: 0, behavior: prefersReducedMotion ? "auto" : "smooth" });
    });
  }

  /* ---------- منوی موبایل ---------- */
  var burger = $("#burger");
  var menu = $("#mobileMenu");

  function setMenu(open) {
    if (!burger || !menu) return;
    burger.setAttribute("aria-expanded", String(open));
    burger.setAttribute("aria-label", open ? "بستن منو" : "باز کردن منو");
    menu.classList.toggle("is-open", open);
    menu.setAttribute("aria-hidden", String(!open));
    document.body.style.overflow = open ? "hidden" : "";
  }

  if (burger && menu) {
    burger.addEventListener("click", function () {
      setMenu(burger.getAttribute("aria-expanded") !== "true");
    });

    $$("a", menu).forEach(function (link) {
      link.addEventListener("click", function () {
        setMenu(false);
      });
    });

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && burger.getAttribute("aria-expanded") === "true") {
        setMenu(false);
        burger.focus();
      }
    });

    window.addEventListener("resize", function () {
      if (window.innerWidth > 1000) setMenu(false);
    });
  }

  /* ---------- نوار متحرک ----------
     مسئله: انیمیشن ‎-50%‎ فقط وقتی بی‌درز است که دو نیمهٔ نوار دقیقاً یکسان و
     هر نیمه از عرض صفحه پهن‌تر باشند؛ وگرنه سمت راست نوار خالی می‌ماند.
     راه‌حل: یک گروه مرجع می‌گیریم، آیتم‌ها را تا رسیدن به عرض لازم تکرار
     می‌کنیم و بعد همان گروه را عیناً کپی می‌کنیم.
     ------------------------------------------------------------------- */
  var ticker = $(".ticker");
  var tickerTrack = $(".ticker__track");
  var tickerGroup = $(".ticker__group");
  var tickerBase = null;
  var tickerTimer = null;

  function buildTicker() {
    if (!ticker || !tickerTrack || !tickerGroup) return;

    // نسخهٔ اصلی آیتم‌ها را فقط یک‌بار نگه می‌داریم
    if (!tickerBase) {
      tickerBase = $$(".ticker__item", tickerGroup).map(function (el) {
        return el.cloneNode(true);
      });
    }
    if (!tickerBase.length) return;

    // پاک‌سازی گروه‌های قبلی
    var groups = $$(".ticker__group", tickerTrack);
    groups.forEach(function (g, i) {
      if (i > 0) g.parentNode.removeChild(g);
    });
    while (tickerGroup.firstChild) {
      tickerGroup.removeChild(tickerGroup.firstChild);
    }

    // عرضی که باید پوشش داده شود: دست‌کم عرض نوار × ۱٫۲ (حاشیهٔ اطمینان)
    var need = Math.max(ticker.clientWidth, window.innerWidth) * 1.2 + 160;
    var guard = 0;
    do {
      tickerBase.forEach(function (el) {
        tickerGroup.appendChild(el.cloneNode(true));
      });
      guard++;
    } while (tickerGroup.getBoundingClientRect().width < need && guard < 20);

    // گروه دوم، دقیقاً یکسان با اولی
    var twin = tickerGroup.cloneNode(true);
    twin.setAttribute("aria-hidden", "true");
    tickerTrack.appendChild(twin);

    // سرعت ثابت (~۷۰ پیکسل بر ثانیه) مستقل از عرض صفحه
    var groupWidth = tickerGroup.getBoundingClientRect().width;
    tickerTrack.style.animationDuration = Math.max(16, Math.round(groupWidth / 70)) + "s";
    ticker.classList.add("is-ready");
  }

  buildTicker();
  window.addEventListener("load", buildTicker);
  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(buildTicker).catch(function () {});
  }
  window.addEventListener("resize", function () {
    window.clearTimeout(tickerTimer);
    tickerTimer = window.setTimeout(buildTicker, 180);
  });

  /* ---------- هایلایت لینک فعال منو ---------- */
  var navLinks = $$(".nav__link");
  var sections = navLinks
    .map(function (link) {
      var id = link.getAttribute("href");
      // فقط لنگرهای همین صفحه؛ نشانی کامل یک selector معتبر نیست.
      if (!id || id.charAt(0) !== "#" || id.length < 2) return null;
      return document.querySelector(id);
    })
    .filter(Boolean);

  if ("IntersectionObserver" in window && sections.length) {
    var navObserver = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          var id = "#" + entry.target.id;
          navLinks.forEach(function (l) {
            l.classList.toggle("is-active", l.getAttribute("href") === id);
          });
        });
      },
      { rootMargin: "-45% 0px -50% 0px", threshold: 0 }
    );
    sections.forEach(function (sec) {
      navObserver.observe(sec);
    });
  }

  /* ---------- انیمیشن ورود ---------- */
  var revealItems = $$("[data-reveal]");

  if (prefersReducedMotion || !("IntersectionObserver" in window)) {
    revealItems.forEach(function (el) {
      el.classList.add("is-visible");
    });
  } else {
    var revealObserver = new IntersectionObserver(
      function (entries, observer) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            observer.unobserve(entry.target);
          }
        });
      },
      { rootMargin: "0px 0px -8% 0px", threshold: 0.08 }
    );
    revealItems.forEach(function (el) {
      revealObserver.observe(el);
    });
  }

  /* ---------- آکاردئون سؤالات ---------- */
  var faqButtons = $$(".faq__q");

  faqButtons.forEach(function (btn) {
    var panel = document.getElementById(btn.getAttribute("aria-controls"));
    if (!panel) return;

    btn.addEventListener("click", function () {
      var isOpen = btn.getAttribute("aria-expanded") === "true";

      // بستن بقیه (یکی باز بماند)
      faqButtons.forEach(function (other) {
        if (other === btn) return;
        var otherPanel = document.getElementById(other.getAttribute("aria-controls"));
        other.setAttribute("aria-expanded", "false");
        if (otherPanel) {
          otherPanel.style.height = "0px";
          other.closest(".faq__item").classList.remove("is-open");
        }
      });

      btn.setAttribute("aria-expanded", String(!isOpen));
      btn.closest(".faq__item").classList.toggle("is-open", !isOpen);
      panel.style.height = isOpen ? "0px" : panel.scrollHeight + "px";
    });
  });

  // اصلاح ارتفاع در تغییر اندازهٔ صفحه
  window.addEventListener("resize", function () {
    faqButtons.forEach(function (btn) {
      if (btn.getAttribute("aria-expanded") !== "true") return;
      var panel = document.getElementById(btn.getAttribute("aria-controls"));
      if (panel) panel.style.height = panel.scrollHeight + "px";
    });
  });

  /* ---------- فرم تماس ---------- */
  var form = $("#contactForm");
  var status = $("#formStatus");

  function showStatus(state, message) {
    if (!status) return;
    status.hidden = false;
    status.dataset.state = state;
    status.textContent = message;
  }

  function buildMessage() {
    if (!form) return "";
    var name = form.elements.name.value.trim();
    var contact = form.elements.contact.value.trim();
    var goal = form.elements.goal.value;
    var format = form.elements.format.value;
    var message = form.elements.message.value.trim();

    var lines = [
      "سلام بهنیا،",
      "از سایت پیام می‌دهم.",
      "",
      "نام: " + name,
      "راه تماس: " + contact,
      "هدف: " + goal,
      "نوع همراهی: " + format
    ];
    if (message) {
      lines.push("", "توضیح: " + message);
    }
    return lines.join("\n");
  }

  function validate() {
    if (!form) return false;
    var name = form.elements.name;
    var contact = form.elements.contact;
    var ok = true;

    [name, contact].forEach(function (field) {
      var invalid = !field.value.trim();
      field.setAttribute("aria-invalid", String(invalid));
      if (invalid) ok = false;
    });

    if (!ok) {
      showStatus("error", "لطفاً نام و راه تماس را کامل کن تا بتوانم جواب بدهم.");
      var firstInvalid = form.querySelector('[aria-invalid="true"]');
      if (firstInvalid) firstInvalid.focus();
    }
    return ok;
  }

  if (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      if (!validate()) return;

      var text = encodeURIComponent(buildMessage());
      var url = "https://wa.me/" + CONFIG.whatsapp + "?text=" + text;

      showStatus("ok", "پیام آماده شد؛ در پنجرهٔ جدید واتساپ باز می‌شود. دکمهٔ ارسال را بزن تا به دستم برسد.");
      window.open(url, "_blank", "noopener");
    });

    var tgBtn = $("#telegramBtn");
    if (tgBtn) {
      tgBtn.addEventListener("click", function () {
        if (!validate()) return;
        var text = encodeURIComponent(buildMessage());
        var url = "https://t.me/+" + CONFIG.telegram + "?text=" + text;
        showStatus("ok", "پیام آماده شد؛ در تلگرام باز می‌شود. کافی است دکمهٔ ارسال را بزنی.");
        window.open(url, "_blank", "noopener");
      });
    }
  }

  /* ---------- سال جاری در فوتر ---------- */
  var yearEl = $("#year");
  if (yearEl) {
    try {
      yearEl.textContent = new Date().toLocaleDateString("fa-IR-u-nu-arabext", { year: "numeric" });
    } catch (err) {
      yearEl.textContent = new Date().getFullYear();
    }
  }
})();
