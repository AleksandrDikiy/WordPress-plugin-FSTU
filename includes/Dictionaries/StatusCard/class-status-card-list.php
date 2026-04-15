<?php
/**
 * Контролер довідника статусів карток.
 * Version: 1.1.0
 * Date_update: 2026-04-15
 */

namespace FSTU\Dictionaries\StatusCard;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Class_Status_Card_List {

    public function __construct() {
        add_shortcode( 'fstu_status_card_dict', [ $this, 'render_shortcode' ] );
    }

    public function render_shortcode(): string {
        if ( ! current_user_can( 'manage_options' ) ) {
            return '<div class="fstu-alert fstu-alert--danger">НЕМАЄ ДОСТУПУ</div>';
        }

        $this->enqueue_assets();

        ob_start();
        include FSTU_PLUGIN_DIR . 'views/status-card/main-page.php';
        return ob_get_clean();
    }

    private function enqueue_assets(): void {
        wp_enqueue_style(
            'fstu-status-card-style',
            FSTU_PLUGIN_URL . 'css/fstu-status-card.css',
            [],
            '1.1.0'
        );

        wp_enqueue_script(
            'fstu-status-card-script',
            FSTU_PLUGIN_URL . 'js/fstu-status-card.js',
            [ 'jquery', 'jquery-ui-sortable' ], // Підключаємо UI Sortable для Drag-and-Drop
            '1.1.0',
            true
        );

        wp_localize_script( 'fstu-status-card-script', 'fstuStatusCardData', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'fstu_status_card_nonce' ),
        ] );
    }
}