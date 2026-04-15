<?php
/**
 * Обробник AJAX для довідника статусів карток.
 * Version: 1.1.0
 * Date_update: 2026-04-15
 */

namespace FSTU\Dictionaries\StatusCard;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Class_Status_Card_Ajax {

    private const LOG_NAME = 'StatusCard';

    public function __construct() {
        $hooks = [ 'get_items', 'save_item', 'delete_item', 'reorder_items', 'get_protocol' ];
        foreach ( $hooks as $hook ) {
            add_action( "wp_ajax_fstu_status_card_{$hook}", [ $this, "handle_{$hook}" ] );
        }
    }

    private function verify_request(): void {
        check_ajax_referer( 'fstu_status_card_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Відмовлено в доступі.' ] );
        }
    }

    private function check_honeypot(): void {
        if ( ! empty( $_POST['fstu_website'] ) ) {
            wp_send_json_error( [ 'message' => 'Підозрілий запит відхилено.' ] );
        }
    }

    private function log_action( string $type, string $text, string $status ): bool {
        global $wpdb;
        $res = $wpdb->insert(
            'Logs',
            [
                'User_ID'         => get_current_user_id(),
                'Logs_DateCreate' => current_time( 'mysql' ),
                'Logs_Type'       => $type, // Тільки 'I', 'U', 'D', 'V'
                'Logs_Name'       => self::LOG_NAME,
                'Logs_Text'       => $text,
                'Logs_Error'      => $status,
            ],
            [ '%d', '%s', '%s', '%s', '%s', '%s' ]
        );
        return $res !== false;
    }

    public function handle_get_items(): void {
        $this->verify_request();
        global $wpdb;

        $per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 10;
        $page     = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
        $search   = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
        $offset   = ( $page - 1 ) * $per_page;

        $where = "1=1";
        $args  = [];

        if ( $search ) {
            $where .= " AND StatusCard_Name LIKE %s";
            $args[] = '%' . $wpdb->esc_like( $search ) . '%';
        }

        $query = "SELECT StatusCard_ID, StatusCard_Name, StatusCard_Order FROM S_StatusCard WHERE $where ORDER BY StatusCard_Order ASC";

        $total_query = "SELECT COUNT(*) FROM S_StatusCard WHERE $where";
        $total = $args ? $wpdb->get_var( $wpdb->prepare( $total_query, $args ) ) : $wpdb->get_var( $total_query );

        $query .= " LIMIT %d OFFSET %d";
        $args[] = $per_page;
        $args[] = $offset;

        $items = $wpdb->get_results( $wpdb->prepare( $query, $args ), ARRAY_A );

        wp_send_json_success( [
            'items'       => $items,
            'total'       => (int) $total,
            'total_pages' => ceil( $total / $per_page ),
        ] );
    }

    public function handle_delete_item(): void {
        $this->verify_request();
        global $wpdb;

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        if ( ! $id ) {
            wp_send_json_error( [ 'message' => 'Некоректний ID.' ] );
        }

        // Перевірка залежностей (Soft Lock)
        $in_use = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM UserMemberCard WHERE StatusCard_ID = %d", $id ) );
        if ( $in_use > 0 ) {
            $this->log_action( 'D', "Спроба видалення заблокована (ID: $id, використовується: $in_use)", 'dependency' );
            wp_send_json_error( [ 'message' => "Статус використовується у $in_use картках(ці). Видалення неможливе." ] );
        }

        // Отримання імені для логу
        $name = $wpdb->get_var( $wpdb->prepare( "SELECT StatusCard_Name FROM S_StatusCard WHERE StatusCard_ID = %d", $id ) );

        $wpdb->query( 'START TRANSACTION' );

        $deleted = $wpdb->delete( 'S_StatusCard', [ 'StatusCard_ID' => $id ], [ '%d' ] );
        if ( $deleted === false ) {
            $wpdb->query( 'ROLLBACK' );
            $this->log_action( 'D', "Помилка видалення ID: $id", 'error' );
            wp_send_json_error( [ 'message' => 'Помилка бази даних під час видалення.' ] );
        }

        $log_res = $this->log_action( 'D', "Видалено статус: $name", '✓' );
        if ( ! $log_res ) {
            $wpdb->query( 'ROLLBACK' );
            wp_send_json_error( [ 'message' => 'Помилка запису протоколу. Операцію скасовано.' ] );
        }

        $wpdb->query( 'COMMIT' );
        wp_send_json_success( [ 'message' => 'Запис успішно видалено.' ] );
    }

    public function handle_save_item(): void {
        $this->verify_request();
        $this->check_honeypot();
        global $wpdb;

        $id   = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';

        if ( empty( $name ) ) {
            wp_send_json_error( [ 'message' => 'Найменування не може бути порожнім.' ] );
        }

        // Валідація унікальності
        $unique_check = $id
            ? $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM S_StatusCard WHERE StatusCard_Name = %s AND StatusCard_ID != %d", $name, $id ) )
            : $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM S_StatusCard WHERE StatusCard_Name = %s", $name ) );

        if ( $unique_check > 0 ) {
            wp_send_json_error( [ 'message' => 'Статус із таким найменуванням вже існує.' ] );
        }

        $wpdb->query( 'START TRANSACTION' );

        if ( $id ) {
            // UPDATE
            $updated = $wpdb->update( 'S_StatusCard', [ 'StatusCard_Name' => $name ], [ 'StatusCard_ID' => $id ], [ '%s' ], [ '%d' ] );
            if ( $updated === false ) {
                $wpdb->query( 'ROLLBACK' );
                wp_send_json_error( [ 'message' => 'Помилка оновлення.' ] );
            }
            $log_res = $this->log_action( 'U', "Оновлено статус (ID: $id): $name", '✓' );
        } else {
            // INSERT
            // Автоматичне визначення наступного Order
            $next_order = (int) $wpdb->get_var( "SELECT MAX(StatusCard_Order) FROM S_StatusCard" ) + 10;

            $inserted = $wpdb->insert(
                'S_StatusCard',
                [
                    'StatusCard_Name'       => $name,
                    'StatusCard_Order'      => $next_order,
                    'StatusCard_DateCreate' => current_time( 'mysql' )
                ],
                [ '%s', '%d', '%s' ]
            );

            if ( $inserted === false ) {
                $wpdb->query( 'ROLLBACK' );
                wp_send_json_error( [ 'message' => 'Помилка створення.' ] );
            }
            $log_res = $this->log_action( 'I', "Створено новий статус: $name", '✓' );
        }

        if ( ! $log_res ) {
            $wpdb->query( 'ROLLBACK' );
            wp_send_json_error( [ 'message' => 'Помилка логування. Операцію скасовано.' ] );
        }

        $wpdb->query( 'COMMIT' );
        wp_send_json_success( [ 'message' => 'Дані успішно збережено.' ] );
    }

    public function handle_reorder_items(): void {
        $this->verify_request();
        global $wpdb;

        $orders = isset( $_POST['orders'] ) ? (array) $_POST['orders'] : [];
        if ( empty( $orders ) ) {
            wp_send_json_error( [ 'message' => 'Немає даних для сортування.' ] );
        }

        $wpdb->query( 'START TRANSACTION' );
        foreach ( $orders as $item ) {
            $id    = absint( $item['id'] );
            $order = absint( $item['order'] );
            $wpdb->update( 'S_StatusCard', [ 'StatusCard_Order' => $order ], [ 'StatusCard_ID' => $id ], [ '%d' ], [ '%d' ] );
        }

        $log_res = $this->log_action( 'U', "Змінено порядок сортування статусів карток", '✓' );
        if ( ! $log_res ) {
            $wpdb->query( 'ROLLBACK' );
            wp_send_json_error();
        }

        $wpdb->query( 'COMMIT' );
        wp_send_json_success();
    }
    public function handle_get_protocol(): void {
        $this->verify_request();
        global $wpdb;

        $per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 10;
        $page     = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
        $search   = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
        $offset   = ( $page - 1 ) * $per_page;

        $where = "l.Logs_Name = %s";
        $args  = [ self::LOG_NAME ];

        if ( $search ) {
            $where .= " AND (l.Logs_Text LIKE %s OR u.FIO LIKE %s)";
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $args[] = $like;
            $args[] = $like;
        }

        // Запит до таблиці Logs з JOIN для отримання ПІБ користувача
        $query = "SELECT l.Logs_DateCreate, l.Logs_Type, l.Logs_Name, l.Logs_Text, l.Logs_Error, u.FIO
                  FROM Logs l
                  LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
                  WHERE $where
                  ORDER BY l.Logs_DateCreate DESC";

        $total_query = "SELECT COUNT(*) FROM Logs l LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID WHERE $where";
        $total = $wpdb->get_var( $wpdb->prepare( $total_query, ...$args ) );

        $query .= " LIMIT %d OFFSET %d";
        $args[] = $per_page;
        $args[] = $offset;

        $items = $wpdb->get_results( $wpdb->prepare( $query, ...$args ), ARRAY_A );

        wp_send_json_success( [
            'items'       => $items,
            'total'       => (int) $total,
            'total_pages' => ceil( $total / $per_page ),
        ] );
    }
}