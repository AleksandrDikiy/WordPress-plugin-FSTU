<?php
namespace FSTU\Modules\Registry\Referees;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Контролер відображення модуля «Реєстр суддів ФСТУ».
 * Реєструє shortcode, підключає assets та локалізує фронтенд-дані.
 *
 * Version:     1.1.0
 * Date_update: 2026-04-09
 *
 * @package FSTU\Modules\Registry\Referees
 */
class Referees_List {

	private const ASSET_HANDLE = 'fstu-referees';
	private const ROUTE_OPTION = 'fstu_referees_page_url';
	private const INTENT_QUERY_KEY = 'fstu_intent';
	private const INTENT_VALUE = 'personal_judging';
	private const PROFILE_USER_QUERY_KEY = 'pc_user_id';
	private const RETURN_URL_QUERY_KEY = 'pc_return';

	public const SHORTCODE    = 'fstu_referees';
	public const NONCE_ACTION = 'fstu_referees_nonce';

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
		include FSTU_PLUGIN_DIR . 'views/referees/main-page.php';

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

		$filtered_url = apply_filters( 'fstu_referees_module_url', $url, $context );

		return is_string( $filtered_url ) && '' !== $filtered_url ? $filtered_url : $url;
	}

	private function enqueue_style(): void {
		$style_path = FSTU_PLUGIN_DIR . 'css/fstu-referees.css';

		wp_enqueue_style(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'css/fstu-referees.css',
			[],
			$this->get_asset_version( $style_path )
		);
	}

	/**
	 * @param array<string,bool> $permissions Права поточного користувача.
	 */
	private function enqueue_script( array $permissions ): void {
		$module_url = self::get_module_url( 'login' );
		$login_url  = wp_login_url( '' !== $module_url ? $module_url : home_url( '/' ) );
		$bootstrap  = $this->get_bootstrap_payload( $permissions );

		wp_enqueue_script(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'js/fstu-referees.js',
			[ 'jquery' ],
			$this->get_asset_version( FSTU_PLUGIN_DIR . 'js/fstu-referees.js' ),
			true
		);

		wp_localize_script(
			self::ASSET_HANDLE,
			'fstuRefereesL10n',
			[
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( self::NONCE_ACTION ),
				'loginUrl'    => $login_url,
				'bootstrap'   => $bootstrap,
				'permissions' => $permissions,
				'defaults'    => [
					'perPage'         => 10,
					'protocolPerPage' => 10,
				],
				'table'       => [
					'colspan'         => 7,
					'protocolColspan' => 6,
				],
				'actions'     => [
					'getList'                    => 'fstu_referees_get_list',
					'getSingle'                  => 'fstu_referees_get_single',
					'create'                     => 'fstu_referees_create',
					'update'                     => 'fstu_referees_update',
					'delete'                     => 'fstu_referees_delete',
					'getDictionaries'            => 'fstu_referees_get_dictionaries',
					'getProtocol'                => 'fstu_referees_get_protocol',
					'getCertificates'            => 'fstu_referees_get_certificates',
					'createCertificate'          => 'fstu_referees_create_certificate',
					'bindCertificateCategory'    => 'fstu_referees_bind_certificate_category',
					'unbindCertificateCategory'  => 'fstu_referees_unbind_certificate_category',
				],
				'messages'    => [
					'loading'             => __( 'Завантаження...', 'fstu' ),
					'error'               => __( 'Помилка завантаження даних.', 'fstu' ),
					'protocolError'       => __( 'Не вдалося завантажити протокол.', 'fstu' ),
					'filtersError'        => __( 'Не вдалося завантажити довідники модуля.', 'fstu' ),
					'emptyList'           => __( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ),
					'emptyProtocol'       => __( 'Записи протоколу відсутні.', 'fstu' ),
					'emptyCertificates'   => __( 'Довідки за суддівство відсутні.', 'fstu' ),
					'saveSuccess'         => __( 'Запис успішно збережено.', 'fstu' ),
					'saveError'           => __( 'Помилка при збереженні запису.', 'fstu' ),
					'deleteSuccess'       => __( 'Запис успішно видалено.', 'fstu' ),
					'deleteError'         => __( 'Не вдалося видалити запис.', 'fstu' ),
					'certSaveSuccess'     => __( 'Довідку успішно збережено.', 'fstu' ),
					'certBindSuccess'     => __( 'Категорію довідки успішно оновлено.', 'fstu' ),
					'certUnbindSuccess'   => __( 'Прив’язку категорії успішно знято.', 'fstu' ),
					'confirmDelete'       => __( 'Ви дійсно хочете видалити запис судді?', 'fstu' ),
					'confirmUnbind'       => __( 'Зняти прив’язку категорії з довідки?', 'fstu' ),
					'formAddTitle'        => __( 'Додавання судді', 'fstu' ),
					'formEditTitle'       => __( 'Редагування судді', 'fstu' ),
					'viewTitle'           => __( 'Картка судді', 'fstu' ),
					'certificatesTitle'   => __( 'Довідки за суддівство', 'fstu' ),
					'certificateFormTitle'=> __( 'Додавання довідки', 'fstu' ),
					'certificateBindTitle'=> __( 'Призначення категорії довідці', 'fstu' ),
					'guestPromptTitle'    => __( 'Щоб працювати з реєстром суддів, будь ласка, авторизуйтесь.', 'fstu' ),
				],
			]
		);
	}

	/**
	 * @param array<string,bool> $permissions Права поточного користувача.
	 * @return array<string,mixed>
	 */
	private function get_view_data( array $permissions ): array {
		$module_url     = self::get_module_url( 'login' );
		$is_logged_in   = is_user_logged_in();
		$can_view       = ! empty( $permissions['canView'] );
		$guest_mode     = ! $is_logged_in && ! $can_view;
		$no_access_mode = $is_logged_in && ! $can_view;
		$bootstrap      = $this->get_bootstrap_payload( $permissions );

		return [
			'permissions'     => $permissions,
			'guest_mode'      => $guest_mode,
			'no_access_mode'  => $no_access_mode,
			'guest_login_url' => wp_login_url( '' !== $module_url ? $module_url : home_url( '/' ) ),
			'bootstrap_notice' => isset( $bootstrap['notice'] ) ? (string) $bootstrap['notice'] : '',
			'bootstrap_return_url' => isset( $bootstrap['returnUrl'] ) ? (string) $bootstrap['returnUrl'] : '',
		];
	}

	/**
	 * @return array<string,bool>
	 */
	private function get_permissions(): array {
		return Capabilities::get_referees_permissions();
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

		if ( self::INTENT_VALUE !== $intent || empty( $permissions['canManage'] ) ) {
			return [
				'autoOpen'  => false,
				'mode'      => '',
				'userId'    => 0,
				'userFio'   => '',
				'refereeId' => 0,
				'notice'    => '',
				'returnUrl' => '',
			];
		}

		$user_id = absint( $_GET[ self::PROFILE_USER_QUERY_KEY ] ?? 0 );
		if ( $user_id <= 0 ) {
			return [ 'autoOpen' => false, 'mode' => '', 'userId' => 0, 'userFio' => '', 'refereeId' => 0, 'notice' => '', 'returnUrl' => '' ];
		}

		$repository = new Referees_Repository();
		if ( ! $repository->user_exists( $user_id ) ) {
			return [ 'autoOpen' => false, 'mode' => '', 'userId' => 0, 'userFio' => '', 'refereeId' => 0, 'notice' => '', 'returnUrl' => '' ];
		}

		$referee   = $repository->get_referee_by_user_id( $user_id );
		$user_fio  = $repository->get_user_fio( $user_id );
		$return_url = sanitize_text_field( wp_unslash( $_GET[ self::RETURN_URL_QUERY_KEY ] ?? '' ) );
		$return_url = wp_http_validate_url( $return_url ) ? $return_url : '';

		return [
			'autoOpen'  => true,
			'mode'      => is_array( $referee ) && ! empty( $referee['Referee_ID'] ) ? 'edit' : 'create',
			'userId'    => $user_id,
			'userFio'   => $user_fio,
			'refereeId' => is_array( $referee ) ? (int) ( $referee['Referee_ID'] ?? 0 ) : 0,
			'notice'    => sprintf( __( 'Форму суддівства відкрито з Особистого кабінету для користувача: %s', 'fstu' ), '' !== $user_fio ? $user_fio : '#' . $user_id ),
			'returnUrl' => $return_url,
		];
	}
}

