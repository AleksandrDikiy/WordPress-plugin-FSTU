<?php
namespace FSTU\Modules\Calendar\CalendarEvents;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repository підмодуля Calendar_Events.
 * Працює тільки з базовими таблицями та прямими SQL-запитами.
 *
 * Version: 1.1.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarEvents
 */
class Calendar_Events_Repository {

	private const FILTER_DATASET_CACHE_GROUP = 'fstu_calendar';
	private const FILTER_DATASET_CACHE_KEY   = 'calendar_events_filter_datasets_v1';
	private const FILTER_DATASET_CACHE_TTL   = HOUR_IN_SECONDS;

	/**
	 * @var array<string, array<int, string>>
	 */
	private array $table_columns_cache = [];

	/**
	 * @var array<string, bool>
	 */
	private array $table_exists_cache = [];

	/**
	 * Повертає довідники для фільтрів і форми.
	 *
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public function get_filter_datasets(): array {
		$cached = wp_cache_get( self::FILTER_DATASET_CACHE_KEY, self::FILTER_DATASET_CACHE_GROUP );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;

		$datasets = [
			'statuses'      => $wpdb->get_results( 'SELECT CalendarStatus_ID, CalendarStatus_Name FROM S_CalendarStatus ORDER BY CalendarStatus_Name ASC', ARRAY_A ) ?: [],
			'regions'       => $wpdb->get_results( 'SELECT Region_ID, Region_Name FROM S_Region ORDER BY Region_Name ASC', ARRAY_A ) ?: [],
			'cities'        => $wpdb->get_results( 'SELECT City_ID, City_Name, Region_ID FROM S_City ORDER BY City_Name ASC', ARRAY_A ) ?: [],
			'tourism_types' => $wpdb->get_results( 'SELECT TourismType_ID, TourismType_Name FROM S_TourismType ORDER BY TourismType_Name ASC', ARRAY_A ) ?: [],
			'event_types'   => $wpdb->get_results( 'SELECT EventType_ID, EventType_Name FROM S_EventType ORDER BY EventType_Name ASC', ARRAY_A ) ?: [],
			'type_events'   => $wpdb->get_results( 'SELECT TypeEvent_ID, TypeEvent_Name FROM S_TypeEvent ORDER BY TypeEvent_Name ASC', ARRAY_A ) ?: [],
			'tour_types'    => $wpdb->get_results( 'SELECT TourType_ID, TourType_Name FROM S_TourType ORDER BY TourType_Name ASC', ARRAY_A ) ?: [],
			'years'         => $wpdb->get_results( 'SELECT DISTINCT YEAR(Calendar_DateBegin) AS year_value FROM Calendar WHERE Calendar_DateBegin IS NOT NULL ORDER BY year_value DESC', ARRAY_A ) ?: [],
		];

		wp_cache_set( self::FILTER_DATASET_CACHE_KEY, $datasets, self::FILTER_DATASET_CACHE_GROUP, self::FILTER_DATASET_CACHE_TTL );

		return $datasets;
	}

	/**
	 * Повертає список заходів.
	 *
	 * @param array<string, mixed> $filters Фільтри списку.
	 * @return array{items: array<int, array<string, mixed>>, total: int}
	 */
	public function get_events( array $filters, int $per_page, int $offset ): array {
		global $wpdb;

		[ $where_sql, $params ] = $this->build_events_where_sql( $filters );
		$base_sql               = $this->get_events_base_sql();
		$count_sql              = "SELECT COUNT(*) FROM ({$base_sql}) events WHERE {$where_sql}";
		$data_sql               = "SELECT * FROM ({$base_sql}) events WHERE {$where_sql} ORDER BY events.Calendar_DateBegin ASC, events.Calendar_Name ASC, events.Calendar_ID DESC LIMIT %d OFFSET %d";

		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );
		$items = $wpdb->get_results( $wpdb->prepare( $data_sql, ...array_merge( $params, [ $per_page, $offset ] ) ), ARRAY_A );

		return [
			'items' => is_array( $items ) ? $items : [],
			'total' => $total,
		];
	}

	/**
	 * Повертає один захід за ID.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_event( int $event_id ): ?array {
		global $wpdb;

		$sql  = $this->get_event_single_sql();
		$item = $wpdb->get_row( $wpdb->prepare( $sql, $event_id ), ARRAY_A );

		return is_array( $item ) ? $item : null;
	}

	/**
	 * Повертає події в межах періоду для month/week view.
	 *
	 * @param array<string, mixed> $filters Фільтри календаря.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_events_by_period( string $period_start, string $period_end, array $filters ): array {
		global $wpdb;

		$filters['period_start'] = $period_start;
		$filters['period_end']   = $period_end;

		[ $where_sql, $params ] = $this->build_events_where_sql( $filters, false );
		$sql                    = "SELECT * FROM ({$this->get_events_base_sql()}) events WHERE {$where_sql} ORDER BY events.Calendar_DateBegin ASC, events.Calendar_ID ASC";
		$items                  = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );

		return is_array( $items ) ? $items : [];
	}

	/**
	 * Повертає власника заходу.
	 */
	public function get_event_owner_id( int $event_id ): int {
		global $wpdb;

		$owner_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COALESCE(NULLIF(User_ID, 0), NULLIF(UserCreate, 0), 0) FROM Calendar WHERE Calendar_ID = %d LIMIT 1',
				$event_id
			)
		);

		return max( 0, $owner_id );
	}

	/**
	 * Повертає кількість підтверджених залежностей для delete-flow.
	 *
	 * @return array<string, int>
	 */
	public function get_event_dependency_counts( int $event_id ): array {
		$dependencies = [
			'Application'     => 0,
			'UserCalendar'    => 0,
			'PathTrip'        => 0,
			'AppTravel'       => 0,
			'VerificationMKK' => 0,
			'Race'            => 0,
			'RaceProtokol'    => 0,
			'RaceResult'      => 0,
			'RaceTypeResult'  => 0,
		];

		foreach ( [ 'Application', 'UserCalendar', 'PathTrip', 'AppTravel', 'VerificationMKK', 'Race' ] as $table_name ) {
			$dependencies[ $table_name ] = $this->count_rows_by_column( $table_name, 'Calendar_ID', $event_id );
		}

		foreach ( [ 'RaceProtokol', 'RaceResult', 'RaceTypeResult' ] as $table_name ) {
			$dependencies[ $table_name ] = $this->count_race_child_rows_by_event( $table_name, $event_id );
		}

		return $dependencies;
	}

	/**
	 * Вставляє запис у таблицю Calendar.
	 */
	public function insert_event( array $data ): int {
		global $wpdb;

		$result = $wpdb->insert(
			'Calendar',
			$this->build_event_write_data( $data, true ),
			$this->get_event_write_formats( true )
		);

		if ( false === $result ) {
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Оновлює запис у Calendar.
	 */
	public function update_event( int $event_id, array $data ): bool {
		global $wpdb;

		$result = $wpdb->update(
			'Calendar',
			$this->build_event_write_data( $data, false ),
			[ 'Calendar_ID' => $event_id ],
			$this->get_event_write_formats( false ),
			[ '%d' ]
		);

		return false !== $result;
	}

	/**
	 * Видаляє запис із Calendar.
	 */
	public function delete_event( int $event_id ): bool {
		global $wpdb;

		$result = $wpdb->delete( 'Calendar', [ 'Calendar_ID' => $event_id ], [ '%d' ] );

		return false !== $result;
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
	 * Повертає дані протоколу.
	 *
	 * @return array{items: array<int, array<string, mixed>>, total: int}
	 */
	public function get_protocol( string $log_name, string $search, int $per_page, int $offset ): array {
		global $wpdb;

		$users_table    = $wpdb->users;
		$usermeta_table = $wpdb->usermeta;
		$fio_sql        = "COALESCE(NULLIF(TRIM(CONCAT_WS(' ', NULLIF(um_last.meta_value, ''), NULLIF(um_first.meta_value, ''), NULLIF(um_patronymic.meta_value, ''))), ''), u.display_name, u.user_login)";
		$params         = [ $log_name ];
		$where_sql      = 'l.Logs_Name = %s';

		if ( '' !== $search ) {
			$like       = '%' . $wpdb->esc_like( $search ) . '%';
			$where_sql .= " AND (l.Logs_Text LIKE %s OR {$fio_sql} LIKE %s)";
			$params[]   = $like;
			$params[]   = $like;
		}

		$count_sql = "SELECT COUNT(*) FROM Logs l LEFT JOIN {$users_table} u ON u.ID = l.User_ID LEFT JOIN {$usermeta_table} um_first ON um_first.user_id = u.ID AND um_first.meta_key = 'first_name' LEFT JOIN {$usermeta_table} um_last ON um_last.user_id = u.ID AND um_last.meta_key = 'last_name' LEFT JOIN {$usermeta_table} um_patronymic ON um_patronymic.user_id = u.ID AND um_patronymic.meta_key = 'Patronymic' WHERE {$where_sql}";
		$total     = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );

		$data_sql = "SELECT l.Logs_DateCreate, l.Logs_Type, l.Logs_Name, l.Logs_Text, l.Logs_Error, {$fio_sql} AS FIO FROM Logs l LEFT JOIN {$users_table} u ON u.ID = l.User_ID LEFT JOIN {$usermeta_table} um_first ON um_first.user_id = u.ID AND um_first.meta_key = 'first_name' LEFT JOIN {$usermeta_table} um_last ON um_last.user_id = u.ID AND um_last.meta_key = 'last_name' LEFT JOIN {$usermeta_table} um_patronymic ON um_patronymic.user_id = u.ID AND um_patronymic.meta_key = 'Patronymic' WHERE {$where_sql} ORDER BY l.Logs_DateCreate DESC LIMIT %d OFFSET %d";
		$items    = $wpdb->get_results( $wpdb->prepare( $data_sql, ...array_merge( $params, [ $per_page, $offset ] ) ), ARRAY_A );

		return [
			'items' => is_array( $items ) ? $items : [],
			'total' => $total,
		];
	}

	/**
	 * Будує базовий SELECT для read-моделі заходів.
	 */
	private function get_events_base_sql(): string {
		global $wpdb;

		$users_table    = $wpdb->users;
		$usermeta_table = $wpdb->usermeta;

		return "
			SELECT c.Calendar_ID,
			       c.Calendar_DateCreate,
			       c.Calendar_DateBegin,
			       c.Calendar_DateEnd,
			       c.Calendar_Name,
			       c.CalendarStatus_ID,
			       COALESCE(cs.CalendarStatus_Name, '') AS CalendarStatus_Name,
			       c.City_ID,
			       COALESCE(city.City_Name, '') AS City_Name,
			       COALESCE(city.Region_ID, 0) AS Region_ID,
			       COALESCE(region.Region_Name, '') AS Region_Name,
			       c.TourismType_ID,
			       COALESCE(tourism.TourismType_Name, '') AS TourismType_Name,
			       c.EventType_ID,
			       COALESCE(event_type.EventType_Name, '') AS EventType_Name,
			       c.TypeEvent_ID,
			       COALESCE(type_event.TypeEvent_Name, '') AS TypeEvent_Name,
			       c.TourType_ID,
			       COALESCE(tour_type.TourType_Name, '') AS TourType_Name,
			       c.SailGroupType_ID,
			       c.User_ID,
			       c.UserCreate,
			       c.Calendar_UrlReglament,
			       c.Calendar_UrlProt,
			       c.Calendar_UrlMap,
			       c.Calendar_UrlReport,
			       c.Calendar_PrCrewWeight,
			       c.Calendar_Triangles,
			       c.Calendar_HorseTracking,
			       COALESCE(
			           NULLIF(
			               TRIM(
			                   CONCAT_WS(
			                       ' ',
			                       NULLIF(um_last.meta_value, ''),
			                       NULLIF(um_first.meta_value, ''),
			                       NULLIF(um_patronymic.meta_value, '')
			                   )
			               ),
			               ''
			           ),
			           u.display_name,
			           u.user_login,
			           ''
			       ) AS Responsible_FullName
			FROM Calendar c
			LEFT JOIN S_City city ON city.City_ID = c.City_ID
			LEFT JOIN S_Region region ON region.Region_ID = city.Region_ID
			LEFT JOIN S_TourismType tourism ON tourism.TourismType_ID = c.TourismType_ID
			LEFT JOIN S_EventType event_type ON event_type.EventType_ID = c.EventType_ID
			LEFT JOIN S_TypeEvent type_event ON type_event.TypeEvent_ID = c.TypeEvent_ID
			LEFT JOIN S_TourType tour_type ON tour_type.TourType_ID = c.TourType_ID
			LEFT JOIN S_CalendarStatus cs ON cs.CalendarStatus_ID = c.CalendarStatus_ID
			LEFT JOIN {$users_table} u ON u.ID = c.User_ID
			LEFT JOIN {$usermeta_table} um_first ON um_first.user_id = u.ID AND um_first.meta_key = 'first_name'
			LEFT JOIN {$usermeta_table} um_last ON um_last.user_id = u.ID AND um_last.meta_key = 'last_name'
			LEFT JOIN {$usermeta_table} um_patronymic ON um_patronymic.user_id = u.ID AND um_patronymic.meta_key = 'Patronymic'
		";
	}

	/**
	 * Будує SQL картки заходу.
	 */
	private function get_event_single_sql(): string {
		return $this->get_events_base_sql() . ' WHERE c.Calendar_ID = %d LIMIT 1';
	}

	/**
	 * Будує WHERE-частину для read-моделі заходів.
	 *
	 * @param array<string, mixed> $filters Фільтри.
	 * @return array{0: string, 1: array<int, int|string>}
	 */
	private function build_events_where_sql( array $filters, bool $fallback_to_year_range = true ): array {
		global $wpdb;

		$search          = isset( $filters['search'] ) ? sanitize_text_field( (string) $filters['search'] ) : '';
		$year            = isset( $filters['year'] ) ? absint( $filters['year'] ) : 0;
		$status_id       = isset( $filters['status_id'] ) ? absint( $filters['status_id'] ) : 0;
		$region_id       = isset( $filters['region_id'] ) ? absint( $filters['region_id'] ) : 0;
		$tourism_type_id = isset( $filters['tourism_type_id'] ) ? absint( $filters['tourism_type_id'] ) : 0;
		$event_type_id   = isset( $filters['event_type_id'] ) ? absint( $filters['event_type_id'] ) : 0;
		$period_start    = isset( $filters['period_start'] ) ? sanitize_text_field( (string) $filters['period_start'] ) : '';
		$period_end      = isset( $filters['period_end'] ) ? sanitize_text_field( (string) $filters['period_end'] ) : '';

		$where_clauses = [ '1=1' ];
		$params        = [];

		if ( '' !== $period_start && '' !== $period_end ) {
			$where_clauses[] = 'events.Calendar_DateBegin <= %s';
			$where_clauses[] = 'events.Calendar_DateEnd >= %s';
			$params[]        = $period_end;
			$params[]        = $period_start;
		} elseif ( $fallback_to_year_range && $year > 0 ) {
			$where_clauses[] = 'events.Calendar_DateBegin <= %s';
			$where_clauses[] = 'events.Calendar_DateEnd >= %s';
			$params[]        = sprintf( '%d-12-31 23:59:59', $year );
			$params[]        = sprintf( '%d-01-01 00:00:00', $year );
		}

		if ( '' !== $search ) {
			$where_clauses[] = 'events.Calendar_Name LIKE %s';
			$params[]        = '%' . $wpdb->esc_like( $search ) . '%';
		}

		if ( $status_id > 0 ) {
			$where_clauses[] = 'events.CalendarStatus_ID = %d';
			$params[]        = $status_id;
		}

		if ( $region_id > 0 ) {
			$where_clauses[] = 'events.Region_ID = %d';
			$params[]        = $region_id;
		}

		if ( $tourism_type_id > 0 ) {
			$where_clauses[] = 'events.TourismType_ID = %d';
			$params[]        = $tourism_type_id;
		}

		if ( $event_type_id > 0 ) {
			$where_clauses[] = 'events.EventType_ID = %d';
			$params[]        = $event_type_id;
		}

		return [ implode( ' AND ', $where_clauses ), $params ];
	}

	/**
	 * Будує масив даних для write-операцій Calendar.
	 *
	 * @param array<string, mixed> $data Дані події.
	 * @return array<string, mixed>
	 */
	private function build_event_write_data( array $data, bool $is_create ): array {
		$write_data = [
			'Calendar_DateBegin'      => (string) $data['date_begin'],
			'Calendar_DateEnd'        => (string) $data['date_end'],
			'Calendar_Name'           => (string) $data['name'],
			'TourismType_ID'          => (int) $data['tourism_type_id'],
			'TourType_ID'             => (int) $data['tour_type_id'],
			'CalendarStatus_ID'       => (int) $data['status_id'],
			'SailGroupType_ID'        => (int) $data['sail_group_type_id'],
			'TypeEvent_ID'            => (int) $data['type_event_id'],
			'Calendar_UrlReglament'   => (string) $data['url_reglament'],
			'Calendar_UrlProt'        => (string) $data['url_protocol'],
			'Calendar_UrlMap'         => (string) $data['url_map'],
			'Calendar_UrlReport'      => (string) $data['url_report'],
			'City_ID'                 => (int) $data['city_id'],
			'User_ID'                 => (int) $data['responsible_user_id'],
			'EventType_ID'            => (int) $data['event_type_id'],
			'Calendar_PrCrewWeight'   => (string) $data['crew_weight'],
			'Calendar_Triangles'      => (int) $data['triangles'],
			'Calendar_HorseTracking'  => (int) $data['horse_tracking'],
		];

		if ( $is_create ) {
			$write_data['Calendar_DateCreate'] = current_time( 'mysql' );
			$write_data['UserCreate']          = get_current_user_id();
		}

		return $write_data;
	}

	/**
	 * Повертає формати write-операцій.
	 *
	 * @return array<int, string>
	 */
	private function get_event_write_formats( bool $is_create ): array {
		$formats = [
			'%s',
			'%s',
			'%s',
			'%d',
			'%d',
			'%d',
			'%d',
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%d',
			'%d',
			'%d',
			'%s',
			'%d',
			'%d',
		];

		if ( $is_create ) {
			$formats[] = '%s';
			$formats[] = '%d';
		}

		return $formats;
	}

	/**
	 * Повертає кількість рядків у таблиці за конкретною колонкою.
	 */
	private function count_rows_by_column( string $table_name, string $column_name, int $value ): int {
		global $wpdb;

		if ( ! $this->table_has_column( $table_name, $column_name ) ) {
			return 0;
		}

		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE {$column_name} = %d",
				$value
			)
		);

		return max( 0, $count );
	}

	/**
	 * Повертає кількість дочірніх рядків перегонів заходу.
	 */
	private function count_race_child_rows_by_event( string $table_name, int $event_id ): int {
		global $wpdb;

		$race_id_column = $this->get_race_primary_key_column();
		if ( '' === $race_id_column || ! $this->table_has_column( $table_name, 'Race_ID' ) ) {
			return 0;
		}

		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} child INNER JOIN Race r ON r.{$race_id_column} = child.Race_ID WHERE r.Calendar_ID = %d",
				$event_id
			)
		);

		return max( 0, $count );
	}

	/**
	 * Повертає PK колонки таблиці Race.
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
	 * Перевіряє наявність колонки у таблиці.
	 */
	private function table_has_column( string $table_name, string $column_name ): bool {
		return in_array( $column_name, $this->get_table_columns( $table_name ), true );
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
}

