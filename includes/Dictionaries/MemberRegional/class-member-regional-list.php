<?php
/**
 * Контролер відображення модуля "Довідник посад федерацій".
 * Реєструє шорткод [fstu_member_regional], підключає скрипти/стилі,
 * * передає локалізовані змінні у JS.
 *
 * Version:     1.1.0
 * Date_update: 2026-04-18
 *
 * @package FSTU\Dictionaries\MemberRegional
 */

namespace FSTU\Dictionaries\MemberRegional;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Member_Regional_List {

    private const ASSET_HANDLE = 'fstu-member-regional';

    public const SHORTCODE = 'fstu_member_regional';

    private const ROUTE_OPTION = 'fstu_member_regional_page_url';

    public const NONCE_ACTION = 'fstu_member_regional_nonce';

    public function init(): void {
        add_shortcode( self::SHORTCODE, [ $this, 'render_shortcode' ] );
    }

    public function render_shortcode( array $atts = [] ): string {
        unset( $atts );

        // Використовуємо прямий виклик з Core\Capabilities, оскільки локального методу немає
        $permissions = Capabilities::get_member_regional_permissions();
        $view_data   = $this->get_view_data( $permissions );

        $this->enqueue_style();

        if ( ! empty( $permissions['canView'] ) ) {
            $this->enqueue_script( $permissions );
        }

        extract( $view_data, EXTR_SKIP );

        ob_start();
        include FSTU_PLUGIN_DIR . 'views/member-regional/main-page.php';
        return ob_get_clean();
    }

    public static function get_module_url( string $context = 'default' ): string {
        $configured_url = get_option( self::ROUTE_OPTION, '' );
        $url            = self::resolve_configured_url( is_string( $configured_url ) ? $configured_url : '' );

        if ( '' === $url ) {
            $url = self::discover_shortcode_page_url();
        }

        $filtered_url = apply_filters( 'fstu_member_regional_module_url', $url, $context );

        return is_string( $filtered_url ) && '' !== $filtered_url ? $filtered_url : $url;
    }

    private function enqueue_style(): void {
        wp_enqueue_style(
            self::ASSET_HANDLE,
            FSTU_PLUGIN_URL . 'css/fstu-member-regional.css',
            [],
            FSTU_VERSION
        );
    }

    private function enqueue_script( array $permissions ): void {
        wp_enqueue_script(
            self::ASSET_HANDLE,
            FSTU_PLUGIN_URL . 'js/fstu-member-regional.js',
            [ 'jquery', 'jquery-ui-sortable' ], // Додано залежність для drag-and-drop
            FSTU_VERSION,
            true
        );

        wp_localize_script(
            self::ASSET_HANDLE,
            'fstuMemberRegionalL10n',
            [
                'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
                'nonce'       => wp_create_nonce( self::NONCE_ACTION ),
                'permissions' => $permissions,
                'table'       => [
                    'colspan' => ( ! empty( $permissions['canAdminMeta'] ) ? 5 : 3 ) + ( ! empty( $permissions['canManage'] ) ? 1 : 0 ),
                ],
                'messages'    => [
                    'loading'          => __( 'Завантаження...', 'fstu' ),
                    'error'            => __( 'Помилка завантаження даних.', 'fstu' ),
                    'noAccess'         => __( 'Немає прав для виконання цієї дії.', 'fstu' ),
                    'confirmDelete'    => __( 'Ви дійсно хочете видалити цей запис?', 'fstu' ),
                    'deleteSuccess'    => __( 'Запис успішно видалений.', 'fstu' ),
                    'deleteError'      => __( 'Помилка при видаленні запису.', 'fstu' ),
                    'saveSuccess'      => __( 'Запис успішно збережений.', 'fstu' ),
                    'saveError'        => __( 'Помилка при збереженні запису.', 'fstu' ),
                    'protocolError'    => __( 'Не вдалося завантажити протокол.', 'fstu' ),
                    'protocolEmpty'    => __( 'Записи протоколу відсутні.', 'fstu' ),
                    'noResults'        => __( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ),
                    'formAddTitle'     => __( 'Додавання посади федерації', 'fstu' ),
                    'formEditTitle'    => __( 'Редагування посади федерації', 'fstu' ),
                    'formViewTitle'    => __( 'Перегляд посади федерації', 'fstu' ),
                    'protectedDelete'  => __( 'Цей системний запис не може бути видалений.', 'fstu' ),
                    'loginPromptTitle' => __( 'Щоб бачити додаткові функції, будь ласка, авторизуйтесь.', 'fstu' ),
                    'reorderSuccess'   => __( 'Порядок сортування збережено.', 'fstu' ),
                    'reorderError'     => __( 'Помилка збереження порядку сортування.', 'fstu' ),
                ],
            ]
        );
    }

    private function get_view_data( array $permissions ): array {
        $module_url = self::get_module_url( 'login' );

        return [
            'permissions'     => $permissions,
            'guest_mode'      => empty( $permissions['canView'] ),
            'guest_login_url' => wp_login_url( '' !== $module_url ? $module_url : home_url( '/' ) ),
        ];
    }

    private static function resolve_configured_url( string $configured_url ): string {
        $configured_url = trim( $configured_url );
        if ( '' === $configured_url ) {
            return '';
        }
        if ( str_starts_with( $configured_url, '/' ) ) {
            return home_url( $configured_url );
        }
        return wp_http_validate_url( $configured_url ) ? $configured_url : '';
    }

    private static function discover_shortcode_page_url(): string {
        global $wpdb;

        $shortcode_like = '%' . $wpdb->esc_like( '[' . self::SHORTCODE ) . '%';
        $post_types     = [ 'page', 'post' ];
        $placeholders   = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );

        $sql = "SELECT ID
			FROM {$wpdb->posts}
			WHERE post_status = 'publish'
				AND post_type IN ({$placeholders})
				AND post_content LIKE %s
			ORDER BY CASE WHEN post_type = 'page' THEN 0 ELSE 1 END ASC, menu_order ASC, ID ASC
			LIMIT 1";

        $params  = array_merge( $post_types, [ $shortcode_like ] );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
        $page_id = (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );

        if ( $page_id <= 0 ) {
            return '';
        }
        $permalink = get_permalink( $page_id );

        return is_string( $permalink ) ? $permalink : '';
    }
}