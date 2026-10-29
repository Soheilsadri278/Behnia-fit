<?php
/**
 * نرخ دلار آزاد و تبدیل قیمت‌های دلاری به تومان.
 *
 * مربی قیمت پایه را به دلار وارد می‌کند؛ این فایل آخرین نرخ دلار آزاد را از
 * فایل عمومی Iran Market Data می‌خواند و قیمت نمایشی سایت را به تومان
 * می‌سازد. دادهٔ آن سرویس هر ۳۰ دقیقه منتشر می‌شود، پس نرخ هم همین‌جا
 * ۳۰ دقیقه کش می‌شود. اگر سرویس موقتاً در دسترس نباشد، آخرین نرخ موفق
 * قبلی استفاده می‌شود تا قیمت‌ها خالی یا صفر نشوند.
 *
 * @package behnia-fit-accounts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** نشانی‌های دریافت نرخ — اول Raw GitHub، بعد GitHub Pages همان داده. */
function bf_exchange_api_urls() {
	$urls = array(
		'https://raw.githubusercontent.com/iran-market/iran-market.github.io/main/data/popular.json',
		'https://iran-market.github.io/data/popular.json',
	);

	return apply_filters( 'bf_exchange_api_urls', $urls );
}

/** نرخ ذخیره‌شدهٔ آخرین دریافت موفق. */
function bf_cached_usd_toman_rate() {
	$saved = get_option( 'bf_usd_toman_rate', array() );
	if ( ! is_array( $saved ) || empty( $saved['rate'] ) || ! is_numeric( $saved['rate'] ) ) {
		return null;
	}

	$rate = (int) round( (float) $saved['rate'] );
	if ( $rate <= 0 ) {
		return null;
	}

	return array(
		'rate'        => $rate,
		'fetched_at'  => isset( $saved['fetched_at'] ) ? (string) $saved['fetched_at'] : '',
		'market_time' => isset( $saved['market_time'] ) ? (string) $saved['market_time'] : '',
		'source'      => isset( $saved['source'] ) ? (string) $saved['source'] : '',
		'stale'       => ! empty( $saved['stale'] ),
		'cached'      => true,
	);
}

/**
 * نرخ فعلی دلار به تومان.
 *
 * خروجی rate یعنی «هر یک دلار چند تومان». اگر force باشد، کش کنار می‌رود و
 * از سرویس دوباره خوانده می‌شود.
 *
 * @param bool $force دریافت دوباره از سرویس.
 * @return array|null
 */
function bf_usd_toman_rate( $force = false ) {
	static $memo = null;
	static $memo_loaded = false;

	if ( ! $force && $memo_loaded ) {
		return $memo;
	}

	$cached = bf_cached_usd_toman_rate();

	if ( ! $force && $cached && get_transient( 'bf_usd_toman_rate_fresh' ) ) {
		$memo = $cached;
		$memo_loaded = true;
		return $memo;
	}

	if ( ! $force && get_transient( 'bf_usd_toman_rate_retry' ) ) {
		if ( $cached ) {
			$cached['stale'] = true;
		}
		$memo = $cached;
		$memo_loaded = true;
		return $memo;
	}

	$fresh = bf_fetch_usd_toman_rate();
	if ( $fresh ) {
		$memo = $fresh;
		$memo_loaded = true;
		return $memo;
	}

	// سرویس جواب نداد: آخرین نرخ موفق، حتی اگر قدیمی باشد، از صفر بهتر است.
	set_transient( 'bf_usd_toman_rate_retry', 1, 5 * MINUTE_IN_SECONDS );
	if ( $cached ) {
		$cached['stale'] = true;
		$cached['cached'] = true;
	}

	$memo = $cached;
	$memo_loaded = true;
	return $memo;
}

/** دریافت و اعتبارسنجی نرخ از فایل عمومی قیمت‌ها. */
function bf_fetch_usd_toman_rate() {
	foreach ( bf_exchange_api_urls() as $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 6,
				'redirection' => 2,
				'headers'     => array(
					'Accept'     => 'application/json',
					'User-Agent' => 'BehniaFit/' . BF_ACCOUNTS_VERSION . ' WordPress',
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			continue;
		}

		$payload = json_decode( wp_remote_retrieve_body( $response ), true );
		$parsed  = bf_parse_usd_toman_rate( $payload );
		if ( ! $parsed ) {
			continue;
		}

		$parsed['fetched_at'] = gmdate( 'c' );
		$parsed['cached']     = false;

		update_option( 'bf_usd_toman_rate', $parsed, false );
		set_transient( 'bf_usd_toman_rate_fresh', 1, 30 * MINUTE_IN_SECONDS );
		delete_transient( 'bf_usd_toman_rate_retry' );

		return $parsed;
	}

	return null;
}

/**
 * از payload سرویس، رکورد دلار آزاد را پیدا می‌کند.
 *
 * واحد را از خود رکورد می‌خوانیم: IRT یعنی تومان و IRR یعنی ریال. به اسم
 * نماد تکیه نمی‌کنیم، چون نماد USD_IRR_FREE است ولی فایل popular قیمت را
 * به تومان می‌دهد.
 *
 * @param mixed $payload پاسخ decode شده.
 * @return array|null
 */
function bf_parse_usd_toman_rate( $payload ) {
	if ( ! is_array( $payload ) || empty( $payload['data'] ) || ! is_array( $payload['data'] ) ) {
		return null;
	}

	foreach ( $payload['data'] as $item ) {
		if ( ! is_array( $item ) || ! isset( $item['symbol'] ) || 'USD_IRR_FREE' !== $item['symbol'] ) {
			continue;
		}
		if ( ! isset( $item['price'] ) || ! is_numeric( $item['price'] ) ) {
			return null;
		}

		$price    = (float) $item['price'];
		$currency = isset( $item['currency'] ) ? strtoupper( (string) $item['currency'] ) : 'IRT';
		if ( 'IRR' === $currency ) {
			$price = $price / 10;
		} elseif ( 'IRT' !== $currency ) {
			return null;
		}

		$rate = (int) round( $price );
		if ( $rate <= 1000 ) {
			return null;
		}

		$market_time = isset( $item['timestamp_tehran'] ) ? (string) $item['timestamp_tehran'] : '';
		$stale       = ! empty( $item['stale'] );
		$market_ts   = $market_time ? strtotime( $market_time ) : false;
		if ( $market_ts && $market_ts < time() - DAY_IN_SECONDS ) {
			$stale = true;
		}

		return array(
			'rate'        => $rate,
			'fetched_at'  => gmdate( 'c' ),
			'market_time' => $market_time,
			'source'      => isset( $item['source'] ) ? (string) $item['source'] : 'iran-market',
			'stale'       => $stale,
		);
	}

	return null;
}

/** به‌روزرسانی اجباری نرخ؛ برای دکمهٔ تنظیمات و کرون. */
function bf_refresh_usd_toman_rate() {
	// در حالت قیمت‌گذاری دستی، تبدیل خودکار خاموش است و برای نرخ گرفتن
	// بی‌دلیل به سرویس بیرونی درخواست نمی‌فرستیم.
	if ( bf_manual_pricing_enabled() ) {
		return bf_cached_usd_toman_rate();
	}

	$before = bf_cached_usd_toman_rate();
	$after  = bf_usd_toman_rate( true );

	// اگر نرخ عوض شده و صفحه‌ها کش شده‌اند، کش را خالی می‌کنیم تا عدد تازه
	// در همان بازدیدهای بعدی بنشیند.
	if ( $after && ( ! $before || (int) $before['rate'] !== (int) $after['rate'] ) && function_exists( 'bf_flush_page_caches' ) ) {
		bf_flush_page_caches();
	}

	return $after;
}

/**
 * تبدیل دلار به تومانِ بدون اعشار.
 *
 * @param float      $usd       مبلغ دلاری.
 * @param array|null $rate_data خروجی bf_usd_toman_rate؛ اگر ندهی خودش می‌خواند.
 * @return int مبلغ تومان، یا صفر اگر نرخ نداریم.
 */
function bf_usd_to_toman( $usd, $rate_data = null ) {
	if ( null === $rate_data ) {
		$rate_data = bf_usd_toman_rate();
	}
	if ( ! $rate_data || empty( $rate_data['rate'] ) ) {
		return 0;
	}

	return (int) round( (float) $usd * (int) $rate_data['rate'] );
}

/** 
 * قیمت تومانیِ مؤثر یک برنامه.
 *
 * در حالت دستی همان مبلغی است که مربی به تومان ثبت کرده و نرخ دلار در آن
 * دخالت ندارد. در حالت خودکار، قیمت دلاری با نرخ روز تبدیل می‌شود.
 */
function bf_plan_price_toman( $key, $fallback_usd = 0 ) {
	if ( bf_manual_pricing_enabled() ) {
		return bf_saved_plan_toman( $key );
	}

	return bf_usd_to_toman( bf_plan_price( $key, $fallback_usd ) );
}

/** مبلغ تومان با ارقام فارسی و جداکنندهٔ هزارگان: «۱٬۰۸۴٬۷۶۰ تومان». */
function bf_toman_text( $amount ) {
	return bf_price_text( (int) round( (float) $amount ) ) . ' تومان';
}

/** شکل عمومی نرخ برای پاسخ API. */
function bf_exchange_public( $rate_data = null ) {
	if ( bf_manual_pricing_enabled() ) {
		return null;
	}

	if ( null === $rate_data ) {
		$rate_data = bf_usd_toman_rate();
	}
	if ( ! $rate_data ) {
		return null;
	}

	return array(
		'currency'       => 'IRT',
		'unit'           => 'تومان',
		'usd_toman_rate' => (int) $rate_data['rate'],
		'market_time'    => isset( $rate_data['market_time'] ) ? $rate_data['market_time'] : '',
		'fetched_at'     => isset( $rate_data['fetched_at'] ) ? $rate_data['fetched_at'] : '',
		'stale'          => ! empty( $rate_data['stale'] ),
	);
}

/* ------------------------------------------------------------ کرون */

add_filter(
	'cron_schedules',
	static function ( $schedules ) {
		if ( ! isset( $schedules['bf_every_30_minutes'] ) ) {
			$schedules['bf_every_30_minutes'] = array(
				'interval' => 30 * MINUTE_IN_SECONDS,
				'display'  => 'هر ۳۰ دقیقه (بهنیا فیت)',
			);
		}
		return $schedules;
	}
);

add_action(
	'init',
	static function () {
		if ( ! wp_next_scheduled( 'bf_refresh_usd_toman_rate' ) ) {
			wp_schedule_event( time() + 5 * MINUTE_IN_SECONDS, 'bf_every_30_minutes', 'bf_refresh_usd_toman_rate' );
		}
	},
	20
);

add_action( 'bf_refresh_usd_toman_rate', 'bf_refresh_usd_toman_rate' );
