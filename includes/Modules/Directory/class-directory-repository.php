<?php
/**
 * Repository для роботи з БД модуля Directory.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-16
 *
 * @package FSTU\Modules\Directory
 */

namespace FSTU\Modules\Directory;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Directory_Repository {

    /**
     * Отримання списку членів Виконкому.
     */
    public function get_members(): array {
        global $wpdb;
        $sql = "SELECT Guidance_ID, User_ID, TypeGuidance_ID, 
                       GROUP_CONCAT(Guidance_Notes SEPARATOR ', ') AS Guidance_Notes, 
                       GROUP_CONCAT(MemberGuidance_Name SEPARATOR ', ') AS MemberGuidance_Name, 
                       MIN(MemberGuidance_Order) AS MemberGuidance_Order,  
                       FIO, FIOshort, email, TypeGuidance_Name
                FROM vGuidance 
                WHERE TypeGuidance_ID = 1
                GROUP BY User_ID 
                ORDER BY MemberGuidance_Order, FIO";

        $results = $wpdb->get_results( $sql, ARRAY_A );
        return is_array( $results ) ? $results : [];
    }

}