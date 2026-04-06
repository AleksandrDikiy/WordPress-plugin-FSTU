<?php
/**
 * AJAX-обробники модуля "Довідник видів змагань ФСТУ".
 * Всі запити до БД виконуються виключно через $wpdb->prepare().
 *
 * Version:     1.2.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\TypeEvent
 */

namespace FSTU\Dictionaries\TypeEvent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TypeEvent_Ajax {

	/** Кількість записів на сторінці за замовчуванням. */
	private const DEFAULT_PER_PAGE = 15;

	/** Максимальна кількість записів на сторінці. */
	private const MAX_PER_PAGE = 100;

	/** Максимальна довжина пошукового рядка. */
	private const MAX_SEARCH_LENGTH = 100;

	/** Доступні значення пагінації для протоколу. */
	private const PROTOCOL_PER_PAGE_ALLOWED = [ 10, 20, 50 ];

	/**
	 * Реєструє AJAX хуки WordPress.
	 */
	public function init(): void {
		add_action( 'wp_ajax_fstu_typeevent_get_list', [ $this, 'handle_get_list' ] );
		add_action( 'wp_ajax_nopriv_fstu_typeevent_get_list', [ $this, 'handle_get_list' ] );

		add_action( 'wp_ajax_fstu_typeevent_get_single', [ $this, 'handle_get_single' ] );
		add_action( 'wp_ajax_nopriv_fstu_typeevent_get_single', [ $this, 'handle_get_single' ] );

		add_action( 'wp_ajax_fstu_typeevent_save', [ $this, 'handle_save' ] );
		add_action( 'wp_ajax_fstu_typeevent_delete', [ $this, 'handle_delete' ] );
		add_action( 'wp_ajax_fstu_typeevent_get_protocol', [ $this, 'handle_get_protocol' ] );
	}

	/**
	 * Повертає список видів змагань.
	 */
	public function handle_get_list(): void {
		check_ajax_referer( TypeEvent_List::NONCE_ACTION, 'nonce' );

		$search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$search   = substr( $search, 0, self::MAX_SEARCH_LENGTH );
		$page     = absint( $_POST['page'] ?? 1 );
		$per_page = absint( $_POST['per_page'] ?? self::DEFAULT_PER_PAGE );

		if ( $page < 1 ) {
			$page = 1;
		}
		if ( $per_page < 1 || $per_page > self::MAX_PER_PAGE ) {
			$per_page = self::DEFAULT_PER_PAGE;
		}

		global $wpdb;

		$query       = 'SELECT TypeEvent_ID, TypeEvent_Name, TypeEvent_Code FROM vTypeEvent WHERE 1=1';
		$count_query = 'SELECT COUNT(*) FROM vTypeEvent WHERE 1=1';
		$params      = [];

		if ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$query       .= ' AND TypeEvent_Name LIKE %s';
			$count_query .= ' AND TypeEvent_Name LIKE %s';
			$params[]     = $like;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_query, $params ) );

		$offset = ( $page - 1 ) * $per_page;
		$query .= ' ORDER BY TypeEvent_Code ASC, TypeEvent_Name ASC LIMIT %d OFFSET %d';

		$list_params   = $params;
		$list_params[] = $per_page;
		$list_params[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$items = $wpdb->get_results( $wpdb->prepare( $query, $list_params ), ARRAY_A );

		wp_send_json_success(
			[
				'items'       => is_array( $items ) ? $items : [],
				'total'       => $total,
				'page'        => $page,
				'per_page'    => $per_page,
				'total_pages' => max( 1, (int) ceil( $total / $per_page ) ),
			]
		);
	}

	/**
	 * Повертає один запис довідника.
	 */
	public function handle_get_single(): void {
		check_ajax_referer( TypeEvent_List::NONCE_ACTION, 'nonce' );

		$typeevent_id = absint( $_POST['typeevent_id'] ?? 0 );
		if ( ! $typeevent_id ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ID виду змагань.', 'fstu' ) ] );
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$item = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT TypeEvent_ID, TypeEvent_Name, TypeEvent_Code FROM S_TypeEvent WHERE TypeEvent_ID = %d',
				$typeevent_id
			),
			ARRAY_A
		);

		if ( ! $item ) {
			wp_send_json_error( [ 'message' => __( 'Вид змагань не знайдено.', 'fstu' ) ] );
		}

		wp_send_json_success( [ 'item' => $item ] );
	}

	/**
	 * Зберігає запис довідника.
	 */
	public function handle_save(): void {
		check_ajax_referer( TypeEvent_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage_typeevent() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для збереження виду змагань.', 'fstu' ) ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => __( 'Помилка при обробці форми.', 'fstu' ) ] );
		}

		$typeevent_id = absint( $_POST['typeevent_id'] ?? 0 );
		$data         = $this->get_sanitized_payload();
		$this->validate_payload( $data );

		global $wpdb;

		if ( $typeevent_id > 0 ) {
			$updated = $wpdb->update(
				'S_TypeEvent',
				[
					'TypeEvent_Name' => $data['typeevent_name'],
					'TypeEvent_Code' => $data['typeevent_code'],
				],
				[ 'TypeEvent_ID' => $typeevent_id ],
				[ '%s', '%d' ],
				[ '%d' ]
			);

			if ( false === $updated ) {
				wp_send_json_error( [ 'message' => __( 'Помилка при редагуванні виду змагань.', 'fstu' ) ] );
			}

			wp_send_json_success( [ 'message' => __( 'Вид змагань успішно відредагований.', 'fstu' ) ] );
		}

		$inserted = $wpdb->insert(
			'S_TypeEvent',
			[
				'TypeEvent_Name' => $data['typeevent_name'],
				'TypeEvent_Code' => $data['typeevent_code'],
			],
			[ '%s', '%d' ]
		);

		if ( ! $inserted ) {
			wp_send_json_error( [ 'message' => __( 'Помилка при додаванні виду змагань.', 'fstu' ) ] );
		}

		wp_send_json_success(
			[
				'message'      => __( 'Вид змагань успішно додано.', 'fstu' ),
				'typeevent_id' => $wpdb->insert_id,
			]
		);
	}

	/**
	 * Видаляє запис довідника.
	 */
	public function handle_delete(): void {
		check_ajax_referer( TypeEvent_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_delete_typeevent() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для видалення виду змагань.', 'fstu' ) ] );
		}

		$typeevent_id = absint( $_POST['typeevent_id'] ?? 0 );
		if ( ! $typeevent_id ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ID виду змагань.', 'fstu' ) ] );
		}

		global $wpdb;

		$deleted = $wpdb->delete( 'S_TypeEvent', [ 'TypeEvent_ID' => $typeevent_id ], [ '%d' ] );
		if ( ! $deleted ) {
			wp_send_json_error( [ 'message' => __( 'Помилка при видаленні виду змагань.', 'fstu' ) ] );
		}

		wp_send_json_success( [ 'message' => __( 'Вид змагань успішно видалений.', 'fstu' ) ] );
	}

	/**
	 * Повертає протокол (журнал) операцій довідника TypeEvent.
	 * Колонки: Дата, Тип, Операція, Повідомлення, Статус, Користувач.
	 * Дані отримуються з таблиці Logs.
	 */
	public function handle_get_protocol(): void {
		check_ajax_referer( TypeEvent_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_delete_typeevent() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду протоколу.', 'fstu' ) ] );
		}

		global $wpdb;

		$page     = absint( $_POST['page'] ?? 1 );
		$per_page = absint( $_POST['per_page'] ?? 10 );
		$filter_name = sanitize_text_field( wp_unslash( $_POST['filter_name'] ?? '' ) );

		if ( $page < 1 ) {
			$page = 1;
		}

		if ( ! in_array( $per_page, self::PROTOCOL_PER_PAGE_ALLOWED, true ) ) {
			$per_page = 10;
		}

		$filter_name = substr( $filter_name, 0, self::MAX_SEARCH_LENGTH );

		// Будуємо запит до таблиці Logs для логів операцій модуля TypeEvent.
		$where  = " WHERE l.Logs_Name = 'TypeEvent'";
		$params = [];

		if ( '' !== $filter_name ) {
			$where .= ' AND (l.Logs_Text LIKE %s OR u.FIO LIKE %s)';
			$like = '%' . $wpdb->esc_like( $filter_name ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		$count_sql = "SELECT COUNT(*) FROM Logs l 
			LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
			{$where}";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) );

		$offset = ( $page - 1 ) * $per_page;
		$list_sql = "SELECT l.Logs_DateCreate, l.Logs_Type, l.Logs_Name, l.Logs_Text, l.Logs_Error, u.FIO 
			FROM Logs l 
			LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
			{$where}
			ORDER BY l.Logs_DateCreate DESC 
			LIMIT %d OFFSET %d";

		$list_params = $params;
		$list_params[] = $per_page;
		$list_params[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$items = $wpdb->get_results( $wpdb->prepare( $list_sql, $list_params ), ARRAY_A );
		$total_pages = max( 1, (int) ceil( $total / $per_page ) );

		wp_send_json_success(
			[
				'items'       => is_array( $items ) ? $items : [],
				'generated'   => current_time( 'mysql' ),
				'itemsCount'  => is_array( $items ) ? count( $items ) : 0,
				'total'       => $total,
				'page'        => $page,
				'per_page'    => $per_page,
				'total_pages' => $total_pages,
			]
		);
	}

	/**
	 * Перевіряє права на керування довідником.
	 */
	private function current_user_can_manage_typeevent(): bool {
		$user  = wp_get_current_user();
		$roles = is_array( $user->roles ) ? $user->roles : [];

		return current_user_can( 'manage_options' ) || in_array( 'administrator', $roles, true ) || in_array( 'userregistrar', $roles, true );
	}

	/**
	 * Перевіряє права на видалення.
	 */
	private function current_user_can_delete_typeevent(): bool {
		$user  = wp_get_current_user();
		$roles = is_array( $user->roles ) ? $user->roles : [];

		return current_user_can( 'manage_options' ) || in_array( 'administrator', $roles, true );
	}

	/**
	 * Перевіряє honeypot поле.
	 */
	private function is_honeypot_triggered(): bool {
		$honeypot = sanitize_text_field( wp_unslash( $_POST['fstu_website'] ?? '' ) );
		return '' !== $honeypot;
	}

	/**
	 * Повертає санітизовані дані форми.
	 *
	 * @return array<string,mixed>
	 */
	private function get_sanitized_payload(): array {
		return [
			'typeevent_name' => sanitize_text_field( wp_unslash( $_POST['typeevent_name'] ?? '' ) ),
			'typeevent_code' => absint( $_POST['typeevent_code'] ?? 0 ),
		];
	}

	/**
	 * Валідує дані форми.
	 *
	 * @param array<string,mixed> $data Дані форми.
	 */
	private function validate_payload( array $data ): void {
		if ( '' === $data['typeevent_name'] ) {
			wp_send_json_error( [ 'message' => __( 'Заповніть найменування виду змагань.', 'fstu' ) ] );
		}

		if ( $data['typeevent_code'] < 0 ) {
			wp_send_json_error( [ 'message' => __( 'Сортування не може бути від’ємним.', 'fstu' ) ] );
		}
	}
}

