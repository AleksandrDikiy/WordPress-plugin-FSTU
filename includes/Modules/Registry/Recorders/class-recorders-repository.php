<?php
namespace FSTU\Modules\Registry\Recorders;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repository модуля «Реєстратори».
 * Містить усі SQL-запити до legacy-таблиць і views.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-11
 *
 * @package FSTU\Modules\Registry\Recorders
 */
class Recorders_Repository {

	/**
	 * @param array<string,mixed> $filters
	 * @return array<int,array<string,mixed>>
	 */
	public function get_items( array $filters, int $limit, int $offset ): array {
		global $wpdb;

		[ $where_sql, $params ] = $this->build_list_where( $filters );

		$sql = "SELECT ur.UserRegion_ID, ur.User_ID, ur.Unit_ID, ur.Region_ID,
				sr.Region_Name, su.Unit_ShortName, vu.FIO, vu.FIOshort, vu.user_email
			FROM UserRegion ur
			LEFT JOIN S_Region sr ON sr.Region_ID = ur.Region_ID
			LEFT JOIN S_Unit su ON su.Unit_ID = ur.Unit_ID
			LEFT JOIN vUserFSTU vu ON vu.User_ID = ur.User_ID
			{$where_sql}
			ORDER BY sr.Region_Name ASC, vu.FIO ASC
			LIMIT %d OFFSET %d";

		$params[] = $limit;
		$params[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$items = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );

		return is_array( $items ) ? $items : [];
	}

	/**
	 * @param array<string,mixed> $filters
	 */
	public function count_items( array $filters ): int {
		global $wpdb;

		[ $where_sql, $params ] = $this->build_list_where( $filters );
		$sql = "SELECT COUNT(*)
			FROM UserRegion ur
			LEFT JOIN S_Region sr ON sr.Region_ID = ur.Region_ID
			LEFT JOIN S_Unit su ON su.Unit_ID = ur.Unit_ID
			LEFT JOIN vUserFSTU vu ON vu.User_ID = ur.User_ID
			{$where_sql}";

		if ( ! empty( $params ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
			$total = $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$total = $wpdb->get_var( $sql );
		}

		return (int) $total;
	}

	public function get_single_by_id( int $user_region_id ): ?array {
		global $wpdb;

		$sql = "SELECT ur.UserRegion_ID, ur.User_ID, ur.Unit_ID, ur.Region_ID, ur.UserRegion_DateCreate,
				sr.Region_Name, su.Unit_ShortName, vu.FIO, vu.FIOshort, vu.user_email
			FROM UserRegion ur
			LEFT JOIN S_Region sr ON sr.Region_ID = ur.Region_ID
			LEFT JOIN S_Unit su ON su.Unit_ID = ur.Unit_ID
			LEFT JOIN vUserFSTU vu ON vu.User_ID = ur.User_ID
			WHERE ur.UserRegion_ID = %d
			LIMIT 1";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$item = $wpdb->get_row( $wpdb->prepare( $sql, $user_region_id ), ARRAY_A );

		return is_array( $item ) ? $item : null;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function get_unit_options(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$items = $wpdb->get_results(
			'SELECT Unit_ID, Region_ID, Unit_ShortName FROM S_Unit ORDER BY Unit_ShortName ASC',
			ARRAY_A
		);

		return is_array( $items ) ? $items : [];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function search_candidates( string $search, int $limit ): array {
		global $wpdb;

		$search = trim( $search );
		$sql    = 'SELECT User_ID, FIO, user_email FROM vUserFSTU';
		$params = [];

		if ( '' !== $search ) {
			$sql      .= ' WHERE FIO LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $search ) . '%';
		}

		$sql     .= ' ORDER BY FIO ASC LIMIT %d';
		$params[] = $limit;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$items = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );

		return is_array( $items ) ? $items : [];
	}

	public function get_candidate_by_user_id( int $user_id ): ?array {
		global $wpdb;

		$sql = 'SELECT User_ID, FIO, user_email FROM vUserFSTU WHERE User_ID = %d LIMIT 1';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$item = $wpdb->get_row( $wpdb->prepare( $sql, $user_id ), ARRAY_A );

		return is_array( $item ) ? $item : null;
	}

	public function get_region_id_by_unit_id( int $unit_id ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$region_id = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT Region_ID FROM S_Unit WHERE Unit_ID = %d LIMIT 1',
				$unit_id
			)
		);

		return (int) $region_id;
	}

	public function count_relations_by_user_id( int $user_id, int $exclude_relation_id = 0 ): int {
		global $wpdb;

		$sql    = 'SELECT COUNT(*) FROM UserRegion WHERE User_ID = %d';
		$params = [ $user_id ];

		if ( $exclude_relation_id > 0 ) {
			$sql      .= ' AND UserRegion_ID <> %d';
			$params[] = $exclude_relation_id;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$total = $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );

		return (int) $total;
	}

	public function relation_exists( int $unit_id, int $user_id, int $exclude_id = 0 ): bool {
		global $wpdb;

		$sql    = 'SELECT UserRegion_ID FROM UserRegion WHERE Unit_ID = %d AND User_ID = %d';
		$params = [ $unit_id, $user_id ];

		if ( $exclude_id > 0 ) {
			$sql      .= ' AND UserRegion_ID <> %d';
			$params[] = $exclude_id;
		}

		$sql .= ' LIMIT 1';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$existing_id = $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );

		return null !== $existing_id;
	}

	public function create_relation( array $data ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->insert(
			'UserRegion',
			[
				'UserRegion_DateCreate' => current_time( 'mysql' ),
				'Region_ID'            => (int) $data['region_id'],
				'Unit_ID'              => (int) $data['unit_id'],
				'User_ID'              => (int) $data['user_id'],
			],
			[ '%s', '%d', '%d', '%d' ]
		);

		if ( false === $result ) {
			throw new \RuntimeException( 'recorders_insert_failed' );
		}

		return (int) $wpdb->insert_id;
	}

	public function update_relation( int $user_region_id, array $data ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->update(
			'UserRegion',
			[
				'Region_ID' => (int) $data['region_id'],
				'Unit_ID'   => (int) $data['unit_id'],
				'User_ID'   => (int) $data['user_id'],
			],
			[ 'UserRegion_ID' => $user_region_id ],
			[ '%d', '%d', '%d' ],
			[ '%d' ]
		);

		if ( false === $result ) {
			throw new \RuntimeException( 'recorders_update_failed' );
		}

		return true;
	}

	public function delete_relation( int $user_region_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->delete( 'UserRegion', [ 'UserRegion_ID' => $user_region_id ], [ '%d' ] );

		if ( false === $result ) {
			throw new \RuntimeException( 'recorders_delete_failed' );
		}

		return true;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function get_protocol_items( string $search, int $limit, int $offset ): array {
		global $wpdb;

		[ $where_sql, $params ] = $this->build_protocol_where( $search );
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

	public function count_protocol_items( string $search ): int {
		global $wpdb;

		[ $where_sql, $params ] = $this->build_protocol_where( $search );
		$sql = "SELECT COUNT(*)
			FROM Logs l
			LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
			{$where_sql}";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$total = $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );

		return (int) $total;
	}

	/**
	 * @param array<string,mixed> $filters
	 * @return array{0:string,1:array<int,mixed>}
	 */
	private function build_list_where( array $filters ): array {
		global $wpdb;

		$where = [ '1=1' ];
		$params = [];

		$unit_id = isset( $filters['unit_id'] ) ? (int) $filters['unit_id'] : 0;
		if ( $unit_id > 0 ) {
			$where[]  = 'ur.Unit_ID = %d';
			$params[] = $unit_id;
		}

		$search = isset( $filters['search'] ) ? trim( (string) $filters['search'] ) : '';
		if ( '' !== $search ) {
			$where[]  = 'vu.FIO LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $search ) . '%';
		}

		return [ 'WHERE ' . implode( ' AND ', $where ), $params ];
	}

	/**
	 * @return array{0:string,1:array<int,mixed>}
	 */
	private function build_protocol_where( string $search ): array {
		global $wpdb;

		$where  = [ 'l.Logs_Name = %s' ];
		$params = [ Recorders_Protocol_Service::LOG_NAME ];
		$search = trim( $search );

		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where[]  = '(l.Logs_Text LIKE %s OR u.FIO LIKE %s)';
			$params[] = $like;
			$params[] = $like;
		}

		return [ 'WHERE ' . implode( ' AND ', $where ), $params ];
	}
}

