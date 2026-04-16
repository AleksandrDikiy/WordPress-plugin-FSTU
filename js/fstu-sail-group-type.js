/**
 * JS: Довідник типів вітрильних залікових груп
 * Version: 1.0.0
 * Date_update: 2026-04-15
 */
jQuery(document).ready(function ($) {
    'use strict';

    if (typeof fstuSailGroupTypeData === 'undefined') return;

    var config = fstuSailGroupTypeData;
    var perms = config.permissions;

    // Стейт таблиць
    var stateTypes = { page: 1, per_page: 10, search: '' };
    var stateGroups = { type_id: 0, type_name: '', page: 1, per_page: 10, search: '' };
    var stateProtocol = { page: 1, per_page: 25, search: '' };
    var searchTimer = null;

    // Ініціалізація
    init();

    function init() {
        if (!perms.canManage) {
            $('#fstu-sgt-btn-add-type').hide();
            $('#fstu-sgt-btn-add-group').hide();
        }
        if (!perms.canProtocol) {
            $('#fstu-sgt-btn-protocol').hide();
        }

        bindEvents();
        loadTypes();
    }

    function bindEvents() {
        // ==========================================
        // Навігація між секціями
        // ==========================================
        $('#fstu-sgt-btn-protocol').on('click', function () {
            hideAllSections();
            $(this).hide();
            $('#fstu-sgt-btn-back-to-types').show();
            $('#fstu-sgt-protocol-section').fadeIn(200);
            loadProtocol();
        });

        $('#fstu-sgt-btn-back-to-types').on('click', function () {
            hideAllSections();
            $(this).hide();
            $('#fstu-sgt-btn-protocol').show();
            if (perms.canManage) $('#fstu-sgt-btn-add-type').show();
            $('#fstu-sgt-types-section').fadeIn(200);
            stateGroups.type_id = 0; // Скидаємо вибраний тип
        });

        // ==========================================
        // Пошук та пагінація: ТИПИ
        // ==========================================
        $('#fstu-sgt-types-search').on('input', function () {
            clearTimeout(searchTimer);
            stateTypes.search = $(this).val();
            stateTypes.page = 1;
            searchTimer = setTimeout(loadTypes, 400);
        });

        $('#fstu-sgt-types-per-page').on('change', function () {
            stateTypes.per_page = parseInt($(this).val(), 10);
            stateTypes.page = 1;
            loadTypes();
        });

        $(document).on('click', '.fstu-sgt-types-page-btn', function () {
            stateTypes.page = parseInt($(this).data('page'), 10);
            loadTypes();
        });

        // ==========================================
        // Пошук та пагінація: ПІДГРУПИ
        // ==========================================
        $('#fstu-sgt-groups-search').on('input', function () {
            clearTimeout(searchTimer);
            stateGroups.search = $(this).val();
            stateGroups.page = 1;
            searchTimer = setTimeout(loadGroups, 400);
        });

        $('#fstu-sgt-groups-per-page').on('change', function () {
            stateGroups.per_page = parseInt($(this).val(), 10);
            stateGroups.page = 1;
            loadGroups();
        });

        $(document).on('click', '.fstu-sgt-groups-page-btn', function () {
            stateGroups.page = parseInt($(this).data('page'), 10);
            loadGroups();
        });

        // ==========================================
        // Пошук та пагінація: ПРОТОКОЛ
        // ==========================================
        $('#fstu-sgt-protocol-search').on('input', function () {
            clearTimeout(searchTimer);
            stateProtocol.search = $(this).val();
            stateProtocol.page = 1;
            searchTimer = setTimeout(loadProtocol, 400);
        });

        $('#fstu-sgt-protocol-per-page').on('change', function () {
            stateProtocol.per_page = parseInt($(this).val(), 10);
            stateProtocol.page = 1;
            loadProtocol();
        });

        $(document).on('click', '.fstu-sgt-protocol-page-btn', function () {
            stateProtocol.page = parseInt($(this).data('page'), 10);
            loadProtocol();
        });

        // ==========================================
        // Модальні вікна та форми
        // ==========================================
        $('#fstu-sgt-btn-add-type').on('click', function () {
            openTypeModal();
        });

        $('#fstu-sgt-btn-add-group').on('click', function () {
            openGroupModal();
        });

        $('.fstu-modal-close, .fstu-modal-close-btn').on('click', function () {
            $('.fstu-modal-overlay').fadeOut(200);
        });

        $('#fstu-sgt-type-form').on('submit', function (e) {
            e.preventDefault();
            saveType();
        });

        $('#fstu-sgt-group-form').on('submit', function (e) {
            e.preventDefault();
            saveGroup();
        });

        // ==========================================
        // Dropdown меню
        // ==========================================
        $(document).on('click', '.fstu-dropdown-toggle', function (e) {
            e.stopPropagation();
            $('.fstu-dynamic-dropdown').remove();

            var $btn = $(this);
            var rect = $btn[0].getBoundingClientRect();
            var type = $btn.data('entity'); // 'type' або 'group'
            var id = $btn.data('id');
            var name = $btn.data('name');

            var menuHtml = '<div class="fstu-dynamic-dropdown">';

            if (type === 'type') {
                menuHtml += '<button type="button" class="fstu-action-view-groups" data-id="'+id+'" data-name="'+escapeHtml(name)+'"><span class="dashicons dashicons-visibility"></span> Переглянути групи</button>';
                if (perms.canManage) {
                    menuHtml += '<button type="button" class="fstu-action-edit-type" data-id="'+id+'" data-name="'+escapeHtml(name)+'"><span class="dashicons dashicons-edit"></span> Редагувати</button>';
                }
                if (perms.canDelete) {
                    menuHtml += '<button type="button" class="fstu-action-delete-type" data-id="'+id+'" style="color:#d9534f;"><span class="dashicons dashicons-trash"></span> Видалити</button>';
                }
            } else if (type === 'group') {
                if (perms.canManage) {
                    // Збираємо дані для редагування
                    var min = $btn.data('min'), max = $btn.data('max'), f = $btn.data('formula'), st = $btn.data('start'), sc = $btn.data('score');
                    menuHtml += '<button type="button" class="fstu-action-edit-group" data-id="'+id+'" data-name="'+escapeHtml(name)+'" data-min="'+escapeHtml(min)+'" data-max="'+escapeHtml(max)+'" data-formula="'+escapeHtml(f)+'" data-start="'+escapeHtml(st)+'" data-score="'+escapeHtml(sc)+'"><span class="dashicons dashicons-edit"></span> Редагувати</button>';
                }
                if (perms.canDelete) {
                    menuHtml += '<button type="button" class="fstu-action-delete-group" data-id="'+id+'" style="color:#d9534f;"><span class="dashicons dashicons-trash"></span> Видалити</button>';
                }
            }
            menuHtml += '</div>';

            var $menu = $(menuHtml).appendTo('body');

            // Динамічне позиціонування
            $menu.css({
                top: rect.bottom + 2 + 'px',
                left: (rect.right - $menu.outerWidth()) + 'px'
            });
        });

        $(document).click(function() {
            $('.fstu-dynamic-dropdown').remove();
        });

        // Дії з меню
        $(document).on('click', '.fstu-action-view-groups', function (e) { // ✅ Додано 'e'
            e.preventDefault(); // ✅ Тепер працює

            stateGroups.type_id = $(this).data('id');
            stateGroups.type_name = $(this).data('name');
            stateGroups.page = 1;

            hideAllSections();
            $('#fstu-sgt-btn-add-type').hide();
            $('#fstu-sgt-btn-protocol').show();
            $('#fstu-sgt-btn-back-to-types').show();
            if (perms.canManage) $('#fstu-sgt-btn-add-group').show();

            $('#fstu-sgt-current-type-name span').text(stateGroups.type_name);
            $('#fstu-sgt-groups-section').fadeIn(200);

            loadGroups();
        });

        $(document).on('click', '.fstu-action-edit-type', function () {
            openTypeModal($(this).data('id'), $(this).data('name'));
        });

        $(document).on('click', '.fstu-action-delete-type', function () {
            if (confirm(config.i18n.confirmDeleteType)) deleteType($(this).data('id'));
        });

        $(document).on('click', '.fstu-action-edit-group', function () {
            var $b = $(this);
            openGroupModal($b.data('id'), $b.data('name'), $b.data('min'), $b.data('max'), $b.data('formula'), $b.data('start'), $b.data('score'));
        });

        $(document).on('click', '.fstu-action-delete-group', function () {
            if (confirm(config.i18n.confirmDeleteGroup)) deleteGroup($(this).data('id'));
        });
    }

    function hideAllSections() {
        $('#fstu-sgt-types-section').hide();
        $('#fstu-sgt-groups-section').hide();
        $('#fstu-sgt-protocol-section').hide();
        $('#fstu-sgt-btn-add-type').hide();
        $('#fstu-sgt-btn-add-group').hide();
    }

    // ==========================================================================
    // AJAX: ТИПИ (S_SailGroupType)
    // ==========================================================================
    function loadTypes() {
        $('#fstu-sgt-types-tbody').html('<tr><td colspan="4" class="fstu-text-center">Завантаження...</td></tr>');

        $.post(config.ajaxUrl, {
            action: 'fstu_sgt_get_types',
            nonce: config.nonce,
            page: stateTypes.page,
            per_page: stateTypes.per_page,
            search: stateTypes.search
        }, function (res) {
            if (res.success) {
                renderTypesTable(res.data.items);
                renderPagination(res.data, 'fstu-sgt-types', 'fstu-sgt-types-page-btn');
                initSortable();
            } else {
                alert(res.data.message || config.i18n.error);
            }
        });
    }

    function renderTypesTable(items) {
        var html = '';
        if (items.length === 0) {
            html = '<tr><td colspan="4" class="fstu-text-center">Записів не знайдено</td></tr>';
        } else {
            var n = (stateTypes.page - 1) * stateTypes.per_page;
            $.each(items, function (i, item) {
                n++;
                html += '<tr data-id="' + item.id + '">';
                html += '<td class="fstu-text-center"><span class="fstu-drag-handle">⋮⋮</span></td>';
                html += '<td class="fstu-text-center">' + n + '</td>';
                // Робимо назву клікабельним посиланням, додавши клас fstu-action-view-groups
                html += '<td><a href="#" class="fstu-action-view-groups fstu-name-link" data-id="' + item.id + '" data-name="' + escapeHtml(item.name) + '">' + escapeHtml(item.name) + '</a></td>';
                html += '<td class="fstu-text-center">';
                html += '<button type="button" class="fstu-dropdown-toggle" data-entity="type" data-id="'+item.id+'" data-name="'+escapeHtml(item.name)+'">▼</button>';
                html += '</td></tr>';
            });
        }
        $('#fstu-sgt-types-tbody').html(html);
    }

    function initSortable() {
        if (!perms.canManage || typeof $.ui === 'undefined') return;

        $('#fstu-sgt-types-tbody').sortable({
            handle: '.fstu-drag-handle',
            axis: 'y',
            helper: function(e, tr) {
                var $originals = tr.children();
                var $helper = tr.clone();
                $helper.children().each(function(index) { $(this).width($originals.eq(index).width()); });
                return $helper;
            },
            update: function (event, ui) {
                var orderData = [];
                $('#fstu-sgt-types-tbody tr').each(function (index) {
                    var id = $(this).data('id');
                    if (id) {
                        var baseOrder = (stateTypes.page - 1) * stateTypes.per_page;
                        orderData.push({ id: id, order: baseOrder + index + 1 });
                    }
                });

                $.post(config.ajaxUrl, {
                    action: 'fstu_sgt_reorder_types',
                    nonce: config.nonce,
                    order_data: orderData
                }, function(res) {
                    if(!res.success) {
                        alert(res.data.message);
                        loadTypes();
                    }
                });
            }
        });
    }

    function saveType() {
        var data = {
            nonce: config.nonce,
            id: $('#fstu-sgt-type-id').val(),
            name: $('#fstu-sgt-type-name').val(),
            fstu_website: $('input[name="fstu_website"]').val()
        };
        data.action = data.id > 0 ? 'fstu_sgt_update_type' : 'fstu_sgt_create_type';

        $('#fstu-sgt-btn-save-type').prop('disabled', true);
        $.post(config.ajaxUrl, data, function (res) {
            $('#fstu-sgt-btn-save-type').prop('disabled', false);
            if (res.success) {
                $('#fstu-sgt-modal-type').fadeOut(200);
                loadTypes();
            } else {
                alert(res.data.message || config.i18n.error);
            }
        });
    }

    function deleteType(id) {
        $.post(config.ajaxUrl, { action: 'fstu_sgt_delete_type', nonce: config.nonce, id: id }, function (res) {
            if (res.success) loadTypes(); else alert(res.data.message || config.i18n.error);
        });
    }

    // ==========================================================================
    // AJAX: ПІДГРУПИ (S_SailGroup)
    // ==========================================================================
    function loadGroups() {
        $('#fstu-sgt-groups-tbody').html('<tr><td colspan="8" class="fstu-text-center">Завантаження...</td></tr>');

        $.post(config.ajaxUrl, {
            action: 'fstu_sgt_get_groups',
            nonce: config.nonce,
            type_id: stateGroups.type_id,
            page: stateGroups.page,
            per_page: stateGroups.per_page,
            search: stateGroups.search
        }, function (res) {
            if (res.success) {
                renderGroupsTable(res.data.items);
                renderPagination(res.data, 'fstu-sgt-groups', 'fstu-sgt-groups-page-btn');
            } else {
                alert(res.data.message || config.i18n.error);
            }
        });
    }

    function renderGroupsTable(items) {
        var html = '';
        if (items.length === 0) {
            html = '<tr><td colspan="8" class="fstu-text-center">Записів не знайдено</td></tr>';
        } else {
            var n = (stateGroups.page - 1) * stateGroups.per_page;
            $.each(items, function (i, item) {
                n++;
                html += '<tr>';
                html += '<td class="fstu-text-center">' + n + '</td>';
                html += '<td>' + escapeHtml(item.name) + '</td>';
                html += '<td class="fstu-text-center">' + escapeHtml(item.code_min) + '</td>';
                html += '<td class="fstu-text-center">' + escapeHtml(item.code_max) + '</td>';
                html += '<td class="fstu-text-center">' + escapeHtml(item.formula) + '</td>';
                html += '<td class="fstu-text-center">' + escapeHtml(item.starting_group) + '</td>';
                html += '<td class="fstu-text-center">' + escapeHtml(item.scoring_system) + '</td>';
                html += '<td class="fstu-text-center">';
                if (perms.canManage) {
                    html += '<button type="button" class="fstu-dropdown-toggle" data-entity="group" data-id="'+item.id+'" data-name="'+escapeHtml(item.name)+'" data-min="'+escapeHtml(item.code_min)+'" data-max="'+escapeHtml(item.code_max)+'" data-formula="'+escapeHtml(item.formula)+'" data-start="'+escapeHtml(item.starting_group)+'" data-score="'+escapeHtml(item.scoring_system)+'">▼</button>';
                } else {
                    html += '—';
                }
                html += '</td></tr>';
            });
        }
        $('#fstu-sgt-groups-tbody').html(html);
    }

    function saveGroup() {
        var data = {
            nonce: config.nonce,
            id: $('#fstu-sgt-group-id').val(),
            type_id: $('#fstu-sgt-parent-type-id').val(),
            name: $('#fstu-sgt-group-name').val(),
            code_min: $('#fstu-sgt-group-code-min').val(),
            code_max: $('#fstu-sgt-group-code-max').val(),
            formula: $('#fstu-sgt-group-formula').val(),
            starting_group: $('#fstu-sgt-group-starting').val(),
            scoring_system: $('#fstu-sgt-group-scoring').val(),
            fstu_website: $('input[name="fstu_website"]').val()
        };
        data.action = data.id > 0 ? 'fstu_sgt_update_group' : 'fstu_sgt_create_group';

        $('#fstu-sgt-btn-save-group').prop('disabled', true);
        $.post(config.ajaxUrl, data, function (res) {
            $('#fstu-sgt-btn-save-group').prop('disabled', false);
            if (res.success) {
                $('#fstu-sgt-modal-group').fadeOut(200);
                loadGroups();
            } else {
                alert(res.data.message || config.i18n.error);
            }
        });
    }

    function deleteGroup(id) {
        $.post(config.ajaxUrl, { action: 'fstu_sgt_delete_group', nonce: config.nonce, id: id }, function (res) {
            if (res.success) loadGroups(); else alert(res.data.message || config.i18n.error);
        });
    }

    // ==========================================================================
    // AJAX: ПРОТОКОЛ
    // ==========================================================================
    function loadProtocol() {
        $('#fstu-sgt-protocol-tbody').html('<tr><td colspan="6" class="fstu-text-center">Завантаження...</td></tr>');

        $.post(config.ajaxUrl, {
            action: 'fstu_sgt_get_protocol',
            nonce: config.nonce,
            page: stateProtocol.page,
            per_page: stateProtocol.per_page,
            search: stateProtocol.search
        }, function (res) {
            if (res.success) {
                renderProtocolTable(res.data.items);
                renderPagination(res.data, 'fstu-sgt-protocol', 'fstu-sgt-protocol-page-btn');
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
        $('#fstu-sgt-protocol-tbody').html(html);
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
    function openTypeModal(id, name) {
        id = id || 0;
        $('#fstu-sgt-type-form')[0].reset();
        $('#fstu-sgt-type-id').val(id);
        $('#fstu-sgt-type-name').val(name || '');
        $('#fstu-sgt-modal-type-title').text(id > 0 ? 'Редагувати тип' : 'Додати тип');
        $('#fstu-sgt-modal-type').fadeIn(200);
    }

    function openGroupModal(id, name, min, max, formula, start, score) {
        id = id || 0;
        $('#fstu-sgt-group-form')[0].reset();
        $('#fstu-sgt-group-id').val(id);
        $('#fstu-sgt-parent-type-id').val(stateGroups.type_id);

        $('#fstu-sgt-group-name').val(name || '');
        $('#fstu-sgt-group-code-min').val(min || '');
        $('#fstu-sgt-group-code-max').val(max || '');
        $('#fstu-sgt-group-formula').val(formula || '');
        $('#fstu-sgt-group-starting').val(start || '');
        $('#fstu-sgt-group-scoring').val(score || '');

        $('#fstu-sgt-modal-group-title').text(id > 0 ? 'Редагувати залікову групу' : 'Додати залікову групу');
        $('#fstu-sgt-modal-group').fadeIn(200);
    }

    function renderPagination(data, prefix, btnClass) {
        var html = '';
        if (data.total_pages > 1) {
            if (data.page > 1) {
                html += '<button type="button" class="fstu-btn fstu-btn--default fstu-btn--page ' + btnClass + '" data-page="1">«</button>';
                html += '<button type="button" class="fstu-btn fstu-btn--default fstu-btn--page ' + btnClass + '" data-page="' + (data.page - 1) + '">‹</button>';
            }
            html += '<span style="padding: 0 10px;">Стор. ' + data.page + ' з ' + data.total_pages + '</span>';
            if (data.page < data.total_pages) {
                html += '<button type="button" class="fstu-btn fstu-btn--default fstu-btn--page ' + btnClass + '" data-page="' + (data.page + 1) + '">›</button>';
                html += '<button type="button" class="fstu-btn fstu-btn--default fstu-btn--page ' + btnClass + '" data-page="' + data.total_pages + '">»</button>';
            }
        }
        $('#' + prefix + '-pagination').html(html);
        $('#' + prefix + '-info').text('Записів: ' + data.total + ' | Сторінка ' + data.page + ' з ' + Math.max(1, data.total_pages));
    }

    function escapeHtml(str) {
        if (!str && str !== 0) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});