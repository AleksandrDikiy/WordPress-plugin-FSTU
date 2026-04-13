/**
 * JS модуля "Довідник видів складності походів".
 * Список, пошук, пагінація, dropdown,
 * модальні вікна, CRUD-операції, drag-and-drop та протокол.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-13
 *
 * @package FSTU
 */

/* global fstuHourCategoriesL10n */

jQuery( document ).ready( function ( $ ) {
	'use strict';

	if ( typeof fstuHourCategoriesL10n === 'undefined' ) {
		return;
	}

	const $module = $( '#fstu-hourcategories' );
	if ( ! $module.length ) {
		return;
	}

	const permissions = fstuHourCategoriesL10n.permissions || {};
	const defaults = fstuHourCategoriesL10n.defaults || {};
	const listState = {
		page: 1,
		perPage: parseInt( defaults.perPage, 10 ) || 10,
		search: '',
		total: 0,
		totalPages: 1,
		loading: false,
		canReorder: false,
	};
	const protocolState = {
		page: 1,
		perPage: parseInt( defaults.protocolPerPage, 10 ) || 10,
		search: '',
		total: 0,
		totalPages: 1,
		loading: false,
	};

	let draggedRow = null;
	let reorderRequest = null;
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
			if ( ! $( event.target ).closest( '.fstu-hourcategories-dropdown' ).length ) {
				closeAllDropdowns();
			}
		} );

		$( window ).on( 'scroll resize', function () {
			closeAllDropdowns();
		} );
	}

	function bindListEvents() {
		$( document ).on( 'input', '#fstu-hourcategories-search', debounce( function () {
			listState.search = $( this ).val().trim();
			listState.page = 1;
			loadList();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-hourcategories-per-page', function () {
			listState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'click', '#fstu-hourcategories-prev-page', function () {
			if ( listState.page > 1 ) {
				listState.page--;
				loadList();
			}
		} );

		$( document ).on( 'click', '#fstu-hourcategories-next-page', function () {
			if ( listState.page < listState.totalPages ) {
				listState.page++;
				loadList();
			}
		} );

		$( document ).on( 'click', '.fstu-hourcategories-page-btn', function () {
			const page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
			if ( page !== listState.page ) {
				listState.page = page;
				loadList();
			}
		} );

		$( document ).on( 'click', '.fstu-hourcategories-dropdown__toggle', function ( event ) {
			event.preventDefault();
			event.stopPropagation();

			const $dropdown = $( this ).closest( '.fstu-hourcategories-dropdown' );
			const isOpen = $dropdown.hasClass( 'is-open' );
			closeAllDropdowns();

			if ( ! isOpen ) {
				$dropdown.addClass( 'is-open' );
				positionDropdown( $dropdown );
				$( this ).attr( 'aria-expanded', 'true' );
			}
		} );

		$( document ).on( 'click', '#fstu-hourcategories-add-btn', function () {
			openFormModal();
		} );

		$( document ).on( 'click', '.fstu-hourcategories-view-btn', function () {
			closeAllDropdowns();
			const itemId = parseInt( $( this ).data( 'hourcategories-id' ), 10 ) || 0;
			if ( itemId > 0 ) {
				openViewModal( itemId );
			}
		} );

		$( document ).on( 'click', '.fstu-hourcategories-edit-btn', function () {
			closeAllDropdowns();
			const itemId = parseInt( $( this ).data( 'hourcategories-id' ), 10 ) || 0;
			if ( itemId > 0 ) {
				openFormModal( itemId );
			}
		} );

		$( document ).on( 'click', '.fstu-hourcategories-delete-btn', function () {
			closeAllDropdowns();
			const itemId = parseInt( $( this ).data( 'hourcategories-id' ), 10 ) || 0;
			if ( itemId > 0 ) {
				deleteItem( itemId );
			}
		} );

		$( document ).on( 'dragstart', '.fstu-hourcategories-row[draggable="true"]', function ( event ) {
			if ( listState.loading || reorderRequest || ! listState.canReorder ) {
				event.preventDefault();
				return;
			}

			draggedRow = this;
			$( this ).addClass( 'is-dragging' );
			event.originalEvent.dataTransfer.effectAllowed = 'move';
		} );

		$( document ).on( 'dragover', '.fstu-hourcategories-row[draggable="true"]', function ( event ) {
			if ( ! draggedRow || draggedRow === this ) {
				return;
			}

			event.preventDefault();
			const rect = this.getBoundingClientRect();
			const midpoint = rect.top + ( rect.height / 2 );

			if ( event.originalEvent.clientY < midpoint ) {
				this.parentNode.insertBefore( draggedRow, this );
			} else {
				this.parentNode.insertBefore( draggedRow, this.nextSibling );
			}
		} );

		$( document ).on( 'dragend', '.fstu-hourcategories-row[draggable="true"]', function () {
			$( '.fstu-hourcategories-row' ).removeClass( 'is-dragging' );
			if ( draggedRow ) {
				sendReorder();
			}
			draggedRow = null;
		} );
	}

	function bindProtocolEvents() {
		$( document ).on( 'click', '#fstu-hourcategories-protocol-btn', function () {
			$( '#fstu-hourcategories-main' ).addClass( 'fstu-hidden' );
			$( '#fstu-hourcategories-protocol' ).removeClass( 'fstu-hidden' );
			$( '#fstu-hourcategories-add-btn' ).addClass( 'fstu-hidden' );
			$( '#fstu-hourcategories-protocol-btn' ).addClass( 'fstu-hidden' );
			$( '#fstu-hourcategories-protocol-back-btn' ).removeClass( 'fstu-hidden' );
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-hourcategories-protocol-back-btn', function () {
			$( '#fstu-hourcategories-main' ).removeClass( 'fstu-hidden' );
			$( '#fstu-hourcategories-protocol' ).addClass( 'fstu-hidden' );
			$( '#fstu-hourcategories-add-btn' ).removeClass( 'fstu-hidden' );
			$( '#fstu-hourcategories-protocol-btn' ).removeClass( 'fstu-hidden' );
			$( '#fstu-hourcategories-protocol-back-btn' ).addClass( 'fstu-hidden' );
		} );

		$( document ).on( 'input', '#fstu-hourcategories-protocol-search', debounce( function () {
			protocolState.search = $( this ).val().trim();
			protocolState.page = 1;
			loadProtocol();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-hourcategories-protocol-per-page', function () {
			protocolState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			protocolState.page = 1;
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-hourcategories-protocol-prev-page', function () {
			if ( protocolState.page > 1 ) {
				protocolState.page--;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '#fstu-hourcategories-protocol-next-page', function () {
			if ( protocolState.page < protocolState.totalPages ) {
				protocolState.page++;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '.fstu-hourcategories-protocol-page-btn', function () {
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
		$( document ).on( 'submit', '#fstu-hourcategories-form', function ( event ) {
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
		setTableLoading( '#fstu-hourcategories-tbody', 4, fstuHourCategoriesL10n.messages.loading );

		$.ajax( {
			url: fstuHourCategoriesL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_hourcategories_get_list',
				nonce: fstuHourCategoriesL10n.nonce,
				search: listState.search,
				page: listState.page,
				per_page: listState.perPage,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				$( '#fstu-hourcategories-tbody' ).html( response.data.html );
				listState.total = parseInt( response.data.total, 10 ) || 0;
				listState.page = parseInt( response.data.page, 10 ) || 1;
				listState.perPage = parseInt( response.data.per_page, 10 ) || 10;
				listState.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				listState.canReorder = !! response.data.can_reorder;
				updateListPagination();
			} else {
				showTableError( '#fstu-hourcategories-tbody', 4, response.data.message || fstuHourCategoriesL10n.messages.error );
			}
		} ).fail( function () {
			showTableError( '#fstu-hourcategories-tbody', 4, fstuHourCategoriesL10n.messages.error );
		} ).always( function () {
			listState.loading = false;
		} );
	}

	function loadProtocol() {
		if ( protocolState.loading ) {
			return;
		}

		protocolState.loading = true;
		setTableLoading( '#fstu-hourcategories-protocol-tbody', 6, fstuHourCategoriesL10n.messages.loading );

		$.ajax( {
			url: fstuHourCategoriesL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_hourcategories_get_protocol',
				nonce: fstuHourCategoriesL10n.nonce,
				search: protocolState.search,
				page: protocolState.page,
				per_page: protocolState.perPage,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				$( '#fstu-hourcategories-protocol-tbody' ).html( response.data.html );
				protocolState.total = parseInt( response.data.total, 10 ) || 0;
				protocolState.page = parseInt( response.data.page, 10 ) || 1;
				protocolState.perPage = parseInt( response.data.per_page, 10 ) || 10;
				protocolState.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				updateProtocolPagination();
			} else {
				showTableError( '#fstu-hourcategories-protocol-tbody', 6, response.data.message || fstuHourCategoriesL10n.messages.protocolError );
			}
		} ).fail( function () {
			showTableError( '#fstu-hourcategories-protocol-tbody', 6, fstuHourCategoriesL10n.messages.protocolError );
		} ).always( function () {
			protocolState.loading = false;
		} );
	}

	function updateListPagination() {
		$( '#fstu-hourcategories-per-page' ).val( String( listState.perPage ) );
		$( '#fstu-hourcategories-pagination-pages' ).html( buildPaginationButtons( listState.page, listState.totalPages, 'fstu-hourcategories-page-btn' ) );
		$( '#fstu-hourcategories-pagination-info' ).text( buildPaginationInfo( listState.total, listState.page, listState.totalPages ) );
		setPaginationArrowState( '#fstu-hourcategories-prev-page', '#fstu-hourcategories-next-page', listState.page, listState.totalPages );
	}

	function updateProtocolPagination() {
		$( '#fstu-hourcategories-protocol-per-page' ).val( String( protocolState.perPage ) );
		$( '#fstu-hourcategories-protocol-pagination-pages' ).html( buildPaginationButtons( protocolState.page, protocolState.totalPages, 'fstu-hourcategories-protocol-page-btn' ) );
		$( '#fstu-hourcategories-protocol-pagination-info' ).text( buildPaginationInfo( protocolState.total, protocolState.page, protocolState.totalPages ) );
		setPaginationArrowState( '#fstu-hourcategories-protocol-prev-page', '#fstu-hourcategories-protocol-next-page', protocolState.page, protocolState.totalPages );
	}

	function openViewModal( itemId ) {
		const $viewBody = $( '#fstu-hourcategories-view-body' );
		const $viewFooter = $( '#fstu-hourcategories-view-footer' );

		$viewBody.html( '<p class="fstu-loader-inline">' + escapeHtml( fstuHourCategoriesL10n.messages.loading ) + '</p>' );
		$viewFooter.empty();
		openModal( 'fstu-hourcategories-view-modal' );

		$.ajax( {
			url: fstuHourCategoriesL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_hourcategories_get_single',
				nonce: fstuHourCategoriesL10n.nonce,
				hourcategories_id: itemId,
			},
		} ).done( function ( response ) {
			if ( ! response.success ) {
				$viewBody.html( '<p class="fstu-alert">' + escapeHtml( response.data.message || fstuHourCategoriesL10n.messages.error ) + '</p>' );
				return;
			}

			const item = response.data;
			let html = '<table class="fstu-info-table">';
			html += '<tr><th>Найменування</th><td>' + escapeHtml( item.hourcategories_name || '' ) + '</td></tr>';
			html += '<tr><th>Код категорії</th><td>' + escapeHtml( item.hourcategories_code || '' ) + '</td></tr>';
			html += '<tr><th>Порядок</th><td>' + escapeHtml( String( item.hourcategories_order || 0 ) ) + '</td></tr>';
			html += '</table>';
			$viewBody.html( html );

			if ( permissions.canManage ) {
				$viewFooter.html(
					'<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-hourcategories-view-edit" data-hourcategories-id="' + escapeHtml( String( item.hourcategories_id ) ) + '">' + escapeHtml( fstuHourCategoriesL10n.messages.edit ) + '</button>' +
					'<button type="button" class="fstu-btn fstu-btn--secondary" data-close-modal="fstu-hourcategories-view-modal">' + escapeHtml( fstuHourCategoriesL10n.messages.close ) + '</button>'
				);
			} else {
				$viewFooter.html( '<button type="button" class="fstu-btn fstu-btn--secondary" data-close-modal="fstu-hourcategories-view-modal">' + escapeHtml( fstuHourCategoriesL10n.messages.close ) + '</button>' );
			}
		} ).fail( function () {
			$viewBody.html( '<p class="fstu-alert">' + escapeHtml( fstuHourCategoriesL10n.messages.error ) + '</p>' );
		} );
	}

	$( document ).on( 'click', '#fstu-hourcategories-view-edit', function () {
		const itemId = parseInt( $( this ).data( 'hourcategories-id' ), 10 ) || 0;
		closeModal( 'fstu-hourcategories-view-modal' );
		if ( itemId > 0 ) {
			openFormModal( itemId );
		}
	} );

	function openFormModal( itemId ) {
		resetForm();

		if ( itemId && itemId > 0 ) {
			$( '#fstu-hourcategories-form-title' ).text( fstuHourCategoriesL10n.messages.formEditTitle );
			$( '#fstu-hourcategories-id' ).val( itemId );
			openModal( 'fstu-hourcategories-form-modal' );
			loadFormData( itemId );
		} else {
			$( '#fstu-hourcategories-form-title' ).text( fstuHourCategoriesL10n.messages.formAddTitle );
			openModal( 'fstu-hourcategories-form-modal' );
		}
	}

	function loadFormData( itemId ) {
		$.ajax( {
			url: fstuHourCategoriesL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_hourcategories_get_single',
				nonce: fstuHourCategoriesL10n.nonce,
				hourcategories_id: itemId,
			},
		} ).done( function ( response ) {
			if ( ! response.success ) {
				showFormMessage( response.data.message || fstuHourCategoriesL10n.messages.error, true );
				return;
			}

			$( '#fstu-hourcategories-name' ).val( response.data.hourcategories_name || '' );
			$( '#fstu-hourcategories-code' ).val( response.data.hourcategories_code || '' );
			$( '#fstu-hourcategories-order' ).val( response.data.hourcategories_order || 0 );
		} ).fail( function () {
			showFormMessage( fstuHourCategoriesL10n.messages.error, true );
		} );
	}

	function submitForm() {
		if ( isSubmittingForm ) {
			return;
		}

		const itemId = parseInt( $( '#fstu-hourcategories-id' ).val(), 10 ) || 0;
		const action = itemId > 0 ? 'fstu_hourcategories_update' : 'fstu_hourcategories_create';
		const $submitButton = $( '#fstu-hourcategories-form-submit' );

		isSubmittingForm = true;
		$submitButton.prop( 'disabled', true ).addClass( 'is-loading' );

		$.ajax( {
			url: fstuHourCategoriesL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: action,
				nonce: fstuHourCategoriesL10n.nonce,
				hourcategories_id: itemId,
				hourcategories_name: $( '#fstu-hourcategories-name' ).val().trim(),
				hourcategories_code: $( '#fstu-hourcategories-code' ).val().trim(),
				hourcategories_order: $( '#fstu-hourcategories-order' ).val(),
				fstu_website: $( '#fstu-hourcategories-website' ).val(),
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				showFormMessage( response.data.message || fstuHourCategoriesL10n.messages.saveSuccess, false );
				showPageMessage( response.data.message || fstuHourCategoriesL10n.messages.saveSuccess, false );
				window.setTimeout( function () {
					closeModal( 'fstu-hourcategories-form-modal' );
					loadList();
				}, 500 );
			} else {
				showFormMessage( response.data.message || fstuHourCategoriesL10n.messages.saveError, true );
			}
		} ).fail( function () {
			showFormMessage( fstuHourCategoriesL10n.messages.saveError, true );
		} ).always( function () {
			isSubmittingForm = false;
			$submitButton.prop( 'disabled', false ).removeClass( 'is-loading' );
		} );
	}

	function deleteItem( itemId ) {
		if ( deletingIds.indexOf( itemId ) !== -1 ) {
			return;
		}

		if ( ! window.confirm( fstuHourCategoriesL10n.messages.confirmDelete ) ) {
			return;
		}

		deletingIds.push( itemId );
		const $deleteButtons = $( '.fstu-hourcategories-delete-btn[data-hourcategories-id="' + itemId + '"]' );
		$deleteButtons.prop( 'disabled', true );

		$.ajax( {
			url: fstuHourCategoriesL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_hourcategories_delete',
				nonce: fstuHourCategoriesL10n.nonce,
				hourcategories_id: itemId,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				showPageMessage( response.data.message || 'OK', false );
				loadList();
			} else {
				showPageMessage( response.data.message || fstuHourCategoriesL10n.messages.deleteError, true );
				window.alert( response.data.message || fstuHourCategoriesL10n.messages.deleteError );
			}
		} ).fail( function () {
			showPageMessage( fstuHourCategoriesL10n.messages.deleteError, true );
			window.alert( fstuHourCategoriesL10n.messages.deleteError );
		} ).always( function () {
			deletingIds = deletingIds.filter( function ( value ) {
				return value !== itemId;
			} );
			$deleteButtons.prop( 'disabled', false );
		} );
	}

	function sendReorder() {
		if ( ! permissions.canManage || reorderRequest || listState.search !== '' ) {
			return;
		}

		const items = [];
		const offset = ( listState.page - 1 ) * listState.perPage;
		$( '#fstu-hourcategories-tbody .fstu-hourcategories-row' ).each( function ( index ) {
			const itemId = parseInt( $( this ).data( 'hourcategories-id' ), 10 ) || 0;
			if ( itemId > 0 ) {
				items.push( {
					id: itemId,
					order: offset + index + 1,
				} );
			}
		} );

		if ( ! items.length ) {
			return;
		}

		reorderRequest = $.ajax( {
			url: fstuHourCategoriesL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_hourcategories_reorder',
				nonce: fstuHourCategoriesL10n.nonce,
				items: JSON.stringify( items ),
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				showPageMessage( response.data.message || fstuHourCategoriesL10n.messages.reorderSuccess, false );
				loadList();
			} else {
				showPageMessage( response.data.message || fstuHourCategoriesL10n.messages.reorderError, true );
				window.alert( response.data.message || fstuHourCategoriesL10n.messages.reorderError );
				loadList();
			}
		} ).fail( function () {
			showPageMessage( fstuHourCategoriesL10n.messages.reorderError, true );
			window.alert( fstuHourCategoriesL10n.messages.reorderError );
			loadList();
		} ).always( function () {
			reorderRequest = null;
		} );
	}

	function positionDropdown( $dropdown ) {
		const $toggle = $dropdown.find( '.fstu-hourcategories-dropdown__toggle' );
		const $menu = $dropdown.find( '.fstu-hourcategories-dropdown__menu' );
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
		$( '.fstu-hourcategories-dropdown' ).removeClass( 'is-open is-dropup' );
		$( '.fstu-hourcategories-dropdown__toggle' ).attr( 'aria-expanded', 'false' );
		$( '.fstu-hourcategories-dropdown__menu' ).css( {
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
		const $form = $( '#fstu-hourcategories-form' );
		if ( $form.length ) {
			$form[ 0 ].reset();
		}
		$( '#fstu-hourcategories-id' ).val( 0 );
		$( '#fstu-hourcategories-order' ).val( '' );
		$( '#fstu-hourcategories-form-submit' ).prop( 'disabled', false ).removeClass( 'is-loading' );
		isSubmittingForm = false;
		showFormMessage( '', false, true );
	}

	function showFormMessage( message, isError, hide ) {
		const $message = $( '#fstu-hourcategories-form-message' );
		if ( hide ) {
			$message.addClass( 'fstu-hidden' ).removeClass( 'fstu-message--error fstu-message--success' ).text( '' );
			return;
		}
		$message.removeClass( 'fstu-hidden fstu-message--error fstu-message--success' )
			.addClass( isError ? 'fstu-message--error' : 'fstu-message--success' )
			.text( message );
	}

	function showPageMessage( message, isError ) {
		const $message = $( '#fstu-hourcategories-page-message' );
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

