<?php
/**
 * برگه‌های عادی (حریم خصوصی و مانند آن).
 *
 * @package behnia-fit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main class="legal">
  <?php
  while ( have_posts() ) :
    the_post();
    // محتوای اصلی صفحه‌های حقوقی به‌صورت فایل در قالب است، نه در دیتابیس:
    // ذخیره در دیتابیس برای کاربرِ بدون دسترسی unfiltered_html باعث
    // حذف تگ‌های svg توسط KSES و شکستن آیکون‌ها می‌شود.
    // برای ویرایش متن، همین فایل را عوض کنید؛ اگر فایل نباشد،
    // محتوای برگهٔ وردپرس نمایش داده می‌شود.
    $bf_slug = get_post_field( 'post_name', get_the_ID() );
    $bf_file = get_theme_file_path( 'template-parts/content-' . sanitize_file_name( $bf_slug ) . '.html' );
    $bf_content = get_the_content();
    if ( file_exists( $bf_file ) ) {
        include $bf_file;
    } elseif ( false === strpos( $bf_content, '<!-- wp:' ) ) {
        // HTML خام؛ wpautop با افزودن <p> خالی چیدمان را جابه‌جا می‌کند.
        echo $bf_content; // phpcs:ignore WordPress.Security.EscapeOutput
    } else {
        the_content();
    }
  endwhile;
  ?>
</main>

<?php
get_footer( 'legal' );
