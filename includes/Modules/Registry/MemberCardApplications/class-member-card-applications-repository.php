<?php
namespace FSTU\Modules\Registry\MemberCardApplications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repository модуля «Посвідчення членів ФСТУ».
 * Відповідає лише за доступ до даних і SQL-запити.
 *
 * Version:     1.2.0
 * Date_update: 2026-04-10
 *
 * @package FSTU\Modules\UserFstu\MemberCardApplications
 */
class Member_Card_Applications_Repository {

	private const MAX_FALLBACK_USER_IDS = 100;

	/**
	 * @return array<string,mixed>|null
	 */
	public function get_latest_member_card_by_user_id( int $user_id ): ?array {
		global $wpdb;

		$sql = "SELECT *
			FROM UserMemberCard
			WHERE User_ID = %d
			ORDER BY UserMemberCard_DateCreate DESC, UserMemberCard_ID DESC
			LIMIT 1";

		$row = $wpdb->get_row( $wpdb->prepare( $sql, $user_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared

		return is_array( $row ) ? $row : null;
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function get_raw_member_card_by_id( int $member_card_id ): ?array {
		global $wpdb;

		$sql = "SELECT *
			FROM UserMemberCard
			WHERE UserMemberCard_ID = %d
			LIMIT 1";

		$row = $wpdb->get_row( $wpdb->prepare( $sql, $member_card_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared

		return is_array( $row ) ? $row : null;
	}

	public function get_next_member_card_number( int $region_id ): int {
		global $wpdb;

		return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT COALESCE(MAX(UserMemberCard_Number), 0) + 1 FROM UserMemberCard WHERE Region_ID = %d',
				$region_id
			)
		);
	}

	/**
	 * @param array<string,mixed> $data
	 */
	public function create_member_card( array $data ): int {
		global $wpdb;

		$inserted = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			'UserMemberCard',
			[
				'UserMemberCard_DateCreate'    => (string) $data['UserMemberCard_DateCreate'],
				'UserCreate'                   => (int) $data['UserCreate'],
				'User_ID'                      => (int) $data['User_ID'],
				'Region_ID'                    => (int) $data['Region_ID'],
				'StatusCard_ID'                => (int) $data['StatusCard_ID'],
				'TypeCard_ID'                  => (int) $data['TypeCard_ID'],
				'UserMemberCard_Number'        => (int) $data['UserMemberCard_Number'],
				'UserMemberCard_LastName'      => (string) $data['UserMemberCard_LastName'],
				'UserMemberCard_FirstName'     => (string) $data['UserMemberCard_FirstName'],
				'UserMemberCard_Patronymic'    => (string) $data['UserMemberCard_Patronymic'],
				'UserMemberCard_LastNameEng'   => (string) $data['UserMemberCard_LastNameEng'],
				'UserMemberCard_FirstNameEng'  => (string) $data['UserMemberCard_FirstNameEng'],
				'UserMemberCard_Summa'         => (float) $data['UserMemberCard_Summa'],
				'UserMemberCard_NumberNP'      => (string) $data['UserMemberCard_NumberNP'],
			],
			[ '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%s' ]
		);

		if ( false === $inserted ) {
			throw new \RuntimeException( 'member_card_insert_failed' );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * @param array<string,mixed> $data
	 */
	public function update_member_card( int $member_card_id, array $data ): bool {
		global $wpdb;

		$update_data = [
			'UserMemberCard_LastName'     => (string) $data['UserMemberCard_LastName'],
			'UserMemberCard_FirstName'    => (string) $data['UserMemberCard_FirstName'],
			'UserMemberCard_Patronymic'   => (string) $data['UserMemberCard_Patronymic'],
			'UserMemberCard_LastNameEng'  => (string) $data['UserMemberCard_LastNameEng'],
			'UserMemberCard_FirstNameEng' => (string) $data['UserMemberCard_FirstNameEng'],
			'Region_ID'                   => (int) $data['Region_ID'],
			'StatusCard_ID'               => (int) $data['StatusCard_ID'],
			'TypeCard_ID'                 => (int) $data['TypeCard_ID'],
			'UserMemberCard_Summa'        => (float) $data['UserMemberCard_Summa'],
			'UserMemberCard_NumberNP'     => (string) $data['UserMemberCard_NumberNP'],
			'UserMemberCard_DateEdit'     => (string) $data['UserMemberCard_DateEdit'],
		];

		$update_format = [ '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%f', '%s', '%s' ];

		if ( array_key_exists( 'UserMemberCard_Number', $data ) ) {
			$update_data['UserMemberCard_Number'] = (int) $data['UserMemberCard_Number'];
			$update_format[] = '%d';
		}

		$result = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			'UserMemberCard',
			$update_data,
			[ 'UserMemberCard_ID' => $member_card_id ],
			$update_format,
			[ '%d' ]
		);

		if ( false === $result ) {
			throw new \RuntimeException( 'member_card_update_failed' );
		}

		return true;
	}

	public function delete_member_card( int $member_card_id ): bool {
		global $wpdb;

		$result = $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			'UserMemberCard',
			[ 'UserMemberCard_ID' => $member_card_id ],
			[ '%d' ]
		);

		if ( false === $result ) {
			throw new \RuntimeException( 'member_card_delete_failed' );
		}

		return $result > 0;
	}

	/**
	 * @return array<string,array<int,array<string,mixed>>>
	 */
	public function get_filter_options(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$regions = $wpdb->get_results( 'SELECT Region_ID, Region_Name FROM S_Region ORDER BY Region_Name ASC', ARRAY_A ) ?: [];
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$statuses = $wpdb->get_results( 'SELECT StatusCard_ID, StatusCard_Name FROM S_StatusCard ORDER BY StatusCard_Order ASC, StatusCard_Name ASC', ARRAY_A ) ?: [];
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$types = $wpdb->get_results( 'SELECT TypeCard_ID, TypeCard_Name FROM S_TypeCard ORDER BY TypeCard_Order ASC, TypeCard_Name ASC', ARRAY_A ) ?: [];

		return [
			'regions'  => $regions,
			'statuses' => $statuses,
			'types'    => $types,
		];
	}

	/**
	 * @param array<string,mixed> $args
	 * @return array{rows: array<int,array<string,mixed>>, total: int}
	 */
	public function get_list( array $args ): array {
		global $wpdb;

		$search    = isset( $args['search'] ) ? trim( (string) $args['search'] ) : '';
		$region_id = isset( $args['region_id'] ) ? absint( $args['region_id'] ) : 0;
		$status_id = isset( $args['status_id'] ) ? absint( $args['status_id'] ) : 0;
		$type_id   = isset( $args['type_id'] ) ? absint( $args['type_id'] ) : 0;
		$per_page  = max( 1, absint( $args['per_page'] ?? 10 ) );
		$offset    = max( 0, absint( $args['offset'] ?? 0 ) );
		$user_ids  = isset( $args['user_ids'] ) && is_array( $args['user_ids'] ) ? array_values( array_filter( array_map( 'absint', $args['user_ids'] ) ) ) : [];

		$fio_expression = "TRIM(CONCAT_WS(' ', NULLIF(mc.UserMemberCard_LastName, ''), NULLIF(mc.UserMemberCard_FirstName, ''), NULLIF(mc.UserMemberCard_Patronymic, '')))";
		$card_number_expression = "CASE WHEN COALESCE(r.Region_Code, '') <> '' THEN CONCAT(r.Region_Code, '-', mc.UserMemberCard_Number) ELSE CAST(mc.UserMemberCard_Number AS CHAR) END";

		$where_sql = ' WHERE mc.UserMemberCard_ID > 0';
		$params    = [];

		if ( '' !== $search ) {
			$like       = '%' . $wpdb->esc_like( $search ) . '%';
			$where_sql .= " AND ({$fio_expression} LIKE %s OR {$card_number_expression} LIKE %s OR CAST(mc.UserMemberCard_Number AS CHAR) LIKE %s)";
			$params[]   = $like;
			$params[]   = $like;
			$params[]   = $like;
		}

		if ( $region_id > 0 ) {
			$where_sql .= ' AND mc.Region_ID = %d';
			$params[]   = $region_id;
		}

		if ( $status_id > 0 ) {
			$where_sql .= ' AND mc.StatusCard_ID = %d';
			$params[]   = $status_id;
		}

		if ( $type_id > 0 ) {
			$where_sql .= ' AND mc.TypeCard_ID = %d';
			$params[]   = $type_id;
		}

		if ( ! empty( $user_ids ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $user_ids ), '%d' ) );
			$where_sql   .= " AND mc.User_ID IN ({$placeholders})";
			$params       = array_merge( $params, $user_ids );
		}

		$count_sql = "SELECT COUNT(*)
			FROM UserMemberCard mc
			LEFT JOIN S_Region r ON r.Region_ID = mc.Region_ID
			{$where_sql}";

		$list_sql = "SELECT
				mc.UserMemberCard_ID,
				mc.User_ID,
				mc.UserMemberCard_Number,
				mc.UserMemberCard_DateCreate,
				mc.UserMemberCard_Summa,
				mc.UserMemberCard_LastName,
				mc.UserMemberCard_FirstName,
				mc.UserMemberCard_Patronymic,
				mc.Region_ID,
				r.Region_Name,
				{$card_number_expression} AS CardNumber,
				mc.StatusCard_ID,
				ss.StatusCard_Name,
				mc.TypeCard_ID,
				tc.TypeCard_Name,
				CASE WHEN {$fio_expression} <> '' THEN {$fio_expression} ELSE CONCAT('ID ', mc.User_ID) END AS FIO
			FROM UserMemberCard mc
			LEFT JOIN S_Region r ON r.Region_ID = mc.Region_ID
			LEFT JOIN S_StatusCard ss ON ss.StatusCard_ID = mc.StatusCard_ID
			LEFT JOIN S_TypeCard tc ON tc.TypeCard_ID = mc.TypeCard_ID
			{$where_sql}
			ORDER BY mc.UserMemberCard_DateCreate DESC, mc.UserMemberCard_ID DESC
			LIMIT %d OFFSET %d";

		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$list_params = array_merge( $params, [ $per_page, $offset ] );
		$rows = $wpdb->get_results( $wpdb->prepare( $list_sql, ...$list_params ), ARRAY_A ) ?: []; // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared

		return [
			'rows'  => $rows,
			'total' => $total,
		];
	}

	/**
	 * @return array<int,int>
	 */
	public function search_user_ids_by_meta( string $search ): array {
		global $wpdb;

		$search = trim( $search );
		if ( '' === $search ) {
			return [];
		}

		$like = '%' . $wpdb->esc_like( $search ) . '%';
		$meta_table = $wpdb->usermeta;
		$users_table = $wpdb->users;

		$sql = "SELECT DISTINCT u.ID
			FROM {$users_table} u
			LEFT JOIN (
				SELECT
					user_id,
					MAX(CASE WHEN meta_key = 'last_name' THEN meta_value END) AS last_name,
					MAX(CASE WHEN meta_key = 'first_name' THEN meta_value END) AS first_name,
					MAX(CASE WHEN meta_key = 'Patronymic' THEN meta_value END) AS patronymic
				FROM {$meta_table}
				WHERE meta_key IN ('last_name', 'first_name', 'Patronymic')
				GROUP BY user_id
			) names ON names.user_id = u.ID
			WHERE (
				CONCAT_WS(' ', NULLIF(names.last_name, ''), NULLIF(names.first_name, ''), NULLIF(names.patronymic, '')) LIKE %s
				OR u.display_name LIKE %s
				OR u.user_email LIKE %s
				OR u.user_login LIKE %s
			)
			LIMIT %d";

		$params = [ $like, $like, $like, $like, self::MAX_FALLBACK_USER_IDS ];
		$user_ids = $wpdb->get_col( $wpdb->prepare( $sql, ...$params ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared

		return array_values( array_filter( array_map( 'absint', is_array( $user_ids ) ? $user_ids : [] ) ) );
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function get_single_by_id( int $member_card_id ): ?array {
		global $wpdb;

		$fio_expression = "TRIM(CONCAT_WS(' ', NULLIF(mc.UserMemberCard_LastName, ''), NULLIF(mc.UserMemberCard_FirstName, ''), NULLIF(mc.UserMemberCard_Patronymic, '')))";
		$card_number_expression = "CASE WHEN COALESCE(r.Region_Code, '') <> '' THEN CONCAT(r.Region_Code, '-', mc.UserMemberCard_Number) ELSE CAST(mc.UserMemberCard_Number AS CHAR) END";

		$sql = "SELECT
				mc.UserMemberCard_ID,
				mc.User_ID,
				{$card_number_expression} AS CardNumber,
				mc.UserMemberCard_Number,
				mc.UserMemberCard_DateCreate,
				mc.UserMemberCard_DateEdit,
				mc.UserMemberCard_Summa,
				mc.UserMemberCard_NumberNP,
				mc.UserMemberCard_LastName,
				mc.UserMemberCard_FirstName,
				mc.UserMemberCard_Patronymic,
				mc.UserMemberCard_LastNameEng,
				mc.UserMemberCard_FirstNameEng,
				mc.Region_ID,
				r.Region_Name,
				mc.StatusCard_ID,
				ss.StatusCard_Name,
				mc.TypeCard_ID,
				tc.TypeCard_Name,
				CASE WHEN {$fio_expression} <> '' THEN {$fio_expression} ELSE CONCAT('ID ', mc.User_ID) END AS FIO
			FROM UserMemberCard mc
			LEFT JOIN S_Region r ON r.Region_ID = mc.Region_ID
			LEFT JOIN S_StatusCard ss ON ss.StatusCard_ID = mc.StatusCard_ID
			LEFT JOIN S_TypeCard tc ON tc.TypeCard_ID = mc.TypeCard_ID
			WHERE mc.UserMemberCard_ID = %d
			LIMIT 1";

		$row = $wpdb->get_row( $wpdb->prepare( $sql, $member_card_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared

		return is_array( $row ) ? $row : null;
	}

	/**
	 * @return array{rows: array<int,array<string,mixed>>, total: int}
	 */
	public function get_protocol( string $search, int $per_page, int $offset ): array {
		global $wpdb;

		$count_where  = 'WHERE l.Logs_Name = %s';
		$list_where   = 'WHERE l.Logs_Name = %s';
		$count_params = [ Member_Card_Applications_Protocol_Service::LOG_NAME ];
		$list_params  = [ Member_Card_Applications_Protocol_Service::LOG_NAME ];

		if ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$count_where .= ' AND (l.Logs_Text LIKE %s OR u.FIO LIKE %s)';
			$list_where  .= ' AND (l.Logs_Text LIKE %s OR u.FIO LIKE %s)';
			$count_params[] = $like;
			$count_params[] = $like;
			$list_params[] = $like;
			$list_params[] = $like;
		}

		$count_from_sql = '' !== $search
			? 'FROM Logs l LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID'
			: 'FROM Logs l';

		$count_sql = "SELECT COUNT(*) {$count_from_sql} {$count_where}";
		$total     = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$count_params ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared

		$list_sql = "SELECT l.User_ID, l.Logs_DateCreate, l.Logs_Type, l.Logs_Name, l.Logs_Text, l.Logs_Error, u.FIO
			FROM Logs l
			LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
			{$list_where}
			ORDER BY l.Logs_DateCreate DESC
			LIMIT %d OFFSET %d";

		$list_params = array_merge( $list_params, [ $per_page, $offset ] );
		$rows = $wpdb->get_results( $wpdb->prepare( $list_sql, ...$list_params ), ARRAY_A ) ?: []; // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared

		return [
			'rows'  => $rows,
			'total' => $total,
		];
	}
}

