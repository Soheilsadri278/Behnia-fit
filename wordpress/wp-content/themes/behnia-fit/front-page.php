<?php
/**
 * صفحهٔ اصلی — ساختار عیناً از index.html گرفته شده است.
 *
 * @package behnia-fit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

  <main id="main">
    <!-- ===== هیرو ===== -->
    <section class="hero" id="hero">
      <div class="container hero__inner">
        <div class="hero__content">
          <p class="hero__kicker" data-reveal>
            <span>کارشناس ارشد فیزیولوژی کاربردی · مربی رسمی فدراسیون</span>
            <span class="dot" aria-hidden="true"></span>
          </p>

          <h1 class="hero__title" data-reveal style="--delay:60ms">
            <span class="line">قوی‌تر شو،</span>
            <span class="line"><em>بدون</em> حدس و گمان.</span>
          </h1>

          <p class="hero__lead" data-reveal style="--delay:120ms">
            برنامه بر پایهٔ <strong style="color:var(--ink-soft)">فیزیولوژی کاربردی</strong> نوشته می‌شود،
            نه بر پایهٔ حدس: بار تمرین، شدت، ریکاوری و تغذیه با عدد تعیین می‌شوند.
            حضوری و آنلاین — با پیگیری واقعی و اصلاح فرم.
          </p>

          <div class="hero__actions" data-reveal style="--delay:180ms">
            <a class="btn btn--accent btn--lg" href="#contact">
              مشاورهٔ رایگان ۱۵ دقیقه‌ای
              <svg aria-hidden="true"><use href="#i-arrow"></use></svg>
            </a>
            <a class="btn btn--ghost btn--lg" href="#pricing">دیدن برنامه‌ها و قیمت‌ها</a>
          </div>

          <ul class="hero__facts" data-reveal style="--delay:240ms">
            <li class="hero__fact"><svg aria-hidden="true"><use href="#i-check"></use></svg> جلسهٔ آشنایی رایگان</li>
            <li class="hero__fact"><svg aria-hidden="true"><use href="#i-check"></use></svg> جلسات ۱:۱ اختصاصی</li>
            <li class="hero__fact"><svg aria-hidden="true"><use href="#i-check"></use></svg> بازبینی برنامه هر ۴ هفته</li>
          </ul>
        </div>

        <div class="hero__media" data-reveal="fade" style="--delay:120ms">
          <div class="hero__frame">
            <!-- عکس واقعی مربی. برای تغییر: فایل assets/img/trainer-hero.jpg را
                 با عکسی هم‌نسبت (۴:۴.۷) جایگزین کنید. -->
            <img src="<?php echo esc_url( BF_THEME_URI ); ?>/assets/img/trainer-hero.jpg"
                 alt="بهنیا، مربی بدنسازی، ایستاده در سالن تمرین"
                 width="900" height="1058" fetchpriority="high" />
          </div>
          <div class="hero__badge" aria-hidden="true">
            <div>
              <strong>۵</strong>
              <span>سال سابقهٔ حرفه‌ای</span>
            </div>
          </div>
          <div class="hero__card">
            <span class="avatar" aria-hidden="true">ب</span>
            <div>
              <div class="name">بهنیا</div>
              <div class="role">ارشد فیزیولوژی کاربردی</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== نوار متحرک ===== -->
    <div class="ticker" aria-hidden="true">
      <div class="ticker__track">
        <div class="ticker__group">
          <span class="ticker__item">تمرین مقاومتی</span>
          <span class="ticker__item">اصلاح فرم</span>
          <span class="ticker__item">برنامهٔ اختصاصی</span>
          <span class="ticker__item">تغذیهٔ ساده</span>
          <span class="ticker__item">پیگیری هفتگی</span>
          <span class="ticker__item">حضوری در کرج · مهرشهر</span>
          <span class="ticker__item">کوچینگ آنلاین</span>
          <span class="ticker__item">کارشناسی ارشد فیزیولوژی کاربردی</span>
          <span class="ticker__item">قهرمان فیتنس</span>
        </div>
        <!-- یک نسخهٔ ایستا هم می‌گذاریم تا بدون جاوااسکریپت هم حلقه بی‌پرش باشد.
             جاوااسکریپت این را حذف و بر اساس عرض واقعی صفحه بازسازی می‌کند. -->
        <div class="ticker__group" aria-hidden="true">
          <span class="ticker__item">تمرین مقاومتی</span>
          <span class="ticker__item">اصلاح فرم</span>
          <span class="ticker__item">برنامهٔ اختصاصی</span>
          <span class="ticker__item">تغذیهٔ ساده</span>
          <span class="ticker__item">پیگیری هفتگی</span>
          <span class="ticker__item">حضوری در کرج · مهرشهر</span>
          <span class="ticker__item">کوچینگ آنلاین</span>
          <span class="ticker__item">کارشناسی ارشد فیزیولوژی کاربردی</span>
          <span class="ticker__item">قهرمان فیتنس</span>
        </div>
      </div>
    </div>

    <!-- ===== نوار اعتبارنامه‌ها ===== -->
    <section class="authority" aria-label="اعتبارنامه‌های مربی">
      <div class="container authority__grid">
        <div class="authority__item">
          <span class="authority__icon" aria-hidden="true"><svg><use href="#i-grad"></use></svg></span>
          <span class="authority__text">
            <span class="authority__title">کارشناسی ارشد</span>
            <span class="authority__sub">فیزیولوژی کاربردی</span>
          </span>
        </div>
        <div class="authority__item">
          <span class="authority__icon" aria-hidden="true"><svg><use href="#i-cert"></use></svg></span>
          <span class="authority__text">
            <span class="authority__title">مربی رسمی فدراسیون</span>
            <span class="authority__sub">مدرک رسمی مربیگری</span>
          </span>
        </div>
        <div class="authority__item">
          <span class="authority__icon" aria-hidden="true"><svg><use href="#i-medal"></use></svg></span>
          <span class="authority__text">
            <span class="authority__title">قهرمان فیتنس</span>
            <span class="authority__sub">تجربهٔ واقعی مسابقه</span>
          </span>
        </div>
        <div class="authority__item">
          <span class="authority__icon" aria-hidden="true"><svg><use href="#i-clock"></use></svg></span>
          <span class="authority__text">
            <span class="authority__title">۵ سال سابقه</span>
            <span class="authority__sub">مربیگری حرفه‌ای</span>
          </span>
        </div>
      </div>
    </section>

    <!-- ===== برای چه کسی ===== -->
    <section class="section" id="audience">
      <div class="container">
        <div class="section-head">
          <p class="eyebrow"><span class="num">۰۱</span> برای چه کسی</p>
          <h2>این برنامه برای توست، اگر…</h2>
          <p class="lead">
            هر کسی از یک نقطه شروع می‌کند. تفاوت مسیرها همان چیزی است که برنامه را
            شکل می‌دهد — نه یک نسخهٔ آماده برای همه.
          </p>
        </div>

        <div class="audience__grid">
          <article class="audience__card" data-reveal>
            <span class="audience__index">۰۱</span>
            <span class="audience__icon" aria-hidden="true"><svg><use href="#i-user"></use></svg></span>
            <h3>تازه شروع کرده‌ام</h3>
            <p>
              از کجا شروع کنم؟ چند جلسه در هفته؟ کدام حرکت برای بدن من درست است؟
              در دو هفتهٔ اول مسیر روشن می‌شود و اولین عادت‌ها شکل می‌گیرند.
            </p>
          </article>

          <article class="audience__card" data-reveal style="--delay:90ms">
            <span class="audience__index">۰۲</span>
            <span class="audience__icon" aria-hidden="true"><svg><use href="#i-target"></use></svg></span>
            <h3>سال‌هاست در جا می‌زنم</h3>
            <p>
              بدن به تکرار عادت می‌کند. بار تمرین بازطراحی می‌شود، تکنیک دقیق‌تر
              می‌شود و رکوردها دوباره به حرکت درمی‌آیند.
            </p>
          </article>

          <article class="audience__card" data-reveal style="--delay:180ms">
            <span class="audience__index">۰۳</span>
            <span class="audience__icon" aria-hidden="true"><svg><use href="#i-clock"></use></svg></span>
            <h3>وقت و انرژی کم دارم</h3>
            <p>
              سه جلسهٔ ۴۵ دقیقه‌ایِ درست، از شش جلسهٔ نصفه‌ونیمه بهتر است.
              برنامه با تقویم واقعیِ تو نوشته می‌شود، نه برعکس.
            </p>
          </article>
        </div>
      </div>
    </section>

    <!-- ===== خدمات ===== -->
    <section class="section section--sand" id="services">
      <div class="container">
        <div class="section-head">
          <p class="eyebrow"><span class="num">۰۲</span> خدمات</p>
          <h2>چهار مسیر، یک هدف: پیشرفت قابل‌اندازه‌گیری</h2>
          <p class="lead">
            همهٔ مسیرها به یک برنامهٔ اختصاصی می‌رسند؛ تفاوت در میزان همراهی و شکل
            حضور است. اگر مطمئن نیستی کدام مناسب توست، در مشاورهٔ رایگان مشخص می‌کنیم.
          </p>
        </div>

        <div class="services__grid">
          <article class="service" data-reveal>
            <div class="service__top">
              <span class="service__icon" aria-hidden="true"><svg><use href="#i-dumbbell"></use></svg></span>
              <span class="service__no">۰۱ / حضوری</span>
            </div>
            <h3>تمرین شخصی حضوری</h3>
            <p>جلسه‌های ۱:۱ در استودیو یا باشگاه، با تمرکز روی فرم، شدت و پیشرفت هفته‌به‌هفته.</p>
            <ul class="service__list">
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> ارزیابی اولیه و سنجش ترکیب بدن</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> جلسهٔ ۶۰ دقیقه‌ای اختصاصی</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> برنامهٔ هفتگی روی گوشی</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> اصلاح فرم به‌صورت لحظه‌ای</li>
            </ul>
            <div class="service__foot">
              <div class="price-from">
                <span class="label">شروع از</span>
                <span class="value"><span data-bf-price="inperson">۳۰</span> <small>دلار / هر جلسه</small></span>
              </div>
              <a class="link-arrow" href="#contact">رزرو جلسه <svg aria-hidden="true"><use href="#i-arrow"></use></svg></a>
            </div>
          </article>

          <article class="service" data-reveal style="--delay:80ms">
            <div class="service__top">
              <span class="service__icon" aria-hidden="true"><svg><use href="#i-laptop"></use></svg></span>
              <span class="service__no">۰۲ / آنلاین</span>
            </div>
            <h3>کوچینگ آنلاین</h3>
            <p>همان کیفیت همراهی، بدون محدودیت شهر: برنامه در اپ، بازخورد ویدیویی حرکات و چت مستقیم.</p>
            <ul class="service__list">
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> برنامهٔ ماهانه با بازبینی هر ۴ هفته</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> بازخورد ویدیویی روی حرکات</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> چت مستقیم و پاسخ تا ۲۴ ساعت</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> تنظیم برنامه در سفر و بیماری</li>
            </ul>
            <div class="service__foot">
              <div class="price-from">
                <span class="label">شروع از</span>
                <span class="value"><span data-bf-price="online">۱۵</span> <small>دلار / ماهانه</small></span>
              </div>
              <a class="link-arrow" href="#contact">شروع کنیم <svg aria-hidden="true"><use href="#i-arrow"></use></svg></a>
            </div>
          </article>

          <article class="service" data-reveal style="--delay:160ms">
            <div class="service__top">
              <span class="service__icon" aria-hidden="true"><svg><use href="#i-plan"></use></svg></span>
              <span class="service__no">۰۳ / برنامه</span>
            </div>
            <h3>برنامهٔ تمرینی اختصاصی</h3>
            <p>برای کسانی که خودشان تمرین می‌کنند و فقط یک نقشهٔ دقیق و اجرایی می‌خواهند.</p>
            <ul class="service__list">
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> برنامهٔ ۴ تا ۱۲ هفته</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> ویدیوی آموزشی هر حرکت</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> راهنمای شدت، تکرار و استراحت</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> یک بازبینی رایگان در پایان دوره</li>
            </ul>
            <div class="service__foot">
              <div class="price-from">
                <span class="label">قیمت</span>
                <span class="value"><span data-bf-price="training">۸</span> <small>دلار · یک‌بار پرداخت</small></span>
              </div>
              <a class="link-arrow" href="#contact">درخواست برنامه <svg aria-hidden="true"><use href="#i-arrow"></use></svg></a>
            </div>
          </article>

          <article class="service" data-reveal style="--delay:240ms">
            <div class="service__top">
              <span class="service__icon" aria-hidden="true"><svg><use href="#i-apple"></use></svg></span>
              <span class="service__no">۰۴ / تغذیه</span>
            </div>
            <h3>همراهی تغذیه</h3>
            <p>بدون رژیم‌های گرسنگی و حذف گروه‌های غذایی؛ عادت‌هایی که در زندگی واقعی دوام می‌آورند.</p>
            <ul class="service__list">
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> بررسی عادت‌های فعلی غذا</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> راهنمای خرید و آماده‌سازی</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> تنظیم متناسب با سبک زندگی</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> بدون مکمل اجباری</li>
            </ul>
            <div class="service__foot">
              <div class="price-from">
                <span class="label">قیمت</span>
                <span class="value"><span data-bf-price="nutrition">۴</span> <small>دلار · یک‌بار پرداخت</small></span>
              </div>
              <a class="link-arrow" href="#contact">اطلاعات بیشتر <svg aria-hidden="true"><use href="#i-arrow"></use></svg></a>
            </div>
          </article>
        </div>
      </div>
    </section>

    <!-- ===== فرایند ===== -->
    <section class="section" id="process">
      <div class="container">
        <div class="section-head">
          <p class="eyebrow"><span class="num">۰۳</span> فرایند کار</p>
          <h2>از یک گفت‌وگو تا اولین هفتهٔ تمرین</h2>
        </div>

        <div class="process" data-reveal>
          <div class="process__grid">
            <div class="step step--active">
              <span class="step__num">۱</span>
              <h3>مشاورهٔ رایگان</h3>
              <p>۱۵ دقیقه گفت‌وگو دربارهٔ هدف، سابقه و زمانی که واقعاً در اختیار داری.</p>
            </div>
            <div class="step">
              <span class="step__num">۲</span>
              <h3>ارزیابی و سنجش</h3>
              <p>ترکیب بدن، دامنهٔ حرکتی و الگوی حرکتی پایه؛ نقطهٔ شروع مشخص می‌شود.</p>
            </div>
            <div class="step">
              <span class="step__num">۳</span>
              <h3>برنامهٔ اختصاصی</h3>
              <p>تمرین، تغذیه و خواب — نوشته‌شده برای هفتهٔ واقعیِ تو، نه یک الگوی عمومی.</p>
            </div>
            <div class="step">
              <span class="step__num">۴</span>
              <h3>اجرا و تنظیم</h3>
              <p>بازخورد هفتگی، اصلاح فرم و ثبت پیشرفت؛ برنامه هر ۴ هفته بازبینی می‌شود.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== نتایج و نظرات ===== -->
    <section class="section section--sand" id="results">
      <div class="container">
        <div class="results__head">
          <div class="section-head">
            <p class="eyebrow"><span class="num">۰۴</span> نتایج و نظرات</p>
            <h2>نتیجه، نه وعده</h2>
            <p class="lead">
              بهترین معرفِ یک برنامه، کسانی‌اند که آن را ادامه داده‌اند. این بخش
              جای عکس‌ها و حرف‌های واقعی شاگردان است.
            </p>
          </div>
          <a class="btn btn--ghost" href="#contact">
            دیدن نمونه‌ها در اینستاگرام
            <svg aria-hidden="true"><use href="#i-instagram"></use></svg>
          </a>
        </div>

        <div class="testimonials">
          <article class="quote" data-reveal>
            <span class="quote__mark" aria-hidden="true">»</span>
            <p class="quote__body is-placeholder">
              متن نظر واقعی شاگرد اینجا قرار می‌گیرد — همراه با نتیجهٔ مشخص و مدت
              همکاری (مثلاً: «۱۲ کیلو در ۵ ماه، دو جلسه در هفته»).
            </p>
            <div class="quote__foot">
              <span class="quote__avatar" aria-hidden="true"><svg><use href="#i-user"></use></svg></span>
              <div class="quote__meta">
                <span class="quote__name">[نام شاگرد]</span>
                <span class="quote__role">[نوع همراهی · مدت]</span>
              </div>
            </div>
          </article>

          <article class="quote" data-reveal style="--delay:90ms">
            <span class="quote__mark" aria-hidden="true">»</span>
            <p class="quote__body is-placeholder">
              یک نظر خوب می‌گوید «قبل» چه بود و «بعد» چه شد؛ نه فقط «عالی بود».
              اینجا جملهٔ واقعی شاگرد را بنویسید.
            </p>
            <div class="quote__foot">
              <span class="quote__avatar" aria-hidden="true"><svg><use href="#i-user"></use></svg></span>
              <div class="quote__meta">
                <span class="quote__name">[نام شاگرد]</span>
                <span class="quote__role">[نوع همراهی · مدت]</span>
              </div>
            </div>
          </article>

          <article class="quote" data-reveal style="--delay:180ms">
            <span class="quote__mark" aria-hidden="true">»</span>
            <p class="quote__body is-placeholder">
              اگر نظر ویدیویی داری، ویدیو تأثیر بیشتری از متن دارد. لینک یا فایل
              ویدیو را اینجا بگذارید.
            </p>
            <div class="quote__foot">
              <span class="quote__avatar" aria-hidden="true"><svg><use href="#i-user"></use></svg></span>
              <div class="quote__meta">
                <span class="quote__name">[نام شاگرد]</span>
                <span class="quote__role">[نوع همراهی · مدت]</span>
              </div>
            </div>
          </article>
        </div>

        <div class="transformations">
          <div class="frame" data-reveal>
            <span class="frame__tag">جای نمونه</span>
            <div class="frame__label">
              <svg aria-hidden="true"><use href="#i-image"></use></svg>
              <strong>عکس قبل / بعد ۱</strong>
              <span>با رضایت کتبی شاگرد</span>
            </div>
          </div>
          <div class="frame" data-reveal style="--delay:90ms">
            <span class="frame__tag">جای نمونه</span>
            <div class="frame__label">
              <svg aria-hidden="true"><use href="#i-image"></use></svg>
              <strong>عکس قبل / بعد ۲</strong>
              <span>همراه با مدت و جزئیات برنامه</span>
            </div>
          </div>
          <div class="frame" data-reveal style="--delay:180ms">
            <span class="frame__tag">جای نمونه</span>
            <div class="frame__label">
              <svg aria-hidden="true"><use href="#i-image"></use></svg>
              <strong>عکس قبل / بعد ۳</strong>
              <span>بدون ویرایش؛ فقط اصلاح نور و رنگ</span>
            </div>
          </div>
        </div>

        <p class="pricing__note" data-reveal>
          <svg aria-hidden="true"><use href="#i-info"></use></svg>
          <span>
            این بخش فعلاً نمونه است. قبل از انتشار، آن را با نظرات و تصاویر واقعی
            شاگردان — همراه با اجازهٔ کتبی آن‌ها — جایگزین کنید. ساختن نظر یا نتیجهٔ
            ساختگی، در آلمان تخلف تجاری محسوب می‌شود.
          </span>
        </p>
      </div>
    </section>

    <!-- ===== درباره ===== -->
    <section class="section about" id="about">
      <div class="container">
        <div class="about__grid">
          <div class="about__media" data-reveal="fade">
            <div class="about__frame">
              <!-- همان عکس، با برش نزدیک‌تر. برای تغییر: assets/img/trainer-portrait.jpg -->
              <img src="<?php echo esc_url( BF_THEME_URI ); ?>/assets/img/trainer-portrait.jpg"
                   alt="پرترهٔ بهنیا، مربی بدنسازی، در سالن تمرین"
                   width="800" height="1001" loading="lazy" />
            </div>
            <p class="about__caption">
              <svg aria-hidden="true"><use href="#i-pin"></use></svg>
              محل تمرین · کرج، مهرشهر
            </p>
          </div>

          <div class="about__body">
            <p class="eyebrow"><span class="num">۰۵</span> دربارهٔ من</p>
            <h2>برنامه از فیزیولوژی بدن تو می‌آید، نه از یک الگوی آماده.</h2>

            <p>
              من <strong>بهنیا</strong> هستم؛ <strong>کارشناس ارشد فیزیولوژی کاربردی</strong>،
              <strong>مربی رسمی فدراسیون</strong> و <strong>قهرمان فیتنس</strong>، با
              <strong>۵ سال سابقهٔ مربیگری حرفه‌ای</strong>. تخصص من دقیقاً همان نقطه‌ای
              است که علم بدن به یک برنامهٔ تمرینِ اجراشدنی تبدیل می‌شود.
            </p>
            <p>
              در برنامه‌نویسی، بار تمرین، حجم، شدت، فاصلهٔ استراحت، ریکاوری و تغذیه
              با منطق و عدد تعیین می‌شوند — نه با حس و حدس. اگر هفتهٔ سختی داشته باشی
              یا خوابت کم شود، همان عددها تنظیم می‌شوند. این تفاوتِ یک برنامهٔ
              فیزیولوژی‌محور با یک فهرست حرکتِ کپی‌شده است.
            </p>
            <p>
              مسیر مسابقه را خودم رفته‌ام و روی سکو ایستاده‌ام؛ می‌دانم آماده‌سازی چه
              فشاری به بدن و ذهن می‌آورد. همین تجربه باعث شده با شاگردهایم دقیق‌تر و
              محافظه‌کارتر باشم: اول بدن را می‌سازیم، بعد رکورد را. هیچ جلسه‌ای بدون
              هدف نیست و هیچ هفته‌ای بدون ثبت پیشرفت نمی‌گذرد.
            </p>

            <div class="about__signature">
              <span class="avatar" aria-hidden="true">ب</span>
              <div>
                <div class="sig-name">بهنیا</div>
                <div class="sig-role">ارشد فیزیولوژی کاربردی · مربی رسمی فدراسیون · قهرمان فیتنس</div>
              </div>
            </div>

            <div class="credentials">
              <div class="credential">
                <svg aria-hidden="true"><use href="#i-grad"></use></svg>
                <div>برنامه‌نویسی بر پایهٔ فیزیولوژی کاربردی<small>بار تمرین، شدت و ریکاوری با عدد و منطق</small></div>
              </div>
              <div class="credential">
                <svg aria-hidden="true"><use href="#i-medal"></use></svg>
                <div>آماده‌سازی مسابقه و فیتنس<small>تجربهٔ خودم روی سکو، از تمرین تا پیک</small></div>
              </div>
              <div class="credential">
                <svg aria-hidden="true"><use href="#i-target"></use></svg>
                <div>ترکیب بدن و کاهش چربی<small>بدون رژیم‌های افراطی و حذف گروه غذایی</small></div>
              </div>
              <div class="credential">
                <svg aria-hidden="true"><use href="#i-shield"></use></svg>
                <div>اصلاح فرم و پیشگیری از آسیب<small>حرکت درست، قبل از وزنهٔ سنگین</small></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== قیمت‌ها ===== -->
    <section class="section section--sand" id="pricing">
      <div class="container">
        <div class="section-head section-head--center">
          <p class="eyebrow eyebrow--center"><span class="num">۰۶</span> برنامه‌ها و قیمت</p>
          <h2>شفاف، بدون هزینهٔ پنهان</h2>
          <p class="lead" style="margin-inline:auto">
            یک قیمت روشن، هم برای تو و هم برای من بهتر است. اگر بودجه یا شرایط
            خاصی داری، در مشاوره رایگان درباره‌اش حرف می‌زنیم.
          </p>
        </div>

        <div class="pricing__grid">
          <article class="plan" data-reveal>
            <h3 class="plan__name">برنامهٔ تغذیه</h3>
            <p class="plan__desc">یک نقشهٔ غذایی ساده و شخصی، بدون گرسنگی و بدون حذف گروه غذایی.</p>
            <div class="plan__price">
              <span class="amount" data-bf-price="nutrition">۴</span>
              <span class="unit">دلار · یک‌بار پرداخت</span>
            </div>
            <ul class="plan__list">
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> برنامهٔ غذایی شخصی‌سازی‌شده</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> راهنمای خرید و آماده‌سازی</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> تنظیم متناسب با سبک زندگی</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> بدون مکمل اجباری</li>
              <li class="muted"><svg aria-hidden="true"><use href="#i-check"></use></svg> بدون پیگیری ماهانه</li>
            </ul>
            <a class="btn btn--ghost btn--full" href="account.html?plan=nutrition" data-bf-price-label="nutrition">خرید برنامهٔ تغذیه · ۴ دلار</a>
          </article>

          <article class="plan plan--featured" data-reveal style="--delay:80ms">
            <span class="badge">پیشنهاد بیشتر شاگردان</span>
            <h3 class="plan__name">برنامهٔ تمرینی</h3>
            <p class="plan__desc">برنامهٔ کامل تمرین، نوشته‌شده برای بدن و تقویم خودِ تو.</p>
            <div class="plan__price">
              <span class="amount" data-bf-price="training">۸</span>
              <span class="unit">دلار · یک‌بار پرداخت</span>
            </div>
            <ul class="plan__list">
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> برنامهٔ ۴ هفته‌ای اختصاصی</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> ویدیوی آموزشی هر حرکت</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> راهنمای شدت، تکرار و استراحت</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> یک بازبینی رایگان در پایان دوره</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> مناسب شروع از صفر</li>
            </ul>
            <a class="btn btn--light btn--full" href="account.html?plan=training" data-bf-price-label="training">
              خرید برنامهٔ تمرینی · ۸ دلار
              <svg aria-hidden="true"><use href="#i-arrow"></use></svg>
            </a>
          </article>

          <article class="plan" data-reveal style="--delay:160ms">
            <h3 class="plan__name">کوچینگ آنلاین</h3>
            <p class="plan__desc">همراهی ماهانه با بازخورد و چت مستقیم؛ برای تغییر پایدار.</p>
            <div class="plan__price">
              <span class="amount" data-bf-price="online">۱۵</span>
              <span class="unit">دلار · ماهانه</span>
            </div>
            <ul class="plan__list">
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> همهٔ موارد برنامهٔ تمرینی</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> بازبینی برنامه هر ۴ هفته</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> بازخورد ویدیویی حرکات</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> چت مستقیم با مربی</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> همراهی تغذیه</li>
            </ul>
            <a class="btn btn--ghost btn--full" href="account.html?plan=online" data-bf-price-label="online">شروع کوچینگ آنلاین · ۱۵ دلار</a>
          </article>

          <article class="plan" data-reveal style="--delay:240ms">
            <h3 class="plan__name">حضوری ۱:۱</h3>
            <p class="plan__desc">جلسهٔ اختصاصی با تمرکز روی فرم و شدت، برای بالاترین سرعت پیشرفت.</p>
            <div class="plan__price">
              <span class="amount" data-bf-price="inperson">۳۰</span>
              <span class="unit">دلار · هر جلسه</span>
            </div>
            <ul class="plan__list">
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> جلسهٔ ۶۰ دقیقه‌ای اختصاصی</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> ارزیابی و سنجش بدنی</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> اصلاح فرم به‌صورت لحظه‌ای</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> برنامهٔ هفتگی روی گوشی</li>
              <li><svg aria-hidden="true"><use href="#i-check"></use></svg> جابه‌جایی جلسه تا ۲۴ ساعت قبل</li>
            </ul>
            <a class="btn btn--ghost btn--full" href="account.html?plan=inperson" data-bf-price-label="inperson">رزرو جلسهٔ حضوری · ۳۰ دلار</a>
          </article>
        </div>

        <p class="pricing__note" data-reveal>
          <svg aria-hidden="true"><use href="#i-info"></use></svg>
          <span>
            همهٔ قیمت‌ها به <strong style="color:var(--ink-soft);font-weight:600">دلار آمریکا</strong> است.
            برنامهٔ تغذیه و برنامهٔ تمرینی یک‌بار خریداری می‌شوند و هزینهٔ تکرار ندارند.
            جابه‌جایی جلسهٔ حضوری تا ۲۴ ساعت قبل بدون هزینه انجام می‌شود. برای پکیج
            ترکیبی تمرین + تغذیه یا شرایط خاص، پیام بده.
          </span>
        </p>
      </div>
    </section>

    <!-- ===== سؤالات متداول ===== -->
    <section class="section" id="faq">
      <div class="container faq__grid">
        <div>
          <p class="eyebrow"><span class="num">۰۷</span> سؤالات متداول</p>
          <h2 style="font-size:var(--fs-h2);margin-block:1rem 1rem">
            چیزهایی که بیشتر پرسیده می‌شود
          </h2>
          <p class="lead">
            اگر جواب سؤالت را پیدا نکردی، همین را در فرم تماس بنویس؛ در همان
            مشاورهٔ رایگان جوابش را می‌گیری.
          </p>
          <a class="link-arrow" href="#contact" style="margin-top:1.25rem">
            پرسیدن سؤال
            <svg aria-hidden="true"><use href="#i-arrow"></use></svg>
          </a>
        </div>

        <div class="faq__list" id="faqList">
          <div class="faq__item">
            <button class="faq__q" aria-expanded="false" aria-controls="faq-a1" id="faq-q1">
              برای شروع باید آمادگی بدنی داشته باشم؟
              <span class="faq__icon" aria-hidden="true"><svg><use href="#i-plus"></use></svg></span>
            </button>
            <div class="faq__a" id="faq-a1" role="region" aria-labelledby="faq-q1">
              <div class="faq__a-inner">
                نه. نقطهٔ شروع هر کسی متفاوت است. اگر سال‌ها تمرین نکرده‌ای، از
                الگوهای حرکتی پایه و شدت کنترل‌شده شروع می‌کنیم. اگر محدودیت یا
                آسیب قبلی داری، برنامه با هماهنگی پزشک یا فیزیوتراپیست تنظیم می‌شود.
              </div>
            </div>
          </div>

          <div class="faq__item">
            <button class="faq__q" aria-expanded="false" aria-controls="faq-a2" id="faq-q2">
              هفته‌ای چند جلسه لازم است؟
              <span class="faq__icon" aria-hidden="true"><svg><use href="#i-plus"></use></svg></span>
            </button>
            <div class="faq__a" id="faq-a2" role="region" aria-labelledby="faq-q2">
              <div class="faq__a-inner">
                برای بیشتر آدم‌ها ۲ تا ۳ جلسهٔ باکیفیت در هفته کافی است. کیفیت اجرا
                و ریکاوری، مهم‌تر از تعداد جلسه‌هاست. اگر وقت کمی داری، همان ۲ جلسه
                را طوری می‌چینیم که بیشترین نتیجه را بدهد.
              </div>
            </div>
          </div>

          <div class="faq__item">
            <button class="faq__q" aria-expanded="false" aria-controls="faq-a3" id="faq-q3">
              کوچینگ آنلاین چطور کار می‌کند؟
              <span class="faq__icon" aria-hidden="true"><svg><use href="#i-plus"></use></svg></span>
            </button>
            <div class="faq__a" id="faq-a3" role="region" aria-labelledby="faq-q3">
              <div class="faq__a-inner">
                برنامهٔ تمرین در اپ اختصاصی روی گوشی‌ات می‌آید؛ هر حرکت ویدیوی
                آموزشی دارد. تمرین‌هایت را ثبت می‌کنی، ویدیوی اجرا می‌فرستی و
                بازخورد می‌گیری. هر ۴ هفته برنامه بازبینی و بازنویسی می‌شود و در
                طول هفته در چت مستقیم در دسترس هستم.
              </div>
            </div>
          </div>

          <div class="faq__item">
            <button class="faq__q" aria-expanded="false" aria-controls="faq-a4" id="faq-q4">
              چه مدت طول می‌کشد نتیجه ببینم؟
              <span class="faq__icon" aria-hidden="true"><svg><use href="#i-plus"></use></svg></span>
            </button>
            <div class="faq__a" id="faq-a4" role="region" aria-labelledby="faq-q4">
              <div class="faq__a-inner">
                در ۲ تا ۳ هفتهٔ اول معمولاً انرژی، خواب و کیفیت حرکت بهتر می‌شود.
                تغییر محسوس در قدرت و ترکیب بدن از هفتهٔ ۶ تا ۸ خودش را نشان
                می‌دهد. مهم‌تر از عدد ترازو، روندی است که بتوانی نگهش داری.
              </div>
            </div>
          </div>

          <div class="faq__item">
            <button class="faq__q" aria-expanded="false" aria-controls="faq-a5" id="faq-q5">
              اگر سفر بروم یا مریض شوم چه می‌شود؟
              <span class="faq__icon" aria-hidden="true"><svg><use href="#i-plus"></use></svg></span>
            </button>
            <div class="faq__a" id="faq-a5" role="region" aria-labelledby="faq-q5">
              <div class="faq__a-inner">
                برنامه بازنویسی می‌شود. برای سفر یک نسخهٔ کوتاه با تجهیزات حداقلی
                می‌گیری و در دورهٔ بیماری روی ریکاوری تمرکز می‌کنیم؛ جلسات حضوری هم
                تا ۲۴ ساعت قبل قابل جابه‌جایی است.
              </div>
            </div>
          </div>

          <div class="faq__item">
            <button class="faq__q" aria-expanded="false" aria-controls="faq-a6" id="faq-q6">
              برنامهٔ غذایی هم شامل می‌شود؟
              <span class="faq__icon" aria-hidden="true"><svg><use href="#i-plus"></use></svg></span>
            </button>
            <div class="faq__a" id="faq-a6" role="region" aria-labelledby="faq-q6">
              <div class="faq__a-inner">
                در پلن‌های کوچینگ آنلاین و حضوری، راهنمای تغذیه بخشی از کار است:
                اصول، اندازهٔ وعده‌ها و عادت‌های عملی — نه یک رژیم سخت و کپی‌شده.
                در صورت نیاز به رژیم درمانی، به متخصص تغذیه ارجاع داده می‌شود.
              </div>
            </div>
          </div>

          <div class="faq__item">
            <button class="faq__q" aria-expanded="false" aria-controls="faq-a8" id="faq-q8">
              مدرک و سابقهٔ مربی چیست؟
              <span class="faq__icon" aria-hidden="true"><svg><use href="#i-plus"></use></svg></span>
            </button>
            <div class="faq__a" id="faq-a8" role="region" aria-labelledby="faq-q8">
              <div class="faq__a-inner">
                کارشناسی ارشد فیزیولوژی کاربردی، مربی رسمی فدراسیون و قهرمان فیتنس،
                با ۵ سال سابقهٔ مربیگری حرفه‌ای. یعنی برنامه‌ای که می‌گیری هم پشتوانهٔ
                علمی دارد، هم پشتش تجربهٔ واقعی تمرین و مسابقه است — نه فقط یک مدرک
                روی دیوار.
              </div>
            </div>
          </div>

          <div class="faq__item">
            <button class="faq__q" aria-expanded="false" aria-controls="faq-a7" id="faq-q7">
              قرارداد بلندمدت لازم است؟
              <span class="faq__icon" aria-hidden="true"><svg><use href="#i-plus"></use></svg></span>
            </button>
            <div class="faq__a" id="faq-a7" role="region" aria-labelledby="faq-q7">
              <div class="faq__a-inner">
                نه. کوچینگ ماهانه است و می‌توانی ادامه ندهی. با این حال تجربه نشان
                داده تغییر واقعی معمولاً حداقل ۱۲ هفته زمان می‌برد؛ پس بهتر است با
                همین انتظار شروع کنیم.
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== تماس ===== -->
    <section class="section section--sand contact" id="contact">
      <div class="container contact__grid">
        <div>
          <p class="eyebrow"><span class="num">۰۸</span> تماس</p>
          <h2 style="margin-top:1rem">اولین قدم، یک گفت‌وگوی ۱۵ دقیقه‌ای است.</h2>
          <p class="contact__lead">
            فرم را پر کن؛ پیامت آماده می‌شود و در واتساپ یا تلگرام باز می‌شود.
            اگر ترجیح می‌دهی، از هر راه دیگری هم که راحت‌تری پیام بده.
          </p>

          <div class="contact__list">
            <a class="contact__row" href="https://wa.me/989361701801" target="_blank" rel="noopener">
              <span class="ci" aria-hidden="true"><svg><use href="#i-whatsapp"></use></svg></span>
              <span>
                <span class="label">واتساپ (سریع‌ترین راه)</span>
                <span class="value" style="display:block">+98 936 170 1801</span>
              </span>
            </a>
            <a class="contact__row" href="https://t.me/+989361701801" target="_blank" rel="noopener">
              <span class="ci" aria-hidden="true"><svg><use href="#i-telegram"></use></svg></span>
              <span>
                <span class="label">تلگرام</span>
                <span class="value" style="display:block">+98 936 170 1801</span>
              </span>
            </a>
            <a class="contact__row" href="tel:+989361701801">
              <span class="ci" aria-hidden="true"><svg><use href="#i-phone"></use></svg></span>
              <span>
                <span class="label">تماس تلفنی</span>
                <span class="value" style="display:block">+98 936 170 1801</span>
              </span>
            </a>
            <a class="contact__row" href="https://www.instagram.com/behniafit/" target="_blank" rel="noopener">
              <span class="ci" aria-hidden="true"><svg><use href="#i-instagram"></use></svg></span>
              <span>
                <span class="label">اینستاگرام</span>
                <span class="value" style="display:block" dir="ltr">@behniafit</span>
              </span>
            </a>
            <a class="contact__row" href="https://maps.google.com/?q=Mehrshahr,+Karaj,+Iran" target="_blank" rel="noopener">
              <span class="ci" aria-hidden="true"><svg><use href="#i-pin"></use></svg></span>
              <span>
                <span class="label">محل تمرین</span>
                <span class="value is-rtl" style="display:block">کرج، مهرشهر · آدرس دقیق پس از هماهنگی</span>
              </span>
            </a>
          </div>

          <div class="hours">
            <p class="hours__title"><svg aria-hidden="true"><use href="#i-clock"></use></svg> ساعات پاسخ‌گویی</p>
            <ul>
              <li><span>شنبه تا پنجشنبه</span><span>۰۹:۰۰ – ۱۷:۰۰</span></li>
              <li><span>جمعه</span><span>تعطیل</span></li>
            </ul>
          </div>
        </div>

        <div class="form-card" data-reveal="fade">
          <h3 class="form-card__title">درخواست مشاورهٔ رایگان</h3>
          <p class="form-card__sub">
            فقط چهار فیلد. پیام در واتساپ یا تلگرام خودت آماده می‌شود و با
            دکمهٔ ارسال، مستقیم به دست بهنیا می‌رسد.
          </p>

          <form id="contactForm" novalidate>
            <div class="form-grid">
              <div class="field">
                <label for="f-name">نام <span class="req" aria-hidden="true">*</span></label>
                <input id="f-name" name="name" type="text" autocomplete="name" placeholder="نام و نام خانوادگی" required />
              </div>
              <div class="field">
                <label for="f-contact">راه تماس <span class="req" aria-hidden="true">*</span></label>
                <input id="f-contact" name="contact" type="text" autocomplete="tel" placeholder="شماره واتساپ یا ایمیل" required />
              </div>
              <div class="field">
                <label for="f-goal">هدف اصلی</label>
                <select id="f-goal" name="goal">
                  <option value="کاهش وزن و چربی">کاهش وزن و چربی</option>
                  <option value="عضله‌سازی و قدرت">عضله‌سازی و قدرت</option>
                  <option value="تناسب اندام عمومی">تناسب اندام عمومی</option>
                  <option value="بازگشت پس از مصدومیت">بازگشت پس از مصدومیت</option>
                  <option value="هنوز مطمئن نیستم">هنوز مطمئن نیستم</option>
                </select>
              </div>
              <div class="field">
                <label for="f-format">نوع همراهی</label>
                <select id="f-format" name="format">
                  <option value="حضوری در کرج (مهرشهر)">حضوری در کرج (مهرشهر)</option>
                  <option value="کوچینگ آنلاین">کوچینگ آنلاین</option>
                  <option value="برنامهٔ تمرینی اختصاصی">برنامهٔ تمرینی اختصاصی</option>
                  <option value="همراهی تغذیه">همراهی تغذیه</option>
                  <option value="مشاوره و انتخاب مسیر">مشاوره و انتخاب مسیر</option>
                </select>
              </div>
              <div class="field field--full">
                <label for="f-message">چند جمله دربارهٔ خودت <span style="font-weight:400;color:var(--muted-2)">(اختیاری)</span></label>
                <textarea id="f-message" name="message" rows="4" placeholder="سابقهٔ تمرین، زمانی که در هفته داری، محدودیت‌ها…"></textarea>
              </div>
            </div>

            <div class="form-actions">
              <button class="btn btn--accent btn--full" type="submit">
                ارسال در واتساپ
                <svg aria-hidden="true"><use href="#i-whatsapp"></use></svg>
              </button>
              <button class="btn btn--ghost btn--full" type="button" id="telegramBtn">
                ارسال در تلگرام
                <svg aria-hidden="true"><use href="#i-telegram"></use></svg>
              </button>
            </div>

            <p class="form-note">
              اطلاعات تو فقط برای پاسخ به همین پیام استفاده می‌شود و در اختیار کسی
              دیگر قرار نمی‌گیرد.
            </p>
            <div class="form-status" id="formStatus" role="status" aria-live="polite" hidden></div>
          </form>
        </div>
      </div>
    </section>
  </main>

<?php
get_footer();
