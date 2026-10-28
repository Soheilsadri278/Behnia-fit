<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>حساب من | بهنیا فیت</title>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="theme-color" content="#f7f4ee" />
  <link rel="icon" type="image/png" sizes="64x64" href="<?php echo esc_url( BF_ACCOUNTS_URI ); ?>/assets/img/favicon.png" />
  <link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url( BF_ACCOUNTS_URI ); ?>/assets/img/apple-touch-icon.png" />

  <link rel="preload" as="font" type="font/woff2" href="<?php echo esc_url( BF_ACCOUNTS_URI ); ?>/assets/fonts/vazirmatn-arabic-400.woff2" crossorigin />
  <link rel="preload" as="font" type="font/woff2" href="<?php echo esc_url( BF_ACCOUNTS_URI ); ?>/assets/fonts/estedad-arabic-800.woff2" crossorigin />

  <style>
    *,*::before,*::after{box-sizing:border-box}
    body{margin:0;background:#f7f4ee;color:#14150f;
      font-family:"Vazirmatn","Segoe UI",Tahoma,sans-serif;font-size:1.0625rem;line-height:1.85}
    img{max-width:100%}svg{width:1em;height:1em;flex:none}
    .container{width:100%;max-width:1180px;margin-inline:auto;padding-inline:clamp(1.15rem,0.4rem+3vw,2.75rem)}
    .appbar{background:rgba(247,244,238,.92);border-bottom:1px solid #e3dbcd}
  </style>
  <link rel="stylesheet" href="<?php echo esc_url( BF_ACCOUNTS_URI ); ?>/assets/css/style.css?v=10" />
  <link rel="stylesheet" href="<?php echo esc_url( BF_ACCOUNTS_URI ); ?>/assets/css/app.css?v=4" />
</head>
<body>
  <header class="appbar">
    <div class="container appbar__inner">
      <a class="appbar__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="بهنیا فیت — صفحهٔ اصلی">
        <img src="<?php echo esc_url( BF_ACCOUNTS_URI ); ?>/assets/img/logo-mark.png" alt="" width="90" height="52" />
        <b>بهنیا فیت</b>
      </a>
      <div class="appbar__actions" id="appbarActions"></div>
    </div>
  </header>

  <main class="appmain">
    <div class="container">
      <div class="apphead">
        <h1 id="greeting">حساب من</h1>
        <p>برنامه‌هایت این‌جاست — هر برنامهٔ جدید اضافه می‌شود و قبلی‌ها می‌مانند.</p>
      </div>

      <div id="payNotice" style="margin-bottom:1.5rem"></div>

      <!-- ===== برنامه‌های من ===== -->
      <section aria-labelledby="programsTitle" style="margin-bottom:2.5rem">
        <h2 id="programsTitle" class="apphead" style="font-size:1.1rem;font-family:var(--font-display);color:var(--brand);margin-bottom:.9rem">
          برنامه‌های من
        </h2>
        <div id="programsBox">
          <div class="card"><div class="loading-line" style="width:60%"></div><div class="loading-line" style="width:40%;margin-top:.6rem"></div></div>
        </div>
      </section>

      <!-- ===== سفارش‌ها ===== -->
      <section aria-labelledby="ordersTitle" style="margin-bottom:2.5rem">
        <h2 id="ordersTitle" class="apphead" style="font-size:1.1rem;font-family:var(--font-display);color:var(--brand);margin-bottom:.9rem">
          خریدهای من
        </h2>
        <div class="card" id="ordersBox"></div>
      </section>

      <!-- ===== خرید جدید ===== -->
      <section aria-labelledby="buyTitle">
        <h2 id="buyTitle" class="apphead" style="font-size:1.1rem;font-family:var(--font-display);color:var(--brand);margin-bottom:.9rem">
          خرید برنامهٔ جدید
        </h2>
        <div class="card">
          <div class="plans" id="plansBox"></div>

          <div id="checkout" hidden></div>
      </section>
    </div>
  </main>

  <script src="<?php echo esc_url( BF_ACCOUNTS_URI ); ?>/assets/js/app.js?v=1"></script>
  <script src="<?php echo esc_url( BF_ACCOUNTS_URI ); ?>/assets/js/account.js?v=1"></script>
  <script src="<?php echo esc_url( BF_ACCOUNTS_URI ); ?>/assets/js/card-note.js?v=1"></script>
</body>
</html>
