# Технічне завдання: модуль «Реєстр суддів ФСТУ»

**Дата:** 2026-04-08  
**Документ:** Technical Specification  
**Модуль:** `Referees`  
**Домен:** `includes/Modules/Registry/Referees/`

---

## 1. Мета документа

Це технічне завдання визначає вимоги до реалізації нового модуля **«Реєстр суддів ФСТУ»** у плагіні `fstu_new` як повноцінної OOP-замiни legacy-сторінки `Referee.php`.

Документ фіксує:
- функціональний обсяг модуля;
- цільову архітектуру;
- рольову модель доступу;
- вимоги до UI/UX;
- вимоги до безпеки, SQL, AJAX і логування;
- критерії приймання результату;
- обмеження та legacy-сумісність.

---

## 2. Джерела та підстава для розробки

Розробка виконується на основі:
- `AGENTS.md`
- `PLAN-REFEREES.md`
- legacy-файлу `plugins/fstu/templates/Referee.php`
- SQL-схем:
  - `CertificatesForRefereeing.sql`
  - `S_RefereeCategory.sql`
- поточних архітектурних рішень плагіна `fstu_new`
- модульного еталона `Sailboats`
- адміністративної сторінки `views/admin/main-page.php`

---

## 3. Призначення модуля

Модуль призначений для:
- ведення реєстру суддів ФСТУ;
- перегляду картки судді;
- керування суддівськими записами;
- ведення довідок за суддівство;
- призначення та зняття категорії довідки;
- ведення протоколу змін по реєстру та довідках.

Модуль працює через **shortcode-сторінку** на фронтенді та має бути відображений в адмінці у загальному описі shortcode плагіна.

---

## 4. Scope модуля

### 4.1. У scope входить

1. **Реєстр суддів**
   - список;
   - пошук;
   - фільтрація;
   - пагінація;
   - картка судді;
   - create/update/delete `Referee`.

2. **Довідки за суддівство**
   - список довідок для конкретного судді;
   - створення довідки;
   - призначення категорії довідці;
   - зняття категорії з довідки;
   - перегляд пов’язаних даних.

3. **Протокол**
   - перегляд логів модуля;
   - пошук по журналу;
   - пагінація протоколу;
   - окреме логування реєстру і довідок.

4. **Інтеграція в плагін**
   - shortcode;
   - bootstrap у `fstu.php`;
   - опис shortcode в адмінці;
   - посилання на сторінку модуля в адмінці.

### 4.2. Поза scope першої реалізації

На поточному етапі **не входить**:
- upload файлів замість URL;
- email/notification flow;
- bulk actions;
- імпорт/експорт записів;
- REST API;
- окремий workflow погодження довідок;
- логування `VIEW` перегляду картки.

---

## 5. Сутності та дані

### 5.1. Основні сутності

- `Referee`
- `CertificatesForRefereeing`
- `Logs`
- `S_RefereeCategory`
- `vReferee`
- `vRefereeCategory`
- `vCertificatesForRefereeing`
- `vCalendar`
- `vUserFSTU`
- `vUserMemberCard`
- `Settings`

### 5.2. Підтверджена схема `CertificatesForRefereeing`

Таблиця `CertificatesForRefereeing` має структуру:
- `CertificatesForRefereeing_ID` — PK, `AUTO_INCREMENT`
- `User_ID` — `bigint(20) NOT NULL`
- `Calendar_ID` — `int(11) NOT NULL`
- `RefereeCategory_ID` — `int(11) DEFAULT NULL`
- `CertificatesForRefereeing_DateCreate` — `datetime NOT NULL`
- `CertificatesForRefereeing_URL` — `varchar(300) NOT NULL`

Індекси та обмеження:
- `PRIMARY KEY (CertificatesForRefereeing_ID)`
- `UNIQUE(User_ID, Calendar_ID)`
- індекси по `User_ID`, `Calendar_ID`, `RefereeCategory_ID`

### 5.3. Довідник категорій

Категорії беруться з таблиці `S_RefereeCategory`:
- `RefereeCategory_ID`
- `RefereeCategory_Name`
- `RefereeCategory_NameShort`
- `RefereeCategory_Order`

`RefereeCategory_ID` у `CertificatesForRefereeing` повинен валідуватися відносно `S_RefereeCategory` / `vRefereeCategory`.

---

## 6. Цільова архітектура

### 6.1. Директорії та файли

```text
fstu_new/
├── includes/
│   └── Modules/
│       └── Registry/
│           └── Referees/
│               ├── class-referees-list.php
│               ├── class-referees-ajax.php
│               ├── class-referees-repository.php
│               ├── class-referees-service.php
│               └── class-referees-protocol-service.php
│
├── views/
│   └── referees/
│       ├── main-page.php
│       ├── table-list.php
│       ├── protocol-table-list.php
│       ├── modal-view.php
│       ├── modal-form.php
│       ├── modal-certificates.php
│       ├── modal-certificate-form.php
│       └── modal-certificate-bind-category.php
│
├── js/
│   └── fstu-referees.js
│
└── css/
    └── fstu-referees.css
```

### 6.2. Архітектурні ролі класів

#### `class-referees-list.php`
Відповідає за:
- реєстрацію shortcode;
- enqueue `css/js`;
- `wp_localize_script()`;
- підготовку permission flags;
- `get_module_url()`;
- підготовку даних для шаблону.

#### `class-referees-ajax.php`
Відповідає за:
- реєстрацію AJAX action-ів;
- nonce-check;
- capability-check;
- санітизацію input;
- виклик service-методів;
- `wp_send_json_success/error`.

#### `class-referees-repository.php`
Відповідає за:
- усі `SELECT/INSERT/UPDATE/DELETE`;
- читання з таблиць і views;
- роботу лише через `$wpdb->prepare()`.

#### `class-referees-service.php`
Відповідає за:
- бізнес-правила;
- orchestration repository + protocol service;
- транзакції;
- create/update/delete flows;
- list/view payloads.

#### `class-referees-protocol-service.php`
Відповідає за:
- логування в `Logs`;
- розділення логів між `Referee` і `RefereeDoc`;
- побудову protocol rows;
- strict success logging у межах транзакцій.

---

## 7. Role model і права доступу

### 7.1. Ролі модуля

#### `administrator`
Має:
- повний доступ до списку;
- create/update/delete `Referee`;
- create/update/unbind для довідок;
- доступ до протоколу;
- доступ до всіх службових дій.

#### `referee`
Має:
- повний доступ до **всіх** записів модуля;
- create/update/delete `Referee`;
- create/update/unbind по довідках;
- доступ до протоколу — за окремим capability-рішенням модуля (рекомендовано дозволити, якщо це відповідає legacy-очікуванню; інакше лишити адміністратору та зафіксувати окремо при реалізації).

#### `userregistrar`
Має:
- **тільки перегляд**;
- без create/update/delete;
- без зміни довідок;
- доступ до протоколу — лише якщо окремо буде надано capability, інакше ні.

#### `userfstu`
Має:
- тільки перегляд;
- без службових дій;
- без create/update/delete;
- без зміни довідок;
- без доступу до протоколу, якщо окремо не буде погоджено інше.

### 7.2. Орієнтовні capability-константи

У `includes/Core/class-capabilities.php` потрібно додати:
- `VIEW_REFEREES`
- `MANAGE_REFEREES`
- `DELETE_REFEREES`
- `VIEW_REFEREES_PROTOCOL`
- `MANAGE_REFEREE_CERTIFICATES`
- `DELETE_REFEREE_CERTIFICATES` або окрему capability для `UNBIND_REFEREE_CERTIFICATES_CATEGORY`

### 7.3. Helper доступів

Має бути реалізовано helper:

```php
Capabilities::get_referees_permissions()
```

Очікувані прапорці для фронтенду:
- `canView`
- `canManage`
- `canDelete`
- `canProtocol`
- `canManageCertificates`
- `canUnbindCertificates`

---

## 8. Shortcode і інтеграція в адмінку

### 8.1. Frontend shortcode

Модуль повинен відкриватися через окрему сторінку з shortcode.

Рекомендований shortcode:

```text
[fstu_referees]
```

### 8.2. Вимога до адмін-опису shortcode

У `views/admin/main-page.php` потрібно **додати новий рядок** у загальний блок **«Доступні шорткоди»**.

Новий блок повинен містити:
- shortcode модуля;
- короткий функціональний опис;
- пояснення про рольову модель;
- посилання на сторінку модуля, якщо URL визначений.

### 8.3. Вимога до `Admin_Menu`

У `includes/Admin/class-admin-menu.php` потрібно:
- підготувати URL сторінки модуля за аналогією з чинними модулями;
- передати цей URL у `views/admin/main-page.php`;
- забезпечити відображення посилання на сторінку реєстру суддів.

---

## 9. Функціональні вимоги

## 9.1. Основний список суддів

Модуль повинен підтримувати:
- вивід списку суддів;
- пошук по ПІБ;
- фільтр по області (`Region_ID`);
- фільтр по суддівській категорії (`RefereeCategory_ID`);
- compact pagination;
- нумерацію рядків;
- колонку дій з dropdown-меню.

### Обов’язкові поля списку

Мінімально список повинен відображати:
- № з/п;
- реєстраційний/службовий номер, якщо він є у view;
- ПІБ;
- категорію;
- кількість/наявність довідок;
- регіон;
- дії.

## 9.2. Пошук і фільтри

Пошук повинен:
- працювати через AJAX;
- бути розміщений у шапці таблиці;
- використовувати `LIKE` тільки через `$wpdb->esc_like()` + `$wpdb->prepare()`;
- підтримувати безпечне очищення та повторне завантаження списку.

Фільтри повинні:
- працювати без `$_SESSION`;
- зберігатися в JS-state модуля;
- перезавантажувати список через AJAX.

## 9.3. Перегляд картки судді

Картка судді повинна містити:
- ПІБ;
- суддівську категорію;
- номер наказу;
- дату наказу;
- URL наказу;
- дату створення запису;
- хто створив запис;
- блок довідок за суддівство.

## 9.4. Створення судді

Форма створення повинна підтримувати поля:
- `User_ID`
- `RefereeCategory_ID`
- `NumOrder`
- `DateOrder`
- `URLOrder`
- `fstu_website` (honeypot)

Вимоги:
- `User_ID` повинен бути валідним;
- створення не повинно дублювати вже існуючий суддівський запис того самого користувача, якщо це забороняє схема/бізнес-правило;
- `RefereeCategory_ID` має існувати в довіднику.

## 9.5. Редагування судді

Повинно підтримувати оновлення:
- категорії;
- номера наказу;
- дати наказу;
- URL наказу.

## 9.6. Видалення судді

Повинно:
- бути доступним лише ролям із відповідним правом;
- проходити перевірку залежностей;
- логуватися транзакційно;
- повертати безпечне повідомлення при помилці.

## 9.7. Довідки за суддівство

Для конкретного судді модуль повинен показувати список довідок із:
- датою створення;
- заходом (`Calendar_ID` / назва заходу);
- URL довідки;
- поточною прив’язаною категорією.

## 9.8. Створення довідки

Форма довідки повинна містити:
- `Calendar_ID`
- `CertificatesForRefereeing_URL`
- `User_ID`
- `fstu_website` (honeypot)

Вимоги:
- `Calendar_ID` має бути валідним;
- `CertificatesForRefereeing_URL` має бути текстовим URL-полем;
- має враховуватися унікальність `UNIQUE(User_ID, Calendar_ID)`.

## 9.9. Призначення категорії довідці

Операція повинна:
- змінювати `RefereeCategory_ID` у `CertificatesForRefereeing`;
- працювати окремою дією;
- логуватися через `Logs_Name = 'RefereeDoc'`;
- перевіряти існування категорії в `S_RefereeCategory`.

## 9.10. Зняття категорії з довідки

Legacy-сценарій `DeleteCertRefID` повинен бути перенесений як:
- **unbind category**, а не фізичне видалення довідки.

Операція повинна:
- встановлювати `RefereeCategory_ID = NULL`;
- не видаляти запис `CertificatesForRefereeing`;
- логуватися як окрема дія в `Logs_Name = 'RefereeDoc'`.

## 9.11. Протокол

Модуль повинен мати окремий розділ **«ПРОТОКОЛ»**.

Журнал повинен показувати:
- дату;
- тип;
- операцію;
- повідомлення;
- статус;
- користувача.

Пошук має працювати по:
- `Logs_Text`
- `FIO`

Потрібно підтримувати два потоки журналу:
- `Referee` — для основного реєстру;
- `RefereeDoc` — для довідок.

---

## 10. AJAX-вимоги

### 10.1. Обов’язкові action-и

Потрібно реалізувати щонайменше:
- `handle_get_list()`
- `handle_get_single()`
- `handle_create()`
- `handle_update()`
- `handle_delete()`
- `handle_get_dictionaries()`
- `handle_get_protocol()`
- `handle_get_certificates()`
- `handle_create_certificate()`
- `handle_bind_certificate_category()`
- `handle_unbind_certificate_category()`

### 10.2. Вимоги до кожного handler-а

Кожен handler повинен:
- починатися з `check_ajax_referer()`;
- перевіряти capability;
- санітизувати input;
- не використовувати сирі `$_POST / $_GET` без очищення;
- повертати лише безпечні повідомлення.

---

## 11. UI/UX-вимоги

### 11.1. Загальний стандарт

Модуль повинен відповідати єдиному UI/UX-стандарту з `AGENTS.md`.

### 11.2. Обов’язкові елементи

- action bar у стилі FSTU;
- кнопка `ПРОТОКОЛ`;
- кнопка `ДОВІДНИК` для повернення;
- зелена шапка таблиці;
- zebra rows;
- пошук у шапці таблиці;
- compact pagination тільки внизу;
- dropdown у колонці дій.

### 11.3. Модалки

Повинні бути реалізовані окремі модалки для:
- перегляду судді;
- create/edit судді;
- списку довідок;
- create довідки;
- bind/unbind категорії.

### 11.4. Assets

- без inline JS;
- без inline CSS;
- `js/fstu-referees.js`;
- `css/fstu-referees.css`;
- стилі підключати без залежності від `fstu-registry`, якщо немає гарантії спільної сторінки.

---

## 12. Безпека

Модуль повинен відповідати вимогам `AGENTS.md`:

### 12.1. SQL
- тільки `$wpdb->prepare()`;
- для `LIKE` — тільки `$wpdb->esc_like()`;
- без конкатенації користувацького input у SQL.

### 12.2. Input
- `absint()` для ID;
- `sanitize_text_field()` для текстових полів;
- `sanitize_textarea_field()` для текстових коментарів;
- URL поля додатково валідовувати як текстове посилання.

### 12.3. Forms
- honeypot у create/update формах;
- відхилення бот-запитів;
- nonce для всіх AJAX-операцій.

### 12.4. Output
- late escaping;
- `esc_html()`;
- `esc_attr()`;
- `esc_url()`;
- без виводу технічних SQL-помилок на фронтенд.

---

## 13. Логування і транзакційність

### 13.1. Загальні правила

Усі mutation-flow повинні логуватися транзакційно.

### 13.2. Logs_Name

- для суддів: `Referee`
- для довідок: `RefereeDoc`

### 13.3. Logs_Type

Використовувати тільки односимвольні коди:
- `I`
- `U`
- `D`

`VIEW` не логувати.

### 13.4. Транзакційний порядок

1. `START TRANSACTION`
2. CRUD / update / unbind
3. `INSERT INTO Logs`
4. `COMMIT`
5. якщо лог не вставився — `ROLLBACK`

---

## 14. Нефункціональні вимоги

### 14.1. Архітектура
- суворе розділення `List / Ajax / Repository / Service / Protocol_Service`
- views без SQL
- модульність і масштабованість

### 14.2. Сумісність
- PHP 8.0+
- WordPress 6.0+
- сумісність із поточним bootstrap `fstu_new`

### 14.3. Продуктивність
- server-side pagination;
- без завантаження всього набору записів у DOM;
- оптимізовані SQL з урахуванням індексів.

### 14.4. Підтримуваність
- зрозумілі методи;
- мінімум дублювання;
- контроль версій DocBlock для PHP/JS/CSS файлів.

---

## 15. Acceptance Criteria

Модуль вважається реалізованим, якщо:

### 15.1. Реєстр
- [ ] shortcode `[fstu_referees]` працює
- [ ] список суддів завантажується через AJAX
- [ ] пошук по ПІБ працює
- [ ] фільтр по області працює
- [ ] фільтр по категорії працює
- [ ] compact pagination працює
- [ ] картка судді відкривається коректно
- [ ] create/update/delete `Referee` працюють відповідно до прав

### 15.2. Довідки
- [ ] список довідок по судді завантажується
- [ ] create довідки працює
- [ ] `UNIQUE(User_ID, Calendar_ID)` враховується
- [ ] assign category працює
- [ ] unbind category працює без видалення довідки

### 15.3. Протокол
- [ ] секція `ПРОТОКОЛ` працює
- [ ] логи `Referee` відображаються
- [ ] логи `RefereeDoc` відображаються
- [ ] пошук по протоколу працює
- [ ] пагінація протоколу працює

### 15.4. Доступи
- [ ] `administrator` має повний доступ
- [ ] `referee` має повний доступ до всіх записів
- [ ] `userregistrar` має тільки перегляд
- [ ] `userfstu` має тільки перегляд

### 15.5. Адмінка
- [ ] у `views/admin/main-page.php` додано опис shortcode модуля
- [ ] у блоці shortcode є посилання на сторінку модуля

### 15.6. Безпека
- [ ] усі SQL через `prepare()`
- [ ] усі AJAX через nonce-check
- [ ] honeypot працює
- [ ] технічні SQL-помилки не потрапляють на фронтенд
- [ ] mutation-flow логуються транзакційно

---

## 16. Обмеження та legacy-сумісність

1. Модуль створюється в гібридній кодовій базі, без примусової міграції інших доменів.
2. URL полів поки залишаються текстовими, без upload-механіки.
3. `DeleteCertRefID` інтерпретується як **unbind category**, а не delete certificate.
4. `VIEW` не логувати.
5. Довідки мають окремий журнал `RefereeDoc`.
6. Опис shortcode модуля обов’язково додається в загальний список shortcode плагіна в адмінці.

---

## 17. Етапність реалізації

### MVP
- реєстр;
- картка;
- CRUD суддів;
- список довідок;
- create довідки;
- assign/unbind category;
- протокол;
- shortcode;
- опис shortcode в адмінці.

### Phase 2
- upload файлів;
- нотифікації;
- імпорт/експорт;
- додатковий аудит;
- API / зовнішні інтеграції.

---

## 18. Підсумок

Модуль `Referees` повинен бути реалізований як новий складний реєстровий домен у `fstu_new`, із чітким поділом на шари, суворою WordPress-безпекою, окремим протоколом для довідок (`RefereeDoc`), read-only режимом для `userregistrar` і `userfstu`, повним доступом для `referee`, а також з обов’язковою інтеграцією shortcode в загальний адміністративний опис модулів плагіна.

