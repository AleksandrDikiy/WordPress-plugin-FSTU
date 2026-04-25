<?php
/**
 * Контролер шорткоду модуля "Ревізійна комісія".
 *
 * Version:     1.0.0
 * Date_update: 2026-04-25
 *
 * @package FSTU\Modules\Audit
 */

namespace FSTU\Modules\Audit;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Audit_List {

    public function init(): void {
        add_shortcode( 'fstu_audit', [ $this, 'render_shortcode' ] );
    }

    public function render_shortcode( $atts ): string {
        $permissions = Capabilities::get_audit_permissions();
        $this->enqueue_assets( $permissions );

        ob_start();
        include FSTU_PLUGIN_DIR . 'views/audit/main-page.php';
        return ob_get_clean();
    }

    private function enqueue_assets( array $permissions ): void {
        $version = defined( 'FSTU_VERSION' ) ? FSTU_VERSION : '1.0.0';

        wp_enqueue_style( 'fstu-audit-style', FSTU_PLUGIN_URL . 'css/fstu-audit.css', [], $version );
        wp_enqueue_script( 'fstu-audit-script', FSTU_PLUGIN_URL . 'js/fstu-audit.js', [ 'jquery' ], $version, true );

        wp_localize_script( 'fstu-audit-script', 'fstuAuditObj', [
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