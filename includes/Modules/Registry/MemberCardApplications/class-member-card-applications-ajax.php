<?php
namespace FSTU\Modules\Registry\MemberCardApplications;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX-обробники модуля «Посвідчення членів ФСТУ».
 *
 * Version:     1.5.0
 * Date_update: 2026-04-10
 *
 * @package FSTU\Modules\UserFstu\MemberCardApplications
 */
class Member_Card_Applications_Ajax {

	private const DEFAULT_PER_PAGE = 10;
	private const MAX_PER_PAGE = 50;
	private const MAX_SEARCH_LENGTH = 100;

	private ?Member_Card_Applications_Service $service = null;
	private ?Member_Card_Applications_Protocol_Service $protocol_service = null;

	public function init(): void {
		add_action( 'wp_ajax_fstu_member_card_applications_get_list', [ $this, 'handle_get_list' ] );
		add_action( 'wp_ajax_fstu_member_card_applications_get_single', [ $this, 'handle_get_single' ] );
		add_action( 'wp_ajax_fstu_member_card_applications_get_dictionaries', [ $this, 'handle_get_dictionaries' ] );
		add_action( 'wp_ajax_fstu_member_card_applications_get_protocol', [ $this, 'handle_get_protocol' ] );
		add_action( 'wp_ajax_fstu_member_card_applications_create', [ $this, 'handle_create' ] );
		add_action( 'wp_ajax_fstu_member_card_applications_update', [ $this, 'handle_update' ] );
		add_action( 'wp_ajax_fstu_member_card_applications_reissue', [ $this, 'handle_reissue' ] );
		add_action( 'wp_ajax_fstu_member_card_applications_update_photo', [ $this, 'handle_update_photo' ] );
		add_action( 'wp_ajax_fstu_member_card_applications_delete', [ $this, 'handle_delete' ] );
	}

	public function handle_get_list(): void {
		check_ajax_referer( Member_Card_Applications_List::NONCE_ACTION, 'nonce' );

		if ( ! Capabilities::current_user_can_view_member_card_applications() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду посвідчень.', 'fstu' ) ] );
		}

		$search    = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$search    = mb_substr( $search, 0, self::MAX_SEARCH_LENGTH );
		$page      = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page  = min( max( 1, absint( $_POST['per_page'] ?? self::DEFAULT_PER_PAGE ) ), self::MAX_PER_PAGE );
		$region_id = absint( $_POST['region_id'] ?? 0 );
		$status_id = absint( $_POST['status_id'] ?? 0 );
		$type_id   = absint( $_POST['type_id'] ?? 0 );
		$offset    = ( $page - 1 ) * $per_page;

		$payload = $this->get_service()->get_list_payload(
			[
				'search'    => $search,
				'page'      => $page,
				'per_page'  => $per_page,
				'offset'    => $offset,
				'region_id' => $region_id,
				'status_id' => $status_id,
				'type_id'   => $type_id,
			]
		);

		wp_send_json_success(
			[
				'html'        => $this->build_rows( $payload['items'], $offset, Capabilities::get_member_card_applications_permissions() ),
				'total'       => $payload['total'],
				'page'        => $payload['page'],
				'per_page'    => $payload['per_page'],
				'total_pages' => $payload['total_pages'],
			]
		);
	}

	public function handle_get_single(): void {
		check_ajax_referer( Member_Card_Applications_List::NONCE_ACTION, 'nonce' );

		if ( ! Capabilities::current_user_can_view_member_card_applications()
			&& ! Capabilities::current_user_can_self_manage_member_card_applications()
			&& ! Capabilities::current_user_can_reissue_member_card_applications()
			&& ! Capabilities::current_user_can_update_member_card_applications_photo() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду картки посвідчення.', 'fstu' ) ] );
		}

		$member_card_id = absint( $_POST['member_card_id'] ?? 0 );
		if ( $member_card_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		try {
			$item = $this->get_service()->get_single_payload( $member_card_id );
			if ( ! is_array( $item ) ) {
				wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
			}
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_error_message( trim( $throwable->getMessage() ), 'view' ) ] );
		}

		wp_send_json_success( [ 'item' => $item ] );
	}

	public function handle_get_dictionaries(): void {
		check_ajax_referer( Member_Card_Applications_List::NONCE_ACTION, 'nonce' );

		if ( ! Capabilities::current_user_can_view_member_card_applications()
			&& ! Capabilities::current_user_can_self_manage_member_card_applications()
			&& ! Capabilities::current_user_can_reissue_member_card_applications()
			&& ! Capabilities::current_user_can_update_member_card_applications_photo() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для завантаження довідників.', 'fstu' ) ] );
		}

		wp_send_json_success( [ 'items' => $this->get_service()->get_dictionaries_payload() ] );
	}

	public function handle_get_protocol(): void {
		check_ajax_referer( Member_Card_Applications_List::NONCE_ACTION, 'nonce' );

		if ( ! Capabilities::current_user_can_view_member_card_applications_protocol() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду протоколу.', 'fstu' ) ] );
		}

		$search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$search   = mb_substr( $search, 0, self::MAX_SEARCH_LENGTH );
		$page     = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page = min( max( 1, absint( $_POST['per_page'] ?? self::DEFAULT_PER_PAGE ) ), self::MAX_PER_PAGE );
		$offset   = ( $page - 1 ) * $per_page;

		$payload = $this->get_service()->get_protocol_payload(
			[
				'search'   => $search,
				'page'     => $page,
				'per_page' => $per_page,
				'offset'   => $offset,
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

	public function handle_create(): void {
		check_ajax_referer( Member_Card_Applications_List::NONCE_ACTION, 'nonce' );

		if ( ! Capabilities::current_user_can_manage_member_card_applications() && ! Capabilities::current_user_can_self_manage_member_card_applications() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для створення посвідчення.', 'fstu' ) ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		try {
			$result = $this->get_service()->create_item( $this->sanitize_member_card_payload() );
			wp_send_json_success(
				[
					'message'        => __( 'Посвідчення успішно створено.', 'fstu' ),
					'member_card_id' => (int) ( $result['member_card_id'] ?? 0 ),
					'user_id'        => (int) ( $result['user_id'] ?? 0 ),
				]
			);
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_error_message( trim( $throwable->getMessage() ), 'create' ) ] );
		}
	}

	public function handle_update(): void {
		check_ajax_referer( Member_Card_Applications_List::NONCE_ACTION, 'nonce' );

		if ( ! Capabilities::current_user_can_manage_member_card_applications() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для редагування посвідчення.', 'fstu' ) ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		try {
			$result = $this->get_service()->update_item( $this->sanitize_member_card_payload( true ) );
			wp_send_json_success(
				[
					'message'        => __( 'Посвідчення успішно оновлено.', 'fstu' ),
					'member_card_id' => (int) ( $result['member_card_id'] ?? 0 ),
					'user_id'        => (int) ( $result['user_id'] ?? 0 ),
				]
			);
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_error_message( trim( $throwable->getMessage() ), 'update' ) ] );
		}
	}

	public function handle_reissue(): void {
		check_ajax_referer( Member_Card_Applications_List::NONCE_ACTION, 'nonce' );

		if ( ! Capabilities::current_user_can_reissue_member_card_applications() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для перевипуску посвідчення.', 'fstu' ) ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		try {
			$result = $this->get_service()->reissue_item( $this->sanitize_member_card_payload( true ) );
			wp_send_json_success(
				[
					'message'        => __( 'Перевипуск посвідчення успішно виконано.', 'fstu' ),
					'member_card_id' => (int) ( $result['member_card_id'] ?? 0 ),
					'user_id'        => (int) ( $result['user_id'] ?? 0 ),
				]
			);
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_error_message( trim( $throwable->getMessage() ), 'reissue' ) ] );
		}
	}

	public function handle_update_photo(): void {
		check_ajax_referer( Member_Card_Applications_List::NONCE_ACTION, 'nonce' );

		if ( ! Capabilities::current_user_can_update_member_card_applications_photo() && ! Capabilities::current_user_can_manage_member_card_applications() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для оновлення фото.', 'fstu' ) ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		$member_card_id = absint( $_POST['member_card_id'] ?? 0 );
		$file           = isset( $_FILES['photo_file'] ) && is_array( $_FILES['photo_file'] ) ? $_FILES['photo_file'] : [];

		try {
			$result = $this->get_service()->update_photo( $member_card_id, $file );
			wp_send_json_success(
				[
					'message'        => __( 'Фото посвідчення успішно оновлено.', 'fstu' ),
					'member_card_id' => (int) ( $result['member_card_id'] ?? 0 ),
					'user_id'        => (int) ( $result['user_id'] ?? 0 ),
					'photo_url'      => (string) ( $result['photo_url'] ?? '' ),
				]
			);
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_error_message( trim( $throwable->getMessage() ), 'photo' ) ] );
		}
	}

	public function handle_delete(): void {
		check_ajax_referer( Member_Card_Applications_List::NONCE_ACTION, 'nonce' );

		if ( ! Capabilities::current_user_can_delete_member_card_applications() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для видалення посвідчення.', 'fstu' ) ] );
		}

		$member_card_id = absint( $_POST['member_card_id'] ?? 0 );

		try {
			$this->get_service()->delete_item( $member_card_id );
			wp_send_json_success( [ 'message' => __( 'Посвідчення успішно видалено.', 'fstu' ) ] );
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_error_message( trim( $throwable->getMessage() ), 'delete' ) ] );
		}
	}

	private function is_honeypot_triggered(): bool {
		$honeypot = sanitize_text_field( wp_unslash( $_POST['fstu_website'] ?? '' ) );

		return '' !== trim( $honeypot );
	}

	/**
	 * @return array<string,mixed>
	 */
	private function sanitize_member_card_payload( bool $requires_id = false ): array {
		$payload = [
			'member_card_id' => absint( $_POST['member_card_id'] ?? 0 ),
			'user_id'        => absint( $_POST['user_id'] ?? 0 ),
			'region_id'      => absint( $_POST['region_id'] ?? 0 ),
			'status_card_id' => absint( $_POST['status_card_id'] ?? 0 ),
			'type_card_id'   => absint( $_POST['type_card_id'] ?? 0 ),
			'card_number'    => absint( $_POST['card_number'] ?? 0 ),
			'last_name'      => sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) ),
			'first_name'     => sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) ),
			'patronymic'     => sanitize_text_field( wp_unslash( $_POST['patronymic'] ?? '' ) ),
			'last_name_eng'  => sanitize_text_field( wp_unslash( $_POST['last_name_eng'] ?? '' ) ),
			'first_name_eng' => sanitize_text_field( wp_unslash( $_POST['first_name_eng'] ?? '' ) ),
			'number_np'      => sanitize_text_field( wp_unslash( $_POST['number_np'] ?? '' ) ),
			'summa'          => sanitize_text_field( wp_unslash( $_POST['summa'] ?? '' ) ),
			'birth_date'     => sanitize_text_field( wp_unslash( $_POST['birth_date'] ?? '' ) ),
			'user_email'     => sanitize_email( wp_unslash( $_POST['user_email'] ?? '' ) ),
			'phone_mobile'   => sanitize_text_field( wp_unslash( $_POST['phone_mobile'] ?? '' ) ),
			'phone_2'        => sanitize_text_field( wp_unslash( $_POST['phone_2'] ?? '' ) ),
		];

		if ( $requires_id && $payload['member_card_id'] <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор посвідчення.', 'fstu' ) ] );
		}

		return $payload;
	}

	private function get_error_message( string $marker, string $context ): string {
		return match ( $marker ) {
			'member_card_not_found'          => __( 'Запис посвідчення не знайдено.', 'fstu' ),
			'member_card_invalid_id'         => __( 'Невірний ідентифікатор посвідчення.', 'fstu' ),
			'member_card_invalid_user'       => __( 'Не вдалося визначити користувача для операції.', 'fstu' ),
			'member_card_view_forbidden'     => __( 'Немає прав для перегляду картки посвідчення.', 'fstu' ),
			'member_card_already_exists'     => __( 'Для цього користувача посвідчення вже існує. Використайте перевипуск.', 'fstu' ),
			'member_card_region_required'    => __( 'Оберіть регіон.', 'fstu' ),
			'member_card_status_required'    => __( 'Оберіть статус посвідчення.', 'fstu' ),
			'member_card_type_required'      => __( 'Оберіть тип посвідчення.', 'fstu' ),
			'member_card_name_required'      => __( 'Заповніть прізвище, ім’я та по батькові.', 'fstu' ),
			'member_card_email_invalid'      => __( 'Вказано некоректний email.', 'fstu' ),
			'member_card_create_forbidden'   => __( 'Недостатньо прав для створення посвідчення.', 'fstu' ),
			'member_card_update_forbidden'   => __( 'Недостатньо прав для редагування посвідчення.', 'fstu' ),
			'member_card_reissue_forbidden'  => __( 'Недостатньо прав для перевипуску посвідчення.', 'fstu' ),
			'member_card_photo_forbidden'    => __( 'Недостатньо прав для оновлення фото.', 'fstu' ),
			'member_card_delete_forbidden'   => __( 'Недостатньо прав для видалення посвідчення.', 'fstu' ),
			'member_card_user_update_failed' => __( 'Не вдалося оновити пов’язані дані користувача.', 'fstu' ),
			'member_card_insert_failed'      => __( 'Не вдалося створити посвідчення.', 'fstu' ),
			'member_card_update_failed'      => __( 'Не вдалося оновити посвідчення.', 'fstu' ),
			'member_card_delete_failed'      => __( 'Не вдалося видалити посвідчення.', 'fstu' ),
			'photo_invalid_user'             => __( 'Не вдалося визначити користувача для фото.', 'fstu' ),
			'photo_required'                 => __( 'Оберіть файл фото.', 'fstu' ),
			'photo_not_uploaded'             => __( 'Файл фото не було отримано.', 'fstu' ),
			'photo_empty'                    => __( 'Файл фото порожній.', 'fstu' ),
			'photo_too_large'                => __( 'Файл фото завеликий.', 'fstu' ),
			'photo_invalid_type', 'photo_invalid_image' => __( 'Дозволені лише коректні зображення JPEG/PNG/WebP.', 'fstu' ),
			'photo_directory_unavailable', 'photo_editor_unavailable', 'photo_save_failed', 'photo_upload_failed' => __( 'Не вдалося зберегти фото посвідчення.', 'fstu' ),
			default => match ( $context ) {
				'create'  => __( 'Сталася помилка створення посвідчення.', 'fstu' ),
				'view'    => __( 'Сталася помилка перегляду посвідчення.', 'fstu' ),
				'update'  => __( 'Сталася помилка оновлення посвідчення.', 'fstu' ),
				'reissue' => __( 'Сталася помилка перевипуску посвідчення.', 'fstu' ),
				'photo'   => __( 'Сталася помилка оновлення фото посвідчення.', 'fstu' ),
				'delete'  => __( 'Сталася помилка видалення посвідчення.', 'fstu' ),
				default   => __( 'Сталася службова помилка.', 'fstu' ),
			},
		};
	}

	private function get_service(): Member_Card_Applications_Service {
		if ( null === $this->service ) {
			$this->service = new Member_Card_Applications_Service();
		}

		return $this->service;
	}

	private function get_protocol_service(): Member_Card_Applications_Protocol_Service {
		if ( null === $this->protocol_service ) {
			$this->protocol_service = $this->get_service()->get_protocol_service();
		}

		return $this->protocol_service;
	}

	/**
	 * @param array<int,array<string,mixed>> $items
	 * @param array<string,bool>             $permissions
	 */
	private function build_rows( array $items, int $offset, array $permissions ): string {
		if ( empty( $items ) ) {
			return '<tr class="fstu-row"><td colspan="8" class="fstu-no-results">' . esc_html__( 'Записів не знайдено.', 'fstu' ) . '</td></tr>';
		}

		$html = '';

		foreach ( $items as $index => $item ) {
			$member_card_id = absint( $item['UserMemberCard_ID'] ?? 0 );
			$row_number     = $offset + $index + 1;
			$fio_label      = (string) ( $item['FIO'] ?? '—' );
			$card_number    = '' !== trim( (string) ( $item['CardNumber'] ?? '' ) )
				? (string) $item['CardNumber']
				: (string) ( $item['UserMemberCard_Number'] ?? '' );
			$date_value     = (string) ( $item['UserMemberCard_DateCreate'] ?? '' );
			$date_label     = '' !== $date_value ? wp_date( 'd.m.Y', strtotime( $date_value ) ) : '—';

			$html .= '<tr class="fstu-row">';
			$html .= '<td class="fstu-td fstu-td--num">' . esc_html( (string) $row_number ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--name">';
			if ( $member_card_id > 0 ) {
				$html .= '<button type="button" class="fstu-link-button fstu-member-card-applications-view-btn" data-member-card-id="' . esc_attr( (string) $member_card_id ) . '">' . esc_html( $fio_label ) . '</button>';
			} else {
				$html .= esc_html( $fio_label );
			}
			$html .= '</td>';
			$html .= '<td class="fstu-td">' . esc_html( $card_number ) . '</td>';
			$html .= '<td class="fstu-td">' . esc_html( (string) ( $item['Region_Name'] ?? '—' ) ) . '</td>';
			$html .= '<td class="fstu-td">' . esc_html( (string) ( $item['StatusCard_Name'] ?? '—' ) ) . '</td>';
			$html .= '<td class="fstu-td">' . esc_html( (string) ( $item['TypeCard_Name'] ?? '—' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--date">' . esc_html( $date_label ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--actions">' . $this->build_actions_dropdown( $member_card_id, $permissions ) . '</td>';
			$html .= '</tr>';
		}

		return $html;
	}

	/**
	 * @param array<string,bool> $permissions
	 */
	private function build_actions_dropdown( int $member_card_id, array $permissions ): string {
		if ( $member_card_id <= 0 ) {
			return '—';
		}

		$items = [];
		$items[] = '<button type="button" class="fstu-member-card-applications-dropdown__item fstu-member-card-applications-view-btn" data-member-card-id="' . esc_attr( (string) $member_card_id ) . '">' . esc_html__( 'Перегляд', 'fstu' ) . '</button>';

		if ( ! empty( $permissions['canManage'] ) ) {
			$items[] = '<button type="button" class="fstu-member-card-applications-dropdown__item fstu-member-card-applications-edit-btn" data-member-card-id="' . esc_attr( (string) $member_card_id ) . '">' . esc_html__( 'Редагування', 'fstu' ) . '</button>';
		}

		if ( ! empty( $permissions['canReissue'] ) ) {
			$items[] = '<button type="button" class="fstu-member-card-applications-dropdown__item fstu-member-card-applications-reissue-btn" data-member-card-id="' . esc_attr( (string) $member_card_id ) . '">' . esc_html__( 'Перевипуск', 'fstu' ) . '</button>';
		}

		if ( ! empty( $permissions['canDelete'] ) ) {
			$items[] = '<button type="button" class="fstu-member-card-applications-dropdown__item fstu-member-card-applications-dropdown__item--danger fstu-member-card-applications-delete-btn" data-member-card-id="' . esc_attr( (string) $member_card_id ) . '">' . esc_html__( 'Видалити', 'fstu' ) . '</button>';
		}

		return '<div class="fstu-member-card-applications-dropdown"><button type="button" class="fstu-dropdown-toggle fstu-member-card-applications-dropdown__toggle" aria-expanded="false" title="' . esc_attr__( 'Дії', 'fstu' ) . '" aria-label="' . esc_attr__( 'Дії', 'fstu' ) . '">▼</button><div class="fstu-dropdown-menu fstu-member-card-applications-dropdown__menu" role="menu">' . implode( '', $items ) . '</div></div>';
	}
}

