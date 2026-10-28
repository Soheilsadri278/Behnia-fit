<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo esc_html( bf_doc_title() ); ?></title>
  <meta name="description" content="<?php echo esc_attr( bf_doc_description() ); ?>" />
  <meta name="author" content="Behnia Fit" />
  <meta name="theme-color" content="#f7f4ee" />
  <?php bf_robots_meta(); ?><link rel="canonical" href="<?php echo esc_url( bf_doc_canonical() ); ?>" />
  <link rel="icon" type="image/png" sizes="64x64" href="<?php echo esc_url( BF_THEME_URI ); ?>/assets/img/favicon.png" />
  <link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url( BF_THEME_URI ); ?>/assets/img/apple-touch-icon.png" />

  <!-- Open Graph / شبکه‌های اجتماعی -->
  <meta property="og:type" content="website" />
  <meta property="og:locale" content="fa_IR" />
  <meta property="og:site_name" content="بهنیا فیت" />
  <meta property="og:title" content="<?php echo esc_attr( bf_doc_og_title() ); ?>" />
  <meta property="og:description" content="<?php echo esc_attr( bf_doc_og_description() ); ?>" />
  <meta property="og:url" content="<?php echo esc_url( bf_doc_canonical() ); ?>" />
  <meta property="og:image" content="<?php echo esc_url( BF_THEME_URI . '/assets/img/og-image.png' ); ?>" />
  <meta property="og:image:type" content="image/png" />
  <meta property="og:image:alt" content="بهنیا فیت — مربی شخصی و کوچینگ آنلاین" />
  <meta property="og:image:width" content="1200" />
  <meta property="og:image:height" content="630" />
  <meta name="twitter:card" content="summary_large_image" />

  <link rel="preload" as="font" type="font/woff2" href="<?php echo esc_url( BF_THEME_URI ); ?>/assets/fonts/estedad-arabic-800.woff2" crossorigin />
  <link rel="preload" as="font" type="font/woff2" href="<?php echo esc_url( BF_THEME_URI ); ?>/assets/fonts/vazirmatn-arabic-400.woff2" crossorigin />
  <!-- ------------------------------------------------------------------
       استایل حیاتی (inline): اگر فایل اصلی دیر برسد، کش قدیمی باشد یا
       اصلاً بارگذاری نشود، چیدمان و هویت برند نمی‌شکند.
       عمداً قبل از <link> آمده تا استایل اصلی همیشه بر آن مقدم باشد.
       ------------------------------------------------------------------ -->
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    html { -webkit-text-size-adjust: 100%; }
    body {
      margin: 0; background: #f7f4ee; color: #14150f;
      font-family: "Vazirmatn", "Segoe UI", Tahoma, sans-serif;
      font-size: 1.0625rem; line-height: 1.85;
    }
    img { max-width: 100%; }
    /* بدون استایل، svg پیش‌فرض ۳۰۰×۱۵۰ رندر می‌شود؛ این جلوی آن را می‌گیرد */
    svg { width: 1em; height: 1em; flex: none; }
    a { color: inherit; }
    .container {
      width: 100%; max-width: 1180px; margin-inline: auto;
      padding-inline: clamp(1.15rem, 0.4rem + 3vw, 2.75rem);
    }
    .header {
      position: sticky; top: 0; z-index: 100;
      background: rgba(247, 244, 238, 0.94);
      border-bottom: 1px solid #e3dbcd;
    }
    .header__inner {
      display: flex; align-items: center; justify-content: space-between;
      gap: 1rem; min-height: 82px;
    }
    .brand {
      display: inline-flex; align-items: center; gap: 0.8rem;
      flex: none; text-decoration: none; color: inherit;
    }
    .brand__logo { height: 52px; width: auto; flex: none; }
    .brand__text { display: grid; line-height: 1.25; }
    .brand__name { font-weight: 800; font-size: 1.05rem; }
    .brand__sub { font-size: 0.66rem; color: #6c6d60; letter-spacing: 0.02em; }
    .nav { display: flex; align-items: center; gap: 0.35rem; }
    .nav__link {
      padding: 0.55rem 0.85rem; border-radius: 999px;
      text-decoration: none; color: #2c2e25; font-size: 0.9rem;
    }
    .header__actions { display: flex; align-items: center; gap: 0.6rem; flex: none; }
    .btn {
      display: inline-flex; align-items: center; justify-content: center;
      gap: 0.55rem; padding: 0.9rem 1.5rem; border-radius: 999px;
      border: 1px solid #14234b; background: #14234b; color: #f7f4ee;
      font-weight: 600; font-size: 0.9rem; line-height: 1.2;
      text-decoration: none; white-space: nowrap;
    }
    .btn--ghost { background: transparent; color: #14150f; border-color: #d3c8b6; }
    .btn--light { background: #f7f4ee; color: #14150f; border-color: #f7f4ee; }
    .burger { display: none; width: 44px; height: 44px; border-radius: 12px;
      border: 1px solid #d3c8b6; background: #fff; }
    .hero__title, h1, h2, h3 { line-height: 1.25; margin: 0 0 0.5em; }
    .authority__grid { display: grid; grid-template-columns: repeat(4, 1fr); }
    .authority__icon svg, .hero__fact svg { width: 21px; height: 21px; }
    @media (max-width: 1000px) {
      .nav { display: none; }
      .burger { display: block; }
      .header__cta { display: none; }
    }
    @media (max-width: 640px) {
      .brand__logo { height: 40px; }
      .authority__grid { grid-template-columns: 1fr; }
    }
  </style>

  <!-- خودترمیم: اگر استایل اعمال نشده باشد (کش قدیمی یا خطای شبکه)،
       یک‌بار با پارامتر تازه دوباره بارگذاری می‌کند. -->
  <script>
    (function () {
      var KEY = "bf_css_retry";
      window.addEventListener("load", function () {
        try {
          var bg = getComputedStyle(document.body).backgroundColor;
          var applied = bg && bg !== "rgba(0, 0, 0, 0)" && bg !== "rgb(255, 255, 255)" && bg !== "transparent";
          if (applied || sessionStorage.getItem(KEY)) return;
          sessionStorage.setItem(KEY, "1");
          var link = document.querySelector('link[rel="stylesheet"][href*="style.css"]');
          if (link) {
            link.href = link.href.split("?")[0] + "?r=" + Date.now();
          } else {
            location.reload();
          }
        } catch (e) {}
      });
    })();
  </script>
  <noscript>
    <style>
      [data-reveal] { opacity: 1 !important; transform: none !important; }
      .faq__a { height: auto !important; }
    </style>
  </noscript>

  <!-- داده‌های ساختاریافته برای گوگل (سئوی محلی) -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "HealthAndBeautyBusiness",
    "name": "بهنیا فیت",
    "description": "مربی شخصی و کوچینگ آنلاین: کارشناس ارشد فیزیولوژی کاربردی، مربی رسمی فدراسیون و قهرمان فیتنس با ۵ سال سابقهٔ مربیگری حرفه‌ای.",
    "founder": {
      "@type": "Person",
      "name": "بهنیا",
      "jobTitle": "مربی شخصی و کوچینگ آنلاین",
      "alumniOf": {
        "@type": "EducationalOrganization",
        "name": "کارشناسی ارشد فیزیولوژی کاربردی"
      },
      "hasCredential": [
        { "@type": "EducationalOccupationalCredential", "credentialCategory": "degree", "name": "کارشناسی ارشد فیزیولوژی کاربردی" },
        { "@type": "EducationalOccupationalCredential", "credentialCategory": "certification", "name": "مربی رسمی فدراسیون" },
        { "@type": "EducationalOccupationalCredential", "credentialCategory": "award", "name": "قهرمان فیتنس" }
      ]
    },
    "areaServed": ["کرج", "مهرشهر", "ایران"],
    "address": {
      "@type": "PostalAddress",
      "addressLocality": "کرج",
      "addressRegion": "البرز",
      "addressCountry": "IR"
    },
    "priceRange": "$$",
    "url": "<?php echo esc_url( home_url( '/' ) ); ?>",
    "sameAs": ["https://www.instagram.com/behniafit/"],
    "openingHours": "Sa-Th 09:00-17:00"
  }
  </script>
  <?php wp_head(); ?>
</head>
<?php bf_body_tag(); ?><?php wp_body_open(); ?>

  <!-- ===== آیکون‌ها ===== -->
  <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
    <symbol id="i-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></symbol>
    <symbol id="i-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></symbol>
    <symbol id="i-plus" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></symbol>
    <symbol id="i-up" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></symbol>
    <symbol id="i-user" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a7 7 0 0 1 7-7h2a7 7 0 0 1 7 7v1"/></symbol>
    <symbol id="i-target" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.4" fill="currentColor" stroke="none"/></symbol>
    <symbol id="i-clock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol>
    <symbol id="i-dumbbell" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 6.5v11M3.5 9v5M17.5 6.5v11M20.5 9v5M6.5 12h11"/></symbol>
    <symbol id="i-laptop" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="5" width="16" height="11" rx="2"/><path d="M2 20h20"/></symbol>
    <symbol id="i-plan" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V2.8M15 4V2.8M9 10h6M9 14h6M9 18h3"/></symbol>
    <symbol id="i-apple" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8c-1.2-2-3.2-2.6-4.6-1.6C5.6 7.8 5.2 11 6.4 14c1.1 2.9 3 5 4.4 5 .7 0 1.2-.3 1.2-.3s.5.3 1.2.3c1.4 0 3.3-2.1 4.4-5 1.2-3 .8-6.2-1-7.6C15.2 5.4 13.2 6 12 8Z"/><path d="M12 8V5c0-1.2.8-2.2 2-2.5"/></symbol>
    <symbol id="i-pin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.6"/></symbol>
    <symbol id="i-mail" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m3.5 7 8.5 6 8.5-6"/></symbol>
    <symbol id="i-phone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16.9v2.6a1.9 1.9 0 0 1-2.1 1.9 18.6 18.6 0 0 1-8.1-2.9 18.3 18.3 0 0 1-5.6-5.6A18.6 18.6 0 0 1 2.3 4.7 1.9 1.9 0 0 1 4.2 2.6h2.6a1.9 1.9 0 0 1 1.9 1.6c.1 1 .4 1.9.7 2.8a1.9 1.9 0 0 1-.4 2L7.8 10.3a15 15 0 0 0 5.6 5.6l1.3-1.3a1.9 1.9 0 0 1 2-.4c.9.3 1.8.6 2.8.7a1.9 1.9 0 0 1 1.6 1.9Z"/></symbol>
    <symbol id="i-whatsapp" viewBox="0 0 24 24" fill="currentColor"><path d="M17.5 14.4c-.3-.2-1.8-.9-2-1-.3-.1-.5-.2-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1a8 8 0 0 1-2.4-1.5 9 9 0 0 1-1.6-2c-.2-.3 0-.5.1-.6l.5-.5.3-.5c.1-.2 0-.4 0-.5l-1-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.4s1 2.8 1.2 3c.1.2 1.9 3 4.7 4.2 2.3 1 2.8.8 3.3.8.5-.1 1.6-.7 1.9-1.3.2-.6.2-1.2.2-1.3-.1-.2-.2-.2-.4-.3Z"/><path d="M12 2a9.9 9.9 0 0 0-8.5 15L2 22l5.2-1.4A9.9 9.9 0 1 0 12 2Zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8 8 0 1 1 12 20Z"/></symbol>
    <symbol id="i-telegram" viewBox="0 0 24 24" fill="currentColor"><path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71L12.6 16.3l-1.99 1.93c-.23.23-.42.42-.83.42z"/></symbol>
    <symbol id="i-instagram" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1" fill="currentColor" stroke="none"/></symbol>
    <symbol id="i-image" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2.5"/><circle cx="8.5" cy="9.5" r="1.6"/><path d="m4 17 4.5-4.2a2 2 0 0 1 2.7 0L16 17M14.5 15l1.6-1.5a2 2 0 0 1 2.7 0L21 15.8"/></symbol>
    <symbol id="i-grad" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M22 9 12 4 2 9l10 5 10-5Z"/><path d="M6 11.6V16c0 1.65 2.7 3 6 3s6-1.35 6-3v-4.4M22 9v5"/></symbol>
    <symbol id="i-medal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="15.5" r="4.7"/><path d="M8.7 11.2 6 3h4.2l1.8 4.2L13.8 3H18l-2.7 8.2"/><path d="m12 13.4.75 1.5 1.65.25-1.2 1.15.3 1.65-1.5-.8-1.5.8.3-1.65-1.2-1.15 1.65-.25.75-1.5Z"/></symbol>
    <symbol id="i-cert" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 3h11A1.5 1.5 0 0 1 19 4.5V15H5V4.5A1.5 1.5 0 0 1 6.5 3Z"/><path d="M8.5 7h7M8.5 10.3h4.5"/><circle cx="12" cy="17" r="3"/><path d="m10.1 19.4-.5 2.4 2.4-1.1 2.4 1.1-.5-2.4"/></symbol>
    <symbol id="i-shield" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-3.5 8-10V5.5L12 2.5 4 5.5V12c0 6.5 8 10 8 10Z"/><path d="m8.8 11.8 2.2 2.2 4.2-4.4"/></symbol>
    <symbol id="i-info" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 7.8v.2"/></symbol>
  </svg>

  <a class="skip-link" href="#main">رفتن به محتوای اصلی</a>

  <!-- ===== هدر ===== -->
  <header class="header" id="header">
    <div class="container header__inner">
      <a class="brand" href="#hero" aria-label="بهنیا فیت — صفحهٔ اصلی">
        <img class="brand__logo" src="<?php echo esc_url( BF_THEME_URI ); ?>/assets/img/logo-mark.png" alt=""
             width="90" height="52" fetchpriority="high" aria-hidden="true" />
        <span class="brand__text">
          <span class="brand__name">بهنیا فیت</span>
          <span class="brand__sub">STRENGTH · FUNCTION · PERFORMANCE</span>
        </span>
      </a>

      <nav class="nav" id="nav" aria-label="ناوبری اصلی">
        <a class="nav__link" href="#about">دربارهٔ من</a>
        <a class="nav__link" href="#services">خدمات</a>
        <a class="nav__link" href="#process">فرایند کار</a>
        <a class="nav__link" href="#results">نتایج</a>
        <a class="nav__link" href="#pricing">قیمت‌ها</a>
        <a class="nav__link" href="#faq">سؤالات</a>
        <a class="nav__link" href="account.html">حساب من</a>
      </nav>

      <div class="header__actions">
        <a class="btn btn--accent header__cta" href="#contact">
          رزرو مشاورهٔ رایگان
          <svg aria-hidden="true"><use href="#i-arrow"></use></svg>
        </a>
        <button class="burger" id="burger" aria-label="باز کردن منو" aria-expanded="false" aria-controls="mobileMenu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </header>

  <!-- منوی موبایل -->
  <div class="mobile-menu" id="mobileMenu" aria-hidden="true">
    <a class="mobile-menu__link" href="#about">دربارهٔ من <span>۰۱</span></a>
    <a class="mobile-menu__link" href="#services">خدمات <span>۰۲</span></a>
    <a class="mobile-menu__link" href="#process">فرایند کار <span>۰۳</span></a>
    <a class="mobile-menu__link" href="#results">نتایج <span>۰۴</span></a>
    <a class="mobile-menu__link" href="#pricing">قیمت‌ها <span>۰۵</span></a>
    <a class="mobile-menu__link" href="#faq">سؤالات متداول <span>۰۶</span></a>
    <a class="mobile-menu__link" href="account.html">حساب من</a>
    <a class="mobile-menu__link" href="#contact">تماس <span>۰۷</span></a>
    <a class="btn btn--accent btn--lg btn--full" href="#contact">
      رزرو مشاورهٔ رایگان
      <svg aria-hidden="true"><use href="#i-arrow"></use></svg>
    </a>
  </div>

