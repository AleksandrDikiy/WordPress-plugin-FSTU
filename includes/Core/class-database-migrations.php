<?php
/**
 * Клас маршрутизації версіонованих міграцій бази даних.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-22
 *
 * @package FSTU\Core
 */

namespace FSTU\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Database_Migrations {

    /**
     * Запускає всі міграції, які більші за встановлену версію.
     *
     * @param string $installed Встановлена версія БД.
     * @param string $target    Цільова версія БД.
     */
    public static function run( string $installed, string $target ): void {
        // Масив усіх міграцій (Версія => Клас)
        $migrations = [
            '1.3.0' => '\FSTU\Migrations\Migration_1_3_0',
            '1.3.1' => '\FSTU\Migrations\Migration_1_3_0', // Примусовий перезапуск для створення таблиць
        ];

        foreach ( $migrations as $version => $class_name ) {
            // Якщо міграція новіша за встановлену версію
            if ( version_compare( $installed, $version, '<' ) ) {
                if ( class_exists( $class_name ) && method_exists( $class_name, 'up' ) ) {
                    call_user_func( [ $class_name, 'up' ] );
                }
            }
        }
    }
}