<?php
/**
 * Контролер відображення модуля "Реєстр членів ФСТУ".
 * Реєструє шорткод [fstu_registry], підключає скрипти/стилі,
 * передає локалізовані змінні у JS.
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

class Registry_List {

	/** Slug для enqueue (JS/CSS handle). */
	private const ASSET_HANDLE = 'fstu-registry';

	/** Nonce action для AJAX-запитів реєстру. */
	public const NONCE_ACTION = 'fstu_registry_nonce';

	/**
	 * Реєструє WordPress хуки.
	 */
	public function init(): void {
		add_shortcode( 'fstu_registry', [ $this, 'render_shortcode' ] );
	}

	/**
	 * Рендерить HTML модуля реєстру.
	 * Підключає скрипти/стилі тільки при наявності шорткоду на сторінці.
	 *
	 * @param array $atts Атрибути шорткоду (наразі не використовуються).
	 * @return string HTML-вміст модуля.
	 */
	public function render_shortcode( array $atts = [] ): string {
		$this->enqueue_assets();

		ob_start();
		include FSTU_PLUGIN_DIR . 'views/registry/main-page.php';
		return ob_get_clean();
	}

	/**
	 * Підключає CSS та JS для модуля реєстру.
	 * Передає бекенд-змінні у JS через wp_localize_script.
	 */
	private function enqueue_assets(): void {
		$ver = FSTU_VERSION;

		wp_enqueue_style(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'css/fstu-registry.css',
			[],
			$ver
		);

		wp_enqueue_script(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'js/fstu-registry.js',
			[ 'jquery' ],
			$ver,
			true // у футері
		);

		// Передаємо дані у JS (без inline-скриптів у PHP!)
		wp_localize_script(
			self::ASSET_HANDLE,
			'fstuRegistry',
			[
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( self::NONCE_ACTION ),
				'isAdmin'        => current_user_can( 'manage_options' ) ? '1' : '0',
				'isLoggedIn'     => is_user_logged_in() ? '1' : '0',
				'currentUserId'  => get_current_user_id(),
				'turnstileSiteKey' => defined( 'FSTU_TURNSTILE_SITE_KEY' ) ? FSTU_TURNSTILE_SITE_KEY : '',
				'currentYear'    => (int) date( 'Y' ),
				'strings'        => [
					'loading'      => 'Завантаження...',
					'noResults'    => 'Записів не знайдено.',
					'errorGeneric' => 'Сталася помилка. Спробуйте ще раз.',
					'confirmReset' => 'Скинути всі фільтри?',
				],
			]
		);
	}

	/**
	 * Повертає список одиниць (ОФСТ) для фільтру.
	 * Використовується у filter-bar.php.
	 *
	 * @return array<int, array{Unit_ID: int, Unit_ShortName: string, Region_Code: string}>
	 */
	public static function get_units(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$results = $wpdb->get_results(
			"SELECT Unit_ID, Unit_ShortName, Region_Code
             FROM vFilterSUnit
             ORDER BY Region_Code ASC, Unit_ShortName ASC",
			ARRAY_A
		);

		// Fallback: якщо view не існує, читаємо напряму з S_Unit
		if ( null === $results ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$results = $wpdb->get_results(
				"SELECT u.Unit_ID, u.Unit_ShortName, r.Region_Code
				 FROM S_Unit u
				 LEFT JOIN S_Region r ON r.Region_ID = u.Region_ID
				 WHERE u.Unit_ID IN (
				     SELECT DISTINCT ur.Unit_ID FROM UserRegistationOFST ur
				 )
				 ORDER BY r.Region_Code ASC, u.Unit_ShortName ASC",
				ARRAY_A
			);
		}

		return $results ?? [];
	}

	/**
	 * Повертає список видів туризму для фільтру.
	 *
	 * @return array<int, array{TourismType_ID: int, TourismType_Name: string}>
	 */
	public static function get_tourism_types(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$results = $wpdb->get_results(
			"SELECT TourismType_ID, TourismType_Name
			 FROM S_TourismType
			 WHERE TourismType_ID IN (
			     SELECT DISTINCT TourismType_ID FROM UserTourismType
			 )
			 ORDER BY TourismType_Order ASC",
			ARRAY_A
		);

		return $results ?? [];
	}

	/**
	 * Повертає список клубів для фільтру.
	 *
	 * @return array<int, array{Club_ID: int, Club_Name: string}>
	 */
	public static function get_clubs(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$results = $wpdb->get_results(
			"SELECT c.Club_ID, c.Club_Name
			 FROM S_Club c
			 WHERE c.Club_ID IN (SELECT DISTINCT Club_ID FROM UserClub)
			 ORDER BY c.Club_Name ASC",
			ARRAY_A
		);

		return $results ?? [];
	}

	/**
	 * Повертає список доступних років для фільтру членських внесків.
	 *
	 * @return int[]
	 */
	public static function get_years(): array {
		$current = (int) date( 'Y' );
		$years   = [];
		for ( $y = $current; $y >= 2010; $y-- ) {
			$years[] = $y;
		}
		return $years;
	}
}
