<?php
namespace FSTU\Dictionaries\Clubs;

/**
 * Контролер відображення модуля "Довідник клубів ФСТУ".
 * Реєструє шорткод [fstu_clubs], підключає скрипти/стилі.
 *
 * Доступ:
 *   Всі відвідувачі   → перегляд списку
 *   userfstu         → перегляд + деталі
 *   userregistrar    → + додавання, редагування
 *   administrator    → + видалення
 *
 * Version:     1.2.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Dictionaries\Clubs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Clubs_List {

	private const ASSET_HANDLE = 'fstu-clubs';
	public const  NONCE_ACTION  = 'fstu_clubs_nonce';
	public const  SHORTCODE      = 'fstu_clubs';
	private const ROUTE_OPTION   = 'fstu_clubs_page_url';
	private const LEGACY_PATH    = '/adm/Club/';

	public function init(): void {
		add_shortcode( self::SHORTCODE, [ $this, 'render_shortcode' ] );
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

		$filtered_url = apply_filters( 'fstu_clubs_module_url', $url, $context );

		return is_string( $filtered_url ) && '' !== $filtered_url ? $filtered_url : $url;
	}

	/**
	 * Рендер шорткоду [fstu_clubs].
	 */
	public function render_shortcode( array $atts = [] ): string {
		$this->enqueue_assets();

		ob_start();
		include FSTU_PLUGIN_DIR . 'views/clubs/main-page.php';
		return (string) ob_get_clean();
	}

	/**
	 * Підключає CSS та JS тільки при наявності шорткоду.
	 * Усі дані передаються через wp_localize_script — жодних inline-скриптів.
	 */
	private function enqueue_assets(): void {
		$ver = FSTU_VERSION;

		wp_enqueue_style(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'css/fstu-clubs.css',
			[], // успадковує CSS-змінні базового CSS
			$ver
		);

		wp_enqueue_script(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'js/fstu-clubs.js',
			[ 'jquery' ],
			$ver,
			true
		);

		// Права поточного користувача (обчислюємо один раз на сервері)
		$user        = wp_get_current_user();
		$roles       = (array) $user->roles;
		$is_admin    = in_array( 'administrator', $roles, true ) || current_user_can( 'manage_options' );
		$is_reg      = in_array( 'userregistrar',  $roles, true );
		$is_logged   = is_user_logged_in();

		wp_localize_script(
			self::ASSET_HANDLE,
			'fstuClubs',
			[
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( self::NONCE_ACTION ),
				'isAdmin'   => $is_admin    ? '1' : '0',
				'isReg'     => ( $is_admin || $is_reg ) ? '1' : '0',
				'isLogged'  => $is_logged   ? '1' : '0',
				'strings'   => [
					'confirmDelete' => 'Ви дійсно хочете ВИДАЛИТИ клуб?',
					'addTitle'      => 'Додавання клубу',
					'editTitle'     => 'Редагування клубу',
					'noData'        => 'Клубів не знайдено.',
					'protocolEmpty' => 'Записи протоколу відсутні.',
					'protocolError' => 'Не вдалося завантажити протокол.',
					'errorGeneric'  => 'Сталася помилка. Спробуйте ще раз.',
					'saving'        => 'Збереження...',
					'deleting'      => 'Видалення...',
				],
			]
		);
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

