<?php
/**
 * AJAX-обробник для довідника типів вітрильних залікових груп.
 * * Version: 1.0.0
 * Date_update: 2026-04-15
 */

namespace FSTU\Dictionaries\SailGroupType;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Class_Sail_Group_Type_Ajax {

    private const LOG_NAME = 'SailGroupType';

    public function __construct() {
        // Хуки для Типів (S_SailGroupType)
        add_action( 'wp_ajax_fstu_sgt_get_types', [ $this, 'get_types' ] );
        add_action( 'wp_ajax_fstu_sgt_create_type', [ $this, 'create_type' ] );
        add_action( 'wp_ajax_fstu_sgt_update_type', [ $this, 'update_type' ] );
        add_action( 'wp_ajax_fstu_sgt_delete_type', [ $this, 'delete_type' ] );
        add_action( 'wp_ajax_fstu_sgt_reorder_types', [ $this, 'reorder_types' ] );

        // Хуки для Підгруп (S_SailGroup)
        add_action( 'wp_ajax_fstu_sgt_get_groups', [ $this, 'get_groups' ] );
        add_action( 'wp_ajax_fstu_sgt_create_group', [ $this, 'create_group' ] );
        add_action( 'wp_ajax_fstu_sgt_update_group', [ $this, 'update_group' ] );
        add_action( 'wp_ajax_fstu_sgt_delete_group', [ $this, 'delete_group' ] );

        // Протокол
        add_action( 'wp_ajax_fstu_sgt_get_protocol', [ $this, 'get_protocol' ] );
    }

    /**
     * Перевірка Nonce та прав доступу.
     */
    private function verify_request( string $action = 'canManage' ): void {
        check_ajax_referer( 'fstu_module_nonce', 'nonce' );
        $permissions = Capabilities::get_sail_group_type_permissions();
        if ( empty( $permissions[ $action ] ) ) {
            wp_send_json_error( [ 'message' => __( 'Недостатньо прав для виконання цієї дії.', 'fstu' ) ] );
        }
    }

    // ==========================================================================
    // 1. РОБОТА З ТИПАМИ (S_SailGroupType)
    // ==========================================================================

    public function get_types(): void {
        $this->verify_request( 'canView' );
        global $wpdb;

        $page     = max( 1, isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1 );
        $per_page = max( 1, isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 10 );
        $search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
        $offset   = ( $page - 1 ) * $per_page;

        $where_sql = '1=1';
        $args      = [];

        if ( ! empty( $search ) ) {
            $where_sql .= " AND SailGroupType_Name LIKE %s";
            $args[] = '%' . $wpdb->esc_like( $search ) . '%';
        }

        $query = "SELECT SQL_CALC_FOUND_ROWS SailGroupType_ID as id, SailGroupType_Name as name, SailGroupType_Order as `order`
				  FROM S_SailGroupType
				  WHERE {$where_sql}
				  ORDER BY SailGroupType_Order ASC, SailGroupType_ID ASC
				  LIMIT %d OFFSET %d";

        $args[] = $per_page;
        $args[] = $offset;

        $items = $wpdb->get_results( $wpdb->prepare( $query, $args ), ARRAY_A );
        $total = (int) $wpdb->get_var( 'SELECT FOUND_ROWS()' );

        wp_send_json_success( [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total / $per_page ),
        ] );
    }

    public function create_type(): void {
        $this->verify_request( 'canManage' );
        global $wpdb;

        if ( ! empty( $_POST['fstu_website'] ) ) {
            wp_send_json_error( [ 'message' => 'Spam detected.' ] );
        }

        $name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        if ( empty( $name ) ) {
            wp_send_json_error( [ 'message' => __( 'Найменування не може бути порожнім.', 'fstu' ) ] );
        }

        // Перевірка на унікальність
        $exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM S_SailGroupType WHERE SailGroupType_Name = %s", $name ) );
        if ( $exists > 0 ) {
            wp_send_json_error( [ 'message' => __( 'Такий тип залікової групи вже існує.', 'fstu' ) ] );
        }

        $max_order = (int) $wpdb->get_var( "SELECT MAX(SailGroupType_Order) FROM S_SailGroupType" );
        $new_order = $max_order + 10;

        $wpdb->query( 'START TRANSACTION' );

        $inserted = $wpdb->insert(
            'S_SailGroupType',
            [
                'SailGroupType_DateCreate' => current_time( 'mysql' ),
                'SailGroupType_Name'       => $name,
                'SailGroupType_Order'      => $new_order,
            ],
            [ '%s', '%s', '%d' ]
        );

        if ( $inserted ) {
            $this->log_action( 'I', "Додано тип залікової групи: {$name}", '✓' );
            $wpdb->query( 'COMMIT' );
            wp_send_json_success( [ 'message' => __( 'Тип успішно додано.', 'fstu' ) ] );
        } else {
            $wpdb->query( 'ROLLBACK' );
            wp_send_json_error( [ 'message' => __( 'Помилка збереження типу.', 'fstu' ) ] );
        }
    }

    public function update_type(): void {
        $this->verify_request( 'canManage' );
        global $wpdb;

        $id   = absint( $_POST['id'] ?? 0 );
        $name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );

        if ( $id <= 0 || empty( $name ) ) {
            wp_send_json_error( [ 'message' => __( 'Некоректні дані.', 'fstu' ) ] );
        }

        $exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM S_SailGroupType WHERE SailGroupType_Name = %s AND SailGroupType_ID != %d", $name, $id ) );
        if ( $exists > 0 ) {
            wp_send_json_error( [ 'message' => __( 'Такий тип залікової групи вже існує.', 'fstu' ) ] );
        }

        $wpdb->query( 'START TRANSACTION' );

        $updated = $wpdb->update(
            'S_SailGroupType',
            [ 'SailGroupType_Name' => $name ],
            [ 'SailGroupType_ID' => $id ],
            [ '%s' ],
            [ '%d' ]
        );

        if ( $updated !== false ) {
            $this->log_action( 'U', "Оновлено тип залікової групи [ID {$id}]: {$name}", '✓' );
            $wpdb->query( 'COMMIT' );
            wp_send_json_success( [ 'message' => __( 'Тип успішно оновлено.', 'fstu' ) ] );
        } else {
            $wpdb->query( 'ROLLBACK' );
            wp_send_json_error( [ 'message' => __( 'Помилка оновлення типу.', 'fstu' ) ] );
        }
    }

    public function delete_type(): void {
        $this->verify_request( 'canDelete' );
        global $wpdb;

        $id = absint( $_POST['id'] ?? 0 );
        if ( $id <= 0 ) {
            wp_send_json_error( [ 'message' => __( 'Некоректний ID.', 'fstu' ) ] );
        }

        // Захист від сиротинців
        $has_children = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM S_SailGroup WHERE SailGroupType_ID = %d", $id ) );
        if ( $has_children > 0 ) {
            wp_send_json_error( [ 'message' => __( 'Неможливо видалити цей тип, оскільки до нього прив\'язані залікові групи. Спочатку видаліть їх.', 'fstu' ) ] );
        }

        $name = (string) $wpdb->get_var( $wpdb->prepare( "SELECT SailGroupType_Name FROM S_SailGroupType WHERE SailGroupType_ID = %d", $id ) );

        $wpdb->query( 'START TRANSACTION' );

        $deleted = $wpdb->delete( 'S_SailGroupType', [ 'SailGroupType_ID' => $id ], [ '%d' ] );

        if ( $deleted ) {
            $this->log_action( 'D', "Видалено тип залікової групи: {$name} (ID: {$id})", '✓' );
            $wpdb->query( 'COMMIT' );
            wp_send_json_success( [ 'message' => __( 'Тип успішно видалено.', 'fstu' ) ] );
        } else {
            $wpdb->query( 'ROLLBACK' );
            wp_send_json_error( [ 'message' => __( 'Помилка видалення.', 'fstu' ) ] );
        }
    }

    public function reorder_types(): void {
        $this->verify_request( 'canManage' );
        global $wpdb;

        $order_data = isset( $_POST['order_data'] ) ? (array) $_POST['order_data'] : [];
        if ( empty( $order_data ) ) {
            wp_send_json_error( [ 'message' => __( 'Немає даних для сортування.', 'fstu' ) ] );
        }

        $wpdb->query( 'START TRANSACTION' );
        $has_errors = false;

        foreach ( $order_data as $item ) {
            $id    = absint( $item['id'] ?? 0 );
            $order = absint( $item['order'] ?? 0 );

            if ( $id > 0 ) {
                $res = $wpdb->update( 'S_SailGroupType', [ 'SailGroupType_Order' => $order ], [ 'SailGroupType_ID' => $id ], [ '%d' ], [ '%d' ] );
                if ( $res === false ) {
                    $has_errors = true;
                }
            }
        }

        if ( ! $has_errors ) {
            $this->log_action( 'U', 'Оновлено сортування типів вітрильних груп (Drag & Drop)', '✓' );
            $wpdb->query( 'COMMIT' );
            wp_send_json_success();
        } else {
            $wpdb->query( 'ROLLBACK' );
            wp_send_json_error( [ 'message' => __( 'Помилка при збереженні сортування.', 'fstu' ) ] );
        }
    }

    // ==========================================================================
    // 2. РОБОТА З ПІДГРУПАМИ (S_SailGroup)
    // ==========================================================================

    public function get_groups(): void {
        $this->verify_request( 'canView' );
        global $wpdb;

        $type_id  = absint( $_POST['type_id'] ?? 0 );
        $page     = max( 1, isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1 );
        $per_page = max( 1, isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 10 );
        $search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
        $offset   = ( $page - 1 ) * $per_page;

        if ( $type_id <= 0 ) {
            wp_send_json_error( [ 'message' => 'Не вказано тип групи.' ] );
        }

        $where_sql = 'SailGroupType_ID = %d';
        $args      = [ $type_id ];

        if ( ! empty( $search ) ) {
            $where_sql .= " AND SailGroup_Name LIKE %s";
            $args[] = '%' . $wpdb->esc_like( $search ) . '%';
        }

        $query = "SELECT SQL_CALC_FOUND_ROWS SailGroup_ID as id, SailGroup_Name as name, 
						 SailGroup_CodeMin as code_min, SailGroup_CodeMax as code_max,
						 SailGroup_Formula as formula, SailGroup_StartingGroup as starting_group,
						 SailGroup_ScoringSystem as scoring_system
				  FROM S_SailGroup
				  WHERE {$where_sql}
				  ORDER BY SailGroup_Name ASC
				  LIMIT %d OFFSET %d";

        // Додаємо ліміти до масиву аргументів
        $args[] = $per_page;
        $args[] = $offset;

        $items = $wpdb->get_results( $wpdb->prepare( $query, $args ), ARRAY_A );
        $total = (int) $wpdb->get_var( 'SELECT FOUND_ROWS()' );

        wp_send_json_success( [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total / $per_page ),
        ] );
    }

    public function create_group(): void {
        $this->verify_request( 'canManage' );
        global $wpdb;

        if ( ! empty( $_POST['fstu_website'] ) ) {
            wp_send_json_error( [ 'message' => 'Spam detected.' ] );
        }

        $type_id = absint( $_POST['type_id'] ?? 0 );
        $name    = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );

        if ( $type_id <= 0 || empty( $name ) ) {
            wp_send_json_error( [ 'message' => __( 'Найменування не може бути порожнім.', 'fstu' ) ] );
        }

        $wpdb->query( 'START TRANSACTION' );

        $inserted = $wpdb->insert(
            'S_SailGroup',
            [
                'SailGroup_DateCreate'    => current_time( 'mysql' ),
                'SailGroupType_ID'        => $type_id,
                'SailGroup_Name'          => $name,
                'SailGroup_CodeMin'       => sanitize_text_field( wp_unslash( $_POST['code_min'] ?? '' ) ),
                'SailGroup_CodeMax'       => sanitize_text_field( wp_unslash( $_POST['code_max'] ?? '' ) ),
                'SailGroup_Formula'       => sanitize_text_field( wp_unslash( $_POST['formula'] ?? '' ) ),
                'SailGroup_StartingGroup' => sanitize_text_field( wp_unslash( $_POST['starting_group'] ?? '' ) ),
                'SailGroup_ScoringSystem' => sanitize_text_field( wp_unslash( $_POST['scoring_system'] ?? '' ) ),
            ],
            [ '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' ]
        );

        if ( $inserted ) {
            $this->log_action( 'I', "Додано залікову групу: {$name} (Тип ID: {$type_id})", '✓' );
            $wpdb->query( 'COMMIT' );
            wp_send_json_success( [ 'message' => __( 'Залікову групу успішно додано.', 'fstu' ) ] );
        } else {
            $wpdb->query( 'ROLLBACK' );
            wp_send_json_error( [ 'message' => __( 'Помилка збереження групи.', 'fstu' ) ] );
        }
    }

    public function update_group(): void {
        $this->verify_request( 'canManage' );
        global $wpdb;

        $id   = absint( $_POST['id'] ?? 0 );
        $name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );

        if ( $id <= 0 || empty( $name ) ) {
            wp_send_json_error( [ 'message' => __( 'Некоректні дані.', 'fstu' ) ] );
        }

        $wpdb->query( 'START TRANSACTION' );

        $updated = $wpdb->update(
            'S_SailGroup',
            [
                'SailGroup_Name'          => $name,
                'SailGroup_CodeMin'       => sanitize_text_field( wp_unslash( $_POST['code_min'] ?? '' ) ),
                'SailGroup_CodeMax'       => sanitize_text_field( wp_unslash( $_POST['code_max'] ?? '' ) ),
                'SailGroup_Formula'       => sanitize_text_field( wp_unslash( $_POST['formula'] ?? '' ) ),
                'SailGroup_StartingGroup' => sanitize_text_field( wp_unslash( $_POST['starting_group'] ?? '' ) ),
                'SailGroup_ScoringSystem' => sanitize_text_field( wp_unslash( $_POST['scoring_system'] ?? '' ) ),
            ],
            [ 'SailGroup_ID' => $id ],
            [ '%s', '%s', '%s', '%s', '%s', '%s' ],
            [ '%d' ]
        );

        if ( $updated !== false ) {
            $this->log_action( 'U', "Оновлено залікову групу [ID {$id}]: {$name}", '✓' );
            $wpdb->query( 'COMMIT' );
            wp_send_json_success( [ 'message' => __( 'Залікову групу успішно оновлено.', 'fstu' ) ] );
        } else {
            $wpdb->query( 'ROLLBACK' );
            wp_send_json_error( [ 'message' => __( 'Помилка оновлення групи.', 'fstu' ) ] );
        }
    }

    public function delete_group(): void {
        $this->verify_request( 'canDelete' );
        global $wpdb;

        $id = absint( $_POST['id'] ?? 0 );
        if ( $id <= 0 ) {
            wp_send_json_error( [ 'message' => __( 'Некоректний ID.', 'fstu' ) ] );
        }

        $name = (string) $wpdb->get_var( $wpdb->prepare( "SELECT SailGroup_Name FROM S_SailGroup WHERE SailGroup_ID = %d", $id ) );

        $wpdb->query( 'START TRANSACTION' );

        $deleted = $wpdb->delete( 'S_SailGroup', [ 'SailGroup_ID' => $id ], [ '%d' ] );

        if ( $deleted ) {
            $this->log_action( 'D', "Видалено залікову групу: {$name} (ID: {$id})", '✓' );
            $wpdb->query( 'COMMIT' );
            wp_send_json_success( [ 'message' => __( 'Залікову групу успішно видалено.', 'fstu' ) ] );
        } else {
            $wpdb->query( 'ROLLBACK' );
            wp_send_json_error( [ 'message' => __( 'Помилка видалення.', 'fstu' ) ] );
        }
    }

    // ==========================================================================
    // 3. ПРОТОКОЛ (LOGS)
    // ==========================================================================

    public function get_protocol(): void {
        $this->verify_request( 'canProtocol' );
        global $wpdb;

        $page     = max( 1, isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1 );
        $per_page = max( 1, isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 25 );
        $search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
        $offset   = ( $page - 1 ) * $per_page;

        $where_sql = 'l.Logs_Name = %s';
        $args      = [ self::LOG_NAME ];

        if ( ! empty( $search ) ) {
            $where_sql .= " AND (l.Logs_Text LIKE %s OR u.FIO LIKE %s)";
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $args[] = $like;
            $args[] = $like;
        }

        $query = "SELECT SQL_CALC_FOUND_ROWS l.Logs_DateCreate, l.Logs_Type, l.Logs_Name, l.Logs_Text, l.Logs_Error, u.FIO
				  FROM Logs l
				  LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
				  WHERE {$where_sql}
				  ORDER BY l.Logs_DateCreate DESC
				  LIMIT %d OFFSET %d";

        $args[] = $per_page;
        $args[] = $offset;

        $items = $wpdb->get_results( $wpdb->prepare( $query, $args ), ARRAY_A );
        $total = (int) $wpdb->get_var( 'SELECT FOUND_ROWS()' );

        wp_send_json_success( [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total / $per_page ),
        ] );
    }

    /**
     * Запис у протокол. Виконується тільки в межах відкритої транзакції.
     */
    private function log_action( string $type, string $text, string $status ): void {
        global $wpdb;
        $wpdb->insert(
            'Logs',
            [
                'User_ID'         => get_current_user_id(),
                'Logs_DateCreate' => current_time( 'mysql' ),
                'Logs_Type'       => substr( $type, 0, 1 ), // Гарантія 1 символу
                'Logs_Name'       => self::LOG_NAME,
                'Logs_Text'       => $text,
                'Logs_Error'      => $status,
            ],
            [ '%d', '%s', '%s', '%s', '%s', '%s' ]
        );
    }
}