<?php
/**
 * Plugin Name:       بهنیا فیت — حساب کاربری
 * Plugin URI:        https://github.com/Soheilsadri278/Behnia-fit
 * Description:       حساب کاربری شاگردها و پنل مربی: ثبت‌نام با شمارهٔ موبایل، خرید برنامه با کارت‌به‌کارت، تایید پرداخت توسط مربی، و تحویل برنامهٔ تمرینی/تغذیه با جدول حرکت‌ها و فایل پیوست.
 * Version:           1.1.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Behnia Fit
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       behnia-fit-accounts
 *
 * @package behnia-fit-accounts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BF_ACCOUNTS_VERSION', '1.1.0' );
define( 'BF_ACCOUNTS_FILE', __FILE__ );
define( 'BF_ACCOUNTS_DIR', plugin_dir_path( __FILE__ ) );
define( 'BF_ACCOUNTS_URI', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

require_once BF_ACCOUNTS_DIR . 'inc/db.php';
require_once BF_ACCOUNTS_DIR . 'inc/auth.php';
require_once BF_ACCOUNTS_DIR . 'inc/plans.php';
require_once BF_ACCOUNTS_DIR . 'inc/upload.php';
require_once BF_ACCOUNTS_DIR . 'inc/api.php';
require_once BF_ACCOUNTS_DIR . 'inc/settings.php';

/**
 * هستهٔ افزونه.
 *
 * قاعده‌ای که کل این افزونه رویش بنا شده: رابط کاربری همان سه فایل HTML
 * نسخهٔ Node است و دست‌نخورده مانده. آن فایل‌ها با آدرس‌های ثابت
 * /login.html، /account.html و /admin.html و اندپوینت‌های /api/... کار
 * می‌کنند. پس اینجا همان آدرس‌ها و همان قرارداد JSON بازسازی می‌شوند؛
 * هیچ خطی از جاوااسکریپت عوض نمی‌شود.
 *
 * مسیرها در init قلاب می‌شوند، نه با rewrite rule؛ به این ترتیب مستقل از
 * تنظیم «پیوندهای یکتا» و بدون نیاز به flush کار می‌کنند.
 */
final class BF_Accounts {

	/** آدرس‌های صفحه‌ای که این افزونه سرو می‌کند */
	const PAGES = array(
		'/login.html'   => 'login',
		'/account.html' => 'account',
		'/admin.html'   => 'admin',
	);

	/** کجا وصل می‌شویم */
	public static function boot() {
		add_action( 'init', array( __CLASS__, 'route' ), 1 );
		add_filter( 'bf_account_url', array( __CLASS__, 'account_url' ), 10, 1 );
		add_filter( 'bf_card_number', array( __CLASS__, 'card_number' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_install' ) );
	}

	/** فقط یک بار پس از فعال‌سازی جدول‌ها ساخته می‌شوند */
	public static function maybe_install() {
		if ( get_option( 'bf_accounts_db_version' ) === BF_ACCOUNTS_VERSION ) {
			return;
		}
		bf_install_tables();
		bf_install_roles();
		update_option( 'bf_accounts_db_version', BF_ACCOUNTS_VERSION );
	}

	/** سرو کردن API و صفحه‌ها */
	public static function route() {
		$path = self::request_path();

		if ( '/index.html' === $path ) {
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}

		if ( 0 === strpos( $path, '/api/' ) ) {
			BF_Accounts_API::handle( $path );
			exit;
		}

		$page = isset( self::PAGES[ $path ] ) ? self::PAGES[ $path ] : '';
		if ( $page ) {
			self::render( $page );
			exit;
		}
	}

	/** مسیر درخواست، بدون پیشوند پوشهٔ نصب */
	private static function request_path() {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
		$path = parse_url( $uri, PHP_URL_PATH );
		if ( ! is_string( $path ) || '' === $path ) {
			return '';
		}
		$path = rawurldecode( $path );

		$home = parse_url( home_url( '/' ), PHP_URL_PATH );
		$home = is_string( $home ) ? rtrim( $home, '/' ) : '';
		if ( '' !== $home && 0 === strpos( $path, $home ) ) {
			$path = substr( $path, strlen( $home ) );
		}
		if ( '' === $path || '/' !== $path[0] ) {
			$path = '/' . $path;
		}
		return $path;
	}

	/** قالب صفحه را مستقیم چاپ می‌کند — بدون wp_head تا خروجی مو‌به‌مو مثل نسخهٔ ایستا بماند */
	private static function render( $page ) {
		$file = BF_ACCOUNTS_DIR . 'templates/' . $page . '.php';
		if ( ! file_exists( $file ) ) {
			status_header( 500 );
			echo 'قالب صفحه پیدا نشد.';
			return;
		}
		status_header( 200 );
		header( 'Content-Type: text/html; charset=utf-8' );
		header( 'X-Robots-Tag: noindex, nofollow' );
		nocache_headers();
		include $file;
	}

	/** نشانی حساب کاربری برای لینک‌های سایت (قالب به این فیلتر وصل است) */
	public static function account_url( $url ) {
		return home_url( '/account.html' );
	}

	/** شمارهٔ کارت کارت‌به‌کارت — از تنظیمات می‌آید */
	public static function card_number( $value = '' ) {
		$saved = get_option( 'bf_card_number', '' );
		return '' !== $saved ? $saved : $value;
	}

	/** آدرسی که به بقیهٔ افزونه‌ها می‌دهیم */
	public static function url( $file = '' ) {
		return BF_ACCOUNTS_URI . '/' . ltrim( $file, '/' );
	}
}


/**
 * نشانی صفحهٔ حریم خصوصی — از تنظیمات خود وردپرس، با بازگشت به صفحهٔ اصلی.
 *
 * @return string
 */
function bf_accounts_privacy_url() {
	$url = get_privacy_policy_url();
	return $url ? $url : home_url( '/' );
}

BF_Accounts::boot();

register_activation_hook(
	BF_ACCOUNTS_FILE,
	static function () {
		bf_install_tables();
		bf_install_roles();
		update_option( 'bf_accounts_db_version', BF_ACCOUNTS_VERSION );
	}
);

register_deactivation_hook(
	BF_ACCOUNTS_FILE,
	static function () {
		// داده‌ها پاک نمی‌شوند: فقط با حذف واقعی افزونه از بین می‌روند.
		wp_cache_flush();
	}
);
