<?php
namespace FSTU\Modules\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Контролер відображення модуля "Заявки в ФСТУ".
 * Реєструє шорткод [fstu_applications], підключає скрипти/стилі.
 *
 * Version:     1.0.1
 * Date_update: 2026-04-06
 */
class Applications_List {

    private const ASSET_HANDLE = 'fstu-applications';
    public const  NONCE_ACTION = 'fstu_applications_nonce';

    public function init(): void {
        add_shortcode( 'fstu_applications', [ $this, 'render_shortcode' ] );
    }

    public function render_shortcode( array $atts = [] ): string {
        if ( ! is_user_logged_in() ) {
            return '<div class="fstu-alert fstu-alert--error">Ви повинні увійти як адміністратор або реєстратор.</div>';
        }

        if ( ! $this->current_user_can_view_module() ) {
            return '<div class="fstu-alert fstu-alert--error">У вас немає доступу до цього модуля.</div>';
        }

        $this->ensure_email_setting_exists();

        [ $regions, $units ] = $this->get_filter_datasets();

        $this->enqueue_assets();

        ob_start();
        include FSTU_PLUGIN_DIR . 'views/applications/main-page.php';
        return ob_get_clean();
    }

    /**
     * Чи має поточний користувач доступ до модуля заявок.
     */
    private function current_user_can_view_module(): bool {
        $user  = wp_get_current_user();
        $roles = is_array( $user->roles ) ? $user->roles : [];

        return current_user_can( 'manage_options' )
            || in_array( 'administrator', $roles, true )
            || in_array( 'userregistrar', $roles, true );
    }

    /**
     * Готує довідники для фільтрів модуля.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    private function get_filter_datasets(): array {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
        $regions = $wpdb->get_results(
            "SELECT Region_ID, Region_Name FROM S_Region ORDER BY Region_Name ASC",
            ARRAY_A
        ) ?: [];

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
        $units = $wpdb->get_results(
            "SELECT Unit_ID, Unit_ShortName, Region_ID FROM S_Unit ORDER BY Unit_ShortName ASC",
            ARRAY_A
        ) ?: [];

        return [ $regions, $units ];
    }

    private function enqueue_assets(): void {
        $ver = FSTU_VERSION;

        // Підключення стилів із суворо пустим масивом залежностей
        wp_enqueue_style(
            self::ASSET_HANDLE,
            FSTU_PLUGIN_URL . 'css/fstu-applications.css',
            [],
            $ver
        );

        // Підключення скриптів
        wp_enqueue_script(
            self::ASSET_HANDLE,
            FSTU_PLUGIN_URL . 'js/fstu-applications.js',
            [ 'jquery' ],
            $ver,
            true
        );

        // Визначення прав доступу
        $user     = wp_get_current_user();
        $roles    = (array) $user->roles;
        $is_admin = in_array( 'administrator', $roles, true );
        $is_reg   = in_array( 'userregistrar', $roles, true );

        // Передача даних у JS
        wp_localize_script(
            self::ASSET_HANDLE,
            'fstuApplications',
            [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
                'isAdmin' => $is_admin ? '1' : '0',
                'isReg'   => ( $is_admin || $is_reg ) ? '1' : '0',
                'strings' => [
                    'confirmAccept' => 'Ви дійсно бажаєте ПРИЙНЯТИ цього кандидата в члени ФСТУ?',
                    'confirmReject' => 'Ви дійсно бажаєте ВІДХИЛИТИ цю заявку?',
                    'errorGeneric'  => 'Сталася помилка. Спробуйте ще раз.',
                    'loading'       => 'Завантаження...',
                ],
            ]
        );
    }

    /**
     * Перевіряє наявність Email-адреси відправника у таблиці Settings.
     * Якщо відсутня — створює новий запис.
     */
    private function ensure_email_setting_exists(): void {
        global $wpdb;

        $setting_exists = $wpdb->get_var(
            $wpdb->prepare( "SELECT ParamName FROM Settings WHERE ParamName = %s LIMIT 1", 'EmailFSTU' )
        );

        if ( ! $setting_exists ) {
            $wpdb->insert(
                'Settings',
                [
                    'ParamName'  => 'EmailFSTU',
                    'ParamValue' => 'fstu.com.ua@gmail.com',
                    'ParamNotes' => 'офіційна Email-адреса відправника при автоматичному відправленні',
                ],
                [ '%s', '%s', '%s' ]
            );
        }
    }
}