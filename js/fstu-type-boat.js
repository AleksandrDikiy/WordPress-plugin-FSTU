/**
 * Клієнтська логіка для модуля "Виробники та типи суден"
 * Version: 1.2.1
 */
jQuery(document).ready(function ($) {
    if (!$('#fstu-typeboat-module').length) return;

    const state = {
        view: 'producers',
        producerId: 0,
        producerName: '',
        producers: { page: 1, perPage: 10, search: '' },
        boats: { page: 1, perPage: 10, search: '' },
        protocol: { page: 1, perPage: 10, search: '' }
    };

    let searchTimeout;

    function init() {
        updateActionBar();
        bindEvents();
        loadProducers();
    }

    function formatVal(val, unit) {
        if (!val || parseFloat(val) === 0) return '—';
        return parseFloat(val).toString() + ' ' + unit;
    }

    function buildSmartLink(url, text = 'Відкрити') {
        if (!url || url.length < 5) return '—';
        const validUrl = /^https?:\/\//i.test(url) ? url : 'http://' + url;
        return `<a href="${validUrl}" target="_blank" rel="noopener noreferrer" class="fstu-smart-link">🔗 ${text}</a>`;
    }

    function buildTypeBadge(type) {
        let cls = 'fstu-badge--default';
        let label = type || '—';
        if (type === 'I') { cls = 'fstu-badge--success'; label = 'INSERT'; }
        if (type === 'U') { cls = 'fstu-badge--warning'; label = 'UPDATE'; }
        if (type === 'D') { cls = 'fstu-badge--danger'; label = 'DELETE'; }
        return `<span class="fstu-badge ${cls}">${label}</span>`;
    }

    function updateActionBar() {
        $('#fstu-typeboat-add-producer-btn, #fstu-typeboat-protocol-btn, #fstu-typeboat-list-btn').addClass('fstu-hidden');
        if (!fstuTypeBoat.permissions.canManage) $('.fstu-btn--add-boat').addClass('fstu-hidden');

        let hasButtons = false;

        if (state.view === 'producers') {
            if (fstuTypeBoat.permissions.canManage) { $('#fstu-typeboat-add-producer-btn').removeClass('fstu-hidden'); hasButtons = true; }
            if (fstuTypeBoat.permissions.canProtocol) { $('#fstu-typeboat-protocol-btn').removeClass('fstu-hidden'); hasButtons = true; }
        } else if (state.view === 'boats') {
            if (fstuTypeBoat.permissions.canProtocol) { $('#fstu-typeboat-protocol-btn').removeClass('fstu-hidden'); hasButtons = true; }
        } else if (state.view === 'protocol') {
            if (fstuTypeBoat.permissions.canProtocol) { $('#fstu-typeboat-list-btn').removeClass('fstu-hidden'); hasButtons = true; }
        }

        hasButtons ? $('.fstu-action-bar').removeClass('fstu-hidden') : $('.fstu-action-bar').addClass('fstu-hidden');
    }

    // --- ЕТАЛОННА ПАГІНАЦІЯ ---
    function updatePaginationUI(prefix, data) {
        let total = parseInt(data.total_pages) || 1;
        let current = parseInt(data.page) || 1;
        // ВИПРАВЛЕННЯ: Беремо perPage з бекенду або з поточного стейту
        let perPage = parseInt(data.per_page) || state[prefix].perPage || 10;

        $(`#fstu-${prefix}-info`).text(`Записів: ${data.total} | Сторінка ${current} з ${total}`);
        $(`#fstu-${prefix}-per-page`).val(perPage);

        $(`#fstu-${prefix}-prev`).prop('disabled', current <= 1);
        $(`#fstu-${prefix}-next`).prop('disabled', current >= total);

        let html = '';
        if (total > 1) {
            let delta = 1;
            let range = [];
            let rangeWithDots = [];
            let l;

            for (let i = 1; i <= total; i++) {
                if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) {
                    range.push(i);
                }
            }

            for (let i of range) {
                if (l) {
                    if (i - l === 2) {
                        rangeWithDots.push(l + 1);
                    } else if (i - l !== 1) {
                        rangeWithDots.push('...');
                    }
                }
                rangeWithDots.push(i);
                l = i;
            }

            for (let i of rangeWithDots) {
                if (i === '...') {
                    html += `<span style="display:inline-flex; align-items:center; padding:0 4px; color:#6b7280; font-weight:bold;">...</span>`;
                } else {
                    const activeClass = i === current ? ' fstu-btn--page-active' : '';
                    html += `<button type="button" class="fstu-btn--page fstu-num-page-btn${activeClass}" data-page="${i}">${i}</button>`;
                }
            }
        }
        $(`#fstu-${prefix}-pages`).html(html);
    }

    // --- AJAX ЗАВАНТАЖЕННЯ ---
    function loadProducers() {
        $.post(fstuTypeBoat.ajaxUrl, {
            action: 'fstu_type_boat_get_producers', nonce: fstuTypeBoat.nonce,
            page: state.producers.page, per_page: state.producers.perPage, search: state.producers.search
        }, function (res) {
            if (res.success) {
                renderProducers(res.data);
                updatePaginationUI('producers', res.data);
            }
        });
    }

    function loadBoats() {
        $.post(fstuTypeBoat.ajaxUrl, {
            action: 'fstu_type_boat_get_boats', nonce: fstuTypeBoat.nonce, producer_id: state.producerId,
            page: state.boats.page, per_page: state.boats.perPage, search: state.boats.search
        }, function (res) {
            if (res.success) {
                renderBoats(res.data);
                updatePaginationUI('boats', res.data);
            }
        });
    }

    function loadProtocol() {
        $.post(fstuTypeBoat.ajaxUrl, {
            action: 'fstu_type_boat_get_protocol', nonce: fstuTypeBoat.nonce,
            page: state.protocol.page, per_page: state.protocol.perPage, search: state.protocol.search
        }, function (res) {
            if (res.success) {
                renderProtocol(res.data);
                updatePaginationUI('protocol', res.data);
            }
        });
    }

    // --- РЕНДЕРИНГ ТАБЛИЦЬ ---
    function renderProducers(data) {
        let html = '';
        if (data.items.length === 0) {
            html = `<tr><td colspan="5" class="fstu-text-center">Дані відсутні</td></tr>`;
        } else {
            data.items.forEach((item, index) => {
                let num = (data.page - 1) * state.producers.perPage + index + 1;
                html += `<tr>
                    <td class="fstu-text-center">${num}</td>
                    <td>
                        <a href="#" class="fstu-open-boats fstu-typeboat-quickview" data-id="${item.ProducerShips_ID}" data-name="${item.ProducerShips_Name}">
                            ${item.ProducerShips_Name}
                        </a>
                        ${item.ProducerShips_URL ? '<br>' + buildSmartLink(item.ProducerShips_URL, 'Сайт') : ''}
                    </td>
                    <td>${item.ProducerShips_Phone || '—'}<br><small>${item.ProducerShips_Adr || ''}</small></td>
                    <td class="fstu-text-center"><span class="fstu-badge fstu-badge--grey">${item.BoatsCount}</span></td>
                    <td class="fstu-text-center">${buildDropdown('producer', item)}</td>
                </tr>`;
            });
        }
        $('#fstu-producers-tbody').html(html);
    }

    function renderBoats(data) {
        let html = '';
        if (data.items.length === 0) {
            html = `<tr><td colspan="5" class="fstu-text-center">Типи суден відсутні</td></tr>`;
        } else {
            data.items.forEach((item, index) => {
                let num = (data.page - 1) * state.boats.perPage + index + 1;
                let qvContent = `Площа: ${formatVal(item.TypeBoat_SailArea, 'кв.м')}\nДовжина: ${formatVal(item.TypeBoat_HillLength, 'м')}\nШирина: ${formatVal(item.TypeBoat_WidthOverall, 'м')}\nВага: ${formatVal(item.TypeBoat_Weight, 'кг')}`;

                html += `<tr>
                    <td class="fstu-text-center">${num}</td>
                    <td>
                        <span class="fstu-typeboat-quickview">
                            ${item.TypeBoat_Name}
                            <div class="fstu-typeboat-quickview-content">${qvContent}</div>
                        </span>
                    </td>
                    <td>
                        <small>S: ${formatVal(item.TypeBoat_SailArea, 'кв.м')} | L: ${formatVal(item.TypeBoat_HillLength, 'м')}</small><br>
                        <small>B: ${formatVal(item.TypeBoat_WidthOverall, 'м')} | W: ${formatVal(item.TypeBoat_Weight, 'кг')}</small>
                    </td>
                    <td>${item.TypeBoat_Notes || '—'}<br>${buildSmartLink(item.TypeBoat_URL, 'Документація')}</td>
                    <td class="fstu-text-center">${buildDropdown('boat', item)}</td>
                </tr>`;
            });
        }
        $('#fstu-boats-tbody').html(html);
    }

    function renderProtocol(data) {
        let html = '';
        if (data.items.length === 0) {
            html = `<tr><td colspan="6" class="fstu-text-center">Логи відсутні</td></tr>`;
        } else {
            data.items.forEach(item => {
                html += `<tr>
                    <td>${item.Logs_DateCreate}</td>
                    <td class="fstu-text-center">${buildTypeBadge(item.Logs_Type)}</td>
                    <td>${item.Logs_Name}</td>
                    <td>${item.Logs_Text}</td>
                    <td class="fstu-text-center">${item.Logs_Error === '✓' ? '<span style="color:green">✓</span>' : item.Logs_Error}</td>
                    <td>${item.FIO || 'Система'}</td>
                </tr>`;
            });
        }
        $('#fstu-protocol-tbody').html(html);
    }

    function buildDropdown(type, item) {
        if (!fstuTypeBoat.permissions.canManage) return '—';
        let id = type === 'producer' ? item.ProducerShips_ID : item.TypeBoat_ID;
        let dataAttr = `data-id="${id}" data-type="${type}"`;
        let jsonStr = JSON.stringify(item).replace(/'/g, "&apos;");

        let menuItems = '';

        if (type === 'producer') {
            menuItems += `<button type="button" class="fstu-dropdown__item fstu-open-boats" data-id="${id}" data-name="${item.ProducerShips_Name}"><span class="dashicons dashicons-visibility"></span> Перегляд</button>`;
        }
        if (fstuTypeBoat.permissions.canManage) {
            menuItems += `<button type="button" class="fstu-dropdown__item fstu-edit-record" ${dataAttr} data-json='${jsonStr}'><span class="dashicons dashicons-edit"></span> Редагувати</button>`;
        }
        if (fstuTypeBoat.permissions.canDelete) {
            menuItems += `<button type="button" class="fstu-dropdown__item fstu-dropdown__item--danger fstu-delete-record" ${dataAttr}><span class="dashicons dashicons-trash"></span> Видалити</button>`;
        }

        if (!menuItems) return '—';

        return `
        <div style="position:relative; display:inline-block;">
            <button type="button" class="fstu-dropdown-toggle fstu-trigger-menu" title="Дії" ${dataAttr}>▼</button>
            <div class="fstu-dropdown-menu fstu-hidden" id="menu-${type}-${id}">${menuItems}</div>
        </div>`;
    }

    // --- ОБРОБКА ПОДІЙ ---
    function bindEvents() {
        // ВИПРАВЛЕННЯ: Сувора перевірка по ID селектора
        $(document).on('change', '.fstu-select--compact', function () {
            let val = parseInt($(this).val(), 10) || 10;
            let id = $(this).attr('id');

            if (id === 'fstu-producers-per-page') {
                state.producers.perPage = val;
                state.producers.page = 1;
                loadProducers();
            } else if (id === 'fstu-boats-per-page') {
                state.boats.perPage = val;
                state.boats.page = 1;
                loadBoats();
            } else if (id === 'fstu-protocol-per-page') {
                state.protocol.perPage = val;
                state.protocol.page = 1;
                loadProtocol();
            }
        });

        $(document).on('click', '.fstu-num-page-btn', function () {
            if ($(this).hasClass('fstu-btn--page-active')) return;
            let p = parseInt($(this).data('page'), 10);
            if (state.view === 'producers') { state.producers.page = p; loadProducers(); }
            else if (state.view === 'boats') { state.boats.page = p; loadBoats(); }
            else if (state.view === 'protocol') { state.protocol.page = p; loadProtocol(); }
        });

        $(document).on('click', '.fstu-prev-page-btn', function () {
            if ($(this).prop('disabled')) return;
            if (state.view === 'producers' && state.producers.page > 1) { state.producers.page--; loadProducers(); }
            else if (state.view === 'boats' && state.boats.page > 1) { state.boats.page--; loadBoats(); }
            else if (state.view === 'protocol' && state.protocol.page > 1) { state.protocol.page--; loadProtocol(); }
        });

        $(document).on('click', '.fstu-next-page-btn', function () {
            if ($(this).prop('disabled')) return;
            if (state.view === 'producers') { state.producers.page++; loadProducers(); }
            else if (state.view === 'boats') { state.boats.page++; loadBoats(); }
            else if (state.view === 'protocol') { state.protocol.page++; loadProtocol(); }
        });

        $(document).on('input', '.fstu-input--in-header', function () {
            clearTimeout(searchTimeout);
            let val = $(this).val(), id = $(this).attr('id');
            searchTimeout = setTimeout(() => {
                if (id === 'fstu-producer-search') { state.producers.search = val; state.producers.page = 1; loadProducers(); }
                else if (id === 'fstu-boat-search') { state.boats.search = val; state.boats.page = 1; loadBoats(); }
                else if (id === 'fstu-protocol-search') { state.protocol.search = val; state.protocol.page = 1; loadProtocol(); }
            }, 300);
        });

        $(document).on('click', '#fstu-typeboat-protocol-btn', function () {
            $('#fstu-producers-section, #fstu-boats-section').addClass('fstu-hidden');
            $('#fstu-protocol-section').removeClass('fstu-hidden');
            state.view = 'protocol';
            updateActionBar();
            loadProtocol();
        });

        $(document).on('click', '#fstu-typeboat-list-btn', function () {
            $('#fstu-protocol-section').addClass('fstu-hidden');
            $('#fstu-producers-section').removeClass('fstu-hidden');
            state.view = 'producers';
            updateActionBar();
            loadProducers();
        });

        $(document).on('click', '.fstu-open-boats', function (e) {
            e.preventDefault();
            $('.fstu-dropdown-menu').addClass('fstu-hidden');
            state.producerId = $(this).data('id');
            state.producerName = $(this).data('name');
            $('#fstu-selected-producer-name').text('Типи суден: ' + state.producerName);
            $('#fstu-producers-section').addClass('fstu-hidden');
            $('#fstu-boats-section').removeClass('fstu-hidden');
            state.view = 'boats';
            updateActionBar();
            loadBoats();
        });

        $(document).on('click', '.fstu-btn--back-to-producers', function () {
            $('#fstu-boats-section').addClass('fstu-hidden');
            $('#fstu-producers-section').removeClass('fstu-hidden');
            state.view = 'producers';
            updateActionBar();
            loadProducers();
        });

        $(document).on('click', '.fstu-trigger-menu', function (e) {
            e.stopPropagation();
            $('.fstu-dropdown-menu').addClass('fstu-hidden');
            let id = $(this).data('id'), type = $(this).data('type'), menu = $(`#menu-${type}-${id}`);
            if (menu.length) {
                let rect = this.getBoundingClientRect();
                menu.css({ position: 'fixed', top: rect.bottom + 'px', left: (rect.left - menu.outerWidth() + rect.width) + 'px', zIndex: 9999 }).removeClass('fstu-hidden');
            }
        });
        $(window).on('scroll click', function () { $('.fstu-dropdown-menu').addClass('fstu-hidden'); });

        $(document).on('click', '#fstu-typeboat-add-producer-btn', function () {
            $('#fstu-producer-form')[0].reset();
            $('#fstu-producer-form [name="id"]').val(0);
            $('#fstu-modal-producer').removeClass('fstu-hidden');
        });

        $(document).on('click', '.fstu-btn--add-boat', function () {
            $('#fstu-boat-form')[0].reset();
            $('#fstu-boat-form [name="id"]').val(0);
            $('#fstu-boat-form [name="ProducerShips_ID"]').val(state.producerId);
            $('#fstu-modal-boat').removeClass('fstu-hidden');
        });

        $(document).on('click', '.fstu-edit-record', function (e) {
            e.preventDefault();
            let type = $(this).data('type'), data = $(this).data('json');

            if (type === 'producer') {
                $('#fstu-producer-form [name="id"]').val(data.ProducerShips_ID);
                $('#fstu-producer-form [name="ProducerShips_Name"]').val(data.ProducerShips_Name);
                $('#fstu-producer-form [name="ProducerShips_Phone"]').val(data.ProducerShips_Phone);
                $('#fstu-producer-form [name="ProducerShips_URL"]').val(data.ProducerShips_URL);
                $('#fstu-producer-form [name="ProducerShips_Adr"]').val(data.ProducerShips_Adr);
                $('#fstu-producer-form [name="ProducerShips_Notes"]').val(data.ProducerShips_Notes);
                $('#fstu-modal-producer').removeClass('fstu-hidden');
            } else {
                $('#fstu-boat-form [name="id"]').val(data.TypeBoat_ID);
                $('#fstu-boat-form [name="ProducerShips_ID"]').val(data.ProducerShips_ID || state.producerId);
                $('#fstu-boat-form [name="TypeBoat_Name"]').val(data.TypeBoat_Name);
                $('#fstu-boat-form [name="TypeBoat_SailArea"]').val(data.TypeBoat_SailArea);
                $('#fstu-boat-form [name="TypeBoat_HillLength"]').val(data.TypeBoat_HillLength);
                $('#fstu-boat-form [name="TypeBoat_WidthOverall"]').val(data.TypeBoat_WidthOverall);
                $('#fstu-boat-form [name="TypeBoat_Weight"]').val(data.TypeBoat_Weight);
                $('#fstu-boat-form [name="TypeBoat_URL"]').val(data.TypeBoat_URL);
                $('#fstu-boat-form [name="TypeBoat_Notes"]').val(data.TypeBoat_Notes);
                $('#fstu-modal-boat').removeClass('fstu-hidden');
            }
        });

        $(document).on('click', '.fstu-modal-close, .fstu-btn--cancel', function () {
            $(this).closest('.fstu-modal').addClass('fstu-hidden');
        });

        $(document).on('click', '.fstu-delete-record', function (e) {
            e.preventDefault();
            if (!confirm(fstuTypeBoat.i18n.deleteConfirm)) return;
            let id = $(this).data('id'), type = $(this).data('type');
            let action = type === 'producer' ? 'fstu_type_boat_delete_producer' : 'fstu_type_boat_delete_boat';

            $.post(fstuTypeBoat.ajaxUrl, { action: action, nonce: fstuTypeBoat.nonce, id: id }, function (res) {
                if (res.success) type === 'producer' ? loadProducers() : loadBoats();
                else alert(res.data.message);
            });
        });

        $('#fstu-producer-form').on('submit', function (e) {
            e.preventDefault();
            let formData = $(this).serialize() + '&action=fstu_type_boat_save_producer&nonce=' + fstuTypeBoat.nonce;
            $.post(fstuTypeBoat.ajaxUrl, formData, function (res) {
                if (res.success) {
                    $('#fstu-modal-producer').addClass('fstu-hidden');
                    loadProducers();
                } else alert(res.data.message);
            });
        });

        $('#fstu-boat-form').on('submit', function (e) {
            e.preventDefault();
            let formData = $(this).serialize() + '&action=fstu_type_boat_save_boat&nonce=' + fstuTypeBoat.nonce;
            $.post(fstuTypeBoat.ajaxUrl, formData, function (res) {
                if (res.success) {
                    $('#fstu-modal-boat').addClass('fstu-hidden');
                    loadBoats();
                } else alert(res.data.message);
            });
        });
    }

    init();
});