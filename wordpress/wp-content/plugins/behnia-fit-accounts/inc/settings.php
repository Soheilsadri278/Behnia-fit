<?php
/**
 * صفحهٔ تنظیمات در پیشخوان.
 *
 * سه چیز که تا امروز در فایل .env نسخهٔ Node بود و اینجا در پیشخوان
 * وردپرس تنظیم می‌شود: حساب مربی، شمارهٔ کارت، و اعتماد به پروکسی.
 *
 * @package behnia-fit-accounts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'admin_menu',
	static function () {
		add_options_page(
			'بهنیا فیت',
			'بهنیا فیت',
			'manage_options',
			'behnia-fit',
			'bf_settings_page'
		);
	}
);

/** ذخیرهٔ تنظیمات */
add_action(
	'admin_post_bf_save_settings',
	static function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی نداری.' );
		}
		check_admin_referer( 'bf_save_settings' );

		$card = isset( $_POST['bf_card_number'] ) ? sanitize_text_field( wp_unslash( $_POST['bf_card_number'] ) ) : '';
		update_option( 'bf_card_number', $card );
		update_option( 'bf_trust_proxy', isset( $_POST['bf_trust_proxy'] ) ? 1 : 0 );

		// قیمت‌ها: هر کلید فقط اگر عدد معتبر باشد ذخیره می‌شود، وگرنه پیش‌فرض
		// می‌ماند. عدد صفر یعنی «همان پیش‌فرض».
		$prices = array();
		$posted = isset( $_POST['bf_price'] ) && is_array( $_POST['bf_price'] ) ? wp_unslash( $_POST['bf_price'] ) : array(); // phpcs:ignore
		foreach ( bf_plan_defaults() as $plan ) {
			$key = $plan['key'];
			if ( ! isset( $posted[ $key ] ) ) {
				continue;
			}
			$raw = trim( (string) $posted[ $key ] );
			if ( false !== strpos( $raw, '-' ) ) {
				continue; // عدد منفی معنی ندارد
			}
			$value = bf_price_text_from_fa( $raw );
			if ( '' === $raw || $value <= 0 ) {
				continue; // خالی یا نامعتبر → پیش‌فرض
			}
			$prices[ $key ] = min( round( $value, 2 ), 1000000 );
		}
		if ( $prices ) {
			update_option( 'bf_plan_prices', $prices );
		} else {
			delete_option( 'bf_plan_prices' );
		}

		$notice = 'saved';

		$phone = isset( $_POST['bf_coach_phone'] ) ? bf_normalize_phone( wp_unslash( $_POST['bf_coach_phone'] ) ) : null;
		$pass  = isset( $_POST['bf_coach_password'] ) ? (string) wp_unslash( $_POST['bf_coach_password'] ) : '';
		$name  = isset( $_POST['bf_coach_name'] ) ? bf_normalize_name( wp_unslash( $_POST['bf_coach_name'] ) ) : null;

		if ( $phone ) {
			$existing = bf_user_by_phone( $phone );
			if ( ! $existing ) {
				$existing = get_user_by( 'login', $phone );
			}

			if ( $existing instanceof WP_User ) {
				$existing->set_role( 'bf_coach' );
				update_user_meta( $existing->ID, 'bf_phone', $phone );
				if ( $name ) {
					wp_update_user(
						array(
							'ID'           => $existing->ID,
							'display_name' => $name,
						)
					);
				}
				if ( $pass ) {
					wp_set_password( $pass, $existing->ID );
				}
			} else {
				if ( ! $pass || ! bf_valid_password( $pass ) ) {
					wp_safe_redirect( add_query_arg( 'bf_msg', 'need_password', admin_url( 'options-general.php?page=behnia-fit' ) ) );
					exit;
				}
				$new_id = wp_insert_user(
					array(
						'user_login'   => $phone,
						'user_pass'    => $pass,
						'display_name' => $name ? $name : 'بهنیا',
						'role'         => 'bf_coach',
					)
				);
				if ( ! is_wp_error( $new_id ) ) {
					update_user_meta( $new_id, 'bf_phone', $phone );
				}
			}
			$notice = 'coach';
		}

		wp_safe_redirect( add_query_arg( 'bf_msg', $notice, admin_url( 'options-general.php?page=behnia-fit' ) ) );
		exit;
	}
);

/** ظاهر صفحه */
function bf_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$coach = get_users(
		array(
			'role'    => 'bf_coach',
			'number'  => 1,
			'orderby' => 'ID',
		)
	);
	$coach = $coach ? $coach[0] : null;

	$msg = isset( $_GET['bf_msg'] ) ? sanitize_key( wp_unslash( $_GET['bf_msg'] ) ) : ''; // phpcs:ignore
	?>
	<div class="wrap">
		<h1>بهنیا فیت — حساب کاربری</h1>

		<?php if ( 'coach' === $msg ) : ?>
			<div class="notice notice-success is-dismissible"><p>حساب مربی ذخیره شد.</p></div>
		<?php elseif ( 'saved' === $msg ) : ?>
			<div class="notice notice-success is-dismissible"><p>تنظیمات ذخیره شد.</p></div>
		<?php elseif ( 'need_password' === $msg ) : ?>
			<div class="notice notice-error is-dismissible"><p>برای ساخت حساب مربی تازه، رمز عبور لازم است.</p></div>
		<?php endif; ?>

		<p style="max-width:46rem">
			صفحه‌های حساب کاربری روی این نشانی‌ها هستند:
			<code><?php echo esc_html( home_url( '/login.html' ) ); ?></code>،
			<code><?php echo esc_html( home_url( '/account.html' ) ); ?></code> و
			<code><?php echo esc_html( home_url( '/admin.html' ) ); ?></code>.
		</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'bf_save_settings' ); ?>
			<input type="hidden" name="action" value="bf_save_settings" />

			<h2>حساب مربی</h2>
			<p>این حساب به پنل مربی دسترسی دارد. اگر کاربری با این شماره نباشد، ساخته می‌شود.</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="bf_coach_phone">شمارهٔ موبایل مربی</label></th>
					<td>
						<input class="regular-text" dir="ltr" id="bf_coach_phone" name="bf_coach_phone"
							value="<?php echo esc_attr( $coach ? get_user_meta( $coach->ID, 'bf_phone', true ) : '' ); ?>"
							placeholder="09123456789" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="bf_coach_name">نام نمایشی</label></th>
					<td>
						<input class="regular-text" id="bf_coach_name" name="bf_coach_name"
							value="<?php echo esc_attr( $coach ? $coach->display_name : 'بهنیا' ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="bf_coach_password">رمز عبور</label></th>
					<td>
						<input class="regular-text" dir="ltr" type="password" id="bf_coach_password" name="bf_coach_password"
							autocomplete="new-password" placeholder="<?php echo $coach ? 'برای عوض نکردن، خالی بگذار' : 'حداقل ۸ کاراکتر'; ?>" />
					</td>
				</tr>
			</table>

			<h2>پرداخت</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="bf_card_number">شمارهٔ کارت برای کارت‌به‌کارت</label></th>
					<td>
						<input class="regular-text" dir="ltr" id="bf_card_number" name="bf_card_number"
							value="<?php echo esc_attr( get_option( 'bf_card_number', '' ) ); ?>"
							placeholder="6037-9970-0000-0000" />
						<p class="description">این شماره در مرحلهٔ پرداخت به شاگرد نشان داده می‌شود.</p>
					</td>
				</tr>
			</table>

			<h2>قیمت برنامه‌ها</h2>
			<p style="max-width:46rem">
				قیمت‌ها به <strong>دلار</strong> است و همان‌جا روی صفحهٔ اصلی سایت و در
				صفحهٔ خرید نشان داده می‌شود. اگر خانه‌ای را خالی بگذاری، قیمت پیش‌فرض
				برمی‌گردد. سفارش‌هایی که قبلاً ثبت شده‌اند مبلغ خودشان را نگه می‌دارند.
			</p>
			<table class="form-table" role="presentation">
				<?php foreach ( bf_plan_defaults() as $plan ) : ?>
					<?php $current = bf_plan_price( $plan['key'], $plan['price_usd'] ); ?>
					<tr>
						<th scope="row">
							<label for="bf_price_<?php echo esc_attr( $plan['key'] ); ?>">
								<?php echo esc_html( $plan['title'] ); ?>
							</label>
						</th>
						<td>
							<input class="small-text" dir="ltr" type="text" inputmode="decimal"
								id="bf_price_<?php echo esc_attr( $plan['key'] ); ?>"
								name="bf_price[<?php echo esc_attr( $plan['key'] ); ?>]"
								value="<?php echo esc_attr( (string) ( $current == (int) $current ? (int) $current : $current ) ); ?>" />
							<span>دلار</span>
							<p class="description">
								روی سایت الان این‌طور دیده می‌شود:
								<strong><?php echo esc_html( bf_price_text( $current ) ); ?> دلار</strong>
							</p>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>

			<h2>پیشرفته</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">پشت پروکسی HTTPS</th>
					<td>
						<label>
							<input type="checkbox" name="bf_trust_proxy" value="1" <?php checked( get_option( 'bf_trust_proxy' ), 1 ); ?> />
							اگر روی هاست پشت کلودفلر یا پروکسی هستی، روشن کن
						</label>
					</td>
				</tr>
			</table>

			<?php submit_button( 'ذخیرهٔ تنظیمات' ); ?>
		</form>

		<hr />
		<h2>وضعیت</h2>
		<ul style="line-height:2">
			<li>شاگردها: <strong><?php echo esc_html( (string) bf_students_total() ); ?></strong></li>
			<li>سفارش‌های منتظر تایید: <strong><?php echo esc_html( (string) bf_pending_total() ); ?></strong></li>
			<li>جداول دیتابیس: <code><?php echo esc_html( bf_table( 'orders' ) ); ?></code> و چهار جدول دیگر</li>
		</ul>
	</div>
	<?php
}

/** شمارش شاگردها برای کارت وضعیت */
function bf_students_total() {
	$count = count_users();
	$roles = isset( $count['avail_roles'] ) ? $count['avail_roles'] : array();
	return isset( $roles['bf_student'] ) ? (int) $roles['bf_student'] : 0;
}

/** شمارش سفارش‌های باز */
function bf_pending_total() {
	global $wpdb;
	return (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . bf_table( 'orders' ) . " WHERE status IN ('pending','awaiting_confirm')" ); // phpcs:ignore
}

/** لینک تنظیمات کنار نام افزونه در فهرست افزونه‌ها */
add_filter(
	'plugin_action_links_' . plugin_basename( BF_ACCOUNTS_FILE ),
	static function ( $links ) {
		$url = admin_url( 'options-general.php?page=behnia-fit' );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">تنظیمات</a>' );
		return $links;
	}
);
