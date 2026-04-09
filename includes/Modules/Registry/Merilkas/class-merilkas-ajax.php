<?php
/**
 * AJAX-маршрутизатор модуля "Реєстр мерилок".
 *
 * Version:     1.0.0
 * Date_update: 2026-04-09
 *
 * @package FSTU\Modules\Registry\Merilkas
 */

namespace FSTU\Modules\Registry\Merilkas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Merilkas_Ajax {

	private ?Merilkas_Service $service = null;
	private ?Merilkas_Repository $repository = null;

	public function init(): void {
		add_action( 'wp_ajax_fstu_merilkas_get_list', [ $this, 'handle_get_list' ] );
		// TODO: Додати хуки для get_single, create, update, delete, print
        add_action( 'wp_ajax_fstu_merilkas_print', [ $this, 'handle_print_merilka' ] );
        add_action( 'wp_ajax_fstu_merilkas_get_single', [ $this, 'handle_get_single' ] );
	}

	/**
	 * Перевірка прав на управління мерилкою (Створення/Редагування/Видалення).
	 * Дозволено власнику судна АБО Адміністраторам вітрил.
	 */
	private function current_user_can_manage_merilka( int $sailboat_id ): bool {
		$user  = wp_get_current_user();
		$roles = is_array( $user->roles ) ? $user->roles : [];

		if ( current_user_can( 'manage_options' ) || in_array( 'administrator', $roles, true ) || in_array( 'sailadministrator', $roles, true ) ) {
			return true;
		}

		// Перевірка, чи користувач є власником судна
		$owner_id = $this->get_repository()->get_sailboat_owner_id( $sailboat_id );
		return $owner_id === $user->ID && $user->ID > 0;
	}

	/**
	 * Спеціальна перевірка для редагування вже використаної мерилки.
	 */
	private function current_user_can_force_edit_used_merilka(): bool {
		$user  = wp_get_current_user();
		$roles = is_array( $user->roles ) ? $user->roles : [];
		return current_user_can( 'manage_options' ) || in_array( 'administrator', $roles, true );
	}

	private function get_repository(): Merilkas_Repository {
		if ( null === $this->repository ) {
			$this->repository = new Merilkas_Repository();
		}
		return $this->repository;
	}

    // TODO: Додати обробники AJAX запитів (буде реалізовано у Фазі 2)
    /**
	 * Генерує HTML сторінку обмірного свідоцтва для друку.
	 */
	public function handle_print_merilka(): void {
		// Перевірка прав (тільки авторизовані члени або адміни)
		if ( ! is_user_logged_in() ) {
			wp_die( 'Немає доступу. Будь ласка, авторизуйтесь.' );
		}

		$mr_id = absint( $_GET['mr_id'] ?? 0 );
		if ( $mr_id <= 0 ) {
			wp_die( 'Невірний ідентифікатор свідоцтва.' );
		}

		global $wpdb;

		// Отримуємо всі дані мерилки та судна
		$sql = "SELECT m.*, s.Sailboat_Name, s.Sailboat_NumberSail, s.RegNumber, s.Sailboat_Year, u.FIO 
				FROM Merilka m 
				JOIN vSailboat s ON s.Sailboat_ID = m.Sailboat_ID 
				LEFT JOIN vUserFSTU u ON u.User_ID = m.User_ID
				WHERE m.MR_ID = %d LIMIT 1";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$item = $wpdb->get_row( $wpdb->prepare( $sql, $mr_id ), ARRAY_A );

		if ( ! $item ) {
			wp_die( 'Свідоцтво не знайдено.' );
		}

		// Підключаємо чистий шаблон для друку
		include FSTU_PLUGIN_DIR . 'views/merilkas/print-template.php';
		exit; // Зупиняємо виконання WP, щоб не завантажувати зайвий HTML теми
	}
    /**
	 * Повертає дані однієї мерилки для завантаження у форму (редагування або клонування).
	 */
	public function handle_get_single(): void {
		check_ajax_referer( 'fstu_merilkas_nonce', 'nonce' );

		$mr_id = absint( $_POST['mr_id'] ?? 0 );
		if ( $mr_id <= 0 ) {
			wp_send_json_error( [ 'message' => 'Невірний ідентифікатор свідоцтва.' ] );
		}

		global $wpdb;

		// Отримуємо всі поля мерилки
		$sql = "SELECT * FROM Merilka WHERE MR_ID = %d LIMIT 1";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$item = $wpdb->get_row( $wpdb->prepare( $sql, $mr_id ), ARRAY_A );

		if ( ! $item ) {
			wp_send_json_error( [ 'message' => 'Свідоцтво не знайдено.' ] );
		}

		// Перевіряємо права доступу (користувач має право керувати мерилками цього судна)
		if ( ! $this->current_user_can_manage_merilka( (int) $item['Sailboat_ID'] ) ) {
			wp_send_json_error( [ 'message' => 'Немає прав для доступу до цього свідоцтва.' ] );
		}

		wp_send_json_success( [ 'item' => $item ] );
	}
}