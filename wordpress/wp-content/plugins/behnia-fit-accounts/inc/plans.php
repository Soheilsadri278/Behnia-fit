<?php
/**
 * کاتالوگ برنامه‌ها و قیمت‌ها.
 *
 * قیمت‌ها از پیشخوان وردپرس (تنظیمات ← بهنیا فیت) خوانده می‌شوند و در
 * سفارش‌های تازه اعمال می‌شوند. سفارش‌های قبلی مبلغ خودشان را نگه می‌دارند،
 * چون مبلغ هنگام ثبت سفارش در جدول نوشته می‌شود.
 *
 * @package behnia-fit-accounts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کاتالوگ پیش‌فرض — همان چیزی که روی صفحهٔ اصلی نوشته شده است.
 *
 * price_usd عدد پیش‌فرض است؛ اگر مربی از پیشخوان عوض کند، همان جای این
 * عدد می‌نشیند و بقیهٔ مشخصات دست نمی‌خورند.
 */
function bf_plan_defaults() {
	return array(
		array(
			'key'         => 'nutrition',
			'title'       => 'برنامهٔ تغذیه',
			'description' => 'برنامهٔ غذایی متناسب با هدف و سبک زندگی تو',
			'price_usd'   => 4,
			'period'      => 'once',
			'sort'        => 1,
		),
		array(
			'key'         => 'training',
			'title'       => 'برنامهٔ تمرینی',
			'description' => 'برنامهٔ اختصاصی تمرین با اصلاح فرم و پیشرفت هفتگی',
			'price_usd'   => 8,
			'period'      => 'once',
			'sort'        => 2,
		),
		array(
			'key'         => 'online',
			'title'       => 'کوچینگ آنلاین',
			'description' => 'پیگیری کامل تمرین و تغذیه با بازبینی ماهانه',
			'price_usd'   => 15,
			'period'      => 'month',
			'sort'        => 3,
		),
		array(
			'key'         => 'inperson',
			'title'       => 'تمرین حضوری ۱:۱',
			'description' => 'جلسهٔ حضوری در کرج، مهرشهر با اصلاح فرم زنده',
			'price_usd'   => 30,
			'period'      => 'session',
			'sort'        => 4,
		),
	);
}

/** قیمت‌های ذخیره‌شده به شکل key => number */
function bf_saved_prices() {
	$saved = get_option( 'bf_plan_prices', array() );
	return is_array( $saved ) ? $saved : array();
}

/**
 * قیمت یک برنامه. اگر مربی عوض نکرده باشد، پیش‌فرض برمی‌گردد.
 *
 * @param string $key   کلید برنامه.
 * @param float  $fallback مقدار پیش‌فرض.
 * @return float
 */
function bf_plan_price( $key, $fallback = 0 ) {
	$saved = bf_saved_prices();
	if ( isset( $saved[ $key ] ) && is_numeric( $saved[ $key ] ) && (float) $saved[ $key ] > 0 ) {
		return (float) $saved[ $key ];
	}
	return (float) $fallback;
}

/**
 * عدد را با ارقام فارسی و جداکنندهٔ هزارگان می‌نویسد.
 *
 * ۴ → «۴»، ۱۲٫۵ → «۱۲٫۵»، ۱۵۰۰ → «۱٬۵۰۰»
 *
 * @param float $amount مبلغ.
 * @return string
 */
function bf_price_text( $amount ) {
	$amount = (float) $amount;

	// حداکثر دو رقم اعشار، بدون صفرهای بی‌مصرف.
	if ( floor( $amount ) === $amount ) {
		$text = number_format( $amount, 0, '.', '' );
	} else {
		$text = rtrim( rtrim( number_format( $amount, 2, '.', '' ), '0' ), '.' );
	}

	// جداکنندهٔ هزارگان فارسی.
	if ( strpos( $text, '.' ) !== false ) {
		list( $whole, $frac ) = explode( '.', $text, 2 );
	} else {
		$whole = $text;
		$frac  = '';
	}
	$whole = preg_replace( '/\B(?=(\d{3})+(?!\d))/', '٬', $whole );
	$text  = '' !== $frac ? $whole . '٫' . $frac : $whole;

	return str_replace(
		array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ),
		array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ),
		$text
	);
}

/**
 * جای خالی قیمت‌ها در HTML را پر می‌کند.
 *
 * صفحهٔ اصلی از همان index.html ساخته می‌شود و عددها داخلش نوشته شده‌اند
 * (۴، ۸، ۱۵، ۳۰). جای هر عدد با data-bf-price نشانه‌گذاری شده تا اینجا با
 * قیمت تنظیمات عوض شود؛ ظاهر و بقیهٔ متن دست نمی‌خورد. اگر افزونه غیرفعال
 * باشد، همان عددهای نوشته‌شده سر جایشان می‌مانند.
 *
 * @param string $html خروجی صفحه.
 * @return string
 */
function bf_render_prices( $html ) {
	if ( false === strpos( $html, 'data-bf-price' ) ) {
		return $html;
	}

	// ۱) جای عددهای مستقل (قیمت کارت‌ها و بخش خدمات)
	$html = preg_replace_callback(
		'#<(span|b|strong)([^>]*\sdata-bf-price="([a-z]+)"[^>]*)>(.*?)</\1>#s',
		static function ( $m ) {
			$fallback = bf_price_text_from_fa( $m[4] );
			$price    = bf_plan_price( $m[3], $fallback );

			return '<' . $m[1] . $m[2] . '>' . bf_price_text( $price ) . '</' . $m[1] . '>';
		},
		$html
	);

	// ۲) عددی که وسط متن دکمه نشسته: «خرید برنامهٔ تغذیه · ۴ دلار»
	//
	// دکمه inline-flex است و gap دارد؛ اگر عدد را تگ بپیچیم، فاصله می‌افتد و
	// ظاهر عوض می‌شود. پس فقط ارقام را در همان متن جایگزین می‌کنیم.
	return preg_replace_callback(
		'#<a\b([^>]*\sdata-bf-price-label="([a-z]+)"[^>]*)>(.*?)</a>#s',
		static function ( $m ) {
			$inner = preg_replace_callback(
				'#(·\s*)([۰-۹٬٫]+)(\s*دلار)#u',
				static function ( $n ) use ( $m ) {
					$fallback = bf_price_text_from_fa( $n[2] );
					$price    = bf_plan_price( $m[2], $fallback );
					return $n[1] . bf_price_text( $price ) . $n[3];
				},
				$m[3]
			);

			return '<a' . $m[1] . '>' . $inner . '</a>';
		},
		$html
	);
}
add_filter( 'bf_localize_html', 'bf_render_prices' );

/**
 * ارقام فارسی داخل تگ را به عدد برمی‌گرداند — برای وقتی که مربی قیمتی را
 * عوض نکرده و می‌خواهیم همان عدد نوشته‌شده در قالب بماند.
 *
 * @param string $text محتوای تگ.
 * @return float
 */
function bf_price_text_from_fa( $text ) {
	$text = wp_strip_all_tags( $text );
	$text = str_replace(
		array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٬', '٫' ),
		array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '', '.' ),
		$text
	);
	$text = preg_replace( '/[^0-9.]/', '', $text );
	return '' !== $text ? (float) $text : 0;
}
