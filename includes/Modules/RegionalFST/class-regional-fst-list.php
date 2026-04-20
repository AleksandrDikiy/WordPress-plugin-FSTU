<?php
/**
 * Контролер модуля "Осередки федерації спортивного туризму".
 *
 * Version:     1.0.1
 * Date_update: 2026-04-20
 *
 * @package FSTU\Modules\RegionalFST
 */

namespace FSTU\Modules\RegionalFST;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Class_Regional_FST_List {

    /**
     * Ініціалізація модуля (реєстрація шорткоду та скриптів).
     */
    public function init(): void {
        add_shortcode( 'fstu_regional_fst', [ $this, 'render_shortcode' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Рендер шорткоду.
     */
    public function render_shortcode(): string {
        ob_start();
        $this->load_view();
        return ob_get_clean();
    }

    /**
     * Підключення CSS та JS (тільки на сторінці з шорткодом).
     */
    public function enqueue_assets(): void {
        global $post;
        if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'fstu_regional_fst' ) ) {
            return;
        }

        $ver = '1.0.1';

        // Підключаємо Select2 для модального вікна (якщо він ще не підключений темою)
        if ( ! wp_style_is( 'select2', 'enqueued' ) ) {
            wp_enqueue_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0' );
        }
        if ( ! wp_script_is( 'select2', 'enqueued' ) ) {
            wp_enqueue_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', [ 'jquery' ], '4.1.0', true );
        }

        wp_enqueue_style( 'fstu-regional-fst', FSTU_PLUGIN_URL . 'css/fstu-regional-fst.css', [], $ver );
        wp_enqueue_script( 'fstu-regional-fst', FSTU_PLUGIN_URL . 'js/fstu-regional-fst.js', [ 'jquery', 'select2' ], $ver, true );

        // Передаємо права та налаштування у JS
        wp_localize_script( 'fstu-regional-fst', 'fstuRegionalFST', [
            'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'fstu_regional_fst_nonce' ),
            'permissions' => Capabilities::get_regional_fst_permissions(),
            'paymentUrl'  => site_url( '/personal/rejestr-platizhok/' ), // Виправлено посилання
        ] );
    }

    /**
     * Завантажує головний HTML-шаблон.
     */
    private function load_view(): void {
        global $wpdb;

        // Завантажуємо списки для <select> у модальному вікні
        $units = $wpdb->get_results( "SELECT Unit_ID, Unit_ShortName FROM S_Unit ORDER BY Unit_ShortName ASC", ARRAY_A );
        $roles = $wpdb->get_results( "SELECT MemberRegional_ID, MemberRegional_Name FROM vMemberRegional ORDER BY MemberRegional_Order ASC", ARRAY_A );

        $template_path = FSTU_PLUGIN_DIR . 'views/regional-fst/main-page.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            echo '<div class="fstu-alert fstu-alert--error">Помилка: Файл шаблону views/regional-fst/main-page.php не знайдено.</div>';
        }
    }
}