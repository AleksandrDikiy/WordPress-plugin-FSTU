<?php
/**
 * Контролер відображення модуля "Довідник осередків ФСТУ".
 * Реєструє шорткод [fstu_units], підключає скрипти/стилі,
 * передає локалізовані змінні у JS.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\Units
 */

namespace FSTU\Dictionaries\Units;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Units_List {

	/** Slug для enqueue (JS/CSS handle). */
	private const ASSET_HANDLE = 'fstu-units';

	/** Nonce action для AJAX-запитів. */
	public const NONCE_ACTION = 'fstu_units_nonce';

	/**
	 * Реєструє WordPress хуки.
	 */
	public function init(): void {
		add_shortcode( 'fstu_units', [ $this, 'render_shortcode' ] );
	}

	/**
	 * Рендерить HTML модуля довідника осередків.
	 * Підключає скрипти/стилі тільки при наявності шорткоду на сторінці.
	 *
	 * @param array $atts Атрибути шорткоду (наразі не використовуються).
	 * @return string HTML-вміст модуля.
	 */
	public function render_shortcode( array $atts = [] ): string {
		$view_data = $this->get_view_data();
		$this->enqueue_assets();

		extract( $view_data, EXTR_SKIP );

		ob_start();
		include FSTU_PLUGIN_DIR . 'views/units/main-page.php';
		return ob_get_clean();
	}

	/**
	 * Підключає CSS та JS для модуля осередків.
	 * Передає бекенд-змінні у JS через wp_localize_script.
	 */
	private function enqueue_assets(): void {
		$ver         = FSTU_VERSION;
		$permissions = $this->get_permissions();

		// CSS (без залежностей, щоб тема не блокувала завантаження)
		wp_enqueue_style(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'css/fstu-units.css',
			[],
			$ver
		);

		// JS
		wp_enqueue_script(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'js/fstu-units.js',
			[ 'jquery' ],
			$ver,
			true
		);

		// Локалізація для JS
		wp_localize_script(
			self::ASSET_HANDLE,
			'fstuUnitsL10n',
			[
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( self::NONCE_ACTION ),
				'permissions' => $permissions,
				'table' => [
					'colspan' => $permissions['canView'] ? 9 : 8,
				],
				'messages' => [
					'loading'       => __( 'Завантаження...', 'fstu' ),
					'error'         => __( 'Помилка завантаження даних.', 'fstu' ),
					'confirmDelete' => __( 'Ви дійсно хочете видалити цей осередок?', 'fstu' ),
					'deleteSuccess' => __( 'Осередок успішно видалений.', 'fstu' ),
					'deleteError'   => __( 'Помилка при видаленні осередка.', 'fstu' ),
					'saveSuccess'   => __( 'Осередок успішно збережений.', 'fstu' ),
					'saveError'     => __( 'Помилка при збереженні осередка.', 'fstu' ),
					'noResults'     => __( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ),
				],
			]
		);
	}

	/**
	 * Готує дані для views модуля.
	 *
	 * @return array<string,mixed>
	 */
	private function get_view_data(): array {
		global $wpdb;

		$permissions = $this->get_permissions();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$regions = $wpdb->get_results( 'SELECT Region_ID, Region_Name FROM S_Region ORDER BY Region_Name ASC', ARRAY_A );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$unit_types = $wpdb->get_results( 'SELECT UnitType_ID, UnitType_Name FROM S_UnitType ORDER BY UnitType_Order ASC, UnitType_Name ASC', ARRAY_A );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$opfs = $wpdb->get_results( 'SELECT OPF_ID, OPF_Name FROM S_OPF ORDER BY OPF_Name ASC', ARRAY_A );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$parent_units = $wpdb->get_results( 'SELECT Unit_ID, Unit_Name FROM S_Unit ORDER BY Unit_Name ASC', ARRAY_A );

		return [
			'permissions' => $permissions,
			'regions'     => is_array( $regions ) ? $regions : [],
			'unit_types'  => is_array( $unit_types ) ? $unit_types : [],
			'opfs'        => is_array( $opfs ) ? $opfs : [],
			'parent_units'=> is_array( $parent_units ) ? $parent_units : [],
		];
	}

	/**
	 * Повертає прапорці доступу для поточного користувача.
	 *
	 * @return array<string,bool>
	 */
	private function get_permissions(): array {
		$user  = wp_get_current_user();
		$roles = is_array( $user->roles ) ? $user->roles : [];

		$can_manage = current_user_can( 'manage_options' ) || in_array( 'administrator', $roles, true ) || in_array( 'userregistrar', $roles, true );
		$can_delete = current_user_can( 'manage_options' ) || in_array( 'administrator', $roles, true );

		return [
			'canView'   => true,
			'canManage' => $can_manage,
			'canDelete' => $can_delete,
		];
	}
}

