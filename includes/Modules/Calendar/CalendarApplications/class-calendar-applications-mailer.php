<?php
namespace FSTU\Modules\Calendar\CalendarApplications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Поштовий сервіс підмодуля Calendar_Applications.
 * Відповідає за MVP email-сценарії заявок і нових учасників.
 *
 * Version: 1.0.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarApplications
 */
class Calendar_Applications_Mailer {

	private ?Calendar_Applications_Repository $repository = null;

	public function __construct( ?Calendar_Applications_Repository $repository = null ) {
		$this->repository = $repository instanceof Calendar_Applications_Repository ? $repository : null;
	}

	/**
	 * @param array<string,mixed> $payload
	 */
	public function send_new_participant_account_email( array $payload ): bool {
		$to = sanitize_email( (string) ( $payload['email'] ?? '' ) );
		if ( '' === $to || ! is_email( $to ) ) {
			return false;
		}

		$subject = sprintf( 'Новий учасник ФСТУ створений: %s', $this->resolve_name( $payload ) );
		$message = implode(
			"\n",
			array_filter(
				[
					'Вітаємо!',
					'Для вас створено обліковий запис учасника ФСТУ.',
					'',
					'Логін: ' . (string) ( $payload['login'] ?? '' ),
					'Email: ' . $to,
					'Тимчасовий пароль: ' . (string) ( $payload['password'] ?? '' ),
					'',
					'Заявка: ' . (string) ( $payload['application_name'] ?? '—' ),
					'' !== trim( (string) ( $payload['module_url'] ?? '' ) ) ? 'Посилання на модуль: ' . (string) $payload['module_url'] : '',
				]
			)
		);

		return $this->send_email( $to, $subject, $message );
	}

	/**
	 * @param array<string,mixed> $payload
	 */
	public function send_application_submitted_email( array $payload ): bool {
		$to = $this->get_admin_email();
		if ( '' === $to || ! is_email( $to ) ) {
			return false;
		}

		$subject = sprintf( 'Заявку подано на розгляд: %s', (string) ( $payload['application_name'] ?? 'Без назви' ) );
		$message = implode(
			"\n",
			array_filter(
				[
					'До системи ФСТУ подано заявку на службовий розгляд.',
					'',
					'Заявка: ' . (string) ( $payload['application_name'] ?? '—' ),
					'Створив: ' . (string) ( $payload['owner_name'] ?? '—' ),
					'Новий статус: ' . (string) ( $payload['status_name'] ?? '—' ),
					'' !== trim( (string) ( $payload['module_url'] ?? '' ) ) ? 'Посилання на модуль: ' . (string) $payload['module_url'] : '',
				]
			)
		);

		return $this->send_email( $to, $subject, $message );
	}

	/**
	 * @param array<string,mixed> $payload
	 */
	public function send_application_status_changed_email( array $payload ): bool {
		$to = sanitize_email( (string) ( $payload['owner_email'] ?? '' ) );
		if ( '' === $to || ! is_email( $to ) ) {
			return false;
		}

		$subject = sprintf( 'Статус заявки змінено: %s', (string) ( $payload['application_name'] ?? 'Без назви' ) );
		$message = implode(
			"\n",
			array_filter(
				[
					'Статус вашої заявки у ФСТУ змінено.',
					'',
					'Заявка: ' . (string) ( $payload['application_name'] ?? '—' ),
					'Старий статус: ' . (string) ( $payload['previous_status_name'] ?? '—' ),
					'Новий статус: ' . (string) ( $payload['status_name'] ?? '—' ),
					'' !== trim( (string) ( $payload['module_url'] ?? '' ) ) ? 'Посилання на модуль: ' . (string) $payload['module_url'] : '',
				]
			)
		);

		return $this->send_email( $to, $subject, $message );
	}

	private function get_repository(): Calendar_Applications_Repository {
		if ( null === $this->repository ) {
			$this->repository = new Calendar_Applications_Repository();
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

	/**
	 * @param array<string,mixed> $payload
	 */
	private function resolve_name( array $payload ): string {
		return trim( implode( ' ', array_filter( [
			(string) ( $payload['last_name'] ?? '' ),
			(string) ( $payload['first_name'] ?? '' ),
			(string) ( $payload['patronymic'] ?? '' ),
		] ) ) );
	}
}

