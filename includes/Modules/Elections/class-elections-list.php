<?php
/**
 * Контролер головної сторінки модуля виборів.
 * * Version: 1.0.0
 * Date_update: 2026-04-22
 */

namespace FSTU\Modules\Elections;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Elections_List {

    private Elections_Service $service;

    public function __construct() {
        $this->service = new Elections_Service();
    }

    public function init(): void {
        add_shortcode( 'fstu_elections', [ $this, 'render_shortcode' ] );
    }

    public function render_shortcode( $atts ): string {
        if ( ! is_user_logged_in() ) {
            return '<div class="fstu-alert fstu-alert--info">' . __( 'Будь ласка, авторизуйтесь для доступу до виборів.', 'fstu' ) . '</div>';
        }

        $permissions = Capabilities::get_elections_permissions();
        $elections   = $this->service->get_repository()->get_all_elections();

        // Підключаємо стилі та скрипти
        wp_enqueue_style( 'fstu-elections', FSTU_PLUGIN_URL . 'css/fstu-elections.css', [], FSTU_VERSION );

        // Підключення Select2 (для пошуку користувачів)
        wp_enqueue_style( 'select2', FSTU_PLUGIN_URL . 'css/select2.min.css', [], '4.1.0' );
        wp_enqueue_script( 'select2', FSTU_PLUGIN_URL . 'js/select2.min.js', [ 'jquery' ], '4.1.0', true );
        wp_enqueue_script( 'select2-uk', FSTU_PLUGIN_URL . 'js/uk.js', [ 'select2' ], '4.1.0', true );

        wp_enqueue_script( 'sortable-js', FSTU_PLUGIN_URL . 'js/Sortable.min.js', [], '1.15.2', true );
        wp_enqueue_script( 'fstu-elections', FSTU_PLUGIN_URL . 'js/fstu-elections.js', [ 'jquery', 'sortable-js', 'select2' ], FSTU_VERSION, true );

        // Локалізація змінних для JS (передача AJAX URL та Nonce)
        wp_localize_script( 'fstu-elections', 'fstuSettings', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'fstu_elections_nonce' ),
        ] );

        ob_start();
        include FSTU_PLUGIN_DIR . 'views/elections/main-page.php';
        return ob_get_clean();
    }
}