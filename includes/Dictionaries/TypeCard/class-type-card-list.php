<?php
/**
 * Клас-контролер для виводу довідника типів членських білетів.
 * * Version: 1.0.0
 * Date_update: 2026-04-15
 */

namespace FSTU\Dictionaries\TypeCard;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Class_Type_Card_List {

    public function __construct() {
        add_shortcode( 'fstu_type_card', [ $this, 'render_shortcode' ] );
    }

    /**
     * Рендеринг шорткоду.
     */
    public function render_shortcode( $atts ): string {
        $permissions = Capabilities::get_type_card_permissions();

        if ( empty( $permissions['canView'] ) ) {
            return '<div class="fstu-alert fstu-alert--danger">У вас немає доступу до цього розділу.</div>';
        }

        $this->enqueue_assets( $permissions );

        ob_start();
        // Шлях до шаблону, який ми створимо у Фазі 3
        $view_path = FSTU_PLUGIN_DIR . 'views/typecard/main-page.php';
        if ( file_exists( $view_path ) ) {
            require $view_path;
        } else {
            echo '<div class="fstu-alert fstu-alert--danger">Помилка: Шаблон не знайдено.</div>';
        }

        return ob_get_clean();
    }

    /**
     * Підключення скриптів та стилів для шорткоду.
     */
    private function enqueue_assets( array $permissions ): void {
        // Стилі модуля (зверніть увагу на назву файлу з дефісами)
        wp_enqueue_style(
            'fstu-type-card',
            FSTU_PLUGIN_URL . 'css/fstu-type-card.css',
            [],
            FSTU_VERSION
        );

        // Скрипти модуля (зверніть увагу на назву файлу з дефісами)
        wp_enqueue_script(
            'fstu-type-card',
            FSTU_PLUGIN_URL . 'js/fstu-type-card.js',
            [ 'jquery' ], // Тільки базовий jQuery
            FSTU_VERSION,
            true
        );

        // Передача параметрів у JavaScript (важливо щоб handle збігався з wp_enqueue_script)
        wp_localize_script(
            'fstu-type-card',
            'fstuTypeCardData',
            [
                'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
                'nonce'       => wp_create_nonce( 'fstu_module_nonce' ),
                'permissions' => $permissions,
                'i18n'        => [
                    'error'         => __( 'Сталася помилка. Спробуйте ще раз.', 'fstu' ),
                    'confirmDelete' => __( 'Ви дійсно хочете ВИДАЛИТИ цей тип квитка? Дія незворотна.', 'fstu' ),
                ],
            ]
        );
    }
}