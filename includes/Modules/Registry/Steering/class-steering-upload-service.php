<?php
namespace FSTU\Modules\Registry\Steering;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Сервіс upload-логіки модуля «Реєстр стернових ФСТУ».
 *
 * Поточний етап містить лише каркас сервісу для безпечної роботи
 * з legacy-сумісним шляхом `/photo_steering/{User_ID}.jpg`.
 *
	 * Version:     1.2.0
 * Date_update: 2026-04-08
 *
 * @package FSTU\Modules\UserFstu\Steering
 */
class Steering_Upload_Service {

	private const DIRECTORY_NAME = 'photo_steering';
	private const MAX_FILE_SIZE  = 10485760;

	/**
	 * @param array<string,mixed> $file Дані файлу з $_FILES.
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

		return $destination_path;
	}

	public function delete_photo( int $user_id ): void {
		$destination_path = $this->get_destination_path( $user_id );

		if ( is_file( $destination_path ) ) {
			wp_delete_file( $destination_path );
		}
	}

	public function backup_existing_photo( int $user_id ): string {
		$source_path = $this->get_destination_path( $user_id );

		if ( ! is_file( $source_path ) ) {
			return '';
		}

		$backup_path = wp_tempnam( 'fstu-steering-photo-' . $user_id );
		if ( ! is_string( $backup_path ) || '' === $backup_path ) {
			throw new \RuntimeException( 'photo_backup_failed' );
		}

		if ( ! copy( $source_path, $backup_path ) ) {
			if ( is_file( $backup_path ) ) {
				wp_delete_file( $backup_path );
			}

			throw new \RuntimeException( 'photo_backup_failed' );
		}

		return $backup_path;
	}

	public function restore_photo_backup( int $user_id, string $backup_path ): void {
		$backup_path = trim( $backup_path );

		if ( '' === $backup_path ) {
			$this->delete_photo( $user_id );
			return;
		}

		$destination_path = $this->get_destination_path( $user_id );
		if ( ! copy( $backup_path, $destination_path ) ) {
			throw new \RuntimeException( 'photo_restore_failed' );
		}
	}

	public function remove_backup( string $backup_path ): void {
		$backup_path = trim( $backup_path );

		if ( '' !== $backup_path && is_file( $backup_path ) ) {
			wp_delete_file( $backup_path );
		}
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

		$image_size = getimagesize( $tmp_name );
		if ( false === $image_size ) {
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

