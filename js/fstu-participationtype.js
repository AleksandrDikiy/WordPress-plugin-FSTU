/**
 * JS модуля "Довідник видів участі в заходах".
 * Список, пошук, пагінація, dropdown, модальні вікна,
 * CRUD-операції, drag-and-drop та протокол.
 *
 * Version:     1.0.1
 * Date_update: 2026-04-12
 *
 * @package FSTU
 */

/* global fstuParticipationTypeL10n */

jQuery( document ).ready( function ( $ ) {
	'use strict';

	if ( typeof fstuParticipationTypeL10n === 'undefined' ) {
		return;
	}

	const $module = $( '#fstu-participationtype' );
	if ( ! $module.length ) {
		return;
	}

	const permissions = fstuParticipationTypeL10n.permissions || {};
	const defaults = fstuParticipationTypeL10n.defaults || {};
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
			if ( ! $( event.target ).closest( '.fstu-participationtype-dropdown' ).length ) {
				closeAllDropdowns();
			}
		} );

		$( window ).on( 'scroll resize', function () {
			closeAllDropdowns();
		} );
	}

	function bindListEvents() {
		$( document ).on( 'input', '#fstu-participationtype-search', debounce( function () {
			listState.search = $( this ).val().trim();
			listState.page = 1;
			loadList();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-participationtype-per-page', function () {
			listState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'click', '#fstu-participationtype-prev-page', function () {
			if ( listState.page > 1 ) {
				listState.page--;
				loadList();
			}
		} );

		$( document ).on( 'click', '#fstu-participationtype-next-page', function () {
			if ( listState.page < listState.totalPages ) {
				listState.page++;
				loadList();
			}
		} );

		$( document ).on( 'click', '.fstu-participationtype-page-btn', function () {
			const page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
			if ( page !== listState.page ) {
				listState.page = page;
				loadList();
			}
		} );

		$( document ).on( 'click', '.fstu-participationtype-dropdown__toggle', function ( event ) {
			event.preventDefault();
			event.stopPropagation();

			const $dropdown = $( this ).closest( '.fstu-participationtype-dropdown' );
			const isOpen = $dropdown.hasClass( 'is-open' );
			closeAllDropdowns();

			if ( ! isOpen ) {
				$dropdown.addClass( 'is-open' );
				positionDropdown( $dropdown );
				$( this ).attr( 'aria-expanded', 'true' );
			}
		} );

		$( document ).on( 'click', '#fstu-participationtype-add-btn', function () {
			openFormModal();
		} );

		$( document ).on( 'click', '.fstu-participationtype-view-btn', function () {
			closeAllDropdowns();
			const itemId = parseInt( $( this ).data( 'participation-type-id' ), 10 ) || 0;
			if ( itemId > 0 ) {
				openViewModal( itemId );
			}
		} );

		$( document ).on( 'click', '.fstu-participationtype-edit-btn', function () {
			closeAllDropdowns();
			const itemId = parseInt( $( this ).data( 'participation-type-id' ), 10 ) || 0;
			if ( itemId > 0 ) {
				openFormModal( itemId );
			}
		} );

		$( document ).on( 'click', '.fstu-participationtype-delete-btn', function () {
			closeAllDropdowns();
			const itemId = parseInt( $( this ).data( 'participation-type-id' ), 10 ) || 0;
			if ( itemId > 0 ) {
				deleteItem( itemId );
			}
		} );

		$( document ).on( 'dragstart', '.fstu-participationtype-row[draggable="true"]', function ( event ) {
			if ( listState.loading || reorderRequest ) {
				event.preventDefault();
				return;
			}

			draggedRow = this;
			$( this ).addClass( 'is-dragging' );
			event.originalEvent.dataTransfer.effectAllowed = 'move';
		} );

		$( document ).on( 'dragover', '.fstu-participationtype-row[draggable="true"]', function ( event ) {
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

		$( document ).on( 'dragend', '.fstu-participationtype-row[draggable="true"]', function () {
			$( '.fstu-participationtype-row' ).removeClass( 'is-dragging' );
			if ( draggedRow ) {
				sendReorder();
			}
			draggedRow = null;
		} );
	}

	function bindProtocolEvents() {
		$( document ).on( 'click', '#fstu-participationtype-protocol-btn', function () {
			$( '#fstu-participationtype-main' ).addClass( 'fstu-hidden' );
			$( '#fstu-participationtype-protocol' ).removeClass( 'fstu-hidden' );
			$( '#fstu-participationtype-add-btn' ).addClass( 'fstu-hidden' );
			$( '#fstu-participationtype-protocol-btn' ).addClass( 'fstu-hidden' );
			$( '#fstu-participationtype-protocol-back-btn' ).removeClass( 'fstu-hidden' );
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-participationtype-protocol-back-btn', function () {
			$( '#fstu-participationtype-main' ).removeClass( 'fstu-hidden' );
			$( '#fstu-participationtype-protocol' ).addClass( 'fstu-hidden' );
			$( '#fstu-participationtype-add-btn' ).removeClass( 'fstu-hidden' );
			$( '#fstu-participationtype-protocol-btn' ).removeClass( 'fstu-hidden' );
			$( '#fstu-participationtype-protocol-back-btn' ).addClass( 'fstu-hidden' );
		} );

		$( document ).on( 'input', '#fstu-participationtype-protocol-search', debounce( function () {
			protocolState.search = $( this ).val().trim();
			protocolState.page = 1;
			loadProtocol();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-participationtype-protocol-per-page', function () {
			protocolState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			protocolState.page = 1;
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-participationtype-protocol-prev-page', function () {
			if ( protocolState.page > 1 ) {
				protocolState.page--;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '#fstu-participationtype-protocol-next-page', function () {
			if ( protocolState.page < protocolState.totalPages ) {
				protocolState.page++;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '.fstu-participationtype-protocol-page-btn', function () {
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
		$( document ).on( 'submit', '#fstu-participationtype-form', function ( event ) {
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
		setTableLoading( '#fstu-participationtype-tbody', 4, fstuParticipationTypeL10n.messages.loading );

		$.ajax( {
			url: fstuParticipationTypeL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_participationtype_get_list',
				nonce: fstuParticipationTypeL10n.nonce,
				search: listState.search,
				page: listState.page,
				per_page: listState.perPage,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				$( '#fstu-participationtype-tbody' ).html( response.data.html );
				listState.total = parseInt( response.data.total, 10 ) || 0;
				listState.page = parseInt( response.data.page, 10 ) || 1;
				listState.perPage = parseInt( response.data.per_page, 10 ) || 10;
				listState.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				updateListPagination();
			} else {
				showTableError( '#fstu-participationtype-tbody', 4, response.data.message || fstuParticipationTypeL10n.messages.error );
			}
		} ).fail( function () {
			showTableError( '#fstu-participationtype-tbody', 4, fstuParticipationTypeL10n.messages.error );
		} ).always( function () {
			listState.loading = false;
		} );
	}

	function loadProtocol() {
		if ( protocolState.loading ) {
			return;
		}

		protocolState.loading = true;
		setTableLoading( '#fstu-participationtype-protocol-tbody', 6, fstuParticipationTypeL10n.messages.loading );

		$.ajax( {
			url: fstuParticipationTypeL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_participationtype_get_protocol',
				nonce: fstuParticipationTypeL10n.nonce,
				search: protocolState.search,
				page: protocolState.page,
				per_page: protocolState.perPage,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				$( '#fstu-participationtype-protocol-tbody' ).html( response.data.html );
				protocolState.total = parseInt( response.data.total, 10 ) || 0;
				protocolState.page = parseInt( response.data.page, 10 ) || 1;
				protocolState.perPage = parseInt( response.data.per_page, 10 ) || 10;
				protocolState.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				updateProtocolPagination();
			} else {
				showTableError( '#fstu-participationtype-protocol-tbody', 6, response.data.message || fstuParticipationTypeL10n.messages.protocolError );
			}
		} ).fail( function () {
			showTableError( '#fstu-participationtype-protocol-tbody', 6, fstuParticipationTypeL10n.messages.protocolError );
		} ).always( function () {
			protocolState.loading = false;
		} );
	}

	function updateListPagination() {
		$( '#fstu-participationtype-per-page' ).val( String( listState.perPage ) );
		$( '#fstu-participationtype-pagination-pages' ).html( buildPaginationButtons( listState.page, listState.totalPages, 'fstu-participationtype-page-btn' ) );
		$( '#fstu-participationtype-pagination-info' ).text( buildPaginationInfo( listState.total, listState.page, listState.totalPages ) );
		setPaginationArrowState( '#fstu-participationtype-prev-page', '#fstu-participationtype-next-page', listState.page, listState.totalPages );
	}

	function updateProtocolPagination() {
		$( '#fstu-participationtype-protocol-per-page' ).val( String( protocolState.perPage ) );
		$( '#fstu-participationtype-protocol-pagination-pages' ).html( buildPaginationButtons( protocolState.page, protocolState.totalPages, 'fstu-participationtype-protocol-page-btn' ) );
		$( '#fstu-participationtype-protocol-pagination-info' ).text( buildPaginationInfo( protocolState.total, protocolState.page, protocolState.totalPages ) );
		setPaginationArrowState( '#fstu-participationtype-protocol-prev-page', '#fstu-participationtype-protocol-next-page', protocolState.page, protocolState.totalPages );
	}

	function openViewModal( itemId ) {
		const $viewBody = $( '#fstu-participationtype-view-body' );
		const $viewFooter = $( '#fstu-participationtype-view-footer' );

		$viewBody.html( '<p class="fstu-loader-inline">' + escapeHtml( fstuParticipationTypeL10n.messages.loading ) + '</p>' );
		$viewFooter.empty();
		openModal( 'fstu-participationtype-view-modal' );

		$.ajax( {
			url: fstuParticipationTypeL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_participationtype_get_single',
				nonce: fstuParticipationTypeL10n.nonce,
				participation_type_id: itemId,
			},
		} ).done( function ( response ) {
			if ( ! response.success ) {
				$viewBody.html( '<p class="fstu-alert">' + escapeHtml( response.data.message || fstuParticipationTypeL10n.messages.error ) + '</p>' );
				return;
			}

			const item = response.data;
			let html = '<table class="fstu-info-table">';
			html += '<tr><th>Найменування</th><td>' + escapeHtml( item.participation_type_name || '' ) + '</td></tr>';
			html += '<tr><th>Тип</th><td>' + escapeHtml( item.participation_type_type_label || '' ) + '</td></tr>';
			html += '<tr><th>Сортування</th><td>' + escapeHtml( String( item.participation_type_order || 0 ) ) + '</td></tr>';
			html += '<tr><th>Дата створення</th><td>' + ( item.participation_type_datecreate ? escapeHtml( item.participation_type_datecreate ) : '—' ) + '</td></tr>';
			html += '</table>';
			$viewBody.html( html );

			if ( permissions.canManage ) {
				$viewFooter.html(
					'<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-participationtype-view-edit" data-participation-type-id="' + escapeHtml( String( item.participation_type_id ) ) + '">Редагувати</button>' +
					'<button type="button" class="fstu-btn fstu-btn--secondary" data-close-modal="fstu-participationtype-view-modal">Закрити</button>'
				);
			} else {
				$viewFooter.html( '<button type="button" class="fstu-btn fstu-btn--secondary" data-close-modal="fstu-participationtype-view-modal">Закрити</button>' );
			}
		} ).fail( function () {
			$viewBody.html( '<p class="fstu-alert">' + escapeHtml( fstuParticipationTypeL10n.messages.error ) + '</p>' );
		} );
	}

	$( document ).on( 'click', '#fstu-participationtype-view-edit', function () {
		const itemId = parseInt( $( this ).data( 'participation-type-id' ), 10 ) || 0;
		closeModal( 'fstu-participationtype-view-modal' );
		if ( itemId > 0 ) {
			openFormModal( itemId );
		}
	} );

	function openFormModal( itemId ) {
		resetForm();

		if ( itemId && itemId > 0 ) {
			$( '#fstu-participationtype-form-title' ).text( fstuParticipationTypeL10n.messages.formEditTitle );
			$( '#fstu-participationtype-id' ).val( itemId );
			openModal( 'fstu-participationtype-form-modal' );
			loadFormData( itemId );
		} else {
			$( '#fstu-participationtype-form-title' ).text( fstuParticipationTypeL10n.messages.formAddTitle );
			openModal( 'fstu-participationtype-form-modal' );
		}
	}

	function loadFormData( itemId ) {
		$.ajax( {
			url: fstuParticipationTypeL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_participationtype_get_single',
				nonce: fstuParticipationTypeL10n.nonce,
				participation_type_id: itemId,
			},
		} ).done( function ( response ) {
			if ( ! response.success ) {
				showFormMessage( response.data.message || fstuParticipationTypeL10n.messages.error, true );
				return;
			}

			$( '#fstu-participationtype-name' ).val( response.data.participation_type_name || '' );
			$( '#fstu-participationtype-order' ).val( response.data.participation_type_order || 0 );
			$( '#fstu-participationtype-form input[name="participation_type_type"][value="' + String( response.data.participation_type_type || 1 ) + '"]' ).prop( 'checked', true );
		} ).fail( function () {
			showFormMessage( fstuParticipationTypeL10n.messages.error, true );
		} );
	}

	function submitForm() {
		if ( isSubmittingForm ) {
			return;
		}

		const itemId = parseInt( $( '#fstu-participationtype-id' ).val(), 10 ) || 0;
		const action = itemId > 0 ? 'fstu_participationtype_update' : 'fstu_participationtype_create';
		const $submitButton = $( '#fstu-participationtype-form-submit' );

		isSubmittingForm = true;
		$submitButton.prop( 'disabled', true ).addClass( 'is-loading' );

		$.ajax( {
			url: fstuParticipationTypeL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: action,
				nonce: fstuParticipationTypeL10n.nonce,
				participation_type_id: itemId,
				participation_type_name: $( '#fstu-participationtype-name' ).val().trim(),
				participation_type_type: $( '#fstu-participationtype-form input[name="participation_type_type"]:checked' ).val(),
				participation_type_order: $( '#fstu-participationtype-order' ).val(),
				fstu_website: $( '#fstu-participationtype-website' ).val(),
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				showFormMessage( response.data.message || fstuParticipationTypeL10n.messages.saveSuccess, false );
				showPageMessage( response.data.message || fstuParticipationTypeL10n.messages.saveSuccess, false );
				window.setTimeout( function () {
					closeModal( 'fstu-participationtype-form-modal' );
					loadList();
				}, 500 );
			} else {
				showFormMessage( response.data.message || fstuParticipationTypeL10n.messages.saveError, true );
			}
		} ).fail( function () {
			showFormMessage( fstuParticipationTypeL10n.messages.saveError, true );
		} ).always( function () {
			isSubmittingForm = false;
			$submitButton.prop( 'disabled', false ).removeClass( 'is-loading' );
		} );
	}

	function deleteItem( itemId ) {
		if ( deletingIds.indexOf( itemId ) !== -1 ) {
			return;
		}

		if ( ! window.confirm( fstuParticipationTypeL10n.messages.confirmDelete ) ) {
			return;
		}

		deletingIds.push( itemId );
		const $deleteButtons = $( '.fstu-participationtype-delete-btn[data-participation-type-id="' + itemId + '"]' );
		$deleteButtons.prop( 'disabled', true );

		$.ajax( {
			url: fstuParticipationTypeL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_participationtype_delete',
				nonce: fstuParticipationTypeL10n.nonce,
				participation_type_id: itemId,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				showPageMessage( response.data.message || 'OK', false );
				loadList();
			} else {
				showPageMessage( response.data.message || fstuParticipationTypeL10n.messages.deleteError, true );
				window.alert( response.data.message || fstuParticipationTypeL10n.messages.deleteError );
			}
		} ).fail( function () {
			showPageMessage( fstuParticipationTypeL10n.messages.deleteError, true );
			window.alert( fstuParticipationTypeL10n.messages.deleteError );
		} ).always( function () {
			deletingIds = deletingIds.filter( function ( value ) {
				return value !== itemId;
			} );
			$deleteButtons.prop( 'disabled', false );
		} );
	}

	function sendReorder() {
		if ( ! permissions.canManage || reorderRequest || listState.search ) {
			return;
		}

		const items = [];
		const offset = ( listState.page - 1 ) * listState.perPage;
		$( '#fstu-participationtype-tbody .fstu-participationtype-row' ).each( function ( index ) {
			const itemId = parseInt( $( this ).data( 'participation-type-id' ), 10 ) || 0;
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
			url: fstuParticipationTypeL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_participationtype_reorder',
				nonce: fstuParticipationTypeL10n.nonce,
				items: JSON.stringify( items ),
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				showPageMessage( response.data.message || fstuParticipationTypeL10n.messages.reorderSuccess, false );
				loadList();
			} else {
				showPageMessage( response.data.message || fstuParticipationTypeL10n.messages.reorderError, true );
				window.alert( response.data.message || fstuParticipationTypeL10n.messages.reorderError );
				loadList();
			}
		} ).fail( function () {
			showPageMessage( fstuParticipationTypeL10n.messages.reorderError, true );
			window.alert( fstuParticipationTypeL10n.messages.reorderError );
			loadList();
		} ).always( function () {
			reorderRequest = null;
		} );
	}

	function positionDropdown( $dropdown ) {
		const $toggle = $dropdown.find( '.fstu-participationtype-dropdown__toggle' );
		const $menu = $dropdown.find( '.fstu-participationtype-dropdown__menu' );
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
		$( '.fstu-participationtype-dropdown' ).removeClass( 'is-open is-dropup' );
		$( '.fstu-participationtype-dropdown__toggle' ).attr( 'aria-expanded', 'false' );
		$( '.fstu-participationtype-dropdown__menu' ).css( {
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
		const $form = $( '#fstu-participationtype-form' );
		if ( $form.length ) {
			$form[ 0 ].reset();
		}
		$( '#fstu-participationtype-id' ).val( 0 );
		$( '#fstu-participationtype-order' ).val( '' );
		$( '#fstu-participationtype-form input[name="participation_type_type"][value="1"]' ).prop( 'checked', true );
		$( '#fstu-participationtype-form-submit' ).prop( 'disabled', false ).removeClass( 'is-loading' );
		isSubmittingForm = false;
		showFormMessage( '', false, true );
	}

	function showFormMessage( message, isError, hide ) {
		const $message = $( '#fstu-participationtype-form-message' );
		if ( hide ) {
			$message.addClass( 'fstu-hidden' ).removeClass( 'fstu-message--error fstu-message--success' ).text( '' );
			return;
		}
		$message.removeClass( 'fstu-hidden fstu-message--error fstu-message--success' )
			.addClass( isError ? 'fstu-message--error' : 'fstu-message--success' )
			.text( message );
	}

	function showPageMessage( message, isError ) {
		const $message = $( '#fstu-participationtype-page-message' );
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

