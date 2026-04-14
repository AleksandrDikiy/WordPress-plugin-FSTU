/**
 * JS модуля «Особистий кабінет ФСТУ».
 *
 * Version:     1.8.1
 * Date_update: 2026-04-14
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
            
            // Підтримка сирого HTML (для 3-колонкової таблиці реквізитів)
            if (section.type === 'raw_html') {
                html += section.html || '';
            } else {
                html += buildInfoList(section.items || []);
            }
            
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
                
                if (col.type === 'link' && val && val !== '—') {
                    cellHtml = '<a href="' + escHtml(val) + '" target="_blank" rel="noopener noreferrer">Відкрити</a>';
                } else if (col.type === 'html') {
                    // Новий тип: виводимо HTML як є (для посилань на календар)
                    cellHtml = val || '—';
                } else if (col.type === 'actions') {
                    if (val === 'delete') {
                        cellHtml = '<button type="button" class="fstu-btn fstu-delete-club-btn" data-id="' + escHtml(row.id) + '">Видалити</button>';
                    } else if (val === 'delete_city') {
                        cellHtml = '<button type="button" class="fstu-btn fstu-delete-city-btn" data-id="' + escHtml(row.id) + '" style="padding: 4px 8px; font-size: 11px; background-color: #fef2f2!important; color: #b91c1c!important; border: 1px solid #fecaca!important;">Видалити</button>';
                    } else if (val === 'delete_unit') {
                        cellHtml = '<button type="button" class="fstu-btn fstu-delete-unit-btn" data-id="' + escHtml(row.id) + '" style="padding: 4px 8px; font-size: 11px; background-color: #fef2f2!important; color: #b91c1c!important; border: 1px solid #fecaca!important;">Видалити</button>';
                    } else if (val === 'delete_tourism') {
                        cellHtml = '<button type="button" class="fstu-btn fstu-delete-tourism-btn" data-id="' + escHtml(row.id) + '" style="padding: 4px 8px; font-size: 11px; background-color: #fef2f2!important; color: #b91c1c!important; border: 1px solid #fecaca!important;">Видалити</button>';
                    } else if (val === 'edit_divodka') {
                        var existingUrl = row['divodka'] !== '—' ? row['divodka'] : '';
                        cellHtml = '<button type="button" class="fstu-btn fstu-edit-divodka-btn" data-id="' + escHtml(row.id) + '" data-url="' + escHtml(existingUrl) + '" style="padding: 4px 8px; font-size: 11px; background-color: #eff6ff!important; color: #1d4ed8!important; border: 1px solid #bfdbfe!important;">Довідка</button>';
                    } else if (val === 'delete_rank') {
                        cellHtml = '<button type="button" class="fstu-btn fstu-delete-rank-btn" data-id="' + escHtml(row.id) + '" style="padding: 4px 8px; font-size: 11px; background-color: #fef2f2!important; color: #b91c1c!important; border: 1px solid #fecaca!important;">Видалити</button>';
                    } else if (val === 'delete_judging') {
                        cellHtml = '<button type="button" class="fstu-btn fstu-delete-judging-btn" data-id="' + escHtml(row.id) + '" style="padding: 4px 8px; font-size: 11px; background-color: #fef2f2!important; color: #b91c1c!important; border: 1px solid #fecaca!important;">Видалити</button>';
                    } else if (val === 'pay_portmone') {
                        // ЗЕЛЕНА КНОПКА СПЛАТИТИ (з передачею року)
                        cellHtml = '<button type="button" class="fstu-btn fstu-pay-portmone-btn" data-year="' + escHtml(row.year) + '" style="padding: 4px 8px; font-size: 11px; background-color: #ecfdf5!important; color: #047857!important; border: 1px solid #a7f3d0!important;">Сплатити</button>';
                    } else if (val === 'pay_monobank') {
                        // ЗЕЛЕНА КНОПКА ДЛЯ ВІТРИЛЬНИКІВ (Посилання-кнопка в нову вкладку)
                        var payUrl = row.url_pay ? escHtml(row.url_pay) : '#';
                        cellHtml = '<a href="' + payUrl + '" target="_blank" class="fstu-btn" style="padding: 4px 8px; font-size: 11px; background-color: #ecfdf5!important; color: #047857!important; border: 1px solid #a7f3d0!important; text-decoration: none; display: inline-block;">Сплатити</a>';
                    } else {
                        cellHtml = ''; // ПРИБИРАЄМО РИСКУ "—", ЯКЩО ДІЙ НЕМАЄ
                    }
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

    // Оновлена функція приймає рік!
    function requestPortmonePayload(year) {
        setMainAlert('info', l10n.messages.paymentPreparing || 'Готуємо безпечний платіжний payload Portmone...');

        $.post(l10n.ajaxUrl, {
            action: 'fstu_personal_cabinet_get_portmone_payload',
            nonce: l10n.nonce,
            profile_user_id: state.profileUserId,
            year: year, // ПЕРЕДАЄМО РІК
            fstu_website: ''
        }).done(function (response) {
            if (!response || !response.success || !response.data) {
                setMainAlert('error', response && response.data && response.data.message ? response.data.message : 'Не вдалося підготувати онлайн-оплату внеску.');
                return;
            }
            submitGatewayForm(response.data);
        }).fail(function (xhr) {
            setMainAlert('error', getAjaxErrorMessage(xhr, 'Не вдалося підготувати онлайн-оплату внеску.'));
        });
    }

    // Перехоплюємо клік по зеленій кнопці в таблиці
    $(document).on('click', '.fstu-pay-portmone-btn', function () {
        var year = $(this).data('year');
        requestPortmonePayload(year);
    });

    // Швидкий перехід до реєстру платежів
    $(document).on('click', '.fstu-personal-tab-actions__item-button[data-action-key="open_payment_docs"]', function() {
        window.open('/personal/rejestr-platizhok/', '_blank');
    });

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

            // ВИПРАВЛЕННЯ 1: Ховаємо заголовок (надійний селектор)
            var $title = $content.closest('.fstu-personal-tab-card').find('.fstu-personal-tab-card__title');
            if (tab.title === '') {
                $title.addClass('fstu-hidden');
            } else if (tab.title) {
                $title.text(tab.title).removeClass('fstu-hidden');
            }

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
                var consentText = isConsent ? 'показати персональні данні для членів ФСТУ' : 'заборонити показ персональних даних';
                html += '<div class="fstu-consent-wrapper">';
                html += '<label class="fstu-switch">';
                html += '<input type="checkbox" id="fstu-consent-toggle" ' + (isConsent ? 'checked' : '') + (tab.isReadOnly ? ' disabled' : '') + '>';
                html += '<span class="fstu-slider"></span>';
                html += '</label>';
                html += '<div class="fstu-consent-text" id="fstu-consent-text">' + escHtml(consentText) + '</div>';
                html += '</div>'; // закриття fstu-consent-wrapper
                
                // ДОДАНО КНОПКУ РЕДАГУВАТИ
                if (profile.canEditProfile) {
                    html += '<button type="button" class="fstu-btn fstu-btn--primary" id="fstu-open-edit-modal" style="width: 100%; margin-top: 12px;">РЕДАГУВАТИ</button>';
                }

                html += '</div>'; // закриття лівої колонки

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
            // Спеціальний рендеринг для вкладки ПРИВАТНЕ
            else if (slug === 'private') {
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
                
                if (tab.note) {
                     html += '<div class="fstu-personal-tab-card__note">' + escHtml(tab.note) + '</div>';
                }
            }
            // Спеціальний рендеринг для вкладки СЛУЖБОВЕ
            else if (slug === 'service') {
                var buildServiceTable = function(section) {
                    if (!section) return '';
                    var sHtml = '<div class="fstu-service-block">';
                    if (section.title) {
                        sHtml += '<h4 class="fstu-service-block-title">' + escHtml(section.title) + '</h4>';
                    }
                    sHtml += '<table class="fstu-personal-general-table"><tbody>';
                    $.each(section.items || [], function(_, item) {
                        sHtml += '<tr>';
                        sHtml += '<td class="fstu-general-table-label">' + escHtml(item.label) + ':</td>';
                        sHtml += '<td class="fstu-general-table-value">' + escHtml(item.value || '—') + '</td>';
                        sHtml += '</tr>';
                    });
                    sHtml += '</tbody></table></div>';
                    return sHtml;
                };

                html += '<div class="fstu-personal-grid-service">';
                html += '<div class="fstu-personal-grid-service__col">';
                html += buildServiceTable(tab.sections[0]);
                html += buildServiceTable(tab.sections[1]);
                html += '</div>';
                html += '<div class="fstu-personal-grid-service__col">';
                html += buildServiceTable(tab.sections[2]);
                html += buildServiceTable(tab.sections[3]);
                html += '</div>';
                html += '</div>';
            }
            // ВИПРАВЛЕННЯ: Виводимо секції (Реквізити) ПІД таблицею!
            else if (tab.table) {
                html += buildTabActions(tab.actions); 
                html += buildTabTable(slug, tab.table); // Таблиця спочатку
                if (tab.sections && tab.sections.length > 0) {
                    html += buildSections(tab.sections); // Секції після таблиці
                }
            }
            else {
                html += buildTabActions(tab.actions); // <--- Кнопки для інших вкладок (Осередки, Місто)
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
        
        // ДОДАНО: Передача схеми редагування в глобальний state
        state.editSchema = profile.editSchema || {};

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
    // Логіка модального вікна редагування (всі вкладки в DOM одночасно)
    var activeEditTab = 'general';

    function buildEditField(field) {
        var colStyle = '';
        if (field.col === 'left') colStyle = 'grid-column: 1;';
        else if (field.col === 'right') colStyle = 'grid-column: 2;';
        else if (field.col === 'full') colStyle = 'grid-column: 1 / -1;';

        var html = '<div class="fstu-form-group fstu-form-group--inline" style="' + colStyle + '">';
        html += '<label class="fstu-label">' + escHtml(field.label) + '</label>';

        var readonlyAttr = field.readonly ? 'readonly style="background: #f3f4f6; color: #6b7280; cursor: not-allowed;"' : '';

        if (field.type === 'toggle_sex') {
            var isMale = field.value === 'Ч';
            html += '<div class="fstu-switch-wrapper" style="justify-content: flex-start; margin: 0; padding: 0; border: none; background: transparent;">';
            html += '<label class="fstu-switch">';
            html += '<input type="checkbox" name="' + escHtml(field.key) + '" value="Ч" ' + (isMale ? 'checked' : '') + ' ' + (field.readonly ? 'disabled' : '') + '>';
            html += '<span class="fstu-slider"></span>';
            html += '</label></div>';
        } else if (field.type === 'toggle_bool') {
            var isActive = field.value === '1' || field.value === 'Так' || field.value === 'true';
            html += '<div class="fstu-switch-wrapper" style="justify-content: flex-start; margin: 0; padding: 0; border: none; background: transparent;">';
            html += '<label class="fstu-switch">';
            html += '<input type="checkbox" name="' + escHtml(field.key) + '" value="1" ' + (isActive ? 'checked' : '') + ' ' + (field.readonly ? 'disabled' : '') + '>';
            html += '<span class="fstu-slider"></span>';
            html += '</label></div>';
        } else if (field.type === 'roles') {
            html += '<div class="fstu-input fstu-text-wrap" style="background: #f3f4f6; color: #6b7280; height: auto; min-height: 26px; padding: 6px; cursor: not-allowed;">' + escHtml(field.value || '—') + '</div>';
        } else {
            var inputType = field.type || 'text';
            html += '<input type="' + inputType + '" name="' + escHtml(field.key) + '" value="' + escHtml(field.value) + '" class="fstu-input" ' + readonlyAttr + '>';
        }

        html += '</div>';
        return html;
    }

    function initEditForm() {
        var schema = state.editSchema || {};
        var html = '';
        
        // Рендеримо всі 3 вкладки одразу
        $.each(['general', 'private', 'service'], function(_, tabSlug) {
            var fields = schema[tabSlug] || [];
            if (!fields.length) return;

            var displayClass = (tabSlug === activeEditTab) ? '' : 'fstu-hidden';
            html += '<div class="fstu-edit-tab-pane ' + displayClass + '" data-pane="' + tabSlug + '">';

            if (tabSlug === 'service') {
                html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px 24px;">';
                $.each(fields, function(_, field) { html += buildEditField(field); });
                html += '</div>';
            } else {
                html += '<div style="display: flex; flex-direction: column; gap: 8px;">';
                $.each(fields, function(_, field) { html += buildEditField(field); });
                html += '</div>';
            }
            html += '</div>';
        });

        $('#fstu-personal-edit-form').html(html);
        updateEditTabUI();
    }

    function updateEditTabUI() {
        $('.fstu-personal-tabs__btn[data-edit-tab]').removeClass('fstu-personal-tabs__btn--active');
        $('.fstu-personal-tabs__btn[data-edit-tab="' + activeEditTab + '"]').addClass('fstu-personal-tabs__btn--active');
        
        $('.fstu-edit-tab-pane').addClass('fstu-hidden');
        $('.fstu-edit-tab-pane[data-pane="' + activeEditTab + '"]').removeClass('fstu-hidden');
    }

    $(document).on('click', '#fstu-open-edit-modal', function() {
        $('#fstu-personal-edit-alert').addClass('fstu-hidden');
        $('#fstu-personal-edit-modal').removeClass('fstu-hidden');
        activeEditTab = 'general'; // Завжди починаємо з першої вкладки
        initEditForm();
    });

    $(document).on('click', '.fstu-personal-tabs__btn[data-edit-tab]', function() {
        activeEditTab = $(this).data('edit-tab');
        updateEditTabUI(); // Тепер ми просто перемикаємо видимість, а не перемальовуємо поля
    });

    $(document).on('click', '#fstu-personal-edit-modal-close, #fstu-personal-edit-cancel', function() {
        $('#fstu-personal-edit-modal').addClass('fstu-hidden');
    });

    $(document).on('submit', '#fstu-personal-edit-form', function(e) {
        e.preventDefault();
        var $btn = $('#fstu-personal-edit-submit');
        var $alert = $('#fstu-personal-edit-alert');
        var formData = $(this).serializeArray();
        
        var payload = {
            action: 'fstu_personal_cabinet_update_profile',
            nonce: l10n.nonce,
            profile_user_id: state.profileUserId
        };
        
        $.each(formData, function(_, field) {
            payload[field.name] = field.value;
        });

        // Ручна обробка невідмічених перемикачів для ВСІХ вкладок
        var schema = state.editSchema || {};
        $.each(['general', 'private', 'service'], function(_, tabSlug) {
            var schemaFields = schema[tabSlug] || [];
            $.each(schemaFields, function(_, field) {
                if (field.type === 'toggle_sex' && !payload[field.key]) {
                    payload[field.key] = 'Ж';
                } else if (field.type === 'toggle_bool' && !payload[field.key]) {
                    payload[field.key] = '0';
                }
            });
        });

        $btn.prop('disabled', true).text('Збереження...');
        $alert.addClass('fstu-hidden');

        $.post(l10n.ajaxUrl, payload).done(function(response) {
            if (response.success) {
                $('#fstu-personal-edit-modal').addClass('fstu-hidden');
                state.flashAlert = { type: 'success', message: 'Дані профілю успішно оновлено.' };
                loadProfile();
            } else {
                $alert.removeClass('fstu-hidden').addClass('fstu-alert--error').text(response.data.message || 'Помилка');
            }
        }).fail(function(xhr) {
            $alert.removeClass('fstu-hidden').addClass('fstu-alert--error').text(getAjaxErrorMessage(xhr, 'Помилка збереження.'));
        }).always(function() {
            $btn.prop('disabled', false).text('Зберегти зміни');
        });
    });

    // Логіка додавання/видалення клубів (Живий пошук)
    var allClubsList = []; // Кеш для довідника клубів

    $(document).on('click', '.fstu-personal-tab-actions__item-button[data-action-key="add_club"]', function () {
        $('#fstu-personal-club-alert').addClass('fstu-hidden');
        $('#fstu-personal-club-form')[0].reset();
        $('#fstu-club-id').val('');
        $('#fstu-club-dropdown').addClass('fstu-hidden');
        $('#fstu-personal-club-modal').removeClass('fstu-hidden');

        // Завантажуємо список клубів при першому відкритті модалки
        if (!allClubsList.length) {
            var $searchInput = $('#fstu-club-search');
            $searchInput.prop('disabled', true).val('Завантаження довідника...');
            
            $.post(l10n.ajaxUrl, {
                action: 'fstu_personal_cabinet_get_all_clubs',
                nonce: l10n.nonce
            }).done(function(res) {
                if (res && res.success) {
                    allClubsList = res.data || [];
                    $searchInput.prop('disabled', false).val('').attr('placeholder', 'Почніть вводити назву...');
                    $searchInput.focus(); // Одразу ставимо курсор для зручності
                } else {
                    // Якщо бекенд повернув success: false (наприклад, помилку SQL)
                    $searchInput.val('Помилка: ' + (res.data && res.data.message ? res.data.message : 'Немає даних'));
                    setTimeout(function() { $searchInput.prop('disabled', false).val(''); }, 3500);
                }
            }).fail(function(xhr) {
                // Якщо сервер взагалі "впав" (500 помилка або обрив інтернету)
                $searchInput.val('Фатальна помилка сервера. Дивіться консоль (F12)');
                setTimeout(function() { $searchInput.prop('disabled', false).val(''); }, 3500);
                console.error('AJAX Clubs Error:', xhr.responseText);
            });
        }
    });

    // Фільтрація списку при введенні тексту
    $(document).on('input', '#fstu-club-search', function() {
        var term = $(this).val().toLowerCase();
        var $dropdown = $('#fstu-club-dropdown');
        $('#fstu-club-id').val(''); // Скидаємо ID, якщо користувач змінив текст

        if (term.length < 1) {
            $dropdown.addClass('fstu-hidden');
            return;
        }

        // Шукаємо збіги
        var matches = allClubsList.filter(function(c) {
            return c.name.toLowerCase().indexOf(term) > -1;
        });

        if (matches.length) {
            var html = '';
            $.each(matches, function(_, c) {
                html += '<div class="fstu-club-option" data-id="' + c.id + '" data-name="' + escHtml(c.name) + '">' + escHtml(c.name) + '</div>';
            });
            $dropdown.html(html).removeClass('fstu-hidden');
        } else {
            $dropdown.html('<div style="padding: 8px 12px; color: #6b7280; font-size: 13px;">Клуб не знайдено</div>').removeClass('fstu-hidden');
        }
    });

    // Вибір клубу зі списку
    $(document).on('click', '.fstu-club-option', function() {
        $('#fstu-club-id').val($(this).data('id')); // Записуємо схований ID
        $('#fstu-club-search').val($(this).data('name')); // Показуємо красиву назву
        $('#fstu-club-dropdown').addClass('fstu-hidden'); // Ховаємо список
    });

    // Закриття списку при кліку будь-де поза ним
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.fstu-autocomplete-wrapper').length) {
            $('#fstu-club-dropdown').addClass('fstu-hidden');
        }
    });

    // Відправка форми
    $(document).on('submit', '#fstu-personal-club-form', function(e) {
        e.preventDefault();
        var $btn = $('#fstu-personal-club-submit');
        var $alert = $('#fstu-personal-club-alert');
        var clubId = $('#fstu-club-id').val();
        
        // Захист: перевіряємо чи користувач дійсно обрав клуб зі списку
        if (!clubId) {
            $alert.removeClass('fstu-hidden').addClass('fstu-alert--error').text('Будь ласка, оберіть клуб зі списку підказок.');
            return;
        }

        $btn.prop('disabled', true).text('Збереження...');
        $alert.addClass('fstu-hidden');

        $.post(l10n.ajaxUrl, {
            action: 'fstu_personal_cabinet_add_club',
            nonce: l10n.nonce,
            profile_user_id: state.profileUserId,
            club_id: clubId
        }).done(function(response) {
            if (response.success) {
                $('#fstu-personal-club-modal').addClass('fstu-hidden');
                state.flashAlert = { type: 'success', message: 'Клуб успішно додано.' };
                loadProfile();
            } else {
                $alert.removeClass('fstu-hidden').addClass('fstu-alert--error').text(response.data.message || 'Помилка');
            }
        }).fail(function(xhr) {
            $alert.removeClass('fstu-hidden').addClass('fstu-alert--error').text(getAjaxErrorMessage(xhr, 'Помилка збереження.'));
        }).always(function() {
            $btn.prop('disabled', false).text('Додати');
        });
    });

    $(document).on('click', '.fstu-delete-club-btn', function() {
        if (!confirm('Ви впевнені, що хочете видалити цей клуб?')) return;
        
        var clubId = $(this).data('id');
        $.post(l10n.ajaxUrl, {
            action: 'fstu_personal_cabinet_delete_club',
            nonce: l10n.nonce,
            profile_user_id: state.profileUserId,
            club_id: clubId
        }).done(function(response) {
            if (response.success) {
                state.flashAlert = { type: 'success', message: 'Клуб видалено зі списку.' };
                loadProfile();
            } else {
                // ТЕПЕР МИ ПОБАЧИМО ПОМИЛКУ, ЯКЩО ВОНА Є
                alert(response.data && response.data.message ? response.data.message : 'Помилка видалення');
            }
        }).fail(function(xhr) {
            alert(getAjaxErrorMessage(xhr, 'Помилка видалення клубу.'));
        });
    });
    // Логіка Міста (Autocomplete)
    var allCitiesList = [];
    $(document).on('click', '.fstu-personal-tab-actions__item-button[data-action-key="add_city"]', function() {
        $('#fstu-personal-city-alert').addClass('fstu-hidden');
        $('#fstu-personal-city-form')[0].reset();
        $('#fstu-city-id').val('');
        $('#fstu-personal-city-modal').removeClass('fstu-hidden');
        if (!allCitiesList.length) {
            $.post(l10n.ajaxUrl, { action: 'fstu_personal_cabinet_get_all_cities', nonce: l10n.nonce }).done(function(res) {
                if (res.success) allCitiesList = res.data || [];
            });
        }
    });

    $(document).on('input', '#fstu-city-search', function() {
        var term = $(this).val().toLowerCase(), $dropdown = $('#fstu-city-dropdown');
        if (term.length < 2) { $dropdown.addClass('fstu-hidden'); return; }
        var matches = allCitiesList.filter(c => c.name.toLowerCase().includes(term));
        if (matches.length) {
            var html = matches.map(c => '<div class="fstu-club-option" data-id="'+c.id+'" data-name="'+escHtml(c.name)+'">'+escHtml(c.name)+'</div>').join('');
            $dropdown.html(html).removeClass('fstu-hidden');
        } else { $dropdown.html('<div class="fstu-club-option">Нічого не знайдено</div>').removeClass('fstu-hidden'); }
    });

    $(document).on('click', '#fstu-city-dropdown .fstu-club-option', function() {
        $('#fstu-city-id').val($(this).data('id'));
        $('#fstu-city-search').val($(this).data('name'));
        $('#fstu-city-dropdown').addClass('fstu-hidden');
    });

    $(document).on('submit', '#fstu-personal-city-form', function(e) {
        e.preventDefault();
        var cityId = $('#fstu-city-id').val();
        if (!cityId) return alert('Оберіть місто зі списку');
        $.post(l10n.ajaxUrl, { action: 'fstu_personal_cabinet_add_city', nonce: l10n.nonce, profile_user_id: state.profileUserId, city_id: cityId }).done(function() {
            $('#fstu-personal-city-modal').addClass('fstu-hidden');
            loadProfile();
        });
    });

    $(document).on('click', '.fstu-delete-city-btn', function() {
        if (!confirm('Видалити цей запис з історії міст?')) return;
        
        var cityId = $(this).data('id');
        $.post(l10n.ajaxUrl, {
            action: 'fstu_personal_cabinet_delete_city',
            nonce: l10n.nonce,
            profile_user_id: state.profileUserId,
            city_id: cityId
        }).done(function(response) {
            if (response.success) {
                state.flashAlert = { type: 'success', message: 'Місто видалено зі списку.' };
                loadProfile();
            } else {
                alert(response.data && response.data.message ? response.data.message : 'Помилка видалення');
            }
        }).fail(function(xhr) {
            alert(getAjaxErrorMessage(xhr, 'Помилка видалення міста.'));
        });
    });
    // Логіка Осередки (Autocomplete)
    var allUnitsList = [];
    $(document).on('click', '.fstu-personal-tab-actions__item-button[data-action-key="add_unit"]', function() {
        $('#fstu-personal-unit-alert').addClass('fstu-hidden');
        $('#fstu-personal-unit-form')[0].reset();
        $('#fstu-unit-id').val('');
        $('#fstu-personal-unit-modal').removeClass('fstu-hidden');

        if (!allUnitsList.length) {
            var $searchInput = $('#fstu-unit-search');
            $searchInput.prop('disabled', true).val('Завантаження довідника...');
            $.post(l10n.ajaxUrl, { action: 'fstu_personal_cabinet_get_all_units', nonce: l10n.nonce }).done(function(res) {
                if (res && res.success) {
                    allUnitsList = res.data || [];
                    $searchInput.prop('disabled', false).val('').attr('placeholder', 'Введіть назву осередку...');
                    $searchInput.focus();
                } else {
                    $searchInput.val('Помилка: ' + (res.data && res.data.message ? res.data.message : 'Немає даних'));
                    setTimeout(function() { $searchInput.prop('disabled', false).val(''); }, 3500);
                }
            }).fail(function(xhr) {
                $searchInput.val('Фатальна помилка сервера.');
                setTimeout(function() { $searchInput.prop('disabled', false).val(''); }, 3500);
            });
        }
    });

    $(document).on('input', '#fstu-unit-search', function() {
        var term = $(this).val().toLowerCase(), $dropdown = $('#fstu-unit-dropdown');
        $('#fstu-unit-id').val('');
        if (term.length < 2) { $dropdown.addClass('fstu-hidden'); return; }

        var matches = allUnitsList.filter(c => c.name.toLowerCase().includes(term));
        if (matches.length) {
            var html = matches.map(c => '<div class="fstu-club-option" data-id="'+c.id+'" data-name="'+escHtml(c.name)+'">'+escHtml(c.name)+'</div>').join('');
            $dropdown.html(html).removeClass('fstu-hidden');
        } else { $dropdown.html('<div class="fstu-club-option">Нічого не знайдено</div>').removeClass('fstu-hidden'); }
    });

    $(document).on('click', '#fstu-unit-dropdown .fstu-club-option', function() {
        $('#fstu-unit-id').val($(this).data('id'));
        $('#fstu-unit-search').val($(this).data('name'));
        $('#fstu-unit-dropdown').addClass('fstu-hidden');
    });

    $(document).on('click', '#fstu-personal-unit-cancel, #fstu-personal-unit-cancel-icon', function() {
        $('#fstu-personal-unit-modal').addClass('fstu-hidden');
    });

    $(document).on('submit', '#fstu-personal-unit-form', function(e) {
        e.preventDefault();
        var unitId = $('#fstu-unit-id').val();
        if (!unitId) return alert('Оберіть осередок зі списку підказок.');

        var $btn = $('#fstu-personal-unit-submit').prop('disabled', true).text('Збереження...');
        $.post(l10n.ajaxUrl, {
            action: 'fstu_personal_cabinet_add_unit',
            nonce: l10n.nonce,
            profile_user_id: state.profileUserId,
            unit_id: unitId
        }).done(function(res) {
            if(res.success) {
                $('#fstu-personal-unit-modal').addClass('fstu-hidden');
                state.flashAlert = { type: 'success', message: 'Осередок додано.' };
                loadProfile();
            } else {
                $('#fstu-personal-unit-alert').removeClass('fstu-hidden').addClass('fstu-alert--error').text(res.data.message || 'Помилка');
            }
        }).fail(function(xhr) {
            $('#fstu-personal-unit-alert').removeClass('fstu-hidden').addClass('fstu-alert--error').text(getAjaxErrorMessage(xhr, 'Помилка збереження.'));
        }).always(function() {
            $btn.prop('disabled', false).text('Додати');
        });
    });

    $(document).on('click', '.fstu-delete-unit-btn', function() {
        if (!confirm('Видалити цей осередок з історії користувача?')) return;
        $.post(l10n.ajaxUrl, {
            action: 'fstu_personal_cabinet_delete_unit',
            nonce: l10n.nonce,
            profile_user_id: state.profileUserId,
            unit_id: $(this).data('id')
        }).done(function(res) {
            if(res.success) {
                state.flashAlert = { type: 'success', message: 'Осередок видалено.' };
                loadProfile();
            } else {
                alert(res.data && res.data.message ? res.data.message : 'Помилка видалення');
            }
        }).fail(function(xhr) {
            alert(getAjaxErrorMessage(xhr, 'Помилка видалення осередку.'));
        });
    });
    // Логіка Видів туризму (Autocomplete)
    var allTourismList = [];
    $(document).on('click', '.fstu-personal-tab-actions__item-button[data-action-key="add_tourism"]', function() {
        $('#fstu-personal-tourism-alert').addClass('fstu-hidden');
        $('#fstu-personal-tourism-form')[0].reset();
        $('#fstu-tourism-id').val('');
        $('#fstu-personal-tourism-modal').removeClass('fstu-hidden');

        if (!allTourismList.length) {
            var $searchInput = $('#fstu-tourism-search');
            $searchInput.prop('disabled', true).val('Завантаження довідника...');
            $.post(l10n.ajaxUrl, { action: 'fstu_personal_cabinet_get_all_tourism', nonce: l10n.nonce }).done(function(res) {
                if (res && res.success) {
                    allTourismList = res.data || [];
                    $searchInput.prop('disabled', false).val('').attr('placeholder', 'Введіть назву (наприклад, Пішохідний)...');
                    $searchInput.focus();
                } else {
                    $searchInput.val('Помилка: ' + (res.data && res.data.message ? res.data.message : 'Немає даних'));
                    setTimeout(function() { $searchInput.prop('disabled', false).val(''); }, 3500);
                }
            }).fail(function(xhr) {
                $searchInput.val('Фатальна помилка сервера.');
                setTimeout(function() { $searchInput.prop('disabled', false).val(''); }, 3500);
            });
        }
    });

    $(document).on('input', '#fstu-tourism-search', function() {
        var term = $(this).val().toLowerCase(), $dropdown = $('#fstu-tourism-dropdown');
        $('#fstu-tourism-id').val('');
        if (term.length < 1) { $dropdown.addClass('fstu-hidden'); return; }

        var matches = allTourismList.filter(c => c.name.toLowerCase().includes(term));
        if (matches.length) {
            var html = matches.map(c => '<div class="fstu-club-option" data-id="'+c.id+'" data-name="'+escHtml(c.name)+'">'+escHtml(c.name)+'</div>').join('');
            $dropdown.html(html).removeClass('fstu-hidden');
        } else { $dropdown.html('<div class="fstu-club-option">Нічого не знайдено</div>').removeClass('fstu-hidden'); }
    });

    $(document).on('click', '#fstu-tourism-dropdown .fstu-club-option', function() {
        $('#fstu-tourism-id').val($(this).data('id'));
        $('#fstu-tourism-search').val($(this).data('name'));
        $('#fstu-tourism-dropdown').addClass('fstu-hidden');
    });

    $(document).on('click', '#fstu-personal-tourism-cancel, #fstu-personal-tourism-cancel-icon', function() {
        $('#fstu-personal-tourism-modal').addClass('fstu-hidden');
    });

    $(document).on('submit', '#fstu-personal-tourism-form', function(e) {
        e.preventDefault();
        var tourismId = $('#fstu-tourism-id').val();
        if (!tourismId) return alert('Оберіть вид туризму зі списку підказок.');

        var $btn = $('#fstu-personal-tourism-submit').prop('disabled', true).text('Збереження...');
        $.post(l10n.ajaxUrl, {
            action: 'fstu_personal_cabinet_add_tourism',
            nonce: l10n.nonce,
            profile_user_id: state.profileUserId,
            tourism_id: tourismId
        }).done(function(res) {
            if(res.success) {
                $('#fstu-personal-tourism-modal').addClass('fstu-hidden');
                state.flashAlert = { type: 'success', message: 'Вид туризму додано.' };
                loadProfile();
            } else {
                $('#fstu-personal-tourism-alert').removeClass('fstu-hidden').addClass('fstu-alert--error').text(res.data.message || 'Помилка');
            }
        }).fail(function(xhr) {
            $('#fstu-personal-tourism-alert').removeClass('fstu-hidden').addClass('fstu-alert--error').text(getAjaxErrorMessage(xhr, 'Помилка збереження.'));
        }).always(function() {
            $btn.prop('disabled', false).text('Додати');
        });
    });

    $(document).on('click', '.fstu-delete-tourism-btn', function() {
        if (!confirm('Видалити цей вид туризму?')) return;
        $.post(l10n.ajaxUrl, {
            action: 'fstu_personal_cabinet_delete_tourism',
            nonce: l10n.nonce,
            profile_user_id: state.profileUserId,
            tourism_id: $(this).data('id')
        }).done(function(res) {
            if(res.success) {
                state.flashAlert = { type: 'success', message: 'Вид туризму видалено.' };
                loadProfile();
            } else {
                alert(res.data && res.data.message ? res.data.message : 'Помилка видалення');
            }
        }).fail(function(xhr) {
            alert(getAjaxErrorMessage(xhr, 'Помилка видалення.'));
        });
    });
    // Логіка Досвід (Довідка за похід)
    $(document).on('click', '.fstu-edit-divodka-btn', function() {
        $('#fstu-personal-experience-alert').addClass('fstu-hidden');
        $('#fstu-experience-id').val($(this).attr('data-id')); // Змінено на attr для надійності
        $('#fstu-experience-url').val($(this).attr('data-url'));
        $('#fstu-personal-experience-modal').removeClass('fstu-hidden');
    });

    $(document).on('click', '#fstu-personal-experience-cancel, #fstu-personal-experience-cancel-icon', function() {
        $('#fstu-personal-experience-modal').addClass('fstu-hidden');
    });

    $(document).on('submit', '#fstu-personal-experience-form', function(e) {
        e.preventDefault();
        var expId = $('#fstu-experience-id').val();
        var url = $('#fstu-experience-url').val();

        var $btn = $('#fstu-personal-experience-submit').prop('disabled', true).text('Збереження...');
        $.post(l10n.ajaxUrl, {
            action: 'fstu_personal_cabinet_update_experience_url',
            nonce: l10n.nonce,
            profile_user_id: state.profileUserId,
            experience_id: expId,
            url: url
        }).done(function(res) {
            if(res.success) {
                $('#fstu-personal-experience-modal').addClass('fstu-hidden');
                state.flashAlert = { type: 'success', message: 'Посилання на довідку оновлено.' };
                loadProfile();
            } else {
                $('#fstu-personal-experience-alert').removeClass('fstu-hidden').addClass('fstu-alert--error').text(res.data.message || 'Помилка');
            }
        }).fail(function(xhr) {
            $('#fstu-personal-experience-alert').removeClass('fstu-hidden').addClass('fstu-alert--error').text(getAjaxErrorMessage(xhr, 'Помилка збереження.'));
        }).always(function() {
            $btn.prop('disabled', false).text('Зберегти посилання');
        });
    });
    // Логіка Розряди (Ranks)
    var allRanksList = [];
    
    function populateTourismSelect() {
        var $tSelect = $('#fstu-rank-tourism-id');
        if ($tSelect.children().length <= 1 && allTourismList.length) {
            var opts = '<option value="">Оберіть зі списку...</option>';
            $.each(allTourismList, function(_, t) { opts += '<option value="'+t.id+'">'+escHtml(t.name)+'</option>'; });
            $tSelect.html(opts);
        }
    }

    $(document).on('click', '.fstu-personal-tab-actions__item-button[data-action-key="add_rank"]', function() {
        $('#fstu-personal-rank-alert').addClass('fstu-hidden');
        $('#fstu-personal-rank-form')[0].reset();
        $('#fstu-rank-tourism-group').addClass('fstu-hidden');
        $('#fstu-rank-tourism-id').prop('required', false);
        $('#fstu-personal-rank-modal').removeClass('fstu-hidden');
        
        if (!allRanksList.length) {
            var $rankSelect = $('#fstu-rank-id');
            $rankSelect.html('<option value="">Завантаження...</option>').prop('disabled', true);
            $.post(l10n.ajaxUrl, { action: 'fstu_personal_cabinet_get_all_ranks', nonce: l10n.nonce }).done(function(res) {
                if (res && res.success) {
                    allRanksList = res.data || [];
                    var opts = '<option value="">Оберіть зі списку...</option>';
                    $.each(allRanksList, function(_, r) { opts += '<option value="'+r.id+'">'+escHtml(r.name)+'</option>'; });
                    $rankSelect.html(opts).prop('disabled', false);
                }
            });
        }
        
        // Використовуємо кеш видів туризму (він спільний з іншою вкладкою)
        if (!allTourismList.length) {
            $.post(l10n.ajaxUrl, { action: 'fstu_personal_cabinet_get_all_tourism', nonce: l10n.nonce }).done(function(res) {
                if (res && res.success) allTourismList = res.data || [];
                populateTourismSelect();
            });
        } else {
            populateTourismSelect();
        }
    });

    $(document).on('change', '#fstu-rank-show-tourism', function() {
        if ($(this).is(':checked')) {
            $('#fstu-rank-tourism-group').removeClass('fstu-hidden');
            $('#fstu-rank-tourism-id').prop('required', true);
        } else {
            $('#fstu-rank-tourism-group').addClass('fstu-hidden');
            $('#fstu-rank-tourism-id').prop('required', false).val('');
        }
    });

    $(document).on('click', '#fstu-personal-rank-cancel, #fstu-personal-rank-cancel-icon', function() {
        $('#fstu-personal-rank-modal').addClass('fstu-hidden');
    });

    $(document).on('submit', '#fstu-personal-rank-form', function(e) {
        e.preventDefault();
        var $btn = $('#fstu-personal-rank-submit').prop('disabled', true).text('Збереження...');
        
        $.post(l10n.ajaxUrl, {
            action: 'fstu_personal_cabinet_add_rank',
            nonce: l10n.nonce,
            profile_user_id: state.profileUserId,
            rank_id: $('#fstu-rank-id').val(),
            tourism_id: $('#fstu-rank-tourism-id').val(),
            prikaz_num: $('#fstu-rank-prikaz-num').val(),
            prikaz_date: $('#fstu-rank-prikaz-date').val(),
            prikaz_url: $('#fstu-rank-prikaz-url').val()
        }).done(function(res) {
            if(res.success) {
                $('#fstu-personal-rank-modal').addClass('fstu-hidden');
                state.flashAlert = { type: 'success', message: 'Розряд успішно додано.' };
                loadProfile();
            } else {
                $('#fstu-personal-rank-alert').removeClass('fstu-hidden').addClass('fstu-alert--error').text(res.data.message || 'Помилка');
            }
        }).fail(function(xhr) {
            $('#fstu-personal-rank-alert').removeClass('fstu-hidden').addClass('fstu-alert--error').text(getAjaxErrorMessage(xhr, 'Помилка збереження.'));
        }).always(function() {
            $btn.prop('disabled', false).text('Зберегти');
        });
    });

    $(document).on('click', '.fstu-delete-rank-btn', function() {
        if (!confirm('Видалити цей спортивний розряд?')) return;
        $.post(l10n.ajaxUrl, {
            action: 'fstu_personal_cabinet_delete_rank',
            nonce: l10n.nonce,
            profile_user_id: state.profileUserId,
            rank_id: $(this).data('id')
        }).done(function(res) {
            if(res.success) {
                state.flashAlert = { type: 'success', message: 'Розряд видалено.' };
                loadProfile();
            } else {
                alert(res.data && res.data.message ? res.data.message : 'Помилка видалення');
            }
        }).fail(function(xhr) {
            alert(getAjaxErrorMessage(xhr, 'Помилка видалення.'));
        });
    });
    // Логіка Суддівство (Judging)
    var allJudgingCategories = [];

    $(document).on('click', '.fstu-personal-tab-actions__item-button[data-action-key="add_judging"]', function() {
        $('#fstu-personal-judging-alert').addClass('fstu-hidden');
        $('#fstu-personal-judging-form')[0].reset();
        $('#fstu-personal-judging-modal').removeClass('fstu-hidden');
        
        if (!allJudgingCategories.length) {
            var $select = $('#fstu-judging-category-id');
            $select.html('<option value="">Завантаження...</option>').prop('disabled', true);
            $.post(l10n.ajaxUrl, { action: 'fstu_personal_cabinet_get_all_referee_categories', nonce: l10n.nonce }).done(function(res) {
                if (res && res.success) {
                    allJudgingCategories = res.data || [];
                    var opts = '<option value="">Оберіть зі списку...</option>';
                    $.each(allJudgingCategories, function(_, r) { opts += '<option value="'+r.id+'">'+escHtml(r.name)+'</option>'; });
                    $select.html(opts).prop('disabled', false);
                }
            });
        }
    });

    $(document).on('click', '#fstu-personal-judging-cancel, #fstu-personal-judging-cancel-icon', function() {
        $('#fstu-personal-judging-modal').addClass('fstu-hidden');
    });

    $(document).on('submit', '#fstu-personal-judging-form', function(e) {
        e.preventDefault();
        var $btn = $('#fstu-personal-judging-submit');
        var $alert = $('#fstu-personal-judging-alert');

        $btn.prop('disabled', true).text('Збереження...');
        $alert.addClass('fstu-hidden').text(''); 

        $.post(l10n.ajaxUrl, {
            action: 'fstu_personal_cabinet_add_judging',
            nonce: l10n.nonce,
            profile_user_id: state.profileUserId,
            // Передаємо ТІЛЬКИ категорію
            category_id: $('#fstu-judging-category-id').val()
        }).done(function(res) {
            if(res.success) {
                $('#fstu-personal-judging-modal').addClass('fstu-hidden');
                state.flashAlert = { type: 'success', message: 'Суддівську категорію додано.' };
                loadProfile();
            } else {
                $alert.removeClass('fstu-hidden').addClass('fstu-alert--error').text(res.data.message || 'Помилка');
            }
        }).fail(function(xhr) {
            $alert.removeClass('fstu-hidden').addClass('fstu-alert--error').text(getAjaxErrorMessage(xhr, 'Помилка збереження.'));
        }).always(function() {
            $btn.prop('disabled', false).text('Додати');
        });
    });

    $(document).on('click', '.fstu-delete-judging-btn', function() {
        if (!confirm('Видалити цю суддівську категорію?')) return;
        $.post(l10n.ajaxUrl, {
            action: 'fstu_personal_cabinet_delete_judging',
            nonce: l10n.nonce,
            profile_user_id: state.profileUserId,
            judging_id: $(this).data('id')
        }).done(function(res) {
            if(res.success) {
                state.flashAlert = { type: 'success', message: 'Категорію видалено.' };
                loadProfile();
            } else {
                alert(res.data && res.data.message ? res.data.message : 'Помилка видалення');
            }
        }).fail(function(xhr) {
            alert(getAjaxErrorMessage(xhr, 'Помилка видалення.'));
        });
    });
    // Логіка швидкого переходу до реєстру суддів
    $(document).on('click', '.fstu-personal-tab-actions__item-button[data-action-key="open_referee_registry"]', function() {
        window.open('/referee', '_blank');
    });
    // Логіка додавання вітрильного внеску (Dues Sail)
    $(document).on('click', '.fstu-personal-tab-actions__item-button[data-action-key="add_sail_dues"]', function() {
        $('#fstu-personal-dues-sail-alert').addClass('fstu-hidden');
        $('#fstu-personal-dues-sail-form')[0].reset();

        // Підставляємо поточний рік за замовчуванням
        var currentYear = new Date().getFullYear();
        $('#fstu-dues-sail-year').val(currentYear);

        $('#fstu-personal-dues-sail-modal').removeClass('fstu-hidden');
    });

    $(document).on('click', '#fstu-personal-dues-sail-cancel, #fstu-personal-dues-sail-cancel-icon', function() {
        $('#fstu-personal-dues-sail-modal').addClass('fstu-hidden');
    });

    $(document).on('submit', '#fstu-personal-dues-sail-form', function(e) {
        e.preventDefault();
        var $btn = $('#fstu-personal-dues-sail-submit').prop('disabled', true).text('Збереження...');
        var $alert = $('#fstu-personal-dues-sail-alert').addClass('fstu-hidden');

        $.post(l10n.ajaxUrl, {
            action: 'fstu_personal_cabinet_add_sail_dues',
            nonce: l10n.nonce,
            profile_user_id: state.profileUserId,
            year: $('#fstu-dues-sail-year').val(),
            summa: $('#fstu-dues-sail-sum').val()
        }).done(function(res) {
            if(res.success) {
                $('#fstu-personal-dues-sail-modal').addClass('fstu-hidden');
                state.flashAlert = { type: 'success', message: 'Оплату успішно додано.' };
                loadProfile();
            } else {
                $alert.removeClass('fstu-hidden').addClass('fstu-alert--error').text(res.data.message || 'Помилка');
            }
        }).fail(function(xhr) {
            $alert.removeClass('fstu-hidden').addClass('fstu-alert--error').text(getAjaxErrorMessage(xhr, 'Помилка збереження.'));
        }).always(function() {
            $btn.prop('disabled', false).text('Зберегти');
        });
    });
    // Ініціалізація профілю при завантаженні сторінки
    loadProfile();
});

