<?php
/**
 * Контролер відображення модуля "Довідник клубів ФСТУ".
 * Реєструє шорткод [fstu_clubs], підключає скрипти/стилі.
 *
 * Доступ:
 *   Всі відвідувачі   → перегляд списку
 *   userfstu           → перегляд + деталі
 *   userregistrar      → + додавання, редагування
 *   administrator      → + видалення
 *
 * Version:     1.0.0
 * Date_update: 2026-04-05
 *
 * @package FSTU\Clubs
 */

namespace FSTU\Clubs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Clubs_List {

	private const ASSET_HANDLE = 'fstu-clubs';
	public const  NONCE_ACTION  = 'fstu_clubs_nonce';

	public function init(): void {
		add_shortcode( 'fstu_clubs', [ $this, 'render_shortcode' ] );
	}

	/**
	 * Рендер шорткоду [fstu_clubs].
	 */
	public function render_shortcode( array $atts = [] ): string {
		$this->enqueue_assets();

		ob_start();
		include FSTU_PLUGIN_DIR . 'views/clubs/main-page.php';
		return ob_get_clean();
	}

	/**
	 * Підключає CSS та JS тільки при наявності шорткоду.
	 * Усі дані передаються через wp_localize_script — жодних inline-скриптів.
	 */
	private function enqueue_assets(): void {
		$ver = FSTU_VERSION;

		wp_enqueue_style(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'css/fstu-clubs.css',
			[], // успадковує CSS-змінні базового CSS
			$ver
		);

		wp_enqueue_script(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'js/fstu-clubs.js',
			[ 'jquery' ],
			$ver,
			true
		);

		// Права поточного користувача (обчислюємо один раз на сервері)
		$user        = wp_get_current_user();
		$roles       = (array) $user->roles;
		$is_admin    = in_array( 'administrator', $roles, true );
		$is_reg      = in_array( 'userregistrar',  $roles, true );
		$is_logged   = is_user_logged_in();

		wp_localize_script(
			self::ASSET_HANDLE,
			'fstuClubs',
			[
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( self::NONCE_ACTION ),
				'isAdmin'   => $is_admin    ? '1' : '0',
				'isReg'     => ( $is_admin || $is_reg ) ? '1' : '0',
				'isLogged'  => $is_logged   ? '1' : '0',
				'strings'   => [
					'confirmDelete' => 'Ви дійсно хочете ВИДАЛИТИ клуб?',
					'addTitle'      => 'Додавання клубу',
					'editTitle'     => 'Редагування клубу',
					'noData'        => 'Клубів не знайдено.',
					'errorGeneric'  => 'Сталася помилка. Спробуйте ще раз.',
					'saving'        => 'Збереження...',
					'deleting'      => 'Видалення...',
				],
			]
		);
	}
}
