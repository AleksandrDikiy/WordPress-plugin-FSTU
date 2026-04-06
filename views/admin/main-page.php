<?php
/**
 * View: Головна сторінка плагіна в адмінці.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$version = isset( $version ) ? (string) $version : ( defined( 'FSTU_VERSION' ) ? FSTU_VERSION : 'n/a' );
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Федерація спортивного туризму України</h1>
    <hr class="wp-header-end">

    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">

            <div id="post-body-content">

                <div class="postbox">
                    <h2 class="hndle" style="padding: 12px 15px;"><span>Доступні шорткоди</span></h2>
                    <div class="inside">
                        <p>Скопіюйте потрібний шорткод та вставте його на будь-яку сторінку сайту. Система автоматично обмежить доступ для неавторизованих користувачів там, де це потрібно.</p>

                        <table class="form-table" role="presentation">
                            <tbody>
                            <tr>
                                <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_registry]</code></th>
                                <td>
                                    <strong>Головний реєстр членів ФСТУ</strong><br>
                                    Виводить велику таблицю з учасниками, фільтрами (ОФСТ, клуби, роки), пагінацією та модальними вікнами (Картка члена, Протоколи, Звіти). Дозволяє незареєстрованим подавати заявку на вступ.
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_payment_docs]</code></th>
                                <td>
                                    <strong>Реєстр платіжних документів (Групові платежі)</strong><br>
                                    Виводить фінансовий модуль для реєстраторів та адміністраторів. Дозволяє створювати, редагувати та видаляти масові оплати внесків.
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_clubs]</code></th>
                                <td>
                                    <strong>Довідник клубів ФСТУ</strong><br>
                                    Виводить таблицю з переліком спортивних клубів, їхніми адресами та сайтами. Користувачі можуть переглядати детальну інформацію про клуб та кількість його учасників. Дозволяє адміністраторам та реєстраторам додавати, редагувати та безпечно видаляти клуби.
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_units]</code></th>
                                <td>
                                    <strong>Довідник осередків ФСТУ</strong><br>
                                    Виводить таблицю осередків з пошуком, фільтрами за регіоном і типом, пагінацією та модальними вікнами перегляду. Дозволяє адміністраторам та реєстраторам додавати, редагувати й безпечно видаляти осередки з урахуванням перевірок зв’язків у базі даних.
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_typeevent]</code></th>
                                <td>
                                    <strong>Довідник видів змагань ФСТУ</strong><br>
                                    Виводить таблицю видів змагань з пошуком, пагінацією та модальними вікнами перегляду/редагування. Дозволяє адміністраторам та реєстраторам додавати й редагувати записи, а адміністраторам — видаляти їх без inline-коду та з AJAX-захистом nonce.
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_dictionaries_hub]</code></th>
                                <td>
                                    <strong>Довідники (Хаб)</strong><br>
                                    сучасний дашборд з усіма 13-ма довідниками, розбитими по категоріях, з живим пошуком
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div> <div id="postbox-container-1" class="postbox-container">

                <div class="postbox">
                    <h2 class="hndle" style="padding: 12px 15px;"><span>Про плагін</span></h2>
                    <div class="inside">
                        <p><strong>Версія:</strong> <?php echo esc_html( $version ); ?></p>
                        <p><strong>Призначення:</strong> Комплексна система управління базою даних членів ФСТУ, автоматизація фінансового обліку (сплата внесків) та генерація звітної документації.</p>
                        <hr>
                        <p style="color: #72777c; font-size: 12px;">Розроблено спеціально для Федерації спортивного туризму України.</p>
                    </div>
                </div>

            </div> </div>
    </div>
</div>