<?php
/**
 * AJAX Controller для модуля "Комісії з видів туризму (Board)".
 * Відповідає за маршрутизацію запитів від шорткоду [fstu_board].
 *
 * * Version: 1.1.0
 * Date_update: 2026-04-24
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
        // Склад комісії (Додано nopriv для гостей)
        add_action( 'wp_ajax_fstu_board_get_members', [ $this, 'handle_get_members' ] );
        add_action( 'wp_ajax_nopriv_fstu_board_get_members', [ $this, 'handle_get_members' ] );
        add_action( 'wp_ajax_fstu_board_save_member', [ $this, 'handle_save_member' ] );
        add_action( 'wp_ajax_fstu_board_delete_member', [ $this, 'handle_delete_member' ] );

        // Опитування
        add_action( 'wp_ajax_fstu_board_get_polls', [ $this, 'handle_get_polls' ] );
        add_action( 'wp_ajax_nopriv_fstu_board_get_polls', [ $this, 'handle_get_polls' ] );
        add_action( 'wp_ajax_fstu_board_save_poll', [ $this, 'handle_save_poll' ] );
        add_action( 'wp_ajax_fstu_board_delete_poll', [ $this, 'handle_delete_poll' ] );
        add_action( 'wp_ajax_fstu_board_send_email', [ $this, 'handle_send_email' ] );

        // Опитування та Голосування (Новий формат)
        add_action( 'wp_ajax_fstu_board_get_poll_details', [ $this, 'handle_get_poll_details' ] );
        add_action( 'wp_ajax_nopriv_fstu_board_get_poll_details', [ $this, 'handle_get_poll_details' ] );
        add_action( 'wp_ajax_fstu_board_cast_vote', [ $this, 'handle_cast_vote' ] );

        // Протокол
        add_action( 'wp_ajax_fstu_board_get_protocol', [ $this, 'handle_get_protocol' ] );
        add_action( 'wp_ajax_nopriv_fstu_board_get_protocol', [ $this, 'handle_get_protocol' ] );

        // Модальні вікна та пошук
        add_action( 'wp_ajax_fstu_board_get_modal', [ $this, 'handle_get_modal' ] );
        add_action( 'wp_ajax_nopriv_fstu_board_get_modal', [ $this, 'handle_get_modal' ] );
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

        // Дозволяємо приймати 0 (Поточний склад). Якщо не передано взагалі - беремо 0.
        $year_id            = isset( $_POST['year_id'] ) ? intval( $_POST['year_id'] ) : 0;
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
            wp_send_json_success( [ 'items' => [] ] );
        }

        $repository = new Commissions_Repository();
        $users      = $repository->search_users( $search );

        wp_send_json_success( [
            'items' => $users
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
     * Отримання деталей опитування.
     */
    public function handle_get_poll_details(): void {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        $question_id = isset( $_POST['question_id'] ) ? absint( $_POST['question_id'] ) : 0;
        $state       = isset( $_POST['state'] ) ? absint( $_POST['state'] ) : 0;

        if ( ! $question_id ) {
            wp_send_json_error( [ 'message' => 'Не передано ID опитування.' ] );
        }

        // Поіменне голосування бачать усі, якщо воно публічне (0), або якщо це Адмін.
        $show_names = ( $state === 0 && current_user_can( 'userfstu' ) ) || current_user_can( 'administrator' );

        $repository = new Commissions_Repository();
        $details    = $repository->get_poll_details( $question_id, $show_names );

        wp_send_json_success( $details );
    }

    /**
     * Голосування за поточне питання (документ).
     */
    public function handle_cast_vote(): void {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        $question_id        = isset( $_POST['question_id'] ) ? absint( $_POST['question_id'] ) : 0;
        $answer_id          = isset( $_POST['answer_id'] ) ? absint( $_POST['answer_id'] ) : 0;
        $commission_type_id = isset( $_POST['commission_type_id'] ) ? absint( $_POST['commission_type_id'] ) : 1;
        $s_commission_id    = isset( $_POST['s_commission_id'] ) ? absint( $_POST['s_commission_id'] ) : 0;
        $region_id          = isset( $_POST['region_id'] ) ? absint( $_POST['region_id'] ) : 30;

        if ( ! $question_id || ! $answer_id ) {
            wp_send_json_error( [ 'message' => 'Не передані обов\'язкові дані.' ] );
        }

        $service = new Commissions_Service();
        $result  = $service->cast_issue_vote( get_current_user_id(), $question_id, $answer_id, $commission_type_id, $s_commission_id, $region_id );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }

        wp_send_json_success( [ 'message' => 'Ваш голос успішно враховано.' ] );
    }

    /**
     * Створення/Редагування опитування (документу).
     */
    public function handle_save_poll(): void {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        if ( ! $this->current_user_can_manage() ) {
            wp_send_json_error( [ 'message' => __( 'Немає прав.', 'fstu' ) ] );
        }

        $question_id = isset( $_POST['question_id'] ) ? absint( $_POST['question_id'] ) : 0;

        global $wpdb;
        $wpdb->query( 'START TRANSACTION' );

        $data = [
            'Question_Name'      => sanitize_text_field( $_POST['question_name'] ?? '' ),
            'Question_Note'      => sanitize_textarea_field( $_POST['question_note'] ?? '' ),
            'Question_URL'       => esc_url_raw( $_POST['question_url'] ?? '' ),
            'Question_DateBegin' => sanitize_text_field( $_POST['date_begin'] ?? current_time('Y-m-d') ),
            'Question_DateEnd'   => sanitize_text_field( $_POST['date_end'] ?? current_time('Y-m-d') ),
            'Question_State'     => isset($_POST['is_private']) && $_POST['is_private'] == '1' ? 1 : 0,
        ];

        if ( $question_id > 0 ) {
            $result = $wpdb->update( 'Question', $data, [ 'Question_ID' => $question_id ], null, [ '%d' ] );
        } else {
            $data['User_ID'] = get_current_user_id();
            $data['QuestionType_ID'] = 1; // 1 - Поточні питання / Документи
            $data['Question_DateCreate'] = current_time( 'mysql' );

            $result = $wpdb->insert( 'Question', $data );
            $question_id = $wpdb->insert_id;

            // Прив'язуємо до комісії
            $wpdb->insert( 'QuestionCommission', [
                'Question_ID'       => $question_id,
                'Commission_ID'     => absint( $_POST['s_commission_id'] ?? 0 ),
                'CommissionType_ID' => absint( $_POST['commission_type_id'] ?? 1 ),
                'Region_ID'         => 30 // За замовчуванням
            ]);

            // Прив'язуємо стандартні відповіді ФСТУ: ЗА(1), ПРОТИ(2), УТРИМАВСЯ(3)
            $wpdb->insert( 'AnswerQuestion', [ 'Question_ID' => $question_id, 'Answer_ID' => 1 ] );
            $wpdb->insert( 'AnswerQuestion', [ 'Question_ID' => $question_id, 'Answer_ID' => 2 ] );
            $wpdb->insert( 'AnswerQuestion', [ 'Question_ID' => $question_id, 'Answer_ID' => 3 ] );
        }

        if ( false === $result ) {
            $wpdb->query( 'ROLLBACK' );
            wp_send_json_error( [ 'message' => 'Помилка збереження.' ] );
        }

        $wpdb->query( 'COMMIT' );
        wp_send_json_success( [ 'message' => 'Опитування успішно збережено.' ] );
    }

    /**
     * Видалення опитування.
     */
    public function handle_delete_poll(): void {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );
        if ( ! $this->current_user_can_delete() ) {
            wp_send_json_error( [ 'message' => __( 'Немає прав.', 'fstu' ) ] );
        }

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $repo = new Commissions_Repository();

        if ( $repo->delete_poll( $id ) ) {
            wp_send_json_success( [ 'message' => 'Опитування видалено.' ] );
        }
        wp_send_json_error( [ 'message' => 'Помилка видалення.' ] );
    }
}