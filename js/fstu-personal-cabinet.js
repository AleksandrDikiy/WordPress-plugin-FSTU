/**
 * JS модуля «Особистий кабінет ФСТУ».
 *
 * Version:     1.8.0
 * Date_update: 2026-04-09
 */
jQuery(document).ready(function ($) {
    'use strict';

    var l10n = window.fstuPersonalCabinetL10n || null;
    var $root = $('#fstu-personal-cabinet-app');

    if (!l10n || !$root.length) {
        return;
    }

    var state = {
        profileUserId: parseInt(l10n.profileUserId || 0, 10) || 0,
        activeTab: 'general',
        flashAlert: null,
        tables: {},
        protocol: {
            page: 1,
            perPage: parseInt((l10n.defaults && l10n.defaults.protocolPerPage) || 10, 10),
            search: ''
        }
    };

    function escHtml(value) {
        return $('<div/>').text(value || '').html();
    }

    function getAjaxErrorMessage(xhr, fallback) {
        if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
            return xhr.responseJSON.data.message;
        }

        return fallback;
    }

    function buildInfoList(items) {
        if (!items || !items.length) {
            return '<div class="fstu-personal-tab-card__placeholder">Наразі для цієї вкладки немає записів або доступних даних.</div>';
        }

        var html = '<div class="fstu-personal-info-list">';
        $.each(items, function (_, item) {
            var valueHtml = escHtml(item.value || '—');

            if (item.type === 'link' && item.value && item.value !== '—') {
                valueHtml = '<a href="' + escHtml(item.value) + '" target="_blank" rel="noopener noreferrer">' + escHtml(item.value) + '</a>';
            }

            if (item.type === 'image' && item.value) {
                valueHtml = '<div class="fstu-personal-image-wrap"><img src="' + escHtml(item.value) + '" alt="Фото користувача" class="fstu-personal-image"></div>';
            }

            html += '<div class="fstu-personal-info-list__row">';
            html += '<div class="fstu-personal-info-list__label">' + escHtml(item.label || '') + '</div>';
            html += '<div class="fstu-personal-info-list__value">' + valueHtml + '</div>';
            html += '</div>';
        });
        html += '</div>';

        return html;
    }

    function buildSections(sections) {
        if (!sections || !sections.length) {
            return '';
        }

        var html = '<div class="fstu-personal-sections">';

        $.each(sections, function (_, section) {
            html += '<section class="fstu-personal-section-block">';
            if (section.title) {
                html += '<h4 class="fstu-personal-section-block__title">' + escHtml(section.title) + '</h4>';
            }
            html += buildInfoList(section.items || []);
            html += '</section>';
        });

        html += '</div>';

        return html;
    }

    function getTableState(tabSlug, tableConfig) {
        if (!state.tables[tabSlug]) {
            state.tables[tabSlug] = {
                page: 1,
                perPage: parseInt((tableConfig && tableConfig.defaultPerPage) || 10, 10) || 10
            };
        }

        return state.tables[tabSlug];
    }

    function buildTabTable(tabSlug, tableConfig) {
        var rows = tableConfig && tableConfig.rows ? tableConfig.rows : [];
        var emptyMessage = tableConfig && tableConfig.emptyMessage ? tableConfig.emptyMessage : 'Записи відсутні.';
        var tableType = tableConfig && tableConfig.type ? String(tableConfig.type) : '';
        var tableState = getTableState(tabSlug, tableConfig || {});
        var perPage = parseInt(tableState.perPage, 10) || 10;
        var currentPage = parseInt(tableState.page, 10) || 1;
        var total = rows.length;
        var totalPages = Math.max(1, Math.ceil(total / perPage));
        var startIndex = (currentPage - 1) * perPage;
        var pageRows;
        var html = '<div class="fstu-personal-table-shell">';

        if (currentPage > totalPages) {
            currentPage = totalPages;
            tableState.page = currentPage;
            startIndex = (currentPage - 1) * perPage;
        }

        pageRows = rows.slice(startIndex, startIndex + perPage);

        html += '<div class="fstu-table-wrap">';
        html += '<table class="fstu-table">';
        html += buildTabTableHead(tableType);
        html += '<tbody class="fstu-tbody">';

        if (!pageRows.length) {
            html += '<tr class="fstu-row"><td colspan="' + getTabTableColspan(tableType) + '" class="fstu-td fstu-td--empty">' + escHtml(emptyMessage) + '</td></tr>';
        } else {
            $.each(pageRows, function (_, row) {
                html += buildTabTableRow(tableType, row);
            });
        }

        html += '</tbody></table></div>';
        html += buildTabTablePagination(tabSlug, currentPage, perPage, total, totalPages);
        html += '</div>';

        return html;
    }

    function buildTabTableHead(tableType) {
        var html = '<thead class="fstu-thead"><tr>';

        if (tableType === 'dues_sail') {
            html += '<th class="fstu-th">Рік</th>';
            html += '<th class="fstu-th">Сума</th>';
            html += '<th class="fstu-th">Дата додавання</th>';
            html += '<th class="fstu-th">Фінансист</th>';
        } else {
            html += '<th class="fstu-th">Рік</th>';
            html += '<th class="fstu-th">Сума</th>';
            html += '<th class="fstu-th">Тип</th>';
            html += '<th class="fstu-th">Дата додавання</th>';
            html += '<th class="fstu-th">Фінансист</th>';
            html += '<th class="fstu-th">Статус</th>';
            html += '<th class="fstu-th">Квитанція</th>';
        }

        html += '</tr></thead>';

        return html;
    }

    function buildTabTableRow(tableType, row) {
        var html = '<tr class="fstu-row">';

        if (tableType === 'dues_sail') {
            html += '<td class="fstu-td">' + escHtml(row.year || '—') + '</td>';
            html += '<td class="fstu-td">' + escHtml(row.sum || '—') + '</td>';
            html += '<td class="fstu-td">' + escHtml(row.date || '—') + '</td>';
            html += '<td class="fstu-td">' + escHtml(row.financier || '—') + '</td>';
        } else {
            html += '<td class="fstu-td">' + escHtml(row.year || '—') + '</td>';
            html += '<td class="fstu-td">' + escHtml(row.sum || '—') + '</td>';
            html += '<td class="fstu-td">' + escHtml(row.type || '—') + '</td>';
            html += '<td class="fstu-td">' + escHtml(row.date || '—') + '</td>';
            html += '<td class="fstu-td">' + escHtml(row.financier || '—') + '</td>';
            html += '<td class="fstu-td">' + escHtml(row.status || '—') + '</td>';
            html += '<td class="fstu-td">';
            if (row.receipt_url) {
                html += '<a href="' + escHtml(row.receipt_url) + '" target="_blank" rel="noopener noreferrer">Відкрити</a>';
            } else {
                html += '—';
            }
            html += '</td>';
        }

        html += '</tr>';

        return html;
    }

    function getTabTableColspan(tableType) {
        return tableType === 'dues_sail' ? 4 : 7;
    }

    function buildTabTablePagination(tabSlug, currentPage, perPage, total, totalPages) {
        var html = '<div class="fstu-pagination fstu-pagination--compact">';
        html += '<div class="fstu-pagination__left">';
        html += '<label class="fstu-pagination__per-page-label" for="fstu-personal-table-per-page-' + escHtml(tabSlug) + '">Показувати по:</label>';
        html += '<select id="fstu-personal-table-per-page-' + escHtml(tabSlug) + '" class="fstu-select fstu-select--compact fstu-personal-table-per-page" data-tab-slug="' + escHtml(tabSlug) + '">';
        html += '<option value="10"' + (perPage === 10 ? ' selected' : '') + '>10</option>';
        html += '<option value="15"' + (perPage === 15 ? ' selected' : '') + '>15</option>';
        html += '<option value="25"' + (perPage === 25 ? ' selected' : '') + '>25</option>';
        html += '<option value="50"' + (perPage === 50 ? ' selected' : '') + '>50</option>';
        html += '</select></div>';
        html += '<div class="fstu-pagination__controls">' + buildTabTablePaginationButtons(tabSlug, currentPage, totalPages) + '</div>';
        html += '<div class="fstu-pagination__info">Записів: ' + total + ' | Сторінка ' + currentPage + ' з ' + totalPages + '</div>';
        html += '</div>';

        return html;
    }

    function buildTabTablePaginationButtons(tabSlug, currentPage, totalPages) {
        var html = '';
        var start = Math.max(1, currentPage - 2);
        var end = Math.min(totalPages, currentPage + 2);

        function addButton(page, label, active) {
            html += '<button type="button" class="fstu-page-btn fstu-personal-table-page-btn' + (active ? ' fstu-page-btn--active' : '') + '" data-tab-slug="' + escHtml(tabSlug) + '" data-page="' + page + '">' + escHtml(label) + '</button>';
        }

        if (currentPage > 1) {
            addButton(currentPage - 1, '«', false);
        }
        if (start > 1) {
            addButton(1, '1', currentPage === 1);
            if (start > 2) {
                html += '<span class="fstu-page-dots">…</span>';
            }
        }
        for (var page = start; page <= end; page++) {
            addButton(page, String(page), page === currentPage);
        }
        if (end < totalPages) {
            if (end < totalPages - 1) {
                html += '<span class="fstu-page-dots">…</span>';
            }
            addButton(totalPages, String(totalPages), currentPage === totalPages);
        }
        if (currentPage < totalPages) {
            addButton(currentPage + 1, '»', false);
        }

        return html;
    }

    function buildTabActions(actions) {
        if (!actions || !actions.length) {
            return '';
        }

        var html = '<div class="fstu-personal-tab-actions">';

        $.each(actions, function (_, action) {
            var enabled = !!action.enabled;
            var actionUrl = String(action.url || '');
            var actionTarget = String(action.target || '');
            var actionKey = String(action.actionKey || '');
            var itemClass = 'fstu-personal-tab-actions__item' + (enabled ? ' fstu-personal-tab-actions__item--enabled' : ' fstu-personal-tab-actions__item--disabled');

            if (enabled && actionKey) {
                html += '<button type="button" class="' + itemClass + ' fstu-personal-tab-actions__item-button" data-action-key="' + escHtml(actionKey) + '">';
                html += '<span class="fstu-personal-tab-actions__label">' + escHtml(action.label || '') + '</span>';
                html += '</button>';
                return;
            }

            if (enabled && actionUrl) {
                html += '<a class="' + itemClass + ' fstu-personal-tab-actions__item-link" href="' + escHtml(actionUrl) + '"' + (actionTarget ? ' target="' + escHtml(actionTarget) + '" rel="noopener noreferrer"' : '') + '>';
                html += '<span class="fstu-personal-tab-actions__label">' + escHtml(action.label || '') + '</span>';
                html += '</a>';
                return;
            }

            html += '<span class="' + itemClass + '">';
            html += '<span class="fstu-personal-tab-actions__label">' + escHtml(action.label || '') + '</span>';
            if (!enabled && action.pending) {
                html += '<small class="fstu-personal-tab-actions__hint">' + escHtml(action.pending) + '</small>';
            }
            html += '</span>';
        });

        html += '</div>';

        return html;
    }

    function setMainAlert(type, message) {
        var $alert = $('#fstu-personal-alert');
        if (!$alert.length) {
            return;
        }

        $alert.removeClass('fstu-alert--info fstu-alert--error fstu-alert--warning fstu-alert--success')
            .addClass('fstu-alert--' + type)
            .text(message || '');
    }

    function setDuesModalMessage(message) {
        var $message = $('#fstu-personal-dues-modal-message');
        if (!$message.length) {
            return;
        }

        if (!message) {
            $message.addClass('fstu-hidden').text('');
            return;
        }

        $message.removeClass('fstu-hidden').text(message);
    }

    function openDuesModal() {
        $('#fstu-personal-dues-year').val(parseInt((l10n.defaults && l10n.defaults.currentYear) || new Date().getFullYear(), 10));
        $('#fstu-personal-dues-summa').val('');
        $('#fstu-personal-dues-url').val('');
        setDuesModalMessage('');
        $('#fstu-personal-dues-modal').removeClass('fstu-hidden');
    }

    function closeDuesModal() {
        $('#fstu-personal-dues-modal').addClass('fstu-hidden');
        setDuesModalMessage('');
    }

    function submitGatewayForm(payload) {
        var gatewayUrl = payload && payload.gatewayUrl ? String(payload.gatewayUrl) : '';
        var fields = payload && payload.fields ? payload.fields : {};
        var method = payload && payload.method ? String(payload.method).toUpperCase() : 'POST';

        if (!gatewayUrl) {
            setMainAlert('error', l10n.messages.paymentError || 'Не вдалося підготувати онлайн-оплату внеску.');
            return;
        }

        var $form = $('<form/>', {
            method: method,
            action: gatewayUrl,
            class: 'fstu-hidden',
            enctype: 'application/x-www-form-urlencoded'
        });

        $.each(fields, function (name, value) {
            $('<input/>', {
                type: 'hidden',
                name: name,
                value: String(value == null ? '' : value)
            }).appendTo($form);
        });

        $('body').append($form);
        setMainAlert('info', l10n.messages.paymentRedirecting || 'Перенаправляємо на сторінку Portmone...');
        $form.trigger('submit');
    }

    function requestPortmonePayload() {
        setMainAlert('info', l10n.messages.paymentPreparing || 'Готуємо безпечний платіжний payload Portmone...');

        $.post(l10n.ajaxUrl, {
            action: l10n.actions.getPortmonePayload,
            nonce: l10n.nonce,
            profile_user_id: state.profileUserId,
            fstu_website: ''
        }).done(function (response) {
            if (!response || !response.success || !response.data) {
                setMainAlert('error', response && response.data && response.data.message ? response.data.message : (l10n.messages.paymentError || 'Не вдалося підготувати онлайн-оплату внеску.'));
                return;
            }

            submitGatewayForm(response.data);
        }).fail(function (xhr) {
            setMainAlert('error', getAjaxErrorMessage(xhr, l10n.messages.paymentError || 'Не вдалося підготувати онлайн-оплату внеску.'));
        });
    }

    function uploadDuesReceipt() {
        var $form = $('#fstu-personal-dues-form');
        var $submit = $('#fstu-personal-dues-submit');

        setDuesModalMessage('');
        $submit.prop('disabled', true);

        $.post(l10n.ajaxUrl, {
            action: l10n.actions.uploadDuesReceipt,
            nonce: l10n.nonce,
            profile_user_id: state.profileUserId,
            year_id: $('#fstu-personal-dues-year').val(),
            summa: $('#fstu-personal-dues-summa').val(),
            url: $('#fstu-personal-dues-url').val(),
            fstu_website: $form.find('input[name="fstu_website"]').val() || ''
        }).done(function (response) {
            if (!response || !response.success || !response.data) {
                setDuesModalMessage(response && response.data && response.data.message ? response.data.message : (l10n.messages.duesSaveError || 'Не вдалося зберегти квитанцію.'));
                return;
            }

            closeDuesModal();
            state.flashAlert = {
                type: 'success',
                message: response.data.message || l10n.messages.duesSaveSuccess || 'Квитанцію успішно збережено.'
            };
            loadProfile();
        }).fail(function (xhr) {
            setDuesModalMessage(getAjaxErrorMessage(xhr, l10n.messages.duesSaveError || 'Не вдалося зберегти квитанцію.'));
        }).always(function () {
            $submit.prop('disabled', false);
        });
    }

    function buildAccessNotice(notice, isReadOnly) {
        if (!notice) {
            return '';
        }

        return '<div class="fstu-alert ' + (isReadOnly ? 'fstu-alert--warning' : 'fstu-alert--info') + ' fstu-personal-tab-card__access-notice">' + escHtml(notice) + '</div>';
    }

    function getFirstVisibleTab(visibleTabs) {
        var first = 'general';

        $.each($('.fstu-personal-tabs__btn'), function () {
            var slug = String($(this).data('tab') || '');
            if (slug && visibleTabs[slug]) {
                first = slug;
                return false;
            }
            return true;
        });

        return first;
    }

    function renderTabs(tabs) {
        var visibleTabs = {};

        $.each(tabs || {}, function (slug, tab) {
            visibleTabs[slug] = !!tab.visible;

            var $pane = $('[data-tab-pane="' + slug + '"]');
            var $content = $('#fstu-personal-tab-' + slug);
            var $button = $('[data-tab="' + slug + '"]');

            if (!$pane.length || !$content.length || !$button.length) {
                return;
            }

            if (!tab.visible) {
                $pane.addClass('fstu-hidden');
                $button.addClass('fstu-hidden');
                return;
            }

            $pane.removeClass('fstu-hidden');
            $button.removeClass('fstu-hidden');

            var html = '';

            html += buildAccessNotice(tab.accessNotice || '', !!tab.isReadOnly);
            html += buildTabActions(tab.actions || []);

            if (tab.sections && tab.sections.length) {
                html += buildSections(tab.sections);
            }

            if (tab.table && tab.table.rows) {
                html += buildTabTable(slug, tab.table);
            } else if (!tab.sections || !tab.sections.length) {
                html += buildInfoList(tab.items || []);
            }

            if (tab.note) {
                html += '<div class="fstu-personal-tab-card__note">' + escHtml(tab.note) + '</div>';
            }
            $content.html(html);
        });

        if (!visibleTabs[state.activeTab]) {
            state.activeTab = getFirstVisibleTab(visibleTabs);
        }

        switchTab(state.activeTab);
    }

    function renderProfile(payload) {
        var profile = payload.profile || {};
        $('#fstu-personal-name').text(profile.displayName || 'Особистий кабінет');
        $('#fstu-personal-email').text(profile.email || '');
        $('#fstu-personal-roles').text(profile.roles || '');
        $('#fstu-personal-scope').text(profile.isOwnProfile ? 'Власний профіль' : 'Публічний перегляд профілю');

        if ($('#fstu-personal-alert').length) {
            if (state.flashAlert && state.flashAlert.message) {
                setMainAlert(state.flashAlert.type || 'info', state.flashAlert.message);
                state.flashAlert = null;
            } else {
                setMainAlert(
                    'info',
                    profile.isOwnProfile
                        ? 'Завантажено ваш особистий кабінет. Публічні, приватні та службові вкладки показуються згідно з роллю та правилами доступу.'
                        : 'Завантажено публічний профіль користувача. Приватні та службові вкладки приховані відповідно до матриці доступу.'
                );
            }
        }

        renderTabs(payload.tabs || {});
    }

    function loadProfile() {
        $.post(l10n.ajaxUrl, {
            action: l10n.actions.getProfile,
            nonce: l10n.nonce,
            profile_user_id: state.profileUserId
        }).done(function (response) {
            if (!response || !response.success || !response.data) {
                var message = response && response.data && response.data.message ? response.data.message : (l10n.messages.profileError || 'Помилка завантаження.');
                $('#fstu-personal-alert').removeClass('fstu-alert--info').addClass('fstu-alert--error').text(message);
                return;
            }

            renderProfile(response.data);
        }).fail(function (xhr) {
            $('#fstu-personal-alert')
                .removeClass('fstu-alert--info')
                .addClass('fstu-alert--error')
                .text(getAjaxErrorMessage(xhr, l10n.messages.profileError || 'Помилка завантаження.'));
        });
    }

    function buildTypeBadge(type) {
        var cls = 'fstu-badge--default';
        var label = type || '—';
        if (type === 'INSERT' || type === 'I') { cls = 'fstu-badge--insert'; label = 'INSERT'; }
        if (type === 'UPDATE' || type === 'U') { cls = 'fstu-badge--update'; label = 'UPDATE'; }
        if (type === 'DELETE' || type === 'D') { cls = 'fstu-badge--delete'; label = 'DELETE'; }
        if (type === 'VIEW' || type === 'V') { cls = 'fstu-badge--default'; label = 'VIEW'; }
        return '<span class="fstu-badge ' + cls + '">' + escHtml(label) + '</span>';
    }

    function renderProtocol(items, meta) {
        var $body = $('#fstu-personal-protocol-body');
        var html = '';

        if (!items || !items.length) {
            html = '<tr><td colspan="' + ((l10n.table && l10n.table.protocolColspan) || 6) + '" class="fstu-td fstu-td--empty">' + escHtml(l10n.messages.emptyProtocol || 'Записи протоколу відсутні.') + '</td></tr>';
            $body.html(html);
            $('#fstu-personal-protocol-controls').html('');
            $('#fstu-personal-protocol-info').text('Записів: 0 | Сторінка 1 з 1');
            return;
        }

        $.each(items, function (_, item) {
            html += '<tr class="fstu-row">';
            html += '<td class="fstu-td">' + escHtml(item.Logs_DateCreate || '') + '</td>';
            html += '<td class="fstu-td fstu-td--center">' + buildTypeBadge(item.Logs_Type || '') + '</td>';
            html += '<td class="fstu-td">' + escHtml(item.Logs_Name || '') + '</td>';
            html += '<td class="fstu-td">' + escHtml(item.Logs_Text || '') + '</td>';
            html += '<td class="fstu-td fstu-td--center">' + escHtml(item.Logs_Error || '') + '</td>';
            html += '<td class="fstu-td">' + escHtml(item.FIO || 'Система') + '</td>';
            html += '</tr>';
        });

        $body.html(html);
        renderProtocolPagination(meta);
    }

    function renderProtocolPagination(meta) {
        var current = meta.page || 1;
        var totalPages = meta.total_pages || 1;
        var total = meta.total || 0;
        var html = '';
        var start = Math.max(1, current - 2);
        var end = Math.min(totalPages, current + 2);

        function addButton(page, label, active) {
            html += '<button type="button" class="fstu-page-btn' + (active ? ' fstu-page-btn--active' : '') + '" data-page="' + page + '">' + escHtml(label) + '</button>';
        }

        if (current > 1) {
            addButton(current - 1, '«', false);
        }

        if (start > 1) {
            addButton(1, '1', current === 1);
            if (start > 2) {
                html += '<span class="fstu-page-dots">…</span>';
            }
        }

        for (var i = start; i <= end; i++) {
            addButton(i, String(i), i === current);
        }

        if (end < totalPages) {
            if (end < totalPages - 1) {
                html += '<span class="fstu-page-dots">…</span>';
            }
            addButton(totalPages, String(totalPages), current === totalPages);
        }

        if (current < totalPages) {
            addButton(current + 1, '»', false);
        }

        $('#fstu-personal-protocol-controls').html(html);
        $('#fstu-personal-protocol-info').text('Записів: ' + total + ' | Сторінка ' + current + ' з ' + totalPages);
    }

    function loadProtocol() {
        $('#fstu-personal-protocol-body').html('<tr><td colspan="' + ((l10n.table && l10n.table.protocolColspan) || 6) + '" class="fstu-td fstu-td--empty">' + escHtml(l10n.messages.loading || 'Завантаження...') + '</td></tr>');

        $.post(l10n.ajaxUrl, {
            action: l10n.actions.getProtocol,
            nonce: l10n.nonce,
            page: state.protocol.page,
            per_page: state.protocol.perPage,
            search: state.protocol.search
        }).done(function (response) {
            if (!response || !response.success || !response.data) {
                $('#fstu-personal-protocol-body').html('<tr><td colspan="' + ((l10n.table && l10n.table.protocolColspan) || 6) + '" class="fstu-td fstu-td--empty">' + escHtml(l10n.messages.protocolError || 'Помилка завантаження.') + '</td></tr>');
                return;
            }

            renderProtocol(response.data.items || [], response.data);
        }).fail(function (xhr) {
            $('#fstu-personal-protocol-body').html('<tr><td colspan="' + ((l10n.table && l10n.table.protocolColspan) || 6) + '" class="fstu-td fstu-td--empty">' + escHtml(getAjaxErrorMessage(xhr, l10n.messages.protocolError || 'Помилка завантаження.')) + '</td></tr>');
        });
    }

    function switchTab(tab) {
        state.activeTab = tab;
        $('.fstu-personal-tabs__btn').removeClass('fstu-personal-tabs__btn--active');
        $('.fstu-personal-tabs__pane').removeClass('fstu-personal-tabs__pane--active');
        $('[data-tab="' + tab + '"]').addClass('fstu-personal-tabs__btn--active');
        $('[data-tab-pane="' + tab + '"]').addClass('fstu-personal-tabs__pane--active');
    }

    var protocolSearchTimer = null;

    $(document).on('click', '.fstu-personal-tabs__btn', function () {
        var tab = $(this).data('tab');
        if (tab) {
            switchTab(String(tab));
        }
    });

    $(document).on('click', '#fstu-personal-show-protocol', function () {
        $('#fstu-personal-main-section').addClass('fstu-hidden');
        $('#fstu-personal-protocol-section').removeClass('fstu-hidden');
        $('#fstu-personal-show-protocol').addClass('fstu-hidden');
        $('#fstu-personal-show-main').removeClass('fstu-hidden');
        loadProtocol();
    });

    $(document).on('click', '#fstu-personal-show-main', function () {
        $('#fstu-personal-protocol-section').addClass('fstu-hidden');
        $('#fstu-personal-main-section').removeClass('fstu-hidden');
        $('#fstu-personal-show-main').addClass('fstu-hidden');
        $('#fstu-personal-show-protocol').removeClass('fstu-hidden');
    });

    $(document).on('change', '#fstu-personal-protocol-per-page', function () {
        state.protocol.perPage = parseInt($(this).val(), 10) || 10;
        state.protocol.page = 1;
        loadProtocol();
    });

    $(document).on('click', '.fstu-page-btn', function () {
        var page = parseInt($(this).data('page'), 10) || 1;
        state.protocol.page = page;
        loadProtocol();
    });

    $(document).on('input', '#fstu-personal-protocol-search', function () {
        state.protocol.search = $(this).val() || '';
        state.protocol.page = 1;
        clearTimeout(protocolSearchTimer);
        protocolSearchTimer = window.setTimeout(loadProtocol, 300);
    });

    $(document).on('click', '.fstu-personal-tab-actions__item-button[data-action-key="portmone"]', function () {
        requestPortmonePayload();
    });

    $(document).on('click', '.fstu-personal-tab-actions__item-button[data-action-key="upload_dues_receipt"]', function () {
        openDuesModal();
    });

    $(document).on('click', '.fstu-personal-table-page-btn', function () {
        var tabSlug = String($(this).data('tab-slug') || '');
        var page = parseInt($(this).data('page'), 10) || 1;

        if (!tabSlug) {
            return;
        }

        getTableState(tabSlug, { defaultPerPage: 10 }).page = page;
        loadProfile();
    });

    $(document).on('change', '.fstu-personal-table-per-page', function () {
        var tabSlug = String($(this).data('tab-slug') || '');
        var perPage = parseInt($(this).val(), 10) || 10;

        if (!tabSlug) {
            return;
        }

        getTableState(tabSlug, { defaultPerPage: 10 }).perPage = perPage;
        getTableState(tabSlug, { defaultPerPage: 10 }).page = 1;
        loadProfile();
    });

    $(document).on('click', '#fstu-personal-dues-modal-close, #fstu-personal-dues-cancel', function () {
        closeDuesModal();
    });

    $(document).on('click', '#fstu-personal-dues-modal', function (event) {
        if ($(event.target).is('#fstu-personal-dues-modal')) {
            closeDuesModal();
        }
    });

    $(document).on('submit', '#fstu-personal-dues-form', function (event) {
        event.preventDefault();
        uploadDuesReceipt();
    });

    loadProfile();
});

