<?php
namespace FSTU\Modules\Calendar\CalendarResults;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Сервіс бізнес-логіки підмодуля Calendar_Results.
 *
 * Version: 1.2.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarResults
 */
class Calendar_Results_Service {

	private Calendar_Results_Repository $repository;
	private Calendar_Results_Protocol_Service $protocol_service;
	private Calendar_Results_Sailing_Rules_Service $sailing_rules_service;

	public function __construct( ?Calendar_Results_Repository $repository = null, ?Calendar_Results_Protocol_Service $protocol_service = null, ?Calendar_Results_Sailing_Rules_Service $sailing_rules_service = null ) {
		$this->repository       = $repository instanceof Calendar_Results_Repository ? $repository : new Calendar_Results_Repository();
		$this->protocol_service = $protocol_service instanceof Calendar_Results_Protocol_Service ? $protocol_service : new Calendar_Results_Protocol_Service( $this->repository );
		$this->sailing_rules_service = $sailing_rules_service instanceof Calendar_Results_Sailing_Rules_Service ? $sailing_rules_service : new Calendar_Results_Sailing_Rules_Service( $this->repository );
	}

	/**
	 * Створює перегін.
	 *
	 * @param array<string, mixed> $data
	 * @return array{success: bool, message: string, race_id?: int, code?: string}
	 */
	public function create_race( array $data ): array {
		$this->repository->begin_transaction();

		$race_id = $this->repository->insert_race( $data );
		if ( $race_id <= 0 ) {
			$this->repository->rollback();

			return [
				'success' => false,
				'message' => 'Сталася помилка збереження перегону.',
				'code'    => 'insert_failed',
			];
		}

		$log_ok = $this->repository->insert_log(
			$this->protocol_service->get_log_name(),
			'I',
			sprintf( 'Додано перегін: %s', (string) ( $data['race_name'] ?: 'без назви' ) ),
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
			'success' => true,
			'message' => 'Перегін успішно додано.',
			'race_id' => $race_id,
		];
	}

	/**
	 * Оновлює перегін.
	 *
	 * @param array<string, mixed> $data
	 * @return array{success: bool, message: string, code?: string}
	 */
	public function update_race( int $race_id, array $data, int $current_user_id, bool $can_manage_any ): array {
		$owner_id = $this->repository->get_race_owner_id( $race_id );
		if ( $owner_id <= 0 ) {
			return [
				'success' => false,
				'message' => 'Перегін не знайдено.',
				'code'    => 'race_not_found',
			];
		}

		if ( ! $can_manage_any && $owner_id !== $current_user_id ) {
			return [
				'success' => false,
				'message' => 'Недостатньо прав для редагування цього перегону.',
				'code'    => 'forbidden',
			];
		}

		$this->repository->begin_transaction();
		$updated = $this->repository->update_race( $race_id, $data );
		if ( ! $updated ) {
			$this->repository->rollback();

			return [
				'success' => false,
				'message' => 'Сталася помилка збереження перегону.',
				'code'    => 'update_failed',
			];
		}

		$log_ok = $this->repository->insert_log(
			$this->protocol_service->get_log_name(),
			'U',
			sprintf( 'Оновлено перегін #%d: %s', $race_id, (string) ( $data['race_name'] ?: 'без назви' ) ),
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
			'message' => 'Перегін успішно оновлено.',
		];
	}

	/**
	 * Видаляє перегін з dependency-safe policy.
	 *
	 * @return array{success: bool, message: string, code?: string, dependencies?: array<string, int>}
	 */
	public function delete_race( int $race_id, int $current_user_id, bool $can_delete_any ): array {
		$race = $this->repository->get_race( $race_id );
		if ( ! is_array( $race ) ) {
			return [
				'success' => false,
				'message' => 'Перегін не знайдено.',
				'code'    => 'race_not_found',
			];
		}

		$owner_id = $this->repository->get_race_owner_id( $race_id );
		if ( ! $can_delete_any && $owner_id !== $current_user_id ) {
			return [
				'success' => false,
				'message' => 'Недостатньо прав для видалення цього перегону.',
				'code'    => 'forbidden',
			];
		}

		$dependencies = $this->repository->get_race_dependency_counts( $race_id );
		if ( array_sum( $dependencies ) > 0 ) {
			$this->repository->insert_log(
				$this->protocol_service->get_log_name(),
				'D',
				sprintf( 'Заблоковано видалення перегону #%d', $race_id ),
				'dependency'
			);

			return [
				'success'      => false,
				'message'      => 'Видалення заблоковано: у перегону є пов’язані протоколи або результати.',
				'code'         => 'dependency',
				'dependencies' => $dependencies,
			];
		}

		$this->repository->begin_transaction();
		$deleted = $this->repository->delete_race( $race_id );
		if ( ! $deleted ) {
			$this->repository->rollback();

			return [
				'success' => false,
				'message' => 'Сталася помилка видалення перегону.',
				'code'    => 'delete_failed',
			];
		}

		$log_ok = $this->repository->insert_log(
			$this->protocol_service->get_log_name(),
			'D',
			sprintf( 'Видалено перегін #%d', $race_id ),
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
			'message' => 'Перегін успішно видалено.',
		];
	}

	/**
	 * Виконує базовий перерахунок результатів.
	 *
	 * @return array{success: bool, message: string, updated?: int, created?: int, code?: string}
	 */
	public function recalculate_results( int $race_id, int $current_user_id, bool $can_manage_any ): array {
		$owner_id = $this->repository->get_race_owner_id( $race_id );
		if ( $owner_id <= 0 ) {
			return [
				'success' => false,
				'message' => 'Перегін не знайдено.',
				'code'    => 'race_not_found',
			];
		}

		if ( ! $can_manage_any && $owner_id !== $current_user_id ) {
			return [
				'success' => false,
				'message' => 'Недостатньо прав для перерахунку результатів.',
				'code'    => 'forbidden',
			];
		}

		$this->repository->begin_transaction();
		$summary = $this->sailing_rules_service->recalculate_results( $race_id );
		$log_ok  = $this->repository->insert_log(
			$this->protocol_service->get_log_name(),
			'U',
			sprintf( 'Виконано перерахунок результатів перегону #%d [strategy=%s].', $race_id, (string) ( $summary['strategy'] ?? Calendar_Results_Sailing_Rules_Service::STRATEGY_DEFAULT_PLACE_ORDER ) ),
			'✓'
		);

		if ( ! $log_ok ) {
			$this->repository->rollback();

			return [
				'success' => false,
				'message' => 'Сталася помилка збереження протоколу. Операцію скасовано.',
				'code'    => 'log_recalculate_failed',
			];
		}

		$this->repository->commit();

		return [
			'success' => true,
			'message' => 'Перерахунок результатів виконано.',
			'updated' => (int) ( $summary['updated'] ?? 0 ),
			'created' => (int) ( $summary['created'] ?? 0 ),
			'strategy' => (string) ( $summary['strategy'] ?? Calendar_Results_Sailing_Rules_Service::STRATEGY_DEFAULT_PLACE_ORDER ),
			'warnings' => (array) ( $summary['warnings'] ?? [] ),
			'missing_dependencies' => (array) ( $summary['missing_dependencies'] ?? [] ),
			'inputs_used' => (array) ( $summary['inputs_used'] ?? [] ),
		];
	}

	/**
	 * Оновлює фінішний протокол перегону.
	 *
	 * @param array<int, array<string, mixed>> $items
	 * @return array{success: bool, message: string, updated?: int, code?: string}
	 */
	public function update_race_protocol( int $race_id, array $items, int $current_user_id, bool $can_manage_any ): array {
		$owner_id = $this->repository->get_race_owner_id( $race_id );
		if ( $owner_id <= 0 ) {
			return [
				'success' => false,
				'message' => 'Перегін не знайдено.',
				'code'    => 'race_not_found',
			];
		}

		if ( ! $can_manage_any && $owner_id !== $current_user_id ) {
			return [
				'success' => false,
				'message' => 'Недостатньо прав для редагування фінішного протоколу.',
				'code'    => 'forbidden',
			];
		}

		$this->repository->begin_transaction();
		$result = $this->repository->update_race_protocol_items( $race_id, $items );
		if ( empty( $result['success'] ) ) {
			$this->repository->rollback();

			return [
				'success' => false,
				'message' => 'Сталася помилка збереження фінішного протоколу.',
				'code'    => 'protocol_update_failed',
			];
		}

		$log_ok = $this->repository->insert_log(
			$this->protocol_service->get_log_name(),
			'U',
			sprintf( 'Оновлено фінішний протокол перегону #%d (рядків: %d).', $race_id, (int) ( $result['updated'] ?? 0 ) ),
			'✓'
		);

		if ( ! $log_ok ) {
			$this->repository->rollback();

			return [
				'success' => false,
				'message' => 'Сталася помилка збереження протоколу. Операцію скасовано.',
				'code'    => 'log_protocol_update_failed',
			];
		}

		$this->repository->commit();

		return [
			'success' => true,
			'message' => 'Фінішний протокол успішно оновлено.',
			'updated' => (int) ( $result['updated'] ?? 0 ),
		];
	}
}

