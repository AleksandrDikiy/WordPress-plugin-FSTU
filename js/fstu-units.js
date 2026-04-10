/**
 * Клієнтська логіка модуля "Довідник осередків ФСТУ".
 * * Version: 1.1.0
 * Date_update: 2026-04-10
 */

jQuery(document).ready(function($) {
    'use strict';

    // Стан таблиці Довідника
    let listState = { page: 1, perPage: 10, search: '' };
    // Стан таблиці Протоколу
    let protocolState = { page: 1, perPage: 10, search: '' };

	// Ініціалізація
    loadDictionaries();
    loadUnits();

    // ==========================================
    // 1. ДОВІДНИК: Завантаження та рендер
    // ==========================================
    function loadUnits() {
        $('#fstu-units-tbody').html('<tr><td colspan="8" style="text-align:center;">Завантаження...</td></tr>');
        
        $.post(fstuUnitsData.ajax_url, {
            action: 'fstu_units_get_list',
            nonce: fstuUnitsData.nonce,
            page: listState.page,
            per_page: listState.perPage,
            search: listState.search
        }, function(response) {
            if (response.success) {
                renderUnitsTable(response.data);
            } else {
                // Безпечне отримання повідомлення навіть якщо data не існує
                let errorMsg = (response && response.data && response.data.message) ? response.data.message : fstuUnitsData.i18n.error;
                alert(errorMsg);
                $('#fstu-units-tbody').html(`<tr><td colspan="8" style="text-align:center; color:red;">${escapeHtml(errorMsg)}</td></tr>`);
            }
        }).fail(function() {
            alert(fstuUnitsData.i18n.error);
            $('#fstu-units-tbody').html('<tr><td colspan="8" style="text-align:center; color:red;">Помилка з\'єднання з сервером</td></tr>');
        });
    }
	// ==========================================
    // ЗАВАНТАЖЕННЯ ДОВІДНИКІВ ДЛЯ ФОРМ
    // ==========================================
    function loadDictionaries() {
        // Завантажуємо лише для тих, хто має доступ до редагування (якщо є форма на сторінці)
        if ($('#fstu-unit-form').length === 0) return;

        $.post(fstuUnitsData.ajax_url, {
            action: 'fstu_units_get_dictionaries',
            nonce: fstuUnitsData.nonce
        }, function(response) {
            if (response.success) {
                let d = response.data;
                
                let opfHtml = '<option value="">-- Оберіть ОПФ --</option>';
                d.opf.forEach(i => opfHtml += `<option value="${i.OPF_ID}">${escapeHtml(i.OPF_Name)}</option>`);
                $('#OPF_ID').html(opfHtml);

                let typeHtml = '<option value="">-- Оберіть ранг --</option>';
                d.types.forEach(i => typeHtml += `<option value="${i.UnitType_ID}">${escapeHtml(i.UnitType_Name)}</option>`);
                $('#UnitType_ID').html(typeHtml);

                let regionHtml = '<option value="">-- Оберіть регіон --</option>';
                d.regions.forEach(i => regionHtml += `<option value="${i.Region_ID}">${escapeHtml(i.Region_Name)}</option>`);
                $('#Region_ID').html(regionHtml);

                let parentHtml = '<option value="0">-- Немає --</option>';
                d.parents.forEach(i => parentHtml += `<option value="${i.Unit_ID}">${escapeHtml(i.Unit_Name)}</option>`);
                $('#Unit_Parent').html(parentHtml);
            }
        });
    }

    function renderUnitsTable(data) {
        let html = '';
        if (data.items.length === 0) {
            html = '<tr><td colspan="8" style="text-align:center;">Немає записів</td></tr>';
        } else {
            let num = (listState.page - 1) * listState.perPage + 1;
            data.items.forEach(function(item) {
                let rowClass = parseFloat(item.Unit_AnnualFee) > 0 ? 'fstu-row-highlight' : '';
                // Безпечне кодування JSON для атрибутів (захист від одинарних лапок у назвах)
                let jsonStr = escapeHtml(JSON.stringify(item));
                
                html += `<tr class="${rowClass}">
                    <td>${num++}</td>
                    <td><a href="#" class="fstu-action-view" data-id="${item.Unit_ID}" data-json="${jsonStr}" style="font-weight:bold;">${escapeHtml(item.Unit_Name)}</a></td>
                    <td title="ЄДРПОУ: ${escapeHtml(item.Unit_OKPO)}">${escapeHtml(item.Unit_ShortName)}</td>
                    <td title="${escapeHtml(item.OPF_Name)}">${escapeHtml(item.OPF_NameShort)}</td>
                    <td>${escapeHtml(item.UnitType_Name)}</td>
                    <td>${escapeHtml(item.Region_Name)}</td>
                    <td>${escapeHtml(item.City_Name)}</td>
                    <td style="text-align:center; position:relative;">
                        <button type="button" class="fstu-dropdown-toggle" data-id="${item.Unit_ID}" title="Дії" aria-label="Дії">▼</button>
                        <ul class="fstu-dropdown-menu" id="dropdown-${item.Unit_ID}">
                            <li><a href="#" class="fstu-action-view" data-id="${item.Unit_ID}" data-json="${jsonStr}">Перегляд</a></li>
                            <li><a href="#" class="fstu-action-edit" data-id="${item.Unit_ID}">Редагувати</a></li>
                            <li><a href="#" class="fstu-action-delete" data-id="${item.Unit_ID}" style="color:red;">Видалити</a></li>
                        </ul>
                    </td>
                </tr>`;
            });
        }
        $('#fstu-units-tbody').html(html);
        renderPagination('list', data, '#fstu-units-pagination-controls', '#fstu-units-pagination-info');
    }

    // ==========================================
    // 2. ПРОТОКОЛ: Завантаження та рендер
    // ==========================================
    // ==========================================
    // 2. ПРОТОКОЛ: Завантаження та рендер
    // ==========================================
    function loadProtocol() {
        $('#fstu-units-protocol-tbody').html('<tr><td colspan="5" style="text-align:center;">Завантаження...</td></tr>');
        
        $.post(fstuUnitsData.ajax_url, {
            action: 'fstu_units_get_protocol',
            nonce: fstuUnitsData.nonce,
            page: protocolState.page,
            per_page: protocolState.perPage,
            search: protocolState.search
        }, function(response) {
            if (response.success) {
                renderProtocolTable(response.data);
            } else {
                // Безпечне отримання повідомлення навіть якщо data не існує
                let errorMsg = (response && response.data && response.data.message) ? response.data.message : fstuUnitsData.i18n.error;
                alert(errorMsg);
                $('#fstu-units-tbody').html(`<tr><td colspan="8" style="text-align:center; color:red;">${escapeHtml(errorMsg)}</td></tr>`);
            }
        }).fail(function() {
            // Обробка крашу сервера або мережі (500, 502, timeout)
            alert(fstuUnitsData.i18n.error);
            $('#fstu-units-protocol-tbody').html('<tr><td colspan="5" style="text-align:center; color:red;">Помилка з\'єднання з сервером</td></tr>');
        });
    }

    function renderProtocolTable(data) {
        let html = '';
        if (data.items.length === 0) {
            html = '<tr><td colspan="5" style="text-align:center;">Немає логів</td></tr>';
        } else {
            data.items.forEach(function(item) {
                html += `<tr>
                    <td>${escapeHtml(item.Logs_DateCreate)}</td>
                    <td style="text-align:center;">${buildTypeBadge(item.Logs_Type)}</td>
                    <td>${escapeHtml(item.Logs_Text)}</td>
                    <td style="text-align:center;">${escapeHtml(item.Logs_Error)}</td>
                    <td>${escapeHtml(item.FIO || 'Система')}</td>
                </tr>`;
            });
        }
        $('#fstu-units-protocol-tbody').html(html);
        renderPagination('protocol', data, '#fstu-protocol-pagination-controls', '#fstu-protocol-pagination-info');
    }

    function buildTypeBadge(type) {
        let cls = 'fstu-badge--default', label = type || '—';
        if (type === 'I') { cls = 'fstu-badge--insert'; label = 'INSERT'; }
        if (type === 'U') { cls = 'fstu-badge--update'; label = 'UPDATE'; }
        if (type === 'D') { cls = 'fstu-badge--delete'; label = 'DELETE'; }
        return `<span class="fstu-badge ${cls}">${escapeHtml(label)}</span>`;
    }

    // ==========================================
    // 3. ПОДІЇ ТА UI (Пошук, Пагінація, Вкладки)
    // ==========================================
    
    $(document).on('click', '#fstu-units-btn-protocol', function() {
        $('#fstu-units-directory-section').hide();
        $('#fstu-units-protocol-section').show();
        loadProtocol();
    });

    $(document).on('click', '#fstu-units-btn-back-directory', function() {
        $('#fstu-units-protocol-section').hide();
        $('#fstu-units-directory-section').show();
        loadUnits();
    });

    // ==========================================
    // 3. ПОДІЇ ТА UI (Пошук, Пагінація, Вкладки)
    // ==========================================

    // Фікс випадаючого меню (щоб не обрізалося таблицею)
    $(document).on('click', '.fstu-dropdown-toggle', function(e) {
        e.stopPropagation();
        $('.fstu-dropdown-menu').removeClass('fstu-dropdown--open');

        let $menu = $(this).siblings('.fstu-dropdown-menu');
        $menu.toggleClass('fstu-dropdown--open');

        if ($menu.hasClass('fstu-dropdown--open')) {
            // Отримуємо координати кнопки на екрані
            let rect = this.getBoundingClientRect();
            $menu.css({
                'position': 'fixed',
                'top': rect.bottom + 'px',
                'left': 'auto',
                'right': ($(window).width() - rect.right) + 'px',
                'z-index': 999999 // Поверх усього
            });
        }
    });

    // Закриття меню при кліку поза ним
    $(document).on('click', function() {
        $('.fstu-dropdown-menu').removeClass('fstu-dropdown--open');
    });

    // Закриття меню при скролі (щоб меню не "літало" окремо від таблиці)
    window.addEventListener('scroll', function() {
        $('.fstu-dropdown-menu').removeClass('fstu-dropdown--open');
    }, true);

    $(document).on('click', function() {
        $('.fstu-dropdown-menu').removeClass('fstu-dropdown--open');
    });

    $(document).on('change', '#Region_ID', function() {
        let regionId = $(this).val();
        let $citySelect = $('#City_ID');
        $citySelect.html('<option value="">Завантаження...</option>');

        $.post(fstuUnitsData.ajax_url, {
            action: 'fstu_units_get_cities',
            nonce: fstuUnitsData.nonce,
            region_id: regionId
        }, function(response) {
            if (response.success) {
                let options = '<option value="">-- Оберіть місто --</option>';
                response.data.cities.forEach(function(city) {
                    options += `<option value="${city.City_ID}">${escapeHtml(city.City_Name)}</option>`;
                });
                $citySelect.html(options);
            }
        });
    });

    // ==========================================
    // ЖИВИЙ ПОШУК (Debounce 400ms)
    // ==========================================
    
    // Пошук для Довідника
    let searchTimeout;
    $(document).on('input', '#fstu-units-search', function() {
        clearTimeout(searchTimeout);
        let val = $(this).val();
        
        searchTimeout = setTimeout(function() {
            if (listState.search !== val) {
                listState.search = val;
                listState.page = 1;
                loadUnits();
            }
        }, 400); // Чекаємо 400мс після останнього натискання клавіші
    });

    // Пошук для Протоколу (щоб там також працювало автоматично)
    let protocolSearchTimeout;
    $(document).on('input', '#fstu-protocol-search', function() {
        clearTimeout(protocolSearchTimeout);
        let val = $(this).val();
        
        protocolSearchTimeout = setTimeout(function() {
            if (protocolState.search !== val) {
                protocolState.search = val;
                protocolState.page = 1;
                loadProtocol();
            }
        }, 400);
    });

    $(document).on('change', '#fstu-units-per-page', function() {
        listState.perPage = parseInt($(this).val());
        listState.page = 1;
        loadUnits();
    });

    // ==========================================
    // 4. CRUD ОПЕРАЦІЇ ТА ПЕРЕГЛЯД
    // ==========================================

    const $modalEdit = $('#fstu-modal-unit-edit');
    const $modalView = $('#fstu-modal-unit-view');
    const $formEdit = $('#fstu-unit-form');

    // 4.1. Закриття всіх модалок
    $(document).on('click', '.fstu-modal-close', function() {
        $('.fstu-modal').hide();
    });

    // 4.2. ПЕРЕГЛЯД картки (Оновлений з історією оплат)
    $(document).on('click', '.fstu-action-view', function(e) {
        e.preventDefault();
        $('.fstu-dropdown-menu').removeClass('fstu-dropdown--open');
        
        let unitId = $(this).data('id');
        let data = $(this).data('json');

        // Заповнення базових даних
        $('#view-Unit_Name').text(data.Unit_Name || '');
        $('#view-Unit_ShortName').text(data.Unit_ShortName || '');
        $('#view-OPF_Name').text(data.OPF_Name || data.OPF_NameShort || '');
        $('#view-UnitType_Name').text(data.UnitType_Name || '');
        $('#view-Region_Name').text(data.Region_Name || '');
        $('#view-City_Name').text(data.City_Name || '');
        $('#view-Unit_Adr').text(data.Unit_Adr || '');
        $('#view-Unit_OKPO').text(data.Unit_OKPO || '');
        $('#view-Unit_EntranceFee').text(data.Unit_EntranceFee || '0.00');
        $('#view-Unit_AnnualFee').text(data.Unit_AnnualFee || '0.00');
        $('#view-Unit_UrlPay').text(data.Unit_UrlPay || '');
        $('#view-Unit_PaymentCard').text(data.Unit_PaymentCard || '');

        // Завантаження історії оплат (тільки для адмінів/реєстраторів)
        $('#view-dues-tbody').html('<tr><td colspan="4" style="text-align:center;">Завантаження...</td></tr>');
        $('#fstu-view-dues-wrapper').show();

        $.post(fstuUnitsData.ajax_url, {
            action: 'fstu_units_get_dues',
            nonce: fstuUnitsData.nonce,
            Unit_ID: unitId
        }, function(response) {
            if (response.success) {
                let html = '';
                response.data.history.forEach(function(item) {
                    html += `<tr>
                        <td><b>${item.year}</b></td>`;
                    
                    if (item.status === 'paid') {
                        html += `
                        <td>${item.summa}</td>
                        <td>${escapeHtml(item.date)}</td>
                        <td><span class="fstu-badge fstu-badge--insert">Сплачено</span></td>`;
                    } else {
                        // Якщо не сплачено — створюємо кнопку, яка сабмітить приховану форму Portmone
                        html += `
                        <td>—</td>
                        <td>—</td>
                        <td>
                            <button type="button" class="fstu-btn fstu-btn--action fstu-btn-pay" data-portmone='${JSON.stringify(item.portmone_data)}' style="background-color:#d9534f; color:#fff; padding:3px 8px; font-size:11px;">
                                Сплатити ${item.bill_amount} грн
                            </button>
                        </td>`;
                    }
                    html += `</tr>`;
                });
                $('#view-dues-tbody').html(html);
            } else {
                $('#view-dues-tbody').html(`<tr><td colspan="4" style="text-align:center; color:red;">${escapeHtml(response.data.message)}</td></tr>`);
            }
        });

        $modalView.show();
    });

    // Обробка кліку на "Сплатити" (Генерація та відправка форми Portmone)
    $(document).on('click', '.fstu-btn-pay', function() {
        let pData = $(this).data('portmone');
        
        // Створюємо тимчасову форму в пам'яті браузера
        let $form = $('<form>', {
            action: 'https://www.portmone.com.ua/gateway/',
            method: 'POST',
            target: '_blank' // Відкриваємо в новій вкладці
        });

        // Додаємо всі параметри як приховані поля
        $.each(pData, function(key, value) {
            $form.append($('<input>', { type: 'hidden', name: key, value: value }));
        });

        // Додаємо форму в DOM, відправляємо і видаляємо
        $('body').append($form);
        $form.submit();
        $form.remove();
    });

    // 4.3. Відкриття модалки ДОДАТИ
    $(document).on('click', '#fstu-units-btn-add', function() {
        $formEdit[0].reset();
        $('#Unit_ID').val('0'); 
        $('#City_ID').html('<option value="">-- Оберіть регіон --</option>');
        $('#fstu-modal-title').text('Додати осередок');
        $modalEdit.show();
    });

    // 4.4. Відкриття модалки РЕДАГУВАТИ (через AJAX запит)
    $(document).on('click', '.fstu-action-edit', function(e) {
        e.preventDefault();
        let unitId = $(this).data('id');
        $('.fstu-dropdown-menu').removeClass('fstu-dropdown--open');

        $.post(fstuUnitsData.ajax_url, {
            action: 'fstu_units_get_item',
            nonce: fstuUnitsData.nonce,
            Unit_ID: unitId
        }, function(response) {
            if (response.success) {
                let data = response.data.item;
                
                $('#Unit_ID').val(data.Unit_ID);
                $('#Unit_Name').val(data.Unit_Name);
                $('#Unit_ShortName').val(data.Unit_ShortName);
                $('#Unit_Adr').val(data.Unit_Adr);
                $('#Unit_OKPO').val(data.Unit_OKPO);
                $('#Unit_EntranceFee').val(data.Unit_EntranceFee);
                $('#Unit_AnnualFee').val(data.Unit_AnnualFee);
                $('#Unit_PaymentCard').val(data.Unit_PaymentCard);
                
                $('#OPF_ID').val(data.OPF_ID);
                $('#UnitType_ID').val(data.UnitType_ID);
                $('#Unit_Parent').val(data.Unit_Parent);
                $('#Region_ID').val(data.Region_ID);
                
                let cityOptions = '<option value="">-- Оберіть місто --</option>';
                response.data.cities.forEach(function(city) {
                    let selected = (city.City_ID == data.City_ID) ? 'selected' : '';
                    cityOptions += `<option value="${city.City_ID}" ${selected}>${escapeHtml(city.City_Name)}</option>`;
                });
                $('#City_ID').html(cityOptions);

                $('#fstu-modal-title').text('Редагувати осередок');
                $modalEdit.show();
            } else {
                alert(response.data.message || fstuUnitsData.i18n.error);
            }
        });
    });

    // 4.5. Збереження форми (Insert / Update) з Honeypot
    $(document).on('submit', '#fstu-unit-form', function(e) {
        e.preventDefault();
        
        let formData = $(this).serialize() + '&nonce=' + fstuUnitsData.nonce;
        let $btn = $(this).find('.fstu-btn--save');
        let originalText = $btn.text();
        $btn.prop('disabled', true).text('Збереження...');

        $.post(fstuUnitsData.ajax_url, formData, function(response) {
            $btn.prop('disabled', false).text(originalText);
            
            if (response.success) {
                $modalEdit.hide();
                loadUnits();
            } else {
                alert(response.data.message || fstuUnitsData.i18n.error);
            }
        }).fail(function() {
            $btn.prop('disabled', false).text(originalText);
            alert(fstuUnitsData.i18n.error);
        });
    });

    // 4.6. М'яке Видалення (Soft Delete)
    $(document).on('click', '.fstu-action-delete', function(e) {
        e.preventDefault();
        let unitId = $(this).data('id');
        $('.fstu-dropdown-menu').removeClass('fstu-dropdown--open');

        if (confirm(fstuUnitsData.i18n.confirm)) {
            $.post(fstuUnitsData.ajax_url, {
                action: 'fstu_units_delete',
                nonce: fstuUnitsData.nonce,
                Unit_ID: unitId
            }, function(response) {
                if (response.success) {
                    loadUnits();
                } else {
                    alert(response.data.message || fstuUnitsData.i18n.error);
                }
            });
        }
    });

    // Загальні хелпери для форматування
    function escapeHtml(text) {
        if (!text) return '';
        let map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function renderPagination(type, data, controlsSelector, infoSelector) {
        let state = type === 'list' ? listState : protocolState;
        let fn = type === 'list' ? loadUnits : loadProtocol;
        
        let info = `Записів: ${data.total} | Сторінка ${data.page} з ${data.total_pages || 1}`;
        $(infoSelector).text(info);

        let btns = '';
        if (data.page > 1) btns += `<button class="fstu-btn fstu-btn--page" data-page="${data.page - 1}">◀</button>`;
        if (data.page < data.total_pages) btns += `<button class="fstu-btn fstu-btn--page" data-page="${data.page + 1}">▶</button>`;
        
        $(controlsSelector).html(btns);

        $(controlsSelector + ' .fstu-btn--page').off('click').on('click', function() {
            state.page = parseInt($(this).data('page'));
            fn();
        });
    }
});