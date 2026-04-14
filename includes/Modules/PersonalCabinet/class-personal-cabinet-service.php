<?php
namespace FSTU\Modules\PersonalCabinet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Бізнес-сервіс модуля «Особистий кабінет ФСТУ».
 *
 * Version:     1.14.1
 * Date_update: 2026-04-14
 *
 * @package FSTU\Modules\PersonalCabinet
 */
class Personal_Cabinet_Service {

	private Personal_Cabinet_Repository $repository;
	private Personal_Cabinet_Protocol_Service $protocol_service;
	private const DUES_TYPE_ID = 1;

	public function __construct( ?Personal_Cabinet_Protocol_Service $protocol_service = null, ?Personal_Cabinet_Repository $repository = null ) {
		$this->repository       = $repository ?? new Personal_Cabinet_Repository();
		$this->protocol_service = $protocol_service ?? new Personal_Cabinet_Protocol_Service();
	}

	/**
	 * Геттер для сервісу протоколу.
	 * Дозволяє іншим класам (наприклад, AJAX) використовувати логування через цей сервіс.
	 */
	public function get_protocol_service(): Personal_Cabinet_Protocol_Service {
		return $this->protocol_service;
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_initial_profile_summary( int $user_id, array $permissions = [] ): array {
		$user = get_userdata( $user_id );
		$can_view_member_data = ! empty( $permissions['canViewPrivate'] ) || ! empty( $permissions['canManage'] ) || ! empty( $permissions['isOwner'] );
		$can_view_roles       = ! empty( $permissions['canViewService'] ) || ! empty( $permissions['canManage'] ) || ! empty( $permissions['isOwner'] );

		if ( ! ( $user instanceof \WP_User ) ) {
			return [
				'userId'      => 0,
				'displayName' => '',
				'email'       => '',
				'roles'       => '',
			];
		}

		return [
			'userId'      => $user_id,
			'displayName' => trim( (string) get_user_meta( $user_id, 'last_name', true ) . ' ' . (string) get_user_meta( $user_id, 'first_name', true ) . ' ' . (string) get_user_meta( $user_id, 'Patronymic', true ) ),
			'email'       => $can_view_member_data ? (string) $user->user_email : '',
			'roles'       => $can_view_roles ? implode( ', ', array_map( 'strval', (array) $user->roles ) ) : '',
		];
	}

	/**
	 * @param array<string,bool> $permissions
	 * @return array<string,mixed>
	 */
	public function get_profile_payload( int $user_id, array $permissions ): array {
		$user = get_userdata( $user_id );

		if ( ! ( $user instanceof \WP_User ) ) {
			return [
				'profile' => [],
				'tabs'    => [],
			];
		}

		$display_name = trim( implode( ' ', array_filter( [
			(string) get_user_meta( $user_id, 'last_name', true ),
			(string) get_user_meta( $user_id, 'first_name', true ),
			(string) get_user_meta( $user_id, 'Patronymic', true ),
		] ) ) );

		if ( '' === $display_name ) {
			$display_name = (string) $user->display_name;
		}

		$context      = $this->repository->get_profile_context( $user_id );
		$clubs        = $this->repository->get_user_clubs( $user_id );
		$cities       = $this->repository->get_user_cities( $user_id );
		$units        = $this->repository->get_user_units( $user_id );
		$tourism      = $this->repository->get_user_tourism_types( $user_id );
		$experience   = $this->repository->get_user_experience( $user_id );
		// --- ВИПРАВЛЕННЯ БАГА СТАРОЇ СИСТЕМИ ---
		// Репозиторій тягнув розряди тільки через UserCalendar, ігноруючи ручні.
		// Робимо правильний запит, що захоплює абсолютно всі розряди:
		global $wpdb;
		$ranks = $wpdb->get_results( $wpdb->prepare( "
			SELECT s.*, 
			       c.Calendar_ID, c.Calendar_Name, c.Calendar_DateBegin, c.Calendar_DateEnd
			FROM vUserSportCategories s
			LEFT JOIN UserCalendar u ON s.UserCalendar_ID = u.UserCalendar_ID
			LEFT JOIN Calendar c ON c.Calendar_ID = u.Calendar_ID
			WHERE s.User_ID = %d
			ORDER BY s.UserSportCategories_DatePrikaz DESC
		", $user_id ), ARRAY_A );
		
		if ( ! is_array( $ranks ) ) {
			$ranks = [];
		}
		// ---------------------------------------
		$judging      = $this->repository->get_user_judging( $user_id );
		$dues         = $this->repository->get_user_dues( $user_id );
		$dues_sail    = $this->repository->get_user_dues_sail( $user_id );
		$vessels      = $this->repository->get_user_vessels( $user_id );
		$certs        = $this->repository->get_user_sailing_certificates( $user_id );
		$unit_payment = $this->repository->get_user_unit_payment_info( $user_id );
		$settings     = $this->repository->get_settings_values(
			[
				'AnnualFee',
				'PaymentRegistration',
				'NameSailboatFinancier',
				'FinancierCardNumber',
				'UrlPayFinancierCard',
				'UrlSailboatFinancier',
				'CardNumberToPayDocuments',
				'UrlPayDocuments',
			]
		);
		$phones       = array_values( array_filter( [
			(string) get_user_meta( $user_id, 'PhoneMobile', true ),
			(string) get_user_meta( $user_id, 'Phone2', true ),
			(string) get_user_meta( $user_id, 'Phone3', true ),
		] ) );
		$facebook_raw = (string) get_user_meta( $user_id, 'FaceBook', true );
		$city_name    = isset( $context['city']['City_Name'] ) ? (string) $context['city']['City_Name'] : '';
		$region_name  = isset( $context['city']['Region_Name'] ) ? (string) $context['city']['Region_Name'] : '';
		$can_view_private = ! empty( $permissions['canViewPrivate'] );
		$can_view_service = ! empty( $permissions['canViewService'] );
		$can_view_member_data = $can_view_private || ! empty( $permissions['canManage'] ) || ! empty( $permissions['isOwner'] );

		// Логіка прав для редагування
		$is_owner   = ! empty( $permissions['isOwner'] );
		$is_admin   = current_user_can( 'administrator' );
		$is_global  = current_user_can( 'globalregistrar' );
		$is_userreg = current_user_can( 'userregistrar' );

		$can_edit_full    = $is_owner || $is_admin || $is_global;
		$can_edit_partial = $can_edit_full || $is_userreg;

		$edit_schema = [
			'general' => [
				[ 'key' => 'last_name', 'label' => 'Прізвище', 'value' => (string) get_user_meta( $user_id, 'last_name', true ), 'readonly' => ! $can_edit_partial ],
				[ 'key' => 'first_name', 'label' => 'Ім\'я', 'value' => (string) get_user_meta( $user_id, 'first_name', true ), 'readonly' => ! $can_edit_partial ],
				[ 'key' => 'Patronymic', 'label' => 'По батькові', 'value' => (string) get_user_meta( $user_id, 'Patronymic', true ), 'readonly' => ! $can_edit_partial ],
				[ 'key' => 'Sex', 'label' => 'Стать (Ж/Ч)', 'value' => (string) get_user_meta( $user_id, 'Sex', true ), 'readonly' => ! $can_edit_partial, 'type' => 'toggle_sex' ],
				[ 'key' => 'BirthDate', 'label' => 'Дата народження', 'value' => gmdate( 'Y-m-d', strtotime( get_user_meta( $user_id, 'BirthDate', true ) ?: 'now' ) ), 'readonly' => ! $can_edit_partial, 'type' => 'date' ],
				[ 'key' => 'user_email', 'label' => 'Email', 'value' => $user->user_email, 'readonly' => ! $can_edit_partial, 'type' => 'email' ],
				[ 'key' => 'PhoneMobile', 'label' => 'Телефон', 'value' => (string) get_user_meta( $user_id, 'PhoneMobile', true ), 'readonly' => ! $can_edit_partial, 'type' => 'tel' ],
				[ 'key' => 'Skype', 'label' => 'Skype', 'value' => (string) get_user_meta( $user_id, 'Skype', true ), 'readonly' => ! $can_edit_partial ],
				[ 'key' => 'FaceBook', 'label' => 'Facebook', 'value' => (string) get_user_meta( $user_id, 'FaceBook', true ), 'readonly' => ! $can_edit_partial ],
			],
			'private' => [
				[ 'key' => 'Adr', 'label' => 'Адреса проживання', 'value' => (string) get_user_meta( $user_id, 'Adr', true ), 'readonly' => ! $can_edit_partial ],
				[ 'key' => 'Job', 'label' => 'Місце роботи, посада', 'value' => (string) get_user_meta( $user_id, 'Job', true ), 'readonly' => ! $can_edit_partial ],
				[ 'key' => 'Education', 'label' => 'Освіта', 'value' => (string) get_user_meta( $user_id, 'Education', true ), 'readonly' => ! $can_edit_partial ],
				[ 'key' => 'Phone2', 'label' => 'Дод. телефон 1', 'value' => (string) get_user_meta( $user_id, 'Phone2', true ), 'readonly' => ! $can_edit_partial, 'type' => 'tel' ],
				[ 'key' => 'Phone3', 'label' => 'Дод. телефон 2', 'value' => (string) get_user_meta( $user_id, 'Phone3', true ), 'readonly' => ! $can_edit_partial, 'type' => 'tel' ],
				[ 'key' => 'PhoneFamily', 'label' => 'Телефон родичів', 'value' => (string) get_user_meta( $user_id, 'PhoneFamily', true ), 'readonly' => ! $can_edit_partial, 'type' => 'tel' ],
			],
			'service' => [
				// Рядок 1
				[ 'key' => 'IPN', 'label' => 'ІПН', 'value' => isset($context['user_params']['IPN']) ? $context['user_params']['IPN'] : '', 'readonly' => ! $can_edit_full, 'col' => 'left' ],
				[ 'key' => 'sys_id', 'label' => 'User ID', 'value' => $user_id, 'readonly' => true, 'col' => 'right' ],
				
				// Рядок 2
				[ 'key' => 'BankName', 'label' => 'Назва банку', 'value' => isset($context['user_params']['BankName']) ? $context['user_params']['BankName'] : '', 'readonly' => ! $can_edit_full, 'col' => 'left' ],
				[ 'key' => 'sys_reg', 'label' => 'Дата реєстрації', 'value' => $this->format_date((string)$user->user_registered), 'readonly' => true, 'col' => 'right' ],
				
				// Рядок 3 (Тепер IBAN та Login стоять в один рядок)
				[ 'key' => 'IBAN', 'label' => 'IBAN', 'value' => isset($context['user_params']['IBAN']) ? $context['user_params']['IBAN'] : '', 'readonly' => ! $can_edit_full, 'col' => 'left' ],
				[ 'key' => 'sys_login', 'label' => 'Login', 'value' => $user->user_login, 'readonly' => true, 'col' => 'right' ],
				
				// Рядок 4
				[ 'key' => 'TelegramID', 'label' => 'Telegram ID', 'value' => isset($context['telegram_id']) ? $context['telegram_id'] : '', 'readonly' => ! $can_edit_full, 'col' => 'left' ],
				[ 'key' => 'sys_last', 'label' => 'Останній вхід', 'value' => $this->format_date((string)get_user_meta( $user_id, 'last_login', true )), 'readonly' => true, 'col' => 'right' ],
				
				// Рядок 5
				[ 'key' => 'VerificationCode', 'label' => 'VerificationCode', 'value' => isset($context['verification_code']) ? $context['verification_code'] : '', 'readonly' => ! $can_edit_full, 'col' => 'left' ],
				[ 'key' => 'sys_roles', 'label' => 'Ролі', 'value' => implode( ', ', (array) $user->roles ), 'readonly' => true, 'type' => 'roles', 'col' => 'right' ],
				
				// Рядок 6
				[ 'key' => 'TelegramVerification', 'label' => 'Активація Telegram', 'value' => isset($user->TelegramVerification) ? $user->TelegramVerification : '', 'readonly' => ! $can_edit_full, 'col' => 'left', 'type' => 'toggle_bool' ],
				
				// Рядки 7, 8, 9 (Тільки зліва під Telegram, права колонка порожня під ролями)
				[ 'key' => 'sys_card1', 'label' => 'Тип картки', 'value' => isset($member_card['TypeCard_Name']) ? $member_card['TypeCard_Name'] : '', 'readonly' => true, 'col' => 'left' ],
				[ 'key' => 'sys_card2', 'label' => 'Статус', 'value' => isset($member_card['StatusCard_Name']) ? $member_card['StatusCard_Name'] : '', 'readonly' => true, 'col' => 'left' ],
				[ 'key' => 'sys_card3', 'label' => 'Сума', 'value' => $this->normalize_sum(isset($member_card['UserMemberCard_Summa']) ? (string)$member_card['UserMemberCard_Summa'] : ''), 'readonly' => true, 'col' => 'left' ],
			]
		];

		$profile = [
			'userId'      => $user_id,
			'isOwnProfile' => ! empty( $permissions['isOwner'] ),
			'isGuestView' => ! is_user_logged_in(),
			'displayName' => $display_name,
			'email'       => $can_view_member_data ? (string) $user->user_email : '',
			'roles'       => $can_view_service ? implode( ', ', array_map( 'strval', (array) $user->roles ) ) : '',
			'photoUrl'    => $this->get_photo_url( $user_id ),
			'canEditProfile' => $can_edit_partial,
			'editSchema'     => $edit_schema,
			'hasConsent'  => $can_view_member_data ? $this->has_member_consent( $user_id ) : false,
			'phone'       => $can_view_member_data ? (string) get_user_meta( $user_id, 'PhoneMobile', true ) : '',
			'phoneList'   => $can_view_member_data ? $phones : [],
			'birthDate'   => $can_view_member_data ? (string) get_user_meta( $user_id, 'BirthDate', true ) : '',
			'sex'         => (string) get_user_meta( $user_id, 'Sex', true ),
			'skype'       => $can_view_member_data ? (string) get_user_meta( $user_id, 'Skype', true ) : '',
			'facebook'    => $can_view_member_data ? $this->normalize_facebook_url( $facebook_raw ) : '',
			'facebookRaw' => $can_view_member_data ? $facebook_raw : '',
			'city'        => $city_name,
			'region'      => $region_name,
			'cityRegion'  => trim( $city_name . ( '' !== $region_name ? ' (' . $region_name . ')' : '' ) ),
			'ofstName'    => isset( $context['ofst']['Region_Name'] ) ? (string) $context['ofst']['Region_Name'] : '',
			'memberCard'  => isset( $context['member_card'] ) && is_array( $context['member_card'] ) ? $context['member_card'] : [],
			'address'     => $can_view_private ? (string) get_user_meta( $user_id, 'Adr', true ) : '',
			'job'         => $can_view_private ? (string) get_user_meta( $user_id, 'Job', true ) : '',
			'education'   => $can_view_private ? (string) get_user_meta( $user_id, 'Education', true ) : '',
			'phone2'      => $can_view_private ? (string) get_user_meta( $user_id, 'Phone2', true ) : '',
			'phone3'      => $can_view_private ? (string) get_user_meta( $user_id, 'Phone3', true ) : '',
			'familyPhone' => $can_view_private ? (string) get_user_meta( $user_id, 'PhoneFamily', true ) : '',
			'login'       => $can_view_service ? (string) $user->user_login : '',
			'registered'  => $can_view_service ? (string) $user->user_registered : '',
			'lastLogin'   => $can_view_service ? (string) get_user_meta( $user_id, 'last_login', true ) : '',
			'telegramActive' => $can_view_service && isset( $user->TelegramVerification ) ? (string) $user->TelegramVerification : '',
			'telegramId'     => $can_view_service && isset( $context['telegram_id'] ) ? (string) $context['telegram_id'] : '',
			'telegramCode'   => $can_view_service && isset( $context['verification_code'] ) ? (string) $context['verification_code'] : '',
			'ipn'            => $can_view_service && isset( $context['user_params']['IPN'] ) ? (string) $context['user_params']['IPN'] : '',
			'bankName'       => $can_view_service && isset( $context['user_params']['BankName'] ) ? (string) $context['user_params']['BankName'] : '',
			'iban'           => $can_view_service && isset( $context['user_params']['IBAN'] ) ? (string) $context['user_params']['IBAN'] : '',
		];

		return [
			'profile' => $profile,
			'tabs'    => $this->get_tabs_payload(
				$profile,
				$permissions,
				[
					'clubs'   => $clubs,
					'cities'  => $cities,
					'units'   => $units,
					'tourism' => $tourism,
					'experience' => $experience,
					'ranks'      => $ranks,
					'judging'    => $judging,
					'dues'       => $dues,
					'dues_sail'  => $dues_sail,
					'vessels'    => $vessels,
					'certs'      => $certs,
					'settings'   => $settings,
					'unit_payment' => $unit_payment,
				]
			),
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_protocol_payload( string $search, int $page, int $per_page ): array {
		return $this->protocol_service->get_protocol_payload( $search, $page, $per_page );
	}

	/**
	 * @param array<string,bool> $permissions
	 * @return array<string,mixed>|\WP_Error
	 */
	public function save_dues_receipt( int $profile_user_id, array $permissions, int $year_id, float $summa, string $url ): array|\WP_Error {
		if ( $profile_user_id <= 0 || ! ( get_userdata( $profile_user_id ) instanceof \WP_User ) ) {
			return new \WP_Error( 'invalid_user', __( 'Профіль користувача не знайдено.', 'fstu' ) );
		}

		if ( empty( $permissions['canManageDues'] ) ) {
			return new \WP_Error( 'forbidden', __( 'Немає прав для додавання квитанції.', 'fstu' ) );
		}

		$current_year = (int) current_time( 'Y' );
		if ( $year_id < 2000 || $year_id > ( $current_year + 1 ) ) {
			return new \WP_Error( 'invalid_year', __( 'Вкажіть коректний рік внеску.', 'fstu' ) );
		}

		if ( $summa <= 0 ) {
			return new \WP_Error( 'invalid_sum', __( 'Сума внеску повинна бути більшою за нуль.', 'fstu' ) );
		}

		$url = trim( $url );
		if ( '' === $url || ! wp_http_validate_url( $url ) ) {
			return new \WP_Error( 'invalid_url', __( 'Вкажіть коректне повне посилання на квитанцію.', 'fstu' ) );
		}

		if ( $this->repository->has_due_for_year( $profile_user_id, $year_id, self::DUES_TYPE_ID ) ) {
			return new \WP_Error( 'duplicate_due', __( 'Внесок за вказаний рік уже існує.', 'fstu' ) );
		}

		global $wpdb;

		$actor_user_id = get_current_user_id();
		$wpdb->query( 'START TRANSACTION' );

		$inserted = $wpdb->insert(
			'Dues',
			[
				'Dues_DateCreate' => current_time( 'mysql' ),
				'Dues_URL'        => $url,
				'UserCreate'      => $actor_user_id,
				'User_ID'         => $profile_user_id,
				'Dues_Summa'      => round( $summa, 2 ),
				'DuesType_ID'     => self::DUES_TYPE_ID,
				'Year_ID'         => $year_id,
			],
			[ '%s', '%s', '%d', '%d', '%f', '%d', '%d' ]
		);

		if ( false === $inserted ) {
			$wpdb->query( 'ROLLBACK' );

			return new \WP_Error( 'db_error', __( 'Сталася помилка збереження квитанції.', 'fstu' ) );
		}

		$log_payload = $this->protocol_service->get_log_insert_payload(
			$actor_user_id,
			'I',
			sprintf( 'Додано квитанцію про сплату членського внеску за %d рік для користувача ID %d.', $year_id, $profile_user_id ),
			'receipt_uploaded'
		);

		$log_inserted = $wpdb->insert( 'Logs', $log_payload['data'], $log_payload['format'] );

		if ( false === $log_inserted ) {
			$wpdb->query( 'ROLLBACK' );

			return new \WP_Error( 'log_error', __( 'Сталася помилка фіксації квитанції у протоколі.', 'fstu' ) );
		}

		$wpdb->query( 'COMMIT' );

		return [
			'message' => __( 'Квитанцію успішно додано. Запис передано на перевірку.', 'fstu' ),
		];
	}

	/**
	 * @param array<string,mixed> $profile
	 * @param array<string,bool>  $permissions
	 * @return array<string,mixed>
	 */
	private function get_tabs_payload( array $profile, array $permissions, array $collections = [] ): array {
		global $wpdb;
		$profile_user_id = isset( $profile['userId'] ) ? (int) $profile['userId'] : 0;
		$user = get_userdata( $profile_user_id );

		// Ініціалізуємо всі права доступу на самому початку, щоб уникнути PHP помилок
		$can_view_private      = ! empty( $permissions['canViewPrivate'] );
		$can_view_member_data  = $can_view_private || ! empty( $permissions['canManage'] ) || ! empty( $permissions['isOwner'] );
		$is_owner              = ! empty( $permissions['isOwner'] );
		$can_edit_profile      = ! empty( $permissions['canEditProfile'] );
		$can_manage_clubs      = ! empty( $permissions['canManageClubs'] );
		$can_manage_cities     = ! empty( $permissions['canManageCities'] );
		$can_manage_units      = ! empty( $permissions['canManageUnits'] );
		$can_manage_tourism    = ! empty( $permissions['canManageTourism'] );
		$can_manage_experience = ! empty( $permissions['canManageExperience'] );
		$can_manage_ranks      = ! empty( $permissions['canManageRanks'] );
		$can_manage_judging    = ! empty( $permissions['canManageJudging'] );
		$can_manage_dues       = ! empty( $permissions['canManageDues'] );
		$can_pay_online        = ! empty( $permissions['canPayOnline'] );
		$can_view_dues         = $can_manage_dues || $can_pay_online || $is_owner;
		$can_manage_sailing    = ! empty( $permissions['canManageSailing'] );
        $can_manage_sail_dues  = ! empty( $permissions['canManageSailDues'] );

		$dues_items_raw      = isset( $collections['dues'] ) && is_array( $collections['dues'] ) ? $collections['dues'] : [];
		$dues_sail_items_raw = isset( $collections['dues_sail'] ) && is_array( $collections['dues_sail'] ) ? $collections['dues_sail'] : [];
		$member_card         = isset( $profile['memberCard'] ) && is_array( $profile['memberCard'] ) ? $profile['memberCard'] : [];
		$phone_list          = isset( $profile['phoneList'] ) && is_array( $profile['phoneList'] ) ? $profile['phoneList'] : [];
		$settings_values     = isset( $collections['settings'] ) && is_array( $collections['settings'] ) ? $collections['settings'] : [];
		
		$club_sections       = $this->build_club_sections( isset( $collections['clubs'] ) && is_array( $collections['clubs'] ) ? $collections['clubs'] : [] );
		$city_sections       = $this->build_city_sections( isset( $collections['cities'] ) && is_array( $collections['cities'] ) ? $collections['cities'] : [] );
		$unit_sections       = $this->build_unit_sections( isset( $collections['units'] ) && is_array( $collections['units'] ) ? $collections['units'] : [] );
		$tourism_sections    = $this->build_tourism_sections( isset( $collections['tourism'] ) && is_array( $collections['tourism'] ) ? $collections['tourism'] : [] );
		$experience_sections = $this->build_experience_sections( isset( $collections['experience'] ) && is_array( $collections['experience'] ) ? $collections['experience'] : [] );

		// --- РОЗРЯДИ ---
		$db_ranks = $wpdb->get_results( $wpdb->prepare( "
			SELECT s.*, 
			       t.TourismType_Name,
			       c.Calendar_ID, c.Calendar_Name, c.Calendar_DateBegin, c.Calendar_DateEnd
			FROM vUserSportCategories s
			LEFT JOIN S_TourismType t ON s.TourismType_ID = t.TourismType_ID
			LEFT JOIN UserCalendar u ON s.UserCalendar_ID = u.UserCalendar_ID
			LEFT JOIN Calendar c ON c.Calendar_ID = u.Calendar_ID
			WHERE s.User_ID = %d
			ORDER BY s.UserSportCategories_DateCreate DESC
		", $profile_user_id ), ARRAY_A );
		if ( ! is_array( $db_ranks ) ) { $db_ranks = []; }

		// --- СУДДІВСТВО ---
		$db_judging = $wpdb->get_results( $wpdb->prepare( "
			SELECT r.*, c.RefereeCategory_Name 
			FROM Referee r 
			LEFT JOIN vRefereeCategory c ON r.RefereeCategory_ID = c.RefereeCategory_ID 
			WHERE r.User_ID = %d 
			ORDER BY c.RefereeCategory_Order ASC
		", $profile_user_id ), ARRAY_A );
		if ( ! is_array( $db_judging ) ) { $db_judging = []; }

		$reg_date = $user->user_registered ?? current_time('mysql');
		$year_reg = (int) gmdate('Y', strtotime($reg_date));
		if ($year_reg < 2000) $year_reg = (int) current_time('Y');
		$current_year = (int) current_time('Y');

		// --- ВНЕСКИ ФСТУ ---
		$db_dues = $wpdb->get_results( $wpdb->prepare( "
			SELECT d.Dues_ID, d.Year_Name, d.Dues_Summa, d.Dues_URL, d.Dues_DateCreate, d.UserCreate, 
				   (SELECT FIOshort FROM vUser WHERE User_ID = d.UserCreate LIMIT 1) as Financier,
				   d.Dues_ShopBillid, d.Dues_ShopOrderNumber, d.Dues_ApprovalCode, d.Dues_CardMask, d.DuesType_Name
			FROM vUserDues d 
			WHERE d.User_ID = %d
			ORDER BY d.Year_Name DESC, d.Dues_DateCreate DESC
		", $profile_user_id ), ARRAY_A );
		if ( ! is_array( $db_dues ) ) { $db_dues = []; }

		$dues_by_year = [];
		foreach ($db_dues as $due) {
			$y = (int) ($due['Year_Name'] ?? 0);
			if ($y > 0) { $dues_by_year[$y][] = $due; }
		}

		$dues_table_rows = [];
		for ($y = $current_year; $y >= $year_reg; $y--) {
			if (!empty($dues_by_year[$y])) {
				$due = $dues_by_year[$y][0]; 
				$has_receipt   = '' !== trim( (string) ( $due['Dues_URL'] ?? '' ) );
				$has_acquiring = '' !== trim( (string) ( $due['Dues_ShopBillid'] ?? '' ) );
				$status        = $has_receipt ? 'Квитанція додана' : ( $has_acquiring ? 'Еквайринг' : 'Квитанція відсутня' );
				
				$dues_table_rows[] = [
					'id'           => $due['Dues_ID'] ?? '',
					'year'         => $y,
					'sum'          => $this->normalize_sum( (string) ($due['Dues_Summa'] ?? '') ),
					'type'         => $this->normalize_value( $due['DuesType_Name'] ?? '' ),
					'date'         => $this->format_date( $due['Dues_DateCreate'] ?? '' ),
					'financier'    => $this->normalize_value( $due['Financier'] ?? '' ),
					'status'       => $status,
					'receipt_url'  => $this->normalize_integration_url( (string) ( $due['Dues_URL'] ?? '' ) ),
					'_actions'     => '', 
				];
			} else {
				$dues_table_rows[] = [
					'id'           => '',
					'year'         => $y,
					'sum'          => '—',
					'type'         => '—',
					'date'         => '—',
					'financier'    => '—',
					'status'       => 'Не сплачено',
					'receipt_url'  => '',
					'_actions'     => ($is_owner || current_user_can('administrator')) ? 'pay_portmone' : '',
				];
			}
		}

		// --- ВНЕСКИ (ВІТР.) (Точний запит зі старого плагіна) ---
		$db_dues_sail = $wpdb->get_results( $wpdb->prepare( "
			SELECT 
				`d`.`DuesSail_ID` AS `DuesSail_ID`,
				`d`.`User_ID` AS `User_ID`,
				`d`.`Year_ID` AS `Year_ID`,
				`d`.`DuesSail_Summa` AS `DuesSail_Summa`,
				`d`.`DuesSail_DateCreate` AS `DuesSail_DateCreate`,
				`d`.`UserCreate` AS `UserCreate`,
				concat(
					(case when isnull(`m21`.`meta_value`) then '' else `m21`.`meta_value` end), ' ',
					(case when isnull(`m22`.`meta_value`) then '' else `m22`.`meta_value` end), ' ',
					(case when isnull(`m23`.`meta_value`) then '' else `m23`.`meta_value` end)
				) AS `FIOCreate`
			FROM `DuesSail` `d` 
			JOIN {$wpdb->users} `u` ON `u`.`ID` = `d`.`User_ID`
			LEFT JOIN {$wpdb->users} `u2` ON `u2`.`ID` = `d`.`UserCreate`
			LEFT JOIN {$wpdb->usermeta} `m21` ON `m21`.`user_id` = `u2`.`ID` AND `m21`.`meta_key` = 'last_name'
			LEFT JOIN {$wpdb->usermeta} `m22` ON `m22`.`user_id` = `u2`.`ID` AND `m22`.`meta_key` = 'first_name'
			LEFT JOIN {$wpdb->usermeta} `m23` ON `m23`.`user_id` = `u2`.`ID` AND `m23`.`meta_key` = 'Patronymic'
			WHERE `d`.`User_ID` = %d 
			ORDER BY `d`.`Year_ID` DESC
		", $profile_user_id ), ARRAY_A );
		if ( ! is_array( $db_dues_sail ) ) { $db_dues_sail = []; }

		$dues_sail_by_year = [];
		foreach ($db_dues_sail as $ds) {
			$y = (int) ($ds['Year_ID'] ?? 0);
			if ($y > 0) { $dues_sail_by_year[$y][] = $ds; }
		}

		// Дістаємо посилання на оплату Monobank з налаштувань БД
		$url_pay_sail = isset($settings_values['UrlPayFinancierCard']) ? $this->normalize_integration_url((string)$settings_values['UrlPayFinancierCard']) : '';

		$dues_sail_table_rows = [];
		for ($y = $current_year; $y >= $year_reg; $y--) {
			if (!empty($dues_sail_by_year[$y])) {
				$ds = $dues_sail_by_year[$y][0]; 
				$dues_sail_table_rows[] = [
					'year'      => $y,
					'sum'       => $this->normalize_sum( (string) ($ds['DuesSail_Summa'] ?? '') ),
					'date'      => $this->format_date( $ds['DuesSail_DateCreate'] ?? '' ),
					'financier' => $this->normalize_value( $ds['FIOCreate'] ?? '' ),
					'status'    => 'Сплачено',
					'url_pay'   => '', // Порожнє, якщо сплачено
					'_actions'  => '', 
				];
			} else {
				$dues_sail_table_rows[] = [
					'year'      => $y,
					'sum'       => '—',
					'date'      => '—',
					'financier' => '—',
					'status'    => 'Не сплачено',
					'url_pay'   => $url_pay_sail, // Передаємо посилання у фронтенд
					// Нова кнопка: з'явиться, якщо є посилання на банку
					'_actions'  => ($is_owner || current_user_can('administrator')) && $url_pay_sail ? 'pay_monobank' : '', 
				];
			}
		}

		$rank_sections       = $this->build_rank_sections( $db_ranks );
		$judging_sections    = $this->build_judging_sections( $db_judging );
		$dues_sections       = $this->build_dues_sections(
			$dues_items_raw,
			$settings_values,
			isset( $collections['unit_payment'] ) && is_array( $collections['unit_payment'] ) ? $collections['unit_payment'] : []
		);
		$sailing_sections    = $this->build_sailing_sections(
			isset( $collections['certs'] ) && is_array( $collections['certs'] ) ? $collections['certs'] : []
		);
		$dues_sail_sections  = $this->build_dues_sail_sections(
			$dues_sail_items_raw,
			$settings_values
		);
		$integration_urls    = $this->get_integration_urls();
		$member_card_permissions = \FSTU\Core\Capabilities::get_member_card_applications_permissions();

		if ( ! $can_view_dues ) {
			$dues_sections = [];
			$dues_table_rows = [];
		}

		if ( empty( $permissions['canViewSailDues'] ) ) {
			$dues_sail_sections = [];
			$dues_sail_table_rows = [];
		}

		$general_sections = [
			[
				'title' => 'Публічні дані',
				'items' => [
					[ 'label' => 'реєстраційний №', 'value' => $this->normalize_value( isset( $member_card['CardNumber'] ) ? (string) $member_card['CardNumber'] : '' ) ],
					[ 'label' => 'ПІБ', 'value' => (string) ( $profile['displayName'] ?? '' ) ],
					[ 'label' => 'Місто (область)', 'value' => $this->normalize_value( (string) ( $profile['cityRegion'] ?? '' ) ) ],
					[ 'label' => 'Осередок ФСТУ', 'value' => $this->normalize_value( (string) ( $profile['ofstName'] ?? '' ) ) ],
					[ 'label' => 'Стать', 'value' => $this->normalize_sex( (string) ( $profile['sex'] ?? '' ) ) ],
					[ 'label' => 'Фото', 'value' => (string) ( $profile['photoUrl'] ?? '' ), 'type' => 'image' ],
				],
			]
		];

		if ( $can_view_member_data ) {
			array_splice(
				$general_sections,
				1,
				0,
				[
					[
						'title' => 'Тільки для членів ФСТУ',
						'items' => [
							[ 'label' => 'Email', 'value' => $this->normalize_value( (string) ( $profile['email'] ?? '' ) ) ],
							[ 'label' => 'Телефони', 'value' => ! empty( $phone_list ) ? implode( ', ', array_map( 'strval', $phone_list ) ) : '—' ],
							[ 'label' => 'Дата народження', 'value' => $this->format_date( (string) ( $profile['birthDate'] ?? '' ) ) ],
							[ 'label' => 'Skype', 'value' => $this->normalize_value( (string) ( $profile['skype'] ?? '' ) ) ],
							[ 'label' => 'Facebook', 'value' => $this->normalize_value( (string) ( $profile['facebook'] ?? '' ) ), 'type' => 'link' ],
						],
					],
				]
			);
		}

		$general_actions = array_merge(
			$this->build_member_card_action_items(
				$member_card,
				$member_card_permissions,
				$integration_urls,
				$profile_user_id,
				$is_owner
			),
			$this->build_tab_actions(
				[
					'Редагувати профіль' => $can_edit_profile,
				],
				'Функції редагування профілю буде підключено окремим етапом.'
			)
		);

		$general_note = 'Розділ «Загальні» вже читає реальні дані користувача, фото, місто, осередок і короткий стан членського квитка.';

		return [
			'general' => [
				'title'   => 'Загальні',
				'visible' => true,
				'sections' => $general_sections,
				'actions' => $general_actions,
				'accessNotice' => '',
				'isReadOnly' => ! $can_edit_profile,
				'note'    => $general_note,
			],
			'private' => [
				'title'   => 'Приватне',
				'visible' => $can_view_private,
				'sections' => [
					[
						'title' => '', 
						'items' => [
							[ 'label' => 'Адреса проживання', 'value' => (string) ( $profile['address'] ?? '' ), 'key' => 'Adr' ],
							[ 'label' => 'Місце роботи, посада', 'value' => (string) ( $profile['job'] ?? '' ), 'key' => 'Job' ],
							[ 'label' => 'Освіта', 'value' => (string) ( $profile['education'] ?? '' ), 'key' => 'Education' ],
							[ 'label' => 'Додатковий телефон 1', 'value' => (string) ( $profile['phone2'] ?? '' ), 'key' => 'Phone2' ],
							[ 'label' => 'Додатковий телефон 2', 'value' => (string) ( $profile['phone3'] ?? '' ), 'key' => 'Phone3' ],
							[ 'label' => 'Телефон родичів', 'value' => (string) ( $profile['familyPhone'] ?? '' ), 'key' => 'PhoneFamily' ],
						],
					],
				],
				'actions' => [],
				'accessNotice' => '', 
				'isReadOnly' => true, 
				'note'    => '* Для оформлення документів',
			],
			'service' => [
				'title'   => 'Службове',
				'visible' => ! empty( $permissions['canViewService'] ),
				'sections' => [
					[
						'title' => 'Профіль користувача',
						'items' => [
							[ 'label' => 'Користувач', 'value' => (string) ( $profile['displayName'] ?? '' ) ],
							[ 'label' => 'Email', 'value' => (string) ( $profile['email'] ?? '' ) ],
							[ 'label' => 'Ролі', 'value' => implode( ', ', array_map( 'strval', (array) ( $user->roles ?? [] ) ) ) ],
							[ 'label' => 'Тип профілю', 'value' => ! empty( $permissions['isOwner'] ) ? 'Власний профіль' : 'Публічний перегляд профілю' ],
						],
					],
					[
						'title' => 'Технічна інформація',
						'items' => [
							[ 'label' => 'User ID', 'value' => (string) ( $profile['userId'] ?? '' ) ],
							[ 'label' => 'Login', 'value' => $this->normalize_value( (string) ( $profile['login'] ?? '' ) ) ],
							[ 'label' => 'Дата реєстрації', 'value' => $this->normalize_value( (string) ( $profile['registered'] ?? '' ) ) ],
							[ 'label' => 'Останній вхід', 'value' => $this->normalize_value( (string) ( $profile['lastLogin'] ?? '' ) ) ],
							[ 'label' => 'Активація Telegram', 'value' => $this->normalize_value( (string) ( $profile['telegramActive'] ?? '' ) ) ],
							[ 'label' => 'VerificationCode', 'value' => $this->normalize_value( (string) ( $profile['telegramCode'] ?? '' ) ) ],
							[ 'label' => 'Telegram ID', 'value' => $this->normalize_value( (string) ( $profile['telegramId'] ?? '' ) ) ],
						],
					],
					[
						'title' => 'Картка',
						'items' => [
							[ 'label' => 'Тип картки', 'value' => $this->normalize_value( isset( $member_card['TypeCard_Name'] ) ? (string) $member_card['TypeCard_Name'] : '' ) ],
							[ 'label' => 'Статус', 'value' => $this->normalize_value( isset( $member_card['StatusCard_Name'] ) ? (string) $member_card['StatusCard_Name'] : '' ) ],
							[ 'label' => 'Сума за виготовлення', 'value' => $this->normalize_sum( isset( $member_card['UserMemberCard_Summa'] ) ? (string) $member_card['UserMemberCard_Summa'] : '' ) ],
						],
					],
					[
						'title' => 'Банківські параметри',
						'items' => [
							[ 'label' => 'ІПН', 'value' => $this->normalize_value( (string) ( $profile['ipn'] ?? '' ) ) ],
							[ 'label' => 'Назва банку', 'value' => $this->normalize_value( (string) ( $profile['bankName'] ?? '' ) ) ],
							[ 'label' => 'IBAN', 'value' => $this->normalize_value( (string) ( $profile['iban'] ?? '' ) ) ],
						],
					],
				],
				'actions' => [],
				'accessNotice' => '',
				'isReadOnly' => true,
				'note'    => '',
			],
			'clubs' => [
				'title'   => '',
				'visible' => true,
				'table'   => [
					'columns' => [
						[ 'key' => 'name', 'label' => 'Назва клубу' ],
						[ 'key' => 'site', 'label' => 'Сайт', 'type' => 'link' ],
						[ 'key' => 'address', 'label' => 'Адреса' ],
						[ 'key' => 'date', 'label' => 'Дата додавання' ],
						[ 'key' => '_actions', 'label' => 'Дії', 'type' => 'actions' ],
					],
					'rows' => array_map( function( $club ) use ( $can_manage_clubs ) {
						return [
							'id'      => ! empty( $club['Club_ID'] ) ? $club['Club_ID'] : ( $club['Club_Name'] ?? '' ),
							'name'    => $this->normalize_value( $club['Club_Name'] ?? '' ),
							'site'    => $this->normalize_value( $club['Club_WWW'] ?? '' ),
							'address' => $this->normalize_value( $club['Club_Adr'] ?? '' ),
							'date'    => $this->format_date( $club['UserClub_Date'] ?? '' ),
							'_actions'=> $can_manage_clubs ? 'delete' : '',
						];
					}, isset( $collections['clubs'] ) && is_array( $collections['clubs'] ) ? $collections['clubs'] : [] ),
					'defaultPerPage' => 10,
					'emptyMessage'   => __( 'Користувач поки не прив’язаний до жодного клубу.', 'fstu' ),
				],
				'actions' => $this->build_tab_actions( [
					[
						'label'   => 'Додати клуб',
						'enabled' => $can_manage_clubs,
						'actionKey' => 'add_club',
					]
				], '' ),
				'accessNotice' => '',
				'isReadOnly' => ! $can_manage_clubs,
				'note'    => 'Показано історію членства у спортивних клубах у вигляді компактної таблиці.',
			],
			'city' => [
				'title'   => '',
				'visible' => true,
				'table'   => [
					'columns' => [
						[ 'key' => 'city', 'label' => 'Місто' ],
						[ 'key' => 'region', 'label' => 'Область' ],
						[ 'key' => 'date', 'label' => 'Дата додавання' ],
						[ 'key' => '_actions', 'label' => 'Дії', 'type' => 'actions' ],
					],
					'rows' => array_map( function( $city ) use ( $can_manage_cities ) {
						return [
							'id'      => ! empty( $city['City_ID'] ) ? $city['City_ID'] : ( $city['City_Name'] ?? '' ),
							'city'    => $this->normalize_value( $city['City_Name'] ?? '' ),
							'region'  => $this->normalize_value( $city['Region_Name'] ?? '' ),
							'date'    => $this->format_date( $city['UserCity_DateCreate'] ?? '' ),
							'_actions'=> $can_manage_cities ? 'delete_city' : '', 
						];
					}, isset( $collections['cities'] ) && is_array( $collections['cities'] ) ? $collections['cities'] : [] ),
					'defaultPerPage' => 10,
					'emptyMessage'   => __( 'Історія міст проживання порожня.', 'fstu' ),
				],
				'actions' => $this->build_tab_actions( [
					[
						'label'   => 'Додати місто',
						'enabled' => $can_manage_cities,
						'actionKey' => 'add_city',
					]
				], '' ),
				'accessNotice' => '',
				'isReadOnly' => ! $can_manage_cities,
				'note'    => 'Показано актуальне місто проживання та історію змін у вигляді таблиці.',
			],
            'units' => [
                'title'   => '',
                'visible' => true,
                'table'   => [
                    'columns' => [
                        [ 'key' => 'unit', 'label' => 'Осередок' ],
                        [ 'key' => 'region', 'label' => 'Область' ],
                        [ 'key' => 'date', 'label' => 'Дата додавання' ],
                        [ 'key' => '_actions', 'label' => 'Дії', 'type' => 'actions' ],
                    ],
                    'rows' => array_map( function( $unit ) use ( $can_manage_units ) {
                        return [
                            'id'      => ! empty( $unit['UserRegistationOFST_ID'] ) ? $unit['UserRegistationOFST_ID'] : ( ! empty( $unit['Unit_ID'] ) ? $unit['Unit_ID'] : ( $unit['Region_Name'] ?? '' ) ),
                            'unit'    => $this->normalize_value( $unit['Unit_Name'] ?? $unit['Region_Name'] ?? '' ),
                            'region'  => $this->normalize_value( $unit['Region_Name'] ?? '' ),
                            'date'    => $this->format_date( $unit['UserRegistationOFST_DateCreate'] ?? '' ),
                            '_actions'=> $can_manage_units ? 'delete_unit' : '',
                        ];
                    }, isset( $collections['units'] ) && is_array( $collections['units'] ) ? $collections['units'] : [] ),
                    'defaultPerPage' => 10,
                    'emptyMessage'   => __( 'Історія членства в осередках ФСТУ відсутня.', 'fstu' ),
                ],
                'actions' => $this->build_tab_actions( [
                    [
                        'label'   => 'Додати осередок',
                        'enabled' => $can_manage_units,
                        'actionKey' => 'add_unit',
                    ]
                ], '' ),
                'accessNotice' => '',
                'isReadOnly' => ! $can_manage_units,
                'note'    => 'Показано актуальний осередок та історію членства в осередках ФСТУ.',
            ],
			'tourism' => [
				'title'   => '',
				'visible' => true,
				'table'   => [
					'columns' => [
						[ 'key' => 'tourism_type', 'label' => 'Вид туризму' ],
						[ 'key' => 'date', 'label' => 'Дата додавання' ],
						[ 'key' => '_actions', 'label' => 'Дії', 'type' => 'actions' ],
					],
					'rows' => array_map( function( $item ) use ( $can_manage_tourism ) {
						return [
							'id'           => ! empty( $item['UserTourismType_ID'] ) ? $item['UserTourismType_ID'] : ( ! empty( $item['TourismType_ID'] ) ? $item['TourismType_ID'] : ( $item['TourismType_Name'] ?? '' ) ),
							'tourism_type' => $this->normalize_value( $item['TourismType_Name'] ?? '' ),
							'date'         => $this->format_date( $item['UserTourismType_DateCreate'] ?? '' ),
							'_actions'     => $can_manage_tourism ? 'delete_tourism' : '',
						];
					}, isset( $collections['tourism'] ) && is_array( $collections['tourism'] ) ? $collections['tourism'] : [] ),
					'defaultPerPage' => 10,
					'emptyMessage'   => __( 'Види туризму для користувача поки не визначені.', 'fstu' ),
				],
				'actions' => $this->build_tab_actions( [
					[
						'label'   => 'Додати вид туризму',
						'enabled' => $can_manage_tourism,
						'actionKey' => 'add_tourism',
					]
				], '' ),
				'accessNotice' => '',
				'isReadOnly' => ! $can_manage_tourism,
				'note'    => 'Показано історію прив’язки видів туризму.',
			],
			'experience' => [
				'title'   => '',
				'visible' => true,
				'table'   => [
					'columns' => [
						[ 'key' => 'category', 'label' => 'Категорія' ],
						[ 'key' => 'type', 'label' => 'Участь' ],
						[ 'key' => 'event', 'label' => 'Захід' ],
						[ 'key' => 'tourism', 'label' => 'Вид туризму' ],
						[ 'key' => 'dates', 'label' => 'Терміни' ],
						[ 'key' => 'divodka', 'label' => 'Довідка', 'type' => 'link' ],
						[ 'key' => '_actions', 'label' => 'Дії', 'type' => 'actions' ],
					],
					'rows' => array_map( function( $item ) use ( $can_manage_experience, $is_owner ) {
						$can_edit = $is_owner || $can_manage_experience || current_user_can('administrator') || current_user_can('globalregistrar');
						$row_id = $item['UserHikingCategory_ID'] ?? $item['UserCalendar_ID'] ?? $item['Calendar_ID'] ?? $item['id'] ?? '';

						return [
							'id'       => $row_id,
							'category' => $this->normalize_value( $item['HikingCategory_Name'] ?? $item['CategoriesName'] ?? '' ),
							'type'     => $this->normalize_value( $item['ParticipationType_Name'] ?? '' ),
							'event'    => $this->normalize_value( $item['Calendar_Name'] ?? '' ),
							'tourism'  => $this->normalize_value( $item['TourismType_Name'] ?? '' ),
							'dates'    => $this->format_period( $item['Calendar_DateBegin'] ?? '', $item['Calendar_DateEnd'] ?? '' ),
							'divodka'  => $this->normalize_value( $item['UserHikingCategory_UrlDivodka'] ?? '' ),
							'_actions' => $can_edit ? 'edit_divodka' : '',
						];
					}, isset( $collections['experience'] ) && is_array( $collections['experience'] ) ? $collections['experience'] : [] ),
					'defaultPerPage' => 10,
					'emptyMessage'   => __( 'Спортивний туристський досвід поки не вказаний.', 'fstu' ),
				],
				'actions' => [], 
				'accessNotice' => '',
				'isReadOnly' => !( $is_owner || $can_manage_experience || current_user_can('administrator') || current_user_can('globalregistrar') ),
				'note'    => 'Показано історію участі у спортивних походах.',
			],
			'ranks' => [
				'title'   => '',
				'visible' => true,
				'table'   => [
					'columns' => [
						[ 'key' => 'rank', 'label' => 'Розряд / звання' ],
						[ 'key' => 'date', 'label' => 'Дата присвоєння' ],
						[ 'key' => 'tourism', 'label' => 'Вид туризму' ],
						[ 'key' => 'calendar', 'label' => 'Календар', 'type' => 'html' ],
						[ 'key' => 'dates', 'label' => 'Терміни' ],
						[ 'key' => 'prikaz', 'label' => 'Наказ', 'type' => 'link' ],
						[ 'key' => '_actions', 'label' => 'Дії', 'type' => 'actions' ],
					],
					'rows' => array_map( function( $item ) use ( $is_owner, $can_manage_ranks ) {
						$can_edit = $is_owner || $can_manage_ranks || current_user_can('administrator') || current_user_can('globalregistrar');
						$row_id = $item['UserSportCategories_ID'] ?? $item['id'] ?? $item['ID'] ?? $item['Calendar_ID'] ?? $item['SportsCategories_Name'] ?? $item['CategoriesName'] ?? '';
						
						$calendar_name = $this->normalize_value( $item['Calendar_Name'] ?? '' );
						$calendar_url  = $this->build_calendar_url( $item['Calendar_ID'] ?? '' );
						$calendar_html = $calendar_url ? '<a href="' . esc_url($calendar_url) . '" target="_blank" style="color:#b5473a;text-decoration:underline;">Змагання: ' . esc_html($calendar_name) . '</a>' : $calendar_name;

						return [
							'id'       => $row_id,
							'rank'     => $this->normalize_value( $item['SportsCategories_Name'] ?? $item['CategoriesName'] ?? '' ),
							'date'     => $this->format_date( $item['UserSportCategories_DatePrikaz'] ?? '' ),
							'tourism'  => $this->normalize_value( $item['TourismType_Name'] ?? '' ),
							'calendar' => $calendar_html, 
							'dates'    => $this->format_period( $item['Calendar_DateBegin'] ?? '', $item['Calendar_DateEnd'] ?? '' ),
							'prikaz'   => $this->normalize_value( $item['UserSportCategories_UrlPrikaz'] ?? '' ),
							'_actions' => $can_edit && ! empty( $row_id ) ? 'delete_rank' : '',
						];
					}, $db_ranks ),
					'defaultPerPage' => 10,
					'emptyMessage'   => __( 'Спортивні розряди або звання поки не вказані.', 'fstu' ),
				],
				'actions' => $this->build_tab_actions( [
					[
						'label'     => 'Додати розряд',
						'enabled'   => false, 
						'actionKey' => 'add_rank',
						'pending'   => 'Додавання розрядів тимчасово заблоковано і буде реалізовано через модуль Календаря змагань.'
					]
				], '' ),
				'accessNotice' => '',
				'isReadOnly' => !( $is_owner || $can_manage_ranks || current_user_can('administrator') || current_user_can('globalregistrar') ),
				'note'    => 'Показано історію присвоєння спортивних розрядів та звань.',
			],
			'judging' => [
				'title'   => '',
				'visible' => true,
				'table'   => [
					'columns' => [
						[ 'key' => 'category', 'label' => 'Категорія' ],
						[ 'key' => 'date', 'label' => 'Дата додавання' ],
						[ 'key' => '_actions', 'label' => 'Дії', 'type' => 'actions' ],
					],
					'rows' => array_map( function( $item ) use ( $is_owner, $can_manage_judging ) {
						$can_edit = $is_owner || $can_manage_judging || current_user_can('administrator') || current_user_can('globalregistrar');
						$row_id = $item['Referee_ID'] ?? $item['id'] ?? $item['ID'] ?? '';

						return [
							'id'         => $row_id,
							'category'   => $this->normalize_value( $item['RefereeCategory_Name'] ?? '' ),
							'date'       => $this->format_date( $item['Referee_DateCreate'] ?? '' ),
							'_actions'   => $can_edit && ! empty( $row_id ) ? 'delete_judging' : '',
						];
					}, $db_judging ),
					'defaultPerPage' => 10,
					'emptyMessage'   => __( 'Суддівська категорія поки не вказана.', 'fstu' ),
				],
				'actions' => $this->build_tab_actions( [
					[
						'label'     => 'Додати суддівську категорію',
						'enabled'   => $is_owner || $can_manage_judging || current_user_can('administrator') || current_user_can('globalregistrar'),
						'actionKey' => 'add_judging',
					],
					[
						'label'     => 'Відкрити реєстр суддів ФСТУ',
						'enabled'   => true,
						'actionKey' => 'open_referee_registry', 
					]
				], '' ),
				'accessNotice' => '',
				'isReadOnly' => !( $is_owner || $can_manage_judging || current_user_can('administrator') || current_user_can('globalregistrar') ),
				'note'    => 'Показано історію суддівських категорій.',
			],
			'dues' => [
				'title'   => '', 
				'visible' => $can_view_dues,
				'sections' => $dues_sections, 
				'table'    => [
					'columns' => [
						[ 'key' => 'year', 'label' => 'Рік' ],
						[ 'key' => 'sum', 'label' => 'Сума' ],
						[ 'key' => 'type', 'label' => 'Тип' ],
						[ 'key' => 'date', 'label' => 'Дата додавання' ],
						[ 'key' => 'financier', 'label' => 'Фінансист' ],
						[ 'key' => 'status', 'label' => 'Статус' ],
						[ 'key' => 'receipt_url', 'label' => 'Квитанція', 'type' => 'link' ],
						[ 'key' => '_actions', 'label' => 'Дії', 'type' => 'actions' ],
					],
					'rows'           => $dues_table_rows, 
					'defaultPerPage' => 15,
					'emptyMessage'   => __( 'Записи членських внесків відсутні.', 'fstu' ),
				],
				'actions' => $this->build_tab_actions(
					[
						[
							'label'   => 'Додати квитанцію',
							'enabled' => $can_manage_dues || $is_owner, 
							'actionKey' => 'upload_dues_receipt',
						],
						[
							'label'   => 'Відкрити реєстр платіжних документів',
							'enabled' => true,
							'actionKey' => 'open_payment_docs',
						],
					],
					''
				),
				'accessNotice' => '',
				'isReadOnly' => ! ( $can_manage_dues || $can_pay_online || $is_owner ),
				'note'    => 'Показано історію членських внесків. Якщо внесок за рік відсутній, ви можете оплатити його онлайн.',
			],
			'sailing' => [
				'title'   => '',
				'visible' => true,
				'table'   => [
					'columns' => [
						[ 'key' => 'name', 'label' => 'Назва' ],
						[ 'key' => 'reg_num', 'label' => 'Реєстраційний №', 'type' => 'html' ],
						[ 'key' => 'sail_num', 'label' => '№ на вітрилі' ],
						[ 'key' => 'date', 'label' => 'Дата реєстрації' ],
						[ 'key' => 'status', 'label' => 'Статус' ],
						[ 'key' => 'sum', 'label' => 'Сума сплати' ],
						[ 'key' => 'dues_prev', 'label' => 'Попер. рік' ],
						[ 'key' => 'dues_curr', 'label' => 'Поточ. рік' ],
						[ 'key' => 'warning', 'label' => 'Попередження' ],
					],
					'rows' => array_map( function( $item ) {
						$reg_num = $this->normalize_value( $item['RegNumber'] ?? '' );
						$url = $this->build_sailboat_url( $item['AppShipTicket_ID'] ?? '' );
						$reg_html = $url ? '<a href="' . esc_url($url) . '" target="_blank" style="color: #1d4ed8; font-weight: bold; text-decoration: underline;">' . esc_html($reg_num) . '</a>' : esc_html($reg_num);

						return [
							'name'      => $this->normalize_value( $item['Sailboat_Name'] ?? '' ),
							'reg_num'   => $reg_html,
							'sail_num'  => $this->normalize_value( $item['Sailboat_NumberSail'] ?? '' ),
							'date'      => $this->format_date( $item['AppShipTicket_DateCreate'] ?? '' ),
							'status'    => $this->normalize_value( $item['Verification_Name'] ?? '' ),
							'sum'       => $this->normalize_sum( (string) ($item['AppShipTicket_Summa'] ?? '') ),
							'dues_prev' => $this->normalize_dues_flag( (string) ($item['PrevYearDuesSail'] ?? '') ),
							'dues_curr' => $this->normalize_dues_flag( (string) ($item['CurrYearDuesSail'] ?? '') ),
							'warning'   => $this->build_sailing_dues_warning( (string) ($item['PrevYearDuesSail'] ?? ''), (string) ($item['CurrYearDuesSail'] ?? '') ),
						];
					}, isset( $collections['vessels'] ) && is_array( $collections['vessels'] ) ? $collections['vessels'] : [] ),
					'defaultPerPage' => 10,
					'emptyMessage'   => __( 'Вітрильні судна відсутні.', 'fstu' ),
				],
				'sections' => $sailing_sections, 
				'actions' => $this->build_tab_actions(
					[
						[
							'label'   => 'Подати / редагувати заявку стернового',
							'enabled' => $can_manage_sailing && '' !== ( $integration_urls['steering'] ?? '' ) && $profile_user_id > 0,
							'url'     => $this->build_steering_manage_url( (string) ( $integration_urls['steering'] ?? '' ), $profile_user_id ),
							'pending' => 'Сторінка Steering поки не визначена.',
						],
						[
							'label'   => 'Подати заявку до суднового реєстру',
							'enabled' => $can_manage_sailing && $is_owner && '' !== ( $integration_urls['sailboats'] ?? '' ) && $profile_user_id > 0,
							'url'     => $this->build_sailboats_create_url( (string) ( $integration_urls['sailboats'] ?? '' ), $profile_user_id ),
							'pending' => 'Модуль суднового реєстру доступний лише для власного профілю.',
						],
						[
							'label'   => 'Відкрити реєстр суден ФСТУ',
							'enabled' => '' !== ( $integration_urls['sailboats'] ?? '' ),
							'url'     => (string) ( $integration_urls['sailboats'] ?? '' ),
							'target'  => '_blank',
							'pending' => 'Сторінка реєстру суден поки не визначена.',
						],
						[
							'label'   => 'Відкрити реєстр стернових ФСТУ',
							'enabled' => '' !== ( $integration_urls['steering'] ?? '' ),
							'url'     => (string) ( $integration_urls['steering'] ?? '' ),
							'target'  => '_blank',
							'pending' => 'Сторінка реєстру стернових поки не визначена.',
						],
					],
					''
				),
				'accessNotice' => '',
				'isReadOnly' => ! $can_manage_sailing,
				'note'    => 'Показано дані про судна та вітрильні посвідчення користувача.',
			],
            'dues_sail' => [
                'title'   => '',
                'visible' => ! empty( $permissions['canViewSailDues'] ),
                'sections' => $dues_sail_sections,
                'table'    => [
                    'columns' => [
                        [ 'key' => 'year', 'label' => 'Рік' ],
                        [ 'key' => 'sum', 'label' => 'Сума' ],
                        [ 'key' => 'date', 'label' => 'Дата додавання' ],
                        [ 'key' => 'financier', 'label' => 'Фінансист' ],
                        [ 'key' => 'status', 'label' => 'Статус' ],
                        [ 'key' => '_actions', 'label' => 'Дії', 'type' => 'actions' ],
                    ],
                    'rows'           => $dues_sail_table_rows,
                    'defaultPerPage' => 10,
                    'emptyMessage'   => __( 'Записи вітрильних внесків відсутні.', 'fstu' ),
                ],
                // ГАРАНТОВАНЕ виведення кнопки без зайвих обгорток і тернарних операторів
                'actions' => $this->build_tab_actions( [
                    [
                        'label'     => 'Додати оплату',
                        'enabled'   => $can_manage_sail_dues || current_user_can('administrator') || current_user_can('sailingfinancier'),
                        'actionKey' => 'add_sail_dues',
                    ],
                ], '' ),
                'accessNotice' => '',
                'isReadOnly' => ! $can_manage_sail_dues,
                'note'    => 'Показано історію членських внесків вітрильників.',
            ],
		];
	}
//...

	private function get_photo_url( int $user_id ): string {
		$photo_path = ABSPATH . 'photo/' . $user_id . '.jpg';

		if ( file_exists( $photo_path ) ) {
			// Додаємо ?v=час_файлу, щоб браузер ніколи не кешував старе фото
			return site_url( '/photo/' . $user_id . '.jpg?v=' . filemtime( $photo_path ) );
		}

		return get_avatar_url( $user_id, [
			'size'          => 220,
			'default'       => 'mp',
			'force_default' => false,
		] );
	}

	private function has_member_consent( int $user_id ): bool {
		$flag_view = get_user_meta( $user_id, 'FlagView', true );

		return '1' === (string) $flag_view || '' === (string) $flag_view;
	}

	private function normalize_facebook_url( string $value ): string {
		$value = trim( $value );

		if ( '' === $value ) {
			return '';
		}

		if ( ! str_starts_with( $value, 'http' ) ) {
			$value = 'https://facebook.com/' . ltrim( $value, '/' );
		}

		return $value;
	}

	private function normalize_value( string $value ): string {
		$value = trim( $value );

		return '' !== $value ? $value : '—';
	}

	private function normalize_sex( string $value ): string {
		if ( 'Ч' === $value ) {
			return 'Чоловіча';
		}

		if ( 'Ж' === $value ) {
			return 'Жіноча';
		}

		return '—';
	}

	private function format_date( string $value ): string {
		$value = trim( $value );

		if ( '' === $value ) {
			return '—';
		}

		$date = date_create( $value );

		return $date ? date_format( $date, 'd.m.Y' ) : $value;
	}

	private function normalize_sum( string $value ): string {
		$value = trim( $value );

		if ( '' === $value || ! is_numeric( $value ) ) {
			return '—';
		}

		return number_format( (float) $value, 2, '.', ' ' ) . ' грн';
	}

	/**
	 * @param array<int,array<string,string>> $clubs
	 * @return array<int,array<string,mixed>>
	 */
	private function build_club_sections( array $clubs ): array {
		$sections = [];

		foreach ( $clubs as $index => $club ) {
			$name = $this->normalize_value( $club['Club_Name'] ?? '' );

			$sections[] = [
				'title' => 'Клуб ' . ( $index + 1 ) . ': ' . $name,
				'items' => [
					[ 'label' => 'Назва клубу', 'value' => $name ],
					[ 'label' => 'Сайт', 'value' => $this->normalize_value( $club['Club_WWW'] ?? '' ), 'type' => 'link' ],
					[ 'label' => 'Адреса', 'value' => $this->normalize_value( $club['Club_Adr'] ?? '' ) ],
					[ 'label' => 'Дата додавання', 'value' => $this->format_date( $club['UserClub_Date'] ?? '' ) ],
				],
			];
		}

		return $sections;
	}

	/**
	 * @param array<int,array<string,string>> $cities
	 * @return array<int,array<string,mixed>>
	 */
	private function build_city_sections( array $cities ): array {
		if ( empty( $cities ) ) {
			return [];
		}

		$sections   = [];
		$current    = $cities[0];
		$sections[] = [
			'title' => 'Актуальний запис',
			'items' => [
				[ 'label' => 'Місто', 'value' => $this->normalize_value( $current['City_Name'] ?? '' ) ],
				[ 'label' => 'Область', 'value' => $this->normalize_value( $current['Region_Name'] ?? '' ) ],
				[ 'label' => 'Дата додавання', 'value' => $this->format_date( $current['UserCity_DateCreate'] ?? '' ) ],
			],
		];

		for ( $i = 1, $count = count( $cities ); $i < $count; $i++ ) {
			$city       = $cities[ $i ];
			$sections[] = [
				'title' => 'Історія #' . $i,
				'items' => [
					[ 'label' => 'Місто', 'value' => $this->normalize_value( $city['City_Name'] ?? '' ) ],
					[ 'label' => 'Область', 'value' => $this->normalize_value( $city['Region_Name'] ?? '' ) ],
					[ 'label' => 'Дата додавання', 'value' => $this->format_date( $city['UserCity_DateCreate'] ?? '' ) ],
				],
			];
		}

		return $sections;
	}

	/**
	 * @param array<int,array<string,string>> $units
	 * @return array<int,array<string,mixed>>
	 */
	private function build_unit_sections( array $units ): array {
		if ( empty( $units ) ) {
			return [];
		}

		$sections   = [];
		$current    = $units[0];
		$sections[] = [
			'title' => 'Актуальний осередок',
			'items' => [
				[ 'label' => 'Осередок', 'value' => $this->normalize_value( $current['Unit_Name'] ?? '' ) ],
				[ 'label' => 'Область', 'value' => $this->normalize_value( $current['Region_Name'] ?? '' ) ],
				[ 'label' => 'Дата додавання', 'value' => $this->format_date( $current['UserRegistationOFST_DateCreate'] ?? '' ) ],
			],
		];

		for ( $i = 1, $count = count( $units ); $i < $count; $i++ ) {
			$unit       = $units[ $i ];
			$sections[] = [
				'title' => 'Історія #' . $i,
				'items' => [
					[ 'label' => 'Осередок', 'value' => $this->normalize_value( $unit['Unit_Name'] ?? '' ) ],
					[ 'label' => 'Область', 'value' => $this->normalize_value( $unit['Region_Name'] ?? '' ) ],
					[ 'label' => 'Дата додавання', 'value' => $this->format_date( $unit['UserRegistationOFST_DateCreate'] ?? '' ) ],
				],
			];
		}

		return $sections;
	}

	/**
	 * @param array<int,array<string,string>> $items
	 * @return array<int,array<string,mixed>>
	 */
	private function build_tourism_sections( array $items ): array {
		$sections = [];

		foreach ( $items as $index => $item ) {
			$name = $this->normalize_value( $item['TourismType_Name'] ?? '' );

			$sections[] = [
				'title' => 'Вид туризму #' . ( $index + 1 ),
				'items' => [
					[ 'label' => 'Назва', 'value' => $name ],
					[ 'label' => 'Дата додавання', 'value' => $this->format_date( $item['UserTourismType_DateCreate'] ?? '' ) ],
				],
			];
		}

		return $sections;
	}

	/**
	 * @param array<int,array<string,string>> $items
	 * @return array<int,array<string,mixed>>
	 */
	private function build_experience_sections( array $items ): array {
		$sections = [];

		foreach ( $items as $index => $item ) {
			$event_name = $this->normalize_value( $item['Calendar_Name'] ?? '' );

			$sections[] = [
				'title' => 'Похід #' . ( $index + 1 ) . ': ' . $event_name,
				'items' => [
					[ 'label' => 'Категорія походу', 'value' => $this->normalize_value( $item['HikingCategory_Name'] ?? '' ) ],
					[ 'label' => 'Тип участі', 'value' => $this->normalize_value( $item['ParticipationType_Name'] ?? '' ) ],
					[ 'label' => 'Захід', 'value' => $event_name ],
					[ 'label' => 'Посилання на захід', 'value' => $this->build_calendar_url( $item['Calendar_ID'] ?? '' ), 'type' => 'link' ],
					[ 'label' => 'Вид туризму', 'value' => $this->normalize_value( $item['TourismType_Name'] ?? '' ) ],
					[ 'label' => 'Терміни', 'value' => $this->format_period( $item['Calendar_DateBegin'] ?? '', $item['Calendar_DateEnd'] ?? '' ) ],
					[ 'label' => 'Довідка', 'value' => $this->normalize_value( $item['UserHikingCategory_UrlDivodka'] ?? '' ), 'type' => 'link' ],
				],
			];
		}

		return $sections;
	}

	/**
	 * @param array<int,array<string,string>> $items
	 * @return array<int,array<string,mixed>>
	 */
	private function build_rank_sections( array $items ): array {
		$sections = [];

		foreach ( $items as $index => $item ) {
			$rank_name = $this->normalize_value( $item['SportsCategories_Name'] ?? '' );

			$sections[] = [
				'title' => 'Розряд #' . ( $index + 1 ) . ': ' . $rank_name,
				'items' => [
					[ 'label' => 'Розряд / звання', 'value' => $rank_name ],
					[ 'label' => 'Дата присвоєння', 'value' => $this->format_date( $item['UserSportCategories_DatePrikaz'] ?? '' ) ],
					[ 'label' => 'Посилання на наказ', 'value' => $this->normalize_value( $item['UserSportCategories_UrlPrikaz'] ?? '' ), 'type' => 'link' ],
					[ 'label' => 'Вид туризму', 'value' => $this->normalize_value( $item['TourismType_Name'] ?? '' ) ],
					[ 'label' => 'Календар', 'value' => $this->normalize_value( $item['Calendar_Name'] ?? '' ) ],
					[ 'label' => 'Посилання на календар', 'value' => $this->build_calendar_url( $item['Calendar_ID'] ?? '' ), 'type' => 'link' ],
					[ 'label' => 'Терміни', 'value' => $this->format_period( $item['Calendar_DateBegin'] ?? '', $item['Calendar_DateEnd'] ?? '' ) ],
				],
			];
		}

		return $sections;
	}

	/**
	 * @param array<int,array<string,string>> $items
	 * @return array<int,array<string,mixed>>
	 */
	private function build_judging_sections( array $items ): array {
		$sections = [];

		foreach ( $items as $index => $item ) {
			$category = $this->normalize_value( $item['RefereeCategory_Name'] ?? '' );

			$sections[] = [
				'title' => 'Суддівська категорія #' . ( $index + 1 ),
				'items' => [
					[ 'label' => 'Категорія', 'value' => $category ],
					[ 'label' => 'Дата додавання', 'value' => $this->format_date( $item['Referee_DateCreate'] ?? '' ) ],
					[ 'label' => 'Номер наказу', 'value' => $this->normalize_value( $item['Referee_NumOrder'] ?? '' ) ],
					[ 'label' => 'Дата наказу', 'value' => $this->format_date( $item['Referee_DateOrder'] ?? '' ) ],
					[ 'label' => 'Посилання на наказ', 'value' => $this->normalize_value( $item['Referee_URLOrder'] ?? '' ), 'type' => 'link' ],
				],
			];
		}

		return $sections;
	}

	/**
	 * @param array<int,array<string,string>> $items
	 * @param array<string,string> $settings
	 * @param array<string,string> $unit_payment
	 * @return array<int,array<string,mixed>>
	 */
	private function build_dues_sections( array $items, array $settings, array $unit_payment ): array {
		$sections = [];

		$annual_fee = $this->normalize_sum( $settings['AnnualFee'] ?? '' );
		$unit_fee   = !empty($unit_payment['Unit_AnnualFee']) ? $this->normalize_sum( $unit_payment['Unit_AnnualFee'] ) : '—';
		
		$fstu_link = 'https://www.fstu.com.ua/rekviziti-dlya-oplati-organizacijnix-vneskiv-fstu/';
		$unit_link = !empty($unit_payment['Unit_UrlPay']) ? $this->normalize_value($unit_payment['Unit_UrlPay']) : '';
		$unit_card = !empty($unit_payment['Unit_PaymentCard']) ? $this->normalize_value($unit_payment['Unit_PaymentCard']) : '';

		// Формуємо 3-колонкову таблицю у вигляді сирого HTML
		$html = '<div class="fstu-table-wrap" style="margin-top: 20px;"><table class="fstu-table"><tbody class="fstu-tbody">';
		
		// Рядок 1: ФСТУ
		$html .= '<tr class="fstu-row">';
		$html .= '<td class="fstu-td" style="width: 33%;">Річний внесок ФСТУ</td>';
		$html .= '<td class="fstu-td" style="width: 33%; font-weight: 600;">' . esc_html($annual_fee) . '</td>';
		$html .= '<td class="fstu-td" style="width: 33%;"><a href="' . esc_url($fstu_link) . '" target="_blank" style="color: #1d4ed8; text-decoration: underline;">Реквізити для сплати членських внесків</a></td>';
		$html .= '</tr>';

		// Рядок 2: Осередок (Посилання)
		if ( $unit_fee !== '—' || $unit_link ) {
			$html .= '<tr class="fstu-row">';
			$html .= '<td class="fstu-td">Річний внесок в осередок</td>';
			$html .= '<td class="fstu-td" style="font-weight: 600;">' . esc_html($unit_fee) . '</td>';
			if ($unit_link) {
				$html .= '<td class="fstu-td"><a href="' . esc_url($unit_link) . '" target="_blank" style="color: #1d4ed8; text-decoration: underline;">Посилання для сплати в осередок</a></td>';
			} else {
				$html .= '<td class="fstu-td">—</td>';
			}
			$html .= '</tr>';
		}

		// Рядок 3: Осередок (Карта)
		if ( $unit_card ) {
			$html .= '<tr class="fstu-row">';
			$html .= '<td class="fstu-td">Карта для сплати в осередок</td>';
			$html .= '<td class="fstu-td" style="font-weight: 600;">' . esc_html($unit_card) . '</td>';
			$html .= '<td class="fstu-td"></td>';
			$html .= '</tr>';
		}

		$html .= '</tbody></table></div>';

		$sections[] = [
			'title' => 'Реквізити та сплата',
			'type'  => 'raw_html', // Використовуємо новий тип для рендерингу
			'html'  => $html,
		];

		return $sections;
	}

	/**
	 * @param array<int,array<string,string>> $items
	 * @return array<int,array<string,string>>
	 */
	private function build_dues_table_rows( array $items ): array {
		$rows = [];

		foreach ( $items as $item ) {
			$has_receipt   = '' !== trim( (string) ( $item['Dues_URL'] ?? '' ) );
			$has_acquiring = '' !== trim( (string) ( $item['Dues_ShopBillid'] ?? '' ) );
			$status        = $has_receipt ? 'Квитанція додана' : ( $has_acquiring ? 'Еквайринг' : 'Квитанція відсутня' );

			$rows[] = [
				'year'         => $this->normalize_value( $item['Year_Name'] ?? '' ),
				'sum'          => $this->normalize_sum( $item['Dues_Summa'] ?? '' ),
				'type'         => $this->normalize_value( $item['DuesType_Name'] ?? '' ),
				'date'         => $this->normalize_value( $item['Dues_DateCreate'] ?? '' ),
				'financier'    => $this->normalize_value( $item['Financier'] ?? '' ),
				'status'       => $status,
				'receipt_url'  => $this->normalize_integration_url( (string) ( $item['Dues_URL'] ?? '' ) ),
				'shop_bill_id' => $this->normalize_value( $item['Dues_ShopBillid'] ?? '' ),
			];
		}

		return $rows;
	}

	/**
	 * @param array<int,array<string,string>> $certs
	 * @return array<int,array<string,mixed>>
	 */
	private function build_sailing_sections( array $certs ): array {
		if ( empty( $certs ) ) {
			return [];
		}

		$html = '<h4 class="fstu-personal-section-block__title" style="margin-top: 20px; margin-bottom: 15px;">Посвідчення стернового</h4>';
		$html .= '<div class="fstu-table-wrap"><table class="fstu-table"><thead class="fstu-thead"><tr class="fstu-row">';
		$html .= '<th class="fstu-th">Тип</th><th class="fstu-th">Номер</th><th class="fstu-th">Статус</th><th class="fstu-th">Дата створення</th><th class="fstu-th">Дата оплати / реєстрації</th><th class="fstu-th">Документ</th>';
		$html .= '</tr></thead><tbody class="fstu-tbody">';

		foreach ( $certs as $item ) {
			$type = $this->normalize_value( $item['Type'] ?? '' );
			$url = $this->build_document_url( $item['Base_Url'] ?? '', $item['Document_ID'] ?? '' );
			$doc_link = $url ? '<a href="' . esc_url($url) . '" target="_blank" style="color: #1d4ed8; text-decoration: underline;">Відкрити</a>' : '—';

			$html .= '<tr class="fstu-row">';
			$html .= '<td class="fstu-td">' . esc_html($type) . '</td>';
			$html .= '<td class="fstu-td">' . esc_html($this->normalize_value( $item['Number'] ?? '' )) . '</td>';
			$html .= '<td class="fstu-td">' . esc_html($this->normalize_value( $item['Status'] ?? '' )) . '</td>';
			$html .= '<td class="fstu-td">' . esc_html($this->format_date( $item['Created_At'] ?? '' )) . '</td>';
			$html .= '<td class="fstu-td">' . esc_html($this->format_date( $item['Paid_At'] ?? '' )) . '</td>';
			$html .= '<td class="fstu-td">' . $doc_link . '</td>';
			$html .= '</tr>';
		}

		$html .= '</tbody></table></div>';

		return [
			[
				'type' => 'raw_html',
				'html' => $html,
			]
		];
	}

	/**
	 * @param array<int,array<string,string>> $items
	 * @param array<string,string> $settings
	 * @return array<int,array<string,mixed>>
	 */
	private function build_dues_sail_sections( array $items, array $settings ): array {
		$sail_fee = $this->normalize_sum( $settings['PaymentRegistration'] ?? '' );
		$financier_name = $this->normalize_value( $settings['NameSailboatFinancier'] ?? '' );
		$financier_card = $this->normalize_value( $settings['FinancierCardNumber'] ?? '' );
		
		$url_pay = $this->normalize_value( $settings['UrlPayFinancierCard'] ?? '' );
		$url_docs = $this->normalize_value( $settings['UrlPayDocuments'] ?? '' );
		$card_docs = $this->normalize_value( $settings['CardNumberToPayDocuments'] ?? '' );

		$html = '<div class="fstu-table-wrap" style="margin-top: 20px;"><table class="fstu-table"><tbody class="fstu-tbody">';
		
		// Рядок 1: Річний внесок
		$html .= '<tr class="fstu-row">';
		$html .= '<td class="fstu-td" style="width: 33%;">Річний внесок вітрильників</td>';
		$html .= '<td class="fstu-td" style="width: 33%; font-weight: 600;">' . esc_html($sail_fee) . '</td>';
		$html .= '<td class="fstu-td" style="width: 33%;">' . ($url_pay !== '—' ? '<a href="' . esc_url($url_pay) . '" target="_blank" style="color: #1d4ed8; text-decoration: underline;">Посилання для сплати</a>' : '') . '</td>';
		$html .= '</tr>';

		// Рядок 2: Фінансист
		$html .= '<tr class="fstu-row">';
		$html .= '<td class="fstu-td">Фінансист / Карта</td>';
		$html .= '<td class="fstu-td" style="font-weight: 600;">' . esc_html($financier_card) . '</td>';
		$html .= '<td class="fstu-td">' . esc_html($financier_name) . '</td>';
		$html .= '</tr>';

		// Рядок 3: Оплата документів
		if ($card_docs !== '—' || $url_docs !== '—') {
			$html .= '<tr class="fstu-row">';
			$html .= '<td class="fstu-td">Оплата документів (Карта / Посилання)</td>';
			$html .= '<td class="fstu-td" style="font-weight: 600;">' . esc_html($card_docs) . '</td>';
			$html .= '<td class="fstu-td">' . ($url_docs !== '—' ? '<a href="' . esc_url($url_docs) . '" target="_blank" style="color: #1d4ed8; text-decoration: underline;">Оплатити документи</a>' : '') . '</td>';
			$html .= '</tr>';
		}

		$html .= '</tbody></table></div>';

		return [
			[
				'title' => 'Реквізити та посилання (вітрильництво)',
				'type'  => 'raw_html',
				'html'  => $html,
			]
		];
	}

	/**
	 * @param array<int,array<string,string>> $items
	 * @return array<int,array<string,string>>
	 */
	private function build_dues_sail_table_rows( array $items ): array {
		$rows = [];

		foreach ( $items as $item ) {
			$rows[] = [
				'year'      => $this->normalize_value( $item['Year_ID'] ?? '' ),
				'sum'       => $this->normalize_sum( $item['DuesSail_Summa'] ?? '' ),
				'date'      => $this->normalize_value( $item['DuesSail_DateCreate'] ?? '' ),
				'financier' => $this->normalize_value( $item['FIOCreate'] ?? '' ),
			];
		}

		return $rows;
	}

	private function format_period( string $date_from, string $date_to ): string {
		$from = $this->format_date( $date_from );
		$to   = $this->format_date( $date_to );

		if ( '—' === $from && '—' === $to ) {
			return '—';
		}

		return $from . ' - ' . $to;
	}

	private function build_calendar_url( string $calendar_id ): string {
		$calendar_id = trim( $calendar_id );

		if ( '' === $calendar_id || ! ctype_digit( $calendar_id ) ) {
			return '';
		}

		return home_url( '/calendar/?ViewID=' . $calendar_id );
	}

	private function build_sailboat_url( string $document_id ): string {
		$document_id = trim( $document_id );

		if ( '' === $document_id || ! ctype_digit( $document_id ) ) {
			return '';
		}

		$module_url = $this->resolve_module_url( 'FSTU\\Modules\\Registry\\Sailboats\\Sailboats_List' );

		if ( '' !== $module_url ) {
			return add_query_arg( [ 'sailboat_id' => $document_id ], $module_url );
		}

		return home_url( '/sailboat/?ViewID=' . $document_id );
	}

	private function build_document_url( string $base_url, string $document_id ): string {
		$base_url    = trim( $base_url );
		$document_id = trim( $document_id );

		if ( '' === $base_url || '' === $document_id || ! ctype_digit( $document_id ) ) {
			return '';
		}

		return $base_url . $document_id;
	}

	private function build_sailing_dues_warning( string $prev_year_paid, string $curr_year_paid ): string {
		$prev = (float) $prev_year_paid;
		$curr = (float) $curr_year_paid;

		if ( $prev > 0 && $curr <= 0 ) {
			return 'Сплачено тільки в попередньому році';
		}

		if ( $prev <= 0 && $curr <= 0 ) {
			return 'Не сплачено ні в поточному, ні в попередньому році';
		}

		return 'Актуально';
	}

	private function normalize_dues_flag( string $value ): string {
		return (float) $value > 0 ? 'Сплачено' : 'Не сплачено';
	}

	/**
	 * @param array<int|string,mixed> $action_map
	 * @return array<int,array<string,mixed>>
	 */
	private function build_tab_actions( array $action_map, string $pending_text = '' ): array {
		$actions = [];

		foreach ( $action_map as $label => $enabled ) {
			if ( is_array( $enabled ) ) {
				$action_label = isset( $enabled['label'] ) ? trim( (string) $enabled['label'] ) : '';

				if ( '' === $action_label ) {
					continue;
				}

				$actions[] = [
					'label'   => $action_label,
					'enabled' => ! empty( $enabled['enabled'] ),
					'pending' => isset( $enabled['pending'] ) ? (string) $enabled['pending'] : $pending_text,
					'url'     => isset( $enabled['url'] ) ? $this->normalize_integration_url( (string) $enabled['url'] ) : '',
					'target'  => isset( $enabled['target'] ) ? (string) $enabled['target'] : '',
					'actionKey' => isset( $enabled['actionKey'] ) ? sanitize_key( (string) $enabled['actionKey'] ) : '',
				];

				continue;
			}

			$actions[] = [
				'label'   => (string) $label,
				'enabled' => (bool) $enabled,
				'pending' => $pending_text,
				'url'     => '',
				'target'  => '',
				'actionKey' => '',
			];
		}

		return $actions;
	}

	/**
	 * @return array<string,string>
	 */
	private function get_integration_urls(): array {
		return [
			'member_card_applications' => $this->resolve_module_url( 'FSTU\\Modules\\Registry\\MemberCardApplications\\Member_Card_Applications_List' ),
			'referees'     => $this->resolve_module_url( 'FSTU\\Modules\\Registry\\Referees\\Referees_List' ),
			'payment_docs' => $this->discover_shortcode_page_url( 'fstu_payment_docs' ),
			'steering'     => $this->resolve_module_url( 'FSTU\\Modules\\Registry\\Steering\\Steering_List' ),
			'sailboats'    => $this->resolve_module_url( 'FSTU\\Modules\\Registry\\Sailboats\\Sailboats_List' ),
		];
	}

	/**
	 * @param array<string,mixed>  $member_card
	 * @param array<string,bool>   $member_card_permissions
	 * @param array<string,string> $integration_urls
	 * @return array<int,array<string,mixed>>
	 */
	private function build_member_card_action_items( array $member_card, array $member_card_permissions, array $integration_urls, int $profile_user_id, bool $is_owner ): array {
		$actions  = [];
		$base_url = (string) ( $integration_urls['member_card_applications'] ?? '' );
		$has_card = ! empty( $member_card['UserMemberCard_ID'] ) || '' !== trim( (string) ( $member_card['CardNumber'] ?? '' ) );
		$can_open_module = '' !== $base_url && $profile_user_id > 0;
		$can_self_create = $is_owner && ! empty( $member_card_permissions['canSelfService'] ) && $can_open_module;
		$can_self_reissue = $is_owner && $has_card && ! empty( $member_card_permissions['canReissue'] ) && $can_open_module;
		$can_self_photo = $is_owner && $has_card && ! empty( $member_card_permissions['canUpdatePhoto'] ) && $can_open_module;
		$can_staff_open = $can_open_module && ( ! empty( $member_card_permissions['canView'] ) || ! empty( $member_card_permissions['canManage'] ) );

		if ( $can_self_create ) {
			$actions[] = [
				'label'   => $has_card ? 'Переглянути посвідчення' : 'Оформити посвідчення',
				'enabled' => true,
				'url'     => $this->build_member_card_manage_url( $base_url, $profile_user_id, $has_card ? 'view' : 'create' ),
				'pending' => '',
			];
		}

		if ( $can_self_reissue ) {
			$actions[] = [
				'label'   => 'Перевипустити посвідчення',
				'enabled' => true,
				'url'     => $this->build_member_card_manage_url( $base_url, $profile_user_id, 'reissue' ),
				'pending' => '',
			];
		}

		if ( $can_self_photo ) {
			$actions[] = [
				'label'   => 'Оновити фото посвідчення',
				'enabled' => true,
				'url'     => $this->build_member_card_manage_url( $base_url, $profile_user_id, 'photo' ),
				'pending' => '',
			];
		}

		if ( $can_staff_open && ! $is_owner ) {
			$actions[] = [
				'label'   => 'Відкрити модуль посвідчень',
				'enabled' => true,
				'url'     => $this->build_member_card_manage_url( $base_url, $profile_user_id, $has_card ? 'view' : 'create' ),
				'pending' => '',
			];
		} elseif ( $profile_user_id > 0 && ! $is_owner ) {
			$actions[] = [
				'label'   => 'Відкрити модуль посвідчень',
				'enabled' => false,
				'url'     => '',
				'pending' => '' !== $base_url
					? 'Перехід до модуля посвідчень доступний лише власнику профілю або службовим ролям.'
					: 'Сторінка модуля посвідчень поки не визначена через shortcode або налаштування модуля.',
			];
		}

		return $actions;
	}

	private function resolve_module_url( string $class_name ): string {
		if ( ! class_exists( $class_name ) || ! method_exists( $class_name, 'get_module_url' ) ) {
			return '';
		}

		$url = $class_name::get_module_url( 'default' );

		return $this->normalize_integration_url( is_string( $url ) ? $url : '' );
	}

	private function discover_shortcode_page_url( string $shortcode ): string {
		global $wpdb;

		$shortcode = trim( $shortcode );
		if ( '' === $shortcode ) {
			return '';
		}

		$shortcode_like = '%' . $wpdb->esc_like( '[' . $shortcode ) . '%';
		$post_types     = [ 'page', 'post' ];
		$placeholders   = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );

		$sql = "SELECT ID
			FROM {$wpdb->posts}
			WHERE post_status = 'publish'
				AND post_type IN ({$placeholders})
				AND post_content LIKE %s
			ORDER BY CASE WHEN post_type = 'page' THEN 0 ELSE 1 END ASC, menu_order ASC, ID ASC
			LIMIT 1";

		$params  = array_merge( $post_types, [ $shortcode_like ] );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$page_id = (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );

		if ( $page_id <= 0 ) {
			return '';
		}

		$permalink = get_permalink( $page_id );

		return $this->normalize_integration_url( is_string( $permalink ) ? $permalink : '' );
	}

	private function normalize_integration_url( string $url ): string {
		$url = trim( $url );

		if ( '' === $url ) {
			return '';
		}

		if ( str_starts_with( $url, '/' ) ) {
			return home_url( $url );
		}

		return wp_http_validate_url( $url ) ? $url : '';
	}

	private function build_referees_manage_url( string $base_url, int $profile_user_id ): string {
		$base_url = $this->normalize_integration_url( $base_url );

		if ( '' === $base_url || $profile_user_id <= 0 ) {
			return '';
		}

		$return_url = class_exists( Personal_Cabinet_List::class ) ? Personal_Cabinet_List::get_module_url() : '';
		$return_url = $this->normalize_integration_url( $return_url );

		if ( '' !== $return_url ) {
			$return_url = add_query_arg( [ 'ViewID' => $profile_user_id ], $return_url );
		}

		$args = [
			'fstu_intent' => 'personal_judging',
			'pc_user_id'  => $profile_user_id,
		];

		if ( '' !== $return_url ) {
			$args['pc_return'] = $return_url;
		}

		return add_query_arg( $args, $base_url );
	}

	private function build_steering_manage_url( string $base_url, int $profile_user_id ): string {
		$base_url = $this->normalize_integration_url( $base_url );

		if ( '' === $base_url || $profile_user_id <= 0 ) {
			return '';
		}

		$return_url = class_exists( Personal_Cabinet_List::class ) ? Personal_Cabinet_List::get_module_url() : '';
		$return_url = $this->normalize_integration_url( $return_url );

		if ( '' !== $return_url ) {
			$return_url = add_query_arg( [ 'ViewID' => $profile_user_id ], $return_url );
		}

		$args = [
			'fstu_intent' => 'personal_steering',
			'pc_user_id'  => $profile_user_id,
		];

		if ( '' !== $return_url ) {
			$args['pc_return'] = $return_url;
		}

		return add_query_arg( $args, $base_url );
	}

	private function build_member_card_manage_url( string $base_url, int $profile_user_id, string $action = 'create' ): string {
		$base_url = $this->normalize_integration_url( $base_url );
		$action   = sanitize_key( $action );

		if ( '' === $base_url || $profile_user_id <= 0 ) {
			return '';
		}

		if ( ! in_array( $action, [ 'create', 'view', 'reissue', 'photo' ], true ) ) {
			$action = 'create';
		}

		$return_url = class_exists( Personal_Cabinet_List::class ) ? Personal_Cabinet_List::get_module_url() : '';
		$return_url = $this->normalize_integration_url( $return_url );

		if ( '' !== $return_url ) {
			$return_url = add_query_arg( [ 'ViewID' => $profile_user_id ], $return_url );
		}

		$args = [
			'fstu_intent'             => 'personal_member_card',
			'fstu_user_id'            => $profile_user_id,
			'fstu_member_card_action' => $action,
		];

		if ( '' !== $return_url ) {
			$args['pc_return'] = $return_url;
		}

		return add_query_arg( $args, $base_url );
	}

	private function build_sailboats_create_url( string $base_url, int $profile_user_id ): string {
		$base_url = $this->normalize_integration_url( $base_url );

		if ( '' === $base_url || $profile_user_id <= 0 ) {
			return '';
		}

		$return_url = class_exists( Personal_Cabinet_List::class ) ? Personal_Cabinet_List::get_module_url() : '';
		$return_url = $this->normalize_integration_url( $return_url );

		if ( '' !== $return_url ) {
			$return_url = add_query_arg( [ 'ViewID' => $profile_user_id ], $return_url );
		}

		$args = [
			'fstu_intent' => 'personal_sailing',
			'pc_user_id'  => $profile_user_id,
		];

		if ( '' !== $return_url ) {
			$args['pc_return'] = $return_url;
		}

		return add_query_arg( $args, $base_url );
	}

	/**
	 * @param array<int,array<string,string>> $dues_items
	 */
	private function has_paid_due_for_current_year( array $dues_items ): bool {
		$current_year = (string) current_time( 'Y' );

		foreach ( $dues_items as $item ) {
			$year_name = isset( $item['Year_Name'] ) ? trim( (string) $item['Year_Name'] ) : '';

			if ( $current_year === $year_name ) {
				return true;
			}
		}

		return false;
	}
}

