<?php
/**
 * Клас централізованого керування capability-моделлю ФСТУ.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Core
 */

namespace FSTU\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Capabilities {

	public const ACCESS_ADMIN             = 'fstu_access_admin';
	public const MANAGE_COMMISSION        = 'fstu_manage_commission';
	public const DELETE_COMMISSION        = 'fstu_delete_commission';
	public const VIEW_COMMISSION_PROTOCOL = 'fstu_view_commission_protocol';

	/**
	 * Ініціалізує capability-модель для поточного запиту.
	 */
	public static function bootstrap(): void {
		static $bootstrapped = false;

		if ( $bootstrapped ) {
			return;
		}

		$bootstrapped = true;
		self::register_role_capabilities();
	}

	/**
	 * Призначає capability потрібним ролям.
	 */
	public static function register_role_capabilities(): void {
		$role_caps = [
			'administrator' => [
				self::ACCESS_ADMIN             => true,
				self::MANAGE_COMMISSION        => true,
				self::DELETE_COMMISSION        => true,
				self::VIEW_COMMISSION_PROTOCOL => true,
			],
			'userregistrar' => [
				self::ACCESS_ADMIN             => true,
				self::MANAGE_COMMISSION        => true,
				self::VIEW_COMMISSION_PROTOCOL => true,
			],
		];

		foreach ( $role_caps as $role_name => $caps ) {
			$role = get_role( $role_name );

			if ( ! $role ) {
				continue;
			}

			foreach ( $caps as $capability => $grant ) {
				if ( $grant && ! $role->has_cap( $capability ) ) {
					$role->add_cap( $capability, true );
				}
			}
		}
	}

	/**
	 * Повертає прапорці прав для модуля комісій.
	 *
	 * @return array<string,bool>
	 */
	public static function get_commission_permissions(): array {
		return [
			'canView'     => true,
			'canManage'   => self::current_user_can_manage_commission(),
			'canDelete'   => self::current_user_can_delete_commission(),
			'canProtocol' => self::current_user_can_view_commission_protocol(),
		];
	}

	/**
	 * Чи має користувач доступ до адмін-розділів ФСТУ.
	 */
	public static function current_user_can_access_admin(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::ACCESS_ADMIN );
	}

	/**
	 * Чи може користувач керувати довідником комісій.
	 */
	public static function current_user_can_manage_commission(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_COMMISSION );
	}

	/**
	 * Чи може користувач видаляти записи довідника комісій.
	 */
	public static function current_user_can_delete_commission(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::DELETE_COMMISSION );
	}

	/**
	 * Чи може користувач переглядати протокол модуля комісій.
	 */
	public static function current_user_can_view_commission_protocol(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_COMMISSION_PROTOCOL );
	}
}
