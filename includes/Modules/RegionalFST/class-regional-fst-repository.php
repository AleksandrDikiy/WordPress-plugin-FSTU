<?php
/**
 * Репозиторій модуля "Осередки федерації спортивного туризму".
 * Відповідає за всі SQL-запити до БД (читання даних).
 *
 * Version:     1.0.1
 * Date_update: 2026-04-20
 *
 * @package FSTU\Modules\RegionalFST
 */

namespace FSTU\Modules\RegionalFST;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Class_Regional_FST_Repository {

    /**
     * Отримання списку членів осередку з пагінацією та пошуком.
     * Використовує legacy-в'юху vRegionalFST для збереження сумісності та групування.
     *
     * @param int    $region_id ID регіону (області).
     * @param int    $unit_id   ID осередку (опціонально).
     * @param string $search    Пошуковий запит (по ПІБ).
     * @param int    $limit     Кількість на сторінку.
     * @param int    $offset    Зсув (offset).
     * @return array Масив з 'items' та 'total'.
     */
    public function get_list( int $region_id, int $unit_id = 0, string $search = '', int $limit = 10, int $offset = 0 ): array {
        global $wpdb;

        $where_parts = [ '1=1' ];
        $params      = [];

        if ( $region_id > 0 ) {
            $where_parts[] = 'r.Region_ID = %d';
            $params[]      = $region_id;
        }

        if ( $unit_id > 0 ) {
            $where_parts[] = 'r.Unit_ID = %d';
            $params[]      = $unit_id;
        }

        if ( ! empty( $search ) ) {
            $where_parts[] = '(r.FIO LIKE %s OR r.FIOshort LIKE %s)';
            $like_search   = '%' . $wpdb->esc_like( $search ) . '%';
            $params[]      = $like_search;
            $params[]      = $like_search;
        }

        $where_sql = implode( ' AND ', $where_parts );

        // Оскільки в старому коді один юзер міг мати кілька посад, робимо GROUP BY
        $base_sql = "
			FROM vRegionalFST r 
			LEFT JOIN S_Unit su ON r.Unit_ID = su.Unit_ID
			WHERE {$where_sql}
		";

        // Рахуємо загальну кількість унікальних користувачів в осередках
        $count_sql = "SELECT COUNT(DISTINCT r.User_ID, r.Unit_ID) " . $base_sql;
        $total     = (int) ( ! empty( $params ) ? $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) ) : $wpdb->get_var( $count_sql ) );

        // Отримуємо самі дані
        $select_sql = "
			SELECT 
				GROUP_CONCAT(r.RegionalFST_ID SEPARATOR '|') AS RegionalFST_ID, 
				r.User_ID, 
				r.Region_ID, 
				r.Unit_ID, 
				GROUP_CONCAT(r.MemberRegional_Name SEPARATOR ', ') AS MemberRegional_Name, 
				MIN(r.MemberRegional_Order) AS MemberRegional_Order, 
				r.FIO, 
				r.FIOshort, 
				r.email, 
				su.Unit_ShortName
			{$base_sql}
			GROUP BY r.User_ID, r.Unit_ID 
			ORDER BY su.Unit_ShortName ASC, MemberRegional_Order ASC, r.FIO ASC
			LIMIT %d OFFSET %d
		";

        $data_params = array_merge( $params, [ $limit, $offset ] );
        $items       = $wpdb->get_results( $wpdb->prepare( $select_sql, ...$data_params ), ARRAY_A );

        // Довантажуємо телефони (з wp_usermeta), щоб не перевантажувати основний запит
        if ( ! empty( $items ) ) {
            foreach ( $items as &$item ) {
                $user_id = (int) $item['User_ID'];
                $phones  = array_filter( [
                    get_user_meta( $user_id, 'PhoneMobile', true ),
                    get_user_meta( $user_id, 'Phone2', true ),
                    get_user_meta( $user_id, 'Phone3', true ),
                ] );
                $item['Phones'] = implode( '<br>', array_map( 'esc_html', $phones ) );
            }
        }

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * Пошук користувачів для AJAX Autocomplete (Надійна фільтрація на стороні PHP).
     *
     * @param string $search_query Текст пошуку.
     * @return array Масив з 'results' та 'query' (для Select2).
     */
    public function search_users_for_select2( string $search_query ): array {
        global $wpdb;

        if ( empty( $search_query ) ) {
            return [ 'results' => [], 'query' => 'empty' ];
        }

        // 1. Отримуємо всіх активних членів та групуємо за User_ID (використовуємо CAST як у Комісіях)
        $sql = "SELECT User_ID, CAST(FIO AS CHAR) AS FIO FROM vUserFSTUnew WHERE UserFSTU = '1' OR UserFSTU = 1 GROUP BY User_ID";
        $all_users = $wpdb->get_results( $sql, ARRAY_A );

        if ( empty( $all_users ) ) {
            return [ 'results' => [], 'query' => $sql ];
        }

        $results = [];
        $search_lower = mb_strtolower( trim( $search_query ), 'UTF-8' );

        // 2. Фільтруємо масив засобами PHP (обходимо всі проблеми з LIKE та кодуваннями)
        foreach ( $all_users as $user ) {
            if ( empty( $user['FIO'] ) ) {
                continue;
            }

            $fio_lower = mb_strtolower( $user['FIO'], 'UTF-8' );

            if ( mb_strpos( $fio_lower, $search_lower, 0, 'UTF-8' ) !== false ) {
                // Select2 очікує саме ключі 'id' та 'text'
                $results[] = [
                    'id'   => $user['User_ID'],
                    'text' => $user['FIO']
                ];
            }

            if ( count( $results ) >= 30 ) {
                break;
            }
        }

        // Сортуємо результати за алфавітом
        usort( $results, function( $a, $b ) {
            return strcmp( $a['text'], $b['text'] );
        } );

        return $results;
    }

    /**
     * Отримання одного запису для модального вікна редагування.
     *
     * @param int $id ID запису в RegionalFST.
     * @return array|null
     */
    public function get_item( int $id ): ?array {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM vRegionalFST WHERE RegionalFST_ID = %d", $id ), ARRAY_A );
    }
}