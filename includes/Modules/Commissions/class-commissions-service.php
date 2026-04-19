<?php
/**
 * Service для бізнес-логіки модуля "Комісії з видів туризму (Board)".
 *
 * * Version: 1.0.0
 * Date_update: 2026-04-18
 */

namespace FSTU\Modules\Commissions;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Commissions_Service {

    /**
     * Перевіряє, чи сплатив користувач членський внесок у поточному році.
     *
     * @param int $user_id ID користувача.
     * @return bool
     */
    public function has_paid_dues_current_year( int $user_id ): bool {
        global $wpdb;
        $dues = $wpdb->get_var( $wpdb->prepare( "SELECT GetUserDues(%d, YEAR(now()))", $user_id ) );

        return (float) $dues > 0;
    }

    /**
     * Отримує кількість голосів "ЗА", які користувач вже віддав у конкретному опитуванні.
     *
     * @param int $user_id     ID користувача.
     * @param int $question_id ID опитування.
     * @return int
     */
    public function get_user_yes_votes_count( int $user_id, int $question_id ): int {
        global $wpdb;
        $sql = "
			SELECT COUNT(r.CandidatesCommissionResult_ID) 
			FROM CandidatesCommissionResult r
			INNER JOIN CandidatesCommission c ON c.CandidatesCommission_ID = r.CandidatesCommission_ID
			WHERE c.Question_ID = %d 
			  AND r.User_ID = %d 
			  AND r.CandidatesCommissionResult_Value = 1
		";
        return (int) $wpdb->get_var( $wpdb->prepare( $sql, $question_id, $user_id ) );
    }
    /**
     * Записує голос користувача за кандидата з перевірками квот та правил самовідводу.
     *
     * @param int $user_id      ID користувача, що голосує.
     * @param int $candidate_id ID запису кандидата.
     * @param int $vote_value   Голос (1 - ЗА, 2 - ПРОТИ, 0 - УТРИМ).
     * @return bool|string|\WP_Error True у разі успіху, строка 'self_removed' при самовідводі, або WP_Error.
     */
    public function cast_vote( int $user_id, int $candidate_id, int $vote_value ) {
        global $wpdb;

        // 1. Отримуємо Question_ID, квоту та User_ID самого кандидата
        $sql_info = "
			SELECT c.Question_ID, vqc.SetCommission_CountMembers, u.display_name, c.User_ID as Candidate_User_ID
			FROM CandidatesCommission c
			INNER JOIN vQuestionCommission vqc ON vqc.Question_ID = c.Question_ID
			LEFT JOIN {$wpdb->users} u ON u.ID = c.User_ID
			WHERE c.CandidatesCommission_ID = %d
		";
        $info = $wpdb->get_row( $wpdb->prepare( $sql_info, $candidate_id ), ARRAY_A );

        if ( ! $info ) {
            return new \WP_Error( 'not_found', 'Кандидата не знайдено.' );
        }

        $question_id    = (int) $info['Question_ID'];
        $quota          = (int) $info['SetCommission_CountMembers'];
        $candidate_uid  = (int) $info['Candidate_User_ID'];
        $candidate_name = $info['display_name'] ?: 'Невідомий';

        // 2. Правила само-голосування
        if ( $user_id === $candidate_uid ) {
            if ( $vote_value === 1 ) {
                return new \WP_Error( 'self_vote_yes', 'Ви не можете голосувати ЗА себе. Дозволено лише УТРИМАВСЯ.' );
            }
            if ( $vote_value === 2 ) {
                // Зняття своєї кандидатури (Самовідвід)
                $wpdb->query( 'START TRANSACTION' );
                $wpdb->delete( 'CandidatesCommissionResult', [ 'CandidatesCommission_ID' => $candidate_id ], [ '%d' ] );
                $res = $wpdb->delete( 'CandidatesCommission', [ 'CandidatesCommission_ID' => $candidate_id ], [ '%d' ] );

                if ( false === $res ) {
                    $wpdb->query( 'ROLLBACK' );
                    return new \WP_Error( 'db_error', 'Помилка бази даних при знятті кандидатури.' );
                }

                $this->protocol->log_action( 'D', "Кандидат {$candidate_name} зняв свою кандидатуру (самовідвід) в опитуванні ID: {$question_id}", '✓' );
                $wpdb->query( 'COMMIT' );

                return 'self_removed'; // Спеціальний статус для AJAX
            }
        }

        // 3. Елегантне обмеження: якщо голосуємо "ЗА", всі інші "ЗА" цього юзера
        // в цьому опитуванні автоматично стають "УТРИМАВСЯ" (0)
        if ( $vote_value === 1 ) {
            $sql_reset = "
                UPDATE CandidatesCommissionResult cr
                INNER JOIN CandidatesCommission cc ON cc.CandidatesCommission_ID = cr.CandidatesCommission_ID
                SET cr.CandidatesCommissionResult_Value = 0
                WHERE cc.Question_ID = %d 
                  AND cr.User_ID = %d 
                  AND cr.CandidatesCommissionResult_Value = 1
                  AND cr.CandidatesCommission_ID != %d
            ";
            $wpdb->query( $wpdb->prepare( $sql_reset, $question_id, $user_id, $candidate_id ) );
        }

        // 4. Записуємо голос
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT CandidatesCommissionResult_ID FROM CandidatesCommissionResult WHERE CandidatesCommission_ID = %d AND User_ID = %d",
            $candidate_id, $user_id
        ) );

        if ( $existing ) {
            $result = $wpdb->update(
                'CandidatesCommissionResult',
                [ 'CandidatesCommissionResult_Value' => $vote_value ],
                [ 'CandidatesCommissionResult_ID' => $existing ],
                [ '%d' ],
                [ '%d' ]
            );
        } else {
            $result = $wpdb->insert(
                'CandidatesCommissionResult',
                [
                    'CandidatesCommission_ID'          => $candidate_id,
                    'User_ID'                          => $user_id,
                    'CandidatesCommissionResult_Value' => $vote_value,
                ],
                [ '%d', '%d', '%d' ]
            );
        }

        if ( false === $result ) {
            return new \WP_Error( 'db_error', 'Помилка збереження в базу даних.' );
        }

        return true;
    }
    //---
}