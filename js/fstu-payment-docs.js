/**
 * JS модуля "Реєстр платіжних документів".
 * Version:     1.1.0
 * Date_update: 2026-04-05
 */
jQuery( document ).ready( function( $ ) {
    'use strict';

    // Зберігаємо стан сторінки
    const state = {
        page: 1,
        per_page: 10,
        total_pages: 1
    };

    function fetchDocs() {
        $( '#fstu-pd-loader' ).removeClass( 'fstu-hidden' );
        $( '#fstu-pd-tbody' ).css( 'opacity', '0.5' );

        $.ajax({
            url: fstuPaymentDocs.ajaxUrl,
            method: 'POST',
            data: {
                action: 'fstu_get_payment_docs',
                nonce: fstuPaymentDocs.nonce,
                unit_id:  $( '#pd-filter-unit' ).val(),
                resp_id:  $( '#pd-filter-resp' ).val(),
                year:     $( '#pd-filter-year' ).val(),
                per_page: state.per_page,
                page:     state.page
            },
            success: function( r ) {
                if ( r.success ) {
                    state.total_pages = parseInt( r.data.total_pages, 10 );
                    $( '#fstu-pd-tbody' ).html( r.data.html );

                    // Обробка загальної суми
                    const totalSum = parseFloat( r.data.total_sum || 0 );
                    if ( r.data.total > 0 ) {
                        $( '#fstu-pd-total-sum-val' ).text( totalSum.toFixed( 2 ) );
                        $( '#fstu-pd-total-sum-wrap' ).show();
                    } else {
                        $( '#fstu-pd-total-sum-wrap' ).hide(); // Ховаємо, якщо записів немає
                    }

                    buildPagination( r.data.page, state.total_pages, parseInt( r.data.total, 10 ) );
                } else {
                    alert( 'Помилка: ' + r.data.message );
                }
            },
            complete: function() {
                $( '#fstu-pd-loader' ).addClass( 'fstu-hidden' );
                $( '#fstu-pd-tbody' ).css( 'opacity', '1' );
            }
        });
    }

    function buildPagination( current, totalPages, totalItems ) {
        if ( totalItems === 0 ) {
            $( '#fstu-pd-pagination-info' ).text( '' );
            $( '#fstu-pd-page-numbers' ).html( '' );
            return;
        }

        // Текст зліва
        const from = ( ( current - 1 ) * state.per_page ) + 1;
        const to   = Math.min( current * state.per_page, totalItems );
        $( '#fstu-pd-pagination-info' ).text( `Показано ${ from }–${ to } з ${ totalItems } записів` );

        if ( totalPages <= 1 ) {
            $( '#fstu-pd-page-numbers' ).html( '' );
            return;
        }

        let html = '';

        // Кнопки На початок / Назад
        html += `<button type="button" class="fstu-btn fstu-btn--secondary fstu-btn--page-nav" id="pd-page-first" ${ current <= 1 ? 'disabled' : '' }>«</button>`;
        html += `<button type="button" class="fstu-btn fstu-btn--secondary fstu-btn--page-nav" id="pd-page-prev" ${ current <= 1 ? 'disabled' : '' }>‹</button>`;

        // Алгоритм номерів сторінок
        const MAX_VISIBLE = 7;
        let pages = [];

        if ( totalPages <= MAX_VISIBLE ) {
            for ( let i = 1; i <= totalPages; i++ ) pages.push( i );
        } else {
            pages = [ 1 ];
            let start = Math.max( 2, current - 2 );
            let end   = Math.min( totalPages - 1, current + 2 );

            if ( start > 2 ) pages.push( '…' );
            for ( let i = start; i <= end; i++ ) pages.push( i );
            if ( end < totalPages - 1 ) pages.push( '…' );
            pages.push( totalPages );
        }

        // Рендер кнопок з цифрами
        pages.forEach( p => {
            if ( p === '…' ) {
                html += `<span class="fstu-pagination__ellipsis" style="padding: 0 5px; color: var(--fstu-text-light);">…</span>`;
            } else {
                const active = ( p === current ) ? ' fstu-btn--page-active' : '';
                html += `<button type="button" class="fstu-btn fstu-btn--page${active}" data-page="${p}">${p}</button>`;
            }
        });

        // Кнопки Далі / В кінець
        html += `<button type="button" class="fstu-btn fstu-btn--secondary fstu-btn--page-nav" id="pd-page-next" ${ current >= totalPages ? 'disabled' : '' }>›</button>`;
        html += `<button type="button" class="fstu-btn fstu-btn--secondary fstu-btn--page-nav" id="pd-page-last" ${ current >= totalPages ? 'disabled' : '' }>»</button>`;

        $( '#fstu-pd-page-numbers' ).html( html );
    }

    // ─── Події ─────────────────────────────────────────────────────────────────
    // Зміна фільтрів
    $( document ).on( 'change', '.fstu-filter-trigger', function() {
        state.page = 1;

        // Показуємо або ховаємо кнопку внесків осередку
        if ( $('#pd-filter-unit').val() > 0 ) {
            $('#fstu-pd-btn-yearly-dues').show();
        } else {
            $('#fstu-pd-btn-yearly-dues').hide();
        }

        fetchDocs();
    });

    // Зміна кількості на сторінку
    $( document ).on( 'change', '#pd-filter-per-page', function() {
        state.per_page = parseInt( $( this ).val(), 10 ) || 25;
        state.page = 1;
        fetchDocs();
    });

    // Кнопка Оновити
    $( document ).on( 'click', '#fstu-pd-btn-refresh', function() { fetchDocs(); });

    // Кліки по пагінації
    $( document ).on( 'click', '#pd-page-first', function() { if ( !$(this).prop('disabled') ) { state.page = 1; fetchDocs(); } });
    $( document ).on( 'click', '#pd-page-prev',  function() { if ( !$(this).prop('disabled') && state.page > 1 ) { state.page--; fetchDocs(); } });
    $( document ).on( 'click', '#pd-page-next',  function() { if ( !$(this).prop('disabled') && state.page < state.total_pages ) { state.page++; fetchDocs(); } });
    $( document ).on( 'click', '#pd-page-last',  function() { if ( !$(this).prop('disabled') ) { state.page = state.total_pages; fetchDocs(); } });

    $( document ).on( 'click', '#fstu-pd-page-numbers .fstu-btn--page', function() {
        const page = parseInt( $( this ).data( 'page' ) );
        if ( page && ! $( this ).prop( 'disabled' ) && ! $( this ).hasClass('fstu-btn--page-active') ) {
            state.page = page;
            fetchDocs();
        }
    });

    // КЛІК "ВИДАЛИТИ ДОКУМЕНТ"
    $( document ).on( 'click', '.fstu-action-delete', function( e ) {
        e.preventDefault();
        const docId = $( this ).data( 'id' );

        // Ховаємо випадаюче меню
        $( '.fstu-opts' ).removeClass( 'fstu-opts--open' ).removeClass( 'fstu-dropup' );

        if ( confirm( `УВАГА!\nВи дійсно хочете безповоротно ВИДАЛИТИ документ №${docId}?\nВсі оплати людей з цього документа будуть скасовані!` ) ) {

            // Щоб візуально показати, що йде процес, додамо прозорість таблиці
            $( '#fstu-pd-tbody' ).css( 'opacity', '0.5' );

            $.ajax({
                url: fstuPaymentDocs.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'fstu_delete_payment_doc',
                    nonce: fstuPaymentDocs.nonce,
                    doc_id: docId
                },
                success: function( r ) {
                    if ( r.success ) {
                        fetchDocs(); // Оновлюємо таблицю без перезавантаження
                    } else {
                        alert( 'Помилка: ' + ( r.data?.message || 'Невідома помилка' ) );
                        $( '#fstu-pd-tbody' ).css( 'opacity', '1' ); // Повертаємо прозорість, якщо помилка
                    }
                },
                error: function() {
                    alert( 'Помилка з\'єднання з сервером.' );
                    $( '#fstu-pd-tbody' ).css( 'opacity', '1' );
                }
            });
        }
    });

    // ─── РЕДАКТОР ДОКУМЕНТА (Модальне вікно) ──────────────────────────────────

    const $modalEditor = $( '#fstu-modal-doc-editor' );
    const $formEditor  = $( '#fstu-pd-editor-form' );
    const $tbodyTP     = $( '#pd-tp-tbody' );

    // Зчитуємо HTML-шаблон нашого рядка з <template>
    const templateRowHTML = $( '#pd-row-template' ).html();

    // Функція: Оновлення номерів рядків (1, 2, 3...)
    function updateRowNumbers() {
        $tbodyTP.find( '.fstu-row' ).each( function( index ) {
            $( this ).find( '.pd-row-num' ).text( index + 1 );
        });
    }

    // Функція: Перерахунок загальної суми
    function recalculateTotal() {
        let total = 0;
        $tbodyTP.find( '.pd-tp-sum' ).each( function() {
            const val = parseFloat( $( this ).val() ) || 0;
            total += val;
        });
        $( '#pd-total-sum' ).text( total.toFixed( 2 ) );
        $( '#pd-input-total-sum' ).val( total.toFixed( 2 ) );
    }

    // Функція: Фільтрація людей по вибраному ОФСТ
    function filterUsersByUnit() {
        const selectedUnit = $( '#pd-edit-unit' ).val();

        $tbodyTP.find( '.pd-tp-user' ).each( function() {
            const $select = $( this );
            const currentVal = $select.val();
            let hasValidSelection = false;

            $select.find( 'option' ).each( function() {
                const $opt = $( this );
                if ( $opt.val() === '' ) return; // Пропускаємо плейсхолдер

                // Якщо ОФСТ вибрано, ховаємо людей з інших осередків
                if ( selectedUnit && $opt.data( 'unit' ) != selectedUnit ) {
                    $opt.hide().prop( 'disabled', true );
                } else {
                    $opt.show().prop( 'disabled', false );
                    if ( $opt.val() === currentVal ) hasValidSelection = true;
                }
            });

            // Якщо поточна вибрана людина не з цього ОФСТ, скидаємо вибір
            if ( !hasValidSelection && currentVal !== '' ) {
                $select.val( '' );
            }
        });
    }

    // Додавання нового рядка в таблицю
    function addTableRow() {
        $tbodyTP.append( templateRowHTML );
        updateRowNumbers();
        recalculateTotal();
        filterUsersByUnit(); // Одразу застосовуємо фільтр до нового рядка
    }

    // ─── Події модалки ────────────────────────────────────────────────────────

    // Клік на кнопку "Створити документ"
    $( document ).on( 'click', '#fstu-pd-btn-add', function() {
        $formEditor[0].reset();
        $( '#pd-edit-doc-id' ).val( 0 );
        $( '#pd-modal-title' ).text( 'Створення документу про оплату' );

        // РОЗБЛОКОВУЄМО ПОЛЯ (після режиму view)
        $( '#fstu-pd-editor-form input, #fstu-pd-editor-form select' ).prop( 'disabled', false );
        $( '#pd-btn-save-doc, #pd-btn-add-row, .pd-btn-remove-row' ).removeClass( 'fstu-hidden' );

        $tbodyTP.empty();
        addTableRow();

        const now = new Date();
        const tzOffset = now.getTimezoneOffset() * 60000;
        const localISOTime = (new Date(now - tzOffset)).toISOString().slice(0, 16);
        $( '#pd-edit-date' ).val( localISOTime );

        $modalEditor.removeClass( 'fstu-hidden' ).attr( 'aria-hidden', 'false' );
        $( 'body' ).addClass( 'fstu-modal-open' );
    });

    // Клік "Закрити" (Хрестик)
    $( document ).on( 'click', '#fstu-modal-doc-editor .fstu-modal-close-btn', function() {
        $modalEditor.addClass( 'fstu-hidden' ).attr( 'aria-hidden', 'true' );
        $( 'body' ).removeClass( 'fstu-modal-open' );
    });

    // Клік "Додати рядок"
    $( document ).on( 'click', '#pd-btn-add-row', function() {
        addTableRow();
    });

    // Клік "Видалити рядок" (✖)
    $( document ).on( 'click', '.pd-btn-remove-row', function() {
        if ( $tbodyTP.find( '.fstu-row' ).length > 1 ) {
            $( this ).closest( '.fstu-row' ).remove();
            updateRowNumbers();
            recalculateTotal();
        } else {
            alert( 'У документі має бути хоча б один рядок!' );
        }
    });

    // Зміна суми в будь-якому рядку -> миттєвий перерахунок
    $( document ).on( 'input', '.pd-tp-sum', function() {
        recalculateTotal();
    });

    // Зміна ОФСТ у шапці документа -> фільтрація списку людей
    $( document ).on( 'change', '#pd-edit-unit', function() {
        filterUsersByUnit();
    });

    // ЗБЕРЕЖЕННЯ ФОРМИ
    $( document ).on( 'submit', '#fstu-pd-editor-form', function( e ) {
        e.preventDefault();

        if ( $tbodyTP.find( '.fstu-row' ).length === 0 ) {
            alert( 'Додайте хоча б один рядок!' );
            return;
        }

        const $btn = $( '#pd-btn-save-doc' );
        const originalText = $btn.html();
        $btn.prop( 'disabled', true ).html( '<span class="fstu-btn__text">⏳ Збереження...</span>' );

        $.ajax({
            url: fstuPaymentDocs.ajaxUrl,
            method: 'POST',
            data: $( this ).serialize() + '&action=fstu_save_payment_doc&nonce=' + fstuPaymentDocs.nonce,
            success: function( r ) {
                if ( r.success ) {
                    alert( r.data.message ); // Показуємо успіх
                    $modalEditor.addClass( 'fstu-hidden' ).attr( 'aria-hidden', 'true' );
                    $( 'body' ).removeClass( 'fstu-modal-open' );
                    fetchDocs(); // Оновлюємо таблицю документів на задньому плані
                } else {
                    alert( 'Помилка: ' + ( r.data?.message || 'Невідома помилка' ) );
                }
            },
            error: function() {
                alert( 'Помилка з\'єднання з сервером.' );
            },
            complete: function() {
                $btn.prop( 'disabled', false ).html( originalText );
            }
        });
    });
    // Універсальна функція відкриття існуючого документа
    function openExistingDoc( docId, mode ) {
        // mode: 'view' або 'edit'
        $( '#fstu-pd-loader' ).removeClass( 'fstu-hidden' );

        $.ajax({
            url: fstuPaymentDocs.ajaxUrl,
            method: 'POST',
            data: { action: 'fstu_get_payment_doc', nonce: fstuPaymentDocs.nonce, doc_id: docId },
            success: function( r ) {
                if ( r.success ) {
                    const data = r.data;
                    $formEditor[0].reset();
                    $tbodyTP.empty();

                    $( '#pd-edit-doc-id' ).val( docId );
                    $( '#pd-modal-title' ).text( mode === 'view' ? `Перегляд документа №${docId}` : `Редагування документа №${docId}` );

                    // Заповнюємо шапку
                    const dateFormatted = data.header.Doc_DuesPayment_Date.replace(' ', 'T').slice(0, 16);
                    $( '#pd-edit-date' ).val( dateFormatted );
                    $( '#pd-edit-unit' ).val( data.header.Doc_DuesPayment_UnitID );
                    $( '#pd-edit-resp' ).val( data.header.Doc_DuesPayment_RespID );
                    $( '#pd-edit-url' ).val( data.header.Doc_DuesPayment_URL );
                    $( '#pd-edit-comment' ).val( data.header.Doc_DuesPayment_Comment );

                    // Заповнюємо табличну частину
                    if ( data.rows && data.rows.length > 0 ) {
                        data.rows.forEach( row => {
                            $tbodyTP.append( templateRowHTML );
                            const $lastRow = $tbodyTP.find( '.fstu-row' ).last();

                            $lastRow.find( '.pd-tp-user' ).val( row.TP_DuesPayment_UserID );
                            $lastRow.find( '.pd-tp-type' ).val( row.DuesType_ID || 1 );
                            $lastRow.find( '.pd-tp-sum' ).val( parseFloat( row.TP_DuesPayment_Sum ).toFixed( 2 ) );
                            $lastRow.find( '.pd-tp-year' ).val( row.Year_ID );
                        });
                    }

                    updateRowNumbers();
                    filterUsersByUnit(); // Застосовуємо фільтри ОФСТ
                    recalculateTotal();

                    // Застосовуємо режим (View / Edit)
                    if ( mode === 'view' ) {
                        $( '#fstu-pd-editor-form input, #fstu-pd-editor-form select' ).prop( 'disabled', true );
                        $( '#pd-btn-save-doc, #pd-btn-add-row, .pd-btn-remove-row' ).addClass( 'fstu-hidden' );
                    } else {
                        $( '#fstu-pd-editor-form input, #fstu-pd-editor-form select' ).prop( 'disabled', false );
                        $( '#pd-btn-save-doc, #pd-btn-add-row, .pd-btn-remove-row' ).removeClass( 'fstu-hidden' );
                    }

                    $modalEditor.removeClass( 'fstu-hidden' ).attr( 'aria-hidden', 'false' );
                    $( 'body' ).addClass( 'fstu-modal-open' );

                } else {
                    alert( 'Помилка: ' + ( r.data?.message || 'Невідома помилка' ) );
                }
            },
            complete: function() {
                $( '#fstu-pd-loader' ).addClass( 'fstu-hidden' );
            }
        });
    }

    // КЛІК "ПЕРЕГЛЯД"
    $( document ).on( 'click', '.fstu-action-view', function( e ) {
        e.preventDefault();
        $( '.fstu-opts' ).removeClass( 'fstu-opts--open fstu-dropup' );
        openExistingDoc( $( this ).data( 'id' ), 'view' );
    });

    // КЛІК "РЕДАГУВАТИ"
    $( document ).on( 'click', '.fstu-action-edit', function( e ) {
        e.preventDefault();
        $( '.fstu-opts' ).removeClass( 'fstu-opts--open fstu-dropup' );
        openExistingDoc( $( this ).data( 'id' ), 'edit' );
    });

    // ─── Обробка меню опцій (Відкриття / Закриття) ────────────────────────────
    $( document ).on( 'click', '.fstu-opts-btn', function ( e ) {
        e.stopPropagation(); // Зупиняємо клік, щоб не спрацювало закриття
        const $parent = $( this ).parent();

        // Закриваємо всі інші відкриті меню
        $( '.fstu-opts' ).not( $parent ).removeClass( 'fstu-opts--open fstu-dropup' );

        // Розумне позиціонування (щоб не вилазило за низ екрану)
        const menuHeight = 150;
        const windowHeight = $( window ).height();
        const rect = this.getBoundingClientRect();

        if ( rect.bottom + menuHeight > windowHeight && rect.top > menuHeight ) {
            $parent.addClass( 'fstu-dropup' ); // Відкриваємо вгору
        } else {
            $parent.removeClass( 'fstu-dropup' ); // Відкриваємо вниз
        }

        $parent.toggleClass( 'fstu-opts--open' );
    } );

    // Закриття меню при кліку в будь-яке місце сторінки
    $( document ).on( 'click', function () {
        $( '.fstu-opts' ).removeClass( 'fstu-opts--open fstu-dropup' );
    } );

    // ─── ВНЕСКИ ОСЕРЕДКУ (Portmone) ───────────────────────────────────────────
    $( document ).on( 'click', '#fstu-pd-btn-yearly-dues', function() {
        const unitId = $( '#pd-filter-unit' ).val();
        if ( !unitId || unitId == 0 ) return;

        $( '#fstu-yearly-dues-tbody' ).empty();
        $( '#fstu-yearly-dues-loader' ).removeClass( 'fstu-hidden' );
        $( '#fstu-modal-yearly-dues' ).removeClass( 'fstu-hidden' );
        $( 'body' ).addClass( 'fstu-modal-open' );

        $.post( fstuPaymentDocs.ajaxUrl, {
            action: 'fstu_get_yearly_unit_dues',
            nonce: fstuPaymentDocs.nonce,
            unit_id: unitId
        }, function( r ) {
            $( '#fstu-yearly-dues-loader' ).addClass( 'fstu-hidden' );
            if ( r.success ) {
                $( '#fstu-yearly-dues-tbody' ).html( r.data.html );
            } else {
                alert( 'Помилка: ' + ( r.data?.message || 'Невідома помилка' ) );
                $( '#fstu-modal-yearly-dues .fstu-modal-close-btn' ).trigger('click');
            }
        });
    });

    $( document ).on( 'click', '#fstu-modal-yearly-dues .fstu-modal-close-btn', function() {
        $( '#fstu-modal-yearly-dues' ).addClass( 'fstu-hidden' );
        $( 'body' ).removeClass( 'fstu-modal-open' );
    });

    // ─── Ініціалізація ────────────────────────────────────────────────────────

    // Автоматично обираємо осередок, якщо передано в URL (?unit_id=...)
    if ( fstuPaymentDocs.unitId > 0 ) {
        $( '#pd-filter-unit' ).val( fstuPaymentDocs.unitId );
        $( '#fstu-pd-btn-yearly-dues' ).show();
    }

    fetchDocs();
});