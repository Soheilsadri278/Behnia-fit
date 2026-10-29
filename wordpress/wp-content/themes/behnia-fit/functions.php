<?php
/**
 * بهنیا فیت — توابع قالب.
 *
 * اصل حاکم بر این فایل: وردپرس نباید هیچ استایل یا اسکریپتی تزریق کند که
 * ظاهر سایت را تغییر دهد. هر چیزی که قالب پیش‌فرض اضافه می‌کند حذف می‌شود،
 * تا خروجی تا حد امکان با نسخهٔ ایستا یکی باشد.
 *
 * @package behnia-fit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BF_VERSION', '1.0.0' );

if ( ! defined( 'BF_THEME_URI' ) ) {
	define( 'BF_THEME_URI', get_template_directory_uri() );
}

/* ------------------------------------------------------- فعال‌سازی قالب */

/**
 * پس از فعال‌سازی: برگهٔ حریم خصوصی ساخته می‌شود (متنش از فایل قالب می‌آید)
 * و به‌عنوان صفحهٔ رسمی حریم خصوصی وردپرس ثبت می‌شود. بدون این، لینک
 * «حریم خصوصی» در فوتر صفحه‌های حقوقی به ۴۰۴ می‌خورد.
 */
function bf_activate() {
	$page = get_page_by_path( 'privacy' );

	if ( ! $page instanceof WP_Post ) {
		$id = wp_insert_post(
			array(
				'post_title'   => 'حریم خصوصی',
				'post_name'    => 'privacy',
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => '',
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			$page = get_post( $id );
		}
	}

	if ( $page instanceof WP_Post && ! get_option( 'wp_page_for_privacy_policy' ) ) {
		update_option( 'wp_page_for_privacy_policy', $page->ID );
	}

	// پیوندهای یکتا با ساختار /%postname%/ تا صفحه‌ها نشانی تمیز بگیرند
	if ( ! get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}

	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'bf_activate' );

/* ---------------------------------------------------------------- پیکربندی */

function bf_setup() {
	// عنوان صفحه‌ها دستی در قالب‌ها نوشته شده تا عیناً مثل نسخهٔ ایستا باشد
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'style', 'script' ) );
	add_theme_support( 'automatic-feed-links' );

	// منوها اختیاری‌اند؛ قالب بدون آن‌ها هم کامل کار می‌کند
	register_nav_menus(
		array(
			'primary' => __( 'منوی اصلی', 'behnia-fit' ),
			'footer'  => __( 'منوی فوتر', 'behnia-fit' ),
		)
	);
}
add_action( 'after_setup_theme', 'bf_setup' );

/** عرض محتوا فقط برای ادیتور — روی فرانت‌اند اثری ندارد */
function bf_content_width() {
	$GLOBALS['content_width'] = 1180;
}
add_action( 'after_setup_theme', 'bf_content_width', 0 );

/* ------------------------------------------------- تگ body بدون کلاس اضافه */

/**
 * نسخهٔ ایستا <body> خالی دارد. body_class() همیشه class="" چاپ می‌کند و
 * برخی افزونه‌ها کلاس‌های ناخواسته اضافه می‌کنند؛ پس خودمان کنترل می‌کنیم.
 */
function bf_body_tag() {
	$classes = apply_filters( 'bf_body_class', array() );
	$classes = array_filter( (array) $classes );

	if ( $classes ) {
		echo '<body class="' . esc_attr( implode( ' ', array_unique( $classes ) ) ) . '">';
	} else {
		echo '<body>';
	}
}

/* ------------------------------------------------------------------ دارایی‌ها */

function bf_assets() {
	// صفحهٔ ۴۰۴ عمداً کاملاً خودبسنده است (همان 404.html نسخهٔ ایستا با
	// استایل درون‌خطی)؛ بارگذاری CSS/JS سایت رویش قلم و رنگ را عوض می‌کند.
	if ( is_404() ) {
		return;
	}

	$dir = get_template_directory();

	// CSS اصلی سایت — همان فایلی که نسخهٔ ایستا استفاده می‌کند
	wp_enqueue_style(
		'bf-main',
		BF_THEME_URI . '/assets/css/style.css',
		array(),
		bf_file_version( $dir . '/assets/css/style.css' )
	);

	// استایل صفحهٔ حقوقی، درست بعد از CSS اصلی تا ترتیب آبشاری حفظ شود
	if ( is_page() && ! is_front_page() && file_exists( $dir . '/inc/legal.css' ) ) {
		$legal = file_get_contents( $dir . '/inc/legal.css' ); // phpcs:ignore
		if ( $legal ) {
			wp_add_inline_style( 'bf-main', $legal );
		}
	}

	// JS اصلی — همان فایل، با defer تا رفتار نسخهٔ ایستا حفظ شود
	wp_enqueue_script(
		'bf-main',
		BF_THEME_URI . '/assets/js/main.js',
		array(),
		bf_file_version( $dir . '/assets/js/main.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	// فونت‌ها با preload تا مثل نسخهٔ ایستا زودتر برسند
	add_action( 'wp_head', 'bf_preload_fonts', 1 );
}
add_action( 'wp_enqueue_scripts', 'bf_assets' );

/** نسخهٔ فایل بر اساس زمان تغییر، تا کش مرورگر با هر ویرایش باطل شود */
function bf_file_version( $path ) {
	if ( file_exists( $path ) ) {
		return (string) filemtime( $path );
	}
	return BF_VERSION;
}

function bf_preload_fonts() {
	$fonts = array( 'estedad-arabic-800.woff2', 'vazirmatn-arabic-400.woff2' );
	foreach ( $fonts as $font ) {
		printf(
			'<link rel="preload" as="font" type="font/woff2" href="%s" crossorigin />' . "\n",
			esc_url( BF_THEME_URI . '/assets/fonts/' . $font )
		);
	}
}

/* --------------------------------------- حذف هر چیزی که ظاهر را عوض می‌کند */

function bf_dequeue_wp_styles() {
	// استایل بلوک‌ها، استایل سراسری و کلاسیک — هیچ‌کدام در نسخهٔ ایستا نیستند
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
	wp_dequeue_style( 'core-block-supports' );
	wp_dequeue_style( 'wc-blocks-style' );

	// اگر قالب بلوکی فعال شود، این‌ها هم اضافه می‌شوند
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
	remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
	remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
}
add_action( 'wp_enqueue_scripts', 'bf_dequeue_wp_styles', 100 );

function bf_remove_head_clutter() {
	// اسکریپت ایموجی، oEmbed و لینک‌های اضافی — هیچ‌کدام در نسخهٔ ایستا نیست
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	// canonical دستی در header.php است؛ نسخهٔ وردپرس حذف می‌شود تا تکراری نباشد.
	remove_action( 'wp_head', 'rel_canonical' );
	remove_action( 'wp_head', 'rest_output_link_wp_head' );
	remove_action( 'wp_head', 'wp_resource_hints', 2 );
	// نسخهٔ ایستا فید RSS ندارد؛ لینک‌های alternate هم لازم نیست.
	remove_action( 'wp_head', 'feed_links', 2 );
	remove_action( 'wp_head', 'feed_links_extra', 3 );
}

/** Speculation Rules وردپرس ۷ در نسخهٔ ایستا وجود ندارد. */
add_filter( 'wp_speculation_rules_configuration', '__return_null' );

/** متای robots خودِ وردپرس (max-image-preview) هم در نسخهٔ ایستا نیست؛
 *  متای robots را خود قالب در header.php می‌نویسد. */
add_filter( 'wp_robots', '__return_empty_array' );

/** استایل auto-sizes وردپرس ۷ برای تصویرهای این قالب کاربردی ندارد. */
add_action( 'wp_enqueue_scripts', function () {
	wp_dequeue_style( 'wp-img-auto-sizes-contain' );
}, 100 );
add_action( 'init', 'bf_remove_head_clutter' );

/** ادیتور بلوکی CSS خودش را به فرانت نمی‌فرستد */
add_filter( 'should_load_separate_core_block_assets', '__return_true' );


/* ------------------------------------------------------------- متاتگ‌ها */

/**
 * مقادیر پیش‌فرض دقیقاً همان متن‌های نسخهٔ ایستا هستند.
 * مقدار هر صفحه را می‌توان با فیلد دلخواه bf_description عوض کرد.
 */
const BF_FRONT_TITLE    = 'بهنیا فیت | مربی شخصی و کوچینگ آنلاین · کارشناس ارشد فیزیولوژی کاربردی';
const BF_FRONT_DESC     = 'کارشناس ارشد فیزیولوژی کاربردی، مربی رسمی فدراسیون و قهرمان فیتنس با ۵ سال سابقهٔ حرفه‌ای. تمرین حضوری و کوچینگ آنلاین با برنامهٔ اختصاصی، اصلاح فرم و پیگیری هفتگی.';
const BF_FRONT_OG_TITLE = 'بهنیا فیت | مربی شخصی، کارشناس ارشد فیزیولوژی کاربردی';
const BF_FRONT_OG_DESC  = 'قوی‌تر شو، بدون حدس و گمان. کارشناس ارشد فیزیولوژی کاربردی، مربی رسمی فدراسیون، قهرمان فیتنس و ۵ سال سابقهٔ حرفه‌ای. حضوری و آنلاین.';
const BF_PRIVACY_DESC   = 'توضیح شفاف دربارهٔ اینکه این وب‌سایت چه اطلاعاتی جمع می‌کند، چه اطلاعاتی جمع نمی‌کند و چگونه با داده‌های شما رفتار می‌شود.';

function bf_doc_title() {
	if ( is_front_page() ) {
		return BF_FRONT_TITLE;
	}
	if ( is_singular() ) {
		return get_the_title() . ' | ' . get_bloginfo( 'name' );
	}
	if ( is_404() ) {
		return 'صفحه پیدا نشد | ' . get_bloginfo( 'name' );
	}
	return get_bloginfo( 'name' );
}

function bf_doc_description() {
	if ( is_front_page() ) {
		return BF_FRONT_DESC;
	}
	if ( is_singular() ) {
		$custom = get_post_meta( get_the_ID(), 'bf_description', true );
		if ( $custom ) {
			return $custom;
		}
		// توضیح ثابت برگه‌های حقوقی، عیناً مثل نسخهٔ ایستا.
		if ( 'privacy' === get_post_field( 'post_name', get_the_ID() ) ) {
			return BF_PRIVACY_DESC;
		}
		// excerpts خودکار وردپرس متن منو را هم برمی‌دارد؛ عمداً استفاده نمی‌شود.
		$content = wp_strip_all_tags( strip_shortcodes( get_the_content() ) );
		$content = trim( preg_replace( '/\s+/u', ' ', $content ) );
		if ( mb_strlen( $content ) > 60 ) {
			return wp_trim_words( $content, 28, '…' );
		}
	}
	return get_bloginfo( 'description' );
}

/** صفحهٔ اصلی مثل نسخهٔ ایستا متای robots ندارد؛ بقیه noindex,follow. */
function bf_doc_robots() {
	return is_front_page() ? '' : 'noindex, follow';
}

function bf_robots_meta() {
	$value = bf_doc_robots();
	if ( '' !== $value ) {
		echo '<meta name="robots" content="' . esc_attr( $value ) . '" />' . "\n  ";
	}
}

function bf_doc_og_title() {
	return is_front_page() ? BF_FRONT_OG_TITLE : bf_doc_title();
}

function bf_doc_og_description() {
	return is_front_page() ? BF_FRONT_OG_DESC : bf_doc_description();
}

function bf_doc_canonical() {
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	if ( is_singular() ) {
		return get_permalink();
	}
	return home_url( add_query_arg( array(), $GLOBALS['wp']->request ) );
}

/* ------------------------------------------------- بومی‌سازی لینک‌ها */

/**
 * قالب‌ها برشِ عیناً یکسانِ فایل‌های ایستا هستند، پس لینک‌ها همان‌جا به شکل
 * «account.html» و «#contact» مانده‌اند. اینجا فقط در خروجی نهایی اصلاح می‌شوند:
 *
 *  • account.html و account.html?plan=… → نشانی واقعی حساب کاربری
 *  • #anchor روی صفحه‌های دیگر → صفحهٔ اصلی + لنگر (وگرنه لینک مرده می‌شود)
 *
 * ارجاع‌های SVG (#i-check و مانند آن) و #main دست‌نخورده می‌مانند.
 */
function bf_localize_output( $html ) {
	// قیمت‌ها در صفحه با افزونهٔ حساب کاربری عوض می‌شوند؛ اگر افزونه نباشد،
	// همان عددهای نوشته‌شده در قالب می‌مانند.
	$html = apply_filters( 'bf_localize_html', $html );

	$account = esc_url( bf_account_url() );
	$html    = str_replace( 'href="account.html', 'href="' . $account, $html );

	if ( ! is_front_page() ) {
		$home = esc_url( home_url( '/' ) );
		$html = preg_replace_callback(
			'/href="#(about|services|process|results|pricing|faq|contact|hero|audience)"/',
			static function ( $m ) use ( $home ) {
				return 'href="' . $home . '#' . $m[1] . '"';
			},
			$html
		);
	}

	return $html;
}

function bf_buffer_start() {
	ob_start( 'bf_localize_output' );
}
add_action( 'template_redirect', 'bf_buffer_start', 1 );

/* --------------------------------------------------------------- نوار مدیریت */

/**
 * نوار بالای وردپرس با position:fixed بالای سایت می‌نشیند و ۳۲ پیکسل
 * جابه‌جایی ایجاد می‌کند. چون این سایت هدیه است و مربی از پیشخوان استفاده
 * می‌کند، نوار را کامل خاموش می‌کنیم تا چیزی از ظاهر تغییر نکند.
 */
add_filter( 'show_admin_bar', '__return_false' );

/* ------------------------------------------------------------ عنوان صفحه‌ها */

/**
 * title-tag فعال نیست (عنوان در قالب‌ها دستی نوشته شده تا عیناً یکسان بماند).
 * فقط برای اطمینان، اگر جایی وردپرس عنوان گذاشت، پاکش می‌کنیم.
 */
remove_action( 'wp_head', '_wp_render_title_tag', 1 );

/* ----------------------------------------------------- پیوندهای صفحهٔ حقوقی */

/**
 * در نسخهٔ ایستا لینک حریم خصوصی «privacy.html» بود. اینجا شناسهٔ برگه
 * استفاده می‌شود تا اگر برگه حذف نشود، لینک هم نشکند.
 */
function bf_privacy_url() {
	$page = get_page_by_path( 'privacy' );
	if ( $page instanceof WP_Post ) {
		return get_permalink( $page );
	}
	return home_url( '/' );
}

/* --------------------------------------------------------- اسکریپت‌های کمکی */

/**
 * قالب به هیچ افزونه‌ای وابسته نیست. اگر ووکامرس یا افزونهٔ حساب کاربری
 * نصب شود، این قلاب‌ها اجازهٔ همکاری می‌دهند؛ بدون آن‌ها هم همه چیز کار می‌کند.
 */
function bf_account_url() {
	$url = apply_filters( 'bf_account_url', '' );
	if ( $url ) {
		return $url;
	}
	// اگر برگه‌ای با نام account ساخته شده باشد
	$page = get_page_by_path( 'account' );
	if ( $page instanceof WP_Post ) {
		return get_permalink( $page );
	}
	return wp_login_url();
}
