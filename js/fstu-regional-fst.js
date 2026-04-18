/**
 * Клієнтська логіка модуля "Осередки федерації спортивного туризму"
 * Version: 1.0.0
 * Date_update: 2026-04-18
 */
jQuery(document).ready(function($) {
    var state = {
        regionId: 0,
        unitId: 0,
        search: '',
        page: 1,
        perPage: 10
    };

    // 1. ІНТЕРАКТИВНА КАРТА
    $('.fstu-region-path').on('mouseenter', function(e) {
        var title = $(this).attr('title');
        $('#fstu-map-tooltip').text(title).css({
            display: 'block',
            left: e.pageX + 10,
            top: e.pageY + 10
        });
    }).on('mouseleave', function() {
        $('#fstu-map-tooltip').hide();
    }).on('mousemove', function(e) {
        $('#fstu-map-tooltip').css({
            left: e.pageX + 10,
            top: e.pageY + 10
        });
    });

    $('.fstu-region-path').on('click', function() {
        $('.fstu-region-path').removeClass('is-active');
        $(this).addClass('is-active');

        var regionName = $(this).attr('title');
        state.regionId = $(this).data('region-id');
        state.page = 1;

        // Показуємо таблицю та Action Bar
        $('#fstu-regional-current-title').text('Регіон: ' + regionName);
        $('#fstu-regional-fst-table-section').show();
        $('#fstu-regional-fst-action-bar').css('display', 'flex');

        // Керування видимістю кнопок відповідно до прав
        if (fstuRegionalFST.permissions.canManage) {
            $('#fstu-btn-add').show();
        }
        if (fstuRegionalFST.permissions.canProtocol) {
            $('#fstu-btn-protocol').show();
        }

        // Лінк для реєстру внесків
        // Передаємо ID регіону, по якому клікнули на карті
        var duesUrl = fstuRegionalFST.paymentUrl + '?region_id=' + state.regionId;
        $('#fstu-btn-dues').attr('href', duesUrl).show();

        loadTable();
    });

    // 2. ЗАВАНТАЖЕННЯ ТАБЛИЦІ (AJAX)
    function loadTable() {
        if (!state.regionId) return;

        $.post(fstuRegionalFST.ajaxUrl, {
            action: 'fstu_get_regional_fst',
            nonce: fstuRegionalFST.nonce,
            region_id: state.regionId,
            search: state.search,
            page: state.page,
            per_page: state.perPage
        }, function(response) {
            if (response.success) {
                renderTable(response.data);
            }
        });
    }

    function renderTable(data) {
        var tbody = $('#fstu-regional-tbody');
        tbody.empty();

        if (data.items.length === 0) {
            tbody.append('<tr><td colspan="7" style="text-align:center;">Записів не знайдено</td></tr>');
            $('#fstu-regional-pagination-wrap').hide();
            return;
        }

        var offset = (data.page - 1) * data.per_page;

        $.each(data.items, function(index, item) {
            var num = offset + index + 1;

            var actionsHtml = '';
            if (fstuRegionalFST.permissions.canManage || fstuRegionalFST.permissions.canDelete) {
                actionsHtml = '<button type="button" class="fstu-dropdown-toggle" data-id="' + item.RegionalFST_ID + '">▼</button>';
                // Dropdown menu added dynamically to body to prevent clipping
            } else {
                actionsHtml = '—';
            }

            var tr = $('<tr>').append(
                $('<td class="fstu-td" style="text-align:center;">').text(num),
                $('<td class="fstu-td">').html('<strong>' + item.Unit_ShortName + '</strong>'),
                $('<td class="fstu-td">').text(item.MemberRegional_Name),
                $('<td class="fstu-td">').html('<a href="/personal/?ViewID=' + item.User_ID + '" target="_blank">' + item.FIO + '</a>'),
                $('<td class="fstu-td">').html(item.Phones),
                $('<td class="fstu-td">').text(item.email),
                $('<td class="fstu-td" style="text-align:center;">').html(actionsHtml)
            );
            tbody.append(tr);
        });

        $('#fstu-regional-pagination-wrap').show();
        // Тут виклик функції рендеру пагінації (опущено для лаконічності)
    }

    // 3. ПОШУК ТА ПАГІНАЦІЯ
    $('#fstu-regional-search').on('keyup', function(e) {
        if (e.key === 'Enter') {
            state.search = $(this).val();
            state.page = 1;
            loadTable();
        }
    });

    $('#fstu-regional-per-page').on('change', function() {
        state.perPage = $(this).val();
        state.page = 1;
        loadTable();
    });

    // 4. DROPDOWN "ДІЇ" (ФІКСОВАНЕ ПОЗИЦІОНУВАННЯ)
    $(document).on('click', '.fstu-dropdown-toggle', function(e) {
        e.stopPropagation();
        $('.fstu-dropdown-menu').remove(); // Close others

        var id = $(this).data('id');
        var rect = this.getBoundingClientRect();

        var menu = $('<ul class="fstu-dropdown-menu is-open">').css({
            top: rect.bottom + 'px',
            left: rect.left - 100 + 'px' // Зсув вліво, щоб не вилазити за екран
        });

        if (fstuRegionalFST.permissions.canManage) {
            menu.append('<li><a href="#" class="fstu-action-edit" data-id="' + id + '">📝 Редагувати</a></li>');
        }
        if (fstuRegionalFST.permissions.canDelete) {
            menu.append('<li><a href="#" class="fstu-action-delete" data-id="' + id + '" style="color:#d9534f;">❌ Видалити</a></li>');
        }

        $('body').append(menu);
    });

    $(document).on('click', function() {
        $('.fstu-dropdown-menu').remove();
    });

    // Закриваємо меню при скролі
    $(window).on('scroll', function() {
        $('.fstu-dropdown-menu').remove();
    });

    // 5. МОДАЛЬНЕ ВІКНО ТА SELECT2
    $('#fstu-btn-add').on('click', function() {
        $('#fstu-regional-id').val(0);
        $('#fstu-regional-region-id').val(state.regionId);
        $('#fstu-regional-form')[0].reset();

        // Ініціалізація Select2 для пошуку юзерів
        initUserSelect2();

        $('#fstu-regional-modal').css('display', 'flex');
    });

    $('.fstu-modal-close, .fstu-modal-close-btn').on('click', function() {
        $('#fstu-regional-modal').hide();
    });

    function initUserSelect2() {
        // Перевіряємо чи підключений Select2
        if ($.fn.select2) {
            $('#fstu-regional-user').select2({
                ajax: {
                    url: fstuRegionalFST.ajaxUrl,
                    dataType: 'json',
                    delay: 250,
                    type: 'POST',
                    data: function (params) {
                        return {
                            action: 'fstu_search_users_for_regional',
                            nonce: fstuRegionalFST.nonce,
                            q: params.term
                        };
                    },
                    processResults: function (data) {
                        return { results: data.data.results };
                    },
                    cache: true
                },
                minimumInputLength: 3,
                placeholder: 'Введіть ПІБ для пошуку...'
            });
        }
    }
    // (Додаємо до fstu-regional-fst.js)

    // Функція рендеру бейджів згідно зі стандартом AGENTS.md
    function buildTypeBadge( type ) {
        var cls = 'fstu-badge--default';
        var label = type || '—';
        if ( type === 'INSERT' || type === 'I' ) { cls = 'fstu-badge--insert'; label = 'INSERT'; }
        if ( type === 'UPDATE' || type === 'U' ) { cls = 'fstu-badge--update'; label = 'UPDATE'; }
        if ( type === 'DELETE' || type === 'D' ) { cls = 'fstu-badge--delete'; label = 'DELETE'; }
        return '<span class="fstu-badge ' + cls + '">' + label + '</span>';
    }

    // Перемикання на вкладку протоколу
    $('#fstu-btn-protocol').on('click', function() {
        $('#fstu-regional-fst-table-section').hide();
        $('#fstu-regional-fst-protocol-section').show();

        $(this).hide(); // Ховаємо кнопку "Протокол"
        $('#fstu-btn-back-to-directory').show(); // Показуємо кнопку "Довідник"

        loadProtocolTable();
    });

    // Повернення до довідника
    $('#fstu-btn-back-to-directory').on('click', function() {
        $('#fstu-regional-fst-protocol-section').hide();
        $('#fstu-regional-fst-table-section').show();

        $(this).hide();
        $('#fstu-btn-protocol').show();
    });

    // Стан протоколу
    var protocolState = {
        page: 1,
        perPage: 10,
        search: ''
    };

    // Завантаження протоколу (AJAX)
    function loadProtocolTable() {
        var tbody = $('#fstu-protocol-tbody');
        tbody.css('opacity', '0.5');

        $.post(fstuRegionalFST.ajaxUrl, {
            action: 'fstu_get_regional_fst_protocol',
            nonce: fstuRegionalFST.nonce,
            search: protocolState.search,
            page: protocolState.page,
            per_page: protocolState.perPage
        }, function(response) {
            tbody.css('opacity', '1');
            if (response.success) {
                renderProtocolTable(response.data);
            } else {
                tbody.html('<tr><td colspan="6" class="fstu-td" style="text-align:center; color:red;">' + (response.data.message || 'Помилка') + '</td></tr>');
            }
        }).fail(function() {
            tbody.css('opacity', '1');
            tbody.html('<tr><td colspan="6" class="fstu-td" style="text-align:center; color:red;">Помилка сервера</td></tr>');
        });
    }

    function renderProtocolTable(data) {
        var tbody = $('#fstu-protocol-tbody');
        tbody.empty();

        if (data.items.length === 0) {
            tbody.append('<tr><td colspan="6" class="fstu-td" style="text-align:center;">Записів не знайдено</td></tr>');
            $('#fstu-protocol-pagination-wrap').hide();
            return;
        }

        $.each(data.items, function(index, item) {
            var tr = $('<tr>').append(
                $('<td class="fstu-td">').text(item.Logs_DateCreate),
                $('<td class="fstu-td" style="text-align:center;">').html(buildTypeBadge(item.Logs_Type)),
                $('<td class="fstu-td">').text(item.Logs_Name),
                $('<td class="fstu-td">').text(item.Logs_Text),
                $('<td class="fstu-td" style="text-align:center;">').text(item.Logs_Error),
                $('<td class="fstu-td">').text(item.FIO || '—')
            );
            tbody.append(tr);
        });

        $('#fstu-protocol-pagination-wrap').show();

        // Рендер пагінації протоколу
        var info = 'Показано сторінку ' + data.page + ' з ' + data.total_pages + ' (всього ' + data.total + ' записів)';
        $('#fstu-protocol-info').text(info);

        var pagination = $('#fstu-protocol-pagination');
        pagination.empty();

        if (data.total_pages > 1) {
            var btnPrev = $('<button type="button" class="fstu-btn fstu-btn--secondary fstu-btn--page-nav" style="min-width:28px; height:28px;">‹</button>');
            if (data.page <= 1) btnPrev.prop('disabled', true);
            else btnPrev.on('click', function() { protocolState.page--; loadProtocolTable(); });
            pagination.append(btnPrev);

            var btnNext = $('<button type="button" class="fstu-btn fstu-btn--secondary fstu-btn--page-nav" style="min-width:28px; height:28px; margin-left:3px;">›</button>');
            if (data.page >= data.total_pages) btnNext.prop('disabled', true);
            else btnNext.on('click', function() { protocolState.page++; loadProtocolTable(); });
            pagination.append(btnNext);
        }
    }

    // Обробники для пошуку та селекта протоколу
    $('#fstu-protocol-search').on('keyup', function(e) {
        if (e.key === 'Enter') {
            protocolState.search = $(this).val();
            protocolState.page = 1;
            loadProtocolTable();
        }
    });

    $('#fstu-protocol-per-page').on('change', function() {
        protocolState.perPage = $(this).val();
        protocolState.page = 1;
        loadProtocolTable();
    });
    // 6. ЗБЕРЕЖЕННЯ ФОРМИ (ДОДАВАННЯ / РЕДАГУВАННЯ)
    $('#fstu-regional-form').on('submit', function(e) {
        e.preventDefault();

        var submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).text('Збереження...');

        var data = $(this).serialize() + '&action=fstu_save_regional_fst&nonce=' + fstuRegionalFST.nonce;

        $.post(fstuRegionalFST.ajaxUrl, data, function(res) {
            submitBtn.prop('disabled', false).text('Зберегти');
            if (res.success) {
                $('#fstu-regional-modal').hide();
                loadTable(); // Оновлюємо таблицю
            } else {
                alert(res.data.message || 'Сталася помилка.');
            }
        }).fail(function() {
            submitBtn.prop('disabled', false).text('Зберегти');
            alert('Помилка з\'єднання з сервером.');
        });
    });

    // 7. КЛІК ПО КНОПЦІ "РЕДАГУВАТИ" У DROPDOWN
    $(document).on('click', '.fstu-action-edit', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        $('.fstu-dropdown-menu').remove(); // Закриваємо меню

        // Отримуємо дані з сервера
        $.post(fstuRegionalFST.ajaxUrl, {
            action: 'fstu_get_regional_fst_item',
            nonce: fstuRegionalFST.nonce,
            id: id
        }, function(res) {
            if (res.success) {
                var item = res.data;

                // Заповнюємо форму
                $('#fstu-regional-id').val(item.RegionalFST_ID);
                $('#fstu-regional-region-id').val(item.Region_ID);
                $('#fstu-regional-unit').val(item.Unit_ID);
                $('#fstu-regional-role').val(item.MemberRegional_ID);

                initUserSelect2(); // Ініціалізація Select2

                // Вставляємо вибраного користувача в Select2
                var newOption = new Option(item.FIO, item.User_ID, true, true);
                $('#fstu-regional-user').append(newOption).trigger('change');

                // Відкриваємо модалку
                $('#fstu-regional-modal').css('display', 'flex');
            } else {
                alert(res.data.message || 'Не вдалося завантажити дані.');
            }
        });
    });

    // 8. КЛІК ПО КНОПЦІ "ВИДАЛИТИ" У DROPDOWN
    $(document).on('click', '.fstu-action-delete', function(e) {
        e.preventDefault();
        $('.fstu-dropdown-menu').remove(); // Закриваємо меню

        if (!confirm('Ви впевнені, що хочете видалити цю посаду?')) {
            return;
        }

        var id = $(this).data('id');

        $.post(fstuRegionalFST.ajaxUrl, {
            action: 'fstu_delete_regional_fst',
            nonce: fstuRegionalFST.nonce,
            id: id
        }, function(res) {
            if (res.success) {
                loadTable(); // Оновлюємо таблицю
            } else {
                alert(res.data.message || 'Не вдалося видалити запис.');
            }
        });
    });
    //-----
});