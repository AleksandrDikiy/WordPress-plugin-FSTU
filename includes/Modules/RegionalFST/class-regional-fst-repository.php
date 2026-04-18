<?php
/**
 * Репозиторій модуля "Осередки федерації спортивного туризму".
 * Відповідає за всі SQL-запити до БД (читання даних).
 *
 * Version:     1.0.0
 * Date_update: 2026-04-18
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
     * Швидкий пошук користувачів (Select2) через прямий SQL запит.
     * Оминає повільний vUserFSTU для миттєвої видачі результатів.
     *
     * @param string $search Текст пошуку.
     * @param int    $limit  Максимальна кількість результатів.
     * @return array Масив [ id => ПІБ ]
     */
    public function search_users_for_select2( string $search, int $limit = 20 ): array {
        global $wpdb;

        if ( empty( $search ) ) {
            return [];
        }

        $like_search = '%' . $wpdb->esc_like( $search ) . '%';
        $cap_key     = $wpdb->prefix . 'capabilities';

        // Використовуємо HAVING для фільтрації по згенерованому ПІБ
        // Шукаємо тільки тих, хто має роль/capability 'userfstu'
        $sql = "
			SELECT u.ID as id,
			       CONCAT_WS(' ',
			           (SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = u.ID AND meta_key = 'last_name' LIMIT 1),
			           (SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = u.ID AND meta_key = 'first_name' LIMIT 1),
			           (SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = u.ID AND meta_key = 'Patronymic' LIMIT 1)
			       ) as text
			FROM {$wpdb->users} u
			INNER JOIN {$wpdb->usermeta} cap ON cap.user_id = u.ID AND cap.meta_key = %s AND cap.meta_value LIKE '%userfstu%'
			HAVING text LIKE %s OR u.user_email LIKE %s
			ORDER BY text ASC
			LIMIT %d
		";

        return $wpdb->get_results( $wpdb->prepare( $sql, $cap_key, $like_search, $like_search, $limit ), ARRAY_A ) ?? [];
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