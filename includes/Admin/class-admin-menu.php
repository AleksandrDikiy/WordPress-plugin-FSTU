<?php
/**
 * Клас управління меню адміністратора.
 * * Version: 1.3.0
 * Date_update: 2026-04-13
 */

namespace FSTU\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin_Menu {

    public function init(): void {
        add_action( 'admin_menu', [ $this, 'register_menus' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        // Відновлено: обробник збереження сторінки Налаштувань
        add_action( 'admin_init', [ $this, 'handle_settings_save' ] );
    }

    public function register_menus(): void {
        // 1. Головне меню (Шорткоди) - Новий Хаб
        add_menu_page(
            __( 'ФСТУ: Шорткоди', 'fstu' ),
            __( 'ФСТУ', 'fstu' ),
            'manage_options',
            'fstu-main',
            [ $this, 'render_main_page' ],
            'dashicons-clipboard',
            30
        );

        // 2. Підменю (Налаштування) - Відновлено зі старого функціоналу
        add_submenu_page(
            'fstu-main',
            __( 'Налаштування системи ФСТУ', 'fstu' ),
            __( 'Налаштування', 'fstu' ),
            'manage_options',
            'fstu-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Підключення скриптів та стилів.
     */
    public function enqueue_assets( string $hook_suffix ): void {
        // Завантажуємо ассети ТІЛЬКИ на сторінці шорткодів
        if ( 'toplevel_page_fstu-main' === $hook_suffix ) {
            wp_enqueue_style( 'fstu-admin-css', FSTU_PLUGIN_URL . 'css/fstu-admin.css', [], FSTU_VERSION );
            wp_enqueue_script( 'fstu-admin-js', FSTU_PLUGIN_URL . 'js/fstu-admin.js', [ 'jquery' ], FSTU_VERSION, true );
        }
    }

    /**
     * Рендеринг головної сторінки з вкладками та шорткодами.
     */
    public function render_main_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'У вас немає достатніх прав.', 'fstu' ) );
        }

        // Підключення реєстру шорткодів (виправлення автозавантажувача)
        require_once FSTU_PLUGIN_DIR . 'includes/Admin/class-shortcodes-registry.php';

        $view_path = FSTU_PLUGIN_DIR . 'views/admin/fstu-main-page.php';
        if ( file_exists( $view_path ) ) {
            require_once $view_path;
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Помилка: Файл fstu-main-page.php не знайдено.', 'fstu' ) . '</p></div>';
        }
    }

    /**
     * Рендеринг сторінки глобальних налаштувань (Відновлено).
     */
    public function render_settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'У вас немає достатніх прав.', 'fstu' ) );
        }

        global $wpdb;
        // Отримуємо налаштування з кастомної таблиці Settings
        $settings = $wpdb->get_results( "SELECT * FROM Settings", ARRAY_A );

        $view_path = FSTU_PLUGIN_DIR . 'views/admin/settings-page.php';
        if ( file_exists( $view_path ) ) {
            require_once $view_path;
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Помилка: Файл settings-page.php не знайдено.', 'fstu' ) . '</p></div>';
        }
    }

    /**
     * Обробка збереження глобальних налаштувань (Відновлено).
     */
    public function handle_settings_save(): void {
        if ( ! isset( $_POST['fstu_save_settings'] ) ) {
            return;
        }

        if ( ! check_admin_referer( 'fstu_save_settings_action', 'fstu_settings_nonce' ) ) {
            wp_die( esc_html__( 'Помилка перевірки безпеки (Nonce).', 'fstu' ) );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'У вас немає достатніх прав.', 'fstu' ) );
        }

        if ( isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ) {
            global $wpdb;

            // Збереження даних з урахуванням дозволу на HTML (наприклад, для підписів email)
            foreach ( $_POST['settings'] as $param_name => $param_value ) {
                $wpdb->update(
                    'Settings',
                    [ 'ParamValue' => wp_kses_post( wp_unslash( $param_value ) ) ],
                    [ 'ParamName'  => sanitize_text_field( $param_name ) ],
                    [ '%s' ],
                    [ '%s' ]
                );
            }

            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Налаштування успішно збережено.', 'fstu' ) . '</p></div>';
            });
        }
    }
}