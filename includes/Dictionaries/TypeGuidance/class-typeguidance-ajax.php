<?php
/**
 * AJAX-обробники модуля "Довідник керівних органів ФСТУ".
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\TypeGuidance
 */

namespace FSTU\Dictionaries\TypeGuidance;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TypeGuidance_Ajax {

	private const MAX_PER_PAGE          = 50;
	private const MAX_PROTOCOL_PER_PAGE = 50;
	private const LOG_NAME              = 'TypeGuidance';

	/**
	 * Реєструє AJAX-обробники.
	 */
	public function init(): void {
		add_action( 'wp_ajax_fstu_typeguidance_get_list', [ $this, 'handle_get_list' ] );
		add_action( 'wp_ajax_fstu_typeguidance_get_single', [ $this, 'handle_get_single' ] );
		add_action( 'wp_ajax_fstu_typeguidance_create', [ $this, 'handle_create' ] );
		add_action( 'wp_ajax_fstu_typeguidance_update', [ $this, 'handle_update' ] );
		add_action( 'wp_ajax_fstu_typeguidance_delete', [ $this, 'handle_delete' ] );
		add_action( 'wp_ajax_fstu_typeguidance_get_protocol', [ $this, 'handle_get_protocol' ] );
	}

	/**
	 * Повертає список записів.
	 */
	public function handle_get_list(): void {
		check_ajax_referer( TypeGuidance_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду довідника.', 'fstu' ) ] );
		}

		$search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$page     = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page = min( max( 1, absint( $_POST['per_page'] ?? 10 ) ), self::MAX_PER_PAGE );
		$offset   = ( $page - 1 ) * $per_page;

		global $wpdb;

		$where  = '1=1';
		$params = [];

		if ( '' !== $search ) {
			$like    = '%' . $wpdb->esc_like( $search ) . '%';
			$where  .= ' AND TypeGuidance_Name LIKE %s';
			$params[] = $like;
		}

		$count_sql = "SELECT COUNT(*) FROM vTypeGuidance WHERE {$where}";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$total = (int) ( $params ? $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) ) : $wpdb->get_var( $count_sql ) );

		$list_sql = "SELECT TypeGuidance_ID, TypeGuidance_Name, TypeGuidance_Number, TypeGuidance_Order
			FROM vTypeGuidance
			WHERE {$where}
			ORDER BY TypeGuidance_Order ASC, TypeGuidance_Name ASC
			LIMIT %d OFFSET %d";

		$list_params = array_merge( $params, [ $per_page, $offset ] );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$items = $wpdb->get_results( $wpdb->prepare( $list_sql, ...$list_params ), ARRAY_A );

		wp_send_json_success(
			[
				'html'        => $this->build_rows( is_array( $items ) ? $items : [], $offset, $this->get_permissions() ),
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
		check_ajax_referer( TypeGuidance_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду запису.', 'fstu' ) ] );
		}

		$typeguidance_id = absint( $_POST['typeguidance_id'] ?? 0 );
		if ( $typeguidance_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		$item = $this->get_typeguidance_by_id( $typeguidance_id );
		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		wp_send_json_success(
			[
				'item' => [
					'typeguidance_id'     => (int) $item['TypeGuidance_ID'],
					'typeguidance_name'   => (string) $item['TypeGuidance_Name'],
					'typeguidance_number' => (string) ( $item['TypeGuidance_Number'] ?? '' ),
					'typeguidance_order'  => (int) ( $item['TypeGuidance_Order'] ?? 0 ),
				],
			]
		);
	}

	/**
	 * Створює запис.
	 */
	public function handle_create(): void {
		check_ajax_referer( TypeGuidance_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для додавання запису.', 'fstu' ) ] );
		}

		if ( ! $this->validate_honeypot() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		$data          = $this->sanitize_form_data();
		$error_message = $this->validate_form_data( $data );

		if ( '' !== $error_message ) {
			wp_send_json_error( [ 'message' => $error_message ] );
		}

		global $wpdb;

		$order = $data['typeguidance_order'];
		if ( null === $order ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$order = (int) $wpdb->get_var( 'SELECT COALESCE(MAX(TypeGuidance_Order), 0) FROM S_TypeGuidance' ) + 1;
		}

		$inserted = $wpdb->insert(
			'S_TypeGuidance',
			[
				'TypeGuidance_DateCreate' => current_time( 'mysql' ),
				'TypeGuidance_Name'       => $data['typeguidance_name'],
				'TypeGuidance_Number'     => $data['typeguidance_number'],
				'TypeGuidance_Order'      => $order,
			],
			[ '%s', '%s', '%s', '%d' ]
		);

		if ( false === $inserted ) {
			$this->log_action( 'INSERT', __( 'Помилка додавання керівного органу.', 'fstu' ), $wpdb->last_error ?: 'DB error' );
			wp_send_json_error( [ 'message' => __( 'Помилка при збереженні запису.', 'fstu' ) ] );
		}

		$this->log_action( 'INSERT', sprintf( 'Додано керівний орган: %s', $data['typeguidance_name'] ), '✓' );

		wp_send_json_success( [ 'message' => __( 'Запис успішно додано.', 'fstu' ) ] );
	}

	/**
	 * Оновлює запис.
	 */
	public function handle_update(): void {
		check_ajax_referer( TypeGuidance_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для редагування запису.', 'fstu' ) ] );
		}

		if ( ! $this->validate_honeypot() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		$typeguidance_id = absint( $_POST['typeguidance_id'] ?? 0 );
		$data            = $this->sanitize_form_data();
		$error_message   = $this->validate_form_data( $data, $typeguidance_id );

		if ( $typeguidance_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		if ( '' !== $error_message ) {
			wp_send_json_error( [ 'message' => $error_message ] );
		}

		$item = $this->get_typeguidance_by_id( $typeguidance_id );
		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		global $wpdb;

		$order = null === $data['typeguidance_order']
			? (int) ( $item['TypeGuidance_Order'] ?? 0 )
			: $data['typeguidance_order'];

		$updated = $wpdb->update(
			'S_TypeGuidance',
			[
				'TypeGuidance_Name'   => $data['typeguidance_name'],
				'TypeGuidance_Number' => $data['typeguidance_number'],
				'TypeGuidance_Order'  => $order,
			],
			[ 'TypeGuidance_ID' => $typeguidance_id ],
			[ '%s', '%s', '%d' ],
			[ '%d' ]
		);

		if ( false === $updated ) {
			$this->log_action( 'UPDATE', sprintf( 'Помилка оновлення керівного органу: %s', (string) $item['TypeGuidance_Name'] ), $wpdb->last_error ?: 'DB error' );
			wp_send_json_error( [ 'message' => __( 'Помилка при збереженні запису.', 'fstu' ) ] );
		}

		$this->log_action( 'UPDATE', sprintf( 'Оновлено керівний орган: %s', $data['typeguidance_name'] ), '✓' );

		wp_send_json_success( [ 'message' => __( 'Запис успішно оновлено.', 'fstu' ) ] );
	}

	/**
	 * Видаляє запис.
	 */
	public function handle_delete(): void {
		check_ajax_referer( TypeGuidance_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_delete() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для видалення запису.', 'fstu' ) ] );
		}

		$typeguidance_id = absint( $_POST['typeguidance_id'] ?? 0 );
		if ( $typeguidance_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		$item = $this->get_typeguidance_by_id( $typeguidance_id );
		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		global $wpdb;
		$deleted = $wpdb->delete( 'S_TypeGuidance', [ 'TypeGuidance_ID' => $typeguidance_id ], [ '%d' ] );

		if ( false === $deleted ) {
			$this->log_action( 'DELETE', sprintf( 'Помилка видалення керівного органу: %s', (string) $item['TypeGuidance_Name'] ), $wpdb->last_error ?: 'DB error' );
			wp_send_json_error( [ 'message' => __( 'Не вдалося видалити запис.', 'fstu' ) ] );
		}

		$this->log_action( 'DELETE', sprintf( 'Видалено керівний орган: %s', (string) $item['TypeGuidance_Name'] ), '✓' );

		wp_send_json_success( [ 'message' => __( 'Запис успішно видалено.', 'fstu' ) ] );
	}

	/**
	 * Повертає протокол модуля.
	 */
	public function handle_get_protocol(): void {
		check_ajax_referer( TypeGuidance_List::NONCE_ACTION, 'nonce' );

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
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where   .= ' AND (l.Logs_Text LIKE %s OR u.FIO LIKE %s)';
			$params[] = $like;
			$params[] = $like;
		}

		$count_sql = "SELECT COUNT(*)
			FROM Logs l
			LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
			{$where}";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );

		$list_sql = "SELECT l.Logs_DateCreate, l.Logs_Type, l.Logs_Name, l.Logs_Text, l.Logs_Error, u.FIO
			FROM Logs l
			LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
			{$where}
			ORDER BY l.Logs_DateCreate DESC
			LIMIT %d OFFSET %d";

		$list_params = array_merge( $params, [ $per_page, $offset ] );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $list_sql, ...$list_params ), ARRAY_A );

		wp_send_json_success(
			[
				'html'        => $this->build_protocol_rows( is_array( $rows ) ? $rows : [] ),
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
	 */
	private function build_rows( array $items, int $offset, array $permissions ): string {
		if ( empty( $items ) ) {
			return '<tr class="fstu-row"><td colspan="4" class="fstu-no-results">' . esc_html__( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ) . '</td></tr>';
		}

		$html  = '';
		$index = $offset;

		foreach ( $items as $item ) {
			++$index;

			$typeguidance_id     = (int) ( $item['TypeGuidance_ID'] ?? 0 );
			$typeguidance_name   = (string) ( $item['TypeGuidance_Name'] ?? '' );
			$typeguidance_number = (string) ( $item['TypeGuidance_Number'] ?? '' );

			$actions   = [];
			$actions[] = '<button type="button" class="fstu-typeguidance-dropdown__item fstu-typeguidance-view-btn" data-typeguidance-id="' . esc_attr( (string) $typeguidance_id ) . '">' . esc_html__( 'Перегляд', 'fstu' ) . '</button>';

			if ( ! empty( $permissions['canManage'] ) ) {
				$actions[] = '<button type="button" class="fstu-typeguidance-dropdown__item fstu-typeguidance-edit-btn" data-typeguidance-id="' . esc_attr( (string) $typeguidance_id ) . '">' . esc_html__( 'Редагування', 'fstu' ) . '</button>';
			}

			if ( ! empty( $permissions['canDelete'] ) ) {
				$actions[] = '<button type="button" class="fstu-typeguidance-dropdown__item fstu-typeguidance-dropdown__item--danger fstu-typeguidance-delete-btn" data-typeguidance-id="' . esc_attr( (string) $typeguidance_id ) . '">' . esc_html__( 'Видалення', 'fstu' ) . '</button>';
			}

			$html .= '<tr class="fstu-row">';
			$html .= '<td class="fstu-td fstu-td--num">' . esc_html( (string) $index ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--name"><button type="button" class="fstu-typeguidance-link-button fstu-typeguidance-view-btn" data-typeguidance-id="' . esc_attr( (string) $typeguidance_id ) . '">' . esc_html( $typeguidance_name ) . '</button></td>';
			$html .= '<td class="fstu-td fstu-td--number">' . ( '' !== $typeguidance_number ? esc_html( $typeguidance_number ) : '<span class="fstu-text-muted">—</span>' ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--actions">';
			$html .= '<div class="fstu-typeguidance-dropdown">';
			$html .= '<button type="button" class="fstu-typeguidance-dropdown__toggle" aria-expanded="false" title="' . esc_attr__( 'Меню дій', 'fstu' ) . '">▼</button>';
			$html .= '<div class="fstu-typeguidance-dropdown__menu">' . implode( '', $actions ) . '</div>';
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
	 * @return array<string,mixed>
	 */
	private function sanitize_form_data(): array {
		$order_raw = sanitize_text_field( wp_unslash( $_POST['typeguidance_order'] ?? '' ) );

		return [
			'typeguidance_name'      => sanitize_text_field( wp_unslash( $_POST['typeguidance_name'] ?? '' ) ),
			'typeguidance_number'    => sanitize_text_field( wp_unslash( $_POST['typeguidance_number'] ?? '' ) ),
			'typeguidance_order_raw' => $order_raw,
			'typeguidance_order'     => '' === $order_raw ? null : absint( $order_raw ),
		];
	}

	/**
	 * Валідує форму.
	 *
	 * @param array<string,mixed> $data Дані форми.
	 */
	private function validate_form_data( array $data, int $typeguidance_id = 0 ): string {
		$name = (string) ( $data['typeguidance_name'] ?? '' );
		$number = (string) ( $data['typeguidance_number'] ?? '' );
		$order_raw = (string) ( $data['typeguidance_order_raw'] ?? '' );

		if ( mb_strlen( $name ) < 2 ) {
			return __( 'Поле «Найменування» є обов’язковим.', 'fstu' );
		}

		if ( mb_strlen( $name ) > 255 ) {
			return __( 'Поле «Найменування» не може бути довшим за 255 символів.', 'fstu' );
		}

		if ( mb_strlen( $number ) > 50 ) {
			return __( 'Поле «№ статті/сторінки» не може бути довшим за 50 символів.', 'fstu' );
		}

		if ( '' !== $number && ! preg_match( '/^[\p{L}\p{N}\s\/\-.,()№#]+$/u', $number ) ) {
			return __( 'Поле «№ статті/сторінки» містить недопустимі символи.', 'fstu' );
		}

		if ( '' !== $order_raw && ! preg_match( '/^\d+$/', $order_raw ) ) {
			return __( 'Поле «Сортування» повинно містити лише невід’ємне число.', 'fstu' );
		}

		if ( $this->typeguidance_name_exists( $name, $typeguidance_id ) ) {
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
		return Capabilities::get_typeguidance_permissions();
	}

	/**
	 * Чи може користувач переглядати модуль.
	 */
	private function current_user_can_view(): bool {
		return Capabilities::current_user_can_view_typeguidance();
	}

	/**
	 * Чи може користувач керувати модулем.
	 */
	private function current_user_can_manage(): bool {
		return Capabilities::current_user_can_manage_typeguidance();
	}

	/**
	 * Чи може користувач видаляти записи.
	 */
	private function current_user_can_delete(): bool {
		return Capabilities::current_user_can_delete_typeguidance();
	}

	/**
	 * Чи може користувач переглядати протокол.
	 */
	private function current_user_can_protocol(): bool {
		return Capabilities::current_user_can_view_typeguidance_protocol();
	}

	/**
	 * Повертає запис довідника за ID.
	 *
	 * @return array<string,mixed>|null
	 */
	private function get_typeguidance_by_id( int $typeguidance_id ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$item = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT TypeGuidance_ID, TypeGuidance_Name, TypeGuidance_Number, TypeGuidance_Order
				 FROM S_TypeGuidance
				 WHERE TypeGuidance_ID = %d
				 LIMIT 1",
				$typeguidance_id
			),
			ARRAY_A
		);

		return is_array( $item ) ? $item : null;
	}

	/**
	 * Перевіряє, чи існує запис з таким найменуванням.
	 */
	private function typeguidance_name_exists( string $typeguidance_name, int $exclude_id = 0 ): bool {
		global $wpdb;

		$sql    = 'SELECT TypeGuidance_ID FROM S_TypeGuidance WHERE TypeGuidance_Name = %s';
		$params = [ $typeguidance_name ];

		if ( $exclude_id > 0 ) {
			$sql     .= ' AND TypeGuidance_ID != %d';
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

