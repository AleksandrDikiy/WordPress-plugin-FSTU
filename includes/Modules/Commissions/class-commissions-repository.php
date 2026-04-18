<?php
/**
 * Repository для роботи з БД модуля "Комісії з видів туризму".
 * Усі запити виконуються через явні JOIN (без використання старих SQL Views).
 * * Version: 1.0.0
 * Date_update: 2026-04-18
 */

namespace FSTU\Modules\Commissions;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Commissions_Repository {

    /**
     * Отримує склад комісії (заміна старого vCommission).
     *
     * @param int $year_id            Рік.
     * @param int $commission_type_id Тип комісії (1 - ЦМК, 2 - Регіональна тощо).
     * @param int $s_commission_id    Код конкретної комісії (напр., пішохідна).
     * @param int $region_id          Код регіону (ігнорується для ЦМК).
     * @return array
     */
    public function get_members_list( int $year_id, int $commission_type_id, int $s_commission_id, int $region_id, int $page = 1, int $per_page = 10, string $search = '' ): array {
        global $wpdb;

        // Замість vCommission використовуємо vUserFSTUnew для точного отримання ПІБ
        // Конвертуємо FIO з BLOB у CHAR
        $sql = "
			SELECT SQL_CALC_FOUND_ROWS
				c.Commission_ID, c.User_ID, c.SCommission_ID, c.CommissionType_ID, 
				c.Member_ID, c.Region_ID, c.Year_ID,
				CAST(COALESCE(vu.FIO, u.display_name) AS CHAR) AS FIO,
				u.user_email,
				sc.Commission_Name,
				ct.CommissionType_Name,
				m.Member_Name,
				r.Region_Name
			FROM Commission c
			LEFT JOIN S_Commission sc ON sc.Commission_ID = c.SCommission_ID
			LEFT JOIN S_CommissionType ct ON ct.CommissionType_ID = c.CommissionType_ID
			LEFT JOIN S_Member m ON m.Member_ID = c.Member_ID
			LEFT JOIN S_Region r ON r.Region_ID = c.Region_ID
			LEFT JOIN {$wpdb->users} u ON u.ID = c.User_ID
			LEFT JOIN vUserFSTUnew vu ON vu.User_ID = c.User_ID
			WHERE c.Year_ID = %d 
			  AND c.CommissionType_ID = %d 
			  AND c.SCommission_ID = %d
		";

        $args = [ $year_id, $commission_type_id, $s_commission_id ];

        if ( 1 !== $commission_type_id ) {
            $sql .= " AND c.Region_ID = %d";
            $args[] = $region_id;
        }

        if ( ! empty( $search ) ) {
            $sql .= " AND (vu.FIO LIKE %s OR u.display_name LIKE %s)";
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $args[] = $like;
            $args[] = $like;
        }

        // Групуємо по ID запису комісії, щоб уникнути дублювання через vUserFSTUnew
        $sql .= " GROUP BY c.Commission_ID ORDER BY m.Member_Order ASC LIMIT %d OFFSET %d";
        $args[] = $per_page;
        $args[] = ( $page - 1 ) * $per_page;

        $items = $wpdb->get_results( $wpdb->prepare( $sql, ...$args ), ARRAY_A ) ?? [];
        $total = (int) $wpdb->get_var( "SELECT FOUND_ROWS()" );

        // Отримання телефонів як у старому Commission.php
        foreach ( $items as &$row ) {
            $phones = [];
            $p1 = get_user_meta( $row['User_ID'], 'PhoneMobile', true );
            $p2 = get_user_meta( $row['User_ID'], 'Phone2', true );
            $p3 = get_user_meta( $row['User_ID'], 'Phone3', true );
            if ( $p1 ) $phones[] = $p1;
            if ( $p2 ) $phones[] = $p2;
            if ( $p3 ) $phones[] = $p3;
            $row['Phones'] = implode( '<br>', $phones );
        }

        return [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total / $per_page )
        ];
    }

    /**
     * Отримує список опитувань для конкретної комісії (заміна vQuestionCommission).
     *
     * @param int $question_type_id   Тип опитування (1 - рішення, 2 - вибори).
     * @param int $commission_type_id Тип комісії.
     * @param int $s_commission_id    Код комісії.
     * @param int $region_id          Код регіону.
     * @param int $question_state     Статус (0 - публічний, 1 - приватний).
     * @return array
     */
    public function get_polls_list( int $question_type_id, int $commission_type_id, int $s_commission_id, int $region_id, int $question_state = 0 ): array {
        global $wpdb;

        $sql = "
			SELECT 
				qc.QuestionCommission_ID, qc.Question_ID, qc.Commission_ID, 
				qc.Question_DateCreate, qc.Question_DateBegin, qc.Question_DateEnd, 
				qc.QuestionType_ID, qc.Question_Name, qc.Question_State, qc.Question_URL,
				qc.SetCommission_CountMembers,
				sc.Commission_Name,
				(SELECT COUNT(*) FROM CandidatesCommissionResult cr WHERE cr.Question_ID = qc.Question_ID) as votes_count
			FROM QuestionCommission qc
			LEFT JOIN S_Commission sc ON sc.Commission_ID = qc.Commission_ID
			WHERE qc.QuestionType_ID = %d
			  AND qc.Commission_ID = %d
			  AND qc.CommissionType_ID = %d
			  AND qc.Question_State = %d
		";

        $args = [ $question_type_id, $s_commission_id, $commission_type_id, $question_state ];

        if ( 1 !== $commission_type_id ) {
            $sql .= " AND qc.Region_ID = %d";
            $args[] = $region_id;
        }

        $sql .= " ORDER BY qc.Question_DateCreate DESC";

        return $wpdb->get_results( $wpdb->prepare( $sql, ...$args ), ARRAY_A ) ?? [];
    }

    /**
     * Отримує налаштування Email групи для комісії.
     * Використовуватиметься Mailer сервісом.
     *
     * @param int $s_commission_id Код комісії.
     * @return string
     */
    public function get_commission_email_group( int $s_commission_id ): string {
        // Пізніше ми додамо це поле в Options API WordPress (інтерфейс в адмінці),
        // але поки забезпечуємо fallback на рівні БД, якщо налаштування відсутнє.
        $admin_email_setting = get_option( 'fstu_commission_email_' . $s_commission_id );

        if ( ! empty( $admin_email_setting ) ) {
            return $admin_email_setting;
        }

        global $wpdb;
        $email = $wpdb->get_var( $wpdb->prepare( "SELECT Commission_EmailGoogleGroup FROM S_Commission WHERE Commission_ID = %d", $s_commission_id ) );

        return $email ? (string) $email : get_option( 'admin_email' );
    }
    /**
     * Отримує список кандидатів для конкретного опитування з підрахунком голосів.
     *
     * @param int $question_id     ID опитування.
     * @param int $current_user_id ID поточного користувача (для визначення його голосу).
     * @return array
     */
    public function get_candidates_list( int $question_id, int $current_user_id = 0 ): array {
        global $wpdb;

        // Отримуємо кандидатів, їхні програми та агреговані голоси (ЗА, ПРОТИ, УТРИМАВСЯ)
        $sql = "
			SELECT 
				c.CandidatesCommission_ID, c.Question_ID, c.User_ID, 
				c.CandidatesCommission_Direction AS Direction, 
				c.CandidatesCommission_Development AS Development, 
				c.CandidatesCommission_URL AS URL,
				u.display_name AS FIO,
				(SELECT COUNT(*) FROM CandidatesCommissionResult r WHERE r.CandidatesCommission_ID = c.CandidatesCommission_ID AND r.CandidatesCommissionResult_Value = 1) AS cnt_yes,
				(SELECT COUNT(*) FROM CandidatesCommissionResult r WHERE r.CandidatesCommission_ID = c.CandidatesCommission_ID AND r.CandidatesCommissionResult_Value = 2) AS cnt_no,
				(SELECT COUNT(*) FROM CandidatesCommissionResult r WHERE r.CandidatesCommission_ID = c.CandidatesCommission_ID AND r.CandidatesCommissionResult_Value = 0) AS cnt_abstain,
				(SELECT CandidatesCommissionResult_Value FROM CandidatesCommissionResult r WHERE r.CandidatesCommission_ID = c.CandidatesCommission_ID AND r.User_ID = %d LIMIT 1) AS current_user_vote
			FROM CandidatesCommission c
			LEFT JOIN {$wpdb->users} u ON u.ID = c.User_ID
			WHERE c.Question_ID = %d
			ORDER BY cnt_yes DESC, cnt_no ASC
		";

        return $wpdb->get_results( $wpdb->prepare( $sql, $current_user_id, $question_id ), ARRAY_A ) ?? [];
    }
    /**
     * Видаляє члена комісії.
     */
    public function delete_member( int $id ): bool {
        global $wpdb;
        return (bool) $wpdb->delete( 'Commission', [ 'Commission_ID' => $id ], [ '%d' ] );
    }

    /**
     * Видаляє опитування та всі пов'язані результати.
     */
    public function delete_poll( int $id ): bool {
        global $wpdb;
        $wpdb->query( 'START TRANSACTION' );

        // Видаляємо результати голосувань за кандидатів цього опитування
        $wpdb->query( $wpdb->prepare( "DELETE FROM CandidatesCommissionResult WHERE CandidatesCommission_ID IN (SELECT CandidatesCommission_ID FROM CandidatesCommission WHERE Question_ID = %d)", $id ) );
        // Видаляємо самих кандидатів
        $wpdb->delete( 'CandidatesCommission', [ 'Question_ID' => $id ], [ '%d' ] );
        // Видаляємо опитування
        $res = $wpdb->delete( 'QuestionCommission', [ 'Question_ID' => $id ], [ '%d' ] );

        if ( false === $res ) {
            $wpdb->query( 'ROLLBACK' );
            return false;
        }
        $wpdb->query( 'COMMIT' );
        return true;
    }

    /**
     * Розрахунок статистики для прогрес-барів.
     */
    public function get_voting_stats( int $question_id ): array {
        global $wpdb;
        // Отримуємо квоту та кількість тих, хто сплатив внески (легітимність)
        $sql = "SELECT qc.SetCommission_CountMembers as quota, 
                (SELECT COUNT(*) FROM CandidatesCommissionResult WHERE Question_ID = qc.Question_ID) as total_voted
                FROM QuestionCommission qc WHERE qc.Question_ID = %d";
        return $wpdb->get_row( $wpdb->prepare( $sql, $question_id ), ARRAY_A ) ?? [];
    }

    /**
     * Пошук користувачів для AJAX Autocomplete (Надійна фільтрація на стороні PHP).
     */
    public function search_users( string $search_query ): array {
        global $wpdb;

        // 1. Отримуємо всіх активних членів (Цей простий запит без LIKE працює бездоганно)
        $sql = "SELECT User_ID, CAST(FIO AS CHAR) AS FIO FROM vUserFSTUnew WHERE UserFSTU = '1' OR UserFSTU = 1";
        $all_users = $wpdb->get_results( $sql, ARRAY_A );

        if ( empty( $all_users ) ) {
            return [];
        }

        $results = [];
        // Переводимо запит у нижній регістр для незалежного від регістру пошуку
        $search_lower = mb_strtolower( trim( $search_query ), 'UTF-8' );

        // 2. Фільтруємо масив засобами PHP (обходимо всі проблеми кодувань MySQL)
        foreach ( $all_users as $user ) {
            if ( empty( $user['FIO'] ) ) {
                continue;
            }

            $fio_lower = mb_strtolower( $user['FIO'], 'UTF-8' );

            // Якщо знаходимо збіг підрядка (аналог LIKE %search%)
            if ( mb_strpos( $fio_lower, $search_lower, 0, 'UTF-8' ) !== false ) {
                $results[] = [
                    'User_ID' => $user['User_ID'],
                    'FIO'     => $user['FIO']
                ];
            }

            // Ліміт результатів для виводу в модальному вікні
            if ( count( $results ) >= 30 ) {
                break;
            }
        }

        // Сортуємо результати за алфавітом
        usort( $results, function( $a, $b ) {
            return strcmp( $a['FIO'], $b['FIO'] );
        } );

        return $results;
    }
}