<?php
/**
 * AJAX-обробники модуля "Довідник клубів ФСТУ".
 * Таблиця S_Club: Club_ID, Club_Name, Club_Adr, Club_WWW.
 * Всі запити виключно через $wpdb->prepare().
 *
 * Дії:
 *   fstu_clubs_get_list   — список (публічний)
 *   fstu_clubs_get_single — один запис (публічний)
 *   fstu_clubs_save       — додати / оновити (userregistrar, administrator)
 *   fstu_clubs_delete     — видалити (тільки administrator)
 *
	 * Version:     1.2.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Clubs
 */

namespace FSTU\Clubs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Clubs_Ajax {

	private const MAX_PER_PAGE = 200; // довідник невеликий — дозволяємо більше
	private const PROTOCOL_PER_PAGE_ALLOWED = [ 10, 20, 50 ];
	private const LOG_NAME = 'Clubs';
	private const EDIT_ROLES   = [ 'administrator', 'userregistrar' ];
	private const DELETE_ROLES = [ 'administrator' ];

	public function init(): void {
		// Список — публічний
		add_action( 'wp_ajax_fstu_clubs_get_list',        [ $this, 'handle_get_list' ] );
		add_action( 'wp_ajax_nopriv_fstu_clubs_get_list', [ $this, 'handle_get_list' ] );

		// Один запис — публічний (для модального перегляду)
		add_action( 'wp_ajax_fstu_clubs_get_single',        [ $this, 'handle_get_single' ] );
		add_action( 'wp_ajax_nopriv_fstu_clubs_get_single', [ $this, 'handle_get_single' ] );

		// Збереження (додати / оновити) — потребує прав
		add_action( 'wp_ajax_fstu_clubs_save', [ $this, 'handle_save' ] );
		add_action( 'wp_ajax_fstu_clubs_get_protocol', [ $this, 'handle_get_protocol' ] );

		// Видалення — тільки адмін
		add_action( 'wp_ajax_fstu_clubs_delete', [ $this, 'handle_delete' ] );
	}

	// ─── Публічні AJAX-обробники ──────────────────────────────────────────────

	/**
	 * Повертає HTML рядків таблиці клубів.
	 * Підтримує пошук за Club_Name.
	 */
	public function handle_get_list(): void {
		check_ajax_referer( Clubs_List::NONCE_ACTION, 'nonce' );

		$search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$page     = max( 1, absint( $_POST['page']     ?? 1 ) );
		$per_page = min( absint( $_POST['per_page'] ?? 10 ), self::MAX_PER_PAGE );
		if ( $per_page < 1 ) {
			$per_page = 10;
		}

		global $wpdb;

		// ── WHERE ─────────────────────────────────────────────────────────────
		$where  = '';
		$params = [];

		if ( '' !== $search ) {
			$like   = '%' . $wpdb->esc_like( $search ) . '%';
			$where  = 'WHERE Club_Name LIKE %s';
			$params = [ $like ];
		}

		// COUNT
		$cnt_sql = "SELECT COUNT(*) FROM S_Club {$where}";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		$total = $params
			? (int) $wpdb->get_var( $wpdb->prepare( $cnt_sql, ...$params ) )
			: (int) $wpdb->get_var( $cnt_sql ); // phpcs:ignore

		$offset       = ( $page - 1 ) * $per_page;
		$data_params  = array_merge( $params, [ $per_page, $offset ] );
		$data_sql     = "SELECT Club_ID, Club_Name, Club_WWW, Club_Adr FROM S_Club {$where} ORDER BY Club_Name ASC LIMIT %d OFFSET %d";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( $wpdb->prepare( $data_sql, ...$data_params ), ARRAY_A );

		// Право на редагування/видалення визначається на сервері
		$can_edit   = $this->has_any_role( self::EDIT_ROLES );
		$can_delete = $this->has_any_role( self::DELETE_ROLES );

		wp_send_json_success( [
			'html'        => $this->build_rows( $rows ?? [], $offset, $can_edit, $can_delete ),
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => max( 1, (int) ceil( $total / max( 1, $per_page ) ) ),
		] );
	}

	/**
	 * Повертає JSON з даними одного клубу (для перегляду / заповнення форми).
	 */
	public function handle_get_single(): void {
		check_ajax_referer( Clubs_List::NONCE_ACTION, 'nonce' );

		$club_id = absint( $_POST['club_id'] ?? 0 );

		if ( $club_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Невірний ідентифікатор клубу.' ] );
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$club = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT Club_ID, Club_Name, Club_WWW, Club_Adr FROM S_Club WHERE Club_ID = %d LIMIT 1",
				$club_id
			),
			ARRAY_A
		);

		if ( ! $club ) {
			wp_send_json_error( [ 'message' => 'Клуб не знайдено.' ] );
		}

		// Кількість учасників клубу
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$members = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM UserClub WHERE Club_ID = %d", $club_id )
		);

		wp_send_json_success( [
			'club_id'      => (int) $club['Club_ID'],
			'club_name'    => $club['Club_Name'],   // сирий текст — екранується в JS
			'club_adr'     => $club['Club_Adr']  ?? '',
			'club_www'     => $club['Club_WWW']  ?? '',
			'member_count' => $members,
		] );
	}

	/**
	 * Зберігає клуб (INSERT або UPDATE).
	 * Доступ: administrator, userregistrar.
	 */
	public function handle_save(): void {
		check_ajax_referer( Clubs_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->has_any_role( self::EDIT_ROLES ) ) {
			wp_send_json_error( [ 'message' => 'Недостатньо прав для збереження.' ] );
		}

		// Санітизація
		$club_id  = absint( $_POST['club_id'] ?? 0 );
		$name     = sanitize_text_field( wp_unslash( $_POST['club_name'] ?? '' ) );
		$adr      = sanitize_text_field( wp_unslash( $_POST['club_adr']  ?? '' ) );
		$www      = esc_url_raw( wp_unslash( $_POST['club_www']  ?? '' ) );

		// Валідація
		if ( mb_strlen( $name ) < 2 ) {
			wp_send_json_error( [ 'message' => 'Назва клубу обов\'язкова (мінімум 2 символи).' ] );
		}

		global $wpdb;
		$data   = [ 'Club_Name' => $name, 'Club_Adr' => $adr, 'Club_WWW' => $www ];
		$format = [ '%s', '%s', '%s' ];

		if ( $club_id > 0 ) {
			$existing_name = (string) $wpdb->get_var(
				$wpdb->prepare( 'SELECT Club_Name FROM S_Club WHERE Club_ID = %d LIMIT 1', $club_id )
			);

			// UPDATE
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$result = $wpdb->update( 'S_Club', $data, [ 'Club_ID' => $club_id ], $format, [ '%d' ] );

			if ( false === $result ) {
				$this->log_operation( 'UPDATE', sprintf( 'Помилка оновлення клубу: %s', $existing_name ?: $name ), 'Помилка оновлення' );
				wp_send_json_error( [ 'message' => 'Помилка оновлення. Зверніться до адміністратора.' ] );
			}

			$this->log_operation( 'UPDATE', sprintf( 'Оновлено клуб: %s', $name ), '✓' );

			wp_send_json_success( [
				'message'  => 'Клуб успішно оновлено.',
				'club_id'  => $club_id,
				'action'   => 'updated',
			] );
		} else {
			// INSERT
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$result = $wpdb->insert( 'S_Club', $data, $format );

			if ( ! $result ) {
				$this->log_operation( 'INSERT', sprintf( 'Помилка додавання клубу: %s', $name ), 'Помилка додавання' );
				wp_send_json_error( [ 'message' => 'Помилка додавання. Зверніться до адміністратора.' ] );
			}

			$this->log_operation( 'INSERT', sprintf( 'Додано новий клуб: %s', $name ), '✓' );

			wp_send_json_success( [
				'message'  => 'Клуб успішно додано.',
				'club_id'  => (int) $wpdb->insert_id,
				'action'   => 'inserted',
			] );
		}
	}

	/**
	 * Повертає протокол (журнал операцій) модуля клубів.
	 * Колонки: Дата, Тип, Операція, Повідомлення, Статус, Користувач.
	 */
	public function handle_get_protocol(): void {
		check_ajax_referer( Clubs_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->has_any_role( self::DELETE_ROLES ) ) {
			wp_send_json_error( [ 'message' => 'Немає прав для перегляду протоколу.' ] );
		}

		$page        = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page    = absint( $_POST['per_page'] ?? 10 );
		$filter_name = sanitize_text_field( wp_unslash( $_POST['filter_name'] ?? '' ) );

		if ( ! in_array( $per_page, self::PROTOCOL_PER_PAGE_ALLOWED, true ) ) {
			$per_page = 10;
		}

		global $wpdb;

		$where  = ' WHERE l.Logs_Name = %s';
		$params = [ self::LOG_NAME ];

		if ( '' !== $filter_name ) {
			$like = '%' . $wpdb->esc_like( $filter_name ) . '%';
			$where .= ' AND (l.Logs_Text LIKE %s OR u.FIO LIKE %s)';
			$params[] = $like;
			$params[] = $like;
		}

		$count_sql = "SELECT COUNT(*)
			FROM Logs l
			LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
			{$where}";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );

		$offset      = ( $page - 1 ) * $per_page;
		$list_params = array_merge( $params, [ $per_page, $offset ] );
		$list_sql    = "SELECT l.Logs_DateCreate, l.Logs_Type, l.Logs_Name, l.Logs_Text, l.Logs_Error, u.FIO
			FROM Logs l
			LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
			{$where}
			ORDER BY l.Logs_DateCreate DESC
			LIMIT %d OFFSET %d";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$items = $wpdb->get_results( $wpdb->prepare( $list_sql, ...$list_params ), ARRAY_A );

		wp_send_json_success( [
			'items'       => is_array( $items ) ? $items : [],
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => max( 1, (int) ceil( $total / max( 1, $per_page ) ) ),
		] );
	}

	/**
	 * Видаляє клуб.
	 * Доступ: тільки administrator.
	 * КРИТИЧНО: перевіряємо, чи немає прив'язаних учасників (UserClub).
	 */
	public function handle_delete(): void {
		check_ajax_referer( Clubs_List::NONCE_ACTION, 'nonce' );

		if ( ! $this->has_any_role( self::DELETE_ROLES ) ) {
			wp_send_json_error( [ 'message' => 'Видалення доступне тільки адміністратору.' ] );
		}

		$club_id = absint( $_POST['club_id'] ?? 0 );

		if ( $club_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Невірний ідентифікатор.' ] );
		}

		global $wpdb;
		$club_name = (string) $wpdb->get_var(
			$wpdb->prepare( 'SELECT Club_Name FROM S_Club WHERE Club_ID = %d LIMIT 1', $club_id )
		);

		// Перевірка прив'язаних учасників
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$members = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM UserClub WHERE Club_ID = %d", $club_id )
		);

		if ( $members > 0 ) {
			$this->log_operation( 'DELETE', sprintf( 'Спроба видалення клубу з учасниками: %s', $club_name ?: ('ID ' . $club_id) ), 'Клуб має привʼязаних учасників' );
			wp_send_json_error( [
				'message' => sprintf(
					'Неможливо видалити: клуб має %d учасників. Спочатку відкріпіть учасників.',
					$members
				),
			] );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->delete( 'S_Club', [ 'Club_ID' => $club_id ], [ '%d' ] );

		if ( false === $result ) {
			$this->log_operation( 'DELETE', sprintf( 'Помилка видалення клубу: %s', $club_name ?: ('ID ' . $club_id) ), 'Помилка видалення' );
			wp_send_json_error( [ 'message' => 'Помилка видалення. Зверніться до адміністратора.' ] );
		}

		$this->log_operation( 'DELETE', sprintf( 'Видалено клуб: %s', $club_name ?: ('ID ' . $club_id) ), '✓' );

		wp_send_json_success( [
			'message' => 'Клуб успішно видалено.',
			'club_id' => $club_id,
		] );
	}

	// ─── Приватні допоміжні методи ────────────────────────────────────────────

	/**
	 * Перевіряє наявність хоча б однієї ролі у поточного користувача.
	 *
	 * @param string[] $roles
	 */
	private function has_any_role( array $roles ): bool {
		$user = wp_get_current_user();
		return $user->exists() && (bool) array_intersect( $roles, (array) $user->roles );
	}

	/**
	 * Записує дію користувача у таблицю Logs.
	 */
	private function log_operation( string $type, string $text, string $status ): void {
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

	/**
	 * Будує HTML рядки таблиці клубів для передачі у JS.
	 *
	 * @param array<int, array<string,mixed>> $rows      Рядки з БД.
	 * @param int                             $offset    Зміщення для нумерації (починається з $offset + 1).
	 * @param bool                            $can_edit   Чи показувати кнопки редагування.
	 * @param bool                            $can_delete Чи показувати кнопку видалення.
	 * @return string HTML рядків <tr>.
	 */
	private function build_rows( array $rows, int $offset, bool $can_edit, bool $can_delete ): string {
		if ( empty( $rows ) ) {
			$colspan = ( $can_edit || $can_delete ) ? '4' : '3';
			return '<tr><td colspan="' . $colspan . '" class="fstu-no-results">Клубів не знайдено.</td></tr>';
		}

		$html = '';
		$num  = $offset;

		foreach ( $rows as $row ) {
			$num++;
			$club_id = (int) $row['Club_ID'];
			$name    = esc_html( $row['Club_Name'] );
			$adr     = esc_html( $row['Club_Adr'] ?? '' );
			$www     = $row['Club_WWW'] ?? '';

			// Назва — посилання якщо є сайт, інакше клікабельна для перегляду
			$name_link = sprintf(
				'<a href="#" class="fstu-club-name-link" data-club-id="%d">%s</a>',
				$club_id,
				$name
			);

			// Адреса
			$adr_html = $adr ?: '<span class="fstu-text-muted">—</span>';

			// Сайт (зовнішнє посилання)
			$www_html = $www
				? '<a href="' . esc_url( $www ) . '" class="fstu-ext-link" target="_blank" rel="noopener noreferrer"
				      title="' . esc_attr( $www ) . '">🌐</a>'
				: '';

			// Dropdown-меню дій
			$actions = sprintf(
				'<li><a href="#" class="fstu-btn-action fstu-btn--view" data-club-id="%1$d">🔎 Перегляд</a></li>',
				$club_id
			);

			if ( $can_edit ) {
				$actions .= sprintf(
					'<li><a href="#" class="fstu-btn-action fstu-btn--edit" data-club-id="%d">📝 Редагування</a></li>',
					$club_id
				);
			}

			if ( $can_delete ) {
				$actions .= '<li><hr class="fstu-clubs-opts-divider"></li>';
				$actions .= sprintf(
					'<li><a href="#" class="fstu-btn-action fstu-btn--delete" data-club-id="%d">❌ Видалення</a></li>',
					$club_id
				);
			}

			$actions_td = ( $can_edit || $can_delete )
				? '<td class="fstu-td fstu-td--actions"><div class="fstu-clubs-opts"><button type="button" class="fstu-clubs-opts-btn" title="Дії" aria-label="Дії">▼</button><ul class="fstu-clubs-opts-list">' . $actions . '</ul></div></td>'
				: '';

			$html .= "
			<tr class=\"fstu-row\" data-club-id=\"{$club_id}\">
				<td class=\"fstu-td fstu-td--num\">{$num}</td>
				<td class=\"fstu-td fstu-td--name\">{$name_link} {$www_html}</td>
				<td class=\"fstu-td fstu-td--adr\">{$adr_html}</td>
				{$actions_td}
			</tr>";
		}

		return $html;
	}
}
