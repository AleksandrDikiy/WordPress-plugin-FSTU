<?php
namespace FSTU\Modules\Registry\Steering;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX-контролер модуля «Реєстр стернових ФСТУ».
	 *
	 * Реалізує read-only flow першого робочого етапу: список, картка,
	 * пошук, compact pagination, правила публічної видимості по внесках
	 * та перегляд розділу «ПРОТОКОЛ».
	 *
	 * Version:     1.9.0
 * Date_update: 2026-04-08
 *
 * @package FSTU\Modules\Registry\Steering
 */
class Steering_Ajax {

	private const DEFAULT_PER_PAGE = 10;
	private const MAX_PER_PAGE     = 50;
	private const MAX_SEARCH_LEN   = 100;

	private ?Steering_Service $service = null;

	public function init(): void {
		add_action( 'wp_ajax_nopriv_fstu_steering_get_list', [ $this, 'handle_get_list' ] );
		add_action( 'wp_ajax_fstu_steering_get_list', [ $this, 'handle_get_list' ] );
		add_action( 'wp_ajax_nopriv_fstu_steering_get_single', [ $this, 'handle_get_single' ] );
		add_action( 'wp_ajax_fstu_steering_get_single', [ $this, 'handle_get_single' ] );
		add_action( 'wp_ajax_fstu_steering_get_protocol', [ $this, 'handle_get_protocol' ] );
		add_action( 'wp_ajax_fstu_steering_get_dictionaries', [ $this, 'handle_get_dictionaries' ] );
		add_action( 'wp_ajax_fstu_steering_create', [ $this, 'handle_create' ] );
		add_action( 'wp_ajax_fstu_steering_update', [ $this, 'handle_update' ] );
		add_action( 'wp_ajax_fstu_steering_delete', [ $this, 'handle_delete' ] );
		add_action( 'wp_ajax_fstu_steering_confirm_verification', [ $this, 'handle_confirm_verification' ] );
		add_action( 'wp_ajax_fstu_steering_register', [ $this, 'handle_register' ] );
		add_action( 'wp_ajax_fstu_steering_mark_sent_post', [ $this, 'handle_mark_sent_post' ] );
		add_action( 'wp_ajax_fstu_steering_mark_received', [ $this, 'handle_mark_received' ] );
	}

	public function handle_get_list(): void {
		check_ajax_referer( Steering_List::NONCE_ACTION, 'nonce' );

		$search      = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$search      = mb_substr( $search, 0, self::MAX_SEARCH_LEN );
		$page        = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page    = min( max( 1, absint( $_POST['per_page'] ?? self::DEFAULT_PER_PAGE ) ), self::MAX_PER_PAGE );
		$dues_filter = sanitize_key( wp_unslash( $_POST['dues_filter'] ?? 'all' ) );
		$status_id   = absint( $_POST['status_id'] ?? 0 );
		$type_filter = sanitize_key( wp_unslash( $_POST['type_filter'] ?? 'all' ) );
		$offset      = ( $page - 1 ) * $per_page;

		$permissions = $this->get_permissions();
		if ( empty( $permissions['canSeeFinance'] ) ) {
			$dues_filter = 'all';
		}

		if ( empty( $permissions['canManage'] ) && empty( $permissions['canManageStatus'] ) ) {
			$status_id = 0;
		}

		$payload = $this->get_service()->get_list_payload(
			[
				'search'      => $search,
				'page'        => $page,
				'per_page'    => $per_page,
				'offset'      => $offset,
				'status_id'   => $status_id,
				'dues_filter' => $dues_filter,
				'type_filter' => $type_filter,
			],
			$permissions
		);

		wp_send_json_success(
			[
				'html'        => $this->build_rows( $payload['items'], $offset, $permissions ),
				'total'       => $payload['total'],
				'page'        => $payload['page'],
				'per_page'    => $payload['per_page'],
				'total_pages' => $payload['total_pages'],
				'footer_html' => $this->build_footer_summary( $payload['footer'] ?? [], $permissions ),
			]
		);
	}

	public function handle_get_single(): void {
		check_ajax_referer( Steering_List::NONCE_ACTION, 'nonce' );

		$steering_id = absint( $_POST['steering_id'] ?? 0 );
		if ( $steering_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		$item = $this->get_service()->get_single_payload( $steering_id, $this->get_permissions() );
		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено або він недоступний для перегляду.', 'fstu' ) ] );
		}

		wp_send_json_success( [ 'item' => $item ] );
	}

	public function handle_get_protocol(): void {
		check_ajax_referer( Steering_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_protocol() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду протоколу.', 'fstu' ) ] );
		}

		$search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$search   = mb_substr( $search, 0, self::MAX_SEARCH_LEN );
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

	public function handle_get_dictionaries(): void {
		check_ajax_referer( Steering_List::NONCE_ACTION, 'nonce' );

		$permissions = $this->get_permissions();
		if ( empty( $permissions['canSubmit'] ) && empty( $permissions['canManage'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для завантаження довідників форми.', 'fstu' ) ] );
		}

		wp_send_json_success( [ 'items' => $this->get_service()->get_dictionaries_payload( $permissions ) ] );
	}

	public function handle_create(): void {
		check_ajax_referer( Steering_List::NONCE_ACTION, 'nonce' );

		$permissions = $this->get_permissions();
		if ( empty( $permissions['canSubmit'] ) && empty( $permissions['canManage'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для подачі заявки.', 'fstu' ) ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		$data          = $this->sanitize_create_data();
		$photo         = isset( $_FILES['photo'] ) && is_array( $_FILES['photo'] ) ? $_FILES['photo'] : [];
		$error_message = $this->validate_create_data( $data, $permissions );

		if ( '' !== $error_message ) {
			wp_send_json_error( [ 'message' => $error_message ] );
		}

		$photo_error_message = $this->validate_photo_upload( $photo );
		if ( '' !== $photo_error_message ) {
			wp_send_json_error( [ 'message' => $photo_error_message ] );
		}

		try {
			$result = $this->get_service()->create_item( $data, $permissions, $photo );
			wp_send_json_success(
				[
					'message'     => __( 'Заявку успішно збережено.', 'fstu' ),
					'steering_id' => (int) ( $result['steering_id'] ?? 0 ),
				]
			);
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_create_error_message( $throwable ) ] );
		}
	}

	public function handle_update(): void {
		check_ajax_referer( Steering_List::NONCE_ACTION, 'nonce' );

		$permissions = $this->get_permissions();
		if ( empty( $permissions['canManage'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для редагування запису.', 'fstu' ) ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		$steering_id    = absint( $_POST['steering_id'] ?? 0 );
		$data           = $this->sanitize_update_data();
		$photo          = isset( $_FILES['photo'] ) && is_array( $_FILES['photo'] ) ? $_FILES['photo'] : [];
		$error_message  = $this->validate_update_data( $data, $permissions, $steering_id );

		if ( '' !== $error_message ) {
			wp_send_json_error( [ 'message' => $error_message ] );
		}

		if ( $this->has_uploaded_photo( $photo ) ) {
			$photo_error_message = $this->validate_photo_upload( $photo );
			if ( '' !== $photo_error_message ) {
				wp_send_json_error( [ 'message' => $photo_error_message ] );
			}
		}

		try {
			$result = $this->get_service()->update_item( $steering_id, $data, $permissions, $photo );
			wp_send_json_success(
				[
					'message'     => __( 'Запис успішно оновлено.', 'fstu' ),
					'steering_id' => (int) ( $result['steering_id'] ?? $steering_id ),
					'item'        => $result['item'] ?? [],
				]
			);
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_update_error_message( $throwable ) ] );
		}
	}

	public function handle_delete(): void {
		check_ajax_referer( Steering_List::NONCE_ACTION, 'nonce' );

		$permissions = $this->get_permissions();
		if ( empty( $permissions['canDelete'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для видалення запису.', 'fstu' ) ] );
		}

		$steering_id = absint( $_POST['steering_id'] ?? 0 );
		if ( $steering_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		try {
			$result = $this->get_service()->delete_item( $steering_id, $permissions );
			wp_send_json_success(
				[
					'message'     => __( 'Запис успішно видалено.', 'fstu' ),
					'deleted'     => ! empty( $result['deleted'] ),
					'steering_id' => (int) ( $result['steering_id'] ?? $steering_id ),
				]
			);
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_delete_error_message( $throwable ) ] );
		}
	}

	public function handle_confirm_verification(): void {
		check_ajax_referer( Steering_List::NONCE_ACTION, 'nonce' );

		$permissions = $this->get_permissions();
		if ( empty( $permissions['canVerify'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для підтвердження кваліфікації.', 'fstu' ) ] );
		}

		$steering_id = absint( $_POST['steering_id'] ?? 0 );
		if ( $steering_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		try {
			$result  = $this->get_service()->confirm_verification( $steering_id, $permissions );
			$message = ! empty( $result['auto_registered'] )
				? sprintf( __( 'Кваліфікацію підтверджено. Запис автоматично зареєстровано, № %d.', 'fstu' ), (int) ( $result['registration_number'] ?? 0 ) )
				: __( 'Кваліфікацію успішно підтверджено.', 'fstu' );

			wp_send_json_success(
				[
					'message'             => $message,
					'item'                => $result['item'] ?? [],
					'auto_registered'     => ! empty( $result['auto_registered'] ),
					'registration_number' => (int) ( $result['registration_number'] ?? 0 ),
					'verification_count'  => (int) ( $result['verification_count'] ?? 0 ),
				]
			);
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_verify_error_message( $throwable ) ] );
		}
	}

	public function handle_register(): void {
		$this->handle_status_action( 'register' );
	}

	public function handle_mark_sent_post(): void {
		$this->handle_status_action( 'sent_post' );
	}

	public function handle_mark_received(): void {
		$this->handle_status_action( 'received' );
	}

	/**
	 * @param array<int,array<string,mixed>> $items Список записів.
	 * @param array<string,bool>             $permissions Права поточного користувача.
	 */
	private function build_rows( array $items, int $offset, array $permissions ): string {
		$colspan = ! empty( $permissions['canSeeFinance'] ) ? 12 : 5;

		if ( empty( $items ) ) {
			return '<tr class="fstu-row"><td colspan="' . esc_attr( (string) $colspan ) . '" class="fstu-no-results">' . esc_html__( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ) . '</td></tr>';
		}

		$html = '';

		foreach ( $items as $index => $item ) {
			$steering_id    = (int) ( $item['Steering_ID'] ?? 0 );
			$number         = $offset + $index + 1;
			$fio            = trim( (string) ( $item['FIO'] ?? '' ) );
			$reg_number     = (string) ( $item['Steering_RegNumber'] ?? '' );


			$date_pay       = (string) ( $item['Steering_DatePay'] ?? '' );
			$row_class      = $this->resolve_row_class( $item );
			$is_skipper     = ( $item['Record_Type'] ?? 'steering' ) === 'skipper';
			$badge          = $is_skipper ? ' <span class="fstu-badge fstu-badge--warning">Капітан</span>' : '';
			$actions        = '<button type="button" class="fstu-steering-dropdown__item fstu-steering-view-btn" data-steering-id="' . esc_attr( (string) $steering_id ) . '">' . esc_html__( 'Перегляд', 'fstu' ) . '</button>';

			if ( ! empty( $permissions['canManage'] ) && ! $is_skipper ) {
				$actions .= '<button type="button" class="fstu-steering-dropdown__item fstu-steering-edit-btn" data-steering-id="' . esc_attr( (string) $steering_id ) . '">' . esc_html__( 'Редагувати', 'fstu' ) . '</button>';
			}

			if ( ! empty( $permissions['canDelete'] ) && ! $is_skipper ) {
				$actions .= '<button type="button" class="fstu-steering-dropdown__item fstu-steering-delete-btn" data-steering-id="' . esc_attr( (string) $steering_id ) . '">' . esc_html__( 'Видалити', 'fstu' ) . '</button>';
			}

			$html .= '<tr class="' . esc_attr( $row_class ) . '">';
			$html .= '<td class="fstu-td fstu-td--num">' . esc_html( (string) $number ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--name"><button type="button" class="fstu-steering-link-button fstu-steering-view-btn" data-steering-id="' . esc_attr( (string) $steering_id ) . '">' . esc_html( '' !== $fio ? $fio : '—' ) . '</button>' . $badge . '</td>';
			
			
			$html .= '<td class="fstu-td">' . $this->format_table_value( $reg_number ) . '</td>';
			$html .= '<td class="fstu-td">' . esc_html( $this->format_date( $date_pay ) ) . '</td>';

			if ( ! empty( $permissions['canSeeFinance'] ) ) {
				$html .= '<td class="fstu-td fstu-td--center">' . esc_html( $this->format_amount( (float) ( $item['SailPrevYear'] ?? 0 ) ) ) . '</td>';
				$html .= '<td class="fstu-td fstu-td--center">' . esc_html( $this->format_amount( (float) ( $item['SailCurrentYear'] ?? 0 ) ) ) . '</td>';
				$html .= '<td class="fstu-td fstu-td--center">' . esc_html( $this->format_amount( (float) ( $item['FstuPrevYear'] ?? 0 ) ) ) . '</td>';
				$html .= '<td class="fstu-td fstu-td--center">' . esc_html( $this->format_amount( (float) ( $item['FstuCurrentYear'] ?? 0 ) ) ) . '</td>';
				$html .= '<td class="fstu-td">' . $this->format_table_value( (string) ( $item['AppStatus_Name'] ?? '' ) ) . '</td>';
				$html .= '<td class="fstu-td fstu-td--center">' . esc_html( (string) ( (int) ( $item['CntVerification'] ?? 0 ) ) ) . '</td>';
				$html .= '<td class="fstu-td fstu-td--center">' . esc_html( $this->format_amount( (float) ( $item['Steering_Summa'] ?? 0 ) ) ) . '</td>';
			}

			$html .= '<td class="fstu-td fstu-td--actions"><div class="fstu-steering-dropdown"><button type="button" class="fstu-steering-dropdown__toggle" aria-expanded="false" title="' . esc_attr__( 'Дії', 'fstu' ) . '">▼</button><div class="fstu-steering-dropdown__menu">' . $actions . '</div></div></td>';
			$html .= '</tr>';
		}

		return $html;
	}

	/**
	 * @param array<string,float|int> $footer Підсумки footer.
	 * @param array<string,bool>      $permissions Права поточного користувача.
	 */
	private function build_footer_summary( array $footer, array $permissions ): string {
		if ( empty( $permissions['canSeeFinance'] ) ) {
			return '';
		}

		return sprintf(
			/* translators: 1: records count, 2: sail prev total, 3: sail current total, 4: fstu prev total, 5: fstu current total */
			__( 'Записів: %1$d | Вітрильні минулий рік: %2$s | Вітрильні поточний рік: %3$s | ФСТУ минулий рік: %4$s | ФСТУ поточний рік: %5$s', 'fstu' ),
			(int) ( $footer['items_count'] ?? 0 ),
			esc_html( $this->format_amount( (float) ( $footer['sail_prev_year'] ?? 0 ) ) ),
			esc_html( $this->format_amount( (float) ( $footer['sail_current_year'] ?? 0 ) ) ),
			esc_html( $this->format_amount( (float) ( $footer['fstu_prev_year'] ?? 0 ) ) ),
			esc_html( $this->format_amount( (float) ( $footer['fstu_current_year'] ?? 0 ) ) )
		);
	}

	/**
	 * @param array<string,mixed> $item Запис реєстру.
	 */
	private function resolve_row_class( array $item ): string {
		$sail_prev = (float) ( $item['SailPrevYear'] ?? 0 );
		$sail_curr = (float) ( $item['SailCurrentYear'] ?? 0 );
		$fstu_prev = (float) ( $item['FstuPrevYear'] ?? 0 );
		$fstu_curr = (float) ( $item['FstuCurrentYear'] ?? 0 );

		$has_sail = $sail_prev > 0 || $sail_curr > 0;
		$has_fstu = $fstu_prev > 0 || $fstu_curr > 0;

		if ( ! $has_sail || ! $has_fstu ) {
			return 'fstu-row fstu-row--danger';
		}

		if ( $sail_curr > 0 ) {
			return 'fstu-row fstu-row--success';
		}

		if ( $sail_prev > 0 ) {
			return 'fstu-row fstu-row--warning';
		}

		return 'fstu-row';
	}

	private function format_table_value( string $value ): string {
		$value = trim( $value );

		return '' !== $value ? esc_html( $value ) : '<span class="fstu-text-muted">—</span>';
	}

	private function format_date( string $date ): string {
		$date = trim( $date );
		if ( '' === $date ) {
			return '—';
		}

		$timestamp = strtotime( $date );

		return false !== $timestamp ? wp_date( 'd.m.Y', $timestamp ) : $date;
	}

	private function format_amount( float $amount ): string {
		if ( $amount <= 0 ) {
			return '0';
		}

		return number_format_i18n( $amount, 2 );
	}

	/**
	 * @return array<string,mixed>
	 */
	private function sanitize_create_data(): array {
		return [
			'user_id'         => absint( $_POST['user_id'] ?? 0 ),
			'type_app'        => absint( $_POST['type_app'] ?? 0 ),
			'surname_ukr'     => sanitize_text_field( wp_unslash( $_POST['surname_ukr'] ?? '' ) ),
			'name_ukr'        => sanitize_text_field( wp_unslash( $_POST['name_ukr'] ?? '' ) ),
			'patronymic_ukr'  => sanitize_text_field( wp_unslash( $_POST['patronymic_ukr'] ?? '' ) ),
			'surname_eng'     => sanitize_text_field( wp_unslash( $_POST['surname_eng'] ?? '' ) ),
			'name_eng'        => sanitize_text_field( wp_unslash( $_POST['name_eng'] ?? '' ) ),
			'birth_date'      => $this->normalize_date_input( sanitize_text_field( wp_unslash( $_POST['birth_date'] ?? '' ) ) ),
			'city_id'         => absint( $_POST['city_id'] ?? 0 ),
			'number_np'       => sanitize_text_field( wp_unslash( $_POST['number_np'] ?? '' ) ),
			'url'             => esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) ),
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	private function sanitize_update_data(): array {
		return [
			'type_app'        => absint( $_POST['type_app'] ?? 0 ),
			'surname_ukr'     => sanitize_text_field( wp_unslash( $_POST['surname_ukr'] ?? '' ) ),
			'name_ukr'        => sanitize_text_field( wp_unslash( $_POST['name_ukr'] ?? '' ) ),
			'patronymic_ukr'  => sanitize_text_field( wp_unslash( $_POST['patronymic_ukr'] ?? '' ) ),
			'surname_eng'     => sanitize_text_field( wp_unslash( $_POST['surname_eng'] ?? '' ) ),
			'name_eng'        => sanitize_text_field( wp_unslash( $_POST['name_eng'] ?? '' ) ),
			'birth_date'      => $this->normalize_date_input( sanitize_text_field( wp_unslash( $_POST['birth_date'] ?? '' ) ) ),
			'city_id'         => absint( $_POST['city_id'] ?? 0 ),
			'number_np'       => sanitize_text_field( wp_unslash( $_POST['number_np'] ?? '' ) ),
			'url'             => esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) ),
		];
	}

	/**
	 * @param array<string,mixed> $data Дані форми.
	 * @param array<string,bool>  $permissions Права поточного користувача.
	 */
	private function validate_create_data( array $data, array $permissions ): string {
		if ( ! empty( $permissions['canManage'] ) && (int) ( $data['user_id'] ?? 0 ) <= 0 ) {
			return __( 'Оберіть користувача для створення заявки.', 'fstu' );
		}

		return $this->validate_common_form_data( $data );
	}

	/**
	 * @param array<string,mixed> $data Дані форми.
	 */
	private function validate_common_form_data( array $data ): string {

		if ( (int) ( $data['type_app'] ?? 0 ) < 1 || (int) ( $data['type_app'] ?? 0 ) > 4 ) {
			return __( 'Оберіть коректну підставу для посвідчення.', 'fstu' );
		}

		if ( '' === trim( (string) ( $data['surname_ukr'] ?? '' ) ) || '' === trim( (string) ( $data['name_ukr'] ?? '' ) ) ) {
			return __( 'Заповніть українські прізвище та ім’я.', 'fstu' );
		}

		if ( (int) ( $data['city_id'] ?? 0 ) <= 0 ) {
			return __( 'Оберіть місто Нової пошти.', 'fstu' );
		}

		$birth_date = trim( (string) ( $data['birth_date'] ?? '' ) );
		if ( '' === $birth_date || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $birth_date ) ) {
			return __( 'Вкажіть коректну дату народження.', 'fstu' );
		}

		$number_np = trim( (string) ( $data['number_np'] ?? '' ) );
		if ( '' === $number_np ) {
			return __( 'Вкажіть номер відділення / реквізит Нової пошти.', 'fstu' );
		}

		$url = trim( (string) ( $data['url'] ?? '' ) );
		if ( '' !== $url && ! wp_http_validate_url( $url ) ) {
			return __( 'Вкажіть коректне посилання на підтверджуючий документ.', 'fstu' );
		}

		return '';
	}

	/**
	 * @param array<string,mixed> $data Дані форми.
	 * @param array<string,bool>  $permissions Права поточного користувача.
	 */
	private function validate_update_data( array $data, array $permissions, int $steering_id ): string {
		if ( empty( $permissions['canManage'] ) ) {
			return __( 'Недостатньо прав для редагування запису.', 'fstu' );
		}

		if ( $steering_id <= 0 ) {
			return __( 'Невірний ідентифікатор запису.', 'fstu' );
		}

		return $this->validate_common_form_data( $data );
	}

	private function get_create_error_message( \Throwable $throwable ): string {
		$marker = trim( $throwable->getMessage() );

		return match ( $marker ) {
			'submit_login_required'   => __( 'Для подачі заявки потрібно авторизуватися.', 'fstu' ),
			'user_not_found'          => __( 'Обраного користувача не знайдено.', 'fstu' ),
			'city_not_found'          => __( 'Обране місто не знайдено.', 'fstu' ),
			'duplicate_user'          => __( 'Для цього користувача заявка або посвідчення вже існує.', 'fstu' ),
			'photo_required'         => __( 'Додайте фотографію для посвідчення.', 'fstu' ),
			'photo_too_large'        => __( 'Розмір фотографії перевищує допустимий ліміт 10 МБ.', 'fstu' ),
			'photo_partial',
			'photo_upload_failed',
			'photo_not_uploaded',
			'photo_empty',
			'photo_tmp_dir_missing',
			'photo_disk_write_failed',
			'photo_extension_blocked',
			'photo_directory_unavailable',
			'photo_editor_unavailable',
			'photo_save_failed',
			'photo_invalid_user',
			'photo_invalid_type',
			'photo_invalid_image'    => __( 'Не вдалося зберегти фотографію. Перевірте формат файлу.', 'fstu' ),
			'steering_insert_failed',
			'steering_log_insert_failed' => __( 'Помилка при збереженні заявки стернового.', 'fstu' ),
			default                   => __( 'Помилка при збереженні заявки стернового.', 'fstu' ),
		};
	}

	private function get_update_error_message( \Throwable $throwable ): string {
		$marker = trim( $throwable->getMessage() );

		return match ( $marker ) {
			'update_forbidden'            => __( 'Недостатньо прав для редагування запису.', 'fstu' ),
			'update_item_not_found'       => __( 'Запис не знайдено.', 'fstu' ),
			'city_not_found'              => __( 'Обране місто не знайдено.', 'fstu' ),
			'steering_update_failed',
			'photo_backup_failed',
			'photo_restore_failed',
			'steering_log_insert_failed'  => __( 'Сталася помилка збереження.', 'fstu' ),
			'photo_partial',
			'photo_upload_failed',
			'photo_not_uploaded',
			'photo_empty',
			'photo_tmp_dir_missing',
			'photo_disk_write_failed',
			'photo_extension_blocked',
			'photo_directory_unavailable',
			'photo_editor_unavailable',
			'photo_save_failed',
			'photo_invalid_user',
			'photo_invalid_type',
			'photo_invalid_image'         => __( 'Не вдалося зберегти фотографію. Перевірте формат файлу.', 'fstu' ),
			default                       => __( 'Сталася помилка оновлення запису.', 'fstu' ),
		};
	}

	private function get_delete_error_message( \Throwable $throwable ): string {
		$marker = trim( $throwable->getMessage() );

		return match ( true ) {
			'delete_forbidden' === $marker => __( 'Недостатньо прав для видалення запису.', 'fstu' ),
			'delete_item_not_found' === $marker => __( 'Запис не знайдено.', 'fstu' ),
			'delete_failed' === $marker,
			'steering_log_insert_failed' === $marker => __( 'Сталася помилка видалення запису.', 'fstu' ),
			str_starts_with( $marker, 'delete_blocked:' ) => __( 'Видалення заблоковано: запис має пов’язані дані або вже зареєстрований.', 'fstu' ),
			default => __( 'Сталася помилка видалення запису.', 'fstu' ),
		};
	}

	private function get_verify_error_message( \Throwable $throwable ): string {
		$marker = trim( $throwable->getMessage() );

		return match ( $marker ) {
			'verify_login_required'        => __( 'Для підтвердження кваліфікації потрібно авторизуватися.', 'fstu' ),
			'verify_forbidden'             => __( 'Недостатньо прав для підтвердження кваліфікації.', 'fstu' ),
			'verify_item_not_found'        => __( 'Запис не знайдено.', 'fstu' ),
			'verify_self_forbidden'        => __( 'Не можна підтверджувати власну кваліфікацію.', 'fstu' ),
			'verify_duplicate'             => __( 'Ви вже підтверджували цей запис.', 'fstu' ),
			'verify_qualification_required' => __( 'Підтвердження доступне лише кваліфікованим особам.', 'fstu' ),
			'verify_already_registered'    => __( 'Запис уже зареєстровано. Повторне підтвердження не потрібне.', 'fstu' ),
			'steering_register_failed'     => __( 'Сталася помилка збереження.', 'fstu' ),
			'steering_log_insert_failed'   => __( 'Сталася помилка збереження.', 'fstu' ),
			default                        => __( 'Сталася помилка підтвердження кваліфікації.', 'fstu' ),
		};
	}

	private function get_status_error_message( \Throwable $throwable ): string {
		$marker = trim( $throwable->getMessage() );

		return match ( $marker ) {
			'status_forbidden'               => __( 'Недостатньо прав для виконання статусної дії.', 'fstu' ),
			'status_item_not_found'          => __( 'Запис не знайдено.', 'fstu' ),
			'status_already_registered'      => __( 'Посвідчення вже зареєстровано.', 'fstu' ),
			'status_send_requires_registered' => __( 'Спочатку потрібно зареєструвати посвідчення.', 'fstu' ),
			'status_already_sent'            => __( 'Запис уже позначено як відправлений поштою.', 'fstu' ),
			'status_received_requires_sent'  => __( 'Позначити доставку можна лише після відправки поштою.', 'fstu' ),
			'status_already_received'        => __( 'Запис уже позначено як доставлений одержувачу.', 'fstu' ),
			'status_update_failed',
			'status_action_invalid',
			'steering_register_failed',
			'steering_log_insert_failed'     => __( 'Сталася помилка збереження.', 'fstu' ),
			default                          => __( 'Сталася помилка оновлення статусу.', 'fstu' ),
		};
	}

	/**
	 * @param array<string,mixed> $photo Дані файлу з $_FILES.
	 */
	private function validate_photo_upload( array $photo ): string {
		if ( empty( $photo ) ) {
			return __( 'Додайте фотографію для посвідчення.', 'fstu' );
		}

		$error_code = isset( $photo['error'] ) ? (int) $photo['error'] : UPLOAD_ERR_NO_FILE;
		if ( UPLOAD_ERR_OK !== $error_code ) {
			return $this->get_create_error_message( new \RuntimeException( $this->map_upload_error( $error_code ) ) );
		}

		return '';
	}

	/**
	 * @param array<string,mixed> $photo Дані файлу з $_FILES.
	 */
	private function has_uploaded_photo( array $photo ): bool {
		if ( empty( $photo ) ) {
			return false;
		}

		return UPLOAD_ERR_NO_FILE !== (int) ( $photo['error'] ?? UPLOAD_ERR_NO_FILE );
	}

	private function normalize_date_input( string $value ): string {
		$value = trim( $value );

		if ( '' === $value ) {
			return '';
		}

		$timestamp = strtotime( $value );

		return false !== $timestamp ? wp_date( 'Y-m-d', $timestamp ) : $value;
	}

	private function is_honeypot_triggered(): bool {
		$honeypot = sanitize_text_field( wp_unslash( $_POST['fstu_website'] ?? '' ) );

		return '' !== $honeypot;
	}

	private function map_upload_error( int $error_code ): string {
		return match ( $error_code ) {
			UPLOAD_ERR_INI_SIZE,
			UPLOAD_ERR_FORM_SIZE => 'photo_too_large',
			UPLOAD_ERR_PARTIAL   => 'photo_partial',
			UPLOAD_ERR_NO_FILE   => 'photo_required',
			UPLOAD_ERR_NO_TMP_DIR => 'photo_tmp_dir_missing',
			UPLOAD_ERR_CANT_WRITE => 'photo_disk_write_failed',
			UPLOAD_ERR_EXTENSION => 'photo_extension_blocked',
			default              => 'photo_upload_failed',
		};
	}

	private function handle_status_action( string $action ): void {
		check_ajax_referer( Steering_List::NONCE_ACTION, 'nonce' );

		$permissions = $this->get_permissions();
		if ( empty( $permissions['canManageStatus'] ) && empty( $permissions['canManage'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для виконання статусної дії.', 'fstu' ) ] );
		}

		$steering_id = absint( $_POST['steering_id'] ?? 0 );
		if ( $steering_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		try {
			switch ( $action ) {
				case 'register':
					$result  = $this->get_service()->register_item( $steering_id, $permissions );
					$message = sprintf( __( 'Посвідчення успішно зареєстровано, № %d.', 'fstu' ), (int) ( $result['registration_number'] ?? 0 ) );
					break;

				case 'sent_post':
					$result  = $this->get_service()->mark_sent_post( $steering_id, $permissions );
					$message = __( 'Запис позначено як відправлений поштою.', 'fstu' );
					break;

				case 'received':
					$result  = $this->get_service()->mark_received( $steering_id, $permissions );
					$message = __( 'Запис позначено як доставлений одержувачу.', 'fstu' );
					break;

				default:
					wp_send_json_error( [ 'message' => __( 'Невідома статусна дія.', 'fstu' ) ] );
			}

			wp_send_json_success(
				[
					'message'             => $message,
					'item'                => $result['item'] ?? [],
					'action'              => (string) ( $result['action'] ?? $action ),
					'registration_number' => (int) ( $result['registration_number'] ?? 0 ),
				]
			);
		} catch ( \Throwable $throwable ) {
			wp_send_json_error( [ 'message' => $this->get_status_error_message( $throwable ) ] );
		}
	}

	/**
	 * @return array<string,bool>
	 */
	private function get_permissions(): array {
		return Capabilities::get_steering_permissions();
	}

	private function current_user_can_protocol(): bool {
		$permissions = $this->get_permissions();

		return ! empty( $permissions['canProtocol'] );
	}

	private function get_protocol_service(): Steering_Protocol_Service {
		return $this->get_service()->get_protocol_service();
	}

	private function get_service(): Steering_Service {
		if ( ! $this->service instanceof Steering_Service ) {
			$this->service = new Steering_Service( new Steering_Repository(), new Steering_Protocol_Service(), new Steering_Upload_Service() );
		}

		return $this->service;
	}
}

