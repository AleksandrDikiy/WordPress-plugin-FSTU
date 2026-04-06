<?php
/**
 * Клас централізованого керування capability-моделлю ФСТУ.
 *
 * Version:     1.5.0
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
	public const VIEW_TYPEGUIDANCE        = 'fstu_view_typeguidance';
	public const MANAGE_TYPEGUIDANCE      = 'fstu_manage_typeguidance';
	public const DELETE_TYPEGUIDANCE      = 'fstu_delete_typeguidance';
	public const VIEW_TYPEGUIDANCE_PROTOCOL = 'fstu_view_typeguidance_protocol';
	public const VIEW_MEMBER_REGIONAL        = 'fstu_view_member_regional';
	public const MANAGE_MEMBER_REGIONAL      = 'fstu_manage_member_regional';
	public const DELETE_MEMBER_REGIONAL      = 'fstu_delete_member_regional';
	public const VIEW_MEMBER_REGIONAL_PROTOCOL = 'fstu_view_member_regional_protocol';
	public const VIEW_MEMBER_GUIDANCE          = 'fstu_view_member_guidance';
	public const MANAGE_MEMBER_GUIDANCE        = 'fstu_manage_member_guidance';
	public const DELETE_MEMBER_GUIDANCE        = 'fstu_delete_member_guidance';
	public const DELETE_OWN_MEMBER_GUIDANCE    = 'fstu_delete_own_member_guidance';
	public const VIEW_MEMBER_GUIDANCE_PROTOCOL = 'fstu_view_member_guidance_protocol';
	public const VIEW_COUNTRY                  = 'fstu_view_country';
	public const MANAGE_COUNTRY                = 'fstu_manage_country';
	public const DELETE_COUNTRY                = 'fstu_delete_country';
	public const VIEW_COUNTRY_PROTOCOL         = 'fstu_view_country_protocol';
	public const VIEW_REGION                   = 'fstu_view_region';
	public const MANAGE_REGION                 = 'fstu_manage_region';
	public const DELETE_REGION                 = 'fstu_delete_region';
	public const VIEW_REGION_PROTOCOL          = 'fstu_view_region_protocol';

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
				self::VIEW_TYPEGUIDANCE        => true,
				self::MANAGE_TYPEGUIDANCE      => true,
				self::DELETE_TYPEGUIDANCE      => true,
				self::VIEW_TYPEGUIDANCE_PROTOCOL => true,
				self::VIEW_MEMBER_REGIONAL        => true,
				self::MANAGE_MEMBER_REGIONAL      => true,
				self::DELETE_MEMBER_REGIONAL      => true,
				self::VIEW_MEMBER_REGIONAL_PROTOCOL => true,
				self::VIEW_MEMBER_GUIDANCE          => true,
				self::MANAGE_MEMBER_GUIDANCE        => true,
				self::DELETE_MEMBER_GUIDANCE        => true,
				self::VIEW_MEMBER_GUIDANCE_PROTOCOL => true,
				self::VIEW_COUNTRY                  => true,
				self::MANAGE_COUNTRY                => true,
				self::DELETE_COUNTRY                => true,
				self::VIEW_COUNTRY_PROTOCOL         => true,
				self::VIEW_REGION                   => true,
				self::MANAGE_REGION                 => true,
				self::DELETE_REGION                 => true,
				self::VIEW_REGION_PROTOCOL          => true,
			],
			'userregistrar' => [
				self::ACCESS_ADMIN             => true,
				self::MANAGE_COMMISSION        => true,
				self::VIEW_COMMISSION_PROTOCOL => true,
				self::VIEW_MEMBER_REGIONAL     => true,
				self::MANAGE_MEMBER_REGIONAL   => true,
				self::VIEW_MEMBER_GUIDANCE       => true,
				self::MANAGE_MEMBER_GUIDANCE     => true,
				self::DELETE_OWN_MEMBER_GUIDANCE => true,
				self::VIEW_MEMBER_GUIDANCE_PROTOCOL => true,
				self::VIEW_COUNTRY               => true,
				self::MANAGE_COUNTRY             => true,
				self::DELETE_COUNTRY             => true,
				self::VIEW_COUNTRY_PROTOCOL      => true,
				self::VIEW_REGION                => true,
				self::VIEW_REGION_PROTOCOL       => true,
			],
			'userfstu' => [
				self::VIEW_TYPEGUIDANCE => true,
				self::VIEW_MEMBER_GUIDANCE => true,
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
	 * Повертає прапорці прав для модуля керівних органів.
	 *
	 * @return array<string,bool>
	 */
	public static function get_typeguidance_permissions(): array {
		$can_view = self::current_user_can_view_typeguidance();

		return [
			'canView'     => $can_view,
			'canManage'   => self::current_user_can_manage_typeguidance(),
			'canDelete'   => self::current_user_can_delete_typeguidance(),
			'canProtocol' => self::current_user_can_view_typeguidance_protocol(),
		];
	}

	/**
	 * Повертає прапорці прав для модуля посад федерацій.
	 *
	 * @return array<string,bool>
	 */
	public static function get_member_regional_permissions(): array {
		$can_view = self::current_user_can_view_member_regional();

		return [
			'canView'      => $can_view,
			'canManage'    => self::current_user_can_manage_member_regional(),
			'canDelete'    => self::current_user_can_delete_member_regional(),
			'canProtocol'  => self::current_user_can_view_member_regional_protocol(),
			'canAdminMeta' => self::current_user_can_delete_member_regional(),
		];
	}

	/**
	 * Повертає прапорці прав для модуля посад у керівних органах.
	 *
	 * @return array<string,bool>
	 */
	public static function get_member_guidance_permissions(): array {
		$can_view       = self::current_user_can_view_member_guidance();
		$can_delete_any = self::current_user_can_delete_member_guidance();
		$can_delete_own = self::current_user_can_delete_own_member_guidance();

		return [
			'canView'      => $can_view,
			'canManage'    => self::current_user_can_manage_member_guidance(),
			'canDelete'    => $can_delete_any || $can_delete_own,
			'canDeleteAny' => $can_delete_any,
			'canDeleteOwn' => $can_delete_own,
			'canProtocol'  => self::current_user_can_view_member_guidance_protocol(),
		];
	}

	/**
	 * Повертає прапорці прав для модуля довідника країн.
	 *
	 * @return array<string,bool>
	 */
	public static function get_country_permissions(): array {
		$can_view = self::current_user_can_view_country();

		return [
			'canView'     => $can_view,
			'canManage'   => self::current_user_can_manage_country(),
			'canDelete'   => self::current_user_can_delete_country(),
			'canProtocol' => self::current_user_can_view_country_protocol(),
		];
	}

	/**
	 * Повертає прапорці прав для модуля довідника областей.
	 *
	 * @return array<string,bool>
	 */
	public static function get_region_permissions(): array {
		$can_view = self::current_user_can_view_region();

		return [
			'canView'     => $can_view,
			'canManage'   => self::current_user_can_manage_region(),
			'canDelete'   => self::current_user_can_delete_region(),
			'canProtocol' => self::current_user_can_view_region_protocol(),
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

	/**
	 * Чи може користувач переглядати довідник керівних органів.
	 */
	public static function current_user_can_view_typeguidance(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_TYPEGUIDANCE );
	}

	/**
	 * Чи може користувач керувати довідником керівних органів.
	 */
	public static function current_user_can_manage_typeguidance(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_TYPEGUIDANCE );
	}

	/**
	 * Чи може користувач видаляти записи довідника керівних органів.
	 */
	public static function current_user_can_delete_typeguidance(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::DELETE_TYPEGUIDANCE );
	}

	/**
	 * Чи може користувач переглядати протокол довідника керівних органів.
	 */
	public static function current_user_can_view_typeguidance_protocol(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_TYPEGUIDANCE_PROTOCOL );
	}

	/**
	 * Чи може користувач переглядати довідник посад федерацій.
	 */
	public static function current_user_can_view_member_regional(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_MEMBER_REGIONAL );
	}

	/**
	 * Чи може користувач керувати довідником посад федерацій.
	 */
	public static function current_user_can_manage_member_regional(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_MEMBER_REGIONAL );
	}

	/**
	 * Чи може користувач видаляти записи довідника посад федерацій.
	 */
	public static function current_user_can_delete_member_regional(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::DELETE_MEMBER_REGIONAL );
	}

	/**
	 * Чи може користувач переглядати протокол довідника посад федерацій.
	 */
	public static function current_user_can_view_member_regional_protocol(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_MEMBER_REGIONAL_PROTOCOL );
	}

	/**
	 * Чи може користувач переглядати довідник посад у керівних органах.
	 */
	public static function current_user_can_view_member_guidance(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_MEMBER_GUIDANCE );
	}

	/**
	 * Чи може користувач керувати довідником посад у керівних органах.
	 */
	public static function current_user_can_manage_member_guidance(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_MEMBER_GUIDANCE );
	}

	/**
	 * Чи може користувач видаляти будь-які записи довідника посад у керівних органах.
	 */
	public static function current_user_can_delete_member_guidance(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::DELETE_MEMBER_GUIDANCE );
	}

	/**
	 * Чи може користувач видаляти лише власні записи довідника посад у керівних органах.
	 */
	public static function current_user_can_delete_own_member_guidance(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::DELETE_OWN_MEMBER_GUIDANCE );
	}

	/**
	 * Чи може користувач переглядати протокол довідника посад у керівних органах.
	 */
	public static function current_user_can_view_member_guidance_protocol(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_MEMBER_GUIDANCE_PROTOCOL );
	}

	/**
	 * Чи може користувач переглядати довідник країн.
	 */
	public static function current_user_can_view_country(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_COUNTRY );
	}

	/**
	 * Чи може користувач керувати довідником країн.
	 */
	public static function current_user_can_manage_country(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_COUNTRY );
	}

	/**
	 * Чи може користувач видаляти записи довідника країн.
	 */
	public static function current_user_can_delete_country(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::DELETE_COUNTRY );
	}

	/**
	 * Чи може користувач переглядати протокол довідника країн.
	 */
	public static function current_user_can_view_country_protocol(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_COUNTRY_PROTOCOL );
	}

	/**
	 * Чи може користувач переглядати довідник областей.
	 */
	public static function current_user_can_view_region(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_REGION );
	}

	/**
	 * Чи може користувач керувати довідником областей.
	 */
	public static function current_user_can_manage_region(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_REGION );
	}

	/**
	 * Чи може користувач видаляти записи довідника областей.
	 */
	public static function current_user_can_delete_region(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::DELETE_REGION );
	}

	/**
	 * Чи може користувач переглядати протокол довідника областей.
	 */
	public static function current_user_can_view_region_protocol(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_REGION_PROTOCOL );
	}
}
