<?php
namespace FSTU\Modules\Registry\Steering;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repository модуля «Реєстр стернових ФСТУ».
 *
	 * Містить SQL-запити до legacy-таблиць і views модуля Steering,
	 * включно зі списком реєстру, карткою, довідниками та протоколом Logs.
 *
	 * Version:     1.9.0
 * Date_update: 2026-04-08
 *
 * @package FSTU\Modules\Registry\Steering
 */
class Steering_Repository {

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function get_cities(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$items = $wpdb->get_results(
			'SELECT City_ID, City_Name FROM S_City ORDER BY City_Name ASC',
			ARRAY_A
		);

		return is_array( $items ) ? $items : [];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function get_available_users(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$items = $wpdb->get_results(
			'SELECT User_ID, FIO FROM vUserFSTU WHERE User_ID NOT IN (SELECT User_ID FROM Steering) ORDER BY FIO ASC',
			ARRAY_A
		);

		return is_array( $items ) ? $items : [];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function get_status_options(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$items = $wpdb->get_results(
			'SELECT AppStatus_ID, AppStatus_Name FROM S_AppStatus ORDER BY AppStatus_ID ASC',
			ARRAY_A
		);

		return is_array( $items ) ? $items : [];
	}

	public function get_setting_value( string $param_name ): string {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$value = $wpdb->get_var(
			$wpdb->prepare( 'SELECT ParamValue FROM Settings WHERE ParamName = %s', $param_name )
		);

		return is_string( $value ) ? $value : '';
	}

	public function user_has_steering_record( int $user_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT Steering_ID FROM Steering WHERE User_ID = %d LIMIT 1',
				$user_id
			)
		);

		return null !== $existing_id;
	}

	public function user_exists( int $user_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT User_ID FROM vUserFSTU WHERE User_ID = %d LIMIT 1',
				$user_id
			)
		);

		return null !== $existing_id;
	}

	public function get_user_fio( int $user_id ): string {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$fio = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT FIO FROM vUserFSTU WHERE User_ID = %d LIMIT 1',
				$user_id
			)
		);

		return is_string( $fio ) ? $fio : '';
	}

	public function user_has_verifier_qualification( int $user_id ): bool {
		return $this->user_has_skipper_certificate( $user_id ) || $this->user_has_registered_steering_certificate( $user_id );
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function get_verifications_by_steering_id( int $steering_id ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT v.VerificationSteering_Date, v.User_ID, u.FIO,
					k.Skipper_RegNumber,
					sv.Steering_RegNumber
				FROM VerificationSteering v
				LEFT JOIN vUserFSTU u ON u.User_ID = v.User_ID
				LEFT JOIN vSkipper k ON k.User_ID = v.User_ID
				LEFT JOIN vSteering sv ON sv.User_ID = v.User_ID
				WHERE v.Steering_ID = %d
				ORDER BY v.VerificationSteering_Date DESC, v.User_ID DESC",
				$steering_id
			),
			ARRAY_A
		);

		return is_array( $items ) ? $items : [];
	}

	public function verification_exists_for_user( int $steering_id, int $user_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT User_ID FROM VerificationSteering WHERE Steering_ID = %d AND User_ID = %d LIMIT 1',
				$steering_id,
				$user_id
			)
		);

		return null !== $existing_id;
	}

	public function insert_verification( int $steering_id, int $user_id ): bool {
		global $wpdb;

		$sql = $wpdb->prepare(
			'INSERT INTO VerificationSteering (VerificationSteering_Date, Steering_ID, User_ID)
			 SELECT %s, %d, %d
			 WHERE NOT EXISTS (
				 SELECT 1
				 FROM VerificationSteering
				 WHERE Steering_ID = %d AND User_ID = %d
			 )',
			current_time( 'mysql' ),
			$steering_id,
			$user_id,
			$steering_id,
			$user_id
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->query( $sql );

		return 1 === (int) $result;
	}

	public function count_verifications( int $steering_id ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$total = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM VerificationSteering WHERE Steering_ID = %d',
				$steering_id
			)
		);

		return (int) $total;
	}

	public function get_next_registration_number(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$max_number = $wpdb->get_var( 'SELECT COALESCE(MAX(CAST(Steering_RegNumber AS UNSIGNED)), 0) FROM Steering' );

		return max( 1, (int) $max_number + 1 );
	}

	public function register_after_verification_threshold( int $steering_id, int $threshold = 3 ): int {
		global $wpdb;

		if ( $threshold <= 0 || $this->count_verifications( $steering_id ) < $threshold || $this->is_registered_item( $steering_id ) ) {
			return 0;
		}

		$next_number = $this->get_next_registration_number();
		if ( $next_number <= 0 ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE Steering
				SET AppStatus_ID = 3,
					Steering_RegNumber = %d
				WHERE Steering_ID = %d
				  AND ( Steering_RegNumber IS NULL OR Steering_RegNumber = '' OR AppStatus_ID < 3 )",
				$next_number,
				$steering_id
			)
		);

		if ( false === $result ) {
			throw new \RuntimeException( 'steering_register_failed' );
		}

		if ( 0 === (int) $result && ! $this->is_registered_item( $steering_id ) ) {
			throw new \RuntimeException( 'steering_register_failed' );
		}

		return 0 === (int) $result ? 0 : $next_number;
	}

	public function register_item( int $steering_id ): int {
		global $wpdb;

		if ( $this->is_registered_item( $steering_id ) ) {
			return 0;
		}

		$next_number = $this->get_next_registration_number();
		if ( $next_number <= 0 ) {
			throw new \RuntimeException( 'steering_register_failed' );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->update(
			'Steering',
			[
				'AppStatus_ID'       => 3,
				'Steering_RegNumber' => $next_number,
			],
			[ 'Steering_ID' => $steering_id ],
			[ '%d', '%d' ],
			[ '%d' ]
		);

		if ( false === $result || ( 0 === (int) $result && ! $this->is_registered_item( $steering_id ) ) ) {
			throw new \RuntimeException( 'steering_register_failed' );
		}

		return $next_number;
	}

	public function update_item_status( int $steering_id, int $status_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->update(
			'Steering',
			[ 'AppStatus_ID' => $status_id ],
			[ 'Steering_ID' => $steering_id ],
			[ '%d' ],
			[ '%d' ]
		);

		return false !== $result;
	}

	public function city_exists( int $city_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT City_ID FROM S_City WHERE City_ID = %d LIMIT 1',
				$city_id
			)
		);

		return null !== $existing_id;
	}

	public function get_city_name( int $city_id ): string {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$city_name = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT City_Name FROM S_City WHERE City_ID = %d LIMIT 1',
				$city_id
			)
		);

		return is_string( $city_name ) ? $city_name : '';
	}

	public function insert_item( array $data ): int {
		global $wpdb;

		$city_id   = (int) $data['city_id'];
		$city_name = $this->get_city_name( $city_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->insert(
			'Steering',
			[
				'Steering_DateCreate'   => current_time( 'mysql' ),
				'AppStatus_ID'          => 1,
				'User_ID'               => (int) $data['user_id'],
				'City_ID'               => $city_id,
				'Steering_TypeApp'      => (int) $data['type_app'],
				'Steering_SurName'      => (string) $data['surname_ukr'],
				'Steering_Name'         => (string) $data['name_ukr'],
				'Steering_Partronymic'  => (string) $data['patronymic_ukr'],
				'Steering_SurNameEng'   => (string) $data['surname_eng'],
				'Steering_NameEng'      => (string) $data['name_eng'],
				'Steering_BirthDate'    => (string) $data['birth_date'],
				'Steering_CityNP'       => $city_name,
				'Steering_NumberNP'     => (string) $data['number_np'],
				'Steering_Url'          => (string) ( $data['url'] ?? '' ),
			],
			[ '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);

		if ( false === $result ) {
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	public function update_item( int $steering_id, array $data ): bool {
		global $wpdb;

		$city_id   = (int) $data['city_id'];
		$city_name = $this->get_city_name( $city_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->update(
			'Steering',
			[
				'City_ID'              => $city_id,
				'Steering_TypeApp'     => (int) $data['type_app'],
				'Steering_SurName'     => (string) $data['surname_ukr'],
				'Steering_Name'        => (string) $data['name_ukr'],
				'Steering_Partronymic' => (string) $data['patronymic_ukr'],
				'Steering_SurNameEng'  => (string) $data['surname_eng'],
				'Steering_NameEng'     => (string) $data['name_eng'],
				'Steering_BirthDate'   => (string) $data['birth_date'],
				'Steering_CityNP'      => $city_name,
				'Steering_NumberNP'    => (string) $data['number_np'],
				'Steering_Url'         => (string) ( $data['url'] ?? '' ),
			],
			[ 'Steering_ID' => $steering_id ],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ],
			[ '%d' ]
		);

		return false !== $result;
	}

	/**
	 * @return array<int,string>
	 */
	public function get_delete_blockers( int $steering_id ): array {
		$blockers = [];

		if ( $this->count_verifications( $steering_id ) > 0 ) {
			$blockers[] = 'verification';
		}

		if ( $this->is_registered_item( $steering_id ) ) {
			$blockers[] = 'registered';
		}

		return $blockers;
	}

	public function delete_item( int $steering_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->delete(
			'Steering',
			[ 'Steering_ID' => $steering_id ],
			[ '%d' ]
		);

		return false !== $result && (int) $result > 0;
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function get_notification_context( int $steering_id ): ?array {
		global $wpdb;

		$users_table = $wpdb->users;
		$sql = "SELECT vs.Steering_ID, vs.User_ID, vs.FIO, vs.AppStatus_ID, vs.AppStatus_Name,
				vs.Steering_RegNumber, vs.Steering_CityNP, vs.Steering_NumberNP,
				vs.Steering_DateCreate, vs.Steering_DateDelivery, vs.Steering_DatePay,
				vs.Steering_Url,
				u.user_email,
				( SELECT vt.TelegramID
					FROM vUserTelegram vt
					WHERE vt.User_ID = vs.User_ID
					ORDER BY vt.UserTelegram_DateCreate DESC
					LIMIT 1
				) AS TelegramID
			FROM vSteering vs
			LEFT JOIN {$users_table} u ON u.ID = vs.User_ID
			WHERE vs.Steering_ID = %d
			LIMIT 1";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( $sql, $steering_id ), ARRAY_A );

		return is_array( $row ) ? $row : null;
	}

	/**
	 * @param array<string,mixed> $filters Набір фільтрів.
	 * @return array<int,array<string,mixed>>
	 */
	public function get_items( array $filters, int $limit, int $offset, bool $can_view_hidden_expired ): array {
		global $wpdb;

		[ $where_sql, $params ] = $this->build_where_sql( $filters, $can_view_hidden_expired );
		$sql = "SELECT vs.Steering_ID, vs.User_ID, vs.FIO, vs.AppStatus_ID, vs.AppStatus_Name,
				vs.Steering_RegNumber, vs.Steering_DatePay, vs.Steering_DateCreate, vs.Steering_DateDelivery,
				vs.Steering_NumberNP, vs.Steering_CityNP, vs.Steering_BirthDate, vs.Steering_Url,
				vs.Steering_TypeApp, vs.Steering_SurName, vs.Steering_Name, vs.Steering_Partronymic,
				vs.Steering_SurNameEng, vs.Steering_NameEng, vs.Steering_Summa, vs.UserFSTU,
				COALESCE(vs.cnt, 0) AS CntVerification,
				GetUserDuesSail(vs.User_ID, YEAR(CURDATE()) - 1) AS SailPrevYear,
				GetUserDuesSail(vs.User_ID, YEAR(CURDATE())) AS SailCurrentYear,
				GetUserDues(vs.User_ID, YEAR(CURDATE()) - 1) AS FstuPrevYear,
				GetUserDues(vs.User_ID, YEAR(CURDATE())) AS FstuCurrentYear
			FROM vSteering vs
			{$where_sql}
			ORDER BY vs.FIO ASC
			LIMIT %d OFFSET %d";

		$params[] = $limit;
		$params[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$items = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );

		return is_array( $items ) ? $items : [];
	}

	/**
	 * @param array<string,mixed> $filters Набір фільтрів.
	 */
	public function count_items( array $filters, bool $can_view_hidden_expired ): int {
		global $wpdb;

		[ $where_sql, $params ] = $this->build_where_sql( $filters, $can_view_hidden_expired );
		$sql = "SELECT COUNT(*) FROM vSteering vs {$where_sql}";

		if ( empty( $params ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$total = $wpdb->get_var( $sql );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
			$total = $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );
		}

		return (int) $total;
	}

	/**
	 * @param array<string,mixed> $filters Набір фільтрів.
	 * @return array<string,float|int>
	 */
	public function get_footer_totals( array $filters, bool $can_view_hidden_expired ): array {
		global $wpdb;

		[ $where_sql, $params ] = $this->build_where_sql( $filters, $can_view_hidden_expired );
		$sql = "SELECT COUNT(*) AS ItemsCount,
				SUM(GetUserDuesSail(vs.User_ID, YEAR(CURDATE()) - 1)) AS SailPrevYearTotal,
				SUM(GetUserDuesSail(vs.User_ID, YEAR(CURDATE()))) AS SailCurrentYearTotal,
				SUM(GetUserDues(vs.User_ID, YEAR(CURDATE()) - 1)) AS FstuPrevYearTotal,
				SUM(GetUserDues(vs.User_ID, YEAR(CURDATE()))) AS FstuCurrentYearTotal
			FROM vSteering vs
			{$where_sql}";

		if ( empty( $params ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$row = $wpdb->get_row( $sql, ARRAY_A );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
			$row = $wpdb->get_row( $wpdb->prepare( $sql, ...$params ), ARRAY_A );
		}

		if ( ! is_array( $row ) ) {
			return [
				'items_count'         => 0,
				'sail_prev_year'      => 0.0,
				'sail_current_year'   => 0.0,
				'fstu_prev_year'      => 0.0,
				'fstu_current_year'   => 0.0,
			];
		}

		return [
			'items_count'       => (int) ( $row['ItemsCount'] ?? 0 ),
			'sail_prev_year'    => (float) ( $row['SailPrevYearTotal'] ?? 0 ),
			'sail_current_year' => (float) ( $row['SailCurrentYearTotal'] ?? 0 ),
			'fstu_prev_year'    => (float) ( $row['FstuPrevYearTotal'] ?? 0 ),
			'fstu_current_year' => (float) ( $row['FstuCurrentYearTotal'] ?? 0 ),
		];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function get_protocol_items( string $search, int $limit, int $offset, string $log_name ): array {
		global $wpdb;

		$where_sql = 'WHERE l.Logs_Name = %s';
		$params    = [ $log_name ];

		$search = trim( $search );
		if ( '' !== $search ) {
			$search_like = '%' . $wpdb->esc_like( $search ) . '%';
			$where_sql  .= ' AND ( l.Logs_Text LIKE %s OR u.FIO LIKE %s )';
			$params[]    = $search_like;
			$params[]    = $search_like;
		}

		$sql = "SELECT l.Logs_DateCreate, l.Logs_Type, l.Logs_Name, l.Logs_Text, l.Logs_Error, u.FIO
			FROM Logs l
			LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
			{$where_sql}
			ORDER BY l.Logs_DateCreate DESC
			LIMIT %d OFFSET %d";

		$params[] = $limit;
		$params[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$items = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );

		return is_array( $items ) ? $items : [];
	}

	public function count_protocol_items( string $search, string $log_name ): int {
		global $wpdb;

		$where_sql = 'WHERE l.Logs_Name = %s';
		$params    = [ $log_name ];

		$search = trim( $search );
		if ( '' !== $search ) {
			$search_like = '%' . $wpdb->esc_like( $search ) . '%';
			$where_sql  .= ' AND ( l.Logs_Text LIKE %s OR u.FIO LIKE %s )';
			$params[]    = $search_like;
			$params[]    = $search_like;
		}

		$sql = "SELECT COUNT(*)
			FROM Logs l
			LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
			{$where_sql}";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$total = $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );

		return (int) $total;
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function get_item_by_id( int $steering_id, bool $can_view_hidden_expired ): ?array {
		global $wpdb;

		$filters = [ 'search' => '', 'dues_filter' => 'all' ];
		[ $where_sql, $params ] = $this->build_where_sql( $filters, $can_view_hidden_expired );
		$where_sql .= '' === $where_sql ? ' WHERE vs.Steering_ID = %d' : ' AND vs.Steering_ID = %d';
		$params[] = $steering_id;

		$sql = $this->get_single_select_sql() . "
			{$where_sql}
			LIMIT 1";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$item = $wpdb->get_row( $wpdb->prepare( $sql, ...$params ), ARRAY_A );

		return is_array( $item ) ? $item : null;
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function get_item_raw_by_id( int $steering_id ): ?array {
		global $wpdb;

		$sql = $this->get_single_select_sql() . ' WHERE vs.Steering_ID = %d LIMIT 1';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$item = $wpdb->get_row( $wpdb->prepare( $sql, $steering_id ), ARRAY_A );

		return is_array( $item ) ? $item : null;
	}

	public function build_photo_url( int $user_id ): string {
		if ( $user_id <= 0 ) {
			return '';
		}

		return home_url( '/photo_steering/' . $user_id . '.jpg' );
	}

	private function user_has_skipper_certificate( int $user_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT User_ID FROM vSkipper WHERE User_ID = %d LIMIT 1',
				$user_id
			)
		);

		return null !== $existing_id;
	}

	private function user_has_registered_steering_certificate( int $user_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT User_ID
				FROM vSteering
				WHERE User_ID = %d
				  AND ( ( Steering_RegNumber IS NOT NULL AND Steering_RegNumber <> '' ) OR AppStatus_ID >= 3 )
				LIMIT 1",
				$user_id
			)
		);

		return null !== $existing_id;
	}

	public function is_registered_item( int $steering_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT Steering_ID
				FROM vSteering
				WHERE Steering_ID = %d
				  AND ( ( Steering_RegNumber IS NOT NULL AND Steering_RegNumber <> '' ) OR AppStatus_ID >= 3 )
				LIMIT 1",
				$steering_id
			)
		);

		return null !== $existing_id;
	}

	private function get_single_select_sql(): string {
		return "SELECT vs.Steering_ID, vs.User_ID, vs.City_ID, vs.FIO, vs.AppStatus_ID, vs.AppStatus_Name,
				vs.Steering_RegNumber, vs.Steering_DatePay, vs.Steering_DateCreate, vs.Steering_DateDelivery,
				vs.Steering_NumberNP, vs.Steering_CityNP, vs.Steering_BirthDate, vs.Steering_Url,
				vs.Steering_TypeApp, vs.Steering_SurName, vs.Steering_Name, vs.Steering_Partronymic,
				vs.Steering_SurNameEng, vs.Steering_NameEng, vs.Steering_Summa, vs.UserFSTU,
				COALESCE(vs.cnt, 0) AS CntVerification,
				GetUserDuesSail(vs.User_ID, YEAR(CURDATE()) - 1) AS SailPrevYear,
				GetUserDuesSail(vs.User_ID, YEAR(CURDATE())) AS SailCurrentYear,
				GetUserDues(vs.User_ID, YEAR(CURDATE()) - 1) AS FstuPrevYear,
				GetUserDues(vs.User_ID, YEAR(CURDATE())) AS FstuCurrentYear
			FROM vSteering vs";
	}

	/**
	 * @param array<string,mixed> $filters Набір фільтрів.
	 * @return array{0:string,1:array<int,string|int>}
	 */
	private function build_where_sql( array $filters, bool $can_view_hidden_expired ): array {
		global $wpdb;

		$where  = [];
		$params = [];

		$search     = trim( (string) ( $filters['search'] ?? '' ) );
		$dues_filter = trim( (string) ( $filters['dues_filter'] ?? 'all' ) );
		$status_id  = (int) ( $filters['status_id'] ?? 0 );

		$has_sail_any = '( GetUserDuesSail(vs.User_ID, YEAR(CURDATE()) - 1) > 0 OR GetUserDuesSail(vs.User_ID, YEAR(CURDATE())) > 0 )';
		$has_fstu_any = '( GetUserDues(vs.User_ID, YEAR(CURDATE()) - 1) > 0 OR GetUserDues(vs.User_ID, YEAR(CURDATE())) > 0 )';

		if ( ! $can_view_hidden_expired ) {
			$where[] = 'vs.AppStatus_ID > 2';
			$where[] = $has_sail_any;
			$where[] = $has_fstu_any;
		}

		if ( '' !== $search ) {
			$search_like = '%' . $wpdb->esc_like( $search ) . '%';
			$where[]     = '( vs.FIO LIKE %s OR CAST(vs.Steering_RegNumber AS CHAR) LIKE %s )';
			$params[]    = $search_like;
			$params[]    = $search_like;
		}

		if ( $can_view_hidden_expired ) {
			if ( $status_id > 0 ) {
				$where[]  = 'vs.AppStatus_ID = %d';
				$params[] = $status_id;
			}

			switch ( $dues_filter ) {
				case 'full_paid':
					$where[] = $has_sail_any;
					$where[] = $has_fstu_any;
					break;
				case 'fstu_paid':
					$where[] = $has_fstu_any;
					break;
				case 'sail_paid':
					$where[] = $has_sail_any;
					break;
			}
		}

		$where_sql = ! empty( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '';

		return [ $where_sql, $params ];
	}
}

