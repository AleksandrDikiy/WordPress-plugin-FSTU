<?php
/**
 * Контролер відображення модуля "Довідник видів змагань ФСТУ".
 * Реєструє шорткод [fstu_typeevent], підключає скрипти/стилі,
 * передає локалізовані змінні у JS.
 *
 * Version:     1.1.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\TypeEvent
 */

namespace FSTU\Dictionaries\TypeEvent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TypeEvent_List {

	/** Slug для enqueue (JS/CSS handle). */
	private const ASSET_HANDLE = 'fstu-typeevent';

	/** Nonce action для AJAX-запитів. */
	public const NONCE_ACTION = 'fstu_typeevent_nonce';

	/**
	 * Реєструє WordPress хуки.
	 */
	public function init(): void {
		add_shortcode( 'fstu_typeevent', [ $this, 'render_shortcode' ] );
	}

	/**
	 * Рендерить HTML модуля довідника видів змагань.
	 *
	 * @param array $atts Атрибути шорткоду.
	 * @return string
	 */
	public function render_shortcode( array $atts = [] ): string {
		$view_data = $this->get_view_data();
		$this->enqueue_assets();

		extract( $view_data, EXTR_SKIP );

		ob_start();
		include FSTU_PLUGIN_DIR . 'views/typeevent/main-page.php';
		return ob_get_clean();
	}

	/**
	 * Підключає CSS та JS модуля.
	 */
	private function enqueue_assets(): void {
		$ver         = FSTU_VERSION;
		$permissions = $this->get_permissions();

		wp_enqueue_style(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'css/fstu-typeevent.css',
			[],
			$ver
		);

		wp_enqueue_script(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'js/fstu-typeevent.js',
			[ 'jquery' ],
			$ver,
			true
		);

		wp_localize_script(
			self::ASSET_HANDLE,
			'fstuTypeEventL10n',
			[
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( self::NONCE_ACTION ),
				'permissions' => $permissions,
				'table'       => [
					'colspan' => $permissions['canView'] ? 4 : 3,
				],
				'messages'    => [
					'loading'       => __( 'Завантаження...', 'fstu' ),
					'error'         => __( 'Помилка завантаження даних.', 'fstu' ),
					'protocolTitle' => __( 'ПРОТОКОЛ змін довідника', 'fstu' ),
					'protocolListTitle' => __( 'Окремий протокол TypeEvent', 'fstu' ),
					'protocolError' => __( 'Не вдалося завантажити протокол.', 'fstu' ),
					'protocolEmpty' => __( 'Записи протоколу відсутні.', 'fstu' ),
					'confirmDelete' => __( 'Ви дійсно хочете видалити цей вид змагань?', 'fstu' ),
					'deleteSuccess' => __( 'Вид змагань успішно видалений.', 'fstu' ),
					'deleteError'   => __( 'Помилка при видаленні виду змагань.', 'fstu' ),
					'saveSuccess'   => __( 'Вид змагань успішно збережений.', 'fstu' ),
					'saveError'     => __( 'Помилка при збереженні виду змагань.', 'fstu' ),
					'noResults'     => __( 'Немає записів, які б відповідали критеріям пошуку.', 'fstu' ),
				],
			]
		);
	}

	/**
	 * Готує дані для views.
	 *
	 * @return array<string,mixed>
	 */
	private function get_view_data(): array {
		return [
			'permissions' => $this->get_permissions(),
		];
	}

	/**
	 * Повертає права поточного користувача для модуля.
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

