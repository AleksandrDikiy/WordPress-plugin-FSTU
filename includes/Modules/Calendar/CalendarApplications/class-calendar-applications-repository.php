<?php
namespace FSTU\Modules\Calendar\CalendarApplications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repository підмодуля Calendar_Applications.
 * Працює тільки з базовими таблицями та прямими SQL-запитами.
 *
	 * Version: 1.4.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarApplications
 */
class Calendar_Applications_Repository {

	/**
	 * @var array<string, array<int, string>>
	 */
	private array $table_columns_cache = [];

	/**
	 * @var array<string, bool>
	 */
	private array $table_exists_cache = [];

	/**
	 * Повертає довідники форми заявок.
	 *
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public function get_support_datasets(): array {
		global $wpdb;

		return [
			'app_statuses'        => $wpdb->get_results( 'SELECT StatusApp_ID, StatusApp_Name, StatusApp_Order FROM S_StatusApp ORDER BY StatusApp_Order ASC, StatusApp_ID ASC', ARRAY_A ) ?: [],
			'participation_types' => $wpdb->get_results( 'SELECT ParticipationType_ID, ParticipationType_Name, ParticipationType_Order, ParticipationType_Type FROM S_ParticipationType ORDER BY ParticipationType_Order ASC, ParticipationType_ID ASC', ARRAY_A ) ?: [],
			'regions'             => $wpdb->get_results( 'SELECT Region_ID, Region_Name FROM S_Region ORDER BY Region_Name ASC', ARRAY_A ) ?: [],
		];
	}

	/**
	 * Повертає список заявок заходу.
	 *
	 * @return array{items: array<int, array<string, mixed>>, total: int}
	 */
	public function get_applications( int $calendar_id, string $search, int $per_page, int $offset, int $current_user_id, bool $can_manage_any ): array {
		global $wpdb;

		$application_id_column = $this->get_application_primary_key_column();
		if ( '' === $application_id_column ) {
			return [ 'items' => [], 'total' => 0 ];
		}

		$where_clauses = [ 'apps.Calendar_ID = %d' ];
		$params        = [ $calendar_id ];

		if ( ! $can_manage_any ) {
			$where_clauses[] = 'apps.UserCreate = %d';
			$params[]        = $current_user_id;
		}

		if ( '' !== $search ) {
			$like            = '%' . $wpdb->esc_like( $search ) . '%';
			$where_clauses[] = '(apps.App_Name LIKE %s OR apps.Creator_FullName LIKE %s OR apps.Sailboat_Name LIKE %s)';
			$params[]        = $like;
			$params[]        = $like;
			$params[]        = $like;
		}

		$where_sql = implode( ' AND ', $where_clauses );
		$base_sql  = $this->get_applications_base_sql();
		$count_sql = "SELECT COUNT(*) FROM ({$base_sql}) apps WHERE {$where_sql}";
		$data_sql  = "SELECT * FROM ({$base_sql}) apps WHERE {$where_sql} ORDER BY apps.App_DateCreate DESC, apps.application_id DESC LIMIT %d OFFSET %d";

		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );
		$items = $wpdb->get_results( $wpdb->prepare( $data_sql, ...array_merge( $params, [ $per_page, $offset ] ) ), ARRAY_A );

		return [
			'items' => is_array( $items ) ? $items : [],
			'total' => $total,
		];
	}

	/**
	 * Повертає одну заявку.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_application( int $application_id ): ?array {
		global $wpdb;

		$application_id_column = $this->get_application_primary_key_column();
		if ( '' === $application_id_column ) {
			return null;
		}

		$sql  = $this->get_applications_base_sql() . ' WHERE a.' . $application_id_column . ' = %d LIMIT 1';
		$item = $wpdb->get_row( $wpdb->prepare( $sql, $application_id ), ARRAY_A );

		return is_array( $item ) ? $item : null;
	}

	/**
	 * Повертає розширений контекст заявки для participant-flow.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_application_context( int $application_id ): ?array {
		global $wpdb;

		$application_id_column = $this->get_application_primary_key_column();
		if ( '' === $application_id_column ) {
			return null;
		}

		$sql = "
			SELECT a.{$application_id_column} AS application_id,
			       a.Calendar_ID,
			       a.UserCreate,
			       a.StatusApp_ID,
			       a.Region_ID AS Application_Region_ID,
			       COALESCE(a.App_Name, '') AS App_Name,
			       c.TourismType_ID,
			       COALESCE(city.Region_ID, 0) AS Event_Region_ID
			FROM Application a
			INNER JOIN Calendar c ON c.Calendar_ID = a.Calendar_ID
			LEFT JOIN S_City city ON city.City_ID = c.City_ID
			WHERE a.{$application_id_column} = %d
			LIMIT 1
		";

		$item = $wpdb->get_row( $wpdb->prepare( $sql, $application_id ), ARRAY_A );

		return is_array( $item ) ? $item : null;
	}

	/**
	 * Повертає список учасників заявки.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_application_participants( int $application_id ): array {
		global $wpdb;

		$usercalendar_app_column = $this->get_usercalendar_application_fk_column();
		if ( '' === $usercalendar_app_column ) {
			return [];
		}

		$users_table    = $wpdb->users;
		$usermeta_table = $wpdb->usermeta;
		$latest_city_sql = "
			SELECT city_map.User_ID, city_map.City_ID, city.City_Name, region.Region_Name
			FROM UserCity city_map
			INNER JOIN (
				SELECT User_ID, MAX(UserCity_DateCreate) AS latest_date
				FROM UserCity
				GROUP BY User_ID
			) latest_city ON latest_city.User_ID = city_map.User_ID AND latest_city.latest_date = city_map.UserCity_DateCreate
			LEFT JOIN S_City city ON city.City_ID = city_map.City_ID
			LEFT JOIN S_Region region ON region.Region_ID = city.Region_ID
		";

		$sql = "
			SELECT uc.UserCalendar_ID,
			       uc.Calendar_ID,
			       uc.User_ID,
			       uc.{$usercalendar_app_column} AS application_id,
			       uc.ParticipationType_ID,
			       pt.ParticipationType_Name,
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
			           u.user_login
			       ) AS FIO,
			       CONCAT(
			           COALESCE(NULLIF(um_last.meta_value, ''), ''),
			           ' ',
			           CASE WHEN COALESCE(NULLIF(um_first.meta_value, ''), '') = '' THEN '' ELSE CONCAT(LEFT(um_first.meta_value, 1), '.') END,
			           CASE WHEN COALESCE(NULLIF(um_patronymic.meta_value, ''), '') = '' THEN '' ELSE CONCAT(LEFT(um_patronymic.meta_value, 1), '.') END
			       ) AS FIOshort,
			       COALESCE(um_birth.meta_value, '') AS BirthDate,
			       COALESCE(um_sex.meta_value, '') AS Sex,
			       COALESCE(city_data.City_Name, '') AS City_Name,
			       COALESCE(city_data.Region_Name, '') AS Region_Name
			FROM UserCalendar uc
			INNER JOIN {$users_table} u ON u.ID = uc.User_ID
			LEFT JOIN {$usermeta_table} um_first ON um_first.user_id = u.ID AND um_first.meta_key = 'first_name'
			LEFT JOIN {$usermeta_table} um_last ON um_last.user_id = u.ID AND um_last.meta_key = 'last_name'
			LEFT JOIN {$usermeta_table} um_patronymic ON um_patronymic.user_id = u.ID AND um_patronymic.meta_key = 'Patronymic'
			LEFT JOIN {$usermeta_table} um_birth ON um_birth.user_id = u.ID AND um_birth.meta_key = 'BirthDate'
			LEFT JOIN {$usermeta_table} um_sex ON um_sex.user_id = u.ID AND um_sex.meta_key = 'Sex'
			LEFT JOIN S_ParticipationType pt ON pt.ParticipationType_ID = uc.ParticipationType_ID
			LEFT JOIN ({$latest_city_sql}) city_data ON city_data.User_ID = uc.User_ID
			WHERE uc.{$usercalendar_app_column} = %d
			ORDER BY pt.ParticipationType_Order DESC, uc.UserCalendar_ID ASC
		";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $application_id ), ARRAY_A );

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * Повертає зв'язок учасника із заявкою.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_participant_link( int $usercalendar_id ): ?array {
		global $wpdb;

		if ( ! $this->table_has_column( 'UserCalendar', 'UserCalendar_ID' ) ) {
			return null;
		}

		$usercalendar_app_column = $this->get_usercalendar_application_fk_column();
		if ( '' === $usercalendar_app_column ) {
			return null;
		}

		$application_id_column = $this->get_application_primary_key_column();
		if ( '' === $application_id_column ) {
			return null;
		}

		$users_table    = $wpdb->users;
		$usermeta_table = $wpdb->usermeta;
		$sql            = "
			SELECT uc.UserCalendar_ID,
			       uc.Calendar_ID,
			       uc.User_ID,
			       uc.{$usercalendar_app_column} AS application_id,
			       uc.ParticipationType_ID,
			       a.UserCreate AS Application_UserCreate,
			       a.StatusApp_ID,
			       COALESCE(a.App_Name, '') AS App_Name,
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
			       ) AS Participant_FullName
			FROM UserCalendar uc
			INNER JOIN Application a ON a.{$application_id_column} = uc.{$usercalendar_app_column}
			LEFT JOIN {$users_table} u ON u.ID = uc.User_ID
			LEFT JOIN {$usermeta_table} um_first ON um_first.user_id = u.ID AND um_first.meta_key = 'first_name'
			LEFT JOIN {$usermeta_table} um_last ON um_last.user_id = u.ID AND um_last.meta_key = 'last_name'
			LEFT JOIN {$usermeta_table} um_patronymic ON um_patronymic.user_id = u.ID AND um_patronymic.meta_key = 'Patronymic'
			WHERE uc.UserCalendar_ID = %d
			LIMIT 1
		";

		$item = $wpdb->get_row( $wpdb->prepare( $sql, $usercalendar_id ), ARRAY_A );

		return is_array( $item ) ? $item : null;
	}

	/**
	 * Перевіряє, чи вже доданий користувач у заявку.
	 */
	public function participant_exists( int $application_id, int $user_id ): bool {
		global $wpdb;

		$usercalendar_app_column = $this->get_usercalendar_application_fk_column();
		if ( '' === $usercalendar_app_column || ! $this->table_has_column( 'UserCalendar', 'User_ID' ) ) {
			return false;
		}

		$exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT 1 FROM UserCalendar WHERE ' . $usercalendar_app_column . ' = %d AND User_ID = %d LIMIT 1',
				$application_id,
				$user_id
			)
		);

		return ! empty( $exists );
	}

	/**
	 * Повертає true, якщо існує тип участі.
	 */
	public function participation_type_exists( int $participation_type_id ): bool {
		return $participation_type_id > 0 && $this->count_rows_by_column( 'S_ParticipationType', 'ParticipationType_ID', $participation_type_id ) > 0;
	}

	/**
	 * Додає учасника до заявки.
	 */
	public function insert_application_participant( int $application_id, int $calendar_id, int $user_id, int $participation_type_id ): int {
		global $wpdb;

		$usercalendar_app_column = $this->get_usercalendar_application_fk_column();
		if ( '' === $usercalendar_app_column ) {
			return 0;
		}

		$insert_data = [
			'Calendar_ID' => $calendar_id,
			'User_ID'     => $user_id,
			$usercalendar_app_column => $application_id,
		];

		if ( $this->table_has_column( 'UserCalendar', 'ParticipationType_ID' ) ) {
			$insert_data['ParticipationType_ID'] = $participation_type_id;
		}

		if ( $this->table_has_column( 'UserCalendar', 'UserCreate' ) ) {
			$insert_data['UserCreate'] = get_current_user_id();
		}

		if ( $this->table_has_column( 'UserCalendar', 'UserCalendar_DateCreate' ) ) {
			$insert_data['UserCalendar_DateCreate'] = current_time( 'mysql' );
		}

		$result = $wpdb->insert( 'UserCalendar', $insert_data, $this->build_formats( $insert_data ) );

		return false === $result ? 0 : (int) $wpdb->insert_id;
	}

	/**
	 * Видаляє зв'язок учасника із заявкою.
	 */
	public function delete_application_participant( int $usercalendar_id ): bool {
		global $wpdb;

		$result = $wpdb->delete( 'UserCalendar', [ 'UserCalendar_ID' => $usercalendar_id ], [ '%d' ] );

		return false !== $result;
	}

	/**
	 * Повертає owner заявки.
	 */
	public function get_application_owner_id( int $application_id ): int {
		global $wpdb;

		$application_id_column = $this->get_application_primary_key_column();
		if ( '' === $application_id_column ) {
			return 0;
		}

		$sql = 'SELECT UserCreate FROM Application WHERE ' . $application_id_column . ' = %d LIMIT 1';

		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $application_id ) );
	}

	/**
	 * Повертає кількість залежностей заявки.
	 *
	 * @return array<string,int>
	 */
	public function get_application_dependency_counts( int $application_id, int $calendar_id = 0 ): array {
		$usercalendar_app_column = $this->get_usercalendar_application_fk_column();
		$dependencies            = [
			'UserCalendar'    => 0,
			'PathTrip'        => 0,
			'AppTravel'       => 0,
			'VerificationMKK' => 0,
			'Race'            => 0,
			'RaceProtokol'    => 0,
			'RaceResult'      => 0,
			'RaceTypeResult'  => 0,
		];

		if ( '' !== $usercalendar_app_column ) {
			$dependencies['UserCalendar'] = $this->count_rows_by_column( 'UserCalendar', $usercalendar_app_column, $application_id );
		}

		$apptravel_ids             = [];
		$can_use_calendar_fallback = $calendar_id > 0 && 1 === $this->count_rows_by_column( 'Application', 'Calendar_ID', $calendar_id );

		$apptravel_app_column = $this->get_first_existing_column( 'AppTravel', [ 'App_ID', 'Application_ID' ] );
		if ( '' !== $apptravel_app_column ) {
			$dependencies['AppTravel'] = $this->count_rows_by_column( 'AppTravel', $apptravel_app_column, $application_id );
			$apptravel_ids             = $this->get_ids_by_column( 'AppTravel', 'AppTravel_ID', $apptravel_app_column, $application_id );
		}

		$pathtrip_app_column = $this->get_first_existing_column( 'PathTrip', [ 'App_ID', 'Application_ID' ] );
		if ( '' !== $pathtrip_app_column ) {
			$dependencies['PathTrip'] = $this->count_rows_by_column( 'PathTrip', $pathtrip_app_column, $application_id );
		}

		$verification_app_column = $this->get_first_existing_column( 'VerificationMKK', [ 'App_ID', 'Application_ID' ] );
		if ( '' !== $verification_app_column ) {
			$dependencies['VerificationMKK'] = $this->count_rows_by_column( 'VerificationMKK', $verification_app_column, $application_id );
		}

		if ( ! empty( $apptravel_ids ) ) {
			if ( 0 === $dependencies['PathTrip'] && $this->table_has_column( 'PathTrip', 'AppTravel_ID' ) ) {
				$dependencies['PathTrip'] = $this->count_rows_by_ids( 'PathTrip', 'AppTravel_ID', $apptravel_ids );
			}

			if ( 0 === $dependencies['VerificationMKK'] && $this->table_has_column( 'VerificationMKK', 'AppTravel_ID' ) ) {
				$dependencies['VerificationMKK'] = $this->count_rows_by_ids( 'VerificationMKK', 'AppTravel_ID', $apptravel_ids );
			}
		}

		if ( $can_use_calendar_fallback ) {
			if ( 0 === $dependencies['AppTravel'] ) {
				$dependencies['AppTravel'] = $this->count_rows_by_column( 'AppTravel', 'Calendar_ID', $calendar_id );
			}

			if ( 0 === $dependencies['PathTrip'] ) {
				$dependencies['PathTrip'] = $this->count_rows_by_column( 'PathTrip', 'Calendar_ID', $calendar_id );
			}

			if ( 0 === $dependencies['VerificationMKK'] ) {
				$dependencies['VerificationMKK'] = $this->count_rows_by_column( 'VerificationMKK', 'Calendar_ID', $calendar_id );
			}

			$dependencies['Race']           = $this->count_rows_by_column( 'Race', 'Calendar_ID', $calendar_id );
			$dependencies['RaceProtokol']   = $this->count_race_child_rows_by_calendar( 'RaceProtokol', $calendar_id );
			$dependencies['RaceResult']     = $this->count_race_child_rows_by_calendar( 'RaceResult', $calendar_id );
			$dependencies['RaceTypeResult'] = $this->count_race_child_rows_by_calendar( 'RaceTypeResult', $calendar_id );
		}

		return $dependencies;
	}

	/**
	 * Повертає статус за замовчуванням.
	 */
	public function get_default_status_id(): int {
		global $wpdb;

		return (int) $wpdb->get_var( 'SELECT StatusApp_ID FROM S_StatusApp ORDER BY StatusApp_Order ASC, StatusApp_ID ASC LIMIT 1' );
	}

	/**
	 * Повертає карту статусного flow для MVP.
	 *
	 * @return array<string, int>
	 */
	public function get_application_status_flow_map(): array {
		$statuses = $this->get_support_datasets()['app_statuses'] ?? [];
		$map      = [
			'draft'        => 0,
			'under_review' => 0,
			'approved'     => 0,
			'needs_fixes'  => 0,
		];

		if ( ! is_array( $statuses ) ) {
			return $map;
		}

		foreach ( $statuses as $index => $status ) {
			$status_id   = isset( $status['StatusApp_ID'] ) ? absint( $status['StatusApp_ID'] ) : 0;
			$status_name = isset( $status['StatusApp_Name'] ) ? mb_strtolower( sanitize_text_field( (string) $status['StatusApp_Name'] ) ) : '';

			if ( $status_id <= 0 ) {
				continue;
			}

			if ( 0 === $map['draft'] && ( false !== mb_strpos( $status_name, 'чернет' ) || false !== mb_strpos( $status_name, 'draft' ) || 0 === $index ) ) {
				$map['draft'] = $status_id;
				continue;
			}

			if ( 0 === $map['under_review'] && ( false !== mb_strpos( $status_name, 'розгляд' ) || false !== mb_strpos( $status_name, 'review' ) || 1 === $index ) ) {
				$map['under_review'] = $status_id;
				continue;
			}

			if ( 0 === $map['approved'] && ( false !== mb_strpos( $status_name, 'підтвер' ) || false !== mb_strpos( $status_name, 'approved' ) || 2 === $index ) ) {
				$map['approved'] = $status_id;
				continue;
			}

			if ( 0 === $map['needs_fixes'] && ( false !== mb_strpos( $status_name, 'доопрац' ) || false !== mb_strpos( $status_name, 'помил' ) || false !== mb_strpos( $status_name, 'fix' ) || 3 === $index ) ) {
				$map['needs_fixes'] = $status_id;
			}
		}

		return $map;
	}

	/**
	 * Повертає назву статусу заявки.
	 */
	public function get_status_name_by_id( int $status_id ): string {
		$statuses = $this->get_support_datasets()['app_statuses'] ?? [];

		if ( is_array( $statuses ) ) {
			foreach ( $statuses as $status ) {
				if ( $status_id === absint( $status['StatusApp_ID'] ?? 0 ) ) {
					return sanitize_text_field( (string) ( $status['StatusApp_Name'] ?? '' ) );
				}
			}
		}

		return '';
	}

	/**
	 * Повертає регіон міста.
	 */
	public function get_city_region_id( int $city_id ): int {
		global $wpdb;

		return $city_id > 0
			? (int) $wpdb->get_var( $wpdb->prepare( 'SELECT Region_ID FROM S_City WHERE City_ID = %d LIMIT 1', $city_id ) )
			: 0;
	}

	/**
	 * Чи має користувач already tourism link.
	 */
	public function user_has_tourism_type( int $user_id, int $tourism_type_id ): bool {
		global $wpdb;

		if ( $user_id <= 0 || $tourism_type_id <= 0 ) {
			return false;
		}

		$exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT 1 FROM UserTourismType WHERE User_ID = %d AND TourismType_ID = %d LIMIT 1',
				$user_id,
				$tourism_type_id
			)
		);

		return ! empty( $exists );
	}

	/**
	 * Додає місто користувача.
	 */
	public function insert_user_city( int $user_id, int $city_id ): bool {
		global $wpdb;

		$result = $wpdb->insert(
			'UserCity',
			[
				'User_ID'             => $user_id,
				'City_ID'             => $city_id,
				'UserCity_DateCreate' => current_time( 'mysql' ),
			],
			[ '%d', '%d', '%s' ]
		);

		return false !== $result;
	}

	/**
	 * Додає вид туризму користувачу.
	 */
	public function insert_user_tourism_type( int $user_id, int $tourism_type_id ): bool {
		global $wpdb;

		$result = $wpdb->insert(
			'UserTourismType',
			[
				'User_ID'                    => $user_id,
				'TourismType_ID'             => $tourism_type_id,
				'UserTourismType_DateCreate' => current_time( 'mysql' ),
			],
			[ '%d', '%d', '%s' ]
		);

		return false !== $result;
	}

	/**
	 * Додає реєстрацію користувача в ОФСТ.
	 */
	public function insert_user_registration_ofst( int $user_id, int $region_id, int $unit_id ): bool {
		global $wpdb;

		$result = $wpdb->insert(
			'UserRegistationOFST',
			[
				'User_ID'                        => $user_id,
				'Region_ID'                      => $region_id,
				'Unit_ID'                        => $unit_id,
				'UserRegistationOFST_DateCreate' => current_time( 'mysql' ),
			],
			[ '%d', '%d', '%d', '%s' ]
		);

		return false !== $result;
	}

	/**
	 * Чи має користувач хоча б один членський квиток.
	 */
	public function user_has_member_card( int $user_id ): bool {
		global $wpdb;

		$exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT 1 FROM UserMemberCard WHERE User_ID = %d LIMIT 1',
				$user_id
			)
		);

		return ! empty( $exists );
	}

	/**
	 * Повертає наступний номер членського квитка в межах регіону.
	 */
	public function get_next_member_card_number( int $region_id ): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COALESCE(MAX(UserMemberCard_Number), 0) + 1 FROM UserMemberCard WHERE Region_ID = %d',
				$region_id
			)
		);
	}

	/**
	 * Створює запис членського квитка.
	 */
	public function create_member_card( int $user_id, int $region_id, int $card_number, string $last_name, string $first_name ): bool {
		global $wpdb;

		$result = $wpdb->insert(
			'UserMemberCard',
			[
				'UserMemberCard_DateCreate' => current_time( 'mysql' ),
				'UserCreate'                => get_current_user_id(),
				'User_ID'                   => $user_id,
				'TypeCard_ID'               => 1,
				'StatusCard_ID'             => 1,
				'Region_ID'                 => $region_id,
				'UserMemberCard_Number'     => $card_number,
				'UserMemberCard_LastName'   => $last_name,
				'UserMemberCard_FirstName'  => $first_name,
			],
			[ '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s' ]
		);

		return false !== $result;
	}

	/**
	 * Повертає значення системного налаштування.
	 */
	public function get_setting_value( string $param_name ): string {
		global $wpdb;

		$value = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT ParamValue FROM Settings WHERE ParamName = %s',
				$param_name
			)
		);

		return is_string( $value ) ? $value : '';
	}

	/**
	 * Створює заявку.
	 */
	public function insert_application( array $data ): int {
		global $wpdb;

		$insert_data = [
			'App_DateCreate' => current_time( 'mysql' ),
			'UserCreate'     => get_current_user_id(),
			'Calendar_ID'    => (int) $data['calendar_id'],
			'StatusApp_ID'   => (int) $data['status_app_id'],
			'Sailboat_ID'    => (int) $data['sailboat_id'],
			'MR_ID'          => (int) $data['mr_id'],
			'SailGroup_ID'   => (int) $data['sail_group_id'],
			'Region_ID'      => (int) $data['region_id'],
			'App_Name'       => (string) $data['app_name'],
			'App_Number'     => (string) $data['app_number'],
			'App_Phone'      => $data['app_phone'] > 0 ? (int) $data['app_phone'] : null,
		];

		$columns = $this->get_table_columns( 'Application' );
		foreach ( array_keys( $insert_data ) as $column_name ) {
			if ( ! in_array( $column_name, $columns, true ) ) {
				unset( $insert_data[ $column_name ] );
			}
		}

		$formats = [];
		foreach ( $insert_data as $column_name => $value ) {
			$formats[] = in_array( $column_name, [ 'App_DateCreate', 'App_Name', 'App_Number' ], true ) ? '%s' : '%d';
		}

		$result = $wpdb->insert( 'Application', $insert_data, $formats );
		if ( false === $result ) {
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Оновлює заявку.
	 */
	public function update_application( int $application_id, array $data ): bool {
		global $wpdb;

		$application_id_column = $this->get_application_primary_key_column();
		if ( '' === $application_id_column ) {
			return false;
		}

		$update_data = [
			'StatusApp_ID' => (int) $data['status_app_id'],
			'Sailboat_ID'  => (int) $data['sailboat_id'],
			'MR_ID'        => (int) $data['mr_id'],
			'SailGroup_ID' => (int) $data['sail_group_id'],
			'Region_ID'    => (int) $data['region_id'],
			'App_Name'     => (string) $data['app_name'],
			'App_Number'   => (string) $data['app_number'],
			'App_Phone'    => $data['app_phone'] > 0 ? (int) $data['app_phone'] : null,
		];

		$columns = $this->get_table_columns( 'Application' );
		foreach ( array_keys( $update_data ) as $column_name ) {
			if ( ! in_array( $column_name, $columns, true ) ) {
				unset( $update_data[ $column_name ] );
			}
		}

		$formats = [];
		foreach ( $update_data as $column_name => $value ) {
			$formats[] = in_array( $column_name, [ 'App_Name', 'App_Number' ], true ) ? '%s' : '%d';
		}

		$result = $wpdb->update(
			'Application',
			$update_data,
			[ $application_id_column => $application_id ],
			$formats,
			[ '%d' ]
		);

		return false !== $result;
	}

	/**
	 * Оновлює лише статус заявки.
	 */
	public function update_application_status( int $application_id, int $status_id ): bool {
		global $wpdb;

		$application_id_column = $this->get_application_primary_key_column();
		if ( '' === $application_id_column || ! $this->table_has_column( 'Application', 'StatusApp_ID' ) ) {
			return false;
		}

		$result = $wpdb->update(
			'Application',
			[ 'StatusApp_ID' => $status_id ],
			[ $application_id_column => $application_id ],
			[ '%d' ],
			[ '%d' ]
		);

		return false !== $result;
	}

	/**
	 * Видаляє заявку.
	 */
	public function delete_application( int $application_id ): bool {
		global $wpdb;

		$application_id_column = $this->get_application_primary_key_column();
		if ( '' === $application_id_column ) {
			return false;
		}

		$result = $wpdb->delete( 'Application', [ $application_id_column => $application_id ], [ '%d' ] );

		return false !== $result;
	}

	/**
	 * Повертає протокол підмодуля.
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

		return [ 'items' => is_array( $rows ) ? $rows : [], 'total' => $total ];
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
	 * Ролбек транзакції.
	 */
	public function rollback(): void {
		global $wpdb;
		$wpdb->query( 'ROLLBACK' );
	}

	/**
	 * Повертає базовий SELECT read-моделі заявок.
	 */
	private function get_applications_base_sql(): string {
		global $wpdb;

		$application_id_column = $this->get_application_primary_key_column();
		$usercalendar_app_column = $this->get_usercalendar_application_fk_column();
		$users_table = $wpdb->users;
		$usermeta_table = $wpdb->usermeta;
		$participants_count_sql = '' !== $usercalendar_app_column
			? '(SELECT COUNT(*) FROM UserCalendar ucp WHERE ucp.' . $usercalendar_app_column . ' = a.' . $application_id_column . ')'
			: '0';

		return "
			SELECT a.{$application_id_column} AS application_id,
			       a.Calendar_ID,
			       a.App_DateCreate,
			       a.UserCreate,
			       a.StatusApp_ID,
			       COALESCE(status_app.StatusApp_Name, '') AS StatusApp_Name,
			       a.Sailboat_ID,
			       a.MR_ID,
			       a.SailGroup_ID,
			       COALESCE(sail_group.SailGroup_Name, '') AS SailGroup_Name,
			       a.Region_ID,
			       COALESCE(region.Region_Name, '') AS Region_Name,
			       COALESCE(a.App_Name, '') AS App_Name,
			       COALESCE(a.App_Number, '') AS App_Number,
			       COALESCE(a.App_Phone, '') AS App_Phone,
			       COALESCE(sailboat.Sailboat_Name, '') AS Sailboat_Name,
			       COALESCE(sailboat.RegNumber, '') AS RegNumber,
			       COALESCE(sailboat.Sailboat_NumberSail, '') AS Sailboat_NumberSail,
			       {$participants_count_sql} AS Participants_Count,
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
			           creator.display_name,
			           creator.user_login,
			           ''
			       ) AS Creator_FullName
			FROM Application a
			LEFT JOIN S_StatusApp status_app ON status_app.StatusApp_ID = a.StatusApp_ID
			LEFT JOIN S_Region region ON region.Region_ID = a.Region_ID
			LEFT JOIN Sailboat sailboat ON sailboat.Sailboat_ID = a.Sailboat_ID
			LEFT JOIN S_SailGroup sail_group ON sail_group.SailGroup_ID = a.SailGroup_ID
			LEFT JOIN {$users_table} creator ON creator.ID = a.UserCreate
			LEFT JOIN {$usermeta_table} um_first ON um_first.user_id = creator.ID AND um_first.meta_key = 'first_name'
			LEFT JOIN {$usermeta_table} um_last ON um_last.user_id = creator.ID AND um_last.meta_key = 'last_name'
			LEFT JOIN {$usermeta_table} um_patronymic ON um_patronymic.user_id = creator.ID AND um_patronymic.meta_key = 'Patronymic'
		";
	}

	/**
	 * Повертає ім’я PK колонки таблиці Application.
	 */
	private function get_application_primary_key_column(): string {
		$columns = $this->get_table_columns( 'Application' );

		foreach ( [ 'App_ID', 'Application_ID' ] as $column_name ) {
			if ( in_array( $column_name, $columns, true ) ) {
				return $column_name;
			}
		}

		return '';
	}

	/**
	 * Повертає FK колонку зв’язку UserCalendar -> Application.
	 */
	private function get_usercalendar_application_fk_column(): string {
		$columns = $this->get_table_columns( 'UserCalendar' );

		foreach ( [ 'App_ID', 'Application_ID' ] as $column_name ) {
			if ( in_array( $column_name, $columns, true ) ) {
				return $column_name;
			}
		}

		return '';
	}

	/**
	 * Повертає першу знайдену колонку зі списку кандидатів.
	 */
	private function get_first_existing_column( string $table_name, array $column_candidates ): string {
		$columns = $this->get_table_columns( $table_name );

		foreach ( $column_candidates as $column_name ) {
			if ( in_array( $column_name, $columns, true ) ) {
				return $column_name;
			}
		}

		return '';
	}

	/**
	 * Повертає кількість рядків у таблиці за колонкою.
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
	 * Повертає набір ID за колонкою зв'язку.
	 *
	 * @return array<int, int>
	 */
	private function get_ids_by_column( string $table_name, string $id_column, string $match_column, int $match_value ): array {
		global $wpdb;

		if ( ! $this->table_has_column( $table_name, $id_column ) || ! $this->table_has_column( $table_name, $match_column ) ) {
			return [];
		}

		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT {$id_column} FROM {$table_name} WHERE {$match_column} = %d",
				$match_value
			)
		);

		if ( ! is_array( $rows ) ) {
			return [];
		}

		return array_values( array_filter( array_map( 'absint', $rows ) ) );
	}

	/**
	 * Повертає кількість рядків за списком ID.
	 *
	 * @param array<int, int> $ids
	 */
	private function count_rows_by_ids( string $table_name, string $column_name, array $ids ): int {
		global $wpdb;

		$ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
		if ( empty( $ids ) || ! $this->table_has_column( $table_name, $column_name ) ) {
			return 0;
		}

		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
		$sql          = "SELECT COUNT(*) FROM {$table_name} WHERE {$column_name} IN ({$placeholders})";
		$count        = (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$ids ) );

		return max( 0, $count );
	}

	/**
	 * Повертає кількість дочірніх race-залежностей у межах календаря заявки.
	 */
	private function count_race_child_rows_by_calendar( string $table_name, int $calendar_id ): int {
		global $wpdb;

		$race_id_column = $this->get_first_existing_column( 'Race', [ 'Race_ID', 'RaceId' ] );
		if ( '' === $race_id_column || ! $this->table_has_column( 'Race', 'Calendar_ID' ) || ! $this->table_has_column( $table_name, 'Race_ID' ) ) {
			return 0;
		}

		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} child INNER JOIN Race r ON r.{$race_id_column} = child.Race_ID WHERE r.Calendar_ID = %d",
				$calendar_id
			)
		);

		return max( 0, $count );
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

	/**
	 * Повертає формати для write-операцій.
	 *
	 * @param array<string, mixed> $data
	 * @return array<int, string>
	 */
	private function build_formats( array $data ): array {
		$formats = [];
		foreach ( $data as $value ) {
			if ( null === $value ) {
				$formats[] = '%s';
				continue;
			}

			$formats[] = is_int( $value ) ? '%d' : '%s';
		}

		return $formats;
	}
}

