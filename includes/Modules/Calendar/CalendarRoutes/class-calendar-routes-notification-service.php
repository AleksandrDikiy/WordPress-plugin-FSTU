<?php
namespace FSTU\Modules\Calendar\CalendarRoutes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Сервіс email-сповіщень підмодуля Calendar_Routes.
 * Відповідає за MVP MKK-email сценарії для відправлення і review маршруту.
 *
 * Version: 1.0.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarRoutes
 */
class Calendar_Routes_Notification_Service {

	private ?Calendar_Routes_Repository $repository = null;

	public function __construct( ?Calendar_Routes_Repository $repository = null ) {
		$this->repository = $repository instanceof Calendar_Routes_Repository ? $repository : null;
	}

	/**
	 * @param array<string,mixed> $payload
	 */
	public function send_route_submitted_to_mkk_email( array $payload ): bool {
		$to = sanitize_email( (string) ( $payload['recipient_email'] ?? '' ) );
		if ( '' === $to || ! is_email( $to ) ) {
			$to = $this->get_admin_email();
		}

		if ( '' === $to || ! is_email( $to ) ) {
			return false;
		}

		$subject = sprintf( 'Маршрут подано на погодження МКК: %s', (string) ( $payload['event_name'] ?? 'Без назви' ) );
		$message = implode(
			"\n",
			array_filter(
				[
					'До системи ФСТУ подано маршрут на погодження МКК.',
					'',
					'Захід: ' . (string) ( $payload['event_name'] ?? '—' ),
					'Відповідальний: ' . (string) ( $payload['responsible_name'] ?? '—' ),
					'Цільова МКК: ' . (string) ( $payload['mkk_name'] ?? '—' ),
					'' !== trim( (string) ( $payload['module_url'] ?? '' ) ) ? 'Посилання на модуль: ' . (string) $payload['module_url'] : '',
				]
			)
		);

		return $this->send_email( $to, $subject, $message );
	}

	/**
	 * @param array<string,mixed> $payload
	 */
	public function send_route_review_decision_email( array $payload ): bool {
		$to = sanitize_email( (string) ( $payload['recipient_email'] ?? '' ) );
		if ( '' === $to || ! is_email( $to ) ) {
			return false;
		}

		$subject = sprintf( 'Рішення МКК по маршруту: %s', (string) ( $payload['event_name'] ?? 'Без назви' ) );
		$message = implode(
			"\n",
			array_filter(
				[
					'Для вашого маршруту зафіксовано рішення МКК.',
					'',
					'Захід: ' . (string) ( $payload['event_name'] ?? '—' ),
					'Статус маршруту: ' . (string) ( $payload['status_name'] ?? '—' ),
					'Рішення: ' . (string) ( $payload['decision_label'] ?? '—' ),
					'' !== trim( (string) ( $payload['review_note'] ?? '' ) ) ? 'Примітка МКК: ' . (string) $payload['review_note'] : '',
					'' !== trim( (string) ( $payload['module_url'] ?? '' ) ) ? 'Посилання на модуль: ' . (string) $payload['module_url'] : '',
				]
			)
		);

		return $this->send_email( $to, $subject, $message );
	}

	private function get_repository(): Calendar_Routes_Repository {
		if ( null === $this->repository ) {
			$this->repository = new Calendar_Routes_Repository();
		}

		return $this->repository;
	}

	private function get_admin_email(): string {
		$email = sanitize_email( $this->get_repository()->get_setting_value( 'EmailFSTU' ) );
		if ( '' !== $email && is_email( $email ) ) {
			return $email;
		}

		return sanitize_email( (string) get_option( 'admin_email', '' ) );
	}

	private function get_headers(): array {
		$headers    = [ 'Content-Type: text/plain; charset=UTF-8' ];
		$from_email = $this->get_admin_email();

		if ( '' !== $from_email && is_email( $from_email ) ) {
			$headers[] = 'From: ' . $from_email;
			$headers[] = 'Reply-To: ' . $from_email;
		}

		return $headers;
	}

	private function send_email( string $to, string $subject, string $message ): bool {
		if ( '' === $to || ! is_email( $to ) ) {
			return false;
		}

		return (bool) wp_mail( $to, $subject, $message, $this->get_headers() );
	}
}

