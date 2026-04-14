<?php
namespace FSTU\Modules\Calendar\CalendarEvents;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Сервіс бізнес-логіки підмодуля Calendar_Events.
 *
 * Version: 1.1.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarEvents
 */
class Calendar_Events_Service {

	private Calendar_Events_Repository $repository;
	private Calendar_Events_Protocol_Service $protocol_service;

	public function __construct( ?Calendar_Events_Repository $repository = null, ?Calendar_Events_Protocol_Service $protocol_service = null ) {
		$this->repository       = $repository instanceof Calendar_Events_Repository ? $repository : new Calendar_Events_Repository();
		$this->protocol_service = $protocol_service instanceof Calendar_Events_Protocol_Service ? $protocol_service : new Calendar_Events_Protocol_Service( $this->repository );
	}

	/**
	 * Створює захід із транзакційним логуванням.
	 *
	 * @param array<string, mixed> $data Дані події.
	 * @return array{success: bool, message: string, event_id?: int, code?: string}
	 */
	public function create_event( array $data ): array {
		$this->repository->begin_transaction();

		$event_id = $this->repository->insert_event( $data );
		if ( $event_id <= 0 ) {
			$this->repository->rollback();

			return [
				'success' => false,
				'message' => 'Сталася помилка збереження заходу.',
				'code'    => 'insert_failed',
			];
		}

		$log_ok = $this->repository->insert_log(
			$this->protocol_service->get_log_name(),
			'I',
			sprintf( 'Додано захід: %s', (string) $data['name'] ),
			'✓'
		);

		if ( ! $log_ok ) {
			$this->repository->rollback();

			return [
				'success' => false,
				'message' => 'Сталася помилка збереження протоколу. Операцію скасовано.',
				'code'    => 'log_insert_failed',
			];
		}

		$this->repository->commit();

		return [
			'success'  => true,
			'message'  => 'Захід успішно додано.',
			'event_id' => $event_id,
		];
	}

	/**
	 * Оновлює захід із перевіркою owner / any-manage прав.
	 *
	 * @param array<string, mixed> $data Дані події.
	 * @return array{success: bool, message: string, code?: string}
	 */
	public function update_event( int $event_id, array $data, int $current_user_id, bool $can_manage_any ): array {
		$owner_id = $this->repository->get_event_owner_id( $event_id );
		if ( $event_id <= 0 || $owner_id <= 0 ) {
			return [
				'success' => false,
				'message' => 'Захід не знайдено.',
				'code'    => 'event_not_found',
			];
		}

		if ( ! $can_manage_any && $owner_id !== $current_user_id ) {
			return [
				'success' => false,
				'message' => 'Недостатньо прав для редагування цього заходу.',
				'code'    => 'forbidden',
			];
		}

		$this->repository->begin_transaction();
		$updated = $this->repository->update_event( $event_id, $data );
		if ( ! $updated ) {
			$this->repository->rollback();

			return [
				'success' => false,
				'message' => 'Сталася помилка збереження заходу.',
				'code'    => 'update_failed',
			];
		}

		$log_ok = $this->repository->insert_log(
			$this->protocol_service->get_log_name(),
			'U',
			sprintf( 'Оновлено захід: %s', (string) $data['name'] ),
			'✓'
		);

		if ( ! $log_ok ) {
			$this->repository->rollback();

			return [
				'success' => false,
				'message' => 'Сталася помилка збереження протоколу. Операцію скасовано.',
				'code'    => 'log_update_failed',
			];
		}

		$this->repository->commit();

		return [
			'success' => true,
			'message' => 'Захід успішно оновлено.',
		];
	}

	/**
	 * Видаляє захід за dependency-safe policy.
	 *
	 * @return array{success: bool, message: string, code?: string, dependencies?: array<string, int>}
	 */
	public function delete_event( int $event_id, int $current_user_id, bool $can_delete_any ): array {
		$event = $this->repository->get_event( $event_id );
		if ( ! is_array( $event ) ) {
			return [
				'success' => false,
				'message' => 'Захід не знайдено.',
				'code'    => 'event_not_found',
			];
		}

		$owner_id = (int) ( $event['User_ID'] ?: $event['UserCreate'] ?: 0 );
		if ( ! $can_delete_any && $owner_id !== $current_user_id ) {
			return [
				'success' => false,
				'message' => 'Недостатньо прав для видалення цього заходу.',
				'code'    => 'forbidden',
			];
		}

		$dependencies = $this->repository->get_event_dependency_counts( $event_id );
		$has_dependencies = array_sum( $dependencies ) > 0;
		if ( $has_dependencies ) {
			$this->repository->insert_log(
				$this->protocol_service->get_log_name(),
				'D',
				sprintf( 'Заблоковано видалення заходу: %s', (string) $event['Calendar_Name'] ),
				'dependency'
			);

			return [
				'success'      => false,
				'message'      => 'Видалення заблоковано: у заходу є пов’язані записи.',
				'code'         => 'dependency',
				'dependencies' => $dependencies,
			];
		}

		$this->repository->begin_transaction();
		$deleted = $this->repository->delete_event( $event_id );
		if ( ! $deleted ) {
			$this->repository->rollback();

			return [
				'success' => false,
				'message' => 'Сталася помилка видалення заходу.',
				'code'    => 'delete_failed',
			];
		}

		$log_ok = $this->repository->insert_log(
			$this->protocol_service->get_log_name(),
			'D',
			sprintf( 'Видалено захід: %s', (string) $event['Calendar_Name'] ),
			'✓'
		);

		if ( ! $log_ok ) {
			$this->repository->rollback();

			return [
				'success' => false,
				'message' => 'Сталася помилка збереження протоколу. Операцію скасовано.',
				'code'    => 'log_delete_failed',
			];
		}

		$this->repository->commit();

		return [
			'success' => true,
			'message' => 'Захід успішно видалено.',
		];
	}
}

