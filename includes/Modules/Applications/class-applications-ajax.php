<?php

namespace FSTU\Modules\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Клас обробки AJAX запитів модуля "Заявки в ФСТУ".
 * Фаза 4: CRUD операції, Транзакції та Логування.
 *
 * Version:     1.3.1
 * Date_update: 2026-04-06
 */
class Applications_Ajax {

    private const LOG_NAME = 'Applications';
    private const MAX_PER_PAGE = 50;

    public function init(): void {
        add_action( 'wp_ajax_fstu_applications_get_list',    [ $this, 'handle_get_list' ] );
        add_action( 'wp_ajax_fstu_applications_get_single',  [ $this, 'handle_get_single' ] );
        add_action( 'wp_ajax_fstu_applications_vote',        [ $this, 'handle_vote' ] );
        add_action( 'wp_ajax_fstu_applications_accept',      [ $this, 'handle_accept' ] );
        add_action( 'wp_ajax_fstu_applications_change_ofst', [ $this, 'handle_change_ofst' ] );
        add_action( 'wp_ajax_fstu_applications_send_email',  [ $this, 'handle_send_message' ] );
        add_action( 'wp_ajax_fstu_applications_reject',      [ $this, 'handle_reject' ] );
        add_action( 'wp_ajax_fstu_applications_get_protocol', [ $this, 'handle_get_protocol' ] );
    }

    /**
     * [КРИТИЧНО] Приватний метод логування згідно AGENTS.md
     */
    private function log_action( string $type, string $text, string $status ): void {
        global $wpdb;
        $wpdb->insert(
            'Logs',
            [
                'User_ID'         => get_current_user_id(),
                'Logs_DateCreate' => current_time( 'mysql' ),
                'Logs_Type'       => $type,
                'Logs_Name'       => self::LOG_NAME,
                'Logs_Text'       => $text,
                'Logs_Error'      => $status,
            ],
            [ '%d', '%s', '%s', '%s', '%s', '%s' ]
        );
    }

    /**
     * Чи може користувач керувати заявками.
     */
    private function current_user_can_manage_applications(): bool {
        $user  = wp_get_current_user();
        $roles = is_array( $user->roles ) ? $user->roles : [];

        return current_user_can( 'manage_options' )
            || in_array( 'administrator', $roles, true )
            || in_array( 'userregistrar', $roles, true );
    }

    /**
     * Чи є поточний користувач адміністратором модуля.
     */
    private function current_user_is_administrator(): bool {
        $user  = wp_get_current_user();
        $roles = is_array( $user->roles ) ? $user->roles : [];

        return current_user_can( 'manage_options' ) || in_array( 'administrator', $roles, true );
    }

    /**
     * Отримання списку заявок для таблиці.
     */
    public function handle_get_list(): void {
        check_ajax_referer( Applications_List::NONCE_ACTION, 'nonce' );

        if ( ! $this->current_user_can_manage_applications() ) {
            wp_send_json_error( [ 'message' => 'Недостатньо прав для перегляду заявок.' ] );
        }

        $search    = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
        $page      = max( 1, absint( $_POST['page'] ?? 1 ) );
        $per_page  = max( 1, min( self::MAX_PER_PAGE, absint( $_POST['per_page'] ?? 10 ) ?: 10 ) );
        $region_id = absint( $_POST['region_id'] ?? 0 );
        $unit_id   = absint( $_POST['unit_id'] ?? 0 );
        $offset    = ( $page - 1 ) * $per_page;

        global $wpdb;

        $where_clauses = [];
        $params        = [];

        if ( '' !== $search ) {
            $like            = '%' . $wpdb->esc_like( $search ) . '%';
            $where_clauses[] = "(CONCAT_WS(' ', NULLIF(m_ln.meta_value, ''), NULLIF(m_fn.meta_value, ''), NULLIF(m_pt.meta_value, '')) LIKE %s OR u.display_name LIKE %s OR u.user_email LIKE %s)";
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

        $base_sql = "
            FROM vUserBlocked vb
            INNER JOIN {$wpdb->users} u ON u.ID = vb.User_ID
            LEFT JOIN {$wpdb->usermeta} m_ln ON (m_ln.user_id = u.ID AND m_ln.meta_key = 'last_name')
            LEFT JOIN {$wpdb->usermeta} m_fn ON (m_fn.user_id = u.ID AND m_fn.meta_key = 'first_name')
            LEFT JOIN {$wpdb->usermeta} m_pt ON (m_pt.user_id = u.ID AND m_pt.meta_key = 'Patronymic')
            LEFT JOIN (
                SELECT u1.*
                FROM UserRegistationOFST u1
                INNER JOIN (
                    SELECT User_ID, MAX(UserRegistationOFST_DateCreate) AS max_date
                    FROM UserRegistationOFST
                    GROUP BY User_ID
                ) u2 ON u1.User_ID = u2.User_ID AND u1.UserRegistationOFST_DateCreate = u2.max_date
            ) ur ON ur.User_ID = u.ID
            LEFT JOIN S_Unit su ON su.Unit_ID = ur.Unit_ID
            LEFT JOIN S_Region sr ON sr.Region_ID = ur.Region_ID
            {$where_sql}
        ";

        $count_sql = "SELECT COUNT(DISTINCT u.ID) {$base_sql}";
        if ( ! empty( $params ) ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
            $total = (int) $wpdb->get_var( $count_sql );
        }

        $select_sql = "
            SELECT DISTINCT
                u.ID AS user_id,
                u.user_registered,
                u.user_email,
                u.display_name,
                u.user_login,
                TRIM(CONCAT_WS(' ', NULLIF(m_ln.meta_value, ''), NULLIF(m_fn.meta_value, ''), NULLIF(m_pt.meta_value, ''))) AS fio,
                IFNULL(su.Unit_ShortName, '') AS unit_short_name,
                IFNULL(sr.Region_Name, '') AS region_name
            {$base_sql}
            ORDER BY u.user_registered DESC, u.ID DESC
            LIMIT %d OFFSET %d
        ";

        $data_params = array_merge( $params, [ $per_page, $offset ] );
        $prepared_sql = ! empty( $params )
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            ? $wpdb->prepare( $select_sql, ...$data_params )
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            : $wpdb->prepare( $select_sql, $per_page, $offset );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
        $rows = $wpdb->get_results( $prepared_sql, ARRAY_A ) ?: [];

        wp_send_json_success(
            [
                'html'        => $this->render_list_rows( $rows, $offset ),
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $per_page,
                'total_pages' => (int) ceil( $total / max( 1, $per_page ) ),
            ]
        );
    }

    /**
     * Формує HTML рядків таблиці заявок.
     */
    private function render_list_rows( array $rows, int $offset ): string {
        if ( empty( $rows ) ) {
            return '<tr><td colspan="8" class="fstu-applications-empty">Заявок не знайдено.</td></tr>';
        }

        $html     = '';
        $is_admin = $this->current_user_is_administrator();

        foreach ( $rows as $index => $row ) {
            $user_id      = (int) ( $row['user_id'] ?? 0 );
            $fio          = trim( (string) ( $row['fio'] ?? '' ) );
            $display_name = trim( (string) ( $row['display_name'] ?? '' ) );
            $user_login   = trim( (string) ( $row['user_login'] ?? '' ) );
            $fio          = '' !== $fio ? $fio : $display_name;
            $fio          = '' !== $fio ? $fio : $user_login;
            $fio          = '' !== $fio ? $fio : 'Без ПІБ';
            $email        = (string) ( $row['user_email'] ?? '' );
            $unit_name    = (string) ( $row['unit_short_name'] ?? '' );
            $region_name  = (string) ( $row['region_name'] ?? '' );
            $date_raw     = (string) ( $row['user_registered'] ?? '' );
            $date         = '' !== $date_raw ? wp_date( 'd.m.Y H:i', strtotime( $date_raw ) ) : '—';
            $row_number   = $offset + $index + 1;
            $actions      = $this->render_actions_dropdown( $user_id, $is_admin );

            $html .= sprintf(
                '<tr class="fstu-row"><td class="fstu-td fstu-td--num">%1$d</td><td class="fstu-td">%2$s</td><td class="fstu-td">%3$s</td><td class="fstu-td">%4$s</td><td class="fstu-td">%5$s</td><td class="fstu-td">%6$s</td><td class="fstu-td fstu-th--center">—</td><td class="fstu-td fstu-td--actions">%7$s</td></tr>',
                $row_number,
                esc_html( $date ),
                esc_html( $fio ),
                esc_html( $email ?: '—' ),
                esc_html( $unit_name ?: '—' ),
                esc_html( $region_name ?: '—' ),
                $actions
            );
        }

        return $html;
    }

    /**
     * Формує dropdown дій для рядка заявки.
     */
    private function render_actions_dropdown( int $user_id, bool $is_admin ): string {
        if ( $user_id <= 0 ) {
            return '—';
        }

        $items = [
            sprintf(
                '<button type="button" class="fstu-applications-dropdown__item fstu-action-view" data-id="%1$d">%2$s</button>',
                $user_id,
                esc_html__( 'Перегляд', 'fstu' )
            ),
            sprintf(
                '<button type="button" class="fstu-applications-dropdown__item fstu-action-accept" data-id="%1$d">%2$s</button>',
                $user_id,
                esc_html__( 'Прийняти', 'fstu' )
            ),
        ];

        if ( $is_admin ) {
            $items[] = sprintf(
                '<button type="button" class="fstu-applications-dropdown__item fstu-applications-dropdown__item--danger fstu-action-reject" data-id="%1$d">%2$s</button>',
                $user_id,
                esc_html__( 'Відхилити', 'fstu' )
            );
        }

        return '<div class="fstu-applications-dropdown"><button type="button" class="fstu-applications-dropdown__toggle" aria-expanded="false" title="' . esc_attr__( 'Меню дій', 'fstu' ) . '">▼</button><div class="fstu-applications-dropdown__menu">' . implode( '', $items ) . '</div></div>';
    }

    /**
     * Повертає безпечний fallback для імені користувача в протоколі.
     */
    private function get_protocol_user_label( array $row ): string {
        $fio = trim( (string) ( $row['FIO'] ?? '' ) );
        if ( '' !== $fio ) {
            return $fio;
        }

        $user_id = absint( $row['User_ID'] ?? 0 );
        if ( $user_id > 0 ) {
            $user = get_userdata( $user_id );
            if ( $user instanceof \WP_User ) {
                if ( ! empty( $user->display_name ) ) {
                    return (string) $user->display_name;
                }

                if ( ! empty( $user->user_login ) ) {
                    return (string) $user->user_login;
                }
            }

            return 'ID:' . $user_id;
        }

        return 'Система';
    }

    /**
     * Тимчасовий заглушковий обробник голосування.
     */
    public function handle_vote(): void {
        check_ajax_referer( Applications_List::NONCE_ACTION, 'nonce' );

        if ( ! $this->current_user_can_manage_applications() ) {
            wp_send_json_error( [ 'message' => 'Недостатньо прав.' ] );
        }

        wp_send_json_error( [ 'message' => 'Функціонал голосування ще не реалізовано.' ] );
    }

    /**
     * Тимчасовий заглушковий обробник відправлення повідомлень.
     */
    public function handle_send_message(): void {
        check_ajax_referer( Applications_List::NONCE_ACTION, 'nonce' );

        if ( ! $this->current_user_can_manage_applications() ) {
            wp_send_json_error( [ 'message' => 'Недостатньо прав.' ] );
        }

        wp_send_json_error( [ 'message' => 'Функціонал відправлення повідомлень ще не реалізовано.' ] );
    }

    /**
     * Отримання даних одного кандидата для модального вікна
     */
    public function handle_get_single(): void {
        check_ajax_referer( Applications_List::NONCE_ACTION, 'nonce' );

        if ( ! $this->current_user_can_manage_applications() ) {
            wp_send_json_error( [ 'message' => 'Недостатньо прав.' ] );
        }

        $user_id = absint( wp_unslash( $_POST['id'] ?? 0 ) );

        global $wpdb;
        $data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM vUserBlocked WHERE User_ID = %d", $user_id ), ARRAY_A );

        if ( ! $data ) {
            wp_send_json_error( [ 'message' => 'Кандидата не знайдено.' ] );
        }

        // Додаємо мета-дані, які не входять у View
        $data['adr']   = get_user_meta( $user_id, 'Adr', true );
        $data['phone'] = get_user_meta( $user_id, 'PhoneMobile', true );

        wp_send_json_success( $data );
    }

    /**
     * Фінальне прийняття в члени ФСТУ (Транзакція)
     */
    public function handle_accept(): void {
        check_ajax_referer( Applications_List::NONCE_ACTION, 'nonce' );

        if ( ! $this->current_user_can_manage_applications() ) {
            wp_send_json_error( [ 'message' => 'Недостатньо прав.' ] );
        }

        $user_id = absint( wp_unslash( $_POST['id'] ?? 0 ) );
        global $wpdb;

        // [Покращення 1] Початок транзакції
        $wpdb->query( 'START TRANSACTION' );

        try {
            $user_obj = get_userdata( $user_id );
            if ( ! $user_obj ) throw new \Exception( 'Користувача не існує.' );

            // 1. Зміна ролі
            $user_obj->set_role( 'userfstu' );

            // 2. Генерація номера картки (на основі регіону)
            $region_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT Region_ID FROM vUserRegistationOFST WHERE User_ID = %d", $user_id ) );
            $next_num  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(MAX(UserMemberCard_Number), 0) + 1 FROM UserMemberCard WHERE Region_ID = %d", $region_id ) );

            // 3. Створення запису про картку
            $inserted = $wpdb->insert( 'UserMemberCard', [
                'UserMemberCard_DateCreate' => current_time( 'mysql' ),
                'UserCreate'                => get_current_user_id(),
                'User_ID'                   => $user_id,
                'TypeCard_ID'               => 1,
                'StatusCard_ID'             => 1,
                'Region_ID'                 => $region_id,
                'UserMemberCard_Number'     => $next_num,
                'UserMemberCard_LastName'   => get_user_meta( $user_id, 'last_name', true ),
                'UserMemberCard_FirstName'  => get_user_meta( $user_id, 'first_name', true )
            ] );

            if ( ! $inserted ) throw new \Exception( 'Помилка створення картки.' );

            $wpdb->query( 'COMMIT' );

            // [Покращення 2 & 4] Відправка Email
            $from_email = $wpdb->get_var(
                $wpdb->prepare( 'SELECT ParamValue FROM Settings WHERE ParamName = %s', 'EmailFSTU' )
            );
            $from_email = is_email( $from_email ) ? $from_email : get_option( 'admin_email' );
            $subject    = "Вас прийнято до членів ФСТУ";
            $message    = "Вітаємо! Вашу заявку схвалено. Ваш номер квитка: " . $next_num;
            wp_mail( $user_obj->user_email, $subject, $message, [ 'From: ' . $from_email ] );

            $this->log_action( 'UPDATE', "Користувача ID:{$user_id} прийнято в члени ФСТУ. Картка №{$next_num}", '✓' );
            wp_send_json_success( [ 'message' => 'Користувача успішно прийнято!' ] );

        } catch ( \Exception $e ) {
            $wpdb->query( 'ROLLBACK' );
            $this->log_action( 'UPDATE', "Помилка прийняття ID:{$user_id}: " . $e->getMessage(), 'error' );
            wp_send_json_error( [ 'message' => 'Сталася помилка при обробці заявки.' ] );
        }
    }

    /**
     * [Покращення 3] Відхилення заявки (Soft Delete)
     */
    public function handle_reject(): void {
        check_ajax_referer( Applications_List::NONCE_ACTION, 'nonce' );
        if ( ! $this->current_user_is_administrator() ) {
            wp_send_json_error( [ 'message' => 'Тільки адміністратор може відхиляти заявку.' ] );
        }

        $user_id = absint( wp_unslash( $_POST['id'] ?? 0 ) );
        $user_obj = get_userdata( $user_id );

        if ( $user_obj ) {
            $user_obj->set_role( 'rejected_applicant' ); // М'яке видалення
            $this->log_action( 'DELETE', "Заявку ID:{$user_id} відхилено (Soft Delete)", '✓' );
            wp_send_json_success( [ 'message' => 'Заявку відхилено.' ] );
        }
        wp_send_json_error( [ 'message' => 'Користувача не знайдено.' ] );
    }

    /**
     * Зміна ОФСТ (Підрозділу)
     */
    public function handle_change_ofst(): void {
        check_ajax_referer( Applications_List::NONCE_ACTION, 'nonce' );

        if ( ! $this->current_user_can_manage_applications() ) {
            wp_send_json_error( [ 'message' => 'Недостатньо прав.' ] );
        }

        $user_id = absint( wp_unslash( $_POST['user_id'] ?? 0 ) );
        $unit_id = absint( wp_unslash( $_POST['unit_id'] ?? 0 ) );

        if ( $user_id <= 0 || $unit_id <= 0 ) {
            wp_send_json_error( [ 'message' => 'Невірні параметри оновлення.' ] );
        }

        global $wpdb;
        $unit_row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT Unit_ID, Region_ID, Unit_ShortName FROM S_Unit WHERE Unit_ID = %d LIMIT 1",
                $unit_id
            ),
            ARRAY_A
        );

        if ( ! is_array( $unit_row ) ) {
            wp_send_json_error( [ 'message' => 'Обраний осередок не знайдено.' ] );
        }

        $region_id = (int) ( $unit_row['Region_ID'] ?? 0 );
        if ( $region_id <= 0 ) {
            $this->log_action( 'UPDATE', "Помилка зміни ОФСТ для користувача ID:{$user_id}: не знайдено Region_ID для Unit:{$unit_id}", 'invalid_region' );
            wp_send_json_error( [ 'message' => 'Для обраного осередку не визначено область.' ] );
        }

        $current_row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT Unit_ID, Region_ID FROM UserRegistationOFST WHERE User_ID = %d LIMIT 1",
                $user_id
            ),
            ARRAY_A
        );

        if ( ! is_array( $current_row ) ) {
            $this->log_action( 'UPDATE', "Помилка зміни ОФСТ для користувача ID:{$user_id}: запис UserRegistationOFST не знайдено", 'not_found' );
            wp_send_json_error( [ 'message' => 'Запис прив’язки користувача до осередку не знайдено.' ] );
        }

        $current_unit_id   = (int) ( $current_row['Unit_ID'] ?? 0 );
        $current_region_id = (int) ( $current_row['Region_ID'] ?? 0 );

        if ( $current_unit_id === $unit_id && $current_region_id === $region_id ) {
            $this->log_action( 'UPDATE', "Спроба зміни ОФСТ для користувача ID:{$user_id}: змін не виявлено", 'no_changes' );
            wp_send_json_success( [ 'message' => 'Змін не виявлено.' ] );
        }

        $updated = $wpdb->update(
            'UserRegistationOFST',
            [ 'Unit_ID' => $unit_id, 'Region_ID' => $region_id ],
            [ 'User_ID' => $user_id ],
            [ '%d', '%d' ],
            [ '%d' ]
        );

        if ( false === $updated ) {
            $this->log_action( 'UPDATE', "Помилка зміни ОФСТ для користувача ID:{$user_id} на Unit:{$unit_id}", 'error' );
            wp_send_json_error( [ 'message' => 'Помилка оновлення.' ] );
        }

        if ( 0 === (int) $updated ) {
            $this->log_action( 'UPDATE', "Спроба зміни ОФСТ для користувача ID:{$user_id}: змін не виявлено", 'no_changes' );
            wp_send_json_success( [ 'message' => 'Змін не виявлено.' ] );
        }

        if ( ! empty( $unit_row['Unit_ShortName'] ) ) {
            $this->log_action( 'UPDATE', "Змінено ОФСТ для користувача ID:{$user_id} на осередок {$unit_row['Unit_ShortName']} (Unit:{$unit_id})", '✓' );
        } else {
            $this->log_action( 'UPDATE', "Змінено ОФСТ для користувача ID:{$user_id} на Unit:{$unit_id}", '✓' );
        }

            wp_send_json_success( [ 'message' => 'Осередки оновлено.' ] );
    }

    /**
     * Отримання журналу операцій (Протоколу)
     */
    public function handle_get_protocol(): void {
        check_ajax_referer( Applications_List::NONCE_ACTION, 'nonce' );

        // Згідно з правилами, тільки адміністратори мають доступ до перегляду всіх логів
        if ( ! $this->current_user_is_administrator() ) {
            wp_send_json_error( [ 'message' => 'Немає прав для перегляду протоколу.' ] );
        }

        global $wpdb;

        $search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
        $page     = max( 1, absint( $_POST['page'] ?? 1 ) );
        $per_page = max( 1, min( self::MAX_PER_PAGE, absint( $_POST['per_page'] ?? 10 ) ?: 10 ) );
        $offset   = ( $page - 1 ) * $per_page;

        // Базова умова: шукаємо тільки логи поточного модуля
        $where_clauses = [ "l.Logs_Name = %s" ];
        $params        = [ self::LOG_NAME ];

        // Пермісивний пошук по тексту операції АБО ПІБ користувача
        if ( '' !== $search ) {
            $where_clauses[] = "(l.Logs_Text LIKE %s OR u.FIO LIKE %s)";
            $like_search     = '%' . $wpdb->esc_like( $search ) . '%';
            $params[]        = $like_search;
            $params[]        = $like_search;
        }

        $where_sql = implode( ' AND ', $where_clauses );

        // 1. Рахуємо загальну кількість для пагінації
        $count_sql = "SELECT COUNT(l.Logs_ID) 
		              FROM Logs l 
		              LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID 
		              WHERE {$where_sql}";
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );

        // 2. Отримуємо самі дані
        $data_sql = "SELECT l.User_ID, l.Logs_DateCreate, l.Logs_Type, l.Logs_Name, l.Logs_Text, l.Logs_Error, u.FIO 
		             FROM Logs l 
		             LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID 
		             WHERE {$where_sql} 
		             ORDER BY l.Logs_DateCreate DESC 
		             LIMIT %d OFFSET %d";

        $data_params = array_merge( $params, [ $per_page, $offset ] );
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $rows = $wpdb->get_results( $wpdb->prepare( $data_sql, ...$data_params ), ARRAY_A );

        // 3. Формуємо HTML
        $html = '';
        if ( empty( $rows ) ) {
            $html = '<tr><td colspan="6" style="text-align:center; padding: 20px; color: #7f8c8d;">Записів у протоколі не знайдено.</td></tr>';
        } else {
            foreach ( $rows as $row ) {
                $date    = esc_html( gmdate( 'd.m.Y H:i', strtotime( $row['Logs_DateCreate'] ) ) );
                $type    = esc_html( $row['Logs_Type'] );
                $message = esc_html( $row['Logs_Text'] );
                $status  = esc_html( $row['Logs_Error'] );
                $fio     = esc_html( $this->get_protocol_user_label( $row ) );

                // Красива стилізація статусу
                $status_html = ( $status === '✓' )
                    ? '<span style="color: #27ae60; font-weight: bold;">✓</span>'
                    : '<span style="color: #e74c3c; font-size: 11px;">' . $status . '</span>';

                // Кольорові бейджі для типів операцій
                $type_color = match( $type ) {
                    'INSERT' => '#27ae60', // Зелений
                    'UPDATE' => '#f39c12', // Помаранчевий
                    'DELETE' => '#e74c3c', // Червоний
                    default  => '#7f8c8d', // Сірий
                };
                $type_html = '<span style="background: ' . $type_color . '; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: 600;">' . $type . '</span>';

                $html .= "<tr>
					<td>{$date}</td>
					<td style=\"text-align:center;\">{$type_html}</td>
					<td style=\"font-weight: 500;\">" . esc_html( self::LOG_NAME ) . "</td>
					<td>{$message}</td>
					<td style=\"text-align:center;\">{$status_html}</td>
					<td>{$fio}</td>
				</tr>";
            }
        }

        wp_send_json_success( [
            'html'        => $html,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => (int) ceil( $total / max( 1, $per_page ) ),
        ] );
    }
    //
}