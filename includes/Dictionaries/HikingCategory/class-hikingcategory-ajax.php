<?php
/**
 * AJAX-обробники модуля "Довідник категорій походів".
 *
 * Version:     1.0.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Dictionaries\HikingCategory
 */

namespace FSTU\Dictionaries\HikingCategory;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HikingCategory_Ajax {

	private const MAX_PER_PAGE          = 50;
	private const MAX_PROTOCOL_PER_PAGE = 50;
	private const MAX_SEARCH_LENGTH     = 100;
	private const LOG_NAME              = 'HikingCategory';
	private const ORDER_COLUMN          = 'HikingCategory_Order';

	/**
	 * Реєструє AJAX-обробники модуля.
	 */
	public function init(): void {
		add_action( 'wp_ajax_fstu_hikingcategory_get_list', [ $this, 'handle_get_list' ] );
		add_action( 'wp_ajax_nopriv_fstu_hikingcategory_get_list', [ $this, 'handle_get_list' ] );
		add_action( 'wp_ajax_fstu_hikingcategory_get_single', [ $this, 'handle_get_single' ] );
		add_action( 'wp_ajax_nopriv_fstu_hikingcategory_get_single', [ $this, 'handle_get_single' ] );
		add_action( 'wp_ajax_fstu_hikingcategory_create', [ $this, 'handle_create' ] );
		add_action( 'wp_ajax_fstu_hikingcategory_update', [ $this, 'handle_update' ] );
		add_action( 'wp_ajax_fstu_hikingcategory_delete', [ $this, 'handle_delete' ] );
		add_action( 'wp_ajax_fstu_hikingcategory_reorder', [ $this, 'handle_reorder' ] );
		add_action( 'wp_ajax_fstu_hikingcategory_get_protocol', [ $this, 'handle_get_protocol' ] );
	}

	/**
	 * Повертає список категорій походів.
	 */
	public function handle_get_list(): void {
		check_ajax_referer( HikingCategory_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду довідника.', 'fstu' ) ] );
		}

		$search      = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$search      = mb_substr( $search, 0, self::MAX_SEARCH_LENGTH );
		$page        = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page    = min( max( 1, absint( $_POST['per_page'] ?? 10 ) ), self::MAX_PER_PAGE );
		$offset      = ( $page - 1 ) * $per_page;
		$permissions = $this->get_permissions();

		global $wpdb;

		$where  = 'WHERE 1=1';
		$params = [];

		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where   .= ' AND HikingCategory_Name LIKE %s';
			$params[] = $like;
		}

		$count_sql = "SELECT COUNT(*) FROM S_HikingCategory {$where}";
		$total     = (int) ( ! empty( $params )
			? $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) )
			: $wpdb->get_var( $count_sql ) );

		$data_sql = "SELECT HikingCategory_ID,
				HikingCategory_DateCreate,
				HikingCategory_Name,
				" . self::ORDER_COLUMN . "
			FROM S_HikingCategory
			{$where}
			ORDER BY " . self::ORDER_COLUMN . " ASC, HikingCategory_Name ASC, HikingCategory_ID ASC
			LIMIT %d OFFSET %d";

		$data_params = array_merge( $params, [ $per_page, $offset ] );
		$items       = $wpdb->get_results( $wpdb->prepare( $data_sql, ...$data_params ), ARRAY_A );
		$can_reorder = ! empty( $permissions['canManage'] ) && '' === $search;

		wp_send_json_success(
			[
				'html'        => $this->build_rows( is_array( $items ) ? $items : [], $offset, $permissions, $can_reorder ),
				'total'       => $total,
				'page'        => $page,
				'per_page'    => $per_page,
				'total_pages' => max( 1, (int) ceil( $total / max( 1, $per_page ) ) ),
				'can_reorder' => $can_reorder,
			]
		);
	}

	/**
	 * Повертає один запис довідника.
	 */
	public function handle_get_single(): void {
		check_ajax_referer( HikingCategory_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду запису.', 'fstu' ) ] );
		}

		$hikingcategory_id = absint( $_POST['hikingcategory_id'] ?? 0 );

		if ( $hikingcategory_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		$item = $this->get_item_by_id( $hikingcategory_id );

		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		wp_send_json_success(
			[
				'hikingcategory_id'          => (int) ( $item['HikingCategory_ID'] ?? 0 ),
				'hikingcategory_datecreate'  => (string) ( $item['HikingCategory_DateCreate'] ?? '' ),
				'hikingcategory_name'        => (string) ( $item['HikingCategory_Name'] ?? '' ),
				'hikingcategory_order'       => (int) ( $item['HikingCategory_Order'] ?? 0 ),
			]
		);
	}

	/**
	 * Створює запис довідника.
	 */
	public function handle_create(): void {
		check_ajax_referer( HikingCategory_List::NONCE_ACTION, 'nonce' );

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
		$insert_data = [
			'HikingCategory_DateCreate' => current_time( 'mysql' ),
			'HikingCategory_Name'       => $data['hikingcategory_name'],
			self::ORDER_COLUMN          => $data['hikingcategory_order'] > 0 ? $data['hikingcategory_order'] : $this->get_next_order(),
		];

		$wpdb->query( 'START TRANSACTION' );
		$result = $wpdb->insert( 'S_HikingCategory', $insert_data, [ '%s', '%s', '%d' ] );

		if ( false === $result ) {
			$wpdb->query( 'ROLLBACK' );
			$this->log_action_best_effort( 'I', __( 'Помилка додавання категорії походів.', 'fstu' ), 'error' );
			wp_send_json_error( [ 'message' => __( 'Помилка при збереженні запису.', 'fstu' ) ] );
		}

		if ( ! $this->log_action_transactional( 'I', sprintf( 'Додано категорію походів: %s', (string) $data['hikingcategory_name'] ), '✓' ) ) {
			$wpdb->query( 'ROLLBACK' );
			wp_send_json_error( [ 'message' => __( 'Помилка при збереженні запису.', 'fstu' ) ] );
		}

		$wpdb->query( 'COMMIT' );

		wp_send_json_success( [ 'message' => __( 'Запис успішно додано.', 'fstu' ) ] );
	}

	/**
	 * Оновлює запис довідника.
	 */
	public function handle_update(): void {
		check_ajax_referer( HikingCategory_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для редагування запису.', 'fstu' ) ] );
		}

		if ( ! $this->validate_honeypot() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		$hikingcategory_id = absint( $_POST['hikingcategory_id'] ?? 0 );
		$data              = $this->sanitize_form_data();
		$error_message     = $this->validate_form_data( $data, $hikingcategory_id );

		if ( $hikingcategory_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		if ( '' !== $error_message ) {
			wp_send_json_error( [ 'message' => $error_message ] );
		}

		$current_item = $this->get_item_by_id( $hikingcategory_id );
		if ( ! is_array( $current_item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		$update_data = [
			'HikingCategory_Name' => $data['hikingcategory_name'],
			self::ORDER_COLUMN    => $data['hikingcategory_order'] > 0 ? $data['hikingcategory_order'] : (int) ( $current_item['HikingCategory_Order'] ?? 0 ),
		];

		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );

		$result = $wpdb->update(
			'S_HikingCategory',
			$update_data,
			[ 'HikingCategory_ID' => $hikingcategory_id ],
			[ '%s', '%d' ],
			[ '%d' ]
		);

		if ( false === $result ) {
			$wpdb->query( 'ROLLBACK' );
			$this->log_action_best_effort( 'U', __( 'Помилка оновлення категорії походів.', 'fstu' ), 'error' );
			wp_send_json_error( [ 'message' => __( 'Помилка при збереженні запису.', 'fstu' ) ] );
		}

		if ( ! $this->log_action_transactional( 'U', sprintf( 'Оновлено категорію походів: %s', (string) $data['hikingcategory_name'] ), '✓' ) ) {
			$wpdb->query( 'ROLLBACK' );
			wp_send_json_error( [ 'message' => __( 'Помилка при збереженні запису.', 'fstu' ) ] );
		}

		$wpdb->query( 'COMMIT' );

		wp_send_json_success( [ 'message' => __( 'Запис успішно оновлено.', 'fstu' ) ] );
	}

	/**
	 * Видаляє запис довідника.
	 */
	public function handle_delete(): void {
		check_ajax_referer( HikingCategory_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_delete() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для видалення запису.', 'fstu' ) ] );
		}

		$hikingcategory_id = absint( $_POST['hikingcategory_id'] ?? 0 );

		if ( $hikingcategory_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		$item = $this->get_item_by_id( $hikingcategory_id );
		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		if ( $this->has_delete_dependencies( $hikingcategory_id ) ) {
			$this->log_action_best_effort(
				'D',
				sprintf( 'Заблоковано видалення категорії походів: %s', (string) ( $item['HikingCategory_Name'] ?? '' ) ),
				'dependency'
			);

			wp_send_json_error( [ 'message' => __( 'Не вдалося видалити запис, оскільки ця категорія вже використовується в даних досвіду.', 'fstu' ) ] );
		}

		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );
		$result = $wpdb->delete( 'S_HikingCategory', [ 'HikingCategory_ID' => $hikingcategory_id ], [ '%d' ] );

		if ( false === $result ) {
			$wpdb->query( 'ROLLBACK' );
			$this->log_action_best_effort( 'D', __( 'Помилка видалення категорії походів.', 'fstu' ), 'error' );
			wp_send_json_error( [ 'message' => __( 'Не вдалося видалити запис.', 'fstu' ) ] );
		}

		if ( ! $this->log_action_transactional( 'D', sprintf( 'Видалено категорію походів: %s', (string) ( $item['HikingCategory_Name'] ?? '' ) ), '✓' ) ) {
			$wpdb->query( 'ROLLBACK' );
			wp_send_json_error( [ 'message' => __( 'Не вдалося видалити запис.', 'fstu' ) ] );
		}

		$wpdb->query( 'COMMIT' );

		wp_send_json_success( [ 'message' => __( 'Запис успішно видалено.', 'fstu' ) ] );
	}

	/**
	 * Оновлює порядок записів після drag-and-drop.
	 */
	public function handle_reorder(): void {
		check_ajax_referer( HikingCategory_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для зміни сортування.', 'fstu' ) ] );
		}

		$items = wp_unslash( $_POST['items'] ?? '' );
		$items = is_string( $items ) ? json_decode( $items, true ) : $items;

		if ( ! is_array( $items ) || empty( $items ) ) {
			wp_send_json_error( [ 'message' => __( 'Немає даних для сортування.', 'fstu' ) ] );
		}

		$prepared_items = [];
		$seen_ids       = [];

		foreach ( $items as $item ) {
			$hikingcategory_id = absint( $item['id'] ?? 0 );
			$order             = absint( $item['order'] ?? 0 );

			if ( $hikingcategory_id <= 0 || $order <= 0 || isset( $seen_ids[ $hikingcategory_id ] ) ) {
				continue;
			}

			if ( ! is_array( $this->get_item_by_id( $hikingcategory_id ) ) ) {
				continue;
			}

			$seen_ids[ $hikingcategory_id ] = true;
			$prepared_items[]               = [
				'id'    => $hikingcategory_id,
				'order' => $order,
			];
		}

		if ( empty( $prepared_items ) ) {
			wp_send_json_error( [ 'message' => __( 'Передано некоректні дані сортування.', 'fstu' ) ] );
		}

		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );

		foreach ( $prepared_items as $item ) {
			$result = $wpdb->update(
				'S_HikingCategory',
				[ self::ORDER_COLUMN => $item['order'] ],
				[ 'HikingCategory_ID' => $item['id'] ],
				[ '%d' ],
				[ '%d' ]
			);

			if ( false === $result ) {
				$wpdb->query( 'ROLLBACK' );
				$this->log_action_best_effort( 'U', __( 'Помилка зміни порядку категорій походів.', 'fstu' ), 'error' );
				wp_send_json_error( [ 'message' => __( 'Не вдалося оновити порядок записів.', 'fstu' ) ] );
			}
		}

		if ( ! $this->log_action_transactional( 'U', __( 'Оновлено порядок категорій походів.', 'fstu' ), '✓' ) ) {
			$wpdb->query( 'ROLLBACK' );
			wp_send_json_error( [ 'message' => __( 'Не вдалося оновити порядок записів.', 'fstu' ) ] );
		}

		$wpdb->query( 'COMMIT' );

		wp_send_json_success( [ 'message' => __( 'Порядок записів успішно оновлено.', 'fstu' ) ] );
	}

	/**
	 * Повертає протокол модуля.
	 */
	public function handle_get_protocol(): void {
		check_ajax_referer( HikingCategory_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_protocol() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду протоколу.', 'fstu' ) ] );
		}

		$search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$search   = mb_substr( $search, 0, self::MAX_SEARCH_LENGTH );
		$page     = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page = min( max( 1, absint( $_POST['per_page'] ?? 10 ) ), self::MAX_PROTOCOL_PER_PAGE );
		$offset   = ( $page - 1 ) * $per_page;

		global $wpdb;

		$where  = 'WHERE l.Logs_Name = %s AND l.Logs_Type IN (%s, %s, %s)';
		$params = [ self::LOG_NAME, 'I', 'U', 'D' ];

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

		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );

		$data_sql = "SELECT l.Logs_DateCreate, l.Logs_Type, l.Logs_Name, l.Logs_Text, l.Logs_Error, u.FIO
			FROM Logs l
			LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
			{$where}
			ORDER BY l.Logs_DateCreate DESC
			LIMIT %d OFFSET %d";

		$data_params = array_merge( $params, [ $per_page, $offset ] );
		$items       = $wpdb->get_results( $wpdb->prepare( $data_sql, ...$data_params ), ARRAY_A );

		wp_send_json_success(
			[
				'html'        => $this->build_protocol_rows( is_array( $items ) ? $items : [] ),
				'total'       => $total,
				'page'        => $page,
				'per_page'    => $per_page,
				'total_pages' => max( 1, (int) ceil( $total / max( 1, $per_page ) ) ),
			]
		);
	}

	/**
	 * @param array<int,array<string,mixed>> $items
	 * @param array<string,bool>             $permissions
	 */
	private function build_rows( array $items, int $offset, array $permissions, bool $can_reorder ): string {
		if ( empty( $items ) ) {
			return '<tr class="fstu-row"><td colspan="3" class="fstu-no-results">' . esc_html__( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ) . '</td></tr>';
		}

		$html       = '';
		$index      = $offset;
		$can_manage = ! empty( $permissions['canManage'] );
		$can_delete = ! empty( $permissions['canDelete'] );

		foreach ( $items as $item ) {
			++$index;
			$hikingcategory_id = (int) ( $item['HikingCategory_ID'] ?? 0 );
			$drag_handle       = $can_reorder
				? '<span class="fstu-drag-handle" title="' . esc_attr__( 'Перетягніть для зміни порядку', 'fstu' ) . '">⋮⋮</span>'
				: '<span class="fstu-drag-handle fstu-drag-handle--disabled" aria-hidden="true">⋮⋮</span>';

			$actions   = [];
			$actions[] = '<button type="button" class="fstu-hikingcategory-dropdown__item fstu-hikingcategory-view-btn" data-hikingcategory-id="' . esc_attr( (string) $hikingcategory_id ) . '">' . esc_html__( 'Перегляд', 'fstu' ) . '</button>';

			if ( $can_manage ) {
				$actions[] = '<button type="button" class="fstu-hikingcategory-dropdown__item fstu-hikingcategory-edit-btn" data-hikingcategory-id="' . esc_attr( (string) $hikingcategory_id ) . '">' . esc_html__( 'Редагування', 'fstu' ) . '</button>';
			}

			if ( $can_delete ) {
				$actions[] = '<button type="button" class="fstu-hikingcategory-dropdown__item fstu-hikingcategory-dropdown__item--danger fstu-hikingcategory-delete-btn" data-hikingcategory-id="' . esc_attr( (string) $hikingcategory_id ) . '">' . esc_html__( 'Видалення', 'fstu' ) . '</button>';
			}

			$html .= '<tr class="fstu-row fstu-hikingcategory-row" data-hikingcategory-id="' . esc_attr( (string) $hikingcategory_id ) . '"' . ( $can_reorder ? ' draggable="true"' : '' ) . '>';
			$html .= '<td class="fstu-td fstu-td--num">' . $drag_handle . '<span class="fstu-row-number">' . esc_html( (string) $index ) . '</span></td>';
			$html .= '<td class="fstu-td fstu-td--name"><button type="button" class="fstu-hikingcategory-link-button fstu-hikingcategory-view-btn" data-hikingcategory-id="' . esc_attr( (string) $hikingcategory_id ) . '">' . esc_html( (string) ( $item['HikingCategory_Name'] ?? '' ) ) . '</button></td>';
			$html .= '<td class="fstu-td fstu-td--actions"><div class="fstu-hikingcategory-dropdown"><button type="button" class="fstu-hikingcategory-dropdown__toggle" aria-expanded="false" title="' . esc_attr__( 'Меню дій', 'fstu' ) . '">▼</button><div class="fstu-hikingcategory-dropdown__menu">' . implode( '', $actions ) . '</div></div></td>';
			$html .= '</tr>';
		}

		return $html;
	}

	/**
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
			$html .= '<td class="fstu-td fstu-td--type">' . $this->build_protocol_type_badge( (string) ( $item['Logs_Type'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--operation">' . esc_html( (string) ( $item['Logs_Name'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--message">' . esc_html( (string) ( $item['Logs_Text'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--status">' . esc_html( (string) ( $item['Logs_Error'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--user">' . esc_html( (string) ( $item['FIO'] ?? '' ) ) . '</td>';
			$html .= '</tr>';
		}

		return $html;
	}

	/**
	 * @return array<string,int|string>
	 */
	private function sanitize_form_data(): array {
		return [
			'hikingcategory_name'  => sanitize_text_field( wp_unslash( $_POST['hikingcategory_name'] ?? '' ) ),
			'hikingcategory_order' => absint( $_POST['hikingcategory_order'] ?? 0 ),
		];
	}

	/**
	 * @param array<string,int|string> $data
	 */
	private function validate_form_data( array $data, int $exclude_id = 0 ): string {
		$name  = trim( (string) ( $data['hikingcategory_name'] ?? '' ) );
		$order = (int) ( $data['hikingcategory_order'] ?? 0 );

		if ( mb_strlen( $name ) < 2 ) {
			return __( 'Поле «Найменування» є обов’язковим.', 'fstu' );
		}

		if ( mb_strlen( $name ) > 255 ) {
			return __( 'Поле «Найменування» не може бути довшим за 255 символів.', 'fstu' );
		}

		if ( ! preg_match( '/^[\p{L}\p{N}\s\-.,()№#\/]+$/u', $name ) ) {
			return __( 'Поле «Найменування» містить недопустимі символи.', 'fstu' );
		}

		if ( $order < 0 ) {
			return __( 'Поле «Сортування» має бути невід’ємним числом.', 'fstu' );
		}

		return '';
	}

	/**
	 * Перевіряє honeypot-поле.
	 */
	private function validate_honeypot(): bool {
		$honeypot = sanitize_text_field( wp_unslash( $_POST['fstu_website'] ?? '' ) );

		return '' === $honeypot;
	}

	/**
	 * @return array<string,bool>
	 */
	private function get_permissions(): array {
		return Capabilities::get_hikingcategory_permissions();
	}

	/**
	 * @return array<string,mixed>|null
	 */
	private function get_item_by_id( int $hikingcategory_id ): ?array {
		global $wpdb;

		$item = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT HikingCategory_ID,
					HikingCategory_DateCreate,
					HikingCategory_Name,
					" . self::ORDER_COLUMN . "
				FROM S_HikingCategory
				WHERE HikingCategory_ID = %d
				LIMIT 1",
				$hikingcategory_id
			),
			ARRAY_A
		);

		return is_array( $item ) ? $item : null;
	}


	/**
	 * Перевіряє залежності перед delete.
	 */
	private function has_delete_dependencies( int $hikingcategory_id ): bool {
		global $wpdb;

		if ( ! $this->table_exists( 'UserHikingCategory' ) ) {
			return false;
		}

		$usage_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM UserHikingCategory WHERE HikingCategory_ID = %d',
				$hikingcategory_id
			)
		);

		return $usage_count > 0;
	}

	/**
	 * Перевіряє наявність таблиці.
	 */
	private function table_exists( string $table_name ): bool {
		static $cache = [];

		if ( array_key_exists( $table_name, $cache ) ) {
			return $cache[ $table_name ];
		}

		global $wpdb;
		$prepared           = $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name );
		$cache[ $table_name ] = null !== $wpdb->get_var( $prepared );

		return $cache[ $table_name ];
	}

	/**
	 * Повертає наступний order.
	 */
	private function get_next_order(): int {
		global $wpdb;

		$max_order = (int) $wpdb->get_var( 'SELECT COALESCE(MAX(HikingCategory_Order), 0) FROM S_HikingCategory' );

		return $max_order + 1;
	}

	/**
	 * Записує лог у поточній транзакції.
	 */
	private function log_action_transactional( string $type, string $text, string $status ): bool {
		global $wpdb;

		$result = $wpdb->insert(
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

		return false !== $result;
	}

	/**
	 * Записує лог поза транзакцією у best-effort режимі.
	 */
	private function log_action_best_effort( string $type, string $text, string $status ): void {
		global $wpdb;

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

	/**
	 * Повертає HTML-мітку типу операції протоколу.
	 */
	private function build_protocol_type_badge( string $type ): string {
		$map = [
			'I' => [ 'label' => 'INSERT', 'class' => 'fstu-badge--insert' ],
			'U' => [ 'label' => 'UPDATE', 'class' => 'fstu-badge--update' ],
			'D' => [ 'label' => 'DELETE', 'class' => 'fstu-badge--delete' ],
		];

		$config = $map[ $type ] ?? [ 'label' => $type ?: '—', 'class' => 'fstu-badge--default' ];

		return '<span class="fstu-badge ' . esc_attr( $config['class'] ) . '">' . esc_html( $config['label'] ) . '</span>';
	}

	/**
	 * Чи може поточний користувач бачити модуль.
	 */
	private function current_user_can_view(): bool {
		return Capabilities::current_user_can_view_hikingcategory();
	}

	/**
	 * Чи може поточний користувач керувати довідником.
	 */
	private function current_user_can_manage(): bool {
		return Capabilities::current_user_can_manage_hikingcategory();
	}

	/**
	 * Чи може поточний користувач видаляти записи.
	 */
	private function current_user_can_delete(): bool {
		return Capabilities::current_user_can_delete_hikingcategory();
	}

	/**
	 * Чи може поточний користувач бачити протокол.
	 */
	private function current_user_can_protocol(): bool {
		return Capabilities::current_user_can_view_hikingcategory_protocol();
	}
}

