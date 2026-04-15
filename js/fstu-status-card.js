/**
 * Клієнтська логіка довідника статусів карток.
 * Version: 1.1.0
 * Date_update: 2026-04-15
 */
jQuery(document).ready(function($) {
    'use strict';

    let currentPage = 1;
    let perPage = 10;
    let searchQuery = '';

    // Ініціалізація
    loadItems();

    // ==========================================
    // AJAX: ЗАВАНТАЖЕННЯ ДАНИХ
    // ==========================================
    function loadItems() {
        $.ajax({
            url: fstuStatusCardData.ajax_url,
            type: 'POST',
            data: {
                action: 'fstu_status_card_get_items',
                nonce: fstuStatusCardData.nonce,
                page: currentPage,
                per_page: perPage,
                search: searchQuery
            },
            success: function(response) {
                if (response.success) {
                    renderTable(response.data.items);
                    renderPagination(response.data.total, response.data.total_pages);
                } else {
                    alert('Помилка: ' + (response.data.message || 'Невідома помилка'));
                }
            }
        });
    }

    function renderTable(items) {
        let html = '';
        if (items.length === 0) {
            html = '<tr><td colspan="3" class="fstu-text-center">Немає даних для відображення</td></tr>';
        } else {
            $.each(items, function(index, item) {
                html += `<tr data-id="${item.StatusCard_ID}" data-order="${item.StatusCard_Order}">
                    <td class="fstu-td-drag fstu-text-center" title="Потягніть для сортування">⋮⋮</td>
                    <td>${escapeHtml(item.StatusCard_Name)}</td>
                    <td class="fstu-text-center">
                        <button type="button" class="fstu-dropdown-toggle" title="Дії" aria-label="Дії" data-id="${item.StatusCard_ID}" data-name="${escapeHtml(item.StatusCard_Name)}">▼</button>
                    </td>
                </tr>`;
            });
        }
        $('#fstu-status-card-tbody').html(html);
        initSortable();
    }

    // ==========================================
    // DRAG AND DROP (СОРТУВАННЯ)
    // ==========================================
    function initSortable() {
        $('#fstu-status-card-tbody').sortable({
            handle: '.fstu-td-drag',
            axis: 'y',
            cursor: 'grabbing',
            update: function(event, ui) {
                let orders = [];
                // Перераховуємо порядок
                $('#fstu-status-card-tbody tr').each(function(index) {
                    let newOrder = (index + 1) * 10;
                    $(this).attr('data-order', newOrder);
                    orders.push({
                        id: $(this).data('id'),
                        order: newOrder
                    });
                });

                // Відправляємо новий порядок на сервер
                $.post(fstuStatusCardData.ajax_url, {
                    action: 'fstu_status_card_reorder_items',
                    nonce: fstuStatusCardData.nonce,
                    orders: orders
                });
            }
        });
    }

    // ==========================================
    // DROPDOWN МЕНЮ (POSITION: FIXED)
    // ==========================================
    $(document).on('click', '.fstu-dropdown-toggle', function(e) {
        e.stopPropagation();
        $('.fstu-dropdown-menu').remove(); // Закриваємо інші меню

        let $btn = $(this);
        let id = $btn.data('id');
        let name = $btn.data('name');
        let rect = this.getBoundingClientRect();

        let menuHtml = `
            <ul class="fstu-dropdown-menu">
                <li><button type="button" class="fstu-action-edit" data-id="${id}" data-name="${name}">✏️ Редагувати</button></li>
                <li><button type="button" class="fstu-action-delete" data-id="${id}">🗑️ Видалити</button></li>
            </ul>
        `;

        let $menu = $(menuHtml).appendTo('body');

        // Вираховуємо позицію (запобігання виходу за нижній край)
        let topPos = rect.bottom;
        if (topPos + $menu.outerHeight() > $(window).height()) {
            topPos = rect.top - $menu.outerHeight(); // Відкриваємо вгору
        }

        $menu.css({
            top: topPos + 'px',
            left: rect.left + 'px',
            position: 'fixed',
            zIndex: 99999
        });
    });

    // Закриття меню при скролі або кліку поза ним
    $(window).on('scroll blur resize', function() { $('.fstu-dropdown-menu').remove(); });
    $(document).on('click', function() { $('.fstu-dropdown-menu').remove(); });

    // ==========================================
    // МОДАЛЬНІ ВІКНА ТА ФОРМИ
    // ==========================================
    $('#fstu-btn-add').on('click', function() {
        $('#fstu-modal-title').text('Додати статус');
        $('#fstu-status-card-id').val('0');
        $('#fstu-status-card-name').val('');
        $('#fstu-status-card-modal').fadeIn(200);
    });

    $(document).on('click', '.fstu-action-edit', function() {
        $('#fstu-modal-title').text('Редагувати статус');
        $('#fstu-status-card-id').val($(this).data('id'));
        $('#fstu-status-card-name').val($(this).data('name'));
        $('#fstu-status-card-modal').fadeIn(200);
    });

    $('.fstu-modal-close, .fstu-modal-close-btn').on('click', function() {
        $('#fstu-status-card-modal').fadeOut(200);
    });

    $('#fstu-status-card-form').on('submit', function(e) {
        e.preventDefault();
        let $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Збереження...');

        $.ajax({
            url: fstuStatusCardData.ajax_url,
            type: 'POST',
            data: $(this).serialize() + '&action=fstu_status_card_save_item&nonce=' + fstuStatusCardData.nonce,
            success: function(response) {
                if (response.success) {
                    $('#fstu-status-card-modal').fadeOut(200);
                    loadItems();
                } else {
                    alert('Помилка: ' + (response.data.message || 'Невідома помилка'));
                }
            },
            complete: function() { $btn.prop('disabled', false).text('Зберегти'); }
        });
    });

    // ==========================================
    // ВИДАЛЕННЯ
    // ==========================================
    $(document).on('click', '.fstu-action-delete', function() {
        let id = $(this).data('id');
        if (!confirm('Ви дійсно хочете видалити цей запис?')) return;

        $.post(fstuStatusCardData.ajax_url, {
            action: 'fstu_status_card_delete_item',
            nonce: fstuStatusCardData.nonce,
            id: id
        }, function(response) {
            if (response.success) {
                loadItems();
            } else {
                alert('Помилка: ' + response.data.message);
            }
        });
    });

    // ==========================================
    // ПОШУК ТА ПАГІНАЦІЯ
    // ==========================================
    let searchTimeout;
    $('#fstu-status-card-search').on('input', function() {
        clearTimeout(searchTimeout);
        searchQuery = $(this).val();
        searchTimeout = setTimeout(function() {
            currentPage = 1;
            loadItems();
        }, 500);
    });

    $('#fstu-status-card-per-page').on('change', function() {
        perPage = $(this).val();
        currentPage = 1;
        loadItems();
    });

    // ==========================================
    // ПЕРЕМИКАННЯ ПРОТОКОЛ / ДОВІДНИК
    // ==========================================
    let protocolCurrentPage = 1;
    let protocolPerPage = 10;
    let protocolSearchQuery = '';
    let protocolSearchTimeout;

    $('#fstu-btn-protocol').on('click', function() {
        $('#fstu-dictionary-container, #fstu-btn-add, #fstu-btn-protocol').hide();
        $('#fstu-protocol-container, #fstu-btn-dictionary').show();
        loadProtocol();
    });

    $('#fstu-btn-dictionary').on('click', function() {
        $('#fstu-protocol-container, #fstu-btn-dictionary').hide();
        $('#fstu-dictionary-container, #fstu-btn-add, #fstu-btn-protocol').show();
    });

    function loadProtocol() {
        $.ajax({
            url: fstuStatusCardData.ajax_url,
            type: 'POST',
            data: {
                action: 'fstu_status_card_get_protocol',
                nonce: fstuStatusCardData.nonce,
                page: protocolCurrentPage,
                per_page: protocolPerPage,
                search: protocolSearchQuery
            },
            success: function(response) {
                if (response.success) {
                    renderProtocolTable(response.data.items);
                    $('#fstu-status-card-protocol-info').text(`Записів: ${response.data.total} | Сторінка ${protocolCurrentPage} з ${response.data.total_pages || 1}`);
                }
            }
        });
    }

    function renderProtocolTable(items) {
        let html = '';
        if (items.length === 0) {
            html = '<tr><td colspan="5" class="fstu-text-center">Немає записів у протоколі</td></tr>';
        } else {
            $.each(items, function(index, item) {
                html += `<tr>
                    <td>${escapeHtml(item.Logs_DateCreate)}</td>
                    <td class="fstu-text-center">${buildTypeBadge(item.Logs_Type)}</td>
                    <td>${escapeHtml(item.Logs_Text)}</td>
                    <td class="fstu-text-center">${escapeHtml(item.Logs_Error)}</td>
                    <td>${escapeHtml(item.FIO || '—')}</td>
                </tr>`;
            });
        }
        $('#fstu-status-card-protocol-tbody').html(html);
    }

    function buildTypeBadge( type ) {
        var cls = 'fstu-badge--default';
        var label = type || '—';
        if ( type === 'INSERT' || type === 'I' ) { cls = 'fstu-badge--insert'; label = 'INSERT'; }
        if ( type === 'UPDATE' || type === 'U' ) { cls = 'fstu-badge--update'; label = 'UPDATE'; }
        if ( type === 'DELETE' || type === 'D' ) { cls = 'fstu-badge--delete'; label = 'DELETE'; }
        return '<span style="padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; background: #e9ecef; color: #495057;">' + escapeHtml( label ) + '</span>';
    }

    $('#fstu-protocol-filter-text').on('input', function() {
        clearTimeout(protocolSearchTimeout);
        protocolSearchQuery = $(this).val();
        protocolSearchTimeout = setTimeout(function() {
            protocolCurrentPage = 1;
            loadProtocol();
        }, 500);
    });

    $('#fstu-status-card-protocol-per-page').on('change', function() {
        protocolPerPage = $(this).val();
        protocolCurrentPage = 1;
        loadProtocol();
    });

    // Хелпер
    function escapeHtml(unsafe) {
        return (unsafe || '').toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Stub для пагінації (генерує кнопки)
    function renderPagination(total, totalPages) {
        $('#fstu-status-card-info').text(`Записів: ${total} | Сторінка ${currentPage} з ${totalPages}`);
        // Логіка генерації кнопок пагінації...
    }
});