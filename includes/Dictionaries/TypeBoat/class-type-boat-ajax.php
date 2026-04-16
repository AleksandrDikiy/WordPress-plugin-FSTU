<?php
/**
 * Клас обробки AJAX-запитів довідника "Виробники та типи суден".
 *
 * Version:     1.0.0
 * Date_update: 2026-04-16
 */

namespace FSTU\Dictionaries\TypeBoat;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Type_Boat_Ajax {

    private const LOG_NAME = 'TypeBoat';

    public function init(): void {
        $actions = [
            'get_producers',
            'save_producer',
            'delete_producer',
            'get_boats',
            'save_boat',
            'delete_boat',
            'get_protocol'
        ];

        foreach ( $actions as $action ) {
            add_action( "wp_ajax_fstu_type_boat_{$action}", [ $this, "handle_{$action}" ] );
        }

        // Додаємо публічні хуки для читання
        add_action( 'wp_ajax_nopriv_fstu_type_boat_get_producers', [ $this, 'handle_get_producers' ] );
        add_action( 'wp_ajax_nopriv_fstu_type_boat_get_boats', [ $this, 'handle_get_boats' ] );
    }

    /**
     * Базова перевірка безпеки перед виконанням запиту.
     */
    private function verify_request( string $required_capability = 'canView' ): void {
        check_ajax_referer( 'fstu_module_nonce', 'nonce' );

        $permissions = Capabilities::get_type_boat_permissions();
        if ( empty( $permissions[ $required_capability ] ) ) {
            wp_send_json_error( [ 'message' => __( 'Недостатньо прав для виконання операції.', 'fstu' ) ] );
        }
    }

    /**
     * Перевірка Honeypot від ботів.
     */
    private function check_honeypot(): void {
        if ( ! empty( $_POST['fstu_website'] ) ) {
            wp_send_json_error( [ 'message' => __( 'Запит відхилено системою безпеки.', 'fstu' ) ] );
        }
    }

    // ==========================================
    // ВИРОБНИКИ (MASTER)
    // ==========================================

    public function handle_get_producers(): void {
        $this->verify_request( 'canView' );
        global $wpdb;

        $page     = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
        $per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 10;
        $search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
        $offset   = ( $page - 1 ) * $per_page;

        $where_sql = "1=1";
        $params    = [];

        if ( '' !== $search ) {
            $where_sql .= " AND p.ProducerShips_Name LIKE %s";
            $params[] = '%' . $wpdb->esc_like( $search ) . '%';
        }

        // Підрахунок загальної кількості
        $count_sql = "SELECT COUNT(ProducerShips_ID) FROM S_ProducerShips p WHERE $where_sql";
        $total     = (int) ( empty( $params ) ? $wpdb->get_var( $count_sql ) : $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) );

        // Отримання даних з LEFT JOIN для підрахунку типів
        $sql = "SELECT p.ProducerShips_ID, p.ProducerShips_Name, p.ProducerShips_URL, p.ProducerShips_Phone, p.ProducerShips_Adr, p.ProducerShips_Notes,
				COUNT(b.TypeBoat_ID) AS BoatsCount
				FROM S_ProducerShips p
				LEFT JOIN S_TypeBoat b ON p.ProducerShips_ID = b.ProducerShips_ID
				WHERE $where_sql
				GROUP BY p.ProducerShips_ID
				ORDER BY p.ProducerShips_Name ASC
				LIMIT %d OFFSET %d";

        $params[] = $per_page;
        $params[] = $offset;

        $items = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

        wp_send_json_success( [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => max( 1, (int) ceil( $total / max( 1, $per_page ) ) ),
        ] );
    }

    public function handle_save_producer(): void {
        $this->verify_request( 'canManage' );
        $this->check_honeypot();
        global $wpdb;

        $id    = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $name  = sanitize_text_field( wp_unslash( $_POST['ProducerShips_Name'] ?? '' ) );
        $phone = sanitize_text_field( wp_unslash( $_POST['ProducerShips_Phone'] ?? '' ) );
        $adr   = sanitize_text_field( wp_unslash( $_POST['ProducerShips_Adr'] ?? '' ) );
        $notes = sanitize_textarea_field( wp_unslash( $_POST['ProducerShips_Notes'] ?? '' ) );
        $url   = esc_url_raw( wp_unslash( $_POST['ProducerShips_URL'] ?? '' ) );

        if ( empty( $name ) ) {
            wp_send_json_error( [ 'message' => __( 'Найменування виробника є обов\'язковим.', 'fstu' ) ] );
        }

        $data = [
            'ProducerShips_Name'  => $name,
            'ProducerShips_Phone' => $phone,
            'ProducerShips_Adr'   => $adr,
            'ProducerShips_Notes' => $notes,
            'ProducerShips_URL'   => $url,
        ];
        $format = [ '%s', '%s', '%s', '%s', '%s' ];

        $wpdb->query( 'START TRANSACTION' );

        if ( $id > 0 ) {
            $result = $wpdb->update( 'S_ProducerShips', $data, [ 'ProducerShips_ID' => $id ], $format, [ '%d' ] );
            if ( false === $result ) {
                $wpdb->query( 'ROLLBACK' );
                wp_send_json_error( [ 'message' => __( 'Помилка оновлення бази даних.', 'fstu' ) ] );
            }
            $this->log_action_transactional( 'U', "Оновлено виробника ID: {$id} ({$name})", '✓' );
        } else {
            $result = $wpdb->insert( 'S_ProducerShips', $data, $format );
            if ( false === $result ) {
                $wpdb->query( 'ROLLBACK' );
                wp_send_json_error( [ 'message' => __( 'Помилка збереження бази даних.', 'fstu' ) ] );
            }
            $this->log_action_transactional( 'I', "Додано виробника: {$name}", '✓' );
        }

        $wpdb->query( 'COMMIT' );
        wp_send_json_success( [ 'message' => __( 'Дані успішно збережено.', 'fstu' ) ] );
    }

    public function handle_delete_producer(): void {
        $this->verify_request( 'canDelete' );
        global $wpdb;

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        if ( ! $id ) {
            wp_send_json_error( [ 'message' => __( 'Некоректний ID.', 'fstu' ) ] );
        }

        // Перевірка залежностей
        $boats_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM S_TypeBoat WHERE ProducerShips_ID = %d", $id ) );
        if ( $boats_count > 0 ) {
            // Логуємо спробу видалення заблокованого ресурсу згідно з AGENTS.md
            $this->log_safe_action( 'D', "Заблоковано видалення виробника ID: {$id} (має прив'язані судна)", 'dependency' );
            wp_send_json_error( [ 'message' => sprintf( __( 'Неможливо видалити: у цього виробника є %d типів суден.', 'fstu' ), $boats_count ) ] );
        }

        $wpdb->query( 'START TRANSACTION' );

        $result = $wpdb->delete( 'S_ProducerShips', [ 'ProducerShips_ID' => $id ], [ '%d' ] );
        if ( false === $result ) {
            $wpdb->query( 'ROLLBACK' );
            wp_send_json_error( [ 'message' => __( 'Помилка видалення.', 'fstu' ) ] );
        }

        $this->log_action_transactional( 'D', "Видалено виробника ID: {$id}", '✓' );
        $wpdb->query( 'COMMIT' );

        wp_send_json_success( [ 'message' => __( 'Виробника видалено.', 'fstu' ) ] );
    }

    // ==========================================
    // ТИПИ СУДЕН (DETAIL)
    // ==========================================

    public function handle_get_boats(): void {
        $this->verify_request( 'canView' );
        global $wpdb;

        $producer_id = isset( $_POST['producer_id'] ) ? absint( $_POST['producer_id'] ) : 0;
        $page        = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
        $per_page    = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 10;
        $search      = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
        $offset      = ( $page - 1 ) * $per_page;

        if ( ! $producer_id ) {
            wp_send_json_error( [ 'message' => 'Не вказано виробника.' ] );
        }

        $where_sql = "ProducerShips_ID = %d";
        $params    = [ $producer_id ];

        if ( '' !== $search ) {
            $where_sql .= " AND TypeBoat_Name LIKE %s";
            $params[] = '%' . $wpdb->esc_like( $search ) . '%';
        }

        $count_sql = "SELECT COUNT(TypeBoat_ID) FROM S_TypeBoat WHERE $where_sql";
        $total     = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) );

        // ВИПРАВЛЕНО: Додано ProducerShips_ID до вибірки
        $sql = "SELECT TypeBoat_ID, ProducerShips_ID, TypeBoat_Name, TypeBoat_Notes, TypeBoat_SailArea, TypeBoat_HillLength, TypeBoat_WidthOverall, TypeBoat_Weight, TypeBoat_URL
				FROM S_TypeBoat
				WHERE $where_sql
				ORDER BY TypeBoat_Name ASC
				LIMIT %d OFFSET %d";

        $params[] = $per_page;
        $params[] = $offset;

        $items = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

        wp_send_json_success( [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => max( 1, (int) ceil( $total / max( 1, $per_page ) ) ),
        ] );
    }

    public function handle_save_boat(): void {
        $this->verify_request( 'canManage' );
        $this->check_honeypot();
        global $wpdb;

        $id          = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $producer_id = isset( $_POST['ProducerShips_ID'] ) ? absint( $_POST['ProducerShips_ID'] ) : 0;
        $name        = sanitize_text_field( wp_unslash( $_POST['TypeBoat_Name'] ?? '' ) );
        $notes       = sanitize_textarea_field( wp_unslash( $_POST['TypeBoat_Notes'] ?? '' ) );
        $sail_area   = isset( $_POST['TypeBoat_SailArea'] ) ? (float) $_POST['TypeBoat_SailArea'] : 0;
        $hill_len    = isset( $_POST['TypeBoat_HillLength'] ) ? (float) $_POST['TypeBoat_HillLength'] : 0;
        $width       = isset( $_POST['TypeBoat_WidthOverall'] ) ? (float) $_POST['TypeBoat_WidthOverall'] : 0;
        $weight      = isset( $_POST['TypeBoat_Weight'] ) ? (float) $_POST['TypeBoat_Weight'] : 0;
        $url         = esc_url_raw( wp_unslash( $_POST['TypeBoat_URL'] ?? '' ) );

        if ( empty( $name ) || ! $producer_id ) {
            wp_send_json_error( [ 'message' => __( 'Найменування та прив\'язка до виробника є обов\'язковими.', 'fstu' ) ] );
        }

        $data = [
            'ProducerShips_ID'      => $producer_id,
            'TypeBoat_Name'         => $name,
            'TypeBoat_Notes'        => $notes,
            'TypeBoat_SailArea'     => $sail_area,
            'TypeBoat_HillLength'   => $hill_len,
            'TypeBoat_WidthOverall' => $width,
            'TypeBoat_Weight'       => $weight,
            'TypeBoat_URL'          => $url,
        ];
        $format = [ '%d', '%s', '%s', '%f', '%f', '%f', '%f', '%s' ];

        $wpdb->query( 'START TRANSACTION' );

        if ( $id > 0 ) {
            $result = $wpdb->update( 'S_TypeBoat', $data, [ 'TypeBoat_ID' => $id ], $format, [ '%d' ] );
            if ( false === $result ) {
                $wpdb->query( 'ROLLBACK' );
                wp_send_json_error( [ 'message' => __( 'Помилка оновлення бази даних.', 'fstu' ) ] );
            }
            $this->log_action_transactional( 'U', "Оновлено тип судна ID: {$id} ({$name})", '✓' );
        } else {
            $result = $wpdb->insert( 'S_TypeBoat', $data, $format );
            if ( false === $result ) {
                $wpdb->query( 'ROLLBACK' );
                wp_send_json_error( [ 'message' => __( 'Помилка збереження бази даних.', 'fstu' ) ] );
            }
            $this->log_action_transactional( 'I', "Додано тип судна: {$name}", '✓' );
        }

        $wpdb->query( 'COMMIT' );
        wp_send_json_success( [ 'message' => __( 'Дані успішно збережено.', 'fstu' ) ] );
    }

    public function handle_delete_boat(): void {
        $this->verify_request( 'canDelete' );
        global $wpdb;

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        if ( ! $id ) {
            wp_send_json_error( [ 'message' => __( 'Некоректний ID.', 'fstu' ) ] );
        }

        $wpdb->query( 'START TRANSACTION' );

        $result = $wpdb->delete( 'S_TypeBoat', [ 'TypeBoat_ID' => $id ], [ '%d' ] );
        if ( false === $result ) {
            $wpdb->query( 'ROLLBACK' );
            wp_send_json_error( [ 'message' => __( 'Помилка видалення типу судна.', 'fstu' ) ] );
        }

        $this->log_action_transactional( 'D', "Видалено тип судна ID: {$id}", '✓' );
        $wpdb->query( 'COMMIT' );

        wp_send_json_success( [ 'message' => __( 'Тип судна видалено.', 'fstu' ) ] );
    }

    // ==========================================
    // ПРОТОКОЛ (LOGS)
    // ==========================================

    public function handle_get_protocol(): void {
        $this->verify_request( 'canProtocol' );
        global $wpdb;

        $page     = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
        $per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 10;
        $search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
        $offset   = ( $page - 1 ) * $per_page;

        $where_sql = "l.Logs_Name = %s";
        $params    = [ self::LOG_NAME ];

        if ( '' !== $search ) {
            $where_sql .= " AND (l.Logs_Text LIKE %s OR u.FIO LIKE %s)";
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $count_sql = "SELECT COUNT(l.Logs_ID) FROM Logs l LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID WHERE $where_sql";
        $total     = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) );

        $sql = "SELECT l.Logs_DateCreate, l.Logs_Type, l.Logs_Name, l.Logs_Text, l.Logs_Error, u.FIO
				FROM Logs l 
				LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
				WHERE $where_sql
				ORDER BY l.Logs_DateCreate DESC
				LIMIT %d OFFSET %d";

        $params[] = $per_page;
        $params[] = $offset;

        $items = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

        wp_send_json_success( [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => max( 1, (int) ceil( $total / max( 1, $per_page ) ) ),
        ] );
    }

    /**
     * Логування всередині відкритої транзакції (кидає помилку і rollback, якщо не вдалося записати лог).
     */
    private function log_action_transactional( string $type, string $text, string $status ): void {
        global $wpdb;
        $result = $wpdb->insert(
            'Logs',
            [
                'User_ID'         => get_current_user_id(),
                'Logs_DateCreate' => current_time( 'mysql' ),
                'Logs_Type'       => $type, // Тільки 'I', 'U' або 'D'
                'Logs_Name'       => self::LOG_NAME,
                'Logs_Text'       => $text,
                'Logs_Error'      => $status,
            ],
            [ '%d', '%s', '%s', '%s', '%s', '%s' ]
        );

        if ( false === $result ) {
            $wpdb->query( 'ROLLBACK' );
            wp_send_json_error( [ 'message' => __( 'Не вдалося зберегти протокол операції.', 'fstu' ) ] );
        }
    }

    /**
     * Логування поза основною транзакцією (наприклад, блокування видалення).
     */
    private function log_safe_action( string $type, string $text, string $status ): void {
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
}