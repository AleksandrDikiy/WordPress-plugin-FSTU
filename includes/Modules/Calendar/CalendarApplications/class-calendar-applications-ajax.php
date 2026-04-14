<?php
namespace FSTU\Modules\Calendar\CalendarApplications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use FSTU\Core\Capabilities;
use FSTU\Modules\Calendar\Calendar_List;

/**
 * AJAX-обробники підмодуля Calendar_Applications.
 *
 * Version: 1.2.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarApplications
 */
class Calendar_Applications_Ajax {

	private const MAX_PER_PAGE = 50;

	private ?Calendar_Applications_Repository $repository = null;
	private ?Calendar_Applications_Protocol_Service $protocol_service = null;
	private ?Calendar_Applications_Service $service = null;

	public function init(): void {
		add_action( 'wp_ajax_fstu_calendar_get_applications', [ $this, 'handle_get_applications' ] );
		add_action( 'wp_ajax_fstu_calendar_get_application', [ $this, 'handle_get_application' ] );
		add_action( 'wp_ajax_fstu_calendar_create_application', [ $this, 'handle_create_application' ] );
		add_action( 'wp_ajax_fstu_calendar_update_application', [ $this, 'handle_update_application' ] );
		add_action( 'wp_ajax_fstu_calendar_change_application_status', [ $this, 'handle_change_application_status' ] );
		add_action( 'wp_ajax_fstu_calendar_delete_application', [ $this, 'handle_delete_application' ] );
		add_action( 'wp_ajax_fstu_calendar_get_application_participants', [ $this, 'handle_get_application_participants' ] );
		add_action( 'wp_ajax_fstu_calendar_add_application_participant', [ $this, 'handle_add_application_participant' ] );
		add_action( 'wp_ajax_fstu_calendar_remove_application_participant', [ $this, 'handle_remove_application_participant' ] );
		add_action( 'wp_ajax_fstu_calendar_create_participant_user', [ $this, 'handle_create_participant_user' ] );
		add_action( 'wp_ajax_fstu_calendar_get_applications_protocol', [ $this, 'handle_get_applications_protocol' ] );
	}

	private function get_repository(): Calendar_Applications_Repository {
		if ( null === $this->repository ) {
			$this->repository = new Calendar_Applications_Repository();
		}

		return $this->repository;
	}

	private function get_protocol_service(): Calendar_Applications_Protocol_Service {
		if ( null === $this->protocol_service ) {
			$this->protocol_service = new Calendar_Applications_Protocol_Service( $this->get_repository() );
		}

		return $this->protocol_service;
	}

	private function get_service(): Calendar_Applications_Service {
		if ( null === $this->service ) {
			$this->service = new Calendar_Applications_Service( $this->get_repository(), $this->get_protocol_service() );
		}

		return $this->service;
	}

	private function is_honeypot_triggered(): bool {
		$honeypot = sanitize_text_field( wp_unslash( $_POST['fstu_website'] ?? '' ) );

		return '' !== trim( $honeypot );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function get_form_data(): array {
		$phone = preg_replace( '/\D+/', '', (string) wp_unslash( $_POST['app_phone'] ?? '' ) );

		return [
			'calendar_id'    => absint( $_POST['calendar_id'] ?? 0 ),
			'status_app_id'  => absint( $_POST['status_app_id'] ?? $this->get_repository()->get_default_status_id() ),
			'sailboat_id'    => absint( $_POST['sailboat_id'] ?? 0 ),
			'mr_id'          => absint( $_POST['mr_id'] ?? 0 ),
			'sail_group_id'  => absint( $_POST['sail_group_id'] ?? 0 ),
			'region_id'      => absint( $_POST['region_id'] ?? 0 ),
			'app_name'       => mb_substr( sanitize_text_field( wp_unslash( $_POST['app_name'] ?? '' ) ), 0, 30 ),
			'app_number'     => mb_substr( sanitize_text_field( wp_unslash( $_POST['app_number'] ?? '' ) ), 0, 10 ),
			'app_phone'      => absint( $phone ),
		];
	}

	private function validate_form_data( array $data ): string {
		if ( (int) $data['calendar_id'] <= 0 ) {
			return 'Не вказано захід для заявки.';
		}

		if ( (int) $data['status_app_id'] <= 0 ) {
			return 'Оберіть статус заявки.';
		}

		if ( '' === (string) $data['app_name'] && (int) $data['sailboat_id'] <= 0 ) {
			return 'Вкажіть назву команди або пов’язане судно.';
		}

		return '';
	}

	/**
	 * @return array<string,mixed>
	 */
	private function get_existing_participant_form_data(): array {
		return [
			'application_id'        => absint( $_POST['application_id'] ?? 0 ),
			'user_id'               => absint( $_POST['user_id'] ?? 0 ),
			'participation_type_id' => absint( $_POST['participation_type_id'] ?? 0 ),
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	private function get_new_participant_form_data(): array {
		$phone = preg_replace( '/\D+/', '', (string) wp_unslash( $_POST['phone'] ?? '' ) );

		return [
			'last_name'             => mb_substr( sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) ), 0, 100 ),
			'first_name'            => mb_substr( sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) ), 0, 100 ),
			'patronymic'            => mb_substr( sanitize_text_field( wp_unslash( $_POST['patronymic'] ?? '' ) ), 0, 100 ),
			'email'                 => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
			'phone'                 => $phone,
			'city_id'               => absint( $_POST['city_id'] ?? 0 ),
			'unit_id'               => absint( $_POST['unit_id'] ?? 0 ),
			'participation_type_id' => absint( $_POST['participation_type_id'] ?? 0 ),
			'BirthDate'             => sanitize_text_field( wp_unslash( $_POST['birth_date'] ?? '' ) ),
			'Sex'                   => mb_substr( sanitize_text_field( wp_unslash( $_POST['sex'] ?? '' ) ), 0, 5 ),
			'Patronymic'            => mb_substr( sanitize_text_field( wp_unslash( $_POST['patronymic'] ?? '' ) ), 0, 100 ),
			'Phone'                 => $phone,
		];
	}

	private function get_permissions(): array {
		return Capabilities::get_calendar_applications_permissions();
	}

	private function can_manage_any(): bool {
		$permissions = $this->get_permissions();

		return ! empty( $permissions['canManage'] );
	}

	private function can_submit(): bool {
		$permissions = $this->get_permissions();

		return ! empty( $permissions['canSubmit'] ) || ! empty( $permissions['canManage'] );
	}

	public function handle_get_applications(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => 'Для перегляду заявок потрібно увійти в систему.' ] );
		}

		$calendar_id = absint( $_POST['calendar_id'] ?? 0 );
		$page        = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page    = min( max( 1, absint( $_POST['per_page'] ?? 10 ) ), self::MAX_PER_PAGE );
		$offset      = ( $page - 1 ) * $per_page;
		$search      = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );

		if ( $calendar_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Не обрано захід для перегляду заявок.' ] );
		}

		if ( ! $this->can_submit() ) {
			wp_send_json_error( [ 'message' => 'Немає прав для перегляду заявок.' ] );
		}

		$result = $this->get_repository()->get_applications( $calendar_id, $search, $per_page, $offset, get_current_user_id(), $this->can_manage_any() );
		$total  = (int) ( $result['total'] ?? 0 );

		wp_send_json_success(
			[
				'items'       => $result['items'] ?? [],
				'total'       => $total,
				'page'        => $page,
				'per_page'    => $per_page,
				'total_pages' => max( 1, (int) ceil( $total / max( 1, $per_page ) ) ),
			]
		);
	}

	public function handle_get_application(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => 'Для перегляду заявки потрібно увійти в систему.' ] );
		}

		$application_id = absint( $_POST['application_id'] ?? 0 );
		if ( $application_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Невірний ідентифікатор заявки.' ] );
		}

		$item = $this->get_repository()->get_application( $application_id );
		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => 'Заявку не знайдено.' ] );
		}

		if ( ! $this->can_manage_any() && (int) ( $item['UserCreate'] ?? 0 ) !== get_current_user_id() ) {
			wp_send_json_error( [ 'message' => 'Немає прав для перегляду цієї заявки.' ] );
		}

		wp_send_json_success( [ 'item' => $item ] );
	}

	public function handle_create_application(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() || ! $this->can_submit() ) {
			wp_send_json_error( [ 'message' => 'Недостатньо прав для створення заявки.' ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => 'Запит відхилено.' ] );
		}

		$data  = $this->get_form_data();
		$error = $this->validate_form_data( $data );
		if ( '' !== $error ) {
			wp_send_json_error( [ 'message' => $error ] );
		}

		$result = $this->get_service()->create_application( $data );
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( [ 'message' => $result['message'] ?? 'Сталася помилка створення заявки.' ] );
		}

		wp_send_json_success( $result );
	}

	public function handle_update_application(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() || ! $this->can_submit() ) {
			wp_send_json_error( [ 'message' => 'Недостатньо прав для редагування заявки.' ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => 'Запит відхилено.' ] );
		}

		$application_id = absint( $_POST['application_id'] ?? 0 );
		$data           = $this->get_form_data();
		$error          = $this->validate_form_data( $data );

		if ( $application_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Невірний ідентифікатор заявки.' ] );
		}

		if ( '' !== $error ) {
			wp_send_json_error( [ 'message' => $error ] );
		}

		$result = $this->get_service()->update_application( $application_id, $data, get_current_user_id(), $this->can_manage_any() );
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( [ 'message' => $result['message'] ?? 'Сталася помилка збереження заявки.' ] );
		}

		wp_send_json_success( $result );
	}

	public function handle_change_application_status(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() || ! $this->can_submit() ) {
			wp_send_json_error( [ 'message' => 'Недостатньо прав для зміни статусу заявки.' ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => 'Запит відхилено.' ] );
		}

		$application_id  = absint( $_POST['application_id'] ?? 0 );
		$target_status_id = absint( $_POST['target_status_id'] ?? 0 );

		if ( $application_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Невірний ідентифікатор заявки.' ] );
		}

		if ( $target_status_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Оберіть коректний цільовий статус заявки.' ] );
		}

		$result = $this->get_service()->change_application_status( $application_id, $target_status_id, get_current_user_id(), $this->can_manage_any() );
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( [ 'message' => $result['message'] ?? 'Сталася помилка зміни статусу заявки.' ] );
		}

		wp_send_json_success( $result );
	}

	public function handle_delete_application(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() || ! $this->can_submit() ) {
			wp_send_json_error( [ 'message' => 'Недостатньо прав для видалення заявки.' ] );
		}

		$application_id = absint( $_POST['application_id'] ?? 0 );
		if ( $application_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Невірний ідентифікатор заявки.' ] );
		}

		$result = $this->get_service()->delete_application( $application_id, get_current_user_id(), ! empty( $this->get_permissions()['canDelete'] ) );
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( [ 'message' => $result['message'] ?? 'Сталася помилка видалення заявки.' ] );
		}

		wp_send_json_success( $result );
	}

	public function handle_get_application_participants(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => 'Для перегляду учасників потрібно увійти в систему.' ] );
		}

		$application_id = absint( $_POST['application_id'] ?? 0 );
		if ( $application_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Невірний ідентифікатор заявки.' ] );
		}

		$item = $this->get_repository()->get_application( $application_id );
		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => 'Заявку не знайдено.' ] );
		}

		if ( ! $this->can_manage_any() && (int) ( $item['UserCreate'] ?? 0 ) !== get_current_user_id() ) {
			wp_send_json_error( [ 'message' => 'Немає прав для перегляду учасників цієї заявки.' ] );
		}

		$items = $this->get_repository()->get_application_participants( $application_id );
		wp_send_json_success( [ 'items' => $items ] );
	}

	public function handle_add_application_participant(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() || ! $this->can_submit() ) {
			wp_send_json_error( [ 'message' => 'Недостатньо прав для додавання учасника.' ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => 'Запит відхилено.' ] );
		}

		$data = $this->get_existing_participant_form_data();
		if ( $data['application_id'] <= 0 || $data['user_id'] <= 0 || $data['participation_type_id'] <= 0 ) {
			wp_send_json_error( [ 'message' => 'Заповніть коректно форму додавання учасника.' ] );
		}

		$result = $this->get_service()->add_participant( (int) $data['application_id'], (int) $data['user_id'], (int) $data['participation_type_id'], get_current_user_id(), $this->can_manage_any() );
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( [ 'message' => $result['message'] ?? 'Сталася помилка додавання учасника.' ] );
		}

		wp_send_json_success( $result );
	}

	public function handle_remove_application_participant(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() || ! $this->can_submit() ) {
			wp_send_json_error( [ 'message' => 'Недостатньо прав для видалення учасника.' ] );
		}

		$usercalendar_id = absint( $_POST['usercalendar_id'] ?? 0 );
		if ( $usercalendar_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Невірний ідентифікатор учасника заявки.' ] );
		}

		$result = $this->get_service()->remove_participant( $usercalendar_id, get_current_user_id(), $this->can_manage_any() );
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( [ 'message' => $result['message'] ?? 'Сталася помилка видалення учасника.' ] );
		}

		wp_send_json_success( $result );
	}

	public function handle_create_participant_user(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() || ! $this->can_submit() ) {
			wp_send_json_error( [ 'message' => 'Недостатньо прав для створення нового учасника.' ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => 'Запит відхилено.' ] );
		}

		$application_id = absint( $_POST['application_id'] ?? 0 );
		if ( $application_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Невірний ідентифікатор заявки.' ] );
		}

		$result = $this->get_service()->create_participant_user( $application_id, $this->get_new_participant_form_data(), get_current_user_id(), $this->can_manage_any() );
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( [ 'message' => $result['message'] ?? 'Сталася помилка створення нового учасника.' ] );
		}

		wp_send_json_success( $result );
	}

	public function handle_get_applications_protocol(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );

		$permissions = $this->get_permissions();
		if ( empty( $permissions['canProtocol'] ) ) {
			wp_send_json_error( [ 'message' => 'Немає прав для перегляду протоколу.' ] );
		}

		$search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$page     = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page = min( max( 1, absint( $_POST['per_page'] ?? 10 ) ), self::MAX_PER_PAGE );
		$offset   = ( $page - 1 ) * $per_page;
		$result   = $this->get_protocol_service()->get_protocol( $search, $per_page, $offset );
		$total    = (int) ( $result['total'] ?? 0 );

		wp_send_json_success(
			[
				'items'       => $result['items'] ?? [],
				'total'       => $total,
				'page'        => $page,
				'per_page'    => $per_page,
				'total_pages' => max( 1, (int) ceil( $total / max( 1, $per_page ) ) ),
			]
		);
	}
}

