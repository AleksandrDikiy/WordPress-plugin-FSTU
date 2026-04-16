<?php
/**
 * Клас централізованого керування capability-моделлю ФСТУ.
 *
 * Version:     1.26.1
 * Date_update: 2026-04-14
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
	public const VIEW_PARTICIPATION_TYPE  = 'fstu_view_participation_type';
	public const MANAGE_PARTICIPATION_TYPE = 'fstu_manage_participation_type';
	public const DELETE_PARTICIPATION_TYPE = 'fstu_delete_participation_type';
	public const VIEW_PARTICIPATION_TYPE_PROTOCOL = 'fstu_view_participation_type_protocol';
	public const VIEW_TOURTYPE            = 'fstu_view_tourtype';
	public const MANAGE_TOURTYPE          = 'fstu_manage_tourtype';
	public const DELETE_TOURTYPE          = 'fstu_delete_tourtype';
	public const VIEW_TOURTYPE_PROTOCOL   = 'fstu_view_tourtype_protocol';
	public const VIEW_HIKING_CATEGORY     = 'fstu_view_hiking_category';
	public const MANAGE_HIKING_CATEGORY   = 'fstu_manage_hiking_category';
	public const DELETE_HIKING_CATEGORY   = 'fstu_delete_hiking_category';
	public const VIEW_HIKING_CATEGORY_PROTOCOL = 'fstu_view_hiking_category_protocol';
	public const VIEW_HOURCATEGORIES      = 'fstu_view_hourcategories';
	public const MANAGE_HOURCATEGORIES    = 'fstu_manage_hourcategories';
	public const DELETE_HOURCATEGORIES    = 'fstu_delete_hourcategories';
	public const VIEW_HOURCATEGORIES_PROTOCOL = 'fstu_view_hourcategories_protocol';
	public const VIEW_SPORTSCATEGORIES    = 'fstu_view_sportscategories';
	public const MANAGE_SPORTSCATEGORIES  = 'fstu_manage_sportscategories';
	public const DELETE_SPORTSCATEGORIES  = 'fstu_delete_sportscategories';
	public const VIEW_SPORTSCATEGORIES_PROTOCOL = 'fstu_view_sportscategories_protocol';
	public const VIEW_REFEREE_CATEGORY    = 'fstu_view_referee_category';
	public const MANAGE_REFEREE_CATEGORY  = 'fstu_manage_referee_category';
	public const DELETE_REFEREE_CATEGORY  = 'fstu_delete_referee_category';
	public const VIEW_REFEREE_CATEGORY_PROTOCOL = 'fstu_view_referee_category_protocol';
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
	public const VIEW_RECORDERS                = 'fstu_view_recorders';
	public const MANAGE_RECORDERS              = 'fstu_manage_recorders';
	public const DELETE_RECORDERS              = 'fstu_delete_recorders';
	public const VIEW_RECORDERS_PROTOCOL       = 'fstu_view_recorders_protocol';
	public const VIEW_MKK                      = 'fstu_view_mkk';
	public const MANAGE_MKK                    = 'fstu_manage_mkk';
	public const DELETE_MKK                    = 'fstu_delete_mkk';
	public const VIEW_MKK_PROTOCOL             = 'fstu_view_mkk_protocol';
	public const VIEW_GUIDANCE                 = 'fstu_view_guidance';
	public const VIEW_GUIDANCE_CARD            = 'fstu_view_guidance_card';
	public const MANAGE_GUIDANCE               = 'fstu_manage_guidance';
	public const DELETE_GUIDANCE               = 'fstu_delete_guidance';
	public const VIEW_GUIDANCE_PROTOCOL        = 'fstu_view_guidance_protocol';
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
	public const VIEW_MEMBER_CARD_APPLICATIONS = 'fstu_view_member_card_applications';
	public const MANAGE_MEMBER_CARD_APPLICATIONS = 'fstu_manage_member_card_applications';
	public const DELETE_MEMBER_CARD_APPLICATIONS = 'fstu_delete_member_card_applications';
	public const VIEW_MEMBER_CARD_APPLICATIONS_PROTOCOL = 'fstu_view_member_card_applications_protocol';
	public const SELF_MANAGE_MEMBER_CARD_APPLICATIONS = 'fstu_self_manage_member_card_applications';
	public const REISSUE_MEMBER_CARD_APPLICATIONS = 'fstu_reissue_member_card_applications';
	public const UPDATE_MEMBER_CARD_APPLICATIONS_PHOTO = 'fstu_update_member_card_applications_photo';
	public const MANAGE_MEMBER_CARD_NUMBER = 'fstu_manage_member_card_number';
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
	public const VIEW_CALENDAR                  = 'fstu_view_calendar';
	public const MANAGE_CALENDAR_EVENTS         = 'fstu_manage_calendar_events';
	public const MANAGE_ANY_CALENDAR_EVENTS     = 'fstu_manage_any_calendar_events';
	public const DELETE_CALENDAR_EVENTS         = 'fstu_delete_calendar_events';
	public const VIEW_CALENDAR_EVENTS_PROTOCOL  = 'fstu_view_calendar_events_protocol';
	public const SUBMIT_CALENDAR_APPLICATIONS   = 'fstu_submit_calendar_applications';
	public const MANAGE_CALENDAR_APPLICATIONS   = 'fstu_manage_calendar_applications';
	public const DELETE_CALENDAR_APPLICATIONS   = 'fstu_delete_calendar_applications';
	public const VIEW_CALENDAR_APPLICATIONS_PROTOCOL = 'fstu_view_calendar_applications_protocol';
	public const MANAGE_CALENDAR_ROUTES         = 'fstu_manage_calendar_routes';
	public const DELETE_CALENDAR_ROUTES         = 'fstu_delete_calendar_routes';
	public const VIEW_CALENDAR_ROUTES_PROTOCOL  = 'fstu_view_calendar_routes_protocol';
	public const REVIEW_CALENDAR_MKK            = 'fstu_review_calendar_mkk';
	public const VIEW_CALENDAR_RESULTS          = 'fstu_view_calendar_results';
	public const MANAGE_CALENDAR_RESULTS        = 'fstu_manage_calendar_results';
	public const DELETE_CALENDAR_RESULTS        = 'fstu_delete_calendar_results';
	public const VIEW_CALENDAR_RESULTS_PROTOCOL = 'fstu_view_calendar_results_protocol';

    public const VIEW_TYPE_CARD             = 'fstu_view_type_card';
    public const MANAGE_TYPE_CARD           = 'fstu_manage_type_card';
    public const DELETE_TYPE_CARD           = 'fstu_delete_type_card';
    public const VIEW_TYPE_CARD_PROTOCOL    = 'fstu_view_type_card_protocol';
    public const VIEW_SAIL_GROUP_TYPE          = 'fstu_view_sail_group_type';
    public const MANAGE_SAIL_GROUP_TYPE        = 'fstu_manage_sail_group_type';
    public const DELETE_SAIL_GROUP_TYPE        = 'fstu_delete_sail_group_type';
    public const VIEW_SAIL_GROUP_TYPE_PROTOCOL = 'fstu_view_sail_group_type_protocol';
    public const VIEW_TYPE_BOAT          = 'fstu_view_type_boat';
    public const MANAGE_TYPE_BOAT        = 'fstu_manage_type_boat';
    public const DELETE_TYPE_BOAT        = 'fstu_delete_type_boat';
    public const VIEW_TYPE_BOAT_PROTOCOL = 'fstu_view_type_boat_protocol';
    public const VIEW_DIRECTORY             = 'fstu_view_directory';
    public const MANAGE_DIRECTORY           = 'fstu_manage_directory';
    public const DELETE_DIRECTORY           = 'fstu_delete_directory';
    public const VIEW_DIRECTORY_PROTOCOL    = 'fstu_view_directory_protocol';
    public const SUBMIT_DIRECTORY_POLL      = 'fstu_submit_directory_poll';
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
				self::VIEW_PARTICIPATION_TYPE  => true,
				self::MANAGE_PARTICIPATION_TYPE => true,
				self::DELETE_PARTICIPATION_TYPE => true,
				self::VIEW_PARTICIPATION_TYPE_PROTOCOL => true,
				self::VIEW_TOURTYPE            => true,
				self::MANAGE_TOURTYPE          => true,
				self::DELETE_TOURTYPE          => true,
				self::VIEW_TOURTYPE_PROTOCOL   => true,
				self::VIEW_HIKING_CATEGORY     => true,
				self::MANAGE_HIKING_CATEGORY   => true,
				self::DELETE_HIKING_CATEGORY   => true,
				self::VIEW_HIKING_CATEGORY_PROTOCOL => true,
				self::VIEW_HOURCATEGORIES      => true,
				self::MANAGE_HOURCATEGORIES    => true,
				self::DELETE_HOURCATEGORIES    => true,
				self::VIEW_HOURCATEGORIES_PROTOCOL => true,
				self::VIEW_SPORTSCATEGORIES    => true,
				self::MANAGE_SPORTSCATEGORIES  => true,
				self::DELETE_SPORTSCATEGORIES  => true,
				self::VIEW_SPORTSCATEGORIES_PROTOCOL => true,
				self::VIEW_REFEREE_CATEGORY    => true,
				self::MANAGE_REFEREE_CATEGORY  => true,
				self::DELETE_REFEREE_CATEGORY  => true,
				self::VIEW_REFEREE_CATEGORY_PROTOCOL => true,
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
				self::VIEW_RECORDERS                => true,
				self::MANAGE_RECORDERS              => true,
				self::DELETE_RECORDERS              => true,
				self::VIEW_RECORDERS_PROTOCOL       => true,
				self::VIEW_MKK                      => true,
				self::MANAGE_MKK                    => true,
				self::DELETE_MKK                    => true,
				self::VIEW_MKK_PROTOCOL             => true,
				self::VIEW_GUIDANCE                 => true,
				self::VIEW_GUIDANCE_CARD            => true,
				self::MANAGE_GUIDANCE               => true,
				self::DELETE_GUIDANCE               => true,
				self::VIEW_GUIDANCE_PROTOCOL        => true,
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
				self::VIEW_MEMBER_CARD_APPLICATIONS => true,
				self::MANAGE_MEMBER_CARD_APPLICATIONS => true,
				self::DELETE_MEMBER_CARD_APPLICATIONS => true,
				self::VIEW_MEMBER_CARD_APPLICATIONS_PROTOCOL => true,
				self::SELF_MANAGE_MEMBER_CARD_APPLICATIONS => true,
				self::REISSUE_MEMBER_CARD_APPLICATIONS => true,
				self::UPDATE_MEMBER_CARD_APPLICATIONS_PHOTO => true,
				self::MANAGE_MEMBER_CARD_NUMBER => true,
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
				self::VIEW_CALENDAR                 => true,
				self::MANAGE_CALENDAR_EVENTS        => true,
				self::MANAGE_ANY_CALENDAR_EVENTS    => true,
				self::DELETE_CALENDAR_EVENTS        => true,
				self::VIEW_CALENDAR_EVENTS_PROTOCOL => true,
				self::SUBMIT_CALENDAR_APPLICATIONS  => true,
				self::MANAGE_CALENDAR_APPLICATIONS  => true,
				self::DELETE_CALENDAR_APPLICATIONS  => true,
				self::VIEW_CALENDAR_APPLICATIONS_PROTOCOL => true,
				self::MANAGE_CALENDAR_ROUTES        => true,
				self::DELETE_CALENDAR_ROUTES        => true,
				self::VIEW_CALENDAR_ROUTES_PROTOCOL => true,
				self::REVIEW_CALENDAR_MKK           => true,
				self::VIEW_CALENDAR_RESULTS         => true,
				self::MANAGE_CALENDAR_RESULTS       => true,
				self::DELETE_CALENDAR_RESULTS       => true,
				self::VIEW_CALENDAR_RESULTS_PROTOCOL => true,
                self::VIEW_TYPE_CARD             => true,
                self::MANAGE_TYPE_CARD           => true,
                self::DELETE_TYPE_CARD           => true,
                self::VIEW_TYPE_CARD_PROTOCOL    => true,
                self::VIEW_SAIL_GROUP_TYPE          => true,
                self::MANAGE_SAIL_GROUP_TYPE        => true,
                self::DELETE_SAIL_GROUP_TYPE        => true,
                self::VIEW_SAIL_GROUP_TYPE_PROTOCOL => true,
                self::VIEW_TYPE_BOAT          => true,
                self::MANAGE_TYPE_BOAT        => true,
                self::DELETE_TYPE_BOAT        => true,
                self::VIEW_TYPE_BOAT_PROTOCOL => true,
                self::VIEW_DIRECTORY             => true,
                self::MANAGE_DIRECTORY           => true,
                self::DELETE_DIRECTORY           => true,
                self::VIEW_DIRECTORY_PROTOCOL    => true,
                self::SUBMIT_DIRECTORY_POLL      => true,
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
                self::VIEW_SAIL_GROUP_TYPE          => true,
                self::MANAGE_SAIL_GROUP_TYPE        => true,
                self::DELETE_SAIL_GROUP_TYPE        => true,
                self::VIEW_SAIL_GROUP_TYPE_PROTOCOL => true,
                self::VIEW_TYPE_BOAT          => true,
                self::MANAGE_TYPE_BOAT        => true,
                self::DELETE_TYPE_BOAT        => true,
                self::VIEW_TYPE_BOAT_PROTOCOL => true,
			],
			'globalregistrar' => [
				self::ACCESS_ADMIN                  => true,
				self::VIEW_PARTICIPATION_TYPE       => true,
				self::MANAGE_PARTICIPATION_TYPE     => true,
				self::DELETE_PARTICIPATION_TYPE     => true,
				self::VIEW_PARTICIPATION_TYPE_PROTOCOL => true,
				self::VIEW_TOURTYPE                => true,
				self::MANAGE_TOURTYPE              => true,
				self::DELETE_TOURTYPE              => true,
				self::VIEW_TOURTYPE_PROTOCOL       => true,
				self::VIEW_HIKING_CATEGORY         => true,
				self::MANAGE_HIKING_CATEGORY       => true,
				self::DELETE_HIKING_CATEGORY       => true,
				self::VIEW_HIKING_CATEGORY_PROTOCOL => true,
				self::VIEW_HOURCATEGORIES         => true,
				self::MANAGE_HOURCATEGORIES       => true,
				self::DELETE_HOURCATEGORIES       => true,
				self::VIEW_HOURCATEGORIES_PROTOCOL => true,
				self::VIEW_SPORTSCATEGORIES       => true,
				self::MANAGE_SPORTSCATEGORIES     => true,
				self::DELETE_SPORTSCATEGORIES     => true,
				self::VIEW_SPORTSCATEGORIES_PROTOCOL => true,
				self::VIEW_REFEREE_CATEGORY       => true,
				self::MANAGE_REFEREE_CATEGORY     => true,
				self::DELETE_REFEREE_CATEGORY     => true,
				self::VIEW_REFEREE_CATEGORY_PROTOCOL => true,
				self::VIEW_MEMBER_CARD_APPLICATIONS => true,
				self::MANAGE_MEMBER_CARD_APPLICATIONS => true,
				self::VIEW_MEMBER_CARD_APPLICATIONS_PROTOCOL => true,
				self::REISSUE_MEMBER_CARD_APPLICATIONS => true,
				self::UPDATE_MEMBER_CARD_APPLICATIONS_PHOTO => true,
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
				self::VIEW_RECORDERS                => true,
				self::MANAGE_RECORDERS              => true,
				self::DELETE_RECORDERS              => true,
				self::VIEW_RECORDERS_PROTOCOL       => true,
				self::VIEW_MKK                      => true,
				self::MANAGE_MKK                    => true,
				self::DELETE_MKK                    => true,
				self::VIEW_MKK_PROTOCOL             => true,
				self::VIEW_GUIDANCE                 => true,
				self::VIEW_GUIDANCE_CARD            => true,
				self::MANAGE_GUIDANCE               => true,
				self::DELETE_GUIDANCE               => true,
				self::VIEW_GUIDANCE_PROTOCOL        => true,
				self::VIEW_CALENDAR                 => true,
				self::MANAGE_CALENDAR_EVENTS        => true,
				self::MANAGE_ANY_CALENDAR_EVENTS    => true,
				self::DELETE_CALENDAR_EVENTS        => true,
				self::VIEW_CALENDAR_EVENTS_PROTOCOL => true,
				self::SUBMIT_CALENDAR_APPLICATIONS  => true,
				self::MANAGE_CALENDAR_APPLICATIONS  => true,
				self::DELETE_CALENDAR_APPLICATIONS  => true,
				self::VIEW_CALENDAR_APPLICATIONS_PROTOCOL => true,
				self::MANAGE_CALENDAR_ROUTES        => true,
				self::DELETE_CALENDAR_ROUTES        => true,
				self::VIEW_CALENDAR_ROUTES_PROTOCOL => true,
				self::VIEW_CALENDAR_RESULTS         => true,
				self::MANAGE_CALENDAR_RESULTS       => true,
				self::DELETE_CALENDAR_RESULTS       => true,
				self::VIEW_CALENDAR_RESULTS_PROTOCOL => true,
                // ДОДАНО ДЛЯ ВІТРИЛЬНИКІВ
                self::VIEW_SAILBOATS                => true,
                self::VIEW_SAILBOATS_FINANCE_COLUMNS => true,
                self::VIEW_STEERING                 => true,
                self::VIEW_STEERING_FINANCE_COLUMNS => true,
                self::VIEW_TYPE_CARD             => true,
                self::MANAGE_TYPE_CARD           => true,
                self::DELETE_TYPE_CARD           => true,
                self::VIEW_TYPE_CARD_PROTOCOL    => true,
                self::VIEW_DIRECTORY             => true,
                self::MANAGE_DIRECTORY           => true,
                self::DELETE_DIRECTORY           => true,
                self::VIEW_DIRECTORY_PROTOCOL    => true,
                self::SUBMIT_DIRECTORY_POLL      => true,
			],
			'userregistrar' => [
				self::ACCESS_ADMIN             => true,
				self::VIEW_PARTICIPATION_TYPE  => true,
				self::MANAGE_PARTICIPATION_TYPE => true,
				self::VIEW_PARTICIPATION_TYPE_PROTOCOL => true,
				self::VIEW_TOURTYPE            => true,
				self::VIEW_HOURCATEGORIES      => true,
				self::VIEW_REFEREE_CATEGORY    => true,
				self::VIEW_MEMBER_CARD_APPLICATIONS => true,
				self::MANAGE_MEMBER_CARD_APPLICATIONS => true,
				self::VIEW_MEMBER_CARD_APPLICATIONS_PROTOCOL => true,
				self::REISSUE_MEMBER_CARD_APPLICATIONS => true,
				self::UPDATE_MEMBER_CARD_APPLICATIONS_PHOTO => true,
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
				self::VIEW_MKK                   => true,
				self::MANAGE_MKK                 => true,
				self::DELETE_MKK                 => true,
				self::VIEW_MKK_PROTOCOL          => true,
				self::VIEW_GUIDANCE              => true,
				self::VIEW_GUIDANCE_CARD         => true,
				self::VIEW_CALENDAR              => true,
				self::MANAGE_CALENDAR_EVENTS     => true,
				self::MANAGE_ANY_CALENDAR_EVENTS => true,
				self::VIEW_CALENDAR_EVENTS_PROTOCOL => true,
				self::SUBMIT_CALENDAR_APPLICATIONS => true,
				self::MANAGE_CALENDAR_APPLICATIONS => true,
				self::VIEW_CALENDAR_APPLICATIONS_PROTOCOL => true,
				self::MANAGE_CALENDAR_ROUTES     => true,
				self::VIEW_CALENDAR_ROUTES_PROTOCOL => true,
				self::VIEW_CALENDAR_RESULTS      => true,
				self::MANAGE_CALENDAR_RESULTS    => true,
				self::VIEW_CALENDAR_RESULTS_PROTOCOL => true,
                self::VIEW_TYPE_CARD             => true,
                self::VIEW_TYPE_CARD_PROTOCOL    => true,
                self::VIEW_DIRECTORY             => true,
                self::VIEW_DIRECTORY_PROTOCOL    => true,
                self::SUBMIT_DIRECTORY_POLL      => true,
			],
			'userfstu' => [
				self::VIEW_PARTICIPATION_TYPE => true,
				self::VIEW_TOURTYPE => true,
				self::VIEW_HOURCATEGORIES => true,
				self::VIEW_REFEREE_CATEGORY => true,
				self::SELF_MANAGE_MEMBER_CARD_APPLICATIONS => true,
				self::REISSUE_MEMBER_CARD_APPLICATIONS => true,
				self::UPDATE_MEMBER_CARD_APPLICATIONS_PHOTO => true,
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
				self::VIEW_GUIDANCE => true,
				self::VIEW_GUIDANCE_CARD => true,
				self::VIEW_CALENDAR => true,
				self::MANAGE_CALENDAR_EVENTS => true,
				self::SUBMIT_CALENDAR_APPLICATIONS => true,
				self::MANAGE_CALENDAR_ROUTES => true,
				self::VIEW_CALENDAR_RESULTS => true,
                self::VIEW_SAIL_GROUP_TYPE          => true,
                self::VIEW_TYPE_BOAT => true,
                self::VIEW_DIRECTORY             => true,
                self::SUBMIT_DIRECTORY_POLL      => true,
			],
			'referee' => [
				self::VIEW_REFEREES               => true,
				self::VIEW_REFEREE_CATEGORY       => true,
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
				self::VIEW_PERSONAL_CABINET     => true,
				self::MANAGE_PERSONAL_SAILING   => true,
				self::MANAGE_PERSONAL_SAIL_DUES => true,
				self::VIEW_PERSONAL_SAIL_DUES   => true,
                // ДОДАНО ДЛЯ ВІТРИЛЬНИКІВ
                self::VIEW_SAILBOATS                    => true,
                self::VIEW_SAILBOATS_FINANCE_COLUMNS    => true,
                self::VIEW_STEERING                     => true,
                self::VIEW_STEERING_FINANCE_COLUMNS     => true,
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
	 * Повертає прапорці прав для модуля реєстраторів.
	 *
	 * @return array<string,bool>
	 */
	public static function get_recorders_permissions(): array {
		return [
			'canView'     => self::current_user_can_view_recorders(),
			'canManage'   => self::current_user_can_manage_recorders(),
			'canDelete'   => self::current_user_can_delete_recorders(),
			'canProtocol' => self::current_user_can_view_recorders_protocol(),
		];
	}

	/**
	 * Повертає прапорці прав для довідника видів участі в заходах.
	 *
	 * @return array<string,bool>
	 */
	public static function get_participation_type_permissions(): array {
		return [
			'canView'     => self::current_user_can_view_participation_type(),
			'canManage'   => self::current_user_can_manage_participation_type(),
			'canDelete'   => self::current_user_can_delete_participation_type(),
			'canProtocol' => self::current_user_can_view_participation_type_protocol(),
		];
	}

	/**
	 * Повертає прапорці прав для довідника видів походів.
	 *
	 * @return array<string,bool>
	 */
	public static function get_tourtype_permissions(): array {
		$can_view = self::current_user_can_view_tourtype();

		return [
			'canView'     => $can_view,
			'canManage'   => self::current_user_can_manage_tourtype(),
			'canDelete'   => self::current_user_can_delete_tourtype(),
			'canProtocol' => self::current_user_can_view_tourtype_protocol(),
		];
	}

	/**
	 * Повертає прапорці прав для довідника категорій походів.
	 *
	 * @return array<string,bool>
	 */
	public static function get_hikingcategory_permissions(): array {
		return [
			'canView'     => self::current_user_can_view_hikingcategory(),
			'canManage'   => self::current_user_can_manage_hikingcategory(),
			'canDelete'   => self::current_user_can_delete_hikingcategory(),
			'canProtocol' => self::current_user_can_view_hikingcategory_protocol(),
		];
	}

	/**
	 * Повертає прапорці прав для довідника видів складності походів.
	 *
	 * @return array<string,bool>
	 */
	public static function get_hourcategories_permissions(): array {
		return [
			'canView'     => self::current_user_can_view_hourcategories(),
			'canManage'   => self::current_user_can_manage_hourcategories(),
			'canDelete'   => self::current_user_can_delete_hourcategories(),
			'canProtocol' => self::current_user_can_view_hourcategories_protocol(),
		];
	}

	/**
	 * Повертає прапорці прав для довідника спортивних розрядів.
	 *
	 * @return array<string,bool>
	 */
	public static function get_sportscategories_permissions(): array {
		return [
			'canView'     => self::current_user_can_view_sportscategories(),
			'canManage'   => self::current_user_can_manage_sportscategories(),
			'canDelete'   => self::current_user_can_delete_sportscategories(),
			'canProtocol' => self::current_user_can_view_sportscategories_protocol(),
		];
	}

	/**
	 * Повертає прапорці прав для довідника суддівських категорій.
	 *
	 * @return array<string,bool>
	 */
	public static function get_referee_category_permissions(): array {
		return [
			'canView'      => self::current_user_can_view_referee_category(),
			'canManage'    => self::current_user_can_manage_referee_category(),
			'canDelete'    => self::current_user_can_delete_referee_category(),
			'canProtocol'  => self::current_user_can_view_referee_category_protocol(),
			'canAdminMeta' => self::current_user_can_manage_referee_category(),
		];
	}

	/**
	 * Повертає прапорці прав для модуля реєстру членів МКК.
	 *
	 * @return array<string,bool>
	 */
	public static function get_mkk_permissions(): array {
		return [
			'canView'     => self::current_user_can_view_mkk(),
			'canManage'   => self::current_user_can_manage_mkk(),
			'canDelete'   => self::current_user_can_delete_mkk(),
			'canProtocol' => self::current_user_can_view_mkk_protocol(),
		];
	}

	/**
	 * Повертає прапорці прав для модуля складу керівних органів ФСТУ.
	 *
	 * @return array<string,bool>
	 */
	public static function get_guidance_permissions(): array {
		return [
			'canViewList' => self::current_user_can_view_guidance(),
			'canViewCard' => self::current_user_can_view_guidance_card(),
			'canManage'   => self::current_user_can_manage_guidance(),
			'canDelete'   => self::current_user_can_delete_guidance(),
			'canProtocol' => self::current_user_can_view_guidance_protocol(),
			'canViewContactsInList' => self::current_user_can_view_guidance_contacts_in_list(),
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
            'canViewSkipperBadge'  => self::current_user_can_view_skipper_badge(),
            'canViewServiceTab'    => self::current_user_can_view_steering_service_tab(),
        ];
    }

    /**
     * Чи може користувач бачити службову вкладку в картці стернового.
     */
    public static function current_user_can_view_steering_service_tab(): bool {
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        $current_user = wp_get_current_user();
        $user_roles   = $current_user instanceof \WP_User ? array_map( 'strval', (array) $current_user->roles ) : [];

        foreach ( [ 'administrator', 'sailingfinancier', 'usersail', 'sailadministrator' ] as $role ) {
            if ( in_array( $role, $user_roles, true ) ) {
                return true;
            }
        }

        return false;
    }
    /**
     * Чи може користувач бачити бейдж "Капітан" у загальному реєстрі.
     */
    public static function current_user_can_view_skipper_badge(): bool {
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        $current_user = wp_get_current_user();
        $user_roles   = $current_user instanceof \WP_User ? array_map( 'strval', (array) $current_user->roles ) : [];

        foreach ( [ 'administrator', 'sailadministrator' ] as $role ) {
            if ( in_array( $role, $user_roles, true ) ) {
                return true;
            }
        }

        return false;
    }
    /**
	 * Повертає прапорці прав для модуля посвідчень членів ФСТУ.
	 *
	 * @return array<string,bool>
	 */
	public static function get_member_card_applications_permissions(): array {
		$can_self = self::current_user_can_self_manage_member_card_applications();

		return [
			'canView'             => self::current_user_can_view_member_card_applications(),
			'canManage'           => self::current_user_can_manage_member_card_applications(),
			'canDelete'           => self::current_user_can_delete_member_card_applications(),
			'canProtocol'         => self::current_user_can_view_member_card_applications_protocol(),
			'canSelfService'      => $can_self,
			'canReissue'          => self::current_user_can_reissue_member_card_applications(),
			'canUpdatePhoto'      => self::current_user_can_update_member_card_applications_photo(),
			'canManageCardNumber' => self::current_user_can_manage_member_card_number(),
		];
	}

	/**
	 * Повертає прапорці прав для модуля Calendar.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_calendar_permissions(): array {
		return [
			'canView'      => self::current_user_can_view_calendar(),
			'events'       => self::get_calendar_events_permissions(),
			'applications' => self::get_calendar_applications_permissions(),
			'routes'       => self::get_calendar_routes_permissions(),
			'results'      => self::get_calendar_results_permissions(),
			'canReviewMkk' => self::current_user_can_review_calendar_mkk(),
			'isGuest'      => ! is_user_logged_in(),
		];
	}

	/**
	 * Повертає прапорці прав для Calendar_Events.
	 *
	 * @return array<string,bool>
	 */
	public static function get_calendar_events_permissions(): array {
		return [
			'canView'      => self::current_user_can_view_calendar(),
			'canManage'    => self::current_user_can_manage_calendar_events(),
			'canManageAny' => self::current_user_can_manage_any_calendar_events(),
			'canDelete'    => self::current_user_can_delete_calendar_events(),
			'canProtocol'  => self::current_user_can_view_calendar_events_protocol(),
		];
	}

	/**
	 * Повертає прапорці прав для Calendar_Applications.
	 *
	 * @return array<string,bool>
	 */
	public static function get_calendar_applications_permissions(): array {
		return [
			'canSubmit'   => self::current_user_can_submit_calendar_applications(),
			'canManage'   => self::current_user_can_manage_calendar_applications(),
			'canDelete'   => self::current_user_can_delete_calendar_applications(),
			'canProtocol' => self::current_user_can_view_calendar_applications_protocol(),
		];
	}

	/**
	 * Повертає прапорці прав для Calendar_Routes.
	 *
	 * @return array<string,bool>
	 */
	public static function get_calendar_routes_permissions(): array {
		return [
			'canManage'   => self::current_user_can_manage_calendar_routes(),
			'canDelete'   => self::current_user_can_delete_calendar_routes(),
			'canProtocol' => self::current_user_can_view_calendar_routes_protocol(),
			'canReviewMkk'=> self::current_user_can_review_calendar_mkk(),
		];
	}

	/**
	 * Повертає прапорці прав для Calendar_Results.
	 *
	 * @return array<string,bool>
	 */
	public static function get_calendar_results_permissions(): array {
		return [
			'canView'     => self::current_user_can_view_calendar_results(),
			'canManage'   => self::current_user_can_manage_calendar_results(),
			'canDelete'   => self::current_user_can_delete_calendar_results(),
			'canProtocol' => self::current_user_can_view_calendar_results_protocol(),
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
	 * Чи може користувач переглядати модуль посвідчень членів ФСТУ.
	 */
	public static function current_user_can_view_member_card_applications(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_MEMBER_CARD_APPLICATIONS );
	}

	/**
	 * Чи може користувач керувати посвідченнями членів ФСТУ.
	 */
	public static function current_user_can_manage_member_card_applications(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_MEMBER_CARD_APPLICATIONS );
	}

	/**
	 * Чи може користувач видаляти посвідчення членів ФСТУ.
	 */
	public static function current_user_can_delete_member_card_applications(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::DELETE_MEMBER_CARD_APPLICATIONS );
	}

	/**
	 * Чи може користувач переглядати протокол посвідчень членів ФСТУ.
	 */
	public static function current_user_can_view_member_card_applications_protocol(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_MEMBER_CARD_APPLICATIONS_PROTOCOL );
	}

	/**
	 * Чи може користувач працювати зі своїм посвідченням у self-service режимі.
	 */
	public static function current_user_can_self_manage_member_card_applications(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::SELF_MANAGE_MEMBER_CARD_APPLICATIONS );
	}

	/**
	 * Чи може користувач виконувати перевипуск посвідчення.
	 */
	public static function current_user_can_reissue_member_card_applications(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::REISSUE_MEMBER_CARD_APPLICATIONS );
	}

	/**
	 * Чи може користувач оновлювати фото посвідчення.
	 */
	public static function current_user_can_update_member_card_applications_photo(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::UPDATE_MEMBER_CARD_APPLICATIONS_PHOTO );
	}

	/**
	 * Чи може користувач змінювати номер картки вручну.
	 */
	public static function current_user_can_manage_member_card_number(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_MEMBER_CARD_NUMBER );
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
        return true; // КРИТИЧНА ЗМІНА: Дозволяємо перегляд усім (гостям та авторизованим)
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
	 * Чи може користувач переглядати модуль реєстраторів.
	 */
	public static function current_user_can_view_recorders(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_RECORDERS );
	}

	/**
	 * Чи може користувач переглядати довідник видів участі в заходах.
	 */
	public static function current_user_can_view_participation_type(): bool {
		return is_user_logged_in() || current_user_can( self::VIEW_PARTICIPATION_TYPE );
	}

	/**
	 * Чи може користувач керувати довідником видів участі в заходах.
	 */
	public static function current_user_can_manage_participation_type(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_PARTICIPATION_TYPE );
	}

	/**
	 * Чи може користувач видаляти записи довідника видів участі в заходах.
	 */
	public static function current_user_can_delete_participation_type(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::DELETE_PARTICIPATION_TYPE );
	}

	/**
	 * Чи може користувач переглядати протокол довідника видів участі в заходах.
	 */
	public static function current_user_can_view_participation_type_protocol(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_PARTICIPATION_TYPE_PROTOCOL );
	}

	/**
	 * Чи може користувач переглядати довідник видів походів.
	 */
	public static function current_user_can_view_tourtype(): bool {
		return true;
	}

	/**
	 * Чи може користувач переглядати довідник категорій походів.
	 */
	public static function current_user_can_view_hikingcategory(): bool {
		return true;
	}

	/**
	 * Чи може користувач переглядати довідник видів складності походів.
	 */
	public static function current_user_can_view_hourcategories(): bool {
		return true;
	}

	/**
	 * Чи може користувач переглядати довідник спортивних розрядів.
	 */
	public static function current_user_can_view_sportscategories(): bool {
		return true;
	}

	/**
	 * Чи може користувач переглядати довідник суддівських категорій.
	 */
	public static function current_user_can_view_referee_category(): bool {
		return true;
	}

	/**
	 * Чи може користувач керувати довідником видів походів.
	 */
	public static function current_user_can_manage_tourtype(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_TOURTYPE );
	}

	/**
	 * Чи може користувач керувати довідником категорій походів.
	 */
	public static function current_user_can_manage_hikingcategory(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_HIKING_CATEGORY );
	}

	/**
	 * Чи може користувач керувати довідником видів складності походів.
	 */
	public static function current_user_can_manage_hourcategories(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_HOURCATEGORIES );
	}

	/**
	 * Чи може користувач керувати довідником спортивних розрядів.
	 */
	public static function current_user_can_manage_sportscategories(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_SPORTSCATEGORIES );
	}

	/**
	 * Чи може користувач керувати довідником суддівських категорій.
	 */
	public static function current_user_can_manage_referee_category(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_REFEREE_CATEGORY );
	}

	/**
	 * Чи може користувач видаляти записи довідника видів походів.
	 */
	public static function current_user_can_delete_tourtype(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::DELETE_TOURTYPE );
	}

	/**
	 * Чи може користувач видаляти записи довідника категорій походів.
	 */
	public static function current_user_can_delete_hikingcategory(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::DELETE_HIKING_CATEGORY );
	}

	/**
	 * Чи може користувач видаляти записи довідника видів складності походів.
	 */
	public static function current_user_can_delete_hourcategories(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::DELETE_HOURCATEGORIES );
	}

	/**
	 * Чи може користувач видаляти записи довідника спортивних розрядів.
	 */
	public static function current_user_can_delete_sportscategories(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::DELETE_SPORTSCATEGORIES );
	}

	/**
	 * Чи може користувач видаляти записи довідника суддівських категорій.
	 */
	public static function current_user_can_delete_referee_category(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::DELETE_REFEREE_CATEGORY );
	}

	/**
	 * Чи може користувач переглядати протокол довідника видів походів.
	 */
	public static function current_user_can_view_tourtype_protocol(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_TOURTYPE_PROTOCOL );
	}

	/**
	 * Чи може користувач переглядати протокол довідника категорій походів.
	 */
	public static function current_user_can_view_hikingcategory_protocol(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_HIKING_CATEGORY_PROTOCOL );
	}

	/**
	 * Чи може користувач переглядати протокол довідника видів складності походів.
	 */
	public static function current_user_can_view_hourcategories_protocol(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_HOURCATEGORIES_PROTOCOL );
	}

	/**
	 * Чи може користувач переглядати протокол довідника спортивних розрядів.
	 */
	public static function current_user_can_view_sportscategories_protocol(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_SPORTSCATEGORIES_PROTOCOL );
	}

	/**
	 * Чи може користувач переглядати протокол довідника суддівських категорій.
	 */
	public static function current_user_can_view_referee_category_protocol(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_REFEREE_CATEGORY_PROTOCOL );
	}

	/**
	 * Чи може користувач керувати модулем реєстраторів.
	 */
	public static function current_user_can_manage_recorders(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_RECORDERS );
	}

	/**
	 * Чи може користувач видаляти записи модуля реєстраторів.
	 */
	public static function current_user_can_delete_recorders(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::DELETE_RECORDERS );
	}

	/**
	 * Чи може користувач переглядати протокол модуля реєстраторів.
	 */
	public static function current_user_can_view_recorders_protocol(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_RECORDERS_PROTOCOL );
	}

	/**
	 * Чи може користувач переглядати реєстр членів МКК.
	 */
	public static function current_user_can_view_mkk(): bool {
		return true;
	}

	/**
	 * Чи може користувач керувати реєстром членів МКК.
	 */
	public static function current_user_can_manage_mkk(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_MKK );
	}

	/**
	 * Чи може користувач видаляти записи реєстру членів МКК.
	 */
	public static function current_user_can_delete_mkk(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::DELETE_MKK );
	}

	/**
	 * Чи може користувач переглядати протокол реєстру членів МКК.
	 */
	public static function current_user_can_view_mkk_protocol(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_MKK_PROTOCOL );
	}

	/**
	 * Чи може користувач переглядати список складу керівних органів.
	 */
	public static function current_user_can_view_guidance(): bool {
		return true;
	}

	/**
	 * Чи може користувач переглядати картку запису Guidance.
	 */
	public static function current_user_can_view_guidance_card(): bool {
		return is_user_logged_in() || current_user_can( self::VIEW_GUIDANCE_CARD );
	}

	/**
	 * Чи може користувач керувати записами Guidance.
	 */
	public static function current_user_can_manage_guidance(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_GUIDANCE );
	}

	/**
	 * Чи може користувач видаляти записи Guidance.
	 */
	public static function current_user_can_delete_guidance(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::DELETE_GUIDANCE );
	}

	/**
	 * Чи може користувач переглядати протокол модуля Guidance.
	 */
	public static function current_user_can_view_guidance_protocol(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_GUIDANCE_PROTOCOL );
	}

	/**
	 * Чи може користувач бачити телефони та email у таблиці Guidance.
	 */
	public static function current_user_can_view_guidance_contacts_in_list(): bool {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$current_user = wp_get_current_user();
		$user_roles   = $current_user instanceof \WP_User ? array_map( 'strval', (array) $current_user->roles ) : [];

		foreach ( [ 'administrator', 'globalregistrar', 'userregistrar' ] as $role ) {
			if ( in_array( $role, $user_roles, true ) ) {
				return true;
			}
		}

		return false;
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

	/**
	 * Чи може користувач переглядати модуль Calendar.
	 */
	public static function current_user_can_view_calendar(): bool {
		return true;
	}

	/**
	 * Чи може користувач керувати власними/доступними подіями Calendar.
	 */
	public static function current_user_can_manage_calendar_events(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_CALENDAR_EVENTS );
	}

	/**
	 * Чи може користувач керувати будь-якими подіями Calendar.
	 */
	public static function current_user_can_manage_any_calendar_events(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_ANY_CALENDAR_EVENTS );
	}

	/**
	 * Чи може користувач видаляти події Calendar без owner-обмеження.
	 */
	public static function current_user_can_delete_calendar_events(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::DELETE_CALENDAR_EVENTS );
	}

	/**
	 * Чи може користувач переглядати протокол подій Calendar.
	 */
	public static function current_user_can_view_calendar_events_protocol(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_CALENDAR_EVENTS_PROTOCOL );
	}

	/**
	 * Чи може користувач подавати заявки Calendar.
	 */
	public static function current_user_can_submit_calendar_applications(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::SUBMIT_CALENDAR_APPLICATIONS );
	}

	/**
	 * Чи може користувач керувати заявками Calendar.
	 */
	public static function current_user_can_manage_calendar_applications(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_CALENDAR_APPLICATIONS );
	}

	/**
	 * Чи може користувач видаляти заявки Calendar.
	 */
	public static function current_user_can_delete_calendar_applications(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::DELETE_CALENDAR_APPLICATIONS );
	}

	/**
	 * Чи може користувач переглядати протокол заявок Calendar.
	 */
	public static function current_user_can_view_calendar_applications_protocol(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_CALENDAR_APPLICATIONS_PROTOCOL );
	}

	/**
	 * Чи може користувач керувати маршрутами Calendar.
	 */
	public static function current_user_can_manage_calendar_routes(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_CALENDAR_ROUTES );
	}

	/**
	 * Чи може користувач видаляти маршрути Calendar.
	 */
	public static function current_user_can_delete_calendar_routes(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::DELETE_CALENDAR_ROUTES );
	}

	/**
	 * Чи може користувач переглядати протокол маршрутів Calendar.
	 */
	public static function current_user_can_view_calendar_routes_protocol(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_CALENDAR_ROUTES_PROTOCOL );
	}

	/**
	 * Чи може користувач виконувати MKK-review для Calendar.
	 */
	public static function current_user_can_review_calendar_mkk(): bool {
		return current_user_can( 'manage_options' )
			|| current_user_can( self::REVIEW_CALENDAR_MKK )
			|| current_user_can( self::MANAGE_MKK );
	}

	/**
	 * Чи може користувач переглядати результати Calendar.
	 */
	public static function current_user_can_view_calendar_results(): bool {
		return true;
	}

	/**
	 * Чи може користувач керувати результатами Calendar.
	 */
	public static function current_user_can_manage_calendar_results(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_CALENDAR_RESULTS );
	}

	/**
	 * Чи може користувач видаляти результати Calendar.
	 */
	public static function current_user_can_delete_calendar_results(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::DELETE_CALENDAR_RESULTS );
	}

	/**
	 * Чи може користувач переглядати протокол результатів Calendar.
	 */
	public static function current_user_can_view_calendar_results_protocol(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( self::VIEW_CALENDAR_RESULTS_PROTOCOL );
	}
    /**
     * Повертає прапорці прав для довідника типів членських білетів.
     *
     * @return array<string,bool>
     */
    public static function get_type_card_permissions(): array {
        return [
            'canView'     => current_user_can( 'manage_options' ) || current_user_can( self::VIEW_TYPE_CARD ),
            'canManage'   => current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_TYPE_CARD ),
            'canDelete'   => current_user_can( 'manage_options' ) || current_user_can( self::DELETE_TYPE_CARD ),
            'canProtocol' => current_user_can( 'manage_options' ) || current_user_can( self::VIEW_TYPE_CARD_PROTOCOL ),
        ];
    }
    /**
     * Повертає прапорці прав для довідника типів вітрильних залікових груп.
     */
    public static function get_sail_group_type_permissions(): array {
        return [
            'canView'     => current_user_can( 'manage_options' ) || current_user_can( self::VIEW_SAIL_GROUP_TYPE ),
            'canManage'   => current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_SAIL_GROUP_TYPE ),
            'canDelete'   => current_user_can( 'manage_options' ) || current_user_can( self::DELETE_SAIL_GROUP_TYPE ),
            'canProtocol' => current_user_can( 'manage_options' ) || current_user_can( self::VIEW_SAIL_GROUP_TYPE_PROTOCOL ),
        ];
    }
    /**
     * Повертає прапорці прав для модуля Виробники та типи суден.
     * @return array<string,bool>
     */
    public static function get_type_boat_permissions(): array {
        return [
            'canView'     => true, // Публічний доступ для всіх
            'canManage'   => current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_TYPE_BOAT ),
            'canDelete'   => current_user_can( 'manage_options' ) || current_user_can( self::DELETE_TYPE_BOAT ),
            'canProtocol' => current_user_can( 'manage_options' ) || current_user_can( self::VIEW_TYPE_BOAT_PROTOCOL ),
        ];
    }
    /**
     * Повертає прапорці прав для модуля Directory (Виконком та Опитування).
     *
     * @return array<string,bool>
     */
    public static function get_directory_permissions(): array {
        return [
            'canViewList' => current_user_can( 'manage_options' ) || current_user_can( self::VIEW_DIRECTORY ),
            'canSubmit'   => current_user_can( 'manage_options' ) || current_user_can( self::SUBMIT_DIRECTORY_POLL ),
            'canManage'   => current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_DIRECTORY ),
            'canDelete'   => current_user_can( 'manage_options' ) || current_user_can( self::DELETE_DIRECTORY ),
            'canProtocol' => current_user_can( 'manage_options' ) || current_user_can( self::VIEW_DIRECTORY_PROTOCOL ),
            'canViewContactsInList' => current_user_can( 'manage_options' ) || current_user_can( self::MANAGE_DIRECTORY ),
        ];
    }
    //----------
}
