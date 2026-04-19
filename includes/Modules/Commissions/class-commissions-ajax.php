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

        // Кандидати та Голосування
        add_action( 'wp_ajax_fstu_board_get_candidates', [ $this, 'handle_get_candidates' ] );
        add_action( 'wp_ajax_nopriv_fstu_board_get_candidates', [ $this, 'handle_get_candidates' ] );
        add_action( 'wp_ajax_fstu_board_save_candidate', [ $this, 'handle_save_candidate' ] );
        add_action( 'wp_ajax_fstu_board_delete_candidate', [ $this, 'handle_delete_candidate' ] );
        add_action( 'wp_ajax_fstu_board_cast_vote', [ $this, 'handle_cast_vote' ] );
        add_action( 'wp_ajax_fstu_board_get_voters', [ $this, 'handle_get_voters' ] );
        add_action( 'wp_ajax_nopriv_fstu_board_get_voters', [ $this, 'handle_get_voters' ] );

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
            // Віддаємо помилку (наприклад: "Ви вичерпали ліміт голосів" або "Не можна голосувати ЗА себе")
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }

        if ( is_string( $result ) && $result === 'self_removed' ) {
            // Якщо користувач зняв власну кандидатуру
            wp_send_json_success( [ 'message' => 'Вашу кандидатуру успішно знято (самовідвід).' ] );
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
            wp_send_json_success( [ 'message' => 'Опитування видалено.' ] );
        }
        wp_send_json_error( [ 'message' => 'Помилка бази даних при видаленні.' ] );
    }
    /**
     * Збереження опитування (Insert / Update).
     */
    public function handle_save_poll(): void {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        if ( ! $this->current_user_can_manage() ) {
            wp_send_json_error( [ 'message' => __( 'Недостатньо прав.', 'fstu' ) ] );
        }

        $question_id = isset( $_POST['question_id'] ) ? absint( $_POST['question_id'] ) : 0;

        global $wpdb;
        $protocol = new Commissions_Protocol_Service();
        $user_id  = get_current_user_id();

        // Санітизація вхідних даних
        $q_name   = isset( $_POST['question_name'] ) ? sanitize_text_field( wp_unslash( $_POST['question_name'] ) ) : '';
        $q_note   = isset( $_POST['question_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['question_note'] ) ) : '';
        $q_begin  = isset( $_POST['question_date_begin'] ) ? sanitize_text_field( $_POST['question_date_begin'] ) : '';
        $q_end    = isset( $_POST['question_date_end'] ) ? sanitize_text_field( $_POST['question_date_end'] ) : '';
        $q_state  = isset( $_POST['question_state'] ) ? absint( $_POST['question_state'] ) : 0;
        $q_url    = isset( $_POST['question_url'] ) ? esc_url_raw( wp_unslash( $_POST['question_url'] ) ) : '';

        $c_type   = isset( $_POST['commission_type_id'] ) ? absint( $_POST['commission_type_id'] ) : 0;
        $c_region = isset( $_POST['region_id'] ) ? absint( $_POST['region_id'] ) : 30;
        $c_id     = isset( $_POST['s_commission_id'] ) ? absint( $_POST['s_commission_id'] ) : 0;
        $q_count  = isset( $_POST['set_commission_count'] ) ? absint( $_POST['set_commission_count'] ) : 1;

        if ( $question_id > 0 ) {
            // --- ОНОВЛЕННЯ ---
            $wpdb->query( 'START TRANSACTION' );

            // 1. Оновлюємо таблицю Question
            $res_q = $wpdb->update(
                'Question',
                [
                    'Question_Name'      => $q_name,
                    'Question_Note'      => $q_note,
                    'Question_DateBegin' => $q_begin,
                    'Question_DateEnd'   => $q_end,
                    'Question_State'     => $q_state,
                    'Question_URL'       => $q_url,
                ],
                [ 'Question_ID' => $question_id ],
                [ '%s', '%s', '%s', '%s', '%d', '%s' ],
                [ '%d' ]
            );

            // 2. Оновлюємо квоту у зв'язковій таблиці
            $wpdb->update(
                'QuestionCommission',
                [ 'SetCommission_CountMembers' => $q_count ],
                [ 'Question_ID' => $question_id ],
                [ '%d' ],
                [ '%d' ]
            );

            if ( false === $res_q ) {
                $wpdb->query( 'ROLLBACK' );
                $protocol->log_action( 'U', "Помилка оновлення опитування ID: {$question_id}", 'error' );
                wp_send_json_error( [ 'message' => __( 'Помилка оновлення бази даних.', 'fstu' ) ] );
            }

            $protocol->log_action( 'U', "Оновлено опитування ID: {$question_id}", '✓' );
            $wpdb->query( 'COMMIT' );
            wp_send_json_success( [ 'message' => __( 'Опитування успішно оновлено!', 'fstu' ) ] );

        } else {
            // --- СТВОРЕННЯ (через існуючу збережену процедуру бази даних) ---
            $wpdb->get_var(
                $wpdb->prepare(
                    "call InsertQuestionCommission(%d, %d, %d, %d, %d, %d, %s, %s, %d, %s, %s, %s, %s, %d, @ResultID)",
                    $user_id,
                    $c_id,
                    2, // QuestionType_ID = 2 (Опитування за кандидатів)
                    $c_type,
                    $c_region,
                    1, // AnswerPool_ID = 1 (Стандартний набір)
                    $q_begin,
                    $q_end,
                    $q_state,
                    $q_name,
                    $q_note,
                    $q_url,
                    '1', // Внутрішній прапорець з legacy
                    $q_count
                )
            );

            $result_id = $wpdb->get_var( "SELECT @ResultID" );

            if ( $result_id == 1 ) {
                $protocol->log_action( 'I', "Створено нове опитування: {$q_name}", '✓' );
                wp_send_json_success( [ 'message' => __( 'Опитування успішно створено!', 'fstu' ) ] );
            } else {
                $error_msg = $wpdb->last_error;
                $protocol->log_action( 'I', "Помилка створення опитування", 'error' );
                wp_send_json_error( [ 'message' => __( 'Помилка бази даних при створенні опитування. ' . $error_msg, 'fstu' ) ] );
            }
        }
    }
    /**
     * Збереження кандидата.
     */
    public function handle_save_candidate(): void {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        if ( ! $this->current_user_can_manage() ) {
            wp_send_json_error( [ 'message' => __( 'Недостатньо прав.', 'fstu' ) ] );
        }

        $question_id = isset( $_POST['question_id'] ) ? absint( $_POST['question_id'] ) : 0;
        $user_id     = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
        $direction   = isset( $_POST['direction'] ) ? sanitize_text_field( wp_unslash( $_POST['direction'] ) ) : '';
        $development = isset( $_POST['development'] ) ? sanitize_textarea_field( wp_unslash( $_POST['development'] ) ) : '';
        $url         = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';

        if ( ! $question_id || ! $user_id ) {
            wp_send_json_error( [ 'message' => 'Не передано ID опитування або кандидата.' ] );
        }

        $repository = new Commissions_Repository();
        $result = $repository->save_candidate( $question_id, $user_id, $direction, $development, $url );

        if ( $result === true ) {
            (new Commissions_Protocol_Service())->log_action( 'I', "Додано кандидата (User ID: {$user_id}) до опитування ID: {$question_id}", '✓' );
            wp_send_json_success( [ 'message' => 'Кандидата успішно додано!' ] );
        } else {
            (new Commissions_Protocol_Service())->log_action( 'I', "Помилка додавання кандидата", 'error' );
            wp_send_json_error( [ 'message' => $result ] );
        }
    }
    /**
     * Отримання списку тих, хто проголосував.
     */
    public function handle_get_voters(): void {
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );

        // Перевіряємо, чи має юзер роль/право userfstu (або є адміном)
        if ( ! current_user_can( 'userfstu' ) && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'У вас немає прав для перегляду списку тих, хто проголосував.' ] );
        }

        $candidate_id = isset( $_POST['candidate_id'] ) ? absint( $_POST['candidate_id'] ) : 0;

        if ( ! $candidate_id ) {
            wp_send_json_error( [ 'message' => 'Не передано ID кандидата.' ] );
        }

        $repository = new Commissions_Repository();
        $voters     = $repository->get_voters_for_candidate( $candidate_id );

        // Визначаємо, чи є користувач адміністратором, щоб показати колонку "Як проголосував"
        $is_admin = current_user_can( 'administrator' );

        wp_send_json_success( [
            'items'    => $voters,
            'is_admin' => $is_admin
        ] );
    }
    //---
}