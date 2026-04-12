<?php
/**
 * AJAX-обробники модуля "Довідник видів участі в заходах".
 *
 * Version:     1.0.0
 * Date_update: 2026-04-12
 *
 * @package FSTU\Dictionaries\ParticipationType
 */

namespace FSTU\Dictionaries\ParticipationType;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ParticipationType_Ajax {

	private const MAX_PER_PAGE          = 50;
	private const MAX_PROTOCOL_PER_PAGE = 50;
	private const LOG_NAME              = 'ParticipationType';

	/**
	 * Реєструє AJAX-обробники модуля.
	 */
	public function init(): void {
		add_action( 'wp_ajax_fstu_participationtype_get_list', [ $this, 'handle_get_list' ] );
		add_action( 'wp_ajax_fstu_participationtype_get_single', [ $this, 'handle_get_single' ] );
		add_action( 'wp_ajax_fstu_participationtype_create', [ $this, 'handle_create' ] );
		add_action( 'wp_ajax_fstu_participationtype_update', [ $this, 'handle_update' ] );
		add_action( 'wp_ajax_fstu_participationtype_delete', [ $this, 'handle_delete' ] );
		add_action( 'wp_ajax_fstu_participationtype_reorder', [ $this, 'handle_reorder' ] );
		add_action( 'wp_ajax_fstu_participationtype_get_protocol', [ $this, 'handle_get_protocol' ] );
	}

	/**
	 * Повертає список записів довідника.
	 */
	public function handle_get_list(): void {
		check_ajax_referer( ParticipationType_List::NONCE_ACTION, 'nonce' );

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
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where   .= ' AND ParticipationType_Name LIKE %s';
			$params[] = $like;
		}

		$count_sql = "SELECT COUNT(*) FROM S_ParticipationType WHERE {$where}";
		$total     = (int) ( ! empty( $params )
			? $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) )
			: $wpdb->get_var( $count_sql ) );

		$data_sql = "SELECT ParticipationType_ID, ParticipationType_DateCreate, ParticipationType_Name, ParticipationType_Order, ParticipationType_Type
			FROM S_ParticipationType
			WHERE {$where}
			ORDER BY ParticipationType_Order ASC, ParticipationType_ID ASC
			LIMIT %d OFFSET %d";

		$data_params = array_merge( $params, [ $per_page, $offset ] );
		$items       = $wpdb->get_results( $wpdb->prepare( $data_sql, ...$data_params ), ARRAY_A );
		$permissions = $this->get_permissions();
		$can_reorder = ! empty( $permissions['canManage'] ) && '' === $search;

		wp_send_json_success(
			[
				'html'        => $this->build_rows( is_array( $items ) ? $items : [], $offset, $permissions, $can_reorder ),
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
		check_ajax_referer( ParticipationType_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_view() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду запису.', 'fstu' ) ] );
		}

		$participation_type_id = absint( $_POST['participation_type_id'] ?? 0 );

		if ( $participation_type_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		$item = $this->get_item_by_id( $participation_type_id );

		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		wp_send_json_success(
			[
				'participation_type_id'         => (int) ( $item['ParticipationType_ID'] ?? 0 ),
				'participation_type_name'       => (string) ( $item['ParticipationType_Name'] ?? '' ),
				'participation_type_type'       => (int) ( $item['ParticipationType_Type'] ?? 1 ),
				'participation_type_order'      => (int) ( $item['ParticipationType_Order'] ?? 0 ),
				'participation_type_datecreate' => (string) ( $item['ParticipationType_DateCreate'] ?? '' ),
				'participation_type_type_label' => $this->get_type_label( (int) ( $item['ParticipationType_Type'] ?? 1 ) ),
			]
		);
	}

	/**
	 * Створює новий запис довідника.
	 */
	public function handle_create(): void {
		check_ajax_referer( ParticipationType_List::NONCE_ACTION, 'nonce' );

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

		if ( $data['participation_type_order'] <= 0 ) {
			$max_order                         = (int) $wpdb->get_var( 'SELECT COALESCE(MAX(ParticipationType_Order), 0) FROM S_ParticipationType' );
			$data['participation_type_order'] = $max_order + 1;
		}

		$wpdb->query( 'START TRANSACTION' );

		$result = $wpdb->insert(
			'S_ParticipationType',
			[
				'ParticipationType_DateCreate' => current_time( 'mysql' ),
				'ParticipationType_Name'       => $data['participation_type_name'],
				'ParticipationType_Type'       => $data['participation_type_type'],
				'ParticipationType_Order'      => $data['participation_type_order'],
			],
			[ '%s', '%s', '%d', '%d' ]
		);

		if ( false === $result ) {
			$wpdb->query( 'ROLLBACK' );
			$this->log_action_best_effort( 'I', __( 'Помилка додавання виду участі.', 'fstu' ), 'error' );
			wp_send_json_error( [ 'message' => __( 'Помилка при збереженні запису.', 'fstu' ) ] );
		}

		if ( ! $this->log_action_transactional( 'I', sprintf( 'Додано вид участі: %s', $data['participation_type_name'] ), '✓' ) ) {
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
		check_ajax_referer( ParticipationType_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для редагування запису.', 'fstu' ) ] );
		}

		if ( ! $this->validate_honeypot() ) {
			wp_send_json_error( [ 'message' => __( 'Запит відхилено.', 'fstu' ) ] );
		}

		$participation_type_id = absint( $_POST['participation_type_id'] ?? 0 );
		$data                  = $this->sanitize_form_data();
		$error_message         = $this->validate_form_data( $data, $participation_type_id );

		if ( $participation_type_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		if ( '' !== $error_message ) {
			wp_send_json_error( [ 'message' => $error_message ] );
		}

		if ( ! is_array( $this->get_item_by_id( $participation_type_id ) ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );

		$result = $wpdb->update(
			'S_ParticipationType',
			[
				'ParticipationType_Name'  => $data['participation_type_name'],
				'ParticipationType_Type'  => $data['participation_type_type'],
				'ParticipationType_Order' => $data['participation_type_order'],
			],
			[ 'ParticipationType_ID' => $participation_type_id ],
			[ '%s', '%d', '%d' ],
			[ '%d' ]
		);

		if ( false === $result ) {
			$wpdb->query( 'ROLLBACK' );
			$this->log_action_best_effort( 'U', __( 'Помилка оновлення виду участі.', 'fstu' ), 'error' );
			wp_send_json_error( [ 'message' => __( 'Помилка при збереженні запису.', 'fstu' ) ] );
		}

		if ( ! $this->log_action_transactional( 'U', sprintf( 'Оновлено вид участі: %s', $data['participation_type_name'] ), '✓' ) ) {
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
		check_ajax_referer( ParticipationType_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_delete() ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для видалення запису.', 'fstu' ) ] );
		}

		$participation_type_id = absint( $_POST['participation_type_id'] ?? 0 );

		if ( $participation_type_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		$item = $this->get_item_by_id( $participation_type_id );

		if ( ! is_array( $item ) ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		if ( $this->has_delete_dependencies( $participation_type_id ) ) {
			$this->log_action_best_effort(
				'D',
				sprintf( 'Заблоковано видалення виду участі: %s', (string) ( $item['ParticipationType_Name'] ?? '' ) ),
				'dependency'
			);

			wp_send_json_error( [ 'message' => __( 'Не вдалося видалити запис, оскільки він уже використовується у пов’язаних даних.', 'fstu' ) ] );
		}

		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );

		$result = $wpdb->delete( 'S_ParticipationType', [ 'ParticipationType_ID' => $participation_type_id ], [ '%d' ] );

		if ( false === $result ) {
			$wpdb->query( 'ROLLBACK' );
			$this->log_action_best_effort( 'D', __( 'Помилка видалення виду участі.', 'fstu' ), 'error' );
			wp_send_json_error( [ 'message' => __( 'Не вдалося видалити запис.', 'fstu' ) ] );
		}

		if ( ! $this->log_action_transactional( 'D', sprintf( 'Видалено вид участі: %s', (string) ( $item['ParticipationType_Name'] ?? '' ) ), '✓' ) ) {
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
		check_ajax_referer( ParticipationType_List::NONCE_ACTION, 'nonce' );

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
			$participation_type_id = absint( $item['id'] ?? 0 );
			$order                 = absint( $item['order'] ?? 0 );

			if ( $participation_type_id <= 0 || $order <= 0 || isset( $seen_ids[ $participation_type_id ] ) ) {
				continue;
			}

			$seen_ids[ $participation_type_id ] = true;
			$prepared_items[]                   = [
				'id'    => $participation_type_id,
				'order' => $order,
			];
		}

		if ( empty( $prepared_items ) ) {
			wp_send_json_error( [ 'message' => __( 'Передано некоректні дані сортування.', 'fstu' ) ] );
		}

		$wpdb->query( 'START TRANSACTION' );

		foreach ( $prepared_items as $item ) {
			$result = $wpdb->update(
				'S_ParticipationType',
				[ 'ParticipationType_Order' => $item['order'] ],
				[ 'ParticipationType_ID' => $item['id'] ],
				[ '%d' ],
				[ '%d' ]
			);

			if ( false === $result ) {
				$wpdb->query( 'ROLLBACK' );
				$this->log_action_best_effort( 'U', __( 'Помилка зміни порядку видів участі.', 'fstu' ), 'error' );
				wp_send_json_error( [ 'message' => __( 'Не вдалося оновити порядок записів.', 'fstu' ) ] );
			}
		}

		if ( ! $this->log_action_transactional( 'U', __( 'Оновлено порядок видів участі.', 'fstu' ), '✓' ) ) {
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
		check_ajax_referer( ParticipationType_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->current_user_can_protocol() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду протоколу.', 'fstu' ) ] );
		}

		$search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
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
			return '<tr class="fstu-row"><td colspan="4" class="fstu-no-results">' . esc_html__( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ) . '</td></tr>';
		}

		$html       = '';
		$index      = $offset;
		$can_manage = ! empty( $permissions['canManage'] );
		$can_delete = ! empty( $permissions['canDelete'] );
		$can_view   = ! empty( $permissions['canView'] );

		foreach ( $items as $item ) {
			++$index;
			$participation_type_id = (int) ( $item['ParticipationType_ID'] ?? 0 );
			$drag_handle           = $can_reorder
				? '<span class="fstu-drag-handle" title="' . esc_attr__( 'Перетягніть для зміни порядку', 'fstu' ) . '">⋮⋮</span>'
				: '<span class="fstu-drag-handle fstu-drag-handle--disabled" aria-hidden="true">⋮⋮</span>';

			$actions = [];
			if ( $can_view ) {
				$actions[] = '<button type="button" class="fstu-participationtype-dropdown__item fstu-participationtype-view-btn" data-participation-type-id="' . esc_attr( (string) $participation_type_id ) . '">' . esc_html__( 'Перегляд', 'fstu' ) . '</button>';
			}

			if ( $can_manage ) {
				$actions[] = '<button type="button" class="fstu-participationtype-dropdown__item fstu-participationtype-edit-btn" data-participation-type-id="' . esc_attr( (string) $participation_type_id ) . '">' . esc_html__( 'Редагування', 'fstu' ) . '</button>';
			}

			if ( $can_delete ) {
				$actions[] = '<button type="button" class="fstu-participationtype-dropdown__item fstu-participationtype-dropdown__item--danger fstu-participationtype-delete-btn" data-participation-type-id="' . esc_attr( (string) $participation_type_id ) . '">' . esc_html__( 'Видалення', 'fstu' ) . '</button>';
			}

			$html .= '<tr class="fstu-row fstu-participationtype-row" data-participation-type-id="' . esc_attr( (string) $participation_type_id ) . '"' . ( $can_reorder ? ' draggable="true"' : '' ) . '>';
			$html .= '<td class="fstu-td fstu-td--num">' . $drag_handle . '<span class="fstu-row-number">' . esc_html( (string) $index ) . '</span></td>';
			$html .= '<td class="fstu-td fstu-td--name"><button type="button" class="fstu-participationtype-link-button fstu-participationtype-view-btn" data-participation-type-id="' . esc_attr( (string) $participation_type_id ) . '">' . esc_html( (string) ( $item['ParticipationType_Name'] ?? '' ) ) . '</button></td>';
			$html .= '<td class="fstu-td fstu-td--participation-type">' . $this->build_type_badge( (int) ( $item['ParticipationType_Type'] ?? 1 ) ) . '</td>';
			$html .= '<td class="fstu-td fstu-td--actions">';
			$html .= '<div class="fstu-participationtype-dropdown">';
			$html .= '<button type="button" class="fstu-participationtype-dropdown__toggle" aria-expanded="false" title="' . esc_attr__( 'Меню дій', 'fstu' ) . '">▼</button>';
			$html .= '<div class="fstu-participationtype-dropdown__menu">' . implode( '', $actions ) . '</div>';
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
			'participation_type_name'  => sanitize_text_field( wp_unslash( $_POST['participation_type_name'] ?? '' ) ),
			'participation_type_type'  => absint( $_POST['participation_type_type'] ?? 1 ),
			'participation_type_order' => absint( $_POST['participation_type_order'] ?? 0 ),
		];
	}

	/**
	 * @param array<string,int|string> $data
	 */
	private function validate_form_data( array $data, int $exclude_id = 0 ): string {
		$name = trim( (string) ( $data['participation_type_name'] ?? '' ) );
		$type = (int) ( $data['participation_type_type'] ?? 1 );

		if ( mb_strlen( $name ) < 2 ) {
			return __( 'Поле «Найменування» є обов’язковим.', 'fstu' );
		}

		if ( mb_strlen( $name ) > 255 ) {
			return __( 'Поле «Найменування» не може бути довшим за 255 символів.', 'fstu' );
		}

		if ( ! in_array( $type, [ 1, 2 ], true ) ) {
			return __( 'Оберіть коректний тип участі.', 'fstu' );
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
		return Capabilities::get_participation_type_permissions();
	}

	/**
	 * Повертає запис за ID.
	 *
	 * @return array<string,mixed>|null
	 */
	private function get_item_by_id( int $participation_type_id ): ?array {
		global $wpdb;

		$item = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT ParticipationType_ID, ParticipationType_DateCreate, ParticipationType_Name, ParticipationType_Order, ParticipationType_Type FROM S_ParticipationType WHERE ParticipationType_ID = %d LIMIT 1',
				$participation_type_id
			),
			ARRAY_A
		);

		return is_array( $item ) ? $item : null;
	}

	/**
	 * Перевіряє, чи існує запис з таким самим найменуванням.
	 */
	private function name_exists( string $name, int $exclude_id = 0 ): bool {
		global $wpdb;

		$sql    = 'SELECT ParticipationType_ID FROM S_ParticipationType WHERE ParticipationType_Name = %s';
		$params = [ $name ];

		if ( $exclude_id > 0 ) {
			$sql     .= ' AND ParticipationType_ID != %d';
			$params[] = $exclude_id;
		}

		$sql .= ' LIMIT 1';

		$existing_id = $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );

		return null !== $existing_id;
	}

	/**
	 * Перевіряє залежності перед видаленням.
	 */
	private function has_delete_dependencies( int $participation_type_id ): bool {
		global $wpdb;

		$usage_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM UserCalendar WHERE ParticipationType_ID = %d',
				$participation_type_id
			)
		);

		return $usage_count > 0;
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
	 * Повертає HTML-мітку типу участі.
	 */
	private function build_type_badge( int $type ): string {
		$label = $this->get_type_label( $type );
		$cls   = 1 === $type ? 'fstu-badge--type-primary' : 'fstu-badge--type-secondary';

		return '<span class="fstu-badge ' . esc_attr( $cls ) . '">' . esc_html( $label ) . '</span>';
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
	 * Повертає текстову мітку типу участі.
	 */
	private function get_type_label( int $type ): string {
		return 1 === $type
			? __( 'Змагання / походи', 'fstu' )
			: __( 'Інше', 'fstu' );
	}

	/**
	 * Чи може поточний користувач бачити модуль.
	 */
	private function current_user_can_view(): bool {
		return Capabilities::current_user_can_view_participation_type();
	}

	/**
	 * Чи може поточний користувач керувати довідником.
	 */
	private function current_user_can_manage(): bool {
		return Capabilities::current_user_can_manage_participation_type();
	}

	/**
	 * Чи може поточний користувач видаляти записи.
	 */
	private function current_user_can_delete(): bool {
		return Capabilities::current_user_can_delete_participation_type();
	}

	/**
	 * Чи може поточний користувач бачити протокол.
	 */
	private function current_user_can_protocol(): bool {
		return Capabilities::current_user_can_view_participation_type_protocol();
	}
}
