<?php
namespace FSTU\Modules\Registry\Recorders;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service модуля «Реєстратори».
 * Інкапсулює бізнес-правила, транзакції та синхронізацію ролей.
 *
 * Version:     1.0.2
 * Date_update: 2026-04-11
 *
 * @package FSTU\Modules\Registry\Recorders
 */
class Recorders_Service {

	private const DEFAULT_PER_PAGE = 10;
	private const MAX_PER_PAGE     = 50;

	private Recorders_Repository $repository;
	private Recorders_Protocol_Service $protocol_service;

	public function __construct( ?Recorders_Repository $repository = null, ?Recorders_Protocol_Service $protocol_service = null ) {
		$this->repository       = $repository ?? new Recorders_Repository();
		$this->protocol_service = $protocol_service ?? new Recorders_Protocol_Service();
	}

	/**
	 * @param array<string,mixed> $args
	 * @return array<string,mixed>
	 */
	public function get_list_payload( array $args ): array {
		$page     = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per_page = min( max( 1, (int) ( $args['per_page'] ?? self::DEFAULT_PER_PAGE ) ), self::MAX_PER_PAGE );
		$filters  = [
			'search'  => trim( (string) ( $args['search'] ?? '' ) ),
			'unit_id' => (int) ( $args['unit_id'] ?? 0 ),
		];

		$total       = $this->repository->count_items( $filters );
		$total_pages = max( 1, (int) ceil( $total / max( 1, $per_page ) ) );
		$page        = min( $page, $total_pages );
		$offset      = ( $page - 1 ) * $per_page;
		$items       = $this->repository->get_items( $filters, $per_page, $offset );

		return [
			'items'       => $items,
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => $total_pages,
		];
	}

	public function get_single_payload( int $user_region_id ): ?array {
		$item = $this->repository->get_single_by_id( $user_region_id );

		if ( ! is_array( $item ) ) {
			return null;
		}

		$item['profile_url'] = $this->build_profile_url( (int) ( $item['User_ID'] ?? 0 ) );

		return $item;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function get_units_payload(): array {
		return $this->repository->get_unit_options();
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function search_candidates_payload( string $search, int $limit = 20 ): array {
		$search = trim( $search );
		$limit  = max( 1, min( 20, $limit ) );

		return $this->repository->search_candidates( $search, $limit );
	}

	/**
	 * @param array<string,mixed> $args
	 * @return array<string,mixed>
	 */
	public function get_protocol_payload( array $args ): array {
		$page     = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per_page = min( max( 1, (int) ( $args['per_page'] ?? self::DEFAULT_PER_PAGE ) ), self::MAX_PER_PAGE );
		$search   = trim( (string) ( $args['search'] ?? '' ) );

		$total       = $this->repository->count_protocol_items( $search );
		$total_pages = max( 1, (int) ceil( $total / max( 1, $per_page ) ) );
		$page        = min( $page, $total_pages );
		$offset      = ( $page - 1 ) * $per_page;
		$items       = $this->repository->get_protocol_items( $search, $per_page, $offset );

		return [
			'items'       => $items,
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => $total_pages,
		];
	}

	/**
	 * @param array<string,mixed> $data
	 * @return array<string,int>
	 */
	public function create_relation( array $data ): array {
		$payload = $this->validate_and_enrich_payload( $data, false );

		$this->begin_transaction();

		try {
			$relation_id = $this->repository->create_relation( $payload );
			$this->grant_registrar_role( (int) $payload['user_id'] );
			$this->protocol_service->log_action_transactional(
				'I',
				sprintf(
					'Додано реєстратора осередку «%s»: %s',
					(string) $payload['unit_name'],
					(string) $payload['user_fio']
				)
			);
			$this->commit_transaction();

			return [ 'relation_id' => $relation_id ];
		} catch ( \Throwable $throwable ) {
			$this->rollback_transaction();
			$this->protocol_service->try_log_action( 'I', 'Помилка додавання реєстратора осередку.', 'error' );
			throw $throwable;
		}
	}

	/**
	 * @param array<string,mixed> $data
	 * @return array<string,int>
	 */
	public function update_relation( array $data ): array {
		$payload = $this->validate_and_enrich_payload( $data, true );
		$current = $this->repository->get_single_by_id( (int) $payload['user_region_id'] );

		if ( ! is_array( $current ) ) {
			throw new \RuntimeException( __( 'Запис не знайдено.', 'fstu' ) );
		}

		$old_user_id   = (int) ( $current['User_ID'] ?? 0 );
		$old_user_fio  = (string) ( $current['FIO'] ?? '' );
		$old_unit_name = (string) ( $current['Unit_ShortName'] ?? '' );
		$payload['unit_id']   = (int) ( $current['Unit_ID'] ?? 0 );
		$payload['region_id'] = (int) ( $current['Region_ID'] ?? 0 );
		$payload['unit_name'] = $old_unit_name;

		$this->begin_transaction();

		try {
			$this->repository->update_relation( (int) $payload['user_region_id'], $payload );

			if ( $old_user_id !== (int) $payload['user_id'] && $old_user_id > 0 ) {
				$this->maybe_revoke_registrar_role( $old_user_id );
			}

			$this->grant_registrar_role( (int) $payload['user_id'] );
			$this->protocol_service->log_action_transactional(
				'U',
				sprintf(
					'Оновлено реєстратора осередку «%s»: %s → %s',
					'' !== $old_unit_name ? $old_unit_name : (string) $payload['unit_name'],
					'' !== $old_user_fio ? $old_user_fio : '—',
					(string) $payload['user_fio']
				)
			);
			$this->commit_transaction();

			return [ 'relation_id' => (int) $payload['user_region_id'] ];
		} catch ( \Throwable $throwable ) {
			$this->rollback_transaction();
			$this->protocol_service->try_log_action( 'U', 'Помилка оновлення призначення реєстратора.', 'error' );
			throw $throwable;
		}
	}

	public function delete_relation( int $user_region_id ): void {
		$current = $this->repository->get_single_by_id( $user_region_id );

		if ( ! is_array( $current ) ) {
			throw new \RuntimeException( __( 'Запис не знайдено.', 'fstu' ) );
		}

		$unit_name = (string) ( $current['Unit_ShortName'] ?? '' );
		$user_fio  = (string) ( $current['FIO'] ?? '' );
		$user_id   = (int) ( $current['User_ID'] ?? 0 );

		$this->begin_transaction();

		try {
			$this->repository->delete_relation( $user_region_id );
			if ( $user_id > 0 ) {
				$this->maybe_revoke_registrar_role( $user_id );
			}
			$this->protocol_service->log_action_transactional(
				'D',
				sprintf(
					'Видалено призначення реєстратора для осередку «%s»: %s',
					$unit_name,
					$user_fio
				)
			);
			$this->commit_transaction();
		} catch ( \Throwable $throwable ) {
			$this->rollback_transaction();
			$this->protocol_service->try_log_action( 'D', 'Помилка видалення призначення реєстратора.', 'error' );
			throw $throwable;
		}
	}

	/**
	 * @param array<string,mixed> $data
	 * @return array<string,mixed>
	 */
	private function validate_and_enrich_payload( array $data, bool $is_update ): array {
		$user_region_id = (int) ( $data['user_region_id'] ?? 0 );
		$unit_id        = (int) ( $data['unit_id'] ?? 0 );
		$user_id        = (int) ( $data['user_id'] ?? 0 );

		if ( $is_update && $user_region_id <= 0 ) {
			throw new \RuntimeException( __( 'Невірний ідентифікатор запису.', 'fstu' ) );
		}

		if ( $unit_id <= 0 ) {
			throw new \RuntimeException( __( 'Оберіть осередок ФСТУ.', 'fstu' ) );
		}

		if ( $user_id <= 0 ) {
			throw new \RuntimeException( __( 'Оберіть реєстратора зі списку підказок.', 'fstu' ) );
		}

		$candidate = $this->repository->get_candidate_by_user_id( $user_id );
		if ( ! is_array( $candidate ) ) {
			throw new \RuntimeException( __( 'Користувача не знайдено у джерелі `vUserFSTU`.', 'fstu' ) );
		}

		$this->assert_wp_user_exists( $user_id );

		$region_id = $this->repository->get_region_id_by_unit_id( $unit_id );
		if ( $region_id <= 0 ) {
			throw new \RuntimeException( __( 'Не вдалося визначити область для осередку.', 'fstu' ) );
		}

		if ( $this->repository->relation_exists( $unit_id, $user_id, $user_region_id ) ) {
			throw new \RuntimeException( __( 'Такий реєстратор уже призначений цьому осередку.', 'fstu' ) );
		}

		$unit_name = $this->find_unit_name( $unit_id );

		return [
			'user_region_id' => $user_region_id,
			'unit_id'        => $unit_id,
			'region_id'      => $region_id,
			'user_id'        => $user_id,
			'user_fio'       => (string) ( $candidate['FIO'] ?? '' ),
			'unit_name'      => $unit_name,
		];
	}

	private function find_unit_name( int $unit_id ): string {
		foreach ( $this->repository->get_unit_options() as $unit ) {
			if ( (int) ( $unit['Unit_ID'] ?? 0 ) === $unit_id ) {
				return (string) ( $unit['Unit_ShortName'] ?? '' );
			}
		}

		return '';
	}

	private function build_profile_url( int $user_id ): string {
		if ( $user_id <= 0 ) {
			return '';
		}

		return home_url( '/Personal' ) . '?ViewID=' . $user_id;
	}

	private function assert_wp_user_exists( int $user_id ): void {
		if ( $user_id <= 0 ) {
			throw new \RuntimeException( __( 'Невірний користувач реєстратора.', 'fstu' ) );
		}

		$user = get_userdata( $user_id );
		if ( ! $user instanceof \WP_User ) {
			throw new \RuntimeException( __( 'Користувач WordPress для обраного реєстратора не знайдений.', 'fstu' ) );
		}
	}

	private function grant_registrar_role( int $user_id ): void {
		$user = get_userdata( $user_id );
		if ( ! $user instanceof \WP_User ) {
			throw new \RuntimeException( 'recorders_role_user_not_found' );
		}

		if ( in_array( 'userregistrar', (array) $user->roles, true ) ) {
			return;
		}

		$user->add_role( 'userregistrar' );
		$this->assert_user_has_role( $user_id, 'userregistrar' );
	}

	private function maybe_revoke_registrar_role( int $user_id ): void {
		if ( $this->repository->count_relations_by_user_id( $user_id ) > 0 ) {
			return;
		}

		$user = get_userdata( $user_id );
		if ( ! $user instanceof \WP_User ) {
			return;
		}

		if ( ! in_array( 'userregistrar', (array) $user->roles, true ) ) {
			return;
		}

		$user->remove_role( 'userregistrar' );
		$this->assert_user_not_has_role( $user_id, 'userregistrar' );
	}

	private function assert_user_has_role( int $user_id, string $role ): void {
		$user = get_userdata( $user_id );

		if ( ! $user instanceof \WP_User || ! in_array( $role, (array) $user->roles, true ) ) {
			throw new \RuntimeException( 'recorders_role_grant_failed' );
		}
	}

	private function assert_user_not_has_role( int $user_id, string $role ): void {
		$user = get_userdata( $user_id );

		if ( $user instanceof \WP_User && in_array( $role, (array) $user->roles, true ) ) {
			throw new \RuntimeException( 'recorders_role_revoke_failed' );
		}
	}

	private function begin_transaction(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'START TRANSACTION' );
	}

	private function commit_transaction(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'COMMIT' );
	}

	private function rollback_transaction(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'ROLLBACK' );
	}
}

