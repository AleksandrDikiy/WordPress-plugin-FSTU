<?php
/**
 * AJAX обробник модуля Directory.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-16
 *
 * @package FSTU\Modules\Directory
 */

namespace FSTU\Modules\Directory;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Directory_Ajax {

    private Directory_Service $service;

    public function __construct() {
        $this->service = new Directory_Service();
    }

    /**
     * Реєстрація AJAX ендпоінтів.
     */
    public function init(): void {
        add_action( 'wp_ajax_fstu_directory_get_members', [ $this, 'handle_get_members' ] );
        add_action( 'wp_ajax_fstu_get_photo', [ $this, 'handle_get_photo' ] );
        add_action( 'wp_ajax_nopriv_fstu_get_photo', [ $this, 'handle_get_photo' ] );
    }

    /**
     * Проксі для безпечного завантаження фото без розкриття URL.
     */
    public function handle_get_photo(): void {
        $id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

        if ( ! $id ) {
            wp_die();
        }

        $url = "https://fstu.com.ua/photo/{$id}.jpg";
        $response = wp_remote_get( $url, [ 'timeout' => 3 ] );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            // Фото не знайдено. Замість 404 помилки віддаємо дефолтний SVG-аватар (статус 200 OK)
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#cbd5e1"><path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm0 2c-3.33 0-10 1.67-10 5v2h20v-2c0-3.33-6.67-5-10-5z"/></svg>';

            header( 'Content-Type: image/svg+xml' );
            header( 'Cache-Control: public, max-age=86400' );
            echo $svg;
            exit;
        }

        header( 'Content-Type: image/jpeg' );
        header( 'Cache-Control: public, max-age=86400' ); // Кешуємо в браузері на 1 день
        echo wp_remote_retrieve_body( $response );
        exit;
    }


    /**
     * Перевірка базової безпеки для всіх запитів.
     */
    private function verify_request(): void {
        check_ajax_referer( 'fstu_module_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => __( 'Не авторизовано.', 'fstu' ) ] );
        }
    }

    /**
     * Отримання списку членів Виконкому.
     */
    public function handle_get_members(): void {
        $this->verify_request();

        // Перевірка прав доступу на читання
        $permissions = Capabilities::get_directory_permissions();
        if ( empty( $permissions['canViewList'] ) ) {
            wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду.', 'fstu' ) ] );
        }

        $members = $this->service->get_repository()->get_members();

        wp_send_json_success( [ 'items' => $members ] );
    }

}