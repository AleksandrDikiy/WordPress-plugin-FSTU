<?php
namespace FSTU\Dictionaries\MemberGuidance;

/**
 * Контролер відображення модуля "Довідник посад у керівних органах ФСТУ".
 * Реєструє шорткод [fstu_member_guidance], підключає скрипти/стилі
 * та передає локалізовані дані у JS.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\MemberGuidance
 */

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Member_Guidance_List {

	private const ASSET_HANDLE = 'fstu-member-guidance';

	public const SHORTCODE = 'fstu_member_guidance';

	private const ROUTE_OPTION = 'fstu_member_guidance_page_url';

	public const NONCE_ACTION = 'fstu_member_guidance_nonce';

	private const DEFAULT_TYPEGUIDANCE_ID = 1;

	public function init(): void {
		add_shortcode( self::SHORTCODE, [ $this, 'render_shortcode' ] );
	}

	/**
	 * Рендерить HTML модуля довідника посад у керівних органах.
	 *
	 * @param array<string,mixed> $atts Атрибути шорткоду.
	 */
	public function render_shortcode( array $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'typeguidance_id' => '',
			],
			$atts,
			self::SHORTCODE
		);

		$permissions             = $this->get_permissions();
		$initial_typeguidance_id = $this->resolve_initial_typeguidance_id( $atts );
		$typeguidance_options    = $this->get_typeguidance_options();
		$view_data               = $this->get_view_data( $permissions, $typeguidance_options, $initial_typeguidance_id );

		$this->enqueue_style();

		if ( ! empty( $permissions['canView'] ) ) {
			$this->enqueue_script( $permissions, $typeguidance_options, $initial_typeguidance_id );
		}

		extract( $view_data, EXTR_SKIP );

		ob_start();
		include FSTU_PLUGIN_DIR . 'views/member-guidance/main-page.php';
		return ob_get_clean();
	}

	/**
	 * Повертає канонічний URL модуля Member Guidance.
	 */
	public static function get_module_url( string $context = 'default' ): string {
		$configured_url = get_option( self::ROUTE_OPTION, '' );
		$url            = self::resolve_configured_url( is_string( $configured_url ) ? $configured_url : '' );

		if ( '' === $url ) {
			$url = self::discover_shortcode_page_url();
		}

		$filtered_url = apply_filters( 'fstu_member_guidance_module_url', $url, $context );

		return is_string( $filtered_url ) && '' !== $filtered_url ? $filtered_url : $url;
	}

	/**
	 * Підключає CSS модуля.
	 */
	private function enqueue_style(): void {
		wp_enqueue_style(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'css/fstu-member-guidance.css',
			[],
			FSTU_VERSION
		);
	}

	/**
	 * Підключає JS модуля та передає дані у frontend.
	 *
	 * @param array<string,bool>                  $permissions          Права поточного користувача.
	 * @param array<int,array<string,int|string>> $typeguidance_options Доступні типи керівних органів.
	 */
	private function enqueue_script( array $permissions, array $typeguidance_options, int $initial_typeguidance_id ): void {
		wp_enqueue_script(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'js/fstu-member-guidance.js',
			[ 'jquery' ],
			FSTU_VERSION,
			true
		);

		wp_localize_script(
			self::ASSET_HANDLE,
			'fstuMemberGuidanceL10n',
			[
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( self::NONCE_ACTION ),
				'permissions' => $permissions,
				'initialState' => [
					'typeguidanceId' => $initial_typeguidance_id,
				],
				'options'     => [
					'typeguidance' => $typeguidance_options,
				],
				'table'       => [
					'colspan' => 4,
				],
				'messages'    => [
					'loading'           => __( 'Завантаження...', 'fstu' ),
					'error'             => __( 'Помилка завантаження даних.', 'fstu' ),
					'noAccess'          => __( 'Немає прав для виконання цієї дії.', 'fstu' ),
					'confirmDelete'     => __( 'Ви дійсно хочете видалити цей запис?', 'fstu' ),
					'deleteSuccess'     => __( 'Запис успішно видалений.', 'fstu' ),
					'deleteError'       => __( 'Помилка при видаленні запису.', 'fstu' ),
					'ownershipDenied'   => __( 'Видалення дозволено лише для власних записів.', 'fstu' ),
					'saveSuccess'       => __( 'Запис успішно збережений.', 'fstu' ),
					'saveError'         => __( 'Помилка при збереженні запису.', 'fstu' ),
					'protocolError'     => __( 'Не вдалося завантажити протокол.', 'fstu' ),
					'protocolEmpty'     => __( 'Записи протоколу відсутні.', 'fstu' ),
					'noResults'         => __( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ),
					'formAddTitle'      => __( 'Додавання посади у керівному органі', 'fstu' ),
					'formEditTitle'     => __( 'Редагування посади у керівному органі', 'fstu' ),
					'formViewTitle'     => __( 'Перегляд посади у керівному органі', 'fstu' ),
					'loginPromptTitle'  => __( 'Щоб бачити додаткові функції, будь ласка, авторизуйтесь.', 'fstu' ),
				],
			]
		);
	}

	/**
	 * Готує дані для views.
	 *
	 * @param array<string,bool>                  $permissions          Права поточного користувача.
	 * @param array<int,array<string,int|string>> $typeguidance_options Доступні типи керівних органів.
	 * @return array<string,mixed>
	 */
	private function get_view_data( array $permissions, array $typeguidance_options, int $initial_typeguidance_id ): array {
		$module_url = self::get_module_url( 'login' );

		return [
			'permissions'             => $permissions,
			'guest_mode'              => empty( $permissions['canView'] ),
			'guest_login_url'         => wp_login_url( '' !== $module_url ? $module_url : home_url( '/' ) ),
			'initial_typeguidance_id' => $initial_typeguidance_id,
			'typeguidance_options'    => $typeguidance_options,
		];
	}

	/**
	 * Повертає права поточного користувача для модуля.
	 *
	 * @return array<string,bool>
	 */
	private function get_permissions(): array {
		return Capabilities::get_member_guidance_permissions();
	}

	/**
	 * Визначає стартовий контекст TypeGuidance_ID.
	 *
	 * @param array<string,mixed> $atts Атрибути shortcode.
	 */
	private function resolve_initial_typeguidance_id( array $atts ): int {
		$from_shortcode = absint( $atts['typeguidance_id'] ?? 0 );
		$from_request   = absint( $_GET['typeguidance_id'] ?? 0 );
		$typeguidance_id = $from_shortcode > 0 ? $from_shortcode : $from_request;

		return $typeguidance_id > 0 ? $typeguidance_id : self::DEFAULT_TYPEGUIDANCE_ID;
	}

	/**
	 * Повертає список типів керівних органів.
	 *
	 * @return array<int,array<string,int|string>>
	 */
	private function get_typeguidance_options(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			"SELECT TypeGuidance_ID, TypeGuidance_Name
			 FROM vTypeGuidance
			 ORDER BY TypeGuidance_Order ASC, TypeGuidance_Name ASC",
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return [];
		}

		$options = [];

		foreach ( $rows as $row ) {
			$options[] = [
				'id'   => (int) ( $row['TypeGuidance_ID'] ?? 0 ),
				'name' => (string) ( $row['TypeGuidance_Name'] ?? '' ),
			];
		}

		return $options;
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
	 * Автоматично знаходить фронтенд-сторінку з shortcode [fstu_member_guidance].
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

