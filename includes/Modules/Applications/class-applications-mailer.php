<?php

namespace FSTU\Modules\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Поштовий сервіс модуля "Заявки в ФСТУ".
 * Відповідає за формування та відправку листів через wp_mail().
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 */
class Applications_Mailer {

    /**
     * Відправляє лист після успішного прийняття кандидата.
     */
    public function send_acceptance_email( \WP_User $user, int $ticket_number, string $from_email ): bool {
        $to = sanitize_email( $user->user_email );
        if ( '' === $to || ! is_email( $to ) ) {
            return false;
        }

        $validated_from = sanitize_email( $from_email );
        if ( ! is_email( $validated_from ) ) {
            $validated_from = (string) get_option( 'admin_email' );
        }

        $subject = 'Вас прийнято до членів ФСТУ';
        $message = 'Вітаємо! Вашу заявку схвалено. Ваш номер квитка: ' . $ticket_number;
        $headers = [ 'Content-Type: text/plain; charset=UTF-8' ];

        if ( '' !== $validated_from ) {
            $headers[] = 'From: ' . $validated_from;
            $headers[] = 'Reply-To: ' . $validated_from;
        }

        return (bool) wp_mail( $to, $subject, $message, $headers );
    }
}

