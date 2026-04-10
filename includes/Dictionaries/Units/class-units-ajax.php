<?php
/**
 * Клас обробки AJAX запитів довідника осередків ФСТУ.
 * * Version: 1.0.0
 * Date_update: 2026-04-10
 */

namespace FSTU\Dictionaries\Units;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Units_Ajax {

	private Units_Repository $repository;
	private Units_Service $service;

	public function __construct() {
		$this->repository = new Units_Repository();
		$this->service    = new Units_Service();
	}

	public function init(): void {
		// Читання (Довідник та Протокол)
		add_action( 'wp_ajax_fstu_units_get_list', [ $this, 'handle_get_list' ] );
		add_action( 'wp_ajax_fstu_units_get_item', [ $this, 'handle_get_item' ] );
		add_action( 'wp_ajax_fstu_units_get_protocol', [ $this, 'handle_get_protocol' ] );
		add_action( 'wp_ajax_fstu_units_get_cities', [ $this, 'handle_get_cities' ] );

		// Запис (CRUD)
		add_action( 'wp_ajax_fstu_units_save', [ $this, 'handle_save' ] );
		add_action( 'wp_ajax_fstu_units_delete', [ $this, 'handle_delete' ] );
        add_action( 'wp_ajax_fstu_units_get_dues', [ $this, 'handle_get_dues' ] );
        add_action( 'wp_ajax_fstu_units_get_dictionaries', [ $this, 'handle_get_dictionaries' ] );
	}

	/**
	 * Отримання списку осередків
	 */
	public function handle_get_list(): void {
		check_ajax_referer( 'fstu_units_nonce', 'nonce' );

		// TODO: Тут додати перевірку прав Capabilities::get_unit_permissions()

		$search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$page     = isset( $_POST['page'] ) ? max( 1, absint( $_POST['page'] ) ) : 1;
		$per_page = isset( $_POST['per_page'] ) ? max( 10, absint( $_POST['per_page'] ) ) : 10;
		$offset   = ( $page - 1 ) * $per_page;

		$data = $this->repository->get_units( $search, $per_page, $offset );

		wp_send_json_success( [
			'items'       => $data['items'],
			'total'       => $data['total'],
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => ceil( $data['total'] / $per_page ),
		] );
	}

	/**
	 * Динамічне завантаження міст за ID регіону
	 */
	public function handle_get_cities(): void {
		check_ajax_referer( 'fstu_units_nonce', 'nonce' );

		$region_id = isset( $_POST['region_id'] ) ? absint( $_POST['region_id'] ) : 0;
		if ( ! $region_id ) {
			wp_send_json_error( [ 'message' => 'Не вказано регіон.' ] );
		}

		$cities = $this->repository->get_cities_by_region( $region_id );
		wp_send_json_success( [ 'cities' => $cities ] );
	}

	/**
	 * Збереження осередку (Insert / Update)
	 */
	public function handle_save(): void {
		check_ajax_referer( 'fstu_units_nonce', 'nonce' );

		// Базовий захист від ботів (Honeypot)
		if ( ! empty( $_POST['fstu_website'] ) ) {
			wp_send_json_error( [ 'message' => 'Запит відхилено системою безпеки.' ] );
		}

		// TODO: Перевірка прав (тільки Адміністратор або Реєстратор)
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'userregistrar' ) ) {
			wp_send_json_error( [ 'message' => 'Немає доступу.' ] );
		}

		$unit_id = isset( $_POST['Unit_ID'] ) ? absint( $_POST['Unit_ID'] ) : 0;
		
		// Санітизація вхідних даних
		$data = [
			'Unit_Name'        => sanitize_text_field( wp_unslash( $_POST['Unit_Name'] ?? '' ) ),
			'Unit_ShortName'   => sanitize_text_field( wp_unslash( $_POST['Unit_ShortName'] ?? '' ) ),
			'OPF_ID'           => absint( $_POST['OPF_ID'] ?? 0 ),
			'UnitType_ID'      => absint( $_POST['UnitType_ID'] ?? 0 ),
			'Unit_Parent'      => absint( $_POST['Unit_Parent'] ?? 0 ),
			'Region_ID'        => absint( $_POST['Region_ID'] ?? 0 ),
			'City_ID'          => absint( $_POST['City_ID'] ?? 0 ),
			'Unit_Adr'         => sanitize_text_field( wp_unslash( $_POST['Unit_Adr'] ?? '' ) ),
			'Unit_OKPO'        => sanitize_text_field( wp_unslash( $_POST['Unit_OKPO'] ?? '' ) ),
			'Unit_EntranceFee' => floatval( $_POST['Unit_EntranceFee'] ?? 0 ),
			'Unit_AnnualFee'   => floatval( $_POST['Unit_AnnualFee'] ?? 0 ),
			'Unit_UrlPay'      => sanitize_url( wp_unslash( $_POST['Unit_UrlPay'] ?? '' ) ),
			'Unit_PaymentCard' => sanitize_text_field( wp_unslash( $_POST['Unit_PaymentCard'] ?? '' ) ),
		];

		$user_id = get_current_user_id();

		if ( $unit_id > 0 ) {
			$success = $this->service->update_unit( $unit_id, $data, $user_id );
			$message = 'Осередок успішно оновлено.';
		} else {
			$success = $this->service->create_unit( $data, $user_id );
			$message = 'Новий осередок успішно додано.';
		}

		if ( $success ) {
			wp_send_json_success( [ 'message' => $message ] );
		} else {
			wp_send_json_error( [ 'message' => 'Помилка бази даних. Перевірте лог.' ] );
		}
	}

	/**
	 * М'яке видалення осередку
	 */
	public function handle_delete(): void {
		check_ajax_referer( 'fstu_units_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Немає доступу для видалення.' ] );
		}

		$unit_id = isset( $_POST['Unit_ID'] ) ? absint( $_POST['Unit_ID'] ) : 0;
		if ( ! $unit_id ) {
			wp_send_json_error( [ 'message' => 'Некоректний ID.' ] );
		}

		if ( $this->service->delete_unit( $unit_id, get_current_user_id() ) ) {
			wp_send_json_success( [ 'message' => 'Осередок успішно видалено.' ] );
		} else {
			wp_send_json_error( [ 'message' => 'Помилка при видаленні.' ] );
		}
	}

	/**
	 * Завантаження протоколу для осередків
	 */
	public function handle_get_protocol(): void {
		check_ajax_referer( 'fstu_units_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Немає прав для перегляду протоколу.' ] );
		}

		global $wpdb;

		$search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$page     = isset( $_POST['page'] ) ? max( 1, absint( $_POST['page'] ) ) : 1;
		$per_page = isset( $_POST['per_page'] ) ? max( 10, absint( $_POST['per_page'] ) ) : 10;
		$offset   = ( $page - 1 ) * $per_page;

		$where = "WHERE l.Logs_Name = 'Unit'";
		$args  = [];

		if ( ! empty( $search ) ) {
			$where .= " AND (l.Logs_Text LIKE %s OR u.FIO LIKE %s)";
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			array_push( $args, $like, $like );
		}

		array_push( $args, $per_page, $offset );

		$sql = "SELECT l.Logs_DateCreate, l.Logs_Type, l.Logs_Name, l.Logs_Text, l.Logs_Error, u.FIO
				FROM Logs l 
				LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
				$where
				ORDER BY l.Logs_DateCreate DESC
				LIMIT %d OFFSET %d";

		$items = $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A );

		// Підрахунок загальної кількості для пагінації протоколу
		$sql_count = "SELECT COUNT(*) FROM Logs l LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID $where";
		$count_args = array_slice( $args, 0, count( $args ) - 2 );
		$total = (int) ( empty( $count_args ) ? $wpdb->get_var( $sql_count ) : $wpdb->get_var( $wpdb->prepare( $sql_count, $count_args ) ) );

		wp_send_json_success( [
			'items'       => $items,
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total / $per_page ),
		] );
	}
    /**
	 * Отримання даних одного осередку для форми редагування
	 */
	public function handle_get_item(): void {
		check_ajax_referer( 'fstu_units_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'userregistrar' ) ) {
			wp_send_json_error( [ 'message' => 'Немає доступу.' ] );
		}

		$unit_id = isset( $_POST['Unit_ID'] ) ? absint( $_POST['Unit_ID'] ) : 0;
		if ( ! $unit_id ) {
			wp_send_json_error( [ 'message' => 'Некоректний ID.' ] );
		}

		$item = $this->repository->get_unit_by_id( $unit_id );
		
		if ( $item ) {
			// Одразу віддаємо список міст для регіону цього осередку, щоб JS міг коректно заповнити <select>
			$cities = $this->repository->get_cities_by_region( (int) $item['Region_ID'] );
			wp_send_json_success( [ 'item' => $item, 'cities' => $cities ] );
		} else {
			wp_send_json_error( [ 'message' => 'Осередок не знайдено.' ] );
		}
	}
 	/**
	 * Отримання історії внесків для осередку (з 2020 по поточний рік)
	 */
	public function handle_get_dues(): void {
		check_ajax_referer( 'fstu_units_nonce', 'nonce' );

		// Тільки для Адмінів або Реєстраторів цього ОФСТ
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'userregistrar' ) ) {
			wp_send_json_error( [ 'message' => 'Немає доступу до фінансової інформації.' ] );
		}

		$unit_id = isset( $_POST['Unit_ID'] ) ? absint( $_POST['Unit_ID'] ) : 0;
		if ( ! $unit_id ) {
			wp_send_json_error( [ 'message' => 'Некоректний ID осередку.' ] );
		}

		global $wpdb;
		$current_year = (int) current_time( 'Y' );
		$reg_year     = 2020; // Рік початку записів згідно з legacy кодом

		// Отримуємо налаштування сум з БД (без хардкоду!)
		$settings    = $this->repository->get_settings();
		$amount      = $settings['amount'];
		$bill_amount = $amount + round( ( $amount * $settings['commission'] ), 2 );

		// Вибірка вже сплачених років
		$sql = "SELECT Year_ID, DuesUnit_Summa, Dues_DateCreate FROM vDuesUnit WHERE Unit_ID = %d";
		$paid_dues = $wpdb->get_results( $wpdb->prepare( $sql, $unit_id ), ARRAY_A );
		
		$paid_map = [];
		if ( $paid_dues ) {
			foreach ( $paid_dues as $pd ) {
				$paid_map[ $pd['Year_ID'] ] = $pd;
			}
		}

		$unit = $this->repository->get_unit_by_id( $unit_id );
		$unit_short_name = $unit ? $unit['Unit_ShortName'] : 'Осередок';
		$user_id = get_current_user_id();

		$history = [];
		// Формуємо масив від поточного року до року реєстрації (2020)
		for ( $y = $current_year; $y >= $reg_year; $y-- ) {
			if ( isset( $paid_map[ $y ] ) ) {
				$history[] = [
					'year'   => $y,
					'status' => 'paid',
					'summa'  => $paid_map[ $y ]['DuesUnit_Summa'],
					'date'   => $paid_map[ $y ]['Dues_DateCreate'],
				];
			} else {
				// Якщо не сплачено, генеруємо захищені параметри для Portmone
				$history[] = [
					'year'         => $y,
					'status'       => 'unpaid',
					'amount'       => $amount,
					'bill_amount'  => $bill_amount,
					'portmone_data'=> [
						'payee_id'          => '28935',
						'shop_order_number' => $y . $user_id,
						'bill_amount'       => $bill_amount,
						'description'       => sprintf( 'Членські внески за %d рік, осередок: %s, платник ID: %d', $y, $unit_short_name, $user_id ),
						'success_url'       => get_site_url() . "?success=" . $user_id,
						'failure_url'       => get_site_url() . "?failure=" . $user_id,
						'attribute1'        => $y,
						'attribute2'        => $user_id,
						'lang'              => 'uk',
						'encoding'          => 'UTF-8',
						'exp_time'          => '400'
					]
				];
			}
		}

		wp_send_json_success( [ 'history' => $history ] );
	}
    /**
	 * Завантаження довідників (ОПФ, Ранги, Регіони) для форми
	 */
	public function handle_get_dictionaries(): void {
		check_ajax_referer( 'fstu_units_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'userregistrar' ) ) {
			wp_send_json_error( [ 'message' => 'Немає доступу.' ] );
		}

		wp_send_json_success( $this->repository->get_dictionaries() );
	}
    //-------------
}