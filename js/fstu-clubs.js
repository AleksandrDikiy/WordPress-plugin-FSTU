/**
 * JS модуля "Довідник клубів ФСТУ".
 * Список, пагінація, пошук, перегляд, додавання, редагування, видалення.
 * Жодного inline-коду у PHP!
 *
 * Version:     1.3.0
 * Date_update: 2026-04-06
 *
 * @package FSTU
 * @requires jQuery
 * @requires fstuClubs  (wp_localize_script)
 */

/* global fstuClubs */

jQuery( document ).ready( function ( $ ) {

	'use strict';

	const $mainSection        = $( '#fstu-clubs-main' );
	const $protocolSection    = $( '#fstu-clubs-protocol' );
	const $protocolOpenBtn    = $( '#fstu-club-btn-protocol' );
	const $protocolBackBtn    = $( '#fstu-club-btn-protocol-back' );
	const $protocolPerPage    = $( '#fstu-clubs-protocol-per-page' );
	const $protocolFilterName = $( '#fstu-clubs-protocol-filter-name' );

	// ─── Стан модуля ──────────────────────────────────────────────────────────
	const state = {
		page:        1,
		per_page:    10,
		total:       0,
		total_pages: 0,
		search:      '',
		loading:     false,
	};

	const protocolState = {
		page:        1,
		per_page:    parseInt( $protocolPerPage.val(), 10 ) || 10,
		total:       0,
		total_pages: 1,
		loading:     false,
	};

	// ─── Ініціалізація ─────────────────────────────────────────────────────────
	bindListEvents();
	bindModalEvents();
	bindFormEvents();
	$( '#fstu-clubs-per-page' ).val( String( state.per_page ) );
	$protocolPerPage.val( String( protocolState.per_page ) );
	fetchList(); // початкове завантаження

	// ══════════════════════════════════════════════════════════════════════════
	// 1. СПИСОК
	// ══════════════════════════════════════════════════════════════════════════

	function bindListEvents() {
		// Кнопка «Оновити»
		$( document ).on( 'click', '#fstu-club-btn-refresh', function () {
			fetchList();
		} );

		$( document ).on( 'click', '#fstu-club-btn-protocol', function () {
			handleOpenProtocol();
		} );

		$( document ).on( 'click', '#fstu-club-btn-protocol-back', function () {
			handleCloseProtocol();
		} );

		// Пошук із дебаунсом
		$( document ).on( 'input', '#fstu-club-search', debounce( function () {
			const val = $( this ).val().trim();
			state.search = val;
			state.page   = 1;
			fetchList();
		}, 350 ) );

		// Вибір кількості записів
		$( document ).on( 'change', '#fstu-clubs-per-page', function () {
			state.per_page = parseInt( $( this ).val(), 10 ) || 10;
			state.page = 1;
			fetchList();
		} );

		$( document ).on( 'change', '#fstu-clubs-protocol-per-page', function () {
			protocolState.per_page = parseInt( $( this ).val(), 10 ) || 10;
			protocolState.page = 1;
			loadProtocol();
		} );

		$( document ).on( 'input', '#fstu-clubs-protocol-filter-name', debounce( function () {
			protocolState.page = 1;
			loadProtocol();
		}, 300 ) );

		// Пагінація — статичні кнопки
		$( document ).on( 'click', '#fstu-clubs-first', function () {
			if ( ! $( this ).prop( 'disabled' ) ) { goTo( 1 ); }
		} );
		$( document ).on( 'click', '#fstu-clubs-prev', function () {
			if ( ! $( this ).prop( 'disabled' ) ) { goTo( state.page - 1 ); }
		} );
		$( document ).on( 'click', '#fstu-clubs-next', function () {
			if ( ! $( this ).prop( 'disabled' ) ) { goTo( state.page + 1 ); }
		} );
		$( document ).on( 'click', '#fstu-clubs-last', function () {
			if ( ! $( this ).prop( 'disabled' ) ) { goTo( state.total_pages ); }
		} );

		// Пагінація — динамічні кнопки сторінок
		$( document ).on( 'click', '.fstu-clubs-page-btn', function () {
			const p = parseInt( $( this ).data( 'page' ), 10 );
			if ( p ) { goTo( p ); }
		} );

		$( document ).on( 'click', '#fstu-clubs-protocol-prev-page', function () {
			if ( protocolState.page > 1 ) {
				protocolState.page -= 1;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '#fstu-clubs-protocol-next-page', function () {
			if ( protocolState.page < protocolState.total_pages ) {
				protocolState.page += 1;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '.fstu-clubs-protocol-page-btn', function () {
			const p = parseInt( $( this ).data( 'page' ), 10 );
			if ( p ) {
				protocolState.page = p;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '.fstu-clubs-opts-btn', function ( e ) {
			e.preventDefault();
			e.stopPropagation();

			const $parent = $( this ).parent();
			$( '.fstu-clubs-opts' ).not( $parent ).removeClass( 'fstu-clubs-opts--open fstu-clubs-dropup' );

			const menuHeight = 180;
			const windowHeight = $( window ).height();
			const rect = this.getBoundingClientRect();

			if ( rect.bottom + menuHeight > windowHeight && rect.top > menuHeight ) {
				$parent.addClass( 'fstu-clubs-dropup' );
			} else {
				$parent.removeClass( 'fstu-clubs-dropup' );
			}

			$parent.toggleClass( 'fstu-clubs-opts--open' );
		} );

		$( document ).on( 'click', '.fstu-clubs-opts-list', function ( e ) {
			e.stopPropagation();
		} );

		$( document ).on( 'click', function () {
			$( '.fstu-clubs-opts' ).removeClass( 'fstu-clubs-opts--open fstu-clubs-dropup' );
		} );
	}

	/**
	 * AJAX-запит списку клубів.
	 */
	function fetchList() {
		if ( state.loading ) { return; }
		state.loading = true;

		$( '#fstu-clubs-loader' ).removeClass( 'fstu-hidden' );
		$( '#fstu-clubs-tbody' ).addClass( 'fstu-tbody--loading' );

		$.ajax( {
			url:    fstuClubs.ajaxUrl,
			method: 'POST',
			data: {
				action:   'fstu_clubs_get_list',
				nonce:    fstuClubs.nonce,
				search:   state.search,
				page:     state.page,
				per_page: state.per_page,
			},
			success: function ( r ) {
				if ( r.success ) {
					$( '#fstu-clubs-tbody' ).html( r.data.html );
					state.total       = parseInt( r.data.total, 10 ) || 0;
					state.total_pages = Math.max( parseInt( r.data.total_pages, 10 ) || 1, 1 );
					state.page        = parseInt( r.data.page, 10 ) || 1;
					state.per_page    = parseInt( r.data.per_page, 10 ) || state.per_page;
					$( '#fstu-clubs-per-page' ).val( String( state.per_page ) );
					updatePagination( r.data );
				} else {
					showTableError( r.data.message );
				}
			},
			error: function () {
				showTableError( fstuClubs.strings.errorGeneric );
			},
			complete: function () {
				state.loading = false;
				$( '#fstu-clubs-loader' ).addClass( 'fstu-hidden' );
				$( '#fstu-clubs-tbody' ).removeClass( 'fstu-tbody--loading' );
			},
		} );
	}

	function goTo( page ) {
		page = Math.max( 1, Math.min( page, state.total_pages ) );
		if ( page !== state.page ) {
			state.page = page;
			fetchList();
		}
	}

	function updatePagination( data ) {
		const total = parseInt( data.total, 10 ) || 0;
		const page  = parseInt( data.page, 10 ) || 1;
		const tp    = Math.max( parseInt( data.total_pages, 10 ) || 1, 1 );
		const from  = total > 0 ? ( ( page - 1 ) * state.per_page ) + 1 : 0;
		const to    = total > 0 ? Math.min( page * state.per_page, total ) : 0;

		$( '#fstu-clubs-pag-info' ).text(
			total ? ( 'Показано ' + from + '–' + to + ' з ' + total + ' клубів' ) : ''
		);

		$( '#fstu-clubs-first' ).prop( 'disabled', page <= 1 );
		$( '#fstu-clubs-prev'  ).prop( 'disabled', page <= 1 );
		$( '#fstu-clubs-next'  ).prop( 'disabled', page >= tp );
		$( '#fstu-clubs-last'  ).prop( 'disabled', page >= tp );
		$( '#fstu-clubs-pages' ).html( buildPagination( page, tp, 'fstu-clubs-page-btn' ) );
	}

	function showTableError( msg ) {
		$( '#fstu-clubs-tbody' ).html(
			'<tr><td colspan="4" class="fstu-no-results fstu-no-results--error">' + escHtml( msg ) + '</td></tr>'
		);
	}

	function handleOpenProtocol() {
		$mainSection.addClass( 'fstu-hidden' );
		$protocolSection.removeClass( 'fstu-hidden' );
		$protocolOpenBtn.addClass( 'fstu-hidden' );
		$protocolBackBtn.removeClass( 'fstu-hidden' );
		protocolState.page = 1;
		loadProtocol();
	}

	function handleCloseProtocol() {
		$protocolSection.addClass( 'fstu-hidden' );
		$mainSection.removeClass( 'fstu-hidden' );
		$protocolBackBtn.addClass( 'fstu-hidden' );
		$protocolOpenBtn.removeClass( 'fstu-hidden' );
	}

	function loadProtocol() {
		if ( protocolState.loading ) { return; }
		protocolState.loading = true;
		setProtocolLoading();

		$.ajax( {
			url:    fstuClubs.ajaxUrl,
			method: 'POST',
			data: {
				action:      'fstu_clubs_get_protocol',
				nonce:       fstuClubs.nonce,
				page:        protocolState.page,
				per_page:    protocolState.per_page,
				filter_name: $protocolFilterName.val(),
			},
			success: function ( r ) {
				if ( ! r || ! r.success || ! r.data ) {
					showProtocolError( fstuClubs.strings.protocolError || fstuClubs.strings.errorGeneric );
					return;
				}

				protocolState.total       = parseInt( r.data.total, 10 ) || 0;
				protocolState.total_pages = Math.max( parseInt( r.data.total_pages, 10 ) || 1, 1 );
				protocolState.page        = parseInt( r.data.page, 10 ) || 1;
				protocolState.per_page    = parseInt( r.data.per_page, 10 ) || protocolState.per_page;
				$protocolPerPage.val( String( protocolState.per_page ) );

				renderProtocolTable( r.data.items || [] );
				updateProtocolPagination( r.data );
			},
			error: function () {
				showProtocolError( fstuClubs.strings.protocolError || fstuClubs.strings.errorGeneric );
			},
			complete: function () {
				protocolState.loading = false;
			},
		} );
	}

	function renderProtocolTable( items ) {
		const $tbody = $( '#fstu-clubs-protocol-tbody' );

		if ( ! items.length ) {
			$tbody.html(
				'<tr class="fstu-row"><td colspan="6" class="fstu-no-results">' +
				escHtml( fstuClubs.strings.protocolEmpty || fstuClubs.strings.noData ) +
				'</td></tr>'
			);
			return;
		}

		let html = '';

		items.forEach( function ( item ) {
			html += '<tr class="fstu-row">';
			html += '<td class="fstu-td fstu-td--date">' + escHtml( item.Logs_DateCreate || '' ) + '</td>';
			html += '<td class="fstu-td fstu-td--type">' + escHtml( item.Logs_Type || '' ) + '</td>';
			html += '<td class="fstu-td fstu-td--operation">' + escHtml( item.Logs_Name || '' ) + '</td>';
			html += '<td class="fstu-td fstu-td--message">' + escHtml( item.Logs_Text || '' ) + '</td>';
			html += '<td class="fstu-td fstu-td--status">' + escHtml( item.Logs_Error || '✓' ) + '</td>';
			html += '<td class="fstu-td fstu-td--user">' + escHtml( item.FIO || '—' ) + '</td>';
			html += '</tr>';
		} );

		$tbody.html( html );
	}

	function updateProtocolPagination( data ) {
		const total = parseInt( data.total, 10 ) || 0;
		const page  = parseInt( data.page, 10 ) || 1;
		const tp    = Math.max( parseInt( data.total_pages, 10 ) || 1, 1 );

		$( '#fstu-clubs-protocol-info' ).text(
			'Записів: ' + total + ' | Сторінка ' + page + ' з ' + tp
		);

		$( '#fstu-clubs-protocol-prev-page' ).prop( 'disabled', page <= 1 );
		$( '#fstu-clubs-protocol-next-page' ).prop( 'disabled', page >= tp );
		$( '#fstu-clubs-protocol-pages' ).html( buildPagination( page, tp, 'fstu-clubs-protocol-page-btn' ) );
	}

	function setProtocolLoading() {
		$( '#fstu-clubs-protocol-tbody' ).html(
			'<tr class="fstu-row"><td colspan="6" class="fstu-no-results">Завантаження...</td></tr>'
		);
	}

	function showProtocolError( msg ) {
		$( '#fstu-clubs-protocol-tbody' ).html(
			'<tr class="fstu-row"><td colspan="6" class="fstu-no-results fstu-no-results--error">' + escHtml( msg ) + '</td></tr>'
		);
	}

	// ══════════════════════════════════════════════════════════════════════════
	// 2. МОДАЛЬНІ ВІКНА (загальна логіка)
	// ══════════════════════════════════════════════════════════════════════════

	function bindModalEvents() {
		// Закрити по data-close-modal
		$( document ).on( 'click', '[data-close-modal]', function () {
			closeModal( $( this ).data( 'close-modal' ) );
		} );

		// Закрити по кліку на оверлей
		$( document ).on( 'click', '.fstu-modal-overlay', function ( e ) {
			if ( $( e.target ).is( '.fstu-modal-overlay' ) ) {
				closeModal( $( this ).attr( 'id' ) );
			}
		} );

		// Закрити по Escape
		$( document ).on( 'keydown', function ( e ) {
			if ( e.key === 'Escape' ) {
				$( '.fstu-modal-overlay:not(.fstu-hidden)' ).each( function () {
					closeModal( $( this ).attr( 'id' ) );
				} );
			}
		} );

		// ── Перегляд клубу (клік на назву) ────────────────────────────────────
		$( document ).on( 'click', '.fstu-club-name-link', function ( e ) {
			e.preventDefault();
			const clubId = parseInt( $( this ).data( 'club-id' ), 10 );
			if ( clubId > 0 ) { openViewModal( clubId ); }
		} );

		// ── Кнопка «Редагувати» у вікні перегляду ────────────────────────────
		$( document ).on( 'click', '#fstu-club-view-edit-btn', function () {
			const clubId = parseInt( $( this ).data( 'club-id' ), 10 );
			closeModal( 'fstu-modal-club-view' );
			openEditForm( clubId );
		} );

		// ── Пункт «Перегляд» у dropdown-меню ─────────────────────────────────
		$( document ).on( 'click', '.fstu-btn--view', function ( e ) {
			e.preventDefault();
			const clubId = parseInt( $( this ).data( 'club-id' ), 10 );
			openViewModal( clubId );
		} );

		// ── Кнопки редагування у таблиці ──────────────────────────────────────
		$( document ).on( 'click', '.fstu-btn--edit', function ( e ) {
			e.preventDefault();
			const clubId = parseInt( $( this ).data( 'club-id' ), 10 );
			openEditForm( clubId );
		} );

		// ── Кнопки видалення у таблиці ────────────────────────────────────────
		$( document ).on( 'click', '.fstu-btn--delete', function ( e ) {
			e.preventDefault();
			const clubId = parseInt( $( this ).data( 'club-id' ), 10 );
			deleteClub( clubId, $( this ).closest( 'tr' ) );
		} );

		// ── Кнопка «Додати клуб» ──────────────────────────────────────────────
		$( document ).on( 'click', '#fstu-club-btn-add', function () {
			openAddForm();
		} );
	}

	function openModal( id ) {
		$( '#' + id )
			.removeClass( 'fstu-hidden' )
			.attr( 'aria-hidden', 'false' );
		$( 'body' ).addClass( 'fstu-modal-open' );
		$( '#' + id ).find( 'button, input, select, textarea, a' ).first().trigger( 'focus' );
	}

	function closeModal( id ) {
		$( '#' + id )
			.addClass( 'fstu-hidden' )
			.attr( 'aria-hidden', 'true' );
		if ( $( '.fstu-modal-overlay:not(.fstu-hidden)' ).length === 0 ) {
			$( 'body' ).removeClass( 'fstu-modal-open' );
		}
	}

	// ─── Перегляд клубу ───────────────────────────────────────────────────────

	function openViewModal( clubId ) {
		$( '#fstu-club-view-body' ).html(
			'<div class="fstu-tab-loader"><span class="fstu-loader__spinner"></span></div>'
		);
		$( '#fstu-club-view-footer' ).empty();
		$( '#fstu-club-view-title' ).text( 'Клуб' );
		openModal( 'fstu-modal-club-view' );

		$.ajax( {
			url:    fstuClubs.ajaxUrl,
			method: 'POST',
			data: {
				action:  'fstu_clubs_get_single',
				nonce:   fstuClubs.nonce,
				club_id: clubId,
			},
			success: function ( r ) {
				if ( ! r.success ) {
					$( '#fstu-club-view-body' ).html(
						'<p class="fstu-tab-empty fstu-tab-empty--error">' + escHtml( r.data.message ) + '</p>'
					);
					return;
				}

				const d = r.data;
				$( '#fstu-club-view-title' ).text( d.club_name );

				let html = '<table class="fstu-dtable">';
				html += '<tr><td>Назва</td><td><b>' + escHtml( d.club_name ) + '</b></td></tr>';
				html += '<tr><td>Адреса</td><td>' + ( d.club_adr ? escHtml( d.club_adr ) : '<span class="fstu-text-muted">—</span>' ) + '</td></tr>';
				if ( d.club_www ) {
					html += '<tr><td>Сайт</td><td><a href="' + escHtml( d.club_www ) + '" target="_blank" rel="noopener noreferrer">' + escHtml( d.club_www ) + '</a></td></tr>';
				}
				html += '<tr><td>Учасників</td><td><b>' + d.member_count + '</b></td></tr>';
				html += '</table>';
				$( '#fstu-club-view-body' ).html( html );

				// Кнопка «Редагувати» — тільки для прав
				if ( fstuClubs.isReg === '1' ) {
					$( '#fstu-club-view-footer' ).html(
						'<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-club-view-edit-btn" data-club-id="' + d.club_id + '">✏ Редагувати</button>'
					);
				}
			},
			error: function () {
				$( '#fstu-club-view-body' ).html(
					'<p class="fstu-tab-empty fstu-tab-empty--error">' + escHtml( fstuClubs.strings.errorGeneric ) + '</p>'
				);
			},
		} );
	}

	// ══════════════════════════════════════════════════════════════════════════
	// 3. ФОРМА ДОДАВАННЯ / РЕДАГУВАННЯ
	// ══════════════════════════════════════════════════════════════════════════

	function bindFormEvents() {
		$( document ).on( 'submit', '#fstu-club-form', function ( e ) {
			e.preventDefault();
			submitForm();
		} );
	}

	function openAddForm() {
		resetForm();
		$( '#fstu-club-form-title' ).text( fstuClubs.strings.addTitle );
		$( '#fstu-club-form-id' ).val( 0 );
		openModal( 'fstu-modal-club-form' );
		$( '#fstu-club-name' ).trigger( 'focus' );
	}

	function openEditForm( clubId ) {
		resetForm();
		$( '#fstu-club-form-title' ).text( fstuClubs.strings.editTitle );
		$( '#fstu-club-form-id' ).val( 0 );

		// Блокуємо кнопку поки завантажуємо дані
		setFormLoading( true );
		openModal( 'fstu-modal-club-form' );

		$.ajax( {
			url:    fstuClubs.ajaxUrl,
			method: 'POST',
			data: {
				action:  'fstu_clubs_get_single',
				nonce:   fstuClubs.nonce,
				club_id: clubId,
			},
			success: function ( r ) {
				if ( r.success ) {
					const d = r.data;
					$( '#fstu-club-form-id' ).val( d.club_id );
					$( '#fstu-club-name' ).val( d.club_name );
					$( '#fstu-club-adr'  ).val( d.club_adr );
					$( '#fstu-club-www'  ).val( d.club_www );
				} else {
					showFormMessage( r.data.message, 'error' );
				}
			},
			error: function () {
				showFormMessage( fstuClubs.strings.errorGeneric, 'error' );
			},
			complete: function () {
				setFormLoading( false );
				$( '#fstu-club-name' ).trigger( 'focus' );
			},
		} );
	}

	function submitForm() {
		// Honeypot перевірка
		if ( $( 'input[name="fstu_website"]' ).val() !== '' ) {
			return; // тихо ігноруємо бота
		}

		const clubId   = parseInt( $( '#fstu-club-form-id' ).val(), 10 ) || 0;
		const clubName = $( '#fstu-club-name' ).val().trim();
		const clubAdr  = $( '#fstu-club-adr'  ).val().trim();
		const clubWww  = $( '#fstu-club-www'  ).val().trim();

		// Валідація
		if ( clubName.length < 2 ) {
			$( '#fstu-club-name-error' ).text( 'Назва обов\'язкова (мінімум 2 символи).' ).removeClass( 'fstu-hidden' );
			$( '#fstu-club-name' ).trigger( 'focus' );
			return;
		}
		$( '#fstu-club-name-error' ).addClass( 'fstu-hidden' );

		setFormLoading( true );
		hideFormMessage();

		$.ajax( {
			url:    fstuClubs.ajaxUrl,
			method: 'POST',
			data: {
				action:    'fstu_clubs_save',
				nonce:     fstuClubs.nonce,
				club_id:   clubId,
				club_name: clubName,
				club_adr:  clubAdr,
				club_www:  clubWww,
			},
			success: function ( r ) {
				if ( r.success ) {
					showFormMessage( r.data.message, 'success' );
					setTimeout( function () {
						closeModal( 'fstu-modal-club-form' );
						fetchList(); // оновлюємо таблицю
					}, 1200 );
				} else {
					showFormMessage( r.data.message, 'error' );
				}
			},
			error: function () {
				showFormMessage( fstuClubs.strings.errorGeneric, 'error' );
			},
			complete: function () {
				setFormLoading( false );
			},
		} );
	}

	function resetForm() {
		$( '#fstu-club-form' )[ 0 ].reset();
		$( '#fstu-club-form-id' ).val( 0 );
		$( '#fstu-club-name-error' ).addClass( 'fstu-hidden' );
		hideFormMessage();
		setFormLoading( false );
	}

	function setFormLoading( isLoading ) {
		const $btn    = $( '#fstu-club-form-submit' );
		const $text   = $btn.find( '.fstu-btn__text' );
		const $loader = $btn.find( '.fstu-btn__loader' );
		$btn.prop( 'disabled', isLoading ).attr( 'aria-disabled', isLoading ? 'true' : 'false' );
		$text.toggleClass( 'fstu-hidden', isLoading );
		$loader.toggleClass( 'fstu-hidden', ! isLoading );
	}

	function showFormMessage( msg, type ) {
		$( '#fstu-club-form-message' )
			.text( msg )
			.removeClass( 'fstu-hidden fstu-message--success fstu-message--error' )
			.addClass( type === 'success' ? 'fstu-message--success' : 'fstu-message--error' );
	}

	function hideFormMessage() {
		$( '#fstu-club-form-message' ).addClass( 'fstu-hidden' );
	}

	// ══════════════════════════════════════════════════════════════════════════
	// 4. ВИДАЛЕННЯ
	// ══════════════════════════════════════════════════════════════════════════

	function deleteClub( clubId, $row ) {
		if ( ! window.confirm( fstuClubs.strings.confirmDelete ) ) {
			return;
		}

		// Візуальне блокування рядка
		$row.addClass( 'fstu-row--deleting' ).find( 'button' ).prop( 'disabled', true );

		$.ajax( {
			url:    fstuClubs.ajaxUrl,
			method: 'POST',
			data: {
				action:  'fstu_clubs_delete',
				nonce:   fstuClubs.nonce,
				club_id: clubId,
			},
			success: function ( r ) {
				if ( r.success ) {
					// Анімоване зникнення рядка
					$row.fadeOut( 250, function () {
						$( this ).remove();
						state.total = Math.max( 0, state.total - 1 );
						// Якщо сторінка стала порожньою — перейти на попередню
						if ( $( '#fstu-clubs-tbody tr' ).length === 0 && state.page > 1 ) {
							state.page--;
							fetchList();
						} else {
							updatePagination( {
								total:       state.total,
								page:        state.page,
								total_pages: Math.ceil( state.total / state.per_page ) || 1,
							} );
						}
					} );
				} else {
					$row.removeClass( 'fstu-row--deleting' ).find( 'button' ).prop( 'disabled', false );
					alert( r.data.message );
				}
			},
			error: function () {
				$row.removeClass( 'fstu-row--deleting' ).find( 'button' ).prop( 'disabled', false );
				alert( fstuClubs.strings.errorGeneric );
			},
		} );
	}

	// ─── Допоміжні функції ─────────────────────────────────────────────────────

	/**
	 * Будує HTML кнопок пагінації.
	 */
	function buildPagination( current, total, btnClass ) {
		if ( total <= 1 ) { return ''; }
		const MAX = 7;
		let pages = [];

		if ( total <= MAX ) {
			for ( let i = 1; i <= total; i++ ) { pages.push( i ); }
		} else {
			pages = [ 1 ];
			const s = Math.max( 2, current - 2 );
			const e = Math.min( total - 1, current + 2 );
			if ( s > 2 ) { pages.push( '…' ); }
			for ( let i = s; i <= e; i++ ) { pages.push( i ); }
			if ( e < total - 1 ) { pages.push( '…' ); }
			pages.push( total );
		}

		return pages.map( function ( p ) {
			if ( p === '…' ) { return '<span class="fstu-pagination__ellipsis">…</span>'; }
			const active = p === current ? ' fstu-btn--page-active' : '';
			return '<button type="button" class="fstu-btn fstu-btn--page ' + btnClass + active + '" data-page="' + p + '"' +
			       ( p === current ? ' aria-current="page"' : '' ) + '>' + p + '</button>';
		} ).join( '' );
	}

	/**
	 * Debounce — відкладає виклик після останнього спрацювання.
	 */
	function debounce( fn, wait ) {
		let t;
		return function ( ...args ) {
			clearTimeout( t );
			t = setTimeout( () => fn.apply( this, args ), wait );
		};
	}

	/**
	 * Безпечне клієнтське екранування HTML.
	 */
	function escHtml( s ) {
		if ( typeof s !== 'string' ) { return ''; }
		return s.replace( /&/g, '&amp;' ).replace( /</g, '&lt;' ).replace( />/g, '&gt;' )
		        .replace( /"/g, '&quot;' ).replace( /'/g, '&#039;' );
	}

} );
