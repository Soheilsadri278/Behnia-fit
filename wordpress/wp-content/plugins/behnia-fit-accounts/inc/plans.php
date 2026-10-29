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
 * حالت قیمت‌گذاری.
 *
 * auto_usd: مربی دلار وارد می‌کند و سایت با نرخ روز به تومان تبدیل می‌کند.
 * manual_toman: تبدیل خودکار خاموش است و مربی خودش مبلغ تومان را وارد می‌کند.
 */
function bf_pricing_mode() {
	$mode = get_option( 'bf_pricing_mode', 'auto_usd' );
	return 'manual_toman' === $mode ? 'manual_toman' : 'auto_usd';
}

/** آیا مربی قیمت‌ها را دستی و به تومان وارد می‌کند؟ */
function bf_manual_pricing_enabled() {
	return 'manual_toman' === bf_pricing_mode();
}

/** قیمت‌های تومانیِ دستی به شکل key => تومان */
function bf_saved_toman_prices() {
	$saved = get_option( 'bf_plan_prices_toman', array() );
	return is_array( $saved ) ? $saved : array();
}

/** قیمت دستی یک برنامه به تومان؛ صفر یعنی هنوز ثبت نشده است. */
function bf_saved_plan_toman( $key ) {
	$saved = bf_saved_toman_prices();
	if ( isset( $saved[ $key ] ) && is_numeric( $saved[ $key ] ) && (float) $saved[ $key ] > 0 ) {
		return (int) round( (float) $saved[ $key ] );
	}
	return 0;
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
 * قیمت‌های پیش‌فرض به شکل key => دلار.
 *
 * برای رندر صفحه لازم است: اگر مربی هنوز قیمتی ذخیره نکرده، نباید عدد
 * داخل قالب را به‌عنوان دلارِ تازه تفسیر کنیم، چون ممکن است همین تابع
 * قبلاً آن را به تومان تبدیل کرده باشد.
 */
function bf_plan_default_usd_map() {
	$out = array();
	foreach ( bf_plan_defaults() as $plan ) {
		$out[ $plan['key'] ] = (float) $plan['price_usd'];
	}
	return $out;
}

/**
 * عدد نمایشی یک برنامه.
 *
 * در حالت دستی، فقط مبلغ تومانیِ ثبت‌شدهٔ مربی برمی‌گردد و هیچ تبدیلی
 * انجام نمی‌شود. در حالت خودکار، دلار پایه با نرخ روز به تومان تبدیل
 * می‌شود. اگر مبلغی در دسترس نباشد، دلار پایه می‌ماند تا صفحه بی‌قیمت نشود.
 *
 * @param string $key کلید برنامه.
 * @param float  $fallback_usd قیمت دلاری جایگزین.
 * @return float|int
 */
function bf_display_amount( $key, $fallback_usd ) {
	if ( bf_manual_pricing_enabled() ) {
		$manual = bf_saved_plan_toman( $key );
		if ( $manual > 0 ) {
			return $manual;
		}
		return bf_plan_price( $key, $fallback_usd );
	}

	$usd  = bf_plan_price( $key, $fallback_usd );
	$rate = bf_usd_toman_rate();
	return $rate ? bf_usd_to_toman( $usd, $rate ) : $usd;
}

/**
 * آیا مبلغ نمایشی فعلی تومان است؟
 *
 * @param string|null $key کلید برنامه؛ خالی یعنی «در مجموع صفحه».
 * @return bool
 */
function bf_display_is_toman( $key = null ) {
	if ( bf_manual_pricing_enabled() ) {
		if ( null === $key ) {
			return (bool) bf_saved_toman_prices();
		}
		return bf_saved_plan_toman( $key ) > 0;
	}

	return (bool) bf_usd_toman_rate();
}

/** واحدها و جملهٔ زیر قیمت‌ها را با واحد نمایشی هماهنگ می‌کند. */
function bf_localize_price_units( $html ) {
	if ( ! bf_display_is_toman() ) {
		return $html;
	}

	$note = bf_manual_pricing_enabled()
		? 'قیمت‌ها به <strong$1>تومان</strong> است.'
		: 'قیمت‌ها بر اساس نرخ روز دلار محاسبه و به <strong$1>تومان</strong> نمایش داده می‌شوند.';

	$html = str_replace( '<span class="unit">دلار ·', '<span class="unit">تومان ·', $html );
	$html = preg_replace( '#(<small>\s*)دلار(\s*[·/])#u', '$1تومان$2', $html );
	$html = preg_replace(
		'#همهٔ قیمت‌ها به\s*<strong([^>]*)>دلار آمریکا</strong>\s*است\.#u',
		$note,
		$html
	);

	return $html;
}

/**
 * اصلاحیهٔ چیدمان کارت‌های قیمت برای مبلغ‌های تومانیِ طولانی.
 *
 * این CSS از داخل افزونه در <head> صفحهٔ اصلی گذاشته می‌شود تا اگر کسی
 * فقط افزونه را به‌روز کرد و پوسته هنوز قدیمی بود، کارت‌های دسکتاپ با
 * عددهای چندمیلیونی به‌هم نریزند. در قالب تازه همین قواعد داخل style.css
 * هم هست و تکراری بودنشان ضرری ندارد.
 */
function bf_pricing_layout_fix( $html ) {
	if ( false !== strpos( $html, 'bf-pricing-layout-fix' ) ) {
		return $html;
	}

	$css = '.pricing__grid{grid-template-columns:repeat(4,minmax(0,1fr))}'
		. '.services__grid{grid-template-columns:repeat(2,minmax(0,1fr))}'
		. '.pricing__grid .plan{min-width:0;container-type:inline-size}'
		. '.plan__price{min-width:0;width:100%}'
		. '.plan__price .amount{font-size:clamp(1.35rem,.95rem + 1vw,1.95rem);line-height:1.15;letter-spacing:0;max-width:100%;min-width:0;overflow-wrap:anywhere}'
		. '@supports(font-size:1cqw){.plan__price .amount{font-size:clamp(1.25rem,8.5cqw,2rem)}}'
		. '.plan__price .unit{min-width:0}'
		. '.plan .btn{min-width:0;max-width:100%;white-space:normal;text-align:center;overflow-wrap:break-word}'
		. '@media(max-width:1120px){.pricing__grid{grid-template-columns:repeat(2,minmax(0,1fr))}}'
		. '@media(max-width:940px){.services__grid{grid-template-columns:minmax(0,1fr)}.pricing__grid{grid-template-columns:repeat(2,minmax(0,1fr))}}'
		. '@media(max-width:640px){.pricing__grid{grid-template-columns:minmax(0,1fr)}}';

	$style = '<style id="bf-pricing-layout-fix">' . $css . '</style>';
	$pos   = stripos( $html, '</head>' );
	if ( false === $pos ) {
		return $html;
	}

	return substr( $html, 0, $pos ) . $style . substr( $html, $pos );
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
	if ( ! is_string( $html ) || '' === $html ) {
		return $html;
	}

	// این فیلتر روی هر صفحه‌ای صدا زده می‌شود؛ اگر صفحه قیمت ندارد، دست نمی‌زنیم.
	if ( false === strpos( $html, 'plan__price' ) && false === strpos( $html, 'price-from' ) ) {
		return $html;
	}

	// اگر قالب تازه باشد، عددها با data-bf-price نشانه خورده‌اند و دقیق‌تریم.
	if ( false !== strpos( $html, 'data-bf-price' ) ) {
		return bf_pricing_layout_fix( bf_localize_price_units( bf_render_marked_prices( $html ) ) );
	}

	// قالب قدیمی (بدون نشانه): همان جای‌ها را از روی ساختار پیدا می‌کنیم.
	// این مسیر عمداً وجود دارد تا تغییر قیمت فقط با به‌روزرسانی افزونه کار
	// کند و کسی مجبور نشود پوسته را هم دوباره نصب کند.
	return bf_pricing_layout_fix( bf_localize_price_units( bf_render_plain_prices( $html ) ) );
}

/**
 * نمایش قیمت با نشانه‌های data-bf-price (قالب تازه).
 *
 * @param string $html خروجی صفحه.
 * @return string
 */
function bf_render_marked_prices( $html ) {
	// ۱) جای عددهای مستقل (قیمت کارت‌ها و بخش خدمات)
	$html = preg_replace_callback(
		'#<(span|b|strong)([^>]*\sdata-bf-price="([a-z]+)"[^>]*)>(.*?)</\1>#s',
		static function ( $m ) {
			// این تابع ممکن است دو بار روی یک خروجی اجرا شود (فیلتر قالب و
			// بافر افزونه). اگر قبلاً به تومان تبدیل شده، دوباره تبدیل نکن.
			if ( false !== strpos( $m[2], 'data-bf-currency="IRT"' ) ) {
				return $m[0];
			}

			$defaults = bf_plan_default_usd_map();
			$fallback = isset( $defaults[ $m[3] ] ) ? $defaults[ $m[3] ] : bf_price_text_from_fa( $m[4] );
			$amount   = bf_display_amount( $m[3], $fallback );
			$attrs    = $m[2];
			if ( bf_display_is_toman( $m[3] ) ) {
				$attrs .= ' data-bf-currency="IRT"';
			}

			return '<' . $m[1] . $attrs . '>' . bf_price_text( $amount ) . '</' . $m[1] . '>';
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
				'#(·\s*)([۰-۹٬٫]+)(\s*)(دلار|تومان)#u',
				static function ( $n ) use ( $m ) {
					if ( 'تومان' === $n[4] ) {
						return $n[0];
					}
					$defaults = bf_plan_default_usd_map();
					$fallback = isset( $defaults[ $m[2] ] ) ? $defaults[ $m[2] ] : bf_price_text_from_fa( $n[2] );
					$amount   = bf_display_amount( $m[2], $fallback );
					$unit     = bf_display_is_toman( $m[2] ) ? 'تومان' : 'دلار';
					return $n[1] . bf_price_text( $amount ) . $n[3] . $unit;
				},
				$m[3]
			);

			return '<a' . $m[1] . '>' . $inner . '</a>';
		},
		$html
	);
}

/**
 * نام خدمت در بخش «خدمات» → کلید برنامه.
 *
 * @return array
 */
function bf_service_key_map() {
	return array(
		'تمرین شخصی حضوری'        => 'inperson',
		'حضوری ۱:۱'               => 'inperson',
		'کوچینگ آنلاین'           => 'online',
		'برنامهٔ تمرینی اختصاصی'  => 'training',
		'همراهی تغذیه'            => 'nutrition',
	);
}

/**
 * نمایش قیمت بدون نشانه — برای قالبی که data-bf-price ندارد.
 *
 * ساختار صفحه ثابت است و از index.html می‌آید، پس با همان شکل پیدا می‌شود:
 *   • <article class="plan">   → <span class="amount">۴</span> و «· ۴ دلار»
 *   • <article class="service"> → <span class="value">۳۰ <small>…</small>
 *
 * اگر قیمتی در تنظیمات نباشد، همان عدد نوشته‌شده در قالب دست‌نخورده می‌ماند.
 *
 * @param string $html خروجی صفحه.
 * @return string
 */
function bf_render_plain_prices( $html ) {
	// کارت‌های تعرفه — کلید از لینک خرید همان کارت خوانده می‌شود.
	$html = preg_replace_callback(
		'#<article class="plan[^"]*".*?</article>#s',
		static function ( $m ) {
			$block = $m[0];
			if ( ! preg_match( '#plan=([a-z]+)#', $block, $key ) ) {
				return $block;
			}
			$defaults = bf_plan_default_usd_map();
			$fallback = isset( $defaults[ $key[1] ] ) ? $defaults[ $key[1] ] : 0;
			$price    = bf_plan_price( $key[1], $fallback );
			if ( $price <= 0 ) {
				return $block;
			}
			$text = bf_price_text( bf_display_amount( $key[1], $fallback ) );
			$unit = bf_display_is_toman( $key[1] ) ? 'تومان' : 'دلار';

			$block = preg_replace(
				'#(<span class="amount"[^>]*>)\s*[۰-۹٬٫]+\s*(</span>)#u',
				'${1}' . $text . '${2}',
				$block,
				1
			);

			// متن دکمه: «خرید … · ۱٬۰۸۴٬۷۶۰ تومان»
			return preg_replace(
				'#(·\s*)[۰-۹٬٫]+\s*دلار#u',
				'${1}' . $text . ' ' . $unit,
				$block,
				1
			);
		},
		$html
	);

	// بخش خدمات — کلید از عنوان <h3> خوانده می‌شود.
	$keys = bf_service_key_map();

	return preg_replace_callback(
		'#<article class="service".*?</article>#s',
		static function ( $m ) use ( $keys ) {
			$block = $m[0];
			if ( ! preg_match( '#<h3>(.*?)</h3>#s', $block, $h ) ) {
				return $block;
			}
			$name = trim( wp_strip_all_tags( $h[1] ) );
			if ( ! isset( $keys[ $name ] ) ) {
				return $block;
			}
			$defaults = bf_plan_default_usd_map();
			$fallback = isset( $defaults[ $keys[ $name ] ] ) ? $defaults[ $keys[ $name ] ] : 0;
			$price    = bf_plan_price( $keys[ $name ], $fallback );
			if ( $price <= 0 ) {
				return $block;
			}

			// قالب ممکن است عدد را ساده نوشته باشد یا داخل یک <span> دیگر؛ هر دو.
			return preg_replace(
				'#(<span class="value">\s*(?:<span>)?\s*)[۰-۹٬٫]+#u',
				'${1}' . bf_price_text( bf_display_amount( $keys[ $name ], $fallback ) ),
				$block,
				1
			);
		},
		$html
	);
}

/**
 * بافر خروجی خود افزونه.
 *
 * قالب تازه خودش فیلتر bf_localize_html را صدا می‌زند، ولی اگر کسی پوسته را
 * به‌روز نکرده باشد این بافر کار را انجام می‌دهد؛ پس تغییر قیمت فقط با
 * به‌روزرسانی افزونه دیده می‌شود. اگر هر دو باشند، دو بار اجرا می‌شود و
 * نتیجه همان است (مقدارها ثابت‌اند).
 */
function bf_prices_buffer_start() {
	if ( is_admin() || is_feed() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	ob_start( 'bf_render_prices' );
}
add_action( 'template_redirect', 'bf_prices_buffer_start', 2 );
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
