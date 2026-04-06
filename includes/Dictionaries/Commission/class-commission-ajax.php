<?php
/**
 * AJAX-обробники модуля "Довідник комісій та колегій ФСТУ".
 *
	 * Version:     1.1.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\Commission
 */

namespace FSTU\Dictionaries\Commission;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Commission_Ajax {

	private const MAX_PER_PAGE          = 50;
	private const MAX_PROTOCOL_PER_PAGE = 50;
	private const LOG_NAME              = 'SCommission';

	/**
	 * Реєструє AJAX-обробники.
	 */
	public function init(): void {
		add_action( 'wp_ajax_fstu_commission_get_list', [ $this, 'handle_get_list' ] );

		add_action( 'wp_ajax_fstu_commission_get_single', [ $this, 'handle_get_single' ] );

		add_action( 'wp_ajax_fstu_commission_create', [ $this, 'handle_create' ] );
		add_action( 'wp_ajax_fstu_commission_update', [ $this, 'handle_update' ] );
		add_action( 'wp_ajax_fstu_commission_delete', [ $this, 'handle_delete' ] );
		add_action( 'wp_ajax_fstu_commission_reorder', [ $this, 'handle_reorder' ] );
		add_action( 'wp_ajax_fstu_commission_get_protocol', [ $this, 'handle_get_protocol' ] );
	}

	/**
	 * Повертає список записів.
	 */
	public function handle_get_list(): void {
		check_ajax_referer( Commission_List::NONCE_ACTION, 'nonce' );

		$search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$page     = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page = min( max( 1, absint( $_POST['per_page'] ?? 10 ) ), self::MAX_PER_PAGE );
		$offset   = ( $page - 1 ) * $per_page;

		global $wpdb;

		$where        = '1=1';
		$count_params = [];

		if ( '' !== $search ) {
			$like         = '%' . $wpdb->esc_like( $search ) . '%';
			$where       .= ' AND Commission_Name LIKE %s';
			$count_params = [ $like ];
		}

		$count_sql = "SELECT COUNT(*) FROM S_Commission WHERE {$where}";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$total = (int) ( $count_params ? $wpdb->get_var( $wpdb->prepare( $count_sql, ...$count_params ) ) : $wpdb->get_var( $count_sql ) );

		$data_sql = "SELECT Commission_ID, Commission_Name, Commission_EmailGoogleGroup, Commission_Number, Commission_Order
			FROM S_Commission
			WHERE {$where}
			ORDER BY Commission_Order ASC, Commission_Name ASC
			LIMIT %d OFFSET %d";

		$data_params = array_merge( $count_params, [ $per_page, $offset ] );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$items = $wpdb->get_results( $wpdb->prepare( $data_sql, ...$data_params ), ARRAY_A );

		$permissions = $this->get_permissions();

		wp_send_json_success(
			[
				'html'        => $this->build_rows( is_array( $items ) ? $items : [], $offset, $permissions ),
				'total'       => $total,
				'page'        => $page,
				'per_page'    => $per_page,
				'total_pages' => max( 1, (int) ceil( $total / max( 1, $per_page ) ) ),
			]
		);
	}

	/**
	 * Повертає один запис.
	 */
	public function handle_get_single(): void {
		check_ajax_referer( Commission_List::NONCE_ACTION, 'nonce' );

		$commission_id = absint( $_POST['commission_id'] ?? 0 );
		$context       = sanitize_key( wp_unslash( $_POST['context'] ?? '' ) );

		if ( $commission_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		$item = $this->get_commission_by_id( $commission_id );

		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		if ( 'view' === $context ) {
			$this->log_action( 'VIEW', sprintf( 'Перегляд запису комісії / колегії: %s', (string) $item['Commission_Name'] ), '✓' );
		}

		wp_send_json_success(
			[
				'commission_id'                => (int) $item['Commission_ID'],
				'commission_name'              => (string) $item['Commission_Name'],
				'commission_emailgooglegroup'  => (string) ( $item['Commission_EmailGoogleGroup'] ?? '' ),
				'commission_number'            => (string) ( $item['Commission_Number'] ?? '' ),
				'commission_order'             => (int) ( $item['Commission_Order'] ?? 0 ),
			]
		);
	}

	/**
	 * Створює запис.
	 */
	public function handle_create(): void {
		check_ajax_referer( Commission_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для додавання запису.', 'fstu' ) ] );
		}

		if ( ! $this->validate_honeypot() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		$data = $this->sanitize_form_data();
		$error_message = $this->validate_form_data( $data );

		if ( '' !== $error_message ) {
			wp_send_json_error( [ 'message' => $error_message ] );
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$max_order = (int) $wpdb->get_var( 'SELECT COALESCE(MAX(Commission_Order), 0) FROM S_Commission' );

		$insert_data = [
			'Commission_Name'             => $data['commission_name'],
			'Commission_EmailGoogleGroup' => $data['commission_emailgooglegroup'],
			'Commission_Number'           => $data['commission_number'],
			'Commission_Order'            => $max_order + 1,
		];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->insert(
			'S_Commission',
			$insert_data,
			[ '%s', '%s', '%s', '%d' ]
		);

		if ( false === $result ) {
			$this->log_action( 'INSERT', __( 'Помилка додавання запису.', 'fstu' ), $wpdb->last_error ?: 'DB error' );
			wp_send_json_error( [ 'message' => __( 'Помилка при збереженні запису.', 'fstu' ) ] );
		}

		$this->log_action( 'INSERT', sprintf( 'Додано комісію / колегію: %s', $data['commission_name'] ), '✓' );

		wp_send_json_success( [ 'message' => __( 'Запис успішно додано.', 'fstu' ) ] );
	}

	/**
	 * Оновлює запис.
	 */
	public function handle_update(): void {
		check_ajax_referer( Commission_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для редагування запису.', 'fstu' ) ] );
		}

		if ( ! $this->validate_honeypot() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		$commission_id = absint( $_POST['commission_id'] ?? 0 );
		$data          = $this->sanitize_form_data();
		$error_message = $this->validate_form_data( $data, $commission_id );

		if ( $commission_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		if ( '' !== $error_message ) {
			wp_send_json_error( [ 'message' => $error_message ] );
		}

		if ( ! is_array( $this->get_commission_by_id( $commission_id ) ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		global $wpdb;

		$update_data = [
			'Commission_Name'             => $data['commission_name'],
			'Commission_EmailGoogleGroup' => $data['commission_emailgooglegroup'],
			'Commission_Number'           => $data['commission_number'],
		];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->update(
			'S_Commission',
			$update_data,
			[ 'Commission_ID' => $commission_id ],
			[ '%s', '%s', '%s' ],
			[ '%d' ]
		);

		if ( false === $result ) {
			$this->log_action( 'UPDATE', __( 'Помилка оновлення запису.', 'fstu' ), $wpdb->last_error ?: 'DB error' );
			wp_send_json_error( [ 'message' => __( 'Помилка при збереженні запису.', 'fstu' ) ] );
		}

		$this->log_action( 'UPDATE', sprintf( 'Оновлено комісію / колегію: %s', $data['commission_name'] ), '✓' );

		wp_send_json_success( [ 'message' => __( 'Запис успішно оновлено.', 'fstu' ) ] );
	}

	/**
	 * Видаляє запис.
	 */
	public function handle_delete(): void {
		check_ajax_referer( Commission_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_delete() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для видалення запису.', 'fstu' ) ] );
		}

		$commission_id = absint( $_POST['commission_id'] ?? 0 );

		if ( $commission_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$item = $wpdb->get_row(
			$wpdb->prepare( 'SELECT Commission_Name FROM S_Commission WHERE Commission_ID = %d LIMIT 1', $commission_id ),
			ARRAY_A
		);

		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->delete( 'S_Commission', [ 'Commission_ID' => $commission_id ], [ '%d' ] );

		if ( false === $result ) {
			$this->log_action( 'DELETE', sprintf( 'Помилка видалення запису: %s', (string) $item['Commission_Name'] ), $wpdb->last_error ?: 'DB error' );
			wp_send_json_error( [ 'message' => __( 'Не вдалося видалити запис. Перевірте пов’язані дані.', 'fstu' ) ] );
		}

		$this->log_action( 'DELETE', sprintf( 'Видалено комісію / колегію: %s', (string) $item['Commission_Name'] ), '✓' );

		wp_send_json_success( [ 'message' => __( 'Запис успішно видалено.', 'fstu' ) ] );
	}

	/**
	 * Оновлює порядок записів після drag-and-drop.
	 */
	public function handle_reorder(): void {
		check_ajax_referer( Commission_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для зміни сортування.', 'fstu' ) ] );
		}

		$items = wp_unslash( $_POST['items'] ?? '' );
		$items = is_string( $items ) ? json_decode( $items, true ) : $items;

		if ( ! is_array( $items ) || empty( $items ) ) {
			wp_send_json_error( [ 'message' => __( 'Немає даних для сортування.', 'fstu' ) ] );
		}

		global $wpdb;
		$prepared_items = [];
		$seen_ids       = [];

		foreach ( $items as $item ) {
			$commission_id = absint( $item['id'] ?? 0 );
			$order         = absint( $item['order'] ?? 0 );

			if ( $commission_id <= 0 || $order <= 0 || isset( $seen_ids[ $commission_id ] ) ) {
				continue;
			}

			$seen_ids[ $commission_id ] = true;
			$prepared_items[]           = [
				'id'    => $commission_id,
				'order' => $order,
			];
		}

		if ( empty( $prepared_items ) ) {
			wp_send_json_error( [ 'message' => __( 'Передано некоректні дані сортування.', 'fstu' ) ] );
		}

		$placeholders = implode( ', ', array_fill( 0, count( $prepared_items ), '%d' ) );
		$ids          = array_column( $prepared_items, 'id' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$existing_ids = $wpdb->get_col( $wpdb->prepare( "SELECT Commission_ID FROM S_Commission WHERE Commission_ID IN ({$placeholders})", ...$ids ) );

		if ( count( $existing_ids ) !== count( $prepared_items ) ) {
			$this->log_action( 'REORDER', __( 'Помилка сортування: частина записів не знайдена.', 'fstu' ), 'Invalid items' );
			wp_send_json_error( [ 'message' => __( 'Не вдалося оновити порядок. Частина записів не знайдена.', 'fstu' ) ] );
		}

		foreach ( $prepared_items as $item ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$result = $wpdb->update(
				'S_Commission',
				[ 'Commission_Order' => $item['order'] ],
				[ 'Commission_ID' => $item['id'] ],
				[ '%d' ],
				[ '%d' ]
			);

			if ( false === $result ) {
				$this->log_action( 'REORDER', __( 'Помилка оновлення порядку комісій / колегій.', 'fstu' ), $wpdb->last_error ?: 'DB error' );
				wp_send_json_error( [ 'message' => __( 'Не вдалося оновити порядок записів.', 'fstu' ) ] );
			}
		}

		$this->log_action( 'REORDER', __( 'Оновлено порядок комісій / колегій.', 'fstu' ), '✓' );

		wp_send_json_success( [ 'message' => __( 'Порядок записів успішно оновлено.', 'fstu' ) ] );
	}

	/**
	 * Повертає протокол модуля.
	 */
	public function handle_get_protocol(): void {
		check_ajax_referer( Commission_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_protocol() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду протоколу.', 'fstu' ) ] );
		}

		$search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$page     = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page = min( max( 1, absint( $_POST['per_page'] ?? 10 ) ), self::MAX_PROTOCOL_PER_PAGE );
		$offset   = ( $page - 1 ) * $per_page;

		global $wpdb;

		$where  = 'WHERE l.Logs_Name = %s';
		$params = [ self::LOG_NAME ];

		if ( '' !== $search ) {
			$like    = '%' . $wpdb->esc_like( $search ) . '%';
			$where  .= ' AND (l.Logs_Text LIKE %s OR u.FIO LIKE %s)';
			$params[] = $like;
			$params[] = $like;
		}

		$count_sql = "SELECT COUNT(*)
			FROM Logs l
			LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
			{$where}";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );

		$data_sql = "SELECT l.Logs_DateCreate, l.Logs_Type, l.Logs_Name, l.Logs_Text, l.Logs_Error, u.FIO
			FROM Logs l
			LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
			{$where}
			ORDER BY l.Logs_DateCreate DESC
			LIMIT %d OFFSET %d";

		$data_params   = array_merge( $params, [ $per_page, $offset ] );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$protocol_rows = $wpdb->get_results( $wpdb->prepare( $data_sql, ...$data_params ), ARRAY_A );

		wp_send_json_success(
			[
				'html'        => $this->build_protocol_rows( is_array( $protocol_rows ) ? $protocol_rows : [] ),
				'total'       => $total,
				'page'        => $page,
				'per_page'    => $per_page,
				'total_pages' => max( 1, (int) ceil( $total / max( 1, $per_page ) ) ),
			]
		);
	}

	/**
	 * Будує HTML рядків таблиці.
	 *
	 * @param array<int,array<string,mixed>> $items
	 * @param int                            $offset
	 * @param array<string,bool>             $permissions
	 * @return string
	 */
	private function build_rows( array $items, int $offset, array $permissions ): string {
		if ( empty( $items ) ) {
			return '<tr class="fstu-row"><td colspan="5" class="fstu-no-results">' . esc_html__( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ) . '</td></tr>';
		}

		$html = '';
		$index = $offset;

		foreach ( $items as $item ) {
			++$index;

			$commission_id   = (int) ( $item['Commission_ID'] ?? 0 );
			$commission_name = (string) ( $item['Commission_Name'] ?? '' );
			$google_group    = (string) ( $item['Commission_EmailGoogleGroup'] ?? '' );
			$page_number     = (string) ( $item['Commission_Number'] ?? '' );
			$drag_enabled    = ! empty( $permissions['canManage'] );

			$actions = [];
			$actions[] = '<button type="button" class="fstu-commission-dropdown__item fstu-commission-view-btn" data-commission-id="' . esc_attr( (string) $commission_id ) . '">' . esc_html__( 'Перегляд', 'fstu' ) . '</button>';

			if ( ! empty( $permissions['canManage'] ) ) {
				$actions[] = '<button type="button" class="fstu-commission-dropdown__item fstu-commission-edit-btn" data-commission-id="' . esc_attr( (string) $commission_id ) . '">' . esc_html__( 'Редагування', 'fstu' ) . '</button>';
			}

			if ( ! empty( $permissions['canDelete'] ) ) {
				$actions[] = '<button type="button" class="fstu-commission-dropdown__item fstu-commission-dropdown__item--danger fstu-commission-delete-btn" data-commission-id="' . esc_attr( (string) $commission_id ) . '">' . esc_html__( 'Видалення', 'fstu' ) . '</button>';
			}

			$drag_handle = $drag_enabled
				? '<span class="fstu-drag-handle" title="' . esc_attr__( 'Перетягніть для зміни порядку', 'fstu' ) . '">⋮⋮</span>'
				: '<span class="fstu-drag-handle fstu-drag-handle--disabled" aria-hidden="true">⋮⋮</span>';

			$html .= '<tr class="fstu-row fstu-commission-row" data-commission-id="' . esc_attr( (string) $commission_id ) . '"' . ( $drag_enabled ? ' draggable="true"' : '' ) . '>';
			$html .= '<td class="fstu-td fstu-td--num">' . $drag_handle . '<span class="fstu-row-number">' . esc_html( (string) $index ) . '</span></td>';
			$html .= '<td class="fstu-td fstu-td--name"><button type="button" class="fstu-commission-link-button fstu-commission-view-btn" data-commission-id="' . esc_attr( (string) $commission_id ) . '">' . esc_html( $commission_name ) . '</button></td>';
			$html .= '<td class="fstu-td fstu-td--email">' . ( '' !== $google_group ? esc_html( $google_group ) : '<span class="fstu-text-muted">—</span>' ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--number">' . ( '' !== $page_number ? esc_html( $page_number ) : '<span class="fstu-text-muted">—</span>' ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--actions">';
			$html .= '<div class="fstu-commission-dropdown">';
			$html .= '<button type="button" class="fstu-commission-dropdown__toggle" aria-expanded="false" title="' . esc_attr__( 'Меню дій', 'fstu' ) . '">▼</button>';
			$html .= '<div class="fstu-commission-dropdown__menu">' . implode( '', $actions ) . '</div>';
			$html .= '</div>';
			$html .= '</td>';
			$html .= '</tr>';
		}

		return $html;
	}

	/**
	 * Будує HTML рядків протоколу.
	 *
	 * @param array<int,array<string,mixed>> $items
	 * @return string
	 */
	private function build_protocol_rows( array $items ): string {
		if ( empty( $items ) ) {
			return '<tr class="fstu-row"><td colspan="6" class="fstu-no-results">' . esc_html__( 'Записи протоколу відсутні.', 'fstu' ) . '</td></tr>';
		}

		$html = '';

		foreach ( $items as $item ) {
			$html .= '<tr class="fstu-row">';
			$html .= '<td class="fstu-td fstu-td--date">' . esc_html( (string) ( $item['Logs_DateCreate'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--type">' . esc_html( (string) ( $item['Logs_Type'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--operation">' . esc_html( (string) ( $item['Logs_Name'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--message">' . esc_html( (string) ( $item['Logs_Text'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--status">' . esc_html( (string) ( $item['Logs_Error'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--user">' . esc_html( (string) ( $item['FIO'] ?? '' ) ) . '</td>';
			$html .= '</tr>';
		}

		return $html;
	}

	/**
	 * Повертає очищені дані форми.
	 *
	 * @return array<string,string>
	 */
	private function sanitize_form_data(): array {
		return [
			'commission_name'             => sanitize_text_field( wp_unslash( $_POST['commission_name'] ?? '' ) ),
			'commission_emailgooglegroup' => sanitize_email( wp_unslash( $_POST['commission_emailgooglegroup'] ?? '' ) ),
			'commission_number'           => sanitize_text_field( wp_unslash( $_POST['commission_number'] ?? '' ) ),
		];
	}

	/**
	 * Валідує форму.
	 *
	 * @param array<string,string> $data          Дані форми.
	 * @param int                  $commission_id Поточний ID запису під час редагування.
	 * @return string
	 */
	private function validate_form_data( array $data, int $commission_id = 0 ): string {
		if ( mb_strlen( $data['commission_name'] ) < 2 ) {
			return __( 'Поле «Найменування» є обов’язковим.', 'fstu' );
		}

		if ( mb_strlen( $data['commission_name'] ) > 255 ) {
			return __( 'Поле «Найменування» не може бути довшим за 255 символів.', 'fstu' );
		}

		$email = $data['commission_emailgooglegroup'];
		if ( '' !== $email && ! is_email( $email ) ) {
			return __( 'Вкажіть коректну адресу Google Group.', 'fstu' );
		}

		if ( mb_strlen( $data['commission_number'] ) > 50 ) {
			return __( 'Поле «№ статті/сторінки» не може бути довшим за 50 символів.', 'fstu' );
		}

		if ( '' !== $data['commission_number'] && ! preg_match( '/^[\p{L}\p{N}\s\/\-\.,()№#]+$/u', $data['commission_number'] ) ) {
			return __( 'Поле «№ статті/сторінки» містить недопустимі символи.', 'fstu' );
		}

		if ( $this->commission_name_exists( $data['commission_name'], $commission_id ) ) {
			return __( 'Запис з таким найменуванням уже існує.', 'fstu' );
		}

		return '';
	}

	/**
	 * Перевіряє honeypot.
	 */
	private function validate_honeypot(): bool {
		$honeypot = sanitize_text_field( wp_unslash( $_POST['fstu_website'] ?? '' ) );
		return '' === $honeypot;
	}

	/**
	 * Повертає права для поточного користувача.
	 *
	 * @return array<string,bool>
	 */
	private function get_permissions(): array {
		return Capabilities::get_commission_permissions();
	}

	/**
	 * Чи може поточний користувач керувати довідником.
	 */
	private function current_user_can_manage(): bool {
		return Capabilities::current_user_can_manage_commission();
	}

	/**
	 * Чи може поточний користувач видаляти записи.
	 */
	private function current_user_can_delete(): bool {
		return Capabilities::current_user_can_delete_commission();
	}

	/**
	 * Чи може поточний користувач переглядати протокол.
	 */
	private function current_user_can_protocol(): bool {
		return Capabilities::current_user_can_view_commission_protocol();
	}

	/**
	 * Повертає запис довідника за ID.
	 *
	 * @param int $commission_id Ідентифікатор запису.
	 * @return array<string,mixed>|null
	 */
	private function get_commission_by_id( int $commission_id ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$item = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT Commission_ID, Commission_Name, Commission_EmailGoogleGroup, Commission_Number, Commission_Order
				 FROM S_Commission
				 WHERE Commission_ID = %d
				 LIMIT 1",
				$commission_id
			),
			ARRAY_A
		);

		return is_array( $item ) ? $item : null;
	}

	/**
	 * Перевіряє, чи існує комісія з таким найменуванням.
	 *
	 * @param string $commission_name Назва комісії.
	 * @param int    $exclude_id      ID запису, який треба виключити з перевірки.
	 */
	private function commission_name_exists( string $commission_name, int $exclude_id = 0 ): bool {
		global $wpdb;

		$sql    = 'SELECT Commission_ID FROM S_Commission WHERE Commission_Name = %s';
		$params = [ $commission_name ];

		if ( $exclude_id > 0 ) {
			$sql     .= ' AND Commission_ID != %d';
			$params[] = $exclude_id;
		}

		$sql .= ' LIMIT 1';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$existing_id = $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );

		return null !== $existing_id;
	}

	/**
	 * Записує подію у таблицю Logs.
	 */
	private function log_action( string $type, string $text, string $status ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			'Logs',
			[
				'User_ID'         => get_current_user_id(),
				'Logs_DateCreate' => current_time( 'mysql' ),
				'Logs_Type'       => $type,
				'Logs_Name'       => self::LOG_NAME,
				'Logs_Text'       => $text,
				'Logs_Error'      => $status,
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s' ]
		);
	}
}

