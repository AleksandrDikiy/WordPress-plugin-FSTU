<?php
namespace FSTU\Modules\Registry\MKK;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repository модуля «Реєстр членів МКК ФСТУ».
 * Містить усі SQL-запити до legacy-таблиць та views модуля.
 *
 * Version:     1.1.2
 * Date_update: 2026-04-12
 *
 * @package FSTU\Modules\UserFstu\MKK
 */
class MKK_Repository {

	/**
	 * @param array<string,mixed> $filters
	 * @return array<int,array<string,mixed>>
	 */
	public function get_items( array $filters, int $limit, int $offset ): array {
		global $wpdb;

		[ $where_sql, $params ] = $this->build_list_where( $filters );
		$select                 = $this->get_vmkk_select_sql();

		$sql = "SELECT {$select}
			FROM vmkk m
			LEFT JOIN vUserFSTU vu ON vu.User_ID = m.User_ID
			{$where_sql}
			ORDER BY m.MemberRegional_ID ASC,
				COALESCE(NULLIF(m.surname, ''), NULLIF(m.FIO, ''), NULLIF(vu.FIO, ''), '') ASC,
				COALESCE(NULLIF(m.FIO, ''), NULLIF(vu.FIO, ''), NULLIF(m.FIOshort, ''), NULLIF(vu.FIOshort, ''), '') ASC,
				m.mkk_ID ASC
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
		$sql                    = "SELECT COUNT(*)
			FROM vmkk m
			LEFT JOIN vUserFSTU vu ON vu.User_ID = m.User_ID
			{$where_sql}";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$total = $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );

		return (int) $total;
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function get_single_by_id( int $mkk_id ): ?array {
		global $wpdb;

		$select = $this->get_vmkk_select_sql() . ',
			creator.FIO AS UserCreate_FIO';

		$sql = "SELECT {$select}
			FROM vmkk m
			LEFT JOIN vUserFSTU vu ON vu.User_ID = m.User_ID
			LEFT JOIN vUserFSTU creator ON creator.User_ID = m.UserCreate
			WHERE m.mkk_ID = %d
			LIMIT 1";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$item = $wpdb->get_row( $wpdb->prepare( $sql, $mkk_id ), ARRAY_A );

		return is_array( $item ) ? $item : null;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function get_region_options(): array {
		global $wpdb;

		$sql = 'SELECT Region_ID, Region_Name FROM vRegion WHERE Region_ID >= %d ORDER BY Region_Code ASC, Region_Name ASC';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$items = $wpdb->get_results( $wpdb->prepare( $sql, 0 ), ARRAY_A );

		return is_array( $items ) ? $items : [];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function get_commission_type_options(): array {
		global $wpdb;

		$sql = 'SELECT CommissionType_ID, CommissionType_Name FROM vCommissionType WHERE CommissionType_ID >= %d ORDER BY CommissionType_Order ASC, CommissionType_Name ASC';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$items = $wpdb->get_results( $wpdb->prepare( $sql, 0 ), ARRAY_A );

		return is_array( $items ) ? $items : [];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function get_tourism_type_options(): array {
		global $wpdb;

		$sql = 'SELECT TourismType_ID, TourismType_Name FROM vTourismType WHERE TourismType_ID >= %d ORDER BY TourismType_Order ASC, TourismType_Name ASC';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$items = $wpdb->get_results( $wpdb->prepare( $sql, 0 ), ARRAY_A );

		return is_array( $items ) ? $items : [];
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function get_region_by_id( int $region_id ): ?array {
		global $wpdb;

		$sql = 'SELECT Region_ID, Region_Name FROM vRegion WHERE Region_ID = %d LIMIT 1';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$item = $wpdb->get_row( $wpdb->prepare( $sql, $region_id ), ARRAY_A );

		return is_array( $item ) ? $item : null;
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function get_commission_type_by_id( int $commission_type_id ): ?array {
		global $wpdb;

		$sql = 'SELECT CommissionType_ID, CommissionType_Name FROM vCommissionType WHERE CommissionType_ID = %d LIMIT 1';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$item = $wpdb->get_row( $wpdb->prepare( $sql, $commission_type_id ), ARRAY_A );

		return is_array( $item ) ? $item : null;
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function get_tourism_type_by_id( int $tourism_type_id ): ?array {
		global $wpdb;

		$sql = 'SELECT TourismType_ID, TourismType_Name FROM vTourismType WHERE TourismType_ID = %d LIMIT 1';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$item = $wpdb->get_row( $wpdb->prepare( $sql, $tourism_type_id ), ARRAY_A );

		return is_array( $item ) ? $item : null;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function search_users( string $search, int $limit ): array {
		global $wpdb;

		$search = trim( $search );
		$params = [];
		$sql    = 'SELECT User_ID, FIO, user_email FROM vUserFSTU';

		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$sql     .= ' WHERE FIO LIKE %s';
			$params[] = $like;
		}

		$sql     .= ' ORDER BY FIO ASC LIMIT %d';
		$params[] = $limit;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$items = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );

		return is_array( $items ) ? $items : [];
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function get_user_by_id( int $user_id ): ?array {
		global $wpdb;

		$sql = 'SELECT User_ID, FIO, user_email FROM vUserFSTU WHERE User_ID = %d LIMIT 1';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$item = $wpdb->get_row( $wpdb->prepare( $sql, $user_id ), ARRAY_A );

		return is_array( $item ) ? $item : null;
	}

	public function create_item( array $data ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->insert(
			'mkk',
			[
				'mkk_DateCreate'     => current_time( 'mysql' ),
				'mkk_DateBegin'      => current_time( 'mysql' ),
				'User_ID'            => (int) $data['user_id'],
				'UserCreate'         => get_current_user_id(),
				'Region_ID'          => (int) $data['region_id'],
				'TourismType_ID'     => (int) $data['tourism_type_id'],
				'CommissionType_ID'  => (int) $data['commission_type_id'],
			],
			[ '%s', '%s', '%d', '%d', '%d', '%d', '%d' ]
		);

		if ( false === $result ) {
			throw new \RuntimeException( 'mkk_insert_failed' );
		}

		return (int) $wpdb->insert_id;
	}

	public function update_item( int $mkk_id, array $data ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->update(
			'mkk',
			[
				'User_ID'           => (int) $data['user_id'],
				'Region_ID'         => (int) $data['region_id'],
				'TourismType_ID'    => (int) $data['tourism_type_id'],
				'CommissionType_ID' => (int) $data['commission_type_id'],
			],
			[ 'mkk_ID' => $mkk_id ],
			[ '%d', '%d', '%d', '%d' ],
			[ '%d' ]
		);

		if ( false === $result ) {
			throw new \RuntimeException( 'mkk_update_failed' );
		}

		return true;
	}

	public function delete_item( int $mkk_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->delete( 'mkk', [ 'mkk_ID' => $mkk_id ], [ '%d' ] );

		if ( false === $result ) {
			throw new \RuntimeException( 'mkk_delete_failed' );
		}

		return true;
	}

	/**
	 * @return array<string,mixed>
	 */
	public function check_delete_dependencies( int $mkk_id ): array {
		global $wpdb;

		$dependency_tables = $this->get_known_delete_dependency_tables();
		if ( empty( $dependency_tables ) ) {
			return [
				'can_delete' => true,
				'message'    => '',
				'status'     => '✓',
			];
		}

		$dependencies = [];

		foreach ( $dependency_tables as $dependency ) {
			$table_name = (string) ( $dependency['table'] ?? '' );
			$column_name = (string) ( $dependency['column'] ?? '' );
			$label = (string) ( $dependency['label'] ?? $table_name );

			if ( '' === $table_name || '' === $column_name ) {
				continue;
			}

			if ( ! preg_match( '/^[A-Za-z0-9_]+$/', $table_name ) || ! preg_match( '/^[A-Za-z0-9_]+$/', $column_name ) ) {
				continue;
			}

			$sql = "SELECT COUNT(*) FROM `{$table_name}` WHERE `{$column_name}` = %d";

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$count = (int) $wpdb->get_var( $wpdb->prepare( $sql, $mkk_id ) );

			if ( ! empty( $wpdb->last_error ) ) {
				return [
					'can_delete' => false,
					'message'    => __( 'Неможливо перевірити зв’язки запису. Видалення заблоковано з міркувань безпеки.', 'fstu' ),
					'status'     => 'dependency query error',
				];
			}

			if ( $count > 0 ) {
				$dependencies[] = $label . ': ' . $count;
			}
		}

		if ( ! empty( $dependencies ) ) {
			return [
				'can_delete' => false,
				'message'    => __( 'Запис МКК неможливо видалити, оскільки він використовується в інших даних.', 'fstu' ),
				'status'     => implode( '; ', $dependencies ),
			];
		}

		return [
			'can_delete' => true,
			'message'    => '',
			'status'     => '✓',
		];
	}

	/**
	 * @return array<int,array<string,string>>
	 */
	private function get_known_delete_dependency_tables(): array {
		return [];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function get_protocol_items( string $search, int $limit, int $offset ): array {
		global $wpdb;

		[ $where_sql, $params ] = $this->build_protocol_where( $search );
		$sql                    = "SELECT l.Logs_DateCreate, l.Logs_Type, l.Logs_Name, l.Logs_Text, l.Logs_Error, u.FIO
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
		$sql                    = "SELECT COUNT(*)
			FROM Logs l
			LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
			{$where_sql}";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$total = $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );

		return (int) $total;
	}

	private function get_vmkk_select_sql(): string {
		return "m.mkk_ID,
			m.User_ID,
			m.Region_ID,
			m.CommissionType_ID,
			m.TourismType_ID,
			m.MemberRegional_ID,
			m.mkk_DateBegin,
			m.mkk_DateCreate,
			m.UserCreate,
			m.MemberRegional_Name,
			m.Region_Name,
			m.CommissionType_Name,
			m.TourismType_Name,
			COALESCE(NULLIF(m.FIO, ''), NULLIF(vu.FIO, '')) AS FIO,
			COALESCE(NULLIF(m.FIOshort, ''), NULLIF(vu.FIOshort, '')) AS FIOshort,
			m.surname";
	}

	/**
	 * @param array<string,mixed> $filters
	 * @return array{0:string,1:array<int,mixed>}
	 */
	private function build_list_where( array $filters ): array {
		global $wpdb;

		$where  = [ 'm.mkk_ID > 0' ];
		$params = [];

		$region_id = isset( $filters['region_id'] ) ? (int) $filters['region_id'] : 0;
		if ( $region_id > 0 ) {
			$where[]  = 'm.Region_ID = %d';
			$params[] = $region_id;
		}

		$commission_type_id = isset( $filters['commission_type_id'] ) ? (int) $filters['commission_type_id'] : 0;
		if ( $commission_type_id > 0 ) {
			$where[]  = 'm.CommissionType_ID = %d';
			$params[] = $commission_type_id;
		}

		$tourism_type_id = isset( $filters['tourism_type_id'] ) ? (int) $filters['tourism_type_id'] : 0;
		if ( $tourism_type_id > 0 ) {
			$where[]  = 'm.TourismType_ID = %d';
			$params[] = $tourism_type_id;
		}

		$search = isset( $filters['search'] ) ? trim( (string) $filters['search'] ) : '';
		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where[]  = "(COALESCE(NULLIF(m.FIO, ''), NULLIF(vu.FIO, ''), '') LIKE %s OR COALESCE(NULLIF(m.FIOshort, ''), NULLIF(vu.FIOshort, ''), '') LIKE %s)";
			$params[] = $like;
			$params[] = $like;
		}

		return [ 'WHERE ' . implode( ' AND ', $where ), $params ];
	}

	/**
	 * @return array{0:string,1:array<int,mixed>}
	 */
	private function build_protocol_where( string $search ): array {
		global $wpdb;

		$where  = [ 'l.Logs_Name = %s' ];
		$params = [ MKK_Protocol_Service::LOG_NAME ];
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

