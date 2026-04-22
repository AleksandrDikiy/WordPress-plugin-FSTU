<?php
/**
 * Контролер списку модуля "Реєстр мерилок".
 *
 * Version:     1.0.0
 * Date_update: 2026-04-09
 *
 * @package FSTU\Modules\UserFstu\Merilkas
 */

namespace FSTU\Modules\Registry\Merilkas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Merilkas_List {

	public function init(): void {
		// Реєструємо шорткод [fstu_merilkas]
		add_shortcode( 'fstu_merilkas', [ $this, 'render_shortcode' ] );
		
		// Реєструємо підключення скриптів та стилів
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
	}

	public function register_assets(): void {
		$version = defined( 'FSTU_VERSION' ) ? FSTU_VERSION : '1.0.0';

		// Реєструємо стилі (які ми створили у Фазі 3)
		wp_register_style(
			'fstu-merilkas-style',
			FSTU_PLUGIN_URL . 'css/fstu-merilkas.css',
			[],
			$version
		);

		// Реєструємо скрипти (які ми створили у Фазі 2, 4 та 5)
		wp_register_script(
			'fstu-merilkas-script',
			FSTU_PLUGIN_URL . 'js/fstu-merilkas.js',
			[ 'jquery' ],
			$version,
			true
		);

		// Передаємо змінні з PHP у JavaScript
		wp_localize_script(
			'fstu-merilkas-script',
			'fstuMerilkasL10n',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'fstu_merilkas_nonce' ),
			]
		);
	}

	public function render_shortcode( $atts = [] ): string {
		// Підключаємо активи тільки коли шорткод викликається на сторінці
		wp_enqueue_style( 'fstu-merilkas-style' );
		wp_enqueue_script( 'fstu-merilkas-script' );

		ob_start();
		
		// Обов'язково завантажуємо HTML модального вікна форми, щоб воно існувало на сторінці
		$form_path = FSTU_PLUGIN_DIR . 'views/merilkas/modal-form.php';
		if ( file_exists( $form_path ) ) {
			include $form_path;
		}

		// Текст-підказка тепер виводиться ПІСЛЯ форми (внизу)
		echo '<div class="fstu-merilkas-wrapper" style="margin-top: 20px; padding: 16px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; text-align: center; color: #4b5563;">';
		echo '<h4 style="margin: 0 0 8px; font-size: 14px; color: #1f2937;">Реєстр обмірних свідоцтв (Мерилок)</h4>';
		echo '<p style="margin: 0; font-size: 13px;">Цей модуль інтегровано у <strong>Судновий Реєстр</strong>. Ви можете керувати мерилками судна безпосередньо з його картки на вкладці "Мерилки".</p>';
		echo '</div>';

		return ob_get_clean();
	}
}