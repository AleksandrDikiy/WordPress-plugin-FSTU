<?php
/**
 * Сервіс протоколу модуля "Реєстр мерилок".
 * Відповідає за логування дій користувачів.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-09
 *
 * @package FSTU\Modules\UserFstu\Merilkas
 */

namespace FSTU\Modules\Registry\Merilkas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Merilkas_Protocol_Service {

	public const LOG_NAME       = 'Merilka';
	public const STATUS_SUCCESS = '✓';

	/**
	 * Пише запис у таблицю Logs. Для success-flow метод є strict.
	 */
	public function log_action( string $type, string $text, string $status = self::STATUS_SUCCESS, bool $strict = true ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$inserted = $wpdb->insert(
			'Logs',
			[
				'User_ID'         => get_current_user_id(),
				'Logs_DateCreate' => current_time( 'mysql' ),
				'Logs_Type'       => $type, // Тільки 1 символ! (I, U, D, V)
				'Logs_Name'       => self::LOG_NAME,
				'Logs_Text'       => $text,
				'Logs_Error'      => $status,
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s' ]
		);

		if ( false === $inserted ) {
			if ( $strict ) {
				throw new \RuntimeException( 'merilkas_log_insert_failed' );
			}
			return false;
		}

		return true;
	}

	/**
	 * Безпечне логування поза strict success-flow.
	 */
	public function try_log_action( string $type, string $text, string $status ): bool {
		try {
			return $this->log_action( $type, $text, $status, false );
		} catch ( \Throwable $throwable ) {
			return false;
		}
	}
}