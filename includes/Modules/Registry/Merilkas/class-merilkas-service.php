<?php
/**
 * Бізнес-сервіс модуля "Реєстр мерилок".
 * Бізнес-логіка, калькулятор ГБ та транзакції.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-09
 *
 * @package FSTU\Modules\Registry\Merilkas
 */

namespace FSTU\Modules\Registry\Merilkas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Merilkas_Service {

	private Merilkas_Repository $repository;
	private Merilkas_Protocol_Service $protocol_service;

	public function __construct( Merilkas_Repository $repository, Merilkas_Protocol_Service $protocol_service ) {
		$this->repository       = $repository;
		$this->protocol_service = $protocol_service;
	}

	/**
	 * Запускає транзакцію.
	 */
	public function begin_transaction(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'START TRANSACTION' );
	}

	/**
	 * Фіксує транзакцію.
	 */
	public function commit_transaction(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'COMMIT' );
	}

	/**
	 * Відкочує транзакцію.
	 */
	public function rollback_transaction(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'ROLLBACK' );
	}

	/**
	 * Перевіряє можливість видалення/редагування.
	 */
	public function check_usage_dependency( int $mr_id ): void {
		if ( $this->repository->is_merilka_used( $mr_id ) ) {
			throw new \RuntimeException( 'delete_blocked:merilka_used_in_race' );
		}
	}

    // TODO: Додати calculate_gb(), create_item(), update_item(), delete_item() (буде реалізовано у Фазі 2)
    /**
	 * Надійний парсер чисел (санітизація).
	 * Замінює кому на крапку та приводить до float.
	 *
	 * @param mixed $value
	 * @return float
	 */
	public function parse_float( $value ): float {
		if ( is_numeric( $value ) ) {
			return (float) $value;
		}
		$normalized = str_replace( ',', '.', sanitize_text_field( wp_unslash( (string) $value ) ) );
		// Видаляємо всі символи крім цифр, крапки та мінуса
		$normalized = preg_replace( '/[^0-9\.-]/', '', $normalized );
		return is_numeric( $normalized ) ? (float) $normalized : 0.0;
	}

	/**
	 * Серверний калькулятор ГБ та площ вітрил.
	 * Повністю дублює логіку merilka.js для безпечного збереження в БД.
     * розрахунок площ і Гоночного Бала (ГБ) за формулою Герона S=p(p−a)(p−b)(p−c)​, де a, b, c - сторони трикутника, p - півпериметр. До площі додаються коригування на шверт та вуглепластикову щоглу.
	 *
	 * @param array<string,mixed> $data Сирі дані з форми
	 * @return array<string,float> Розраховані параметри
	 */
	public function calculate_merilka_params( array $data ): array {
		$bc  = 0.0; // коригування на шверт
		$cmp = 0.0; // за вуглепластикову щоглу

		// Базові параметри
		$crew_count   = $this->parse_float( $data['MR_GrevNumber'] ?? 0 );
		$crew_weight  = $this->parse_float( $data['MR_CrewWeight'] ?? 0 );
		$hull_weight  = $this->parse_float( $data['MR_Weight'] ?? 0 );
		$motor_weight = $this->parse_float( $data['MR_WeightMotor'] ?? 0 );
		$length       = $this->parse_float( $data['MR_Length'] ?? 0 );

		// --- ПЛОЩІ ВІТРИЛ ---
		// 1. Грот
		$grot_p   = $this->parse_float( $data['MR_Grot_P'] ?? 0 );
		$grot_b   = $this->parse_float( $data['MR_Grot_B'] ?? 0 );
		$grot_e   = $this->parse_float( $data['MR_Grot_E'] ?? 0 );
		$grot_hp  = $this->parse_float( $data['MR_Grot_HP'] ?? 0 );
		$grot_hb  = $this->parse_float( $data['MR_Grot_HB'] ?? 0 );
		$grot_he  = $this->parse_float( $data['MR_Grot_HE'] ?? 0 );
		$grot_vlm = $this->parse_float( $data['MR_Grot_VLM'] ?? 0 );
		
		$mast_ppd = $this->parse_float( $data['MR_Machta_PPD'] ?? 0 );
		$mast_prd = $this->parse_float( $data['MR_Machta_PRD'] ?? 0 );
		$liktros  = $this->parse_float( $data['MR_Liktros'] ?? 0 );

		$area_grot = 0.0;
		if ( $grot_p > 0 && $grot_b > 0 && $grot_e > 0 && $grot_vlm > 0 ) {
			$machta = ( $mast_prd === $mast_ppd ) ? ( $mast_prd - $mast_ppd + $liktros ) : ( $mast_prd - $mast_ppd - $liktros );
			$p = ( $grot_p + $grot_b + $grot_e ) / 2;
			$heron_base = $p * ( $p - $grot_p ) * ( $p - $grot_b ) * ( $p - $grot_e );
			$heron = $heron_base > 0 ? sqrt( $heron_base ) : 0;
			$area_grot = $heron + ( 2/3 * $grot_p * $grot_hp ) + ( 2/3 * $grot_b * $grot_hb ) + ( 2/3 * $grot_e * $grot_he ) + ( $grot_p * $machta );
		}

		// 2. Стаксель
		$staksel_p   = $this->parse_float( $data['MR_Staksel_P'] ?? 0 );
		$staksel_b   = $this->parse_float( $data['MR_Staksel_B'] ?? 0 );
		$staksel_e   = $this->parse_float( $data['MR_Staksel_E'] ?? 0 );
		$staksel_hp  = $this->parse_float( $data['MR_Staksel_HP'] ?? 0 );
		$staksel_hb  = $this->parse_float( $data['MR_Staksel_HB'] ?? 0 );
		$staksel_he  = $this->parse_float( $data['MR_Staksel_HE'] ?? 0 );
		$staksel_vlm = $this->parse_float( $data['MR_Staksel_VLM'] ?? 0 );

		$area_staksel = 0.0;
		if ( $staksel_p > 0 && $staksel_b > 0 && $staksel_e > 0 && $staksel_vlm > 0 ) {
			$p = ( $staksel_p + $staksel_b + $staksel_e ) / 2;
			$heron_base = $p * ( $p - $staksel_p ) * ( $p - $staksel_b ) * ( $p - $staksel_e );
			$heron = $heron_base > 0 ? sqrt( $heron_base ) : 0;
			$area_staksel = $heron + ( 2/3 * $staksel_p * $staksel_hp ) + ( 2/3 * $staksel_b * $staksel_hb ) + ( 2/3 * $staksel_e * $staksel_he );
		}

		// 3. Клівер
		$kliver_p   = $this->parse_float( $data['MR_Kliver_P'] ?? 0 );
		$kliver_b   = $this->parse_float( $data['MR_Kliver_B'] ?? 0 );
		$kliver_e   = $this->parse_float( $data['MR_Kliver_E'] ?? 0 );
		$kliver_hp  = $this->parse_float( $data['MR_Kliver_HP'] ?? 0 );
		$kliver_hb  = $this->parse_float( $data['MR_Kliver_HB'] ?? 0 );
		$kliver_he  = $this->parse_float( $data['MR_Kliver_HE'] ?? 0 );
		$kliver_vlm = $this->parse_float( $data['MR_Kliver_VLM'] ?? 0 );

		$area_kliver = 0.0;
		if ( $kliver_p > 0 && $kliver_b > 0 && $kliver_e > 0 && $kliver_vlm > 0 ) {
			$p = ( $kliver_p + $kliver_b + $kliver_e ) / 2;
			$heron_base = $p * ( $p - $kliver_p ) * ( $p - $kliver_b ) * ( $p - $kliver_e );
			$heron = $heron_base > 0 ? sqrt( $heron_base ) : 0;
			$area_kliver = $heron + ( 2/3 * $kliver_p * $kliver_hp ) + ( 2/3 * $kliver_b * $kliver_hb ) + ( 2/3 * $kliver_e * $kliver_he );
		}

		// 4. Спінакер
		$spinaker_p   = $this->parse_float( $data['MR_Spinaker_P'] ?? 0 );
		$spinaker_b   = $this->parse_float( $data['MR_Spinaker_B'] ?? 0 );
		$spinaker_e   = $this->parse_float( $data['MR_Spinaker_E'] ?? 0 );
		$spinaker_smw = $this->parse_float( $data['MR_Spinaker_SMW'] ?? 0 );

		$area_spinaker = 0.0;
		$spinaker_smw_e = 0.0;
		if ( $spinaker_p > 0 && $spinaker_b > 0 && $spinaker_e > 0 && $spinaker_smw > 0 ) {
			$spinaker_smw_e = ( $spinaker_smw / $spinaker_e ) * 100;
			$base_sum = $spinaker_p + $spinaker_b + $spinaker_e;
			$area_spinaker = pow( $base_sum, 2 ) / 16;
		}

		// Загальні площі
		$main_sail = ( $area_grot > 0 ) ? ( $area_grot + $area_staksel + $area_kliver ) : 0.0;
		$spinaker_main_sail = ( $area_spinaker > 0 && $main_sail > 0 ) ? ( $area_spinaker / $main_sail * 100 ) : 0.0;

		// --- ЕФЕКТИВНІСТЬ ВІТРИЛ ---
		$xm = ( $grot_vlm > 0 && $area_grot > 0 ) ? ( pow( $grot_vlm, 2 ) / $area_grot ) : 0;
		$me = 40.1 + 18.31 * $xm - 2.016 * pow( $xm, 2 ) + 0.07472 * pow( $xm, 3 );
		$m  = $area_grot * $me / 100;

		$xj = ( $staksel_vlm > 0 && $area_staksel > 0 ) ? ( pow( $staksel_vlm, 2 ) / $area_staksel ) : 0;
		$mj = ( $xj > 0 ) ? ( 40.1 + 18.31 * $xj - 2.016 * pow( $xj, 2 ) + 0.07472 * pow( $xj, 3 ) ) : 0;

		$xk = ( $kliver_vlm > 0 && $area_kliver > 0 ) ? ( pow( $kliver_vlm, 2 ) / $area_kliver ) : 0;
		$mk = ( $xk > 0 ) ? ( 40.1 + 18.31 * $xk - 2.016 * pow( $xk, 2 ) + 0.07472 * pow( $xk, 3 ) ) : 0;

		// --- РОЗРАХУНОК ГБ (Внутрішня функція для математики) ---
		$calculate_single_gb = function( $w_total, $j_val ) use ( $m, $mk, $area_kliver, $length, $bc, $cmp ) {
			if ( $length <= 0 ) return 0.0;
			$k = $area_kliver * $mk / 100;
			$a = $m + $j_val + $k;
			if ( $a <= 0 ) return 0.0;

			$zm2 = sqrt( $w_total * $length ) / $a;
			$dlr = $w_total / pow( $length, 3 );
			
			$xc4 = 1 + ( 0.0061012 * $zm2 * $length * $dlr ); // Збережено як у старому скрипті
			$xc2 = 0.4556343 - ( 0.473292 * $zm2 * ( 1.038881 + ( 0.4371713 * $dlr ) ) );
			$xc  = ( -0.0414213 + ( -2.554547 * $zm2 / $length ) + ( 0.00132305 * $zm2 * pow( $length, 2 ) ) );
			
			$discriminant = pow( $xc2, 2 ) - 4 * $xc4 * $xc;
			if ( $discriminant < 0 || $xc4 == 0 ) return 0.0;

			$vt_vb = sqrt( ( -$xc2 + sqrt( $discriminant ) ) / ( 2 * $xc4 ) );
			$r = 0.8 * $vt_vb * ( 1 - ( $bc + $cmp ) / 100 );

			return ( $r > 0 ) ? ( 1 / $r ) : 0.0;
		};

		// 1. ГБ без екіпажу, зі спінакером
		$w1 = ( $crew_count * 75 ) + $hull_weight + $motor_weight;
		$j1 = ( $area_staksel > 0 ) ? ( $area_staksel * $mj / 100 ) + ( 0.1 * ( $area_spinaker - $area_staksel - $area_kliver ) ) : 0;
		$gb_spinaker = ( $area_spinaker > 0 ) ? $calculate_single_gb( $w1, $j1 ) : 0.0;

		// 2. ГБ без екіпажу, БЕЗ спінакера
		$j2 = ( $area_staksel > 0 ) ? ( $area_staksel * $mj / 100 ) : 0;
		$gb = $calculate_single_gb( $w1, $j2 );

		// 3. ГБ З екіпажем, ЗІ спінакером (У старому скрипті Kliver виключено з формули J)
		$w3 = $hull_weight + $motor_weight + $crew_weight;
		$j3 = ( $area_staksel > 0 ) ? ( $area_staksel * $mj / 100 ) + ( 0.1 * ( $area_spinaker - $area_staksel ) ) : 0;
		$gb_crew_spinaker = ( $area_spinaker > 0 ) ? $calculate_single_gb( $w3, $j3 ) : 0.0;

		// 4. ГБ З екіпажем, БЕЗ спінакера
		$j4 = ( $area_staksel > 0 ) ? ( $area_staksel * $mj / 100 ) : 0;
		$gb_crew = $calculate_single_gb( $w3, $j4 );

		return [
			'MR_Area_Grot'              => round( $area_grot, 1 ),
			'MR_Area_Staksel'           => round( $area_staksel, 1 ),
			'MR_Area_Kliver'            => round( $area_kliver, 1 ),
			'MR_Area_Spinaker'          => round( $area_spinaker, 1 ),
			'MR_Spinaker_SMW_E'         => round( $spinaker_smw_e, 0 ),
			'MR_Main_Sail'              => round( $main_sail, 1 ),
			'MR_Spinaker_MainSail'      => round( $spinaker_main_sail, 1 ),
			'MR_GB_Spinaker'            => round( $gb_spinaker, 3 ),
			'MR_GB'                     => round( $gb, 3 ),
			'MR_GB_CrewWeight_Spinaker' => round( $gb_crew_spinaker, 3 ),
			'MR_GB_CrewWeight'          => round( $gb_crew, 3 ),
		];
	}
}