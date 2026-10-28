<?php
/**
 * فوتر برگه‌های حقوقی — ساختار عیناً از privacy.html.
 *
 * @package behnia-fit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<footer class="footer" style="padding-top:2.5rem">
    <div class="container">
      <div class="footer__bottom" style="border-top:0;padding-bottom:2.5rem">
        <span>© <span id="year">۱۴۰۵</span> بهنیا فیت · همهٔ حقوق محفوظ است.</span>
        <div class="footer__legal">
          <a href="<?php echo esc_url( bf_privacy_url() ); ?>">حریم خصوصی</a>
          <a href="<?php echo esc_url( home_url( '/' ) ); ?>">صفحهٔ اصلی</a>
        </div>
      </div>
    </div>
  </footer>

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
          if (link) link.href = link.href.split("?")[0] + "?r=" + Date.now();
        } catch (e) {}
      });
    })();
  </script>

  <?php wp_footer(); ?>
</body>
</html>
