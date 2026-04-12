<?php
/**
 * View: Головна сторінка плагіна в адмінці.
 *
 * Version:     1.14.0
 * Date_update: 2026-04-12
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
  $participationtype_page_url = isset( $participationtype_page_url ) ? (string) $participationtype_page_url : '';
$tourtype_page_url = isset( $tourtype_page_url ) ? (string) $tourtype_page_url : '';
$tourismtype_page_url = isset( $tourismtype_page_url ) ? (string) $tourismtype_page_url : '';
$referees_page_url = isset( $referees_page_url ) ? (string) $referees_page_url : '';
$recorders_page_url = isset( $recorders_page_url ) ? (string) $recorders_page_url : '';
$mkk_page_url = isset( $mkk_page_url ) ? (string) $mkk_page_url : '';
$guidance_page_url = isset( $guidance_page_url ) ? (string) $guidance_page_url : '';
$member_card_applications_page_url = isset( $member_card_applications_page_url ) ? (string) $member_card_applications_page_url : '';
$steering_page_url = isset( $steering_page_url ) ? (string) $steering_page_url : '';
$personal_cabinet_page_url = isset( $personal_cabinet_page_url ) ? (string) $personal_cabinet_page_url : '';
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
                                <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_member_card_applications]</code></th>
                                <td>
                                    <strong>Посвідчення членів ФСТУ</strong><br>
                                    Службовий модуль нового покоління для ведення посвідчень членів ФСТУ: список із фільтрами, картка посвідчення, протокол змін, а також підготовлений owner/self-service flow через особистий кабінет. Для ролі administrator доступне повне керування включно з номером картки і видаленням, для userregistrar та globalregistrar — службове керування без ручної зміни номера картки.
                                    <?php if ( '' !== $member_card_applications_page_url ) : ?>
                                        <br><a href="<?php echo esc_url( $member_card_applications_page_url ); ?>">Відкрити сторінку модуля →</a>
                                    <?php endif; ?>
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
                                <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_recorders]</code></th>
                                <td>
                                    <strong>Реєстратори ФСТУ</strong><br>
                                    Виводить службовий реєстр призначень реєстраторів по осередках ФСТУ з фільтром по осередку, пошуком по ПІБ у шапці таблиці, compact-пагінацією, dropdown-меню «Дії», модальними вікнами перегляду/редагування та окремим розділом «ПРОТОКОЛ». Для ролей administrator і globalregistrar доступне повне керування, а колонка ПІБ веде на legacy-сторінку профілю `/Personal?ViewID=...`.
                                    <?php if ( '' !== $recorders_page_url ) : ?>
                                        <br><a href="<?php echo esc_url( $recorders_page_url ); ?>">Відкрити сторінку реєстру →</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_mkk]</code></th>
                                <td>
                                    <strong>Реєстр членів МКК ФСТУ</strong><br>
                                                  Публічний модуль нового покоління для ведення складу МКК: список з фільтрами за областю, типом комісії та видом туризму, пошук по ПІБ у шапці таблиці, compact-пагінація, dropdown-меню «Дії», картка запису, CRUD для службових ролей та окремий розділ «ПРОТОКОЛ». Публічний перегляд доступний навіть гостям, але `ПРОТОКОЛ`, додавання, редагування та видалення залишаються тільки для ролей `administrator`, `globalregistrar` і `userregistrar`. Для гостей показується скорочене ПІБ, для авторизованих — повне ПІБ, а перехід у legacy-профіль веде на `/Personal/?ViewID=...`.
                                    <?php if ( '' !== $mkk_page_url ) : ?>
                                        <br><a href="<?php echo esc_url( $mkk_page_url ); ?>">Відкрити сторінку реєстру →</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_guidance]</code></th>
                                <td>
                                    <strong>Склад керівних органів ФСТУ</strong><br>
                                    Публічний модуль нового покоління для ведення складу керівних органів ФСТУ: список із фільтром по керівному органу, пошуком у шапці таблиці, compact-пагінацією, dropdown-меню «Дії», карткою запису з колонки «Посада» та окремим розділом «ПРОТОКОЛ». Гості бачать основний список, усі авторизовані користувачі можуть відкривати картку запису, а CRUD і протокол доступні лише ролям `administrator` і `globalregistrar`.
                                    <?php if ( '' !== $guidance_page_url ) : ?>
                                        <br><a href="<?php echo esc_url( $guidance_page_url ); ?>">Відкрити сторінку модуля →</a>
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
                                <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_merilkas]</code></th>
                                <td>
                                    <strong>Реєстр обмірних свідоцтв (Мерилок)</strong><br>
                                    Новий незалежний модуль для розрахунку гоночного бала (ГБ) та керування фізичними параметрами вітрильних суден. Має подвійну серверну валідацію за формулою Герона, live-калькулятор на фронтенді, автоматичне блокування видалення свідоцтв, що брали участь у змаганнях, та генерацію друкованого свідоцтва (HTML/PDF) формату А4.
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_personal_cabinet]</code></th>
                                <td>
                                    <strong>Особистий кабінет ФСТУ</strong><br>
                                    Виводить модуль «Особистий кабінет ФСТУ» з вкладками профілю, секцією «ПРОТОКОЛ», інтеграціями `Referees`, `PaymentDocs`, `Steering`, `Sailboats`, контрольованим Portmone-flow та централізованою capability-моделлю.
                                    <?php if ( '' !== $personal_cabinet_page_url ) : ?>
                                        <br><a href="<?php echo esc_url( $personal_cabinet_page_url ); ?>">Відкрити сторінку кабінету →</a>
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
                                              <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_participationtype]</code></th>
                                              <td>
                                                <strong>Довідник видів участі в заходах ФСТУ</strong><br>
                                                Виводить довідник видів участі з пошуком у шапці таблиці, колонкою «Тип», compact-пагінацією, drag-and-drop сортуванням, dropdown-меню «Дії», модальними вікнами перегляду/редагування та окремим розділом «ПРОТОКОЛ». Для всіх авторизованих користувачів доступний перегляд списку й картки, для ролей `administrator`, `globalregistrar`, `userregistrar` — create/update, а delete залишено лише для `administrator` і `globalregistrar`.
                                                <?php if ( '' !== $participationtype_page_url ) : ?>
                                                  <br><a href="<?php echo esc_url( $participationtype_page_url ); ?>">Відкрити сторінку довідника →</a>
                                                <?php endif; ?>
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
                                <th scope="row"><code style="font-size: 16px; padding: 5px 10px;">[fstu_tourtype]</code></th>
                                <td>
                                    <strong>Довідник видів походів</strong><br>
                                    Виводить публічний довідник видів походів з фільтром по категорії складності, пошуком у шапці таблиці, compact-пагінацією, dropdown-меню «Дії», модальними вікнами перегляду/редагування та окремим розділом «ПРОТОКОЛ». Перегляд списку і картки доступний гостям та авторизованим користувачам, а create/update/delete/protocol — лише ролям `administrator` і `globalregistrar`.
                                    <?php if ( '' !== ( $tourtype_page_url ?? '' ) ) : ?>
                                        <br><a href="<?php echo esc_url( (string) ( $tourtype_page_url ?? '' ) ); ?>">Відкрити сторінку довідника →</a>
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