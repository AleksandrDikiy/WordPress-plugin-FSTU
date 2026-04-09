<?php
namespace FSTU\Modules\Registry\Steering;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Сервіс протоколу модуля «Реєстр стернових ФСТУ».
 *
	 * Працює з журналом Logs модуля Steering та рендерить
	 * HTML-рядки розділу «ПРОТОКОЛ».
 *
	 * Version:     1.3.0
 * Date_update: 2026-04-08
 *
 * @package FSTU\Modules\Registry\Steering
 */
class Steering_Protocol_Service {

	public const LOG_NAME = 'Steering';
	public const STATUS_SUCCESS = '✓';

	public function log_action_transactional( string $type, string $text, string $status = self::STATUS_SUCCESS ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->insert(
			'Logs',
			[
				'User_ID'         => get_current_user_id(),
				'Logs_DateCreate' => current_time( 'mysql' ),
				'Logs_Type'       => mb_substr( $type, 0, 1 ),
				'Logs_Name'       => self::LOG_NAME,
				'Logs_Text'       => $text,
				'Logs_Error'      => $status,
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s' ]
		);

		if ( false === $result ) {
			throw new \RuntimeException( 'steering_log_insert_failed' );
		}
	}

	public function try_log_action( string $type, string $text, string $status ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			'Logs',
			[
				'User_ID'         => get_current_user_id(),
				'Logs_DateCreate' => current_time( 'mysql' ),
				'Logs_Type'       => mb_substr( $type, 0, 1 ),
				'Logs_Name'       => self::LOG_NAME,
				'Logs_Text'       => $text,
				'Logs_Error'      => $status,
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s' ]
		);
	}

	/**
	 * @param array<int,array<string,mixed>> $items Елементи журналу.
	 */
	public function build_protocol_rows( array $items ): string {
		if ( empty( $items ) ) {
			return '<tr class="fstu-row"><td colspan="6" class="fstu-no-results">' . esc_html__( 'Записи протоколу відсутні.', 'fstu' ) . '</td></tr>';
		}

		$html = '';

		foreach ( $items as $item ) {
			$type = (string) ( $item['Logs_Type'] ?? '' );

			$html .= '<tr class="fstu-row">';
			$html .= '<td class="fstu-td fstu-td--date">' . esc_html( (string) ( $item['Logs_DateCreate'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--type">' . $this->build_type_badge( $type ) . '</td>';
			$html .= '<td class="fstu-td">' . esc_html( (string) ( $item['Logs_Name'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--message">' . esc_html( (string) ( $item['Logs_Text'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--status">' . esc_html( (string) ( $item['Logs_Error'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--user">' . esc_html( (string) ( $item['FIO'] ?? '' ) ) . '</td>';
			$html .= '</tr>';
		}

		return $html;
	}

	private function build_type_badge( string $type ): string {
		$normalized = strtoupper( trim( $type ) );
		$label      = $normalized;
		$class      = 'fstu-badge--default';

		switch ( $normalized ) {
			case 'I':
				$label = 'INSERT';
				$class = 'fstu-badge--insert';
				break;
				case 'V':
					$label = 'VERIFY';
					$class = 'fstu-badge--verify';
					break;
			case 'U':
				$label = 'UPDATE';
				$class = 'fstu-badge--update';
				break;
			case 'D':
				$label = 'DELETE';
				$class = 'fstu-badge--delete';
				break;
		}

		return '<span class="fstu-badge ' . esc_attr( $class ) . '">' . esc_html( '' !== $label ? $label : '—' ) . '</span>';
	}
}

