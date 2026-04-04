/**
 * JS модуля "Реєстр членів ФСТУ".
 * Обробка: фільтри, пагінація, модальні вікна, форма заявки.
 * Жодних inline-скриптів у PHP!
 *
 * Version:     1.2.1
 * Date_update: 2026-04-04
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
		protocol: {
			log_name: 'UserFstu',
			page: 1,
			per_page: 10
		},
		turnstileToken: '',
		emailCheckTimer: null,
	};
	// ─── Стан модальних вікон ─────────────────────────────────────────────────
	const mcState = {
		expData: [], expPage: 1, expPerPage: 10,
		rankData: [], rankPage: 1, rankPerPage: 10,
		duesData: [], duesPage: 1, duesPerPage: 10, // <--- ТУТ БРАКУВАЛО КОМИ
		duesSailData: [], duesSailPage: 1, duesSailPerPage: 10
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
		bindTabsEvents();
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
					// ДОДАЄМО ВИВІД У КОНСОЛЬ ДЛЯ АДМІНІВ
					if ( response.data.debug_sql ) {
						console.log( '🔍 SQL ЗАПИТ ТАБЛИЦІ:\n', response.data.debug_sql );
					}
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

		// ДОДАНО: Оновлення блоку статистики під таблицею
		$( '#fstu-stat-total' ).text( state.total );
		$( '#fstu-stat-paid' ).text( data.total_paid || 0 );

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
			openMemberCard( userId );
		} );

		// Клік по "Клуб" — відкрити інформацію про клуб
		$( document ).on( 'click', '.fstu-club-link', function ( e ) {
			e.preventDefault();
			const clubId = $( this ).data( 'club-id' );
			openClubInfo( clubId );
		} );

		// Кнопка деталей рядка ▾
		$( document ).on( 'click', '.fstu-btn--details', function () {
			const userId = $( this ).data( 'user-id' );
			$( this ).toggleClass( 'fstu-btn--details-open' );
			// Додаткову логіку розгортання рядка можна додати тут
		} );
		// Кнопка ПЕРЕГЛЯД
		$( document ).on( 'click', '.fstu-action-view', function ( e ) {
			e.preventDefault();
			const userId = $( this ).data( 'id' );
			openMemberCard( userId );
		} );
		// Кнопка РЕДАГУВАННЯ
		$( document ).on( 'click', '.fstu-action-edit', function ( e ) {
			e.preventDefault();
			const userId = $( this ).data( 'id' );
			openEditUserModal( userId ); // Цю функцію ми створили в попередньому кроці
		} );
		// Кнопка ВИДАЛЕННЯ (Soft Delete)
		$( document ).on( 'click', '.fstu-action-delete', function ( e ) {
			e.preventDefault();
			const userId = $( this ).data( 'id' );

			if ( confirm( 'Ви дійсно хочете ВИКЛЮЧИТИ цю людину з членів ФСТУ?\n\n(Фізично дані залишаться в базі для фінансової історії, але користувач втратить активний статус).' ) ) {

				const $btn = $( this );
				const originalText = $btn.html();
				$btn.html( '⏳ Видалення...' ).css( 'pointer-events', 'none' );

				$.ajax( {
					url: fstuRegistry.ajaxUrl,
					method: 'POST',
					data: {
						action: 'fstu_delete_user',
						nonce: fstuRegistry.nonce,
						user_id: userId
					},
					success: function ( r ) {
						if ( r.success ) {
							// Ховаємо меню і перезавантажуємо таблицю
							$( '.fstu-opts' ).removeClass( 'fstu-opts--open' ).removeClass( 'fstu-dropup' );
							fetchRegistry();
						} else {
							alert( 'Помилка: ' + ( r.data?.message || 'Невідома помилка' ) );
							$btn.html( originalText ).css( 'pointer-events', 'auto' );
						}
					},
					error: function () {
						alert( 'Помилка з\'єднання з сервером.' );
						$btn.html( originalText ).css( 'pointer-events', 'auto' );
					}
				} );
			}
		} );
		// Кнопка ДОДАТИ КЛУБ
		$( document ).on( 'click', '.fstu-action-club', function ( e ) {
			e.preventDefault();
			const userId = $( this ).data( 'id' );

			// Підготовка форми перед відкриттям
			$( '#add_club_user_id' ).val( userId );
			$( '#fstu-add-club-form' )[0].reset();
			$( '#fstu-add-club-alert' ).addClass( 'fstu-hidden' );
			$( '#fstu-add-club-submit' ).prop( 'disabled', false ).find( '.fstu-btn__text' ).text( '💾 Зберегти' );

			openModal( 'fstu-modal-add-club' );
		} );
		// Кнопка ЗМІНИТИ ПАРОЛЬ
		$( document ).on( 'click', '.fstu-action-password', function ( e ) {
			e.preventDefault();
			const userId = $( this ).data( 'id' );

			if ( confirm( 'Ви дійсно хочете згенерувати новий пароль для цього користувача та надіслати йому на пошту?' ) ) {

				// Захист від подвійного кліку і візуалізація процесу
				const $btn = $( this );
				const originalText = $btn.html();
				$btn.html( '⏳ Відправка...' ).css( 'pointer-events', 'none' );

				$.ajax( {
					url: fstuRegistry.ajaxUrl,
					method: 'POST',
					data: {
						action: 'fstu_reset_send_password',
						nonce: fstuRegistry.nonce,
						user_id: userId
					},
					success: function ( r ) {
						if ( r.success ) {
							alert( r.data.message ); // Показуємо успішне повідомлення
						} else {
							alert( 'Помилка: ' + ( r.data?.message || 'Невідома помилка' ) );
						}
					},
					error: function () {
						alert( 'Помилка з\'єднання з сервером.' );
					},
					complete: function () {
						// Повертаємо кнопку в нормальний стан
						$btn.html( originalText ).css( 'pointer-events', 'auto' );
						// Закриваємо меню після дії
						$( '.fstu-opts' ).removeClass( 'fstu-opts--open' );
					}
				} );
			}
		} );
		// Кнопка ЗМІНИТИ ОФСТ
		$( document ).on( 'click', '.fstu-action-ofst', function ( e ) {
			e.preventDefault();
			const userId = $( this ).data( 'id' );

			// Ховаємо меню
			$( '.fstu-opts' ).removeClass( 'fstu-opts--open' ).removeClass( 'fstu-dropup' );

			// Готуємо модалку
			$( '#ofst_user_id' ).val( userId );
			$( '#fstu-change-ofst-form' ).addClass( 'fstu-hidden' );
			$( '#fstu-ofst-alert' ).addClass( 'fstu-hidden' );
			$( '#fstu-ofst-loader' ).removeClass( 'fstu-hidden' );
			$( '#fstu-ofst-submit' ).prop( 'disabled', false ).find( '.fstu-btn__text' ).text( '💾 Зберегти зміни' );

			openModal( 'fstu-modal-change-ofst' );

			// Отримуємо поточний ОФСТ, щоб підставити у селект
			$.ajax({
				url: fstuRegistry.ajaxUrl,
				method: 'POST',
				data: { action: 'fstu_get_user_ofst', nonce: fstuRegistry.nonce, user_id: userId },
				success: function(r) {
					$( '#fstu-ofst-loader' ).addClass( 'fstu-hidden' );
					if (r.success) {
						$( '#ofst_unit_id' ).val( r.data.unit_id || '' );
						$( '#fstu-change-ofst-form' ).removeClass( 'fstu-hidden' );
					} else {
						$( '#fstu-ofst-alert' ).text( r.data?.message || 'Помилка' ).removeClass( 'fstu-hidden' );
					}
				}
			});
		} );
		// Кнопка ПОВІДОМИТИ ПРО ВНЕСОК
		$( document ).on( 'click', '.fstu-action-notify', function ( e ) {
			e.preventDefault();
			const userId = $( this ).data( 'id' );

			if ( confirm( 'Надіслати користувачу нагадування про сплату членських внесків на email?' ) ) {

				const $btn = $( this );
				const originalText = $btn.html();

				// Змінюємо текст і блокуємо кнопку
				$btn.html( '⏳ Відправка...' ).css( 'pointer-events', 'none' );

				$.ajax( {
					url: fstuRegistry.ajaxUrl,
					method: 'POST',
					data: {
						action: 'fstu_notify_dues',
						nonce: fstuRegistry.nonce,
						user_id: userId
					},
					success: function ( r ) {
						if ( r.success ) {
							alert( r.data.message );
						} else {
							alert( 'Помилка: ' + ( r.data?.message || 'Невідома помилка' ) );
						}
					},
					error: function () {
						alert( 'Помилка з\'єднання з сервером.' );
					},
					complete: function () {
						// Відновлюємо кнопку та ховаємо меню
						$btn.html( originalText ).css( 'pointer-events', 'auto' );
						$( '.fstu-opts' ).removeClass( 'fstu-opts--open' ).removeClass( 'fstu-dropup' );
					}
				} );
			}
		} );
		// Кнопка ДОДАТИ ВНЕСОК
		$( document ).on( 'click', '.fstu-action-dues', function ( e ) {
			e.preventDefault();
			const userId = $( this ).data( 'id' );

			// Ховаємо меню
			$( '.fstu-opts' ).removeClass( 'fstu-opts--open' ).removeClass( 'fstu-dropup' );

			// Готуємо модалку
			$( '#add_dues_user_id' ).val( userId );
			$( '#fstu-add-dues-form' ).addClass( 'fstu-hidden' );
			$( '#fstu-add-dues-form' )[0].reset();
			$( '#fstu-dues-alert' ).addClass( 'fstu-hidden' );
			$( '#fstu-dues-loader' ).removeClass( 'fstu-hidden' );
			$( '#fstu-dues-submit' ).prop( 'disabled', false ).find( '.fstu-btn__text' ).text( '💾 Зберегти квитанцію' );

			openModal( 'fstu-modal-add-dues' );

			// Отримуємо доступні роки через AJAX
			$.ajax({
				url: fstuRegistry.ajaxUrl,
				method: 'POST',
				data: { action: 'fstu_get_user_dues_years', nonce: fstuRegistry.nonce, user_id: userId },
				success: function(r) {
					$( '#fstu-dues-loader' ).addClass( 'fstu-hidden' );
					if (r.success) {
						let options = '';
						if ( r.data.years.length > 0 ) {
							r.data.years.forEach( y => {
								const selected = ( y === r.data.current_year ) ? 'selected' : '';
								options += `<option value="${y}" ${selected}>${y}</option>`;
							});
							$( '#add_dues_year' ).html( options );
							$( '#fstu-add-dues-form' ).removeClass( 'fstu-hidden' );
						} else {
							$( '#fstu-dues-alert' ).text( 'Користувач вже сплатив внески за всі доступні роки.' ).removeClass( 'fstu-hidden' );
						}
					} else {
						$( '#fstu-dues-alert' ).text( r.data?.message || 'Помилка' ).removeClass( 'fstu-hidden' );
					}
				}
			});
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
						$modal.find( '#mc-val-sex' ).text( general.sex || '—' );
						$modal.find( '#mc-val-email' ).text( general.email || '—' );
						$modal.find( '#mc-val-phone' ).text( general.phone || '—' );
						$modal.find( '#mc-val-skype' ).text( general.skype || '—' );

						// Логіка для Facebook
						let fbHtml = general.facebook || '—';
						if ( general.facebook && general.facebook.startsWith( 'http' ) ) {
							fbHtml = `<a href="${ general.facebook }" target="_blank">Профіль Facebook</a>`;
						}
						$modal.find( '#mc-val-facebook' ).html( fbHtml );

						// Фото
						$modal.find( '#mc-photo' ).attr( 'src', general.photo_url );

						// Показуємо іконку згоди та статус
						$modal.find( '#mc-pd-icon-ok' ).toggleClass( 'fstu-hidden', !general.can_see_personal );
						$modal.find( '#mc-pd-icon-no' ).toggleClass( 'fstu-hidden', general.can_see_personal );

						let pdText = 'Згоду на показ ПД не надано';
						if ( general.can_see_personal ) {
							pdText = general.has_consent ? 'Надано згоду на показ ПД' : 'Доступ (Адмін / Власник)';
						}
						$modal.find( '#mc-pd-text' ).text( pdText );
					}
					// Заповнюємо вкладку "Приватне"
					const $tabPrivate = $modal.find( '#mc-tab-private' );
					if ( r.data.private ) {
						$tabPrivate.removeClass( 'fstu-hidden' );
						$modal.find( '#mc-val-address' ).text( r.data.private.address || '—' );
						$modal.find( '#mc-val-job' ).text( r.data.private.job || '—' );
						$modal.find( '#mc-val-edu' ).text( r.data.private.education || '—' );
						$modal.find( '#mc-val-family-ph' ).text( r.data.private.family_ph || '—' );
					} else {
						$tabPrivate.addClass( 'fstu-hidden' );
					}

					// Заповнюємо вкладку "Службове"
					const $tabService = $modal.find( '#mc-tab-service' );
					if ( r.data.service ) {
						$tabService.removeClass( 'fstu-hidden' );
						$modal.find( '#mc-val-id' ).text( r.data.service.id || '—' );
						$modal.find( '#mc-val-login' ).text( r.data.service.login || '—' );
						$modal.find( '#mc-val-lastlog' ).text( r.data.service.last_login || '—' );
						$modal.find( '#mc-val-regdate' ).text( r.data.service.registered || '—' );
						$modal.find( '#mc-val-tgact' ).text( r.data.service.tg_active || '—' );
						$modal.find( '#mc-val-tgcode' ).text( r.data.service.tg_code || '—' );
						$modal.find( '#mc-val-tgid' ).text( r.data.service.tg_id || '—' );
						$modal.find( '#mc-val-ipn' ).text( r.data.service.ipn || '—' );
						$modal.find( '#mc-val-bank' ).text( r.data.service.bank || '—' );
						$modal.find( '#mc-val-iban' ).text( r.data.service.iban || '—' );
					} else {
						$tabService.addClass( 'fstu-hidden' );
					}
					// Заповнюємо вкладку "Клуби"
					const $clubsList  = $modal.find( '#mc-val-clubs-list' );
					const $clubsEmpty = $modal.find( '#mc-val-clubs-empty' );
					const $clubsTable = $modal.find( '#mc-clubs-table' );

					$clubsList.empty();
					if ( r.data.clubs && r.data.clubs.length > 0 ) {
						$clubsEmpty.addClass( 'fstu-hidden' );
						$clubsTable.removeClass( 'fstu-hidden' );

						let clubsHtml = '';
						r.data.clubs.forEach( function( club ) {
							const nameHtml = club.www
								? `<a href="${ club.www }" target="_blank">${ club.name }</a>`
								: club.name;
							clubsHtml += `
								<tr class="fstu-row">
									<td class="fstu-td" style="font-weight:600;">${ nameHtml }</td>
									<td class="fstu-td">${ club.adr }</td>
								</tr>`;
						});
						$clubsList.html( clubsHtml );
					} else {
						$clubsTable.addClass( 'fstu-hidden' );
						$clubsEmpty.removeClass( 'fstu-hidden' );
					}
					// Заповнюємо вкладку "Осередки"
					const $ofstList  = $modal.find( '#mc-val-ofst-list' );
					const $ofstEmpty = $modal.find( '#mc-val-ofst-empty' );
					const $ofstTable = $modal.find( '#mc-ofst-table' );

					$ofstList.empty();
					if ( r.data.ofst && r.data.ofst.length > 0 ) {
						$ofstEmpty.addClass( 'fstu-hidden' );
						$ofstTable.removeClass( 'fstu-hidden' );

						let ofstHtml = '';
						r.data.ofst.forEach( function( item ) {
							ofstHtml += `
								<tr class="fstu-row">
									<td class="fstu-td" style="font-weight:600;">${ item.unit }</td>
									<td class="fstu-td">${ item.region }</td>
									<td class="fstu-td" style="color: var(--fstu-text-light);">${ item.date }</td>
								</tr>`;
						});
						$ofstList.html( ofstHtml );
					} else {
						$ofstTable.addClass( 'fstu-hidden' );
						$ofstEmpty.removeClass( 'fstu-hidden' );
					}
					// Заповнюємо вкладку "Міста"
					const $citiesList  = $modal.find( '#mc-val-cities-list' );
					const $citiesEmpty = $modal.find( '#mc-val-cities-empty' );
					const $citiesTable = $modal.find( '#mc-cities-table' );

					$citiesList.empty();
					if ( r.data.cities && r.data.cities.length > 0 ) {
						$citiesEmpty.addClass( 'fstu-hidden' );
						$citiesTable.removeClass( 'fstu-hidden' );

						let citiesHtml = '';
						r.data.cities.forEach( function( item ) {
							citiesHtml += `
								<tr class="fstu-row">
									<td class="fstu-td" style="font-weight:600;">${ item.city }</td>
									<td class="fstu-td">${ item.region }</td>
									<td class="fstu-td">${ item.date }</td>
								</tr>`;
						});
						$citiesList.html( citiesHtml );
					} else {
						$citiesTable.addClass( 'fstu-hidden' );
						$citiesEmpty.removeClass( 'fstu-hidden' );
					}
					// Заповнюємо вкладку "Види туризму"
					const $tourismList  = $modal.find( '#mc-val-tourism-list' );
					const $tourismEmpty = $modal.find( '#mc-val-tourism-empty' );
					const $tourismTable = $modal.find( '#mc-tourism-table' );

					$tourismList.empty();
					if ( r.data.tourism && r.data.tourism.length > 0 ) {
						$tourismEmpty.addClass( 'fstu-hidden' );
						$tourismTable.removeClass( 'fstu-hidden' );

						let tourismHtml = '';
						r.data.tourism.forEach( function( item ) {
							tourismHtml += `
								<tr class="fstu-row">
									<td class="fstu-td" style="font-weight:600;">${ item.name }</td>
									<td class="fstu-td" style="color: var(--fstu-text-light);">${ item.date }</td>
								</tr>`;
						});
						$tourismList.html( tourismHtml );
					} else {
						$tourismTable.addClass( 'fstu-hidden' );
						$tourismEmpty.removeClass( 'fstu-hidden' );
					}
					// Заповнюємо вкладку "Досвід"
					const $experienceEmpty = $modal.find( '#mc-val-experience-empty' );
					const $experienceTable = $modal.find( '#mc-experience-table' );
					const $experiencePagin = $modal.find( '#mc-experience-pagination' );

					if ( r.data.experience && r.data.experience.length > 0 ) {
						$experienceEmpty.addClass( 'fstu-hidden' );
						$experienceTable.removeClass( 'fstu-hidden' );

						mcState.expData = r.data.experience;
						mcState.expPage = 1;
						renderExperienceTable();

						if ( mcState.expData.length > mcState.expPerPage ) {
							$experiencePagin.removeClass( 'fstu-hidden' );
						} else {
							$experiencePagin.addClass( 'fstu-hidden' );
						}
					} else {
						$experienceTable.addClass( 'fstu-hidden' );
						$experiencePagin.addClass( 'fstu-hidden' );
						$experienceEmpty.removeClass( 'fstu-hidden' );
					}
					// Заповнюємо вкладку "Розряди"
					const $ranksEmpty = $modal.find( '#mc-val-ranks-empty' );
					const $ranksTable = $modal.find( '#mc-ranks-table' );
					const $ranksPagin = $modal.find( '#mc-ranks-pagination' );

					if ( r.data.ranks && r.data.ranks.length > 0 ) {
						$ranksEmpty.addClass( 'fstu-hidden' );
						$ranksTable.removeClass( 'fstu-hidden' );

						mcState.rankData = r.data.ranks;
						mcState.rankPage = 1;
						renderRanksTable();

						if ( mcState.rankData.length > mcState.rankPerPage ) {
							$ranksPagin.removeClass( 'fstu-hidden' );
						} else {
							$ranksPagin.addClass( 'fstu-hidden' );
						}
					} else {
						$ranksTable.addClass( 'fstu-hidden' );
						$ranksPagin.addClass( 'fstu-hidden' );
						$ranksEmpty.removeClass( 'fstu-hidden' );
					}
					// Заповнюємо вкладку "Суддівство"
					const $judgingList  = $modal.find( '#mc-val-judging-list' );
					const $judgingEmpty = $modal.find( '#mc-val-judging-empty' );
					const $judgingTable = $modal.find( '#mc-judging-table' );

					$judgingList.empty();
					if ( r.data.judging && r.data.judging.length > 0 ) {
						$judgingEmpty.addClass( 'fstu-hidden' );
						$judgingTable.removeClass( 'fstu-hidden' );

						let judgingHtml = '';
						r.data.judging.forEach( function( judge ) {
							judgingHtml += `
								<tr class="fstu-row">
									<td class="fstu-td" style="font-weight:600;">${ judge.category }</td>
									<td class="fstu-td" style="color: var(--fstu-text-light);">${ judge.date }</td>
								</tr>`;
						});
						$judgingList.html( judgingHtml );
					} else {
						$judgingTable.addClass( 'fstu-hidden' );
						$judgingEmpty.removeClass( 'fstu-hidden' );
					}

					// Показуємо/ховаємо вкладки вітрильництва
					const $tabSailing = $modal.find( '#mc-tab-sailing' );
					const $tabDuesSail = $modal.find( '#mc-tab-dues-sail' );

					if ( r.data.permissions && r.data.permissions.can_see_sailing ) {
						$tabSailing.removeClass( 'fstu-hidden' );
						$tabDuesSail.removeClass( 'fstu-hidden' );

						// Внески вітрильників
						mcState.duesSailData = r.data.dues_sail || [];
						mcState.duesSailPage = 1;
						renderDuesSailTable();

						// Вітрильні судна та посвідчення
						renderSailingTable( r.data.vessels || [], r.data.certs || [] );
					} else {
						$tabSailing.addClass( 'fstu-hidden' );
						$tabDuesSail.addClass( 'fstu-hidden' );
					}

					// Внески загальні
					mcState.duesData = r.data.dues || [];
					mcState.duesPage = 1;
					renderDuesTable();

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

					// Логіка для назви клубу (з посиланням або без)
					let nameHtml = r.data.name || '—';
					if ( r.data.www ) {
						nameHtml = `<a href="${ r.data.www }" target="_blank" style="color: var(--fstu-link); font-weight: 600;">${ nameHtml }</a>`;
						$( '#club-val-name' ).html( nameHtml );
					} else {
						$( '#club-val-name' ).text( nameHtml );
					}

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
		// Відкриття модалки по кнопці
		$( document ).on( 'click', '.fstu-btn--open-modal', function () {
			const modalId = $( this ).data( 'modal' );
			openModal( modalId );

			// Якщо це модалка протоколу - вантажимо дані
			if ( modalId === 'fstu-modal-protocol' ) {
				loadProtocolData();
			} else if ( modalId === 'fstu-modal-report' ) {
				loadReportData();
			}
		} );
		// Клік по кнопці "Редагувати" у випадаючому списку
		$( document ).on( 'click', '.action-edit-user', function ( e ) {
			e.preventDefault();
			const userId = $( this ).data( 'id' );
			openEditUserModal( userId );
		});

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

		// Закриття по кліку на оверлей
		$( document ).on( 'click', '.fstu-modal-overlay', function ( e ) {
			if ( $( e.target ).hasClass( 'fstu-modal-overlay' ) ) {
				closeModal( $( this ).attr( 'id' ) );
			}
		} );

		// Закриття по Escape
		$( document ).on( 'keydown', function ( e ) {
			if ( e.key === 'Escape' ) {
				$( '.fstu-modal-overlay:not(.fstu-hidden)' ).each( function () {
					closeModal( $( this ).attr( 'id' ) );
				} );
			}
		} );

		// Пагінація модалки: Досвід
		$( document ).on( 'click', '#mc-exp-prev', function () { if ( mcState.expPage > 1 ) { mcState.expPage--; renderExperienceTable(); } } );
		$( document ).on( 'click', '#mc-exp-next', function () { const total = Math.ceil( mcState.expData.length / mcState.expPerPage ); if ( mcState.expPage < total ) { mcState.expPage++; renderExperienceTable(); } } );

		// Пагінація модалки: Розряди
		$( document ).on( 'click', '#mc-rank-prev', function () { if ( mcState.rankPage > 1 ) { mcState.rankPage--; renderRanksTable(); } } );
		$( document ).on( 'click', '#mc-rank-next', function () { const total = Math.ceil( mcState.rankData.length / mcState.rankPerPage ); if ( mcState.rankPage < total ) { mcState.rankPage++; renderRanksTable(); } } );

		// Пагінація модалки: Внески
		$( document ).on( 'click', '#mc-dues-prev', function () { if ( mcState.duesPage > 1 ) { mcState.duesPage--; renderDuesTable(); } } );
		$( document ).on( 'click', '#mc-dues-next', function () { const total = Math.ceil( mcState.duesData.length / mcState.duesPerPage ); if ( mcState.duesPage < total ) { mcState.duesPage++; renderDuesTable(); } } );

		// Пагінація модалки: Внески (Вітрильні)
		$( document ).on( 'click', '#mc-dues-sail-prev', function () { if ( mcState.duesSailPage > 1 ) { mcState.duesSailPage--; renderDuesSailTable(); } } );
		$( document ).on( 'click', '#mc-dues-sail-next', function () { const total = Math.ceil( mcState.duesSailData.length / mcState.duesSailPerPage ); if ( mcState.duesSailPage < total ) { mcState.duesSailPage++; renderDuesSailTable(); } } );
		// Зміна кількості записів у протоколі
		$( document ).on( 'change', '#fstu-protocol-per-page', function() {
			state.protocol.per_page = parseInt( $( this ).val() );
			state.protocol.page = 1;
			loadProtocolData();
		});

		// Навігація сторінками протоколу
		$( document ).on( 'click', '#fstu-protocol-prev', function() {
			if ( state.protocol.page > 1 ) {
				state.protocol.page--;
				loadProtocolData();
			}
		});

		$( document ).on( 'click', '#fstu-protocol-next', function() {
			state.protocol.page++;
			loadProtocolData();
		});
		// Обробка збереження Клубу
		$( document ).on( 'submit', '#fstu-add-club-form', function( e ) {
			e.preventDefault();
			const $form  = $( this );
			const $alert = $( '#fstu-add-club-alert' );
			const $btn   = $( '#fstu-add-club-submit' );

			$btn.prop( 'disabled', true ).find( '.fstu-btn__text' ).text( 'Збереження...' );
			$alert.addClass( 'fstu-hidden' );

			$.ajax( {
				url: fstuRegistry.ajaxUrl,
				method: 'POST',
				data: $form.serialize() + '&action=fstu_save_user_club&nonce=' + fstuRegistry.nonce,
				success: function ( r ) {
					if ( r.success ) {
						closeModal( 'fstu-modal-add-club' );
						fetchRegistry(); // Оновлюємо таблицю
					} else {
						$alert.text( r.data?.message || 'Помилка' ).removeClass( 'fstu-hidden' ).css('color', 'red');
					}
				},
				complete: function() {
					// Скидаємо стан кнопки
					$btn.prop( 'disabled', false ).find( '.fstu-btn__text' ).text( '💾 Зберегти' );
				}
			});
		});
		// Збереження зміни ОФСТ
		$( document ).on( 'submit', '#fstu-change-ofst-form', function( e ) {
			e.preventDefault();
			const $form  = $( this );
			const $alert = $( '#fstu-ofst-alert' );
			const $btn   = $( '#fstu-ofst-submit' );

			$btn.prop( 'disabled', true ).find( '.fstu-btn__text' ).text( 'Збереження...' );
			$alert.addClass( 'fstu-hidden' );

			$.ajax( {
				url: fstuRegistry.ajaxUrl,
				method: 'POST',
				data: $form.serialize() + '&action=fstu_save_user_ofst&nonce=' + fstuRegistry.nonce,
				success: function ( r ) {
					if ( r.success ) {
						closeModal( 'fstu-modal-change-ofst' );
						fetchRegistry(); // Оновлюємо таблицю, щоб побачити нову назву ОФСТ у колонці
					} else {
						$alert.text( r.data?.message || 'Помилка' ).removeClass( 'fstu-hidden' ).css('color', 'red');
					}
				},
				complete: function() {
					$btn.prop( 'disabled', false ).find( '.fstu-btn__text' ).text( '💾 Зберегти зміни' );
				}
			});
		});
		// Збереження внеску
		$( document ).on( 'submit', '#fstu-add-dues-form', function( e ) {
			e.preventDefault();
			const $form  = $( this );
			const $alert = $( '#fstu-dues-alert' );
			const $btn   = $( '#fstu-dues-submit' );

			$btn.prop( 'disabled', true ).find( '.fstu-btn__text' ).text( 'Збереження...' );
			$alert.addClass( 'fstu-hidden' );

			$.ajax( {
				url: fstuRegistry.ajaxUrl,
				method: 'POST',
				data: $form.serialize() + '&action=fstu_save_user_dues&nonce=' + fstuRegistry.nonce,
				success: function ( r ) {
					if ( r.success ) {
						alert( r.data.message ); // Показуємо успіх
						closeModal( 'fstu-modal-add-dues' );
						fetchRegistry(); // Оновлюємо таблицю (щоб сума з'явилася в колонці)
					} else {
						$alert.text( r.data?.message || 'Помилка' ).removeClass( 'fstu-hidden' ).css('color', 'red');
					}
				},
				complete: function() {
					$btn.prop( 'disabled', false ).find( '.fstu-btn__text' ).text( '💾 Зберегти квитанцію' );
				}
			});
		});
	}

	// ─── Функції рендеру таблиць модалки ──────────────────────────────────────

	function renderExperienceTable() {
		const $list = $( '#mc-val-experience-list' );
		const total = mcState.expData.length;
		const totalPages = Math.ceil( total / mcState.expPerPage ) || 1;

		const start = ( mcState.expPage - 1 ) * mcState.expPerPage;
		const end   = Math.min( start + mcState.expPerPage, total );
		const pageData = mcState.expData.slice( start, end );

		let expHtml = '';
		pageData.forEach( function( exp ) {
			const urlHtml = exp.url
				? `<a href="${ exp.url }" target="_blank" class="fstu-action-link" style="font-size:12px;">посилання</a>`
				: '<span style="color:var(--fstu-text-light); font-size:12px;">відсутня</span>';

			expHtml += `
				<tr class="fstu-row">
					<td class="fstu-td" style="font-weight:600;">${ exp.category }</td>
					<td class="fstu-td" style="text-align:center;">${ exp.role }</td>
					<td class="fstu-td"><a href="/calendar/?ViewID=${ exp.event_id }" target="_blank">${ exp.event }</a></td>
					<td class="fstu-td">${ exp.tourism }</td>
					<td class="fstu-td" style="text-align:center; font-size:11px;">${ exp.dates }</td>
					<td class="fstu-td" style="text-align:center;">${ urlHtml }</td>
				</tr>`;
		});
		$list.html( expHtml );

		$( '#mc-exp-pagin-info' ).text( `Показано ${ start + 1 }–${ end } з ${ total } записів` );
		$( '#mc-exp-page-nums' ).text( `Стор. ${ mcState.expPage } з ${ totalPages }` );
		$( '#mc-exp-prev' ).prop( 'disabled', mcState.expPage <= 1 );
		$( '#mc-exp-next' ).prop( 'disabled', mcState.expPage >= totalPages );
	}

	function renderRanksTable() {
		const $list = $( '#mc-val-ranks-list' );
		const total = mcState.rankData.length;
		const totalPages = Math.ceil( total / mcState.rankPerPage ) || 1;

		const start = ( mcState.rankPage - 1 ) * mcState.rankPerPage;
		const end   = Math.min( start + mcState.rankPerPage, total );
		const pageData = mcState.rankData.slice( start, end );

		let ranksHtml = '';
		pageData.forEach( function( rank ) {
			const prikazHtml = rank.url
				? `<a href="${ rank.url }" target="_blank" style="font-weight:600;">${ rank.prikaz }</a>`
				: rank.prikaz;

			ranksHtml += `
				<tr class="fstu-row">
					<td class="fstu-td" style="font-weight:600;">${ rank.name }</td>
					<td class="fstu-td" style="text-align:center;">${ prikazHtml }</td>
					<td class="fstu-td">${ rank.tourism }</td>
					<td class="fstu-td"><a href="/calendar/?ViewID=${ rank.event_id }" target="_blank">${ rank.event }</a></td>
					<td class="fstu-td" style="text-align:center; font-size:11px;">${ rank.dates }</td>
				</tr>`;
		});
		$list.html( ranksHtml );

		$( '#mc-rank-pagin-info' ).text( `Показано ${ start + 1 }–${ end } з ${ total } записів` );
		$( '#mc-rank-page-nums' ).text( `Стор. ${ mcState.rankPage } з ${ totalPages }` );
		$( '#mc-rank-prev' ).prop( 'disabled', mcState.rankPage <= 1 );
		$( '#mc-rank-next' ).prop( 'disabled', mcState.rankPage >= totalPages );
	}

	function renderDuesTable() {
		const $list = $('#mc-val-dues-list');
		const total = mcState.duesData.length;
		const totalPages = Math.ceil( total / mcState.duesPerPage ) || 1;
		const start = (mcState.duesPage - 1) * mcState.duesPerPage;
		const pageData = mcState.duesData.slice(start, start + mcState.duesPerPage);

		let html = '';
		pageData.forEach(d => {
			let docHtml = '—';
			if ( d.Dues_URL ) {
				docHtml = `<a href="${ d.Dues_URL }" target="_blank" style="color:#2980b9; font-weight:600;">📄 Чек</a>`;
			} else if ( d.Dues_ShopBillid ) {
				docHtml = `<span style="color:#27ae60; font-weight:600;" title="ID: ${ d.Dues_ShopBillid } | Код: ${ d.Dues_ApprovalCode || '' }">еквайринг</span>`;
			}

			html += `<tr class="fstu-row">
				<td class="fstu-td" style="text-align:center;">${d.Year_Name}</td>
				<td class="fstu-td" style="text-align:right; font-weight:600;">${parseFloat(d.Dues_Summa).toFixed(2)}</td>
				<td class="fstu-td" style="text-align:center;">${d.DuesType_Name}</td>
				<td class="fstu-td" style="text-align:center;">${docHtml}</td>
				<td class="fstu-td">${d.financier} <br><small style="color:var(--fstu-text-light);">${d.Dues_DateCreate}</small></td>
			</tr>`;
		});

		$list.html(html);
		$('#mc-dues-table').toggleClass('fstu-hidden', total === 0);
		$('#mc-val-dues-empty').toggleClass('fstu-hidden', total > 0);

		if ( total > mcState.duesPerPage ) {
			$( '#mc-dues-pagination' ).removeClass( 'fstu-hidden' );
			$( '#mc-dues-pagin-info' ).text( `Показано ${ start + 1 }–${ Math.min( start + mcState.duesPerPage, total ) } з ${ total }` );
			$( '#mc-dues-page-nums' ).text( `Стор. ${ mcState.duesPage } з ${ totalPages }` );
			$( '#mc-dues-prev' ).prop( 'disabled', mcState.duesPage <= 1 );
			$( '#mc-dues-next' ).prop( 'disabled', mcState.duesPage >= totalPages );
		} else {
			$( '#mc-dues-pagination' ).addClass( 'fstu-hidden' );
		}
	}

	function renderDuesSailTable() {
		const $list = $( '#mc-val-dues-sail-list' );
		const total = mcState.duesSailData.length;
		const totalPages = Math.ceil( total / mcState.duesSailPerPage ) || 1;
		const start = ( mcState.duesSailPage - 1 ) * mcState.duesSailPerPage;
		const pageData = mcState.duesSailData.slice( start, start + mcState.duesSailPerPage );

		let html = '';
		pageData.forEach( d => {
			html += `<tr class="fstu-row">
				<td class="fstu-td" style="text-align:center;">${ d.Year_ID }</td>
				<td class="fstu-td" style="text-align:right; font-weight:600; color:#d35400;">${ parseFloat( d.DuesSail_Summa ).toFixed( 2 ) }</td>
				<td class="fstu-td" style="text-align:center;">${ d.DuesSail_DateCreate }</td>
				<td class="fstu-td">${ d.FIOCreate }</td>
			</tr>`;
		});

		$list.html( html );
		$( '#mc-dues-sail-table' ).toggleClass( 'fstu-hidden', total === 0 );
		$( '#mc-val-dues-sail-empty' ).toggleClass( 'fstu-hidden', total > 0 );

		if ( total > mcState.duesSailPerPage ) {
			$( '#mc-dues-sail-pagination' ).removeClass( 'fstu-hidden' );
			$( '#mc-dues-sail-pagin-info' ).text( `Показано ${ start + 1 }–${ Math.min( start + mcState.duesSailPerPage, total ) } з ${ total }` );
			$( '#mc-dues-sail-page-nums' ).text( `Стор. ${ mcState.duesSailPage } з ${ totalPages }` );
			$( '#mc-dues-sail-prev' ).prop( 'disabled', mcState.duesSailPage <= 1 );
			$( '#mc-dues-sail-next' ).prop( 'disabled', mcState.duesSailPage >= totalPages );
		} else {
			$( '#mc-dues-sail-pagination' ).addClass( 'fstu-hidden' );
		}
	}

	function renderSailingTable( vessels, certs ) {
		let vHtml = '';
		vessels.forEach( v => {
			vHtml += `<tr class="fstu-row">
				<td class="fstu-td" style="font-weight:600;">${ v.Sailboat_Name }<br><small style="color:var(--fstu-text-light); font-weight:normal;">${ v.RegNumber }</small></td>
				<td class="fstu-td" style="text-align:center; font-weight:600;">${ v.Sailboat_NumberSail || '—' }</td>
				<td class="fstu-td">${ v.Verification_Name }</td>
				<td class="fstu-td" style="text-align:right; color:#d35400;">${ parseFloat( v.AppShipTicket_Summa || 0 ).toFixed( 2 ) }</td>
			</tr>`;
		});
		$( '#mc-val-vessels-list' ).html( vHtml );
		$( '#mc-vessels-table' ).toggleClass( 'fstu-hidden', vessels.length === 0 );
		$( '#mc-val-vessels-empty' ).toggleClass( 'fstu-hidden', vessels.length > 0 );

		let cHtml = '';
		certs.forEach( c => {
			cHtml += `<tr class="fstu-row">
				<td class="fstu-td"><b>${ c.type }</b><br><span style="color:var(--fstu-primary);">${ c.num }</span></td>
				<td class="fstu-td">${ c.status }</td>
				<td class="fstu-td" style="text-align:center;">${ c.date || '—' }</td>
			</tr>`;
		});
		$( '#mc-val-certs-list' ).html( cHtml );
		$( '#mc-certs-table' ).toggleClass( 'fstu-hidden', certs.length === 0 );
		$( '#mc-val-certs-empty' ).toggleClass( 'fstu-hidden', certs.length > 0 );
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
	function loadProtocolData() {
		const $loader  = $( '#fstu-protocol-loader' );
		const $alert   = $( '#fstu-protocol-alert' );
		const $content = $( '#fstu-protocol-content' );
		const $tbody   = $( '#fstu-protocol-tbody' );

		$content.addClass( 'fstu-hidden' );
		$alert.addClass( 'fstu-hidden' );
		$loader.removeClass( 'fstu-hidden' );

		$.ajax({
			url:    fstuRegistry.ajaxUrl,
			method: 'POST',
			data:   {
				action:   'fstu_get_protocol',
				nonce:    fstuRegistry.nonce,
				log_name: state.protocol.log_name,
				page:     state.protocol.page,
				per_page: state.protocol.per_page
			},
			success: function ( r ) {
				$loader.addClass( 'fstu-hidden' );
				if ( r.success ) {
					$tbody.html( r.data.html );
					$content.removeClass( 'fstu-hidden' );

					// Оновлення інфо пагінації
					const totalPages = r.data.total_pages;
					$( '#fstu-protocol-pagin-info' ).text( `Всього записів: ${ r.data.total }` );
					$( '#fstu-protocol-page-nums' ).text( `Стор. ${ r.data.page } з ${ totalPages }` );

					// Активація кнопок
					$( '#fstu-protocol-prev' ).prop( 'disabled', r.data.page <= 1 );
					$( '#fstu-protocol-next' ).prop( 'disabled', r.data.page >= totalPages );
				} else {
					$alert.text( r.data?.message || 'Помилка завантаження' ).removeClass( 'fstu-hidden' );
				}
			}
		});
	}
	function loadReportData() {
		const $loader  = $( '#fstu-report-loader' );
		const $alert   = $( '#fstu-report-alert' );
		const $content = $( '#fstu-report-content' );
		const $thead   = $( '#fstu-report-thead' );
		const $tbody   = $( '#fstu-report-tbody' );
		const $title   = $( '#fstu-report-year-title' );

		$content.addClass( 'fstu-hidden' );
		$alert.addClass( 'fstu-hidden' );
		$loader.removeClass( 'fstu-hidden' );

		$.ajax( {
			url:    fstuRegistry.ajaxUrl,
			method: 'POST',
			data:   {
				action: 'fstu_get_report',
				nonce: fstuRegistry.nonce,
				year: state.filters.year // Передаємо рік, який зараз вибраний у фільтрі
			},
			success: function ( r ) {
				$loader.addClass( 'fstu-hidden' );
				if ( r.success ) {
					$thead.html( r.data.thead );
					$tbody.html( r.data.tbody );
					$title.text( `За ${ r.data.year } рік` );
					$content.removeClass( 'fstu-hidden' );
				} else {
					$alert.text( r.data?.message || 'Помилка завантаження звіту' ).removeClass( 'fstu-hidden' );
				}
			},
			error: function () {
				$loader.addClass( 'fstu-hidden' );
				$alert.text( fstuRegistry.strings.errorGeneric ).removeClass( 'fstu-hidden' );
			}
		} );
	}
	function openEditUserModal( userId ) {
		openModal( 'fstu-modal-edit-user' );

		const $loader = $( '#fstu-edit-loader' );
		const $alert  = $( '#fstu-edit-alert' );
		const $form   = $( '#fstu-edit-user-form' );

		// Скидаємо кнопку про всяк випадок при кожному відкритті
		$( '#fstu-edit-submit' ).prop( 'disabled', false ).find( '.fstu-btn__text' ).text( '💾 Зберегти зміни' );

		$form.addClass( 'fstu-hidden' );
		$alert.addClass( 'fstu-hidden' );
		$loader.removeClass( 'fstu-hidden' );
		$( '#edit_user_id' ).val( userId );

		$.ajax( {
			url: fstuRegistry.ajaxUrl,
			method: 'POST',
			data: { action: 'fstu_get_user_edit_data', nonce: fstuRegistry.nonce, user_id: userId },
			success: function ( r ) {
				$loader.addClass( 'fstu-hidden' );
				if ( r.success ) {
					// Заповнюємо поля
					Object.keys( r.data ).forEach( key => {
						$form.find( `[name="${ key }"]` ).val( r.data[ key ] );
					});
					$form.removeClass( 'fstu-hidden' );
				} else {
					$alert.text( r.data?.message || 'Помилка завантаження' ).removeClass( 'fstu-hidden' );
				}
			}
		});
	}

	// Обробка збереження форми
	$( document ).on( 'submit', '#fstu-edit-user-form', function( e ) {
		e.preventDefault();
		const $form  = $( this );
		const $alert = $( '#fstu-edit-alert' );
		const $btn   = $( '#fstu-edit-submit' );

		$btn.prop( 'disabled', true ).find( '.fstu-btn__text' ).text( 'Збереження...' );
		$alert.addClass( 'fstu-hidden' );

		$.ajax( {
			url: fstuRegistry.ajaxUrl,
			method: 'POST',
			data: $form.serialize() + '&action=fstu_save_user_edit_data&nonce=' + fstuRegistry.nonce,
			success: function ( r ) {
				if ( r.success ) {
					closeModal( 'fstu-modal-edit-user' );
					fetchRegistry(); // Оновлюємо таблицю, щоб побачити нове ПІБ

					// ВАЖЛИВО: Скидаємо стан кнопки для наступних відкриттів!
					$btn.prop( 'disabled', false ).find( '.fstu-btn__text' ).text( '💾 Зберегти зміни' );
				} else {
					$alert.text( r.data?.message || 'Помилка збереження' ).removeClass( 'fstu-hidden' ).css('color', 'red');
					$btn.prop( 'disabled', false ).find( '.fstu-btn__text' ).text( '💾 Зберегти зміни' );
				}
			}
		});
	});
	// ─── Обробка меню опцій (Розумне позиціонування) ──────────────────────────
	$( document ).on( 'click', '.fstu-opts-btn', function ( e ) {
		e.stopPropagation();
		const $parent = $( this ).parent();

		// Закриваємо всі інші відкриті меню
		$( '.fstu-opts' ).not( $parent ).removeClass( 'fstu-opts--open' );

		// Розумне відкриття (вгору чи вниз)
		const menuHeight = 280; // Приблизна висота меню
		const windowHeight = $( window ).height();
		const rect = this.getBoundingClientRect();

		// Якщо знизу немає місця, але зверху є — відкриваємо вгору
		if ( rect.bottom + menuHeight > windowHeight && rect.top > menuHeight ) {
			$parent.addClass( 'fstu-dropup' );
		} else {
			$parent.removeClass( 'fstu-dropup' );
		}

		$parent.toggleClass( 'fstu-opts--open' );
	} );

	// Закриття меню при кліку в будь-якому іншому місці
	$( document ).on( 'click', function () {
		$( '.fstu-opts' ).removeClass( 'fstu-opts--open' ).removeClass( 'fstu-dropup' );
	} );

	// ─── Старт ────────────────────────────────────────────────────────────────
	init();

} );