<?php
/**
 * Реєстрація меню плагіна в адмін-панелі WordPress.
 *
 * Version:     1.12.0
 * Date_update: 2026-04-10
 *
 * @package FSTU\Admin
 */

namespace FSTU\Admin;

use FSTU\Core\Capabilities;
use FSTU\Dictionaries\Commission\Commission_List;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin_Menu {

    public function init(): void {
        add_action( 'admin_menu', [ $this, 'register_menus' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    /**
     * Підключає стилі для службових адмін-сторінок плагіна.
     */
    public function enqueue_admin_assets(): void {
        $page = sanitize_text_field( wp_unslash( $_GET['page'] ?? '' ) );

        if ( 'fstu-settings' !== $page ) {
            return;
        }

        wp_enqueue_style(
            'fstu-admin-settings',
            FSTU_PLUGIN_URL . 'css/fstu-admin-settings.css',
            [],
            FSTU_VERSION
        );
    }

    public function register_menus(): void {
        $admin_capability = Capabilities::ACCESS_ADMIN;

        // Головне меню "ФСТУ"
        add_menu_page(
            'ФСТУ - Керування системою',
            'ФСТУ',
            $admin_capability,
            'fstu-main',
            [ $this, 'render_main_page' ],
            'dashicons-groups',
            30
        );

        add_submenu_page(
            'fstu-main',
            'Головна інформація',
            'Головна',
            $admin_capability,
            'fstu-main',
            [ $this, 'render_main_page' ]
        );

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
        $typeguidance_list_class = 'FSTU\\Dictionaries\\TypeGuidance\\TypeGuidance_List';
        $member_regional_list_class = 'FSTU\\Dictionaries\\MemberRegional\\Member_Regional_List';
        $member_guidance_list_class = 'FSTU\\Dictionaries\\MemberGuidance\\Member_Guidance_List';
        $country_list_class = 'FSTU\\Dictionaries\\Country\\Country_List';
        $region_list_class = 'FSTU\\Dictionaries\\Region\\Region_List';
        $city_list_class = 'FSTU\\Dictionaries\\City\\City_List';
        $eventtype_list_class = 'FSTU\\Dictionaries\\EventType\\EventType_List';
        $tourismtype_list_class = 'FSTU\\Dictionaries\\TourismType\\TourismType_List';
        $referees_list_class = 'FSTU\\Modules\\Registry\\Referees\\Referees_List';
            $member_card_applications_list_class = 'FSTU\\Modules\\Registry\\MemberCardApplications\\Member_Card_Applications_List';
            $steering_list_class = 'FSTU\\Modules\\Registry\\Steering\\Steering_List';
            $personal_cabinet_list_class = 'FSTU\\Modules\\PersonalCabinet\\Personal_Cabinet_List';
        $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/fstu_new/fstu.php' );
            $version = ! empty( $plugin_data['Version'] ) ? $plugin_data['Version'] : '1.12.0';
        $commission_page_url = class_exists( Commission_List::class )
          ? Commission_List::get_module_url( 'admin' )
          : '';
        $typeguidance_page_url = class_exists( $typeguidance_list_class )
          ? $typeguidance_list_class::get_module_url( 'admin' )
          : '';
        $member_regional_page_url = class_exists( $member_regional_list_class )
          ? $member_regional_list_class::get_module_url( 'admin' )
          : '';
        $member_guidance_page_url = class_exists( $member_guidance_list_class )
          ? $member_guidance_list_class::get_module_url( 'admin' )
          : '';
        $country_page_url = class_exists( $country_list_class )
          ? $country_list_class::get_module_url( 'admin' )
          : '';
        $region_page_url = class_exists( $region_list_class )
          ? $region_list_class::get_module_url( 'admin' )
          : '';
        $city_page_url = class_exists( $city_list_class )
          ? $city_list_class::get_module_url( 'admin' )
          : '';
        $eventtype_page_url = class_exists( $eventtype_list_class )
            ? $eventtype_list_class::get_module_url( 'admin' )
            : '';
        $tourismtype_page_url = class_exists( $tourismtype_list_class )
          ? $tourismtype_list_class::get_module_url( 'admin' )
          : '';
        $referees_page_url = class_exists( $referees_list_class )
          ? $referees_list_class::get_module_url( 'admin' )
          : '';
            $member_card_applications_page_url = class_exists( $member_card_applications_list_class )
              ? $member_card_applications_list_class::get_module_url( 'admin' )
              : '';
            $steering_page_url = class_exists( $steering_list_class )
              ? $steering_list_class::get_module_url( 'admin' )
              : '';
            $personal_cabinet_page_url = class_exists( $personal_cabinet_list_class )
              ? $personal_cabinet_list_class::get_module_url( 'admin' )
              : '';

        include dirname( __DIR__, 2 ) . '/views/admin/main-page.php';
    }

    /**
     * Вивід та обробка сторінки "Налаштування".
     */
    public function render_settings_page(): void {
        global $wpdb;

        $this->ensure_sailboats_settings_defaults();

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

    /**
     * Гарантує наявність Settings-рядків для шаблонів Sailboats.
     */
    private function ensure_sailboats_settings_defaults(): void {
        global $wpdb;

        $notification_service_class = 'FSTU\\Modules\\Registry\\Sailboats\\Sailboats_Notification_Service';
        if ( ! class_exists( $notification_service_class ) || ! method_exists( $notification_service_class, 'get_settings_defaults' ) ) {
            return;
        }

        $defaults = $notification_service_class::get_settings_defaults();
        if ( ! is_array( $defaults ) || empty( $defaults ) ) {
            return;
        }

        $settings_columns = $this->get_settings_columns();
        $has_description  = in_array( 'Description', $settings_columns, true );

        foreach ( $defaults as $param_name => $meta ) {
            $clean_name = sanitize_text_field( (string) $param_name );
            if ( '' === $clean_name ) {
                continue;
            }

            $description = isset( $meta['description'] ) ? sanitize_textarea_field( (string) $meta['description'] ) : '';

            $select_fields = $has_description ? 'ParamName, Description' : 'ParamName';

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $existing_row = $wpdb->get_row(
                $wpdb->prepare( 'SELECT ' . $select_fields . ' FROM Settings WHERE ParamName = %s LIMIT 1', $clean_name ),
                ARRAY_A
            );

            if ( is_array( $existing_row ) ) {
                if ( $has_description && '' !== $description && '' === trim( (string) ( $existing_row['Description'] ?? '' ) ) ) {
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                    $wpdb->update(
                        'Settings',
                        [ 'Description' => $description ],
                        [ 'ParamName' => $clean_name ],
                        [ '%s' ],
                        [ '%s' ]
                    );
                }

                continue;
            }

            $default_value = isset( $meta['value'] ) ? sanitize_textarea_field( (string) $meta['value'] ) : '';
            $insert_data   = [
                'ParamName'  => $clean_name,
                'ParamValue' => $default_value,
            ];
            $insert_format = [ '%s', '%s' ];

            if ( $has_description ) {
                $insert_data['Description'] = $description;
                $insert_format[]            = '%s';
            }

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->insert(
                'Settings',
                $insert_data,
                $insert_format
            );
        }
    }

    /**
     * Повертає список колонок таблиці Settings.
     *
     * @return array<int,string>
     */
    private function get_settings_columns(): array {
        static $columns = null;

        if ( is_array( $columns ) ) {
            return $columns;
        }

        global $wpdb;

        $schema_name = defined( 'DB_NAME' ) ? (string) DB_NAME : '';
        if ( '' === $schema_name ) {
            $columns = [];

            return $columns;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
        $result = $wpdb->get_col(
            $wpdb->prepare(
                'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s ORDER BY ORDINAL_POSITION ASC',
                $schema_name,
                'Settings'
            )
        );

        $columns = is_array( $result ) ? array_values( array_map( 'strval', $result ) ) : [];

        return $columns;
    }
}

