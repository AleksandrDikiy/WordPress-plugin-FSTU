<?php
namespace FSTU\Modules\PersonalCabinet;

use FSTU\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX-обробники модуля «Особистий кабінет ФСТУ».
 *
 * Version:     1.5.1
 * Date_update: 2026-04-14
 */
class Personal_Cabinet_Ajax {

	private const HONEYPOT_FIELD = 'fstu_website';

	private Personal_Cabinet_Service $service;
	private Personal_Cabinet_Payments_Service $payments_service;

	public function __construct( ?Personal_Cabinet_Service $service = null, ?Personal_Cabinet_Payments_Service $payments_service = null ) {
		$this->service          = $service ?? new Personal_Cabinet_Service();
		$this->payments_service = $payments_service ?? new Personal_Cabinet_Payments_Service();
	}

	public function init(): void {
		add_action( 'wp_ajax_fstu_personal_cabinet_get_profile', [ $this, 'handle_get_profile' ] );
		add_action( 'wp_ajax_fstu_personal_cabinet_get_protocol', [ $this, 'handle_get_protocol' ] );
		add_action( 'wp_ajax_fstu_personal_cabinet_get_portmone_payload', [ $this, 'handle_get_portmone_payload' ] );
		add_action( 'wp_ajax_fstu_personal_cabinet_upload_dues_receipt', [ $this, 'handle_upload_dues_receipt' ] );
		add_action( 'admin_post_fstu_personal_cabinet_portmone_return', [ $this, 'handle_portmone_return' ] );
		add_action( 'admin_post_nopriv_fstu_personal_cabinet_portmone_return', [ $this, 'handle_portmone_return' ] );
		// Реєстрація нових методів
		add_action( 'wp_ajax_fstu_personal_cabinet_upload_photo', [ $this, 'handle_upload_photo' ] );
		add_action( 'wp_ajax_fstu_personal_cabinet_update_private_data', [ $this, 'handle_update_private_data' ] );
		add_action( 'wp_ajax_fstu_personal_cabinet_update_consent', [ $this, 'handle_update_consent' ] );
		add_action( 'wp_ajax_fstu_personal_cabinet_update_profile', [ $this, 'handle_update_profile' ] );
		// Методи для роботи з клубами
		add_action( 'wp_ajax_fstu_personal_cabinet_add_club', [ $this, 'handle_add_club' ] );
		add_action( 'wp_ajax_fstu_personal_cabinet_delete_club', [ $this, 'handle_delete_club' ] );
		add_action( 'wp_ajax_fstu_personal_cabinet_get_all_clubs', [ $this, 'handle_get_all_clubs' ] );
		// Методи для роботи з містами
		add_action( 'wp_ajax_fstu_personal_cabinet_get_all_cities', [ $this, 'handle_get_all_cities' ] );
		add_action( 'wp_ajax_fstu_personal_cabinet_add_city', [ $this, 'handle_add_city' ] );
		add_action( 'wp_ajax_fstu_personal_cabinet_delete_city', [ $this, 'handle_delete_city' ] );
        // Методи для роботи з осередками
        add_action( 'wp_ajax_fstu_personal_cabinet_get_all_units', [ $this, 'handle_get_all_units' ] );
        add_action( 'wp_ajax_fstu_personal_cabinet_add_unit', [ $this, 'handle_add_unit' ] );
        add_action( 'wp_ajax_fstu_personal_cabinet_delete_unit', [ $this, 'handle_delete_unit' ] );
		// Методи для роботи з видами туризму
		add_action( 'wp_ajax_fstu_personal_cabinet_get_all_tourism', [ $this, 'handle_get_all_tourism' ] );
		add_action( 'wp_ajax_fstu_personal_cabinet_add_tourism', [ $this, 'handle_add_tourism' ] );
		add_action( 'wp_ajax_fstu_personal_cabinet_delete_tourism', [ $this, 'handle_delete_tourism' ] );
		// Методи для роботи з Досвідом (Довідки)
		add_action( 'wp_ajax_fstu_personal_cabinet_update_experience_url', [ $this, 'handle_update_experience_url' ] );
		// Методи для роботи з розрядами
		add_action( 'wp_ajax_fstu_personal_cabinet_get_all_ranks', [ $this, 'handle_get_all_ranks' ] );
		add_action( 'wp_ajax_fstu_personal_cabinet_add_rank', [ $this, 'handle_add_rank' ] );
		add_action( 'wp_ajax_fstu_personal_cabinet_delete_rank', [ $this, 'handle_delete_rank' ] );
		// Методи для роботи з Суддівством
		add_action( 'wp_ajax_fstu_personal_cabinet_get_all_referee_categories', [ $this, 'handle_get_all_referee_categories' ] );
		add_action( 'wp_ajax_fstu_personal_cabinet_add_judging', [ $this, 'handle_add_judging' ] );
		add_action( 'wp_ajax_fstu_personal_cabinet_delete_judging', [ $this, 'handle_delete_judging' ] );
        // Методи для роботи з внесками вітрильників
        add_action( 'wp_ajax_fstu_personal_cabinet_add_sail_dues', [ $this, 'handle_add_sail_dues' ] );
	}

	public function handle_get_profile(): void {
		$this->verify_nonce();
		$profile_user_id = $this->sanitize_profile_user_id();
		if ( $profile_user_id <= 0 ) { $this->send_safe_error( 'Профіль не знайдено', 404 ); }

		$permissions = Capabilities::get_personal_cabinet_permissions( $profile_user_id );
		$payload = $this->service->get_profile_payload( $profile_user_id, $permissions );
		wp_send_json_success( $payload );
	}
	// Нові методи для обробки AJAX-запитів
	public function handle_upload_photo(): void {
		$this->verify_nonce();
		$this->assert_authenticated();
		$this->validate_honeypot();

		$profile_user_id = $this->sanitize_profile_user_id();
		if ( $profile_user_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			$this->send_safe_error( 'Ви можете оновлювати лише власне фото.', 403 );
		}

		if ( empty( $_FILES['profile_photo'] ) || 0 !== $_FILES['profile_photo']['error'] ) {
			$this->send_safe_error( 'Помилка файлу.', 400 );
		}

		$file = $_FILES['profile_photo']; // phpcs:ignore
		
		// Надійна перевірка на те, чи це зображення (працює на всіх хостингах)
		$image_info = getimagesize( $file['tmp_name'] );
		if ( false === $image_info || ! in_array( $image_info[2], [ IMAGETYPE_JPEG ], true ) ) {
			$this->send_safe_error( 'Файл не є дійсним зображенням у форматі JPG/JPEG.', 400 );
		}

		if ( $file['size'] > 2 * 1024 * 1024 ) {
			$this->send_safe_error( 'Розмір файлу не повинен перевищувати 2 МБ.', 400 );
		}

		$target_dir = ABSPATH . 'photo/';
		if ( ! is_dir( $target_dir ) ) { wp_mkdir_p( $target_dir ); }

		$target_path = $target_dir . $profile_user_id . '.jpg';
		if ( ! move_uploaded_file( $file['tmp_name'], $target_path ) ) {
			$this->send_safe_error( 'Помилка збереження на сервері.', 500 );
		}

		$this->service->get_protocol_service()->log_action_for_user( 
			get_current_user_id(), 'U', "Оновлено фото профілю (ID $profile_user_id)", '✓' 
		);

		wp_send_json_success( [ 'message' => 'Фото оновлено.' ] );
	}

	public function handle_update_private_data(): void {
		$this->verify_nonce();
		$this->assert_authenticated();

		if ( ! current_user_can( 'manage_options' ) ) { $this->send_safe_error( 'Тільки для адміна.', 403 ); }

		$profile_user_id = $this->sanitize_profile_user_id();
		$allowed_keys = [ 'Adr', 'Job', 'Education', 'Phone2', 'Phone3', 'PhoneFamily' ];
		
		foreach ( $allowed_keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_user_meta( $profile_user_id, $key, sanitize_text_field( $_POST[ $key ] ) );
			}
		}

		// ВИКОРИСТАННЯ ГЕТТЕРА: Записуємо в протокол
		$this->service->get_protocol_service()->log_action_for_user( 
            get_current_user_id(), 'U', "Адмін оновив приватні дані профілю ID $profile_user_id", '✓' 
        );

		wp_send_json_success( [ 'message' => 'Дані збережено.' ] );
	}

	public function handle_get_protocol(): void {
		$this->verify_nonce();
		$this->assert_authenticated();
		$page = max( 1, absint( $_POST['page'] ?? 1 ) );
		$per_page = max( 1, absint( $_POST['per_page'] ?? 10 ) );
		$search = sanitize_text_field( $_POST['search'] ?? '' );
		wp_send_json_success( $this->service->get_protocol_payload( $search, $page, $per_page ) );
	}
	// Новий метод для отримання даних для Portmone
	public function handle_get_portmone_payload(): void {
		$this->verify_nonce();
		$profile_user_id = $this->sanitize_profile_user_id();
		$year = absint($_POST['year'] ?? current_time('Y')); // Отримуємо рік з кнопки
		
		if ($profile_user_id <= 0) {
			$this->send_safe_error('Користувача не знайдено');
		}

		global $wpdb;
		// Отримуємо суму як у старому плагіні
		$amount = floatval( $wpdb->get_var( "SELECT GetParamValueSettings('AnnualFee') as 'AnnualFee'" ) );
		if ($amount <= 0) $amount = 25; // Страховка
		
		// Рахуємо комісію еквайрингу 2.3%
		$bill_amount = $amount + round(($amount * 0.023), 2);
		
		// ФІО
		$fio = trim( get_user_meta($profile_user_id, 'last_name', true) . ' ' . get_user_meta($profile_user_id, 'first_name', true) . ' ' . get_user_meta($profile_user_id, 'Patronymic', true) );

		// Формуємо payload
		$payload = [
			'gatewayUrl' => 'https://www.portmone.com.ua/gateway/',
			'method' => 'POST',
			'fields' => [
				'payee_id'          => '28935',
				'shop_order_number' => $year . $profile_user_id,
				'bill_amount'       => $bill_amount,
				'description'       => 'Благодійна допомога у вигляді реєстраційних членських внесків за ' . $year . ' рік, платник ' . $fio,
				
				// ВИПРАВЛЕННЯ: Відправляємо на системний обробник (webhook)
				'success_url'       => admin_url('admin-post.php?action=fstu_personal_cabinet_portmone_return&result=success&user_id=' . $profile_user_id),
				'failure_url'       => admin_url('admin-post.php?action=fstu_personal_cabinet_portmone_return&result=failure&user_id=' . $profile_user_id),
				
				'attribute1'        => $year,
				'attribute2'        => get_current_user_id(),
				'lang'              => 'uk',
				'encoding'          => 'UTF-8',
				'exp_time'          => '400'
			]
		];

		wp_send_json_success($payload);
	}
	// Новий метод для обробки завантаження квитанції про внесок
	public function handle_upload_dues_receipt(): void {
		$this->verify_nonce();
		$profile_user_id = $this->sanitize_profile_user_id();
		$permissions = Capabilities::get_personal_cabinet_permissions( $profile_user_id );
		$result = $this->service->save_dues_receipt( $profile_user_id, $permissions, absint($_POST['year_id']), (float)$_POST['summa'], esc_url_raw($_POST['url']) );
		is_wp_error( $result ) ? $this->send_safe_error( $result->get_error_message() ) : wp_send_json_success( $result );
	}
    // Новий метод для обробки повернення з Portmone
    public function handle_portmone_return(): void {
        // Отримуємо базові дані з URL, які ми самі ж туди і поклали
        $result = sanitize_key($_GET['result'] ?? '');
        $profile_user_id = absint($_GET['user_id'] ?? 0);

        // ЯКЩО ОПЛАТА УСПІШНА
        if ( 'success' === $result && $profile_user_id > 0 ) {
            global $wpdb;

            // 1. Отримуємо суму з налаштувань (точно як у Personal.php)
            $amount = floatval( $wpdb->get_var( "SELECT GetParamValueSettings('AnnualFee') as 'AnnualFee'" ) );
            if ( $amount <= 0 ) $amount = 25; // Страховка

            // 2. Отримуємо параметри, які повернув Portmone у $_POST масиві
            $year = absint( $_POST['ATTRIBUTE1'] ?? 0 );
            $user_create = absint( $_POST['ATTRIBUTE2'] ?? 0 );

            // 3. Захист від дублювання (перевіряємо чи цей рік вже не оплачений)
            $exists = $wpdb->get_var( $wpdb->prepare( "SELECT Dues_ID FROM Dues WHERE User_ID = %d AND Year_ID = %d", $profile_user_id, $year ) );

            if ( ! $exists && $year > 0 ) {
                // 4. Прямий запис у БД (ідентично до логіки старого плагіна)
                $wpdb->insert( 'Dues', [
                    'Dues_DateCreate'      => current_time('mysql'),
                    'User_ID'              => $profile_user_id,
                    'Year_ID'              => $year,
                    'UserCreate'           => $user_create > 0 ? $user_create : $profile_user_id,
                    'Dues_ShopBillid'      => sanitize_text_field( $_POST['SHOPBILLID'] ?? '' ),
                    'Dues_ShopOrderNumber' => sanitize_text_field( $_POST['SHOPORDERNUMBER'] ?? '' ),
                    'Dues_ApprovalCode'    => sanitize_text_field( $_POST['APPROVALCODE'] ?? '' ),
                    'Dues_CardMask'        => sanitize_text_field( $_POST['CARD_MASK'] ?? '' ),
                    'Dues_Summa'           => $amount,
                    'DuesType_ID'          => 1 // 1 = Членський внесок
                ] );

                // Логування в протокол
                $this->service->get_protocol_service()->log_action_for_user(
                    $user_create > 0 ? $user_create : $profile_user_id, 'I',
                    "Онлайн-оплату членського внеску за $year рік успішно зафіксовано через Portmone.", '✓'
                );
            }

            // 5. Перенаправляємо назад до кабінету з повідомленням про успіх
            wp_safe_redirect( home_url("/personal/?ViewID={$profile_user_id}&payment_status=success&payment_year={$year}") );
            exit;
        }

        // ЯКЩО ПОМИЛКА АБО СКАСУВАННЯ
        if ( 'failure' === $result && $profile_user_id > 0 ) {
            wp_safe_redirect( home_url("/personal/?ViewID={$profile_user_id}&payment_status=failure") );
            exit;
        }

        // Fallback (резервний варіант)
        wp_safe_redirect( home_url('/personal/') );
        exit;
    }

	private function verify_nonce(): void { check_ajax_referer( Personal_Cabinet_List::NONCE_ACTION, 'nonce' ); }
	private function assert_authenticated(): void { if ( ! is_user_logged_in() ) { $this->send_safe_error( 'Авторизуйтесь', 401 ); } }
	private function validate_honeypot(): void { if ( ! empty( $_POST[ self::HONEYPOT_FIELD ] ) ) { $this->send_safe_error( 'Bot detected', 400 ); } }
	private function send_safe_error( string $message, int $status = 400 ): void { wp_send_json_error( [ 'message' => $message ], $status ); }
	private function sanitize_profile_user_id(): int {
		$id = absint( $_POST['profile_user_id'] ?? get_current_user_id() );
		return get_userdata( $id ) ? $id : 0;
	}
	public function handle_update_consent(): void {
		$this->verify_nonce();
		$this->assert_authenticated();

		$profile_user_id = $this->sanitize_profile_user_id();
		if ( $profile_user_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			$this->send_safe_error( 'Немає прав.', 403 );
		}

		$consent = isset( $_POST['consent'] ) && '1' === $_POST['consent'] ? '1' : '0';
		update_user_meta( $profile_user_id, 'FlagView', $consent );

		//$protocol = new Personal_Cabinet_Protocol_Service();
		$protocol = $this->service->get_protocol_service();
		$protocol->log_action_for_user( 
			get_current_user_id(), 'U', "Оновлено згоду на показ персональних даних (ID $profile_user_id) -> $consent", '✓' 
		);

		wp_send_json_success();
	}
	public function handle_update_profile(): void {
		$this->verify_nonce();
		$this->assert_authenticated();

		$profile_user_id = $this->sanitize_profile_user_id();
		if ( $profile_user_id <= 0 ) {
			$this->send_safe_error( 'Профіль не знайдено.', 404 );
		}

		$is_owner   = $profile_user_id === get_current_user_id();
		$is_admin   = current_user_can( 'administrator' );
		$is_global  = current_user_can( 'globalregistrar' );
		$is_userreg = current_user_can( 'userregistrar' );

		$can_edit_full    = $is_owner || $is_admin || $is_global;
		$can_edit_partial = $can_edit_full || $is_userreg;

		if ( ! $can_edit_partial ) {
			$this->send_safe_error( 'У вас немає прав для редагування цього профілю.', 403 );
		}

		global $wpdb;
		$tab = sanitize_key( $_POST['current_tab'] ?? 'general' );
		$changes_log = []; // Масив для збору змінених полів

		// Словник для зрозумілого відображення полів у протоколі
		$field_labels = [
			'last_name' => 'Прізвище', 'first_name' => 'Ім\'я', 'Patronymic' => 'По батькові',
			'Sex' => 'Стать', 'BirthDate' => 'Дата народження', 'PhoneMobile' => 'Телефон',
			'Skype' => 'Skype', 'FaceBook' => 'Facebook', 'user_email' => 'Email',
			'Adr' => 'Адреса', 'Job' => 'Посада', 'Education' => 'Освіта',
			'Phone2' => 'Дод. телефон 1', 'Phone3' => 'Дод. телефон 2', 'PhoneFamily' => 'Телефон родичів',
			'TelegramVerification' => 'Активація Telegram', 'VerificationCode' => 'VerificationCode',
			'IPN' => 'ІПН', 'BankName' => 'Назва банку', 'IBAN' => 'IBAN', 'TelegramID' => 'Telegram ID'
		];

		$wpdb->query( 'START TRANSACTION' );

		try {
			// --- ОБРОБКА ВКЛАДКИ "ЗАГАЛЬНІ" ---
			$meta_keys_gen = [ 'last_name', 'first_name', 'Patronymic', 'Sex', 'BirthDate', 'PhoneMobile', 'Skype', 'FaceBook' ];
			foreach ( $meta_keys_gen as $key ) {
				if ( isset( $_POST[ $key ] ) ) {
					$new_val = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
					$old_val = (string) get_user_meta( $profile_user_id, $key, true );
					if ( $new_val !== $old_val ) {
						update_user_meta( $profile_user_id, $key, $new_val );
						$changes_log[] = sprintf( '%s: "%s" -> "%s"', $field_labels[$key] ?? $key, $old_val ?: '—', $new_val ?: '—' );
					}
				}
			}
			if ( isset( $_POST['user_email'] ) ) {
				$new_email = sanitize_email( wp_unslash( $_POST['user_email'] ) );
				$user_obj  = get_userdata( $profile_user_id );
				$old_email = $user_obj ? $user_obj->user_email : '';
				if ( is_email( $new_email ) && $new_email !== $old_email ) {
					wp_update_user( [ 'ID' => $profile_user_id, 'user_email' => $new_email ] );
					$changes_log[] = sprintf( 'Email: "%s" -> "%s"', $old_email ?: '—', $new_email );
				}
			}

			// --- ОБРОБКА ВКЛАДКИ "ПРИВАТНЕ" ---
			$meta_keys_priv = [ 'Adr', 'Job', 'Education', 'Phone2', 'Phone3', 'PhoneFamily' ];
			foreach ( $meta_keys_priv as $key ) {
				if ( isset( $_POST[ $key ] ) ) {
					$new_val = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
					$old_val = (string) get_user_meta( $profile_user_id, $key, true );
					if ( $new_val !== $old_val ) {
						update_user_meta( $profile_user_id, $key, $new_val );
						$changes_log[] = sprintf( '%s: "%s" -> "%s"', $field_labels[$key] ?? $key, $old_val ?: '—', $new_val ?: '—' );
					}
				}
			}

			// --- ОБРОБКА ВКЛАДКИ "СЛУЖБОВЕ" ---
			if ( $can_edit_full ) {
				$user_obj = get_userdata( $profile_user_id );
				$user_fields = [ 'TelegramVerification', 'VerificationCode' ];
				foreach ( $user_fields as $key ) {
					if ( isset( $_POST[ $key ] ) ) {
						$new_val = sanitize_text_field( $_POST[ $key ] );
						$old_val = isset( $user_obj->$key ) ? (string) $user_obj->$key : '';
						if ( $new_val !== $old_val ) {
							$wpdb->update( $wpdb->users, [ $key => $new_val ], [ 'ID' => $profile_user_id ] );
							$changes_log[] = sprintf( '%s: "%s" -> "%s"', $field_labels[$key] ?? $key, $old_val ?: '—', $new_val ?: '—' );
						}
					}
				}
				$param_keys = [ 'IPN', 'BankName', 'IBAN', 'TelegramID' ];
				foreach ( $param_keys as $param ) {
					if ( isset( $_POST[ $param ] ) ) {
						$new_val = sanitize_text_field( wp_unslash( $_POST[ $param ] ) );
						$row = $wpdb->get_row( $wpdb->prepare( "SELECT UserParams_ID, UserParams_Value FROM UserParams WHERE User_ID = %d AND UserParams_Name = %s", $profile_user_id, $param ) );
						$old_val = $row ? (string) $row->UserParams_Value : '';
						if ( $new_val !== $old_val ) {
							if ( $row ) {
								$wpdb->update( 'UserParams', [ 'UserParams_Value' => $new_val ], [ 'UserParams_ID' => $row->UserParams_ID ] );
							} else {
								$wpdb->insert( 'UserParams', [ 'User_ID' => $profile_user_id, 'UserParams_Name' => $param, 'UserParams_Value' => $new_val ] );
							}
							$changes_log[] = sprintf( '%s: "%s" -> "%s"', $field_labels[$param] ?? $param, $old_val ?: '—', $new_val ?: '—' );
						}
					}
				}
			}

			// Записуємо в протокол тільки якщо були реальні зміни
			if ( ! empty( $changes_log ) ) {
				$protocol = $this->service->get_protocol_service();
				$changes_str = implode( '; ', $changes_log );
				$message = sprintf( "Оновлено профіль ID %d. Зміни: %s", $profile_user_id, $changes_str );
				
				$protocol->log_action_for_user( get_current_user_id(), 'U', $message, '✓' );
				$wpdb->query( 'COMMIT' );
				wp_send_json_success( [ 'message' => 'Зміни успішно збережено.' ] );
			} else {
				$wpdb->query( 'COMMIT' );
				wp_send_json_success( [ 'message' => 'Дані актуальні, змін не виявлено.' ] );
			}

		} catch ( \Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			$this->send_safe_error( 'Помилка бази даних під час збереження.', 500 );
		}
	}
	// Методи для роботи з клубами
	public function handle_get_all_clubs(): void {
		$this->verify_nonce();
		$this->assert_authenticated();

		global $wpdb;
		
		// Придушуємо виведення помилок БД в екран, щоб вони не ламали JSON-формат відповіді
		$wpdb->suppress_errors( true );
		
		// Запитуємо ID та назви всіх клубів з довідника
		$clubs = $wpdb->get_results( "SELECT Club_ID as id, Club_Name as name FROM S_Club ORDER BY Club_Name ASC", ARRAY_A );
		
		if ( $wpdb->last_error ) {
			// Якщо таблиця називається якось інакше (наприклад S_Club або Clubs), ми повернемо текст помилки в JS
			$this->send_safe_error( 'Помилка SQL: ' . $wpdb->last_error, 500 );
		}

		wp_send_json_success( is_array( $clubs ) ? $clubs : [] );
	}
	public function handle_add_club(): void {
		$this->verify_nonce();
		$this->assert_authenticated();
		$profile_user_id = $this->sanitize_profile_user_id();
		
		$permissions = \FSTU\Core\Capabilities::get_personal_cabinet_permissions( $profile_user_id );
		if ( empty( $permissions['canManageClubs'] ) ) {
			$this->send_safe_error( 'Немає прав для керування клубами.', 403 );
		}

		$club_id = absint( $_POST['club_id'] ?? 0 );
		if ( $club_id <= 0 ) {
			$this->send_safe_error( 'Некоректний ID клубу.', 400 );
		}

		global $wpdb;
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT Club_ID FROM UserClub WHERE User_ID = %d AND Club_ID = %d", $profile_user_id, $club_id ) );
		if ( $exists ) {
			$this->send_safe_error( 'Цей клуб вже є в списку користувача.', 400 );
		}

		// Дістаємо красиву назву клубу для Протоколу
		$club_name = $wpdb->get_var( $wpdb->prepare( "SELECT Club_Name FROM S_Club WHERE Club_ID = %d", $club_id ) );
		$club_name = $club_name ? $club_name : "ID $club_id";

		$wpdb->query( 'START TRANSACTION' );
		try {
			$wpdb->insert( 'UserClub', [
				'User_ID' => $profile_user_id,
				'Club_ID' => $club_id,
				'UserClub_Date' => current_time('mysql')
			] );

			$protocol = $this->service->get_protocol_service();
			// Тепер лог буде виглядати як: Додано клуб: "ДніпроВелоКлуб" (профіль ID 35)
			$protocol->log_action_for_user( get_current_user_id(), 'I', "Додано клуб: \"$club_name\" (профіль ID $profile_user_id)", '✓' );
			
			$wpdb->query( 'COMMIT' );
			wp_send_json_success( [ 'message' => 'Клуб успішно додано.' ] );
		} catch ( \Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			$this->send_safe_error( 'Помилка бази даних.', 500 );
		}
	}

	public function handle_delete_club(): void {
		$this->verify_nonce();
		$this->assert_authenticated();
		$profile_user_id = $this->sanitize_profile_user_id();
		
		if ( empty( \FSTU\Core\Capabilities::get_personal_cabinet_permissions( $profile_user_id )['canManageClubs'] ) ) {
			$this->send_safe_error( 'Немає прав для видалення клубів.', 403 );
		}

		$identifier = sanitize_text_field( wp_unslash( $_POST['club_id'] ?? '' ) );
		$club_id = absint( $identifier );
		global $wpdb;

		// Якщо ID прийшов як текст (назва), шукаємо його реальний ID у базі
		if ( $club_id === 0 && '' !== $identifier ) {
			$sql = "SELECT c.Club_ID FROM S_Club c JOIN UserClub uc ON c.Club_ID = uc.Club_ID WHERE c.Club_Name = %s AND uc.User_ID = %d LIMIT 1";
			$club_id = (int) $wpdb->get_var( $wpdb->prepare( $sql, $identifier, $profile_user_id ) );
		}

		if ( $club_id <= 0 ) {
			$this->send_safe_error( 'Помилка: неможливо знайти клуб для видалення.', 400 );
		}
		
		$club_name = $wpdb->get_var( $wpdb->prepare( "SELECT Club_Name FROM S_Club WHERE Club_ID = %d", $club_id ) ) ?: "ID $club_id";

		$wpdb->query( 'START TRANSACTION' );
		try {
			$deleted = $wpdb->delete( 'UserClub', [ 'User_ID' => $profile_user_id, 'Club_ID' => $club_id ] );
			if ( $deleted ) {
				$this->service->get_protocol_service()->log_action_for_user( get_current_user_id(), 'D', "Видалено клуб: \"$club_name\" (профіль ID $profile_user_id)", '✓' );
			}
			$wpdb->query( 'COMMIT' );
			wp_send_json_success( [ 'message' => 'Клуб успішно видалено.' ] );
		} catch ( \Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			$this->send_safe_error( 'Помилка бази даних.', 500 );
		}
	}
	// Методи для роботи з містами
	public function handle_get_all_cities(): void {
		$this->verify_nonce();
		global $wpdb;
		$wpdb->suppress_errors(true);
		// Об'єднуємо місто з областю для зручного пошуку
		$sql = "SELECT c.City_ID as id, CONCAT(c.City_Name, ' (', r.Region_Name, ')') as name 
				FROM S_City c 
				LEFT JOIN S_Region r ON c.Region_ID = r.Region_ID 
				ORDER BY c.City_Name ASC";
		$results = $wpdb->get_results($sql, ARRAY_A);
		wp_send_json_success($results ?: []);
	}

	public function handle_add_city(): void {
		$this->verify_nonce();
		$this->assert_authenticated();
		$profile_user_id = $this->sanitize_profile_user_id();
		
		if ( ! current_user_can('userregistrar') && ! current_user_can('administrator') && get_current_user_id() !== $profile_user_id ) {
			$this->send_safe_error('Немає прав.', 403);
		}
		
		$city_id = absint($_POST['city_id'] ?? 0);
		if ( $city_id <= 0 ) {
			$this->send_safe_error( 'Некоректний ID міста.', 400 );
		}

		global $wpdb;

		// Дістаємо місто разом з областю
		$sql = "SELECT CONCAT(c.City_Name, ' (', r.Region_Name, ')') 
				FROM S_City c 
				LEFT JOIN S_Region r ON c.Region_ID = r.Region_ID 
				WHERE c.City_ID = %d";
		$city_name = $wpdb->get_var( $wpdb->prepare( $sql, $city_id ) );
		$city_name = $city_name ? $city_name : "ID $city_id";

		$wpdb->query('START TRANSACTION');
		try {
			$wpdb->insert('UserCity', [
				'User_ID' => $profile_user_id,
				'City_ID' => $city_id,
				'UserCity_DateCreate' => current_time('mysql')
			]);
			
			$this->service->get_protocol_service()->log_action_for_user( get_current_user_id(), 'I', "Додано місто: \"$city_name\" (профіль ID $profile_user_id)", '✓' );
			
			$wpdb->query('COMMIT');
			wp_send_json_success();
		} catch (\Exception $e) { 
			$wpdb->query('ROLLBACK'); 
			$this->send_safe_error('Помилка БД.', 500); 
		}
	}

	public function handle_delete_city(): void {
		$this->verify_nonce();
		$this->assert_authenticated();
		$profile_user_id = $this->sanitize_profile_user_id();
		
		if ( ! current_user_can('userregistrar') && ! current_user_can('administrator') && get_current_user_id() !== $profile_user_id ) {
			$this->send_safe_error('Немає прав.', 403);
		}

		$identifier = sanitize_text_field( wp_unslash( $_POST['city_id'] ?? '' ) );
		$city_id = absint( $identifier );
		global $wpdb;

		// Якщо ID прийшов як текст (назва), шукаємо його реальний ID у базі
		if ( $city_id === 0 && '' !== $identifier ) {
			$sql = "SELECT c.City_ID FROM S_City c JOIN UserCity uc ON c.City_ID = uc.City_ID WHERE c.City_Name = %s AND uc.User_ID = %d LIMIT 1";
			$city_id = (int) $wpdb->get_var( $wpdb->prepare( $sql, $identifier, $profile_user_id ) );
		}

		if ( $city_id <= 0 ) {
			$this->send_safe_error( 'Помилка: неможливо знайти місто для видалення.', 400 );
		}

		$sql_name = "SELECT CONCAT(c.City_Name, ' (', r.Region_Name, ')') FROM S_City c LEFT JOIN S_Region r ON c.Region_ID = r.Region_ID WHERE c.City_ID = %d";
		$city_name = $wpdb->get_var( $wpdb->prepare( $sql_name, $city_id ) ) ?: "ID $city_id";

		$wpdb->query('START TRANSACTION');
		try {
			$deleted = $wpdb->delete('UserCity', [ 'User_ID' => $profile_user_id, 'City_ID' => $city_id ]);
			if ( $deleted ) {
				$this->service->get_protocol_service()->log_action_for_user( get_current_user_id(), 'D', "Видалено місто: \"$city_name\" (профіль ID $profile_user_id)", '✓' );
			}
			$wpdb->query('COMMIT');
			wp_send_json_success( [ 'message' => 'Місто успішно видалено.' ] );
		} catch (\Exception $e) { 
			$wpdb->query('ROLLBACK'); 
			$this->send_safe_error('Помилка БД.', 500); 
		}
	}
    public function handle_get_all_units(): void {
        $this->verify_nonce();
        global $wpdb;
        $wpdb->suppress_errors(true);

        // Об'єднуємо осередок з областю
        $sql = "SELECT u.Unit_ID as id, CONCAT(u.Unit_Name, ' (', r.Region_Name, ')') as name 
				FROM S_Unit u 
				LEFT JOIN S_Region r ON u.Region_ID = r.Region_ID 
				ORDER BY u.Unit_Name ASC";
        $results = $wpdb->get_results($sql, ARRAY_A);

        if ( $wpdb->last_error ) {
            $this->send_safe_error( 'Помилка SQL: ' . $wpdb->last_error, 500 );
        }

        wp_send_json_success($results ?: []);
    }

    public function handle_add_unit(): void {
        $this->verify_nonce();
        $this->assert_authenticated();
        $profile_user_id = $this->sanitize_profile_user_id();

        if ( empty( \FSTU\Core\Capabilities::get_personal_cabinet_permissions( $profile_user_id )['canManageUnits'] ) ) {
            $this->send_safe_error('Немає прав для керування осередками.', 403);
        }

        $unit_id = absint($_POST['unit_id'] ?? 0);
        if ( $unit_id <= 0 ) {
            $this->send_safe_error( 'Некоректний ID осередку.', 400 );
        }

        global $wpdb;

        // ДІСТАЄМО ОСЕРЕДОК РАЗОМ З REGION_ID
        $sql = "SELECT u.Region_ID, CONCAT(u.Unit_Name, ' (', r.Region_Name, ')') as Unit_Name_Full 
				FROM S_Unit u 
				LEFT JOIN S_Region r ON u.Region_ID = r.Region_ID 
				WHERE u.Unit_ID = %d";
        $unit_data = $wpdb->get_row( $wpdb->prepare( $sql, $unit_id ), ARRAY_A );

        $unit_name = $unit_data['Unit_Name_Full'] ?? "ID $unit_id";
        $region_id = $unit_data['Region_ID'] ?? 0;

        $wpdb->query('START TRANSACTION');
        try {
            // ТЕПЕР ПЕРЕДАЄМО Region_ID, ЯК ЦЬОГО ВИМАГАЄ СТАРА БАЗА
            $inserted = $wpdb->insert('UserRegistationOFST', [
                'User_ID' => $profile_user_id,
                'Region_ID' => $region_id,
                'Unit_ID' => $unit_id,
                'UserRegistationOFST_DateCreate' => current_time('mysql')
            ]);

            // Якщо база відмовилася записувати рядок (наприклад, через дублікат чи обмеження)
            if ( false === $inserted ) {
                throw new \Exception('Помилка вставки в БД');
            }

            $this->service->get_protocol_service()->log_action_for_user( get_current_user_id(), 'I', "Додано осередок: \"$unit_name\" (профіль ID $profile_user_id)", '✓' );

            $wpdb->query('COMMIT');
            wp_send_json_success( [ 'message' => 'Осередок успішно додано.' ] );
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            $this->send_safe_error('Помилка збереження в базу даних. Можливо, такий осередок вже існує.', 500);
        }
    }

    public function handle_delete_unit(): void {
        $this->verify_nonce();
        $this->assert_authenticated();
        $profile_user_id = $this->sanitize_profile_user_id();

        if ( empty( \FSTU\Core\Capabilities::get_personal_cabinet_permissions( $profile_user_id )['canManageUnits'] ) ) {
            $this->send_safe_error('Немає прав для видалення осередків.', 403);
        }

        $identifier = sanitize_text_field( wp_unslash( $_POST['unit_id'] ?? '' ) );
        $passed_id = absint( $identifier );
        global $wpdb;

        // 1. Спочатку шукаємо за точним первинним ключем (UserRegistationOFST_ID)
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM UserRegistationOFST WHERE UserRegistationOFST_ID = %d AND User_ID = %d", $passed_id, $profile_user_id ) );

        // 2. Якщо не знайшли (можливо прийшов Unit_ID), шукаємо за Unit_ID
        if ( ! $row && $passed_id > 0 ) {
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM UserRegistationOFST WHERE Unit_ID = %d AND User_ID = %d LIMIT 1", $passed_id, $profile_user_id ) );
        }

        // 3. Fallback: Шукаємо за текстом (якщо прийшла назва області)
        if ( ! $row && $passed_id === 0 && '' !== $identifier ) {
            $sql = "SELECT uro.* FROM UserRegistationOFST uro LEFT JOIN S_Unit u ON uro.Unit_ID = u.Unit_ID LEFT JOIN S_Region r ON uro.Region_ID = r.Region_ID WHERE (u.Unit_Name = %s OR r.Region_Name = %s) AND uro.User_ID = %d LIMIT 1";
            $row = $wpdb->get_row( $wpdb->prepare( $sql, $identifier, $identifier, $profile_user_id ) );
        }

        if ( ! $row ) {
            $this->send_safe_error( 'Помилка: неможливо знайти осередок для видалення.', 400 );
        }

        $target_id = (int) $row->UserRegistationOFST_ID;
        $unit_id = (int) $row->Unit_ID;

        $sql_name = "SELECT CONCAT(u.Unit_Name, ' (', r.Region_Name, ')') FROM S_Unit u LEFT JOIN S_Region r ON u.Region_ID = r.Region_ID WHERE u.Unit_ID = %d";
        $unit_name = $wpdb->get_var( $wpdb->prepare( $sql_name, $unit_id ) ) ?: "ID $unit_id";

        $wpdb->query('START TRANSACTION');
        try {
            // Видаляємо за точним первинним ключем!
            $deleted = $wpdb->delete('UserRegistationOFST', [ 'UserRegistationOFST_ID' => $target_id ]);
            if ( $deleted ) {
                $this->service->get_protocol_service()->log_action_for_user( get_current_user_id(), 'D', "Видалено осередок: \"$unit_name\" (профіль ID $profile_user_id)", '✓' );
            }
            $wpdb->query('COMMIT');
            wp_send_json_success( [ 'message' => 'Осередок успішно видалено.' ] );
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            $this->send_safe_error('Помилка БД.', 500);
        }
    }
	public function handle_get_all_tourism(): void {
		$this->verify_nonce();
		global $wpdb;
		$wpdb->suppress_errors(true);
		
		$results = $wpdb->get_results( "SELECT TourismType_ID as id, TourismType_Name as name FROM S_TourismType ORDER BY TourismType_Name ASC", ARRAY_A );
		if ( $wpdb->last_error ) { $this->send_safe_error( 'Помилка SQL', 500 ); }
		wp_send_json_success( $results ?: [] );
	}

	public function handle_add_tourism(): void {
		$this->verify_nonce();
		$this->assert_authenticated();
		$profile_user_id = $this->sanitize_profile_user_id();
		
		if ( empty( \FSTU\Core\Capabilities::get_personal_cabinet_permissions( $profile_user_id )['canManageTourism'] ) ) {
			$this->send_safe_error('Немає прав для керування.', 403);
		}
		
		$tourism_id = absint($_POST['tourism_id'] ?? 0);
		if ( $tourism_id <= 0 ) { $this->send_safe_error( 'Некоректний ID.', 400 ); }

		global $wpdb;
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT UserTourismType_ID FROM UserTourismType WHERE User_ID = %d AND TourismType_ID = %d", $profile_user_id, $tourism_id ) );
		if ( $exists ) { $this->send_safe_error( 'Цей вид туризму вже додано.', 400 ); }

		$tourism_name = $wpdb->get_var( $wpdb->prepare( "SELECT TourismType_Name FROM S_TourismType WHERE TourismType_ID = %d", $tourism_id ) ) ?: "ID $tourism_id";

		$wpdb->query('START TRANSACTION');
		try {
			$inserted = $wpdb->insert('UserTourismType', [
				'User_ID' => $profile_user_id,
				'TourismType_ID' => $tourism_id,
				'UserTourismType_DateCreate' => current_time('mysql')
			]);
			
			if ( false === $inserted ) { throw new \Exception('Помилка вставки в БД'); }
			
			$this->service->get_protocol_service()->log_action_for_user( get_current_user_id(), 'I', "Додано вид туризму: \"$tourism_name\" (профіль ID $profile_user_id)", '✓' );
			$wpdb->query('COMMIT');
			wp_send_json_success( [ 'message' => 'Вид туризму додано.' ] );
		} catch (\Exception $e) { 
			$wpdb->query('ROLLBACK'); 
			$this->send_safe_error('Помилка збереження в базу даних.', 500); 
		}
	}

	public function handle_delete_tourism(): void {
		$this->verify_nonce();
		$this->assert_authenticated();
		$profile_user_id = $this->sanitize_profile_user_id();
		
		if ( empty( \FSTU\Core\Capabilities::get_personal_cabinet_permissions( $profile_user_id )['canManageTourism'] ) ) {
			$this->send_safe_error('Немає прав для видалення.', 403);
		}

		$identifier = sanitize_text_field( wp_unslash( $_POST['tourism_id'] ?? '' ) );
		$passed_id = absint( $identifier );
		global $wpdb;

		// Шукаємо за первинним ключем (UserTourismType_ID)
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM UserTourismType WHERE UserTourismType_ID = %d AND User_ID = %d", $passed_id, $profile_user_id ) );
		
		// Шукаємо за TourismType_ID
		if ( ! $row && $passed_id > 0 ) {
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM UserTourismType WHERE TourismType_ID = %d AND User_ID = %d LIMIT 1", $passed_id, $profile_user_id ) );
		}
		
		// Шукаємо за текстом
		if ( ! $row && $passed_id === 0 && '' !== $identifier ) {
			$sql = "SELECT ut.* FROM UserTourismType ut JOIN S_TourismType t ON ut.TourismType_ID = t.TourismType_ID WHERE t.TourismType_Name = %s AND ut.User_ID = %d LIMIT 1";
			$row = $wpdb->get_row( $wpdb->prepare( $sql, $identifier, $profile_user_id ) );
		}

		if ( ! $row ) { $this->send_safe_error( 'Помилка: неможливо знайти запис для видалення.', 400 ); }

		$target_id = (int) $row->UserTourismType_ID;
		$tourism_id = (int) $row->TourismType_ID;
		$tourism_name = $wpdb->get_var( $wpdb->prepare( "SELECT TourismType_Name FROM S_TourismType WHERE TourismType_ID = %d", $tourism_id ) ) ?: "ID $tourism_id";

		$wpdb->query('START TRANSACTION');
		try {
			$deleted = $wpdb->delete('UserTourismType', [ 'UserTourismType_ID' => $target_id ]);
			if ( $deleted ) {
				$this->service->get_protocol_service()->log_action_for_user( get_current_user_id(), 'D', "Видалено вид туризму: \"$tourism_name\" (профіль ID $profile_user_id)", '✓' );
			}
			$wpdb->query('COMMIT');
			wp_send_json_success( [ 'message' => 'Вид туризму видалено.' ] );
		} catch (\Exception $e) { 
			$wpdb->query('ROLLBACK'); 
			$this->send_safe_error('Помилка БД.', 500); 
		}
	}
	public function handle_update_experience_url(): void {
		$this->verify_nonce();
		$this->assert_authenticated();
		$profile_user_id = $this->sanitize_profile_user_id();

		$is_owner  = $profile_user_id === get_current_user_id();
		$is_admin  = current_user_can( 'administrator' );
		$is_global = current_user_can( 'globalregistrar' );

		if ( ! $is_owner && ! $is_admin && ! $is_global ) {
			$this->send_safe_error('Немає прав для редагування.', 403);
		}

		$passed_id = absint($_POST['experience_id'] ?? 0);
		$url = esc_url_raw($_POST['url'] ?? '');

		if ( $passed_id <= 0 ) {
			$this->send_safe_error('Некоректний ID запису.', 400);
		}

		global $wpdb;

		// РОЗУМНИЙ ПОШУК ID
		// 1. Припускаємо, що нам прислали прямий UserHikingCategory_ID
		$target_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT UserHikingCategory_ID FROM UserHikingCategory WHERE UserHikingCategory_ID = %d AND User_ID = %d", $passed_id, $profile_user_id ) );

		// 2. Якщо не знайдено, значить прислали Calendar_ID. Шукаємо зв'язок через в'юху!
		if ( ! $target_id ) {
			$sql_cal = "SELECT h.UserHikingCategory_ID FROM vUserHikingCategory h JOIN UserCalendar uc ON h.UserCalendar_ID = uc.UserCalendar_ID WHERE uc.Calendar_ID = %d AND h.User_ID = %d LIMIT 1";
			$target_id = (int) $wpdb->get_var( $wpdb->prepare( $sql_cal, $passed_id, $profile_user_id ) );
		}

		if ( ! $target_id ) {
			$this->send_safe_error('Помилка: неможливо знайти похід у базі даних.', 400);
		}

		// Дістаємо назву походу для Протоколу
		$sql_name = "SELECT c.Calendar_Name FROM UserHikingCategory u LEFT JOIN UserCalendar uc ON u.UserCalendar_ID = uc.UserCalendar_ID LEFT JOIN Calendar c ON uc.Calendar_ID = c.Calendar_ID WHERE u.UserHikingCategory_ID = %d";
		$event_name = $wpdb->get_var($wpdb->prepare($sql_name, $target_id)) ?: "ID $target_id";

		$wpdb->query('START TRANSACTION');
		try {
			// Оновлюємо посилання за точним цільовим ID
			$updated = $wpdb->update('UserHikingCategory', 
				[ 'UserHikingCategory_UrlDivodka' => $url ], 
				[ 'UserHikingCategory_ID' => $target_id ]
			);

			if ( false === $updated ) { throw new \Exception('Помилка оновлення БД.'); }

			$this->service->get_protocol_service()->log_action_for_user( 
				get_current_user_id(), 'U', 
				"Оновлено довідку за похід: \"$event_name\" (профіль ID $profile_user_id)", '✓' 
			);

			$wpdb->query('COMMIT');
			wp_send_json_success(['message' => 'Посилання успішно оновлено.']);
		} catch (\Exception $e) {
			$wpdb->query('ROLLBACK');
			$this->send_safe_error('Помилка збереження в базу даних.', 500);
		}
	}
	public function handle_get_all_ranks(): void {
		$this->verify_nonce();
		global $wpdb;
		$wpdb->suppress_errors(true);
		$results = $wpdb->get_results( "SELECT SportsCategories_ID as id, SportsCategories_Name as name FROM S_SportsCategories ORDER BY SportsCategories_Order ASC", ARRAY_A );
		wp_send_json_success( $results ?: [] );
	}
	// Цей метод підтримує видалення розрядів за різними типами ідентифікаторів: UserSportCategories_ID, Calendar_ID, SportsCategories_ID або навіть текстовою назвою розряду.
	public function handle_add_rank(): void {
		$this->verify_nonce();
		$this->assert_authenticated();
		$profile_user_id = $this->sanitize_profile_user_id();

		$is_owner  = $profile_user_id === get_current_user_id();
		$can_manage = ! empty( \FSTU\Core\Capabilities::get_personal_cabinet_permissions( $profile_user_id )['canManageRanks'] );
		if ( ! $is_owner && ! $can_manage && ! current_user_can('administrator') && ! current_user_can('globalregistrar') ) {
			$this->send_safe_error('Немає прав.', 403);
		}

		$rank_id = absint($_POST['rank_id'] ?? 0);
		$tourism_id = absint($_POST['tourism_id'] ?? 0);
		$prikaz_num = sanitize_text_field(wp_unslash($_POST['prikaz_num'] ?? ''));
		$prikaz_date = sanitize_text_field(wp_unslash($_POST['prikaz_date'] ?? ''));
		$prikaz_url = esc_url_raw($_POST['prikaz_url'] ?? '');

		if ( $rank_id <= 0 ) { $this->send_safe_error('Некоректний розряд.', 400); }

		global $wpdb;
		
		// УВІМКНЕМО ВИВЕДЕННЯ РЕАЛЬНИХ ПОМИЛОК MySQL
		$wpdb->show_errors(true);

		$rank_name = $wpdb->get_var($wpdb->prepare("SELECT SportsCategories_Name FROM S_SportsCategories WHERE SportsCategories_ID = %d", $rank_id)) ?: "ID $rank_id";

		$data = [
			'User_ID' => $profile_user_id,
			'SportsCategories_ID' => $rank_id,
			'UserSportCategories_DateCreate' => current_time('mysql'),
			'UserCreate' => get_current_user_id(),
			'UserSportCategories_PrikazNumber' => $prikaz_num,
			'UserSportCategories_UrlPrikaz' => $prikaz_url,
		];
		if ($prikaz_date) { $data['UserSportCategories_DatePrikaz'] = $prikaz_date; }
		if ($tourism_id > 0) { $data['TourismType_ID'] = $tourism_id; }

		// Вставляємо без транзакцій, щоб уникнути конфліктів рушіїв InnoDB/MyISAM
		$inserted = $wpdb->insert('UserSportCategories', $data);
		
		if ( false === $inserted ) { 
			// Віддаємо точну помилку бази прямо в червоний алерт!
			$this->send_safe_error('Помилка БД: ' . $wpdb->last_error, 500); 
		}

		$this->service->get_protocol_service()->log_action_for_user(
			get_current_user_id(), 'I', "Додано спортивний розряд: \"$rank_name\" (профіль ID $profile_user_id)", '✓'
		);

		wp_send_json_success(['message' => 'Розряд успішно додано.']);
	}
	// Цей метод підтримує видалення розрядів за різними типами ідентифікаторів: UserSportCategories_ID, Calendar_ID, SportsCategories_ID або навіть текстовою назвою розряду.
	public function handle_delete_rank(): void {
		$this->verify_nonce();
		$this->assert_authenticated();
		$profile_user_id = $this->sanitize_profile_user_id();

		$is_owner  = $profile_user_id === get_current_user_id();
		$can_manage = ! empty( \FSTU\Core\Capabilities::get_personal_cabinet_permissions( $profile_user_id )['canManageRanks'] );
		if ( ! $is_owner && ! $can_manage && ! current_user_can('administrator') && ! current_user_can('globalregistrar') ) {
			$this->send_safe_error('Немає прав.', 403);
		}

		$identifier = sanitize_text_field( wp_unslash( $_POST['rank_id'] ?? '' ) );
		$passed_id  = absint( $identifier );
		global $wpdb;

		$target_id = 0;
		
		// 1. Шукаємо за точним UserSportCategories_ID
		if ( $passed_id > 0 ) {
			$target_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT UserSportCategories_ID FROM UserSportCategories WHERE UserSportCategories_ID = %d AND User_ID = %d", $passed_id, $profile_user_id ) );
		}
		
		// 2. Якщо це Calendar_ID (розряд отриманий за змагання)
		if ( ! $target_id && $passed_id > 0 ) {
			$sql_cal = "SELECT s.UserSportCategories_ID FROM vUserSportCategories s JOIN UserCalendar u ON s.UserCalendar_ID = u.UserCalendar_ID WHERE u.Calendar_ID = %d AND s.User_ID = %d LIMIT 1";
			$target_id = (int) $wpdb->get_var( $wpdb->prepare( $sql_cal, $passed_id, $profile_user_id ) );
		}

		// 3. Якщо це SportsCategories_ID (ID самого розряду з довідника)
		if ( ! $target_id && $passed_id > 0 ) {
			$target_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT UserSportCategories_ID FROM UserSportCategories WHERE SportsCategories_ID = %d AND User_ID = %d LIMIT 1", $passed_id, $profile_user_id ) );
		}

		// 4. Fallback по текстовій назві розряду (наприклад, "КМСУ")
		if ( ! $target_id && '' !== $identifier ) {
			$sql_name = "SELECT usc.UserSportCategories_ID FROM UserSportCategories usc JOIN S_SportsCategories sc ON usc.SportsCategories_ID = sc.SportsCategories_ID WHERE sc.SportsCategories_Name = %s AND usc.User_ID = %d LIMIT 1";
			$target_id = (int) $wpdb->get_var( $wpdb->prepare( $sql_name, $identifier, $profile_user_id ) );
		}

		if ( ! $target_id ) {
			$this->send_safe_error('Запис не знайдено у базі.', 400);
		}

		$rank_id = (int) $wpdb->get_var( $wpdb->prepare("SELECT SportsCategories_ID FROM UserSportCategories WHERE UserSportCategories_ID = %d", $target_id) );
		$rank_name = $wpdb->get_var($wpdb->prepare("SELECT SportsCategories_Name FROM S_SportsCategories WHERE SportsCategories_ID = %d", $rank_id)) ?: "ID $rank_id";
		
		$wpdb->query('START TRANSACTION');
		try {
			$deleted = $wpdb->delete('UserSportCategories', ['UserSportCategories_ID' => $target_id]);
			if ($deleted) {
				$this->service->get_protocol_service()->log_action_for_user(get_current_user_id(), 'D', "Видалено спортивний розряд: \"$rank_name\" (профіль ID $profile_user_id)", '✓');
			}
			$wpdb->query('COMMIT');
			wp_send_json_success(['message' => 'Розряд видалено.']);
		} catch (\Exception $e) {
			$wpdb->query('ROLLBACK');
			$this->send_safe_error('Помилка видалення', 500);
		}
	}
	// Цей метод підтримує видалення категорій за різними типами ідентифікаторів: Referee_ID, Calendar_ID, RefereeCategory_ID або навіть текстовою назвою категорії.
	public function handle_get_all_referee_categories(): void {
		$this->verify_nonce();
		global $wpdb;
		$wpdb->suppress_errors(true);
		$results = $wpdb->get_results( "SELECT RefereeCategory_ID as id, RefereeCategory_Name as name FROM vRefereeCategory ORDER BY RefereeCategory_Order ASC", ARRAY_A );
		wp_send_json_success( $results ?: [] );
	}
	// Цей метод підтримує видалення категорій за різними типами ідентифікаторів: Referee_ID, Calendar_ID, RefereeCategory_ID або навіть текстовою назвою категорії.
	public function handle_add_judging(): void {
		$this->verify_nonce();
		$this->assert_authenticated();
		$profile_user_id = $this->sanitize_profile_user_id();

		$is_owner  = $profile_user_id === get_current_user_id();
		$can_manage = ! empty( \FSTU\Core\Capabilities::get_personal_cabinet_permissions( $profile_user_id )['canManageJudging'] );
		if ( ! $is_owner && ! $can_manage && ! current_user_can('administrator') && ! current_user_can('globalregistrar') ) {
			$this->send_safe_error('Немає прав.', 403);
		}

		$category_id = absint($_POST['category_id'] ?? 0);

		if ( $category_id <= 0 ) { $this->send_safe_error('Некоректна категорія.', 400); }

		global $wpdb;
		$wpdb->suppress_errors(true);

		$category_name = $wpdb->get_var($wpdb->prepare("SELECT RefereeCategory_Name FROM vRefereeCategory WHERE RefereeCategory_ID = %d", $category_id)) ?: "ID $category_id";

		// Вставляємо ВИКЛЮЧНО ті поля, які є у старій базі даних
		$data = [
			'User_ID'            => $profile_user_id,
			'RefereeCategory_ID' => $category_id,
			'Referee_DateCreate' => current_time('mysql'),
		];

		$inserted = $wpdb->insert('Referee', $data);
		
		if ( false === $inserted ) { 
			$this->send_safe_error('Помилка БД: ' . $wpdb->last_error, 500); 
		}

		$this->service->get_protocol_service()->log_action_for_user(
			get_current_user_id(), 'I', "Додано суддівську категорію: \"$category_name\" (профіль ID $profile_user_id)", '✓'
		);

		wp_send_json_success(['message' => 'Суддівську категорію успішно додано.']);
	}
	// Цей метод підтримує видалення категорій за різними типами ідентифікаторів: Referee_ID, Calendar_ID, RefereeCategory_ID або навіть текстовою назвою категорії.
	public function handle_delete_judging(): void {
		$this->verify_nonce();
		$this->assert_authenticated();
		$profile_user_id = $this->sanitize_profile_user_id();

		$is_owner  = $profile_user_id === get_current_user_id();
		$can_manage = ! empty( \FSTU\Core\Capabilities::get_personal_cabinet_permissions( $profile_user_id )['canManageJudging'] );
		if ( ! $is_owner && ! $can_manage && ! current_user_can('administrator') && ! current_user_can('globalregistrar') ) {
			$this->send_safe_error('Немає прав.', 403);
		}

		$passed_id = absint( $_POST['judging_id'] ?? 0 );
		global $wpdb;

		if ($passed_id > 0) {
			$target_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT Referee_ID FROM Referee WHERE Referee_ID = %d AND User_ID = %d", $passed_id, $profile_user_id ) );
			if (!$target_id) { $this->send_safe_error('Запис не знайдено.', 400); }
			
			$cat_id = (int) $wpdb->get_var( $wpdb->prepare("SELECT RefereeCategory_ID FROM Referee WHERE Referee_ID = %d", $target_id) );
			$cat_name = $wpdb->get_var($wpdb->prepare("SELECT RefereeCategory_Name FROM vRefereeCategory WHERE RefereeCategory_ID = %d", $cat_id)) ?: "ID $cat_id";
			
			$wpdb->query('START TRANSACTION');
			try {
				$deleted = $wpdb->delete('Referee', ['Referee_ID' => $target_id]);
				if ($deleted) {
					$this->service->get_protocol_service()->log_action_for_user(get_current_user_id(), 'D', "Видалено суддівську категорію: \"$cat_name\" (профіль ID $profile_user_id)", '✓');
				}
				$wpdb->query('COMMIT');
				wp_send_json_success(['message' => 'Категорію видалено.']);
			} catch (\Exception $e) {
				$wpdb->query('ROLLBACK');
				$this->send_safe_error('Помилка видалення', 500);
			}
		} else {
			$this->send_safe_error('Некоректний ID', 400);
		}
	}
    public function handle_add_sail_dues(): void {
        $this->verify_nonce();
        $this->assert_authenticated();
        $profile_user_id = $this->sanitize_profile_user_id();

        // Використовуємо системну перевірку прав з вашого class-capabilities.php
        if ( ! current_user_can( \FSTU\Core\Capabilities::MANAGE_PERSONAL_SAIL_DUES ) ) {
            $this->send_safe_error('Немає прав для додавання оплати.', 403);
        }

        $year = absint($_POST['year'] ?? 0);
        $summa = floatval($_POST['summa'] ?? 0);

        if ( $year < 2000 || $year > ((int)date('Y') + 1) ) {
            $this->send_safe_error('Некоректний рік.', 400);
        }

        global $wpdb;
        $wpdb->suppress_errors(true);

        // Перевіряємо, чи немає вже оплати за цей рік (щоб не було дублів)
        $exists = $wpdb->get_var($wpdb->prepare("SELECT DuesSail_ID FROM DuesSail WHERE User_ID = %d AND Year_ID = %d", $profile_user_id, $year));
        if ( $exists ) {
            $this->send_safe_error("Внесок за $year рік вже існує.", 400);
        }

        $data = [
            'User_ID'             => $profile_user_id,
            'Year_ID'             => $year,
            'DuesSail_Summa'      => $summa,
            'DuesSail_DateCreate' => current_time('mysql'),
            'UserCreate'          => get_current_user_id(),
        ];

        $inserted = $wpdb->insert('DuesSail', $data);

        if ( false === $inserted ) {
            $this->send_safe_error('Помилка збереження в БД: ' . $wpdb->last_error, 500);
        }

        // Логування в протокол
        $this->service->get_protocol_service()->log_action_for_user(
            get_current_user_id(), 'I', "Додано оплату вітрильних внесків за $year рік на суму $summa грн (профіль ID $profile_user_id)", '✓'
        );

        wp_send_json_success(['message' => 'Оплату успішно додано.']);
    }
	//-----------------
}