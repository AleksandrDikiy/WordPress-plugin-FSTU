<?php
/**
 * Контролер відображення модуля "Довідник видів походів".
 * Реєструє shortcode [fstu_tourtype], підключає assets
 * та передає локалізовані змінні у JS.
 *
 * Version:     1.1.0
 * Date_update: 2026-04-12
 *
 * @package FSTU\Dictionaries\TourType
 */

namespace FSTU\Dictionaries\TourType;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TourType_List {

	private const ASSET_HANDLE = 'fstu-tourtype';

	public const SHORTCODE = 'fstu_tourtype';

	private const ROUTE_OPTION = 'fstu_tourtype_page_url';

	public const NONCE_ACTION = 'fstu_tourtype_nonce';

	private const LEGACY_PATH = '/adm/tourtype/';

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
			FSTU_PLUGIN_URL . 'css/fstu-tourtype.css',
			[],
			FSTU_VERSION
		);

		wp_enqueue_script(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'js/fstu-tourtype.js',
			[ 'jquery' ],
			FSTU_VERSION,
			true
		);

		wp_localize_script(
			self::ASSET_HANDLE,
			'fstuTourTypeL10n',
			[
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( self::NONCE_ACTION ),
				'permissions' => $permissions,
				'defaults'    => [
					'perPage'         => 10,
					'protocolPerPage' => 10,
					'categoryId'      => 0,
				],
				'table'       => [
					'listColspan'     => 6,
					'protocolColspan' => 6,
				],
				'messages'    => [
					'loading'              => __( 'Завантаження...', 'fstu' ),
					'error'                => __( 'Помилка завантаження даних.', 'fstu' ),
					'noResults'            => __( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ),
					'protocolEmpty'        => __( 'Записи протоколу відсутні.', 'fstu' ),
					'protocolError'        => __( 'Не вдалося завантажити протокол.', 'fstu' ),
					'filtersError'         => __( 'Не вдалося завантажити список категорій.', 'fstu' ),
					'confirmDelete'        => __( 'Ви дійсно хочете видалити цей запис?', 'fstu' ),
					'deleteError'          => __( 'Не вдалося видалити запис.', 'fstu' ),
					'saveError'            => __( 'Помилка при збереженні запису.', 'fstu' ),
					'saveSuccess'          => __( 'Запис успішно збережено.', 'fstu' ),
					'reorderError'         => __( 'Не вдалося оновити порядок записів.', 'fstu' ),
					'reorderSuccess'       => __( 'Порядок записів успішно оновлено.', 'fstu' ),
					'reorderUnavailable'   => __( 'У поточній структурі БД сортування drag-and-drop недоступне.', 'fstu' ),
					'reorderCategoryHint'  => __( 'Для зміни порядку спочатку оберіть конкретну категорію.', 'fstu' ),
					'reorderSearchHint'    => __( 'Для зміни порядку очистіть поле пошуку і залиште тільки одну категорію.', 'fstu' ),
					'formAddTitle'         => __( 'Додавання виду походу', 'fstu' ),
					'formEditTitle'        => __( 'Редагування виду походу', 'fstu' ),
					'viewTitle'            => __( 'Перегляд виду походу', 'fstu' ),
					'allCategories'        => __( 'Усі категорії', 'fstu' ),
					'selectCategory'       => __( 'Оберіть категорію', 'fstu' ),
					'close'                => __( 'Закрити', 'fstu' ),
					'edit'                 => __( 'Редагувати', 'fstu' ),
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

		$filtered_url = apply_filters( 'fstu_tourtype_module_url', $url, $context );

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
		include FSTU_PLUGIN_DIR . 'views/tourtype/main-page.php';
		return ob_get_clean();
	}

	/**
	 * Повертає прапорці доступу для поточного користувача.
	 *
	 * @return array<string,bool>
	 */
	private function get_permissions(): array {
		return Capabilities::get_tourtype_permissions();
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

