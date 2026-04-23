/**
 * Клієнтська логіка модуля Електронні вибори STV.
 * Version: 1.0.0
 * Date_update: 2026-04-22
 */
jQuery(document).ready(function($) {
    'use strict';

    // Впевнимося, що ми на правильній сторінці
    if (!$('#fstu-elections-module').length) return;

    // Глобальний стан модуля
    let state = {
        items: [],
        filteredItems: [],
        page: 1,
        perPage: 10,
        search: '',
        permissions: {},
        // Стан для протоколу
        protoPage: 1,
        protoPerPage: 10,
        protoSearch: ''
    };

    // Ініціалізація
    init();

    function init() {
        loadElections();
        bindEvents();
    }

    // Завантаження списку виборів
    function loadElections() {
        $('#fstu-elections-tbody').html('<tr><td colspan="5" style="text-align:center; padding: 20px;">Завантаження...</td></tr>');

        // Зверніть увагу: fstuSettings має бути переданий через wp_localize_script у class-elections-list.php
        $.ajax({
            url: fstuSettings.ajaxurl, // Стандартна змінна WP AJAX
            type: 'POST',
            data: {
                action: 'fstu_get_elections',
                nonce: fstuSettings.nonce
            },
            success: function(response) {
                if (response.success) {
                    state.items = response.data.items || [];
                    state.permissions = response.data.permissions || {};
                    applyFilters();
                } else {
                    $('#fstu-elections-tbody').html('<tr><td colspan="5" class="fstu-text-danger" style="text-align:center;">Помилка: ' + response.data.message + '</td></tr>');
                }
            },
            error: function() {
                $('#fstu-elections-tbody').html('<tr><td colspan="5" class="fstu-text-danger" style="text-align:center;">Сталася помилка при завантаженні даних.</td></tr>');
            }
        });
    }

    // Прив'язка подій
    function bindEvents() {
        // Пошук
        $(document).on('input', '#fstu-election-search', function() {
            state.search = $(this).val().toLowerCase();
            state.page = 1;
            applyFilters();
        });

        // Зміна кількості на сторінці
        $(document).on('change', '#fstu-elections-per-page', function() {
            state.perPage = parseInt($(this).val(), 10);
            state.page = 1;
            applyFilters();
        });

        // Кліки пагінації
        $(document).on('click', '.fstu-btn--page', function() {
            if ($(this).prop('disabled') || $(this).hasClass('active')) return;
            state.page = parseInt($(this).data('page'), 10);
            renderTable();
        });

        // Модальне вікно створення/редагування
        $(document).on('click', '#fstu-election-create-btn', function() {
            $('#fstu-form-election')[0].reset();
            $('#fstu-election-id').val(0);
            $('#fstu-modal-election-title').text('Створення виборів');
            $('#fstu-modal-election').fadeIn(200);
        });

        // Закриття модалки
        $(document).on('click', '.fstu-modal-close, .fstu-modal-close-btn', function() {
            $('.fstu-modal').fadeOut(200);
        });

        // Збереження виборів
        $(document).on('click', '#fstu-btn-save-election', function() {
            saveElection();
        });

        // Dropdown меню дій (Анти-обрізання)
        $(document).on('click', '.fstu-dropdown-toggle', function(e) {
            e.stopPropagation();
            let $btn = $(this);
            let electionId = $btn.data('id');

            // Видаляємо всі відкриті меню
            $('.fstu-dropdown-menu-dynamic').remove();
            $('.fstu-dropdown-toggle').removeClass('fstu-dropdown--open');

            $btn.addClass('fstu-dropdown--open');

            // Створюємо меню
            let menuHtml = '<div class="fstu-dropdown-menu-dynamic">';
            menuHtml += '<button type="button" class="fstu-dropdown-item btn-edit" data-id="' + electionId + '">✎ Редагувати</button>';
            menuHtml += '<button type="button" class="fstu-dropdown-item btn-manage" data-id="' + electionId + '">⚙ Управління фазами</button>';

            let election = state.items.find(e => parseInt(e.Election_ID) === parseInt(electionId));

            // Обробка модалки фаз
            $(document).on('click', '.btn-manage', function() {
                let id = $(this).data('id');
                let election = state.items.find(e => parseInt(e.Election_ID) === parseInt(id));
                if (election) {
                    $('#fstu-phase-election-id').val(id);
                    $('#fstu-phase-status').val(election.Status);
                    $('#fstu-modal-phase').fadeIn(200);
                }
                $('.fstu-dropdown-menu-dynamic').remove();
            });

            $(document).on('click', '#fstu-btn-save-phase', function() {
                let electionId = $('#fstu-phase-election-id').val();
                let status = $('#fstu-phase-status').val();
                let $btn = $(this);

                $btn.prop('disabled', true).text('Збереження...');
                $.post(fstuSettings.ajaxurl, {
                    action: 'fstu_change_election_phase',
                    nonce: fstuSettings.nonce,
                    election_id: electionId,
                    status: status
                }, function(res) {
                    $btn.prop('disabled', false).text('Змінити статус');
                    if (res.success) {
                        $('#fstu-modal-phase').fadeOut(200);
                        loadElections();
                    } else {
                        alert(res.data.message);
                    }
                });
            });

            // Додаємо кнопку номінації та список кандидатів
            if (election && election.Status === 'nomination') {
                menuHtml += '<button type="button" class="fstu-dropdown-item btn-nominate" data-id="' + electionId + '">🙋 Висунути кандидата</button>';
                menuHtml += '<button type="button" class="fstu-dropdown-item btn-candidates" data-id="' + electionId + '">👥 Кандидати</button>';
            }

            // Додаємо кнопку голосування
            if (election && election.Status === 'voting') {
                menuHtml += '<button type="button" class="fstu-dropdown-item btn-vote" data-id="' + electionId + '">🗳 Голосувати</button>';
            }

            // Адмінська кнопка примусового підрахунку
            if (election && state.permissions.canManage && (election.Status === 'voting' || election.Status === 'calculation')) {
                menuHtml += '<button type="button" class="fstu-dropdown-item btn-calculate" data-id="' + electionId + '">⚙ Завершити і підрахувати</button>';
            }

            // Кнопка звіту
            if (election && election.Status === 'completed') {
                menuHtml += '<button type="button" class="fstu-dropdown-item btn-report" data-id="' + electionId + '">📊 Звіт та Результати</button>';
            }

            menuHtml += '</div>';

            $('body').append(menuHtml);
            let $menu = $('.fstu-dropdown-menu-dynamic');

            // Позиціонування Fixed
            let rect = $btn[0].getBoundingClientRect();
            $menu.css({
                top: rect.bottom + window.scrollY + 2 + 'px',
                left: rect.left + window.scrollX - ($menu.outerWidth() - rect.width) + 'px'
            });
        });

        // Закриття dropdown при кліку поза ним або скролі
        $(document).on('click scroll', function(e) {
            if (!$(e.target).closest('.fstu-dropdown-toggle, .fstu-dropdown-menu-dynamic').length) {
                $('.fstu-dropdown-menu-dynamic').remove();
                $('.fstu-dropdown-toggle').removeClass('fstu-dropdown--open');
            }
        });

        // Редагування виборів (через dropdown)
        $(document).on('click', '.btn-edit', function() {
            let id = $(this).data('id');
            let election = state.items.find(e => parseInt(e.Election_ID) === parseInt(id));
            if (election) {
                $('#fstu-election-id').val(election.Election_ID);
                $('#fstu-election-name').val(election.Election_Name);
                $('#fstu-election-tourism').val(election.TourismType_ID);
                $('#fstu-election-count').val(election.Settings_Candidates_Count);
                $('#fstu-election-nom-days').val(election.Settings_Nomination_Days);
                $('#fstu-election-ext-days').val(election.Settings_Extension_Days);
                $('#fstu-election-vote-days').val(election.Settings_Voting_Days);

                $('#fstu-modal-election-title').text('Редагування виборів ID ' + id);
                $('#fstu-modal-election').fadeIn(200);
            }
            $('.fstu-dropdown-menu-dynamic').remove();
        });

        // Відкриття модалки номінації
        $(document).on('click', '.btn-nominate', function() {
            let id = $(this).data('id');
            $('#fstu-form-nomination')[0].reset();
            $('#fstu-nomination-election-id').val(id);
            $('#fstu-nomination-user-wrapper').hide();

            // Знищуємо старий інстанс Select2, щоб уникнути конфліктів
            if ($.fn.select2 && $('#fstu-nomination-user-id').hasClass("select2-hidden-accessible")) {
                $('#fstu-nomination-user-id').select2('destroy');
            }
            $('#fstu-nomination-user-id').empty().append('<option value="">Почніть вводити ПІБ...</option>');

            // Ініціалізація Select2 з фіксом z-index
            if ($.fn.select2) {
                $('#fstu-nomination-user-id').select2({
                    dropdownParent: $('#fstu-modal-nomination .fstu-modal-content'), // Зміна контейнера для правильного позиціонування
                    width: '100%', // Примусова ширина
                    ajax: {
                        url: fstuSettings.ajaxurl,
                        type: 'POST', // Примусовий POST запит
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                action: 'fstu_search_users_for_nomination',
                                nonce: fstuSettings.nonce,
                                q: params.term,
                                election_id: $('#fstu-nomination-election-id').val()
                            };
                        },
                        processResults: function(data) {
                            return { results: data.success ? data.data.results : [] };
                        },
                        cache: true
                    },
                    minimumInputLength: 3,
                    placeholder: 'Почніть вводити ПІБ...',
                    language: 'uk'
                });

                // Фікс для z-index випадаючого списку Select2 поверх модалки ФСТУ
                $('.select2-container').css('z-index', '999999');
            }

            $('#fstu-modal-nomination').fadeIn(200);
            $('.fstu-dropdown-menu-dynamic').remove();
        });

        // Перемикання типу номінації (Самовисування / Інший)
        $(document).on('change', 'input[name="nomination_type"]', function() {
            if ($(this).val() === 'other') {
                $('#fstu-nomination-user-wrapper').slideDown();
                $('#fstu-nomination-user-id').prop('required', true);
            } else {
                $('#fstu-nomination-user-wrapper').slideUp();
                $('#fstu-nomination-user-id').prop('required', false);
            }
        });

        // Збереження номінації
        $(document).on('click', '#fstu-btn-save-nomination', function() {
            let form = $('#fstu-form-nomination')[0];
            if (!form.reportValidity()) return;

            let $btn = $(this);
            $btn.prop('disabled', true).text('Відправка...');

            let formData = new FormData(form);
            formData.append('action', 'fstu_nominate_candidate');
            formData.append('nonce', fstuSettings.nonce);

            $.ajax({
                url: fstuSettings.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    $btn.prop('disabled', false).text('Висунути');
                    if (res.success) {
                        showToast(res.data.message, 'success');
                        $('#fstu-modal-nomination').fadeOut(200);
                    } else {
                        showToast(res.data.message, 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('Висунути');
                    showToast('Сталася помилка з\'єднання з сервером.', 'error');
                }
            });
        });
    }

    function applyFilters() {
        let s = state.search;
        state.filteredItems = state.items.filter(item => {
            if (!s) return true;
            let nameMatch = (item.Election_Name || '').toLowerCase().indexOf(s) !== -1;
            return nameMatch;
        });
        renderTable();
    }

    function renderTable() {
        let total = state.filteredItems.length;
        let totalPages = Math.ceil(total / state.perPage) || 1;
        if (state.page > totalPages) state.page = totalPages;

        let start = (state.page - 1) * state.perPage;
        let end = start + state.perPage;
        let pageItems = state.filteredItems.slice(start, end);

        let $tbody = $('#fstu-elections-tbody');
        $tbody.empty();

        if (pageItems.length === 0) {
            $tbody.html('<tr><td colspan="5" style="text-align:center;">Записів не знайдено</td></tr>');
        } else {
            pageItems.forEach(item => {
                let statusBadge = getStatusBadge(item.Status);
                let dateRange = item.Date_Voting_Start ? (item.Date_Voting_Start.split(' ')[0] + ' — ' + (item.Date_Voting_End ? item.Date_Voting_End.split(' ')[0] : '...')) : '—';

                let row = `<tr>
                    <td class="fstu-td-id">${item.Election_ID}</td>
                    <td class="fstu-td-name"><strong>${escapeHtml(item.Election_Name)}</strong><br><small class="fstu-text-muted">Мандатів: ${item.Settings_Candidates_Count}</small></td>
                    <td class="fstu-td-status">${statusBadge}</td>
                    <td class="fstu-td-date">${dateRange}</td>
                    <td class="fstu-td-actions" style="white-space: nowrap;">`;

                if (state.permissions.canManage) {
                    // Адміністратори бачать Dropdown з усіма діями
                    row += `<button type="button" class="fstu-dropdown-toggle" data-id="${item.Election_ID}" title="Дії" aria-label="Дії">▼</button>`;
                } else {
                    // Звичайні користувачі бачать кнопки ТІЛЬКИ якщо вони є членами виду туризму цих виборів
                    if (item.is_member) {
                        if (item.Status === 'nomination') {
                            row += `<button type="button" class="fstu-btn fstu-btn--secondary btn-nominate" data-id="${item.Election_ID}" style="margin-right:4px;">🙋 Висунути</button>`;
                            row += `<button type="button" class="fstu-btn fstu-btn--default btn-candidates" data-id="${item.Election_ID}">👥 Кандидати</button>`;
                        } else if (item.Status === 'voting') {
                            row += `<button type="button" class="fstu-btn fstu-btn--save btn-vote" data-id="${item.Election_ID}">🗳 Голосувати</button>`;
                        } else if (item.Status === 'completed') {
                            row += `<button type="button" class="fstu-btn fstu-btn--default btn-report" data-id="${item.Election_ID}">📊 Звіт</button>`;
                        } else {
                            row += `<span class="fstu-text-muted">—</span>`;
                        }
                    } else {
                        // Якщо не член виду туризму - бачить тільки кнопку перегляду кандидатів або звіту
                        if (item.Status === 'completed') {
                            row += `<button type="button" class="fstu-btn fstu-btn--default btn-report" data-id="${item.Election_ID}">📊 Звіт</button>`;
                        } else {
                            row += `<small class="fstu-text-muted">Тільки для членів виду</small>`;
                        }
                    }
                }

                row += `</td></tr>`;
                $tbody.append(row);
            });
        }

        renderPagination(total, totalPages, '#fstu-elections-pagination', '#fstu-elections-info', state.page, 'fstu-btn--page');
    }

    function getStatusBadge(status) {
        let text = 'ЧЕРНЕТКА';
        let cls = 'fstu-badge--default';
        if (status === 'nomination') { text = 'ВИСУНЕННЯ'; cls = 'fstu-badge--warning'; }
        if (status === 'voting') { text = 'ГОЛОСУВАННЯ'; cls = 'fstu-badge--success'; }
        if (status === 'calculation') { text = 'ПІДРАХУНОК'; cls = 'fstu-badge--danger'; }
        if (status === 'completed') { text = 'ЗАВЕРШЕНО'; cls = 'fstu-badge--info'; }
        return `<span class="fstu-badge ${cls}">${text}</span>`;
    }

    function renderPagination(total, totalPages, controlsId, infoId, currentPage, btnClass) {
        let $controls = $(controlsId);
        let $info = $(infoId);
        $controls.empty();

        if (totalPages > 1) {
            let prevDisabled = currentPage === 1 ? 'disabled' : '';
            let nextDisabled = currentPage === totalPages ? 'disabled' : '';

            $controls.append(`<button class="fstu-btn ${btnClass}" data-page="1" ${prevDisabled}>&laquo;</button>`);
            $controls.append(`<button class="fstu-btn ${btnClass}" data-page="${currentPage - 1}" ${prevDisabled}>&lsaquo;</button>`);

            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, currentPage + 2);

            if (startPage > 1) {
                $controls.append(`<button class="fstu-btn ${btnClass}" data-page="1">1</button>`);
                if (startPage > 2) $controls.append(`<span class="fstu-pagination__ellipsis">&hellip;</span>`);
            }

            for (let i = startPage; i <= endPage; i++) {
                let active = currentPage === i ? 'fstu-btn--page-active' : '';
                $controls.append(`<button class="fstu-btn ${btnClass} ${active}" data-page="${i}">${i}</button>`);
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) $controls.append(`<span class="fstu-pagination__ellipsis">&hellip;</span>`);
                $controls.append(`<button class="fstu-btn ${btnClass}" data-page="${totalPages}">${totalPages}</button>`);
            }

            $controls.append(`<button class="fstu-btn ${btnClass}" data-page="${currentPage + 1}" ${nextDisabled}>&rsaquo;</button>`);
            $controls.append(`<button class="fstu-btn ${btnClass}" data-page="${totalPages}" ${nextDisabled}>&raquo;</button>`);
        }

        $info.text(`Записів: ${total} | Сторінка ${currentPage} з ${totalPages || 1}`);
    }

    function saveElection() {
        let form = $('#fstu-form-election')[0];
        if (!form.reportValidity()) return;

        let $btn = $('#fstu-btn-save-election');
        $btn.prop('disabled', true).text('Збереження...');

        let formData = new FormData(form);
        formData.append('action', 'fstu_save_election');
        formData.append('nonce', fstuSettings.nonce);

        $.ajax({
            url: fstuSettings.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                $btn.prop('disabled', false).text('Зберегти');
                if (res.success) {
                    $('.fstu-modal').fadeOut(200);
                    loadElections(); // Перезавантажуємо таблицю
                    showToast('Вибори успішно збережено.', 'success');
                } else {
                    showToast(res.data.message, 'error');
                }
            },
            error: function() {
                $btn.prop('disabled', false).text('Зберегти');
                showToast('Сталася помилка з\'єднання з сервером.', 'error');
            }
        });
    }

    // Helper для безпечного виводу
    function escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe.toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    /* ======================================================================
       ЛОГІКА ГОЛОСУВАННЯ ТА КАБІНКИ (Voting Booth)
       ====================================================================== */

    $(document).on('click', '.btn-vote', function() {
        let electionId = $(this).data('id');
        $('.fstu-dropdown-menu-dynamic').remove();
        openVotingBooth(electionId);
    });

    $(document).on('click', '.btn-close-booth', function() {
        $('#fstu-voting-booth').fadeOut(200);
    });

    function openVotingBooth(electionId) {
        $('#fstu-vote-election-id').val(electionId);
        $('#fstu-voting-debt-alert, #fstu-voting-done-alert, #fstu-voting-workspace, #fstu-btn-submit-ballot, #fstu-voting-legend').hide();
        $('#fstu-candidates-source, #fstu-candidates-target').empty();
        $('#fstu-voting-booth').fadeIn(200);

        // Завантажуємо дані з бекенду
        $.ajax({
            url: fstuSettings.ajaxurl,
            type: 'POST',
            data: { action: 'fstu_get_voting_booth', nonce: fstuSettings.nonce, election_id: electionId },
            success: function(res) {
                if (!res.success) {
                    $('#fstu-voting-booth').fadeOut(200);
                    return showToast(res.data ? res.data.message : 'Помилка даних', 'error');
                }

                let data = res.data;

                if (data.already_voted) {
                    $('#fstu-voting-done-alert').show();
                    checkLocalReceipt(electionId);
                    return;
                }

                if (data.has_debt) {
                    $('#fstu-voting-debt-alert').show();
                    return;
                }

                // Генеруємо HTML карток
                let cardsHtml = {};
                if (data.candidates && Array.isArray(data.candidates)) {
                    data.candidates.forEach(c => {
                        let debtClass = c.has_debt ? 'fstu-candidate--debt' : '';
                        let link = c.Motivation_URL ? `<a href="${escapeHtml(c.Motivation_URL)}" target="_blank" style="font-size: 11px; margin-left: 5px;">Програма</a>` : '';

                        cardsHtml[c.Candidate_ID] = `<li class="fstu-candidate-card ${debtClass}" data-id="${c.Candidate_ID}" data-name="${escapeHtml(c.FIO)}">
                            <div style="display:flex; flex-direction:column;">
                                <strong>${escapeHtml(c.FIO)}</strong>
                                <small class="fstu-text-muted">${escapeHtml(c.Region_Name)} ${link}</small>
                            </div>
                            <span style="color:#ccc; font-size:18px;">≡</span>
                        </li>`;
                    });
                }

                // Відновлення чернетки з безпечним парсингом
                let draftIds = [];
                try {
                    let draftJson = localStorage.getItem('fstu_voting_draft_' + electionId);
                    if (draftJson) draftIds = JSON.parse(draftJson);
                } catch (e) {
                    console.error('Помилка читання чернетки', e);
                    localStorage.removeItem('fstu_voting_draft_' + electionId);
                }

                // Спочатку додаємо тих, хто в чернетці
                if (Array.isArray(draftIds)) {
                    draftIds.forEach(id => {
                        if (cardsHtml[id]) {
                            $('#fstu-candidates-target').append(cardsHtml[id]);
                            delete cardsHtml[id];
                        }
                    });
                }

                // Інші
                Object.values(cardsHtml).forEach(card => {
                    $('#fstu-candidates-source').append(card);
                });

                $('#fstu-voting-workspace, #fstu-btn-submit-ballot, #fstu-voting-legend').show();
                initSortable();
            },
            error: function(xhr) {
                $('#fstu-voting-booth').fadeOut(200);
                showToast('Помилка сервера. Відкрийте консоль браузера (F12) для деталей.', 'error');
                console.error('AJAX Error:', xhr.responseText);
            }
        });
    }

    function updateVotingDraft() {
        let electionId = $('#fstu-vote-election-id').val();
        let draft = [];
        $('#fstu-candidates-target .fstu-candidate-card').each(function() {
            draft.push($(this).data('id'));
        });
        localStorage.setItem('fstu_voting_draft_' + electionId, JSON.stringify(draft));
    }

    function initSortable() {
        if (typeof Sortable === 'undefined') return showToast('Помилка завантаження бібліотеки SortableJS', 'error');

        new Sortable(document.getElementById('fstu-candidates-source'), {
            group: 'shared',
            animation: 150,
            sort: false, // У лівій колонці не можна міняти порядок
            onSort: function () {
                updateVotingDraft(); // Зберігаємо при будь-якій зміні
            }
        });

        new Sortable(document.getElementById('fstu-candidates-target'), {
            group: 'shared',
            animation: 150,
            onSort: function () {
                updateVotingDraft(); // Зберігаємо при перетягуванні та сортуванні
            }
        });
    }

    // Відправка бюлетеня
    $(document).on('click', '#fstu-btn-submit-ballot', function() {
        let electionId = $('#fstu-vote-election-id').val();
        let ballotIds = [];
        let ballotNames = [];

        $('#fstu-candidates-target .fstu-candidate-card').each(function() {
            ballotIds.push($(this).data('id'));
            ballotNames.push($(this).data('name'));
        });

        // Мінімальна кількість кандидатів
        let minRequired = Math.min(7, $('#fstu-candidates-source .fstu-candidate-card').length + ballotIds.length);

        if (ballotIds.length < minRequired) {
            return showToast('Згідно правил, ви повинні обрати та проранжувати мінімум ' + minRequired + ' кандидатів.', 'error');
        }

        if (!confirm('Ви впевнені? Цю дію неможливо скасувати. Ваш голос буде анонімно зашифровано.')) return;

        let $btn = $(this);
        $btn.prop('disabled', true).text('Шифрування...');

        $.ajax({
            url: fstuSettings.ajaxurl,
            type: 'POST',
            data: {
                action: 'fstu_submit_ballot',
                nonce: fstuSettings.nonce,
                election_id: electionId,
                ballot: ballotIds
            },
            success: function(res) {
                if (res.success) {
                    let hash = res.data.hash;

                    // Зберігаємо локально для підсвітки у майбутньому
                    localStorage.setItem('fstu_election_' + electionId, JSON.stringify({ hash: hash, date: new Date().toLocaleString() }));

                    // Очищаємо чернетку, бо бюлетень відправлено
                    localStorage.removeItem('fstu_voting_draft_' + electionId);

                    // Генеруємо квитанцію для скачування
                    downloadReceipt(electionId, hash, ballotNames);

                    $('#fstu-voting-booth').fadeOut(200);
                    showToast('Ваш голос успішно враховано! Збережіть завантажену Цифрову квитанцію для аудиту.', 'success');
                } else {
                    showToast(res.data.message, 'error');
                    $btn.prop('disabled', false).text('🗳 Відправити бюлетень');
                }
            },
            error: function() {
                showToast('Сталася помилка з\'єднання з сервером.', 'error');
                $btn.prop('disabled', false).text('🗳 Відправити бюлетень');
            }
        });
    });

    function downloadReceipt(electionId, hash, names) {
        let content = "КВИТАНЦІЯ ПРО ГОЛОСУВАННЯ (ФСТУ STV ВИБОРИ)\n";
        content += "===============================================\n";
        content += "Вибори ID: " + electionId + "\n";
        content += "Дата голосування: " + new Date().toLocaleString() + "\n";
        content += "Ваш анонімний Хеш-код: \n" + hash + "\n\n";
        content += "Ваш вибір (за рівнем пріоритету):\n";
        names.forEach((name, i) => {
            content += (i + 1) + ". " + name + "\n";
        });
        content += "\n===============================================\n";
        content += "Будь ласка, збережіть цей файл. Після завершення виборів ви зможете завантажити відкритий реєстр CVR та знайти свій хеш-код для перевірки цілісності результатів.\n";

        let blob = new Blob([content], { type: "text/plain;charset=utf-8" });
        let link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = "FSTU_Ballot_Receipt_" + hash.substring(0, 8) + ".txt";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function checkLocalReceipt(electionId) {
        let saved = localStorage.getItem('fstu_election_' + electionId);
        if (saved) {
            let data = JSON.parse(saved);
            $('#fstu-voting-done-alert').append(`<hr><div style="margin-top: 10px;"><strong>Ваша квитанція на цьому пристрої:</strong><br><code style="word-break: break-all; font-size: 11px;">${data.hash}</code><br><small>Збережено: ${data.date}</small></div>`);
        }
    }

    // Відкриття списку кандидатів
    $(document).on('click', '.btn-candidates', function() {
        let id = $(this).data('id');
        $('#fstu-candidates-list').html('<li style="padding: 20px; text-align: center;">Завантаження...</li>');
        $('#fstu-modal-candidates').fadeIn(200);

        $.post(fstuSettings.ajaxurl, { action: 'fstu_get_election_candidates', nonce: fstuSettings.nonce, election_id: id }, function(res) {
            if (!res.success) return showToast(res.data.message, 'error');

            let html = '';
            if (res.data.candidates.length === 0) {
                html = '<li style="padding: 20px; text-align: center; color: #7f8c8d; font-style: italic;">Ще немає жодного кандидата. Будьте першим!</li>';
            } else {
                res.data.candidates.forEach(c => {
                    let status = c.Status === 'confirmed' ? '<span class="fstu-badge fstu-badge--success">Підтверджено</span>' : '<span class="fstu-badge fstu-badge--warning">Очікує згоди</span>';
                    let nominator = c.Nominator_FIO ? `<br><small class="fstu-text-muted" style="font-size:11px;">Висунув: ${escapeHtml(c.Nominator_FIO)}</small>` : '<br><small class="fstu-text-muted" style="font-size:11px;">Самовисування</small>';

                    // Обробка мотивації для title (tooltip)
                    let motivation = c.Motivation_Text ? escapeHtml(c.Motivation_Text) : 'Мотиваційний текст відсутній.';

                    // Обробка посилання
                    let nameHtml = escapeHtml(c.FIO);
                    if (c.Motivation_URL) {
                        nameHtml = `<a href="${escapeHtml(c.Motivation_URL)}" target="_blank" title="Відкрити програму кандидата">${nameHtml} 🔗</a>`;
                    }

                    html += `<li title="${motivation}" style="padding: 12px 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; cursor: help;">
                        <div style="line-height: 1.3;"><strong>${nameHtml}</strong> <span style="color:#7f8c8d; font-size:12px;">(${escapeHtml(c.Region_Name)})</span>${nominator}</div>
                        <div>${status}</div>
                    </li>`;
                });
            }
            $('#fstu-candidates-list').html(html);
        });
    });
    /* ======================================================================
           TOAST-ПОВІДОМЛЕННЯ (Заміна стандартного alert)
    ====================================================================== */
    function showToast(message, type = 'success') {
        let $container = $('.fstu-toast-container');
        if ($container.length === 0) {
            $('body').append('<div class="fstu-toast-container"></div>');
            $container = $('.fstu-toast-container');
        }

        let typeClass = 'fstu-toast--' + type;
        let $toast = $(`
            <div class="fstu-toast ${typeClass}">
                <div class="fstu-toast-content">${escapeHtml(message)}</div>
                <button class="fstu-toast-close" aria-label="Закрити">&times;</button>
            </div>
        `);

        $container.append($toast);

        // Анімація появи
        setTimeout(() => $toast.addClass('fstu-toast--show'), 10);

        // Автозакриття через 10 секунд (10000ms)
        let timeout = setTimeout(() => {
            $toast.removeClass('fstu-toast--show');
            setTimeout(() => $toast.remove(), 300);
        }, 10000);

        // Закриття по кліку
        $toast.find('.fstu-toast-close').on('click', function() {
            clearTimeout(timeout);
            $toast.removeClass('fstu-toast--show');
            setTimeout(() => $toast.remove(), 300);
        });
    }
    /* ======================================================================
       ПІДРАХУНОК ТА АНАЛІТИКА
       ====================================================================== */

    $(document).on('click', '.btn-calculate', function() {
        if (!confirm('Це закриє голосування та розпочне безповоротний математичний підрахунок. Продовжити?')) return;

        let electionId = $(this).data('id');
        $('.fstu-dropdown-menu-dynamic').remove();

        $.post(fstuSettings.ajaxurl, { action: 'fstu_calculate_election', nonce: fstuSettings.nonce, election_id: electionId }, function(res) {
            if (res.success) {
                showToast(res.data.message, 'success');
                loadElections();
            } else {
                showToast(res.data.message, 'error');
            }
        });
    });

    $(document).on('click', '.btn-report', function() {
        let electionId = $(this).data('id');
        $('.fstu-dropdown-menu-dynamic').remove();

        $.post(fstuSettings.ajaxurl, { action: 'fstu_get_election_report', nonce: fstuSettings.nonce, election_id: electionId }, function(res) {
            if (!res.success) return showToast(res.data.message, 'error');

            let data = res.data;
            $('#fstu-report-turnout').text(data.turnout);
            $('#fstu-report-quota').text(data.quota);
            $('#fstu-report-exhausted').text(data.exhausted);

            // Переможці
            let winnersHtml = '';
            data.elected.forEach(w => {
                winnersHtml += `<li><strong>${escapeHtml(w.name)}</strong> <span class="fstu-text-muted">(${escapeHtml(w.region)})</span></li>`;
            });
            $('#fstu-report-winners').html(winnersHtml);

            // Журнал
            let roundsHtml = '';
            data.rounds.forEach(r => {
                roundsHtml += `<p><strong>Раунд ${r.round}:</strong><br>`;
                r.actions.forEach(a => { roundsHtml += `- ${escapeHtml(a)}<br>`; });
                roundsHtml += `</p>`;
            });
            $('#fstu-report-rounds').html(roundsHtml);

            // Кнопка CVR
            $('#fstu-btn-download-cvr').attr('href', fstuSettings.ajaxurl.replace('admin-ajax.php', 'admin-post.php') + '?action=fstu_download_cvr&election_id=' + electionId);

            $('#fstu-modal-report').fadeIn(200);
        });
    });

    /* ======================================================================
       РОЗДІЛ ПРОТОКОЛУ (Logs)
       ====================================================================== */

    // Відкрити протокол
    $(document).on('click', '#fstu-election-protocol-btn', function() {
        $('#fstu-main-section').hide();
        $('#fstu-protocol-section').fadeIn(200);
        state.protoPage = 1;
        loadProtocol();
    });

    // Повернутися до довідника
    $(document).on('click', '#fstu-btn-back-to-module', function() {
        $('#fstu-protocol-section').hide();
        $('#fstu-main-section').fadeIn(200);
        loadElections(); // Оновлюємо дані на випадок змін
    });

    // Пошук у протоколі
    $(document).on('input', '#fstu-protocol-search', function() {
        state.protoSearch = $(this).val();
        state.protoPage = 1;
        loadProtocol();
    });

    // Зміна кількості на сторінці (Протокол)
    $(document).on('change', '#fstu-protocol-per-page', function() {
        state.protoPerPage = parseInt($(this).val(), 10);
        state.protoPage = 1;
        loadProtocol();
    });

    // Кліки пагінації (Протокол)
    $(document).on('click', '.fstu-btn--proto-page', function() {
        if ($(this).prop('disabled') || $(this).hasClass('active')) return;
        state.protoPage = parseInt($(this).data('page'), 10);
        loadProtocol();
    });

    function loadProtocol() {
        let $tbody = $('#fstu-protocol-tbody');
        $tbody.html('<tr><td colspan="6" style="text-align:center; padding: 20px;">Завантаження...</td></tr>');

        $.ajax({
            url: fstuSettings.ajaxurl,
            type: 'POST',
            data: {
                action: 'fstu_get_elections_protocol',
                nonce: fstuSettings.nonce,
                page: state.protoPage,
                per_page: state.protoPerPage,
                search: state.protoSearch
            },
            success: function(res) {
                if (res.success) {
                    renderProtocol(res.data);
                } else {
                    $tbody.html('<tr><td colspan="6" class="fstu-text-danger" style="text-align:center;">' + escapeHtml(res.data.message) + '</td></tr>');
                }
            }
        });
    }

    function renderProtocol(data) {
        let $tbody = $('#fstu-protocol-tbody');
        $tbody.empty();

        if (!data.items || data.items.length === 0) {
            $tbody.html('<tr><td colspan="6" style="text-align:center;">Журнал порожній</td></tr>');
        } else {
            data.items.forEach(item => {
                let badgeCls = 'fstu-badge--default';
                let typeLabel = item.Logs_Type || '—';

                if (typeLabel === 'I') { badgeCls = 'fstu-badge--success'; typeLabel = 'СТВОРЕННЯ'; }
                if (typeLabel === 'U') { badgeCls = 'fstu-badge--warning'; typeLabel = 'ОНОВЛЕННЯ'; }
                if (typeLabel === 'D') { badgeCls = 'fstu-badge--danger';  typeLabel = 'ВИДАЛЕННЯ'; }

                let row = `<tr>
                    <td class="fstu-td-date">${escapeHtml(item.Logs_DateCreate)}</td>
                    <td style="text-align:center;"><span class="fstu-badge ${badgeCls}">${typeLabel}</span></td>
                    <td><strong>${escapeHtml(item.Logs_Name)}</strong></td>
                    <td>${escapeHtml(item.Logs_Text)}</td>
                    <td style="text-align:center;">${escapeHtml(item.Logs_Error)}</td>
                    <td>${escapeHtml(item.FIO || 'Система')}</td>
                </tr>`;
                $tbody.append(row);
            });
        }

        renderPagination(data.total, data.total_pages, '#fstu-protocol-pagination', '#fstu-protocol-info', data.page, 'fstu-btn--page fstu-btn--proto-page');
    }
});