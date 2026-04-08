<?php
namespace FSTU\Modules\Registry\Sailboats;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX-обробники модуля "Судновий реєстр ФСТУ".
 * Список, картка, протокол, create/update та службові операції модуля.
 *
 * Version:     1.10.0
 * Date_update: 2026-04-07
 *
 * @package FSTU\Modules\Registry\Sailboats
 */
class Sailboats_Ajax {

	private const DEFAULT_PER_PAGE      = 10;
	private const MAX_PER_PAGE          = 50;
	private const MAX_PROTOCOL_PER_PAGE = 50;
	private const MAX_EXISTING_RESULTS  = 15;
	private const MAX_SEARCH_LENGTH     = 100;
	private const LOG_NAME              = 'Sailboat';

	private ?Sailboats_Repository $repository = null;
	private ?Sailboats_Protocol_Service $protocol_service = null;
	private ?Sailboats_Notification_Service $notification_service = null;
	private ?Sailboats_Service $service = null;

	/**
	 * Реєструє AJAX-обробники.
	 */
	public function init(): void {
		add_action( 'wp_ajax_fstu_sailboats_get_list', [ $this, 'handle_get_list' ] );
		add_action( 'wp_ajax_fstu_sailboats_get_single', [ $this, 'handle_get_single' ] );
		add_action( 'wp_ajax_fstu_sailboats_create', [ $this, 'handle_create' ] );
		add_action( 'wp_ajax_fstu_sailboats_update', [ $this, 'handle_update' ] );
		add_action( 'wp_ajax_fstu_sailboats_update_status', [ $this, 'handle_update_status' ] );
		add_action( 'wp_ajax_fstu_sailboats_set_payment', [ $this, 'handle_set_payment' ] );
		add_action( 'wp_ajax_fstu_sailboats_mark_received', [ $this, 'handle_mark_received' ] );
		add_action( 'wp_ajax_fstu_sailboats_mark_sale', [ $this, 'handle_mark_sale' ] );
		add_action( 'wp_ajax_fstu_sailboats_delete', [ $this, 'handle_delete' ] );
		add_action( 'wp_ajax_fstu_sailboats_send_dues_notification', [ $this, 'handle_send_dues_notification' ] );
		add_action( 'wp_ajax_fstu_sailboats_get_protocol', [ $this, 'handle_get_protocol' ] );
		add_action( 'wp_ajax_fstu_sailboats_get_dictionaries', [ $this, 'handle_get_dictionaries' ] );
		add_action( 'wp_ajax_fstu_sailboats_search_existing', [ $this, 'handle_search_existing' ] );
	}

	/**
	 * Повертає список записів.
	 */
	public function handle_get_list(): void {
		check_ajax_referer( Sailboats_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду реєстру суден.', 'fstu' ) ] );
		}

		$search    = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$search    = mb_substr( $search, 0, self::MAX_SEARCH_LENGTH );
		$page      = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page  = min( max( 1, absint( $_POST['per_page'] ?? self::DEFAULT_PER_PAGE ) ), self::MAX_PER_PAGE );
		$region_id = absint( $_POST['region_id'] ?? 0 );
		$status_id = absint( $_POST['status_id'] ?? 0 );
		$offset    = ( $page - 1 ) * $per_page;

		$payload = $this->get_service()->get_list_payload(
			[
				'search'    => $search,
				'page'      => $page,
				'per_page'  => $per_page,
				'offset'    => $offset,
				'region_id' => $region_id,
				'status_id' => $status_id,
			]
		);

		wp_send_json_success(
			[
				'html'        => $this->build_rows( $payload['items'], $offset, $this->get_permissions() ),
				'total'       => $payload['total'],
				'page'        => $payload['page'],
				'per_page'    => $payload['per_page'],
				'total_pages' => $payload['total_pages'],
			]
		);
	}

	/**
	 * Повертає один запис судна.
	 */
	public function handle_get_single(): void {
		check_ajax_referer( Sailboats_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду картки судна.', 'fstu' ) ] );
		}

		$sailboat_id = absint( $_POST['sailboat_id'] ?? 0 );
		if ( $sailboat_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		$view_context = sanitize_text_field( wp_unslash( $_POST['view_context'] ?? '' ) );

		$item = $this->get_service()->get_single_payload( $sailboat_id );
		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		if ( 'card' === $view_context ) {
			$this->get_protocol_service()->try_log_action(
				'V',
				sprintf(
					'Переглянуто картку судна "%s" (AppShipTicket_ID:%d, Sailboat_ID:%d)',
					(string) ( $item['name'] ?? '' ),
					(int) ( $item['item_id'] ?? 0 ),
					(int) ( $item['sailboat_id'] ?? 0 )
				),
				Sailboats_Protocol_Service::STATUS_SUCCESS
			);
		}

		wp_send_json_success( [ 'item' => $item ] );
	}

	/**
	 * Повертає протокол модуля.
	 */
	public function handle_get_protocol(): void {
		check_ajax_referer( Sailboats_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view_protocol() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду протоколу.', 'fstu' ) ] );
		}

		$search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$search   = mb_substr( $search, 0, self::MAX_SEARCH_LENGTH );
		$page     = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page = min( max( 1, absint( $_POST['per_page'] ?? self::DEFAULT_PER_PAGE ) ), self::MAX_PROTOCOL_PER_PAGE );
		$offset   = ( $page - 1 ) * $per_page;

		$payload = $this->get_service()->get_protocol_payload(
			[
				'search'   => $search,
				'page'     => $page,
				'per_page' => $per_page,
				'offset'   => $offset,
				'log_name' => self::LOG_NAME,
			]
		);

		wp_send_json_success(
			[
				'html'        => $this->get_protocol_service()->build_protocol_rows( $payload['items'] ),
				'total'       => $payload['total'],
				'page'        => $payload['page'],
				'per_page'    => $payload['per_page'],
				'total_pages' => $payload['total_pages'],
			]
		);
	}

	/**
	 * Повертає довідники форми.
	 */
	public function handle_get_dictionaries(): void {
		check_ajax_referer( Sailboats_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду довідників модуля.', 'fstu' ) ] );
		}

		wp_send_json_success( [ 'items' => $this->get_service()->get_dictionaries_payload() ] );
	}

	/**
	 * Повертає список існуючих суден для сценарію створення нової заявки.
	 */
	public function handle_search_existing(): void {
		check_ajax_referer( Sailboats_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_submit() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для подання заявки.', 'fstu' ) ] );
		}

		$search = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$search = mb_substr( $search, 0, self::MAX_SEARCH_LENGTH );

		if ( mb_strlen( trim( $search ) ) < 2 ) {
			wp_send_json_success( [ 'items' => [] ] );
		}

		wp_send_json_success(
			[
				'items' => $this->get_service()->search_existing_sailboats_payload( $search, self::MAX_EXISTING_RESULTS ),
			]
		);
	}

	/**
	 * Створює новий запис судна / заявки.
	 */
	public function handle_create(): void {
		check_ajax_referer( Sailboats_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_submit() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для подання заявки або додавання запису.', 'fstu' ) ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		$data          = $this->sanitize_form_data();
		$error_message = $this->validate_form_data( $data, false );

		if ( '' !== $error_message ) {
			wp_send_json_error( [ 'message' => $error_message ] );
		}

		try {
			$result = $this->get_service()->create_item( $data );

			wp_send_json_success(
				[
					'message' => __( 'Запис успішно збережено.', 'fstu' ),
					'item_id' => (int) ( $result['item_id'] ?? 0 ),
				]
			);
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_mutation_error_message( $throwable, true ) ] );
		}
	}

	/**
	 * Оновлює існуючий запис.
	 */
	public function handle_update(): void {
		check_ajax_referer( Sailboats_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для редагування запису.', 'fstu' ) ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		$data          = $this->sanitize_form_data();
		$error_message = $this->validate_form_data( $data, true );

		if ( '' !== $error_message ) {
			wp_send_json_error( [ 'message' => $error_message ] );
		}

		try {
			$result = $this->get_service()->update_item( $data );

			wp_send_json_success(
				[
					'message' => __( 'Запис успішно оновлено.', 'fstu' ),
					'item_id' => (int) ( $result['item_id'] ?? 0 ),
				]
			);
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_mutation_error_message( $throwable, false ) ] );
		}
	}

	/**
	 * Оновлює статус запису.
	 */
	public function handle_update_status(): void {
		check_ajax_referer( Sailboats_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage_status() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для зміни статусу.', 'fstu' ) ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		$data = $this->sanitize_status_action_data();
		$error_message = $this->validate_status_action_data( $data );

		if ( '' !== $error_message ) {
			wp_send_json_error( [ 'message' => $error_message ] );
		}

		try {
			$this->get_service()->update_status( $data );

			wp_send_json_success( [ 'message' => __( 'Статус успішно оновлено.', 'fstu' ) ] );
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_service_action_error_message( $throwable, 'status' ) ] );
		}
	}

	/**
	 * Зберігає оплату.
	 */
	public function handle_set_payment(): void {
		check_ajax_referer( Sailboats_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage_payments() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для фінансової операції.', 'fstu' ) ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		$data = $this->sanitize_payment_action_data();
		$error_message = $this->validate_payment_action_data( $data );

		if ( '' !== $error_message ) {
			wp_send_json_error( [ 'message' => $error_message ] );
		}

		try {
			$this->get_service()->set_payment( $data );

			wp_send_json_success( [ 'message' => __( 'Оплату успішно збережено.', 'fstu' ) ] );
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_service_action_error_message( $throwable, 'payment' ) ] );
		}
	}

	/**
	 * Фіксує вручення / доставку документа.
	 */
	public function handle_mark_received(): void {
		check_ajax_referer( Sailboats_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage_status() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для фіксації вручення.', 'fstu' ) ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		$data = $this->sanitize_received_action_data();
		$error_message = $this->validate_received_action_data( $data );

		if ( '' !== $error_message ) {
			wp_send_json_error( [ 'message' => $error_message ] );
		}

		try {
			$this->get_service()->mark_received( $data );

			wp_send_json_success( [ 'message' => __( 'Позначку вручення успішно збережено.', 'fstu' ) ] );
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_service_action_error_message( $throwable, 'received' ) ] );
		}
	}

	/**
	 * Фіксує продаж / вибуття судна.
	 */
	public function handle_mark_sale(): void {
		check_ajax_referer( Sailboats_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage_status() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для фіксації вибуття судна.', 'fstu' ) ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		$data = $this->sanitize_sale_action_data();
		$error_message = $this->validate_sale_action_data( $data );

		if ( '' !== $error_message ) {
			wp_send_json_error( [ 'message' => $error_message ] );
		}

		try {
			$this->get_service()->mark_sale( $data );

			wp_send_json_success( [ 'message' => __( 'Продаж / вибуття успішно зафіксовано.', 'fstu' ) ] );
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_service_action_error_message( $throwable, 'sale' ) ] );
		}
	}

	/**
	 * Виконує hard delete запису.
	 */
	public function handle_delete(): void {
		check_ajax_referer( Sailboats_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_hard_delete_admin_only() ) {
			wp_send_json_error( [ 'message' => __( 'Hard delete доступний лише адміністратору.', 'fstu' ) ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		$data = $this->sanitize_delete_action_data();
		$error_message = $this->validate_delete_action_data( $data );

		if ( '' !== $error_message ) {
			wp_send_json_error( [ 'message' => $error_message ] );
		}

		try {
			$this->get_service()->delete_item( $data );

			wp_send_json_success( [ 'message' => __( 'Запис успішно видалено.', 'fstu' ) ] );
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_delete_error_message( $throwable ) ] );
		}
	}

	/**
	 * Надсилає повідомлення щодо внесків.
	 */
	public function handle_send_dues_notification(): void {
		check_ajax_referer( Sailboats_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_send_notifications() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для надсилання повідомлень.', 'fstu' ) ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		$data = $this->sanitize_notification_action_data();
		$error_message = $this->validate_notification_action_data( $data );

		if ( '' !== $error_message ) {
			wp_send_json_error( [ 'message' => $error_message ] );
		}

		try {
			$this->get_service()->send_dues_notification( $data );

			wp_send_json_success( [ 'message' => __( 'Повідомлення успішно надіслано.', 'fstu' ) ] );
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_notification_error_message( $throwable ) ] );
		}
	}

	/**
	 * Заглушка для керуючих дій.
	 */
	public function handle_not_implemented_manage(): void {
		$this->handle_not_implemented_action(
			fn() => $this->current_user_can_manage(),
			__( 'Недостатньо прав для виконання дії.', 'fstu' )
		);
	}

	/**
	 * Заглушка для дій зі статусами.
	 */
	public function handle_not_implemented_status(): void {
		$this->handle_not_implemented_action(
			fn() => $this->current_user_can_manage_status(),
			__( 'Недостатньо прав для зміни статусу.', 'fstu' )
		);
	}

	/**
	 * Заглушка для фінансових дій.
	 */
	public function handle_not_implemented_payments(): void {
		$this->handle_not_implemented_action(
			fn() => $this->current_user_can_manage_payments(),
			__( 'Недостатньо прав для фінансової операції.', 'fstu' )
		);
	}

	/**
	 * Заглушка для видалення.
	 */
	public function handle_not_implemented_delete(): void {
		$this->handle_not_implemented_action(
			fn() => $this->current_user_can_delete(),
			__( 'Недостатньо прав для видалення запису.', 'fstu' )
		);
	}

	/**
	 * Заглушка для сповіщень.
	 */
	public function handle_not_implemented_notifications(): void {
		$this->handle_not_implemented_action(
			fn() => $this->current_user_can_send_notifications(),
			__( 'Недостатньо прав для надсилання повідомлень.', 'fstu' )
		);
	}

	/**
	 * Обробляє ще не реалізовані дії безпечною AJAX-відповіддю.
	 *
	 * @param callable():bool $permission_check Перевірка права доступу.
	 */
	private function handle_not_implemented_action( callable $permission_check, string $no_access_message ): void {
		check_ajax_referer( Sailboats_List::NONCE_ACTION, 'nonce' );

		if ( ! $permission_check() ) {
			wp_send_json_error( [ 'message' => $no_access_message ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		wp_send_json_error( [ 'message' => __( 'Функціонал буде реалізовано на наступному етапі.', 'fstu' ) ] );
	}

	/**
	 * Санітизує дані форми.
	 *
	 * @return array<string,mixed>
	 */
	private function sanitize_form_data(): array {
		$data = [
			'item_id'                     => absint( $_POST['item_id'] ?? 0 ),
			'form_mode'                   => sanitize_text_field( wp_unslash( $_POST['form_mode'] ?? 'new' ) ),
			'create_mode'                 => sanitize_text_field( wp_unslash( $_POST['create_mode'] ?? 'new' ) ),
			'sailboat_id'                 => absint( $_POST['sailboat_id'] ?? 0 ),
			'existing_sailboat_id'        => absint( $_POST['existing_sailboat_id'] ?? 0 ),
			'sailboat_name'               => sanitize_text_field( wp_unslash( $_POST['sailboat_name'] ?? '' ) ),
			'sailboat_name_eng'           => sanitize_text_field( wp_unslash( $_POST['sailboat_name_eng'] ?? '' ) ),
			'sailboat_number_sail'        => sanitize_text_field( wp_unslash( $_POST['sailboat_number_sail'] ?? '' ) ),
			'sailboat_year'               => absint( $_POST['sailboat_year'] ?? 0 ),
			'city_id'                     => absint( $_POST['city_id'] ?? 0 ),
			'region_id'                   => absint( $_POST['region_id'] ?? 0 ),
			'verification_id'             => absint( $_POST['verification_id'] ?? 0 ),
			'producer_id'                 => absint( $_POST['producer_id'] ?? 0 ),
			'type_boat_id'                => absint( $_POST['type_boat_id'] ?? 0 ),
			'type_hull_id'                => absint( $_POST['type_hull_id'] ?? 0 ),
			'type_construction_id'        => absint( $_POST['type_construction_id'] ?? 0 ),
			'type_ship_id'                => absint( $_POST['type_ship_id'] ?? 0 ),
			'hull_material_id'            => absint( $_POST['hull_material_id'] ?? 0 ),
			'hull_color_id'               => absint( $_POST['hull_color_id'] ?? 0 ),
			'sailboat_sail_main'          => $this->sanitize_float_field( $_POST['sailboat_sail_main'] ?? '' ),
			'sailboat_hill_length'        => $this->sanitize_float_field( $_POST['sailboat_hill_length'] ?? '' ),
			'sailboat_crew_max'           => absint( $_POST['sailboat_crew_max'] ?? 0 ),
			'sailboat_width_overall'      => $this->sanitize_float_field( $_POST['sailboat_width_overall'] ?? '' ),
			'sailboat_clearance'          => $this->sanitize_float_field( $_POST['sailboat_clearance'] ?? '' ),
			'sailboat_load_capacity'      => $this->sanitize_float_field( $_POST['sailboat_load_capacity'] ?? '' ),
			'sailboat_motor_power'        => $this->sanitize_float_field( $_POST['sailboat_motor_power'] ?? '' ),
			'sailboat_motor_number'       => sanitize_text_field( wp_unslash( $_POST['sailboat_motor_number'] ?? '' ) ),
			'appshipticket_number_manual' => sanitize_text_field( wp_unslash( $_POST['appshipticket_number_manual'] ?? '' ) ),
			'appshipticket_last_name'     => sanitize_text_field( wp_unslash( $_POST['appshipticket_last_name'] ?? '' ) ),
			'appshipticket_first_name'    => sanitize_text_field( wp_unslash( $_POST['appshipticket_first_name'] ?? '' ) ),
			'appshipticket_patronymic'    => sanitize_text_field( wp_unslash( $_POST['appshipticket_patronymic'] ?? '' ) ),
			'appshipticket_last_name_eng' => sanitize_text_field( wp_unslash( $_POST['appshipticket_last_name_eng'] ?? '' ) ),
			'appshipticket_first_name_eng'=> sanitize_text_field( wp_unslash( $_POST['appshipticket_first_name_eng'] ?? '' ) ),
			'appshipticket_np'            => sanitize_text_field( wp_unslash( $_POST['appshipticket_np'] ?? '' ) ),
		];

		return $this->apply_current_user_owner_defaults( $data );
	}

	/**
	 * Санітизує дані дії зміни статусу.
	 *
	 * @return array<string,mixed>
	 */
	private function sanitize_status_action_data(): array {
		return [
			'item_id'         => absint( $_POST['item_id'] ?? 0 ),
			'verification_id' => absint( $_POST['verification_id'] ?? 0 ),
			'comment'         => sanitize_textarea_field( wp_unslash( $_POST['comment'] ?? '' ) ),
		];
	}

	/**
	 * Санітизує дані фінансової дії.
	 *
	 * @return array<string,mixed>
	 */
	private function sanitize_payment_action_data(): array {
		return [
			'item_id'        => absint( $_POST['item_id'] ?? 0 ),
			'payment_slot'   => sanitize_text_field( wp_unslash( $_POST['payment_slot'] ?? 'V1' ) ),
			'payment_amount' => (float) str_replace( ',', '.', (string) wp_unslash( $_POST['payment_amount'] ?? '0' ) ),
			'payment_date'   => $this->normalize_date_input( sanitize_text_field( wp_unslash( $_POST['payment_date'] ?? '' ) ), false ),
			'comment'        => sanitize_textarea_field( wp_unslash( $_POST['comment'] ?? '' ) ),
		];
	}

	/**
	 * Санітизує дані дії вручення.
	 *
	 * @return array<string,mixed>
	 */
	private function sanitize_received_action_data(): array {
		return [
			'item_id'     => absint( $_POST['item_id'] ?? 0 ),
			'received_at' => $this->normalize_date_input( sanitize_text_field( wp_unslash( $_POST['received_at'] ?? '' ) ), false ),
			'comment'     => sanitize_textarea_field( wp_unslash( $_POST['comment'] ?? '' ) ),
		];
	}

	/**
	 * Санітизує дані дії вибуття / продажу.
	 *
	 * @return array<string,mixed>
	 */
	private function sanitize_sale_action_data(): array {
		return [
			'item_id'   => absint( $_POST['item_id'] ?? 0 ),
			'sale_date' => $this->normalize_date_input( sanitize_text_field( wp_unslash( $_POST['sale_date'] ?? '' ) ), false ),
			'comment'   => sanitize_textarea_field( wp_unslash( $_POST['comment'] ?? '' ) ),
		];
	}

	/**
	 * Санітизує дані видалення.
	 *
	 * @return array<string,mixed>
	 */
	private function sanitize_delete_action_data(): array {
		return [
			'item_id'             => absint( $_POST['item_id'] ?? 0 ),
			'confirm_hard_delete' => absint( $_POST['confirm_hard_delete'] ?? 0 ),
		];
	}

	/**
	 * Санітизує дані надсилання повідомлення.
	 *
	 * @return array<string,mixed>
	 */
	private function sanitize_notification_action_data(): array {
		return [
			'item_id'           => absint( $_POST['item_id'] ?? 0 ),
			'notification_type' => sanitize_text_field( wp_unslash( $_POST['notification_type'] ?? 'membership' ) ),
			'comment'           => sanitize_textarea_field( wp_unslash( $_POST['comment'] ?? '' ) ),
		];
	}

	/**
	 * Валідовує дані форми.
	 */
	private function validate_form_data( array $data, bool $is_update ): string {
		if ( $is_update && (int) ( $data['item_id'] ?? 0 ) <= 0 ) {
			return __( 'Невірний ідентифікатор запису.', 'fstu' );
		}

		$create_mode = (string) ( $data['create_mode'] ?? 'new' );
		if ( ! in_array( $create_mode, [ 'new', 'existing' ], true ) ) {
			return __( 'Невірний режим створення.', 'fstu' );
		}

		if ( '' === trim( (string) ( $data['sailboat_name'] ?? '' ) ) ) {
			return __( 'Вкажіть найменування судна.', 'fstu' );
		}

		if ( (int) ( $data['region_id'] ?? 0 ) <= 0 ) {
			return __( 'Оберіть область.', 'fstu' );
		}

		if ( '' === trim( (string) ( $data['appshipticket_last_name'] ?? '' ) ) ) {
			return __( 'Вкажіть прізвище власника / капітана.', 'fstu' );
		}

		if ( '' === trim( (string) ( $data['appshipticket_first_name'] ?? '' ) ) ) {
			return __( 'Вкажіть ім’я власника / капітана.', 'fstu' );
		}

		if ( ! $is_update && 'existing' === $create_mode && (int) ( $data['existing_sailboat_id'] ?? 0 ) <= 0 ) {
			return __( 'Для режиму існуючого судна вкажіть ID судна.', 'fstu' );
		}

		$year = (int) ( $data['sailboat_year'] ?? 0 );
		if ( $year > 0 && ( $year < 1900 || $year > 2100 ) ) {
			return __( 'Вкажіть коректний рік побудови.', 'fstu' );
		}

		$latin_fields = [
			'sailboat_name_eng'           => __( 'Найменування судна (ENG)', 'fstu' ),
			'appshipticket_last_name_eng' => __( 'Прізвище (ENG)', 'fstu' ),
			'appshipticket_first_name_eng'=> __( 'Ім’я (ENG)', 'fstu' ),
		];

		foreach ( $latin_fields as $key => $label ) {
			$value = trim( (string) ( $data[ $key ] ?? '' ) );

			if ( '' !== $value && ! $this->is_valid_latin_text( $value ) ) {
				return sprintf( __( 'Поле «%s» повинно містити лише латинські літери, пробіли, апостроф або дефіс.', 'fstu' ), $label );
			}
		}

		foreach (
			[
				'sailboat_sail_main'     => __( 'Площа основного вітрила', 'fstu' ),
				'sailboat_hill_length'   => __( 'Довжина корпусу', 'fstu' ),
				'sailboat_width_overall' => __( 'Ширина габаритна', 'fstu' ),
				'sailboat_clearance'     => __( 'Осадка / кліренс', 'fstu' ),
				'sailboat_load_capacity' => __( 'Вантажопідйомність', 'fstu' ),
				'sailboat_motor_power'   => __( 'Потужність двигуна', 'fstu' ),
			] as $key => $label
		) {
			if ( (float) ( $data[ $key ] ?? 0 ) < 0 ) {
				return sprintf( __( 'Поле «%s» не може бути меншим за 0.', 'fstu' ), $label );
			}
		}

		if ( (int) ( $data['sailboat_crew_max'] ?? 0 ) < 0 ) {
			return __( 'Максимальний екіпаж не може бути меншим за 0.', 'fstu' );
		}

		return '';
	}

	/**
	 * Перевіряє, що текстове поле містить лише допустимі латинські символи.
	 */
	private function is_valid_latin_text( string $value ): bool {
		return 1 === preg_match( "/^[A-Za-z\\s'’`-]+$/u", $value );
	}

	/**
	 * Автоматично підставляє ПІБ поточного користувача для нової заявки, якщо поля порожні.
	 *
	 * @param array<string,mixed> $data
	 * @return array<string,mixed>
	 */
	private function apply_current_user_owner_defaults( array $data ): array {
		$user = wp_get_current_user();

		if ( ! ( $user instanceof \WP_User ) || $user->ID <= 0 ) {
			return $data;
		}

		$defaults = [
			'appshipticket_last_name'  => get_user_meta( $user->ID, 'last_name', true ),
			'appshipticket_first_name' => get_user_meta( $user->ID, 'first_name', true ),
			'appshipticket_patronymic' => get_user_meta( $user->ID, 'Patronymic', true ),
		];

		foreach ( $defaults as $key => $value ) {
			if ( '' === trim( (string) ( $data[ $key ] ?? '' ) ) && is_string( $value ) ) {
				$data[ $key ] = sanitize_text_field( $value );
			}
		}

		return $data;
	}

	/**
	 * Санітизує float-поле форми.
	 */
	private function sanitize_float_field( $value ): float {
		$normalized = str_replace( ',', '.', sanitize_text_field( wp_unslash( (string) $value ) ) );

		return is_numeric( $normalized ) ? (float) $normalized : 0.0;
	}

	/**
	 * Валідовує зміну статусу.
	 */
	private function validate_status_action_data( array $data ): string {
		if ( (int) ( $data['item_id'] ?? 0 ) <= 0 ) {
			return __( 'Невірний ідентифікатор запису.', 'fstu' );
		}

		if ( (int) ( $data['verification_id'] ?? 0 ) <= 0 ) {
			return __( 'Оберіть новий статус.', 'fstu' );
		}

		return '';
	}

	/**
	 * Валідовує фінансову дію.
	 */
	private function validate_payment_action_data( array $data ): string {
		if ( (int) ( $data['item_id'] ?? 0 ) <= 0 ) {
			return __( 'Невірний ідентифікатор запису.', 'fstu' );
		}

		if ( ! in_array( (string) ( $data['payment_slot'] ?? '' ), [ 'V1', 'V2', 'F1', 'F2' ], true ) ) {
			return __( 'Невірний тип внеску.', 'fstu' );
		}

		if ( (float) ( $data['payment_amount'] ?? 0 ) <= 0 ) {
			return __( 'Вкажіть коректну суму оплати.', 'fstu' );
		}

		if ( '' === (string) ( $data['payment_date'] ?? '' ) ) {
			return __( 'Вкажіть коректну дату оплати.', 'fstu' );
		}

		return '';
	}

	/**
	 * Валідовує дію вручення.
	 */
	private function validate_received_action_data( array $data ): string {
		if ( (int) ( $data['item_id'] ?? 0 ) <= 0 ) {
			return __( 'Невірний ідентифікатор запису.', 'fstu' );
		}

		if ( '' === (string) ( $data['received_at'] ?? '' ) ) {
			return __( 'Вкажіть коректну дату вручення.', 'fstu' );
		}

		return '';
	}

	/**
	 * Валідовує дію продажу / вибуття.
	 */
	private function validate_sale_action_data( array $data ): string {
		if ( (int) ( $data['item_id'] ?? 0 ) <= 0 ) {
			return __( 'Невірний ідентифікатор запису.', 'fstu' );
		}

		if ( '' === (string) ( $data['sale_date'] ?? '' ) ) {
			return __( 'Вкажіть коректну дату продажу / вибуття.', 'fstu' );
		}

		return '';
	}

	/**
	 * Валідовує видалення.
	 */
	private function validate_delete_action_data( array $data ): string {
		if ( (int) ( $data['item_id'] ?? 0 ) <= 0 ) {
			return __( 'Невірний ідентифікатор запису.', 'fstu' );
		}

		if ( 1 !== (int) ( $data['confirm_hard_delete'] ?? 0 ) ) {
			return __( 'Підтвердьте виконання hard delete.', 'fstu' );
		}

		return '';
	}

	/**
	 * Валідовує повідомлення щодо внесків.
	 */
	private function validate_notification_action_data( array $data ): string {
		if ( (int) ( $data['item_id'] ?? 0 ) <= 0 ) {
			return __( 'Невірний ідентифікатор запису.', 'fstu' );
		}

		if ( ! in_array( (string) ( $data['notification_type'] ?? '' ), [ 'membership', 'sailing', 'combined' ], true ) ) {
			return __( 'Невірний тип повідомлення.', 'fstu' );
		}

		return '';
	}

	/**
	 * Повертає безпечне повідомлення для mutation-flow.
	 */
	private function get_mutation_error_message( \Throwable $throwable, bool $is_create ): string {
		$marker = trim( $throwable->getMessage() );

		return match ( $marker ) {
			'existing_sailboat_not_found'          => __( 'Існуюче судно не знайдено.', 'fstu' ),
			'existing_sailboat_has_active_application' => __( 'Для цього судна вже існує активна заявка / судновий квиток. Спочатку заверште або відкрийте наявний запис.', 'fstu' ),
			'item_not_found'                       => __( 'Запис не знайдено.', 'fstu' ),
			'application_ship_ticket_not_found'    => __( 'Пов’язану заявку не знайдено.', 'fstu' ),
			'sailboat_table_schema_invalid',
			'application_ship_ticket_schema_invalid' => __( 'Структура legacy-таблиць не дозволяє виконати операцію збереження.', 'fstu' ),
			'sailboat_insert_failed',
			'application_ship_ticket_insert_failed',
			'sailboat_update_failed',
			'application_ship_ticket_update_failed',
			'sailboat_link_update_failed',
			'sailboats_log_insert_failed'          => $is_create
				? __( 'Помилка при збереженні запису.', 'fstu' )
				: __( 'Помилка при оновленні запису.', 'fstu' ),
			default                               => $is_create
				? __( 'Помилка при збереженні запису.', 'fstu' )
				: __( 'Помилка при оновленні запису.', 'fstu' ),
		};
	}

	/**
	 * Повертає безпечне повідомлення для службових операцій.
	 */
	private function get_service_action_error_message( \Throwable $throwable, string $action ): string {
		$marker = trim( $throwable->getMessage() );

		return match ( $marker ) {
			'item_not_found', 'application_ship_ticket_not_found' => __( 'Запис не знайдено.', 'fstu' ),
			'invalid_payment_date'                                 => __( 'Вкажіть коректну дату оплати.', 'fstu' ),
			'invalid_payment_date_sequence'                        => __( 'Дата оплати не може бути раніше дати створення заявки.', 'fstu' ),
			'invalid_received_date'                                => __( 'Вкажіть коректну дату вручення.', 'fstu' ),
			'invalid_received_date_sequence'                       => __( 'Дата вручення не може бути раніше дати створення заявки.', 'fstu' ),
			'invalid_sale_date'                                    => __( 'Вкажіть коректну дату продажу / вибуття.', 'fstu' ),
			'invalid_sale_date_sequence'                           => __( 'Дата продажу / вибуття не може бути раніше дати створення заявки.', 'fstu' ),
			'application_ship_ticket_status_unsupported'          => __( 'Legacy-схема не підтримує зміну статусу через новий модуль.', 'fstu' ),
			'application_ship_ticket_payment_unsupported'         => __( 'Legacy-схема не підтримує збереження оплати через новий модуль.', 'fstu' ),
			'application_ship_ticket_received_unsupported'        => __( 'Legacy-схема не підтримує фіксацію вручення через новий модуль.', 'fstu' ),
			'application_ship_ticket_sale_unsupported'            => __( 'Legacy-схема не підтримує фіксацію вибуття через новий модуль.', 'fstu' ),
			'application_ship_ticket_update_failed',
			'sailboats_log_insert_failed'                        => $this->get_generic_action_error_message( $action ),
			default                                              => $this->get_generic_action_error_message( $action ),
		};
	}

	/**
	 * Повертає generic-повідомлення для службової дії.
	 */
	private function get_generic_action_error_message( string $action ): string {
		return match ( $action ) {
			'status'   => __( 'Помилка при зміні статусу.', 'fstu' ),
			'payment'  => __( 'Помилка при збереженні оплати.', 'fstu' ),
			'received' => __( 'Помилка при фіксації вручення.', 'fstu' ),
			'sale'     => __( 'Помилка при фіксації продажу / вибуття.', 'fstu' ),
			'delete'   => __( 'Не вдалося видалити запис.', 'fstu' ),
			'notify'   => __( 'Не вдалося надіслати повідомлення.', 'fstu' ),
			default    => __( 'Помилка виконання службової операції.', 'fstu' ),
		};
	}

	/**
	 * Повертає безпечне повідомлення для delete-flow.
	 */
	private function get_delete_error_message( \Throwable $throwable ): string {
		$marker = trim( $throwable->getMessage() );

		if ( str_starts_with( $marker, 'delete_blocked:' ) ) {
			return (string) mb_substr( $marker, mb_strlen( 'delete_blocked:' ) );
		}

		return match ( $marker ) {
			'item_not_found', 'application_ship_ticket_not_found' => __( 'Запис не знайдено.', 'fstu' ),
			'application_delete_failed',
			'application_ship_ticket_delete_failed',
			'sailboat_delete_failed',
			'sailboats_log_insert_failed'                         => __( 'Не вдалося видалити запис.', 'fstu' ),
			default                                               => __( 'Не вдалося видалити запис.', 'fstu' ),
		};
	}

	/**
	 * Повертає безпечне повідомлення для notification-flow.
	 */
	private function get_notification_error_message( \Throwable $throwable ): string {
		$marker = trim( $throwable->getMessage() );

		return match ( $marker ) {
			'item_not_found'               => __( 'Запис не знайдено.', 'fstu' ),
			'notification_email_not_found' => __( 'Не знайдено валідний email одержувача.', 'fstu' ),
			'notification_send_failed'     => __( 'Не вдалося надіслати повідомлення.', 'fstu' ),
			default                        => __( 'Не вдалося надіслати повідомлення.', 'fstu' ),
		};
	}

	/**
	 * Нормалізує дату форми до MySQL-формату.
	 */
	private function normalize_date_input( string $value, bool $fallback_to_now ): string {
		$value = trim( $value );

		if ( '' === $value ) {
			return $fallback_to_now ? current_time( 'mysql' ) : '';
		}

		$timestamp = strtotime( $value );
		if ( false === $timestamp ) {
			return '';
		}

		return wp_date( 'Y-m-d H:i:s', $timestamp );
	}

	/**
	 * Повертає true, якщо спрацювало honeypot-поле.
	 */
	private function is_honeypot_triggered(): bool {
		$honeypot = sanitize_text_field( wp_unslash( $_POST['fstu_website'] ?? '' ) );

		return '' !== trim( $honeypot );
	}

	/**
	 * Повертає HTML рядків таблиці.
	 *
	 * @param array<int,array<string,mixed>> $items Список суден.
	 */
	private function build_rows( array $items, int $offset, array $permissions ): string {
		$colspan = ! empty( $permissions['canFinance'] ) ? 13 : 9;

		if ( empty( $items ) ) {
			return '<tr class="fstu-row"><td colspan="' . esc_attr( (string) $colspan ) . '" class="fstu-no-results">' . esc_html__( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ) . '</td></tr>';
		}

		$html = '';

		foreach ( $items as $index => $item ) {
			$item_id     = (int) ( $item['item_id'] ?? 0 );
			$number = $offset + $index + 1;
			$name   = trim( (string) ( $item['name'] ?? '' ) );
			$dropdown_actions   = [];
			$dropdown_actions[] = [
				'label' => __( 'Перегляд', 'fstu' ),
				'class' => 'fstu-sailboats-view-btn',
			];

			if ( ! empty( $permissions['canManage'] ) ) {
				$dropdown_actions[] = [
					'label' => __( 'Редагування', 'fstu' ),
					'class' => 'fstu-sailboats-edit-btn',
				];
			}

			if ( ! empty( $permissions['canStatus'] ) ) {
				$dropdown_actions[] = [
					'label' => __( 'Статус', 'fstu' ),
					'class' => 'fstu-sailboats-status-btn',
				];
				$dropdown_actions[] = [
					'label' => __( 'Доставлено', 'fstu' ),
					'class' => 'fstu-sailboats-received-btn',
				];
				$dropdown_actions[] = [
					'label' => __( 'Продаж / вибуття', 'fstu' ),
					'class' => 'fstu-sailboats-sale-btn',
				];
			}

			if ( ! empty( $permissions['canPayments'] ) ) {
				$dropdown_actions[] = [
					'label' => __( 'Оплата', 'fstu' ),
					'class' => 'fstu-sailboats-payment-btn',
				];
			}

			if ( ! empty( $permissions['canNotify'] ) ) {
				$dropdown_actions[] = [
					'label' => __( 'Повідомлення', 'fstu' ),
					'class' => 'fstu-sailboats-notification-btn',
				];
			}

			if ( ! empty( $permissions['canHardDeleteAdmin'] ) ) {
				$dropdown_actions[] = [
					'label'  => __( 'Видалення', 'fstu' ),
					'class'  => 'fstu-sailboats-delete-btn',
					'danger' => true,
				];
			}

			$html  .= '<tr class="fstu-row">';
			$html  .= '<td class="fstu-td fstu-td--num">' . esc_html( (string) $number ) . '</td>';
			$html  .= '<td class="fstu-td fstu-td--name"><button type="button" class="fstu-sailboats-link-button fstu-sailboats-view-btn" data-sailboat-id="' . esc_attr( (string) $item_id ) . '">' . esc_html( '' !== $name ? $name : '—' ) . '</button></td>';
			$html  .= '<td class="fstu-td">' . $this->format_table_value( $item['registration_number'] ?? '' ) . '</td>';
			$html  .= '<td class="fstu-td">' . $this->format_table_value( $item['sail_number'] ?? '' ) . '</td>';
			$html  .= '<td class="fstu-td">' . $this->format_table_value( $item['region_name'] ?? '' ) . '</td>';
			$html  .= '<td class="fstu-td">' . $this->format_table_value( $item['owner_name'] ?? '' ) . '</td>';
			$html  .= '<td class="fstu-td">' . $this->format_table_value( $item['producer_name'] ?? '' ) . '</td>';
			$html  .= '<td class="fstu-td">' . $this->format_table_value( $item['status_name'] ?? '' ) . '</td>';

			if ( ! empty( $permissions['canFinance'] ) ) {
				$html .= '<td class="fstu-td">' . $this->format_table_value( $item['registration_date'] ?? '' ) . '</td>';
				$html .= '<td class="fstu-td fstu-td--finance">' . $this->format_table_value( $item['V1'] ?? '' ) . '</td>';
				$html .= '<td class="fstu-td fstu-td--finance">' . $this->format_table_value( $item['V2'] ?? '' ) . '</td>';
				$html .= '<td class="fstu-td fstu-td--finance">' . $this->format_table_value( $item['F1'] ?? '' ) . '</td>';
				$html .= '<td class="fstu-td fstu-td--finance">' . $this->format_table_value( $item['F2'] ?? '' ) . '</td>';
			}

			ob_start();
			include FSTU_PLUGIN_DIR . 'views/sailboats/partials/action-dropdown.php';
			$action_dropdown_html = (string) ob_get_clean();

			$html  .= '<td class="fstu-td fstu-td--actions">' . $action_dropdown_html . '</td>';
			$html  .= '</tr>';
		}

		return $html;
	}

	/**
	 * Форматує значення для комірки таблиці.
	 */
	private function format_table_value( $value ): string {
		$text = trim( (string) $value );

		return '' !== $text ? esc_html( $text ) : '<span class="fstu-text-muted">—</span>';
	}

	/**
	 * Повертає repository модуля.
	 */
	private function get_repository(): Sailboats_Repository {
		if ( null === $this->repository ) {
			$this->repository = new Sailboats_Repository();
		}

		return $this->repository;
	}

	/**
	 * Повертає сервіс протоколу модуля.
	 */
	private function get_protocol_service(): Sailboats_Protocol_Service {
		if ( null === $this->protocol_service ) {
			$this->protocol_service = new Sailboats_Protocol_Service();
		}

		return $this->protocol_service;
	}

	/**
	 * Повертає сервіс сповіщень модуля.
	 */
	private function get_notification_service(): Sailboats_Notification_Service {
		if ( null === $this->notification_service ) {
			$this->notification_service = new Sailboats_Notification_Service();
		}

		return $this->notification_service;
	}

	/**
	 * Повертає сервіс модуля.
	 */
	private function get_service(): Sailboats_Service {
		if ( null === $this->service ) {
			$this->service = new Sailboats_Service(
				$this->get_repository(),
				$this->get_protocol_service(),
				$this->get_notification_service()
			);
		}

		return $this->service;
	}

	/**
	 * Чи може користувач переглядати модуль.
	 */
	private function current_user_can_view(): bool {
		$permissions = Capabilities::get_sailboats_permissions();

		return ! empty( $permissions['canView'] );
	}

	/**
	 * Чи може користувач керувати модулем.
	 */
	private function current_user_can_manage(): bool {
		$permissions = Capabilities::get_sailboats_permissions();

		return ! empty( $permissions['canManage'] );
	}

	/**
	 * Чи може користувач подавати нові заявки модуля.
	 */
	private function current_user_can_submit(): bool {
		$permissions = Capabilities::get_sailboats_permissions();

		return ! empty( $permissions['canManage'] ) || ! empty( $permissions['canSubmit'] );
	}

	/**
	 * Чи може користувач видаляти записи модуля.
	 */
	private function current_user_can_delete(): bool {
		$permissions = Capabilities::get_sailboats_permissions();

		return ! empty( $permissions['canDelete'] );
	}

	/**
	 * Hard delete дозволено лише адміністратору.
	 */
	private function current_user_can_hard_delete_admin_only(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Чи може користувач переглядати протокол модуля.
	 */
	private function current_user_can_view_protocol(): bool {
		$permissions = Capabilities::get_sailboats_permissions();

		return ! empty( $permissions['canProtocol'] );
	}

	/**
	 * Чи може користувач керувати оплатами.
	 */
	private function current_user_can_manage_payments(): bool {
		$permissions = Capabilities::get_sailboats_permissions();

		return ! empty( $permissions['canPayments'] );
	}

	/**
	 * Чи може користувач змінювати статуси.
	 */
	private function current_user_can_manage_status(): bool {
		$permissions = Capabilities::get_sailboats_permissions();

		return ! empty( $permissions['canStatus'] );
	}

	/**
	 * Чи може користувач надсилати службові повідомлення.
	 */
	private function current_user_can_send_notifications(): bool {
		$permissions = Capabilities::get_sailboats_permissions();

		return ! empty( $permissions['canNotify'] );
	}

	/**
	 * Повертає прапорці прав поточного користувача.
	 *
	 * @return array<string,bool>
	 */
	private function get_permissions(): array {
		$permissions = Capabilities::get_sailboats_permissions();
		$permissions['canHardDeleteAdmin'] = $this->current_user_can_hard_delete_admin_only();

		return $permissions;
	}
}

