<?php
/**
 * AJAX-обробники модуля "Довідник міст".
 *
 * Version:     1.1.0
 * Date_update: 2026-04-07
 *
 * @package FSTU\Dictionaries\City
 */

namespace FSTU\Dictionaries\City;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class City_Ajax {

	private const DEFAULT_PER_PAGE      = 10;
	private const MAX_PER_PAGE          = 50;
	private const MAX_PROTOCOL_PER_PAGE = 50;
	private const MAX_SEARCH_LENGTH     = 100;
	private const LOG_NAME              = 'City';
	private const LOG_TYPE_INSERT       = 'I';
	private const LOG_TYPE_UPDATE       = 'U';
	private const LOG_TYPE_DELETE       = 'D';
	private const LOG_STATUS_SUCCESS    = 'успішно';

	/**
	 * Реєструє AJAX-обробники.
	 */
	public function init(): void {
		add_action( 'wp_ajax_fstu_city_get_list', [ $this, 'handle_get_list' ] );
		add_action( 'wp_ajax_fstu_city_get_regions', [ $this, 'handle_get_regions' ] );
		add_action( 'wp_ajax_fstu_city_get_single', [ $this, 'handle_get_single' ] );
		add_action( 'wp_ajax_fstu_city_create', [ $this, 'handle_create' ] );
		add_action( 'wp_ajax_fstu_city_update', [ $this, 'handle_update' ] );
		add_action( 'wp_ajax_fstu_city_delete', [ $this, 'handle_delete' ] );
		add_action( 'wp_ajax_fstu_city_get_protocol', [ $this, 'handle_get_protocol' ] );
	}

	/**
	 * Повертає список областей для фільтра/форми.
	 */
	public function handle_get_regions(): void {
		check_ajax_referer( City_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду областей.', 'fstu' ) ] );
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$items = $wpdb->get_results(
			'SELECT Region_ID, Region_Name FROM S_Region ORDER BY Region_Name ASC',
			ARRAY_A
		);

		wp_send_json_success(
			[
				'items' => is_array( $items ) ? $items : [],
			]
		);
	}

	/**
	 * Повертає список міст.
	 */
	public function handle_get_list(): void {
		check_ajax_referer( City_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду довідника.', 'fstu' ) ] );
		}

		$search    = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$search    = mb_substr( $search, 0, self::MAX_SEARCH_LENGTH );
		$region_id = absint( $_POST['region_id'] ?? 0 );
		$page      = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page  = min( max( 1, absint( $_POST['per_page'] ?? self::DEFAULT_PER_PAGE ) ), self::MAX_PER_PAGE );
		$offset    = ( $page - 1 ) * $per_page;

		global $wpdb;

		$where  = '1=1';
		$params = [];

		if ( $region_id > 0 ) {
			$where   .= ' AND Region_ID = %d';
			$params[] = $region_id;
		}

		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where   .= ' AND (City_Name LIKE %s OR Region_Name LIKE %s)';
			$params[] = $like;
			$params[] = $like;
		}

		$count_sql = "SELECT COUNT(*) FROM vCity WHERE {$where}";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$total = (int) ( $params ? $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) ) : $wpdb->get_var( $count_sql ) );

		$list_sql = "SELECT City_ID, City_Name, City_NameEng, City_Order, Region_ID, Region_Name
			FROM vCity
			WHERE {$where}
			ORDER BY Region_Name ASC, City_Name ASC
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
		check_ajax_referer( City_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду запису.', 'fstu' ) ] );
		}

		$city_id = absint( $_POST['city_id'] ?? 0 );
		if ( $city_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		$item = $this->get_city_by_id( $city_id );
		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		wp_send_json_success(
			[
				'item' => [
					'city_id'           => (int) $item['City_ID'],
					'region_id'         => (int) $item['Region_ID'],
					'city_name'         => (string) $item['City_Name'],
					'city_name_eng'     => (string) ( $item['City_NameEng'] ?? '' ),
					'city_order'        => (int) ( $item['City_Order'] ?? 0 ),
					'city_date_create'  => (string) ( $item['City_DateCreate'] ?? '' ),
				],
			]
		);
	}

	/**
	 * Створює запис міста.
	 */
	public function handle_create(): void {
		check_ajax_referer( City_List::NONCE_ACTION, 'nonce' );

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

		$order = $data['city_order'];
		if ( null === $order ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$order = (int) $wpdb->get_var( 'SELECT COALESCE(MAX(City_Order), 0) FROM S_City' ) + 1;
		}

		try {
			$this->begin_transaction();

			$inserted = $wpdb->insert(
				'S_City',
				[
					'City_DateCreate' => current_time( 'mysql' ),
					'Region_ID'       => (int) $data['region_id'],
					'City_Name'       => $data['city_name'],
					'City_NameEng'    => $data['city_name_eng'],
					'City_Order'      => $order,
				],
				[ '%s', '%d', '%s', '%s', '%d' ]
			);

			if ( false === $inserted ) {
				throw new \RuntimeException( 'city_insert_failed' );
			}

			$this->log_action( self::LOG_TYPE_INSERT, sprintf( 'Додано нове місто: %s', $data['city_name'] ), self::LOG_STATUS_SUCCESS );
			$this->commit_transaction();

			wp_send_json_success( [ 'message' => __( 'Місто успішно додано.', 'fstu' ) ] );
		} catch ( \Throwable $exception ) {
			$this->rollback_transaction();
			$this->try_log_action(
				self::LOG_TYPE_INSERT,
				sprintf( 'Помилка додавання міста: %s', $data['city_name'] ),
				'error'
			);

			wp_send_json_error( [ 'message' => __( 'Помилка при збереженні запису.', 'fstu' ) ] );
		}
	}

	/**
	 * Оновлює запис міста.
	 */
	public function handle_update(): void {
		check_ajax_referer( City_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для редагування запису.', 'fstu' ) ] );
		}

		if ( ! $this->validate_honeypot() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		$city_id        = absint( $_POST['city_id'] ?? 0 );
		$data           = $this->sanitize_form_data();
		$error_message  = $this->validate_form_data( $data, $city_id );

		if ( $city_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		if ( '' !== $error_message ) {
			wp_send_json_error( [ 'message' => $error_message ] );
		}

		$item = $this->get_city_by_id( $city_id );
		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		global $wpdb;

		$order = null === $data['city_order']
			? (int) ( $item['City_Order'] ?? 0 )
			: $data['city_order'];

		try {
			$this->begin_transaction();

			$updated = $wpdb->update(
				'S_City',
				[
					'Region_ID'    => (int) $data['region_id'],
					'City_Name'    => $data['city_name'],
					'City_NameEng' => $data['city_name_eng'],
					'City_Order'   => $order,
				],
				[ 'City_ID' => $city_id ],
				[ '%d', '%s', '%s', '%d' ],
				[ '%d' ]
			);

			if ( false === $updated ) {
				throw new \RuntimeException( 'city_update_failed' );
			}

			$this->log_action( self::LOG_TYPE_UPDATE, sprintf( 'Оновлено місто: %s', $data['city_name'] ), self::LOG_STATUS_SUCCESS );
			$this->commit_transaction();

			wp_send_json_success( [ 'message' => __( 'Місто успішно оновлено.', 'fstu' ) ] );
		} catch ( \Throwable $exception ) {
			$this->rollback_transaction();
			$this->try_log_action(
				self::LOG_TYPE_UPDATE,
				sprintf( 'Помилка оновлення міста: %s', (string) $item['City_Name'] ),
				'error'
			);

			wp_send_json_error( [ 'message' => __( 'Помилка при збереженні запису.', 'fstu' ) ] );
		}
	}

	/**
	 * Видаляє запис міста.
	 */
	public function handle_delete(): void {
		check_ajax_referer( City_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_delete() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для видалення запису.', 'fstu' ) ] );
		}

		$city_id = absint( $_POST['city_id'] ?? 0 );
		if ( $city_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		$item = $this->get_city_by_id( $city_id );
		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		global $wpdb;

		try {
			$this->begin_transaction();

			$deleted = $wpdb->delete( 'S_City', [ 'City_ID' => $city_id ], [ '%d' ] );

			if ( false === $deleted || 1 !== (int) $deleted ) {
				throw new \RuntimeException( 'city_delete_failed' );
			}

			$this->log_action( self::LOG_TYPE_DELETE, sprintf( 'Видалено місто: %s', (string) $item['City_Name'] ), self::LOG_STATUS_SUCCESS );
			$this->commit_transaction();

			wp_send_json_success( [ 'message' => __( 'Місто успішно видалено.', 'fstu' ) ] );
		} catch ( \Throwable $exception ) {
			$this->rollback_transaction();
			$this->try_log_action(
				self::LOG_TYPE_DELETE,
				sprintf( 'Помилка видалення міста: %s', (string) $item['City_Name'] ),
				'error'
			);

			wp_send_json_error( [ 'message' => __( 'Не вдалося видалити запис.', 'fstu' ) ] );
		}
	}

	/**
	 * Повертає протокол модуля.
	 */
	public function handle_get_protocol(): void {
		check_ajax_referer( City_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_protocol() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду протоколу.', 'fstu' ) ] );
		}

		$search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$search   = mb_substr( $search, 0, self::MAX_SEARCH_LENGTH );
		$page     = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page = min( max( 1, absint( $_POST['per_page'] ?? self::DEFAULT_PER_PAGE ) ), self::MAX_PROTOCOL_PER_PAGE );
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

		$list_sql = "SELECT l.User_ID, l.Logs_DateCreate, l.Logs_Type, l.Logs_Name, l.Logs_Text, l.Logs_Error, u.FIO
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
	 * @param array<int,array<string,mixed>> $items       Рядки таблиці.
	 * @param int                            $offset      Зсув пагінації.
	 * @param array<string,bool>             $permissions Права поточного користувача.
	 */
	private function build_rows( array $items, int $offset, array $permissions ): string {
		if ( empty( $items ) ) {
			return '<tr class="fstu-row"><td colspan="5" class="fstu-no-results">' . esc_html__( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ) . '</td></tr>';
		}

		$html  = '';
		$index = $offset;

		foreach ( $items as $item ) {
			++$index;

			$city_id       = (int) ( $item['City_ID'] ?? 0 );
			$region_name   = (string) ( $item['Region_Name'] ?? '' );
			$city_name     = (string) ( $item['City_Name'] ?? '' );
			$city_name_eng = (string) ( $item['City_NameEng'] ?? '' );

			$actions   = [];
			$actions[] = '<button type="button" class="fstu-city-dropdown__item fstu-city-view-btn" data-city-id="' . esc_attr( (string) $city_id ) . '">' . esc_html__( 'Перегляд', 'fstu' ) . '</button>';

			if ( ! empty( $permissions['canManage'] ) ) {
				$actions[] = '<button type="button" class="fstu-city-dropdown__item fstu-city-edit-btn" data-city-id="' . esc_attr( (string) $city_id ) . '">' . esc_html__( 'Редагування', 'fstu' ) . '</button>';
			}

			if ( ! empty( $permissions['canDelete'] ) ) {
				$actions[] = '<button type="button" class="fstu-city-dropdown__item fstu-city-dropdown__item--danger fstu-city-delete-btn" data-city-id="' . esc_attr( (string) $city_id ) . '">' . esc_html__( 'Видалення', 'fstu' ) . '</button>';
			}

			$html .= '<tr class="fstu-row">';
			$html .= '<td class="fstu-td fstu-td--num">' . esc_html( (string) $index ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--region">' . ( '' !== $region_name ? esc_html( $region_name ) : '<span class="fstu-text-muted">—</span>' ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--name"><button type="button" class="fstu-city-link-button fstu-city-view-btn" data-city-id="' . esc_attr( (string) $city_id ) . '">' . esc_html( $city_name ) . '</button></td>';
			$html .= '<td class="fstu-td fstu-td--name-eng">' . ( '' !== $city_name_eng ? esc_html( $city_name_eng ) : '<span class="fstu-text-muted">—</span>' ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--actions">';
			$html .= '<div class="fstu-city-dropdown">';
			$html .= '<button type="button" class="fstu-city-dropdown__toggle" aria-expanded="false" title="' . esc_attr__( 'Меню дій', 'fstu' ) . '">▼</button>';
			$html .= '<div class="fstu-city-dropdown__menu">' . implode( '', $actions ) . '</div>';
			$html .= '</div>';
			$html .= '</td>';
			$html .= '</tr>';
		}

		return $html;
	}

	/**
	 * Будує HTML рядків протоколу.
	 *
	 * @param array<int,array<string,mixed>> $items Рядки протоколу.
	 */
	private function build_protocol_rows( array $items ): string {
		if ( empty( $items ) ) {
			return '<tr class="fstu-row"><td colspan="6" class="fstu-no-results">' . esc_html__( 'Записи протоколу відсутні.', 'fstu' ) . '</td></tr>';
		}

		$html = '';

		foreach ( $items as $item ) {
			$html .= '<tr class="fstu-row">';
			$html .= '<td class="fstu-td fstu-td--date">' . esc_html( (string) ( $item['Logs_DateCreate'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--type">' . esc_html( $this->get_log_type_label( (string) ( $item['Logs_Type'] ?? '' ) ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--operation">' . esc_html( (string) ( $item['Logs_Name'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--message">' . esc_html( (string) ( $item['Logs_Text'] ?? '' ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--status">' . esc_html( $this->get_log_status_label( (string) ( $item['Logs_Error'] ?? '' ) ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--user">' . esc_html( $this->get_log_user_label( $item ) ) . '</td>';
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
		$order_raw = sanitize_text_field( wp_unslash( $_POST['city_order'] ?? '' ) );

		return [
			'region_id'        => absint( $_POST['region_id'] ?? 0 ),
			'city_name'        => $this->normalize_form_string( sanitize_text_field( wp_unslash( $_POST['city_name'] ?? '' ) ) ),
			'city_name_eng'    => $this->normalize_form_string( sanitize_text_field( wp_unslash( $_POST['city_name_eng'] ?? '' ) ) ),
			'city_order_raw'   => $order_raw,
			'city_order'       => '' === $order_raw ? null : absint( $order_raw ),
		];
	}

	/**
	 * Валідує форму.
	 *
	 * @param array<string,mixed> $data    Дані форми.
	 * @param int                 $city_id Поточний ID для update.
	 */
	private function validate_form_data( array $data, int $city_id = 0 ): string {
		$region_id = (int) ( $data['region_id'] ?? 0 );
		$name      = (string) ( $data['city_name'] ?? '' );
		$name_eng  = (string) ( $data['city_name_eng'] ?? '' );
		$order_raw = (string) ( $data['city_order_raw'] ?? '' );

		if ( $region_id <= 0 ) {
			return __( 'Поле «Область» є обов’язковим.', 'fstu' );
		}

		if ( mb_strlen( $name ) < 2 ) {
			return __( 'Поле «Найменування» є обов’язковим.', 'fstu' );
		}

		if ( mb_strlen( $name ) > 255 ) {
			return __( 'Поле «Найменування» не може бути довшим за 255 символів.', 'fstu' );
		}

		if ( '' !== $name_eng && mb_strlen( $name_eng ) > 255 ) {
			return __( 'Поле «Англійською» не може бути довшим за 255 символів.', 'fstu' );
		}

		if ( '' !== $order_raw && ! preg_match( '/^\d+$/', $order_raw ) ) {
			return __( 'Поле «Сортування» повинно містити лише невід’ємне число.', 'fstu' );
		}

		if ( $this->city_name_exists( $region_id, $name, $city_id ) ) {
			return __( 'Запис з такою назвою міста для цієї області уже існує.', 'fstu' );
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
		return Capabilities::get_city_permissions();
	}

	/**
	 * Чи може користувач переглядати модуль.
	 */
	private function current_user_can_view(): bool {
		return Capabilities::current_user_can_view_city();
	}

	/**
	 * Чи може користувач керувати модулем.
	 */
	private function current_user_can_manage(): bool {
		return Capabilities::current_user_can_manage_city();
	}

	/**
	 * Чи може користувач видаляти записи.
	 */
	private function current_user_can_delete(): bool {
		return Capabilities::current_user_can_delete_city();
	}

	/**
	 * Чи може користувач переглядати протокол.
	 */
	private function current_user_can_protocol(): bool {
		return Capabilities::current_user_can_view_city_protocol();
	}

	/**
	 * Повертає запис довідника за ID.
	 *
	 * @return array<string,mixed>|null
	 */
	private function get_city_by_id( int $city_id ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$item = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT City_ID, City_DateCreate, Region_ID, City_Name, City_NameEng, City_Order FROM S_City WHERE City_ID = %d LIMIT 1',
				$city_id
			),
			ARRAY_A
		);

		return is_array( $item ) ? $item : null;
	}

	/**
	 * Перевіряє, чи існує запис з такою назвою міста в межах області.
	 */
	private function city_name_exists( int $region_id, string $city_name, int $exclude_id = 0 ): bool {
		global $wpdb;

		$needle = $this->normalize_compare_string( $city_name );
		if ( '' === $needle ) {
			return false;
		}

		$sql = 'SELECT City_ID, City_Name FROM S_City WHERE Region_ID = %d';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $region_id ), ARRAY_A );

		if ( ! is_array( $rows ) ) {
			return false;
		}

		foreach ( $rows as $row ) {
			$current_id = (int) ( $row['City_ID'] ?? 0 );
			if ( $exclude_id > 0 && $exclude_id === $current_id ) {
				continue;
			}

			$current_name = $this->normalize_compare_string( (string) ( $row['City_Name'] ?? '' ) );
			if ( '' !== $current_name && $current_name === $needle ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Нормалізує рядок для duplicate-check.
	 */
	private function normalize_compare_string( string $value ): string {
		$value = $this->normalize_form_string( $value );
		return mb_strtolower( $value );
	}

	/**
	 * Нормалізує рядок форми без зміни початкового регістру символів.
	 */
	private function normalize_form_string( string $value ): string {
		return trim( preg_replace( '/\s+/u', ' ', $value ) ?? $value );
	}

	/**
	 * Повертає читабельний підпис типу логування для UI протоколу.
	 */
	private function get_log_type_label( string $type ): string {
		return match ( strtoupper( $type ) ) {
			self::LOG_TYPE_INSERT, 'INSERT' => 'INSERT',
			self::LOG_TYPE_UPDATE, 'UPDATE' => 'UPDATE',
			self::LOG_TYPE_DELETE, 'DELETE' => 'DELETE',
			default => $type,
		};
	}

	/**
	 * Повертає читабельний підпис статусу логування для UI протоколу.
	 */
	private function get_log_status_label( string $status ): string {
		return match ( trim( mb_strtolower( $status ) ) ) {
			'✓', 'успішно', 'success' => self::LOG_STATUS_SUCCESS,
			default => $status,
		};
	}

	/**
	 * Повертає підпис користувача для рядка протоколу з fallback, якщо FIO порожнє.
	 *
	 * @param array<string,mixed> $item Рядок протоколу.
	 */
	private function get_log_user_label( array $item ): string {
		$fio = trim( (string) ( $item['FIO'] ?? '' ) );

		if ( '' !== $fio ) {
			return $fio;
		}

		$user_id = (int) ( $item['User_ID'] ?? 0 );
		if ( $user_id <= 0 ) {
			return __( 'Система', 'fstu' );
		}

		$user = get_userdata( $user_id );
		if ( $user ) {
			$display_name = trim( (string) $user->display_name );
			if ( '' !== $display_name ) {
				return $display_name;
			}

			$user_login = trim( (string) $user->user_login );
			if ( '' !== $user_login ) {
				return $user_login;
			}
		}

		return sprintf( 'ID:%d', $user_id );
	}

	/**
	 * Записує подію у таблицю Logs.
	 */
	private function log_action( string $type, string $text, string $status ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$inserted = $wpdb->insert(
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

		if ( false === $inserted ) {
			throw new \RuntimeException( 'city_log_insert_failed' );
		}
	}

	/**
	 * Пише лог поза транзакційним успішним сценарієм без викидання винятку назовні.
	 */
	private function try_log_action( string $type, string $text, string $status ): void {
		try {
			$this->log_action( $type, $text, $status );
		} catch ( \Throwable $exception ) {
			// Не виводимо технічні деталі на фронтенд.
		}
	}

	/**
	 * Починає транзакцію для atomic CRUD + Logs flow.
	 */
	private function begin_transaction(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->query( 'START TRANSACTION' );

		if ( false === $result ) {
			throw new \RuntimeException( 'city_transaction_start_failed' );
		}
	}

	/**
	 * Підтверджує транзакцію.
	 */
	private function commit_transaction(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->query( 'COMMIT' );

		if ( false === $result ) {
			throw new \RuntimeException( 'city_transaction_commit_failed' );
		}
	}

	/**
	 * Скасовує транзакцію.
	 */
	private function rollback_transaction(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'ROLLBACK' );
	}
}

