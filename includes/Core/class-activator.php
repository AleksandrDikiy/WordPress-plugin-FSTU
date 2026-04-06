<?php
/**
 * Клас активації та деактивації плагіна FSTU.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-03
 *
 * @package FSTU\Core
 */

namespace FSTU\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Activator {

	/**
	 * Виконується при активації плагіна.
	 * Встановлює початкові опції, перевіряє версію БД.
	 */
	public static function activate(): void {
		Capabilities::register_role_capabilities();

		$installed_version = get_option( 'fstu_db_version', '0' );

		if ( version_compare( $installed_version, FSTU_DB_VERSION, '<' ) ) {
			self::run_migrations();
			update_option( 'fstu_db_version', FSTU_DB_VERSION );
		}

		flush_rewrite_rules();
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
	private static function run_migrations(): void {
		// Майбутні міграції через dbDelta() розміщуватимуться тут.
		update_option( 'fstu_version', FSTU_VERSION );
	}
}
