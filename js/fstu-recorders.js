/**
 * JS модуля «Реєстратори».
 *
 * Version:     1.0.1
 * Date_update: 2026-04-11
 *
 * @package FSTU
 */

/* global fstuRecordersL10n */

jQuery( document ).ready( function ( $ ) {
	'use strict';

	if ( typeof fstuRecordersL10n === 'undefined' ) {
		return;
	}

	const $module = $( '#fstu-recorders' );
	if ( ! $module.length ) {
		return;
	}

	const permissions = fstuRecordersL10n.permissions || {};
	const $viewBody = $( '#fstu-recorders-view-body' );
	const $viewFooter = $( '#fstu-recorders-view-footer' );
	const listState = {
		page: 1,
		perPage: parseInt( fstuRecordersL10n.defaults.perPage, 10 ) || 10,
		search: '',
		unitId: 0,
		total: 0,
		totalPages: 1,
		loading: false,
	};
	const protocolState = {
		page: 1,
		perPage: parseInt( fstuRecordersL10n.defaults.protocolPerPage, 10 ) || 10,
		search: '',
		total: 0,
		totalPages: 1,
		loading: false,
	};

	let isSubmittingForm = false;
	let deletingIds = [];
	let unitsLoaded = false;
	let unitsCache = [];
	let unitsRequest = null;
	let candidateRequest = null;
	let candidateSearchToken = 0;

	bindGlobalEvents();
	bindListEvents();
	bindProtocolEvents();
	bindModalEvents();
	bindFormEvents();

	ensureUnitsLoaded().always( function () {
		loadList();
	} );

	function bindGlobalEvents() {
		$( document ).on( 'click', function ( event ) {
			if ( ! $( event.target ).closest( '[data-dropdown]' ).length ) {
				closeAllDropdowns();
			}

			if ( ! $( event.target ).closest( '.fstu-form-group--autocomplete' ).length ) {
				hideCandidateResults();
			}
		} );

		$( window ).on( 'scroll resize', debounce( function () {
			closeAllDropdowns();
		}, 30 ) );
	}

	function bindListEvents() {
		$( document ).on( 'click', '#fstu-recorders-refresh-btn', function () {
			loadList();
		} );

		$( document ).on( 'change', '#fstu-recorders-unit-filter', function () {
			listState.unitId = parseInt( $( this ).val(), 10 ) || 0;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'input', '#fstu-recorders-search', debounce( function () {
			listState.search = $( this ).val().trim();
			listState.page = 1;
			loadList();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-recorders-per-page', function () {
			listState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'click', '#fstu-recorders-prev-page', function () {
			if ( listState.page > 1 ) {
				listState.page--;
				loadList();
			}
		} );

		$( document ).on( 'click', '#fstu-recorders-next-page', function () {
			if ( listState.page < listState.totalPages ) {
				listState.page++;
				loadList();
			}
		} );

		$( document ).on( 'click', '.fstu-recorders-page-btn', function () {
			const page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
			if ( page !== listState.page ) {
				listState.page = page;
				loadList();
			}
		} );

		$( document ).on( 'click', '#fstu-recorders-add-btn', function () {
			openFormModal();
		} );

		$( document ).on( 'click', '.fstu-recorders-dropdown__toggle', function ( event ) {
			event.preventDefault();
			event.stopPropagation();

			const $dropdown = $( this ).closest( '[data-dropdown]' );
			const isOpen = $dropdown.hasClass( 'is-open' );

			closeAllDropdowns();

			if ( ! isOpen ) {
				$dropdown.addClass( 'is-open' );
				$( this ).attr( 'aria-expanded', 'true' );
				positionDropdown( $dropdown );
			}
		} );

		$( document ).on( 'click', '.fstu-recorders-view-btn', function () {
			closeAllDropdowns();
			openViewModal( parseInt( $( this ).data( 'user-region-id' ), 10 ) || 0 );
		} );

		$( document ).on( 'click', '.fstu-recorders-edit-btn', function () {
			closeAllDropdowns();
			openFormModal( parseInt( $( this ).data( 'user-region-id' ), 10 ) || 0 );
		} );

		$( document ).on( 'click', '.fstu-recorders-delete-btn', function () {
			closeAllDropdowns();
			deleteItem( parseInt( $( this ).data( 'user-region-id' ), 10 ) || 0 );
		} );
	}

	function bindProtocolEvents() {
		$( document ).on( 'click', '#fstu-recorders-protocol-btn', function () {
			$( '#fstu-recorders-main' ).addClass( 'fstu-hidden' );
			$( '#fstu-recorders-protocol' ).removeClass( 'fstu-hidden' );
			$( '#fstu-recorders-protocol-btn' ).addClass( 'fstu-hidden' );
			$( '#fstu-recorders-protocol-back-btn' ).removeClass( 'fstu-hidden' );
			$( '#fstu-recorders-filter-wrap' ).addClass( 'fstu-hidden' );
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-recorders-protocol-back-btn', function () {
			$( '#fstu-recorders-main' ).removeClass( 'fstu-hidden' );
			$( '#fstu-recorders-protocol' ).addClass( 'fstu-hidden' );
			$( '#fstu-recorders-protocol-btn' ).removeClass( 'fstu-hidden' );
			$( '#fstu-recorders-protocol-back-btn' ).addClass( 'fstu-hidden' );
			$( '#fstu-recorders-filter-wrap' ).removeClass( 'fstu-hidden' );
		} );

		$( document ).on( 'input', '#fstu-recorders-protocol-search', debounce( function () {
			protocolState.search = $( this ).val().trim();
			protocolState.page = 1;
			loadProtocol();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-recorders-protocol-per-page', function () {
			protocolState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			protocolState.page = 1;
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-recorders-protocol-prev-page', function () {
			if ( protocolState.page > 1 ) {
				protocolState.page--;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '#fstu-recorders-protocol-next-page', function () {
			if ( protocolState.page < protocolState.totalPages ) {
				protocolState.page++;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '.fstu-recorders-protocol-page-btn', function () {
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
		$( document ).on( 'submit', '#fstu-recorders-form', function ( event ) {
			event.preventDefault();
			submitForm();
		} );

		$( document ).on( 'input', '#fstu-recorders-candidate-input', debounce( function () {
			const search = $( this ).val().trim();
			$( '#fstu-recorders-user-id' ).val( '0' );

			if ( search.length < 2 ) {
				showCandidateHint();
				return;
			}

			loadCandidates( search );
		}, 250 ) );

		$( document ).on( 'click', '.fstu-autocomplete-results__item', function () {
			$( '#fstu-recorders-user-id' ).val( $( this ).data( 'user-id' ) );
			$( '#fstu-recorders-candidate-input' ).val( $( this ).data( 'fio' ) );
			hideCandidateResults();
		} );
	}

	function ensureUnitsLoaded() {
		if ( unitsLoaded ) {
			return $.Deferred().resolve().promise();
		}

		if ( unitsRequest ) {
			return unitsRequest;
		}

		unitsRequest = $.ajax( {
			url: fstuRecordersL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuRecordersL10n.actions.getUnits,
				nonce: fstuRecordersL10n.nonce,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				unitsCache = response.data.items || [];
				unitsLoaded = true;
				renderUnitOptions();
			}
		} ).always( function () {
			unitsRequest = null;
		} );

		return unitsRequest;
	}

	function loadList() {
		if ( listState.loading ) {
			return;
		}

		listState.loading = true;
		closeAllDropdowns();
		setTableLoading( '#fstu-recorders-tbody', 6, fstuRecordersL10n.messages.loading );

		$.ajax( {
			url: fstuRecordersL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuRecordersL10n.actions.getList,
				nonce: fstuRecordersL10n.nonce,
				search: listState.search,
				page: listState.page,
				per_page: listState.perPage,
				unit_id: listState.unitId,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				$( '#fstu-recorders-tbody' ).html( response.data.html );
				listState.total = parseInt( response.data.total, 10 ) || 0;
				listState.page = parseInt( response.data.page, 10 ) || 1;
				listState.perPage = parseInt( response.data.per_page, 10 ) || 10;
				listState.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				updateListPagination();
			} else {
				showTableError( '#fstu-recorders-tbody', 6, response.data.message || fstuRecordersL10n.messages.error );
			}
		} ).fail( function () {
			showTableError( '#fstu-recorders-tbody', 6, fstuRecordersL10n.messages.error );
		} ).always( function () {
			listState.loading = false;
		} );
	}

	function loadProtocol() {
		if ( protocolState.loading ) {
			return;
		}

		protocolState.loading = true;
		setTableLoading( '#fstu-recorders-protocol-tbody', 6, fstuRecordersL10n.messages.loading );

		$.ajax( {
			url: fstuRecordersL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuRecordersL10n.actions.getProtocol,
				nonce: fstuRecordersL10n.nonce,
				search: protocolState.search,
				page: protocolState.page,
				per_page: protocolState.perPage,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				$( '#fstu-recorders-protocol-tbody' ).html( response.data.html );
				protocolState.total = parseInt( response.data.total, 10 ) || 0;
				protocolState.page = parseInt( response.data.page, 10 ) || 1;
				protocolState.perPage = parseInt( response.data.per_page, 10 ) || 10;
				protocolState.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				updateProtocolPagination();
			} else {
				showTableError( '#fstu-recorders-protocol-tbody', 6, response.data.message || fstuRecordersL10n.messages.protocolError );
			}
		} ).fail( function () {
			showTableError( '#fstu-recorders-protocol-tbody', 6, fstuRecordersL10n.messages.protocolError );
		} ).always( function () {
			protocolState.loading = false;
		} );
	}

	function loadCandidates( search ) {
		candidateSearchToken++;
		const currentToken = candidateSearchToken;

		if ( candidateRequest && typeof candidateRequest.abort === 'function' ) {
			candidateRequest.abort();
		}

		candidateRequest = $.ajax( {
			url: fstuRecordersL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuRecordersL10n.actions.getCandidates,
				nonce: fstuRecordersL10n.nonce,
				search: search,
			},
		} ).done( function ( response ) {
			if ( currentToken !== candidateSearchToken ) {
				return;
			}

			if ( response.success ) {
				renderCandidateResults( response.data.items || [] );
			} else {
				showCandidateError();
			}
		} ).fail( function () {
			if ( currentToken !== candidateSearchToken ) {
				return;
			}

			showCandidateError();
		} ).always( function () {
			candidateRequest = null;
		} );
	}

	function openViewModal( userRegionId ) {
		if ( userRegionId <= 0 ) {
			return;
		}

		$viewBody.html( '<p class="fstu-loader-inline">' + escapeHtml( fstuRecordersL10n.messages.loading ) + '</p>' );
		$viewFooter.empty();
		openModal( 'fstu-recorders-view-modal' );

		$.ajax( {
			url: fstuRecordersL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuRecordersL10n.actions.getSingle,
				nonce: fstuRecordersL10n.nonce,
				request_context: 'view',
				user_region_id: userRegionId,
			},
		} ).done( function ( response ) {
			if ( ! response.success ) {
				$viewBody.html( '<p class="fstu-alert">' + escapeHtml( response.data.message || fstuRecordersL10n.messages.error ) + '</p>' );
				return;
			}

			const item = response.data.item || {};
			let html = '<table class="fstu-info-table">';
			html += '<tr><th>Область</th><td>' + escapeHtml( item.Region_Name || '' ) + '</td></tr>';
			html += '<tr><th>Осередок</th><td>' + escapeHtml( item.Unit_ShortName || '' ) + '</td></tr>';
			html += '<tr><th>ПІБ</th><td>' + escapeHtml( item.FIO || '' ) + '</td></tr>';
			html += '<tr><th>E-mail</th><td>' + escapeHtml( item.user_email || '' ) + '</td></tr>';
			html += '<tr><th>Профіль</th><td><a class="fstu-recorders-link" target="_blank" rel="noopener noreferrer" href="' + escapeHtml( item.profile_url || '' ) + '">/Personal?ViewID=' + escapeHtml( String( item.User_ID || 0 ) ) + '</a></td></tr>';
			html += '</table>';
			$viewBody.html( html );

			let footerHtml = '';
			if ( item.profile_url ) {
				footerHtml += '<a class="fstu-btn fstu-btn--secondary" target="_blank" rel="noopener noreferrer" href="' + escapeHtml( item.profile_url ) + '">Відкрити профіль</a>';
			}
			if ( permissions.canManage ) {
				footerHtml += '<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-recorders-view-edit" data-user-region-id="' + escapeHtml( String( item.UserRegion_ID || 0 ) ) + '">Редагувати</button>';
			}
			footerHtml += '<button type="button" class="fstu-btn fstu-btn--secondary fstu-btn--cancel" data-close-modal="fstu-recorders-view-modal">Закрити</button>';
			$viewFooter.html( footerHtml );
		} ).fail( function () {
			$viewBody.html( '<p class="fstu-alert">' + escapeHtml( fstuRecordersL10n.messages.error ) + '</p>' );
		} );
	}

	$( document ).on( 'click', '#fstu-recorders-view-edit', function () {
		const userRegionId = parseInt( $( this ).data( 'user-region-id' ), 10 ) || 0;
		closeModal( 'fstu-recorders-view-modal' );
		openFormModal( userRegionId );
	} );

	function openFormModal( userRegionId ) {
		resetForm();

		ensureUnitsLoaded().always( function () {
			if ( userRegionId > 0 ) {
				$( '#fstu-recorders-form-title' ).text( fstuRecordersL10n.messages.formEditTitle );
				$( '#fstu-recorders-user-region-id' ).val( userRegionId );
				$( '#fstu-recorders-unit-id' ).prop( 'disabled', true );
				openModal( 'fstu-recorders-form-modal' );
				loadFormData( userRegionId );
			} else {
				$( '#fstu-recorders-form-title' ).text( fstuRecordersL10n.messages.formAddTitle );
				$( '#fstu-recorders-unit-id' ).prop( 'disabled', false );
				openModal( 'fstu-recorders-form-modal' );
			}
		} );
	}

	function loadFormData( userRegionId ) {
		$.ajax( {
			url: fstuRecordersL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuRecordersL10n.actions.getSingle,
				nonce: fstuRecordersL10n.nonce,
				request_context: 'edit',
				user_region_id: userRegionId,
			},
		} ).done( function ( response ) {
			if ( ! response.success ) {
				showFormMessage( response.data.message || fstuRecordersL10n.messages.error, true );
				return;
			}

			const item = response.data.item || {};
			$( '#fstu-recorders-unit-id' ).val( String( item.Unit_ID || 0 ) );
			$( '#fstu-recorders-user-id' ).val( String( item.User_ID || 0 ) );
			$( '#fstu-recorders-candidate-input' ).val( item.FIO || '' );
		} ).fail( function () {
			showFormMessage( fstuRecordersL10n.messages.error, true );
		} );
	}

	function submitForm() {
		if ( isSubmittingForm ) {
			return;
		}

		const userRegionId = parseInt( $( '#fstu-recorders-user-region-id' ).val(), 10 ) || 0;
		const userId = parseInt( $( '#fstu-recorders-user-id' ).val(), 10 ) || 0;
		if ( userId <= 0 ) {
			showFormMessage( fstuRecordersL10n.messages.candidateRequired, true );
			return;
		}

		const action = userRegionId > 0 ? fstuRecordersL10n.actions.update : fstuRecordersL10n.actions.create;
		const $submit = $( '#fstu-recorders-form-submit' );

		isSubmittingForm = true;
		$submit.prop( 'disabled', true ).addClass( 'is-loading' );

		$.ajax( {
			url: fstuRecordersL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: action,
				nonce: fstuRecordersL10n.nonce,
				user_region_id: userRegionId,
				unit_id: parseInt( $( '#fstu-recorders-unit-id' ).val(), 10 ) || 0,
				user_id: userId,
				fstu_website: $( '#fstu-recorders-website' ).val(),
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				showFormMessage( response.data.message || fstuRecordersL10n.messages.saveSuccess, false );
				window.setTimeout( function () {
					closeModal( 'fstu-recorders-form-modal' );
					loadList();
				}, 500 );
			} else {
				showFormMessage( response.data.message || fstuRecordersL10n.messages.saveError, true );
			}
		} ).fail( function () {
			showFormMessage( fstuRecordersL10n.messages.saveError, true );
		} ).always( function () {
			isSubmittingForm = false;
			$submit.prop( 'disabled', false ).removeClass( 'is-loading' );
		} );
	}

	function deleteItem( userRegionId ) {
		if ( userRegionId <= 0 || deletingIds.indexOf( userRegionId ) !== -1 ) {
			return;
		}

		if ( ! window.confirm( fstuRecordersL10n.messages.confirmDelete ) ) {
			return;
		}

		deletingIds.push( userRegionId );

		$.ajax( {
			url: fstuRecordersL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuRecordersL10n.actions.delete,
				nonce: fstuRecordersL10n.nonce,
				user_region_id: userRegionId,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				loadList();
			} else {
				window.alert( response.data.message || fstuRecordersL10n.messages.deleteError );
			}
		} ).fail( function () {
			window.alert( fstuRecordersL10n.messages.deleteError );
		} ).always( function () {
			deletingIds = deletingIds.filter( function ( id ) {
				return id !== userRegionId;
			} );
		} );
	}

	function renderUnitOptions() {
		const selectedFilter = String( listState.unitId || 0 );
		const selectedFormUnit = String( parseInt( $( '#fstu-recorders-unit-id' ).val(), 10 ) || 0 );
		const filterOptions = [ '<option value="0">' + escapeHtml( fstuRecordersL10n.messages.allUnits ) + '</option>' ];
		const formOptions = [ '<option value="0">Оберіть осередок</option>' ];

		unitsCache.forEach( function ( unit ) {
			const id = parseInt( unit.Unit_ID, 10 ) || 0;
			const label = unit.Unit_ShortName || '';
			if ( id <= 0 ) {
				return;
			}
			filterOptions.push( '<option value="' + escapeHtml( String( id ) ) + '">' + escapeHtml( label ) + '</option>' );
			formOptions.push( '<option value="' + escapeHtml( String( id ) ) + '">' + escapeHtml( label ) + '</option>' );
		} );

		$( '#fstu-recorders-unit-filter' ).html( filterOptions.join( '' ) ).val( selectedFilter );
		$( '#fstu-recorders-unit-id' ).html( formOptions.join( '' ) ).val( selectedFormUnit );
	}

	function renderCandidateResults( items ) {
		const $results = $( '#fstu-recorders-candidate-results' );
		if ( ! items.length ) {
			$results.html( '<div class="fstu-autocomplete-results__empty">' + escapeHtml( fstuRecordersL10n.messages.candidateEmpty ) + '</div>' ).removeClass( 'fstu-hidden' );
			return;
		}

		let html = '';
		items.forEach( function ( item ) {
			html += '<button type="button" class="fstu-autocomplete-results__item" data-user-id="' + escapeHtml( String( item.User_ID || 0 ) ) + '" data-fio="' + escapeHtml( item.FIO || '' ) + '">';
			html += '<span class="fstu-autocomplete-results__title">' + escapeHtml( item.FIO || '' ) + '</span>';
			if ( item.user_email ) {
				html += '<span class="fstu-autocomplete-results__meta">' + escapeHtml( item.user_email ) + '</span>';
			}
			html += '</button>';
		} );

		$results.html( html ).removeClass( 'fstu-hidden' );
	}

	function showCandidateHint() {
		$( '#fstu-recorders-candidate-results' ).html( '<div class="fstu-autocomplete-results__empty">' + escapeHtml( fstuRecordersL10n.messages.candidateHint ) + '</div>' ).removeClass( 'fstu-hidden' );
	}

	function showCandidateError() {
		$( '#fstu-recorders-candidate-results' ).html( '<div class="fstu-autocomplete-results__empty">' + escapeHtml( fstuRecordersL10n.messages.candidatesError ) + '</div>' ).removeClass( 'fstu-hidden' );
	}

	function hideCandidateResults() {
		candidateSearchToken++;
		$( '#fstu-recorders-candidate-results' ).addClass( 'fstu-hidden' ).empty();
	}

	function updateListPagination() {
		$( '#fstu-recorders-per-page' ).val( String( listState.perPage ) );
		$( '#fstu-recorders-pagination-pages' ).html( buildPaginationButtons( listState.page, listState.totalPages, 'fstu-recorders-page-btn' ) );
		$( '#fstu-recorders-pagination-info' ).text( buildPaginationInfo( listState.total, listState.page, listState.totalPages ) );
		setPaginationArrowState( '#fstu-recorders-prev-page', '#fstu-recorders-next-page', listState.page, listState.totalPages );
	}

	function updateProtocolPagination() {
		$( '#fstu-recorders-protocol-per-page' ).val( String( protocolState.perPage ) );
		$( '#fstu-recorders-protocol-pagination-pages' ).html( buildPaginationButtons( protocolState.page, protocolState.totalPages, 'fstu-recorders-protocol-page-btn' ) );
		$( '#fstu-recorders-protocol-pagination-info' ).text( buildPaginationInfo( protocolState.total, protocolState.page, protocolState.totalPages ) );
		setPaginationArrowState( '#fstu-recorders-protocol-prev-page', '#fstu-recorders-protocol-next-page', protocolState.page, protocolState.totalPages );
	}

	function openModal( modalId ) {
		$( '#' + modalId ).removeClass( 'fstu-hidden' ).attr( 'aria-hidden', 'false' );
		$( 'body' ).addClass( 'fstu-modal-open' );
	}

	function closeModal( modalId ) {
		const $modal = $( '#' + modalId );
		$modal.addClass( 'fstu-hidden' ).attr( 'aria-hidden', 'true' );
		if ( 'fstu-recorders-form-modal' === modalId ) {
			resetForm();
		}
		if ( ! $( '.fstu-modal-overlay:not(.fstu-hidden)' ).length ) {
			$( 'body' ).removeClass( 'fstu-modal-open' );
		}
	}

	function resetForm() {
		$( '#fstu-recorders-form' )[0].reset();
		$( '#fstu-recorders-user-region-id' ).val( '0' );
		$( '#fstu-recorders-user-id' ).val( '0' );
		$( '#fstu-recorders-unit-id' ).prop( 'disabled', false );
		showFormMessage( '', false );
		hideCandidateResults();
		if ( unitsLoaded ) {
			renderUnitOptions();
		}
	}

	function showFormMessage( message, isError ) {
		const $message = $( '#fstu-recorders-form-message' );
		if ( ! message ) {
			$message.addClass( 'fstu-hidden' ).removeClass( 'is-error is-success' ).empty();
			return;
		}
		$message.removeClass( 'fstu-hidden is-error is-success' ).addClass( isError ? 'is-error' : 'is-success' ).text( message );
	}

	function closeAllDropdowns() {
		$( '[data-dropdown].is-open' ).each( function () {
			$( this ).removeClass( 'is-open is-dropup' );
			$( this ).find( '.fstu-recorders-dropdown__toggle' ).attr( 'aria-expanded', 'false' );
			$( this ).find( '[data-dropdown-menu]' ).removeAttr( 'style' );
		} );
	}

	function positionDropdown( $dropdown ) {
		const $toggle = $dropdown.find( '.fstu-recorders-dropdown__toggle' );
		const $menu = $dropdown.find( '[data-dropdown-menu]' );
		if ( ! $toggle.length || ! $menu.length ) {
			return;
		}

		const toggleRect = $toggle[0].getBoundingClientRect();
		const menuEl = $menu[0];
		menuEl.style.position = 'fixed';
		menuEl.style.visibility = 'hidden';
		menuEl.style.display = 'block';
		menuEl.style.left = '0px';
		menuEl.style.top = '0px';

		const menuRect = menuEl.getBoundingClientRect();
		const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
		const openUp = toggleRect.bottom + menuRect.height + 8 > viewportHeight && toggleRect.top - menuRect.height - 8 > 0;
		const top = openUp ? ( toggleRect.top - menuRect.height - 6 ) : ( toggleRect.bottom + 6 );
		const left = Math.max( 8, toggleRect.right - menuRect.width );

		$dropdown.toggleClass( 'is-dropup', openUp );
		menuEl.style.left = left + 'px';
		menuEl.style.top = Math.max( 8, top ) + 'px';
		menuEl.style.visibility = 'visible';
	}

	function setTableLoading( selector, colspan, message ) {
		$( selector ).html( '<tr class="fstu-row"><td colspan="' + escapeHtml( String( colspan ) ) + '" class="fstu-no-results">' + escapeHtml( message ) + '</td></tr>' );
	}

	function showTableError( selector, colspan, message ) {
		$( selector ).html( '<tr class="fstu-row"><td colspan="' + escapeHtml( String( colspan ) ) + '" class="fstu-no-results fstu-no-results--error">' + escapeHtml( message ) + '</td></tr>' );
	}

	function buildPaginationButtons( currentPage, totalPages, buttonClass ) {
		if ( totalPages <= 1 ) {
			return '';
		}

		let start = Math.max( 1, currentPage - 2 );
		let end = Math.min( totalPages, start + 4 );
		start = Math.max( 1, end - 4 );

		let html = '';
		for ( let page = start; page <= end; page++ ) {
			html += '<button type="button" class="fstu-btn--page ' + buttonClass + ( page === currentPage ? ' fstu-btn--page-active' : '' ) + '" data-page="' + escapeHtml( String( page ) ) + '">' + escapeHtml( String( page ) ) + '</button>';
		}

		return html;
	}

	function buildPaginationInfo( total, page, totalPages ) {
		return 'Записів: ' + total + ' | Сторінка ' + page + ' з ' + totalPages;
	}

	function setPaginationArrowState( prevSelector, nextSelector, currentPage, totalPages ) {
		$( prevSelector ).prop( 'disabled', currentPage <= 1 );
		$( nextSelector ).prop( 'disabled', currentPage >= totalPages );
	}

	function debounce( callback, wait ) {
		let timeoutId = null;
		return function () {
			const context = this;
			const args = arguments;
			window.clearTimeout( timeoutId );
			timeoutId = window.setTimeout( function () {
				callback.apply( context, args );
			}, wait );
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

