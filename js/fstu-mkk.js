/**
 * JS модуля «Реєстр членів МКК ФСТУ».
 *
 * Version:     1.1.4
 * Date_update: 2026-04-12
 *
 * @package FSTU
 */

/* global fstuMkkL10n */

jQuery( document ).ready( function ( $ ) {
	'use strict';

	if ( typeof fstuMkkL10n === 'undefined' ) {
		return;
	}

	const $module = $( '#fstu-mkk' );
	if ( ! $module.length ) {
		return;
	}

	const permissions = fstuMkkL10n.permissions || {};
	const $viewBody = $( '#fstu-mkk-view-body' );
	const $viewFooter = $( '#fstu-mkk-view-footer' );
	const $form = $( '#fstu-mkk-form' );
	const listState = {
		page: 1,
		perPage: parseInt( fstuMkkL10n.defaults.perPage, 10 ) || 10,
		search: '',
		regionId: 0,
		commissionTypeId: 0,
		tourismTypeId: 0,
		total: 0,
		totalPages: 1,
		loading: false,
	};
	const protocolState = {
		page: 1,
		perPage: parseInt( fstuMkkL10n.defaults.protocolPerPage, 10 ) || 10,
		search: '',
		total: 0,
		totalPages: 1,
		loading: false,
	};

	let isSubmittingForm = false;
	let deletingIds = [];
	let filtersLoaded = false;
	let filtersRequest = null;
	let filtersCache = {
		regions: [],
		commissionTypes: [],
		tourismTypes: [],
	};
	let userRequest = null;
	let userSearchToken = 0;

	bindGlobalEvents();
	bindListEvents();
	bindProtocolEvents();
	bindModalEvents();
	bindFormEvents();

	ensureFiltersLoaded().always( function () {
		loadList();
	} );

	function bindGlobalEvents() {
		$( document ).on( 'click', function ( event ) {
			if ( ! $( event.target ).closest( '[data-dropdown="mkk"]' ).length ) {
				closeAllDropdowns();
			}

			if ( ! $( event.target ).closest( '.fstu-form-group--autocomplete' ).length ) {
				hideUserResults();
			}
		} );

		$( window ).on( 'scroll resize', debounce( function () {
			closeAllDropdowns();
		}, 30 ) );
	}

	function bindListEvents() {
		$( document ).on( 'click', '#fstu-mkk-refresh-btn', function () {
			loadList();
		} );

		$( document ).on( 'change', '#fstu-mkk-region-filter', function () {
			listState.regionId = parseInt( $( this ).val(), 10 ) || 0;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'change', '#fstu-mkk-commission-type-filter', function () {
			listState.commissionTypeId = parseInt( $( this ).val(), 10 ) || 0;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'change', '#fstu-mkk-tourism-type-filter', function () {
			listState.tourismTypeId = parseInt( $( this ).val(), 10 ) || 0;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'input', '#fstu-mkk-search', debounce( function () {
			listState.search = $( this ).val().trim();
			listState.page = 1;
			loadList();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-mkk-per-page', function () {
			listState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'click', '#fstu-mkk-prev-page', function () {
			if ( listState.page > 1 ) {
				listState.page--;
				loadList();
			}
		} );

		$( document ).on( 'click', '#fstu-mkk-next-page', function () {
			if ( listState.page < listState.totalPages ) {
				listState.page++;
				loadList();
			}
		} );

		$( document ).on( 'click', '.fstu-mkk-page-btn', function () {
			const page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
			if ( page !== listState.page ) {
				listState.page = page;
				loadList();
			}
		} );

		$( document ).on( 'click', '#fstu-mkk-add-btn', function () {
			openFormModal();
		} );

		$( document ).on( 'click', '.fstu-mkk-dropdown__toggle', function ( event ) {
			event.preventDefault();
			event.stopPropagation();

			const $dropdown = $( this ).closest( '[data-dropdown="mkk"]' );
			const isOpen = $dropdown.hasClass( 'is-open' );

			closeAllDropdowns();

			if ( ! isOpen ) {
				$dropdown.addClass( 'is-open' );
				$( this ).attr( 'aria-expanded', 'true' );
				positionDropdown( $dropdown );
			}
		} );

		$( document ).on( 'click', '.fstu-mkk-view-btn', function () {
			closeAllDropdowns();
			openViewModal( parseInt( $( this ).data( 'mkk-id' ), 10 ) || 0 );
		} );

		$( document ).on( 'click', '.fstu-mkk-view-link', function ( event ) {
			event.preventDefault();
			closeAllDropdowns();
			openViewModal( parseInt( $( this ).data( 'mkk-id' ), 10 ) || 0 );
		} );

		$( document ).on( 'click', '.fstu-mkk-edit-btn', function () {
			closeAllDropdowns();
			openFormModal( parseInt( $( this ).data( 'mkk-id' ), 10 ) || 0 );
		} );

		$( document ).on( 'click', '.fstu-mkk-delete-btn', function () {
			closeAllDropdowns();
			deleteItem( parseInt( $( this ).data( 'mkk-id' ), 10 ) || 0 );
		} );
	}

	function bindProtocolEvents() {
		$( document ).on( 'click', '#fstu-mkk-protocol-btn', function () {
			$( '#fstu-mkk-main' ).addClass( 'fstu-hidden' );
			$( '#fstu-mkk-protocol' ).removeClass( 'fstu-hidden' );
			$( '#fstu-mkk-add-btn' ).addClass( 'fstu-hidden' );
			$( '#fstu-mkk-protocol-btn' ).addClass( 'fstu-hidden' );
			$( '#fstu-mkk-protocol-back-btn' ).removeClass( 'fstu-hidden' );
			$( '#fstu-mkk-filter-wrap' ).addClass( 'fstu-hidden' );
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-mkk-protocol-back-btn', function () {
			$( '#fstu-mkk-main' ).removeClass( 'fstu-hidden' );
			$( '#fstu-mkk-protocol' ).addClass( 'fstu-hidden' );
			$( '#fstu-mkk-add-btn' ).removeClass( 'fstu-hidden' );
			$( '#fstu-mkk-protocol-btn' ).removeClass( 'fstu-hidden' );
			$( '#fstu-mkk-protocol-back-btn' ).addClass( 'fstu-hidden' );
			$( '#fstu-mkk-filter-wrap' ).removeClass( 'fstu-hidden' );
		} );

		$( document ).on( 'input', '#fstu-mkk-protocol-search', debounce( function () {
			protocolState.search = $( this ).val().trim();
			protocolState.page = 1;
			loadProtocol();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-mkk-protocol-per-page', function () {
			protocolState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			protocolState.page = 1;
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-mkk-protocol-prev-page', function () {
			if ( protocolState.page > 1 ) {
				protocolState.page--;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '#fstu-mkk-protocol-next-page', function () {
			if ( protocolState.page < protocolState.totalPages ) {
				protocolState.page++;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '.fstu-mkk-protocol-page-btn', function () {
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
		$( document ).on( 'submit', '#fstu-mkk-form', function ( event ) {
			event.preventDefault();
			submitForm();
		} );

		$( document ).on( 'input', '#fstu-mkk-user-input', debounce( function () {
			const search = $( this ).val().trim();
			$( '#fstu-mkk-user-id' ).val( '0' );

			if ( search.length < 2 ) {
				showUserHint();
				return;
			}

			loadUsers( search );
		}, 250 ) );

		$( document ).on( 'click', '.fstu-autocomplete-results__item', function () {
			$( '#fstu-mkk-user-id' ).val( $( this ).data( 'user-id' ) );
			$( '#fstu-mkk-user-input' ).val( $( this ).data( 'fio' ) );
			hideUserResults();
		} );
	}

	function ensureFiltersLoaded() {
		if ( filtersLoaded ) {
			return $.Deferred().resolve().promise();
		}

		if ( filtersRequest ) {
			return filtersRequest;
		}

		filtersRequest = $.ajax( {
			url: fstuMkkL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuMkkL10n.actions.getFilters,
				nonce: fstuMkkL10n.nonce,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				filtersCache = {
					regions: response.data.regions || [],
					commissionTypes: response.data.commissionTypes || [],
					tourismTypes: response.data.tourismTypes || [],
				};
				filtersLoaded = true;
				renderFilterOptions();
			} else {
				window.console.warn( getAjaxMessage( response, fstuMkkL10n.messages.filtersError ) );
			}
		} ).always( function () {
			filtersRequest = null;
		} );

		return filtersRequest;
	}

	function loadList() {
		if ( listState.loading ) {
			return;
		}

		listState.loading = true;
		closeAllDropdowns();
		setTableLoading( '#fstu-mkk-tbody', 7, fstuMkkL10n.messages.loading );

		$.ajax( {
			url: fstuMkkL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuMkkL10n.actions.getList,
				nonce: fstuMkkL10n.nonce,
				search: listState.search,
				page: listState.page,
				per_page: listState.perPage,
				region_id: listState.regionId,
				commission_type_id: listState.commissionTypeId,
				tourism_type_id: listState.tourismTypeId,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				$( '#fstu-mkk-tbody' ).html( response.data.html );
				listState.total = parseInt( response.data.total, 10 ) || 0;
				listState.page = parseInt( response.data.page, 10 ) || 1;
				listState.perPage = parseInt( response.data.per_page, 10 ) || 10;
				listState.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				updateListPagination();
			} else {
				showTableError( '#fstu-mkk-tbody', 7, getAjaxMessage( response, fstuMkkL10n.messages.error ) );
			}
		} ).fail( function () {
			showTableError( '#fstu-mkk-tbody', 7, fstuMkkL10n.messages.error );
		} ).always( function () {
			listState.loading = false;
		} );
	}

	function loadProtocol() {
		if ( protocolState.loading ) {
			return;
		}

		protocolState.loading = true;
		setTableLoading( '#fstu-mkk-protocol-tbody', 6, fstuMkkL10n.messages.loading );

		$.ajax( {
			url: fstuMkkL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuMkkL10n.actions.getProtocol,
				nonce: fstuMkkL10n.nonce,
				search: protocolState.search,
				page: protocolState.page,
				per_page: protocolState.perPage,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				$( '#fstu-mkk-protocol-tbody' ).html( response.data.html );
				protocolState.total = parseInt( response.data.total, 10 ) || 0;
				protocolState.page = parseInt( response.data.page, 10 ) || 1;
				protocolState.perPage = parseInt( response.data.per_page, 10 ) || 10;
				protocolState.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				updateProtocolPagination();
			} else {
				showTableError( '#fstu-mkk-protocol-tbody', 6, getAjaxMessage( response, fstuMkkL10n.messages.protocolError ) );
			}
		} ).fail( function () {
			showTableError( '#fstu-mkk-protocol-tbody', 6, fstuMkkL10n.messages.protocolError );
		} ).always( function () {
			protocolState.loading = false;
		} );
	}

	function loadUsers( search ) {
		userSearchToken++;
		const currentToken = userSearchToken;

		if ( userRequest && typeof userRequest.abort === 'function' ) {
			userRequest.abort();
		}

		userRequest = $.ajax( {
			url: fstuMkkL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuMkkL10n.actions.searchUsers,
				nonce: fstuMkkL10n.nonce,
				search: search,
			},
		} ).done( function ( response ) {
			if ( currentToken !== userSearchToken ) {
				return;
			}

			if ( response.success ) {
				renderUserResults( response.data.items || [] );
			} else {
				showUserError();
			}
		} ).fail( function () {
			if ( currentToken !== userSearchToken ) {
				return;
			}

			showUserError();
		} ).always( function () {
			userRequest = null;
		} );
	}

	function openViewModal( mkkId ) {
		if ( mkkId <= 0 ) {
			return;
		}

		$viewBody.html( '<p class="fstu-loader-inline">' + escapeHtml( fstuMkkL10n.messages.loading ) + '</p>' );
		$viewFooter.empty();
		openModal( 'fstu-mkk-view-modal' );

		$.ajax( {
			url: fstuMkkL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuMkkL10n.actions.getSingle,
				nonce: fstuMkkL10n.nonce,
				mkk_id: mkkId,
			},
		} ).done( function ( response ) {
			if ( ! response.success ) {
				$viewBody.html( '<p class="fstu-alert">' + escapeHtml( getAjaxMessage( response, fstuMkkL10n.messages.error ) ) + '</p>' );
				return;
			}

			const item = response.data.item || {};
			let html = '<div class="fstu-mkk-view">';
			html += '<section class="fstu-mkk-view__section">';
			html += '<h4 class="fstu-mkk-view__section-title">Основні дані</h4>';
			html += '<div class="fstu-mkk-view__grid">';
			html += buildViewField( 'ПІБ', item.DisplayFIO || '' );
			html += buildViewField( 'Посада', item.MemberRegional_Name || '' );
			html += buildViewField( 'Регіон', item.Region_Name || '' );
			html += buildViewField( 'Тип комісії', item.CommissionType_Name || '' );
			html += buildViewField( 'Вид туризму', item.TourismType_Name || '' );
			html += buildViewField( 'Профіль', item.ProfileUrl ? '<a class="fstu-mkk-link" target="_blank" rel="noopener noreferrer" href="' + escapeHtml( item.ProfileUrl ) + '">/Personal/?ViewID=' + escapeHtml( String( item.User_ID || 0 ) ) + '</a>' : '—', true );
			html += '</div>';
			html += '</section>';
			html += '<section class="fstu-mkk-view__section">';
			html += '<h4 class="fstu-mkk-view__section-title">Службова інформація</h4>';
			html += '<div class="fstu-mkk-view__grid">';
			html += buildViewField( 'Дата початку', item.mkk_DateBegin || '' );
			html += buildViewField( 'Дата створення', item.mkk_DateCreate || '' );
			html += buildViewField( 'Створив', item.UserCreate_Display || '' );
			html += '</div>';
			html += '</section>';
			html += '</div>';
			$viewBody.html( html );

			let footerHtml = '';
			if ( item.ProfileUrl ) {
				footerHtml += '<a class="fstu-btn fstu-btn--secondary" target="_blank" rel="noopener noreferrer" href="' + escapeHtml( item.ProfileUrl ) + '">Відкрити профіль</a>';
			}
			if ( permissions.canManage ) {
				footerHtml += '<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-mkk-view-edit" data-mkk-id="' + escapeHtml( String( item.mkk_ID || item.mkk_id || 0 ) ) + '">Редагувати</button>';
			}
			footerHtml += '<button type="button" class="fstu-btn fstu-btn--secondary fstu-btn--cancel" data-close-modal="fstu-mkk-view-modal">Закрити</button>';
			$viewFooter.html( footerHtml );
		} ).fail( function () {
			$viewBody.html( '<p class="fstu-alert">' + escapeHtml( fstuMkkL10n.messages.error ) + '</p>' );
		} );
	}

	$( document ).on( 'click', '#fstu-mkk-view-edit', function () {
		const mkkId = parseInt( $( this ).data( 'mkk-id' ), 10 ) || 0;
		closeModal( 'fstu-mkk-view-modal' );
		openFormModal( mkkId );
	} );

	function openFormModal( mkkId ) {
		resetForm();

		ensureFiltersLoaded().always( function () {
			if ( mkkId > 0 ) {
				$( '#fstu-mkk-form-mode' ).val( 'edit' );
				$( '#fstu-mkk-form-title' ).text( fstuMkkL10n.messages.formEditTitle );
				$( '#fstu-mkk-user-input' ).prop( 'readonly', true );
				$( '#fstu-mkk-user-hint' ).text( fstuMkkL10n.messages.userLockedEdit || fstuMkkL10n.messages.userHint );
				$( '#fstu-mkk-id' ).val( mkkId );
				openModal( 'fstu-mkk-form-modal' );
				loadFormData( mkkId );
			} else {
				$( '#fstu-mkk-form-mode' ).val( 'create' );
				$( '#fstu-mkk-form-title' ).text( fstuMkkL10n.messages.formAddTitle );
				$( '#fstu-mkk-user-input' ).prop( 'readonly', false );
				$( '#fstu-mkk-user-hint' ).text( fstuMkkL10n.messages.userHint );
				openModal( 'fstu-mkk-form-modal' );
			}
		} );
	}

	function loadFormData( mkkId ) {
		$.ajax( {
			url: fstuMkkL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuMkkL10n.actions.getSingle,
				nonce: fstuMkkL10n.nonce,
				mkk_id: mkkId,
			},
		} ).done( function ( response ) {
			if ( ! response.success ) {
				showFormMessage( getAjaxMessage( response, fstuMkkL10n.messages.error ), true );
				return;
			}

			const item = response.data.item || {};
			$( '#fstu-mkk-user-id' ).val( String( item.User_ID || 0 ) );
			$( '#fstu-mkk-user-input' ).val( item.FIO || item.DisplayFIO || '' );
			$( '#fstu-mkk-region-id' ).val( String( item.Region_ID || 0 ) );
			$( '#fstu-mkk-commission-type-id' ).val( String( item.CommissionType_ID || 0 ) );
			$( '#fstu-mkk-tourism-type-id' ).val( String( item.TourismType_ID || 0 ) );
		} ).fail( function () {
			showFormMessage( fstuMkkL10n.messages.error, true );
		} );
	}

	function submitForm() {
		if ( isSubmittingForm ) {
			return;
		}

		const mkkId = parseInt( $( '#fstu-mkk-id' ).val(), 10 ) || 0;
		const userId = parseInt( $( '#fstu-mkk-user-id' ).val(), 10 ) || 0;
		if ( userId <= 0 ) {
			showFormMessage( fstuMkkL10n.messages.userRequired, true );
			return;
		}

		const action = mkkId > 0 ? fstuMkkL10n.actions.update : fstuMkkL10n.actions.create;
		const $submit = $( '#fstu-mkk-form-submit' );

		isSubmittingForm = true;
		$submit.prop( 'disabled', true ).addClass( 'is-loading' );

		$.ajax( {
			url: fstuMkkL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: action,
				nonce: fstuMkkL10n.nonce,
				mkk_id: mkkId,
				user_id: userId,
				region_id: parseInt( $( '#fstu-mkk-region-id' ).val(), 10 ) || 0,
				commission_type_id: parseInt( $( '#fstu-mkk-commission-type-id' ).val(), 10 ) || 0,
				tourism_type_id: parseInt( $( '#fstu-mkk-tourism-type-id' ).val(), 10 ) || 0,
				fstu_website: $( '#fstu-mkk-website' ).val(),
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				showFormMessage( getAjaxMessage( response, fstuMkkL10n.messages.saveSuccess ), false );
				window.setTimeout( function () {
					closeModal( 'fstu-mkk-form-modal' );
					loadList();
				}, 500 );
			} else {
				showFormMessage( getAjaxMessage( response, fstuMkkL10n.messages.saveError ), true );
			}
		} ).fail( function () {
			showFormMessage( fstuMkkL10n.messages.saveError, true );
		} ).always( function () {
			isSubmittingForm = false;
			$submit.prop( 'disabled', false ).removeClass( 'is-loading' );
		} );
	}

	function deleteItem( mkkId ) {
		if ( mkkId <= 0 || deletingIds.indexOf( mkkId ) !== -1 ) {
			return;
		}

		if ( ! window.confirm( fstuMkkL10n.messages.confirmDelete ) ) {
			return;
		}

		deletingIds.push( mkkId );

		$.ajax( {
			url: fstuMkkL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuMkkL10n.actions.delete,
				nonce: fstuMkkL10n.nonce,
				mkk_id: mkkId,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				loadList();
			} else {
				window.alert( getAjaxMessage( response, fstuMkkL10n.messages.deleteError ) );
			}
		} ).fail( function () {
			window.alert( fstuMkkL10n.messages.deleteError );
		} ).always( function () {
			deletingIds = deletingIds.filter( function ( id ) {
				return id !== mkkId;
			} );
		} );
	}

	function renderFilterOptions() {
		renderSelect( '#fstu-mkk-region-filter', 'Усі області', filtersCache.regions, 'Region_ID', 'Region_Name', listState.regionId );
		renderSelect( '#fstu-mkk-commission-type-filter', 'Усі типи', filtersCache.commissionTypes, 'CommissionType_ID', 'CommissionType_Name', listState.commissionTypeId );
		renderSelect( '#fstu-mkk-tourism-type-filter', 'Усі види', filtersCache.tourismTypes, 'TourismType_ID', 'TourismType_Name', listState.tourismTypeId );
		renderSelect( '#fstu-mkk-region-id', 'Оберіть область', filtersCache.regions, 'Region_ID', 'Region_Name', parseInt( $( '#fstu-mkk-region-id' ).val(), 10 ) || 0 );
		renderSelect( '#fstu-mkk-commission-type-id', 'Оберіть тип', filtersCache.commissionTypes, 'CommissionType_ID', 'CommissionType_Name', parseInt( $( '#fstu-mkk-commission-type-id' ).val(), 10 ) || 0 );
		renderSelect( '#fstu-mkk-tourism-type-id', 'Оберіть вид туризму', filtersCache.tourismTypes, 'TourismType_ID', 'TourismType_Name', parseInt( $( '#fstu-mkk-tourism-type-id' ).val(), 10 ) || 0 );
	}

	function renderSelect( selector, emptyLabel, items, idKey, nameKey, selected ) {
		const options = [ '<option value="0">' + escapeHtml( emptyLabel ) + '</option>' ];
		items.forEach( function ( item ) {
			const id = parseInt( item[ idKey ], 10 ) || 0;
			if ( id <= 0 ) {
				return;
			}
			options.push( '<option value="' + escapeHtml( String( id ) ) + '">' + escapeHtml( item[ nameKey ] || '' ) + '</option>' );
		} );
		$( selector ).html( options.join( '' ) ).val( String( selected || 0 ) );
	}

	function renderUserResults( items ) {
		const $results = $( '#fstu-mkk-user-results' );
		if ( ! items.length ) {
			$results.html( '<div class="fstu-autocomplete-results__empty">' + escapeHtml( fstuMkkL10n.messages.userEmpty ) + '</div>' ).removeClass( 'fstu-hidden' );
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

	function showUserHint() {
		$( '#fstu-mkk-user-results' ).html( '<div class="fstu-autocomplete-results__empty">' + escapeHtml( fstuMkkL10n.messages.userHint ) + '</div>' ).removeClass( 'fstu-hidden' );
	}

	function showUserError() {
		$( '#fstu-mkk-user-results' ).html( '<div class="fstu-autocomplete-results__empty">' + escapeHtml( fstuMkkL10n.messages.usersError ) + '</div>' ).removeClass( 'fstu-hidden' );
	}

	function hideUserResults() {
		userSearchToken++;
		$( '#fstu-mkk-user-results' ).addClass( 'fstu-hidden' ).empty();
	}

	function updateListPagination() {
		$( '#fstu-mkk-per-page' ).val( String( listState.perPage ) );
		$( '#fstu-mkk-pagination-pages' ).html( buildPaginationButtons( listState.page, listState.totalPages, 'fstu-mkk-page-btn' ) );
		$( '#fstu-mkk-pagination-info' ).text( buildPaginationInfo( listState.total, listState.page, listState.totalPages ) );
		setPaginationArrowState( '#fstu-mkk-prev-page', '#fstu-mkk-next-page', listState.page, listState.totalPages );
	}

	function updateProtocolPagination() {
		$( '#fstu-mkk-protocol-per-page' ).val( String( protocolState.perPage ) );
		$( '#fstu-mkk-protocol-pagination-pages' ).html( buildPaginationButtons( protocolState.page, protocolState.totalPages, 'fstu-mkk-protocol-page-btn' ) );
		$( '#fstu-mkk-protocol-pagination-info' ).text( buildPaginationInfo( protocolState.total, protocolState.page, protocolState.totalPages ) );
		setPaginationArrowState( '#fstu-mkk-protocol-prev-page', '#fstu-mkk-protocol-next-page', protocolState.page, protocolState.totalPages );
	}

	function openModal( modalId ) {
		$( '#' + modalId ).removeClass( 'fstu-hidden' ).attr( 'aria-hidden', 'false' );
		$( 'body' ).addClass( 'fstu-modal-open' );
	}

	function closeModal( modalId ) {
		const $modal = $( '#' + modalId );
		$modal.addClass( 'fstu-hidden' ).attr( 'aria-hidden', 'true' );
		if ( 'fstu-mkk-form-modal' === modalId ) {
			resetForm();
		}
		if ( ! $( '.fstu-modal-overlay:not(.fstu-hidden)' ).length ) {
			$( 'body' ).removeClass( 'fstu-modal-open' );
		}
	}

	function resetForm() {
		if ( $form.length ) {
			$form[0].reset();
		}
		$( '#fstu-mkk-id' ).val( '0' );
		$( '#fstu-mkk-form-mode' ).val( 'create' );
		$( '#fstu-mkk-user-id' ).val( '0' );
		$( '#fstu-mkk-user-input' ).prop( 'readonly', false );
		$( '#fstu-mkk-user-hint' ).text( fstuMkkL10n.messages.userHint );
		showFormMessage( '', false );
		hideUserResults();
		if ( filtersLoaded ) {
			renderFilterOptions();
		}
	}

	function showFormMessage( message, isError ) {
		const $message = $( '#fstu-mkk-form-message' );
		if ( ! message ) {
			$message.addClass( 'fstu-hidden' ).removeClass( 'is-error is-success' ).empty();
			return;
		}
		$message.removeClass( 'fstu-hidden is-error is-success' ).addClass( isError ? 'is-error' : 'is-success' ).text( message );
	}

	function closeAllDropdowns() {
		$( '[data-dropdown="mkk"].is-open' ).each( function () {
			$( this ).removeClass( 'is-open is-dropup' );
			$( this ).find( '.fstu-mkk-dropdown__toggle' ).attr( 'aria-expanded', 'false' );
			$( this ).find( '[data-dropdown-menu]' ).removeAttr( 'style' );
		} );
	}

	function positionDropdown( $dropdown ) {
		const $toggle = $dropdown.find( '.fstu-mkk-dropdown__toggle' );
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

	function getAjaxMessage( response, fallback ) {
		if ( response && response.data && response.data.message ) {
			return String( response.data.message );
		}

		return String( fallback || '' );
	}

	function buildViewField( label, value, isHtml ) {
		let content = value;
		if ( ! isHtml ) {
			content = escapeHtml( value || '—' );
		} else if ( ! content ) {
			content = '—';
		}

		return '<div class="fstu-mkk-view__field">'
			+ '<div class="fstu-mkk-view__label">' + escapeHtml( label ) + '</div>'
			+ '<div class="fstu-mkk-view__value">' + content + '</div>'
			+ '</div>';
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

