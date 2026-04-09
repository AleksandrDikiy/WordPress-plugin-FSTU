/**
 * JS модуля «Реєстр суддів ФСТУ».
 *
 * Version:     1.1.0
 * Date_update: 2026-04-09
 *
 * @package FSTU
 */

/* global fstuRefereesL10n */

jQuery( document ).ready( function ( $ ) {
	'use strict';

	if ( typeof fstuRefereesL10n === 'undefined' ) {
		return;
	}

	const $module = $( '#fstu-referees' );
	if ( ! $module.length ) {
		return;
	}

	const l10n = fstuRefereesL10n;
	const permissions = l10n.permissions || {};
	const listState = {
		page: 1,
		perPage: parseInt( l10n.defaults.perPage, 10 ) || 10,
		search: '',
		regionId: 0,
		categoryId: 0,
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
		regions: [],
		categories: [],
		availableUsers: [],
		calendars: [],
	};
	const currentState = {
		referee: null,
		userId: 0,
		certificate: null,
	};
	const bootstrapState = {
		applied: false,
	};

	bindGlobalEvents();
	bindListEvents();
	bindProtocolEvents();
	bindModalEvents();
	bindFormEvents();
	bindCertificatesEvents();

	loadDictionaries().always( function () {
		loadList();
		applyBootstrapIntent();
	} );

	function bindGlobalEvents() {
		$( document ).on( 'click', function ( event ) {
			if ( ! $( event.target ).closest( '.fstu-referees-dropdown' ).length ) {
				closeAllDropdowns();
			}
		} );
	}

	function bindListEvents() {
		$( document ).on( 'click', '#fstu-referees-refresh-btn', function () {
			loadList();
		} );

		$( document ).on( 'input keyup change search', '#fstu-referees-search', debounce( function () {
			const nextSearch = $( this ).val().trim();

			if ( nextSearch === listState.search ) {
				return;
			}

			listState.search = nextSearch;
			listState.page = 1;
			loadList();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-referees-region-filter', function () {
			listState.regionId = parseInt( $( this ).val(), 10 ) || 0;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'change', '#fstu-referees-category-filter', function () {
			listState.categoryId = parseInt( $( this ).val(), 10 ) || 0;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'change', '#fstu-referees-per-page', function () {
			listState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'click', '#fstu-referees-prev-page', function () {
			if ( listState.page > 1 ) {
				listState.page -= 1;
				loadList();
			}
		} );

		$( document ).on( 'click', '#fstu-referees-next-page', function () {
			if ( listState.page < listState.totalPages ) {
				listState.page += 1;
				loadList();
			}
		} );

		$( document ).on( 'click', '.fstu-referees-page-btn', function () {
			const page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
			if ( page !== listState.page ) {
				listState.page = page;
				loadList();
			}
		} );

		$( document ).on( 'click', '.fstu-referees-dropdown__toggle', function ( event ) {
			event.preventDefault();
			event.stopPropagation();

			const $dropdown = $( this ).closest( '.fstu-referees-dropdown' );
			const isOpen = $dropdown.hasClass( 'is-open' );

			closeAllDropdowns();

			if ( ! isOpen ) {
				$dropdown.addClass( 'is-open' );
				positionDropdown( $dropdown );
				$( this ).attr( 'aria-expanded', 'true' );
			}
		} );

		$( document ).on( 'click', '#fstu-referees-add-btn', function () {
			openRefereeFormModal();
		} );

		$( document ).on( 'click', '.fstu-referees-view-btn', function () {
			closeAllDropdowns();
			const refereeId = parseInt( $( this ).data( 'referee-id' ), 10 ) || 0;
			if ( refereeId > 0 ) {
				openViewModal( refereeId );
			}
		} );

		$( document ).on( 'click', '.fstu-referees-edit-btn', function () {
			closeAllDropdowns();
			const refereeId = parseInt( $( this ).data( 'referee-id' ), 10 ) || 0;
			if ( refereeId > 0 ) {
				openRefereeFormModal( refereeId );
			}
		} );

		$( document ).on( 'click', '.fstu-referees-delete-btn', function () {
			closeAllDropdowns();
			const refereeId = parseInt( $( this ).data( 'referee-id' ), 10 ) || 0;
			if ( refereeId > 0 ) {
				deleteReferee( refereeId );
			}
		} );

		$( document ).on( 'click', '.fstu-referees-certificates-btn', function () {
			closeAllDropdowns();
			const refereeId = parseInt( $( this ).data( 'referee-id' ), 10 ) || 0;
			const userId = parseInt( $( this ).data( 'user-id' ), 10 ) || 0;
			const fio = $( this ).data( 'referee-fio' ) || '';
			openCertificatesModal( refereeId, userId, fio );
		} );
	}

	function bindProtocolEvents() {
		$( document ).on( 'click', '#fstu-referees-protocol-btn', function () {
			$( '#fstu-referees-main' ).addClass( 'fstu-hidden' );
			$( '#fstu-referees-protocol' ).removeClass( 'fstu-hidden' );
			$( '#fstu-referees-protocol-btn' ).addClass( 'fstu-hidden' );
			$( '#fstu-referees-protocol-back-btn' ).removeClass( 'fstu-hidden' );
			protocolState.page = 1;
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-referees-protocol-back-btn', function () {
			$( '#fstu-referees-main' ).removeClass( 'fstu-hidden' );
			$( '#fstu-referees-protocol' ).addClass( 'fstu-hidden' );
			$( '#fstu-referees-protocol-btn' ).removeClass( 'fstu-hidden' );
			$( '#fstu-referees-protocol-back-btn' ).addClass( 'fstu-hidden' );
		} );

		$( document ).on( 'input', '#fstu-referees-protocol-search', debounce( function () {
			protocolState.search = $( this ).val().trim();
			protocolState.page = 1;
			loadProtocol();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-referees-protocol-per-page', function () {
			protocolState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			protocolState.page = 1;
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-referees-protocol-prev-page', function () {
			if ( protocolState.page > 1 ) {
				protocolState.page -= 1;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '#fstu-referees-protocol-next-page', function () {
			if ( protocolState.page < protocolState.totalPages ) {
				protocolState.page += 1;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '.fstu-referees-protocol-page-btn', function () {
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
		$( document ).on( 'submit', '#fstu-referees-form', function ( event ) {
			event.preventDefault();
			submitRefereeForm();
		} );

		$( document ).on( 'submit', '#fstu-referees-certificate-form', function ( event ) {
			event.preventDefault();
			submitCertificateForm();
		} );

		$( document ).on( 'submit', '#fstu-referees-certificate-bind-form', function ( event ) {
			event.preventDefault();
			submitCertificateBindForm();
		} );
	}

	function bindCertificatesEvents() {
		$( document ).on( 'click', '#fstu-referees-certificates-refresh-btn', function () {
			if ( currentState.userId > 0 ) {
				loadCertificates( currentState.userId );
			}
		} );

		$( document ).on( 'click', '#fstu-referees-certificates-add-btn', function () {
			openCertificateFormModal();
		} );

		$( document ).on( 'click', '.fstu-referees-certificate-bind-btn', function () {
			const certificate = {
				id: parseInt( $( this ).data( 'certificate-id' ), 10 ) || 0,
				meta: $( this ).data( 'certificate-meta' ) || '',
				categoryId: parseInt( $( this ).data( 'category-id' ), 10 ) || 0,
			};
			openCertificateBindModal( certificate );
		} );

		$( document ).on( 'click', '#fstu-referees-unbind-certificate-btn', function () {
			unbindCertificateCategory();
		} );

		$( document ).on( 'click', '#fstu-referees-view-open-certificates-btn', function () {
			closeModal( 'fstu-referees-view-modal' );
			if ( currentState.referee && currentState.referee.User_ID ) {
				openCertificatesModal(
					parseInt( currentState.referee.Referee_ID, 10 ) || 0,
					parseInt( currentState.referee.User_ID, 10 ) || 0,
					currentState.referee.FIO || ''
				);
			}
		} );
	}

	function loadDictionaries() {
		return ajaxRequest( l10n.actions.getDictionaries, {} ).done( function ( response ) {
			const items = response.items || {};
			dictionaries.loaded = true;
			dictionaries.regions = items.regions || [];
			dictionaries.categories = items.categories || [];
			dictionaries.availableUsers = items.availableUsers || [];
			dictionaries.calendars = items.calendars || [];

			renderRegionFilter();
			renderCategoryFilter();
			renderCategoryOptions( '#fstu-referees-form-category-id', true );
			renderCategoryOptions( '#fstu-referees-bind-category-id', true );
			renderUserOptions();
			renderCalendarOptions();
		} ).fail( function ( response ) {
			showTopMessage( getErrorMessage( response, l10n.messages.filtersError ), 'error' );
		} );
	}

	function loadList() {
		if ( listState.loading ) {
			return;
		}

		listState.loading = true;
		setTableLoading( '#fstu-referees-tbody', 7, l10n.messages.loading );

		ajaxRequest( l10n.actions.getList, {
			search: listState.search,
			page: listState.page,
			per_page: listState.perPage,
			region_id: listState.regionId,
			referee_category_id: listState.categoryId,
		} ).done( function ( response ) {
			$( '#fstu-referees-tbody' ).html( response.html || '' );
			listState.total = parseInt( response.total, 10 ) || 0;
			listState.totalPages = parseInt( response.total_pages, 10 ) || 1;
			listState.page = parseInt( response.page, 10 ) || 1;
			listState.perPage = parseInt( response.per_page, 10 ) || listState.perPage;
			updateListPagination();
		} ).fail( function ( response ) {
			setTableError( '#fstu-referees-tbody', 7, getErrorMessage( response, l10n.messages.error ) );
		} ).always( function () {
			listState.loading = false;
		} );
	}

	function loadProtocol() {
		if ( protocolState.loading ) {
			return;
		}

		protocolState.loading = true;
		setTableLoading( '#fstu-referees-protocol-tbody', 6, l10n.messages.loading );

		ajaxRequest( l10n.actions.getProtocol, {
			search: protocolState.search,
			page: protocolState.page,
			per_page: protocolState.perPage,
		} ).done( function ( response ) {
			$( '#fstu-referees-protocol-tbody' ).html( response.html || '' );
			protocolState.total = parseInt( response.total, 10 ) || 0;
			protocolState.totalPages = parseInt( response.total_pages, 10 ) || 1;
			protocolState.page = parseInt( response.page, 10 ) || 1;
			protocolState.perPage = parseInt( response.per_page, 10 ) || protocolState.perPage;
			updateProtocolPagination();
		} ).fail( function ( response ) {
			setTableError( '#fstu-referees-protocol-tbody', 6, getErrorMessage( response, l10n.messages.protocolError ) );
		} ).always( function () {
			protocolState.loading = false;
		} );
	}

	function openViewModal( refereeId ) {
		showModal( 'fstu-referees-view-modal' );
		showMessage( '#fstu-referees-view-message', l10n.messages.loading, 'success' );

		ajaxRequest( l10n.actions.getSingle, { referee_id: refereeId } ).done( function ( response ) {
			currentState.referee = response.item || null;
			fillViewModal( currentState.referee || {} );
			hideMessage( '#fstu-referees-view-message' );
		} ).fail( function ( response ) {
			showMessage( '#fstu-referees-view-message', getErrorMessage( response, l10n.messages.error ), 'error' );
		} );
	}

	function openRefereeFormModal( refereeId ) {
		const $title = $( '#fstu-referees-form-modal-title' );

		resetRefereeForm();
		showModal( 'fstu-referees-form-modal' );

		if ( ! refereeId ) {
			$title.text( l10n.messages.formAddTitle );
			return;
		}

		$title.text( l10n.messages.formEditTitle );
		showMessage( '#fstu-referees-form-message', l10n.messages.loading, 'success' );

		ajaxRequest( l10n.actions.getSingle, { referee_id: refereeId } ).done( function ( response ) {
			fillRefereeForm( response.item || {} );
			hideMessage( '#fstu-referees-form-message' );
		} ).fail( function ( response ) {
			showMessage( '#fstu-referees-form-message', getErrorMessage( response, l10n.messages.error ), 'error' );
		} );
	}

	function applyBootstrapIntent() {
		const bootstrap = l10n.bootstrap || {};

		if ( bootstrapState.applied || ! bootstrap.autoOpen ) {
			return;
		}

		bootstrapState.applied = true;

		if ( bootstrap.mode === 'edit' && parseInt( bootstrap.refereeId, 10 ) > 0 ) {
			openRefereeFormModal( parseInt( bootstrap.refereeId, 10 ) || 0 );
			return;
		}

		openRefereeFormModal();
		prefillBootstrapUser( parseInt( bootstrap.userId, 10 ) || 0, bootstrap.userFio || '' );
	}

	function prefillBootstrapUser( userId, fio ) {
		if ( userId <= 0 ) {
			return;
		}

		const $select = $( '#fstu-referees-form-user-id' );
		if ( ! $select.find( 'option[value="' + userId + '"]' ).length ) {
			$select.append( '<option value="' + escAttr( userId ) + '">' + escHtml( fio || ( 'User #' + userId ) ) + '</option>' );
		}

		$select.val( String( userId ) );
		$( '#fstu-referees-user-group' ).addClass( 'fstu-hidden' );
		$( '#fstu-referees-user-name-group' ).removeClass( 'fstu-hidden' );
		$( '#fstu-referees-form-user-name' ).text( fio || ( 'User #' + userId ) );
	}

	function openCertificatesModal( refereeId, userId, fio ) {
		currentState.userId = userId;
		$( '#fstu-referees-certificates-referee-name' ).text( fio || '—' );
		$( '#fstu-referees-certificates-modal' ).data( 'referee-id', refereeId );
		showModal( 'fstu-referees-certificates-modal' );
		loadCertificates( userId );
	}

	function loadCertificates( userId ) {
		setTableLoading( '#fstu-referees-certificates-tbody', 5, l10n.messages.loading );
		ajaxRequest( l10n.actions.getCertificates, { user_id: userId } ).done( function ( response ) {
			renderCertificatesRows( response.items || [] );
		} ).fail( function ( response ) {
			setTableError( '#fstu-referees-certificates-tbody', 5, getErrorMessage( response, l10n.messages.error ) );
		} );
	}

	function openCertificateFormModal() {
		resetCertificateForm();
		$( '#fstu-referees-certificate-referee-name' ).text( $( '#fstu-referees-certificates-referee-name' ).text() || '—' );
		$( '#fstu-referees-certificate-user-id' ).val( currentState.userId || 0 );
		showModal( 'fstu-referees-certificate-form-modal' );
	}

	function openCertificateBindModal( certificate ) {
		currentState.certificate = certificate;
		$( '#fstu-referees-bind-certificate-id' ).val( certificate.id || 0 );
		$( '#fstu-referees-bind-certificate-meta' ).text( certificate.meta || '—' );
		$( '#fstu-referees-bind-category-id' ).val( certificate.categoryId || 0 );
		$( '#fstu-referees-unbind-certificate-btn' ).toggle( !! permissions.canUnbindCertificates && !! certificate.categoryId );
		$( '#fstu-referees-bind-certificate-submit' ).toggle( !! permissions.canManageCertificates );
		showModal( 'fstu-referees-certificate-bind-modal' );
	}

	function submitRefereeForm() {
		const refereeId = parseInt( $( '#fstu-referees-form-referee-id' ).val(), 10 ) || 0;
		const action = refereeId > 0 ? l10n.actions.update : l10n.actions.create;
		const data = {
			referee_id: refereeId,
			user_id: parseInt( $( '#fstu-referees-form-user-id' ).val(), 10 ) || 0,
			referee_category_id: parseInt( $( '#fstu-referees-form-category-id' ).val(), 10 ) || 0,
			num_order: $( '#fstu-referees-form-num-order' ).val() || '',
			date_order: $( '#fstu-referees-form-date-order' ).val() || '',
			url_order: $( '#fstu-referees-form-url-order' ).val() || '',
			fstu_website: $( '#fstu-referees-form-website' ).val() || '',
		};

		ajaxRequest( action, data ).done( function ( response ) {
			showMessage( '#fstu-referees-form-message', response.message || l10n.messages.saveSuccess, 'success' );
			showTopMessage( response.message || l10n.messages.saveSuccess, 'success' );
			if ( 0 === refereeId ) {
				loadDictionaries();
			}
			loadList();
			setTimeout( function () {
				closeModal( 'fstu-referees-form-modal' );
			}, 400 );
		} ).fail( function ( response ) {
			showMessage( '#fstu-referees-form-message', getErrorMessage( response, l10n.messages.saveError ), 'error' );
		} );
	}

	function submitCertificateForm() {
		const data = {
			user_id: parseInt( $( '#fstu-referees-certificate-user-id' ).val(), 10 ) || 0,
			calendar_id: parseInt( $( '#fstu-referees-certificate-calendar-id' ).val(), 10 ) || 0,
			certificate_url: $( '#fstu-referees-certificate-url' ).val() || '',
			fstu_website: $( '#fstu-referees-certificate-website' ).val() || '',
		};

		ajaxRequest( l10n.actions.createCertificate, data ).done( function ( response ) {
			showMessage( '#fstu-referees-certificate-form-message', response.message || l10n.messages.certSaveSuccess, 'success' );
			showTopMessage( response.message || l10n.messages.certSaveSuccess, 'success' );
			loadCertificates( currentState.userId );
			loadList();
			setTimeout( function () {
				closeModal( 'fstu-referees-certificate-form-modal' );
			}, 400 );
		} ).fail( function ( response ) {
			showMessage( '#fstu-referees-certificate-form-message', getErrorMessage( response, l10n.messages.saveError ), 'error' );
		} );
	}

	function submitCertificateBindForm() {
		const data = {
			certificate_id: parseInt( $( '#fstu-referees-bind-certificate-id' ).val(), 10 ) || 0,
			referee_category_id: parseInt( $( '#fstu-referees-bind-category-id' ).val(), 10 ) || 0,
		};

		ajaxRequest( l10n.actions.bindCertificateCategory, data ).done( function ( response ) {
			showMessage( '#fstu-referees-certificate-bind-message', response.message || l10n.messages.certBindSuccess, 'success' );
			showTopMessage( response.message || l10n.messages.certBindSuccess, 'success' );
			loadCertificates( currentState.userId );
			setTimeout( function () {
				closeModal( 'fstu-referees-certificate-bind-modal' );
			}, 400 );
		} ).fail( function ( response ) {
			showMessage( '#fstu-referees-certificate-bind-message', getErrorMessage( response, l10n.messages.error ), 'error' );
		} );
	}

	function unbindCertificateCategory() {
		const certificateId = parseInt( $( '#fstu-referees-bind-certificate-id' ).val(), 10 ) || 0;
		if ( ! certificateId ) {
			return;
		}

		if ( ! window.confirm( l10n.messages.confirmUnbind ) ) {
			return;
		}

		ajaxRequest( l10n.actions.unbindCertificateCategory, {
			certificate_id: certificateId,
		} ).done( function ( response ) {
			showMessage( '#fstu-referees-certificate-bind-message', response.message || l10n.messages.certUnbindSuccess, 'success' );
			showTopMessage( response.message || l10n.messages.certUnbindSuccess, 'success' );
			loadCertificates( currentState.userId );
			setTimeout( function () {
				closeModal( 'fstu-referees-certificate-bind-modal' );
			}, 400 );
		} ).fail( function ( response ) {
			showMessage( '#fstu-referees-certificate-bind-message', getErrorMessage( response, l10n.messages.error ), 'error' );
		} );
	}

	function deleteReferee( refereeId ) {
		if ( ! window.confirm( l10n.messages.confirmDelete ) ) {
			return;
		}

		ajaxRequest( l10n.actions.delete, { referee_id: refereeId } ).done( function () {
			showTopMessage( l10n.messages.deleteSuccess, 'success' );
			loadDictionaries();
			loadList();
		} ).fail( function ( response ) {
			showTopMessage( getErrorMessage( response, l10n.messages.deleteError ), 'error' );
		} );
	}

	function fillViewModal( item ) {
		setText( '#fstu-referees-view-fio', item.FIO || '—' );
		setText( '#fstu-referees-view-category', item.RefereeCategory_Name || '—' );
		setText( '#fstu-referees-view-region', item.Region_Name || '—' );
		setText( '#fstu-referees-view-card-number', item.CardNumber || '—' );
		setText( '#fstu-referees-view-order-number', item.Referee_NumOrder || '—' );
		setText( '#fstu-referees-view-order-date', formatDateValue( item.Referee_DateOrder, false ) || '—' );
		setLink( '#fstu-referees-view-order-url', item.Referee_URLOrder || '' );
		setText( '#fstu-referees-view-created-date', formatDateValue( item.Referee_DateCreate, true ) || '—' );
		setText( '#fstu-referees-view-created-by', item.CreatedByFio || '—' );
		setText( '#fstu-referees-view-certificates-count', item.CntCertificates || 0 );
		renderCertificatesPreview( item.certificates || [] );
	}

	function fillRefereeForm( item ) {
		const refereeId = parseInt( item.Referee_ID, 10 ) || 0;
		$( '#fstu-referees-form-referee-id' ).val( refereeId );
		$( '#fstu-referees-form-category-id' ).val( parseInt( item.RefereeCategory_ID, 10 ) || 0 );
		$( '#fstu-referees-form-num-order' ).val( item.Referee_NumOrder || '' );
		$( '#fstu-referees-form-date-order' ).val( normalizeDateForInput( item.Referee_DateOrder || '' ) );
		$( '#fstu-referees-form-url-order' ).val( item.Referee_URLOrder || '' );

		$( '#fstu-referees-user-group' ).addClass( 'fstu-hidden' );
		$( '#fstu-referees-user-name-group' ).removeClass( 'fstu-hidden' );
		$( '#fstu-referees-form-user-name' ).text( item.FIO || '—' );
	}

	function renderCertificatesRows( items ) {
		const $tbody = $( '#fstu-referees-certificates-tbody' );
		if ( ! items.length ) {
			$tbody.html( '<tr class="fstu-row"><td colspan="5" class="fstu-no-results">' + escHtml( l10n.messages.emptyCertificates ) + '</td></tr>' );
			return;
		}

		let html = '';
		items.forEach( function ( item ) {
			const categoryName = item.RefereeCategory_Name || '—';
			const url = item.CertificatesForRefereeing_URL || '';
			const beginDate = formatDateValue( item.Calendar_DateBegin, false );
			const endDate = formatDateValue( item.Calendar_DateEnd, false );
			const meta = [ item.Calendar_Name || '', beginDate, endDate ].filter( Boolean ).join( ' / ' );
			let actions = '';

			if ( permissions.canManageCertificates || permissions.canUnbindCertificates ) {
				actions += '<button type="button" class="fstu-referees-dropdown__item fstu-referees-certificate-bind-btn" data-certificate-id="' + escAttr( item.CertificatesForRefereeing_ID ) + '" data-certificate-meta="' + escAttr( meta ) + '" data-category-id="' + escAttr( item.RefereeCategory_ID || 0 ) + '">' + escHtml( 'Категорія' ) + '</button>';
			}

			html += '<tr class="fstu-row">';
			html += '<td class="fstu-td fstu-td--date">' + escHtml( formatDateValue( item.CertificatesForRefereeing_DateCreate, true ) || '—' ) + '</td>';
			html += '<td class="fstu-td">' + escHtml( meta || '—' ) + '</td>';
			html += '<td class="fstu-td">' + escHtml( categoryName ) + '</td>';
			html += '<td class="fstu-td">' + buildLink( url ) + '</td>';
			if ( actions ) {
				html += '<td class="fstu-td fstu-td--actions"><div class="fstu-referees-dropdown"><button type="button" class="fstu-referees-dropdown__toggle" aria-expanded="false">▼</button><div class="fstu-referees-dropdown__menu">' + actions + '</div></div></td>';
			} else {
				html += '<td class="fstu-td fstu-td--actions"><span class="fstu-text-muted">—</span></td>';
			}
			html += '</tr>';
		} );

		$tbody.html( html );
	}

	function renderCertificatesPreview( items ) {
		const $box = $( '#fstu-referees-view-certificates-preview' );
		if ( ! items.length ) {
			$box.html( '<div class="fstu-text-muted">' + escHtml( l10n.messages.emptyCertificates ) + '</div>' );
			return;
		}

		const preview = items.slice( 0, 3 ).map( function ( item ) {
			const beginDate = formatDateValue( item.Calendar_DateBegin, false );
			return '<div class="fstu-referees-certificates-preview__item"><strong>' + escHtml( item.Calendar_Name || '—' ) + '</strong><br><span>' + escHtml( item.RefereeCategory_Name || '—' ) + '</span><br><small>' + escHtml( beginDate || '—' ) + '</small></div>';
		} ).join( '' );

		$box.html( preview );
	}

	function renderRegionFilter() {
		const $select = $( '#fstu-referees-region-filter' );
		let html = '<option value="0">' + escHtml( 'Усі області' ) + '</option>';
		dictionaries.regions.forEach( function ( item ) {
			html += '<option value="' + escAttr( item.Region_ID ) + '">' + escHtml( item.Region_Name ) + '</option>';
		} );
		$select.html( html );
	}

	function renderCategoryFilter() {
		const $select = $( '#fstu-referees-category-filter' );
		let html = '<option value="0">' + escHtml( 'Усі категорії' ) + '</option>';
		dictionaries.categories.forEach( function ( item ) {
			html += '<option value="' + escAttr( item.RefereeCategory_ID ) + '">' + escHtml( item.RefereeCategory_Name ) + '</option>';
		} );
		$select.html( html );
	}

	function renderCategoryOptions( selector, withEmpty ) {
		const $select = $( selector );
		let html = withEmpty ? '<option value="0">' + escHtml( 'Оберіть категорію' ) + '</option>' : '';
		dictionaries.categories.forEach( function ( item ) {
			html += '<option value="' + escAttr( item.RefereeCategory_ID ) + '">' + escHtml( item.RefereeCategory_Name ) + '</option>';
		} );
		$select.html( html );
	}

	function renderUserOptions() {
		const $select = $( '#fstu-referees-form-user-id' );
		let html = '<option value="0">' + escHtml( 'Оберіть користувача' ) + '</option>';
		dictionaries.availableUsers.forEach( function ( item ) {
			html += '<option value="' + escAttr( item.User_ID ) + '">' + escHtml( item.FIO ) + '</option>';
		} );
		$select.html( html );
	}

	function renderCalendarOptions() {
		const $select = $( '#fstu-referees-certificate-calendar-id' );
		let html = '<option value="0">' + escHtml( 'Оберіть захід' ) + '</option>';
		dictionaries.calendars.forEach( function ( item ) {
			html += '<option value="' + escAttr( item.Calendar_ID ) + '">' + escHtml( item.Calendar_Name ) + '</option>';
		} );
		$select.html( html );
	}

	function updateListPagination() {
		$( '#fstu-referees-per-page' ).val( String( listState.perPage ) );
		$( '#fstu-referees-pagination-pages' ).html( buildPaginationButtons( listState.page, listState.totalPages, 'fstu-referees-page-btn' ) );
		$( '#fstu-referees-pagination-info' ).text( buildPaginationInfo( listState.total, listState.page, listState.totalPages ) );
		setPaginationArrowState( '#fstu-referees-prev-page', '#fstu-referees-next-page', listState.page, listState.totalPages );
	}

	function updateProtocolPagination() {
		$( '#fstu-referees-protocol-per-page' ).val( String( protocolState.perPage ) );
		$( '#fstu-referees-protocol-pagination-pages' ).html( buildPaginationButtons( protocolState.page, protocolState.totalPages, 'fstu-referees-protocol-page-btn' ) );
		$( '#fstu-referees-protocol-pagination-info' ).text( buildPaginationInfo( protocolState.total, protocolState.page, protocolState.totalPages ) );
		setPaginationArrowState( '#fstu-referees-protocol-prev-page', '#fstu-referees-protocol-next-page', protocolState.page, protocolState.totalPages );
	}

	function buildPaginationButtons( currentPage, totalPages, buttonClass ) {
		let html = '';
		const safeCurrentPage = Math.max( 1, currentPage || 1 );
		const safeTotalPages = Math.max( 1, totalPages || 1 );
		const start = Math.max( 1, safeCurrentPage - 2 );
		const end = Math.min( safeTotalPages, safeCurrentPage + 2 );

		if ( start > 1 ) {
			html += buildPageButton( 1, safeCurrentPage === 1, buttonClass );
			if ( start > 2 ) {
				html += '<span class="fstu-pagination__ellipsis">…</span>';
			}
		}

		for ( let page = start; page <= end; page++ ) {
			html += buildPageButton( page, page === safeCurrentPage, buttonClass );
		}

		if ( end < safeTotalPages ) {
			if ( end < safeTotalPages - 1 ) {
				html += '<span class="fstu-pagination__ellipsis">…</span>';
			}
			html += buildPageButton( safeTotalPages, safeCurrentPage === safeTotalPages, buttonClass );
		}

		return html;
	}

	function buildPageButton( page, isActive, buttonClass ) {
		const activeClass = isActive ? ' fstu-btn--page-active' : '';
		const disabled = isActive ? ' disabled' : '';
		return '<button type="button" class="fstu-btn--page ' + buttonClass + activeClass + '" data-page="' + page + '"' + disabled + '>' + page + '</button>';
	}

	function buildPaginationInfo( total, page, totalPages ) {
		return 'Записів: ' + ( total || 0 ) + ' | Сторінка ' + Math.max( 1, page || 1 ) + ' з ' + Math.max( 1, totalPages || 1 );
	}

	function setPaginationArrowState( prevSelector, nextSelector, page, totalPages ) {
		const safePage = Math.max( 1, page || 1 );
		const safeTotalPages = Math.max( 1, totalPages || 1 );

		$( prevSelector ).prop( 'disabled', safePage <= 1 );
		$( nextSelector ).prop( 'disabled', safePage >= safeTotalPages );
	}

	function ajaxRequest( action, data ) {
		return $.ajax( {
			url: l10n.ajaxUrl,
			method: 'POST',
			dataType: 'json',
			data: $.extend( {}, data || {}, {
				action: action,
				nonce: l10n.nonce,
			} ),
		} ).then( function ( response ) {
			if ( response && response.success ) {
				return response.data || {};
			}

			return $.Deferred().reject( response && response.data ? response.data : {} ).promise();
		} );
	}

	function showModal( id ) {
		$( 'body' ).addClass( 'fstu-modal-open' );
		$( '#' + id ).removeClass( 'fstu-hidden' );
	}

	function closeModal( id ) {
		$( '#' + id ).addClass( 'fstu-hidden' );
		resetModalState( id );
		if ( ! $( '.fstu-modal-overlay:not(.fstu-hidden)' ).length ) {
			$( 'body' ).removeClass( 'fstu-modal-open' );
		}
	}

	function resetModalState( id ) {
		switch ( id ) {
			case 'fstu-referees-form-modal':
				resetRefereeForm();
				break;
			case 'fstu-referees-certificate-form-modal':
				resetCertificateForm();
				break;
			case 'fstu-referees-certificate-bind-modal':
				hideMessage( '#fstu-referees-certificate-bind-message' );
				$( '#fstu-referees-bind-certificate-id' ).val( 0 );
				$( '#fstu-referees-bind-category-id' ).val( 0 );
				$( '#fstu-referees-bind-certificate-meta' ).text( '—' );
				currentState.certificate = null;
				break;
			case 'fstu-referees-view-modal':
				hideMessage( '#fstu-referees-view-message' );
				currentState.referee = null;
				$( '#fstu-referees-view-certificates-preview' ).html( '—' );
				break;
			case 'fstu-referees-certificates-modal':
				hideMessage( '#fstu-referees-certificates-message' );
				currentState.userId = 0;
				$( '#fstu-referees-certificates-referee-name' ).text( '—' );
				setTableLoading( '#fstu-referees-certificates-tbody', 5, l10n.messages.loading );
				break;
		}
	}

	function closeAllDropdowns() {
		$( '.fstu-referees-dropdown' ).removeClass( 'is-open is-dropup' );
		$( '.fstu-referees-dropdown__toggle' ).attr( 'aria-expanded', 'false' );
	}

	function positionDropdown( $dropdown ) {
		const rect = $dropdown[0].getBoundingClientRect();
		const spaceBelow = window.innerHeight - rect.bottom;
		if ( spaceBelow < 180 ) {
			$dropdown.addClass( 'is-dropup' );
		}
	}

	function setTableLoading( selector, colspan, message ) {
		$( selector ).html( '<tr class="fstu-row"><td colspan="' + colspan + '" class="fstu-no-results">' + escHtml( message ) + '</td></tr>' );
	}

	function setTableError( selector, colspan, message ) {
		$( selector ).html( '<tr class="fstu-row"><td colspan="' + colspan + '" class="fstu-no-results">' + escHtml( message ) + '</td></tr>' );
	}

	function showMessage( selector, message, type ) {
		const $el = $( selector );
		$el.removeClass( 'fstu-hidden fstu-form-message--error fstu-form-message--success' );
		$el.addClass( type === 'error' ? 'fstu-form-message--error' : 'fstu-form-message--success' );
		$el.text( message );
	}

	function hideMessage( selector ) {
		$( selector ).addClass( 'fstu-hidden' ).text( '' );
	}

	function showTopMessage( message, type ) {
		const $notice = $( '#fstu-referees-notice' );
		if ( ! $notice.length ) {
			if ( type === 'error' ) {
				window.alert( message );
			}
			return;
		}

		$notice
			.removeClass( 'fstu-hidden fstu-page-notice--error fstu-page-notice--success' )
			.addClass( type === 'error' ? 'fstu-page-notice--error' : 'fstu-page-notice--success' )
			.text( message );

		window.clearTimeout( $notice.data( 'hideTimer' ) || 0 );

		const timerId = window.setTimeout( function () {
			$notice.addClass( 'fstu-hidden' ).text( '' );
		}, 5000 );

		$notice.data( 'hideTimer', timerId );
	}


	function getErrorMessage( response, fallback ) {
		if ( response && response.message ) {
			return response.message;
		}
		return fallback || l10n.messages.error;
	}

	function setText( selector, value ) {
		$( selector ).text( value || '—' );
	}

	function setLink( selector, url ) {
		const value = ( url || '' ).trim();
		if ( ! value ) {
			$( selector ).html( '—' );
			return;
		}
		$( selector ).html( '<a href="' + escAttr( value ) + '" target="_blank" rel="noopener noreferrer">' + escHtml( 'Відкрити документ' ) + '</a>' );
	}

	function buildLink( url ) {
		const value = ( url || '' ).trim();
		if ( ! value ) {
			return '<span class="fstu-text-muted">—</span>';
		}
		return '<a href="' + escAttr( value ) + '" target="_blank" rel="noopener noreferrer">' + escHtml( 'Відкрити' ) + '</a>';
	}

	function resetRefereeForm() {
		$( '#fstu-referees-form' )[0].reset();
		$( '#fstu-referees-form-referee-id' ).val( 0 );
		$( '#fstu-referees-user-group' ).removeClass( 'fstu-hidden' );
		$( '#fstu-referees-user-name-group' ).addClass( 'fstu-hidden' );
		hideMessage( '#fstu-referees-form-message' );
	}

	function resetCertificateForm() {
		$( '#fstu-referees-certificate-form' )[0].reset();
		hideMessage( '#fstu-referees-certificate-form-message' );
	}

	function normalizeDateForInput( value ) {
		if ( ! value ) {
			return '';
		}
		return String( value ).substring( 0, 10 );
	}

	function formatDateValue( value, withTime ) {
		if ( ! value ) {
			return '';
		}

		const normalized = String( value ).trim().replace( ' ', 'T' );
		const date = new Date( normalized );

		if ( Number.isNaN( date.getTime() ) ) {
			return String( value );
		}

		const dd = String( date.getDate() ).padStart( 2, '0' );
		const mm = String( date.getMonth() + 1 ).padStart( 2, '0' );
		const yyyy = String( date.getFullYear() );

		if ( ! withTime ) {
			return dd + '.' + mm + '.' + yyyy;
		}

		const hh = String( date.getHours() ).padStart( 2, '0' );
		const ii = String( date.getMinutes() ).padStart( 2, '0' );

		return dd + '.' + mm + '.' + yyyy + ' ' + hh + ':' + ii;
	}

	function escHtml( value ) {
		return $( '<div/>' ).text( value === null || typeof value === 'undefined' ? '' : String( value ) ).html();
	}

	function escAttr( value ) {
		return escHtml( value ).replace( /"/g, '&quot;' );
	}

	function debounce( callback, delay ) {
		let timeoutId = null;
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

