<?php
namespace FSTU\Dictionaries\MemberGuidance;

/**
 * AJAX-обробники модуля "Довідник посад у керівних органах ФСТУ".
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\MemberGuidance
 */

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Member_Guidance_Ajax {

	private const DEFAULT_PER_PAGE      = 10;
	private const MAX_PER_PAGE          = 50;
	private const MAX_PROTOCOL_PER_PAGE = 50;
	private const LOG_NAME              = 'MemberGuidance';
	private const DEFAULT_TYPEGUIDANCE_ID = 1;
	private const OWNERSHIP_COLUMNS     = [ 'UserCreate', 'User_ID', 'Author_ID' ];

	/**
	 * Кеш назви колонки авторства.
	 *
	 * @var string|false|null
	 */
	private static $owner_column = null;

	/**
	 * Реєструє AJAX-обробники.
	 */
	public function init(): void {
		add_action( 'wp_ajax_fstu_member_guidance_get_list', [ $this, 'handle_get_list' ] );
		add_action( 'wp_ajax_fstu_member_guidance_get_single', [ $this, 'handle_get_single' ] );
		add_action( 'wp_ajax_fstu_member_guidance_create', [ $this, 'handle_create' ] );
		add_action( 'wp_ajax_fstu_member_guidance_update', [ $this, 'handle_update' ] );
		add_action( 'wp_ajax_fstu_member_guidance_delete', [ $this, 'handle_delete' ] );
		add_action( 'wp_ajax_fstu_member_guidance_get_protocol', [ $this, 'handle_get_protocol' ] );
	}

	/**
	 * Повертає список записів.
	 */
	public function handle_get_list(): void {
		check_ajax_referer( Member_Guidance_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду довідника.', 'fstu' ) ] );
		}

		$search          = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$typeguidance_id = $this->resolve_typeguidance_context( $_POST['typeguidance_id'] ?? 0 );
		$page            = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page        = min( max( 1, absint( $_POST['per_page'] ?? self::DEFAULT_PER_PAGE ) ), self::MAX_PER_PAGE );
		$offset          = ( $page - 1 ) * $per_page;

		global $wpdb;

		$where  = 'TypeGuidance_ID = %d';
		$params = [ $typeguidance_id ];

		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where   .= ' AND MemberGuidance_Name LIKE %s';
			$params[] = $like;
		}

		$count_sql = "SELECT COUNT(*) FROM vMemberGuidance WHERE {$where}";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );

		$list_sql = "SELECT MemberGuidance_ID, MemberGuidance_Name, MemberGuidance_Order, TypeGuidance_ID, TypeGuidance_Name
			FROM vMemberGuidance
			WHERE {$where}
			ORDER BY MemberGuidance_Order ASC, MemberGuidance_Name ASC
			LIMIT %d OFFSET %d";

		$list_params = array_merge( $params, [ $per_page, $offset ] );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$items = $wpdb->get_results( $wpdb->prepare( $list_sql, ...$list_params ), ARRAY_A );

		$items       = is_array( $items ) ? $items : [];
		$permissions = $this->get_permissions();
		$ownership   = $this->get_delete_permissions_map( $items );

		wp_send_json_success(
			[
				'html'            => $this->build_rows( $items, $offset, $permissions, $ownership ),
				'total'           => $total,
				'page'            => $page,
				'per_page'        => $per_page,
				'total_pages'     => max( 1, (int) ceil( $total / max( 1, $per_page ) ) ),
				'typeguidance_id' => $typeguidance_id,
			]
		);
	}

	/**
	 * Повертає один запис для shared-модалки.
	 */
	public function handle_get_single(): void {
		check_ajax_referer( Member_Guidance_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду запису.', 'fstu' ) ] );
		}

		$member_guidance_id = absint( $_POST['member_guidance_id'] ?? 0 );
		if ( $member_guidance_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		$item = $this->get_member_guidance_by_id( $member_guidance_id );
		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		wp_send_json_success(
			[
				'item' => [
					'member_guidance_id'    => (int) $item['MemberGuidance_ID'],
					'member_guidance_name'  => (string) $item['MemberGuidance_Name'],
					'member_guidance_order' => (int) ( $item['MemberGuidance_Order'] ?? 0 ),
					'typeguidance_id'       => (int) ( $item['TypeGuidance_ID'] ?? 0 ),
					'typeguidance_name'     => (string) ( $item['TypeGuidance_Name'] ?? '' ),
				],
			]
		);
	}

	/**
	 * Створює запис.
	 */
	public function handle_create(): void {
		check_ajax_referer( Member_Guidance_List::NONCE_ACTION, 'nonce' );

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

		$order = $data['member_guidance_order'];
		if ( null === $order ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$order = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COALESCE(MAX(MemberGuidance_Order), 0) FROM S_MemberGuidance WHERE TypeGuidance_ID = %d',
					$data['typeguidance_id']
				)
			) + 1;
		}

		$insert_data = [
			'MemberGuidance_DateCreate' => current_time( 'mysql' ),
			'TypeGuidance_ID'          => $data['typeguidance_id'],
			'MemberGuidance_Name'      => $data['member_guidance_name'],
			'MemberGuidance_Order'     => $order,
		];
		$formats     = [ '%s', '%d', '%s', '%d' ];

		$owner_column = $this->get_owner_column();
		if ( $owner_column ) {
			$insert_data[ $owner_column ] = get_current_user_id();
			$formats[]                    = '%d';
		}

		$inserted = $wpdb->insert( 'S_MemberGuidance', $insert_data, $formats );

		if ( false === $inserted ) {
			$this->log_action( 'INSERT', __( 'Помилка додавання посади у керівному органі.', 'fstu' ), $wpdb->last_error ?: 'DB error' );
			wp_send_json_error( [ 'message' => __( 'Помилка при збереженні запису.', 'fstu' ) ] );
		}

		$this->log_action(
			'INSERT',
			sprintf(
				'Додано посаду у керівному органі: %1$s (%2$s)',
				$data['member_guidance_name'],
				$this->get_typeguidance_name( $data['typeguidance_id'] )
			),
			'✓'
		);

		wp_send_json_success( [ 'message' => __( 'Запис успішно додано.', 'fstu' ) ] );
	}

	/**
	 * Оновлює запис.
	 */
	public function handle_update(): void {
		check_ajax_referer( Member_Guidance_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для редагування запису.', 'fstu' ) ] );
		}

		if ( ! $this->validate_honeypot() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		$member_guidance_id = absint( $_POST['member_guidance_id'] ?? 0 );
		$data               = $this->sanitize_form_data();
		$error_message      = $this->validate_form_data( $data, $member_guidance_id );

		if ( $member_guidance_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		if ( '' !== $error_message ) {
			wp_send_json_error( [ 'message' => $error_message ] );
		}

		$item = $this->get_member_guidance_by_id( $member_guidance_id );
		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		global $wpdb;

		$order = null === $data['member_guidance_order']
			? (int) ( $item['MemberGuidance_Order'] ?? 0 )
			: $data['member_guidance_order'];

		$updated = $wpdb->update(
			'S_MemberGuidance',
			[
				'TypeGuidance_ID'      => $data['typeguidance_id'],
				'MemberGuidance_Name'  => $data['member_guidance_name'],
				'MemberGuidance_Order' => $order,
			],
			[ 'MemberGuidance_ID' => $member_guidance_id ],
			[ '%d', '%s', '%d' ],
			[ '%d' ]
		);

		if ( false === $updated ) {
			$this->log_action( 'UPDATE', sprintf( 'Помилка оновлення посади у керівному органі: %s', (string) $item['MemberGuidance_Name'] ), $wpdb->last_error ?: 'DB error' );
			wp_send_json_error( [ 'message' => __( 'Помилка при збереженні запису.', 'fstu' ) ] );
		}

		$this->log_action(
			'UPDATE',
			sprintf(
				'Оновлено посаду у керівному органі: %1$s (%2$s)',
				$data['member_guidance_name'],
				$this->get_typeguidance_name( $data['typeguidance_id'] )
			),
			'✓'
		);

		wp_send_json_success( [ 'message' => __( 'Запис успішно оновлено.', 'fstu' ) ] );
	}

	/**
	 * Видаляє запис.
	 */
	public function handle_delete(): void {
		check_ajax_referer( Member_Guidance_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_delete() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для видалення запису.', 'fstu' ) ] );
		}

		$member_guidance_id = absint( $_POST['member_guidance_id'] ?? 0 );
		if ( $member_guidance_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		$item = $this->get_member_guidance_by_id( $member_guidance_id );
		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		if ( ! $this->current_user_can_delete_any() && ! $this->can_current_user_delete_owned_item( $member_guidance_id ) ) {
			$this->log_action(
				'DELETE',
				sprintf( 'Відхилено видалення посади у керівному органі без підтвердженого авторства: %s', (string) $item['MemberGuidance_Name'] ),
				__( 'Немає прав', 'fstu' )
			);
			wp_send_json_error( [ 'message' => __( 'Видалення дозволено лише для власних записів.', 'fstu' ) ] );
		}

		global $wpdb;
		$deleted = $wpdb->delete( 'S_MemberGuidance', [ 'MemberGuidance_ID' => $member_guidance_id ], [ '%d' ] );

		if ( false === $deleted ) {
			$this->log_action( 'DELETE', sprintf( 'Помилка видалення посади у керівному органі: %s', (string) $item['MemberGuidance_Name'] ), $wpdb->last_error ?: 'DB error' );
			wp_send_json_error( [ 'message' => __( 'Не вдалося видалити запис.', 'fstu' ) ] );
		}

		$this->log_action( 'DELETE', sprintf( 'Видалено посаду у керівному органі: %s', (string) $item['MemberGuidance_Name'] ), '✓' );

		wp_send_json_success( [ 'message' => __( 'Запис успішно видалено.', 'fstu' ) ] );
	}

	/**
	 * Повертає протокол модуля.
	 */
	public function handle_get_protocol(): void {
		check_ajax_referer( Member_Guidance_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_protocol() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду протоколу.', 'fstu' ) ] );
		}

		$search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
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
	 * Будує HTML рядків основної таблиці.
	 *
	 * @param array<int,array<string,mixed>> $items
	 * @param int                            $offset
	 * @param array<string,bool>             $permissions
	 * @param array<int,bool>                $ownership_map
	 */
	private function build_rows( array $items, int $offset, array $permissions, array $ownership_map ): string {
		if ( empty( $items ) ) {
			return '<tr class="fstu-row"><td colspan="4" class="fstu-no-results">' . esc_html__( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ) . '</td></tr>';
		}

		$html  = '';
		$index = $offset;

		foreach ( $items as $item ) {
			++$index;

			$member_guidance_id   = (int) ( $item['MemberGuidance_ID'] ?? 0 );
			$member_guidance_name = (string) ( $item['MemberGuidance_Name'] ?? '' );
			$typeguidance_name    = (string) ( $item['TypeGuidance_Name'] ?? '' );
			$can_delete_row       = ! empty( $ownership_map[ $member_guidance_id ] );

			$actions   = [];
			$actions[] = '<button type="button" class="fstu-member-guidance-dropdown__item fstu-member-guidance-view-btn" data-member-guidance-id="' . esc_attr( (string) $member_guidance_id ) . '">' . esc_html__( 'Перегляд', 'fstu' ) . '</button>';

			if ( ! empty( $permissions['canManage'] ) ) {
				$actions[] = '<button type="button" class="fstu-member-guidance-dropdown__item fstu-member-guidance-edit-btn" data-member-guidance-id="' . esc_attr( (string) $member_guidance_id ) . '">' . esc_html__( 'Редагування', 'fstu' ) . '</button>';
			}

			if ( $can_delete_row ) {
				$actions[] = '<button type="button" class="fstu-member-guidance-dropdown__item fstu-member-guidance-dropdown__item--danger fstu-member-guidance-delete-btn" data-member-guidance-id="' . esc_attr( (string) $member_guidance_id ) . '">' . esc_html__( 'Видалення', 'fstu' ) . '</button>';
			}

			$html .= '<tr class="fstu-row">';
			$html .= '<td class="fstu-td fstu-td--num">' . esc_html( (string) $index ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--typeguidance">' . ( '' !== $typeguidance_name ? esc_html( $typeguidance_name ) : '<span class="fstu-text-muted">—</span>' ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--name"><button type="button" class="fstu-member-guidance-link-button fstu-member-guidance-view-btn" data-member-guidance-id="' . esc_attr( (string) $member_guidance_id ) . '">' . esc_html( $member_guidance_name ) . '</button></td>';
			$html .= '<td class="fstu-td fstu-td--actions">';
			$html .= '<div class="fstu-member-guidance-dropdown">';
			$html .= '<button type="button" class="fstu-member-guidance-dropdown__toggle" aria-expanded="false" title="' . esc_attr__( 'Меню дій', 'fstu' ) . '">▼</button>';
			$html .= '<div class="fstu-member-guidance-dropdown__menu">' . implode( '', $actions ) . '</div>';
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
		$order_raw = sanitize_text_field( wp_unslash( $_POST['member_guidance_order'] ?? '' ) );

		return [
			'typeguidance_id'         => absint( $_POST['typeguidance_id'] ?? 0 ),
			'member_guidance_name'    => sanitize_text_field( wp_unslash( $_POST['member_guidance_name'] ?? '' ) ),
			'member_guidance_order_raw' => $order_raw,
			'member_guidance_order'   => '' === $order_raw ? null : absint( $order_raw ),
		];
	}

	/**
	 * Валідує форму.
	 *
	 * @param array<string,mixed> $data               Дані форми.
	 * @param int                 $member_guidance_id Поточний ID для update.
	 */
	private function validate_form_data( array $data, int $member_guidance_id = 0 ): string {
		$name            = (string) ( $data['member_guidance_name'] ?? '' );
		$order_raw       = (string) ( $data['member_guidance_order_raw'] ?? '' );
		$typeguidance_id = (int) ( $data['typeguidance_id'] ?? 0 );

		if ( $typeguidance_id <= 0 || ! $this->typeguidance_exists( $typeguidance_id ) ) {
			return __( 'Поле «Тип керівного органу» містить некоректне значення.', 'fstu' );
		}

		if ( mb_strlen( $name ) < 2 ) {
			return __( 'Поле «Найменування» є обов’язковим.', 'fstu' );
		}

		if ( mb_strlen( $name ) > 255 ) {
			return __( 'Поле «Найменування» не може бути довшим за 255 символів.', 'fstu' );
		}

		if ( '' !== $order_raw && ! preg_match( '/^\d+$/', $order_raw ) ) {
			return __( 'Поле «Сортування» повинно містити лише невід’ємне число.', 'fstu' );
		}

		if ( $this->member_guidance_name_exists( $name, $typeguidance_id, $member_guidance_id ) ) {
			return __( 'У межах обраного типу керівного органу вже існує запис з таким найменуванням.', 'fstu' );
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
		return Capabilities::get_member_guidance_permissions();
	}

	/**
	 * Чи може користувач переглядати модуль.
	 */
	private function current_user_can_view(): bool {
		return Capabilities::current_user_can_view_member_guidance();
	}

	/**
	 * Чи може користувач керувати модулем.
	 */
	private function current_user_can_manage(): bool {
		return Capabilities::current_user_can_manage_member_guidance();
	}

	/**
	 * Чи може користувач видаляти записи.
	 */
	private function current_user_can_delete(): bool {
		return Capabilities::current_user_can_delete_member_guidance() || Capabilities::current_user_can_delete_own_member_guidance();
	}

	/**
	 * Чи може користувач видаляти будь-які записи.
	 */
	private function current_user_can_delete_any(): bool {
		return Capabilities::current_user_can_delete_member_guidance();
	}

	/**
	 * Чи може користувач видаляти лише власні записи.
	 */
	private function current_user_can_delete_own(): bool {
		return ! $this->current_user_can_delete_any() && Capabilities::current_user_can_delete_own_member_guidance();
	}

	/**
	 * Чи може користувач переглядати протокол.
	 */
	private function current_user_can_protocol(): bool {
		return Capabilities::current_user_can_view_member_guidance_protocol();
	}

	/**
	 * Повертає запис довідника за ID.
	 *
	 * @return array<string,mixed>|null
	 */
	private function get_member_guidance_by_id( int $member_guidance_id ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$item = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT MemberGuidance_ID, MemberGuidance_Name, MemberGuidance_Order, TypeGuidance_ID, TypeGuidance_Name
				 FROM vMemberGuidance
				 WHERE MemberGuidance_ID = %d
				 LIMIT 1",
				$member_guidance_id
			),
			ARRAY_A
		);

		return is_array( $item ) ? $item : null;
	}

	/**
	 * Визначає контекст TypeGuidance_ID для списку.
	 *
	 * @param mixed $raw_value Сире значення з запиту.
	 */
	private function resolve_typeguidance_context( $raw_value ): int {
		$typeguidance_id = absint( $raw_value );

		if ( $typeguidance_id > 0 && $this->typeguidance_exists( $typeguidance_id ) ) {
			return $typeguidance_id;
		}

		return self::DEFAULT_TYPEGUIDANCE_ID;
	}

	/**
	 * Перевіряє існування батьківського типу керівного органу.
	 */
	private function typeguidance_exists( int $typeguidance_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT TypeGuidance_ID FROM vTypeGuidance WHERE TypeGuidance_ID = %d LIMIT 1',
				$typeguidance_id
			)
		);

		return null !== $existing_id;
	}

	/**
	 * Повертає назву типу керівного органу.
	 */
	private function get_typeguidance_name( int $typeguidance_id ): string {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$name = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT TypeGuidance_Name FROM vTypeGuidance WHERE TypeGuidance_ID = %d LIMIT 1',
				$typeguidance_id
			)
		);

		return is_string( $name ) ? $name : '';
	}

	/**
	 * Перевіряє scoped-унікальність назви в межах TypeGuidance_ID.
	 */
	private function member_guidance_name_exists( string $member_guidance_name, int $typeguidance_id, int $exclude_id = 0 ): bool {
		global $wpdb;

		$sql    = 'SELECT MemberGuidance_ID FROM S_MemberGuidance WHERE TypeGuidance_ID = %d AND MemberGuidance_Name = %s';
		$params = [ $typeguidance_id, $member_guidance_name ];

		if ( $exclude_id > 0 ) {
			$sql     .= ' AND MemberGuidance_ID != %d';
			$params[] = $exclude_id;
		}

		$sql .= ' LIMIT 1';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$existing_id = $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );

		return null !== $existing_id;
	}

	/**
	 * Повертає карту доступності delete по рядках.
	 *
	 * @param array<int,array<string,mixed>> $items Рядки списку.
	 * @return array<int,bool>
	 */
	private function get_delete_permissions_map( array $items ): array {
		$map = [];

		foreach ( $items as $item ) {
			$item_id = (int) ( $item['MemberGuidance_ID'] ?? 0 );
			if ( $item_id > 0 ) {
				$map[ $item_id ] = false;
			}
		}

		if ( empty( $map ) || ! $this->current_user_can_delete() ) {
			return $map;
		}

		if ( $this->current_user_can_delete_any() ) {
			foreach ( array_keys( $map ) as $item_id ) {
				$map[ $item_id ] = true;
			}

			return $map;
		}

		if ( ! $this->current_user_can_delete_own() ) {
			return $map;
		}

		$owner_column = $this->get_owner_column();
		if ( ! $owner_column ) {
			return $map;
		}

		$item_ids      = array_keys( $map );
		$placeholders  = implode( ', ', array_fill( 0, count( $item_ids ), '%d' ) );
		$params        = array_merge( $item_ids, [ get_current_user_id() ] );
		global $wpdb;

		$sql = "SELECT MemberGuidance_ID
			FROM S_MemberGuidance
			WHERE MemberGuidance_ID IN ({$placeholders})
				AND {$owner_column} = %d";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_col( $wpdb->prepare( $sql, ...$params ) );

		if ( ! is_array( $rows ) ) {
			return $map;
		}

		foreach ( $rows as $row_id ) {
			$map[ (int) $row_id ] = true;
		}

		return $map;
	}

	/**
	 * Перевіряє, чи може поточний користувач видалити власний запис.
	 */
	private function can_current_user_delete_owned_item( int $member_guidance_id ): bool {
		if ( ! $this->current_user_can_delete_own() ) {
			return false;
		}

		$owner_column = $this->get_owner_column();
		if ( ! $owner_column ) {
			return false;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$owner_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT {$owner_column}
				 FROM S_MemberGuidance
				 WHERE MemberGuidance_ID = %d
				 LIMIT 1",
				$member_guidance_id
			)
		);

		return (int) $owner_id > 0 && (int) $owner_id === get_current_user_id();
	}

	/**
	 * Повертає назву доступної колонки авторства або false.
	 *
	 * @return string|false
	 */
	private function get_owner_column() {
		if ( null !== self::$owner_column ) {
			return self::$owner_column;
		}

		global $wpdb;

		foreach ( self::OWNERSHIP_COLUMNS as $column_name ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$result = $wpdb->get_var(
				$wpdb->prepare(
					'SHOW COLUMNS FROM S_MemberGuidance LIKE %s',
					$column_name
				)
			);

			if ( is_string( $result ) && $column_name === $result ) {
				self::$owner_column = $column_name;
				return self::$owner_column;
			}
		}

		self::$owner_column = false;
		return self::$owner_column;
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

