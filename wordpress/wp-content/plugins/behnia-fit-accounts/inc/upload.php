<?php
/**
 * آپلود فایل.
 *
 * همان قاعدهٔ lib/upload.js: فقط JPG/PNG/WebP/PDF، نوع فایل با محتوای
 * واقعی تشخیص داده می‌شود نه با پسوند، و نام ذخیره‌شده تصادفی است تا
 * نشود فایل بقیه را حدس زد.
 *
 * فایل‌ها بیرون از پوشهٔ رسانهٔ وردپرس و در uploads/behnia-fit می‌نشینند
 * و مستقیم سرو نمی‌شوند؛ فقط از /api/files/... و پس از بررسی مالکیت.
 *
 * @package behnia-fit-accounts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** فقط این چهار نوع پذیرفته می‌شود — نگاشت mime به پسوند */
function bf_allowed_types() {
	return array(
		'image/jpeg'      => 'jpg',
		'image/png'       => 'png',
		'image/webp'      => 'webp',
		'application/pdf' => 'pdf',
	);
}

/** پوشهٔ ذخیرهٔ فایل‌ها */
function bf_store_dir() {
	$uploads = wp_upload_dir( null, false );
	$dir     = trailingslashit( $uploads['basedir'] ) . 'behnia-fit';
	if ( ! file_exists( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	// جلوی سرو مستقیم فایل‌ها را می‌گیریم؛ تحویل فقط از مسیر API است.
	$guard = $dir . '/.htaccess';
	if ( ! file_exists( $guard ) ) {
		@file_put_contents( $guard, "Require all denied\nDeny from all\n" ); // phpcs:ignore
	}
	$index = $dir . '/index.php';
	if ( ! file_exists( $index ) ) {
		@file_put_contents( $index, "<?php\n// سکوت.\n" ); // phpcs:ignore
	}
	return $dir;
}

/**
 * نوع واقعی فایل را از محتوا تشخیص می‌دهد.
 *
 * @param string $path مسیر فایل موقت.
 * @return string|null mime مجاز یا null.
 */
function bf_detect_mime( $path ) {
	if ( ! is_file( $path ) ) {
		return null;
	}
	$allowed = bf_allowed_types();

	$mime = '';
	if ( function_exists( 'finfo_open' ) ) {
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		if ( $finfo ) {
			$mime = (string) finfo_file( $finfo, $path );
			finfo_close( $finfo );
		}
	}
	if ( ! $mime && function_exists( 'mime_content_type' ) ) {
		$mime = (string) mime_content_type( $path );
	}
	if ( 'image/jpg' === $mime ) {
		$mime = 'image/jpeg';
	}

	return isset( $allowed[ $mime ] ) ? $mime : null;
}

/**
 * نام اصلی فایل را از چیزهای خطرناک پاک می‌کند.
 *
 * @param string $name نام خام.
 * @return string
 */
function bf_clean_original_name( $name ) {
	$name = (string) $name;
	$name = basename( str_replace( '\\', '/', $name ) );
	$name = preg_replace( '/[\x00-\x1F\x7F]/u', '', $name );
	$name = str_replace( array( '"', '<', '>', '|', ':', '*', '?' ), '', $name );
	$name = trim( $name );
	if ( '' === $name ) {
		$name = 'file';
	}
	return function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 180 ) : substr( $name, 0, 180 );
}

/**
 * فایل‌های آپلودی را ذخیره می‌کند.
 *
 * @param array $files آرایهٔ $_FILES به شکل فهرست؛ هر عضو name/type/tmp_name/error/size.
 * @param int   $max   حداکثر تعداد.
 * @return array فهرست [ stored_name, original_name, mime, size ].
 * @throws RuntimeException وقتی فایل بزرگ یا از نوع غیرمجاز باشد.
 */
function bf_save_uploads( $files, $max = 6 ) {
	$saved   = array();
	$allowed = bf_allowed_types();
	$dir     = bf_store_dir();
	$limit   = wp_max_upload_size();

	foreach ( array_slice( (array) $files, 0, $max ) as $file ) {
		if ( empty( $file['tmp_name'] ) || ! isset( $file['error'] ) || UPLOAD_ERR_OK !== (int) $file['error'] ) {
			continue;
		}

		$size = (int) $file['size'];
		if ( $size <= 0 || $size > $limit ) {
			throw new RuntimeException( 'حجم فایل بیش از حد مجاز است' );
		}

		$mime = bf_detect_mime( $file['tmp_name'] );
		if ( ! $mime ) {
			throw new RuntimeException( 'فقط تصویر (JPG/PNG/WebP) یا PDF پذیرفته می‌شود' );
		}

		$ext    = $allowed[ $mime ];
		$stored = bin2hex( random_bytes( 16 ) ) . '.' . $ext;
		$target = trailingslashit( $dir ) . $stored;

		if ( ! @move_uploaded_file( $file['tmp_name'], $target ) ) { // phpcs:ignore
			// در تست‌های CLI گاهی move_uploaded_file اجازه ندارد
			if ( ! @rename( $file['tmp_name'], $target ) ) { // phpcs:ignore
				throw new RuntimeException( 'ذخیرهٔ فایل ممکن نشد' );
			}
		}
		@chmod( $target, 0640 ); // phpcs:ignore

		$saved[] = array(
			'stored_name'   => $stored,
			'original_name' => bf_clean_original_name( isset( $file['name'] ) ? $file['name'] : '' ),
			'mime'          => $mime,
			'size'          => $size,
		);
	}

	return $saved;
}

/**
 * $_FILES تکی یا چندتایی را به فهرست یکدست تبدیل می‌کند.
 *
 * @param array $field مقدار $_FILES['name'].
 * @return array
 */
function bf_normalize_files( $field ) {
	if ( ! is_array( $field ) || ! isset( $field['tmp_name'] ) ) {
		return array();
	}
	if ( ! is_array( $field['tmp_name'] ) ) {
		return array( $field );
	}
	$out = array();
	foreach ( $field['tmp_name'] as $i => $tmp ) {
		$out[] = array(
			'name'     => isset( $field['name'][ $i ] ) ? $field['name'][ $i ] : '',
			'type'     => isset( $field['type'][ $i ] ) ? $field['type'][ $i ] : '',
			'tmp_name' => $tmp,
			'error'    => isset( $field['error'][ $i ] ) ? $field['error'][ $i ] : UPLOAD_ERR_NO_FILE,
			'size'     => isset( $field['size'][ $i ] ) ? $field['size'][ $i ] : 0,
		);
	}
	return $out;
}
