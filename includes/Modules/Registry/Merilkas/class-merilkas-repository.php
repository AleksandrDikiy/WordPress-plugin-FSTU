<?php
/**
 * Репозиторій модуля "Реєстр мерилок".
 * Читання/запис бази даних, перевірка залежностей.
 *
 * Version:     1.0.1
 * Date_update: 2026-04-09
 *
 * @package FSTU\Modules\Registry\Merilkas
 */

namespace FSTU\Modules\Registry\Merilkas;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Merilkas_Repository {

    /**
     * Перевіряє, чи використовується мерилка в змаганнях.
     *
     * @param int $mr_id ID мерилки.
     * @return bool
     */
    public function is_merilka_used( int $mr_id ): bool {
        if ( $mr_id <= 0 ) {
            return false;
        }

        global $wpdb;

        // Перевіряємо, чи прив'язана мерилка до будь-якої заявки на змагання
        $sql = 'SELECT 1 FROM Application WHERE MR_ID = %d LIMIT 1';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
        $used_in_app = (bool) $wpdb->get_var( $wpdb->prepare( $sql, $mr_id ) );

        return $used_in_app;
    }

    /**
     * Повертає актуальну (останню) мерилку для конкретного судна.
     *
     * @param int $sailboat_id ID судна.
     * @return array<string,mixed>|null
     */
    public function get_latest_merilka( int $sailboat_id ): ?array {
        if ( $sailboat_id <= 0 ) {
            return null;
        }

        global $wpdb;

        $sql = 'SELECT * FROM Merilka WHERE Sailboat_ID = %d ORDER BY MR_DateObmera DESC, MR_ID DESC LIMIT 1';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
        $item = $wpdb->get_row( $wpdb->prepare( $sql, $sailboat_id ), ARRAY_A );

        return is_array( $item ) ? $item : null;
    }

    /**
     * Повертає власника судна.
     *
     * @param int $sailboat_id ID судна.
     * @return int ID користувача.
     */
    public function get_sailboat_owner_id( int $sailboat_id ): int {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
        return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT User_ID FROM Sailboat WHERE Sailboat_ID = %d LIMIT 1', $sailboat_id ) );
    }

    /**
     * Повертає список мерилок для конкретного судна.
     *
     * @param int $sailboat_id ID судна.
     * @return array<int,array<string,mixed>>
     */
    public function get_list_by_sailboat( int $sailboat_id ): array {
        if ( $sailboat_id <= 0 ) {
            return [];
        }

        global $wpdb;

        $sql = 'SELECT * FROM Merilka WHERE Sailboat_ID = %d ORDER BY MR_DateObmera DESC, MR_ID DESC';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
        $rows = $wpdb->get_results( $wpdb->prepare( $sql, $sailboat_id ), ARRAY_A );

        if ( ! is_array( $rows ) || empty( $rows ) ) {
            return [];
        }

        $items = [];
        $is_first = true;

        foreach ( $rows as $row ) {
            $row['is_latest'] = $is_first; // Перша мерилка завжди актуальна
            $row['is_used']   = $this->is_merilka_used( (int) $row['MR_ID'] );
            $items[]          = $row;
            $is_first         = false;
        }

        return $items;
    }

    /**
     * Створює новий запис мерилки.
     */
    public function insert_merilka( array $data ): int {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $inserted = $wpdb->insert( 'Merilka', $data );
        if ( false === $inserted ) {
            throw new \RuntimeException( 'Помилка запису в БД.' );
        }
        return (int) $wpdb->insert_id;
    }

    /**
     * Оновлює існуючу мерилку.
     */
    public function update_merilka( int $mr_id, array $data ): bool {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $updated = $wpdb->update( 'Merilka', $data, [ 'MR_ID' => $mr_id ], null, [ '%d' ] );
        if ( false === $updated ) {
            throw new \RuntimeException( 'Помилка оновлення в БД.' );
        }
        return true;
    }

    /**
     * Видаляє мерилку.
     */
    public function delete_merilka( int $mr_id ): bool {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $deleted = $wpdb->delete( 'Merilka', [ 'MR_ID' => $mr_id ], [ '%d' ] );
        if ( false === $deleted ) {
            throw new \RuntimeException( 'Помилка видалення з БД.' );
        }
        return true;
    }
}