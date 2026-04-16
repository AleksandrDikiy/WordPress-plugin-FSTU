<?php
/**
 * Клас-контролер для виводу довідника типів вітрильних залікових груп.
 * * Version: 1.0.0
 * Date_update: 2026-04-15
 */

namespace FSTU\Dictionaries\SailGroupType;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Class_Sail_Group_Type_List {

    public function __construct() {
        add_shortcode( 'fstu_sail_group_type', [ $this, 'render_shortcode' ] );
    }

    /**
     * Рендеринг шорткоду.
     */
    public function render_shortcode( $atts ): string {
        $permissions = Capabilities::get_sail_group_type_permissions();

        if ( empty( $permissions['canView'] ) ) {
            return '<div class="fstu-alert fstu-alert--danger">У вас немає доступу до цього розділу.</div>';
        }

        $this->enqueue_assets( $permissions );

        ob_start();

        // Підключення головного шаблону (буде створено у Фазі 3)
        $view_path = FSTU_PLUGIN_DIR . 'views/sail-group-type/main-page.php';
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
        // Стилі модуля
        wp_enqueue_style(
            'fstu-sail-group-type',
            FSTU_PLUGIN_URL . 'css/fstu-sail-group-type.css',
            [],
            FSTU_VERSION
        );

        // Скрипти модуля
        wp_enqueue_script(
            'fstu-sail-group-type',
            FSTU_PLUGIN_URL . 'js/fstu-sail-group-type.js',
            [ 'jquery', 'jquery-ui-sortable' ], // Додано залежність для drag-and-drop
            FSTU_VERSION,
            true
        );

        // Передача параметрів у JavaScript
        wp_localize_script(
            'fstu-sail-group-type',
            'fstuSailGroupTypeData',
            [
                'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
                'nonce'       => wp_create_nonce( 'fstu_module_nonce' ),
                'permissions' => $permissions,
                'i18n'        => [
                    'error'              => __( 'Сталася помилка. Спробуйте ще раз.', 'fstu' ),
                    'confirmDeleteType'  => __( 'Ви дійсно хочете ВИДАЛИТИ цей тип? Це можливо лише якщо в ньому немає підгруп.', 'fstu' ),
                    'confirmDeleteGroup' => __( 'Ви дійсно хочете ВИДАЛИТИ цю залікову групу? Дія незворотна.', 'fstu' ),
                ],
            ]
        );
    }
}