<?php
namespace FSTU\Modules\PersonalCabinet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Сервіс безпечної інтеграції онлайн-оплати Portmone для модуля «Особистий кабінет ФСТУ».
 *
 * Version:     1.1.0
 * Date_update: 2026-04-09
 *
 * @package FSTU\Modules\PersonalCabinet
 */
class Personal_Cabinet_Payments_Service {

	private const GATEWAY_URL      = 'https://www.portmone.com.ua/gateway/';
	private const PAYEE_ID         = '28935';
	private const COMMISSION_RATE  = 0.023;
	private const DUES_TYPE_ID     = 1;
	private const EXP_TIME         = '400';
	private const TRANSIENT_TTL    = 1800;
	private const TRANSIENT_PREFIX = 'fstu_pc_portmone_';
	private const LOCK_PREFIX      = 'fstu_pc_portmone_lock_';
	private const LOG_NAME         = 'Personal';
	private const STATUS_PAYLOAD_CREATED = 'payment_payload_created';
	private const STATUS_COMPLETED       = 'payment_completed';
	private const STATUS_FAILED          = 'payment_failed';
	private const STATUS_DUPLICATE       = 'payment_duplicate';
	private const STATUS_INVALID         = 'payment_validation_failed';
	private const STATUS_DB_ERROR        = 'payment_db_error';

	private Personal_Cabinet_Repository $repository;
	private Personal_Cabinet_Protocol_Service $protocol_service;

	public function __construct( ?Personal_Cabinet_Repository $repository = null, ?Personal_Cabinet_Protocol_Service $protocol_service = null ) {
		$this->repository       = $repository ?? new Personal_Cabinet_Repository();
		$this->protocol_service = $protocol_service ?? new Personal_Cabinet_Protocol_Service( $this->repository );
	}

	/**
	 * @param array<string,bool> $permissions
	 * @return array<string,mixed>|\WP_Error
	 */
	public function build_portmone_payload( int $profile_user_id, array $permissions ): array|\WP_Error {
		if ( $profile_user_id <= 0 || ! ( get_userdata( $profile_user_id ) instanceof \WP_User ) ) {
			return new \WP_Error( 'invalid_user', __( 'Профіль користувача не знайдено.', 'fstu' ) );
		}

		if ( empty( $permissions['canPayOnline'] ) ) {
			return new \WP_Error( 'forbidden', __( 'У вас немає прав для онлайн-оплати внеску.', 'fstu' ) );
		}

		$amount = $this->get_annual_fee_amount();
		if ( $amount <= 0 ) {
			return new \WP_Error( 'missing_fee', __( 'Річний внесок не налаштований. Зверніться до адміністратора.', 'fstu' ) );
		}

		$target_year = (int) current_time( 'Y' );
		if ( $this->has_paid_due_for_year( $profile_user_id, $target_year ) ) {
			return new \WP_Error( 'already_paid', sprintf( __( 'Внесок за %d рік уже зафіксовано.', 'fstu' ), $target_year ) );
		}

		$current_user_id = get_current_user_id();
		if ( $current_user_id <= 0 ) {
			return new \WP_Error( 'auth_required', __( 'Потрібна авторизація для підготовки онлайн-оплати.', 'fstu' ) );
		}

		$bill_amount  = $this->calculate_bill_amount( $amount );
		$order_number = $this->build_order_number( $profile_user_id, $target_year );
		$session_token = wp_generate_password( 48, false, false );
		$description  = $this->build_payment_description( $profile_user_id, $target_year );
		$session_key  = $this->get_transient_key( $order_number );
		$session      = [
			'profile_user_id' => $profile_user_id,
			'initiator_id'    => $current_user_id,
			'target_year'     => $target_year,
			'amount'          => $amount,
			'bill_amount'     => $bill_amount,
			'order_number'    => $order_number,
			'session_token'   => $session_token,
			'description'     => $description,
			'created_at'      => current_time( 'mysql' ),
		];

		set_transient( $session_key, $session, self::TRANSIENT_TTL );
		$this->protocol_service->log_action_for_user( $current_user_id, 'V', sprintf( 'Підготовлено Portmone payload для користувача ID %d за %d рік.', $profile_user_id, $target_year ), self::STATUS_PAYLOAD_CREATED );

		return [
			'gatewayUrl' => self::GATEWAY_URL,
			'method'     => 'post',
			'fields'     => [
				'payee_id'          => self::PAYEE_ID,
				'shop_order_number' => $order_number,
				'bill_amount'       => $this->format_amount( $bill_amount ),
				'description'       => $description,
				'success_url'       => $this->build_return_url( 'success', $profile_user_id, $order_number, $session_token ),
				'failure_url'       => $this->build_return_url( 'failure', $profile_user_id, $order_number, $session_token ),
				'attribute1'        => (string) $target_year,
				'attribute2'        => (string) $current_user_id,
				'lang'              => 'uk',
				'encoding'          => 'UTF-8',
				'exp_time'          => self::EXP_TIME,
			],
			'meta'       => [
				'year'       => $target_year,
				'amount'     => $this->format_amount( $amount ),
				'billAmount' => $this->format_amount( $bill_amount ),
			],
		];
	}

	/**
	 * @param array<string,mixed> $query_data
	 * @param array<string,mixed> $post_data
	 */
	public function process_portmone_return( string $result, array $query_data, array $post_data ): string {
		$result = in_array( $result, [ 'success', 'failure' ], true ) ? $result : 'error';

		$order_number    = sanitize_text_field( wp_unslash( $post_data['SHOPORDERNUMBER'] ?? '' ) );
		$query_order     = sanitize_text_field( wp_unslash( $query_data['pc_order'] ?? '' ) );
		$query_token     = sanitize_text_field( wp_unslash( $query_data['pc_token'] ?? '' ) );
		$shop_bill_id    = sanitize_text_field( wp_unslash( $post_data['SHOPBILLID'] ?? '' ) );
		$approval_code   = sanitize_text_field( wp_unslash( $post_data['APPROVALCODE'] ?? '' ) );
		$card_mask       = sanitize_text_field( wp_unslash( $post_data['CARD_MASK'] ?? '' ) );
		$description     = sanitize_text_field( wp_unslash( $post_data['DESCRIPTION'] ?? '' ) );
		$posted_year     = absint( $post_data['ATTRIBUTE1'] ?? 0 );
		$posted_user_id  = absint( $post_data['ATTRIBUTE2'] ?? 0 );
		$posted_bill_raw = sanitize_text_field( wp_unslash( $post_data['BILL_AMOUNT'] ?? '' ) );
		$profile_user_id = absint( $query_data['profile_user_id'] ?? 0 );

		if ( '' === $order_number || '' === $query_order || '' === $query_token || $query_order !== $order_number ) {
			return $this->build_module_redirect_url( $profile_user_id, 'error', 0 );
		}

		$session_key = $this->get_transient_key( $order_number );
		$session     = get_transient( $session_key );

		if ( ! is_array( $session ) ) {
			$existing_user_id = $this->repository->get_due_user_id_by_shop_order_number( $order_number );

			if ( $existing_user_id > 0 ) {
				return $this->build_module_redirect_url( $existing_user_id, 'success', $posted_year );
			}

			return $this->build_module_redirect_url( $profile_user_id, 'error', $posted_year );
		}

		$profile_user_id = (int) ( $session['profile_user_id'] ?? 0 );
		$initiator_id    = (int) ( $session['initiator_id'] ?? 0 );
		$target_year     = (int) ( $session['target_year'] ?? 0 );
		$amount          = isset( $session['amount'] ) ? (float) $session['amount'] : 0.0;
		$bill_amount     = isset( $session['bill_amount'] ) ? (float) $session['bill_amount'] : 0.0;
		$session_token   = isset( $session['session_token'] ) ? (string) $session['session_token'] : '';

		if ( '' === $session_token || ! hash_equals( $session_token, $query_token ) ) {
			$this->protocol_service->log_action_for_user( $initiator_id, 'V', sprintf( 'Portmone return не пройшов перевірку session token для користувача ID %d.', $profile_user_id ), self::STATUS_INVALID );
			delete_transient( $session_key );

			return $this->build_module_redirect_url( $profile_user_id, 'error', $target_year );
		}

		if ( 'failure' === $result ) {
			$this->protocol_service->log_action_for_user( $initiator_id, 'V', sprintf( 'Скасовано або не завершено Portmone-оплату внеску за %d рік для користувача ID %d.', $target_year, $profile_user_id ), self::STATUS_FAILED );
			delete_transient( $session_key );

			return $this->build_module_redirect_url( $profile_user_id, 'failure', $target_year );
		}

		if ( $profile_user_id <= 0 || $target_year <= 0 || $amount <= 0 || $bill_amount <= 0 ) {
			$this->protocol_service->log_action_for_user( $initiator_id, 'V', 'Portmone success return не пройшов внутрішню валідацію сесії.', self::STATUS_INVALID );
			delete_transient( $session_key );

			return $this->build_module_redirect_url( $profile_user_id, 'error', $target_year );
		}

		if ( $posted_year > 0 && $posted_year !== $target_year ) {
			$this->protocol_service->log_action_for_user( $initiator_id, 'V', sprintf( 'Portmone повернув некоректний рік оплати для користувача ID %d.', $profile_user_id ), self::STATUS_INVALID );
			delete_transient( $session_key );

			return $this->build_module_redirect_url( $profile_user_id, 'error', $target_year );
		}

		if ( $posted_user_id > 0 && $initiator_id > 0 && $posted_user_id !== $initiator_id ) {
			$this->protocol_service->log_action_for_user( $initiator_id, 'V', sprintf( 'Portmone повернув некоректний ініціатор платежу для користувача ID %d.', $profile_user_id ), self::STATUS_INVALID );
			delete_transient( $session_key );

			return $this->build_module_redirect_url( $profile_user_id, 'error', $target_year );
		}

		if ( ! $this->amounts_match( $bill_amount, $posted_bill_raw ) ) {
			$this->protocol_service->log_action_for_user( $initiator_id, 'V', sprintf( 'Portmone повернув некоректну суму платежу для користувача ID %d.', $profile_user_id ), self::STATUS_INVALID );
			delete_transient( $session_key );

			return $this->build_module_redirect_url( $profile_user_id, 'error', $target_year );
		}

		if ( $this->due_exists_by_order_number( $order_number ) || $this->has_paid_due_for_year( $profile_user_id, $target_year ) ) {
			$this->protocol_service->log_action_for_user( $initiator_id, 'V', sprintf( 'Отримано дубльований success return Portmone для користувача ID %d.', $profile_user_id ), self::STATUS_DUPLICATE );
			delete_transient( $session_key );

			return $this->build_module_redirect_url( $profile_user_id, 'success', $target_year );
		}

		if ( ! $this->acquire_payment_lock( $order_number ) ) {
			if ( $this->due_exists_by_order_number( $order_number ) ) {
				$this->protocol_service->log_action_for_user( $initiator_id, 'V', sprintf( 'Portmone callback повторно звернувся до вже обробленого order number %s.', $order_number ), self::STATUS_DUPLICATE );
				delete_transient( $session_key );

				return $this->build_module_redirect_url( $profile_user_id, 'success', $target_year );
			}

			$this->protocol_service->log_action_for_user( $initiator_id, 'V', sprintf( 'Portmone callback повторно намагається обробити order number %s без готового результату.', $order_number ), self::STATUS_DUPLICATE );
			delete_transient( $session_key );

			return $this->build_module_redirect_url( $profile_user_id, 'error', $target_year );
		}

		if ( ! $this->insert_due_and_log( $profile_user_id, $initiator_id, $target_year, $amount, $order_number, $shop_bill_id, $approval_code, $card_mask, $description ) ) {
			$this->release_payment_lock( $order_number );
			$this->protocol_service->log_action_for_user( $initiator_id, 'V', sprintf( 'Не вдалося зафіксувати Portmone-оплату внеску за %d рік для користувача ID %d.', $target_year, $profile_user_id ), self::STATUS_DB_ERROR );
			delete_transient( $session_key );

			return $this->build_module_redirect_url( $profile_user_id, 'error', $target_year );
		}

		$this->mark_payment_lock_complete( $order_number );
		delete_transient( $session_key );

		return $this->build_module_redirect_url( $profile_user_id, 'success', $target_year );
	}

	private function get_annual_fee_amount(): float {
		$settings = $this->repository->get_settings_values( [ 'AnnualFee' ] );
		$raw      = isset( $settings['AnnualFee'] ) ? str_replace( ',', '.', (string) $settings['AnnualFee'] ) : '';

		return is_numeric( $raw ) ? round( (float) $raw, 2 ) : 0.0;
	}

	private function calculate_bill_amount( float $amount ): float {
		return round( $amount + round( $amount * self::COMMISSION_RATE, 2 ), 2 );
	}

	private function build_order_number( int $profile_user_id, int $target_year ): string {
		return sprintf( 'pc-%d-%d-%d', $target_year, $profile_user_id, wp_rand( 1000, 9999 ) );
	}

	private function build_payment_description( int $profile_user_id, int $target_year ): string {
		$user = get_userdata( $profile_user_id );
		$fio  = '';

		if ( $user instanceof \WP_User ) {
			$fio = trim( implode( ' ', array_filter( [
				(string) get_user_meta( $profile_user_id, 'last_name', true ),
				(string) get_user_meta( $profile_user_id, 'first_name', true ),
				(string) get_user_meta( $profile_user_id, 'Patronymic', true ),
			] ) ) );

			if ( '' === $fio ) {
				$fio = (string) $user->display_name;
			}
		}

		return sprintf( 'Благодійна допомога у вигляді реєстраційних членських внесків за %d рік, платник %s', $target_year, '' !== $fio ? $fio : 'член ФСТУ' );
	}

	private function build_return_url( string $result, int $profile_user_id, string $order_number, string $session_token ): string {
		return add_query_arg(
			[
				'action'          => 'fstu_personal_cabinet_portmone_return',
				'result'          => $result,
				'profile_user_id' => $profile_user_id,
				'pc_order'        => $order_number,
				'pc_token'        => $session_token,
			],
			admin_url( 'admin-post.php' )
		);
	}

	private function build_module_redirect_url( int $profile_user_id, string $status, int $year ): string {
		$module_url = Personal_Cabinet_List::get_module_url();
		$base_url   = '' !== $module_url ? $module_url : home_url( '/' );
		$args       = [
			'payment_status' => sanitize_key( $status ),
		];

		if ( $profile_user_id > 0 ) {
			$args['ViewID'] = $profile_user_id;
		}

		if ( $year > 0 ) {
			$args['payment_year'] = $year;
		}

		return add_query_arg( $args, $base_url );
	}

	private function format_amount( float $amount ): string {
		return number_format( $amount, 2, '.', '' );
	}

	private function amounts_match( float $expected, string $actual ): bool {
		$normalized_actual = str_replace( ',', '.', trim( $actual ) );
		if ( '' === $normalized_actual || ! is_numeric( $normalized_actual ) ) {
			return false;
		}

		return abs( $expected - (float) $normalized_actual ) < 0.01;
	}

	private function has_paid_due_for_year( int $profile_user_id, int $target_year ): bool {
		return $this->repository->has_due_for_year( $profile_user_id, $target_year, self::DUES_TYPE_ID );
	}

	private function due_exists_by_order_number( string $order_number ): bool {
		return $this->repository->get_due_user_id_by_shop_order_number( $order_number ) > 0;
	}

	private function insert_due_and_log( int $profile_user_id, int $initiator_id, int $target_year, float $amount, string $order_number, string $shop_bill_id, string $approval_code, string $card_mask, string $description ): bool {
		global $wpdb;

		$wpdb->query( 'START TRANSACTION' );

		$due_inserted = $wpdb->insert(
			'Dues',
			[
				'Dues_DateCreate'      => current_time( 'mysql' ),
				'User_ID'              => $profile_user_id,
				'Year_ID'              => $target_year,
				'UserCreate'           => $initiator_id,
				'Dues_ShopBillid'      => $shop_bill_id,
				'Dues_ShopOrderNumber' => $order_number,
				'Dues_ApprovalCode'    => $approval_code,
				'Dues_CardMask'        => $card_mask,
				'Dues_Summa'           => $amount,
				'DuesType_ID'          => self::DUES_TYPE_ID,
			],
			[ '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%f', '%d' ]
		);

		if ( false === $due_inserted ) {
			$wpdb->query( 'ROLLBACK' );

			return false;
		}

		$log_payload  = $this->protocol_service->get_log_insert_payload(
			$initiator_id,
			'I',
			sprintf( 'Онлайн-оплату членського внеску за %d рік зафіксовано для користувача ID %d. %s', $target_year, $profile_user_id, '' !== $description ? $description : '' ),
			self::STATUS_COMPLETED
		);
		$log_inserted = $wpdb->insert( 'Logs', $log_payload['data'], $log_payload['format'] );

		if ( false === $log_inserted ) {
			$wpdb->query( 'ROLLBACK' );

			return false;
		}

		$wpdb->query( 'COMMIT' );

		return true;
	}

	private function acquire_payment_lock( string $order_number ): bool {
		return add_option( $this->get_lock_option_name( $order_number ), 'processing', '', 'no' );
	}

	private function mark_payment_lock_complete( string $order_number ): void {
		update_option( $this->get_lock_option_name( $order_number ), 'done', false );
	}

	private function release_payment_lock( string $order_number ): void {
		delete_option( $this->get_lock_option_name( $order_number ) );
	}

	private function get_transient_key( string $order_number ): string {
		return self::TRANSIENT_PREFIX . md5( $order_number );
	}

	private function get_lock_option_name( string $order_number ): string {
		return self::LOCK_PREFIX . md5( $order_number );
	}
}

