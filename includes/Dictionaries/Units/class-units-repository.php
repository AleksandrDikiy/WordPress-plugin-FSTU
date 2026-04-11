<?php
/**
 * Репозиторій довідника осередків (тільки читання).
 * * Version: 1.0.0
 * Date_update: 2026-04-10
 */

namespace FSTU\Dictionaries\Units;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Units_Repository {

	/**
	 * Отримує налаштування для оплат з БД, якщо немає - створює дефолтні.
	 */
	public function get_settings(): array {
		global $wpdb;

		$amount = $wpdb->get_var( "SELECT ParamValue FROM Settings WHERE ParamName = 'Unit_Dues_Amount'" );
		$commission = $wpdb->get_var( "SELECT ParamValue FROM Settings WHERE ParamName = 'Unit_Dues_Commission'" );

		// Якщо налаштувань немає в БД, створюємо їх (захист від хардкоду)
		if ( $amount === null ) {
			$wpdb->insert( 'Settings', [ 'ParamName' => 'Unit_Dues_Amount', 'ParamValue' => '400' ] );
			$amount = '400';
		}
		if ( $commission === null ) {
			$wpdb->insert( 'Settings', [ 'ParamName' => 'Unit_Dues_Commission', 'ParamValue' => '0.023' ] );
			$commission = '0.023';
		}

		return [
			'amount'     => (float) $amount,
			'commission' => (float) $commission,
		];
	}

	/**
	 * Отримання списку осередків (з пошуком та пагінацією).
	 * Використовується прямий запит з JOIN замість SQL View (vUnit).
	 */
	public function get_units( string $search = '', int $limit = 10, int $offset = 0 ): array {
		global $wpdb;

		$where = "WHERE u.Unit_Status = 1";
		$args = [];

		if ( ! empty( $search ) ) {
			// Пошук тепер йде напряму по таблиці S_Unit (аліас u)
			$where .= " AND (u.Unit_Name LIKE %s OR u.Unit_ShortName LIKE %s OR u.Unit_OKPO LIKE %s)";
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			array_push( $args, $like, $like, $like );
		}

		array_push( $args, $limit, $offset );

		// Твій детальний SELECT
		$select_clause = "SELECT u.Unit_ID, u.Region_ID, u.Unit_Parent, u.Unit_DateCreate, u.Unit_Name, u.Unit_ShortName, 
				u.Unit_Adr, u.Unit_OKPO, u.Unit_EntranceFee, u.Unit_AnnualFee, u.Unit_UrlPay, u.Unit_PaymentCard, 
				t.City_Name, t.City_ID, r.Region_Name, r.Region_NameEng, r.Region_Code, r.Region_Order, 
				p.UnitType_Name, p.UnitType_Order, p.UnitType_ID, c.Country_ID, c.Country_Name, c.Country_NameEng, 
				c.Country_Order, o.OPF_Name, o.OPF_NameShort, o.OPF_ID";

		// Блок JOIN згідно з твоїм SQL
		$from_clause = "FROM S_Unit u 
				JOIN S_City t ON t.City_ID = u.City_ID 
				JOIN S_Region r ON r.Region_ID = u.Region_ID 
				JOIN S_UnitType p ON p.UnitType_ID = u.UnitType_ID 
				JOIN S_OPF o ON o.OPF_ID = u.OPF_ID 
				LEFT JOIN S_Country c ON c.Country_ID = r.Country_ID";

		// Формування фінального запиту
		$sql = "$select_clause 
				$from_clause 
				$where 
				ORDER BY r.Region_Name ASC, p.UnitType_Order ASC, u.Unit_Name ASC 
				LIMIT %d OFFSET %d";

		$items = $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A );
		
		// Підрахунок загальної кількості для пагінації
		$sql_count = "SELECT COUNT(*) $from_clause $where";
		$count_args = array_slice( $args, 0, count( $args ) - 2 ); // прибираємо limit та offset
		$total = (int) ( empty( $count_args ) ? $wpdb->get_var( $sql_count ) : $wpdb->get_var( $wpdb->prepare( $sql_count, $count_args ) ) );

		return [
			'items' => $items,
			'total' => $total,
		];
	}

	/**
	 * Отримання списків (довідників) для форми додавання/редагування.
	 */
	public function get_dictionaries(): array {
		global $wpdb;
		return [
			'opf'     => $wpdb->get_results( "SELECT OPF_ID, OPF_Name FROM S_OPF ORDER BY OPF_Name", ARRAY_A ),
			'types'   => $wpdb->get_results( "SELECT UnitType_ID, UnitType_Name FROM S_UnitType ORDER BY UnitType_Order", ARRAY_A ),
			'regions' => $wpdb->get_results( "SELECT Region_ID, Region_Name FROM S_Region ORDER BY Region_Name", ARRAY_A ),
			'parents' => $wpdb->get_results( "SELECT Unit_ID, Unit_Name FROM S_Unit WHERE Unit_Status = 1 ORDER BY Unit_Name", ARRAY_A ),
		];
	}

	/**
	 * Отримання детальної інформації по одному осередку.
	 */
	public function get_unit_by_id( int $id ): ?array {
		global $wpdb;
		$sql = "SELECT * FROM S_Unit WHERE Unit_ID = %d AND Unit_Status = 1";
		return $wpdb->get_row( $wpdb->prepare( $sql, $id ), ARRAY_A );
	}

	/**
	 * Динамічне підвантаження міст за ID регіону (Оптимізація пам'яті фронтенду).
	 */
	public function get_cities_by_region( int $region_id ): array {
		global $wpdb;
		$sql = "SELECT City_ID, City_Name FROM S_City WHERE Region_ID = %d ORDER BY City_Name ASC";
		return $wpdb->get_results( $wpdb->prepare( $sql, $region_id ), ARRAY_A );
	}
}