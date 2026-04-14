<?php
namespace FSTU\Modules\Calendar;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use FSTU\Core\Capabilities;
use FSTU\Modules\Calendar\CalendarApplications\Calendar_Applications_Repository;
use FSTU\Modules\Calendar\CalendarEvents\Calendar_Events_Repository;
use FSTU\Modules\Calendar\CalendarResults\Calendar_Results_Repository;
use FSTU\Modules\Calendar\CalendarRoutes\Calendar_Routes_Repository;

/**
 * Контролер відображення модуля "Календарний план змагань ФСТУ".
 *
 * Version: 1.3.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar
 */
class Calendar_List {

	private const ASSET_HANDLE = 'fstu-calendar';
	public const SHORTCODE     = 'fstu_calendar';
	public const NONCE_ACTION  = 'fstu_calendar_nonce';
	private const ROUTE_OPTION = 'fstu_calendar_page_url';

	private ?Calendar_Events_Repository $repository = null;
	private ?Calendar_Applications_Repository $applications_repository = null;
	private ?Calendar_Routes_Repository $routes_repository = null;
	private ?Calendar_Results_Repository $results_repository = null;

	public function init(): void {
		add_shortcode( self::SHORTCODE, [ $this, 'render_shortcode' ] );
	}

	/**
	 * Рендерить HTML модуля.
	 */
	public function render_shortcode( array $atts = [] ): string {
		$this->enqueue_assets();

		$view_data = $this->get_view_data();
		extract( $view_data, EXTR_SKIP );

		ob_start();
		include FSTU_PLUGIN_DIR . 'views/calendar/main-page.php';

		return (string) ob_get_clean();
	}

	/**
	 * Повертає канонічний URL модуля Calendar.
	 */
	public static function get_module_url( string $context = 'default' ): string {
		$configured_url = get_option( self::ROUTE_OPTION, '' );
		$configured_url = is_string( $configured_url ) ? trim( $configured_url ) : '';

		if ( '' !== $configured_url ) {
			if ( str_starts_with( $configured_url, '/' ) ) {
				return home_url( $configured_url );
			}

			if ( wp_http_validate_url( $configured_url ) ) {
				return $configured_url;
			}
		}

		global $wpdb;
		$shortcode_like = '%' . $wpdb->esc_like( '[' . self::SHORTCODE ) . '%';
		$post_types     = [ 'page', 'post' ];
		$placeholders   = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
		$sql            = "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ({$placeholders}) AND post_content LIKE %s ORDER BY CASE WHEN post_type = 'page' THEN 0 ELSE 1 END ASC, menu_order ASC, ID ASC LIMIT 1";
		$page_id        = (int) $wpdb->get_var( $wpdb->prepare( $sql, ...array_merge( $post_types, [ $shortcode_like ] ) ) );

		if ( $page_id <= 0 ) {
			return '';
		}

		$permalink = get_permalink( $page_id );

		return is_string( $permalink ) ? $permalink : '';
	}

	/**
	 * Підключає CSS та JS модуля.
	 */
	private function enqueue_assets(): void {
		wp_enqueue_style(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'css/fstu-calendar.css',
			[],
			FSTU_VERSION
		);

		wp_enqueue_script(
			self::ASSET_HANDLE,
			FSTU_PLUGIN_URL . 'js/fstu-calendar.js',
			[ 'jquery' ],
			FSTU_VERSION,
			true
		);

		wp_localize_script(
			self::ASSET_HANDLE,
			'fstuCalendarL10n',
			[
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( self::NONCE_ACTION ),
				'moduleUrl'    => self::get_module_url(),
				'permissions'  => Capabilities::get_calendar_permissions(),
				'datasets'     => $this->get_combined_datasets(),
				'currentUserId'=> get_current_user_id(),
				'currentYear'  => (int) current_time( 'Y' ),
				'currentMonth' => (int) current_time( 'n' ),
				'messages'     => [
					'loading'        => __( 'Завантаження...', 'fstu' ),
					'error'          => __( 'Сталася помилка завантаження даних.', 'fstu' ),
					'empty'          => __( 'Записів не знайдено.', 'fstu' ),
					'protocolEmpty'  => __( 'Записи протоколу відсутні.', 'fstu' ),
					'confirmDelete'  => __( 'Ви дійсно хочете видалити цей захід?', 'fstu' ),
					'deleteSuccess'  => __( 'Захід успішно видалено.', 'fstu' ),
					'saveSuccess'    => __( 'Зміни успішно збережено.', 'fstu' ),
					'routeDeleteConfirm' => __( 'Ви дійсно хочете видалити цю ділянку маршруту?', 'fstu' ),
					'routeDeleteSuccess' => __( 'Ділянку маршруту успішно видалено.', 'fstu' ),
					'routeSendSuccess'   => __( 'Маршрут успішно відправлено на погодження МКК.', 'fstu' ),
					'routeReviewSuccess' => __( 'Рішення МКК успішно збережено.', 'fstu' ),
					'placeholder'    => __( 'Каркас секції підготовлено. Наступні підмодулі будуть реалізовані в наступних ітераціях.', 'fstu' ),
				],
			]
		);
	}

	/**
	 * Готує дані для view.
	 *
	 * @return array<string, mixed>
	 */
	private function get_view_data(): array {
		return [
			'permissions' => Capabilities::get_calendar_permissions(),
			'nonce'       => wp_create_nonce( self::NONCE_ACTION ),
			'datasets'    => $this->get_combined_datasets(),
			'current_year'=> (int) current_time( 'Y' ),
		];
	}

	/**
	 * Повертає об’єднані datasets для shell і підмодулів.
	 *
	 * @return array<string, mixed>
	 */
	private function get_combined_datasets(): array {
		return array_merge(
			$this->get_repository()->get_filter_datasets(),
			$this->get_applications_repository()->get_support_datasets(),
			$this->get_routes_repository()->get_support_datasets(),
			$this->get_results_repository()->get_support_datasets()
		);
	}

	/**
	 * Повертає repository підмодуля подій.
	 */
	private function get_repository(): Calendar_Events_Repository {
		if ( null === $this->repository ) {
			$this->repository = new Calendar_Events_Repository();
		}

		return $this->repository;
	}

	/**
	 * Повертає repository підмодуля заявок.
	 */
	private function get_applications_repository(): Calendar_Applications_Repository {
		if ( null === $this->applications_repository ) {
			$this->applications_repository = new Calendar_Applications_Repository();
		}

		return $this->applications_repository;
	}

	/**
	 * Повертає repository підмодуля маршрутів.
	 */
	private function get_routes_repository(): Calendar_Routes_Repository {
		if ( null === $this->routes_repository ) {
			$this->routes_repository = new Calendar_Routes_Repository();
		}

		return $this->routes_repository;
	}

	/**
	 * Повертає repository підмодуля результатів.
	 */
	private function get_results_repository(): Calendar_Results_Repository {
		if ( null === $this->results_repository ) {
			$this->results_repository = new Calendar_Results_Repository();
		}

		return $this->results_repository;
	}
}

