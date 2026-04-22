<?php
namespace FSTU\Modules\Registry\MKK;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX-обробники модуля «Реєстр членів МКК ФСТУ».
 *
 * Version:     1.1.4
 * Date_update: 2026-04-12
 *
 * @package FSTU\Modules\UserFstu\MKK
 */
class MKK_Ajax {

	private const DEFAULT_PER_PAGE  = 10;
	private const MAX_PER_PAGE      = 50;
	private const MAX_SEARCH_LENGTH = 100;
	private const MAX_USERS         = 20;

	private ?MKK_Repository $repository = null;
	private ?MKK_Service $service = null;
	private ?MKK_Protocol_Service $protocol_service = null;

	public function init(): void {
		add_action( 'wp_ajax_fstu_mkk_get_list', [ $this, 'handle_get_list' ] );
		add_action( 'wp_ajax_nopriv_fstu_mkk_get_list', [ $this, 'handle_get_list' ] );
		add_action( 'wp_ajax_fstu_mkk_get_single', [ $this, 'handle_get_single' ] );
		add_action( 'wp_ajax_nopriv_fstu_mkk_get_single', [ $this, 'handle_get_single' ] );
		add_action( 'wp_ajax_fstu_mkk_get_filters', [ $this, 'handle_get_filters' ] );
		add_action( 'wp_ajax_nopriv_fstu_mkk_get_filters', [ $this, 'handle_get_filters' ] );
		add_action( 'wp_ajax_fstu_mkk_search_users', [ $this, 'handle_search_users' ] );
		add_action( 'wp_ajax_fstu_mkk_create', [ $this, 'handle_create' ] );
		add_action( 'wp_ajax_fstu_mkk_update', [ $this, 'handle_update' ] );
		add_action( 'wp_ajax_fstu_mkk_delete', [ $this, 'handle_delete' ] );
		add_action( 'wp_ajax_fstu_mkk_get_protocol', [ $this, 'handle_get_protocol' ] );
	}

	public function handle_get_list(): void {
		check_ajax_referer( MKK_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду реєстру МКК.', 'fstu' ) ] );
		}

		$search             = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$search             = mb_substr( $search, 0, self::MAX_SEARCH_LENGTH );
		$page               = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page           = min( max( 1, absint( $_POST['per_page'] ?? self::DEFAULT_PER_PAGE ) ), self::MAX_PER_PAGE );
		$region_id          = absint( $_POST['region_id'] ?? 0 );
		$commission_type_id = absint( $_POST['commission_type_id'] ?? 0 );
		$tourism_type_id    = absint( $_POST['tourism_type_id'] ?? 0 );
		$offset             = ( $page - 1 ) * $per_page;

		try {
			$payload = $this->get_service()->get_list_payload(
				[
					'search'             => $search,
					'page'               => $page,
					'per_page'           => $per_page,
					'region_id'          => $region_id,
					'commission_type_id' => $commission_type_id,
					'tourism_type_id'    => $tourism_type_id,
					'offset'             => $offset,
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
		check_ajax_referer( MKK_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду запису.', 'fstu' ) ] );
		}

		$mkk_id = absint( $_POST['mkk_id'] ?? 0 );
		if ( $mkk_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		try {
			$item = $this->get_service()->get_single_payload( $mkk_id );
			if ( ! is_array( $item ) ) {
				wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
			}

			wp_send_json_success( [ 'item' => $item ] );
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_safe_public_message( $throwable, __( 'Помилка завантаження даних.', 'fstu' ) ) ] );
		}
	}

	public function handle_get_filters(): void {
		check_ajax_referer( MKK_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для завантаження довідників.', 'fstu' ) ] );
		}

		try {
			wp_send_json_success( $this->get_service()->get_filters_payload() );
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_safe_public_message( $throwable, __( 'Не вдалося завантажити довідники.', 'fstu' ) ) ] );
		}
	}

	public function handle_search_users(): void {
		check_ajax_referer( MKK_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для пошуку користувачів.', 'fstu' ) ] );
		}

		$search = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$search = mb_substr( $search, 0, self::MAX_SEARCH_LENGTH );

		try {
			wp_send_json_success( [ 'items' => $this->get_service()->search_users_payload( $search, self::MAX_USERS ) ] );
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_safe_public_message( $throwable, __( 'Не вдалося завантажити список користувачів.', 'fstu' ) ) ] );
		}
	}

	public function handle_get_protocol(): void {
		check_ajax_referer( MKK_List::NONCE_ACTION, 'nonce' );

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
		check_ajax_referer( MKK_List::NONCE_ACTION, 'nonce' );

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
					'message' => __( 'Запис успішно збережено.', 'fstu' ),
					'mkk_id'  => (int) ( $result['mkk_id'] ?? 0 ),
				]
			);
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_safe_error_message( $throwable ) ] );
		}
	}

	public function handle_update(): void {
		check_ajax_referer( MKK_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для редагування запису.', 'fstu' ) ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		try {
			$data            = $this->sanitize_item_data();
			$data['mkk_id']  = absint( $_POST['mkk_id'] ?? 0 );
			$result          = $this->get_service()->update_item( $data );
			wp_send_json_success(
				[
					'message' => __( 'Запис успішно оновлено.', 'fstu' ),
					'mkk_id'  => (int) ( $result['mkk_id'] ?? 0 ),
				]
			);
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_safe_error_message( $throwable ) ] );
		}
	}

	public function handle_delete(): void {
		check_ajax_referer( MKK_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_delete() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для видалення запису.', 'fstu' ) ] );
		}

		$mkk_id = absint( $_POST['mkk_id'] ?? 0 );
		if ( $mkk_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		try {
			$this->get_service()->delete_item( $mkk_id );
			wp_send_json_success( [ 'message' => __( 'Запис успішно видалено.', 'fstu' ) ] );
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_safe_error_message( $throwable ) ] );
		}
	}

	/**
	 * @param array<int,array<string,mixed>> $items
	 * @param array<string,bool>             $permissions
	 */
	private function build_rows( array $items, int $offset, array $permissions ): string {
		if ( empty( $items ) ) {
			return '<tr class="fstu-row"><td colspan="7" class="fstu-no-results">' . esc_html__( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ) . '</td></tr>';
		}

		$can_manage = ! empty( $permissions['canManage'] );
		$can_delete = ! empty( $permissions['canDelete'] );
		$html       = '';
		$number     = $offset;

		foreach ( $items as $item ) {
			$number++;
			$mkk_id       = (int) ( $item['mkk_ID'] ?? $item['mkk_id'] ?? 0 );
			$profile_url  = (string) ( $item['ProfileUrl'] ?? '' );
			$display_name = (string) ( $item['DisplayFIO'] ?? '' );
			$position     = (string) ( $item['MemberRegional_Name'] ?? '' );

			$html .= '<tr class="fstu-row">';
			$html .= '<td class="fstu-td fstu-td--num">' . esc_html( (string) $number ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--position">';
			if ( $mkk_id > 0 && '' !== $position ) {
				$html .= '<a href="#" class="fstu-mkk-link fstu-mkk-view-link" data-mkk-id="' . esc_attr( (string) $mkk_id ) . '">' . esc_html( $position ) . '</a>';
			} else {
				$html .= esc_html( $position );
			}
			$html .= '</td>';
			$html .= '<td class="fstu-td fstu-td--name">';
			if ( '' !== $profile_url ) {
				$html .= '<a class="fstu-mkk-link" href="' . esc_url( $profile_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $display_name ) . '</a>';
			} else {
				$html .= esc_html( $display_name );
			}
			$html .= '</td>';
			$html .= '<td class="fstu-td fstu-td--region">' . esc_html( (string) ( $item['Region_Name'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--type">' . esc_html( (string) ( $item['CommissionType_Name'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--tourism">' . esc_html( (string) ( $item['TourismType_Name'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--actions">' . $this->build_actions_dropdown( $mkk_id, $can_manage, $can_delete ) . '</td>';
			$html .= '</tr>';
		}

		return $html;
	}

	private function build_actions_dropdown( int $mkk_id, bool $can_manage, bool $can_delete ): string {
		$items = [];
		$items[] = '<button type="button" role="menuitem" class="fstu-mkk-dropdown__item fstu-mkk-view-btn" data-mkk-id="' . esc_attr( (string) $mkk_id ) . '">' . esc_html__( 'Перегляд', 'fstu' ) . '</button>';

		if ( $can_manage ) {
			$items[] = '<button type="button" role="menuitem" class="fstu-mkk-dropdown__item fstu-mkk-edit-btn" data-mkk-id="' . esc_attr( (string) $mkk_id ) . '">' . esc_html__( 'Редагувати', 'fstu' ) . '</button>';
		}

		if ( $can_delete ) {
			$items[] = '<button type="button" role="menuitem" class="fstu-mkk-dropdown__item fstu-mkk-dropdown__item--danger fstu-mkk-delete-btn" data-mkk-id="' . esc_attr( (string) $mkk_id ) . '">' . esc_html__( 'Видалити', 'fstu' ) . '</button>';
		}

		$html  = '<div class="fstu-mkk-dropdown" data-dropdown="mkk">';
		$html .= '<button type="button" class="fstu-mkk-dropdown__toggle" aria-expanded="false" aria-label="' . esc_attr__( 'Дії', 'fstu' ) . '" title="' . esc_attr__( 'Дії', 'fstu' ) . '">▼</button>';
		$html .= '<div class="fstu-mkk-dropdown__menu" role="menu" data-dropdown-menu>' . implode( '', $items ) . '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * @return array<string,mixed>
	 */
	private function sanitize_item_data(): array {
		return [
			'user_id'            => absint( $_POST['user_id'] ?? 0 ),
			'region_id'          => absint( $_POST['region_id'] ?? 0 ),
			'commission_type_id' => absint( $_POST['commission_type_id'] ?? 0 ),
			'tourism_type_id'    => absint( $_POST['tourism_type_id'] ?? 0 ),
		];
	}

	private function is_honeypot_triggered(): bool {
		$honeypot = sanitize_text_field( wp_unslash( $_POST['fstu_website'] ?? '' ) );

		return '' !== $honeypot;
	}

	private function get_safe_error_message( \Throwable $throwable ): string {
		return $this->get_safe_public_message( $throwable, __( 'Сталася помилка збереження.', 'fstu' ) );
	}

	private function get_safe_public_message( \Throwable $throwable, string $default_message ): string {
		$message = trim( $throwable->getMessage() );

		if ( '' === $message || str_starts_with( $message, 'mkk_' ) ) {
			return $default_message;
		}

		if ( in_array( $message, $this->get_allowed_error_messages(), true ) ) {
			return $message;
		}

		return $default_message;
	}

	/**
	 * @return array<int,string>
	 */
	private function get_allowed_error_messages(): array {
		return [
			__( 'Невірний ідентифікатор запису.', 'fstu' ),
			__( 'Запис не знайдено.', 'fstu' ),
			__( 'Оберіть члена ФСТУ зі списку підказок.', 'fstu' ),
			__( 'Оберіть область.', 'fstu' ),
			__( 'Оберіть тип комісії.', 'fstu' ),
			__( 'Оберіть вид туризму.', 'fstu' ),
			__( 'Користувача не знайдено у реєстрі ФСТУ.', 'fstu' ),
			__( 'Обрану область не знайдено у довіднику.', 'fstu' ),
			__( 'Обраний тип комісії не знайдено у довіднику.', 'fstu' ),
			__( 'Обраний вид туризму не знайдено у довіднику.', 'fstu' ),
			__( 'Запис МКК неможливо видалити, оскільки він використовується в інших даних.', 'fstu' ),
			__( 'Неможливо перевірити зв’язки запису. Видалення заблоковано з міркувань безпеки.', 'fstu' ),
			__( 'Запис неможливо видалити.', 'fstu' ),
		];
	}

	private function current_user_can_view(): bool {
		return Capabilities::current_user_can_view_mkk();
	}

	private function current_user_can_manage(): bool {
		return Capabilities::current_user_can_manage_mkk();
	}

	private function current_user_can_delete(): bool {
		return Capabilities::current_user_can_delete_mkk();
	}

	private function current_user_can_protocol(): bool {
		return Capabilities::current_user_can_view_mkk_protocol();
	}

	/**
	 * @return array<string,bool>
	 */
	private function get_permissions(): array {
		return Capabilities::get_mkk_permissions();
	}

	private function get_repository(): MKK_Repository {
		if ( ! $this->repository instanceof MKK_Repository ) {
			$this->repository = new MKK_Repository();
		}

		return $this->repository;
	}

	private function get_protocol_service(): MKK_Protocol_Service {
		if ( ! $this->protocol_service instanceof MKK_Protocol_Service ) {
			$this->protocol_service = new MKK_Protocol_Service();
		}

		return $this->protocol_service;
	}

	private function get_service(): MKK_Service {
		if ( ! $this->service instanceof MKK_Service ) {
			$this->service = new MKK_Service( $this->get_repository(), $this->get_protocol_service() );
		}

		return $this->service;
	}
}

