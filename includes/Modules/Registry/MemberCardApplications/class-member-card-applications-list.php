<?php
namespace FSTU\Modules\Registry\MemberCardApplications;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Контролер відображення модуля «Посвідчення членів ФСТУ».
 * Реєструє shortcode, підключає assets та локалізує фронтенд-дані.
 *
 * Version:     1.5.0
 * Date_update: 2026-04-10
 *
 * @package FSTU\Modules\UserFstu\MemberCardApplications
 */
class Member_Card_Applications_List {

	private const ASSET_HANDLE = 'fstu-member-card-applications';
	private const ROUTE_OPTION = 'fstu_member_card_applications_page_url';
	private const INTENT_QUERY_KEY = 'fstu_intent';
	private const PERSONAL_INTENT_VALUE = 'personal_member_card';
	private const REGISTRY_INTENT_VALUE = 'registry_member_card';
	private const USER_QUERY_KEY = 'fstu_user_id';
	private const ACTION_QUERY_KEY = 'fstu_member_card_action';
	private const MEMBER_CARD_QUERY_KEY = 'member_card_id';
	private const PROFILE_USER_QUERY_KEY = 'pc_user_id';
	private const RETURN_URL_QUERY_KEY = 'pc_return';

	public const SHORTCODE    = 'fstu_member_card_applications';
	public const NONCE_ACTION = 'fstu_member_card_applications_nonce';

	private ?\FSTU\Modules\Registry\MemberCardApplications\Member_Card_Applications_Repository $repository = null;

	public function init(): void {
		add_shortcode( self::SHORTCODE, [ $this, 'render_shortcode' ] );
	}

	/**
	 * @param array<string,mixed> $atts
	 */
	public function render_shortcode( array $atts = [] ): string {
		unset( $atts );

		$permissions = $this->get_permissions();
		$can_render  = ! empty( $permissions['canView'] ) || ! empty( $permissions['canSelfService'] ) || ! empty( $permissions['canReissue'] ) || ! empty( $permissions['canUpdatePhoto'] );
		$view_data   = $this->get_view_data( $permissions );

		$this->enqueue_style();

		if ( $can_render ) {
			$this->enqueue_script( $permissions );
		}

		extract( $view_data, EXTR_SKIP );

		ob_start();
		include FSTU_PLUGIN_DIR . 'views/member-card-applications/main-page.php';

		return (string) ob_get_clean();
	}

	public static function get_module_url( string $context = 'default' ): string {
		$configured_url = get_option( self::ROUTE_OPTION, '' );
		$url            = self::resolve_configured_url( is_string( $configured_url ) ? $configured_url : '' );

		if ( '' === $url ) {
			$url = self::discover_shortcode_page_url();
		}

		$filtered_url = apply_filters( 'fstu_member_card_applications_module_url', $url, $context );

		return is_string( $filtered_url ) && '' !== $filtered_url ? $filtered_url : $url;
	}

	private function enqueue_style(): void {
		$style_path = FSTU_PLUGIN_DIR . 'css/fstu-member-card-applications.css';

		wp_enqueue_style(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'css/fstu-member-card-applications.css',
			[],
			$this->get_asset_version( $style_path )
		);
	}

	/**
	 * @param array<string,bool> $permissions
	 */
	private function enqueue_script( array $permissions ): void {
		$bootstrap = $this->get_bootstrap_payload( $permissions );
		$current_user_defaults = $this->get_user_defaults();
		$module_url = self::get_module_url( 'login' );
		$login_url  = wp_login_url( '' !== $module_url ? $module_url : home_url( '/' ) );

		wp_enqueue_script(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'js/fstu-member-card-applications.js',
			[ 'jquery' ],
			$this->get_asset_version( FSTU_PLUGIN_DIR . 'js/fstu-member-card-applications.js' ),
			true
		);

		wp_localize_script(
			self::ASSET_HANDLE,
			'fstuMemberCardApplicationsL10n',
			[
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( self::NONCE_ACTION ),
				'loginUrl'    => $login_url,
				'moduleUrl'   => self::get_module_url(),
				'bootstrap'   => $bootstrap,
				'permissions' => $permissions,
				'currentUser' => $current_user_defaults,
				'defaults'    => [
					'perPage'         => 10,
					'protocolPerPage' => 10,
				],
				'table'       => [
					'colspan'         => 8,
					'protocolColspan' => 6,
				],
				'actions'     => [
					'getList'      => 'fstu_member_card_applications_get_list',
					'getSingle'    => 'fstu_member_card_applications_get_single',
					'getDictionaries' => 'fstu_member_card_applications_get_dictionaries',
					'getProtocol'  => 'fstu_member_card_applications_get_protocol',
					'create'       => 'fstu_member_card_applications_create',
					'update'       => 'fstu_member_card_applications_update',
					'reissue'      => 'fstu_member_card_applications_reissue',
					'updatePhoto'  => 'fstu_member_card_applications_update_photo',
					'delete'       => 'fstu_member_card_applications_delete',
				],
				'messages'     => [
					'loading'       => __( 'Завантаження...', 'fstu' ),
					'error'         => __( 'Не вдалося завантажити дані посвідчень.', 'fstu' ),
					'redirecting'   => __( 'Операцію завершено. Повертаємо вас до попереднього модуля...', 'fstu' ),
					'emptyList'     => __( 'Записів не знайдено.', 'fstu' ),
					'emptyProtocol' => __( 'Записи протоколу відсутні.', 'fstu' ),
					'viewError'     => __( 'Не вдалося завантажити картку посвідчення.', 'fstu' ),
					'protocolError' => __( 'Не вдалося завантажити протокол.', 'fstu' ),
					'filtersError'  => __( 'Не вдалося завантажити довідники модуля.', 'fstu' ),
					'notImplemented' => __( 'Функціонал буде реалізовано на наступному кроці.', 'fstu' ),
					'formTitle'     => __( 'Посвідчення члена ФСТУ', 'fstu' ),
					'viewTitle'     => __( 'Картка посвідчення', 'fstu' ),
					'guestPrompt'   => __( 'Щоб працювати з модулем посвідчень, будь ласка, авторизуйтесь.', 'fstu' ),
				],
			]
		);
	}

	/**
	 * @param array<string,bool> $permissions
	 * @return array<string,mixed>
	 */
	private function get_view_data( array $permissions ): array {
		$is_logged_in      = is_user_logged_in();
		$can_view          = ! empty( $permissions['canView'] );
		$can_self_service  = ! empty( $permissions['canSelfService'] );
		$can_reissue       = ! empty( $permissions['canReissue'] );
		$can_update_photo  = ! empty( $permissions['canUpdatePhoto'] );
		$can_owner_flow    = $can_self_service || $can_reissue || $can_update_photo;
		$guest_mode        = ! $is_logged_in && ! $can_view && ! $can_owner_flow;
		$no_access_mode    = $is_logged_in && ! $can_view && ! $can_owner_flow;
		$self_service_only = $can_owner_flow && ! $can_view;
		$bootstrap         = $this->get_bootstrap_payload( $permissions );
		$filters           = ( $can_view || $can_owner_flow ) ? $this->get_repository()->get_filter_options() : [
			'regions' => [],
			'statuses' => [],
			'types' => [],
		];

		return [
			'permissions'            => $permissions,
			'guest_mode'             => $guest_mode,
			'no_access_mode'         => $no_access_mode,
			'self_service_only'      => $self_service_only,
			'guest_login_url'        => wp_login_url( '' !== self::get_module_url( 'login' ) ? self::get_module_url( 'login' ) : home_url( '/' ) ),
			'bootstrap_notice'       => isset( $bootstrap['notice'] ) ? (string) $bootstrap['notice'] : '',
			'bootstrap_return_url'   => isset( $bootstrap['returnUrl'] ) ? (string) $bootstrap['returnUrl'] : '',
			'bootstrap_return_label' => isset( $bootstrap['returnLabel'] ) ? (string) $bootstrap['returnLabel'] : '',
			'bootstrap_auto_open'    => ! empty( $bootstrap['autoOpen'] ),
			'self_service_has_member_card' => ! empty( $bootstrap['hasMemberCard'] ),
			'self_service_member_card_id'  => isset( $bootstrap['memberCardId'] ) ? (int) $bootstrap['memberCardId'] : 0,
			'self_service_card_number'     => isset( $bootstrap['cardNumber'] ) ? (string) $bootstrap['cardNumber'] : '',
			'filter_regions'         => isset( $filters['regions'] ) && is_array( $filters['regions'] ) ? $filters['regions'] : [],
			'filter_statuses'        => isset( $filters['statuses'] ) && is_array( $filters['statuses'] ) ? $filters['statuses'] : [],
			'filter_types'           => isset( $filters['types'] ) && is_array( $filters['types'] ) ? $filters['types'] : [],
		];
	}

	/**
	 * @return array<string,bool>
	 */
	private function get_permissions(): array {
		return Capabilities::get_member_card_applications_permissions();
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

	/**
	 * @param array<string,bool> $permissions
	 * @return array<string,mixed>
	 */
	private function get_bootstrap_payload( array $permissions ): array {
		$intent = sanitize_key( wp_unslash( $_GET[ self::INTENT_QUERY_KEY ] ?? '' ) );
		$default_payload = [
			'autoOpen'     => false,
			'intent'       => '',
			'mode'         => '',
			'action'       => '',
			'userId'       => 0,
			'memberCardId' => 0,
			'hasMemberCard' => false,
			'cardNumber'   => '',
			'notice'       => '',
			'returnUrl'    => '',
			'returnLabel'  => '',
			'postSuccessAction' => 'stay',
			'userDefaults' => [],
		];

		if ( ! in_array( $intent, [ self::PERSONAL_INTENT_VALUE, self::REGISTRY_INTENT_VALUE ], true ) ) {
			return $default_payload;
		}

		$user_id = absint( wp_unslash( $_GET[ self::USER_QUERY_KEY ] ?? 0 ) );
		if ( $user_id <= 0 ) {
			$user_id = absint( wp_unslash( $_GET[ self::PROFILE_USER_QUERY_KEY ] ?? 0 ) );
		}

		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}

		if ( $user_id <= 0 ) {
			return $default_payload;
		}

		$current_user_id = get_current_user_id();
		$is_owner        = $current_user_id > 0 && $current_user_id === $user_id;
		$can_staff_open  = ! empty( $permissions['canManage'] ) || ! empty( $permissions['canView'] );
		$can_owner_open  = $is_owner && ( ! empty( $permissions['canSelfService'] ) || ! empty( $permissions['canReissue'] ) || ! empty( $permissions['canUpdatePhoto'] ) );

		if ( ! $can_staff_open && ! $can_owner_open ) {
			return $default_payload;
		}

		$return_url = esc_url_raw( wp_unslash( $_GET[ self::RETURN_URL_QUERY_KEY ] ?? '' ) );
		$action     = sanitize_key( wp_unslash( $_GET[ self::ACTION_QUERY_KEY ] ?? '' ) );
		$latest     = $this->get_repository()->get_latest_member_card_by_user_id( $user_id );
		$member_card_id = 0;

		if ( is_array( $latest ) ) {
			$member_card_id = absint( $latest['UserMemberCard_ID'] ?? 0 );
		}

		$requested_member_card_id = absint( wp_unslash( $_GET[ self::MEMBER_CARD_QUERY_KEY ] ?? 0 ) );
		if ( $requested_member_card_id > 0 ) {
			$requested_member_card = $this->get_repository()->get_raw_member_card_by_id( $requested_member_card_id );

			if ( is_array( $requested_member_card ) && absint( $requested_member_card['User_ID'] ?? 0 ) === $user_id ) {
				$member_card_id = $requested_member_card_id;
			}
		}

		$has_member_card = $member_card_id > 0;
		$allowed_actions = [ 'create', 'view', 'reissue', 'photo' ];
		if ( ! in_array( $action, $allowed_actions, true ) ) {
			$action = $has_member_card ? 'view' : 'create';
		}

		$can_create = ! empty( $permissions['canManage'] ) || ( $is_owner && ! empty( $permissions['canSelfService'] ) );
		$can_view_single = $has_member_card && ( $can_staff_open || $can_owner_open );
		$can_reissue = $has_member_card && ( ! empty( $permissions['canManage'] ) || ( $is_owner && ! empty( $permissions['canReissue'] ) ) );
		$can_photo = $has_member_card && ( ! empty( $permissions['canManage'] ) || ( $is_owner && ! empty( $permissions['canUpdatePhoto'] ) ) );

		if ( 'create' === $action && ! $can_create ) {
			$action = $can_view_single ? 'view' : '';
		}

		if ( 'view' === $action && ! $can_view_single ) {
			$action = $can_create ? 'create' : '';
		}

		if ( 'reissue' === $action && ! $can_reissue ) {
			$action = $can_view_single ? 'view' : ( $can_create ? 'create' : '' );
		}

		if ( 'photo' === $action && ! $can_photo ) {
			$action = $can_view_single ? 'view' : ( $can_create ? 'create' : '' );
		}

		if ( '' === $action ) {
			return $default_payload;
		}

		$notice = self::PERSONAL_INTENT_VALUE === $intent
			? __( 'Запущено сценарій посвідчення з особистого кабінету.', 'fstu' )
			: __( 'Запущено сценарій посвідчення з реєстру членів ФСТУ.', 'fstu' );

		$return_label = self::PERSONAL_INTENT_VALUE === $intent
			? __( 'ПОВЕРНУТИСЬ ДО КАБІНЕТУ', 'fstu' )
			: __( 'ПОВЕРНУТИСЬ ДО РЕЄСТРУ', 'fstu' );

		$post_success_action = ( self::PERSONAL_INTENT_VALUE === $intent && '' !== $return_url && in_array( $action, [ 'create', 'reissue', 'photo' ], true ) )
			? 'redirect'
			: 'stay';

		$notice_map = [
			'create'  => self::PERSONAL_INTENT_VALUE === $intent
				? __( 'Відкрито форму оформлення посвідчення з особистого кабінету.', 'fstu' )
				: __( 'Відкрито форму посвідчення для обраного користувача з реєстру.', 'fstu' ),
			'view'    => __( 'Відкрито картку посвідчення для швидкого перегляду.', 'fstu' ),
			'reissue' => __( 'Відкрито сценарій перевипуску посвідчення.', 'fstu' ),
			'photo'   => __( 'Відкрито сценарій оновлення фото посвідчення.', 'fstu' ),
		];

		return [
			'autoOpen'      => $user_id > 0,
			'intent'        => $intent,
			'mode'          => in_array( $action, [ 'create', 'reissue', 'photo' ], true ) ? $action : '',
			'action'        => $action,
			'userId'        => $user_id,
			'memberCardId'  => $member_card_id,
			'hasMemberCard' => $has_member_card,
			'cardNumber'    => is_array( $latest ) && isset( $latest['UserMemberCard_Number'] ) ? (string) $latest['UserMemberCard_Number'] : '',
			'notice'        => isset( $notice_map[ $action ] ) ? (string) $notice_map[ $action ] : $notice,
			'returnUrl'     => $return_url,
			'returnLabel'   => $return_label,
			'postSuccessAction' => $post_success_action,
			'userDefaults'  => $this->get_user_defaults( $user_id ),
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	private function get_user_defaults( int $user_id = 0 ): array {
		$user_id = $user_id > 0 ? $user_id : get_current_user_id();
		$user    = $user_id > 0 ? get_userdata( $user_id ) : false;

		if ( ! ( $user instanceof \WP_User ) || $user->ID <= 0 ) {
			return [
				'userId'     => 0,
				'lastName'   => '',
				'firstName'  => '',
				'patronymic' => '',
				'birthDate'  => '',
				'email'      => '',
							'photoUrl'   => '',
				'phoneMobile' => '',
				'phone2'     => '',
			];
		}

		return [
			'userId'     => $user->ID,
			'lastName'   => (string) get_user_meta( $user->ID, 'last_name', true ),
			'firstName'  => (string) get_user_meta( $user->ID, 'first_name', true ),
			'patronymic' => (string) get_user_meta( $user->ID, 'Patronymic', true ),
			'birthDate'  => (string) get_user_meta( $user->ID, 'BirthDate', true ),
			'email'      => (string) $user->user_email,
			'photoUrl'   => ( new Member_Card_Applications_Upload_Service() )->get_photo_url( $user->ID, true ),
			'phoneMobile' => (string) get_user_meta( $user->ID, 'PhoneMobile', true ),
			'phone2'     => (string) get_user_meta( $user->ID, 'Phone2', true ),
		];
	}

	private function get_repository(): \FSTU\Modules\Registry\MemberCardApplications\Member_Card_Applications_Repository {
		if ( null === $this->repository ) {
			$this->repository = new \FSTU\Modules\Registry\MemberCardApplications\Member_Card_Applications_Repository();
		}

		return $this->repository;
	}
}

