<?php
namespace FSTU\Dictionaries\SportsCategories;

/**
 * AJAX-обробники модуля "Довідник спортивних розрядів".
 *
 * Version:     1.0.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Dictionaries\SportsCategories
 */

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SportsCategories_Ajax {

	private const MAX_PER_PAGE          = 50;
	private const MAX_PROTOCOL_PER_PAGE = 50;
	private const MAX_SEARCH_LENGTH     = 100;
	private const LOG_NAME              = 'SportsCategories';

	/**
	 * Реєструє AJAX-обробники модуля.
	 */
	public function init(): void {
		add_action( 'wp_ajax_fstu_sportscategories_get_list', [ $this, 'handle_get_list' ] );
		add_action( 'wp_ajax_nopriv_fstu_sportscategories_get_list', [ $this, 'handle_get_list' ] );
		add_action( 'wp_ajax_fstu_sportscategories_get_single', [ $this, 'handle_get_single' ] );
		add_action( 'wp_ajax_nopriv_fstu_sportscategories_get_single', [ $this, 'handle_get_single' ] );
		add_action( 'wp_ajax_fstu_sportscategories_create', [ $this, 'handle_create' ] );
		add_action( 'wp_ajax_fstu_sportscategories_update', [ $this, 'handle_update' ] );
		add_action( 'wp_ajax_fstu_sportscategories_delete', [ $this, 'handle_delete' ] );
		add_action( 'wp_ajax_fstu_sportscategories_get_protocol', [ $this, 'handle_get_protocol' ] );
	}

	/**
	 * Повертає список спортивних розрядів.
	 */
	public function handle_get_list(): void {
		check_ajax_referer( SportsCategories_List::NONCE_ACTION, 'nonce' );

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
			$where   .= ' AND SportsCategories_Name LIKE %s';
			$params[] = $like;
		}

		$count_sql = "SELECT COUNT(*) FROM S_SportsCategories {$where}";
		$total     = (int) ( ! empty( $params )
			? $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) )
			: $wpdb->get_var( $count_sql ) );

		$data_sql = "SELECT SportsCategories_ID,
				SportsCategories_DateCreate,
				SportsCategories_Name,
				SportsCategories_Order,
				SportsCategories_Ball
			FROM S_SportsCategories
			{$where}
			ORDER BY SportsCategories_Order ASC, SportsCategories_Name ASC, SportsCategories_ID ASC
			LIMIT %d OFFSET %d";

		$data_params = array_merge( $params, [ $per_page, $offset ] );
		$items       = $wpdb->get_results( $wpdb->prepare( $data_sql, ...$data_params ), ARRAY_A );

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
	 * Повертає один запис довідника.
	 */
	public function handle_get_single(): void {
		check_ajax_referer( SportsCategories_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду запису.', 'fstu' ) ] );
		}

		$sportscategory_id = absint( $_POST['sportscategory_id'] ?? 0 );

		if ( $sportscategory_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		$item = $this->get_item_by_id( $sportscategory_id );

		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		wp_send_json_success(
			[
				'sportscategory_id'         => (int) ( $item['SportsCategories_ID'] ?? 0 ),
				'sportscategory_datecreate' => (string) ( $item['SportsCategories_DateCreate'] ?? '' ),
				'sportscategory_name'       => (string) ( $item['SportsCategories_Name'] ?? '' ),
				'sportscategory_order'      => (int) ( $item['SportsCategories_Order'] ?? 0 ),
				'sportscategory_ball'       => (float) ( $item['SportsCategories_Ball'] ?? 0 ),
			]
		);
	}

	/**
	 * Створює запис довідника.
	 */
	public function handle_create(): void {
		check_ajax_referer( SportsCategories_List::NONCE_ACTION, 'nonce' );

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
			'SportsCategories_DateCreate' => current_time( 'mysql' ),
			'SportsCategories_Name'       => $data['sportscategory_name'],
			'SportsCategories_Order'      => $data['sportscategory_order'] > 0 ? $data['sportscategory_order'] : $this->get_next_order(),
		];

		$wpdb->query( 'START TRANSACTION' );
		$result = $wpdb->insert( 'S_SportsCategories', $insert_data, [ '%s', '%s', '%d' ] );

		if ( false === $result ) {
			$wpdb->query( 'ROLLBACK' );
			$this->log_action_best_effort( 'I', __( 'Помилка додавання спортивного розряду.', 'fstu' ), 'error' );
			wp_send_json_error( [ 'message' => __( 'Помилка при збереженні запису.', 'fstu' ) ] );
		}

		if ( ! $this->log_action_transactional( 'I', sprintf( 'Додано спортивний розряд: %s', (string) $data['sportscategory_name'] ), '✓' ) ) {
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
		check_ajax_referer( SportsCategories_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для редагування запису.', 'fstu' ) ] );
		}

		if ( ! $this->validate_honeypot() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		$sportscategory_id = absint( $_POST['sportscategory_id'] ?? 0 );
		$data             = $this->sanitize_form_data();
		$error_message    = $this->validate_form_data( $data, $sportscategory_id );

		if ( $sportscategory_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		if ( '' !== $error_message ) {
			wp_send_json_error( [ 'message' => $error_message ] );
		}

		$current_item = $this->get_item_by_id( $sportscategory_id );
		if ( ! is_array( $current_item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		$update_data = [
			'SportsCategories_Name'  => $data['sportscategory_name'],
			'SportsCategories_Order' => $data['sportscategory_order'] > 0 ? $data['sportscategory_order'] : (int) ( $current_item['SportsCategories_Order'] ?? 0 ),
		];

		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );

		$result = $wpdb->update(
			'S_SportsCategories',
			$update_data,
			[ 'SportsCategories_ID' => $sportscategory_id ],
			[ '%s', '%d' ],
			[ '%d' ]
		);

		if ( false === $result ) {
			$wpdb->query( 'ROLLBACK' );
			$this->log_action_best_effort( 'U', __( 'Помилка оновлення спортивного розряду.', 'fstu' ), 'error' );
			wp_send_json_error( [ 'message' => __( 'Помилка при збереженні запису.', 'fstu' ) ] );
		}

		if ( ! $this->log_action_transactional( 'U', sprintf( 'Оновлено спортивний розряд: %s', (string) $data['sportscategory_name'] ), '✓' ) ) {
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
		check_ajax_referer( SportsCategories_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_delete() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для видалення запису.', 'fstu' ) ] );
		}

		$sportscategory_id = absint( $_POST['sportscategory_id'] ?? 0 );

		if ( $sportscategory_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		$item = $this->get_item_by_id( $sportscategory_id );
		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		if ( $this->has_delete_dependencies( $sportscategory_id ) ) {
			$this->log_action_best_effort(
				'D',
				sprintf( 'Заблоковано видалення спортивного розряду: %s', (string) ( $item['SportsCategories_Name'] ?? '' ) ),
				'dependency'
			);

			wp_send_json_error( [ 'message' => __( 'Не вдалося видалити запис, оскільки цей спортивний розряд уже використовується в профілях користувачів.', 'fstu' ) ] );
		}

		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );
		$result = $wpdb->delete( 'S_SportsCategories', [ 'SportsCategories_ID' => $sportscategory_id ], [ '%d' ] );

		if ( false === $result ) {
			$wpdb->query( 'ROLLBACK' );
			$this->log_action_best_effort( 'D', __( 'Помилка видалення спортивного розряду.', 'fstu' ), 'error' );
			wp_send_json_error( [ 'message' => __( 'Не вдалося видалити запис.', 'fstu' ) ] );
		}

		if ( ! $this->log_action_transactional( 'D', sprintf( 'Видалено спортивний розряд: %s', (string) ( $item['SportsCategories_Name'] ?? '' ) ), '✓' ) ) {
			$wpdb->query( 'ROLLBACK' );
			wp_send_json_error( [ 'message' => __( 'Не вдалося видалити запис.', 'fstu' ) ] );
		}

		$wpdb->query( 'COMMIT' );

		wp_send_json_success( [ 'message' => __( 'Запис успішно видалено.', 'fstu' ) ] );
	}

	/**
	 * Повертає протокол модуля.
	 */
	public function handle_get_protocol(): void {
		check_ajax_referer( SportsCategories_List::NONCE_ACTION, 'nonce' );

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
	private function build_rows( array $items, int $offset, array $permissions ): string {
		if ( empty( $items ) ) {
			return '<tr class="fstu-row"><td colspan="3" class="fstu-no-results">' . esc_html__( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ) . '</td></tr>';
		}

		$html       = '';
		$index      = $offset;
		$can_manage = ! empty( $permissions['canManage'] );
		$can_delete = ! empty( $permissions['canDelete'] );

		foreach ( $items as $item ) {
			++$index;
			$sportscategory_id = (int) ( $item['SportsCategories_ID'] ?? 0 );
			$actions           = [];
			$actions[]         = '<button type="button" class="fstu-sportscategories-dropdown__item fstu-sportscategories-view-btn" data-sportscategory-id="' . esc_attr( (string) $sportscategory_id ) . '">' . esc_html__( 'Перегляд', 'fstu' ) . '</button>';

			if ( $can_manage ) {
				$actions[] = '<button type="button" class="fstu-sportscategories-dropdown__item fstu-sportscategories-edit-btn" data-sportscategory-id="' . esc_attr( (string) $sportscategory_id ) . '">' . esc_html__( 'Редагування', 'fstu' ) . '</button>';
			}

			if ( $can_delete ) {
				$actions[] = '<button type="button" class="fstu-sportscategories-dropdown__item fstu-sportscategories-dropdown__item--danger fstu-sportscategories-delete-btn" data-sportscategory-id="' . esc_attr( (string) $sportscategory_id ) . '">' . esc_html__( 'Видалення', 'fstu' ) . '</button>';
			}

			$html .= '<tr class="fstu-row fstu-sportscategories-row" data-sportscategory-id="' . esc_attr( (string) $sportscategory_id ) . '">';
			$html .= '<td class="fstu-td fstu-td--num"><span class="fstu-row-number">' . esc_html( (string) $index ) . '</span></td>';
			$html .= '<td class="fstu-td fstu-td--name"><button type="button" class="fstu-sportscategories-link-button fstu-sportscategories-view-btn" data-sportscategory-id="' . esc_attr( (string) $sportscategory_id ) . '">' . esc_html( (string) ( $item['SportsCategories_Name'] ?? '' ) ) . '</button></td>';
			$html .= '<td class="fstu-td fstu-td--actions"><div class="fstu-sportscategories-dropdown"><button type="button" class="fstu-sportscategories-dropdown__toggle" aria-expanded="false" title="' . esc_attr__( 'Меню дій', 'fstu' ) . '">▼</button><div class="fstu-sportscategories-dropdown__menu">' . implode( '', $actions ) . '</div></div></td>';
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
			'sportscategory_name'  => sanitize_text_field( wp_unslash( $_POST['sportscategory_name'] ?? '' ) ),
			'sportscategory_order' => absint( $_POST['sportscategory_order'] ?? 0 ),
		];
	}

	/**
	 * @param array<string,int|string> $data
	 */
	private function validate_form_data( array $data, int $exclude_id = 0 ): string {
		$name  = trim( (string) ( $data['sportscategory_name'] ?? '' ) );
		$order = (int) ( $data['sportscategory_order'] ?? 0 );

		if ( mb_strlen( $name ) < 2 ) {
			return __( 'Поле «Найменування» є обов’язковим.', 'fstu' );
		}

		if ( mb_strlen( $name ) > 50 ) {
			return __( 'Поле «Найменування» не може бути довшим за 50 символів.', 'fstu' );
		}

		if ( ! preg_match( '/^[\p{L}\p{N}\s\-.,()№#\/]+$/u', $name ) ) {
			return __( 'Поле «Найменування» містить недопустимі символи.', 'fstu' );
		}

		if ( $order < 0 ) {
			return __( 'Поле «Сортування» має бути невід’ємним числом.', 'fstu' );
		}

		if ( $this->name_exists( $name, $exclude_id ) ) {
			return __( 'Запис з таким найменуванням уже існує.', 'fstu' );
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
		return Capabilities::get_sportscategories_permissions();
	}

	/**
	 * @return array<string,mixed>|null
	 */
	private function get_item_by_id( int $sportscategory_id ): ?array {
		global $wpdb;

		$item = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT SportsCategories_ID,
					SportsCategories_DateCreate,
					SportsCategories_Name,
					SportsCategories_Order,
					SportsCategories_Ball
				FROM S_SportsCategories
				WHERE SportsCategories_ID = %d
				LIMIT 1",
				$sportscategory_id
			),
			ARRAY_A
		);

		return is_array( $item ) ? $item : null;
	}

	/**
	 * Перевіряє залежності перед delete.
	 */
	private function has_delete_dependencies( int $sportscategory_id ): bool {
		global $wpdb;

		if ( ! $this->table_exists( 'UserSportCategories' ) ) {
			return false;
		}

		$usage_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM UserSportCategories WHERE SportsCategories_ID = %d',
				$sportscategory_id
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
		$prepared             = $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name );
		$cache[ $table_name ] = null !== $wpdb->get_var( $prepared );

		return $cache[ $table_name ];
	}

	/**
	 * Перевіряє наявність дубля по назві.
	 */
	private function name_exists( string $name, int $exclude_id = 0 ): bool {
		global $wpdb;

		$sql    = 'SELECT SportsCategories_ID FROM S_SportsCategories WHERE SportsCategories_Name = %s';
		$params = [ $name ];

		if ( $exclude_id > 0 ) {
			$sql     .= ' AND SportsCategories_ID != %d';
			$params[] = $exclude_id;
		}

		$sql .= ' LIMIT 1';

		$existing_id = $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );

		return null !== $existing_id;
	}

	/**
	 * Повертає наступний order.
	 */
	private function get_next_order(): int {
		global $wpdb;

		$max_order = (int) $wpdb->get_var( 'SELECT COALESCE(MAX(SportsCategories_Order), 0) FROM S_SportsCategories' );

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
		return Capabilities::current_user_can_view_sportscategories();
	}

	/**
	 * Чи може поточний користувач керувати довідником.
	 */
	private function current_user_can_manage(): bool {
		return Capabilities::current_user_can_manage_sportscategories();
	}

	/**
	 * Чи може поточний користувач видаляти записи.
	 */
	private function current_user_can_delete(): bool {
		return Capabilities::current_user_can_delete_sportscategories();
	}

	/**
	 * Чи може поточний користувач бачити протокол.
	 */
	private function current_user_can_protocol(): bool {
		return Capabilities::current_user_can_view_sportscategories_protocol();
	}
}

