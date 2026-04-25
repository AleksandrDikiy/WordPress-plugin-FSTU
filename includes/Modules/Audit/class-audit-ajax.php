<?php
/**
 * AJAX контролер модуля "Ревізійна комісія".
 *
 * Version:     1.0.0
 * Date_update: 2026-04-25
 *
 * @package FSTU\Modules\Audit
 */

namespace FSTU\Modules\Audit;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Audit_Ajax {

    private Audit_Repository $repository;

    public function __construct() {
        $this->repository = new Audit_Repository();
    }

    public function init(): void {
        add_action( 'wp_ajax_fstu_audit_get_members', [ $this, 'handle_get_members' ] );
        add_action( 'wp_ajax_nopriv_fstu_audit_get_members', [ $this, 'handle_get_members' ] );
        add_action( 'wp_ajax_fstu_audit_delete', [ $this, 'handle_delete_member' ] );
    }

    public function handle_get_members(): void {
        check_ajax_referer( 'fstu_module_nonce', 'nonce' );
        $data = $this->repository->get_members();
        wp_send_json_success( [ 'items' => $data['items'] ] );
    }

    public function handle_delete_member(): void {
        check_ajax_referer( 'fstu_module_nonce', 'nonce' );

        if ( ! current_user_can( Capabilities::DELETE_AUDIT ) && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Недостатньо прав для видалення.', 'fstu' ) ] );
        }

        $guidance_id = isset( $_POST['guidance_id'] ) ? absint( $_POST['guidance_id'] ) : 0;
        $user_id     = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

        if ( ! $guidance_id ) {
            wp_send_json_error( [ 'message' => __( 'Не передано ID запису.', 'fstu' ) ] );
        }

        $result = $this->repository->delete_member( $guidance_id, $user_id );

        if ( is_wp_error( $result ) ) wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        wp_send_json_success( [ 'message' => __( 'Запис успішно видалено.', 'fstu' ) ] );
    }
}