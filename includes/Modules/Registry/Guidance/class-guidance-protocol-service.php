<?php
namespace FSTU\Modules\Registry\Guidance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Сервіс протоколу модуля «Склад керівних органів ФСТУ».
 * Відповідає за журнал `Logs` і відображення рядків протоколу.
 *
 * Version:     1.0.1
 * Date_update: 2026-04-12
 *
 * @package FSTU\Modules\UserFstu\Guidance
 */
class Guidance_Protocol_Service {

	public const LOG_NAME       = 'Guidance';
	public const STATUS_SUCCESS = '✓';

	public function log_action_transactional( string $type, string $text, string $status = self::STATUS_SUCCESS ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->insert(
			'Logs',
			[
				'User_ID'         => get_current_user_id(),
				'Logs_DateCreate' => current_time( 'mysql' ),
				'Logs_Type'       => $this->normalize_type( $type ),
				'Logs_Name'       => self::LOG_NAME,
				'Logs_Text'       => $text,
				'Logs_Error'      => $status,
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s' ]
		);

		if ( false === $result ) {
			throw new \RuntimeException( 'guidance_log_insert_failed' );
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
				'Logs_Type'       => $this->normalize_type( $type ),
				'Logs_Name'       => self::LOG_NAME,
				'Logs_Text'       => $text,
				'Logs_Error'      => $status,
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s' ]
		);
	}

	/**
	 * @param array<string,mixed> $item
	 */
	public function log_view_action( array $item ): void {
		$this->try_log_action(
			'V',
			sprintf(
				'Переглянуто картку запису Guidance: %s / %s / %s',
				(string) ( $item['TypeGuidance_Name'] ?? '—' ),
				(string) ( $item['MemberGuidance_Name'] ?? '—' ),
				(string) ( $item['FIO'] ?? '—' )
			),
			self::STATUS_SUCCESS
		);
	}

	/**
	 * @param array<int,array<string,mixed>> $items
	 */
	public function build_protocol_rows( array $items ): string {
		if ( empty( $items ) ) {
			return '<tr class="fstu-row"><td colspan="6" class="fstu-no-results">' . esc_html__( 'Записи протоколу відсутні.', 'fstu' ) . '</td></tr>';
		}

		$html = '';

		foreach ( $items as $item ) {
			$html .= '<tr class="fstu-row">';
			$html .= '<td class="fstu-td fstu-td--date">' . esc_html( (string) ( $item['Logs_DateCreate'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--type">' . $this->build_type_badge( (string) ( $item['Logs_Type'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td">' . esc_html( (string) ( $item['Logs_Name'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--message">' . esc_html( (string) ( $item['Logs_Text'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--status">' . esc_html( (string) ( $item['Logs_Error'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--user">' . esc_html( (string) ( $item['FIO'] ?? '' ) ) . '</td>';
			$html .= '</tr>';
		}

		return $html;
	}

	private function normalize_type( string $type ): string {
		$type = strtoupper( trim( $type ) );

		if ( '' === $type ) {
			return 'V';
		}

		return (string) mb_substr( $type, 0, 1 );
	}

	private function build_type_badge( string $type ): string {
		$normalized = $this->normalize_type( $type );
		$label      = $normalized;
		$class      = 'fstu-badge--default';

		switch ( $normalized ) {
			case 'I':
				$label = 'INSERT';
				$class = 'fstu-badge--insert';
				break;
			case 'U':
				$label = 'UPDATE';
				$class = 'fstu-badge--update';
				break;
			case 'D':
				$label = 'DELETE';
				$class = 'fstu-badge--delete';
				break;
			case 'V':
				$label = 'VIEW';
				break;
		}

		return '<span class="fstu-badge ' . esc_attr( $class ) . '">' . esc_html( $label ) . '</span>';
	}
}

