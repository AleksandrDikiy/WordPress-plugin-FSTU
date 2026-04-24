<?php
/**
 * Service для бізнес-логіки модуля "Комісії з видів туризму (Board)".
 *
 * * Version: 1.1.0
 * Date_update: 2026-04-24
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
     * Записує голос користувача за поточне питання (документ).
     */
    public function cast_issue_vote( int $user_id, int $question_id, int $answer_id, int $commission_type_id, int $s_commission_id, int $region_id ) {
        global $wpdb;

        // 1. Отримуємо інформацію про опитування та перевіряємо його статус
        $poll = $wpdb->get_row( $wpdb->prepare( "SELECT Question_Name, Question_DateEnd FROM Question WHERE Question_ID = %d", $question_id ) );

        if ( ! $poll ) {
            return new \WP_Error( 'not_found', 'Опитування не знайдено.' );
        }

        if ( current_time( 'Y-m-d' ) > $poll->Question_DateEnd ) {
            return new \WP_Error( 'expired', 'Термін голосування завершено. Опитування закрито.' );
        }

        // 2. Перевірка доступу (Адмін або член комісії)
        $is_admin = current_user_can( 'administrator' );
        if ( ! $is_admin ) {
            if ( ! current_user_can( 'userfstu' ) ) {
                return new \WP_Error( 'no_access', 'Голосувати можуть тільки члени ФСТУ.' );
            }

            $repository = new Commissions_Repository();
            if ( ! $repository->is_user_in_commission( $user_id, $s_commission_id, $commission_type_id, $region_id ) ) {
                return new \WP_Error( 'not_in_commission', 'Тільки члени поточної комісії мають право голосувати за це питання.' );
            }
        }

        // 3. Збереження голосу (з можливістю переголосувати)
        $wpdb->query( 'START TRANSACTION' );

        // Видаляємо попередній голос цього юзера в цьому опитуванні
        $wpdb->delete( 'VotingResults', [ 'Question_ID' => $question_id, 'User_ID' => $user_id ], [ '%d', '%d' ] );

        $result = $wpdb->insert(
            'VotingResults',
            [
                'User_ID'                  => $user_id,
                'Question_ID'              => $question_id,
                'Answer_ID'                => $answer_id,
                'VotingResults_DateCreate' => current_time( 'mysql' ),
                'VotingResults_Note'       => 'сайт',
                'VotingResults_API'        => 0
            ],
            [ '%d', '%d', '%d', '%s', '%s', '%d' ]
        );

        if ( false === $result ) {
            $wpdb->query( 'ROLLBACK' );
            return new \WP_Error( 'db_error', 'Помилка збереження в базу даних.' );
        }

        $protocol = new Commissions_Protocol_Service();
        $protocol->log_action( 'I', "Проголосував у питанні ID: {$question_id} (відповідь: {$answer_id})", '✓' );

        $wpdb->query( 'COMMIT' );

        return true;
    }

    //---
}