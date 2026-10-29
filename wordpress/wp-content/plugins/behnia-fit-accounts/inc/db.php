<?php
/**
 * جدول‌های افزونه.
 *
 * ساختار عیناً همان چیزی است که نسخهٔ Node در SQLite داشت، فقط با
 * پیشوند جدول وردپرس. اسم ستون‌ها عوض نشده تا پاسخ‌های JSON همان
 * کلیدهایی باشند که جاوااسکریپت انتظار دارد.
 *
 * @package behnia-fit-accounts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * نام کامل جدول با پیشوند.
 *
 * @param string $name بخش آخر: orders | programs | program_days | program_items | program_files.
 * @return string
 */
function bf_table( $name ) {
	global $wpdb;
	return $wpdb->prefix . 'bf_' . $name;
}

/**
 * ساخت/به‌روزرسانی جدول‌ها.
 *
 * ستون created_at عمداً VARCHAR است نه DATETIME: مقدارش همان ISO 8601
 * نسخهٔ Node است (2026-10-28T17:39:16+00:00) تا تاریخ‌ها در مرورگر بدون
 * هیچ تبدیل منطقهٔ زمانی درست خوانده شوند و مقایسه/مرتب‌سازی متنی هم
 * درست بماند.
 */
function bf_install_tables() {
	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset = $wpdb->get_charset_collate();
	$orders  = bf_table( 'orders' );
	$programs = bf_table( 'programs' );
	$days     = bf_table( 'program_days' );
	$items    = bf_table( 'program_items' );
	$files    = bf_table( 'program_files' );

	$sql = array();

	$sql[] = "CREATE TABLE {$orders} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		user_id bigint(20) unsigned NOT NULL,
		plan_key varchar(40) NOT NULL DEFAULT '',
		amount_usd decimal(10,2) NOT NULL DEFAULT 0,
		amount_toman bigint(20) unsigned NOT NULL DEFAULT 0,
		usd_rate_toman bigint(20) unsigned NOT NULL DEFAULT 0,
		status varchar(20) NOT NULL DEFAULT 'pending',
		method varchar(20) NOT NULL DEFAULT 'manual',
		receipt_file varchar(191) DEFAULT NULL,
		note text,
		created_at varchar(32) NOT NULL DEFAULT '',
		paid_at varchar(32) DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY user_id (user_id),
		KEY status (status)
	) {$charset};";

	$sql[] = "CREATE TABLE {$programs} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		user_id bigint(20) unsigned NOT NULL,
		order_id bigint(20) unsigned DEFAULT NULL,
		plan_key varchar(40) DEFAULT NULL,
		title varchar(191) NOT NULL DEFAULT '',
		summary text,
		start_date varchar(20) DEFAULT NULL,
		created_at varchar(32) NOT NULL DEFAULT '',
		PRIMARY KEY  (id),
		KEY user_id (user_id)
	) {$charset};";

	$sql[] = "CREATE TABLE {$days} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		program_id bigint(20) unsigned NOT NULL,
		label varchar(191) NOT NULL DEFAULT '',
		note text,
		sort int(11) NOT NULL DEFAULT 0,
		PRIMARY KEY  (id),
		KEY program_id (program_id)
	) {$charset};";

	$sql[] = "CREATE TABLE {$items} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		day_id bigint(20) unsigned NOT NULL,
		exercise varchar(191) NOT NULL DEFAULT '',
		sets varchar(20) DEFAULT NULL,
		reps varchar(30) DEFAULT NULL,
		rest varchar(30) DEFAULT NULL,
		note varchar(200) DEFAULT NULL,
		sort int(11) NOT NULL DEFAULT 0,
		PRIMARY KEY  (id),
		KEY day_id (day_id)
	) {$charset};";

	$sql[] = "CREATE TABLE {$files} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		program_id bigint(20) unsigned NOT NULL,
		stored_name varchar(191) NOT NULL DEFAULT '',
		original_name varchar(191) NOT NULL DEFAULT '',
		mime varchar(100) DEFAULT NULL,
		size bigint(20) unsigned DEFAULT 0,
		created_at varchar(32) NOT NULL DEFAULT '',
		PRIMARY KEY  (id),
		KEY program_id (program_id),
		KEY stored_name (stored_name)
	) {$charset};";

	dbDelta( $sql );
}

/** نقش شاگرد و نقش مربی — مربی یک قابلیت ویژه دارد و بس */
function bf_install_roles() {
	if ( ! get_role( 'bf_student' ) ) {
		add_role( 'bf_student', 'شاگرد بهنیا فیت', array( 'read' => true ) );
	}
	if ( ! get_role( 'bf_coach' ) ) {
		add_role(
			'bf_coach',
			'مربی بهنیا فیت',
			array(
				'read'      => true,
				'bf_manage' => true,
			)
		);
	}
	// مدیر سایت هم باید بتواند پنل مربی را ببیند
	$admin = get_role( 'administrator' );
	if ( $admin && ! $admin->has_cap( 'bf_manage' ) ) {
		$admin->add_cap( 'bf_manage' );
	}
}
