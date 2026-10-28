/**
 * شمارهٔ کارت را در مرحلهٔ پرداخت نشان می‌دهد.
 *
 * چرا جدا؟ فایل account.js عیناً همان نسخهٔ Node است و دست نمی‌خورد؛ آن
 * نسخه شمارهٔ کارت را در رابط کاربری نشان نمی‌داد و شاگرد باید از جای
 * دیگری می‌پرسید. این چند خط بیرون از آن فایل، همان جای خالی را پر می‌کند.
 */
(function () {
  var card = String(window.BF_CARD || "").trim();
  if (!card) return;

  function esc(value) {
    return String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function inject() {
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

  var host = document.getElementById("checkout");
  if (!host) return;

  // جعبهٔ پرداخت بعد از انتخاب پلن ساخته می‌شود، پس نگاهش می‌کنیم.
  new MutationObserver(inject).observe(host, { childList: true, subtree: true });
  inject();
})();
