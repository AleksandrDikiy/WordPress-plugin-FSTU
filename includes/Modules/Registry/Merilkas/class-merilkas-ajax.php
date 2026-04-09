<?php
/**
 * AJAX-маршрутизатор модуля "Реєстр мерилок".
 *
 * Version:     1.0.1
 * Date_update: 2026-04-09
 *
 * @package FSTU\Modules\Registry\Merilkas
 */

namespace FSTU\Modules\Registry\Merilkas;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Merilkas_Ajax {

    private ?Merilkas_Service $service = null;
    private ?Merilkas_Repository $repository = null;

    public function init(): void {
        // Хуки для виводу
        add_action( 'wp_ajax_fstu_merilkas_get_list', [ $this, 'handle_get_list' ] );
        add_action( 'wp_ajax_fstu_merilkas_print', [ $this, 'handle_print_merilka' ] );
        add_action( 'wp_ajax_fstu_merilkas_get_single', [ $this, 'handle_get_single' ] );

        // Хук для лінивого завантаження вкладки
        // Хук для лінивого завантаження вкладки (Захищений від дублікатів)
        add_action( 'wp_ajax_fstu_load_merilkas_tab', [ $this, 'handle_get_list_by_sailboat' ] );

        // Хуки для CRUD (Фаза 4)
        add_action( 'wp_ajax_fstu_merilkas_save', [ $this, 'handle_save_merilka' ] );
        add_action( 'wp_ajax_fstu_merilkas_delete', [ $this, 'handle_delete_merilka' ] );
    }

    /**
     * Перевірка прав на управління мерилкою (Створення/Редагування/Видалення).
     */
    private function current_user_can_manage_merilka( int $sailboat_id ): bool {
        $user  = wp_get_current_user();
        $roles = is_array( $user->roles ) ? $user->roles : [];

        if ( current_user_can( 'manage_options' ) || in_array( 'administrator', $roles, true ) || in_array( 'sailadministrator', $roles, true ) ) {
            return true;
        }

        // Перевірка, чи користувач є власником судна
        $owner_id = $this->get_repository()->get_sailboat_owner_id( $sailboat_id );
        return $owner_id === $user->ID && $user->ID > 0;
    }

    private function get_repository(): Merilkas_Repository {
        if ( null === $this->repository ) {
            $this->repository = new Merilkas_Repository();
        }
        return $this->repository;
    }

    public function handle_get_list(): void {
        wp_send_json_success();
    }

    /**
     * Генерує HTML сторінку обмірного свідоцтва для друку.
     */
    public function handle_print_merilka(): void {
        if ( ! is_user_logged_in() ) {
            wp_die( 'Немає доступу. Будь ласка, авторизуйтесь.' );
        }

        $mr_id = absint( $_GET['mr_id'] ?? 0 );
        if ( $mr_id <= 0 ) {
            wp_die( 'Невірний ідентифікатор свідоцтва.' );
        }

        global $wpdb;

        $sql = "SELECT m.*, s.Sailboat_Name, s.Sailboat_NumberSail, s.RegNumber, s.Sailboat_Year, u.FIO 
				FROM Merilka m 
				JOIN vSailboat s ON s.Sailboat_ID = m.Sailboat_ID 
				LEFT JOIN vUserFSTU u ON u.User_ID = m.User_ID
				WHERE m.MR_ID = %d LIMIT 1";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
        $item = $wpdb->get_row( $wpdb->prepare( $sql, $mr_id ), ARRAY_A );

        if ( ! $item ) {
            wp_die( 'Свідоцтво не знайдено.' );
        }

        include FSTU_PLUGIN_DIR . 'views/merilkas/print-template.php';
        exit;
    }

    /**
     * Повертає дані однієї мерилки для завантаження у форму.
     */
    public function handle_get_single(): void {
        check_ajax_referer( 'fstu_merilkas_nonce', 'nonce' );

        $mr_id = absint( $_POST['mr_id'] ?? 0 );
        if ( $mr_id <= 0 ) {
            wp_send_json_error( [ 'message' => 'Невірний ідентифікатор свідоцтва.' ] );
        }

        global $wpdb;
        $sql = "SELECT * FROM Merilka WHERE MR_ID = %d LIMIT 1";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
        $item = $wpdb->get_row( $wpdb->prepare( $sql, $mr_id ), ARRAY_A );

        if ( ! $item ) {
            wp_send_json_error( [ 'message' => 'Свідоцтво не знайдено.' ] );
        }

        if ( ! $this->current_user_can_manage_merilka( (int) $item['Sailboat_ID'] ) ) {
            wp_send_json_error( [ 'message' => 'Немає прав для доступу до цього свідоцтва.' ] );
        }

        wp_send_json_success( [ 'item' => $item ] );
    }

    /**
     * Повертає готову HTML-таблицю історії мерилок для вкладки Суднового реєстру.
     */
    public function handle_get_list_by_sailboat(): void {
        // ТУТ НЕМАЄ check_ajax_referer(), який викликав помилку -1

        $nonce = $_POST['nonce'] ?? '';
        $valid_merilkas = wp_verify_nonce( $nonce, 'fstu_merilkas_nonce' );
        $valid_sailboats = wp_verify_nonce( $nonce, \FSTU\Modules\Registry\Sailboats\Sailboats_List::NONCE_ACTION );

        if ( ! $valid_merilkas && ! $valid_sailboats ) {
            wp_send_json_error( [ 'message' => 'Помилка безпеки (Nonce).' ] );
        }

        $sailboat_id = absint( $_POST['sailboat_id'] ?? 0 );
        if ( $sailboat_id <= 0 ) {
            wp_send_json_error( [ 'message' => 'Невірний ідентифікатор судна.' ] );
        }

        $permissions = \FSTU\Core\Capabilities::get_sailboats_permissions();
        if ( empty( $permissions['canView'] ) ) {
            wp_send_json_error( [ 'message' => 'Немає прав для перегляду.' ] );
        }

        $items      = $this->get_repository()->get_list_by_sailboat( $sailboat_id );
        $can_manage = $this->current_user_can_manage_merilka( $sailboat_id );

        ob_start();
        $template_path = FSTU_PLUGIN_DIR . 'views/merilkas/tab-list.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            echo '<div class="fstu-no-results">Шаблон таблиці не знайдено.</div>';
        }
        $html = ob_get_clean();

        wp_send_json_success( [ 'html' => $html ] );
    }

    /**
     * Зберігає (створює/оновлює) свідоцтво.
     */
    public function handle_save_merilka(): void {
        check_ajax_referer( 'fstu_merilkas_nonce', 'nonce' );

        $sailboat_id = absint( $_POST['sailboat_id'] ?? 0 );
        if ( ! $this->current_user_can_manage_merilka( $sailboat_id ) ) {
            wp_send_json_error( [ 'message' => 'Немає прав для управління мерилками цього судна.' ] );
        }

        try {
            if ( null === $this->service ) {
                $this->service = new Merilkas_Service( $this->get_repository(), new Merilkas_Protocol_Service() );
            }
            $result = $this->service->save_item( $_POST );
            wp_send_json_success( [ 'message' => 'Свідоцтво успішно збережено!', 'mr_id' => $result['mr_id'] ] );
        } catch ( \Throwable $e ) {
            wp_send_json_error( [ 'message' => $e->getMessage() ] );
        }
    }

    /**
     * Видаляє свідоцтво.
     */
    public function handle_delete_merilka(): void {
        check_ajax_referer( 'fstu_merilkas_nonce', 'nonce' );

        $mr_id = absint( $_POST['mr_id'] ?? 0 );
        $sailboat_id = absint( $_POST['sailboat_id'] ?? 0 );

        if ( ! $this->current_user_can_manage_merilka( $sailboat_id ) ) {
            wp_send_json_error( [ 'message' => 'Немає прав для видалення.' ] );
        }

        try {
            if ( null === $this->service ) {
                $this->service = new Merilkas_Service( $this->get_repository(), new Merilkas_Protocol_Service() );
            }
            $this->service->delete_item( $mr_id );
            wp_send_json_success( [ 'message' => 'Свідоцтво видалено.' ] );
        } catch ( \Throwable $e ) {
            $msg = $e->getMessage();
            if ( str_starts_with( $msg, 'delete_blocked:' ) ) {
                $msg = 'Видалення неможливе: це свідоцтво вже використовувалося у змаганнях.';
            }
            wp_send_json_error( [ 'message' => $msg ] );
        }
    }
}