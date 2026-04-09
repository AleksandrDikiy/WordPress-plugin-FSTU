<?php
/**
 * Клас централізованого керування capability-моделлю ФСТУ.
 *
 * Version:     1.13.0
 * Date_update: 2026-04-09
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
	public const VIEW_CITY                     = 'fstu_view_city';
	public const MANAGE_CITY                   = 'fstu_manage_city';
	public const DELETE_CITY                   = 'fstu_delete_city';
	public const VIEW_CITY_PROTOCOL            = 'fstu_view_city_protocol';
	public const VIEW_SAILBOATS                = 'fstu_view_sailboats';
	public const SUBMIT_SAILBOATS_APPLICATIONS = 'fstu_submit_sailboats_applications';
	public const MANAGE_SAILBOATS              = 'fstu_manage_sailboats';
	public const DELETE_SAILBOATS              = 'fstu_delete_sailboats';
	public const VIEW_SAILBOATS_PROTOCOL       = 'fstu_view_sailboats_protocol';
	public const MANAGE_SAILBOATS_PAYMENTS     = 'fstu_manage_sailboats_payments';
	public const MANAGE_SAILBOATS_STATUS       = 'fstu_manage_sailboats_status';
	public const SEND_SAILBOATS_NOTIFICATIONS  = 'fstu_send_sailboats_notifications';
	public const VIEW_SAILBOATS_FINANCE_COLUMNS = 'fstu_view_sailboats_finance_columns';
	public const VIEW_REFEREES                 = 'fstu_view_referees';
	public const MANAGE_REFEREES               = 'fstu_manage_referees';
	public const DELETE_REFEREES               = 'fstu_delete_referees';
	public const VIEW_REFEREES_PROTOCOL        = 'fstu_view_referees_protocol';
	public const MANAGE_REFEREE_CERTIFICATES   = 'fstu_manage_referee_certificates';
	public const UNBIND_REFEREE_CERTIFICATES   = 'fstu_unbind_referee_certificates';
	public const VIEW_STEERING                 = 'fstu_view_steering';
	public const SUBMIT_STEERING_APPLICATIONS  = 'fstu_submit_steering_applications';
	public const MANAGE_STEERING               = 'fstu_manage_steering';
	public const DELETE_STEERING               = 'fstu_delete_steering';
	public const VIEW_STEERING_PROTOCOL        = 'fstu_view_steering_protocol';
	public const VERIFY_STEERING_QUALIFICATION = 'fstu_verify_steering_qualification';
	public const MANAGE_STEERING_STATUS        = 'fstu_manage_steering_status';
	public const VIEW_STEERING_FINANCE_COLUMNS = 'fstu_view_steering_finance_columns';
	public const SEND_STEERING_NOTIFICATIONS   = 'fstu_send_steering_notifications';
	public const VIEW_PERSONAL_CABINET         = 'fstu_view_personal_cabinet';
	public const MANAGE_PERSONAL_CABINET       = 'fstu_manage_personal_cabinet';
	public const VIEW_PERSONAL_CABINET_PROTOCOL = 'fstu_view_personal_cabinet_protocol';
	public const VIEW_OWN_PERSONAL_PRIVATE     = 'fstu_view_own_personal_private';
	public const VIEW_PERSONAL_SERVICE         = 'fstu_view_personal_service';
	public const MANAGE_PERSONAL_PROFILE       = 'fstu_manage_personal_profile';
	public const MANAGE_PERSONAL_CLUBS         = 'fstu_manage_personal_clubs';
	public const MANAGE_PERSONAL_CITIES        = 'fstu_manage_personal_cities';
	public const MANAGE_PERSONAL_UNITS         = 'fstu_manage_personal_units';
	public const MANAGE_PERSONAL_TOURISM_TYPES = 'fstu_manage_personal_tourism_types';
	public const MANAGE_PERSONAL_EXPERIENCE    = 'fstu_manage_personal_experience';
	public const MANAGE_PERSONAL_RANKS         = 'fstu_manage_personal_ranks';
	public const MANAGE_PERSONAL_JUDGING       = 'fstu_manage_personal_judging';
	public const MANAGE_PERSONAL_DUES          = 'fstu_manage_personal_dues';
	public const PAY_PERSONAL_DUES_ONLINE      = 'fstu_pay_personal_dues_online';
	public const MANAGE_PERSONAL_SAILING       = 'fstu_manage_personal_sailing';
	public const MANAGE_PERSONAL_SAIL_DUES     = 'fstu_manage_personal_sail_dues';
	public const VIEW_PERSONAL_SAIL_DUES       = 'fstu_view_personal_sail_dues';

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
		self::ensure_core_roles();

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
				self::VIEW_CITY                     => true,
				self::MANAGE_CITY                   => true,
				self::DELETE_CITY                   => true,
				self::VIEW_CITY_PROTOCOL            => true,
				self::VIEW_SAILBOATS                => true,
				self::SUBMIT_SAILBOATS_APPLICATIONS => true,
				self::MANAGE_SAILBOATS              => true,
				self::DELETE_SAILBOATS              => true,
				self::VIEW_SAILBOATS_PROTOCOL       => true,
				self::MANAGE_SAILBOATS_PAYMENTS     => true,
				self::MANAGE_SAILBOATS_STATUS       => true,
				self::SEND_SAILBOATS_NOTIFICATIONS  => true,
				self::VIEW_SAILBOATS_FINANCE_COLUMNS => true,
				self::VIEW_REFEREES                 => true,
				self::MANAGE_REFEREES               => true,
				self::DELETE_REFEREES               => true,
				self::VIEW_REFEREES_PROTOCOL        => true,
				self::MANAGE_REFEREE_CERTIFICATES   => true,
				self::UNBIND_REFEREE_CERTIFICATES   => true,
				self::VIEW_STEERING                 => true,
				self::SUBMIT_STEERING_APPLICATIONS  => true,
				self::MANAGE_STEERING               => true,
				self::DELETE_STEERING               => true,
				self::VIEW_STEERING_PROTOCOL        => true,
				self::VERIFY_STEERING_QUALIFICATION => true,
				self::MANAGE_STEERING_STATUS        => true,
				self::VIEW_STEERING_FINANCE_COLUMNS => true,
				self::SEND_STEERING_NOTIFICATIONS   => true,
				self::VIEW_PERSONAL_CABINET         => true,
				self::MANAGE_PERSONAL_CABINET       => true,
				self::VIEW_PERSONAL_CABINET_PROTOCOL => true,
				self::VIEW_OWN_PERSONAL_PRIVATE     => true,
				self::VIEW_PERSONAL_SERVICE         => true,
				self::MANAGE_PERSONAL_PROFILE       => true,
				self::MANAGE_PERSONAL_CLUBS         => true,
				self::MANAGE_PERSONAL_CITIES        => true,
				self::MANAGE_PERSONAL_UNITS         => true,
				self::MANAGE_PERSONAL_TOURISM_TYPES => true,
				self::MANAGE_PERSONAL_EXPERIENCE    => true,
				self::MANAGE_PERSONAL_RANKS         => true,
				self::MANAGE_PERSONAL_JUDGING       => true,
				self::MANAGE_PERSONAL_DUES          => true,
				self::PAY_PERSONAL_DUES_ONLINE      => true,
				self::MANAGE_PERSONAL_SAILING       => true,
				self::MANAGE_PERSONAL_SAIL_DUES     => true,
				self::VIEW_PERSONAL_SAIL_DUES       => true,
			],
			'sailadministrator' => [
				self::ACCESS_ADMIN                  => true,
				self::VIEW_SAILBOATS                => true,
				self::SUBMIT_SAILBOATS_APPLICATIONS => true,
				self::MANAGE_SAILBOATS              => true,
				self::VIEW_SAILBOATS_PROTOCOL       => true,
				self::MANAGE_SAILBOATS_PAYMENTS     => true,
				self::MANAGE_SAILBOATS_STATUS       => true,
				self::SEND_SAILBOATS_NOTIFICATIONS  => true,
				self::VIEW_SAILBOATS_FINANCE_COLUMNS => true,
				self::VIEW_STEERING                 => true,
				self::SUBMIT_STEERING_APPLICATIONS  => true,
				self::MANAGE_STEERING               => true,
				self::DELETE_STEERING               => true,
				self::VIEW_STEERING_PROTOCOL        => true,
				self::VERIFY_STEERING_QUALIFICATION => true,
				self::MANAGE_STEERING_STATUS        => true,
				self::VIEW_STEERING_FINANCE_COLUMNS => true,
				self::SEND_STEERING_NOTIFICATIONS   => true,
				self::VIEW_PERSONAL_CABINET         => true,
				self::MANAGE_PERSONAL_SAILING       => true,
				self::VIEW_PERSONAL_SAIL_DUES       => true,
			],
			'globalregistrar' => [
				self::ACCESS_ADMIN                  => true,
				self::VIEW_PERSONAL_CABINET         => true,
				self::MANAGE_PERSONAL_CABINET       => true,
				self::VIEW_PERSONAL_CABINET_PROTOCOL => true,
				self::VIEW_PERSONAL_SERVICE         => true,
				self::MANAGE_PERSONAL_PROFILE       => true,
				self::MANAGE_PERSONAL_CLUBS         => true,
				self::MANAGE_PERSONAL_CITIES        => true,
				self::MANAGE_PERSONAL_UNITS         => true,
				self::MANAGE_PERSONAL_TOURISM_TYPES => true,
				self::MANAGE_PERSONAL_EXPERIENCE    => true,
				self::MANAGE_PERSONAL_JUDGING       => true,
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
				self::VIEW_CITY                  => true,
				self::MANAGE_CITY                => true,
				self::DELETE_CITY                => true,
				self::VIEW_CITY_PROTOCOL         => true,
				self::VIEW_REFEREES              => true,
				self::VIEW_PERSONAL_CABINET      => true,
				self::MANAGE_PERSONAL_CABINET    => true,
				self::VIEW_PERSONAL_CABINET_PROTOCOL => true,
				self::VIEW_PERSONAL_SERVICE      => true,
				self::MANAGE_PERSONAL_PROFILE    => true,
				self::MANAGE_PERSONAL_CLUBS      => true,
				self::MANAGE_PERSONAL_CITIES     => true,
				self::MANAGE_PERSONAL_UNITS      => true,
				self::MANAGE_PERSONAL_TOURISM_TYPES => true,
				self::MANAGE_PERSONAL_EXPERIENCE => true,
				self::MANAGE_PERSONAL_RANKS      => true,
			],
			'userfstu' => [
				self::VIEW_TYPEGUIDANCE => true,
				self::VIEW_MEMBER_GUIDANCE => true,
				self::VIEW_SAILBOATS => true,
				self::SUBMIT_SAILBOATS_APPLICATIONS => true,
				self::VIEW_REFEREES => true,
				self::VIEW_STEERING => true,
				self::SUBMIT_STEERING_APPLICATIONS => true,
				self::VIEW_PERSONAL_CABINET => true,
				self::VIEW_OWN_PERSONAL_PRIVATE => true,
				self::PAY_PERSONAL_DUES_ONLINE => true,
			],
			'referee' => [
				self::VIEW_REFEREES               => true,
				self::MANAGE_REFEREES             => true,
				self::DELETE_REFEREES             => true,
				self::VIEW_REFEREES_PROTOCOL      => true,
				self::MANAGE_REFEREE_CERTIFICATES => true,
				self::UNBIND_REFEREE_CERTIFICATES => true,
				self::VIEW_PERSONAL_CABINET       => true,
				self::MANAGE_PERSONAL_EXPERIENCE  => true,
				self::MANAGE_PERSONAL_JUDGING     => true,
			],
			'sailingfinancier' => [
				self::VIEW_PERSONAL_CABINET => true,
				self::MANAGE_PERSONAL_SAILING => true,
				self::MANAGE_PERSONAL_SAIL_DUES => true,
				self::VIEW_PERSONAL_SAIL_DUES => true,
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
	 * Реєструє базові службові ролі плагіна, якщо їх ще немає у WordPress.
	 */
	private static function ensure_core_roles(): void {
		if ( ! get_role( 'applicants' ) ) {
			add_role(
				'applicants',
				'Заявник ФСТУ',
				[
					'read' => true,
				]
			);
		}

		if ( ! get_role( 'referee' ) ) {
			add_role(
				'referee',
				'Суддя ФСТУ',
				[
					'read' => true,
				]
			);
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
	 * Повертає прапорці прав для модуля довідника міст.
	 *
	 * @return array<string,bool>
	 */
	public static function get_city_permissions(): array {
		$can_view = self::current_user_can_view_city();

		return [
			'canView'     => $can_view,
			'canManage'   => self::current_user_can_manage_city(),
			'canDelete'   => self::current_user_can_delete_city(),
			'canProtocol' => self::current_user_can_view_city_protocol(),
		];
	}

	/**
	 * Повертає прапорці прав для модуля реєстру суден.
	 *
	 * @return array<string,bool>
	 */
	public static function get_sailboats_permissions(): array {
		$can_view = self::current_user_can_view_sailboats();

		return [
			'canView'      => $can_view,
			'canSubmit'    => self::current_user_can_submit_sailboats_applications(),
			'canManage'    => self::current_user_can_manage_sailboats(),
			'canDelete'    => self::current_user_can_delete_sailboats(),
			'canProtocol'  => self::current_user_can_view_sailboats_protocol(),
			'canPayments'  => self::current_user_can_manage_sailboats_payments(),
			'canStatus'    => self::current_user_can_manage_sailboats_status(),
			'canNotify'    => self::current_user_can_send_sailboats_notifications(),
			'canFinance'   => self::current_user_can_view_sailboats_finance_columns(),
		];
	}

	/**
	 * Повертає прапорці прав для модуля суддів.
	 *
	 * @return array<string,bool>
	 */
	public static function get_referees_permissions(): array {
		$can_view = self::current_user_can_view_referees();

		return [
			'canView'               => $can_view,
			'canManage'             => self::current_user_can_manage_referees(),
			'canDelete'             => self::current_user_can_delete_referees(),
			'canProtocol'           => self::current_user_can_view_referees_protocol(),
			'canManageCertificates' => self::current_user_can_manage_referee_certificates(),
			'canUnbindCertificates' => self::current_user_can_unbind_referee_certificates(),
		];
	}

	/**
	 * Повертає прапорці прав для модуля реєстру стернових.
	 *
	 * @return array<string,bool>
	 */
	public static function get_steering_permissions(): array {
		return [
			'canView'              => self::current_user_can_view_steering(),
			'canSubmit'            => self::current_user_can_submit_steering_applications(),
			'canManage'            => self::current_user_can_manage_steering(),
			'canDelete'            => self::current_user_can_delete_steering(),
			'canProtocol'          => self::current_user_can_view_steering_protocol(),
			'canVerify'            => self::current_user_can_verify_steering_qualification(),
			'canManageStatus'      => self::current_user_can_manage_steering_status(),
			'canSeeFinance'        => self::current_user_can_view_steering_finance_columns(),
			'canNotify'            => self::current_user_can_send_steering_notifications(),
			'canViewHiddenExpired' => self::current_user_can_view_steering_finance_columns(),
		];
	}

	/**
	 * Повертає прапорці прав для модуля особистого кабінету.
	 *
	 * @return array<string,bool>
	 */
	public static function get_personal_cabinet_permissions( int $profile_user_id = 0 ): array {
		$current_user_id  = get_current_user_id();
		$normalized_user  = $profile_user_id > 0 ? $profile_user_id : $current_user_id;
		$profile_user     = $normalized_user > 0 ? get_userdata( $normalized_user ) : false;
		$has_target_user  = $profile_user instanceof \WP_User;
		$is_logged_in     = is_user_logged_in();
		$is_owner         = $current_user_id > 0 && $normalized_user === $current_user_id;
		$current_user     = wp_get_current_user();
		$user_roles       = $current_user instanceof \WP_User ? array_map( 'strval', (array) $current_user->roles ) : [];
		$can_view_public  = $has_target_user;
		$can_view         = self::current_user_can_view_personal_cabinet() || $can_view_public;
		$can_manage       = self::current_user_can_manage_personal_cabinet();
		$can_private      = $has_target_user && $is_logged_in && ( $can_manage || $is_owner || ( $is_owner && self::current_user_can_view_own_personal_private() ) );
		$can_service      = $has_target_user && $is_logged_in && ( $is_owner || $can_manage || self::current_user_can_view_personal_service() );
		$can_manage_profile = $is_logged_in && ( self::current_user_can_manage_personal_profile() || $is_owner );
		$can_manage_dues  = $has_target_user && $is_logged_in && ( self::current_user_can_manage_personal_dues() || $is_owner );
		$can_pay_online   = $has_target_user && $is_logged_in && ( self::current_user_can_manage_personal_dues() || ( $is_owner && self::current_user_can_pay_personal_dues_online() ) );
		$can_view_sail_dues = $has_target_user && ( self::current_user_can_view_personal_sail_dues() || ( $is_owner && in_array( 'usersail', $user_roles, true ) ) );

		return [
			'canView'          => $can_view,
			'canViewPrivate'   => $can_private,
			'canViewService'   => $can_service,
			'canManage'        => $can_manage,
			'canProtocol'      => self::current_user_can_view_personal_cabinet_protocol(),
			'canEditProfile'   => $can_manage_profile,
			'canManageClubs'   => self::current_user_can_manage_personal_clubs(),
			'canManageCities'  => self::current_user_can_manage_personal_cities(),
			'canManageUnits'   => self::current_user_can_manage_personal_units(),
			'canManageTourism' => self::current_user_can_manage_personal_tourism_types(),
			'canManageExperience' => self::current_user_can_manage_personal_experience() || $is_owner,
			'canManageRanks'   => self::current_user_can_manage_personal_ranks(),
			'canManageJudging' => self::current_user_can_manage_personal_judging(),
			'canManageDues'    => $can_manage_dues,
			'canUploadReceipt' => $can_manage_dues,
			'canPayOnline'     => $can_pay_online,
			'canManageSailing' => self::current_user_can_manage_personal_sailing(),
			'canManageSailDues' => self::current_user_can_manage_personal_sail_dues(),
			'canViewSailDues'  => $can_view_sail_dues,
			'isOwner'          => $is_owner,
			'isGuest'          => ! $is_logged_in,
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

	/**
	 * Чи може користувач переглядати довідник міст.
	 */
	public static function current_user_can_view_city(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_CITY );
	}

	/**
	 * Чи може користувач керувати довідником міст.
	 */
	public static function current_user_can_manage_city(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_CITY );
	}

	/**
	 * Чи може користувач видаляти записи довідника міст.
	 */
	public static function current_user_can_delete_city(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::DELETE_CITY );
	}

	/**
	 * Чи може користувач переглядати протокол довідника міст.
	 */
	public static function current_user_can_view_city_protocol(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_CITY_PROTOCOL );
	}

	/**
	 * Чи може користувач переглядати реєстр суден.
	 */
	public static function current_user_can_view_sailboats(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_SAILBOATS );
	}

	/**
	 * Чи може користувач керувати реєстром суден.
	 */
	public static function current_user_can_manage_sailboats(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_SAILBOATS );
	}

	/**
	 * Чи може користувач подавати заявки модуля реєстру суден.
	 */
	public static function current_user_can_submit_sailboats_applications(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::SUBMIT_SAILBOATS_APPLICATIONS );
	}

	/**
	 * Чи може користувач видаляти записи реєстру суден.
	 */
	public static function current_user_can_delete_sailboats(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::DELETE_SAILBOATS );
	}

	/**
	 * Чи може користувач переглядати протокол реєстру суден.
	 */
	public static function current_user_can_view_sailboats_protocol(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_SAILBOATS_PROTOCOL );
	}

	/**
	 * Чи може користувач виконувати фінансові операції реєстру суден.
	 */
	public static function current_user_can_manage_sailboats_payments(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_SAILBOATS_PAYMENTS );
	}

	/**
	 * Чи може користувач змінювати статуси у реєстрі суден.
	 */
	public static function current_user_can_manage_sailboats_status(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_SAILBOATS_STATUS );
	}

	/**
	 * Чи може користувач надсилати сповіщення модуля реєстру суден.
	 */
	public static function current_user_can_send_sailboats_notifications(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::SEND_SAILBOATS_NOTIFICATIONS );
	}

	/**
	 * Чи може користувач бачити фінансові колонки модуля.
	 */
	public static function current_user_can_view_sailboats_finance_columns(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_SAILBOATS_FINANCE_COLUMNS );
	}

	/**
	 * Чи може користувач переглядати довідник суддів.
	 */
	public static function current_user_can_view_referees(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_REFEREES );
	}

	/**
	 * Чи може користувач керувати довідником суддів.
	 */
	public static function current_user_can_manage_referees(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_REFEREES );
	}

	/**
	 * Чи може користувач видаляти записи довідника суддів.
	 */
	public static function current_user_can_delete_referees(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::DELETE_REFEREES );
	}

	/**
	 * Чи може користувач переглядати протокол довідника суддів.
	 */
	public static function current_user_can_view_referees_protocol(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_REFEREES_PROTOCOL );
	}

	/**
	 * Чи може користувач керувати сертифікатами суддів.
	 */
	public static function current_user_can_manage_referee_certificates(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_REFEREE_CERTIFICATES );
	}

	/**
	 * Чи може користувач розв'язувати сертифікати суддів.
	 */
	public static function current_user_can_unbind_referee_certificates(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::UNBIND_REFEREE_CERTIFICATES );
	}

	/**
	 * Чи може користувач переглядати реєстр стернових.
	 */
	public static function current_user_can_view_steering(): bool {
		return true;
	}

	/**
	 * Чи може користувач подавати заявку до реєстру стернових.
	 */
	public static function current_user_can_submit_steering_applications(): bool {
		return current_user_can( 'manage_options' )
			|| current_user_can( self::SUBMIT_STEERING_APPLICATIONS )
			|| current_user_can( self::MANAGE_STEERING );
	}

	/**
	 * Чи може користувач керувати реєстром стернових.
	 */
	public static function current_user_can_manage_steering(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_STEERING );
	}

	/**
	 * Чи може користувач видаляти записи реєстру стернових.
	 */
	public static function current_user_can_delete_steering(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::DELETE_STEERING );
	}

	/**
	 * Чи може користувач переглядати протокол реєстру стернових.
	 */
	public static function current_user_can_view_steering_protocol(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_STEERING_PROTOCOL );
	}

	/**
	 * Чи може користувач підтверджувати кваліфікацію в реєстрі стернових.
	 */
	public static function current_user_can_verify_steering_qualification(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VERIFY_STEERING_QUALIFICATION );
	}

	/**
	 * Чи може користувач змінювати статуси реєстру стернових.
	 */
	public static function current_user_can_manage_steering_status(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_STEERING_STATUS );
	}

	/**
	 * Чи може користувач бачити фінансові колонки реєстру стернових.
	 */
	public static function current_user_can_view_steering_finance_columns(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_STEERING_FINANCE_COLUMNS );
	}

	/**
	 * Чи може користувач надсилати сповіщення модуля реєстру стернових.
	 */
	public static function current_user_can_send_steering_notifications(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::SEND_STEERING_NOTIFICATIONS );
	}

	/**
	 * Чи може користувач переглядати модуль особистого кабінету.
	 */
	public static function current_user_can_view_personal_cabinet(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_PERSONAL_CABINET );
	}

	/**
	 * Чи може користувач керувати даними особистого кабінету.
	 */
	public static function current_user_can_manage_personal_cabinet(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_PERSONAL_CABINET );
	}

	/**
	 * Чи може користувач переглядати протокол особистого кабінету.
	 */
	public static function current_user_can_view_personal_cabinet_protocol(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_PERSONAL_CABINET_PROTOCOL );
	}

	public static function current_user_can_view_own_personal_private(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_OWN_PERSONAL_PRIVATE );
	}

	public static function current_user_can_view_personal_service(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_PERSONAL_SERVICE );
	}

	public static function current_user_can_manage_personal_profile(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_PERSONAL_PROFILE );
	}

	public static function current_user_can_manage_personal_clubs(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_PERSONAL_CLUBS );
	}

	public static function current_user_can_manage_personal_cities(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_PERSONAL_CITIES );
	}

	public static function current_user_can_manage_personal_units(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_PERSONAL_UNITS );
	}

	public static function current_user_can_manage_personal_tourism_types(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_PERSONAL_TOURISM_TYPES );
	}

	public static function current_user_can_manage_personal_experience(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_PERSONAL_EXPERIENCE );
	}

	public static function current_user_can_manage_personal_ranks(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_PERSONAL_RANKS );
	}

	public static function current_user_can_manage_personal_judging(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_PERSONAL_JUDGING );
	}

	public static function current_user_can_manage_personal_dues(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_PERSONAL_DUES );
	}

	public static function current_user_can_pay_personal_dues_online(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::PAY_PERSONAL_DUES_ONLINE );
	}

	public static function current_user_can_manage_personal_sailing(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_PERSONAL_SAILING );
	}

	public static function current_user_can_manage_personal_sail_dues(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_PERSONAL_SAIL_DUES );
	}

	public static function current_user_can_view_personal_sail_dues(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_PERSONAL_SAIL_DUES );
	}
}
