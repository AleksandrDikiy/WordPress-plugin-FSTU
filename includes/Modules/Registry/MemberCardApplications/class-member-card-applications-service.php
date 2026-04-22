<?php
namespace FSTU\Modules\Registry\MemberCardApplications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service-шар модуля «Посвідчення членів ФСТУ».
 * Містить бізнес-правила й підготовку payload-ів.
 *
 * Version:     1.5.0
 * Date_update: 2026-04-10
 *
 * @package FSTU\Modules\UserFstu\MemberCardApplications
 */
class Member_Card_Applications_Service {

	private Member_Card_Applications_Repository $repository;
	private Member_Card_Applications_Protocol_Service $protocol_service;
	private Member_Card_Applications_Upload_Service $upload_service;

	public function __construct(
		?Member_Card_Applications_Repository $repository = null,
		?Member_Card_Applications_Protocol_Service $protocol_service = null,
		?Member_Card_Applications_Upload_Service $upload_service = null
	) {
		$this->repository       = $repository instanceof Member_Card_Applications_Repository ? $repository : new Member_Card_Applications_Repository();
		$this->protocol_service = $protocol_service instanceof Member_Card_Applications_Protocol_Service ? $protocol_service : new Member_Card_Applications_Protocol_Service();
		$this->upload_service   = $upload_service instanceof Member_Card_Applications_Upload_Service ? $upload_service : new Member_Card_Applications_Upload_Service();
	}

	/**
	 * @param array<string,mixed> $args
	 * @return array<string,mixed>
	 */
	public function get_list_payload( array $args ): array {
		$page     = max( 1, absint( $args['page'] ?? 1 ) );
		$per_page = max( 1, min( 50, absint( $args['per_page'] ?? 10 ) ) );
		$offset   = max( 0, absint( $args['offset'] ?? ( $page - 1 ) * $per_page ) );
		$search   = trim( (string) ( $args['search'] ?? '' ) );

		$result = $this->repository->get_list(
			[
				'search'    => $search,
				'region_id' => absint( $args['region_id'] ?? 0 ),
				'status_id' => absint( $args['status_id'] ?? 0 ),
				'type_id'   => absint( $args['type_id'] ?? 0 ),
				'per_page'  => $per_page,
				'offset'    => $offset,
			]
		);

		if ( '' !== $search && (int) ( $result['total'] ?? 0 ) <= 0 ) {
			$fallback_user_ids = $this->repository->search_user_ids_by_meta( $search );

			if ( ! empty( $fallback_user_ids ) ) {
				$result = $this->repository->get_list(
					[
						'search'    => '',
						'region_id' => absint( $args['region_id'] ?? 0 ),
						'status_id' => absint( $args['status_id'] ?? 0 ),
						'type_id'   => absint( $args['type_id'] ?? 0 ),
						'user_ids'  => $fallback_user_ids,
						'per_page'  => $per_page,
						'offset'    => $offset,
					]
				);
			}
		}

		$total = (int) ( $result['total'] ?? 0 );

		return [
			'items'       => is_array( $result['rows'] ?? null ) ? $result['rows'] : [],
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => max( 1, (int) ceil( $total / max( 1, $per_page ) ) ),
		];
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function get_single_payload( int $member_card_id ): ?array {
		$item = $this->repository->get_single_by_id( $member_card_id );

		if ( ! is_array( $item ) ) {
			return null;
		}

		$user_id = absint( $item['User_ID'] ?? 0 );

		if ( ! $this->current_user_can_access_single( $user_id ) ) {
			throw new \RuntimeException( 'member_card_view_forbidden' );
		}

		$user    = $user_id > 0 ? get_userdata( $user_id ) : false;

		$item['user_email'] = $user instanceof \WP_User ? (string) $user->user_email : '';
		$item['phone_mobile'] = $user_id > 0 ? (string) get_user_meta( $user_id, 'PhoneMobile', true ) : '';
		$item['phone_2']      = $user_id > 0 ? (string) get_user_meta( $user_id, 'Phone2', true ) : '';
		$item['birth_date']   = $user_id > 0 ? (string) get_user_meta( $user_id, 'BirthDate', true ) : '';
		$item['photo_url']    = $user_id > 0 ? $this->upload_service->get_photo_url( $user_id, true ) : '';
		$item['has_photo']    = $user_id > 0 ? $this->upload_service->has_photo( $user_id ) : false;
		$item['is_owner']     = $user_id > 0 && get_current_user_id() === $user_id;

		return $item;
	}

	/**
	 * @return array<string,array<int,array<string,mixed>>>
	 */
	public function get_dictionaries_payload(): array {
		return $this->repository->get_filter_options();
	}

	/**
	 * @param array<string,mixed> $args
	 * @return array<string,mixed>
	 */
	public function get_protocol_payload( array $args ): array {
		$page     = max( 1, absint( $args['page'] ?? 1 ) );
		$per_page = max( 1, min( 50, absint( $args['per_page'] ?? 10 ) ) );
		$offset   = max( 0, absint( $args['offset'] ?? ( $page - 1 ) * $per_page ) );
		$search   = trim( (string) ( $args['search'] ?? '' ) );

		$result = $this->repository->get_protocol( $search, $per_page, $offset );
		$total  = (int) ( $result['total'] ?? 0 );

		return [
			'items'       => is_array( $result['rows'] ?? null ) ? $result['rows'] : [],
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => max( 1, (int) ceil( $total / max( 1, $per_page ) ) ),
		];
	}

	public function get_protocol_service(): Member_Card_Applications_Protocol_Service {
		return $this->protocol_service;
	}

	/**
	 * @param array<string,mixed> $data
	 * @return array<string,mixed>
	 */
	public function create_item( array $data ): array {
		global $wpdb;

		$normalized = $this->normalize_payload( $data, false );
		$this->assert_create_permissions( $normalized['user_id'] );

		$latest = $this->repository->get_latest_member_card_by_user_id( $normalized['user_id'] );
		if ( is_array( $latest ) ) {
			throw new \RuntimeException( 'member_card_already_exists' );
		}

		$card_number = $this->resolve_card_number( $normalized['region_id'], $normalized['card_number'] );
		$timestamp   = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'START TRANSACTION' );

		try {
			$this->sync_user_profile_transactional( $normalized['user_id'], $normalized );

			$member_card_id = $this->repository->create_member_card(
				[
					'UserMemberCard_DateCreate'   => $timestamp,
					'UserCreate'                  => get_current_user_id(),
					'User_ID'                     => $normalized['user_id'],
					'Region_ID'                   => $normalized['region_id'],
					'StatusCard_ID'               => $normalized['status_card_id'],
					'TypeCard_ID'                 => $normalized['type_card_id'],
					'UserMemberCard_Number'       => $card_number,
					'UserMemberCard_LastName'     => $normalized['last_name'],
					'UserMemberCard_FirstName'    => $normalized['first_name'],
					'UserMemberCard_Patronymic'   => $normalized['patronymic'],
					'UserMemberCard_LastNameEng'  => $normalized['last_name_eng'],
					'UserMemberCard_FirstNameEng' => $normalized['first_name_eng'],
					'UserMemberCard_Summa'        => $normalized['summa'],
					'UserMemberCard_NumberNP'     => $normalized['number_np'],
				]
			);

			$this->protocol_service->log_action_transactional(
				'I',
				sprintf( 'Створено посвідчення члена ФСТУ для користувача ID %d.', $normalized['user_id'] )
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'COMMIT' );

			return [
				'member_card_id' => $member_card_id,
				'user_id'        => $normalized['user_id'],
				'card_number'    => $card_number,
			];
		} catch ( \Throwable $throwable ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'ROLLBACK' );
			$this->protocol_service->try_log_action( 'I', 'Помилка створення посвідчення члена ФСТУ.', 'create_failed' );
			throw $throwable;
		}
	}

	/**
	 * @param array<string,mixed> $data
	 * @return array<string,mixed>
	 */
	public function update_item( array $data ): array {
		global $wpdb;

		$normalized     = $this->normalize_payload( $data, true );
		$member_card_id = $normalized['member_card_id'];
		$existing       = $this->repository->get_raw_member_card_by_id( $member_card_id );

		if ( ! is_array( $existing ) ) {
			throw new \RuntimeException( 'member_card_not_found' );
		}

		$this->assert_update_permissions( (int) $existing['User_ID'] );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'START TRANSACTION' );

		try {
			$this->sync_user_profile_transactional( (int) $existing['User_ID'], $normalized );

			$update_data = [
				'UserMemberCard_LastName'     => $normalized['last_name'],
				'UserMemberCard_FirstName'    => $normalized['first_name'],
				'UserMemberCard_Patronymic'   => $normalized['patronymic'],
				'UserMemberCard_LastNameEng'  => $normalized['last_name_eng'],
				'UserMemberCard_FirstNameEng' => $normalized['first_name_eng'],
				'Region_ID'                   => $normalized['region_id'],
				'StatusCard_ID'               => $normalized['status_card_id'],
				'TypeCard_ID'                 => $normalized['type_card_id'],
				'UserMemberCard_Summa'        => $normalized['summa'],
				'UserMemberCard_NumberNP'     => $normalized['number_np'],
				'UserMemberCard_DateEdit'     => current_time( 'mysql' ),
			];

			if ( $this->current_user_can_manage_card_number() ) {
				$update_data['UserMemberCard_Number'] = $this->resolve_card_number( $normalized['region_id'], $normalized['card_number'] );
			}

			$this->repository->update_member_card( $member_card_id, $update_data );

			$this->protocol_service->log_action_transactional(
				'U',
				sprintf( 'Оновлено посвідчення члена ФСТУ ID %d.', $member_card_id )
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'COMMIT' );

			return [
				'member_card_id' => $member_card_id,
				'user_id'        => (int) $existing['User_ID'],
			];
		} catch ( \Throwable $throwable ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'ROLLBACK' );
			$this->protocol_service->try_log_action( 'U', 'Помилка оновлення посвідчення члена ФСТУ.', 'update_failed' );
			throw $throwable;
		}
	}

	/**
	 * @param array<string,mixed> $data
	 * @return array<string,mixed>
	 */
	public function reissue_item( array $data ): array {
		global $wpdb;

		$normalized     = $this->normalize_payload( $data, true );
		$member_card_id = $normalized['member_card_id'];
		$existing       = $this->repository->get_raw_member_card_by_id( $member_card_id );

		if ( ! is_array( $existing ) ) {
			throw new \RuntimeException( 'member_card_not_found' );
		}

		$this->assert_reissue_permissions( (int) $existing['User_ID'] );
		$card_number = $this->resolve_card_number( $normalized['region_id'], $normalized['card_number'] );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'START TRANSACTION' );

		try {
			$this->sync_user_profile_transactional( (int) $existing['User_ID'], $normalized );

			$new_member_card_id = $this->repository->create_member_card(
				[
					'UserMemberCard_DateCreate'   => current_time( 'mysql' ),
					'UserCreate'                  => get_current_user_id(),
					'User_ID'                     => (int) $existing['User_ID'],
					'Region_ID'                   => $normalized['region_id'],
					'StatusCard_ID'               => $normalized['status_card_id'],
					'TypeCard_ID'                 => $normalized['type_card_id'],
					'UserMemberCard_Number'       => $card_number,
					'UserMemberCard_LastName'     => $normalized['last_name'],
					'UserMemberCard_FirstName'    => $normalized['first_name'],
					'UserMemberCard_Patronymic'   => $normalized['patronymic'],
					'UserMemberCard_LastNameEng'  => $normalized['last_name_eng'],
					'UserMemberCard_FirstNameEng' => $normalized['first_name_eng'],
					'UserMemberCard_Summa'        => $normalized['summa'],
					'UserMemberCard_NumberNP'     => $normalized['number_np'],
				]
			);

			$this->protocol_service->log_action_transactional(
				'U',
				sprintf( 'Виконано перевипуск посвідчення члена ФСТУ на основі запису ID %d.', $member_card_id )
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'COMMIT' );

			return [
				'member_card_id' => $new_member_card_id,
				'user_id'        => (int) $existing['User_ID'],
				'card_number'    => $card_number,
			];
		} catch ( \Throwable $throwable ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'ROLLBACK' );
			$this->protocol_service->try_log_action( 'U', 'Помилка перевипуску посвідчення члена ФСТУ.', 'reissue_failed' );
			throw $throwable;
		}
	}

	public function delete_item( int $member_card_id ): void {
		global $wpdb;

		$existing = $this->repository->get_raw_member_card_by_id( $member_card_id );
		if ( ! is_array( $existing ) ) {
			throw new \RuntimeException( 'member_card_not_found' );
		}

		if ( ! $this->current_user_can_delete() ) {
			$this->protocol_service->try_log_action(
				'D',
				sprintf( 'Заблоковано видалення посвідчення члена ФСТУ ID %d через відсутність прав.', $member_card_id ),
				'forbidden'
			);
			throw new \RuntimeException( 'member_card_delete_forbidden' );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'START TRANSACTION' );

		try {
			$this->repository->delete_member_card( $member_card_id );

			$this->protocol_service->log_action_transactional(
				'D',
				sprintf( 'Видалено посвідчення члена ФСТУ ID %d.', $member_card_id )
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'COMMIT' );
		} catch ( \Throwable $throwable ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'ROLLBACK' );
			$this->protocol_service->try_log_action( 'D', 'Помилка видалення посвідчення члена ФСТУ.', 'delete_failed' );
			throw $throwable;
		}
	}

	/**
	 * @param array<string,mixed> $file
	 */
	public function update_photo( int $member_card_id, array $file ): array {
		$existing = $this->repository->get_raw_member_card_by_id( $member_card_id );
		if ( ! is_array( $existing ) ) {
			throw new \RuntimeException( 'member_card_not_found' );
		}

		$user_id = (int) $existing['User_ID'];
		$this->assert_photo_permissions( $user_id );

		try {
			$photo_url = $this->upload_service->store_uploaded_photo( $file, $user_id );
			$this->protocol_service->try_log_action( 'U', sprintf( 'Оновлено фото посвідчення користувача ID %d.', $user_id ), Member_Card_Applications_Protocol_Service::STATUS_SUCCESS );

			return [
				'member_card_id' => $member_card_id,
				'user_id'        => $user_id,
				'photo_url'      => $photo_url,
			];
		} catch ( \Throwable $throwable ) {
			$this->protocol_service->try_log_action( 'U', sprintf( 'Помилка оновлення фото посвідчення користувача ID %d.', $user_id ), 'photo_failed' );
			throw $throwable;
		}
	}

	/**
	 * @param array<string,mixed> $data
	 * @return array<string,mixed>
	 */
	private function normalize_payload( array $data, bool $requires_id ): array {
		$member_card_id = absint( $data['member_card_id'] ?? 0 );
		$user_id        = absint( $data['user_id'] ?? 0 );

		if ( $requires_id && $member_card_id <= 0 ) {
			throw new \RuntimeException( 'member_card_invalid_id' );
		}

		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}

		if ( $user_id <= 0 ) {
			throw new \RuntimeException( 'member_card_invalid_user' );
		}

		$normalized = [
			'member_card_id' => $member_card_id,
			'user_id'        => $user_id,
			'region_id'      => absint( $data['region_id'] ?? 0 ),
			'status_card_id' => absint( $data['status_card_id'] ?? 0 ),
			'type_card_id'   => absint( $data['type_card_id'] ?? 0 ),
			'card_number'    => absint( $data['card_number'] ?? 0 ),
			'last_name'      => sanitize_text_field( (string) ( $data['last_name'] ?? '' ) ),
			'first_name'     => sanitize_text_field( (string) ( $data['first_name'] ?? '' ) ),
			'patronymic'     => sanitize_text_field( (string) ( $data['patronymic'] ?? '' ) ),
			'last_name_eng'  => sanitize_text_field( (string) ( $data['last_name_eng'] ?? '' ) ),
			'first_name_eng' => sanitize_text_field( (string) ( $data['first_name_eng'] ?? '' ) ),
			'number_np'      => sanitize_text_field( (string) ( $data['number_np'] ?? '' ) ),
			'birth_date'     => sanitize_text_field( (string) ( $data['birth_date'] ?? '' ) ),
			'user_email'     => sanitize_email( (string) ( $data['user_email'] ?? '' ) ),
			'phone_mobile'   => sanitize_text_field( (string) ( $data['phone_mobile'] ?? '' ) ),
			'phone_2'        => sanitize_text_field( (string) ( $data['phone_2'] ?? '' ) ),
			'summa'          => round( (float) str_replace( ',', '.', (string) ( $data['summa'] ?? '0' ) ), 2 ),
		];

		$this->assert_required_fields( $normalized );

		return $normalized;
	}

	/**
	 * @param array<string,mixed> $data
	 */
	private function assert_required_fields( array $data ): void {
		if ( (int) $data['region_id'] <= 0 ) {
			throw new \RuntimeException( 'member_card_region_required' );
		}

		if ( (int) $data['status_card_id'] <= 0 ) {
			throw new \RuntimeException( 'member_card_status_required' );
		}

		if ( (int) $data['type_card_id'] <= 0 ) {
			throw new \RuntimeException( 'member_card_type_required' );
		}

		if ( '' === (string) $data['last_name'] || '' === (string) $data['first_name'] || '' === (string) $data['patronymic'] ) {
			throw new \RuntimeException( 'member_card_name_required' );
		}

		if ( '' !== (string) $data['user_email'] && ! is_email( (string) $data['user_email'] ) ) {
			throw new \RuntimeException( 'member_card_email_invalid' );
		}
	}

	private function resolve_card_number( int $region_id, int $requested_number ): int {
		if ( $this->current_user_can_manage_card_number() && $requested_number > 0 ) {
			return $requested_number;
		}

		return $this->repository->get_next_member_card_number( $region_id );
	}

	private function current_user_can_manage(): bool {
		return \FSTU\Core\Capabilities::current_user_can_manage_member_card_applications();
	}

	private function current_user_can_view(): bool {
		return \FSTU\Core\Capabilities::current_user_can_view_member_card_applications();
	}

	private function current_user_can_delete(): bool {
		return \FSTU\Core\Capabilities::current_user_can_delete_member_card_applications();
	}

	private function current_user_can_manage_card_number(): bool {
		return \FSTU\Core\Capabilities::current_user_can_manage_member_card_number();
	}

	private function current_user_can_self_manage(): bool {
		return \FSTU\Core\Capabilities::current_user_can_self_manage_member_card_applications();
	}

	private function current_user_can_reissue(): bool {
		return \FSTU\Core\Capabilities::current_user_can_reissue_member_card_applications();
	}

	private function current_user_can_update_photo(): bool {
		return \FSTU\Core\Capabilities::current_user_can_update_member_card_applications_photo();
	}

	private function current_user_can_access_single( int $user_id ): bool {
		if ( $this->current_user_can_manage() || $this->current_user_can_view() ) {
			return true;
		}

		$current_user_id = get_current_user_id();

		if ( $current_user_id <= 0 || $current_user_id !== $user_id ) {
			return false;
		}

		return $this->current_user_can_self_manage() || $this->current_user_can_reissue() || $this->current_user_can_update_photo();
	}

	private function assert_create_permissions( int $user_id ): void {
		if ( $this->current_user_can_manage() ) {
			return;
		}

		if ( ! $this->current_user_can_self_manage() || get_current_user_id() !== $user_id ) {
			throw new \RuntimeException( 'member_card_create_forbidden' );
		}
	}

	private function assert_update_permissions( int $user_id ): void {
		if ( $this->current_user_can_manage() ) {
			return;
		}

		throw new \RuntimeException( 'member_card_update_forbidden' );
	}

	private function assert_reissue_permissions( int $user_id ): void {
		if ( $this->current_user_can_manage() || $this->current_user_can_reissue() ) {
			if ( $this->current_user_can_manage() || get_current_user_id() === $user_id ) {
				return;
			}
		}

		throw new \RuntimeException( 'member_card_reissue_forbidden' );
	}

	private function assert_photo_permissions( int $user_id ): void {
		if ( $this->current_user_can_manage() ) {
			return;
		}

		if ( $this->current_user_can_update_photo() && get_current_user_id() === $user_id ) {
			return;
		}

		throw new \RuntimeException( 'member_card_photo_forbidden' );
	}

	/**
	 * @param array<string,mixed> $data
	 */
	private function sync_user_profile_transactional( int $user_id, array $data ): void {
		if ( $user_id <= 0 ) {
			return;
		}

		$email = (string) ( $data['user_email'] ?? '' );
		if ( '' !== $email ) {
			$result = wp_update_user(
				[
					'ID'         => $user_id,
					'user_email' => $email,
				]
			);

			if ( is_wp_error( $result ) ) {
				throw new \RuntimeException( 'member_card_user_update_failed' );
			}
		}

		update_user_meta( $user_id, 'last_name', (string) ( $data['last_name'] ?? '' ) );
		update_user_meta( $user_id, 'first_name', (string) ( $data['first_name'] ?? '' ) );
		update_user_meta( $user_id, 'Patronymic', (string) ( $data['patronymic'] ?? '' ) );
		update_user_meta( $user_id, 'BirthDate', (string) ( $data['birth_date'] ?? '' ) );
		update_user_meta( $user_id, 'PhoneMobile', (string) ( $data['phone_mobile'] ?? '' ) );
		update_user_meta( $user_id, 'Phone2', (string) ( $data['phone_2'] ?? '' ) );
	}
}

