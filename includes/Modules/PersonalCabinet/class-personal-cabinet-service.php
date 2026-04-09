<?php
namespace FSTU\Modules\PersonalCabinet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Бізнес-сервіс модуля «Особистий кабінет ФСТУ».
 *
 * Version:     1.13.0
 * Date_update: 2026-04-09
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
		$ranks        = $this->repository->get_user_ranks( $user_id );
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

		$profile = [
			'userId'      => $user_id,
			'isOwnProfile' => ! empty( $permissions['isOwner'] ),
			'isGuestView' => ! is_user_logged_in(),
			'displayName' => $display_name,
			'email'       => $can_view_member_data ? (string) $user->user_email : '',
			'roles'       => $can_view_service ? implode( ', ', array_map( 'strval', (array) $user->roles ) ) : '',
			'photoUrl'    => $this->get_photo_url( $user_id ),
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
		$can_view_private    = ! empty( $permissions['canViewPrivate'] );
		$can_view_member_data = $can_view_private || ! empty( $permissions['canManage'] ) || ! empty( $permissions['isOwner'] );
		$dues_items_raw      = isset( $collections['dues'] ) && is_array( $collections['dues'] ) ? $collections['dues'] : [];
		$dues_sail_items_raw = isset( $collections['dues_sail'] ) && is_array( $collections['dues_sail'] ) ? $collections['dues_sail'] : [];
		$member_card      = isset( $profile['memberCard'] ) && is_array( $profile['memberCard'] ) ? $profile['memberCard'] : [];
		$phone_list       = isset( $profile['phoneList'] ) && is_array( $profile['phoneList'] ) ? $profile['phoneList'] : [];
		$settings_values   = isset( $collections['settings'] ) && is_array( $collections['settings'] ) ? $collections['settings'] : [];
		$club_sections    = $this->build_club_sections( isset( $collections['clubs'] ) && is_array( $collections['clubs'] ) ? $collections['clubs'] : [] );
		$city_sections    = $this->build_city_sections( isset( $collections['cities'] ) && is_array( $collections['cities'] ) ? $collections['cities'] : [] );
		$unit_sections    = $this->build_unit_sections( isset( $collections['units'] ) && is_array( $collections['units'] ) ? $collections['units'] : [] );
		$tourism_sections = $this->build_tourism_sections( isset( $collections['tourism'] ) && is_array( $collections['tourism'] ) ? $collections['tourism'] : [] );
		$experience_sections = $this->build_experience_sections( isset( $collections['experience'] ) && is_array( $collections['experience'] ) ? $collections['experience'] : [] );
		$rank_sections       = $this->build_rank_sections( isset( $collections['ranks'] ) && is_array( $collections['ranks'] ) ? $collections['ranks'] : [] );
		$judging_sections    = $this->build_judging_sections( isset( $collections['judging'] ) && is_array( $collections['judging'] ) ? $collections['judging'] : [] );
		$dues_sections       = $this->build_dues_sections(
			$dues_items_raw,
			$settings_values,
			isset( $collections['unit_payment'] ) && is_array( $collections['unit_payment'] ) ? $collections['unit_payment'] : []
		);
		$dues_table_rows     = $this->build_dues_table_rows( $dues_items_raw );
		$sailing_sections    = $this->build_sailing_sections(
			isset( $collections['vessels'] ) && is_array( $collections['vessels'] ) ? $collections['vessels'] : [],
			isset( $collections['certs'] ) && is_array( $collections['certs'] ) ? $collections['certs'] : []
		);
		$dues_sail_sections  = $this->build_dues_sail_sections(
			$dues_sail_items_raw,
			$settings_values
		);
		$dues_sail_table_rows = $this->build_dues_sail_table_rows( $dues_sail_items_raw );
		$integration_urls    = $this->get_integration_urls();
		$profile_user_id     = isset( $profile['userId'] ) ? (int) $profile['userId'] : 0;
		$is_owner            = ! empty( $permissions['isOwner'] );
		$can_edit_profile   = ! empty( $permissions['canEditProfile'] );
		$can_manage_clubs   = ! empty( $permissions['canManageClubs'] );
		$can_manage_cities  = ! empty( $permissions['canManageCities'] );
		$can_manage_units   = ! empty( $permissions['canManageUnits'] );
		$can_manage_tourism = ! empty( $permissions['canManageTourism'] );
		$can_manage_experience = ! empty( $permissions['canManageExperience'] );
		$can_manage_ranks   = ! empty( $permissions['canManageRanks'] );
		$can_manage_judging = ! empty( $permissions['canManageJudging'] );
		$can_manage_dues    = ! empty( $permissions['canManageDues'] );
		$can_pay_online     = ! empty( $permissions['canPayOnline'] );
		$can_view_dues      = $can_manage_dues || $can_pay_online || ! empty( $permissions['isOwner'] );
		$can_manage_sailing = ! empty( $permissions['canManageSailing'] );
		$can_manage_sail_dues = ! empty( $permissions['canManageSailDues'] );

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
					[ 'label' => 'ПІБ', 'value' => (string) ( $profile['displayName'] ?? '' ) ],
					[ 'label' => 'Місто (область)', 'value' => $this->normalize_value( (string) ( $profile['cityRegion'] ?? '' ) ) ],
					[ 'label' => 'Осередок ФСТУ', 'value' => $this->normalize_value( (string) ( $profile['ofstName'] ?? '' ) ) ],
					[ 'label' => 'Стать', 'value' => $this->normalize_sex( (string) ( $profile['sex'] ?? '' ) ) ],
					[ 'label' => 'Фото', 'value' => (string) ( $profile['photoUrl'] ?? '' ), 'type' => 'image' ],
				],
			],
			[
				'title' => 'Членський квиток',
				'items' => [
					[ 'label' => 'Номер', 'value' => $this->normalize_value( isset( $member_card['CardNumber'] ) ? (string) $member_card['CardNumber'] : '' ) ],
					[ 'label' => 'Тип картки', 'value' => $this->normalize_value( isset( $member_card['TypeCard_Name'] ) ? (string) $member_card['TypeCard_Name'] : '' ) ],
					[ 'label' => 'Статус', 'value' => $this->normalize_value( isset( $member_card['StatusCard_Name'] ) ? (string) $member_card['StatusCard_Name'] : '' ) ],
					[ 'label' => 'Сума', 'value' => $this->normalize_sum( isset( $member_card['UserMemberCard_Summa'] ) ? (string) $member_card['UserMemberCard_Summa'] : '' ) ],
				],
			],
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
							[ 'label' => 'Згода на показ персональних даних', 'value' => ! empty( $profile['hasConsent'] ) ? 'Так' : 'Ні' ],
						],
					],
				]
			);
		}

		return [
			'general' => [
				'title'   => 'Загальні',
				'visible' => true,
				'sections' => $general_sections,
				'actions' => $this->build_tab_actions(
					[
						'Редагувати профіль' => $can_edit_profile,
					],
					'Функції редагування профілю буде підключено окремим етапом.'
				),
				'accessNotice' => $can_edit_profile ? 'Ви зможете редагувати загальні дані після підключення mutation-flow.' : 'Вкладка доступна у режимі тільки для перегляду.',
				'isReadOnly' => ! $can_edit_profile,
				'note'    => 'Розділ «Загальні» вже читає реальні дані користувача, фото, місто, осередок і короткий стан членського квитка. Подання заявки на новий членський квиток буде підключене окремим етапом.',
			],
			'private' => [
				'title'   => 'Приватне',
				'visible' => $can_view_private,
				'sections' => [
					[
						'title' => 'Додаткова інформація',
						'items' => [
							[ 'label' => 'Адреса', 'value' => $this->normalize_value( (string) ( $profile['address'] ?? '' ) ) ],
							[ 'label' => 'Місце роботи', 'value' => $this->normalize_value( (string) ( $profile['job'] ?? '' ) ) ],
							[ 'label' => 'Освіта', 'value' => $this->normalize_value( (string) ( $profile['education'] ?? '' ) ) ],
							[ 'label' => 'Додатковий телефон 1', 'value' => $this->normalize_value( (string) ( $profile['phone2'] ?? '' ) ) ],
							[ 'label' => 'Додатковий телефон 2', 'value' => $this->normalize_value( (string) ( $profile['phone3'] ?? '' ) ) ],
							[ 'label' => 'Телефон родичів', 'value' => $this->normalize_value( (string) ( $profile['familyPhone'] ?? '' ) ) ],
						],
					],
				],
				'actions' => $this->build_tab_actions(
					[
						'Редагувати приватні дані' => $can_edit_profile,
					],
					'Приватні поля будуть доступні для редагування на наступному етапі.'
				),
				'accessNotice' => $can_edit_profile ? 'Редагування приватних даних буде відкрито окремим mutation-flow.' : 'Приватні дані показані у режимі лише для перегляду.',
				'isReadOnly' => ! $can_edit_profile,
				'note'    => 'Приватні дані доступні лише для власника профілю та дозволених ролей.',
			],
			'service' => [
				'title'   => 'Службове',
				'visible' => $can_view_service,
				'sections' => [
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
						'title' => 'Банківські параметри',
						'items' => [
							[ 'label' => 'ІПН', 'value' => $this->normalize_value( (string) ( $profile['ipn'] ?? '' ) ) ],
							[ 'label' => 'Назва банку', 'value' => $this->normalize_value( (string) ( $profile['bankName'] ?? '' ) ) ],
							[ 'label' => 'IBAN', 'value' => $this->normalize_value( (string) ( $profile['iban'] ?? '' ) ) ],
						],
					],
				],
				'actions' => [],
				'accessNotice' => 'Службова вкладка доступна лише визначеним ролям і працює тільки в read-only режимі.',
				'isReadOnly' => true,
				'note'    => 'Службова вкладка вже читає реальні технічні й банківські поля користувача.',
			],
			'clubs' => [
				'title'   => 'Клуби',
				'visible' => true,
				'sections' => $club_sections,
				'actions' => $this->build_tab_actions(
					[
						'Додати клуб' => $can_manage_clubs,
						'Видалити клуб' => $can_manage_clubs,
					],
					'CRUD клубів буде підключено окремим етапом.'
				),
				'accessNotice' => $can_manage_clubs ? 'Ви маєте право керувати клубами після підключення mutation-flow.' : 'Вкладка доступна лише для перегляду.',
				'isReadOnly' => ! $can_manage_clubs,
				'note'    => empty( $club_sections ) ? 'Користувач поки не прив’язаний до жодного клубу.' : 'Показано історію членства у спортивних клубах. CRUD буде перенесено окремим інкрементом.',
			],
			'city' => [
				'title'   => 'Місто',
				'visible' => true,
				'sections' => $city_sections,
				'actions' => $this->build_tab_actions(
					[
						'Додати місто' => $can_manage_cities,
						'Оновити історію міст' => $can_manage_cities,
					],
					'Mutation-flow для міст буде підключено окремим етапом.'
				),
				'accessNotice' => $can_manage_cities ? 'Ви маєте право керувати історією міст після підключення mutation-flow.' : 'Вкладка доступна лише для перегляду.',
				'isReadOnly' => ! $can_manage_cities,
				'note'    => empty( $city_sections ) ? 'Історія міст проживання поки відсутня.' : 'Показано актуальне місто проживання та історію змін.',
			],
			'units' => [
				'title'   => 'Осередоки',
				'visible' => true,
				'sections' => $unit_sections,
				'actions' => $this->build_tab_actions(
					[
						'Додати осередок' => $can_manage_units,
						'Оновити осередок' => $can_manage_units,
					],
					'Mutation-flow для осередків буде підключено окремим етапом.'
				),
				'accessNotice' => $can_manage_units ? 'Ви маєте право керувати осередками після підключення mutation-flow.' : 'Вкладка доступна лише для перегляду.',
				'isReadOnly' => ! $can_manage_units,
				'note'    => empty( $unit_sections ) ? 'Історія членства в осередках ФСТУ поки відсутня.' : 'Показано актуальний осередок та історію членства в осередках ФСТУ.',
			],
			'tourism' => [
				'title'   => 'Види туризму',
				'visible' => true,
				'sections' => $tourism_sections,
				'actions' => $this->build_tab_actions(
					[
						'Додати вид туризму' => $can_manage_tourism,
						'Видалити вид туризму' => $can_manage_tourism,
					],
					'Mutation-flow для видів туризму буде підключено окремим етапом.'
				),
				'accessNotice' => $can_manage_tourism ? 'Ви маєте право керувати видами туризму після підключення mutation-flow.' : 'Вкладка доступна лише для перегляду.',
				'isReadOnly' => ! $can_manage_tourism,
				'note'    => empty( $tourism_sections ) ? 'Види туризму для користувача поки не визначені.' : 'Показано історію прив’язки видів туризму. Mutation-flow буде перенесено окремим етапом.',
			],
			'experience' => [
				'title'   => 'Досвід',
				'visible' => true,
				'sections' => $experience_sections,
				'actions' => $this->build_tab_actions(
					[
						'Додати / оновити довідку' => $can_manage_experience,
					],
					'Редагування довідки за похід буде підключено окремим етапом.'
				),
				'accessNotice' => $can_manage_experience ? 'Ви маєте право керувати довідками за похід після підключення mutation-flow.' : 'Вкладка доступна лише для перегляду.',
				'isReadOnly' => ! $can_manage_experience,
				'note'    => empty( $experience_sections ) ? 'Спортивний туристський досвід поки не вказаний.' : 'Показано read-only дані про досвід участі у спортивних походах. Додавання або редагування довідки буде перенесено окремим етапом.',
			],
			'ranks' => [
				'title'   => 'Розряди',
				'visible' => true,
				'sections' => $rank_sections,
				'actions' => $this->build_tab_actions(
					[
						'Додати розряд' => $can_manage_ranks,
					],
					'Mutation-flow для розрядів буде підключено окремим етапом.'
				),
				'accessNotice' => $can_manage_ranks ? 'Ви маєте право керувати розрядами після підключення mutation-flow.' : 'Вкладка доступна лише для перегляду.',
				'isReadOnly' => ! $can_manage_ranks,
				'note'    => empty( $rank_sections ) ? 'Спортивні розряди або звання поки не вказані.' : 'Показано read-only дані про спортивні розряди та звання. Mutation-flow буде перенесено окремим етапом.',
			],
			'judging' => [
				'title'   => 'Суддівство',
				'visible' => true,
				'sections' => $judging_sections,
				'actions' => $this->build_tab_actions(
					[
						[
							'label'   => 'Додати суддівську категорію',
							'enabled' => $can_manage_judging && '' !== ( $integration_urls['referees'] ?? '' ) && $profile_user_id > 0,
							'url'     => $this->build_referees_manage_url( (string) ( $integration_urls['referees'] ?? '' ), $profile_user_id ),
							'pending' => 'Модальна форма суддівства буде доступна після визначення сторінки Referees та прав керування.',
						],
						[
							'label'   => 'Відкрити реєстр суддів ФСТУ',
							'enabled' => '' !== ( $integration_urls['referees'] ?? '' ),
							'url'     => (string) ( $integration_urls['referees'] ?? '' ),
							'target'  => '_blank',
							'pending' => 'Сторінка реєстру суддів поки не визначена через shortcode або налаштування модуля.',
						],
					],
					'Reuse інтеграція з модулем Referees буде підключена окремим етапом.'
				),
				'accessNotice' => $can_manage_judging ? 'Ви маєте право керувати суддівськими категоріями після підключення reuse-flow.' : 'Вкладка доступна лише для перегляду.',
				'isReadOnly' => ! $can_manage_judging,
				'note'    => empty( $judging_sections ) ? 'Суддівська категорія поки не вказана.' : 'Показано read-only дані про суддівські категорії. Reuse інтеграція з модулем Referees для додавання/редагування буде виконана окремим етапом.',
			],
			'dues' => [
				'title'   => 'Внески',
				'visible' => $can_view_dues,
				'sections' => $dues_sections,
				'table'    => [
					'type'           => 'dues',
					'rows'           => $dues_table_rows,
					'defaultPerPage' => 10,
					'emptyMessage'   => __( 'Записи членських внесків відсутні.', 'fstu' ),
				],
				'actions' => $this->build_tab_actions(
					[
						[
							'label'   => 'Додати квитанцію',
							'enabled' => $can_manage_dues,
							'actionKey' => 'upload_dues_receipt',
							'pending' => 'Додавання квитанції доступне лише користувачам із правом керування внесками.',
						],
						'ОПЛАТА' => $can_pay_online,
						[
							'label'   => 'Відкрити реєстр платіжних документів',
							'enabled' => '' !== ( $integration_urls['payment_docs'] ?? '' ),
							'url'     => (string) ( $integration_urls['payment_docs'] ?? '' ),
							'target'  => '_blank',
							'pending' => 'Сторінка реєстру платіжних документів поки не визначена через shortcode [fstu_payment_docs].',
						],
						[
							'label'   => 'PORTMONE',
							'enabled' => $can_pay_online && ! $this->has_paid_due_for_current_year( isset( $collections['dues'] ) && is_array( $collections['dues'] ) ? $collections['dues'] : [] ),
							'actionKey' => 'portmone',
							'pending' => $this->has_paid_due_for_current_year( isset( $collections['dues'] ) && is_array( $collections['dues'] ) ? $collections['dues'] : [] )
								? sprintf( 'Внесок за %d рік уже зафіксовано.', (int) current_time( 'Y' ) )
								: 'Portmone-flow готується сервером і запускається без inline-форм у шаблоні.',
						],
					],
					'Додавання квитанції та онлайн-оплата працюють через окремі контрольовані сценарії без inline-форм у шаблоні.'
				),
				'accessNotice' => $can_manage_dues || $can_pay_online ? 'Ви можете оплачувати внески online через Portmone та додавати квитанції згідно з вашими правами.' : 'Вкладка доступна лише для перегляду.',
				'isReadOnly' => ! ( $can_manage_dues || $can_pay_online ),
				'note'    => empty( $dues_sections ) ? 'Членські внески або реквізити поки відсутні.' : 'Показано історію членських внесків і базові реквізити для сплати. Portmone запускається через окремий безпечний payment payload, а квитанція додається через контрольований mutation-flow.',
			],
			'sailing' => [
				'title'   => 'Вітрильництво',
				'visible' => true,
				'sections' => $sailing_sections,
				'actions' => $this->build_tab_actions(
					[
						[
							'label'   => 'Подати / редагувати заявку стернового',
							'enabled' => $can_manage_sailing && '' !== ( $integration_urls['steering'] ?? '' ) && $profile_user_id > 0,
							'url'     => $this->build_steering_manage_url( (string) ( $integration_urls['steering'] ?? '' ), $profile_user_id ),
							'pending' => 'Reuse-flow стернових буде доступний після визначення сторінки Steering та прав керування.',
						],
						[
							'label'   => 'Подати заявку до суднового реєстру',
							'enabled' => $can_manage_sailing && $is_owner && '' !== ( $integration_urls['sailboats'] ?? '' ) && $profile_user_id > 0,
							'url'     => $this->build_sailboats_create_url( (string) ( $integration_urls['sailboats'] ?? '' ), $profile_user_id ),
							'pending' => 'Auto-open create-flow суднового реєстру доступний лише для власного профілю.',
						],
						[
							'label'   => 'Відкрити реєстр суден ФСТУ',
							'enabled' => '' !== ( $integration_urls['sailboats'] ?? '' ),
							'url'     => (string) ( $integration_urls['sailboats'] ?? '' ),
							'target'  => '_blank',
							'pending' => 'Сторінка реєстру суден поки не визначена через shortcode або налаштування модуля.',
						],
						[
							'label'   => 'Відкрити реєстр стернових ФСТУ',
							'enabled' => '' !== ( $integration_urls['steering'] ?? '' ),
							'url'     => (string) ( $integration_urls['steering'] ?? '' ),
							'target'  => '_blank',
							'pending' => 'Сторінка реєстру стернових поки не визначена через shortcode або налаштування модуля.',
						],
					],
					'Керування суднами та посвідченнями виконується через чинні модулі Steering і Sailboats без дублювання форм у кабінеті.'
				),
				'accessNotice' => $can_manage_sailing ? 'Ви маєте право керувати вітрильним доменом після підключення mutation-flow.' : 'Вкладка доступна лише для перегляду.',
				'isReadOnly' => ! $can_manage_sailing,
				'note'    => empty( $sailing_sections ) ? 'Вітрильні судна або посвідчення поки відсутні.' : 'Показано read-only дані про судна та вітрильні посвідчення користувача.',
			],
			'dues_sail' => [
				'title'   => 'Внески (вітр.)',
				'visible' => ! empty( $permissions['canViewSailDues'] ),
				'sections' => $dues_sail_sections,
				'table'    => [
					'type'           => 'dues_sail',
					'rows'           => $dues_sail_table_rows,
					'defaultPerPage' => 10,
					'emptyMessage'   => __( 'Записи вітрильних внесків відсутні.', 'fstu' ),
				],
				'actions' => $this->build_tab_actions(
					[
						'Додати вітрильний внесок' => $can_manage_sail_dues,
					],
					'Mutation-flow для вітрильних внесків буде підключено окремим етапом.'
				),
				'accessNotice' => $can_manage_sail_dues ? 'Ви маєте право керувати вітрильними внесками після підключення mutation-flow.' : 'Вкладка доступна лише для перегляду.',
				'isReadOnly' => ! $can_manage_sail_dues,
				'note'    => empty( $dues_sail_sections ) ? 'Вітрильні внески або реквізити поки відсутні.' : 'Показано read-only історію членських внесків вітрильників та службові реквізити для оплати.',
			],
		];
	}

	private function get_photo_url( int $user_id ): string {
		$photo_path = ABSPATH . 'photo/' . $user_id . '.jpg';

		if ( file_exists( $photo_path ) ) {
			return site_url( '/photo/' . $user_id . '.jpg' );
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
		$payment_info_items = [
			[ 'label' => 'Річний внесок ФСТУ', 'value' => $annual_fee ],
			[ 'label' => 'Реквізити для сплати членських внесків', 'value' => 'https://www.fstu.com.ua/rekviziti-dlya-oplati-organizacijnix-vneskiv-fstu/', 'type' => 'link' ],
		];

		if ( ! empty( $unit_payment['Unit_AnnualFee'] ) ) {
			$payment_info_items[] = [ 'label' => 'Річний внесок в осередок', 'value' => $this->normalize_sum( $unit_payment['Unit_AnnualFee'] ) ];
		}
		if ( ! empty( $unit_payment['Unit_UrlPay'] ) ) {
			$payment_info_items[] = [ 'label' => 'Посилання для сплати в осередок', 'value' => $this->normalize_value( $unit_payment['Unit_UrlPay'] ), 'type' => 'link' ];
		}
		if ( ! empty( $unit_payment['Unit_PaymentCard'] ) ) {
			$payment_info_items[] = [ 'label' => 'Карта для сплати в осередок', 'value' => $this->normalize_value( $unit_payment['Unit_PaymentCard'] ) ];
		}

		$sections[] = [
			'title' => 'Реквізити та сплата',
			'items' => $payment_info_items,
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
	 * @param array<int,array<string,string>> $vessels
	 * @param array<int,array<string,string>> $certs
	 * @return array<int,array<string,mixed>>
	 */
	private function build_sailing_sections( array $vessels, array $certs ): array {
		$sections = [];

		foreach ( $vessels as $index => $item ) {
			$warning = $this->build_sailing_dues_warning( $item['PrevYearDuesSail'] ?? '', $item['CurrYearDuesSail'] ?? '' );
			$sections[] = [
				'title' => 'Судно #' . ( $index + 1 ) . ': ' . $this->normalize_value( $item['Sailboat_Name'] ?? '' ),
				'items' => [
					[ 'label' => 'Назва', 'value' => $this->normalize_value( $item['Sailboat_Name'] ?? '' ) ],
					[ 'label' => 'Реєстраційний №', 'value' => $this->normalize_value( $item['RegNumber'] ?? '' ) ],
					[ 'label' => 'Посилання на реєстр судна', 'value' => $this->build_sailboat_url( $item['AppShipTicket_ID'] ?? '' ), 'type' => 'link' ],
					[ 'label' => '№ на вітрилі', 'value' => $this->normalize_value( $item['Sailboat_NumberSail'] ?? '' ) ],
					[ 'label' => 'Дата реєстрації', 'value' => $this->format_date( $item['AppShipTicket_DateCreate'] ?? '' ) ],
					[ 'label' => 'Статус', 'value' => $this->normalize_value( $item['Verification_Name'] ?? '' ) ],
					[ 'label' => 'Сума сплати', 'value' => $this->normalize_sum( $item['AppShipTicket_Summa'] ?? '' ) ],
					[ 'label' => 'Попередній рік (вітр. внесок)', 'value' => $this->normalize_dues_flag( $item['PrevYearDuesSail'] ?? '' ) ],
					[ 'label' => 'Поточний рік (вітр. внесок)', 'value' => $this->normalize_dues_flag( $item['CurrYearDuesSail'] ?? '' ) ],
					[ 'label' => 'Попередження', 'value' => $warning ],
				],
			];
		}

		foreach ( $certs as $index => $item ) {
			$type = $this->normalize_value( $item['Type'] ?? '' );
			$sections[] = [
				'title' => 'Посвідчення #' . ( $index + 1 ) . ': ' . $type,
				'items' => [
					[ 'label' => 'Тип', 'value' => $type ],
					[ 'label' => 'Номер', 'value' => $this->normalize_value( $item['Number'] ?? '' ) ],
					[ 'label' => 'Статус', 'value' => $this->normalize_value( $item['Status'] ?? '' ) ],
					[ 'label' => 'Дата створення', 'value' => $this->format_date( $item['Created_At'] ?? '' ) ],
					[ 'label' => 'Дата оплати / реєстрації', 'value' => $this->format_date( $item['Paid_At'] ?? '' ) ],
					[ 'label' => 'Посилання на документ', 'value' => $this->build_document_url( $item['Base_Url'] ?? '', $item['Document_ID'] ?? '' ), 'type' => 'link' ],
				],
			];
		}

		return $sections;
	}

	/**
	 * @param array<int,array<string,string>> $items
	 * @param array<string,string> $settings
	 * @return array<int,array<string,mixed>>
	 */
	private function build_dues_sail_sections( array $items, array $settings ): array {
		$sections = [];

		$sections[] = [
			'title' => 'Реквізити та посилання',
			'items' => [
				[ 'label' => 'Річний внесок вітрильників', 'value' => $this->normalize_sum( $settings['PaymentRegistration'] ?? '' ) ],
				[ 'label' => 'Фінансист / отримувач', 'value' => $this->normalize_value( $settings['NameSailboatFinancier'] ?? '' ) ],
				[ 'label' => 'Карта фінансиста', 'value' => $this->normalize_value( $settings['FinancierCardNumber'] ?? '' ) ],
				[ 'label' => 'Посилання для сплати внесків вітрильників', 'value' => $this->normalize_value( $settings['UrlPayFinancierCard'] ?? '' ), 'type' => 'link' ],
				[ 'label' => 'Посилання на фінансиста', 'value' => $this->normalize_value( $settings['UrlSailboatFinancier'] ?? '' ), 'type' => 'link' ],
				[ 'label' => 'Карта для оплати документів', 'value' => $this->normalize_value( $settings['CardNumberToPayDocuments'] ?? '' ) ],
				[ 'label' => 'Посилання для оплати документів', 'value' => $this->normalize_value( $settings['UrlPayDocuments'] ?? '' ), 'type' => 'link' ],
				[ 'label' => 'ПОЛОЖЕННЯ про реєстрацію', 'value' => 'https://drive.google.com/file/d/1EV7nIMphPMG5YGh9DZ3VmCDyBNUnMH79/view?usp=sharing', 'type' => 'link' ],
				[ 'label' => 'Правила та порядок реєстрації суден', 'value' => 'https://docs.google.com/document/d/1TnHnEQS3FZl_6JJ1ni1vYgFLUKO2MfFDtGCMHLRCIuU/edit?usp=sharing', 'type' => 'link' ],
			],
		];

		return $sections;
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
			'referees'     => $this->resolve_module_url( 'FSTU\\Modules\\Registry\\Referees\\Referees_List' ),
			'payment_docs' => $this->discover_shortcode_page_url( 'fstu_payment_docs' ),
			'steering'     => $this->resolve_module_url( 'FSTU\\Modules\\Registry\\Steering\\Steering_List' ),
			'sailboats'    => $this->resolve_module_url( 'FSTU\\Modules\\Registry\\Sailboats\\Sailboats_List' ),
		];
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

