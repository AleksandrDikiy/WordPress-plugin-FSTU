<?php

namespace FSTU\Modules\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Поштовий сервіс модуля "Заявки в ФСТУ".
 * Відповідає за формування та відправку листів через wp_mail().
 *
 * Version:     1.0.1
 * Date_update: 2026-04-24
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

        $subject = 'Вас успішно прийнято до Федерації спортивного туризму України!';

        $login_url = wp_login_url();
        $cabinet_url = home_url('/personal/');

        $message  = "Вітаємо у великій родині ФСТУ!\n\n";
        $message .= "Вашу заявку схвалено і вам присвоєно статус повноправного члена Федерації спортивного туризму України.\n\n";
        $message .= "Ваш офіційний номер членського квитка: " . $ticket_number . "\n\n";
        $message .= "Тепер ви маєте доступ до свого Особистого кабінету, де зберігається ваш електронний квиток, інформація про спортивні походи, розряди та членські внески.\n\n";
        $message .= "Увійти на сайт: " . $login_url . "\n";
        $message .= "Ваш Особистий кабінет: " . $cabinet_url . "\n\n";
        $message .= "З повагою,\nАдміністрація ФСТУ";

        $headers = [ 'Content-Type: text/plain; charset=UTF-8' ];

        if ( '' !== $validated_from ) {
            $headers[] = 'From: ' . $validated_from;
            $headers[] = 'Reply-To: ' . $validated_from;
        }

        return (bool) wp_mail( $to, $subject, $message, $headers );
    }
}

