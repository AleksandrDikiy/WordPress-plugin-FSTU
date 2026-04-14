<?php
namespace FSTU\Modules\Calendar\CalendarEvents;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Protocol service підмодуля Calendar_Events.
 *
 * Version: 1.0.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarEvents
 */
class Calendar_Events_Protocol_Service {

	public const LOG_NAME = 'CalendarEvents';

	private Calendar_Events_Repository $repository;

	public function __construct( ?Calendar_Events_Repository $repository = null ) {
		$this->repository = $repository instanceof Calendar_Events_Repository ? $repository : new Calendar_Events_Repository();
	}

	/**
	 * Повертає логічне ім’я протоколу.
	 */
	public function get_log_name(): string {
		return self::LOG_NAME;
	}

	/**
	 * Повертає дані протоколу.
	 *
	 * @return array{items: array<int, array<string, mixed>>, total: int}
	 */
	public function get_protocol( string $search, int $per_page, int $offset ): array {
		return $this->repository->get_protocol( self::LOG_NAME, $search, $per_page, $offset );
	}
}

