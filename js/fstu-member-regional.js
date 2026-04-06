/**
 * JS модуля "Довідник посад федерацій".
 * Список, пошук, пагінація, протокол і модальна форма create/edit/view.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 *
 * @package FSTU
 */

/* global fstuMemberRegionalL10n */

jQuery( document ).ready( function ( $ ) {
	'use strict';

	if ( typeof fstuMemberRegionalL10n === 'undefined' ) {
		return;
	}

	const $module = $( '#fstu-member-regional' );
	if ( ! $module.length ) {
		return;
	}

	const permissions = fstuMemberRegionalL10n.permissions || {};
	if ( ! permissions.canView ) {
		return;
	}

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
			if ( ! $( event.target ).closest( '.fstu-member-regional-dropdown' ).length ) {
				closeAllDropdowns();
			}
		} );
	}

	function bindListEvents() {
		$( document ).on( 'click', '#fstu-member-regional-refresh-btn', function () {
			loadList();
		} );

		$( document ).on( 'input', '#fstu-member-regional-search', debounce( function () {
			listState.search = $( this ).val().trim();
			listState.page = 1;
			loadList();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-member-regional-per-page', function () {
			listState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'click', '#fstu-member-regional-prev-page', function () {
			if ( listState.page > 1 ) {
				listState.page--;
				loadList();
			}
		} );

		$( document ).on( 'click', '#fstu-member-regional-next-page', function () {
			if ( listState.page < listState.totalPages ) {
				listState.page++;
				loadList();
			}
		} );

		$( document ).on( 'click', '.fstu-member-regional-page-btn', function () {
			const page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
			if ( page !== listState.page ) {
				listState.page = page;
				loadList();
			}
		} );

		$( document ).on( 'click', '.fstu-member-regional-dropdown__toggle', function ( event ) {
			event.preventDefault();
			event.stopPropagation();

			const $dropdown = $( this ).closest( '.fstu-member-regional-dropdown' );
			const isOpen = $dropdown.hasClass( 'is-open' );

			closeAllDropdowns();

			if ( ! isOpen ) {
				$dropdown.addClass( 'is-open' );
				positionDropdown( $dropdown );
				$( this ).attr( 'aria-expanded', 'true' );
			}
		} );

		$( document ).on( 'click', '#fstu-member-regional-add-btn', function () {
			if ( permissions.canManage ) {
				openFormModal( 0, false );
			}
		} );

		$( document ).on( 'click', '.fstu-member-regional-view-btn', function () {
			closeAllDropdowns();
			const id = parseInt( $( this ).attr( 'data-member-regional-id' ), 10 ) || 0;
			if ( id > 0 ) {
				openFormModal( id, true );
			}
		} );

		$( document ).on( 'click', '.fstu-member-regional-edit-btn', function () {
			closeAllDropdowns();
			const id = parseInt( $( this ).attr( 'data-member-regional-id' ), 10 ) || 0;
			if ( permissions.canManage && id > 0 ) {
				openFormModal( id, false );
			}
		} );

		$( document ).on( 'click', '.fstu-member-regional-delete-btn', function () {
			closeAllDropdowns();
			const id = parseInt( $( this ).attr( 'data-member-regional-id' ), 10 ) || 0;
			if ( permissions.canDelete && id > 0 ) {
				deleteItem( id );
			}
		} );
	}

	function bindProtocolEvents() {
		$( document ).on( 'click', '#fstu-member-regional-protocol-btn', function () {
			$( '#fstu-member-regional-main' ).addClass( 'fstu-hidden' );
			$( '#fstu-member-regional-protocol' ).removeClass( 'fstu-hidden' );
			$( '#fstu-member-regional-protocol-btn' ).addClass( 'fstu-hidden' );
			$( '#fstu-member-regional-protocol-back-btn' ).removeClass( 'fstu-hidden' );
			protocolState.page = 1;
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-member-regional-protocol-back-btn', function () {
			$( '#fstu-member-regional-main' ).removeClass( 'fstu-hidden' );
			$( '#fstu-member-regional-protocol' ).addClass( 'fstu-hidden' );
			$( '#fstu-member-regional-protocol-btn' ).removeClass( 'fstu-hidden' );
			$( '#fstu-member-regional-protocol-back-btn' ).addClass( 'fstu-hidden' );
		} );

		$( document ).on( 'input', '#fstu-member-regional-protocol-search', debounce( function () {
			protocolState.search = $( this ).val().trim();
			protocolState.page = 1;
			loadProtocol();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-member-regional-protocol-per-page', function () {
			protocolState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			protocolState.page = 1;
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-member-regional-protocol-prev-page', function () {
			if ( protocolState.page > 1 ) {
				protocolState.page--;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '#fstu-member-regional-protocol-next-page', function () {
			if ( protocolState.page < protocolState.totalPages ) {
				protocolState.page++;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '.fstu-member-regional-protocol-page-btn', function () {
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
		$( document ).on( 'submit', '#fstu-member-regional-form', function ( event ) {
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
		setTableLoading( '#fstu-member-regional-tbody', getTableColspan(), fstuMemberRegionalL10n.messages.loading );

		$.ajax( {
			url: fstuMemberRegionalL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_member_regional_get_list',
				nonce: fstuMemberRegionalL10n.nonce,
				search: listState.search,
				page: listState.page,
				per_page: listState.perPage,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				$( '#fstu-member-regional-tbody' ).html( response.data.html );
				listState.total = parseInt( response.data.total, 10 ) || 0;
				listState.page = parseInt( response.data.page, 10 ) || 1;
				listState.perPage = parseInt( response.data.per_page, 10 ) || 10;
				listState.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				updateListPagination();
			} else {
				showTableError( '#fstu-member-regional-tbody', getTableColspan(), response.data.message || fstuMemberRegionalL10n.messages.error );
			}
		} ).fail( function () {
			showTableError( '#fstu-member-regional-tbody', getTableColspan(), fstuMemberRegionalL10n.messages.error );
		} ).always( function () {
			listState.loading = false;
		} );
	}

	function loadProtocol() {
		if ( protocolState.loading ) {
			return;
		}

		protocolState.loading = true;
		setTableLoading( '#fstu-member-regional-protocol-tbody', 6, fstuMemberRegionalL10n.messages.loading );

		$.ajax( {
			url: fstuMemberRegionalL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_member_regional_get_protocol',
				nonce: fstuMemberRegionalL10n.nonce,
				search: protocolState.search,
				page: protocolState.page,
				per_page: protocolState.perPage,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				$( '#fstu-member-regional-protocol-tbody' ).html( response.data.html );
				protocolState.total = parseInt( response.data.total, 10 ) || 0;
				protocolState.page = parseInt( response.data.page, 10 ) || 1;
				protocolState.perPage = parseInt( response.data.per_page, 10 ) || 10;
				protocolState.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				updateProtocolPagination();
			} else {
				showTableError( '#fstu-member-regional-protocol-tbody', 6, response.data.message || fstuMemberRegionalL10n.messages.protocolError );
			}
		} ).fail( function () {
			showTableError( '#fstu-member-regional-protocol-tbody', 6, fstuMemberRegionalL10n.messages.protocolError );
		} ).always( function () {
			protocolState.loading = false;
		} );
	}

	function updateListPagination() {
		$( '#fstu-member-regional-per-page' ).val( String( listState.perPage ) );
		$( '#fstu-member-regional-pagination-pages' ).html( buildPaginationButtons( listState.page, listState.totalPages, 'fstu-member-regional-page-btn' ) );
		$( '#fstu-member-regional-pagination-info' ).text( buildPaginationInfo( listState.total, listState.page, listState.totalPages ) );
		setPaginationArrowState( '#fstu-member-regional-prev-page', '#fstu-member-regional-next-page', listState.page, listState.totalPages );
	}

	function updateProtocolPagination() {
		$( '#fstu-member-regional-protocol-per-page' ).val( String( protocolState.perPage ) );
		$( '#fstu-member-regional-protocol-pagination-pages' ).html( buildPaginationButtons( protocolState.page, protocolState.totalPages, 'fstu-member-regional-protocol-page-btn' ) );
		$( '#fstu-member-regional-protocol-pagination-info' ).text( buildPaginationInfo( protocolState.total, protocolState.page, protocolState.totalPages ) );
		setPaginationArrowState( '#fstu-member-regional-protocol-prev-page', '#fstu-member-regional-protocol-next-page', protocolState.page, protocolState.totalPages );
	}

	function openFormModal( memberRegionalId, readOnly ) {
		const $form = $( '#fstu-member-regional-form' );
		const $message = $( '#fstu-member-regional-form-message' );
		const $title = $( '#fstu-member-regional-form-title' );
		const $submit = $( '#fstu-member-regional-form-submit' );
		const $inputs = $form.find( 'input[type="text"], input[type="number"]' );

		resetForm();
		$message.addClass( 'fstu-hidden' ).empty();
		$( '#fstu-member-regional-mode' ).val( readOnly ? 'view' : ( memberRegionalId > 0 ? 'edit' : 'create' ) );

		if ( readOnly ) {
			$title.text( fstuMemberRegionalL10n.messages.formViewTitle );
			$submit.addClass( 'fstu-hidden' );
			$inputs.prop( 'readonly', true ).prop( 'disabled', false );
		} else if ( memberRegionalId > 0 ) {
			$title.text( fstuMemberRegionalL10n.messages.formEditTitle );
			$submit.removeClass( 'fstu-hidden' );
			$inputs.prop( 'readonly', false );
		} else {
			$title.text( fstuMemberRegionalL10n.messages.formAddTitle );
			$submit.removeClass( 'fstu-hidden' );
			$inputs.prop( 'readonly', false );
		}

		openModal( 'fstu-member-regional-form-modal' );

		if ( memberRegionalId <= 0 ) {
			return;
		}

		showFormMessage( fstuMemberRegionalL10n.messages.loading, false );
		$.ajax( {
			url: fstuMemberRegionalL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_member_regional_get_single',
				nonce: fstuMemberRegionalL10n.nonce,
				member_regional_id: memberRegionalId,
			},
		} ).done( function ( response ) {
			if ( response.success && response.data.item ) {
				fillForm( response.data.item );
				$message.addClass( 'fstu-hidden' ).empty();
			} else {
				showFormMessage( response.data.message || fstuMemberRegionalL10n.messages.error, true );
			}
		} ).fail( function () {
			showFormMessage( fstuMemberRegionalL10n.messages.error, true );
		} );
	}

	function submitForm() {
		const mode = $( '#fstu-member-regional-mode' ).val();
		if ( 'view' === mode || ! permissions.canManage ) {
			return;
		}

		if ( isSubmittingForm ) {
			return;
		}

		isSubmittingForm = true;
		const isEdit = parseInt( $( '#fstu-member-regional-id' ).val(), 10 ) > 0;
		const action = isEdit ? 'fstu_member_regional_update' : 'fstu_member_regional_create';
		const $submit = $( '#fstu-member-regional-form-submit' );
		$submit.prop( 'disabled', true );
		showFormMessage( fstuMemberRegionalL10n.messages.loading, false );

		$.ajax( {
			url: fstuMemberRegionalL10n.ajaxUrl,
			method: 'POST',
			data: $( '#fstu-member-regional-form' ).serialize() + '&action=' + encodeURIComponent( action ),
		} ).done( function ( response ) {
			if ( response.success ) {
				showFormMessage( response.data.message || fstuMemberRegionalL10n.messages.saveSuccess, false, true );
				loadList();
				window.setTimeout( function () {
					closeModal( 'fstu-member-regional-form-modal' );
				}, 400 );
			} else {
				showFormMessage( response.data.message || fstuMemberRegionalL10n.messages.saveError, true );
			}
		} ).fail( function () {
			showFormMessage( fstuMemberRegionalL10n.messages.saveError, true );
		} ).always( function () {
			isSubmittingForm = false;
			$submit.prop( 'disabled', false );
		} );
	}

	function deleteItem( memberRegionalId ) {
		if ( ! permissions.canDelete || -1 !== deletingIds.indexOf( memberRegionalId ) ) {
			return;
		}

		if ( ! window.confirm( fstuMemberRegionalL10n.messages.confirmDelete ) ) {
			return;
		}

		deletingIds.push( memberRegionalId );
		$.ajax( {
			url: fstuMemberRegionalL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_member_regional_delete',
				nonce: fstuMemberRegionalL10n.nonce,
				member_regional_id: memberRegionalId,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				loadList();
			} else {
				window.alert( response.data.message || fstuMemberRegionalL10n.messages.deleteError );
			}
		} ).fail( function () {
			window.alert( fstuMemberRegionalL10n.messages.deleteError );
		} ).always( function () {
			deletingIds = deletingIds.filter( function ( id ) {
				return id !== memberRegionalId;
			} );
		} );
	}

	function fillForm( item ) {
		$( '#fstu-member-regional-id' ).val( item.member_regional_id || 0 );
		$( '#fstu-member-regional-name' ).val( item.member_regional_name || '' );
		$( '#fstu-member-regional-order' ).val( item.member_regional_order || 0 );

		const hasMeta = !!( item.member_regional_date_create || item.member_regional_fio );
		if ( hasMeta ) {
			$( '#fstu-member-regional-meta-date' ).text( item.member_regional_date_create || '—' );
			$( '#fstu-member-regional-meta-user' ).text( item.member_regional_fio || '—' );
			$( '#fstu-member-regional-meta' ).removeClass( 'fstu-hidden' );
		}
	}

	function resetForm() {
		const $form = $( '#fstu-member-regional-form' );
		if ( $form.length && $form.get( 0 ) ) {
			$form.get( 0 ).reset();
		}
		$( '#fstu-member-regional-id' ).val( 0 );
		$( '#fstu-member-regional-mode' ).val( 'create' );
		$( '#fstu-member-regional-form-submit' ).removeClass( 'fstu-hidden' );
		$form.find( 'input[type="text"], input[type="number"]' ).prop( 'readonly', false );
		$( '#fstu-member-regional-meta' ).addClass( 'fstu-hidden' );
		$( '#fstu-member-regional-meta-date' ).text( '—' );
		$( '#fstu-member-regional-meta-user' ).text( '—' );
	}

	function showFormMessage( message, isError, isSuccess ) {
		const $message = $( '#fstu-member-regional-form-message' );
		$message
			.removeClass( 'fstu-hidden fstu-message--success fstu-message--error' )
			.addClass( isError ? 'fstu-message--error' : ( isSuccess ? 'fstu-message--success' : '' ) )
			.text( message );
	}

	function closeAllDropdowns() {
		$( '.fstu-member-regional-dropdown' ).removeClass( 'is-open is-dropup' );
		$( '.fstu-member-regional-dropdown__toggle' ).attr( 'aria-expanded', 'false' );
	}

	function positionDropdown( $dropdown ) {
		const menu = $dropdown.find( '.fstu-member-regional-dropdown__menu' ).get( 0 );
		const toggle = $dropdown.find( '.fstu-member-regional-dropdown__toggle' ).get( 0 );
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

	function getTableColspan() {
		return permissions.canAdminMeta ? 5 : 3;
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

