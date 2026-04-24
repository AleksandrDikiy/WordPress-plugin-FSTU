<?php
/**
 * Бізнес-логіка модуля Directory.
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

class Directory_Service {

    private Directory_Repository $repository;

    public function __construct() {
        $this->repository = new Directory_Repository();
    }
    /**
     * Отримання екземпляра репозиторію.
     *
     * @return Directory_Repository
     */
    public function get_repository(): Directory_Repository {
        return $this->repository;
    }

    public function cast_issue_vote( int $user_id, int $question_id, int $answer_id, int $type_guidance_id ) {
        global $wpdb;

        $poll = $wpdb->get_row( $wpdb->prepare( "SELECT Question_Name, Question_DateEnd FROM Question WHERE Question_ID = %d", $question_id ) );

        if ( ! $poll ) {
            return new \WP_Error( 'not_found', 'Опитування не знайдено.' );
        }

        if ( current_time( 'Y-m-d' ) > $poll->Question_DateEnd ) {
            return new \WP_Error( 'expired', 'Термін голосування завершено.' );
        }

        $is_admin = current_user_can( 'administrator' );
        if ( ! $is_admin ) {
            if ( ! current_user_can( 'userfstu' ) ) {
                return new \WP_Error( 'no_access', 'Голосувати можуть тільки члени ФСТУ.' );
            }

            if ( ! $this->repository->is_user_in_guidance( $user_id, $type_guidance_id ) ) {
                return new \WP_Error( 'not_in_guidance', 'Тільки члени Виконкому мають право голосувати.' );
            }
        }

        $wpdb->query( 'START TRANSACTION' );
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
            return new \WP_Error( 'db_error', 'Помилка збереження.' );
        }

        if ( ! class_exists( 'FSTU\Modules\Directory\Directory_Protocol_Service' ) && file_exists( __DIR__ . '/class-directory-protocol-service.php' ) ) {
            require_once __DIR__ . '/class-directory-protocol-service.php';
        }
        $protocol = new Directory_Protocol_Service();
        $protocol->log_action( 'I', "Проголосував у питанні Виконкому ID: {$question_id} (відповідь: {$answer_id})", '✓' );

        $wpdb->query( 'COMMIT' );
        return true;
    }
}