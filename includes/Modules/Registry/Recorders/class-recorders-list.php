<?php
namespace FSTU\Modules\Registry\Recorders;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Контролер відображення модуля «Реєстратори».
 * Реєструє shortcode, підключає assets та локалізує фронтенд-дані.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-11
 *
 * @package FSTU\Modules\UserFstu\Recorders
 */
class Recorders_List {

	private const ASSET_HANDLE = 'fstu-recorders';
	private const ROUTE_OPTION = 'fstu_recorders_page_url';

	public const SHORTCODE    = 'fstu_recorders';
	public const NONCE_ACTION = 'fstu_recorders_nonce';

	public function init(): void {
		add_shortcode( self::SHORTCODE, [ $this, 'render_shortcode' ] );
	}

	/**
	 * Рендерить модуль за shortcode.
	 *
	 * @param array<string,mixed> $atts Атрибути shortcode.
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
		include FSTU_PLUGIN_DIR . 'views/recorders/main-page.php';

		return (string) ob_get_clean();
	}

	/**
	 * Повертає URL сторінки модуля.
	 */
	public static function get_module_url( string $context = 'default' ): string {
		$configured_url = get_option( self::ROUTE_OPTION, '' );
		$url            = self::resolve_configured_url( is_string( $configured_url ) ? $configured_url : '' );

		if ( '' === $url ) {
			$url = self::discover_shortcode_page_url();
		}

		$filtered_url = apply_filters( 'fstu_recorders_module_url', $url, $context );

		return is_string( $filtered_url ) && '' !== $filtered_url ? $filtered_url : $url;
	}

	private function enqueue_style(): void {
		wp_enqueue_style(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'css/fstu-recorders.css',
			[],
			$this->get_asset_version( FSTU_PLUGIN_DIR . 'css/fstu-recorders.css' )
		);
	}

	/**
	 * @param array<string,bool> $permissions Права поточного користувача.
	 */
	private function enqueue_script( array $permissions ): void {
		wp_enqueue_script(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'js/fstu-recorders.js',
			[ 'jquery' ],
			$this->get_asset_version( FSTU_PLUGIN_DIR . 'js/fstu-recorders.js' ),
			true
		);

		wp_localize_script(
			self::ASSET_HANDLE,
			'fstuRecordersL10n',
			[
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( self::NONCE_ACTION ),
				'permissions' => $permissions,
				'defaults'    => [
					'perPage'         => 10,
					'protocolPerPage' => 10,
				],
				'table'       => [
					'colspan'         => 6,
					'protocolColspan' => 6,
				],
				'actions'     => [
					'getList'      => 'fstu_recorders_get_list',
					'getSingle'    => 'fstu_recorders_get_single',
					'getUnits'     => 'fstu_recorders_get_units',
					'getCandidates'=> 'fstu_recorders_get_candidates',
					'create'       => 'fstu_recorders_create',
					'update'       => 'fstu_recorders_update',
					'delete'       => 'fstu_recorders_delete',
					'getProtocol'  => 'fstu_recorders_get_protocol',
				],
				'messages'    => [
					'loading'            => __( 'Завантаження...', 'fstu' ),
					'error'              => __( 'Помилка завантаження даних.', 'fstu' ),
					'emptyList'          => __( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ),
					'emptyProtocol'      => __( 'Записи протоколу відсутні.', 'fstu' ),
					'protocolError'      => __( 'Не вдалося завантажити протокол.', 'fstu' ),
					'unitsError'         => __( 'Не вдалося завантажити перелік осередків.', 'fstu' ),
					'candidatesError'    => __( 'Не вдалося завантажити список кандидатів.', 'fstu' ),
					'confirmDelete'      => __( 'Ви дійсно хочете видалити це призначення?', 'fstu' ),
					'saveSuccess'        => __( 'Запис успішно збережено.', 'fstu' ),
					'saveError'          => __( 'Помилка при збереженні запису.', 'fstu' ),
					'deleteError'        => __( 'Не вдалося видалити запис.', 'fstu' ),
					'formAddTitle'       => __( 'Додавання реєстратора', 'fstu' ),
					'formEditTitle'      => __( 'Редагування призначення реєстратора', 'fstu' ),
					'viewTitle'          => __( 'Перегляд призначення', 'fstu' ),
					'unitFilterLabel'    => __( 'Осередок ФСТУ', 'fstu' ),
					'allUnits'           => __( 'Усі осередки', 'fstu' ),
					'candidateHint'      => __( 'Введіть щонайменше 2 символи для пошуку.', 'fstu' ),
					'candidateEmpty'     => __( 'Кандидатів не знайдено.', 'fstu' ),
					'candidateRequired'  => __( 'Оберіть реєстратора зі списку підказок.', 'fstu' ),
				],
			]
		);
	}

	/**
	 * @param array<string,bool> $permissions
	 * @return array<string,mixed>
	 */
	private function get_view_data( array $permissions ): array {
		$module_url = self::get_module_url( 'login' );

		return [
			'permissions'     => $permissions,
			'guest_mode'      => ! is_user_logged_in() && empty( $permissions['canView'] ),
			'no_access_mode'  => is_user_logged_in() && empty( $permissions['canView'] ),
			'guest_login_url' => wp_login_url( '' !== $module_url ? $module_url : home_url( '/' ) ),
		];
	}

	/**
	 * @return array<string,bool>
	 */
	private function get_permissions(): array {
		return Capabilities::get_recorders_permissions();
	}

	private function get_asset_version( string $file_path ): string {
		if ( file_exists( $file_path ) ) {
			$filemtime = filemtime( $file_path );

			if ( false !== $filemtime ) {
				return (string) $filemtime;
			}
		}

		return (string) FSTU_VERSION;
	}

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

