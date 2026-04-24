<?php
/**
 * Repository для роботи з БД модуля Directory.
 *
 * Version:     1.1.0
 * Date_update: 2026-04-24
 *
 * @package FSTU\Modules\Directory
 */

namespace FSTU\Modules\Directory;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Directory_Repository {

    /**
     * Отримання списку членів Виконкому.
     */
    public function get_members(): array {
        global $wpdb;
        $sql = "SELECT Guidance_ID, User_ID, TypeGuidance_ID, 
                       GROUP_CONCAT(Guidance_Notes SEPARATOR ', ') AS Guidance_Notes, 
                       GROUP_CONCAT(MemberGuidance_Name SEPARATOR ', ') AS MemberGuidance_Name, 
                       MIN(MemberGuidance_Order) AS MemberGuidance_Order,  
                       FIO, FIOshort, email, TypeGuidance_Name
                FROM vGuidance 
                WHERE TypeGuidance_ID = 1
                GROUP BY User_ID 
                ORDER BY MemberGuidance_Order, FIO";

        $results = $wpdb->get_results( $sql, ARRAY_A );
        return is_array( $results ) ? $results : [];
    }

    /**
     * Отримує список опитувань Виконкому (TypeGuidance_ID = 1).
     */
    public function get_polls_list( int $type_guidance_id, int $question_state = 0, int $page = 1, int $per_page = 10 ): array {
        global $wpdb;

        // Отримуємо кількість членів Виконкому для статистики квоти
        $total_members = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT User_ID) FROM Guidance WHERE TypeGuidance_ID = %d", $type_guidance_id ) );

        $sql = "
            SELECT SQL_CALC_FOUND_ROWS
                QuestionGuidance_ID, Question_ID, TypeGuidance_ID,
                Question_DateCreate, Question_DateBegin, Question_DateEnd,
                Question_Name, Question_Note, Question_URL, Question_State,
                cnt as votes_count
            FROM vQuestionGuidance
            WHERE TypeGuidance_ID = %d
        ";

        $args = [ $type_guidance_id ];

        if ( $question_state !== -1 ) {
            $sql .= " AND Question_State = %d";
            $args[] = $question_state;
        }

        $sql .= " ORDER BY Question_DateCreate DESC LIMIT %d OFFSET %d";
        $args[] = $per_page;
        $args[] = ( $page - 1 ) * $per_page;

        $items = $wpdb->get_results( $wpdb->prepare( $sql, ...$args ), ARRAY_A ) ?? [];
        $total = (int) $wpdb->get_var( "SELECT FOUND_ROWS()" );

        // Додаємо загальну квоту до кожного опитування
        foreach ( $items as &$row ) {
            $row['Total_Members'] = $total_members;
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
     * Деталі опитування.
     */
    public function get_poll_details( int $question_id, bool $show_names ): array {
        global $wpdb;

        $sql_answers = "
            SELECT a.Answer_ID, a.Answer_Name 
            FROM AnswerQuestion aq
            INNER JOIN Answer a ON a.Answer_ID = aq.Answer_ID
            WHERE aq.Question_ID = %d
            ORDER BY a.Answer_ID ASC
        ";
        $answers = $wpdb->get_results( $wpdb->prepare( $sql_answers, $question_id ), ARRAY_A ) ?? [];

        $sql_stats = "SELECT Answer_Name, cnt FROM vResultGuidanceCount WHERE QuestionGuidance_ID = (SELECT QuestionGuidance_ID FROM QuestionGuidance WHERE Question_ID = %d LIMIT 1)";
        $stats = $wpdb->get_results( $wpdb->prepare( $sql_stats, $question_id ), ARRAY_A ) ?? [];

        $voters = [];
        if ( $show_names ) {
            $sql_voters = "
                SELECT User_ID, FIO, Answer_Name, APIName as API, VotingResults_DateCreate, VotingResults_Note
                FROM vResultGuidance 
                WHERE Question_ID = %d
                ORDER BY VotingResults_DateCreate DESC
            ";
            $voters = $wpdb->get_results( $wpdb->prepare( $sql_voters, $question_id ), ARRAY_A ) ?? [];
        }

        $current_vote = $wpdb->get_var( $wpdb->prepare( "SELECT Answer_ID FROM VotingResults WHERE Question_ID = %d AND User_ID = %d LIMIT 1", $question_id, get_current_user_id() ) );

        // Форматуємо статистику, оскільки vResultGuidanceCount не містить Answer_ID
        $formatted_stats = [];
        foreach ($answers as $ans) {
            $count = 0;
            foreach ($stats as $st) {
                if ($st['Answer_Name'] === $ans['Answer_Name']) {
                    $count = $st['cnt'];
                    break;
                }
            }
            $formatted_stats[] = [ 'Answer_ID' => $ans['Answer_ID'], 'Answer_Name' => $ans['Answer_Name'], 'cnt' => $count ];
        }

        return [
            'answers'      => $answers,
            'stats'        => $formatted_stats,
            'voters'       => $voters,
            'current_vote' => $current_vote
        ];
    }

    public function is_user_in_guidance( int $user_id, int $type_guidance_id ): bool {
        global $wpdb;
        $sql = "SELECT COUNT(*) FROM Guidance WHERE User_ID = %d AND TypeGuidance_ID = %d";
        return (int) $wpdb->get_var( $wpdb->prepare( $sql, $user_id, $type_guidance_id ) ) > 0;
    }
}