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
        html += buildTabTableHead(tableConfig);
        html += '<tbody class="fstu-tbody">';

        if (!pageRows.length) {
            html += '<tr class="fstu-row"><td colspan="' + getTabTableColspan(tableConfig) + '" class="fstu-td fstu-td--empty">' + escHtml(emptyMessage) + '</td></tr>';
        } else {
            $.each(pageRows, function (_, row) {
                html += buildTabTableRow(tableConfig, row);
            });
        }

        html += '</tbody></table></div>';
        html += buildTabTablePagination(tabSlug, currentPage, perPage, total, totalPages);
        html += '</div>';

        return html;
    }

    function buildTabTableHead(tableConfig) {
        var html = '<thead class="fstu-thead"><tr>';
        if (tableConfig && tableConfig.columns) {
            $.each(tableConfig.columns, function (_, col) {
                html += '<th class="fstu-th">' + escHtml(col.label || '') + '</th>';
            });
        }
        html += '</tr></thead>';
        return html;
    }

    function buildTabTableRow(tableConfig, row) {
        var html = '<tr class="fstu-row">';
        if (tableConfig && tableConfig.columns) {
            $.each(tableConfig.columns, function (_, col) {
                var val = row[col.key];
                var cellHtml = escHtml(val || '—');
                
                if (col.type === 'link' && val) {
                    cellHtml = '<a href="' + escHtml(val) + '" target="_blank" rel="noopener noreferrer">Відкрити</a>';
                }
                
                html += '<td class="fstu-td">' + cellHtml + '</td>';
            });
        }
        html += '</tr>';
        return html;
    }

    function getTabTableColspan(tableConfig) {
        return tableConfig && tableConfig.columns ? tableConfig.columns.length : 1;
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
        // Додано видалення fstu-hidden
        $alert.removeClass('fstu-hidden fstu-alert--info fstu-alert--error fstu-alert--warning fstu-alert--success')
            .addClass('fstu-alert--' + type)
            .text(message || '');

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

    function renderTabs(tabs, profile) {
        $.each(tabs || {}, function (slug, tab) {
            var $content = $('#fstu-personal-tab-' + slug);
            if (!$content.length || !tab.visible) return;

            var html = '';
            html += buildAccessNotice(tab.accessNotice || '', !!tab.isReadOnly);

            // Спеціальний рендеринг для вкладки ЗАГАЛЬНІ (з таблицею)
            if (slug === 'general') {
                html += '<div class="fstu-personal-grid-general">';
                
                // Ліва колонка: Фото та Перемикач згоди
                html += '<div class="fstu-personal-grid-general__photo-side">';
                var photoItem = tab.sections[0].items.find(i => i.type === 'image');
                html += '<div class="fstu-personal-image-wrap"><img src="' + escHtml(photoItem.value) + '" class="fstu-personal-image"></div>';
                
                if (!tab.isReadOnly) {
                    html += '<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-open-photo-modal" style="width: 100%;">Змінити фото</button>';
                }
                
                // Перемикач згоди
                var isConsent = profile.hasConsent;
                var consentText = isConsent ? '1=показати персональні данні для членів ФСТУ' : '0=заборонити показ персональних даних';
                html += '<div class="fstu-consent-wrapper">';
                html += '<label class="fstu-switch">';
                html += '<input type="checkbox" id="fstu-consent-toggle" ' + (isConsent ? 'checked' : '') + (tab.isReadOnly ? ' disabled' : '') + '>';
                html += '<span class="fstu-slider"></span>';
                html += '</label>';
                html += '<div class="fstu-consent-text" id="fstu-consent-text">' + escHtml(consentText) + '</div>';
                html += '</div>';

                html += '</div>';

                // Права колонка: Таблиця як у Реєстрі
                html += '<div class="fstu-personal-grid-general__content-side">';
                html += '<table class="fstu-personal-general-table"><tbody>';
                
                $.each(tab.sections, function(_, section) {
                    $.each(section.items, function(_, item) {
                        if (item.type === 'image') return;
                        
                        var valHtml = escHtml(item.value || '—');
                        if (item.type === 'link' && item.value && item.value !== '—') {
                            valHtml = '<a href="' + escHtml(item.value) + '" target="_blank" rel="noopener noreferrer" style="color: #b5473a; font-weight: 700; text-decoration: underline;">Профіль ' + escHtml(item.label) + '</a>';
                        }
                        
                        html += '<tr>';
                        html += '<td class="fstu-general-table-label">' + escHtml(item.label) + ':</td>';
                        html += '<td class="fstu-general-table-value">' + valHtml + '</td>';
                        html += '</tr>';
                    });
                });
                
                html += '</tbody></table>';
                html += '</div></div>';
            } 
            // Спеціальний рендеринг для вкладки ПРИВАТНЕ (Адмін-форма)
            else if (slug === 'private' && !tab.isReadOnly) {
                html += '<form class="fstu-personal-form fstu-personal-form--compact" id="fstu-admin-private-form">';
                $.each(tab.sections[0].items, function(_, item) {
                    html += '<div class="fstu-form-group">';
                    html += '<label class="fstu-label">' + escHtml(item.label) + '</label>';
                    html += '<input type="text" name="' + escHtml(item.key) + '" value="' + escHtml(item.value) + '" class="fstu-input">';
                    html += '</div>';
                });
                html += '<button type="submit" class="fstu-btn fstu-btn--primary" style="margin-top:10px;">Зберегти зміни</button>';
                html += '</form>';
            }
            // Спеціальний рендеринг для вкладки ПРИВАТНЕ (Перегляд як у Реєстрі ФСТУ)
            else if (slug === 'private' && tab.isReadOnly) {
                html += '<table class="fstu-personal-general-table"><tbody>';
                $.each(tab.sections, function(_, section) {
                    $.each(section.items, function(_, item) {
                        html += '<tr>';
                        html += '<td class="fstu-general-table-label">' + escHtml(item.label) + ':</td>';
                        html += '<td class="fstu-general-table-value">' + escHtml(item.value || '—') + '</td>';
                        html += '</tr>';
                    });
                });
                html += '</tbody></table>';
            }
            else if (tab.table) {
                // Рендеринг таблиці для вкладки з конфігурацією table  
                html += buildTabTable(slug, tab.table);
            }
            else {
                // Стандартний рендеринг для інших вкладок
                html += buildSections(tab.sections);
            }

            $content.html(html);
        });
        
        switchTab(state.activeTab);
    }

    // Обробка відкриття модалки фото
    $(document).on('click', '#fstu-open-photo-modal', function() {
        $('#fstu-personal-photo-modal').removeClass('fstu-hidden');
    });

    $(document).on('click', '#fstu-personal-photo-modal-close, #fstu-personal-photo-cancel', function() {
        $('#fstu-personal-photo-modal').addClass('fstu-hidden');
    });
    // Рендер профілю після завантаження даних
    function renderProfile(payload) {
        var profile = payload.profile || {};

        if ($('#fstu-personal-alert').length) {
            if (state.flashAlert && state.flashAlert.message) {
                setMainAlert(state.flashAlert.type || 'info', state.flashAlert.message);
                state.flashAlert = null;
            } else {
                // Просто ховаємо блок, якщо немає повідомлень (наприклад, після перезавантаження)
                $('#fstu-personal-alert').addClass('fstu-hidden').text('');
            }
        }

        renderTabs(payload.tabs || {}, profile);
    }
    // Збереження форми Приватні (Адміністратор)
    $(document).on('submit', '#fstu-admin-private-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var formData = $form.serializeArray();
        
        var payload = {
            action: 'fstu_personal_cabinet_update_private_data',
            nonce: l10n.nonce,
            profile_user_id: state.profileUserId
        };
        
        $.each(formData, function(_, field) {
            payload[field.name] = field.value;
        });

        $btn.prop('disabled', true).text('Збереження...');

        $.post(l10n.ajaxUrl, payload).done(function (response) {
            if (response.success) {
                state.flashAlert = { type: 'success', message: response.data.message || 'Збережено' };
                loadProfile();
            } else {
                setMainAlert('error', response.data.message || 'Помилка');
            }
        }).fail(function (xhr) {
            setMainAlert('error', getAjaxErrorMessage(xhr, 'Помилка збереження.'));
        }).always(function () {
            $btn.prop('disabled', false).text('Зберегти зміни');
        });
    });

    // Завантаження фото
    $(document).on('submit', '#fstu-personal-photo-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $('#fstu-personal-photo-submit');
        var $alert = $('#fstu-personal-photo-alert');
        var fileInput = document.getElementById('fstu-personal-photo-input');

        if (!fileInput.files.length) {
            $alert.removeClass('fstu-hidden').text('Будь ласка, виберіть файл.');
            return;
        }

        var formData = new FormData();
        formData.append('action', 'fstu_personal_cabinet_upload_photo');
        formData.append('nonce', l10n.nonce);
        formData.append('profile_user_id', state.profileUserId);
        formData.append('fstu_website', ''); // honeypot
        formData.append('profile_photo', fileInput.files[0]);

        $btn.prop('disabled', true).text('Завантаження...');
        $alert.addClass('fstu-hidden').text('');

        $.ajax({
            url: l10n.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    $('#fstu-personal-photo-modal').addClass('fstu-hidden');
                    state.flashAlert = { type: 'success', message: response.data.message || 'Фото оновлено' };
                    loadProfile();
                } else {
                    $alert.removeClass('fstu-hidden').text(response.data.message || 'Помилка');
                }
            },
            error: function (xhr) {
                $alert.removeClass('fstu-hidden').text(getAjaxErrorMessage(xhr, 'Помилка завантаження.'));
            },
            complete: function () {
                $btn.prop('disabled', false).text('Завантажити');
                $form[0].reset();
            }
        });
    });

    function loadProfile() {
        // ОНОВЛЕННЯ ДЛЯ ФАЗИ 5: Вставляємо Skeleton Loaders
        $('.fstu-personal-tab-card__content').each(function() {
            var tabId = $(this).attr('id');
            var skeletonHtml = '';
            
            if (tabId === 'fstu-personal-tab-general') {
                skeletonHtml = '<div class="fstu-personal-grid-general">' +
                               '<div class="fstu-personal-grid-general__photo-side"><div class="fstu-skeleton fstu-skeleton-image"></div></div>' +
                               '<div class="fstu-personal-grid-general__content-side">' +
                               '<div><div class="fstu-skeleton fstu-skeleton-text"></div><div class="fstu-skeleton fstu-skeleton-text fstu-skeleton-text--short"></div></div>' +
                               '<div><div class="fstu-skeleton fstu-skeleton-text"></div><div class="fstu-skeleton fstu-skeleton-text fstu-skeleton-text--short"></div></div>' +
                               '</div></div>';
            } else {
                skeletonHtml = '<div class="fstu-skeleton fstu-skeleton-title"></div>' +
                               '<div class="fstu-skeleton fstu-skeleton-table-row"></div>' +
                               '<div class="fstu-skeleton fstu-skeleton-table-row"></div>' +
                               '<div class="fstu-skeleton fstu-skeleton-table-row"></div>';
            }
            
            $(this).html(skeletonHtml);
        });

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
        // ОНОВЛЕННЯ ДЛЯ ФАЗИ 5: Skeleton для таблиці протоколу
        var skeletonRow = '<tr><td colspan="' + ((l10n.table && l10n.table.protocolColspan) || 6) + '">' +
                          '<div class="fstu-skeleton fstu-skeleton-table-row" style="margin-bottom:0;"></div>' +
                          '</td></tr>';
        $('#fstu-personal-protocol-body').html(skeletonRow + skeletonRow + skeletonRow);

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

    // Обробка перемикача згоди на показ даних
    $(document).on('change', '#fstu-consent-toggle', function() {
        var isChecked = $(this).is(':checked');
        var text = isChecked ? 'показати персональні данні для членів ФСТУ' : 'заборонити показ персональних даних';
        $('#fstu-consent-text').text(text);
        
        $.post(l10n.ajaxUrl, {
            action: 'fstu_personal_cabinet_update_consent',
            nonce: l10n.nonce,
            profile_user_id: state.profileUserId,
            consent: isChecked ? 1 : 0
        });
    });

    loadProfile();
});

