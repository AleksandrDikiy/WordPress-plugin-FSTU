<?php
namespace FSTU\Modules\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Контролер відображення модуля "Заявки в ФСТУ".
 * Реєструє шорткод [fstu_applications], підключає скрипти/стилі.
 *
 * Version:     1.2.3
 * Date_update: 2026-04-06
 */
class Applications_List {

    private const ASSET_HANDLE = 'fstu-applications';
    private const MODULE_PAGE_URL = 'https://www.fstu.com.ua/appuserfstu/';
    private const PAYMENT_DOCS_SHORTCODE = '[fstu_payment_docs]';
    private const PAYMENT_DOCS_URL_CACHE_KEY = 'fstu_applications_payment_docs_page_url';
    private const PAYMENT_DOCS_URL_CACHE_TTL = HOUR_IN_SECONDS;
    public const  NONCE_ACTION = 'fstu_applications_nonce';

    private ?Applications_Repository $repository = null;

    public function init(): void {
        add_shortcode( 'fstu_applications', [ $this, 'render_shortcode' ] );
        add_action( 'save_post_page', [ $this, 'clear_payment_docs_page_url_cache' ] );
    }

    public function render_shortcode( array $atts = [] ): string {
        if ( ! is_user_logged_in() ) {
            return '<div class="fstu-alert fstu-alert--error">Ви повинні увійти як адміністратор або реєстратор.</div>';
        }

        if ( ! $this->current_user_can_view_module() ) {
            return '<div class="fstu-alert fstu-alert--error">У вас немає доступу до цього модуля.</div>';
        }

        [ $regions, $units ] = $this->get_filter_datasets();
        $applications_page_url = self::MODULE_PAGE_URL;
        $payment_docs_shortcode = self::PAYMENT_DOCS_SHORTCODE;

        $this->enqueue_assets();

        ob_start();
        include FSTU_PLUGIN_DIR . 'views/applications/main-page.php';
        return ob_get_clean();
    }

    /**
     * Чи має поточний користувач доступ до модуля заявок.
     */
    private function current_user_can_view_module(): bool {
        $user  = wp_get_current_user();
        $roles = is_array( $user->roles ) ? $user->roles : [];

        return current_user_can( 'manage_options' )
            || in_array( 'administrator', $roles, true )
            || in_array( 'userregistrar', $roles, true );
    }

    /**
     * Готує довідники для фільтрів модуля.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    private function get_filter_datasets(): array {
        return $this->get_repository()->get_filter_datasets();
    }

    /**
     * Повертає repository модуля заявок.
     */
    private function get_repository(): Applications_Repository {
        if ( null === $this->repository ) {
            $this->repository = new Applications_Repository();
        }

        return $this->repository;
    }

    private function enqueue_assets(): void {
        $ver = FSTU_VERSION;
        $payment_docs_page_url = $this->get_payment_docs_page_url();

        // Підключення стилів із суворо пустим масивом залежностей
        wp_enqueue_style(
            self::ASSET_HANDLE,
            FSTU_PLUGIN_URL . 'css/fstu-applications.css',
            [],
            $ver
        );

        // Підключення скриптів
        wp_enqueue_script(
            self::ASSET_HANDLE,
            FSTU_PLUGIN_URL . 'js/fstu-applications.js',
            [ 'jquery' ],
            $ver,
            true
        );

        // Визначення прав доступу
        $user     = wp_get_current_user();
        $roles    = (array) $user->roles;
        $is_admin = in_array( 'administrator', $roles, true );
        $is_reg   = in_array( 'userregistrar', $roles, true );

        // Передача даних у JS
        wp_localize_script(
            self::ASSET_HANDLE,
            'fstuApplications',
            [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
                'isAdmin' => $is_admin ? '1' : '0',
                'isReg'   => ( $is_admin || $is_reg ) ? '1' : '0',
                'blockedRole' => 'Blocked',
                'modulePageUrl' => self::MODULE_PAGE_URL,
                'paymentDocsPageUrl' => $payment_docs_page_url,
                'paymentDocsShortcode' => self::PAYMENT_DOCS_SHORTCODE,
                'strings' => [
                    'confirmAccept' => 'Ви дійсно бажаєте ПРИЙНЯТИ цього кандидата в члени ФСТУ?',
                    'confirmReject' => 'Ви дійсно бажаєте ВІДХИЛИТИ цю заявку?',
                    'errorGeneric'  => 'Сталася помилка. Спробуйте ще раз.',
                    'loading'       => 'Завантаження...',
                    'paymentDocsNotConfigured' => 'Сторінка PaymentDocs ще не визначена. Використайте шорткод [fstu_payment_docs].',
                    'acceptModalTitle' => 'Підтвердження прийняття кандидата',
                    'acceptSuccess' => 'Користувача успішно прийнято в члени ФСТУ.',
                    'acceptError' => 'Не вдалося прийняти кандидата.',
                    'acceptSubmit' => 'Прийняти в члени ФСТУ',
                    'changeOfstModalTitle' => 'Зміна ОФСТ кандидата',
                    'changeOfstSuccess' => 'Осередок оновлено, а історію змін збережено.',
                    'changeOfstError' => 'Не вдалося змінити ОФСТ.',
                    'changeOfstNoUnit' => 'Оберіть осередок для збереження.',
                    'rejectModalTitle' => 'Відхилення заявки',
                    'rejectSubmit' => 'Відхилити заявку',
                    'rejectSuccess' => 'Заявку успішно відхилено.',
                    'rejectError' => 'Не вдалося відхилити заявку.',
                ],
            ]
        );
    }

    /**
     * Повертає URL сторінки з shortcode PaymentDocs, якщо вона знайдена.
     */
    private function get_payment_docs_page_url(): string {
        $cached_url = get_transient( self::PAYMENT_DOCS_URL_CACHE_KEY );
        if ( is_string( $cached_url ) ) {
            return $cached_url;
        }

        $pages = get_posts(
            [
                'post_type'      => 'page',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'menu_order title',
                'order'          => 'ASC',
            ]
        );

        foreach ( $pages as $page ) {
            if ( $page instanceof \WP_Post && has_shortcode( $page->post_content, 'fstu_payment_docs' ) ) {
                $url = get_permalink( $page );
                $resolved_url = is_string( $url ) ? $url : '';
                set_transient( self::PAYMENT_DOCS_URL_CACHE_KEY, $resolved_url, self::PAYMENT_DOCS_URL_CACHE_TTL );

                return $resolved_url;
            }
        }

        set_transient( self::PAYMENT_DOCS_URL_CACHE_KEY, '', HOUR_IN_SECONDS );

        return '';
    }

    /**
     * Очищає кеш URL сторінки PaymentDocs при зміні сторінок.
     */
    public function clear_payment_docs_page_url_cache(): void {
        delete_transient( self::PAYMENT_DOCS_URL_CACHE_KEY );
    }
}