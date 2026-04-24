<?php

namespace FSTU\Modules\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Бізнес-сервіс модуля "Заявки в ФСТУ".
 * Оркеструє транзакції, зміни ролей та критичні бізнес-операції.
 *
 * Version:     1.3.1
 * Date_update: 2026-04-24
 */
class Applications_Service {

    private const ROLE_ACCEPTED = 'userfstu';
    private const ROLE_BLOCKED = 'Blocked';
    private const ROLE_BBP_BLOCKED = 'bbp_blocked';

    private Applications_Repository $repository;
    private Applications_Protocol_Service $protocol_service;
    private Applications_Mailer $mailer;

    public function __construct(
        Applications_Repository $repository,
        Applications_Protocol_Service $protocol_service,
        Applications_Mailer $mailer
    ) {
        $this->repository       = $repository;
        $this->protocol_service = $protocol_service;
        $this->mailer           = $mailer;
    }

    /**
     * Приймає кандидата в члени ФСТУ.
     *
     * @return array<string, mixed>
     */
    public function accept_candidate( int $user_id ): array {
        global $wpdb;

        $user = get_userdata( $user_id );
        if ( ! $user instanceof \WP_User ) {
            throw new \RuntimeException( 'candidate_not_found' );
        }

        $roles = is_array( $user->roles ) ? $user->roles : [];
        $has_member_card = $this->repository->candidate_has_member_card( $user_id );

        if ( in_array( self::ROLE_ACCEPTED, $roles, true ) && $has_member_card ) {
            throw new \RuntimeException( 'candidate_already_accepted' );
        }

        if ( in_array( self::ROLE_ACCEPTED, $roles, true ) && ! $has_member_card ) {
            throw new \RuntimeException( 'candidate_role_already_accepted' );
        }

        if ( ! in_array( self::ROLE_ACCEPTED, $roles, true ) && $has_member_card ) {
            throw new \RuntimeException( 'candidate_card_already_exists' );
        }

        $region_id = $this->repository->get_candidate_region_id( $user_id );
        if ( $region_id <= 0 ) {
            throw new \RuntimeException( 'candidate_region_not_found' );
        }

        $ticket_number = $this->repository->get_next_member_card_number( $region_id );
        if ( $ticket_number <= 0 ) {
            throw new \RuntimeException( 'ticket_number_not_generated' );
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query( 'START TRANSACTION' );

        try {
            $user->set_role( self::ROLE_ACCEPTED );
            $user->remove_role( self::ROLE_BLOCKED );
            $user->remove_role( self::ROLE_BBP_BLOCKED );

            if ( ! $this->repository->create_member_card( $user_id, $region_id, $ticket_number ) ) {
                throw new \RuntimeException( 'member_card_insert_failed' );
            }

            $this->protocol_service->log_action(
                'U',
                sprintf( 'Користувача ID:%d прийнято в члени ФСТУ. Картка №%d', $user_id, $ticket_number ),
                Applications_Protocol_Service::STATUS_SUCCESS,
                true
            );

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->query( 'COMMIT' );
        } catch ( \Throwable $throwable ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->query( 'ROLLBACK' );
            $this->protocol_service->try_log_action(
                'U',
                sprintf( 'Помилка прийняття користувача ID:%d [%s]', $user_id, $this->get_accept_error_marker( $throwable ) ),
                'error'
            );
            throw $throwable;
        }

        $from_email = $this->repository->get_setting_value( 'EmailFSTU' );
        $email_sent = $this->mailer->send_acceptance_email( $user, $ticket_number, $from_email );

        return [
            'ticket_number' => $ticket_number,
            'email_sent'    => $email_sent,
        ];
    }

    /**
     * Повертає безпечний службовий маркер причини помилки прийняття.
     */
    private function get_accept_error_marker( \Throwable $throwable ): string {
        $marker = trim( $throwable->getMessage() );

        return '' !== $marker ? $marker : 'accept_failed';
    }

    /**
     * Повертає roles користувача у нормалізованому вигляді.
     *
     * @return array<int, string>
     */
    private function get_user_roles( \WP_User $user ): array {
        return is_array( $user->roles ) ? $user->roles : [];
    }

    /**
     * Виконує спільні pre-checks стану кандидата для бізнес-операцій модуля.
     */
    private function assert_candidate_state_before_mutation( int $user_id ): \WP_User {
        $user = get_userdata( $user_id );
        if ( ! $user instanceof \WP_User ) {
            throw new \RuntimeException( 'candidate_not_found' );
        }

        $roles           = $this->get_user_roles( $user );
        $has_member_card = $this->repository->candidate_has_member_card( $user_id );

        if ( in_array( self::ROLE_ACCEPTED, $roles, true ) && $has_member_card ) {
            throw new \RuntimeException( 'candidate_already_accepted' );
        }

        if ( in_array( self::ROLE_ACCEPTED, $roles, true ) && ! $has_member_card ) {
            throw new \RuntimeException( 'candidate_role_already_accepted' );
        }

        if ( ! in_array( self::ROLE_ACCEPTED, $roles, true ) && $has_member_card ) {
            throw new \RuntimeException( 'candidate_card_already_exists' );
        }

        return $user;
    }

    /**
     * Відхиляє заявку без фізичного видалення.
     */
    public function reject_candidate( int $user_id ): array {
        global $wpdb;

        $user  = $this->assert_candidate_state_before_mutation( $user_id );
        $roles = $this->get_user_roles( $user );

        if ( in_array( self::ROLE_BLOCKED, $roles, true ) && ! in_array( self::ROLE_BBP_BLOCKED, $roles, true ) ) {
            return [ 'status' => 'already_rejected' ];
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query( 'START TRANSACTION' );

        try {
            $user->set_role( self::ROLE_BLOCKED );

            $updated_user = get_userdata( $user_id );
            $updated_roles = $updated_user instanceof \WP_User ? $this->get_user_roles( $updated_user ) : [];

            if ( ! in_array( self::ROLE_BLOCKED, $updated_roles, true ) ) {
                throw new \RuntimeException( 'reject_role_update_failed' );
            }

            $this->protocol_service->log_action(
                'D',
                sprintf( 'Заявку користувача ID:%d відхилено. Роль змінено на %s', $user_id, self::ROLE_BLOCKED ),
                Applications_Protocol_Service::STATUS_SUCCESS,
                true
            );

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->query( 'COMMIT' );
        } catch ( \Throwable $throwable ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->query( 'ROLLBACK' );
            $this->protocol_service->try_log_action(
                'D',
                sprintf( 'Помилка відхилення заявки користувача ID:%d [%s]', $user_id, $this->get_reject_error_marker( $throwable ) ),
                'error'
            );
            throw $throwable;
        }

        return [ 'status' => 'rejected' ];
    }

    /**
     * Повертає безпечний службовий маркер причини помилки відхилення.
     */
    private function get_reject_error_marker( \Throwable $throwable ): string {
        $marker = trim( $throwable->getMessage() );

        return '' !== $marker ? $marker : 'reject_failed';
    }

    /**
     * Змінює ОФСТ кандидата.
     *
     * @return array<string, mixed>
     */
    public function change_candidate_ofst( int $user_id, int $unit_id ): array {
        global $wpdb;

        $this->assert_candidate_state_before_mutation( $user_id );

        $unit_row = $this->repository->get_unit_by_id( $unit_id );
        if ( ! is_array( $unit_row ) ) {
            throw new \RuntimeException( 'unit_not_found' );
        }

        $region_id = (int) ( $unit_row['Region_ID'] ?? 0 );
        if ( $region_id <= 0 ) {
            throw new \RuntimeException( 'unit_region_not_found' );
        }

        $current_row = $this->repository->get_current_ofst_record( $user_id );

        if ( is_array( $current_row ) ) {
            $current_unit_id   = (int) ( $current_row['Unit_ID'] ?? 0 );
            $current_region_id = (int) ( $current_row['Region_ID'] ?? 0 );
            $record_id         = (int) ( $current_row['UserRegistationOFST_ID'] ?? 0 );
            $current_date      = (string) ( $current_row['UserRegistationOFST_DateCreate'] ?? '' );

            if ( $record_id <= 0 ) {
                throw new \RuntimeException( 'current_ofst_record_invalid' );
            }
        } else {
            // Якщо кандидат взагалі не мав ОФСТ (аномалія даних), готуємось створити запис з нуля
            $current_unit_id   = 0;
            $current_region_id = 0;
            $record_id         = 0;
            $current_date      = current_time( 'mysql' );
        }

        if ( $current_unit_id === $unit_id && $current_region_id === $region_id ) {
            return [ 'status' => 'no_changes' ];
        }

        $next_date = $this->build_next_ofst_datetime( $current_date );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query( 'START TRANSACTION' );

        try {
            if ( $record_id > 0 ) {
                // Стандартний флоу: оновлення існуючого + запис в історію
                $update_result = $this->repository->update_current_ofst_record_conditionally(
                    $record_id,
                    $user_id,
                    $current_date,
                    $current_region_id,
                    $current_unit_id,
                    $region_id,
                    $unit_id,
                    $next_date
                );

                if ( is_wp_error( $update_result ) ) {
                    throw new \RuntimeException( (string) $update_result->get_error_code() );
                }

                if ( ! $update_result ) {
                    throw new \RuntimeException( 'ofst_state_conflict' );
                }

                $history_result = $this->repository->insert_ofst_history_snapshot( $current_row );
                if ( is_wp_error( $history_result ) ) {
                    throw new \RuntimeException( (string) $history_result->get_error_code() );
                }
            } else {
                // Виправлення: Створення нового запису ОФСТ, якщо він був відсутній
                $insert_result = $this->repository->insert_new_ofst_record( $user_id, $region_id, $unit_id, $next_date );
                if ( is_wp_error( $insert_result ) ) {
                    throw new \RuntimeException( (string) $insert_result->get_error_code() );
                }
            }

            $this->protocol_service->log_action(
                'U',
                sprintf(
                    'Змінено ОФСТ для користувача ID:%d на осередок %s (Unit:%d)',
                    $user_id,
                    (string) ( $unit_row['Unit_ShortName'] ?? '—' ),
                    $unit_id
                ),
                Applications_Protocol_Service::STATUS_SUCCESS,
                true
            );

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->query( 'COMMIT' );
        } catch ( \Throwable $throwable ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->query( 'ROLLBACK' );
            $this->protocol_service->try_log_action(
                'U',
                sprintf( 'Помилка зміни ОФСТ для користувача ID:%d [%s]', $user_id, $this->get_change_ofst_error_marker( $throwable ) ),
                'error'
            );
            throw $throwable;
        }

        return [
            'status'      => 'updated',
            'region_id'   => $region_id,
            'unit_id'     => $unit_id,
            'unit_name'   => (string) ( $unit_row['Unit_ShortName'] ?? '' ),
        ];
    }

    /**
     * Будує нову дату поточного запису ОФСТ так, щоб вона гарантовано відрізнялася від старої.
     */
    private function build_next_ofst_datetime( string $current_date ): string {
        $candidate_ts = time();
        $current_ts   = strtotime( $current_date );

        if ( false !== $current_ts && $candidate_ts <= $current_ts ) {
            $candidate_ts = $current_ts + 1;
        }

        return wp_date( 'Y-m-d H:i:s', $candidate_ts, wp_timezone() );
    }

    /**
     * Повертає безпечний службовий маркер причини помилки зміни ОФСТ.
     */
    private function get_change_ofst_error_marker( \Throwable $throwable ): string {
        $marker = trim( $throwable->getMessage() );

        return '' !== $marker ? $marker : 'change_ofst_failed';
    }
}

