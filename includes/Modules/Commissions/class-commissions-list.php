<?php
/**
 * Клас для відображення фронтенд-частини модуля "Board".
 * * Version: 1.1.0
 * Date_update: 2026-04-18
 */

namespace FSTU\Modules\Commissions;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Commissions_List {

    /**
     * Реєстрація шорткоду [fstu_board].
     */
    public function init(): void {
        add_shortcode( 'fstu_board', [ $this, 'render_shortcode' ] );
    }

    /**
     * Рендеринг основного інтерфейсу.
     */
    public function render_shortcode( $atts ): string {
        // Отримуємо права доступу для передачі у View та JS
        $permissions = Capabilities::get_board_permissions();

        // Підключаємо стилі та скрипти
        $this->enqueue_assets( $permissions );

        // Завантажуємо головний шаблон з НОВОЇ папки views/board/
        ob_start();
        $view_path = FSTU_PLUGIN_DIR . 'views/board/main-page.php';

        if ( file_exists( $view_path ) ) {
            include $view_path;
        } else {
            echo '<div class="fstu-error">Template views/board/main-page.php not found.</div>';
        }

        return ob_get_clean();
    }

    /**
     * Підключення Assets.
     */
    private function enqueue_assets( array $permissions ): void {
        wp_enqueue_style( 'fstu-board-css', FSTU_PLUGIN_URL . 'css/fstu-board.css', [], FSTU_VERSION );
        wp_enqueue_script( 'fstu-board-js', FSTU_PLUGIN_URL . 'js/fstu-board.js', [ 'jquery' ], FSTU_VERSION, true );

        // Передача даних у JS
        wp_localize_script( 'fstu-board-js', 'fstuBoardData', [
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'fstu_board_nonce' ),
            'permissions' => $permissions,
            'i18n'        => [
                'loading' => __( 'Завантаження...', 'fstu' ),
                'error'   => __( 'Сталася помилка.', 'fstu' ),
            ]
        ] );
    }
}