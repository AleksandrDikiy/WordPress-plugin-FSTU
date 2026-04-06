<?php
/**
 * Plugin Name:  FSTU Portal
 * Plugin URI:   https://www.fstu.com.ua/
 * Description:  Офіційний плагін Федерації спортивного туризму України. Enterprise ERP/CRM система управління реєстрами, структурою та фінансами федерації.
 * Version:      1.2.1
 * Author:       Oleksandr Dykyi
 * Author URI:   https://www.fstu.com.ua/
 * Text Domain:  fstu
 * Domain Path:  /languages
 * Requires PHP: 8.0
 * Requires at least: 6.0
 *
 * Date_update: 2026-04-05
 *
 * @package FSTU
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── Константи плагіна ────────────────────────────────────────────────────────

define( 'FSTU_VERSION',      '1.2.1' );
define( 'FSTU_DB_VERSION',   '1.0.0' );
define( 'FSTU_PLUGIN_FILE',  __FILE__ );
define( 'FSTU_PLUGIN_DIR',   plugin_dir_path( __FILE__ ) );
define( 'FSTU_PLUGIN_URL',   plugin_dir_url( __FILE__ ) );
define( 'FSTU_PLUGIN_BASE',  plugin_basename( __FILE__ ) );

// Тимчасовий тестовий ключ Cloudflare (завжди успішний)
// TODO: замінити на реальні ключі у production через wp-config.php
define( 'FSTU_TURNSTILE_SITE_KEY',   '1x00000000000000000000AA' );
define( 'FSTU_TURNSTILE_SECRET_KEY', '1x0000000000000000000000000000000AA' );

// ─── Автозавантажувач ─────────────────────────────────────────────────────────

spl_autoload_register( function ( string $class ): void {
	if ( ! str_starts_with( $class, 'FSTU\\' ) ) {
		return;
	}
	$relative = str_replace( [ 'FSTU\\', '\\' ], [ '', '/' ], $class );
	$file      = FSTU_PLUGIN_DIR . 'includes/' . $relative . '.php';
	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

// ─── Підключення класів (fallback якщо автозавантажувач не спрацює) ──────────

// Core
require_once FSTU_PLUGIN_DIR . 'includes/Core/class-activator.php';

// Registry — Реєстр членів ФСТУ
require_once FSTU_PLUGIN_DIR . 'includes/Registry/class-registry-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Registry/class-registry-ajax.php';
require_once FSTU_PLUGIN_DIR . 'includes/Registry/class-registry-modals-ajax.php';

// Clubs — Довідник клубів (2026-04-05)
require_once FSTU_PLUGIN_DIR . 'includes/Clubs/class-clubs-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Clubs/class-clubs-ajax.php';

// Admin
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Admin/class-admin-menu.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Admin/class-admin-menu.php';
}

// PaymentDocs
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/PaymentDocs/class-payment-docs-list.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/PaymentDocs/class-payment-docs-list.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/PaymentDocs/class-payment-docs-ajax.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/PaymentDocs/class-payment-docs-ajax.php';
}

// ─── Активація / Деактивація ──────────────────────────────────────────────────

register_activation_hook( FSTU_PLUGIN_FILE, [ 'FSTU\\Core\\Activator', 'activate' ] );
register_deactivation_hook( FSTU_PLUGIN_FILE, [ 'FSTU\\Core\\Activator', 'deactivate' ] );

// ─── Ініціалізація ────────────────────────────────────────────────────────────

add_action( 'plugins_loaded', 'fstu_init' );

function fstu_init(): void {

	// ── Реєстр членів ФСТУ ────────────────────────────────────────────────────
	( new FSTU\Registry\Registry_List() )->init();
	( new FSTU\Registry\Registry_Ajax() )->init();
	( new FSTU\Registry\Registry_Modals_Ajax() )->init();

	// ── Довідник клубів ФСТУ ─────────────────────────────────────────────────
	( new FSTU\Clubs\Clubs_List() )->init();
	( new FSTU\Clubs\Clubs_Ajax() )->init();

	// ── Адмінка ───────────────────────────────────────────────────────────────
	if ( class_exists( 'FSTU\\Admin\\Admin_Menu' ) ) {
		( new FSTU\Admin\Admin_Menu() )->init();
	}

	// ── Реєстр платіжних документів ──────────────────────────────────────────
	if ( class_exists( 'FSTU\\PaymentDocs\\Payment_Docs_List' ) ) {
		( new FSTU\PaymentDocs\Payment_Docs_List() )->init();
	}
	if ( class_exists( 'FSTU\\PaymentDocs\\Payment_Docs_Ajax' ) ) {
		( new FSTU\PaymentDocs\Payment_Docs_Ajax() )->init();
	}
}
