<?php
/**
 * List handler for модуля Статистики.
 * Version: 1.0.0
 * Date_update: 2026-04-25
 */

namespace FSTU\Modules\Part;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Part_List {
    public function init(): void {
        add_shortcode( 'fstu_part', [ $this, 'render_shortcode' ] );
    }

    public function render_shortcode( $atts ): string {
        if ( ! is_user_logged_in() || ! current_user_can( Capabilities::VIEW_PART ) ) {
            return '<div class="alert alert-danger">Немає доступу до статистики.</div>';
        }

        wp_enqueue_style( 'fstu-part', FSTU_PLUGIN_URL . 'css/fstu-part.css', [], FSTU_VERSION );
        wp_enqueue_script( 'fstu-part-js', FSTU_PLUGIN_URL . 'js/fstu-part.js', ['jquery'], FSTU_VERSION, true );

        $repo = new Part_Repository();
        $years = $repo->get_years( get_current_user_id() );

        wp_localize_script( 'fstu-part-js', 'fstuPartData', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'fstu_part_nonce' ),
            'canAdmin' => current_user_can( 'administrator' ) || current_user_can( Capabilities::VIEW_PART_PROTOCOL )
        ] );

        ob_start();
        include FSTU_PLUGIN_DIR . 'views/part/main-page.php';
        return ob_get_clean();
    }
}