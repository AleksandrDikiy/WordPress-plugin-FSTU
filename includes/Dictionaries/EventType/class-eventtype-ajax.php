<?php

namespace FSTU\Dictionaries\EventType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Клас AJAX модуля "Довідник типів заходів".
 * Version:     1.0.0
 * Date_update: 2026-04-07
 */

class EventType_Ajax {

	private const LOG_NAME = 'EventType';

	public function init(): void {
		add_action( 'wp_ajax_fstu_eventtype_get_list', [ $this, 'handle_get_list' ] );
		add_action( 'wp_ajax_fstu_eventtype_get_protocol', [ $this, 'handle_get_protocol' ] );
		add_action( 'wp_ajax_fstu_eventtype_save', [ $this, 'handle_save' ] );
		add_action( 'wp_ajax_fstu_eventtype_delete', [ $this, 'handle_delete' ] );
	}

	private function log_action_strict( string $type, string $text, string $status ): bool {
		global $wpdb;
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
		return (bool) $inserted;
	}

	public function handle_get_list(): void {
		check_ajax_referer( EventType_List::NONCE_ACTION, 'nonce' );
		if ( ! current_user_can( 'administrator' ) ) wp_send_json_error( [ 'message' => 'Немає прав' ] );

		global $wpdb;
		$search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$page     = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page = absint( $_POST['per_page'] ?? 10 );
		$offset   = ( $page - 1 ) * $per_page;

		$where_sql = "1=1";
		$params = [];

		if ( '' !== $search ) {
			$where_sql .= " AND EventType_Name LIKE %s";
			$params[] = '%' . $wpdb->esc_like( $search ) . '%';
		}

		$count_sql = "SELECT COUNT(EventType_ID) FROM vEventType WHERE {$where_sql}";
		$total = $params ? (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) ) : (int) $wpdb->get_var( $count_sql );

		$data_sql = "SELECT EventType_ID, EventType_Name, EventType_Order FROM vEventType WHERE {$where_sql} ORDER BY EventType_Order ASC LIMIT %d OFFSET %d";
		$data_params = array_merge( $params, [ $per_page, $offset ] );
		$rows = $wpdb->get_results( $wpdb->prepare( $data_sql, ...$data_params ), ARRAY_A );

		$html = '';
		if ( empty( $rows ) ) {
			$html = '<tr><td colspan="3" style="text-align:center; color:#7f8c8d;">Даних не знайдено</td></tr>';
		} else {
			$n = $offset;
			foreach ( $rows as $row ) {
				$n++;
				$id    = (int) $row['EventType_ID'];
				$name  = esc_html( $row['EventType_Name'] );
				$order = (int) $row['EventType_Order'];

				$actions = '
				<div class="fstu-dropdown">
					<button type="button" class="fstu-dropdown-toggle">Дії ▼</button>
					<ul class="fstu-dropdown-menu">
						<li><button type="button" class="fstu-action-edit" data-id="' . $id . '" data-name="' . esc_attr($name) . '" data-order="' . $order . '">✏️ Редагувати</button></li>
						<li><button type="button" class="fstu-action-delete fstu-text-danger" data-id="' . $id . '">❌ Видалити</button></li>
					</ul>
				</div>';

				$html .= "<tr>
					<td class=\"fstu-th--num\">{$n}</td>
					<td style=\"font-weight:500;\">{$name}</td>
					<td class=\"fstu-th--actions\">{$actions}</td>
				</tr>";
			}
		}

		wp_send_json_success( [ 'html' => $html, 'total' => $total, 'page' => $page, 'per_page' => $per_page, 'total_pages' => ceil( $total / max( 1, $per_page ) ) ] );
	}

	public function handle_save(): void {
		check_ajax_referer( EventType_List::NONCE_ACTION, 'nonce' );
		if ( ! current_user_can( 'administrator' ) ) wp_send_json_error( [ 'message' => 'Немає прав' ] );

		$id    = absint( $_POST['id'] ?? 0 );
		$name  = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$order = absint( $_POST['order'] ?? 0 );

		if ( empty( $name ) ) wp_send_json_error( [ 'message' => 'Найменування є обов\'язковим.' ] );

		global $wpdb;
		$wpdb->query( 'START TRANSACTION' ); 

		try {
			if ( $id > 0 ) {
				$result = $wpdb->update( 'S_EventType', [ 'EventType_Name' => $name, 'EventType_Order' => $order ], [ 'EventType_ID' => $id ], [ '%s', '%d' ], [ '%d' ] );
				if ( false === $result ) throw new \Exception( 'Помилка оновлення БД.' );

				if ( ! $this->log_action_strict( 'U', "Оновлено тип заходу ID:{$id} -> {$name}", '✓' ) ) throw new \Exception( 'Помилка запису протоколу.' );
			} else {
				$result = $wpdb->insert( 'S_EventType', [ 'EventType_DateCreate' => current_time('mysql'), 'EventType_Name' => $name, 'EventType_Order' => $order ], [ '%s', '%s', '%d' ] );
				if ( ! $result ) throw new \Exception( 'Помилка збереження БД.' );

				if ( ! $this->log_action_strict( 'I', "Додано тип заходу: {$name}", '✓' ) ) throw new \Exception( 'Помилка запису протоколу.' );
			}

			$wpdb->query( 'COMMIT' );
			wp_send_json_success( [ 'message' => 'Дані успішно збережено!' ] );

		} catch ( \Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			$this->log_action_strict( $id > 0 ? 'U' : 'INSERT', "Помилка збереження типу заходу {$name}", 'db_error' );
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}
	}

	public function handle_delete(): void {
		check_ajax_referer( EventType_List::NONCE_ACTION, 'nonce' );
		if ( ! current_user_can( 'administrator' ) ) wp_send_json_error( [ 'message' => 'Немає прав' ] );

		$id = absint( $_POST['id'] ?? 0 );
		if ( ! $id ) wp_send_json_error( [ 'message' => 'ID не вказано.' ] );

		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );

		try {
			$deleted = $wpdb->delete( 'S_EventType', [ 'EventType_ID' => $id ], [ '%d' ] );
			if ( ! $deleted ) throw new \Exception( 'Помилка видалення з БД.' );

			if ( ! $this->log_action_strict( 'D', "Видалено тип заходу ID:{$id}", '✓' ) ) throw new \Exception( 'Помилка запису протоколу.' );

			$wpdb->query( 'COMMIT' );
			wp_send_json_success( [ 'message' => 'Запис видалено.' ] );

		} catch ( \Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			$this->log_action_strict( 'D', "Помилка видалення ID:{$id}", 'db_error' );
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}
	}

	public function handle_get_protocol(): void {
		check_ajax_referer( EventType_List::NONCE_ACTION, 'nonce' );
		if ( ! current_user_can( 'administrator' ) ) wp_send_json_error( [ 'message' => 'Немає прав.' ] );

		global $wpdb;
		$search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$page     = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page = absint( $_POST['per_page'] ?? 10 );
		$offset   = ( $page - 1 ) * $per_page;

		$where_sql = "l.Logs_Name = %s";
		$params    = [ self::LOG_NAME ];

		if ( '' !== $search ) {
			$where_sql .= " AND (l.Logs_Text LIKE %s OR u.FIO LIKE %s)";
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		$count_sql = "SELECT COUNT(l.Logs_ID) FROM Logs l LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID WHERE {$where_sql}";
		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );

		$data_sql = "SELECT l.Logs_DateCreate, l.Logs_Type, l.Logs_Name, l.Logs_Text, l.Logs_Error, u.FIO FROM Logs l LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID WHERE {$where_sql} ORDER BY l.Logs_DateCreate DESC LIMIT %d OFFSET %d";
		$data_params = array_merge( $params, [ $per_page, $offset ] );
		$rows = $wpdb->get_results( $wpdb->prepare( $data_sql, ...$data_params ), ARRAY_A );

		$html = '';
		if ( empty( $rows ) ) {
			$html = '<tr><td colspan="6" style="text-align:center; color:#7f8c8d;">Логів не знайдено.</td></tr>';
		} else {
			foreach ( $rows as $row ) {
				$date    = esc_html( gmdate( 'd.m.Y H:i', strtotime( $row['Logs_DateCreate'] ) ) );
				$type    = esc_html( $row['Logs_Type'] );
				$message = esc_html( $row['Logs_Text'] );
				$status  = esc_html( $row['Logs_Error'] );
				$fio     = esc_html( $row['FIO'] ?? 'Система' );

				$status_html = ( $status === '✓' ) ? '<span style="color:#27ae60;font-weight:bold;">✓</span>' : '<span style="color:#e74c3c;">' . $status . '</span>';
				$html .= "<tr><td>{$date}</td><td style=\"text-align:center;\"><strong>{$type}</strong></td><td>" . esc_html(self::LOG_NAME) . "</td><td>{$message}</td><td style=\"text-align:center;\">{$status_html}</td><td>{$fio}</td></tr>";
			}
		}

		wp_send_json_success( [ 'html' => $html, 'total' => $total, 'page' => $page, 'per_page' => $per_page, 'total_pages' => ceil( $total / max( 1, $per_page ) ) ] );
	}
}