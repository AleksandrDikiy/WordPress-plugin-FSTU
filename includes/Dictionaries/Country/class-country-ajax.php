<?php
/**
 * AJAX-обробники модуля "Довідник країн".
 *
 * Version:     1.0.2
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\Country
 */

namespace FSTU\Dictionaries\Country;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Country_Ajax {

	private const DEFAULT_PER_PAGE      = 10;
	private const MAX_PER_PAGE          = 50;
	private const MAX_PROTOCOL_PER_PAGE = 50;
	private const MAX_SEARCH_LENGTH     = 100;
	private const LOG_NAME              = 'Country';
	private const LOG_TYPE_INSERT       = 'I';
	private const LOG_TYPE_UPDATE       = 'U';
	private const LOG_TYPE_DELETE       = 'D';
	private const LOG_STATUS_SUCCESS    = 'успішно';

	/**
	 * Реєструє AJAX-обробники.
	 */
	public function init(): void {
		add_action( 'wp_ajax_fstu_country_get_list', [ $this, 'handle_get_list' ] );
		add_action( 'wp_ajax_fstu_country_get_single', [ $this, 'handle_get_single' ] );
		add_action( 'wp_ajax_fstu_country_create', [ $this, 'handle_create' ] );
		add_action( 'wp_ajax_fstu_country_update', [ $this, 'handle_update' ] );
		add_action( 'wp_ajax_fstu_country_delete', [ $this, 'handle_delete' ] );
		add_action( 'wp_ajax_fstu_country_get_protocol', [ $this, 'handle_get_protocol' ] );
	}

	/**
	 * Повертає список записів.
	 */
	public function handle_get_list(): void {
		check_ajax_referer( Country_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду довідника.', 'fstu' ) ] );
		}

		$search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$search   = mb_substr( $search, 0, self::MAX_SEARCH_LENGTH );
		$page     = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page = min( max( 1, absint( $_POST['per_page'] ?? self::DEFAULT_PER_PAGE ) ), self::MAX_PER_PAGE );
		$offset   = ( $page - 1 ) * $per_page;

		global $wpdb;

		$where  = '1=1';
		$params = [];

		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where   .= ' AND Country_Name LIKE %s';
			$params[] = $like;
		}

		$count_sql = "SELECT COUNT(*) FROM vCountry WHERE {$where}";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$total = (int) ( $params ? $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) ) : $wpdb->get_var( $count_sql ) );

		$list_sql = "SELECT Country_ID, Country_Name, Country_NameEng, Country_Order
			FROM vCountry
			WHERE {$where}
			ORDER BY Country_Order ASC, Country_Name ASC
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
		check_ajax_referer( Country_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду запису.', 'fstu' ) ] );
		}

		$country_id = absint( $_POST['country_id'] ?? 0 );
		if ( $country_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		$item = $this->get_country_by_id( $country_id );
		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		wp_send_json_success(
			[
				'item' => [
					'country_id'         => (int) $item['Country_ID'],
					'country_name'       => (string) $item['Country_Name'],
					'country_name_eng'   => (string) ( $item['Country_NameEng'] ?? '' ),
					'country_order'      => (int) ( $item['Country_Order'] ?? 0 ),
					'country_date_create'=> (string) ( $item['Country_DateCreate'] ?? '' ),
				],
			]
		);
	}

	/**
	 * Створює запис.
	 */
	public function handle_create(): void {
		check_ajax_referer( Country_List::NONCE_ACTION, 'nonce' );

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

		$order = $data['country_order'];
		if ( null === $order ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$order = (int) $wpdb->get_var( 'SELECT COALESCE(MAX(Country_Order), 0) FROM S_Country' ) + 1;
		}

		try {
			$this->begin_transaction();

			$inserted = $wpdb->insert(
				'S_Country',
				[
					'Country_DateCreate' => current_time( 'mysql' ),
					'Country_Name'       => $data['country_name'],
					'Country_NameEng'    => $data['country_name_eng'],
					'Country_Order'      => $order,
				],
				[ '%s', '%s', '%s', '%d' ]
			);

			if ( false === $inserted ) {
				throw new \RuntimeException( 'country_insert_failed' );
			}

			$this->log_action( self::LOG_TYPE_INSERT, sprintf( 'Додано нову країну: %s', $data['country_name'] ), self::LOG_STATUS_SUCCESS );
			$this->commit_transaction();

			wp_send_json_success( [ 'message' => __( 'Країну успішно додано.', 'fstu' ) ] );
		} catch ( \Throwable $exception ) {
			$this->rollback_transaction();
			$this->try_log_action(
				self::LOG_TYPE_INSERT,
				sprintf( 'Помилка додавання країни: %s', $data['country_name'] ),
				'error'
			);

			wp_send_json_error( [ 'message' => __( 'Помилка при збереженні запису.', 'fstu' ) ] );
		}
	}

	/**
	 * Оновлює запис.
	 */
	public function handle_update(): void {
		check_ajax_referer( Country_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для редагування запису.', 'fstu' ) ] );
		}

		if ( ! $this->validate_honeypot() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		$country_id     = absint( $_POST['country_id'] ?? 0 );
		$data           = $this->sanitize_form_data();
		$error_message  = $this->validate_form_data( $data, $country_id );

		if ( $country_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		if ( '' !== $error_message ) {
			wp_send_json_error( [ 'message' => $error_message ] );
		}

		$item = $this->get_country_by_id( $country_id );
		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		global $wpdb;

		$order = null === $data['country_order']
			? (int) ( $item['Country_Order'] ?? 0 )
			: $data['country_order'];


		try {
			$this->begin_transaction();

			$updated = $wpdb->update(
				'S_Country',
				[
					'Country_Name'    => $data['country_name'],
					'Country_NameEng' => $data['country_name_eng'],
					'Country_Order'   => $order,
				],
				[ 'Country_ID' => $country_id ],
				[ '%s', '%s', '%d' ],
				[ '%d' ]
			);

			if ( false === $updated ) {
				throw new \RuntimeException( 'country_update_failed' );
			}

			$this->log_action( self::LOG_TYPE_UPDATE, sprintf( 'Оновлено країну: %s', $data['country_name'] ), self::LOG_STATUS_SUCCESS );
			$this->commit_transaction();

			wp_send_json_success( [ 'message' => __( 'Країну успішно оновлено.', 'fstu' ) ] );
		} catch ( \Throwable $exception ) {
			$this->rollback_transaction();
			$this->try_log_action(
				self::LOG_TYPE_UPDATE,
				sprintf( 'Помилка оновлення країни: %s', (string) $item['Country_Name'] ),
				'error'
			);

			wp_send_json_error( [ 'message' => __( 'Помилка при збереженні запису.', 'fstu' ) ] );
		}
	}

	/**
	 * Видаляє запис.
	 */
	public function handle_delete(): void {
		check_ajax_referer( Country_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_delete() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для видалення запису.', 'fstu' ) ] );
		}

		$country_id = absint( $_POST['country_id'] ?? 0 );
		if ( $country_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		$item = $this->get_country_by_id( $country_id );
		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		$dependency_check = $this->check_delete_dependencies( $country_id );
		if ( ! $dependency_check['can_delete'] ) {
			$this->try_log_action( self::LOG_TYPE_DELETE, sprintf( 'Заблоковано видалення країни: %s', (string) $item['Country_Name'] ), $dependency_check['status'] );
			wp_send_json_error( [ 'message' => $dependency_check['message'] ] );
		}

		global $wpdb;

		try {
			$this->begin_transaction();

			$deleted = $wpdb->delete( 'S_Country', [ 'Country_ID' => $country_id ], [ '%d' ] );

			if ( false === $deleted || 1 !== (int) $deleted ) {
				throw new \RuntimeException( 'country_delete_failed' );
			}

			$this->log_action( self::LOG_TYPE_DELETE, sprintf( 'Видалено країну: %s', (string) $item['Country_Name'] ), self::LOG_STATUS_SUCCESS );
			$this->commit_transaction();

			wp_send_json_success( [ 'message' => __( 'Країну успішно видалено.', 'fstu' ) ] );
		} catch ( \Throwable $exception ) {
			$this->rollback_transaction();
			$this->try_log_action(
				self::LOG_TYPE_DELETE,
				sprintf( 'Помилка видалення країни: %s', (string) $item['Country_Name'] ),
				'error'
			);

			wp_send_json_error( [ 'message' => __( 'Не вдалося видалити запис.', 'fstu' ) ] );
		}
	}

	/**
	 * Повертає протокол модуля.
	 */
	public function handle_get_protocol(): void {
		check_ajax_referer( Country_List::NONCE_ACTION, 'nonce' );

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
			return '<tr class="fstu-row"><td colspan="4" class="fstu-no-results">' . esc_html__( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ) . '</td></tr>';
		}

		$html  = '';
		$index = $offset;

		foreach ( $items as $item ) {
			++$index;

			$country_id       = (int) ( $item['Country_ID'] ?? 0 );
			$country_name     = (string) ( $item['Country_Name'] ?? '' );
			$country_name_eng = (string) ( $item['Country_NameEng'] ?? '' );

			$actions   = [];
			$actions[] = '<button type="button" class="fstu-country-dropdown__item fstu-country-view-btn" data-country-id="' . esc_attr( (string) $country_id ) . '">' . esc_html__( 'Перегляд', 'fstu' ) . '</button>';

			if ( ! empty( $permissions['canManage'] ) ) {
				$actions[] = '<button type="button" class="fstu-country-dropdown__item fstu-country-edit-btn" data-country-id="' . esc_attr( (string) $country_id ) . '">' . esc_html__( 'Редагування', 'fstu' ) . '</button>';
			}

			if ( ! empty( $permissions['canDelete'] ) ) {
				$actions[] = '<button type="button" class="fstu-country-dropdown__item fstu-country-dropdown__item--danger fstu-country-delete-btn" data-country-id="' . esc_attr( (string) $country_id ) . '">' . esc_html__( 'Видалення', 'fstu' ) . '</button>';
			}

			$html .= '<tr class="fstu-row">';
			$html .= '<td class="fstu-td fstu-td--num">' . esc_html( (string) $index ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--name"><button type="button" class="fstu-country-link-button fstu-country-view-btn" data-country-id="' . esc_attr( (string) $country_id ) . '">' . esc_html( $country_name ) . '</button></td>';
			$html .= '<td class="fstu-td fstu-td--name-eng">' . ( '' !== $country_name_eng ? esc_html( $country_name_eng ) : '<span class="fstu-text-muted">—</span>' ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--actions">';
			$html .= '<div class="fstu-country-dropdown">';
			$html .= '<button type="button" class="fstu-country-dropdown__toggle" aria-expanded="false" title="' . esc_attr__( 'Меню дій', 'fstu' ) . '">▼</button>';
			$html .= '<div class="fstu-country-dropdown__menu">' . implode( '', $actions ) . '</div>';
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
		$order_raw = sanitize_text_field( wp_unslash( $_POST['country_order'] ?? '' ) );

		return [
			'country_name'         => $this->normalize_form_string( sanitize_text_field( wp_unslash( $_POST['country_name'] ?? '' ) ) ),
			'country_name_eng'     => $this->normalize_form_string( sanitize_text_field( wp_unslash( $_POST['country_name_eng'] ?? '' ) ) ),
			'country_order_raw'    => $order_raw,
			'country_order'        => '' === $order_raw ? null : absint( $order_raw ),
		];
	}

	/**
	 * Валідує форму.
	 *
	 * @param array<string,mixed> $data       Дані форми.
	 * @param int                 $country_id Поточний ID для update.
	 */
	private function validate_form_data( array $data, int $country_id = 0 ): string {
		$name      = (string) ( $data['country_name'] ?? '' );
		$name_eng  = (string) ( $data['country_name_eng'] ?? '' );
		$order_raw = (string) ( $data['country_order_raw'] ?? '' );

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

		if ( $this->country_name_exists( $name, $country_id ) ) {
			return __( 'Запис з таким найменуванням уже існує.', 'fstu' );
		}

		if ( '' !== $name_eng && $this->country_name_eng_exists( $name_eng, $country_id ) ) {
			return __( 'Запис з такою англійською назвою уже існує.', 'fstu' );
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
		return Capabilities::get_country_permissions();
	}

	/**
	 * Чи може користувач переглядати модуль.
	 */
	private function current_user_can_view(): bool {
		return Capabilities::current_user_can_view_country();
	}

	/**
	 * Чи може користувач керувати модулем.
	 */
	private function current_user_can_manage(): bool {
		return Capabilities::current_user_can_manage_country();
	}

	/**
	 * Чи може користувач видаляти записи.
	 */
	private function current_user_can_delete(): bool {
		return Capabilities::current_user_can_delete_country();
	}

	/**
	 * Чи може користувач переглядати протокол.
	 */
	private function current_user_can_protocol(): bool {
		return Capabilities::current_user_can_view_country_protocol();
	}

	/**
	 * Повертає запис довідника за ID.
	 *
	 * @return array<string,mixed>|null
	 */
	private function get_country_by_id( int $country_id ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$item = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT Country_ID, Country_DateCreate, Country_Name, Country_NameEng, Country_Order FROM S_Country WHERE Country_ID = %d LIMIT 1',
				$country_id
			),
			ARRAY_A
		);

		return is_array( $item ) ? $item : null;
	}

	/**
	 * Перевіряє, чи існує запис з таким найменуванням.
	 */
	private function country_name_exists( string $country_name, int $exclude_id = 0 ): bool {
		$rows = $this->get_existing_country_names();
		$needle = $this->normalize_compare_string( $country_name );

		foreach ( $rows as $row ) {
			$current_id = (int) ( $row['Country_ID'] ?? 0 );
			if ( $exclude_id > 0 && $exclude_id === $current_id ) {
				continue;
			}

			$current_name = $this->normalize_compare_string( (string) ( $row['Country_Name'] ?? '' ) );
			if ( '' !== $current_name && $current_name === $needle ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Перевіряє, чи існує запис з такою англійською назвою.
	 */
	private function country_name_eng_exists( string $country_name_eng, int $exclude_id = 0 ): bool {
		$rows   = $this->get_existing_country_names();
		$needle = $this->normalize_compare_string( $country_name_eng );

		foreach ( $rows as $row ) {
			$current_id = (int) ( $row['Country_ID'] ?? 0 );
			if ( $exclude_id > 0 && $exclude_id === $current_id ) {
				continue;
			}

			$current_name_eng = $this->normalize_compare_string( (string) ( $row['Country_NameEng'] ?? '' ) );
			if ( '' !== $current_name_eng && $current_name_eng === $needle ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Повертає всі наявні назви країн для duplicate-check.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_existing_country_names(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( 'SELECT Country_ID, Country_Name, Country_NameEng FROM S_Country', ARRAY_A );

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * Перевіряє залежності перед видаленням.
	 *
	 * @return array<string,mixed>
	 */
	private function check_delete_dependencies( int $country_id ): array {
		global $wpdb;

		$schema_name = defined( 'DB_NAME' ) ? (string) DB_NAME : '';
		if ( '' === $schema_name ) {
			return [
				'can_delete' => false,
				'message'    => __( 'Неможливо перевірити зв’язки запису. Видалення заблоковано з міркувань безпеки.', 'fstu' ),
				'status'     => 'dependency check unavailable',
			];
		}

		$metadata_sql = 'SELECT TABLE_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = %s AND COLUMN_NAME = %s AND TABLE_NAME NOT IN (%s, %s) GROUP BY TABLE_NAME ORDER BY TABLE_NAME ASC';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$tables = $wpdb->get_col( $wpdb->prepare( $metadata_sql, $schema_name, 'Country_ID', 'S_Country', 'vCountry' ) );

		if ( ! is_array( $tables ) ) {
			return [
				'can_delete' => false,
				'message'    => __( 'Неможливо перевірити зв’язки запису. Видалення заблоковано з міркувань безпеки.', 'fstu' ),
				'status'     => 'dependency check failed',
			];
		}

		/**
		 * Дає змогу розширити список таблиць / views, які треба перевірити перед delete.
		 *
		 * @param array<int,string> $tables Список об’єктів БД з колонкою Country_ID.
		 */
		$tables = apply_filters( 'fstu_country_dependency_tables', $tables );
		$tables = is_array( $tables ) ? array_values( array_unique( array_filter( array_map( 'strval', $tables ) ) ) ) : [];

		$dependencies = [];

		foreach ( $tables as $table_name ) {
			if ( ! preg_match( '/^[A-Za-z0-9_]+$/', $table_name ) ) {
				continue;
			}

			$sql = "SELECT COUNT(*) FROM `{$table_name}` WHERE `Country_ID` = %d";
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$count = (int) $wpdb->get_var( $wpdb->prepare( $sql, $country_id ) );

			if ( ! empty( $wpdb->last_error ) ) {
				return [
					'can_delete' => false,
					'message'    => __( 'Неможливо перевірити зв’язки запису. Видалення заблоковано з міркувань безпеки.', 'fstu' ),
					'status'     => 'dependency query error',
				];
			}

			if ( $count > 0 ) {
				$dependencies[] = $table_name . ': ' . $count;
			}
		}

		if ( ! empty( $dependencies ) ) {
			return [
				'can_delete' => false,
				'message'    => __( 'Країну неможливо видалити, оскільки вона використовується в інших даних.', 'fstu' ),
				'status'     => implode( '; ', $dependencies ),
			];
		}

		return [
			'can_delete' => true,
			'message'    => '',
			'status'     => '✓',
		];
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
			throw new \RuntimeException( 'country_log_insert_failed' );
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
			throw new \RuntimeException( 'country_transaction_start_failed' );
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
			throw new \RuntimeException( 'country_transaction_commit_failed' );
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

