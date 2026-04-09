<?php
namespace FSTU\Modules\Registry\Steering;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Сервіс сповіщень модуля «Реєстр стернових ФСТУ».
 *
 * Реалізує MVP email і Telegram-сповіщення для створення заявки,
 * реєстрації посвідчення та позначки «ВІДПРАВЛЕНО ПОШТОЮ».
 *
 * Version:     1.1.0
 * Date_update: 2026-04-08
 *
 * @package FSTU\Modules\Registry\Steering
 */
class Steering_Notification_Service {

	private ?Steering_Repository $repository = null;

	public function __construct( ?Steering_Repository $repository = null ) {
		$this->repository = $repository instanceof Steering_Repository ? $repository : null;
	}

	/**
	 * @param array<string,mixed> $payload
	 */
	public function dispatch_submission_created( array $payload ): void {
		$subject = sprintf( 'Заявка на посвідчення стернового - %s', $this->resolve_fio( $payload ) );
		$message = $this->build_submission_message( $payload );

		$this->send_email( $this->get_admin_email(), $subject, $message );
		$this->send_telegram_message( $message, $this->get_admin_telegram_chat_id() );
	}

	/**
	 * @param array<string,mixed> $payload
	 */
	public function dispatch_certificate_registered( array $payload, bool $is_auto = false ): void {
		$email = sanitize_email( (string) ( $payload['user_email'] ?? '' ) );
		if ( '' !== $email && is_email( $email ) ) {
			$subject = sprintf( 'Посвідчення стернового зареєстровано - %s', $this->resolve_fio( $payload ) );
			$message = $this->build_registered_message( $payload, $is_auto );
			$this->send_email( $email, $subject, $message );
		}

		$this->send_telegram_message(
			$this->build_registered_message( $payload, $is_auto ),
			(string) ( $payload['TelegramID'] ?? '' )
		);
	}

	/**
	 * @param array<string,mixed> $payload
	 */
	public function dispatch_certificate_sent( array $payload ): void {
		$email = sanitize_email( (string) ( $payload['user_email'] ?? '' ) );
		if ( '' !== $email && is_email( $email ) ) {
			$subject = sprintf( 'Посвідчення стернового відправлено поштою - %s', $this->resolve_fio( $payload ) );
			$message = $this->build_sent_message( $payload );
			$this->send_email( $email, $subject, $message );
		}

		$this->send_telegram_message(
			$this->build_sent_message( $payload ),
			(string) ( $payload['TelegramID'] ?? '' )
		);
	}

	private function get_repository(): Steering_Repository {
		if ( null === $this->repository ) {
			$this->repository = new Steering_Repository();
		}

		return $this->repository;
	}

	private function get_admin_email(): string {
		$email = sanitize_email( $this->get_repository()->get_setting_value( 'EmailFSTU' ) );

		if ( '' !== $email && is_email( $email ) ) {
			return $email;
		}

		return (string) get_option( 'admin_email', '' );
	}

	private function get_admin_telegram_chat_id(): string {
		if ( defined( 'FSTU_TELEGRAM_CHAT_ID' ) && is_string( FSTU_TELEGRAM_CHAT_ID ) ) {
			return trim( FSTU_TELEGRAM_CHAT_ID );
		}

		foreach ( [ 'TelegramChatID', 'TelegramAdminChatID', 'TelegramID' ] as $setting_name ) {
			$value = trim( $this->get_repository()->get_setting_value( $setting_name ) );
			if ( '' !== $value ) {
				return $value;
			}
		}

		return '';
	}

	private function get_telegram_bot_token(): string {
		if ( defined( 'FSTU_TELEGRAM_BOT_TOKEN' ) && is_string( FSTU_TELEGRAM_BOT_TOKEN ) ) {
			return trim( FSTU_TELEGRAM_BOT_TOKEN );
		}

		foreach ( [ 'TelegramBotToken', 'TelegramToken', 'TelegramBotApiToken' ] as $setting_name ) {
			$value = trim( $this->get_repository()->get_setting_value( $setting_name ) );
			if ( '' !== $value ) {
				return $value;
			}
		}

		return '';
	}

	private function resolve_fio( array $payload ): string {
		$fio = trim( (string) ( $payload['FIO'] ?? '' ) );

		return '' !== $fio ? $fio : '#' . (int) ( $payload['Steering_ID'] ?? 0 );
	}

	/**
	 * @param array<string,mixed> $payload
	 */
	private function build_submission_message( array $payload ): string {
		$lines = [
			sprintf( 'Подано заявку на посвідчення стернового ФСТУ: %s', $this->resolve_fio( $payload ) ),
			sprintf( 'ID запису: %d', (int) ( $payload['Steering_ID'] ?? 0 ) ),
			sprintf( 'Email: %s', $this->normalize_text( (string) ( $payload['user_email'] ?? '' ) ) ),
			sprintf( 'Телефон: %s', $this->normalize_text( (string) get_user_meta( (int) ( $payload['User_ID'] ?? 0 ), 'PhoneMobile', true ) ) ),
			sprintf( 'Місто НП: %s', $this->normalize_text( (string) ( $payload['Steering_CityNP'] ?? '' ) ) ),
			sprintf( '№ НП: %s', $this->normalize_text( (string) ( $payload['Steering_NumberNP'] ?? '' ) ) ),
			sprintf( 'Статус: %s', $this->normalize_text( (string) ( $payload['AppStatus_Name'] ?? '' ) ) ),
			sprintf( 'Посилання: %s', $this->get_item_url( (int) ( $payload['Steering_ID'] ?? 0 ) ) ),
		];

		return implode( PHP_EOL, $lines );
	}

	/**
	 * @param array<string,mixed> $payload
	 */
	private function build_registered_message( array $payload, bool $is_auto ): string {
		$lines = [
			sprintf( '%s посвідчення стернового ФСТУ для %s.', $is_auto ? 'Автоматично зареєстровано' : 'Зареєстровано', $this->resolve_fio( $payload ) ),
			sprintf( '№ посвідчення: %s', $this->normalize_text( (string) ( $payload['Steering_RegNumber'] ?? '' ) ) ),
			sprintf( 'Статус: %s', $this->normalize_text( (string) ( $payload['AppStatus_Name'] ?? '' ) ) ),
			sprintf( 'Посилання: %s', $this->get_item_url( (int) ( $payload['Steering_ID'] ?? 0 ) ) ),
		];

		return implode( PHP_EOL, $lines );
	}

	/**
	 * @param array<string,mixed> $payload
	 */
	private function build_sent_message( array $payload ): string {
		$lines = [
			sprintf( 'Посвідчення стернового ФСТУ відправлено поштою: %s', $this->resolve_fio( $payload ) ),
			sprintf( '№ посвідчення: %s', $this->normalize_text( (string) ( $payload['Steering_RegNumber'] ?? '' ) ) ),
			sprintf( 'Місто НП: %s', $this->normalize_text( (string) ( $payload['Steering_CityNP'] ?? '' ) ) ),
			sprintf( '№ НП: %s', $this->normalize_text( (string) ( $payload['Steering_NumberNP'] ?? '' ) ) ),
			sprintf( 'Статус: %s', $this->normalize_text( (string) ( $payload['AppStatus_Name'] ?? '' ) ) ),
			sprintf( 'Посилання: %s', $this->get_item_url( (int) ( $payload['Steering_ID'] ?? 0 ) ) ),
		];

		return implode( PHP_EOL, $lines );
	}

	private function get_item_url( int $steering_id ): string {
		$module_url = Steering_List::get_module_url();

		if ( '' === $module_url ) {
			return home_url( '/' );
		}

		return add_query_arg( 'steering_id', $steering_id, $module_url );
	}

	private function normalize_text( string $value ): string {
		$value = trim( $value );

		return '' !== $value ? $value : '—';
	}

	private function send_email( string $to_email, string $subject, string $message ): void {
		$to_email = sanitize_email( $to_email );
		if ( '' === $to_email || ! is_email( $to_email ) ) {
			throw new \RuntimeException( 'notification_email_not_found' );
		}

		$headers = [ 'Content-Type: text/plain; charset=UTF-8' ];
		$from_email = sanitize_email( $this->get_repository()->get_setting_value( 'EmailFSTU' ) );

		if ( '' !== $from_email && is_email( $from_email ) ) {
			$headers[] = 'Reply-To: ' . $from_email;
		}

		if ( ! wp_mail( $to_email, $subject, $message, $headers ) ) {
			throw new \RuntimeException( 'notification_send_failed' );
		}
	}

	private function send_telegram_message( string $message, string $chat_id ): void {
		$chat_id = trim( $chat_id );
		$token   = $this->get_telegram_bot_token();

		if ( '' === $chat_id || '' === $token ) {
			return;
		}

		$response = wp_remote_post(
			'https://api.telegram.org/bot' . $token . '/sendMessage',
			[
				'timeout' => 10,
				'body'    => [
					'chat_id'                  => $chat_id,
					'text'                     => $message,
					'disable_web_page_preview' => true,
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			throw new \RuntimeException( 'notification_telegram_failed' );
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		if ( $status_code >= 400 ) {
			throw new \RuntimeException( 'notification_telegram_failed' );
		}
	}
}

