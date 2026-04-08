<?php
namespace FSTU\Modules\Registry\Referees;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX-обробники модуля «Реєстр суддів ФСТУ».
 *
 * Version:     1.0.1
 * Date_update: 2026-04-08
 *
 * @package FSTU\Modules\Registry\Referees
 */
class Referees_Ajax {

	private const DEFAULT_PER_PAGE = 10;
	private const MAX_PER_PAGE = 50;
	private const MAX_SEARCH_LENGTH = 100;

	private ?Referees_Repository $repository = null;
	private ?Referees_Protocol_Service $protocol_service = null;
	private ?Referees_Service $service = null;

	public function init(): void {
		add_action( 'wp_ajax_fstu_referees_get_list', [ $this, 'handle_get_list' ] );
		add_action( 'wp_ajax_fstu_referees_get_single', [ $this, 'handle_get_single' ] );
		add_action( 'wp_ajax_fstu_referees_create', [ $this, 'handle_create' ] );
		add_action( 'wp_ajax_fstu_referees_update', [ $this, 'handle_update' ] );
		add_action( 'wp_ajax_fstu_referees_delete', [ $this, 'handle_delete' ] );
		add_action( 'wp_ajax_fstu_referees_get_dictionaries', [ $this, 'handle_get_dictionaries' ] );
		add_action( 'wp_ajax_fstu_referees_get_protocol', [ $this, 'handle_get_protocol' ] );
		add_action( 'wp_ajax_fstu_referees_get_certificates', [ $this, 'handle_get_certificates' ] );
		add_action( 'wp_ajax_fstu_referees_create_certificate', [ $this, 'handle_create_certificate' ] );
		add_action( 'wp_ajax_fstu_referees_bind_certificate_category', [ $this, 'handle_bind_certificate_category' ] );
		add_action( 'wp_ajax_fstu_referees_unbind_certificate_category', [ $this, 'handle_unbind_certificate_category' ] );
	}

	public function handle_get_list(): void {
		check_ajax_referer( Referees_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду реєстру суддів.', 'fstu' ) ] );
		}

		$search              = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$search              = mb_substr( $search, 0, self::MAX_SEARCH_LENGTH );
		$page                = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page            = min( max( 1, absint( $_POST['per_page'] ?? self::DEFAULT_PER_PAGE ) ), self::MAX_PER_PAGE );
		$region_id           = absint( $_POST['region_id'] ?? 0 );
		$referee_category_id = absint( $_POST['referee_category_id'] ?? 0 );
		$offset              = ( $page - 1 ) * $per_page;

		$payload = $this->get_service()->get_list_payload(
			[
				'search'              => $search,
				'page'                => $page,
				'per_page'            => $per_page,
				'offset'              => $offset,
				'region_id'           => $region_id,
				'referee_category_id' => $referee_category_id,
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
		check_ajax_referer( Referees_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду картки судді.', 'fstu' ) ] );
		}

		$referee_id = absint( $_POST['referee_id'] ?? 0 );
		if ( $referee_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		$item = $this->get_service()->get_single_payload( $referee_id );
		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		wp_send_json_success( [ 'item' => $item ] );
	}

	public function handle_get_dictionaries(): void {
		check_ajax_referer( Referees_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для завантаження довідників.', 'fstu' ) ] );
		}

		wp_send_json_success( [ 'items' => $this->get_service()->get_dictionaries_payload() ] );
	}

	public function handle_get_protocol(): void {
		check_ajax_referer( Referees_List::NONCE_ACTION, 'nonce' );

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
		check_ajax_referer( Referees_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для додавання судді.', 'fstu' ) ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		$data          = $this->sanitize_referee_data();
		$error_message = $this->validate_referee_data( $data, false );

		if ( '' !== $error_message ) {
			wp_send_json_error( [ 'message' => $error_message ] );
		}

		try {
			$result = $this->get_service()->create_referee( $data );
			wp_send_json_success(
				[
					'message'    => __( 'Запис судді успішно збережено.', 'fstu' ),
					'referee_id' => (int) ( $result['referee_id'] ?? 0 ),
				]
			);
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_referee_error_message( $throwable, true ) ] );
		}
	}

	public function handle_update(): void {
		check_ajax_referer( Referees_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для редагування судді.', 'fstu' ) ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		$data          = $this->sanitize_referee_data();
		$error_message = $this->validate_referee_data( $data, true );

		if ( '' !== $error_message ) {
			wp_send_json_error( [ 'message' => $error_message ] );
		}

		try {
			$result = $this->get_service()->update_referee( $data );
			wp_send_json_success(
				[
					'message'    => __( 'Запис судді успішно оновлено.', 'fstu' ),
					'referee_id' => (int) ( $result['referee_id'] ?? 0 ),
				]
			);
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_referee_error_message( $throwable, false ) ] );
		}
	}

	public function handle_delete(): void {
		check_ajax_referer( Referees_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_delete() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для видалення судді.', 'fstu' ) ] );
		}

		$referee_id = absint( $_POST['referee_id'] ?? 0 );
		if ( $referee_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		try {
			$this->get_service()->delete_referee( $referee_id );
			wp_send_json_success( [ 'message' => __( 'Запис судді успішно видалено.', 'fstu' ) ] );
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_delete_error_message( $throwable ) ] );
		}
	}

	public function handle_get_certificates(): void {
		check_ajax_referer( Referees_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду довідок.', 'fstu' ) ] );
		}

		$user_id = absint( $_POST['user_id'] ?? 0 );
		if ( $user_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний користувач для довідок.', 'fstu' ) ] );
		}

		wp_send_json_success( [ 'items' => $this->get_service()->get_certificates_payload( $user_id ) ] );
	}

	public function handle_create_certificate(): void {
		check_ajax_referer( Referees_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage_certificates() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для додавання довідки.', 'fstu' ) ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		$data          = $this->sanitize_certificate_data();
		$error_message = $this->validate_certificate_data( $data );

		if ( '' !== $error_message ) {
			wp_send_json_error( [ 'message' => $error_message ] );
		}

		try {
			$result = $this->get_service()->create_certificate( $data );
			wp_send_json_success(
				[
					'message'        => __( 'Довідку успішно додано.', 'fstu' ),
					'certificate_id' => (int) ( $result['certificate_id'] ?? 0 ),
				]
			);
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_certificate_error_message( $throwable ) ] );
		}
	}

	public function handle_bind_certificate_category(): void {
		check_ajax_referer( Referees_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage_certificates() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для оновлення категорії довідки.', 'fstu' ) ] );
		}

		$certificate_id = absint( $_POST['certificate_id'] ?? 0 );
		$category_id    = absint( $_POST['referee_category_id'] ?? 0 );

		if ( $certificate_id <= 0 || $category_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Оберіть довідку та категорію.', 'fstu' ) ] );
		}

		try {
			$this->get_service()->bind_certificate_category( $certificate_id, $category_id );
			wp_send_json_success( [ 'message' => __( 'Категорію довідки успішно оновлено.', 'fstu' ) ] );
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_certificate_action_error_message( $throwable, 'bind' ) ] );
		}
	}

	public function handle_unbind_certificate_category(): void {
		check_ajax_referer( Referees_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_unbind_certificates() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для зняття прив’язки категорії.', 'fstu' ) ] );
		}

		$certificate_id = absint( $_POST['certificate_id'] ?? 0 );
		if ( $certificate_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор довідки.', 'fstu' ) ] );
		}

		try {
			$this->get_service()->unbind_certificate_category( $certificate_id );
			wp_send_json_success( [ 'message' => __( 'Прив’язку категорії успішно знято.', 'fstu' ) ] );
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_certificate_action_error_message( $throwable, 'unbind' ) ] );
		}
	}

	/**
	 * @param array<int,array<string,mixed>> $items Список суддів.
	 * @param array<string,bool>             $permissions Права поточного користувача.
	 */
	private function build_rows( array $items, int $offset, array $permissions ): string {
		if ( empty( $items ) ) {
			return '<tr class="fstu-row"><td colspan="7" class="fstu-no-results">' . esc_html__( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ) . '</td></tr>';
		}

		$html = '';

		foreach ( $items as $index => $item ) {
			$referee_id         = (int) ( $item['Referee_ID'] ?? 0 );
			$user_id            = (int) ( $item['User_ID'] ?? 0 );
			$number             = $offset + $index + 1;
			$fio                = trim( (string) ( $item['FIO'] ?? '' ) );
			$category_name      = (string) ( $item['RefereeCategory_Name'] ?? '' );
			$region_name        = (string) ( $item['Region_Name'] ?? '' );
			$certificates_count = (int) ( $item['CntCertificates'] ?? 0 );
			$order_number       = (string) ( $item['Referee_NumOrder'] ?? '' );
			$order_date         = (string) ( $item['Referee_DateOrder'] ?? '' );
			$actions            = [];

			$actions[] = '<button type="button" class="fstu-referees-dropdown__item fstu-referees-view-btn" data-referee-id="' . esc_attr( (string) $referee_id ) . '">' . esc_html__( 'Перегляд', 'fstu' ) . '</button>';
			$actions[] = '<button type="button" class="fstu-referees-dropdown__item fstu-referees-certificates-btn" data-referee-id="' . esc_attr( (string) $referee_id ) . '" data-user-id="' . esc_attr( (string) $user_id ) . '" data-referee-fio="' . esc_attr( $fio ) . '">' . esc_html__( 'Довідки', 'fstu' ) . '</button>';

			if ( ! empty( $permissions['canManage'] ) ) {
				$actions[] = '<button type="button" class="fstu-referees-dropdown__item fstu-referees-edit-btn" data-referee-id="' . esc_attr( (string) $referee_id ) . '">' . esc_html__( 'Редагування', 'fstu' ) . '</button>';
			}

			if ( ! empty( $permissions['canDelete'] ) ) {
				$actions[] = '<button type="button" class="fstu-referees-dropdown__item fstu-referees-dropdown__item--danger fstu-referees-delete-btn" data-referee-id="' . esc_attr( (string) $referee_id ) . '">' . esc_html__( 'Видалення', 'fstu' ) . '</button>';
			}

			$html .= '<tr class="fstu-row">';
			$html .= '<td class="fstu-td fstu-td--num">' . esc_html( (string) $number ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--name"><button type="button" class="fstu-referees-link-button fstu-referees-view-btn" data-referee-id="' . esc_attr( (string) $referee_id ) . '">' . esc_html( '' !== $fio ? $fio : '—' ) . '</button></td>';
			$html .= '<td class="fstu-td">' . $this->format_table_value( $category_name ) . '</td>';
			$html .= '<td class="fstu-td">' . $this->format_order_value( $order_number, $order_date, (string) ( $item['Referee_URLOrder'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--certificates"><button type="button" class="fstu-referees-link-button fstu-referees-certificates-btn" data-referee-id="' . esc_attr( (string) $referee_id ) . '" data-user-id="' . esc_attr( (string) $user_id ) . '" data-referee-fio="' . esc_attr( $fio ) . '">' . esc_html( (string) $certificates_count ) . '</button></td>';
			$html .= '<td class="fstu-td">' . $this->format_table_value( $region_name ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--actions"><div class="fstu-referees-dropdown"><button type="button" class="fstu-referees-dropdown__toggle" aria-expanded="false" title="' . esc_attr__( 'Дії', 'fstu' ) . '">▼</button><div class="fstu-referees-dropdown__menu">' . implode( '', $actions ) . '</div></div></td>';
			$html .= '</tr>';
		}

		return $html;
	}

	private function format_table_value( string $value ): string {
		$text = trim( $value );

		return '' !== $text ? esc_html( $text ) : '<span class="fstu-text-muted">—</span>';
	}

	private function format_order_value( string $number, string $date, string $url = '' ): string {
		$number = trim( $number );
		$date   = trim( $date );
		$url    = trim( $url );

		if ( '' === $number && '' === $date ) {
			return '<span class="fstu-text-muted">—</span>';
		}

		$text = $number;
		if ( '' !== $date ) {
			$timestamp = strtotime( $date );
			$formatted_date = false !== $timestamp ? wp_date( 'd.m.Y', $timestamp ) : $date;
			$text .= ( '' !== $text ? ' / ' : '' ) . $formatted_date;
		}

		if ( '' !== $url && wp_http_validate_url( $url ) ) {
			return '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $text ) . '</a>';
		}

		return esc_html( $text );
	}

	/**
	 * @return array<string,mixed>
	 */
	private function sanitize_referee_data(): array {
		return [
			'referee_id'           => absint( $_POST['referee_id'] ?? 0 ),
			'user_id'              => absint( $_POST['user_id'] ?? 0 ),
			'referee_category_id'  => absint( $_POST['referee_category_id'] ?? 0 ),
			'num_order'            => sanitize_text_field( wp_unslash( $_POST['num_order'] ?? '' ) ),
			'date_order'           => $this->normalize_date_input( sanitize_text_field( wp_unslash( $_POST['date_order'] ?? '' ) ) ),
			'url_order'            => esc_url_raw( wp_unslash( $_POST['url_order'] ?? '' ) ),
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	private function sanitize_certificate_data(): array {
		return [
			'user_id'         => absint( $_POST['user_id'] ?? 0 ),
			'calendar_id'     => absint( $_POST['calendar_id'] ?? 0 ),
			'certificate_url' => esc_url_raw( wp_unslash( $_POST['certificate_url'] ?? '' ) ),
		];
	}

	private function validate_referee_data( array $data, bool $is_update ): string {
		if ( $is_update && (int) ( $data['referee_id'] ?? 0 ) <= 0 ) {
			return __( 'Невірний ідентифікатор запису.', 'fstu' );
		}

		if ( ! $is_update && (int) ( $data['user_id'] ?? 0 ) <= 0 ) {
			return __( 'Оберіть користувача для створення судді.', 'fstu' );
		}

		if ( (int) ( $data['referee_category_id'] ?? 0 ) <= 0 ) {
			return __( 'Оберіть суддівську категорію.', 'fstu' );
		}

		$num_order = trim( (string) ( $data['num_order'] ?? '' ) );
		if ( mb_strlen( $num_order ) > 120 ) {
			return __( 'Номер наказу не може бути довшим за 120 символів.', 'fstu' );
		}

		if ( '' !== $num_order && ! preg_match( '/^[\p{L}\p{N}\s\/\-.,()№#]+$/u', $num_order ) ) {
			return __( 'Номер наказу містить недопустимі символи.', 'fstu' );
		}

		$date_order = trim( (string) ( $data['date_order'] ?? '' ) );
		if ( '' !== $date_order && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_order ) ) {
			return __( 'Вкажіть коректну дату наказу.', 'fstu' );
		}

		$url_order = trim( (string) ( $data['url_order'] ?? '' ) );
		if ( mb_strlen( $url_order ) > 300 ) {
			return __( 'Посилання на наказ не може бути довшим за 300 символів.', 'fstu' );
		}

		if ( '' !== $url_order && ! wp_http_validate_url( $url_order ) ) {
			return __( 'Вкажіть коректне посилання на наказ.', 'fstu' );
		}

		return '';
	}

	private function validate_certificate_data( array $data ): string {
		if ( (int) ( $data['user_id'] ?? 0 ) <= 0 ) {
			return __( 'Невірний користувач для довідки.', 'fstu' );
		}

		if ( (int) ( $data['calendar_id'] ?? 0 ) <= 0 ) {
			return __( 'Оберіть захід із календаря.', 'fstu' );
		}

		$certificate_url = trim( (string) ( $data['certificate_url'] ?? '' ) );
		if ( mb_strlen( $certificate_url ) > 300 ) {
			return __( 'Посилання на довідку не може бути довшим за 300 символів.', 'fstu' );
		}

		if ( '' === $certificate_url || ! wp_http_validate_url( $certificate_url ) ) {
			return __( 'Вкажіть коректне посилання на довідку.', 'fstu' );
		}

		return '';
	}

	private function get_referee_error_message( \Throwable $throwable, bool $is_create ): string {
		$marker = trim( $throwable->getMessage() );

		return match ( $marker ) {
			'user_not_found'         => __( 'Обраного користувача не знайдено.', 'fstu' ),
			'category_not_found'     => __( 'Обрану суддівську категорію не знайдено.', 'fstu' ),
			'duplicate_referee_user' => __( 'Для цього користувача запис судді вже існує.', 'fstu' ),
			'referee_not_found'      => __( 'Запис судді не знайдено.', 'fstu' ),
			'referee_insert_failed',
			'referee_update_failed',
			'referees_log_insert_failed' => $is_create
				? __( 'Помилка при збереженні запису судді.', 'fstu' )
				: __( 'Помилка при оновленні запису судді.', 'fstu' ),
			default => $is_create
				? __( 'Помилка при збереженні запису судді.', 'fstu' )
				: __( 'Помилка при оновленні запису судді.', 'fstu' ),
		};
	}

	private function get_delete_error_message( \Throwable $throwable ): string {
		$marker = trim( $throwable->getMessage() );

		if ( str_starts_with( $marker, 'delete_blocked:' ) ) {
			return (string) mb_substr( $marker, mb_strlen( 'delete_blocked:' ) );
		}

		return match ( $marker ) {
			'referee_not_found'       => __( 'Запис судді не знайдено.', 'fstu' ),
			'referee_delete_failed',
			'referees_log_insert_failed' => __( 'Не вдалося видалити запис судді.', 'fstu' ),
			default                   => __( 'Не вдалося видалити запис судді.', 'fstu' ),
		};
	}

	private function get_certificate_error_message( \Throwable $throwable ): string {
		$marker = trim( $throwable->getMessage() );

		return match ( $marker ) {
			'referee_not_found'         => __( 'Суддю для довідки не знайдено.', 'fstu' ),
			'calendar_not_found'        => __( 'Обраний захід календаря не знайдено.', 'fstu' ),
			'duplicate_certificate'     => __( 'Для цього судді вже існує довідка по вибраному заходу.', 'fstu' ),
			'certificate_insert_failed',
			'referees_log_insert_failed' => __( 'Помилка при збереженні довідки.', 'fstu' ),
			default                     => __( 'Помилка при збереженні довідки.', 'fstu' ),
		};
	}

	private function get_certificate_action_error_message( \Throwable $throwable, string $action ): string {
		$marker = trim( $throwable->getMessage() );

		return match ( $marker ) {
			'certificate_not_found'      => __( 'Довідку не знайдено.', 'fstu' ),
			'category_not_found'         => __( 'Обрану суддівську категорію не знайдено.', 'fstu' ),
			'certificate_bind_failed',
			'certificate_unbind_failed',
			'referees_log_insert_failed' => 'bind' === $action
				? __( 'Не вдалося оновити категорію довідки.', 'fstu' )
				: __( 'Не вдалося зняти прив’язку категорії.', 'fstu' ),
			default                      => 'bind' === $action
				? __( 'Не вдалося оновити категорію довідки.', 'fstu' )
				: __( 'Не вдалося зняти прив’язку категорії.', 'fstu' ),
		};
	}

	private function normalize_date_input( string $value ): string {
		$value = trim( $value );

		if ( '' === $value ) {
			return '';
		}

		$timestamp = strtotime( $value );
		if ( false === $timestamp ) {
			return '';
		}

		return wp_date( 'Y-m-d', $timestamp );
	}

	private function is_honeypot_triggered(): bool {
		$honeypot = sanitize_text_field( wp_unslash( $_POST['fstu_website'] ?? '' ) );

		return '' !== trim( $honeypot );
	}

	private function get_repository(): Referees_Repository {
		if ( null === $this->repository ) {
			$this->repository = new Referees_Repository();
		}

		return $this->repository;
	}

	private function get_protocol_service(): Referees_Protocol_Service {
		if ( null === $this->protocol_service ) {
			$this->protocol_service = new Referees_Protocol_Service();
		}

		return $this->protocol_service;
	}

	private function get_service(): Referees_Service {
		if ( null === $this->service ) {
			$this->service = new Referees_Service(
				$this->get_repository(),
				$this->get_protocol_service()
			);
		}

		return $this->service;
	}

	private function current_user_can_view(): bool {
		return Capabilities::current_user_can_view_referees();
	}

	private function current_user_can_manage(): bool {
		return Capabilities::current_user_can_manage_referees();
	}

	private function current_user_can_delete(): bool {
		return Capabilities::current_user_can_delete_referees();
	}

	private function current_user_can_protocol(): bool {
		return Capabilities::current_user_can_view_referees_protocol();
	}

	private function current_user_can_manage_certificates(): bool {
		return Capabilities::current_user_can_manage_referee_certificates();
	}

	private function current_user_can_unbind_certificates(): bool {
		return Capabilities::current_user_can_unbind_referee_certificates();
	}

	/**
	 * @return array<string,bool>
	 */
	private function get_permissions(): array {
		return Capabilities::get_referees_permissions();
	}
}

