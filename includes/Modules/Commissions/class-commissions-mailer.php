<?php
/**
 * Mailer Service для відправки сповіщень модуля "Комісії з видів туризму (Board)".
 *
 * * Version: 1.0.0
 * Date_update: 2026-04-18
 */

namespace FSTU\Modules\Commissions;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Commissions_Mailer {

    /**
     * Відправляє сповіщення про початок опитування.
     *
     * @param array $poll_data Дані опитування (назва, дати, посилання тощо).
     * @param int   $s_commission_id ID комісії для визначення отримувача.
     * @return bool
     */
    public function send_poll_notification( array $poll_data, int $s_commission_id ): bool {

        $repository = new Commissions_Repository();
        $to_email   = $repository->get_commission_email_group( $s_commission_id );

        if ( empty( $to_email ) || ! is_email( $to_email ) ) {
            return false;
        }

        $subject = 'ОПИТУВАННЯ на сайті ФСТУ: ' . sanitize_text_field( $poll_data['Question_Name'] );

        $message = $this->build_email_html( $poll_data );
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ФСТУ-робот <fstu.com.ua@gmail.com>',
            'Reply-To: fstu.com.ua@gmail.com',
        ];

        // Логування факту відправки
        $protocol = new Commissions_Protocol_Service();
        $sent = wp_mail( $to_email, $subject, $message, $headers );

        if ( $sent ) {
            $protocol->log_action( 'M', 'Відправлено email сповіщення для опитування ID: ' . $poll_data['Question_ID'], '✓' );
        } else {
            $protocol->log_action( 'M', 'Помилка відправки email для опитування ID: ' . $poll_data['Question_ID'], 'error' );
        }

        return $sent;
    }

    /**
     * Формує HTML тіло листа.
     *
     * @param array $poll_data
     * @return string
     */
    private function build_email_html( array $poll_data ): string {
        $url = site_url( '/board/?ViewPoll=' . absint( $poll_data['Question_ID'] ) ); // Приклад URL

        ob_start();
        ?>
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">
            <h2 style="color: #d9534f;">Вітаємо Вас!</h2>
            <p>Це автоматичне повідомлення з сайту Федерації спортивного туризму України.</p>

            <div style="background-color: #f8f9fa; border-left: 4px solid #d9534f; padding: 15px; margin: 20px 0;">
                <p><strong>НАЙМЕНУВАННЯ ОПИТУВАННЯ:</strong> <?php echo esc_html( $poll_data['Question_Name'] ); ?></p>
                <p><strong>ПОЧАТОК ОПИТУВАННЯ:</strong> <?php echo esc_html( wp_date( 'd.m.Y', strtotime( $poll_data['Question_DateBegin'] ) ) ); ?></p>
                <?php if ( ! empty( $poll_data['Question_Note'] ) ) : ?>
                    <p><strong>ДЕТАЛЬНИЙ ОПИС:</strong> <?php echo esc_html( $poll_data['Question_Note'] ); ?></p>
                <?php endif; ?>
                <?php if ( ! empty( $poll_data['Question_URL'] ) ) : ?>
                    <p><strong>ДОКУМЕНТ:</strong> <a href="<?php echo esc_url( $poll_data['Question_URL'] ); ?>">Переглянути документ</a></p>
                <?php endif; ?>
            </div>

            <p style="text-align: center; margin: 30px 0;">
                <a href="<?php echo esc_url( $url ); ?>" style="background-color: #d9534f; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: bold;">Перейти до опитування</a>
            </p>

            <p style="font-size: 12px; color: #777;">PS. Для участі в опитуванні необхідно бути авторизованим на сайті ФСТУ.</p>
            <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
            <p style="font-size: 12px; color: #777;">З повагою,<br>ФСТУ-робот автоматичного листування.</p>
        </div>
        <?php
        return ob_get_clean();
    }
}