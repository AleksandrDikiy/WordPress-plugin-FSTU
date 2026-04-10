<?php
namespace FSTU\Modules\Registry\MemberCardApplications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Сервіс upload-логіки модуля «Посвідчення членів ФСТУ».
 * Працює з legacy-сумісним шляхом `/photo/{User_ID}.jpg`.
 *
 * Version:     1.1.0
 * Date_update: 2026-04-10
 *
 * @package FSTU\Modules\Registry\MemberCardApplications
 */
class Member_Card_Applications_Upload_Service {

	private const DIRECTORY_NAME = 'photo';
	private const MAX_FILE_SIZE  = 10485760;

	/**
	 * @param array<string,mixed> $file
	 */
	public function store_uploaded_photo( array $file, int $user_id ): string {
		if ( $user_id <= 0 ) {
			throw new \RuntimeException( 'photo_invalid_user' );
		}

		$error_code = isset( $file['error'] ) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
		if ( UPLOAD_ERR_OK !== $error_code ) {
			throw new \RuntimeException( $this->map_upload_error( $error_code ) );
		}

		$tmp_name = isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';
		if ( '' === $tmp_name || ! is_uploaded_file( $tmp_name ) ) {
			throw new \RuntimeException( 'photo_not_uploaded' );
		}

		$file_size = isset( $file['size'] ) ? (int) $file['size'] : 0;
		if ( $file_size <= 0 ) {
			throw new \RuntimeException( 'photo_empty' );
		}

		if ( $file_size > self::MAX_FILE_SIZE ) {
			throw new \RuntimeException( 'photo_too_large' );
		}

		$this->validate_image_file( $tmp_name, (string) ( $file['name'] ?? '' ) );

		$directory_path = $this->get_directory_path();
		if ( ! wp_mkdir_p( $directory_path ) && ! is_dir( $directory_path ) ) {
			throw new \RuntimeException( 'photo_directory_unavailable' );
		}

		$destination_path = $this->get_destination_path( $user_id );
		$editor           = wp_get_image_editor( $tmp_name );

		if ( is_wp_error( $editor ) ) {
			throw new \RuntimeException( 'photo_editor_unavailable' );
		}

		if ( method_exists( $editor, 'set_quality' ) ) {
			$editor->set_quality( 90 );
		}

		$result = $editor->save( $destination_path, 'image/jpeg' );
		if ( is_wp_error( $result ) || ! is_array( $result ) ) {
			throw new \RuntimeException( 'photo_save_failed' );
		}

		return $this->get_photo_url( $user_id, true );
	}

	public function get_photo_url( int $user_id, bool $with_cache_bust = false ): string {
		if ( $user_id <= 0 ) {
			return '';
		}

		$photo_path = $this->get_destination_path( $user_id );
		if ( ! file_exists( $photo_path ) ) {
			return '';
		}

		$url = home_url( '/photo/' . $user_id . '.jpg' );

		if ( ! $with_cache_bust ) {
			return $url;
		}

		$filemtime = filemtime( $photo_path );

		return false !== $filemtime ? add_query_arg( 'v', (string) $filemtime, $url ) : add_query_arg( 'v', (string) time(), $url );
	}

	public function has_photo( int $user_id ): bool {
		return '' !== $this->get_photo_url( $user_id );
	}

	private function get_directory_path(): string {
		return trailingslashit( ABSPATH ) . self::DIRECTORY_NAME;
	}

	private function get_destination_path( int $user_id ): string {
		return trailingslashit( $this->get_directory_path() ) . $user_id . '.jpg';
	}

	private function validate_image_file( string $tmp_name, string $original_name ): void {
		$file_info      = wp_check_filetype_and_ext( $tmp_name, $original_name );
		$validated_type = isset( $file_info['type'] ) ? (string) $file_info['type'] : '';

		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		$real_mime = false !== $finfo ? (string) finfo_file( $finfo, $tmp_name ) : '';
		if ( false !== $finfo ) {
			finfo_close( $finfo );
		}

		$allowed_mimes = [
			'image/jpeg',
			'image/png',
			'image/webp',
		];

		if ( ! in_array( $validated_type, $allowed_mimes, true ) && ! in_array( $real_mime, $allowed_mimes, true ) ) {
			throw new \RuntimeException( 'photo_invalid_type' );
		}

		if ( false === getimagesize( $tmp_name ) ) {
			throw new \RuntimeException( 'photo_invalid_image' );
		}
	}

	private function map_upload_error( int $error_code ): string {
		return match ( $error_code ) {
			UPLOAD_ERR_INI_SIZE,
			UPLOAD_ERR_FORM_SIZE => 'photo_too_large',
			UPLOAD_ERR_PARTIAL   => 'photo_partial',
			UPLOAD_ERR_NO_FILE   => 'photo_required',
			UPLOAD_ERR_NO_TMP_DIR => 'photo_tmp_dir_missing',
			UPLOAD_ERR_CANT_WRITE => 'photo_disk_write_failed',
			UPLOAD_ERR_EXTENSION => 'photo_extension_blocked',
			default              => 'photo_upload_failed',
		};
	}
}

