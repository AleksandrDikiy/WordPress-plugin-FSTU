<?php
/**
 * Репозиторій для роботи з таблицею виборів.
 * * Version: 1.0.0
 * Date_update: 2026-04-22
 */

namespace FSTU\Modules\Elections;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Elections_Repository {

    private string $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = 'Elections';
    }

    /**
     * Отримати список усіх виборів.
     */
    public function get_all_elections(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM {$this->table_name} ORDER BY DateCreate DESC",
            ARRAY_A
        );
    }

    /**
     * Отримати дані конкретних виборів за ID.
     */
    public function get_by_id( int $id ): ?array {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE Election_ID = %d", $id ),
            ARRAY_A
        );
        return $row ?: null;
    }

    /**
     * Створити нові вибори.
     */
    public function create( array $data ): int {
        global $wpdb;
        $wpdb->insert( $this->table_name, $data );
        return (int) $wpdb->insert_id;
    }

    /**
     * Оновити дані виборів.
     */
    public function update( int $id, array $data ): bool {
        global $wpdb;
        return false !== $wpdb->update( $this->table_name, $data, [ 'Election_ID' => $id ] );
    }
}