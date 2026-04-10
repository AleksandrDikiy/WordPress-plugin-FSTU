<?php
namespace FSTU\Modules\PersonalCabinet;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX-обробники модуля «Особистий кабінет ФСТУ».
 *
 * Version:     1.5.0
 * Date_update: 2026-04-10
 */
class Personal_Cabinet_Ajax {

	private const HONEYPOT_FIELD = 'fstu_website';

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

		// Реєстрація нових методів
		add_action( 'wp_ajax_fstu_personal_cabinet_upload_photo', [ $this, 'handle_upload_photo' ] );
		add_action( 'wp_ajax_fstu_personal_cabinet_update_private_data', [ $this, 'handle_update_private_data' ] );
		add_action( 'wp_ajax_fstu_personal_cabinet_update_consent', [ $this, 'handle_update_consent' ] );
	}

	public function handle_get_profile(): void {
		$this->verify_nonce();
		$profile_user_id = $this->sanitize_profile_user_id();
		if ( $profile_user_id <= 0 ) { $this->send_safe_error( 'Профіль не знайдено', 404 ); }

		$permissions = Capabilities::get_personal_cabinet_permissions( $profile_user_id );
		$payload = $this->service->get_profile_payload( $profile_user_id, $permissions );
		wp_send_json_success( $payload );
	}
	// Нові методи для обробки AJAX-запитів
	public function handle_upload_photo(): void {
		$this->verify_nonce();
		$this->assert_authenticated();
		$this->validate_honeypot();

		$profile_user_id = $this->sanitize_profile_user_id();
		if ( $profile_user_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			$this->send_safe_error( 'Ви можете оновлювати лише власне фото.', 403 );
		}

		if ( empty( $_FILES['profile_photo'] ) || 0 !== $_FILES['profile_photo']['error'] ) {
			$this->send_safe_error( 'Помилка файлу.', 400 );
		}

		$file = $_FILES['profile_photo']; // phpcs:ignore
		
		// Надійна перевірка на те, чи це зображення (працює на всіх хостингах)
		$image_info = getimagesize( $file['tmp_name'] );
		if ( false === $image_info || ! in_array( $image_info[2], [ IMAGETYPE_JPEG ], true ) ) {
			$this->send_safe_error( 'Файл не є дійсним зображенням у форматі JPG/JPEG.', 400 );
		}

		if ( $file['size'] > 2 * 1024 * 1024 ) {
			$this->send_safe_error( 'Розмір файлу не повинен перевищувати 2 МБ.', 400 );
		}

		$target_dir = ABSPATH . 'photo/';
		if ( ! is_dir( $target_dir ) ) { wp_mkdir_p( $target_dir ); }

		$target_path = $target_dir . $profile_user_id . '.jpg';
		if ( ! move_uploaded_file( $file['tmp_name'], $target_path ) ) {
			$this->send_safe_error( 'Помилка збереження на сервері.', 500 );
		}

		$this->service->get_protocol_service()->log_action_for_user( 
			get_current_user_id(), 'U', "Оновлено фото профілю (ID $profile_user_id)", '✓' 
		);

		wp_send_json_success( [ 'message' => 'Фото оновлено.' ] );
	}

	public function handle_update_private_data(): void {
		$this->verify_nonce();
		$this->assert_authenticated();

		if ( ! current_user_can( 'manage_options' ) ) { $this->send_safe_error( 'Тільки для адміна.', 403 ); }

		$profile_user_id = $this->sanitize_profile_user_id();
		$allowed_keys = [ 'Adr', 'Job', 'Education', 'Phone2', 'Phone3', 'PhoneFamily' ];
		
		foreach ( $allowed_keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_user_meta( $profile_user_id, $key, sanitize_text_field( $_POST[ $key ] ) );
			}
		}

		// ВИКОРИСТАННЯ ГЕТТЕРА: Записуємо в протокол
		$this->service->get_protocol_service()->log_action_for_user( 
            get_current_user_id(), 'U', "Адмін оновив приватні дані профілю ID $profile_user_id", '✓' 
        );

		wp_send_json_success( [ 'message' => 'Дані збережено.' ] );
	}

	public function handle_get_protocol(): void {
		$this->verify_nonce();
		$this->assert_authenticated();
		$page = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page = max( 1, absint( $_POST['per_page'] ?? 10 ) );
		$search = sanitize_text_field( $_POST['search'] ?? '' );
		wp_send_json_success( $this->service->get_protocol_payload( $search, $page, $per_page ) );
	}

	public function handle_get_portmone_payload(): void {
		$this->verify_nonce();
		$profile_user_id = $this->sanitize_profile_user_id();
		$permissions = Capabilities::get_personal_cabinet_permissions( $profile_user_id );
		$payload = $this->payments_service->build_portmone_payload( $profile_user_id, $permissions );
		is_wp_error( $payload ) ? $this->send_safe_error( $payload->get_error_message() ) : wp_send_json_success( $payload );
	}

	public function handle_upload_dues_receipt(): void {
		$this->verify_nonce();
		$profile_user_id = $this->sanitize_profile_user_id();
		$permissions = Capabilities::get_personal_cabinet_permissions( $profile_user_id );
		$result = $this->service->save_dues_receipt( $profile_user_id, $permissions, absint($_POST['year_id']), (float)$_POST['summa'], esc_url_raw($_POST['url']) );
		is_wp_error( $result ) ? $this->send_safe_error( $result->get_error_message() ) : wp_send_json_success( $result );
	}

	public function handle_portmone_return(): void {
		$redirect_url = $this->payments_service->process_portmone_return( sanitize_key($_GET['result'] ?? ''), $_GET, $_POST );
		wp_safe_redirect( $redirect_url ?: home_url('/') );
		exit;
	}

	private function verify_nonce(): void { check_ajax_referer( Personal_Cabinet_List::NONCE_ACTION, 'nonce' ); }
	private function assert_authenticated(): void { if ( ! is_user_logged_in() ) { $this->send_safe_error( 'Авторизуйтесь', 401 ); } }
	private function validate_honeypot(): void { if ( ! empty( $_POST[ self::HONEYPOT_FIELD ] ) ) { $this->send_safe_error( 'Bot detected', 400 ); } }
	private function send_safe_error( string $message, int $status = 400 ): void { wp_send_json_error( [ 'message' => $message ], $status ); }
	private function sanitize_profile_user_id(): int {
		$id = absint( $_POST['profile_user_id'] ?? get_current_user_id() );
		return get_userdata( $id ) ? $id : 0;
	}
	public function handle_update_consent(): void {
		$this->verify_nonce();
		$this->assert_authenticated();

		$profile_user_id = $this->sanitize_profile_user_id();
		if ( $profile_user_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			$this->send_safe_error( 'Немає прав.', 403 );
		}

		$consent = isset( $_POST['consent'] ) && '1' === $_POST['consent'] ? '1' : '0';
		update_user_meta( $profile_user_id, 'FlagView', $consent );

		//$protocol = new Personal_Cabinet_Protocol_Service();
		$protocol = $this->service->get_protocol_service();
		$protocol->log_action_for_user( 
			get_current_user_id(), 'U', "Оновлено згоду на показ персональних даних (ID $profile_user_id) -> $consent", '✓' 
		);

		wp_send_json_success();
	}
	//-----------------
}