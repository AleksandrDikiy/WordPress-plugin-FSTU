<?php
namespace FSTU\Modules\Calendar\CalendarRoutes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repository підмодуля Calendar_Routes.
 * Працює тільки з базовими таблицями маршрутного та MKK-контуру.
 *
 * Version: 1.2.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarRoutes
 */
class Calendar_Routes_Repository {

	/**
	 * Повертає довідники для форм маршрутного контуру.
	 *
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public function get_support_datasets(): array {
		global $wpdb;

		return [
			'route_vehicles' => $wpdb->get_results( 'SELECT Vehicle_ID, Vehicle_Name, Vehicle_Order FROM S_Vehicle ORDER BY Vehicle_Order ASC, Vehicle_ID ASC', ARRAY_A ) ?: [],
			'route_statuses' => $wpdb->get_results( 'SELECT StatusApp_ID, StatusApp_Name, StatusApp_Order FROM S_StatusApp ORDER BY StatusApp_Order ASC, StatusApp_ID ASC', ARRAY_A ) ?: [],
		];
	}

	/**
	 * Повертає зведений контекст маршруту заходу.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_route_overview( int $calendar_id ): ?array {
		global $wpdb;

		$users_table    = $wpdb->users;
		$usermeta_table = $wpdb->usermeta;
		$sql            = "
			SELECT c.Calendar_ID,
			       c.Calendar_Name,
			       c.Calendar_DateBegin,
			       c.Calendar_DateEnd,
			       c.User_ID,
			       c.UserCreate,
			       c.TourismType_ID,
			       COALESCE(tourism.TourismType_Name, '') AS TourismType_Name,
			       COALESCE(city.City_Name, '') AS City_Name,
			       COALESCE(city.Region_ID, 0) AS Region_ID,
			       COALESCE(region.Region_Name, '') AS Region_Name,
			       at.AppTravel_ID,
			       at.StatusApp_ID,
			       at.UserCreate AS AppTravel_UserCreate,
			       at.AppTravel_DateCreate,
			       COALESCE(status_app.StatusApp_Name, '') AS StatusApp_Name,
			       COALESCE(path_summary.segments_count, 0) AS Segments_Count,
			       COALESCE(path_summary.total_distance, 0) AS Total_Distance,
			       COALESCE(verification_summary.verifications_count, 0) AS Verifications_Count,
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
			           responsible.display_name,
			           responsible.user_login,
			           ''
			       ) AS Responsible_FullName
			FROM Calendar c
			LEFT JOIN S_City city ON city.City_ID = c.City_ID
			LEFT JOIN S_Region region ON region.Region_ID = city.Region_ID
			LEFT JOIN S_TourismType tourism ON tourism.TourismType_ID = c.TourismType_ID
			LEFT JOIN AppTravel at ON at.Calendar_ID = c.Calendar_ID
			LEFT JOIN S_StatusApp status_app ON status_app.StatusApp_ID = at.StatusApp_ID
			LEFT JOIN (
				SELECT Calendar_ID,
				       COUNT(*) AS segments_count,
				       COALESCE(SUM(PathTrip_Distance), 0) AS total_distance
				FROM PathTrip
				GROUP BY Calendar_ID
			) path_summary ON path_summary.Calendar_ID = c.Calendar_ID
			LEFT JOIN (
				SELECT Calendar_ID, COUNT(*) AS verifications_count
				FROM VerificationMKK
				GROUP BY Calendar_ID
			) verification_summary ON verification_summary.Calendar_ID = c.Calendar_ID
			LEFT JOIN {$users_table} responsible ON responsible.ID = c.User_ID
			LEFT JOIN {$usermeta_table} um_first ON um_first.user_id = responsible.ID AND um_first.meta_key = 'first_name'
			LEFT JOIN {$usermeta_table} um_last ON um_last.user_id = responsible.ID AND um_last.meta_key = 'last_name'
			LEFT JOIN {$usermeta_table} um_patronymic ON um_patronymic.user_id = responsible.ID AND um_patronymic.meta_key = 'Patronymic'
			WHERE c.Calendar_ID = %d
			LIMIT 1
		";

		$item = $wpdb->get_row( $wpdb->prepare( $sql, $calendar_id ), ARRAY_A );

		return is_array( $item ) ? $item : null;
	}

	/**
	 * Повертає список ділянок маршруту.
	 *
	 * @return array{items: array<int, array<string, mixed>>, total: int, total_distance: int}
	 */
	public function get_route_segments( int $calendar_id, string $search, int $per_page, int $offset ): array {
		global $wpdb;

		$where  = [ 'p.Calendar_ID = %d' ];
		$params = [ $calendar_id ];

		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where[]  = '(p.PathTrip_Note LIKE %s OR COALESCE(vehicle.Vehicle_Name, "") LIKE %s)';
			$params[] = $like;
			$params[] = $like;
		}

		$where_sql = implode( ' AND ', $where );
		$count_sql = "SELECT COUNT(*) FROM PathTrip p LEFT JOIN S_Vehicle vehicle ON vehicle.Vehicle_ID = p.Vehicle_ID WHERE {$where_sql}";
		$data_sql  = "
			SELECT p.PathTrip_ID,
			       p.Calendar_ID,
			       p.Vehicle_ID,
			       p.PathTrip_DateCreate,
			       p.PathTrip_Date,
			       p.PathTrip_Note,
			       p.PathTrip_Distance,
			       p.PathTrip_UrlTreck,
			       COALESCE(vehicle.Vehicle_Name, '') AS Vehicle_Name
			FROM PathTrip p
			LEFT JOIN S_Vehicle vehicle ON vehicle.Vehicle_ID = p.Vehicle_ID
			WHERE {$where_sql}
			ORDER BY p.PathTrip_Date ASC, p.PathTrip_ID ASC
			LIMIT %d OFFSET %d
		";
		$total_sql = 'SELECT COALESCE(SUM(PathTrip_Distance), 0) FROM PathTrip WHERE Calendar_ID = %d';

		$total          = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );
		$total_distance = (int) $wpdb->get_var( $wpdb->prepare( $total_sql, $calendar_id ) );
		$items          = $wpdb->get_results( $wpdb->prepare( $data_sql, ...array_merge( $params, [ $per_page, $offset ] ) ), ARRAY_A );

		return [
			'items'          => is_array( $items ) ? $items : [],
			'total'          => $total,
			'total_distance' => $total_distance,
		];
	}

	/**
	 * Повертає всі ділянки маршруту без пагінації.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_route_segments_all( int $calendar_id ): array {
		global $wpdb;

		$sql = "
			SELECT p.PathTrip_ID,
			       p.Calendar_ID,
			       p.Vehicle_ID,
			       p.PathTrip_DateCreate,
			       p.PathTrip_Date,
			       p.PathTrip_Note,
			       p.PathTrip_Distance,
			       p.PathTrip_UrlTreck,
			       COALESCE(vehicle.Vehicle_Name, '') AS Vehicle_Name
			FROM PathTrip p
			LEFT JOIN S_Vehicle vehicle ON vehicle.Vehicle_ID = p.Vehicle_ID
			WHERE p.Calendar_ID = %d
			ORDER BY p.PathTrip_Date ASC, p.PathTrip_ID ASC
		";

		$items = $wpdb->get_results( $wpdb->prepare( $sql, $calendar_id ), ARRAY_A );

		return is_array( $items ) ? $items : [];
	}

	/**
	 * Повертає одну ділянку маршруту.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_route_segment( int $pathtrip_id ): ?array {
		global $wpdb;

		$sql = "
			SELECT p.PathTrip_ID,
			       p.Calendar_ID,
			       p.Vehicle_ID,
			       p.PathTrip_DateCreate,
			       p.PathTrip_Date,
			       p.PathTrip_Note,
			       p.PathTrip_Distance,
			       p.PathTrip_UrlTreck,
			       COALESCE(vehicle.Vehicle_Name, '') AS Vehicle_Name,
			       COALESCE(calendar.Calendar_Name, '') AS Calendar_Name
			FROM PathTrip p
			LEFT JOIN S_Vehicle vehicle ON vehicle.Vehicle_ID = p.Vehicle_ID
			LEFT JOIN Calendar calendar ON calendar.Calendar_ID = p.Calendar_ID
			WHERE p.PathTrip_ID = %d
			LIMIT 1
		";

		$item = $wpdb->get_row( $wpdb->prepare( $sql, $pathtrip_id ), ARRAY_A );

		return is_array( $item ) ? $item : null;
	}

	/**
	 * Повертає список рішень МКК для маршруту.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_verifications( int $calendar_id ): array {
		global $wpdb;

		$users_table    = $wpdb->users;
		$usermeta_table = $wpdb->usermeta;
		$sql            = "
			SELECT v.VerificationMKK_ID,
			       v.mkk_ID,
			       v.Calendar_ID,
			       v.VerificationMKK_DateCreate,
			       v.VerificationMKK_Status,
			       v.VerificationMKK_Note,
			       m.Region_ID,
			       m.TourismType_ID,
			       m.CommissionType_ID,
			       COALESCE(region.Region_Name, '') AS Region_Name,
			       COALESCE(tourism.TourismType_Name, '') AS TourismType_Name,
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
			           reviewer.display_name,
			           reviewer.user_login,
			           ''
			       ) AS Reviewer_FullName
			FROM VerificationMKK v
			LEFT JOIN mkk m ON m.mkk_ID = v.mkk_ID
			LEFT JOIN S_Region region ON region.Region_ID = m.Region_ID
			LEFT JOIN S_TourismType tourism ON tourism.TourismType_ID = m.TourismType_ID
			LEFT JOIN {$users_table} reviewer ON reviewer.ID = m.User_ID
			LEFT JOIN {$usermeta_table} um_first ON um_first.user_id = reviewer.ID AND um_first.meta_key = 'first_name'
			LEFT JOIN {$usermeta_table} um_last ON um_last.user_id = reviewer.ID AND um_last.meta_key = 'last_name'
			LEFT JOIN {$usermeta_table} um_patronymic ON um_patronymic.user_id = reviewer.ID AND um_patronymic.meta_key = 'Patronymic'
			WHERE v.Calendar_ID = %d
			ORDER BY v.VerificationMKK_DateCreate DESC, v.VerificationMKK_ID DESC
		";

		$items = $wpdb->get_results( $wpdb->prepare( $sql, $calendar_id ), ARRAY_A );

		return is_array( $items ) ? $items : [];
	}

	/**
	 * Повертає поточний запис AppTravel.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_apptravel( int $calendar_id ): ?array {
		global $wpdb;

		$sql  = "
			SELECT at.AppTravel_ID,
			       at.Calendar_ID,
			       at.StatusApp_ID,
			       at.UserCreate,
			       at.AppTravel_DateCreate,
			       COALESCE(status_app.StatusApp_Name, '') AS StatusApp_Name
			FROM AppTravel at
			LEFT JOIN S_StatusApp status_app ON status_app.StatusApp_ID = at.StatusApp_ID
			WHERE at.Calendar_ID = %d
			LIMIT 1
		";
		$item = $wpdb->get_row( $wpdb->prepare( $sql, $calendar_id ), ARRAY_A );

		return is_array( $item ) ? $item : null;
	}

	/**
	 * Повертає ID власника заходу.
	 */
	public function get_route_owner_id( int $calendar_id ): int {
		global $wpdb;

		$sql = 'SELECT COALESCE(NULLIF(User_ID, 0), NULLIF(UserCreate, 0), 0) FROM Calendar WHERE Calendar_ID = %d LIMIT 1';

		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $calendar_id ) );
	}

	/**
	 * Повертає email/name контакт відповідальної МКК.
	 *
	 * @return array<string,string>
	 */
	public function get_mkk_contact( int $mkk_id ): array {
		global $wpdb;

		$users_table    = $wpdb->users;
		$usermeta_table = $wpdb->usermeta;
		$sql            = "
			SELECT COALESCE(u.user_email, '') AS email,
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
			       ) AS full_name
			FROM mkk m
			LEFT JOIN {$users_table} u ON u.ID = m.User_ID
			LEFT JOIN {$usermeta_table} um_first ON um_first.user_id = u.ID AND um_first.meta_key = 'first_name'
			LEFT JOIN {$usermeta_table} um_last ON um_last.user_id = u.ID AND um_last.meta_key = 'last_name'
			LEFT JOIN {$usermeta_table} um_patronymic ON um_patronymic.user_id = u.ID AND um_patronymic.meta_key = 'Patronymic'
			WHERE m.mkk_ID = %d
			LIMIT 1
		";

		$item = $wpdb->get_row( $wpdb->prepare( $sql, $mkk_id ), ARRAY_A );

		return [
			'email'     => sanitize_email( (string) ( $item['email'] ?? '' ) ),
			'full_name' => sanitize_text_field( (string) ( $item['full_name'] ?? '' ) ),
		];
	}

	/**
	 * Повертає підпис/назву МКК.
	 */
	public function get_mkk_label( int $mkk_id ): string {
		global $wpdb;

		$sql = "
			SELECT COALESCE(
				NULLIF(TRIM(CONCAT_WS(' ', COALESCE(region.Region_Name, ''), COALESCE(tourism.TourismType_Name, ''), 'МКК')), ''),
				CONCAT('МКК #', m.mkk_ID)
			) AS label
			FROM mkk m
			LEFT JOIN S_Region region ON region.Region_ID = m.Region_ID
			LEFT JOIN S_TourismType tourism ON tourism.TourismType_ID = m.TourismType_ID
			WHERE m.mkk_ID = %d
			LIMIT 1
		";

		$label = $wpdb->get_var( $wpdb->prepare( $sql, $mkk_id ) );

		return is_string( $label ) ? sanitize_text_field( $label ) : '';
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
	 * Повертає базовий контекст заходу для MKK-перевірок.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_event_context( int $calendar_id ): ?array {
		global $wpdb;

		$sql  = "
			SELECT c.Calendar_ID,
			       c.Calendar_Name,
			       c.TourismType_ID,
			       COALESCE(city.Region_ID, 0) AS Region_ID,
			       c.User_ID,
			       c.UserCreate
			FROM Calendar c
			LEFT JOIN S_City city ON city.City_ID = c.City_ID
			WHERE c.Calendar_ID = %d
			LIMIT 1
		";
		$item = $wpdb->get_row( $wpdb->prepare( $sql, $calendar_id ), ARRAY_A );

		return is_array( $item ) ? $item : null;
	}

	/**
	 * Повертає розширений review-контекст маршруту для MKK policy.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_route_review_context( int $calendar_id ): ?array {
		global $wpdb;

		$sql = "
			SELECT c.Calendar_ID,
			       c.Calendar_Name,
			       c.TourismType_ID,
			       COALESCE(city.Region_ID, 0) AS Region_ID,
			       at.AppTravel_ID,
			       at.StatusApp_ID,
			       latest_verification.VerificationMKK_ID,
			       latest_verification.mkk_ID AS Verification_mkk_ID,
			       latest_verification.VerificationMKK_Status
			FROM Calendar c
			LEFT JOIN S_City city ON city.City_ID = c.City_ID
			LEFT JOIN AppTravel at ON at.Calendar_ID = c.Calendar_ID
			LEFT JOIN (
				SELECT v.Calendar_ID,
				       v.VerificationMKK_ID,
				       v.mkk_ID,
				       v.VerificationMKK_Status
				FROM VerificationMKK v
				INNER JOIN (
					SELECT Calendar_ID, MAX( VerificationMKK_ID ) AS latest_verification_id
					FROM VerificationMKK
					GROUP BY Calendar_ID
				) latest ON latest.latest_verification_id = v.VerificationMKK_ID
			) latest_verification ON latest_verification.Calendar_ID = c.Calendar_ID
			WHERE c.Calendar_ID = %d
			LIMIT 1
		";

		$item = $wpdb->get_row( $wpdb->prepare( $sql, $calendar_id ), ARRAY_A );

		return is_array( $item ) ? $item : null;
	}

	/**
	 * Повертає всі членства користувача у контурі МКК.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_user_mkk_memberships( int $user_id, int $tourism_type_id = 0 ): array {
		global $wpdb;

		$where  = [ 'User_ID = %d' ];
		$params = [ $user_id ];

		if ( $tourism_type_id > 0 ) {
			$where[]  = 'TourismType_ID = %d';
			$params[] = $tourism_type_id;
		}

		$sql = 'SELECT mkk_ID, User_ID, Region_ID, TourismType_ID, CommissionType_ID, mkk_DateBegin FROM mkk WHERE ' . implode( ' AND ', $where ) . ' ORDER BY CASE WHEN CommissionType_ID = 1 THEN 0 ELSE 1 END ASC, mkk_DateBegin DESC, mkk_ID DESC';
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * Повертає найбільш релевантну МКК для маршруту.
	 */
	public function find_matching_mkk_id( int $tourism_type_id, int $region_id ): int {
		global $wpdb;

		$match_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT mkk_ID FROM mkk WHERE TourismType_ID = %d ORDER BY CASE WHEN Region_ID = %d THEN 0 WHEN COALESCE(Region_ID, 0) = 0 THEN 1 ELSE 2 END ASC, CASE WHEN CommissionType_ID = %d THEN 0 ELSE 1 END ASC, mkk_DateBegin DESC, mkk_ID DESC LIMIT 1',
				$tourism_type_id,
				$region_id,
				1
			)
		);

		return max( 0, $match_id );
	}

	/**
	 * Повертає службовий стан маршруту для mutation-policy.
	 *
	 * @return array<string, int>
	 */
	public function get_route_mutation_state( int $calendar_id ): array {
		global $wpdb;

		$verification_count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM VerificationMKK WHERE Calendar_ID = %d', $calendar_id ) );
		$status_app_id      = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT StatusApp_ID FROM AppTravel WHERE Calendar_ID = %d LIMIT 1', $calendar_id ) );

		return [
			'verifications_count' => $verification_count,
			'status_app_id'       => $status_app_id,
		];
	}

	/**
	 * Створює ділянку маршруту.
	 */
	public function insert_pathtrip( array $data ): int {
		global $wpdb;

		$result = $wpdb->insert(
			'PathTrip',
			[
				'Calendar_ID'         => (int) $data['calendar_id'],
				'Vehicle_ID'          => (int) $data['vehicle_id'] > 0 ? (int) $data['vehicle_id'] : null,
				'PathTrip_DateCreate' => current_time( 'mysql' ),
				'PathTrip_Date'       => (string) $data['pathtrip_date'],
				'PathTrip_Note'       => (string) $data['pathtrip_note'],
				'PathTrip_Distance'   => (int) $data['pathtrip_distance'],
				'PathTrip_UrlTreck'   => '' !== (string) $data['pathtrip_url_treck'] ? (string) $data['pathtrip_url_treck'] : null,
			],
			[ '%d', '%d', '%s', '%s', '%s', '%d', '%s' ]
		);

		return false === $result ? 0 : (int) $wpdb->insert_id;
	}

	/**
	 * Оновлює ділянку маршруту.
	 */
	public function update_pathtrip( int $pathtrip_id, array $data ): bool {
		global $wpdb;

		$result = $wpdb->update(
			'PathTrip',
			[
				'Vehicle_ID'        => (int) $data['vehicle_id'] > 0 ? (int) $data['vehicle_id'] : null,
				'PathTrip_Date'     => (string) $data['pathtrip_date'],
				'PathTrip_Note'     => (string) $data['pathtrip_note'],
				'PathTrip_Distance' => (int) $data['pathtrip_distance'],
				'PathTrip_UrlTreck' => '' !== (string) $data['pathtrip_url_treck'] ? (string) $data['pathtrip_url_treck'] : null,
			],
			[ 'PathTrip_ID' => $pathtrip_id ],
			[ '%d', '%s', '%s', '%d', '%s' ],
			[ '%d' ]
		);

		return false !== $result;
	}

	/**
	 * Видаляє ділянку маршруту.
	 */
	public function delete_pathtrip( int $pathtrip_id ): bool {
		global $wpdb;

		$result = $wpdb->delete( 'PathTrip', [ 'PathTrip_ID' => $pathtrip_id ], [ '%d' ] );

		return false !== $result;
	}

	/**
	 * Оновлює або створює AppTravel для заходу.
	 */
	public function upsert_apptravel( int $calendar_id, int $status_app_id, int $user_id ): int {
		global $wpdb;

		$apptravel_id = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT AppTravel_ID FROM AppTravel WHERE Calendar_ID = %d LIMIT 1', $calendar_id ) );
		if ( $apptravel_id > 0 ) {
			$updated = $wpdb->update(
				'AppTravel',
				[ 'StatusApp_ID' => $status_app_id ],
				[ 'AppTravel_ID' => $apptravel_id ],
				[ '%d' ],
				[ '%d' ]
			);

			return false === $updated ? 0 : $apptravel_id;
		}

		$inserted = $wpdb->insert(
			'AppTravel',
			[
				'Calendar_ID'          => $calendar_id,
				'StatusApp_ID'         => $status_app_id,
				'UserCreate'           => $user_id,
				'AppTravel_DateCreate' => current_time( 'mysql' ),
			],
			[ '%d', '%d', '%d', '%s' ]
		);

		return false === $inserted ? 0 : (int) $wpdb->insert_id;
	}

	/**
	 * Додає рішення МКК.
	 */
	public function insert_verification( int $calendar_id, int $mkk_id, int $status, string $note ): int {
		global $wpdb;

		$result = $wpdb->insert(
			'VerificationMKK',
			[
				'mkk_ID'                     => $mkk_id,
				'Calendar_ID'                => $calendar_id,
				'VerificationMKK_DateCreate' => current_time( 'mysql' ),
				'VerificationMKK_Status'     => $status,
				'VerificationMKK_Note'       => '' !== $note ? $note : null,
			],
			[ '%d', '%d', '%s', '%d', '%s' ]
		);

		return false === $result ? 0 : (int) $wpdb->insert_id;
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

		return [
			'items' => is_array( $rows ) ? $rows : [],
			'total' => $total,
		];
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
	 * Підтверджує транзакцію.
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
}

