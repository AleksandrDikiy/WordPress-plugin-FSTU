<?php
/**
 * AJAX-обробник довідника "Види туризму".
 *
 * Містить виключно:
 * — реєстрацію WordPress AJAX-хуків;
 * — валідацію nonce та прав доступу;
 * — безпечні SQL-запити через $wpdb->prepare();
 * — атомарне логування у таблицю Logs (транзакції);
 * — повернення даних через wp_send_json_success / wp_send_json_error.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-07
 *
 * @package FSTU\Dictionaries\TourismType
 */

namespace FSTU\Dictionaries\TourismType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TourismType_Ajax
 */
class TourismType_Ajax {

	// -------------------------------------------------------------------------
	// Константи
	// -------------------------------------------------------------------------

	/** Ідентифікатор модуля у таблиці Logs. */
	private const LOG_NAME = 'TourismType';

	/** Ім'я nonce для всіх AJAX-запитів модуля. */
	private const NONCE_ACTION = 'fstu_tourismtype_nonce';

	// -------------------------------------------------------------------------
	// Ініціалізація хуків
	// -------------------------------------------------------------------------

	/**
	 * Реєструє AJAX-хуки WordPress.
	 * Усі дії доступні лише авторизованим користувачам (priv).
	 */
	public function init(): void {
		$actions = [
			'fstu_tourismtype_get_list'     => 'handle_get_list',
			'fstu_tourismtype_get_item'      => 'handle_get_item',
			'fstu_tourismtype_save'          => 'handle_save',
			'fstu_tourismtype_delete'        => 'handle_delete',
			'fstu_tourismtype_get_protocol'  => 'handle_get_protocol',
		];

		foreach ( $actions as $action => $method ) {
			add_action( 'wp_ajax_' . $action, [ $this, $method ] );
		}
	}

	// =========================================================================
	// PUBLIC AJAX HANDLERS
	// =========================================================================

	/**
	 * Отримати список видів туризму (з пагінацією та пошуком).
	 */
	public function handle_get_list(): void {
		$this->verify_nonce();
		$this->require_read_access();

		global $wpdb;

		$page     = max( 1, absint( $_POST['page']     ?? 1 ) );
		$per_page = $this->sanitize_per_page( $_POST['per_page'] ?? 10 );
		$search   = sanitize_text_field( $_POST['search'] ?? '' );
		$offset   = ( $page - 1 ) * $per_page;

		// ---- Базовий SQL ----
		$where  = '1=1';
		$params = [];

		if ( $search !== '' ) {
			$where   .= ' AND TourismType_Name LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $search ) . '%';
		}

		// ---- Загальна кількість ----
		$count_sql = "SELECT COUNT(*) FROM vTourismType WHERE {$where}";
		$total     = (int) ( $params
			? $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) ) // phpcs:ignore
			: $wpdb->get_var( $count_sql ) );

		// ---- Дані сторінки ----
		$data_sql = "SELECT TourismType_ID, TourismType_Name, TourismType_Number, TourismType_Order
		             FROM vTourismType
		             WHERE {$where}
		             ORDER BY TourismType_Order ASC, TourismType_Name ASC
		             LIMIT %d OFFSET %d";

		$data_params   = array_merge( $params, [ $per_page, $offset ] );
		$items         = $wpdb->get_results( $wpdb->prepare( $data_sql, ...$data_params ) ); // phpcs:ignore

		wp_send_json_success( [
			'items'       => $items ?? [],
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => (int) ceil( $total / $per_page ),
		] );
	}

	// -------------------------------------------------------------------------

	/**
	 * Отримати один запис за ID (для форми редагування у модальному вікні).
	 */
	public function handle_get_item(): void {
		$this->verify_nonce();
		$this->require_read_access();

		global $wpdb;

		$id = absint( $_POST['id'] ?? 0 );
		if ( $id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		$item = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT TourismType_ID, TourismType_Name, TourismType_Number, TourismType_Order
				 FROM vTourismType
				 WHERE TourismType_ID = %d',
				$id
			)
		);

		if ( ! $item ) {
			wp_send_json_error( [ 'message' => __( 'Запис не знайдено.', 'fstu' ) ] );
		}

		wp_send_json_success( [ 'item' => $item ] );
	}

	// -------------------------------------------------------------------------

	/**
	 * Зберегти вид туризму (INSERT або UPDATE).
	 * Використовує транзакцію: CRUD + Logs в одній атомарній операції.
	 */
	public function handle_save(): void {
		$this->verify_nonce();
		$this->require_write_access();

		// ---- Honeypot (захист від ботів) ----
		if ( ! empty( $_POST['fstu_website'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Помилка перевірки форми.', 'fstu' ) ] );
		}

		global $wpdb;

		// ---- Санітизація вхідних даних ----
		$id     = absint( $_POST['id']   ?? 0 );
		$name   = sanitize_text_field( $_POST['TourismType_Name']   ?? '' );
		$number = absint( $_POST['TourismType_Number'] ?? 0 );
		$order  = absint( $_POST['TourismType_Order']  ?? 0 );

		// ---- Валідація ----
		if ( $name === '' ) {
			wp_send_json_error( [ 'message' => __( 'Найменування є обов\'язковим полем.', 'fstu' ) ] );
		}

		$is_insert = ( $id === 0 );

		// =========================================================
		// ТРАНЗАКЦІЯ: CRUD + лог в одній атомарній операції
		// =========================================================
		$wpdb->query( 'START TRANSACTION' );

		if ( $is_insert ) {
			// --- INSERT ---
			$crud_result = $wpdb->insert(
				'S_TourismType',
				[
					'TourismType_DateCreate' => current_time( 'mysql' ),
					'TourismType_Name'       => $name,
					'TourismType_Number'     => $number, // ВИПРАВЛЕНО: Записуємо 0 замість NULL
					'TourismType_Order'      => $order,  // ВИПРАВЛЕНО: Записуємо 0 замість NULL
				],
				[ '%s', '%s', '%d', '%d' ]
			);
			$new_id      = (int) $wpdb->insert_id;
			$log_type    = 'I';
			$log_text    = sprintf( 'Додано вид туризму: "%s" (ID=%d)', $name, $new_id );
		} else {
			// --- UPDATE ---
			$crud_result = $wpdb->update(
				'S_TourismType',
				[
					'TourismType_Name'   => $name,
					'TourismType_Number' => $number, // ВИПРАВЛЕНО
					'TourismType_Order'  => $order,  // ВИПРАВЛЕНО
				],
				[ 'TourismType_ID' => $id ],
				[ '%s', '%d', '%d' ],
				[ '%d' ]
			);
			$log_type = 'U';
			$log_text = sprintf( 'Оновлено вид туризму: "%s" (ID=%d)', $name, $id );
		}

		// Перевіряємо на false (0 рядків оновлено — це не помилка, це 그냥 відсутність змін)
		if ( $crud_result === false ) {
			$wpdb->query( 'ROLLBACK' );
			
			// Логуємо саму помилку БД (не виводячи її користувачу)
			$this->log_action( $log_type, "Помилка БД при збереженні '{$name}'", 'db_error' );
			wp_send_json_error( [ 'message' => __( 'Помилка збереження даних.', 'fstu' ) ] );
		}

		// --- Лог у транзакції ---
		$log_result = $this->log_action_transactional( $log_type, $log_text, '✓' );
		if ( ! $log_result ) {
			$wpdb->query( 'ROLLBACK' );
			wp_send_json_error( [ 'message' => __( 'Помилка збереження протоколу.', 'fstu' ) ] );
		}

		$wpdb->query( 'COMMIT' );
		// =========================================================

		wp_send_json_success( [
			'message'   => $is_insert
				? __( 'Вид туризму успішно додано.', 'fstu' )
				: __( 'Вид туризму успішно оновлено.', 'fstu' ),
			'is_insert' => $is_insert,
		] );
	}

	// -------------------------------------------------------------------------

	/**
	 * Видалити вид туризму.
	 * Перевіряє залежності, використовує транзакцію.
	 */
	public function handle_delete(): void {
		$this->verify_nonce();
		$this->require_admin_access();

		global $wpdb;

		$id = absint( $_POST['id'] ?? 0 );
		if ( $id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ідентифікатор запису.', 'fstu' ) ] );
		}

		// ---- Отримати назву для логу ----
		$name = (string) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT TourismType_Name FROM S_TourismType WHERE TourismType_ID = %d',
				$id
			)
		);

		// ---- Перевірка залежностей ----
		$has_calendar = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM Calendar WHERE TourismType_ID = %d LIMIT 1',
				$id
			)
		);
		$has_commission = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM S_Commission WHERE TourismType_ID = %d LIMIT 1',
				$id
			)
		);

		if ( $has_calendar > 0 || $has_commission > 0 ) {
			// Логуємо заблоковане видалення (поза транзакцією — просте логування)
			$this->log_action(
				'DELETE',
				sprintf( 'Заблоковано видалення виду туризму: "%s" (ID=%d) — є залежні записи', $name, $id ),
				'dependency'
			);
			wp_send_json_error( [
				'message' => __( 'Неможливо видалити: є пов\'язані заходи або комісії.', 'fstu' ),
			] );
		}

		// =========================================================
		// ТРАНЗАКЦІЯ: DELETE + лог
		// =========================================================
		$wpdb->query( 'START TRANSACTION' );

		$crud_result = $wpdb->delete(
			'S_TourismType',
			[ 'TourismType_ID' => $id ],
			[ '%d' ]
		);

		if ( $crud_result === false ) {
			$wpdb->query( 'ROLLBACK' );
			wp_send_json_error( [ 'message' => __( 'Помилка видалення запису.', 'fstu' ) ] );
		}

		$log_result = $this->log_action_transactional(
			'D',
			sprintf( 'Видалено вид туризму: "%s" (ID=%d)', $name, $id ),
			'✓'
		);

		if ( ! $log_result ) {
			$wpdb->query( 'ROLLBACK' );
			wp_send_json_error( [ 'message' => __( 'Помилка видалення запису.', 'fstu' ) ] );
		}

		$wpdb->query( 'COMMIT' );
		// =========================================================

		wp_send_json_success( [
			'message' => __( 'Вид туризму успішно видалено.', 'fstu' ),
		] );
	}

	// -------------------------------------------------------------------------

	/**
	 * Отримати журнал операцій (ПРОТОКОЛ) для цього модуля.
	 */
	public function handle_get_protocol(): void {
		$this->verify_nonce();
		$this->require_admin_access();

		global $wpdb;

		$page     = max( 1, absint( $_POST['page']     ?? 1 ) );
		$per_page = $this->sanitize_per_page( $_POST['per_page'] ?? 10 );
		$search   = sanitize_text_field( $_POST['search'] ?? '' );
		$offset   = ( $page - 1 ) * $per_page;

		// ---- Умова пошуку ----
		$where  = 'l.Logs_Name = %s';
		$params = [ self::LOG_NAME ];

		if ( $search !== '' ) {
			$where   .= ' AND (l.Logs_Text LIKE %s OR u.FIO LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		// ---- Загальна кількість ----
		$count_sql = "SELECT COUNT(*)
		              FROM Logs l
		              LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
		              WHERE {$where}";

		$total = (int) $wpdb->get_var(
			$wpdb->prepare( $count_sql, ...$params ) // phpcs:ignore
		);

		// ---- Дані сторінки ----
		$data_sql = "SELECT l.Logs_DateCreate, l.Logs_Type, l.Logs_Name,
		                    l.Logs_Text, l.Logs_Error, u.FIO
		             FROM Logs l
		             LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
		             WHERE {$where}
		             ORDER BY l.Logs_DateCreate DESC
		             LIMIT %d OFFSET %d";

		$data_params = array_merge( $params, [ $per_page, $offset ] );
		$items       = $wpdb->get_results(
			$wpdb->prepare( $data_sql, ...$data_params ) // phpcs:ignore
		);

		wp_send_json_success( [
			'items'       => $items ?? [],
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => (int) ceil( $total / $per_page ),
		] );
	}

	// =========================================================================
	// PRIVATE HELPERS
	// =========================================================================

	/**
	 * Перевіряє nonce. Завершує виконання з помилкою, якщо nonce недійсний.
	 */
	private function verify_nonce(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
	}

	/**
	 * Вимагає права на читання (будь-який авторизований).
	 */
	private function require_read_access(): void {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'Необхідна авторизація.', 'fstu' ) ] );
		}
	}

	/**
	 * Вимагає права на запис (адмін або редактор).
	 */
	private function require_write_access(): void {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для цієї операції.', 'fstu' ) ] );
		}
	}

	/**
	 * Вимагає прав адміністратора.
	 */
	private function require_admin_access(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Недостатньо прав для цієї операції.', 'fstu' ) ] );
		}
	}

	/**
	 * Санітизує кількість записів на сторінку.
	 * Допустимі значення: 10, 15, 25, 50. За замовчуванням — 10.
	 *
	 * @param  mixed $value Вхідне значення.
	 * @return int          Безпечне значення.
	 */
	private function sanitize_per_page( mixed $value ): int {
		$allowed = [ 10, 15, 25, 50 ];
		$val     = absint( $value );
		return in_array( $val, $allowed, true ) ? $val : 10;
	}

	// -------------------------------------------------------------------------
	// Логування
	// -------------------------------------------------------------------------

	/**
	 * Записує подію до таблиці Logs (поза транзакцією).
	 * Використовується для логування заблокованих операцій або помилок.
	 *
	 * @param string $type   Тип операції (INSERT | UPDATE | DELETE).
	 * @param string $text   Опис події (без stack trace і технічних деталей БД).
	 * @param string $status Статус ('✓', 'dependency', 'error' тощо).
	 */
	private function log_action( string $type, string $text, string $status ): void {
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
	 * Записує подію до таблиці Logs ВСЕРЕДИНІ відкритої транзакції.
	 * Повертає false у разі невдачі — сигнал для ROLLBACK.
	 *
	 * @param string $type   Тип операції.
	 * @param string $text   Опис події.
	 * @param string $status Статус.
	 * @return bool          true — лог записано, false — не записано.
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

		return $result !== false;
	}
}
