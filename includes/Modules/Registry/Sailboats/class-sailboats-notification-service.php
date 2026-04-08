<?php
namespace FSTU\Modules\Registry\Sailboats;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Сервіс сповіщень модуля "Судновий реєстр ФСТУ".
 * Формує та надсилає повідомлення щодо внесків через wp_mail().
 *
 * Version:     1.2.0
 * Date_update: 2026-04-07
 *
 * @package FSTU\Modules\Registry\Sailboats
 */
class Sailboats_Notification_Service {

	private ?Sailboats_Repository $repository = null;

	/**
	 * Повертає дефолтні Settings-параметри шаблонів сповіщень модуля.
	 *
	 * @return array<string,array<string,string>>
	 */
	public static function get_settings_defaults(): array {
		return [
			'SailboatsMailSubjectMembership' => [
				'value'       => 'Нагадування про сплату членських внесків ФСТУ: {boat_name}',
				'description' => 'Тема листа для нагадування про членські внески ФСТУ. Доступні плейсхолдери: {boat_name}, {owner_name}.',
			],
			'SailboatsMailSubjectSailing' => [
				'value'       => 'Нагадування про сплату вітрильних внесків: {boat_name}',
				'description' => 'Тема листа для нагадування про вітрильні внески. Доступні плейсхолдери: {boat_name}, {owner_name}.',
			],
			'SailboatsMailSubjectCombined' => [
				'value'       => 'Нагадування про сплату внесків ФСТУ та вітрильних внесків: {boat_name}',
				'description' => 'Тема листа для комбінованого нагадування. Доступні плейсхолдери: {boat_name}, {owner_name}.',
			],
			'SailboatsMailBodyMembership' => [
				'value'       => "Шановний(а) {owner_name},\n\nНагадуємо про необхідність перевірити та сплатити членські внески ФСТУ для {boat_name}.\n\nV1: {V1}\nV2: {V2}\nF1: {F1}\nF2: {F2}\n\n{comment_block}\n{signature}",
				'description' => 'Текст листа для членських внесків. Доступні плейсхолдери: {owner_name}, {boat_name}, {V1}, {V2}, {F1}, {F2}, {comment}, {comment_block}, {signature}.',
			],
			'SailboatsMailBodySailing' => [
				'value'       => "Шановний(а) {owner_name},\n\nНагадуємо про необхідність перевірити та сплатити вітрильні внески для {boat_name}.\n\nV1: {V1}\nV2: {V2}\nF1: {F1}\nF2: {F2}\n\n{comment_block}\n{signature}",
				'description' => 'Текст листа для вітрильних внесків. Доступні плейсхолдери: {owner_name}, {boat_name}, {V1}, {V2}, {F1}, {F2}, {comment}, {comment_block}, {signature}.',
			],
			'SailboatsMailBodyCombined' => [
				'value'       => "Шановний(а) {owner_name},\n\nНагадуємо про необхідність перевірити та сплатити членські внески ФСТУ і вітрильні внески для {boat_name}.\n\nV1: {V1}\nV2: {V2}\nF1: {F1}\nF2: {F2}\n\n{comment_block}\n{signature}",
				'description' => 'Текст листа для комбінованого нагадування. Доступні плейсхолдери: {owner_name}, {boat_name}, {V1}, {V2}, {F1}, {F2}, {comment}, {comment_block}, {signature}.',
			],
			'SailboatsMailSignature' => [
				'value'       => "З повагою,\nФедерація спортивного туризму України",
				'description' => 'Підпис, який додається до всіх листів реєстру суден.',
			],
		];
	}

	/**
	 * Надсилає повідомлення щодо внесків.
	 *
	 * @param array<string,mixed> $payload Дані для формування повідомлення.
	 */
	public function send_dues_notification( array $payload ): bool {
		$email = sanitize_email( (string) ( $payload['notification_email'] ?? '' ) );
		if ( '' === $email || ! is_email( $email ) ) {
			throw new \RuntimeException( 'notification_email_not_found' );
		}

		$subject = $this->build_subject( (string) ( $payload['notification_type'] ?? 'membership' ), $payload );
		$message = $this->build_message( (string) ( $payload['notification_type'] ?? 'membership' ), $payload );
		$headers = [ 'Content-Type: text/plain; charset=UTF-8' ];
		$from_email = sanitize_email( $this->get_repository()->get_setting_value( 'EmailFSTU' ) );

		if ( '' !== $from_email && is_email( $from_email ) ) {
			$headers[] = 'Reply-To: ' . $from_email;
		}

		$sent = wp_mail( $email, $subject, $message, $headers );
		if ( ! $sent ) {
			throw new \RuntimeException( 'notification_send_failed' );
		}

		return true;
	}

	/**
	 * Формує тему листа.
	 *
	 * @param array<string,mixed> $payload Дані листа.
	 */
	private function build_subject( string $type, array $payload ): string {
		$setting_name = match ( $type ) {
			'sailing'  => 'SailboatsMailSubjectSailing',
			'combined' => 'SailboatsMailSubjectCombined',
			default    => 'SailboatsMailSubjectMembership',
		};

		$default_template = self::get_settings_defaults()[ $setting_name ]['value'] ?? '{boat_name}';
		$template         = $this->get_setting_or_default( $setting_name, $default_template );

		return $this->replace_placeholders( $template, $this->build_placeholder_map( $payload ) );
	}

	/**
	 * Формує текст повідомлення.
	 *
	 * @param array<string,mixed> $payload Дані листа.
	 */
	private function build_message( string $type, array $payload ): string {
		$setting_name = match ( $type ) {
			'sailing'  => 'SailboatsMailBodySailing',
			'combined' => 'SailboatsMailBodyCombined',
			default    => 'SailboatsMailBodyMembership',
		};

		$default_template = self::get_settings_defaults()[ $setting_name ]['value'] ?? '{signature}';
		$template         = $this->get_setting_or_default( $setting_name, $default_template );

		return $this->replace_placeholders( $template, $this->build_placeholder_map( $payload ) );
	}

	/**
	 * Повертає налаштування або fallback-значення.
	 */
	private function get_setting_or_default( string $setting_name, string $default ): string {
		$value = $this->get_repository()->get_setting_value( $setting_name );

		return '' !== trim( $value ) ? $value : $default;
	}

	/**
	 * Будує карту плейсхолдерів для шаблонів листа.
	 *
	 * @param array<string,mixed> $payload Дані листа.
	 * @return array<string,string>
	 */
	private function build_placeholder_map( array $payload ): array {
		$owner_name = trim( (string) ( $payload['owner_name'] ?? '' ) );
		$boat_name  = trim( (string) ( $payload['name'] ?? '' ) );
		$comment    = trim( (string) ( $payload['comment'] ?? '' ) );
		$signature  = $this->get_setting_or_default(
			'SailboatsMailSignature',
			self::get_settings_defaults()['SailboatsMailSignature']['value'] ?? ''
		);

		$resolved_owner = '' !== $owner_name ? $owner_name : __( 'члене ФСТУ', 'fstu' );
		$resolved_boat  = '' !== $boat_name ? '«' . $boat_name . '»' : __( 'вашого судна', 'fstu' );
		$comment_block  = '' !== $comment
			? __( 'Додатковий коментар:', 'fstu' ) . PHP_EOL . $comment
			: '';

		return [
			'{owner_name}'    => $resolved_owner,
			'{boat_name}'     => $resolved_boat,
			'{V1}'            => $this->normalize_due_value( $payload['V1'] ?? '' ),
			'{V2}'            => $this->normalize_due_value( $payload['V2'] ?? '' ),
			'{F1}'            => $this->normalize_due_value( $payload['F1'] ?? '' ),
			'{F2}'            => $this->normalize_due_value( $payload['F2'] ?? '' ),
			'{comment}'       => $comment,
			'{comment_block}' => $comment_block,
			'{signature}'     => trim( $signature ),
		];
	}

	/**
	 * Безпечно підставляє плейсхолдери в шаблон.
	 *
	 * @param array<string,string> $placeholders Карта замін.
	 */
	private function replace_placeholders( string $template, array $placeholders ): string {
		$result = strtr( $template, $placeholders );
		$result = preg_replace( "/\n{3,}/", PHP_EOL . PHP_EOL, $result );

		return is_string( $result ) ? trim( $result ) : trim( $template );
	}

	/**
	 * Повертає репозиторій налаштувань модуля.
	 */
	private function get_repository(): Sailboats_Repository {
		if ( null === $this->repository ) {
			$this->repository = new Sailboats_Repository();
		}

		return $this->repository;
	}

	/**
	 * Нормалізує значення індикатора внеску для листа.
	 *
	 * @param mixed $value Значення індикатора.
	 */
	private function normalize_due_value( $value ): string {
		$text = trim( (string) $value );

		return '' !== $text ? $text : '—';
	}
}

