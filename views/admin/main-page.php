<?php
/**
 * View: Головна сторінка плагіна в адмінці.
 *
 * Version:     1.8.0
 * Date_update: 2026-04-08
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$version = isset( $version ) ? (string) $version : ( defined( 'FSTU_VERSION' ) ? FSTU_VERSION : 'n/a' );
$commission_page_url = isset( $commission_page_url ) ? (string) $commission_page_url : '';
$typeguidance_page_url = isset( $typeguidance_page_url ) ? (string) $typeguidance_page_url : '';
$member_regional_page_url = isset( $member_regional_page_url ) ? (string) $member_regional_page_url : '';
$member_guidance_page_url = isset( $member_guidance_page_url ) ? (string) $member_guidance_page_url : '';
$country_page_url = isset( $country_page_url ) ? (string) $country_page_url : '';
$region_page_url = isset( $region_page_url ) ? (string) $region_page_url : '';
$city_page_url = isset( $city_page_url ) ? (string) $city_page_url : '';
$eventtype_page_url = isset( $eventtype_page_url ) ? (string) $eventtype_page_url : '';
$tourismtype_page_url = isset( $tourismtype_page_url ) ? (string) $tourismtype_page_url : '';
$referees_page_url = isset( $referees_page_url ) ? (string) $referees_page_url : '';
$steering_page_url = isset( $steering_page_url ) ? (string) $steering_page_url : '';
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
                                <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_applications]</code></th>
                                <td>
                                    <strong>Заявки в ФСТУ</strong><br>
                                    Службовий модуль для перегляду та обробки заявок на вступ до ФСТУ. Розміщуйте його лише на внутрішній сторінці для адміністраторів і реєстраторів.
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_referees]</code></th>
                                <td>
                                    <strong>Реєстр суддів ФСТУ</strong><br>
                                    Виводить реєстр суддів з пошуком по ПІБ, фільтрами за областю і категорією, compact-пагінацією, карткою судді, довідками за суддівство та розділом «ПРОТОКОЛ». Для ролей administrator і referee доступне повне керування, для userregistrar і userfstu — лише перегляд.
                                    <?php if ( '' !== $referees_page_url ) : ?>
                                        <br><a href="<?php echo esc_url( $referees_page_url ); ?>">Відкрити сторінку реєстру →</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_steering]</code></th>
                                <td>
                                    <strong>Реєстр стернових ФСТУ</strong><br>
                                    Публічний реєстр посвідчень стернових із пошуком, compact-пагінацією, карткою запису, verify-flow, службовими статусами заявки та розділом «ПРОТОКОЛ». Для ролей administrator і sailadministrator передбачене повне керування, для userfstu — подання лише власної заявки за відсутності попереднього запису/заявки, а для гостей — публічний перегляд актуальних посвідчень.
                                    <?php if ( '' !== $steering_page_url ) : ?>
                                        <br><a href="<?php echo esc_url( $steering_page_url ); ?>">Відкрити сторінку реєстру →</a>
                                    <?php endif; ?>
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
                                <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_commission]</code></th>
                                <td>
                                    <strong>Довідник комісій та колегій ФСТУ</strong><br>
                                    Виводить список комісій і колегій з пошуком, пагінацією, протоколом, drag-and-drop сортуванням та модальними вікнами перегляду/редагування. Розміщується на окремій сторінці сайта через shortcode.
                                    <?php if ( '' !== $commission_page_url ) : ?>
                                        <br><a href="<?php echo esc_url( $commission_page_url ); ?>">Відкрити сторінку довідника →</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_typeguidance]</code></th>
                                <td>
                                    <strong>Довідник керівних органів ФСТУ</strong><br>
                                    Виводить таблицю керівних органів з пошуком, пагінацією, протоколом і модальною формою створення/редагування. Для ролі userfstu доступний лише перегляд списку, для адміністратора — повне керування.
                                    <?php if ( '' !== $typeguidance_page_url ) : ?>
                                        <br><a href="<?php echo esc_url( $typeguidance_page_url ); ?>">Відкрити сторінку довідника →</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_member_regional]</code></th>
                                <td>
                                    <strong>Довідник посад федерацій</strong><br>
                                    Виводить список посад федерацій з пошуком, пагінацією, протоколом і модальною формою перегляду/редагування. Для ролі userregistrar доступні перегляд, додавання та редагування, для адміністратора — повне керування і перегляд протоколу.
                                    <?php if ( '' !== $member_regional_page_url ) : ?>
                                        <br><a href="<?php echo esc_url( $member_regional_page_url ); ?>">Відкрити сторінку довідника →</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_member_guidance]</code></th>
                                <td>
                                    <strong>Довідник посад у керівних органах ФСТУ</strong><br>
                                    Виводить список посад з фільтром по типу керівного органу, пошуком у шапці таблиці, пагінацією, протоколом і shared-модалкою. Для ролі userfstu доступний лише перегляд, для userregistrar — create/update та delete only own, для адміністратора — повне керування.
                                    <?php if ( '' !== $member_guidance_page_url ) : ?>
                                        <br><a href="<?php echo esc_url( $member_guidance_page_url ); ?>">Відкрити сторінку довідника →</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_country]</code></th>
                                <td>
                                    <strong>Довідник країн</strong><br>
                                    Виводить список країн з пошуком у шапці таблиці, compact-пагінацією, shared-модалкою та розділом «ПРОТОКОЛ». Для ролей administrator і userregistrar доступні CRUD-операції, а видалення дозволяється лише після перевірки залежностей запису.
                                    <?php if ( '' !== $country_page_url ) : ?>
                                        <br><a href="<?php echo esc_url( $country_page_url ); ?>">Відкрити сторінку довідника →</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_region]</code></th>
                                <td>
                                    <strong>Довідник областей</strong><br>
                                    Виводить список областей з пошуком у шапці таблиці, compact-пагінацією, shared-модалкою та розділом «ПРОТОКОЛ». Для ролі userregistrar доступні перегляд і протокол, для адміністратора — повне керування, а видалення дозволяється лише після перевірки залежностей запису.
                                    <?php if ( '' !== $region_page_url ) : ?>
                                        <br><a href="<?php echo esc_url( $region_page_url ); ?>">Відкрити сторінку довідника →</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_city]</code></th>
                                <td>
                                    <strong>Довідник міст</strong><br>
                                    Виводить список міст з колонкою області, пошуком у шапці таблиці, compact-пагінацією, shared-модалкою та розділом «ПРОТОКОЛ». Для ролі userregistrar доступні перегляд, створення, редагування та видалення, для адміністратора — повне керування модулем.
                                    <?php if ( '' !== $city_page_url ) : ?>
                                        <br><a href="<?php echo esc_url( $city_page_url ); ?>">Відкрити сторінку довідника →</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_eventtype]</code></th>
                                <td>
                                    <strong>Довідник типів заходів</strong><br>
                                    Виводить список типів заходів з пошуком у шапці таблиці, compact-пагінацією, shared-модалкою та розділом «ПРОТОКОЛ». Для ролі userregistrar доступні перегляд, створення, редагування та видалення, для адміністратора — повне керування модулем.
                                    <?php if ( '' !== ( $eventtype_page_url ?? '' ) ) : ?>
                                        <br><a href="<?php echo esc_url( (string) ( $eventtype_page_url ?? '' ) ); ?>">Відкрити сторінку довідника →</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_tourismtype]</code></th>
                                <td>
                                    <strong>Довідник типів туризму</strong><br>
                                    Виводить список типів туризму з пошуком у шапці таблиці, compact-пагінацією, shared-модалкою та розділом «ПРОТОКОЛ». Для ролі userregistrar доступні перегляд, створення, редагування та видалення, для адміністратора — повне керування модулем.
                                    <?php if ( '' !== ( $tourismtype_page_url ?? '' ) ) : ?>
                                        <br><a href="<?php echo esc_url( (string) ( $tourismtype_page_url ?? '' ) ); ?>">Відкрити сторінку довідника →</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_dictionaries_hub]</code></th>
                                <td>
                                    <strong>Довідники (Хаб)</strong><br>
                                    сучасний дашборд з 21-м довідником, розбитими по категоріях, з живим пошуком
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