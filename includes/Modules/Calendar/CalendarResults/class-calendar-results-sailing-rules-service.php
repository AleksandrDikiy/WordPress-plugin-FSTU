<?php
namespace FSTU\Modules\Calendar\CalendarResults;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Сервісний контракт вітрильних бізнес-правил підмодуля Calendar_Results.
 *
 * Призначення сервісу:
 * - ізолювати sailing-контекст від UI та Repository CRUD;
 * - агрегувати `RaceTypeResult`, `Sailboat`, `Merilka`, `S_SailGroup`;
 * - повертати явну strategy/fallback-модель для перерахунку результатів;
 * - бути єдиною точкою розширення для майбутніх формул дистанцій та коефіцієнтів.
 *
 * Поточна реалізація використовує безпечний fallback `default_place_order`,
 * але вже формує контекст і попередження для подальшого переходу на точні
 * legacy-сумісні формули.
 *
 * Version: 1.0.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarResults
 */
class Calendar_Results_Sailing_Rules_Service {

	public const STRATEGY_DEFAULT_PLACE_ORDER = 'default_place_order';
	public const STRATEGY_RULESET_PENDING     = 'race_type_rules_pending';

	private Calendar_Results_Repository $repository;

	public function __construct( ?Calendar_Results_Repository $repository = null ) {
		$this->repository = $repository instanceof Calendar_Results_Repository ? $repository : new Calendar_Results_Repository();
	}

	/**
	 * Будує повний sailing-контекст перегону.
	 *
	 * @return array<string, mixed>
	 */
	public function build_race_context( int $race_id ): array {
		$race               = $this->repository->get_race( $race_id );
		$protocol_items     = $this->repository->get_race_protocol_items( $race_id );
		$result_items       = $this->repository->get_race_result_items( $race_id );
		$race_type_id       = is_array( $race ) ? absint( $race['RaceType_ID'] ?? 0 ) : 0;
		$race_type_rules    = $this->repository->get_race_type_result_items( $race_id, $race_type_id );
		$sailboat_context   = $this->repository->get_race_sailboat_context( $race_id );
		$warnings           = [];
		$missing            = [];
		$strategy           = self::STRATEGY_DEFAULT_PLACE_ORDER;

		if ( ! is_array( $race ) ) {
			$missing[] = 'race';
		}

		if ( empty( $protocol_items ) ) {
			$missing[] = 'race_protocol';
		}

		if ( empty( $race_type_rules ) ) {
			$missing[] = 'race_type_result';
		} else {
			$strategy = self::STRATEGY_RULESET_PENDING;
			$warnings[] = 'Виявлено RaceTypeResult-правила, але точні legacy-формули ще не зафіксовані — використано fallback-перерахунок.';
		}

		if ( empty( $sailboat_context ) ) {
			$missing[] = 'sailboat_context';
		}

		$has_merilka = false;
		foreach ( $sailboat_context as $row ) {
			if ( (float) ( $row['MR_GB'] ?? 0 ) > 0 || (float) ( $row['MR_GB_Spinaker'] ?? 0 ) > 0 ) {
				$has_merilka = true;
				break;
			}
		}

		if ( ! $has_merilka ) {
			$missing[] = 'merilka_coefficients';
			$warnings[] = 'Для суден перегону не знайдено мерилок з коефіцієнтами MR_GB/MR_GB_Spinaker.';
		}

		return [
			'race'                 => $race,
			'protocol_items'       => $protocol_items,
			'result_items'         => $result_items,
			'race_type_rules'      => $race_type_rules,
			'sailboat_context'     => $sailboat_context,
			'strategy'             => $strategy,
			'warnings'             => $warnings,
			'missing_dependencies' => array_values( array_unique( $missing ) ),
			'inputs_used'          => [
				'protocol_count'    => count( $protocol_items ),
				'result_count'      => count( $result_items ),
				'rules_count'       => count( $race_type_rules ),
				'sailboats_count'   => count( $sailboat_context ),
				'has_merilka'       => $has_merilka,
				'race_type_id'      => $race_type_id,
			],
		];
	}

	/**
	 * Виконує перерахунок результатів через ізольований sailing-контракт.
	 *
	 * @return array<string, mixed>
	 */
	public function recalculate_results( int $race_id ): array {
		$context = $this->build_race_context( $race_id );
		$summary = $this->repository->recalculate_results_from_protocol( $race_id );

		return [
			'updated'              => (int) ( $summary['updated'] ?? 0 ),
			'created'              => (int) ( $summary['created'] ?? 0 ),
			'strategy'             => (string) ( $context['strategy'] ?? self::STRATEGY_DEFAULT_PLACE_ORDER ),
			'warnings'             => (array) ( $context['warnings'] ?? [] ),
			'missing_dependencies' => (array) ( $context['missing_dependencies'] ?? [] ),
			'inputs_used'          => (array) ( $context['inputs_used'] ?? [] ),
		];
	}
}

