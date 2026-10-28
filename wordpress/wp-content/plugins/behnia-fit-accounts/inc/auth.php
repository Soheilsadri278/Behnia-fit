<?php
/**
 * احراز هویت.
 *
 * در نسخهٔ Node رمز با scrypt هش می‌شد و نشست یک توکن تصادفی در دیتابیس
 * بود. در وردپرس این کارها را خود هسته انجام می‌دهد (password_hash و
 * کوکی نشست امضاشده) و ما فقط رویش سوار می‌شویم: شناسهٔ کاربر همان
 * شمارهٔ موبایل است و نقش‌ها همان bf_student و bf_coach.
 *
 * @package behnia-fit-accounts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * شمارهٔ موبایل ایران را به شکل یکدست 09xxxxxxxxx درمی‌آورد.
 *
 * دقیقاً همان قاعدهٔ lib/auth.js: ارقام فارسی/عربی، فاصله، خط تیره،
 * پرانتز، +98، 0098 و 98 ابتدای شماره پاک می‌شوند.
 *
 * @param mixed $raw ورودی خام.
 * @return string|null شمارهٔ درست یا null.
 */
function bf_normalize_phone( $raw ) {
	if ( ! is_string( $raw ) ) {
		return null;
	}

	$digits = array(
		'۰' => '0',
		'۱' => '1',
		'۲' => '2',
		'۳' => '3',
		'۴' => '4',
		'۵' => '5',
		'۶' => '6',
		'۷' => '7',
		'۸' => '8',
		'۹' => '9',
		'٠' => '0',
		'١' => '1',
		'٢' => '2',
		'٣' => '3',
		'٤' => '4',
		'٥' => '5',
		'٦' => '6',
		'٧' => '7',
		'٨' => '8',
		'٩' => '9',
	);

	$s = strtr( $raw, $digits );
	$s = preg_replace( '/[\s\x{00A0}\-()]/u', '', $s );
	$s = preg_replace( '/^\+98/', '0', $s );
	$s = preg_replace( '/^0098/', '0', $s );
	$s = preg_replace( '/^98(?=9\d{9}$)/', '0', $s );

	if ( ! is_string( $s ) || ! preg_match( '/^09\d{9}$/', $s ) ) {
		return null;
	}
	return $s;
}

/**
 * نام را تمیز می‌کند: فاصله‌های تکراری یکی می‌شوند، طول بین ۲ تا ۶۰.
 *
 * @param mixed $raw ورودی خام.
 * @return string|null
 */
function bf_normalize_name( $raw ) {
	if ( ! is_string( $raw ) ) {
		return null;
	}
	$s = trim( preg_replace( '/\s+/u', ' ', $raw ) );
	$n = function_exists( 'mb_strlen' ) ? mb_strlen( $s ) : strlen( $s );
	if ( $n < 2 || $n > 60 ) {
		return null;
	}
	return $s;
}

/**
 * قانون رمز: حداقل ۸ کاراکتر.
 *
 * @param mixed $password رمز خام.
 * @return bool
 */
function bf_valid_password( $password ) {
	if ( ! is_string( $password ) ) {
		return false;
	}
	$n = function_exists( 'mb_strlen' ) ? mb_strlen( $password ) : strlen( $password );
	return $n >= 8 && $n <= 200;
}

/**
 * کاربر وردپرس را به شکل عمومی برمی‌گرداند — همان کلیدهایی که فرانت‌اند
 * انتظار دارد: id، name، phone، role.
 *
 * @param WP_User|null $user کاربر.
 * @return array|null
 */
function bf_user_public( $user ) {
	if ( ! $user instanceof WP_User || ! $user->ID ) {
		return null;
	}
	return array(
		'id'    => (int) $user->ID,
		'name'  => (string) $user->display_name,
		'phone' => (string) get_user_meta( $user->ID, 'bf_phone', true ),
		'role'  => bf_is_coach( $user ) ? 'coach' : 'student',
	);
}

/**
 * آیا این کاربر مربی است؟
 *
 * @param WP_User|null $user کاربر.
 * @return bool
 */
function bf_is_coach( $user ) {
	if ( ! $user instanceof WP_User || ! $user->ID ) {
		return false;
	}
	return user_can( $user, 'bf_manage' );
}

/** کاربر فعلی یا null */
function bf_current_user() {
	if ( ! is_user_logged_in() ) {
		return null;
	}
	$user = wp_get_current_user();
	return $user instanceof WP_User && $user->ID ? $user : null;
}

/**
 * کاربر را با شمارهٔ موبایل پیدا می‌کند.
 *
 * @param string $phone شمارهٔ یکدست‌شده.
 * @return WP_User|null
 */
function bf_user_by_phone( $phone ) {
	$args = array(
		'meta_key'    => 'bf_phone',
		'meta_value'  => $phone,
		'number'      => 1,
		'count_total' => false,
	);
	$found = get_users( $args );
	return $found ? $found[0] : null;
}

/* ------------------------------------------------- محدودیت تلاش ورود */

/**
 * آیا این IP بیش از حد تلاش کرده؟
 *
 * نسخهٔ Node شمارنده را در حافظه نگه می‌داشت (با هر ری‌استارت صفر می‌شد).
 * اینجا transient است، پس پایدارتر است.
 *
 * @param string $ip نشانی.
 * @return bool
 */
function bf_too_many_attempts( $ip ) {
	$key = 'bf_try_' . md5( (string) $ip );
	$rec = get_transient( $key );
	if ( ! is_array( $rec ) ) {
		return false;
	}
	return (int) $rec['count'] >= 8;
}

/**
 * یک تلاش را ثبت می‌کند. موفق ⇒ صفر شدن شمارنده.
 *
 * @param string $ip نشانی.
 * @param bool   $ok نتیجه.
 */
function bf_note_attempt( $ip, $ok ) {
	$key = 'bf_try_' . md5( (string) $ip );

	if ( $ok ) {
		delete_transient( $key );
		return;
	}

	$rec = get_transient( $key );
	if ( ! is_array( $rec ) ) {
		$rec = array( 'count' => 0 );
	}
	$rec['count'] = (int) $rec['count'] + 1;
	set_transient( $key, $rec, 15 * MINUTE_IN_SECONDS );
}

/** نشانی کلاینت — پشت پروکسی به X-Forwarded-For اعتماد می‌کنیم فقط اگر تنظیم شده باشد */
function bf_client_ip() {
	$trust = (bool) get_option( 'bf_trust_proxy', false );
	if ( $trust && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$parts = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
		return trim( $parts[0] );
	}
	return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
}
