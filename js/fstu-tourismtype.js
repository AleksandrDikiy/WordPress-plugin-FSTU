/**
 * JS-логіка модуля "Довідник видів туризму".
 *
 * Version:     1.0.1
 * Date_update: 2026-04-08
 */

/* global fstuTourismType, jQuery */

jQuery( document ).ready( function ( $ ) {
    'use strict';

    if ( typeof fstuTourismType === 'undefined' ) {
        return;
    }

    var cfg = {
        ajaxUrl : fstuTourismType.ajaxUrl,
        nonce   : fstuTourismType.nonce,
        isAdmin : fstuTourismType.isAdmin === '1',
        i18n    : fstuTourismType.i18n
    };

    var state = { page: 1, perPage: 10, search: '', loading: false };
    var protocolState = { page: 1, perPage: 10, search: '', loading: false };

    var $app            = $( '#fstu-tourismtype-app' );
    var $sectionDict    = $( '#fstu-tourismtype-section-dict' );
    var $sectionProt    = $( '#fstu-tourismtype-section-protocol' );

    var $loading        = $( '#fstu-tourismtype-loading' );
    var $tbody          = $( '#fstu-tourismtype-tbody' );
    var $notice         = $( '#fstu-tourismtype-notice' );
    var $pageControls   = $( '#fstu-tourismtype-page-controls' );
    var $pageInfo       = $( '#fstu-tourismtype-page-info' );
    var $searchInput    = $( '#fstu-tourismtype-search' );
    var $perPageSelect  = $( '#fstu-tourismtype-per-page' );

    var $protLoading    = $( '#fstu-tourismtype-protocol-loading' );
    var $protTbody      = $( '#fstu-tourismtype-protocol-tbody' );
    var $protNotice     = $( '#fstu-tourismtype-protocol-notice' );
    var $protSearch     = $( '#fstu-tourismtype-protocol-search' );
    var $protPerPage    = $( '#fstu-tourismtype-protocol-per-page' );
    var $protControls   = $( '#fstu-tourismtype-protocol-page-controls' );
    var $protInfo       = $( '#fstu-tourismtype-protocol-page-info' );

    var $modal          = $( '#fstu-tourismtype-modal' );
    var $modalTitle     = $( '#fstu-tourismtype-modal-title' );
    var $formNotice     = $( '#fstu-tourismtype-form-notice' );
    var $form           = $( '#fstu-tourismtype-form' );
    var $fieldId        = $( '#fstu-tt-id' );
    var $fieldName      = $( '#fstu-tt-name' );
    var $fieldNumber    = $( '#fstu-tt-number' );
    var $fieldOrder     = $( '#fstu-tt-order' );

    function init() {
        loadList();
        bindEvents();
    }

    function bindEvents() {
        $app.on( 'click', '#fstu-tourismtype-btn-refresh', function () { state.page = 1; loadList(); } );
        $app.on( 'click', '#fstu-tourismtype-btn-protocol', function () { showSection( 'protocol' ); protocolState.page = 1; loadProtocol(); } );
        $app.on( 'click', '#fstu-tourismtype-btn-back-to-dict', function () { showSection( 'dict' ); } );

        var searchTimer;
        $app.on( 'input', '#fstu-tourismtype-search', function () {
            clearTimeout( searchTimer );
            searchTimer = setTimeout( function () { state.search = $searchInput.val().trim(); state.page = 1; loadList(); }, 350 );
        } );

        $app.on( 'change', '#fstu-tourismtype-per-page', function () { state.perPage = parseInt( $perPageSelect.val(), 10 ); state.page = 1; loadList(); } );

        $app.on( 'click', '#fstu-tourismtype-btn-add', function () { openModal( null ); } );

        $app.on( 'click', '.fstu-dropdown__trigger', function ( e ) {
            e.stopPropagation();
            var $dd = $( this ).closest( '.fstu-dropdown' );
            var isOpen = $dd.hasClass( 'fstu-dropdown--open' );
            closeAllDropdowns();
            if ( ! isOpen ) { $dd.addClass( 'fstu-dropdown--open' ); adjustDropdownDirection( $dd ); }
        } );

        $( document ).on( 'click.fstu_tt', function () { closeAllDropdowns(); } );

        $app.on( 'click', '.fstu-btn--edit', function () { closeAllDropdowns(); openModal( $( this ).data( 'id' ) ); } );

        $app.on( 'click', '.fstu-btn--delete', function () {
            var id = $( this ).data( 'id' ), name = $( this ).data( 'name' );
            closeAllDropdowns();
            if ( ! window.confirm( cfg.i18n.confirmDelete + '\n\n"' + name + '"' ) ) return;
            deleteItem( id );
        } );

        $app.on( 'click', '#fstu-tourismtype-btn-save', function () { saveForm(); } );
        $app.on( 'click', '#fstu-tourismtype-modal-close, #fstu-tourismtype-btn-cancel', function () { closeModal(); } );
        $app.on( 'click', '#fstu-tourismtype-modal-overlay', function () { closeModal(); } );

        $app.on( 'keydown', '#fstu-tourismtype-form', function ( e ) { if ( e.key === 'Enter' ) { e.preventDefault(); saveForm(); } } );
        $( document ).on( 'keydown.fstu_tt_modal', function ( e ) { if ( e.key === 'Escape' && ! $modal.hasClass( 'fstu-hidden' ) ) closeModal(); } );

        var protSearchTimer;
        $app.on( 'input', '#fstu-tourismtype-protocol-search', function () {
            clearTimeout( protSearchTimer );
            protSearchTimer = setTimeout( function () { protocolState.search = $protSearch.val().trim(); protocolState.page = 1; loadProtocol(); }, 350 );
        } );

        $app.on( 'change', '#fstu-tourismtype-protocol-per-page', function () { protocolState.perPage = parseInt( $protPerPage.val(), 10 ); protocolState.page = 1; loadProtocol(); } );
    }

    function loadList() {
        if ( state.loading ) return;
        state.loading = true; showLoading( true ); hideNotice( $notice );

        ajaxRequest( 'fstu_tourismtype_get_list', { page: state.page, per_page: state.perPage, search: state.search } )
            .done( function ( response ) {
                if ( response.success ) {
                    renderTable( response.data );
                    renderPagination( response.data.total_pages, response.data.page, response.data.total, response.data.per_page, $pageControls, $pageInfo, function ( p ) { state.page = p; loadList(); } );
                } else { showNotice( $notice, response.data.message || cfg.i18n.errorLoad, 'error' ); }
            } )
            .fail( function () { showNotice( $notice, cfg.i18n.errorLoad, 'error' ); } )
            .always( function () { state.loading = false; showLoading( false ); } );
    }

    function renderTable( data ) {
        $tbody.empty();
        if ( ! data.items || data.items.length === 0 ) {
            var cols = cfg.isAdmin ? 4 : 2;
            $tbody.append( '<tr><td class="fstu-td--empty" colspan="' + cols + '">' + escHtml( cfg.i18n.noData ) + '</td></tr>' );
            return;
        }

        var offset = ( data.page - 1 ) * data.per_page;
        $.each( data.items, function ( idx, item ) {
            var num = offset + idx + 1;
            var $tr = $( '<tr>' );
            $tr.append( '<td style="text-align:center">' + num + '</td>' );
            $tr.append( '<td>' + escHtml( item.TourismType_Name ) + '</td>' );

            if ( cfg.isAdmin ) {
                $tr.append( '<td style="text-align:center">' + escHtml( item.TourismType_Number || '—' ) + '</td>' );
                var dropHtml = '<td style="text-align:center"><div class="fstu-dropdown"><button type="button" class="fstu-btn fstu-btn--icon fstu-dropdown__trigger" aria-label="Дії" title="Дії для рядка">⋮</button><div class="fstu-dropdown__menu"><button type="button" class="fstu-dropdown__item fstu-btn--edit" data-id="' + parseInt( item.TourismType_ID, 10 ) + '" data-name="' + escAttr( item.TourismType_Name ) + '">✏️ Редагування</button><button type="button" class="fstu-dropdown__item fstu-dropdown__item--danger fstu-btn--delete" data-id="' + parseInt( item.TourismType_ID, 10 ) + '" data-name="' + escAttr( item.TourismType_Name ) + '">🗑 Видалення</button></div></div></td>';
                $tr.append( dropHtml );
            }
            $tbody.append( $tr );
        } );
    }

    function openModal( id ) {
        resetForm();
        if ( id ) {
            $modalTitle.text( 'Редагування виду туризму' );
            loadItemForEdit( id );
        } else {
            $modalTitle.text( 'Додавання виду туризму' );
            $modal.removeClass( 'fstu-hidden' );
            $fieldName.trigger( 'focus' );
        }
    }

    function loadItemForEdit( id ) {
        ajaxRequest( 'fstu_tourismtype_get_item', { id : id } ).done( function ( response ) {
            if ( response.success ) {
                var item = response.data.item;
                $fieldId.val( item.TourismType_ID );
                $fieldName.val( item.TourismType_Name );
                $fieldNumber.val( item.TourismType_Number || '' );
                $fieldOrder.val( item.TourismType_Order  || '' );
                $modal.removeClass( 'fstu-hidden' );
                $fieldName.trigger( 'focus' );
            } else { showNotice( $notice, response.data.message || cfg.i18n.errorLoad, 'error' ); }
        } ).fail( function () { showNotice( $notice, cfg.i18n.errorLoad, 'error' ); } );
    }

    function closeModal() {
        $modal.addClass( 'fstu-hidden' );
        resetForm();
    }

    function resetForm() {
        $form[ 0 ].reset();
        $fieldId.val( '0' );
        hideNotice( $formNotice );
    }

    function saveForm() {
        var name = $fieldName.val().trim();
        if ( name === '' ) {
            // ВИПРАВЛЕННЯ: Екрановані одинарні лапки у тексті
            showNotice( $formNotice, 'Найменування є обов\'язковим.', 'error' );
            $fieldName.trigger( 'focus' );
            return;
        }

        var $saveBtn = $( '#fstu-tourismtype-btn-save' );
        $saveBtn.prop( 'disabled', true ).text( cfg.i18n.saving );

        var formData = {
            id                 : $fieldId.val(),
            TourismType_Name   : name,
            TourismType_Number : $fieldNumber.val(),
            TourismType_Order  : $fieldOrder.val(),
            // ВИПРАВЛЕННЯ: Додано відправку поля Honeypot
            fstu_website       : $('input[name="fstu_website"]').val()
        };

        ajaxRequest( 'fstu_tourismtype_save', formData )
            .done( function ( response ) {
                if ( response.success ) { closeModal(); showNotice( $notice, response.data.message, 'success' ); state.page = 1; loadList(); }
                else { showNotice( $formNotice, response.data.message || cfg.i18n.errorSave, 'error' ); }
            } )
            .fail( function () { showNotice( $formNotice, cfg.i18n.errorSave, 'error' ); } )
            .always( function () { $saveBtn.prop( 'disabled', false ).html( '💾 Зберегти' ); } );
    }

    function deleteItem( id ) {
        ajaxRequest( 'fstu_tourismtype_delete', { id : id } ).done( function ( response ) {
            if ( response.success ) {
                showNotice( $notice, response.data.message, 'success' );
                if ( $tbody.find( 'tr' ).length === 1 && state.page > 1 ) state.page--;
                loadList();
            } else { showNotice( $notice, response.data.message || cfg.i18n.errorSave, 'error' ); }
        } ).fail( function () { showNotice( $notice, cfg.i18n.errorSave, 'error' ); } );
    }

    function loadProtocol() {
        if ( protocolState.loading ) return;
        protocolState.loading = true; $protLoading.removeClass( 'fstu-loading--hidden' ); hideNotice( $protNotice );

        ajaxRequest( 'fstu_tourismtype_get_protocol', { page: protocolState.page, per_page: protocolState.perPage, search: protocolState.search } )
            .done( function ( response ) {
                if ( response.success ) { renderProtocol( response.data ); renderPagination( response.data.total_pages, response.data.page, response.data.total, response.data.per_page, $protControls, $protInfo, function ( p ) { protocolState.page = p; loadProtocol(); } ); }
                else { showNotice( $protNotice, response.data.message || cfg.i18n.errorLoad, 'error' ); }
            } )
            .fail( function () { showNotice( $protNotice, cfg.i18n.errorLoad, 'error' ); } )
            .always( function () { protocolState.loading = false; $protLoading.addClass( 'fstu-loading--hidden' ); } );
    }

    function renderProtocol( data ) {
        $protTbody.empty();
        if ( ! data.items || data.items.length === 0 ) { $protTbody.append( '<tr><td class="fstu-td--empty" colspan="6">' + escHtml( cfg.i18n.noData ) + '</td></tr>' ); return; }
        $.each( data.items, function ( idx, row ) {
            var typeBadge = buildTypeBadge( row.Logs_Type ), statusHtml = buildStatusHtml( row.Logs_Error );
            var $tr = $( '<tr>' );
            $tr.append( '<td style="white-space:nowrap">' + escHtml( row.Logs_DateCreate ) + '</td>' );
            $tr.append( '<td style="text-align:center">'  + typeBadge + '</td>' );
            $tr.append( '<td>' + escHtml( row.Logs_Name ) + '</td>' );
            $tr.append( '<td>' + escHtml( row.Logs_Text ) + '</td>' );
            $tr.append( '<td style="text-align:center">'  + statusHtml + '</td>' );
            $tr.append( '<td>' + escHtml( row.FIO || '—' ) + '</td>' );
            $protTbody.append( $tr );
        } );
    }

    function showSection( which ) {
        if ( which === 'protocol' ) { $sectionDict.removeClass( 'fstu-section--active' ).addClass( 'fstu-hidden' ); $sectionProt.removeClass( 'fstu-hidden' ).addClass( 'fstu-section--active' ); }
        else { $sectionProt.removeClass( 'fstu-section--active' ).addClass( 'fstu-hidden' ); $sectionDict.removeClass( 'fstu-hidden' ).addClass( 'fstu-section--active' ); }
    }

    function showLoading( show ) { if ( show ) { $loading.show(); $tbody.css( 'opacity', '.4' ); } else { $loading.hide(); $tbody.css( 'opacity', '1' ); } }
    function showNotice( $el, message, type ) { $el.removeClass( 'fstu-notice--hidden fstu-notice--success fstu-notice--error fstu-notice--info' ).addClass( 'fstu-notice--' + type ).text( message ); if ( type === 'success' ) setTimeout( function () { hideNotice( $el ); }, 4000 ); }
    function hideNotice( $el ) { $el.addClass( 'fstu-notice--hidden' ).text( '' ); }

    function renderPagination( totalPages, currentPage, total, perPage, $controls, $info, onPage ) {
        $controls.empty();
        if ( totalPages <= 1 ) { $info.text( 'Записів: ' + total ); return; }
        var $prev = $( '<button type="button" class="fstu-page-btn">«</button>' ).prop( 'disabled', currentPage <= 1 );
        $prev.on( 'click', function () { onPage( currentPage - 1 ); } ); $controls.append( $prev );
        var pages = buildPageRange( currentPage, totalPages );
        $.each( pages, function ( i, p ) {
            if ( p === '…' ) { $controls.append( '<span style="padding:0 4px;line-height:26px;color:#6c757d">…</span>' ); return; }
            var $btn = $( '<button type="button" class="fstu-page-btn">' + p + '</button>' );
            if ( p === currentPage ) $btn.addClass( 'fstu-page-btn--active' );
            $btn.on( 'click', ( function ( pg ) { return function () { onPage( pg ); }; }( p ) ) ); $controls.append( $btn );
        } );
        var $next = $( '<button type="button" class="fstu-page-btn">»</button>' ).prop( 'disabled', currentPage >= totalPages );
        $next.on( 'click', function () { onPage( currentPage + 1 ); } ); $controls.append( $next );
        var from = ( currentPage - 1 ) * perPage + 1, to = Math.min( currentPage * perPage, total );
        $info.text( 'Записів: ' + total + ' | ' + from + '–' + to + ' | Стор. ' + currentPage + ' з ' + totalPages );
    }

    function buildPageRange( current, total ) {
        if ( total <= 7 ) return Array.from( { length: total }, function ( _, i ) { return i + 1; } );
        var pages = [ 1 ];
        if ( current > 3 ) pages.push( '…' );
        for ( var i = Math.max( 2, current - 1 ); i <= Math.min( total - 1, current + 1 ); i++ ) pages.push( i );
        if ( current < total - 2 ) pages.push( '…' );
        pages.push( total );
        return pages;
    }

    function buildTypeBadge( type ) {
        var cls = 'fstu-badge--default';
        var label = type || '—';
        
        if ( type === 'INSERT' || type === 'I' ) { cls = 'fstu-badge--insert'; label = 'INSERT'; }
        if ( type === 'UPDATE' || type === 'U' ) { cls = 'fstu-badge--update'; label = 'UPDATE'; }
        if ( type === 'DELETE' || type === 'D' ) { cls = 'fstu-badge--delete'; label = 'DELETE'; }
        
        return '<span class="fstu-badge ' + cls + '">' + escHtml( label ) + '</span>';
    }

    function buildStatusHtml( status ) { return status === '✓' ? '<span class="fstu-status--ok">✓</span>' : '<span class="fstu-status--err" title="' + escAttr( status ) + '">✗</span>'; }
    function closeAllDropdowns() { $app.find( '.fstu-dropdown--open' ).removeClass( 'fstu-dropdown--open' ); }
    function adjustDropdownDirection( $dd ) { var $menu = $dd.find( '.fstu-dropdown__menu' ); if ( $menu[ 0 ].getBoundingClientRect().bottom > window.innerHeight ) $dd.addClass( 'fstu-dropdown--up' ); else $dd.removeClass( 'fstu-dropdown--up' ); }

    function escHtml( str ) { if ( str === null || str === undefined ) return ''; return String( str ).replace( /&/g, '&amp;' ).replace( /</g, '&lt;' ).replace( />/g, '&gt;' ).replace( /"/g, '&quot;' ).replace( /'/g, '&#039;' ); }
    function escAttr( str ) { return escHtml( str ); }
    function ajaxRequest( action, data ) { return $.ajax( { url: cfg.ajaxUrl, type: 'POST', data: $.extend( {}, data, { action: action, nonce: cfg.nonce } ) } ); }

    init();
} );