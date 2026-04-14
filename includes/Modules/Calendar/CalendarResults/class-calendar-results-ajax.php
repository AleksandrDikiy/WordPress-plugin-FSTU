<?php
namespace FSTU\Modules\Calendar\CalendarResults;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX-обробники підмодуля Calendar_Results.
 *
 * Version: 1.1.1
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarResults
 */
class Calendar_Results_Ajax {

	private const MAX_PER_PAGE = 50;

	private ?Calendar_Results_Repository $repository = null;
	private ?Calendar_Results_Protocol_Service $protocol_service = null;
	private ?Calendar_Results_Service $service = null;

	public function init(): void {
		add_action( 'wp_ajax_fstu_calendar_get_races', [ $this, 'handle_get_races' ] );
		add_action( 'wp_ajax_nopriv_fstu_calendar_get_races', [ $this, 'handle_get_races' ] );
		add_action( 'wp_ajax_fstu_calendar_get_race', [ $this, 'handle_get_race' ] );
		add_action( 'wp_ajax_nopriv_fstu_calendar_get_race', [ $this, 'handle_get_race' ] );
		add_action( 'wp_ajax_fstu_calendar_create_race', [ $this, 'handle_create_race' ] );
		add_action( 'wp_ajax_fstu_calendar_update_race', [ $this, 'handle_update_race' ] );
		add_action( 'wp_ajax_fstu_calendar_delete_race', [ $this, 'handle_delete_race' ] );
		add_action( 'wp_ajax_fstu_calendar_update_race_protocol', [ $this, 'handle_update_race_protocol' ] );
		add_action( 'wp_ajax_fstu_calendar_recalculate_race_results', [ $this, 'handle_recalculate_race_results' ] );
		add_action( 'wp_ajax_fstu_calendar_get_results_protocol', [ $this, 'handle_get_results_protocol' ] );
	}

	private function get_repository(): Calendar_Results_Repository {
		if ( null === $this->repository ) {
			$this->repository = new Calendar_Results_Repository();
		}

		return $this->repository;
	}

	private function get_protocol_service(): Calendar_Results_Protocol_Service {
		if ( null === $this->protocol_service ) {
			$this->protocol_service = new Calendar_Results_Protocol_Service( $this->get_repository() );
		}

		return $this->protocol_service;
	}

	private function get_service(): Calendar_Results_Service {
		if ( null === $this->service ) {
			$this->service = new Calendar_Results_Service( $this->get_repository(), $this->get_protocol_service() );
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
		return [
			'calendar_id'       => absint( $_POST['calendar_id'] ?? 0 ),
			'race_date'         => sanitize_text_field( wp_unslash( $_POST['race_date'] ?? '' ) ),
			'race_number'       => absint( $_POST['race_number'] ?? 0 ),
			'race_name'         => mb_substr( sanitize_text_field( wp_unslash( $_POST['race_name'] ?? '' ) ), 0, 150 ),
			'race_type_id'      => absint( $_POST['race_type_id'] ?? 0 ),
			'race_description'  => mb_substr( sanitize_textarea_field( wp_unslash( $_POST['race_description'] ?? '' ) ), 0, 500 ),
		];
	}

	private function validate_form_data( array $data ): string {
		if ( (int) $data['calendar_id'] <= 0 ) {
			return 'Не обрано захід для перегону.';
		}

		if ( '' === (string) $data['race_date'] || false === strtotime( (string) $data['race_date'] ) ) {
			return 'Вкажіть коректну дату перегону.';
		}

		if ( '' === trim( (string) $data['race_name'] ) && (int) $data['race_number'] <= 0 ) {
			return 'Вкажіть назву перегону або його номер.';
		}

		return '';
	}

	private function get_permissions(): array {
		return \FSTU\Core\Capabilities::get_calendar_results_permissions();
	}

	private function can_manage_any(): bool {
		$permissions = $this->get_permissions();

		return ! empty( $permissions['canManage'] );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function get_protocol_items_from_request(): array {
		$raw_items = wp_unslash( $_POST['protocol_items'] ?? '[]' );
		$decoded   = json_decode( is_string( $raw_items ) ? $raw_items : '[]', true );

		if ( ! is_array( $decoded ) ) {
			return [];
		}

		$items = [];
		foreach ( $decoded as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$items[] = [
				'protocol_id'     => absint( $item['protocol_id'] ?? 0 ),
				'protocol_place'  => absint( $item['protocol_place'] ?? 0 ),
				'protocol_start'  => mb_substr( sanitize_text_field( (string) ( $item['protocol_start'] ?? '' ) ), 0, 50 ),
				'protocol_finish' => mb_substr( sanitize_text_field( (string) ( $item['protocol_finish'] ?? '' ) ), 0, 50 ),
				'protocol_result' => mb_substr( sanitize_text_field( (string) ( $item['protocol_result'] ?? '' ) ), 0, 100 ),
				'protocol_note'   => mb_substr( sanitize_textarea_field( (string) ( $item['protocol_note'] ?? '' ) ), 0, 250 ),
			];
		}

		return $items;
	}

	public function handle_get_races(): void {
		check_ajax_referer( \FSTU\Modules\Calendar\Calendar_List::NONCE_ACTION, 'nonce' );

		$calendar_id = absint( $_POST['calendar_id'] ?? 0 );
		$page        = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page    = min( max( 1, absint( $_POST['per_page'] ?? 10 ) ), self::MAX_PER_PAGE );
		$offset      = ( $page - 1 ) * $per_page;
		$search      = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );

		if ( $calendar_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Не обрано захід для перегляду перегонів.' ] );
		}

		$result = $this->get_repository()->get_races( $calendar_id, $search, $per_page, $offset );
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

	public function handle_get_race(): void {
		check_ajax_referer( \FSTU\Modules\Calendar\Calendar_List::NONCE_ACTION, 'nonce' );

		$race_id = absint( $_POST['race_id'] ?? 0 );
		if ( $race_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Невірний ідентифікатор перегону.' ] );
		}

		$item = $this->get_repository()->get_race_payload( $race_id );
		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => 'Перегін не знайдено.' ] );
		}

		wp_send_json_success( [ 'item' => $item ] );
	}

	public function handle_create_race(): void {
		check_ajax_referer( \FSTU\Modules\Calendar\Calendar_List::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() || ! $this->can_manage_any() ) {
			wp_send_json_error( [ 'message' => 'Недостатньо прав для додавання перегону.' ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => 'Запит відхилено.' ] );
		}

		$data  = $this->get_form_data();
		$error = $this->validate_form_data( $data );
		if ( '' !== $error ) {
			wp_send_json_error( [ 'message' => $error ] );
		}

		$result = $this->get_service()->create_race( $data );
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( [ 'message' => $result['message'] ?? 'Сталася помилка створення перегону.' ] );
		}

		wp_send_json_success( $result );
	}

	public function handle_update_race(): void {
		check_ajax_referer( \FSTU\Modules\Calendar\Calendar_List::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() || ! $this->can_manage_any() ) {
			wp_send_json_error( [ 'message' => 'Недостатньо прав для редагування перегону.' ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => 'Запит відхилено.' ] );
		}

		$race_id = absint( $_POST['race_id'] ?? 0 );
		$data    = $this->get_form_data();
		$error   = $this->validate_form_data( $data );

		if ( $race_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Невірний ідентифікатор перегону.' ] );
		}

		if ( '' !== $error ) {
			wp_send_json_error( [ 'message' => $error ] );
		}

		$result = $this->get_service()->update_race( $race_id, $data, get_current_user_id(), $this->can_manage_any() );
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( [ 'message' => $result['message'] ?? 'Сталася помилка збереження перегону.' ] );
		}

		wp_send_json_success( $result );
	}

	public function handle_delete_race(): void {
		check_ajax_referer( \FSTU\Modules\Calendar\Calendar_List::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => 'Недостатньо прав для видалення перегону.' ] );
		}

		$race_id = absint( $_POST['race_id'] ?? 0 );
		if ( $race_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Невірний ідентифікатор перегону.' ] );
		}

		$result = $this->get_service()->delete_race( $race_id, get_current_user_id(), ! empty( $this->get_permissions()['canDelete'] ) );
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( [ 'message' => $result['message'] ?? 'Сталася помилка видалення перегону.' ] );
		}

		wp_send_json_success( $result );
	}

	public function handle_update_race_protocol(): void {
		check_ajax_referer( \FSTU\Modules\Calendar\Calendar_List::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() || ! $this->can_manage_any() ) {
			wp_send_json_error( [ 'message' => 'Недостатньо прав для редагування фінішного протоколу.' ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => 'Запит відхилено.' ] );
		}

		$race_id = absint( $_POST['race_id'] ?? 0 );
		$items   = $this->get_protocol_items_from_request();

		if ( $race_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Невірний ідентифікатор перегону.' ] );
		}

		if ( empty( $items ) ) {
			wp_send_json_error( [ 'message' => 'Немає даних для збереження фінішного протоколу.' ] );
		}

		$result = $this->get_service()->update_race_protocol( $race_id, $items, get_current_user_id(), $this->can_manage_any() );
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( [ 'message' => $result['message'] ?? 'Сталася помилка збереження фінішного протоколу.' ] );
		}

		wp_send_json_success( $result );
	}

	public function handle_recalculate_race_results(): void {
		check_ajax_referer( \FSTU\Modules\Calendar\Calendar_List::NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() || ! $this->can_manage_any() ) {
			wp_send_json_error( [ 'message' => 'Недостатньо прав для перерахунку результатів.' ] );
		}

		$race_id = absint( $_POST['race_id'] ?? 0 );
		if ( $race_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Невірний ідентифікатор перегону.' ] );
		}

		$result = $this->get_service()->recalculate_results( $race_id, get_current_user_id(), $this->can_manage_any() );
		if ( empty( $result['success'] ) ) {
			wp_send_json_error( [ 'message' => $result['message'] ?? 'Сталася помилка перерахунку результатів.' ] );
		}

		wp_send_json_success( $result );
	}

	public function handle_get_results_protocol(): void {
		check_ajax_referer( \FSTU\Modules\Calendar\Calendar_List::NONCE_ACTION, 'nonce' );

		$permissions = $this->get_permissions();
		if ( empty( $permissions['canProtocol'] ) ) {
			wp_send_json_error( [ 'message' => 'Немає прав для перегляду протоколу результатів.' ] );
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

