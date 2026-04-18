<?php
/**
 * Клас обробки AJAX запитів модуля "Осередки федерації спортивного туризму".
 *
 * Version:     1.0.0
 * Date_update: 2026-04-18
 *
 * @package FSTU\Modules\RegionalFST
 */

namespace FSTU\Modules\RegionalFST;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Class_Regional_FST_Ajax {

    private const NONCE_ACTION = 'fstu_regional_fst_nonce';
    private const LOG_NAME     = 'RegionalFST';

    /**
     * Ініціалізація AJAX хуків.
     */
    public function init(): void {
        add_action( 'wp_ajax_fstu_get_regional_fst', [ $this, 'handle_get_list' ] );
        add_action( 'wp_ajax_nopriv_fstu_get_regional_fst', [ $this, 'handle_get_list' ] ); // Гості теж бачать

        add_action( 'wp_ajax_fstu_save_regional_fst', [ $this, 'handle_save_item' ] );
        add_action( 'wp_ajax_fstu_delete_regional_fst', [ $this, 'handle_delete_item' ] );
        add_action( 'wp_ajax_fstu_get_regional_fst_protocol', [ $this, 'handle_get_protocol' ] );

        add_action( 'wp_ajax_fstu_get_regional_fst_item', [ $this, 'handle_get_item' ] );
    }

    /**
     * Отримання списку членів осередку.
     */
    public function handle_get_list(): void {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        $permissions = Capabilities::get_regional_fst_permissions();
        if ( ! $permissions['canView'] ) {
            wp_send_json_error( [ 'message' => __( 'Немає доступу до реєстру.', 'fstu' ) ] );
        }

        $region_id = absint( $_POST['region_id'] ?? 0 );
        $unit_id   = absint( $_POST['unit_id'] ?? 0 );
        $search    = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
        $page      = max( 1, absint( $_POST['page'] ?? 1 ) );
        $per_page  = min( max( absint( $_POST['per_page'] ?? 10 ), 1 ), 100 ); // За стандартом 10
        $offset    = ( $page - 1 ) * $per_page;

        $repo   = new Class_Regional_FST_Repository();
        $result = $repo->get_list( $region_id, $unit_id, $search, $per_page, $offset );

        wp_send_json_success( [
            'items'       => $result['items'],
            'total'       => $result['total'],
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => ceil( $result['total'] / $per_page ),
        ] );
    }

    /**
     * Збереження (додавання або оновлення).
     */
    public function handle_save_item(): void {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        $permissions = Capabilities::get_regional_fst_permissions();
        if ( ! $permissions['canManage'] ) {
            wp_send_json_error( [ 'message' => __( 'Недостатньо прав для збереження.', 'fstu' ) ] );
        }

        // Захист від ботів (Honeypot) - стандарт для нових форм
        if ( ! empty( $_POST['fstu_website'] ) ) {
            wp_send_json_error( [ 'message' => __( 'Спам заблоковано.', 'fstu' ) ] );
        }

        $data = [
            'RegionalFST_ID'    => absint( $_POST['id'] ?? 0 ),
            'User_ID'           => absint( $_POST['user_id'] ?? 0 ),
            'Unit_ID'           => absint( $_POST['unit_id'] ?? 0 ),
            'MemberRegional_ID' => absint( $_POST['member_regional_id'] ?? 0 ),
        ];

        $service = new Class_Regional_FST_Service();

        try {
            $service->save_item( $data );
            wp_send_json_success( [ 'message' => __( 'Посаду успішно збережено!', 'fstu' ) ] );
        } catch ( \Exception $e ) {
            wp_send_json_error( [ 'message' => $e->getMessage() ] );
        }
    }

    /**
     * Видалення запису.
     */
    public function handle_delete_item(): void {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        $permissions = Capabilities::get_regional_fst_permissions();
        if ( ! $permissions['canDelete'] ) {
            wp_send_json_error( [ 'message' => __( 'Недостатньо прав для видалення.', 'fstu' ) ] );
        }

        $id      = absint( $_POST['id'] ?? 0 );
        $service = new Class_Regional_FST_Service();

        try {
            $service->delete_item( $id );
            wp_send_json_success( [ 'message' => __( 'Посаду успішно видалено!', 'fstu' ) ] );
        } catch ( \Exception $e ) {
            wp_send_json_error( [ 'message' => $e->getMessage() ] );
        }
    }

    /**
     * Отримання протоколу операцій за стандартом.
     */
    public function handle_get_protocol(): void {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        $permissions = Capabilities::get_regional_fst_permissions();
        if ( ! $permissions['canProtocol'] ) {
            wp_send_json_error( [ 'message' => __( 'Недостатньо прав для перегляду протоколу.', 'fstu' ) ] );
        }

        global $wpdb;

        $search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
        $page     = max( 1, absint( $_POST['page'] ?? 1 ) );
        $per_page = min( max( absint( $_POST['per_page'] ?? 10 ), 1 ), 50 ); // Максимум 50
        $offset   = ( $page - 1 ) * $per_page;

        $where_parts = [ 'l.Logs_Name = %s' ];
        $params      = [ self::LOG_NAME ];

        if ( ! empty( $search ) ) {
            $where_parts[] = '(l.Logs_Text LIKE %s OR u.FIO LIKE %s)';
            $like_search   = '%' . $wpdb->esc_like( $search ) . '%';
            $params[]      = $like_search;
            $params[]      = $like_search;
        }

        $where_sql = implode( ' AND ', $where_parts );

        $count_sql = "
			SELECT COUNT(l.Logs_ID) 
			FROM Logs l 
			LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
			WHERE {$where_sql}
		";
        $total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );

        $select_sql = "
			SELECT l.Logs_DateCreate, l.Logs_Type, l.Logs_Name, l.Logs_Text, l.Logs_Error, u.FIO
			FROM Logs l 
			LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
			WHERE {$where_sql}
			ORDER BY l.Logs_DateCreate DESC
			LIMIT %d OFFSET %d
		";

        $data_params = array_merge( $params, [ $per_page, $offset ] );
        $items       = $wpdb->get_results( $wpdb->prepare( $select_sql, ...$data_params ), ARRAY_A );

        wp_send_json_success( [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total / $per_page ),
        ] );
    }

    /**
     * Швидкий AJAX-пошук користувачів для Select2 (в обхід vUserFSTU).
     */
    public function handle_search_users(): void {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        $permissions = Capabilities::get_regional_fst_permissions();
        if ( ! $permissions['canManage'] ) {
            wp_send_json_error( [] );
        }

        $search = sanitize_text_field( wp_unslash( $_POST['q'] ?? '' ) );
        $repo   = new Class_Regional_FST_Repository();
        $users  = $repo->search_users_for_select2( $search );

        // Select2 очікує масив об'єктів з полями id та text
        wp_send_json_success( [ 'results' => $users ] );
    }
    /**
     * Отримання одного запису для модального вікна редагування.
     */
    public function handle_get_item(): void {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        $permissions = Capabilities::get_regional_fst_permissions();
        if ( ! $permissions['canManage'] ) {
            wp_send_json_error( [ 'message' => __( 'Немає доступу.', 'fstu' ) ] );
        }

        $id   = absint( $_POST['id'] ?? 0 );
        $repo = new Class_Regional_FST_Repository();
        $item = $repo->get_item( $id );

        if ( ! $item ) {
            wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
        }

        wp_send_json_success( $item );
    }
    //--------------
}