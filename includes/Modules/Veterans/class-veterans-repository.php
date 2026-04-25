<?php
/**
 * Repository для модуля "Рада ветеранів".
 *
 * Version:     1.1.0
 * Date_update: 2026-04-25
 *
 * @package FSTU\Modules\Veterans
 */

namespace FSTU\Modules\Veterans;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Veterans_Repository {

    /**
     * Отримує повний список ветеранів без пагінації.
     */
    public function get_members(): array {
        global $wpdb;

        $sql = "
            SELECT Guidance_ID, User_ID, Guidance_Notes, FIO, FIOshort, email,
                GROUP_CONCAT(MemberGuidance_Name SEPARATOR ', ') AS MemberGuidance_Name,
                MIN(MemberGuidance_Order) AS MemberGuidance_Order
            FROM vGuidance
            WHERE TypeGuidance_ID = 2
            GROUP BY User_ID
            ORDER BY MemberGuidance_Order ASC, FIO ASC
        ";

        $items = $wpdb->get_results( $sql, ARRAY_A ) ?? [];

        return [
            'items' => $items,
        ];
    }

    /**
     * Безпечно видаляє запис та логує дію.
     */
    public function delete_member( int $guidance_id, int $user_id ): bool|\WP_Error {
        global $wpdb;

        $wpdb->query( 'START TRANSACTION' );

        $deleted = $wpdb->delete( 'Guidance', [ 'Guidance_ID' => $guidance_id ], [ '%d' ] );

        if ( false === $deleted ) {
            $wpdb->query( 'ROLLBACK' );
            return new \WP_Error( 'db_error', __( 'Помилка видалення запису з БД.', 'fstu' ) );
        }

        $log_inserted = $wpdb->insert(
            'Logs',
            [
                'User_ID'         => get_current_user_id(),
                'Logs_DateCreate' => current_time( 'mysql' ),
                'Logs_Type'       => 'D',
                'Logs_Name'       => 'Veterans',
                'Logs_Text'       => "Видалено ветерана з Guidance_ID: {$guidance_id}, User_ID: {$user_id}",
                'Logs_Error'      => '✓',
            ],
            [ '%d', '%s', '%s', '%s', '%s', '%s' ]
        );

        if ( false === $log_inserted ) {
            $wpdb->query( 'ROLLBACK' );
            return new \WP_Error( 'log_error', __( 'Помилка збереження протоколу.', 'fstu' ) );
        }

        $wpdb->query( 'COMMIT' );
        return true;
    }
}