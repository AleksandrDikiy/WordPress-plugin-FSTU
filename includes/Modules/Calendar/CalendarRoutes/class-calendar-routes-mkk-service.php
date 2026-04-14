<?php
namespace FSTU\Modules\Calendar\CalendarRoutes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Сервіс MKK-контексту для Calendar_Routes.
 *
 * Version: 1.1.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarRoutes
 */
class Calendar_Routes_MKK_Service {

	private const HEAD_COMMISSION_TYPE_ID = 1;

	private Calendar_Routes_Repository $repository;

	public function __construct( ?Calendar_Routes_Repository $repository = null ) {
		$this->repository = $repository instanceof Calendar_Routes_Repository ? $repository : new Calendar_Routes_Repository();
	}

	/**
	 * Повертає MKK-контекст поточного користувача в межах маршруту.
	 *
	 * @return array<string, mixed>
	 */
	public function get_reviewer_context( int $calendar_id, int $current_user_id, bool $can_global_review ): array {
		$empty_context = $this->get_empty_context();

		if ( $current_user_id <= 0 ) {
			return $empty_context;
		}

		$route_context = $this->repository->get_route_review_context( $calendar_id );
		if ( ! is_array( $route_context ) ) {
			return $empty_context;
		}

		$tourism_type_id = (int) ( $route_context['TourismType_ID'] ?? 0 );
		$region_id       = (int) ( $route_context['Region_ID'] ?? 0 );
		$target_mkk_id   = (int) ( $route_context['Verification_mkk_ID'] ?? 0 );

		if ( $target_mkk_id <= 0 ) {
			$target_mkk_id = $this->repository->find_matching_mkk_id( $tourism_type_id, $region_id );
		}

		if ( $can_global_review ) {
			$reviewer_mkk_id = $target_mkk_id > 0 ? $target_mkk_id : $this->get_admin_fallback_mkk_id( $tourism_type_id, $region_id );

			return array_merge(
				$empty_context,
				[
					'allowed'                => $reviewer_mkk_id > 0,
					'can_final_approve'      => $reviewer_mkk_id > 0,
					'reviewer_mkk_id'        => $reviewer_mkk_id,
					'target_mkk_id'          => $target_mkk_id,
					'route_context'          => $route_context,
					'match_type'             => 'administrator_override',
					'target_resolution'      => (int) ( $route_context['Verification_mkk_ID'] ?? 0 ) > 0 ? 'verification' : 'route_context',
				]
			);
		}

		$memberships = $this->repository->get_user_mkk_memberships( $current_user_id, $tourism_type_id );
		if ( empty( $memberships ) ) {
			return array_merge(
				$empty_context,
				[
					'target_mkk_id'     => $target_mkk_id,
					'route_context'     => $route_context,
					'target_resolution' => (int) ( $route_context['Verification_mkk_ID'] ?? 0 ) > 0 ? 'verification' : 'route_context',
				]
			);
		}

		$group_cmkk = null;
		$head_mkk   = null;
		$group_mkk  = null;
		$matched    = null;

		foreach ( $memberships as $membership ) {
			$is_central = $this->is_central_membership( $membership );
			$is_head    = $this->is_head_membership( $membership, $region_id );
			$is_regional_match = (int) ( $membership['Region_ID'] ?? 0 ) === $region_id && $region_id > 0;

			if ( $is_central && null === $group_cmkk ) {
				$group_cmkk = $membership;
			}

			if ( $is_head && null === $head_mkk ) {
				$head_mkk = $membership;
			}

			if ( ( $is_regional_match || $is_central ) && null === $group_mkk ) {
				$group_mkk = $membership;
			}

			if ( $target_mkk_id > 0 && (int) ( $membership['mkk_ID'] ?? 0 ) === $target_mkk_id ) {
				$matched = $membership;
				break;
			}
		}

		if ( ! is_array( $matched ) ) {
			$matched = is_array( $head_mkk ) ? $head_mkk : ( is_array( $group_mkk ) ? $group_mkk : $group_cmkk );
		}

		$reviewer_mkk_id = is_array( $matched ) ? (int) ( $matched['mkk_ID'] ?? 0 ) : 0;
		$match_type      = 'none';
		if ( is_array( $matched ) ) {
			if ( $target_mkk_id > 0 && $reviewer_mkk_id === $target_mkk_id ) {
				$match_type = 'exact_mkk';
			} elseif ( $this->is_central_membership( $matched ) ) {
				$match_type = 'central_override';
			} elseif ( $this->is_head_membership( $matched, $region_id ) ) {
				$match_type = 'regional_head';
			} else {
				$match_type = 'regional_group';
			}
		}

		return array_merge(
			$empty_context,
			[
				'allowed'           => $reviewer_mkk_id > 0,
				'can_final_approve' => is_array( $matched ) && ( $this->is_central_membership( $matched ) || $this->is_head_membership( $matched, $region_id ) ),
				'reviewer_mkk_id'   => $reviewer_mkk_id,
				'group_mkk_id'      => is_array( $group_mkk ) ? (int) ( $group_mkk['mkk_ID'] ?? 0 ) : 0,
				'head_mkk_id'       => is_array( $head_mkk ) ? (int) ( $head_mkk['mkk_ID'] ?? 0 ) : 0,
				'group_cmkk_id'     => is_array( $group_cmkk ) ? (int) ( $group_cmkk['mkk_ID'] ?? 0 ) : 0,
				'target_mkk_id'     => $target_mkk_id,
				'route_context'     => $route_context,
				'match_type'        => $match_type,
				'target_resolution' => (int) ( $route_context['Verification_mkk_ID'] ?? 0 ) > 0 ? 'verification' : 'route_context',
			]
		);
	}

	public function can_view_route_context( int $calendar_id, int $current_user_id, bool $can_global_review ): bool {
		$context = $this->get_reviewer_context( $calendar_id, $current_user_id, $can_global_review );

		return ! empty( $context['allowed'] );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function get_empty_context(): array {
		return [
			'allowed'           => false,
			'can_final_approve' => false,
			'reviewer_mkk_id'   => 0,
			'group_mkk_id'      => 0,
			'head_mkk_id'       => 0,
			'group_cmkk_id'     => 0,
			'target_mkk_id'     => 0,
			'route_context'     => [],
			'match_type'        => 'none',
			'target_resolution' => 'none',
		];
	}

	/**
	 * Чи є членство центральним контуром ЦМКК.
	 *
	 * @param array<string, mixed> $membership
	 */
	private function is_central_membership( array $membership ): bool {
		return (int) ( $membership['Region_ID'] ?? 0 ) <= 0;
	}

	/**
	 * Чи має reviewer право фінального погодження в регіональному/центральному контурі.
	 *
	 * @param array<string, mixed> $membership
	 */
	private function is_head_membership( array $membership, int $route_region_id ): bool {
		$commission_type_id = (int) ( $membership['CommissionType_ID'] ?? 0 );
		$membership_region  = (int) ( $membership['Region_ID'] ?? 0 );

		if ( self::HEAD_COMMISSION_TYPE_ID !== $commission_type_id ) {
			return false;
		}

		return $membership_region <= 0 || $membership_region === $route_region_id;
	}

	private function get_admin_fallback_mkk_id( int $tourism_type_id, int $region_id ): int {
		$fallback_id = $this->repository->find_matching_mkk_id( $tourism_type_id, $region_id );

		if ( $fallback_id > 0 ) {
			return $fallback_id;
		}

		global $wpdb;

		return (int) $wpdb->get_var( 'SELECT mkk_ID FROM mkk ORDER BY mkk_DateBegin DESC, mkk_ID DESC LIMIT 1' );
	}
}
