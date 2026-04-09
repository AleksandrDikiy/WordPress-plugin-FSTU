/**
 * JS модуля "Реєстр мерилок".
 * Live-калькулятор ГБ та обробка модальних вікон.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-09
 *
 * @package FSTU
 */

jQuery( document ).ready( function( $ ) {
	'use strict';

	// Надійний парсер для JS (аналог PHP parse_float)
	function parseFloatSafe( val ) {
		if ( typeof val === 'undefined' || val === null ) return 0;
		var normalized = String( val ).replace( /,/g, '.' ).replace( /[^0-9\.-]/g, '' );
		var parsed = parseFloat( normalized );
		return isNaN( parsed ) ? 0 : parsed;
	}

	// Головна функція перерахунку
	function calculateGB() {
		var BC = 0;
		var CMP = 0;

		// Читання значень з інпутів
		var crewNum    = parseFloatSafe( $('#MR_GrevNumber').val() );
		var crewWeight = parseFloatSafe( $('#MR_CrewWeight').val() );
		var weight     = parseFloatSafe( $('#MR_Weight').val() );
		var weightMotor= parseFloatSafe( $('#MR_WeightMotor').val() );
		var length     = parseFloatSafe( $('#MR_Length').val() );

		var mastPPD = parseFloatSafe( $('#MR_Machta_PPD').val() );
		var mastPRD = parseFloatSafe( $('#MR_Machta_PRD').val() );
		var liktros = parseFloatSafe( $('#MR_Liktros').val() );

		var grotP = parseFloatSafe( $('#MR_Grot_P').val() );
		var grotB = parseFloatSafe( $('#MR_Grot_B').val() );
		var grotE = parseFloatSafe( $('#MR_Grot_E').val() );
		var grotHP = parseFloatSafe( $('#MR_Grot_HP').val() );
		var grotHB = parseFloatSafe( $('#MR_Grot_HB').val() );
		var grotHE = parseFloatSafe( $('#MR_Grot_HE').val() );
		var grotVLM = parseFloatSafe( $('#MR_Grot_VLM').val() );

		// ... (Аналогічне читання для Стакселя, Клівера та Спінакера)
		var stakP = parseFloatSafe( $('#MR_Staksel_P').val() );
		var stakB = parseFloatSafe( $('#MR_Staksel_B').val() );
		var stakE = parseFloatSafe( $('#MR_Staksel_E').val() );
		var stakHP = parseFloatSafe( $('#MR_Staksel_HP').val() );
		var stakHB = parseFloatSafe( $('#MR_Staksel_HB').val() );
		var stakHE = parseFloatSafe( $('#MR_Staksel_HE').val() );
		var stakVLM = parseFloatSafe( $('#MR_Staksel_VLM').val() );

		var klivP = parseFloatSafe( $('#MR_Kliver_P').val() );
		var klivB = parseFloatSafe( $('#MR_Kliver_B').val() );
		var klivE = parseFloatSafe( $('#MR_Kliver_E').val() );
		var klivHP = parseFloatSafe( $('#MR_Kliver_HP').val() );
		var klivHB = parseFloatSafe( $('#MR_Kliver_HB').val() );
		var klivHE = parseFloatSafe( $('#MR_Kliver_HE').val() );
		var klivVLM = parseFloatSafe( $('#MR_Kliver_VLM').val() );

		var spinP = parseFloatSafe( $('#MR_Spinaker_P').val() );
		var spinB = parseFloatSafe( $('#MR_Spinaker_B').val() );
		var spinE = parseFloatSafe( $('#MR_Spinaker_E').val() );
		var spinSMW = parseFloatSafe( $('#MR_Spinaker_SMW').val() );

		// Розрахунок площ (Формула Герона)
		var areaGrot = 0;
		if ( grotP > 0 && grotB > 0 && grotE > 0 && grotVLM > 0 ) {
			var machta = (mastPRD === mastPPD) ? (mastPRD - mastPPD + liktros) : (mastPRD - mastPPD - liktros);
			var pGrot = (grotP + grotB + grotE) / 2;
			var baseGrot = pGrot * (pGrot - grotP) * (pGrot - grotB) * (pGrot - grotE);
			areaGrot = (baseGrot > 0 ? Math.sqrt(baseGrot) : 0) + (2/3 * grotP * grotHP) + (2/3 * grotB * grotHB) + (2/3 * grotE * grotHE) + (grotP * machta);
		}

		var areaStak = 0;
		if ( stakP > 0 && stakB > 0 && stakE > 0 && stakVLM > 0 ) {
			var pStak = (stakP + stakB + stakE) / 2;
			var baseStak = pStak * (pStak - stakP) * (pStak - stakB) * (pStak - stakE);
			areaStak = (baseStak > 0 ? Math.sqrt(baseStak) : 0) + (2/3 * stakP * stakHP) + (2/3 * stakB * stakHB) + (2/3 * stakE * stakHE);
		}

		var areaKliv = 0;
		if ( klivP > 0 && klivB > 0 && klivE > 0 && klivVLM > 0 ) {
			var pKliv = (klivP + klivB + klivE) / 2;
			var baseKliv = pKliv * (pKliv - klivP) * (pKliv - klivB) * (pKliv - klivE);
			areaKliv = (baseKliv > 0 ? Math.sqrt(baseKliv) : 0) + (2/3 * klivP * klivHP) + (2/3 * klivB * klivHB) + (2/3 * klivE * klivHE);
		}

		var areaSpin = 0, spinSMWE = 0;
		if ( spinP > 0 && spinB > 0 && spinE > 0 && spinSMW > 0 ) {
			spinSMWE = (spinSMW / spinE) * 100;
			areaSpin = Math.pow(spinP + spinB + spinE, 2) / 16;
		}

		var mainSail = (areaGrot > 0) ? (areaGrot + areaStak + areaKliv) : 0;
		var spinMainSail = (areaSpin > 0 && mainSail > 0) ? (areaSpin / mainSail * 100) : 0;

		// Ефективність
		var xm = (grotVLM > 0 && areaGrot > 0) ? (Math.pow(grotVLM, 2) / areaGrot) : 0;
		var m = areaGrot * (40.1 + 18.31 * xm - 2.016 * Math.pow(xm, 2) + 0.07472 * Math.pow(xm, 3)) / 100;

		var xj = (stakVLM > 0 && areaStak > 0) ? (Math.pow(stakVLM, 2) / areaStak) : 0;
		var mj = (xj > 0) ? (40.1 + 18.31 * xj - 2.016 * Math.pow(xj, 2) + 0.07472 * Math.pow(xj, 3)) : 0;

		var xk = (klivVLM > 0 && areaKliv > 0) ? (Math.pow(klivVLM, 2) / areaKliv) : 0;
		var mk = (xk > 0) ? (40.1 + 18.31 * xk - 2.016 * Math.pow(xk, 2) + 0.07472 * Math.pow(xk, 3)) : 0;

		// Внутрішня функція розрахунку одного ГБ
		function calcSingleGB( wTotal, jVal ) {
			if ( length <= 0 ) return 0;
			var k = areaKliv * mk / 100;
			var a = m + jVal + k;
			if ( a <= 0 ) return 0;

			var zm2 = Math.sqrt( wTotal * length ) / a;
			var dlr = wTotal / Math.pow( length, 3 );
			
			var xc4 = 1 + (0.0061012 * zm2 * length * dlr);
			var xc2 = 0.4556343 - (0.473292 * zm2 * (1.038881 + (0.4371713 * dlr)));
			var xc = (-0.0414213 + (-2.554547 * zm2 / length) + (0.00132305 * zm2 * Math.pow(length, 2)));

			var disc = Math.pow(xc2, 2) - 4 * xc4 * xc;
			if ( disc < 0 || xc4 === 0 ) return 0;

			var vt_vb = Math.sqrt( (-xc2 + Math.sqrt(disc)) / (2 * xc4) );
			var r = 0.8 * vt_vb * (1 - (BC + CMP) / 100);
			return r > 0 ? (1 / r) : 0;
		}

		// Розрахунок 4 варіантів ГБ
		var w1 = (crewNum * 75) + weight + weightMotor;
		var j1 = (areaStak > 0) ? (areaStak * mj / 100) + (0.1 * (areaSpin - areaStak - areaKliv)) : 0;
		var gbSpin = (areaSpin > 0) ? calcSingleGB( w1, j1 ) : 0;

		var j2 = (areaStak > 0) ? (areaStak * mj / 100) : 0;
		var gb = calcSingleGB( w1, j2 );

		var w3 = weight + weightMotor + crewWeight;
		var j3 = (areaStak > 0) ? (areaStak * mj / 100) + (0.1 * (areaSpin - areaStak)) : 0;
		var gbCrewSpin = (areaSpin > 0) ? calcSingleGB( w3, j3 ) : 0;

		var j4 = (areaStak > 0) ? (areaStak * mj / 100) : 0;
		var gbCrew = calcSingleGB( w3, j4 );

		// Оновлення DOM (Виводимо розраховані значення у фіксований підвал та інпути)
		$('#MR_Area_Grot').val( areaGrot.toFixed(1) );
		$('#MR_Area_Staksel').val( areaStak.toFixed(1) );
		$('#MR_Area_Kliver').val( areaKliv.toFixed(1) );
		$('#MR_Area_Spinaker').val( areaSpin.toFixed(1) );
		$('#MR_Spinaker_SMW_E').val( spinSMWE.toFixed(0) );
		$('#MR_Main_Sail').val( mainSail.toFixed(1) );
		$('#MR_Spinaker_MainSail').val( spinMainSail.toFixed(1) );

		$('#MR_GB_Spinaker').val( gbSpin.toFixed(3) );
		$('#MR_GB').val( gb.toFixed(3) );
		$('#MR_GB_CrewWeight_Spinaker').val( gbCrewSpin.toFixed(3) );
		$('#MR_GB_CrewWeight').val( gbCrew.toFixed(3) );
	}

	// Делегування подій для миттєвого перерахунку
	$( document ).on( 'input', '.fstu-merilka-calc-input', function() {
		// Очищуємо поле від зайвих символів прямо під час вводу (UX)
		var val = $(this).val().replace(/,/g, '.').replace(/[^0-9\.-]/g, '');
		$(this).val(val);
		
		calculateGB();
	});

	// Обробка натискання кнопки "Друк" для конкретної мерилки
	$( document ).on( 'click', '.fstu-merilkas-print-btn', function( event ) {
		event.preventDefault();
		const mrId = parseInt( $( this ).data( 'mr-id' ), 10 ) || 0;
		
		if ( mrId > 0 ) {
			// Формуємо URL для друку (використовуємо ajaxUrl з основного модуля)
			const printUrl = fstuMerilkasL10n.ajaxUrl + '?action=fstu_merilkas_print&mr_id=' + mrId;
			
			// Відкриваємо нову вкладку браузера
			window.open( printUrl, '_blank', 'width=900,height=800,toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes' );
		}
	} );
// ================= ДОДАТИ ЦЕЙ БЛОК =================
	// Обробка натискання кнопки "Створити копію" (Клонування)
	$( document ).on( 'click', '.fstu-merilkas-clone-btn', function( event ) {
		event.preventDefault();
		const mrId = parseInt( $( this ).data( 'mr-id' ), 10 ) || 0;
		
		if ( mrId <= 0 ) return;

		// 1. Відкриваємо модалку та змінюємо заголовок
		$( '#fstu-merilkas-form-modal' ).removeClass( 'fstu-hidden' ).attr( 'aria-hidden', 'false' );
		$( 'body' ).addClass( 'fstu-modal-open' );
		$( '#fstu-merilkas-form-title' ).text( 'Нове свідоцтво (на основі старого)' );

		// 2. Робимо AJAX запит для отримання даних старої мерилки
		$.ajax({
			url: fstuMerilkasL10n.ajaxUrl, // Використовуємо глобальний URL
			method: 'POST',
			data: {
				action: 'fstu_merilkas_get_single',
				nonce: fstuMerilkasL10n.nonce, // Змініть на свій nonce, якщо він відрізняється
				mr_id: mrId
			}
		}).done(function( response ) {
			if ( response.success && response.data && response.data.item ) {
				const item = response.data.item;

				// Очищаємо форму від попередніх даних
				$( '#fstu-merilkas-form' )[0].reset();

				// ВАЖЛИВО: mr_id ставимо 0, щоб при збереженні створився НОВИЙ запис!
				$( '#fstu-merilkas-item-id' ).val( 0 ); 
				$( '#fstu-merilkas-sailboat-id' ).val( item.Sailboat_ID );

				// Ставимо поточну дату для нового обміру
				const today = new Date().toISOString().split('T')[0];
				$( '#MR_DateObmera' ).val( today );

				// Масив усіх полів, які треба перенести
				const fieldsToCopy = [
					'MR_GrevNumber', 'MR_CrewWeight', 'MR_Weight', 'MR_WeightMotor', 'MR_Length',
					'MR_Machta_PPD', 'MR_Machta_PRD', 'MR_Liktros', 
					'MR_Grot_P', 'MR_Grot_B', 'MR_Grot_E', 'MR_Grot_HP', 'MR_Grot_HB', 'MR_Grot_HE', 'MR_Grot_VLM',
					'MR_Staksel_P', 'MR_Staksel_B', 'MR_Staksel_E', 'MR_Staksel_HP', 'MR_Staksel_HB', 'MR_Staksel_HE', 'MR_Staksel_VLM',
					'MR_Kliver_P', 'MR_Kliver_B', 'MR_Kliver_E', 'MR_Kliver_HP', 'MR_Kliver_HB', 'MR_Kliver_HE', 'MR_Kliver_VLM',
					'MR_Spinaker_P', 'MR_Spinaker_B', 'MR_Spinaker_E', 'MR_Spinaker_SMW'
				];

				// Заповнюємо інпути
				fieldsToCopy.forEach(function( field ) {
					if ( item[field] !== null && item[field] !== undefined ) {
						$( '#' + field ).val( item[field] );
					}
				});

				// Викликаємо нашу функцію калькулятора, щоб миттєво перерахувати ГБ та площі
				if ( typeof calculateGB === 'function' ) {
					calculateGB();
				}

			} else {
				alert( response.data?.message || 'Помилка завантаження даних для клонування.' );
			}
		}).fail(function() {
			alert( 'Помилка з\'єднання з сервером.' );
		});
	});
	// ===================================================
	// Відкриття форми створення НОВОЇ мерилки
	$( document ).on( 'click', '#fstu-merilkas-add-btn', function( event ) {
		event.preventDefault();
		
		// Беремо ID судна з контейнера вкладки
		const sailboatId = parseInt( $( '#fstu-merilkas-tab-container' ).data( 'sailboat-id' ), 10 ) || 0;
		if ( sailboatId <= 0 ) return;

		// Відкриваємо модалку та змінюємо заголовок
		$( '#fstu-merilkas-form-modal' ).removeClass( 'fstu-hidden' ).attr( 'aria-hidden', 'false' );
		$( 'body' ).addClass( 'fstu-modal-open' );
		$( '#fstu-merilkas-form-title' ).text( 'Нове обмірне свідоцтво' );

		// Очищаємо форму повністю
		$( '#fstu-merilkas-form' )[0].reset();
		$( '#fstu-merilkas-item-id' ).val( 0 ); 
		$( '#fstu-merilkas-sailboat-id' ).val( sailboatId );

		// Ставимо сьогоднішню дату за замовчуванням
		const today = new Date().toISOString().split('T')[0];
		$( '#MR_DateObmera' ).val( today );

		// Скидаємо калькулятор (щоб там скрізь стали нулі)
		if ( typeof calculateGB === 'function' ) {
			calculateGB();
		}
	});
	// Допоміжна функція: перезавантаження вкладки у Судновому реєстрі
	function reloadMerilkasTab() {
		const $container = $( '#fstu-merilkas-tab-container' );
		if ( $container.length ) {
			$container.removeClass( 'is-loaded' );
			// Клікаємо на активну вкладку, щоб запустився існуючий AJAX-обробник Lazy Loading
			$( '.fstu-tab-btn[data-tab="merilka"]' ).trigger( 'click' );
		}
	}

	// ЗБЕРЕЖЕННЯ (CREATE / UPDATE)
	$( document ).on( 'submit', '#fstu-merilkas-form', function( event ) {
		event.preventDefault();

		const $form = $( this );
		const $submitBtn = $( '#fstu-merilkas-form-submit' );
		const $msg = $( '#fstu-merilkas-form-message' );

		$submitBtn.prop( 'disabled', true );
		$msg.removeClass( 'fstu-hidden fstu-form-message--success fstu-form-message--error' ).text( 'Збереження...' );

		const formData = $form.serializeArray();
		formData.push({ name: 'action', value: 'fstu_merilkas_save' });

		$.ajax({
			url: fstuMerilkasL10n.ajaxUrl,
			method: 'POST',
			data: formData
		}).done(function( response ) {
			if ( response.success ) {
				$msg.addClass( 'fstu-form-message--success' ).text( response.data.message );
				setTimeout(function() {
					// Закриваємо модалку форми
					$( '#fstu-merilkas-form-modal' ).addClass( 'fstu-hidden' ).attr( 'aria-hidden', 'true' );
					if ( ! $( '.fstu-modal-overlay:not(.fstu-hidden)' ).length ) {
						$( 'body' ).removeClass( 'fstu-modal-open' );
					}
					// Перезавантажуємо таблицю мерилок!
					reloadMerilkasTab();
				}, 700);
			} else {
				$msg.addClass( 'fstu-form-message--error' ).text( response.data.message );
				$submitBtn.prop( 'disabled', false );
			}
		}).fail(function() {
			$msg.addClass( 'fstu-form-message--error' ).text( 'Помилка з\'єднання з сервером.' );
			$submitBtn.prop( 'disabled', false );
		});
	});

	// ВИДАЛЕННЯ (DELETE)
	$( document ).on( 'click', '.fstu-merilkas-delete-btn', function( event ) {
		event.preventDefault();
		const mrId = parseInt( $( this ).data( 'mr-id' ), 10 ) || 0;
		const sailboatId = parseInt( $( '#fstu-merilkas-tab-container' ).data( 'sailboat-id' ), 10 ) || 0;

		if ( mrId > 0 && confirm( 'Ви впевнені, що хочете видалити це свідоцтво? Операція незворотна.' ) ) {
			$.ajax({
				url: fstuMerilkasL10n.ajaxUrl,
				method: 'POST',
				data: {
					action: 'fstu_merilkas_delete',
					nonce: fstuMerilkasL10n.nonce,
					mr_id: mrId,
					sailboat_id: sailboatId
				}
			}).done(function( response ) {
				if ( response.success ) {
					reloadMerilkasTab();
				} else {
					alert( response.data.message || 'Помилка видалення.' );
				}
			}).fail(function() {
				alert( 'Помилка з\'єднання з сервером.' );
			});
		}
	});

	// ПЕРЕГЛЯД / РЕДАГУВАННЯ (Завантаження даних у форму)
	$( document ).on( 'click', '.fstu-merilkas-edit-btn', function( event ) {
		event.preventDefault();
		const mrId = parseInt( $( this ).data( 'mr-id' ), 10 ) || 0;
		if ( mrId <= 0 ) return;

		$( '#fstu-merilkas-form-modal' ).removeClass( 'fstu-hidden' ).attr( 'aria-hidden', 'false' );
		$( 'body' ).addClass( 'fstu-modal-open' );
		$( '#fstu-merilkas-form-title' ).text( 'Редагування свідоцтва' );
		$( '#fstu-merilkas-form' )[0].reset();

		$.ajax({
			url: fstuMerilkasL10n.ajaxUrl,
			method: 'POST',
			data: { action: 'fstu_merilkas_get_single', nonce: fstuMerilkasL10n.nonce, mr_id: mrId }
		}).done(function( response ) {
			if ( response.success && response.data.item ) {
				const item = response.data.item;
				$( '#fstu-merilkas-item-id' ).val( item.MR_ID );
				$( '#fstu-merilkas-sailboat-id' ).val( item.Sailboat_ID );
				// Заповнюємо всі поля (беремо логіку з клонування)
				const fields = [ 'MR_DateObmera', 'MR_GrevNumber', 'MR_CrewWeight', 'MR_Weight', 'MR_WeightMotor', 'MR_Length', 'MR_Machta_PPD', 'MR_Machta_PRD', 'MR_Liktros', 'MR_Grot_P', 'MR_Grot_B', 'MR_Grot_E', 'MR_Grot_HP', 'MR_Grot_HB', 'MR_Grot_HE', 'MR_Grot_VLM', 'MR_Staksel_P', 'MR_Staksel_B', 'MR_Staksel_E', 'MR_Staksel_HP', 'MR_Staksel_HB', 'MR_Staksel_HE', 'MR_Staksel_VLM', 'MR_Kliver_P', 'MR_Kliver_B', 'MR_Kliver_E', 'MR_Kliver_HP', 'MR_Kliver_HB', 'MR_Kliver_HE', 'MR_Kliver_VLM', 'MR_Spinaker_P', 'MR_Spinaker_B', 'MR_Spinaker_E', 'MR_Spinaker_SMW'];
				fields.forEach(f => { if(item[f] !== null) $('#'+f).val(item[f]); });
				if ( typeof calculateGB === 'function' ) calculateGB();
			} else {
				alert( response.data?.message || 'Помилка.' );
			}
		});
	});
	// ===================================================
}); // Це остання дужка всього файлу