<?php
namespace FSTU\Modules\Registry\Referees;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service-шар модуля «Реєстр суддів ФСТУ».
 * Містить бізнес-правила, транзакції та підготовку payload.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-08
 *
 * @package FSTU\Modules\Registry\Referees
 */
class Referees_Service {

	private Referees_Repository $repository;
	private Referees_Protocol_Service $protocol_service;

	public function __construct( Referees_Repository $repository, Referees_Protocol_Service $protocol_service ) {
		$this->repository       = $repository;
		$this->protocol_service = $protocol_service;
	}

	/**
	 * @param array<string,mixed> $filters Набір фільтрів.
	 * @return array<string,mixed>
	 */
	public function get_list_payload( array $filters ): array {
		$page     = max( 1, (int) ( $filters['page'] ?? 1 ) );
		$per_page = max( 1, (int) ( $filters['per_page'] ?? 10 ) );
		$offset   = (int) ( $filters['offset'] ?? ( $page - 1 ) * $per_page );
		$search   = trim( (string) ( $filters['search'] ?? '' ) );

		if ( '' !== $search ) {
			$all_items = $this->repository->get_referees_for_search_fallback( $filters );
			$filtered_items = array_values(
				array_filter(
					$all_items,
					function ( array $item ) use ( $search ): bool {
						return $this->matches_search( $item, $search );
					}
				)
			);

			$total = count( $filtered_items );
			$items = array_slice( $filtered_items, $offset, $per_page );
		} else {
			$total = $this->repository->count_referees( $filters );
			$items = $this->repository->get_referees( $filters, $per_page, $offset );
		}

		return [
			'items'       => $items,
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => max( 1, (int) ceil( $total / max( 1, $per_page ) ) ),
		];
	}

	/**
	 * Визначає, чи відповідає запис пошуковому запиту.
	 *
	 * @param array<string,mixed> $item Запис судді.
	 */
	private function matches_search( array $item, string $search ): bool {
		$needle = $this->normalize_search_string( $search );

		if ( '' === $needle ) {
			return true;
		}

		$haystacks = [
			(string) ( $item['FIO'] ?? '' ),
			(string) ( $item['FIOshort'] ?? '' ),
			(string) ( $item['Referee_NumOrder'] ?? '' ),
			(string) ( $item['CardNumber'] ?? '' ),
		];

		foreach ( $haystacks as $haystack ) {
			if ( '' !== $haystack && false !== mb_stripos( $this->normalize_search_string( $haystack ), $needle ) ) {
				return true;
			}
		}

		return false;
	}

	private function normalize_search_string( string $value ): string {
		$value = trim( preg_replace( '/\s+/u', ' ', $value ) ?? '' );

		return mb_strtolower( $value );
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function get_single_payload( int $referee_id ): ?array {
		$item = $this->repository->get_referee_by_id( $referee_id );

		if ( ! is_array( $item ) ) {
			return null;
		}

		$user_id               = (int) ( $item['User_ID'] ?? 0 );
		$item['certificates']  = $user_id > 0 ? $this->repository->get_certificates_by_user_id( $user_id ) : [];

		return $item;
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_dictionaries_payload(): array {
		return [
			'regions'        => $this->repository->get_regions(),
			'categories'     => $this->repository->get_categories(),
			'availableUsers' => $this->repository->get_available_users(),
			'calendars'      => $this->repository->get_calendars(),
		];
	}

	/**
	 * @param array<string,mixed> $filters Набір фільтрів протоколу.
	 * @return array<string,mixed>
	 */
	public function get_protocol_payload( array $filters ): array {
		$page      = max( 1, (int) ( $filters['page'] ?? 1 ) );
		$per_page  = max( 1, (int) ( $filters['per_page'] ?? 10 ) );
		$offset    = (int) ( $filters['offset'] ?? ( $page - 1 ) * $per_page );
		$search    = (string) ( $filters['search'] ?? '' );
		$log_names = [
			Referees_Protocol_Service::REFEREE_LOG_NAME,
			Referees_Protocol_Service::REFEREE_DOC_LOG_NAME,
		];
		$total     = $this->repository->count_protocol_items( $search, $log_names );
		$items     = $this->repository->get_protocol_items( $search, $per_page, $offset, $log_names );

		return [
			'items'       => $items,
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => max( 1, (int) ceil( $total / max( 1, $per_page ) ) ),
		];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function get_certificates_payload( int $user_id ): array {
		return $this->repository->get_certificates_by_user_id( $user_id );
	}

	/**
	 * @param array<string,mixed> $data Дані форми.
	 * @return array<string,int>
	 */
	public function create_referee( array $data ): array {
		$user_id = (int) ( $data['user_id'] ?? 0 );
		$category_id = (int) ( $data['referee_category_id'] ?? 0 );

		if ( ! $this->repository->user_exists( $user_id ) ) {
			throw new \RuntimeException( 'user_not_found' );
		}

		if ( ! $this->repository->category_exists( $category_id ) ) {
			throw new \RuntimeException( 'category_not_found' );
		}

		if ( $this->repository->get_referee_by_user_id( $user_id ) ) {
			throw new \RuntimeException( 'duplicate_referee_user' );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'START TRANSACTION' );

		try {
			$referee_id = $this->repository->insert_referee( $data );
			if ( $referee_id <= 0 ) {
				throw new \RuntimeException( 'referee_insert_failed' );
			}

			$fio = $this->repository->get_user_fio( $user_id );
			$this->protocol_service->log_action_transactional(
				Referees_Protocol_Service::REFEREE_LOG_NAME,
				'I',
				sprintf( 'Додано суддю: %s', '' !== $fio ? $fio : '#' . $user_id )
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'COMMIT' );
		} catch ( \Throwable $throwable ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'ROLLBACK' );
			$this->protocol_service->try_log_action(
				Referees_Protocol_Service::REFEREE_LOG_NAME,
				'I',
				sprintf( 'Помилка додавання судді: %s', '' !== $this->repository->get_user_fio( $user_id ) ? $this->repository->get_user_fio( $user_id ) : '#' . $user_id ),
				'error'
			);
			throw $throwable;
		}

		return [ 'referee_id' => $referee_id ];
	}

	/**
	 * @param array<string,mixed> $data Дані форми.
	 * @return array<string,int>
	 */
	public function update_referee( array $data ): array {
		$referee_id = (int) ( $data['referee_id'] ?? 0 );
		$category_id = (int) ( $data['referee_category_id'] ?? 0 );
		$existing   = $this->repository->get_referee_by_id( $referee_id );

		if ( ! is_array( $existing ) ) {
			throw new \RuntimeException( 'referee_not_found' );
		}

		if ( ! $this->repository->category_exists( $category_id ) ) {
			throw new \RuntimeException( 'category_not_found' );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'START TRANSACTION' );

		try {
			if ( ! $this->repository->update_referee( $referee_id, $data ) ) {
				throw new \RuntimeException( 'referee_update_failed' );
			}

			$this->protocol_service->log_action_transactional(
				Referees_Protocol_Service::REFEREE_LOG_NAME,
				'U',
				sprintf( 'Оновлено суддю: %s', (string) ( $existing['FIO'] ?? '#' . $referee_id ) )
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'COMMIT' );
		} catch ( \Throwable $throwable ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'ROLLBACK' );
			$this->protocol_service->try_log_action(
				Referees_Protocol_Service::REFEREE_LOG_NAME,
				'U',
				sprintf( 'Помилка оновлення судді: %s', (string) ( $existing['FIO'] ?? '#' . $referee_id ) ),
				'error'
			);
			throw $throwable;
		}

		return [ 'referee_id' => $referee_id ];
	}

	public function delete_referee( int $referee_id ): void {
		$existing = $this->repository->get_referee_by_id( $referee_id );
		if ( ! is_array( $existing ) ) {
			throw new \RuntimeException( 'referee_not_found' );
		}

		$user_id = (int) ( $existing['User_ID'] ?? 0 );
		if ( $user_id > 0 && $this->repository->count_referee_certificates( $user_id ) > 0 ) {
			$this->protocol_service->try_log_action(
				Referees_Protocol_Service::REFEREE_LOG_NAME,
				'D',
				sprintf( 'Заблоковано видалення судді: %s', (string) ( $existing['FIO'] ?? '#' . $referee_id ) ),
				'dependency'
			);

			throw new \RuntimeException( 'delete_blocked:Неможливо видалити суддю, поки існують довідки за суддівство.' );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'START TRANSACTION' );

		try {
			if ( ! $this->repository->delete_referee( $referee_id ) ) {
				throw new \RuntimeException( 'referee_delete_failed' );
			}

			$this->protocol_service->log_action_transactional(
				Referees_Protocol_Service::REFEREE_LOG_NAME,
				'D',
				sprintf( 'Видалено суддю: %s', (string) ( $existing['FIO'] ?? '#' . $referee_id ) )
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'COMMIT' );
		} catch ( \Throwable $throwable ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'ROLLBACK' );
			$this->protocol_service->try_log_action(
				Referees_Protocol_Service::REFEREE_LOG_NAME,
				'D',
				sprintf( 'Помилка видалення судді: %s', (string) ( $existing['FIO'] ?? '#' . $referee_id ) ),
				'error'
			);
			throw $throwable;
		}
	}

	/**
	 * @param array<string,mixed> $data Дані довідки.
	 * @return array<string,int>
	 */
	public function create_certificate( array $data ): array {
		$user_id     = (int) ( $data['user_id'] ?? 0 );
		$calendar_id = (int) ( $data['calendar_id'] ?? 0 );

		if ( ! $this->repository->get_referee_by_user_id( $user_id ) ) {
			throw new \RuntimeException( 'referee_not_found' );
		}

		if ( ! $this->repository->calendar_exists( $calendar_id ) ) {
			throw new \RuntimeException( 'calendar_not_found' );
		}

		if ( $this->repository->certificate_exists_for_user_calendar( $user_id, $calendar_id ) ) {
			throw new \RuntimeException( 'duplicate_certificate' );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'START TRANSACTION' );

		try {
			$certificate_id = $this->repository->insert_certificate( $data );
			if ( $certificate_id <= 0 ) {
				throw new \RuntimeException( 'certificate_insert_failed' );
			}

			$fio = $this->repository->get_user_fio( $user_id );
			$this->protocol_service->log_action_transactional(
				Referees_Protocol_Service::REFEREE_DOC_LOG_NAME,
				'I',
				sprintf( 'Додано довідку за суддівство для судді: %s', '' !== $fio ? $fio : '#' . $user_id )
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'COMMIT' );
		} catch ( \Throwable $throwable ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'ROLLBACK' );
			$this->protocol_service->try_log_action(
				Referees_Protocol_Service::REFEREE_DOC_LOG_NAME,
				'I',
				sprintf( 'Помилка додавання довідки судді: %s', '' !== $this->repository->get_user_fio( $user_id ) ? $this->repository->get_user_fio( $user_id ) : '#' . $user_id ),
				'error'
			);
			throw $throwable;
		}

		return [ 'certificate_id' => $certificate_id ];
	}

	public function bind_certificate_category( int $certificate_id, int $category_id ): void {
		$certificate = $this->repository->get_certificate_by_id( $certificate_id );
		if ( ! is_array( $certificate ) ) {
			throw new \RuntimeException( 'certificate_not_found' );
		}

		if ( ! $this->repository->category_exists( $category_id ) ) {
			throw new \RuntimeException( 'category_not_found' );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'START TRANSACTION' );

		try {
			if ( ! $this->repository->update_certificate_category( $certificate_id, $category_id ) ) {
				throw new \RuntimeException( 'certificate_bind_failed' );
			}

			$fio = $this->repository->get_user_fio( (int) $certificate['User_ID'] );
			$this->protocol_service->log_action_transactional(
				Referees_Protocol_Service::REFEREE_DOC_LOG_NAME,
				'U',
				sprintf( 'Оновлено категорію довідки судді: %s', '' !== $fio ? $fio : '#' . (int) $certificate['User_ID'] )
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'COMMIT' );
		} catch ( \Throwable $throwable ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'ROLLBACK' );
			$this->protocol_service->try_log_action(
				Referees_Protocol_Service::REFEREE_DOC_LOG_NAME,
				'U',
				sprintf( 'Помилка оновлення категорії довідки судді: %s', '' !== $fio ? $fio : '#' . (int) $certificate['User_ID'] ),
				'error'
			);
			throw $throwable;
		}
	}

	public function unbind_certificate_category( int $certificate_id ): void {
		$certificate = $this->repository->get_certificate_by_id( $certificate_id );
		if ( ! is_array( $certificate ) ) {
			throw new \RuntimeException( 'certificate_not_found' );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'START TRANSACTION' );

		try {
			if ( ! $this->repository->update_certificate_category( $certificate_id, null ) ) {
				throw new \RuntimeException( 'certificate_unbind_failed' );
			}

			$fio = $this->repository->get_user_fio( (int) $certificate['User_ID'] );
			$this->protocol_service->log_action_transactional(
				Referees_Protocol_Service::REFEREE_DOC_LOG_NAME,
				'D',
				sprintf( 'Знято прив’язку категорії з довідки судді: %s', '' !== $fio ? $fio : '#' . (int) $certificate['User_ID'] )
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'COMMIT' );
		} catch ( \Throwable $throwable ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'ROLLBACK' );
			$this->protocol_service->try_log_action(
				Referees_Protocol_Service::REFEREE_DOC_LOG_NAME,
				'D',
				sprintf( 'Помилка зняття прив’язки категорії довідки судді: %s', '' !== $fio ? $fio : '#' . (int) $certificate['User_ID'] ),
				'error'
			);
			throw $throwable;
		}
	}
}

