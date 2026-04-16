/**
 * JS модуля «Склад керівних органів ФСТУ».
 *
 * Version:     1.1.1
 * Date_update: 2026-04-12
 */

jQuery( document ).ready( function ( $ ) {
	'use strict';

	if ( 'undefined' === typeof window.fstuGuidanceL10n || ! $( '#fstu-guidance' ).length ) {
		return;
	}

	var listState = {
		page: 1,
		perPage: parseInt( fstuGuidanceL10n.defaults.perPage, 10 ) || 10,
		search: '',
		typeguidanceId: parseInt( fstuGuidanceL10n.defaults.typeguidanceId, 10 ) || 1,
	};
	var protocolState = {
		page: 1,
		perPage: parseInt( fstuGuidanceL10n.defaults.protocolPerPage, 10 ) || 10,
		search: '',
	};
	var $typeguidanceFilter = $( '#fstu-guidance-typeguidance-filter' );
	var $typeguidanceField = $( '#fstu-guidance-typeguidance-id' );
	var $listTbody = $( '#fstu-guidance-tbody' );
	var $protocolTbody = $( '#fstu-guidance-protocol-tbody' );
	var dropdownOpen = null;
	var autocompleteRequest = null;
	var deletingIds = [];

	function escHtml( text ) {
		return String( text || '' )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#039;' );
	}

	function debounce( callback, delay ) {
		var timer = null;
		return function () {
			var args = arguments;
			var context = this;
			clearTimeout( timer );
			timer = setTimeout( function () {
				callback.apply( context, args );
			}, delay );
		};
	}

	function getAjaxMessage( response, fallback ) {
		if ( response && response.data && response.data.message ) {
			return response.data.message;
		}
		return fallback;
	}

	function showPageMessage( message, isError ) {
		var $message = $( '#fstu-guidance-page-message' );

		if ( ! $message.length ) {
			return;
		}

		$message
			.text( message || '' )
			.removeClass( 'fstu-hidden fstu-page-message--success fstu-page-message--error' )
			.addClass( isError ? 'fstu-page-message--error' : 'fstu-page-message--success' );
	}

	function clearPageMessage() {
		$( '#fstu-guidance-page-message' )
			.addClass( 'fstu-hidden' )
			.removeClass( 'fstu-page-message--success fstu-page-message--error' )
			.text( '' );
	}

	function renderPagination( state, totalPages, controlsId, infoId, prevId, nextId, total ) {
		var pagesHtml = '';
		var page;
		var $controls = $( controlsId );
		var end = Math.min( totalPages, Math.max( 5, state.page + 2 ) );
		var start = Math.max( 1, end - 4 );

		for ( page = start; page <= end; page++ ) {
			pagesHtml += '<button type="button" class="fstu-btn--page' + ( page === state.page ? ' is-active' : '' ) + '" data-page="' + page + '">' + page + '</button>';
		}

		$controls.html( pagesHtml );
		$( infoId ).text( 'Записів: ' + total + ' | Сторінка ' + state.page + ' з ' + totalPages );
		$( prevId ).prop( 'disabled', state.page <= 1 );
		$( nextId ).prop( 'disabled', state.page >= totalPages );
	}

	function loadFilters( callback ) {
		$.ajax( {
			url: fstuGuidanceL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuGuidanceL10n.actions.getFilters,
				nonce: fstuGuidanceL10n.nonce,
			},
		} ).done( function ( response ) {
			if ( ! response.success ) {
				return;
			}

			populateTypeGuidanceOptions( response.data.typeguidance || [] );
			populateMemberGuidanceOptions( response.data.memberGuidance || [] );
		} ).always( function () {
			if ( callback ) {
				callback();
			}
		} );
	}

	function populateTypeGuidanceOptions( items ) {
		var html = '';
		var hasSelected = false;
		var fallbackId = 1;
		$.each( items, function ( index, item ) {
			var id = parseInt( item.TypeGuidance_ID || item.id || 0, 10 );
			var name = item.TypeGuidance_Name || item.name || '';
			if ( 0 === index && id > 0 ) {
				fallbackId = id;
			}
			if ( id === listState.typeguidanceId ) {
				hasSelected = true;
			}
			html += '<option value="' + id + '">' + escHtml( name ) + '</option>';
		} );

		if ( '' === html ) {
			html = '<option value="1">Виконком</option>';
			fallbackId = 1;
			hasSelected = true;
		}

		if ( ! hasSelected ) {
			listState.typeguidanceId = fallbackId;
		}

		$typeguidanceFilter.html( html );
		$typeguidanceField.html( html );
		$typeguidanceFilter.val( String( listState.typeguidanceId ) );
		$typeguidanceField.val( String( listState.typeguidanceId ) );
	}

	function populateMemberGuidanceOptions( items ) {
		var html = '<option value="0">Оберіть посаду</option>';
		$.each( items, function ( index, item ) {
			var id = parseInt( item.MemberGuidance_ID || item.id || 0, 10 );
			var name = item.MemberGuidance_Name || item.name || '';
			html += '<option value="' + id + '">' + escHtml( name ) + '</option>';
		} );
		$( '#fstu-guidance-member-guidance-id' ).html( html );
	}

	function loadMemberGuidanceOptions( typeguidanceId, callback ) {
		$.ajax( {
			url: fstuGuidanceL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuGuidanceL10n.actions.getMemberGuidanceOptions,
				nonce: fstuGuidanceL10n.nonce,
				typeguidance_id: typeguidanceId,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				populateMemberGuidanceOptions( response.data.items || [] );
				if ( callback ) {
					callback();
				}
			}
		} );
	}

	function loadList() {
		$listTbody.html( '<tr class="fstu-row"><td colspan="' + fstuGuidanceL10n.table.colspan + '" class="fstu-no-results">' + escHtml( fstuGuidanceL10n.messages.loading ) + '</td></tr>' );

		$.ajax( {
			url: fstuGuidanceL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuGuidanceL10n.actions.getList,
				nonce: fstuGuidanceL10n.nonce,
				search: listState.search,
				page: listState.page,
				per_page: listState.perPage,
				typeguidance_id: listState.typeguidanceId,
			},
		} ).done( function ( response ) {
			if ( ! response.success ) {
				$listTbody.html( '<tr class="fstu-row"><td colspan="' + fstuGuidanceL10n.table.colspan + '" class="fstu-no-results">' + escHtml( getAjaxMessage( response, fstuGuidanceL10n.messages.error ) ) + '</td></tr>' );
				return;
			}

			listState.page = parseInt( response.data.page || listState.page, 10 ) || 1;
			listState.perPage = parseInt( response.data.per_page || listState.perPage, 10 ) || listState.perPage;

			$listTbody.html( response.data.html || '' );
			renderPagination( listState, parseInt( response.data.total_pages || 1, 10 ), '#fstu-guidance-pagination-pages', '#fstu-guidance-pagination-info', '#fstu-guidance-prev-page', '#fstu-guidance-next-page', parseInt( response.data.total || 0, 10 ) );
		} ).fail( function () {
			$listTbody.html( '<tr class="fstu-row"><td colspan="' + fstuGuidanceL10n.table.colspan + '" class="fstu-no-results">' + escHtml( fstuGuidanceL10n.messages.error ) + '</td></tr>' );
		} );
	}

	function loadProtocol() {
		$protocolTbody.html( '<tr class="fstu-row"><td colspan="' + fstuGuidanceL10n.table.protocolColspan + '" class="fstu-no-results">' + escHtml( fstuGuidanceL10n.messages.loading ) + '</td></tr>' );

		$.ajax( {
			url: fstuGuidanceL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuGuidanceL10n.actions.getProtocol,
				nonce: fstuGuidanceL10n.nonce,
				search: protocolState.search,
				page: protocolState.page,
				per_page: protocolState.perPage,
			},
		} ).done( function ( response ) {
			if ( ! response.success ) {
				$protocolTbody.html( '<tr class="fstu-row"><td colspan="' + fstuGuidanceL10n.table.protocolColspan + '" class="fstu-no-results">' + escHtml( getAjaxMessage( response, fstuGuidanceL10n.messages.protocolError ) ) + '</td></tr>' );
				return;
			}
			protocolState.page = parseInt( response.data.page || protocolState.page, 10 ) || 1;
			protocolState.perPage = parseInt( response.data.per_page || protocolState.perPage, 10 ) || protocolState.perPage;
			$protocolTbody.html( response.data.html || '' );
			renderPagination( protocolState, parseInt( response.data.total_pages || 1, 10 ), '#fstu-guidance-protocol-pagination-pages', '#fstu-guidance-protocol-pagination-info', '#fstu-guidance-protocol-prev-page', '#fstu-guidance-protocol-next-page', parseInt( response.data.total || 0, 10 ) );
		} ).fail( function () {
			$protocolTbody.html( '<tr class="fstu-row"><td colspan="' + fstuGuidanceL10n.table.protocolColspan + '" class="fstu-no-results">' + escHtml( fstuGuidanceL10n.messages.protocolError ) + '</td></tr>' );
		} );
	}

	function closeModal( modalId ) {
		$( '#' + modalId ).addClass( 'fstu-hidden' ).attr( 'aria-hidden', 'true' );
	}

	function openModal( modalId ) {
		$( '#' + modalId ).removeClass( 'fstu-hidden' ).attr( 'aria-hidden', 'false' );
	}

	function showFormMessage( message, isError ) {
		var $message = $( '#fstu-guidance-form-message' );
		$message.text( message ).removeClass( 'fstu-hidden fstu-form-message--error fstu-form-message--success' );
		$message.addClass( isError ? 'fstu-form-message--error' : 'fstu-form-message--success' );
	}

	function resetForm() {
		$( '#fstu-guidance-form' )[0].reset();
		$( '#fstu-guidance-id' ).val( '0' );
		$( '#fstu-guidance-form-mode' ).val( 'create' );
		$( '#fstu-guidance-form-message' ).addClass( 'fstu-hidden' ).text( '' );
		$( '#fstu-guidance-form-nonce' ).val( fstuGuidanceL10n.nonce );
		$( '#fstu-guidance-user-id' ).val( '0' );
		$( '#fstu-guidance-user-input' ).prop( 'readonly', false ).val( '' );
		$( '#fstu-guidance-user-results' ).addClass( 'fstu-hidden' ).empty();
		$( '#fstu-guidance-typeguidance-id' ).val( String( listState.typeguidanceId ) );
		loadMemberGuidanceOptions( listState.typeguidanceId );
	}

	function fillForm( item ) {
		$( '#fstu-guidance-id' ).val( item.Guidance_ID || 0 );
		$( '#fstu-guidance-form-mode' ).val( 'update' );
		$( '#fstu-guidance-typeguidance-id' ).val( String( item.TypeGuidance_ID || 1 ) );
		$( '#fstu-guidance-user-id' ).val( item.User_ID || 0 );
		$( '#fstu-guidance-user-input' ).val( item.FIO || '' ).prop( 'readonly', false );
		$( '#fstu-guidance-notes' ).val( item.Guidance_Notes || '' );
		loadMemberGuidanceOptions( parseInt( item.TypeGuidance_ID || 1, 10 ) || 1, function () {
			$( '#fstu-guidance-member-guidance-id' ).val( String( item.MemberGuidance_ID || 0 ) );
		} );
	}

	function openCreateModal() {
		clearPageMessage();
		resetForm();
		$( '#fstu-guidance-form-title' ).text( fstuGuidanceL10n.messages.formAddTitle );
		openModal( 'fstu-guidance-form-modal' );
	}

	function openEditModal( guidanceId ) {
		clearPageMessage();
		$.ajax( {
			url: fstuGuidanceL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuGuidanceL10n.actions.getSingle,
				nonce: fstuGuidanceL10n.nonce,
				request_context: 'edit',
				guidance_id: guidanceId,
			},
		} ).done( function ( response ) {
			if ( response.success && response.data.item ) {
				fillForm( response.data.item );
				$( '#fstu-guidance-form-title' ).text( fstuGuidanceL10n.messages.formEditTitle );
				openModal( 'fstu-guidance-form-modal' );
			} else {
				window.alert( getAjaxMessage( response, fstuGuidanceL10n.messages.error ) );
			}
		} ).fail( function () {
			window.alert( fstuGuidanceL10n.messages.error );
		} );
	}

	function buildViewHtml( item ) {
		return '' +
			'<div class="fstu-view-grid">' +
				'<div class="fstu-view-row"><span class="fstu-view-label">Керівний орган</span><span class="fstu-view-value">' + escHtml( item.TypeGuidance_Name || '' ) + '</span></div>' +
				'<div class="fstu-view-row"><span class="fstu-view-label">Посада</span><span class="fstu-view-value">' + escHtml( item.MemberGuidance_Name || '' ) + '</span></div>' +
				'<div class="fstu-view-row"><span class="fstu-view-label">ПІБ</span><span class="fstu-view-value">' + escHtml( item.FIO || '' ) + '</span></div>' +
				'<div class="fstu-view-row"><span class="fstu-view-label">Телефони</span><span class="fstu-view-value">' + escHtml( item.Phones || '' ).replace( /\n/g, '<br>' ) + '</span></div>' +
				'<div class="fstu-view-row"><span class="fstu-view-label">Email</span><span class="fstu-view-value">' + escHtml( item.user_email || '' ) + '</span></div>' +
				'<div class="fstu-view-row"><span class="fstu-view-label">Дата створення</span><span class="fstu-view-value">' + escHtml( item.Guidance_DateCreate || '' ) + '</span></div>' +
				'<div class="fstu-view-row fstu-view-row--full"><span class="fstu-view-label">Примітка</span><span class="fstu-view-value">' + escHtml( item.Guidance_Notes || '' ) + '</span></div>' +
			'</div>';
	}

	function openViewModal( guidanceId ) {
		if ( ! fstuGuidanceL10n.permissions.canViewCard ) {
			window.alert( fstuGuidanceL10n.messages.cardDenied );
			return;
		}

		clearPageMessage();

		$( '#fstu-guidance-view-body' ).html( '<p class="fstu-loader-inline">' + escHtml( fstuGuidanceL10n.messages.loading ) + '</p>' );
		$( '#fstu-guidance-view-footer' ).html( '<button type="button" class="fstu-btn fstu-btn--secondary fstu-btn--cancel" data-close-modal="fstu-guidance-view-modal">Закрити</button>' );
		openModal( 'fstu-guidance-view-modal' );

		$.ajax( {
			url: fstuGuidanceL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuGuidanceL10n.actions.getSingle,
				nonce: fstuGuidanceL10n.nonce,
				request_context: 'view',
				guidance_id: guidanceId,
			},
		} ).done( function ( response ) {
			if ( response.success && response.data.item ) {
				$( '#fstu-guidance-view-body' ).html( buildViewHtml( response.data.item ) );
				if ( fstuGuidanceL10n.permissions.canManage ) {
					$( '#fstu-guidance-view-footer' ).html( '<button type="button" class="fstu-btn fstu-btn--secondary fstu-guidance-view-edit-btn" data-guidance-id="' + guidanceId + '">Редагувати</button><button type="button" class="fstu-btn fstu-btn--secondary fstu-btn--cancel" data-close-modal="fstu-guidance-view-modal">Закрити</button>' );
				}
			} else {
				$( '#fstu-guidance-view-body' ).html( '<p class="fstu-no-results">' + escHtml( getAjaxMessage( response, fstuGuidanceL10n.messages.error ) ) + '</p>' );
			}
		} ).fail( function () {
			$( '#fstu-guidance-view-body' ).html( '<p class="fstu-no-results">' + escHtml( fstuGuidanceL10n.messages.error ) + '</p>' );
		} );
	}

	function searchUsers( query ) {
		if ( autocompleteRequest ) {
			autocompleteRequest.abort();
		}

		if ( query.length < 2 ) {
			$( '#fstu-guidance-user-results' ).addClass( 'fstu-hidden' ).empty();
			return;
		}

		autocompleteRequest = $.ajax( {
			url: fstuGuidanceL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuGuidanceL10n.actions.searchUsers,
				nonce: fstuGuidanceL10n.nonce,
				search: query,
			},
		} ).done( function ( response ) {
			var html = '';
			if ( response.success && response.data.items && response.data.items.length ) {
				$.each( response.data.items, function ( index, item ) {
					html += '<button type="button" class="fstu-autocomplete-item" data-user-id="' + parseInt( item.user_id || 0, 10 ) + '" data-user-name="' + escHtml( item.FIO || '' ) + '">' + escHtml( item.FIO || '' ) + '</button>';
				} );
			} else {
				html = '<div class="fstu-autocomplete-empty">' + escHtml( fstuGuidanceL10n.messages.userEmpty ) + '</div>';
			}
			$( '#fstu-guidance-user-results' ).removeClass( 'fstu-hidden' ).html( html );
		} );
	}

	function submitForm() {
		var mode = $( '#fstu-guidance-form-mode' ).val();
		var action = 'update' === mode ? fstuGuidanceL10n.actions.update : fstuGuidanceL10n.actions.create;
		var data = {
			action: action,
			nonce: $( '#fstu-guidance-form-nonce' ).val() || fstuGuidanceL10n.nonce,
			guidance_id: $( '#fstu-guidance-id' ).val(),
			typeguidance_id: $( '#fstu-guidance-typeguidance-id' ).val(),
			member_guidance_id: $( '#fstu-guidance-member-guidance-id' ).val(),
			user_id: $( '#fstu-guidance-user-id' ).val(),
			guidance_notes: $( '#fstu-guidance-notes' ).val(),
			fstu_website: $( '#fstu-guidance-website' ).val(),
		};

		console.log('Відправка даних:', data); // Перевір у консолі браузера (F12)

		$.ajax( {
			url: fstuGuidanceL10n.ajaxUrl,
			method: 'POST',
			data: data,
		} ).done( function ( response ) {
			if ( response.success ) {
				clearPageMessage();
				closeModal( 'fstu-guidance-form-modal' );
				showPageMessage( response.data.message || fstuGuidanceL10n.messages.saveSuccess, false );
				loadList();
			} else {
				showFormMessage( getAjaxMessage( response, fstuGuidanceL10n.messages.saveError ), true );
			}
		} ).fail( function () {
			showFormMessage( fstuGuidanceL10n.messages.saveError, true );
		} );
	}

	function deleteItem( guidanceId ) {
		if ( guidanceId <= 0 || deletingIds.indexOf( guidanceId ) !== -1 ) {
			return;
		}

		if ( ! window.confirm( fstuGuidanceL10n.messages.confirmDelete ) ) {
			return;
		}

		deletingIds.push( guidanceId );

		$.ajax( {
			url: fstuGuidanceL10n.ajaxUrl,
			method: 'POST',
			data: {
				action: fstuGuidanceL10n.actions.delete,
				nonce: fstuGuidanceL10n.nonce,
				guidance_id: guidanceId,
			},
		} ).done( function ( response ) {
			if ( response.success ) {
				showPageMessage( response.data.message || fstuGuidanceL10n.messages.saveSuccess, false );
				loadList();
			} else {
				showPageMessage( getAjaxMessage( response, fstuGuidanceL10n.messages.deleteError ), true );
				window.alert( getAjaxMessage( response, fstuGuidanceL10n.messages.deleteError ) );
			}
		} ).fail( function () {
			showPageMessage( fstuGuidanceL10n.messages.deleteError, true );
			window.alert( fstuGuidanceL10n.messages.deleteError );
		} ).always( function () {
			deletingIds = deletingIds.filter( function ( id ) {
				return id !== guidanceId;
			} );
		} );
	}

	function closeAllDropdowns() {
		if ( dropdownOpen ) {
			dropdownOpen.removeClass( 'fstu-dropdown--open fstu-dropdown--up' );
			dropdownOpen.find( '.fstu-dropdown-menu' ).removeAttr( 'style' );
			dropdownOpen = null;
		}
	}

	function toggleDropdown( $dropdown ) {
		if ( dropdownOpen && dropdownOpen[0] === $dropdown[0] ) {
			closeAllDropdowns();
			return;
		}

		closeAllDropdowns();

		var $menu = $dropdown.find( '.fstu-dropdown-menu' );
		var rect = $dropdown.find( '.fstu-dropdown-toggle' )[0].getBoundingClientRect();
		var menuHeight = $menu.outerHeight() || 120;
		var menuWidth = $menu.outerWidth() || 150;
		var viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
		var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
		var top = rect.bottom + 4;
		var left = rect.left;
		var openUp = top + menuHeight > viewportHeight - 12;

		if ( openUp ) {
			top = rect.top - menuHeight - 4;
			$dropdown.addClass( 'fstu-dropdown--up' );
		}

		if ( left + menuWidth > viewportWidth - 8 ) {
			left = Math.max( 8, viewportWidth - menuWidth - 8 );
		}

		if ( left < 8 ) {
			left = 8;
		}

		if ( top < 8 ) {
			top = 8;
		}

		if ( top + menuHeight > viewportHeight - 8 ) {
			top = Math.max( 8, viewportHeight - menuHeight - 8 );
		}

		$menu.css( {
			position: 'fixed',
			top: top + 'px',
			left: left + 'px',
			zIndex: 100001,
		} );
		$dropdown.addClass( 'fstu-dropdown--open' );
		dropdownOpen = $dropdown;
	}

	$( document ).on( 'click', '#fstu-guidance-add-btn', function () {
		openCreateModal();
	} );

	$( document ).on( 'change', '#fstu-guidance-typeguidance-filter', function () {
		clearPageMessage();
		listState.typeguidanceId = parseInt( $( this ).val(), 10 ) || 1;
		listState.page = 1;
		loadList();
	} );

	$( document ).on( 'input', '#fstu-guidance-search', debounce( function () {
		clearPageMessage();
		listState.search = $( this ).val().trim();
		listState.page = 1;
		loadList();
	}, 300 ) );

	$( document ).on( 'change', '#fstu-guidance-per-page', function () {
		clearPageMessage();
		listState.perPage = parseInt( $( this ).val(), 10 ) || 10;
		listState.page = 1;
		loadList();
	} );

	$( document ).on( 'click', '#fstu-guidance-pagination-pages .fstu-btn--page', function () {
		listState.page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
		loadList();
	} );

	$( document ).on( 'click', '#fstu-guidance-prev-page', function () {
		if ( listState.page > 1 ) {
			listState.page -= 1;
			loadList();
		}
	} );

	$( document ).on( 'click', '#fstu-guidance-next-page', function () {
		listState.page += 1;
		loadList();
	} );

	$( document ).on( 'click', '#fstu-guidance-protocol-btn', function () {
		clearPageMessage();
		$( '#fstu-guidance-main' ).addClass( 'fstu-hidden' );
		$( '#fstu-guidance-protocol' ).removeClass( 'fstu-hidden' );
		$( '#fstu-guidance-add-btn' ).addClass( 'fstu-hidden' );
		$( '#fstu-guidance-protocol-btn' ).addClass( 'fstu-hidden' );
		$( '#fstu-guidance-protocol-back-btn' ).removeClass( 'fstu-hidden' );
		$( '#fstu-guidance-filter-wrap' ).addClass( 'fstu-hidden' );
		loadProtocol();
	} );

	$( document ).on( 'click', '#fstu-guidance-protocol-back-btn', function () {
		clearPageMessage();
		$( '#fstu-guidance-main' ).removeClass( 'fstu-hidden' );
		$( '#fstu-guidance-protocol' ).addClass( 'fstu-hidden' );
		$( '#fstu-guidance-add-btn' ).removeClass( 'fstu-hidden' );
		$( '#fstu-guidance-protocol-btn' ).removeClass( 'fstu-hidden' );
		$( '#fstu-guidance-protocol-back-btn' ).addClass( 'fstu-hidden' );
		$( '#fstu-guidance-filter-wrap' ).removeClass( 'fstu-hidden' );
	} );

	$( document ).on( 'input', '#fstu-guidance-protocol-search', debounce( function () {
		protocolState.search = $( this ).val().trim();
		protocolState.page = 1;
		loadProtocol();
	}, 300 ) );

	$( document ).on( 'change', '#fstu-guidance-protocol-per-page', function () {
		protocolState.perPage = parseInt( $( this ).val(), 10 ) || 10;
		protocolState.page = 1;
		loadProtocol();
	} );

	$( document ).on( 'click', '#fstu-guidance-protocol-pagination-pages .fstu-btn--page', function () {
		protocolState.page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
		loadProtocol();
	} );

	$( document ).on( 'click', '#fstu-guidance-protocol-prev-page', function () {
		if ( protocolState.page > 1 ) {
			protocolState.page -= 1;
			loadProtocol();
		}
	} );

	$( document ).on( 'click', '#fstu-guidance-protocol-next-page', function () {
		protocolState.page += 1;
		loadProtocol();
	} );

	$( document ).on( 'change', '#fstu-guidance-typeguidance-id', function () {
		loadMemberGuidanceOptions( parseInt( $( this ).val(), 10 ) || 1 );
	} );

	$( document ).on( 'input', '#fstu-guidance-user-input', debounce( function () {
		$( '#fstu-guidance-user-id' ).val( '0' );
		searchUsers( $( this ).val().trim() );
	}, 250 ) );

	$( document ).on( 'click', '.fstu-autocomplete-item', function () {
		$( '#fstu-guidance-user-id' ).val( parseInt( $( this ).data( 'user-id' ), 10 ) || 0 );
		$( '#fstu-guidance-user-input' ).val( $( this ).data( 'user-name' ) || '' );
		$( '#fstu-guidance-user-results' ).addClass( 'fstu-hidden' ).empty();
	} );

	$( document ).on( 'submit', '#fstu-guidance-form', function ( event ) {
		event.preventDefault();
		submitForm();
	} );

	$( document ).on( 'click', '.fstu-guidance-view-link, .fstu-guidance-view-btn', function ( event ) {
		event.preventDefault();
		closeAllDropdowns();
		openViewModal( parseInt( $( this ).data( 'guidance-id' ), 10 ) || 0 );
	} );

	$( document ).on( 'click', '.fstu-guidance-view-edit-btn, .fstu-guidance-edit-btn', function () {
		closeModal( 'fstu-guidance-view-modal' );
		closeAllDropdowns();
		openEditModal( parseInt( $( this ).data( 'guidance-id' ), 10 ) || 0 );
	} );

	$( document ).on( 'click', '.fstu-guidance-delete-btn', function () {
		closeAllDropdowns();
		deleteItem( parseInt( $( this ).data( 'guidance-id' ), 10 ) || 0 );
	} );

	$( document ).on( 'click', '.fstu-dropdown-toggle', function ( event ) {
		event.preventDefault();
		event.stopPropagation();
		toggleDropdown( $( this ).closest( '.fstu-dropdown' ) );
	} );

	$( document ).on( 'click', function () {
		closeAllDropdowns();
		$( '#fstu-guidance-user-results' ).addClass( 'fstu-hidden' );
	} );

	$( window ).on( 'scroll resize', function () {
		closeAllDropdowns();
	} );

	$( document ).on( 'click', '[data-close-modal]', function () {
		closeModal( $( this ).data( 'close-modal' ) );
	} );

	loadFilters( function () {
		loadList();
	} );
} );

