<?php
namespace FSTU\Modules\Registry\Sailboats;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Сервіс протоколу модуля "Судновий реєстр ФСТУ".
 * Відповідає за базове логування та рендер рядків протоколу.
 *
 * Version:     1.0.1
 * Date_update: 2026-04-07
 *
 * @package FSTU\Modules\UserFstu\Sailboats
 */
class Sailboats_Protocol_Service {

	public const LOG_NAME       = 'Sailboat';
	public const STATUS_SUCCESS = '✓';

	/**
	 * Пише запис у таблицю Logs.
	 */
	public function log_action( string $type, string $text, string $status = self::STATUS_SUCCESS, bool $strict = true ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$inserted = $wpdb->insert(
			'Logs',
			[
				'User_ID'         => get_current_user_id(),
				'Logs_DateCreate' => current_time( 'mysql' ),
				'Logs_Type'       => $type,
				'Logs_Name'       => self::LOG_NAME,
				'Logs_Text'       => $text,
				'Logs_Error'      => $status,
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s' ]
		);

		if ( false === $inserted ) {
			if ( $strict ) {
				throw new \RuntimeException( 'sailboats_log_insert_failed' );
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

	/**
	 * Будує HTML рядків протоколу.
	 *
	 * @param array<int,array<string,mixed>> $rows Рядки журналу.
	 */
	public function build_protocol_rows( array $rows ): string {
		if ( empty( $rows ) ) {
			return '<tr class="fstu-row"><td colspan="6" class="fstu-no-results">Записи протоколу відсутні.</td></tr>';
		}

		$html = '';

		foreach ( $rows as $row ) {
			$html .= '<tr class="fstu-row">';
			$html .= '<td class="fstu-td fstu-td--date">' . esc_html( (string) ( $row['Logs_DateCreate'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--type">' . esc_html( $this->get_log_type_label( (string) ( $row['Logs_Type'] ?? '' ) ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--operation">' . esc_html( (string) ( $row['Logs_Name'] ?? self::LOG_NAME ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--message">' . esc_html( (string) ( $row['Logs_Text'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--status">' . esc_html( $this->get_log_status_label( (string) ( $row['Logs_Error'] ?? '' ) ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--user">' . esc_html( (string) ( $row['FIO'] ?? 'Система' ) ) . '</td>';
			$html .= '</tr>';
		}

		return $html;
	}

	/**
	 * Повертає читабельний підпис типу логування.
	 */
	public function get_log_type_label( string $type ): string {
		return match ( strtoupper( trim( $type ) ) ) {
			'I', 'INSERT' => 'INSERT',
			'U', 'UPDATE' => 'UPDATE',
			'D', 'DELETE' => 'DELETE',
			'V', 'VIEW'   => 'VIEW',
			default       => $type,
		};
	}

	/**
	 * Повертає читабельний підпис статусу логування.
	 */
	public function get_log_status_label( string $status ): string {
		return match ( trim( mb_strtolower( $status ) ) ) {
			'✓', 'success', 'успішно' => self::STATUS_SUCCESS,
			default                   => $status,
		};
	}
}

