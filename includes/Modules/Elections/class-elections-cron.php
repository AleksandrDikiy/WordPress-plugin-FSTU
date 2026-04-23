<?php
/**
 * Фоновий агент (WP Cron) для моніторингу стадій виборів STV.
 * * Version: 1.0.0
 * Date_update: 2026-04-22
 */

namespace FSTU\Modules\Elections;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Elections_Cron {

    public function init(): void {
        // Реєструємо хук крону
        add_action( 'fstu_elections_stage_monitor', [ $this, 'monitor_elections' ] );

        // Плануємо щогодинне виконання, якщо ще не заплановано
        if ( ! wp_next_scheduled( 'fstu_elections_stage_monitor' ) ) {
            wp_schedule_event( time(), 'hourly', 'fstu_elections_stage_monitor' );
        }
    }

    /**
     * Основний метод моніторингу.
     */
    public function monitor_elections(): void {
        global $wpdb;
        $current_time = current_time( 'mysql' );

        // 1. Перевірка фази НОМІНАЦІЇ (nomination)
        $nominations = $wpdb->get_results(
            "SELECT * FROM Elections WHERE Status = 'nomination' AND Date_Nomination_End <= '{$current_time}'",
            ARRAY_A
        );

        if ( ! empty( $nominations ) ) {
            foreach ( $nominations as $election ) {
                // Рахуємо кількість ПІДТВЕРДЖЕНИХ кандидатів
                $candidates_count = (int) $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM Election_Candidates WHERE Election_ID = %d AND Status = 'confirmed'",
                    $election['Election_ID']
                ) );

                if ( $candidates_count < 10 ) {
                    // Подовжуємо термін (п. 2.3 Порядку) до 23:59:59
                    $extension_days = (int) $election['Settings_Extension_Days'];
                    $new_end_date = date( 'Y-m-d 23:59:59', strtotime( $election['Date_Nomination_End'] . " + {$extension_days} days" ) );

                    $wpdb->update( 'Elections', [ 'Date_Nomination_End' => $new_end_date ], [ 'Election_ID' => $election['Election_ID'] ] );

                    $this->log_cron_action( "Вибори ID {$election['Election_ID']}: Термін номінації подовжено на {$extension_days} днів (Кандидатів: {$candidates_count})", 'U' );
                } else {
                    // Переводимо у статус ГОЛОСУВАННЯ та фіксуємо дати (до 23:59:59)
                    $voting_days = (int) $election['Settings_Voting_Days'];
                    $voting_end  = date( 'Y-m-d 23:59:59', strtotime( $current_time . " + {$voting_days} days" ) );

                    $wpdb->update( 'Elections', [
                        'Status'            => 'voting',
                        'Date_Voting_Start' => $current_time,
                        'Date_Voting_End'   => $voting_end
                    ], [ 'Election_ID' => $election['Election_ID'] ] );

                    $this->log_cron_action( "Вибори ID {$election['Election_ID']}: Перехід у фазу ГОЛОСУВАННЯ", 'U' );
                }
            }
        }

        // 2. Перевірка фази ГОЛОСУВАННЯ (voting)
        $votings = $wpdb->get_results(
            "SELECT * FROM Elections WHERE Status = 'voting' AND Date_Voting_End <= '{$current_time}'",
            ARRAY_A
        );

        if ( ! empty( $votings ) ) {
            foreach ( $votings as $election ) {
                // Переводимо у статус ПІДРАХУНКУ
                $wpdb->update( 'Elections', [ 'Status' => 'calculation' ], [ 'Election_ID' => $election['Election_ID'] ] );
                $this->log_cron_action( "Вибори ID {$election['Election_ID']}: Голосування закрито. Перехід до ПІДРАХУНКУ", 'U' );

                // TODO: Ініціювання STV Engine (буде реалізовано в Етапі 5)
            }
        }
    }

    private function log_cron_action( string $text, string $type ): void {
        global $wpdb;
        $wpdb->insert( 'Logs', [
            'User_ID'         => 0, // Системна дія
            'Logs_DateCreate' => current_time( 'mysql' ),
            'Logs_Type'       => $type,
            'Logs_Name'       => 'Elections_Cron',
            'Logs_Text'       => $text,
            'Logs_Error'      => '✓'
        ], [ '%d', '%s', '%s', '%s', '%s', '%s' ] );
    }
}