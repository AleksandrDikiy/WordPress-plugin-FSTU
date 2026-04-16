<?php
namespace FSTU\Modules\Registry\Guidance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Сервіс модуля «Склад керівних органів ФСТУ».
 * Містить бізнес-правила і транзакційні CRUD-сценарії.
 *
 * Version:     1.1.0
 * Date_update: 2026-04-12
 *
 * @package FSTU\Modules\Registry\Guidance
 */
class Guidance_Service {

	private const DEFAULT_PER_PAGE = 10;
	private const MAX_PER_PAGE     = 50;

	private Guidance_Repository $repository;
	private Guidance_Protocol_Service $protocol_service;

	public function __construct( ?Guidance_Repository $repository = null, ?Guidance_Protocol_Service $protocol_service = null ) {
		$this->repository       = $repository instanceof Guidance_Repository ? $repository : new Guidance_Repository();
		$this->protocol_service = $protocol_service instanceof Guidance_Protocol_Service ? $protocol_service : new Guidance_Protocol_Service();
	}

	/**
	 * @param array<string,mixed> $filters
	 * @return array<string,mixed>
	 */
	public function get_list_payload( array $filters ): array {
		$page            = max( 1, (int) ( $filters['page'] ?? 1 ) );
		$per_page        = min( max( 1, (int) ( $filters['per_page'] ?? self::DEFAULT_PER_PAGE ) ), self::MAX_PER_PAGE );
		$typeguidance_id = (int) ( $filters['typeguidance_id'] ?? 1 );
		$search          = trim( (string) ( $filters['search'] ?? '' ) );

		$query_filters = [
			'typeguidance_id' => $typeguidance_id > 0 ? $typeguidance_id : 1,
			'search'          => $search,
		];

		$total = $this->repository->count_items( $query_filters );
		$total_pages = max( 1, (int) ceil( $total / max( 1, $per_page ) ) );
		$page = min( $page, $total_pages );
		$offset = ( $page - 1 ) * $per_page;
		$items = array_map( [ $this, 'prepare_item' ], $this->repository->get_items( $query_filters, $per_page, $offset ) );

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
	public function get_single_payload( int $guidance_id ): ?array {
		$item = $this->repository->get_single_by_id( $guidance_id );

		return is_array( $item ) ? $this->prepare_item( $item ) : null;
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_filters_payload(): array {
		$typeguidance_options = $this->repository->get_typeguidance_options();
		$default_id          = isset( $typeguidance_options[0]['TypeGuidance_ID'] ) ? (int) $typeguidance_options[0]['TypeGuidance_ID'] : 1;

		return [
			'typeguidance'   => $typeguidance_options,
			'defaults'       => [
				'typeguidance_id' => $default_id,
			],
			'memberGuidance' => $this->repository->get_member_guidance_options( $default_id ),
		];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function get_member_guidance_options_payload( int $typeguidance_id ): array {
		return $this->repository->get_member_guidance_options( $typeguidance_id > 0 ? $typeguidance_id : 1 );
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function search_users_payload( string $search, int $limit ): array {
		$items = $this->repository->search_users( $search, $limit );

		return array_map(
			static function ( array $item ): array {
				return [
					'user_id'    => (int) ( $item['User_ID'] ?? 0 ),
					'FIO'        => (string) ( $item['FIO'] ?? '' ),
					'user_email' => (string) ( $item['user_email'] ?? '' ),
				];
			},
			$items
		);
	}

	/**
	 * @param array<string,mixed> $data
	 * @return array<string,int>
	 */
	public function create_item( array $data ): array {
		$normalized = $this->validate_and_normalize_payload( $data );

		$this->begin_transaction();

		try {
			$guidance_id = $this->repository->create_item( $normalized );
			$this->protocol_service->log_action_transactional(
				'I',
				sprintf(
					'Додано запис складу керівних органів: %s / %s / %s',
					$normalized['typeguidance_name'],
					$normalized['member_guidance_name'],
					$normalized['user_fio']
				)
			);
			$this->commit_transaction();

			return [ 'guidance_id' => $guidance_id ];
		} catch ( \Throwable $throwable ) {
			$this->rollback_transaction();
			$this->protocol_service->try_log_action( 'I', 'Помилка додавання запису Guidance.', 'error' );
			throw $throwable;
		}
	}

	/**
	 * @param array<string,mixed> $data
	 * @return array<string,int>
	 */
	public function update_item( array $data ): array {
		$guidance_id = (int) ( $data['guidance_id'] ?? 0 );
		if ( $guidance_id <= 0 ) {
			throw new \RuntimeException( __( 'Невірний ідентифікатор запису.', 'fstu' ) );
		}

		$current = $this->repository->get_single_by_id( $guidance_id );
		if ( ! is_array( $current ) ) {
			throw new \RuntimeException( __( 'Запис не знайдено.', 'fstu' ) );
		}

        // Валідуємо дані. validate_and_normalize_payload вже містить новий user_id
        // ВІДЛАДКА: Перевіряємо, що прийшло з AJAX
        error_log( 'FSTU Guidance Debug - Вхідні дані: ' . print_r( $data, true ) );

        // Валідуємо дані. validate_and_normalize_payload вже містить новий user_id
        $normalized = $this->validate_and_normalize_payload( $data, $guidance_id );

        // ВІДЛАДКА: Перевіряємо дані після нормалізації (чи є там новий user_id та user_fio)
        error_log( 'FSTU Guidance Debug - Нормалізовані дані: ' . print_r( $normalized, true ) );

        $this->begin_transaction();

        try {
            // Виконуємо оновлення та перевіряємо, чи повернув репозиторій успіх (кількість змінених рядків або true)
            $updated = $this->repository->update_item( $guidance_id, $normalized );

            if ( false === $updated ) {
                throw new \RuntimeException( __( 'Не вдалося оновити запис у базі даних.', 'fstu' ) );
            }

            // Тільки при успішному оновленні основної таблиці пишемо в лог
            $this->protocol_service->log_action_transactional(
                'U',
                sprintf(
                    'Оновлено запис складу керівних органів: %s / %s / %s',
                    $normalized['typeguidance_name'],
                    $normalized['member_guidance_name'],
                    $normalized['user_fio']
                ),
                '✓'
            );

            $this->commit_transaction();

            return [ 'guidance_id' => $guidance_id ];

		} catch ( \Throwable $throwable ) {
			$this->rollback_transaction();
			$this->protocol_service->try_log_action( 'U', 'Помилка оновлення запису Guidance.', 'error' );
			throw $throwable;
		}
	}

	public function delete_item( int $guidance_id ): void {
		$current = $this->repository->get_single_by_id( $guidance_id );
		if ( ! is_array( $current ) ) {
			throw new \RuntimeException( __( 'Запис не знайдено.', 'fstu' ) );
		}

		$dependency_check = $this->repository->check_delete_dependencies( $guidance_id );
		if ( empty( $dependency_check['can_delete'] ) ) {
			$this->protocol_service->try_log_action(
				'D',
				sprintf( 'Заблоковано видалення запису Guidance: %s', (string) ( $current['FIO'] ?? '—' ) ),
				(string) ( $dependency_check['status'] ?? 'dependency' )
			);
			throw new \RuntimeException( (string) ( $dependency_check['message'] ?? __( 'Запис неможливо видалити.', 'fstu' ) ) );
		}

		$this->begin_transaction();

		try {
			$this->repository->delete_item( $guidance_id );
			$this->protocol_service->log_action_transactional(
				'D',
				sprintf(
					'Видалено запис складу керівних органів: %s / %s / %s',
					(string) ( $current['TypeGuidance_Name'] ?? '—' ),
					(string) ( $current['MemberGuidance_Name'] ?? '—' ),
					(string) ( $current['FIO'] ?? '—' )
				)
			);
			$this->commit_transaction();
		} catch ( \Throwable $throwable ) {
			$this->rollback_transaction();
			$this->protocol_service->try_log_action( 'D', 'Помилка видалення запису Guidance.', 'error' );
			throw $throwable;
		}
	}

	/**
	 * @param array<string,mixed> $filters
	 * @return array<string,mixed>
	 */
	public function get_protocol_payload( array $filters ): array {
		$page     = max( 1, (int) ( $filters['page'] ?? 1 ) );
		$per_page = min( max( 1, (int) ( $filters['per_page'] ?? self::DEFAULT_PER_PAGE ) ), self::MAX_PER_PAGE );
		$search   = trim( (string) ( $filters['search'] ?? '' ) );
		$total    = $this->repository->count_protocol_items( $search );
		$total_pages = max( 1, (int) ceil( $total / max( 1, $per_page ) ) );
		$page = min( $page, $total_pages );
		$offset = ( $page - 1 ) * $per_page;
		$items    = $this->repository->get_protocol_items( $search, $per_page, $offset );

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
	 * @return array<string,mixed>
	 */
	private function validate_and_normalize_payload( array $data, int $exclude_guidance_id = 0 ): array {
		$typeguidance_id   = (int) ( $data['typeguidance_id'] ?? 0 );
		$member_guidance_id = (int) ( $data['member_guidance_id'] ?? 0 );
		$user_id           = (int) ( $data['user_id'] ?? 0 );
		$guidance_notes    = sanitize_textarea_field( (string) ( $data['guidance_notes'] ?? '' ) );

		if ( $typeguidance_id <= 0 ) {
			throw new \RuntimeException( __( 'Оберіть керівний орган.', 'fstu' ) );
		}

		if ( $member_guidance_id <= 0 ) {
			throw new \RuntimeException( __( 'Оберіть посаду.', 'fstu' ) );
		}

		if ( $user_id <= 0 ) {
			throw new \RuntimeException( __( 'Оберіть користувача зі списку підказок.', 'fstu' ) );
		}

		$typeguidance = $this->repository->get_typeguidance_by_id( $typeguidance_id );
		if ( ! is_array( $typeguidance ) ) {
			throw new \RuntimeException( __( 'Керівний орган не знайдено.', 'fstu' ) );
		}

		$member_guidance = $this->repository->get_member_guidance_by_id( $member_guidance_id );
		if ( ! is_array( $member_guidance ) ) {
			throw new \RuntimeException( __( 'Посаду не знайдено.', 'fstu' ) );
		}

		if ( ! $this->repository->member_guidance_belongs_to_type( $member_guidance_id, $typeguidance_id ) ) {
			throw new \RuntimeException( __( 'Обрана посада не належить до цього керівного органу.', 'fstu' ) );
		}

		$user = $this->repository->get_user_by_id( $user_id );
		if ( ! is_array( $user ) ) {
			throw new \RuntimeException( __( 'Користувача не знайдено.', 'fstu' ) );
		}

		if ( $this->repository->relation_exists( $typeguidance_id, $member_guidance_id, $user_id, $exclude_guidance_id ) ) {
			throw new \RuntimeException( __( 'Такий запис уже існує.', 'fstu' ) );
		}

        return [
            'TypeGuidance_ID'    => $typeguidance_id, // Ключі як у таблиці БД
            'typeguidance_name'  => (string) ( $typeguidance['TypeGuidance_Name'] ?? '' ),
            'MemberGuidance_ID'  => $member_guidance_id,
            'member_guidance_name' => (string) ( $member_guidance['MemberGuidance_Name'] ?? '' ),
            'User_ID'            => $user_id, // Legacy БД часто очікує саме такий регістр
            'user_fio'           => (string) ( $user['FIO'] ?? '' ),
            'Guidance_Notes'     => $guidance_notes,
        ];
	}

	/**
	 * @param array<string,mixed> $item
	 * @return array<string,mixed>
	 */
	private function prepare_item( array $item ): array {
		$item['Guidance_ID']        = (int) ( $item['Guidance_ID'] ?? 0 );
		$item['User_ID']            = (int) ( $item['User_ID'] ?? 0 );
		$item['TypeGuidance_ID']    = (int) ( $item['TypeGuidance_ID'] ?? 0 );
		$item['MemberGuidance_ID']  = (int) ( $item['MemberGuidance_ID'] ?? 0 );
		$item['FIO']                = trim( (string) ( $item['FIO'] ?? '' ) );
		$item['TypeGuidance_Name']  = trim( (string) ( $item['TypeGuidance_Name'] ?? '' ) );
		$item['MemberGuidance_Name'] = trim( (string) ( $item['MemberGuidance_Name'] ?? '' ) );
		$item['Guidance_Notes']     = trim( (string) ( $item['Guidance_Notes'] ?? '' ) );
		$item['Guidance_DateCreate'] = trim( (string) ( $item['Guidance_DateCreate'] ?? '' ) );
		$item['user_email']         = trim( (string) ( $item['user_email'] ?? '' ) );
		$phones = [];

		foreach ( [ 'PhoneMobile', 'Phone2', 'Phone3' ] as $key ) {
			$value = trim( (string) ( $item[ $key ] ?? '' ) );
			if ( '' !== $value && ! in_array( $value, $phones, true ) ) {
				$phones[] = $value;
			}
		}

		$item['Phones']     = implode( "\n", $phones );
		$item['ProfileUrl'] = (int) $item['User_ID'] > 0 ? home_url( '/Personal/?ViewID=' . absint( $item['User_ID'] ) ) : '';

		return $item;
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

