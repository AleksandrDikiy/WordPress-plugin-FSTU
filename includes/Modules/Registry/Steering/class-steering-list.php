<?php
namespace FSTU\Modules\Registry\Steering;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Контролер відображення модуля «Реєстр стернових ФСТУ».
 * Реєструє shortcode, підключає assets та локалізує фронтенд-дані.
 *
	 * Version:     1.10.0
	 * Date_update: 2026-04-09
 *
 * @package FSTU\Modules\Registry\Steering
 */
class Steering_List {

	private const ASSET_HANDLE = 'fstu-steering';
	private const ROUTE_OPTION = 'fstu_steering_page_url';
	private const POLICY_URL   = 'https://www.fstu.com.ua/wp-content/uploads/2015/06/%D0%9F%D1%80%D0%BE%D1%82%D0%BE%D0%BA%D0%BE%D0%BB-%D0%92%D0%B8%D0%BA%D0%BE%D0%BD%D0%BA%D0%BE%D0%BC%D1%83-%D0%BE%D0%BF%D0%B8%D1%82%D1%83%D0%B2%D0%B0%D0%BD%D0%BD%D1%8F-%D0%B2%D1%96%D0%B4-15-%D0%B0%D0%B2%D0%B3%D1%83%D1%81%D1%822015.pdf';
	private const INTENT_QUERY_KEY = 'fstu_intent';
	private const INTENT_VALUE     = 'personal_steering';
	private const PROFILE_USER_QUERY_KEY = 'pc_user_id';
	private const RETURN_URL_QUERY_KEY   = 'pc_return';

	public const SHORTCODE    = 'fstu_steering';
	public const NONCE_ACTION = 'fstu_steering_nonce';

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
		$this->enqueue_script( $permissions );

		extract( $view_data, EXTR_SKIP );

		ob_start();
		include FSTU_PLUGIN_DIR . 'views/steering/main-page.php';

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

		$filtered_url = apply_filters( 'fstu_steering_module_url', $url, $context );

		return is_string( $filtered_url ) && '' !== $filtered_url ? $filtered_url : $url;
	}

	private function enqueue_style(): void {
		$style_path = FSTU_PLUGIN_DIR . 'css/fstu-steering.css';

		wp_enqueue_style(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'css/fstu-steering.css',
			[],
			$this->get_asset_version( $style_path )
		);
	}

	/**
	 * @param array<string,bool> $permissions Права поточного користувача.
	 */
	private function enqueue_script( array $permissions ): void {
		$table_colspan = ! empty( $permissions['canSeeFinance'] ) ? 12 : 5;
		$current_user_defaults = $this->get_current_user_defaults();
		$submit_blocked        = $this->is_current_user_submit_blocked( $permissions );
		$bootstrap             = $this->get_bootstrap_payload( $permissions );

		wp_enqueue_script(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'js/fstu-steering.js',
			[ 'jquery' ],
			$this->get_asset_version( FSTU_PLUGIN_DIR . 'js/fstu-steering.js' ),
			true
		);

		wp_localize_script(
			self::ASSET_HANDLE,
			'fstuSteeringL10n',
			[
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( self::NONCE_ACTION ),
				'moduleUrl'   => self::get_module_url(),
				'policyUrl'   => self::POLICY_URL,
				'bootstrap'   => $bootstrap,
				'permissions' => $permissions,
				'currentUser' => $current_user_defaults,
				'submitBlocked' => $submit_blocked,
				'defaults'    => [
					'perPage'         => 10,
					'protocolPerPage' => 10,
				],
				'table'       => [
					'colspan'         => $table_colspan,
					'protocolColspan' => 6,
				],
				'actions'     => [
					'getList'   => 'fstu_steering_get_list',
					'getSingle' => 'fstu_steering_get_single',
					'getProtocol' => 'fstu_steering_get_protocol',
					'getDictionaries' => 'fstu_steering_get_dictionaries',
					'create'    => 'fstu_steering_create',
					'update'    => 'fstu_steering_update',
					'delete'    => 'fstu_steering_delete',
					'confirmVerification' => 'fstu_steering_confirm_verification',
					'register'  => 'fstu_steering_register',
					'markSentPost' => 'fstu_steering_mark_sent_post',
					'markReceived' => 'fstu_steering_mark_received',
				],
				'typeOptions' => $this->get_type_options(),
				'messages'    => [
					'loading'         => __( 'Завантаження...', 'fstu' ),
					'error'           => __( 'Помилка завантаження даних реєстру.', 'fstu' ),
					'emptyList'       => __( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ),
					'viewError'       => __( 'Не вдалося завантажити картку стернового.', 'fstu' ),
					'protocolError'   => __( 'Не вдалося завантажити протокол модуля.', 'fstu' ),
					'emptyProtocol'   => __( 'Записи протоколу відсутні.', 'fstu' ),
					'filtersError'    => __( 'Не вдалося завантажити довідники форми.', 'fstu' ),
					'saveSuccess'     => __( 'Заявку успішно збережено.', 'fstu' ),
					'saveError'       => __( 'Помилка при збереженні заявки.', 'fstu' ),
					'updateSuccess'   => __( 'Запис успішно оновлено.', 'fstu' ),
					'updateError'     => __( 'Сталася помилка оновлення запису.', 'fstu' ),
					'deleteSuccess'   => __( 'Запис успішно видалено.', 'fstu' ),
					'deleteError'     => __( 'Сталася помилка видалення запису.', 'fstu' ),
					'confirmDelete'   => __( 'Ви дійсно бажаєте видалити запис стернового?', 'fstu' ),
					'verifySuccess'   => __( 'Кваліфікацію успішно підтверджено.', 'fstu' ),
					'verifyError'     => __( 'Сталася помилка підтвердження кваліфікації.', 'fstu' ),
					'verifyLoading'   => __( 'Виконується підтвердження...', 'fstu' ),
					'registerSuccess' => __( 'Посвідчення успішно зареєстровано.', 'fstu' ),
					'registerError'   => __( 'Сталася помилка реєстрації посвідчення.', 'fstu' ),
					'sendPostSuccess' => __( 'Запис позначено як відправлений поштою.', 'fstu' ),
					'sendPostError'   => __( 'Сталася помилка оновлення статусу відправки.', 'fstu' ),
					'receivedSuccess' => __( 'Запис позначено як доставлений одержувачу.', 'fstu' ),
					'receivedError'   => __( 'Сталася помилка оновлення статусу доставки.', 'fstu' ),
					'statusLoading'   => __( 'Оновлення статусу...', 'fstu' ),
					'formTitle'       => __( 'Заявка на посвідчення стернового', 'fstu' ),
					'formEditTitle'   => __( 'Редагування запису стернового', 'fstu' ),
					'formEditSubmit'  => __( 'Зберегти зміни', 'fstu' ),
					'formCreateSubmit' => __( 'Відправити', 'fstu' ),
					'submitBlocked'   => __( 'Для цього користувача заявка або посвідчення вже існує. Повторна подача недоступна.', 'fstu' ),
					'notImplemented'  => __( 'Функціонал буде реалізовано на наступному етапі.', 'fstu' ),
					'viewTitle'       => __( 'Картка стернового', 'fstu' ),
					'protocolTitle'   => __( 'Протокол модуля «Реєстр стернових ФСТУ».', 'fstu' ),
				],
			]
		);
	}

	/**
	 * @param array<string,bool> $permissions Права поточного користувача.
	 * @return array<string,mixed>
	 */
	private function get_view_data( array $permissions ): array {
		$submit_blocked = $this->is_current_user_submit_blocked( $permissions );
		$bootstrap      = $this->get_bootstrap_payload( $permissions );

		return [
			'permissions'    => $permissions,
			'submit_blocked' => $submit_blocked,
			'type_options'   => $this->get_type_options(),
			'status_options' => ( ! empty( $permissions['canManage'] ) || ! empty( $permissions['canManageStatus'] ) ) ? ( new Steering_Repository() )->get_status_options() : [],
			'policy_url'     => self::POLICY_URL,
			'bootstrap_notice' => isset( $bootstrap['notice'] ) ? (string) $bootstrap['notice'] : '',
			'bootstrap_return_url' => isset( $bootstrap['returnUrl'] ) ? (string) $bootstrap['returnUrl'] : '',
		];
	}

	/**
	 * @return array<string,bool>
	 */
	private function get_permissions(): array {
		return Capabilities::get_steering_permissions();
	}

	/**
	 * @return array<string,mixed>
	 */
	private function get_current_user_defaults(): array {
		$user = wp_get_current_user();

		if ( ! ( $user instanceof \WP_User ) || $user->ID <= 0 ) {
			return [
				'userId'      => 0,
				'lastName'    => '',
				'firstName'   => '',
				'patronymic'  => '',
				'birthDate'   => '',
				'surnameEng'  => '',
				'nameEng'     => '',
			];
		}

		return [
			'userId'      => $user->ID,
			'lastName'    => (string) get_user_meta( $user->ID, 'last_name', true ),
			'firstName'   => (string) get_user_meta( $user->ID, 'first_name', true ),
			'patronymic'  => (string) get_user_meta( $user->ID, 'Patronymic', true ),
			'birthDate'   => (string) get_user_meta( $user->ID, 'BirthDate', true ),
			'surnameEng'  => '',
			'nameEng'     => '',
		];
	}

	/**
	 * @return array<int,array<string,string|int>>
	 */
	private function get_type_options(): array {
		return [
			[
				'value' => 1,
				'label' => '3.1.1. Довідки МКК про участь хоча б у одному категорійному спортивному туристському вітрильному поході другої і вище категорії складності, або керівництва спортивним туристським вітрильним походом першої і вище категорії складності.',
			],
			[
				'value' => 2,
				'label' => '3.1.2. Виписки з протоколу змагань зі спортивного вітрильного туризму ФСТУ про проходження в якості учасника дистанцій змагань 3 і вище класу, або в якості капітана команди дистанцій змагань 1 і вище класу.',
			],
			[
				'value' => 3,
				'label' => '3.1.3. Протоколу про здачу теоретичного та практичного тесту комісії що складається з не менш ніж двох сертифікованих стернових вітрильного спортивно-туристського судна ФСТУ і не менш ніж одного капітана вітрильного спортивно-туристського судна туристського судна ФСТУ.',
			],
			[
				'value' => 4,
				'label' => '3.1.4. Розрядної книжки що підтверджує 2-й і вище спортивний розряд зі спортивного (вітрильного) туризму.',
			],
		];
	}

	private function is_current_user_submit_blocked( array $permissions ): bool {
		if ( empty( $permissions['canSubmit'] ) || ! empty( $permissions['canManage'] ) ) {
			return false;
		}

		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return true;
		}

		$repository = new Steering_Repository();

		return $repository->user_has_steering_record( $user_id );
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
				'steeringId'=> 0,
				'notice'    => '',
				'returnUrl' => '',
			];
		}

		$user_id = absint( $_GET[ self::PROFILE_USER_QUERY_KEY ] ?? 0 );
		if ( $user_id <= 0 ) {
			return [ 'autoOpen' => false, 'mode' => '', 'userId' => 0, 'userFio' => '', 'steeringId' => 0, 'notice' => '', 'returnUrl' => '' ];
		}

		$repository = new Steering_Repository();
		if ( ! $repository->user_exists( $user_id ) ) {
			return [ 'autoOpen' => false, 'mode' => '', 'userId' => 0, 'userFio' => '', 'steeringId' => 0, 'notice' => '', 'returnUrl' => '' ];
		}

		$user_fio    = $repository->get_user_fio( $user_id );
		$steering_id = $repository->get_steering_id_by_user_id( $user_id );
		$return_url  = sanitize_text_field( wp_unslash( $_GET[ self::RETURN_URL_QUERY_KEY ] ?? '' ) );
		$return_url  = wp_http_validate_url( $return_url ) ? $return_url : '';

		return [
			'autoOpen'   => true,
			'mode'       => $steering_id > 0 ? 'edit' : 'create',
			'userId'     => $user_id,
			'userFio'    => $user_fio,
			'steeringId' => $steering_id,
			'notice'     => sprintf( __( 'Форму реєстру стернових відкрито з Особистого кабінету для користувача: %s', 'fstu' ), '' !== $user_fio ? $user_fio : '#' . $user_id ),
			'returnUrl'  => $return_url,
		];
	}
}

