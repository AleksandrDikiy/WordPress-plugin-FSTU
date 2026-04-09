<?php
/**
 * Plugin Name:  FSTU Portal
 * Plugin URI:   https://www.fstu.com.ua/
 * Description:  Офіційний плагін Федерації спортивного туризму України. Enterprise ERP/CRM система управління реєстрами, структурою та фінансами федерації.
 * Version:      1.11.0
 * Author:       Oleksandr Dykyi
 * Author URI:   https://www.fstu.com.ua/
 * Text Domain:  fstu
 * Domain Path:  /languages
 * Requires PHP: 8.0
 * Requires at least: 6.0
 *
 * Date_update: 2026-04-08
 *
 * @package FSTU
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── Константи плагіна ────────────────────────────────────────────────────────

define( 'FSTU_VERSION',      '1.11.0' );
define( 'FSTU_DB_VERSION',   '1.0.0' );
define( 'FSTU_PLUGIN_FILE',  __FILE__ );
define( 'FSTU_PLUGIN_DIR',   plugin_dir_path( __FILE__ ) );
define( 'FSTU_PLUGIN_URL',   plugin_dir_url( __FILE__ ) );
define( 'FSTU_PLUGIN_BASE',  plugin_basename( __FILE__ ) );

// Тимчасовий тестовий ключ Cloudflare (завжди успішний)
// TODO: замінити на реальні ключі у production через wp-config.php
//define( 'FSTU_TURNSTILE_SITE_KEY',   '1x00000000000000000000AA' );
//define( 'FSTU_TURNSTILE_SECRET_KEY', '1x0000000000000000000000000000000AA' );

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
require_once FSTU_PLUGIN_DIR . 'includes/Core/class-capabilities.php';
require_once FSTU_PLUGIN_DIR . 'includes/Core/class-activator.php';

// Registry — Реєстр членів ФСТУ
require_once FSTU_PLUGIN_DIR . 'includes/Registry/class-registry-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Registry/class-registry-ajax.php';
require_once FSTU_PLUGIN_DIR . 'includes/Registry/class-registry-modals-ajax.php';

// Clubs — Довідник клубів (2026-04-05)
require_once FSTU_PLUGIN_DIR . 'includes/Clubs/class-clubs-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Clubs/class-clubs-ajax.php';

// Units — Довідник осередків ФСТУ (2026-04-06)
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/Units/class-units-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/Units/class-units-ajax.php';

// TypeEvent — Довідник видів змагань ФСТУ (2026-04-06)
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/TypeEvent/class-typeevent-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/TypeEvent/class-typeevent-ajax.php';

// TypeGuidance — Довідник керівних органів ФСТУ (2026-04-06)
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/TypeGuidance/class-typeguidance-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/TypeGuidance/class-typeguidance-ajax.php';

// MemberRegional — Довідник посад федерацій ФСТУ (2026-04-06)
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/MemberRegional/class-member-regional-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/MemberRegional/class-member-regional-ajax.php';

// MemberGuidance — Довідник посад у керівних органах ФСТУ (2026-04-06)
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/MemberGuidance/class-member-guidance-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/MemberGuidance/class-member-guidance-ajax.php';

// Country — Довідник країн ФСТУ (2026-04-06)
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/Country/class-country-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/Country/class-country-ajax.php';

// Region — Довідник областей ФСТУ (2026-04-06)
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/Region/class-region-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/Region/class-region-ajax.php';

// City — Довідник міст ФСТУ (2026-04-07)
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/City/class-city-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/City/class-city-ajax.php';

// Commission — Довідник комісій та колегій ФСТУ (2026-04-06)
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/Commission/class-commission-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/Commission/class-commission-ajax.php';

// EventType — Довідник типів заходів ФСТУ (2026-04-07)
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/EventType/class-eventtype-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/EventType/class-eventtype-ajax.php';

// TourismType — Довідник типів туризму ФСТУ (2026-04-07)
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/TourismType/class-tourismtype-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/TourismType/class-tourismtype-ajax.php';

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

// Applications — Заявки в ФСТУ (2026-04-06)
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Applications/class-applications-repository.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Applications/class-applications-repository.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Applications/class-applications-mailer.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Applications/class-applications-mailer.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Applications/class-applications-protocol-service.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Applications/class-applications-protocol-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Applications/class-applications-service.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Applications/class-applications-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Applications/class-applications-list.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Applications/class-applications-list.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Applications/class-applications-ajax.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Applications/class-applications-ajax.php';
}

// Sailboats — Реєстр суден ФСТУ (2026-04-07)
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Sailboats/class-sailboats-repository.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Sailboats/class-sailboats-repository.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Sailboats/class-sailboats-protocol-service.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Sailboats/class-sailboats-protocol-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Sailboats/class-sailboats-notification-service.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Sailboats/class-sailboats-notification-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Sailboats/class-sailboats-service.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Sailboats/class-sailboats-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Sailboats/class-sailboats-list.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Sailboats/class-sailboats-list.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Sailboats/class-sailboats-ajax.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Sailboats/class-sailboats-ajax.php';
}

// Referees — Реєстр суддів ФСТУ (2026-04-08)
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Referees/class-referees-repository.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Referees/class-referees-repository.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Referees/class-referees-protocol-service.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Referees/class-referees-protocol-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Referees/class-referees-service.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Referees/class-referees-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Referees/class-referees-list.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Referees/class-referees-list.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Referees/class-referees-ajax.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Referees/class-referees-ajax.php';
}

// Steering — Реєстр стернових ФСТУ (2026-04-08)
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Steering/class-steering-repository.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Steering/class-steering-repository.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Steering/class-steering-protocol-service.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Steering/class-steering-protocol-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Steering/class-steering-notification-service.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Steering/class-steering-notification-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Steering/class-steering-upload-service.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Steering/class-steering-upload-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Steering/class-steering-service.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Steering/class-steering-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Steering/class-steering-list.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Steering/class-steering-list.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Steering/class-steering-ajax.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Steering/class-steering-ajax.php';
}
// ── Довідники (Хаб) ──────────────────────────────────────────────────────
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/class-dictionaries-hub.php';
( new FSTU\Modules\Dictionaries\Dictionaries_Hub() )->init();

// ─── Активація / Деактивація ──────────────────────────────────────────────────

register_activation_hook( FSTU_PLUGIN_FILE, [ 'FSTU\\Core\\Activator', 'activate' ] );
register_deactivation_hook( FSTU_PLUGIN_FILE, [ 'FSTU\\Core\\Activator', 'deactivate' ] );

// ─── Ініціалізація ────────────────────────────────────────────────────────────

add_action( 'plugins_loaded', 'fstu_init' );

function fstu_init(): void {
	FSTU\Core\Capabilities::bootstrap();

	// ── Реєстр членів ФСТУ ────────────────────────────────────────────────────
	( new FSTU\Registry\Registry_List() )->init();
	( new FSTU\Registry\Registry_Ajax() )->init();
	( new FSTU\Registry\Registry_Modals_Ajax() )->init();

	// ── Довідник клубів ФСТУ ─────────────────────────────────────────────────
	( new FSTU\Clubs\Clubs_List() )->init();
	( new FSTU\Clubs\Clubs_Ajax() )->init();

	// ── Довідник осередків ФСТУ ───────────────────────────────────────────────
	( new FSTU\Dictionaries\Units\Units_List() )->init();
	( new FSTU\Dictionaries\Units\Units_Ajax() )->init();

	// ── Довідник видів змагань ФСТУ ───────────────────────────────────────────
	( new FSTU\Dictionaries\TypeEvent\TypeEvent_List() )->init();
	( new FSTU\Dictionaries\TypeEvent\TypeEvent_Ajax() )->init();

	// ── Довідник керівних органів ФСТУ ────────────────────────────────────────
	( new FSTU\Dictionaries\TypeGuidance\TypeGuidance_List() )->init();
	( new FSTU\Dictionaries\TypeGuidance\TypeGuidance_Ajax() )->init();

	// ── Довідник посад федерацій ФСТУ ─────────────────────────────────────────
	( new FSTU\Dictionaries\MemberRegional\Member_Regional_List() )->init();
	( new FSTU\Dictionaries\MemberRegional\Member_Regional_Ajax() )->init();

	// ── Довідник посад у керівних органах ФСТУ ────────────────────────────────
	( new FSTU\Dictionaries\MemberGuidance\Member_Guidance_List() )->init();
	( new FSTU\Dictionaries\MemberGuidance\Member_Guidance_Ajax() )->init();

	// ── Довідник країн ФСТУ ────────────────────────────────────────────────────
	( new FSTU\Dictionaries\Country\Country_List() )->init();
	( new FSTU\Dictionaries\Country\Country_Ajax() )->init();

	// ── Довідник областей ФСТУ ─────────────────────────────────────────────────
	( new FSTU\Dictionaries\Region\Region_List() )->init();
	( new FSTU\Dictionaries\Region\Region_Ajax() )->init();

	// ── Довідник міст ФСТУ ────────────────────────────────────────────────────
	( new FSTU\Dictionaries\City\City_List() )->init();
	( new FSTU\Dictionaries\City\City_Ajax() )->init();

	// ── Довідник комісій та колегій ФСТУ ──────────────────────────────────────
	( new FSTU\Dictionaries\Commission\Commission_List() )->init();
	( new FSTU\Dictionaries\Commission\Commission_Ajax() )->init();

	// ── Довідник типів заходів ФСТУ ───────────────────────────────────────────
	( new FSTU\Dictionaries\EventType\EventType_List() )->init();
	( new FSTU\Dictionaries\EventType\EventType_Ajax() )->init();

	// ── Довідник типів туризму ФСТУ ───────────────────────────────────────────
	( new FSTU\Dictionaries\TourismType\TourismType_List() )->init();
	( new FSTU\Dictionaries\TourismType\TourismType_Ajax() )->init();
	
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

	// ── Заявки в ФСТУ ───────────────────────────────────────────────────────
	if ( class_exists( 'FSTU\\Modules\\Applications\\Applications_List' ) ) {
		( new FSTU\Modules\Applications\Applications_List() )->init();
	}
	if ( class_exists( 'FSTU\\Modules\\Applications\\Applications_Ajax' ) ) {
		( new FSTU\Modules\Applications\Applications_Ajax() )->init();
	}

	// ── Реєстр суден ФСТУ ────────────────────────────────────────────────────
	if ( class_exists( 'FSTU\\Modules\\Registry\\Sailboats\\Sailboats_List' ) ) {
		( new FSTU\Modules\Registry\Sailboats\Sailboats_List() )->init();
	}
	if ( class_exists( 'FSTU\\Modules\\Registry\\Sailboats\\Sailboats_Ajax' ) ) {
		( new FSTU\Modules\Registry\Sailboats\Sailboats_Ajax() )->init();
	}

	// ── Реєстр суддів ФСТУ ────────────────────────────────────────────────────
	if ( class_exists( 'FSTU\\Modules\\Registry\\Referees\\Referees_List' ) ) {
		( new FSTU\Modules\Registry\Referees\Referees_List() )->init();
	}
	if ( class_exists( 'FSTU\\Modules\\Registry\\Referees\\Referees_Ajax' ) ) {
		( new FSTU\Modules\Registry\Referees\Referees_Ajax() )->init();
	}

	// ── Реєстр стернових ФСТУ ─────────────────────────────────────────────────
	if ( class_exists( 'FSTU\\Modules\\Registry\\Steering\\Steering_List' ) ) {
		( new FSTU\Modules\Registry\Steering\Steering_List() )->init();
	}
	if ( class_exists( 'FSTU\\Modules\\Registry\\Steering\\Steering_Ajax' ) ) {
		( new FSTU\Modules\Registry\Steering\Steering_Ajax() )->init();
	}
}
