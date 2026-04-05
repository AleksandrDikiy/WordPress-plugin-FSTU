<?php
/**
 * AJAX-обробники модуля "Реєстр платіжних документів".
 *
 * Version:     1.0.0
 * Date_update: 2026-04-05
 *
 * @package FSTU\PaymentDocs
 */

namespace FSTU\PaymentDocs;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Payment_Docs_Ajax {

    public function init(): void {
        add_action( 'wp_ajax_fstu_get_payment_docs', [ $this, 'handle_get_payment_docs' ] );
        add_action( 'wp_ajax_fstu_save_payment_doc', [ $this, 'handle_save_payment_doc' ] );
        add_action( 'wp_ajax_fstu_delete_payment_doc', [ $this, 'handle_delete_payment_doc' ] );
        add_action( 'wp_ajax_fstu_get_payment_doc', [ $this, 'handle_get_payment_doc' ] );
    }

    public function handle_get_payment_docs(): void {
        check_ajax_referer( 'fstu_payment_docs_nonce', 'nonce' );

        $user_roles = (array) wp_get_current_user()->roles;
        if ( empty( array_intersect( [ 'administrator', 'userregistrar', 'groupauditor' ], $user_roles ) ) ) {
            wp_send_json_error( [ 'message' => 'Недостатньо прав.' ] );
        }

        global $wpdb;

        // Фільтри
        $unit_id = absint( $_POST['unit_id'] ?? 0 );
        $resp_id = absint( $_POST['resp_id'] ?? 0 );
        $year    = absint( $_POST['year'] ?? 0 );
        $page    = max( 1, absint( $_POST['page'] ?? 1 ) );

        // Отримуємо кількість на сторінку з форми, обмежуємо від 1 до 100
        $per_page = absint( $_POST['per_page'] ?? 25 );
        $per_page = min( max( $per_page, 1 ), 100 );

        $offset  = ( $page - 1 ) * $per_page;

        $where_parts = [ '1=1' ];
        $params = [];

        if ( $unit_id > 0 ) {
            $where_parts[] = "dp.Doc_DuesPayment_UnitID = %d";
            $params[] = $unit_id;
        }
        if ( $resp_id > 0 ) {
            $where_parts[] = "dp.Doc_DuesPayment_RespID = %d";
            $params[] = $resp_id;
        }
        if ( $year > 0 ) {
            $where_parts[] = "YEAR(dp.Doc_DuesPayment_Date) = %d";
            $params[] = $year;
        }

        // Обмеження для Реєстраторів (бачать тільки свої осередки)
        if ( ! in_array( 'administrator', $user_roles, true ) && ! in_array( 'groupauditor', $user_roles, true ) ) {
            $where_parts[] = "dp.Doc_DuesPayment_UnitID IN (SELECT Unit_ID FROM UserRegion WHERE User_ID = %d)";
            $params[] = get_current_user_id();
        }

        $where_sql = implode( ' AND ', $where_parts );

        // Підрахунок загальної кількості та загальної суми
        $count_sql = "SELECT COUNT(dp.Doc_DuesPayment_ID) FROM Doc_DuesPayment dp WHERE {$where_sql}";
        $sum_sql   = "SELECT SUM(dp.Doc_DuesPayment_Sum) FROM Doc_DuesPayment dp WHERE {$where_sql}";

        $total     = (int) ( ! empty( $params ) ? $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) ) : $wpdb->get_var( $count_sql ) );
        $total_sum = (float) ( ! empty( $params ) ? $wpdb->get_var( $wpdb->prepare( $sum_sql, ...$params ) ) : $wpdb->get_var( $sum_sql ) );

        // Отримання даних
        $select_sql = "
			SELECT dp.Doc_DuesPayment_ID, dp.Doc_DuesPayment_Date, dp.Doc_DuesPayment_Comment, 
			       dp.Doc_DuesPayment_Sum, su.Unit_ShortName, u.FIO 
			FROM Doc_DuesPayment dp
			LEFT JOIN S_Unit su ON dp.Doc_DuesPayment_UnitID = su.Unit_ID
			LEFT JOIN vUserFSTUnew u ON dp.Doc_DuesPayment_RespID = u.User_ID
			WHERE {$where_sql}
			ORDER BY dp.Doc_DuesPayment_ID DESC
			LIMIT %d OFFSET %d
		";
        $data_params = array_merge( $params, [ $per_page, $offset ] );
        $rows = $wpdb->get_results( $wpdb->prepare( $select_sql, ...$data_params ), ARRAY_A );

        // Формування HTML
        $html = '';
        if ( empty( $rows ) ) {
            $html = '<tr><td colspan="7" class="fstu-td" style="text-align:center; padding:20px;">Записів не знайдено.</td></tr>';
        } else {
            foreach ( $rows as $row ) {
                $date = wp_date( 'd.m.Y H:i', strtotime( $row['Doc_DuesPayment_Date'] ) );
                $sum  = number_format( (float) $row['Doc_DuesPayment_Sum'], 2, '.', '' );

                $html .= '<tr class="fstu-row">';
                $html .= '<td class="fstu-td" style="text-align:center;">' . esc_html( $row['Doc_DuesPayment_ID'] ) . '</td>';
                $html .= '<td class="fstu-td">' . esc_html( $date ) . '</td>';
                $html .= '<td class="fstu-td"><a href="#" class="fstu-action-view" data-id="' . esc_attr( $row['Doc_DuesPayment_ID'] ) . '" style="color: var(--fstu-primary, #c0392b); font-weight: 500; text-decoration: none;" onmouseover="this.style.textDecoration=\'underline\'" onmouseout="this.style.textDecoration=\'none\'">' . esc_html( $row['Unit_ShortName'] ) . '</a></td>';
                $html .= '<td class="fstu-td" style="text-align:right; font-weight:bold; color:var(--fstu-primary);">' . esc_html( $sum ) . '</td>';
                $html .= '<td class="fstu-td">' . esc_html( $row['FIO'] ) . '</td>';
                $html .= '<td class="fstu-td" style="font-size:11px;">' . esc_html( $row['Doc_DuesPayment_Comment'] ) . '</td>';

                // Повноцінне меню опцій
                $btn_html = '
				<div class="fstu-opts">
					<button type="button" class="fstu-opts-btn" title="Опції">▼</button>
					<ul class="fstu-opts-list">
						<li><a href="#" class="fstu-action-view" data-id="' . esc_attr( $row['Doc_DuesPayment_ID'] ) . '">🔍 Перегляд</a></li>
						<li><a href="#" class="fstu-action-edit" data-id="' . esc_attr( $row['Doc_DuesPayment_ID'] ) . '">📝 Редагувати</a></li>
						<li><hr class="fstu-opts-divider"></li>
						<li><a href="#" class="fstu-action-delete" data-id="' . esc_attr( $row['Doc_DuesPayment_ID'] ) . '" style="color:#c0392b !important;">❌ Видалити</a></li>
					</ul>
				</div>';

                $html .= '<td class="fstu-td" style="text-align:center; padding: 2px; position:relative;">' . $btn_html . '</td>';
                $html .= '</tr>';
            }
        }

        wp_send_json_success( [
            'html'        => $html,
            'total'       => $total,
            'total_sum'   => $total_sum,
            'page'        => $page,
            'total_pages' => ceil( $total / $per_page )
        ] );
    }
    /**
     * Зберігає новий платіжний документ та його табличну частину.
     */
    public function handle_save_payment_doc(): void {
        check_ajax_referer( 'fstu_payment_docs_nonce', 'nonce' );

        $user_roles = (array) wp_get_current_user()->roles;
        if ( empty( array_intersect( [ 'administrator', 'userregistrar' ], $user_roles ) ) ) {
            wp_send_json_error( [ 'message' => 'Недостатньо прав для збереження.' ] );
        }

        global $wpdb;
        $current_user_id = get_current_user_id();

        // 1. Зчитуємо та санітизуємо шапку документа
        $doc_id      = absint( $_POST['doc_id'] ?? 0 ); // 0 = новий документ
        $doc_date    = sanitize_text_field( $_POST['doc_date'] ?? '' );
        $unit_id     = absint( $_POST['unit_id'] ?? 0 );
        $resp_id     = absint( $_POST['resp_id'] ?? 0 );
        $doc_url     = esc_url_raw( wp_unslash( $_POST['doc_url'] ?? '' ) );
        $doc_comment = sanitize_text_field( wp_unslash( $_POST['doc_comment'] ?? '' ) );
        $doc_sum     = floatval( $_POST['doc_sum'] ?? 0 );

        if ( ! $doc_date || ! $unit_id || ! $resp_id || empty( $doc_url ) ) {
            wp_send_json_error( [ 'message' => 'Заповніть усі обов\'язкові поля шапки (з зірочкою).' ] );
        }

        // 2. Зчитуємо масиви табличної частини (ТЧ)
        $tp_users = wp_unslash( $_POST['tp_user_id'] ?? [] );
        $tp_types = wp_unslash( $_POST['tp_dues_type'] ?? [] );
        $tp_sums  = wp_unslash( $_POST['tp_sum'] ?? [] );
        $tp_years = wp_unslash( $_POST['tp_year'] ?? [] );

        if ( empty( $tp_users ) || ! is_array( $tp_users ) ) {
            wp_send_json_error( [ 'message' => 'Таблична частина порожня.' ] );
        }

        // Відкриваємо транзакцію для безпечного множинного INSERT
        $wpdb->query( 'START TRANSACTION' );

        try {
            $date_formatted = gmdate( 'Y-m-d H:i:s', strtotime( $doc_date ) );

// 3. Збереження або Оновлення
            if ( $doc_id > 0 ) {
                // ОНОВЛЕННЯ: Спочатку очищаємо стару табличну частину
                $tp_ids = $wpdb->get_col( $wpdb->prepare( "SELECT TP_DuesPayment_ID FROM TP_DuesPayment WHERE TP_DuesPayment_DPID = %d", $doc_id ) );
                if ( ! empty( $tp_ids ) ) {
                    $in_clause = implode( ',', array_map( 'absint', $tp_ids ) );
                    $wpdb->query( "DELETE FROM Dues WHERE TP_DuesPayment_ID IN ($in_clause)" );
                    $wpdb->query( $wpdb->prepare( "DELETE FROM TP_DuesPayment WHERE TP_DuesPayment_DPID = %d", $doc_id ) );
                }

                // Оновлюємо шапку
                $wpdb->update( 'Doc_DuesPayment', [
                    'Doc_DuesPayment_UnitID'  => $unit_id,
                    'Doc_DuesPayment_URL'     => $doc_url,
                    'Doc_DuesPayment_RespID'  => $resp_id,
                    'Doc_DuesPayment_Date'    => $date_formatted,
                    'Doc_DuesPayment_Comment' => $doc_comment,
                    'Doc_DuesPayment_Sum'     => $doc_sum
                ], [ 'Doc_DuesPayment_ID' => $doc_id ], [ '%d', '%s', '%d', '%s', '%s', '%f' ], [ '%d' ] );

                $new_doc_id = $doc_id;
                $log_text = "Оновлено груповий платіжний док. №{$new_doc_id}";
            } else {
                // СТВОРЕННЯ НОВОГО:
                $inserted_doc = $wpdb->insert( 'Doc_DuesPayment', [
                    'Doc_DuesPayment_UnitID'  => $unit_id,
                    'Doc_DuesPayment_URL'     => $doc_url,
                    'Doc_DuesPayment_RespID'  => $resp_id,
                    'Doc_DuesPayment_Date'    => $date_formatted,
                    'Doc_DuesPayment_Comment' => $doc_comment,
                    'Doc_DuesPayment_Sum'     => $doc_sum
                ], [ '%d', '%s', '%d', '%s', '%s', '%f' ] );

                if ( ! $inserted_doc ) throw new \Exception( 'Помилка збереження шапки документа.' );
                $new_doc_id = $wpdb->insert_id;
                $log_text = "Створено груповий платіжний док. №{$new_doc_id} на суму {$doc_sum}";
            }

            // 4. Запис Нових Рядків ТЧ та Внесків (Dues)
            $row_num = 1;
            $email_rows = [];

            foreach ( $tp_users as $index => $u_id ) {
                $tp_user = absint( $u_id );
                $tp_type = absint( $tp_types[ $index ] ?? 1 );
                $tp_sum  = floatval( $tp_sums[ $index ] ?? 0 );
                $tp_year = absint( $tp_years[ $index ] ?? gmdate('Y') );

                if ( ! $tp_user ) continue;

                // Додаємо рядок ТЧ
                $wpdb->insert( 'TP_DuesPayment', [ 'TP_DuesPayment_UserID' => $tp_user, 'TP_DuesPayment_Sum' => $tp_sum, 'TP_DuesPayment_DPID' => $new_doc_id, 'TP_DuesPayment_Row' => $row_num ], [ '%d', '%f', '%d', '%d' ] );
                $tp_id = $wpdb->insert_id;

                // Додаємо фізичний Внесок (Dues)
                $wpdb->insert( 'Dues', [ 'Dues_DateCreate' => $date_formatted, 'Dues_URL' => $doc_url, 'UserCreate' => $current_user_id, 'User_ID' => $tp_user, 'Dues_Summa' => $tp_sum, 'DuesType_ID' => $tp_type, 'Year_ID' => $tp_year, 'TP_DuesPayment_ID' => $tp_id ], [ '%s', '%s', '%d', '%d', '%f', '%d', '%d', '%d' ] );

                // Дані для email
                $fio = $wpdb->get_var( $wpdb->prepare( "SELECT FIOshort FROM vUser WHERE User_ID = %d", $tp_user ) );
                $email_rows[] = "{$row_num}.\t{$fio}\thttps://www.fstu.com.ua/personal/?ViewID={$tp_user}\t{$tp_sum}\t{$tp_year}";

                $row_num++;
            }

            // 5. Логування в Протокол
            $wpdb->insert( 'Logs', [
                'Logs_DateCreate' => current_time( 'mysql' ),
                'User_ID'         => $current_user_id,
                'Logs_Type'       => ( $doc_id > 0 ) ? 'U' : 'I', // U - оновлення, I - новий
                'Logs_Name'       => 'DuesPayment',
                'Logs_Text'       => $log_text,
                'Logs_Error'      => 'успішно'
            ], [ '%s', '%d', '%s', '%s', '%s', '%s' ] );

            // 5. Логування в Протокол
            $wpdb->insert( 'Logs', [
                'Logs_DateCreate' => current_time( 'mysql' ),
                'User_ID'         => $current_user_id,
                'Logs_Type'       => 'I',
                'Logs_Name'       => 'DuesPayment',
                'Logs_Text'       => "Створено груповий платіжний док. №{$new_doc_id} на суму {$doc_sum}",
                'Logs_Error'      => 'успішно'
            ], [ '%s', '%d', '%s', '%s', '%s', '%s' ] );

            // Якщо ми дійшли сюди без помилок — зберігаємо зміни в базу назавжди
            $wpdb->query( 'COMMIT' );

            // 6. Відправка повідомлень на email
            $this->send_notification_emails( $new_doc_id, $doc_url, $current_user_id, $unit_id, $email_rows );

            wp_send_json_success( [ 'message' => "Документ №{$new_doc_id} успішно збережено!" ] );

        } catch ( \Exception $e ) {
            // Якщо сталася помилка — відкочуємо ВСІ зміни (видаляємо уламки документа)
            $wpdb->query( 'ROLLBACK' );
            wp_send_json_error( [ 'message' => $e->getMessage() ] );
        }
    }

    /**
     * Приватний метод для збору адрес і розсилки листів (як у старому скрипті).
     */
    private function send_notification_emails( $doc_id, $url, $creator_id, $unit_id, $email_rows ): void {
        global $wpdb;

        $subject  = "Додано квитанцію про сплату членських внесків на сайті ФСТУ";
        $message  = "Посилання на квитанцію : {$url}\n";
        $message .= "Посилання на документ : " . site_url( "/personal/rejestr-platizhok/?view_id={$doc_id}" ) . "\n";
        $message .= "Посилання на сторінку користувача, який додав оплату : " . site_url( "/personal/?ViewID={$creator_id}" ) . "\n";
        $message .= "------------------------ \n";
        $message .= "№ з/п\tПІБ\tПосилання\tСума\tРік\n";
        $message .= implode( "\n", $email_rows ) . "\n";
        $message .= "------------------------ \n";

        // Формуємо список отримувачів
        $to_emails = [ 'fstu.com.ua@gmail.com' ];

        // Пошта реєстраторів області
        $registrators = $wpdb->get_results( $wpdb->prepare( "SELECT User_ID FROM vUserRegion WHERE Unit_ID = %d", $unit_id ) );
        foreach ( $registrators as $reg ) {
            $user_info = get_userdata( $reg->User_ID );
            if ( $user_info && ! empty( $user_info->user_email ) ) {
                $to_emails[] = $user_info->user_email;
            }
        }

        // Пошта Президентів
        $region_id  = $wpdb->get_var( $wpdb->prepare( "SELECT Region_ID FROM S_Unit WHERE Unit_ID = %d", $unit_id ) );
        if ( $region_id ) {
            $presidents = $wpdb->get_col( $wpdb->prepare( "SELECT email FROM vRegionalFST WHERE Region_ID = %d AND MemberRegional_ID='1'", $region_id ) );
            $to_emails  = array_merge( $to_emails, $presidents );
        }

        // Прибираємо дублікати адрес
        $to_emails = array_unique( array_filter( $to_emails ) );

        // Відправляємо (можна використати bcc для приховання адрес одна від одної)
        $headers = [ 'Content-Type: text/plain; charset=UTF-8' ];
        foreach ( $to_emails as $email ) {
            wp_mail( $email, $subject, $message, $headers );
        }
    }
    /**
     * Видаляє платіжний документ та пов'язані записи (каскадне видалення).
     */
    public function handle_delete_payment_doc(): void {
        check_ajax_referer( 'fstu_payment_docs_nonce', 'nonce' );

        // Тільки адміністратори мають право видаляти фінансові документи
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Тільки Адміністратори можуть видаляти платіжні документи.' ] );
        }

        $doc_id = absint( $_POST['doc_id'] ?? 0 );
        if ( ! $doc_id ) wp_send_json_error( [ 'message' => 'Невірний ID документа.' ] );

        global $wpdb;

        // Відкриваємо транзакцію
        $wpdb->query( 'START TRANSACTION' );

        try {
            // 1. Знаходимо всі ID рядків ТЧ для цього документа
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $tp_ids = $wpdb->get_col( $wpdb->prepare( "SELECT TP_DuesPayment_ID FROM TP_DuesPayment WHERE TP_DuesPayment_DPID = %d", $doc_id ) );

            if ( ! empty( $tp_ids ) ) {
                $in_clause = implode( ',', array_map( 'absint', $tp_ids ) );

                // 2. Видаляємо фізичні внески (Dues)
                $wpdb->query( "DELETE FROM Dues WHERE TP_DuesPayment_ID IN ($in_clause)" );

                // 3. Видаляємо рядки табличної частини (TP_DuesPayment)
                $wpdb->query( $wpdb->prepare( "DELETE FROM TP_DuesPayment WHERE TP_DuesPayment_DPID = %d", $doc_id ) );
            }

            // 4. Видаляємо шапку документа
            $deleted_doc = $wpdb->delete( 'Doc_DuesPayment', [ 'Doc_DuesPayment_ID' => $doc_id ], [ '%d' ] );

            if ( ! $deleted_doc ) throw new \Exception( 'Не вдалося видалити шапку документа. Можливо, він вже видалений.' );

            // 5. Записуємо в Протокол
            $wpdb->insert( 'Logs', [
                'Logs_DateCreate' => current_time( 'mysql' ),
                'User_ID'         => get_current_user_id(),
                'Logs_Type'       => 'D',
                'Logs_Name'       => 'DuesPayment',
                'Logs_Text'       => "Видалено платіжний документ №{$doc_id} та його вміст",
                'Logs_Error'      => 'успішно'
            ], [ '%s', '%d', '%s', '%s', '%s', '%s' ] );

            // Фіксуємо зміни
            $wpdb->query( 'COMMIT' );

            wp_send_json_success( [ 'message' => "Документ №{$doc_id} успішно видалено!" ] );

        } catch ( \Exception $e ) {
            // У разі помилки відкочуємо базу
            $wpdb->query( 'ROLLBACK' );
            wp_send_json_error( [ 'message' => $e->getMessage() ] );
        }
    }
    /**
     * Отримує дані документа для модального вікна (Перегляд/Редагування).
     */
    public function handle_get_payment_doc(): void {
        check_ajax_referer( 'fstu_payment_docs_nonce', 'nonce' );

        $user_roles = (array) wp_get_current_user()->roles;
        if ( empty( array_intersect( [ 'administrator', 'userregistrar', 'groupauditor' ], $user_roles ) ) ) {
            wp_send_json_error( [ 'message' => 'Недостатньо прав.' ] );
        }

        $doc_id = absint( $_POST['doc_id'] ?? 0 );
        if ( ! $doc_id ) wp_send_json_error( [ 'message' => 'Невірний ID документа.' ] );

        global $wpdb;

        // Отримуємо шапку документа
        $header = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM Doc_DuesPayment WHERE Doc_DuesPayment_ID = %d", $doc_id ), ARRAY_A );

        if ( ! $header ) wp_send_json_error( [ 'message' => 'Документ не знайдено.' ] );

        // Отримуємо рядки ТЧ разом із роком і типом внеску
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $rows = $wpdb->get_results( $wpdb->prepare( "
			SELECT dp.TP_DuesPayment_UserID, dp.TP_DuesPayment_Sum, d.DuesType_ID, d.Year_ID
			FROM TP_DuesPayment dp
			LEFT JOIN Dues d ON d.TP_DuesPayment_ID = dp.TP_DuesPayment_ID
			WHERE dp.TP_DuesPayment_DPID = %d
			ORDER BY dp.TP_DuesPayment_Row ASC
		", $doc_id ), ARRAY_A );

        wp_send_json_success( [ 'header' => $header, 'rows' => $rows ] );
    }
    // кінець класу
}