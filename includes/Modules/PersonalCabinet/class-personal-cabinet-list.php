<?php
namespace FSTU\Modules\PersonalCabinet;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Контролер відображення модуля «Особистий кабінет ФСТУ».
 *
 * Version:     1.3.0
 * Date_update: 2026-04-09
 *
 * @package FSTU\Modules\PersonalCabinet
 */
class Personal_Cabinet_List {

	private const ASSET_HANDLE = 'fstu-personal-cabinet';
	private const ROUTE_OPTION = 'fstu_personal_cabinet_page_url';
	private const QUERY_VAR     = 'ViewID';

	public const SHORTCODE    = 'fstu_personal_cabinet';
	public const NONCE_ACTION = 'fstu_personal_cabinet_nonce';

	private ?Personal_Cabinet_Service $service = null;

	public function init(): void {
		add_shortcode( self::SHORTCODE, [ $this, 'render_shortcode' ] );
	}

	/**
	 * @param array<string,mixed> $atts
	 */
	public function render_shortcode( array $atts = [] ): string {
		unset( $atts );

		$profile_request = $this->resolve_profile_request();
		$profile_user_id = $profile_request['user_id'];
		$permissions     = $this->get_permissions( $profile_user_id );
		$view_data       = $this->get_view_data( $permissions, $profile_user_id, ! empty( $profile_request['is_explicit'] ) );

		$this->enqueue_style();

		if ( ! $view_data['guest_mode'] && ! $view_data['no_access_mode'] && ! $view_data['profile_not_found_mode'] ) {
			$this->enqueue_script( $permissions );
		}

		extract( $view_data, EXTR_SKIP );

		ob_start();
		include FSTU_PLUGIN_DIR . 'views/personal-cabinet/main-page.php';

		return (string) ob_get_clean();
	}

	public static function get_module_url( string $context = 'default' ): string {
		$configured_url = get_option( self::ROUTE_OPTION, '' );
		$url            = self::resolve_configured_url( is_string( $configured_url ) ? $configured_url : '' );

		if ( '' === $url ) {
			$url = self::discover_shortcode_page_url();
		}

		$filtered_url = apply_filters( 'fstu_personal_cabinet_module_url', $url, $context );

		return is_string( $filtered_url ) && '' !== $filtered_url ? $filtered_url : $url;
	}

	private function enqueue_style(): void {
		$style_path = FSTU_PLUGIN_DIR . 'css/fstu-personal-cabinet.css';

		wp_enqueue_style(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'css/fstu-personal-cabinet.css',
			[],
			$this->get_asset_version( $style_path )
		);
	}

	/**
	 * @param array<string,bool> $permissions
	 */
	private function enqueue_script( array $permissions ): void {
		$module_url = self::get_module_url( 'login' );
		$login_url  = wp_login_url( '' !== $module_url ? $module_url : home_url( '/' ) );
		$profile_request = $this->resolve_profile_request();

		wp_enqueue_script(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'js/fstu-personal-cabinet.js',
			[ 'jquery' ],
			$this->get_asset_version( FSTU_PLUGIN_DIR . 'js/fstu-personal-cabinet.js' ),
			true
		);

		wp_localize_script(
			self::ASSET_HANDLE,
			'fstuPersonalCabinetL10n',
			[
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( self::NONCE_ACTION ),
				'moduleUrl'   => self::get_module_url(),
				'loginUrl'    => $login_url,
				'profileUserId' => $profile_request['user_id'],
				'permissions' => $permissions,
				'defaults'    => [
					'protocolPerPage' => Personal_Cabinet_Protocol_Service::get_default_per_page(),
					'currentYear'     => (int) current_time( 'Y' ),
				],
				'table'       => [
					'protocolColspan' => 6,
				],
				'actions'     => [
					'getProfile'         => 'fstu_personal_cabinet_get_profile',
					'getProtocol'        => 'fstu_personal_cabinet_get_protocol',
					'getPortmonePayload' => 'fstu_personal_cabinet_get_portmone_payload',
					'uploadDuesReceipt'  => 'fstu_personal_cabinet_upload_dues_receipt',
				],
				'messages'    => [
					'loading'       => __( 'Завантаження...', 'fstu' ),
					'profileError'  => __( 'Не вдалося завантажити дані особистого кабінету.', 'fstu' ),
					'profileNotFound' => __( 'Профіль користувача не знайдено.', 'fstu' ),
					'protocolError' => __( 'Не вдалося завантажити протокол модуля.', 'fstu' ),
					'emptyProtocol' => __( 'Записи протоколу відсутні.', 'fstu' ),
					'paymentPreparing' => __( 'Готуємо безпечний платіжний payload Portmone...', 'fstu' ),
					'paymentRedirecting' => __( 'Перенаправляємо на сторінку Portmone...', 'fstu' ),
					'paymentError' => __( 'Не вдалося підготувати онлайн-оплату внеску.', 'fstu' ),
					'duesModalTitle' => __( 'Додати квитанцію про оплату', 'fstu' ),
					'duesSaveSuccess' => __( 'Квитанцію успішно збережено.', 'fstu' ),
					'duesSaveError' => __( 'Не вдалося зберегти квитанцію.', 'fstu' ),
				],
			]
		);
	}

	/**
	 * @param array<string,bool> $permissions
	 * @return array<string,mixed>
	 */
	private function get_view_data( array $permissions, int $profile_user_id, bool $is_explicit_request ): array {
		$module_url     = self::get_module_url( 'login' );
		$is_logged_in   = is_user_logged_in();
		$profile_exists = $profile_user_id > 0 && $this->profile_exists( $profile_user_id );
		$can_view       = ! empty( $permissions['canView'] ) && $profile_exists;
		$guest_mode     = ! $is_logged_in && ! $is_explicit_request;
		$profile_not_found_mode = $is_explicit_request && ! $profile_exists;
		$no_access_mode = ! $guest_mode && ! $profile_not_found_mode && ! $can_view;
		$current_user_id = get_current_user_id();

		return [
			'permissions'      => $permissions,
			'guest_mode'       => $guest_mode,
			'no_access_mode'   => $no_access_mode,
			'profile_not_found_mode' => $profile_not_found_mode,
			'guest_login_url'  => wp_login_url( '' !== $module_url ? $module_url : home_url( '/' ) ),
			'module_url'       => self::get_module_url(),
			'current_user_id'  => $current_user_id,
			'viewed_user_id'   => $profile_user_id,
			'is_own_profile'   => $profile_user_id > 0 && $profile_user_id === $current_user_id,
			'payment_notice'   => $this->get_payment_notice(),
			'profile_summary'  => $can_view ? $this->get_service()->get_initial_profile_summary( $profile_user_id, $permissions ) : [],
		];
	}

	/**
	 * @return array<string,string>
	 */
	private function get_payment_notice(): array {
		$status = sanitize_key( wp_unslash( $_GET['payment_status'] ?? '' ) );
		$year   = absint( $_GET['payment_year'] ?? 0 );

		if ( '' === $status ) {
			return [];
		}

		$year_text = $year > 0 ? ' ' . $year . ' ' : ' ';

		$map = [
			'success' => [
				'type'    => 'success',
				'message' => sprintf( __( 'Portmone-оплату за%sрік успішно зафіксовано.', 'fstu' ), $year_text ),
			],
			'failure' => [
				'type'    => 'warning',
				'message' => sprintf( __( 'Portmone-оплату за%sрік не завершено.', 'fstu' ), $year_text ),
			],
			'error'   => [
				'type'    => 'error',
				'message' => __( 'Не вдалося обробити відповідь платіжного шлюзу Portmone.', 'fstu' ),
			],
		];

		return isset( $map[ $status ] ) ? $map[ $status ] : [];
	}

	/**
	 * @return array<string,bool>
	 */
	private function get_permissions( int $profile_user_id ): array {
		return Capabilities::get_personal_cabinet_permissions( $profile_user_id );
	}

	/**
	 * @return array{user_id:int,is_explicit:bool}
	 */
	private function resolve_profile_request(): array {
		$requested_user_id = absint( $_GET[ self::QUERY_VAR ] ?? 0 );

		if ( $requested_user_id > 0 ) {
			return [
				'user_id'     => $requested_user_id,
				'is_explicit' => true,
			];
		}

		return [
			'user_id'     => get_current_user_id(),
			'is_explicit' => false,
		];
	}

	private function profile_exists( int $user_id ): bool {
		return $user_id > 0 && get_userdata( $user_id ) instanceof \WP_User;
	}

	private function get_service(): Personal_Cabinet_Service {
		if ( null === $this->service ) {
			$this->service = new Personal_Cabinet_Service();
		}

		return $this->service;
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

