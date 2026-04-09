<?php
namespace FSTU\Modules\PersonalCabinet;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repository модуля «Особистий кабінет ФСТУ».
 *
 * Version:     1.4.0
 * Date_update: 2026-04-09
 *
 * @package FSTU\Modules\PersonalCabinet
 */
class Personal_Cabinet_Repository {

	private const LOG_NAME = 'Personal';

	/**
	 * @return array<int,array<string,string>>
	 */
	public function get_user_clubs( int $user_id ): array {
		global $wpdb;

		$sql = "SELECT u.UserClub_Date, c.Club_Name, c.Club_WWW, c.Club_Adr
			FROM UserClub u
			INNER JOIN S_Club c ON c.Club_ID = u.Club_ID
			WHERE u.User_ID = %d
			ORDER BY u.UserClub_Date DESC, u.UserClub_ID DESC";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $user_id ), ARRAY_A );

		return $this->normalize_rows( $rows );
	}

	/**
	 * @return array<int,array<string,string>>
	 */
	public function get_user_cities( int $user_id ): array {
		global $wpdb;

		$sql = "SELECT u.UserCity_DateCreate, c.City_Name, r.Region_Name
			FROM UserCity u
			INNER JOIN S_City c ON c.City_ID = u.City_ID
			INNER JOIN S_Region r ON r.Region_ID = c.Region_ID
			WHERE u.User_ID = %d
			ORDER BY u.UserCity_DateCreate DESC, u.UserCity_ID DESC";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $user_id ), ARRAY_A );

		return $this->normalize_rows( $rows );
	}

	/**
	 * @return array<int,array<string,string>>
	 */
	public function get_user_units( int $user_id ): array {
		global $wpdb;

		$sql = "SELECT u.UserRegistationOFST_DateCreate,
				COALESCE(un.Unit_ShortName, r.Region_Name) AS Unit_Name,
				r.Region_Name
			FROM UserRegistationOFST u
			INNER JOIN S_Region r ON r.Region_ID = u.Region_ID
			LEFT JOIN S_Unit un ON un.Unit_ID = u.Unit_ID
			WHERE u.User_ID = %d
			ORDER BY u.UserRegistationOFST_DateCreate DESC, u.UserRegistationOFST_ID DESC";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $user_id ), ARRAY_A );

		return $this->normalize_rows( $rows );
	}

	/**
	 * @return array<int,array<string,string>>
	 */
	public function get_user_tourism_types( int $user_id ): array {
		global $wpdb;

		$sql = "SELECT u.UserTourismType_DateCreate, t.TourismType_Name
			FROM UserTourismType u
			INNER JOIN S_TourismType t ON t.TourismType_ID = u.TourismType_ID
			WHERE u.User_ID = %d
			ORDER BY u.UserTourismType_DateCreate DESC, u.UserTourismType_ID DESC";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $user_id ), ARRAY_A );

		return $this->normalize_rows( $rows );
	}

	/**
	 * @return array<int,array<string,string>>
	 */
	public function get_user_experience( int $user_id ): array {
		global $wpdb;

		$sql = "SELECT p.ParticipationType_Name,
				c.Calendar_ID,
				c.Calendar_DateBegin,
				c.Calendar_DateEnd,
				c.Calendar_Name,
				h.HikingCategory_Name,
				h.UserHikingCategory_UrlDivodka,
				t.TourismType_Name
			FROM UserCalendar u
			INNER JOIN Calendar c ON c.Calendar_ID = u.Calendar_ID
			INNER JOIN S_ParticipationType p ON p.ParticipationType_ID = u.ParticipationType_ID
			INNER JOIN S_TourismType t ON t.TourismType_ID = c.TourismType_ID
			LEFT JOIN vUserHikingCategory h ON h.UserCalendar_ID = u.UserCalendar_ID
			WHERE u.User_ID = %d
			  AND c.EventType_ID = 2
			  AND h.HikingCategory_ID IS NOT NULL
			ORDER BY c.Calendar_DateBegin DESC, c.Calendar_ID DESC";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $user_id ), ARRAY_A );

		return $this->normalize_rows( $rows );
	}

	/**
	 * @return array<int,array<string,string>>
	 */
	public function get_user_ranks( int $user_id ): array {
		global $wpdb;

		$sql = "SELECT s.SportsCategories_Name,
				t.TourismType_Name,
				c.Calendar_ID,
				c.Calendar_Name,
				c.Calendar_DateBegin,
				c.Calendar_DateEnd,
				s.UserSportCategories_DatePrikaz,
				s.UserSportCategories_UrlPrikaz
			FROM UserCalendar u
			INNER JOIN Calendar c ON c.Calendar_ID = u.Calendar_ID
			INNER JOIN S_TourismType t ON t.TourismType_ID = c.TourismType_ID
			LEFT JOIN vUserSportCategories s ON s.UserCalendar_ID = u.UserCalendar_ID
			WHERE u.User_ID = %d
			  AND c.EventType_ID = 1
			  AND s.SportsCategories_ID IS NOT NULL
			ORDER BY c.Calendar_DateBegin DESC, c.Calendar_ID DESC";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $user_id ), ARRAY_A );

		return $this->normalize_rows( $rows );
	}

	/**
	 * @return array<int,array<string,string>>
	 */
	public function get_user_judging( int $user_id ): array {
		global $wpdb;

		$sql = "SELECT RefereeCategory_Name,
				Referee_DateCreate,
				Referee_NumOrder,
				Referee_DateOrder,
				Referee_URLOrder
			FROM vReferee
			WHERE User_ID = %d
			ORDER BY RefereeCategory_Order ASC, Referee_DateCreate DESC";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $user_id ), ARRAY_A );

		return $this->normalize_rows( $rows );
	}

	/**
	 * @return array<int,array<string,string>>
	 */
	public function get_user_dues( int $user_id ): array {
		global $wpdb;

		$sql = "SELECT d.Dues_ID,
				d.Year_Name,
				d.Dues_Summa,
				d.Dues_URL,
				d.Dues_DateCreate,
				u.FIOshort AS Financier,
				d.DuesType_Name,
				d.Dues_ShopBillid,
				d.Dues_ApprovalCode
			FROM vUserDues d
			INNER JOIN vUser u ON u.User_ID = d.UserCreate
			WHERE d.User_ID = %d
			ORDER BY d.Year_Name DESC, d.Dues_DateCreate DESC, d.Dues_ID DESC";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $user_id ), ARRAY_A );

		return $this->normalize_rows( $rows );
	}

	public function has_due_for_year( int $user_id, int $year_id, int $dues_type_id = 1 ): bool {
		global $wpdb;

		$sql = "SELECT Dues_ID
			FROM Dues
			WHERE User_ID = %d
				AND Year_ID = %d
				AND DuesType_ID = %d
			LIMIT 1";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$due_id = (int) $wpdb->get_var( $wpdb->prepare( $sql, $user_id, $year_id, $dues_type_id ) );

		return $due_id > 0;
	}

	public function get_due_user_id_by_shop_order_number( string $order_number ): int {
		global $wpdb;

		$order_number = trim( $order_number );
		if ( '' === $order_number ) {
			return 0;
		}

		$sql = "SELECT User_ID
			FROM Dues
			WHERE Dues_ShopOrderNumber = %s
			LIMIT 1";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$user_id = (int) $wpdb->get_var( $wpdb->prepare( $sql, $order_number ) );

		return $user_id > 0 ? $user_id : 0;
	}

	/**
	 * @return array<string,string>
	 */
	public function get_user_unit_payment_info( int $user_id ): array {
		global $wpdb;

		$sql = "SELECT Unit_UrlPay, Unit_PaymentCard, Unit_AnnualFee
			FROM vUserRegistationOFST
			WHERE User_ID = %d
			  AND UnitType_ID > 1
			ORDER BY UserRegistationOFST_DateCreate DESC
			LIMIT 1";

		$row = $wpdb->get_row( $wpdb->prepare( $sql, $user_id ), ARRAY_A );

		return is_array( $row ) ? $this->normalize_rows( [ $row ] )[0] : [];
	}

	/**
	 * @param array<int,string> $param_names
	 * @return array<string,string>
	 */
	public function get_settings_values( array $param_names ): array {
		global $wpdb;

		$param_names = array_values( array_filter( array_map( 'strval', $param_names ) ) );
		if ( empty( $param_names ) ) {
			return [];
		}

		$placeholders = implode( ', ', array_fill( 0, count( $param_names ), '%s' ) );
		$sql          = "SELECT ParamName, ParamValue FROM Settings WHERE ParamName IN ({$placeholders})";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$param_names ), ARRAY_A );
		if ( ! is_array( $rows ) ) {
			return [];
		}

		$map = [];
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$name = isset( $row['ParamName'] ) ? (string) $row['ParamName'] : '';
			if ( '' === $name ) {
				continue;
			}

			$map[ $name ] = isset( $row['ParamValue'] ) ? (string) $row['ParamValue'] : '';
		}

		return $map;
	}

	/**
	 * @return array<int,array<string,string>>
	 */
	public function get_user_dues_sail( int $user_id ): array {
		global $wpdb;

		$sql = "SELECT DuesSail_ID, Year_ID, DuesSail_Summa, DuesSail_DateCreate, FIOCreate
			FROM vUserDuesSail
			WHERE User_ID = %d
			ORDER BY Year_ID DESC, DuesSail_DateCreate DESC, DuesSail_ID DESC";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $user_id ), ARRAY_A );

		return $this->normalize_rows( $rows );
	}

	/**
	 * @return array<int,array<string,string>>
	 */
	public function get_user_vessels( int $user_id ): array {
		global $wpdb;

		$sql = "SELECT s.Sailboat_Name,
				s.RegNumber,
				s.Sailboat_NumberSail,
				a.AppShipTicket_ID,
				a.AppShipTicket_DateCreate,
				a.AppShipTicket_Summa,
				v.Verification_Name,
				GetUserDuesSail(a.User_ID, YEAR(NOW()) - 1) AS PrevYearDuesSail,
				GetUserDuesSail(a.User_ID, YEAR(NOW())) AS CurrYearDuesSail
			FROM ApplicationShipTicket a
			INNER JOIN S_Verification v ON v.Verification_ID = a.Verification_ID
			INNER JOIN vSailboat s ON s.AppShipTicket_ID = a.AppShipTicket_ID
			WHERE a.User_ID = %d
			ORDER BY a.AppShipTicket_DateCreate DESC, a.AppShipTicket_ID DESC";

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $user_id ), ARRAY_A );

		return $this->normalize_rows( $rows );
	}

	/**
	 * @return array<int,array<string,string>>
	 */
	public function get_user_sailing_certificates( int $user_id ): array {
		global $wpdb;

		$steering_sql = "SELECT Steering_ID AS Document_ID,
				Steering_RegNumber AS Number,
				AppStatus_Name AS Status,
				Steering_DateCreate AS Created_At,
				Steering_DatePay AS Paid_At,
				'Стерновий' AS Type,
				'" . esc_sql( home_url( '/Steering/?ViewID=' ) ) . "' AS Base_Url
			FROM vSteering
			WHERE User_ID = %d";

		$skipper_sql = "SELECT Skipper_ID AS Document_ID,
				Skipper_RegNumber AS Number,
				AppStatus_Name AS Status,
				Skipper_DateCreate AS Created_At,
				Skipper_DatePay AS Paid_At,
				'Капітан' AS Type,
				'" . esc_sql( home_url( '/Skipper/?View_ID=' ) ) . "' AS Base_Url
			FROM vSkipper
			WHERE User_ID = %d";

		$steering_rows = $wpdb->get_results( $wpdb->prepare( $steering_sql, $user_id ), ARRAY_A );
		$skipper_rows  = $wpdb->get_results( $wpdb->prepare( $skipper_sql, $user_id ), ARRAY_A );

		return $this->normalize_rows( array_merge( is_array( $steering_rows ) ? $steering_rows : [], is_array( $skipper_rows ) ? $skipper_rows : [] ) );
	}

	/**
	 * @return array<string,mixed>
	 */
	public function get_profile_context( int $user_id ): array {
		global $wpdb;

		$city = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT c.City_Name, r.Region_Name
				 FROM UserCity uc
				 INNER JOIN S_City c ON c.City_ID = uc.City_ID
				 INNER JOIN S_Region r ON r.Region_ID = c.Region_ID
				 WHERE uc.User_ID = %d
				 ORDER BY uc.UserCity_DateCreate DESC
				 LIMIT 1",
				$user_id
			),
			ARRAY_A
		);

		$ofst = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT r.Region_Name
				 FROM UserRegistationOFST uro
				 INNER JOIN S_Region r ON r.Region_ID = uro.Region_ID
				 WHERE uro.User_ID = %d
				 ORDER BY uro.UserRegistationOFST_DateCreate DESC
				 LIMIT 1",
				$user_id
			),
			ARRAY_A
		);

		$member_card = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT umc.User_ID,
				        umc.UserMemberCard_Number,
				        umc.UserMemberCard_Summa,
				        umc.StatusCard_ID,
				        umc.TypeCard_ID,
				        r.Region_Code,
				        ss.StatusCard_Name,
				        tc.TypeCard_Name,
				        CONCAT(r.Region_Code, '-', umc.UserMemberCard_Number) AS CardNumber
				 FROM UserMemberCard umc
				 LEFT JOIN S_Region r ON r.Region_ID = umc.Region_ID
				 LEFT JOIN S_StatusCard ss ON ss.StatusCard_ID = umc.StatusCard_ID
				 LEFT JOIN S_TypeCard tc ON tc.TypeCard_ID = umc.TypeCard_ID
				 WHERE umc.User_ID = %d
				 ORDER BY umc.UserMemberCard_DateCreate DESC
				 LIMIT 1",
				$user_id
			),
			ARRAY_A
		);

		$telegram_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT TelegramID
				 FROM vUserTelegram
				 WHERE User_ID = %d
				 ORDER BY UserTelegram_DateCreate DESC
				 LIMIT 1",
				$user_id
			)
		);

		$user_params = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT UserParams_Name, UserParams_Value
				 FROM UserParams
				 WHERE User_ID = %d
				   AND UserParams_Name IN ('IPN', 'BankName', 'IBAN')",
				$user_id
			),
			ARRAY_A
		);

		$params_map = [];
		if ( is_array( $user_params ) ) {
			foreach ( $user_params as $row ) {
				$name = isset( $row['UserParams_Name'] ) ? (string) $row['UserParams_Name'] : '';
				if ( '' === $name ) {
					continue;
				}

				$params_map[ $name ] = isset( $row['UserParams_Value'] ) ? (string) $row['UserParams_Value'] : '';
			}
		}

		$verification_code = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT VerificationCode FROM {$wpdb->users} WHERE ID = %d LIMIT 1",
				$user_id
			)
		);

		return [
			'city'              => is_array( $city ) ? $city : [],
			'ofst'              => is_array( $ofst ) ? $ofst : [],
			'member_card'       => is_array( $member_card ) ? $member_card : [],
			'telegram_id'       => is_scalar( $telegram_id ) ? (string) $telegram_id : '',
			'verification_code' => is_scalar( $verification_code ) ? (string) $verification_code : '',
			'user_params'       => $params_map,
		];
	}

	/**
	 * @return array<int,array<string,string>>
	 */
	public function get_protocol_items( string $search, int $limit, int $offset ): array {
		global $wpdb;

		$search       = trim( $search );
		$where_sql    = 'WHERE l.Logs_Name = %s';
		$query_params = [ self::LOG_NAME ];

		if ( '' !== $search ) {
			$like         = '%' . $wpdb->esc_like( $search ) . '%';
			$where_sql   .= ' AND (l.Logs_Text LIKE %s OR COALESCE(u.FIO, \'\') LIKE %s)';
			$query_params[] = $like;
			$query_params[] = $like;
		}

		$query_params[] = $limit;
		$query_params[] = $offset;

		$sql = "SELECT l.Logs_DateCreate, l.Logs_Type, l.Logs_Name, l.Logs_Text, l.Logs_Error,
				COALESCE(u.FIO, '') AS FIO
			FROM Logs l
			LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
			{$where_sql}
			ORDER BY l.Logs_DateCreate DESC
			LIMIT %d OFFSET %d";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $wpdb->prepare( $sql, ...$query_params ), ARRAY_A );

		return is_array( $results ) ? array_map( [ $this, 'normalize_protocol_row' ], $results ) : [];
	}

	public function count_protocol_items( string $search ): int {
		global $wpdb;

		$search       = trim( $search );
		$where_sql    = 'WHERE l.Logs_Name = %s';
		$query_params = [ self::LOG_NAME ];

		if ( '' !== $search ) {
			$like         = '%' . $wpdb->esc_like( $search ) . '%';
			$where_sql   .= ' AND (l.Logs_Text LIKE %s OR COALESCE(u.FIO, \'\') LIKE %s)';
			$query_params[] = $like;
			$query_params[] = $like;
		}

		$sql = "SELECT COUNT(*)
			FROM Logs l
			LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
			{$where_sql}";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$total = $wpdb->get_var( $wpdb->prepare( $sql, ...$query_params ) );

		return absint( $total );
	}

	/**
	 * @param array<string,mixed> $row
	 * @return array<string,string>
	 */
	private function normalize_protocol_row( array $row ): array {
		return [
			'Logs_DateCreate' => isset( $row['Logs_DateCreate'] ) ? (string) $row['Logs_DateCreate'] : '',
			'Logs_Type'       => isset( $row['Logs_Type'] ) ? (string) $row['Logs_Type'] : '',
			'Logs_Name'       => isset( $row['Logs_Name'] ) ? (string) $row['Logs_Name'] : '',
			'Logs_Text'       => isset( $row['Logs_Text'] ) ? (string) $row['Logs_Text'] : '',
			'Logs_Error'      => isset( $row['Logs_Error'] ) ? (string) $row['Logs_Error'] : '',
			'FIO'             => isset( $row['FIO'] ) ? (string) $row['FIO'] : '',
		];
	}

	/**
	 * @param mixed $rows
	 * @return array<int,array<string,string>>
	 */
	private function normalize_rows( $rows ): array {
		if ( ! is_array( $rows ) ) {
			return [];
		}

		$normalized = [];

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$normalized_row = [];
			foreach ( $row as $key => $value ) {
				$normalized_row[ (string) $key ] = is_scalar( $value ) ? (string) $value : '';
			}

			$normalized[] = $normalized_row;
		}

		return $normalized;
	}
}

