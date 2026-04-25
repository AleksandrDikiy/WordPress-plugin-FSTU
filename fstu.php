<?php
/**
 * Plugin Name:  FSTU Portal
 * Plugin URI:   https://www.fstu.com.ua/
 * Description:  Офіційний плагін Федерації спортивного туризму України. Enterprise ERP/CRM система управління реєстрами, структурою та фінансами федерації.
 * Version:      1.23.1
 * Author:       Oleksandr Dykyi
 * Author URI:   https://www.fstu.com.ua/
 * Text Domain:  fstu
 * Domain Path:  /languages
 * Requires PHP: 8.0
 * Requires at least: 6.0
 *
 * Date_update: 2026-04-24
 *
 * @package FSTU
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── Константи плагіна ────────────────────────────────────────────────────────

define( 'FSTU_VERSION',      '1.23.3' );
define( 'FSTU_DB_VERSION',   '1.3.1' );
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
require_once FSTU_PLUGIN_DIR . 'includes/Core/class-database-migrations.php';
require_once FSTU_PLUGIN_DIR . 'includes/Core/class-activator.php';

// Migrations
require_once FSTU_PLUGIN_DIR . 'includes/Migrations/class-migration-1-3-0.php';

// UserFstu — Реєстр членів ФСТУ
require_once FSTU_PLUGIN_DIR . 'includes/UserFstu/class-user-fstu-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/UserFstu/class-user-fstu-ajax.php';
require_once FSTU_PLUGIN_DIR . 'includes/UserFstu/class-user-fstu-modals-ajax.php';

// Clubs — Довідник клубів (2026-04-13)
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/Clubs/class-clubs-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/Clubs/class-clubs-ajax.php';

// Units — Довідник осередків ФСТУ (2026-04-06)
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/Units/class-units-repository.php';
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/Units/class-units-service.php';
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

// ParticipationType — Довідник видів участі в заходах ФСТУ (2026-04-12)
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/ParticipationType/class-participationtype-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/ParticipationType/class-participationtype-ajax.php';

// TourType — Довідник видів походів ФСТУ (2026-04-12)
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/TourType/class-tourtype-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/TourType/class-tourtype-ajax.php';

// HikingCategory — Довідник категорій походів ФСТУ (2026-04-13)
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/HikingCategory/class-hikingcategory-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/HikingCategory/class-hikingcategory-ajax.php';

// HourCategories — Довідник видів складності походів ФСТУ (2026-04-13)
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/HourCategories/class-hourcategories-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/HourCategories/class-hourcategories-ajax.php';

// SportsCategories — Довідник спортивних розрядів ФСТУ (2026-04-13)
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/SportsCategories/class-sportscategories-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/SportsCategories/class-sportscategories-ajax.php';

// RefereeCategory — Довідник суддівських категорій ФСТУ (2026-04-13)
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/RefereeCategory/class-referee-category-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/RefereeCategory/class-referee-category-ajax.php';

// EventType — Довідник типів заходів ФСТУ (2026-04-07)
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/EventType/class-eventtype-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/EventType/class-eventtype-ajax.php';

// TourismType — Довідник типів туризму ФСТУ (2026-04-07)
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/TourismType/class-tourismtype-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/TourismType/class-tourismtype-ajax.php';

// StatusCard — Довідник статусів карток та квитків ФСТУ (2026-04-15)
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/StatusCard/class-status-card-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/StatusCard/class-status-card-ajax.php';

// ── Довідник типів членських білетів (Type Card) ───────────────────────────
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/TypeCard/class-type-card-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/TypeCard/class-type-card-ajax.php';

// ── Довідник типів вітрильних залікових груп ───────────────────────────────
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/SailGroupType/class-sail-group-type-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/SailGroupType/class-sail-group-type-ajax.php';
// ── Довідник типів суден ФСТУ ─────────────────────────────────────────────
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/TypeBoat/class-type-boat-list.php';
require_once FSTU_PLUGIN_DIR . 'includes/Dictionaries/TypeBoat/class-type-boat-ajax.php';

// ── Комісії з видів туризму (Board) ───────────────────────────────────────
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Commissions/class-commissions-repository.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Commissions/class-commissions-repository.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Commissions/class-commissions-protocol-service.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Commissions/class-commissions-protocol-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Commissions/class-commissions-mailer.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Commissions/class-commissions-mailer.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Commissions/class-commissions-service.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Commissions/class-commissions-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Commissions/class-commissions-list.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Commissions/class-commissions-list.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Commissions/class-commissions-ajax.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Commissions/class-commissions-ajax.php';
}

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

// Calendar — Календарний план змагань ФСТУ (2026-04-13)
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarEvents/class-calendar-events-repository.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarEvents/class-calendar-events-repository.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarEvents/class-calendar-events-protocol-service.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarEvents/class-calendar-events-protocol-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarEvents/class-calendar-events-service.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarEvents/class-calendar-events-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/class-calendar-list.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/class-calendar-list.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarEvents/class-calendar-events-ajax.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarEvents/class-calendar-events-ajax.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarApplications/class-calendar-applications-repository.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarApplications/class-calendar-applications-repository.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarApplications/class-calendar-applications-mailer.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarApplications/class-calendar-applications-mailer.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarApplications/class-calendar-applications-protocol-service.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarApplications/class-calendar-applications-protocol-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarApplications/class-calendar-applications-service.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarApplications/class-calendar-applications-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarApplications/class-calendar-applications-ajax.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarApplications/class-calendar-applications-ajax.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarRoutes/class-calendar-routes-repository.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarRoutes/class-calendar-routes-repository.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarRoutes/class-calendar-routes-protocol-service.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarRoutes/class-calendar-routes-protocol-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarRoutes/class-calendar-routes-mkk-service.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarRoutes/class-calendar-routes-mkk-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarRoutes/class-calendar-routes-notification-service.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarRoutes/class-calendar-routes-notification-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarRoutes/class-calendar-routes-service.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarRoutes/class-calendar-routes-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarRoutes/class-calendar-routes-ajax.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarRoutes/class-calendar-routes-ajax.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarResults/class-calendar-results-repository.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarResults/class-calendar-results-repository.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarResults/class-calendar-results-protocol-service.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarResults/class-calendar-results-protocol-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarResults/class-calendar-results-sailing-rules-service.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarResults/class-calendar-results-sailing-rules-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarResults/class-calendar-results-service.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarResults/class-calendar-results-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarResults/class-calendar-results-ajax.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/Calendar/CalendarResults/class-calendar-results-ajax.php';
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

// Recorders — Реєстратори ФСТУ (2026-04-11)
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Recorders/class-recorders-repository.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Recorders/class-recorders-repository.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Recorders/class-recorders-protocol-service.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Recorders/class-recorders-protocol-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Recorders/class-recorders-service.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Recorders/class-recorders-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Recorders/class-recorders-list.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Recorders/class-recorders-list.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Recorders/class-recorders-ajax.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Recorders/class-recorders-ajax.php';
}

// MKK — Реєстр членів МКК ФСТУ (2026-04-12)
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/MKK/class-mkk-repository.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/MKK/class-mkk-repository.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/MKK/class-mkk-protocol-service.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/MKK/class-mkk-protocol-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/MKK/class-mkk-service.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/MKK/class-mkk-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/MKK/class-mkk-list.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/MKK/class-mkk-list.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/MKK/class-mkk-ajax.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/MKK/class-mkk-ajax.php';
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
// MemberCardApplications — Посвідчення членів ФСТУ (2026-04-10)
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/MemberCardApplications/class-member-card-applications-repository.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/MemberCardApplications/class-member-card-applications-repository.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/MemberCardApplications/class-member-card-applications-protocol-service.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/MemberCardApplications/class-member-card-applications-protocol-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/MemberCardApplications/class-member-card-applications-upload-service.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/MemberCardApplications/class-member-card-applications-upload-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/MemberCardApplications/class-member-card-applications-service.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/MemberCardApplications/class-member-card-applications-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/MemberCardApplications/class-member-card-applications-list.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/MemberCardApplications/class-member-card-applications-list.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/MemberCardApplications/class-member-card-applications-ajax.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/MemberCardApplications/class-member-card-applications-ajax.php';
}
// Merilkas — Реєстр мерилок ФСТУ (2026-04-09)
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Merilkas/class-merilkas-repository.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Merilkas/class-merilkas-repository.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Merilkas/class-merilkas-list.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Merilkas/class-merilkas-list.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Merilkas/class-merilkas-ajax.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Merilkas/class-merilkas-ajax.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Merilkas/class-merilkas-service.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Merilkas/class-merilkas-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Merilkas/class-merilkas-protocol-service.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Merilkas/class-merilkas-protocol-service.php';
}
// Guidance — Склад керівних органів ФСТУ (2026-04-12)
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Guidance/class-guidance-repository.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Guidance/class-guidance-repository.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Guidance/class-guidance-protocol-service.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Guidance/class-guidance-protocol-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Guidance/class-guidance-service.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Guidance/class-guidance-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Guidance/class-guidance-list.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Guidance/class-guidance-list.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Guidance/class-guidance-ajax.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Registry/Guidance/class-guidance-ajax.php';
}
// Directory — Довідник Виконкому
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Directory/class-directory-repository.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Directory/class-directory-repository.php';
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Directory/class-directory-service.php';
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Directory/class-directory-list.php';
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Directory/class-directory-ajax.php';
}
// Presidium — Президія ФСТУ
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Presidium/class-presidium-repository.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Presidium/class-presidium-repository.php';
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Presidium/class-presidium-service.php';
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Presidium/class-presidium-list.php';
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Presidium/class-presidium-ajax.php';
}
// RegionalFST — Осередки ФСТУ
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/RegionalFST/class-regional-fst-repository.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/RegionalFST/class-regional-fst-repository.php';
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/RegionalFST/class-regional-fst-protocol-service.php';
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/RegionalFST/class-regional-fst-service.php';
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/RegionalFST/class-regional-fst-list.php';
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/RegionalFST/class-regional-fst-ajax.php';
}
// Elections — Електронні вибори STV (2026-04-22)
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Elections/class-elections-stv-engine.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Elections/class-elections-stv-engine.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Elections/class-elections-cron.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Elections/class-elections-cron.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Elections/class-elections-repository.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Elections/class-elections-repository.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Elections/class-elections-service.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Elections/class-elections-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Elections/class-elections-list.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Elections/class-elections-list.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Elections/class-elections-ajax.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Elections/class-elections-ajax.php';
}
// PersonalCabinet — Особистий кабінет ФСТУ (2026-04-09)
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/PersonalCabinet/class-personal-cabinet-repository.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/PersonalCabinet/class-personal-cabinet-repository.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/PersonalCabinet/class-personal-cabinet-protocol-service.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/PersonalCabinet/class-personal-cabinet-protocol-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/PersonalCabinet/class-personal-cabinet-payments-service.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/PersonalCabinet/class-personal-cabinet-payments-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/PersonalCabinet/class-personal-cabinet-service.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/PersonalCabinet/class-personal-cabinet-service.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/PersonalCabinet/class-personal-cabinet-list.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/PersonalCabinet/class-personal-cabinet-list.php';
}
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/PersonalCabinet/class-personal-cabinet-ajax.php' ) ) {
	require_once FSTU_PLUGIN_DIR . 'includes/Modules/PersonalCabinet/class-personal-cabinet-ajax.php';
}
// ── Рада ветеранів ФСТУ ───────────────────────────────────────────────────
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Veterans/class-veterans-repository.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Veterans/class-veterans-repository.php';
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Veterans/class-veterans-list.php';
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Veterans/class-veterans-ajax.php';
}
// ── Ревізійна комісія ФСТУ ──────────────────────────────────────────────────
if ( file_exists( FSTU_PLUGIN_DIR . 'includes/Modules/Audit/class-audit-repository.php' ) ) {
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Audit/class-audit-repository.php';
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Audit/class-audit-list.php';
    require_once FSTU_PLUGIN_DIR . 'includes/Modules/Audit/class-audit-ajax.php';
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
	FSTU\Core\Activator::maybe_upgrade();
	FSTU\Core\Capabilities::bootstrap();

	// ── Реєстр членів ФСТУ ────────────────────────────────────────────────────
    (new \FSTU\UserFstu\User_Fstu_List())->init();
    (new \FSTU\UserFstu\User_Fstu_Ajax())->init();
    (new \FSTU\UserFstu\User_Fstu_Modals_Ajax())->init();

	// ── Довідник клубів ФСТУ ─────────────────────────────────────────────────
	( new FSTU\Dictionaries\Clubs\Clubs_List() )->init();
	( new FSTU\Dictionaries\Clubs\Clubs_Ajax() )->init();

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

	// ── Довідник видів участі в заходах ФСТУ ──────────────────────────────────
	( new FSTU\Dictionaries\ParticipationType\ParticipationType_List() )->init();
	( new FSTU\Dictionaries\ParticipationType\ParticipationType_Ajax() )->init();

	// ── Довідник видів походів ФСТУ ───────────────────────────────────────────
	( new FSTU\Dictionaries\TourType\TourType_List() )->init();
	( new FSTU\Dictionaries\TourType\TourType_Ajax() )->init();

	// ── Довідник категорій походів ФСТУ ───────────────────────────────────────
	( new FSTU\Dictionaries\HikingCategory\HikingCategory_List() )->init();
	( new FSTU\Dictionaries\HikingCategory\HikingCategory_Ajax() )->init();

	// ── Довідник видів складності походів ФСТУ ────────────────────────────────
	( new FSTU\Dictionaries\HourCategories\HourCategories_List() )->init();
	( new FSTU\Dictionaries\HourCategories\HourCategories_Ajax() )->init();

	// ── Довідник спортивних розрядів ФСТУ ─────────────────────────────────────
	( new FSTU\Dictionaries\SportsCategories\SportsCategories_List() )->init();
	( new FSTU\Dictionaries\SportsCategories\SportsCategories_Ajax() )->init();

	// ── Довідник суддівських категорій ФСТУ ───────────────────────────────────
	( new FSTU\Dictionaries\RefereeCategory\RefereeCategory_List() )->init();
	( new FSTU\Dictionaries\RefereeCategory\RefereeCategory_Ajax() )->init();

	// ── Довідник типів заходів ФСТУ ───────────────────────────────────────────
	( new FSTU\Dictionaries\EventType\EventType_List() )->init();
	( new FSTU\Dictionaries\EventType\EventType_Ajax() )->init();

	// ── Довідник типів туризму ФСТУ ───────────────────────────────────────────
	( new FSTU\Dictionaries\TourismType\TourismType_List() )->init();
	( new FSTU\Dictionaries\TourismType\TourismType_Ajax() )->init();

    // ── Довідник статусів карток ───────────────────────────────────────────
    //( new FSTU\Dictionaries\StatusCard\Class_Status_Card_List() )->init();
    //( new FSTU\Dictionaries\StatusCard\Class_Status_Card_Ajax() )->init();
    new \FSTU\Dictionaries\StatusCard\Class_Status_Card_List();
    new \FSTU\Dictionaries\StatusCard\Class_Status_Card_Ajax();

    // ── Довідник типів членських білетів (Type Card) ───────────────────────────
    new \FSTU\Dictionaries\TypeCard\Class_Type_Card_List();
    new \FSTU\Dictionaries\TypeCard\Class_Type_Card_Ajax();
    // ── Довідник типів вітрильних залікових груп ───────────────────────────────
    new \FSTU\Dictionaries\SailGroupType\Class_Sail_Group_Type_List();
    new \FSTU\Dictionaries\SailGroupType\Class_Sail_Group_Type_Ajax();
    // ── Довідник типів суден ФСТУ ─────────────────────────────────────────────
    ( new FSTU\Dictionaries\TypeBoat\Type_Boat_List() )->init();
    ( new FSTU\Dictionaries\TypeBoat\Type_Boat_Ajax() )->init();

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

    // ── Комісії з видів туризму (Board / Опитування) ────────────────────────
    if ( class_exists( 'FSTU\\Modules\\Commissions\\Commissions_List' ) ) {
        ( new FSTU\Modules\Commissions\Commissions_List() )->init();
    }
    if ( class_exists( 'FSTU\\Modules\\Commissions\\Commissions_Ajax' ) ) {
        ( new FSTU\Modules\Commissions\Commissions_Ajax() )->init();
    }

    // ── Заявки в ФСТУ ───────────────────────────────────────────────────────
	if ( class_exists( 'FSTU\\Modules\\Applications\\Applications_List' ) ) {
		( new FSTU\Modules\Applications\Applications_List() )->init();
	}
	if ( class_exists( 'FSTU\\Modules\\Applications\\Applications_Ajax' ) ) {
		( new FSTU\Modules\Applications\Applications_Ajax() )->init();
	}

	// ── Календарний план змагань ФСТУ ─────────────────────────────────────────
	if ( class_exists( 'FSTU\\Modules\\Calendar\\Calendar_List' ) ) {
		( new FSTU\Modules\Calendar\Calendar_List() )->init();
	}
	if ( class_exists( 'FSTU\\Modules\\Calendar\\CalendarEvents\\Calendar_Events_Ajax' ) ) {
		( new FSTU\Modules\Calendar\CalendarEvents\Calendar_Events_Ajax() )->init();
	}
	if ( class_exists( 'FSTU\\Modules\\Calendar\\CalendarApplications\\Calendar_Applications_Ajax' ) ) {
		( new FSTU\Modules\Calendar\CalendarApplications\Calendar_Applications_Ajax() )->init();
	}
	if ( class_exists( 'FSTU\\Modules\\Calendar\\CalendarRoutes\\Calendar_Routes_Ajax' ) ) {
		( new FSTU\Modules\Calendar\CalendarRoutes\Calendar_Routes_Ajax() )->init();
	}
	if ( class_exists( 'FSTU\\Modules\\Calendar\\CalendarResults\\Calendar_Results_Ajax' ) ) {
		( new FSTU\Modules\Calendar\CalendarResults\Calendar_Results_Ajax() )->init();
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

	// ── Реєстратори ФСТУ ─────────────────────────────────────────────────────
	if ( class_exists( 'FSTU\Modules\Registry\Recorders\Recorders_List' ) ) {
		( new FSTU\Modules\Registry\Recorders\Recorders_List() )->init();
	}
	if ( class_exists( 'FSTU\Modules\Registry\Recorders\Recorders_Ajax' ) ) {
		( new FSTU\Modules\Registry\Recorders\Recorders_Ajax() )->init();
	}

	// ── Реєстр членів МКК ФСТУ ───────────────────────────────────────────────
	if ( class_exists( 'FSTU\Modules\Registry\MKK\MKK_List' ) ) {
		( new FSTU\Modules\Registry\MKK\MKK_List() )->init();
	}
	if ( class_exists( 'FSTU\Modules\Registry\MKK\MKK_Ajax' ) ) {
		( new FSTU\Modules\Registry\MKK\MKK_Ajax() )->init();
	}

	// ── Склад керівних органів ФСТУ ──────────────────────────────────────────
	if ( class_exists( 'FSTU\Modules\Registry\Guidance\Guidance_List' ) ) {
		( new FSTU\Modules\Registry\Guidance\Guidance_List() )->init();
	}
	if ( class_exists( 'FSTU\Modules\Registry\Guidance\Guidance_Ajax' ) ) {
		( new FSTU\Modules\Registry\Guidance\Guidance_Ajax() )->init();
	}
    // ── Directory — Виконком та Опитування ────────────────────────────────────
    if ( class_exists( 'FSTU\Modules\Directory\Directory_List' ) ) {
        ( new FSTU\Modules\Directory\Directory_List() )->init();
        ( new FSTU\Modules\Directory\Directory_Ajax() )->init();
    }
    if ( class_exists( 'FSTU\Modules\Presidium\Presidium_List' ) ) {
        ( new FSTU\Modules\Presidium\Presidium_List() )->init();
        ( new FSTU\Modules\Presidium\Presidium_Ajax() )->init();
    }
    // ── Осередки ФСТУ ─────────────────────────────────────────────────────────
    if ( class_exists( 'FSTU\Modules\RegionalFST\Class_Regional_FST_List' ) ) {
        ( new FSTU\Modules\RegionalFST\Class_Regional_FST_List() )->init();
        ( new FSTU\Modules\RegionalFST\Class_Regional_FST_Ajax() )->init();
    }
	// ── Реєстр стернових ФСТУ ─────────────────────────────────────────────────
	if ( class_exists( 'FSTU\Modules\Registry\Steering\Steering_List' ) ) {
		( new FSTU\Modules\Registry\Steering\Steering_List() )->init();
	}
	if ( class_exists( 'FSTU\Modules\Registry\Steering\Steering_Ajax' ) ) {
		( new FSTU\Modules\Registry\Steering\Steering_Ajax() )->init();
	}

	// ── Посвідчення членів ФСТУ ──────────────────────────────────────────────
	if ( class_exists( 'FSTU\Modules\Registry\MemberCardApplications\Member_Card_Applications_List' ) ) {
		( new FSTU\Modules\Registry\MemberCardApplications\Member_Card_Applications_List() )->init();
	}
	if ( class_exists( 'FSTU\Modules\Registry\MemberCardApplications\Member_Card_Applications_Ajax' ) ) {
		( new FSTU\Modules\Registry\MemberCardApplications\Member_Card_Applications_Ajax() )->init();
	}
    // ── Електронні вибори STV ─────────────────────────────────────────────────
    if ( class_exists( 'FSTU\Modules\Elections\Elections_List' ) ) {
        ( new \FSTU\Modules\Elections\Elections_List() )->init();
    }
    if ( class_exists( 'FSTU\Modules\Elections\Elections_Ajax' ) ) {
        ( new \FSTU\Modules\Elections\Elections_Ajax() )->init();
    }
    if ( class_exists( 'FSTU\Modules\Elections\Elections_Cron' ) ) {
        ( new \FSTU\Modules\Elections\Elections_Cron() )->init();
    }
    // ── Особистий кабінет ФСТУ ───────────────────────────────────────────────
    if ( class_exists( 'FSTU\Modules\PersonalCabinet\Personal_Cabinet_List' ) ) {
		( new FSTU\Modules\PersonalCabinet\Personal_Cabinet_List() )->init();
	}
	if ( class_exists( 'FSTU\Modules\PersonalCabinet\Personal_Cabinet_Ajax' ) ) {
		( new FSTU\Modules\PersonalCabinet\Personal_Cabinet_Ajax() )->init();
	}
    // ── Реєстр мерилок ФСТУ ────────────────────────────────────────────────────
    if ( class_exists( 'FSTU\Modules\Registry\Merilkas\Merilkas_List' ) ) {
        ( new FSTU\Modules\Registry\Merilkas\Merilkas_List() )->init();
    }
    if ( class_exists( 'FSTU\Modules\Registry\Merilkas\Merilkas_Ajax' ) ) {
        ( new FSTU\Modules\Registry\Merilkas\Merilkas_Ajax() )->init();
    }
    // ── Рада ветеранів ФСТУ ───────────────────────────────────────────────────
    if ( class_exists( 'FSTU\Modules\Veterans\Veterans_List' ) ) {
        ( new FSTU\Modules\Veterans\Veterans_List() )->init();
        ( new FSTU\Modules\Veterans\Veterans_Ajax() )->init();
    }
    //
    if ( class_exists( 'FSTU\Modules\Audit\Audit_List' ) ) {
        ( new FSTU\Modules\Audit\Audit_List() )->init();
        ( new FSTU\Modules\Audit\Audit_Ajax() )->init();
    }
}