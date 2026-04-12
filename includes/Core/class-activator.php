<?php
/**
 * Клас активації та деактивації плагіна FSTU.
 *
 * Version:     1.1.0
 * Date_update: 2026-04-12
 *
 * @package FSTU\Core
 */

namespace FSTU\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Activator {

	private const TOURTYPE_ORDER_COLUMN = 'TourType_Order';
	private const TOURTYPE_ORDER_BACKFILL_OPTION = 'fstu_tourtype_order_backfill_done';

	/**
	 * Виконується при активації плагіна.
	 * Встановлює початкові опції, перевіряє версію БД.
	 */
	public static function activate(): void {
		Capabilities::register_role_capabilities();
		self::maybe_upgrade();
		flush_rewrite_rules();
	}

	/**
	 * Виконує відкладений upgrade для вже встановлених інсталяцій.
	 */
	public static function maybe_upgrade(): void {
		static $did_upgrade = false;

		if ( $did_upgrade ) {
			return;
		}

		$did_upgrade = true;

		$installed_version = get_option( 'fstu_db_version', '0' );

		if ( version_compare( $installed_version, FSTU_DB_VERSION, '<' ) ) {
			if ( self::run_migrations() ) {
				update_option( 'fstu_db_version', FSTU_DB_VERSION );
			}
		}
	}

	/**
	 * Виконується при деактивації плагіна.
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}

	/**
	 * Запускає міграції бази даних (за потреби).
	 * Кастомні таблиці ФСТУ вже існують — тут лише налаштовуємо власні опції.
	 */
	private static function run_migrations(): bool {
		if ( ! self::maybe_add_tourtype_order_column() ) {
			return false;
		}

		if ( ! self::maybe_backfill_tourtype_order_column() ) {
			return false;
		}

		update_option( 'fstu_version', FSTU_VERSION );

		return true;
	}

	/**
	 * Додає технічну колонку порядку для TourType, якщо її ще немає.
	 *
	 * Колонка потрібна для drag-and-drop reorder у модулі довідника.
	 * Міграція additive та idempotent — без зміни наявних полів legacy-схеми.
	 */
	private static function maybe_add_tourtype_order_column(): bool {
		global $wpdb;

		if ( ! self::table_exists( 'S_TourType' ) ) {
			return false;
		}

		if ( self::tourtype_order_column_exists() ) {
			return true;
		}

		$altered = $wpdb->query(
			'ALTER TABLE S_TourType ADD COLUMN ' . self::TOURTYPE_ORDER_COLUMN . ' INT UNSIGNED NOT NULL DEFAULT 0'
		);

		if ( false === $altered ) {
			return false;
		}

		delete_option( self::TOURTYPE_ORDER_BACKFILL_OPTION );

		return true;
	}

	/**
	 * Виконує backfill значень сортування для TourType, якщо він ще не завершений.
	 */
	private static function maybe_backfill_tourtype_order_column(): bool {
		if ( ! self::tourtype_order_column_exists() ) {
			return false;
		}

		if ( '1' === get_option( self::TOURTYPE_ORDER_BACKFILL_OPTION, '0' ) ) {
			return true;
		}

		if ( ! self::backfill_tourtype_order_column() ) {
			return false;
		}

		update_option( self::TOURTYPE_ORDER_BACKFILL_OPTION, '1' );

		return true;
	}

	/**
	 * Заповнює значення сортування для наявних записів TourType.
	 */
	private static function backfill_tourtype_order_column(): bool {
		global $wpdb;

		$rows = $wpdb->get_results(
			'SELECT TourType_ID, HourCategories_ID, TourType_Order FROM S_TourType ORDER BY HourCategories_ID ASC, TourType_Code ASC, TourType_ID ASC',
			ARRAY_A
		);

		if ( ! is_array( $rows ) || empty( $rows ) ) {
			return true;
		}

		$order_by_category = [];

		foreach ( $rows as $row ) {
			$tourtype_id        = absint( $row['TourType_ID'] ?? 0 );
			$hour_categories_id = absint( $row['HourCategories_ID'] ?? 0 );

			if ( $tourtype_id <= 0 ) {
				continue;
			}

			if ( ! isset( $order_by_category[ $hour_categories_id ] ) ) {
				$order_by_category[ $hour_categories_id ] = 0;
			}

			$order_by_category[ $hour_categories_id ]++;
			$target_order = $order_by_category[ $hour_categories_id ];
			$current_order = absint( $row['TourType_Order'] ?? 0 );

			if ( $current_order === $target_order ) {
				continue;
			}

			$updated = $wpdb->update(
				'S_TourType',
				[ self::TOURTYPE_ORDER_COLUMN => $target_order ],
				[ 'TourType_ID' => $tourtype_id ],
				[ '%d' ],
				[ '%d' ]
			);

			if ( false === $updated ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Перевіряє наявність таблиці в поточній схемі WordPress.
	 */
	private static function table_exists( string $table_name ): bool {
		global $wpdb;

		return null !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
	}

	/**
	 * Перевіряє наявність колонки TourType_Order.
	 */
	private static function tourtype_order_column_exists(): bool {
		global $wpdb;

		return null !== $wpdb->get_var(
			$wpdb->prepare(
				'SHOW COLUMNS FROM S_TourType LIKE %s',
				self::TOURTYPE_ORDER_COLUMN
			)
		);
	}
}
