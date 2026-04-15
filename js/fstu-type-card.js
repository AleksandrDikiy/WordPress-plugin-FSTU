/**
 * JS: Довідник типів членських білетів
 * Version: 1.0.0
 * Date_update: 2026-04-15
 */
jQuery(document).ready(function ($) {
    'use strict';

    if (typeof fstuTypeCardData === 'undefined') return;

    var config = fstuTypeCardData;
    var perms = config.permissions;

    // Стейт таблиці довідника
    var state = { page: 1, per_page: 10, search: '' };
    // Стейт таблиці протоколу
    var protoState = { page: 1, per_page: 25, search: '' };
    var searchTimer = null;

    // Ініціалізація
    init();

    function init() {
        if (!perms.canManage) {
            $('#fstu-type-card-btn-add').hide();
        }
        if (!perms.canProtocol) {
            $('#fstu-type-card-btn-protocol').hide();
        }

        bindEvents();
        loadItems();
    }

    function bindEvents() {
        // Дебаунс пошуку для довідника
        $('#fstu-type-card-search').on('input', function () {
            clearTimeout(searchTimer);
            state.search = $(this).val();
            state.page = 1;
            searchTimer = setTimeout(loadItems, 400);
        });

        // Пагінація довідника
        $('#fstu-type-card-per-page').on('change', function () {
            state.per_page = parseInt($(this).val(), 10);
            state.page = 1;
            loadItems();
        });

        $(document).on('click', '.fstu-type-card-page-btn', function () {
            state.page = parseInt($(this).data('page'), 10);
            loadItems();
        });

        // Перемикання секцій
        $('#fstu-type-card-btn-protocol').on('click', function () {
            $('#fstu-type-card-dictionary-section').hide();
            $(this).hide();
            $('#fstu-type-card-btn-add').hide();
            $('#fstu-type-card-btn-back').show();
            $('#fstu-type-card-protocol-section').fadeIn(200);
            loadProtocol();
        });

        $('#fstu-type-card-btn-back').on('click', function () {
            $('#fstu-type-card-protocol-section').hide();
            $(this).hide();
            $('#fstu-type-card-btn-protocol').show();
            if (perms.canManage) $('#fstu-type-card-btn-add').show();
            $('#fstu-type-card-dictionary-section').fadeIn(200);
        });

        // Протокол пошук та пагінація
        $('#fstu-type-card-protocol-search').on('input', function () {
            clearTimeout(searchTimer);
            protoState.search = $(this).val();
            protoState.page = 1;
            searchTimer = setTimeout(loadProtocol, 400);
        });

        $('#fstu-type-card-protocol-per-page').on('change', function () {
            protoState.per_page = parseInt($(this).val(), 10);
            protoState.page = 1;
            loadProtocol();
        });

        $(document).on('click', '.fstu-type-card-proto-page-btn', function () {
            protoState.page = parseInt($(this).data('page'), 10);
            loadProtocol();
        });

        // Модальне вікно (Додавання/Редагування)
        $('#fstu-type-card-btn-add').on('click', function () {
            openModal();
        });

        $('.fstu-modal-close, .fstu-modal-close-btn').on('click', function () {
            closeModal();
        });

        $('#fstu-type-card-form').on('submit', function (e) {
            e.preventDefault();
            saveItem();
        });

        // Динамічний Dropdown
        $(document).on('click', '.fstu-dropdown-toggle', function (e) {
            e.stopPropagation();
            $('.fstu-dynamic-dropdown').remove();

            var $btn = $(this);
            var id = $btn.data('id');
            var name = $btn.data('name');
            var summa = $btn.data('summa');
            var rect = $btn[0].getBoundingClientRect();

            var menuHtml = '<div class="fstu-dynamic-dropdown">';
            if (perms.canManage) {
                menuHtml += '<button type="button" class="fstu-action-edit" data-id="'+id+'" data-name="'+escapeHtml(name)+'" data-summa="'+summa+'"><span class="dashicons dashicons-edit"></span> Редагувати</button>';
            }
            if (perms.canDelete) {
                menuHtml += '<button type="button" class="fstu-action-delete" data-id="'+id+'" style="color:#d9534f;"><span class="dashicons dashicons-trash"></span> Видалити</button>';
            }
            menuHtml += '</div>';

            var $menu = $(menuHtml).appendTo('body');

            // Позиціонування Fixed
            $menu.css({
                top: rect.bottom + 2 + 'px',
                left: (rect.right - $menu.outerWidth()) + 'px'
            });
        });

        $(document).click(function() {
            $('.fstu-dynamic-dropdown').remove();
        });

        // Дії з Dropdown
        $(document).on('click', '.fstu-action-edit', function () {
            var id = $(this).data('id');
            var name = $(this).data('name');
            var summa = $(this).data('summa');
            openModal(id, name, summa);
        });

        $(document).on('click', '.fstu-action-delete', function () {
            if (confirm(config.i18n.confirmDelete)) {
                deleteItem($(this).data('id'));
            }
        });
    }

    // ==========================================================================
    // AJAX Довідник
    // ==========================================================================
    function loadItems() {
        $('#fstu-type-card-tbody').html('<tr><td colspan="5" class="fstu-text-center">Завантаження...</td></tr>');

        $.post(config.ajaxUrl, {
            action: 'fstu_type_card_get_items',
            nonce: config.nonce,
            page: state.page,
            per_page: state.per_page,
            search: state.search
        }, function (res) {
            if (res.success) {
                renderTable(res.data.items);
                renderPagination(res.data, 'fstu-type-card');
                initSortable();
            } else {
                alert(res.data.message || config.i18n.error);
            }
        });
    }

    function renderTable(items) {
        var html = '';
        if (items.length === 0) {
            html = '<tr><td colspan="5" class="fstu-text-center">Записів не знайдено</td></tr>';
        } else {
            var n = (state.page - 1) * state.per_page;
            $.each(items, function (i, item) {
                n++;
                var formattedSumma = parseFloat(item.summa).toFixed(2) + ' ₴'; // Форматування валюти

                html += '<tr data-id="' + item.id + '">';
                html += '<td class="fstu-text-center"><span class="fstu-drag-handle">⋮⋮</span></td>';
                html += '<td class="fstu-text-center">' + n + '</td>';
                html += '<td>' + escapeHtml(item.name) + '</td>';
                html += '<td class="fstu-text-right">' + formattedSumma + '</td>';
                html += '<td class="fstu-text-center">';

                if (perms.canManage || perms.canDelete) {
                    html += '<button type="button" class="fstu-dropdown-toggle" title="Дії" data-id="'+item.id+'" data-name="'+escapeHtml(item.name)+'" data-summa="'+item.summa+'">▼</button>';
                } else {
                    html += '—';
                }
                html += '</td></tr>';
            });
        }
        $('#fstu-type-card-tbody').html(html);
    }

    // ==========================================================================
    // Drag & Drop (Sortable)
    // ==========================================================================
    function initSortable() {
        if (!perms.canManage || typeof $.ui === 'undefined') return;

        $('#fstu-type-card-tbody').sortable({
            handle: '.fstu-drag-handle',
            axis: 'y',
            helper: function(e, tr) {
                var $originals = tr.children();
                var $helper = tr.clone();
                $helper.children().each(function(index) {
                    $(this).width($originals.eq(index).width());
                });
                return $helper;
            },
            update: function (event, ui) {
                var orderData = [];
                $('#fstu-type-card-tbody tr').each(function (index) {
                    var id = $(this).data('id');
                    if (id) {
                        // Рахуємо порядок: (сторінка - 1) * per_page + index
                        var baseOrder = (state.page - 1) * state.per_page;
                        orderData.push({ id: id, order: baseOrder + index + 1 });
                    }
                });

                $.post(config.ajaxUrl, {
                    action: 'fstu_type_card_reorder',
                    nonce: config.nonce,
                    order_data: orderData
                }, function(res) {
                    if(!res.success) {
                        alert(res.data.message);
                        loadItems(); // rollback візуально
                    }
                });
            }
        });
    }

    // ==========================================================================
    // CRUD Операції
    // ==========================================================================
    function saveItem() {
        var data = {
            nonce: config.nonce,
            id: $('#fstu-tc-id').val(),
            name: $('#fstu-tc-name').val(),
            summa: $('#fstu-tc-summa').val(),
            fstu_website: $('input[name="fstu_website"]').val() // Honeypot
        };

        var actionName = data.id > 0 ? 'fstu_type_card_update' : 'fstu_type_card_create';
        data.action = actionName;

        $('#fstu-type-card-btn-save').prop('disabled', true).text('Збереження...');

        $.post(config.ajaxUrl, data, function (res) {
            $('#fstu-type-card-btn-save').prop('disabled', false).text('Зберегти');
            if (res.success) {
                closeModal();
                loadItems();
            } else {
                alert(res.data.message || config.i18n.error);
            }
        });
    }

    function deleteItem(id) {
        $.post(config.ajaxUrl, {
            action: 'fstu_type_card_delete',
            nonce: config.nonce,
            id: id
        }, function (res) {
            if (res.success) {
                loadItems();
            } else {
                alert(res.data.message || config.i18n.error);
            }
        });
    }

    // ==========================================================================
    // AJAX Протокол
    // ==========================================================================
    function loadProtocol() {
        $('#fstu-type-card-protocol-tbody').html('<tr><td colspan="6" class="fstu-text-center">Завантаження...</td></tr>');

        $.post(config.ajaxUrl, {
            action: 'fstu_type_card_get_protocol',
            nonce: config.nonce,
            page: protoState.page,
            per_page: protoState.per_page,
            search: protoState.search
        }, function (res) {
            if (res.success) {
                renderProtocolTable(res.data.items);
                renderPagination(res.data, 'fstu-type-card-protocol');
            } else {
                alert(res.data.message || config.i18n.error);
            }
        });
    }

    function renderProtocolTable(items) {
        var html = '';
        if (items.length === 0) {
            html = '<tr><td colspan="6" class="fstu-text-center">Логів не знайдено</td></tr>';
        } else {
            $.each(items, function (i, item) {
                html += '<tr>';
                html += '<td>' + escapeHtml(item.Logs_DateCreate) + '</td>';
                html += '<td class="fstu-text-center">' + buildTypeBadge(item.Logs_Type) + '</td>';
                html += '<td>' + escapeHtml(item.Logs_Name) + '</td>';
                html += '<td>' + escapeHtml(item.Logs_Text) + '</td>';
                html += '<td class="fstu-text-center">' + escapeHtml(item.Logs_Error) + '</td>';
                html += '<td>' + escapeHtml(item.FIO || 'Система') + '</td>';
                html += '</tr>';
            });
        }
        $('#fstu-type-card-protocol-tbody').html(html);
    }

    function buildTypeBadge(type) {
        var cls = 'fstu-badge--default';
        var label = type || '—';
        if (type === 'I') { cls = 'fstu-badge--insert'; label = 'INSERT'; }
        if (type === 'U') { cls = 'fstu-badge--update'; label = 'UPDATE'; }
        if (type === 'D') { cls = 'fstu-badge--delete'; label = 'DELETE'; }
        return '<span class="fstu-badge ' + cls + '">' + label + '</span>';
    }

    // ==========================================================================
    // Утиліти
    // ==========================================================================
    function openModal(id, name, summa) {
        id = id || 0;
        $('#fstu-tc-id').val(id);
        $('#fstu-tc-name').val(name || '');
        $('#fstu-tc-summa').val(summa !== undefined ? summa : 0);

        $('#fstu-type-card-modal-title').text(id > 0 ? 'Редагувати запис' : 'Додати запис');
        $('#fstu-type-card-modal').fadeIn(200);
    }

    function closeModal() {
        $('#fstu-type-card-modal').fadeOut(200);
        $('#fstu-type-card-form')[0].reset();
    }

    function renderPagination(data, prefix) {
        var html = '';
        if (data.total_pages > 1) {
            if (data.page > 1) {
                html += '<button type="button" class="fstu-btn fstu-btn--default fstu-btn--page ' + prefix + '-page-btn" data-page="1">«</button>';
                html += '<button type="button" class="fstu-btn fstu-btn--default fstu-btn--page ' + prefix + '-page-btn" data-page="' + (data.page - 1) + '">‹</button>';
            }
            html += '<span style="padding: 0 10px;">Стор. ' + data.page + ' з ' + data.total_pages + '</span>';
            if (data.page < data.total_pages) {
                html += '<button type="button" class="fstu-btn fstu-btn--default fstu-btn--page ' + prefix + '-page-btn" data-page="' + (data.page + 1) + '">›</button>';
                html += '<button type="button" class="fstu-btn fstu-btn--default fstu-btn--page ' + prefix + '-page-btn" data-page="' + data.total_pages + '">»</button>';
            }
        }
        $('#' + prefix + '-pagination').html(html);
        $('#' + prefix + '-info').text('Записів: ' + data.total + ' | Сторінка ' + data.page + ' з ' + Math.max(1, data.total_pages));
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});