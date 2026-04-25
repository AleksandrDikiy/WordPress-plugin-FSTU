<?php
/**
 * Service для модуля Статистики.
 * Version: 1.0.0
 * Date_update: 2026-04-25
 */

namespace FSTU\Modules\Part;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Part_Service {
    private Part_Protocol_Service $protocol;

    public function __construct() {
        $this->protocol = new Part_Protocol_Service();
    }

    public function save_item( int $user_id, array $data ): bool {
        global $wpdb;

        $part_id      = absint( $data['Part_ID'] ?? 0 );
        $calendar_id  = absint( $data['Calendar_ID'] ?? 0 );

        if ( ! $calendar_id ) return false;

        $db_data = [
            'Part_Distance'     => floatval( $data['Part_Distance'] ?? 0 ),
            'Part_MaxSpeed'     => floatval( $data['Part_MaxSpeed'] ?? 0 ),
            'Part_AverageSpeed' => floatval( $data['Part_AverageSpeed'] ?? 0 ),
            'Part_Note'         => sanitize_textarea_field( $data['Part_Note'] ?? '' ),
            'Part_URL'          => esc_url_raw( $data['Part_URL'] ?? '' ),
        ];

        $wpdb->query( 'START TRANSACTION' );

        try {
            if ( $part_id > 0 ) {
                $db_data['Part_DateEdit'] = current_time( 'mysql' );
                $result = $wpdb->update( 'Part', $db_data, [ 'Part_ID' => $part_id ] );
                if ( $result === false ) throw new \Exception( 'DB Update Error' );
                $this->protocol->log( 'U', "Оновлено статистику для Calendar_ID: $calendar_id", '✓' );
            } else {
                $db_data['Calendar_ID']     = $calendar_id;
                $db_data['UserCreate']      = get_current_user_id();
                $db_data['Part_DateCreate'] = current_time( 'mysql' );
                $result = $wpdb->insert( 'Part', $db_data );
                if ( $result === false ) throw new \Exception( 'DB Insert Error' );
                $this->protocol->log( 'I', "Додано статистику для Calendar_ID: $calendar_id", '✓' );
            }
            $wpdb->query( 'COMMIT' );
            return true;
        } catch ( \Exception $e ) {
            $wpdb->query( 'ROLLBACK' );
            $this->protocol->log( $part_id > 0 ? 'U' : 'I', "Помилка збереження", 'Error' );
            return false;
        }
    }

    public function delete_item( int $part_id ): bool {
        global $wpdb;
        $wpdb->query( 'START TRANSACTION' );
        try {
            $result = $wpdb->delete( 'Part', [ 'Part_ID' => $part_id ] );
            if ( $result === false ) throw new \Exception( 'DB Delete Error' );

            $this->protocol->log( 'D', "Видалено статистику ID: $part_id", '✓' );
            $wpdb->query( 'COMMIT' );
            return true;
        } catch ( \Exception $e ) {
            $wpdb->query( 'ROLLBACK' );
            $this->protocol->log( 'D', "Помилка видалення ID: $part_id", 'Error' );
            return false;
        }
    }
}