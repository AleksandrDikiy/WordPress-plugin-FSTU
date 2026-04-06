<?php
/**
 * Реєстрація меню плагіна в адмін-панелі WordPress.
 *
 * Version:     1.0.1
 * Date_update: 2026-04-06
 *
 * @package FSTU\Admin
 */

namespace FSTU\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin_Menu {

    public function init(): void {
        add_action( 'admin_menu', [ $this, 'register_menus' ] );
    }

    public function register_menus(): void {
        // Головне меню "ФСТУ"
        add_menu_page(
            'ФСТУ - Керування системою', // Title сторінки
            'ФСТУ',                      // Назва в меню
            'manage_options',            // Права доступу (тільки адміни)
            'fstu-main',                 // Slug (URL)
            [ $this, 'render_main_page' ],// Метод виводу
            'dashicons-groups',          // Іконка (люди)
            30                           // Позиція в меню
        );

        // Підменю "Головна" (дублює перший пункт, це стандарт WP)
        add_submenu_page(
            'fstu-main',
            'Головна інформація',
            'Головна',
            'manage_options',
            'fstu-main',
            [ $this, 'render_main_page' ]
        );

        // Підменю "Налаштування" (для майбутньої таблиці Settings)
        add_submenu_page(
            'fstu-main',
            'Налаштування системи ФСТУ',
            'Налаштування',
            'manage_options',
            'fstu-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    public function render_main_page(): void {
        $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/fstu_new/fstu.php' ); // Вкажіть правильну папку вашого плагіна
        $version     = ! empty( $plugin_data['Version'] ) ? $plugin_data['Version'] : '1.4.0';

        include dirname( __DIR__, 2 ) . '/views/admin/main-page.php';
    }

    /**
     * Вивід та обробка сторінки "Налаштування".
     */
    public function render_settings_page(): void {
        global $wpdb;

        // 1. Обробка збереження форми (POST запит)
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['fstu_save_settings'] ) ) {
            // Перевірка безпеки (Nonce)
            check_admin_referer( 'fstu_save_settings_action', 'fstu_settings_nonce' );

            if ( current_user_can( 'manage_options' ) && ! empty( $_POST['settings'] ) && is_array( $_POST['settings'] ) ) {

                foreach ( $_POST['settings'] as $param_name => $param_value ) {
                    $clean_name  = sanitize_text_field( $param_name );
                    // Дозволяємо HTML у посиланнях/текстах, якщо треба, але базово санітизуємо
                    $clean_value = sanitize_textarea_field( wp_unslash( $param_value ) );

                    // Оновлюємо запис у БД
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                    $wpdb->update(
                        'Settings',
                        [ 'ParamValue' => $clean_value ],
                        [ 'ParamName'  => $clean_name ],
                        [ '%s' ],
                        [ '%s' ]
                    );
                }

                // Виводимо зелене повідомлення про успіх (стандартний клас WP)
                echo '<div class="notice notice-success is-dismissible"><p><strong>Налаштування успішно збережено!</strong></p></div>';
            }
        }

        // 2. Отримання поточних налаштувань для виводу у форму
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $settings = $wpdb->get_results( "SELECT * FROM Settings ORDER BY ParamName ASC", ARRAY_A );

        // 3. Підключення View
        include FSTU_PLUGIN_DIR . 'views/admin/settings-page.php';
    }
}