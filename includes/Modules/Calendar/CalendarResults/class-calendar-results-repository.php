<?php
namespace FSTU\Modules\Calendar\CalendarResults;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repository підмодуля Calendar_Results.
 * Працює тільки з базовими таблицями перегонів, протоколів та результатів.
 *
 * Version: 1.2.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarResults
 */
class Calendar_Results_Repository {

	/**
	 * @var array<string, array<int, string>>
	 */
	private array $table_columns_cache = [];

	/**
	 * @var array<string, bool>
	 */
	private array $table_exists_cache = [];

	/**
	 * Повертає довідники форми перегонів.
	 *
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public function get_support_datasets(): array {
		global $wpdb;

		$datasets = [
			'race_types' => [],
		];

		if ( $this->table_exists( 'S_RaceType' ) ) {
			$datasets['race_types'] = $wpdb->get_results( 'SELECT RaceType_ID, RaceType_Name FROM S_RaceType ORDER BY RaceType_Name ASC', ARRAY_A ) ?: [];
		}

		return $datasets;
	}

	/**
	 * Повертає список перегонів заходу.
	 *
	 * @return array{items: array<int, array<string, mixed>>, total: int}
	 */
	public function get_races( int $calendar_id, string $search, int $per_page, int $offset ): array {
		global $wpdb;

		if ( ! $this->table_exists( 'Race' ) ) {
			return [ 'items' => [], 'total' => 0 ];
		}

		$race_id_column = $this->get_race_primary_key_column();
		if ( '' === $race_id_column ) {
			return [ 'items' => [], 'total' => 0 ];
		}

		$columns        = $this->get_table_columns( 'Race' );
		$base_sql       = $this->get_races_base_sql();
		$where_clauses  = [ 'races.Calendar_ID = %d' ];
		$params         = [ $calendar_id ];

		if ( '' !== $search ) {
			$like          = '%' . $wpdb->esc_like( $search ) . '%';
			$search_parts  = [];

			foreach ( [ 'Race_Name', 'race_type_name' ] as $column_name ) {
				$search_parts[] = $column_name . ' LIKE %s';
				$params[]       = $like;
			}

			if ( in_array( 'Race_Number', $columns, true ) ) {
				$search_parts[] = 'CAST(Race_Number AS CHAR) LIKE %s';
				$params[]       = $like;
			}

			$where_clauses[] = '(' . implode( ' OR ', $search_parts ) . ')';
		}

		$where_sql = implode( ' AND ', $where_clauses );
		$count_sql = "SELECT COUNT(*) FROM ({$base_sql}) races WHERE {$where_sql}";
		$data_sql  = "SELECT * FROM ({$base_sql}) races WHERE {$where_sql} ORDER BY race_date ASC, race_number ASC, race_id ASC LIMIT %d OFFSET %d";

		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );
		$items = $wpdb->get_results( $wpdb->prepare( $data_sql, ...array_merge( $params, [ $per_page, $offset ] ) ), ARRAY_A );

		return [
			'items' => is_array( $items ) ? $items : [],
			'total' => $total,
		];
	}

	/**
	 * Повертає один перегін.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_race( int $race_id ): ?array {
		global $wpdb;

		if ( ! $this->table_exists( 'Race' ) ) {
			return null;
		}

		$race_id_column = $this->get_race_primary_key_column();
		if ( '' === $race_id_column ) {
			return null;
		}

		$sql  = $this->get_races_base_sql() . ' WHERE r.' . $race_id_column . ' = %d LIMIT 1';
		$item = $wpdb->get_row( $wpdb->prepare( $sql, $race_id ), ARRAY_A );

		return is_array( $item ) ? $item : null;
	}

	/**
	 * Повертає картку перегону з вкладеними даними.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_race_payload( int $race_id ): ?array {
		$race = $this->get_race( $race_id );
		if ( ! is_array( $race ) ) {
			return null;
		}

		$race['protocol_items'] = $this->get_race_protocol_items( $race_id );
		$race['result_items']   = $this->get_race_result_items( $race_id );

		return $race;
	}

	/**
	 * Повертає рядки фінішного протоколу.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_race_protocol_items( int $race_id ): array {
		global $wpdb;

		if ( ! $this->table_exists( 'RaceProtokol' ) ) {
			return [];
		}

		$columns             = $this->get_table_columns( 'RaceProtokol' );
		$protocol_id_column  = $this->get_protocol_primary_key_column();
		$select              = [ 'rp.' . $protocol_id_column . ' AS protocol_id' ];
		$joins               = [];
		$sailboat_joined     = false;

		$select[] = in_array( 'Race_ID', $columns, true ) ? 'rp.Race_ID AS race_id' : '0 AS race_id';
		$select[] = in_array( 'RaceProtokol_Place', $columns, true ) ? 'rp.RaceProtokol_Place AS protocol_place' : '0 AS protocol_place';
		$select[] = in_array( 'RaceProtokol_Start', $columns, true ) ? 'rp.RaceProtokol_Start AS protocol_start' : "'' AS protocol_start";
		$select[] = in_array( 'RaceProtokol_Finish', $columns, true ) ? 'rp.RaceProtokol_Finish AS protocol_finish' : "'' AS protocol_finish";
		$select[] = in_array( 'RaceProtokol_Result', $columns, true ) ? 'rp.RaceProtokol_Result AS protocol_result' : "'' AS protocol_result";
		$select[] = in_array( 'RaceProtokol_Note', $columns, true ) ? 'rp.RaceProtokol_Note AS protocol_note' : "'' AS protocol_note";

		if ( in_array( 'Sailboat_ID', $columns, true ) ) {
			$select[] = 'rp.Sailboat_ID AS sailboat_id';
			if ( $this->table_exists( 'Sailboat' ) ) {
				$joins[]           = 'LEFT JOIN Sailboat sb ON sb.Sailboat_ID = rp.Sailboat_ID';
				$select[]          = "COALESCE(sb.Sailboat_Name, '') AS sailboat_name";
				$select[]          = "COALESCE(sb.Sailboat_NumberSail, '') AS sail_number";
				$sailboat_joined   = true;
			}
		} else {
			$select[] = '0 AS sailboat_id';
		}

		if ( ! $sailboat_joined ) {
			$select[] = "'' AS sailboat_name";
			$select[] = "'' AS sail_number";
		}

		$sql = 'SELECT ' . implode( ', ', $select ) . ' FROM RaceProtokol rp ' . implode( ' ', $joins ) . ' WHERE rp.Race_ID = %d ORDER BY protocol_place ASC, protocol_id ASC';
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $race_id ), ARRAY_A );

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * Повертає результати перегону.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_race_result_items( int $race_id ): array {
		global $wpdb;

		if ( ! $this->table_exists( 'RaceResult' ) ) {
			return [];
		}

		$columns            = $this->get_table_columns( 'RaceResult' );
		$result_id_column   = $this->get_result_primary_key_column();
		$select             = [ 'rr.' . $result_id_column . ' AS result_id' ];
		$joins              = [];
		$sailboat_joined    = false;

		$select[] = in_array( 'Race_ID', $columns, true ) ? 'rr.Race_ID AS race_id' : '0 AS race_id';
		$select[] = in_array( 'RaceResult_Place', $columns, true ) ? 'rr.RaceResult_Place AS result_place' : '0 AS result_place';
		$select[] = in_array( 'RaceResult_Result', $columns, true ) ? 'rr.RaceResult_Result AS result_value' : "'' AS result_value";
		$select[] = in_array( 'RaceResult_Point', $columns, true ) ? 'rr.RaceResult_Point AS result_points' : '0 AS result_points';
		$select[] = in_array( 'RaceTypeResult_ID', $columns, true ) ? 'rr.RaceTypeResult_ID AS race_type_result_id' : '0 AS race_type_result_id';

		if ( in_array( 'Sailboat_ID', $columns, true ) ) {
			$select[] = 'rr.Sailboat_ID AS sailboat_id';
			if ( $this->table_exists( 'Sailboat' ) ) {
				$joins[]         = 'LEFT JOIN Sailboat sb ON sb.Sailboat_ID = rr.Sailboat_ID';
				$select[]        = "COALESCE(sb.Sailboat_Name, '') AS sailboat_name";
				$select[]        = "COALESCE(sb.Sailboat_NumberSail, '') AS sail_number";
				$sailboat_joined = true;
			}
		} else {
			$select[] = '0 AS sailboat_id';
		}

		if ( ! $sailboat_joined ) {
			$select[] = "'' AS sailboat_name";
			$select[] = "'' AS sail_number";
		}

		$sql = 'SELECT ' . implode( ', ', $select ) . ' FROM RaceResult rr ' . implode( ' ', $joins ) . ' WHERE rr.Race_ID = %d ORDER BY result_place ASC, result_id ASC';
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $race_id ), ARRAY_A );

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * Повертає рядки RaceTypeResult для перегону/типу перегону.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_race_type_result_items( int $race_id, int $race_type_id = 0 ): array {
		global $wpdb;

		if ( ! $this->table_exists( 'RaceTypeResult' ) ) {
			return [];
		}

		$columns       = $this->get_table_columns( 'RaceTypeResult' );
		$select        = [];
		$where_clauses = [];
		$params        = [];
		$joins         = [];

		foreach ( [
			'RaceTypeResult_ID',
			'Race_ID',
			'RaceType_ID',
			'SailGroup_ID',
			'SailGroupType_ID',
			'MR_ID',
			'RaceTypeResult_Name',
			'RaceTypeResult_Koeff',
			'RaceTypeResult_Distance',
			'RaceTypeResult_Point',
		] as $column_name ) {
			if ( in_array( $column_name, $columns, true ) ) {
				$select[] = 'rtr.' . $column_name . ' AS ' . $column_name;
			}
		}

		if ( in_array( 'SailGroup_ID', $columns, true ) && $this->table_exists( 'S_SailGroup' ) ) {
			$joins[]  = 'LEFT JOIN S_SailGroup sg ON sg.SailGroup_ID = rtr.SailGroup_ID';
			$select[] = "COALESCE(sg.SailGroup_Name, '') AS SailGroup_Name";
		}

		if ( in_array( 'SailGroupType_ID', $columns, true ) && $this->table_exists( 'S_SailGroupType' ) ) {
			$joins[]  = 'LEFT JOIN S_SailGroupType sgt ON sgt.SailGroupType_ID = rtr.SailGroupType_ID';
			$select[] = "COALESCE(sgt.SailGroupType_Name, '') AS SailGroupType_Name";
		}

		if ( in_array( 'MR_ID', $columns, true ) && $this->table_exists( 'Merilka' ) ) {
			$merilka_columns = $this->get_table_columns( 'Merilka' );
			$joins[]  = 'LEFT JOIN Merilka mr ON mr.MR_ID = rtr.MR_ID';
			$select[] = in_array( 'MR_GB', $merilka_columns, true ) ? 'COALESCE(mr.MR_GB, 0) AS MR_GB' : '0 AS MR_GB';
			$select[] = in_array( 'MR_GB_Spinaker', $merilka_columns, true ) ? 'COALESCE(mr.MR_GB_Spinaker, 0) AS MR_GB_Spinaker' : '0 AS MR_GB_Spinaker';
		}

		if ( empty( $select ) ) {
			$select[] = '1 AS placeholder_value';
		}

		if ( in_array( 'Race_ID', $columns, true ) ) {
			$where_clauses[] = 'rtr.Race_ID = %d';
			$params[]        = $race_id;
		} elseif ( $race_type_id > 0 && in_array( 'RaceType_ID', $columns, true ) ) {
			$where_clauses[] = 'rtr.RaceType_ID = %d';
			$params[]        = $race_type_id;
		} else {
			return [];
		}

		$sql  = 'SELECT ' . implode( ', ', $select ) . ' FROM RaceTypeResult rtr ' . implode( ' ', $joins ) . ' WHERE ' . implode( ' AND ', $where_clauses ) . ' ORDER BY ' . ( in_array( 'RaceTypeResult_ID', $columns, true ) ? 'rtr.RaceTypeResult_ID' : '1' ) . ' ASC';
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * Повертає sailing-контекст суден перегону через Calendar/Application/Sailboat/Merilka.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_race_sailboat_context( int $race_id ): array {
		global $wpdb;

		$race = $this->get_race( $race_id );
		if ( ! is_array( $race ) ) {
			return [];
		}

		$calendar_id = isset( $race['Calendar_ID'] ) ? absint( $race['Calendar_ID'] ) : 0;
		if ( $calendar_id <= 0 || ! $this->table_exists( 'Application' ) ) {
			return [];
		}

		$application_columns = $this->get_table_columns( 'Application' );
		if ( ! in_array( 'Calendar_ID', $application_columns, true ) || ! in_array( 'Sailboat_ID', $application_columns, true ) ) {
			return [];
		}

		$select = [
			'a.Sailboat_ID AS Sailboat_ID',
			"COALESCE(sb.Sailboat_Name, '') AS Sailboat_Name",
			"COALESCE(sb.Sailboat_NumberSail, '') AS Sailboat_NumberSail",
			'COALESCE(mr.MR_GB, 0) AS MR_GB',
			'COALESCE(mr.MR_GB_Spinaker, 0) AS MR_GB_Spinaker',
			'COALESCE(mr.MR_GB_CrewWeight, 0) AS MR_GB_CrewWeight',
			'COALESCE(mr.MR_GB_CrewWeight_Spinaker, 0) AS MR_GB_CrewWeight_Spinaker',
		];
		$joins = [];

		if ( $this->table_exists( 'Sailboat' ) ) {
			$sailboat_columns = $this->get_table_columns( 'Sailboat' );

			$joins[] = 'LEFT JOIN Sailboat sb ON sb.Sailboat_ID = a.Sailboat_ID';
			$select[1] = in_array( 'Sailboat_Name', $sailboat_columns, true ) ? "COALESCE(sb.Sailboat_Name, '') AS Sailboat_Name" : "'' AS Sailboat_Name";
			$select[2] = in_array( 'Sailboat_NumberSail', $sailboat_columns, true ) ? "COALESCE(sb.Sailboat_NumberSail, '') AS Sailboat_NumberSail" : "'' AS Sailboat_NumberSail";
		} else {
			$select[1] = "'' AS Sailboat_Name";
			$select[2] = "'' AS Sailboat_NumberSail";
		}

		if ( in_array( 'SailGroup_ID', $application_columns, true ) && $this->table_exists( 'S_SailGroup' ) ) {
			$select[] = 'a.SailGroup_ID AS SailGroup_ID';
			$select[] = "COALESCE(sg.SailGroup_Name, '') AS SailGroup_Name";
			$joins[]  = 'LEFT JOIN S_SailGroup sg ON sg.SailGroup_ID = a.SailGroup_ID';
		} else {
			$select[] = '0 AS SailGroup_ID';
			$select[] = "'' AS SailGroup_Name";
		}

		if ( $this->table_exists( 'Merilka' ) ) {
			$merilka_columns = $this->get_table_columns( 'Merilka' );
			$can_join_merilka = in_array( 'MR_ID', $merilka_columns, true ) && in_array( 'Sailboat_ID', $merilka_columns, true );

			if ( $can_join_merilka ) {
				$joins[] = 'LEFT JOIN Merilka mr ON mr.MR_ID = (SELECT mr2.MR_ID FROM Merilka mr2 WHERE mr2.Sailboat_ID = a.Sailboat_ID ORDER BY mr2.MR_ID DESC LIMIT 1)';
			}

			$select[3] = ( $can_join_merilka && in_array( 'MR_GB', $merilka_columns, true ) ) ? 'COALESCE(mr.MR_GB, 0) AS MR_GB' : '0 AS MR_GB';
			$select[4] = ( $can_join_merilka && in_array( 'MR_GB_Spinaker', $merilka_columns, true ) ) ? 'COALESCE(mr.MR_GB_Spinaker, 0) AS MR_GB_Spinaker' : '0 AS MR_GB_Spinaker';
			$select[5] = ( $can_join_merilka && in_array( 'MR_GB_CrewWeight', $merilka_columns, true ) ) ? 'COALESCE(mr.MR_GB_CrewWeight, 0) AS MR_GB_CrewWeight' : '0 AS MR_GB_CrewWeight';
			$select[6] = ( $can_join_merilka && in_array( 'MR_GB_CrewWeight_Spinaker', $merilka_columns, true ) ) ? 'COALESCE(mr.MR_GB_CrewWeight_Spinaker, 0) AS MR_GB_CrewWeight_Spinaker' : '0 AS MR_GB_CrewWeight_Spinaker';
		} else {
			$select[3] = '0 AS MR_GB';
			$select[4] = '0 AS MR_GB_Spinaker';
			$select[5] = '0 AS MR_GB_CrewWeight';
			$select[6] = '0 AS MR_GB_CrewWeight_Spinaker';
		}

		$sql = 'SELECT ' . implode( ', ', $select ) . ' FROM Application a ' . implode( ' ', $joins ) . ' WHERE a.Calendar_ID = %d AND a.Sailboat_ID > 0 ORDER BY Sailboat_Name ASC, a.Sailboat_ID ASC';
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $calendar_id ), ARRAY_A );

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * Повертає ID власника перегону через батьківський захід.
	 */
	public function get_race_owner_id( int $race_id ): int {
		global $wpdb;

		$race_id_column = $this->get_race_primary_key_column();
		if ( '' === $race_id_column ) {
			return 0;
		}

		$sql = "
			SELECT COALESCE(NULLIF(c.User_ID, 0), NULLIF(c.UserCreate, 0), 0)
			FROM Race r
			INNER JOIN Calendar c ON c.Calendar_ID = r.Calendar_ID
			WHERE r.{$race_id_column} = %d
			LIMIT 1
		";

		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $race_id ) );
	}

	/**
	 * Повертає залежності для delete-flow.
	 *
	 * @return array<string, int>
	 */
	public function get_race_dependency_counts( int $race_id ): array {
		global $wpdb;

		$dependencies = [
			'RaceProtokol'   => 0,
			'RaceResult'     => 0,
			'RaceTypeResult' => 0,
		];

		foreach ( array_keys( $dependencies ) as $table_name ) {
			if ( ! $this->table_exists( $table_name ) || ! in_array( 'Race_ID', $this->get_table_columns( $table_name ), true ) ) {
				continue;
			}

			$dependencies[ $table_name ] = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table_name} WHERE Race_ID = %d",
					$race_id
				)
			);
		}

		return $dependencies;
	}

	/**
	 * Створює перегін.
	 */
	public function insert_race( array $data ): int {
		global $wpdb;

		$insert_data = $this->build_race_write_data( $data, true );
		if ( empty( $insert_data ) ) {
			return 0;
		}

		$result = $wpdb->insert( 'Race', $insert_data, $this->build_formats( $insert_data ) );

		return false === $result ? 0 : (int) $wpdb->insert_id;
	}

	/**
	 * Оновлює перегін.
	 */
	public function update_race( int $race_id, array $data ): bool {
		global $wpdb;

		$race_id_column = $this->get_race_primary_key_column();
		$update_data    = $this->build_race_write_data( $data, false );
		if ( '' === $race_id_column || empty( $update_data ) ) {
			return false;
		}

		$result = $wpdb->update(
			'Race',
			$update_data,
			[ $race_id_column => $race_id ],
			$this->build_formats( $update_data ),
			[ '%d' ]
		);

		return false !== $result;
	}

	/**
	 * Видаляє перегін.
	 */
	public function delete_race( int $race_id ): bool {
		global $wpdb;

		$race_id_column = $this->get_race_primary_key_column();
		if ( '' === $race_id_column ) {
			return false;
		}

		$result = $wpdb->delete( 'Race', [ $race_id_column => $race_id ], [ '%d' ] );

		return false !== $result;
	}

	/**
	 * Оновлює рядки фінішного протоколу перегону.
	 *
	 * @param array<int, array<string, mixed>> $items
	 * @return array{success: bool, updated: int}
	 */
	public function update_race_protocol_items( int $race_id, array $items ): array {
		global $wpdb;

		if ( ! $this->table_exists( 'RaceProtokol' ) ) {
			return [ 'success' => false, 'updated' => 0 ];
		}

		$protocol_id_column = $this->get_protocol_primary_key_column();
		$columns            = $this->get_table_columns( 'RaceProtokol' );
		if ( '' === $protocol_id_column || ! in_array( 'Race_ID', $columns, true ) ) {
			return [ 'success' => false, 'updated' => 0 ];
		}

		$updated_count = 0;

		foreach ( $items as $item ) {
			$protocol_id = isset( $item['protocol_id'] ) ? absint( $item['protocol_id'] ) : 0;
			if ( $protocol_id <= 0 ) {
				continue;
			}

			$update_data = [];
			if ( in_array( 'RaceProtokol_Place', $columns, true ) ) {
				$update_data['RaceProtokol_Place'] = isset( $item['protocol_place'] ) ? absint( $item['protocol_place'] ) : 0;
			}
			if ( in_array( 'RaceProtokol_Start', $columns, true ) ) {
				$update_data['RaceProtokol_Start'] = isset( $item['protocol_start'] ) ? (string) $item['protocol_start'] : '';
			}
			if ( in_array( 'RaceProtokol_Finish', $columns, true ) ) {
				$update_data['RaceProtokol_Finish'] = isset( $item['protocol_finish'] ) ? (string) $item['protocol_finish'] : '';
			}
			if ( in_array( 'RaceProtokol_Result', $columns, true ) ) {
				$update_data['RaceProtokol_Result'] = isset( $item['protocol_result'] ) ? (string) $item['protocol_result'] : '';
			}
			if ( in_array( 'RaceProtokol_Note', $columns, true ) ) {
				$update_data['RaceProtokol_Note'] = isset( $item['protocol_note'] ) ? (string) $item['protocol_note'] : '';
			}

			if ( empty( $update_data ) ) {
				continue;
			}

			$result = $wpdb->update(
				'RaceProtokol',
				$update_data,
				[
					$protocol_id_column => $protocol_id,
					'Race_ID'           => $race_id,
				],
				$this->build_formats( $update_data ),
				[ '%d', '%d' ]
			);

			if ( false === $result ) {
				return [ 'success' => false, 'updated' => $updated_count ];
			}

			$updated_count++;
		}

		return [ 'success' => true, 'updated' => $updated_count ];
	}

	/**
	 * Виконує базовий перерахунок результатів за фінішним протоколом.
	 *
	 * @return array{updated: int, created: int}
	 */
	public function recalculate_results_from_protocol( int $race_id ): array {
		global $wpdb;

		if ( ! $this->table_exists( 'RaceResult' ) ) {
			return [ 'updated' => 0, 'created' => 0 ];
		}

		$protocol_items    = $this->get_race_protocol_items( $race_id );
		$result_columns    = $this->get_table_columns( 'RaceResult' );
		$result_id_column  = $this->get_result_primary_key_column();
		$created           = 0;
		$updated           = 0;

		if ( empty( $protocol_items ) || '' === $result_id_column || ! in_array( 'Race_ID', $result_columns, true ) ) {
			return [ 'updated' => 0, 'created' => 0 ];
		}

		foreach ( array_values( $protocol_items ) as $index => $protocol_item ) {
			$place        = $index + 1;
			$protocol_row = [
				'Race_ID' => $race_id,
			];

			if ( in_array( 'RaceResult_Place', $result_columns, true ) ) {
				$protocol_row['RaceResult_Place'] = $place;
			}
			if ( in_array( 'RaceResult_Result', $result_columns, true ) ) {
				$protocol_row['RaceResult_Result'] = (string) ( $protocol_item['protocol_result'] ?? $place );
			}
			if ( in_array( 'RaceResult_Point', $result_columns, true ) ) {
				$protocol_row['RaceResult_Point'] = $place;
			}
			if ( in_array( 'Sailboat_ID', $result_columns, true ) && ! empty( $protocol_item['sailboat_id'] ) ) {
				$protocol_row['Sailboat_ID'] = (int) $protocol_item['sailboat_id'];
			}

			$existing_id = 0;
			if ( in_array( 'Sailboat_ID', $result_columns, true ) && ! empty( $protocol_item['sailboat_id'] ) ) {
				$existing_id = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT {$result_id_column} FROM RaceResult WHERE Race_ID = %d AND Sailboat_ID = %d LIMIT 1",
						$race_id,
						(int) $protocol_item['sailboat_id']
					)
				);
			}

			if ( $existing_id > 0 ) {
				$result = $wpdb->update(
					'RaceResult',
					$protocol_row,
					[ $result_id_column => $existing_id ],
					$this->build_formats( $protocol_row ),
					[ '%d' ]
				);

				if ( false !== $result ) {
					$updated++;
				}
				continue;
			}

			if ( in_array( 'RaceResult_DateCreate', $result_columns, true ) ) {
				$protocol_row['RaceResult_DateCreate'] = current_time( 'mysql' );
			}
			if ( in_array( 'UserCreate', $result_columns, true ) ) {
				$protocol_row['UserCreate'] = get_current_user_id();
			}

			$inserted = $wpdb->insert( 'RaceResult', $protocol_row, $this->build_formats( $protocol_row ) );
			if ( false !== $inserted ) {
				$created++;
			}
		}

		return [ 'updated' => $updated, 'created' => $created ];
	}

	/**
	 * Додає запис у Logs.
	 */
	public function insert_log( string $log_name, string $type, string $text, string $status ): bool {
		global $wpdb;

		$result = $wpdb->insert(
			'Logs',
			[
				'User_ID'         => get_current_user_id(),
				'Logs_DateCreate' => current_time( 'mysql' ),
				'Logs_Type'       => $type,
				'Logs_Name'       => $log_name,
				'Logs_Text'       => $text,
				'Logs_Error'      => $status,
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s' ]
		);

		return false !== $result;
	}

	/**
	 * Повертає дані протоколу підмодуля.
	 *
	 * @return array{items: array<int, array<string, mixed>>, total: int}
	 */
	public function get_protocol( string $log_name, string $search, int $per_page, int $offset ): array {
		global $wpdb;

		$users_table    = $wpdb->users;
		$usermeta_table = $wpdb->usermeta;
		$fio_sql        = "COALESCE(NULLIF(TRIM(CONCAT_WS(' ', NULLIF(um_last.meta_value, ''), NULLIF(um_first.meta_value, ''), NULLIF(um_patronymic.meta_value, ''))), ''), u.display_name, u.user_login)";
		$where_sql      = 'l.Logs_Name = %s';
		$params         = [ $log_name ];

		if ( '' !== $search ) {
			$like       = '%' . $wpdb->esc_like( $search ) . '%';
			$where_sql .= " AND (l.Logs_Text LIKE %s OR {$fio_sql} LIKE %s)";
			$params[]   = $like;
			$params[]   = $like;
		}

		$count_sql = "SELECT COUNT(*) FROM Logs l LEFT JOIN {$users_table} u ON u.ID = l.User_ID LEFT JOIN {$usermeta_table} um_first ON um_first.user_id = u.ID AND um_first.meta_key = 'first_name' LEFT JOIN {$usermeta_table} um_last ON um_last.user_id = u.ID AND um_last.meta_key = 'last_name' LEFT JOIN {$usermeta_table} um_patronymic ON um_patronymic.user_id = u.ID AND um_patronymic.meta_key = 'Patronymic' WHERE {$where_sql}";
		$data_sql  = "SELECT l.Logs_DateCreate, l.Logs_Type, l.Logs_Name, l.Logs_Text, l.Logs_Error, {$fio_sql} AS FIO FROM Logs l LEFT JOIN {$users_table} u ON u.ID = l.User_ID LEFT JOIN {$usermeta_table} um_first ON um_first.user_id = u.ID AND um_first.meta_key = 'first_name' LEFT JOIN {$usermeta_table} um_last ON um_last.user_id = u.ID AND um_last.meta_key = 'last_name' LEFT JOIN {$usermeta_table} um_patronymic ON um_patronymic.user_id = u.ID AND um_patronymic.meta_key = 'Patronymic' WHERE {$where_sql} ORDER BY l.Logs_DateCreate DESC LIMIT %d OFFSET %d";

		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );
		$rows  = $wpdb->get_results( $wpdb->prepare( $data_sql, ...array_merge( $params, [ $per_page, $offset ] ) ), ARRAY_A );

		return [
			'items' => is_array( $rows ) ? $rows : [],
			'total' => $total,
		];
	}

	/**
	 * Починає транзакцію.
	 */
	public function begin_transaction(): void {
		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );
	}

	/**
	 * Комітить транзакцію.
	 */
	public function commit(): void {
		global $wpdb;
		$wpdb->query( 'COMMIT' );
	}

	/**
	 * Відкочує транзакцію.
	 */
	public function rollback(): void {
		global $wpdb;
		$wpdb->query( 'ROLLBACK' );
	}

	/**
	 * Будує базовий SELECT для списку перегонів.
	 */
	private function get_races_base_sql(): string {
		$columns           = $this->get_table_columns( 'Race' );
		$race_id_column    = $this->get_race_primary_key_column();
		$select            = [ 'r.' . $race_id_column . ' AS race_id' ];
		$joins             = [];
		$has_type_join     = false;
		$protocol_count_sql = $this->table_exists( 'RaceProtokol' ) ? '(SELECT COUNT(*) FROM RaceProtokol rp WHERE rp.Race_ID = r.' . $race_id_column . ')' : '0';
		$result_count_sql   = $this->table_exists( 'RaceResult' ) ? '(SELECT COUNT(*) FROM RaceResult rr WHERE rr.Race_ID = r.' . $race_id_column . ')' : '0';

		$select[] = in_array( 'Calendar_ID', $columns, true ) ? 'r.Calendar_ID AS Calendar_ID' : '0 AS Calendar_ID';
		$select[] = in_array( 'Race_Date', $columns, true ) ? 'r.Race_Date AS race_date' : ( in_array( 'Race_DateCreate', $columns, true ) ? 'r.Race_DateCreate AS race_date' : "'' AS race_date" );
		$select[] = in_array( 'Race_Number', $columns, true ) ? 'r.Race_Number AS race_number' : '0 AS race_number';
		$select[] = in_array( 'Race_Name', $columns, true ) ? 'r.Race_Name AS Race_Name' : "CONCAT('Перегін #', COALESCE(r.Race_Number, r.{$race_id_column})) AS Race_Name";
		$select[] = in_array( 'Race_Description', $columns, true ) ? 'r.Race_Description AS Race_Description' : ( in_array( 'Race_Note', $columns, true ) ? 'r.Race_Note AS Race_Description' : "'' AS Race_Description" );
		$select[] = in_array( 'RaceType_ID', $columns, true ) ? 'r.RaceType_ID AS RaceType_ID' : '0 AS RaceType_ID';
		$select[] = in_array( 'UserCreate', $columns, true ) ? 'r.UserCreate AS UserCreate' : '0 AS UserCreate';
		$select[] = $protocol_count_sql . ' AS Protocol_Count';
		$select[] = $result_count_sql . ' AS Result_Count';

		if ( in_array( 'RaceType_ID', $columns, true ) && $this->table_exists( 'S_RaceType' ) ) {
			$joins[]         = 'LEFT JOIN S_RaceType rt ON rt.RaceType_ID = r.RaceType_ID';
			$select[]        = "COALESCE(rt.RaceType_Name, '') AS race_type_name";
			$has_type_join   = true;
		}

		if ( ! $has_type_join ) {
			$select[] = "'' AS race_type_name";
		}

		return 'SELECT ' . implode( ', ', $select ) . ' FROM Race r ' . implode( ' ', $joins );
	}

	/**
	 * Будує дані для write-операцій таблиці Race.
	 *
	 * @return array<string, mixed>
	 */
	private function build_race_write_data( array $data, bool $is_create ): array {
		$columns = $this->get_table_columns( 'Race' );
		$write   = [];

		$mapping = [
			'Calendar_ID'      => (int) ( $data['calendar_id'] ?? 0 ),
			'Race_Date'        => (string) ( $data['race_date'] ?? '' ),
			'Race_Number'      => (int) ( $data['race_number'] ?? 0 ),
			'Race_Name'        => (string) ( $data['race_name'] ?? '' ),
			'RaceType_ID'      => (int) ( $data['race_type_id'] ?? 0 ),
			'Race_Description' => (string) ( $data['race_description'] ?? '' ),
			'Race_Note'        => (string) ( $data['race_description'] ?? '' ),
		];

		foreach ( $mapping as $column_name => $value ) {
			if ( ! in_array( $column_name, $columns, true ) ) {
				continue;
			}

			if ( is_int( $value ) && $value <= 0 && 'Calendar_ID' !== $column_name && 'Race_Number' !== $column_name ) {
				$write[ $column_name ] = null;
				continue;
			}

			$write[ $column_name ] = $value;
		}

		if ( $is_create ) {
			if ( in_array( 'Race_DateCreate', $columns, true ) ) {
				$write['Race_DateCreate'] = current_time( 'mysql' );
			}
			if ( in_array( 'UserCreate', $columns, true ) ) {
				$write['UserCreate'] = get_current_user_id();
			}
		}

		return $write;
	}

	/**
	 * Повертає ім’я PK таблиці Race.
	 */
	private function get_race_primary_key_column(): string {
		$columns = $this->get_table_columns( 'Race' );

		foreach ( [ 'Race_ID', 'RaceId' ] as $column_name ) {
			if ( in_array( $column_name, $columns, true ) ) {
				return $column_name;
			}
		}

		return '';
	}

	/**
	 * Повертає ім’я PK таблиці RaceProtokol.
	 */
	private function get_protocol_primary_key_column(): string {
		$columns = $this->get_table_columns( 'RaceProtokol' );

		foreach ( [ 'RaceProtokol_ID', 'RaceProtocol_ID' ] as $column_name ) {
			if ( in_array( $column_name, $columns, true ) ) {
				return $column_name;
			}
		}

		return 'RaceProtokol_ID';
	}

	/**
	 * Повертає ім’я PK таблиці RaceResult.
	 */
	private function get_result_primary_key_column(): string {
		$columns = $this->get_table_columns( 'RaceResult' );

		foreach ( [ 'RaceResult_ID', 'Race_Result_ID' ] as $column_name ) {
			if ( in_array( $column_name, $columns, true ) ) {
				return $column_name;
			}
		}

		return 'RaceResult_ID';
	}

	/**
	 * Повертає список колонок таблиці.
	 *
	 * @return array<int, string>
	 */
	private function get_table_columns( string $table_name ): array {
		if ( isset( $this->table_columns_cache[ $table_name ] ) ) {
			return $this->table_columns_cache[ $table_name ];
		}

		if ( ! $this->table_exists( $table_name ) ) {
			$this->table_columns_cache[ $table_name ] = [];

			return [];
		}

		global $wpdb;

		$results = $wpdb->get_results( 'SHOW COLUMNS FROM `' . esc_sql( $table_name ) . '`', ARRAY_A );
		$columns = [];
		if ( is_array( $results ) ) {
			foreach ( $results as $row ) {
				if ( isset( $row['Field'] ) ) {
					$columns[] = (string) $row['Field'];
				}
			}
		}

		$this->table_columns_cache[ $table_name ] = $columns;

		return $columns;
	}

	/**
	 * Перевіряє існування таблиці.
	 */
	private function table_exists( string $table_name ): bool {
		if ( isset( $this->table_exists_cache[ $table_name ] ) ) {
			return $this->table_exists_cache[ $table_name ];
		}

		global $wpdb;

		$exists = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
		$this->table_exists_cache[ $table_name ] = $exists;

		return $exists;
	}

	/**
	 * Повертає формати для write-операцій.
	 *
	 * @param array<string, mixed> $data
	 * @return array<int, string>
	 */
	private function build_formats( array $data ): array {
		$formats = [];
		foreach ( $data as $key => $value ) {
			if ( null === $value ) {
				$formats[] = '%s';
				continue;
			}

			$formats[] = is_int( $value ) ? '%d' : '%s';
		}

		return $formats;
	}
}

