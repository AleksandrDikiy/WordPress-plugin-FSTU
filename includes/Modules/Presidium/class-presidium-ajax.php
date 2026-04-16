<?php
/**
 * AJAX обробник модуля Presidium.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-16
 *
 * @package FSTU\Modules\Presidium
 */

namespace FSTU\Modules\Presidium;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Presidium_Ajax {

    private Presidium_Service $service;

    public function __construct() {
        $this->service = new Presidium_Service();
    }

    public function init(): void {
        // Список членів (публічний)
        add_action( 'wp_ajax_fstu_presidium_get_members', [ $this, 'handle_get_members' ] );
        add_action( 'wp_ajax_nopriv_fstu_presidium_get_members', [ $this, 'handle_get_members' ] );

        // Фото (публічне, через проксі)
        add_action( 'wp_ajax_fstu_presidium_get_photo', [ $this, 'handle_get_photo' ] );
        add_action( 'wp_ajax_nopriv_fstu_presidium_get_photo', [ $this, 'handle_get_photo' ] );
    }

    public function handle_get_members(): void {
        check_ajax_referer( 'fstu_module_nonce', 'nonce' );

        $members = $this->service->get_members_list();
        wp_send_json_success( [ 'items' => $members ] );
    }

    /**
     * Проксі для фото (ідентично Directory)
     */
    public function handle_get_photo(): void {
        $id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        if ( ! $id ) wp_die();

        $url = "https://fstu.com.ua/photo/{$id}.jpg";
        $response = wp_remote_get( $url, [ 'timeout' => 3 ] );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#cbd5e1"><path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm0 2c-3.33 0-10 1.67-10 5v2h20v-2c0-3.33-6.67-5-10-5z"/></svg>';
            header( 'Content-Type: image/svg+xml' );
            echo $svg;
            exit;
        }

        header( 'Content-Type: image/jpeg' );
        echo wp_remote_retrieve_body( $response );
        exit;
    }
}