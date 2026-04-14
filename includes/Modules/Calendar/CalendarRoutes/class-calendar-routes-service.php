<?php
namespace FSTU\Modules\Calendar\CalendarRoutes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use FSTU\Modules\Calendar\Calendar_List;

/**
 * Сервіс бізнес-логіки підмодуля Calendar_Routes.
 *
 * Version: 1.3.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarRoutes
 */
class Calendar_Routes_Service {

	private const STATUS_UNDER_REVIEW = 2;
	private const STATUS_APPROVED     = 3;
	private const STATUS_NEEDS_FIXES  = 4;

	private Calendar_Routes_Repository $repository;
	private Calendar_Routes_Protocol_Service $protocol_service;
	private Calendar_Routes_MKK_Service $mkk_service;
	private Calendar_Routes_Notification_Service $notification_service;

	public function __construct( ?Calendar_Routes_Repository $repository = null, ?Calendar_Routes_Protocol_Service $protocol_service = null, ?Calendar_Routes_MKK_Service $mkk_service = null, ?Calendar_Routes_Notification_Service $notification_service = null ) {
		$this->repository       = $repository instanceof Calendar_Routes_Repository ? $repository : new Calendar_Routes_Repository();
		$this->protocol_service = $protocol_service instanceof Calendar_Routes_Protocol_Service ? $protocol_service : new Calendar_Routes_Protocol_Service( $this->repository );
		$this->mkk_service      = $mkk_service instanceof Calendar_Routes_MKK_Service ? $mkk_service : new Calendar_Routes_MKK_Service( $this->repository );
		$this->notification_service = $notification_service instanceof Calendar_Routes_Notification_Service ? $notification_service : new Calendar_Routes_Notification_Service( $this->repository );
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function get_route_payload( int $calendar_id, int $current_user_id, bool $can_manage, bool $can_review_mkk ): ?array {
		$overview = $this->repository->get_route_overview( $calendar_id );
		if ( ! is_array( $overview ) ) {
			return null;
		}

		$owner_id           = (int) ( $overview['User_ID'] ?: $overview['UserCreate'] ?: 0 );
		$can_manage_context = $can_manage || ( $owner_id > 0 && $owner_id === $current_user_id );
		$mkk_context        = $this->mkk_service->get_reviewer_context( $calendar_id, $current_user_id, $can_review_mkk );
		$is_under_review    = self::STATUS_UNDER_REVIEW === (int) ( $overview['StatusApp_ID'] ?? 0 );
		$can_view_context   = $can_manage_context || ! empty( $mkk_context['allowed'] );

		if ( ! $can_view_context ) {
			return null;
		}

		$segments      = $this->repository->get_route_segments_all( $calendar_id );
		$verifications = $this->repository->get_verifications( $calendar_id );
		$apptravel     = $this->repository->get_apptravel( $calendar_id );
		$mutation_lock = $this->get_mutation_lock_state( $calendar_id );

		return [
			'overview'      => $overview,
			'apptravel'     => $apptravel,
			'segments'      => $segments,
			'verifications' => $verifications,
			'permissions'   => [
				'canView'         => true,
				'canManage'       => $can_manage_context && ! $mutation_lock['locked'],
				'canDelete'       => $can_manage_context && ! $mutation_lock['locked'],
				'canSendToMkk'    => $can_manage_context && (int) ( $overview['Segments_Count'] ?? 0 ) > 0 && self::STATUS_APPROVED !== (int) ( $overview['StatusApp_ID'] ?? 0 ),
				'canReviewMkk'    => $is_under_review && ! empty( $mkk_context['allowed'] ),
				'canFinalApprove' => $is_under_review && ! empty( $mkk_context['can_final_approve'] ),
				'isLocked'        => $mutation_lock['locked'],
				'lockReason'      => $mutation_lock['reason'],
			],
			'mkk_context'   => $mkk_context,
		];
	}

	/**
	 * @return array{success: bool, message: string, pathtrip_id?: int, code?: string}
	 */
	public function create_pathtrip( array $data, int $current_user_id, bool $can_manage ): array {
		$calendar_id = (int) ( $data['calendar_id'] ?? 0 );
		$ownership   = $this->assert_owner_or_manager( $calendar_id, $current_user_id, $can_manage );
		if ( true !== $ownership['success'] ) {
			return $ownership;
		}

		$mutation_lock = $this->get_mutation_lock_state( $calendar_id );
		if ( $mutation_lock['locked'] ) {
			return [ 'success' => false, 'message' => $mutation_lock['reason'], 'code' => 'route_locked' ];
		}

		$this->repository->begin_transaction();

		$pathtrip_id = $this->repository->insert_pathtrip( $data );
		if ( $pathtrip_id <= 0 ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка збереження ділянки маршруту.', 'code' => 'insert_failed' ];
		}

		$log_ok = $this->repository->insert_log(
			$this->protocol_service->get_log_name(),
			'I',
			sprintf( 'Додано ділянку маршруту для заходу #%d: %s', $calendar_id, (string) $data['pathtrip_note'] ),
			'✓'
		);

		if ( ! $log_ok ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка збереження протоколу. Операцію скасовано.', 'code' => 'log_insert_failed' ];
		}

		$this->repository->commit();

		return [ 'success' => true, 'message' => 'Ділянку маршруту успішно додано.', 'pathtrip_id' => $pathtrip_id ];
	}

	/**
	 * @return array{success: bool, message: string, code?: string}
	 */
	public function update_pathtrip( int $pathtrip_id, array $data, int $current_user_id, bool $can_manage ): array {
		$segment = $this->repository->get_route_segment( $pathtrip_id );
		if ( ! is_array( $segment ) ) {
			return [ 'success' => false, 'message' => 'Ділянку маршруту не знайдено.', 'code' => 'pathtrip_not_found' ];
		}

		$calendar_id = (int) ( $segment['Calendar_ID'] ?? 0 );
		$ownership   = $this->assert_owner_or_manager( $calendar_id, $current_user_id, $can_manage );
		if ( true !== $ownership['success'] ) {
			return $ownership;
		}

		$mutation_lock = $this->get_mutation_lock_state( $calendar_id );
		if ( $mutation_lock['locked'] ) {
			return [ 'success' => false, 'message' => $mutation_lock['reason'], 'code' => 'route_locked' ];
		}

		$this->repository->begin_transaction();

		$updated = $this->repository->update_pathtrip( $pathtrip_id, $data );
		if ( ! $updated ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка оновлення ділянки маршруту.', 'code' => 'update_failed' ];
		}

		$log_ok = $this->repository->insert_log(
			$this->protocol_service->get_log_name(),
			'U',
			sprintf( 'Оновлено ділянку маршруту #%d для заходу #%d: %s', $pathtrip_id, $calendar_id, (string) $data['pathtrip_note'] ),
			'✓'
		);

		if ( ! $log_ok ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка збереження протоколу. Операцію скасовано.', 'code' => 'log_update_failed' ];
		}

		$this->repository->commit();

		return [ 'success' => true, 'message' => 'Ділянку маршруту успішно оновлено.' ];
	}

	/**
	 * @return array{success: bool, message: string, code?: string}
	 */
	public function delete_pathtrip( int $pathtrip_id, int $current_user_id, bool $can_delete ): array {
		$segment = $this->repository->get_route_segment( $pathtrip_id );
		if ( ! is_array( $segment ) ) {
			return [ 'success' => false, 'message' => 'Ділянку маршруту не знайдено.', 'code' => 'pathtrip_not_found' ];
		}

		$calendar_id = (int) ( $segment['Calendar_ID'] ?? 0 );
		$ownership   = $this->assert_owner_or_manager( $calendar_id, $current_user_id, $can_delete );
		if ( true !== $ownership['success'] ) {
			return $ownership;
		}

		$mutation_lock = $this->get_mutation_lock_state( $calendar_id );
		if ( $mutation_lock['locked'] ) {
			$this->repository->insert_log(
				$this->protocol_service->get_log_name(),
				'D',
				sprintf( 'Заблоковано видалення ділянки маршруту #%d для заходу #%d', $pathtrip_id, $calendar_id ),
				'dependency'
			);

			return [ 'success' => false, 'message' => $mutation_lock['reason'], 'code' => 'dependency' ];
		}

		$this->repository->begin_transaction();

		$deleted = $this->repository->delete_pathtrip( $pathtrip_id );
		if ( ! $deleted ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка видалення ділянки маршруту.', 'code' => 'delete_failed' ];
		}

		$log_ok = $this->repository->insert_log(
			$this->protocol_service->get_log_name(),
			'D',
			sprintf( 'Видалено ділянку маршруту #%d для заходу #%d: %s', $pathtrip_id, $calendar_id, (string) ( $segment['PathTrip_Note'] ?? '' ) ),
			'✓'
		);

		if ( ! $log_ok ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка збереження протоколу. Операцію скасовано.', 'code' => 'log_delete_failed' ];
		}

		$this->repository->commit();

		return [ 'success' => true, 'message' => 'Ділянку маршруту успішно видалено.' ];
	}

	/**
	 * @return array{success: bool, message: string, apptravel_id?: int, code?: string}
	 */
	public function send_to_mkk( int $calendar_id, int $current_user_id, bool $can_manage ): array {
		$ownership = $this->assert_owner_or_manager( $calendar_id, $current_user_id, $can_manage );
		if ( true !== $ownership['success'] ) {
			return $ownership;
		}

		$overview = $this->repository->get_route_overview( $calendar_id );
		if ( ! is_array( $overview ) ) {
			return [ 'success' => false, 'message' => 'Маршрут не знайдено.', 'code' => 'route_not_found' ];
		}

		if ( (int) ( $overview['Segments_Count'] ?? 0 ) <= 0 ) {
			return [ 'success' => false, 'message' => 'Неможливо відправити маршрут без жодної ділянки.', 'code' => 'empty_route' ];
		}

		if ( self::STATUS_APPROVED === (int) ( $overview['StatusApp_ID'] ?? 0 ) ) {
			return [ 'success' => false, 'message' => 'Маршрут уже фінально погоджено МКК.', 'code' => 'already_approved' ];
		}

		$target_mkk_id = $this->repository->find_matching_mkk_id(
			(int) ( $overview['TourismType_ID'] ?? 0 ),
			(int) ( $overview['Region_ID'] ?? 0 )
		);
		if ( $target_mkk_id <= 0 ) {
			return [ 'success' => false, 'message' => 'Не вдалося визначити МКК для погодження цього маршруту.', 'code' => 'mkk_context_missing' ];
		}

		$this->repository->begin_transaction();

		$apptravel_id = $this->repository->upsert_apptravel( $calendar_id, self::STATUS_UNDER_REVIEW, $current_user_id );
		if ( $apptravel_id <= 0 ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка відправлення маршруту на погодження.', 'code' => 'apptravel_failed' ];
		}

		$log_ok = $this->repository->insert_log(
			$this->protocol_service->get_log_name(),
			'U',
			sprintf( 'Маршрут заходу #%d відправлено на погодження МКК.', $calendar_id ),
			'✓'
		);

		if ( ! $log_ok ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка збереження протоколу. Операцію скасовано.', 'code' => 'log_send_failed' ];
		}

		$this->repository->commit();

		$mkk_contact = $this->repository->get_mkk_contact( $target_mkk_id );
		$email_sent  = $this->notification_service->send_route_submitted_to_mkk_email(
			[
				'recipient_email'  => (string) ( $mkk_contact['email'] ?? '' ),
				'event_name'       => (string) ( $overview['Calendar_Name'] ?? ( '#' . $calendar_id ) ),
				'responsible_name' => (string) ( $overview['Responsible_FullName'] ?? '' ),
				'mkk_name'         => $this->repository->get_mkk_label( $target_mkk_id ),
				'module_url'       => Calendar_List::get_module_url(),
			]
		);

		$message = 'Маршрут успішно відправлено на погодження МКК.';
		if ( ! $email_sent ) {
			$message .= ' Маршрут збережено, але email-сповіщення МКК не вдалося доставити.';
		}

		return [ 'success' => true, 'message' => $message, 'apptravel_id' => $apptravel_id, 'email_sent' => $email_sent ];
	}

	/**
	 * @return array{success: bool, message: string, verification_id?: int, code?: string}
	 */
	public function review_route( int $calendar_id, int $decision_status, string $note, int $current_user_id, bool $can_review_mkk ): array {
		$route = $this->repository->get_route_overview( $calendar_id );
		if ( ! is_array( $route ) ) {
			return [ 'success' => false, 'message' => 'Маршрут не знайдено.', 'code' => 'route_not_found' ];
		}

		if ( self::STATUS_UNDER_REVIEW !== (int) ( $route['StatusApp_ID'] ?? 0 ) ) {
			return [ 'success' => false, 'message' => 'Маршрут не перебуває у статусі погодження МКК.', 'code' => 'invalid_status' ];
		}

		$mkk_context = $this->mkk_service->get_reviewer_context( $calendar_id, $current_user_id, $can_review_mkk );
		if ( empty( $mkk_context['allowed'] ) || (int) ( $mkk_context['reviewer_mkk_id'] ?? 0 ) <= 0 ) {
			return [ 'success' => false, 'message' => 'Немає прав для погодження цього маршруту в контексті МКК.', 'code' => 'forbidden' ];
		}

		$next_status = self::STATUS_UNDER_REVIEW;
		$log_message = sprintf( 'МКК-рішення для маршруту заходу #%d: погоджено.', $calendar_id );

		if ( 2 === $decision_status ) {
			$next_status = self::STATUS_NEEDS_FIXES;
			$log_message = sprintf( 'МКК-рішення для маршруту заходу #%d: виявлено помилки.', $calendar_id );
		} elseif ( ! empty( $mkk_context['can_final_approve'] ) ) {
			$next_status = self::STATUS_APPROVED;
			$log_message = sprintf( 'Маршрут заходу #%d фінально погоджено МКК.', $calendar_id );
		}

		$this->repository->begin_transaction();

		$verification_id = $this->repository->insert_verification( $calendar_id, (int) $mkk_context['reviewer_mkk_id'], $decision_status, $note );
		if ( $verification_id <= 0 ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка збереження рішення МКК.', 'code' => 'verification_failed' ];
		}

		$apptravel_id = $this->repository->upsert_apptravel( $calendar_id, $next_status, (int) ( $route['AppTravel_UserCreate'] ?: $route['UserCreate'] ?: $current_user_id ) );
		if ( $apptravel_id <= 0 ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка оновлення статусу маршруту.', 'code' => 'status_update_failed' ];
		}

		$log_ok = $this->repository->insert_log(
			$this->protocol_service->get_log_name(),
			'U',
			$log_message,
			'✓'
		);

		if ( ! $log_ok ) {
			$this->repository->rollback();

			return [ 'success' => false, 'message' => 'Сталася помилка збереження протоколу. Операцію скасовано.', 'code' => 'log_review_failed' ];
		}

		$this->repository->commit();

		$owner_id    = (int) ( $route['User_ID'] ?: $route['UserCreate'] ?: 0 );
		$owner_user  = $owner_id > 0 ? get_userdata( $owner_id ) : false;
		$email_sent  = $owner_user instanceof \WP_User
			? $this->notification_service->send_route_review_decision_email(
				[
					'recipient_email' => $owner_user->user_email,
					'event_name'      => (string) ( $route['Calendar_Name'] ?? ( '#' . $calendar_id ) ),
					'status_name'     => self::STATUS_APPROVED === $next_status ? 'Погоджено' : ( self::STATUS_NEEDS_FIXES === $next_status ? 'Потребує доопрацювання' : 'На розгляді' ),
					'decision_label'  => 2 === $decision_status ? 'Зауваження МКК' : ( self::STATUS_APPROVED === $next_status ? 'Фінальне погодження' : 'Проміжне погодження' ),
					'review_note'     => $note,
					'module_url'      => Calendar_List::get_module_url(),
				]
			)
			: false;

		$message = 2 === $decision_status ? 'Рішення МКК про зауваження успішно збережено.' : 'Рішення МКК успішно збережено.';
		if ( ! $email_sent ) {
			$message .= ' Рішення збережено, але email-сповіщення власнику маршруту не вдалося доставити.';
		}

		return [ 'success' => true, 'message' => $message, 'verification_id' => $verification_id, 'email_sent' => $email_sent ];
	}

	/**
	 * @return array{success: bool, message: string, code?: string}
	 */
	private function assert_owner_or_manager( int $calendar_id, int $current_user_id, bool $can_manage ): array {
		$owner_id = $this->repository->get_route_owner_id( $calendar_id );
		if ( $owner_id <= 0 ) {
			return [ 'success' => false, 'message' => 'Маршрут не знайдено.', 'code' => 'route_not_found' ];
		}

		if ( ! $can_manage && $owner_id !== $current_user_id ) {
			return [ 'success' => false, 'message' => 'Недостатньо прав для керування цим маршрутом.', 'code' => 'forbidden' ];
		}

		return [ 'success' => true, 'message' => '' ];
	}

	/**
	 * @return array{locked: bool, reason: string}
	 */
	private function get_mutation_lock_state( int $calendar_id ): array {
		$state              = $this->repository->get_route_mutation_state( $calendar_id );
		$status_app_id      = (int) ( $state['status_app_id'] ?? 0 );
		$verifications_count = (int) ( $state['verifications_count'] ?? 0 );

		if ( self::STATUS_APPROVED === $status_app_id ) {
			return [
				'locked' => true,
				'reason' => 'Маршрут уже фінально погоджено МКК. Редагування заблоковано.',
			];
		}

		if ( self::STATUS_UNDER_REVIEW === $status_app_id ) {
			return [
				'locked' => true,
				'reason' => 'Маршрут перебуває на погодженні МКК. Дочекайтеся завершення review-flow.',
			];
		}

		if ( $verifications_count > 0 ) {
			return [
				'locked' => true,
				'reason' => 'Видалення заблоковано: для маршруту вже існують рішення МКК або downstream-залежності.',
			];
		}

		return [ 'locked' => false, 'reason' => '' ];
	}
}
