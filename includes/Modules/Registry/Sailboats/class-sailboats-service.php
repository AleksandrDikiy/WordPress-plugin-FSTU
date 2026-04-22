<?php
namespace FSTU\Modules\Registry\Sailboats;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Бізнес-сервіс модуля "Судновий реєстр ФСТУ".
 * Оркеструє список, перегляд, а також transactional create/update-flow.
 *
 * Version:     1.7.2
 * Date_update: 2026-04-14
 *
 * @package FSTU\Modules\UserFstu\Sailboats
 */
class Sailboats_Service {

	private const LOG_TYPE_INSERT = 'I'; // Було 'INSERT'
	private const LOG_TYPE_UPDATE = 'U'; // Було 'UPDATE'
	private const LOG_TYPE_DELETE = 'D'; // Було 'DELETE'

	private Sailboats_Repository $repository;
	private Sailboats_Protocol_Service $protocol_service;
	private Sailboats_Notification_Service $notification_service;

	public function __construct(
		Sailboats_Repository $repository,
		Sailboats_Protocol_Service $protocol_service,
		Sailboats_Notification_Service $notification_service
	) {
		$this->repository           = $repository;
		$this->protocol_service     = $protocol_service;
		$this->notification_service = $notification_service;
	}

	/**
	 * Повертає payload списку суден.
	 *
	 * @param array<string,mixed> $args Параметри списку.
	 * @return array{items: array<int,array<string,mixed>>, total: int, page: int, per_page: int, total_pages: int}
	 */
	public function get_list_payload( array $args ): array {
		$result   = $this->repository->get_sailboats_list( $args );
		$page     = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per_page = max( 1, (int) ( $args['per_page'] ?? 10 ) );
		$total    = (int) ( $result['total'] ?? 0 );

		return [
			'items'       => is_array( $result['items'] ?? null ) ? $result['items'] : [],
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => max( 1, (int) ceil( $total / max( 1, $per_page ) ) ),
		];
	}

	/**
	 * Повертає картку судна.
	 *
	 * @return array<string,mixed>|null
	 */
	public function get_single_payload( int $sailboat_id ): ?array {
		return $this->repository->get_sailboat_by_id( $sailboat_id );
	}

	/**
	 * Повертає payload протоколу.
	 *
	 * @param array<string,mixed> $args Параметри протоколу.
	 * @return array{items: array<int,array<string,mixed>>, total: int, page: int, per_page: int, total_pages: int}
	 */
	public function get_protocol_payload( array $args ): array {
		$result   = $this->repository->get_protocol_items( $args );
		$page     = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per_page = max( 1, (int) ( $args['per_page'] ?? 10 ) );
		$total    = (int) ( $result['total'] ?? 0 );

		return [
			'items'       => is_array( $result['items'] ?? null ) ? $result['items'] : [],
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => max( 1, (int) ceil( $total / max( 1, $per_page ) ) ),
		];
	}

	/**
	 * Повертає словники форми.
	 *
	 * @return array<string,array<int,array<string,mixed>>>
	 */
	public function get_dictionaries_payload(): array {
		return $this->repository->get_dictionaries();
	}

	/**
	 * Повертає кандидатів для сценарію створення заявки по існуючому судну.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function search_existing_sailboats_payload( string $search, int $limit = 15 ): array {
		return $this->repository->search_existing_sailboats( $search, $limit );
	}

	/**
	 * Створює новий запис модуля.
	 *
	 * @param array<string,mixed> $data Дані форми.
	 * @return array<string,int>
	 */
	public function create_item( array $data ): array {
		global $wpdb;

		$create_mode  = (string) ( $data['create_mode'] ?? 'new' );
		$sailboat_id  = 0;
		$app_item_id  = 0;

		$this->begin_transaction();

		try {
			if ( 'existing' === $create_mode ) {
				$sailboat_id = (int) ( $data['existing_sailboat_id'] ?? 0 );
				if ( $sailboat_id <= 0 || ! $this->repository->sailboat_exists( $sailboat_id ) ) {
					throw new \RuntimeException( 'existing_sailboat_not_found' );
				}

				if ( $this->repository->has_active_application_ship_ticket_for_sailboat( $sailboat_id ) ) {
					throw new \RuntimeException( 'existing_sailboat_has_active_application' );
				}
			} else {
				$sailboat_id = $this->repository->insert_sailboat( $data );
			}

			$app_item_id = $this->repository->insert_application_ship_ticket( $data, $sailboat_id );
			$this->repository->sync_sailboat_application_link( $sailboat_id, $app_item_id );

			$this->protocol_service->log_action(
				self::LOG_TYPE_INSERT,
				sprintf( 'Створено запис судна "%s" (AppShipTicket_ID:%d, Sailboat_ID:%d)', (string) $data['sailboat_name'], $app_item_id, $sailboat_id ),
				Sailboats_Protocol_Service::STATUS_SUCCESS,
				true
			);

			$this->commit_transaction();
		} catch ( \Throwable $throwable ) {
			$this->rollback_transaction();
			$this->protocol_service->try_log_action(
				self::LOG_TYPE_INSERT,
				sprintf( 'Помилка створення запису судна "%s" [%s]', (string) ( $data['sailboat_name'] ?? '' ), $this->get_error_marker( $throwable, 'create_failed' ) ),
				'error'
			);
			throw $throwable;
		}

		return [
			'item_id'     => $app_item_id,
			'sailboat_id' => $sailboat_id,
		];
	}

	/**
	 * Оновлює існуючий запис модуля.
	 *
	 * @param array<string,mixed> $data Дані форми.
	 * @return array<string,int>
	 */
	public function update_item( array $data ): array {
		$item_id = (int) ( $data['item_id'] ?? 0 );
		if ( $item_id <= 0 ) {
			throw new \RuntimeException( 'item_not_found' );
		}

		$context = $this->repository->get_item_context( $item_id );
		if ( ! is_array( $context ) ) {
			throw new \RuntimeException( 'item_not_found' );
		}

		$this->begin_transaction();

		try {
			$this->repository->update_sailboat( (int) ( $context['sailboat_id'] ?? 0 ), $data );
			$this->repository->update_application_ship_ticket( (int) ( $context['appshipticket_id'] ?? 0 ), $data );

			$this->protocol_service->log_action(
				self::LOG_TYPE_UPDATE,
				sprintf( 'Оновлено запис судна "%s" (AppShipTicket_ID:%d)', (string) $data['sailboat_name'], (int) ( $context['appshipticket_id'] ?? 0 ) ),
				Sailboats_Protocol_Service::STATUS_SUCCESS,
				true
			);

			$this->commit_transaction();
		} catch ( \Throwable $throwable ) {
			$this->rollback_transaction();
			$this->protocol_service->try_log_action(
				self::LOG_TYPE_UPDATE,
				sprintf( 'Помилка оновлення запису судна "%s" [%s]', (string) ( $data['sailboat_name'] ?? '' ), $this->get_error_marker( $throwable, 'update_failed' ) ),
				'error'
			);
			throw $throwable;
		}

		return [
			'item_id'     => (int) ( $context['appshipticket_id'] ?? 0 ),
			'sailboat_id' => (int) ( $context['sailboat_id'] ?? 0 ),
		];
	}

	/**
	 * Оновлює статус заявки / суднового квитка.
	 *
	 * @param array<string,mixed> $data Дані дії.
	 */
	public function update_status( array $data ): bool {
		$context = $this->require_item_context( (int) ( $data['item_id'] ?? 0 ) );

		$this->begin_transaction();

		try {
			$this->repository->update_application_status(
				(int) $context['appshipticket_id'],
				(int) $data['verification_id'],
				(string) ( $data['comment'] ?? '' )
			);

			$this->protocol_service->log_action(
				self::LOG_TYPE_UPDATE,
				sprintf( 'Оновлено статус запису судна (AppShipTicket_ID:%d, Verification_ID:%d)', (int) $context['appshipticket_id'], (int) $data['verification_id'] ),
				Sailboats_Protocol_Service::STATUS_SUCCESS,
				true
			);

			$this->commit_transaction();

			return true;
		} catch ( \Throwable $throwable ) {
			$this->rollback_transaction();
			$this->protocol_service->try_log_action(
				self::LOG_TYPE_UPDATE,
				sprintf( 'Помилка зміни статусу запису судна (AppShipTicket_ID:%d) [%s]', (int) $context['appshipticket_id'], $this->get_error_marker( $throwable, 'status_failed' ) ),
				'error'
			);
			throw $throwable;
		}
	}

	/**
	 * Фіксує оплату по заявці / судновому квитку.
	 *
	 * @param array<string,mixed> $data Дані дії.
	 */
	public function set_payment( array $data ): bool {
		$context = $this->require_item_context( (int) ( $data['item_id'] ?? 0 ) );
		$this->assert_service_action_date_is_valid( $context, (string) ( $data['payment_date'] ?? '' ), 'payment' );

		$this->begin_transaction();

		try {
			$this->repository->set_application_payment(
				(int) $context['appshipticket_id'],
				(float) $data['payment_amount'],
				(string) $data['payment_date'],
				(string) $data['payment_slot'],
				(string) ( $data['comment'] ?? '' )
			);

			$this->protocol_service->log_action(
				self::LOG_TYPE_UPDATE,
				sprintf( 'Збережено оплату %s для запису судна (AppShipTicket_ID:%d, сума:%s)', (string) $data['payment_slot'], (int) $context['appshipticket_id'], (string) $data['payment_amount'] ),
				Sailboats_Protocol_Service::STATUS_SUCCESS,
				true
			);

			$this->commit_transaction();

			return true;
		} catch ( \Throwable $throwable ) {
			$this->rollback_transaction();
			$this->protocol_service->try_log_action(
				self::LOG_TYPE_UPDATE,
				sprintf( 'Помилка збереження оплати для запису судна (AppShipTicket_ID:%d) [%s]', (int) $context['appshipticket_id'], $this->get_error_marker( $throwable, 'payment_failed' ) ),
				'error'
			);
			throw $throwable;
		}
	}

	/**
	 * Фіксує вручення / доставку документа.
	 *
	 * @param array<string,mixed> $data Дані дії.
	 */
    /**
     * Фіксує вручення / доставку документа.
     *
     * @param array<string,mixed> $data Дані дії.
     */
    public function mark_received( array $data ): bool {
        $context = $this->require_item_context( (int) ( $data['item_id'] ?? 0 ) );
        $this->assert_service_action_date_is_valid( $context, (string) ( $data['received_at'] ?? '' ), 'received' );

        $this->begin_transaction();

        try {
            // 1. Записуємо дату вручення
            $this->repository->mark_application_received(
                (int) $context['appshipticket_id'],
                (string) $data['received_at'],
                (string) ( $data['comment'] ?? '' )
            );

            // 2. КРИТИЧНЕ ВИПРАВЛЕННЯ: Оновлюємо статус на "Доставлено одержувачу" (ID 7)
            $this->repository->update_application_status(
                (int) $context['appshipticket_id'],
                7, // 7 - це стандартний ID статусу "Доставлено одержувачу"
                (string) ( $data['comment'] ?? '' )
            );

            $this->protocol_service->log_action(
                self::LOG_TYPE_UPDATE,
                sprintf( 'Позначено вручення документа та змінено статус на "Доставлено" (AppShipTicket_ID:%d)', (int) $context['appshipticket_id'] ),
                Sailboats_Protocol_Service::STATUS_SUCCESS,
                true
            );

            $this->commit_transaction();

            return true;
        } catch ( \Throwable $throwable ) {
            $this->rollback_transaction();
            $this->protocol_service->try_log_action(
                self::LOG_TYPE_UPDATE,
                sprintf( 'Помилка фіксації вручення документа (AppShipTicket_ID:%d) [%s]', (int) $context['appshipticket_id'], $this->get_error_marker( $throwable, 'received_failed' ) ),
                'error'
            );
            throw $throwable;
        }
    }

	/**
	 * Фіксує продаж / вибуття судна.
	 *
	 * @param array<string,mixed> $data Дані дії.
	 */
	public function mark_sale( array $data ): bool {
		$context = $this->require_item_context( (int) ( $data['item_id'] ?? 0 ) );
		$this->assert_service_action_date_is_valid( $context, (string) ( $data['sale_date'] ?? '' ), 'sale' );

		$this->begin_transaction();

		try {
			$this->repository->mark_application_sale(
				(int) $context['appshipticket_id'],
				(string) $data['sale_date'],
				(string) ( $data['comment'] ?? '' )
			);

			$this->protocol_service->log_action(
				self::LOG_TYPE_UPDATE,
				sprintf( 'Зафіксовано продаж / вибуття судна (AppShipTicket_ID:%d)', (int) $context['appshipticket_id'] ),
				Sailboats_Protocol_Service::STATUS_SUCCESS,
				true
			);

			$this->commit_transaction();

			return true;
		} catch ( \Throwable $throwable ) {
			$this->rollback_transaction();
			$this->protocol_service->try_log_action(
				self::LOG_TYPE_UPDATE,
				sprintf( 'Помилка фіксації продажу / вибуття судна (AppShipTicket_ID:%d) [%s]', (int) $context['appshipticket_id'], $this->get_error_marker( $throwable, 'sale_failed' ) ),
				'error'
			);
			throw $throwable;
		}
	}

	/**
	 * Виконує hard delete запису allowlist-каскадом.
	 *
	 * @param array<string,mixed> $data Дані дії.
	 */
	public function delete_item( array $data ): bool {
		$item_id = (int) ( $data['item_id'] ?? 0 );
		$plan    = $this->repository->check_delete_dependencies( $item_id );

		if ( empty( $plan['can_delete'] ) ) {
			$this->protocol_service->try_log_action(
				'D',
				sprintf( 'Заблоковано видалення запису судна (AppShipTicket_ID:%d)', $item_id ),
				(string) ( $plan['status'] ?? 'dependency' )
			);

			throw new \RuntimeException( 'delete_blocked:' . (string) ( $plan['message'] ?? __( 'Видалення заблоковано.', 'fstu' ) ) );
		}

		$context = isset( $plan['context'] ) && is_array( $plan['context'] ) ? $plan['context'] : [];

		$this->begin_transaction();

		try {
			$this->repository->delete_item_by_plan( $plan );

			$this->protocol_service->log_action(
				'D',
				sprintf( 'Видалено запис судна (AppShipTicket_ID:%d, Sailboat_ID:%d)', (int) ( $context['appshipticket_id'] ?? 0 ), (int) ( $context['sailboat_id'] ?? 0 ) ),
				Sailboats_Protocol_Service::STATUS_SUCCESS,
				true
			);

			$this->commit_transaction();

			return true;
		} catch ( \Throwable $throwable ) {
			$this->rollback_transaction();
			$this->protocol_service->try_log_action(
				'D',
				sprintf( 'Помилка видалення запису судна (AppShipTicket_ID:%d) [%s]', (int) ( $context['appshipticket_id'] ?? $item_id ), $this->get_error_marker( $throwable, 'delete_failed' ) ),
				'error'
			);
			throw $throwable;
		}
	}

	/**
	 * Надсилає повідомлення щодо внесків.
	 *
	 * @param array<string,mixed> $data Дані дії.
	 */
	public function send_dues_notification( array $data ): bool {
		$item_id = (int) ( $data['item_id'] ?? 0 );
		$context = $this->repository->get_notification_context( $item_id );

		if ( ! is_array( $context ) ) {
			throw new \RuntimeException( 'item_not_found' );
		}

		$payload = array_merge( $context, $data );

		if ( '' === trim( (string) ( $payload['notification_email'] ?? '' ) ) ) {
			throw new \RuntimeException( 'notification_email_not_found' );
		}

		try {
			$this->notification_service->send_dues_notification( $payload );

			$this->protocol_service->log_action(
				'U',
				sprintf( 'Надіслано повідомлення щодо внесків (%s) для запису судна (AppShipTicket_ID:%d)', (string) ( $data['notification_type'] ?? 'membership' ), (int) ( $context['appshipticket_id'] ?? 0 ) ),
				Sailboats_Protocol_Service::STATUS_SUCCESS,
				true
			);
		} catch ( \Throwable $throwable ) {
			$this->protocol_service->try_log_action(
				'U',
				sprintf( 'Помилка надсилання повідомлення щодо внесків (%s) для запису судна (AppShipTicket_ID:%d) [%s]', (string) ( $data['notification_type'] ?? 'membership' ), (int) ( $context['appshipticket_id'] ?? 0 ), $this->get_error_marker( $throwable, 'notification_failed' ) ),
				'error'
			);
			throw $throwable;
		}

		return true;
	}

	/**
	 * Повертає сервіс протоколу.
	 */
	public function get_protocol_service(): Sailboats_Protocol_Service {
		return $this->protocol_service;
	}

	/**
	 * Повертає сервіс сповіщень.
	 */
	public function get_notification_service(): Sailboats_Notification_Service {
		return $this->notification_service;
	}

	/**
	 * Запускає транзакцію.
	 */
	private function begin_transaction(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'START TRANSACTION' );
	}

	/**
	 * Фіксує транзакцію.
	 */
	private function commit_transaction(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'COMMIT' );
	}

	/**
	 * Відкочує транзакцію.
	 */
	private function rollback_transaction(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'ROLLBACK' );
	}

	/**
	 * Повертає безпечний службовий маркер причини помилки.
	 */
	private function get_error_marker( \Throwable $throwable, string $fallback ): string {
		$marker = trim( $throwable->getMessage() );

		return '' !== $marker ? $marker : $fallback;
	}

	/**
	 * Перевіряє логічну коректність службової дати відносно дати створення заявки.
	 *
	 * @param array<string,int> $context Контекст запису.
	 */
	private function assert_service_action_date_is_valid( array $context, string $candidate_date, string $action ): void {
		$candidate_timestamp = strtotime( $candidate_date );
		if ( false === $candidate_timestamp ) {
			throw new \RuntimeException( 'invalid_' . $action . '_date' );
		}

		$item = $this->repository->get_sailboat_by_id( (int) ( $context['appshipticket_id'] ?? 0 ) );
		if ( ! is_array( $item ) ) {
			return;
		}

		$registration_date = trim( (string) ( $item['appshipticket_date_create'] ?? $item['registration_date'] ?? '' ) );
		if ( '' === $registration_date ) {
			return;
		}

		$registration_timestamp = strtotime( $registration_date );
		if ( false === $registration_timestamp ) {
			return;
		}

		if ( $candidate_timestamp < $registration_timestamp ) {
			throw new \RuntimeException( 'invalid_' . $action . '_date_sequence' );
		}
	}

	/**
	 * Повертає контекст item_id або кидає контрольовану помилку.
	 *
	 * @return array<string,int>
	 */
	private function require_item_context( int $item_id ): array {
		if ( $item_id <= 0 ) {
			throw new \RuntimeException( 'item_not_found' );
		}

		$context = $this->repository->get_item_context( $item_id );
		if ( ! is_array( $context ) ) {
			throw new \RuntimeException( 'item_not_found' );
		}

		return $context;
	}
}

