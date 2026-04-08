/**
 * JavaScript модуля "Довідник видів змагань ФСТУ".
 * AJAX-запити, фільтри, модальні вікна, CRUD-операції.
 *
 * Version:     1.5.0
 * Date_update: 2026-04-06
 *
 * @package FSTU
 */

jQuery(document).ready(function($) {
	'use strict';

	const $root = $('#fstu-typeevent');
	if (!$root.length || typeof fstuTypeEventL10n === 'undefined') {
		return;
	}

	const $search = $('#fstu-typeevent-search');
	const $perPage = $('#fstu-typeevent-per-page');
	const $mainSection = $('#fstu-typeevent-main');
	const $protocolSection = $('#fstu-typeevent-protocol');
	const $protocolOpenBtn = $('#fstu-typeevent-protocol-btn');
	const $protocolBackBtn = $('#fstu-typeevent-protocol-back-btn');
	const $protocolPerPage = $('#fstu-typeevent-protocol-per-page');
	const $protocolFilterName = $('#fstu-protocol-filter-name');

	const state = {
		currentPage: 1,
		perPage: parseInt($perPage.val(), 10) || 15,
		totalPages: 1,
		isLoading: false,
	};

	const protocolState = {
		currentPage: 1,
		perPage: parseInt($protocolPerPage.val(), 10) || 10,
		totalPages: 1,
		isLoading: false,
	};

	const permissions = fstuTypeEventL10n.permissions || {
		canView: true,
		canManage: false,
		canDelete: false,
	};

	bindEvents();
	loadItems();

	function bindEvents() {
		$('#fstu-typeevent-add-btn').on('click', openAddForm);
		$('#fstu-typeevent-refresh-btn').on('click', loadItems);
		$protocolOpenBtn.on('click', handleOpenProtocol);
		$protocolBackBtn.on('click', handleCloseProtocol);

		$search.on('input', debounce(function() {
			state.currentPage = 1;
			loadItems();
		}, 300));

		$perPage.on('change', function() {
			state.perPage = parseInt($(this).val(), 10) || 15;
			state.currentPage = 1;
			loadItems();
		});

		$protocolPerPage.on('change', function() {
			protocolState.perPage = parseInt($(this).val(), 10) || 10;
			protocolState.currentPage = 1;
			loadProtocol();
		});

		const protocolFilterHandler = debounce(function() {
			protocolState.currentPage = 1;
			loadProtocol();
		}, 300);

		$protocolFilterName.on('input', protocolFilterHandler);

		$('#fstu-typeevent-prev-page').on('click', function() {
			if (state.currentPage > 1) {
				state.currentPage -= 1;
				loadItems();
			}
		});

		$('#fstu-typeevent-next-page').on('click', function() {
			if (state.currentPage < state.totalPages) {
				state.currentPage += 1;
				loadItems();
			}
		});

		$('#fstu-typeevent-protocol-prev-page').on('click', function() {
			if (protocolState.currentPage > 1) {
				protocolState.currentPage -= 1;
				loadProtocol();
			}
		});

		$('#fstu-typeevent-protocol-next-page').on('click', function() {
			if (protocolState.currentPage < protocolState.totalPages) {
				protocolState.currentPage += 1;
				loadProtocol();
			}
		});

		// Відкриття/закриття Dropdown
		$(document).on('click', '.fstu-dropdown-toggle', function(event) {
			event.preventDefault();
			event.stopPropagation();
			const $dd = $(this).closest('.fstu-dropdown');
			const isOpen = $dd.hasClass('fstu-dropdown--open');
			$('.fstu-dropdown--open').removeClass('fstu-dropdown--open');
			
			if (!isOpen) {
				$dd.addClass('fstu-dropdown--open');
				// Перевірка, чи не вилазить за низ екрану
				const $menu = $dd.find('.fstu-dropdown-menu');
				if ($menu[0].getBoundingClientRect().bottom > window.innerHeight) {
					$dd.addClass('fstu-dropdown--up');
				} else {
					$dd.removeClass('fstu-dropdown--up');
				}
			}
		});

		$(document).on('click', function() {
			$('.fstu-dropdown--open').removeClass('fstu-dropdown--open');
		});

		$(document).on('click', '.fstu-typeevent-opts-list', function(event) {
			event.stopPropagation();
		});

		$(document).on('click', function() {
			$('.fstu-typeevent-opts').removeClass('fstu-typeevent-opts--open fstu-typeevent-dropup');
		});

		$(document).on('click', '.fstu-typeevent-btn-view', handleViewItem);
		$(document).on('click', '.fstu-typeevent-btn-edit', handleEditItem);
		$(document).on('click', '.fstu-typeevent-btn-delete', handleDeleteItem);

		$('#fstu-typeevent-modal-view-close').on('click', closeViewModal);
		$('#fstu-typeevent-modal-form-close, #fstu-typeevent-form-cancel').on('click', closeFormModal);

		$('#fstu-typeevent-modal-view').on('click', function(event) {
			if (event.target === this) {
				closeViewModal();
			}
		});

		$('#fstu-typeevent-modal-form').on('click', function(event) {
			if (event.target === this) {
				closeFormModal();
			}
		});

		$('#fstu-typeevent-form').on('submit', handleFormSubmit);
	}

	function loadItems() {
		if (state.isLoading) {
			return;
		}

		state.isLoading = true;
		setTableLoading();

		$.ajax({
			type: 'POST',
			url: fstuTypeEventL10n.ajaxUrl,
			dataType: 'json',
			data: {
				action: 'fstu_typeevent_get_list',
				nonce: fstuTypeEventL10n.nonce,
				search: $search.val(),
				page: state.currentPage,
				per_page: state.perPage,
			},
			success: function(response) {
				if (!response || !response.success || !response.data) {
					showTableError(fstuTypeEventL10n.messages.error);
					return;
				}

				renderTable(response.data.items || []);
				updatePagination(response.data);
			},
			error: function() {
				showTableError(fstuTypeEventL10n.messages.error);
			},
			complete: function() {
				state.isLoading = false;
			},
		});
	}

	function renderTable(items) {
		const $tbody = $('#fstu-typeevent-tbody');
		const colspan = getTableColspan();

		if (!items.length) {
			$tbody.html('<tr class="fstu-row"><td colspan="' + colspan + '" class="fstu-no-results">' + escapeHtml(fstuTypeEventL10n.messages.noResults) + '</td></tr>');
			return;
		}

		let html = '';

		items.forEach(function(item, index) {
			const rowNumber = ((state.currentPage - 1) * state.perPage) + index + 1;

			html += '<tr class="fstu-row">';
			html += '<td class="fstu-td fstu-td--num">' + rowNumber + '</td>';
			html += '<td class="fstu-td">' + escapeHtml(item.TypeEvent_Name || '') + '</td>';

			if (permissions.canView) {
				html += '<td class="fstu-td fstu-td--actions"><div class="fstu-dropdown">';
				html += '<button type="button" class="fstu-dropdown-toggle" title="Дії" aria-label="Дії">▼</button>';
				html += '<ul class="fstu-dropdown-menu">';
				html += '<li><button type="button" class="fstu-typeevent-btn-view" data-typeevent-id="' + absint(item.TypeEvent_ID) + '">🔎 Перегляд</button></li>';
				if (permissions.canManage) {
					html += '<li><button type="button" class="fstu-typeevent-btn-edit" data-typeevent-id="' + absint(item.TypeEvent_ID) + '">📝 Редагування</button></li>';
				}
				if (permissions.canDelete) {
					html += '<li><hr class="fstu-dropdown-divider"></li>';
					html += '<li><button type="button" class="fstu-typeevent-btn-delete fstu-text-danger" data-typeevent-id="' + absint(item.TypeEvent_ID) + '">❌ Видалення</button></li>';
				}
				html += '</ul></div></td>';
			}

			html += '</tr>';
		});

		$tbody.html(html);
	}

	function updatePagination(data) {
		state.totalPages = Math.max(parseInt(data.total_pages, 10) || 1, 1);
		state.currentPage = parseInt(data.page, 10) || 1;

		$('#fstu-typeevent-pagination-info').text(
			'Записів: ' + (parseInt(data.total, 10) || 0) + ' | Сторінка ' + state.currentPage + ' з ' + state.totalPages
		);

		$('#fstu-typeevent-prev-page').prop('disabled', state.currentPage <= 1);
		$('#fstu-typeevent-next-page').prop('disabled', state.currentPage >= state.totalPages);

		const $pages = $('#fstu-typeevent-pagination-pages');
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
			loadItems();
		});
	}

	function handleViewItem(event) {
		event.preventDefault();
		const id = absint($(this).data('typeevent-id'));
		const $modal = $('#fstu-typeevent-modal-view');
		const $body = $('#fstu-typeevent-modal-body');
		$('#fstu-typeevent-modal-title').text('Перегляд виду змагань');

		$body.html('<div class="fstu-loader-inline">' + escapeHtml(fstuTypeEventL10n.messages.loading) + '</div>');
		openModal($modal);

		fetchSingle(id)
			.done(function(response) {
				if (!response || !response.success || !response.data || !response.data.item) {
					$body.html('<div class="fstu-alert">' + escapeHtml(getResponseMessage(response, fstuTypeEventL10n.messages.error)) + '</div>');
					return;
				}
				$body.html(renderDetailHtml(response.data.item));
			})
			.fail(function() {
				$body.html('<div class="fstu-alert">' + escapeHtml(fstuTypeEventL10n.messages.error) + '</div>');
			});
	}

	function handleOpenProtocol(event) {
		event.preventDefault();
		$mainSection.addClass('fstu-typeevent-hidden');
		$protocolSection.removeClass('fstu-typeevent-hidden');
		$protocolOpenBtn.addClass('fstu-typeevent-hidden');
		$protocolBackBtn.removeClass('fstu-typeevent-hidden');
		protocolState.currentPage = 1;
		loadProtocol();
	}

	function handleCloseProtocol(event) {
		event.preventDefault();
		$protocolSection.addClass('fstu-typeevent-hidden');
		$mainSection.removeClass('fstu-typeevent-hidden');
		$protocolBackBtn.addClass('fstu-typeevent-hidden');
		$protocolOpenBtn.removeClass('fstu-typeevent-hidden');
	}

	function handleEditItem(event) {
		event.preventDefault();
		if (!permissions.canManage) {
			return;
		}

		const id = absint($(this).data('typeevent-id'));
		fetchSingle(id)
			.done(function(response) {
				if (!response || !response.success || !response.data || !response.data.item) {
					window.alert(getResponseMessage(response, fstuTypeEventL10n.messages.error));
					return;
				}

				populateForm(response.data.item);
				$('#fstu-typeevent-form-title').text('Редагування виду змагань');
				openModal($('#fstu-typeevent-modal-form'));
			})
			.fail(function() {
				window.alert(fstuTypeEventL10n.messages.error);
			});
	}

	function handleDeleteItem(event) {
		event.preventDefault();
		if (!permissions.canDelete) {
			return;
		}

		const id = absint($(this).data('typeevent-id'));
		const name = $.trim($(this).closest('tr').find('td:nth-child(2)').text());
		if (!window.confirm(fstuTypeEventL10n.messages.confirmDelete + '\n\n' + name)) {
			return;
		}

		$.ajax({
			type: 'POST',
			url: fstuTypeEventL10n.ajaxUrl,
			dataType: 'json',
			data: {
				action: 'fstu_typeevent_delete',
				nonce: fstuTypeEventL10n.nonce,
				typeevent_id: id,
			},
			success: function(response) {
				if (!response || !response.success) {
					window.alert(getResponseMessage(response, fstuTypeEventL10n.messages.deleteError));
					return;
				}
				window.alert(getResponseMessage(response, fstuTypeEventL10n.messages.deleteSuccess));
				state.currentPage = 1;
				loadItems();
			},
			error: function() {
				window.alert(fstuTypeEventL10n.messages.deleteError);
			},
		});
	}

	function handleFormSubmit(event) {
		event.preventDefault();

		const $submit = $('#fstu-typeevent-form-submit');
		const $icon = $submit.find('.fstu-btn__icon');
		hideFormMessage();
		$submit.prop('disabled', true);
		$icon.text('⏳');

		$.ajax({
			type: 'POST',
			url: fstuTypeEventL10n.ajaxUrl,
			dataType: 'json',
			data: {
				action: 'fstu_typeevent_save',
				nonce: fstuTypeEventL10n.nonce,
				typeevent_id: absint($('#fstu-typeevent-edit-id').val()),
				typeevent_name: $('#fstu-typeevent-name').val(),
				typeevent_code: $('#fstu-typeevent-code').val(),
				fstu_website: $('#fstu-typeevent-form').find('input[name="fstu_website"]').val(),
			},
			success: function(response) {
				if (!response || !response.success) {
					showFormMessage(getResponseMessage(response, fstuTypeEventL10n.messages.saveError), 'error');
					return;
				}
				showFormMessage(getResponseMessage(response, fstuTypeEventL10n.messages.saveSuccess), 'success');
				window.setTimeout(function() {
					closeFormModal();
					loadItems();
				}, 700);
			},
			error: function() {
				showFormMessage(fstuTypeEventL10n.messages.saveError, 'error');
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
		$('#fstu-typeevent-form-title').text('Додавання виду змагань');
		openModal($('#fstu-typeevent-modal-form'));
		$('#fstu-typeevent-name').trigger('focus');
	}

	function populateForm(item) {
		resetForm();
		$('#fstu-typeevent-edit-id').val(absint(item.TypeEvent_ID));
		$('#fstu-typeevent-name').val(item.TypeEvent_Name || '');
		$('#fstu-typeevent-code').val(item.TypeEvent_Code || '0');
	}

	function fetchSingle(id) {
		return $.ajax({
			type: 'POST',
			url: fstuTypeEventL10n.ajaxUrl,
			dataType: 'json',
			data: {
				action: 'fstu_typeevent_get_single',
				nonce: fstuTypeEventL10n.nonce,
				typeevent_id: id,
			},
		});
	}

	function renderDetailHtml(item) {
		let html = '<table class="fstu-info-table">';
		html += rowHtml('Найменування', '<strong>' + escapeHtml(item.TypeEvent_Name || '') + '</strong>');
		html += rowHtml('Сортування', escapeHtml(item.TypeEvent_Code || '0'));
		html += '</table>';
		return html;
	}

	function loadProtocol() {
		if (protocolState.isLoading) {
			return;
		}

		protocolState.isLoading = true;
		setProtocolLoading();

		$.ajax({
			type: 'POST',
			url: fstuTypeEventL10n.ajaxUrl,
			dataType: 'json',
			data: {
				action: 'fstu_typeevent_get_protocol',
				nonce: fstuTypeEventL10n.nonce,
				page: protocolState.currentPage,
				per_page: protocolState.perPage,
				filter_name: $protocolFilterName.val(),
			},
			success: function(response) {
				if (!response || !response.success || !response.data) {
					showProtocolError(fstuTypeEventL10n.messages.protocolError || fstuTypeEventL10n.messages.error);
					return;
				}

				renderProtocolTable(response.data.items || []);
				updateProtocolPagination(response.data);
			},
			error: function() {
				showProtocolError(fstuTypeEventL10n.messages.protocolError || fstuTypeEventL10n.messages.error);
			},
			complete: function() {
				protocolState.isLoading = false;
			},
		});
	}

	function renderProtocolTable(items) {
		const $tbody = $('#fstu-typeevent-protocol-tbody');

		if (!items.length) {
			$tbody.html('<tr class="fstu-row"><td colspan="6" class="fstu-no-results">' + escapeHtml(fstuTypeEventL10n.messages.protocolEmpty || fstuTypeEventL10n.messages.noResults) + '</td></tr>');
			return;
		}

		let html = '';
		items.forEach(function(item) {
			const dateCreate = item.Logs_DateCreate ? item.Logs_DateCreate : '';
			const logsType = item.Logs_Type ? item.Logs_Type : '';
			const logsName = item.Logs_Name ? item.Logs_Name : '';
			const logsText = item.Logs_Text ? item.Logs_Text : '';
			const logsStatus = item.Logs_Error ? item.Logs_Error : '✓';
			const userName = item.FIO ? item.FIO : '—';

			html += '<tr class="fstu-row">';
			html += '<td class="fstu-td fstu-td--date">' + escapeHtml(dateCreate) + '</td>';
			html += '<td class="fstu-td fstu-td--type">' + buildTypeBadge(logsType) + '</td>';
			html += '<td class="fstu-td fstu-td--operation">' + escapeHtml(logsName) + '</td>';
			html += '<td class="fstu-td fstu-td--message">' + escapeHtml(logsText) + '</td>';
			html += '<td class="fstu-td fstu-td--status">' + escapeHtml(logsStatus) + '</td>';
			html += '<td class="fstu-td fstu-td--user">' + escapeHtml(userName) + '</td>';
			html += '</tr>';
		});

		$tbody.html(html);
	}

	function updateProtocolPagination(data) {
		protocolState.totalPages = Math.max(parseInt(data.total_pages, 10) || 1, 1);
		protocolState.currentPage = parseInt(data.page, 10) || 1;

		$('#fstu-typeevent-protocol-pagination-info').text(
			'Записів: ' + (parseInt(data.total, 10) || 0) + ' | Сторінка ' + protocolState.currentPage + ' з ' + protocolState.totalPages
		);

		$('#fstu-typeevent-protocol-prev-page').prop('disabled', protocolState.currentPage <= 1);
		$('#fstu-typeevent-protocol-next-page').prop('disabled', protocolState.currentPage >= protocolState.totalPages);

		const $pages = $('#fstu-typeevent-protocol-pagination-pages');
		let html = '';
		const start = Math.max(1, protocolState.currentPage - 2);
		const end = Math.min(protocolState.totalPages, protocolState.currentPage + 2);

		if (start > 1) {
			html += buildPageButton(1, false);
			if (start > 2) {
				html += '<span class="fstu-pagination__ellipsis">…</span>';
			}
		}

		for (let page = start; page <= end; page += 1) {
			html += buildPageButton(page, page === protocolState.currentPage);
		}

		if (end < protocolState.totalPages) {
			if (end < protocolState.totalPages - 1) {
				html += '<span class="fstu-pagination__ellipsis">…</span>';
			}
			html += buildPageButton(protocolState.totalPages, false);
		}

		$pages.html(html);
		$pages.find('.fstu-btn--page').on('click', function() {
			protocolState.currentPage = parseInt($(this).data('page'), 10) || 1;
			loadProtocol();
		});
	}

	function setProtocolLoading() {
		$('#fstu-typeevent-protocol-tbody').html('<tr class="fstu-row"><td colspan="2" class="fstu-no-results">' + escapeHtml(fstuTypeEventL10n.messages.loading) + '</td></tr>');
	}

	function showProtocolError(message) {
		$('#fstu-typeevent-protocol-tbody').html('<tr class="fstu-row"><td colspan="2" class="fstu-no-results fstu-no-results--error">' + escapeHtml(message) + '</td></tr>');
	}

	function rowHtml(label, value) {
		return '<tr><th>' + escapeHtml(label) + ':</th><td>' + value + '</td></tr>';
	}

	function setTableLoading() {
		$('#fstu-typeevent-tbody').html('<tr class="fstu-row"><td colspan="' + getTableColspan() + '" class="fstu-no-results">' + escapeHtml(fstuTypeEventL10n.messages.loading) + '</td></tr>');
	}

	function showTableError(message) {
		$('#fstu-typeevent-tbody').html('<tr class="fstu-row"><td colspan="' + getTableColspan() + '" class="fstu-no-results fstu-no-results--error">' + escapeHtml(message) + '</td></tr>');
	}

	function showFormMessage(message, type) {
		const $box = $('#fstu-typeevent-form-message');
		$box.removeClass('fstu-typeevent-hidden fstu-message--error fstu-message--success');
		$box.addClass(type === 'success' ? 'fstu-message--success' : 'fstu-message--error');
		$box.text(message);
	}

	function hideFormMessage() {
		$('#fstu-typeevent-form-message').addClass('fstu-typeevent-hidden').removeClass('fstu-message--error fstu-message--success').text('');
	}

	function resetForm() {
		$('#fstu-typeevent-form')[0].reset();
		$('#fstu-typeevent-edit-id').val('');
		hideFormMessage();
	}

	function openModal($modal) {
		$modal.removeClass('fstu-typeevent-hidden').attr('aria-hidden', 'false');
		$('body').addClass('fstu-typeevent-modal-open');
	}

	function closeViewModal() {
		$('#fstu-typeevent-modal-view').addClass('fstu-typeevent-hidden').attr('aria-hidden', 'true');
		if ($('#fstu-typeevent-modal-form').hasClass('fstu-typeevent-hidden')) {
			$('body').removeClass('fstu-typeevent-modal-open');
		}
	}

	function closeFormModal() {
		$('#fstu-typeevent-modal-form').addClass('fstu-typeevent-hidden').attr('aria-hidden', 'true');
		resetForm();
		if ($('#fstu-typeevent-modal-view').hasClass('fstu-typeevent-hidden')) {
			$('body').removeClass('fstu-typeevent-modal-open');
		}
	}

	function getTableColspan() {
		return permissions.canView ? 3 : 2;
	}

	function buildPageButton(page, isActive) {
		return '<button type="button" class="fstu-btn--page' + (isActive ? ' fstu-btn--page-active' : '') + '" data-page="' + page + '">' + page + '</button>';
	}

	function getResponseMessage(response, fallback) {
		return response && response.data && response.data.message ? response.data.message : fallback;
	}

	function absint(value) {
		const parsed = parseInt(value, 10);
		return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
	}

	function escapeHtml(value) {
		return $('<div>').text(value == null ? '' : String(value)).html();
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

	function buildTypeBadge( type ) {
		var cls = 'fstu-badge--default';
		var label = type || '—';
		if ( type === 'INSERT' || type === 'I' ) { cls = 'fstu-badge--insert'; label = 'INSERT'; }
		if ( type === 'UPDATE' || type === 'U' ) { cls = 'fstu-badge--update'; label = 'UPDATE'; }
		if ( type === 'DELETE' || type === 'D' ) { cls = 'fstu-badge--delete'; label = 'DELETE'; }
		return '<span class="fstu-badge ' + cls + '">' + escapeHtml( label ) + '</span>';
	}
});

