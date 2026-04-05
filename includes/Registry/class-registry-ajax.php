<?php
/**
 * AJAX-обробники модуля "Реєстр членів ФСТУ".
 * Всі запити до БД виконуються виключно через $wpdb->prepare().
 *
 * Version:     1.0.0
 * Date_update: 2026-04-03
 *
 * @package FSTU\Registry
 */

namespace FSTU\Registry;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Registry_Ajax {

	/** Кількість записів на сторінці за замовчуванням. */
	private const DEFAULT_PER_PAGE = 10;

	/** Максимально допустима кількість записів на сторінці (захист від DDoS). */
	private const MAX_PER_PAGE = 100;

	/**
	 * Реєструє AJAX хуки WordPress.
	 */
    /**
     * Реєструє AJAX хуки WordPress.
     */
    public function init(): void {
        // Отримання списку членів (авторизовані та гості)
        add_action( 'wp_ajax_fstu_get_registry',        [ $this, 'handle_get_registry' ] );
        add_action( 'wp_ajax_nopriv_fstu_get_registry', [ $this, 'handle_get_registry' ] );

        // Перевірка email при реєстрації
        add_action( 'wp_ajax_fstu_check_email',        [ $this, 'handle_check_email' ] );
        add_action( 'wp_ajax_nopriv_fstu_check_email', [ $this, 'handle_check_email' ] );

        // Відправка заявки на вступ (тільки незареєстровані)
        add_action( 'wp_ajax_nopriv_fstu_submit_application', [ $this, 'handle_submit_application' ] );
        add_action( 'wp_ajax_fstu_submit_application',        [ $this, 'handle_submit_application' ] );

        // Завантаження ОФСТ по регіону
        add_action( 'wp_ajax_fstu_get_units_by_region',        [ $this, 'handle_get_units_by_region' ] );
        add_action( 'wp_ajax_nopriv_fstu_get_units_by_region', [ $this, 'handle_get_units_by_region' ] );
        // Завантаження міст по регіону
        add_action( 'wp_ajax_fstu_get_cities_by_region',        [ $this, 'handle_get_cities_by_region' ] );
        add_action( 'wp_ajax_nopriv_fstu_get_cities_by_region', [ $this, 'handle_get_cities_by_region' ] );
    }

    // ─── Публічні AJAX обробники ─────────────────────────────────────────────

    /**
     * Отримує список ОФСТ (осередків) для обраної області.
     */
    public function handle_get_units_by_region(): void {
        check_ajax_referer( Registry_List::NONCE_ACTION, 'nonce' );

        $region_id = absint( $_POST['region_id'] ?? 0 );

        if ( ! $region_id ) {
            wp_send_json_error( [ 'message' => 'Невірна область.' ] );
        }

        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $units = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT Unit_ID, Unit_ShortName FROM S_Unit WHERE Region_ID = %d ORDER BY Unit_ShortName ASC",
                $region_id
            ),
            ARRAY_A
        );

        wp_send_json_success( [ 'units' => $units ?? [] ] );
    }

    /**
     * Отримує список Міст для обраної області.
     */
    public function handle_get_cities_by_region(): void {
        check_ajax_referer( Registry_List::NONCE_ACTION, 'nonce' );
        $region_id = absint( $_POST['region_id'] ?? 0 );
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $cities = $wpdb->get_results(
            $wpdb->prepare( "SELECT City_ID, City_Name FROM S_City WHERE Region_ID = %d ORDER BY City_Name ASC", $region_id ),
            ARRAY_A
        );
        wp_send_json_success( [ 'cities' => $cities ?? [] ] );
    }

	// ─── Публічні AJAX обробники ─────────────────────────────────────────────

	/**
	 * Обробляє AJAX-запит отримання списку членів ФСТУ з фільтрами та пагінацією.
	 */
	public function handle_get_registry(): void {
		// 1. Перевірка nonce (захист від CSRF)
		check_ajax_referer( Registry_List::NONCE_ACTION, 'nonce' );

		// 2. Санітизація вхідних параметрів
		$filters = $this->sanitize_filters( $_POST );

		// 3. Отримання даних
		$result = $this->get_registry_data( $filters );

		// 4. Формування HTML рядків таблиці
		$rows_html = $this->build_table_rows( $result['rows'], $filters['page'], $filters['per_page'] );

        $response_data = [
            'html'        => $rows_html,
            'total'      => (int) $result['total'],
            'page'       => (int) $filters['page'],
            'per_page'   => (int) $filters['per_page'],
            'total_paid'  => (int) $result['total_paid'], // Додаємо кількість сплативших до відповіді
            'total_pages' => (int) ceil( $result['total'] / $filters['per_page'] ),
        ];

        // ДОДАЄМО ВИВІД ЗАПИТУ ДЛЯ ДЕБАГУ АДМІНІСТРАТОРАМ
        if ( current_user_can( 'administrator' ) && ! empty( $result['debug_sql'] ) ) {
            $response_data['debug_sql'] = preg_replace('/\s+/', ' ', $result['debug_sql']);
        }

        wp_send_json_success( $response_data );
	}

	/**
	 * Перевіряє, чи існує email у системі WordPress.
	 */
	public function handle_check_email(): void {
		check_ajax_referer( Registry_List::NONCE_ACTION, 'nonce' );

		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

		if ( empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_error( [ 'message' => 'Невірний формат email.' ] );
		}

		$exists = (bool) email_exists( $email );

		wp_send_json_success( [ 'exists' => $exists ] );
	}

	/**
	 * Обробляє заявку на вступ до ФСТУ.
	 * Вимагає: nonce, honeypot перевірку, валідацію Turnstile.
	 */
	public function handle_submit_application(): void {
		// 1. Перевірка nonce
		check_ajax_referer( Registry_List::NONCE_ACTION, 'nonce' );

		// 2. Honeypot перевірка (якщо заповнено — бот)
		$honeypot = sanitize_text_field( wp_unslash( $_POST['fstu_website'] ?? '' ) );
		if ( ! empty( $honeypot ) ) {
			// Тихо відхиляємо, не кажемо боту про причину
			wp_send_json_error( [ 'message' => 'Заявку відхилено.' ] );
		}

		// 3. Cloudflare Turnstile валідація
		$turnstile_token = sanitize_text_field( wp_unslash( $_POST['cf_turnstile_response'] ?? '' ) );
		if ( ! $this->verify_turnstile( $turnstile_token ) ) {
			wp_send_json_error( [ 'message' => 'Верифікація не пройдена. Спробуйте ще раз.' ] );
		}

		// 4. Санітизація та валідація полів форми
		$data = $this->sanitize_application_data( $_POST );
		$validation_errors = $this->validate_application_data( $data );

		if ( ! empty( $validation_errors ) ) {
			wp_send_json_error( [
				'message' => implode( ' ', $validation_errors ),
				'errors'  => $validation_errors,
			] );
		}

		// 5. Збереження: створення або оновлення WordPress-користувача
		$result = $this->save_application( $data );

		if ( is_wp_error( $result ) ) {
			// Не виводимо внутрішню помилку назовні!
			wp_send_json_error( [ 'message' => 'Помилка збереження. Зверніться до адміністратора.' ] );
		}

		wp_send_json_success( [
			'message' => 'Заявку успішно подано! Очікуйте підтвердження від регіонального адміністратора.',
		] );
	}

	// ─── Приватні методи: санітизація ────────────────────────────────────────

	/**
	 * Санітизує та нормалізує фільтри з POST-запиту.
	 *
	 * @param array $post Масив $_POST.
	 * @return array<string, mixed>
	 */
	private function sanitize_filters( array $post ): array {
		$per_page = absint( $post['per_page'] ?? self::DEFAULT_PER_PAGE );

		return [
			'unit_id'      => absint( $post['unit_id'] ?? 0 ),
			'tourism_type' => absint( $post['tourism_type'] ?? 0 ),
			'club_id'      => absint( $post['club_id'] ?? 0 ),
			'year'         => absint( $post['year'] ?? date( 'Y' ) ),
			'search'       => sanitize_text_field( wp_unslash( $post['search'] ?? '' ) ),
			'fstu_only'    => ! empty( $post['fstu_only'] ) ? 1 : 0,
			'page'         => max( 1, absint( $post['page'] ?? 1 ) ),
			'per_page'     => min( $per_page, self::MAX_PER_PAGE ),
		];
	}

	/**
	 * Санітизує дані заявки на вступ.
	 *
	 * @param array $post Масив $_POST.
	 * @return array<string, string>
	 */
    private function sanitize_application_data( array $post ): array {
        return [
            'last_name'         => sanitize_text_field( wp_unslash( $post['last_name'] ?? '' ) ),
            'first_name'        => sanitize_text_field( wp_unslash( $post['first_name'] ?? '' ) ),
            'patronymic'        => sanitize_text_field( wp_unslash( $post['patronymic'] ?? '' ) ),
            'sex'               => sanitize_text_field( wp_unslash( $post['sex'] ?? 'M' ) ),
            'email'             => sanitize_email( wp_unslash( $post['email'] ?? '' ) ),
            'password'          => wp_unslash( $post['password'] ?? '' ), // Пароль не можна жорстко санітизувати, щоб не зламати спецсимволи
            'phone'             => sanitize_text_field( wp_unslash( $post['phone'] ?? '' ) ),
            'phone_alt'         => sanitize_text_field( wp_unslash( $post['phone_alt'] ?? '' ) ),
            'birth_date'        => sanitize_text_field( wp_unslash( $post['birth_date'] ?? '' ) ),
            'region_id'         => absint( $post['region_id'] ?? 0 ),
            'city_id'           => absint( $post['city_id'] ?? 0 ),
            'unit_id'           => absint( $post['unit_id'] ?? 0 ),
            'tourism_type_id'   => absint( $post['tourism_type_id'] ?? 0 ),
            'club_id'           => absint( $post['club_id'] ?? 0 ),
            'rank_id'           => absint( $post['rank_id'] ?? 0 ),
            'judge_category_id' => absint( $post['judge_category_id'] ?? 0 ),
            'public_titles'     => sanitize_textarea_field( wp_unslash( $post['public_titles'] ?? '' ) ),
        ];
    }

	// ─── Приватні методи: валідація ───────────────────────────────────────────

    /**
     * Валідує поля заявки на вступ.
     *
     * @param array $data Санітизовані дані.
     * @return string[] Масив помилок (порожній = все ОК).
     */
    private function validate_application_data( array $data ): array {
        $errors = [];

        if ( mb_strlen( $data['last_name'] ) < 2 ) {
            $errors[] = 'Прізвище обов\'язкове (мінімум 2 символи).';
        }
        if ( mb_strlen( $data['first_name'] ) < 2 ) {
            $errors[] = 'Ім\'я обов\'язкове (мінімум 2 символи).';
        }
        if ( ! is_email( $data['email'] ) ) {
            $errors[] = 'Невірний формат email.';
        }
        if ( email_exists( $data['email'] ) ) {
            $errors[] = 'Користувач з таким email вже зареєстрований.';
        }
        if ( empty( $data['password'] ) ) {
            $errors[] = 'Пароль не може бути порожнім.';
        } elseif ( mb_strlen( $data['password'] ) < 6 ) {
            $errors[] = 'Пароль занадто короткий (мінімум 6 символів).';
        }
        if ( ! preg_match( '/^\+380\d{9}$/', $data['phone'] ) ) {
            $errors[] = 'Невірний формат телефону. Очікується: +380XXXXXXXXX.';
        }
        if ( empty( $data['birth_date'] ) || ! strtotime( $data['birth_date'] ) ) {
            $errors[] = 'Невірна дата народження.';
        }
        if ( $data['region_id'] <= 0 ) {
            $errors[] = 'Оберіть область.';
        }

        return $errors;
    }

	// ─── Приватні методи: отримання даних ────────────────────────────────────

	/**
	 * Виконує SQL-запит для отримання списку членів з пагінацією.
	 *
	 * @param array $filters Санітизовані фільтри.
	 * @return array{rows: array, total: int}
	 */
	private function get_registry_data( array $filters ): array {
		global $wpdb;

		$prev_year   = $filters['year'] - 1;
		$curr_year   = $filters['year'];
		$offset      = ( $filters['page'] - 1 ) * $filters['per_page'];
		$limit       = $filters['per_page'];

		// ── Будуємо WHERE умови ───────────────────────────────────────────────
		$where_parts = [];
		$params      = [];

		// Тільки члени ФСТУ?
		if ( $filters['fstu_only'] ) {
			$where_parts[] = "cap.meta_value LIKE %s";
			$params[]      = '%userfstu%';
		}

		// Фільтр по ОФСТ
		if ( $filters['unit_id'] > 0 ) {
			$where_parts[] = "ur.Unit_ID = %d";
			$params[]      = $filters['unit_id'];
		}

		// Фільтр по виду туризму
		if ( $filters['tourism_type'] > 0 ) {
			$where_parts[] = "EXISTS (
				SELECT 1 FROM UserTourismType utt
				WHERE utt.User_ID = u.ID AND utt.TourismType_ID = %d
			)";
			$params[] = $filters['tourism_type'];
		}

		// Фільтр по клубу
		if ( $filters['club_id'] > 0 ) {
			$where_parts[] = "EXISTS (
				SELECT 1 FROM UserClub uc2
				WHERE uc2.User_ID = u.ID AND uc2.Club_ID = %d
			)";
			$params[] = $filters['club_id'];
		}

		// Пошук по ПІБ
        // Пошук по ПІБ (шукаємо і в мета-полях, і в display_name)
        if ( ! empty( $filters['search'] ) ) {
            $like = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
            $where_parts[] = "(
				CONCAT_WS(' ', m_ln.meta_value, m_fn.meta_value, m_pt.meta_value) LIKE %s
				OR u.display_name LIKE %s
			)";
            $params[] = $like; // Для CONCAT_WS
            $params[] = $like; // Для display_name
        }

        // Виключаємо заблокованих (безпечно через %s)
        //$where_parts[] = "(cap.meta_value NOT LIKE %s OR cap.meta_value IS NULL)";
        //$params[]      = '%bbp_blocked%';

		$where_sql = ! empty( $where_parts )
			? 'WHERE ' . implode( ' AND ', $where_parts )
			: '';

        // ── Базовий SQL ───────────────────────────────────────────────────────
        $base_sql = "
			FROM {$wpdb->users} u
			LEFT JOIN {$wpdb->usermeta} cap  ON (cap.user_id  = u.ID AND cap.meta_key  = 'wp_capabilities')
			LEFT JOIN {$wpdb->usermeta} m_ln ON (m_ln.user_id = u.ID AND m_ln.meta_key = 'last_name')
			LEFT JOIN {$wpdb->usermeta} m_fn ON (m_fn.user_id = u.ID AND m_fn.meta_key = 'first_name')
			LEFT JOIN {$wpdb->usermeta} m_pt ON (m_pt.user_id = u.ID AND m_pt.meta_key = 'Patronymic')
			LEFT JOIN (
				SELECT u1.* FROM UserRegistationOFST u1
				INNER JOIN (
					SELECT User_ID, MAX(UserRegistationOFST_DateCreate) as max_date 
					FROM UserRegistationOFST GROUP BY User_ID
				) u2 ON u1.User_ID = u2.User_ID AND u1.UserRegistationOFST_DateCreate = u2.max_date
			) ur ON ur.User_ID = u.ID
			LEFT JOIN S_Unit su               ON su.Unit_ID = ur.Unit_ID
			LEFT JOIN S_Region sr             ON sr.Region_ID = ur.Region_ID
			{$where_sql}
		";

		// ── Підрахунок загальної кількості ───────────────────────────────────
		$count_sql = "SELECT COUNT(DISTINCT u.ID) {$base_sql}";

		if ( ! empty( $params ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
			$total = (int) $wpdb->get_var( $count_sql );
		}
        // ── Підрахунок тих, хто сплатив (за поточним фільтром і роком) ───────
        $paid_sql = "SELECT COUNT(DISTINCT u.ID) {$base_sql} AND GetSumPayToTypeYear(u.ID, 1, %d) > 0";
        $paid_params = array_merge( $params, [ $curr_year ] );
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $total_paid = (int) $wpdb->get_var( $wpdb->prepare( $paid_sql, ...$paid_params ) );

		// ── Основний запит ────────────────────────────────────────────────────
		$select_sql = "
			SELECT
				u.ID                                              AS user_id,
				IFNULL(m_ln.meta_value, '')                       AS last_name,
				IFNULL(m_fn.meta_value, '')                       AS first_name,
				IFNULL(m_pt.meta_value, '')                       AS patronymic,
				u.user_email                                      AS email,
				cap.meta_value                                    AS capabilities,
				ur.UserRegistationOFST_ID,
				ur.Unit_ID,
				IFNULL(su.Unit_ShortName, '')                     AS unit_short_name,
				sr.Region_Code                                    AS region_code
			{$base_sql}
			GROUP BY u.ID
			ORDER BY m_ln.meta_value ASC, m_fn.meta_value ASC
			LIMIT %d OFFSET %d
		";

		$data_params   = array_merge( $params, [ $limit, $offset ] );
		$prepare_sql   = ! empty( $params )
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			? $wpdb->prepare( $select_sql, ...$data_params )
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			: $wpdb->prepare( $select_sql, $limit, $offset );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( $prepare_sql, ARRAY_A );

        if ( empty( $rows ) ) {
            return [ 'rows' => [], 'total' => $total, 'debug_sql' => $prepare_sql ];
        }

		// ── Збагачення рядків додатковими даними ─────────────────────────────
		$user_ids = array_column( $rows, 'user_id' );
		$enriched = $this->enrich_rows( $rows, $user_ids, $prev_year, $curr_year, $filters );

        return [ 'rows' => $enriched, 'total' => $total, 'total_paid' => $total_paid, 'debug_sql' => $prepare_sql ];
	}

	/**
	 * Збагачує список користувачів даними про членські квитки, внески та клуби.
	 * Виконується через batch-запити (не N+1!).
	 *
	 * @param array $rows     Базові рядки з БД.
	 * @param int[] $user_ids Масив ідентифікаторів користувачів.
	 * @param int   $prev_year Попередній рік.
	 * @param int   $curr_year Поточний рік.
	 * @param array $filters  Фільтри (для визначення контексту).
	 * @return array
	 */
	private function enrich_rows( array $rows, array $user_ids, int $prev_year, int $curr_year, array $filters ): array {
		global $wpdb;

		if ( empty( $user_ids ) ) {
			return $rows;
		}

		$ids_placeholder = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );

		// ── Членські квитки (UserMemberCard) ─────────────────────────────────
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		$cards = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT umc.User_ID, umc.UserMemberCard_Number, r.Region_Code,
				        CONCAT(r.Region_Code, '-', umc.UserMemberCard_Number) AS card_number,
				        ss.StatusCard_Name, umc.StatusCard_ID
				 FROM UserMemberCard umc
				 INNER JOIN (
				     SELECT User_ID, MAX(UserMemberCard_DateCreate) AS max_date
				     FROM UserMemberCard
				     WHERE User_ID IN ({$ids_placeholder})
				     GROUP BY User_ID
				 ) latest ON umc.User_ID = latest.User_ID AND umc.UserMemberCard_DateCreate = latest.max_date
				 LEFT JOIN S_Region r   ON r.Region_ID = umc.Region_ID
				 LEFT JOIN S_StatusCard ss ON ss.StatusCard_ID = umc.StatusCard_ID",
				...$user_ids
			),
			ARRAY_A
		);
		$cards_map = array_column( $cards ?? [], null, 'User_ID' );

		// ── Членські внески (Dues) ────────────────────────────────────────────
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		$dues = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT User_ID, Year_ID, Dues_Summa
				 FROM Dues
				 WHERE User_ID IN ({$ids_placeholder})
				   AND Year_ID IN (%d, %d)
				   AND DuesType_ID = 1",
				...array_merge( $user_ids, [ $prev_year, $curr_year ] )
			),
			ARRAY_A
		);
		$dues_map = [];
		foreach ( $dues ?? [] as $d ) {
			$dues_map[ $d['User_ID'] ][ $d['Year_ID'] ] = $d['Dues_Summa'];
		}

		// ── Вітрильні внески (DuesSail) ───────────────────────────────────────
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		$dues_sail = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT User_ID, Year_ID, DuesSail_Summa
				 FROM DuesSail
				 WHERE User_ID IN ({$ids_placeholder})
				   AND Year_ID IN (%d, %d)",
				...array_merge( $user_ids, [ $prev_year, $curr_year ] )
			),
			ARRAY_A
		);
		$dues_sail_map = [];
		foreach ( $dues_sail ?? [] as $ds ) {
			$dues_sail_map[ $ds['User_ID'] ][ $ds['Year_ID'] ] = $ds['DuesSail_Summa'];
		}

		// ── Клуби (UserClub) ─────────────────────────────────────────────────
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		$clubs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT uc.User_ID, cl.Club_ID, cl.Club_Name
				 FROM UserClub uc
				 INNER JOIN (
				     SELECT User_ID, MAX(UserClub_Date) AS max_date
				     FROM UserClub
				     WHERE User_ID IN ({$ids_placeholder})
				     GROUP BY User_ID
				 ) latest ON uc.User_ID = latest.User_ID AND uc.UserClub_Date = latest.max_date
				 LEFT JOIN S_Club cl ON cl.Club_ID = uc.Club_ID",
				...$user_ids
			),
			ARRAY_A
		);
		$clubs_map = array_column( $clubs ?? [], null, 'User_ID' );

		// ── Збагачення ────────────────────────────────────────────────────────
		foreach ( $rows as &$row ) {
			$uid = (int) $row['user_id'];
			$cap = $row['capabilities'] ?? '';

			// Визначаємо статус членства ФСТУ
			if ( str_contains( $cap, 'userfstu' ) ) {
				$row['fstu_status'] = 'member';   // ✔
			} elseif ( str_contains( $cap, 'removefstu' ) ) {
				$row['fstu_status'] = 'removed';  // ✖ (був, видалений)
			} elseif ( str_contains( $cap, 'applicants' ) ) {
				$row['fstu_status'] = 'applicant'; // ⬇ заявка
			} else {
				$row['fstu_status'] = 'none';     // —
			}

			// Членський квиток
			$card = $cards_map[ $uid ] ?? null;
			$row['card_number']   = $card['card_number']    ?? '';
			$row['card_status_id'] = (int) ( $card['StatusCard_ID'] ?? 0 );

			// Членські внески
			$row['dues_prev'] = $dues_map[ $uid ][ $prev_year ] ?? null;
			$row['dues_curr'] = $dues_map[ $uid ][ $curr_year ] ?? null;

			// Вітрильні внески
			$row['sail_prev'] = $dues_sail_map[ $uid ][ $prev_year ] ?? null;
			$row['sail_curr'] = $dues_sail_map[ $uid ][ $curr_year ] ?? null;

			// Клуб
			$row['club_name'] = $clubs_map[ $uid ]['Club_Name'] ?? '';
			$row['club_id']   = (int) ( $clubs_map[ $uid ]['Club_ID'] ?? 0 );

			// Видаляємо capabilities з відповіді (security)
			unset( $row['capabilities'] );
		}
		unset( $row );

		return $rows;
	}

	// ─── Приватні методи: рендер HTML ────────────────────────────────────────

	/**
	 * Будує HTML рядки таблиці для передачі у JS.
	 *
	 * @param array $rows     Збагачені рядки.
	 * @param int   $page     Поточна сторінка.
	 * @param int   $per_page Кількість на сторінці.
	 * @return string HTML рядків tbody.
	 */
	private function build_table_rows( array $rows, int $page, int $per_page ): string {
		if ( empty( $rows ) ) {
			return '<tr><td colspan="11" class="fstu-no-results">Записів не знайдено.</td></tr>';
		}

		$is_admin     = current_user_can( 'manage_options' );
		$is_logged_in = is_user_logged_in();

		$html = '';
		$num  = ( $page - 1 ) * $per_page;

		foreach ( $rows as $row ) {
			$num++;
			$uid = (int) $row['user_id'];

			// ПІБ
			$fio = trim(
				esc_html( $row['last_name'] ) . ' ' .
				esc_html( $row['first_name'] ) . ' ' .
				esc_html( $row['patronymic'] )
			);

			// Статус ФСТУ (іконка)
			$fstu_icon = match ( $row['fstu_status'] ) {
				'member'    => '<span class="fstu-icon fstu-icon--ok" title="Член ФСТУ">✔</span>',
				'removed'   => '<span class="fstu-icon fstu-icon--no" title="Видалений">✖</span>',
				'applicant' => '<span class="fstu-icon fstu-icon--app" title="Заявник">⬇</span>',
				default     => '<span class="fstu-icon fstu-icon--none" title="Не член">—</span>',
			};

			// Членський квиток (клікабельний для авторизованих)
			$card_num = esc_html( $row['card_number'] );
			if ( empty( $card_num ) ) {
				$card_html = '<span class="fstu-card fstu-card--none">б/н</span>';
			} else {
				$card_class = ( $row['card_status_id'] === 3 )
					? 'fstu-card fstu-card--download'
					: 'fstu-card fstu-card--link';
				$card_icon  = ( $row['card_status_id'] === 3 ) ? '⬇ ' : '';
				if ( $is_logged_in ) {
					$card_html = sprintf(
						'<a href="#" class="%s" data-user-id="%d" data-card="%s">%s%s</a>',
						esc_attr( $card_class ),
						$uid,
						esc_attr( $row['card_number'] ),
						$card_icon,
						$card_num
					);
				} else {
					$card_html = '<span class="' . esc_attr( $card_class ) . '">' . $card_num . '</span>';
				}
			}

			// ОФСТ
			$ofst_html = esc_html( $row['unit_short_name'] );

			// Клуб (клікабельний)
			if ( ! empty( $row['club_name'] ) ) {
				$club_html = sprintf(
					'<a href="#" class="fstu-club-link" data-club-id="%d">%s</a>',
					(int) $row['club_id'],
					esc_html( $row['club_name'] )
				);
			} else {
				$club_html = '';
			}

			// Внески (форматовані)
			$dues_prev_html = $this->format_dues( $row['dues_prev'] );
			$dues_curr_html = $this->format_dues( $row['dues_curr'] );
			$sail_prev_html = $this->format_dues( $row['sail_prev'] );
			$sail_curr_html = $this->format_dues( $row['sail_curr'] );

			// Колонки внесків — тільки для авторизованих (захист даних)
			$dues_html = $is_logged_in
				? "<td class=\"fstu-td fstu-td--dues\">{$dues_prev_html}</td>
				   <td class=\"fstu-td fstu-td--dues\">{$dues_curr_html}</td>
				   <td class=\"fstu-td fstu-td--dues\">{$sail_prev_html}</td>
				   <td class=\"fstu-td fstu-td--dues\">{$sail_curr_html}</td>"
				: '<td colspan="4" class="fstu-td fstu-td--locked" title="Тільки для авторизованих">—</td>';

            // ── Формування меню опцій (БЕЗ КОНФЛІКТІВ З ТЕМОЮ) ─────────────
            $btn_html = '';
            if ( $is_logged_in ) {
                $btn_html = '
				<div class="fstu-opts">
					<button type="button" class="fstu-btn-action fstu-opts-btn" title="Опції">▾</button>
					<ul class="fstu-opts-list">
						<li><a href="#" class="fstu-action-view" data-id="'.$uid.'">🔍 Перегляд</a></li>';

                if ( $is_admin ) {
                    $btn_html .= '
						<li><a href="#" class="fstu-action-edit" data-id="'.$uid.'">📝 Редагування</a></li>
						<li><a href="#" class="fstu-action-dues" data-id="'.$uid.'">💰 Додати чл. внесок</a></li>
						<li><a href="#" class="fstu-action-club" data-id="'.$uid.'">👤 Додати клуб</a></li>
						<li><a href="#" class="fstu-action-ofst" data-id="'.$uid.'">⛺ Змінити ОФСТ</a></li>
						<li><hr class="fstu-opts-divider"></li>
						<li><a href="#" class="fstu-action-password" data-id="'.$uid.'">✉️ Змінити пароль</a></li>
						<li><a href="#" class="fstu-action-notify" data-id="'.$uid.'">📧 Повідомити про внесок</a></li>
						<li><hr class="fstu-opts-divider"></li>
						<li><a href="#" class="fstu-action-delete" data-id="'.$uid.'" style="color:#c0392b !important;">❌ Видалення</a></li>';
                }

                $btn_html .= '
					</ul>
				</div>';
            }

			$html .= "
			<tr class=\"fstu-row\" data-user-id=\"{$uid}\">
				<td class=\"fstu-td fstu-td--num\">{$num}</td>
				<td class=\"fstu-td fstu-td--fstu\">{$fstu_icon}</td>
				<td class=\"fstu-td fstu-td--card\">{$card_html}</td>
				<td class=\"fstu-td fstu-td--fio\">{$fio}</td>
				<td class=\"fstu-td fstu-td--ofst\">{$ofst_html}</td>
				<td class=\"fstu-td fstu-td--club\">{$club_html}</td>
				{$dues_html}
				<td class=\"fstu-td fstu-td--actions\">{$btn_html}</td>
			</tr>";
		}

		return $html;
	}

	/**
	 * Форматує суму внеску для відображення у таблиці.
	 *
	 * @param string|null $amount Сума або null.
	 * @return string HTML-рядок.
	 */
	private function format_dues( ?string $amount ): string {
		if ( null === $amount || '' === $amount ) {
			return '';
		}
		$formatted = number_format( (float) $amount, 2 );
		return '<span class="fstu-dues-amount">' . esc_html( $formatted ) . '</span>';
	}

	// ─── Приватні методи: допоміжні ──────────────────────────────────────────

	/**
	 * Верифікує токен Cloudflare Turnstile через сервер Cloudflare.
	 *
	 * @param string $token Токен з форми.
	 * @return bool True якщо верифікація пройшла.
	 */
	private function verify_turnstile( string $token ): bool {
		// Якщо ключ не налаштований — пропускаємо (dev-режим)
		if ( ! defined( 'FSTU_TURNSTILE_SECRET_KEY' ) || empty( FSTU_TURNSTILE_SECRET_KEY ) ) {
			return true;
		}

		if ( empty( $token ) ) {
			return false;
		}

		$response = wp_remote_post(
			'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			[
				'timeout' => 10,
				'body'    => [
					'secret'   => FSTU_TURNSTILE_SECRET_KEY,
					'response' => $token,
					'remoteip' => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		return ! empty( $body['success'] );
	}

    /**
     * Зберігає заявку на вступ до ФСТУ у БД.
     *
     * @param array $data Валідовані дані.
     * @return int|\WP_Error User ID або помилка.
     */
    /**
     * Зберігає заявку на вступ до ФСТУ у БД.
     *
     * @param array $data Валідовані дані.
     * @return int|\WP_Error User ID або помилка.
     */
    private function save_application( array $data ): int|\WP_Error {
        global $wpdb;

        // Використовуємо пароль з форми
        $password = $data['password'];

        // Логін = email до @
        $login_base = sanitize_user( strstr( $data['email'], '@', true ) );
        $login      = wp_unique_username( $login_base );

        $user_id = wp_insert_user( [
            'user_login' => $login,
            'user_pass'  => $password,
            'user_email' => $data['email'],
            'role'       => 'applicants', // кастомна роль "заявник"
        ] );

        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }

        // Зберігаємо базові метадані
        update_user_meta( $user_id, 'last_name',  $data['last_name'] );
        update_user_meta( $user_id, 'first_name', $data['first_name'] );
        update_user_meta( $user_id, 'Patronymic', $data['patronymic'] );
        update_user_meta( $user_id, 'BirthDate',  $data['birth_date'] );
        update_user_meta( $user_id, 'Sex',        $data['sex'] );
        update_user_meta( $user_id, 'Phone',      $data['phone'] );

        if ( ! empty( $data['phone_alt'] ) ) {
            update_user_meta( $user_id, 'Phone_alt', $data['phone_alt'] );
        }
        if ( ! empty( $data['public_titles'] ) ) {
            update_user_meta( $user_id, 'Public_titles', $data['public_titles'] );
        }

        // Прив'язка клубу
        if ( $data['club_id'] > 0 ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->insert( 'UserClub', [ 'User_ID' => $user_id, 'Club_ID' => $data['club_id'], 'UserClub_Date' => current_time('mysql') ], [ '%d', '%d', '%s' ] );
        }

        // Реєстрація в ОФСТ
        if ( $data['unit_id'] > 0 && $data['region_id'] > 0 ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->insert(
                'UserRegistationOFST',
                [
                    'User_ID'                        => $user_id,
                    'Region_ID'                      => $data['region_id'],
                    'Unit_ID'                        => $data['unit_id'],
                    'UserRegistationOFST_DateCreate' => current_time( 'mysql' ),
                ],
                [ '%d', '%d', '%d', '%s' ]
            );
        }

        // Прив'язка міста
        if ( $data['city_id'] > 0 ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->insert(
                'UserCity',
                [
                    'User_ID'               => $user_id,
                    'City_ID'               => $data['city_id'],
                    'UserCity_DateCreate'   => current_time( 'mysql' ),
                ],
                [ '%d', '%d', '%s' ]
            );
        }

        // Надсилаємо стандартний email від WP
        wp_new_user_notification( $user_id, null, 'user' );

        return $user_id;
    }
}