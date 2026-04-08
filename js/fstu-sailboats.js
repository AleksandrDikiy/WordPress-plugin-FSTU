/**
 * JS модуля "Реєстр суден".
 * Робочий список, фільтри, протокол, dropdown дій, перегляд, форма та службові операції.
 *
 * Version:     1.12.0
 * Date_update: 2026-04-08
 *
 * @package FSTU
 */

/* global fstuSailboatsL10n */

jQuery( document ).ready( function ( $ ) {
	'use strict';

	if ( typeof fstuSailboatsL10n === 'undefined' ) {
		return;
	}

	const $module = $( '#fstu-sailboats' );
	if ( ! $module.length ) {
		return;
	}

	const permissions = fstuSailboatsL10n.permissions || {};
	if ( ! permissions.canView ) {
		return;
	}

	const listState = {
		page: 1,
		perPage: parseInt( fstuSailboatsL10n.defaults.perPage, 10 ) || 10,
		search: '',
		regionId: 0,
		statusId: 0,
		total: 0,
		totalPages: 1,
		loading: false,
	};

	const protocolState = {
		page: 1,
		perPage: parseInt( fstuSailboatsL10n.defaults.protocolPerPage, 10 ) || 10,
		search: '',
		total: 0,
		totalPages: 1,
		loading: false,
	};

	let dictionariesCache = null;
	let isSubmittingForm = false;
	let activeActionRequests = 0;
	let existingSearchRequest = null;

	bindGlobalEvents();
	bindListEvents();
	bindProtocolEvents();
	bindModalEvents();
	bindFormEvents();
	loadDictionaries();
	loadList();

	function bindGlobalEvents() {
		$( document ).on( 'click', function ( event ) {
			if ( ! $( event.target ).closest( '.fstu-sailboats-dropdown' ).length ) {
				closeAllDropdowns();
			}
		} );

		// Перемикання табів у модалці перегляду
		$( document ).on( 'click', '.fstu-tab-btn', function () {
			const tabId = $( this ).data( 'tab' );
			const $wrapper = $( this ).closest( '.fstu-tabs-wrapper' );
			
			$wrapper.find( '.fstu-tab-btn' ).removeClass( 'is-active' );
			$( this ).addClass( 'is-active' );
			
			$wrapper.find( '.fstu-tab-content' ).removeClass( 'is-active' );
			$wrapper.find( '#tab-' + tabId ).addClass( 'is-active' );
		} );
	}

	function bindListEvents() {
		$( document ).on( 'click', '#fstu-sailboats-refresh-btn', function () {
			loadList();
		} );

		$( document ).on( 'input', '#fstu-sailboats-search', debounce( function () {
			listState.search = $( this ).val().trim();
			listState.page = 1;
			loadList();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-sailboats-region-filter', function () {
			listState.regionId = parseInt( $( this ).val(), 10 ) || 0;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'change', '#fstu-sailboats-status-filter', function () {
			listState.statusId = parseInt( $( this ).val(), 10 ) || 0;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'change', '#fstu-sailboats-per-page', function () {
			listState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'click', '#fstu-sailboats-prev-page', function () {
			if ( listState.page > 1 ) {
				listState.page -= 1;
				loadList();
			}
		} );

		$( document ).on( 'click', '#fstu-sailboats-next-page', function () {
			if ( listState.page < listState.totalPages ) {
				listState.page += 1;
				loadList();
			}
		} );

		$( document ).on( 'click', '.fstu-sailboats-page-btn', function () {
			const page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
			if ( page !== listState.page ) {
				listState.page = page;
				loadList();
			}
		} );

		$( document ).on( 'click', '.fstu-sailboats-dropdown__toggle', function ( event ) {
			event.preventDefault();
			event.stopPropagation();

			const $dropdown = $( this ).closest( '.fstu-sailboats-dropdown' );
			const isOpen = $dropdown.hasClass( 'is-open' );

			closeAllDropdowns();

			if ( ! isOpen ) {
				$dropdown.addClass( 'is-open' );
				positionDropdown( $dropdown );
				$( this ).attr( 'aria-expanded', 'true' );
			}
		} );

		$( document ).on( 'click', '#fstu-sailboats-add-btn', function () {
			openFormModal( 0 );
		} );

		$( document ).on( 'click', '.fstu-sailboats-view-btn', function () {
			closeAllDropdowns();
			const itemId = parseInt( $( this ).attr( 'data-sailboat-id' ), 10 ) || 0;
			if ( itemId > 0 ) {
				openViewModal( itemId );
			}
		} );

		$( document ).on( 'click', '.fstu-sailboats-edit-btn', function () {
			closeAllDropdowns();
			const itemId = parseInt( $( this ).attr( 'data-sailboat-id' ), 10 ) || 0;
			if ( permissions.canManage && itemId > 0 ) {
				openFormModal( itemId );
			}
		} );

		$( document ).on( 'click', '.fstu-sailboats-status-btn', function () {
			closeAllDropdowns();
			const itemId = parseInt( $( this ).attr( 'data-sailboat-id' ), 10 ) || 0;
			if ( permissions.canStatus && itemId > 0 ) {
				openStatusModal( itemId );
			}
		} );

		$( document ).on( 'click', '.fstu-sailboats-payment-btn', function () {
			closeAllDropdowns();
			const itemId = parseInt( $( this ).attr( 'data-sailboat-id' ), 10 ) || 0;
			if ( permissions.canPayments && itemId > 0 ) {
				openPaymentModal( itemId );
			}
		} );

		$( document ).on( 'click', '.fstu-sailboats-received-btn', function () {
			closeAllDropdowns();
			const itemId = parseInt( $( this ).attr( 'data-sailboat-id' ), 10 ) || 0;
			if ( permissions.canStatus && itemId > 0 ) {
				openReceivedModal( itemId );
			}
		} );

		$( document ).on( 'click', '.fstu-sailboats-sale-btn', function () {
			closeAllDropdowns();
			const itemId = parseInt( $( this ).attr( 'data-sailboat-id' ), 10 ) || 0;
			if ( permissions.canStatus && itemId > 0 ) {
				openSaleModal( itemId );
			}
		} );

		$( document ).on( 'click', '.fstu-sailboats-notification-btn', function () {
			closeAllDropdowns();
			const itemId = parseInt( $( this ).attr( 'data-sailboat-id' ), 10 ) || 0;
			if ( permissions.canNotify && itemId > 0 ) {
				openNotificationModal( itemId );
			}
		} );

		$( document ).on( 'click', '.fstu-sailboats-delete-btn', function () {
			closeAllDropdowns();
			const itemId = parseInt( $( this ).attr( 'data-sailboat-id' ), 10 ) || 0;
			if ( permissions.canHardDeleteAdmin && itemId > 0 ) {
				handleDeleteAction( itemId );
			}
		} );
	}

	function bindProtocolEvents() {
		$( document ).on( 'click', '#fstu-sailboats-protocol-btn', function () {
			$( '#fstu-sailboats-main' ).addClass( 'fstu-hidden' );
			$( '#fstu-sailboats-protocol' ).removeClass( 'fstu-hidden' );
			$( '#fstu-sailboats-protocol-btn' ).addClass( 'fstu-hidden' );
			$( '#fstu-sailboats-protocol-back-btn' ).removeClass( 'fstu-hidden' );
			protocolState.page = 1;
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-sailboats-protocol-back-btn', function () {
			$( '#fstu-sailboats-main' ).removeClass( 'fstu-hidden' );
			$( '#fstu-sailboats-protocol' ).addClass( 'fstu-hidden' );
			$( '#fstu-sailboats-protocol-btn' ).removeClass( 'fstu-hidden' );
			$( '#fstu-sailboats-protocol-back-btn' ).addClass( 'fstu-hidden' );
		} );

		$( document ).on( 'input', '#fstu-sailboats-protocol-search', debounce( function () {
			protocolState.search = $( this ).val().trim();
			protocolState.page = 1;
			loadProtocol();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-sailboats-protocol-per-page', function () {
			protocolState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			protocolState.page = 1;
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-sailboats-protocol-prev-page', function () {
			if ( protocolState.page > 1 ) {
				protocolState.page -= 1;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '#fstu-sailboats-protocol-next-page', function () {
			if ( protocolState.page < protocolState.totalPages ) {
				protocolState.page += 1;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '.fstu-sailboats-protocol-page-btn', function () {
			const page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
			if ( page !== protocolState.page ) {
				protocolState.page = page;
				loadProtocol();
			}
		} );
	}

	function bindModalEvents() {
		$( document ).on( 'click', '[data-close-modal]', function () {
			closeModal( '#' + $( this ).data( 'close-modal' ) );
		} );

		$( document ).on( 'click', '.fstu-modal-overlay', function ( event ) {
			if ( $( event.target ).is( '.fstu-modal-overlay' ) ) {
				closeModal( '#' + $( this ).attr( 'id' ) );
			}
		} );

		$( document ).on( 'keydown', function ( event ) {
			if ( event.key === 'Escape' ) {
				$( '.fstu-modal-overlay:not(.fstu-hidden)' ).each( function () {
					closeModal( '#' + $( this ).attr( 'id' ) );
				} );
			}
		} );
	}

	function bindFormEvents() {
		$( document ).on( 'submit', '#fstu-sailboats-form', function ( event ) {
			event.preventDefault();
			submitForm();
		} );

		$( document ).on( 'submit', '#fstu-sailboats-status-form', function ( event ) {
			event.preventDefault();
			submitStatusForm();
		} );

		$( document ).on( 'submit', '#fstu-sailboats-payment-form', function ( event ) {
			event.preventDefault();
			submitPaymentForm();
		} );

		$( document ).on( 'submit', '#fstu-sailboats-received-form', function ( event ) {
			event.preventDefault();
			submitReceivedForm();
		} );

		$( document ).on( 'submit', '#fstu-sailboats-sale-form', function ( event ) {
			event.preventDefault();
			submitSaleForm();
		} );

		$( document ).on( 'submit', '#fstu-sailboats-notification-form', function ( event ) {
			event.preventDefault();
			submitNotificationForm();
		} );

		$( document ).on( 'change', '#fstu-sailboats-create-mode', function () {
			toggleCreateModeFields( $( this ).val() );
		} );

		$( document ).on( 'change', '#fstu-sailboats-region-id', function () {
			populateCitiesSelect( parseInt( $( this ).val(), 10 ) || 0, parseInt( $( '#fstu-sailboats-city-id' ).val(), 10 ) || 0 );
		} );

		$( document ).on( 'input', '#fstu-sailboats-existing-search', debounce( function () {
			handleExistingSailboatSearchInput( $( this ).val() );
		}, 300 ) );

		$( document ).on( 'click', '.fstu-sailboats-existing-results__item', function () {
			selectExistingSailboat( {
				sailboatId: parseInt( $( this ).attr( 'data-sailboat-id' ), 10 ) || 0,
				name: $( this ).attr( 'data-name' ) || '',
				label: $( this ).attr( 'data-label' ) || '',
				registrationNumber: $( this ).attr( 'data-registration-number' ) || '',
				sailNumber: $( this ).attr( 'data-sail-number' ) || '',
				regionId: parseInt( $( this ).attr( 'data-region-id' ), 10 ) || 0,
				regionName: $( this ).attr( 'data-region-name' ) || '',
			} );
		} );

		$( document ).on( 'click', '#fstu-sailboats-existing-clear-btn', function () {
			clearExistingSailboatSelection( false );
			clearExistingSailboatResults();
		} );
	}

	function loadDictionaries() {
		$.ajax( {
			url: fstuSailboatsL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuSailboatsL10n.actions.getDictionaries,
				nonce: fstuSailboatsL10n.nonce,
			},
		} ).done( function ( response ) {
			if ( response.success && response.data && response.data.items ) {
				dictionariesCache = response.data.items;
				populateFilterSelect( '#fstu-sailboats-region-filter', dictionariesCache.regions || [], 'Region_ID', 'Region_Name', 'Усі області', listState.regionId );
				populateFilterSelect( '#fstu-sailboats-status-filter', dictionariesCache.statuses || [], 'Verification_ID', 'Verification_Name', 'Усі статуси', listState.statusId );
				populateFormSelect( '#fstu-sailboats-region-id', dictionariesCache.regions || [], 'Region_ID', 'Region_Name', 'Оберіть область', 0 );
				populateCitiesSelect( 0, 0 );
				populateFormSelect( '#fstu-sailboats-verification-id', dictionariesCache.statuses || [], 'Verification_ID', 'Verification_Name', 'Оберіть статус', 0 );
				populateFormSelect( '#fstu-sailboats-status-verification-id', dictionariesCache.statuses || [], 'Verification_ID', 'Verification_Name', 'Оберіть статус', 0 );
				populateFormSelect( '#fstu-sailboats-producer-id', dictionariesCache.producers || [], 'ProducerShips_ID', 'ProducerShips_Name', 'Оберіть виробника', 0 );
				populateFormSelect( '#fstu-sailboats-type-boat-id', dictionariesCache.type_boats || [], 'TypeBoat_ID', 'TypeBoat_Name', 'Оберіть тип', 0 );
				populateFormSelect( '#fstu-sailboats-type-hull-id', dictionariesCache.type_hulls || [], 'TypeHull_ID', 'TypeHull_Name', 'Оберіть тип корпусу', 0 );
				populateFormSelect( '#fstu-sailboats-type-construction-id', dictionariesCache.type_constructions || [], 'TypeConstruction_ID', 'TypeConstruction_Name', 'Оберіть тип конструкції', 0 );
				populateFormSelect( '#fstu-sailboats-type-ship-id', dictionariesCache.type_ships || [], 'TypeShip_ID', 'TypeShip_Name', 'Оберіть тип судна', 0 );
				populateFormSelect( '#fstu-sailboats-hull-material-id', dictionariesCache.hull_materials || [], 'HullMaterial_ID', 'HullMaterial_Name', 'Оберіть матеріал', 0 );
				populateFormSelect( '#fstu-sailboats-hull-color-id', dictionariesCache.hull_colors || [], 'HullColor_ID', 'HullColor_Name', 'Оберіть колір', 0 );
			}
		} );
	}

	function loadList() {
		if ( listState.loading ) {
			return;
		}

		listState.loading = true;
		closeAllDropdowns();
		setTableLoading( '#fstu-sailboats-tbody', fstuSailboatsL10n.table.colspan, fstuSailboatsL10n.messages.loading );

		$.ajax( {
			url: fstuSailboatsL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuSailboatsL10n.actions.getList,
				nonce: fstuSailboatsL10n.nonce,
				search: listState.search,
				page: listState.page,
				per_page: listState.perPage,
				region_id: listState.regionId,
				status_id: listState.statusId,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				$( '#fstu-sailboats-tbody' ).html( response.data.html );
				listState.total = parseInt( response.data.total, 10 ) || 0;
				listState.page = parseInt( response.data.page, 10 ) || 1;
				listState.perPage = parseInt( response.data.per_page, 10 ) || 10;
				listState.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				updatePagination( listState, '#fstu-sailboats-pagination-pages', '#fstu-sailboats-pagination-info', 'fstu-sailboats-page-btn' );
			} else {
				showTableError( '#fstu-sailboats-tbody', fstuSailboatsL10n.table.colspan, response.data.message || fstuSailboatsL10n.messages.error );
			}
		} ).fail( function () {
			showTableError( '#fstu-sailboats-tbody', fstuSailboatsL10n.table.colspan, fstuSailboatsL10n.messages.error );
		} ).always( function () {
			listState.loading = false;
		} );
	}

	function openViewModal( itemId ) {
		openModal( '#fstu-sailboats-view-modal' );
		$( '#fstu-sailboats-view-content' ).html( '<div class="fstu-placeholder-box">' + escapeHtml( fstuSailboatsL10n.messages.loading ) + '</div>' );

		$.ajax( {
			url: fstuSailboatsL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuSailboatsL10n.actions.getSingle,
				nonce: fstuSailboatsL10n.nonce,
				view_context: 'card',
				sailboat_id: itemId,
			},
		} ).done( function ( response ) {
			if ( response.success && response.data && response.data.item ) {
				$( '#fstu-sailboats-view-content' ).html( renderViewContent( response.data.item ) );
			} else {
				$( '#fstu-sailboats-view-content' ).html( '<div class="fstu-placeholder-box">' + escapeHtml( response.data.message || fstuSailboatsL10n.messages.viewError ) + '</div>' );
			}
		} ).fail( function () {
			$( '#fstu-sailboats-view-content' ).html( '<div class="fstu-placeholder-box">' + escapeHtml( fstuSailboatsL10n.messages.viewError ) + '</div>' );
		} );
	}

	function openFormModal( itemId ) {
		resetMainForm();
		ensureFormDictionaries();
		closeAllDropdowns();
		openModal( '#fstu-sailboats-form-modal' );

		if ( itemId > 0 ) {
			$( '#fstu-sailboats-form-title' ).text( fstuSailboatsL10n.messages.formEditTitle );
			$( '#fstu-sailboats-form-mode' ).val( 'edit' );
			$( '#fstu-sailboats-item-id' ).val( itemId );
			$( '#fstu-sailboats-create-mode' ).prop( 'disabled', true );
			loadMainFormItem( itemId );
		} else {
			$( '#fstu-sailboats-form-title' ).text( fstuSailboatsL10n.messages.formAddTitle );
			$( '#fstu-sailboats-form-mode' ).val( 'new' );
			$( '#fstu-sailboats-create-mode' ).prop( 'disabled', false );
			applyCurrentUserDefaultsToForm();
			toggleCreateModeFields( 'new' );
		}
	}

	function loadMainFormItem( itemId ) {
		showFormMessage( '#fstu-sailboats-form-message', fstuSailboatsL10n.messages.loading, 'success' );

		$.ajax( {
			url: fstuSailboatsL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuSailboatsL10n.actions.getSingle,
				nonce: fstuSailboatsL10n.nonce,
				sailboat_id: itemId,
			},
		} ).done( function ( response ) {
			if ( response.success && response.data && response.data.item ) {
				populateMainForm( response.data.item );
				hideFormMessage( '#fstu-sailboats-form-message' );
			} else {
				showFormMessage( '#fstu-sailboats-form-message', response.data.message || fstuSailboatsL10n.messages.viewError, 'error' );
			}
		} ).fail( function () {
			showFormMessage( '#fstu-sailboats-form-message', fstuSailboatsL10n.messages.viewError, 'error' );
		} );
	}

	function populateMainForm( item ) {
		ensureFormDictionaries();
		$( '#fstu-sailboats-item-id' ).val( item.item_id || 0 );
		$( '#fstu-sailboats-sailboat-id' ).val( item.sailboat_id || 0 );
		$( '#fstu-sailboats-name' ).val( item.name || '' );
		$( '#fstu-sailboats-name-eng' ).val( item.sailboat_name_eng || '' );
		$( '#fstu-sailboats-number-sail' ).val( item.sail_number || '' );
		$( '#fstu-sailboats-year' ).val( item.sailboat_year || '' );
		$( '#fstu-sailboats-last-name' ).val( item.appshipticket_last_name || '' );
		$( '#fstu-sailboats-first-name' ).val( item.appshipticket_first_name || '' );
		$( '#fstu-sailboats-patronymic' ).val( item.appshipticket_patronymic || '' );
		$( '#fstu-sailboats-last-name-eng' ).val( item.appshipticket_last_name_eng || '' );
		$( '#fstu-sailboats-first-name-eng' ).val( item.appshipticket_first_name_eng || '' );
		$( '#fstu-sailboats-np' ).val( item.appshipticket_np || '' );
		$( '#fstu-sailboats-sail-main' ).val( item.sailboat_sail_main || '' );
		$( '#fstu-sailboats-hill-length' ).val( item.sailboat_hill_length || '' );
		$( '#fstu-sailboats-crew-max' ).val( item.sailboat_crew_max || '' );
		$( '#fstu-sailboats-width-overall' ).val( item.sailboat_width_overall || '' );
		$( '#fstu-sailboats-clearance' ).val( item.sailboat_clearance || '' );
		$( '#fstu-sailboats-load-capacity' ).val( item.sailboat_load_capacity || '' );
		$( '#fstu-sailboats-motor-power' ).val( item.sailboat_motor_power || '' );
		$( '#fstu-sailboats-motor-number' ).val( item.sailboat_motor_number || '' );
		$( '#fstu-sailboats-region-id' ).val( String( item.region_id || 0 ) );
		populateCitiesSelect( parseInt( item.region_id, 10 ) || 0, parseInt( item.city_id, 10 ) || 0 );
		$( '#fstu-sailboats-producer-id' ).val( String( item.producer_id || 0 ) );
		$( '#fstu-sailboats-type-boat-id' ).val( String( item.type_boat_id || 0 ) );
		$( '#fstu-sailboats-type-hull-id' ).val( String( item.type_hull_id || 0 ) );
		$( '#fstu-sailboats-type-construction-id' ).val( String( item.type_construction_id || 0 ) );
		$( '#fstu-sailboats-type-ship-id' ).val( String( item.type_ship_id || 0 ) );
		$( '#fstu-sailboats-hull-material-id' ).val( String( item.hull_material_id || 0 ) );
		$( '#fstu-sailboats-hull-color-id' ).val( String( item.hull_color_id || 0 ) );
		$( '#fstu-sailboats-create-mode' ).val( 'new' );
		toggleCreateModeFields( 'new' );
	}

	function openStatusModal( itemId ) {
		resetActionForm( '#fstu-sailboats-status-form', '#fstu-sailboats-status-message' );
		ensureFormDictionaries();
		$( '#fstu-sailboats-status-item-id' ).val( itemId );
		openModal( '#fstu-sailboats-status-modal' );
		prefillActionItem( itemId, function ( item ) {
			$( '#fstu-sailboats-status-verification-id' ).val( String( item.verification_id || 0 ) );
		} );
	}

	function openPaymentModal( itemId ) {
		resetActionForm( '#fstu-sailboats-payment-form', '#fstu-sailboats-payment-message' );
		$( '#fstu-sailboats-payment-item-id' ).val( itemId );
		$( '#fstu-sailboats-payment-date' ).val( getTodayInputValue() );
		$( '#fstu-sailboats-payment-slot' ).val( 'V1' );
		openModal( '#fstu-sailboats-payment-modal' );
	}

	function openReceivedModal( itemId ) {
		resetActionForm( '#fstu-sailboats-received-form', '#fstu-sailboats-received-message' );
		$( '#fstu-sailboats-received-item-id' ).val( itemId );
		$( '#fstu-sailboats-received-date' ).val( getTodayInputValue() );
		openModal( '#fstu-sailboats-received-modal' );
	}

	function openSaleModal( itemId ) {
		resetActionForm( '#fstu-sailboats-sale-form', '#fstu-sailboats-sale-message' );
		$( '#fstu-sailboats-sale-item-id' ).val( itemId );
		$( '#fstu-sailboats-sale-date' ).val( getTodayInputValue() );
		openModal( '#fstu-sailboats-sale-modal' );
	}

	function openNotificationModal( itemId ) {
		resetActionForm( '#fstu-sailboats-notification-form', '#fstu-sailboats-notification-message' );
		$( '#fstu-sailboats-notification-item-id' ).val( itemId );
		$( '#fstu-sailboats-notification-type' ).val( 'membership' );
		openModal( '#fstu-sailboats-notification-modal' );
	}

	function prefillActionItem( itemId, onSuccess ) {
		$.ajax( {
			url: fstuSailboatsL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuSailboatsL10n.actions.getSingle,
				nonce: fstuSailboatsL10n.nonce,
				sailboat_id: itemId,
			},
		} ).done( function ( response ) {
			if ( response.success && response.data && response.data.item && typeof onSuccess === 'function' ) {
				onSuccess( response.data.item );
			}
		} );
	}

	function submitForm() {
		if ( isSubmittingForm ) {
			return;
		}

		isSubmittingForm = true;
		const formMode = $( '#fstu-sailboats-form-mode' ).val();
		const action = formMode === 'edit' ? fstuSailboatsL10n.actions.update : fstuSailboatsL10n.actions.create;
		const formData = $( '#fstu-sailboats-form' ).serializeArray();
		formData.push( { name: 'action', value: action } );
		formData.push( { name: 'nonce', value: fstuSailboatsL10n.nonce } );

		$( '#fstu-sailboats-form-submit' ).prop( 'disabled', true );
		hideFormMessage( '#fstu-sailboats-form-message' );

		$.ajax( {
			url: fstuSailboatsL10n.ajaxUrl,
			method: 'POST',
			data: formData,
		} ).done( function ( response ) {
			if ( response.success ) {
				showFormMessage( '#fstu-sailboats-form-message', response.data.message || fstuSailboatsL10n.messages.saveSuccess, 'success' );
				loadList();
				window.setTimeout( function () {
					closeModal( '#fstu-sailboats-form-modal' );
				}, 600 );
			} else {
				showFormMessage( '#fstu-sailboats-form-message', response.data.message || fstuSailboatsL10n.messages.saveError, 'error' );
			}
		} ).fail( function () {
			showFormMessage( '#fstu-sailboats-form-message', fstuSailboatsL10n.messages.saveError, 'error' );
		} ).always( function () {
			isSubmittingForm = false;
			$( '#fstu-sailboats-form-submit' ).prop( 'disabled', false );
		} );
	}

	function submitStatusForm() {
		submitActionForm( {
			formSelector: '#fstu-sailboats-status-form',
			messageSelector: '#fstu-sailboats-status-message',
			submitSelector: '#fstu-sailboats-status-submit',
			action: fstuSailboatsL10n.actions.updateStatus,
			successMessage: fstuSailboatsL10n.messages.statusSaved,
			fallbackError: fstuSailboatsL10n.messages.actionError,
			modalSelector: '#fstu-sailboats-status-modal',
		} );
	}

	function submitPaymentForm() {
		submitActionForm( {
			formSelector: '#fstu-sailboats-payment-form',
			messageSelector: '#fstu-sailboats-payment-message',
			submitSelector: '#fstu-sailboats-payment-submit',
			action: fstuSailboatsL10n.actions.setPayment,
			successMessage: fstuSailboatsL10n.messages.paymentSaved,
			fallbackError: fstuSailboatsL10n.messages.actionError,
			modalSelector: '#fstu-sailboats-payment-modal',
		} );
	}

	function submitReceivedForm() {
		submitActionForm( {
			formSelector: '#fstu-sailboats-received-form',
			messageSelector: '#fstu-sailboats-received-message',
			submitSelector: '#fstu-sailboats-received-submit',
			action: fstuSailboatsL10n.actions.markReceived,
			successMessage: fstuSailboatsL10n.messages.receivedSaved,
			fallbackError: fstuSailboatsL10n.messages.actionError,
			modalSelector: '#fstu-sailboats-received-modal',
		} );
	}

	function submitSaleForm() {
		submitActionForm( {
			formSelector: '#fstu-sailboats-sale-form',
			messageSelector: '#fstu-sailboats-sale-message',
			submitSelector: '#fstu-sailboats-sale-submit',
			action: fstuSailboatsL10n.actions.markSale,
			successMessage: fstuSailboatsL10n.messages.saleSaved,
			fallbackError: fstuSailboatsL10n.messages.actionError,
			modalSelector: '#fstu-sailboats-sale-modal',
		} );
	}

	function submitNotificationForm() {
		submitActionForm( {
			formSelector: '#fstu-sailboats-notification-form',
			messageSelector: '#fstu-sailboats-notification-message',
			submitSelector: '#fstu-sailboats-notification-submit',
			action: fstuSailboatsL10n.actions.sendNotification,
			successMessage: fstuSailboatsL10n.messages.notificationSaved,
			fallbackError: fstuSailboatsL10n.messages.actionError,
			modalSelector: '#fstu-sailboats-notification-modal',
		} );
	}

	function handleDeleteAction( itemId ) {
		if ( ! window.confirm( fstuSailboatsL10n.messages.deleteConfirm ) ) {
			return;
		}

		if ( activeActionRequests > 0 ) {
			return;
		}

		activeActionRequests += 1;

		$.ajax( {
			url: fstuSailboatsL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuSailboatsL10n.actions.delete,
				nonce: fstuSailboatsL10n.nonce,
				item_id: itemId,
				confirm_hard_delete: 1,
				fstu_website: '',
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				loadList();
				window.alert( response.data.message || fstuSailboatsL10n.messages.deleteSaved );
			} else {
				window.alert( response.data.message || fstuSailboatsL10n.messages.actionError );
			}
		} ).fail( function () {
			window.alert( fstuSailboatsL10n.messages.actionError );
		} ).always( function () {
			activeActionRequests = Math.max( 0, activeActionRequests - 1 );
		} );
	}

	function submitActionForm( config ) {
		if ( activeActionRequests > 0 ) {
			return;
		}

		activeActionRequests += 1;
		const formData = $( config.formSelector ).serializeArray();
		formData.push( { name: 'action', value: config.action } );
		formData.push( { name: 'nonce', value: fstuSailboatsL10n.nonce } );
		$( config.submitSelector ).prop( 'disabled', true );
		hideFormMessage( config.messageSelector );

		$.ajax( {
			url: fstuSailboatsL10n.ajaxUrl,
			method: 'POST',
			data: formData,
		} ).done( function ( response ) {
			if ( response.success ) {
				showFormMessage( config.messageSelector, response.data.message || config.successMessage, 'success' );
				loadList();
				window.setTimeout( function () {
					closeModal( config.modalSelector );
				}, 600 );
			} else {
				showFormMessage( config.messageSelector, response.data.message || config.fallbackError, 'error' );
			}
		} ).fail( function () {
			showFormMessage( config.messageSelector, config.fallbackError, 'error' );
		} ).always( function () {
			activeActionRequests = Math.max( 0, activeActionRequests - 1 );
			$( config.submitSelector ).prop( 'disabled', false );
		} );
	}

	function loadProtocol() {
		if ( protocolState.loading ) {
			return;
		}

		protocolState.loading = true;
		setTableLoading( '#fstu-sailboats-protocol-tbody', fstuSailboatsL10n.table.protocolColspan, fstuSailboatsL10n.messages.loading );

		$.ajax( {
			url: fstuSailboatsL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuSailboatsL10n.actions.getProtocol,
				nonce: fstuSailboatsL10n.nonce,
				search: protocolState.search,
				page: protocolState.page,
				per_page: protocolState.perPage,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				$( '#fstu-sailboats-protocol-tbody' ).html( response.data.html );
				protocolState.total = parseInt( response.data.total, 10 ) || 0;
				protocolState.page = parseInt( response.data.page, 10 ) || 1;
				protocolState.perPage = parseInt( response.data.per_page, 10 ) || 10;
				protocolState.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				updatePagination( protocolState, '#fstu-sailboats-protocol-pagination-pages', '#fstu-sailboats-protocol-pagination-info', 'fstu-sailboats-protocol-page-btn' );
			} else {
				showTableError( '#fstu-sailboats-protocol-tbody', fstuSailboatsL10n.table.protocolColspan, response.data.message || fstuSailboatsL10n.messages.protocolError );
			}
		} ).fail( function () {
			showTableError( '#fstu-sailboats-protocol-tbody', fstuSailboatsL10n.table.protocolColspan, fstuSailboatsL10n.messages.protocolError );
		} ).always( function () {
			protocolState.loading = false;
		} );
	}

	function ensureFormDictionaries() {
		if ( ! dictionariesCache ) {
			return;
		}

		populateFormSelect( '#fstu-sailboats-region-id', dictionariesCache.regions || [], 'Region_ID', 'Region_Name', 'Оберіть область', parseInt( $( '#fstu-sailboats-region-id' ).val(), 10 ) || 0 );
		populateCitiesSelect( parseInt( $( '#fstu-sailboats-region-id' ).val(), 10 ) || 0, parseInt( $( '#fstu-sailboats-city-id' ).val(), 10 ) || 0 );
		populateFormSelect( '#fstu-sailboats-verification-id', dictionariesCache.statuses || [], 'Verification_ID', 'Verification_Name', 'Оберіть статус', parseInt( $( '#fstu-sailboats-verification-id' ).val(), 10 ) || 0 );
		populateFormSelect( '#fstu-sailboats-status-verification-id', dictionariesCache.statuses || [], 'Verification_ID', 'Verification_Name', 'Оберіть статус', parseInt( $( '#fstu-sailboats-status-verification-id' ).val(), 10 ) || 0 );
		populateFormSelect( '#fstu-sailboats-producer-id', dictionariesCache.producers || [], 'ProducerShips_ID', 'ProducerShips_Name', 'Оберіть виробника', parseInt( $( '#fstu-sailboats-producer-id' ).val(), 10 ) || 0 );
		populateFormSelect( '#fstu-sailboats-type-boat-id', dictionariesCache.type_boats || [], 'TypeBoat_ID', 'TypeBoat_Name', 'Оберіть тип', parseInt( $( '#fstu-sailboats-type-boat-id' ).val(), 10 ) || 0 );
		populateFormSelect( '#fstu-sailboats-type-hull-id', dictionariesCache.type_hulls || [], 'TypeHull_ID', 'TypeHull_Name', 'Оберіть тип корпусу', parseInt( $( '#fstu-sailboats-type-hull-id' ).val(), 10 ) || 0 );
		populateFormSelect( '#fstu-sailboats-type-construction-id', dictionariesCache.type_constructions || [], 'TypeConstruction_ID', 'TypeConstruction_Name', 'Оберіть тип конструкції', parseInt( $( '#fstu-sailboats-type-construction-id' ).val(), 10 ) || 0 );
		populateFormSelect( '#fstu-sailboats-type-ship-id', dictionariesCache.type_ships || [], 'TypeShip_ID', 'TypeShip_Name', 'Оберіть тип судна', parseInt( $( '#fstu-sailboats-type-ship-id' ).val(), 10 ) || 0 );
		populateFormSelect( '#fstu-sailboats-hull-material-id', dictionariesCache.hull_materials || [], 'HullMaterial_ID', 'HullMaterial_Name', 'Оберіть матеріал', parseInt( $( '#fstu-sailboats-hull-material-id' ).val(), 10 ) || 0 );
		populateFormSelect( '#fstu-sailboats-hull-color-id', dictionariesCache.hull_colors || [], 'HullColor_ID', 'HullColor_Name', 'Оберіть колір', parseInt( $( '#fstu-sailboats-hull-color-id' ).val(), 10 ) || 0 );
	}

	function populateCitiesSelect( regionId, currentValue ) {
		const $select = $( '#fstu-sailboats-city-id' );
		if ( ! $select.length ) {
			return;
		}

		const cities = Array.isArray( dictionariesCache && dictionariesCache.cities ) ? dictionariesCache.cities : [];
		let html = '<option value="0">' + escapeHtml( 'Оберіть місто' ) + '</option>';

		cities.forEach( function ( item ) {
			const value = getDictionaryNumericValue( item, [ 'City_ID', 'item_id' ] );
			const itemRegionId = getDictionaryNumericValue( item, [ 'Region_ID', 'region_id' ] );
			const label = getDictionaryLabelValue( item, [ 'City_Name', 'Name', 'item_name' ] );

			if ( value > 0 && ( ! regionId || ! itemRegionId || itemRegionId === regionId ) ) {
				html += '<option value="' + value + '">' + escapeHtml( label || '—' ) + '</option>';
			}
		} );

		$select.html( html );
		$select.val( String( currentValue || 0 ) );
	}

	function resetMainForm() {
		const form = document.getElementById( 'fstu-sailboats-form' );
		if ( form ) {
			form.reset();
		}

		$( '#fstu-sailboats-item-id' ).val( 0 );
		$( '#fstu-sailboats-form-mode' ).val( 'new' );
		$( '#fstu-sailboats-sailboat-id' ).val( 0 );
		clearExistingSailboatSelection( false );
		$( '#fstu-sailboats-create-mode' ).val( 'new' ).prop( 'disabled', false );
		applyCurrentUserDefaultsToForm();
		toggleCreateModeFields( 'new' );
		hideFormMessage( '#fstu-sailboats-form-message' );
	}

	function applyCurrentUserDefaultsToForm() {
		const currentUser = fstuSailboatsL10n.currentUser || {};

		if ( ! $( '#fstu-sailboats-last-name' ).val().trim() ) {
			$( '#fstu-sailboats-last-name' ).val( currentUser.lastName || '' );
		}

		if ( ! $( '#fstu-sailboats-first-name' ).val().trim() ) {
			$( '#fstu-sailboats-first-name' ).val( currentUser.firstName || '' );
		}

		if ( ! $( '#fstu-sailboats-patronymic' ).val().trim() ) {
			$( '#fstu-sailboats-patronymic' ).val( currentUser.patronymic || '' );
		}
	}

	function resetActionForm( formSelector, messageSelector ) {
		const form = document.querySelector( formSelector );
		if ( form ) {
			form.reset();
		}
		hideFormMessage( messageSelector );
	}

	function toggleCreateModeFields( createMode ) {
		const isExisting = createMode === 'existing';
		const $existingGroup = $( '#fstu-sailboats-existing-id-group' );
		const $existingSearch = $( '#fstu-sailboats-existing-search' );

		$existingGroup.removeClass( 'fstu-hidden' ).toggleClass( 'is-disabled', ! isExisting );
		$existingSearch.prop( 'disabled', ! isExisting );
		$( '#fstu-sailboats-existing-sailboat-id' ).prop( 'required', isExisting );

		if ( isExisting ) {
			$existingSearch.attr( 'placeholder', 'Введіть назву або номер' );
			renderExistingSailboatResultsMessage( fstuSailboatsL10n.messages.existingSearchMinLength, false );
		} else {
			$existingSearch.attr( 'placeholder', 'Оберіть тип дії «Заявка для існуючого судна»' );
			clearExistingSailboatSelection( false );
			clearExistingSailboatResults();
		}
	}

	function handleExistingSailboatSearchInput( rawValue ) {
		const query = String( rawValue || '' ).trim();

		if ( ( parseInt( $( '#fstu-sailboats-existing-sailboat-id' ).val(), 10 ) || 0 ) > 0 ) {
			clearExistingSailboatSelection( true );
		}

		if ( query.length < 2 ) {
			renderExistingSailboatResultsMessage( query.length ? fstuSailboatsL10n.messages.existingSearchMinLength : '', false );
			return;
		}

		searchExistingSailboats( query );
	}

	function searchExistingSailboats( query ) {
		if ( existingSearchRequest && typeof existingSearchRequest.abort === 'function' ) {
			existingSearchRequest.abort();
		}

		renderExistingSailboatResultsMessage( fstuSailboatsL10n.messages.existingSearchLoading, false );

		existingSearchRequest = $.ajax( {
			url: fstuSailboatsL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuSailboatsL10n.actions.searchExisting,
				nonce: fstuSailboatsL10n.nonce,
				search: query,
			},
		} ).done( function ( response ) {
			if ( response && response.success && response.data ) {
				renderExistingSailboatResults( Array.isArray( response.data.items ) ? response.data.items : [] );
				return;
			}

			renderExistingSailboatResultsMessage( response && response.data && response.data.message ? response.data.message : fstuSailboatsL10n.messages.existingSearchError, true );
		} ).fail( function ( jqXHR, textStatus ) {
			if ( textStatus === 'abort' ) {
				return;
			}

			renderExistingSailboatResultsMessage( fstuSailboatsL10n.messages.existingSearchError, true );
		} ).always( function () {
			existingSearchRequest = null;
		} );
	}

	function renderExistingSailboatResults( items ) {
		const $results = $( '#fstu-sailboats-existing-results' );

		if ( ! items.length ) {
			renderExistingSailboatResultsMessage( fstuSailboatsL10n.messages.existingSearchEmpty, false );
			return;
		}

		let html = '';

		items.forEach( function ( item ) {
			const sailboatId = parseInt( item.sailboat_id, 10 ) || 0;
			const name = String( item.name || '' );
			const label = String( item.label || name || ( '#' + sailboatId ) );
			const registrationNumber = String( item.registration_number || '' );
			const sailNumber = String( item.sail_number || '' );
			const regionId = parseInt( item.region_id, 10 ) || 0;
			const regionName = String( item.region_name || '' );

			html += '<button type="button" class="fstu-sailboats-existing-results__item"'
				+ ' data-sailboat-id="' + escapeHtml( sailboatId ) + '"'
				+ ' data-name="' + escapeHtml( name ) + '"'
				+ ' data-label="' + escapeHtml( label ) + '"'
				+ ' data-registration-number="' + escapeHtml( registrationNumber ) + '"'
				+ ' data-sail-number="' + escapeHtml( sailNumber ) + '"'
				+ ' data-region-id="' + escapeHtml( regionId ) + '"'
				+ ' data-region-name="' + escapeHtml( regionName ) + '">';
			html += '<span class="fstu-sailboats-existing-results__title">' + escapeHtml( label ) + '</span>';
			if ( registrationNumber || sailNumber ) {
				html += '<span class="fstu-sailboats-existing-results__meta">';
				html += registrationNumber ? escapeHtml( '№ реєстрації: ' + registrationNumber ) : '';
				html += registrationNumber && sailNumber ? ' • ' : '';
				html += sailNumber ? escapeHtml( '№ на вітрилі: ' + sailNumber ) : '';
				html += '</span>';
			}
			html += '</button>';
		} );

		$results.removeClass( 'fstu-hidden is-error' ).html( html );
	}

	function renderExistingSailboatResultsMessage( message, isError ) {
		const $results = $( '#fstu-sailboats-existing-results' );
		const text = String( message || '' ).trim();

		if ( text === '' ) {
			clearExistingSailboatResults();
			return;
		}

		$results.removeClass( 'fstu-hidden' ).toggleClass( 'is-error', !! isError ).html( '<div class="fstu-sailboats-existing-results__message">' + escapeHtml( text ) + '</div>' );
	}

	function clearExistingSailboatResults() {
		$( '#fstu-sailboats-existing-results' ).addClass( 'fstu-hidden' ).removeClass( 'is-error' ).empty();
	}

	function selectExistingSailboat( item ) {
		const sailboatId = parseInt( item && item.sailboatId, 10 ) || 0;
		if ( sailboatId <= 0 ) {
			return;
		}

		const label = String( item.label || item.name || ( '#' + sailboatId ) );
		$( '#fstu-sailboats-existing-sailboat-id' ).val( sailboatId );
		$( '#fstu-sailboats-existing-search' ).val( label );
		$( '#fstu-sailboats-existing-selected' ).removeClass( 'fstu-hidden' ).text( fstuSailboatsL10n.messages.existingSelected + ' ' + label );
		$( '#fstu-sailboats-existing-clear-btn' ).removeClass( 'fstu-hidden' );
		clearExistingSailboatResults();
		applyExistingSailboatSelectionToForm( item );
	}

	function clearExistingSailboatSelection( preserveSearchValue ) {
		$( '#fstu-sailboats-existing-sailboat-id' ).val( 0 );
		$( '#fstu-sailboats-existing-selected' ).addClass( 'fstu-hidden' ).text( '' );
		$( '#fstu-sailboats-existing-clear-btn' ).addClass( 'fstu-hidden' );

		if ( ! preserveSearchValue ) {
			$( '#fstu-sailboats-existing-search' ).val( '' );
		}
	}

	function applyExistingSailboatSelectionToForm( item ) {
		const regionId = parseInt( item && item.regionId, 10 ) || 0;

		if ( item && typeof item.name === 'string' ) {
			$( '#fstu-sailboats-name' ).val( item.name );
		}

		if ( item && typeof item.sailNumber === 'string' ) {
			$( '#fstu-sailboats-number-sail' ).val( item.sailNumber );
		}

		if ( regionId > 0 ) {
			$( '#fstu-sailboats-region-id' ).val( String( regionId ) );
			populateCitiesSelect( regionId, 0 );
		}
	}

	function populateFilterSelect( selector, items, idKey, labelKey, defaultLabel, currentValue ) {
		const $select = $( selector );
		let html = '<option value="0">' + escapeHtml( defaultLabel ) + '</option>';

		items.forEach( function ( item ) {
			const value = getDictionaryNumericValue( item, [ idKey, 'item_id' ] );
			const label = getDictionaryLabelValue( item, [ labelKey, 'item_name', 'Name' ] );
			if ( value > 0 ) {
				html += '<option value="' + value + '">' + escapeHtml( label || '—' ) + '</option>';
			}
		} );

		$select.html( html );
		$select.val( String( currentValue || 0 ) );
	}

	function populateFormSelect( selector, items, idKey, labelKey, defaultLabel, currentValue ) {
		const $select = $( selector );
		if ( ! $select.length ) {
			return;
		}

		let html = '<option value="0">' + escapeHtml( defaultLabel ) + '</option>';

		items.forEach( function ( item ) {
			const value = getDictionaryNumericValue( item, [ idKey, 'item_id' ] );
			const label = getDictionaryLabelValue( item, [ labelKey, 'item_name', 'Name' ] );
			if ( value > 0 ) {
				html += '<option value="' + value + '">' + escapeHtml( label || '—' ) + '</option>';
			}
		} );

		$select.html( html );
		$select.val( String( currentValue || 0 ) );
	}

	function getDictionaryNumericValue( item, keys ) {
		for ( let index = 0; index < keys.length; index++ ) {
			const key = keys[ index ];
			const value = parseInt( item && item[ key ], 10 ) || 0;
			if ( value > 0 ) {
				return value;
			}
		}

		return 0;
	}

	function getDictionaryLabelValue( item, keys ) {
		for ( let index = 0; index < keys.length; index++ ) {
			const key = keys[ index ];
			const value = String( item && item[ key ] || '' ).trim();
			if ( value !== '' ) {
				return value;
			}
		}

		return '';
	}

	function renderViewContent( item ) {
		// --- ДАНІ ДЛЯ ТАБУ 1: ЗАГАЛЬНА ---
		const basicSection = {
			title: 'Основні дані судна',
			rows: [
				[ 'Найменування судна', item.name ],
				[ 'Найменування (ENG)', item.sailboat_name_eng ],
				[ '№ на вітрилі', item.sail_number ],
				[ 'Прізвище', item.appshipticket_last_name ],
				[ 'Ім’я', item.appshipticket_first_name ],
				[ 'По батькові', item.appshipticket_patronymic ],
				[ 'Прізвище (ENG)', item.appshipticket_last_name_eng ],
				[ 'Ім’я (ENG)', item.appshipticket_first_name_eng ],
			],
		};

		const classificationSection = {
			title: 'Класифікація судна',
			rows: [
				[ 'Тип корпусу', item.type_hull_name ],
				[ 'Тип конструкції', item.type_construction_name ],
				[ 'Тип судна', item.type_ship_name ],
				[ 'Матеріал корпусу', item.hull_material_name ],
				[ 'Колір корпусу', item.hull_color_name ],
				[ 'Тип човна', item.type_boat_name ],
				[ 'Виробник', item.producer_name ],
				[ 'Рік побудови', item.sailboat_year ],
			],
		};

		const technicalSection = {
			title: 'Технічні характеристики',
			rows: [
				[ 'Площа вітрила', item.sailboat_sail_main ],
				[ 'Довжина корпусу', item.sailboat_hill_length ],
				[ 'Ширина габаритна', item.sailboat_width_overall ],
				[ 'Вантажопідйомність', item.sailboat_load_capacity ],
				[ 'Макс. екіпаж', item.sailboat_crew_max ],
				[ 'Осадка / кліренс', item.sailboat_clearance ],
				[ 'Потужність двигуна', item.sailboat_motor_power ],
				[ 'Номер двигуна', item.sailboat_motor_number ],
			],
		};

		const bottomFields = {
			rows: [
				[ 'Область', item.region_name ],
				[ 'Місто', item.city_name ],
				[ 'Нова пошта', item.appshipticket_np ],
			],
		};

		// --- ДАНІ ДЛЯ ТАБУ 2: СЛУЖБОВА ---
		const metaSections = [
			{
				title: 'Службова інформація',
				rows: [
					[ '№ реєстрації', item.registration_number ],
					[ '№ заявки/квитка', item.appshipticket_number ],
					[ 'Статус', item.status_name ],
					[ 'AppShipTicket_ID', item.item_id ],
					[ 'Sailboat_ID', item.sailboat_id ],
					[ 'Попередній №', item.appshipticket_number_old ],
				],
			},
			{
				title: 'Часові показники',
				rows: [
					[ 'Створено', item.appshipticket_date_create ],
					[ 'Редаговано', item.appshipticket_date_edit ],
					[ 'Відправлено', item.appshipticket_date_send ],
					[ 'Оплачено', item.appshipticket_date_pay ],
					[ 'Вручено', item.appshipticket_date_receiving ],
					[ 'Продаж/вибуття', item.appshipticket_date_sale ],
				],
			},
			{
				title: 'Відкладені залежності',
				rows: [
					[ 'Статус Merilka', item.merilka_dependency_status ],
					[ 'Зв\'язків', item.merilka_dependency_count ],
					[ 'Ключі', item.merilka_dependency_matched_by ],
					[ 'Пояснення', item.merilka_dependency_message ],
				],
			}
		];

		if ( permissions.canFinance ) {
			// Додаємо лише один раз!
			metaSections.push( {
				title: 'Фінансові індикатори',
				rows: [
					[ 'Дата реєстрації', item.registration_date ],
					[ 'V1', item.V1 ],
					[ 'V2', item.V2 ],
					[ 'F1', item.F1 ],
					[ 'F2', item.F2 ],
				],
			} );
		}

		// Визначаємо кількість колонок для службового табу
		const metaColClass = metaSections.length === 4 ? 'fstu-view-layout__meta--4col' : 'fstu-view-layout__meta--3col';

		// --- ФОРМУЄМО HTML З ТАБАМИ ---
		let html = '<div class="fstu-tabs-wrapper">';
		
		// Навігація
		html += '<div class="fstu-tabs-nav">';
		html += '<button type="button" class="fstu-tab-btn is-active" data-tab="general">ЗАГАЛЬНА</button>';
		html += '<button type="button" class="fstu-tab-btn" data-tab="service">СЛУЖБОВА</button>';
		html += '<button type="button" class="fstu-tab-btn" data-tab="merilka">МЕРИЛКИ</button>';
		html += '</div>';

		// Контейнер контенту
		html += '<div class="fstu-tabs-container">';

		// ТАБ 1: ЗАГАЛЬНА
		html += '<div class="fstu-tab-content is-active" id="tab-general">';
		html += '<div class="fstu-view-layout">';
		html += '<div class="fstu-view-layout__column">' + renderViewSection( basicSection.title, basicSection.rows ) + '</div>';
		html += '<div class="fstu-view-layout__column">' + renderViewSection( classificationSection.title, classificationSection.rows ) + '</div>';
		html += '<div class="fstu-view-layout__column">' + renderViewSection( technicalSection.title, technicalSection.rows ) + '</div>';
		html += '<div class="fstu-view-layout__bottom">' + bottomFields.rows.map( function ( row ) { return renderViewValueCard( row[ 0 ], row[ 1 ] ); } ).join( '' ) + '</div>';
		html += '</div>';
		html += '</div>';

		// ТАБ 2: СЛУЖБОВА
		html += '<div class="fstu-tab-content" id="tab-service">';
		html += '<div class="fstu-view-layout__meta ' + metaColClass + '">';
		html += renderViewSectionsColumn( metaSections );
		html += '</div>';
		html += '</div>';

		// ТАБ 3: МЕРИЛКИ
		html += '<div class="fstu-tab-content" id="tab-merilka">';
		html += '<div class="fstu-placeholder-box" style="text-align: center; padding: 40px; border-radius: 6px;">Тут буде перелік мерилок (модуль у розробці)</div>';
		html += '</div>';

		html += '</div>'; // end container
		html += '</div>'; // end wrapper

		return html;
	}

	function renderViewSectionsColumn( sections ) {
		return sections.map( function ( section ) {
			return renderViewSection( section.title, section.rows );
		} ).join( '' );
	}

	function renderViewSection( title, rows ) {
		let html = '<div class="fstu-view-section">';
		html += '<h4 class="fstu-form-section__title">' + escapeHtml( title ) + '</h4>';
		html += '<div class="fstu-view-grid">';

		rows.forEach( function ( row ) {
			html += '<div class="fstu-view-grid__row">';
			html += '<div class="fstu-view-grid__label">' + escapeHtml( row[ 0 ] ) + '</div>';
			html += '<div class="fstu-view-grid__value">' + escapeHtml( normalizeViewValue( row[ 1 ] ) ) + '</div>';
			html += '</div>';
		} );

		html += '</div>';
		html += '</div>';

		return html;
	}

	function renderViewValueCard( title, value ) {
		let html = '<div class="fstu-view-section fstu-view-section--compact-card">';
		html += '<h4 class="fstu-form-section__title">' + escapeHtml( title ) + '</h4>';
		html += '<div class="fstu-view-grid__value">' + escapeHtml( normalizeViewValue( value ) ) + '</div>';
		html += '</div>';

		return html;
	}

	function normalizeViewValue( value ) {
		if ( value === null || typeof value === 'undefined' ) {
			return '—';
		}

		const text = String( value ).trim();
		return text !== '' ? text : '—';
	}

	function updatePagination( state, pagesSelector, infoSelector, buttonClass ) {
		const totalPages = Math.max( 1, state.totalPages );
		const currentPage = Math.min( state.page, totalPages );
		const visibleRadius = 2;
		const startPage = Math.max( 1, currentPage - visibleRadius );
		const endPage = Math.min( totalPages, currentPage + visibleRadius );
		let html = '';

		if ( startPage > 1 ) {
			html += buildPaginationButton( 1, currentPage, buttonClass );

			if ( startPage > 2 ) {
				html += '<span class="fstu-pagination__ellipsis">…</span>';
			}
		}

		for ( let page = startPage; page <= endPage; page++ ) {
			const isActive = page === currentPage ? ' fstu-btn--page-active' : '';
			html += '<button type="button" class="fstu-btn--page ' + buttonClass + isActive + '" data-page="' + page + '">' + page + '</button>';
		}

		if ( endPage < totalPages ) {
			if ( endPage < totalPages - 1 ) {
				html += '<span class="fstu-pagination__ellipsis">…</span>';
			}

			html += buildPaginationButton( totalPages, currentPage, buttonClass );
		}

		$( pagesSelector ).html( html );
		$( infoSelector ).text( 'Записів: ' + state.total + ' | Сторінка ' + currentPage + ' з ' + totalPages );
	}

	function buildPaginationButton( page, currentPage, buttonClass ) {
		const isActive = page === currentPage ? ' fstu-btn--page-active' : '';

		return '<button type="button" class="fstu-btn--page ' + buttonClass + isActive + '" data-page="' + page + '">' + page + '</button>';
	}

	function closeAllDropdowns() {
		$( '.fstu-sailboats-dropdown' ).removeClass( 'is-open is-dropup' );
		$( '.fstu-sailboats-dropdown__toggle' ).attr( 'aria-expanded', 'false' );
	}

	function positionDropdown( $dropdown ) {
		const $menu = $dropdown.find( '.fstu-sailboats-dropdown__menu' );
		if ( ! $menu.length ) {
			return;
		}

		$dropdown.removeClass( 'is-dropup' );
		$menu.css( 'visibility', 'hidden' );
		$dropdown.addClass( 'is-open' );

		const rect = $menu.get( 0 ).getBoundingClientRect();
		if ( rect.bottom > window.innerHeight && rect.top > rect.height ) {
			$dropdown.addClass( 'is-dropup' );
		}

		$menu.css( 'visibility', '' );
	}

	function setTableLoading( tbodySelector, colspan, message ) {
		$( tbodySelector ).html( '<tr class="fstu-row"><td colspan="' + colspan + '" class="fstu-no-results">' + escapeHtml( message ) + '</td></tr>' );
	}

	function showTableError( tbodySelector, colspan, message ) {
		$( tbodySelector ).html( '<tr class="fstu-row"><td colspan="' + colspan + '" class="fstu-no-results">' + escapeHtml( message ) + '</td></tr>' );
	}

	function showFormMessage( selector, message, type ) {
		const $message = $( selector );
		$message.removeClass( 'fstu-hidden fstu-form-message--success fstu-form-message--error' );
		$message.addClass( type === 'success' ? 'fstu-form-message--success' : 'fstu-form-message--error' );
		$message.text( message );
	}

	function hideFormMessage( selector ) {
		$( selector ).addClass( 'fstu-hidden' ).removeClass( 'fstu-form-message--success fstu-form-message--error' ).text( '' );
	}

	function getTodayInputValue() {
		const now = new Date();
		const year = now.getFullYear();
		const month = String( now.getMonth() + 1 ).padStart( 2, '0' );
		const day = String( now.getDate() ).padStart( 2, '0' );

		return year + '-' + month + '-' + day;
	}

	function openModal( selector ) {
		closeAllModals( selector );
		$( selector ).removeClass( 'fstu-hidden' ).attr( 'aria-hidden', 'false' );
		$( 'body' ).addClass( 'fstu-modal-open' );
	}

	function closeModal( selector ) {
		$( selector ).addClass( 'fstu-hidden' ).attr( 'aria-hidden', 'true' );

		if ( ! $( '.fstu-modal-overlay:not(.fstu-hidden)' ).length ) {
			$( 'body' ).removeClass( 'fstu-modal-open' );
		}
	}

	function closeAllModals( excludeSelector ) {
		$( '.fstu-modal-overlay:not(.fstu-hidden)' ).each( function () {
			const selector = '#' + $( this ).attr( 'id' );

			if ( excludeSelector && selector === excludeSelector ) {
				return;
			}

			$( this ).addClass( 'fstu-hidden' ).attr( 'aria-hidden', 'true' );
		} );

		if ( ! excludeSelector ) {
			$( 'body' ).removeClass( 'fstu-modal-open' );
		}
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
		let timeoutId;

		return function () {
			const context = this;
			const args = arguments;

			window.clearTimeout( timeoutId );
			timeoutId = window.setTimeout( function () {
				callback.apply( context, args );
			}, delay );
		};
	}
} );

