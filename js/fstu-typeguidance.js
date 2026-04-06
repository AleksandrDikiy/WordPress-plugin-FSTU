/**
 * JS модуля "Довідник керівних органів ФСТУ".
 * Список, пошук, пагінація, протокол і модальна форма create/edit/view.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 *
 * @package FSTU
 */

/* global fstuTypeGuidanceL10n */

jQuery( document ).ready( function ( $ ) {
	'use strict';

	if ( typeof fstuTypeGuidanceL10n === 'undefined' ) {
		return;
	}

	const $module = $( '#fstu-typeguidance' );
	if ( ! $module.length ) {
		return;
	}

	const permissions = fstuTypeGuidanceL10n.permissions || {};
	const listState = {
		page: 1,
		perPage: 10,
		search: '',
		total: 0,
		totalPages: 1,
		loading: false,
	};
	const protocolState = {
		page: 1,
		perPage: 10,
		search: '',
		total: 0,
		totalPages: 1,
		loading: false,
	};

	let isSubmittingForm = false;
	let deletingIds = [];

	bindGlobalEvents();
	bindListEvents();
	bindProtocolEvents();
	bindModalEvents();
	bindFormEvents();
	loadList();

	function bindGlobalEvents() {
		$( document ).on( 'click', function ( event ) {
			if ( ! $( event.target ).closest( '.fstu-typeguidance-dropdown' ).length ) {
				closeAllDropdowns();
			}
		} );
	}

	function bindListEvents() {
		$( document ).on( 'click', '#fstu-typeguidance-refresh-btn', function () {
			loadList();
		} );

		$( document ).on( 'input', '#fstu-typeguidance-search', debounce( function () {
			listState.search = $( this ).val().trim();
			listState.page = 1;
			loadList();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-typeguidance-per-page', function () {
			listState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'click', '#fstu-typeguidance-prev-page', function () {
			if ( listState.page > 1 ) {
				listState.page--;
				loadList();
			}
		} );

		$( document ).on( 'click', '#fstu-typeguidance-next-page', function () {
			if ( listState.page < listState.totalPages ) {
				listState.page++;
				loadList();
			}
		} );

		$( document ).on( 'click', '.fstu-typeguidance-page-btn', function () {
			const page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
			if ( page !== listState.page ) {
				listState.page = page;
				loadList();
			}
		} );

		$( document ).on( 'click', '.fstu-typeguidance-dropdown__toggle', function ( event ) {
			event.preventDefault();
			event.stopPropagation();

			const $dropdown = $( this ).closest( '.fstu-typeguidance-dropdown' );
			const isOpen = $dropdown.hasClass( 'is-open' );

			closeAllDropdowns();

			if ( ! isOpen ) {
				$dropdown.addClass( 'is-open' );
				positionDropdown( $dropdown );
				$( this ).attr( 'aria-expanded', 'true' );
			}
		} );

		$( document ).on( 'click', '#fstu-typeguidance-add-btn', function () {
			if ( permissions.canManage ) {
				openFormModal( 0, false );
			}
		} );

		$( document ).on( 'click', '.fstu-typeguidance-view-btn', function () {
			closeAllDropdowns();
			const id = parseInt( $( this ).data( 'typeguidance-id' ), 10 ) || 0;
			if ( id > 0 ) {
				openFormModal( id, true );
			}
		} );

		$( document ).on( 'click', '.fstu-typeguidance-edit-btn', function () {
			closeAllDropdowns();
			const id = parseInt( $( this ).data( 'typeguidance-id' ), 10 ) || 0;
			if ( permissions.canManage && id > 0 ) {
				openFormModal( id, false );
			}
		} );

		$( document ).on( 'click', '.fstu-typeguidance-delete-btn', function () {
			closeAllDropdowns();
			const id = parseInt( $( this ).data( 'typeguidance-id' ), 10 ) || 0;
			if ( permissions.canDelete && id > 0 ) {
				deleteItem( id );
			}
		} );
	}

	function bindProtocolEvents() {
		$( document ).on( 'click', '#fstu-typeguidance-protocol-btn', function () {
			$( '#fstu-typeguidance-main' ).addClass( 'fstu-hidden' );
			$( '#fstu-typeguidance-protocol' ).removeClass( 'fstu-hidden' );
			$( '#fstu-typeguidance-protocol-btn' ).addClass( 'fstu-hidden' );
			$( '#fstu-typeguidance-protocol-back-btn' ).removeClass( 'fstu-hidden' );
			protocolState.page = 1;
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-typeguidance-protocol-back-btn', function () {
			$( '#fstu-typeguidance-main' ).removeClass( 'fstu-hidden' );
			$( '#fstu-typeguidance-protocol' ).addClass( 'fstu-hidden' );
			$( '#fstu-typeguidance-protocol-btn' ).removeClass( 'fstu-hidden' );
			$( '#fstu-typeguidance-protocol-back-btn' ).addClass( 'fstu-hidden' );
		} );

		$( document ).on( 'input', '#fstu-typeguidance-protocol-search', debounce( function () {
			protocolState.search = $( this ).val().trim();
			protocolState.page = 1;
			loadProtocol();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-typeguidance-protocol-per-page', function () {
			protocolState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			protocolState.page = 1;
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-typeguidance-protocol-prev-page', function () {
			if ( protocolState.page > 1 ) {
				protocolState.page--;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '#fstu-typeguidance-protocol-next-page', function () {
			if ( protocolState.page < protocolState.totalPages ) {
				protocolState.page++;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '.fstu-typeguidance-protocol-page-btn', function () {
			const page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
			if ( page !== protocolState.page ) {
				protocolState.page = page;
				loadProtocol();
			}
		} );
	}

	function bindModalEvents() {
		$( document ).on( 'click', '[data-close-modal]', function () {
			closeModal( $( this ).data( 'close-modal' ) );
		} );

		$( document ).on( 'click', '.fstu-modal-overlay', function ( event ) {
			if ( $( event.target ).is( '.fstu-modal-overlay' ) ) {
				closeModal( $( this ).attr( 'id' ) );
			}
		} );

		$( document ).on( 'keydown', function ( event ) {
			if ( event.key === 'Escape' ) {
				$( '.fstu-modal-overlay:not(.fstu-hidden)' ).each( function () {
					closeModal( $( this ).attr( 'id' ) );
				} );
			}
		} );
	}

	function bindFormEvents() {
		$( document ).on( 'submit', '#fstu-typeguidance-form', function ( event ) {
			event.preventDefault();
			submitForm();
		} );
	}

	function loadList() {
		if ( listState.loading ) {
			return;
		}

		listState.loading = true;
		closeAllDropdowns();
		setTableLoading( '#fstu-typeguidance-tbody', 4, fstuTypeGuidanceL10n.messages.loading );

		$.ajax( {
			url: fstuTypeGuidanceL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_typeguidance_get_list',
				nonce: fstuTypeGuidanceL10n.nonce,
				search: listState.search,
				page: listState.page,
				per_page: listState.perPage,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				$( '#fstu-typeguidance-tbody' ).html( response.data.html );
				listState.total = parseInt( response.data.total, 10 ) || 0;
				listState.page = parseInt( response.data.page, 10 ) || 1;
				listState.perPage = parseInt( response.data.per_page, 10 ) || 10;
				listState.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				updateListPagination();
			} else {
				showTableError( '#fstu-typeguidance-tbody', 4, response.data.message || fstuTypeGuidanceL10n.messages.error );
			}
		} ).fail( function () {
			showTableError( '#fstu-typeguidance-tbody', 4, fstuTypeGuidanceL10n.messages.error );
		} ).always( function () {
			listState.loading = false;
		} );
	}

	function loadProtocol() {
		if ( protocolState.loading ) {
			return;
		}

		protocolState.loading = true;
		setTableLoading( '#fstu-typeguidance-protocol-tbody', 6, fstuTypeGuidanceL10n.messages.loading );

		$.ajax( {
			url: fstuTypeGuidanceL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_typeguidance_get_protocol',
				nonce: fstuTypeGuidanceL10n.nonce,
				search: protocolState.search,
				page: protocolState.page,
				per_page: protocolState.perPage,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				$( '#fstu-typeguidance-protocol-tbody' ).html( response.data.html );
				protocolState.total = parseInt( response.data.total, 10 ) || 0;
				protocolState.page = parseInt( response.data.page, 10 ) || 1;
				protocolState.perPage = parseInt( response.data.per_page, 10 ) || 10;
				protocolState.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				updateProtocolPagination();
			} else {
				showTableError( '#fstu-typeguidance-protocol-tbody', 6, response.data.message || fstuTypeGuidanceL10n.messages.protocolError );
			}
		} ).fail( function () {
			showTableError( '#fstu-typeguidance-protocol-tbody', 6, fstuTypeGuidanceL10n.messages.protocolError );
		} ).always( function () {
			protocolState.loading = false;
		} );
	}

	function updateListPagination() {
		$( '#fstu-typeguidance-per-page' ).val( String( listState.perPage ) );
		$( '#fstu-typeguidance-pagination-pages' ).html( buildPaginationButtons( listState.page, listState.totalPages, 'fstu-typeguidance-page-btn' ) );
		$( '#fstu-typeguidance-pagination-info' ).text( buildPaginationInfo( listState.total, listState.page, listState.totalPages ) );
		setPaginationArrowState( '#fstu-typeguidance-prev-page', '#fstu-typeguidance-next-page', listState.page, listState.totalPages );
	}

	function updateProtocolPagination() {
		$( '#fstu-typeguidance-protocol-per-page' ).val( String( protocolState.perPage ) );
		$( '#fstu-typeguidance-protocol-pagination-pages' ).html( buildPaginationButtons( protocolState.page, protocolState.totalPages, 'fstu-typeguidance-protocol-page-btn' ) );
		$( '#fstu-typeguidance-protocol-pagination-info' ).text( buildPaginationInfo( protocolState.total, protocolState.page, protocolState.totalPages ) );
		setPaginationArrowState( '#fstu-typeguidance-protocol-prev-page', '#fstu-typeguidance-protocol-next-page', protocolState.page, protocolState.totalPages );
	}

	function openFormModal( typeguidanceId, readOnly ) {
		const $form = $( '#fstu-typeguidance-form' );
		const $message = $( '#fstu-typeguidance-form-message' );
		const $title = $( '#fstu-typeguidance-form-title' );
		const $submit = $( '#fstu-typeguidance-form-submit' );
		const $inputs = $form.find( 'input[type="text"], input[type="number"]' );
		const $orderGroup = $( '.fstu-typeguidance-order-group' );

		resetForm();
		$message.addClass( 'fstu-hidden' ).empty();
		$( '#fstu-typeguidance-mode' ).val( readOnly ? 'view' : ( typeguidanceId > 0 ? 'edit' : 'create' ) );

		if ( readOnly ) {
			$title.text( fstuTypeGuidanceL10n.messages.formViewTitle );
			$submit.addClass( 'fstu-hidden' );
			$inputs.prop( 'readonly', true ).prop( 'disabled', false );
			$orderGroup.removeClass( 'fstu-hidden' );
			$( '#fstu-typeguidance-order' ).prop( 'readonly', true );
		} else if ( typeguidanceId > 0 ) {
			$title.text( fstuTypeGuidanceL10n.messages.formEditTitle );
			$submit.removeClass( 'fstu-hidden' );
			$inputs.prop( 'readonly', false );
			$orderGroup.removeClass( 'fstu-hidden' );
			$( '#fstu-typeguidance-order' ).prop( 'readonly', false );
		} else {
			$title.text( fstuTypeGuidanceL10n.messages.formAddTitle );
			$submit.removeClass( 'fstu-hidden' );
			$inputs.prop( 'readonly', false );
			$orderGroup.addClass( 'fstu-hidden' );
			$( '#fstu-typeguidance-order' ).prop( 'readonly', false );
		}

		openModal( 'fstu-typeguidance-form-modal' );

		if ( typeguidanceId <= 0 ) {
			return;
		}

		showFormMessage( fstuTypeGuidanceL10n.messages.loading, false );
		$.ajax( {
			url: fstuTypeGuidanceL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_typeguidance_get_single',
				nonce: fstuTypeGuidanceL10n.nonce,
				typeguidance_id: typeguidanceId,
			},
		} ).done( function ( response ) {
			if ( response.success && response.data.item ) {
				fillForm( response.data.item );
				$message.addClass( 'fstu-hidden' ).empty();
			} else {
				showFormMessage( response.data.message || fstuTypeGuidanceL10n.messages.error, true );
			}
		} ).fail( function () {
			showFormMessage( fstuTypeGuidanceL10n.messages.error, true );
		} );
	}

	function submitForm() {
		const mode = $( '#fstu-typeguidance-mode' ).val();
		if ( 'view' === mode || ! permissions.canManage ) {
			return;
		}

		if ( isSubmittingForm ) {
			return;
		}

		isSubmittingForm = true;
		const isEdit = parseInt( $( '#fstu-typeguidance-id' ).val(), 10 ) > 0;
		const action = isEdit ? 'fstu_typeguidance_update' : 'fstu_typeguidance_create';
		const $submit = $( '#fstu-typeguidance-form-submit' );
		$submit.prop( 'disabled', true );
		showFormMessage( fstuTypeGuidanceL10n.messages.loading, false );

		$.ajax( {
			url: fstuTypeGuidanceL10n.ajaxUrl,
			method: 'POST',
			data: $( '#fstu-typeguidance-form' ).serialize() + '&action=' + encodeURIComponent( action ),
		} ).done( function ( response ) {
			if ( response.success ) {
				showFormMessage( response.data.message || fstuTypeGuidanceL10n.messages.saveSuccess, false, true );
				loadList();
				window.setTimeout( function () {
					closeModal( 'fstu-typeguidance-form-modal' );
				}, 400 );
			} else {
				showFormMessage( response.data.message || fstuTypeGuidanceL10n.messages.saveError, true );
			}
		} ).fail( function () {
			showFormMessage( fstuTypeGuidanceL10n.messages.saveError, true );
		} ).always( function () {
			isSubmittingForm = false;
			$submit.prop( 'disabled', false );
		} );
	}

	function deleteItem( typeguidanceId ) {
		if ( ! permissions.canDelete || -1 !== deletingIds.indexOf( typeguidanceId ) ) {
			return;
		}

		if ( ! window.confirm( fstuTypeGuidanceL10n.messages.confirmDelete ) ) {
			return;
		}

		deletingIds.push( typeguidanceId );
		$.ajax( {
			url: fstuTypeGuidanceL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_typeguidance_delete',
				nonce: fstuTypeGuidanceL10n.nonce,
				typeguidance_id: typeguidanceId,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				loadList();
			} else {
				window.alert( response.data.message || fstuTypeGuidanceL10n.messages.deleteError );
			}
		} ).fail( function () {
			window.alert( fstuTypeGuidanceL10n.messages.deleteError );
		} ).always( function () {
			deletingIds = deletingIds.filter( function ( id ) {
				return id !== typeguidanceId;
			} );
		} );
	}

	function fillForm( item ) {
		$( '#fstu-typeguidance-id' ).val( item.typeguidance_id || 0 );
		$( '#fstu-typeguidance-name' ).val( item.typeguidance_name || '' );
		$( '#fstu-typeguidance-number' ).val( item.typeguidance_number || '' );
		$( '#fstu-typeguidance-order' ).val( item.typeguidance_order || 0 );
	}

	function resetForm() {
		const $form = $( '#fstu-typeguidance-form' );
		$form[ 0 ].reset();
		$( '#fstu-typeguidance-id' ).val( 0 );
		$( '#fstu-typeguidance-mode' ).val( 'create' );
		$( '#fstu-typeguidance-form-submit' ).removeClass( 'fstu-hidden' );
		$form.find( 'input[type="text"], input[type="number"]' ).prop( 'readonly', false );
		$( '.fstu-typeguidance-order-group' ).addClass( 'fstu-hidden' );
	}

	function showFormMessage( message, isError, isSuccess ) {
		const $message = $( '#fstu-typeguidance-form-message' );
		$message
			.removeClass( 'fstu-hidden fstu-message--success fstu-message--error' )
			.addClass( isError ? 'fstu-message--error' : ( isSuccess ? 'fstu-message--success' : '' ) )
			.text( message );
	}

	function closeAllDropdowns() {
		$( '.fstu-typeguidance-dropdown' ).removeClass( 'is-open is-dropup' );
		$( '.fstu-typeguidance-dropdown__toggle' ).attr( 'aria-expanded', 'false' );
	}

	function positionDropdown( $dropdown ) {
		const menu = $dropdown.find( '.fstu-typeguidance-dropdown__menu' ).get( 0 );
		const toggle = $dropdown.find( '.fstu-typeguidance-dropdown__toggle' ).get( 0 );
		if ( ! menu || ! toggle ) {
			return;
		}

		const menuHeight = menu.offsetHeight || 180;
		const rect = toggle.getBoundingClientRect();
		const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
		if ( rect.bottom + menuHeight > viewportHeight && rect.top > menuHeight ) {
			$dropdown.addClass( 'is-dropup' );
		} else {
			$dropdown.removeClass( 'is-dropup' );
		}
	}

	function openModal( modalId ) {
		$( '#' + modalId ).removeClass( 'fstu-hidden' ).attr( 'aria-hidden', 'false' );
		$( 'body' ).addClass( 'fstu-modal-open' );
	}

	function closeModal( modalId ) {
		$( '#' + modalId ).addClass( 'fstu-hidden' ).attr( 'aria-hidden', 'true' );
		if ( ! $( '.fstu-modal-overlay:not(.fstu-hidden)' ).length ) {
			$( 'body' ).removeClass( 'fstu-modal-open' );
		}
	}

	function setTableLoading( tbodySelector, colspan, message ) {
		$( tbodySelector ).html( '<tr class="fstu-row"><td colspan="' + colspan + '" class="fstu-no-results">' + escapeHtml( message ) + '</td></tr>' );
	}

	function showTableError( tbodySelector, colspan, message ) {
		$( tbodySelector ).html( '<tr class="fstu-row"><td colspan="' + colspan + '" class="fstu-no-results fstu-no-results--error">' + escapeHtml( message ) + '</td></tr>' );
	}

	function buildPaginationButtons( currentPage, totalPages, buttonClass ) {
		let html = '';
		const start = Math.max( 1, currentPage - 2 );
		const end = Math.min( totalPages, currentPage + 2 );

		if ( start > 1 ) {
			html += buildPageButton( 1, currentPage === 1, buttonClass );
			if ( start > 2 ) {
				html += '<span class="fstu-pagination__ellipsis">…</span>';
			}
		}

		for ( let page = start; page <= end; page++ ) {
			html += buildPageButton( page, page === currentPage, buttonClass );
		}

		if ( end < totalPages ) {
			if ( end < totalPages - 1 ) {
				html += '<span class="fstu-pagination__ellipsis">…</span>';
			}
			html += buildPageButton( totalPages, currentPage === totalPages, buttonClass );
		}

		return html;
	}

	function buildPageButton( page, isActive, buttonClass ) {
		const activeClass = isActive ? ' fstu-btn--page-active' : '';
		const disabled = isActive ? ' disabled' : '';
		return '<button type="button" class="fstu-btn--page ' + buttonClass + activeClass + '" data-page="' + page + '"' + disabled + '>' + page + '</button>';
	}

	function buildPaginationInfo( total, page, totalPages ) {
		return 'Записів: ' + total + ' | Сторінка ' + page + ' з ' + totalPages;
	}

	function setPaginationArrowState( prevSelector, nextSelector, page, totalPages ) {
		$( prevSelector ).prop( 'disabled', page <= 1 );
		$( nextSelector ).prop( 'disabled', page >= totalPages );
	}

	function debounce( callback, delay ) {
		let timeoutId = null;
		return function () {
			const args = arguments;
			const context = this;
			window.clearTimeout( timeoutId );
			timeoutId = window.setTimeout( function () {
				callback.apply( context, args );
			}, delay );
		};
	}

	function escapeHtml( value ) {
		return String( value )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#039;' );
	}
} );

