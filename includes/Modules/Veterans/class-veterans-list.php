<?php
/**
 * Контролер шорткоду модуля "Рада ветеранів".
 *
 * Version:     1.1.0
 * Date_update: 2026-04-25
 *
 * @package FSTU\Modules\Veterans
 */

namespace FSTU\Modules\Veterans;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Veterans_List {

    public function init(): void {
        add_shortcode( 'fstu_veterans', [ $this, 'render_shortcode' ] );
    }

    public function render_shortcode( $atts ): string {
        $permissions = Capabilities::get_veterans_permissions();
        $this->enqueue_assets( $permissions );

        ob_start();
        include FSTU_PLUGIN_DIR . 'views/veterans/main-page.php';
        return ob_get_clean();
    }

    private function enqueue_assets( array $permissions ): void {
        $version = defined( 'FSTU_VERSION' ) ? FSTU_VERSION : '1.0.0';

        wp_enqueue_style( 'fstu-veterans-style', FSTU_PLUGIN_URL . 'css/fstu-veterans.css', [], $version );
        wp_enqueue_script( 'fstu-veterans-script', FSTU_PLUGIN_URL . 'js/fstu-veterans.js', [ 'jquery' ], $version, true );

        wp_localize_script( 'fstu-veterans-script', 'fstuVeteransObj', [
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'fstu_module_nonce' ),
            'permissions' => $permissions,
            'i18n'        => [
                'error'       => __( 'Сталася помилка. Спробуйте пізніше.', 'fstu' ),
                'confirm_del' => __( 'Ви дійсно хочете видалити цей запис?', 'fstu' ),
                'copied'      => __( 'Скопійовано', 'fstu' ),
            ],
        ] );
    }
}