<?php
/**
 * AJAX-обробники модуля "Довідник осередків ФСТУ".
 * Всі запити до БД виконуються виключно через $wpdb->prepare().
 *
 * Version:     1.0.1
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\Units
 */

namespace FSTU\Dictionaries\Units;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Units_Ajax {

	/** Кількість записів на сторінці за замовчуванням. */
	private const DEFAULT_PER_PAGE = 15;

	/** Максимально допустима кількість записів на сторінці (захист від DDoS). */
	private const MAX_PER_PAGE = 100;

	/** Максимальна довжина пошукового рядка. */
	private const MAX_SEARCH_LENGTH = 100;

	/**
	 * Реєструє AJAX хуки WordPress.
	 */
	public function init(): void {
		// Отримання списку осередків (авторизовані та гості)
		add_action( 'wp_ajax_fstu_get_units', [ $this, 'handle_get_units' ] );
		add_action( 'wp_ajax_nopriv_fstu_get_units', [ $this, 'handle_get_units' ] );

		// Отримання деталей осередка (авторизовані та гості)
		add_action( 'wp_ajax_fstu_get_unit_detail', [ $this, 'handle_get_unit_detail' ] );
		add_action( 'wp_ajax_nopriv_fstu_get_unit_detail', [ $this, 'handle_get_unit_detail' ] );

		// Отримання міст по регіону (авторизовані та гості)
		add_action( 'wp_ajax_fstu_units_get_cities_by_region', [ $this, 'handle_get_cities_by_region' ] );
		add_action( 'wp_ajax_nopriv_fstu_units_get_cities_by_region', [ $this, 'handle_get_cities_by_region' ] );

		// Додавання осередка (тільки адміни та реєстратори)
		add_action( 'wp_ajax_fstu_add_unit', [ $this, 'handle_add_unit' ] );

		// Редагування осередка (тільки адміни та реєстратори)
		add_action( 'wp_ajax_fstu_edit_unit', [ $this, 'handle_edit_unit' ] );

		// Видалення осередка (тільки адміни)
		add_action( 'wp_ajax_fstu_delete_unit', [ $this, 'handle_delete_unit' ] );
	}

	// ─── Публічні AJAX обробники ──────────────────────────────────────────────────

	/**
	 * Обробляє AJAX-запит отримання списку осередків з фільтрами та пагінацією.
	 */
	public function handle_get_units(): void {
		check_ajax_referer( Units_List::NONCE_ACTION, 'nonce' );

		// Отримуємо та санітизуємо параметри
		$search      = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$search      = substr( $search, 0, self::MAX_SEARCH_LENGTH );
		$region_id   = absint( $_POST['region_id'] ?? 0 );
		$unit_type   = absint( $_POST['unit_type'] ?? 0 );
		$page        = absint( $_POST['page'] ?? 1 );
		$per_page    = absint( $_POST['per_page'] ?? self::DEFAULT_PER_PAGE );

		// Захист від DDoS
		if ( $per_page > self::MAX_PER_PAGE || $per_page < 1 ) {
			$per_page = self::DEFAULT_PER_PAGE;
		}
		if ( $page < 1 ) {
			$page = 1;
		}

		global $wpdb;

		// Будуємо базовий запит
		$query = 'SELECT Unit_ID, Unit_Name, Unit_ShortName, UnitType_Name, Region_Name, City_Name, Unit_Adr, OPF_NameShort, Unit_AnnualFee FROM vUnit WHERE 1=1';
		$params = [];

		// Пошук за назвою
		if ( $search ) {
			$query  .= " AND (Unit_Name LIKE %s OR Unit_ShortName LIKE %s)";
			$like    = '%' . $wpdb->esc_like( $search ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		// Фільтр за регіоном
		if ( $region_id > 0 ) {
			$query    .= " AND Region_ID = %d";
			$params[] = $region_id;
		}

		// Фільтр за типом осередка
		if ( $unit_type > 0 ) {
			$query    .= " AND UnitType_ID = %d";
			$params[] = $unit_type;
		}

		// Загальна кількість записів
		$count_query = 'SELECT COUNT(*) FROM vUnit WHERE 1=1';
		if ( $search ) {
			$count_query .= " AND (Unit_Name LIKE %s OR Unit_ShortName LIKE %s)";
		}
		if ( $region_id > 0 ) {
			$count_query .= " AND Region_ID = %d";
		}
		if ( $unit_type > 0 ) {
			$count_query .= " AND UnitType_ID = %d";
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$total_count = (int) $wpdb->get_var( $wpdb->prepare( $count_query, $params ) );

		// Сортування та пагінація
		$query .= " ORDER BY Unit_Name ASC LIMIT %d OFFSET %d";
		$offset = ( $page - 1 ) * $per_page;

		$params[] = $per_page;
		$params[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$units = $wpdb->get_results( $wpdb->prepare( $query, $params ), ARRAY_A );

		// Розраховуємо кількість сторінок
		$total_pages = max( 1, (int) ceil( $total_count / $per_page ) );

		wp_send_json_success(
			[
				'units'       => $units ?? [],
				'total'       => $total_count,
				'page'        => $page,
				'per_page'    => $per_page,
				'total_pages' => $total_pages,
			]
		);
	}

	/**
	 * Отримує деталі осередка для модального вікна перегляду.
	 */
	public function handle_get_unit_detail(): void {
		check_ajax_referer( Units_List::NONCE_ACTION, 'nonce' );

		$unit_id = absint( $_POST['unit_id'] ?? 0 );

		if ( ! $unit_id ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ID осередка.', 'fstu' ) ] );
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$unit = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT u.Unit_ID, u.Unit_Name, u.Unit_ShortName, u.OPF_ID, u.UnitType_ID, u.Unit_Parent, u.Region_ID, u.City_ID,
				        u.Unit_Adr, u.Unit_OKPO, u.Unit_EntranceFee, u.Unit_AnnualFee, u.Unit_UrlPay, u.Unit_PaymentCard,
				        opf.OPF_Name, opf.OPF_NameShort,
				        ut.UnitType_Name,
				        region.Region_Name,
				        city.City_Name,
				        parent.Unit_Name AS Parent_Unit_Name
				 FROM S_Unit u
				 LEFT JOIN S_OPF opf ON opf.OPF_ID = u.OPF_ID
				 LEFT JOIN S_UnitType ut ON ut.UnitType_ID = u.UnitType_ID
				 LEFT JOIN S_Region region ON region.Region_ID = u.Region_ID
				 LEFT JOIN S_City city ON city.City_ID = u.City_ID
				 LEFT JOIN S_Unit parent ON parent.Unit_ID = u.Unit_Parent
				 WHERE u.Unit_ID = %d",
				$unit_id
			),
			ARRAY_A
		);

		if ( ! $unit ) {
			wp_send_json_error( [ 'message' => __( 'Осередок не знайдено.', 'fstu' ) ] );
		}

		wp_send_json_success( [ 'unit' => $unit ] );
	}

	/**
	 * Отримує список міст для обраної області.
	 */
	public function handle_get_cities_by_region(): void {
		check_ajax_referer( Units_List::NONCE_ACTION, 'nonce' );

		$region_id = absint( $_POST['region_id'] ?? 0 );

		if ( ! $region_id ) {
			wp_send_json_error( [ 'message' => __( 'Невірна область.', 'fstu' ) ] );
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$cities = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT City_ID, City_Name FROM S_City WHERE Region_ID = %d ORDER BY City_Name ASC",
				$region_id
			),
			ARRAY_A
		);

		wp_send_json_success( [ 'cities' => $cities ?? [] ] );
	}

	/**
	 * Обробляє додавання нового осередка.
	 */
	public function handle_add_unit(): void {
		check_ajax_referer( Units_List::NONCE_ACTION, 'nonce' );

		// Перевіряємо права доступу
		if ( ! $this->current_user_can_manage_units() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для додавання осередка.', 'fstu' ) ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => __( 'Помилка при обробці форми.', 'fstu' ) ] );
		}

		$data = $this->get_sanitized_unit_payload();
		$this->validate_unit_payload( $data );

		global $wpdb;

		// Вставляємо запис
		$inserted = $wpdb->insert(
			'S_Unit',
			[
				'Unit_DateCreate' => current_time( 'mysql' ),
				'Unit_Name'        => $data['unit_name'],
				'Unit_ShortName'   => $data['unit_short_name'],
				'OPF_ID'           => $data['opf_id'],
				'UnitType_ID'      => $data['unit_type_id'],
				'Unit_Parent'      => $data['unit_parent'],
				'Region_ID'        => $data['region_id'],
				'City_ID'          => $data['city_id'],
				'Unit_Adr'         => $data['unit_adr'],
				'Unit_OKPO'        => $data['unit_okpo'],
				'Unit_EntranceFee' => $data['entrance_fee'],
				'Unit_AnnualFee'   => $data['annual_fee'],
				'Unit_UrlPay'      => $data['url_pay'],
				'Unit_PaymentCard' => $data['payment_card'],
			],
			[
				'%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%f', '%f', '%s', '%s',
			]
		);

		if ( ! $inserted ) {
			wp_send_json_error( [ 'message' => __( 'Помилка при додаванні осередка до бази даних.', 'fstu' ) ] );
		}

		wp_send_json_success(
			[
				'message' => __( 'Осередок успішно додано.', 'fstu' ),
				'unit_id' => $wpdb->insert_id,
			]
		);
	}

	/**
	 * Обробляє редагування осередка.
	 */
	public function handle_edit_unit(): void {
		check_ajax_referer( Units_List::NONCE_ACTION, 'nonce' );

		// Перевіряємо права доступу
		if ( ! $this->current_user_can_manage_units() ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для редагування осередка.', 'fstu' ) ] );
		}

		if ( $this->is_honeypot_triggered() ) {
			wp_send_json_error( [ 'message' => __( 'Помилка при обробці форми.', 'fstu' ) ] );
		}

		$unit_id          = absint( $_POST['unit_id'] ?? 0 );
		$data             = $this->get_sanitized_unit_payload();

		// Валідація
		if ( ! $unit_id ) {
			wp_send_json_error( [ 'message' => __( 'Невірні дані для редагування.', 'fstu' ) ] );
		}

		$this->validate_unit_payload( $data );

		global $wpdb;

		// Оновлюємо запис
		$updated = $wpdb->update(
			'S_Unit',
			[
				'Unit_Name'        => $data['unit_name'],
				'Unit_ShortName'   => $data['unit_short_name'],
				'OPF_ID'           => $data['opf_id'],
				'UnitType_ID'      => $data['unit_type_id'],
				'Unit_Parent'      => $data['unit_parent'],
				'Region_ID'        => $data['region_id'],
				'City_ID'          => $data['city_id'],
				'Unit_Adr'         => $data['unit_adr'],
				'Unit_OKPO'        => $data['unit_okpo'],
				'Unit_EntranceFee' => $data['entrance_fee'],
				'Unit_AnnualFee'   => $data['annual_fee'],
				'Unit_UrlPay'      => $data['url_pay'],
				'Unit_PaymentCard' => $data['payment_card'],
			],
			[ 'Unit_ID' => $unit_id ],
			[
				'%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%f', '%f', '%s', '%s',
			],
			[ '%d' ]
		);

		if ( false === $updated ) {
			wp_send_json_error( [ 'message' => __( 'Помилка при редаганні осередка.', 'fstu' ) ] );
		}

		wp_send_json_success(
			[
				'message' => __( 'Осередок успішно відредагований.', 'fstu' ),
			]
		);
	}

	/**
	 * Обробляє видалення осередка.
	 */
	public function handle_delete_unit(): void {
		check_ajax_referer( Units_List::NONCE_ACTION, 'nonce' );

		// Видалення дозволено тільки адмінам
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Немає прав для видалення осередка.', 'fstu' ) ] );
		}

		$unit_id = absint( $_POST['unit_id'] ?? 0 );

		if ( ! $unit_id ) {
			wp_send_json_error( [ 'message' => __( 'Невірний ID осередка.', 'fstu' ) ] );
		}

		global $wpdb;

		// Не дозволяємо видаляти осередок, якщо він використовується в дочірніх записах.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$has_children = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM S_Unit WHERE Unit_Parent = %d', $unit_id )
		);

		if ( $has_children > 0 ) {
			wp_send_json_error( [ 'message' => __( 'Осередок має підлеглі осередки та не може бути видалений.', 'fstu' ) ] );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$linked_users = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM UserRegion WHERE Unit_ID = %d', $unit_id )
		);

		if ( $linked_users > 0 ) {
			wp_send_json_error( [ 'message' => __( 'Осередок прив’язаний до користувачів і не може бути видалений.', 'fstu' ) ] );
		}

		// Видаляємо запис
		$deleted = $wpdb->delete( 'S_Unit', [ 'Unit_ID' => $unit_id ], [ '%d' ] );

		if ( ! $deleted ) {
			wp_send_json_error( [ 'message' => __( 'Помилка при видаленні осередка.', 'fstu' ) ] );
		}

		wp_send_json_success(
			[
				'message' => __( 'Осередок успішно видалений.', 'fstu' ),
			]
		);
	}

	/**
	 * Перевіряє права поточного користувача на керування осередками.
	 */
	private function current_user_can_manage_units(): bool {
		$user  = wp_get_current_user();
		$roles = is_array( $user->roles ) ? $user->roles : [];

		return current_user_can( 'manage_options' ) || in_array( 'administrator', $roles, true ) || in_array( 'userregistrar', $roles, true );
	}

	/**
	 * Перевіряє honeypot поле.
	 */
	private function is_honeypot_triggered(): bool {
		$honeypot = sanitize_text_field( wp_unslash( $_POST['fstu_website'] ?? '' ) );

		return '' !== $honeypot;
	}

	/**
	 * Повертає санітизовані дані форми осередка.
	 *
	 * @return array<string,mixed>
	 */
	private function get_sanitized_unit_payload(): array {
		$unit_okpo_raw = sanitize_text_field( wp_unslash( $_POST['unit_okpo'] ?? '' ) );

		return [
			'unit_name'       => sanitize_text_field( wp_unslash( $_POST['unit_name'] ?? '' ) ),
			'unit_short_name' => sanitize_text_field( wp_unslash( $_POST['unit_short_name'] ?? '' ) ),
			'opf_id'          => absint( $_POST['opf_id'] ?? 0 ),
			'unit_type_id'    => absint( $_POST['unit_type_id'] ?? 0 ),
			'unit_parent'     => absint( $_POST['unit_parent'] ?? 0 ),
			'region_id'       => absint( $_POST['region_id'] ?? 0 ),
			'city_id'         => absint( $_POST['city_id'] ?? 0 ),
			'unit_adr'        => sanitize_text_field( wp_unslash( $_POST['unit_adr'] ?? '' ) ),
			'unit_okpo'       => preg_replace( '/\D+/', '', $unit_okpo_raw ),
			'entrance_fee'    => round( (float) wp_unslash( $_POST['entrance_fee'] ?? 0 ), 2 ),
			'annual_fee'      => round( (float) wp_unslash( $_POST['annual_fee'] ?? 0 ), 2 ),
			'url_pay'         => esc_url_raw( wp_unslash( $_POST['url_pay'] ?? '' ) ),
			'payment_card'    => sanitize_text_field( wp_unslash( $_POST['payment_card'] ?? '' ) ),
		];
	}

	/**
	 * Валідує дані форми осередка.
	 *
	 * @param array<string,mixed> $data Дані форми.
	 */
	private function validate_unit_payload( array $data ): void {
		if ( '' === $data['unit_name'] || '' === $data['unit_short_name'] || empty( $data['region_id'] ) || empty( $data['city_id'] ) || empty( $data['unit_type_id'] ) || empty( $data['opf_id'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Заповніть усі обов’язкові поля.', 'fstu' ) ] );
		}

		if ( $data['unit_parent'] > 0 && $data['unit_parent'] === (int) ( $_POST['unit_id'] ?? 0 ) ) {
			wp_send_json_error( [ 'message' => __( 'Осередок не може бути батьківським сам для себе.', 'fstu' ) ] );
		}

		if ( '' !== $data['unit_okpo'] && ! preg_match( '/^\d{8,10}$/', (string) $data['unit_okpo'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Код ЄДРПОУ повинен містити 8-10 цифр.', 'fstu' ) ] );
		}

		if ( $data['entrance_fee'] < 0 || $data['annual_fee'] < 0 ) {
			wp_send_json_error( [ 'message' => __( 'Сума внеску не може бути від’ємною.', 'fstu' ) ] );
		}

		if ( '' !== $data['url_pay'] && ! wp_http_validate_url( (string) $data['url_pay'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Вкажіть коректне посилання на форму оплати.', 'fstu' ) ] );
		}
	}
}



