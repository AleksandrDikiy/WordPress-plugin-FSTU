<?php
/**
 * Контролер відображення модуля "Довідник областей".
 * Реєструє шорткод [fstu_region], підключає скрипти/стилі,
 * передає локалізовані змінні у JS.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\Region
 */

namespace FSTU\Dictionaries\Region;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Region_List {

	private const ASSET_HANDLE = 'fstu-region';

	public const SHORTCODE = 'fstu_region';

	private const ROUTE_OPTION = 'fstu_region_page_url';

	public const NONCE_ACTION = 'fstu_region_nonce';

	public function init(): void {
		add_shortcode( self::SHORTCODE, [ $this, 'render_shortcode' ] );
	}

	/**
	 * Рендерить HTML модуля довідника областей.
	 *
	 * @param array $atts Атрибути шорткоду.
	 */
	public function render_shortcode( array $atts = [] ): string {
		unset( $atts );

		$permissions = $this->get_permissions();
		$view_data   = $this->get_view_data( $permissions );

		$this->enqueue_style();

		if ( ! empty( $permissions['canView'] ) ) {
			$this->enqueue_script( $permissions );
		}

		extract( $view_data, EXTR_SKIP );

		ob_start();
		include FSTU_PLUGIN_DIR . 'views/region/main-page.php';
		return ob_get_clean();
	}

	/**
	 * Повертає канонічний URL модуля Region.
	 */
	public static function get_module_url( string $context = 'default' ): string {
		$configured_url = get_option( self::ROUTE_OPTION, '' );
		$url            = self::resolve_configured_url( is_string( $configured_url ) ? $configured_url : '' );

		if ( '' === $url ) {
			$url = self::discover_shortcode_page_url();
		}

		$filtered_url = apply_filters( 'fstu_region_module_url', $url, $context );

		return is_string( $filtered_url ) && '' !== $filtered_url ? $filtered_url : $url;
	}

	/**
	 * Підключає CSS модуля.
	 */
	private function enqueue_style(): void {
		wp_enqueue_style(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'css/fstu-region.css',
			[],
			FSTU_VERSION
		);
	}

	/**
	 * Підключає JS модуля та передає дані у frontend.
	 *
	 * @param array<string,bool> $permissions Права поточного користувача.
	 */
	private function enqueue_script( array $permissions ): void {
		wp_enqueue_script(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'js/fstu-region.js',
			[ 'jquery' ],
			FSTU_VERSION,
			true
		);

		wp_localize_script(
			self::ASSET_HANDLE,
			'fstuRegionL10n',
			[
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( self::NONCE_ACTION ),
				'permissions' => $permissions,
				'table'       => [
					'colspan' => 5,
				],
				'messages'    => [
					'loading'          => __( 'Завантаження...', 'fstu' ),
					'error'            => __( 'Помилка завантаження даних.', 'fstu' ),
					'noAccess'         => __( 'Немає прав для виконання цієї дії.', 'fstu' ),
					'confirmDelete'    => __( 'Ви дійсно хочете видалити цю область?', 'fstu' ),
					'deleteSuccess'    => __( 'Область успішно видалено.', 'fstu' ),
					'deleteError'      => __( 'Помилка при видаленні області.', 'fstu' ),
					'saveSuccess'      => __( 'Область успішно збережено.', 'fstu' ),
					'saveError'        => __( 'Помилка при збереженні області.', 'fstu' ),
					'protocolError'    => __( 'Не вдалося завантажити протокол.', 'fstu' ),
					'protocolEmpty'    => __( 'Записи протоколу відсутні.', 'fstu' ),
					'noResults'        => __( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ),
					'formAddTitle'     => __( 'Додавання області', 'fstu' ),
					'formEditTitle'    => __( 'Редагування області', 'fstu' ),
					'formViewTitle'    => __( 'Перегляд області', 'fstu' ),
					'blockedDelete'    => __( 'Область неможливо видалити, оскільки вона використовується в інших даних.', 'fstu' ),
					'loginPromptTitle' => __( 'Щоб працювати з довідником областей, будь ласка, авторизуйтесь.', 'fstu' ),
				],
			]
		);
	}

	/**
	 * Готує дані для views.
	 *
	 * @param array<string,bool> $permissions Права поточного користувача.
	 * @return array<string,mixed>
	 */
	private function get_view_data( array $permissions ): array {
		$module_url     = self::get_module_url( 'login' );
		$is_logged_in   = is_user_logged_in();
		$can_view       = ! empty( $permissions['canView'] );
		$guest_mode     = ! $is_logged_in && ! $can_view;
		$no_access_mode = $is_logged_in && ! $can_view;

		return [
			'permissions'     => $permissions,
			'guest_mode'      => $guest_mode,
			'no_access_mode'  => $no_access_mode,
			'guest_login_url' => wp_login_url( '' !== $module_url ? $module_url : home_url( '/' ) ),
		];
	}

	/**
	 * Повертає права поточного користувача для модуля.
	 *
	 * @return array<string,bool>
	 */
	private function get_permissions(): array {
		return Capabilities::get_region_permissions();
	}

	/**
	 * Нормалізує URL сторінки модуля, збережений в опції.
	 */
	private static function resolve_configured_url( string $configured_url ): string {
		$configured_url = trim( $configured_url );

		if ( '' === $configured_url ) {
			return '';
		}

		if ( str_starts_with( $configured_url, '/' ) ) {
			return home_url( $configured_url );
		}

		return wp_http_validate_url( $configured_url ) ? $configured_url : '';
	}

	/**
	 * Автоматично знаходить фронтенд-сторінку з shortcode [fstu_region].
	 */
	private static function discover_shortcode_page_url(): string {
		global $wpdb;

		$shortcode_like = '%' . $wpdb->esc_like( '[' . self::SHORTCODE ) . '%';
		$post_types     = [ 'page', 'post' ];
		$placeholders   = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );

		$sql = "SELECT ID
			FROM {$wpdb->posts}
			WHERE post_status = 'publish'
				AND post_type IN ({$placeholders})
				AND post_content LIKE %s
			ORDER BY CASE WHEN post_type = 'page' THEN 0 ELSE 1 END ASC, menu_order ASC, ID ASC
			LIMIT 1";

		$params  = array_merge( $post_types, [ $shortcode_like ] );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$page_id = (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );

		if ( $page_id <= 0 ) {
			return '';
		}

		$permalink = get_permalink( $page_id );

		return is_string( $permalink ) ? $permalink : '';
	}
}

