/**
 * JS модуля "Довідник посад у керівних органах ФСТУ".
 * Список, пошук, фільтр, пагінація, протокол і shared-модалка.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 *
 * @package FSTU
 */

/* global fstuMemberGuidanceL10n */

jQuery( document ).ready( function ( $ ) {
	'use strict';

	if ( typeof fstuMemberGuidanceL10n === 'undefined' ) {
		return;
	}

	const $module = $( '#fstu-member-guidance' );
	if ( ! $module.length ) {
		return;
	}

	const permissions = fstuMemberGuidanceL10n.permissions || {};
	if ( ! permissions.canView ) {
		return;
	}

	const listState = {
		page: 1,
		perPage: 10,
		search: '',
		typeguidanceId: parseInt( ( fstuMemberGuidanceL10n.initialState || {} ).typeguidanceId, 10 ) || 1,
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
	initialiseFilter();
	loadList();

	function bindGlobalEvents() {
		$( document ).on( 'click', function ( event ) {
			if ( ! $( event.target ).closest( '.fstu-member-guidance-dropdown' ).length ) {
				closeAllDropdowns();
			}
		} );
	}

	function bindListEvents() {
		$( document ).on( 'click', '#fstu-member-guidance-refresh-btn', function () {
			loadList();
		} );

		$( document ).on( 'change', '#fstu-member-guidance-typeguidance-filter', function () {
			listState.typeguidanceId = parseInt( $( this ).val(), 10 ) || 1;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'input', '#fstu-member-guidance-search', debounce( function () {
			listState.search = $( this ).val().trim();
			listState.page = 1;
			loadList();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-member-guidance-per-page', function () {
			listState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'click', '#fstu-member-guidance-prev-page', function () {
			if ( listState.page > 1 ) {
				listState.page--;
				loadList();
			}
		} );

		$( document ).on( 'click', '#fstu-member-guidance-next-page', function () {
			if ( listState.page < listState.totalPages ) {
				listState.page++;
				loadList();
			}
		} );

		$( document ).on( 'click', '.fstu-member-guidance-page-btn', function () {
			const page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
			if ( page !== listState.page ) {
				listState.page = page;
				loadList();
			}
		} );

		$( document ).on( 'click', '.fstu-member-guidance-dropdown__toggle', function ( event ) {
			event.preventDefault();
			event.stopPropagation();

			const $dropdown = $( this ).closest( '.fstu-member-guidance-dropdown' );
			const isOpen = $dropdown.hasClass( 'is-open' );

			closeAllDropdowns();

			if ( ! isOpen ) {
				$dropdown.addClass( 'is-open' );
				positionDropdown( $dropdown );
				$( this ).attr( 'aria-expanded', 'true' );
			}
		} );

		$( document ).on( 'click', '#fstu-member-guidance-add-btn', function () {
			if ( permissions.canManage ) {
				openFormModal( 0, false );
			}
		} );

		$( document ).on( 'click', '.fstu-member-guidance-view-btn', function () {
			closeAllDropdowns();
			const id = parseInt( $( this ).attr( 'data-member-guidance-id' ), 10 ) || 0;
			if ( id > 0 ) {
				openFormModal( id, true );
			}
		} );

		$( document ).on( 'click', '.fstu-member-guidance-edit-btn', function () {
			closeAllDropdowns();
			const id = parseInt( $( this ).attr( 'data-member-guidance-id' ), 10 ) || 0;
			if ( permissions.canManage && id > 0 ) {
				openFormModal( id, false );
			}
		} );

		$( document ).on( 'click', '.fstu-member-guidance-delete-btn', function () {
			closeAllDropdowns();
			const id = parseInt( $( this ).attr( 'data-member-guidance-id' ), 10 ) || 0;
			if ( permissions.canDelete && id > 0 ) {
				deleteItem( id );
			}
		} );
	}

	function bindProtocolEvents() {
		$( document ).on( 'click', '#fstu-member-guidance-protocol-btn', function () {
			$( '#fstu-member-guidance-main' ).addClass( 'fstu-hidden' );
			$( '#fstu-member-guidance-protocol' ).removeClass( 'fstu-hidden' );
			$( '#fstu-member-guidance-protocol-btn' ).addClass( 'fstu-hidden' );
			$( '#fstu-member-guidance-protocol-back-btn' ).removeClass( 'fstu-hidden' );
			protocolState.page = 1;
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-member-guidance-protocol-back-btn', function () {
			$( '#fstu-member-guidance-main' ).removeClass( 'fstu-hidden' );
			$( '#fstu-member-guidance-protocol' ).addClass( 'fstu-hidden' );
			$( '#fstu-member-guidance-protocol-btn' ).removeClass( 'fstu-hidden' );
			$( '#fstu-member-guidance-protocol-back-btn' ).addClass( 'fstu-hidden' );
		} );

		$( document ).on( 'input', '#fstu-member-guidance-protocol-search', debounce( function () {
			protocolState.search = $( this ).val().trim();
			protocolState.page = 1;
			loadProtocol();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-member-guidance-protocol-per-page', function () {
			protocolState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			protocolState.page = 1;
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-member-guidance-protocol-prev-page', function () {
			if ( protocolState.page > 1 ) {
				protocolState.page--;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '#fstu-member-guidance-protocol-next-page', function () {
			if ( protocolState.page < protocolState.totalPages ) {
				protocolState.page++;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '.fstu-member-guidance-protocol-page-btn', function () {
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
		$( document ).on( 'submit', '#fstu-member-guidance-form', function ( event ) {
			event.preventDefault();
			submitForm();
		} );
	}

	function initialiseFilter() {
		$( '#fstu-member-guidance-typeguidance-filter' ).val( String( listState.typeguidanceId ) );
	}

	function loadList() {
		if ( listState.loading ) {
			return;
		}

		listState.loading = true;
		closeAllDropdowns();
		setTableLoading( '#fstu-member-guidance-tbody', 4, fstuMemberGuidanceL10n.messages.loading );

		$.ajax( {
			url: fstuMemberGuidanceL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_member_guidance_get_list',
				nonce: fstuMemberGuidanceL10n.nonce,
				search: listState.search,
				typeguidance_id: listState.typeguidanceId,
				page: listState.page,
				per_page: listState.perPage,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				$( '#fstu-member-guidance-tbody' ).html( response.data.html );
				listState.total = parseInt( response.data.total, 10 ) || 0;
				listState.page = parseInt( response.data.page, 10 ) || 1;
				listState.perPage = parseInt( response.data.per_page, 10 ) || 10;
				listState.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				listState.typeguidanceId = parseInt( response.data.typeguidance_id, 10 ) || listState.typeguidanceId;
				updateListPagination();
				$( '#fstu-member-guidance-typeguidance-filter' ).val( String( listState.typeguidanceId ) );
			} else {
				showTableError( '#fstu-member-guidance-tbody', 4, response.data.message || fstuMemberGuidanceL10n.messages.error );
			}
		} ).fail( function () {
			showTableError( '#fstu-member-guidance-tbody', 4, fstuMemberGuidanceL10n.messages.error );
		} ).always( function () {
			listState.loading = false;
		} );
	}

	function loadProtocol() {
		if ( protocolState.loading ) {
			return;
		}

		protocolState.loading = true;
		setTableLoading( '#fstu-member-guidance-protocol-tbody', 6, fstuMemberGuidanceL10n.messages.loading );

		$.ajax( {
			url: fstuMemberGuidanceL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_member_guidance_get_protocol',
				nonce: fstuMemberGuidanceL10n.nonce,
				search: protocolState.search,
				page: protocolState.page,
				per_page: protocolState.perPage,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				$( '#fstu-member-guidance-protocol-tbody' ).html( response.data.html );
				protocolState.total = parseInt( response.data.total, 10 ) || 0;
				protocolState.page = parseInt( response.data.page, 10 ) || 1;
				protocolState.perPage = parseInt( response.data.per_page, 10 ) || 10;
				protocolState.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				updateProtocolPagination();
			} else {
				showTableError( '#fstu-member-guidance-protocol-tbody', 6, response.data.message || fstuMemberGuidanceL10n.messages.protocolError );
			}
		} ).fail( function () {
			showTableError( '#fstu-member-guidance-protocol-tbody', 6, fstuMemberGuidanceL10n.messages.protocolError );
		} ).always( function () {
			protocolState.loading = false;
		} );
	}

	function updateListPagination() {
		$( '#fstu-member-guidance-per-page' ).val( String( listState.perPage ) );
		$( '#fstu-member-guidance-pagination-pages' ).html( buildPaginationButtons( listState.page, listState.totalPages, 'fstu-member-guidance-page-btn' ) );
		$( '#fstu-member-guidance-pagination-info' ).text( buildPaginationInfo( listState.total, listState.page, listState.totalPages ) );
		setPaginationArrowState( '#fstu-member-guidance-prev-page', '#fstu-member-guidance-next-page', listState.page, listState.totalPages );
	}

	function updateProtocolPagination() {
		$( '#fstu-member-guidance-protocol-per-page' ).val( String( protocolState.perPage ) );
		$( '#fstu-member-guidance-protocol-pagination-pages' ).html( buildPaginationButtons( protocolState.page, protocolState.totalPages, 'fstu-member-guidance-protocol-page-btn' ) );
		$( '#fstu-member-guidance-protocol-pagination-info' ).text( buildPaginationInfo( protocolState.total, protocolState.page, protocolState.totalPages ) );
		setPaginationArrowState( '#fstu-member-guidance-protocol-prev-page', '#fstu-member-guidance-protocol-next-page', protocolState.page, protocolState.totalPages );
	}

	function openFormModal( memberGuidanceId, readOnly ) {
		const $form = $( '#fstu-member-guidance-form' );
		const $message = $( '#fstu-member-guidance-form-message' );
		const $title = $( '#fstu-member-guidance-form-title' );
		const $submit = $( '#fstu-member-guidance-form-submit' );

		resetForm();
		$message.addClass( 'fstu-hidden' ).empty();
		$( '#fstu-member-guidance-mode' ).val( readOnly ? 'view' : ( memberGuidanceId > 0 ? 'edit' : 'create' ) );
		$( '#fstu-member-guidance-typeguidance-id' ).val( String( listState.typeguidanceId ) );

		if ( readOnly ) {
			$title.text( fstuMemberGuidanceL10n.messages.formViewTitle );
			$submit.addClass( 'fstu-hidden' );
			setFormReadonly( $form, true );
		} else if ( memberGuidanceId > 0 ) {
			$title.text( fstuMemberGuidanceL10n.messages.formEditTitle );
			$submit.removeClass( 'fstu-hidden' );
			setFormReadonly( $form, false );
		} else {
			$title.text( fstuMemberGuidanceL10n.messages.formAddTitle );
			$submit.removeClass( 'fstu-hidden' );
			setFormReadonly( $form, false );
		}

		openModal( 'fstu-member-guidance-form-modal' );

		if ( memberGuidanceId <= 0 ) {
			return;
		}

		showFormMessage( fstuMemberGuidanceL10n.messages.loading, false );
		$.ajax( {
			url: fstuMemberGuidanceL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_member_guidance_get_single',
				nonce: fstuMemberGuidanceL10n.nonce,
				member_guidance_id: memberGuidanceId,
			},
		} ).done( function ( response ) {
			if ( response.success && response.data.item ) {
				fillForm( response.data.item );
				$message.addClass( 'fstu-hidden' ).empty();
			} else {
				showFormMessage( response.data.message || fstuMemberGuidanceL10n.messages.error, true );
			}
		} ).fail( function () {
			showFormMessage( fstuMemberGuidanceL10n.messages.error, true );
		} );
	}

	function submitForm() {
		const mode = $( '#fstu-member-guidance-mode' ).val();
		if ( 'view' === mode || ! permissions.canManage ) {
			return;
		}

		if ( isSubmittingForm ) {
			return;
		}

		isSubmittingForm = true;
		const isEdit = parseInt( $( '#fstu-member-guidance-id' ).val(), 10 ) > 0;
		const action = isEdit ? 'fstu_member_guidance_update' : 'fstu_member_guidance_create';
		const $submit = $( '#fstu-member-guidance-form-submit' );
		$submit.prop( 'disabled', true );
		showFormMessage( fstuMemberGuidanceL10n.messages.loading, false );

		$.ajax( {
			url: fstuMemberGuidanceL10n.ajaxUrl,
			method: 'POST',
			data: $( '#fstu-member-guidance-form' ).serialize() + '&action=' + encodeURIComponent( action ),
		} ).done( function ( response ) {
			if ( response.success ) {
				showFormMessage( response.data.message || fstuMemberGuidanceL10n.messages.saveSuccess, false, true );
				loadList();
				window.setTimeout( function () {
					closeModal( 'fstu-member-guidance-form-modal' );
				}, 400 );
			} else {
				showFormMessage( response.data.message || fstuMemberGuidanceL10n.messages.saveError, true );
			}
		} ).fail( function () {
			showFormMessage( fstuMemberGuidanceL10n.messages.saveError, true );
		} ).always( function () {
			isSubmittingForm = false;
			$submit.prop( 'disabled', false );
		} );
	}

	function deleteItem( memberGuidanceId ) {
		if ( ! permissions.canDelete || -1 !== deletingIds.indexOf( memberGuidanceId ) ) {
			return;
		}

		if ( ! window.confirm( fstuMemberGuidanceL10n.messages.confirmDelete ) ) {
			return;
		}

		deletingIds.push( memberGuidanceId );
		$.ajax( {
			url: fstuMemberGuidanceL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_member_guidance_delete',
				nonce: fstuMemberGuidanceL10n.nonce,
				member_guidance_id: memberGuidanceId,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				loadList();
			} else {
				window.alert( response.data.message || fstuMemberGuidanceL10n.messages.deleteError );
			}
		} ).fail( function () {
			window.alert( fstuMemberGuidanceL10n.messages.deleteError );
		} ).always( function () {
			deletingIds = deletingIds.filter( function ( id ) {
				return id !== memberGuidanceId;
			} );
		} );
	}

	function fillForm( item ) {
		$( '#fstu-member-guidance-id' ).val( item.member_guidance_id || 0 );
		$( '#fstu-member-guidance-name' ).val( item.member_guidance_name || '' );
		$( '#fstu-member-guidance-order' ).val( item.member_guidance_order || 0 );
		$( '#fstu-member-guidance-typeguidance-id' ).val( String( item.typeguidance_id || listState.typeguidanceId || 1 ) );
	}

	function resetForm() {
		const $form = $( '#fstu-member-guidance-form' );
		if ( $form.length && $form.get( 0 ) ) {
			$form.get( 0 ).reset();
		}
		$( '#fstu-member-guidance-id' ).val( 0 );
		$( '#fstu-member-guidance-mode' ).val( 'create' );
		$( '#fstu-member-guidance-form-submit' ).removeClass( 'fstu-hidden' );
		setFormReadonly( $form, false );
		$( '#fstu-member-guidance-typeguidance-id' ).val( String( listState.typeguidanceId ) );
	}

	function setFormReadonly( $form, isReadonly ) {
		$form.find( 'input[type="text"], input[type="number"]' ).prop( 'readonly', isReadonly );
		$form.find( 'select' ).prop( 'disabled', isReadonly );
		$form.find( 'input[type="hidden"]' ).prop( 'disabled', false );
		$form.find( '#fstu-member-guidance-website' ).prop( 'disabled', false );
	}

	function showFormMessage( message, isError, isSuccess ) {
		const $message = $( '#fstu-member-guidance-form-message' );
		$message
			.removeClass( 'fstu-hidden fstu-message--success fstu-message--error' )
			.addClass( isError ? 'fstu-message--error' : ( isSuccess ? 'fstu-message--success' : '' ) )
			.text( message );
	}

	function closeAllDropdowns() {
		$( '.fstu-member-guidance-dropdown' ).removeClass( 'is-open is-dropup' );
		$( '.fstu-member-guidance-dropdown__toggle' ).attr( 'aria-expanded', 'false' );
	}

	function positionDropdown( $dropdown ) {
		const menu = $dropdown.find( '.fstu-member-guidance-dropdown__menu' ).get( 0 );
		const toggle = $dropdown.find( '.fstu-member-guidance-dropdown__toggle' ).get( 0 );
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

