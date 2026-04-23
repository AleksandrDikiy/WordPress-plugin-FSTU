<?php
/**
 * Сервіс бізнес-логіки модуля виборів.
 * * Version: 1.0.0
 * Date_update: 2026-04-22
 */

namespace FSTU\Modules\Elections;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Elections_Service {

    private Elections_Repository $repository;

    public function __construct() {
        $this->repository = new Elections_Repository();
    }

    /**
     * Перевіряє, чи є користувач головою конкретної комісії.
     * Базується на таблиці Commission та типі посади (Member_ID = 1 - Голова).
     */
    public function is_commission_head( int $user_id, int $tourism_type_id ): bool {
        global $wpdb;
        // Шукаємо актуальний запис у таблиці Commission для цього виду туризму з роллю Голови
        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM Commission c 
             JOIN S_Commission sc ON sc.Commission_ID = c.SCommission_ID
             WHERE c.User_ID = %d 
             AND sc.TourismType_ID = %d 
             AND c.Member_ID = 1",
            $user_id,
            $tourism_type_id
        );
        return (int) $wpdb->get_var( $sql ) > 0;
    }

    /**
     * Валідація кількості мандатів (3-15).
     */
    public function validate_candidates_count( int $count ): int {
        if ( $count < 3 ) return 3;
        if ( $count > 15 ) return 15;
        return $count;
    }

    public function get_repository(): Elections_Repository {
        return $this->repository;
    }

    /**
     * Перевіряє чи є у користувача заборгованість по внесках.
     * Вимога: сплачений внесок до ФСТУ (та вітрильний, якщо це вітрильні вибори)
     * АБО за поточний, АБО за попередній рік.
     */
    public function has_dues_debt( int $user_id, int $election_id = 0 ): bool {
        global $wpdb;
        $current_year = (int) gmdate( 'Y' );
        $prev_year    = $current_year - 1;

        // 1. Перевірка базового внеску ФСТУ (має бути хоча б 1 запис: поточний АБО попередній)
        $fstu_paid = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM Dues WHERE User_ID = %d AND Year_ID IN (%d, %d)",
            $user_id, $current_year, $prev_year
        ) );

        if ( $fstu_paid === 0 ) {
            return true; // Немає жодного базового внеску за 2 роки -> БОРГ
        }

        // 2. Якщо передано ID виборів, перевіряємо чи це вітрильний туризм
        if ( $election_id > 0 ) {
            $like = '%' . $wpdb->esc_like( 'ітрил' ) . '%';
            $is_sailing = $wpdb->get_var( $wpdb->prepare(
                "SELECT 1 FROM Elections e 
                 JOIN S_TourismType t ON t.TourismType_ID = e.TourismType_ID 
                 WHERE e.Election_ID = %d AND t.TourismType_Name LIKE %s",
                $election_id, $like
            ) );

            if ( $is_sailing ) {
                // Перевіряємо вітрильні внески (таблиця DuesSail)
                $sail_paid = (int) $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM DuesSail WHERE User_ID = %d AND Year_ID IN (%d, %d)",
                    $user_id, $current_year, $prev_year
                ) );

                if ( $sail_paid === 0 ) {
                    return true; // Немає вітрильного внеску -> БОРГ
                }
            }
        }

        return false; // Всі необхідні внески сплачено!
    }
}