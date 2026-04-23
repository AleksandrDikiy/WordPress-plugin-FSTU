<?php
/**
 * AJAX-контролер модуля Електронних виборів STV.
 * * Version: 1.0.0
 * Date_update: 2026-04-22
 */

namespace FSTU\Modules\Elections;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Elections_Ajax {

    private const LOG_NAME = 'Elections';
    private Elections_Service $service;

    public function __construct() {
        $this->service = new Elections_Service();
    }

    public function init(): void {
        // Захист від витоку структури БД: примусово вимикаємо HTML-помилки $wpdb для AJAX
        if ( wp_doing_ajax() ) {
            global $wpdb;
            $wpdb->hide_errors();
        }

        add_action( 'wp_ajax_fstu_get_elections', [ $this, 'handle_get_elections' ] );
        add_action( 'wp_ajax_fstu_save_election', [ $this, 'handle_save_election' ] );
        add_action( 'wp_ajax_fstu_search_users_for_nomination', [ $this, 'handle_search_users' ] );
        add_action( 'wp_ajax_fstu_nominate_candidate', [ $this, 'handle_nominate_candidate' ] );
        add_action( 'wp_ajax_fstu_get_voting_booth', [ $this, 'handle_get_voting_booth' ] );
        add_action( 'wp_ajax_fstu_submit_ballot', [ $this, 'handle_submit_ballot' ] );
        add_action( 'wp_ajax_fstu_calculate_election', [ $this, 'handle_calculate_election' ] );
        add_action( 'wp_ajax_fstu_get_election_report', [ $this, 'handle_get_election_report' ] );
        add_action( 'wp_ajax_fstu_get_elections_protocol', [ $this, 'handle_get_protocol' ] );
        add_action( 'wp_ajax_fstu_change_election_phase', [ $this, 'handle_change_phase' ] );
        add_action( 'wp_ajax_fstu_get_election_candidates', [ $this, 'handle_get_candidates' ] );
        // Для скачування CVR використовуємо admin-post (щоб віддати файл напряму)
        add_action( 'admin_post_fstu_download_cvr', [ $this, 'handle_download_cvr' ] );
    }

    /**
     * Хелпер для логування адміністративних дій (вимоги стандарту ФСТУ).
     */
    private function log_action( string $type, string $text, string $status ): void {
        global $wpdb;
        $wpdb->insert(
            'Logs',
            [
                'User_ID'         => get_current_user_id(),
                'Logs_DateCreate' => current_time( 'mysql' ),
                'Logs_Type'       => $type, // Тільки 1 символ ('I', 'U', 'D')
                'Logs_Name'       => self::LOG_NAME,
                'Logs_Text'       => $text,
                'Logs_Error'      => $status,
            ],
            [ '%d', '%s', '%s', '%s', '%s', '%s' ]
        );
    }

    /**
     * Отримання списку виборів для таблиці.
     */
    public function handle_get_elections(): void {
        check_ajax_referer( 'fstu_elections_nonce', 'nonce' );

        $permissions = Capabilities::get_elections_permissions();
        if ( ! $permissions['canView'] ) {
            wp_send_json_error( [ 'message' => __( 'Недостатньо прав.', 'fstu' ) ] );
        }

        global $wpdb;
        $current_user_id = get_current_user_id();
        $elections = $this->service->get_repository()->get_all_elections();

        // Отримуємо всі види туризму, до яких належить користувач
        $user_tourism_types = $wpdb->get_col( $wpdb->prepare(
            "SELECT TourismType_ID FROM UserTourismType WHERE User_ID = %d",
            $current_user_id
        ) );

        foreach ( $elections as &$election ) {
            // Користувач вважається членом, якщо вибори глобальні (null) або він зареєстрований у цьому виді
            $election['is_member'] = empty( $election['TourismType_ID'] ) || in_array( (int) $election['TourismType_ID'], $user_tourism_types );
        }

        wp_send_json_success( [
            'items'       => $elections,
            'permissions' => $permissions,
        ] );
    }

    /**
     * Збереження або створення нових виборів.
     */
    public function handle_save_election(): void {
        check_ajax_referer( 'fstu_elections_nonce', 'nonce' );

        if ( ! Capabilities::get_elections_permissions()['canManage'] ) {
            wp_send_json_error( [ 'message' => __( 'Недостатньо прав для керування виборами.', 'fstu' ) ] );
        }

        // Honeypot перевірка
        if ( ! empty( $_POST['fstu_website'] ) ) {
            wp_send_json_error( [ 'message' => 'Spam detected.' ] );
        }

        global $wpdb;

        $election_id      = isset( $_POST['election_id'] ) ? absint( $_POST['election_id'] ) : 0;
        $election_name    = sanitize_text_field( wp_unslash( $_POST['election_name'] ?? '' ) );
        $tourism_type_id  = isset( $_POST['tourism_type_id'] ) && $_POST['tourism_type_id'] !== '' ? absint( $_POST['tourism_type_id'] ) : null;

        $candidates_count = isset( $_POST['candidates_count'] ) ? absint( $_POST['candidates_count'] ) : 7;
        $candidates_count = $this->service->validate_candidates_count( $candidates_count ); // Ліміт 3-15

        $nomination_days  = isset( $_POST['nomination_days'] ) ? absint( $_POST['nomination_days'] ) : 7;
        $extension_days   = isset( $_POST['extension_days'] ) ? absint( $_POST['extension_days'] ) : 5;
        $voting_days      = isset( $_POST['voting_days'] ) ? absint( $_POST['voting_days'] ) : 7;

        if ( empty( $election_name ) ) {
            wp_send_json_error( [ 'message' => __( 'Назва виборів є обов\'язковою.', 'fstu' ) ] );
        }

        $data = [
            'Election_Name'             => $election_name,
            'TourismType_ID'            => $tourism_type_id,
            'Settings_Candidates_Count' => $candidates_count,
            'Settings_Nomination_Days'  => $nomination_days,
            'Settings_Extension_Days'   => $extension_days,
            'Settings_Voting_Days'      => $voting_days,
        ];

        // Транзакційне збереження з логуванням
        $wpdb->query( 'START TRANSACTION' );

        try {
            if ( $election_id > 0 ) {
                $this->service->get_repository()->update( $election_id, $data );
                $this->log_action( 'U', "Оновлено налаштування виборів: {$election_name} (ID: {$election_id})", '✓' );
            } else {
                $data['Status']     = 'draft';
                $data['UserCreate'] = get_current_user_id();
                $data['DateCreate'] = current_time( 'mysql' );

                $election_id = $this->service->get_repository()->create( $data );
                if ( ! $election_id ) {
                    throw new \Exception( __( 'Помилка створення запису в БД.', 'fstu' ) );
                }
                $this->log_action( 'I', "Створено нові вибори: {$election_name} (Мандатів: {$candidates_count})", '✓' );
            }

            $wpdb->query( 'COMMIT' );
            wp_send_json_success( [ 'message' => __( 'Вибори успішно збережено.', 'fstu' ) ] );

        } catch ( \Exception $e ) {
            $wpdb->query( 'ROLLBACK' );
            $this->log_action( $election_id > 0 ? 'U' : 'I', "Помилка збереження виборів: {$election_name}", 'error' );
            wp_send_json_error( [ 'message' => $e->getMessage() ] );
        }
    }

    /**
     * ШВИДКИЙ AJAX Пошук користувачів (Прямий SQL замість VIEW).
     */
    public function handle_search_users(): void {
        check_ajax_referer( 'fstu_elections_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) wp_send_json_error();

        global $wpdb;
        $search      = sanitize_text_field( wp_unslash( $_POST['q'] ?? '' ) );
        $election_id = absint( $_POST['election_id'] ?? 0 );

        if ( mb_strlen( $search ) < 3 ) wp_send_json_success( ['results' => []] );

        // Дізнаємось вид туризму виборів
        $tourism_type_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT TourismType_ID FROM Elections WHERE Election_ID = %d",
            $election_id
        ) );

        $like = '%' . $wpdb->esc_like( $search ) . '%';

        // Формуємо SQL з урахуванням виду туризму (якщо він заданий)
        $join_sql  = "";
        $where_sql = "";
        if ( $tourism_type_id ) {
            $join_sql  = " INNER JOIN UserTourismType utt ON utt.User_ID = u.ID ";
            $where_sql = $wpdb->prepare( " AND utt.TourismType_ID = %d ", $tourism_type_id );
        }

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT DISTINCT u.ID as id, 
                    CONCAT(m1.meta_value, ' ', m2.meta_value, ' ', IFNULL(m3.meta_value, '')) as text,
                    r.Region_Name
             FROM {$wpdb->users} u
             INNER JOIN {$wpdb->usermeta} cap ON cap.user_id = u.ID AND cap.meta_key = 'wp_capabilities' AND cap.meta_value LIKE '%%userfstu%%'
             INNER JOIN {$wpdb->usermeta} m1 ON m1.user_id = u.ID AND m1.meta_key = 'last_name'
             INNER JOIN {$wpdb->usermeta} m2 ON m2.user_id = u.ID AND m2.meta_key = 'first_name'
             LEFT JOIN {$wpdb->usermeta} m3 ON m3.user_id = u.ID AND m3.meta_key = 'Patronymic'
             {$join_sql}
             LEFT JOIN UserRegistationOFST ur ON ur.User_ID = u.ID
             LEFT JOIN S_Region r ON r.Region_ID = ur.Region_ID
             WHERE (m1.meta_value LIKE %s OR m2.meta_value LIKE %s) {$where_sql}
             LIMIT 20",
            $like, $like
        ), ARRAY_A );

        $like = '%' . $wpdb->esc_like( $search ) . '%';

        // Прямий запит по таблицях WordPress та ФСТУ
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT u.ID as id, 
                    CONCAT(m1.meta_value, ' ', m2.meta_value, ' ', IFNULL(m3.meta_value, '')) as text,
                    r.Region_Name
             FROM {$wpdb->users} u
             INNER JOIN {$wpdb->usermeta} cap ON cap.user_id = u.ID AND cap.meta_key = 'wp_capabilities' AND cap.meta_value LIKE '%%userfstu%%'
             INNER JOIN {$wpdb->usermeta} m1 ON m1.user_id = u.ID AND m1.meta_key = 'last_name'
             INNER JOIN {$wpdb->usermeta} m2 ON m2.user_id = u.ID AND m2.meta_key = 'first_name'
             LEFT JOIN {$wpdb->usermeta} m3 ON m3.user_id = u.ID AND m3.meta_key = 'Patronymic'
             LEFT JOIN UserRegistationOFST ur ON ur.User_ID = u.ID
             LEFT JOIN S_Region r ON r.Region_ID = ur.Region_ID
             WHERE (m1.meta_value LIKE %s OR m2.meta_value LIKE %s)
             LIMIT 20",
            $like, $like
        ), ARRAY_A );

        $formatted = [];
        if ( ! empty( $results ) ) {
            foreach ( $results as $row ) {
                $region = ! empty( $row['Region_Name'] ) ? $row['Region_Name'] : 'Регіон не вказано';
                $formatted[] = [
                    'id'   => $row['id'],
                    'text' => trim( $row['text'] ) . ' (' . $region . ')'
                ];
            }
        }

        wp_send_json_success( [ 'results' => $formatted ] );
    }

    /**
     * Збереження номінації (Самовисування або висунення іншого).
     */
    public function handle_nominate_candidate(): void {
        check_ajax_referer( 'fstu_elections_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => __( 'Необхідна авторизація.', 'fstu' ) ] );

        if ( ! empty( $_POST['fstu_website'] ) ) wp_send_json_error( [ 'message' => 'Spam detected.' ] );

        global $wpdb;
        $current_user_id = get_current_user_id();
        $election_id     = absint( $_POST['election_id'] ?? 0 );
        $nomination_type = sanitize_text_field( $_POST['nomination_type'] ?? 'self' );
        $candidate_id    = $nomination_type === 'self' ? $current_user_id : absint( $_POST['candidate_user_id'] ?? 0 );
        $motivation_txt  = sanitize_textarea_field( $_POST['motivation_text'] ?? '' );
        $motivation_url  = esc_url_raw( $_POST['motivation_url'] ?? '' );

        if ( ! $election_id || ! $candidate_id ) {
            wp_send_json_error( [ 'message' => __( 'Некоректні дані.', 'fstu' ) ] );
        }

        // Перевірка чи вибори у статусі nomination
        $election = $this->service->get_repository()->get_by_id( $election_id );
        if ( ! $election || $election['Status'] !== 'nomination' ) {
            wp_send_json_error( [ 'message' => __( 'Етап висунення закрито.', 'fstu' ) ] );
        }

        // КРИТИЧНО: Перевірка приналежності кандидата до виду туризму
        if ( ! empty( $election['TourismType_ID'] ) ) {
            $is_member = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM UserTourismType WHERE User_ID = %d AND TourismType_ID = %d",
                $candidate_id, $election['TourismType_ID']
            ) );
            if ( ! $is_member ) {
                wp_send_json_error( [ 'message' => __( 'Помилка: Кандидат не належить до виду туризму цих виборів.', 'fstu' ) ] );
            }
        }

        // Перевірка чи кандидат вже номінований
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT Candidate_ID FROM Election_Candidates WHERE Election_ID = %d AND User_ID = %d",
            $election_id, $candidate_id
        ) );

        if ( $exists ) {
            wp_send_json_error( [ 'message' => __( 'Цей кандидат вже висунутий на ці вибори.', 'fstu' ) ] );
        }

        $status    = $nomination_type === 'self' ? 'confirmed' : 'pending';
        $nominator = $nomination_type === 'self' ? null : $current_user_id;

        $wpdb->query( 'START TRANSACTION' );
        try {
            $wpdb->insert( 'Election_Candidates', [
                'Election_ID'     => $election_id,
                'User_ID'         => $candidate_id,
                'Nominator_ID'    => $nominator,
                'Motivation_Text' => $motivation_txt,
                'Motivation_URL'  => $motivation_url,
                'Status'          => $status
            ], [ '%d', '%d', '%d', '%s', '%s', '%s' ] );

            $this->log_action( 'I', "Висунення кандидата (User_ID: {$candidate_id}) на вибори ID {$election_id}", '✓' );
            $wpdb->query( 'COMMIT' );
            wp_send_json_success( [ 'message' => __( 'Кандидатуру успішно висунуто.', 'fstu' ) ] );

        } catch ( \Exception $e ) {
            $wpdb->query( 'ROLLBACK' );
            $this->log_action( 'I', "Помилка висунення на вибори ID {$election_id}", 'error' );
            wp_send_json_error( [ 'message' => __( 'Помилка бази даних.', 'fstu' ) ] );
        }
    }

    /**
     * Отримання даних для інтерфейсу виборця (кабінка для голосування).
     */
    public function handle_get_voting_booth(): void {
        check_ajax_referer( 'fstu_elections_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) wp_send_json_error();

        global $wpdb;
        $current_user_id = get_current_user_id();
        $election_id     = absint( $_POST['election_id'] ?? 0 );

        // 1. Перевірка чи виборець вже голосував
        $has_voted = (bool) $wpdb->get_var( $wpdb->prepare(
            "SELECT Has_Voted FROM Election_Voters WHERE Election_ID = %d AND User_ID = %d",
            $election_id, $current_user_id
        ) );

        if ( $has_voted ) {
            wp_send_json_success( [ 'already_voted' => true ] );
        }

        // 2. Перевірка фінансової заборгованості (боржники не голосують)
        if ( $this->service->has_dues_debt( $current_user_id, $election_id ) ) {
            wp_send_json_success( [ 'has_debt' => true ] );
        }

        // 3. Завантаження списку підтверджених кандидатів (Без SQL-групування)
        $candidates_raw = $wpdb->get_results( $wpdb->prepare(
            "SELECT c.Candidate_ID, c.User_ID, c.Motivation_URL, u.FIO, u.Region_Name 
             FROM Election_Candidates c
             JOIN vUserFSTUnew u ON u.User_ID = c.User_ID
             WHERE c.Election_ID = %d AND c.Status = 'confirmed'
             ORDER BY RAND()",
            $election_id
        ), ARRAY_A );

        // Дедуплікація засобами PHP (надійно на 100%) та перевірка боргів
        $candidates = [];
        $seen = [];
        if ( is_array( $candidates_raw ) ) {
            foreach ( $candidates_raw as $row ) {
                if ( ! isset( $seen[ $row['Candidate_ID'] ] ) ) {
                    $seen[ $row['Candidate_ID'] ] = true;
                    // Перевіряємо борги кандидатів тут
                    $row['has_debt'] = $this->service->has_dues_debt( (int) $row['User_ID'], $election_id );
                    $candidates[] = $row;
                }
            }
        }

        wp_send_json_success( [
            'already_voted' => false,
            'has_debt'      => false,
            'candidates'    => $candidates
        ] );
    }

    /**
     * Збереження анонімного бюлетеня (STV Ballot).
     */
    public function handle_submit_ballot(): void {
        check_ajax_referer( 'fstu_elections_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) wp_send_json_error( [ 'message' => __( 'Авторизуйтесь.', 'fstu' ) ] );
        if ( ! empty( $_POST['fstu_website'] ) ) wp_send_json_error( [ 'message' => 'Spam detected.' ] );

        global $wpdb;
        $current_user_id = get_current_user_id();
        $election_id     = absint( $_POST['election_id'] ?? 0 );
        $ballot_data     = isset( $_POST['ballot'] ) ? array_map( 'absint', (array) $_POST['ballot'] ) : [];

        // Валідація: Мінімум 7 кандидатів (або скільки дозволяє система, якщо кандидатів всього мало)
        $election = $this->service->get_repository()->get_by_id( $election_id );
        if ( ! $election || $election['Status'] !== 'voting' ) {
            wp_send_json_error( [ 'message' => __( 'Голосування закрите або не існує.', 'fstu' ) ] );
        }

        $total_confirmed = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM Election_Candidates WHERE Election_ID = %d AND Status = 'confirmed'", $election_id ) );
        $min_required = min( 7, $total_confirmed ); // Якщо підтвердилось всього 5, вимагаємо 5

        if ( count( $ballot_data ) < $min_required ) {
            wp_send_json_error( [ 'message' => sprintf( __( 'Оберіть мінімум %d кандидатів у свій бюлетень.', 'fstu' ), $min_required ) ] );
        }

        // Подвійна перевірка боргів та факту голосування
        if ( $this->service->has_dues_debt( $current_user_id, $election_id ) ) {
            wp_send_json_error( [ 'message' => __( 'Ви маєте заборгованість і не можете голосувати.', 'fstu' ) ] );
        }

        $voter_record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM Election_Voters WHERE Election_ID = %d AND User_ID = %d FOR UPDATE", $election_id, $current_user_id ) );
        if ( $voter_record && $voter_record->Has_Voted ) {
            wp_send_json_error( [ 'message' => __( 'Ви вже проголосували на цих виборах.', 'fstu' ) ] );
        }

        // Генерація анонімного криптографічного Хешу
        $random_salt = wp_generate_password( 64, true, true ) . microtime();
        $ballot_hash = hash( 'sha256', $election_id . $current_user_id . $random_salt );

        $wpdb->query( 'START TRANSACTION' );
        try {
            // 1. Зберігаємо зашифрований анонімний бюлетень
            $wpdb->insert( 'Election_Ballots', [
                'Election_ID'      => $election_id,
                'Ballot_Hash'      => $ballot_hash,
                'Preferences_JSON' => wp_json_encode( $ballot_data ),
                'DateCreate'       => current_time( 'mysql' )
            ], [ '%d', '%s', '%s', '%s' ] );

            // 2. Фіксуємо факт явки (без зв'язку з бюлетенем)
            if ( $voter_record ) {
                $wpdb->update( 'Election_Voters', [ 'Has_Voted' => 1, 'Date_Voted' => current_time( 'mysql' ) ], [ 'Voter_ID' => $voter_record->Voter_ID ] );
            } else {
                $wpdb->insert( 'Election_Voters', [
                    'Election_ID' => $election_id,
                    'User_ID'     => $current_user_id,
                    'Has_Voted'   => 1,
                    'Date_Voted'  => current_time( 'mysql' )
                ], [ '%d', '%d', '%d', '%s' ] );
            }

            $wpdb->query( 'COMMIT' );
            wp_send_json_success( [ 'message' => __( 'Голос успішно враховано.', 'fstu' ), 'hash' => $ballot_hash ] );

        } catch ( \Exception $e ) {
            $wpdb->query( 'ROLLBACK' );
            wp_send_json_error( [ 'message' => __( 'Сталася помилка при обробці бюлетеня.', 'fstu' ) ] );
        }
    }

    /**
     * Запуск підрахунку голосів (STV Engine) та збереження результатів.
     */
    public function handle_calculate_election(): void {
        check_ajax_referer( 'fstu_elections_nonce', 'nonce' );
        if ( ! Capabilities::get_elections_permissions()['canManage'] ) wp_send_json_error();

        global $wpdb;
        $election_id = absint( $_POST['election_id'] ?? 0 );
        $election = $this->service->get_repository()->get_by_id( $election_id );

        if ( ! $election ) wp_send_json_error( [ 'message' => 'Вибори не знайдено.' ] );

        // Запуск математичного ядра
        $engine = new Elections_STV_Engine( $election_id, (int) $election['Settings_Candidates_Count'] );
        $results = $engine->calculate();

        if ( isset( $results['error'] ) ) {
            wp_send_json_error( [ 'message' => $results['error'] ] );
        }

        // Зберігаємо результати у wp_options як JSON (щоб не створювати ще одну таблицю)
        update_option( 'fstu_election_results_' . $election_id, wp_json_encode( $results ), false );

        // Змінюємо статус
        $wpdb->update( 'Elections', [ 'Status' => 'completed' ], [ 'Election_ID' => $election_id ] );

        $this->log_action( 'U', "Вибори ID {$election_id} підраховано та завершено.", '✓' );

        wp_send_json_success( [ 'message' => 'Підрахунок успішно завершено.' ] );
    }

    /**
     * Отримання аналітичного звіту для UI.
     */
    public function handle_get_election_report(): void {
        check_ajax_referer( 'fstu_elections_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) wp_send_json_error();

        $election_id = absint( $_POST['election_id'] ?? 0 );
        $results_json = get_option( 'fstu_election_results_' . $election_id );

        if ( ! $results_json ) {
            wp_send_json_error( [ 'message' => 'Звіт ще не сформовано.' ] );
        }

        wp_send_json_success( json_decode( $results_json, true ) );
    }

    /**
     * Генерація та скачування відкритого CSV-файлу (CVR).
     */
    public function handle_download_cvr(): void {
        if ( ! is_user_logged_in() ) wp_die( 'Необхідна авторизація' );

        $election_id = absint( $_GET['election_id'] ?? 0 );
        if ( ! $election_id ) wp_die( 'Некоректний ID' );

        global $wpdb;
        $ballots = $wpdb->get_results( $wpdb->prepare(
            "SELECT Ballot_Hash, Preferences_JSON FROM Election_Ballots WHERE Election_ID = %d",
            $election_id
        ), ARRAY_A );

        if ( empty( $ballots ) ) wp_die( 'Немає бюлетенів для цих виборів.' );

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=FSTU_CVR_Election_' . $election_id . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Ballot_Hash', 'Preference_1', 'Preference_2', 'Preference_3', 'Preference_4', 'Preference_5', 'Preference_6', 'Preference_7', '...']);

        foreach ( $ballots as $b ) {
            $prefs = json_decode( $b['Preferences_JSON'], true );
            $row = array_merge( [ $b['Ballot_Hash'] ], (array) $prefs );
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }

    /**
     * Отримання записів протоколу (Logs).
     */
    public function handle_get_protocol(): void {
        check_ajax_referer( 'fstu_elections_nonce', 'nonce' );
        if ( ! Capabilities::get_elections_permissions()['canProtocol'] ) {
            wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду протоколу.', 'fstu' ) ] );
        }

        global $wpdb;
        $search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
        $page     = absint( $_POST['page'] ?? 1 );
        $per_page = absint( $_POST['per_page'] ?? 10 );
        $offset   = ( $page - 1 ) * $per_page;

        $where = $wpdb->prepare( "l.Logs_Name = %s", self::LOG_NAME );
        if ( ! empty( $search ) ) {
            $like   = '%' . $wpdb->esc_like( $search ) . '%';
            $where .= $wpdb->prepare( " AND (l.Logs_Text LIKE %s OR u.FIO LIKE %s)", $like, $like );
        }

        // Використовуємо COUNT(DISTINCT) щоб уникнути дублювання через VIEW
        $total = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT l.Logs_ID) FROM Logs l LEFT JOIN vUserFSTUnew u ON u.User_ID = l.User_ID WHERE {$where}" );

        // Додаємо GROUP BY l.Logs_ID для захисту від JOIN-вибуху
        $items = $wpdb->get_results( $wpdb->prepare(
            "SELECT l.Logs_ID, l.Logs_DateCreate, l.Logs_Type, l.Logs_Name, l.Logs_Text, l.Logs_Error, MAX(u.FIO) as FIO
             FROM Logs l
             LEFT JOIN vUserFSTUnew u ON u.User_ID = l.User_ID
             WHERE {$where}
             GROUP BY l.Logs_ID
             ORDER BY l.Logs_DateCreate DESC
             LIMIT %d OFFSET %d",
            $per_page, $offset
        ), ARRAY_A );

        wp_send_json_success( [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total / $per_page ),
        ] );
    }

    /**
     * Ручне керування статусом виборів з авторозрахунком дат.
     */
    public function handle_change_phase(): void {
        check_ajax_referer( 'fstu_elections_nonce', 'nonce' );
        if ( ! Capabilities::get_elections_permissions()['canManage'] ) wp_send_json_error();

        global $wpdb;
        $election_id = absint( $_POST['election_id'] ?? 0 );
        $status      = sanitize_text_field( $_POST['status'] ?? '' );

        $valid_statuses = [ 'draft', 'nomination', 'voting', 'calculation', 'completed' ];
        if ( ! in_array( $status, $valid_statuses, true ) ) {
            wp_send_json_error( [ 'message' => 'Невірний статус.' ] );
        }

        $election = $this->service->get_repository()->get_by_id( $election_id );
        if ( ! $election ) {
            wp_send_json_error( [ 'message' => 'Вибори не знайдено.' ] );
        }

        $update_data  = [ 'Status' => $status ];
        $current_time = current_time( 'mysql' );

        // Автоматичне встановлення дат при старті відповідних фаз (дедлайн завжди о 23:59:59)
        if ( $status === 'nomination' && empty( $election['Date_Nomination_Start'] ) ) {
            $days = (int) $election['Settings_Nomination_Days'];
            $update_data['Date_Nomination_Start'] = $current_time;
            $update_data['Date_Nomination_End']   = date( 'Y-m-d 23:59:59', strtotime( $current_time . " + {$days} days" ) );
        } elseif ( $status === 'voting' && empty( $election['Date_Voting_Start'] ) ) {
            $days = (int) $election['Settings_Voting_Days'];
            $update_data['Date_Voting_Start'] = $current_time;
            $update_data['Date_Voting_End']   = date( 'Y-m-d 23:59:59', strtotime( $current_time . " + {$days} days" ) );
        }

        $wpdb->update( 'Elections', $update_data, [ 'Election_ID' => $election_id ] );
        $this->log_action( 'U', "Змінено статус виборів ID {$election_id} на '{$status}'", '✓' );

        wp_send_json_success( [ 'message' => 'Статус успішно змінено.' ] );
    }

    /**
     * Отримання списку висунутих кандидатів для UI (з мотивацією та URL).
     */
    public function handle_get_candidates(): void {
        check_ajax_referer( 'fstu_elections_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) wp_send_json_error();

        global $wpdb;
        $election_id = absint( $_POST['election_id'] ?? 0 );

        // Завантаження списку висунутих кандидатів (Без SQL-групування)
        $candidates_raw = $wpdb->get_results( $wpdb->prepare(
            "SELECT c.Candidate_ID, c.Status, c.Motivation_Text, c.Motivation_URL, 
                    u.FIO, u.Region_Name, n.FIO as Nominator_FIO
             FROM Election_Candidates c
             JOIN vUserFSTUnew u ON u.User_ID = c.User_ID
             LEFT JOIN vUserFSTUnew n ON n.User_ID = c.Nominator_ID
             WHERE c.Election_ID = %d
             ORDER BY c.Candidate_ID ASC",
            $election_id
        ), ARRAY_A );

        // Дедуплікація засобами PHP
        $candidates = [];
        $seen = [];
        if ( is_array( $candidates_raw ) ) {
            foreach ( $candidates_raw as $row ) {
                if ( ! isset( $seen[ $row['Candidate_ID'] ] ) ) {
                    $seen[ $row['Candidate_ID'] ] = true;
                    $candidates[] = $row;
                }
            }
        }

        wp_send_json_success( [ 'candidates' => $candidates ] );
    }
}