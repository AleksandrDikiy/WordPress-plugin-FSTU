<?php
/**
 * AJAX-обробники для модальних вікон (Картка, Клуб, Протокол, Звіт) модуля "Реєстр".
 *
 * Version:     1.2.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Registry
 */

namespace FSTU\Registry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Registry_Modals_Ajax {

	/**
	 * Реєструє AJAX хуки WordPress для модальних вікон.
	 */
	public function init(): void {
		// Авторизовані користувачі
		add_action( 'wp_ajax_fstu_get_member_card', [ $this, 'handle_get_member_card' ] );
		add_action( 'wp_ajax_fstu_get_club_info', [ $this, 'handle_get_club_info' ] );
		add_action( 'wp_ajax_fstu_get_protocol', [ $this, 'handle_get_protocol' ] );
		add_action( 'wp_ajax_fstu_get_report', [ $this, 'handle_get_report' ] );
	}

    /**
     * Отримує дані для вкладок "Картки члена ФСТУ".
     */
    public function handle_get_member_card(): void {
        check_ajax_referer( Registry_List::NONCE_ACTION, 'nonce' );

        $user_id = absint( $_POST['user_id'] ?? 0 );
        if ( ! $user_id ) {
            wp_send_json_error( [ 'message' => 'Невірний ID користувача.' ] );
        }

        $user = get_userdata( $user_id );
        if ( ! $user ) {
            wp_send_json_error( [ 'message' => 'Користувача не знайдено.' ] );
        }

        // ── Логіка доступу до персональних даних ──
        $current_user    = wp_get_current_user();
        $current_user_id = $current_user->ID;
        $user_roles      = (array) $current_user->roles;

        $is_admin        = in_array( 'administrator', $user_roles, true );
        $is_registrar    = in_array( 'userregistrar', $user_roles, true );
        $is_fstu_member  = in_array( 'userfstu', $user_roles, true );
        // Нові ролі для вітрильників
        $is_sail_admin   = in_array( 'sailingadmin', $user_roles, true );
        $is_sail_fin     = in_array( 'sailingfinancier', $user_roles, true );

        $is_self         = ( $current_user_id === $user_id );

        $flag_view   = get_user_meta( $user_id, 'FlagView', true );
        $has_consent = ( $flag_view == '1' || $flag_view === '' );

        // Права на вкладки
        $can_see_personal = $is_admin || $is_self || ( $is_fstu_member && $has_consent );
        $can_see_private  = $is_admin || $is_self || $is_registrar;
        $can_see_service  = $is_admin;

        // Доступ до вітрильних вкладок
        $can_see_sailing  = $is_admin || $is_sail_admin || $is_sail_fin;

        $data['permissions'] = [
            'can_see_sailing' => $can_see_sailing
        ];
        // ── Форматування загальних даних ──
        $birth_date_raw = get_user_meta( $user_id, 'BirthDate', true );
        $birth_date_formatted = '—';
        if ( ! empty( $birth_date_raw ) ) {
            $birth_date_obj = date_create( $birth_date_raw );
            if ( $birth_date_obj ) {
                $birth_date_formatted = date_format( $birth_date_obj, 'd.m.Y' );
            }
        }

        $sex_raw = get_user_meta( $user_id, 'Sex', true );
        $sex_formatted = ($sex_raw === 'Ж') ? 'Жіноча' : (($sex_raw === 'Ч') ? 'Чоловіча' : '—');

        $phones = [];
        if ( $p1 = get_user_meta( $user_id, 'PhoneMobile', true ) ) $phones[] = $p1;
        if ( $p2 = get_user_meta( $user_id, 'Phone2', true ) ) $phones[] = $p2;
        if ( $p3 = get_user_meta( $user_id, 'Phone3', true ) ) $phones[] = $p3;
        $phone_display = !empty($phones) ? implode(', ', $phones) : '—';

        $skype    = get_user_meta( $user_id, 'Skype', true ) ?: '—';
        $facebook = get_user_meta( $user_id, 'FaceBook', true ) ?: '—';
        if ( $facebook !== '—' && ! str_starts_with( $facebook, 'http' ) ) {
            $facebook = 'https://facebook.com/' . ltrim( $facebook, '/' );
        }

        $photo_path = ABSPATH . 'photo/' . $user_id . '.jpg';
        $photo_url  = file_exists( $photo_path )
            ? site_url( '/photo/' . $user_id . '.jpg' )
            : get_avatar_url( 0, [ 'size' => 150, 'default' => 'mp', 'force_default' => true ] );

        // ── 1. Вкладка "Загальні" ──
        $data = [
            'general' => [
                'name'             => esc_html( trim( $user->last_name . ' ' . $user->first_name . ' ' . get_user_meta( $user_id, 'Patronymic', true ) ) ),
                'email'            => $can_see_personal ? esc_html( $user->user_email ) : 'Приховано',
                'phone'            => $can_see_personal ? esc_html( $phone_display ) : 'Приховано',
                'birth_date'       => $can_see_personal ? esc_html( $birth_date_formatted ) : 'Приховано',
                'sex'              => $can_see_personal ? esc_html( $sex_formatted ) : 'Приховано',
                'skype'            => $can_see_personal ? esc_html( $skype ) : 'Приховано',
                'facebook'         => $can_see_personal ? ( $facebook === '—' ? '—' : esc_url( $facebook ) ) : 'Приховано',
                'photo_url'        => $can_see_personal ? esc_url( $photo_url ) : esc_url( get_avatar_url( 0, [ 'size' => 150, 'default' => 'mp', 'force_default' => true ] ) ),
                'has_consent'      => $has_consent,
                'can_see_personal' => $can_see_personal,
            ],
        ];

        // ── 2. Вкладка "Приватне" (Тільки для Реєстраторів, Власника, Адміна) ──
        if ( $can_see_private ) {
            $data['private'] = [
                'address'   => esc_html( get_user_meta( $user_id, 'Adr', true ) ?: '—' ),
                'job'       => esc_html( get_user_meta( $user_id, 'Job', true ) ?: '—' ),
                'education' => esc_html( get_user_meta( $user_id, 'Education', true ) ?: '—' ),
                'family_ph' => esc_html( get_user_meta( $user_id, 'PhoneFamily', true ) ?: '—' ),
            ];
        }

        // ── 3. Вкладка "Службове" (Тільки для Адмінів) ──
        if ( $can_see_service ) {
            global $wpdb;

            // phpcs:disable WordPress.DB.DirectDatabaseQuery
            $tg_code = $wpdb->get_var( $wpdb->prepare( "SELECT VerificationCode FROM {$wpdb->users} WHERE ID = %d LIMIT 1", $user_id ) );
            $tg_id   = $wpdb->get_var( $wpdb->prepare( "SELECT TelegramID FROM vUserTelegram WHERE User_ID = %d ORDER BY UserTelegram_DateCreate DESC LIMIT 1", $user_id ) );

            // Прямі запити до таблиці UserParams (обходимо зламану SQL-функцію)
            $ipn  = $wpdb->get_var( $wpdb->prepare( "SELECT UserParams_Value FROM UserParams WHERE User_ID = %d AND UserParams_Name = 'IPN'", $user_id ) );
            $bank = $wpdb->get_var( $wpdb->prepare( "SELECT UserParams_Value FROM UserParams WHERE User_ID = %d AND UserParams_Name = 'BankName'", $user_id ) );
            $iban = $wpdb->get_var( $wpdb->prepare( "SELECT UserParams_Value FROM UserParams WHERE User_ID = %d AND UserParams_Name = 'IBAN'", $user_id ) );
            // phpcs:enable

            $data['service'] = [
                'id'         => $user_id,
                'login'      => esc_html( $user->user_login ),
                'last_login' => esc_html( get_user_meta( $user_id, 'last_login', true ) ?: '—' ),
                'registered' => esc_html( $user->user_registered ),
                'tg_active'  => esc_html( $user->TelegramVerification ?? '—' ),
                'tg_code'    => esc_html( $tg_code ?: '—' ),
                'tg_id'      => esc_html( $tg_id ?: '—' ),
                'ipn'        => esc_html( $ipn ?: '—' ),
                'bank'       => esc_html( $bank ?: '—' ),
                'iban'       => esc_html( $iban ?: '—' ),
            ];
        }
        // ── 4. Вкладка "Клуби" ──
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $clubs_raw = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT c.Club_Name, c.Club_WWW, c.Club_Adr 
				 FROM UserClub u 
				 JOIN S_Club c ON c.Club_ID = u.Club_ID 
				 WHERE u.User_ID = %d 
				 ORDER BY u.UserClub_Date DESC",
                $user_id
            ),
            ARRAY_A
        );

        $clubs_data = [];
        if ( ! empty( $clubs_raw ) ) {
            foreach ( $clubs_raw as $c ) {
                $clubs_data[] = [
                    'name' => esc_html( $c['Club_Name'] ),
                    'www'  => esc_url( $c['Club_WWW'] ),
                    'adr'  => esc_html( $c['Club_Adr'] ?: '—' ),
                ];
            }
        }
        $data['clubs'] = $clubs_data;
        // ── 5. Вкладка "Міста" ──
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $cities_raw = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT r.Region_Name, c.City_Name, u.UserCity_DateCreate 
				 FROM UserCity u 
				 JOIN S_City c ON c.City_ID = u.City_ID 
				 JOIN S_Region r ON r.Region_ID = c.Region_ID
				 WHERE u.User_ID = %d 
				 ORDER BY u.UserCity_DateCreate DESC",
                $user_id
            ),
            ARRAY_A
        );

        $cities_data = [];
        if ( ! empty( $cities_raw ) ) {
            foreach ( $cities_raw as $city ) {
                $cities_data[] = [
                    'city'   => esc_html( $city['City_Name'] ),
                    'region' => esc_html( $city['Region_Name'] ),
                    'date'   => ! empty( $city['UserCity_DateCreate'] ) ? date( 'd.m.Y', strtotime( $city['UserCity_DateCreate'] ) ) : '—',
                ];
            }
        }
        $data['cities'] = $cities_data;
        // ── 6. Вкладка "Види туризму" ──
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $tourism_raw = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.TourismType_Name, u.UserTourismType_DateCreate 
				 FROM UserTourismType u 
				 JOIN S_TourismType t ON t.TourismType_ID = u.TourismType_ID 
				 WHERE u.User_ID = %d 
				 ORDER BY u.UserTourismType_DateCreate DESC",
                $user_id
            ),
            ARRAY_A
        );

        $tourism_data = [];
        if ( ! empty( $tourism_raw ) ) {
            foreach ( $tourism_raw as $item ) {
                $tourism_data[] = [
                    'name' => esc_html( $item['TourismType_Name'] ),
                    'date' => ! empty( $item['UserTourismType_DateCreate'] ) ? date( 'd.m.Y', strtotime( $item['UserTourismType_DateCreate'] ) ) : '—',
                ];
            }
        }
        $data['tourism'] = $tourism_data;
        // ── 7. Вкладка "Досвід" (Походи) ──
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $experience_raw = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.ParticipationType_Name, c.Calendar_ID, c.Calendar_DateBegin, 
				        c.Calendar_DateEnd, c.Calendar_Name, h.HikingCategory_Name, 
				        h.UserHikingCategory_UrlDivodka, t.TourismType_Name
				 FROM UserCalendar u
				 JOIN Calendar c ON c.Calendar_ID = u.Calendar_ID
				 JOIN S_ParticipationType p ON p.ParticipationType_ID = u.ParticipationType_ID
				 JOIN S_TourismType t ON t.TourismType_ID = c.TourismType_ID
				 LEFT JOIN vUserHikingCategory h ON h.UserCalendar_ID = u.UserCalendar_ID 
				 WHERE u.User_ID = %d
				   AND c.EventType_ID = 2
				   AND h.HikingCategory_ID IS NOT NULL
				 ORDER BY c.Calendar_DateBegin DESC",
                $user_id
            ),
            ARRAY_A
        );

        $experience_data = [];
        if ( ! empty( $experience_raw ) ) {
            foreach ( $experience_raw as $exp ) {
                $experience_data[] = [
                    'category' => esc_html( $exp['HikingCategory_Name'] ),
                    'role'     => esc_html( $exp['ParticipationType_Name'] ),
                    'event'    => esc_html( $exp['Calendar_Name'] ),
                    'event_id' => (int) $exp['Calendar_ID'],
                    'tourism'  => esc_html( $exp['TourismType_Name'] ),
                    'dates'    => date( 'd.m.Y', strtotime( $exp['Calendar_DateBegin'] ) ) . ' - ' . date( 'd.m.Y', strtotime( $exp['Calendar_DateEnd'] ) ),
                    'url'      => esc_url( $exp['UserHikingCategory_UrlDivodka'] ?? '' ),
                ];
            }
        }
        $data['experience'] = $experience_data;
        // ── 8. Вкладка "Розряди" ──
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $ranks_raw = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT s.SportsCategories_Name, t.TourismType_Name, c.Calendar_ID, c.Calendar_Name,
				        c.Calendar_DateBegin, c.Calendar_DateEnd, s.UserSportCategories_DatePrikaz, 
				        s.UserSportCategories_UrlPrikaz
				 FROM UserCalendar u
				 JOIN Calendar c ON c.Calendar_ID = u.Calendar_ID
				 JOIN S_TourismType t ON t.TourismType_ID = c.TourismType_ID 
				 LEFT JOIN vUserSportCategories s ON s.UserCalendar_ID = u.UserCalendar_ID 
				 WHERE u.User_ID = %d
				   AND c.EventType_ID = 1
				   AND s.SportsCategories_ID IS NOT NULL
				 ORDER BY c.Calendar_DateBegin DESC",
                $user_id
            ),
            ARRAY_A
        );
        //
        $ranks_data = [];
        if ( ! empty( $ranks_raw ) ) {
            foreach ( $ranks_raw as $rank ) {
                $ranks_data[] = [
                    'name'     => esc_html( $rank['SportsCategories_Name'] ),
                    'tourism'  => esc_html( $rank['TourismType_Name'] ),
                    'event'    => esc_html( $rank['Calendar_Name'] ),
                    'event_id' => (int) $rank['Calendar_ID'],
                    'dates'    => date( 'd.m.Y', strtotime( $rank['Calendar_DateBegin'] ) ) . ' - ' . date( 'd.m.Y', strtotime( $rank['Calendar_DateEnd'] ) ),
                    'prikaz'   => ! empty( $rank['UserSportCategories_DatePrikaz'] ) ? date( 'd.m.Y', strtotime( $rank['UserSportCategories_DatePrikaz'] ) ) : '—',
                    'url'      => esc_url( $rank['UserSportCategories_UrlPrikaz'] ?? '' ),
                ];
            }
        }
        $data['ranks'] = $ranks_data;
        // ── 9. Вкладка "Суддівство" ──
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $judging_raw = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT RefereeCategory_Name, Referee_DateCreate 
				 FROM vReferee 
				 WHERE User_ID = %d 
				 ORDER BY RefereeCategory_Order",
                $user_id
            ),
            ARRAY_A
        );

        $judging_data = [];
        if ( ! empty( $judging_raw ) ) {
            foreach ( $judging_raw as $judge ) {
                $judging_data[] = [
                    'category' => esc_html( $judge['RefereeCategory_Name'] ),
                    'date'     => ! empty( $judge['Referee_DateCreate'] ) ? date( 'd.m.Y', strtotime( $judge['Referee_DateCreate'] ) ) : '—',
                ];
            }
        }
        $data['judging'] = $judging_data;
        // ── 10. Внески (Загальні) ──
        // Додали Dues_ShopBillid та Dues_ApprovalCode для еквайрингу
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $data['dues'] = $wpdb->get_results( $wpdb->prepare(
            "SELECT d.Year_Name, d.Dues_Summa, d.Dues_URL, d.Dues_DateCreate, u.FIOshort as financier, d.DuesType_Name, d.Dues_ShopBillid, d.Dues_ApprovalCode
			 FROM vUserDues d 
			 JOIN vUser u ON u.User_ID = d.UserCreate
			 WHERE d.User_ID = %d ORDER BY d.Year_Name DESC", $user_id
        ), ARRAY_A ) ?: [];

        // ── 11. Вітрильний блок (Тільки за наявності прав) ──
        if ( $can_see_sailing ) {
            // Внески вітрильників
            $data['dues_sail'] = $wpdb->get_results( $wpdb->prepare(
                "SELECT Year_ID, DuesSail_Summa, DuesSail_DateCreate, FIOCreate
				 FROM vUserDuesSail WHERE User_ID = %d ORDER BY Year_ID DESC", $user_id
            ), ARRAY_A ) ?: [];

            // Вітрильні судна
            $data['vessels'] = $wpdb->get_results( $wpdb->prepare(
                "SELECT s.Sailboat_Name, s.RegNumber, s.Sailboat_NumberSail, a.AppShipTicket_DateCreate, v.Verification_Name, a.AppShipTicket_Summa
				 FROM ApplicationShipTicket a 
				 JOIN S_Verification v ON v.Verification_ID=a.Verification_ID 
				 JOIN vSailboat s ON s.AppShipTicket_ID=a.AppShipTicket_ID
				 WHERE a.User_ID = %d", $user_id
            ), ARRAY_A ) ?: [];

            // Вітрильні посвідчення
            $steering = $wpdb->get_results( $wpdb->prepare(
                "SELECT Steering_RegNumber as num, AppStatus_Name as status, Steering_DatePay as date, 'Стерновий' as type FROM vSteering WHERE User_ID = %d", $user_id
            ), ARRAY_A ) ?: [];

            $skipper = $wpdb->get_results( $wpdb->prepare(
                "SELECT Skipper_RegNumber as num, AppStatus_Name as status, Skipper_DatePay as date, 'Капітан' as type FROM vSkipper WHERE User_ID = %d", $user_id
            ), ARRAY_A ) ?: [];

            $data['certs'] = array_merge( $steering, $skipper );
        } else {
            // Якщо прав немає, передаємо порожні масиви, щоб JS не видав помилку
            $data['dues_sail'] = [];
            $data['vessels']   = [];
            $data['certs']     = [];
        }
        // ДОДАЙТЕ ОСЬ ЦЕЙ БЛОК:
        $data['permissions'] = [
            'can_see_sailing' => $can_see_sailing
        ];
        //
        wp_send_json_success( $data );
    }
    /**
	 * Отримує інформацію про клуб.
	 */
	public function handle_get_club_info(): void {
		check_ajax_referer( Registry_List::NONCE_ACTION, 'nonce' );

		$club_id = absint( $_POST['club_id'] ?? 0 );
		if ( ! $club_id ) {
			wp_send_json_error( [ 'message' => 'Невірний ID клубу.' ] );
		}

		global $wpdb;

		// Використовуємо поле Club_Adr замість окремої таблиці міст
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$club = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM S_Club WHERE Club_ID = %d",
				$club_id
			),
			ARRAY_A
		);

		if ( ! $club ) {
			wp_send_json_error( [ 'message' => 'Клуб не знайдено.' ] );
		}

		wp_send_json_success( [
			'name' => esc_html( $club['Club_Name'] ?? '' ),
			'city' => esc_html( $club['Club_Adr'] ?? 'Не вказано адреси' ),
            'www'  => esc_url( $club['Club_WWW'] ?? '' ), // <--- ДОДАЛИ ПОСИЛАННЯ
		] );
	}
    /**
     * Отримує дані для універсальної таблиці "Протокол".
     */
    public function handle_get_protocol(): void {
        check_ajax_referer( Registry_List::NONCE_ACTION, 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Недостатньо прав.' ] );
        }

        $log_name = sanitize_text_field( $_POST['log_name'] ?? 'UserFstu' );
        $page     = absint( $_POST['page'] ?? 1 );
        $per_page = absint( $_POST['per_page'] ?? 10 );
        $offset   = ( $page - 1 ) * $per_page;

        global $wpdb;

        // 1. Рахуємо загальну кількість для пагінації
        $total_logs = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM Logs WHERE Logs_name = %s",
            $log_name
        ) );

        // 2. Отримуємо порцію даних
        $logs = $wpdb->get_results( $wpdb->prepare(
            "SELECT l.Logs_DateCreate, l.Logs_Type, l.Logs_Name, l.Logs_Text, l.Logs_Error, u.FIOshort
			 FROM Logs l 
			 LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
			 WHERE l.Logs_name = %s
			 ORDER BY l.Logs_DateCreate DESC 
			 LIMIT %d OFFSET %d",
            $log_name,
            $per_page,
            $offset
        ), ARRAY_A );

        if ( empty( $logs ) ) {
            wp_send_json_success( [ 'html' => '<tr><td colspan="6" style="text-align:center; padding:20px;">Записів не знайдено</td></tr>', 'total' => 0 ] );
        }

        $html = '';
        foreach ( $logs as $log ) {
            $type_color = ($log['Logs_Type'] === 'I') ? '#27ae60' : (($log['Logs_Type'] === 'U') ? '#2980b9' : (($log['Logs_Type'] === 'D') ? '#c0392b' : 'var(--fstu-text)'));

            $html .= '<tr class="fstu-row">';
            $html .= '<td class="fstu-td" style="white-space:nowrap; font-size:11px;">' . esc_html( $log['Logs_DateCreate'] ) . '</td>';
            $html .= '<td class="fstu-td" style="text-align:center; font-weight:bold; color:' . $type_color . ';">' . esc_html( $log['Logs_Type'] ) . '</td>';
            $html .= '<td class="fstu-td">' . esc_html( $log['Logs_Name'] ) . '</td>';
            $html .= '<td class="fstu-td" style="font-size:12px;">' . esc_html( $log['Logs_Text'] ) . '</td>';
            $html .= '<td class="fstu-td">' . esc_html( $log['Logs_Error'] ) . '</td>';
            $html .= '<td class="fstu-td">' . esc_html( $log['FIOshort'] ?: 'Система' ) . '</td>';
            $html .= '</tr>';
        }

        wp_send_json_success( [
            'html'        => $html,
            'total'       => (int) $total_logs,
            'page'        => $page,
            'total_pages' => ceil( $total_logs / $per_page )
        ] );
    }

    /**
     * Отримує дані для таблиці "Звіт" (статистика по областях).
     */
    public function handle_get_report(): void {
        check_ajax_referer( Registry_List::NONCE_ACTION, 'nonce' );

        $current_user = wp_get_current_user();
        $roles        = (array) $current_user->roles;

        $is_fstu       = in_array( 'userfstu', $roles, true );
        $is_admin      = in_array( 'administrator', $roles, true );
        $is_global_reg = in_array( 'globalregistrar', $roles, true );
        $is_auditor    = in_array( 'groupauditor', $roles, true );

        // Якщо взагалі не член ФСТУ (і не адмін)
        if ( ! ( $is_fstu || $is_admin || $is_global_reg || $is_auditor ) ) {
            wp_send_json_error( [ 'message' => 'Доступ відсутній, Ви не є членом ФСТУ!' ] );
        }

        // Фінансові колонки бачать тільки ці ролі
        $show_finances = ( $is_admin || $is_global_reg || $is_auditor );
        $year          = sanitize_text_field( $_POST['year'] ?? gmdate( 'Y' ) );

        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT Region_ID, Region_Name
			 , GetUserCountRegion(Region_ID) AS UserCountRegion
			 , GetUserCountDuesYear(%s, Region_ID) AS UserCountDues
			 , GetSumUserDuesYearRegion(%s, Region_ID) AS SumUserDues
			 , (GetUserCountRegion(Region_ID)-GetUserCountDuesYear(%s, Region_ID)) AS CntBad
			 FROM vRegion WHERE Region_ID <> 30 ORDER BY Region_Name",
            $year, $year, $year
        ) );

        if ( empty( $results ) ) {
            wp_send_json_error( [ 'message' => 'Немає даних для звіту!' ] );
        }

        // Формуємо заголовки
        $thead = '<tr>';
        $thead .= '<th class="fstu-th" style="text-align:center; width: 60px;">№ з/п</th>';
        $thead .= '<th class="fstu-th">Область</th>';
        $thead .= '<th class="fstu-th" style="text-align:center;">КІЛЬКІСТЬ</th>';
        $thead .= '<th class="fstu-th" style="text-align:center;">СПЛАТИЛИ</th>';
        if ( $show_finances ) {
            $thead .= '<th class="fstu-th" style="text-align:right;">СУМА СПЛАТИ</th>';
            $thead .= '<th class="fstu-th" style="text-align:center;">НЕ СПЛАТИЛИ</th>';
            $thead .= '<th class="fstu-th" style="text-align:right;">СУМА БОРГУ</th>';
        }
        $thead .= '</tr>';

        // Формуємо тіло і підсумовуємо
        $tbody = '';
        $num = $total_users = $total_paid = $total_sum = $total_bad = $total_debt = 0;

        foreach ( $results as $row ) {
            $num++;
            $debt = $row->CntBad * 25; // Як у старій логіці (борг = 25 грн за людину)

            $total_users += $row->UserCountRegion;
            $total_paid  += $row->UserCountDues;
            $total_sum   += $row->SumUserDues;
            $total_bad   += $row->CntBad;
            $total_debt  += $debt;

            $tbody .= '<tr class="fstu-row">';
            $tbody .= '<td class="fstu-td" style="text-align:center;">' . $num . '</td>';
            $tbody .= '<td class="fstu-td" style="font-weight:600;">' . esc_html( $row->Region_Name ) . '</td>';
            $tbody .= '<td class="fstu-td" style="text-align:center;">' . (int) $row->UserCountRegion . '</td>';
            $tbody .= '<td class="fstu-td" style="text-align:center; color:#27ae60; font-weight:bold;">' . (int) $row->UserCountDues . '</td>';

            if ( $show_finances ) {
                $tbody .= '<td class="fstu-td" style="text-align:right;">' . number_format((float)$row->SumUserDues, 2, '.', '') . '</td>';
                $tbody .= '<td class="fstu-td" style="text-align:center; color:#c0392b;">' . (int) $row->CntBad . '</td>';
                $tbody .= '<td class="fstu-td" style="text-align:right; font-weight:bold;">' . number_format((float)$debt, 2, '.', '') . '</td>';
            }
            $tbody .= '</tr>';
        }

        // Фінальний рядок "ВСЬОГО"
        $tbody .= '<tr class="fstu-row" style="background-color:#eef2f5; font-weight:bold; font-size:14px;">';
        $tbody .= '<td class="fstu-td" colspan="2" style="text-align:right;">ВСЬОГО:</td>';
        $tbody .= '<td class="fstu-td" style="text-align:center;">' . $total_users . '</td>';
        $tbody .= '<td class="fstu-td" style="text-align:center; color:#27ae60;">' . $total_paid . '</td>';
        if ( $show_finances ) {
            $tbody .= '<td class="fstu-td" style="text-align:right;">' . number_format((float)$total_sum, 2, '.', '') . '</td>';
            $tbody .= '<td class="fstu-td" style="text-align:center; color:#c0392b;">' . $total_bad . '</td>';
            $tbody .= '<td class="fstu-td" style="text-align:right;">' . number_format((float)$total_debt, 2, '.', '') . '</td>';
        }
        $tbody .= '</tr>';

        wp_send_json_success( [
            'thead' => $thead,
            'tbody' => $tbody,
            'year'  => $year
        ] );
    }
}
