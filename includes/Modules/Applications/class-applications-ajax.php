<?php

namespace FSTU\Modules\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Клас обробки AJAX запитів модуля "Заявки в ФСТУ".
 * Фаза 4: CRUD операції, Транзакції та Логування.
 *
 * Version:     1.7.1
 * Date_update: 2026-04-24
 */
class Applications_Ajax {

    private const LOG_NAME = 'Applications';
    private const MAX_PER_PAGE = 50;
    private const DEBUG_FLAG = 'FSTU_DEBUG_APPLICATIONS';

    private ?Applications_Repository $repository = null;
    private ?Applications_Protocol_Service $protocol_service = null;
    private ?Applications_Mailer $mailer = null;
    private ?Applications_Service $service = null;

    public function init(): void {
        add_action( 'wp_ajax_fstu_applications_get_list',    [ $this, 'handle_get_list' ] );
        add_action( 'wp_ajax_fstu_applications_get_single',  [ $this, 'handle_get_single' ] );
        add_action( 'wp_ajax_fstu_applications_vote',        [ $this, 'handle_vote' ] );
        add_action( 'wp_ajax_fstu_applications_accept',      [ $this, 'handle_accept' ] );
        add_action( 'wp_ajax_fstu_applications_change_ofst', [ $this, 'handle_change_ofst' ] );
        add_action( 'wp_ajax_fstu_applications_send_email',  [ $this, 'handle_send_message' ] );
        add_action( 'wp_ajax_fstu_applications_reject',      [ $this, 'handle_reject' ] );
        add_action( 'wp_ajax_fstu_applications_get_protocol', [ $this, 'handle_get_protocol' ] );
    }

    /**
     * Повертає repository модуля.
     */
    private function get_repository(): Applications_Repository {
        if ( null === $this->repository ) {
            $this->repository = new Applications_Repository();
        }

        return $this->repository;
    }

    /**
     * Повертає сервіс протоколу модуля.
     */
    private function get_protocol_service(): Applications_Protocol_Service {
        if ( null === $this->protocol_service ) {
            $this->protocol_service = new Applications_Protocol_Service();
        }

        return $this->protocol_service;
    }

    /**
     * Повертає mailer модуля.
     */
    private function get_mailer(): Applications_Mailer {
        if ( null === $this->mailer ) {
            $this->mailer = new Applications_Mailer();
        }

        return $this->mailer;
    }

    /**
     * Повертає бізнес-сервіс модуля.
     */
    private function get_service(): Applications_Service {
        if ( null === $this->service ) {
            $this->service = new Applications_Service(
                $this->get_repository(),
                $this->get_protocol_service(),
                $this->get_mailer()
            );
        }

        return $this->service;
    }

    /**
     * Чи ввімкнено debug-режим модуля заявок.
     */
    private function is_debug_enabled(): bool {
        return defined( self::DEBUG_FLAG ) && constant( self::DEBUG_FLAG );
    }

    /**
     * Чи може користувач керувати заявками.
     */
    private function current_user_can_manage_applications(): bool {
        $user  = wp_get_current_user();
        $roles = is_array( $user->roles ) ? $user->roles : [];

        return current_user_can( 'manage_options' )
            || in_array( 'administrator', $roles, true )
            || in_array( 'userregistrar', $roles, true );
    }

    /**
     * Чи є поточний користувач адміністратором модуля.
     */
    private function current_user_is_administrator(): bool {
        $user  = wp_get_current_user();
        $roles = is_array( $user->roles ) ? $user->roles : [];

        return current_user_can( 'manage_options' ) || in_array( 'administrator', $roles, true );
    }

    /**
     * Повертає true, якщо спрацювало honeypot-поле.
     */
    private function is_honeypot_triggered(): bool {
        $honeypot = sanitize_text_field( wp_unslash( $_POST['fstu_website'] ?? '' ) );

        return '' !== trim( $honeypot );
    }

    /**
     * Формує safe error payload для AJAX-відповіді.
     *
     * @return array{message: string, code: string}
     */
    private function build_error_payload( string $message, string $code ): array {
        return [
            'message' => $message,
            'code'    => $code,
        ];
    }

    /**
     * Повертає повідомлення для accept-flow.
     */
    private function get_accept_error_message( string $marker ): string {
        return match ( $marker ) {
            'candidate_not_found'             => 'Кандидата не знайдено.',
            'candidate_region_not_found'      => 'Для кандидата не визначено область.',
            'ticket_number_not_generated'     => 'Не вдалося сформувати номер квитка.',
            'candidate_already_accepted'      => 'Кандидат уже прийнятий до ФСТУ та має квиток.',
            'candidate_role_already_accepted' => 'Кандидат уже має роль члена ФСТУ. Перевірте картку вручну.',
            'candidate_card_already_exists'   => 'Для кандидата вже існує членський квиток. Перевірте картку вручну.',
            'member_card_insert_failed'       => 'Не вдалося створити членський квиток кандидата.',
            'applications_log_insert_failed'  => 'Сталася помилка збереження протоколу. Операцію скасовано.',
            default                           => 'Сталася помилка при обробці заявки.',
        };
    }

    /**
     * Повертає повідомлення для reject-flow.
     */
    private function get_reject_error_message( string $marker ): string {
        return match ( $marker ) {
            'candidate_not_found'             => 'Кандидата не знайдено.',
            'candidate_already_accepted'      => 'Кандидат уже прийнятий до ФСТУ та не може бути відхилений у модулі заявок.',
            'candidate_role_already_accepted' => 'Кандидат уже має роль члена ФСТУ. Перевірте його стан вручну.',
            'candidate_card_already_exists'   => 'Для кандидата вже існує членський квиток. Операцію відхилення заблоковано.',
            'reject_role_update_failed'       => 'Не вдалося завершити відхилення заявки. Спробуйте ще раз.',
            'applications_log_insert_failed'  => 'Сталася помилка збереження протоколу. Операцію скасовано.',
            default                           => 'Сталася помилка при відхиленні заявки.',
        };
    }

    /**
     * Повертає повідомлення для change_ofst-flow.
     */
    private function get_change_ofst_error_message( string $marker ): string {
        return match ( $marker ) {
            'candidate_not_found'             => 'Кандидата не знайдено.',
            'candidate_already_accepted'      => 'Кандидат уже прийнятий до ФСТУ. Зміну ОФСТ виконуйте у відповідному реєстрі.',
            'candidate_role_already_accepted' => 'Кандидат уже має роль члена ФСТУ. Перевірте його стан вручну.',
            'candidate_card_already_exists'   => 'Для кандидата вже існує членський квиток. Зміну ОФСТ у модулі заявок заблоковано.',
            'unit_not_found'                  => 'Обраний осередок не знайдено.',
            'unit_region_not_found'           => 'Для обраного осередку не визначено область.',
            'current_ofst_not_found'          => 'Не вдалося визначити поточний запис ОФСТ кандидата.',
            'current_ofst_record_invalid'     => 'Поточний запис ОФСТ має некоректний стан.',
            'ofst_history_insert_failed'      => 'Не вдалося зберегти історію змін ОФСТ. Операцію скасовано.',
            'ofst_history_duplicate'          => 'Не вдалося зберегти історію змін ОФСТ: виявлено конфлікт дати/часу історичного запису. Спробуйте повторити дію ще раз.',
            'ofst_history_required_field_missing' => 'Не вдалося зберегти історію змін ОФСТ: у таблиці відсутні або не заповнені обов’язкові службові поля.',
            'ofst_history_constraint_failed'  => 'Не вдалося зберегти історію змін ОФСТ через обмеження цілісності даних (осередок/область/користувач).',
            'ofst_history_invalid_datetime'   => 'Не вдалося зберегти історію змін ОФСТ через некоректну дату службового запису.',
            'ofst_update_failed'              => 'Не вдалося оновити поточний запис ОФСТ через службову помилку збереження.',
            'ofst_state_conflict'             => 'Запис ОФСТ було змінено паралельно. Оновіть список і повторіть спробу.',
            'applications_log_insert_failed'  => 'Сталася помилка збереження протоколу. Операцію скасовано.',
            default                           => 'Помилка оновлення ОФСТ.',
        };
    }

    /**
     * Отримання списку заявок для таблиці.
     */
    public function handle_get_list(): void {
        check_ajax_referer( Applications_List::NONCE_ACTION, 'nonce' );

        if ( ! $this->current_user_can_manage_applications() ) {
            wp_send_json_error( [ 'message' => 'Недостатньо прав для перегляду заявок.' ] );
        }

        $search    = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
        $page      = max( 1, absint( $_POST['page'] ?? 1 ) );
        $per_page  = max( 1, min( self::MAX_PER_PAGE, absint( $_POST['per_page'] ?? 10 ) ?: 10 ) );
        $region_id = absint( $_POST['region_id'] ?? 0 );
        $unit_id   = absint( $_POST['unit_id'] ?? 0 );
        $offset    = ( $page - 1 ) * $per_page;
        $started_at = microtime( true );

        $result = $this->get_repository()->get_applications_list( $search, $region_id, $unit_id, $per_page, $offset );
        $rows   = is_array( $result['rows'] ?? null ) ? $result['rows'] : [];
        $total  = (int) ( $result['total'] ?? 0 );

        $response = [
            'html'        => $this->render_list_rows( $rows, $offset ),
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => (int) ceil( $total / max( 1, $per_page ) ),
        ];

        if ( $this->is_debug_enabled() ) {
            $response['debug'] = [
                'execution_ms' => round( ( microtime( true ) - $started_at ) * 1000, 2 ),
                'rows_count'    => count( $rows ),
                'filters'       => [
                    'search'    => $search,
                    'region_id' => $region_id,
                    'unit_id'   => $unit_id,
                    'page'      => $page,
                    'per_page'  => $per_page,
                ],
            ];
        }

        wp_send_json_success( $response );
    }

    /**
     * Формує HTML рядків таблиці заявок.
     */
    private function render_list_rows( array $rows, int $offset ): string {
        if ( empty( $rows ) ) {
            return '<tr><td colspan="8" class="fstu-applications-empty">Заявок не знайдено.</td></tr>';
        }

        $html              = '';
        $can_manage_actions = $this->current_user_can_manage_applications();

        foreach ( $rows as $index => $row ) {
            $user_id      = (int) ( $row['user_id'] ?? 0 );
            $fio          = trim( (string) ( $row['fio'] ?? '' ) );
            $display_name = trim( (string) ( $row['display_name'] ?? '' ) );
            $user_login   = trim( (string) ( $row['user_login'] ?? '' ) );
            $fio          = '' !== $fio ? $fio : $display_name;
            $fio          = '' !== $fio ? $fio : $user_login;
            $fio          = '' !== $fio ? $fio : 'Без ПІБ';
            $email        = (string) ( $row['user_email'] ?? '' );
            $unit_id      = (int) ( $row['unit_id'] ?? 0 );
            $unit_name    = (string) ( $row['unit_short_name'] ?? '' );
            $region_name  = (string) ( $row['region_name'] ?? '' );
            $date_raw     = (string) ( $row['user_registered'] ?? '' );
            $date         = '' !== $date_raw ? wp_date( 'd.m.Y H:i', strtotime( $date_raw ) ) : '—';
            $row_number   = $offset + $index + 1;
            $actions      = $this->render_actions_dropdown( $user_id, $unit_id, $fio, $unit_name, $can_manage_actions );

            $html .= sprintf(
                '<tr class="fstu-row"><td class="fstu-td fstu-td--num">%1$d</td><td class="fstu-td"><a href="#" class="fstu-action-view fstu-app-card__link" data-id="%8$d" title="Переглянути заявку">%2$s</a></td><td class="fstu-td"><a href="/personal/?ViewID=%8$d" class="fstu-app-card__link" target="_blank" title="Перейти в кабінет">%3$s</a></td><td class="fstu-td">%4$s</td><td class="fstu-td">%5$s</td><td class="fstu-td">%6$s</td><td class="fstu-td fstu-th--center">—</td><td class="fstu-td fstu-td--actions">%7$s</td></tr>',
                $row_number,
                esc_html( $date ),
                esc_html( $fio ),
                esc_html( $email ?: '—' ),
                esc_html( $unit_name ?: '—' ),
                esc_html( $region_name ?: '—' ),
                $actions,
                $user_id
            );
        }

        return $html;
    }

    /**
     * Формує dropdown дій для рядка заявки.
     */
    private function render_actions_dropdown( int $user_id, int $unit_id, string $candidate_name, string $unit_name, bool $can_manage_actions ): string {
        if ( $user_id <= 0 ) {
            return '—';
        }

        $candidate_name_attr = esc_attr( $candidate_name );
        $unit_name_attr      = esc_attr( $unit_name );

        $items = [
            sprintf(
                '<button type="button" class="fstu-applications-dropdown__item fstu-action-view" data-id="%1$d" data-candidate-name="%3$s"><span class="fstu-dropdown-icon">👁️</span>%2$s</button>',
                $user_id,
                esc_html__( 'Перегляд', 'fstu' ),
                $candidate_name_attr
            ),
            sprintf(
                '<button type="button" class="fstu-applications-dropdown__item fstu-action-change-ofst" data-id="%1$d" data-unit-id="%2$d" data-candidate-name="%4$s" data-unit-name="%5$s"><span class="fstu-dropdown-icon">🔄</span>%3$s</button>',
                $user_id,
                $unit_id,
                esc_html__( 'Змінити ОФСТ', 'fstu' ),
                $candidate_name_attr,
                $unit_name_attr
            ),
            sprintf(
                '<button type="button" class="fstu-applications-dropdown__item fstu-action-accept" data-id="%1$d" data-candidate-name="%3$s"><span class="fstu-dropdown-icon">✅</span>%2$s</button>',
                $user_id,
                esc_html__( 'Прийняти', 'fstu' ),
                $candidate_name_attr
            ),
        ];

        if ( $can_manage_actions ) {
            $items[] = sprintf(
                '<button type="button" class="fstu-applications-dropdown__item fstu-applications-dropdown__item--danger fstu-action-reject" data-id="%1$d" data-candidate-name="%3$s"><span class="fstu-dropdown-icon">❌</span>%2$s</button>',
                $user_id,
                esc_html__( 'Відхилити', 'fstu' ),
                $candidate_name_attr
            );
        }

        return '<div class="fstu-applications-dropdown"><button type="button" class="fstu-applications-dropdown__toggle" aria-expanded="false" title="' . esc_attr__( 'Меню дій', 'fstu' ) . '">▼</button><div class="fstu-applications-dropdown__menu">' . implode( '', $items ) . '</div></div>';
    }

    /**
     * Тимчасовий заглушковий обробник голосування.
     */
    public function handle_vote(): void {
        check_ajax_referer( Applications_List::NONCE_ACTION, 'nonce' );

        if ( ! $this->current_user_can_manage_applications() ) {
            wp_send_json_error( $this->build_error_payload( 'Недостатньо прав.', 'forbidden' ) );
        }

        wp_send_json_error( [ 'message' => 'Функціонал голосування ще не реалізовано.' ] );
    }

    /**
     * Тимчасовий заглушковий обробник відправлення повідомлень.
     */
    public function handle_send_message(): void {
        check_ajax_referer( Applications_List::NONCE_ACTION, 'nonce' );

        if ( ! $this->current_user_can_manage_applications() ) {
            wp_send_json_error( [ 'message' => 'Недостатньо прав.' ] );
        }

        wp_send_json_error( [ 'message' => 'Функціонал відправлення повідомлень ще не реалізовано.' ] );
    }

    /**
     * Отримання даних одного кандидата для модального вікна
     */
    public function handle_get_single(): void {
        check_ajax_referer( Applications_List::NONCE_ACTION, 'nonce' );

        if ( ! $this->current_user_can_manage_applications() ) {
            wp_send_json_error( [ 'message' => 'Недостатньо прав.' ] );
        }

        $user_id = absint( wp_unslash( $_POST['id'] ?? 0 ) );
        $started_at = microtime( true );

        $result = $this->get_repository()->get_candidate_by_id( $user_id, $this->is_debug_enabled() );
        $data   = is_array( $result['data'] ?? null ) ? $result['data'] : null;

        if ( ! is_array( $data ) ) {
            wp_send_json_error( [ 'message' => 'Кандидата не знайдено.' ] );
        }

        $response = $data;

        if ( $this->is_debug_enabled() ) {
            $response['debug'] = array_merge(
                is_array( $result['debug'] ?? null ) ? $result['debug'] : [],
                [
                    'ajax_execution_ms' => round( ( microtime( true ) - $started_at ) * 1000, 2 ),
                ]
            );
        }

        wp_send_json_success( $response );
    }

    /**
     * Фінальне прийняття в члени ФСТУ (Транзакція)
     */
    public function handle_accept(): void {
        check_ajax_referer( Applications_List::NONCE_ACTION, 'nonce' );

        if ( ! $this->current_user_can_manage_applications() ) {
            wp_send_json_error( [ 'message' => 'Недостатньо прав.' ] );
        }

        try {
            $user_id = absint( wp_unslash( $_POST['id'] ?? 0 ) );

            if ( $user_id <= 0 ) {
                wp_send_json_error( $this->build_error_payload( 'Не вдалося визначити кандидата для прийняття.', 'invalid_candidate_id' ) );
            }

            $result  = $this->get_service()->accept_candidate( $user_id );

            $message = ! empty( $result['email_sent'] )
                ? 'Користувача успішно прийнято!'
                : 'Користувача успішно прийнято. Лист не було відправлено.';

            wp_send_json_success( [ 'message' => $message, 'code' => 'accepted' ] );
        } catch ( \Throwable $throwable ) {
            $marker = trim( $throwable->getMessage() );
            wp_send_json_error( $this->build_error_payload( $this->get_accept_error_message( $marker ), '' !== $marker ? $marker : 'accept_failed' ) );
        }
    }

    /**
     * [Покращення 3] Відхилення заявки (Soft Delete)
     */
    public function handle_reject(): void {
        check_ajax_referer( Applications_List::NONCE_ACTION, 'nonce' );
        if ( ! $this->current_user_can_manage_applications() ) {
            wp_send_json_error( $this->build_error_payload( 'Недостатньо прав для відхилення заявки.', 'forbidden' ) );
        }

        if ( $this->is_honeypot_triggered() ) {
            wp_send_json_error( $this->build_error_payload( 'Запит відхилено з міркувань безпеки.', 'spam_detected' ) );
        }

        try {
            $user_id = absint( wp_unslash( $_POST['id'] ?? 0 ) );

            if ( $user_id <= 0 ) {
                wp_send_json_error( $this->build_error_payload( 'Не вдалося визначити кандидата для відхилення.', 'invalid_candidate_id' ) );
            }

            $result = $this->get_service()->reject_candidate( $user_id );
            $status = (string) ( $result['status'] ?? '' );

            if ( 'already_rejected' === $status ) {
                wp_send_json_success( [
                    'message' => 'Заявка вже була відхилена раніше.',
                    'code'    => 'candidate_already_rejected',
                ] );
            }

            wp_send_json_success( [
                'message' => 'Заявку відхилено.',
                'code'    => 'rejected',
            ] );
        } catch ( \Throwable $throwable ) {
            $marker = trim( $throwable->getMessage() );

            wp_send_json_error( $this->build_error_payload( $this->get_reject_error_message( $marker ), '' !== $marker ? $marker : 'reject_failed' ) );
        }
    }

    /**
     * Зміна ОФСТ (Підрозділу)
     */
    public function handle_change_ofst(): void {
        check_ajax_referer( Applications_List::NONCE_ACTION, 'nonce' );

        if ( ! $this->current_user_can_manage_applications() ) {
            wp_send_json_error( $this->build_error_payload( 'Недостатньо прав.', 'forbidden' ) );
        }

        if ( $this->is_honeypot_triggered() ) {
            wp_send_json_error( $this->build_error_payload( 'Запит відхилено з міркувань безпеки.', 'spam_detected' ) );
        }

        try {
            $user_id = absint( wp_unslash( $_POST['user_id'] ?? 0 ) );
            $unit_id = absint( wp_unslash( $_POST['unit_id'] ?? 0 ) );

            if ( $user_id <= 0 || $unit_id <= 0 ) {
                wp_send_json_error( $this->build_error_payload( 'Невірні параметри оновлення.', 'invalid_payload' ) );
            }

            $result = $this->get_service()->change_candidate_ofst( $user_id, $unit_id );

            if ( 'no_changes' === (string) ( $result['status'] ?? '' ) ) {
                wp_send_json_success( [ 'message' => 'Змін не виявлено.', 'code' => 'ofst_no_changes' ] );
            }

            wp_send_json_success( [
                'message'   => 'Осередок оновлено, а історію змін збережено.',
                'code'      => 'updated',
                'region_id' => (int) ( $result['region_id'] ?? 0 ),
                'unit_id'   => (int) ( $result['unit_id'] ?? 0 ),
                'unit_name' => (string) ( $result['unit_name'] ?? '' ),
            ] );
        } catch ( \Throwable $throwable ) {
            $marker = trim( $throwable->getMessage() );

            wp_send_json_error( $this->build_error_payload( $this->get_change_ofst_error_message( $marker ), '' !== $marker ? $marker : 'change_ofst_failed' ) );
        }
    }

    /**
     * Отримання журналу операцій (Протоколу)
     */
    public function handle_get_protocol(): void {
        check_ajax_referer( Applications_List::NONCE_ACTION, 'nonce' );

        // Згідно з правилами, тільки адміністратори мають доступ до перегляду всіх логів
        if ( ! $this->current_user_is_administrator() ) {
            wp_send_json_error( [ 'message' => 'Немає прав для перегляду протоколу.' ] );
        }

        $search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
        $page     = max( 1, absint( $_POST['page'] ?? 1 ) );
        $per_page = max( 1, min( self::MAX_PER_PAGE, absint( $_POST['per_page'] ?? 10 ) ?: 10 ) );
        $offset   = ( $page - 1 ) * $per_page;
        $started_at = microtime( true );
        $protocol = $this->get_repository()->get_protocol( $search, $per_page, $offset, self::LOG_NAME );
        $rows     = is_array( $protocol['rows'] ?? null ) ? $protocol['rows'] : [];
        $total    = (int) ( $protocol['total'] ?? 0 );
        $html     = $this->get_protocol_service()->build_protocol_rows( $rows );

        $response = [
            'html'        => $html,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => (int) ceil( $total / max( 1, $per_page ) ),
        ];

        if ( $this->is_debug_enabled() ) {
            $response['debug'] = [
                'execution_ms' => round( ( microtime( true ) - $started_at ) * 1000, 2 ),
                'rows_count'    => count( $rows ),
                'filters'       => [
                    'search'   => $search,
                    'page'     => $page,
                    'per_page' => $per_page,
                ],
            ];
        }

        wp_send_json_success( $response );
    }
    //
}