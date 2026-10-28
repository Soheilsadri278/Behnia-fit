/**
 * شمارهٔ کارت را در مرحلهٔ پرداخت نشان می‌دهد.
 *
 * چرا جدا؟ فایل account.js دست نمی‌خورد تا هر دو نسخهٔ سایت (Node و وردپرس)
 * دقیقاً یک چیز را اجرا کنند. آن فایل شمارهٔ کارت را در رابط کاربری نشان
 * نمی‌داد و شاگرد باید از جای دیگری می‌پرسید؛ این چند خط همان جای خالی را پر
 * می‌کند و در هر دو نسخه یکسان کار می‌کند.
 *
 * منبع شماره: در وردپرس از window.BF_CARD (تنظیمات افزونه) و در نسخهٔ Node
 * از /api/plans که همان مقدار را برمی‌گرداند.
 */
(function () {
  var host = document.getElementById("checkout");
  if (!host) return;

  var card = String(window.BF_CARD || "").trim();
  var asked = false;

  function esc(value) {
    return String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function inject() {
    if (!card) return;

    var box = document.getElementById("manualBox");
    if (!box || box.getAttribute("data-bf-card") === "1") return;

    var note = document.createElement("p");
    note.className = "bf-card-note";
    note.style.cssText =
      "margin:0 0 .85rem;padding:.7rem .9rem;border-radius:.7rem;" +
      "background:#f3efe4;border:1px solid #e3dbcd;font-size:.9rem;line-height:1.9";
    note.innerHTML =
      "کارت‌به‌کارت به شمارهٔ " +
      '<b dir="ltr" style="letter-spacing:.04em">' +
      esc(card) +
      "</b><br />بعد از پرداخت، تصویر رسید را همین‌جا بفرست.";

    box.insertBefore(note, box.firstChild);
    box.setAttribute("data-bf-card", "1");
  }

  function ask() {
    if (card || asked) return;
    asked = true;

    fetch("/api/plans", { credentials: "same-origin" })
      .then(function (res) {
        return res.ok ? res.json() : null;
      })
      .then(function (data) {
        if (data && data.card) {
          card = String(data.card).trim();
          inject();
        }
      })
      .catch(function () {
        /* بی‌صدا رد می‌شویم؛ نبودن شماره نباید بقیهٔ صفحه را بشکند */
      });
  }

  // جعبهٔ پرداخت بعد از انتخاب پلن ساخته می‌شود، پس نگاهش می‌کنیم.
  new MutationObserver(function () {
    inject();
    ask();
  }).observe(host, { childList: true, subtree: true });

  inject();
  ask();
})();
