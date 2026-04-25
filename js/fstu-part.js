jQuery(document).ready(function($) {
    let currentPage = 1;
    let currentProtocolPage = 1;

    // --- 1. ЗАВАНТАЖЕННЯ ДАНИХ (ДОВІДНИК) ---
    function loadItems() {
        const perPage = $('#fstu-part-per-page').val() || 10;
        const search  = $('#fstu-part-search').val() || '';
        const year    = $('#fstu-part-year-filter').val() || 0;

        $.post(fstuPartData.ajax_url, {
            action: 'fstu_part_get_items',
            nonce: fstuPartData.nonce,
            page: currentPage,
            per_page: perPage,
            search: search,
            year: year
        }, function(response) {
            if (response.success) {
                renderTable(response.data.items);
                renderKPI(response.data.kpi);
                renderPagination(response.data.page, response.data.total_pages, '#fstu-part-pagination', 'main');
                $('#fstu-part-info').text(`Записів: ${response.data.total} | Сторінка ${response.data.page} з ${response.data.total_pages || 1}`);
            }
        });
    }

    function renderTable(items) {
        let html = '';
        if(items.length === 0) {
            html = '<tr><td colspan="7" class="fstu-text-center">Немає даних</td></tr>';
        } else {
            items.forEach((item, index) => {
                let trackLink = item.Part_URL ? `<a href="${item.Part_URL}" target="_blank" class="fstu-link">🔗 Трек</a>` : '-';
                let partId    = item.Part_ID || 0;
                let statusCls = partId > 0 ? 'fstu-text-success' : 'fstu-text-muted';

                html += `<tr>
                    <td class="fstu-text-center">${index + 1}</td>
                    <td>${item.DateBegin}</td>
                    <td><b>${item.Calendar_Name}</b> <span class="${statusCls}" style="font-size:11px;">(${partId > 0 ? 'Внесено' : 'Не внесено'})</span></td>
                    <td>${item.RoleName || '-'}</td>
                    <td>${item.Part_Distance || '0'} км</td>
                    <td>${trackLink}</td>
                    <td>
                        <button type="button" class="fstu-dropdown-toggle fstu-btn-actions" title="Дії">▼</button>
                        <div class="fstu-dropdown-menu" style="display:none; background:#fff; border:1px solid #e2e8f0; padding:4px; border-radius:6px; box-shadow:0 4px 6px rgba(0,0,0,0.1); min-width:140px;">
                            <button type="button" class="fstu-dropdown-item btn-edit-part" 
                                data-id="${partId}" data-cal="${item.Calendar_ID}" data-cal-name="${item.Calendar_Name}" 
                                data-dist="${item.Part_Distance || ''}" data-max="${item.Part_MaxSpeed || ''}" 
                                data-avg="${item.Part_AverageSpeed || ''}" data-url="${item.Part_URL || ''}" 
                                data-note="${item.Part_Note || ''}">
                                ${partId > 0 ? '✏️ Редагувати' : '➕ Додати'}
                            </button>
                            ${partId > 0 ? `<div style="height:1px; background:#e2e8f0; margin:4px 0;"></div><button type="button" class="fstu-dropdown-item fstu-text-danger btn-delete-part" data-id="${partId}">🗑 Видалити</button>` : ''}
                        </div>
                    </td>
                </tr>`;
            });
        }
        $('#fstu-part-tbody').html(html);
    }

    function renderKPI(kpi) {
        $('#kpi-events').text(kpi.TotalEvents || 0);
        $('#kpi-distance').text(parseFloat(kpi.TotalDistance || 0).toFixed(2));
        $('#kpi-speed').html(parseFloat(kpi.MaxSpeed || 0).toFixed(2) + ' <small>км/год</small>');
    }

    // --- 2. ВЗАЄМОДІЯ З UI ТА ФІЛЬТРАМИ ---
    $('#fstu-part-year-filter, #fstu-part-per-page').on('change', function() { currentPage = 1; loadItems(); });

    let typingTimer;
    $('#fstu-part-search').on('keyup', function() {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(function() { currentPage = 1; loadItems(); }, 500);
    });

    // Динамічне Dropdown меню (Уникнення обрізання overflow)
    $(document).on('click', '.fstu-dropdown-toggle', function(e) {
        e.stopPropagation();
        $('.fstu-dropdown-menu').hide();
        const menu = $(this).next('.fstu-dropdown-menu');
        const rect = this.getBoundingClientRect();
        menu.css({ position: 'fixed', top: rect.bottom + 'px', left: rect.left - 80 + 'px', zIndex: 9999 }).show();
    });
    $(document).on('click', function() { $('.fstu-dropdown-menu').hide(); });
    $(window).on('scroll', function() { $('.fstu-dropdown-menu').hide(); });

    // --- 3. МОДАЛКА (РЕДАГУВАННЯ / ДОДАВАННЯ) ---
    $(document).on('click', '.btn-edit-part', function() {
        $('#fstu-part-form')[0].reset();
        $('#fstu-part-id').val($(this).data('id'));
        $('#fstu-part-calendar-id').val($(this).data('cal'));
        $('#fstu-part-calendar-name').text($(this).data('cal-name'));

        $('#fstu-part-distance').val($(this).data('dist'));
        $('#fstu-part-maxspeed').val($(this).data('max'));
        $('#fstu-part-avgspeed').val($(this).data('avg'));
        $('#fstu-part-url').val($(this).data('url'));
        $('#fstu-part-note').val($(this).data('note'));

        $('#fstu-part-modal').show();
    });

    $('[data-micromodal-close]').on('click', function(e) {
        e.preventDefault(); $('#fstu-part-modal').hide();
    });

    // --- 4. ЗБЕРЕЖЕННЯ ТА ВИДАЛЕННЯ ---
    $('#fstu-part-form').on('submit', function(e) {
        e.preventDefault();

        // Honeypot перевірка
        if ($('input[name="fstu_website"]').val() !== '') return false;

        const btn = $(this).find('.fstu-btn--save');
        btn.prop('disabled', true).text('Збереження...');

        const formData = {};
        $(this).serializeArray().forEach(item => formData[item.name] = item.value);

        $.post(fstuPartData.ajax_url, {
            action: 'fstu_part_save_item',
            nonce: fstuPartData.nonce,
            data: formData
        }, function(response) {
            btn.prop('disabled', false).text('Зберегти');
            if (response.success) {
                $('#fstu-part-modal').hide();
                loadItems();
            } else {
                alert(response.data.message);
            }
        });
    });

    $(document).on('click', '.btn-delete-part', function() {
        if(!confirm('Ви дійсно хочете видалити цю статистику?')) return;
        const partId = $(this).data('id');

        $.post(fstuPartData.ajax_url, {
            action: 'fstu_part_delete_item',
            nonce: fstuPartData.nonce,
            part_id: partId
        }, function(response) {
            if(response.success) loadItems(); else alert(response.data.message);
        });
    });

    // --- 5. ПРОТОКОЛ ТА ПАГІНАЦІЯ ---
    function renderPagination(page, totalPages, targetId, context) {
        if (totalPages <= 1) {
            $(targetId).html('');
            return;
        }

        let html = '';

        // Кнопка "Назад" («)
        if (page > 1) {
            html += `<button type="button" class="fstu-btn--page fstu-page-btn" data-page="${page - 1}" data-context="${context}">&laquo;</button>`;
        }

        // Обчислення діапазону видимих кнопок
        let start = Math.max(1, page - 1);
        let end = Math.min(totalPages, page + 1);

        if (page <= 3) {
            end = Math.min(totalPages, 4);
        }
        if (page >= totalPages - 2) {
            start = Math.max(1, totalPages - 3);
        }

        // Перша сторінка та "..."
        if (start > 1) {
            html += `<button type="button" class="fstu-btn--page fstu-page-btn" data-page="1" data-context="${context}">1</button>`;
            if (start > 2) {
                html += `<span class="fstu-pagination-ellipsis">...</span>`;
            }
        }

        // Основні сторінки (поточна та сусідні)
        for (let i = start; i <= end; i++) {
            let activeClass = (i === page) ? 'fstu-btn--page-active' : '';
            html += `<button type="button" class="fstu-btn--page fstu-page-btn ${activeClass}" data-page="${i}" data-context="${context}">${i}</button>`;
        }

        // Остання сторінка та "..."
        if (end < totalPages) {
            if (end < totalPages - 1) {
                html += `<span class="fstu-pagination-ellipsis">...</span>`;
            }
            html += `<button type="button" class="fstu-btn--page fstu-page-btn" data-page="${totalPages}" data-context="${context}">${totalPages}</button>`;
        }

        // Кнопка "Вперед" (»)
        if (page < totalPages) {
            html += `<button type="button" class="fstu-btn--page fstu-page-btn" data-page="${page + 1}" data-context="${context}">&raquo;</button>`;
        }

        $(targetId).html(html);
    }

    $(document).on('click', '.fstu-page-btn', function() {
        let p = $(this).data('page');
        if($(this).data('context') === 'main') { currentPage = p; loadItems(); }
        else { currentProtocolPage = p; loadProtocol(); }
    });

    $('#fstu-btn-protocol').on('click', function() {
        $('#fstu-part-main-view, .fstu-kpi-dashboard, .fstu-action-bar__left').hide();
        $('#fstu-part-protocol-view, #fstu-btn-back-to-list').show();
        $(this).hide();
        currentProtocolPage = 1;
        loadProtocol();
    });

    $('#fstu-btn-back-to-list').on('click', function() {
        $('#fstu-part-protocol-view').hide();
        $('#fstu-part-main-view, .fstu-kpi-dashboard, .fstu-action-bar__left, #fstu-btn-protocol').show();
        $(this).hide();
        loadItems();
    });

    $('#fstu-part-protocol-per-page').on('change', function() { currentProtocolPage = 1; loadProtocol(); });
    $('#fstu-part-protocol-search').on('keyup', function() {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(function() { currentProtocolPage = 1; loadProtocol(); }, 500);
    });

    function loadProtocol() {
        $.post(fstuPartData.ajax_url, {
            action: 'fstu_part_get_protocol',
            nonce: fstuPartData.nonce,
            page: currentProtocolPage,
            per_page: $('#fstu-part-protocol-per-page').val() || 10,
            search: $('#fstu-part-protocol-search').val() || ''
        }, function(response) {
            if (response.success) {
                let html = '';
                if(response.data.items.length === 0) {
                    html = '<tr><td colspan="6" class="fstu-text-center">Немає записів</td></tr>';
                } else {
                    response.data.items.forEach(log => {
                        let typeLabel = log.Logs_Type === 'I' ? 'СТВОРЕНО' : (log.Logs_Type === 'U' ? 'ОНОВЛЕНО' : 'ВИДАЛЕНО');
                        html += `<tr>
                            <td>${log.Logs_DateCreate}</td>
                            <td class="fstu-text-center"><b>${typeLabel}</b></td>
                            <td>${log.Logs_Name}</td>
                            <td>${log.Logs_Text}</td>
                            <td class="fstu-text-center">${log.Logs_Error}</td>
                            <td>${log.FIO || '-'}</td>
                        </tr>`;
                    });
                }
                $('#fstu-part-protocol-tbody').html(html);
                renderPagination(response.data.page, response.data.total_pages, '#fstu-part-protocol-pagination', 'protocol');
                $('#fstu-part-protocol-info').text(`Записів: ${response.data.total} | Сторінка ${response.data.page} з ${response.data.total_pages || 1}`);
            }
        });
    }

    // Запуск
    loadItems();
});