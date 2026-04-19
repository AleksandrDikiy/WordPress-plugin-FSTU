/**
 * Скрипт модуля "Комісії з видів туризму (Board)".
 * * Version: 1.1.1
 * Date_update: 2026-04-19
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

        $('#fstu-board-filter-type, #fstu-board-filter-year').on('change', function() {
            triggerReload();
        });

        // Скидання року при зміні конкретної комісії
        $('#fstu-board-filter-commission').on('change', function() {
            $('#fstu-board-filter-year').val('0');
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

        // Відкриття модалки додавання кандидата
        $(document).on('click', '.fstu-add-candidate-btn', function(e) {
            e.preventDefault();
            const qId = $(this).data('qid');
            openModal('candidate', null, { questionId: qId });
        });

        // -----------------------------------------------------
        // АВТОКОМПЛІТ (AJAX пошук користувача для обох модалок)
        // -----------------------------------------------------
        let autocompleteTimeout;
        $(document).on('input', '#modal-member-user-search, #modal-candidate-user-search', function() {
            const $input = $(this);
            const isCandidate = $input.attr('id') === 'modal-candidate-user-search';
            const $results = isCandidate ? $('#modal-candidate-user-results') : $('#modal-member-user-results');
            const $hidden = isCandidate ? $('#modal-candidate-user-id') : $('#modal-member-user-id');
            const query = $input.val().trim();

            $hidden.val('');
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
                        const users = response.data.items || [];
                        if (users.length > 0) {
                            users.forEach(user => {
                                $results.append(`<li class="fstu-autocomplete-item" data-id="${user.User_ID}">${escapeHtml(user.FIO)}</li>`);
                            });
                        } else {
                            $results.append(`<li class="fstu-autocomplete-item" style="color:#666; cursor:default;">За запитом нікого не знайдено</li>`);
                        }
                    } else {
                        $results.append(`<li class="fstu-autocomplete-item" style="color:red; cursor:default;">Помилка: ${response.data.message || 'Невідома помилка'}</li>`);
                    }
                    $results.show();
                });
            }, 400);
        });

        // Клік по результату автокомпліту
        $(document).on('click', '.fstu-autocomplete-item', function() {
            if (!$(this).data('id')) return;
            const $results = $(this).closest('.fstu-autocomplete-list');
            const isCandidate = $results.attr('id') === 'modal-candidate-user-results';
            const $input = isCandidate ? $('#modal-candidate-user-search') : $('#modal-member-user-search');
            const $hidden = isCandidate ? $('#modal-candidate-user-id') : $('#modal-member-user-id');

            $hidden.val($(this).data('id'));
            $input.val($(this).text());
            $input[0].setCustomValidity('');
            $results.hide().empty();
        });

        // Приховати результати при кліку поза полем
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.fstu-autocomplete-wrapper').length) {
                $('.fstu-autocomplete-list').hide();
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
                    showToast('Запис успішно видалено', 'success');
                    loadMembers(); // Оновлюємо таблицю
                } else {
                    showToast(response.data.message, 'error');
                }
            }).fail(function() {
                showToast('Помилка сервера', 'error');
            });
        });

        // Закриття модалки
        $(document).on('click', '.fstu-modal-close, .fstu-btn--cancel', function(e) {
            e.preventDefault();
            $(this).closest('.fstu-modal-overlay').fadeOut(200);
        });

        // -----------------------------------------------------
        // Відправка форми (Додавання/Редагування ЧЛЕНА КОМІСІЇ)
        // -----------------------------------------------------
        $(document).on('submit', '#fstu-board-member-form', function(e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $form.find('.fstu-btn--save');

            // Підтягуємо поточні значення фільтрів у приховані поля
            $('#modal-member-year').val($('#fstu-board-filter-year').val());
            $('#modal-member-type').val($('#fstu-board-filter-type').val());
            $('#modal-member-s-commission').val($('#fstu-board-filter-commission').val());

            $form.find('input[name="nonce"]').val(boardData.nonce);
            $btn.prop('disabled', true).text('Збереження...');

            $.post(boardData.ajaxUrl, $form.serialize(), function(response) {
                if (response.success) {
                    $('#fstu-modal-member').fadeOut(200);
                    loadMembers(); // Перезавантажуємо таблицю
                    showToast(response.data.message, 'success');
                } else {
                    showToast(response?.data?.message || 'Помилка сервера', 'error');
                }
            }).fail(function() {
                showToast('Помилка сервера', 'error');
            }).always(function() {
                $btn.prop('disabled', false).text('Зберегти');
            });
        });

        // -----------------------------------------------------
        // Відправка форми (Створення/Редагування ОПИТУВАННЯ)
        // -----------------------------------------------------
        $(document).on('submit', '#fstu-board-poll-form', function(e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $form.find('.fstu-btn--save');

            // Підтягуємо поточні значення фільтрів у приховані поля
            $('#modal-poll-type').val($('#fstu-board-filter-type').val());
            $('#modal-poll-s-commission').val($('#fstu-board-filter-commission').val());

            $form.find('input[name="nonce"]').val(boardData.nonce);
            $btn.prop('disabled', true).text('Збереження...');

            $.post(boardData.ajaxUrl, $form.serialize(), function(response) {
                if (response.success) {
                    $('#fstu-modal-poll').fadeOut(200);
                    loadPolls(); // Оновлюємо таблицю опитувань
                    showToast(response.data.message, 'success');
                } else {
                    showToast(response?.data?.message || 'Помилка сервера', 'error');
                }
            }).fail(function() {
                showToast('Помилка сервера', 'error');
            }).always(function() {
                $btn.prop('disabled', false).text('Зберегти');
            });
        });

        // -----------------------------------------------------
        // Відправка форми (Додавання КАНДИДАТА)
        // -----------------------------------------------------
        $(document).on('submit', '#fstu-board-candidate-form', function(e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $form.find('.fstu-btn--save');
            const qId = $('#modal-candidate-question-id').val();

            $form.find('input[name="nonce"]').val(boardData.nonce);
            $btn.prop('disabled', true).text('Збереження...');

            $.post(boardData.ajaxUrl, $form.serialize(), function(response) {
                if (response.success) {
                    $('#fstu-modal-candidate').fadeOut(200);
                    loadCandidates(qId); // Оновлюємо таблицю кандидатів
                    showToast(response.data.message, 'success');
                } else {
                    showToast(response?.data?.message || 'Помилка сервера', 'error');
                }
            }).fail(function() {
                showToast('Помилка сервера', 'error');
            }).always(function() {
                $btn.prop('disabled', false).text('Зберегти');
            });
        });

        // Динамічне закриття Dropdown-меню при кліку поза ним або скролі
        $(document).on('click', closeAllDropdowns);
        $('.fstu-table-responsive').on('scroll', closeAllDropdowns);
    }

    function triggerReload() {
        if (currentTab === 'members') {
            loadMembers();
        } else if (currentTab === 'polls') {
            loadPolls();
        }
    }

    /**
     * 2. AJAX ЗАВАНТАЖЕННЯ (Склад)
     */
    let membersPage = 1;

    function loadMembers(page = 1) {
        membersPage = page;
        const tbody = $('#fstu-board-members-tbody');
        const colspan = boardData.permissions.canViewContacts ? (boardData.permissions.canManage ? 6 : 5) : (boardData.permissions.canManage ? 4 : 3);

        tbody.html(`<tr><td colspan="${colspan}" class="fstu-text-center">Завантаження...</td></tr>`);

        $.post(boardData.ajaxUrl, {
            action: 'fstu_board_get_members',
            nonce: boardData.nonce,
            year_id: $('#fstu-board-filter-year').val(),
            commission_type_id: $('#fstu-board-filter-type').val(),
            s_commission_id: $('#fstu-board-filter-commission').val(),
            page: membersPage,
            per_page: $('#fstu-board-members-per-page').val() || 10,
            search: $('#fstu-board-search-member').val()
        }, function(response) {
            if (response.success) {
                if (response.data.resolved_year) {
                    $('#fstu-board-filter-year').val(response.data.resolved_year);
                }
                renderMembersTable(response.data);
            } else {
                tbody.html(`<tr><td colspan="${colspan}" class="fstu-text-center fstu-text-danger">${response.data.message}</td></tr>`);
            }
        }).fail(function() {
            tbody.html(`<tr><td colspan="${colspan}" class="fstu-text-center fstu-text-danger">Помилка з'єднання з сервером.</td></tr>`);
        });
    }

    $(document).on('change', '#fstu-board-members-per-page', function() {
        loadMembers(1);
    });

    $(document).on('click', '.fstu-members-page', function() {
        const page = $(this).data('page');
        loadMembers(page);
    });

    let searchMemberTimeout;
    $(document).on('input', '#fstu-board-search-member', function() {
        clearTimeout(searchMemberTimeout);
        searchMemberTimeout = setTimeout(function() {
            loadMembers(1);
        }, 500);
    });

    function renderMembersTable(data) {
        const infoContainer = $('#fstu-board-commission-info');

        // АРХІТЕКТУРНЕ РІШЕННЯ: Жорстко переміщуємо блок сторінки ПІД таблицю.
        infoContainer.insertAfter('#tab-members .fstu-table-responsive');

        if (data.commission_post_id && data.commission_post_id > 0) {
            let editBtnHtml = '';
            if (data.can_edit_page) {
                const editUrl = boardData.ajaxUrl.replace('admin-ajax.php', `post.php?post=${data.commission_post_id}&action=edit`);
                editBtnHtml = `
                <div class="fstu-edit-page-wrapper">
                    <a href="${editUrl}" target="_blank" rel="noopener noreferrer" class="fstu-btn--edit-page">
                        ✏️ Редагувати опис сторінки
                    </a>
                </div>`;
            }

            infoContainer.html(editBtnHtml + '<div class="fstu-text-center fstu-text-muted fstu-p-10">Завантаження інформації...</div>').slideDown(200);

            $.get(`/wp-json/wp/v2/pages/${data.commission_post_id}`)
                .done(function(page) {
                    let contentHtml = '';
                    if (page && page.content && page.content.rendered && page.content.rendered.trim() !== '') {
                        contentHtml = page.content.rendered;
                    } else {
                        contentHtml = '<div class="fstu-text-center fstu-text-muted fstu-p-10">Опис для цієї комісії поки що порожній. Натисніть кнопку "Редагувати", щоб додати текст та посилання.</div>';
                    }
                    infoContainer.html(editBtnHtml + contentHtml);
                })
                .fail(function() {
                    let errorHtml = '<div class="fstu-text-center fstu-text-danger fstu-p-10">Сторінку не знайдено або вона в чернетках. Натисніть "Редагувати", щоб створити або опублікувати її.</div>';
                    infoContainer.html(editBtnHtml + errorHtml);
                });
        } else {
            infoContainer.slideUp(200).empty();
        }

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
                tr.append(`<td><strong><a href="/personal/?ViewID=${item.User_ID}" target="_blank">${escapeHtml(item.FIO)}</a></strong></td>`);

                if (boardData.permissions.canViewContacts) {
                    tr.append(`<td>${item.Phones || '-'}</td>`);
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

        if (data.total !== undefined) {
            $('#fstu-board-members-info').text(`Записів: ${data.total} | Сторінка ${data.page} з ${data.total_pages}`);

            let paginationHtml = '';
            if (data.total_pages > 1) {
                if (data.page > 1) {
                    paginationHtml += `<button class="fstu-btn--page fstu-members-page" data-page="${data.page - 1}">«</button>`;
                }
                paginationHtml += `<button class="fstu-btn--page active">${data.page}</button>`;
                if (data.page < data.total_pages) {
                    paginationHtml += `<button class="fstu-btn--page fstu-members-page" data-page="${data.page + 1}">»</button>`;
                }
            }
            $('#fstu-board-members-pagination').html(paginationHtml);
        }

        initDropdowns();
    }

    /**
     * 3. DROPDOWN (Анти-обрізання)
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
                const rect = $btn[0].getBoundingClientRect();
                $menu.css({
                    'position': 'fixed',
                    'top': rect.bottom + 'px',
                    'left': rect.right - 150 + 'px',
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

    function escapeHtml(text) {
        if (!text) return '';
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    /**
     * 3.5. СИСТЕМА ПОВІДОМЛЕНЬ (Toast)
     */
    function showToast(message, type = 'success') {
        $('.fstu-toast').remove(); // Видаляємо попередні

        const toastClass = type === 'success' ? 'fstu-toast--success' : 'fstu-toast--error';

        const $toast = $(`
            <div class="fstu-toast ${toastClass}">
                ${escapeHtml(message)}
            </div>
        `);

        $('body').append($toast);
        $toast.fadeIn(300);

        setTimeout(() => {
            $toast.fadeOut(400, function() { $(this).remove(); });
        }, 3000); // Зникає через 3 секунди
    }

    /**
     * 4. AJAX ЗАВАНТАЖЕННЯ (Опитування)
     */
    function loadPolls() {
        const container = $('#fstu-board-polls-container');
        container.html('<div class="fstu-text-center">Завантаження опитувань...</div>');

        $.post(boardData.ajaxUrl, {
            action: 'fstu_board_get_polls',
            nonce: boardData.nonce,
            question_type_id: 2,
            commission_type_id: $('#fstu-board-filter-type').val(),
            s_commission_id: $('#fstu-board-filter-commission').val()
        }, function(response) {
            if (response.success) {
                renderPolls(response?.data?.items || []);
            } else {
                const msg = response?.data?.message || 'Помилка завантаження опитувань';
                container.html(`<div class="fstu-text-center fstu-text-danger">${msg}</div>`);
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

            html += `<td class="fstu-text-center">
                        <button type="button" class="fstu-btn fstu-view-candidates" data-id="${item.Question_ID}">Відкрити</button>
                     </td>`;
            html += `</tr>`;

            html += `<tr class="fstu-candidates-row" id="candidates-row-${item.Question_ID}" style="display:none;">
                        <td colspan="6" style="padding: 0; background: #fafafa;">
                            <div class="fstu-candidates-container" id="candidates-container-${item.Question_ID}" style="padding: 15px; border-bottom: 2px solid #dcead6;">
                                </div>
                        </td>
                     </tr>`;
        });

        html += '</tbody></table></div>';
        container.html(html);

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
     * 5. AJAX ЗАВАНТАЖЕННЯ КАНДИДАТІВ
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
        let html = '';

        if (boardData.permissions.canManage) {
            html += `<div style="margin-bottom: 10px; text-align: right;">
                        <button type="button" class="fstu-btn fstu-btn--save fstu-add-candidate-btn" data-qid="${questionId}">+ Додати кандидата</button>
                     </div>`;
        }

        if (items.length === 0) {
            html += '<div class="fstu-text-center" style="padding: 10px;">Кандидати відсутні. Додайте першого кандидата, щоб розпочати голосування.</div>';
            container.html(html);
            return;
        }

        html += '<table class="fstu-table"><thead><tr>';
        html += '<th style="width: 40px;">№</th>';
        html += '<th>Кандидат / Напрямок</th>';
        html += '<th colspan="3" class="fstu-text-center">Результати голосування</th>';

        if (boardData.permissions.canVote) {
            html += '<th style="width: 200px;" class="fstu-text-center">Ваш голос</th>';
        }
        html += '</tr></thead><tbody>';

        items.forEach((item, index) => {
            const cntYes = parseInt(item.cnt_yes) || 0;
            const cntNo = parseInt(item.cnt_no) || 0;
            const cntAbs = parseInt(item.cnt_abstain) || 0;
            const total = cntYes + cntNo + cntAbs;

            const pYes = total > 0 ? (cntYes / total * 100) : 0;
            const pAbs = total > 0 ? (cntAbs / total * 100) : 0;
            const pNo = total > 0 ? (cntNo / total * 100) : 0;

            const progressHtml = `
                <div class="fstu-progress-wrapper">
                    <div class="fstu-progress-bar fstu-progress-bar--yes" style="width: ${pYes}%;" title="ЗА: ${cntYes}"></div>
                    <div class="fstu-progress-bar fstu-progress-bar--abstain" style="width: ${pAbs}%;" title="УТРИМАВСЯ: ${cntAbs}"></div>
                    <div class="fstu-progress-bar fstu-progress-bar--no" style="width: ${pNo}%;" title="ПРОТИ: ${cntNo}"></div>
                </div>
            `;

            // Формуємо tooltip для програми розвитку
            const devTooltip = item.Development ? `title="Програма розвитку: ${escapeHtml(item.Development)}"` : '';
            // Формуємо посилання на документ, якщо воно є
            const urlLink = (item.URL && item.URL.trim() !== '')
                ? `<br><a href="${escapeHtml(item.URL)}" target="_blank" class="fstu-doc-link">📄 Відкрити документ</a>`
                : '';

            html += `<tr>`;
            html += `<td class="fstu-text-center">${index + 1}</td>`;
            html += `<td>
                        <strong><a href="/personal/?ViewID=${item.User_ID}" target="_blank">${escapeHtml(item.FIO)}</a></strong><br>
                        <small class="fstu-candidate-dev" ${devTooltip}>${escapeHtml(item.Direction)}</small>
                        ${urlLink}
                     </td>`;
            // Додаємо кнопку "Хто проголосував", якщо є хоча б 1 голос (показується тільки авторизованим userfstu)
            let votersBtn = '';
            if (total > 0 && boardData.permissions.canVote) { // canVote зазвичай є у тих, хто має userfstu
                votersBtn = `<button class="fstu-voters-btn" data-cid="${item.CandidatesCommission_ID}" data-name="${escapeHtml(item.FIO)}">👁️ Хто проголосував</button>`;
            }

            html += `<td colspan="3" class="fstu-text-center" style="vertical-align: middle;">
                        ${progressHtml}
                        <div class="fstu-vote-stats">
                            <span class="fstu-vote-val--yes">ЗА: ${cntYes}</span>
                            <span class="fstu-vote-val--abstain">УТРИМ: ${cntAbs}</span>
                            <span class="fstu-vote-val--no">ПРОТИ: ${cntNo}</span>
                        </div>
                        ${votersBtn}
                     </td>`;

            if (boardData.permissions.canVote) {
                const voteVal = item.current_user_vote !== null ? item.current_user_vote : '';
                const btnYesClass = voteVal == '1' ? 'fstu-btn--page active' : 'fstu-btn--page';
                const btnAbstClass = voteVal == '0' ? 'fstu-btn--page active' : 'fstu-btn--page';
                const btnNoClass = voteVal == '2' ? 'fstu-btn--page active' : 'fstu-btn--page';

                html += `<td class="fstu-text-center">
                            <button class="fstu-cast-vote ${btnYesClass}" data-cid="${item.CandidatesCommission_ID}" data-val="1" data-uid="${item.User_ID}">ЗА</button>
                            <button class="fstu-cast-vote ${btnAbstClass}" data-cid="${item.CandidatesCommission_ID}" data-val="0" data-uid="${item.User_ID}">УТРИМ</button>
                            <button class="fstu-cast-vote ${btnNoClass}" data-cid="${item.CandidatesCommission_ID}" data-val="2" data-uid="${item.User_ID}">ПРОТИ</button>
                         </td>`;
            }
            html += `</tr>`;
        });

        html += '</tbody></table>';
        container.html(html);

        container.find('.fstu-cast-vote').on('click', function() {
            const cid = $(this).data('cid');
            const val = $(this).data('val');
            const candUid = $(this).data('uid');
            const btn = $(this);

            // Перевірка на Самовідвід (Голосування ПРОТИ самого себе)
            if (val == 2 && candUid == boardData.currentUserId) {
                const confirmMsg = "УВАГА! Ви голосуєте ПРОТИ власної кандидатури.\n\n" +
                    "Цю дію буде розцінено як САМОВІДВІД. Вашу кандидатуру буде остаточно ВИДАЛЕНО з опитування, " +
                    "а запис про це занесено в офіційний протокол.\n\n" +
                    "Ви впевнені, що хочете зняти свою кандидатуру?";

                if (!confirm(confirmMsg)) {
                    return; // Скасовуємо дію, якщо користувач передумав
                }
            }

            btn.siblings().prop('disabled', true);
            btn.text('...').prop('disabled', true);

            $.post(boardData.ajaxUrl, {
                action: 'fstu_board_cast_vote',
                nonce: boardData.nonce,
                candidate_id: cid,
                vote_value: val
            }, function(response) {
                if (response.success) {
                    showToast('Голос враховано', 'success');
                    loadCandidates(questionId);
                } else {
                    showToast(response.data.message, 'error');
                    loadCandidates(questionId);
                }
            }).fail(function() {
                showToast('Помилка сервера', 'error');
                loadCandidates(questionId);
            });
        });

        // Біндимо клік на кнопку "Хто проголосував"
        container.find('.fstu-voters-btn').off('click').on('click', function() {
            const cid = $(this).data('cid');
            const candidateName = $(this).data('name');

            $.post(boardData.ajaxUrl, {
                action: 'fstu_board_get_voters',
                nonce: boardData.nonce,
                candidate_id: cid
            }, function(response) {
                if (response.success) {
                    renderVotersModal(candidateName, response.data.items, response.data.is_admin);
                } else {
                    showToast(response.data.message, 'error');
                }
            }).fail(function() {
                showToast('Помилка сервера', 'error');
            });
        });
    }

    function renderVotersModal(candidateName, voters, isAdmin) {
        $('#fstu-voters-modal').remove(); // Видаляємо старе вікно, якщо є

        let html = `
        <div class="fstu-modal-overlay" id="fstu-voters-modal" style="display:flex;">
            <div class="fstu-modal-content">
                <div class="fstu-modal-header">
                    <h3 class="fstu-modal-title">Результати голосування</h3>
                    <button type="button" class="fstu-modal-close">×</button>
                </div>
                <div class="fstu-modal-body">
                    <div class="fstu-mb-15">
                        <small class="fstu-text-muted">Кандидат:</small><br>
                        <strong class="fstu-fw-bold">${candidateName}</strong>
                    </div>
                    <table class="fstu-table fstu-table--striped">
                        <thead>
                            <tr>
                                <th>ПІБ виборця</th>
                                ${isAdmin ? '<th class="fstu-text-center" style="width: 100px;">Голос</th>' : ''}
                            </tr>
                        </thead>
                        <tbody>
        `;

        if (voters.length === 0) {
            html += `<tr><td colspan="${isAdmin ? 2 : 1}" class="fstu-text-center">Ще ніхто не проголосував.</td></tr>`;
        } else {
            voters.forEach(v => {
                html += `<tr><td>${escapeHtml(v.FIO)}</td>`;

                // Друга колонка рендериться ТІЛЬКИ якщо сервер підтвердив, що це адмін
                if (isAdmin) {
                    let voteStr = '';
                    let color = '';
                    if (v.VoteValue == 1) { voteStr = 'ЗА'; color = 'green'; }
                    else if (v.VoteValue == 2) { voteStr = 'ПРОТИ'; color = 'red'; }
                    else if (v.VoteValue == 0) { voteStr = 'УТРИМ'; color = 'orange'; }

                    html += `<td class="fstu-text-center" style="color: ${color}; font-weight: bold; font-size: 12px;">${voteStr}</td>`;
                }
                html += `</tr>`;
            });
        }

        html += `</tbody></table></div></div></div>`;

        $('body').append(html);

        // Закриття по кліку на кнопку "Х"
        $('#fstu-voters-modal .fstu-modal-close').on('click', function() {
            $('#fstu-voters-modal').fadeOut(200, function(){ $(this).remove(); });
        });

        // Закриття по кліку на фон
        $('#fstu-voters-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).fadeOut(200, function(){ $(this).remove(); });
            }
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
                tr.append(`<td><strong class="fstu-fw-bold">${escapeHtml(log.FIO)}</strong><br>${escapeHtml(log.Logs_Text)}</td>`);
                tr.append(`<td class="fstu-text-center">${escapeHtml(log.Logs_Error)}</td>`);
                tbody.append(tr);
            });
        }

        $('#fstu-board-protocol-info').text(`Записів: ${data.total} | Сторінка ${data.page} з ${data.total_pages}`);

        let paginationHtml = '';
        if (data.total_pages > 1) {
            if (data.page > 1) {
                paginationHtml += `<button class="fstu-btn--page fstu-protocol-page" data-page="${data.page - 1}">«</button>`;
            }
            paginationHtml += `<button class="fstu-btn--page active">${data.page}</button>`;
            if (data.page < data.total_pages) {
                paginationHtml += `<button class="fstu-btn--page fstu-protocol-page" data-page="${data.page + 1}">»</button>`;
            }
        }
        $('#fstu-board-protocol-pagination').html(paginationHtml);
    }

    $(document).on('change', '#fstu-board-protocol-per-page', function() {
        loadProtocol(1);
    });

    $(document).on('click', '.fstu-protocol-page', function() {
        const page = $(this).data('page');
        loadProtocol(page);
    });

    let searchTimeout;
    $(document).on('input', '#fstu-board-search-protocol', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            loadProtocol(1);
        }, 500);
    });

    /**
     * 7. МОДАЛКИ (Відкриття та Заповнення)
     */
    function openModal(type, id, extraData = null) {
        const modalId = '#fstu-modal-' + type;

        if ($(modalId).length === 0) {
            // Завантажуємо форму через AJAX
            $.post(boardData.ajaxUrl, {
                action: 'fstu_board_get_modal',
                nonce: boardData.nonce,
                modal: type
            }, function(response) {
                if (response.success) {
                    $('#fstu-board-modals-container').append(response.data.html);
                    fillModalDataAndShow(type, id, extraData);
                } else {
                    showToast(response.data.message, 'error');
                }
            });
        } else {
            fillModalDataAndShow(type, id, extraData);
        }
    }

    function fillModalDataAndShow(type, id, extraData) {
        const modalId = '#fstu-modal-' + type;
        const $form = $(modalId).find('form');

        // Очищаємо форму
        $form[0].reset();

        if (id) {
            $(modalId).find('.fstu-modal-title').text('Редагування запису');
            // Встановлюємо ID для оновлення
            $(modalId).find('input[name="' + (type === 'member' ? 'commission_id' : 'question_id') + '"]').val(id);

            // Якщо редагуємо члена, підтягуємо додаткові дані
            if (type === 'member' && extraData) {
                $(modalId).find('#modal-member-user-id').val(extraData.userId);
                $(modalId).find('#modal-member-user-search').val(extraData.fio);
                $(modalId).find('#modal-member-user-search')[0].setCustomValidity(''); // Знімаємо помилку валідації
                $(modalId).find('#modal-member-role').val(extraData.roleId);
            }
        } else {
            $(modalId).find('.fstu-modal-title').text('Додавання запису');
            $(modalId).find('input[name="' + (type === 'member' ? 'commission_id' : 'question_id') + '"]').val('');

            if (type === 'candidate' && extraData && extraData.questionId) {
                $(modalId).find('#modal-candidate-question-id').val(extraData.questionId);
            }
        }

        // Показуємо модалку
        $(modalId).css('display', 'flex').hide().fadeIn(200);
    }

    // Запускаємо ініціалізацію
    init();
});
