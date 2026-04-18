<?php
/**
 * Головний клас модуля "Реєстр платіжних документів".
 * Відповідає за шорткод [fstu_payment_docs] та підключення скриптів.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-05
 *
 * @package FSTU\PaymentDocs
 */

namespace FSTU\PaymentDocs;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Payment_Docs_List {

    /**
     * Ініціалізація модуля (реєстрація шорткоду та скриптів).
     */
    public function init(): void {
        add_shortcode( 'fstu_payment_docs', [ $this, 'render_shortcode' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Рендер шорткоду.
     */
    public function render_shortcode(): string {
        if ( ! is_user_logged_in() ) {
            return '<div class="fstu-alert fstu-alert--error">Ви повинні увійти як адміністратор або реєстратор.</div>';
        }

        $user_roles = (array) wp_get_current_user()->roles;
        $allowed_roles = [ 'administrator', 'globalregistrar', 'userregistrar', 'groupauditor' ];

        if ( empty( array_intersect( $allowed_roles, $user_roles ) ) ) {
            return '<div class="fstu-alert fstu-alert--error">У вас немає доступу до цього модуля.</div>';
        }

        ob_start();
        $this->load_view();
        return ob_get_clean();
    }

    /**
     * Підключення CSS та JS (тільки на сторінці з шорткодом).
     */
    public function enqueue_assets(): void {
        global $post;
        if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'fstu_payment_docs' ) ) {
            return;
        }

        $ver = time(); // Тимчасово для розробки (скидання кешу)

        // Підключаємо стилі та скрипти (створимо їх пізніше)
        wp_enqueue_style( 'fstu-payment-docs-css', FSTU_PLUGIN_URL . 'css/fstu-payment-docs.css', [], $ver );
        wp_enqueue_script( 'fstu-payment-docs-js', FSTU_PLUGIN_URL . 'js/fstu-payment-docs.js', [ 'jquery' ], $ver, true );

        global $wpdb;
        $unit_id = absint( $_GET['unit_id'] ?? 0 );
        $region_id = absint( $_GET['region_id'] ?? 0 );

        // Якщо передали region_id, знаходимо перший-ліпший Unit_ID для цього регіону
        if ( $region_id > 0 && ! $unit_id ) {
            $unit_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT Unit_ID FROM S_Unit WHERE Region_ID = %d LIMIT 1", $region_id ) );
        }

        wp_localize_script( 'fstu-payment-docs-js', 'fstuPaymentDocs', [
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'fstu_payment_docs_nonce' ),
            'isAdmin'  => current_user_can( 'manage_options' ) ? '1' : '0',
            'unitId'   => $unit_id,
        ] );
    }

    /**
     * Завантажує головний HTML-шаблон.
     */
    private function load_view(): void {
        global $wpdb;
        $user_id = get_current_user_id();
        $is_admin = current_user_can( 'manage_options' );

        // Базові довідники для фільтрів та шапки
        $units = $wpdb->get_results( "SELECT Unit_ID, Unit_ShortName FROM S_Unit ORDER BY Unit_ShortName", ARRAY_A );
        $resp_users = $wpdb->get_results( "SELECT User_ID, FIO FROM vUserFSTUnew WHERE UserFSTU='1' AND User_ID IN (SELECT DISTINCT(User_ID) FROM UserRegion) ORDER BY FIO", ARRAY_A );
        $years = $wpdb->get_col( "SELECT DISTINCT(Year_Name) FROM vUserDues ORDER BY Year_Name DESC" );

        // Довідники для Табличної Частини (ТЧ)
        $dues_types = $wpdb->get_results( "SELECT DuesType_ID, DuesType_Name FROM S_DuesType ORDER BY DuesType_ID", ARRAY_A );
        $fstu_users = $wpdb->get_results( "SELECT User_ID, FIO, Unit_ID FROM vUserFSTUnew WHERE UserFSTU='1' ORDER BY FIO", ARRAY_A );

        // Отримуємо суму внеску за замовчуванням
        $default_sum = $wpdb->get_var( "SELECT ParamValue FROM Settings WHERE ParamName='AnnualFee'" );
        $default_sum = $default_sum ? number_format( (float) $default_sum, 2, '.', '' ) : '25.00';

        // Шлях до шаблону
        $template_path = FSTU_PLUGIN_DIR . 'views/payment-docs/main-page.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            echo '<div class="fstu-alert fstu-alert--error">Помилка: Файл шаблону не знайдено.</div>';
        }
    }
}