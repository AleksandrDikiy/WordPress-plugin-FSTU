<?php
namespace FSTU\Dictionaries\RefereeCategory;

/**
 * Контролер відображення модуля "Довідник суддівських категорій".
 * Реєструє shortcode [fstu_referee_category], підключає assets
 * та передає локалізовані змінні у JS.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Dictionaries\RefereeCategory
 */

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RefereeCategory_List {

	private const ASSET_HANDLE = 'fstu-referee-category';
	public const SHORTCODE     = 'fstu_referee_category';
	public const NONCE_ACTION  = 'fstu_referee_category_nonce';
	private const ROUTE_OPTION = 'fstu_referee_category_page_url';
	private const LEGACY_PATH  = '/adm/RefereeCategory/';

	/**
	 * Реєструє shortcode модуля.
	 */
	public function init(): void {
		add_shortcode( self::SHORTCODE, [ $this, 'render_shortcode' ] );
	}

	/**
	 * Рендерить HTML модуля.
	 */
	public function render_shortcode( array $atts = [] ): string {
		$this->enqueue_assets();

		return $this->render_module();
	}

	/**
	 * Підключає CSS та JS модуля.
	 */
	private function enqueue_assets(): void {
		$permissions = $this->get_permissions();

		wp_enqueue_style(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'css/fstu-referee-category.css',
			[],
			FSTU_VERSION
		);

		wp_enqueue_script(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'js/fstu-referee-category.js',
			[ 'jquery' ],
			FSTU_VERSION,
			true
		);

		wp_localize_script(
			self::ASSET_HANDLE,
			'fstuRefereeCategoryL10n',
			[
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( self::NONCE_ACTION ),
				'permissions' => $permissions,
				'defaults'    => [
					'perPage'         => 10,
					'protocolPerPage' => 10,
				],
				'table'       => [
					'listColspan'     => 3,
					'protocolColspan' => 6,
				],
				'messages'    => [
					'loading'         => __( 'Завантаження...', 'fstu' ),
					'error'           => __( 'Помилка завантаження даних.', 'fstu' ),
					'noResults'       => __( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ),
					'protocolEmpty'   => __( 'Записи протоколу відсутні.', 'fstu' ),
					'protocolError'   => __( 'Не вдалося завантажити протокол.', 'fstu' ),
					'confirmDelete'   => __( 'Ви дійсно хочете видалити цей запис?', 'fstu' ),
					'deleteError'     => __( 'Не вдалося видалити запис.', 'fstu' ),
					'saveError'       => __( 'Помилка при збереженні запису.', 'fstu' ),
					'saveSuccess'     => __( 'Запис успішно збережено.', 'fstu' ),
					'reorderError'    => __( 'Не вдалося оновити порядок записів.', 'fstu' ),
					'reorderSuccess'  => __( 'Порядок записів успішно оновлено.', 'fstu' ),
					'formAddTitle'    => __( 'Додавання суддівської категорії', 'fstu' ),
					'formEditTitle'   => __( 'Редагування суддівської категорії', 'fstu' ),
					'viewTitle'       => __( 'Перегляд суддівської категорії', 'fstu' ),
					'close'           => __( 'Закрити', 'fstu' ),
					'edit'            => __( 'Редагувати', 'fstu' ),
					'dependencyDelete'=> __( 'Не вдалося видалити запис, оскільки він уже використовується у даних суддів або сертифікатів.', 'fstu' ),
				],
			]
		);
	}

	/**
	 * Повертає канонічний URL модуля.
	 */
	public static function get_module_url( string $context = 'default' ): string {
		$configured_url = get_option( self::ROUTE_OPTION, '' );
		$url            = self::resolve_configured_url( is_string( $configured_url ) ? $configured_url : '' );

		if ( '' === $url ) {
			$url = self::discover_shortcode_page_url();
		}

		if ( '' === $url ) {
			$url = home_url( self::LEGACY_PATH );
		}

		$filtered_url = apply_filters( 'fstu_referee_category_module_url', $url, $context );

		return is_string( $filtered_url ) && '' !== $filtered_url ? $filtered_url : $url;
	}

	/**
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
		include FSTU_PLUGIN_DIR . 'views/referee-category/main-page.php';
		return (string) ob_get_clean();
	}

	/**
	 * @return array<string,bool>
	 */
	private function get_permissions(): array {
		return Capabilities::get_referee_category_permissions();
	}

	/**
	 * Нормалізує URL, збережений в опції маршруту.
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
	 * Автоматично знаходить опубліковану сторінку з shortcode модуля.
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
		$page_id = (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );

		if ( $page_id <= 0 ) {
			return '';
		}

		$permalink = get_permalink( $page_id );

		return is_string( $permalink ) ? $permalink : '';
	}
}

