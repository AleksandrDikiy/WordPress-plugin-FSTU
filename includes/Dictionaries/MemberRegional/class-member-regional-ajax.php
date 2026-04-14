<?php
/**
 * AJAX-обробники модуля "Довідник посад федерацій".
 *
 * Version:     1.0.1
 * Date_update: 2026-04-14
 *
 * @package FSTU\Dictionaries\MemberRegional
 */

namespace FSTU\Dictionaries\MemberRegional;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Member_Regional_Ajax {

    private const DEFAULT_PER_PAGE      = 10;
    private const MAX_PER_PAGE          = 50;
    private const MAX_PROTOCOL_PER_PAGE = 50;
    private const LOG_NAME              = 'MemberRegional';
    private const PROTECTED_IDS         = [ 1 ];

    /**
     * Реєструє AJAX-обробники.
     */
    public function init(): void {
        add_action( 'wp_ajax_fstu_member_regional_get_list', [ $this, 'handle_get_list' ] );
        add_action( 'wp_ajax_fstu_member_regional_get_single', [ $this, 'handle_get_single' ] );
        add_action( 'wp_ajax_fstu_member_regional_create', [ $this, 'handle_create' ] );
        add_action( 'wp_ajax_fstu_member_regional_update', [ $this, 'handle_update' ] );
        add_action( 'wp_ajax_fstu_member_regional_delete', [ $this, 'handle_delete' ] );
        add_action( 'wp_ajax_fstu_member_regional_get_protocol', [ $this, 'handle_get_protocol' ] );
    }

    /**
     * Повертає список записів.
     */
    public function handle_get_list(): void {
        check_ajax_referer( Member_Regional_List::NONCE_ACTION, 'nonce' );

        if ( ! $this->current_user_can_view() ) {
            wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду довідника.', 'fstu' ) ] );
        }

        $search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
        $page     = max( 1, absint( $_POST['page'] ?? 1 ) );
        $per_page = min( max( 1, absint( $_POST['per_page'] ?? self::DEFAULT_PER_PAGE ) ), self::MAX_PER_PAGE );
        $offset   = ( $page - 1 ) * $per_page;

        global $wpdb;

        $where  = '1=1';
        $params = [];

        if ( '' !== $search ) {
            $like     = '%' . $wpdb->esc_like( $search ) . '%';
            $where   .= ' AND m.MemberRegional_Name LIKE %s';
            $params[] = $like;
        }

        $joins = "FROM S_MemberRegional m
			LEFT JOIN {$wpdb->users} u ON u.ID = m.UserCreate
			LEFT JOIN {$wpdb->usermeta} m1 ON m1.user_id = u.ID AND m1.meta_key = 'last_name'
			LEFT JOIN {$wpdb->usermeta} m2 ON m2.user_id = u.ID AND m2.meta_key = 'first_name'
			LEFT JOIN {$wpdb->usermeta} m3 ON m3.user_id = u.ID AND m3.meta_key = 'Patronymic'";

        $count_sql = "SELECT COUNT(m.MemberRegional_ID) {$joins} WHERE {$where}";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
        $total = (int) ( $params ? $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) ) : $wpdb->get_var( $count_sql ) );

        $list_sql = "SELECT m.MemberRegional_ID, m.MemberRegional_Name, m.MemberRegional_Order, m.MemberRegional_DateCreate,
			TRIM(CONCAT(IFNULL(m1.meta_value, ''), ' ', IFNULL(m2.meta_value, ''), ' ', IFNULL(m3.meta_value, ''))) AS FIO
			{$joins}
			WHERE {$where}
			ORDER BY m.MemberRegional_Order ASC, m.MemberRegional_Name ASC
			LIMIT %d OFFSET %d";

        $list_params = array_merge( $params, [ $per_page, $offset ] );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
        $items = $wpdb->get_results( $wpdb->prepare( $list_sql, ...$list_params ), ARRAY_A );

        wp_send_json_success(
            [
                'html'        => $this->build_rows( is_array( $items ) ? $items : [], $offset, $this->get_permissions() ),
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $per_page,
                'total_pages' => max( 1, (int) ceil( $total / max( 1, $per_page ) ) ),
            ]
        );
    }

    /**
     * Повертає один запис.
     */
    public function handle_get_single(): void {
        check_ajax_referer( Member_Regional_List::NONCE_ACTION, 'nonce' );

        if ( ! $this->current_user_can_view() ) {
            wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду запису.', 'fstu' ) ] );
        }

        $member_regional_id = absint( $_POST['member_regional_id'] ?? 0 );
        if ( $member_regional_id <= 0 ) {
            wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
        }

        $item = $this->get_member_regional_by_id( $member_regional_id );
        if ( ! is_array( $item ) ) {
            wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
        }

        $response = [
            'member_regional_id'    => (int) $item['MemberRegional_ID'],
            'member_regional_name'  => (string) $item['MemberRegional_Name'],
            'member_regional_order' => (int) ( $item['MemberRegional_Order'] ?? 0 ),
        ];

        if ( $this->current_user_can_admin_meta() ) {
            $response['member_regional_date_create'] = (string) ( $item['MemberRegional_DateCreate'] ?? '' );
            $response['member_regional_fio']         = (string) ( $item['FIO'] ?? '' );
        }

        wp_send_json_success( [ 'item' => $response ] );
    }

    /**
     * Створює запис.
     */
    public function handle_create(): void {
        check_ajax_referer( Member_Regional_List::NONCE_ACTION, 'nonce' );

        if ( ! $this->current_user_can_manage() ) {
            wp_send_json_error( [ 'message' => __( 'Недостатньо прав для додавання запису.', 'fstu' ) ] );
        }

        if ( ! $this->validate_honeypot() ) {
            wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
        }

        $data          = $this->sanitize_form_data();
        $error_message = $this->validate_form_data( $data );

        if ( '' !== $error_message ) {
            wp_send_json_error( [ 'message' => $error_message ] );
        }

        global $wpdb;

        $order = $data['member_regional_order'];
        if ( null === $order ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $order = (int) $wpdb->get_var( 'SELECT COALESCE(MAX(MemberRegional_Order), 0) FROM S_MemberRegional' ) + 1;
        }

        $inserted = $wpdb->insert(
            'S_MemberRegional',
            [
                'MemberRegional_DateCreate' => current_time( 'mysql' ),
                'UserCreate'               => get_current_user_id(),
                'MemberRegional_Name'      => $data['member_regional_name'],
                'MemberRegional_Order'     => $order,
            ],
            [ '%s', '%d', '%s', '%d' ]
        );

        if ( false === $inserted ) {
            $this->log_action( 'I', __( 'Помилка додавання посади федерації.', 'fstu' ), $wpdb->last_error ?: 'DB error' );
            wp_send_json_error( [ 'message' => __( 'Помилка при збереженні запису.', 'fstu' ) ] );
        }

        $this->log_action( 'I', sprintf( 'Додано посаду федерації: %s', $data['member_regional_name'] ), '✓' );

        wp_send_json_success( [ 'message' => __( 'Запис успішно додано.', 'fstu' ) ] );
    }

    /**
     * Оновлює запис.
     */
    public function handle_update(): void {
        check_ajax_referer( Member_Regional_List::NONCE_ACTION, 'nonce' );

        if ( ! $this->current_user_can_manage() ) {
            wp_send_json_error( [ 'message' => __( 'Недостатньо прав для редагування запису.', 'fstu' ) ] );
        }

        if ( ! $this->validate_honeypot() ) {
            wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
        }

        $member_regional_id = absint( $_POST['member_regional_id'] ?? 0 );
        $data               = $this->sanitize_form_data();
        $error_message      = $this->validate_form_data( $data, $member_regional_id );

        if ( $member_regional_id <= 0 ) {
            wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
        }

        if ( '' !== $error_message ) {
            wp_send_json_error( [ 'message' => $error_message ] );
        }

        $item = $this->get_member_regional_by_id( $member_regional_id );
        if ( ! is_array( $item ) ) {
            wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
        }

        global $wpdb;

        $order = null === $data['member_regional_order']
            ? (int) ( $item['MemberRegional_Order'] ?? 0 )
            : $data['member_regional_order'];

        $updated = $wpdb->update(
            'S_MemberRegional',
            [
                'MemberRegional_Name'  => $data['member_regional_name'],
                'MemberRegional_Order' => $order,
            ],
            [ 'MemberRegional_ID' => $member_regional_id ],
            [ '%s', '%d' ],
            [ '%d' ]
        );

        if ( false === $updated ) {
            $this->log_action( 'U', sprintf( 'Помилка оновлення посади федерації: %s', (string) $item['MemberRegional_Name'] ), $wpdb->last_error ?: 'DB error' );
            wp_send_json_error( [ 'message' => __( 'Помилка при збереженні запису.', 'fstu' ) ] );
        }

        $this->log_action( 'U', sprintf( 'Оновлено посаду федерації: %s', $data['member_regional_name'] ), '✓' );

        wp_send_json_success( [ 'message' => __( 'Запис успішно оновлено.', 'fstu' ) ] );
    }

    /**
     * Видаляє запис.
     */
    public function handle_delete(): void {
        check_ajax_referer( Member_Regional_List::NONCE_ACTION, 'nonce' );

        if ( ! $this->current_user_can_delete() ) {
            wp_send_json_error( [ 'message' => __( 'Недостатньо прав для видалення запису.', 'fstu' ) ] );
        }

        $member_regional_id = absint( $_POST['member_regional_id'] ?? 0 );
        if ( $member_regional_id <= 0 ) {
            wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
        }

        if ( in_array( $member_regional_id, self::PROTECTED_IDS, true ) ) {
            wp_send_json_error( [ 'message' => __( 'Цей системний запис не може бути видалений.', 'fstu' ) ] );
        }

        $item = $this->get_member_regional_by_id( $member_regional_id );
        if ( ! is_array( $item ) ) {
            wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
        }

        global $wpdb;
        $deleted = $wpdb->delete( 'S_MemberRegional', [ 'MemberRegional_ID' => $member_regional_id ], [ '%d' ] );

        if ( false === $deleted ) {
            $this->log_action( 'D', sprintf( 'Помилка видалення посади федерації: %s', (string) $item['MemberRegional_Name'] ), $wpdb->last_error ?: 'DB error' );
            wp_send_json_error( [ 'message' => __( 'Не вдалося видалити запис.', 'fstu' ) ] );
        }

        $this->log_action( 'D', sprintf( 'Видалено посаду федерації: %s', (string) $item['MemberRegional_Name'] ), '✓' );

        wp_send_json_success( [ 'message' => __( 'Запис успішно видалено.', 'fstu' ) ] );
    }

    /**
     * Повертає протокол модуля.
     */
    public function handle_get_protocol(): void {
        check_ajax_referer( Member_Regional_List::NONCE_ACTION, 'nonce' );

        if ( ! $this->current_user_can_protocol() ) {
            wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду протоколу.', 'fstu' ) ] );
        }

        $search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
        $page     = max( 1, absint( $_POST['page'] ?? 1 ) );
        $per_page = min( max( 1, absint( $_POST['per_page'] ?? self::DEFAULT_PER_PAGE ) ), self::MAX_PROTOCOL_PER_PAGE );
        $offset   = ( $page - 1 ) * $per_page;

        global $wpdb;

        $where  = 'WHERE l.Logs_Name = %s';
        $params = [ self::LOG_NAME ];

        if ( '' !== $search ) {
            $like     = '%' . $wpdb->esc_like( $search ) . '%';
            $where   .= ' AND (l.Logs_Text LIKE %s OR u.FIO LIKE %s)';
            $params[] = $like;
            $params[] = $like;
        }

        $count_sql = "SELECT COUNT(*)
			FROM Logs l
			LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
			{$where}";

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
        $total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );

        $list_sql = "SELECT l.Logs_DateCreate, l.Logs_Type, l.Logs_Name, l.Logs_Text, l.Logs_Error, u.FIO
			FROM Logs l
			LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
			{$where}
			ORDER BY l.Logs_DateCreate DESC
			LIMIT %d OFFSET %d";

        $list_params = array_merge( $params, [ $per_page, $offset ] );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
        $rows = $wpdb->get_results( $wpdb->prepare( $list_sql, ...$list_params ), ARRAY_A );

        wp_send_json_success(
            [
                'html'        => $this->build_protocol_rows( is_array( $rows ) ? $rows : [] ),
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $per_page,
                'total_pages' => max( 1, (int) ceil( $total / max( 1, $per_page ) ) ),
            ]
        );
    }

    /**
     * Будує HTML рядків таблиці.
     *
     * @param array<int,array<string,mixed>> $items       Рядки таблиці.
     * @param int                            $offset      Зсув пагінації.
     * @param array<string,bool>             $permissions Права поточного користувача.
     */
    private function build_rows( array $items, int $offset, array $permissions ): string {
        $show_admin_meta = ! empty( $permissions['canAdminMeta'] );
        $colspan         = $show_admin_meta ? 5 : 3;

        if ( empty( $items ) ) {
            return '<tr class="fstu-row"><td colspan="' . esc_attr( (string) $colspan ) . '" class="fstu-no-results">' . esc_html__( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ) . '</td></tr>';
        }

        $html  = '';
        $index = $offset;

        foreach ( $items as $item ) {
            ++$index;

            $member_regional_id   = (int) ( $item['MemberRegional_ID'] ?? 0 );
            $member_regional_name = (string) ( $item['MemberRegional_Name'] ?? '' );
            $date_create          = (string) ( $item['MemberRegional_DateCreate'] ?? '' );
            $fio                  = (string) ( $item['FIO'] ?? '' );
            $date_display         = '' !== $date_create ? wp_date( 'd.m.Y H:i', strtotime( $date_create ) ) : '';

            $actions   = [];
            $actions[] = '<button type="button" class="fstu-member-regional-dropdown__item fstu-member-regional-view-btn" data-member-regional-id="' . esc_attr( (string) $member_regional_id ) . '">' . esc_html__( 'Перегляд', 'fstu' ) . '</button>';

            if ( ! empty( $permissions['canManage'] ) ) {
                $actions[] = '<button type="button" class="fstu-member-regional-dropdown__item fstu-member-regional-edit-btn" data-member-regional-id="' . esc_attr( (string) $member_regional_id ) . '">' . esc_html__( 'Редагування', 'fstu' ) . '</button>';
            }

            if ( ! empty( $permissions['canDelete'] ) ) {
                $actions[] = '<button type="button" class="fstu-member-regional-dropdown__item fstu-member-regional-dropdown__item--danger fstu-member-regional-delete-btn" data-member-regional-id="' . esc_attr( (string) $member_regional_id ) . '">' . esc_html__( 'Видалення', 'fstu' ) . '</button>';
            }

            $html .= '<tr class="fstu-row">';
            $html .= '<td class="fstu-td fstu-td--num">' . esc_html( (string) $index ) . '</td>';
            $html .= '<td class="fstu-td fstu-td--name"><button type="button" class="fstu-member-regional-link-button fstu-member-regional-view-btn" data-member-regional-id="' . esc_attr( (string) $member_regional_id ) . '">' . esc_html( $member_regional_name ) . '</button></td>';

            if ( $show_admin_meta ) {
                $html .= '<td class="fstu-td fstu-td--date">' . ( '' !== $date_display ? esc_html( $date_display ) : '<span class="fstu-text-muted">—</span>' ) . '</td>';
                $html .= '<td class="fstu-td fstu-td--user">' . ( '' !== $fio ? esc_html( $fio ) : '<span class="fstu-text-muted">—</span>' ) . '</td>';
            }

            $html .= '<td class="fstu-td fstu-td--actions">';
            $html .= '<div class="fstu-member-regional-dropdown">';
            $html .= '<button type="button" class="fstu-member-regional-dropdown__toggle" aria-expanded="false" title="' . esc_attr__( 'Меню дій', 'fstu' ) . '">▼</button>';
            $html .= '<div class="fstu-member-regional-dropdown__menu">' . implode( '', $actions ) . '</div>';
            $html .= '</div>';
            $html .= '</td>';
            $html .= '</tr>';
        }

        return $html;
    }

    /**
     * Будує HTML рядків протоколу.
     *
     * @param array<int,array<string,mixed>> $items Рядки протоколу.
     */
    private function build_protocol_rows( array $items ): string {
        if ( empty( $items ) ) {
            return '<tr class="fstu-row"><td colspan="6" class="fstu-no-results">' . esc_html__( 'Записи протоколу відсутні.', 'fstu' ) . '</td></tr>';
        }

        $html = '';

        foreach ( $items as $item ) {
            $html .= '<tr class="fstu-row">';
            $html .= '<td class="fstu-td fstu-td--date">' . esc_html( (string) ( $item['Logs_DateCreate'] ?? '' ) ) . '</td>';
            $html .= '<td class="fstu-td fstu-td--type">' . esc_html( (string) ( $item['Logs_Type'] ?? '' ) ) . '</td>';
            $html .= '<td class="fstu-td fstu-td--operation">' . esc_html( (string) ( $item['Logs_Name'] ?? '' ) ) . '</td>';
            $html .= '<td class="fstu-td fstu-td--message">' . esc_html( (string) ( $item['Logs_Text'] ?? '' ) ) . '</td>';
            $html .= '<td class="fstu-td fstu-td--status">' . esc_html( (string) ( $item['Logs_Error'] ?? '' ) ) . '</td>';
            $html .= '<td class="fstu-td fstu-td--user">' . esc_html( (string) ( $item['FIO'] ?? '' ) ) . '</td>';
            $html .= '</tr>';
        }

        return $html;
    }

    /**
     * Повертає очищені дані форми.
     *
     * @return array<string,mixed>
     */
    private function sanitize_form_data(): array {
        $order_raw = sanitize_text_field( wp_unslash( $_POST['member_regional_order'] ?? '' ) );

        return [
            'member_regional_name'      => sanitize_text_field( wp_unslash( $_POST['member_regional_name'] ?? '' ) ),
            'member_regional_order_raw' => $order_raw,
            'member_regional_order'     => '' === $order_raw ? null : absint( $order_raw ),
        ];
    }

    /**
     * Валідує форму.
     *
     * @param array<string,mixed> $data               Дані форми.
     * @param int                 $member_regional_id Поточний ID для update.
     */
    private function validate_form_data( array $data, int $member_regional_id = 0 ): string {
        $name      = (string) ( $data['member_regional_name'] ?? '' );
        $order_raw = (string) ( $data['member_regional_order_raw'] ?? '' );

        if ( mb_strlen( $name ) < 2 ) {
            return __( 'Поле «Найменування» є обов’язковим.', 'fstu' );
        }

        if ( mb_strlen( $name ) > 255 ) {
            return __( 'Поле «Найменування» не може бути довшим за 255 символів.', 'fstu' );
        }

        if ( '' !== $order_raw && ! preg_match( '/^\d+$/', $order_raw ) ) {
            return __( 'Поле «Сортування» повинно містити лише невід’ємне число.', 'fstu' );
        }

        if ( $this->member_regional_name_exists( $name, $member_regional_id ) ) {
            return __( 'Запис з таким найменуванням уже існує.', 'fstu' );
        }

        return '';
    }

    /**
     * Перевіряє honeypot.
     */
    private function validate_honeypot(): bool {
        $honeypot = sanitize_text_field( wp_unslash( $_POST['fstu_website'] ?? '' ) );
        return '' === $honeypot;
    }

    /**
     * Повертає права для поточного користувача.
     *
     * @return array<string,bool>
     */
    private function get_permissions(): array {
        return Capabilities::get_member_regional_permissions();
    }

    /**
     * Чи може користувач переглядати модуль.
     */
    private function current_user_can_view(): bool {
        return Capabilities::current_user_can_view_member_regional();
    }

    /**
     * Чи може користувач керувати модулем.
     */
    private function current_user_can_manage(): bool {
        return Capabilities::current_user_can_manage_member_regional();
    }

    /**
     * Чи може користувач видаляти записи.
     */
    private function current_user_can_delete(): bool {
        return Capabilities::current_user_can_delete_member_regional();
    }

    /**
     * Чи може користувач переглядати протокол.
     */
    private function current_user_can_protocol(): bool {
        return Capabilities::current_user_can_view_member_regional_protocol();
    }

    /**
     * Чи може користувач бачити admin-only поля.
     */
    private function current_user_can_admin_meta(): bool {
        return Capabilities::current_user_can_delete_member_regional();
    }

    /**
     * Повертає запис довідника за ID.
     *
     * @return array<string,mixed>|null
     */
    private function get_member_regional_by_id( int $member_regional_id ): ?array {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $item = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT m.MemberRegional_ID, m.MemberRegional_Name, m.MemberRegional_Order, m.MemberRegional_DateCreate,
					TRIM(CONCAT(IFNULL(m1.meta_value, ''), ' ', IFNULL(m2.meta_value, ''), ' ', IFNULL(m3.meta_value, ''))) AS FIO
				 FROM S_MemberRegional m
				 LEFT JOIN {$wpdb->users} u ON u.ID = m.UserCreate
				 LEFT JOIN {$wpdb->usermeta} m1 ON m1.user_id = u.ID AND m1.meta_key = 'last_name'
				 LEFT JOIN {$wpdb->usermeta} m2 ON m2.user_id = u.ID AND m2.meta_key = 'first_name'
				 LEFT JOIN {$wpdb->usermeta} m3 ON m3.user_id = u.ID AND m3.meta_key = 'Patronymic'
				 WHERE m.MemberRegional_ID = %d
				 LIMIT 1",
                $member_regional_id
            ),
            ARRAY_A
        );

        return is_array( $item ) ? $item : null;
    }

    /**
     * Перевіряє, чи існує запис з таким найменуванням.
     */
    private function member_regional_name_exists( string $member_regional_name, int $exclude_id = 0 ): bool {
        global $wpdb;

        $sql    = 'SELECT MemberRegional_ID FROM S_MemberRegional WHERE MemberRegional_Name = %s';
        $params = [ $member_regional_name ];

        if ( $exclude_id > 0 ) {
            $sql     .= ' AND MemberRegional_ID != %d';
            $params[] = $exclude_id;
        }

        $sql .= ' LIMIT 1';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $existing_id = $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );

        return null !== $existing_id;
    }

    /**
     * Записує подію у таблицю Logs.
     */
    private function log_action( string $type, string $text, string $status ): void {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
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
}