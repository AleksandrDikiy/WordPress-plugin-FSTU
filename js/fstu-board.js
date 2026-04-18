/**
 * Скрипт модуля "Комісії з видів туризму (Board)".
 * * Version: 1.0.0
 * Date_update: 2026-04-18
 */

jQuery(document).ready(function($) {
    'use strict';

    // Отримуємо глобальні змінні (Nonces, URL, Permissions)
    const boardData = window.fstuBoardData || {};
    let currentTab = 'members';

    /**
     * 1. ІНІЦІАЛІЗАЦІЯ ТА ВКЛАДКИ
     */
    function init() {
        bindEvents();
        loadMembers(); // Завантажуємо першу вкладку
    }

    function bindEvents() {
        // Перемикання вкладок
        $('.fstu-tab-btn').on('click', function() {
            $('.fstu-tab-btn').removeClass('active');
            $(this).addClass('active');

            const targetTab = $(this).data('tab');
            currentTab = targetTab;

            $('.fstu-tab-content').hide();
            $('#tab-' + targetTab).fadeIn(200);

            // Кнопка "Додати" змінює свій контекст залежно від вкладки
            if (targetTab === 'members') {
                $('#fstu-board-add-btn').show().text('Додати члена комісії');
                loadMembers();
            } else if (targetTab === 'polls') {
                $('#fstu-board-add-btn').show().text('Створити опитування');
                loadPolls();
            } else if (targetTab === 'protocol') {
                $('#fstu-board-add-btn').hide();
                loadProtocol();
            }
        });

        // Залежність фільтрів (Регіон показується тільки для Регіональної комісії)
        $('#fstu-board-filter-type').on('change', function() {
            if ($(this).val() == '1') {
                $('#fstu-board-region-group').hide();
            } else {
                $('#fstu-board-region-group').show();
            }
            triggerReload();
        });
        // Відкриття модалки додавання члена
        $('#fstu-board-add-btn').on('click', function(e) {
            e.preventDefault();
            if (currentTab === 'members') {
                openModal('member', null); // null означає режим додавання
            } else if (currentTab === 'polls') {
                openModal('poll', null);
            }
        });

        // Делегування: Редагування члена (з dropdown меню)
        $(document).on('click', '.fstu-edit-member', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const userId = $(this).data('user');
            const roleId = $(this).data('role');
            const fio = $(this).data('fio'); // Отримуємо ПІБ для заповнення
            openModal('member', id, { userId, roleId, fio });
        });

        // -----------------------------------------------------
        // АВТОКОМПЛІТ (AJAX пошук користувача)
        // -----------------------------------------------------
        let autocompleteTimeout;
        $(document).on('input', '#modal-member-user-search', function() {
            const $input = $(this);
            const $results = $('#modal-member-user-results');
            const $hidden = $('#modal-member-user-id');
            const query = $input.val().trim();

            $hidden.val(''); // Скидаємо вибраний ID, якщо текст змінився
            $input[0].setCustomValidity('Оберіть користувача зі списку');

            if (query.length < 3) {
                $results.hide().empty();
                return;
            }

            clearTimeout(autocompleteTimeout);
            autocompleteTimeout = setTimeout(() => {
                $.post(boardData.ajaxUrl, {
                    action: 'fstu_board_search_users',
                    nonce: boardData.nonce,
                    q: query
                }, function(response) {
                    $results.empty();
                    if (response.success) {

                        // ВИВОДИМО SQL ЗАПИТ У КОНСОЛЬ ДЛЯ ПЕРЕВІРКИ
                        console.log("----- SQL DEBUG -----");
                        console.log(response.data.sql);
                        console.log("---------------------");

                        const users = response.data.items || [];

                        // Успішна відповідь сервера
                        if (users.length > 0) {
                            users.forEach(user => {
                                $results.append(`<li class="fstu-autocomplete-item" data-id="${user.User_ID}">${escapeHtml(user.FIO)}</li>`);
                            });
                        } else {
                            $results.append(`<li class="fstu-autocomplete-item" style="color:#666; cursor:default;">За запитом нікого не знайдено</li>`);
                        }
                    } else {
                        // Сервер повернув помилку (наприклад, 'Немає прав')
                        $results.append(`<li class="fstu-autocomplete-item" style="color:red; cursor:default;">Помилка: ${response.data.message || 'Невідома помилка'}</li>`);
                    }
                    $results.show();
                }).fail(function() {
                    $results.empty().append(`<li class="fstu-autocomplete-item" style="color:red; cursor:default;">Помилка сервера</li>`).show();
                });
            }, 400); // 400мс затримка перед запитом
        });

        // Клік по результату автокомпліту
        $(document).on('click', '.fstu-autocomplete-item', function() {
            if (!$(this).data('id')) return;
            const $results = $('#modal-member-user-results');
            const $input = $('#modal-member-user-search');
            const $hidden = $('#modal-member-user-id');

            $hidden.val($(this).data('id'));
            $input.val($(this).text());
            $input[0].setCustomValidity(''); // Знімаємо помилку валідації HTML5
            $results.hide().empty();
        });

        // Приховати результати при кліку поза полем
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.fstu-autocomplete-wrapper').length) {
                $('#modal-member-user-results').hide();
            }
        });

        // Делегування: Видалення члена комісії
        $(document).on('click', '.fstu-delete-member', function(e) {
            e.preventDefault();
            if (!confirm('Ви дійсно хочете ВИДАЛИТИ запис?')) return;

            const id = $(this).data('id');

            $.post(boardData.ajaxUrl, {
                action: 'fstu_board_delete_member',
                nonce: boardData.nonce,
                id: id
            }, function(response) {
                if (response.success) {
                    loadMembers(); // Оновлюємо таблицю
                } else {
                    alert(response.data.message);
                }
            }).fail(function() {
                alert(boardData.i18n.error);
            });
        });

        // Закриття модалки
        $(document).on('click', '.fstu-modal-close, .fstu-btn--cancel', function(e) {
            e.preventDefault();
            $(this).closest('.fstu-modal-overlay').fadeOut(200);
        });

        // Відправка форми (Додавання/Редагування)
        $(document).on('submit', '#fstu-board-member-form', function(e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $form.find('.fstu-btn--save');

            // Підтягуємо поточні значення фільтрів у приховані поля
            $('#modal-member-year').val($('#fstu-board-filter-year').val());
            $('#modal-member-type').val($('#fstu-board-filter-type').val());
            $('#modal-member-region').val($('#fstu-board-filter-region').val());
            $('#modal-member-s-commission').val($('#fstu-board-filter-commission').val());

            $form.find('input[name="nonce"]').val(boardData.nonce);

            $btn.prop('disabled', true).text('Збереження...');

            $.post(boardData.ajaxUrl, $form.serialize(), function(response) {
                if (response.success) {
                    $('#fstu-modal-member').fadeOut(200);
                    loadMembers(); // Перезавантажуємо таблицю
                    alert(response.data.message); // У реалі замінити на гарний Toast
                } else {
                    alert(response.data.message);
                }
            }).fail(function() {
                alert(boardData.i18n.error);
            }).always(function() {
                $btn.prop('disabled', false).text('Зберегти');
            });
        });
        // Перезавантаження даних при зміні будь-якого фільтра
        $('#fstu-board-filter-year, #fstu-board-filter-region, #fstu-board-filter-commission').on('change', triggerReload);

        // Динамічне закриття Dropdown-меню при кліку поза ним або скролі
        $(document).on('click', closeAllDropdowns);
        $('.fstu-table-responsive').on('scroll', closeAllDropdowns);
    }

    function triggerReload() {
        if (currentTab === 'members') loadMembers();
        if (currentTab === 'polls') loadPolls();
    }

    /**
     * 2. AJAX ЗАВАНТАЖЕННЯ (Склад)
     */
    let membersPage = 1;

    function loadMembers(page = 1) {
        membersPage = page;
        const tbody = $('#fstu-board-members-tbody');
        // Обчислюємо colspan залежно від прав (4 або 6 колонок)
        const colspan = boardData.permissions.canViewContacts ? (boardData.permissions.canManage ? 6 : 5) : (boardData.permissions.canManage ? 4 : 3);

        tbody.html(`<tr><td colspan="${colspan}" class="fstu-text-center">Завантаження...</td></tr>`);

        $.post(boardData.ajaxUrl, {
            action: 'fstu_board_get_members',
            nonce: boardData.nonce,
            year_id: $('#fstu-board-filter-year').val(),
            commission_type_id: $('#fstu-board-filter-type').val(),
            region_id: $('#fstu-board-filter-region').val(),
            s_commission_id: $('#fstu-board-filter-commission').val(),
            page: membersPage,
            per_page: $('#fstu-board-members-per-page').val() || 10,
            search: $('#fstu-board-search-member').val()
        }, function(response) {
            if (response.success) {
                renderMembersTable(response.data);
            } else {
                tbody.html(`<tr><td colspan="${colspan}" class="fstu-text-center fstu-text-danger">${response.data.message}</td></tr>`);
            }
        }).fail(function() {
            tbody.html(`<tr><td colspan="${colspan}" class="fstu-text-center fstu-text-danger">Помилка з'єднання з сервером.</td></tr>`);
        });
    }

    // Біндимо події пагінації та пошуку для складу
    $(document).on('change', '#fstu-board-members-per-page', () => loadMembers(1));
    $(document).on('click', '.fstu-members-page', function() { loadMembers($(this).data('page')); });

    let searchMemberTimeout;
    $(document).on('input', '#fstu-board-search-member', function() {
        clearTimeout(searchMemberTimeout);
        searchMemberTimeout = setTimeout(() => loadMembers(1), 500);
    });
    /**
     * 4. AJAX ЗАВАНТАЖЕННЯ (Опитування)
     */
    function loadPolls() {
        const container = $('#fstu-board-polls-container');
        container.html('<div class="fstu-text-center">Завантаження опитувань...</div>');

        $.post(boardData.ajaxUrl, {
            action: 'fstu_board_get_polls',
            nonce: boardData.nonce,
            question_type_id: 2, // Наприклад, опитування за кандидатів
            commission_type_id: $('#fstu-board-filter-type').val(),
            region_id: $('#fstu-board-filter-region').val(),
            s_commission_id: $('#fstu-board-filter-commission').val()
        }, function(response) {
            if (response.success) {
                renderPolls(response.data.items);
            } else {
                container.html(`<div class="fstu-text-center fstu-text-danger">${response.data.message}</div>`);
            }
        }).fail(function() {
            container.html('<div class="fstu-text-center fstu-text-danger">Помилка з\'єднання.</div>');
        });
    }

    function renderPolls(items) {
        const container = $('#fstu-board-polls-container');
        container.empty();

        if (items.length === 0) {
            container.html('<div class="fstu-text-center">Немає доступних опитувань.</div>');
            return;
        }

        let html = '<div class="fstu-table-responsive"><table class="fstu-table fstu-table--striped"><thead><tr>';
        html += '<th style="width: 50px;" class="fstu-text-center">№</th>';
        html += '<th style="width: 100px;">Початок</th>';
        html += '<th>Найменування</th>';
        html += '<th style="width: 80px;" class="fstu-text-center" title="Кількість членів комісії">Квота</th>';
        html += '<th style="width: 100px;" class="fstu-text-center">Статус</th>';
        html += '<th style="width: 120px;" class="fstu-text-center">Дії</th>';
        html += '</tr></thead><tbody>';

        items.forEach((item, index) => {
            const statusBadge = item.Question_State == '0'
                ? '<span class="fstu-badge fstu-badge--success">Публічний</span>'
                : '<span class="fstu-badge fstu-badge--warning">Приватний</span>';

            html += `<tr>`;
            html += `<td class="fstu-text-center">${index + 1}</td>`;
            html += `<td>${item.Question_DateBegin}</td>`;
            html += `<td><strong>${escapeHtml(item.Question_Name)}</strong></td>`;
            html += `<td class="fstu-text-center">${item.SetCommission_CountMembers}</td>`;
            html += `<td class="fstu-text-center">${statusBadge}</td>`;

            // Кнопка перегляду кандидатів
            html += `<td class="fstu-text-center">
                        <button type="button" class="fstu-btn fstu-view-candidates" data-id="${item.Question_ID}">Відкрити</button>
                     </td>`;
            html += `</tr>`;

            // Прихований контейнер для кандидатів (розгортається по кліку)
            html += `<tr class="fstu-candidates-row" id="candidates-row-${item.Question_ID}" style="display:none;">
                        <td colspan="6" style="padding: 0; background: #fafafa;">
                            <div class="fstu-candidates-container" id="candidates-container-${item.Question_ID}" style="padding: 15px; border-bottom: 2px solid #dcead6;">
                                </div>
                        </td>
                     </tr>`;
        });

        html += '</tbody></table></div>';
        container.html(html);

        // Біндимо клік на відкриття кандидатів
        $('.fstu-view-candidates').on('click', function() {
            const qId = $(this).data('id');
            const row = $(`#candidates-row-${qId}`);
            if (row.is(':visible')) {
                row.hide();
                $(this).text('Відкрити');
            } else {
                row.show();
                $(this).text('Закрити');
                loadCandidates(qId);
            }
        });
    }

    /**
     * 5. AJAX ЗАВАНТАЖЕННЯ КАНДИДАТІВ (Для конкретного опитування)
     */
    function loadCandidates(questionId) {
        const container = $(`#candidates-container-${questionId}`);
        container.html('<div class="fstu-text-center">Завантаження кандидатів...</div>');

        $.post(boardData.ajaxUrl, {
            action: 'fstu_board_get_candidates',
            nonce: boardData.nonce,
            question_id: questionId
        }, function(response) {
            if (response.success) {
                renderCandidates(questionId, response.data.items);
            } else {
                container.html(`<div class="fstu-text-danger">${response.data.message}</div>`);
            }
        });
    }

    function renderCandidates(questionId, items) {
        const container = $(`#candidates-container-${questionId}`);

        if (items.length === 0) {
            container.html('<div class="fstu-text-center">Немає кандидатів.</div>');
            return;
        }

        let html = '<table class="fstu-table"><thead><tr>';
        html += '<th style="width: 40px;">№</th>';
        html += '<th>Кандидат / Напрямок</th>';
        html += '<th colspan="3" class="fstu-text-center">Результати голосування</th>';

        if (boardData.permissions.canVote) {
            html += '<th style="width: 200px;" class="fstu-text-center">Ваш голос</th>';
        }
        html += '</tr></thead><tbody>';

        items.forEach((item, index) => {
            // Обчислення відсотків для прогрес-барів
            const cntYes = parseInt(item.cnt_yes) || 0;
            const cntNo = parseInt(item.cnt_no) || 0;
            const cntAbs = parseInt(item.cnt_abstain) || 0;
            const total = cntYes + cntNo + cntAbs;

            const pYes = total > 0 ? (cntYes / total * 100) : 0;
            const pAbs = total > 0 ? (cntAbs / total * 100) : 0;
            const pNo = total > 0 ? (cntNo / total * 100) : 0;

            const progressHtml = `
                <div class="fstu-progress-wrapper" style="margin-bottom: 5px;">
                    <div class="fstu-progress-bar fstu-progress-bar--yes" style="width: ${pYes}%;" title="ЗА: ${cntYes}"></div>
                    <div class="fstu-progress-bar fstu-progress-bar--abstain" style="width: ${pAbs}%;" title="УТРИМАВСЯ: ${cntAbs}"></div>
                    <div class="fstu-progress-bar fstu-progress-bar--no" style="width: ${pNo}%;" title="ПРОТИ: ${cntNo}"></div>
                </div>
            `;

            html += `<tr>`;
            html += `<td class="fstu-text-center">${index + 1}</td>`;
            html += `<td>
                        <strong>${escapeHtml(item.FIO)}</strong><br>
                        <small style="color:#666;">${escapeHtml(item.Direction)}</small>
                     </td>`;
            html += `<td colspan="3" class="fstu-text-center" style="vertical-align: middle;">
                        ${progressHtml}
                        <div class="fstu-vote-stats">
                            <span style="color:green; margin-right:8px;">ЗА: ${cntYes}</span>
                            <span style="color:orange; margin-right:8px;">УТРИМ: ${cntAbs}</span>
                            <span style="color:red;">ПРОТИ: ${cntNo}</span>
                        </div>
                     </td>`;

            if (boardData.permissions.canVote) {
                // Блок кнопок для голосування
                const voteVal = item.current_user_vote !== null ? item.current_user_vote : '';
                const btnYesClass = voteVal == '1' ? 'fstu-btn--page active' : 'fstu-btn--page';
                const btnAbstClass = voteVal == '0' ? 'fstu-btn--page active' : 'fstu-btn--page';
                const btnNoClass = voteVal == '2' ? 'fstu-btn--page active' : 'fstu-btn--page';

                html += `<td class="fstu-text-center">
                            <button class="fstu-cast-vote ${btnYesClass}" data-cid="${item.CandidatesCommission_ID}" data-val="1">ЗА</button>
                            <button class="fstu-cast-vote ${btnAbstClass}" data-cid="${item.CandidatesCommission_ID}" data-val="0">УТРИМ</button>
                            <button class="fstu-cast-vote ${btnNoClass}" data-cid="${item.CandidatesCommission_ID}" data-val="2">ПРОТИ</button>
                         </td>`;
            }
            html += `</tr>`;
        });

        html += '</tbody></table>';
        container.html(html);

        // Біндимо голосування
        container.find('.fstu-cast-vote').on('click', function() {
            const cid = $(this).data('cid');
            const val = $(this).data('val');
            const btn = $(this);

            btn.siblings().prop('disabled', true);
            btn.text('...').prop('disabled', true);

            $.post(boardData.ajaxUrl, {
                action: 'fstu_board_cast_vote',
                nonce: boardData.nonce,
                candidate_id: cid,
                vote_value: val
            }, function(response) {
                if (response.success) {
                    loadCandidates(questionId); // Оновлюємо таблицю після успішного голосу
                } else {
                    alert(response.data.message);
                    loadCandidates(questionId); // Повертаємо стан кнопок
                }
            }).fail(function() {
                alert('Помилка сервера.');
                loadCandidates(questionId);
            });
        });
    }

    /**
     * 6. AJAX ЗАВАНТАЖЕННЯ (Протокол)
     */
    let protocolPage = 1;

    function loadProtocol(page = 1) {
        protocolPage = page;
        const tbody = $('#fstu-board-protocol-tbody');
        tbody.html('<tr><td colspan="4" class="fstu-text-center">Завантаження протоколу...</td></tr>');

        $.post(boardData.ajaxUrl, {
            action: 'fstu_board_get_protocol',
            nonce: boardData.nonce,
            page: protocolPage,
            per_page: $('#fstu-board-protocol-per-page').val(),
            search: $('#fstu-board-search-protocol').val()
        }, function(response) {
            if (response.success) {
                renderProtocol(response.data);
            } else {
                tbody.html(`<tr><td colspan="4" class="fstu-text-center fstu-text-danger">${response.data.message}</td></tr>`);
            }
        });
    }

    function renderProtocol(data) {
        const tbody = $('#fstu-board-protocol-tbody');
        tbody.empty();

        if (data.items.length === 0) {
            tbody.html('<tr><td colspan="4" class="fstu-text-center">Записів не знайдено.</td></tr>');
        } else {
            data.items.forEach(log => {
                let badgeClass = 'fstu-badge--warning';
                let label = log.Logs_Type || '—';

                if (log.Logs_Type === 'I') { badgeClass = 'fstu-badge--success'; label = 'INSERT'; }
                if (log.Logs_Type === 'U') { badgeClass = 'fstu-badge--warning'; label = 'UPDATE'; }
                if (log.Logs_Type === 'D') { badgeClass = 'fstu-badge--danger'; label = 'DELETE'; }
                if (log.Logs_Type === 'M') { badgeClass = 'fstu-badge--success'; label = 'MAIL'; }

                let tr = $('<tr>');
                tr.append(`<td>${log.Logs_DateCreate}</td>`);
                tr.append(`<td class="fstu-text-center"><span class="fstu-badge ${badgeClass}">${label}</span></td>`);
                tr.append(`<td><strong>${escapeHtml(log.FIO)}</strong><br>${escapeHtml(log.Logs_Text)}</td>`);
                tr.append(`<td class="fstu-text-center">${escapeHtml(log.Logs_Error)}</td>`);
                tbody.append(tr);
            });
        }

        // Оновлення інформації пагінації
        $('#fstu-board-protocol-info').text(`Записів: ${data.total} | Сторінка ${data.page} з ${data.total_pages}`);

        // Рендеринг кнопок пагінації
        let paginationHtml = '';
        if (data.total_pages > 1) {
            if (data.page > 1) paginationHtml += `<button class="fstu-btn--page fstu-protocol-page" data-page="${data.page - 1}">«</button>`;
            paginationHtml += `<button class="fstu-btn--page active">${data.page}</button>`;
            if (data.page < data.total_pages) paginationHtml += `<button class="fstu-btn--page fstu-protocol-page" data-page="${data.page + 1}">»</button>`;
        }
        $('#fstu-board-protocol-pagination').html(paginationHtml);
    }

    // Біндимо події протоколу (пошук та пагінація)
    $(document).on('change', '#fstu-board-protocol-per-page', () => loadProtocol(1));
    $(document).on('click', '.fstu-protocol-page', function() { loadProtocol($(this).data('page')); });

    // Затримка пошуку (Debounce)
    let searchTimeout;
    $(document).on('input', '#fstu-board-search-protocol', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => loadProtocol(1), 500);
    });
    function renderMembersTable(data) {
        const tbody = $('#fstu-board-members-tbody');
        tbody.empty();
        const items = data.items || [];
        const colspan = boardData.permissions.canViewContacts ? (boardData.permissions.canManage ? 6 : 5) : (boardData.permissions.canManage ? 4 : 3);

        if (items.length === 0) {
            tbody.html(`<tr><td colspan="${colspan}" class="fstu-text-center">Склад комісії порожній.</td></tr>`);
        } else {
            items.forEach((item, index) => {
                const rowNum = (data.page - 1) * data.per_page + index + 1;
                let tr = $('<tr>');
                tr.append(`<td class="fstu-text-center">${rowNum}</td>`);
                tr.append(`<td>${escapeHtml(item.Member_Name)}</td>`);
                // Правильне посилання на картку користувача з підтягнутим ПІБ
                tr.append(`<td><a href="/personal/?ViewID=${item.User_ID}" target="_blank"><strong>${escapeHtml(item.FIO)}</strong></a></td>`);

                // Відображаємо контакти лише якщо є права
                if (boardData.permissions.canViewContacts) {
                    tr.append(`<td>${item.Phones || '-'}</td>`); // Phones вже мають теги <br> з PHP
                    tr.append(`<td>${escapeHtml(item.user_email || '-')}</td>`);
                }

                if (boardData.permissions.canManage) {
                    let actionHtml = `
                        <div class="fstu-dropdown-wrapper">
                            <button type="button" class="fstu-dropdown-toggle" data-id="${item.Commission_ID}" title="Дії">▼</button>
                            <div class="fstu-dropdown-menu" id="dropdown-${item.Commission_ID}">
                                <a href="#" class="fstu-dropdown-item fstu-edit-member" data-id="${item.Commission_ID}" data-user="${item.User_ID}" data-role="${item.Member_ID}" data-fio="${escapeHtml(item.FIO)}">Редагувати</a>
                                <a href="#" class="fstu-dropdown-item fstu-text-danger fstu-delete-member" data-id="${item.Commission_ID}">Видалити</a>
                            </div>
                        </div>
                    `;
                    tr.append(`<td class="fstu-text-center">${actionHtml}</td>`);
                }
                tbody.append(tr);
            });
        }

        // Оновлення пагінації
        if (data.total !== undefined) {
            $('#fstu-board-members-info').text(`Записів: ${data.total} | Сторінка ${data.page} з ${data.total_pages}`);
            let paginationHtml = '';
            if (data.total_pages > 1) {
                if (data.page > 1) paginationHtml += `<button class="fstu-btn--page fstu-members-page" data-page="${data.page - 1}">«</button>`;
                paginationHtml += `<button class="fstu-btn--page active">${data.page}</button>`;
                if (data.page < data.total_pages) paginationHtml += `<button class="fstu-btn--page fstu-members-page" data-page="${data.page + 1}">»</button>`;
            }
            $('#fstu-board-members-pagination').html(paginationHtml);
        }

        // Ініціалізація Dropdown (Анти-обрізання)
        initDropdowns();
    }

    /**
     * 3. DROPDOWN (Анти-обрізання `overflow: auto`)
     */
    function initDropdowns() {
        $('.fstu-dropdown-toggle').off('click').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const $btn = $(this);
            const $menu = $btn.next('.fstu-dropdown-menu');
            const isOpen = $menu.hasClass('fstu-dropdown--open');

            closeAllDropdowns();

            if (!isOpen) {
                // Вираховуємо координати для position: fixed
                const rect = $btn[0].getBoundingClientRect();

                $menu.css({
                    'position': 'fixed',
                    'top': rect.bottom + 'px',
                    'left': rect.right - 150 + 'px', // 150px = ширина меню
                    'z-index': 100000
                }).addClass('fstu-dropdown--open');

                $btn.addClass('active');
            }
        });
    }

    function closeAllDropdowns() {
        $('.fstu-dropdown-menu').removeClass('fstu-dropdown--open');
        $('.fstu-dropdown-toggle').removeClass('active');
    }

    // Допоміжна функція екранування (Late Escaping JS)
    function escapeHtml(text) {
        if (!text) return '';
        var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    /**
     * Відкриття модалки з завантаженням HTML або очищенням форми.
     */
    function openModal(type, id, extraData = null) {
        const modalId = '#fstu-modal-' + type;

        // Якщо модалки ще немає в DOM, завантажуємо її через AJAX
        if ($(modalId).length === 0) {
            $.post(boardData.ajaxUrl, {
                action: 'fstu_board_get_modal',
                nonce: boardData.nonce,
                modal: type
            }, function(response) {
                if (response.success) {
                    $('#fstu-board-modals-container').append(response.data.html);
                    fillModalDataAndShow(type, id, extraData);
                } else {
                    alert(response.data.message);
                }
            });
        } else {
            fillModalDataAndShow(type, id, extraData);
        }
    }

    function fillModalDataAndShow(type, id, extraData) {
        const modalId = '#fstu-modal-' + type;
        const $form = $(modalId).find('form');

        $form[0].reset(); // Очищаємо форму

        if (id) {
            $(modalId).find('.fstu-modal-title').text('Редагування запису');
            $(modalId).find('input[name="' + (type === 'member' ? 'commission_id' : 'question_id') + '"]').val(id);

            // Заповнюємо дані форми для редагування
            if (type === 'member' && extraData) {
                $(modalId).find('#modal-member-user-id').val(extraData.userId);
                $(modalId).find('#modal-member-user-search').val(extraData.fio);
                $(modalId).find('#modal-member-user-search')[0].setCustomValidity(''); // Дозволяємо відправку
                $(modalId).find('#modal-member-role').val(extraData.roleId);
            }
        } else {
            $(modalId).find('.fstu-modal-title').text('Додавання запису');
            $(modalId).find('input[name="' + (type === 'member' ? 'commission_id' : 'question_id') + '"]').val('');
        }

        $(modalId).css('display', 'flex').hide().fadeIn(200);
    }
    // Старт
    init();
});