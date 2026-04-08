/**
 * JS модуля "Довідник міст".
 *
 * Version:     1.1.1
 * Date_update: 2026-04-07
 *
 * @package FSTU
 */

/* global fstuCityL10n */

jQuery( document ).ready( function ( $ ) {
	'use strict';

	if ( typeof fstuCityL10n === 'undefined' ) {
		return;
	}

	const permissions = fstuCityL10n.permissions || {};
	if ( ! permissions.canView ) {
		return;
	}

	const listState = {
		page: 1,
		perPage: 10,
		search: '',
		regionId: 0,
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
	let regionsLoaded = false;

	bindEvents();
	loadRegions( false );
	loadList();

	function bindEvents() {
		$( document ).on( 'click', function ( event ) {
			if ( ! $( event.target ).closest( '.fstu-city-dropdown' ).length ) {
				closeAllDropdowns();
			}
		} );

		$( document ).on( 'click', '.fstu-city-dropdown__toggle', function ( event ) {
			event.preventDefault();
			event.stopPropagation();

			const $dropdown = $( this ).closest( '.fstu-city-dropdown' );
			const isOpen = $dropdown.hasClass( 'is-open' );
			closeAllDropdowns();

			if ( ! isOpen ) {
				$dropdown.addClass( 'is-open' );
				$( this ).attr( 'aria-expanded', 'true' );
			}
		} );

		$( document ).on( 'click', '#fstu-city-refresh-btn', function () {
			loadList();
		} );

		$( document ).on( 'input', '#fstu-city-search', debounce( function () {
			listState.search = $( this ).val().trim();
			listState.page = 1;
			loadList();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-city-region-filter', function () {
			listState.regionId = parseInt( $( this ).val(), 10 ) || 0;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'change', '#fstu-city-per-page', function () {
			listState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'click', '#fstu-city-prev-page', function () {
			if ( listState.page > 1 ) {
				listState.page--;
				loadList();
			}
		} );

		$( document ).on( 'click', '#fstu-city-next-page', function () {
			if ( listState.page < listState.totalPages ) {
				listState.page++;
				loadList();
			}
		} );

		$( document ).on( 'click', '.fstu-city-page-btn', function () {
			listState.page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
			loadList();
		} );

		$( document ).on( 'click', '.fstu-city-view-btn', function () {
			closeAllDropdowns();
			openFormModal( parseInt( $( this ).data( 'city-id' ), 10 ) || 0, true );
		} );

		$( document ).on( 'click', '.fstu-city-edit-btn', function () {
			closeAllDropdowns();
			openFormModal( parseInt( $( this ).data( 'city-id' ), 10 ) || 0, false );
		} );

		$( document ).on( 'click', '.fstu-city-delete-btn', function () {
			closeAllDropdowns();
			deleteItem( parseInt( $( this ).data( 'city-id' ), 10 ) || 0 );
		} );

		$( document ).on( 'click', '#fstu-city-add-btn', function () {
			openFormModal( 0, false );
		} );

		$( document ).on( 'submit', '#fstu-city-form', function ( event ) {
			event.preventDefault();
			submitForm();
		} );

		$( document ).on( 'click', '#fstu-city-protocol-btn', function () {
			$( '#fstu-city-main' ).addClass( 'fstu-hidden' );
			$( '#fstu-city-protocol' ).removeClass( 'fstu-hidden' );
			$( '#fstu-city-protocol-btn' ).addClass( 'fstu-hidden' );
			$( '#fstu-city-protocol-back-btn' ).removeClass( 'fstu-hidden' );
			protocolState.page = 1;
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-city-protocol-back-btn', function () {
			$( '#fstu-city-main' ).removeClass( 'fstu-hidden' );
			$( '#fstu-city-protocol' ).addClass( 'fstu-hidden' );
			$( '#fstu-city-protocol-btn' ).removeClass( 'fstu-hidden' );
			$( '#fstu-city-protocol-back-btn' ).addClass( 'fstu-hidden' );
		} );

		$( document ).on( 'input', '#fstu-city-protocol-search', debounce( function () {
			protocolState.search = $( this ).val().trim();
			protocolState.page = 1;
			loadProtocol();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-city-protocol-per-page', function () {
			protocolState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			protocolState.page = 1;
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-city-protocol-prev-page', function () {
			if ( protocolState.page > 1 ) {
				protocolState.page--;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '#fstu-city-protocol-next-page', function () {
			if ( protocolState.page < protocolState.totalPages ) {
				protocolState.page++;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '.fstu-city-protocol-page-btn', function () {
			protocolState.page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
			loadProtocol();
		} );

		$( document ).on( 'click', '[data-close-modal]', function () {
			closeModal( $( this ).data( 'close-modal' ) );
		} );

		$( document ).on( 'click', '.fstu-modal-overlay', function ( event ) {
			if ( $( event.target ).is( '.fstu-modal-overlay' ) ) {
				closeModal( $( this ).attr( 'id' ) );
			}
		} );
	}

	function loadRegions( forceReload, onDone ) {
		if ( regionsLoaded && ! forceReload ) {
			if ( typeof onDone === 'function' ) {
				onDone();
			}
			return;
		}

		$.post( fstuCityL10n.ajaxUrl, {
			action: 'fstu_city_get_regions',
			nonce: fstuCityL10n.nonce,
		} ).done( function ( response ) {
			if ( ! response.success || ! response.data || ! Array.isArray( response.data.items ) ) {
				if ( typeof onDone === 'function' ) {
					onDone();
				}
				return;
			}

			populateRegionFilter( response.data.items );
			populateRegionSelect( response.data.items );
			regionsLoaded = true;
			if ( typeof onDone === 'function' ) {
				onDone();
			}
		} );
	}

	function populateRegionFilter( items ) {
		const $filter = $( '#fstu-city-region-filter' );
		const current = parseInt( $filter.val(), 10 ) || 0;
		let html = '<option value="0">Всі області</option>';

		items.forEach( function ( item ) {
			const id = parseInt( item.Region_ID, 10 ) || 0;
			const name = escapeHtml( item.Region_Name || '' );
			if ( id > 0 ) {
				html += '<option value="' + id + '">' + name + '</option>';
			}
		} );

		$filter.html( html ).val( String( current ) );
	}

	function populateRegionSelect( items ) {
		const $select = $( '#fstu-city-region-id' );
		const current = parseInt( $select.val(), 10 ) || 0;
		let html = '<option value="">Виберіть область</option>';

		items.forEach( function ( item ) {
			const id = parseInt( item.Region_ID, 10 ) || 0;
			const name = escapeHtml( item.Region_Name || '' );
			if ( id > 0 ) {
				html += '<option value="' + id + '">' + name + '</option>';
			}
		} );

		$select.html( html );
		if ( current > 0 ) {
			$select.val( String( current ) );
		}
	}

	function loadList() {
		if ( listState.loading ) {
			return;
		}

		listState.loading = true;
		setTable( '#fstu-city-tbody', 5, fstuCityL10n.messages.loading );

		$.post( fstuCityL10n.ajaxUrl, {
			action: 'fstu_city_get_list',
			nonce: fstuCityL10n.nonce,
			search: listState.search,
			region_id: listState.regionId,
			page: listState.page,
			per_page: listState.perPage,
		} ).done( function ( response ) {
			if ( response.success ) {
				$( '#fstu-city-tbody' ).html( response.data.html );
				listState.total = parseInt( response.data.total, 10 ) || 0;
				listState.page = parseInt( response.data.page, 10 ) || 1;
				listState.perPage = parseInt( response.data.per_page, 10 ) || 10;
				listState.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				updateListPagination();
			} else {
				setTable( '#fstu-city-tbody', 5, ( response.data && response.data.message ) || fstuCityL10n.messages.error );
			}
		} ).always( function () {
			listState.loading = false;
		} );
	}

	function loadProtocol() {
		if ( protocolState.loading ) {
			return;
		}

		protocolState.loading = true;
		setTable( '#fstu-city-protocol-tbody', 6, fstuCityL10n.messages.loading );

		$.post( fstuCityL10n.ajaxUrl, {
			action: 'fstu_city_get_protocol',
			nonce: fstuCityL10n.nonce,
			search: protocolState.search,
			page: protocolState.page,
			per_page: protocolState.perPage,
		} ).done( function ( response ) {
			if ( response.success ) {
				$( '#fstu-city-protocol-tbody' ).html( response.data.html );
				protocolState.total = parseInt( response.data.total, 10 ) || 0;
				protocolState.page = parseInt( response.data.page, 10 ) || 1;
				protocolState.perPage = parseInt( response.data.per_page, 10 ) || 10;
				protocolState.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				updateProtocolPagination();
			}
		} ).always( function () {
			protocolState.loading = false;
		} );
	}

	function openFormModal( cityId, readOnly ) {
		loadRegions( false );
		resetForm();

		$( '#fstu-city-mode' ).val( readOnly ? 'view' : ( cityId > 0 ? 'edit' : 'create' ) );
		$( '#fstu-city-form-title' ).text(
			readOnly
				? fstuCityL10n.messages.formViewTitle
				: ( cityId > 0 ? fstuCityL10n.messages.formEditTitle : fstuCityL10n.messages.formAddTitle )
		);
		$( '#fstu-city-form-submit' ).toggleClass( 'fstu-hidden', readOnly );
		$( '#fstu-city-form input[type="text"], #fstu-city-form input[type="number"], #fstu-city-form select' ).prop( 'disabled', readOnly );
		openModal( 'fstu-city-form-modal' );

		if ( cityId <= 0 ) {
			return;
		}

		loadRegions( false, function () {
			$.post( fstuCityL10n.ajaxUrl, {
				action: 'fstu_city_get_single',
				nonce: fstuCityL10n.nonce,
				city_id: cityId,
			} ).done( function ( response ) {
				if ( response.success && response.data.item ) {
					$( '#fstu-city-id' ).val( response.data.item.city_id || 0 );
					$( '#fstu-city-region-id' ).val( String( response.data.item.region_id || '' ) );
					$( '#fstu-city-name' ).val( response.data.item.city_name || '' );
					$( '#fstu-city-name-eng' ).val( response.data.item.city_name_eng || '' );
					$( '#fstu-city-order' ).val( response.data.item.city_order || '' );
				}
			} );
		} );
	}

	function submitForm() {
		if ( isSubmittingForm || ! permissions.canManage || 'view' === $( '#fstu-city-mode' ).val() ) {
			return;
		}

		isSubmittingForm = true;
		const isEdit = parseInt( $( '#fstu-city-id' ).val(), 10 ) > 0;
		const action = isEdit ? 'fstu_city_update' : 'fstu_city_create';

		$.post( fstuCityL10n.ajaxUrl, $( '#fstu-city-form' ).serialize() + '&action=' + encodeURIComponent( action ) )
			.done( function ( response ) {
				if ( response.success ) {
					closeModal( 'fstu-city-form-modal' );
					loadList();
				} else if ( response.data && response.data.message ) {
					$( '#fstu-city-form-message' )
						.removeClass( 'fstu-hidden' )
						.addClass( 'fstu-message--error' )
						.text( response.data.message );
				}
			} )
			.always( function () {
				isSubmittingForm = false;
			} );
	}

	function deleteItem( cityId ) {
		if ( ! permissions.canDelete || cityId <= 0 || ! window.confirm( fstuCityL10n.messages.confirmDelete ) ) {
			return;
		}

		$.post( fstuCityL10n.ajaxUrl, {
			action: 'fstu_city_delete',
			nonce: fstuCityL10n.nonce,
			city_id: cityId,
		} ).done( function ( response ) {
			if ( response.success ) {
				loadList();
			}
		} );
	}

	function closeAllDropdowns() {
		$( '.fstu-city-dropdown' ).removeClass( 'is-open' );
		$( '.fstu-city-dropdown__toggle' ).attr( 'aria-expanded', 'false' );
	}

	function resetForm() {
		const form = $( '#fstu-city-form' ).get( 0 );
		if ( form ) {
			form.reset();
		}

		$( '#fstu-city-id' ).val( 0 );
		$( '#fstu-city-form-message' )
			.addClass( 'fstu-hidden' )
			.removeClass( 'fstu-message--error fstu-message--success' )
			.empty();
	}

	function openModal( modalId ) {
		$( '#' + modalId ).removeClass( 'fstu-hidden' ).attr( 'aria-hidden', 'false' );
	}

	function closeModal( modalId ) {
		$( '#' + modalId ).addClass( 'fstu-hidden' ).attr( 'aria-hidden', 'true' );
	}

	function setTable( selector, colspan, message ) {
		$( selector ).html( '<tr class="fstu-row"><td colspan="' + colspan + '" class="fstu-no-results">' + escapeHtml( message ) + '</td></tr>' );
	}

	function updateListPagination() {
		$( '#fstu-city-per-page' ).val( String( listState.perPage ) );
		$( '#fstu-city-pagination-pages' ).html( buildPaginationButtons( listState.page, listState.totalPages, 'fstu-city-page-btn' ) );
		$( '#fstu-city-pagination-info' ).text( buildPaginationInfo( listState.total, listState.page, listState.totalPages ) );
		setPaginationArrowState( '#fstu-city-prev-page', '#fstu-city-next-page', listState.page, listState.totalPages );
	}

	function updateProtocolPagination() {
		$( '#fstu-city-protocol-per-page' ).val( String( protocolState.perPage ) );
		$( '#fstu-city-protocol-pagination-pages' ).html( buildPaginationButtons( protocolState.page, protocolState.totalPages, 'fstu-city-protocol-page-btn' ) );
		$( '#fstu-city-protocol-pagination-info' ).text( buildPaginationInfo( protocolState.total, protocolState.page, protocolState.totalPages ) );
		setPaginationArrowState( '#fstu-city-protocol-prev-page', '#fstu-city-protocol-next-page', protocolState.page, protocolState.totalPages );
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

