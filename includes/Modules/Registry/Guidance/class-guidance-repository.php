<?php
namespace FSTU\Modules\Registry\Guidance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repository модуля «Склад керівних органів ФСТУ».
 * Містить SQL-запити до Guidance, Logs і пов'язаних views.
 *
 * Version:     1.1.0
 * Date_update: 2026-04-12
 *
 * @package FSTU\Modules\Registry\Guidance
 */
class Guidance_Repository {

	/**
	 * @param array<string,mixed> $filters
	 * @return array<int,array<string,mixed>>
	 */
	public function get_items( array $filters, int $limit, int $offset ): array {
		global $wpdb;

		[ $where_sql, $params ] = $this->build_list_where( $filters );
		$sql                    = $this->get_base_select_sql() . "
			{$where_sql}
			ORDER BY src.TypeGuidance_ID ASC,
				COALESCE(mg.MemberGuidance_Name, '') ASC,
				COALESCE(uf.FIO, '') ASC,
				src.Guidance_ID ASC
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
		$sql                    = "SELECT COUNT(*) " . $this->get_base_from_sql() . " {$where_sql}";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$total = $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );

		return (int) $total;
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function get_single_by_id( int $guidance_id ): ?array {
		global $wpdb;

		$sql = $this->get_base_select_sql() . "
			WHERE src.Guidance_ID = %d
			LIMIT 1";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$item = $wpdb->get_row( $wpdb->prepare( $sql, $guidance_id ), ARRAY_A );

		return is_array( $item ) ? $item : null;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function get_typeguidance_options(): array {
		global $wpdb;

		$sql = 'SELECT TypeGuidance_ID, TypeGuidance_Name FROM vTypeGuidance ORDER BY TypeGuidance_Order ASC, TypeGuidance_Name ASC';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$items = $wpdb->get_results( $sql, ARRAY_A );

		return is_array( $items ) ? $items : [];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function get_member_guidance_options( int $typeguidance_id ): array {
		global $wpdb;

		$sql = 'SELECT MemberGuidance_ID, MemberGuidance_Name FROM vMemberGuidance WHERE TypeGuidance_ID = %d ORDER BY MemberGuidance_Order ASC, MemberGuidance_Name ASC';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$items = $wpdb->get_results( $wpdb->prepare( $sql, $typeguidance_id ), ARRAY_A );

		return is_array( $items ) ? $items : [];
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function get_typeguidance_by_id( int $typeguidance_id ): ?array {
		global $wpdb;

		$sql = 'SELECT TypeGuidance_ID, TypeGuidance_Name FROM vTypeGuidance WHERE TypeGuidance_ID = %d LIMIT 1';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$item = $wpdb->get_row( $wpdb->prepare( $sql, $typeguidance_id ), ARRAY_A );

		return is_array( $item ) ? $item : null;
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function get_member_guidance_by_id( int $member_guidance_id ): ?array {
		global $wpdb;

		$sql = 'SELECT MemberGuidance_ID, MemberGuidance_Name, TypeGuidance_ID FROM vMemberGuidance WHERE MemberGuidance_ID = %d LIMIT 1';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$item = $wpdb->get_row( $wpdb->prepare( $sql, $member_guidance_id ), ARRAY_A );

		return is_array( $item ) ? $item : null;
	}

	public function member_guidance_belongs_to_type( int $member_guidance_id, int $typeguidance_id ): bool {
		global $wpdb;

		$sql = 'SELECT MemberGuidance_ID FROM vMemberGuidance WHERE MemberGuidance_ID = %d AND TypeGuidance_ID = %d LIMIT 1';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$exists = $wpdb->get_var( $wpdb->prepare( $sql, $member_guidance_id, $typeguidance_id ) );

		return null !== $exists;
	}

	public function relation_exists( int $typeguidance_id, int $member_guidance_id, int $user_id, int $exclude_guidance_id = 0 ): bool {
		global $wpdb;

		$sql    = 'SELECT Guidance_ID FROM Guidance WHERE TypeGuidance_ID = %d AND MemberGuidance_ID = %d AND User_ID = %d';
		$params = [ $typeguidance_id, $member_guidance_id, $user_id ];

		if ( $exclude_guidance_id > 0 ) {
			$sql     .= ' AND Guidance_ID != %d';
			$params[] = $exclude_guidance_id;
		}

		$sql .= ' LIMIT 1';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$guidance_id = $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );

		return null !== $guidance_id;
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
			$sql     .= ' WHERE FIO LIKE %s OR user_email LIKE %s';
			$params[] = $like;
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
			'Guidance',
			[
				'Guidance_DateCreate' => current_time( 'mysql' ),
				'User_ID'            => (int) $data['user_id'],
				'MemberGuidance_ID'  => (int) $data['member_guidance_id'],
				'TypeGuidance_ID'    => (int) $data['typeguidance_id'],
				'Guidance_Notes'     => (string) $data['guidance_notes'],
			],
			[ '%s', '%d', '%d', '%d', '%s' ]
		);

		if ( false === $result ) {
			throw new \RuntimeException( 'guidance_insert_failed' );
		}

		return (int) $wpdb->insert_id;
	}

    /**
     * Оновлення запису Guidance.
     * Виправлено: ключі масиву приведені до мапінгу сервісу (Legacy), додано оновлення User_ID.
     */
    public function update_item( int $guidance_id, array $data ): bool {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->update(
            'Guidance',
            [
                'User_ID'           => (int) $data['User_ID'],           // Додано зміну користувача
                'MemberGuidance_ID' => (int) $data['MemberGuidance_ID'], // Виправлено регістр ключа
                'TypeGuidance_ID'   => (int) $data['TypeGuidance_ID'],   // Виправлено регістр ключа
                'Guidance_Notes'    => (string) $data['Guidance_Notes'], // Виправлено регістр ключа
            ],
            [ 'Guidance_ID' => $guidance_id ],
            [ '%d', '%d', '%d', '%s' ],
            [ '%d' ]
        );

        if ( false === $result ) {
            throw new \RuntimeException( 'guidance_update_failed' );
        }

        // Повертаємо true, навіть якщо рядки не змінено (результат 0),
        // щоб сервіс міг зафіксувати успіх у протоколі.
        return true;
    }

	public function delete_item( int $guidance_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->delete( 'Guidance', [ 'Guidance_ID' => $guidance_id ], [ '%d' ] );

		if ( false === $result ) {
			throw new \RuntimeException( 'guidance_delete_failed' );
		}

		return true;
	}

	/**
	 * @return array<string,mixed>
	 */
	public function check_delete_dependencies( int $guidance_id ): array {
		unset( $guidance_id );

		return [
			'can_delete' => true,
			'message'    => '',
			'status'     => '✓',
		];
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

	private function get_base_select_sql(): string {
		global $wpdb;

		return "SELECT
			src.Guidance_ID AS Guidance_ID,
			src.User_ID AS User_ID,
			src.TypeGuidance_ID AS TypeGuidance_ID,
			src.MemberGuidance_ID AS MemberGuidance_ID,
			COALESCE(tg.TypeGuidance_Name, '') AS TypeGuidance_Name,
			COALESCE(mg.MemberGuidance_Name, '') AS MemberGuidance_Name,
			COALESCE(uf.FIO, '') AS FIO,
			COALESCE(uf.FIOshort, '') AS FIOshort,
			COALESCE(src.Guidance_Notes, '') AS Guidance_Notes,
			COALESCE(src.Guidance_DateCreate, '') AS Guidance_DateCreate,
			COALESCE(uf.user_email, u.user_email, '') AS user_email,
			COALESCE(pm.meta_value, '') AS PhoneMobile,
			COALESCE(p2.meta_value, '') AS Phone2,
			COALESCE(p3.meta_value, '') AS Phone3
		" . $this->get_base_from_sql();
	}

	private function get_base_from_sql(): string {
		global $wpdb;

		return "FROM Guidance src
		LEFT JOIN vTypeGuidance tg ON tg.TypeGuidance_ID = src.TypeGuidance_ID
		LEFT JOIN vMemberGuidance mg ON mg.MemberGuidance_ID = src.MemberGuidance_ID
		LEFT JOIN vUserFSTU uf ON uf.User_ID = src.User_ID
		LEFT JOIN {$wpdb->users} u ON u.ID = src.User_ID
		LEFT JOIN (
			SELECT user_id, MAX(meta_value) AS meta_value
			FROM {$wpdb->usermeta}
			WHERE meta_key = 'PhoneMobile'
			GROUP BY user_id
		) pm ON pm.user_id = src.User_ID
		LEFT JOIN (
			SELECT user_id, MAX(meta_value) AS meta_value
			FROM {$wpdb->usermeta}
			WHERE meta_key = 'Phone2'
			GROUP BY user_id
		) p2 ON p2.user_id = src.User_ID
		LEFT JOIN (
			SELECT user_id, MAX(meta_value) AS meta_value
			FROM {$wpdb->usermeta}
			WHERE meta_key = 'Phone3'
			GROUP BY user_id
		) p3 ON p3.user_id = src.User_ID";
	}

	/**
	 * @param array<string,mixed> $filters
	 * @return array{0:string,1:array<int,mixed>}
	 */
	private function build_list_where( array $filters ): array {
		global $wpdb;

		$where  = [ 'src.Guidance_ID > 0' ];
		$params = [];

		$typeguidance_id = isset( $filters['typeguidance_id'] ) ? (int) $filters['typeguidance_id'] : 0;
		if ( $typeguidance_id > 0 ) {
			$where[]  = 'src.TypeGuidance_ID = %d';
			$params[] = $typeguidance_id;
		}

		$search = isset( $filters['search'] ) ? trim( (string) $filters['search'] ) : '';
		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where[]  = '(COALESCE(uf.FIO, \'\') LIKE %s OR COALESCE(mg.MemberGuidance_Name, \'\') LIKE %s OR COALESCE(uf.user_email, u.user_email, \'\') LIKE %s)';
			$params[] = $like;
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
		$params = [ Guidance_Protocol_Service::LOG_NAME ];
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

