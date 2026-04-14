<?php
namespace FSTU\Modules\Calendar\CalendarApplications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use FSTU\Modules\Calendar\CalendarApplications\Calendar_Applications_Protocol_Service;
use FSTU\Modules\Calendar\Calendar_List;

/**
 * Сервіс бізнес-логіки підмодуля Calendar_Applications.
 *
 * Version: 1.5.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarApplications
 */
class Calendar_Applications_Service {

	private const META_KEYS_ALLOWED = [ 'Patronymic', 'BirthDate', 'Sex', 'Phone', 'PhoneMobile' ];

	private Calendar_Applications_Repository $repository;
	private Calendar_Applications_Protocol_Service $protocol_service;
	private Calendar_Applications_Mailer $mailer;

	public function __construct( ?Calendar_Applications_Repository $repository = null, ?Calendar_Applications_Protocol_Service $protocol_service = null, ?Calendar_Applications_Mailer $mailer = null ) {
		$this->repository       = $repository instanceof Calendar_Applications_Repository ? $repository : new Calendar_Applications_Repository();
		$this->protocol_service = $protocol_service instanceof Calendar_Applications_Protocol_Service ? $protocol_service : new Calendar_Applications_Protocol_Service( $this->repository );
		$this->mailer           = $mailer instanceof Calendar_Applications_Mailer ? $mailer : new Calendar_Applications_Mailer( $this->repository );
	}

	/**
	 * Створює заявку.
	 *
	 * @param array<string, mixed> $data Дані заявки.
	 * @return array{success: bool, message: string, application_id?: int, code?: string}
	 */
	public function create_application( array $data ): array {
		$data['status_app_id'] = $this->repository->get_default_status_id();
		if ( (int) $data['status_app_id'] <= 0 ) {
			return [ 'success' => false, 'message' => 'Не вдалося визначити стартовий статус заявки.', 'code' => 'default_status_missing' ];
		}

		$this->repository->begin_transaction();

		$application_id = $this->repository->insert_application( $data );
		if ( $application_id <= 0 ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка збереження заявки.', 'code' => 'insert_failed' ];
		}

		$log_ok = $this->repository->insert_log(
			$this->protocol_service->get_log_name(),
			'I',
			sprintf( 'Додано заявку: %s', (string) $data['app_name'] ),
			'✓'
		);

		if ( ! $log_ok ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка збереження протоколу. Операцію скасовано.', 'code' => 'log_insert_failed' ];
		}

		$this->repository->commit();

		return [ 'success' => true, 'message' => 'Заявку успішно додано.', 'application_id' => $application_id ];
	}

	/**
	 * Оновлює заявку.
	 *
	 * @param array<string, mixed> $data Дані заявки.
	 * @return array{success: bool, message: string, code?: string}
	 */
	public function update_application( int $application_id, array $data, int $current_user_id, bool $can_manage_any ): array {
		$application = $this->repository->get_application( $application_id );
		if ( ! is_array( $application ) ) {
			return [ 'success' => false, 'message' => 'Заявку не знайдено.', 'code' => 'application_not_found' ];
		}

		$permission = $this->assert_application_edit_permission( $application, $current_user_id, $can_manage_any );
		if ( true !== $permission['success'] ) {
			return $permission;
		}

		$data['status_app_id'] = (int) ( $application['StatusApp_ID'] ?? 0 );

		$this->repository->begin_transaction();
		$updated = $this->repository->update_application( $application_id, $data );
		if ( ! $updated ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка збереження заявки.', 'code' => 'update_failed' ];
		}

		$log_ok = $this->repository->insert_log(
			$this->protocol_service->get_log_name(),
			'U',
			sprintf( 'Оновлено заявку: %s', (string) $data['app_name'] ),
			'✓'
		);

		if ( ! $log_ok ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка збереження протоколу. Операцію скасовано.', 'code' => 'log_update_failed' ];
		}

		$this->repository->commit();

		return [ 'success' => true, 'message' => 'Заявку успішно оновлено.' ];
	}

	/**
	 * Змінює статус заявки за MVP review-flow policy.
	 *
	 * @return array{success: bool, message: string, code?: string, status_app_id?: int, status_name?: string}
	 */
	public function change_application_status( int $application_id, int $target_status_id, int $current_user_id, bool $can_manage_any ): array {
		$application = $this->repository->get_application( $application_id );
		if ( ! is_array( $application ) ) {
			return [ 'success' => false, 'message' => 'Заявку не знайдено.', 'code' => 'application_not_found' ];
		}

		$owner_id          = (int) ( $application['UserCreate'] ?? 0 );
		$current_status_id = (int) ( $application['StatusApp_ID'] ?? 0 );
		$status_map        = $this->repository->get_application_status_flow_map();
		$is_owner          = $owner_id > 0 && $owner_id === $current_user_id;

		if ( $target_status_id <= 0 || '' === $this->repository->get_status_name_by_id( $target_status_id ) ) {
			return [ 'success' => false, 'message' => 'Оберіть коректний цільовий статус заявки.', 'code' => 'invalid_target_status' ];
		}

		if ( $current_status_id === $target_status_id ) {
			return [ 'success' => false, 'message' => 'Заявка вже перебуває у вибраному статусі.', 'code' => 'status_unchanged' ];
		}

		if ( $status_map['approved'] > 0 && $current_status_id === $status_map['approved'] ) {
			return [ 'success' => false, 'message' => 'Підтверджену заявку не можна переводити в інший статус у межах MVP policy.', 'code' => 'approved_locked' ];
		}

		$owner_allowed =
			$is_owner
			&& (
				( $status_map['draft'] > 0 && $status_map['under_review'] > 0 && $current_status_id === $status_map['draft'] && $target_status_id === $status_map['under_review'] )
				|| ( $status_map['needs_fixes'] > 0 && $status_map['draft'] > 0 && $current_status_id === $status_map['needs_fixes'] && $target_status_id === $status_map['draft'] )
			);

		$staff_allowed =
			$can_manage_any
			&& (
				( $status_map['under_review'] > 0 && $status_map['approved'] > 0 && $current_status_id === $status_map['under_review'] && $target_status_id === $status_map['approved'] )
				|| ( $status_map['under_review'] > 0 && $status_map['needs_fixes'] > 0 && $current_status_id === $status_map['under_review'] && $target_status_id === $status_map['needs_fixes'] )
			);

		if ( ! $is_owner && ! $can_manage_any ) {
			return [ 'success' => false, 'message' => 'Недостатньо прав для зміни статусу цієї заявки.', 'code' => 'forbidden' ];
		}

		if ( ! $owner_allowed && ! $staff_allowed ) {
			return [ 'success' => false, 'message' => 'Такий перехід статусу не дозволений поточною policy заявки.', 'code' => 'invalid_transition' ];
		}

		$current_status_name = $this->repository->get_status_name_by_id( $current_status_id );
		$target_status_name  = $this->repository->get_status_name_by_id( $target_status_id );

		$this->repository->begin_transaction();
		$updated = $this->repository->update_application_status( $application_id, $target_status_id );
		if ( ! $updated ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка зміни статусу заявки.', 'code' => 'status_update_failed' ];
		}

		$log_ok = $this->repository->insert_log(
			$this->protocol_service->get_log_name(),
			'U',
			sprintf(
				'Змінено статус заявки %s: %s → %s',
				(string) ( $application['App_Name'] ?: $application['Sailboat_Name'] ?: $application_id ),
				'' !== $current_status_name ? $current_status_name : (string) $current_status_id,
				'' !== $target_status_name ? $target_status_name : (string) $target_status_id
			),
			'✓'
		);

		if ( ! $log_ok ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка збереження протоколу. Операцію скасовано.', 'code' => 'log_status_update_failed' ];
		}

		$this->repository->commit();

		$email_sent = null;
		if ( $status_map['under_review'] > 0 && $target_status_id === $status_map['under_review'] ) {
			$email_sent = $this->mailer->send_application_submitted_email(
				[
					'application_name' => (string) ( $application['App_Name'] ?: $application['Sailboat_Name'] ?: $application_id ),
					'owner_name'       => (string) ( $application['Creator_FullName'] ?? '' ),
					'status_name'      => $target_status_name,
					'module_url'       => Calendar_List::get_module_url(),
				]
			);
		} elseif ( ( $status_map['approved'] > 0 && $target_status_id === $status_map['approved'] ) || ( $status_map['needs_fixes'] > 0 && $target_status_id === $status_map['needs_fixes'] ) ) {
			$owner_user  = $owner_id > 0 ? get_userdata( $owner_id ) : false;
			$email_sent = $owner_user instanceof \WP_User
				? $this->mailer->send_application_status_changed_email(
					[
						'owner_email'           => $owner_user->user_email,
						'application_name'      => (string) ( $application['App_Name'] ?: $application['Sailboat_Name'] ?: $application_id ),
						'previous_status_name'  => $current_status_name,
						'status_name'           => $target_status_name,
						'module_url'            => Calendar_List::get_module_url(),
					]
				)
				: false;
		}

		$message = 'Статус заявки успішно змінено.';
		if ( false === $email_sent ) {
			$message .= ' Операцію виконано, але email-сповіщення не вдалося доставити.';
		}

		return [
			'success'       => true,
			'message'       => $message,
			'status_app_id' => $target_status_id,
			'status_name'   => $target_status_name,
			'email_sent'    => $email_sent,
		];
	}

	/**
	 * Додає існуючого користувача до заявки.
	 *
	 * @return array{success: bool, message: string, code?: string, usercalendar_id?: int}
	 */
	public function add_participant( int $application_id, int $user_id, int $participation_type_id, int $current_user_id, bool $can_manage_any ): array {
		$application_context = $this->repository->get_application_context( $application_id );
		if ( ! is_array( $application_context ) ) {
			return [ 'success' => false, 'message' => 'Заявку не знайдено.', 'code' => 'application_not_found' ];
		}

		$permission = $this->assert_participant_manage_permission( $application_context, $current_user_id, $can_manage_any );
		if ( true !== $permission['success'] ) {
			return $permission;
		}

		if ( $user_id <= 0 || ! get_userdata( $user_id ) instanceof \WP_User ) {
			return [ 'success' => false, 'message' => 'Оберіть коректного користувача для додавання.', 'code' => 'invalid_user' ];
		}

		if ( ! $this->repository->participation_type_exists( $participation_type_id ) ) {
			return [ 'success' => false, 'message' => 'Оберіть коректний тип участі.', 'code' => 'invalid_participation_type' ];
		}

		if ( $this->repository->participant_exists( $application_id, $user_id ) ) {
			return [ 'success' => false, 'message' => 'Цей учасник уже доданий до заявки.', 'code' => 'participant_exists' ];
		}

		$this->repository->begin_transaction();
		$usercalendar_id = $this->repository->insert_application_participant( $application_id, (int) ( $application_context['Calendar_ID'] ?? 0 ), $user_id, $participation_type_id );
		if ( $usercalendar_id <= 0 ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка додавання учасника до заявки.', 'code' => 'participant_insert_failed' ];
		}

		$user = get_userdata( $user_id );
		$log_ok = $this->repository->insert_log(
			$this->protocol_service->get_log_name(),
			'U',
			sprintf( 'Додано учасника %s до заявки %s.', $user instanceof \WP_User ? $user->display_name : (string) $user_id, (string) ( $application_context['App_Name'] ?: $application_id ) ),
			'✓'
		);

		if ( ! $log_ok ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка збереження протоколу. Операцію скасовано.', 'code' => 'log_add_participant_failed' ];
		}

		$this->repository->commit();

		return [ 'success' => true, 'message' => 'Учасника успішно додано до заявки.', 'usercalendar_id' => $usercalendar_id ];
	}

	/**
	 * Видаляє учасника із заявки.
	 *
	 * @return array{success: bool, message: string, code?: string}
	 */
	public function remove_participant( int $usercalendar_id, int $current_user_id, bool $can_manage_any ): array {
		$link = $this->repository->get_participant_link( $usercalendar_id );
		if ( ! is_array( $link ) ) {
			return [ 'success' => false, 'message' => 'Учасника заявки не знайдено.', 'code' => 'participant_not_found' ];
		}

		$permission = $this->assert_participant_manage_permission(
			[
				'UserCreate'    => (int) ( $link['Application_UserCreate'] ?? 0 ),
				'StatusApp_ID'  => (int) ( $link['StatusApp_ID'] ?? 0 ),
				'App_Name'      => (string) ( $link['App_Name'] ?? '' ),
			],
			$current_user_id,
			$can_manage_any
		);
		if ( true !== $permission['success'] ) {
			return $permission;
		}

		$this->repository->begin_transaction();
		$deleted = $this->repository->delete_application_participant( $usercalendar_id );
		if ( ! $deleted ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка видалення учасника із заявки.', 'code' => 'participant_delete_failed' ];
		}

		$log_ok = $this->repository->insert_log(
			$this->protocol_service->get_log_name(),
			'U',
			sprintf( 'Видалено учасника %s із заявки %s.', (string) ( $link['Participant_FullName'] ?: $link['User_ID'] ), (string) ( $link['App_Name'] ?: ( $link['application_id'] ?? '' ) ) ),
			'✓'
		);

		if ( ! $log_ok ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка збереження протоколу. Операцію скасовано.', 'code' => 'log_remove_participant_failed' ];
		}

		$this->repository->commit();

		return [ 'success' => true, 'message' => 'Учасника успішно видалено із заявки.' ];
	}

	/**
	 * Створює нового користувача-учасника і одразу додає його до заявки.
	 *
	 * @param array<string,mixed> $data
	 * @return array{success: bool, message: string, code?: string, user_id?: int, usercalendar_id?: int}
	 */
	public function create_participant_user( int $application_id, array $data, int $current_user_id, bool $can_manage_any ): array {
		$application_context = $this->repository->get_application_context( $application_id );
		if ( ! is_array( $application_context ) ) {
			return [ 'success' => false, 'message' => 'Заявку не знайдено.', 'code' => 'application_not_found' ];
		}

		$permission = $this->assert_participant_manage_permission( $application_context, $current_user_id, $can_manage_any );
		if ( true !== $permission['success'] ) {
			return $permission;
		}

		$validation_error = $this->validate_new_participant_data( $data );
		if ( '' !== $validation_error ) {
			return [ 'success' => false, 'message' => $validation_error, 'code' => 'invalid_participant_data' ];
		}

		$email = (string) ( $data['email'] ?? '' );
		if ( email_exists( $email ) ) {
			return [ 'success' => false, 'message' => 'Користувач з таким email уже існує. Додайте його як існуючого учасника.', 'code' => 'email_exists' ];
		}

		if ( ! $this->repository->participation_type_exists( (int) $data['participation_type_id'] ) ) {
			return [ 'success' => false, 'message' => 'Оберіть коректний тип участі.', 'code' => 'invalid_participation_type' ];
		}

		$password       = wp_generate_password( 12, true, true );
		$login          = $this->generate_unique_user_login( $email );
		$applicant_role = get_role( 'applicants' ) instanceof \WP_Role ? 'applicants' : get_option( 'default_role', 'subscriber' );
		$display_name   = trim( implode( ' ', array_filter( [ (string) $data['last_name'], (string) $data['first_name'], (string) $data['patronymic'] ] ) ) );

		$this->repository->begin_transaction();

		$user_id = wp_insert_user(
			[
				'user_login'   => $login,
				'user_pass'    => $password,
				'user_email'   => $email,
				'role'         => $applicant_role,
				'display_name' => '' !== $display_name ? $display_name : $login,
				'first_name'   => (string) $data['first_name'],
				'last_name'    => (string) $data['last_name'],
			]
		);

		if ( is_wp_error( $user_id ) || $user_id <= 0 ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка створення нового учасника.', 'code' => 'user_insert_failed' ];
		}

		update_user_meta( $user_id, 'last_name', (string) $data['last_name'] );
		update_user_meta( $user_id, 'first_name', (string) $data['first_name'] );
		foreach ( self::META_KEYS_ALLOWED as $meta_key ) {
			if ( isset( $data[ $meta_key ] ) && '' !== trim( (string) $data[ $meta_key ] ) ) {
				update_user_meta( $user_id, $meta_key, (string) $data[ $meta_key ] );
			}
		}

		if ( ! empty( $data['phone'] ) ) {
			update_user_meta( $user_id, 'PhoneMobile', (string) $data['phone'] );
		}

		$city_id         = (int) ( $data['city_id'] ?? 0 );
		$tourism_type_id = (int) ( $application_context['TourismType_ID'] ?? 0 );
		$region_id       = (int) ( $application_context['Application_Region_ID'] ?? 0 );
		if ( $region_id <= 0 ) {
			$region_id = (int) ( $application_context['Event_Region_ID'] ?? 0 );
		}

		if ( $city_id > 0 && ! $this->repository->insert_user_city( $user_id, $city_id ) ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка збереження міста учасника.', 'code' => 'user_city_insert_failed' ];
		}

		if ( $tourism_type_id > 0 && ! $this->repository->user_has_tourism_type( $user_id, $tourism_type_id ) && ! $this->repository->insert_user_tourism_type( $user_id, $tourism_type_id ) ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка збереження виду туризму учасника.', 'code' => 'user_tourism_insert_failed' ];
		}

		$unit_id = (int) ( $data['unit_id'] ?? 0 );
		if ( $region_id > 0 && $unit_id > 0 && ! $this->repository->insert_user_registration_ofst( $user_id, $region_id, $unit_id ) ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка збереження реєстрації учасника в ОФСТ.', 'code' => 'user_ofst_insert_failed' ];
		}

		if ( $region_id > 0 && ! $this->repository->user_has_member_card( $user_id ) ) {
			$card_number = $this->repository->get_next_member_card_number( $region_id );
			if ( $card_number <= 0 || ! $this->repository->create_member_card( $user_id, $region_id, $card_number, (string) $data['last_name'], (string) $data['first_name'] ) ) {
				$this->repository->rollback();

				return [ 'success' => false, 'message' => 'Сталася помилка створення членського квитка учасника.', 'code' => 'member_card_insert_failed' ];
			}
		}

		$usercalendar_id = $this->repository->insert_application_participant( $application_id, (int) ( $application_context['Calendar_ID'] ?? 0 ), $user_id, (int) $data['participation_type_id'] );
		if ( $usercalendar_id <= 0 ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка прив’язки нового учасника до заявки.', 'code' => 'participant_insert_failed' ];
		}

		$log_ok = $this->repository->insert_log(
			$this->protocol_service->get_log_name(),
			'I',
			sprintf( 'Створено нового учасника %s і додано до заявки %s.', $display_name, (string) ( $application_context['App_Name'] ?: $application_id ) ),
			'✓'
		);

		if ( ! $log_ok ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка збереження протоколу. Операцію скасовано.', 'code' => 'log_create_participant_failed' ];
		}

		$this->repository->commit();

		$email_sent = $this->mailer->send_new_participant_account_email(
			[
				'email'            => $email,
				'login'            => $login,
				'password'         => $password,
				'last_name'        => (string) $data['last_name'],
				'first_name'       => (string) $data['first_name'],
				'patronymic'       => (string) ( $data['patronymic'] ?? '' ),
				'application_name' => (string) ( $application_context['App_Name'] ?: $application_id ),
				'module_url'       => Calendar_List::get_module_url(),
			]
		);

		$message = 'Нового учасника успішно створено і додано до заявки.';
		if ( ! $email_sent ) {
			$message .= ' Обліковий запис створено, але email із доступом не вдалося доставити.';
		}

		return [
			'success'        => true,
			'message'        => $message,
			'user_id'        => $user_id,
			'usercalendar_id'=> $usercalendar_id,
			'email_sent'     => $email_sent,
		];
	}

	/**
	 * Видаляє заявку з dependency-safe policy.
	 *
	 * @return array{success: bool, message: string, code?: string, dependencies?: array<string,int>}
	 */
	public function delete_application( int $application_id, int $current_user_id, bool $can_delete_any ): array {
		$application = $this->repository->get_application( $application_id );
		if ( ! is_array( $application ) ) {
			return [ 'success' => false, 'message' => 'Заявку не знайдено.', 'code' => 'application_not_found' ];
		}

		$owner_id = (int) ( $application['UserCreate'] ?? 0 );
		if ( ! $can_delete_any && $owner_id !== $current_user_id ) {
			return [ 'success' => false, 'message' => 'Недостатньо прав для видалення цієї заявки.', 'code' => 'forbidden' ];
		}

		$dependencies = $this->repository->get_application_dependency_counts( $application_id, (int) ( $application['Calendar_ID'] ?? 0 ) );
		if ( array_sum( $dependencies ) > 0 ) {
			$this->repository->insert_log(
				$this->protocol_service->get_log_name(),
				'D',
				sprintf( 'Заблоковано видалення заявки: %s', (string) ( $application['App_Name'] ?: $application['Sailboat_Name'] ?: $application_id ) ),
				'dependency'
			);

			return [
				'success'      => false,
				'message'      => 'Видалення заблоковано: у заявки є пов’язані учасники або інші залежності.',
				'code'         => 'dependency',
				'dependencies' => $dependencies,
			];
		}

		$this->repository->begin_transaction();
		$deleted = $this->repository->delete_application( $application_id );
		if ( ! $deleted ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка видалення заявки.', 'code' => 'delete_failed' ];
		}

		$log_ok = $this->repository->insert_log(
			$this->protocol_service->get_log_name(),
			'D',
			sprintf( 'Видалено заявку: %s', (string) ( $application['App_Name'] ?: $application['Sailboat_Name'] ?: $application_id ) ),
			'✓'
		);

		if ( ! $log_ok ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка збереження протоколу. Операцію скасовано.', 'code' => 'log_delete_failed' ];
		}

		$this->repository->commit();

		return [ 'success' => true, 'message' => 'Заявку успішно видалено.' ];
	}

	/**
	 * @param array<string, mixed> $application_context
	 * @return array{success: bool, message: string, code?: string}
	 */
	private function assert_participant_manage_permission( array $application_context, int $current_user_id, bool $can_manage_any ): array {
		$owner_id         = (int) ( $application_context['UserCreate'] ?? 0 );
		$status_app_id    = (int) ( $application_context['StatusApp_ID'] ?? 0 );
		$status_map       = $this->repository->get_application_status_flow_map();
		$is_owner         = $owner_id > 0 && $owner_id === $current_user_id;

		if ( ! $can_manage_any && ! $is_owner ) {
			return [ 'success' => false, 'message' => 'Недостатньо прав для керування учасниками цієї заявки.', 'code' => 'forbidden' ];
		}

		if ( $status_map['approved'] > 0 && $status_app_id === $status_map['approved'] ) {
			return [ 'success' => false, 'message' => 'Підтверджену заявку не можна змінювати у частині учасників.', 'code' => 'approved_locked' ];
		}

		if ( ! $can_manage_any && $status_map['under_review'] > 0 && $status_app_id === $status_map['under_review'] ) {
			return [ 'success' => false, 'message' => 'Поки заявка перебуває на розгляді, власник не може змінювати склад учасників.', 'code' => 'under_review_locked' ];
		}

		return [ 'success' => true, 'message' => '' ];
	}

	/**
	 * @param array<string, mixed> $application
	 * @return array{success: bool, message: string, code?: string}
	 */
	private function assert_application_edit_permission( array $application, int $current_user_id, bool $can_manage_any ): array {
		$owner_id      = (int) ( $application['UserCreate'] ?? 0 );
		$status_app_id = (int) ( $application['StatusApp_ID'] ?? 0 );
		$status_map    = $this->repository->get_application_status_flow_map();
		$is_owner      = $owner_id > 0 && $owner_id === $current_user_id;

		if ( ! $can_manage_any && ! $is_owner ) {
			return [ 'success' => false, 'message' => 'Недостатньо прав для редагування цієї заявки.', 'code' => 'forbidden' ];
		}

		if ( $status_map['approved'] > 0 && $status_app_id === $status_map['approved'] ) {
			return [ 'success' => false, 'message' => 'Підтверджену заявку не можна редагувати у межах MVP policy.', 'code' => 'approved_locked' ];
		}

		if ( ! $can_manage_any && $status_map['under_review'] > 0 && $status_app_id === $status_map['under_review'] ) {
			return [ 'success' => false, 'message' => 'Поки заявка перебуває на розгляді, власник не може її редагувати.', 'code' => 'under_review_locked' ];
		}

		return [ 'success' => true, 'message' => '' ];
	}

	/**
	 * @param array<string, mixed> $data
	 */
	private function validate_new_participant_data( array $data ): string {
		if ( '' === trim( (string) ( $data['last_name'] ?? '' ) ) || '' === trim( (string) ( $data['first_name'] ?? '' ) ) ) {
			return 'Вкажіть прізвище та ім’я нового учасника.';
		}

		if ( empty( $data['email'] ) || ! is_email( (string) $data['email'] ) ) {
			return 'Вкажіть коректний email нового учасника.';
		}

		if ( empty( $data['participation_type_id'] ) || (int) $data['participation_type_id'] <= 0 ) {
			return 'Оберіть тип участі нового учасника.';
		}

		if ( ! empty( $data['BirthDate'] ) && false === strtotime( (string) $data['BirthDate'] ) ) {
			return 'Вкажіть коректну дату народження нового учасника.';
		}

		return '';
	}

	private function generate_unique_user_login( string $email ): string {
		$base_login = sanitize_user( strstr( $email, '@', true ) ?: $email, true );
		$base_login = '' !== $base_login ? $base_login : 'fstu_user';
		$login      = $base_login;
		$suffix     = 1;

		while ( username_exists( $login ) ) {
			$login = $base_login . '_' . $suffix;
			$suffix++;
		}

		return $login;
	}
}

