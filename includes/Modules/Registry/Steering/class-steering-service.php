<?php
namespace FSTU\Modules\Registry\Steering;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service-шар модуля «Реєстр стернових ФСТУ».
	 *
	 * Містить бізнес-правила, транзакції та підготовку payload для списку,
	 * картки, форми подання та протоколу модуля.
	 *
	 * Version:     1.10.0
 * Date_update: 2026-04-08
 *
 * @package FSTU\Modules\UserFstu\Steering
 */
class Steering_Service {

	private const VERIFICATION_THRESHOLD = 3;
	private const STATUS_REGISTERED      = 3;
	private const STATUS_SENT_POST       = 5;
	private const STATUS_RECEIVED        = 6;

	private Steering_Repository $repository;
	private Steering_Protocol_Service $protocol_service;
	private Steering_Upload_Service $upload_service;
	private Steering_Notification_Service $notification_service;

	public function __construct( Steering_Repository $repository, ?Steering_Protocol_Service $protocol_service = null, ?Steering_Upload_Service $upload_service = null, ?Steering_Notification_Service $notification_service = null ) {
		$this->repository       = $repository;
		$this->protocol_service = $protocol_service instanceof Steering_Protocol_Service ? $protocol_service : new Steering_Protocol_Service();
		$this->upload_service   = $upload_service instanceof Steering_Upload_Service ? $upload_service : new Steering_Upload_Service();
		$this->notification_service = $notification_service instanceof Steering_Notification_Service ? $notification_service : new Steering_Notification_Service( $repository );
	}

	public function get_protocol_service(): Steering_Protocol_Service {
		return $this->protocol_service;
	}

	/**
	 * @param array<string,mixed> $filters Набір фільтрів списку.
	 * @param array<string,bool>  $permissions Права поточного користувача.
	 * @return array<string,mixed>
	 */
	public function get_list_payload( array $filters, array $permissions ): array {
		$page      = max( 1, (int) ( $filters['page'] ?? 1 ) );
		$per_page  = max( 1, min( 50, (int) ( $filters['per_page'] ?? 10 ) ) );
		$offset    = (int) ( $filters['offset'] ?? ( $page - 1 ) * $per_page );
		$can_admin = ! empty( $permissions['canViewHiddenExpired'] );

		$total  = $this->repository->count_items( $filters, $can_admin );
		$items  = $this->repository->get_items( $filters, $per_page, $offset, $can_admin );
		$totals = $this->repository->get_footer_totals( $filters, $can_admin );

		return [
			'items'       => $items,
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => max( 1, (int) ceil( $total / max( 1, $per_page ) ) ),
			'footer'      => $totals,
		];
	}

	/**
	 * @param array<string,bool> $permissions Права поточного користувача.
	 * @return array<string,mixed>|null
	 */
	public function get_single_payload( int $steering_id, array $permissions ): ?array {
		$can_admin = ! empty( $permissions['canViewHiddenExpired'] );
		$item      = $this->repository->get_item_by_id( $steering_id, $can_admin );

		if ( ! is_array( $item ) ) {
			return null;
		}

		return $this->enrich_single_payload( $item, $permissions );
	}

	/**
	 * @param array<string,bool> $permissions Права поточного користувача.
	 * @return array<string,mixed>
	 */
	public function confirm_verification( int $steering_id, array $permissions ): array {
		if ( empty( $permissions['canVerify'] ) ) {
			throw new \RuntimeException( 'verify_forbidden' );
		}

		$current_user_id = get_current_user_id();
		if ( $current_user_id <= 0 ) {
			throw new \RuntimeException( 'verify_login_required' );
		}

		$item = $this->repository->get_item_raw_by_id( $steering_id );
		if ( ! is_array( $item ) ) {
			throw new \RuntimeException( 'verify_item_not_found' );
		}

		$target_user_id = (int) ( $item['User_ID'] ?? 0 );
		if ( $target_user_id > 0 && $target_user_id === $current_user_id ) {
			throw new \RuntimeException( 'verify_self_forbidden' );
		}

		if ( $this->repository->is_registered_item( $steering_id ) ) {
			throw new \RuntimeException( 'verify_already_registered' );
		}

		if ( $this->repository->verification_exists_for_user( $steering_id, $current_user_id ) ) {
			throw new \RuntimeException( 'verify_duplicate' );
		}

		if ( empty( $permissions['canManage'] ) && ! $this->repository->user_has_verifier_qualification( $current_user_id ) ) {
			throw new \RuntimeException( 'verify_qualification_required' );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'START TRANSACTION' );

		$auto_registered    = false;
		$registration_number = 0;

		try {
			if ( ! $this->repository->insert_verification( $steering_id, $current_user_id ) ) {
				throw new \RuntimeException( 'verify_duplicate' );
			}

			$fio = trim( (string) ( $item['FIO'] ?? '' ) );
			$this->protocol_service->log_action_transactional(
				'V',
				sprintf( 'Підтверджено кваліфікацію стернового: %s', '' !== $fio ? $fio : '#' . $steering_id )
			);

			$registration_number = $this->repository->register_after_verification_threshold( $steering_id, self::VERIFICATION_THRESHOLD );
			if ( $registration_number > 0 ) {
				$auto_registered = true;
				$this->protocol_service->log_action_transactional(
					'U',
					sprintf( 'Автоматично зареєстровано посвідчення стернового: %s, № %d', '' !== $fio ? $fio : '#' . $steering_id, $registration_number )
				);
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'COMMIT' );
		} catch ( \Throwable $throwable ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'ROLLBACK' );
			$this->protocol_service->try_log_action(
				'V',
				sprintf( 'Помилка підтвердження кваліфікації стернового: %s', '' !== trim( (string) ( $item['FIO'] ?? '' ) ) ? (string) $item['FIO'] : '#' . $steering_id ),
				'error'
			);
			throw $throwable;
		}

		$updated_item = $this->repository->get_item_raw_by_id( $steering_id );
		if ( ! is_array( $updated_item ) ) {
			throw new \RuntimeException( 'verify_item_not_found' );
		}

		if ( $auto_registered ) {
			$this->dispatch_notification_safely(
				'U',
				$this->repository->get_notification_context( $steering_id ),
				'registered_auto'
			);
		}

		return [
			'item'                => $this->enrich_single_payload( $updated_item, $permissions ),
			'auto_registered'     => $auto_registered,
			'registration_number' => $registration_number,
			'verification_count'  => (int) ( $updated_item['CntVerification'] ?? 0 ),
		];
	}

	/**
	 * @param array<string,bool> $permissions Права поточного користувача.
	 * @return array<string,mixed>
	 */
	public function register_item( int $steering_id, array $permissions ): array {
		return $this->process_status_action( $steering_id, $permissions, 'register' );
	}

	/**
	 * @param array<string,bool> $permissions Права поточного користувача.
	 * @return array<string,mixed>
	 */
	public function mark_sent_post( int $steering_id, array $permissions ): array {
		return $this->process_status_action( $steering_id, $permissions, 'sent_post' );
	}

	/**
	 * @param array<string,bool> $permissions Права поточного користувача.
	 * @return array<string,mixed>
	 */
	public function mark_received( int $steering_id, array $permissions ): array {
		return $this->process_status_action( $steering_id, $permissions, 'received' );
	}

	/**
	 * @param array<string,bool> $permissions Права поточного користувача.
	 * @return array<string,mixed>
	 */
	public function get_dictionaries_payload( array $permissions ): array {
		return [
			'cities'          => $this->repository->get_cities(),
			'availableUsers'  => ! empty( $permissions['canManage'] ) ? $this->repository->get_available_users() : [],
			'statusOptions'   => ( ! empty( $permissions['canManage'] ) || ! empty( $permissions['canManageStatus'] ) ) ? $this->repository->get_status_options() : [],
		];
	}

	/**
	 * @param array<string,mixed> $filters Набір фільтрів протоколу.
	 * @return array<string,mixed>
	 */
	public function get_protocol_payload( array $filters ): array {
		$page     = max( 1, (int) ( $filters['page'] ?? 1 ) );
		$per_page = max( 1, min( 50, (int) ( $filters['per_page'] ?? 10 ) ) );
		$offset   = (int) ( $filters['offset'] ?? ( $page - 1 ) * $per_page );
		$search   = (string) ( $filters['search'] ?? '' );
		$log_name = Steering_Protocol_Service::LOG_NAME;

		$total = $this->repository->count_protocol_items( $search, $log_name );
		$items = $this->repository->get_protocol_items( $search, $per_page, $offset, $log_name );

		return [
			'items'       => $items,
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => max( 1, (int) ceil( $total / max( 1, $per_page ) ) ),
		];
	}

	/**
	 * @param array<string,mixed> $data Дані форми.
	 * @param array<string,bool>  $permissions Права поточного користувача.
	 * @return array<string,int>
	 */
	public function create_item( array $data, array $permissions, array $photo = [] ): array {
		$user_id = (int) ( $data['user_id'] ?? 0 );

		if ( empty( $permissions['canManage'] ) ) {
			$current_user_id = get_current_user_id();
			if ( $current_user_id <= 0 ) {
				throw new \RuntimeException( 'submit_login_required' );
			}

			$user_id = $current_user_id;
			$data['user_id'] = $current_user_id;
		}

		if ( ! $this->repository->user_exists( $user_id ) ) {
			throw new \RuntimeException( 'user_not_found' );
		}

		if ( $this->repository->user_has_steering_record( $user_id ) ) {
			throw new \RuntimeException( 'duplicate_user' );
		}

		$city_id = (int) ( $data['city_id'] ?? 0 );
		if ( ! $this->repository->city_exists( $city_id ) ) {
			throw new \RuntimeException( 'city_not_found' );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'START TRANSACTION' );
		$photo_stored = false;

		try {
			$steering_id = $this->repository->insert_item( $data );
			if ( $steering_id <= 0 ) {
				throw new \RuntimeException( 'steering_insert_failed' );
			}

			if ( ! empty( $photo ) ) {
				$this->upload_service->store_uploaded_photo( $photo, $user_id );
				$photo_stored = true;
			}

			$fio = $this->repository->get_user_fio( $user_id );
			$this->protocol_service->log_action_transactional(
				'I',
				sprintf( 'Створено заявку стернового: %s', '' !== $fio ? $fio : '#' . $user_id )
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'COMMIT' );

			$this->dispatch_notification_safely(
				'I',
				$this->repository->get_notification_context( $steering_id ),
				'submission_created'
			);
		} catch ( \Throwable $throwable ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'ROLLBACK' );
			if ( $photo_stored ) {
				$this->upload_service->delete_photo( $user_id );
			}
			$this->protocol_service->try_log_action(
				'I',
				sprintf( 'Помилка створення заявки стернового: %s', '' !== $this->repository->get_user_fio( $user_id ) ? $this->repository->get_user_fio( $user_id ) : '#' . $user_id ),
				'error'
			);
			throw $throwable;
		}

		return [ 'steering_id' => $steering_id ];
	}

	/**
	 * @param array<string,mixed> $data Дані форми.
	 * @param array<string,bool>  $permissions Права поточного користувача.
	 * @return array<string,mixed>
	 */
	public function update_item( int $steering_id, array $data, array $permissions, array $photo = [] ): array {
		if ( empty( $permissions['canManage'] ) ) {
			throw new \RuntimeException( 'update_forbidden' );
		}

		$item = $this->repository->get_item_raw_by_id( $steering_id );
		if ( ! is_array( $item ) ) {
			throw new \RuntimeException( 'update_item_not_found' );
		}

		$city_id = (int) ( $data['city_id'] ?? 0 );
		if ( ! $this->repository->city_exists( $city_id ) ) {
			throw new \RuntimeException( 'city_not_found' );
		}

		$user_id        = (int) ( $item['User_ID'] ?? 0 );
		$has_new_photo  = ! empty( $photo ) && UPLOAD_ERR_NO_FILE !== (int) ( $photo['error'] ?? UPLOAD_ERR_NO_FILE );
		$backup_path    = '';
		$photo_replaced = false;

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'START TRANSACTION' );

		try {
			if ( $has_new_photo ) {
				$backup_path    = $this->upload_service->backup_existing_photo( $user_id );
				$this->upload_service->store_uploaded_photo( $photo, $user_id );
				$photo_replaced = true;
			}

			if ( ! $this->repository->update_item( $steering_id, $data ) ) {
				throw new \RuntimeException( 'steering_update_failed' );
			}

			$fio = $this->build_fio_from_form_data( $data, (string) ( $item['FIO'] ?? '' ) );
			$this->protocol_service->log_action_transactional(
				'U',
				sprintf( 'Оновлено запис стернового: %s', '' !== $fio ? $fio : '#' . $steering_id )
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'COMMIT' );

			if ( $photo_replaced ) {
				$this->upload_service->remove_backup( $backup_path );
			}
		} catch ( \Throwable $throwable ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'ROLLBACK' );

			if ( $photo_replaced ) {
				try {
					$this->upload_service->restore_photo_backup( $user_id, $backup_path );
					$this->upload_service->remove_backup( $backup_path );
				} catch ( \Throwable $restore_error ) {
					$this->protocol_service->try_log_action(
						'U',
						sprintf( 'Помилка відновлення фото стернового після rollback: #%d', $steering_id ),
						'photo_rollback_error'
					);
				}
			}

			$this->protocol_service->try_log_action(
				'U',
				sprintf( 'Помилка оновлення запису стернового: %s', '' !== trim( (string) ( $item['FIO'] ?? '' ) ) ? (string) $item['FIO'] : '#' . $steering_id ),
				'error'
			);

			throw $throwable;
		}

		$updated_item = $this->repository->get_item_raw_by_id( $steering_id );
		if ( ! is_array( $updated_item ) ) {
			throw new \RuntimeException( 'update_item_not_found' );
		}

		return [
			'item' => $this->enrich_single_payload( $updated_item, $permissions ),
			'steering_id' => $steering_id,
		];
	}

	/**
	 * @param array<string,bool> $permissions Права поточного користувача.
	 * @return array<string,mixed>
	 */
	public function delete_item( int $steering_id, array $permissions ): array {
		if ( empty( $permissions['canDelete'] ) ) {
			throw new \RuntimeException( 'delete_forbidden' );
		}

		$item = $this->repository->get_item_raw_by_id( $steering_id );
		if ( ! is_array( $item ) ) {
			throw new \RuntimeException( 'delete_item_not_found' );
		}

		$fio        = trim( (string) ( $item['FIO'] ?? '' ) );
		$user_id    = (int) ( $item['User_ID'] ?? 0 );
		$blockers   = $this->repository->get_delete_blockers( $steering_id );

		if ( ! empty( $blockers ) ) {
			$reason = $this->format_delete_blockers( $blockers );
			$this->protocol_service->try_log_action(
				'D',
				sprintf( 'Заблоковано видалення запису стернового: %s, причина: %s', '' !== $fio ? $fio : '#' . $steering_id, $reason ),
				'dependency'
			);

			throw new \RuntimeException( 'delete_blocked:' . implode( ',', $blockers ) );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'START TRANSACTION' );

		try {
			if ( ! $this->repository->delete_item( $steering_id ) ) {
				throw new \RuntimeException( 'delete_failed' );
			}

			$this->protocol_service->log_action_transactional(
				'D',
				sprintf( 'Видалено запис стернового: %s', '' !== $fio ? $fio : '#' . $steering_id )
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'COMMIT' );
		} catch ( \Throwable $throwable ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'ROLLBACK' );
			$this->protocol_service->try_log_action(
				'D',
				sprintf( 'Помилка видалення запису стернового: %s', '' !== $fio ? $fio : '#' . $steering_id ),
				'error'
			);
			throw $throwable;
		}

		if ( $user_id > 0 ) {
			$this->upload_service->delete_photo( $user_id );
		}

		return [
			'deleted'     => true,
			'steering_id' => $steering_id,
		];
	}

	/**
	 * @param array<string,mixed> $item Дані картки.
	 * @param array<string,bool>  $permissions Права поточного користувача.
	 * @return array<string,mixed>
	 */
	private function enrich_single_payload( array $item, array $permissions ): array {
		$current_user_id = get_current_user_id();
		$steering_id     = (int) ( $item['Steering_ID'] ?? 0 );
		$target_user_id  = (int) ( $item['User_ID'] ?? 0 );
		$status_id       = (int) ( $item['AppStatus_ID'] ?? 0 );
		$is_registered   = $this->repository->is_registered_item( $steering_id );
		$has_confirmed   = $current_user_id > 0 ? $this->repository->verification_exists_for_user( $steering_id, $current_user_id ) : false;
		$is_qualified    = $current_user_id > 0 ? $this->repository->user_has_verifier_qualification( $current_user_id ) : false;
		$can_manage_status = ! empty( $permissions['canManageStatus'] ) || ! empty( $permissions['canManage'] );

		$item['PhotoUrl']               = $this->repository->build_photo_url( $target_user_id );
		$item['Verifiers']             = $this->repository->get_verifications_by_steering_id( $steering_id );
		$item['VerificationThreshold'] = self::VERIFICATION_THRESHOLD;
		$item['IsRegistered']          = $is_registered;
		$item['HasConfirmed']          = $has_confirmed;
		$item['CanRegister']           = $can_manage_status && ! $is_registered;
		$item['CanDelete']             = ! empty( $permissions['canDelete'] );
		$item['CanMarkSentPost']       = $can_manage_status && $is_registered && $status_id >= self::STATUS_REGISTERED && $status_id < self::STATUS_SENT_POST;
		$item['CanMarkReceived']       = $can_manage_status && $status_id === self::STATUS_SENT_POST;
		$item['CanConfirmVerification'] = ! empty( $permissions['canVerify'] )
			&& $current_user_id > 0
			&& $target_user_id !== $current_user_id
			&& ! $has_confirmed
			&& ! $is_registered
			&& ( ! empty( $permissions['canManage'] ) || $is_qualified );

		if ( isset( $item['Record_Type'] ) && 'skipper' === $item['Record_Type'] ) {
			$item['CanRegister']            = false;
			$item['CanDelete']              = false;
			$item['CanMarkSentPost']        = false;
			$item['CanMarkReceived']        = false;
			$item['CanConfirmVerification'] = false;
			$item['PhotoUrl']               = home_url( '/photo_skipper/' . $target_user_id . '.jpg' );
		}

		return $item;
	}

	/**
	 * @param array<string,bool> $permissions Права поточного користувача.
	 * @return array<string,mixed>
	 */
	private function process_status_action( int $steering_id, array $permissions, string $action ): array {
		if ( empty( $permissions['canManageStatus'] ) && empty( $permissions['canManage'] ) ) {
			throw new \RuntimeException( 'status_forbidden' );
		}

		$item = $this->repository->get_item_raw_by_id( $steering_id );
		if ( ! is_array( $item ) ) {
			throw new \RuntimeException( 'status_item_not_found' );
		}

		$fio       = trim( (string) ( $item['FIO'] ?? '' ) );
		$status_id = (int) ( $item['AppStatus_ID'] ?? 0 );

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'START TRANSACTION' );

		$registration_number = 0;

		try {
			switch ( $action ) {
				case 'register':
					if ( $this->repository->is_registered_item( $steering_id ) ) {
						throw new \RuntimeException( 'status_already_registered' );
					}

					$registration_number = $this->repository->register_item( $steering_id );
					$this->protocol_service->log_action_transactional(
						'U',
						sprintf( 'Зареєстровано посвідчення стернового: %s, № %d', '' !== $fio ? $fio : '#' . $steering_id, $registration_number )
					);
					break;

				case 'sent_post':
					if ( ! $this->repository->is_registered_item( $steering_id ) ) {
						throw new \RuntimeException( 'status_send_requires_registered' );
					}

					if ( $status_id >= self::STATUS_RECEIVED ) {
						throw new \RuntimeException( 'status_already_received' );
					}

					if ( $status_id >= self::STATUS_SENT_POST ) {
						throw new \RuntimeException( 'status_already_sent' );
					}

					if ( ! $this->repository->update_item_status( $steering_id, self::STATUS_SENT_POST ) ) {
						throw new \RuntimeException( 'status_update_failed' );
					}

					$this->protocol_service->log_action_transactional(
						'U',
						sprintf( 'Позначено як відправлено поштою посвідчення стернового: %s', '' !== $fio ? $fio : '#' . $steering_id )
					);
					break;

				case 'received':
					if ( $status_id >= self::STATUS_RECEIVED ) {
						throw new \RuntimeException( 'status_already_received' );
					}

					if ( self::STATUS_SENT_POST !== $status_id ) {
						throw new \RuntimeException( 'status_received_requires_sent' );
					}

					if ( ! $this->repository->update_item_status( $steering_id, self::STATUS_RECEIVED ) ) {
						throw new \RuntimeException( 'status_update_failed' );
					}

					$this->protocol_service->log_action_transactional(
						'U',
						sprintf( 'Позначено як доставлено одержувачу посвідчення стернового: %s', '' !== $fio ? $fio : '#' . $steering_id )
					);
					break;

				default:
					throw new \RuntimeException( 'status_action_invalid' );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'COMMIT' );
		} catch ( \Throwable $throwable ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'ROLLBACK' );
			$this->protocol_service->try_log_action(
				'U',
				sprintf( 'Помилка статусної дії стернового (%s): %s', $action, '' !== $fio ? $fio : '#' . $steering_id ),
				'error'
			);
			throw $throwable;
		}

		$updated_item = $this->repository->get_item_raw_by_id( $steering_id );
		if ( ! is_array( $updated_item ) ) {
			throw new \RuntimeException( 'status_item_not_found' );
		}

		$notification_payload = $this->repository->get_notification_context( $steering_id );
		if ( 'register' === $action ) {
			$this->dispatch_notification_safely( 'U', $notification_payload, 'registered_manual' );
		}

		if ( 'sent_post' === $action ) {
			$this->dispatch_notification_safely( 'U', $notification_payload, 'sent_post' );
		}

		return [
			'item'                => $this->enrich_single_payload( $updated_item, $permissions ),
			'registration_number' => $registration_number,
			'action'              => $action,
		];
	}

	/**
	 * @param array<string,mixed> $data Дані форми.
	 */
	private function build_fio_from_form_data( array $data, string $fallback ): string {
		$parts = [
			trim( (string) ( $data['surname_ukr'] ?? '' ) ),
			trim( (string) ( $data['name_ukr'] ?? '' ) ),
			trim( (string) ( $data['patronymic_ukr'] ?? '' ) ),
		];

		$parts = array_filter(
			$parts,
			static fn( string $part ): bool => '' !== $part
		);

		$fio = trim( implode( ' ', $parts ) );

		return '' !== $fio ? $fio : trim( $fallback );
	}

	/**
	 * @param array<int,string> $blockers
	 */
	private function format_delete_blockers( array $blockers ): string {
		$labels = [];

		foreach ( $blockers as $blocker ) {
			switch ( $blocker ) {
				case 'verification':
					$labels[] = 'є підтвердження кваліфікації';
					break;
				case 'registered':
					$labels[] = 'посвідчення вже зареєстровано';
					break;
				default:
					$labels[] = $blocker;
			}
		}

		return implode( '; ', $labels );
	}

	/**
	 * @param array<string,mixed>|null $notification_payload
	 */
	private function dispatch_notification_safely( string $log_type, ?array $notification_payload, string $action ): void {
		if ( ! is_array( $notification_payload ) ) {
			return;
		}

		try {
			switch ( $action ) {
				case 'submission_created':
					$this->notification_service->dispatch_submission_created( $notification_payload );
					break;
				case 'registered_auto':
					$this->notification_service->dispatch_certificate_registered( $notification_payload, true );
					break;
				case 'registered_manual':
					$this->notification_service->dispatch_certificate_registered( $notification_payload, false );
					break;
				case 'sent_post':
					$this->notification_service->dispatch_certificate_sent( $notification_payload );
					break;
			}
		} catch ( \Throwable $throwable ) {
			$this->protocol_service->try_log_action(
				$log_type,
				sprintf( 'Помилка сповіщення Steering (%s): %s', $action, $this->resolve_notification_subject_name( $notification_payload ) ),
				'notify_error'
			);
		}
	}

	/**
	 * @param array<string,mixed> $notification_payload
	 */
	private function resolve_notification_subject_name( array $notification_payload ): string {
		$fio = trim( (string) ( $notification_payload['FIO'] ?? '' ) );

		return '' !== $fio ? $fio : '#' . (int) ( $notification_payload['Steering_ID'] ?? 0 );
	}
}

