<?php
/**
 * AJAX-обробники для модальних вікон (Картка, Клуб, Протокол, Звіт) модуля "Реєстр".
 *
 * Version:     1.2.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Registry
 */

namespace FSTU\Registry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Registry_Modals_Ajax {

	/**
	 * Реєструє AJAX хуки WordPress для модальних вікон.
	 */
	public function init(): void {
		// Авторизовані користувачі
		add_action( 'wp_ajax_fstu_get_member_card', [ $this, 'handle_get_member_card' ] );
		add_action( 'wp_ajax_fstu_get_club_info', [ $this, 'handle_get_club_info' ] );
		add_action( 'wp_ajax_fstu_get_protocol', [ $this, 'handle_get_protocol' ] );
		add_action( 'wp_ajax_fstu_get_report', [ $this, 'handle_get_report' ] );
	}

	/**
	 * Отримує дані для вкладок "Картки члена ФСТУ".
	 */
	public function handle_get_member_card(): void {
		check_ajax_referer( Registry_List::NONCE_ACTION, 'nonce' );

		$user_id = absint( $_POST['user_id'] ?? 0 );
		if ( ! $user_id ) {
			wp_send_json_error( [ 'message' => 'Невірний ID користувача.' ] );
		}

		$is_admin = current_user_can( 'manage_options' );
		$is_self  = get_current_user_id() === $user_id;

		// Виправлена перевірка згоди. Мета-поле може містити '1' або true.
		$has_consent = get_user_meta( $user_id, 'privacy_consent', true ) == '1';

		if ( ! $has_consent && ! $is_admin && ! $is_self ) {
			wp_send_json_error( [ 'message' => 'Користувач обмежив доступ до своїх персональних даних.' ] );
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			wp_send_json_error( [ 'message' => 'Користувача не знайдено.' ] );
		}

		// Отримуємо фото (аватар)
		$photo_url = get_avatar_url( $user_id, [ 'size' => 150 ] );
		if ( ! $photo_url || str_contains( $photo_url, 'avatar-default' ) ) {
			// Використовуємо константу з головного файлу плагіна
			$photo_url = plugin_dir_url( FSTU_REGISTRY_FILE ) . 'assets/img/avatar-placeholder.png';
		}

		// Форматуємо дату
		$birth_date_raw = get_user_meta( $user_id, 'BirthDate', true );
		$birth_date_formatted = '';
		if ( ! empty( $birth_date_raw ) ) {
			$birth_date_obj = date_create( $birth_date_raw );
			if ( $birth_date_obj ) {
				$birth_date_formatted = date_format( $birth_date_obj, 'd.m.Y' );
			}
		}

		// Формуємо безпечний масив даних для вкладок
		$data = [
			'general' => [
				'name'        => esc_html( trim( $user->last_name . ' ' . $user->first_name . ' ' . get_user_meta( $user_id, 'Patronymic', true ) ) ),
				'email'       => ( $is_admin || $is_self ) ? esc_html( $user->user_email ) : 'Приховано',
				'phone'       => ( $is_admin || $is_self ) ? esc_html( get_user_meta( $user_id, 'Phone', true ) ) : 'Приховано',
				'birth_date'  => esc_html( $birth_date_formatted ),
				'skype'       => ( $is_admin || $is_self ) ? esc_html( get_user_meta( $user_id, 'Skype', true ) ) : 'Приховано',
				'facebook'    => ( $is_admin || $is_self ) ? esc_url( get_user_meta( $user_id, 'Facebook', true ) ) : 'Приховано',
				'photo_url'   => esc_url( $photo_url ),
				'has_consent' => $has_consent,
			],
			// TODO: Далі тут будуть SQL-запити для вкладок "Досвід", "Розряди", "Судна" тощо
		];

		wp_send_json_success( $data );
	}

	/**
	 * Отримує інформацію про клуб.
	 */
	public function handle_get_club_info(): void {
		check_ajax_referer( Registry_List::NONCE_ACTION, 'nonce' );

		$club_id = absint( $_POST['club_id'] ?? 0 );
		if ( ! $club_id ) {
			wp_send_json_error( [ 'message' => 'Невірний ID клубу.' ] );
		}

		global $wpdb;

		// Використовуємо поле Club_Adr замість окремої таблиці міст
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$club = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM S_Club WHERE Club_ID = %d",
				$club_id
			),
			ARRAY_A
		);

		if ( ! $club ) {
			wp_send_json_error( [ 'message' => 'Клуб не знайдено.' ] );
		}

		wp_send_json_success( [
			'name' => esc_html( $club['Club_Name'] ?? '' ),
			'city' => esc_html( $club['Club_Adr'] ?? 'Не вказано адреси' ),
		] );
	}
	/**
	 * Отримує дані для таблиці "Протокол" (з пагінацією).
	 */
	public function handle_get_protocol(): void {
		check_ajax_referer( Registry_List::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Недостатньо прав для перегляду протоколу.' ] );
		}

		// TODO: SQL-логіка для Протоколу
		wp_send_json_success( [
			'html'  => '<tr><td colspan="5" style="text-align:center;">Тут будуть дані протоколу...</td></tr>',
			'total' => 0,
		] );
	}

	/**
	 * Отримує дані для таблиці "Звіт" (всі дані одразу).
	 */
	public function handle_get_report(): void {
		check_ajax_referer( Registry_List::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Недостатньо прав для формування звіту.' ] );
		}

		// TODO: SQL-логіка для Звіту
		wp_send_json_success( [
			'html' => '<tr><td colspan="5" style="text-align:center;">Тут будуть дані звіту для копіювання...</td></tr>',
		] );
	}
}
