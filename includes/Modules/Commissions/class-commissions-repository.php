<?php
/**
 * Repository для роботи з БД модуля "Комісії з видів туризму".
 * Усі запити виконуються через явні JOIN (без використання старих SQL Views).
 * * Version: 1.1.0
 * Date_update: 2026-04-24
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

        // "Магічне" визначення поточного складу (шукаємо останній рік формування комісії)
        if ( $year_id === 0 ) {
            $sql_max = "SELECT MAX(Year_ID) FROM Commission WHERE CommissionType_ID = %d AND SCommission_ID = %d";
            $args_max = [ $commission_type_id, $s_commission_id ];
            if ( 1 !== $commission_type_id ) {
                $sql_max .= " AND Region_ID = %d";
                $args_max[] = $region_id;
            }
            $found_year = (int) $wpdb->get_var( $wpdb->prepare( $sql_max, ...$args_max ) );
            // Якщо комісія хоч раз формувалася, беремо її останній рік. Інакше залишаємо поточний календарний.
            $year_id = $found_year > 0 ? $found_year : (int) date('Y');
        }

        // Замість vCommission використовуємо vUserFSTUnew для точного отримання ПІБ
        // Конвертуємо FIO з BLOB у CHAR
        $sql = "
			SELECT SQL_CALC_FOUND_ROWS
				c.Commission_ID, c.User_ID, c.SCommission_ID, c.CommissionType_ID, 
				c.Member_ID, c.Region_ID, c.Year_ID,
				CAST(COALESCE(vu.FIO, u.display_name) AS CHAR) AS FIO,
				u.user_email,
				sc.Commission_Name,
				sc.Commission_Number AS post_id,
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

        // Отримуємо ID прив'язаної сторінки (незалежно від того, чи є люди в комісії)
        $post_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT Commission_Number FROM S_Commission WHERE Commission_ID = %d", $s_commission_id ) );

        // Перевіряємо права для показу кнопки "Редагувати опис"
        $can_edit = current_user_can('administrator') ||
            current_user_can('globalregistrar') ||
            current_user_can('userregistrar') ||
            current_user_can('sailadministrator');

        return [
            'items'              => $items,
            'total'              => $total,
            'page'               => $page,
            'per_page'           => $per_page,
            'total_pages'        => ceil( $total / $per_page ),
            'resolved_year'      => $year_id, // Передаємо знайдений рік на фронтенд
            'commission_post_id' => $post_id,
            'can_edit_page'      => $can_edit
        ];
    }

    /**
     * Отримує список опитувань (поточні питання) для конкретної комісії.
     */
    public function get_polls_list( int $question_type_id, int $commission_type_id, int $s_commission_id, int $region_id, int $question_state = 0 ): array {
        global $wpdb;

        $sql = "
			SELECT 
				QuestionCommission_ID, Question_ID, Commission_ID, 
				Question_DateCreate, Question_DateBegin, Question_DateEnd, 
				QuestionType_ID, Question_Name, Question_Note, Question_URL, Question_State,
				SetCommission_CountMembers,
				Commission_Name,
				(SELECT COUNT(DISTINCT r.User_ID) FROM VotingResults r WHERE r.Question_ID = vqc.Question_ID) as votes_count
			FROM vQuestionCommission vqc
			WHERE QuestionType_ID = 1
			  AND Commission_ID = %d
			  AND CommissionType_ID = %d
		";

        $args = [ $s_commission_id, $commission_type_id ];

        if ( $question_state !== -1 ) {
            $sql .= " AND Question_State = %d";
            $args[] = $question_state;
        }
        if ( 1 !== $commission_type_id ) {
            $sql .= " AND Region_ID = %d";
            $args[] = $region_id;
        }

        $sql .= " ORDER BY Question_DateCreate DESC";

        return $wpdb->get_results( $wpdb->prepare( $sql, ...$args ), ARRAY_A ) ?? [];
    }

    /**
     * Отримує деталі конкретного опитування: варіанти відповідей, статистику та поіменний список (якщо дозволено).
     */
    public function get_poll_details( int $question_id, bool $show_names ): array {
        global $wpdb;

        // 1. Отримуємо варіанти відповідей, прив'язані до питання
        $sql_answers = "
            SELECT a.Answer_ID, a.Answer_Name 
            FROM AnswerQuestion aq
            INNER JOIN Answer a ON a.Answer_ID = aq.Answer_ID
            WHERE aq.Question_ID = %d
            ORDER BY a.Answer_ID ASC
        ";
        $answers = $wpdb->get_results( $wpdb->prepare( $sql_answers, $question_id ), ARRAY_A ) ?? [];

        // 2. Отримуємо загальну статистику
        $sql_stats = "SELECT Answer_ID, Answer_Name, cnt FROM vResultCommissionCount WHERE Question_ID = %d";
        $stats = $wpdb->get_results( $wpdb->prepare( $sql_stats, $question_id ), ARRAY_A ) ?? [];

        // 3. Поіменне голосування (тільки якщо публічне опитування або запитує Адмін)
        $voters = [];
        if ( $show_names ) {
            $sql_voters = "
                SELECT User_ID, FIO, Answer_Name, API, VotingResults_DateCreate, VotingResults_Note
                FROM vResultCommission 
                WHERE Question_ID = %d
                ORDER BY VotingResults_DateCreate DESC
            ";
            $voters = $wpdb->get_results( $wpdb->prepare( $sql_voters, $question_id ), ARRAY_A ) ?? [];
        }

        // 4. Голос поточного юзера
        $current_vote = $wpdb->get_var( $wpdb->prepare( "SELECT Answer_ID FROM VotingResults WHERE Question_ID = %d AND User_ID = %d LIMIT 1", $question_id, get_current_user_id() ) );

        return [
            'answers'      => $answers,
            'stats'        => $stats,
            'voters'       => $voters,
            'current_vote' => $current_vote
        ];
    }

    /**
     * Перевіряє, чи є користувач членом даної комісії в поточному році.
     */
    public function is_user_in_commission( int $user_id, int $s_commission_id, int $commission_type_id, int $region_id ): bool {
        global $wpdb;
        $sql = "
            SELECT COUNT(*) FROM Commission 
            WHERE User_ID = %d 
              AND SCommission_ID = %d 
              AND CommissionType_ID = %d
        ";
        $args = [ $user_id, $s_commission_id, $commission_type_id ];
        if ( 1 !== $commission_type_id ) {
            $sql .= " AND Region_ID = %d";
            $args[] = $region_id;
        }
        return (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$args ) ) > 0;
    }

    public function get_commission_email_group( int $s_commission_id ): string {
        $admin_email_setting = get_option( 'fstu_commission_email_' . $s_commission_id );
        if ( ! empty( $admin_email_setting ) ) return $admin_email_setting;
        global $wpdb;
        $email = $wpdb->get_var( $wpdb->prepare( "SELECT Commission_EmailGoogleGroup FROM S_Commission WHERE Commission_ID = %d", $s_commission_id ) );
        return $email ? (string) $email : get_option( 'admin_email' );
    }

    public function delete_member( int $id ): bool {
        global $wpdb;
        return (bool) $wpdb->delete( 'Commission', [ 'Commission_ID' => $id ], [ '%d' ] );
    }

    public function delete_poll( int $id ): bool {
        global $wpdb;
        $wpdb->query( 'START TRANSACTION' );
        $wpdb->delete( 'VotingResults', [ 'Question_ID' => $id ], [ '%d' ] );
        $res = $wpdb->delete( 'QuestionCommission', [ 'Question_ID' => $id ], [ '%d' ] );
        if ( false === $res ) {
            $wpdb->query( 'ROLLBACK' );
            return false;
        }
        $wpdb->query( 'COMMIT' );
        return true;
    }

    public function search_users( string $search_query ): array {
        global $wpdb;
        $sql = "SELECT User_ID, CAST(FIO AS CHAR) AS FIO FROM vUserFSTUnew WHERE UserFSTU = '1' OR UserFSTU = 1 GROUP BY User_ID";
        $all_users = $wpdb->get_results( $sql, ARRAY_A );
        if ( empty( $all_users ) ) return [];

        $results = [];
        $search_lower = mb_strtolower( trim( $search_query ), 'UTF-8' );

        foreach ( $all_users as $user ) {
            if ( empty( $user['FIO'] ) ) continue;
            if ( mb_strpos( mb_strtolower( $user['FIO'], 'UTF-8' ), $search_lower, 0, 'UTF-8' ) !== false ) {
                $results[] = [ 'User_ID' => $user['User_ID'], 'FIO' => $user['FIO'] ];
            }
            if ( count( $results ) >= 15 ) break;
        }
        usort( $results, function( $a, $b ) { return strcmp( $a['FIO'], $b['FIO'] ); } );
        return $results;
    }
    //----
}