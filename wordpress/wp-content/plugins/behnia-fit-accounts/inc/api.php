<?php
/**
 * مسیرهای API — همان قرارداد نسخهٔ Node، همان کلیدها، همان پیام‌های فارسی.
 *
 * قاعدهٔ امنیتی نسخهٔ Node اینجا هم برقرار است: هر کوئری به برنامه‌ها با
 * شناسهٔ کاربرِ نشست محدود می‌شود و هیچ مسیری برنامهٔ کاربر دیگر را
 * برنمی‌گرداند، حتی با حدس زدن id.
 *
 * @package behnia-fit-accounts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class BF_Accounts_API {

	/** پاسخ عمومی موفق */
	private static function ok( $data = array() ) {
		self::json( 200, array_merge( array( 'ok' => true ), $data ) );
	}

	/** پاسخ خطا با همان شکل { ok:false, error:"..." } */
	private static function fail( $status, $message ) {
		self::json( $status, array( 'ok' => false, 'error' => $message ) );
	}

	private static function json( $status, $data ) {
		status_header( $status );
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		echo wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		exit;
	}

	/** ضد CSRF — معادل sameOrigin در Node */
	private static function same_origin() {
		$origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? (string) $_SERVER['HTTP_ORIGIN'] : '';
		if ( '' === $origin ) {
			return true; // درخواست‌های غیرمرورگری Origin ندارند
		}
		$host = wp_parse_url( $origin, PHP_URL_HOST );
		if ( ! $host ) {
			return false;
		}
		$port  = wp_parse_url( $origin, PHP_URL_PORT );
		$mine  = isset( $_SERVER['HTTP_HOST'] ) ? (string) $_SERVER['HTTP_HOST'] : '';
		$given = $port ? $host . ':' . $port : $host;
		return 0 === strcasecmp( $given, $mine );
	}

	/** بدنهٔ JSON */
	private static function json_body() {
		$raw = file_get_contents( 'php://input' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( ! $raw ) {
			return array();
		}
		$data = json_decode( $raw, true );
		return is_array( $data ) ? $data : array();
	}

	/* ------------------------------------------------------------ پلن‌ها */

	/**
	 * کاتالوگ. قیمت‌ها همان است که روی صفحهٔ اصلی سایت نوشته شده.
	 * با فیلتر bf_plans می‌شود از کد عوضش کرد.
	 */
	public static function plans() {
		$plans  = bf_plan_defaults();
		$manual = bf_manual_pricing_enabled();
		$rate   = $manual ? null : bf_usd_toman_rate();

		foreach ( $plans as $i => $plan ) {
			$plans[ $i ]['price_usd']      = bf_plan_price( $plan['key'], $plan['price_usd'] );
			$plans[ $i ]['price_toman']    = bf_plan_price_toman( $plan['key'], $plans[ $i ]['price_usd'] );
			$plans[ $i ]['usd_toman_rate'] = $rate ? (int) $rate['rate'] : 0;
			$plans[ $i ]['pricing_mode']   = bf_pricing_mode();
		}

		return apply_filters( 'bf_plans', $plans );
	}

	private static function plan_by_key( $key ) {
		foreach ( self::plans() as $plan ) {
			if ( $plan['key'] === $key ) {
				return $plan;
			}
		}
		return null;
	}

	/* ------------------------------------------------------------ کاربر */

	private static function public_user() {
		$user = bf_current_user();
		return $user ? bf_user_public( $user ) : null;
	}

	/* ------------------------------------------------------------ مسیریابی */

	/**
	 * @param string $path مسیر کامل، مثل /api/me.
	 */
	public static function handle( $path ) {
		global $wpdb;

		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET';
		$path   = '/' . ltrim( $path, '/' );
		$user   = bf_current_user();
		$coach  = bf_is_coach( $user );

		$need_user = function () use ( $user ) {
			if ( ! $user ) {
				self::fail( 401, 'برای این کار باید وارد حساب شوی' );
			}
		};
		$need_coach = function () use ( $user, $coach ) {
			if ( ! $user ) {
				self::fail( 401, 'برای این کار باید وارد حساب شوی' );
			}
			if ( ! $coach ) {
				self::fail( 403, 'دسترسی به این بخش فقط برای مربی است' );
			}
		};

		try {
			self::dispatch( $path, $method, $user, $coach, $need_user, $need_coach );
		} catch ( RuntimeException $e ) {
			self::fail( 400, $e->getMessage() );
		}
	}

	private static function dispatch( $path, $method, $user, $coach, $need_user, $need_coach ) {
		global $wpdb;

		/* ---------------- ثبت‌نام ---------------- */
		if ( '/api/auth/register' === $path && 'POST' === $method ) {
			if ( ! self::same_origin() ) {
				self::fail( 403, 'درخواست معتبر نیست' );
			}
			$ip = bf_client_ip();
			if ( bf_too_many_attempts( $ip ) ) {
				self::fail( 429, 'تلاش زیاد. چند دقیقه بعد دوباره امتحان کن.' );
			}

			$body     = self::json_body();
			$phone    = bf_normalize_phone( isset( $body['phone'] ) ? $body['phone'] : '' );
			$name     = bf_normalize_name( isset( $body['name'] ) ? $body['name'] : '' );
			$password = isset( $body['password'] ) ? $body['password'] : '';

			if ( ! $phone ) {
				self::fail( 400, 'شمارهٔ موبایل را درست وارد کن (مثل ۰۹۱۲۳۴۵۶۷۸۹)' );
			}
			if ( ! $name ) {
				self::fail( 400, 'نام را وارد کن' );
			}
			if ( ! bf_valid_password( $password ) ) {
				self::fail( 400, 'رمز باید حداقل ۸ کاراکتر باشد' );
			}
			if ( bf_user_by_phone( $phone ) || get_user_by( 'login', $phone ) ) {
				self::fail( 409, 'این شماره قبلاً ثبت‌نام کرده است' );
			}

			$new_id = wp_insert_user(
				array(
					'user_login'   => $phone,
					'user_pass'    => $password,
					'display_name' => $name,
					'nickname'     => $name,
					'role'         => 'bf_student',
				)
			);
			if ( is_wp_error( $new_id ) ) {
				self::fail( 500, 'ساخت حساب ممکن نشد' );
			}
			update_user_meta( $new_id, 'bf_phone', $phone );

			bf_note_attempt( $ip, true );
			self::log_in( $phone, $password, $new_id );

			$created = get_user_by( 'id', $new_id );
			self::ok(
				array(
					'user'     => bf_user_public( $created ),
					'redirect' => '/account.html',
				)
			);
		}

		/* ---------------- ورود ---------------- */
		if ( '/api/auth/login' === $path && 'POST' === $method ) {
			if ( ! self::same_origin() ) {
				self::fail( 403, 'درخواست معتبر نیست' );
			}
			$ip = bf_client_ip();
			if ( bf_too_many_attempts( $ip ) ) {
				self::fail( 429, 'تلاش زیاد. چند دقیقه بعد دوباره امتحان کن.' );
			}

			$body     = self::json_body();
			$phone    = bf_normalize_phone( isset( $body['phone'] ) ? $body['phone'] : '' );
			$password = isset( $body['password'] ) ? (string) $body['password'] : '';

			if ( ! $phone ) {
				self::fail( 400, 'شمارهٔ موبایل را درست وارد کن' );
			}

			$found = get_user_by( 'login', $phone );
			if ( ! $found ) {
				$found = bf_user_by_phone( $phone );
			}

			if ( ! $found || ! wp_check_password( $password, $found->user_pass, $found->ID ) ) {
				bf_note_attempt( $ip, false );
				self::fail( 401, 'شماره یا رمز اشتباه است' );
			}

			bf_note_attempt( $ip, true );
			self::log_in( $found->user_login, $password, $found->ID );

			self::ok(
				array(
					'user'     => bf_user_public( $found ),
					'redirect' => bf_is_coach( $found ) ? '/admin.html' : '/account.html',
				)
			);
		}

		/* ---------------- خروج ---------------- */
		if ( '/api/auth/logout' === $path && 'POST' === $method ) {
			if ( ! self::same_origin() ) {
				self::fail( 403, 'درخواست معتبر نیست' );
			}
			wp_logout();
			self::ok();
		}

		/* ---------------- من ---------------- */
		if ( '/api/me' === $path && 'GET' === $method ) {
			self::json( 200, array( 'ok' => true, 'user' => self::public_user() ) );
		}

		/* ---------------- کاتالوگ ---------------- */
		if ( '/api/plans' === $path && 'GET' === $method ) {
			self::ok(
				array(
					'plans'        => array_values( self::plans() ),
					'pricing_mode' => bf_pricing_mode(),
					'exchange'     => bf_exchange_public(),
					'zarinpal' => false,
					'card'     => (string) apply_filters( 'bf_card_number', '' ),
				)
			);
		}

		/* ---------------- خرید ---------------- */
		if ( '/api/orders' === $path && 'POST' === $method ) {
			if ( ! self::same_origin() ) {
				self::fail( 403, 'درخواست معتبر نیست' );
			}
			$need_user();
			$body = self::json_body();
			$plan = self::plan_by_key( isset( $body['planKey'] ) ? (string) $body['planKey'] : '' );
			if ( ! $plan ) {
				self::fail( 400, 'برنامهٔ انتخابی پیدا نشد' );
			}

			$manual       = bf_manual_pricing_enabled();
			$rate         = $manual ? null : bf_usd_toman_rate();
			$amount_toman = bf_plan_price_toman( $plan['key'], $plan['price_usd'] );
			// در حالت دستی مبلغ دلاری برای سفارش معنا ندارد؛ فقط اگر هنوز
			// قیمت تومانی ثبت نشده باشد، دلار پایه را به‌عنوان پشتیبان نگه
			// می‌داریم تا مبلغ سفارش صفر نشود.
			$amount_usd = $manual && $amount_toman > 0 ? 0 : (float) $plan['price_usd'];

			$wpdb->insert(
				bf_table( 'orders' ),
				array(
					'user_id'        => $user->ID,
					'plan_key'       => $plan['key'],
					'amount_usd'     => $amount_usd,
					'amount_toman'   => $amount_toman,
					'usd_rate_toman' => $rate ? (int) $rate['rate'] : 0,
					'status'         => 'pending',
					'method'     => 'manual',
					'note'       => mb_substr( isset( $body['note'] ) ? (string) $body['note'] : '', 0, 300 ),
					'created_at' => gmdate( 'c' ),
				),
				array( '%d', '%s', '%f', '%d', '%d', '%s', '%s', '%s', '%s' )
			);

			self::ok(
				array(
					'orderId'       => (int) $wpdb->insert_id,
					'method'        => 'manual',
					'amountUsd'     => $amount_usd,
					'amountToman'   => $amount_toman,
					'exchangeRate'  => $rate ? (int) $rate['rate'] : 0,
					'pricingMode'   => bf_pricing_mode(),
				)
			);
		}

		/* ---------------- آپلود رسید ---------------- */
		if ( preg_match( '#^/api/orders/(\d+)/receipt$#', $path, $m ) && 'POST' === $method ) {
			if ( ! self::same_origin() ) {
				self::fail( 403, 'درخواست معتبر نیست' );
			}
			$need_user();

			$order_id = (int) $m[1];
			$order    = $wpdb->get_row(
				$wpdb->prepare( 'SELECT * FROM ' . bf_table( 'orders' ) . ' WHERE id = %d', $order_id ), // phpcs:ignore
				ARRAY_A
			);
			if ( ! $order || (int) $order['user_id'] !== (int) $user->ID ) {
				self::fail( 404, 'سفارش پیدا نشد' );
			}
			if ( 'pending' !== $order['status'] ) {
				self::fail( 400, 'این سفارش دیگر قابل تغییر نیست' );
			}

			if ( empty( $_FILES['receipt'] ) ) {
				self::fail( 400, 'تصویر رسید را انتخاب کن' );
			}
			$files = bf_normalize_files( $_FILES['receipt'] );
			$saved = bf_save_uploads( $files, 1 );
			if ( ! $saved ) {
				self::fail( 400, 'فایل پذیرفته نشد' );
			}

			$wpdb->update(
				bf_table( 'orders' ),
				array(
					'receipt_file' => $saved[0]['stored_name'],
					'status'       => 'awaiting_confirm',
				),
				array(
					'id'      => $order_id,
					'user_id' => $user->ID,
				),
				array( '%s', '%s' ),
				array( '%d', '%d' )
			);

			self::ok( array( 'status' => 'awaiting_confirm' ) );
		}

		/* ---------------- برنامه‌های من ---------------- */
		if ( '/api/me/programs' === $path && 'GET' === $method ) {
			$need_user();
			self::ok(
				array(
					'programs' => self::programs_for_user( $user->ID ),
					'orders'   => self::orders_for_user( $user->ID ),
				)
			);
		}

		/* ---------------- مشاهدهٔ یک برنامه ---------------- */
		if ( preg_match( '#^/api/programs/(\d+)$#', $path, $m ) && 'GET' === $method ) {
			$need_user();
			$program = self::program_full( (int) $m[1], (int) $user->ID, $coach );
			if ( ! $program ) {
				self::fail( 404, 'برنامه پیدا نشد' );
			}
			self::ok( array( 'program' => $program ) );
		}

		/* ---------------- دانلود فایل برنامه ---------------- */
		if ( preg_match( '#^/api/files/([\w.-]+)$#', $path, $m ) && 'GET' === $method ) {
			// این نشانی در تب تازهٔ مرورگر باز می‌شود (دکمهٔ «دیدن رسید»)، پس
			// خطاها به‌جای JSON خام با یک صفحهٔ ساده و خوانا نشان داده می‌شوند.
			if ( ! $user ) {
				self::file_error( 401, 'برای دیدن این فایل باید وارد حساب شوی.' );
			}
			self::send_file( rawurldecode( $m[1] ), (int) $user->ID, $coach );
		}

		/* ---------------- بازگشت از درگاه (فعلاً غیرفعال) ---------------- */
		if ( '/api/payments/zarinpal/callback' === $path ) {
			wp_safe_redirect( home_url( '/account.html?pay=failed' ) );
			exit;
		}

		/* ======================= مسیرهای مربی ======================= */

		if ( '/api/admin/students' === $path && 'GET' === $method ) {
			$need_coach();
			self::ok( array( 'students' => self::students_list() ) );
		}

		if ( '/api/admin/orders' === $path && 'GET' === $method ) {
			$need_coach();
			self::ok(
				array(
					'orders'   => self::all_orders(),
					'awaiting' => self::awaiting_count(),
				)
			);
		}

		if ( preg_match( '#^/api/admin/orders/(\d+)/status$#', $path, $m ) && 'POST' === $method ) {
			if ( ! self::same_origin() ) {
				self::fail( 403, 'درخواست معتبر نیست' );
			}
			$need_coach();

			$body   = self::json_body();
			$status = isset( $body['status'] ) && in_array( $body['status'], array( 'paid', 'canceled' ), true ) ? $body['status'] : null;
			if ( ! $status ) {
				self::fail( 400, 'وضعیت نامعتبر است' );
			}

			$order_id = (int) $m[1];
			$exists   = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . bf_table( 'orders' ) . ' WHERE id = %d', $order_id ) ); // phpcs:ignore
			if ( ! $exists ) {
				self::fail( 404, 'سفارش پیدا نشد' );
			}

			$fields  = array( 'status' => $status );
			$formats = array( '%s' );
			if ( 'paid' === $status ) {
				$fields['paid_at'] = gmdate( 'c' );
				$formats[]         = '%s';
			}
			$wpdb->update( bf_table( 'orders' ), $fields, array( 'id' => $order_id ), $formats, array( '%d' ) );

			self::ok();
		}

		/* ---------------- ساخت برنامه (چندبخشی) ---------------- */
		if ( '/api/admin/programs' === $path && 'POST' === $method ) {
			if ( ! self::same_origin() ) {
				self::fail( 403, 'درخواست معتبر نیست' );
			}
			$need_coach();
			self::create_program();
		}

		if ( preg_match( '#^/api/admin/students/(\d+)/programs$#', $path, $m ) && 'GET' === $method ) {
			$need_coach();
			$student_id = (int) $m[1];
			self::ok(
				array(
					'programs' => self::programs_for_user( $student_id ),
					'orders'   => self::orders_for_user( $student_id ),
				)
			);
		}

		if ( '/api/admin/summary' === $path && 'GET' === $method ) {
			$need_coach();
			self::ok(
				array(
					'students' => self::students_count(),
					'awaiting' => self::awaiting_count(),
				)
			);
		}

		self::fail( 404, 'این مسیر وجود ندارد' );
	}

	/* ------------------------------------------------------------ کمکی‌ها */

	/** ورود واقعی با کوکی وردپرس */
	private static function log_in( $login, $password, $user_id ) {
		if ( $user_id ) {
			wp_set_current_user( $user_id );
		}
		wp_set_auth_cookie( $user_id, true, is_ssl() );
	}

	/** شمارش شاگردها */
	private static function students_count() {
		$count = count_users();
		$roles = isset( $count['avail_roles'] ) ? $count['avail_roles'] : array();
		return isset( $roles['bf_student'] ) ? (int) $roles['bf_student'] : 0;
	}

	/** فهرست شاگردها برای پنل مربی */
	private static function students_list() {
		global $wpdb;

		$users = get_users(
			array(
				'role'    => 'bf_student',
				'orderby' => 'registered',
				'order'   => 'DESC',
				'number'  => 500,
			)
		);

		$programs = bf_table( 'programs' );
		$orders   = bf_table( 'orders' );
		$out      = array();

		foreach ( $users as $u ) {
			$program_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$programs} WHERE user_id = %d", $u->ID ) ); // phpcs:ignore
			$last_program  = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(created_at) FROM {$programs} WHERE user_id = %d", $u->ID ) ); // phpcs:ignore
			$open_orders   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$orders} WHERE user_id = %d AND status IN ('pending','awaiting_confirm')", $u->ID ) ); // phpcs:ignore

			$registered = $u->user_registered ? gmdate( 'c', strtotime( $u->user_registered . ' UTC' ) ) : '';

			$out[] = array(
				'id'              => (int) $u->ID,
				'phone'           => (string) get_user_meta( $u->ID, 'bf_phone', true ),
				'name'            => (string) $u->display_name,
				'created_at'      => $registered,
				'program_count'   => $program_count,
				'last_program_at' => $last_program ? (string) $last_program : null,
				'open_orders'     => $open_orders,
			);
		}

		return $out;
	}

	/** برنامه‌های یک کاربر (فهرست خلاصه) */
	private static function programs_for_user( $user_id ) {
		global $wpdb;

		$programs = bf_table( 'programs' );
		$days     = bf_table( 'program_days' );
		$files    = bf_table( 'program_files' );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.*,
				        (SELECT COUNT(*) FROM {$days} d WHERE d.program_id = p.id) AS day_count,
				        (SELECT COUNT(*) FROM {$files} f WHERE f.program_id = p.id) AS file_count
				   FROM {$programs} p
				  WHERE p.user_id = %d
				  ORDER BY p.created_at DESC, p.id DESC", // phpcs:ignore
				$user_id
			),
			ARRAY_A
		);

		$out = array();
		foreach ( (array) $rows as $row ) {
			$out[] = array(
				'id'         => (int) $row['id'],
				'user_id'    => (int) $row['user_id'],
				'order_id'   => null === $row['order_id'] ? null : (int) $row['order_id'],
				'plan_key'   => $row['plan_key'],
				'title'      => (string) $row['title'],
				'summary'    => (string) $row['summary'],
				'start_date' => $row['start_date'],
				'created_at' => (string) $row['created_at'],
				'day_count'  => (int) $row['day_count'],
				'file_count' => (int) $row['file_count'],
			);
		}
		return $out;
	}

	/** سفارش‌های یک کاربر */
	private static function orders_for_user( $user_id ) {
		global $wpdb;

		$orders = bf_table( 'orders' );
		$rows   = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$orders} WHERE user_id = %d ORDER BY created_at DESC", $user_id ), // phpcs:ignore
			ARRAY_A
		);

		$out = array();
		foreach ( (array) $rows as $row ) {
			$plan   = self::plan_by_key( $row['plan_key'] );
			$out[]  = array(
				'id'           => (int) $row['id'],
				'user_id'      => (int) $row['user_id'],
				'plan_key'     => $row['plan_key'],
				'amount_usd'   => (float) $row['amount_usd'],
				'amount_toman' => isset( $row['amount_toman'] ) ? (int) $row['amount_toman'] : 0,
				'exchange_rate' => isset( $row['usd_rate_toman'] ) ? (int) $row['usd_rate_toman'] : 0,
				'status'       => $row['status'],
				'method'       => $row['method'],
				'receipt_file' => $row['receipt_file'],
				'note'         => (string) $row['note'],
				'created_at'   => (string) $row['created_at'],
				'paid_at'      => $row['paid_at'],
				'plan_title'   => $plan ? $plan['title'] : $row['plan_key'],
				'period'       => $plan ? $plan['period'] : 'once',
			);
		}
		return $out;
	}

	/** همهٔ سفارش‌ها برای مربی — اول منتظر تایید، آخر لغو‌شده */
	private static function all_orders() {
		global $wpdb;

		$orders = bf_table( 'orders' );
		$rows   = $wpdb->get_results(
			"SELECT * FROM {$orders}
			  ORDER BY CASE status
			             WHEN 'awaiting_confirm' THEN 0
			             WHEN 'pending' THEN 1
			             WHEN 'paid' THEN 2
			             ELSE 3
			           END, created_at DESC
			  LIMIT 200", // phpcs:ignore
			ARRAY_A
		);

		$out = array();
		foreach ( (array) $rows as $row ) {
			$plan_user = get_user_by( 'id', (int) $row['user_id'] );
			$plan      = self::plan_by_key( $row['plan_key'] );

			$out[] = array(
				'id'            => (int) $row['id'],
				'user_id'       => (int) $row['user_id'],
				'plan_key'      => $row['plan_key'],
				'amount_usd'    => (float) $row['amount_usd'],
				'amount_toman'  => isset( $row['amount_toman'] ) ? (int) $row['amount_toman'] : 0,
				'exchange_rate' => isset( $row['usd_rate_toman'] ) ? (int) $row['usd_rate_toman'] : 0,
				'status'        => $row['status'],
				'method'        => $row['method'],
				'receipt_file'  => $row['receipt_file'],
				'note'          => (string) $row['note'],
				'created_at'    => (string) $row['created_at'],
				'paid_at'       => $row['paid_at'],
				'plan_title'    => $plan ? $plan['title'] : $row['plan_key'],
				'student_name'  => $plan_user ? $plan_user->display_name : '',
				'student_phone' => $plan_user ? (string) get_user_meta( $plan_user->ID, 'bf_phone', true ) : '',
			);
		}
		return $out;
	}

	/** چند سفارش منتظر تایید */
	private static function awaiting_count() {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . bf_table( 'orders' ) . " WHERE status IN ('pending','awaiting_confirm')" ); // phpcs:ignore
	}

	/**
	 * برنامهٔ کامل با روزها، حرکت‌ها و فایل‌ها.
	 *
	 * @param int  $id       شناسهٔ برنامه.
	 * @param int  $user_id  کاربر فعلی.
	 * @param bool $is_coach مربی همه را می‌بیند.
	 * @return array|null
	 */
	private static function program_full( $id, $user_id, $is_coach ) {
		global $wpdb;

		$programs = bf_table( 'programs' );
		$days     = bf_table( 'program_days' );
		$items    = bf_table( 'program_items' );
		$files    = bf_table( 'program_files' );

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$programs} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore
		if ( ! $row ) {
			return null;
		}
		if ( ! $is_coach && (int) $row['user_id'] !== (int) $user_id ) {
			return null;
		}

		$day_rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$days} WHERE program_id = %d ORDER BY sort, id", $id ), ARRAY_A ); // phpcs:ignore
		$day_list = array();
		foreach ( (array) $day_rows as $d ) {
			$item_rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$items} WHERE day_id = %d ORDER BY sort, id", (int) $d['id'] ), ARRAY_A ); // phpcs:ignore
			$item_list = array();
			foreach ( (array) $item_rows as $it ) {
				$item_list[] = array(
					'id'       => (int) $it['id'],
					'day_id'   => (int) $it['day_id'],
					'exercise' => (string) $it['exercise'],
					'sets'     => (string) $it['sets'],
					'reps'     => (string) $it['reps'],
					'rest'     => (string) $it['rest'],
					'note'     => (string) $it['note'],
					'sort'     => (int) $it['sort'],
				);
			}
			$day_list[] = array(
				'id'         => (int) $d['id'],
				'program_id' => (int) $d['program_id'],
				'label'      => (string) $d['label'],
				'note'       => (string) $d['note'],
				'sort'       => (int) $d['sort'],
				'items'      => $item_list,
			);
		}

		$file_rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$files} WHERE program_id = %d ORDER BY id", $id ), ARRAY_A ); // phpcs:ignore
		$file_list = array();
		foreach ( (array) $file_rows as $f ) {
			$file_list[] = array(
				'id'            => (int) $f['id'],
				'stored_name'   => (string) $f['stored_name'],
				'original_name' => (string) $f['original_name'],
				'mime'          => (string) $f['mime'],
				'size'          => (int) $f['size'],
			);
		}

		$plan = self::plan_by_key( $row['plan_key'] );

		return array(
			'id'         => (int) $row['id'],
			'user_id'    => (int) $row['user_id'],
			'order_id'   => null === $row['order_id'] ? null : (int) $row['order_id'],
			'plan_key'   => $row['plan_key'],
			'title'      => (string) $row['title'],
			'summary'    => (string) $row['summary'],
			'start_date' => $row['start_date'],
			'created_at' => (string) $row['created_at'],
			'plan_title' => $plan ? $plan['title'] : $row['plan_key'],
			'days'       => $day_list,
			'files'      => $file_list,
		);
	}

	/**
	 * ساخت برنامه — همان اعتبارسنجی cleanProgramPayload در Node.
	 */
	private static function create_program() {
		global $wpdb;

		if ( empty( $_POST['data'] ) ) {
			self::fail( 400, 'دادهٔ برنامه معتبر نیست' );
		}
		$payload = json_decode( wp_unslash( $_POST['data'] ), true ); // phpcs:ignore
		if ( ! is_array( $payload ) ) {
			self::fail( 400, 'دادهٔ برنامه معتبر نیست' );
		}

		$student_id = isset( $payload['userId'] ) ? (int) $payload['userId'] : 0;
		$student    = get_user_by( 'id', $student_id );
		if ( ! $student || ! in_array( 'bf_student', (array) $student->roles, true ) ) {
			self::fail( 400, 'شاگرد پیدا نشد' );
		}

		$title = isset( $payload['title'] ) ? trim( (string) $payload['title'] ) : '';
		if ( '' === $title ) {
			self::fail( 400, 'عنوان برنامه را بنویس' );
		}

		$order_id = ! empty( $payload['orderId'] ) ? (int) $payload['orderId'] : null;
		if ( $order_id ) {
			$order = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . bf_table( 'orders' ) . ' WHERE id = %d', $order_id ), ARRAY_A ); // phpcs:ignore
			if ( ! $order || (int) $order['user_id'] !== $student_id ) {
				self::fail( 400, 'سفارش با این شاگرد هم‌خوان نیست' );
			}
			if ( 'paid' !== $order['status'] ) {
				self::fail( 400, 'این سفارش هنوز پرداخت نشده است' );
			}
		}

		$files_field = array();
		foreach ( array_keys( $_FILES ) as $key ) {
			if ( 0 === strpos( $key, 'file' ) ) {
				$files_field = array_merge( $files_field, bf_normalize_files( $_FILES[ $key ] ) );
			}
		}
		$saved = bf_save_uploads( $files_field, 6 );

		$days = self::clean_days( isset( $payload['days'] ) ? $payload['days'] : array() );

		$wpdb->query( 'START TRANSACTION' );
		try {
			$wpdb->insert(
				bf_table( 'programs' ),
				array(
					'user_id'    => $student_id,
					'order_id'   => $order_id,
					'plan_key'   => ! empty( $payload['plan_key'] ) ? (string) $payload['plan_key'] : ( ! empty( $payload['planKey'] ) ? (string) $payload['planKey'] : null ),
					'title'      => mb_substr( $title, 0, 120 ),
					'summary'    => mb_substr( isset( $payload['summary'] ) ? (string) $payload['summary'] : '', 0, 500 ),
					'start_date' => ! empty( $payload['startDate'] ) ? mb_substr( (string) $payload['startDate'], 0, 10 ) : null,
					'created_at' => gmdate( 'c' ),
				),
				array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
			);
			$program_id = (int) $wpdb->insert_id;
			if ( ! $program_id ) {
				throw new RuntimeException( 'ثبت برنامه ممکن نشد' );
			}

			foreach ( $days as $sort => $day ) {
				$wpdb->insert(
					bf_table( 'program_days' ),
					array(
						'program_id' => $program_id,
						'label'      => $day['label'],
						'note'       => $day['note'],
						'sort'       => $sort,
					),
					array( '%d', '%s', '%s', '%d' )
				);
				$day_id = (int) $wpdb->insert_id;

				foreach ( $day['items'] as $i => $item ) {
					$wpdb->insert(
						bf_table( 'program_items' ),
						array(
							'day_id'   => $day_id,
							'exercise' => $item['exercise'],
							'sets'     => $item['sets'],
							'reps'     => $item['reps'],
							'rest'     => $item['rest'],
							'note'     => $item['note'],
							'sort'     => $i,
						),
						array( '%d', '%s', '%s', '%s', '%s', '%s', '%d' )
					);
				}
			}

			foreach ( $saved as $f ) {
				$wpdb->insert(
					bf_table( 'program_files' ),
					array(
						'program_id'    => $program_id,
						'stored_name'   => $f['stored_name'],
						'original_name' => $f['original_name'],
						'mime'          => $f['mime'],
						'size'          => $f['size'],
						'created_at'    => gmdate( 'c' ),
					),
					array( '%d', '%s', '%s', '%s', '%d', '%s' )
				);
			}

			$wpdb->query( 'COMMIT' );
		} catch ( Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			self::fail( 500, 'ثبت برنامه ممکن نشد' );
		}

		self::ok( array( 'programId' => $program_id ) );
	}

	/**
	 * اعتبارسنجی روزها و حرکت‌ها — همان cleanProgramPayload.
	 *
	 * @param mixed $raw ورودی خام.
	 * @return array
	 */
	private static function clean_days( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$out = array();
		foreach ( array_slice( $raw, 0, 14 ) as $d ) {
			if ( ! is_array( $d ) ) {
				continue;
			}
			$items = array();
			foreach ( array_slice( isset( $d['items'] ) && is_array( $d['items'] ) ? $d['items'] : array(), 0, 40 ) as $it ) {
				if ( ! is_array( $it ) ) {
					continue;
				}
				$exercise = trim( (string) ( isset( $it['exercise'] ) ? $it['exercise'] : '' ) );
				if ( '' === $exercise ) {
					continue;
				}
				$items[] = array(
					'exercise' => mb_substr( $exercise, 0, 120 ),
					'sets'     => mb_substr( trim( (string) ( isset( $it['sets'] ) ? $it['sets'] : '' ) ), 0, 20 ),
					'reps'     => mb_substr( trim( (string) ( isset( $it['reps'] ) ? $it['reps'] : '' ) ), 0, 30 ),
					'rest'     => mb_substr( trim( (string) ( isset( $it['rest'] ) ? $it['rest'] : '' ) ), 0, 30 ),
					'note'     => mb_substr( trim( (string) ( isset( $it['note'] ) ? $it['note'] : '' ) ), 0, 200 ),
				);
			}
			$label = mb_substr( trim( (string) ( isset( $d['label'] ) ? $d['label'] : '' ) ), 0, 80 );
			if ( '' === $label && ! $items ) {
				continue;
			}
			$out[] = array(
				'label' => '' !== $label ? $label : 'روز بدون عنوان',
				'note'  => mb_substr( trim( (string) ( isset( $d['note'] ) ? $d['note'] : '' ) ), 0, 300 ),
				'items' => $items,
			);
		}
		return $out;
	}

	/**
	 * تحویل فایل با بررسی مالکیت.
	 *
	 * @param string $stored_name نام ذخیره‌شده.
	 * @param int    $user_id     کاربر فعلی.
	 * @param bool   $is_coach    مربی همه را می‌بیند.
	 */
	private static function send_file( $stored_name, $user_id, $is_coach ) {
		global $wpdb;

		$stored_name = basename( $stored_name );
		$path        = trailingslashit( bf_store_dir() ) . $stored_name;

		// ۱) پیوست برنامه — نام اصلی و نوعش را از جدول فایل‌ها می‌دانیم.
		$files = bf_table( 'program_files' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$files} WHERE stored_name = %s", $stored_name ), ARRAY_A ); // phpcs:ignore

		if ( $row ) {
			$program = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . bf_table( 'programs' ) . ' WHERE id = %d', (int) $row['program_id'] ), ARRAY_A ); // phpcs:ignore
			if ( ! $program || ( ! $is_coach && (int) $program['user_id'] !== (int) $user_id ) ) {
				self::file_error( 403, 'این فایل به حساب تو مربوط نیست.' );
			}
			if ( ! is_file( $path ) ) {
				self::file_error( 404, 'فایل روی سرور نیست. با پشتیبانی هاست تماس بگیر.' );
			}

			self::stream( $path, $row['mime'], $row['original_name'], 'attachment' );
		}

		// ۲) رسید کارت‌به‌کارت — روی خود سفارش ذخیره می‌شود، نه در جدول فایل‌های برنامه.
		$orders  = bf_table( 'orders' );
		$order   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$orders} WHERE receipt_file = %s", $stored_name ), ARRAY_A ); // phpcs:ignore
		if ( ! $order ) {
			self::file_error( 404, 'این فایل پیدا نشد. ممکن است حذف شده باشد یا نشانی آن کامل کپی نشده باشد.' );
		}
		if ( ! $is_coach && (int) $order['user_id'] !== (int) $user_id ) {
			self::file_error( 403, 'این فایل به حساب تو مربوط نیست.' );
		}
		if ( ! is_file( $path ) ) {
			self::file_error( 404, 'فایل روی سرور نیست. با پشتیبانی هاست تماس بگیر.' );
		}

		$ext  = strtolower( pathinfo( $stored_name, PATHINFO_EXTENSION ) );
		$mime = array_search( $ext, bf_allowed_types(), true );
		self::stream(
			$path,
			$mime ? $mime : 'application/octet-stream',
			'رسید-سفارش-' . (int) $order['id'] . '.' . $ext,
			'inline' // «دیدن رسید» باید تصویر را نشان بدهد، نه اینکه دانلودش کند
		);
	}

	/**
	 * خطای مسیر فایل، به شکل خوانا برای چشم آدم.
	 *
	 * دکمهٔ «دیدن رسید» نشانی فایل را در تب تازه باز می‌کند. آنجا JSON خام
	 * («{"ok":false,...}») بدترین شکل پیام است؛ به‌جایش یک برگهٔ ساده با
	 * عنوان روشن، متن فارسی و دکمهٔ بازگشت می‌فرستیم. اگر درخواست از یک
	 * اسکریپت بیاید (Accept بدون text/html)، همان JSON قبلی برمی‌گردد تا
	 * رفتار برنامه‌ای عوض نشود.
	 *
	 * @param int    $status  کد وضعیت.
	 * @param string $message پیام فارسی.
	 */
	private static function file_error( $status, $message ) {
		$accept = isset( $_SERVER['HTTP_ACCEPT'] ) ? (string) $_SERVER['HTTP_ACCEPT'] : '';
		if ( false === strpos( $accept, 'text/html' ) ) {
			self::fail( $status, $message );
		}

		status_header( $status );
		nocache_headers();
		header( 'Content-Type: text/html; charset=utf-8' );
		header( 'X-Robots-Tag: noindex, nofollow' );

		$back = esc_url( bf_account_url() );
		$home = esc_url( home_url( '/' ) );
		$safe = esc_html( $message );

		$css = 'body{margin:0;min-height:100vh;display:grid;place-items:center;'
			. 'background:#f7f4ee;color:#14234b;font-family:Tahoma,system-ui,sans-serif;padding:24px}'
			. '.box{max-width:26rem;background:#fff;border:1px solid #e3dbcd;border-radius:16px;'
			. 'padding:28px;text-align:center;box-shadow:0 12px 32px rgba(20,35,75,.06)}'
			. '.icon{width:44px;height:44px;border-radius:50%;background:#f3efe4;color:#8a6d2f;'
			. 'display:grid;place-items:center;margin:0 auto 14px;font-size:22px}'
			. 'p{margin:0 0 20px;line-height:2;font-size:.95rem;color:#4a5568}'
			. '.links{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}'
			. 'a{display:inline-block;padding:10px 20px;border-radius:999px;text-decoration:none;'
			. 'font-size:.9rem;font-weight:600;border:1px solid #14234b}'
			. '.main{background:#14234b;color:#f7f4ee}.alt{color:#14234b}';

		echo "<!DOCTYPE html>\n<html lang=\"fa\" dir=\"rtl\">\n<head>\n"
			. "<meta charset=\"utf-8\" />\n"
			. "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" />\n"
			. "<meta name=\"robots\" content=\"noindex, nofollow\" />\n"
			. '<title>' . $safe . "</title>\n<style>" . $css . "</style>\n</head>\n<body>\n"
			. "<div class=\"box\">\n"
			. "<div class=\"icon\">!</div>\n"
			. '<p>' . $safe . "</p>\n"
			. "<div class=\"links\">\n"
			. '<a class="main" href="' . $back . '">برگشت به حساب من</a>' . "\n"
			. '<a class="alt" href="' . $home . '">صفحهٔ اصلی</a>' . "\n"
			. "</div>\n</div>\n</body>\n</html>";
		exit;
	}

	/**
	 * فرستادن فایل به مرورگر و پایان درخواست.
	 *
	 * @param string $path        مسیر کامل فایل.
	 * @param string $mime        نوع محتوا.
	 * @param string $name        نامی که مرورگر می‌بیند.
	 * @param string $disposition attachment یا inline.
	 */
	private static function stream( $path, $mime, $name, $disposition ) {
		nocache_headers();
		header( 'Content-Type: ' . ( $mime ? $mime : 'application/octet-stream' ) );
		header( 'Content-Length: ' . filesize( $path ) );
		header( 'Content-Disposition: ' . $disposition . "; filename*=UTF-8''" . rawurlencode( $name ? $name : 'file' ) );
		header( 'Cache-Control: private, no-store' );
		header( 'X-Content-Type-Options: nosniff' );
		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}
}
