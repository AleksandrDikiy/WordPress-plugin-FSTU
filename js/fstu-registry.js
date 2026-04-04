/**
 * JS модуля "Реєстр членів ФСТУ".
 * Обробка: фільтри, пагінація, модальні вікна, форма заявки.
 * Жодних inline-скриптів у PHP!
 *
 * Version:     1.1.1
 * Date_update: 2026-04-05
 *
 * @package FSTU
 * @requires jQuery
 * @requires fstuRegistry (wp_localize_script)
 */

/* global fstuRegistry, turnstile */

jQuery( document ).ready( function ( $ ) {

	'use strict';

	// ─── Стан модуля ──────────────────────────────────────────────────────────
	const state = {
		page:        1,
		per_page:    10,
		total:       0,
		total_pages: 0,
		loading:     false,
		filters: {
			unit_id:      0,
			tourism_type: 0,
			club_id:      0,
			year:         fstuRegistry.currentYear,
			search:       '',
			fstu_only:    1,
		},
		turnstileToken: '',
		emailCheckTimer: null,
	};

	// ─── DOM-елементи ─────────────────────────────────────────────────────────
	const $tbody        = $( '#fstu-registry-tbody' );
	const $loader       = $( '#fstu-loader' );
	const $pagination   = $( '#fstu-pagination' );
	const $paginInfo    = $( '#fstu-pagination-info' );
	const $paginPages   = $( '#fstu-page-numbers' );
	const $btnFirst     = $( '#fstu-page-first' );
	const $btnPrev      = $( '#fstu-page-prev' );
	const $btnNext      = $( '#fstu-page-next' );
	const $btnLast      = $( '#fstu-page-last' );
	const $searchInput  = $( '#fstu-filter-search' );
	const $searchClear  = $( '#fstu-search-clear' );

	// ─── Ініціалізація ────────────────────────────────────────────────────────

	function init() {
		bindFilterEvents();
		bindPaginationEvents();
		bindTableEvents();
		bindModalEvents();
		bindApplicationFormEvents();
		fetchRegistry(); // Початкове завантаження
	}

	// ─── Функції запиту до бекенду ────────────────────────────────────────────

	function fetchRegistry() {
		if ( state.loading ) {
			return;
		}
		state.loading = true;
		showLoader( true );

		$.ajax( {
			url:    fstuRegistry.ajaxUrl,
			method: 'POST',
			data:   {
				action:   'fstu_get_registry',
				nonce:    fstuRegistry.nonce,
				page:     state.page,
				per_page: state.per_page,
				...state.filters,
			},
			success: function ( response ) {
				if ( response.success ) {
					renderTableRows( response.data.html );
					updatePagination( response.data );
				} else {
					showTableError( fstuRegistry.strings.errorGeneric );
				}
			},
			error: function () {
				showTableError( fstuRegistry.strings.errorGeneric );
			},
			complete: function () {
				state.loading = false;
				showLoader( false );
			},
		} );
	}

	// ─── Рендер таблиці ───────────────────────────────────────────────────────

	function renderTableRows( html ) {
		$tbody.html( html );
	}

	function showTableError( message ) {
		const colspan = fstuRegistry.isLoggedIn === '1' ? 11 : 11;
		$tbody.html(
			`<tr><td colspan="${ colspan }" class="fstu-no-results fstu-no-results--error">${ escHtml( message ) }</td></tr>`
		);
	}

	// ─── Пагінація ────────────────────────────────────────────────────────────

	function updatePagination( data ) {
		state.total       = parseInt( data.total, 10 );
		state.total_pages = parseInt( data.total_pages, 10 );
		state.page        = parseInt( data.page, 10 );

		if ( state.total === 0 ) {
			$paginInfo.text( '' );
			$paginPages.html( '' );
			return;
		}

		const from = ( ( state.page - 1 ) * state.per_page ) + 1;
		const to   = Math.min( state.page * state.per_page, state.total );
		$paginInfo.text( `Показано ${ from }–${ to } з ${ state.total } записів` );

		$btnFirst.prop( 'disabled', state.page <= 1 );
		$btnPrev.prop( 'disabled', state.page <= 1 );
		$btnNext.prop( 'disabled', state.page >= state.total_pages );
		$btnLast.prop( 'disabled', state.page >= state.total_pages );

		$paginPages.html( buildPageNumbers( state.page, state.total_pages ) );
	}

	function buildPageNumbers( current, total ) {
		if ( total <= 1 ) return '';

		const MAX_VISIBLE = 7;
		let pages = [];

		if ( total <= MAX_VISIBLE ) {
			for ( let i = 1; i <= total; i++ ) pages.push( i );
		} else {
			pages = [ 1 ];
			let start = Math.max( 2, current - 2 );
			let end   = Math.min( total - 1, current + 2 );

			if ( start > 2 ) pages.push( '…' );
			for ( let i = start; i <= end; i++ ) pages.push( i );
			if ( end < total - 1 ) pages.push( '…' );
			pages.push( total );
		}

		return pages.map( function ( p ) {
			if ( p === '…' ) return '<span class="fstu-pagination__ellipsis">…</span>';
			const active = p === current ? ' fstu-btn--page-active' : '';
			return `<button type="button" class="fstu-btn fstu-btn--page${ active }" data-page="${ p }" aria-label="Сторінка ${ p }" ${ p === current ? 'aria-current="page"' : '' }>${ p }</button>`;
		} ).join( '' );
	}

	// ─── Обробники подій: Фільтри ─────────────────────────────────────────────

	function bindFilterEvents() {
		$( document ).on( 'change', '.fstu-filter-trigger:not(.fstu-search-input):not(.fstu-checkbox)', function () {
			const filterKey = $( this ).data( 'filter' );
			if ( filterKey === 'per_page' ) {
				state.per_page = parseInt( $( this ).val(), 10 ) || 10;
			} else {
				state.filters[ filterKey ] = $( this ).val() || 0;
			}
			state.page = 1;
			fetchRegistry();
		} );

		$( document ).on( 'change', '#fstu-filter-fstu-only', function () {
			state.filters.fstu_only = $( this ).is( ':checked' ) ? 1 : 0;
			state.page = 1;
			fetchRegistry();
		} );

		$( document ).on( 'input', '#fstu-filter-search', debounce( function () {
			const val = $( this ).val().trim();
			state.filters.search = val;
			state.page = 1;
			$searchClear.toggleClass( 'fstu-hidden', val === '' );
			fetchRegistry();
		}, 400 ) );

		$( document ).on( 'click', '#fstu-search-clear', function () {
			$searchInput.val( '' ).trigger( 'input' );
		} );

		$( document ).on( 'click', '#fstu-btn-refresh', function () {
			fetchRegistry();
		} );
	}

	// ─── Обробники подій: Пагінація ───────────────────────────────────────────

	function bindPaginationEvents() {
		$( document ).on( 'click', '#fstu-page-first', function () { if ( ! $( this ).prop( 'disabled' ) ) goToPage( 1 ); } );
		$( document ).on( 'click', '#fstu-page-prev', function () { if ( ! $( this ).prop( 'disabled' ) ) goToPage( state.page - 1 ); } );
		$( document ).on( 'click', '#fstu-page-next', function () { if ( ! $( this ).prop( 'disabled' ) ) goToPage( state.page + 1 ); } );
		$( document ).on( 'click', '#fstu-page-last', function () { if ( ! $( this ).prop( 'disabled' ) ) goToPage( state.total_pages ); } );

		$( document ).on( 'click', '#fstu-page-numbers .fstu-btn--page', function () {
			const page = parseInt( $( this ).data( 'page' ), 10 );
			if ( page && ! isNaN( page ) ) goToPage( page );
		} );
	}

	function goToPage( page ) {
		page = Math.max( 1, Math.min( page, state.total_pages ) );
		if ( page !== state.page ) {
			state.page = page;
			fetchRegistry();
			$( 'html, body' ).animate( { scrollTop: $( '#fstu-registry' ).offset().top - 20 }, 300 );
		}
	}

	// ─── Обробники подій: Таблиця ─────────────────────────────────────────────

	function bindTableEvents() {
		// Клік по "Членський квиток" — відкрити Картку члена
		$( document ).on( 'click', '.fstu-card-link, .fstu-card--download', function ( e ) {
			e.preventDefault();
			const userId = $( this ).data( 'user-id' );
			const card   = $( this ).data( 'card' );
			console.log( '[FSTU] Членський квиток:', { userId, card } ); // eslint-disable-line no-console
			openMemberCard( userId );
		} );

		// Клік по "Клуб" — відкрити інформацію про клуб
		$( document ).on( 'click', '.fstu-club-link', function ( e ) {
			e.preventDefault();
			const clubId = $( this ).data( 'club-id' );
			console.log( '[FSTU] Клуб ID:', clubId ); // eslint-disable-line no-console
			openClubInfo( clubId );
		} );

		// Кнопка деталей рядка ▾
		$( document ).on( 'click', '.fstu-btn--details', function () {
			const userId = $( this ).data( 'user-id' );
			console.log( '[FSTU] Деталі користувача:', userId ); // eslint-disable-line no-console
			$( this ).toggleClass( 'fstu-btn--details-open' );
			// Додаткову логіку розгортання рядка можна додати тут
		} );
	}

	// ─── Обробка AJAX для Картки Члена ────────────────────────────────────────

	function openMemberCard( userId ) {
		openModal( 'fstu-modal-member-card' );

		const $modal  = $( '#fstu-modal-member-card' );
		const $loader = $modal.find( '#fstu-mc-loader' );
		const $alert  = $modal.find( '#fstu-mc-alert' );
		const $panes  = $modal.find( '.fstu-tabs__pane' );

		// Ховаємо дані, показуємо лоадер
		$panes.addClass( 'fstu-hidden' );
		$alert.addClass( 'fstu-hidden' );
		$loader.removeClass( 'fstu-hidden' );

		// Скидаємо вкладки на першу
		$modal.find( '.fstu-tabs__btn' ).removeClass( 'fstu-tabs__btn--active' );
		$modal.find( '.fstu-tabs__btn[data-tab="mc-general"]' ).addClass( 'fstu-tabs__btn--active' );

		$.ajax( {
			url:    fstuRegistry.ajaxUrl,
			method: 'POST',
			data:   { action: 'fstu_get_member_card', nonce: fstuRegistry.nonce, user_id: userId },
			success: function ( r ) {
				$loader.addClass( 'fstu-hidden' );
				if ( r.success && r.data ) {
					// Відновлюємо видимість панелей
					$panes.removeClass( 'fstu-hidden' );
					$( '.fstu-tabs__pane' ).removeClass( 'fstu-tabs__pane--active' );
					$( '#mc-general' ).addClass( 'fstu-tabs__pane--active' );

					// Заповнюємо загальні дані
					if ( r.data.general ) {
						const general = r.data.general;
						$modal.find( '#mc-val-name' ).text( general.name || '—' );
						$modal.find( '#mc-val-birth' ).text( general.birth_date || '—' );
						$modal.find( '#mc-val-email' ).text( general.email || '—' );
						$modal.find( '#mc-val-phone' ).text( general.phone || '—' );
						$modal.find( '#mc-val-skype' ).text( general.skype || '—' );
						$modal.find( '#mc-val-facebook' ).html( general.facebook ? `<a href="${ general.facebook }" target="_blank">${ general.facebook }</a>` : '—' );
						$modal.find( '#mc-photo' ).attr( 'src', general.photo_url );

						// Показуємо іконку згоди
						$modal.find( '#mc-pd-icon-ok' ).toggleClass( 'fstu-hidden', ! general.has_consent );
						$modal.find( '#mc-pd-icon-no' ).toggleClass( 'fstu-hidden', general.has_consent );
						$modal.find( '#mc-pd-text' ).text(
							general.has_consent ? 'Надано згоду на показ ПД' : 'Згоду на показ ПД не надано'
						);
					}
				} else {
					$alert.text( r.data?.message || 'Помилка завантаження даних' ).removeClass( 'fstu-hidden' );
				}
			},
			error: function () {
				$loader.addClass( 'fstu-hidden' );
				$alert.text( fstuRegistry.strings.errorGeneric ).removeClass( 'fstu-hidden' );
			}
		} );
	}


	// ─── Обробка AJAX для Клубу ───────────────────────────────────────────────

	function openClubInfo( clubId ) {
		openModal( 'fstu-modal-club-info' );

		const $loader = $( '#fstu-club-loader' );
		const $alert  = $( '#fstu-club-alert' );
		const $table  = $( '#fstu-club-data' );

		$table.addClass( 'fstu-hidden' );
		$alert.addClass( 'fstu-hidden' );
		$loader.removeClass( 'fstu-hidden' );

		$.ajax( {
			url:    fstuRegistry.ajaxUrl,
			method: 'POST',
			data:   { action: 'fstu_get_club_info', nonce: fstuRegistry.nonce, club_id: clubId },
			success: function ( r ) {
				$loader.addClass( 'fstu-hidden' );
				if ( r.success && r.data ) {
					$table.removeClass( 'fstu-hidden' );
					$( '#club-val-name' ).text( r.data.name || '—' );
					$( '#club-val-city' ).text( r.data.city || '—' );
				} else {
					$alert.text( r.data?.message || 'Клуб не знайдено' ).removeClass( 'fstu-hidden' );
				}
			},
			error: function () {
				$loader.addClass( 'fstu-hidden' );
				$alert.text( fstuRegistry.strings.errorGeneric ).removeClass( 'fstu-hidden' );
			}
		} );
	}

	// ─── Обробники подій: Вкладки (Tabs) ──────────────────────────────────────

	function bindTabsEvents() {
		$( document ).on( 'click', '.fstu-tabs__btn', function () {
			const $btn = $( this );
			const targetId = $btn.data( 'tab' );
			const $container = $btn.closest( '.fstu-tabs' );

			// Перемикаємо кнопки
			$container.find( '.fstu-tabs__btn' ).removeClass( 'fstu-tabs__btn--active' );
			$btn.addClass( 'fstu-tabs__btn--active' );

			// Перемикаємо контент
			$container.find( '.fstu-tabs__pane' ).removeClass( 'fstu-tabs__pane--active' );
			$( '#' + targetId ).addClass( 'fstu-tabs__pane--active' );
		} );
	}

	// ─── Модальні вікна ───────────────────────────────────────────────────────

	function bindModalEvents() {
		$( document ).on( 'click', '.fstu-btn--open-modal', function () {
			const modalId = $( this ).data( 'modal' );
			openModal( modalId );
		} );

		$( document ).on( 'click', '#fstu-modal-close, #fstu-app-cancel', function () {
		// Закриття модалки по кнопці-хрестику
		$( document ).on( 'click', '.fstu-modal-close-btn', function () {
			const modalId = $( this ).closest( '.fstu-modal-overlay' ).attr( 'id' );
			closeModal( modalId );
		} );

		// Закриття модалки заявки
		$( document ).on( 'click', '#fstu-app-cancel', function () {
			closeModal( 'fstu-modal-application' );
		} );

		$( document ).on( 'click', '.fstu-modal-overlay', function ( e ) {
			if ( $( e.target ).hasClass( 'fstu-modal-overlay' ) ) {
				closeModal( $( this ).attr( 'id' ) );
			}
		} );

		$( document ).on( 'keydown', function ( e ) {
			if ( e.key === 'Escape' ) {
				$( '.fstu-modal-overlay:not(.fstu-hidden)' ).each( function () {
					closeModal( $( this ).attr( 'id' ) );
				} );
			}
		} );
	}

	function openModal( modalId ) {
		const $modal = $( `#${ modalId }` );
		if ( $modal.length ) {
			$modal.removeClass( 'fstu-hidden' ).attr( 'aria-hidden', 'false' );
			$( 'body' ).addClass( 'fstu-modal-open' );
			$modal.find( 'input, button, select, a' ).first().trigger( 'focus' );
		}
	}

	function closeModal( modalId ) {
		const $modal = $( `#${ modalId }` );
		$modal.addClass( 'fstu-hidden' ).attr( 'aria-hidden', 'true' );
		$( 'body' ).removeClass( 'fstu-modal-open' );

		if ( modalId === 'fstu-modal-application' ) {
			resetApplicationForm();
		}
	}

	// ─── Форма заявки ─────────────────────────────────────────────────────────

	function bindApplicationFormEvents() {

		// Погодження з умовами (ВЕРХНІЙ ЧЕКБОКС) → розкриваємо форму
		$( document ).on( 'change', '#fstu-terms-agree-top', function () {
			const $form = $( '#fstu-application-form' );
			if ( $( this ).is( ':checked' ) ) {
				$form.removeClass( 'fstu-hidden' );
				$( '#fstu-app-step-terms' ).addClass( 'fstu-terms--agreed' );
			} else {
				$form.addClass( 'fstu-hidden' );
			}
		} );

		// Нижній чек-бокс (Умови) + перевірка Turnstile для розблокування кнопки
		$( document ).on( 'change', '#fstu-terms-agree-bottom', function () {
			checkSubmitButtonState();
		} );

		// Вибір регіону → завантажуємо одиниці/ОФСТ ТА Міста
		$( document ).on( 'change', '#fstu-app-region', function () {
			const regionId = $( this ).val();
			const $unitSelect = $( '#fstu-app-unit' );
			const $citySelect = $( '#fstu-app-city' );

			if ( ! regionId ) {
				$unitSelect.html( '<option value="">— спочатку оберіть область —</option>' ).prop( 'disabled', true );
				$citySelect.html( '<option value="">— спочатку оберіть область —</option>' ).prop( 'disabled', true );
				return;
			}

			$unitSelect.prop( 'disabled', true ).html( '<option>Завантаження...</option>' );
			$citySelect.prop( 'disabled', true ).html( '<option>Завантаження...</option>' );

			// Вантажимо ОФСТ
			$.ajax( {
				url:    fstuRegistry.ajaxUrl,
				method: 'POST',
				data:   { action: 'fstu_get_units_by_region', nonce: fstuRegistry.nonce, region_id: regionId },
				success: function ( r ) {
					if ( r.success && r.data.units ) {
						let options = '<option value="">— оберіть ОФСТ —</option>';
						r.data.units.forEach( u => { options += `<option value="${ parseInt( u.Unit_ID, 10 ) }">${ escHtml( u.Unit_ShortName ) }</option>`; });
						$unitSelect.html( options ).prop( 'disabled', false );
					}
				}
			} );

			// Вантажимо Міста
			$.ajax( {
				url:    fstuRegistry.ajaxUrl,
				method: 'POST',
				data:   { action: 'fstu_get_cities_by_region', nonce: fstuRegistry.nonce, region_id: regionId },
				success: function ( r ) {
					if ( r.success && r.data.cities ) {
						let options = '<option value="">— оберіть місто —</option>';
						r.data.cities.forEach( c => { options += `<option value="${ parseInt( c.City_ID, 10 ) }">${ escHtml( c.City_Name ) }</option>`; });
						$citySelect.html( options ).prop( 'disabled', false );
					}
				}
			} );
		} );

		// Перевірка email (live)
		$( document ).on( 'input', '#fstu-app-email', debounce( function () {
			const email    = $( this ).val().trim();
			const $hint    = $( '#fstu-email-hint' );
			const emailRx  = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

			if ( ! emailRx.test( email ) ) {
				$hint.text( 'Невірний формат email' ).removeClass( 'fstu-hint--ok fstu-hidden' ).addClass( 'fstu-hint--error' );
				return;
			}

			$hint.text( 'Перевірка...' ).removeClass( 'fstu-hint--ok fstu-hint--error fstu-hidden' );

			$.ajax( {
				url:    fstuRegistry.ajaxUrl,
				method: 'POST',
				data:   { action: 'fstu_check_email', nonce: fstuRegistry.nonce, email: email },
				success: function ( r ) {
					if ( r.success ) {
						if ( r.data.exists ) {
							$hint.text( 'Цей email вже зареєстровано' ).addClass( 'fstu-hint--error' ).removeClass( 'fstu-hint--ok' );
						} else {
							$hint.text( 'Email вільний ✓' ).addClass( 'fstu-hint--ok' ).removeClass( 'fstu-hint--error' );
						}
					}
				}
			} );
		}, 600 ) );

		// Відправка форми
		$( document ).on( 'submit', '#fstu-application-form', function ( e ) {
			e.preventDefault();
			submitApplication( $( this ) );
		} );
	}

	function checkSubmitButtonState() {
		const isAgreed = $( '#fstu-terms-agree-bottom' ).is( ':checked' );
		const hasToken = state.turnstileToken !== '';

		if ( isAgreed && hasToken ) {
			$( '#fstu-app-submit' ).prop( 'disabled', false ).attr( 'aria-disabled', 'false' );
		} else {
			$( '#fstu-app-submit' ).prop( 'disabled', true ).attr( 'aria-disabled', 'true' );
		}
	}

	function submitApplication( $form ) {
		const $submitBtn  = $( '#fstu-app-submit' );
		const $btnText    = $submitBtn.find( '.fstu-btn__text' );
		const $btnLoader  = $submitBtn.find( '.fstu-btn__loader' );
		const $message    = $( '#fstu-app-message' );

		// Детальна перевірка паролів перед запитом
		const pass1 = $( '#fstu-app-pass' ).val();
		const pass2 = $( '#fstu-app-pass-confirm' ).val();

		if ( !pass1 ) {
			$message.text( 'Помилка: Введіть пароль!' ).addClass( 'fstu-message--error' ).removeClass( 'fstu-hidden' );
			return;
		}
		if ( pass1.length < 6 ) {
			$message.text( 'Помилка: Пароль має містити мінімум 6 символів!' ).addClass( 'fstu-message--error' ).removeClass( 'fstu-hidden' );
			return;
		}
		if ( pass1 !== pass2 ) {
			$message.text( 'Помилка: Паролі не співпадають!' ).addClass( 'fstu-message--error' ).removeClass( 'fstu-hidden' );
			return;
		}

		$submitBtn.prop( 'disabled', true ).attr( 'aria-disabled', 'true' );
		$btnText.addClass( 'fstu-hidden' );
		$btnLoader.removeClass( 'fstu-hidden' );
		$message.addClass( 'fstu-hidden' ).removeClass( 'fstu-message--success fstu-message--error' );

		const formData = $form.serializeArray().reduce( function ( obj, item ) {
			obj[ item.name ] = item.value;
			return obj;
		}, {} );

		$.ajax( {
			url:    fstuRegistry.ajaxUrl,
			method: 'POST',
			data: {
				action:               'fstu_submit_application',
				nonce:                fstuRegistry.nonce,
				cf_turnstile_response: state.turnstileToken,
				...formData,
			},
			success: function ( r ) {
				if ( r.success ) {
					$message
						.text( r.data.message )
						.addClass( 'fstu-message--success' )
						.removeClass( 'fstu-hidden' );

					setTimeout( function () {
						closeModal( 'fstu-modal-application' );
						fetchRegistry();
					}, 3000 );
				} else {
					$message
						.text( r.data.message || fstuRegistry.strings.errorGeneric )
						.addClass( 'fstu-message--error' )
						.removeClass( 'fstu-hidden' );

					if ( typeof turnstile !== 'undefined' ) turnstile.reset();
					state.turnstileToken = '';
					checkSubmitButtonState();
				}
			},
			error: function () {
				$message
					.text( fstuRegistry.strings.errorGeneric )
					.addClass( 'fstu-message--error' )
					.removeClass( 'fstu-hidden' );
				checkSubmitButtonState();
			},
			complete: function () {
				$btnText.removeClass( 'fstu-hidden' );
				$btnLoader.addClass( 'fstu-hidden' );
			},
		} );
	}

	function resetApplicationForm() {
		$( '#fstu-application-form' )[ 0 ].reset();
		$( '#fstu-application-form' ).addClass( 'fstu-hidden' );
		$( '#fstu-terms-agree-top' ).prop( 'checked', false );
		$( '#fstu-terms-agree-bottom' ).prop( 'checked', false );
		$( '#fstu-app-message' ).addClass( 'fstu-hidden' );
		$( '#fstu-email-hint' ).addClass( 'fstu-hidden' );
		$( '#fstu-app-unit' ).html( '<option value="0">— спочатку оберіть область —</option>' ).prop( 'disabled', true );
		$( '#fstu-app-city' ).html( '<option value="0">— спочатку оберіть область —</option>' ).prop( 'disabled', true );
		state.turnstileToken = '';
		if ( typeof turnstile !== 'undefined' ) turnstile.reset();
	}

	// ─── Callbackи Cloudflare Turnstile (глобальні) ───────────────────────────

	window.fstuOnTurnstileSuccess = function ( token ) {
		state.turnstileToken = token;
		checkSubmitButtonState();
	};

	window.fstuOnTurnstileExpired = function () {
		state.turnstileToken = '';
		checkSubmitButtonState();
	};

	// ─── Допоміжні функції ────────────────────────────────────────────────────

	function debounce( fn, wait ) {
		let timer;
		return function ( ...args ) {
			clearTimeout( timer );
			timer = setTimeout( () => fn.apply( this, args ), wait );
		};
	}

	function escHtml( str ) {
		if ( typeof str !== 'string' ) return '';
		return str.replace( /&/g, '&amp;' ).replace( /</g, '&lt;' ).replace( />/g, '&gt;' ).replace( /"/g, '&quot;' ).replace( /'/g, '&#039;' );
	}

	function showLoader( show ) {
		$loader.toggleClass( 'fstu-hidden', ! show );
		$tbody.toggleClass( 'fstu-tbody--loading', show );
	}

	// ─── Старт ────────────────────────────────────────────────────────────────
	init();

} );