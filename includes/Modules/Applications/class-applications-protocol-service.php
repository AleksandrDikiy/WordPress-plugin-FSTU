<?php

namespace FSTU\Modules\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Сервіс протоколу модуля "Заявки в ФСТУ".
 * Відповідає за логування та рендер рядків протоколу.
 *
 * Version:     1.2.0
 * Date_update: 2026-04-07
 */
class Applications_Protocol_Service {

    public const LOG_NAME = 'Applications';
    public const STATUS_SUCCESS = 'успішно';

    /**
     * Пише запис у Logs.
     */
    public function log_action( string $type, string $text, string $status = self::STATUS_SUCCESS, bool $strict = true ): bool {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
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

        if ( false === $inserted ) {
            if ( $strict ) {
                throw new \RuntimeException( 'applications_log_insert_failed' );
            }

            return false;
        }

        return true;
    }

    /**
     * Безпечне логування поза strict success-flow.
     */
    public function try_log_action( string $type, string $text, string $status ): bool {
        try {
            return $this->log_action( $type, $text, $status, false );
        } catch ( \Throwable $throwable ) {
            return false;
        }
    }

    /**
     * Будує HTML рядків протоколу.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    public function build_protocol_rows( array $rows ): string {
        if ( empty( $rows ) ) {
            return '<tr class="fstu-row"><td colspan="6" class="fstu-applications-empty">Записів у протоколі не знайдено.</td></tr>';
        }

        $html = '';

        foreach ( $rows as $row ) {
            $html .= '<tr class="fstu-row">';
            $html .= '<td class="fstu-td fstu-td--date">' . esc_html( (string) ( $row['Logs_DateCreate'] ?? '' ) ) . '</td>';
            $html .= '<td class="fstu-td fstu-td--type">' . esc_html( $this->get_log_type_label( (string) ( $row['Logs_Type'] ?? '' ) ) ) . '</td>';
            $html .= '<td class="fstu-td fstu-td--operation">' . esc_html( (string) ( $row['Logs_Name'] ?? self::LOG_NAME ) ) . '</td>';
            $html .= '<td class="fstu-td fstu-td--message">' . esc_html( (string) ( $row['Logs_Text'] ?? '' ) ) . '</td>';
            $html .= '<td class="fstu-td fstu-td--status">' . esc_html( $this->get_log_status_label( (string) ( $row['Logs_Error'] ?? '' ) ) ) . '</td>';
            $html .= '<td class="fstu-td fstu-td--user">' . esc_html( $this->get_log_user_label( $row ) ) . '</td>';
            $html .= '</tr>';
        }

        return $html;
    }

    /**
     * Повертає лейбл типу логування.
     */
    public function get_log_type_label( string $type ): string {
        return match ( strtoupper( trim( $type ) ) ) {
            'I', 'INSERT' => 'I',
            'U', 'UPDATE' => 'U',
            'D', 'DELETE' => 'D',
            default       => $type,
        };
    }

    /**
     * Повертає лейбл статусу логування.
     */
    public function get_log_status_label( string $status ): string {
        return match ( trim( mb_strtolower( $status ) ) ) {
            '✓', 'success', 'успішно' => self::STATUS_SUCCESS,
            default                   => $status,
        };
    }

    /**
     * Повертає fallback-лейбл користувача протоколу.
     *
     * @param array<string, mixed> $row
     */
    public function get_log_user_label( array $row ): string {
        $fio = trim( (string) ( $row['FIO'] ?? '' ) );
        if ( '' !== $fio ) {
            return $fio;
        }

        $display_name = trim( (string) ( $row['User_DisplayName'] ?? '' ) );
        if ( '' !== $display_name ) {
            return $display_name;
        }

        $user_login = trim( (string) ( $row['User_Login'] ?? '' ) );
        if ( '' !== $user_login ) {
            return $user_login;
        }

        $user_id = (int) ( $row['User_ID'] ?? 0 );
        if ( $user_id <= 0 ) {
            return 'Система';
        }

        $user = get_userdata( $user_id );
        if ( $user instanceof \WP_User ) {
            $fallback_display_name = trim( (string) $user->display_name );
            if ( '' !== $fallback_display_name ) {
                return $fallback_display_name;
            }

            $fallback_user_login = trim( (string) $user->user_login );
            if ( '' !== $fallback_user_login ) {
                return $fallback_user_login;
            }
        }

        return 'ID:' . $user_id;
    }
}

