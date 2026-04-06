<?php
/**
 * Контролер відображення модуля "Довідник комісій та колегій ФСТУ".
 * Реєструє шорткод [fstu_commission], підключає скрипти/стилі,
 * передає локалізовані змінні у JS.
 *
 * Version:     1.2.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\Commission
 */

namespace FSTU\Dictionaries\Commission;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Commission_List {

	private const ASSET_HANDLE = 'fstu-commission';

	public const SHORTCODE = 'fstu_commission';

	private const ROUTE_OPTION = 'fstu_commission_page_url';

	public const NONCE_ACTION = 'fstu_commission_nonce';

	public function init(): void {
		add_shortcode( self::SHORTCODE, [ $this, 'render_shortcode' ] );
	}

	/**
	 * Рендерить HTML модуля.
	 *
	 * @param array $atts Атрибути шорткоду.
	 * @return string
	 */
	public function render_shortcode( array $atts = [] ): string {
		$this->enqueue_assets();

		return $this->render_module();
	}

	/**
	 * Підключає CSS та JS модуля.
	 */
	private function enqueue_assets(): void {
		$ver         = FSTU_VERSION;
		$permissions = $this->get_permissions();

		wp_enqueue_style(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'css/fstu-commission.css',
			[],
			$ver
		);

		wp_enqueue_script(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'js/fstu-commission.js',
			[ 'jquery' ],
			$ver,
			true
		);

		wp_localize_script(
			self::ASSET_HANDLE,
			'fstuCommissionL10n',
			[
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( self::NONCE_ACTION ),
				'permissions' => $permissions,
				'table'       => [
					'colspan' => 5,
				],
				'messages'    => [
					'loading'         => __( 'Завантаження...', 'fstu' ),
					'error'           => __( 'Помилка завантаження даних.', 'fstu' ),
					'confirmDelete'   => __( 'Ви дійсно хочете видалити цей запис?', 'fstu' ),
					'deleteSuccess'   => __( 'Запис успішно видалений.', 'fstu' ),
					'deleteError'     => __( 'Помилка при видаленні запису.', 'fstu' ),
					'saveSuccess'     => __( 'Запис успішно збережений.', 'fstu' ),
					'saveError'       => __( 'Помилка при збереженні запису.', 'fstu' ),
					'reorderSuccess'  => __( 'Порядок записів успішно оновлено.', 'fstu' ),
					'reorderError'    => __( 'Не вдалося оновити порядок записів.', 'fstu' ),
					'protocolEmpty'   => __( 'Записи протоколу відсутні.', 'fstu' ),
					'protocolError'   => __( 'Не вдалося завантажити протокол.', 'fstu' ),
					'noResults'       => __( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ),
					'formAddTitle'    => __( 'Додавання комісії / колегії', 'fstu' ),
					'formEditTitle'   => __( 'Редагування комісії / колегії', 'fstu' ),
					'viewTitle'       => __( 'Перегляд запису', 'fstu' ),
					'notImplemented'  => __( 'Функціонал каркаса створено. Наступним кроком буде розширення логіки модуля.', 'fstu' ),
				],
			]
		);
	}

	/**
	 * Повертає канонічний URL модуля commission.
	 */
	public static function get_module_url( string $context = 'default' ): string {
		$configured_url = get_option( self::ROUTE_OPTION, '' );
		$url            = self::resolve_configured_url( is_string( $configured_url ) ? $configured_url : '' );

		if ( '' === $url ) {
			$url = self::discover_shortcode_page_url();
		}

		/**
		 * Дозволяє перевизначити канонічний URL модуля commission.
		 *
		 * @param string $url     Поточний URL.
		 * @param string $context Контекст використання URL (hub/admin/frontend).
		 */
		$filtered_url = apply_filters( 'fstu_commission_module_url', $url, $context );

		return is_string( $filtered_url ) && '' !== $filtered_url ? $filtered_url : $url;
	}

	/**
	 * Повертає дані для views.
	 *
	 * @return array<string,mixed>
	 */
	private function get_view_data(): array {
		return [
			'permissions' => $this->get_permissions(),
		];
	}

	/**
	 * Рендерить HTML-каркас модуля.
	 */
	private function render_module(): string {
		$view_data = $this->get_view_data();

		extract( $view_data, EXTR_SKIP );

		ob_start();
		include FSTU_PLUGIN_DIR . 'views/commission/main-page.php';
		return ob_get_clean();
	}

	/**
	 * Повертає прапорці доступу для поточного користувача.
	 *
	 * @return array<string,bool>
	 */
	private function get_permissions(): array {
		return Capabilities::get_commission_permissions();
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
	 * Автоматично знаходить фронтенд-сторінку з shortcode [fstu_commission].
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

