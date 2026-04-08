<?php
namespace FSTU\Modules\Registry\Referees;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repository модуля «Реєстр суддів ФСТУ».
 * Містить усі SQL-запити до legacy-таблиць і views.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-08
 *
 * @package FSTU\Modules\Registry\Referees
 */
class Referees_Repository {

	/**
	 * @param array<string,mixed> $filters Набір фільтрів.
	 * @return array<int,array<string,mixed>>
	 */
	public function get_referees( array $filters, int $limit, int $offset ): array {
		global $wpdb;

		[ $where_sql, $params ] = $this->build_referees_where( $filters );

		$sql = "SELECT vr.Referee_ID, vr.User_ID, vr.FIO, vr.FIOshort, vr.Region_Name, vr.Region_ID,
				vr.RefereeCategory_ID, vr.RefereeCategory_Name, vr.Referee_NumOrder, vr.Referee_DateOrder,
				vr.Referee_URLOrder, mc.CardNumber, COALESCE(cert.CertificatesCount, 0) AS CntCertificates
			FROM vReferee vr
			LEFT JOIN vUserMemberCard mc ON mc.User_ID = vr.User_ID
			LEFT JOIN (
				SELECT User_ID, COUNT(*) AS CertificatesCount
				FROM CertificatesForRefereeing
				GROUP BY User_ID
			) cert ON cert.User_ID = vr.User_ID
			{$where_sql}
			ORDER BY vr.FIO ASC
			LIMIT %d OFFSET %d";

		$params[] = $limit;
		$params[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$items = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );

		return is_array( $items ) ? $items : [];
	}

	/**
	 * Повертає повний список суддів без SQL-пошуку для fallback-фільтрації на рівні PHP.
	 *
	 * @param array<string,mixed> $filters Набір фільтрів.
	 * @return array<int,array<string,mixed>>
	 */
	public function get_referees_for_search_fallback( array $filters ): array {
		global $wpdb;

		$filters['search'] = '';
		[ $where_sql, $params ] = $this->build_referees_where( $filters );

		$sql = "SELECT vr.Referee_ID, vr.User_ID, vr.FIO, vr.FIOshort, vr.Region_Name, vr.Region_ID,
				vr.RefereeCategory_ID, vr.RefereeCategory_Name, vr.Referee_NumOrder, vr.Referee_DateOrder,
				vr.Referee_URLOrder, mc.CardNumber, COALESCE(cert.CertificatesCount, 0) AS CntCertificates
			FROM vReferee vr
			LEFT JOIN vUserMemberCard mc ON mc.User_ID = vr.User_ID
			LEFT JOIN (
				SELECT User_ID, COUNT(*) AS CertificatesCount
				FROM CertificatesForRefereeing
				GROUP BY User_ID
			) cert ON cert.User_ID = vr.User_ID
			{$where_sql}
			ORDER BY vr.FIO ASC";

		if ( ! empty( $params ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
			$items = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$items = $wpdb->get_results( $sql, ARRAY_A );
		}

		return is_array( $items ) ? $items : [];
	}

	/**
	 * @param array<string,mixed> $filters Набір фільтрів.
	 */
	public function count_referees( array $filters ): int {
		global $wpdb;

		[ $where_sql, $params ] = $this->build_referees_where( $filters );
		$sql = "SELECT COUNT(*) FROM vReferee vr {$where_sql}";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$total = $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );

		return (int) $total;
	}

	public function get_referee_by_id( int $referee_id ): ?array {
		global $wpdb;

		$sql = "SELECT vr.Referee_ID, vr.User_ID, vr.FIO, vr.FIOshort, vr.Region_Name, vr.Region_ID,
				vr.RefereeCategory_ID, vr.RefereeCategory_Name, vr.RefereeCategory_Order,
				vr.Referee_DateCreate, vr.Referee_NumOrder, vr.Referee_DateOrder, vr.Referee_URLOrder,
				mc.CardNumber, COALESCE(cert.CertificatesCount, 0) AS CntCertificates,
				u.FIO AS CreatedByFio
			FROM vReferee vr
			LEFT JOIN vUserMemberCard mc ON mc.User_ID = vr.User_ID
			LEFT JOIN (
				SELECT User_ID, COUNT(*) AS CertificatesCount
				FROM CertificatesForRefereeing
				GROUP BY User_ID
			) cert ON cert.User_ID = vr.User_ID
			LEFT JOIN Logs l ON l.Logs_DateCreate = vr.Referee_DateCreate AND l.Logs_Name = %s AND l.Logs_Type = %s
			LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
			WHERE vr.Referee_ID = %d
			LIMIT 1";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$item = $wpdb->get_row( $wpdb->prepare( $sql, 'Referee', 'I', $referee_id ), ARRAY_A );

		return is_array( $item ) ? $item : null;
	}

	public function get_referee_by_user_id( int $user_id ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$item = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT Referee_ID, User_ID FROM Referee WHERE User_ID = %d LIMIT 1',
				$user_id
			),
			ARRAY_A
		);

		return is_array( $item ) ? $item : null;
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

	public function user_exists( int $user_id ): bool {
		return '' !== $this->get_user_fio( $user_id );
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function get_regions(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$items = $wpdb->get_results(
			'SELECT Region_ID, Region_Name, Region_Code FROM S_Region ORDER BY Region_Code ASC, Region_Name ASC',
			ARRAY_A
		);

		return is_array( $items ) ? $items : [];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function get_categories(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$items = $wpdb->get_results(
			'SELECT RefereeCategory_ID, RefereeCategory_Name, RefereeCategory_Order FROM vRefereeCategory ORDER BY RefereeCategory_Order ASC, RefereeCategory_Name ASC',
			ARRAY_A
		);

		return is_array( $items ) ? $items : [];
	}

	public function category_exists( int $category_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT RefereeCategory_ID FROM S_RefereeCategory WHERE RefereeCategory_ID = %d LIMIT 1',
				$category_id
			)
		);

		return null !== $existing_id;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function get_available_users(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$items = $wpdb->get_results(
			'SELECT User_ID, FIO FROM vUserFSTU WHERE User_ID NOT IN (SELECT User_ID FROM Referee) ORDER BY FIO ASC',
			ARRAY_A
		);

		return is_array( $items ) ? $items : [];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function get_calendars(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$items = $wpdb->get_results(
			'SELECT Calendar_ID, CalendarName AS Calendar_Name, Calendar_DateBegin, Calendar_DateEnd FROM vCalendar ORDER BY Calendar_DateBegin DESC, Calendar_ID DESC',
			ARRAY_A
		);

		return is_array( $items ) ? $items : [];
	}

	public function calendar_exists( int $calendar_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT Calendar_ID FROM vCalendar WHERE Calendar_ID = %d LIMIT 1',
				$calendar_id
			)
		);

		return null !== $existing_id;
	}

	public function insert_referee( array $data ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->insert(
			'Referee',
			[
				'Referee_DateCreate'  => current_time( 'mysql' ),
				'User_ID'             => (int) $data['user_id'],
				'Referee_NumOrder'    => (string) $data['num_order'],
				'Referee_DateOrder'   => (string) $data['date_order'],
				'Referee_URLOrder'    => (string) $data['url_order'],
				'RefereeCategory_ID'  => (int) $data['referee_category_id'],
			],
			[ '%s', '%d', '%s', '%s', '%s', '%d' ]
		);

		if ( false === $result ) {
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	public function update_referee( int $referee_id, array $data ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->update(
			'Referee',
			[
				'Referee_NumOrder'   => (string) $data['num_order'],
				'Referee_DateOrder'  => (string) $data['date_order'],
				'Referee_URLOrder'   => (string) $data['url_order'],
				'RefereeCategory_ID' => (int) $data['referee_category_id'],
			],
			[ 'Referee_ID' => $referee_id ],
			[ '%s', '%s', '%s', '%d' ],
			[ '%d' ]
		);

		return false !== $result;
	}

	public function delete_referee( int $referee_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->delete( 'Referee', [ 'Referee_ID' => $referee_id ], [ '%d' ] );

		return false !== $result;
	}

	public function count_referee_certificates( int $user_id ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$total = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM CertificatesForRefereeing WHERE User_ID = %d',
				$user_id
			)
		);

		return (int) $total;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function get_certificates_by_user_id( int $user_id ): array {
		global $wpdb;

		$sql = "SELECT CertificatesForRefereeing_ID, User_ID, Calendar_ID, Calendar_Name, Calendar_DateBegin,
				Calendar_DateEnd, RefereeCategory_ID, RefereeCategory_Name,
				CertificatesForRefereeing_DateCreate, CertificatesForRefereeing_URL
			FROM vCertificatesForRefereeing
			WHERE User_ID = %d
			ORDER BY Calendar_DateBegin DESC, CertificatesForRefereeing_DateCreate DESC";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$items = $wpdb->get_results( $wpdb->prepare( $sql, $user_id ), ARRAY_A );

		return is_array( $items ) ? $items : [];
	}

	public function get_certificate_by_id( int $certificate_id ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$item = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT CertificatesForRefereeing_ID, User_ID, Calendar_ID, RefereeCategory_ID, CertificatesForRefereeing_DateCreate, CertificatesForRefereeing_URL FROM CertificatesForRefereeing WHERE CertificatesForRefereeing_ID = %d LIMIT 1',
				$certificate_id
			),
			ARRAY_A
		);

		return is_array( $item ) ? $item : null;
	}

	public function certificate_exists_for_user_calendar( int $user_id, int $calendar_id, int $exclude_certificate_id = 0 ): bool {
		global $wpdb;

		$sql    = 'SELECT CertificatesForRefereeing_ID FROM CertificatesForRefereeing WHERE User_ID = %d AND Calendar_ID = %d';
		$params = [ $user_id, $calendar_id ];

		if ( $exclude_certificate_id > 0 ) {
			$sql     .= ' AND CertificatesForRefereeing_ID != %d';
			$params[] = $exclude_certificate_id;
		}

		$sql .= ' LIMIT 1';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$certificate_id = $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );

		return null !== $certificate_id;
	}

	public function insert_certificate( array $data ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->insert(
			'CertificatesForRefereeing',
			[
				'User_ID'                           => (int) $data['user_id'],
				'Calendar_ID'                       => (int) $data['calendar_id'],
				'CertificatesForRefereeing_DateCreate' => current_time( 'mysql' ),
				'CertificatesForRefereeing_URL'     => (string) $data['certificate_url'],
			],
			[ '%d', '%d', '%s', '%s' ]
		);

		if ( false === $result ) {
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	public function update_certificate_category( int $certificate_id, ?int $category_id ): bool {
		global $wpdb;

		if ( null === $category_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$result = $wpdb->query(
				$wpdb->prepare(
					'UPDATE CertificatesForRefereeing SET RefereeCategory_ID = NULL WHERE CertificatesForRefereeing_ID = %d',
					$certificate_id
				)
			);

			return false !== $result;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->query(
			$wpdb->prepare(
				'UPDATE CertificatesForRefereeing SET RefereeCategory_ID = %d WHERE CertificatesForRefereeing_ID = %d',
				$category_id,
				$certificate_id
			)
		);

		return false !== $result;
	}

	/**
	 * @param array<int,string> $log_names Імена журналів.
	 */
	public function count_protocol_items( string $search, array $log_names ): int {
		global $wpdb;

		[ $where_sql, $params ] = $this->build_protocol_where( $search, $log_names );
		$sql = "SELECT COUNT(*) FROM Logs l LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID {$where_sql}";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$total = $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );

		return (int) $total;
	}

	/**
	 * @param array<int,string> $log_names Імена журналів.
	 * @return array<int,array<string,mixed>>
	 */
	public function get_protocol_items( string $search, int $limit, int $offset, array $log_names ): array {
		global $wpdb;

		[ $where_sql, $params ] = $this->build_protocol_where( $search, $log_names );
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

	/**
	 * @param array<string,mixed> $filters Набір фільтрів.
	 * @return array{0:string,1:array<int|string,mixed>}
	 */
	private function build_referees_where( array $filters ): array {
		global $wpdb;

		$where  = 'WHERE vr.Referee_ID > 0';
		$params = [];

		$search = trim( (string) ( $filters['search'] ?? '' ) );
		if ( '' !== $search ) {
			$like    = '%' . $wpdb->esc_like( $search ) . '%';
			$where  .= ' AND (vr.FIO LIKE %s OR vr.FIOshort LIKE %s OR vr.Referee_NumOrder LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$region_id = (int) ( $filters['region_id'] ?? 0 );
		if ( $region_id > 0 ) {
			$where  .= ' AND vr.Region_ID = %d';
			$params[] = $region_id;
		}

		$category_id = (int) ( $filters['referee_category_id'] ?? 0 );
		if ( $category_id > 0 ) {
			$where  .= ' AND vr.RefereeCategory_ID = %d';
			$params[] = $category_id;
		}

		return [ $where, $params ];
	}

	/**
	 * @param array<int,string> $log_names Імена журналів.
	 * @return array{0:string,1:array<int|string,mixed>}
	 */
	private function build_protocol_where( string $search, array $log_names ): array {
		global $wpdb;

		$log_names     = array_values( array_filter( array_map( 'strval', $log_names ) ) );
		$placeholders  = implode( ', ', array_fill( 0, count( $log_names ), '%s' ) );
		$where         = "WHERE l.Logs_Name IN ({$placeholders})";
		$params        = $log_names;
		$search        = trim( $search );

		if ( '' !== $search ) {
			$like    = '%' . $wpdb->esc_like( $search ) . '%';
			$where  .= ' AND (l.Logs_Text LIKE %s OR u.FIO LIKE %s OR l.Logs_Name LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		return [ $where, $params ];
	}
}

