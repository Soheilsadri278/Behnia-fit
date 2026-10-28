<?php
/**
 * فوتر — ساختار عیناً از index.html گرفته شده است.
 *
 * @package behnia-fit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

  <footer class="footer">
    <div class="container">
      <div class="footer__top">
        <div>
          <a class="brand brand--stacked" href="#hero" aria-label="بهنیا فیت — صفحهٔ اصلی">
            <img class="brand__lockup" src="<?php echo esc_url( BF_THEME_URI ); ?>/assets/img/logo-full-light.png"
                 alt="بهنیا فیت — Strength · Function · Performance"
                 width="226" height="147" loading="lazy" />
          </a>
          <p class="footer__about">
            کارشناس ارشد فیزیولوژی کاربردی، مربی رسمی فدراسیون و قهرمان فیتنس.
            تمرین حضوری و کوچینگ آنلاین با برنامهٔ اختصاصی و پیگیری واقعی.
          </p>
          <div class="socials">
            <a href="https://www.instagram.com/behniafit/" target="_blank" rel="noopener" aria-label="اینستاگرام">
              <svg aria-hidden="true"><use href="#i-instagram"></use></svg>
            </a>
            <a href="https://wa.me/989361701801" target="_blank" rel="noopener" aria-label="واتساپ">
              <svg aria-hidden="true"><use href="#i-whatsapp"></use></svg>
            </a>
            <a href="https://t.me/+989361701801" target="_blank" rel="noopener" aria-label="تلگرام">
              <svg aria-hidden="true"><use href="#i-telegram"></use></svg>
            </a>
          </div>
        </div>

        <div>
          <p class="footer__title">صفحه‌ها</p>
          <nav class="footer__links" aria-label="ناوبری فوتر">
            <a href="#about">دربارهٔ من</a>
            <a href="#services">خدمات</a>
            <a href="#process">فرایند کار</a>
            <a href="#results">نتایج و نظرات</a>
            <a href="#faq">سؤالات متداول</a>
            <a href="account.html">حساب من</a>
          </nav>
        </div>

        <div>
          <p class="footer__title">خدمات</p>
          <nav class="footer__links" aria-label="خدمات">
            <a href="#services">تمرین شخصی حضوری</a>
            <a href="#services">کوچینگ آنلاین</a>
            <a href="#services">برنامهٔ تمرینی اختصاصی</a>
            <a href="#services">همراهی تغذیه</a>
            <a href="#pricing">قیمت‌ها</a>
          </nav>
        </div>

        <div>
          <p class="footer__title">شروع کنیم</p>
          <p class="footer__cta">
            جلسهٔ آشنایی ۱۵ دقیقه‌ای رایگان است. کافی است یک پیام بدهی.
          </p>
          <a class="btn btn--light" href="#contact">
            رزرو مشاوره
            <svg aria-hidden="true"><use href="#i-arrow"></use></svg>
          </a>
        </div>
      </div>

      <div class="footer__wordmark" aria-hidden="true">
        <img src="<?php echo esc_url( BF_THEME_URI ); ?>/assets/img/logo-mark-light.png" alt="" width="400" height="231" loading="lazy" />
      </div>

      <div class="footer__dedication">
        این وب‌سایت با احترام و قدردانی، برای مربی‌ای ساخته شده که یادمان داد
        قوی‌تر شدن یک تصمیم روزانه است.
      </div>

      <div class="footer__bottom">
        <span>© <span id="year">۱۴۰۴</span> بهنیا فیت · همهٔ حقوق محفوظ است.</span>
        <div class="footer__legal">
          <a href="login.html">ورود / ثبت‌نام</a>
          <a href="privacy.html">حریم خصوصی</a>
          <a href="#hero">بازگشت به بالا ↑</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- نوار ثابت موبایل -->
  <div class="mobile-bar" id="mobileBar">
    <a class="btn btn--accent" href="#contact">
      مشاورهٔ رایگان
      <svg aria-hidden="true"><use href="#i-arrow"></use></svg>
    </a>
    <a class="btn btn--ghost btn--icon" href="https://wa.me/989361701801" target="_blank" rel="noopener" aria-label="پیام در واتساپ">
      <svg aria-hidden="true"><use href="#i-whatsapp"></use></svg>
    </a>
  </div>

  <button class="to-top" id="toTop" aria-label="بازگشت به بالای صفحه">
    <svg aria-hidden="true"><use href="#i-up"></use></svg>
  </button>


  <?php wp_footer(); ?>
</body>
</html>
