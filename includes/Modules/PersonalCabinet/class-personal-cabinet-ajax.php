<?php
namespace FSTU\Modules\PersonalCabinet;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX-обробники модуля «Особистий кабінет ФСТУ».
 *
 * Version:     1.4.0
 * Date_update: 2026-04-09
 *
 * @package FSTU\Modules\PersonalCabinet
 */
class Personal_Cabinet_Ajax {

	private const HONEYPOT_FIELD = 'fstu_website';

	/**
	 * Зарезервовані mutation-action-и для наступних етапів.
	 *
	 * @var array<int,string>
	 */
	private const MUTATION_ACTIONS = [
		'update_profile',
		'upload_photo',
		'add_club',
		'delete_club',
		'add_city',
		'delete_city',
		'add_unit',
		'delete_unit',
		'add_tourism_type',
		'delete_tourism_type',
		'update_experience_proof',
		'create_member_card_application',
		'upload_dues_receipt',
		'create_sail_dues',
	];

	private Personal_Cabinet_Service $service;
	private Personal_Cabinet_Payments_Service $payments_service;

	public function __construct( ?Personal_Cabinet_Service $service = null, ?Personal_Cabinet_Payments_Service $payments_service = null ) {
		$this->service          = $service ?? new Personal_Cabinet_Service();
		$this->payments_service = $payments_service ?? new Personal_Cabinet_Payments_Service();
	}

	public function init(): void {
		add_action( 'wp_ajax_fstu_personal_cabinet_get_profile', [ $this, 'handle_get_profile' ] );
		add_action( 'wp_ajax_fstu_personal_cabinet_get_protocol', [ $this, 'handle_get_protocol' ] );
		add_action( 'wp_ajax_fstu_personal_cabinet_get_portmone_payload', [ $this, 'handle_get_portmone_payload' ] );
		add_action( 'wp_ajax_fstu_personal_cabinet_upload_dues_receipt', [ $this, 'handle_upload_dues_receipt' ] );
		add_action( 'admin_post_fstu_personal_cabinet_portmone_return', [ $this, 'handle_portmone_return' ] );
		add_action( 'admin_post_nopriv_fstu_personal_cabinet_portmone_return', [ $this, 'handle_portmone_return' ] );
	}

	public function handle_get_profile(): void {
		$this->verify_nonce();

		$profile_user_id = $this->sanitize_profile_user_id();
		if ( $profile_user_id <= 0 ) {
			$this->send_safe_error( __( 'Профіль користувача не знайдено.', 'fstu' ), 404 );
		}

		$permissions     = Capabilities::get_personal_cabinet_permissions( $profile_user_id );
		$this->assert_permission( ! empty( $permissions['canView'] ), __( 'Немає доступу до особистого кабінету.', 'fstu' ), 403 );
		$payload         = $this->service->get_profile_payload( $profile_user_id, $permissions );

		wp_send_json_success( $payload );
	}

	public function handle_get_protocol(): void {
		$this->verify_nonce();
		$this->assert_authenticated();
		$this->assert_permission( Capabilities::current_user_can_view_personal_cabinet_protocol(), __( 'Немає прав для перегляду протоколу.', 'fstu' ), 403 );

		$page     = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page = max( 1, absint( $_POST['per_page'] ?? Personal_Cabinet_Protocol_Service::get_default_per_page() ) );
		$search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$payload  = $this->service->get_protocol_payload( $search, $page, $per_page );

		wp_send_json_success( $payload );
	}

	public function handle_get_portmone_payload(): void {
		$this->verify_nonce();
		$this->assert_authenticated();
		$this->validate_honeypot();

		$profile_user_id = $this->sanitize_profile_user_id();
		if ( $profile_user_id <= 0 ) {
			$this->send_safe_error( __( 'Профіль користувача не знайдено.', 'fstu' ), 404 );
		}

		$permissions = Capabilities::get_personal_cabinet_permissions( $profile_user_id );
		$this->assert_permission( ! empty( $permissions['canPayOnline'] ), __( 'Немає прав для онлайн-оплати внеску.', 'fstu' ), 403 );

		$payload = $this->payments_service->build_portmone_payload( $profile_user_id, $permissions );
		if ( is_wp_error( $payload ) ) {
			$this->send_safe_error( $payload->get_error_message(), 400 );
		}

		wp_send_json_success( $payload );
	}

	public function handle_upload_dues_receipt(): void {
		$this->verify_nonce();
		$this->assert_authenticated();
		$this->validate_honeypot();

		$profile_user_id = $this->sanitize_profile_user_id();
		if ( $profile_user_id <= 0 ) {
			$this->send_safe_error( __( 'Профіль користувача не знайдено.', 'fstu' ), 404 );
		}

		$permissions = Capabilities::get_personal_cabinet_permissions( $profile_user_id );
		$this->assert_permission( ! empty( $permissions['canManageDues'] ), __( 'Немає прав для додавання квитанції.', 'fstu' ), 403 );

		$year_id = absint( $_POST['year_id'] ?? 0 );
		$summa_raw = sanitize_text_field( wp_unslash( $_POST['summa'] ?? '' ) );
		$summa = (float) str_replace( ',', '.', $summa_raw );
		$url   = esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) );

		$result = $this->service->save_dues_receipt( $profile_user_id, $permissions, $year_id, $summa, $url );
		if ( is_wp_error( $result ) ) {
			$this->send_safe_error( $result->get_error_message(), 400 );
		}

		wp_send_json_success( $result );
	}

	public function handle_portmone_return(): void {
		$result       = sanitize_key( wp_unslash( $_GET['result'] ?? '' ) );
		$redirect_url = $this->payments_service->process_portmone_return( $result, $_GET, $_POST );

		wp_safe_redirect( '' !== $redirect_url ? $redirect_url : home_url( '/' ) );
		exit;
	}

	private function verify_nonce(): void {
		check_ajax_referer( Personal_Cabinet_List::NONCE_ACTION, 'nonce' );
	}

	private function assert_authenticated(): void {
		if ( is_user_logged_in() ) {
			return;
		}

		$this->send_safe_error( __( 'Потрібна авторизація.', 'fstu' ), 401 );
	}

	private function assert_permission( bool $allowed, string $message, int $status = 403 ): void {
		if ( $allowed ) {
			return;
		}

		$this->send_safe_error( $message, $status );
	}

	private function validate_honeypot( string $field = self::HONEYPOT_FIELD ): void {
		$honeypot = sanitize_text_field( wp_unslash( $_POST[ $field ] ?? '' ) );

		if ( '' === $honeypot ) {
			return;
		}

		$this->send_safe_error( __( 'Запит відхилено системою захисту форми.', 'fstu' ), 400 );
	}

	private function send_safe_error( string $message, int $status = 400 ): void {
		wp_send_json_error( [ 'message' => $message ], $status );
	}

	private function sanitize_profile_user_id(): int {
		$profile_user_id = absint( $_POST['profile_user_id'] ?? 0 );

		if ( $profile_user_id > 0 ) {
			return get_userdata( $profile_user_id ) instanceof \WP_User ? $profile_user_id : 0;
		}

		$profile_user_id = get_current_user_id();

		return $profile_user_id > 0 && get_userdata( $profile_user_id ) instanceof \WP_User ? $profile_user_id : 0;
	}

	/**
	 * @return array<int,string>
	 */
	public static function get_mutation_action_whitelist(): array {
		return self::MUTATION_ACTIONS;
	}
}

