/**
 * JS модуля «Посвідчення членів ФСТУ».
 *
 * Version:     1.6.0
 * Date_update: 2026-04-10
 *
 * @package FSTU
 */

/* global fstuMemberCardApplicationsL10n */

jQuery( document ).ready( function ( $ ) {
	'use strict';

	if ( typeof fstuMemberCardApplicationsL10n === 'undefined' ) {
		return;
	}

	const l10n = fstuMemberCardApplicationsL10n;
	const permissions = l10n.permissions || {};
	const currentUser = l10n.currentUser || {};
	const $module = $( '#fstu-member-card-applications' );
	const $form = $( '#fstu-member-card-applications-form' );

	if ( ! $module.length ) {
		return;
	}

	const listState = {
		page: 1,
		perPage: parseInt( l10n.defaults.perPage, 10 ) || 10,
		search: '',
		regionId: 0,
		statusId: 0,
		typeId: 0,
		total: 0,
		totalPages: 1,
	};

	const protocolState = {
		page: 1,
		perPage: parseInt( l10n.defaults.protocolPerPage, 10 ) || 10,
		search: '',
		total: 0,
		totalPages: 1,
	};

	const formState = {
		mode: 'create',
		submitting: false,
		previewObjectUrl: '',
	};

	bindEvents();

	if ( permissions.canView ) {
		loadList();
	}

	applyBootstrap();

	function bindEvents() {
		window.addEventListener( 'scroll', handleViewportDropdownClose, true );
		window.addEventListener( 'resize', handleViewportDropdownClose );

		$( document ).on( 'click', function ( event ) {
			if ( ! $( event.target ).closest( '.fstu-member-card-applications-dropdown' ).length ) {
				closeDropdowns();
			}
		} );

		$( document ).on( 'click', '.fstu-member-card-applications-dropdown__toggle', function ( event ) {
			event.preventDefault();
			event.stopPropagation();

			const $dropdown = $( this ).closest( '.fstu-member-card-applications-dropdown' );
			const isOpen = $dropdown.hasClass( 'fstu-dropdown--open' );
			closeDropdowns();

			if ( ! isOpen ) {
				$dropdown.addClass( 'fstu-dropdown--open' );
				$( this ).attr( 'aria-expanded', 'true' );
				positionDropdownMenu( $dropdown );
			}
		} );

		$( document ).on( 'click', '#fstu-member-card-applications-add-btn', function () {
			openCreateForm();
		} );

		$( document ).on( 'click', '#fstu-member-card-applications-self-create-btn', function () {
			openCreateForm();
		} );

		$( document ).on( 'click', '#fstu-member-card-applications-self-view-btn', function () {
			openViewModal( parseInt( $( this ).data( 'member-card-id' ), 10 ) || getBootstrapMemberCardId() );
		} );

		$( document ).on( 'click', '#fstu-member-card-applications-self-reissue-btn', function () {
			openReissueForm( parseInt( $( this ).data( 'member-card-id' ), 10 ) || getBootstrapMemberCardId() );
		} );

		$( document ).on( 'click', '#fstu-member-card-applications-self-photo-btn', function () {
			openPhotoForm( parseInt( $( this ).data( 'member-card-id' ), 10 ) || getBootstrapMemberCardId() );
		} );

		$( document ).on( 'submit', '#fstu-member-card-applications-form', function ( event ) {
			event.preventDefault();
			submitForm();
		} );

		$( document ).on( 'change', '#fstu-member-card-applications-photo-file', function () {
			handlePhotoSelection( this );
		} );

		$( document ).on( 'input change', '#fstu-member-card-applications-user-id-manual', function () {
			const userId = parseInt( $( this ).val(), 10 ) || 0;
			setFieldValue( 'user_id', userId );
		} );

		$( document ).on( 'input', '#fstu-member-card-applications-search', debounce( function () {
			listState.search = String( $( this ).val() || '' ).trim();
			listState.page = 1;
			loadList();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-member-card-applications-region-filter', function () {
			listState.regionId = parseInt( $( this ).val(), 10 ) || 0;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'change', '#fstu-member-card-applications-status-filter', function () {
			listState.statusId = parseInt( $( this ).val(), 10 ) || 0;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'change', '#fstu-member-card-applications-type-filter', function () {
			listState.typeId = parseInt( $( this ).val(), 10 ) || 0;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'change', '#fstu-member-card-applications-per-page', function () {
			listState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			listState.page = 1;
			loadList();
		} );

		$( document ).on( 'click', '.fstu-member-card-applications-page-btn', function () {
			listState.page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
			loadList();
		} );

		$( document ).on( 'click', '.fstu-member-card-applications-view-btn', function () {
			closeDropdowns();
			openViewModal( parseInt( $( this ).data( 'member-card-id' ), 10 ) || 0 );
		} );

		$( document ).on( 'click', '.fstu-member-card-applications-edit-btn', function () {
			closeDropdowns();
			openEditForm( parseInt( $( this ).data( 'member-card-id' ), 10 ) || 0 );
		} );

		$( document ).on( 'click', '.fstu-member-card-applications-reissue-btn', function () {
			closeDropdowns();
			openReissueForm( parseInt( $( this ).data( 'member-card-id' ), 10 ) || 0 );
		} );

		$( document ).on( 'click', '.fstu-member-card-applications-delete-btn', function () {
			closeDropdowns();
			confirmDelete( parseInt( $( this ).data( 'member-card-id' ), 10 ) || 0 );
		} );

		$( document ).on( 'click', '#fstu-member-card-applications-protocol-btn', function () {
			$( '#fstu-member-card-applications-main' ).addClass( 'fstu-hidden' );
			$( '#fstu-member-card-applications-protocol' ).removeClass( 'fstu-hidden' );
			$( '#fstu-member-card-applications-protocol-btn' ).addClass( 'fstu-hidden' );
			$( '#fstu-member-card-applications-protocol-back-btn' ).removeClass( 'fstu-hidden' );
			protocolState.page = 1;
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-member-card-applications-protocol-back-btn', function () {
			$( '#fstu-member-card-applications-main' ).removeClass( 'fstu-hidden' );
			$( '#fstu-member-card-applications-protocol' ).addClass( 'fstu-hidden' );
			$( '#fstu-member-card-applications-protocol-btn' ).removeClass( 'fstu-hidden' );
			$( '#fstu-member-card-applications-protocol-back-btn' ).addClass( 'fstu-hidden' );
		} );

		$( document ).on( 'input', '#fstu-member-card-applications-protocol-search', debounce( function () {
			protocolState.search = String( $( this ).val() || '' ).trim();
			protocolState.page = 1;
			loadProtocol();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-member-card-applications-protocol-per-page', function () {
			protocolState.perPage = parseInt( $( this ).val(), 10 ) || 10;
			protocolState.page = 1;
			loadProtocol();
		} );

		$( document ).on( 'click', '.fstu-member-card-applications-protocol-page-btn', function () {
			protocolState.page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
			loadProtocol();
		} );

		$( document ).on( 'click', '[data-modal-close="view"]', function () {
			closeModal( '#fstu-member-card-applications-view-modal' );
		} );

		$( document ).on( 'click', '[data-modal-close="form"]', function () {
			closeModal( '#fstu-member-card-applications-form-modal' );
		} );
	}

	function applyBootstrap() {
		if ( ! l10n.bootstrap || ! l10n.bootstrap.autoOpen ) {
			return;
		}

		if ( l10n.bootstrap.userDefaults ) {
			$.extend( currentUser, l10n.bootstrap.userDefaults );
		}

		if ( l10n.bootstrap.userId ) {
			currentUser.userId = parseInt( l10n.bootstrap.userId, 10 ) || currentUser.userId || 0;
		}

		const bootstrapAction = String( l10n.bootstrap.action || '' );
		const bootstrapMemberCardId = getBootstrapMemberCardId();

		switch ( bootstrapAction ) {
			case 'view':
				if ( bootstrapMemberCardId > 0 ) {
					openViewModal( bootstrapMemberCardId );
				} else {
					openCreateForm();
				}
				break;
			case 'reissue':
				if ( bootstrapMemberCardId > 0 ) {
					openReissueForm( bootstrapMemberCardId );
				} else {
					openCreateForm();
				}
				break;
			case 'photo':
				if ( bootstrapMemberCardId > 0 ) {
					openPhotoForm( bootstrapMemberCardId );
				} else {
					openCreateForm();
				}
				break;
			case 'create':
			default:
				openCreateForm();
				break;
		}
	}

	function openCreateForm() {
		resetForm();
		formState.mode = 'create';
		setFieldValue( 'mode', 'create' );
		setFormTitle( 'create' );
		fillFormFromCurrentUser();
		updateFormModeUi( 'create' );
		openModal( '#fstu-member-card-applications-form-modal' );
	}

	function openEditForm( memberCardId ) {
		if ( memberCardId <= 0 ) {
			return;
		}

		loadSingle( memberCardId, function ( item ) {
			resetForm();
			formState.mode = 'update';
			setFieldValue( 'mode', 'update' );
			setFormTitle( 'update' );
			fillForm( item );
			updateFormModeUi( 'update' );
			openModal( '#fstu-member-card-applications-form-modal' );
		} );
	}

	function openReissueForm( memberCardId ) {
		if ( memberCardId <= 0 ) {
			return;
		}

		loadSingle( memberCardId, function ( item ) {
			resetForm();
			formState.mode = 'reissue';
			setFieldValue( 'mode', 'reissue' );
			setFormTitle( 'reissue' );
			fillForm( item );
			updateFormModeUi( 'reissue' );
			openModal( '#fstu-member-card-applications-form-modal' );
		} );
	}

	function openPhotoForm( memberCardId ) {
		if ( memberCardId <= 0 ) {
			return;
		}

		loadSingle( memberCardId, function ( item ) {
			resetForm();
			formState.mode = 'photo';
			setFieldValue( 'mode', 'photo' );
			setFormTitle( 'photo' );
			fillForm( item );
			updateFormModeUi( 'photo' );
			openModal( '#fstu-member-card-applications-form-modal' );
		} );
	}

	function openViewModal( memberCardId ) {
		if ( memberCardId <= 0 ) {
			return;
		}

		$( '#fstu-member-card-applications-view-body' ).html( '<p>' + escHtml( l10n.messages.loading ) + '</p>' );
		openModal( '#fstu-member-card-applications-view-modal' );

		loadSingle( memberCardId, function ( item ) {
			$( '#fstu-member-card-applications-view-body' ).html( buildViewHtml( item ) );
		} );
	}

	function loadSingle( memberCardId, callback ) {
		$.post( l10n.ajaxUrl, {
			action: l10n.actions.getSingle,
			nonce: l10n.nonce,
			member_card_id: memberCardId,
		} ).done( function ( response ) {
			if ( ! response || ! response.success || ! response.data || ! response.data.item ) {
				showTopMessage( extractErrorMessage( response, l10n.messages.viewError ), 'error' );
				return;
			}

			callback( response.data.item );
		} ).fail( function () {
			showTopMessage( l10n.messages.viewError, 'error' );
		} );
	}

	function loadList() {
		if ( ! permissions.canView ) {
			return;
		}

		$( '#fstu-member-card-applications-tbody' ).html( '<tr class="fstu-row"><td colspan="8" class="fstu-no-results">' + escHtml( l10n.messages.loading ) + '</td></tr>' );

		$.post( l10n.ajaxUrl, {
			action: l10n.actions.getList,
			nonce: l10n.nonce,
			search: listState.search,
			page: listState.page,
			per_page: listState.perPage,
			region_id: listState.regionId,
			status_id: listState.statusId,
			type_id: listState.typeId,
		} ).done( function ( response ) {
			if ( ! response || ! response.success || ! response.data ) {
				showTopMessage( extractErrorMessage( response, l10n.messages.error ), 'error' );
				return;
			}

			$( '#fstu-member-card-applications-tbody' ).html( response.data.html || '' );
			listState.total = parseInt( response.data.total, 10 ) || 0;
			listState.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
			renderListPagination();
		} ).fail( function () {
			showTopMessage( l10n.messages.error, 'error' );
		} );
	}

	function loadProtocol() {
		$( '#fstu-member-card-applications-protocol-tbody' ).html( '<tr class="fstu-row"><td colspan="6" class="fstu-no-results">' + escHtml( l10n.messages.loading ) + '</td></tr>' );

		$.post( l10n.ajaxUrl, {
			action: l10n.actions.getProtocol,
			nonce: l10n.nonce,
			search: protocolState.search,
			page: protocolState.page,
			per_page: protocolState.perPage,
		} ).done( function ( response ) {
			if ( ! response || ! response.success || ! response.data ) {
				showTopMessage( extractErrorMessage( response, l10n.messages.protocolError ), 'error' );
				return;
			}

			$( '#fstu-member-card-applications-protocol-tbody' ).html( response.data.html || '' );
			protocolState.total = parseInt( response.data.total, 10 ) || 0;
			protocolState.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
			renderProtocolPagination();
		} ).fail( function () {
			showTopMessage( l10n.messages.protocolError, 'error' );
		} );
	}

	function renderListPagination() {
		$( '#fstu-member-card-applications-pagination-pages' ).html( buildPaginationButtons( listState.page, listState.totalPages, 'fstu-member-card-applications-page-btn' ) );
		$( '#fstu-member-card-applications-pagination-info' ).text( 'Записів: ' + listState.total + ' | Сторінка ' + listState.page + ' з ' + Math.max( 1, listState.totalPages ) );
	}

	function renderProtocolPagination() {
		$( '#fstu-member-card-applications-protocol-pagination-pages' ).html( buildPaginationButtons( protocolState.page, protocolState.totalPages, 'fstu-member-card-applications-protocol-page-btn' ) );
		$( '#fstu-member-card-applications-protocol-pagination-info' ).text( 'Записів: ' + protocolState.total + ' | Сторінка ' + protocolState.page + ' з ' + Math.max( 1, protocolState.totalPages ) );
	}

	function buildPaginationButtons( currentPage, totalPages, buttonClass ) {
		if ( totalPages <= 1 ) {
			return '';
		}

		let html = '';
		const start = Math.max( 1, currentPage - 2 );
		const end = Math.min( totalPages, currentPage + 2 );

		for ( let page = start; page <= end; page++ ) {
			html += '<button type="button" class="fstu-btn--page ' + buttonClass + ( page === currentPage ? ' fstu-btn--page-active' : '' ) + '" data-page="' + page + '">' + page + '</button>';
		}

		return html;
	}

	function fillFormFromCurrentUser() {
		const isManageModeWithoutBootstrap = !! permissions.canManage && ! getBootstrapUserId();
		const userId = isManageModeWithoutBootstrap ? 0 : ( parseInt( currentUser.userId, 10 ) || 0 );

		setFieldValue( 'member_card_id', 0 );
		setFieldValue( 'user_id', userId );
		setManualUserIdValue( userId );
		setFieldValue( 'last_name', isManageModeWithoutBootstrap ? '' : ( currentUser.lastName || '' ) );
		setFieldValue( 'first_name', isManageModeWithoutBootstrap ? '' : ( currentUser.firstName || '' ) );
		setFieldValue( 'patronymic', isManageModeWithoutBootstrap ? '' : ( currentUser.patronymic || '' ) );
		setFieldValue( 'birth_date', isManageModeWithoutBootstrap ? '' : ( currentUser.birthDate || '' ) );
		setFieldValue( 'user_email', isManageModeWithoutBootstrap ? '' : ( currentUser.email || '' ) );
		setFieldValue( 'phone_mobile', isManageModeWithoutBootstrap ? '' : ( currentUser.phoneMobile || '' ) );
		setFieldValue( 'phone_2', isManageModeWithoutBootstrap ? '' : ( currentUser.phone2 || '' ) );
		setFieldValue( 'card_number', '' );
		setFieldValue( 'summa', '' );
		setFieldValue( 'number_np', '' );
		renderFormPhotoPanel( {
			photo_url: currentUser.photoUrl || '',
			has_photo: !! currentUser.photoUrl,
		} );
		updateCardNumberReadonly();
	}

	function fillForm( item ) {
		setFieldValue( 'member_card_id', item.UserMemberCard_ID || 0 );
		setFieldValue( 'user_id', item.User_ID || 0 );
		setManualUserIdValue( item.User_ID || 0 );
		setFieldValue( 'last_name', item.UserMemberCard_LastName || '' );
		setFieldValue( 'first_name', item.UserMemberCard_FirstName || '' );
		setFieldValue( 'patronymic', item.UserMemberCard_Patronymic || '' );
		setFieldValue( 'last_name_eng', item.UserMemberCard_LastNameEng || '' );
		setFieldValue( 'first_name_eng', item.UserMemberCard_FirstNameEng || '' );
		setFieldValue( 'birth_date', item.birth_date || '' );
		setFieldValue( 'user_email', item.user_email || '' );
		setFieldValue( 'phone_mobile', item.phone_mobile || '' );
		setFieldValue( 'phone_2', item.phone_2 || '' );
		setFieldValue( 'region_id', item.Region_ID || 0 );
		setFieldValue( 'status_card_id', item.StatusCard_ID || 0 );
		setFieldValue( 'type_card_id', item.TypeCard_ID || 0 );
		setFieldValue( 'card_number', item.UserMemberCard_Number || '' );
		setFieldValue( 'summa', item.UserMemberCard_Summa || '' );
		setFieldValue( 'number_np', item.UserMemberCard_NumberNP || '' );
		$( '#fstu-member-card-applications-photo-file' ).val( '' );
		renderFormPhotoPanel( item );
		updateCardNumberReadonly();
	}

	function updateCardNumberReadonly() {
		$( '#fstu-member-card-applications-card-number' ).prop( 'readonly', ! permissions.canManageCardNumber );
	}

	function submitForm() {
		if ( formState.submitting || ! $form.length ) {
			return;
		}

		formState.submitting = true;
		setSubmitState( true );

		const actionName = resolveActionName();
		const formData = new window.FormData( $form.get( 0 ) );
		formData.set( 'action', actionName );
		formData.set( 'nonce', l10n.nonce );

		$.ajax( {
			url: l10n.ajaxUrl,
			method: 'POST',
			data: formData,
			processData: false,
			contentType: false,
		} ).done( function ( response ) {
			if ( ! response || ! response.success || ! response.data ) {
				showTopMessage( extractErrorMessage( response, l10n.messages.error ), 'error' );
				return;
			}

			const memberCardId = parseInt( response.data.member_card_id, 10 ) || parseInt( $( '#fstu-member-card-applications-member-card-id' ).val(), 10 ) || 0;
			const photoField = $( '#fstu-member-card-applications-photo-file' ).get( 0 );
			const hasPhoto = photoField && photoField.files && photoField.files.length > 0;
			const isPhotoOnlyMode = actionName === l10n.actions.updatePhoto;

			const finishSuccess = function () {
				closeModal( '#fstu-member-card-applications-form-modal' );
				handlePostSuccess( response.data.message || 'OK' );
				if ( permissions.canView && ! shouldRedirectAfterSuccess() ) {
					loadList();
				}
			};

			if ( ! isPhotoOnlyMode && hasPhoto && permissions.canUpdatePhoto && memberCardId > 0 ) {
				uploadPhoto( memberCardId, photoField.files[0], finishSuccess );
				return;
			}

			finishSuccess();
		} ).fail( function () {
			showTopMessage( l10n.messages.error, 'error' );
		} ).always( function () {
			formState.submitting = false;
			setSubmitState( false );
		} );
	}

	function uploadPhoto( memberCardId, file, onSuccess ) {
		const photoData = new window.FormData();
		photoData.append( 'action', l10n.actions.updatePhoto );
		photoData.append( 'nonce', l10n.nonce );
		photoData.append( 'member_card_id', memberCardId );
		photoData.append( 'fstu_website', '' );
		photoData.append( 'photo_file', file );

		$.ajax( {
			url: l10n.ajaxUrl,
			method: 'POST',
			data: photoData,
			processData: false,
			contentType: false,
		} ).done( function ( response ) {
			if ( ! response || ! response.success || ! response.data ) {
				showTopMessage( extractErrorMessage( response, l10n.messages.error ), 'error' );
				return;
			}

			onSuccess();
		} ).fail( function () {
			showTopMessage( l10n.messages.error, 'error' );
		} );
	}

	function confirmDelete( memberCardId ) {
		if ( memberCardId <= 0 ) {
			return;
		}

		if ( ! window.confirm( 'Ви дійсно хочете видалити посвідчення?' ) ) {
			return;
		}

		$.post( l10n.ajaxUrl, {
			action: l10n.actions.delete,
			nonce: l10n.nonce,
			member_card_id: memberCardId,
		} ).done( function ( response ) {
			if ( ! response || ! response.success || ! response.data ) {
				showTopMessage( extractErrorMessage( response, l10n.messages.error ), 'error' );
				return;
			}

			showTopMessage( response.data.message || 'OK', 'success' );
			loadList();
		} ).fail( function () {
			showTopMessage( l10n.messages.error, 'error' );
		} );
	}

	function buildViewHtml( item ) {
		const photoHtml = buildPhotoViewHtml( item );
		const rows = [
			[ 'ПІБ', escHtml( item.FIO || '—' ) ],
			[ '№ картки', escHtml( item.CardNumber || item.UserMemberCard_Number || '—' ) ],
			[ 'Регіон', escHtml( item.Region_Name || '—' ) ],
			[ 'Статус', escHtml( item.StatusCard_Name || '—' ) ],
			[ 'Тип', escHtml( item.TypeCard_Name || '—' ) ],
			[ 'Дата заявки', escHtml( item.UserMemberCard_DateCreate || '—' ) ],
			[ 'Прізвище', escHtml( item.UserMemberCard_LastName || '—' ) ],
			[ 'Ім’я', escHtml( item.UserMemberCard_FirstName || '—' ) ],
			[ 'По батькові', escHtml( item.UserMemberCard_Patronymic || '—' ) ],
			[ 'Прізвище (ENG)', escHtml( item.UserMemberCard_LastNameEng || '—' ) ],
			[ 'Ім’я (ENG)', escHtml( item.UserMemberCard_FirstNameEng || '—' ) ],
			[ 'Email', escHtml( item.user_email || '—' ) ],
			[ 'Мобільний', escHtml( item.phone_mobile || '—' ) ],
			[ 'Додатковий телефон', escHtml( item.phone_2 || '—' ) ],
			[ 'Дата народження', escHtml( item.birth_date || '—' ) ],
			[ 'Номер НП / примітка', escHtml( item.UserMemberCard_NumberNP || '—' ) ],
			[ 'Сума', escHtml( item.UserMemberCard_Summa || '—' ) ],
			[ 'Фото', photoHtml ],
		];

		let html = '<div class="fstu-view-grid">';
		rows.forEach( function ( row ) {
			html += '<div class="fstu-view-grid__row"><div class="fstu-view-grid__label">' + escHtml( row[0] ) + '</div><div class="fstu-view-grid__value">' + row[1] + '</div></div>';
		} );
		html += '</div>';

		return html;
	}

	function resolveActionName() {
		switch ( formState.mode ) {
			case 'photo':
				return l10n.actions.updatePhoto;
			case 'update':
				return l10n.actions.update;
			case 'reissue':
				return l10n.actions.reissue;
			default:
				return l10n.actions.create;
		}
	}

	function setFormTitle( mode ) {
		const $title = $( '#fstu-member-card-applications-form-title' );
		const $submit = $( '#fstu-member-card-applications-submit-btn' );
		setFormNote( '' );

		if ( mode === 'photo' ) {
			$title.text( 'Оновлення фото посвідчення члена ФСТУ' );
			$submit.text( 'ОНОВИТИ ФОТО' );
			setFormNote( 'У цьому режимі доступне лише завантаження нового фото посвідчення. Інші поля тимчасово приховані.' );
			return;
		}

		if ( mode === 'reissue' ) {
			$title.text( 'Перевипуск посвідчення члена ФСТУ' );
			$submit.text( 'ПЕРЕВИПУСТИТИ' );
			setFormNote( 'Перевипуск створює новий запис посвідчення на основі поточних даних користувача.' );
			return;
		}

		if ( mode === 'update' ) {
			$title.text( 'Редагування посвідчення члена ФСТУ' );
			$submit.text( 'ОНОВИТИ' );
			return;
		}

		$title.text( 'Створення посвідчення члена ФСТУ' );
		$submit.text( 'СТВОРИТИ' );
		if ( permissions.canManage && ! getBootstrapUserId() ) {
			setFormNote( 'Для службового створення вкажіть User ID користувача або відкрийте форму напряму з реєстру членів ФСТУ.' );
		}
	}

	function setSubmitState( isSubmitting ) {
		$( '#fstu-member-card-applications-submit-btn' ).prop( 'disabled', isSubmitting );
	}

	function resetForm() {
		clearPreviewObjectUrl();
		if ( $form.length && $form.get( 0 ) ) {
			$form.get( 0 ).reset();
		}
		$form.removeClass( 'is-photo-mode' );
		$form.find( '.fstu-form__field' ).removeClass( 'fstu-hidden' );
		$form.find( '.fstu-form__field input, .fstu-form__field select, .fstu-form__field textarea' ).prop( 'disabled', false );
		setFieldValue( 'mode', 'create' );
		setFieldValue( 'member_card_id', 0 );
		setFieldValue( 'user_id', 0 );
		setManualUserIdValue( 0 );
		setFormNote( '' );
		resetFormPhotoPanel();
	}

	function updateFormModeUi( mode ) {
		const $fields = $form.find( '.fstu-form__field' );
		const $photoField = $( '#fstu-member-card-applications-photo-file' ).closest( '.fstu-form__field' );
		setFieldValue( 'mode', mode );

		$form.removeClass( 'is-photo-mode' );
		$fields.removeClass( 'fstu-hidden' );
		$fields.find( 'input, select, textarea' ).prop( 'disabled', false );

		if ( mode === 'photo' ) {
			$form.addClass( 'is-photo-mode' );
			$fields.not( $photoField ).addClass( 'fstu-hidden' );
			$fields.not( $photoField ).find( 'input, select, textarea' ).prop( 'disabled', true );
		}

		updateCardNumberReadonly();
	}

	function setFormNote( note ) {
		const $note = $( '#fstu-member-card-applications-form-note' );
		if ( ! $note.length ) {
			return;
		}

		if ( note ) {
			$note.removeClass( 'fstu-hidden' ).text( note );
			return;
		}

		$note.addClass( 'fstu-hidden' ).text( '' );
	}

	function buildPhotoViewHtml( item ) {
		const photoUrl = getPhotoUrl( item );

		if ( ! photoUrl ) {
			return '<span class="fstu-member-card-applications-photo-empty">Фото відсутнє.</span>';
		}

		return '<div class="fstu-member-card-applications-photo-view">'
			+ '<a class="fstu-member-card-applications-photo-view__preview" href="' + escAttr( photoUrl ) + '" target="_blank" rel="noopener noreferrer">'
			+ '<img class="fstu-member-card-applications-photo-view__image" src="' + escAttr( photoUrl ) + '" alt="' + escAttr( item.FIO || 'Фото посвідчення' ) + '">'
			+ '</a>'
			+ '<a class="fstu-link-button fstu-member-card-applications-photo-view__link" href="' + escAttr( photoUrl ) + '" target="_blank" rel="noopener noreferrer">Відкрити фото</a>'
			+ '</div>';
	}

	function renderFormPhotoPanel( item, options ) {
		const settings = options || {};
		const photoUrl = settings.previewUrl || getPhotoUrl( item );
		const hasPhoto = !! photoUrl;
		const $panel = $( '#fstu-member-card-applications-photo-panel' );
		const $preview = $( '#fstu-member-card-applications-photo-preview' );
		const $status = $( '#fstu-member-card-applications-photo-status' );
		const $link = $( '#fstu-member-card-applications-photo-link' );

		if ( ! $panel.length ) {
			return;
		}

		if ( ! hasPhoto ) {
			$panel.removeClass( 'fstu-hidden' ).addClass( 'is-empty' );
			if ( ! settings.previewUrl ) {
				$panel.attr( 'data-saved-photo-url', '' );
			}
			$preview.attr( 'src', '' );
			$status.text( settings.emptyMessage || 'Фото ще не завантажено.' );
			$link.addClass( 'fstu-hidden' ).attr( 'href', '#' );
			return;
		}

		$panel.removeClass( 'fstu-hidden is-empty' );
		if ( ! settings.previewUrl ) {
			$panel.attr( 'data-saved-photo-url', photoUrl );
		}
		$preview.attr( 'src', photoUrl );
		$status.text( settings.statusText || 'Поточне завантажене фото.' );
		$link.removeClass( 'fstu-hidden' ).attr( 'href', photoUrl );
	}

	function resetFormPhotoPanel() {
		renderFormPhotoPanel( null, { emptyMessage: 'Фото ще не завантажено.' } );
	}

	function handlePhotoSelection( input ) {
		const file = input && input.files && input.files.length ? input.files[0] : null;
		const savedPhotoUrl = $( '#fstu-member-card-applications-photo-panel' ).attr( 'data-saved-photo-url' ) || '';

		if ( ! file ) {
			renderFormPhotoPanel( {
				photo_url: savedPhotoUrl,
			} );
			return;
		}

		clearPreviewObjectUrl();
		formState.previewObjectUrl = window.URL.createObjectURL( file );
		renderFormPhotoPanel( null, {
			previewUrl: formState.previewObjectUrl,
			statusText: 'Обране нове фото перед збереженням.',
		} );
	}

	function clearPreviewObjectUrl() {
		if ( formState.previewObjectUrl ) {
			window.URL.revokeObjectURL( formState.previewObjectUrl );
			formState.previewObjectUrl = '';
		}
	}

	function getPhotoUrl( item ) {
		if ( item && item.photo_url ) {
			return String( item.photo_url );
		}

		return '';
	}

	function getBootstrapMemberCardId() {
		return parseInt( ( l10n.bootstrap && l10n.bootstrap.memberCardId ) || 0, 10 ) || 0;
	}

	function getBootstrapUserId() {
		return parseInt( ( l10n.bootstrap && l10n.bootstrap.userId ) || 0, 10 ) || 0;
	}

	function setManualUserIdValue( value ) {
		const $manualField = $( '#fstu-member-card-applications-user-id-manual' );
		if ( $manualField.length ) {
			$manualField.val( value || '' );
		}
	}

	function setFieldValue( name, value ) {
		const $field = $form.find( '[name="' + name + '"]' );
		if ( ! $field.length ) {
			return;
		}

		$field.val( value );
		$field.trigger( 'change' );
	}

	function openModal( selector ) {
		$( selector ).removeClass( 'fstu-hidden' ).attr( 'aria-hidden', 'false' );
		$( 'body' ).addClass( 'fstu-modal-open' );
	}

	function closeModal( selector ) {
		$( selector ).addClass( 'fstu-hidden' ).attr( 'aria-hidden', 'true' );
		if ( ! $( '.fstu-modal:not(.fstu-hidden)' ).length ) {
			$( 'body' ).removeClass( 'fstu-modal-open' );
		}
	}

	function closeDropdowns() {
		$( '.fstu-member-card-applications-dropdown' ).removeClass( 'fstu-dropdown--open fstu-dropdown--up' );
		$( '.fstu-member-card-applications-dropdown__toggle' ).attr( 'aria-expanded', 'false' );
		$( '.fstu-member-card-applications-dropdown__menu' ).css( { top: '', left: '' } );
	}

	function positionDropdownMenu( $dropdown ) {
		const $toggle = $dropdown.find( '.fstu-member-card-applications-dropdown__toggle' );
		const $menu = $dropdown.find( '.fstu-member-card-applications-dropdown__menu' );

		if ( ! $toggle.length || ! $menu.length ) {
			return;
		}

		$dropdown.removeClass( 'fstu-dropdown--up' );
		$menu.css( { top: 0, left: 0 } );

		const rect = $toggle.get( 0 ).getBoundingClientRect();
		const menuWidth = $menu.outerWidth() || 190;
		const menuHeight = $menu.outerHeight() || 0;
		const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
		const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
		const gap = 4;
		const margin = 8;

		let left = rect.right - menuWidth;
		left = Math.max( margin, Math.min( left, Math.max( margin, viewportWidth - menuWidth - margin ) ) );

		let top = rect.bottom + gap;
		if ( top + menuHeight > viewportHeight - margin && rect.top - menuHeight - gap >= margin ) {
			top = rect.top - menuHeight - gap;
			$dropdown.addClass( 'fstu-dropdown--up' );
		}

		$menu.css( {
			top: Math.max( margin, top ),
			left: left,
		} );
	}

	function handleViewportDropdownClose() {
		closeDropdowns();
	}

	function handlePostSuccess( message ) {
		const shouldRedirect = shouldRedirectAfterSuccess();
		showTopMessage( shouldRedirect ? ( l10n.messages.redirecting || message || 'OK' ) : ( message || 'OK' ), 'success' );

		if ( ! shouldRedirect ) {
			return;
		}

		window.setTimeout( function () {
			window.location.href = l10n.bootstrap.returnUrl;
		}, 1200 );
	}

	function shouldRedirectAfterSuccess() {
		return !! ( l10n.bootstrap && l10n.bootstrap.postSuccessAction === 'redirect' && l10n.bootstrap.returnUrl );
	}

	function showTopMessage( message, type ) {
		const $notice = $( '#fstu-member-card-applications-notice' );
		$notice.removeClass( 'fstu-hidden fstu-member-card-applications-notice--success fstu-member-card-applications-notice--error' );
		$notice.addClass( type === 'error' ? 'fstu-member-card-applications-notice--error' : 'fstu-member-card-applications-notice--success' );
		$notice.text( message || '' );
	}

	function extractErrorMessage( response, fallback ) {
		if ( response && response.data && response.data.message ) {
			return response.data.message;
		}

		return fallback || 'Помилка';
	}

	function debounce( callback, delay ) {
		let timer = null;
		return function () {
			const args = arguments;
			const context = this;
			window.clearTimeout( timer );
			timer = window.setTimeout( function () {
				callback.apply( context, args );
			}, delay );
		};
	}

	function escHtml( value ) {
		return $( '<div />' ).text( value || '' ).html();
	}

	function escAttr( value ) {
		return escHtml( value );
	}
} );

