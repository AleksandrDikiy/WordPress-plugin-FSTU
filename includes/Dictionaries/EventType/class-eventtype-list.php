<?php

namespace FSTU\Dictionaries\EventType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Контролер модуля "Довідник типів заходів".
 * Version:     1.0.0
 * Date_update: 2026-04-07
 */

class EventType_List {

	public const NONCE_ACTION = 'fstu_eventtype_nonce';
	private const ASSET_HANDLE = 'fstu-eventtype';

	public function init(): void {
		add_shortcode( 'fstu_eventtype', [ $this, 'render_shortcode' ] );
	}

	public function render_shortcode( array $atts = [] ): string {
		if ( ! current_user_can( 'administrator' ) ) {
			return '<div class="fstu-alert fstu-alert-danger">Немає прав для перегляду.</div>';
		}

		$this->enqueue_assets();

		ob_start();
		include FSTU_PLUGIN_DIR . 'views/eventtype/main-page.php';
		return ob_get_clean();
	}
    public static function get_module_url( string $context = 'default' ): string {
        // Повертаємо URL для цього модуля
        return admin_url('admin.php?page=fstu-main'); // Замініть на правильний slug, якщо він відрізняється
    }
	private function enqueue_assets(): void {
		$ver = FSTU_VERSION;

		// Пустий масив залежностей згідно з AGENTS.md
		wp_enqueue_style( self::ASSET_HANDLE, FSTU_PLUGIN_URL . 'css/fstu-eventtype.css', [], $ver );
		wp_enqueue_script( self::ASSET_HANDLE, FSTU_PLUGIN_URL . 'js/fstu-eventtype.js', [ 'jquery' ], $ver, true );

		wp_localize_script( self::ASSET_HANDLE, 'fstuEventType', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
			'strings' => [
				'confirmDelete' => 'Ви дійсно хочете ВИДАЛИТИ запис?',
				'loading'       => 'Завантаження...',
				'error'         => 'Сталася помилка.',
			],
		] );
	}
}