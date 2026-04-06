/**
 * Клієнтська логіка модуля "Заявки в ФСТУ".
 * Суворе дотримання стандартів: делегування подій, debounce, AJAX-безпека.
 *
 * Version:     1.2.0
 * Date_update: 2026-04-06
 */

/* global fstuApplications */

jQuery(document).ready(function ($) {
    'use strict';

    // 1. Глобальний стан модуля (State Management)
    const state = {
        view: 'list', // 'list' або 'protocol'
        list: {
            page: 1,
            per_page: 10,
            search: '',
            region_id: 0,
            unit_id: 0
        },
        protocol: {
            page: 1,
            per_page: 10,
            search: ''
        }
    };

    let listRequest = null;
    let protocolRequest = null;
    let listRequestToken = 0;
    let protocolRequestToken = 0;
    const originalUnitOptions = $('#fstu-filter-unit option').map(function () {
        return {
            value: String($(this).val()),
            text: $(this).text(),
            regionId: String($(this).data('region-id') || '0')
        };
    }).get();

    // 2. Helper: Debounce (затримка для інпутів пошуку, щоб не "спамити" сервер)
    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // 3. Функції завантаження даних (AJAX)
    function fetchList() {
        const $tbody = $('#fstu-applications-tbody');
        $tbody.html('<tr><td colspan="8" style="text-align:center; padding: 20px;">' + fstuApplications.strings.loading + '</td></tr>');

        closeAllDropdowns();

        if (listRequest && typeof listRequest.abort === 'function') {
            listRequest.abort();
        }

        listRequestToken += 1;
        const currentToken = listRequestToken;

        listRequest = $.post(fstuApplications.ajaxUrl, {
            action: 'fstu_applications_get_list',
            nonce: fstuApplications.nonce,
            page: state.list.page,
            per_page: state.list.per_page,
            search: state.list.search,
            region_id: state.list.region_id,
            unit_id: state.list.unit_id
        }, function (res) {
            if (currentToken !== listRequestToken) {
                return;
            }

            if (res.success) {
                $tbody.html(res.data.html);
                renderPagination('list', res.data);
            } else {
                $tbody.html('<tr><td colspan="8" class="fstu-text-danger" style="text-align:center;">' + res.data.message + '</td></tr>');
            }
        }).fail(function () {
            if (currentToken !== listRequestToken) {
                return;
            }

            $tbody.html('<tr><td colspan="8" class="fstu-text-danger" style="text-align:center;">' + fstuApplications.strings.errorGeneric + '</td></tr>');
        }).always(function () {
            if (currentToken === listRequestToken) {
                listRequest = null;
            }
        });
    }

    function fetchProtocol() {
        const $tbody = $('#fstu-protocol-tbody');
        $tbody.html('<tr><td colspan="6" style="text-align:center; padding: 20px;">' + fstuApplications.strings.loading + '</td></tr>');

        if (protocolRequest && typeof protocolRequest.abort === 'function') {
            protocolRequest.abort();
        }

        protocolRequestToken += 1;
        const currentToken = protocolRequestToken;

        protocolRequest = $.post(fstuApplications.ajaxUrl, {
            action: 'fstu_applications_get_protocol',
            nonce: fstuApplications.nonce,
            page: state.protocol.page,
            per_page: state.protocol.per_page,
            search: state.protocol.search
        }, function (res) {
            if (currentToken !== protocolRequestToken) {
                return;
            }

            if (res.success) {
                $tbody.html(res.data.html);
                renderPagination('protocol', res.data);
            } else {
                $tbody.html('<tr><td colspan="6" class="fstu-text-danger" style="text-align:center;">' + res.data.message + '</td></tr>');
            }
        }).fail(function () {
            if (currentToken !== protocolRequestToken) {
                return;
            }

            $tbody.html('<tr><td colspan="6" class="fstu-text-danger" style="text-align:center;">' + fstuApplications.strings.errorGeneric + '</td></tr>');
        }).always(function () {
            if (currentToken === protocolRequestToken) {
                protocolRequest = null;
            }
        });
    }

    function syncUnitOptions(regionId) {
        const normalizedRegionId = String(regionId || 0);
        const currentValue = String(state.list.unit_id || 0);
        let currentExists = false;
        let html = '';

        originalUnitOptions.forEach(function (option) {
            if (option.value === '0' || normalizedRegionId === '0' || option.regionId === normalizedRegionId) {
                const selected = option.value === currentValue ? ' selected' : '';
                if (selected) {
                    currentExists = true;
                }
                html += '<option value="' + escapeHtml(option.value) + '" data-region-id="' + escapeHtml(option.regionId) + '"' + selected + '>' + escapeHtml(option.text) + '</option>';
            }
        });

        $('#fstu-filter-unit').html(html);

        if (!currentExists) {
            state.list.unit_id = 0;
            $('#fstu-filter-unit').val('0');
        }
    }

    function closeAllDropdowns() {
        $('.fstu-applications-dropdown').removeClass('is-open is-dropup');
        $('.fstu-applications-dropdown__toggle').attr('aria-expanded', 'false');
    }

    function positionDropdown($dropdown) {
        const menu = $dropdown.find('.fstu-applications-dropdown__menu').get(0);
        const toggle = $dropdown.find('.fstu-applications-dropdown__toggle').get(0);

        if (!menu || !toggle) {
            return;
        }

        const menuHeight = menu.offsetHeight || 180;
        const rect = toggle.getBoundingClientRect();
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

        if (rect.bottom + menuHeight > viewportHeight && rect.top > menuHeight) {
            $dropdown.addClass('is-dropup');
        } else {
            $dropdown.removeClass('is-dropup');
        }
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // 4. Генерація компактної пагінації
    function renderPagination(type, data) {
        const prefix = type === 'list' ? 'fstu-applications' : 'fstu-protocol';
        const $controls = $(`#${prefix}-pages`);
        const $info = $(`#${prefix}-info`);

        $info.text(`Записів: ${data.total} | Сторінка ${data.page} з ${Math.max(1, data.total_pages)}`);
        $controls.empty();

        if (data.total_pages <= 1) return;

        // Кнопка "Попередня"
        const btnPrev = $('<button>', {
            type: 'button',
            class: 'fstu-btn',
            text: '«',
            disabled: data.page === 1
        }).data('page', data.page - 1);
        $controls.append(btnPrev);

        // Поточна сторінка (візуально виділена)
        const btnCurrent = $('<button>', {
            type: 'button',
            class: 'fstu-btn',
            text: data.page,
            style: 'background-color: #b94a48; color: #fff; border-color: #9f3a38;',
            disabled: true
        });
        $controls.append(btnCurrent);

        // Кнопка "Наступна"
        const btnNext = $('<button>', {
            type: 'button',
            class: 'fstu-btn',
            text: '»',
            disabled: data.page === data.total_pages
        }).data('page', data.page + 1);
        $controls.append(btnNext);
    }

    // 5. Обробники подій (Event Bindings)
    function bindEvents() {
        $(document).on('click', function (event) {
            if (!$(event.target).closest('.fstu-applications-dropdown').length) {
                closeAllDropdowns();
            }
        });

        // --- ПЕРЕМИКАННЯ ЕКРАНІВ ---
        $('#fstu-btn-protocol').on('click', function () {
            $('#fstu-view-directory, #fstu-actions-directory').addClass('fstu-hidden');
            $('#fstu-view-protocol, #fstu-actions-protocol').removeClass('fstu-hidden');
            state.view = 'protocol';
            fetchProtocol();
        });

        $('#fstu-btn-back').on('click', function () {
            $('#fstu-view-protocol, #fstu-actions-protocol').addClass('fstu-hidden');
            $('#fstu-view-directory, #fstu-actions-directory').removeClass('fstu-hidden');
            state.view = 'list';
            fetchList();
        });

        // --- ОНОВЛЕННЯ ТАБЛИЦЬ ---
        $('#fstu-btn-refresh').on('click', fetchList);
        $('#fstu-btn-refresh-protocol').on('click', fetchProtocol);

        // --- ФІЛЬТРИ СПИСКУ ---
        $(document).on('change', '#fstu-filter-region', function () {
            state.list.region_id = parseInt($(this).val(), 10) || 0;
            state.list.page = 1;
            syncUnitOptions(state.list.region_id);
            fetchList();
        });

        $(document).on('change', '#fstu-filter-unit', function () {
            state.list.unit_id = parseInt($(this).val(), 10) || 0;
            state.list.page = 1;
            fetchList();
        });

        // --- DROPDOWN ДІЙ ---
        $(document).on('click', '.fstu-applications-dropdown__toggle', function (event) {
            event.preventDefault();
            event.stopPropagation();

            const $dropdown = $(this).closest('.fstu-applications-dropdown');
            const isOpen = $dropdown.hasClass('is-open');

            closeAllDropdowns();

            if (!isOpen) {
                $dropdown.addClass('is-open');
                positionDropdown($dropdown);
                $(this).attr('aria-expanded', 'true');
            }
        });

        // --- ПОШУК З DEBOUNCE (500ms) ---
        $('#fstu-applications-search').on('input', debounce(function () {
            state.list.search = $(this).val().trim();
            state.list.page = 1; // Скидаємо на першу сторінку при пошуку
            fetchList();
        }, 500));

        $('#fstu-protocol-filter-name').on('input', debounce(function () {
            state.protocol.search = $(this).val().trim();
            state.protocol.page = 1;
            fetchProtocol();
        }, 500));

        // --- КІЛЬКІСТЬ ЗАПИСІВ (PER PAGE) ---
        $('#fstu-applications-per-page').on('change', function () {
            state.list.per_page = parseInt($(this).val(), 10);
            state.list.page = 1;
            fetchList();
        });

        $('#fstu-protocol-per-page').on('change', function () {
            state.protocol.per_page = parseInt($(this).val(), 10);
            state.protocol.page = 1;
            fetchProtocol();
        });

        // --- ПАГІНАЦІЯ (Делегування подій) ---
        $(document).on('click', '.fstu-pagination__controls button:not(:disabled)', function () {
            const targetPage = $(this).data('page');
            if (state.view === 'list') {
                state.list.page = targetPage;
                fetchList();
            } else {
                state.protocol.page = targetPage;
                fetchProtocol();
            }
        });

        // --- ДІЇ З КАНДИДАТОМ (CRUD через Dropdown) ---

        // 1. Прийняти в члени
        $(document).on('click', '.fstu-action-accept', function () {
            closeAllDropdowns();
            if (!window.confirm(fstuApplications.strings.confirmAccept)) return;
            const userId = $(this).data('id');

            $.post(fstuApplications.ajaxUrl, {
                action: 'fstu_applications_accept',
                nonce: fstuApplications.nonce,
                id: userId
            }, function (res) {
                alert(res.data.message || (res.success ? 'Успішно!' : 'Помилка'));
                if (res.success) fetchList();
            });
        });

        // 2. Відхилити (Soft Delete)
        $(document).on('click', '.fstu-action-reject', function () {
            closeAllDropdowns();
            if (!window.confirm(fstuApplications.strings.confirmReject)) return;
            const userId = $(this).data('id');

            $.post(fstuApplications.ajaxUrl, {
                action: 'fstu_applications_reject',
                nonce: fstuApplications.nonce,
                id: userId
            }, function (res) {
                alert(res.data.message || (res.success ? 'Заявку відхилено' : 'Помилка'));
                if (res.success) fetchList();
            });
        });

        // 3. Перегляд (Тут будемо відкривати модалку)
        $(document).on('click', '.fstu-action-view', function () {
            closeAllDropdowns();
            const userId = $(this).data('id');
            // Реалізація виклику модального вікна (fstu_applications_get_single)
            alert('Відкриття картки кандидата ID: ' + userId + '\n(Модальне вікно буде налаштовано окремо)');
        });

        // Інші дії (Зміна ОФСТ, Повідомлення) також прив'язуються тут...
    }

    // 6. Ініціалізація
    syncUnitOptions(state.list.region_id);
    bindEvents();
    fetchList(); // Завантажуємо таблицю при старті
});