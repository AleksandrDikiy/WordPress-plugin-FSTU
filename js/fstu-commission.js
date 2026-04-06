/**
 * JS модуля "Довідник комісій та колегій ФСТУ".
 * Каркас модуля: список, пошук, пагінація, протокол, модальні вікна,
 * CRUD-дії та drag-and-drop сортування.
 *
 * Version:     1.0.1
 * Date_update: 2026-04-06
 *
 * @package FSTU
 */

/* global fstuCommissionL10n */

jQuery( document ).ready( function ( $ ) {
	'use strict';

	if ( typeof fstuCommissionL10n === 'undefined' ) {
		return;
	}

	const $module = $( '#fstu-commission' );
	if ( ! $module.length ) {
		return;
	}

	const permissions = fstuCommissionL10n.permissions || {};
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

	let draggedRow = null;
	let isSubmittingForm = false;
	let deletingIds = [];
	let reorderRequest = null;

	bindGlobalEvents();
	bindListEvents();
	bindProtocolEvents();
	bindModalEvents();
	bindFormEvents();

	loadList();

	function bindGlobalEvents() {
		$( document ).on( 'click', function ( event ) {
			if ( ! $( event.target ).closest( '.fstu-commission-dropdown' ).length ) {
				closeAllDropdowns();
			}
		} );
	}

	function bindListEvents() {
		$( document ).on( 'click', '#fstu-commission-refresh-btn', function () {
			loadList();
		} );

		$( document ).on( 'input', '#fstu-commission-search', debounce( function () {
			listState.search = $( this ).val().trim();
			listState.page = 1;
			loadList();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-commission-per-page', function () {
			listState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'click', '#fstu-commission-prev-page', function () {
			if ( listState.page > 1 ) {
				listState.page--;
				loadList();
			}
		} );

		$( document ).on( 'click', '#fstu-commission-next-page', function () {
			if ( listState.page < listState.totalPages ) {
				listState.page++;
				loadList();
			}
		} );

		$( document ).on( 'click', '.fstu-commission-page-btn', function () {
			const page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
			if ( page !== listState.page ) {
				listState.page = page;
				loadList();
			}
		} );

		$( document ).on( 'click', '.fstu-commission-dropdown__toggle', function ( event ) {
			event.preventDefault();
			event.stopPropagation();

			const $dropdown = $( this ).closest( '.fstu-commission-dropdown' );
			const isOpen = $dropdown.hasClass( 'is-open' );

			closeAllDropdowns();

			if ( ! isOpen ) {
				$dropdown.addClass( 'is-open' );
				positionDropdown( $dropdown );
				$( this ).attr( 'aria-expanded', 'true' );
			}
		} );

		$( document ).on( 'click', '#fstu-commission-add-btn', function () {
			openFormModal();
		} );

		$( document ).on( 'click', '.fstu-commission-view-btn', function () {
			closeAllDropdowns();
			const commissionId = parseInt( $( this ).data( 'commission-id' ), 10 ) || 0;
			if ( commissionId > 0 ) {
				openViewModal( commissionId );
			}
		} );

		$( document ).on( 'click', '.fstu-commission-edit-btn', function () {
			closeAllDropdowns();
			const commissionId = parseInt( $( this ).data( 'commission-id' ), 10 ) || 0;
			if ( commissionId > 0 ) {
				openFormModal( commissionId );
			}
		} );

		$( document ).on( 'click', '.fstu-commission-delete-btn', function () {
			closeAllDropdowns();
			const commissionId = parseInt( $( this ).data( 'commission-id' ), 10 ) || 0;
			if ( commissionId > 0 ) {
				deleteItem( commissionId );
			}
		} );

		$( document ).on( 'dragstart', '.fstu-commission-row[draggable="true"]', function ( event ) {
			if ( listState.loading || reorderRequest ) {
				event.preventDefault();
				return;
			}

			draggedRow = this;
			$( this ).addClass( 'is-dragging' );
			event.originalEvent.dataTransfer.effectAllowed = 'move';
		} );

		$( document ).on( 'dragover', '.fstu-commission-row[draggable="true"]', function ( event ) {
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

		$( document ).on( 'dragend', '.fstu-commission-row[draggable="true"]', function () {
			$( '.fstu-commission-row' ).removeClass( 'is-dragging' );
			if ( draggedRow ) {
				sendReorder();
			}
			draggedRow = null;
		} );
	}

	function bindProtocolEvents() {
		$( document ).on( 'click', '#fstu-commission-protocol-btn', function () {
			$( '#fstu-commission-main' ).addClass( 'fstu-hidden' );
			$( '#fstu-commission-protocol' ).removeClass( 'fstu-hidden' );
			$( '#fstu-commission-protocol-btn' ).addClass( 'fstu-hidden' );
			$( '#fstu-commission-protocol-back-btn' ).removeClass( 'fstu-hidden' );
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-commission-protocol-back-btn', function () {
			$( '#fstu-commission-main' ).removeClass( 'fstu-hidden' );
			$( '#fstu-commission-protocol' ).addClass( 'fstu-hidden' );
			$( '#fstu-commission-protocol-btn' ).removeClass( 'fstu-hidden' );
			$( '#fstu-commission-protocol-back-btn' ).addClass( 'fstu-hidden' );
		} );

		$( document ).on( 'input', '#fstu-commission-protocol-search', debounce( function () {
			protocolState.search = $( this ).val().trim();
			protocolState.page = 1;
			loadProtocol();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-commission-protocol-per-page', function () {
			protocolState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			protocolState.page = 1;
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-commission-protocol-prev-page', function () {
			if ( protocolState.page > 1 ) {
				protocolState.page--;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '#fstu-commission-protocol-next-page', function () {
			if ( protocolState.page < protocolState.totalPages ) {
				protocolState.page++;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '.fstu-commission-protocol-page-btn', function () {
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
		$( document ).on( 'submit', '#fstu-commission-form', function ( event ) {
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
		setTableLoading( '#fstu-commission-tbody', 5, fstuCommissionL10n.messages.loading );

		$.ajax( {
			url: fstuCommissionL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_commission_get_list',
				nonce: fstuCommissionL10n.nonce,
				search: listState.search,
				page: listState.page,
				per_page: listState.perPage,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				$( '#fstu-commission-tbody' ).html( response.data.html );
				listState.total = parseInt( response.data.total, 10 ) || 0;
				listState.page = parseInt( response.data.page, 10 ) || 1;
				listState.perPage = parseInt( response.data.per_page, 10 ) || 10;
				listState.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				updateListPagination();
			} else {
				showTableError( '#fstu-commission-tbody', 5, response.data.message || fstuCommissionL10n.messages.error );
			}
		} ).fail( function () {
			showTableError( '#fstu-commission-tbody', 5, fstuCommissionL10n.messages.error );
		} ).always( function () {
			listState.loading = false;
		} );
	}

	function loadProtocol() {
		if ( protocolState.loading ) {
			return;
		}

		protocolState.loading = true;
		setTableLoading( '#fstu-commission-protocol-tbody', 6, fstuCommissionL10n.messages.loading );

		$.ajax( {
			url: fstuCommissionL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_commission_get_protocol',
				nonce: fstuCommissionL10n.nonce,
				search: protocolState.search,
				page: protocolState.page,
				per_page: protocolState.perPage,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				$( '#fstu-commission-protocol-tbody' ).html( response.data.html );
				protocolState.total = parseInt( response.data.total, 10 ) || 0;
				protocolState.page = parseInt( response.data.page, 10 ) || 1;
				protocolState.perPage = parseInt( response.data.per_page, 10 ) || 10;
				protocolState.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				updateProtocolPagination();
			} else {
				showTableError( '#fstu-commission-protocol-tbody', 6, response.data.message || fstuCommissionL10n.messages.protocolError );
			}
		} ).fail( function () {
			showTableError( '#fstu-commission-protocol-tbody', 6, fstuCommissionL10n.messages.protocolError );
		} ).always( function () {
			protocolState.loading = false;
		} );
	}

	function updateListPagination() {
		$( '#fstu-commission-per-page' ).val( String( listState.perPage ) );
		$( '#fstu-commission-pagination-pages' ).html( buildPaginationButtons( listState.page, listState.totalPages, 'fstu-commission-page-btn' ) );
		$( '#fstu-commission-pagination-info' ).text( buildPaginationInfo( listState.total, listState.page, listState.totalPages ) );
		setPaginationArrowState( '#fstu-commission-prev-page', '#fstu-commission-next-page', listState.page, listState.totalPages );
	}

	function updateProtocolPagination() {
		$( '#fstu-commission-protocol-per-page' ).val( String( protocolState.perPage ) );
		$( '#fstu-commission-protocol-pagination-pages' ).html( buildPaginationButtons( protocolState.page, protocolState.totalPages, 'fstu-commission-protocol-page-btn' ) );
		$( '#fstu-commission-protocol-pagination-info' ).text( buildPaginationInfo( protocolState.total, protocolState.page, protocolState.totalPages ) );
		setPaginationArrowState( '#fstu-commission-protocol-prev-page', '#fstu-commission-protocol-next-page', protocolState.page, protocolState.totalPages );
	}

	function openViewModal( commissionId ) {
		const $viewBody = $( '#fstu-commission-view-body' );
		const $viewFooter = $( '#fstu-commission-view-footer' );

		$viewBody.html( '<p class="fstu-loader-inline">' + escapeHtml( fstuCommissionL10n.messages.loading ) + '</p>' );
		$viewFooter.empty();
		openModal( 'fstu-commission-view-modal' );

		$.ajax( {
			url: fstuCommissionL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_commission_get_single',
				nonce: fstuCommissionL10n.nonce,
				commission_id: commissionId,
				context: 'view',
			},
		} ).done( function ( response ) {
			if ( ! response.success ) {
				$viewBody.html( '<p class="fstu-alert">' + escapeHtml( response.data.message || fstuCommissionL10n.messages.error ) + '</p>' );
				return;
			}

			const item = response.data;
			let html = '<table class="fstu-info-table">';
			html += '<tr><th>Найменування</th><td>' + escapeHtml( item.commission_name || '' ) + '</td></tr>';
			html += '<tr><th>Google Group</th><td>' + ( item.commission_emailgooglegroup ? escapeHtml( item.commission_emailgooglegroup ) : '—' ) + '</td></tr>';
			html += '<tr><th>№ статті/сторінки</th><td>' + ( item.commission_number ? escapeHtml( item.commission_number ) : '—' ) + '</td></tr>';
			html += '<tr><th>Порядок</th><td>' + escapeHtml( String( item.commission_order || 0 ) ) + '</td></tr>';
			html += '</table>';
			$viewBody.html( html );

			if ( permissions.canManage ) {
				$viewFooter.html(
					'<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-commission-view-edit" data-commission-id="' + escapeHtml( String( item.commission_id ) ) + '">Редагувати</button>'
				);
			}
		} ).fail( function () {
			$viewBody.html( '<p class="fstu-alert">' + escapeHtml( fstuCommissionL10n.messages.error ) + '</p>' );
		} );
	}

	$( document ).on( 'click', '#fstu-commission-view-edit', function () {
		const commissionId = parseInt( $( this ).data( 'commission-id' ), 10 ) || 0;
		closeModal( 'fstu-commission-view-modal' );
		if ( commissionId > 0 ) {
			openFormModal( commissionId );
		}
	} );

	function openFormModal( commissionId ) {
		resetForm();

		if ( commissionId && commissionId > 0 ) {
			$( '#fstu-commission-form-title' ).text( fstuCommissionL10n.messages.formEditTitle );
			$( '#fstu-commission-id' ).val( commissionId );
			openModal( 'fstu-commission-form-modal' );
			loadFormData( commissionId );
		} else {
			$( '#fstu-commission-form-title' ).text( fstuCommissionL10n.messages.formAddTitle );
			openModal( 'fstu-commission-form-modal' );
		}
	}

	function loadFormData( commissionId ) {
		$.ajax( {
			url: fstuCommissionL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_commission_get_single',
				nonce: fstuCommissionL10n.nonce,
				commission_id: commissionId,
				context: 'edit',
			},
		} ).done( function ( response ) {
			if ( ! response.success ) {
				showFormMessage( response.data.message || fstuCommissionL10n.messages.error, true );
				return;
			}

			$( '#fstu-commission-name' ).val( response.data.commission_name || '' );
			$( '#fstu-commission-emailgooglegroup' ).val( response.data.commission_emailgooglegroup || '' );
			$( '#fstu-commission-number' ).val( response.data.commission_number || '' );
		} ).fail( function () {
			showFormMessage( fstuCommissionL10n.messages.error, true );
		} );
	}

	function submitForm() {
		if ( isSubmittingForm ) {
			return;
		}

		const commissionId = parseInt( $( '#fstu-commission-id' ).val(), 10 ) || 0;
		const action = commissionId > 0 ? 'fstu_commission_update' : 'fstu_commission_create';
		const $submitButton = $( '#fstu-commission-form-submit' );

		isSubmittingForm = true;
		$submitButton.prop( 'disabled', true ).addClass( 'is-loading' );

		$.ajax( {
			url: fstuCommissionL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: action,
				nonce: fstuCommissionL10n.nonce,
				commission_id: commissionId,
				commission_name: $( '#fstu-commission-name' ).val().trim(),
				commission_emailgooglegroup: $( '#fstu-commission-emailgooglegroup' ).val().trim(),
				commission_number: $( '#fstu-commission-number' ).val().trim(),
				fstu_website: $( '#fstu-commission-website' ).val(),
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				showFormMessage( response.data.message || fstuCommissionL10n.messages.saveSuccess, false );
				window.setTimeout( function () {
					closeModal( 'fstu-commission-form-modal' );
					loadList();
				}, 600 );
			} else {
				showFormMessage( response.data.message || fstuCommissionL10n.messages.saveError, true );
			}
		} ).fail( function () {
			showFormMessage( fstuCommissionL10n.messages.saveError, true );
		} ).always( function () {
			isSubmittingForm = false;
			$submitButton.prop( 'disabled', false ).removeClass( 'is-loading' );
		} );
	}

	function deleteItem( commissionId ) {
		if ( deletingIds.indexOf( commissionId ) !== -1 ) {
			return;
		}

		if ( ! window.confirm( fstuCommissionL10n.messages.confirmDelete ) ) {
			return;
		}

		deletingIds.push( commissionId );
		const $deleteButtons = $( '.fstu-commission-delete-btn[data-commission-id="' + commissionId + '"]' );
		$deleteButtons.prop( 'disabled', true );

		$.ajax( {
			url: fstuCommissionL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_commission_delete',
				nonce: fstuCommissionL10n.nonce,
				commission_id: commissionId,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				loadList();
			} else {
				window.alert( response.data.message || fstuCommissionL10n.messages.deleteError );
			}
		} ).fail( function () {
			window.alert( fstuCommissionL10n.messages.deleteError );
		} ).always( function () {
			deletingIds = deletingIds.filter( function ( id ) {
				return id !== commissionId;
			} );
			$deleteButtons.prop( 'disabled', false );
		} );
	}

	function sendReorder() {
		if ( ! permissions.canManage || reorderRequest ) {
			return;
		}

		const items = [];
		$( '#fstu-commission-tbody .fstu-commission-row' ).each( function ( index ) {
			const commissionId = parseInt( $( this ).data( 'commission-id' ), 10 ) || 0;
			if ( commissionId > 0 ) {
				items.push( {
					id: commissionId,
					order: index + 1,
				} );
			}
		} );

		if ( ! items.length ) {
			return;
		}

		reorderRequest = $.ajax( {
			url: fstuCommissionL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_commission_reorder',
				nonce: fstuCommissionL10n.nonce,
				items: JSON.stringify( items ),
			},
		} ).done( function ( response ) {
			if ( ! response.success ) {
				window.alert( response.data.message || fstuCommissionL10n.messages.reorderError );
				loadList();
				return;
			}

			loadList();
		} ).fail( function () {
			window.alert( fstuCommissionL10n.messages.reorderError );
			loadList();
		} ).always( function () {
			reorderRequest = null;
		} );
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

	function positionDropdown( $dropdown ) {
		const $menu = $dropdown.find( '.fstu-commission-dropdown__menu' );
		$dropdown.removeClass( 'is-dropup' );

		$menu.css( { display: 'block', visibility: 'hidden' } );
		const rect = $menu.get( 0 ).getBoundingClientRect();
		$menu.css( { display: '', visibility: '' } );

		if ( rect.bottom > window.innerHeight - 20 ) {
			$dropdown.addClass( 'is-dropup' );
		}
	}

	function closeAllDropdowns() {
		$( '.fstu-commission-dropdown' ).removeClass( 'is-open is-dropup' );
		$( '.fstu-commission-dropdown__toggle' ).attr( 'aria-expanded', 'false' );
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
		const $form = $( '#fstu-commission-form' );
		if ( $form.length ) {
			$form[ 0 ].reset();
		}
		$( '#fstu-commission-id' ).val( 0 );
		$( '#fstu-commission-form-submit' ).prop( 'disabled', false ).removeClass( 'is-loading' );
		isSubmittingForm = false;
		showFormMessage( '', false, true );
	}

	function showFormMessage( message, isError, hide ) {
		const $message = $( '#fstu-commission-form-message' );
		if ( hide ) {
			$message.addClass( 'fstu-hidden' ).removeClass( 'fstu-message--error fstu-message--success' ).text( '' );
			return;
		}
		$message.removeClass( 'fstu-hidden fstu-message--error fstu-message--success' )
			.addClass( isError ? 'fstu-message--error' : 'fstu-message--success' )
			.text( message );
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

