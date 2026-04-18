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
     * Здійснює голосування користувача за кандидата.
     *
     * @param int $user_id      ID користувача.
     * @param int $candidate_id ID кандидата (CandidatesCommission_ID).
     * @param int $vote_value   Голос: 0 - Утримався, 1 - ЗА, 2 - ПРОТИ.
     * @return \WP_Error|true
     */
    public function cast_vote( int $user_id, int $candidate_id, int $vote_value ) {
        global $wpdb;

        // 1. Отримуємо Question_ID та ліміт комісії
        $sql_info = "
			SELECT c.Question_ID, qc.SetCommission_CountMembers, u.display_name
			FROM CandidatesCommission c
			INNER JOIN QuestionCommission qc ON qc.Question_ID = c.Question_ID
			LEFT JOIN {$wpdb->users} u ON u.ID = c.User_ID
			WHERE c.CandidatesCommission_ID = %d
		";
        $info = $wpdb->get_row( $wpdb->prepare( $sql_info, $candidate_id ) );

        if ( ! $info ) {
            return new \WP_Error( 'not_found', __( 'Кандидата не знайдено.', 'fstu' ) );
        }

        // 2. Бізнес-правило: Перевірка сплати внесків
        if ( ! $this->has_paid_dues_current_year( $user_id ) ) {
            return new \WP_Error( 'no_dues', __( 'Голосування неможливе. Ви не сплатили членський внесок за поточний рік.', 'fstu' ) );
        }

        // 3. Перевірка чи існує вже голос
        $existing_vote_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT CandidatesCommissionResult_ID FROM CandidatesCommissionResult WHERE CandidatesCommission_ID = %d AND User_ID = %d",
            $candidate_id, $user_id
        ) );

        // 4. Бізнес-правило: Ліміт голосів "ЗА" (якщо це новий голос "ЗА" або зміна на "ЗА")
        if ( 1 === $vote_value ) {
            $current_yes = $this->get_user_yes_votes_count( $user_id, (int) $info->Question_ID );

            // Якщо голос вже існує і він був "ЗА", то ми його не плюсуємо до ліміту
            $is_already_yes = $existing_vote_id ? ( 1 === (int) $wpdb->get_var( $wpdb->prepare( "SELECT CandidatesCommissionResult_Value FROM CandidatesCommissionResult WHERE CandidatesCommissionResult_ID = %d", $existing_vote_id ) ) ) : false;

            if ( ! $is_already_yes && $current_yes >= (int) $info->SetCommission_CountMembers ) {
                return new \WP_Error( 'limit_reached', sprintf( __( 'Ви вже вичерпали ліміт голосів "ЗА" (%d).', 'fstu' ), $info->SetCommission_CountMembers ) );
            }
        }

        $protocol = new Commissions_Protocol_Service();
        $vote_labels = [ 0 => 'УТРИМАВСЯ', 1 => 'ЗА', 2 => 'ПРОТИ' ];
        $label = $vote_labels[ $vote_value ] ?? 'НЕВІДОМО';
        $log_text = sprintf( 'Голосування: %s за кандидата %s', $label, $info->display_name );

        // 5. Транзакційне збереження
        $wpdb->query( 'START TRANSACTION' );

        if ( $existing_vote_id ) {
            $result = $wpdb->update(
                'CandidatesCommissionResult',
                [ 'CandidatesCommissionResult_Value' => $vote_value ],
                [ 'CandidatesCommissionResult_ID' => $existing_vote_id ],
                [ '%d' ],
                [ '%d' ]
            );
            $log_type = 'U';
        } else {
            $result = $wpdb->insert(
                'CandidatesCommissionResult',
                [
                    'CandidatesCommissionResult_DateCreate' => current_time( 'mysql' ),
                    'CandidatesCommission_ID'               => $candidate_id,
                    'User_ID'                               => $user_id,
                    'CandidatesCommissionResult_Value'      => $vote_value,
                ],
                [ '%s', '%d', '%d', '%d' ]
            );
            $log_type = 'I';
        }

        if ( false === $result ) {
            $wpdb->query( 'ROLLBACK' );
            $protocol->log_action( $log_type, $log_text, 'error' );
            return new \WP_Error( 'db_error', __( 'Помилка збереження голосу.', 'fstu' ) );
        }

        // Логування в межах транзакції
        $protocol->log_action( $log_type, $log_text, '✓' );
        $wpdb->query( 'COMMIT' );

        return true;
    }
}