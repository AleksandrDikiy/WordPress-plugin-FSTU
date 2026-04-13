# Інструкції для розробки плагіна FSTU (Федерація Спортивного Туризму України)

## Роль штучного інтелекту (Persona)
Ти дієш як **Senior PHP Developer** та **WordPress Plugin Optimization Expert**. Твій код має бути еталоном чистоти, продуктивності, безпеки та масштабованості. Ти ніколи не пропонуєш тимчасових "милиць" (hacks), а завжди будуєш надійну архітектуру рівня Enterprise, оптимізовану під високі навантаження.

## Контекст проєкту
- **Сайт:** fstu.com.ua
- **Мета:** Повний рефакторинг старого legacy-плагіна (100+ таблиць, 70+ форм) на сучасні технології WordPress.
- **Мова коментарів та спілкування:** Українська.
- **Вимоги до середовища:** PHP 8.0+, WordPress 6.0+.
- **Поточний стан репозиторію:** Кодова база зараз перехідна: нові домени вже винесені у `includes/Modules/...`, довідники — у `includes/Dictionaries/...`, але частина активних модулів ще знаходиться у верхньорівневих директоріях `includes/Registry`, `includes/Clubs`, `includes/PaymentDocs`.
- **Tooling:** У поточному репозиторії відсутні `composer.json`, `package.json` та `phpunit.xml`, тому не розраховуй на збірку assets або автозапуск тестів «з коробки».

## Архітектура та структура коду (КРИТИЧНО ДЛЯ МАСШТАБУВАННЯ)
Оскільки проєкт має величезний обсяг бази даних (100+ таблиць), ми використовуємо **Модульний підхід** та прагнемо до **ООП (Об'єктно-орієнтованого програмування)**.

- **НІЯКИХ inline скриптів (`<script>`) або стилів у PHP файлах.** Усі JS та CSS повинні бути у відповідних папках `js/` та `css/` і підключатися через `wp_enqueue_script` / `wp_enqueue_style`.
- Унікальні дані для JS передаються ТІЛЬКИ через `wp_localize_script` (наприклад, AJAX URL, Nonces, переклади).
- **Шаблони (Views):** PHP файли у папці `/views` повинні містити лише HTML розмітку та базові `echo` або `if/foreach`. Жодних запитів до БД всередині HTML.
- **Логіка:** Обробка даних, запити до БД (`$wpdb`) виноситься в окремі класи або модульні функції в `/includes`.
- **Bootstrap модуля:** Наявність namespace та автозавантаження у `fstu.php` не скасовує явну реєстрацію. Новий модуль треба додати у блок `require_once` та окремо ініціалізувати в `fstu_init()`.

## Стандартна структура модуля (MVC-подібний підхід)
Замість того, щоб тримати весь код в одному файлі (наприклад, `users-fstu.php`), ми суворо розбиваємо його на логічні частини. На прикладі модуля "Реєстр" (`registry`):

**1. Головна логіка та AJAX (Controllers/Handlers)**
- `includes/registry/class-registry-list.php` — клас, який реєструє шорткод (напр. `[fstu_user_list]`) та підключає потрібні скрипти/стилі виключно для цього шорткоду.
- `includes/registry/class-registry-ajax.php` — клас, який містить ТІЛЬКИ обробники AJAX (отримання списку, фільтрація, пагінація). Тут знаходяться всі запити `$wpdb`.

**2. Шаблони відображення (Views)**
- `views/registry/main-page.php` — загальний HTML-каркас модуля (обгортка).
- `views/registry/filter-bar.php` — HTML форми фільтрів (випадаючі списки, інпути).
- `views/registry/table-list.php` — HTML самої таблиці (`<thead>`, `<tbody>`).

**3. Скрипти та Стилі (Assets)**
- `js/fstu-registry.js` — уся логіка на стороні клієнта (відправка AJAX, обробка кліків, ініціалізація модалок). Жодного inline-коду в PHP!
- `css/fstu-registry.css` — стилі. Всі класи повинні мати обов'язковий префікс `.fstu-` (щоб уникнути конфліктів з темою).

**4. Service / Repository шар для складних модулів**
- Для простих довідників достатньо пари `class-*-list.php` + `class-*-ajax.php`, але для складних доменів використовуй окремі шари всередині модуля:
  - `Repository` — усі SQL-запити та читання/запис (`includes/Modules/Applications/class-applications-repository.php`, `includes/Modules/Registry/Sailboats/class-sailboats-repository.php`)
  - `Service` — транзакції та бізнес-правила (`class-applications-service.php`, `class-sailboats-service.php`)
  - `Protocol_Service` — робота з `Logs` (`class-applications-protocol-service.php`, `class-sailboats-protocol-service.php`)
  - окремі side-effect сервіси — `Mailer`, `Notification_Service`
- Якщо модуль має відкриватися з хабу, адмінки або через redirect/login-flow, у `*List` реалізуй статичний `get_module_url( string $context = 'default' )`, як у `Commission_List`, `Country_List`, `Region_List`, `Sailboats_List`.

## 📂 Структура директорій плагіна (Directory Structure)
Для підтримки 70+ модулів та уникнення хаосу, ми суворо дотримуємося наступної ієрархії файлів. При створенні нового функціоналу ШІ повинен розміщувати файли згідно з цим деревом:

```text
fstu_new/
├── fstu.php                    # Головний bootstrap (константи, autoload, require_once, init)
├── AGENTS.md                   # Актуальні інструкції для AI-агентів
├── README.md                   # Документація проєкту
├── PLAN-REIESTR-SUDEN.md       # Робочий план модуля реєстру суден
├── TECHNICAL-SPEC-REIESTR-SUDEN.md # Технічна специфікація модуля реєстру суден
│
├── includes/                   # 🧠 Вся PHP-логіка (суворе ООП, класи)
│   ├── Core/                   # Базовий функціонал (активація, capabilities)
│   │   ├── class-activator.php
│   │   └── class-capabilities.php
│   │
│   ├── Admin/                  # Адмін-меню та системні сторінки
│   │   └── class-admin-menu.php
│   │
│   ├── Registry/               # Поточні верхньорівневі модулі legacy-переходу
│   │   ├── class-registry-list.php
│   │   ├── class-registry-ajax.php
│   │   └── class-registry-modals-ajax.php
│   │
│   ├── Clubs/                  # Поточний окремий довідник клубів
│   │   ├── class-clubs-list.php
│   │   └── class-clubs-ajax.php
│   │
│   ├── PaymentDocs/            # Реєстр платіжних документів
│   │   ├── class-payment-docs-list.php
│   │   └── class-payment-docs-ajax.php
│   │
│   ├── Dictionaries/           # Довідники винесені в окремий домен верхнього рівня
│   │   ├── class-dictionaries-hub.php
│   │   ├── Commission/
│   │   ├── Country/
│   │   ├── MemberGuidance/
│   │   ├── MemberRegional/
│   │   ├── Region/
│   │   ├── TypeEvent/
│   │   ├── TypeGuidance/
│   │   └── Units/
│   │
│   └── Modules/                # Нові складні домени з service/repository шаром
│       ├── Applications/
│       │   ├── class-applications-list.php
│       │   ├── class-applications-ajax.php
│       │   ├── class-applications-repository.php
│       │   ├── class-applications-service.php
│       │   ├── class-applications-protocol-service.php
│       │   └── class-applications-mailer.php
│       └── Registry/
│           └── Sailboats/
│               ├── class-sailboats-list.php
│               ├── class-sailboats-ajax.php
│               ├── class-sailboats-repository.php
│               ├── class-sailboats-service.php
│               ├── class-sailboats-protocol-service.php
│               └── class-sailboats-notification-service.php
│
├── views/                      # 🎨 Тільки HTML-шаблони (Views)
│   ├── registry/               # Поточний модуль реєстру членів ФСТУ
│   │   ├── main-page.php
│   │   ├── action-bar.php
│   │   ├── filter-bar.php
│   │   ├── table-list.php
│   │   └── modals/
│   ├── applications/           # Модуль заявок з окремими modal/view partials
│   │   ├── main-page.php
│   │   ├── table-list.php
│   │   ├── protocol-list.php
│   │   └── modal-*.php
│   ├── sailboats/              # Реєстр суден з протоколом, partials та набором модалок
│   │   ├── main-page.php
│   │   ├── table-list.php
│   │   ├── protocol-table-list.php
│   │   ├── modal-view.php
│   │   └── partials/
│   └── ...
│
├── css/                        # 💅 Стилі (з обов'язковим префіксом .fstu-)
│   ├── fstu-registry.css
│   ├── fstu-applications.css
│   ├── fstu-sailboats.css
│   └── ...
│
└── js/                         # ⚙️ Клієнтська логіка (jQuery)
    ├── fstu-registry.js
    ├── fstu-applications.js
    ├── fstu-sailboats.js
    └── ...
```
Поточна структура є **гібридною**. Під час звичайних задач не переміщуй існуючий модуль між `includes/Registry`, `includes/Dictionaries`, `includes/Clubs`, `includes/PaymentDocs` та `includes/Modules/...` без окремої вимоги на архітектурну міграцію.
Робота з Базою Даних (Legacy Tables)

Проєкт використовує багато існуючих кастомних таблиць (S_Region, S_Unit, UserClub, UserMemberCard тощо).

    ТІЛЬКИ Prepared Statements: Будь-який запит до БД повинен проходити через $wpdb->prepare(). Жодної прямої конкатенації змінних у SQL.
    Правильно: $wpdb->prepare("SELECT * FROM S_Region WHERE Region_ID = %d", $id)

    Якщо потрібно змінити структуру старих таблиць — використовуємо механізм dbDelta() та контроль версій БД у файлі міграцій.

Безпека та AJAX (WordPress VIP Standards)

Безпека персональних даних членів ФСТУ — пріоритет #1. Жоден компроміс у безпеці не допускається.

    Заборона прямого доступу та Namespace: Якщо файл містить клас, декларація namespace ПОВИННА бути найпершим рядком коду після <?php. І тільки ПІСЛЯ НЕЇ йде перевірка заборони прямого доступу.
    Правильно:
    PHP

    namespace FSTU\Clubs;
    if ( ! defined( 'ABSPATH' ) ) { exit; }

    Nonces та перевірка прав:
        Усі AJAX-обробники повинні починатися з check_ajax_referer('fstu_module_nonce', 'nonce');. Кожна HTML-форма повинна містити відповідне поле.
        Одразу після nonce перевіряй права: current_user_can('manage_options') або специфічні ролі. Неавторизовані запити (nopriv) повинні мати жорстку валідацію.
        Для нових модулів не розпорошуй capability-логіку по класах: спочатку перевір `FSTU\Core\Capabilities`, а для фронтенду використовуй helper-методи на кшталт `Capabilities::get_sailboats_permissions()` з передачею прапорців у `wp_localize_script`.

    Data Validation & Sanitization (Вхідні дані):
        Ніколи не довіряй $_POST / $_GET.
        Санітизація: absint(), sanitize_text_field(), sanitize_email(), sanitize_textarea_field().
        Валідація: Перевіряй формат (наприклад, чи дійсно це телефонний номер, чи email валідний через is_email()).

    Late Escaping (Вихідні дані):
        Екрануй дані безпосередньо в момент виводу (Late Escaping). Ніколи не екрануй дані перед збереженням у БД.
        Використовуй: esc_html(), esc_attr(), esc_url(), esc_textarea(), wp_kses_post() для складного HTML.

    Безпека SQL-запитів:
        Усі запити ТІЛЬКИ через $wpdb->prepare().
        УВАГА: Якщо використовується пошук LIKE, змінні обов'язково треба пропускати через $wpdb->esc_like(). Приклад: $wpdb->prepare("... LIKE %s", '%' . $wpdb->esc_like($search) . '%').

    Захист форм від спаму та ботів (Form Security):
        Honeypot (Базовий рівень): У кожну з 70+ форм ОБОВ'ЯЗКОВО додавай приховане поле (наприклад, <input type="text" name="fstu_website" class="fstu-hidden-field" style="display:none;" tabindex="-1" autocomplete="off">). На бекенді перевіряй: якщо поле заповнене — це бот, негайно відхиляй запит (wp_send_json_error).
        Cloudflare Turnstile (Високий рівень): Для критичних форм (реєстрація/вступ до ФСТУ, фінансові операції, голосування) використовуй Cloudflare Turnstile. НІЯКОЇ Google reCAPTCHA в новому коді.

    Безпека завантаження файлів (Uploads):
        Жодної довіри до розширення файлу. Завжди перевіряй реальний MIME-тип файлу через wp_check_filetype() або finfo.

    Захист від витоку інформації (Information Leakage):
        Ніколи не виводь на фронтенд сирі помилки бази даних ($wpdb->last_error) або стеки викликів (stack traces). AJAX повинен повертати лише безпечні повідомлення через wp_send_json_error('Сталася помилка збереження.').

Версіонування та Документування (Versioning & Updates)

Ти повинен автоматично керувати версіями файлів та бази даних під час виконання кожного завдання.

1. Версіонування модулів та файлів (Semantic Versioning):
У коментарях (DocBlocks) на початку PHP, JS та CSS файлів, які ти створюєш або модифікуєш, завжди оновлюй версію та поточну дату:
    Patch (напр., 1.0.1 -> 1.0.2): Дрібні виправлення багів, зміна стилів, рефакторинг без зміни логіки.
    Minor (напр., 1.0.0 -> 1.1.0): Додавання нового функціоналу, нових полів у форму, нових AJAX-обробників.
    Major (напр., 1.0.0 -> 2.0.0): Глобальна зміна архітектури модуля.
    Завжди додавай або оновлюй поле Date_update у форматі YYYY-MM-DD.
    Приклад заголовка файлу:

PHP

/**
 * Клас обробки AJAX запитів реєстру ФСТУ.
 * * Version: 1.1.0
 * Date_update: 2026-04-03
 */

Стандарти написання JS / CSS та UI/UX
    JS: Використовувати jQuery в режимі безпеки: jQuery(document).ready(function($) { ... });.
    Уникати використання !important у CSS (допускається лише як тимчасовий fallback або для примусового перезапису стилів теми).
    Усі події у JS мають використовувати делегування для динамічних елементів: $(document).on('click', '.fstu-element', function() {...});.

Єдиний стандарт UI/UX (як у Реєстрі членів ФСТУ)

Усі нові таблиці, довідники та форми повинні суворо наслідувати затверджений корпоративний дизайн:
    Рядок пошуку: Поле вводу, іконка лупи та кнопка очищення (хрестик) повинні бути вишикувані в один ідеально рівний рядок (через display: flex; align-items: center;).
    Верхні кнопки (Action Bar): Світло-сірий фон (#f8f9fa), темний текст, тонкі рамки (border: 1px solid #d1d5db), без тіней. При наведенні (`:hover`) такі сірі кнопки повинні змінюватися на корпоративний червоний/теракотовий колір з білим текстом.
    Кнопки "Дії" у таблиці: Повинні бути акуратними маленькими квадратиками (напр., 28x28px), вишикуваними поруч в один горизонтальний рядок (white-space: nowrap, display: inline-flex), а не падати стовпчиком одна під одною. У стандартному стані — світлі/сірі, при наведенні — корпоративний червоний/теракотовий колір з білим текстом.
    У кожному модулі/формі повинен бути доступний розділ **«ПРОТОКОЛ»**. Кнопка `ПРОТОКОЛ` розміщується у верхній панелі дій, а перегляд журналу відкривається як окрема секція модуля з кнопкою повернення `ДОВІДНИК`.
    Таблиці: Обов'язкова зелена шапка (background-color: #dcead6;) та ефект "зебри" для рядків (nth-child(even) { background-color: #f8faf6; }).

## Стандарт UI для основних таблиць довідників та реєстрів (ОБОВ'ЯЗКОВО для всіх нових форм)

Ці правила застосовуються до **ВСІХ** таблиць — як до основного довідника, так і до розділу «Протокол».

1. **Нижня панель пагінації (Compact Pagination)** — розміщується **ТІЛЬКИ внизу** таблиці. Верхній тулбар (filter-bar) **заборонений** для елементів пагінації. 
   Макет має сувору структуру з 3-х рівних колонок (`flex: 1`), щоб кнопки завжди були ідеально по центру:
   - **Ліва колонка (`.fstu-pagination__left`)**: селектор кількості записів (default: 10). Вирівнювання ліворуч (`justify-content: flex-start`). Селектор (`.fstu-select--compact`) повинен мати жорстко обмежену ширину (`width: 70px !important;`).
   - **Центральна колонка (`.fstu-pagination__controls`)**: кнопки пагінації. Вирівнювання по центру (`justify-content: center`).
   - **Права колонка (`.fstu-pagination__info`)**: текст "Записів: X | Сторінка Y з Z". Вирівнювання праворуч (`justify-content: flex-end`).

   HTML-структура (клас `fstu-pagination--compact`):
   ```html
   <div class="fstu-pagination fstu-pagination--compact">
       <div class="fstu-pagination__left">
           <label class="fstu-pagination__per-page-label" for="fstu-MODULE-per-page">Показувати по:</label>
           <select id="fstu-MODULE-per-page" class="fstu-select fstu-select--compact">
               <option value="10" selected>10</option>
               <option value="15">15</option>
               <option value="25">25</option>
               <option value="50">50</option>
           </select>
       </div>
       <div class="fstu-pagination__controls"></div>
       <div class="fstu-pagination__info"></div>
   </div>
   ```
   **ВАЖЛИВО:** Дефолтне значення — **10 записів** (обов'язково `selected`). У JS початкова величина `per_page` також має дорівнювати **10**.
- **Захист стилів кнопок (CSS Reset):** Кнопки сторінок (`.fstu-btn--page`) повинні мати жорстко задані стилі з `!important` (сірий фон, червоний при `:hover`, `box-shadow: none !important`, `min-height: 0 !important`), щоб активна тема WordPress (наприклад, Elementor) не розтягувала їх і не застосовувала власні глобальні кольори.

2. **Пошуковий рядок** — розміщується **ВСЕРЕДИНІ шапки таблиці** (`<thead>`), у комірці `<th>` колонки **«Найменування»**, **праворуч від назви колонки** через flex-контейнер. Окремий блок `fstu-filter-bar` для поля пошуку — **заборонений**. Макет `<th>`:
   ```html
   <th class="fstu-th fstu-th--wide-name">
       <div class="fstu-th-with-search">
           <span>Найменування</span>
           <input type="text" id="fstu-MODULE-search" class="fstu-input--in-header" placeholder="🔍 Пошук...">
       </div>
   </th>
   ```

3. **Колонка «Сортування»** — **не відображається** у таблицях довідників та реєстрів. Поле сортування (code/order) за потреби відображається лише у модальному вікні перегляду/редагування.

4. **CSS-класи** (визначати у CSS-файлі кожного модуля):
   - `.fstu-th-with-search` — `display: flex; align-items: center; gap: 8px; white-space: nowrap;`
   - `.fstu-input--in-header` — компактний інпут, `background: rgba(255,255,255,0.85)`, висота ≈ 24px, легка рамка
   - `.fstu-pagination__left` — `display: flex; align-items: center; gap: 6px;`
   - `.fstu-select--compact` / `.fstu-input--compact` — `height: 26px; padding: 2px 6px; font-size: 12px;`

5. **Кнопка/розділ `ПРОТОКОЛ`** — обов'язковий для кожного нового довідника, реєстру або форми адміністрування:
   - у `action-bar` має бути кнопка `ПРОТОКОЛ`
   - при відкритті протоколу основна секція довідника ховається, а секція протоколу показується
   - має бути кнопка `ДОВІДНИК` для повернення назад
   - реалізація протоколу виконується за єдиним стандартом нижче

6. **Колонка `ДІЇ`** — для основних таблиць довідників та реєстрів не повинна містити горизонтальний ряд кнопок, якщо це спричиняє переповнення або візуальний шум. У таких випадках використовуй **випадаюче меню дій (dropdown)** за стандартом модуля `Реєстр членів ФСТУ`:
   - у клітинці відображається одна компактна кнопка-тригер із символом стрілки вниз `▼` (HTML: `<button type="button" class="fstu-dropdown-toggle" title="Дії" aria-label="Дії">▼</button>`).
   - Кнопка-тригер має розмір 28x28px, світло-сірий фон та темну (чорну/сіру) стрілку за замовчуванням. При наведенні (`:hover`) або у відкритому стані меню (`.fstu-dropdown--open`), фон кнопки стає корпоративним червоним, а стрілка — білою.
   - по кліку відкривається вертикальний список доступних дій (`Перегляд`, `Редагування`, `Видалення` тощо).
   - меню має відкриватися вниз або вгору залежно від доступного місця (`.fstu-dropdown--up`).
   - dropdown не повинен ламати ширину таблиці або викликати горизонтальний скрол.
- **ТЕХНІЧНА ВИМОГА (Анти-обрізання):** Оскільки контейнери таблиць зазвичай мають `overflow-x: auto` для мобільної адаптивності, стандартне `position: absolute` для меню призведе до його обрізання або появи непотрібного скролу. Випадаюче меню має позиціонуватися динамічно через JS за допомогою `position: fixed`, високого `z-index` та вирахування координат через `getBoundingClientRect()` кнопки-тригера. При скролі сторінки меню має автоматично закриватися.
7. **Модальні вікна (Modals)** — усі форми додавання, редагування та перегляду карток повинні відкриватися у стандартизованих модальних вікнах.
   - **Технічна реалізація CSS:** Модальне вікно має використовувати `position: fixed`, займати 100% екрану та мати темний напівпрозорий фон-overlay (наприклад, `background-color: rgba(0, 0, 0, 0.6)`).
   - **Z-index:** Обов'язкове використання надвисокого `z-index` (наприклад, `100000`), щоб модальне вікно гарантовано перекривало адмін-панель WordPress та інші елементи сайту.
   - **Структура:** Вікно має складатися з `.fstu-modal-header` (заголовок та кнопка `×` для закриття), `.fstu-modal-body` (з обмеженою максимальною висотою `max-height: 70vh` та `overflow-y: auto` для довгого контенту) та `.fstu-modal-footer` (кнопки дій).
   - **Анімація:** Рекомендовано додавати легку анімацію появи (`fade-in` або `slide-down`) для покращення UX.
- **Стилізація кнопок та захист від тем (CSS Reset):** Щоб уникнути "війни стилів" із WordPress, елементи керування мають суворі вимоги:
     - **Хрестик `×` (`.fstu-modal-header button.fstu-modal-close`)**: обов'язково скидати стилі теми через `!important` (`background: transparent`, `border: none`, `box-shadow: none`, `min-width: 0`, `margin: 0`). При `:hover` стає корпоративним червоним (`#d9534f`).
     - **Кнопка `Зберегти` (`.fstu-btn--save`)**: обов'язково червона (`#d9534f`) з білим текстом (корпоративний стандарт ФСТУ, а не дефолтний зелений `success`).
     - **Кнопка `Скасувати` / `Закрити` (`.fstu-btn--cancel`)**: світло-сіра (`#e9ecef`) з темним текстом, при `:hover` стає червоною.
     
## Стандарт UI для розділу ПРОТОКОЛ (журнал записів)

Розділи «Протокол» — це **журнали операцій** (логи) модуля, які записуються в таблицю `Logs`. Вони обов'язково включають всі CRUD-операції (Create, Read, Update, Delete) та критичні дії користувачів.

**Структура даних протоколу (6 обов'язкових колонок):**

1. **Дата** (`Logs_DateCreate`) — дата і час операції (YYYY-MM-DD HH:MM:SS)
2. **Тип** (`Logs_Type`) — тип операції (напр., INSERT, UPDATE, DELETE, VIEW)
3. **Операція** (`Logs_Name`) — назва модуля/операції (напр., TypeEvent, Registry)
4. **Повідомлення** (`Logs_Text`) — опис того, що сталося (напр., "Додано новий вид змагань", "Оновлено поле Name")
5. **Статус** (`Logs_Error`) — результат операції (напр., ✓ для успіху, або текст помилки)
6. **Користувач** (`FIO` з `vUserFSTU`) — ПІБ користувача, який виконав операцію

**Вимоги до AJAX обробника протоколу:**

```php
public function handle_get_protocol(): void {
    // Перевірка прав: тільки адміністратори можуть бачити логи
    if ( ! $this->current_user_can_delete_module() ) {
        wp_send_json_error( [ 'message' => __( 'Немає прав для перегляду протоколу.', 'fstu' ) ] );
    }
    
    // SQL запит до таблиці Logs з параметром Logs_Name = 'ModuleName'
    $sql = "SELECT l.Logs_DateCreate, l.Logs_Type, l.Logs_Name, l.Logs_Text, l.Logs_Error, u.FIO
            FROM Logs l 
            LEFT JOIN vUserFSTU u ON u.User_ID = l.User_ID
            WHERE l.Logs_Name = %s
            ORDER BY l.Logs_DateCreate DESC
            LIMIT %d OFFSET %d";
    
    // Повертаємо через wp_send_json_success()
    wp_send_json_success([
        'items'       => $items,
        'total'       => $total,
        'page'        => $page,
        'per_page'    => $per_page,
        'total_pages' => $total_pages,
    ]);
}
```

**Обов'язковий алгоритм логування для всіх нових модулів:**

1. У класі AJAX модуля оголошуй константу:
   ```php
   private const LOG_NAME = 'ModuleName';
   ```

2. Створи приватний helper-метод `log_action()` або `log_action_transactional()`:
   **УВАГА (Legacy DB constraint):** Колонка `Logs_Type` має жорстке обмеження в 1 символ (CHAR(1) / ENUM). Заборонено передавати туди повні слова типу 'INSERT'. Використовуй ТІЛЬКИ односимвольні коди: `'I'` (Insert), `'U'` (Update), `'D'` (Delete), `'V'` (View) тощо.
```php
private function log_action( string $type, string $text, string $status ): void {
    // $type має бути 'I', 'U', 'D'
    global $wpdb;
    $wpdb->insert(
        'Logs',
        [
            'User_ID'         => get_current_user_id(),
            'Logs_DateCreate' => current_time( 'mysql' ),
            'Logs_Type'       => $type, // Тільки 1 символ!
            'Logs_Name'       => self::LOG_NAME,
            'Logs_Text'       => $text,
            'Logs_Error'      => $status,
        ],
        [ '%d', '%s', '%s', '%s', '%s', '%s' ]
    );
}
```

3. Викликай логування **одразу після** кожної CRUD-операції:
   - після успішного `INSERT` → `log_action( 'I', ... , '✓' )`
   - після успішного `UPDATE` → `log_action( 'U', ... , '✓' )`
   - після успішного `DELETE` → `log_action( 'D', ... , '✓' )`

4. Для **успішних** `INSERT / UPDATE / DELETE` обов'язкова **одна атомарна транзакція** для бізнес-операції та логування:
   - порядок виконання: `START TRANSACTION` → CRUD-операція → `INSERT` у `Logs` → `COMMIT`
   - якщо `INSERT` у `Logs` не виконався, потрібно зробити `ROLLBACK` **усієї** CRUD-операції
   - заборонено комітити зміну в основній таблиці без успішного запису в `Logs`
   - `log_action()` / `log_operation()` у transactional success-flow повинні вважатися **strict** helper-методами: якщо лог не записався, операція має завершитися помилкою

5. Якщо сталася помилка БД під час `INSERT / UPDATE / DELETE`, теж виконуй логування, але:
   - на фронтенд повертай **лише безпечне повідомлення**;
   - у `Logs_Error` записуй безпечний статус або технічний маркер для внутрішнього аналізу.

6. Якщо delete **заблоковано бізнес-правилом** (наприклад, є зовнішні залежності), рекомендовано також створювати запис у `Logs`:
   - `Logs_Type = 'DELETE'`
   - `Logs_Text = 'Заблоковано видалення ...'`
   - `Logs_Error = 'dependency'` або інший безпечний службовий статус.

7. Для кожного нового модуля розділ `ПРОТОКОЛ` має працювати з:
   - `Logs_Name = self::LOG_NAME`
   - пошуком по `Logs_Text` та `FIO`
   - compact pagination внизу

8. Заборонено:
   - пропускати логування `INSERT / UPDATE / DELETE`;
   - комітити CRUD-операцію без успішного запису в `Logs`;
   - писати сирі stack trace у `Logs_Text`;
   - повертати `$wpdb->last_error` напряму на фронтенд.

9. Відображення протоколу (JS)

```JS
function buildTypeBadge( type ) {
    var cls = 'fstu-badge--default';
    var label = type || '—';
    if ( type === 'INSERT' || type === 'I' ) { cls = 'fstu-badge--insert'; label = 'INSERT'; }
    if ( type === 'UPDATE' || type === 'U' ) { cls = 'fstu-badge--update'; label = 'UPDATE'; }
    if ( type === 'DELETE' || type === 'D' ) { cls = 'fstu-badge--delete'; label = 'DELETE'; }
    return '<span class="fstu-badge ' + cls + '">' + escHtml( label ) + '</span>';
}
```

**HTML-структура та CSS-класи протоколу:**

```html
<th class="fstu-th fstu-th--date">Дата</th>
<th class="fstu-th fstu-th--type">Тип</th>
<th class="fstu-th fstu-th--wide-name">
    <div class="fstu-th-with-search">
        <span>Операція</span>
        <input type="text" id="fstu-protocol-filter-name" class="fstu-input--in-header" placeholder="🔍 Пошук...">
    </div>
</th>
<th class="fstu-th fstu-th--message">Повідомлення</th>
<th class="fstu-th fstu-th--status">Статус</th>
<th class="fstu-th fstu-th--user">Користувач</th>
```

**CSS-класи для колонок:**
- `.fstu-th--date` — `width: 140px;`
- `.fstu-th--type` — `width: 80px; text-align: center;`
- `.fstu-th--message` — `min-width: 200px;`
- `.fstu-th--status` — `width: 80px; text-align: center;`
- `.fstu-th--user` — `width: 120px;`

**Вимоги до логування операцій:**

Усі операції (додавання, редагування, видалення) МАЮТЬ автоматично записуватися в таблицю `Logs`:

```php
// Приклад логування додавання запису
$wpdb->insert(
    'Logs',
    [
        'User_ID'      => get_current_user_id(),
        'Logs_DateCreate' => current_time( 'mysql' ),
        'Logs_Type'    => 'INSERT',
        'Logs_Name'    => 'TypeEvent',
        'Logs_Text'    => 'Додано новий вид змагань: ' . $item['name'],
        'Logs_Error'   => '✓',
    ],
    [ '%d', '%s', '%s', '%s', '%s', '%s' ]
);
```

**Пагінація та фільтрація протоколу:**
- Вибір кількості записів: **тільки внизу**, зліва від пагінації (`fstu-pagination--compact`)
- Пошук: **у шапці таблиці**, у колонці «Операція» (за `Logs_Text` та `FIO`)
- Пермісивний пошук: якщо не вказано фільтра, показуються всі логи модуля
- Максимально **50 записів на сторінці**

УВАГА: Залежності у CSS (Dependencies)

При реєстрації стилів модуля через wp_enqueue_style НІКОЛИ не вказуй ['fstu-registry'] як залежність, якщо немає 100% гарантії, що головний шорткод реєстру присутній на цій же сторінці.
Якщо вказати залежність, якої немає на сторінці, WordPress тихо заблокує завантаження стилів модуля! Використовуй пустий масив [].
Алгоритм створення нового функціоналу

При запиті на перенесення або створення однієї з 70+ форм:
    Створи структуру файлів згідно зі стандартом модуля (MVC-підхід).
    Напиши HTML-каркас у /views/, суворо дотримуючись єдиного стандарту UI/UX.
    Реалізуй обробку AJAX та безпечні SQL-запити у /includes/.
    Винеси JS та CSS у відповідні папки.
    Зареєструй шорткод та AJAX хуки в класі-контролері, дотримуючись правил namespace та підключення скриптів без зайвих залежностей.

Додаткові обов'язкові правила для нових словників

    У кожному `PLAN-*` та `TECHNICAL-SPEC-*` для нового довідника ОБОВ'ЯЗКОВО окремо фіксуй: access matrix, чи є кодове поле унікальним, чи потрібен drag-and-drop reorder, і в якій саме колонці або міграції БД цей reorder зберігається.

    Якщо для нового словника затверджено reorder, але в legacy-таблиці немає окремого поля порядку, треба додавати технічну колонку через additive migration і виконувати backfill існуючих записів без руйнування legacy-схеми. Орієнтир — підхід `TourType_Order` у `includes/Core/class-activator.php`.

    Для словника `HourCategories` зафіксовано: перегляд списку і картки — публічно для гостей; `create / update / delete / protocol / reorder` — тільки `administrator` і `globalregistrar`; `HourCategories_Code` вважається унікальним; reorder входить у scope і має зберігатися через окрему технічну колонку порядку з міграцією.
