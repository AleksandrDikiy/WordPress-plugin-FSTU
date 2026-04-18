<?php
/**
 * Сервіс протоколювання (логування) модуля "Осередки федерації спортивного туризму".
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

class Class_Regional_FST_Protocol_Service {

    private const LOG_NAME = 'RegionalFST';

    /**
     * Логує успішну операцію в межах активної транзакції.
     * Якщо лог не запишеться, викидає виняток, що призведе до ROLLBACK всієї транзакції.
     *
     * @param string $type   Тип операції ('I', 'U', 'D').
     * @param string $text   Опис операції.
     * @param string $status Статус (наприклад, '✓').
     * @throws \Exception
     */
    public function log_action_transactional( string $type, string $text, string $status = '✓' ): void {
        global $wpdb;

        $inserted = $wpdb->insert(
            'Logs',
            [
                'User_ID'         => get_current_user_id(),
                'Logs_DateCreate' => current_time( 'mysql' ),
                'Logs_Type'       => $type,
                'Logs_Name'       => self::LOG_NAME,
                'Logs_Text'       => $text,
                'Logs_Error'      => $status,
            ],
            [ '%d', '%s', '%s', '%s', '%s', '%s' ]
        );

        if ( ! $inserted ) {
            throw new \Exception( __( 'Критична помилка логування. Транзакцію скасовано.', 'fstu' ) );
        }
    }

    /**
     * Логує помилку (викликається зазвичай у блоці catch після ROLLBACK).
     *
     * @param string $type  Тип операції, під час якої сталася помилка.
     * @param string $text  Короткий опис.
     * @param string $error Внутрішній код/текст помилки (безпечний).
     */
    public function log_error( string $type, string $text, string $error ): void {
        global $wpdb;

        $wpdb->insert(
            'Logs',
            [
                'User_ID'         => get_current_user_id(),
                'Logs_DateCreate' => current_time( 'mysql' ),
                'Logs_Type'       => $type,
                'Logs_Name'       => self::LOG_NAME,
                'Logs_Text'       => $text,
                'Logs_Error'      => $error,
            ],
            [ '%d', '%s', '%s', '%s', '%s', '%s' ]
        );
    }
}