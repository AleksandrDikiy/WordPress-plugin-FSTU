<?php
/**
 * Protocol Service для модуля Статистики.
 * Version: 1.0.0
 * Date_update: 2026-04-25
 */

namespace FSTU\Modules\Part;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Part_Protocol_Service {
    private const LOG_NAME = 'Part';

    public function log( string $type, string $text, string $status ): void {
        global $wpdb;
        $wpdb->insert(
            'Logs',
            [
                'User_ID'         => get_current_user_id(),
                'Logs_DateCreate' => current_time( 'mysql' ),
                'Logs_Type'       => $type, // Тільки 'I', 'U', 'D'
                'Logs_Name'       => self::LOG_NAME,
                'Logs_Text'       => sanitize_text_field( $text ),
                'Logs_Error'      => sanitize_text_field( $status ),
            ]
        );
    }

    public function get_logs( int $limit, int $offset, string $search = '' ): array {
        global $wpdb;
        $where = $wpdb->prepare( "Logs_Name = %s", self::LOG_NAME );
        if ( ! empty( $search ) ) {
            $where .= $wpdb->prepare( " AND Logs_Text LIKE %s", '%' . $wpdb->esc_like( $search ) . '%' );
        }
        $sql = "SELECT SQL_CALC_FOUND_ROWS l.*, u.FIO 
                FROM Logs l 
                LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID 
                WHERE $where ORDER BY l.Logs_DateCreate DESC LIMIT %d OFFSET %d";

        $items = $wpdb->get_results( $wpdb->prepare( $sql, $limit, $offset ), ARRAY_A );
        $total = (int) $wpdb->get_var( "SELECT FOUND_ROWS()" );
        return [ 'items' => $items ?: [], 'total' => $total ];
    }
}