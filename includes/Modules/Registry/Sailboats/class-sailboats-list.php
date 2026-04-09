<?php
namespace FSTU\Modules\Registry\Sailboats;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Контролер відображення модуля "Судновий реєстр ФСТУ".
 * Реєструє шорткод [fstu_sailboats], підключає assets та передає локалізовані дані у JS.
 *
 * Version:     1.8.0
 * Date_update: 2026-04-09
 *
 * @package FSTU\Modules\Registry\Sailboats
 */
class Sailboats_List {

	private const ASSET_HANDLE = 'fstu-sailboats';
	private const ROUTE_OPTION = 'fstu_sailboats_page_url';
	private const INTENT_QUERY_KEY = 'fstu_intent';
	private const INTENT_VALUE     = 'personal_sailing';
	private const PROFILE_USER_QUERY_KEY = 'pc_user_id';
	private const RETURN_URL_QUERY_KEY   = 'pc_return';

	public const SHORTCODE    = 'fstu_sailboats';
	public const NONCE_ACTION = 'fstu_sailboats_nonce';

	public function init(): void {
		add_shortcode( self::SHORTCODE, [ $this, 'render_shortcode' ] );
	}

	/**
	 * Рендерить HTML модуля.
	 *
	 * @param array<string,mixed> $atts Атрибути шорткоду.
	 */
	public function render_shortcode( array $atts = [] ): string {
		unset( $atts );

		$permissions = $this->get_permissions();
		$view_data   = $this->get_view_data( $permissions );

		$this->enqueue_style();

		if ( ! empty( $permissions['canView'] ) ) {
			$this->enqueue_script( $permissions );
		}
		// --- ДОДАНО ДЛЯ МЕРИЛОК ---
		wp_enqueue_style( 'fstu-merilkas-style' );
		wp_enqueue_script( 'fstu-merilkas-script' );
		// --------------------------
		extract( $view_data, EXTR_SKIP );

		ob_start();
		include FSTU_PLUGIN_DIR . 'views/sailboats/main-page.php';

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

		$filtered_url = apply_filters( 'fstu_sailboats_module_url', $url, $context );

		return is_string( $filtered_url ) && '' !== $filtered_url ? $filtered_url : $url;
	}

	/**
	 * Підключає CSS модуля.
	 */
	private function enqueue_style(): void {
		wp_enqueue_style(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'css/fstu-sailboats.css',
			[],
			FSTU_VERSION
		);
	}

	/**
	 * Підключає JS модуля та передає локалізовані дані.
	 *
	 * @param array<string,bool> $permissions Права поточного користувача.
	 */
	private function enqueue_script( array $permissions ): void {
		$table_colspan = ! empty( $permissions['canFinance'] ) ? 13 : 9;
		$module_url    = self::get_module_url( 'login' );
		$login_url     = wp_login_url( '' !== $module_url ? $module_url : home_url( '/' ) );
		$current_user  = $this->get_current_user_form_defaults();
		$bootstrap     = $this->get_bootstrap_payload( $permissions );

		wp_enqueue_script(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'js/fstu-sailboats.js',
			[ 'jquery' ],
			FSTU_VERSION,
			true
		);

		wp_localize_script(
			self::ASSET_HANDLE,
			'fstuSailboatsL10n',
			[
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( self::NONCE_ACTION ),
				'loginUrl'    => $login_url,
				'bootstrap'   => $bootstrap,
				'permissions' => $permissions,
				'currentUser' => $current_user,
				'defaults'    => [
					'perPage'         => 10,
					'protocolPerPage' => 10,
				],
				'table'       => [
					'colspan'         => $table_colspan,
					'protocolColspan' => 6,
				],
				'actions'     => [
					'getList'         => 'fstu_sailboats_get_list',
					'getSingle'       => 'fstu_sailboats_get_single',
					'create'          => 'fstu_sailboats_create',
					'update'          => 'fstu_sailboats_update',
					'updateStatus'    => 'fstu_sailboats_update_status',
					'setPayment'      => 'fstu_sailboats_set_payment',
					'markReceived'    => 'fstu_sailboats_mark_received',
					'markSale'        => 'fstu_sailboats_mark_sale',
					'delete'          => 'fstu_sailboats_delete',
					'sendNotification'=> 'fstu_sailboats_send_dues_notification',
					'getProtocol'     => 'fstu_sailboats_get_protocol',
					'getDictionaries' => 'fstu_sailboats_get_dictionaries',
					'searchExisting'  => 'fstu_sailboats_search_existing',
				],
				'messages'    => [
					'loading'         => __( 'Завантаження...', 'fstu' ),
					'emptyList'       => __( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ),
					'emptyProtocol'   => __( 'Записи протоколу відсутні.', 'fstu' ),
					'error'           => __( 'Помилка завантаження даних.', 'fstu' ),
					'protocolError'   => __( 'Не вдалося завантажити протокол.', 'fstu' ),
					'filtersError'    => __( 'Не вдалося завантажити довідники фільтрів.', 'fstu' ),
					'existingSearchLoading' => __( 'Пошук існуючих суден...', 'fstu' ),
					'existingSearchMinLength' => __( 'Введіть щонайменше 2 символи для пошуку існуючого судна.', 'fstu' ),
					'existingSearchEmpty' => __( 'Доступні судна за цим запитом не знайдені.', 'fstu' ),
					'existingSearchError' => __( 'Не вдалося виконати пошук існуючих суден.', 'fstu' ),
					'existingSelected' => __( 'Вибрано існуюче судно для створення нової заявки.', 'fstu' ),
					'viewError'       => __( 'Не вдалося завантажити картку судна.', 'fstu' ),
					'saveSuccess'     => __( 'Запис успішно збережено.', 'fstu' ),
					'saveError'       => __( 'Помилка при збереженні запису.', 'fstu' ),
					'statusSaved'     => __( 'Статус успішно оновлено.', 'fstu' ),
					'paymentSaved'    => __( 'Оплату успішно збережено.', 'fstu' ),
					'receivedSaved'   => __( 'Позначку вручення успішно збережено.', 'fstu' ),
					'saleSaved'       => __( 'Продаж / вибуття успішно зафіксовано.', 'fstu' ),
					'deleteSaved'     => __( 'Запис успішно видалено.', 'fstu' ),
					'notificationSaved'=> __( 'Повідомлення успішно надіслано.', 'fstu' ),
					'actionError'     => __( 'Помилка виконання службової операції.', 'fstu' ),
					'deleteConfirm'   => __( 'Підтвердьте hard delete. Операція незворотна.', 'fstu' ),
					'formAddTitle'    => __( 'Додавання судна / заявки', 'fstu' ),
					'formEditTitle'   => __( 'Редагування судна / заявки', 'fstu' ),
					'notImplemented'  => __( 'Функціонал буде реалізовано на наступному етапі.', 'fstu' ),
					'featurePlanned'  => __( 'Функціонал буде реалізовано на наступному етапі.', 'fstu' ),
					'loginPromptTitle'=> __( 'Щоб працювати з Судновим реєстром ФСТУ, будь ласка, авторизуйтесь.', 'fstu' ),
				],
			]
		);
	}

	/**
	 * Повертає дані поточного користувача для автозаповнення форми заявки.
	 *
	 * @return array<string,string>
	 */
	private function get_current_user_form_defaults(): array {
		$user = wp_get_current_user();

		if ( ! ( $user instanceof \WP_User ) || $user->ID <= 0 ) {
			return [
				'userId'      => 0,
				'lastName'   => '',
				'firstName'  => '',
				'patronymic' => '',
			];
		}

		$last_name   = get_user_meta( $user->ID, 'last_name', true );
		$first_name  = get_user_meta( $user->ID, 'first_name', true );
		$patronymic  = get_user_meta( $user->ID, 'Patronymic', true );

		return [
			'userId'      => $user->ID,
			'lastName'   => is_string( $last_name ) ? $last_name : '',
			'firstName'  => is_string( $first_name ) ? $first_name : '',
			'patronymic' => is_string( $patronymic ) ? $patronymic : '',
		];
	}

	/**
	 * Готує дані для шаблону.
	 *
	 * @param array<string,bool> $permissions Права поточного користувача.
	 * @return array<string,mixed>
	 */
	private function get_view_data( array $permissions ): array {
		$module_url      = self::get_module_url( 'login' );
		$is_logged_in    = is_user_logged_in();
		$can_view        = ! empty( $permissions['canView'] );
		$guest_mode      = ! $is_logged_in && ! $can_view;
		$no_access_mode  = $is_logged_in && ! $can_view;
		$bootstrap       = $this->get_bootstrap_payload( $permissions );

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
	 * Повертає права поточного користувача для модуля.
	 *
	 * @return array<string,bool>
	 */
	private function get_permissions(): array {
		$permissions = Capabilities::get_sailboats_permissions();
		$permissions['canHardDeleteAdmin'] = current_user_can( 'manage_options' );

		return $permissions;
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
	 * Автоматично знаходить фронтенд-сторінку з shortcode [fstu_sailboats].
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

	/**
	 * @param array<string,bool> $permissions
	 * @return array<string,mixed>
	 */
	private function get_bootstrap_payload( array $permissions ): array {
		$intent = sanitize_key( wp_unslash( $_GET[ self::INTENT_QUERY_KEY ] ?? '' ) );

		if ( self::INTENT_VALUE !== $intent ) {
			return [
				'autoOpenCreate' => false,
				'userId'         => 0,
				'notice'         => '',
				'returnUrl'      => '',
			];
		}

		$user_id = absint( $_GET[ self::PROFILE_USER_QUERY_KEY ] ?? 0 );
		$current_user_id = get_current_user_id();
		$return_url = sanitize_text_field( wp_unslash( $_GET[ self::RETURN_URL_QUERY_KEY ] ?? '' ) );
		$return_url = wp_http_validate_url( $return_url ) ? $return_url : '';

		$can_auto_open_create = ( ! empty( $permissions['canManage'] ) || ! empty( $permissions['canSubmit'] ) ) && $user_id > 0 && $user_id === $current_user_id;

		return [
			'autoOpenCreate' => $can_auto_open_create,
			'userId'         => $user_id,
			'notice'         => $can_auto_open_create
				? __( 'Форму суднового реєстру відкрито з Особистого кабінету для подання нової заявки.', 'fstu' )
				: '',
			'returnUrl'      => $return_url,
		];
	}
}

