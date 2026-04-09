<?php
/**
 * Контролер довідника "Види туризму" — реєстрація шорткоду та підключення assets.
 *
 * Shortcode: [fstu_tourismtype_list]
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
 * Class TourismType_List
 *
 * Відповідає виключно за:
 * — реєстрацію шорткоду [fstu_tourismtype_list];
 * — підключення JS/CSS тільки на тих сторінках, де є шорткод;
 * — рендер HTML-каркасу через view-шаблон.
 */
class TourismType_List {

	/** Slug шорткоду. */
	private const SHORTCODE = 'fstu_tourismtype_list';

	/** Версія assets (для cache-busting). */
	private const ASSETS_VERSION = '1.0.0';

	/** Slug скрипту (для wp_localize_script). */
	private const JS_HANDLE = 'fstu-tourismtype';

	/** Slug стилів. */
	private const CSS_HANDLE = 'fstu-tourismtype';

	/** Прапорець: assets вже зареєстровано на цій сторінці. */
	private bool $assets_enqueued = false;

	// -------------------------------------------------------------------------
	// Ініціалізація
	// -------------------------------------------------------------------------

	/**
	 * Реєструємо шорткод і хук підключення assets.
	 */
	public function init(): void {
		add_shortcode( self::SHORTCODE, [ $this, 'render_shortcode' ] );
	}

	// -------------------------------------------------------------------------
	// Шорткод
	// -------------------------------------------------------------------------

	/**
	 * Рендерить HTML-каркас модуля.
	 * Assets підключаються лише під час першого виклику шорткоду на сторінці.
	 *
	 * @return string HTML-вивід модуля.
	 */
	public function render_shortcode(): string {
		$this->enqueue_assets();

		ob_start();
		$view = FSTU_PLUGIN_DIR . 'views/tourismtype/main-page.php';
		if ( file_exists( $view ) ) {
			include $view;
		}
		return ob_get_clean();
	}

	// -------------------------------------------------------------------------
	// Assets
	// -------------------------------------------------------------------------

	/**
	 * Підключає CSS та JS модуля (тільки один раз за запит).
	 * Залежності CSS — пустий масив [], щоб уникнути блокування WordPress.
	 */
	private function enqueue_assets(): void {
		if ( $this->assets_enqueued ) {
			return;
		}
		$this->assets_enqueued = true;

		$plugin_url = FSTU_PLUGIN_URL; // константа визначена у fstu.php

		// ---- CSS ----
		wp_enqueue_style(
			self::CSS_HANDLE,
			$plugin_url . 'css/fstu-tourismtype.css',
			[],                  // УВАГА: порожній масив залежностей — обов'язково!
			self::ASSETS_VERSION,
			'screen'
		);

		// ---- JS ----
		wp_enqueue_script(
			self::JS_HANDLE,
			$plugin_url . 'js/fstu-tourismtype.js',
			[ 'jquery' ],
			self::ASSETS_VERSION,
			true                 // підключати у footer
		);

		// ---- Локалізація: передача PHP-змінних у JS ----
		wp_localize_script(
			self::JS_HANDLE,
			'fstuTourismType',   // глобальна JS-змінна
			[
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'fstu_tourismtype_nonce' ),
				'isAdmin'     => current_user_can( 'manage_options' ) ? '1' : '0',
				'i18n'        => [
					'confirmDelete'  => __( 'Ви дійсно хочете ВИДАЛИТИ цей запис?', 'fstu' ),
					'errorSave'      => __( 'Помилка збереження. Спробуйте ще раз.', 'fstu' ),
					'errorLoad'      => __( 'Помилка завантаження даних.', 'fstu' ),
					'noData'         => __( 'Дані відсутні.', 'fstu' ),
					'saving'         => __( 'Збереження…', 'fstu' ),
					'loading'        => __( 'Завантаження…', 'fstu' ),
				],
			]
		);
	}

	// -------------------------------------------------------------------------
	// Статичний хелпер: URL модуля (для хабу / адмінки)
	// -------------------------------------------------------------------------

	/**
	 * Повертає URL сторінки з шорткодом довідника.
	 * За потреби передай $context = 'admin' для альтернативного URL.
	 *
	 * @param  string $context Контекст виклику ('default' | 'admin').
	 * @return string          URL або порожній рядок, якщо сторінки немає.
	 */
	public static function get_module_url( string $context = 'default' ): string {
		$page = get_page_by_path( 'tourismtype' ); // slug сторінки з шорткодом
		if ( $page ) {
			return get_permalink( $page->ID );
		}
		return '';
	}
}
