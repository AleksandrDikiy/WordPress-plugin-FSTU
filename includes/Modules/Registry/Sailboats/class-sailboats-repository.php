<?php
namespace FSTU\Modules\Registry\Sailboats;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Репозиторій модуля "Судновий реєстр ФСТУ".
 * Відповідає за SQL-запити списку, картки, протоколу та довідників фільтрів.
 *
 * Version:     1.11.1
 * Date_update: 2026-04-09
 *
 * @package FSTU\Modules\Registry\Sailboats
 */
class Sailboats_Repository {

	private const FILTER_DATASETS_CACHE_KEY = 'fstu_sailboats_filter_datasets_v1';
	private const FILTER_DATASETS_CACHE_TTL = HOUR_IN_SECONDS;
	private const PROTOCOL_LOG_NAME         = 'Sailboat';

	/**
	 * Повертає список суден.
	 *
	 * @param array<string,mixed> $args Параметри фільтрації.
	 * @return array{items: array<int,array<string,mixed>>, total: int}
	 */
	public function get_sailboats_list( array $args ): array {
		global $wpdb;

		$search    = isset( $args['search'] ) ? sanitize_text_field( (string) $args['search'] ) : '';
		$region_id = isset( $args['region_id'] ) ? absint( $args['region_id'] ) : 0;
		$status_id = isset( $args['status_id'] ) ? absint( $args['status_id'] ) : 0;
		$per_page  = isset( $args['per_page'] ) ? max( 1, absint( $args['per_page'] ) ) : 10;
		$offset    = isset( $args['offset'] ) ? max( 0, absint( $args['offset'] ) ) : 0;

		if ( '' !== $search ) {
			$legacy_search_result = $this->get_sailboats_list_by_legacy_search( $search, $region_id, $status_id, $per_page, $offset );

			if ( is_array( $legacy_search_result ) ) {
				return $legacy_search_result;
			}
		}

		$context      = $this->get_list_query_context();
		$item_id_expr = $context['item_id_expr'];

		if ( '' === $item_id_expr ) {
			return [
				'items' => [],
				'total' => 0,
			];
		}

		$where_parts = [ '1=1' ];
		$params      = [];

		if ( '' !== $search ) {
			$like            = '%' . $wpdb->esc_like( $search ) . '%';
			$search_columns  = array_values(
				array_unique(
					array_filter(
						[
							(string) ( $context['name_expr'] ?? '' ),
							(string) ( $context['registration_number_expr'] ?? '' ),
							(string) ( $context['sail_number_expr'] ?? '' ),
							(string) ( $context['owner_name_expr'] ?? '' ),
							(string) ( $context['region_name_expr'] ?? '' ),
						],
						static fn( string $expression ): bool => '' !== $expression && "''" !== $expression
					)
				)
			);

			if ( ! empty( $search_columns ) ) {
				$search_where = [];

				foreach ( $search_columns as $expression ) {
					$search_where[] = $expression . ' LIKE %s';
					$params[]       = $like;
				}

				$where_parts[] = '( ' . implode( ' OR ', $search_where ) . ' )';
			}
		}

		if ( $region_id > 0 && '' !== $context['region_id_expr'] ) {
			$where_parts[] = $context['region_id_expr'] . ' = %d';
			$params[]      = $region_id;
		}

		if ( $status_id > 0 && '' !== $context['status_id_expr'] ) {
			$where_parts[] = $context['status_id_expr'] . ' = %d';
			$params[]      = $status_id;
		}

		$where_sql = 'WHERE ' . implode( ' AND ', $where_parts );
		$count_sql = "SELECT COUNT(DISTINCT {$item_id_expr}) FROM {$context['from_sql']} {$where_sql}";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$total = (int) ( ! empty( $params ) ? $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) ) : $wpdb->get_var( $count_sql ) );

		$list_sql = "SELECT DISTINCT
			{$item_id_expr} AS item_id,
			{$context['name_expr']} AS name,
			{$context['registration_number_expr']} AS registration_number,
			{$context['sail_number_expr']} AS sail_number,
			{$context['region_name_expr']} AS region_name,
			{$context['owner_name_expr']} AS owner_name,
			{$context['producer_name_expr']} AS producer_name,
			{$context['status_name_expr']} AS status_name,
			{$context['date_create_expr']} AS registration_date,
			{$context['v1_expr']} AS V1,
			{$context['v2_expr']} AS V2,
			{$context['f1_expr']} AS F1,
			{$context['f2_expr']} AS F2
			FROM {$context['from_sql']}
			{$where_sql}
			ORDER BY {$context['order_by_sql']}
			LIMIT %d OFFSET %d";

		$list_params = array_merge( $params, [ $per_page, $offset ] );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$items = $wpdb->get_results( ! empty( $list_params ) ? $wpdb->prepare( $list_sql, ...$list_params ) : $list_sql, ARRAY_A );

		return [
			'items' => is_array( $items ) ? $items : [],
			'total' => $total,
		];
	}

	/**
	 * Повертає один запис судна.
	 *
	 * @return array<string,mixed>|null
	 */
	public function get_sailboat_by_id( int $sailboat_id ): ?array {
		global $wpdb;

		if ( $sailboat_id <= 0 ) {
			return null;
		}

		$context      = $this->get_list_query_context();
		$item_id_expr = $context['item_id_expr'];

		if ( '' === $item_id_expr ) {
			return null;
		}

		$sql = "SELECT DISTINCT
			{$item_id_expr} AS item_id,
			{$context['sailboat_id_expr']} AS sailboat_id,
			{$context['appshipticket_number_expr']} AS appshipticket_number,
			{$context['appshipticket_number_old_expr']} AS appshipticket_number_old,
			{$context['name_expr']} AS name,
			{$context['name_eng_expr']} AS sailboat_name_eng,
			{$context['registration_number_expr']} AS registration_number,
			{$context['sail_number_expr']} AS sail_number,
			{$context['year_expr']} AS sailboat_year,
			{$context['city_id_expr_for_select']} AS city_id,
			{$context['region_id_expr_for_select']} AS region_id,
			{$context['region_name_expr']} AS region_name,
			{$context['city_name_expr']} AS city_name,
			{$context['status_id_expr_for_select']} AS verification_id,
			{$context['producer_id_expr_for_select']} AS producer_id,
			{$context['type_boat_name_expr']} AS type_boat_name,
			{$context['type_boat_id_expr_for_select']} AS type_boat_id,
			{$context['type_hull_name_expr']} AS type_hull_name,
			{$context['type_hull_id_expr_for_select']} AS type_hull_id,
			{$context['type_construction_name_expr']} AS type_construction_name,
			{$context['type_construction_id_expr_for_select']} AS type_construction_id,
			{$context['type_ship_name_expr']} AS type_ship_name,
			{$context['type_ship_id_expr_for_select']} AS type_ship_id,
			{$context['hull_material_name_expr']} AS hull_material_name,
			{$context['hull_material_id_expr_for_select']} AS hull_material_id,
			{$context['hull_color_name_expr']} AS hull_color_name,
			{$context['hull_color_id_expr_for_select']} AS hull_color_id,
			{$context['owner_name_expr']} AS owner_name,
			{$context['producer_name_expr']} AS producer_name,
			{$context['status_name_expr']} AS status_name,
			{$context['date_create_expr']} AS registration_date,
			{$context['date_create_expr']} AS appshipticket_date_create,
			{$context['date_edit_expr']} AS appshipticket_date_edit,
			{$context['date_send_expr']} AS appshipticket_date_send,
			{$context['date_pay_expr']} AS appshipticket_date_pay,
			{$context['date_receiving_expr']} AS appshipticket_date_receiving,
			{$context['date_sale_expr']} AS appshipticket_date_sale,
			{$context['last_name_expr']} AS appshipticket_last_name,
			{$context['first_name_expr']} AS appshipticket_first_name,
			{$context['patronymic_expr']} AS appshipticket_patronymic,
			{$context['last_name_eng_expr']} AS appshipticket_last_name_eng,
			{$context['first_name_eng_expr']} AS appshipticket_first_name_eng,
			{$context['np_expr']} AS appshipticket_np,
			{$context['manual_number_expr']} AS appshipticket_number_manual,
			{$context['sail_main_expr']} AS sailboat_sail_main,
			{$context['hill_length_expr']} AS sailboat_hill_length,
			{$context['crew_max_expr']} AS sailboat_crew_max,
			{$context['width_overall_expr']} AS sailboat_width_overall,
			{$context['clearance_expr']} AS sailboat_clearance,
			{$context['load_capacity_expr']} AS sailboat_load_capacity,
			{$context['motor_power_expr']} AS sailboat_motor_power,
			{$context['motor_number_expr']} AS sailboat_motor_number,
			{$context['v1_expr']} AS V1,
			{$context['v2_expr']} AS V2,
			{$context['f1_expr']} AS F1,
			{$context['f2_expr']} AS F2
			FROM {$context['from_sql']}
			WHERE {$item_id_expr} = %d
			LIMIT 1";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$item = $wpdb->get_row( $wpdb->prepare( $sql, $sailboat_id ), ARRAY_A );

		if ( ! is_array( $item ) ) {
			return null;
		}

		$merilka_summary = $this->get_merilka_dependency_summary(
			(int) ( $item['item_id'] ?? 0 ),
			(int) ( $item['sailboat_id'] ?? 0 )
		);

		return array_merge( $item, $merilka_summary );
	}

	/**
	 * Повертає записи протоколу модуля.
	 *
	 * @param array<string,mixed> $args Параметри фільтрації.
	 * @return array{items: array<int,array<string,mixed>>, total: int}
	 */
	public function get_protocol_items( array $args ): array {
		global $wpdb;

		$search   = isset( $args['search'] ) ? sanitize_text_field( (string) $args['search'] ) : '';
		$page     = isset( $args['page'] ) ? max( 1, absint( $args['page'] ) ) : 1;
		$per_page = isset( $args['per_page'] ) ? max( 1, absint( $args['per_page'] ) ) : 10;
		$offset   = isset( $args['offset'] ) ? max( 0, absint( $args['offset'] ) ) : ( $page - 1 ) * $per_page;
		$log_name = isset( $args['log_name'] ) ? sanitize_text_field( (string) $args['log_name'] ) : self::PROTOCOL_LOG_NAME;

		$where  = 'WHERE l.Logs_Name = %s';
		$params = [ $log_name ];

		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where   .= ' AND (l.Logs_Text LIKE %s OR u.FIO LIKE %s)';
			$params[] = $like;
			$params[] = $like;
		}

		$count_sql = "SELECT COUNT(*)
			FROM Logs l
			LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
			{$where}";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );

		$list_sql = "SELECT l.User_ID, l.Logs_DateCreate, l.Logs_Type, l.Logs_Name, l.Logs_Text, l.Logs_Error, u.FIO
			FROM Logs l
			LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
			{$where}
			ORDER BY l.Logs_DateCreate DESC
			LIMIT %d OFFSET %d";

		$list_params = array_merge( $params, [ $per_page, $offset ] );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$items = $wpdb->get_results( $wpdb->prepare( $list_sql, ...$list_params ), ARRAY_A );

		return [
			'items' => is_array( $items ) ? $items : [],
			'total' => $total,
		];
	}

	/**
	 * Повертає довідники для форми модуля.
	 *
	 * @return array<string,array<int,array<string,mixed>>>
	 */
	public function get_dictionaries(): array {
		$cached = wp_cache_get( self::FILTER_DATASETS_CACHE_KEY, 'fstu_sailboats' );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$cached_transient = get_transient( self::FILTER_DATASETS_CACHE_KEY );
		if ( is_array( $cached_transient ) ) {
			wp_cache_set( self::FILTER_DATASETS_CACHE_KEY, $cached_transient, 'fstu_sailboats', self::FILTER_DATASETS_CACHE_TTL );

			return $cached_transient;
		}

		$regions            = $this->get_dictionary_rows( 'S_Region', 'Region_ID', [ 'Region_Name', 'Name' ] );
		$statuses           = $this->get_dictionary_rows( 'S_Verification', 'Verification_ID', [ 'Verification_Name', 'Name' ] );
		$producers          = $this->get_dictionary_rows( 'S_ProducerShips', 'ProducerShips_ID', [ 'ProducerShips_Name', 'Name' ] );
		$type_boats         = $this->get_dictionary_rows( 'S_TypeBoat', 'TypeBoat_ID', [ 'TypeBoat_Name', 'Name' ] );
		$type_hulls         = $this->get_dictionary_rows( 'S_TypeHull', 'TypeHull_ID', [ 'TypeHull_Name', 'Name' ] );
		$type_constructions = $this->get_dictionary_rows( 'S_TypeConstruction', 'TypeConstruction_ID', [ 'TypeConstruction_Name', 'Name' ] );
		$type_ships         = $this->get_dictionary_rows( 'S_TypeShip', 'TypeShip_ID', [ 'TypeShip_Name', 'Name' ] );
		$hull_materials     = $this->get_dictionary_rows( 'S_HullMaterial', 'HullMaterial_ID', [ 'HullMaterial_Name', 'Name' ] );
		$hull_colors        = $this->get_dictionary_rows( 'S_HullColor', 'HullColor_ID', [ 'HullColor_Name', 'Name' ] );
		$cities             = $this->get_city_dictionary_rows();

		$datasets = [
			'regions'            => is_array( $regions ) ? $regions : [],
			'statuses'           => is_array( $statuses ) ? $statuses : [],
			'producers'          => is_array( $producers ) ? $producers : [],
			'type_boats'         => is_array( $type_boats ) ? $type_boats : [],
			'type_hulls'         => is_array( $type_hulls ) ? $type_hulls : [],
			'type_constructions' => is_array( $type_constructions ) ? $type_constructions : [],
			'type_ships'         => is_array( $type_ships ) ? $type_ships : [],
			'hull_materials'     => is_array( $hull_materials ) ? $hull_materials : [],
			'hull_colors'        => is_array( $hull_colors ) ? $hull_colors : [],
			'cities'             => is_array( $cities ) ? $cities : [],
		];

		wp_cache_set( self::FILTER_DATASETS_CACHE_KEY, $datasets, 'fstu_sailboats', self::FILTER_DATASETS_CACHE_TTL );
		set_transient( self::FILTER_DATASETS_CACHE_KEY, $datasets, self::FILTER_DATASETS_CACHE_TTL );

		return $datasets;
	}

	/**
	 * Формує контекст SQL-запиту для списку / картки суден.
	 *
	 * @return array<string,string>
	 */
	private function get_list_query_context(): array {
		$s_columns = $this->get_table_columns( 'vSailboat' );
		$a_columns = $this->get_table_columns( 'ApplicationShipTicket' );
		$sailboat_columns = $this->get_table_columns( 'Sailboat' );
		$v_columns = $this->get_table_columns( 'S_Verification' );
		$r_columns = $this->get_table_columns( 'S_Region' );
		$c_columns = $this->get_table_columns( 'S_City' );
		$ps_columns = $this->get_table_columns( 'S_ProducerShips' );
		$tb_columns = $this->get_table_columns( 'S_TypeBoat' );
		$th_columns = $this->get_table_columns( 'S_TypeHull' );
		$tc_columns = $this->get_table_columns( 'S_TypeConstruction' );
		$ts_columns = $this->get_table_columns( 'S_TypeShip' );
		$hm_columns = $this->get_table_columns( 'S_HullMaterial' );
		$hc_columns = $this->get_table_columns( 'S_HullColor' );

		$item_id_expr = $this->build_first_existing_column_reference(
			[
				[ 'a', 'AppShipTicket_ID', $a_columns ],
				[ 's', 'AppShipTicket_ID', $s_columns ],
				[ 's', 'Sailboat_ID', $s_columns ],
			],
			''
		);

		$region_id_expr = $this->build_first_existing_column_reference(
			[
				[ 'a', 'Region_ID', $a_columns ],
				[ 's', 'Region_ID', $s_columns ],
			],
			''
		);

		$status_id_expr = $this->build_first_existing_column_reference(
			[
				[ 'a', 'Verification_ID', $a_columns ],
				[ 's', 'Verification_ID', $s_columns ],
			],
			''
		);

		$city_id_expr = $this->build_first_existing_column_reference(
			[
				[ 'a', 'City_ID', $a_columns ],
				[ 's', 'City_ID', $s_columns ],
			],
			''
		);

		$producer_id_expr = $this->build_first_existing_column_reference(
			[
				[ 's', 'ProducerShips_ID', $s_columns ],
				[ 's', 'Producer_ID', $s_columns ],
				[ 'a', 'ProducerShips_ID', $a_columns ],
			],
			''
		);

		$type_boat_id_expr = $this->build_first_existing_column_reference(
			[
				[ 's', 'TypeBoat_ID', $s_columns ],
				[ 'a', 'TypeBoat_ID', $a_columns ],
			],
			''
		);

		$type_hull_id_expr = $this->build_first_existing_column_reference(
			[
				[ 's', 'TypeHull_ID', $s_columns ],
				[ 'a', 'TypeHull_ID', $a_columns ],
			],
			''
		);

		$type_construction_id_expr = $this->build_first_existing_column_reference(
			[
				[ 's', 'TypeConstruction_ID', $s_columns ],
				[ 'a', 'TypeConstruction_ID', $a_columns ],
			],
			''
		);

		$type_ship_id_expr = $this->build_first_existing_column_reference(
			[
				[ 's', 'TypeShip_ID', $s_columns ],
				[ 'a', 'TypeShip_ID', $a_columns ],
			],
			''
		);

		$hull_material_id_expr = $this->build_first_existing_column_reference(
			[
				[ 's', 'HullMaterial_ID', $s_columns ],
				[ 'a', 'HullMaterial_ID', $a_columns ],
			],
			''
		);

		$hull_color_id_expr = $this->build_first_existing_column_reference(
			[
				[ 's', 'HullColor_ID', $s_columns ],
				[ 'a', 'HullColor_ID', $a_columns ],
			],
			''
		);

		$sailboat_id_expr = $this->build_first_existing_column_reference(
			[
				[ 's', 'Sailboat_ID', $s_columns ],
				[ 'a', 'Sailboat_ID', $a_columns ],
			],
			'0'
		);

		$name_expr = $this->build_first_existing_expression(
			[
				[ 's', 'Sailboat_Name', $s_columns ],
				[ 's', 'Name', $s_columns ],
			],
			"''"
		);

		$name_eng_expr = $this->build_first_existing_expression(
			[
				[ 's', 'Sailboat_NameEng', $s_columns ],
				[ 's', 'NameEng', $s_columns ],
			],
			"''"
		);

		$registration_number_expr = $this->build_first_existing_expression(
			[
				[ 's', 'RegNumber', $s_columns ],
				[ 'a', 'AppShipTicket_Number', $a_columns ],
				[ 'a', 'AppShipTicket_NumberOld', $a_columns ],
			],
			"''"
		);

		$appshipticket_number_expr = $this->build_first_existing_expression(
			[
				[ 'a', 'AppShipTicket_Number', $a_columns ],
				[ 's', 'AppShipTicket_Number', $s_columns ],
			],
			"''"
		);

		$appshipticket_number_old_expr = $this->build_first_existing_expression(
			[
				[ 'a', 'AppShipTicket_NumberOld', $a_columns ],
				[ 's', 'AppShipTicket_NumberOld', $s_columns ],
			],
			"''"
		);

		$sail_number_expr = $this->build_first_existing_expression(
			[
				[ 's', 'Sailboat_NumberSail', $s_columns ],
				[ 's', 'NumberSail', $s_columns ],
			],
			"''"
		);

		$year_expr = $this->build_first_existing_expression(
			[
				[ 's', 'Sailboat_Year', $s_columns ],
				[ 's', 'Year', $s_columns ],
			],
			"''"
		);

		$region_name_expr = $this->build_first_existing_expression(
			array_merge(
				'' !== $region_id_expr
					? [
						[ 'r', 'Region_Name', $r_columns ],
						[ 'r', 'Name', $r_columns ],
					]
					: [],
				[
					[ 's', 'Region_Name', $s_columns ],
				]
			),
			"''"
		);

		$city_name_expr = $this->build_first_existing_expression(
			array_merge(
				'' !== $city_id_expr
					? [
						[ 'c', 'City_Name', $c_columns ],
						[ 'c', 'Name', $c_columns ],
					]
					: [],
				[
					[ 's', 'City_Name', $s_columns ],
				]
			),
			"''"
		);

		$status_name_expr = $this->build_first_existing_expression(
			array_merge(
				'' !== $status_id_expr
					? [
						[ 'v', 'Verification_Name', $v_columns ],
						[ 'v', 'Name', $v_columns ],
					]
					: [],
				[
					[ 's', 'Verification_Name', $s_columns ],
					[ 's', 'Status_Name', $s_columns ],
				]
			),
			"''"
		);

		$last_name_eng_expr = $this->build_first_existing_expression(
			[
				[ 'a', 'AppShipTicket_LastNameEng', $a_columns ],
			],
			"''"
		);

		$first_name_eng_expr = $this->build_first_existing_expression(
			[
				[ 'a', 'AppShipTicket_FirstNameEng', $a_columns ],
			],
			"''"
		);

		$np_expr = $this->build_first_existing_expression(
			[
				[ 'a', 'AppShipTicket_NP', $a_columns ],
			],
			"''"
		);

		$sail_main_expr = $this->build_first_existing_expression(
			[
				[ 's', 'Sailboat_SailMain', $s_columns ],
				[ 's', 'SailMain', $s_columns ],
				[ 'a', 'Sailboat_SailMain', $a_columns ],
			],
			"''"
		);

		$hill_length_expr = $this->build_first_existing_expression(
			[
				[ 's', 'Sailboat_HillLength', $s_columns ],
				[ 's', 'HillLength', $s_columns ],
				[ 'a', 'Sailboat_HillLength', $a_columns ],
			],
			"''"
		);

		$crew_max_expr = $this->build_first_existing_expression(
			[
				[ 's', 'Sailboat_CrewMax', $s_columns ],
				[ 's', 'CrewMax', $s_columns ],
				[ 'a', 'Sailboat_CrewMax', $a_columns ],
			],
			"''"
		);

		$width_overall_expr = $this->build_first_existing_expression(
			[
				[ 's', 'Sailboat_WidthOverall', $s_columns ],
				[ 's', 'WidthOverall', $s_columns ],
				[ 'a', 'Sailboat_WidthOverall', $a_columns ],
			],
			"''"
		);

		$clearance_expr = $this->build_first_existing_expression(
			[
				[ 's', 'Sailboat_Clearance', $s_columns ],
				[ 's', 'Clearance', $s_columns ],
				[ 'a', 'Sailboat_Clearance', $a_columns ],
			],
			"''"
		);

		$load_capacity_expr = $this->build_first_existing_expression(
			[
				[ 's', 'Sailboat_LoadCapacity', $s_columns ],
				[ 's', 'LoadCapacity', $s_columns ],
				[ 'a', 'Sailboat_LoadCapacity', $a_columns ],
			],
			"''"
		);

		$motor_power_expr = $this->build_first_existing_expression(
			[
				[ 's', 'Sailboat_MotorPower', $s_columns ],
				[ 's', 'MotorPower', $s_columns ],
				[ 'a', 'Sailboat_MotorPower', $a_columns ],
			],
			"''"
		);

		$motor_number_expr = $this->build_first_existing_expression(
			[
				[ 's', 'Sailboat_MotorNumber', $s_columns ],
				[ 's', 'MotorNumber', $s_columns ],
				[ 'a', 'Sailboat_MotorNumber', $a_columns ],
			],
			"''"
		);

		$producer_name_expr = $this->build_first_existing_expression(
			array_merge(
				[
					[ 's', 'ProducerShips_Name', $s_columns ],
					[ 's', 'Producer_Name', $s_columns ],
					[ 's', 'ProducerName', $s_columns ],
					[ 's', 'Sailboat_Producer', $s_columns ],
				],
				'' !== $producer_id_expr
					? [
						[ 'ps', 'ProducerShips_Name', $ps_columns ],
						[ 'ps', 'Name', $ps_columns ],
					]
					: []
			),
			"''"
		);

		$type_boat_name_expr = $this->build_first_existing_expression(
			array_merge(
				[
					[ 's', 'TypeBoat_Name', $s_columns ],
				],
				'' !== $type_boat_id_expr
					? [
						[ 'tb', 'TypeBoat_Name', $tb_columns ],
						[ 'tb', 'Name', $tb_columns ],
					]
					: []
			),
			"''"
		);

		$type_hull_name_expr = $this->build_first_existing_expression(
			array_merge(
				[
					[ 's', 'TypeHull_Name', $s_columns ],
				],
				'' !== $type_hull_id_expr
					? [
						[ 'th', 'TypeHull_Name', $th_columns ],
						[ 'th', 'Name', $th_columns ],
					]
					: []
			),
			"''"
		);

		$type_construction_name_expr = $this->build_first_existing_expression(
			array_merge(
				[
					[ 's', 'TypeConstruction_Name', $s_columns ],
				],
				'' !== $type_construction_id_expr
					? [
						[ 'tc', 'TypeConstruction_Name', $tc_columns ],
						[ 'tc', 'Name', $tc_columns ],
					]
					: []
			),
			"''"
		);

		$type_ship_name_expr = $this->build_first_existing_expression(
			array_merge(
				[
					[ 's', 'TypeShip_Name', $s_columns ],
				],
				'' !== $type_ship_id_expr
					? [
						[ 'ts', 'TypeShip_Name', $ts_columns ],
						[ 'ts', 'Name', $ts_columns ],
					]
					: []
			),
			"''"
		);

		$hull_material_name_expr = $this->build_first_existing_expression(
			array_merge(
				[
					[ 's', 'HullMaterial_Name', $s_columns ],
				],
				'' !== $hull_material_id_expr
					? [
						[ 'hm', 'HullMaterial_Name', $hm_columns ],
						[ 'hm', 'Name', $hm_columns ],
					]
					: []
			),
			"''"
		);

		$hull_color_name_expr = $this->build_first_existing_expression(
			array_merge(
				[
					[ 's', 'HullColor_Name', $s_columns ],
				],
				'' !== $hull_color_id_expr
					? [
						[ 'hc', 'HullColor_Name', $hc_columns ],
						[ 'hc', 'Name', $hc_columns ],
					]
					: []
			),
			"''"
		);

		$owner_name_expr = $this->build_owner_expression( $a_columns, $s_columns );
		$last_name_expr = $this->build_first_existing_expression(
			[
				[ 'a', 'AppShipTicket_LastName', $a_columns ],
			],
			"''"
		);
		$first_name_expr = $this->build_first_existing_expression(
			[
				[ 'a', 'AppShipTicket_FirstName', $a_columns ],
			],
			"''"
		);
		$patronymic_expr = $this->build_first_existing_expression(
			[
				[ 'a', 'AppShipTicket_Patronymic', $a_columns ],
			],
			"''"
		);
		$manual_number_expr = $this->build_first_existing_expression(
			[
				[ 'a', 'AppShipTicket_NumberManual', $a_columns ],
			],
			"''"
		);
		$date_create_expr = $this->build_first_existing_expression(
			[
				[ 'a', 'AppShipTicket_DateCreate', $a_columns ],
				[ 's', 'AppShipTicket_DateCreate', $s_columns ],
			],
			"''"
		);

		$date_edit_expr = $this->build_first_existing_expression(
			[
				[ 'a', 'AppShipTicket_DateEdit', $a_columns ],
				[ 's', 'AppShipTicket_DateEdit', $s_columns ],
			],
			"''"
		);

		$date_send_expr = $this->build_first_existing_expression(
			[
				[ 'a', 'AppShipTicket_DateSend', $a_columns ],
				[ 'a', 'AppShipTicket_DateSending', $a_columns ],
				[ 's', 'AppShipTicket_DateSend', $s_columns ],
				[ 's', 'AppShipTicket_DateSending', $s_columns ],
			],
			"''"
		);

		$date_pay_expr = $this->build_first_existing_expression(
			[
				[ 'a', 'AppShipTicket_DatePay', $a_columns ],
				[ 's', 'AppShipTicket_DatePay', $s_columns ],
			],
			"''"
		);

		$date_receiving_expr = $this->build_first_existing_expression(
			[
				[ 'a', 'AppShipTicket_DateReceiving', $a_columns ],
				[ 's', 'AppShipTicket_DateReceiving', $s_columns ],
			],
			"''"
		);

		$date_sale_expr = $this->build_first_existing_expression(
			[
				[ 'a', 'AppShipTicket_DateSale', $a_columns ],
				[ 's', 'AppShipTicket_DateSale', $s_columns ],
			],
			"''"
		);

		$v1_expr = $this->build_first_existing_expression(
			[
				[ 's', 'V1', $s_columns ],
				[ 'a', 'V1', $a_columns ],
			],
			"''"
		);

		$user_id_expr = $this->build_first_existing_column_reference(
			[
				[ 'a', 'User_ID', $a_columns ],
				[ 's', 'User_ID', $s_columns ],
			],
			'0'
		);

		$v1_expr = $this->build_due_expression( $user_id_expr, 'GetUserDuesSail', -1, $v1_expr );

		$v2_expr = $this->build_first_existing_expression(
			[
				[ 's', 'V2', $s_columns ],
				[ 'a', 'V2', $a_columns ],
			],
			"''"
		);
		$v2_expr = $this->build_due_expression( $user_id_expr, 'GetUserDuesSail', 0, $v2_expr );

		$f1_expr = $this->build_first_existing_expression(
			[
				[ 's', 'F1', $s_columns ],
				[ 'a', 'F1', $a_columns ],
			],
			"''"
		);
		$f1_expr = $this->build_due_expression( $user_id_expr, 'GetUserDues', -1, $f1_expr );

		$f2_expr = $this->build_first_existing_expression(
			[
				[ 's', 'F2', $s_columns ],
				[ 'a', 'F2', $a_columns ],
			],
			"''"
		);
		$f2_expr = $this->build_due_expression( $user_id_expr, 'GetUserDues', 0, $f2_expr );

		$joins = [ 'ApplicationShipTicket a', 'INNER JOIN vSailboat s ON s.AppShipTicket_ID = a.AppShipTicket_ID' ];

		if ( '' !== $status_id_expr ) {
			$joins[] = 'LEFT JOIN S_Verification v ON v.Verification_ID = ' . $status_id_expr;
		}

		if ( '' !== $region_id_expr ) {
			$joins[] = 'LEFT JOIN S_Region r ON r.Region_ID = ' . $region_id_expr;
		}

		if ( '' !== $city_id_expr ) {
			$joins[] = 'LEFT JOIN S_City c ON c.City_ID = ' . $city_id_expr;
		}

		if ( '' !== $producer_id_expr ) {
			$joins[] = 'LEFT JOIN S_ProducerShips ps ON ps.ProducerShips_ID = ' . $producer_id_expr;
		}

		if ( '' !== $type_boat_id_expr ) {
			$joins[] = 'LEFT JOIN S_TypeBoat tb ON tb.TypeBoat_ID = ' . $type_boat_id_expr;
		}

		if ( '' !== $type_hull_id_expr ) {
			$joins[] = 'LEFT JOIN S_TypeHull th ON th.TypeHull_ID = ' . $type_hull_id_expr;
		}

		if ( '' !== $type_construction_id_expr ) {
			$joins[] = 'LEFT JOIN S_TypeConstruction tc ON tc.TypeConstruction_ID = ' . $type_construction_id_expr;
		}

		if ( '' !== $type_ship_id_expr ) {
			$joins[] = 'LEFT JOIN S_TypeShip ts ON ts.TypeShip_ID = ' . $type_ship_id_expr;
		}

		if ( '' !== $hull_material_id_expr ) {
			$joins[] = 'LEFT JOIN S_HullMaterial hm ON hm.HullMaterial_ID = ' . $hull_material_id_expr;
		}

		if ( '' !== $hull_color_id_expr ) {
			$joins[] = 'LEFT JOIN S_HullColor hc ON hc.HullColor_ID = ' . $hull_color_id_expr;
		}

		$order_by_sql = $this->build_order_by_sql( $date_create_expr, $name_expr, $item_id_expr );

		return [
			'from_sql'                 => implode( ' ', $joins ),
			'item_id_expr'             => $item_id_expr,
			'sailboat_id_expr'         => $sailboat_id_expr,
			'city_id_expr_for_select'  => '' !== $city_id_expr ? $city_id_expr : '0',
			'region_id_expr'           => $region_id_expr,
			'region_id_expr_for_select'=> '' !== $region_id_expr ? $region_id_expr : '0',
			'status_id_expr'           => $status_id_expr,
			'status_id_expr_for_select'=> '' !== $status_id_expr ? $status_id_expr : '0',
			'producer_id_expr_for_select' => '' !== $producer_id_expr ? $producer_id_expr : '0',
			'type_boat_id_expr_for_select' => '' !== $type_boat_id_expr ? $type_boat_id_expr : '0',
			'type_hull_id_expr_for_select' => '' !== $type_hull_id_expr ? $type_hull_id_expr : '0',
			'type_construction_id_expr_for_select' => '' !== $type_construction_id_expr ? $type_construction_id_expr : '0',
			'type_ship_id_expr_for_select' => '' !== $type_ship_id_expr ? $type_ship_id_expr : '0',
			'hull_material_id_expr_for_select' => '' !== $hull_material_id_expr ? $hull_material_id_expr : '0',
			'hull_color_id_expr_for_select' => '' !== $hull_color_id_expr ? $hull_color_id_expr : '0',
			'search_expr'              => $name_expr,
			'name_expr'                => $name_expr,
			'name_eng_expr'            => $name_eng_expr,
			'registration_number_expr' => $registration_number_expr,
			'appshipticket_number_expr' => $appshipticket_number_expr,
			'appshipticket_number_old_expr' => $appshipticket_number_old_expr,
			'sail_number_expr'         => $sail_number_expr,
			'year_expr'                => $year_expr,
			'region_name_expr'         => $region_name_expr,
			'city_name_expr'           => $city_name_expr,
			'owner_name_expr'          => $owner_name_expr,
			'last_name_expr'           => $last_name_expr,
			'first_name_expr'          => $first_name_expr,
			'patronymic_expr'          => $patronymic_expr,
			'last_name_eng_expr'       => $last_name_eng_expr,
			'first_name_eng_expr'      => $first_name_eng_expr,
			'np_expr'                  => $np_expr,
			'manual_number_expr'       => $manual_number_expr,
			'sail_main_expr'           => $sail_main_expr,
			'hill_length_expr'         => $hill_length_expr,
			'crew_max_expr'            => $crew_max_expr,
			'width_overall_expr'       => $width_overall_expr,
			'clearance_expr'           => $clearance_expr,
			'load_capacity_expr'       => $load_capacity_expr,
			'motor_power_expr'         => $motor_power_expr,
			'motor_number_expr'        => $motor_number_expr,
			'producer_name_expr'       => $producer_name_expr,
			'type_boat_name_expr'      => $type_boat_name_expr,
			'type_hull_name_expr'      => $type_hull_name_expr,
			'type_construction_name_expr' => $type_construction_name_expr,
			'type_ship_name_expr'      => $type_ship_name_expr,
			'hull_material_name_expr'  => $hull_material_name_expr,
			'hull_color_name_expr'     => $hull_color_name_expr,
			'status_name_expr'         => $status_name_expr,
			'date_create_expr'         => $date_create_expr,
			'date_edit_expr'           => $date_edit_expr,
			'date_send_expr'           => $date_send_expr,
			'date_pay_expr'            => $date_pay_expr,
			'date_receiving_expr'      => $date_receiving_expr,
			'date_sale_expr'           => $date_sale_expr,
			'v1_expr'                  => $v1_expr,
			'v2_expr'                  => $v2_expr,
			'f1_expr'                  => $f1_expr,
			'f2_expr'                  => $f2_expr,
			'order_by_sql'             => $order_by_sql,
			'sailboat_table_has_id'    => in_array( 'Sailboat_ID', $sailboat_columns, true ) ? '1' : '0',
		];
	}

	/**
	 * Повертає список реєстру за legacy-логікою пошуку: Sailboat + vApplicationShipTicket.
	 *
	 * @return array{items: array<int,array<string,mixed>>, total: int}|null
	 */
	private function get_sailboats_list_by_legacy_search( string $search, int $region_id, int $status_id, int $per_page, int $offset ): ?array {
		global $wpdb;

		$s_columns  = $this->get_table_columns( 'Sailboat' );
		$va_columns = $this->get_table_columns( 'vApplicationShipTicket' );
		$vs_columns = $this->get_table_columns( 'vSailboat' );

		if ( ! in_array( 'Sailboat_ID', $s_columns, true ) || ! in_array( 'Sailboat_ID', $va_columns, true ) || ! in_array( 'AppShipTicket_ID', $va_columns, true ) ) {
			return null;
		}

		$name_column        = $this->resolve_first_existing_column_name( [ 'Sailboat_Name', 'Name' ], $s_columns );
		$sail_number_column = $this->resolve_first_existing_column_name( [ 'Sailboat_NumberSail', 'NumberSail' ], $s_columns );
		$reg_column         = $this->resolve_first_existing_column_name( [ 'RegNumber' ], $va_columns );
		$user_id_column     = $this->resolve_first_existing_column_name( [ 'User_ID' ], $va_columns );
		$region_id_column   = $this->resolve_first_existing_column_name( [ 'Region_ID' ], $va_columns );
		$status_column      = $this->resolve_first_existing_column_name( [ 'Verification_ID', 'VerificationID' ], $va_columns );

		if ( '' === $name_column ) {
			return null;
		}

		$where_parts = [ 'a.AppShipTicket_ID IS NOT NULL' ];
		$params      = [];
		$like        = '%' . $wpdb->esc_like( $search ) . '%';

		$search_parts = [ 's.`' . $name_column . '` LIKE %s' ];
		$params[]     = $like;

		if ( '' !== $sail_number_column ) {
			$search_parts[] = 's.`' . $sail_number_column . '` LIKE %s';
			$params[]       = $like;
		}

		if ( '' !== $reg_column ) {
			$search_parts[] = 'a.`' . $reg_column . '` LIKE %s';
			$params[]       = $like;
		}

		$where_parts[] = '( ' . implode( ' OR ', $search_parts ) . ' )';

		if ( $region_id > 0 && '' !== $region_id_column ) {
			$where_parts[] = 'a.`' . $region_id_column . '` = %d';
			$params[]      = $region_id;
		}

		if ( $status_id > 0 && '' !== $status_column ) {
			$where_parts[] = 'a.`' . $status_column . '` = %d';
			$params[]      = $status_id;
		}

		$user_id_expr = '' !== $user_id_column ? 'a.`' . $user_id_column . '`' : '0';
		$v1_expr      = $this->build_due_expression( $user_id_expr, 'GetUserDuesSail', -1, "''" );
		$v2_expr      = $this->build_due_expression( $user_id_expr, 'GetUserDuesSail', 0, "''" );
		$f1_expr      = $this->build_due_expression( $user_id_expr, 'GetUserDues', -1, "''" );
		$f2_expr      = $this->build_due_expression( $user_id_expr, 'GetUserDues', 0, "''" );

		$producer_expr = "''";
		$status_expr   = "''";

		if ( ! empty( $vs_columns ) ) {
			$producer_name_column = $this->resolve_first_existing_column_name( [ 'TypeBoat_Name', 'ProducerShipsType', 'ProducerShips_Name' ], $vs_columns );
			$status_name_column   = $this->resolve_first_existing_column_name( [ 'Verification_Name', 'Status_Name' ], $vs_columns );

			if ( '' !== $producer_name_column ) {
				$producer_expr = 'COALESCE(vs.`' . $producer_name_column . '`, \'\')';
			}

			if ( '' !== $status_name_column ) {
				$status_expr = 'COALESCE(vs.`' . $status_name_column . '`, \'\')';
			}
		}

		$owner_expr = $this->build_first_existing_expression(
			[
				[ 'a', 'FIO', $va_columns ],
				[ 'a', 'FIOshort', $va_columns ],
			],
			"''"
		);

		$region_name_expr = $this->build_first_existing_expression(
			[
				[ 'a', 'Region_Name', $va_columns ],
				[ 'vs', 'Region_Name', $vs_columns ],
			],
			"''"
		);

		$registration_date_expr = $this->build_first_existing_expression(
			[
				[ 'a', 'AppShipTicket_DateCreate', $va_columns ],
				[ 'vs', 'AppShipTicket_DateCreate', $vs_columns ],
			],
			"''"
		);

		$where_sql = 'WHERE ' . implode( ' AND ', $where_parts );
		$from_sql  = 'Sailboat s LEFT JOIN vApplicationShipTicket a ON a.Sailboat_ID = s.Sailboat_ID LEFT JOIN vSailboat vs ON vs.AppShipTicket_ID = a.AppShipTicket_ID';

		$count_sql = 'SELECT COUNT(DISTINCT a.AppShipTicket_ID) FROM ' . $from_sql . ' ' . $where_sql;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );

		$list_sql = "SELECT DISTINCT
			a.AppShipTicket_ID AS item_id,
			COALESCE(s.`{$name_column}`, '') AS name,
			" . ( '' !== $reg_column ? 'COALESCE(a.`' . $reg_column . '`, \'\')' : "''" ) . " AS registration_number,
			" . ( '' !== $sail_number_column ? 'COALESCE(s.`' . $sail_number_column . '`, \'\')' : "''" ) . " AS sail_number,
			{$region_name_expr} AS region_name,
			{$owner_expr} AS owner_name,
			{$producer_expr} AS producer_name,
			{$status_expr} AS status_name,
			{$registration_date_expr} AS registration_date,
			{$v1_expr} AS V1,
			{$v2_expr} AS V2,
			{$f1_expr} AS F1,
			{$f2_expr} AS F2
			FROM {$from_sql}
			{$where_sql}
			ORDER BY COALESCE(s.`{$name_column}`, '') ASC, a.AppShipTicket_ID DESC
			LIMIT %d OFFSET %d";

		$list_params = array_merge( $params, [ $per_page, $offset ] );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$items = $wpdb->get_results( $wpdb->prepare( $list_sql, ...$list_params ), ARRAY_A );

		return [
			'items' => is_array( $items ) ? $items : [],
			'total' => $total,
		];
	}

	/**
	 * Повертає true, якщо судно існує в системі.
	 */
	public function sailboat_exists( int $sailboat_id ): bool {
		if ( $sailboat_id <= 0 ) {
			return false;
		}

		global $wpdb;

		$sailboat_columns = $this->get_table_columns( 'Sailboat' );
		if ( in_array( 'Sailboat_ID', $sailboat_columns, true ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
			return (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT 1 FROM Sailboat WHERE Sailboat_ID = %d LIMIT 1', $sailboat_id ) );
		}

		$s_columns = $this->get_table_columns( 'vSailboat' );
		if ( in_array( 'Sailboat_ID', $s_columns, true ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
			return (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT 1 FROM vSailboat WHERE Sailboat_ID = %d LIMIT 1', $sailboat_id ) );
		}

		return false;
	}

	/**
	 * Повертає список доступних існуючих суден для створення нової заявки.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function search_existing_sailboats( string $search, int $limit = 15 ): array {
		global $wpdb;

		$search = trim( sanitize_text_field( $search ) );
		$limit  = max( 1, min( 20, $limit ) );

		if ( '' === $search ) {
			return [];
		}

		$search_context = $this->get_existing_sailboats_search_context();
		if ( [] === $search_context ) {
			return [];
		}

		$source_table     = (string) $search_context['source_table'];
		$source_alias     = (string) $search_context['source_alias'];
		$source_columns   = is_array( $search_context['source_columns'] ) ? $search_context['source_columns'] : [];

		$name_column = $this->resolve_first_existing_column_name(
			[ 'Sailboat_Name', 'Name' ],
			$source_columns
		);

		if ( '' === $name_column ) {
			return [];
		}

		$registration_column = $this->resolve_first_existing_column_name(
			[ 'RegNumber', 'Registration_Number' ],
			$source_columns
		);
		$sail_number_column  = $this->resolve_first_existing_column_name(
			[ 'Sailboat_NumberSail', 'NumberSail', 'Sail_Number' ],
			$source_columns
		);
		$region_id_column    = $this->resolve_first_existing_column_name( [ 'Region_ID' ], $source_columns );
		$verification_column = $this->resolve_first_existing_column_name( [ 'VerificationID', 'Verification_ID' ], $source_columns );

		$region_columns    = $this->get_table_columns( 'S_Region' );
		$region_name_field = $this->resolve_first_existing_column_name( [ 'Region_Name', 'Name' ], $region_columns );
		$region_join_sql   = '';
		$region_name_expr  = "''";

		if ( '' !== $region_id_column && in_array( 'Region_ID', $region_columns, true ) && '' !== $region_name_field ) {
			$region_join_sql  = ' LEFT JOIN S_Region r ON r.Region_ID = ' . $source_alias . '.`' . $region_id_column . '`';
			$region_name_expr = 'COALESCE(r.`' . $region_name_field . '`, \'\')';
		}

		$where_parts = [ $source_alias . '.`' . $name_column . '` LIKE %s' ];
		$params      = [ '%' . $wpdb->esc_like( $search ) . '%' ];

		if ( 'vSailboat' === $source_table && '' !== $verification_column ) {
			$where_filters = '( ' . $source_alias . '.`' . $verification_column . '` = 0'
				. ' OR ' . $source_alias . '.`' . $verification_column . "` = '0'"
				. ' OR ' . $source_alias . '.`' . $verification_column . '` IS NULL )';
		} else {
			$where_filters = '1=1';
		}

		if ( '' !== $registration_column ) {
			$where_parts[] = $source_alias . '.`' . $registration_column . '` LIKE %s';
			$params[]      = '%' . $wpdb->esc_like( $search ) . '%';
		}

		if ( '' !== $sail_number_column ) {
			$where_parts[] = $source_alias . '.`' . $sail_number_column . '` LIKE %s';
			$params[]      = '%' . $wpdb->esc_like( $search ) . '%';
		}

		if ( ctype_digit( $search ) ) {
			$where_parts[] = $source_alias . '.`Sailboat_ID` = %d';
			$params[]      = (int) $search;
		}

		$registration_expr = '' !== $registration_column ? 'COALESCE(' . $source_alias . '.`' . $registration_column . '`, \'\')' : "''";
		$sail_number_expr  = '' !== $sail_number_column ? 'COALESCE(' . $source_alias . '.`' . $sail_number_column . '`, \'\')' : "''";
		$region_id_expr    = '' !== $region_id_column ? 'COALESCE(' . $source_alias . '.`' . $region_id_column . '`, 0)' : '0';
		$query_limit       = min( 60, max( $limit * 3, $limit ) );

		$sql = "SELECT DISTINCT {$source_alias}.`Sailboat_ID` AS sailboat_id,
			COALESCE({$source_alias}.`{$name_column}`, '') AS sailboat_name,
			{$registration_expr} AS registration_number,
			{$sail_number_expr} AS sail_number,
			{$region_id_expr} AS region_id,
			{$region_name_expr} AS region_name
			FROM {$source_table} {$source_alias}
			{$region_join_sql}
			WHERE {$where_filters}
			AND (" . implode( ' OR ', $where_parts ) . ")
			ORDER BY COALESCE({$source_alias}.`{$name_column}`, '') ASC, {$source_alias}.`Sailboat_ID` DESC
			LIMIT %d";

		$params[] = $query_limit;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );

		if ( ! is_array( $rows ) ) {
			return [];
		}

		$items = [];

		foreach ( $rows as $row ) {
			$sailboat_id = (int) ( $row['sailboat_id'] ?? 0 );

			if ( $sailboat_id <= 0 || $this->has_active_application_ship_ticket_for_sailboat( $sailboat_id ) ) {
				continue;
			}

			$name                = trim( (string) ( $row['sailboat_name'] ?? '' ) );
			$registration_number = trim( (string) ( $row['registration_number'] ?? '' ) );
			$sail_number         = trim( (string) ( $row['sail_number'] ?? '' ) );
			$region_name         = trim( (string) ( $row['region_name'] ?? '' ) );

			$label_parts = [ '#' . $sailboat_id, '' !== $name ? $name : __( 'Без назви', 'fstu' ) ];

			if ( '' !== $registration_number ) {
				$label_parts[] = '№ реєстрації: ' . $registration_number;
			}

			if ( '' !== $region_name ) {
				$label_parts[] = $region_name;
			}

			$items[] = [
				'sailboat_id'         => $sailboat_id,
				'name'                => $name,
				'registration_number' => $registration_number,
				'sail_number'         => $sail_number,
				'region_id'           => (int) ( $row['region_id'] ?? 0 ),
				'region_name'         => $region_name,
				'label'               => implode( ' — ', $label_parts ),
			];

			if ( count( $items ) >= $limit ) {
				break;
			}
		}

		return $items;
	}

	/**
	 * Повертає контекст джерела даних для пошуку існуючих суден.
	 *
	 * @return array<string,mixed>
	 */
	private function get_existing_sailboats_search_context(): array {
		$view_columns = $this->get_table_columns( 'vSailboat' );
		if ( in_array( 'Sailboat_ID', $view_columns, true ) ) {
			return [
				'source_table'   => 'vSailboat',
				'source_alias'   => 's',
				'source_columns' => $view_columns,
			];
		}

		$sailboat_columns = $this->get_table_columns( 'Sailboat' );
		if ( in_array( 'Sailboat_ID', $sailboat_columns, true ) ) {
			return [
				'source_table'   => 'Sailboat',
				'source_alias'   => 's',
				'source_columns' => $sailboat_columns,
			];
		}

		return [];
	}

	/**
	 * Перевіряє, чи існує для судна активна заявка / судновий квиток.
	 */
	public function has_active_application_ship_ticket_for_sailboat( int $sailboat_id ): bool {
		if ( $sailboat_id <= 0 ) {
			return false;
		}

		$columns = $this->get_table_columns( 'ApplicationShipTicket' );
		if ( ! in_array( 'Sailboat_ID', $columns, true ) ) {
			return false;
		}

		global $wpdb;

		$sql = 'SELECT 1 FROM ApplicationShipTicket WHERE Sailboat_ID = %d';

		if ( in_array( 'AppShipTicket_DateSale', $columns, true ) ) {
			$sql .= " AND (AppShipTicket_DateSale IS NULL OR AppShipTicket_DateSale = '')";
		}

		$sql .= ' LIMIT 1';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		return (bool) $wpdb->get_var( $wpdb->prepare( $sql, $sailboat_id ) );
	}

	/**
	 * Повертає контекст запису для update-flow.
	 *
	 * @return array<string,int>|null
	 */
	public function get_item_context( int $item_id ): ?array {
		if ( $item_id <= 0 ) {
			return null;
		}

		global $wpdb;

		$a_columns            = $this->get_table_columns( 'ApplicationShipTicket' );
		$s_columns            = $this->get_table_columns( 'vSailboat' );
		$app_ticket_id_expr   = in_array( 'AppShipTicket_ID', $a_columns, true ) ? 'a.`AppShipTicket_ID`' : '0';
		$sailboat_id_expr     = $this->build_first_existing_column_reference(
			[
				[ 's', 'Sailboat_ID', $s_columns ],
				[ 'a', 'Sailboat_ID', $a_columns ],
			],
			'0'
		);

		$sql = "SELECT {$app_ticket_id_expr} AS item_id, {$app_ticket_id_expr} AS appshipticket_id, {$sailboat_id_expr} AS sailboat_id
			FROM ApplicationShipTicket a
			LEFT JOIN vSailboat s ON s.AppShipTicket_ID = a.AppShipTicket_ID
			WHERE a.AppShipTicket_ID = %d
			LIMIT 1";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( $sql, $item_id ), ARRAY_A );

		if ( ! is_array( $row ) ) {
			return null;
		}

		return [
			'item_id'          => (int) ( $row['item_id'] ?? 0 ),
			'appshipticket_id' => (int) ( $row['appshipticket_id'] ?? 0 ),
			'sailboat_id'      => (int) ( $row['sailboat_id'] ?? 0 ),
		];
	}

	/**
	 * Створює запис у таблиці Sailboat.
	 */
	public function insert_sailboat( array $data ): int {
		global $wpdb;

		$columns = $this->get_table_columns( 'Sailboat' );
		if ( ! in_array( 'Sailboat_Name', $columns, true ) ) {
			throw new \RuntimeException( 'sailboat_table_schema_invalid' );
		}

		$insert = [];
		$formats = [];

		if ( in_array( 'Sailboat_DateCreate', $columns, true ) ) {
			$insert['Sailboat_DateCreate'] = current_time( 'mysql' );
			$formats[] = '%s';
		}

		$insert['Sailboat_Name'] = (string) $data['sailboat_name'];
		$formats[] = '%s';

		$this->append_sailboat_mapped_fields( $insert, $formats, $columns, $data );

		if ( in_array( 'User_ID', $columns, true ) ) {
			$insert['User_ID'] = get_current_user_id();
			$formats[] = '%d';
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$inserted = $wpdb->insert( 'Sailboat', $insert, $formats );

		if ( false === $inserted ) {
			throw new \RuntimeException( 'sailboat_insert_failed' );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Створює запис у таблиці ApplicationShipTicket.
	 */
	public function insert_application_ship_ticket( array $data, int $sailboat_id = 0 ): int {
		global $wpdb;

		$columns = $this->get_table_columns( 'ApplicationShipTicket' );
		if ( ! in_array( 'AppShipTicket_ID', $columns, true ) && empty( $columns ) ) {
			throw new \RuntimeException( 'application_ship_ticket_schema_invalid' );
		}

		$insert = [];
		$formats = [];

		if ( in_array( 'AppShipTicket_DateCreate', $columns, true ) ) {
			$insert['AppShipTicket_DateCreate'] = current_time( 'mysql' );
			$formats[] = '%s';
		}

		if ( in_array( 'AppShipTicket_DateEdit', $columns, true ) ) {
			$insert['AppShipTicket_DateEdit'] = current_time( 'mysql' );
			$formats[] = '%s';
		}

		if ( in_array( 'User_ID', $columns, true ) ) {
			$insert['User_ID'] = get_current_user_id();
			$formats[] = '%d';
		}

		if ( in_array( 'Sailboat_ID', $columns, true ) && $sailboat_id > 0 ) {
			$insert['Sailboat_ID'] = $sailboat_id;
			$formats[] = '%d';
		}


		$this->append_application_ship_ticket_mapped_fields( $insert, $formats, $columns, $data, false );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$inserted = $wpdb->insert( 'ApplicationShipTicket', $insert, $formats );

		if ( false === $inserted ) {
			throw new \RuntimeException( 'application_ship_ticket_insert_failed' );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Підтримує зв’язок між Sailboat та ApplicationShipTicket, якщо це потрібно схемою.
	 */
	public function sync_sailboat_application_link( int $sailboat_id, int $appshipticket_id ): void {
		global $wpdb;

		if ( $sailboat_id <= 0 || $appshipticket_id <= 0 ) {
			return;
		}

		$columns = $this->get_table_columns( 'Sailboat' );

		if ( in_array( 'AppShipTicket_ID', $columns, true ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$updated = $wpdb->update(
				'Sailboat',
				[ 'AppShipTicket_ID' => $appshipticket_id ],
				[ 'Sailboat_ID' => $sailboat_id ],
				[ '%d' ],
				[ '%d' ]
			);

			if ( false === $updated ) {
				throw new \RuntimeException( 'sailboat_link_update_failed' );
			}
		}
	}

	/**
	 * Оновлює запис Sailboat.
	 */
	public function update_sailboat( int $sailboat_id, array $data ): bool {
		global $wpdb;

		if ( $sailboat_id <= 0 ) {
			return true;
		}

		$columns = $this->get_table_columns( 'Sailboat' );
		if ( ! in_array( 'Sailboat_ID', $columns, true ) ) {
			return true;
		}

		$update = [];
		$formats = [];

		if ( in_array( 'Sailboat_Name', $columns, true ) ) {
			$update['Sailboat_Name'] = (string) $data['sailboat_name'];
			$formats[] = '%s';
		}

		$this->append_sailboat_mapped_fields( $update, $formats, $columns, $data, true );

		if ( in_array( 'Sailboat_DateEdit', $columns, true ) ) {
			$update['Sailboat_DateEdit'] = current_time( 'mysql' );
			$formats[] = '%s';
		}

		if ( empty( $update ) ) {
			return true;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$updated = $wpdb->update( 'Sailboat', $update, [ 'Sailboat_ID' => $sailboat_id ], $formats, [ '%d' ] );

		if ( false === $updated ) {
			throw new \RuntimeException( 'sailboat_update_failed' );
		}

		return true;
	}

	/**
	 * Оновлює запис ApplicationShipTicket.
	 */
	public function update_application_ship_ticket( int $appshipticket_id, array $data ): bool {
		global $wpdb;

		if ( $appshipticket_id <= 0 ) {
			throw new \RuntimeException( 'application_ship_ticket_not_found' );
		}

		$columns = $this->get_table_columns( 'ApplicationShipTicket' );
		if ( ! in_array( 'AppShipTicket_ID', $columns, true ) ) {
			throw new \RuntimeException( 'application_ship_ticket_schema_invalid' );
		}

		$update = [];
		$formats = [];

		$this->append_application_ship_ticket_mapped_fields( $update, $formats, $columns, $data, true );

		if ( in_array( 'AppShipTicket_DateEdit', $columns, true ) ) {
			$update['AppShipTicket_DateEdit'] = current_time( 'mysql' );
			$formats[] = '%s';
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$updated = $wpdb->update( 'ApplicationShipTicket', $update, [ 'AppShipTicket_ID' => $appshipticket_id ], $formats, [ '%d' ] );

		if ( false === $updated ) {
			throw new \RuntimeException( 'application_ship_ticket_update_failed' );
		}

		return true;
	}

	/**
	 * Оновлює статус заявки / суднового квитка.
	 */
	public function update_application_status( int $appshipticket_id, int $verification_id, string $comment = '' ): bool {
		$columns = $this->get_table_columns( 'ApplicationShipTicket' );

		if ( ! in_array( 'Verification_ID', $columns, true ) ) {
			throw new \RuntimeException( 'application_ship_ticket_status_unsupported' );
		}

		$update = [ 'Verification_ID' => $verification_id ];
		$formats = [ '%d' ];

		$this->append_comment_column( $update, $formats, $columns, $comment );
		$this->append_date_edit_column( $update, $formats, $columns );

		return $this->run_application_ship_ticket_update( $appshipticket_id, $update, $formats );
	}

	/**
	 * Зберігає фінансову операцію по заявці / судновому квитку.
	 */
	public function set_application_payment( int $appshipticket_id, float $amount, string $payment_date, string $payment_slot, string $comment = '' ): bool {
		$columns = $this->get_table_columns( 'ApplicationShipTicket' );
		$update  = [];
		$formats = [];

		if ( in_array( 'AppShipTicket_Summa', $columns, true ) ) {
			$update['AppShipTicket_Summa'] = $amount;
			$formats[] = '%f';
		}

		if ( in_array( 'AppShipTicket_DatePay', $columns, true ) ) {
			$update['AppShipTicket_DatePay'] = $payment_date;
			$formats[] = '%s';
		}

		if ( in_array( $payment_slot, $columns, true ) ) {
			$update[ $payment_slot ] = $amount;
			$formats[] = '%f';
		}

		$slot_date_column = $this->find_first_column_name(
			$columns,
			[
				$payment_slot . '_DatePay',
				$payment_slot . '_Date',
				'AppShipTicket_' . $payment_slot . '_DatePay',
			]
		);

		if ( '' !== $slot_date_column ) {
			$update[ $slot_date_column ] = $payment_date;
			$formats[] = '%s';
		}

		$this->append_comment_column( $update, $formats, $columns, $comment );
		$this->append_date_edit_column( $update, $formats, $columns );

		if ( empty( $update ) ) {
			throw new \RuntimeException( 'application_ship_ticket_payment_unsupported' );
		}

		return $this->run_application_ship_ticket_update( $appshipticket_id, $update, $formats );
	}

	/**
	 * Фіксує вручення / доставку документа.
	 */
	public function mark_application_received( int $appshipticket_id, string $received_at, string $comment = '' ): bool {
		$columns = $this->get_table_columns( 'ApplicationShipTicket' );

		if ( ! in_array( 'AppShipTicket_DateReceiving', $columns, true ) ) {
			throw new \RuntimeException( 'application_ship_ticket_received_unsupported' );
		}

		$update = [ 'AppShipTicket_DateReceiving' => $received_at ];
		$formats = [ '%s' ];

		$this->append_comment_column( $update, $formats, $columns, $comment );
		$this->append_date_edit_column( $update, $formats, $columns );

		return $this->run_application_ship_ticket_update( $appshipticket_id, $update, $formats );
	}

	/**
	 * Фіксує продаж / вибуття судна.
	 */
	public function mark_application_sale( int $appshipticket_id, string $sale_date, string $comment = '' ): bool {
		$columns = $this->get_table_columns( 'ApplicationShipTicket' );

		if ( ! in_array( 'AppShipTicket_DateSale', $columns, true ) ) {
			throw new \RuntimeException( 'application_ship_ticket_sale_unsupported' );
		}

		$update = [ 'AppShipTicket_DateSale' => $sale_date ];
		$formats = [ '%s' ];

		$this->append_comment_column( $update, $formats, $columns, $comment );
		$this->append_date_edit_column( $update, $formats, $columns );

		return $this->run_application_ship_ticket_update( $appshipticket_id, $update, $formats );
	}

	/**
	 * Повертає контекст для надсилання повідомлення щодо внесків.
	 *
	 * @return array<string,mixed>|null
	 */
	public function get_notification_context( int $item_id ): ?array {
		$context = $this->get_item_context( $item_id );
		if ( ! is_array( $context ) ) {
			return null;
		}

		$item = $this->get_sailboat_by_id( $item_id );
		if ( ! is_array( $item ) ) {
			return null;
		}

		$email = $this->resolve_notification_email( (int) ( $context['appshipticket_id'] ?? 0 ), (int) ( $context['sailboat_id'] ?? 0 ) );

		$item['notification_email'] = $email;

		return array_merge( $item, $context );
	}

	/**
	 * Перевіряє залежності перед hard delete.
	 *
	 * @return array<string,mixed>
	 */
	public function check_delete_dependencies( int $item_id ): array {
		$context = $this->get_item_context( $item_id );
		if ( ! is_array( $context ) ) {
			return [
				'can_delete' => false,
				'message'    => __( 'Запис не знайдено.', 'fstu' ),
				'status'     => 'missing',
			];
		}

		$appshipticket_id = (int) ( $context['appshipticket_id'] ?? 0 );
		$sailboat_id      = (int) ( $context['sailboat_id'] ?? 0 );

		$merilka_check = $this->check_merilka_dependency( $appshipticket_id, $sailboat_id );
		if ( ! $merilka_check['can_delete'] ) {
			return $merilka_check;
		}

		$application_ids = $this->get_related_application_ids( $appshipticket_id, $sailboat_id );
		$other_tickets   = $this->count_other_application_ship_tickets_for_sailboat( $appshipticket_id, $sailboat_id );
		$can_delete_boat = $sailboat_id > 0 && 0 === $other_tickets;

		return [
			'can_delete'        => true,
			'message'           => '',
			'status'            => 'ok',
			'context'           => $context,
			'application_ids'   => $application_ids,
			'delete_sailboat'   => $can_delete_boat,
			'other_tickets'     => $other_tickets,
		];
	}

	/**
	 * Виконує hard delete allowlist-каскадом.
	 *
	 * @param array<string,mixed> $plan План видалення.
	 */
	public function delete_item_by_plan( array $plan ): bool {
		global $wpdb;

		$context = isset( $plan['context'] ) && is_array( $plan['context'] ) ? $plan['context'] : [];
		$appshipticket_id = (int) ( $context['appshipticket_id'] ?? 0 );
		$sailboat_id      = (int) ( $context['sailboat_id'] ?? 0 );
		$application_ids  = isset( $plan['application_ids'] ) && is_array( $plan['application_ids'] ) ? $plan['application_ids'] : [];
		$delete_sailboat  = ! empty( $plan['delete_sailboat'] );

		if ( $appshipticket_id <= 0 ) {
			throw new \RuntimeException( 'application_ship_ticket_not_found' );
		}

		foreach ( $application_ids as $application_id ) {
			$application_id = absint( $application_id );
			if ( $application_id > 0 ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$deleted = $wpdb->delete( 'Application', [ 'Application_ID' => $application_id ], [ '%d' ] );
				if ( false === $deleted ) {
					throw new \RuntimeException( 'application_delete_failed' );
				}
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$deleted_ticket = $wpdb->delete( 'ApplicationShipTicket', [ 'AppShipTicket_ID' => $appshipticket_id ], [ '%d' ] );
		if ( false === $deleted_ticket || 1 > (int) $deleted_ticket ) {
			throw new \RuntimeException( 'application_ship_ticket_delete_failed' );
		}

		if ( $delete_sailboat && $sailboat_id > 0 ) {
			$sailboat_columns = $this->get_table_columns( 'Sailboat' );
			if ( in_array( 'Sailboat_ID', $sailboat_columns, true ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$deleted_sailboat = $wpdb->delete( 'Sailboat', [ 'Sailboat_ID' => $sailboat_id ], [ '%d' ] );
				if ( false === $deleted_sailboat ) {
					throw new \RuntimeException( 'sailboat_delete_failed' );
				}
			}
		}

		return true;
	}

	/**
	 * Будує SQL вираз сортування для списку.
	 */
	private function build_order_by_sql( string $date_create_expr, string $name_expr, string $item_id_expr ): string {
		$order_parts = [];

		if ( "''" !== $date_create_expr ) {
			$order_parts[] = $date_create_expr . ' DESC';
		}

		if ( "''" !== $name_expr ) {
			$order_parts[] = $name_expr . ' ASC';
		}

		if ( '' !== $item_id_expr ) {
			$order_parts[] = $item_id_expr . ' DESC';
		}

		return ! empty( $order_parts ) ? implode( ', ', $order_parts ) : '1 ASC';
	}

	/**
	 * Будує SQL вираз ПІБ власника / капітана.
	 *
	 * @param array<int,string> $a_columns Колонки ApplicationShipTicket.
	 * @param array<int,string> $s_columns Колонки vSailboat.
	 */
	private function build_owner_expression( array $a_columns, array $s_columns ): string {
		$name_parts = [];

		foreach ( [ 'AppShipTicket_LastName', 'AppShipTicket_FirstName', 'AppShipTicket_Patronymic' ] as $column_name ) {
			if ( in_array( $column_name, $a_columns, true ) ) {
				$name_parts[] = "NULLIF(a.`{$column_name}`, '')";
			}
		}

		if ( ! empty( $name_parts ) ) {
			return 'TRIM(CONCAT_WS(\' \' , ' . implode( ', ', $name_parts ) . '))';
		}

		return $this->build_first_existing_expression(
			[
				[ 's', 'OwnerFIO', $s_columns ],
				[ 's', 'FIO', $s_columns ],
				[ 's', 'UserFIO', $s_columns ],
				[ 's', 'CaptainFIO', $s_columns ],
			],
			"''"
		);
	}

	/**
	 * Повертає перший доступний SQL вираз колонки.
	 *
	 * @param array<int,array{0:string,1:string,2:array<int,string>}> $candidates Кандидати у форматі alias/column/available_columns.
	 */
	private function build_first_existing_expression( array $candidates, string $fallback ): string {
		foreach ( $candidates as $candidate ) {
			[ $alias, $column_name, $available_columns ] = $candidate;

			if ( in_array( $column_name, $available_columns, true ) ) {
				return "COALESCE(`{$alias}`.`{$column_name}`, '')";
			}
		}

		return $fallback;
	}

	/**
	 * Повертає перше доступне raw-посилання на колонку без COALESCE.
	 *
	 * @param array<int,array{0:string,1:string,2:array<int,string>}> $candidates Кандидати у форматі alias/column/available_columns.
	 */
	private function build_first_existing_column_reference( array $candidates, string $fallback ): string {
		foreach ( $candidates as $candidate ) {
			[ $alias, $column_name, $available_columns ] = $candidate;

			if ( in_array( $column_name, $available_columns, true ) ) {
				return "`{$alias}`.`{$column_name}`";
			}
		}

		return $fallback;
	}

	/**
	 * Будує SQL-вираз фінансового індикатора через legacy-функції dues з fallback на наявну колонку.
	 */
	private function build_due_expression( string $user_id_expr, string $function_name, int $year_offset, string $fallback_expr ): string {
		if ( '0' === $user_id_expr || '' === trim( $user_id_expr ) ) {
			return $fallback_expr;
		}

		$year_expr = 0 === $year_offset ? 'YEAR(NOW())' : 'YEAR(NOW()) ' . ( $year_offset > 0 ? '+ ' . $year_offset : '- ' . abs( $year_offset ) );

		return 'CASE WHEN COALESCE(' . $user_id_expr . ', 0) > 0 THEN ' . $function_name . '(' . $user_id_expr . ', ' . $year_expr . ') ELSE ' . $fallback_expr . ' END';
	}

	/**
	 * Повертає першу доступну назву колонки з allowlist.
	 *
	 * @param array<int,string> $candidates
	 * @param array<int,string> $available_columns
	 */
	private function resolve_first_existing_column_name( array $candidates, array $available_columns ): string {
		foreach ( $candidates as $candidate ) {
			if ( in_array( $candidate, $available_columns, true ) ) {
				return $candidate;
			}
		}

		return '';
	}

	/**
	 * Повертає список колонок таблиці / view з information_schema.
	 *
	 * @return array<int,string>
	 */
	private function get_table_columns( string $table_name ): array {
		static $cache = [];

		if ( isset( $cache[ $table_name ] ) ) {
			return $cache[ $table_name ];
		}

		$schema_name = defined( 'DB_NAME' ) ? (string) DB_NAME : '';
		if ( '' === $schema_name ) {
			$cache[ $table_name ] = [];

			return $cache[ $table_name ];
		}

		global $wpdb;

		$sql = 'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s ORDER BY ORDINAL_POSITION ASC';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$columns = $wpdb->get_col( $wpdb->prepare( $sql, $schema_name, $table_name ) );

		$cache[ $table_name ] = is_array( $columns ) ? array_values( array_map( 'strval', $columns ) ) : [];

		return $cache[ $table_name ];
	}

	/**
	 * Очищає кеш довідників фільтрів.
	 */
	public function clear_filter_datasets_cache(): void {
		wp_cache_delete( self::FILTER_DATASETS_CACHE_KEY, 'fstu_sailboats' );
		delete_transient( self::FILTER_DATASETS_CACHE_KEY );
	}

	/**
	 * Повертає значення системного налаштування з таблиці Settings.
	 */
	public function get_setting_value( string $param_name ): string {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$value = $wpdb->get_var(
			$wpdb->prepare( 'SELECT ParamValue FROM Settings WHERE ParamName = %s', $param_name )
		);

		return is_string( $value ) ? $value : '';
	}

	/**
	 * Додає поле коментаря до update-масиву, якщо підтримується схемою.
	 *
	 * @param array<string,mixed> $update  Дані для update.
	 * @param array<int,string>   $formats Формати update.
	 * @param array<int,string>   $columns Колонки таблиці.
	 */
	private function append_comment_column( array &$update, array &$formats, array $columns, string $comment ): void {
		$comment_column = $this->find_first_column_name(
			$columns,
			[
				'AppShipTicket_Comment',
				'AppShipTicket_Note',
				'AppShipTicket_Notes',
				'AppShipTicket_Text',
				'Comment',
				'Notes',
			]
		);

		if ( '' !== $comment_column && '' !== trim( $comment ) ) {
			$update[ $comment_column ] = $comment;
			$formats[] = '%s';
		}
	}

	/**
	 * Додає службове поле дати редагування, якщо воно існує.
	 *
	 * @param array<string,mixed> $update  Дані для update.
	 * @param array<int,string>   $formats Формати update.
	 * @param array<int,string>   $columns Колонки таблиці.
	 */
	private function append_date_edit_column( array &$update, array &$formats, array $columns ): void {
		if ( in_array( 'AppShipTicket_DateEdit', $columns, true ) ) {
			$update['AppShipTicket_DateEdit'] = current_time( 'mysql' );
			$formats[] = '%s';
		}
	}

	/**
	 * Виконує update по таблиці ApplicationShipTicket.
	 *
	 * @param array<string,mixed> $update  Дані для update.
	 * @param array<int,string>   $formats Формати update.
	 */
	private function run_application_ship_ticket_update( int $appshipticket_id, array $update, array $formats ): bool {
		global $wpdb;

		if ( $appshipticket_id <= 0 ) {
			throw new \RuntimeException( 'application_ship_ticket_not_found' );
		}

		if ( empty( $update ) ) {
			return true;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$updated = $wpdb->update( 'ApplicationShipTicket', $update, [ 'AppShipTicket_ID' => $appshipticket_id ], $formats, [ '%d' ] );

		if ( false === $updated ) {
			throw new \RuntimeException( 'application_ship_ticket_update_failed' );
		}

		return true;
	}

	/**
	 * Повертає першу доступну назву колонки зі списку кандидатів.
	 *
	 * @param array<int,string> $columns    Колонки таблиці.
	 * @param array<int,string> $candidates Кандидати.
	 */
	private function find_first_column_name( array $columns, array $candidates ): string {
		foreach ( $candidates as $candidate ) {
			if ( in_array( $candidate, $columns, true ) ) {
				return $candidate;
			}
		}

		return '';
	}

	/**
	 * Повертає рядки довідника з безпечним визначенням поля назви.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_dictionary_rows( string $table_name, string $id_column, array $name_candidates ): array {
		$columns = $this->get_table_columns( $table_name );
		if ( ! in_array( $id_column, $columns, true ) ) {
			return [];
		}

		$name_column = $this->find_first_column_name( $columns, $name_candidates );
		if ( '' === $name_column ) {
			return [];
		}

		global $wpdb;

		$sql = "SELECT `{$id_column}` AS item_id, `{$name_column}` AS item_name FROM `{$table_name}` ORDER BY `{$name_column}` ASC";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $sql, ARRAY_A );

		return is_array( $rows ) ? $this->normalize_dictionary_rows( $rows, $id_column, $name_column ) : [];
	}

	/**
	 * Повертає словник міст із прив’язкою до області, якщо Region_ID існує.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_city_dictionary_rows(): array {
		$columns = $this->get_table_columns( 'S_City' );
		if ( ! in_array( 'City_ID', $columns, true ) ) {
			return [];
		}

		$name_column = $this->find_first_column_name( $columns, [ 'City_Name', 'Name' ] );
		if ( '' === $name_column ) {
			return [];
		}

		$select_columns = [ '`City_ID` AS item_id', "`{$name_column}` AS item_name" ];
		if ( in_array( 'Region_ID', $columns, true ) ) {
			$select_columns[] = '`Region_ID` AS region_id';
		}

		global $wpdb;

		$sql = 'SELECT ' . implode( ', ', $select_columns ) . ' FROM `S_City` ORDER BY `' . $name_column . '` ASC';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $sql, ARRAY_A );

		return is_array( $rows ) ? $this->normalize_dictionary_rows( $rows, 'City_ID', $name_column, true ) : [];
	}

	/**
	 * Нормалізує рядки довідника до frontend-friendly структури.
	 *
	 * @param array<int,array<string,mixed>> $rows Рядки довідника.
	 * @return array<int,array<string,mixed>>
	 */
	private function normalize_dictionary_rows( array $rows, string $id_column, string $name_column, bool $include_region = false ): array {
		$result = [];

		foreach ( $rows as $row ) {
			$item_id   = absint( $row['item_id'] ?? 0 );
			$item_name = trim( (string) ( $row['item_name'] ?? '' ) );

			if ( $item_id <= 0 || '' === $item_name ) {
				continue;
			}

			$normalized = [
				$id_column   => $item_id,
				$name_column => $item_name,
			];

			if ( $include_region ) {
				$normalized['Region_ID'] = absint( $row['region_id'] ?? 0 );
			}

			$result[] = $normalized;
		}

		return $result;
	}

	/**
	 * Додає schema-aware mapped поля Sailboat до insert/update.
	 *
	 * @param array<string,mixed> $target  Цільовий масив insert/update.
	 * @param array<int,string>   $formats Формати цільового масиву.
	 * @param array<int,string>   $columns Колонки таблиці.
	 * @param array<string,mixed> $data    Дані форми.
	 */
	private function append_sailboat_mapped_fields( array &$target, array &$formats, array $columns, array $data, bool $allow_empty_string = false ): void {
		$field_map = [
			'Sailboat_NameEng'      => [ 'key' => 'sailboat_name_eng', 'format' => '%s', 'type' => 'string' ],
			'Sailboat_NumberSail'   => [ 'key' => 'sailboat_number_sail', 'format' => '%s', 'type' => 'string' ],
			'Sailboat_Year'         => [ 'key' => 'sailboat_year', 'format' => '%d', 'type' => 'int' ],
			'Sailboat_SailMain'     => [ 'key' => 'sailboat_sail_main', 'format' => '%f', 'type' => 'float' ],
			'Sailboat_HillLength'   => [ 'key' => 'sailboat_hill_length', 'format' => '%f', 'type' => 'float' ],
			'Sailboat_CrewMax'      => [ 'key' => 'sailboat_crew_max', 'format' => '%d', 'type' => 'int' ],
			'Sailboat_WidthOverall' => [ 'key' => 'sailboat_width_overall', 'format' => '%f', 'type' => 'float' ],
			'Sailboat_Clearance'    => [ 'key' => 'sailboat_clearance', 'format' => '%f', 'type' => 'float' ],
			'Sailboat_LoadCapacity' => [ 'key' => 'sailboat_load_capacity', 'format' => '%f', 'type' => 'float' ],
			'Sailboat_MotorPower'   => [ 'key' => 'sailboat_motor_power', 'format' => '%f', 'type' => 'float' ],
			'Sailboat_MotorNumber'  => [ 'key' => 'sailboat_motor_number', 'format' => '%s', 'type' => 'string' ],
			'Region_ID'             => [ 'key' => 'region_id', 'format' => '%d', 'type' => 'int' ],
			'City_ID'               => [ 'key' => 'city_id', 'format' => '%d', 'type' => 'int' ],
			'ProducerShips_ID'      => [ 'key' => 'producer_id', 'format' => '%d', 'type' => 'int' ],
			'TypeBoat_ID'           => [ 'key' => 'type_boat_id', 'format' => '%d', 'type' => 'int' ],
			'TypeHull_ID'           => [ 'key' => 'type_hull_id', 'format' => '%d', 'type' => 'int' ],
			'TypeConstruction_ID'   => [ 'key' => 'type_construction_id', 'format' => '%d', 'type' => 'int' ],
			'TypeShip_ID'           => [ 'key' => 'type_ship_id', 'format' => '%d', 'type' => 'int' ],
			'HullMaterial_ID'       => [ 'key' => 'hull_material_id', 'format' => '%d', 'type' => 'int' ],
			'HullColor_ID'          => [ 'key' => 'hull_color_id', 'format' => '%d', 'type' => 'int' ],
		];

		foreach ( $field_map as $column_name => $config ) {
			if ( ! in_array( $column_name, $columns, true ) ) {
				continue;
			}

			$this->append_mapped_value( $target, $formats, $column_name, $config, $data, $allow_empty_string );
		}
	}

	/**
	 * Додає schema-aware mapped поля ApplicationShipTicket до insert/update.
	 *
	 * @param array<string,mixed> $target  Цільовий масив insert/update.
	 * @param array<int,string>   $formats Формати цільового масиву.
	 * @param array<int,string>   $columns Колонки таблиці.
	 * @param array<string,mixed> $data    Дані форми.
	 */
	private function append_application_ship_ticket_mapped_fields( array &$target, array &$formats, array $columns, array $data, bool $allow_empty_string = false ): void {
		$field_map = [
			'Region_ID'                  => [ 'key' => 'region_id', 'format' => '%d', 'type' => 'int' ],
			'City_ID'                    => [ 'key' => 'city_id', 'format' => '%d', 'type' => 'int' ],
			'Verification_ID'            => [ 'key' => 'verification_id', 'format' => '%d', 'type' => 'int' ],
			'AppShipTicket_LastName'     => [ 'key' => 'appshipticket_last_name', 'format' => '%s', 'type' => 'string' ],
			'AppShipTicket_FirstName'    => [ 'key' => 'appshipticket_first_name', 'format' => '%s', 'type' => 'string' ],
			'AppShipTicket_Patronymic'   => [ 'key' => 'appshipticket_patronymic', 'format' => '%s', 'type' => 'string' ],
			'AppShipTicket_LastNameEng'  => [ 'key' => 'appshipticket_last_name_eng', 'format' => '%s', 'type' => 'string' ],
			'AppShipTicket_FirstNameEng' => [ 'key' => 'appshipticket_first_name_eng', 'format' => '%s', 'type' => 'string' ],
			'AppShipTicket_NP'           => [ 'key' => 'appshipticket_np', 'format' => '%s', 'type' => 'string' ],
			'AppShipTicket_NumberManual' => [ 'key' => 'appshipticket_number_manual', 'format' => '%s', 'type' => 'string' ],
		];

		foreach ( $field_map as $column_name => $config ) {
			if ( ! in_array( $column_name, $columns, true ) ) {
				continue;
			}

			$this->append_mapped_value( $target, $formats, $column_name, $config, $data, $allow_empty_string );
		}
	}

	/**
	 * Додає одне whitelisted-значення до insert/update масиву.
	 *
	 * @param array<string,mixed>                         $target  Цільовий масив insert/update.
	 * @param array<int,string>                           $formats Формати цільового масиву.
	 * @param array{key:string,format:string,type:string} $config  Конфіг поля.
	 * @param array<string,mixed>                         $data    Дані форми.
	 */
	private function append_mapped_value( array &$target, array &$formats, string $column_name, array $config, array $data, bool $allow_empty_string ): void {
		$key    = $config['key'];
		$type   = $config['type'];
		$format = $config['format'];
		$value  = $data[ $key ] ?? null;

		if ( 'string' === $type ) {
			$value = trim( (string) $value );
			if ( '' === $value && ! $allow_empty_string ) {
				return;
			}
			$target[ $column_name ] = $value;
			$formats[]              = $format;
			return;
		}

		if ( 'int' === $type ) {
			$value = absint( $value );
			if ( $value <= 0 ) {
				return;
			}
			$target[ $column_name ] = $value;
			$formats[]              = $format;
			return;
		}

		if ( 'float' === $type ) {
			$value = (float) $value;
			if ( $value <= 0 ) {
				return;
			}
			$target[ $column_name ] = $value;
			$formats[]              = $format;
		}
	}

	/**
	 * Повертає email-одержувача для повідомлення щодо внесків.
	 */
	private function resolve_notification_email( int $appshipticket_id, int $sailboat_id ): string {
		global $wpdb;

		$a_columns = $this->get_table_columns( 'ApplicationShipTicket' );
		$s_columns = $this->get_table_columns( 'vSailboat' );

		$email_expr = $this->build_first_existing_expression(
			[
				[ 'a', 'AppShipTicket_Email', $a_columns ],
				[ 'a', 'Email', $a_columns ],
				[ 's', 'Email', $s_columns ],
				[ 's', 'UserEmail', $s_columns ],
				[ 's', 'OwnerEmail', $s_columns ],
			],
			"''"
		);

		$user_id_expr = $this->build_first_existing_column_reference(
			[
				[ 'a', 'User_ID', $a_columns ],
				[ 's', 'User_ID', $s_columns ],
			],
			'0'
		);

		if ( "''" !== $email_expr || '0' !== $user_id_expr ) {
			$where_parts = [];
			$params      = [];

			if ( $appshipticket_id > 0 && in_array( 'AppShipTicket_ID', $a_columns, true ) ) {
				$where_parts[] = 'a.AppShipTicket_ID = %d';
				$params[]      = $appshipticket_id;
			}

			if ( empty( $where_parts ) && $sailboat_id > 0 && in_array( 'Sailboat_ID', $s_columns, true ) ) {
				$where_parts[] = 's.Sailboat_ID = %d';
				$params[]      = $sailboat_id;
			}

			if ( ! empty( $where_parts ) ) {
				$sql = "SELECT {$email_expr} AS email, {$user_id_expr} AS user_id
					FROM ApplicationShipTicket a
					LEFT JOIN vSailboat s ON s.AppShipTicket_ID = a.AppShipTicket_ID
					WHERE " . implode( ' AND ', $where_parts ) . ' LIMIT 1';

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
				$row = $wpdb->get_row( $wpdb->prepare( $sql, ...$params ), ARRAY_A );
				if ( is_array( $row ) ) {
					$email = sanitize_email( (string) ( $row['email'] ?? '' ) );
					if ( '' !== $email && is_email( $email ) ) {
						return $email;
					}

					$user_id = absint( $row['user_id'] ?? 0 );
					if ( $user_id > 0 ) {
						$user = get_userdata( $user_id );
						if ( $user instanceof \WP_User ) {
							$user_email = sanitize_email( (string) $user->user_email );
							if ( '' !== $user_email && is_email( $user_email ) ) {
								return $user_email;
							}
						}
					}
				}
			}
		}

		return '';
	}

	/**
	 * Перевіряє залежність від Merilka і блокує delete за її наявності.
	 *
	 * @return array<string,mixed>
	 */
	private function check_merilka_dependency( int $appshipticket_id, int $sailboat_id ): array {
		$summary = $this->get_merilka_dependency_summary( $appshipticket_id, $sailboat_id );

		if ( ! empty( $summary['merilka_check_error'] ) ) {
			return [
				'can_delete' => false,
				'message'    => (string) ( $summary['merilka_dependency_message'] ?? __( 'Видалення заблоковано: не вдалося безпечно перевірити залежності Merilka.', 'fstu' ) ),
				'status'     => 'dependency',
			];
		}

		if ( ! empty( $summary['merilka_dependency_exists'] ) ) {
			return [
				'can_delete' => false,
				'message'    => (string) ( $summary['merilka_dependency_message'] ?? __( 'Видалення заблоковано: існують пов’язані записи Merilka.', 'fstu' ) ),
				'status'     => 'dependency',
				'count'      => (int) ( $summary['merilka_dependency_count'] ?? 0 ),
				'matched_by' => (string) ( $summary['merilka_dependency_matched_by'] ?? '' ),
			];
		}

		return [ 'can_delete' => true ];
	}

	/**
	 * Повертає summary по відкладеній залежності Merilka.
	 *
	 * @return array<string,mixed>
	 */
	private function get_merilka_dependency_summary( int $appshipticket_id, int $sailboat_id ): array {
		$merilka_columns = $this->get_table_columns( 'Merilka' );
		if ( empty( $merilka_columns ) ) {
			return [
				'merilka_dependency_exists'      => false,
				'merilka_dependency_count'       => 0,
				'merilka_dependency_matched_by'  => '',
				'merilka_dependency_status'      => 'not_configured',
				'merilka_dependency_message'     => __( 'Таблицю Merilka не виявлено. Повна інтеграція перенесена в післязапускову чергу.', 'fstu' ),
				'merilka_check_error'            => false,
			];
		}

		global $wpdb;

		$where_parts = [];
		$params      = [];
		$matched_by  = [];

		if ( $appshipticket_id > 0 && in_array( 'AppShipTicket_ID', $merilka_columns, true ) ) {
			$where_parts[] = 'AppShipTicket_ID = %d';
			$params[]      = $appshipticket_id;
			$matched_by[]  = 'AppShipTicket_ID';
		}

		if ( $sailboat_id > 0 && in_array( 'Sailboat_ID', $merilka_columns, true ) ) {
			$where_parts[] = 'Sailboat_ID = %d';
			$params[]      = $sailboat_id;
			$matched_by[]  = 'Sailboat_ID';
		}

		if ( empty( $where_parts ) ) {
			return [
				'merilka_dependency_exists'      => false,
				'merilka_dependency_count'       => 0,
				'merilka_dependency_matched_by'  => '',
				'merilka_dependency_status'      => 'unknown',
				'merilka_dependency_message'     => __( 'Залежність Merilka існує, але схема таблиці не дозволяє безпечно визначити зв’язок із записом.', 'fstu' ),
				'merilka_check_error'            => true,
			];
		}

		$sql = 'SELECT COUNT(*) FROM Merilka WHERE ' . implode( ' OR ', $where_parts );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$count = (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );

		if ( $count > 0 ) {
			return [
				'merilka_dependency_exists'      => true,
				'merilka_dependency_count'       => $count,
				'merilka_dependency_matched_by'  => implode( ', ', $matched_by ),
				'merilka_dependency_status'      => 'blocked',
				'merilka_dependency_message'     => sprintf( __( 'Видалення заблоковано: знайдено %1$d пов’язаних запис(и) Merilka. Ключі зв’язку: %2$s.', 'fstu' ), $count, implode( ', ', $matched_by ) ),
				'merilka_check_error'            => false,
			];
		}

		return [
			'merilka_dependency_exists'      => false,
			'merilka_dependency_count'       => 0,
			'merilka_dependency_matched_by'  => implode( ', ', $matched_by ),
			'merilka_dependency_status'      => 'clear',
			'merilka_dependency_message'     => __( 'Пов’язані записи Merilka для цього судна не знайдені.', 'fstu' ),
			'merilka_check_error'            => false,
		];
	}

	/**
	 * Повертає список пов’язаних записів таблиці Application, які можна безпечно видалити allowlist-каскадом.
	 *
	 * @return array<int,int>
	 */
	private function get_related_application_ids( int $appshipticket_id, int $sailboat_id ): array {
		$application_columns = $this->get_table_columns( 'Application' );
		if ( empty( $application_columns ) || ! in_array( 'Application_ID', $application_columns, true ) ) {
			return [];
		}

		global $wpdb;

		$where_parts = [];
		$params      = [];

		if ( $appshipticket_id > 0 && in_array( 'AppShipTicket_ID', $application_columns, true ) ) {
			$where_parts[] = 'AppShipTicket_ID = %d';
			$params[]      = $appshipticket_id;
		}

		if ( $sailboat_id > 0 && in_array( 'Sailboat_ID', $application_columns, true ) ) {
			$where_parts[] = 'Sailboat_ID = %d';
			$params[]      = $sailboat_id;
		}

		if ( empty( $where_parts ) ) {
			return [];
		}

		$sql = 'SELECT Application_ID FROM Application WHERE ' . implode( ' OR ', $where_parts );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$ids = $wpdb->get_col( $wpdb->prepare( $sql, ...$params ) );

		return is_array( $ids ) ? array_map( 'absint', $ids ) : [];
	}

	/**
	 * Рахує інші заявки / квитки для цього ж судна.
	 */
	private function count_other_application_ship_tickets_for_sailboat( int $appshipticket_id, int $sailboat_id ): int {
		if ( $sailboat_id <= 0 ) {
			return 0;
		}

		$columns = $this->get_table_columns( 'ApplicationShipTicket' );
		if ( ! in_array( 'Sailboat_ID', $columns, true ) || ! in_array( 'AppShipTicket_ID', $columns, true ) ) {
			return 0;
		}

		global $wpdb;

		$sql = 'SELECT COUNT(*) FROM ApplicationShipTicket WHERE Sailboat_ID = %d AND AppShipTicket_ID <> %d';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $sailboat_id, $appshipticket_id ) );
	}
	/**
	 * Повертає список мерилок для конкретного судна.
	 *
	 * @param int $sailboat_id ID судна.
	 * @return array<int,array<string,mixed>>
	 */
	public function get_list_by_sailboat( int $sailboat_id ): array {
		if ( $sailboat_id <= 0 ) {
			return [];
		}

		global $wpdb;

		$sql = 'SELECT * FROM Merilka WHERE Sailboat_ID = %d ORDER BY MR_DateObmera DESC, MR_ID DESC';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $sailboat_id ), ARRAY_A );

		if ( ! is_array( $rows ) || empty( $rows ) ) {
			return [];
		}

		$items = [];
		$is_first = true;

		foreach ( $rows as $row ) {
			$row['is_latest'] = $is_first; // Перша мерилка завжди актуальна
			$row['is_used']   = $this->is_merilka_used( (int) $row['MR_ID'] );
			$items[]          = $row;
			$is_first         = false;
		}

		return $items;
	}
}
