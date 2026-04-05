<?php
/**
 * Plugin Name:  FSTU Portal
 * Plugin URI:   https://www.fstu.com.ua/
 * Description:  Офіційний плагін Федерації спортивного туризму України. Enterprise ERP/CRM система управління реєстрами, структурою та фінансами федерації.
 * Version:      1.0.0
 * Author:       Oleksandr Dykyi
 * Author URI:   https://www.fstu.com.ua/
 * Text Domain:  fstu
 * Domain Path:  /languages
 * Requires PHP: 8.0
 * Requires at least: 6.0
 *
 * Date_update: 2026-04-03
 *
 * @package FSTU
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── Константи плагіна ────────────────────────────────────────────────────────

define( 'FSTU_VERSION',      '1.0.0' );
define( 'FSTU_DB_VERSION',   '1.0.0' );
define( 'FSTU_PLUGIN_FILE',  __FILE__ );
define( 'FSTU_PLUGIN_DIR',   plugin_dir_path( __FILE__ ) );
define( 'FSTU_PLUGIN_URL',   plugin_dir_url( __FILE__ ) );
define( 'FSTU_PLUGIN_BASE',  plugin_basename( __FILE__ ) );
// Тимчасовий тестовий ключ Cloudflare (завжди успішний)
define( 'FSTU_TURNSTILE_SITE_KEY', '1x00000000000000000000AA' );
define( 'FSTU_TURNSTILE_SECRET_KEY', '1x0000000000000000000000000000000AA' );

// ─── Автозавантаження модулів ─────────────────────────────────────────────────

/**
 * Простий автозавантажувач для класів плагіна.
 * Завантажує файли з includes/ за неймспейсом FSTU\.
 */
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

// ─── Підключення обов'язкових класів (fallback, якщо автозавантажувач не спрацює) ──

require_once FSTU_PLUGIN_DIR . 'includes/Core/class-activator.php';
require_once FSTU_PLUGIN_DIR . 'includes/Registry/class-registry-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Registry/class-registry-ajax.php';
require_once FSTU_PLUGIN_DIR . 'includes/Registry/class-registry-modals-ajax.php'; // 2026-04-04

// Десь поруч з ініціалізацією Registry_List
require_once FSTU_PLUGIN_DIR . 'includes/PaymentDocs/class-payment-docs-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/PaymentDocs/class-payment-docs-ajax.php'; // ДОДАНО

$fstu_payment_docs = new \FSTU\PaymentDocs\Payment_Docs_List();
$fstu_payment_docs->init();

$fstu_payment_docs_ajax = new \FSTU\PaymentDocs\Payment_Docs_Ajax(); // ДОДАНО
$fstu_payment_docs_ajax->init(); // ДОДАНО

// ─── Активація / Деактивація ──────────────────────────────────────────────────

register_activation_hook( FSTU_PLUGIN_FILE, [ 'FSTU\\Core\\Activator', 'activate' ] );
register_deactivation_hook( FSTU_PLUGIN_FILE, [ 'FSTU\\Core\\Activator', 'deactivate' ] );

// ─── Ініціалізація ────────────────────────────────────────────────────────────

add_action( 'plugins_loaded', 'fstu_init' );

function fstu_init(): void {
	// Реєстр членів ФСТУ
	$registry_list = new FSTU\Registry\Registry_List();
	$registry_list->init();

	$registry_ajax = new FSTU\Registry\Registry_Ajax();
	$registry_ajax->init();
    // Додаємо ініціалізацію AJAX для модальних вікон
    $registry_modals_ajax = new FSTU\Registry\Registry_Modals_Ajax();
    $registry_modals_ajax->init();
}
