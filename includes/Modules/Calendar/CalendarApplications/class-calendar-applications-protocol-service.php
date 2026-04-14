<?php
namespace FSTU\Modules\Calendar\CalendarApplications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Protocol service підмодуля Calendar_Applications.
 *
 * Version: 1.0.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarApplications
 */
class Calendar_Applications_Protocol_Service {

	public const LOG_NAME = 'CalendarApplications';

	private Calendar_Applications_Repository $repository;

	public function __construct( ?Calendar_Applications_Repository $repository = null ) {
		$this->repository = $repository instanceof Calendar_Applications_Repository ? $repository : new Calendar_Applications_Repository();
	}

	public function get_log_name(): string {
		return self::LOG_NAME;
	}

	/**
	 * @return array{items: array<int, array<string, mixed>>, total: int}
	 */
	public function get_protocol( string $search, int $per_page, int $offset ): array {
		return $this->repository->get_protocol( self::LOG_NAME, $search, $per_page, $offset );
	}
}

