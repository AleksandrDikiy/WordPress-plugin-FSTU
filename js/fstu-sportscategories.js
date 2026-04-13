/**
 * JS модуля "Довідник спортивних розрядів".
 * Список, пошук, пагінація, dropdown,
 * модальні вікна, CRUD-операції та протокол.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-13
 *
 * @package FSTU
 */

/* global fstuSportsCategoriesL10n */

jQuery( document ).ready( function ( $ ) {
	'use strict';

	if ( typeof fstuSportsCategoriesL10n === 'undefined' ) {
		return;
	}

	const $module = $( '#fstu-sportscategories' );
	if ( ! $module.length ) {
		return;
	}

	const permissions = fstuSportsCategoriesL10n.permissions || {};
	const defaults = fstuSportsCategoriesL10n.defaults || {};
	const listState = {
		page: 1,
		perPage: parseInt( defaults.perPage, 10 ) || 10,
		search: '',
		total: 0,
		totalPages: 1,
		loading: false,
	};
	const protocolState = {
		page: 1,
		perPage: parseInt( defaults.protocolPerPage, 10 ) || 10,
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
			if ( ! $( event.target ).closest( '.fstu-sportscategories-dropdown' ).length ) {
				closeAllDropdowns();
			}
		} );

		$( window ).on( 'scroll resize', function () {
			closeAllDropdowns();
		} );
	}

	function bindListEvents() {
		$( document ).on( 'input', '#fstu-sportscategories-search', debounce( function () {
			listState.search = $( this ).val().trim();
			listState.page = 1;
			loadList();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-sportscategories-per-page', function () {
			listState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'click', '#fstu-sportscategories-prev-page', function () {
			if ( listState.page > 1 ) {
				listState.page--;
				loadList();
			}
		} );

		$( document ).on( 'click', '#fstu-sportscategories-next-page', function () {
			if ( listState.page < listState.totalPages ) {
				listState.page++;
				loadList();
			}
		} );

		$( document ).on( 'click', '.fstu-sportscategories-page-btn', function () {
			const page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
			if ( page !== listState.page ) {
				listState.page = page;
				loadList();
			}
		} );

		$( document ).on( 'click', '.fstu-sportscategories-dropdown__toggle', function ( event ) {
			event.preventDefault();
			event.stopPropagation();

			const $dropdown = $( this ).closest( '.fstu-sportscategories-dropdown' );
			const isOpen = $dropdown.hasClass( 'is-open' );
			closeAllDropdowns();

			if ( ! isOpen ) {
				$dropdown.addClass( 'is-open' );
				positionDropdown( $dropdown );
				$( this ).attr( 'aria-expanded', 'true' );
			}
		} );

		$( document ).on( 'click', '#fstu-sportscategories-add-btn', function () {
			openFormModal();
		} );

		$( document ).on( 'click', '.fstu-sportscategories-view-btn', function () {
			closeAllDropdowns();
			const itemId = parseInt( $( this ).data( 'sportscategory-id' ), 10 ) || 0;
			if ( itemId > 0 ) {
				openViewModal( itemId );
			}
		} );

		$( document ).on( 'click', '.fstu-sportscategories-edit-btn', function () {
			closeAllDropdowns();
			const itemId = parseInt( $( this ).data( 'sportscategory-id' ), 10 ) || 0;
			if ( itemId > 0 ) {
				openFormModal( itemId );
			}
		} );

		$( document ).on( 'click', '.fstu-sportscategories-delete-btn', function () {
			closeAllDropdowns();
			const itemId = parseInt( $( this ).data( 'sportscategory-id' ), 10 ) || 0;
			if ( itemId > 0 ) {
				deleteItem( itemId );
			}
		} );
	}

	function bindProtocolEvents() {
		$( document ).on( 'click', '#fstu-sportscategories-protocol-btn', function () {
			$( '#fstu-sportscategories-main' ).addClass( 'fstu-hidden' );
			$( '#fstu-sportscategories-protocol' ).removeClass( 'fstu-hidden' );
			$( '#fstu-sportscategories-add-btn' ).addClass( 'fstu-hidden' );
			$( '#fstu-sportscategories-protocol-btn' ).addClass( 'fstu-hidden' );
			$( '#fstu-sportscategories-protocol-back-btn' ).removeClass( 'fstu-hidden' );
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-sportscategories-protocol-back-btn', function () {
			$( '#fstu-sportscategories-main' ).removeClass( 'fstu-hidden' );
			$( '#fstu-sportscategories-protocol' ).addClass( 'fstu-hidden' );
			$( '#fstu-sportscategories-add-btn' ).removeClass( 'fstu-hidden' );
			$( '#fstu-sportscategories-protocol-btn' ).removeClass( 'fstu-hidden' );
			$( '#fstu-sportscategories-protocol-back-btn' ).addClass( 'fstu-hidden' );
		} );

		$( document ).on( 'input', '#fstu-sportscategories-protocol-search', debounce( function () {
			protocolState.search = $( this ).val().trim();
			protocolState.page = 1;
			loadProtocol();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-sportscategories-protocol-per-page', function () {
			protocolState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			protocolState.page = 1;
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-sportscategories-protocol-prev-page', function () {
			if ( protocolState.page > 1 ) {
				protocolState.page--;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '#fstu-sportscategories-protocol-next-page', function () {
			if ( protocolState.page < protocolState.totalPages ) {
				protocolState.page++;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '.fstu-sportscategories-protocol-page-btn', function () {
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
		$( document ).on( 'submit', '#fstu-sportscategories-form', function ( event ) {
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
		setTableLoading( '#fstu-sportscategories-tbody', 3, fstuSportsCategoriesL10n.messages.loading );

		$.ajax( {
			url: fstuSportsCategoriesL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_sportscategories_get_list',
				nonce: fstuSportsCategoriesL10n.nonce,
				search: listState.search,
				page: listState.page,
				per_page: listState.perPage,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				$( '#fstu-sportscategories-tbody' ).html( response.data.html );
				listState.total = parseInt( response.data.total, 10 ) || 0;
				listState.page = parseInt( response.data.page, 10 ) || 1;
				listState.perPage = parseInt( response.data.per_page, 10 ) || 10;
				listState.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				updateListPagination();
			} else {
				showTableError( '#fstu-sportscategories-tbody', 3, response.data.message || fstuSportsCategoriesL10n.messages.error );
			}
		} ).fail( function () {
			showTableError( '#fstu-sportscategories-tbody', 3, fstuSportsCategoriesL10n.messages.error );
		} ).always( function () {
			listState.loading = false;
		} );
	}

	function loadProtocol() {
		if ( protocolState.loading ) {
			return;
		}

		protocolState.loading = true;
		setTableLoading( '#fstu-sportscategories-protocol-tbody', 6, fstuSportsCategoriesL10n.messages.loading );

		$.ajax( {
			url: fstuSportsCategoriesL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_sportscategories_get_protocol',
				nonce: fstuSportsCategoriesL10n.nonce,
				search: protocolState.search,
				page: protocolState.page,
				per_page: protocolState.perPage,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				$( '#fstu-sportscategories-protocol-tbody' ).html( response.data.html );
				protocolState.total = parseInt( response.data.total, 10 ) || 0;
				protocolState.page = parseInt( response.data.page, 10 ) || 1;
				protocolState.perPage = parseInt( response.data.per_page, 10 ) || 10;
				protocolState.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				updateProtocolPagination();
			} else {
				showTableError( '#fstu-sportscategories-protocol-tbody', 6, response.data.message || fstuSportsCategoriesL10n.messages.protocolError );
			}
		} ).fail( function () {
			showTableError( '#fstu-sportscategories-protocol-tbody', 6, fstuSportsCategoriesL10n.messages.protocolError );
		} ).always( function () {
			protocolState.loading = false;
		} );
	}

	function updateListPagination() {
		$( '#fstu-sportscategories-per-page' ).val( String( listState.perPage ) );
		$( '#fstu-sportscategories-pagination-pages' ).html( buildPaginationButtons( listState.page, listState.totalPages, 'fstu-sportscategories-page-btn' ) );
		$( '#fstu-sportscategories-pagination-info' ).text( buildPaginationInfo( listState.total, listState.page, listState.totalPages ) );
		setPaginationArrowState( '#fstu-sportscategories-prev-page', '#fstu-sportscategories-next-page', listState.page, listState.totalPages );
	}

	function updateProtocolPagination() {
		$( '#fstu-sportscategories-protocol-per-page' ).val( String( protocolState.perPage ) );
		$( '#fstu-sportscategories-protocol-pagination-pages' ).html( buildPaginationButtons( protocolState.page, protocolState.totalPages, 'fstu-sportscategories-protocol-page-btn' ) );
		$( '#fstu-sportscategories-protocol-pagination-info' ).text( buildPaginationInfo( protocolState.total, protocolState.page, protocolState.totalPages ) );
		setPaginationArrowState( '#fstu-sportscategories-protocol-prev-page', '#fstu-sportscategories-protocol-next-page', protocolState.page, protocolState.totalPages );
	}

	function openViewModal( itemId ) {
		const $viewBody = $( '#fstu-sportscategories-view-body' );
		const $viewFooter = $( '#fstu-sportscategories-view-footer' );

		$viewBody.html( '<p class="fstu-loader-inline">' + escapeHtml( fstuSportsCategoriesL10n.messages.loading ) + '</p>' );
		$viewFooter.empty();
		openModal( 'fstu-sportscategories-view-modal' );

		$.ajax( {
			url: fstuSportsCategoriesL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_sportscategories_get_single',
				nonce: fstuSportsCategoriesL10n.nonce,
				sportscategory_id: itemId,
			},
		} ).done( function ( response ) {
			if ( ! response.success ) {
				$viewBody.html( '<p class="fstu-alert">' + escapeHtml( response.data.message || fstuSportsCategoriesL10n.messages.error ) + '</p>' );
				return;
			}

			const item = response.data;
			let html = '<table class="fstu-info-table">';
			html += '<tr><th>Найменування</th><td>' + escapeHtml( item.sportscategory_name || '' ) + '</td></tr>';
			html += '<tr><th>Сортування</th><td>' + escapeHtml( String( item.sportscategory_order || 0 ) ) + '</td></tr>';
			if ( item.sportscategory_datecreate ) {
				html += '<tr><th>Дата створення</th><td>' + escapeHtml( item.sportscategory_datecreate ) + '</td></tr>';
			}
			html += '</table>';
			$viewBody.html( html );

			if ( permissions.canManage ) {
				$viewFooter.html(
					'<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-sportscategories-view-edit" data-sportscategory-id="' + escapeHtml( String( item.sportscategory_id ) ) + '">' + escapeHtml( fstuSportsCategoriesL10n.messages.edit ) + '</button>' +
					'<button type="button" class="fstu-btn fstu-btn--secondary" data-close-modal="fstu-sportscategories-view-modal">' + escapeHtml( fstuSportsCategoriesL10n.messages.close ) + '</button>'
				);
			} else {
				$viewFooter.html( '<button type="button" class="fstu-btn fstu-btn--secondary" data-close-modal="fstu-sportscategories-view-modal">' + escapeHtml( fstuSportsCategoriesL10n.messages.close ) + '</button>' );
			}
		} ).fail( function () {
			$viewBody.html( '<p class="fstu-alert">' + escapeHtml( fstuSportsCategoriesL10n.messages.error ) + '</p>' );
		} );
	}

	$( document ).on( 'click', '#fstu-sportscategories-view-edit', function () {
		const itemId = parseInt( $( this ).data( 'sportscategory-id' ), 10 ) || 0;
		closeModal( 'fstu-sportscategories-view-modal' );
		if ( itemId > 0 ) {
			openFormModal( itemId );
		}
	} );

	function openFormModal( itemId ) {
		resetForm();

		if ( itemId && itemId > 0 ) {
			$( '#fstu-sportscategories-form-title' ).text( fstuSportsCategoriesL10n.messages.formEditTitle );
			$( '#fstu-sportscategories-id' ).val( itemId );
			openModal( 'fstu-sportscategories-form-modal' );
			loadFormData( itemId );
		} else {
			$( '#fstu-sportscategories-form-title' ).text( fstuSportsCategoriesL10n.messages.formAddTitle );
			openModal( 'fstu-sportscategories-form-modal' );
		}
	}

	function loadFormData( itemId ) {
		$.ajax( {
			url: fstuSportsCategoriesL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_sportscategories_get_single',
				nonce: fstuSportsCategoriesL10n.nonce,
				sportscategory_id: itemId,
			},
		} ).done( function ( response ) {
			if ( ! response.success ) {
				showFormMessage( response.data.message || fstuSportsCategoriesL10n.messages.error, true );
				return;
			}

			$( '#fstu-sportscategories-name' ).val( response.data.sportscategory_name || '' );
			$( '#fstu-sportscategories-order' ).val( response.data.sportscategory_order || 0 );
		} ).fail( function () {
			showFormMessage( fstuSportsCategoriesL10n.messages.error, true );
		} );
	}

	function submitForm() {
		if ( isSubmittingForm ) {
			return;
		}

		const itemId = parseInt( $( '#fstu-sportscategories-id' ).val(), 10 ) || 0;
		const action = itemId > 0 ? 'fstu_sportscategories_update' : 'fstu_sportscategories_create';
		const $submitButton = $( '#fstu-sportscategories-form-submit' );

		isSubmittingForm = true;
		$submitButton.prop( 'disabled', true ).addClass( 'is-loading' );

		$.ajax( {
			url: fstuSportsCategoriesL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: action,
				nonce: fstuSportsCategoriesL10n.nonce,
				sportscategory_id: itemId,
				sportscategory_name: $( '#fstu-sportscategories-name' ).val().trim(),
				sportscategory_order: $( '#fstu-sportscategories-order' ).val(),
				fstu_website: $( '#fstu-sportscategories-website' ).val(),
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				showFormMessage( response.data.message || fstuSportsCategoriesL10n.messages.saveSuccess, false );
				showPageMessage( response.data.message || fstuSportsCategoriesL10n.messages.saveSuccess, false );
				window.setTimeout( function () {
					closeModal( 'fstu-sportscategories-form-modal' );
					loadList();
				}, 500 );
			} else {
				showFormMessage( response.data.message || fstuSportsCategoriesL10n.messages.saveError, true );
			}
		} ).fail( function () {
			showFormMessage( fstuSportsCategoriesL10n.messages.saveError, true );
		} ).always( function () {
			isSubmittingForm = false;
			$submitButton.prop( 'disabled', false ).removeClass( 'is-loading' );
		} );
	}

	function deleteItem( itemId ) {
		if ( deletingIds.indexOf( itemId ) !== -1 ) {
			return;
		}

		if ( ! window.confirm( fstuSportsCategoriesL10n.messages.confirmDelete ) ) {
			return;
		}

		deletingIds.push( itemId );
		const $deleteButtons = $( '.fstu-sportscategories-delete-btn[data-sportscategory-id="' + itemId + '"]' );
		$deleteButtons.prop( 'disabled', true );

		$.ajax( {
			url: fstuSportsCategoriesL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_sportscategories_delete',
				nonce: fstuSportsCategoriesL10n.nonce,
				sportscategory_id: itemId,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				showPageMessage( response.data.message || 'OK', false );
				loadList();
			} else {
				showPageMessage( response.data.message || fstuSportsCategoriesL10n.messages.deleteError, true );
				window.alert( response.data.message || fstuSportsCategoriesL10n.messages.deleteError );
			}
		} ).fail( function () {
			showPageMessage( fstuSportsCategoriesL10n.messages.deleteError, true );
			window.alert( fstuSportsCategoriesL10n.messages.deleteError );
		} ).always( function () {
			deletingIds = deletingIds.filter( function ( value ) {
				return value !== itemId;
			} );
			$deleteButtons.prop( 'disabled', false );
		} );
	}

	function positionDropdown( $dropdown ) {
		const $toggle = $dropdown.find( '.fstu-sportscategories-dropdown__toggle' );
		const $menu = $dropdown.find( '.fstu-sportscategories-dropdown__menu' );
		if ( ! $toggle.length || ! $menu.length ) {
			return;
		}

		$dropdown.removeClass( 'is-dropup' );
		$menu.css( { display: 'block', visibility: 'hidden' } );
		const menuWidth = $menu.outerWidth();
		const menuHeight = $menu.outerHeight();
		$menu.css( { display: '', visibility: '' } );

		const rect = $toggle.get( 0 ).getBoundingClientRect();
		let top = rect.bottom + 6;
		let left = rect.right - menuWidth;

		if ( top + menuHeight > window.innerHeight - 12 ) {
			top = rect.top - menuHeight - 6;
			$dropdown.addClass( 'is-dropup' );
		}

		if ( left < 12 ) {
			left = 12;
		}

		if ( left + menuWidth > window.innerWidth - 12 ) {
			left = window.innerWidth - menuWidth - 12;
		}

		$menu.css( {
			position: 'fixed',
			top: top + 'px',
			left: left + 'px',
			right: 'auto',
			bottom: 'auto',
			zIndex: 100050,
		} );
	}

	function closeAllDropdowns() {
		$( '.fstu-sportscategories-dropdown' ).removeClass( 'is-open is-dropup' );
		$( '.fstu-sportscategories-dropdown__toggle' ).attr( 'aria-expanded', 'false' );
		$( '.fstu-sportscategories-dropdown__menu' ).css( {
			position: '',
			top: '',
			left: '',
			right: '',
			bottom: '',
			zIndex: '',
		} );
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

	function resetForm() {
		const $form = $( '#fstu-sportscategories-form' );
		if ( $form.length ) {
			$form[ 0 ].reset();
		}
		$( '#fstu-sportscategories-id' ).val( 0 );
		$( '#fstu-sportscategories-order' ).val( '' );
		$( '#fstu-sportscategories-form-submit' ).prop( 'disabled', false ).removeClass( 'is-loading' );
		isSubmittingForm = false;
		showFormMessage( '', false, true );
	}

	function showFormMessage( message, isError, hide ) {
		const $message = $( '#fstu-sportscategories-form-message' );
		if ( hide ) {
			$message.addClass( 'fstu-hidden' ).removeClass( 'fstu-message--error fstu-message--success' ).text( '' );
			return;
		}
		$message.removeClass( 'fstu-hidden fstu-message--error fstu-message--success' )
			.addClass( isError ? 'fstu-message--error' : 'fstu-message--success' )
			.text( message );
	}

	function showPageMessage( message, isError ) {
		const $message = $( '#fstu-sportscategories-page-message' );
		if ( ! message ) {
			$message.addClass( 'fstu-hidden' ).removeClass( 'fstu-page-message--error fstu-page-message--success' ).text( '' );
			return;
		}
		$message.removeClass( 'fstu-hidden fstu-page-message--error fstu-page-message--success' )
			.addClass( isError ? 'fstu-page-message--error' : 'fstu-page-message--success' )
			.text( message );
	}

	function setTableLoading( selector, colspan, message ) {
		$( selector ).html( '<tr class="fstu-row"><td colspan="' + colspan + '" class="fstu-no-results">' + escapeHtml( message ) + '</td></tr>' );
	}

	function showTableError( selector, colspan, message ) {
		$( selector ).html( '<tr class="fstu-row"><td colspan="' + colspan + '" class="fstu-no-results fstu-no-results--error">' + escapeHtml( message ) + '</td></tr>' );
	}

	function buildPaginationButtons( currentPage, totalPages, buttonClass ) {
		if ( totalPages <= 1 ) {
			return '';
		}

		let html = '';
		for ( let page = 1; page <= totalPages; page++ ) {
			const activeClass = page === currentPage ? ' fstu-btn--page-active' : '';
			html += '<button type="button" class="fstu-btn--page ' + buttonClass + activeClass + '" data-page="' + page + '">' + page + '</button>';
		}

		return html;
	}

	function buildPaginationInfo( total, page, totalPages ) {
		if ( ! total ) {
			return '';
		}
		return 'Записів: ' + total + ' | Сторінка ' + page + ' з ' + totalPages;
	}

	function setPaginationArrowState( prevSelector, nextSelector, currentPage, totalPages ) {
		$( prevSelector ).prop( 'disabled', currentPage <= 1 );
		$( nextSelector ).prop( 'disabled', currentPage >= totalPages );
	}

	function escapeHtml( value ) {
		return String( value )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#039;' );
	}

	function debounce( callback, delay ) {
		let timer = null;
		return function () {
			const context = this;
			const args = arguments;
			window.clearTimeout( timer );
			timer = window.setTimeout( function () {
				callback.apply( context, args );
			}, delay );
		};
	}
} );

