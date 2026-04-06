/**
 * JavaScript модуля "Довідник осередків ФСТУ".
 * AJAX-запити, фільтри, модальні вікна, CRUD-операції.
 *
 * Version:     1.0.1
 * Date_update: 2026-04-06
 *
 * @package FSTU
 */

jQuery(document).ready(function($) {
	'use strict';

	const $root = $('#fstu-units');
	if (!$root.length || typeof fstuUnitsL10n === 'undefined') {
		return;
	}

	const state = {
		currentPage: 1,
		perPage: parseInt($('#fstu-units-per-page').val(), 10) || 15,
		totalPages: 1,
		isLoading: false,
	};

	const permissions = fstuUnitsL10n.permissions || {
		canView: true,
		canManage: false,
		canDelete: false,
	};

	bindEvents();
	loadUnits();

	function bindEvents() {
		$('#fstu-units-add-btn').on('click', openAddForm);
		$('#fstu-units-refresh-btn').on('click', function() {
			loadUnits();
		});

		$('#fstu-units-search').on('input', debounce(function() {
			toggleSearchClear();
			state.currentPage = 1;
			loadUnits();
		}, 300));

		$('#fstu-units-search-clear').on('click', function() {
			$('#fstu-units-search').val('');
			toggleSearchClear();
			state.currentPage = 1;
			loadUnits();
			$('#fstu-units-search').trigger('focus');
		});

		$('#fstu-units-filter-region, #fstu-units-filter-type').on('change', function() {
			state.currentPage = 1;
			loadUnits();
		});

		$('#fstu-units-per-page').on('change', function() {
			state.perPage = parseInt($(this).val(), 10) || 15;
			state.currentPage = 1;
			loadUnits();
		});

		$('#fstu-units-prev-page').on('click', function() {
			if (state.currentPage > 1) {
				state.currentPage -= 1;
				loadUnits();
			}
		});

		$('#fstu-units-next-page').on('click', function() {
			if (state.currentPage < state.totalPages) {
				state.currentPage += 1;
				loadUnits();
			}
		});

		$(document).on('click', '.fstu-btn-action--view', handleViewUnit);
		$(document).on('click', '.fstu-btn-action--edit', handleEditUnit);
		$(document).on('click', '.fstu-btn-action--delete', handleDeleteUnit);

		$('#fstu-units-modal-view-close').on('click', closeViewModal);
		$('#fstu-units-modal-form-close, #fstu-units-form-cancel').on('click', closeFormModal);

		$('#fstu-units-modal-view').on('click', function(event) {
			if (event.target === this) {
				closeViewModal();
			}
		});

		$('#fstu-units-modal-form').on('click', function(event) {
			if (event.target === this) {
				closeFormModal();
			}
		});

		$('#fstu-units-form').on('submit', handleFormSubmit);
		$('#fstu-units-region').on('change', function() {
			loadCitiesByRegion($(this).val());
		});
	}

	function loadUnits() {
		if (state.isLoading) {
			return;
		}

		state.isLoading = true;
		setTableLoading();

		$.ajax({
			type: 'POST',
			url: fstuUnitsL10n.ajaxUrl,
			dataType: 'json',
			data: {
				action: 'fstu_get_units',
				nonce: fstuUnitsL10n.nonce,
				search: $('#fstu-units-search').val(),
				region_id: $('#fstu-units-filter-region').val(),
				unit_type: $('#fstu-units-filter-type').val(),
				page: state.currentPage,
				per_page: state.perPage,
			},
			success: function(response) {
				if (!response || !response.success || !response.data) {
					showTableError(fstuUnitsL10n.messages.error);
					return;
				}

				renderTable(response.data.units || []);
				updatePagination(response.data);
			},
			error: function() {
				showTableError(fstuUnitsL10n.messages.error);
			},
			complete: function() {
				state.isLoading = false;
			},
		});
	}

	function renderTable(units) {
		const $tbody = $('#fstu-units-tbody');
		const colspan = getTableColspan();

		if (!units.length) {
			$tbody.html(
				'<tr class="fstu-row">' +
					'<td colspan="' + colspan + '" class="fstu-no-results">' + escapeHtml(fstuUnitsL10n.messages.noResults) + '</td>' +
				'</tr>'
			);
			return;
		}

		let html = '';

		units.forEach(function(unit, index) {
			const rowNumber = ((state.currentPage - 1) * state.perPage) + index + 1;
			const annualFee = normalizeMoney(unit.Unit_AnnualFee);

			html += '<tr class="fstu-row">';
			html += '<td class="fstu-td fstu-td--num">' + rowNumber + '</td>';
			html += '<td class="fstu-td"><strong>' + escapeHtml(unit.Unit_Name || '') + '</strong></td>';
			html += '<td class="fstu-td">' + escapeHtml(unit.Unit_ShortName || '') + '</td>';
			html += '<td class="fstu-td">' + escapeHtml(unit.OPF_NameShort || '') + '</td>';
			html += '<td class="fstu-td">' + escapeHtml(unit.UnitType_Name || '') + '</td>';
			html += '<td class="fstu-td">' + escapeHtml(unit.Region_Name || '') + '</td>';
			html += '<td class="fstu-td">' + escapeHtml(unit.City_Name || '') + '</td>';
			html += '<td class="fstu-td">' + annualFee + '</td>';

			if (permissions.canView) {
				html += '<td class="fstu-td fstu-td--actions">';
				html += '<div class="fstu-actions-container">';
				html += '<button type="button" class="fstu-btn-action fstu-btn-action--view" data-unit-id="' + absint(unit.Unit_ID) + '" title="Перегляд" aria-label="Перегляд">👁</button>';

				if (permissions.canManage) {
					html += '<button type="button" class="fstu-btn-action fstu-btn-action--edit" data-unit-id="' + absint(unit.Unit_ID) + '" title="Редагувати" aria-label="Редагувати">✎</button>';
				}

				if (permissions.canDelete) {
					html += '<button type="button" class="fstu-btn-action fstu-btn-action--delete" data-unit-id="' + absint(unit.Unit_ID) + '" title="Видалити" aria-label="Видалити">✕</button>';
				}

				html += '</div>';
				html += '</td>';
			}

			html += '</tr>';
		});

		$tbody.html(html);
	}

	function updatePagination(data) {
		state.totalPages = Math.max(parseInt(data.total_pages, 10) || 1, 1);
		state.currentPage = parseInt(data.page, 10) || 1;

		$('#fstu-units-pagination-info').text(
			'Записів: ' + (parseInt(data.total, 10) || 0) + ' | Сторінка ' + state.currentPage + ' з ' + state.totalPages
		);

		$('#fstu-units-prev-page').prop('disabled', state.currentPage <= 1);
		$('#fstu-units-next-page').prop('disabled', state.currentPage >= state.totalPages);

		const $pages = $('#fstu-units-pagination-pages');
		let html = '';
		const start = Math.max(1, state.currentPage - 2);
		const end = Math.min(state.totalPages, state.currentPage + 2);

		if (start > 1) {
			html += buildPageButton(1, false);
			if (start > 2) {
				html += '<span class="fstu-pagination__ellipsis">…</span>';
			}
		}

		for (let page = start; page <= end; page += 1) {
			html += buildPageButton(page, page === state.currentPage);
		}

		if (end < state.totalPages) {
			if (end < state.totalPages - 1) {
				html += '<span class="fstu-pagination__ellipsis">…</span>';
			}
			html += buildPageButton(state.totalPages, false);
		}

		$pages.html(html);
		$pages.find('.fstu-btn--page').on('click', function() {
			state.currentPage = parseInt($(this).data('page'), 10) || 1;
			loadUnits();
		});
	}

	function handleViewUnit(event) {
		event.preventDefault();
		const unitId = absint($(this).data('unit-id'));
		const $modal = $('#fstu-units-modal-view');
		const $body = $('#fstu-units-modal-body');

		$body.html('<div class="fstu-loader-inline">' + escapeHtml(fstuUnitsL10n.messages.loading) + '</div>');
		openModal($modal);

		fetchUnitDetail(unitId)
			.done(function(response) {
				if (!response.success || !response.data || !response.data.unit) {
					$body.html('<div class="fstu-alert">' + escapeHtml(getResponseMessage(response, fstuUnitsL10n.messages.error)) + '</div>');
					return;
				}

				$body.html(renderUnitDetailHtml(response.data.unit));
			})
			.fail(function() {
				$body.html('<div class="fstu-alert">' + escapeHtml(fstuUnitsL10n.messages.error) + '</div>');
			});
	}

	function handleEditUnit(event) {
		event.preventDefault();

		const unitId = absint($(this).data('unit-id'));
		if (!permissions.canManage) {
			return;
		}

		fetchUnitDetail(unitId)
			.done(function(response) {
				if (!response.success || !response.data || !response.data.unit) {
					window.alert(getResponseMessage(response, fstuUnitsL10n.messages.error));
					return;
				}

				populateForm(response.data.unit);
				$('#fstu-units-form-title').text('Редагування осередка');
				openModal($('#fstu-units-modal-form'));
			})
			.fail(function() {
				window.alert(fstuUnitsL10n.messages.error);
			});
	}

	function handleDeleteUnit(event) {
		event.preventDefault();

		if (!permissions.canDelete) {
			return;
		}

		const unitId = absint($(this).data('unit-id'));
		const unitName = $.trim($(this).closest('tr').find('td:nth-child(2)').text());

		if (!window.confirm(fstuUnitsL10n.messages.confirmDelete + '\n\n' + unitName)) {
			return;
		}

		$.ajax({
			type: 'POST',
			url: fstuUnitsL10n.ajaxUrl,
			dataType: 'json',
			data: {
				action: 'fstu_delete_unit',
				nonce: fstuUnitsL10n.nonce,
				unit_id: unitId,
			},
			success: function(response) {
				if (!response || !response.success) {
					window.alert(getResponseMessage(response, fstuUnitsL10n.messages.deleteError));
					return;
				}

				window.alert(getResponseMessage(response, fstuUnitsL10n.messages.deleteSuccess));
				state.currentPage = 1;
				loadUnits();
			},
			error: function() {
				window.alert(fstuUnitsL10n.messages.deleteError);
			},
		});
	}

	function handleFormSubmit(event) {
		event.preventDefault();

		const $submit = $('#fstu-units-form-submit');
		const $icon = $submit.find('.fstu-btn__icon');
		const unitId = absint($('#fstu-units-edit-id').val());
		const action = unitId ? 'fstu_edit_unit' : 'fstu_add_unit';

		hideFormMessage();
		$submit.prop('disabled', true);
		$icon.text('⏳');

		const payload = {
			action: action,
			nonce: fstuUnitsL10n.nonce,
			unit_id: unitId,
			unit_name: $('#fstu-units-unit-name').val(),
			unit_short_name: $('#fstu-units-unit-short').val(),
			opf_id: $('#fstu-units-opf').val(),
			unit_type_id: $('#fstu-units-type').val(),
			unit_parent: $('#fstu-units-parent').val(),
			region_id: $('#fstu-units-region').val(),
			city_id: $('#fstu-units-city').val(),
			unit_adr: $('#fstu-units-address').val(),
			unit_okpo: $('#fstu-units-okpo').val(),
			entrance_fee: $('#fstu-units-entrance-fee').val(),
			annual_fee: $('#fstu-units-annual-fee').val(),
			url_pay: $('#fstu-units-url-pay').val(),
			payment_card: $('#fstu-units-payment-card').val(),
			fstu_website: $('#fstu-units-form').find('input[name="fstu_website"]').val(),
		};

		$.ajax({
			type: 'POST',
			url: fstuUnitsL10n.ajaxUrl,
			dataType: 'json',
			data: payload,
			success: function(response) {
				if (!response || !response.success) {
					showFormMessage(getResponseMessage(response, fstuUnitsL10n.messages.saveError), 'error');
					return;
				}

				showFormMessage(getResponseMessage(response, fstuUnitsL10n.messages.saveSuccess), 'success');
				window.setTimeout(function() {
					closeFormModal();
					loadUnits();
				}, 700);
			},
			error: function() {
				showFormMessage(fstuUnitsL10n.messages.saveError, 'error');
			},
			complete: function() {
				$submit.prop('disabled', false);
				$icon.text('💾');
			},
		});
	}

	function openAddForm() {
		if (!permissions.canManage) {
			return;
		}

		resetForm();
		$('#fstu-units-form-title').text('Додавання осередка');
		openModal($('#fstu-units-modal-form'));
		$('#fstu-units-unit-name').trigger('focus');
	}

	function populateForm(unit) {
		resetForm();
		$('#fstu-units-edit-id').val(absint(unit.Unit_ID));
		$('#fstu-units-unit-name').val(unit.Unit_Name || '');
		$('#fstu-units-unit-short').val(unit.Unit_ShortName || '');
		$('#fstu-units-opf').val(absint(unit.OPF_ID) || '');
		$('#fstu-units-type').val(absint(unit.UnitType_ID) || '');
		$('#fstu-units-parent').val(absint(unit.Unit_Parent) || 0);
		$('#fstu-units-address').val(unit.Unit_Adr || '');
		$('#fstu-units-okpo').val(unit.Unit_OKPO || '');
		$('#fstu-units-entrance-fee').val(unit.Unit_EntranceFee || '');
		$('#fstu-units-annual-fee').val(unit.Unit_AnnualFee || '');
		$('#fstu-units-url-pay').val(unit.Unit_UrlPay || '');
		$('#fstu-units-payment-card').val(unit.Unit_PaymentCard || '');
		$('#fstu-units-region').val(absint(unit.Region_ID) || '');

		return loadCitiesByRegion(unit.Region_ID, unit.City_ID)
			.fail(function() {
				showFormMessage('Не вдалося завантажити список міст для вибраного регіону.', 'error');
			});
	}

	function loadCitiesByRegion(regionId, selectedCityId) {
		const $city = $('#fstu-units-city');
		const normalizedRegionId = absint(regionId);
		const normalizedSelectedCityId = absint(selectedCityId);

		if (!normalizedRegionId) {
			$city.html('<option value="">Виберіть місто</option>');
			return $.Deferred().resolve().promise();
		}

		$city.html('<option value="">' + escapeHtml(fstuUnitsL10n.messages.loading) + '</option>');

		return $.ajax({
			type: 'POST',
			url: fstuUnitsL10n.ajaxUrl,
			dataType: 'json',
			data: {
				action: 'fstu_units_get_cities_by_region',
				nonce: fstuUnitsL10n.nonce,
				region_id: normalizedRegionId,
			},
			success: function(response) {
				if (!response || !response.success || !response.data || !response.data.cities) {
					$city.html('<option value="">Помилка завантаження</option>');
					return;
				}

				let options = '<option value="">Виберіть місто</option>';
				response.data.cities.forEach(function(city) {
					const cityId = absint(city.City_ID);
					const selected = normalizedSelectedCityId && normalizedSelectedCityId === cityId ? ' selected' : '';
					options += '<option value="' + cityId + '"' + selected + '>' + escapeHtml(city.City_Name || '') + '</option>';
				});
				$city.html(options);
			},
			error: function() {
				$city.html('<option value="">Помилка завантаження</option>');
			},
		});
	}

	function fetchUnitDetail(unitId) {
		return $.ajax({
			type: 'POST',
			url: fstuUnitsL10n.ajaxUrl,
			dataType: 'json',
			data: {
				action: 'fstu_get_unit_detail',
				nonce: fstuUnitsL10n.nonce,
				unit_id: unitId,
			},
		});
	}

	function renderUnitDetailHtml(unit) {
		let html = '<table class="fstu-info-table">';
		html += rowHtml('Найменування', '<strong>' + escapeHtml(unit.Unit_Name || '') + '</strong>');
		html += rowHtml('Скорочено', escapeHtml(unit.Unit_ShortName || '—'));
		html += rowHtml('ОПФ', escapeHtml(unit.OPF_Name || '—'));
		html += rowHtml('Тип', escapeHtml(unit.UnitType_Name || '—'));
		html += rowHtml('Вищий осередок', escapeHtml(unit.Parent_Unit_Name || '—'));
		html += rowHtml('Регіон', escapeHtml(unit.Region_Name || '—'));
		html += rowHtml('Місто', escapeHtml(unit.City_Name || '—'));
		html += rowHtml('Адреса', escapeHtml(unit.Unit_Adr || '—'));
		html += rowHtml('Код ЄДРПОУ', escapeHtml(unit.Unit_OKPO || '—'));
		html += rowHtml('Вступний внесок', escapeHtml(normalizeMoney(unit.Unit_EntranceFee)) + ' грн');
		html += rowHtml('Річний внесок', escapeHtml(normalizeMoney(unit.Unit_AnnualFee)) + ' грн');
		html += rowHtml('Форма оплати', unit.Unit_UrlPay ? '<a href="' + escapeAttr(unit.Unit_UrlPay) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(unit.Unit_UrlPay) + '</a>' : '—');
		html += rowHtml('Платіжна картка', escapeHtml(unit.Unit_PaymentCard || '—'));
		html += '</table>';
		return html;
	}

	function rowHtml(label, value) {
		return '<tr><th>' + escapeHtml(label) + ':</th><td>' + value + '</td></tr>';
	}

	function setTableLoading() {
		$('#fstu-units-tbody').html(
			'<tr class="fstu-row">' +
				'<td colspan="' + getTableColspan() + '" class="fstu-no-results">' + escapeHtml(fstuUnitsL10n.messages.loading) + '</td>' +
			'</tr>'
		);
	}

	function showTableError(message) {
		$('#fstu-units-tbody').html(
			'<tr class="fstu-row">' +
				'<td colspan="' + getTableColspan() + '" class="fstu-no-results fstu-no-results--error">' + escapeHtml(message) + '</td>' +
			'</tr>'
		);
	}

	function showFormMessage(message, type) {
		const $box = $('#fstu-units-form-message');
		$box.removeClass('fstu-units-hidden fstu-message--error fstu-message--success');
		$box.addClass(type === 'success' ? 'fstu-message--success' : 'fstu-message--error');
		$box.text(message);
	}

	function hideFormMessage() {
		$('#fstu-units-form-message')
			.addClass('fstu-units-hidden')
			.removeClass('fstu-message--error fstu-message--success')
			.text('');
	}

	function resetForm() {
		$('#fstu-units-form')[0].reset();
		$('#fstu-units-edit-id').val('');
		$('#fstu-units-city').html('<option value="">Виберіть місто</option>');
		hideFormMessage();
	}

	function openModal($modal) {
		$modal.removeClass('fstu-units-hidden').attr('aria-hidden', 'false');
		$('body').addClass('fstu-units-modal-open');
	}

	function closeViewModal() {
		$('#fstu-units-modal-view').addClass('fstu-units-hidden').attr('aria-hidden', 'true');
		if ($('#fstu-units-modal-form').hasClass('fstu-units-hidden')) {
			$('body').removeClass('fstu-units-modal-open');
		}
	}

	function closeFormModal() {
		$('#fstu-units-modal-form').addClass('fstu-units-hidden').attr('aria-hidden', 'true');
		resetForm();
		if ($('#fstu-units-modal-view').hasClass('fstu-units-hidden')) {
			$('body').removeClass('fstu-units-modal-open');
		}
	}

	function toggleSearchClear() {
		$('#fstu-units-search-clear').toggleClass('fstu-units-hidden', !$('#fstu-units-search').val());
	}

	function getTableColspan() {
		return permissions.canView ? 9 : 8;
	}

	function buildPageButton(page, isActive) {
		return '<button type="button" class="fstu-btn--page' + (isActive ? ' fstu-btn--page-active' : '') + '" data-page="' + page + '">' + page + '</button>';
	}

	function getResponseMessage(response, fallback) {
		return response && response.data && response.data.message ? response.data.message : fallback;
	}

	function normalizeMoney(value) {
		const number = parseFloat(value);
		return Number.isFinite(number) ? number.toFixed(2) : '0.00';
	}

	function absint(value) {
		const parsed = parseInt(value, 10);
		return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
	}

	function escapeHtml(value) {
		return $('<div>').text(value == null ? '' : String(value)).html();
	}

	function escapeAttr(value) {
		return escapeHtml(value).replace(/"/g, '&quot;');
	}

	function debounce(callback, wait) {
		let timeoutId;
		return function() {
			const context = this;
			const args = arguments;
			window.clearTimeout(timeoutId);
			timeoutId = window.setTimeout(function() {
				callback.apply(context, args);
			}, wait);
		};
	}
});

