<?php
/**
 * AJAX-обробник для довідника типів членських білетів.
 * * Version: 1.0.0
 * Date_update: 2026-04-15
 */

namespace FSTU\Dictionaries\TypeCard;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Class_Type_Card_Ajax {

    private const LOG_NAME = 'TypeCard';

    public function __construct() {
        // Прив'язка AJAX хуків для авторизованих користувачів
        add_action( 'wp_ajax_fstu_type_card_get_items', [ $this, 'get_items' ] );
        add_action( 'wp_ajax_fstu_type_card_create', [ $this, 'create_item' ] );
        add_action( 'wp_ajax_fstu_type_card_update', [ $this, 'update_item' ] );
        add_action( 'wp_ajax_fstu_type_card_delete', [ $this, 'delete_item' ] );
        add_action( 'wp_ajax_fstu_type_card_reorder', [ $this, 'reorder_items' ] );
        add_action( 'wp_ajax_fstu_type_card_get_protocol', [ $this, 'get_protocol' ] );
    }

    /**
     * Базова перевірка безпеки та Nonce.
     */
    private function verify_request_and_permissions( string $action = 'canManage' ): void {
        check_ajax_referer( 'fstu_module_nonce', 'nonce' );

        $permissions = Capabilities::get_type_card_permissions();
        if ( empty( $permissions[ $action ] ) ) {
            wp_send_json_error( [ 'message' => __( 'Недостатньо прав для виконання цієї дії.', 'fstu' ) ] );
        }
    }

    /**
     * Отримання списку записів з підтримкою пагінації та пошуку.
     */
    public function get_items(): void {
        $this->verify_request_and_permissions( 'canView' );
        global $wpdb;

        $page     = max( 1, isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1 );
        $per_page = max( 1, isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 10 );
        $search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
        $offset   = ( $page - 1 ) * $per_page;

        $where_sql = '1=1';
        $args      = [];

        if ( ! empty( $search ) ) {
            $where_sql .= " AND TypeCard_Name LIKE %s";
            $args[] = '%' . $wpdb->esc_like( $search ) . '%';
        }

        $query = "SELECT SQL_CALC_FOUND_ROWS TypeCard_ID as id, TypeCard_Name as name, TypeCard_Summa as summa, TypeCard_Order as `order`
				  FROM S_TypeCard
				  WHERE {$where_sql}
				  ORDER BY TypeCard_Order ASC
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
     * Перевірка на унікальність імені.
     */
    private function is_name_unique( string $name, int $exclude_id = 0 ): bool {
        global $wpdb;
        $query = "SELECT COUNT(*) FROM S_TypeCard WHERE TypeCard_Name = %s AND TypeCard_ID != %d";
        $count = (int) $wpdb->get_var( $wpdb->prepare( $query, $name, $exclude_id ) );
        return $count === 0;
    }

    /**
     * Створення нового запису (з транзакційним логуванням).
     */
    public function create_item(): void {
        $this->verify_request_and_permissions( 'canManage' );
        global $wpdb;

        // Захист від ботів (Honeypot)
        if ( ! empty( $_POST['fstu_website'] ) ) {
            wp_send_json_error( [ 'message' => 'Spam detected.' ] );
        }

        $name  = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        $summa = isset( $_POST['summa'] ) ? absint( $_POST['summa'] ) : 0; // старий код використовував цілі числа (%d)

        if ( empty( $name ) ) {
            wp_send_json_error( [ 'message' => __( 'Найменування не може бути порожнім.', 'fstu' ) ] );
        }

        if ( ! $this->is_name_unique( $name ) ) {
            wp_send_json_error( [ 'message' => __( 'Таке найменування вже існує в базі.', 'fstu' ) ] );
        }

        // Знаходимо максимальний order, щоб додати в кінець списку
        $max_order = (int) $wpdb->get_var( "SELECT MAX(TypeCard_Order) FROM S_TypeCard" );
        $new_order = $max_order + 10;

        $wpdb->query( 'START TRANSACTION' );

        $inserted = $wpdb->insert(
            'S_TypeCard',
            [
                'TypeCard_DateCreate' => current_time( 'mysql' ),
                'TypeCard_Name'       => $name,
                'TypeCard_Summa'      => $summa,
                'TypeCard_Order'      => $new_order,
            ],
            [ '%s', '%s', '%d', '%d' ]
        );

        if ( $inserted ) {
            $this->log_action( 'I', "Додано тип членського білета: {$name} (Сума: {$summa})", '✓' );
            $wpdb->query( 'COMMIT' );
            wp_send_json_success( [ 'message' => __( 'Запис успішно додано.', 'fstu' ) ] );
        } else {
            $wpdb->query( 'ROLLBACK' );
            wp_send_json_error( [ 'message' => __( 'Помилка збереження в базу даних.', 'fstu' ) ] );
        }
    }

    /**
     * Оновлення існуючого запису.
     */
    public function update_item(): void {
        $this->verify_request_and_permissions( 'canManage' );
        global $wpdb;

        $id    = absint( $_POST['id'] ?? 0 );
        $name  = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        $summa = isset( $_POST['summa'] ) ? absint( $_POST['summa'] ) : 0;

        if ( $id <= 0 || empty( $name ) ) {
            wp_send_json_error( [ 'message' => __( 'Некоректні дані для оновлення.', 'fstu' ) ] );
        }

        if ( ! $this->is_name_unique( $name, $id ) ) {
            wp_send_json_error( [ 'message' => __( 'Таке найменування вже існує в базі.', 'fstu' ) ] );
        }

        $wpdb->query( 'START TRANSACTION' );

        $updated = $wpdb->update(
            'S_TypeCard',
            [
                'TypeCard_Name'  => $name,
                'TypeCard_Summa' => $summa,
            ],
            [ 'TypeCard_ID' => $id ],
            [ '%s', '%d' ],
            [ '%d' ]
        );

        if ( $updated !== false ) {
            $this->log_action( 'U', "Оновлено тип членського білета ID [{$id}]: {$name}", '✓' );
            $wpdb->query( 'COMMIT' );
            wp_send_json_success( [ 'message' => __( 'Запис успішно оновлено.', 'fstu' ) ] );
        } else {
            $wpdb->query( 'ROLLBACK' );
            wp_send_json_error( [ 'message' => __( 'Помилка оновлення бази даних.', 'fstu' ) ] );
        }
    }

    /**
     * Фізичне видалення запису.
     */
    public function delete_item(): void {
        $this->verify_request_and_permissions( 'canDelete' );
        global $wpdb;

        $id = absint( $_POST['id'] ?? 0 );
        if ( $id <= 0 ) {
            wp_send_json_error( [ 'message' => __( 'Некоректний ID.', 'fstu' ) ] );
        }

        // Отримуємо назву перед видаленням для логування
        $name = (string) $wpdb->get_var( $wpdb->prepare( "SELECT TypeCard_Name FROM S_TypeCard WHERE TypeCard_ID = %d", $id ) );

        $wpdb->query( 'START TRANSACTION' );

        $deleted = $wpdb->delete( 'S_TypeCard', [ 'TypeCard_ID' => $id ], [ '%d' ] );

        if ( $deleted ) {
            $this->log_action( 'D', "Видалено тип членського білета: {$name} (ID: {$id})", '✓' );
            $wpdb->query( 'COMMIT' );
            wp_send_json_success( [ 'message' => __( 'Запис успішно видалено.', 'fstu' ) ] );
        } else {
            $wpdb->query( 'ROLLBACK' );
            wp_send_json_error( [ 'message' => __( 'Помилка видалення.', 'fstu' ) ] );
        }
    }

    /**
     * Оновлення порядку сортування (Drag and Drop).
     */
    public function reorder_items(): void {
        $this->verify_request_and_permissions( 'canManage' );
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
                $res = $wpdb->update(
                    'S_TypeCard',
                    [ 'TypeCard_Order' => $order ],
                    [ 'TypeCard_ID' => $id ],
                    [ '%d' ],
                    [ '%d' ]
                );
                if ( $res === false ) {
                    $has_errors = true;
                }
            }
        }

        if ( ! $has_errors ) {
            $this->log_action( 'U', 'Оновлено сортування (Drag & Drop)', '✓' );
            $wpdb->query( 'COMMIT' );
            wp_send_json_success();
        } else {
            $wpdb->query( 'ROLLBACK' );
            wp_send_json_error( [ 'message' => __( 'Помилка при збереженні сортування.', 'fstu' ) ] );
        }
    }

    /**
     * Отримання логів для розділу "ПРОТОКОЛ".
     */
    public function get_protocol(): void {
        $this->verify_request_and_permissions( 'canProtocol' );
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
     * Helper для запису логів. Зверніть увагу: Транзакція має управлятися зовнішнім методом.
     * * @param string $type   ОДИН символ ('I', 'U', 'D', 'V').
     * @param string $text   Опис дії.
     * @param string $status '✓' або помилка.
     */
    private function log_action( string $type, string $text, string $status ): void {
        global $wpdb;
        $wpdb->insert(
            'Logs',
            [
                'User_ID'         => get_current_user_id(),
                'Logs_DateCreate' => current_time( 'mysql' ),
                'Logs_Type'       => substr( $type, 0, 1 ), // Гарантуємо 1 символ
                'Logs_Name'       => self::LOG_NAME,
                'Logs_Text'       => $text,
                'Logs_Error'      => $status,
            ],
            [ '%d', '%s', '%s', '%s', '%s', '%s' ]
        );
    }
}