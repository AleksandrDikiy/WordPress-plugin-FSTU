<?php
/**
 * Міграція БД 1.3.0: Створення таблиць для модуля STV Виборів.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-22
 *
 * @package FSTU\Migrations
 */

namespace FSTU\Migrations;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Migration_1_3_0 {

    /**
     * Виконує міграцію "Вгору".
     */
    public static function up(): void {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE Elections (
			Election_ID int(11) NOT NULL AUTO_INCREMENT COMMENT 'Код виборів',
			Election_Name varchar(250) NOT NULL COMMENT 'Назва виборів',
			TourismType_ID int(11) DEFAULT NULL COMMENT 'Код виду туризму',
			Status varchar(20) NOT NULL DEFAULT 'draft' COMMENT 'Статус (draft/nomination/voting/calculation/completed)',
			Date_Nomination_Start datetime DEFAULT NULL COMMENT 'Дата початку висунення',
			Date_Nomination_End datetime DEFAULT NULL COMMENT 'Дата завершення висунення',
			Date_Voting_Start datetime DEFAULT NULL COMMENT 'Дата початку голосування',
			Date_Voting_End datetime DEFAULT NULL COMMENT 'Дата завершення голосування',
			Settings_Candidates_Count smallint(2) NOT NULL DEFAULT 7 COMMENT 'Кількість мандатів (3-15)',
			Settings_Nomination_Days smallint(2) NOT NULL DEFAULT 7 COMMENT 'Дні висунення',
			Settings_Extension_Days smallint(2) NOT NULL DEFAULT 5 COMMENT 'Дні подовження',
			Settings_Voting_Days smallint(2) NOT NULL DEFAULT 7 COMMENT 'Дні голосування',
			UserCreate bigint(20) NOT NULL COMMENT 'Код користувача який створив',
			DateCreate datetime NOT NULL COMMENT 'Дата створення',
			PRIMARY KEY  (Election_ID),
			KEY Status (Status)
		) $charset_collate;

		CREATE TABLE Election_Candidates (
			Candidate_ID int(11) NOT NULL AUTO_INCREMENT COMMENT 'Код кандидата',
			Election_ID int(11) NOT NULL COMMENT 'Код виборів',
			User_ID bigint(20) NOT NULL COMMENT 'Код користувача-кандидата',
			Nominator_ID bigint(20) DEFAULT NULL COMMENT 'Код користувача який висунув',
			Motivation_Text text DEFAULT NULL COMMENT 'Мотиваційний текст кандидата',
			Motivation_URL varchar(500) DEFAULT NULL COMMENT 'Посилання на програму або документ',
			Status varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'Статус згоди (pending/confirmed)',
			PRIMARY KEY  (Candidate_ID),
			KEY Election_ID (Election_ID),
			KEY User_ID (User_ID)
		) $charset_collate;

		CREATE TABLE Election_Voters (
			Voter_ID int(11) NOT NULL AUTO_INCREMENT COMMENT 'Код запису явки',
			Election_ID int(11) NOT NULL COMMENT 'Код виборів',
			User_ID bigint(20) NOT NULL COMMENT 'Код виборця',
			Has_Voted tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Ознака чи проголосував виборець',
			Date_Voted datetime DEFAULT NULL COMMENT 'Дата і час голосування',
			PRIMARY KEY  (Voter_ID),
			UNIQUE KEY election_user (Election_ID,User_ID),
			KEY Has_Voted (Has_Voted)
		) $charset_collate;

		CREATE TABLE Election_Ballots (
			Ballot_ID int(11) NOT NULL AUTO_INCREMENT COMMENT 'Код бюлетеня',
			Election_ID int(11) NOT NULL COMMENT 'Код виборів',
			Ballot_Hash varchar(64) NOT NULL COMMENT 'Унікальний SHA-256 хеш бюлетеня',
			Preferences_JSON text NOT NULL COMMENT 'Зашифрований масив пріоритетів (ID кандидатів)',
			DateCreate datetime NOT NULL COMMENT 'Дата створення бюлетеня',
			PRIMARY KEY  (Ballot_ID),
			UNIQUE KEY Ballot_Hash (Ballot_Hash),
			KEY Election_ID (Election_ID)
		) $charset_collate;";

        dbDelta( $sql );
    }
}