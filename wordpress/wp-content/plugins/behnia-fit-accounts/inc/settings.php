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

		// حالت قیمت‌گذاری و قیمت‌ها.
		//
		// نکتهٔ مهم: قیمت‌های دلاری و تومانی در دو گزینهٔ جدا نگه داشته
		// می‌شوند. وقتی مربی حالت را عوض می‌کند، عددهای داخل فرم هنوز با
		// واحد حالت قبلی‌اند؛ پس در همان ذخیره خوانده نمی‌شوند تا مثلاً
		// «۸ دلار» اشتباهی «۸ تومان» نشود.
		$current_mode = bf_pricing_mode();
		$posted_mode  = isset( $_POST['bf_pricing_mode'] ) ? sanitize_key( wp_unslash( $_POST['bf_pricing_mode'] ) ) : $current_mode; // phpcs:ignore
		$posted_mode  = 'manual_toman' === $posted_mode ? 'manual_toman' : 'auto_usd';
		$mode_changed = $posted_mode !== $current_mode;
		$posted       = isset( $_POST['bf_price'] ) && is_array( $_POST['bf_price'] ) ? wp_unslash( $_POST['bf_price'] ) : array(); // phpcs:ignore

		if ( $mode_changed ) {
			if ( 'manual_toman' === $posted_mode ) {
				// مبلغ‌های تومانی فعلی سایت نقطهٔ شروع حالت دستی می‌شوند،
				// تا مربی از صفر شروع نکند. بعد از این، ثابت می‌مانند.
				$rate           = bf_usd_toman_rate();
				$manual_prices  = bf_saved_toman_prices();
				foreach ( bf_plan_defaults() as $plan ) {
					$key = $plan['key'];
					if ( empty( $manual_prices[ $key ] ) && $rate ) {
						$manual_prices[ $key ] = bf_usd_to_toman( bf_plan_price( $key, $plan['price_usd'] ), $rate );
					}
				}
				if ( $manual_prices ) {
					update_option( 'bf_plan_prices_toman', $manual_prices );
				} else {
					delete_option( 'bf_plan_prices_toman' );
				}
			}
			// برگشت به حالت خودکار: قیمت‌های دلاری قبلی دست‌نخورده می‌مانند.
		} elseif ( 'manual_toman' === $posted_mode ) {
			$manual_prices = bf_saved_toman_prices();
			$cached_rate   = bf_cached_usd_toman_rate();

			foreach ( bf_plan_defaults() as $plan ) {
				$key = $plan['key'];
				if ( ! isset( $posted[ $key ] ) ) {
					continue;
				}
				$raw   = trim( (string) $posted[ $key ] );
				$value = bf_price_text_from_fa( $raw );

				if ( '' === $raw || $value <= 0 || false !== strpos( $raw, '-' ) ) {
					// خانهٔ خالی یعنی «هنوز دستی ثبت نشده»؛ اگر نرخ ذخیره‌شده
					// داریم، مبلغ تبدیل‌شدهٔ فعلی به‌عنوان شروع ثابت می‌شود.
					if ( empty( $manual_prices[ $key ] ) && $cached_rate ) {
						$manual_prices[ $key ] = bf_usd_to_toman( bf_plan_price( $key, $plan['price_usd'] ), $cached_rate );
					}
					continue;
				}

				$manual_prices[ $key ] = (int) min( round( $value ), 1000000000000 );
			}

			if ( $manual_prices ) {
				update_option( 'bf_plan_prices_toman', $manual_prices );
			} else {
				delete_option( 'bf_plan_prices_toman' );
			}
		} else {
			// قیمت‌های دلاری: هر کلید فقط اگر عدد معتبر باشد ذخیره می‌شود،
			// وگرنه پیش‌فرض می‌ماند. عدد صفر یعنی «همان پیش‌فرض».
			$prices = array();
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
		}

		update_option( 'bf_pricing_mode', $posted_mode );

		// قیمت‌ها در HTML صفحه نوشته می‌شوند، پس اگر هاست کش صفحه داشته باشد
		// کاربر تا انقضای کش عدد قدیمی را می‌بیند. بعد از ذخیره پاکش می‌کنیم.
		bf_flush_page_caches();

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

/**
 * به‌روزرسانی دستی نرخ دلار از صفحهٔ تنظیمات.
 */
add_action(
	'admin_post_bf_refresh_usd_rate',
	static function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی نداری.' );
		}
		check_admin_referer( 'bf_refresh_usd_rate' );

		$rate = bf_refresh_usd_toman_rate();
		if ( function_exists( 'bf_flush_page_caches' ) ) {
			bf_flush_page_caches();
		}

		// اگر سرویس جواب نداده باشد، bf_refresh آخرین نرخ ذخیره‌شده را برمی‌گرداند؛
		// آن را «به‌روزرسانی موفق» حساب نمی‌کنیم.
		$fresh = $rate && empty( $rate['cached'] );

		wp_safe_redirect(
			add_query_arg(
				'bf_msg',
				$fresh ? 'rate_ok' : 'rate_fail',
				admin_url( 'options-general.php?page=behnia-fit' )
			)
		);
		exit;
	}
);

/** ظاهر صفحه */
/**
 * پاک‌کردن کش صفحه/شیء بعد از تغییر تنظیمات.
 *
 * روی هاست‌های ایرانی معمولاً LiteSpeed Cache یا WP Rocket نصب است و صفحه را
 * کش می‌کند. بدون این، قیمت تازه تا انقضای کش دیده نمی‌شود. هر کدام نبود،
 * بی‌صدا رد می‌شود.
 */
function bf_flush_page_caches() {
	wp_cache_flush();

	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}
	if ( function_exists( 'w3tc_flush_all' ) ) {
		w3tc_flush_all();
	}
	if ( function_exists( 'wp_cache_clear_cache' ) ) {
		wp_cache_clear_cache();
	}
	if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
		sg_cachepress_purge_cache();
	}
	if ( function_exists( 'opcache_reset' ) ) {
		@opcache_reset(); // phpcs:ignore
	}

	do_action( 'litespeed_purge_all' );
	do_action( 'ce_action_cache_clean' );
	do_action( 'cache_enabler_clear_complete_cache' );
}

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
			<?php
			$bf_pricing_mode = bf_pricing_mode();
			$bf_manual       = 'manual_toman' === $bf_pricing_mode;
			$bf_rate         = $bf_manual ? bf_cached_usd_toman_rate() : bf_usd_toman_rate();
			?>
			<fieldset style="max-width:46rem;margin:0 0 1rem">
				<legend class="screen-reader-text">حالت قیمت‌گذاری</legend>
				<label style="display:block;line-height:2">
					<input type="radio" name="bf_pricing_mode" value="auto_usd" <?php checked( $bf_pricing_mode, 'auto_usd' ); ?> />
					<strong>خودکار</strong> — قیمت را به دلار وارد می‌کنم و سایت با نرخ روز دلار به تومان تبدیل می‌کند.
				</label>
				<label style="display:block;line-height:2">
					<input type="radio" name="bf_pricing_mode" value="manual_toman" <?php checked( $bf_pricing_mode, 'manual_toman' ); ?> />
					<strong>دستی</strong> — تبدیل خودکار خاموش است و خودم قیمت نهایی را به تومان وارد می‌کنم.
				</label>
				<p class="description">
					اگر حالت را عوض می‌کنی، اول فقط حالت را انتخاب کن و «ذخیرهٔ تنظیمات» را بزن؛
					بعد صفحه با واحد همان حالت باز می‌شود و قیمت‌ها را ویرایش کن. در اولین رفتن به
					حالت دستی، مبلغ‌های تومانی فعلی سایت برای شروع در خانه‌ها می‌نشینند و بعد از آن
					ثابت می‌مانند تا خودت عوضشان کنی.
				</p>
			</fieldset>

			<?php if ( $bf_manual ) : ?>
				<p style="max-width:46rem">
					تبدیل خودکار <strong>خاموش</strong> است. عددهایی که اینجا به تومان وارد می‌کنی
					دقیقاً همان‌هایی‌اند که روی سایت و در صفحهٔ خرید دیده می‌شوند و با بالا و پایین
					شدن دلار تغییر نمی‌کنند. سفارش تازه همین مبلغ تومانی را ذخیره می‌کند.
				</p>
			<?php else : ?>
				<p style="max-width:46rem">
					قیمت پایه را اینجا به <strong>دلار</strong> وارد می‌کنی؛ سایت آن را با
					نرخ روز دلار آزاد به <strong>تومانِ بدون اعشار</strong> تبدیل می‌کند و
					همان مبلغ تومانی روی صفحهٔ اصلی و در صفحهٔ خرید نشان داده می‌شود.
					اگر خانه‌ای را خالی بگذاری، قیمت پیش‌فرض برمی‌گردد. سفارش‌هایی که
					قبلاً ثبت شده‌اند مبلغ خودشان را نگه می‌دارند؛ سفارش تازه مبلغ
					تومانی و نرخ همان لحظه را ذخیره می‌کند.
				</p>
				<?php if ( $bf_rate ) : ?>
					<p style="max-width:46rem">
						نرخ فعلی هر دلار:
						<strong><?php echo esc_html( bf_toman_text( $bf_rate['rate'] ) ); ?></strong>
						<?php if ( ! empty( $bf_rate['market_time'] ) ) : ?>
							<span>— زمان بازار: <span dir="ltr"><?php echo esc_html( $bf_rate['market_time'] ); ?></span></span>
						<?php endif; ?>
						<?php if ( ! empty( $bf_rate['stale'] ) ) : ?>
							<span>(آخرین نرخ موفق ذخیره‌شده)</span>
						<?php endif; ?>
						<?php $bf_refresh_url = wp_nonce_url( admin_url( 'admin-post.php?action=bf_refresh_usd_rate' ), 'bf_refresh_usd_rate' ); ?>
						<a class="button" href="<?php echo esc_url( $bf_refresh_url ); ?>">به‌روزرسانی نرخ دلار</a>
					</p>
				<?php else : ?>
					<div class="notice notice-warning inline" style="max-width:46rem">
						<p>نرخ دلار هنوز دریافت نشده است. اتصال هاست به اینترنت را بررسی کن؛ تا وقتی نرخ نیاید، قیمت‌ها موقتاً دلاری نشان داده می‌شوند.</p>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<table class="form-table" role="presentation">
				<?php foreach ( bf_plan_defaults() as $plan ) : ?>
					<?php
					$current_usd   = bf_plan_price( $plan['key'], $plan['price_usd'] );
					$saved_toman   = bf_saved_plan_toman( $plan['key'] );
					$suggested     = $saved_toman ? $saved_toman : ( $bf_rate ? bf_usd_to_toman( $current_usd, $bf_rate ) : 0 );
					$field_value   = $bf_manual ? ( $saved_toman ? (string) $saved_toman : '' ) : (string) ( $current_usd == (int) $current_usd ? (int) $current_usd : $current_usd );
					?>
					<tr>
						<th scope="row">
							<label for="bf_price_<?php echo esc_attr( $plan['key'] ); ?>">
								<?php echo esc_html( $plan['title'] ); ?>
							</label>
						</th>
						<td>
							<input class="small-text" dir="ltr" type="text" inputmode="<?php echo $bf_manual ? 'numeric' : 'decimal'; ?>"
								style="<?php echo $bf_manual ? 'width:11rem' : ''; ?>"
								id="bf_price_<?php echo esc_attr( $plan['key'] ); ?>"
								name="bf_price[<?php echo esc_attr( $plan['key'] ); ?>]"
								value="<?php echo esc_attr( $field_value ); ?>"
								<?php if ( $bf_manual && ! $saved_toman && $suggested ) : ?>
									placeholder="<?php echo esc_attr( (string) $suggested ); ?>"
								<?php endif; ?> />
							<span><?php echo $bf_manual ? 'تومان' : 'دلار'; ?></span>
							<p class="description">
								روی سایت الان این‌طور دیده می‌شود:
								<?php if ( $bf_manual ) : ?>
									<?php if ( $saved_toman ) : ?>
										<strong><?php echo esc_html( bf_toman_text( $saved_toman ) ); ?></strong>
										<span>— ثابت می‌ماند و با دلار تغییر نمی‌کند.</span>
									<?php elseif ( $suggested ) : ?>
										<span>هنوز قیمت تومانی ثبت نشده است؛ مبلغ تبدیل‌شدهٔ فعلی برای شروع
										<strong><?php echo esc_html( bf_toman_text( $suggested ) ); ?></strong> است.</span>
									<?php else : ?>
										<span>هنوز قیمت تومانی ثبت نشده است؛ مبلغ را به تومان وارد کن.</span>
									<?php endif; ?>
								<?php elseif ( $bf_rate ) : ?>
									<strong><?php echo esc_html( bf_toman_text( bf_usd_to_toman( $current_usd, $bf_rate ) ) ); ?></strong>
								<?php else : ?>
									<strong><?php echo esc_html( bf_price_text( $current_usd ) ); ?> دلار</strong>
								<?php endif; ?>
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
			<li>نسخهٔ افزونه: <code><?php echo esc_html( BF_ACCOUNTS_VERSION ); ?></code></li>
			<li>حالت قیمت‌گذاری:
				<strong><?php echo bf_manual_pricing_enabled() ? 'دستی — تومان' : 'خودکار — دلار + نرخ روز'; ?></strong>
			</li>
			<li>قیمت‌های ذخیره‌شده:
				<?php if ( bf_manual_pricing_enabled() ) : ?>
					<?php $saved_toman_prices = bf_saved_toman_prices(); ?>
					<?php if ( $saved_toman_prices ) : ?>
						<strong><?php echo esc_html( implode( ' · ', array_map( 'bf_toman_text', $saved_toman_prices ) ) ); ?></strong>
					<?php else : ?>
						<strong>هنوز قیمت تومانی ثبت نشده</strong>
					<?php endif; ?>
				<?php else : ?>
					<?php $saved_prices = bf_saved_prices(); ?>
					<?php if ( $saved_prices ) : ?>
						<strong><?php echo esc_html( implode( ' · ', array_map( 'bf_price_text', $saved_prices ) ) ); ?> دلار</strong>
						(بقیه از پیش‌فرض می‌آید)
					<?php else : ?>
						<strong>همه پیش‌فرض</strong>
					<?php endif; ?>
				<?php endif; ?>
			</li>
			<li>نرخ دلار:
				<?php if ( bf_manual_pricing_enabled() ) : ?>
					<strong>در حالت دستی استفاده نمی‌شود</strong>
				<?php else : ?>
					<?php $status_rate = bf_usd_toman_rate(); ?>
					<?php if ( $status_rate ) : ?>
						<strong><?php echo esc_html( bf_toman_text( $status_rate['rate'] ) ); ?></strong>
						<?php if ( ! empty( $status_rate['market_time'] ) ) : ?>
							<span>(<span dir="ltr"><?php echo esc_html( $status_rate['market_time'] ); ?></span>)</span>
						<?php endif; ?>
					<?php else : ?>
						<strong>هنوز دریافت نشده</strong>
					<?php endif; ?>
				<?php endif; ?>
			</li>
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
