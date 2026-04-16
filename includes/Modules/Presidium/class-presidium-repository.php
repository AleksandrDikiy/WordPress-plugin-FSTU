<?php
/**
 * Repository для роботи з БД модуля Presidium.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-16
 *
 * @package FSTU\Modules\Presidium
 */

namespace FSTU\Modules\Presidium;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Presidium_Repository {

    /**
     * Отримання списку членів Президії.
     * Прямий SQL-запит (заміна legacy-представлення vPresidium та функцій)
     * для уникнення проблем з кодуванням бази даних.
     *
     * @return array
     */
    public function get_members(): array {
        global $wpdb;

        $sql = "SELECT 
					u.User_ID,
					u.FIO,
					u.email,
					COALESCE(
						(SELECT mg.MemberGuidance_Name 
						 FROM Guidance g 
                         JOIN vMemberGuidance mg ON mg.MemberGuidance_ID = g.MemberGuidance_ID 
						 WHERE g.User_ID = u.User_ID 
                         ORDER BY mg.MemberGuidance_Order ASC LIMIT 1),
						(SELECT CONCAT(m.MemberRegional_Name, ' (', su.Unit_ShortName, ')') 
						 FROM RegionalFST r 
                         JOIN vMemberRegional m ON m.MemberRegional_ID = r.MemberRegional_ID 
                         JOIN S_Unit su ON su.Unit_ID = r.Unit_ID 
						 WHERE r.User_ID = u.User_ID AND r.MemberRegional_ID = 1 
                         ORDER BY m.MemberRegional_Order ASC LIMIT 1)
					) AS MemberGuidance_Name,
					COALESCE(
						(SELECT mg.MemberGuidance_Order 
						 FROM Guidance g 
                         JOIN vMemberGuidance mg ON mg.MemberGuidance_ID = g.MemberGuidance_ID 
						 WHERE g.User_ID = u.User_ID 
                         ORDER BY mg.MemberGuidance_Order ASC LIMIT 1),
						(SELECT (100 + m.MemberRegional_Order) 
						 FROM RegionalFST r 
                         JOIN vMemberRegional m ON m.MemberRegional_ID = r.MemberRegional_ID 
						 WHERE r.User_ID = u.User_ID AND r.MemberRegional_ID = 1 
                         ORDER BY m.MemberRegional_Order ASC LIMIT 1),
						1000
					) AS PostOrder
				FROM (
					SELECT User_ID FROM Guidance WHERE TypeGuidance_ID IN (1, 4)
					UNION
					SELECT User_ID FROM Guidance WHERE TypeGuidance_ID IN (2, 3) AND MemberGuidance_ID IN (19, 21)
					UNION
					SELECT User_ID FROM RegionalFST WHERE MemberRegional_ID = 1
				) AS base
				JOIN vUserFSTU u ON u.User_ID = base.User_ID
				ORDER BY PostOrder ASC, u.FIO ASC";

        $results = $wpdb->get_results( $sql, ARRAY_A );

        if ( is_array( $results ) ) {
            foreach ( $results as &$row ) {
                $phone = get_user_meta( $row['User_ID'], 'PhoneMobile', true );
                $row['PhoneMobile'] = $phone ? $phone : '';
            }
            return $results;
        }

        return [];
    }
}