/**
 * JS модуля «Реєстр стернових ФСТУ».
	 *
	 * Version:     1.11.0
	 * Date_update: 2026-04-09
 *
 * @package FSTU
 */

/* global fstuSteeringL10n */

jQuery( document ).ready( function ( $ ) {
	'use strict';

	if ( typeof fstuSteeringL10n === 'undefined' ) {
		return;
	}

	const $module = $( '#fstu-steering' );
	if ( ! $module.length ) {
		return;
	}

	const l10n = fstuSteeringL10n;
	const permissions = l10n.permissions || {};
	const currentUser = l10n.currentUser || {};
	const listState = {
		page: 1,
		perPage: parseInt( l10n.defaults.perPage, 10 ) || 10,
		search: '',
		duesFilter: 'all',
		statusId: 0,
		typeFilter: 'all',
		total: 0,
		totalPages: 1,
		loading: false,
	};
	const protocolState = {
		page: 1,
		perPage: parseInt( l10n.defaults.protocolPerPage, 10 ) || 10,
		search: '',
		total: 0,
		totalPages: 1,
		loading: false,
	};
	const dictionaries = {
		loaded: false,
		cities: [],
		availableUsers: [],
	};
	const currentView = {
		steeringId: 0,
		confirming: false,
		statusLoading: false,
		deleting: false,
	};
	const formState = {
		mode: 'create',
		steeringId: 0,
	};
	const bootstrapState = {
		applied: false,
	};

	bindEvents();
	loadList();
	applyInitialDeepLink();

	function bindEvents() {
		$( document ).on( 'click', function ( event ) {
			if ( ! $( event.target ).closest( '.fstu-steering-dropdown' ).length ) {
				closeAllDropdowns();
			}
		} );

		$( document ).on( 'change', '#fstu-steering-type-filter', function () {
			listState.typeFilter = $( this ).val() || 'all';
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'click', '.fstu-steering-dropdown__toggle', function ( event ) {
			event.preventDefault();
			event.stopPropagation();

			const $dropdown = $( this ).closest( '.fstu-steering-dropdown' );
			const isOpen = $dropdown.hasClass( 'is-open' );
			closeAllDropdowns();

			if ( ! isOpen ) {
				$dropdown.addClass( 'is-open' );
				$( this ).attr( 'aria-expanded', 'true' );
			}
		} );

		$( document ).on( 'click', '#fstu-steering-refresh-btn', function () {
			loadList();
		} );

		$( document ).on( 'click', '#fstu-steering-add-btn', function () {
			if ( l10n.submitBlocked ) {
				showTopMessage( l10n.messages.submitBlocked, 'error' );
				return;
			}

			openFormModal();
		} );

		$( document ).on( 'submit', '#fstu-steering-form', function ( event ) {
			event.preventDefault();
			submitForm();
		} );

		$( document ).on( 'input', '#fstu-steering-search', debounce( function () {
			listState.search = $( this ).val().trim();
			listState.page = 1;
			loadList();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-steering-dues-filter', function () {
			listState.duesFilter = $( this ).val() || 'all';
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'change', '#fstu-steering-status-filter', function () {
			listState.statusId = parseInt( $( this ).val(), 10 ) || 0;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'change', '#fstu-steering-per-page', function () {
			listState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'click', '#fstu-steering-prev-page', function () {
			if ( listState.page > 1 ) {
				listState.page -= 1;
				loadList();
			}
		} );

		$( document ).on( 'click', '#fstu-steering-next-page', function () {
			if ( listState.page < listState.totalPages ) {
				listState.page += 1;
				loadList();
			}
		} );

		$( document ).on( 'click', '.fstu-steering-page-btn', function () {
			listState.page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
			loadList();
		} );

		$( document ).on( 'click', '.fstu-steering-view-btn', function () {
			closeAllDropdowns();
			openViewModal( parseInt( $( this ).data( 'steering-id' ), 10 ) || 0 );
		} );

		$( document ).on( 'click', '.fstu-steering-edit-btn', function () {
			closeAllDropdowns();
			openEditForm( parseInt( $( this ).data( 'steering-id' ), 10 ) || 0 );
		} );

		$( document ).on( 'click', '.fstu-steering-delete-btn', function () {
			closeAllDropdowns();
			confirmDelete( parseInt( $( this ).data( 'steering-id' ), 10 ) || 0 );
		} );

		$( document ).on( 'click', '#fstu-steering-edit-btn', function () {
			openEditForm( currentView.steeringId );
		} );

		$( document ).on( 'click', '#fstu-steering-delete-btn', function () {
			confirmDelete( currentView.steeringId );
		} );

		$( document ).on( 'click', '#fstu-steering-confirm-btn', function () {
			confirmVerification();
		} );

		$( document ).on( 'click', '#fstu-steering-register-btn', function () {
			runStatusAction( 'register' );
		} );

		$( document ).on( 'click', '#fstu-steering-send-post-btn', function () {
			runStatusAction( 'sent_post' );
		} );

		$( document ).on( 'click', '#fstu-steering-received-btn', function () {
			runStatusAction( 'received' );
		} );

		$( document ).on( 'click', '#fstu-steering-protocol-btn', function () {
			$( '#fstu-steering-main' ).addClass( 'fstu-hidden' );
			$( '#fstu-steering-protocol' ).removeClass( 'fstu-hidden' );
			$( '#fstu-steering-protocol-btn' ).addClass( 'fstu-hidden' );
			$( '#fstu-steering-protocol-back-btn' ).removeClass( 'fstu-hidden' );
			protocolState.page = 1;
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-steering-protocol-back-btn', function () {
			$( '#fstu-steering-main' ).removeClass( 'fstu-hidden' );
			$( '#fstu-steering-protocol' ).addClass( 'fstu-hidden' );
			$( '#fstu-steering-protocol-btn' ).removeClass( 'fstu-hidden' );
			$( '#fstu-steering-protocol-back-btn' ).addClass( 'fstu-hidden' );
		} );

		$( document ).on( 'input keyup change search', '#fstu-steering-protocol-search', debounce( function () {
			const nextSearch = $( this ).val().trim();

			if ( nextSearch === protocolState.search ) {
				return;
			}

			protocolState.search = nextSearch;
			protocolState.page = 1;
			loadProtocol();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-steering-protocol-per-page', function () {
			protocolState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			protocolState.page = 1;
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-steering-protocol-prev-page', function () {
			if ( protocolState.page > 1 ) {
				protocolState.page -= 1;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '#fstu-steering-protocol-next-page', function () {
			if ( protocolState.page < protocolState.totalPages ) {
				protocolState.page += 1;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '.fstu-steering-protocol-page-btn', function () {
			const page = parseInt( $( this ).data( 'page' ), 10 ) || 1;

			if ( page !== protocolState.page ) {
				protocolState.page = page;
				loadProtocol();
			}
		} );

		$( document ).on( 'change', '#fstu-steering-form-user-select', function () {
			const userId = parseInt( $( this ).val(), 10 ) || 0;
			$( '#fstu-steering-form-user-id' ).val( userId );
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

	function loadList() {
		if ( listState.loading ) {
			return;
		}

		listState.loading = true;
		setTableState( '#fstu-steering-tbody', l10n.table.colspan || 5, l10n.messages.loading );

		ajaxRequest( l10n.actions.getList, {
			search: listState.search,
			page: listState.page,
			per_page: listState.perPage,
			status_id: permissions.canManage || permissions.canManageStatus ? listState.statusId : 0,
			dues_filter: permissions.canSeeFinance ? listState.duesFilter : 'all',
			type_filter: listState.typeFilter,
		} ).done( function ( response ) {
			$( '#fstu-steering-tbody' ).html( response.html || '' );
			listState.total = parseInt( response.total, 10 ) || 0;
			listState.totalPages = parseInt( response.total_pages, 10 ) || 1;
			listState.page = parseInt( response.page, 10 ) || 1;
			listState.perPage = parseInt( response.per_page, 10 ) || listState.perPage;
			updateListPagination();
			updateFooterSummary( response.footer_html || '' );
		} ).fail( function ( response ) {
			setTableState( '#fstu-steering-tbody', l10n.table.colspan || 5, getErrorMessage( response, l10n.messages.error ) );
			updateFooterSummary( '' );
		} ).always( function () {
			listState.loading = false;
		} );
	}

	function openViewModal( steeringId ) {
		if ( ! steeringId ) {
			return;
		}

		currentView.steeringId = steeringId;
		currentView.confirming = false;
		currentView.statusLoading = false;
		currentView.deleting = false;
		resetViewModal();
		updateViewUrlState( steeringId );
		showModal( 'fstu-steering-view-modal' );
		showMessage( '#fstu-steering-view-message', l10n.messages.loading, 'success' );

		ajaxRequest( l10n.actions.getSingle, { steering_id: steeringId } ).done( function ( response ) {
			fillViewModal( response.item || {} );
			hideMessage( '#fstu-steering-view-message' );
		} ).fail( function ( response ) {
			showMessage( '#fstu-steering-view-message', getErrorMessage( response, l10n.messages.viewError ), 'error' );
		} );
	}

	function loadProtocol() {
		if ( protocolState.loading ) {
			return;
		}

		protocolState.loading = true;
		setTableState( '#fstu-steering-protocol-tbody', l10n.table.protocolColspan || 6, l10n.messages.loading );

		ajaxRequest( l10n.actions.getProtocol, {
			search: protocolState.search,
			page: protocolState.page,
			per_page: protocolState.perPage,
		} ).done( function ( response ) {
			$( '#fstu-steering-protocol-tbody' ).html( response.html || '' );
			protocolState.total = parseInt( response.total, 10 ) || 0;
			protocolState.totalPages = parseInt( response.total_pages, 10 ) || 1;
			protocolState.page = parseInt( response.page, 10 ) || 1;
			protocolState.perPage = parseInt( response.per_page, 10 ) || protocolState.perPage;
			updateProtocolPagination();
		} ).fail( function ( response ) {
			protocolState.total = 0;
			protocolState.totalPages = 1;
			protocolState.page = 1;
			setTableState( '#fstu-steering-protocol-tbody', l10n.table.protocolColspan || 6, getErrorMessage( response, l10n.messages.protocolError || l10n.messages.error ) );
			updateProtocolPagination();
		} ).always( function () {
			protocolState.loading = false;
		} );
	}

	function fillViewModal( item ) {
		currentView.steeringId = parseInt( item.Steering_ID, 10 ) || currentView.steeringId;
		setText( '#fstu-steering-view-fio', item.FIO || '—' );
		setText( '#fstu-steering-view-fio-eng', buildEnglishFullName( item ) || '—' );
		setText( '#fstu-steering-view-type-app', resolveTypeLabel( item.Steering_TypeApp ) || '—' );
		setText( '#fstu-steering-view-number', item.Steering_RegNumber || '—' );
		setText( '#fstu-steering-view-status', item.AppStatus_Name || '—' );
		setText( '#fstu-steering-view-date-pay', formatDateValue( item.Steering_DatePay ) || '—' );
		setText( '#fstu-steering-view-birth-date', formatDateValue( item.Steering_BirthDate ) || '—' );
		setText( '#fstu-steering-view-city-np', item.Steering_CityNP || '—' );
		setText( '#fstu-steering-view-number-np', item.Steering_NumberNP || '—' );
		setText( '#fstu-steering-view-date-create', formatDateTimeValue( item.Steering_DateCreate ) || '—' );
		setText( '#fstu-steering-view-date-delivery', formatDateValue( item.Steering_DateDelivery ) || '—' );
		setText( '#fstu-steering-view-verifications', item.CntVerification || 0 );
		setText( '#fstu-steering-view-verification-progress', buildVerificationProgress( item ) );
		setText( '#fstu-steering-view-verification-state', buildVerificationState( item ) );
		setLink( '#fstu-steering-view-url', item.Steering_Url || '' );
		renderVerifiersList( item.Verifiers || [] );
		toggleConfirmButton( !! item.CanConfirmVerification );
		toggleStatusActionButtons( item );
		toggleAdminRows();
		$( '#fstu-steering-edit-btn' ).toggleClass( 'fstu-hidden', ! permissions.canManage );
		$( '#fstu-steering-delete-btn' ).toggleClass( 'fstu-hidden', ! permissions.canDelete );

		const photoUrl = item.PhotoUrl || '';
		if ( photoUrl ) {
			$( '#fstu-steering-view-photo' ).attr( 'src', photoUrl ).removeClass( 'fstu-hidden' );
		} else {
			$( '#fstu-steering-view-photo' ).attr( 'src', '' ).addClass( 'fstu-hidden' );
		}
	}

	function confirmVerification() {
		if ( currentView.confirming || ! currentView.steeringId ) {
			return;
		}

		currentView.confirming = true;
		$( '#fstu-steering-confirm-btn' ).prop( 'disabled', true );
		showMessage( '#fstu-steering-view-message', l10n.messages.verifyLoading || l10n.messages.loading, 'success' );

		ajaxRequest( l10n.actions.confirmVerification, { steering_id: currentView.steeringId } ).done( function ( response ) {
			fillViewModal( response.item || {} );
			showMessage( '#fstu-steering-view-message', response.message || l10n.messages.verifySuccess, 'success' );
			showTopMessage( response.message || l10n.messages.verifySuccess, 'success' );
			loadList();
		} ).fail( function ( response ) {
			showMessage( '#fstu-steering-view-message', getErrorMessage( response, l10n.messages.verifyError || l10n.messages.error ), 'error' );
		} ).always( function () {
			currentView.confirming = false;
			if ( $( '#fstu-steering-confirm-btn' ).is( ':visible' ) ) {
				$( '#fstu-steering-confirm-btn' ).prop( 'disabled', false );
			}
		} );
	}

	function runStatusAction( action ) {
		if ( currentView.statusLoading || ! currentView.steeringId ) {
			return;
		}

		const requestMap = {
			register: {
				action: l10n.actions.register,
				loading: l10n.messages.statusLoading || l10n.messages.loading,
				success: l10n.messages.registerSuccess || l10n.messages.saveSuccess,
				error: l10n.messages.registerError || l10n.messages.error,
			},
			sent_post: {
				action: l10n.actions.markSentPost,
				loading: l10n.messages.statusLoading || l10n.messages.loading,
				success: l10n.messages.sendPostSuccess || l10n.messages.saveSuccess,
				error: l10n.messages.sendPostError || l10n.messages.error,
			},
			received: {
				action: l10n.actions.markReceived,
				loading: l10n.messages.statusLoading || l10n.messages.loading,
				success: l10n.messages.receivedSuccess || l10n.messages.saveSuccess,
				error: l10n.messages.receivedError || l10n.messages.error,
			},
		};

		if ( ! requestMap[ action ] ) {
			return;
		}

		currentView.statusLoading = true;
		setStatusButtonsDisabled( true );
		showMessage( '#fstu-steering-view-message', requestMap[ action ].loading, 'success' );

		ajaxRequest( requestMap[ action ].action, { steering_id: currentView.steeringId } ).done( function ( response ) {
			fillViewModal( response.item || {} );
			showMessage( '#fstu-steering-view-message', response.message || requestMap[ action ].success, 'success' );
			showTopMessage( response.message || requestMap[ action ].success, 'success' );
			loadList();
		} ).fail( function ( response ) {
			showMessage( '#fstu-steering-view-message', getErrorMessage( response, requestMap[ action ].error ), 'error' );
		} ).always( function () {
			currentView.statusLoading = false;
			setStatusButtonsDisabled( false );
		} );
	}

	function confirmDelete( steeringId ) {
		if ( currentView.deleting || ! steeringId || ! permissions.canDelete ) {
			return;
		}

		if ( ! window.confirm( l10n.messages.confirmDelete || 'Ви дійсно бажаєте видалити запис стернового?' ) ) {
			return;
		}

		currentView.deleting = true;
		$( '#fstu-steering-delete-btn' ).prop( 'disabled', true );
		showMessage( '#fstu-steering-view-message', l10n.messages.loading, 'success' );

		ajaxRequest( l10n.actions.delete, { steering_id: steeringId } ).done( function ( response ) {
			showTopMessage( response.message || l10n.messages.deleteSuccess, 'success' );
			invalidateDictionariesCache();
			loadList();
			if ( currentView.steeringId === steeringId ) {
				closeModal( 'fstu-steering-view-modal' );
			}
		} ).fail( function ( response ) {
			const errorMessage = getErrorMessage( response, l10n.messages.deleteError || l10n.messages.error );
			showMessage( '#fstu-steering-view-message', errorMessage, 'error' );
			showTopMessage( errorMessage, 'error' );
		} ).always( function () {
			currentView.deleting = false;
			$( '#fstu-steering-delete-btn' ).prop( 'disabled', false );
		} );
	}

	function updateListPagination() {
		$( '#fstu-steering-per-page' ).val( String( listState.perPage ) );
		if ( $( '#fstu-steering-status-filter' ).length ) {
			$( '#fstu-steering-status-filter' ).val( String( listState.statusId ) );
		}
		$( '#fstu-steering-pagination-pages' ).html( buildPaginationButtons( listState.page, listState.totalPages, 'fstu-steering-page-btn' ) );
		$( '#fstu-steering-pagination-info' ).text( buildPaginationInfo( listState.total, listState.page, listState.totalPages ) );
		setPaginationArrowState( '#fstu-steering-prev-page', '#fstu-steering-next-page', listState.page, listState.totalPages );
	}

	function updateFooterSummary( html ) {
		const $footer = $( '#fstu-steering-footer-summary' );
		if ( $footer.length ) {
			$footer.html( html || '' );
		}
	}

	function updateProtocolPagination() {
		$( '#fstu-steering-protocol-per-page' ).val( String( protocolState.perPage ) );
		$( '#fstu-steering-protocol-pagination-pages' ).html( buildPaginationButtons( protocolState.page, protocolState.totalPages, 'fstu-steering-protocol-page-btn' ) );
		$( '#fstu-steering-protocol-pagination-info' ).text( buildPaginationInfo( protocolState.total, protocolState.page, protocolState.totalPages ) );
		setPaginationArrowState( '#fstu-steering-protocol-prev-page', '#fstu-steering-protocol-next-page', protocolState.page, protocolState.totalPages );
	}

	function closeAllDropdowns() {
		$( '.fstu-steering-dropdown' ).removeClass( 'is-open' );
		$( '.fstu-steering-dropdown__toggle' ).attr( 'aria-expanded', 'false' );
	}

	function showModal( modalId ) {
		$( '#' + modalId ).removeClass( 'fstu-hidden' ).attr( 'aria-hidden', 'false' );
		$( 'body' ).addClass( 'fstu-modal-open' );
	}

	function closeModal( modalId ) {
		$( '#' + modalId ).addClass( 'fstu-hidden' ).attr( 'aria-hidden', 'true' );
		if ( modalId === 'fstu-steering-view-modal' ) {
			updateViewUrlState( 0 );
			currentView.steeringId = 0;
			currentView.confirming = false;
			currentView.statusLoading = false;
			currentView.deleting = false;
			resetViewModal();
		}
		if ( modalId === 'fstu-steering-form-modal' ) {
			resetForm();
		}
		if ( ! $( '.fstu-modal-overlay:not(.fstu-hidden)' ).length ) {
			$( 'body' ).removeClass( 'fstu-modal-open' );
		}
	}

	function setTableState( selector, colspan, message ) {
		$( selector ).html( '<tr class="fstu-row"><td colspan="' + colspan + '" class="fstu-no-results">' + escHtml( message ) + '</td></tr>' );
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

	function ajaxRequest( action, data ) {
		return $.post( l10n.ajaxUrl, $.extend( {
			action: action,
			nonce: l10n.nonce,
		}, data ) ).then( function ( response ) {
			if ( response && response.success ) {
				return response.data || {};
			}

			return $.Deferred().reject( response ).promise();
		} );
	}

	function ajaxFormDataRequest( formData ) {
		return $.ajax( {
			url: l10n.ajaxUrl,
			method: 'POST',
			data: formData,
			processData: false,
			contentType: false,
		} ).then( function ( response ) {
			if ( response && response.success ) {
				return response.data || {};
			}

			return $.Deferred().reject( response ).promise();
		} );
	}

	function getErrorMessage( response, fallback ) {
		if ( response && response.responseJSON && response.responseJSON.data && response.responseJSON.data.message ) {
			return response.responseJSON.data.message;
		}

		if ( response && response.data && response.data.message ) {
			return response.data.message;
		}

		return fallback;
	}

	function showMessage( selector, message, type ) {
		const $message = $( selector );
		$message.removeClass( 'fstu-hidden fstu-form-message--error fstu-form-message--success' );
		$message.addClass( type === 'error' ? 'fstu-form-message--error' : 'fstu-form-message--success' );
		$message.text( message );
	}

	function hideMessage( selector ) {
		$( selector ).addClass( 'fstu-hidden' ).text( '' );
	}

	function showTopMessage( message, type ) {
		const $notice = $( '#fstu-steering-notice' );
		$notice.removeClass( 'fstu-hidden fstu-steering-notice--error fstu-steering-notice--success' );
		$notice.addClass( type === 'error' ? 'fstu-steering-notice--error' : 'fstu-steering-notice--success' );
		$notice.text( message );
	}

	function setText( selector, value ) {
		$( selector ).text( value );
	}

	function setLink( selector, url ) {
		const $target = $( selector );
		if ( url ) {
			$target.html( '<a href="' + escAttr( url ) + '" target="_blank" rel="noopener noreferrer">посилання</a>' );
		} else {
			$target.text( '—' );
		}
	}

	function buildVerificationProgress( item ) {
		const count = parseInt( item.CntVerification, 10 ) || 0;
		const threshold = parseInt( item.VerificationThreshold, 10 ) || 3;
		return count + ' / ' + threshold;
	}

	function buildVerificationState( item ) {
		if ( item.IsRegistered ) {
			return 'Зареєстровано';
		}

		if ( item.HasConfirmed ) {
			return 'Ви вже підтвердили';
		}

		if ( item.CanConfirmVerification ) {
			return 'Доступно для підтвердження';
		}

		return 'Очікує підтвердження';
	}

	function renderVerifiersList( verifiers ) {
		const $target = $( '#fstu-steering-view-verifiers-list' );
		if ( ! $target.length ) {
			return;
		}

		if ( ! verifiers || ! verifiers.length ) {
			$target.html( '<div class="fstu-steering-verifiers__empty">Підтвердження ще відсутні.</div>' );
			return;
		}

		let html = '<div class="fstu-steering-verifiers__table">';
		verifiers.forEach( function ( item, index ) {
			html += '<div class="fstu-steering-verifiers__row">';
			html += '<div class="fstu-steering-verifiers__cell fstu-steering-verifiers__cell--index">' + ( index + 1 ) + '</div>';
			html += '<div class="fstu-steering-verifiers__cell">' + escHtml( item.FIO || '—' ) + '</div>';
			html += '<div class="fstu-steering-verifiers__cell">' + escHtml( resolveVerifierQualification( item ) ) + '</div>';
			html += '<div class="fstu-steering-verifiers__cell">' + escHtml( formatDateTimeValue( item.VerificationSteering_Date ) || '—' ) + '</div>';
			html += '</div>';
		} );
		html += '</div>';

		$target.html( html );
	}

	function resolveVerifierQualification( item ) {
		if ( item.Skipper_RegNumber ) {
			return 'Капітан';
		}

		if ( item.Steering_RegNumber ) {
			return 'Стерновий';
		}

		return '—';
	}

	function toggleConfirmButton( isVisible ) {
		const $button = $( '#fstu-steering-confirm-btn' );
		if ( ! $button.length ) {
			return;
		}

		$button.toggleClass( 'fstu-hidden', ! isVisible );
		$button.prop( 'disabled', false );
	}

	function toggleStatusActionButtons( item ) {
		$( '#fstu-steering-register-btn' ).toggleClass( 'fstu-hidden', ! item.CanRegister );
		$( '#fstu-steering-send-post-btn' ).toggleClass( 'fstu-hidden', ! item.CanMarkSentPost );
		$( '#fstu-steering-received-btn' ).toggleClass( 'fstu-hidden', ! item.CanMarkReceived );
		setStatusButtonsDisabled( false );
	}

	function toggleAdminRows() {
		const isAdminView = !! ( permissions.canManage || permissions.canManageStatus || permissions.canSeeFinance );
		$( '.fstu-steering-admin-row' ).toggleClass( 'fstu-hidden', ! isAdminView );
	}

	function setStatusButtonsDisabled( isDisabled ) {
		$( '#fstu-steering-register-btn, #fstu-steering-send-post-btn, #fstu-steering-received-btn' ).prop( 'disabled', !! isDisabled );
	}

	function resetViewModal() {
		hideMessage( '#fstu-steering-view-message' );
		setText( '#fstu-steering-view-fio', '—' );
		setText( '#fstu-steering-view-fio-eng', '—' );
		setText( '#fstu-steering-view-type-app', '—' );
		setText( '#fstu-steering-view-number', '—' );
		setText( '#fstu-steering-view-status', '—' );
		setText( '#fstu-steering-view-date-pay', '—' );
		setText( '#fstu-steering-view-birth-date', '—' );
		setText( '#fstu-steering-view-city-np', '—' );
		setText( '#fstu-steering-view-number-np', '—' );
		setText( '#fstu-steering-view-date-create', '—' );
		setText( '#fstu-steering-view-date-delivery', '—' );
		setText( '#fstu-steering-view-verifications', '0' );
		setText( '#fstu-steering-view-verification-progress', '0 / 3' );
		setText( '#fstu-steering-view-verification-state', '—' );
		setLink( '#fstu-steering-view-url', '' );
		renderVerifiersList( [] );
		toggleConfirmButton( false );
		toggleStatusActionButtons( {} );
		$( '#fstu-steering-edit-btn' ).addClass( 'fstu-hidden' );
		$( '#fstu-steering-delete-btn' ).addClass( 'fstu-hidden' ).prop( 'disabled', false );
		$( '#fstu-steering-view-photo' ).attr( 'src', '' ).addClass( 'fstu-hidden' );
		toggleAdminRows();
	}

	function resolveTypeLabel( typeId ) {
		const normalizedTypeId = parseInt( typeId, 10 ) || 0;
		let label = '';

		( l10n.typeOptions || [] ).forEach( function ( item ) {
			if ( normalizedTypeId === ( parseInt( item.value, 10 ) || 0 ) ) {
				label = item.label || '';
			}
		} );

		return label;
	}

	function buildEnglishFullName( item ) {
		const parts = [
			item.Steering_SurNameEng || '',
			item.Steering_NameEng || '',
		].filter( function ( part ) {
			return !! String( part ).trim();
		} );

		return parts.join( ' ' );
	}

	function formatDateValue( value ) {
		if ( ! value ) {
			return '';
		}

		const date = new Date( value );
		if ( window.isNaN( date.getTime() ) ) {
			return value;
		}

		const day = String( date.getDate() ).padStart( 2, '0' );
		const month = String( date.getMonth() + 1 ).padStart( 2, '0' );
		const year = date.getFullYear();

		return day + '.' + month + '.' + year;
	}

	function formatDateTimeValue( value ) {
		if ( ! value ) {
			return '';
		}

		const date = new Date( value.replace( ' ', 'T' ) );
		if ( window.isNaN( date.getTime() ) ) {
			return value;
		}

		const day = String( date.getDate() ).padStart( 2, '0' );
		const month = String( date.getMonth() + 1 ).padStart( 2, '0' );
		const year = date.getFullYear();
		const hours = String( date.getHours() ).padStart( 2, '0' );
		const minutes = String( date.getMinutes() ).padStart( 2, '0' );

		return day + '.' + month + '.' + year + ' ' + hours + ':' + minutes;
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

	function escHtml( value ) {
		return String( value )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#039;' );
	}

	function escAttr( value ) {
		return escHtml( value );
	}

	function openFormModal() {
		openFormModalWithMode( 'create', null );
	}

	function applyInitialDeepLink() {
		applyBootstrapIntent();

		const params = new window.URLSearchParams( window.location.search );
		const steeringId = parseInt( params.get( 'steering_id' ), 10 ) || 0;

		if ( steeringId > 0 ) {
			openViewModal( steeringId );
		}
	}

	function applyBootstrapIntent() {
		const bootstrap = l10n.bootstrap || {};

		if ( bootstrapState.applied || ! bootstrap.autoOpen ) {
			return;
		}

		bootstrapState.applied = true;

		if ( bootstrap.mode === 'edit' && parseInt( bootstrap.steeringId, 10 ) > 0 ) {
			openEditForm( parseInt( bootstrap.steeringId, 10 ) || 0 );
			return;
		}

		openFormModal();
		prefillBootstrapUser( parseInt( bootstrap.userId, 10 ) || 0, bootstrap.userFio || '' );
	}

	function prefillBootstrapUser( userId, fio ) {
		if ( userId <= 0 ) {
			return;
		}

		$( '#fstu-steering-form-user-id' ).val( userId );

		const $select = $( '#fstu-steering-form-user-select' );
		if ( $select.length ) {
			if ( ! $select.find( 'option[value="' + userId + '"]' ).length ) {
				$select.append( '<option value="' + escAttr( userId ) + '">' + escHtml( fio || ( 'User #' + userId ) ) + '</option>' );
			}

			$select.val( String( userId ) );
		}
	}

	function updateViewUrlState( steeringId ) {
		if ( ! window.history || typeof window.history.replaceState !== 'function' || ! window.URL ) {
			return;
		}

		const url = new window.URL( window.location.href );
		if ( steeringId > 0 ) {
			url.searchParams.set( 'steering_id', String( steeringId ) );
		} else {
			url.searchParams.delete( 'steering_id' );
		}

		window.history.replaceState( {}, document.title, url.toString() );
	}

	function invalidateDictionariesCache() {
		dictionaries.loaded = false;
		dictionaries.cities = [];
		dictionaries.availableUsers = [];
	}

	function openEditForm( steeringId ) {
		if ( ! steeringId || ! permissions.canManage ) {
			return;
		}

		showModal( 'fstu-steering-form-modal' );
		showMessage( '#fstu-steering-form-message', l10n.messages.loading, 'success' );

		$.when( loadDictionaries(), ajaxRequest( l10n.actions.getSingle, { steering_id: steeringId } ) ).done( function ( dictionariesResponse, singleResponse ) {
			void dictionariesResponse;
			const response = Array.isArray( singleResponse ) ? singleResponse[ 0 ] : singleResponse;
			openFormModalWithMode( 'edit', response.item || {} );
		} ).fail( function ( response ) {
			showMessage( '#fstu-steering-form-message', getErrorMessage( response, l10n.messages.viewError ), 'error' );
		} );
	}

	function openFormModalWithMode( mode, item ) {
		resetForm();
		formState.mode = mode === 'edit' ? 'edit' : 'create';
		formState.steeringId = formState.mode === 'edit' ? ( parseInt( item && item.Steering_ID, 10 ) || 0 ) : 0;

		showModal( 'fstu-steering-form-modal' );

		loadDictionaries().done( function () {
			populateCities();
			populateAvailableUsers();
			configureFormMode( formState.mode, item || {} );
			if ( formState.mode === 'edit' ) {
				fillFormForEdit( item || {} );
			} else {
				applyCurrentUserDefaults();
			}
		} ).fail( function ( response ) {
			showMessage( '#fstu-steering-form-message', getErrorMessage( response, l10n.messages.filtersError ), 'error' );
		} );
	}

	function loadDictionaries() {
		if ( dictionaries.loaded ) {
			return $.Deferred().resolve().promise();
		}

		return ajaxRequest( l10n.actions.getDictionaries, {} ).done( function ( response ) {
			const items = response.items || {};
			dictionaries.cities = items.cities || [];
			dictionaries.availableUsers = items.availableUsers || [];
			dictionaries.loaded = true;
		} );
	}

	function populateCities() {
		const $select = $( '#fstu-steering-form-city-id' );
		const current = parseInt( $select.val(), 10 ) || 0;
		let html = '<option value="0">Оберіть місто</option>';

		dictionaries.cities.forEach( function ( item ) {
			const id = parseInt( item.City_ID, 10 ) || 0;
			const name = escHtml( item.City_Name || '' );
			if ( id > 0 ) {
				html += '<option value="' + id + '">' + name + '</option>';
			}
		} );

		$select.html( html );
		if ( current > 0 ) {
			$select.val( String( current ) );
		}
	}

	function populateAvailableUsers() {
		const $select = $( '#fstu-steering-form-user-select' );
		if ( ! $select.length ) {
			return;
		}

		let html = '<option value="0">Оберіть користувача</option>';

		dictionaries.availableUsers.forEach( function ( item ) {
			const id = parseInt( item.User_ID, 10 ) || 0;
			const fio = escHtml( item.FIO || '' );
			if ( id > 0 ) {
				html += '<option value="' + id + '">' + fio + '</option>';
			}
		} );

		$select.html( html );
	}

	function applyCurrentUserDefaults() {
		if ( permissions.canManage ) {
			return;
		}

		$( '#fstu-steering-form-user-id' ).val( parseInt( currentUser.userId, 10 ) || 0 );
		$( '#fstu-steering-form-surname-ukr' ).val( currentUser.lastName || '' );
		$( '#fstu-steering-form-name-ukr' ).val( currentUser.firstName || '' );
		$( '#fstu-steering-form-patronymic-ukr' ).val( currentUser.patronymic || '' );
		$( '#fstu-steering-form-birth-date' ).val( normalizeDateForInput( currentUser.birthDate || '' ) );
		$( '#fstu-steering-form-surname-eng' ).val( currentUser.surnameEng || '' );
		$( '#fstu-steering-form-name-eng' ).val( currentUser.nameEng || '' );
	}

	function configureFormMode( mode, item ) {
		const isEdit = mode === 'edit';
		$( '#fstu-steering-form-mode' ).val( isEdit ? 'edit' : 'create' );
		$( '#fstu-steering-form-steering-id' ).val( isEdit ? ( parseInt( item.Steering_ID, 10 ) || 0 ) : 0 );
		$( '#fstu-steering-form-title' ).text( isEdit ? ( l10n.messages.formEditTitle || 'Редагування запису стернового' ) : ( l10n.messages.formTitle || 'Заявка на посвідчення стернового' ) );
		$( '#fstu-steering-form-submit' ).text( isEdit ? ( l10n.messages.formEditSubmit || 'Зберегти зміни' ) : ( l10n.messages.formCreateSubmit || 'Відправити' ) );
		$( '#fstu-steering-form-photo' ).prop( 'required', ! isEdit );
		$( '#fstu-steering-form-photo-label' ).text( isEdit ? 'Нове фото' : 'Фото' );
		$( '#fstu-steering-form-photo-help' ).text( isEdit ? 'За потреби завантажте нове фото. Якщо поле порожнє, поточне фото залишиться без змін.' : 'Підтримуються JPG, PNG, WEBP. Після збереження файл буде конвертований і збережений у legacy-шлях `/photo_steering/{User_ID}.jpg`.' );
		if ( $( '#fstu-steering-user-group' ).length ) {
			$( '#fstu-steering-user-group' ).toggleClass( 'fstu-hidden', isEdit );
		}
	}

	function fillFormForEdit( item ) {
		$( '#fstu-steering-form-user-id' ).val( parseInt( item.User_ID, 10 ) || 0 );
		$( '#fstu-steering-form-surname-ukr' ).val( item.Steering_SurName || '' );
		$( '#fstu-steering-form-name-ukr' ).val( item.Steering_Name || '' );
		$( '#fstu-steering-form-patronymic-ukr' ).val( item.Steering_Partronymic || '' );
		$( '#fstu-steering-form-birth-date' ).val( normalizeDateForInput( item.Steering_BirthDate || '' ) );
		$( '#fstu-steering-form-surname-eng' ).val( item.Steering_SurNameEng || '' );
		$( '#fstu-steering-form-name-eng' ).val( item.Steering_NameEng || '' );
		$( '#fstu-steering-form-type-app' ).val( String( parseInt( item.Steering_TypeApp, 10 ) || 0 ) );
		$( '#fstu-steering-form-city-id' ).val( String( parseInt( item.City_ID, 10 ) || 0 ) );
		$( '#fstu-steering-form-number-np' ).val( item.Steering_NumberNP || '' );
		$( '#fstu-steering-form-url' ).val( item.Steering_Url || '' );
		hideMessage( '#fstu-steering-form-message' );
	}

	function submitForm() {
		const formData = new window.FormData();
		const photoInput = document.getElementById( 'fstu-steering-form-photo' );
		const isEdit = ( $( '#fstu-steering-form-mode' ).val() || 'create' ) === 'edit';

		formData.append( 'action', isEdit ? l10n.actions.update : l10n.actions.create );
		formData.append( 'nonce', l10n.nonce );
		formData.append( 'steering_id', parseInt( $( '#fstu-steering-form-steering-id' ).val(), 10 ) || 0 );
		formData.append( 'user_id', parseInt( $( '#fstu-steering-form-user-id' ).val(), 10 ) || 0 );
		formData.append( 'type_app', parseInt( $( '#fstu-steering-form-type-app' ).val(), 10 ) || 0 );
		formData.append( 'surname_ukr', $( '#fstu-steering-form-surname-ukr' ).val() || '' );
		formData.append( 'name_ukr', $( '#fstu-steering-form-name-ukr' ).val() || '' );
		formData.append( 'patronymic_ukr', $( '#fstu-steering-form-patronymic-ukr' ).val() || '' );
		formData.append( 'surname_eng', $( '#fstu-steering-form-surname-eng' ).val() || '' );
		formData.append( 'name_eng', $( '#fstu-steering-form-name-eng' ).val() || '' );
		formData.append( 'birth_date', $( '#fstu-steering-form-birth-date' ).val() || '' );
		formData.append( 'city_id', parseInt( $( '#fstu-steering-form-city-id' ).val(), 10 ) || 0 );
		formData.append( 'number_np', $( '#fstu-steering-form-number-np' ).val() || '' );
		formData.append( 'url', $( '#fstu-steering-form-url' ).val() || '' );
		formData.append( 'fstu_website', $( '#fstu-steering-form-website' ).val() || '' );

		if ( photoInput && photoInput.files && photoInput.files[ 0 ] ) {
			formData.append( 'photo', photoInput.files[ 0 ] );
		}

		ajaxFormDataRequest( formData ).done( function ( response ) {
			const successMessage = response.message || ( isEdit ? l10n.messages.updateSuccess : l10n.messages.saveSuccess );
			showMessage( '#fstu-steering-form-message', successMessage, 'success' );
			showTopMessage( successMessage, 'success' );
			if ( ! isEdit && ! permissions.canManage ) {
				$( '#fstu-steering-add-btn' ).addClass( 'fstu-hidden' );
				l10n.submitBlocked = true;
			}
			if ( isEdit && response.item && currentView.steeringId && parseInt( response.item.Steering_ID, 10 ) === currentView.steeringId ) {
				fillViewModal( response.item );
			}
			if ( ! isEdit ) {
				invalidateDictionariesCache();
			}
			loadList();
			window.setTimeout( function () {
				closeModal( 'fstu-steering-form-modal' );
			}, 400 );
		} ).fail( function ( response ) {
			showMessage( '#fstu-steering-form-message', getErrorMessage( response, isEdit ? l10n.messages.updateError : l10n.messages.saveError ), 'error' );
		} );
	}

	function resetForm() {
		const form = document.getElementById( 'fstu-steering-form' );
		if ( form ) {
			form.reset();
		}

		formState.mode = 'create';
		formState.steeringId = 0;
		$( '#fstu-steering-form-user-id' ).val( 0 );
		$( '#fstu-steering-form-mode' ).val( 'create' );
		$( '#fstu-steering-form-steering-id' ).val( 0 );
		configureFormMode( 'create', {} );
		hideMessage( '#fstu-steering-form-message' );
	}

	function normalizeDateForInput( value ) {
		if ( ! value ) {
			return '';
		}

		if ( /^\d{4}-\d{2}-\d{2}$/.test( value ) ) {
			return value;
		}

		const date = new Date( value );
		if ( window.isNaN( date.getTime() ) ) {
			return '';
		}

		const day = String( date.getDate() ).padStart( 2, '0' );
		const month = String( date.getMonth() + 1 ).padStart( 2, '0' );
		const year = date.getFullYear();

		return year + '-' + month + '-' + day;
	}
} );
