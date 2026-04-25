<?php
/**
 * AJAX handler for модуля Статистики.
 * Version: 1.0.0
 * Date_update: 2026-04-25
 */

namespace FSTU\Modules\Part;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Part_Ajax {
    private Part_Repository $repo;
    private Part_Service $service;

    public function init(): void {
        $this->repo    = new Part_Repository();
        $this->service = new Part_Service();

        add_action( 'wp_ajax_fstu_part_get_items', [ $this, 'get_items' ] );
        add_action( 'wp_ajax_fstu_part_save_item', [ $this, 'save_item' ] );
        add_action( 'wp_ajax_fstu_part_delete_item', [ $this, 'delete_item' ] );
        add_action( 'wp_ajax_fstu_part_get_protocol', [ $this, 'get_protocol' ] );
    }

    private function check_permissions(): void {
        check_ajax_referer( 'fstu_part_nonce', 'nonce' );
        if ( ! current_user_can( Capabilities::VIEW_PART ) ) {
            wp_send_json_error( [ 'message' => 'Немає прав доступу.' ] );
        }
    }

    public function get_items(): void {
        $this->check_permissions();

        $user_id = get_current_user_id();
        $page    = max( 1, absint( $_POST['page'] ?? 1 ) );
        $limit   = max( 1, absint( $_POST['per_page'] ?? 10 ) );
        $offset  = ( $page - 1 ) * $limit;

        $filters = [
            'year'   => absint( $_POST['year'] ?? 0 ),
            'search' => sanitize_text_field( $_POST['search'] ?? '' ),
        ];

        $data = $this->repo->get_items( $user_id, $filters, $limit, $offset );
        $kpi  = $this->repo->get_kpi( $user_id, $filters['year'] );

        wp_send_json_success( [
            'items'       => $data['items'],
            'total'       => $data['total'],
            'kpi'         => $kpi,
            'page'        => $page,
            'total_pages' => ceil( $data['total'] / $limit ),
        ] );
    }

    public function save_item(): void {
        $this->check_permissions();
        if ( ! current_user_can( Capabilities::MANAGE_PART ) ) {
            wp_send_json_error( [ 'message' => 'Немає прав на редагування.' ] );
        }

        $success = $this->service->save_item( get_current_user_id(), $_POST['data'] ?? [] );
        if ( $success ) {
            wp_send_json_success( [ 'message' => 'Збережено успішно!' ] );
        } else {
            wp_send_json_error( [ 'message' => 'Помилка збереження.' ] );
        }
    }

    public function delete_item(): void {
        $this->check_permissions();
        if ( ! current_user_can( Capabilities::MANAGE_PART ) ) {
            wp_send_json_error( [ 'message' => 'Немає прав на видалення.' ] );
        }

        $part_id = absint( $_POST['part_id'] ?? 0 );
        if ( $this->service->delete_item( $part_id ) ) {
            wp_send_json_success( [ 'message' => 'Видалено успішно.' ] );
        } else {
            wp_send_json_error( [ 'message' => 'Помилка видалення.' ] );
        }
    }

    public function get_protocol(): void {
        check_ajax_referer( 'fstu_part_nonce', 'nonce' );
        if ( ! current_user_can( Capabilities::VIEW_PART_PROTOCOL ) && ! current_user_can( 'administrator' ) ) {
            wp_send_json_error( [ 'message' => 'Немає прав на перегляд протоколу.' ] );
        }

        $page    = max( 1, absint( $_POST['page'] ?? 1 ) );
        $limit   = max( 1, absint( $_POST['per_page'] ?? 10 ) );
        $offset  = ( $page - 1 ) * $limit;
        $search  = sanitize_text_field( $_POST['search'] ?? '' );

        $protocol = new Part_Protocol_Service();
        $data = $protocol->get_logs( $limit, $offset, $search );

        wp_send_json_success( [
            'items'       => $data['items'],
            'total'       => $data['total'],
            'page'        => $page,
            'total_pages' => ceil( $data['total'] / $limit ),
        ] );
    }
}