<?php
namespace FSTU\Modules\Registry\Recorders;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX-обробники модуля «Реєстратори».
 *
 * Version:     1.0.1
 * Date_update: 2026-04-11
 *
 * @package FSTU\Modules\UserFstu\Recorders
 */
class Recorders_Ajax {

	private const DEFAULT_PER_PAGE   = 10;
	private const MAX_PER_PAGE       = 50;
	private const MAX_SEARCH_LENGTH  = 100;
	private const MAX_CANDIDATES     = 20;

	private ?Recorders_Repository $repository = null;
	private ?Recorders_Protocol_Service $protocol_service = null;
	private ?Recorders_Service $service = null;

	public function init(): void {
		add_action( 'wp_ajax_fstu_recorders_get_list', [ $this, 'handle_get_list' ] );
		add_action( 'wp_ajax_fstu_recorders_get_single', [ $this, 'handle_get_single' ] );
		add_action( 'wp_ajax_fstu_recorders_get_units', [ $this, 'handle_get_units' ] );
		add_action( 'wp_ajax_fstu_recorders_get_candidates', [ $this, 'handle_get_candidates' ] );
		add_action( 'wp_ajax_fstu_recorders_create', [ $this, 'handle_create' ] );
		add_action( 'wp_ajax_fstu_recorders_update', [ $this, 'handle_update' ] );
		add_action( 'wp_ajax_fstu_recorders_delete', [ $this, 'handle_delete' ] );
		add_action( 'wp_ajax_fstu_recorders_get_protocol', [ $this, 'handle_get_protocol' ] );
	}

	public function handle_get_list(): void {
		check_ajax_referer( Recorders_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду реєстру реєстраторів.', 'fstu' ) ] );
		}

		$search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$search   = mb_substr( $search, 0, self::MAX_SEARCH_LENGTH );
		$page     = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page = min( max( 1, absint( $_POST['per_page'] ?? self::DEFAULT_PER_PAGE ) ), self::MAX_PER_PAGE );
		$unit_id  = absint( $_POST['unit_id'] ?? 0 );
		$offset   = ( $page - 1 ) * $per_page;

		$payload = $this->get_service()->get_list_payload(
			[
				'search'   => $search,
				'page'     => $page,
				'per_page' => $per_page,
				'offset'   => $offset,
				'unit_id'  => $unit_id,
			]
		);

		wp_send_json_success(
			[
				'html'        => $this->build_rows( $payload['items'], $offset, $this->get_permissions() ),
				'total'       => $payload['total'],
				'page'        => $payload['page'],
				'per_page'    => $payload['per_page'],
				'total_pages' => $payload['total_pages'],
			]
		);
	}

	public function handle_get_single(): void {
		check_ajax_referer( Recorders_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду запису.', 'fstu' ) ] );
		}

		$user_region_id = absint( $_POST['user_region_id'] ?? 0 );
		if ( $user_region_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		$item            = $this->get_service()->get_single_payload( $user_region_id );
		$request_context = $this->sanitize_request_context();
		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		if ( 'view' === $request_context ) {
			$this->get_protocol_service()->log_view_action(
				sprintf(
					'Переглянуто призначення реєстратора для осередку «%s»: %s',
					(string) ( $item['Unit_ShortName'] ?? '—' ),
					(string) ( $item['FIO'] ?? '—' )
				)
			);
		}

		wp_send_json_success( [ 'item' => $item ] );
	}

	public function handle_get_units(): void {
		check_ajax_referer( Recorders_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для завантаження осередків.', 'fstu' ) ] );
		}

		wp_send_json_success( [ 'items' => $this->get_service()->get_units_payload() ] );
	}

	public function handle_get_candidates(): void {
		check_ajax_referer( Recorders_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для вибору реєстратора.', 'fstu' ) ] );
		}

		$search = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$search = mb_substr( $search, 0, self::MAX_SEARCH_LENGTH );

		wp_send_json_success( [ 'items' => $this->get_service()->search_candidates_payload( $search, self::MAX_CANDIDATES ) ] );
	}

	public function handle_get_protocol(): void {
		check_ajax_referer( Recorders_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_protocol() ) {
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
		check_ajax_referer( Recorders_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для додавання запису.', 'fstu' ) ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		try {
			$result = $this->get_service()->create_relation( $this->sanitize_relation_data() );
			wp_send_json_success(
				[
					'message'         => __( 'Запис успішно збережено.', 'fstu' ),
					'user_region_id'  => (int) ( $result['relation_id'] ?? 0 ),
				]
			);
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_safe_error_message( $throwable ) ] );
		}
	}

	public function handle_update(): void {
		check_ajax_referer( Recorders_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для редагування запису.', 'fstu' ) ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		try {
			$result = $this->get_service()->update_relation( $this->sanitize_relation_data() );
			wp_send_json_success(
				[
					'message'         => __( 'Запис успішно оновлено.', 'fstu' ),
					'user_region_id'  => (int) ( $result['relation_id'] ?? 0 ),
				]
			);
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_safe_error_message( $throwable ) ] );
		}
	}

	public function handle_delete(): void {
		check_ajax_referer( Recorders_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_delete() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для видалення запису.', 'fstu' ) ] );
		}

		$user_region_id = absint( $_POST['user_region_id'] ?? 0 );
		if ( $user_region_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		try {
			$this->get_service()->delete_relation( $user_region_id );
			wp_send_json_success( [ 'message' => __( 'Запис успішно видалено.', 'fstu' ) ] );
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_safe_error_message( $throwable ) ] );
		}
	}

	/**
	 * @param array<string,bool> $permissions
	 */
	private function build_rows( array $items, int $offset, array $permissions ): string {
		if ( empty( $items ) ) {
			return '<tr class="fstu-row"><td colspan="6" class="fstu-no-results">' . esc_html__( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ) . '</td></tr>';
		}

		$can_manage = ! empty( $permissions['canManage'] );
		$can_delete = ! empty( $permissions['canDelete'] );
		$html       = '';
		$number     = $offset;

		foreach ( $items as $item ) {
			$number++;
			$user_region_id = (int) ( $item['UserRegion_ID'] ?? 0 );
			$user_id        = (int) ( $item['User_ID'] ?? 0 );
			$profile_url    = home_url( '/Personal' ) . '?ViewID=' . $user_id;

			$html .= '<tr class="fstu-row">';
			$html .= '<td class="fstu-td fstu-td--num">' . esc_html( (string) $number ) . '</td>';
			$html .= '<td class="fstu-td">' . esc_html( (string) ( $item['Region_Name'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td">' . esc_html( (string) ( $item['Unit_ShortName'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--name"><a class="fstu-recorders-link" href="' . esc_url( $profile_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( (string) ( $item['FIO'] ?? '' ) ) . '</a></td>';
			$html .= '<td class="fstu-td fstu-td--email">' . esc_html( (string) ( $item['user_email'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--actions">' . $this->build_actions_dropdown( $user_region_id, $can_manage, $can_delete ) . '</td>';
			$html .= '</tr>';
		}

		return $html;
	}

	private function build_actions_dropdown( int $user_region_id, bool $can_manage, bool $can_delete ): string {
		$html  = '<div class="fstu-recorders-dropdown" data-dropdown>';
		$html .= '<button type="button" class="fstu-dropdown-toggle fstu-recorders-dropdown__toggle" aria-expanded="false" title="' . esc_attr__( 'Дії', 'fstu' ) . '" aria-label="' . esc_attr__( 'Дії', 'fstu' ) . '">▼</button>';
		$html .= '<div class="fstu-recorders-dropdown__menu" data-dropdown-menu>';
		$html .= '<button type="button" class="fstu-recorders-dropdown__item fstu-recorders-view-btn" data-user-region-id="' . esc_attr( (string) $user_region_id ) . '">' . esc_html__( 'Перегляд', 'fstu' ) . '</button>';

		if ( $can_manage ) {
			$html .= '<button type="button" class="fstu-recorders-dropdown__item fstu-recorders-edit-btn" data-user-region-id="' . esc_attr( (string) $user_region_id ) . '">' . esc_html__( 'Редагувати', 'fstu' ) . '</button>';
		}

		if ( $can_delete ) {
			$html .= '<button type="button" class="fstu-recorders-dropdown__item fstu-recorders-dropdown__item--danger fstu-recorders-delete-btn" data-user-region-id="' . esc_attr( (string) $user_region_id ) . '">' . esc_html__( 'Видалити', 'fstu' ) . '</button>';
		}

		$html .= '</div></div>';

		return $html;
	}

	/**
	 * @return array<string,mixed>
	 */
	private function sanitize_relation_data(): array {
		return [
			'user_region_id' => absint( $_POST['user_region_id'] ?? 0 ),
			'unit_id'        => absint( $_POST['unit_id'] ?? 0 ),
			'user_id'        => absint( $_POST['user_id'] ?? 0 ),
		];
	}

	private function is_honeypot_triggered(): bool {
		$honeypot = sanitize_text_field( wp_unslash( $_POST['fstu_website'] ?? '' ) );
		return '' !== $honeypot;
	}

	private function sanitize_request_context(): string {
		$context = sanitize_key( wp_unslash( $_POST['request_context'] ?? '' ) );

		return in_array( $context, [ 'view', 'edit' ], true ) ? $context : '';
	}

	private function get_safe_error_message( \Throwable $throwable ): string {
		$message = trim( $throwable->getMessage() );

		if ( '' === $message || str_starts_with( $message, 'recorders_' ) ) {
			return __( 'Сталася помилка збереження.', 'fstu' );
		}

		return $message;
	}

	/**
	 * @return array<string,bool>
	 */
	private function get_permissions(): array {
		return Capabilities::get_recorders_permissions();
	}

	private function current_user_can_view(): bool {
		return Capabilities::current_user_can_view_recorders();
	}

	private function current_user_can_manage(): bool {
		return Capabilities::current_user_can_manage_recorders();
	}

	private function current_user_can_delete(): bool {
		return Capabilities::current_user_can_delete_recorders();
	}

	private function current_user_can_protocol(): bool {
		return Capabilities::current_user_can_view_recorders_protocol();
	}

	private function get_repository(): Recorders_Repository {
		if ( ! $this->repository instanceof Recorders_Repository ) {
			$this->repository = new Recorders_Repository();
		}

		return $this->repository;
	}

	private function get_protocol_service(): Recorders_Protocol_Service {
		if ( ! $this->protocol_service instanceof Recorders_Protocol_Service ) {
			$this->protocol_service = new Recorders_Protocol_Service();
		}

		return $this->protocol_service;
	}

	private function get_service(): Recorders_Service {
		if ( ! $this->service instanceof Recorders_Service ) {
			$this->service = new Recorders_Service( $this->get_repository(), $this->get_protocol_service() );
		}

		return $this->service;
	}
}

