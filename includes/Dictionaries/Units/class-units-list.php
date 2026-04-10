<?php
/**
 * Контролер відображення модуля "Довідник осередків ФСТУ".
 * Реєструє шорткод [fstu_units], підключає скрипти/стилі,
 * передає локалізовані змінні у JS.
 * * Version: 1.0.1
 * Date_update: 2026-04-10
 */

namespace FSTU\Dictionaries\Units;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Заборона прямого доступу
}

class Units_List {

	public function init(): void {
		add_shortcode( 'fstu_units', [ $this, 'render_shortcode' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	public function enqueue_assets(): void {
		global $post;

		// Підключаємо скрипти та стилі ТІЛЬКИ якщо на сторінці є наш шорткод [fstu_units]
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'fstu_units' ) ) {
			
			// Згідно з AGENTS.md, масив залежностей порожній [], щоб уникнути блокування завантаження
			wp_enqueue_style(
				'fstu-units-style',
				plugins_url( 'css/fstu-units.css', dirname( __FILE__, 3 ) ),
				[],
				'1.0.0'
			);

			wp_enqueue_script(
				'fstu-units-script',
				plugins_url( 'js/fstu-units.js', dirname( __FILE__, 3 ) ),
				[ 'jquery' ], // jQuery дозволено в безпечному режимі
				'1.0.0',
				true
			);

			// Передаємо локалізовані змінні у JS
			wp_localize_script( 'fstu-units-script', 'fstuUnitsData', [
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'fstu_units_nonce' ),
				'i18n'     => [
					'error'   => __( 'Сталася помилка. Спробуйте пізніше.', 'fstu' ),
					'confirm' => __( 'Ви дійсно хочете видалити цей запис?', 'fstu' )
				]
			] );
		}
	}

	public function render_shortcode( $atts ): string {
		ob_start();
		// Підключаємо головний view-каркас
		$view_path = plugin_dir_path( dirname( __FILE__, 3 ) ) . 'views/units/main-page.php';
		if ( file_exists( $view_path ) ) {
			require $view_path;
		} else {
			echo '<div class="fstu-alert fstu-alert--error">Помилка: Шаблон не знайдено.</div>';
		}
		return ob_get_clean();
	}
}