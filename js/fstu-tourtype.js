/**
 * JS модуля "Довідник видів походів".
 * Список, фільтр категорій, пошук, пагінація, dropdown,
 * модальні вікна, CRUD-операції, drag-and-drop та протокол.
 *
 * Version:     1.1.2
 * Date_update: 2026-04-12
 *
 * @package FSTU
 */

/* global fstuTourTypeL10n */

jQuery( document ).ready( function ( $ ) {
	'use strict';

	if ( typeof fstuTourTypeL10n === 'undefined' ) {
		return;
	}

	const $module = $( '#fstu-tourtype' );
	if ( ! $module.length ) {
		return;
	}

	const permissions = fstuTourTypeL10n.permissions || {};
	const defaults = fstuTourTypeL10n.defaults || {};
	const listState = {
		page: 1,
		perPage: parseInt( defaults.perPage, 10 ) || 10,
		search: '',
		categoryId: parseInt( defaults.categoryId, 10 ) || 0,
		total: 0,
		totalPages: 1,
		loading: false,
		reorderSupported: false,
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
	let categories = [];

	bindGlobalEvents();
	bindListEvents();
	bindProtocolEvents();
	bindModalEvents();
	bindFormEvents();
	loadFilters();
	loadList();

	function bindGlobalEvents() {
		$( document ).on( 'click', function ( event ) {
			if ( ! $( event.target ).closest( '.fstu-tourtype-dropdown' ).length ) {
				closeAllDropdowns();
			}
		} );

		$( window ).on( 'scroll resize', function () {
			closeAllDropdowns();
		} );
	}

	function bindListEvents() {
		$( document ).on( 'input', '#fstu-tourtype-search', debounce( function () {
			listState.search = $( this ).val().trim();
			listState.page = 1;
			loadList();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-tourtype-category-filter', function () {
			listState.categoryId = parseInt( $( this ).val(), 10 ) || 0;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'change', '#fstu-tourtype-per-page', function () {
			listState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'click', '#fstu-tourtype-prev-page', function () {
			if ( listState.page > 1 ) {
				listState.page--;
				loadList();
			}
		} );

		$( document ).on( 'click', '#fstu-tourtype-next-page', function () {
			if ( listState.page < listState.totalPages ) {
				listState.page++;
				loadList();
			}
		} );

		$( document ).on( 'click', '.fstu-tourtype-page-btn', function () {
			const page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
			if ( page !== listState.page ) {
				listState.page = page;
				loadList();
			}
		} );

		$( document ).on( 'click', '.fstu-tourtype-dropdown__toggle', function ( event ) {
			event.preventDefault();
			event.stopPropagation();

			const $dropdown = $( this ).closest( '.fstu-tourtype-dropdown' );
			const isOpen = $dropdown.hasClass( 'is-open' );
			closeAllDropdowns();

			if ( ! isOpen ) {
				$dropdown.addClass( 'is-open' );
				positionDropdown( $dropdown );
				$( this ).attr( 'aria-expanded', 'true' );
			}
		} );

		$( document ).on( 'click', '#fstu-tourtype-add-btn', function () {
			openFormModal();
		} );

		$( document ).on( 'click', '.fstu-tourtype-view-btn', function () {
			closeAllDropdowns();
			const itemId = parseInt( $( this ).data( 'tourtype-id' ), 10 ) || 0;
			if ( itemId > 0 ) {
				openViewModal( itemId );
			}
		} );

		$( document ).on( 'click', '.fstu-tourtype-edit-btn', function () {
			closeAllDropdowns();
			const itemId = parseInt( $( this ).data( 'tourtype-id' ), 10 ) || 0;
			if ( itemId > 0 ) {
				openFormModal( itemId );
			}
		} );

		$( document ).on( 'click', '.fstu-tourtype-delete-btn', function () {
			closeAllDropdowns();
			const itemId = parseInt( $( this ).data( 'tourtype-id' ), 10 ) || 0;
			if ( itemId > 0 ) {
				deleteItem( itemId );
			}
		} );

		$( document ).on( 'dragstart', '.fstu-tourtype-row[draggable="true"]', function ( event ) {
			if ( listState.loading || reorderRequest || ! listState.canReorder ) {
				event.preventDefault();
				return;
			}

			draggedRow = this;
			$( this ).addClass( 'is-dragging' );
			event.originalEvent.dataTransfer.effectAllowed = 'move';
		} );

		$( document ).on( 'dragover', '.fstu-tourtype-row[draggable="true"]', function ( event ) {
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

		$( document ).on( 'dragend', '.fstu-tourtype-row[draggable="true"]', function () {
			$( '.fstu-tourtype-row' ).removeClass( 'is-dragging' );
			if ( draggedRow ) {
				sendReorder();
			}
			draggedRow = null;
		} );
	}

	function bindProtocolEvents() {
		$( document ).on( 'click', '#fstu-tourtype-protocol-btn', function () {
			$( '#fstu-tourtype-main' ).addClass( 'fstu-hidden' );
			$( '#fstu-tourtype-protocol' ).removeClass( 'fstu-hidden' );
			$( '#fstu-tourtype-filter-wrap' ).addClass( 'fstu-hidden' );
			$( '#fstu-tourtype-add-btn' ).addClass( 'fstu-hidden' );
			$( '#fstu-tourtype-protocol-btn' ).addClass( 'fstu-hidden' );
			$( '#fstu-tourtype-protocol-back-btn' ).removeClass( 'fstu-hidden' );
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-tourtype-protocol-back-btn', function () {
			$( '#fstu-tourtype-main' ).removeClass( 'fstu-hidden' );
			$( '#fstu-tourtype-protocol' ).addClass( 'fstu-hidden' );
			$( '#fstu-tourtype-filter-wrap' ).removeClass( 'fstu-hidden' );
			$( '#fstu-tourtype-add-btn' ).removeClass( 'fstu-hidden' );
			$( '#fstu-tourtype-protocol-btn' ).removeClass( 'fstu-hidden' );
			$( '#fstu-tourtype-protocol-back-btn' ).addClass( 'fstu-hidden' );
		} );

		$( document ).on( 'input', '#fstu-tourtype-protocol-search', debounce( function () {
			protocolState.search = $( this ).val().trim();
			protocolState.page = 1;
			loadProtocol();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-tourtype-protocol-per-page', function () {
			protocolState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			protocolState.page = 1;
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-tourtype-protocol-prev-page', function () {
			if ( protocolState.page > 1 ) {
				protocolState.page--;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '#fstu-tourtype-protocol-next-page', function () {
			if ( protocolState.page < protocolState.totalPages ) {
				protocolState.page++;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '.fstu-tourtype-protocol-page-btn', function () {
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
		$( document ).on( 'submit', '#fstu-tourtype-form', function ( event ) {
			event.preventDefault();
			submitForm();
		} );
	}

	function loadFilters() {
		$.ajax( {
			url: fstuTourTypeL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_tourtype_get_filters',
				nonce: fstuTourTypeL10n.nonce,
			},
		} ).done( function ( response ) {
			if ( ! response.success ) {
				showPageMessage( response.data.message || fstuTourTypeL10n.messages.filtersError, true );
				return;
			}

			categories = Array.isArray( response.data.categories ) ? response.data.categories : [];
			listState.reorderSupported = !! response.data.reorder_supported;
			renderCategorySelects();
			syncOrderFieldVisibility();
		} ).fail( function () {
			listState.reorderSupported = false;
			syncOrderFieldVisibility();
			showPageMessage( fstuTourTypeL10n.messages.filtersError, true );
		} );
	}

	function renderCategorySelects() {
		const filterOptions = [ '<option value="0">' + escapeHtml( fstuTourTypeL10n.messages.allCategories ) + '</option>' ];
		const formOptions = [ '<option value="">' + escapeHtml( fstuTourTypeL10n.messages.selectCategory ) + '</option>' ];

		categories.forEach( function ( item ) {
			const id = parseInt( item.HourCategories_ID, 10 ) || 0;
			const label = ( item.HourCategories_Code ? item.HourCategories_Code + '. ' : '' ) + ( item.HourCategories_Name || '' );
			if ( id > 0 ) {
				filterOptions.push( '<option value="' + id + '">' + escapeHtml( label ) + '</option>' );
				formOptions.push( '<option value="' + id + '">' + escapeHtml( label ) + '</option>' );
			}
		} );

		$( '#fstu-tourtype-category-filter' ).html( filterOptions.join( '' ) ).val( String( listState.categoryId ) );
		$( '#fstu-tourtype-category' ).html( formOptions.join( '' ) );
		syncOrderFieldVisibility();
	}

	function loadList() {
		if ( listState.loading ) {
			return;
		}

		listState.loading = true;
		closeAllDropdowns();
		setTableLoading( '#fstu-tourtype-tbody', 6, fstuTourTypeL10n.messages.loading );

		$.ajax( {
			url: fstuTourTypeL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_tourtype_get_list',
				nonce: fstuTourTypeL10n.nonce,
				search: listState.search,
				page: listState.page,
				per_page: listState.perPage,
				hour_categories_id: listState.categoryId,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				$( '#fstu-tourtype-tbody' ).html( response.data.html );
				listState.total = parseInt( response.data.total, 10 ) || 0;
				listState.page = parseInt( response.data.page, 10 ) || 1;
				listState.perPage = parseInt( response.data.per_page, 10 ) || 10;
				listState.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				listState.reorderSupported = !! response.data.reorder_supported;
				listState.canReorder = !! response.data.can_reorder;
				updateListPagination();
				updateReorderHint();
			} else {
				showTableError( '#fstu-tourtype-tbody', 6, response.data.message || fstuTourTypeL10n.messages.error );
			}
		} ).fail( function () {
			showTableError( '#fstu-tourtype-tbody', 6, fstuTourTypeL10n.messages.error );
		} ).always( function () {
			listState.loading = false;
		} );
	}

	function loadProtocol() {
		if ( protocolState.loading ) {
			return;
		}

		protocolState.loading = true;
		setTableLoading( '#fstu-tourtype-protocol-tbody', 6, fstuTourTypeL10n.messages.loading );

		$.ajax( {
			url: fstuTourTypeL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_tourtype_get_protocol',
				nonce: fstuTourTypeL10n.nonce,
				search: protocolState.search,
				page: protocolState.page,
				per_page: protocolState.perPage,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				$( '#fstu-tourtype-protocol-tbody' ).html( response.data.html );
				protocolState.total = parseInt( response.data.total, 10 ) || 0;
				protocolState.page = parseInt( response.data.page, 10 ) || 1;
				protocolState.perPage = parseInt( response.data.per_page, 10 ) || 10;
				protocolState.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				updateProtocolPagination();
			} else {
				showTableError( '#fstu-tourtype-protocol-tbody', 6, response.data.message || fstuTourTypeL10n.messages.protocolError );
			}
		} ).fail( function () {
			showTableError( '#fstu-tourtype-protocol-tbody', 6, fstuTourTypeL10n.messages.protocolError );
		} ).always( function () {
			protocolState.loading = false;
		} );
	}

	function updateListPagination() {
		$( '#fstu-tourtype-per-page' ).val( String( listState.perPage ) );
		$( '#fstu-tourtype-category-filter' ).val( String( listState.categoryId ) );
		$( '#fstu-tourtype-pagination-pages' ).html( buildPaginationButtons( listState.page, listState.totalPages, 'fstu-tourtype-page-btn' ) );
		$( '#fstu-tourtype-pagination-info' ).text( buildPaginationInfo( listState.total, listState.page, listState.totalPages ) );
		setPaginationArrowState( '#fstu-tourtype-prev-page', '#fstu-tourtype-next-page', listState.page, listState.totalPages );
	}

	function updateProtocolPagination() {
		$( '#fstu-tourtype-protocol-per-page' ).val( String( protocolState.perPage ) );
		$( '#fstu-tourtype-protocol-pagination-pages' ).html( buildPaginationButtons( protocolState.page, protocolState.totalPages, 'fstu-tourtype-protocol-page-btn' ) );
		$( '#fstu-tourtype-protocol-pagination-info' ).text( buildPaginationInfo( protocolState.total, protocolState.page, protocolState.totalPages ) );
		setPaginationArrowState( '#fstu-tourtype-protocol-prev-page', '#fstu-tourtype-protocol-next-page', protocolState.page, protocolState.totalPages );
	}

	function updateReorderHint() {
		if ( ! permissions.canManage ) {
			return;
		}

		if ( ! listState.reorderSupported ) {
			showPageMessage( fstuTourTypeL10n.messages.reorderUnavailable, true );
			return;
		}

		if ( listState.search !== '' ) {
			showPageMessage( fstuTourTypeL10n.messages.reorderSearchHint, false );
			return;
		}

		if ( ! listState.canReorder && listState.categoryId === 0 && listState.search === '' ) {
			showPageMessage( fstuTourTypeL10n.messages.reorderCategoryHint, false );
			return;
		}

		if ( listState.search === '' ) {
			showPageMessage( '', false );
		}
	}

	function openViewModal( itemId ) {
		const $viewBody = $( '#fstu-tourtype-view-body' );
		const $viewFooter = $( '#fstu-tourtype-view-footer' );

		$viewBody.html( '<p class="fstu-loader-inline">' + escapeHtml( fstuTourTypeL10n.messages.loading ) + '</p>' );
		$viewFooter.empty();
		openModal( 'fstu-tourtype-view-modal' );

		$.ajax( {
			url: fstuTourTypeL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_tourtype_get_single',
				nonce: fstuTourTypeL10n.nonce,
				tourtype_id: itemId,
			},
		} ).done( function ( response ) {
			if ( ! response.success ) {
				$viewBody.html( '<p class="fstu-alert">' + escapeHtml( response.data.message || fstuTourTypeL10n.messages.error ) + '</p>' );
				return;
			}

			const item = response.data;
			let html = '<table class="fstu-info-table">';
			html += '<tr><th>Категорія</th><td>' + escapeHtml( item.hour_categories_name || '' ) + '</td></tr>';
			html += '<tr><th>Найменування</th><td>' + escapeHtml( item.tourtype_name || '' ) + '</td></tr>';
			html += '<tr><th>Код походу</th><td>' + escapeHtml( item.tourtype_code || '' ) + '</td></tr>';
			html += '<tr><th>Мінімальна тривалість (дні)</th><td>' + escapeHtml( String( item.tourtype_day || 0 ) ) + '</td></tr>';
			if ( listState.reorderSupported ) {
				html += '<tr><th>Порядок</th><td>' + escapeHtml( String( item.tourtype_order || 0 ) ) + '</td></tr>';
			}
			html += '</table>';
			$viewBody.html( html );

			if ( permissions.canManage ) {
				$viewFooter.html(
					'<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-tourtype-view-edit" data-tourtype-id="' + escapeHtml( String( item.tourtype_id ) ) + '">' + escapeHtml( fstuTourTypeL10n.messages.edit ) + '</button>' +
					'<button type="button" class="fstu-btn fstu-btn--secondary" data-close-modal="fstu-tourtype-view-modal">' + escapeHtml( fstuTourTypeL10n.messages.close ) + '</button>'
				);
			} else {
				$viewFooter.html( '<button type="button" class="fstu-btn fstu-btn--secondary" data-close-modal="fstu-tourtype-view-modal">' + escapeHtml( fstuTourTypeL10n.messages.close ) + '</button>' );
			}
		} ).fail( function () {
			$viewBody.html( '<p class="fstu-alert">' + escapeHtml( fstuTourTypeL10n.messages.error ) + '</p>' );
		} );
	}

	$( document ).on( 'click', '#fstu-tourtype-view-edit', function () {
		const itemId = parseInt( $( this ).data( 'tourtype-id' ), 10 ) || 0;
		closeModal( 'fstu-tourtype-view-modal' );
		if ( itemId > 0 ) {
			openFormModal( itemId );
		}
	} );

	function openFormModal( itemId ) {
		resetForm();
		ensureFormCategories();
		syncOrderFieldVisibility();

		if ( itemId && itemId > 0 ) {
			$( '#fstu-tourtype-form-title' ).text( fstuTourTypeL10n.messages.formEditTitle );
			$( '#fstu-tourtype-id' ).val( itemId );
			openModal( 'fstu-tourtype-form-modal' );
			loadFormData( itemId );
		} else {
			$( '#fstu-tourtype-form-title' ).text( fstuTourTypeL10n.messages.formAddTitle );
			if ( listState.categoryId > 0 ) {
				$( '#fstu-tourtype-category' ).val( String( listState.categoryId ) );
			}
			$( '#fstu-tourtype-order' ).val( '' );
			openModal( 'fstu-tourtype-form-modal' );
		}
	}

	function ensureFormCategories() {
		if ( ! categories.length ) {
			loadFilters();
		}
		renderCategorySelects();
		$( '#fstu-tourtype-category option[value="0"]' ).remove();
	}

	function loadFormData( itemId ) {
		$.ajax( {
			url: fstuTourTypeL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_tourtype_get_single',
				nonce: fstuTourTypeL10n.nonce,
				tourtype_id: itemId,
			},
		} ).done( function ( response ) {
			if ( ! response.success ) {
				showFormMessage( response.data.message || fstuTourTypeL10n.messages.error, true );
				return;
			}

			$( '#fstu-tourtype-name' ).val( response.data.tourtype_name || '' );
			$( '#fstu-tourtype-code' ).val( response.data.tourtype_code || '' );
			$( '#fstu-tourtype-day' ).val( response.data.tourtype_day || 0 );
			$( '#fstu-tourtype-category' ).val( String( response.data.hour_categories_id || '' ) );
			$( '#fstu-tourtype-order' ).val( response.data.tourtype_order || 0 );
			syncOrderFieldVisibility();
		} ).fail( function () {
			showFormMessage( fstuTourTypeL10n.messages.error, true );
		} );
	}

	function submitForm() {
		if ( isSubmittingForm ) {
			return;
		}

		const itemId = parseInt( $( '#fstu-tourtype-id' ).val(), 10 ) || 0;
		const action = itemId > 0 ? 'fstu_tourtype_update' : 'fstu_tourtype_create';
		const $submitButton = $( '#fstu-tourtype-form-submit' );

		isSubmittingForm = true;
		$submitButton.prop( 'disabled', true ).addClass( 'is-loading' );

		$.ajax( {
			url: fstuTourTypeL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: action,
				nonce: fstuTourTypeL10n.nonce,
				tourtype_id: itemId,
				hour_categories_id: $( '#fstu-tourtype-category' ).val(),
				tourtype_name: $( '#fstu-tourtype-name' ).val().trim(),
				tourtype_code: $( '#fstu-tourtype-code' ).val().trim(),
				tourtype_day: $( '#fstu-tourtype-day' ).val(),
				tourtype_order: $( '#fstu-tourtype-order' ).val(),
				fstu_website: $( '#fstu-tourtype-website' ).val(),
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				showFormMessage( response.data.message || fstuTourTypeL10n.messages.saveSuccess, false );
				showPageMessage( response.data.message || fstuTourTypeL10n.messages.saveSuccess, false );
				window.setTimeout( function () {
					closeModal( 'fstu-tourtype-form-modal' );
					loadList();
				}, 500 );
			} else {
				showFormMessage( response.data.message || fstuTourTypeL10n.messages.saveError, true );
			}
		} ).fail( function () {
			showFormMessage( fstuTourTypeL10n.messages.saveError, true );
		} ).always( function () {
			isSubmittingForm = false;
			$submitButton.prop( 'disabled', false ).removeClass( 'is-loading' );
		} );
	}

	function deleteItem( itemId ) {
		if ( deletingIds.indexOf( itemId ) !== -1 ) {
			return;
		}

		if ( ! window.confirm( fstuTourTypeL10n.messages.confirmDelete ) ) {
			return;
		}

		deletingIds.push( itemId );
		const $deleteButtons = $( '.fstu-tourtype-delete-btn[data-tourtype-id="' + itemId + '"]' );
		$deleteButtons.prop( 'disabled', true );

		$.ajax( {
			url: fstuTourTypeL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_tourtype_delete',
				nonce: fstuTourTypeL10n.nonce,
				tourtype_id: itemId,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				showPageMessage( response.data.message || 'OK', false );
				loadList();
			} else {
				showPageMessage( response.data.message || fstuTourTypeL10n.messages.deleteError, true );
				window.alert( response.data.message || fstuTourTypeL10n.messages.deleteError );
			}
		} ).fail( function () {
			showPageMessage( fstuTourTypeL10n.messages.deleteError, true );
			window.alert( fstuTourTypeL10n.messages.deleteError );
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

		if ( ! listState.reorderSupported ) {
			showPageMessage( fstuTourTypeL10n.messages.reorderUnavailable, true );
			loadList();
			return;
		}

		if ( listState.categoryId <= 0 ) {
			showPageMessage( fstuTourTypeL10n.messages.reorderCategoryHint, true );
			loadList();
			return;
		}

		const items = [];
		const offset = ( listState.page - 1 ) * listState.perPage;
		$( '#fstu-tourtype-tbody .fstu-tourtype-row' ).each( function ( index ) {
			const itemId = parseInt( $( this ).data( 'tourtype-id' ), 10 ) || 0;
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
			url: fstuTourTypeL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: 'fstu_tourtype_reorder',
				nonce: fstuTourTypeL10n.nonce,
				hour_categories_id: listState.categoryId,
				items: JSON.stringify( items ),
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				showPageMessage( response.data.message || fstuTourTypeL10n.messages.reorderSuccess, false );
				loadList();
			} else {
				showPageMessage( response.data.message || fstuTourTypeL10n.messages.reorderError, true );
				window.alert( response.data.message || fstuTourTypeL10n.messages.reorderError );
				loadList();
			}
		} ).fail( function () {
			showPageMessage( fstuTourTypeL10n.messages.reorderError, true );
			window.alert( fstuTourTypeL10n.messages.reorderError );
			loadList();
		} ).always( function () {
			reorderRequest = null;
		} );
	}

	function positionDropdown( $dropdown ) {
		const $toggle = $dropdown.find( '.fstu-tourtype-dropdown__toggle' );
		const $menu = $dropdown.find( '.fstu-tourtype-dropdown__menu' );
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
		$( '.fstu-tourtype-dropdown' ).removeClass( 'is-open is-dropup' );
		$( '.fstu-tourtype-dropdown__toggle' ).attr( 'aria-expanded', 'false' );
		$( '.fstu-tourtype-dropdown__menu' ).css( {
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
		const $form = $( '#fstu-tourtype-form' );
		if ( $form.length ) {
			$form[ 0 ].reset();
		}
		$( '#fstu-tourtype-id' ).val( 0 );
		$( '#fstu-tourtype-order' ).val( '' );
		$( '#fstu-tourtype-form-submit' ).prop( 'disabled', false ).removeClass( 'is-loading' );
		isSubmittingForm = false;
		syncOrderFieldVisibility();
		showFormMessage( '', false, true );
	}

	function syncOrderFieldVisibility() {
		const $orderGroup = $( '#fstu-tourtype-order-group' );
		if ( ! $orderGroup.length ) {
			return;
		}

		if ( listState.reorderSupported ) {
			$orderGroup.removeClass( 'fstu-hidden' );
		} else {
			$orderGroup.addClass( 'fstu-hidden' );
			$( '#fstu-tourtype-order' ).val( '' );
		}
	}

	function showFormMessage( message, isError, hide ) {
		const $message = $( '#fstu-tourtype-form-message' );
		if ( hide ) {
			$message.addClass( 'fstu-hidden' ).removeClass( 'fstu-message--error fstu-message--success' ).text( '' );
			return;
		}
		$message.removeClass( 'fstu-hidden fstu-message--error fstu-message--success' )
			.addClass( isError ? 'fstu-message--error' : 'fstu-message--success' )
			.text( message );
	}

	function showPageMessage( message, isError ) {
		const $message = $( '#fstu-tourtype-page-message' );
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

