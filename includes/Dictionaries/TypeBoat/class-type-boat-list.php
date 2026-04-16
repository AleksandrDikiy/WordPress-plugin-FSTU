<?php
/**
 * Контролер відображення модуля "Виробники та типи суден".
 *
 * Version:     1.0.0
 * Date_update: 2026-04-16
 */

namespace FSTU\Dictionaries\TypeBoat;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Type_Boat_List {

    public function init(): void {
        add_shortcode( 'fstu_type_boat', [ $this, 'render_shortcode' ] );
    }

    public function render_shortcode( $atts = [] ): string {
        $permissions = Capabilities::get_type_boat_permissions();

        // Блокуємо доступ, якщо немає навіть права на перегляд
        if ( ! $permissions['canView'] ) {
            return '<div class="fstu-alert fstu-alert--danger">' . esc_html__( 'У вас немає прав для перегляду цього довідника.', 'fstu' ) . '</div>';
        }

        // Підключаємо ресурси без залежностей від інших модулів
        wp_enqueue_style( 'fstu-type-boat-css', FSTU_PLUGIN_URL . 'css/fstu-type-boat.css', [], time() );
        wp_enqueue_script( 'fstu-type-boat-js', FSTU_PLUGIN_URL . 'js/fstu-type-boat.js', [ 'jquery' ], time(), true );

        // Передаємо локалізовані змінні для JS
        wp_localize_script( 'fstu-type-boat-js', 'fstuTypeBoat', [
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'fstu_module_nonce' ),
            'permissions' => $permissions,
            'i18n'        => [
                'error'          => __( 'Сталася помилка з\'єднання. Спробуйте пізніше.', 'fstu' ),
                'deleteConfirm'  => __( 'Ви дійсно бажаєте видалити цей запис? Відмінити дію буде неможливо.', 'fstu' ),
            ],
        ] );

        ob_start();
        // Підключаємо виключно HTML-шаблон
        require FSTU_PLUGIN_DIR . 'views/type-boat/main-page.php';
        return ob_get_clean();
    }
}