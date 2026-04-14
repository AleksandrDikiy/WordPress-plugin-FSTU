<?php
namespace FSTU\Modules\Calendar\CalendarRoutes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use FSTU\Core\Capabilities;
use FSTU\Modules\Calendar\Calendar_List;

/**
 * AJAX-обробники підмодуля Calendar_Routes.
 *
 * Version: 1.0.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarRoutes
 */
class Calendar_Routes_Ajax {

	private const MAX_PER_PAGE = 50;

	private ?Calendar_Routes_Repository $repository = null;
	private ?Calendar_Routes_Protocol_Service $protocol_service = null;
	private ?Calendar_Routes_MKK_Service $mkk_service = null;
	private ?Calendar_Routes_Service $service = null;

	public function init(): void {
		add_action( 'wp_ajax_fstu_calendar_get_routes', [ $this, 'handle_get_routes' ] );
		add_action( 'wp_ajax_fstu_calendar_get_route', [ $this, 'handle_get_route' ] );
		add_action( 'wp_ajax_fstu_calendar_create_route', [ $this, 'handle_create_route' ] );
		add_action( 'wp_ajax_fstu_calendar_update_route', [ $this, 'handle_update_route' ] );
		add_action( 'wp_ajax_fstu_calendar_delete_route', [ $this, 'handle_delete_route' ] );
		add_action( 'wp_ajax_fstu_calendar_send_route_to_mkk', [ $this, 'handle_send_route_to_mkk' ] );
		add_action( 'wp_ajax_fstu_calendar_review_route_mkk', [ $this, 'handle_review_route_mkk' ] );
		add_action( 'wp_ajax_fstu_calendar_get_routes_protocol', [ $this, 'handle_get_routes_protocol' ] );
	}

	private function get_repository(): Calendar_Routes_Repository {
		if ( null === $this->repository ) {
			$this->repository = new Calendar_Routes_Repository();
		}

		return $this->repository;
	}

	private function get_protocol_service(): Calendar_Routes_Protocol_Service {
		if ( null === $this->protocol_service ) {
			$this->protocol_service = new Calendar_Routes_Protocol_Service( $this->get_repository() );
		}

		return $this->protocol_service;
	}

	private function get_mkk_service(): Calendar_Routes_MKK_Service {
		if ( null === $this->mkk_service ) {
			$this->mkk_service = new Calendar_Routes_MKK_Service( $this->get_repository() );
		}

		return $this->mkk_service;
	}

	private function get_service(): Calendar_Routes_Service {
		if ( null === $this->service ) {
			$this->service = new Calendar_Routes_Service( $this->get_repository(), $this->get_protocol_service(), $this->get_mkk_service() );
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
	private function get_route_form_data(): array {
		return [
			'calendar_id'         => absint( $_POST['calendar_id'] ?? 0 ),
			'vehicle_id'          => absint( $_POST['vehicle_id'] ?? 0 ),
			'pathtrip_date'       => sanitize_text_field( wp_unslash( $_POST['pathtrip_date'] ?? '' ) ),
			'pathtrip_note'       => mb_substr( sanitize_text_field( wp_unslash( $_POST['pathtrip_note'] ?? '' ) ), 0, 200 ),
			'pathtrip_distance'   => absint( $_POST['pathtrip_distance'] ?? 0 ),
			'pathtrip_url_treck'  => esc_url_raw( wp_unslash( $_POST['pathtrip_url_treck'] ?? '' ) ),
		];
	}

	private function validate_route_form_data( array $data ): string {
		if ( (int) $data['calendar_id'] <= 0 ) {
			return 'Не обрано захід для маршруту.';
		}

		if ( '' === (string) $data['pathtrip_date'] || false === strtotime( (string) $data['pathtrip_date'] ) ) {
			return 'Вкажіть коректну дату ділянки маршруту.';
		}

		if ( '' === (string) $data['pathtrip_note'] ) {
			return 'Вкажіть відрізок маршруту або опис перешкоди.';
		}

		if ( (int) $data['pathtrip_distance'] <= 0 ) {
			return 'Вкажіть коректну відстань у кілометрах.';
		}

		return '';
	}

	private function get_permissions(): array {
		return Capabilities::get_calendar_routes_permissions();
	}

	private function can_manage_routes(): bool {
		$permissions = $this->get_permissions();

		return ! empty( $permissions['canManage'] );
	}

	private function can_delete_routes(): bool {
		$permissions = $this->get_permissions();

		return ! empty( $permissions['canDelete'] ) || ! empty( $permissions['canManage'] );
	}

	private function can_review_mkk(): bool {
		$permissions = $this->get_permissions();

		return ! empty( $permissions['canReviewMkk'] );
	}

	private function ensure_logged_in(): void {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => 'Для роботи з маршрутами потрібно увійти в систему.' ] );
		}
	}

	public function handle_get_routes(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );
		$this->ensure_logged_in();

		$calendar_id = absint( $_POST['calendar_id'] ?? 0 );
		$page        = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page    = min( max( 1, absint( $_POST['per_page'] ?? 10 ) ), self::MAX_PER_PAGE );
		$offset      = ( $page - 1 ) * $per_page;
		$search      = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );

		if ( $calendar_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Не обрано захід для перегляду маршруту.' ] );
		}

		$route_payload = $this->get_service()->get_route_payload( $calendar_id, get_current_user_id(), $this->can_manage_routes(), $this->can_review_mkk() );
		if ( ! is_array( $route_payload ) ) {
			wp_send_json_error( [ 'message' => 'Немає прав для перегляду цього маршруту.' ] );
		}

		$result = $this->get_repository()->get_route_segments( $calendar_id, $search, $per_page, $offset );
		$total  = (int) ( $result['total'] ?? 0 );

		wp_send_json_success(
			[
				'items'          => $result['items'] ?? [],
				'total'          => $total,
				'page'           => $page,
				'per_page'       => $per_page,
				'total_pages'    => max( 1, (int) ceil( $total / max( 1, $per_page ) ) ),
				'total_distance' => (int) ( $result['total_distance'] ?? 0 ),
				'overview'       => $route_payload['overview'] ?? [],
				'apptravel'      => $route_payload['apptravel'] ?? [],
				'permissions'    => $route_payload['permissions'] ?? [],
				'verifications'  => $route_payload['verifications'] ?? [],
			]
		);
	}

	public function handle_get_route(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );
		$this->ensure_logged_in();

		$calendar_id = absint( $_POST['calendar_id'] ?? 0 );
		if ( $calendar_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Невірний ідентифікатор маршруту.' ] );
		}

		$route_payload = $this->get_service()->get_route_payload( $calendar_id, get_current_user_id(), $this->can_manage_routes(), $this->can_review_mkk() );
		if ( ! is_array( $route_payload ) ) {
			wp_send_json_error( [ 'message' => 'Немає прав для перегляду цього маршруту.' ] );
		}

		wp_send_json_success( $route_payload );
	}

	public function handle_create_route(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );
		$this->ensure_logged_in();

		if ( ! $this->can_manage_routes() ) {
			wp_send_json_error( [ 'message' => 'Недостатньо прав для додавання ділянки маршруту.' ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => 'Запит відхилено.' ] );
		}

		$data  = $this->get_route_form_data();
		$error = $this->validate_route_form_data( $data );
		if ( '' !== $error ) {
			wp_send_json_error( [ 'message' => $error ] );
		}

		$result = $this->get_service()->create_pathtrip( $data, get_current_user_id(), $this->can_manage_routes() );
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( [ 'message' => $result['message'] ?? 'Сталася помилка створення ділянки маршруту.' ] );
		}

		wp_send_json_success( $result );
	}

	public function handle_update_route(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );
		$this->ensure_logged_in();

		if ( ! $this->can_manage_routes() ) {
			wp_send_json_error( [ 'message' => 'Недостатньо прав для редагування маршруту.' ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => 'Запит відхилено.' ] );
		}

		$pathtrip_id = absint( $_POST['pathtrip_id'] ?? 0 );
		$data        = $this->get_route_form_data();
		$error       = $this->validate_route_form_data( $data );

		if ( $pathtrip_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Невірний ідентифікатор ділянки маршруту.' ] );
		}

		if ( '' !== $error ) {
			wp_send_json_error( [ 'message' => $error ] );
		}

		$result = $this->get_service()->update_pathtrip( $pathtrip_id, $data, get_current_user_id(), $this->can_manage_routes() );
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( [ 'message' => $result['message'] ?? 'Сталася помилка оновлення маршруту.' ] );
		}

		wp_send_json_success( $result );
	}

	public function handle_delete_route(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );
		$this->ensure_logged_in();

		$pathtrip_id = absint( $_POST['pathtrip_id'] ?? 0 );
		if ( $pathtrip_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Невірний ідентифікатор ділянки маршруту.' ] );
		}

		$result = $this->get_service()->delete_pathtrip( $pathtrip_id, get_current_user_id(), $this->can_delete_routes() );
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( [ 'message' => $result['message'] ?? 'Сталася помилка видалення маршруту.' ] );
		}

		wp_send_json_success( $result );
	}

	public function handle_send_route_to_mkk(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );
		$this->ensure_logged_in();

		if ( ! $this->can_manage_routes() ) {
			wp_send_json_error( [ 'message' => 'Недостатньо прав для відправлення маршруту до МКК.' ] );
		}

		$calendar_id = absint( $_POST['calendar_id'] ?? 0 );
		if ( $calendar_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Не обрано маршрут для погодження.' ] );
		}

		$result = $this->get_service()->send_to_mkk( $calendar_id, get_current_user_id(), $this->can_manage_routes() );
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( [ 'message' => $result['message'] ?? 'Сталася помилка відправлення маршруту до МКК.' ] );
		}

		wp_send_json_success( $result );
	}

	public function handle_review_route_mkk(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );
		$this->ensure_logged_in();

		if ( ! $this->can_review_mkk() ) {
			wp_send_json_error( [ 'message' => 'Недостатньо прав для MKK review.' ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => 'Запит відхилено.' ] );
		}

		$calendar_id      = absint( $_POST['calendar_id'] ?? 0 );
		$decision_status  = absint( $_POST['verification_status'] ?? 0 );
		$verification_note = mb_substr( sanitize_textarea_field( wp_unslash( $_POST['verification_note'] ?? '' ) ), 0, 250 );

		if ( $calendar_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Не обрано маршрут для погодження.' ] );
		}

		if ( ! in_array( $decision_status, [ 1, 2 ], true ) ) {
			wp_send_json_error( [ 'message' => 'Оберіть коректне рішення МКК.' ] );
		}

		if ( 2 === $decision_status && '' === trim( $verification_note ) ) {
			wp_send_json_error( [ 'message' => 'Для рішення про помилки вкажіть примітку МКК.' ] );
		}

		$result = $this->get_service()->review_route( $calendar_id, $decision_status, $verification_note, get_current_user_id(), $this->can_review_mkk() );
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( [ 'message' => $result['message'] ?? 'Сталася помилка збереження рішення МКК.' ] );
		}

		wp_send_json_success( $result );
	}

	public function handle_get_routes_protocol(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );

		$permissions = $this->get_permissions();
		if ( empty( $permissions['canProtocol'] ) ) {
			wp_send_json_error( [ 'message' => 'Немає прав для перегляду протоколу маршрутів.' ] );
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
