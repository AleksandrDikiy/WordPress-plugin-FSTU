/**
 * JS модуля "Довідник типів заходів".
 *
 * Version:     1.0.0
 * Date_update: 2026-04-07
 *
 * @package FSTU
 */


jQuery(document).ready(function ($) {
	'use strict';

	const state = { view: 'list', list: { page: 1, per_page: 10, search: '' }, protocol: { page: 1, per_page: 10, search: '' } };

	function debounce(func, wait) {
		let timeout;
		return function (...args) { clearTimeout(timeout); timeout = setTimeout(() => func.apply(this, args), wait); };
	}

	function fetchList() {
		$('#fstu-eventtype-tbody').html(`<tr><td colspan="3" style="text-align:center;">${fstuEventType.strings.loading}</td></tr>`);
		$.post(fstuEventType.ajaxUrl, { action: 'fstu_eventtype_get_list', nonce: fstuEventType.nonce, page: state.list.page, per_page: state.list.per_page, search: state.list.search }, function (res) {
			if (res.success) { $('#fstu-eventtype-tbody').html(res.data.html); renderPagination('list', res.data); } else alert(res.data.message);
		});
	}

	function fetchProtocol() {
		$('#fstu-protocol-tbody').html(`<tr><td colspan="6" style="text-align:center;">${fstuEventType.strings.loading}</td></tr>`);
		$.post(fstuEventType.ajaxUrl, { action: 'fstu_eventtype_get_protocol', nonce: fstuEventType.nonce, page: state.protocol.page, per_page: state.protocol.per_page, search: state.protocol.search }, function (res) {
			if (res.success) { $('#fstu-protocol-tbody').html(res.data.html); renderPagination('protocol', res.data); }
		});
	}

	function renderPagination(type, data) {
		const prefix = type === 'list' ? 'fstu-eventtype' : 'fstu-protocol';
		$(`#${prefix}-info`).text(`Записів: ${data.total} | Сторінка ${data.page} з ${Math.max(1, data.total_pages)}`);
		const $controls = $(`#${prefix}-pages`).empty();

		if (data.total_pages > 1) {
			$controls.append($('<button>').addClass('fstu-btn').text('«').prop('disabled', data.page === 1).data('page', data.page - 1));
			$controls.append($('<button>').addClass('fstu-btn').text(data.page).css({background:'#b94a48',color:'#fff'}).prop('disabled', true));
			$controls.append($('<button>').addClass('fstu-btn').text('»').prop('disabled', data.page === data.total_pages).data('page', data.page + 1));
		}
	}

	$('#fstu-btn-protocol').on('click', () => { $('#fstu-view-directory, #fstu-actions-directory').addClass('fstu-hidden'); $('#fstu-view-protocol, #fstu-actions-protocol').removeClass('fstu-hidden'); state.view = 'protocol'; fetchProtocol(); });
	$('#fstu-btn-back').on('click', () => { $('#fstu-view-protocol, #fstu-actions-protocol').addClass('fstu-hidden'); $('#fstu-view-directory, #fstu-actions-directory').removeClass('fstu-hidden'); state.view = 'list'; fetchList(); });
	$('#fstu-btn-refresh').on('click', fetchList);
	$('#fstu-btn-refresh-protocol').on('click', fetchProtocol);

	$('#fstu-eventtype-search').on('input', debounce(function () { state.list.search = $(this).val(); state.list.page = 1; fetchList(); }, 500));
	$('#fstu-protocol-search').on('input', debounce(function () { state.protocol.search = $(this).val(); state.protocol.page = 1; fetchProtocol(); }, 500));

	$('#fstu-eventtype-per-page').on('change', function () { state.list.per_page = $(this).val(); state.list.page = 1; fetchList(); });
	$('#fstu-protocol-per-page').on('change', function () { state.protocol.per_page = $(this).val(); state.protocol.page = 1; fetchProtocol(); });

	$(document).on('click', '.fstu-pagination__controls button:not(:disabled)', function () {
		if (state.view === 'list') { state.list.page = $(this).data('page'); fetchList(); } else { state.protocol.page = $(this).data('page'); fetchProtocol(); }
	});

	const $modal = $('#fstu-modal-eventtype');
	$('#fstu-btn-add').on('click', () => { $('#fstu-eventtype-form')[0].reset(); $('#fstu-eventtype-id').val(0); $('#fstu-modal-title').text('Додати тип заходу'); $modal.removeClass('fstu-hidden'); });
	$(document).on('click', '.fstu-action-edit', function () { $('#fstu-eventtype-id').val($(this).data('id')); $('#fstu-eventtype-name').val($(this).data('name')); $('#fstu-eventtype-order').val($(this).data('order')); $('#fstu-modal-title').text('Редагувати тип заходу'); $modal.removeClass('fstu-hidden'); });
	$('.fstu-modal-close').on('click', () => $modal.addClass('fstu-hidden'));

	$('#fstu-eventtype-form').on('submit', function (e) {
		e.preventDefault(); const $btn = $(this).find('button[type="submit"]').prop('disabled', true);
		$.post(fstuEventType.ajaxUrl, { action: 'fstu_eventtype_save', nonce: fstuEventType.nonce, id: $('#fstu-eventtype-id').val(), name: $('#fstu-eventtype-name').val().trim(), order: $('#fstu-eventtype-order').val() }, function (res) {
			$btn.prop('disabled', false); if (res.success) { $modal.addClass('fstu-hidden'); fetchList(); } else alert(res.data.message);
		});
	});

	$(document).on('click', '.fstu-action-delete', function () {
		if (!confirm(fstuEventType.strings.confirmDelete)) return;
		$.post(fstuEventType.ajaxUrl, { action: 'fstu_eventtype_delete', nonce: fstuEventType.nonce, id: $(this).data('id') }, function (res) {
			if (res.success) fetchList(); else alert(res.data.message);
		});
	});

	fetchList();
});