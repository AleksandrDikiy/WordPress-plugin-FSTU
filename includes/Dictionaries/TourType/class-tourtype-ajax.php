<?php
/**
 * AJAX-обробники модуля "Довідник видів походів".
 *
 * Version:     1.1.0
 * Date_update: 2026-04-12
 *
 * @package FSTU\Dictionaries\TourType
 */

namespace FSTU\Dictionaries\TourType;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TourType_Ajax {

	private const MAX_PER_PAGE          = 50;
	private const MAX_PROTOCOL_PER_PAGE = 50;
	private const MAX_SEARCH_LENGTH     = 100;
	private const LOG_NAME              = 'TourType';
	private const ORDER_COLUMN          = 'TourType_Order';

	/**
	 * Реєструє AJAX-обробники модуля.
	 */
	public function init(): void {
		add_action( 'wp_ajax_fstu_tourtype_get_list', [ $this, 'handle_get_list' ] );
		add_action( 'wp_ajax_nopriv_fstu_tourtype_get_list', [ $this, 'handle_get_list' ] );
		add_action( 'wp_ajax_fstu_tourtype_get_single', [ $this, 'handle_get_single' ] );
		add_action( 'wp_ajax_nopriv_fstu_tourtype_get_single', [ $this, 'handle_get_single' ] );
		add_action( 'wp_ajax_fstu_tourtype_get_filters', [ $this, 'handle_get_filters' ] );
		add_action( 'wp_ajax_nopriv_fstu_tourtype_get_filters', [ $this, 'handle_get_filters' ] );
		add_action( 'wp_ajax_fstu_tourtype_create', [ $this, 'handle_create' ] );
		add_action( 'wp_ajax_fstu_tourtype_update', [ $this, 'handle_update' ] );
		add_action( 'wp_ajax_fstu_tourtype_delete', [ $this, 'handle_delete' ] );
		add_action( 'wp_ajax_fstu_tourtype_reorder', [ $this, 'handle_reorder' ] );
		add_action( 'wp_ajax_fstu_tourtype_get_protocol', [ $this, 'handle_get_protocol' ] );
	}

	/**
	 * Повертає список видів складності для select-фільтра.
	 */
	public function handle_get_filters(): void {
		check_ajax_referer( TourType_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для завантаження довідників.', 'fstu' ) ] );
		}

		wp_send_json_success(
			[
				'categories'         => $this->get_categories(),
				'reorder_supported'  => $this->has_order_column(),
			]
		);
	}

	/**
	 * Повертає список записів довідника.
	 */
	public function handle_get_list(): void {
		check_ajax_referer( TourType_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду довідника.', 'fstu' ) ] );
		}

		$search             = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$search             = mb_substr( $search, 0, self::MAX_SEARCH_LENGTH );
		$page               = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page           = min( max( 1, absint( $_POST['per_page'] ?? 10 ) ), self::MAX_PER_PAGE );
		$hour_categories_id = absint( $_POST['hour_categories_id'] ?? 0 );
		$offset             = ( $page - 1 ) * $per_page;
		$permissions        = $this->get_permissions();
		$has_order_column   = $this->has_order_column();

		global $wpdb;

		$where  = 'WHERE 1=1';
		$params = [];

		if ( $hour_categories_id > 0 ) {
			$where   .= ' AND c.HourCategories_ID = %d';
			$params[] = $hour_categories_id;
		}

		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where   .= ' AND t.TourType_Name LIKE %s';
			$params[] = $like;
		}

		$count_sql = "SELECT COUNT(*)
			FROM S_TourType t
			INNER JOIN S_HourCategories c ON c.HourCategories_ID = t.HourCategories_ID
			{$where}";

		$total = (int) ( ! empty( $params )
			? $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) )
			: $wpdb->get_var( $count_sql ) );

		$order_by = $has_order_column
			? 'c.HourCategories_Code ASC, t.' . self::ORDER_COLUMN . ' ASC, t.TourType_ID ASC'
			: 'c.HourCategories_Code ASC, t.TourType_Code ASC, t.TourType_ID ASC';

		$data_sql = "SELECT CONCAT(c.HourCategories_Name, ' - ', t.TourType_Name) AS TourName,
				t.TourType_ID,
				t.TourType_Name,
				t.TourType_Code,
				t.TourType_Day,
				c.HourCategories_ID,
				c.HourCategories_Code,
				c.HourCategories_Name";

		if ( $has_order_column ) {
			$data_sql .= ', t.' . self::ORDER_COLUMN . ' AS TourType_Order';
		}

		$data_sql .= "
			FROM S_TourType t
			INNER JOIN S_HourCategories c ON c.HourCategories_ID = t.HourCategories_ID
			{$where}
			ORDER BY {$order_by}
			LIMIT %d OFFSET %d";

		$data_params = array_merge( $params, [ $per_page, $offset ] );
		$items       = $wpdb->get_results( $wpdb->prepare( $data_sql, ...$data_params ), ARRAY_A );
		$can_reorder = $has_order_column && ! empty( $permissions['canManage'] ) && 0 !== $hour_categories_id && '' === $search;

		wp_send_json_success(
			[
				'html'              => $this->build_rows( is_array( $items ) ? $items : [], $offset, $permissions, $can_reorder ),
				'total'             => $total,
				'page'              => $page,
				'per_page'          => $per_page,
				'total_pages'       => max( 1, (int) ceil( $total / max( 1, $per_page ) ) ),
				'reorder_supported' => $has_order_column,
				'can_reorder'       => $can_reorder,
			]
		);
	}

	/**
	 * Повертає один запис довідника.
	 */
	public function handle_get_single(): void {
		check_ajax_referer( TourType_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду запису.', 'fstu' ) ] );
		}

		$tourtype_id = absint( $_POST['tourtype_id'] ?? 0 );

		if ( $tourtype_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		$item = $this->get_item_by_id( $tourtype_id );

		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		wp_send_json_success(
			[
				'tourtype_id'          => (int) ( $item['TourType_ID'] ?? 0 ),
				'tourtype_name'        => (string) ( $item['TourType_Name'] ?? '' ),
				'tourtype_code'        => (string) ( $item['TourType_Code'] ?? '' ),
				'tourtype_day'         => (int) ( $item['TourType_Day'] ?? 0 ),
				'tourtype_order'       => (int) ( $item['TourType_Order'] ?? 0 ),
				'hour_categories_id'   => (int) ( $item['HourCategories_ID'] ?? 0 ),
				'hour_categories_name' => (string) ( $item['HourCategories_Name'] ?? '' ),
				'hour_categories_code' => (string) ( $item['HourCategories_Code'] ?? '' ),
				'tour_name'            => (string) ( $item['TourName'] ?? '' ),
			]
		);
	}

	/**
	 * Створює новий запис довідника.
	 */
	public function handle_create(): void {
		check_ajax_referer( TourType_List::NONCE_ACTION, 'nonce' );

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
			'HourCategories_ID' => $data['hour_categories_id'],
			'TourType_Name'     => $data['tourtype_name'],
			'TourType_Code'     => $data['tourtype_code'],
			'TourType_Day'      => $data['tourtype_day'],
		];
		$formats     = [ '%d', '%s', '%s', '%d' ];

		if ( $this->has_order_column() ) {
			$requested_order = (int) ( $data['tourtype_order'] ?? 0 );
			$insert_data[ self::ORDER_COLUMN ] = $requested_order > 0
				? $requested_order
				: $this->get_next_order_for_category( (int) $data['hour_categories_id'] );
			$formats[]                         = '%d';
		}

		$wpdb->query( 'START TRANSACTION' );

		$result = $wpdb->insert( 'S_TourType', $insert_data, $formats );

		if ( false === $result ) {
			$wpdb->query( 'ROLLBACK' );
			$this->log_action_best_effort( 'I', __( 'Помилка додавання виду походу.', 'fstu' ), 'error' );
			wp_send_json_error( [ 'message' => __( 'Помилка при збереженні запису.', 'fstu' ) ] );
		}

		if ( ! $this->log_action_transactional( 'I', sprintf( 'Додано вид походу: %s', (string) $data['tourtype_name'] ), '✓' ) ) {
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
		check_ajax_referer( TourType_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для редагування запису.', 'fstu' ) ] );
		}

		if ( ! $this->validate_honeypot() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		$tourtype_id   = absint( $_POST['tourtype_id'] ?? 0 );
		$data          = $this->sanitize_form_data();
		$error_message = $this->validate_form_data( $data, $tourtype_id );

		if ( $tourtype_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		if ( '' !== $error_message ) {
			wp_send_json_error( [ 'message' => $error_message ] );
		}

		$current_item = $this->get_item_by_id( $tourtype_id );
		if ( ! is_array( $current_item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		$update_data = [
			'HourCategories_ID' => $data['hour_categories_id'],
			'TourType_Name'     => $data['tourtype_name'],
			'TourType_Code'     => $data['tourtype_code'],
			'TourType_Day'      => $data['tourtype_day'],
		];
		$formats     = [ '%d', '%s', '%s', '%d' ];

		if ( $this->has_order_column() ) {
			$current_category_id              = (int) ( $current_item['HourCategories_ID'] ?? 0 );
			$requested_order                  = (int) ( $data['tourtype_order'] ?? 0 );
			$update_data[ self::ORDER_COLUMN ] = $requested_order > 0
				? $requested_order
				: ( (int) $data['hour_categories_id'] !== $current_category_id
					? $this->get_next_order_for_category( (int) $data['hour_categories_id'] )
					: (int) ( $current_item['TourType_Order'] ?? 0 ) );
			$formats[] = '%d';
		}

		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );

		$result = $wpdb->update(
			'S_TourType',
			$update_data,
			[ 'TourType_ID' => $tourtype_id ],
			$formats,
			[ '%d' ]
		);

		if ( false === $result ) {
			$wpdb->query( 'ROLLBACK' );
			$this->log_action_best_effort( 'U', __( 'Помилка оновлення виду походу.', 'fstu' ), 'error' );
			wp_send_json_error( [ 'message' => __( 'Помилка при збереженні запису.', 'fstu' ) ] );
		}

		if ( ! $this->log_action_transactional( 'U', sprintf( 'Оновлено вид походу: %s', (string) $data['tourtype_name'] ), '✓' ) ) {
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
		check_ajax_referer( TourType_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_delete() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для видалення запису.', 'fstu' ) ] );
		}

		$tourtype_id = absint( $_POST['tourtype_id'] ?? 0 );

		if ( $tourtype_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		$item = $this->get_item_by_id( $tourtype_id );
		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		if ( $this->has_delete_dependencies( $tourtype_id ) ) {
			$this->log_action_best_effort(
				'D',
				sprintf( 'Заблоковано видалення виду походу: %s', (string) ( $item['TourType_Name'] ?? '' ) ),
				'dependency'
			);

			wp_send_json_error( [ 'message' => __( 'Не вдалося видалити запис, оскільки він уже використовується у календарі.', 'fstu' ) ] );
		}

		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );

		$result = $wpdb->delete( 'S_TourType', [ 'TourType_ID' => $tourtype_id ], [ '%d' ] );

		if ( false === $result ) {
			$wpdb->query( 'ROLLBACK' );
			$this->log_action_best_effort( 'D', __( 'Помилка видалення виду походу.', 'fstu' ), 'error' );
			wp_send_json_error( [ 'message' => __( 'Не вдалося видалити запис.', 'fstu' ) ] );
		}

		if ( ! $this->log_action_transactional( 'D', sprintf( 'Видалено вид походу: %s', (string) ( $item['TourType_Name'] ?? '' ) ), '✓' ) ) {
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
		check_ajax_referer( TourType_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для зміни сортування.', 'fstu' ) ] );
		}

		if ( ! $this->has_order_column() ) {
			wp_send_json_error( [ 'message' => __( 'У поточній структурі БД сортування drag-and-drop недоступне.', 'fstu' ) ] );
		}

		$hour_categories_id = absint( $_POST['hour_categories_id'] ?? 0 );
		$items              = wp_unslash( $_POST['items'] ?? '' );
		$items              = is_string( $items ) ? json_decode( $items, true ) : $items;

		if ( $hour_categories_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Для зміни порядку оберіть конкретну категорію.', 'fstu' ) ] );
		}

		if ( ! is_array( $items ) || empty( $items ) ) {
			wp_send_json_error( [ 'message' => __( 'Немає даних для сортування.', 'fstu' ) ] );
		}

		$prepared_items = [];
		$seen_ids       = [];

		foreach ( $items as $item ) {
			$tourtype_id = absint( $item['id'] ?? 0 );
			$order       = absint( $item['order'] ?? 0 );

			if ( $tourtype_id <= 0 || $order <= 0 || isset( $seen_ids[ $tourtype_id ] ) ) {
				continue;
			}

			$record = $this->get_item_by_id( $tourtype_id );
			if ( ! is_array( $record ) || (int) ( $record['HourCategories_ID'] ?? 0 ) !== $hour_categories_id ) {
				continue;
			}

			$seen_ids[ $tourtype_id ] = true;
			$prepared_items[]         = [
				'id'    => $tourtype_id,
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
				'S_TourType',
				[ self::ORDER_COLUMN => $item['order'] ],
				[ 'TourType_ID' => $item['id'] ],
				[ '%d' ],
				[ '%d' ]
			);

			if ( false === $result ) {
				$wpdb->query( 'ROLLBACK' );
				$this->log_action_best_effort( 'U', __( 'Помилка зміни порядку видів походів.', 'fstu' ), 'error' );
				wp_send_json_error( [ 'message' => __( 'Не вдалося оновити порядок записів.', 'fstu' ) ] );
			}
		}

		if ( ! $this->log_action_transactional( 'U', __( 'Оновлено порядок видів походів.', 'fstu' ), '✓' ) ) {
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
		check_ajax_referer( TourType_List::NONCE_ACTION, 'nonce' );

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
	 * Будує HTML рядків основної таблиці.
	 *
	 * @param array<int,array<string,mixed>> $items
	 * @param array<string,bool>             $permissions
	 */
	private function build_rows( array $items, int $offset, array $permissions, bool $can_reorder ): string {
		if ( empty( $items ) ) {
			return '<tr class="fstu-row"><td colspan="6" class="fstu-no-results">' . esc_html__( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ) . '</td></tr>';
		}

		$html       = '';
		$index      = $offset;
		$can_manage = ! empty( $permissions['canManage'] );
		$can_delete = ! empty( $permissions['canDelete'] );

		foreach ( $items as $item ) {
			++$index;
			$tourtype_id = (int) ( $item['TourType_ID'] ?? 0 );
			$drag_handle = $can_reorder
				? '<span class="fstu-drag-handle" title="' . esc_attr__( 'Перетягніть для зміни порядку', 'fstu' ) . '">⋮⋮</span>'
				: '<span class="fstu-drag-handle fstu-drag-handle--disabled" aria-hidden="true">⋮⋮</span>';

			$actions   = [];
			$actions[] = '<button type="button" class="fstu-tourtype-dropdown__item fstu-tourtype-view-btn" data-tourtype-id="' . esc_attr( (string) $tourtype_id ) . '">' . esc_html__( 'Перегляд', 'fstu' ) . '</button>';

			if ( $can_manage ) {
				$actions[] = '<button type="button" class="fstu-tourtype-dropdown__item fstu-tourtype-edit-btn" data-tourtype-id="' . esc_attr( (string) $tourtype_id ) . '">' . esc_html__( 'Редагування', 'fstu' ) . '</button>';
			}

			if ( $can_delete ) {
				$actions[] = '<button type="button" class="fstu-tourtype-dropdown__item fstu-tourtype-dropdown__item--danger fstu-tourtype-delete-btn" data-tourtype-id="' . esc_attr( (string) $tourtype_id ) . '">' . esc_html__( 'Видалення', 'fstu' ) . '</button>';
			}

			$html .= '<tr class="fstu-row fstu-tourtype-row" data-tourtype-id="' . esc_attr( (string) $tourtype_id ) . '"' . ( $can_reorder ? ' draggable="true"' : '' ) . '>';
			$html .= '<td class="fstu-td fstu-td--num">' . $drag_handle . '<span class="fstu-row-number">' . esc_html( (string) $index ) . '</span></td>';
			$html .= '<td class="fstu-td fstu-td--category">' . esc_html( (string) ( $item['HourCategories_Name'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--name"><button type="button" class="fstu-tourtype-link-button fstu-tourtype-view-btn" data-tourtype-id="' . esc_attr( (string) $tourtype_id ) . '">' . esc_html( (string) ( $item['TourType_Name'] ?? '' ) ) . '</button></td>';
			$html .= '<td class="fstu-td fstu-td--code">' . esc_html( (string) ( $item['TourType_Code'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--day">' . esc_html( (string) ( $item['TourType_Day'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--actions">';
			$html .= '<div class="fstu-tourtype-dropdown">';
			$html .= '<button type="button" class="fstu-tourtype-dropdown__toggle" aria-expanded="false" title="' . esc_attr__( 'Меню дій', 'fstu' ) . '">▼</button>';
			$html .= '<div class="fstu-tourtype-dropdown__menu">' . implode( '', $actions ) . '</div>';
			$html .= '</div></td></tr>';
		}

		return $html;
	}

	/**
	 * Будує рядки протоколу.
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
			'hour_categories_id' => absint( $_POST['hour_categories_id'] ?? 0 ),
			'tourtype_name'      => sanitize_text_field( wp_unslash( $_POST['tourtype_name'] ?? '' ) ),
			'tourtype_code'      => sanitize_text_field( wp_unslash( $_POST['tourtype_code'] ?? '' ) ),
			'tourtype_day'       => absint( $_POST['tourtype_day'] ?? 0 ),
			'tourtype_order'     => absint( $_POST['tourtype_order'] ?? 0 ),
		];
	}

	/**
	 * @param array<string,int|string> $data
	 */
	private function validate_form_data( array $data, int $exclude_id = 0 ): string {
		$hour_categories_id = (int) ( $data['hour_categories_id'] ?? 0 );
		$name               = trim( (string) ( $data['tourtype_name'] ?? '' ) );
		$code               = trim( (string) ( $data['tourtype_code'] ?? '' ) );
		$day                = (int) ( $data['tourtype_day'] ?? 0 );

		if ( $hour_categories_id <= 0 || ! $this->category_exists( $hour_categories_id ) ) {
			return __( 'Оберіть коректну категорію походу.', 'fstu' );
		}

		if ( mb_strlen( $name ) < 2 ) {
			return __( 'Поле «Найменування» є обов’язковим.', 'fstu' );
		}

		if ( mb_strlen( $name ) > 255 ) {
			return __( 'Поле «Найменування» не може бути довшим за 255 символів.', 'fstu' );
		}

		if ( '' === $code ) {
			return __( 'Поле «Код походу» є обов’язковим.', 'fstu' );
		}

		if ( mb_strlen( $code ) > 20 ) {
			return __( 'Поле «Код походу» не може бути довшим за 20 символів.', 'fstu' );
		}

		if ( ! preg_match( '/^[\p{L}\p{N}\-\/\.\s]+$/u', $code ) ) {
			return __( 'Поле «Код походу» містить недопустимі символи.', 'fstu' );
		}

		if ( $day < 0 ) {
			return __( 'Поле «Мінімальна тривалість (дні)» має бути невід’ємним числом.', 'fstu' );
		}

		if ( $this->code_exists_in_category( $hour_categories_id, $code, $exclude_id ) ) {
			return __( 'У межах вибраної категорії запис з таким кодом уже існує.', 'fstu' );
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
		return Capabilities::get_tourtype_permissions();
	}

	/**
	 * Повертає запис за ID.
	 *
	 * @return array<string,mixed>|null
	 */
	private function get_item_by_id( int $tourtype_id ): ?array {
		global $wpdb;
		$select_order = $this->has_order_column() ? ', t.' . self::ORDER_COLUMN . ' AS TourType_Order' : '';

		$item = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT CONCAT(c.HourCategories_Name, ' - ', t.TourType_Name) AS TourName,
					t.TourType_ID,
					t.TourType_Name,
					t.TourType_Code,
					t.TourType_Day,
					c.HourCategories_ID,
					c.HourCategories_Code,
					c.HourCategories_Name
					{$select_order}
				FROM S_TourType t
				INNER JOIN S_HourCategories c ON c.HourCategories_ID = t.HourCategories_ID
				WHERE t.TourType_ID = %d
				LIMIT 1",
				$tourtype_id
			),
			ARRAY_A
		);

		return is_array( $item ) ? $item : null;
	}

	/**
	 * Повертає список категорій для select-фільтра та форми.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_categories(): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			'SELECT HourCategories_ID, HourCategories_Name, HourCategories_Code FROM S_HourCategories ORDER BY HourCategories_Code ASC, HourCategories_Name ASC',
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * Перевіряє, чи існує категорія.
	 */
	private function category_exists( int $hour_categories_id ): bool {
		global $wpdb;

		$exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT HourCategories_ID FROM S_HourCategories WHERE HourCategories_ID = %d LIMIT 1',
				$hour_categories_id
			)
		);

		return null !== $exists;
	}

	/**
	 * Перевіряє, чи існує такий код у межах тієї самої категорії.
	 */
	private function code_exists_in_category( int $hour_categories_id, string $code, int $exclude_id = 0 ): bool {
		global $wpdb;

		$sql    = 'SELECT TourType_ID FROM S_TourType WHERE HourCategories_ID = %d AND TourType_Code = %s';
		$params = [ $hour_categories_id, $code ];

		if ( $exclude_id > 0 ) {
			$sql     .= ' AND TourType_ID != %d';
			$params[] = $exclude_id;
		}

		$sql .= ' LIMIT 1';

		$existing_id = $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );

		return null !== $existing_id;
	}

	/**
	 * Перевіряє залежності перед видаленням.
	 */
	private function has_delete_dependencies( int $tourtype_id ): bool {
		global $wpdb;

		if ( ! $this->table_exists( 'Calendar' ) ) {
			return false;
		}

		$usage_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM Calendar WHERE TourType_ID = %d',
				$tourtype_id
			)
		);

		return $usage_count > 0;
	}

	/**
	 * Перевіряє наявність технічної колонки сортування.
	 */
	private function has_order_column(): bool {
		static $has_column = null;

		if ( null !== $has_column ) {
			return $has_column;
		}

		global $wpdb;
		$columns = $wpdb->get_results( 'SHOW COLUMNS FROM S_TourType', ARRAY_A );

		$has_column = false;
		if ( is_array( $columns ) ) {
			foreach ( $columns as $column ) {
				if ( self::ORDER_COLUMN === (string) ( $column['Field'] ?? '' ) ) {
					$has_column = true;
					break;
				}
			}
		}

		return $has_column;
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
		$prepared     = $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name );
		$cache[$table_name] = null !== $wpdb->get_var( $prepared );

		return $cache[ $table_name ];
	}

	/**
	 * Повертає наступний order для категорії, якщо колонка існує.
	 */
	private function get_next_order_for_category( int $hour_categories_id ): int {
		if ( ! $this->has_order_column() ) {
			return 0;
		}

		global $wpdb;

		$max_order = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COALESCE(MAX(' . self::ORDER_COLUMN . '), 0) FROM S_TourType WHERE HourCategories_ID = %d',
				$hour_categories_id
			)
		);

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
		return Capabilities::current_user_can_view_tourtype();
	}

	/**
	 * Чи може поточний користувач керувати довідником.
	 */
	private function current_user_can_manage(): bool {
		return Capabilities::current_user_can_manage_tourtype();
	}

	/**
	 * Чи може поточний користувач видаляти записи.
	 */
	private function current_user_can_delete(): bool {
		return Capabilities::current_user_can_delete_tourtype();
	}

	/**
	 * Чи може поточний користувач бачити протокол.
	 */
	private function current_user_can_protocol(): bool {
		return Capabilities::current_user_can_view_tourtype_protocol();
	}
}

