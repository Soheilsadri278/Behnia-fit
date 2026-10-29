<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>ورود و ثبت‌نام | بهنیا فیت</title>
  <meta name="description" content="ورود به حساب کاربری بهنیا فیت برای دیدن برنامه‌های تمرینی و تغذیه." />
  <meta name="robots" content="noindex, follow" />
  <meta name="theme-color" content="#f7f4ee" />
  <link rel="icon" type="image/png" sizes="64x64" href="<?php echo esc_url( BF_ACCOUNTS_URI ); ?>/assets/img/favicon.png" />
  <link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url( BF_ACCOUNTS_URI ); ?>/assets/img/apple-touch-icon.png" />

  <link rel="preload" as="font" type="font/woff2" href="<?php echo esc_url( BF_ACCOUNTS_URI ); ?>/assets/fonts/vazirmatn-arabic-400.woff2" crossorigin />
  <link rel="preload" as="font" type="font/woff2" href="<?php echo esc_url( BF_ACCOUNTS_URI ); ?>/assets/fonts/estedad-arabic-800.woff2" crossorigin />

  <!-- استایل حیاتی: اگر فایل اصلی دیر برسد، چیدمان نمی‌شکند -->
  <style>
    *,*::before,*::after{box-sizing:border-box}
    body{margin:0;background:#f7f4ee;color:#14150f;
      font-family:"Vazirmatn","Segoe UI",Tahoma,sans-serif;font-size:1.0625rem;line-height:1.85}
    img{max-width:100%}svg{width:1em;height:1em;flex:none}
    .container{width:100%;max-width:1180px;margin-inline:auto;padding-inline:clamp(1.15rem,0.4rem+3vw,2.75rem)}
    .appbar{background:rgba(247,244,238,.92);border-bottom:1px solid #e3dbcd}
    .authwrap{max-width:27rem;margin-inline:auto}
  </style>
  <link rel="stylesheet" href="<?php echo esc_url( BF_ACCOUNTS_URI ); ?>/assets/css/style.css?v=11" />
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
      <div class="authwrap">
        <div class="apphead" style="text-align:center">
          <h1>حساب کاربری</h1>
          <p>وارد شو تا برنامه‌هایت را ببینی.</p>
        </div>

        <div class="card">
          <div class="tabs" role="tablist">
            <button class="tabs__btn" role="tab" id="tabLogin" aria-selected="true" aria-controls="panelLogin" type="button">ورود</button>
            <button class="tabs__btn" role="tab" id="tabRegister" aria-selected="false" aria-controls="panelRegister" type="button">ثبت‌نام</button>
          </div>

          <div id="panelLogin" role="tabpanel" aria-labelledby="tabLogin">
            <form id="loginForm" novalidate>
              <label class="field">
                <span class="field__label">شمارهٔ موبایل</span>
                <input class="input" id="loginPhone" type="tel" inputmode="numeric" autocomplete="username"
                       placeholder="۰۹۱۲۳۴۵۶۷۸۹" dir="ltr" />
                <span class="field__error" id="loginPhone-error"></span>
              </label>
              <label class="field">
                <span class="field__label">رمز عبور</span>
                <input class="input" id="loginPassword" type="password" autocomplete="current-password"
                       placeholder="••••••••" dir="ltr" />
                <span class="field__error" id="loginPassword-error"></span>
              </label>
              <div class="notice notice--err" id="loginError" style="display:none;margin-top:1rem"></div>
              <button class="btn btn--accent btn--block" type="submit" id="loginSubmit" style="margin-top:1.25rem">
                ورود به حساب
              </button>
            </form>
            <p class="field__hint" style="text-align:center;margin-top:1rem">
              رمزت را فراموش کردی؟ به
              <a href="https://wa.me/989361701801" target="_blank" rel="noopener">واتساپ</a>
              پیام بده تا بازنشانی شود.
            </p>
          </div>

          <div id="panelRegister" role="tabpanel" aria-labelledby="tabRegister" hidden>
            <form id="registerForm" novalidate>
              <label class="field">
                <span class="field__label">نام و نام خانوادگی</span>
                <input class="input" id="regName" type="text" autocomplete="name" placeholder="مثلاً علی رضایی" />
                <span class="field__error" id="regName-error"></span>
              </label>
              <label class="field">
                <span class="field__label">شمارهٔ موبایل</span>
                <input class="input" id="regPhone" type="tel" inputmode="numeric" autocomplete="username"
                       placeholder="۰۹۱۲۳۴۵۶۷۸۹" dir="ltr" />
                <span class="field__hint">همین شماره برای ورود استفاده می‌شود.</span>
                <span class="field__error" id="regPhone-error"></span>
              </label>
              <label class="field">
                <span class="field__label">رمز عبور</span>
                <input class="input" id="regPassword" type="password" autocomplete="new-password"
                       placeholder="حداقل ۸ کاراکتر" dir="ltr" />
                <span class="field__error" id="regPassword-error"></span>
              </label>
              <div class="notice notice--err" id="registerError" style="display:none;margin-top:1rem"></div>
              <button class="btn btn--accent btn--block" type="submit" id="registerSubmit" style="margin-top:1.25rem">
                ساخت حساب
              </button>
            </form>
          </div>
        </div>

        <p class="field__hint" style="text-align:center;margin-top:1.25rem">
          با ساخت حساب، <a href="<?php echo esc_url( bf_accounts_privacy_url() ); ?>">حریم خصوصی</a> را می‌پذیری.
        </p>
      </div>
    </div>
  </main>

  <script src="<?php echo esc_url( BF_ACCOUNTS_URI ); ?>/assets/js/app.js?v=1"></script>
  <script>
    (function () {
      const next = new URLSearchParams(location.search).get("next") || "";
      const safeNext = /^[a-z0-9-]+\.html(\?[^<>"\s]*)?$/i.test(next) ? next : "";

      // اگر از قبل وارد شده، مستقیم به حساب برو
      api("/api/me")
        .then((d) => {
          initAppbar(d.user);
          if (d.user) {
            location.replace(d.user.role === "coach" ? "admin.html" : safeNext || "account.html");
          }
        })
        .catch(() => initAppbar(null));

      /* ---------------------------- تب‌ها */
      const tabLogin = document.getElementById("tabLogin");
      const tabRegister = document.getElementById("tabRegister");
      const panelLogin = document.getElementById("panelLogin");
      const panelRegister = document.getElementById("panelRegister");

      function pick(which) {
        const isLogin = which === "login";
        tabLogin.setAttribute("aria-selected", String(isLogin));
        tabRegister.setAttribute("aria-selected", String(!isLogin));
        panelLogin.hidden = !isLogin;
        panelRegister.hidden = isLogin;
      }
      tabLogin.addEventListener("click", () => pick("login"));
      tabRegister.addEventListener("click", () => pick("register"));

      /* ---------------------------- ورود */
      const loginForm = document.getElementById("loginForm");
      const loginBtn = document.getElementById("loginSubmit");

      loginForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        const phone = document.getElementById("loginPhone");
        const pass = document.getElementById("loginPassword");
        const box = document.getElementById("loginError");
        box.style.display = "none";
        fieldError(phone, "");
        fieldError(pass, "");

        let bad = false;
        if (!phone.value.trim()) { fieldError(phone, "شمارهٔ موبایل را بنویس"); bad = true; }
        if (!pass.value) { fieldError(pass, "رمز عبور را بنویس"); bad = true; }
        if (bad) return;

        setBusy(loginBtn, true, "در حال ورود…");
        try {
          const res = await api("/api/auth/login", {
            method: "POST",
            json: { phone: phone.value, password: pass.value },
          });
          location.replace(safeNext || res.redirect || "account.html");
        } catch (err) {
          setBusy(loginBtn, false);
          box.textContent = err.message;
          box.style.display = "flex";
        }
      });

      /* ---------------------------- ثبت‌نام */
      const regForm = document.getElementById("registerForm");
      const regBtn = document.getElementById("registerSubmit");

      regForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        const name = document.getElementById("regName");
        const phone = document.getElementById("regPhone");
        const pass = document.getElementById("regPassword");
        const box = document.getElementById("registerError");
        box.style.display = "none";
        [name, phone, pass].forEach((f) => fieldError(f, ""));

        let bad = false;
        if (name.value.trim().length < 2) { fieldError(name, "نامت را کامل بنویس"); bad = true; }
        if (!phone.value.trim()) { fieldError(phone, "شمارهٔ موبایل را بنویس"); bad = true; }
        if (pass.value.length < 8) { fieldError(pass, "رمز باید حداقل ۸ کاراکتر باشد"); bad = true; }
        if (bad) return;

        setBusy(regBtn, true, "در حال ساخت حساب…");
        try {
          await api("/api/auth/register", {
            method: "POST",
            json: { phone: phone.value, name: name.value, password: pass.value },
          });
          location.replace(safeNext || "account.html");
        } catch (err) {
          setBusy(regBtn, false);
          box.textContent = err.message;
          box.style.display = "flex";
        }
      });
    })();
  </script>
</body>
</html>
