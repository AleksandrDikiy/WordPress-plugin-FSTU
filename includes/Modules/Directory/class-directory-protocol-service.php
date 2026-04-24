<?php
/**
 * Protocol Service для логування операцій модуля "Directory" (Виконком).
 *
 * Version:     1.0.0
 * Date_update: 2026-04-24
 */

namespace FSTU\Modules\Directory;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Directory_Protocol_Service {

    private const LOG_NAME = 'Guidance';

    public function log_action( string $type, string $text, string $status ): void {
        global $wpdb;
        $safe_type = substr( strtoupper( trim( $type ) ), 0, 1 );

        $wpdb->insert(
            'Logs',
            [
                'User_ID'         => get_current_user_id(),
                'Logs_DateCreate' => current_time( 'mysql' ),
                'Logs_Type'       => $safe_type,
                'Logs_Name'       => self::LOG_NAME,
                'Logs_Text'       => wp_trim_words( sanitize_text_field( $text ), 30, '...' ),
                'Logs_Error'      => sanitize_text_field( $status ),
            ],
            [ '%d', '%s', '%s', '%s', '%s', '%s' ]
        );
    }

    public function get_logs( int $page = 1, int $per_page = 50, string $search = '' ): array {
        global $wpdb;

        $offset = ( $page - 1 ) * $per_page;
        $args   = [ self::LOG_NAME ];

        $where_clause = "l.Logs_Name = %s";

        if ( ! empty( $search ) ) {
            $where_clause .= " AND (l.Logs_Text LIKE %s OR u.display_name LIKE %s)";
            $like_search = '%' . $wpdb->esc_like( $search ) . '%';
            $args[]      = $like_search;
            $args[]      = $like_search;
        }

        $total_sql = "SELECT COUNT(l.Logs_ID) FROM Logs l LEFT JOIN {$wpdb->users} u ON u.ID = l.User_ID WHERE {$where_clause}";
        $total_items = (int) $wpdb->get_var( $wpdb->prepare( $total_sql, ...$args ) );

        $items_sql = "
			SELECT l.Logs_DateCreate, l.Logs_Type, l.Logs_Name, l.Logs_Text, l.Logs_Error, u.display_name AS FIO
			FROM Logs l 
			LEFT JOIN {$wpdb->users} u ON u.ID = l.User_ID 
			WHERE {$where_clause}
			ORDER BY l.Logs_DateCreate DESC LIMIT %d OFFSET %d
		";

        $args[] = $per_page;
        $args[] = $offset;

        $items = $wpdb->get_results( $wpdb->prepare( $items_sql, ...$args ), ARRAY_A ) ?? [];

        return [
            'items'       => $items,
            'total'       => $total_items,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page ),
        ];
    }
}