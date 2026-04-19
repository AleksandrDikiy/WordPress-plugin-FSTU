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
     * Отримує список опитувань для конкретної комісії.
     *
     * @param int $question_type_id   Тип опитування (1 - рішення, 2 - вибори).
     * @param int $commission_type_id Тип комісії.
     * @param int $s_commission_id    Код комісії.
     * @param int $region_id          Код регіону.
     * @param int $question_state     Статус (-1 - всі, 0 - публічний, 1 - приватний).
     * @return array
     */
    public function get_polls_list( int $question_type_id, int $commission_type_id, int $s_commission_id, int $region_id, int $question_state = 0 ): array {
        global $wpdb;

        // Використовуємо готовий View бази даних ФСТУ (vQuestionCommission),
        // де вже зібрані всі дати, ліміти та назви з потрібних таблиць.
        $sql = "
			SELECT 
				QuestionCommission_ID, Question_ID, Commission_ID, 
				Question_DateCreate, Question_DateBegin, Question_DateEnd, 
				QuestionType_ID, Question_Name, Question_State, Question_URL,
				SetCommission_CountMembers,
				Commission_Name,
				(
                    SELECT COUNT(DISTINCT cr.User_ID) 
                    FROM CandidatesCommissionResult cr
                    INNER JOIN CandidatesCommission cc ON cc.CandidatesCommission_ID = cr.CandidatesCommission_ID
                    WHERE cc.Question_ID = vqc.Question_ID
                ) as votes_count
			FROM vQuestionCommission vqc
			WHERE QuestionType_ID = %d
			  AND Commission_ID = %d
			  AND CommissionType_ID = %d
		";

        $args = [ $question_type_id, $s_commission_id, $commission_type_id ];

        // Якщо $question_state == -1, показуємо і публічні, і приватні опитування
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
        // Отримуємо квоту через View та рахуємо кількість унікальних користувачів
        $sql = "
            SELECT 
                SetCommission_CountMembers as quota, 
                (
                    SELECT COUNT(DISTINCT cr.User_ID) 
                    FROM CandidatesCommissionResult cr
                    INNER JOIN CandidatesCommission cc ON cc.CandidatesCommission_ID = cr.CandidatesCommission_ID
                    WHERE cc.Question_ID = %d
                ) as total_voted
            FROM vQuestionCommission 
            WHERE Question_ID = %d LIMIT 1
        ";
        return $wpdb->get_row( $wpdb->prepare( $sql, $question_id, $question_id ), ARRAY_A ) ?? [];
    }

    /**
     * Пошук користувачів для AJAX Autocomplete (Надійна фільтрація на стороні PHP).
     */
    public function search_users( string $search_query ): array {
        global $wpdb;

        // 1. Отримуємо всіх активних членів та групуємо за User_ID, щоб уникнути дублікатів через осередки
        $sql = "SELECT User_ID, CAST(FIO AS CHAR) AS FIO FROM vUserFSTUnew WHERE UserFSTU = '1' OR UserFSTU = 1 GROUP BY User_ID";
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
    /**
     * Додає кандидата до опитування через існуючу збережену процедуру бази даних.
     *
     * @param int    $question_id ID опитування.
     * @param int    $user_id     ID користувача (кандидата).
     * @param string $direction   Напрямок.
     * @param string $development Програма розвитку.
     * @param string $url         Посилання.
     * @return bool|string True у разі успіху, або текст помилки.
     */
    public function save_candidate( int $question_id, int $user_id, string $direction, string $development, string $url ) {
        global $wpdb;

        $wpdb->get_var(
            $wpdb->prepare(
                "call InsertCandidatesCommission(%d, %d, %d, %s, %s, %s, @ResultID)",
                get_current_user_id(), // Хто додає
                $user_id,              // Кого додають (кандидат)
                $question_id,
                $direction,
                $development,
                $url
            )
        );

        $result_id = $wpdb->get_var( "SELECT @ResultID" );

        if ( $result_id == 1 ) {
            return true;
        }

        return $wpdb->last_error ?: 'Помилка бази даних при додаванні кандидата.';
    }
    /**
     * Отримує список користувачів, які проголосували за кандидата.
     *
     * @param int $candidate_id ID кандидата в комісію.
     * @return array
     */
    public function get_voters_for_candidate( int $candidate_id ): array {
        global $wpdb;

        // Отримуємо результати та з'єднуємо з vUserFSTUnew для коректного ПІБ
        $sql = "
            SELECT 
                r.User_ID, 
                CAST(COALESCE(vu.FIO, u.display_name) AS CHAR) AS FIO,
                r.CandidatesCommissionResult_Value AS VoteValue
            FROM CandidatesCommissionResult r
            LEFT JOIN {$wpdb->users} u ON u.ID = r.User_ID
            LEFT JOIN vUserFSTUnew vu ON vu.User_ID = r.User_ID
            WHERE r.CandidatesCommission_ID = %d
            GROUP BY r.User_ID
            ORDER BY FIO ASC
        ";

        return $wpdb->get_results( $wpdb->prepare( $sql, $candidate_id ), ARRAY_A ) ?? [];
    }
    //----
}