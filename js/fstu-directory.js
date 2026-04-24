/**
 * Клієнтська логіка модуля Directory (Довідник Виконкому).
 *
 * Version:     1.2.0
 * Date_update: 2026-04-24
 */

jQuery(document).ready(function($) {
    'use strict';

    var $module = $('#fstu-directory-module');
    if (!$module.length) return;

    var config = window.fstuDirectoryObj || {};
    var $loader = $module.find('.fstu-loader');
    var currentTab = 'members';

    function init() {
        bindEvents();
        loadMembers();
    }

    function bindEvents() {
        $('.fstu-tab-btn').on('click', function() {
            $('.fstu-tab-btn').removeClass('active');
            $(this).addClass('active');

            currentTab = $(this).data('tab');
            $('.fstu-tab-content').hide();
            $('#tab-' + currentTab).fadeIn(200);

            if (currentTab === 'members') loadMembers();
            else if (currentTab === 'polls') loadPolls();
            else if (currentTab === 'protocol') loadProtocol();
        });

        // Копіювання email по кліку
        $(document).on('click', '.fstu-copy-email', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const email = $(this).data('email');
            navigator.clipboard.writeText(email).then(() => {
                showToast('Email скопійовано: ' + email, 'success');
            }).catch(err => {
                showToast('Помилка копіювання (ваш браузер блокує буфер)', 'error');
            });
        });
    }

    // --- Вкладка 1: СКЛАД ---
    function loadMembers() {
        $('#fstu-directory-view-container').hide();
        $loader.show();

        $.post(config.ajaxUrl, { action: 'fstu_directory_get_members', nonce: config.nonce }, function(response) {
            $loader.hide();
            if (response.success) renderMembersGrid(response.data.items || []);
            else showToast(response.data.message || config.i18n.error, 'error');
        }).fail(() => { $loader.hide(); showToast(config.i18n.error, 'error'); });
    }

    function renderMembersGrid(items) {
        const $viewContainer = $('#fstu-directory-view-container');
        if (!items.length) {
            $viewContainer.html('<div class="fstu-alert fstu-alert--info">Немає даних для відображення.</div>').show();
            return;
        }

        var fallbackAvatar = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iI2NiZDVlMSI+PHBhdGggZD0iTTEyIDEyYzIuNzYgMCA1LTIuMjQgNS01cy0yLjI0LTUtNS01LTUgMi4yNC01IDUgMi4yNCA1IDUgNXptMCAyYy0zLjMzIDAtMTAgMS42Ny0xMCA1djJoMjB2LTJjMC0zLjMzLTYuNjctNS0xMC01eiIvPjwvc3ZnPg==';
        var html = '<div class="fstu-members-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">';

        $.each(items, function(index, item) {
            var photoUrl = config.ajaxUrl + '?action=fstu_get_photo&id=' + item.User_ID + '&nonce=' + config.nonce;
            var cabinetUrl = '/personal/?ViewID=' + item.User_ID;

            html += `<a href="${cabinetUrl}" target="_blank" class="fstu-member-card" style="display: flex; flex-direction: column; align-items: center; padding: 20px; background: #fff; border: 1px solid #dcead6; border-radius: 6px; text-decoration: none; color: #333; transition: transform 0.2s;">
                        <div style="width: 100px; height: 100px; border-radius: 50%; overflow: hidden; margin-bottom: 15px; border: 3px solid #eee;">
                            <img src="${photoUrl}" alt="Photo" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.onerror=null; this.src='${fallbackAvatar}'">
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600; text-transform: uppercase;">${escapeHtml(item.MemberGuidance_Name)}</div>
                            <div style="font-size: 16px; font-weight: bold; margin-bottom: 10px;">${escapeHtml(item.FIO)}</div>`;

            if (config.permissions.canViewContactsInList) {
                if (item.email) {
                    html += `<div class="fstu-copy-email" data-email="${escapeHtml(item.email)}" title="Натисніть, щоб скопіювати" style="font-size: 13px; color: #007cba; margin-bottom: 6px; cursor: pointer; display: inline-block; padding: 2px 6px; background: #f0f5ec; border-radius: 4px; border: 1px solid #dcead6; transition: all 0.2s;"><span class="dashicons dashicons-email" style="font-size:14px; width:14px; height:14px; margin-right:4px;"></span>${escapeHtml(item.email)}</div><br>`;
                }
                if (item.PhoneMobile) {
                    html += `<div style="font-size: 13px; color: #666;"><span class="dashicons dashicons-phone" style="font-size:14px; width:14px; height:14px; margin-right:4px;"></span>${escapeHtml(item.PhoneMobile)}</div>`;
                }
            }
            html += `</div></a>`;
        });
        html += '</div>';
        $viewContainer.html(html).show();
    }

    // --- Вкладка 2: ОПИТУВАННЯ ---
    let pollsPage = 1;

    function loadPolls(page = 1) {
        pollsPage = page;
        const container = $('#fstu-directory-polls-container');
        container.html('<div style="text-align:center; padding: 20px;">Завантаження опитувань...</div>');

        $.post(config.ajaxUrl, {
            action: 'fstu_directory_get_polls',
            nonce: config.nonce,
            page: pollsPage,
            per_page: $('#fstu-directory-polls-per-page').val() || 10
        }, function(response) {
            if (response.success) {
                renderPolls(response.data);
            } else {
                container.html(`<div style="color:red; text-align:center; font-weight:bold; padding: 20px;">${response.data.message}</div>`);
            }
        });
    }

    // Обробники пагінації опитувань
    $(document).on('change', '#fstu-directory-polls-per-page', function() {
        loadPolls(1);
    });

    $(document).on('click', '.fstu-polls-page', function() {
        loadPolls($(this).data('page'));
    });

    function renderPolls(data) {
        const items = data.items || [];
        const container = $('#fstu-directory-polls-container');
        container.empty();

        if (items.length === 0) {
            container.html('<div style="text-align:center; padding: 20px;">Немає доступних опитувань.</div>');
            $('#fstu-directory-polls-info').text('');
            $('#fstu-directory-polls-pagination').empty();
            return;
        }

        let html = '<div style="overflow-x: auto; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;"><table style="width: 100%; border-collapse: collapse; font-size: 13px;"><thead><tr style="background: #dcead6;">';
        html += '<th style="padding: 8px; text-align: center; border: 1px solid #e2e4e7;">№</th>';
        html += '<th style="padding: 8px; text-align: left; border: 1px solid #e2e4e7;">Дедлайн</th>';
        html += '<th style="padding: 8px; text-align: left; border: 1px solid #e2e4e7;">Питання / Документ</th>';
        html += '<th style="padding: 8px; text-align: center; border: 1px solid #e2e4e7;">Голосів</th>';
        html += '<th style="padding: 8px; text-align: center; border: 1px solid #e2e4e7;">Статус</th>';
        html += '<th style="padding: 8px; text-align: center; border: 1px solid #e2e4e7;">Дії</th>';
        html += '</tr></thead><tbody>';

        items.forEach((item, index) => {
            const statusBadge = item.Question_State == '0' ? '<span style="background: #46b450; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px;">Відкрите</span>' : '<span style="background: #ffb900; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px;">Закрите</span>';
            const isExpired = new Date(item.Question_DateEnd) < new Date(new Date().toISOString().split('T')[0]);
            const expiredBadge = isExpired ? '<br><span style="color:red; font-size:10px;">Завершено</span>' : '';

            html += `<tr style="border-bottom: 1px solid #e2e4e7;">`;
            html += `<td style="padding: 8px; text-align: center;">${index + 1}</td>`;
            html += `<td style="padding: 8px;">${item.Question_DateEnd} ${expiredBadge}</td>`;
            html += `<td style="padding: 8px;"><strong>${escapeHtml(item.Question_Name)}</strong></td>`;
            html += `<td style="padding: 8px; text-align: center;">${item.votes_count || 0} / ${item.Total_Members || 0}</td>`;
            html += `<td style="padding: 8px; text-align: center;">${statusBadge}</td>`;
            html += `<td style="padding: 8px; text-align: center;"><button type="button" class="fstu-btn fstu-view-poll" data-id="${item.Question_ID}" data-state="${item.Question_State}" data-expired="${isExpired}" style="height: 26px; padding: 0 10px; font-size: 11px;">Відкрити</button></td>`;
            html += `</tr>`;
            html += `<tr class="fstu-poll-details-row" id="poll-row-${item.Question_ID}" style="display:none;"><td colspan="6" style="padding: 0; background: #fafafa;"><div id="poll-container-${item.Question_ID}" style="padding: 20px; border-bottom: 2px solid #dcead6;"></div></td></tr>`;
        });

        html += '</tbody></table></div>';
        container.html(html);

        $('.fstu-view-poll').on('click', function() {
            const qId = $(this).data('id');
            const row = $(`#poll-row-${qId}`);
            if (row.is(':visible')) { row.hide(); $(this).text('Відкрити'); }
            else { row.show(); $(this).text('Закрити'); loadPollDetails(qId, $(this).data('state'), $(this).data('expired')); }
        });

        // Оновлення інформації про пагінацію
        $('#fstu-directory-polls-info').text(`Записів: ${data.total} | Ст ${data.page} з ${data.total_pages}`);

        // Рендер кнопок сторінок
        let paginationHtml = '';
        if (data.total_pages > 1) {
            if (data.page > 1) paginationHtml += `<button class="fstu-btn--page fstu-polls-page" data-page="${data.page - 1}">«</button>`;
            paginationHtml += `<button class="fstu-btn--page active">${data.page}</button>`;
            if (data.page < data.total_pages) paginationHtml += `<button class="fstu-btn--page fstu-polls-page" data-page="${data.page + 1}">»</button>`;
        }
        $('#fstu-directory-polls-pagination').html(paginationHtml);
    }

    function loadPollDetails(qId, state, isExpired) {
        const container = $(`#poll-container-${qId}`);
        container.html('<div style="text-align:center;">Завантаження...</div>');
        $.post(config.ajaxUrl, { action: 'fstu_directory_get_poll_details', nonce: config.nonce, question_id: qId, state: state }, function(response) {
            if (response.success) renderPollCard(qId, isExpired, response.data);
            else container.html(`<div style="color:red; text-align:center;">${response.data.message}</div>`);
        });
    }

    function renderPollCard(qId, isExpired, data) {
        const container = $(`#poll-container-${qId}`);
        let html = `<div style="max-width: 800px; margin: 0 auto; background: #fff; padding: 20px; border: 1px solid #e2e4e7; border-radius: 4px;">`;

        html += `<h4 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">Ваш голос:</h4>`;

        // Перевіряємо, чи є юзер в масиві тих, хто має право голосу
        // Для спрощення ми завжди показуємо кнопки, але бекенд блокуватиме, якщо немає прав
        if (isExpired) {
            html += `<div style="color:red; font-weight:bold; margin-bottom: 20px;">Голосування завершено.</div>`;
        } else {
            html += `<div style="display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap;">`;
            (data.answers || []).forEach(ans => {
                const isActive = data.current_vote == ans.Answer_ID ? 'background: #007cba; color: #fff; border-color: #007cba;' : 'background: #f8f9fa; border: 1px solid #ccc; color: #333;';
                html += `<button class="fstu-cast-vote-btn" data-qid="${qId}" data-aid="${ans.Answer_ID}" style="padding: 6px 15px; cursor: pointer; border-radius: 3px; font-weight: bold; ${isActive}">${escapeHtml(ans.Answer_Name)}</button>`;
            });
            html += `</div>`;
        }

        html += `<h4 style="border-bottom: 1px solid #eee; padding-bottom: 10px;">Загальні результати:</h4><div style="margin-bottom: 25px;">`;
        let totalVotes = (data.stats || []).reduce((sum, item) => sum + parseInt(item.cnt), 0);
        if (totalVotes === 0) { html += `<div style="color:#666;">Ще ніхто не проголосував.</div>`; }
        else {
            (data.answers || []).forEach(ans => {
                const stat = (data.stats || []).find(s => s.Answer_ID === ans.Answer_ID);
                const cnt = stat ? parseInt(stat.cnt) : 0;
                const percent = (cnt / totalVotes * 100).toFixed(1);
                let color = '#46b450'; if (ans.Answer_Name.toLowerCase().includes('проти')) color = '#d9534f'; if (ans.Answer_Name.toLowerCase().includes('утрим')) color = '#ffb900';

                html += `<div style="margin-bottom: 10px;">
                    <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;"><strong>${escapeHtml(ans.Answer_Name)}</strong><span>${cnt} голосів (${percent}%)</span></div>
                    <div style="width: 100%; background: #eee; height: 8px; border-radius: 4px; overflow: hidden;"><div style="width: ${percent}%; background: ${color}; height: 100%;"></div></div>
                </div>`;
            });
        }
        html += `</div>`;

        if ((data.voters || []).length > 0) {
            html += `<h4 style="border-bottom: 1px solid #eee; padding-bottom: 10px;">Поіменний протокол:</h4>`;
            html += `<table style="width:100%; border-collapse: collapse; font-size: 12px; margin-top: 10px;"><thead><tr style="border-bottom: 1px solid #ccc; text-align: left;"><th>ПІБ</th><th style="text-align: center;">Голос</th><th style="text-align: center;">Джерело</th><th style="text-align: center;">Дата</th></tr></thead><tbody>`;
            data.voters.forEach(v => {
                let color = 'inherit'; if (v.Answer_Name.toLowerCase().includes('за')) color = 'green'; if (v.Answer_Name.toLowerCase().includes('проти')) color = 'red'; if (v.Answer_Name.toLowerCase().includes('утрим')) color = 'orange';
                html += `<tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 4px 0;"><a href="/personal/?ViewID=${v.User_ID}" target="_blank" style="color: #007cba; text-decoration: none;">${escapeHtml(v.FIO)}</a></td>
                    <td style="text-align: center; color: ${color}; font-weight: bold; padding: 4px 0;">${escapeHtml(v.Answer_Name)}</td>
                    <td style="text-align: center; color: #666; padding: 4px 0;">${v.API === 'telegram-bot' ? '📱 Telegram' : '💻 Сайт'}</td>
                    <td style="text-align: center; color: #666; padding: 4px 0;">${v.VotingResults_DateCreate}</td>
                </tr>`;
            });
            html += `</tbody></table>`;
        } else if ((data.stats || []).length > 0) {
            html += `<div style="color:#666; font-size: 12px;">Поіменний протокол прихований (закрите голосування).</div>`;
        }

        html += `</div>`;
        container.html(html);

        container.find('.fstu-cast-vote-btn').on('click', function() {
            const aId = $(this).data('aid');
            $(this).siblings().prop('disabled', true); $(this).text('...').prop('disabled', true);
            $.post(config.ajaxUrl, { action: 'fstu_directory_cast_vote', nonce: config.nonce, question_id: qId, answer_id: aId }, function(response) {
                showToast(response.data.message || 'Обробка', response.success ? 'success' : 'error');
                loadPollDetails(qId, $(`#poll-row-${qId}`).prev().find('.fstu-view-poll').data('state'), isExpired);
            }).fail(() => { showToast('Помилка сервера', 'error'); loadPollDetails(qId, $(`#poll-row-${qId}`).prev().find('.fstu-view-poll').data('state'), isExpired); });
        });
    }

    // --- Вкладка 3: ПРОТОКОЛ ---
    let protocolPage = 1;
    function loadProtocol(page = 1) {
        protocolPage = page;
        const tbody = $('#fstu-directory-protocol-tbody');
        tbody.html('<tr><td colspan="4" style="text-align:center; padding: 20px;">Завантаження...</td></tr>');

        $.post(config.ajaxUrl, {
            action: 'fstu_directory_get_protocol',
            nonce: config.nonce,
            page: protocolPage,
            per_page: $('#fstu-directory-protocol-per-page').val() || 10,
            search: $('#fstu-directory-search-protocol').val()
        }, function(response) {
            if (response.success) {
                tbody.empty();
                if (response.data.items.length === 0) {
                    tbody.html('<tr><td colspan="4" style="text-align:center; padding: 20px;">Немає записів у протоколі.</td></tr>');
                } else {
                    response.data.items.forEach(log => {
                        let badge = '#6c757d'; if (log.Logs_Type === 'I') badge = '#46b450'; if (log.Logs_Type === 'U') badge = '#ffb900'; if (log.Logs_Type === 'D') badge = '#d9534f';
                        tbody.append(`<tr>
                            <td>${log.Logs_DateCreate}</td>
                            <td style="text-align:center;"><span style="background:${badge}; color:#fff; padding:2px 6px; border-radius:3px; font-size:10px; font-weight:bold;">${log.Logs_Type}</span></td>
                            <td><strong>${escapeHtml(log.FIO)}</strong><br>${escapeHtml(log.Logs_Text)}</td>
                            <td style="text-align:center;">${escapeHtml(log.Logs_Error)}</td>
                        </tr>`);
                    });
                }

                $('#fstu-directory-protocol-info').text(`Записів: ${response.data.total} | Ст ${response.data.page} з ${response.data.total_pages}`);

                // Рендер кнопок пагінації за корпоративним стандартом
                let paginationHtml = '';
                if (response.data.total_pages > 1) {
                    if (response.data.page > 1) paginationHtml += `<button class="fstu-btn--page fstu-protocol-page" data-page="${response.data.page - 1}">«</button>`;
                    paginationHtml += `<button class="fstu-btn--page active">${response.data.page}</button>`;
                    if (response.data.page < response.data.total_pages) paginationHtml += `<button class="fstu-btn--page fstu-protocol-page" data-page="${response.data.page + 1}">»</button>`;
                }
                $('#fstu-directory-protocol-pagination').html(paginationHtml);

            } else {
                tbody.html(`<tr><td colspan="4" style="text-align:center; color:#d9534f; padding: 20px; font-weight:bold;">${response.data.message || 'Помилка завантаження'}</td></tr>`);
            }
        }).fail(function() {
            tbody.html('<tr><td colspan="4" style="text-align:center; color:#d9534f; padding: 20px; font-weight:bold;">Помилка з\'єднання з сервером. Перевірте консоль браузера (F12).</td></tr>');
        });
    }

    // Обробники пагінації та пошуку
    $(document).on('change', '#fstu-directory-protocol-per-page', function() {
        loadProtocol(1);
    });

    $(document).on('click', '.fstu-protocol-page', function() {
        loadProtocol($(this).data('page'));
    });

    let searchProtocolTimeout;
    $(document).on('input', '#fstu-directory-search-protocol', function() {
        clearTimeout(searchProtocolTimeout);
        searchProtocolTimeout = setTimeout(function() {
            loadProtocol(1);
        }, 500);
    });

    function escapeHtml(text) {
        if (!text) return '';
        var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function showToast(message, type) {
        $('.fstu-toast').remove();
        const color = type === 'success' ? '#46b450' : '#d9534f';
        const $toast = $(`<div class="fstu-toast" style="position:fixed; bottom:20px; right:20px; background:${color}; color:#fff; padding:15px 25px; border-radius:5px; z-index:999999; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">${escapeHtml(message)}</div>`);
        $('body').append($toast);
        setTimeout(() => $toast.fadeOut(400, function() { $(this).remove(); }), 3000);
    }

    init();
});