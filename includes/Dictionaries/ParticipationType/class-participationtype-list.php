<?php
/**
 * Контролер відображення модуля "Довідник видів участі в заходах".
 * Реєструє shortcode [fstu_participationtype], підключає assets
 * та передає локалізовані змінні у JS.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-12
 *
 * @package FSTU\Dictionaries\ParticipationType
 */

namespace FSTU\Dictionaries\ParticipationType;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ParticipationType_List {

	private const ASSET_HANDLE = 'fstu-participationtype';

	public const SHORTCODE = 'fstu_participationtype';

	private const ROUTE_OPTION = 'fstu_participationtype_page_url';

	public const NONCE_ACTION = 'fstu_participationtype_nonce';

	private const LEGACY_PATH = '/adm/ParticipationType/';

	/**
	 * Реєструє shortcode модуля.
	 */
	public function init(): void {
		add_shortcode( self::SHORTCODE, [ $this, 'render_shortcode' ] );
	}

	/**
	 * Рендерить HTML модуля.
	 *
	 * @param array $atts Атрибути shortcode.
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
			FSTU_PLUGIN_URL . 'css/fstu-participationtype.css',
			[],
			FSTU_VERSION
		);

		wp_enqueue_script(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'js/fstu-participationtype.js',
			[ 'jquery' ],
			FSTU_VERSION,
			true
		);

		wp_localize_script(
			self::ASSET_HANDLE,
			'fstuParticipationTypeL10n',
			[
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( self::NONCE_ACTION ),
				'permissions' => $permissions,
				'defaults'    => [
					'perPage'         => 10,
					'protocolPerPage' => 10,
					'type'            => 1,
				],
				'types'       => [
					[
						'value' => 1,
						'label' => __( 'Змагання / походи', 'fstu' ),
					],
					[
						'value' => 2,
						'label' => __( 'Інше', 'fstu' ),
					],
				],
				'table'       => [
					'listColspan'     => 4,
					'protocolColspan' => 6,
				],
				'messages'    => [
					'loading'        => __( 'Завантаження...', 'fstu' ),
					'error'          => __( 'Помилка завантаження даних.', 'fstu' ),
					'noResults'      => __( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ),
					'protocolEmpty'  => __( 'Записи протоколу відсутні.', 'fstu' ),
					'protocolError'  => __( 'Не вдалося завантажити протокол.', 'fstu' ),
					'confirmDelete'  => __( 'Ви дійсно хочете видалити цей запис?', 'fstu' ),
					'deleteError'    => __( 'Не вдалося видалити запис.', 'fstu' ),
					'saveError'      => __( 'Помилка при збереженні запису.', 'fstu' ),
					'saveSuccess'    => __( 'Запис успішно збережено.', 'fstu' ),
					'reorderError'   => __( 'Не вдалося оновити порядок записів.', 'fstu' ),
					'reorderSuccess' => __( 'Порядок записів успішно оновлено.', 'fstu' ),
					'formAddTitle'   => __( 'Додавання виду участі', 'fstu' ),
					'formEditTitle'  => __( 'Редагування виду участі', 'fstu' ),
					'viewTitle'      => __( 'Перегляд виду участі', 'fstu' ),
					'loginRequired'  => __( 'Щоб працювати з модулем, будь ласка, авторизуйтесь.', 'fstu' ),
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

		$filtered_url = apply_filters( 'fstu_participationtype_module_url', $url, $context );

		return is_string( $filtered_url ) && '' !== $filtered_url ? $filtered_url : $url;
	}

	/**
	 * Повертає дані для views.
	 *
	 * @return array<string,mixed>
	 */
	private function get_view_data(): array {
		$permissions = $this->get_permissions();

		return [
			'permissions'    => $permissions,
			'guest_mode'     => ! is_user_logged_in(),
			'no_access_mode' => is_user_logged_in() && empty( $permissions['canView'] ),
			'guest_login_url' => wp_login_url( self::get_module_url() ),
		];
	}

	/**
	 * Рендерить HTML-каркас модуля.
	 */
	private function render_module(): string {
		$view_data = $this->get_view_data();

		extract( $view_data, EXTR_SKIP );

		ob_start();
		include FSTU_PLUGIN_DIR . 'views/participationtype/main-page.php';
		return ob_get_clean();
	}

	/**
	 * Повертає прапорці доступу для поточного користувача.
	 *
	 * @return array<string,bool>
	 */
	private function get_permissions(): array {
		return Capabilities::get_participation_type_permissions();
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

