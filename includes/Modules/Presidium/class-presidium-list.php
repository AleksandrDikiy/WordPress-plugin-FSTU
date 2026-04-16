<?php
/**
 * Контролер шорткоду [fstu_presidium].
 *
 * Version:     1.0.0
 * Date_update: 2026-04-16
 *
 * @package FSTU\Modules\Presidium
 */

namespace FSTU\Modules\Presidium;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Presidium_List {

    public function init(): void {
        add_shortcode( 'fstu_presidium', [ $this, 'render_shortcode' ] );
    }

    public function render_shortcode(): string {
        $this->enqueue_assets();

        ob_start();
        include FSTU_PLUGIN_DIR . 'views/presidium/main-page.php';
        return ob_get_clean();
    }

    private function enqueue_assets(): void {
        $version = defined( 'FSTU_VERSION' ) ? FSTU_VERSION : '1.0.0';

        wp_enqueue_style( 'fstu-presidium-style', FSTU_PLUGIN_URL . 'css/fstu-presidium.css', [], $version );
        wp_enqueue_script( 'fstu-presidium-script', FSTU_PLUGIN_URL . 'js/fstu-presidium.js', [ 'jquery' ], $version, true );

        wp_localize_script( 'fstu-presidium-script', 'fstuPresidiumObj', [
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'fstu_module_nonce' ),
            'i18n'      => [ 'error' => __( 'Помилка завантаження даних.', 'fstu' ) ]
        ]);
    }
}