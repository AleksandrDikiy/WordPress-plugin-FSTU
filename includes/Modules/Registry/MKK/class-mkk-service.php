<?php
namespace FSTU\Modules\Registry\MKK;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service модуля «Реєстр членів МКК ФСТУ».
 * Інкапсулює бізнес-правила, транзакції й підготовку payload для views/AJAX.
 *
 * Version:     1.1.1
 * Date_update: 2026-04-12
 *
 * @package FSTU\Modules\Registry\MKK
 */
class MKK_Service {

	private const DEFAULT_PER_PAGE = 10;
	private const MAX_PER_PAGE     = 50;
	private const MAX_SEARCH       = 100;

	private MKK_Repository $repository;
	private MKK_Protocol_Service $protocol_service;

	public function __construct( ?MKK_Repository $repository = null, ?MKK_Protocol_Service $protocol_service = null ) {
		$this->repository       = $repository ?? new MKK_Repository();
		$this->protocol_service = $protocol_service ?? new MKK_Protocol_Service();
	}

	/**
	 * @param array<string,mixed> $args
	 * @return array<string,mixed>
	 */
	public function get_list_payload( array $args ): array {
		$page     = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per_page = min( max( 1, (int) ( $args['per_page'] ?? self::DEFAULT_PER_PAGE ) ), self::MAX_PER_PAGE );
		$filters  = [
			'region_id'          => (int) ( $args['region_id'] ?? 0 ),
			'commission_type_id' => (int) ( $args['commission_type_id'] ?? 0 ),
			'tourism_type_id'    => (int) ( $args['tourism_type_id'] ?? 0 ),
			'search'             => mb_substr( trim( (string) ( $args['search'] ?? '' ) ), 0, self::MAX_SEARCH ),
		];

		$total       = $this->repository->count_items( $filters );
		$total_pages = max( 1, (int) ceil( $total / max( 1, $per_page ) ) );
		$page        = min( $page, $total_pages );
		$offset      = ( $page - 1 ) * $per_page;
		$items       = $this->repository->get_items( $filters, $per_page, $offset );
		$is_guest    = ! is_user_logged_in();

		foreach ( $items as &$item ) {
			$item = $this->prepare_item( $item, $is_guest );
		}
		unset( $item );

		return [
			'items'       => $items,
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => $total_pages,
		];
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function get_single_payload( int $mkk_id ): ?array {
		$item = $this->repository->get_single_by_id( $mkk_id );

		if ( ! is_array( $item ) ) {
			return null;
		}

		$is_guest = ! is_user_logged_in();
		$item     = $this->prepare_item( $item, $is_guest );

		if ( $is_guest ) {
			$item['FIO'] = (string) ( $item['FIOshort'] ?? $item['DisplayFIO'] ?? '' );
		}

		$item['UserCreate_Display'] = trim( (string) ( $item['UserCreate_FIO'] ?? '' ) );

		if ( '' === (string) $item['UserCreate_Display'] && ! empty( $item['UserCreate'] ) ) {
			$item['UserCreate_Display'] = sprintf( 'ID %d', (int) $item['UserCreate'] );
		}

		return $item;
	}

	/**
	 * @return array<string,array<int,array<string,mixed>>>
	 */
	public function get_filters_payload(): array {
		return [
			'regions'         => $this->repository->get_region_options(),
			'commissionTypes' => $this->repository->get_commission_type_options(),
			'tourismTypes'    => $this->repository->get_tourism_type_options(),
		];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function search_users_payload( string $search, int $limit = 20 ): array {
		$limit  = max( 1, min( 20, $limit ) );
		$search = mb_substr( trim( $search ), 0, self::MAX_SEARCH );

		return $this->repository->search_users( $search, $limit );
	}

	/**
	 * @param array<string,mixed> $args
	 * @return array<string,mixed>
	 */
	public function get_protocol_payload( array $args ): array {
		$page     = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per_page = min( max( 1, (int) ( $args['per_page'] ?? self::DEFAULT_PER_PAGE ) ), self::MAX_PER_PAGE );
		$search   = mb_substr( trim( (string) ( $args['search'] ?? '' ) ), 0, self::MAX_SEARCH );

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
	public function create_item( array $data ): array {
		$payload = $this->validate_payload( $data, true );

		$this->begin_transaction();

		try {
			$mkk_id = $this->repository->create_item( $payload );
			$this->protocol_service->log_action_transactional(
				'I',
				sprintf( 'Додано запис МКК: %s', (string) $payload['user_fio'] )
			);
			$this->commit_transaction();

			return [ 'mkk_id' => $mkk_id ];
		} catch ( \Throwable $throwable ) {
			$this->rollback_transaction();
			$this->protocol_service->try_log_action( 'I', 'Помилка додавання запису МКК.', 'error' );
			throw $throwable;
		}
	}

	/**
	 * @param array<string,mixed> $data
	 * @return array<string,int>
	 */
	public function update_item( array $data ): array {
		$mkk_id = (int) ( $data['mkk_id'] ?? 0 );
		if ( $mkk_id <= 0 ) {
			throw new \RuntimeException( __( 'Невірний ідентифікатор запису.', 'fstu' ) );
		}

		$current = $this->repository->get_single_by_id( $mkk_id );
		if ( ! is_array( $current ) ) {
			throw new \RuntimeException( __( 'Запис не знайдено.', 'fstu' ) );
		}

		$data['user_id'] = (int) ( $current['User_ID'] ?? 0 );
		$payload         = $this->validate_payload( $data, false );
		$this->begin_transaction();

		try {
			$this->repository->update_item( $mkk_id, $payload );
			$this->protocol_service->log_action_transactional(
				'U',
				sprintf( 'Оновлено запис МКК: %s', (string) $payload['user_fio'] )
			);
			$this->commit_transaction();

			return [ 'mkk_id' => $mkk_id ];
		} catch ( \Throwable $throwable ) {
			$this->rollback_transaction();
			$this->protocol_service->try_log_action( 'U', 'Помилка оновлення запису МКК.', 'error' );
			throw $throwable;
		}
	}

	public function delete_item( int $mkk_id ): void {
		$current = $this->repository->get_single_by_id( $mkk_id );
		if ( ! is_array( $current ) ) {
			throw new \RuntimeException( __( 'Запис не знайдено.', 'fstu' ) );
		}

		$current = $this->prepare_item( $current, ! is_user_logged_in() );
		$dependency_check = $this->repository->check_delete_dependencies( $mkk_id );
		if ( empty( $dependency_check['can_delete'] ) ) {
			$this->protocol_service->try_log_action(
				'D',
				sprintf( 'Заблоковано видалення запису МКК: %s', (string) ( $current['DisplayFIO'] ?? '—' ) ),
				(string) ( $dependency_check['status'] ?? 'dependency' )
			);

			throw new \RuntimeException( (string) ( $dependency_check['message'] ?? __( 'Запис неможливо видалити.', 'fstu' ) ) );
		}

		$this->begin_transaction();

		try {
			$this->repository->delete_item( $mkk_id );
			$this->protocol_service->log_action_transactional(
				'D',
				sprintf( 'Видалено запис МКК: %s', (string) ( $current['DisplayFIO'] ?? '—' ) )
			);
			$this->commit_transaction();
		} catch ( \Throwable $throwable ) {
			$this->rollback_transaction();
			$this->protocol_service->try_log_action( 'D', 'Помилка видалення запису МКК.', 'error' );
			throw $throwable;
		}
	}

	/**
	 * @param array<string,mixed> $data
	 * @return array<string,mixed>
	 */
	private function validate_payload( array $data, bool $allow_user_change ): array {
		$user_id            = (int) ( $data['user_id'] ?? 0 );
		$region_id          = (int) ( $data['region_id'] ?? 0 );
		$commission_type_id = (int) ( $data['commission_type_id'] ?? 0 );
		$tourism_type_id    = (int) ( $data['tourism_type_id'] ?? 0 );

		if ( $user_id <= 0 ) {
			throw new \RuntimeException( __( 'Оберіть члена ФСТУ зі списку підказок.', 'fstu' ) );
		}

		if ( $region_id <= 0 ) {
			throw new \RuntimeException( __( 'Оберіть область.', 'fstu' ) );
		}

		if ( $commission_type_id <= 0 ) {
			throw new \RuntimeException( __( 'Оберіть тип комісії.', 'fstu' ) );
		}

		if ( $tourism_type_id <= 0 ) {
			throw new \RuntimeException( __( 'Оберіть вид туризму.', 'fstu' ) );
		}

		$user = $this->repository->get_user_by_id( $user_id );
		if ( ! is_array( $user ) ) {
			throw new \RuntimeException( __( 'Користувача не знайдено у реєстрі ФСТУ.', 'fstu' ) );
		}

		$region = $this->repository->get_region_by_id( $region_id );
		if ( ! is_array( $region ) ) {
			throw new \RuntimeException( __( 'Обрану область не знайдено у довіднику.', 'fstu' ) );
		}

		$commission_type = $this->repository->get_commission_type_by_id( $commission_type_id );
		if ( ! is_array( $commission_type ) ) {
			throw new \RuntimeException( __( 'Обраний тип комісії не знайдено у довіднику.', 'fstu' ) );
		}

		$tourism_type = $this->repository->get_tourism_type_by_id( $tourism_type_id );
		if ( ! is_array( $tourism_type ) ) {
			throw new \RuntimeException( __( 'Обраний вид туризму не знайдено у довіднику.', 'fstu' ) );
		}

		return [
			'user_id'            => $allow_user_change ? $user_id : (int) ( $user['User_ID'] ?? $user_id ),
			'region_id'          => $region_id,
			'commission_type_id' => $commission_type_id,
			'tourism_type_id'    => $tourism_type_id,
			'user_fio'           => (string) ( $user['FIO'] ?? '' ),
		];
	}

	/**
	 * @param array<string,mixed> $item
	 * @return array<string,mixed>
	 */
	private function prepare_item( array $item, bool $is_guest ): array {
		$fio = trim( (string) ( $item['FIO'] ?? '' ) );
		if ( '' === $fio ) {
			$fio = $this->build_person_name_from_parts(
				(string) ( $item['LastName'] ?? '' ),
				(string) ( $item['FirstName'] ?? '' ),
				(string) ( $item['Patronymic'] ?? '' )
			);
		}

		$fio_short = trim( (string) ( $item['FIOshort'] ?? '' ) );
		if ( '' === $fio_short ) {
			$fio_short = $this->build_short_person_name_from_parts(
				(string) ( $item['LastName'] ?? '' ),
				(string) ( $item['FirstName'] ?? '' ),
				(string) ( $item['Patronymic'] ?? '' )
			);
		}

		if ( '' === $fio_short && '' !== $fio ) {
			$fio_short = $this->build_short_person_name_from_full_name( $fio );
		}

		if ( '' === $fio && '' !== $fio_short ) {
			$fio = $fio_short;
		}

		$item['FIO']        = $fio;
		$item['FIOshort']   = $fio_short;
		$item['DisplayFIO'] = $is_guest
			? ( '' !== $fio_short ? $fio_short : $fio )
			: ( '' !== $fio ? $fio : $fio_short );
		$item['ProfileUrl'] = $this->build_profile_url( (int) ( $item['User_ID'] ?? 0 ) );

		return $item;
	}

	private function build_profile_url( int $user_id ): string {
		if ( $user_id <= 0 ) {
			return '';
		}

		return home_url( '/Personal/' ) . '?ViewID=' . $user_id;
	}

	private function build_person_name_from_parts( string $last_name, string $first_name, string $patronymic ): string {
		$parts = array_filter(
			[
				trim( $last_name ),
				trim( $first_name ),
				trim( $patronymic ),
			],
			static fn( $value ): bool => '' !== $value
		);

		return implode( ' ', $parts );
	}

	private function build_short_person_name_from_parts( string $last_name, string $first_name, string $patronymic ): string {
		$last_name  = trim( $last_name );
		$first_name = trim( $first_name );
		$patronymic = trim( $patronymic );
		$short      = $last_name;

		if ( '' !== $first_name ) {
			$short .= ( '' !== $short ? ' ' : '' ) . mb_substr( $first_name, 0, 1 ) . '.';
		}

		if ( '' !== $patronymic ) {
			$short .= mb_substr( $patronymic, 0, 1 ) . '.';
		}

		return trim( $short );
	}

	private function build_short_person_name_from_full_name( string $full_name ): string {
		$full_name = trim( preg_replace( '/\s+/u', ' ', $full_name ) ?? '' );

		if ( '' === $full_name ) {
			return '';
		}

		$parts = preg_split( '/\s+/u', $full_name ) ?: [];
		$parts = array_values(
			array_filter(
				array_map( 'trim', $parts ),
				static fn( string $value ): bool => '' !== $value
			)
		);

		if ( empty( $parts ) ) {
			return '';
		}

		$last_name = (string) array_shift( $parts );
		$short     = $last_name;

		foreach ( $parts as $part ) {
			$short .= ' ' . mb_substr( $part, 0, 1 ) . '.';
		}

		return trim( $short );
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

