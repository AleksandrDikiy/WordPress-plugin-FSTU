<?php
/**
 * Сервіс модуля "Осередки федерації спортивного туризму".
 * Відповідає за бізнес-логіку: створення, оновлення, видалення посад з використанням транзакцій.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-18
 *
 * @package FSTU\Modules\RegionalFST
 */

namespace FSTU\Modules\RegionalFST;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Class_Regional_FST_Service {

    /**
     * Збереження запису (Create / Update) в межах транзакції.
     *
     * @param array $data Очищені вхідні дані.
     * @throws \Exception У разі помилки збереження.
     */
    public function save_item( array $data ): void {
        global $wpdb;

        $id                = absint( $data['RegionalFST_ID'] ?? 0 );
        $user_id           = absint( $data['User_ID'] ?? 0 );
        $unit_id           = absint( $data['Unit_ID'] ?? 0 );
        $member_reg_id     = absint( $data['MemberRegional_ID'] ?? 0 );
        $current_user_id   = get_current_user_id();

        if ( ! $user_id || ! $unit_id || ! $member_reg_id ) {
            throw new \Exception( __( 'Заповніть усі обов\'язкові поля.', 'fstu' ) );
        }

        // Визначаємо Region_ID на основі вибраного Unit_ID
        $region_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT Region_ID FROM S_Unit WHERE Unit_ID = %d", $unit_id ) );
        if ( ! $region_id ) {
            throw new \Exception( __( 'Не знайдено регіон для обраного осередку.', 'fstu' ) );
        }

        $protocol = new Class_Regional_FST_Protocol_Service();

        // Відкриваємо транзакцію
        $wpdb->query( 'START TRANSACTION' );

        try {
            if ( $id > 0 ) {
                // ОНОВЛЕННЯ (UPDATE)
                $updated = $wpdb->update(
                    'RegionalFST',
                    [
                        'Unit_ID'           => $unit_id,
                        'Region_ID'         => $region_id,
                        'MemberRegional_ID' => $member_reg_id,
                    ],
                    [ 'RegionalFST_ID' => $id ],
                    [ '%d', '%d', '%d' ],
                    [ '%d' ]
                );

                if ( false === $updated ) {
                    throw new \Exception( __( 'Помилка оновлення запису в БД.', 'fstu' ) );
                }

                // Обов'язкове логування
                $protocol->log_action_transactional( 'U', sprintf( 'Оновлено посаду в осередку ID: %d', $id ), '✓' );

            } else {
                // СТВОРЕННЯ (INSERT)
                $inserted = $wpdb->insert(
                    'RegionalFST',
                    [
                        'RegionalFST_DateCreate' => current_time( 'mysql' ),
                        'RegionalFST_DateBegin'  => current_time( 'mysql' ),
                        'User_Create'            => $current_user_id,
                        'User_ID'                => $user_id,
                        'Region_ID'              => $region_id,
                        'Unit_ID'                => $unit_id,
                        'MemberRegional_ID'      => $member_reg_id,
                    ],
                    [ '%s', '%s', '%d', '%d', '%d', '%d', '%d' ]
                );

                if ( ! $inserted ) {
                    throw new \Exception( __( 'Помилка збереження нового запису в БД.', 'fstu' ) );
                }

                // Обов'язкове логування
                $protocol->log_action_transactional( 'I', sprintf( 'Додано нову посаду (User ID: %d, Unit ID: %d)', $user_id, $unit_id ), '✓' );
            }

            // Фіксуємо зміни
            $wpdb->query( 'COMMIT' );

        } catch ( \Exception $e ) {
            $wpdb->query( 'ROLLBACK' );

            // Логуємо помилку
            $protocol->log_error( ( $id > 0 ? 'U' : 'I' ), 'Помилка збереження посади', 'error' );

            throw $e;
        }
    }

    /**
     * Видалення запису (Delete) в межах транзакції.
     *
     * @param int $id ID запису.
     * @throws \Exception У разі помилки видалення.
     */
    public function delete_item( int $id ): void {
        global $wpdb;

        if ( $id <= 0 ) {
            throw new \Exception( __( 'Невірний ID запису.', 'fstu' ) );
        }

        $protocol = new Class_Regional_FST_Protocol_Service();

        $wpdb->query( 'START TRANSACTION' );

        try {
            $deleted = $wpdb->delete( 'RegionalFST', [ 'RegionalFST_ID' => $id ], [ '%d' ] );

            if ( ! $deleted ) {
                throw new \Exception( __( 'Запис не знайдено або вже видалено.', 'fstu' ) );
            }

            $protocol->log_action_transactional( 'D', sprintf( 'Видалено посаду в осередку ID: %d', $id ), '✓' );

            $wpdb->query( 'COMMIT' );

        } catch ( \Exception $e ) {
            $wpdb->query( 'ROLLBACK' );
            $protocol->log_error( 'D', sprintf( 'Помилка видалення ID: %d', $id ), 'error' );
            throw $e;
        }
    }
}