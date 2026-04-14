<?php
namespace FSTU\Modules\Calendar\CalendarEvents;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use FSTU\Core\Capabilities;
use FSTU\Modules\Calendar\Calendar_List;

/**
 * AJAX-обробники підмодуля Calendar_Events.
 *
 * Version: 1.1.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarEvents
 */
class Calendar_Events_Ajax {

	private const MAX_PER_PAGE = 50;

	private ?Calendar_Events_Repository $repository = null;
	private ?Calendar_Events_Protocol_Service $protocol_service = null;
	private ?Calendar_Events_Service $service = null;

	public function init(): void {
		add_action( 'wp_ajax_fstu_calendar_get_events', [ $this, 'handle_get_events' ] );
		add_action( 'wp_ajax_nopriv_fstu_calendar_get_events', [ $this, 'handle_get_events' ] );
		add_action( 'wp_ajax_fstu_calendar_get_event', [ $this, 'handle_get_event' ] );
		add_action( 'wp_ajax_nopriv_fstu_calendar_get_event', [ $this, 'handle_get_event' ] );
		add_action( 'wp_ajax_fstu_calendar_get_calendar_month', [ $this, 'handle_get_calendar_month' ] );
		add_action( 'wp_ajax_nopriv_fstu_calendar_get_calendar_month', [ $this, 'handle_get_calendar_month' ] );
		add_action( 'wp_ajax_fstu_calendar_get_calendar_week', [ $this, 'handle_get_calendar_week' ] );
		add_action( 'wp_ajax_nopriv_fstu_calendar_get_calendar_week', [ $this, 'handle_get_calendar_week' ] );
		add_action( 'wp_ajax_fstu_calendar_create_event', [ $this, 'handle_create_event' ] );
		add_action( 'wp_ajax_fstu_calendar_update_event', [ $this, 'handle_update_event' ] );
		add_action( 'wp_ajax_fstu_calendar_delete_event', [ $this, 'handle_delete_event' ] );
		add_action( 'wp_ajax_fstu_calendar_get_events_protocol', [ $this, 'handle_get_events_protocol' ] );
	}

	/**
	 * Повертає repository.
	 */
	private function get_repository(): Calendar_Events_Repository {
		if ( null === $this->repository ) {
			$this->repository = new Calendar_Events_Repository();
		}

		return $this->repository;
	}

	/**
	 * Повертає protocol service.
	 */
	private function get_protocol_service(): Calendar_Events_Protocol_Service {
		if ( null === $this->protocol_service ) {
			$this->protocol_service = new Calendar_Events_Protocol_Service( $this->get_repository() );
		}

		return $this->protocol_service;
	}

	/**
	 * Повертає service.
	 */
	private function get_service(): Calendar_Events_Service {
		if ( null === $this->service ) {
			$this->service = new Calendar_Events_Service( $this->get_repository(), $this->get_protocol_service() );
		}

		return $this->service;
	}

	/**
	 * Повертає true, якщо honeypot спрацював.
	 */
	private function is_honeypot_triggered(): bool {
		$honeypot = sanitize_text_field( wp_unslash( $_POST['fstu_website'] ?? '' ) );

		return '' !== trim( $honeypot );
	}

	/**
	 * Повертає фільтри списку.
	 *
	 * @return array<string, mixed>
	 */
	private function get_filters_from_request(): array {
		return [
			'search'          => sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) ),
			'year'            => absint( $_POST['year'] ?? current_time( 'Y' ) ),
			'status_id'       => absint( $_POST['status_id'] ?? 0 ),
			'region_id'       => absint( $_POST['region_id'] ?? 0 ),
			'tourism_type_id' => absint( $_POST['tourism_type_id'] ?? 0 ),
			'event_type_id'   => absint( $_POST['event_type_id'] ?? 0 ),
		];
	}

	/**
	 * Повертає дані форми події.
	 *
	 * @return array<string, mixed>
	 */
	private function get_event_form_data(): array {
		return [
			'name'                => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
			'date_begin'          => sanitize_text_field( wp_unslash( $_POST['date_begin'] ?? '' ) ),
			'date_end'            => sanitize_text_field( wp_unslash( $_POST['date_end'] ?? '' ) ),
			'status_id'           => absint( $_POST['status_id'] ?? 0 ),
			'city_id'             => absint( $_POST['city_id'] ?? 0 ),
			'tourism_type_id'     => absint( $_POST['tourism_type_id'] ?? 0 ),
			'event_type_id'       => absint( $_POST['event_type_id'] ?? 0 ),
			'type_event_id'       => absint( $_POST['type_event_id'] ?? 0 ),
			'tour_type_id'        => absint( $_POST['tour_type_id'] ?? 0 ),
			'responsible_user_id' => absint( $_POST['responsible_user_id'] ?? get_current_user_id() ),
			'url_reglament'       => esc_url_raw( wp_unslash( $_POST['url_reglament'] ?? '' ) ),
			'url_protocol'        => esc_url_raw( wp_unslash( $_POST['url_protocol'] ?? '' ) ),
			'url_map'             => esc_url_raw( wp_unslash( $_POST['url_map'] ?? '' ) ),
			'url_report'          => esc_url_raw( wp_unslash( $_POST['url_report'] ?? '' ) ),
			'sail_group_type_id'  => absint( $_POST['sail_group_type_id'] ?? 0 ),
			'crew_weight'         => sanitize_text_field( wp_unslash( $_POST['crew_weight'] ?? '' ) ),
			'triangles'           => absint( $_POST['triangles'] ?? 0 ),
			'horse_tracking'      => absint( $_POST['horse_tracking'] ?? 0 ),
		];
	}

	/**
	 * Базова валідація форми події.
	 */
	private function validate_event_form_data( array $data ): string {
		if ( '' === $data['name'] ) {
			return 'Вкажіть назву заходу.';
		}

		if ( '' === $data['date_begin'] || '' === $data['date_end'] ) {
			return 'Вкажіть дату початку і завершення заходу.';
		}

		if ( strtotime( $data['date_begin'] ) > strtotime( $data['date_end'] ) ) {
			return 'Дата початку не може бути пізнішою за дату завершення.';
		}

		if ( $data['status_id'] <= 0 ) {
			return 'Оберіть статус заходу.';
		}

		if ( $data['city_id'] <= 0 ) {
			return 'Оберіть місто проведення.';
		}

		if ( $data['tourism_type_id'] <= 0 ) {
			return 'Оберіть вид туризму.';
		}

		if ( $data['event_type_id'] <= 0 ) {
			return 'Оберіть тип заходу.';
		}

		if ( $data['responsible_user_id'] <= 0 ) {
			return 'Вкажіть відповідального користувача.';
		}

		return '';
	}

	/**
	 * Повертає список заходів.
	 */
	public function handle_get_events(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );

		$page     = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page = min( max( 1, absint( $_POST['per_page'] ?? 10 ) ), self::MAX_PER_PAGE );
		$offset   = ( $page - 1 ) * $per_page;
		$filters  = $this->get_filters_from_request();
		$result   = $this->get_repository()->get_events( $filters, $per_page, $offset );
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

	/**
	 * Повертає один захід.
	 */
	public function handle_get_event(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );

		$event_id = absint( $_POST['event_id'] ?? 0 );
		if ( $event_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Невірний ідентифікатор заходу.' ] );
		}

		$item = $this->get_repository()->get_event( $event_id );
		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => 'Захід не знайдено.' ] );
		}

		wp_send_json_success( [ 'item' => $item ] );
	}

	/**
	 * Повертає події для month view.
	 */
	public function handle_get_calendar_month(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );

		$year  = max( 2000, absint( $_POST['year'] ?? current_time( 'Y' ) ) );
		$month = min( 12, max( 1, absint( $_POST['month'] ?? current_time( 'n' ) ) ) );
		$start = sprintf( '%04d-%02d-01 00:00:00', $year, $month );
		$end   = gmdate( 'Y-m-t 23:59:59', strtotime( $start ) );

		$items = $this->get_repository()->get_events_by_period( $start, $end, $this->get_filters_from_request() );
		wp_send_json_success( [ 'items' => $items, 'period_start' => $start, 'period_end' => $end ] );
	}

	/**
	 * Повертає події для week view.
	 */
	public function handle_get_calendar_week(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );

		$reference = sanitize_text_field( wp_unslash( $_POST['week_start'] ?? current_time( 'Y-m-d' ) ) );
		$timestamp = strtotime( $reference );
		if ( false === $timestamp ) {
			wp_send_json_error( [ 'message' => 'Невірна дата тижня.' ] );
		}

		$week_start = gmdate( 'Y-m-d 00:00:00', strtotime( 'monday this week', $timestamp ) );
		$week_end   = gmdate( 'Y-m-d 23:59:59', strtotime( 'sunday this week', $timestamp ) );
		$items      = $this->get_repository()->get_events_by_period( $week_start, $week_end, $this->get_filters_from_request() );

		wp_send_json_success( [ 'items' => $items, 'period_start' => $week_start, 'period_end' => $week_end ] );
	}

	/**
	 * Створює захід.
	 */
	public function handle_create_event(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );
		$permissions = Capabilities::get_calendar_events_permissions();

		if ( empty( $permissions['canManage'] ) ) {
			wp_send_json_error( [ 'message' => 'Недостатньо прав для додавання заходу.' ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => 'Запит відхилено.' ] );
		}

		$data  = $this->get_event_form_data();
		$error = $this->validate_event_form_data( $data );
		if ( '' !== $error ) {
			wp_send_json_error( [ 'message' => $error ] );
		}

		$result = $this->get_service()->create_event( $data );
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( [ 'message' => $result['message'] ?? 'Сталася помилка збереження заходу.' ] );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Оновлює захід.
	 */
	public function handle_update_event(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );
		$permissions = Capabilities::get_calendar_events_permissions();

		if ( empty( $permissions['canManage'] ) ) {
			wp_send_json_error( [ 'message' => 'Недостатньо прав для редагування заходу.' ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => 'Запит відхилено.' ] );
		}

		$event_id = absint( $_POST['event_id'] ?? 0 );
		$data     = $this->get_event_form_data();
		$error    = $this->validate_event_form_data( $data );
		if ( $event_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Невірний ідентифікатор заходу.' ] );
		}
		if ( '' !== $error ) {
			wp_send_json_error( [ 'message' => $error ] );
		}

		$result = $this->get_service()->update_event(
			$event_id,
			$data,
			get_current_user_id(),
			! empty( $permissions['canManageAny'] )
		);

		if ( empty( $result['success'] ) ) {
			wp_send_json_error( [ 'message' => $result['message'] ?? 'Сталася помилка збереження заходу.' ] );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Видаляє захід.
	 */
	public function handle_delete_event(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => 'Недостатньо прав для видалення заходу.' ] );
		}

		$permissions = Capabilities::get_calendar_events_permissions();

		$event_id = absint( $_POST['event_id'] ?? 0 );
		if ( $event_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Невірний ідентифікатор заходу.' ] );
		}

		$result = $this->get_service()->delete_event(
			$event_id,
			get_current_user_id(),
			! empty( $permissions['canDelete'] )
		);

		if ( empty( $result['success'] ) ) {
			wp_send_json_error( $result );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Повертає протокол підмодуля подій.
	 */
	public function handle_get_events_protocol(): void {
		check_ajax_referer( Calendar_List::NONCE_ACTION, 'nonce' );
		$permissions = Capabilities::get_calendar_events_permissions();

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

