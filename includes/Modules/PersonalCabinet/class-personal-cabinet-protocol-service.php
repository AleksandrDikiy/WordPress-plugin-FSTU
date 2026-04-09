<?php
namespace FSTU\Modules\PersonalCabinet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Сервіс протоколу модуля «Особистий кабінет ФСТУ».
 *
 * Version:     1.2.0
 * Date_update: 2026-04-09
 *
 * @package FSTU\Modules\PersonalCabinet
 */
class Personal_Cabinet_Protocol_Service {

	private const LOG_NAME             = 'Personal';
	private const DEFAULT_PER_PAGE     = 10;
	private const MAX_PROTOCOL_PER_PAGE = 50;
	private const ALLOWED_LOG_TYPES     = [ 'I', 'U', 'D', 'V' ];

	private Personal_Cabinet_Repository $repository;

	public function __construct( ?Personal_Cabinet_Repository $repository = null ) {
		$this->repository = $repository ?? new Personal_Cabinet_Repository();
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_protocol_payload( string $search, int $page, int $per_page ): array {
		$normalized_page     = max( 1, $page );
		$normalized_per_page = min( max( 1, $per_page ), self::MAX_PROTOCOL_PER_PAGE );
		$offset              = ( $normalized_page - 1 ) * $normalized_per_page;
		$total               = $this->repository->count_protocol_items( $search );
		$items               = $this->repository->get_protocol_items( $search, $normalized_per_page, $offset );
		$total_pages         = max( 1, (int) ceil( $total / $normalized_per_page ) );

		if ( $normalized_page > $total_pages ) {
			$normalized_page = $total_pages;
			$offset          = ( $normalized_page - 1 ) * $normalized_per_page;
			$items           = $this->repository->get_protocol_items( $search, $normalized_per_page, $offset );
		}

		return [
			'items'       => $items,
			'total'       => $total,
			'page'        => $normalized_page,
			'per_page'    => $normalized_per_page,
			'total_pages' => $total_pages,
			'log_name'    => self::LOG_NAME,
		];
	}

	/**
	 * Базовий helper для майбутніх mutation-flow модуля.
	 */
	public function log_action( string $type, string $text, string $status ): bool {
		return $this->log_action_for_user( get_current_user_id(), $type, $text, $status );
	}

	public function log_action_for_user( int $user_id, string $type, string $text, string $status ): bool {
		global $wpdb;
		$payload = $this->get_log_insert_payload( $user_id, $type, $text, $status );

		$result = $wpdb->insert(
			'Logs',
			$payload['data'],
			$payload['format']
		);

		return false !== $result;
	}

	/**
	 * @return array{data:array<string,int|string>,format:array<int,string>}
	 */
	public function get_log_insert_payload( int $user_id, string $type, string $text, string $status ): array {
		$type   = strtoupper( substr( sanitize_key( $type ), 0, 1 ) );
		$text   = sanitize_text_field( $text );
		$status = sanitize_text_field( $status );

		if ( ! in_array( $type, self::ALLOWED_LOG_TYPES, true ) ) {
			$type = 'V';
		}

		return [
			'data'   => [
				'User_ID'         => max( 0, $user_id ),
				'Logs_DateCreate' => current_time( 'mysql' ),
				'Logs_Type'       => $type,
				'Logs_Name'       => self::LOG_NAME,
				'Logs_Text'       => $text,
				'Logs_Error'      => $status,
			],
			'format' => [ '%d', '%s', '%s', '%s', '%s', '%s' ],
		];
	}

	public static function get_default_per_page(): int {
		return self::DEFAULT_PER_PAGE;
	}
}

