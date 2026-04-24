/**
 * Скрипт модуля "Комісії з видів туризму (Board)".
 * * Version: 1.2.0
 * Date_update: 2026-04-24
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

        // -----------------------------------------------------
        // АВТОКОМПЛІТ (AJAX пошук користувача для модалки)
        // -----------------------------------------------------
        let autocompleteTimeout;
        $(document).on('input', '#modal-member-user-search', function() {
            const $input = $(this);
            const $results = $('#modal-member-user-results');
            const $hidden = $('#modal-member-user-id');
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
                    }
                    $results.show();
                });
            }, 400);
        });

        // Клік по результату автокомпліту
        $(document).on('click', '.fstu-autocomplete-item', function() {
            if (!$(this).data('id')) return;
            const $results = $(this).closest('.fstu-autocomplete-list');
            const $input = $('#modal-member-user-search');
            const $hidden = $('#modal-member-user-id');

            $hidden.val($(this).data('id'));
            $input.val($(this).text());
            $input[0].setCustomValidity('');
            $results.hide().empty();
        });

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
        html += '<th style="width: 100px;">Дедлайн</th>';
        html += '<th>Питання / Документ</th>';
        html += '<th style="width: 80px;" class="fstu-text-center">Голосів</th>';
        html += '<th style="width: 100px;" class="fstu-text-center">Статус</th>';
        html += '<th style="width: 120px;" class="fstu-text-center">Дії</th>';
        html += '</tr></thead><tbody>';

        items.forEach((item, index) => {
            const statusBadge = item.Question_State == '0'
                ? '<span class="fstu-badge fstu-badge--success">Відкрите</span>'
                : '<span class="fstu-badge fstu-badge--warning">Закрите</span>';

            const isExpired = new Date(item.Question_DateEnd) < new Date();
            const expiredBadge = isExpired ? '<br><span style="color:red; font-size:10px;">Завершено</span>' : '';

            html += `<tr>`;
            html += `<td class="fstu-text-center">${index + 1}</td>`;
            html += `<td>${item.Question_DateEnd} ${expiredBadge}</td>`;
            html += `<td><strong>${escapeHtml(item.Question_Name)}</strong></td>`;
            html += `<td class="fstu-text-center">${item.votes_count || 0} / ${item.SetCommission_CountMembers || 0}</td>`;
            html += `<td class="fstu-text-center">${statusBadge}</td>`;

            html += `<td class="fstu-text-center">
                        <button type="button" class="fstu-btn fstu-view-poll" data-id="${item.Question_ID}" data-state="${item.Question_State}" data-expired="${isExpired}">Відкрити</button>
                     </td>`;
            html += `</tr>`;

            html += `<tr class="fstu-poll-details-row" id="poll-row-${item.Question_ID}" style="display:none;">
                        <td colspan="6" style="padding: 0; background: #fafafa;">
                            <div class="fstu-poll-container" id="poll-container-${item.Question_ID}" style="padding: 20px; border-bottom: 2px solid #dcead6;">
                            </div>
                        </td>
                     </tr>`;
        });

        html += '</tbody></table></div>';
        container.html(html);

        $('.fstu-view-poll').on('click', function() {
            const qId = $(this).data('id');
            const state = $(this).data('state');
            const isExpired = $(this).data('expired');
            const row = $(`#poll-row-${qId}`);

            if (row.is(':visible')) {
                row.hide();
                $(this).text('Відкрити');
            } else {
                row.show();
                $(this).text('Закрити');
                loadPollDetails(qId, state, isExpired);
            }
        });
    }

    function loadPollDetails(questionId, state, isExpired) {
        const container = $(`#poll-container-${questionId}`);
        container.html('<div class="fstu-text-center">Завантаження деталей...</div>');

        $.post(boardData.ajaxUrl, {
            action: 'fstu_board_get_poll_details',
            nonce: boardData.nonce,
            question_id: questionId,
            state: state
        }, function(response) {
            if (response.success) {
                renderPollCard(questionId, isExpired, response.data);
            } else {
                container.html(`<div class="fstu-text-danger">${response.data.message}</div>`);
            }
        });
    }

    function renderPollCard(questionId, isExpired, data) {
        const container = $(`#poll-container-${questionId}`);
        const answers = data.answers || [];
        const stats = data.stats || [];
        const voters = data.voters || [];

        let html = `<div style="max-width: 800px; margin: 0 auto; background: #fff; padding: 20px; border: 1px solid #e2e4e7; border-radius: 4px;">`;

        // Блок кнопок голосування
        html += `<h4 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">Ваш голос:</h4>`;

        if (isExpired) {
            html += `<div class="fstu-text-danger" style="margin-bottom: 20px; font-weight: bold;">Голосування завершено. Прийняття голосів закрито.</div>`;
        } else if (!boardData.permissions.canVote) {
            html += `<div class="fstu-text-muted" style="margin-bottom: 20px;">Голосувати можуть лише члени ФСТУ, які входять до складу цієї комісії.</div>`;
        } else {
            html += `<div style="display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap;">`;
            answers.forEach(ans => {
                const isActive = data.current_vote == ans.Answer_ID ? 'background: #007cba; color: #fff; border-color: #007cba;' : '';
                html += `<button class="fstu-btn fstu-cast-vote-btn" data-qid="${questionId}" data-aid="${ans.Answer_ID}" style="${isActive}">${escapeHtml(ans.Answer_Name)}</button>`;
            });
            html += `</div>`;
        }

        // Блок статистики (Прогрес-бари)
        html += `<h4 style="border-bottom: 1px solid #eee; padding-bottom: 10px;">Загальні результати:</h4>`;
        html += `<div style="margin-bottom: 25px;">`;

        let totalVotes = stats.reduce((sum, item) => sum + parseInt(item.cnt), 0);

        if (totalVotes === 0) {
            html += `<div class="fstu-text-muted">Ще ніхто не проголосував.</div>`;
        } else {
            answers.forEach(ans => {
                const stat = stats.find(s => s.Answer_ID === ans.Answer_ID);
                const cnt = stat ? parseInt(stat.cnt) : 0;
                const percent = (cnt / totalVotes * 100).toFixed(1);

                let color = '#46b450'; // ЗА (зелений)
                if (ans.Answer_Name.toLowerCase().includes('проти')) color = '#d9534f'; // червоний
                if (ans.Answer_Name.toLowerCase().includes('утрим')) color = '#ffb900'; // жовтий

                html += `
                <div style="margin-bottom: 10px;">
                    <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                        <strong>${escapeHtml(ans.Answer_Name)}</strong>
                        <span>${cnt} голосів (${percent}%)</span>
                    </div>
                    <div style="width: 100%; background: #eee; height: 8px; border-radius: 4px; overflow: hidden;">
                        <div style="width: ${percent}%; background: ${color}; height: 100%;"></div>
                    </div>
                </div>`;
            });
        }
        html += `</div>`;

        // Блок поіменного голосування
        if (voters.length > 0) {
            html += `<h4 style="border-bottom: 1px solid #eee; padding-bottom: 10px;">Поіменний протокол:</h4>`;
            html += `<table class="fstu-table fstu-table--striped"><thead><tr><th>ПІБ</th><th style="width: 100px;" class="fstu-text-center">Голос</th><th style="width: 120px;" class="fstu-text-center">Джерело</th><th style="width: 140px;" class="fstu-text-center">Дата</th></tr></thead><tbody>`;

            voters.forEach(v => {
                let color = 'inherit';
                if (v.Answer_Name.toLowerCase().includes('за')) color = 'green';
                if (v.Answer_Name.toLowerCase().includes('проти')) color = 'red';
                if (v.Answer_Name.toLowerCase().includes('утрим')) color = 'orange';

                const sourceText = v.API === 'telegram-bot' ? '📱 Telegram' : '💻 Сайт';

                html += `<tr>
                    <td><a href="/personal/?ViewID=${v.User_ID}" target="_blank">${escapeHtml(v.FIO)}</a></td>
                    <td class="fstu-text-center" style="color: ${color}; font-weight: bold;">${escapeHtml(v.Answer_Name)}</td>
                    <td class="fstu-text-center"><small class="fstu-text-muted">${sourceText}</small></td>
                    <td class="fstu-text-center"><small>${v.VotingResults_DateCreate}</small></td>
                </tr>`;
            });
            html += `</tbody></table>`;
        } else if (stats.length > 0) {
            html += `<div class="fstu-text-muted" style="font-size: 12px;">Поіменний протокол прихований (закрите голосування).</div>`;
        }

        html += `</div>`;
        container.html(html);

        // Обробник голосування
        container.find('.fstu-cast-vote-btn').on('click', function() {
            const qId = $(this).data('qid');
            const aId = $(this).data('aid');

            $(this).siblings().prop('disabled', true);
            $(this).text('...').prop('disabled', true);

            $.post(boardData.ajaxUrl, {
                action: 'fstu_board_cast_vote',
                nonce: boardData.nonce,
                question_id: qId,
                answer_id: aId,
                commission_type_id: $('#fstu-board-filter-type').val(),
                s_commission_id: $('#fstu-board-filter-commission').val()
            }, function(response) {
                if (response.success) {
                    showToast('Голос успішно зараховано', 'success');
                    loadPollDetails(qId, $(`#poll-row-${qId}`).prev().find('.fstu-view-poll').data('state'), isExpired);
                } else {
                    showToast(response.data.message, 'error');
                    loadPollDetails(qId, $(`#poll-row-${qId}`).prev().find('.fstu-view-poll').data('state'), isExpired);
                }
            }).fail(function() {
                showToast('Помилка сервера', 'error');
                loadPollDetails(qId, $(`#poll-row-${qId}`).prev().find('.fstu-view-poll').data('state'), isExpired);
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
        }

        // Показуємо модалку
        $(modalId).css('display', 'flex').hide().fadeIn(200);
    }

    // Запускаємо ініціалізацію
    init();
});
