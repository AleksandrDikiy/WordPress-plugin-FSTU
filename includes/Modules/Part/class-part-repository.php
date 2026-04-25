<?php
/**
 * Repository для модуля Статистики.
 * Version: 1.0.0
 * Date_update: 2026-04-25
 */

namespace FSTU\Modules\Part;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Part_Repository {
    public function get_items( int $user_id, array $filters, int $limit, int $offset ): array {
        global $wpdb;

        $where_clauses = [ $wpdb->prepare( "User_ID = %d", $user_id ) ];

        if ( ! empty( $filters['year'] ) ) {
            $where_clauses[] = $wpdb->prepare( "YearName = %d", intval( $filters['year'] ) );
        }
        if ( ! empty( $filters['search'] ) ) {
            $where_clauses[] = $wpdb->prepare( "Calendar_Name LIKE %s", '%' . $wpdb->esc_like( $filters['search'] ) . '%' );
        }

        $where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );

        $sql = "SELECT SQL_CALC_FOUND_ROWS 
                    Part_ID, Calendar_ID, Calendar_Name, DateBegin, DateEnd, 
                    YearName, StatusName, SailboatName, RoleName, 
                    Part_Distance, Part_MaxSpeed, Part_AverageSpeed, Part_Note, Part_URL
                FROM vCalendarPart
                $where_sql
                ORDER BY DateBegin DESC
                LIMIT %d OFFSET %d";

        $items = $wpdb->get_results( $wpdb->prepare( $sql, $limit, $offset ), ARRAY_A );
        $total = (int) $wpdb->get_var( "SELECT FOUND_ROWS()" );

        return [
            'items' => $items ?: [],
            'total' => $total
        ];
    }

    public function get_years( int $user_id ): array {
        global $wpdb;
        $sql = "SELECT DISTINCT YearName FROM vCalendarPart WHERE User_ID = %d ORDER BY YearName DESC";
        $results = $wpdb->get_col( $wpdb->prepare( $sql, $user_id ) );
        return $results ?: [];
    }

    public function get_kpi( int $user_id, int $year = 0 ): array {
        global $wpdb;
        $where = $wpdb->prepare( "User_ID = %d", $user_id );
        if ( $year > 0 ) {
            $where .= $wpdb->prepare( " AND YearName = %d", $year );
        }
        $sql = "SELECT 
                    SUM(Part_Distance) AS TotalDistance,
                    MAX(Part_MaxSpeed) AS MaxSpeed,
                    COUNT(Calendar_ID) AS TotalEvents
                FROM vCalendarPart WHERE $where";
        return $wpdb->get_row( $sql, ARRAY_A ) ?: [ 'TotalDistance' => 0, 'MaxSpeed' => 0, 'TotalEvents' => 0 ];
    }
}