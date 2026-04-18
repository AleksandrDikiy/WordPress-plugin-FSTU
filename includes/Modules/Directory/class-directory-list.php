<?php
/**
 * Контролер відображення модуля Directory = Виконком.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-18
 *
 * @package FSTU\Modules\Directory
 */

namespace FSTU\Modules\Directory;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Directory_List {

    /**
     * Ініціалізація хуків.
     */
    public function init(): void {
        add_shortcode( 'fstu_directory', [ $this, 'render_shortcode' ] );
    }

    /**
     * Обробка шорткоду [fstu_directory].
     */
    public function render_shortcode( $atts ): string {
        // 1. Перевірка доступу (з матриці: тільки авторизовані з відповідними правами)
        if ( ! is_user_logged_in() ) {
            return '<div class="fstu-alert fstu-alert--danger">' . esc_html__( 'Доступ заборонено. Будь ласка, авторизуйтесь.', 'fstu' ) . '</div>';
        }

        $permissions = Capabilities::get_directory_permissions();

        // Якщо немає навіть базового права на перегляд списку
        if ( empty( $permissions['canViewList'] ) ) {
            return '<div class="fstu-alert fstu-alert--danger">' . esc_html__( 'У вас немає прав для перегляду цього розділу.', 'fstu' ) . '</div>';
        }

        // 2. Підключення активів (Assets)
        $this->enqueue_assets( $permissions );

        // 3. Рендер HTML-шаблону
        ob_start();

        // Змінні, доступні всередині шаблону
        $can_manage = $permissions['canManage'];

        include FSTU_PLUGIN_DIR . 'views/directory/main-page.php';

        return ob_get_clean();
    }

    /**
     * Підключення CSS та JS з передачею параметрів.
     */
    private function enqueue_assets( array $permissions ): void {
        $version = defined( 'FSTU_VERSION' ) ? FSTU_VERSION : '1.0.0';

        wp_enqueue_style(
            'fstu-directory-style',
            FSTU_PLUGIN_URL . 'css/fstu-directory.css',
            [],
            $version
        );

        wp_enqueue_script(
            'fstu-directory-script',
            FSTU_PLUGIN_URL . 'js/fstu-directory.js',
            [ 'jquery' ], // УВАГА: Залежність тільки від jQuery
            $version,
            true
        );

        wp_localize_script(
            'fstu-directory-script',
            'fstuDirectoryObj',
            [
                'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
                'pluginUrl'   => FSTU_PLUGIN_URL,
                'nonce'       => wp_create_nonce( 'fstu_module_nonce' ),
                'permissions' => $permissions,
                'i18n'        => [
                    'error'       => __( 'Сталася помилка. Спробуйте пізніше.', 'fstu' ),
                    'confirm_del' => __( 'Ви дійсно хочете видалити цей запис?', 'fstu' ),
                ],
            ]
        );
    }
}