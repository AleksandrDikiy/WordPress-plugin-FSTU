<?php
namespace FSTU\Modules\Registry\Guidance;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX-обробники модуля «Склад керівних органів ФСТУ».
 *
 * Version:     1.1.2
 * Date_update: 2026-04-12
 *
 * @package FSTU\Modules\Registry\Guidance
 */
class Guidance_Ajax {

	private const DEFAULT_PER_PAGE  = 10;
	private const MAX_PER_PAGE      = 50;
	private const MAX_SEARCH_LENGTH = 100;
	private const MAX_USERS         = 20;

	private ?Guidance_Repository $repository = null;
	private ?Guidance_Service $service = null;
	private ?Guidance_Protocol_Service $protocol_service = null;

	public function init(): void {
		add_action( 'wp_ajax_fstu_guidance_get_list', [ $this, 'handle_get_list' ] );
		add_action( 'wp_ajax_nopriv_fstu_guidance_get_list', [ $this, 'handle_get_list' ] );
		add_action( 'wp_ajax_fstu_guidance_get_single', [ $this, 'handle_get_single' ] );
		add_action( 'wp_ajax_nopriv_fstu_guidance_get_single', [ $this, 'handle_get_single' ] );
		add_action( 'wp_ajax_fstu_guidance_get_filters', [ $this, 'handle_get_filters' ] );
		add_action( 'wp_ajax_nopriv_fstu_guidance_get_filters', [ $this, 'handle_get_filters' ] );
		add_action( 'wp_ajax_fstu_guidance_get_member_guidance_options', [ $this, 'handle_get_member_guidance_options' ] );
		add_action( 'wp_ajax_fstu_guidance_search_users', [ $this, 'handle_search_users' ] );
		add_action( 'wp_ajax_fstu_guidance_create', [ $this, 'handle_create' ] );
		add_action( 'wp_ajax_fstu_guidance_update', [ $this, 'handle_update' ] );
		add_action( 'wp_ajax_fstu_guidance_delete', [ $this, 'handle_delete' ] );
		add_action( 'wp_ajax_fstu_guidance_get_protocol', [ $this, 'handle_get_protocol' ] );
	}

	public function handle_get_list(): void {
		check_ajax_referer( Guidance_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view_list() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду списку.', 'fstu' ) ] );
		}

		$search          = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$search          = mb_substr( $search, 0, self::MAX_SEARCH_LENGTH );
		$page            = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page        = min( max( 1, absint( $_POST['per_page'] ?? self::DEFAULT_PER_PAGE ) ), self::MAX_PER_PAGE );
		$typeguidance_id = absint( $_POST['typeguidance_id'] ?? 1 );
		$offset          = ( $page - 1 ) * $per_page;

		try {
			$payload = $this->get_service()->get_list_payload(
				[
					'search'          => $search,
					'page'            => $page,
					'per_page'        => $per_page,
					'typeguidance_id' => $typeguidance_id,
					'offset'          => $offset,
				]
			);

			wp_send_json_success(
				[
					'html'        => $this->build_rows( $payload['items'], ( (int) $payload['page'] - 1 ) * (int) $payload['per_page'], $this->get_permissions() ),
					'total'       => $payload['total'],
					'page'        => $payload['page'],
					'per_page'    => $payload['per_page'],
					'total_pages' => $payload['total_pages'],
				]
			);
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_safe_public_message( $throwable, __( 'Помилка завантаження даних.', 'fstu' ) ) ] );
		}
	}

	public function handle_get_single(): void {
		check_ajax_referer( Guidance_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view_card() ) {
			wp_send_json_error( [ 'message' => __( 'Перегляд картки доступний лише авторизованим користувачам.', 'fstu' ) ] );
		}

		$guidance_id = absint( $_POST['guidance_id'] ?? 0 );
		if ( $guidance_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		try {
			$item = $this->get_service()->get_single_payload( $guidance_id );
			if ( ! is_array( $item ) ) {
				wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
			}


			wp_send_json_success( [ 'item' => $item ] );
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_safe_public_message( $throwable, __( 'Помилка завантаження картки.', 'fstu' ) ) ] );
		}
	}

	public function handle_get_filters(): void {
		check_ajax_referer( Guidance_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view_list() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для завантаження довідників.', 'fstu' ) ] );
		}

		try {
			wp_send_json_success( $this->get_service()->get_filters_payload() );
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_safe_public_message( $throwable, __( 'Не вдалося завантажити довідники.', 'fstu' ) ) ] );
		}
	}

	public function handle_get_member_guidance_options(): void {
		check_ajax_referer( Guidance_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для завантаження посад.', 'fstu' ) ] );
		}

		$typeguidance_id = absint( $_POST['typeguidance_id'] ?? 1 );

		try {
			wp_send_json_success( [ 'items' => $this->get_service()->get_member_guidance_options_payload( $typeguidance_id ) ] );
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_safe_public_message( $throwable, __( 'Не вдалося завантажити посади.', 'fstu' ) ) ] );
		}
	}

	public function handle_search_users(): void {
		check_ajax_referer( Guidance_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для пошуку користувачів.', 'fstu' ) ] );
		}

		$search = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$search = mb_substr( $search, 0, self::MAX_SEARCH_LENGTH );

		try {
			wp_send_json_success( [ 'items' => $this->get_service()->search_users_payload( $search, self::MAX_USERS ) ] );
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_safe_public_message( $throwable, __( 'Не вдалося завантажити користувачів.', 'fstu' ) ) ] );
		}
	}

	public function handle_get_protocol(): void {
		check_ajax_referer( Guidance_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_protocol() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду протоколу.', 'fstu' ) ] );
		}

		$search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$search   = mb_substr( $search, 0, self::MAX_SEARCH_LENGTH );
		$page     = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page = min( max( 1, absint( $_POST['per_page'] ?? self::DEFAULT_PER_PAGE ) ), self::MAX_PER_PAGE );
		$offset   = ( $page - 1 ) * $per_page;

		try {
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
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_safe_public_message( $throwable, __( 'Не вдалося завантажити протокол.', 'fstu' ) ) ] );
		}
	}

	public function handle_create(): void {
		check_ajax_referer( Guidance_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для додавання запису.', 'fstu' ) ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		try {
			$result = $this->get_service()->create_item( $this->sanitize_item_data() );
			wp_send_json_success(
				[
					'message'     => __( 'Запис успішно збережено.', 'fstu' ),
					'guidance_id' => (int) ( $result['guidance_id'] ?? 0 ),
				]
			);
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_safe_error_message( $throwable ) ] );
		}
	}

	public function handle_update(): void {
		check_ajax_referer( Guidance_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для редагування запису.', 'fstu' ) ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		try {
			$data                = $this->sanitize_item_data();
			$data['guidance_id'] = absint( $_POST['guidance_id'] ?? 0 );
			$result              = $this->get_service()->update_item( $data );
			wp_send_json_success(
				[
					'message'     => __( 'Запис успішно оновлено.', 'fstu' ),
					'guidance_id' => (int) ( $result['guidance_id'] ?? 0 ),
				]
			);
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_safe_error_message( $throwable ) ] );
		}
	}

	public function handle_delete(): void {
		check_ajax_referer( Guidance_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_delete() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для видалення запису.', 'fstu' ) ] );
		}

		$guidance_id = absint( $_POST['guidance_id'] ?? 0 );
		if ( $guidance_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		try {
			$this->get_service()->delete_item( $guidance_id );
			wp_send_json_success( [ 'message' => __( 'Запис успішно видалено.', 'fstu' ) ] );
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_safe_error_message( $throwable ) ] );
		}
	}

	/**
	 * @param array<int,array<string,mixed>> $items
	 * @param array<string,bool> $permissions
	 */
	private function build_rows( array $items, int $offset, array $permissions ): string {
		if ( empty( $items ) ) {
			return '<tr class="fstu-row"><td colspan="7" class="fstu-no-results">' . esc_html__( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ) . '</td></tr>';
		}

		$can_view_card = ! empty( $permissions['canViewCard'] );
		$can_manage    = ! empty( $permissions['canManage'] );
		$can_delete    = ! empty( $permissions['canDelete'] );
		$can_view_contacts = ! empty( $permissions['canViewContactsInList'] );
		$html          = '';
		$number        = $offset;

		foreach ( $items as $item ) {
			$number++;
			$guidance_id  = (int) ( $item['Guidance_ID'] ?? 0 );
			$position     = (string) ( $item['MemberGuidance_Name'] ?? '' );
			$profile_url  = (string) ( $item['ProfileUrl'] ?? '' );
			$phones       = (string) ( $item['Phones'] ?? '' );
			$email        = (string) ( $item['user_email'] ?? '' );

			$html .= '<tr class="fstu-row">';
			$html .= '<td class="fstu-td fstu-td--num">' . esc_html( (string) $number ) . '</td>';
			$html .= '<td class="fstu-td">' . esc_html( (string) ( $item['TypeGuidance_Name'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--position">';
			if ( $can_view_card && $guidance_id > 0 ) {
				$html .= '<a href="#" class="fstu-guidance-link fstu-guidance-view-link" data-guidance-id="' . esc_attr( (string) $guidance_id ) . '">' . esc_html( $position ) . '</a>';
			} else {
				$html .= esc_html( $position );
			}
			$html .= '</td>';
			$html .= '<td class="fstu-td fstu-td--name">';
			if ( '' !== $profile_url ) {
				$html .= '<a class="fstu-guidance-link" href="' . esc_url( $profile_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( (string) ( $item['FIO'] ?? '' ) ) . '</a>';
			} else {
				$html .= esc_html( (string) ( $item['FIO'] ?? '' ) );
			}
			$html .= '</td>';
			$html .= '<td class="fstu-td fstu-td--phones">' . ( $can_view_contacts ? nl2br( esc_html( $phones ) ) : '—' ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--email">' . ( $can_view_contacts ? esc_html( $email ) : '—' ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--actions">' . $this->build_actions_dropdown( $guidance_id, $can_view_card, $can_manage, $can_delete ) . '</td>';
			$html .= '</tr>';
		}

		return $html;
	}

	private function build_actions_dropdown( int $guidance_id, bool $can_view_card, bool $can_manage, bool $can_delete ): string {
		if ( $guidance_id <= 0 || ( ! $can_view_card && ! $can_manage && ! $can_delete ) ) {
			return '—';
		}

		$html  = '<div class="fstu-dropdown" data-guidance-id="' . esc_attr( (string) $guidance_id ) . '">';
		$html .= '<button type="button" class="fstu-dropdown-toggle" title="Дії" aria-label="Дії">▼</button>';
		$html .= '<div class="fstu-dropdown-menu">';

		if ( $can_view_card ) {
			$html .= '<button type="button" class="fstu-dropdown-item fstu-guidance-view-btn" data-guidance-id="' . esc_attr( (string) $guidance_id ) . '">' . esc_html__( 'Перегляд', 'fstu' ) . '</button>';
		}

		if ( $can_manage ) {
			$html .= '<button type="button" class="fstu-dropdown-item fstu-guidance-edit-btn" data-guidance-id="' . esc_attr( (string) $guidance_id ) . '">' . esc_html__( 'Редагувати', 'fstu' ) . '</button>';
		}

		if ( $can_delete ) {
			$html .= '<button type="button" class="fstu-dropdown-item fstu-dropdown-item--danger fstu-guidance-delete-btn" data-guidance-id="' . esc_attr( (string) $guidance_id ) . '">' . esc_html__( 'Видалити', 'fstu' ) . '</button>';
		}

		$html .= '</div></div>';

		return $html;
	}

	/**
	 * @return array<string,mixed>
	 */
	private function sanitize_item_data(): array {
		return [
			'typeguidance_id'    => absint( $_POST['typeguidance_id'] ?? 0 ),
			'member_guidance_id' => absint( $_POST['member_guidance_id'] ?? 0 ),
			'user_id'            => absint( $_POST['user_id'] ?? 0 ),
			'guidance_notes'     => sanitize_textarea_field( wp_unslash( $_POST['guidance_notes'] ?? '' ) ),
		];
	}

	private function is_honeypot_triggered(): bool {
		return '' !== trim( (string) wp_unslash( $_POST['fstu_website'] ?? '' ) );
	}

	private function get_request_context(): string {
		$context = sanitize_key( wp_unslash( $_POST['request_context'] ?? '' ) );

		if ( in_array( $context, [ 'view', 'edit' ], true ) ) {
			return $context;
		}

		return 'default';
	}

	private function get_safe_public_message( \Throwable $throwable, string $fallback ): string {
		$message = trim( $throwable->getMessage() );
		if ( '' !== $message && ! preg_match( '/^[a-z0-9_:-]+$/i', $message ) ) {
			return $message;
		}

		return $fallback;
	}

	private function get_safe_error_message( \Throwable $throwable ): string {
		return $this->get_safe_public_message( $throwable, __( 'Сталася помилка збереження.', 'fstu' ) );
	}

	private function current_user_can_view_list(): bool {
		return Capabilities::current_user_can_view_guidance();
	}

	private function current_user_can_view_card(): bool {
		return Capabilities::current_user_can_view_guidance_card();
	}

	private function current_user_can_manage(): bool {
		return Capabilities::current_user_can_manage_guidance();
	}

	private function current_user_can_delete(): bool {
		return Capabilities::current_user_can_delete_guidance();
	}

	private function current_user_can_protocol(): bool {
		return Capabilities::current_user_can_view_guidance_protocol();
	}

	/**
	 * @return array<string,bool>
	 */
	private function get_permissions(): array {
		return Capabilities::get_guidance_permissions();
	}

	private function get_repository(): Guidance_Repository {
		if ( ! $this->repository instanceof Guidance_Repository ) {
			$this->repository = new Guidance_Repository();
		}

		return $this->repository;
	}

	private function get_protocol_service(): Guidance_Protocol_Service {
		if ( ! $this->protocol_service instanceof Guidance_Protocol_Service ) {
			$this->protocol_service = new Guidance_Protocol_Service();
		}

		return $this->protocol_service;
	}

	private function get_service(): Guidance_Service {
		if ( ! $this->service instanceof Guidance_Service ) {
			$this->service = new Guidance_Service( $this->get_repository(), $this->get_protocol_service() );
		}

		return $this->service;
	}
}

