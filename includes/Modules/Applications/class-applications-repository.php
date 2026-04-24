<?php

namespace FSTU\Modules\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Repository модуля "Заявки в ФСТУ".
 * Відповідає лише за доступ до даних та SQL-запити.
 *
 * Version:     1.9.1
 * Date_update: 2026-04-24
 */
class Applications_Repository {

    private const FILTER_DATASETS_CACHE_KEY = 'fstu_applications_filter_datasets_v1';
    private const FILTER_DATASETS_CACHE_TTL = HOUR_IN_SECONDS;
    private const ROLE_APPLICANT = 'applicants';
    private const ROLE_BLOCKED = 'Blocked';
    private const ROLE_BBP_BLOCKED = 'bbp_blocked';

    /**
     * Повертає довідники для фільтрів.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    public function get_filter_datasets(): array {
        $cached = wp_cache_get( self::FILTER_DATASETS_CACHE_KEY, 'fstu_applications' );
        if ( is_array( $cached ) && isset( $cached[0], $cached[1] ) ) {
            return $cached;
        }

        $cached_transient = get_transient( self::FILTER_DATASETS_CACHE_KEY );
        if ( is_array( $cached_transient ) && isset( $cached_transient[0], $cached_transient[1] ) ) {
            wp_cache_set( self::FILTER_DATASETS_CACHE_KEY, $cached_transient, 'fstu_applications', self::FILTER_DATASETS_CACHE_TTL );

            return $cached_transient;
        }

        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
        $regions = $wpdb->get_results(
            'SELECT Region_ID, Region_Name FROM S_Region ORDER BY Region_Name ASC',
            ARRAY_A
        ) ?: [];

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
        $units = $wpdb->get_results(
            'SELECT Unit_ID, Unit_ShortName, Region_ID FROM S_Unit ORDER BY Unit_ShortName ASC',
            ARRAY_A
        ) ?: [];

        $datasets = [ $regions, $units ];

        wp_cache_set( self::FILTER_DATASETS_CACHE_KEY, $datasets, 'fstu_applications', self::FILTER_DATASETS_CACHE_TTL );
        set_transient( self::FILTER_DATASETS_CACHE_KEY, $datasets, self::FILTER_DATASETS_CACHE_TTL );

        return $datasets;
    }

    /**
     * Повертає список заявок.
     *
     * @return array{rows: array<int, array<string, mixed>>, total: int}
     */
    public function get_applications_list( string $search, int $region_id, int $unit_id, int $per_page, int $offset ): array {
        global $wpdb;

        [ $where_sql, $params ] = $this->build_list_where_sql( $search, $region_id, $unit_id );
        $names_sql = $this->get_names_subquery_sql();
        $latest_ofst_sql = $this->get_latest_ofst_subquery_sql();
        $count_base_sql = $this->get_count_base_sql( '' !== $search, $region_id > 0 || $unit_id > 0, $names_sql, $latest_ofst_sql, $where_sql );
        $list_base_sql = $this->get_list_base_sql( $names_sql, $latest_ofst_sql, $where_sql );

        $fio_sql = "TRIM(CONCAT_WS(' ', NULLIF(um_name.last_name, ''), NULLIF(um_name.first_name, ''), NULLIF(um_name.patronymic, '')))";

        $count_sql = "SELECT COUNT(DISTINCT u.ID) {$count_base_sql}";
        $total = $this->run_single_numeric_result( $count_sql, $params );

        $select_sql = "
            SELECT DISTINCT
                u.ID AS user_id,
                u.user_registered,
                u.user_email,
                u.display_name,
                u.user_login,
                ur.Unit_ID AS unit_id,
                {$fio_sql} AS fio,
                IFNULL(su.Unit_ShortName, '') AS unit_short_name,
                IFNULL(sr.Region_Name, '') AS region_name
            {$list_base_sql}
            ORDER BY u.user_registered DESC, u.ID DESC
            LIMIT %d OFFSET %d
        ";

        $data_params = array_merge( $params, [ $per_page, $offset ] );
        $rows = $this->run_prepared_results( $select_sql, $data_params );

        return [
            'rows'  => $rows,
            'total' => $total,
        ];
    }

    /**
     * Базова частина COUNT query без зайвих JOIN.
     */
    private function get_count_base_sql( bool $needs_name_join, bool $needs_ofst_join, string $names_sql, string $latest_ofst_sql, string $where_sql ): string {
        $joins = [
            $this->get_candidates_from_sql(),
        ];

        if ( $needs_name_join ) {
            $joins[] = "LEFT JOIN ({$names_sql}) um_name ON um_name.user_id = u.ID";
        }

        if ( $needs_ofst_join ) {
            $joins[] = "LEFT JOIN ({$latest_ofst_sql}) ur ON ur.User_ID = u.ID";
        }

        $joins[] = $where_sql;

        return implode( "\n", array_filter( $joins ) );
    }

    /**
     * Базова частина LIST query.
     */
    private function get_list_base_sql( string $names_sql, string $latest_ofst_sql, string $where_sql ): string {
        return "
            {$this->get_candidates_from_sql()}
            LEFT JOIN ({$names_sql}) um_name ON um_name.user_id = u.ID
            LEFT JOIN ({$latest_ofst_sql}) ur ON ur.User_ID = u.ID
            LEFT JOIN S_Unit su ON su.Unit_ID = ur.Unit_ID
            LEFT JOIN S_Region sr ON sr.Region_ID = ur.Region_ID
            {$where_sql}
        ";
    }

    /**
     * Будує WHERE-частину для списку заявок.
     *
     * @return array{0: string, 1: array<int, int|string>}
     */
    private function build_list_where_sql( string $search, int $region_id, int $unit_id ): array {
        global $wpdb;

        $where_clauses = [];
        $params        = [];

        if ( '' !== $search ) {
            $like            = '%' . $wpdb->esc_like( $search ) . '%';
            $where_clauses[] = "(TRIM(CONCAT_WS(' ', NULLIF(um_name.last_name, ''), NULLIF(um_name.first_name, ''), NULLIF(um_name.patronymic, ''))) LIKE %s OR u.display_name LIKE %s OR u.user_email LIKE %s OR u.user_login LIKE %s)";
            $params[]        = $like;
            $params[]        = $like;
            $params[]        = $like;
            $params[]        = $like;
        }

        if ( $region_id > 0 ) {
            $where_clauses[] = 'ur.Region_ID = %d';
            $params[]        = $region_id;
        }

        if ( $unit_id > 0 ) {
            $where_clauses[] = 'ur.Unit_ID = %d';
            $params[]        = $unit_id;
        }

        $where_sql = ! empty( $where_clauses )
            ? 'WHERE ' . implode( ' AND ', $where_clauses )
            : '';

        return [ $where_sql, $params ];
    }

    /**
     * Повертає FROM/JOIN SQL для джерела кандидатів у модулі заявок.
     * Підтримує як legacy blocked-користувачів, так і нові заявки з роллю applicants.
     * Виключає користувачів, які вже отримали роль члена ФСТУ (userfstu).
     */
    private function get_candidates_from_sql(): string {
        global $wpdb;

        $role_conditions = [
            "cap.meta_value LIKE '%" . self::ROLE_APPLICANT . "%'",
            "cap.meta_value LIKE '%" . self::ROLE_BLOCKED . "%'",
            "cap.meta_value LIKE '%" . self::ROLE_BBP_BLOCKED . "%'",
        ];

        return "FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} cap ON cap.user_id = u.ID
                AND cap.meta_key = 'wp_capabilities'
                AND (" . implode( ' OR ', $role_conditions ) . ")
                AND cap.meta_value NOT LIKE '%userfstu%'";
    }

    /**
     * Повертає SQL підзапиту агрегованих імен із usermeta.
     */
    private function get_names_subquery_sql(): string {
        global $wpdb;

        return "
            SELECT
                user_id,
                MAX(CASE WHEN meta_key = 'last_name' THEN meta_value END) AS last_name,
                MAX(CASE WHEN meta_key = 'first_name' THEN meta_value END) AS first_name,
                MAX(CASE WHEN meta_key = 'Patronymic' THEN meta_value END) AS patronymic
            FROM {$wpdb->usermeta}
            WHERE meta_key IN ('last_name', 'first_name', 'Patronymic')
            GROUP BY user_id
        ";
    }

    /**
     * Повертає SQL підзапиту актуального запису ОФСТ.
     */
    private function get_latest_ofst_subquery_sql(): string {
        return "
            SELECT
                u1.UserRegistationOFST_ID,
                u1.User_ID,
                u1.Unit_ID,
                u1.Region_ID,
                u1.UserRegistationOFST_DateCreate
            FROM UserRegistationOFST u1
            INNER JOIN (
                SELECT User_ID, MAX(UserRegistationOFST_DateCreate) AS max_date
                FROM UserRegistationOFST
                GROUP BY User_ID
            ) u2 ON u1.User_ID = u2.User_ID AND u1.UserRegistationOFST_DateCreate = u2.max_date
        ";
    }

    /**
     * Виконує scalar count/select і повертає int.
     *
     * @param array<int, int|string> $params
     */
    private function run_single_numeric_result( string $sql, array $params ): int {
        global $wpdb;

        if ( ! empty( $params ) ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
            return (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
        return (int) $wpdb->get_var( $sql );
    }

    /**
     * Виконує prepared select і повертає масив рядків.
     *
     * @param array<int, int|string> $params
     * @return array<int, array<string, mixed>>
     */
    private function run_prepared_results( string $sql, array $params ): array {
        global $wpdb;

        $prepared_sql = ! empty( $params )
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            ? $wpdb->prepare( $sql, ...$params )
            : $sql;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
        return $wpdb->get_results( $prepared_sql, ARRAY_A ) ?: [];
    }

    /**
     * Повертає одного кандидата.
     */
    /**
     * @return array{data: array<string, mixed>|null, debug: array<string, mixed>}
     */
    public function get_candidate_by_id( int $user_id, bool $with_debug = false ): array {
        global $wpdb;

        $started_at = microtime( true );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $data = $wpdb->get_row(
            $wpdb->prepare( 'SELECT * FROM vUserBlocked WHERE User_ID = %d LIMIT 1', $user_id ),
            ARRAY_A
        );

        if ( ! is_array( $data ) ) {
            $data = $this->get_candidate_fallback_row( $user_id );
        }

        if ( ! is_array( $data ) ) {
            return [
                'data'  => null,
                'debug' => $with_debug ? $this->build_debug_payload( 'candidate_single', $started_at, [
                    'user_id' => $user_id,
                    'found'   => false,
                ] ) : [],
            ];
        }

        $meta_map = $this->get_user_meta_map(
            $user_id,
            [ 'Adr', 'PhoneMobile', 'Phone2', 'Job', 'Post', 'Education', 'SailingExperience', 'SuddyCategory', 'PublicTourism', 'BirthDate', 'Sex' ]
        );

        $data['adr']                = $meta_map['Adr'] ?? '';
        $data['phone']              = $meta_map['PhoneMobile'] ?? '';
        $data['phone2']             = $meta_map['Phone2'] ?? '';
        $data['job']                = $meta_map['Job'] ?? '';
        $data['post']               = $meta_map['Post'] ?? '';
        $data['education']          = $meta_map['Education'] ?? '';
        $data['sailing_experience'] = $meta_map['SailingExperience'] ?? '';
        $data['suddy_category']     = $meta_map['SuddyCategory'] ?? '';
        $data['public_tourism']     = $meta_map['PublicTourism'] ?? '';
        $data['BirthDate']          = '' !== trim( (string) ( $data['BirthDate'] ?? '' ) ) ? (string) $data['BirthDate'] : ( $meta_map['BirthDate'] ?? '' );
        $data['Sex']                = '' !== trim( (string) ( $data['Sex'] ?? '' ) ) ? (string) $data['Sex'] : ( $meta_map['Sex'] ?? '' );

        $club_row = $this->get_candidate_club_data( $user_id );
        $card_row = $this->get_latest_member_card_data( $user_id );

        $data['club_name']       = (string) ( $club_row['Club_Name'] ?? '' );
        $data['club_website']    = (string) ( $club_row['Club_WWW'] ?? '' );
        $data['tourism_type']    = (string) ( $data['TourismType_Name'] ?? '' );
        $data['card_number']     = $this->build_card_number_label( $card_row );
        $data['payment_info']    = [
            'is_informational' => true,
            'message'          => 'Оплата не блокує прийняття заявки. Деталі переглядаються у модулі PaymentDocs.',
            'year'             => trim( (string) ( $data['Year_Name'] ?? '' ) ),
            'amount'           => trim( (string) ( $data['Dues_Summa'] ?? '' ) ),
            'receipt_url'      => trim( (string) ( $data['Dues_URL'] ?? '' ) ),
            'created_at'       => trim( (string) ( $data['Dues_DateCreate'] ?? '' ) ),
        ];

        return [
            'data'  => $data,
            'debug' => $with_debug ? $this->build_debug_payload( 'candidate_single', $started_at, [
                'user_id'   => $user_id,
                'found'     => true,
                'meta_keys' => array_keys( $meta_map ),
            ] ) : [],
        ];
    }

    /**
     * Fallback-отримання кандидата без залежності від legacy view vUserBlocked.
     * Потрібно для нових заявок, які ще не потрапили у старе view.
     * Використовує Object Cache для зниження навантаження на БД.
     *
     * @return array<string, mixed>|null
     */
    private function get_candidate_fallback_row( int $user_id ): ?array {
        $cache_key = 'fstu_candidate_fallback_' . $user_id;
        $cached    = wp_cache_get( $cache_key, 'fstu_applications' );

        if ( false !== $cached ) {
            return is_array( $cached ) ? $cached : null;
        }

        global $wpdb;

        $names_sql       = $this->get_names_subquery_sql();
        $latest_ofst_sql = $this->get_latest_ofst_subquery_sql();
        $fio_sql         = "TRIM(CONCAT_WS(' ', NULLIF(um_name.last_name, ''), NULLIF(um_name.first_name, ''), NULLIF(um_name.patronymic, '')))";

        $sql = "
            SELECT
                u.ID AS User_ID,
                u.user_registered,
                u.user_email,
                u.display_name,
                u.user_login,
                {$fio_sql} AS FIO,
                ur.Unit_ID,
                ur.Region_ID,
                IFNULL(su.Unit_ShortName, '') AS Unit_ShortName,
                IFNULL(sr.Region_Name, '') AS Region_Name,
                '' AS BirthDate,
                '' AS Sex,
                '' AS Year_Name,
                '' AS Dues_Summa,
                '' AS Dues_URL,
                '' AS Dues_DateCreate,
                '' AS TourismType_Name
            {$this->get_candidates_from_sql()}
            LEFT JOIN ({$names_sql}) um_name ON um_name.user_id = u.ID
            LEFT JOIN ({$latest_ofst_sql}) ur ON ur.User_ID = u.ID
            LEFT JOIN S_Unit su ON su.Unit_ID = ur.Unit_ID
            LEFT JOIN S_Region sr ON sr.Region_ID = ur.Region_ID
            WHERE u.ID = %d
            LIMIT 1
        ";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
        $row = $wpdb->get_row( $wpdb->prepare( $sql, $user_id ), ARRAY_A );

        if ( ! is_array( $row ) ) {
            return null;
        }

        $row['City_Name']         = $this->get_candidate_city_name( $user_id );
        $row['TourismType_Name']  = $this->get_candidate_tourism_type_name( $user_id );

        // Кешуємо результат на 15 хвилин у Redis/RAM
        wp_cache_set( $cache_key, $row, 'fstu_applications', 15 * MINUTE_IN_SECONDS );

        return $row;
    }

    /**
     * Повертає актуальну назву міста кандидата.
     */
    private function get_candidate_city_name( int $user_id ): string {
        global $wpdb;

        $sql = "SELECT sc.City_Name
            FROM UserCity uc
            LEFT JOIN S_City sc ON sc.City_ID = uc.City_ID
            WHERE uc.User_ID = %d
            ORDER BY uc.UserCity_DateCreate DESC, uc.UserCity_ID DESC
            LIMIT 1";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $value = $wpdb->get_var( $wpdb->prepare( $sql, $user_id ) );

        return is_string( $value ) ? $value : '';
    }

    /**
     * Повертає актуальний вид туризму кандидата.
     */
    private function get_candidate_tourism_type_name( int $user_id ): string {
        global $wpdb;

        $sql = "SELECT st.TourismType_Name
            FROM UserTourismType utt
            LEFT JOIN S_TourismType st ON st.TourismType_ID = utt.TourismType_ID
            WHERE utt.User_ID = %d
            ORDER BY utt.UserTourismType_DateCreate DESC, utt.UserTourismType_ID DESC
            LIMIT 1";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $value = $wpdb->get_var( $wpdb->prepare( $sql, $user_id ) );

        return is_string( $value ) ? $value : '';
    }

    /**
     * Повертає значення обраних мета-ключів одним preload-викликом.
     *
     * @param array<int, string> $keys
     * @return array<string, string>
     */
    private function get_user_meta_map( int $user_id, array $keys ): array {
        $all_meta = get_user_meta( $user_id );
        $result   = [];

        foreach ( $keys as $key ) {
            $values = $all_meta[ $key ] ?? [];
            $value  = is_array( $values ) && isset( $values[0] ) ? $values[0] : '';
            $result[ $key ] = is_scalar( $value ) ? (string) $value : '';
        }

        return $result;
    }

    /**
     * Формує safe debug payload для diagnostics без витоку внутрішніх помилок БД.
     *
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function build_debug_payload( string $label, float $started_at, array $extra = [] ): array {
        return array_merge(
            [
                'label'        => $label,
                'execution_ms' => round( ( microtime( true ) - $started_at ) * 1000, 2 ),
            ],
            $extra
        );
    }

    /**
     * Повертає дані клубу кандидата.
     *
     * @return array<string, mixed>|null
     */
    private function get_candidate_club_data( int $user_id ): ?array {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $row = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT Club_Name, Club_WWW FROM vUserClub WHERE User_ID = %d LIMIT 1',
                $user_id
            ),
            ARRAY_A
        );

        return is_array( $row ) ? $row : null;
    }

    /**
     * Повертає останній запис членського квитка кандидата.
     *
     * @return array<string, mixed>|null
     */
    private function get_latest_member_card_data( int $user_id ): ?array {
        global $wpdb;

        $sql = "SELECT umc.Region_ID, umc.UserMemberCard_Number, sr.Region_Code
            FROM UserMemberCard umc
            LEFT JOIN S_Region sr ON sr.Region_ID = umc.Region_ID
            WHERE umc.User_ID = %d
            ORDER BY umc.UserMemberCard_DateCreate DESC, umc.UserMemberCard_Number DESC
            LIMIT 1";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $row = $wpdb->get_row( $wpdb->prepare( $sql, $user_id ), ARRAY_A );

        return is_array( $row ) ? $row : null;
    }

    /**
     * Формує підпис номера квитка.
     *
     * @param array<string, mixed>|null $card_row
     */
    private function build_card_number_label( ?array $card_row ): string {
        if ( ! is_array( $card_row ) ) {
            return '';
        }

        $region_code = trim( (string) ( $card_row['Region_Code'] ?? '' ) );
        $card_number = trim( (string) ( $card_row['UserMemberCard_Number'] ?? '' ) );

        if ( '' === $card_number ) {
            return '';
        }

        return '' !== $region_code
            ? $region_code . '-' . $card_number
            : $card_number;
    }

    /**
     * Повертає актуальний Region_ID кандидата.
     */
    public function get_candidate_region_id( int $user_id ): int {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return (int) $wpdb->get_var(
            $wpdb->prepare( 'SELECT Region_ID FROM vUserRegistationOFST WHERE User_ID = %d', $user_id )
        );
    }

    /**
     * Повертає наступний номер квитка в ��ежах регіону.
     */
    public function get_next_member_card_number( int $region_id ): int {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COALESCE(MAX(UserMemberCard_Number), 0) + 1 FROM UserMemberCard WHERE Region_ID = %d',
                $region_id
            )
        );
    }

    /**
     * Чи має користувач хоча б один членський квиток.
     */
    public function candidate_has_member_card( int $user_id ): bool {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT 1 FROM UserMemberCard WHERE User_ID = %d LIMIT 1',
                $user_id
            )
        );

        return ! empty( $exists );
    }

    /**
     * Створює запис членського квитка.
     */
    public function create_member_card( int $user_id, int $region_id, int $card_number ): bool {
        global $wpdb;

        $meta_map = $this->get_user_meta_map( $user_id, [ 'last_name', 'first_name' ] );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $inserted = $wpdb->insert(
            'UserMemberCard',
            [
                'UserMemberCard_DateCreate' => current_time( 'mysql' ),
                'UserCreate'                => get_current_user_id(),
                'User_ID'                   => $user_id,
                'TypeCard_ID'               => 1,
                'StatusCard_ID'             => 1,
                'Region_ID'                 => $region_id,
                'UserMemberCard_Number'     => $card_number,
                'UserMemberCard_LastName'   => $meta_map['last_name'] ?? '',
                'UserMemberCard_FirstName'  => $meta_map['first_name'] ?? '',
            ],
            [ '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s' ]
        );

        return false !== $inserted;
    }

    /**
     * Повертає значення налаштування.
     */
    public function get_setting_value( string $param_name ): string {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $value = $wpdb->get_var(
            $wpdb->prepare( 'SELECT ParamValue FROM Settings WHERE ParamName = %s', $param_name )
        );

        return is_string( $value ) ? $value : '';
    }

    /**
     * Повертає осередок за ID.
     */
    public function get_unit_by_id( int $unit_id ): ?array {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $row = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT Unit_ID, Region_ID, Unit_ShortName FROM S_Unit WHERE Unit_ID = %d LIMIT 1',
                $unit_id
            ),
            ARRAY_A
        );

        return is_array( $row ) ? $row : null;
    }

    /**
     * Повертає поточний актуальний запис ��ФСТ користувача.
     */
    public function get_current_ofst_record( int $user_id ): ?array {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $row = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT UserRegistationOFST_ID, User_ID, Unit_ID, Region_ID, UserRegistationOFST_DateCreate FROM UserRegistationOFST WHERE User_ID = %d ORDER BY UserRegistationOFST_DateCreate DESC, UserRegistationOFST_ID DESC LIMIT 1',
                $user_id
            ),
            ARRAY_A
        );

        return is_array( $row ) ? $row : null;
    }

    /**
     * Оновлює поточний запис ОФСТ з optimistic concurrency guard.
     */
    public function update_current_ofst_record_conditionally(
        int $record_id,
        int $user_id,
        string $current_date,
        int $current_region_id,
        int $current_unit_id,
        int $region_id,
        int $unit_id,
        string $new_date
    ): bool|\WP_Error {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $updated = $wpdb->update(
            'UserRegistationOFST',
            [
                'UserRegistationOFST_DateCreate' => $new_date,
                'Region_ID'                      => $region_id,
                'Unit_ID'                        => $unit_id,
            ],
            [
                'UserRegistationOFST_ID'         => $record_id,
                'User_ID'                        => $user_id,
                'UserRegistationOFST_DateCreate' => $current_date,
                'Region_ID'                      => $current_region_id,
                'Unit_ID'                        => $current_unit_id,
            ],
            [ '%s', '%d', '%d' ],
            [ '%d', '%d', '%s', '%d', '%d' ]
        );

        if ( false === $updated ) {
            return new \WP_Error(
                $this->map_ofst_write_error_to_marker( (string) $wpdb->last_error, 'ofst_update_failed' ),
                'Не вдалося оновити поточний запис ОФСТ.'
            );
        }

        return 1 === $updated;
    }

    /**
     * Додає snapshot старого стану ОФСТ у таблицю історії.
     *
     * @param array<string,mixed> $record
     */
    public function insert_ofst_history_snapshot( array $record ): bool|\WP_Error {
        global $wpdb;

        $user_id    = (int) ( $record['User_ID'] ?? 0 );
        $region_id  = (int) ( $record['Region_ID'] ?? 0 );
        $unit_id    = (int) ( $record['Unit_ID'] ?? 0 );
        $date_value = (string) ( $record['UserRegistationOFST_DateCreate'] ?? current_time( 'mysql' ) );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $inserted = $wpdb->insert(
            'UserRegistationOFST',
            [
                'UserRegistationOFST_DateCreate' => $date_value,
                'Region_ID'                      => $region_id,
                'Unit_ID'                        => $unit_id,
                'User_ID'                        => $user_id,
            ],
            [ '%s', '%d', '%d', '%d' ]
        );

        if ( false === $inserted ) {
            return new \WP_Error(
                $this->map_ofst_write_error_to_marker( (string) $wpdb->last_error, 'ofst_history_insert_failed' ),
                'Не вдалося зберегти історію зміни ОФСТ.'
            );
        }

        return true;
    }

    /**
     * Створює абсолютно новий запис ОФСТ (використовується, якщо кандидат його не мав).
     */
    public function insert_new_ofst_record( int $user_id, int $region_id, int $unit_id, string $date ): bool|\WP_Error {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $inserted = $wpdb->insert(
            'UserRegistationOFST',
            [
                'UserRegistationOFST_DateCreate' => $date,
                'Region_ID'                      => $region_id,
                'Unit_ID'                        => $unit_id,
                'User_ID'                        => $user_id,
            ],
            [ '%s', '%d', '%d', '%d' ]
        );

        if ( false === $inserted ) {
            return new \WP_Error(
                $this->map_ofst_write_error_to_marker( (string) $wpdb->last_error, 'ofst_insert_failed' ),
                'Не вдалося створити новий запис ОФСТ.'
            );
        }

        return true;
    }

    /**
     * Мапить safe marker помилки для операцій запису ОФСТ.
     */
    private function map_ofst_write_error_to_marker( string $db_error, string $fallback ): string {
        $normalized_error = strtolower( trim( $db_error ) );

        if ( '' === $normalized_error ) {
            return $fallback;
        }

        if ( str_contains( $normalized_error, 'duplicate entry' ) ) {
            return 'ofst_history_duplicate';
        }

        if ( str_contains( $normalized_error, 'cannot be null' ) || str_contains( $normalized_error, 'doesn\'t have a default value' ) ) {
            return 'ofst_history_required_field_missing';
        }

        if ( str_contains( $normalized_error, 'foreign key' ) || str_contains( $normalized_error, 'constraint fails' ) ) {
            return 'ofst_history_constraint_failed';
        }

        if ( str_contains( $normalized_error, 'incorrect datetime value' ) || str_contains( $normalized_error, 'invalid datetime format' ) ) {
            return 'ofst_history_invalid_datetime';
        }

        return $fallback;
    }

    /**
     * Повертає дані протоколу.
     *
     * @return array{rows: array<int, array<string, mixed>>, total: int}
     */
    public function get_protocol( string $search, int $per_page, int $offset, string $log_name ): array {
        global $wpdb;

        $count_where  = 'WHERE l.Logs_Name = %s';
        $list_where   = 'WHERE l.Logs_Name = %s';
        $count_params = [ $log_name ];
        $list_params  = [ $log_name ];

        if ( '' !== $search ) {
            $like     = '%' . $wpdb->esc_like( $search ) . '%';
            $count_where   .= ' AND (l.Logs_Text LIKE %s OR u.FIO LIKE %s)';
            $list_where    .= ' AND (l.Logs_Text LIKE %s OR u.FIO LIKE %s)';
            $count_params[] = $like;
            $count_params[] = $like;
            $list_params[]  = $like;
            $list_params[]  = $like;
        }

        $count_from_sql = '' !== $search
            ? 'FROM Logs l LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID'
            : 'FROM Logs l';

        $count_sql = "SELECT COUNT(*)
            {$count_from_sql}
            {$count_where}";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
        $total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$count_params ) );

        $list_sql = "SELECT l.User_ID, l.Logs_DateCreate, l.Logs_Type, l.Logs_Name, l.Logs_Text, l.Logs_Error, u.FIO, wu.display_name AS User_DisplayName, wu.user_login AS User_Login
            FROM Logs l
            LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
            LEFT JOIN {$wpdb->users} wu ON wu.ID = l.User_ID
            {$list_where}
            ORDER BY l.Logs_DateCreate DESC
            LIMIT %d OFFSET %d";

        $list_params = array_merge( $list_params, [ $per_page, $offset ] );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
        $rows = $wpdb->get_results( $wpdb->prepare( $list_sql, ...$list_params ), ARRAY_A ) ?: [];

        return [
            'rows'  => $rows,
            'total' => $total,
        ];
    }
}

