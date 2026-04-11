<?php
/**
 * Сервісний шар довідника осередків.
 * Відповідає за транзакції, логування та бізнес-правила.
 * * Version: 1.0.0
 * Date_update: 2026-04-10
 */

namespace FSTU\Dictionaries\Units;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Units_Service {

	private const LOG_NAME = 'Unit';

	/**
	 * Створення нового осередку з обов'язковою транзакцією.
	 */
	public function create_unit( array $data, int $user_id ): bool {
		global $wpdb;

		$wpdb->query( 'START TRANSACTION' );

		$data['Unit_DateCreate'] = current_time( 'mysql' );
		$data['Unit_Status']     = 1; // За замовчуванням активний

		$inserted = $wpdb->insert( 'S_Unit', $data );

		if ( ! $inserted ) {
			$wpdb->query( 'ROLLBACK' );
			return false;
		}

		$new_id = $wpdb->insert_id;
		$log_text = sprintf( 'Додано новий осередок ID: %d, Назва: %s', $new_id, $data['Unit_Name'] );

		if ( ! $this->log_action( 'I', $log_text, '✓', $user_id ) ) {
			$wpdb->query( 'ROLLBACK' );
			return false;
		}

		$wpdb->query( 'COMMIT' );
		return true;
	}

	/**
	 * Оновлення осередку.
	 */
	public function update_unit( int $id, array $data, int $user_id ): bool {
		global $wpdb;

		$wpdb->query( 'START TRANSACTION' );

		$updated = $wpdb->update( 'S_Unit', $data, [ 'Unit_ID' => $id ] );

		if ( $updated === false ) {
			$wpdb->query( 'ROLLBACK' );
			return false;
		}

		$log_text = sprintf( 'Оновлено дані осередку ID: %d', $id );

		if ( ! $this->log_action( 'U', $log_text, '✓', $user_id ) ) {
			$wpdb->query( 'ROLLBACK' );
			return false;
		}

		$wpdb->query( 'COMMIT' );
		return true;
	}

	/**
	 * М'яке видалення осередку (Soft Delete).
	 */
	public function delete_unit( int $id, int $user_id ): bool {
		global $wpdb;

		$wpdb->query( 'START TRANSACTION' );

		// Встановлюємо статус 0 замість фізичного DELETE
		$deleted = $wpdb->update( 'S_Unit', [ 'Unit_Status' => 0 ], [ 'Unit_ID' => $id ] );

		if ( $deleted === false ) {
			$wpdb->query( 'ROLLBACK' );
			return false;
		}

		$log_text = sprintf( 'Видалено осередок (Soft Delete) ID: %d', $id );

		if ( ! $this->log_action( 'D', $log_text, '✓', $user_id ) ) {
			$wpdb->query( 'ROLLBACK' );
			return false;
		}

		$wpdb->query( 'COMMIT' );
		return true;
	}

	/**
	 * Внутрішній helper для логування (згідно з правилом AGENTS.md: Logs_Type = 1 символ).
	 */
	private function log_action( string $type, string $text, string $status, int $user_id ): bool {
		global $wpdb;
		
		$inserted = $wpdb->insert(
			'Logs',
			[
				'User_ID'         => $user_id,
				'Logs_DateCreate' => current_time( 'mysql' ),
				'Logs_Type'       => $type,
				'Logs_Name'       => self::LOG_NAME,
				'Logs_Text'       => $text,
				'Logs_Error'      => $status,
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s' ]
		);

		return $inserted !== false;
	}
}