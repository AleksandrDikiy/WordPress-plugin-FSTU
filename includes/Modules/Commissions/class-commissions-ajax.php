<?php
/**
 * AJAX Controller для модуля "Комісії з видів туризму (Board)".
 * Відповідає за маршрутизацію запитів від шорткоду [fstu_board].
 *
 * * Version: 1.0.0
 * Date_update: 2026-04-18
 */

namespace FSTU\Modules\Commissions;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Commissions_Ajax {

    private const NONCE_ACTION = 'fstu_board_nonce';

    /**
     * Ініціалізація AJAX хуків.
     */
    public function init(): void {
        // Склад комісії
        add_action( 'wp_ajax_fstu_board_get_members', [ $this, 'handle_get_members' ] );
        add_action( 'wp_ajax_fstu_board_save_member', [ $this, 'handle_save_member' ] );
        add_action( 'wp_ajax_fstu_board_delete_member', [ $this, 'handle_delete_member' ] );

        // Опитування
        add_action( 'wp_ajax_fstu_board_get_polls', [ $this, 'handle_get_polls' ] );
        add_action( 'wp_ajax_fstu_board_save_poll', [ $this, 'handle_save_poll' ] );
        add_action( 'wp_ajax_fstu_board_delete_poll', [ $this, 'handle_delete_poll' ] );
        add_action( 'wp_ajax_fstu_board_send_email', [ $this, 'handle_send_email' ] );

        // Кандидати та Голосування
        add_action( 'wp_ajax_fstu_board_get_candidates', [ $this, 'handle_get_candidates' ] );
        add_action( 'wp_ajax_fstu_board_save_candidate', [ $this, 'handle_save_candidate' ] );
        add_action( 'wp_ajax_fstu_board_delete_candidate', [ $this, 'handle_delete_candidate' ] );
        add_action( 'wp_ajax_fstu_board_cast_vote', [ $this, 'handle_cast_vote' ] );

        // Протокол
        add_action( 'wp_ajax_fstu_board_get_protocol', [ $this, 'handle_get_protocol' ] );

        // Модальні вікна
        add_action( 'wp_ajax_fstu_board_get_modal', [ $this, 'handle_get_modal' ] );
        add_action( 'wp_ajax_fstu_board_search_users', [ $this, 'handle_search_users' ] );
    }

    /**
     * Отримання складу комісії.
     */
    public function handle_get_members(): void {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        if ( ! $this->current_user_can_view() ) {
            wp_send_json_error( [ 'message' => __( 'Недостатньо прав для перегляду.', 'fstu' ) ] );
        }

        $year_id            = isset( $_POST['year_id'] ) ? absint( $_POST['year_id'] ) : (int) date( 'Y' );
        $commission_type_id = isset( $_POST['commission_type_id'] ) ? absint( $_POST['commission_type_id'] ) : 1;
        $region_id          = isset( $_POST['region_id'] ) ? absint( $_POST['region_id'] ) : 30;
        $s_commission_id    = isset( $_POST['s_commission_id'] ) ? absint( $_POST['s_commission_id'] ) : 0;

        $page     = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
        $per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 10;
        $search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

        $repository = new Commissions_Repository();
        $result     = $repository->get_members_list( $year_id, $commission_type_id, $s_commission_id, $region_id, $page, $per_page, $search );

        wp_send_json_success( $result );
    }

    /**
     * Отримання списку опитувань комісії.
     */
    public function handle_get_polls(): void {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        if ( ! $this->current_user_can_view() ) {
            wp_send_json_error( [ 'message' => __( 'Недостатньо прав для перегляду.', 'fstu' ) ] );
        }

        $question_type_id   = isset( $_POST['question_type_id'] ) ? absint( $_POST['question_type_id'] ) : 1;
        $commission_type_id = isset( $_POST['commission_type_id'] ) ? absint( $_POST['commission_type_id'] ) : 1;
        $region_id          = isset( $_POST['region_id'] ) ? absint( $_POST['region_id'] ) : 30;
        $s_commission_id    = isset( $_POST['s_commission_id'] ) ? absint( $_POST['s_commission_id'] ) : 0;

        // Якщо користувач гість або не має прав, показуємо лише публічні опитування
        $question_state = is_user_logged_in() ? -1 : 0;

        $repository = new Commissions_Repository();
        $polls      = $repository->get_polls_list( $question_type_id, $commission_type_id, $s_commission_id, $region_id, $question_state );

        wp_send_json_success( [
            'items' => $polls,
        ] );
    }

    /**
     * Голосування за кандидата або відповідь (запис у CandidatesCommissionResult).
     */
    public function handle_cast_vote(): void {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        if ( ! current_user_can( Capabilities::VOTE_BOARD ) ) {
            wp_send_json_error( [ 'message' => __( 'У вас немає прав для голосування.', 'fstu' ) ] );
        }

        $candidate_id = isset( $_POST['candidate_id'] ) ? absint( $_POST['candidate_id'] ) : 0;
        $vote_value   = isset( $_POST['vote_value'] ) ? absint( $_POST['vote_value'] ) : 0; // 0 - Утрим, 1 - ЗА, 2 - ПРОТИ

        if ( ! $candidate_id ) {
            wp_send_json_error( [ 'message' => __( 'Не передано ID кандидата.', 'fstu' ) ] );
        }

        $service = new Commissions_Service();
        $result  = $service->cast_vote( get_current_user_id(), $candidate_id, $vote_value );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }

        wp_send_json_success( [ 'message' => __( 'Ваш голос успішно враховано.', 'fstu' ) ] );
    }

    /**
     * Отримання протоколу операцій.
     */
    public function handle_get_protocol(): void {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        if ( ! current_user_can( Capabilities::VIEW_BOARD_PROTOCOL ) ) {
            wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду протоколу.', 'fstu' ) ] );
        }

        $page     = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
        $per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 50;
        $search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

        $protocol_service = new Commissions_Protocol_Service();
        $logs             = $protocol_service->get_logs( $page, $per_page, $search );

        wp_send_json_success( $logs );
    }

    /**
     * Обробник AJAX пошуку користувачів для автокомпліту.
     */
    public function handle_search_users(): void {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        if ( ! $this->current_user_can_manage() ) {
            wp_send_json_error( [ 'message' => 'Немає прав.' ] );
        }

        $search = isset( $_POST['q'] ) ? sanitize_text_field( wp_unslash( $_POST['q'] ) ) : '';

        if ( mb_strlen( $search, 'UTF-8' ) < 3 ) {
            wp_send_json_success( [ 'items' => [], 'sql' => 'Занадто короткий запит' ] );
        }

        $repository = new Commissions_Repository();
        $users      = $repository->search_users( $search );

        global $wpdb;
        $debug_sql  = $wpdb->last_query; // Отримуємо точний SQL-запит, який пішов до бази

        wp_send_json_success( [
            'items' => $users,
            'sql'   => $debug_sql
        ] );
    }

    // ─── Допоміжні методи перевірки прав ──────────────────────────────────────

    private function current_user_can_view(): bool {
        // Публічний перегляд (або з обмеженням для ролей, визначено в Capabilities)
        return true;
    }

    private function current_user_can_manage(): bool {
        return current_user_can( 'manage_options' ) || current_user_can( Capabilities::MANAGE_BOARD );
    }

    private function current_user_can_delete(): bool {
        return current_user_can( 'manage_options' ) || current_user_can( Capabilities::DELETE_BOARD );
    }
    /**
     * Завантажує HTML модального вікна.
     */
    public function handle_get_modal(): void {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        $modal_name = isset( $_POST['modal'] ) ? sanitize_key( $_POST['modal'] ) : '';
        // Наприклад: 'member-form', 'poll-form'

        $file_path = FSTU_PLUGIN_DIR . "views/board/modals/modal-{$modal_name}.php";

        if ( file_exists( $file_path ) ) {
            ob_start();
            include $file_path;
            $html = ob_get_clean();
            wp_send_json_success( [ 'html' => $html ] );
        } else {
            wp_send_json_error( [ 'message' => __( 'Файл форми не знайдено.', 'fstu' ) ] );
        }
    }
    /**
     * Збереження члена комісії (Insert / Update).
     */
    public function handle_save_member(): void {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        if ( ! $this->current_user_can_manage() ) {
            wp_send_json_error( [ 'message' => __( 'Недостатньо прав.', 'fstu' ) ] );
        }

        $commission_id      = isset( $_POST['commission_id'] ) ? absint( $_POST['commission_id'] ) : 0;
        $user_id            = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
        $member_id          = isset( $_POST['member_id'] ) ? absint( $_POST['member_id'] ) : 0;
        $year_id            = isset( $_POST['year_id'] ) ? absint( $_POST['year_id'] ) : 0;
        $commission_type_id = isset( $_POST['commission_type_id'] ) ? absint( $_POST['commission_type_id'] ) : 0;
        $region_id          = isset( $_POST['region_id'] ) ? absint( $_POST['region_id'] ) : 0;
        $s_commission_id    = isset( $_POST['s_commission_id'] ) ? absint( $_POST['s_commission_id'] ) : 0;

        if ( ! $user_id || ! $member_id ) {
            wp_send_json_error( [ 'message' => __( 'Необхідно обрати користувача та роль.', 'fstu' ) ] );
        }

        $repository = new Commissions_Repository();
        $protocol   = new Commissions_Protocol_Service();

        $data = [
            'User_ID'           => $user_id,
            'Member_ID'         => $member_id,
            'Year_ID'           => $year_id,
            'CommissionType_ID' => $commission_type_id,
            'Region_ID'         => $region_id,
            'SCommission_ID'    => $s_commission_id,
        ];

        global $wpdb;
        $wpdb->query( 'START TRANSACTION' );

        if ( $commission_id > 0 ) {
            $result   = $wpdb->update( 'Commission', $data, [ 'Commission_ID' => $commission_id ], [ '%d', '%d', '%d', '%d', '%d', '%d' ], [ '%d' ] );
            $log_type = 'U';
            $log_text = "Оновлено запис у комісії ID: {$commission_id}";
        } else {
            $data['Commission_DateCreate'] = current_time( 'mysql' );
            $data['User_Create']           = get_current_user_id();
            $result   = $wpdb->insert( 'Commission', $data, [ '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%d' ] );
            $log_type = 'I';
            $log_text = "Додано нового члена (User ID: {$user_id}) до комісії";
        }

        if ( false === $result ) {
            $wpdb->query( 'ROLLBACK' );
            $protocol->log_action( $log_type, $log_text, 'error' );
            wp_send_json_error( [ 'message' => __( 'Помилка збереження бази даних.', 'fstu' ) ] );
        }

        $protocol->log_action( $log_type, $log_text, '✓' );
        $wpdb->query( 'COMMIT' );

        wp_send_json_success( [ 'message' => __( 'Успішно збережено!', 'fstu' ) ] );
    }
    /**
     * Отримання списку кандидатів для опитування.
     */
    public function handle_get_candidates(): void {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        if ( ! $this->current_user_can_view() ) {
            wp_send_json_error( [ 'message' => __( 'Недостатньо прав для перегляду.', 'fstu' ) ] );
        }

        $question_id = isset( $_POST['question_id'] ) ? absint( $_POST['question_id'] ) : 0;

        if ( ! $question_id ) {
            wp_send_json_error( [ 'message' => __( 'Не передано ID опитування.', 'fstu' ) ] );
        }

        $repository = new Commissions_Repository();
        $candidates = $repository->get_candidates_list( $question_id, get_current_user_id() );

        wp_send_json_success( [ 'items' => $candidates ] );
    }
    public function handle_delete_member(): void {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );
        if ( ! current_user_can( Capabilities::DELETE_BOARD ) ) {
            wp_send_json_error( [ 'message' => 'Немає прав на видалення.' ] );
        }

        $id = absint( $_POST['id'] );
        $repository = new Commissions_Repository();
        $protocol = new Commissions_Protocol_Service();

        if ( $repository->delete_member( $id ) ) {
            $protocol->log_action( 'D', "Видалено члена комісії ID: $id", '✓' );
            wp_send_json_success( [ 'message' => 'Запис видалено.' ] );
        }
        wp_send_json_error( [ 'message' => 'Помилка БД при видаленні.' ] );
    }

    public function handle_delete_poll(): void {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );
        if ( ! current_user_can( Capabilities::DELETE_BOARD ) ) {
            wp_send_json_error( [ 'message' => 'Немає прав.' ] );
        }

        $id = absint( $_POST['id'] );
        $repository = new Commissions_Repository();
        if ( $repository->delete_poll( $id ) ) {
            (new Commissions_Protocol_Service())->log_action( 'D', "Видалено опитування ID: $id разом з результатами", '✓' );
            wp_send_json_success();
        }
        wp_send_json_error();
    }
    // TODO: Додати імплементацію handle_save_member(), handle_save_poll(), handle_send_email()
    // через виклики відповідних методів у Repository та Service з обов'язковим Protocol_Service->log_action().
}