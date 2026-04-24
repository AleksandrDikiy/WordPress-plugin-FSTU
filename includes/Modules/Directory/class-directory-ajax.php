<?php
/**
 * AJAX обробник модуля Directory.
 *
 * Version:     1.1.0
 * Date_update: 2026-04-24
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
        // Склад Виконкому (Публічний доступ)
        add_action( 'wp_ajax_fstu_directory_get_members', [ $this, 'handle_get_members' ] );
        add_action( 'wp_ajax_nopriv_fstu_directory_get_members', [ $this, 'handle_get_members' ] );

        add_action( 'wp_ajax_fstu_get_photo', [ $this, 'handle_get_photo' ] );
        add_action( 'wp_ajax_nopriv_fstu_get_photo', [ $this, 'handle_get_photo' ] );

        // Опитування та Голосування
        add_action( 'wp_ajax_fstu_directory_get_polls', [ $this, 'handle_get_polls' ] );
        add_action( 'wp_ajax_nopriv_fstu_directory_get_polls', [ $this, 'handle_get_polls' ] );
        add_action( 'wp_ajax_fstu_directory_get_poll_details', [ $this, 'handle_get_poll_details' ] );
        add_action( 'wp_ajax_nopriv_fstu_directory_get_poll_details', [ $this, 'handle_get_poll_details' ] );
        add_action( 'wp_ajax_fstu_directory_cast_vote', [ $this, 'handle_cast_vote' ] );

        // Протокол
        add_action( 'wp_ajax_fstu_directory_get_protocol', [ $this, 'handle_get_protocol' ] );
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
     * Отримання списку членів Виконкому (Публічний доступ).
     */
    public function handle_get_members(): void {
        check_ajax_referer( 'fstu_module_nonce', 'nonce' );

        $members = $this->service->get_repository()->get_members();

        wp_send_json_success( [ 'items' => $members ] );
    }

    public function handle_get_polls(): void {
        check_ajax_referer( 'fstu_module_nonce', 'nonce' );

        $type_guidance_id = 1; // 1 - Виконком
        $question_state = is_user_logged_in() ? -1 : 0; // Гості бачать тільки відкриті

        $page     = !empty( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
        $per_page = !empty( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 10;

        $polls_data = $this->service->get_repository()->get_polls_list( $type_guidance_id, $question_state, $page, $per_page );
        wp_send_json_success( $polls_data );
    }

    public function handle_get_poll_details(): void {
        check_ajax_referer( 'fstu_module_nonce', 'nonce' );

        $question_id = isset( $_POST['question_id'] ) ? absint( $_POST['question_id'] ) : 0;
        $state       = isset( $_POST['state'] ) ? absint( $_POST['state'] ) : 0;

        if ( ! $question_id ) wp_send_json_error( [ 'message' => 'Не передано ID.' ] );

        $show_names = ( $state === 0 && current_user_can( 'userfstu' ) ) || current_user_can( 'administrator' );
        $details    = $this->service->get_repository()->get_poll_details( $question_id, $show_names );

        wp_send_json_success( $details );
    }

    public function handle_cast_vote(): void {
        check_ajax_referer( 'fstu_module_nonce', 'nonce' );

        $question_id = isset( $_POST['question_id'] ) ? absint( $_POST['question_id'] ) : 0;
        $answer_id   = isset( $_POST['answer_id'] ) ? absint( $_POST['answer_id'] ) : 0;
        $type_id     = 1; // Виконком

        if ( ! $question_id || ! $answer_id ) wp_send_json_error( [ 'message' => 'Помилка даних.' ] );

        $result = $this->service->cast_issue_vote( get_current_user_id(), $question_id, $answer_id, $type_id );

        if ( is_wp_error( $result ) ) wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        wp_send_json_success( [ 'message' => 'Голос враховано.' ] );
    }

    public function handle_get_protocol(): void {
        check_ajax_referer( 'fstu_module_nonce', 'nonce' );

        if ( ! current_user_can( 'administrator' ) ) {
            wp_send_json_error( [ 'message' => 'Немає прав для перегляду протоколу.' ] );
        }

        // Жорсткий захист від порожніх значень та ліміт 10 за замовчуванням
        $page     = !empty( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
        $per_page = !empty( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 10;
        $search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

        // Гарантоване підключення файлу сервісу протоколювання, якщо автозавантажувач його не побачив
        if ( ! class_exists( 'FSTU\Modules\Directory\Directory_Protocol_Service' ) ) {
            $protocol_file = __DIR__ . '/class-directory-protocol-service.php';
            if ( file_exists( $protocol_file ) ) {
                require_once $protocol_file;
            } else {
                wp_send_json_error( [ 'message' => 'Файл class-directory-protocol-service.php не знайдено у директорії!' ] );
            }
        }

        try {
            $protocol = new Directory_Protocol_Service();
            wp_send_json_success( $protocol->get_logs( $page, $per_page, $search ) );
        } catch ( \Throwable $e ) {
            // Перехоплюємо SQL помилки, щоб AJAX не падав з Error 500
            wp_send_json_error( [ 'message' => 'Технічна помилка: ' . $e->getMessage() ] );
        }
    }
}