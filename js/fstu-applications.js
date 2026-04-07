/**
 * Клієнтська логіка модуля "Заявки в ФСТУ".
 * Суворе дотримання стандартів: делегування подій, debounce, AJAX-безпека.
 *
 * Version:     1.9.0
 * Date_update: 2026-04-07
 */

/* global fstuApplications */

jQuery(document).ready(function ($) {
    'use strict';

    // 1. Глобальний стан модуля (State Management)
    const state = {
        view: 'list', // 'list' або 'protocol'
        list: {
            page: 1,
            per_page: 10,
            search: '',
            region_id: 0,
            unit_id: 0
        },
        protocol: {
            page: 1,
            per_page: 10,
            search: ''
        }
    };

    let listRequest = null;
    let protocolRequest = null;
    let candidateRequest = null;
    let acceptRequest = null;
    let changeOfstRequest = null;
    let rejectRequest = null;
    let listRequestToken = 0;
    let protocolRequestToken = 0;
    const originalUnitOptions = $('#fstu-filter-unit option').map(function () {
        return {
            value: String($(this).val()),
            text: $(this).text(),
            regionId: String($(this).data('region-id') || '0')
        };
    }).get();

    // 2. Helper: Debounce (затримка для інпутів пошуку, щоб не "спамити" сервер)
    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // 3. Функції завантаження даних (AJAX)
    function fetchList() {
        const $tbody = $('#fstu-applications-tbody');
        $tbody.html('<tr><td colspan="8" class="fstu-table-state fstu-table-state--loading">' + escapeHtml(fstuApplications.strings.loading) + '</td></tr>');

        closeAllDropdowns();

        if (listRequest && typeof listRequest.abort === 'function') {
            listRequest.abort();
        }

        listRequestToken += 1;
        const currentToken = listRequestToken;

        listRequest = $.post(fstuApplications.ajaxUrl, {
            action: 'fstu_applications_get_list',
            nonce: fstuApplications.nonce,
            page: state.list.page,
            per_page: state.list.per_page,
            search: state.list.search,
            region_id: state.list.region_id,
            unit_id: state.list.unit_id
        }, function (res) {
            if (currentToken !== listRequestToken) {
                return;
            }

            if (res.success) {
                $tbody.html(res.data.html);
                renderPagination('list', res.data);
            } else {
                $tbody.html('<tr><td colspan="8" class="fstu-table-state fstu-table-state--error">' + escapeHtml(res.data.message || fstuApplications.strings.errorGeneric) + '</td></tr>');
            }
        }).fail(function () {
            if (currentToken !== listRequestToken) {
                return;
            }

            $tbody.html('<tr><td colspan="8" class="fstu-table-state fstu-table-state--error">' + escapeHtml(fstuApplications.strings.errorGeneric) + '</td></tr>');
        }).always(function () {
            if (currentToken === listRequestToken) {
                listRequest = null;
            }
        });
    }

    function fetchProtocol() {
        const $tbody = $('#fstu-protocol-tbody');
        $tbody.html('<tr><td colspan="6" class="fstu-table-state fstu-table-state--loading">' + escapeHtml(fstuApplications.strings.loading) + '</td></tr>');

        if (protocolRequest && typeof protocolRequest.abort === 'function') {
            protocolRequest.abort();
        }

        protocolRequestToken += 1;
        const currentToken = protocolRequestToken;

        protocolRequest = $.post(fstuApplications.ajaxUrl, {
            action: 'fstu_applications_get_protocol',
            nonce: fstuApplications.nonce,
            page: state.protocol.page,
            per_page: state.protocol.per_page,
            search: state.protocol.search
        }, function (res) {
            if (currentToken !== protocolRequestToken) {
                return;
            }

            if (res.success) {
                $tbody.html(res.data.html);
                renderPagination('protocol', res.data);
            } else {
                $tbody.html('<tr><td colspan="6" class="fstu-table-state fstu-table-state--error">' + escapeHtml(res.data.message || fstuApplications.strings.errorGeneric) + '</td></tr>');
            }
        }).fail(function () {
            if (currentToken !== protocolRequestToken) {
                return;
            }

            $tbody.html('<tr><td colspan="6" class="fstu-table-state fstu-table-state--error">' + escapeHtml(fstuApplications.strings.errorGeneric) + '</td></tr>');
        }).always(function () {
            if (currentToken === protocolRequestToken) {
                protocolRequest = null;
            }
        });
    }

    function syncUnitOptions(regionId) {
        const normalizedRegionId = String(regionId || 0);
        const currentValue = String(state.list.unit_id || 0);
        const $unitSelect = $('#fstu-filter-unit');
        let currentExists = false;
        let html = '';

        originalUnitOptions.forEach(function (option) {
            if (option.value === '0' || normalizedRegionId === '0' || option.regionId === normalizedRegionId) {
                const selected = option.value === currentValue ? ' selected' : '';
                if (selected) {
                    currentExists = true;
                }
                html += '<option value="' + escapeHtml(option.value) + '" data-region-id="' + escapeHtml(option.regionId) + '"' + selected + '>' + escapeHtml(option.text) + '</option>';
            }
        });

        $unitSelect.html(html);

        if (!currentExists) {
            state.list.unit_id = 0;
            $unitSelect.val('0');
        }
    }

    function closeAllDropdowns() {
        $('.fstu-applications-dropdown').removeClass('is-open is-dropup');
        $('.fstu-applications-dropdown__toggle').attr('aria-expanded', 'false');
    }

    function openModal(modalId) {
        $('#' + modalId).removeClass('fstu-hidden').attr('aria-hidden', 'false');
        $('body').addClass('fstu-modal-open');
    }

    function closeModal(modalId) {
        $('#' + modalId).addClass('fstu-hidden').attr('aria-hidden', 'true');

        if (!$('.fstu-modal-overlay:not(.fstu-hidden)').length) {
            $('body').removeClass('fstu-modal-open');
        }
    }

    function positionDropdown($dropdown) {
        const menu = $dropdown.find('.fstu-applications-dropdown__menu').get(0);
        const toggle = $dropdown.find('.fstu-applications-dropdown__toggle').get(0);

        if (!menu || !toggle) {
            return;
        }

        const menuHeight = menu.offsetHeight || 180;
        const rect = toggle.getBoundingClientRect();
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

        if (rect.bottom + menuHeight > viewportHeight && rect.top > menuHeight) {
            $dropdown.addClass('is-dropup');
        } else {
            $dropdown.removeClass('is-dropup');
        }
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function normalizeExternalUrl(value) {
        const normalized = String(value || '').trim();

        if (!normalized || /^(javascript:|data:)/i.test(normalized)) {
            return '';
        }

        if (/^https?:\/\//i.test(normalized)) {
            return normalized;
        }

        if (/^\/\//.test(normalized)) {
            return `https:${normalized}`;
        }

        return `https://${normalized.replace(/^\/+/, '')}`;
    }

    function formatAmountValue(value) {
        const normalized = String(value || '').trim();
        if (!normalized) {
            return '—';
        }

        const numeric = Number(normalized.replace(',', '.'));
        if (!Number.isFinite(numeric)) {
            return normalized;
        }

        return `${numeric.toLocaleString('uk-UA', { maximumFractionDigits: 2 })} грн`;
    }

    function applyLinkField(fieldName, value, label) {
        const $field = $(`[data-field="${fieldName}"]`);
        if (!$field.length) {
            return;
        }

        const normalizedUrl = normalizeExternalUrl(value);
        if (!normalizedUrl) {
            $field
                .attr('href', '#')
                .attr('aria-disabled', 'true')
                .addClass('fstu-app-card__link--disabled')
                .text(label.empty || '—');
            return;
        }

        $field
            .attr('href', normalizedUrl)
            .attr('aria-disabled', 'false')
            .removeClass('fstu-app-card__link--disabled')
            .text(label.filled || normalizedUrl);
    }

    function setCandidateActionButtonsData(item) {
        const userId = parseInt(item.User_ID || item.user_id || 0, 10) || 0;
        const unitId = parseInt(item.Unit_ID || item.unit_id || 0, 10) || 0;
        const candidateName = item.FIO || item.fio || item.display_name || item.user_login || '';
        const unitName = item.Unit_ShortName || item.unit_short_name || '';

        [
            $('#fstu-applications-view-accept'),
            $('#fstu-applications-view-reject'),
            $('#fstu-applications-view-change-ofst')
        ].forEach(function ($button) {
            $button
                .data('userId', userId)
                .data('candidateName', candidateName)
                .prop('disabled', !userId)
                .toggleClass('fstu-btn--disabled', !userId);
        });

        $('#fstu-applications-view-change-ofst')
            .data('unitId', unitId)
            .data('unitName', unitName);
    }

    function normalizeFieldValue(value, formatter) {
        if (value === null || value === undefined) {
            return '—';
        }

        const normalized = String(value).trim();
        if (!normalized) {
            return '—';
        }

        return typeof formatter === 'function' ? formatter(normalized) : normalized;
    }

    function formatDateValue(value) {
        const parsed = new Date(value);

        if (Number.isNaN(parsed.getTime())) {
            return value;
        }

        const day = String(parsed.getDate()).padStart(2, '0');
        const month = String(parsed.getMonth() + 1).padStart(2, '0');
        const year = parsed.getFullYear();
        const hours = String(parsed.getHours()).padStart(2, '0');
        const minutes = String(parsed.getMinutes()).padStart(2, '0');

        return `${day}.${month}.${year} ${hours}:${minutes}`;
    }

    function setCandidateModalTab(tabName) {
        const normalizedTab = String(tabName || 'main');
        const $modal = $('#fstu-applications-modal-view');

        $modal.find('.fstu-app-card-tabs__button').each(function () {
            const isActive = $(this).data('tab') === normalizedTab;
            $(this)
                .toggleClass('is-active', isActive)
                .attr('aria-selected', isActive ? 'true' : 'false')
                .attr('tabindex', isActive ? '0' : '-1');
        });

        $modal.find('.fstu-app-card__panel').each(function () {
            const isActive = $(this).data('tab-panel') === normalizedTab;
            $(this)
                .toggleClass('is-active', isActive)
                .prop('hidden', !isActive);
        });

        const $body = $modal.find('.fstu-modal__body');
        if ($body.length) {
            $body.scrollTop(0);
        }
    }

    function resetCandidateModalTabs() {
        setCandidateModalTab('main');
    }

    function resetCandidateModal() {
        const $modal = $('#fstu-applications-modal-view');

        $modal.find('[data-field]').each(function () {
            if ($(this).is('a')) {
                $(this).attr('href', '#').attr('aria-disabled', 'true').addClass('fstu-app-card__link--disabled').text('—');
            } else {
                $(this).text('—');
            }
        });

        $('#fstu-applications-modal-view-message').addClass('fstu-hidden').text('');
        const $paymentLink = $('#fstu-applications-payment-docs-link');
        $paymentLink.attr('href', '#').attr('aria-disabled', 'true').addClass('fstu-btn--disabled');
        setCandidateActionButtonsData({});
        resetCandidateModalTabs();
    }

    function populateCandidateModal(item) {
        const fieldMap = {
            fio: item.FIO || item.fio || item.display_name,
            email: item.email || item.user_email,
            birth_date: item.BirthDate || item.birth_date,
            sex: item.Sex || item.sex,
            registered_at: item.user_registered || item.registered_at,
            card_number: item.card_number,
            unit_name: item.Unit_ShortName || item.unit_short_name,
            region_name: item.Region_Name || item.region_name,
            city_name: item.City_Name || item.city_name,
            address: item.adr || item.address,
            phone: item.phone,
            phone2: item.phone2,
            tourism_type: item.tourism_type || item.TourismType_Name,
            club_name: item.club_name,
            job: item.job,
            post: item.post,
            education: item.education,
            sailing_experience: item.sailing_experience,
            suddy_category: item.suddy_category,
            public_tourism: item.public_tourism,
            payment_year: item.payment_info && item.payment_info.year ? item.payment_info.year : '',
            payment_amount: item.payment_info && item.payment_info.amount ? item.payment_info.amount : '',
            payment_created_at: item.payment_info && item.payment_info.created_at ? item.payment_info.created_at : '',
            payment_message: (item.payment_info && item.payment_info.message) ? item.payment_info.message : 'Оплата має інформаційний характер. Деталі доступні у модулі PaymentDocs.'
        };

        Object.keys(fieldMap).forEach(function (key) {
            const $field = $(`[data-field="${key}"]`);
            if (!$field.length) {
                return;
            }

            let formattedValue = normalizeFieldValue(fieldMap[key]);

            if (key === 'registered_at' || key === 'birth_date' || key === 'payment_created_at') {
                formattedValue = normalizeFieldValue(fieldMap[key], formatDateValue);
            } else if (key === 'payment_amount') {
                formattedValue = normalizeFieldValue(fieldMap[key], formatAmountValue);
            }

            $field.text(formattedValue);
        });

        applyLinkField('club_website', item.club_website, {
            empty: '—',
            filled: item.club_website || 'Відкрити сайт'
        });

        applyLinkField('payment_receipt_url', item.payment_info && item.payment_info.receipt_url ? item.payment_info.receipt_url : '', {
            empty: 'ВІДСУТНЄ ПОСИЛАННЯ',
            filled: 'ПОСИЛАННЯ'
        });

        const $paymentLink = $('#fstu-applications-payment-docs-link');
        if (fstuApplications.paymentDocsPageUrl) {
            $paymentLink
                .attr('href', fstuApplications.paymentDocsPageUrl)
                .removeAttr('aria-disabled')
                .removeClass('fstu-btn--disabled');
        } else {
            $paymentLink
                .attr('href', '#')
                .attr('aria-disabled', 'true')
                .addClass('fstu-btn--disabled');
        }

        setCandidateActionButtonsData(item);
    }

    function loadCandidateCard(userId) {
        resetCandidateModal();
        $('#fstu-applications-modal-view-message').removeClass('fstu-hidden').text(fstuApplications.strings.loading);
        openModal('fstu-applications-modal-view');

        if (candidateRequest && typeof candidateRequest.abort === 'function') {
            candidateRequest.abort();
        }

        candidateRequest = $.post(fstuApplications.ajaxUrl, {
            action: 'fstu_applications_get_single',
            nonce: fstuApplications.nonce,
            id: userId
        }, function (res) {
            if (res.success) {
                $('#fstu-applications-modal-view-message').addClass('fstu-hidden').text('');
                populateCandidateModal(res.data);
            } else {
                $('#fstu-applications-modal-view-message').removeClass('fstu-hidden').text((res.data && res.data.message) ? res.data.message : fstuApplications.strings.errorGeneric);
            }
        }).fail(function () {
            $('#fstu-applications-modal-view-message').removeClass('fstu-hidden').text(fstuApplications.strings.errorGeneric);
        }).always(function () {
            candidateRequest = null;
        });
    }

    function populateChangeOfstUnits(selectedUnitId) {
        const selectedValue = String(selectedUnitId || 0);
        let html = '<option value="0">— оберіть осередок —</option>';

        originalUnitOptions.forEach(function (option) {
            if (option.value === '0') {
                return;
            }

            const selected = option.value === selectedValue ? ' selected' : '';
            html += '<option value="' + escapeHtml(option.value) + '" data-region-id="' + escapeHtml(option.regionId) + '"' + selected + '>' + escapeHtml(option.text) + '</option>';
        });

        $('#fstu-applications-change-ofst-unit').html(html);
    }

    function openChangeOfstModal(userId, currentUnitId) {
        $('#fstu-applications-change-ofst-user-id').val(String(userId || 0));
        populateChangeOfstUnits(currentUnitId);
        $('#fstu-applications-change-ofst-message').addClass('fstu-hidden').removeClass('fstu-message--error fstu-message--success fstu-message--info').text('');
        $('#fstu-applications-change-ofst-form').find('button[type="submit"]').prop('disabled', false).removeClass('is-loading').text('Зберегти');
        openModal('fstu-applications-modal-change-ofst');
    }

    function showModalMessage(selector, message, type) {
        $(selector)
            .removeClass('fstu-hidden fstu-message--error fstu-message--success fstu-message--info')
            .addClass(type ? `fstu-message--${type}` : '')
            .text(message);
    }

    function resetAcceptModal() {
        $('#fstu-applications-accept-user-id').val('0');
        $('#fstu-applications-accept-candidate-name').text('—');
        $('#fstu-applications-accept-message').addClass('fstu-hidden').removeClass('fstu-message--error fstu-message--success fstu-message--info').text('');

        const $paymentLink = $('#fstu-applications-accept-payment-docs-link');
        if (fstuApplications.paymentDocsPageUrl) {
            $paymentLink.attr('href', fstuApplications.paymentDocsPageUrl).removeAttr('aria-disabled').removeClass('fstu-btn--disabled');
        } else {
            $paymentLink.attr('href', '#').attr('aria-disabled', 'true').addClass('fstu-btn--disabled');
        }

        $('#fstu-applications-accept-submit').prop('disabled', false).removeClass('is-loading').text(fstuApplications.strings.acceptSubmit);
    }

    function openAcceptModal(userId, candidateName) {
        resetAcceptModal();
        $('#fstu-applications-accept-user-id').val(String(userId || 0));
        $('#fstu-applications-accept-candidate-name').text(normalizeFieldValue(candidateName));
        openModal('fstu-applications-modal-accept');
    }

    function openChangeOfstModalWithMeta(userId, currentUnitId, candidateName, unitName) {
        $('#fstu-applications-change-ofst-candidate-name').text(normalizeFieldValue(candidateName));
        $('#fstu-applications-change-ofst-current-unit').text(normalizeFieldValue(unitName));
        openChangeOfstModal(userId, currentUnitId);
    }

    function resetRejectModal() {
        $('#fstu-applications-reject-user-id').val('0');
        $('#fstu-applications-reject-candidate-name').text('—');
        $('#fstu-applications-reject-message').addClass('fstu-hidden').removeClass('fstu-message--error fstu-message--success fstu-message--info').text('');
        $('#fstu-applications-reject-submit').prop('disabled', false).removeClass('is-loading').text(fstuApplications.strings.rejectSubmit);
    }

    function openRejectModal(userId, candidateName) {
        resetRejectModal();
        $('#fstu-applications-reject-user-id').val(String(userId || 0));
        $('#fstu-applications-reject-candidate-name').text(normalizeFieldValue(candidateName));
        openModal('fstu-applications-modal-message');
    }

    // 4. Генерація компактної пагінації
    function renderPagination(type, data) {
        const prefix = type === 'list' ? 'fstu-applications' : 'fstu-protocol';
        const $controls = $(`#${prefix}-pages`);
        const $info = $(`#${prefix}-info`);

        $info.text(`Записів: ${data.total} | Сторінка ${data.page} з ${Math.max(1, data.total_pages)}`);
        $controls.empty();

        if (data.total_pages <= 1) return;

        // Кнопка "Попередня"
        const btnPrev = $('<button>', {
            type: 'button',
            class: 'fstu-btn',
            text: '«',
            disabled: data.page === 1
        }).data('page', data.page - 1);
        $controls.append(btnPrev);

        // Поточна сторінка (візуально виділена)
        const btnCurrent = $('<button>', {
            type: 'button',
            class: 'fstu-btn fstu-btn--current',
            text: data.page,
            disabled: true
        });
        $controls.append(btnCurrent);

        // Кнопка "Наступна"
        const btnNext = $('<button>', {
            type: 'button',
            class: 'fstu-btn',
            text: '»',
            disabled: data.page === data.total_pages
        }).data('page', data.page + 1);
        $controls.append(btnNext);
    }

    // 5. Обробники подій (Event Bindings)
    function bindEvents() {
        $(document).on('click', function (event) {
            if (!$(event.target).closest('.fstu-applications-dropdown').length) {
                closeAllDropdowns();
            }
        });

        $(document).on('click', '[data-close-modal]', function () {
            closeModal($(this).data('close-modal'));
        });

        $(document).on('click', '.fstu-modal-overlay', function (event) {
            if ($(event.target).is('.fstu-modal-overlay')) {
                closeModal($(this).attr('id'));
            }
        });

        $(document).on('click', '.fstu-app-card-tabs__button', function () {
            const tabName = $(this).data('tab');
            if (!tabName) {
                return;
            }

            setCandidateModalTab(tabName);
        });

        $(document).on('keydown', function (event) {
            if (event.key === 'Escape') {
                $('.fstu-modal-overlay:not(.fstu-hidden)').each(function () {
                    closeModal($(this).attr('id'));
                });
            }
        });

        // --- ПЕРЕМИКАННЯ ЕКРАНІВ ---
        $('#fstu-btn-protocol').on('click', function () {
            $('#fstu-view-directory, #fstu-actions-directory').addClass('fstu-hidden');
            $('#fstu-view-protocol, #fstu-actions-protocol').removeClass('fstu-hidden');
            state.view = 'protocol';
            fetchProtocol();
        });

        $('#fstu-btn-back').on('click', function () {
            $('#fstu-view-protocol, #fstu-actions-protocol').addClass('fstu-hidden');
            $('#fstu-view-directory, #fstu-actions-directory').removeClass('fstu-hidden');
            state.view = 'list';
            fetchList();
        });

        // --- ОНОВЛЕННЯ ТАБЛИЦЬ ---
        $('#fstu-btn-refresh').on('click', fetchList);
        $('#fstu-btn-refresh-protocol').on('click', fetchProtocol);

        // --- ФІЛЬТРИ СПИСКУ ---
        $(document).on('change', '#fstu-filter-region', function () {
            state.list.region_id = parseInt($(this).val(), 10) || 0;
            state.list.page = 1;
            syncUnitOptions(state.list.region_id);
            fetchList();
        });

        $(document).on('change', '#fstu-filter-unit', function () {
            state.list.unit_id = parseInt($(this).val(), 10) || 0;
            state.list.page = 1;
            fetchList();
        });

        // --- DROPDOWN ДІЙ ---
        $(document).on('click', '.fstu-applications-dropdown__toggle', function (event) {
            event.preventDefault();
            event.stopPropagation();

            const $dropdown = $(this).closest('.fstu-applications-dropdown');
            const isOpen = $dropdown.hasClass('is-open');

            closeAllDropdowns();

            if (!isOpen) {
                $dropdown.addClass('is-open');
                positionDropdown($dropdown);
                $(this).attr('aria-expanded', 'true');
            }
        });

        // --- ПОШУК З DEBOUNCE (500ms) ---
        $('#fstu-applications-search').on('input', debounce(function () {
            state.list.search = $(this).val().trim();
            state.list.page = 1; // Скидаємо на першу сторінку при пошуку
            fetchList();
        }, 500));

        $('#fstu-protocol-filter-name').on('input', debounce(function () {
            state.protocol.search = $(this).val().trim();
            state.protocol.page = 1;
            fetchProtocol();
        }, 500));

        // --- КІЛЬКІСТЬ ЗАПИСІВ (PER PAGE) ---
        $('#fstu-applications-per-page').on('change', function () {
            state.list.per_page = parseInt($(this).val(), 10);
            state.list.page = 1;
            fetchList();
        });

        $('#fstu-protocol-per-page').on('change', function () {
            state.protocol.per_page = parseInt($(this).val(), 10);
            state.protocol.page = 1;
            fetchProtocol();
        });

        // --- ПАГІНАЦІЯ (Делегування подій) ---
        $(document).on('click', '.fstu-pagination__controls button:not(:disabled)', function () {
            const targetPage = $(this).data('page');
            if (state.view === 'list') {
                state.list.page = targetPage;
                fetchList();
            } else {
                state.protocol.page = targetPage;
                fetchProtocol();
            }
        });

        // --- ДІЇ З КАНДИДАТОМ (CRUD через Dropdown) ---

        // 1. Прийняти в члени
        $(document).on('click', '.fstu-action-accept', function () {
            closeAllDropdowns();
            const userId = parseInt($(this).data('id'), 10) || 0;
            const candidateName = $(this).data('candidate-name') || '';

            if (!userId) {
                return;
            }

            openAcceptModal(userId, candidateName);
        });

        // 2. Відхилити (Soft Delete)
        $(document).on('click', '.fstu-action-reject', function () {
            closeAllDropdowns();
            const userId = parseInt($(this).data('id'), 10) || 0;
            const candidateName = $(this).data('candidate-name') || '';

            if (!userId) {
                return;
            }

            openRejectModal(userId, candidateName);
        });

        // 3. Перегляд (Тут будемо відкривати модалку)
        $(document).on('click', '.fstu-action-view', function () {
            closeAllDropdowns();
            const userId = $(this).data('id');
            if (!userId) {
                return;
            }

            loadCandidateCard(userId);
        });

        $(document).on('click', '#fstu-applications-view-accept', function () {
            const userId = parseInt($(this).data('userId'), 10) || 0;
            const candidateName = $(this).data('candidateName') || '';

            if (!userId) {
                return;
            }

            closeModal('fstu-applications-modal-view');
            openAcceptModal(userId, candidateName);
        });

        $(document).on('click', '#fstu-applications-view-reject', function () {
            const userId = parseInt($(this).data('userId'), 10) || 0;
            const candidateName = $(this).data('candidateName') || '';

            if (!userId) {
                return;
            }

            closeModal('fstu-applications-modal-view');
            openRejectModal(userId, candidateName);
        });

        $(document).on('click', '#fstu-applications-view-change-ofst', function () {
            const userId = parseInt($(this).data('userId'), 10) || 0;
            const unitId = parseInt($(this).data('unitId'), 10) || 0;
            const candidateName = $(this).data('candidateName') || '';
            const unitName = $(this).data('unitName') || '';

            if (!userId) {
                return;
            }

            closeModal('fstu-applications-modal-view');
            openChangeOfstModalWithMeta(userId, unitId, candidateName, unitName);
        });

        $(document).on('click', '.fstu-action-change-ofst', function () {
            closeAllDropdowns();

            const userId = parseInt($(this).data('id'), 10) || 0;
            const currentUnitId = parseInt($(this).data('unit-id'), 10) || 0;
            const candidateName = $(this).data('candidate-name') || '';
            const unitName = $(this).data('unit-name') || '';

            if (!userId) {
                return;
            }

            openChangeOfstModalWithMeta(userId, currentUnitId, candidateName, unitName);
        });

        $(document).on('submit', '#fstu-applications-accept-form', function (event) {
            event.preventDefault();

            const userId = parseInt($('#fstu-applications-accept-user-id').val(), 10) || 0;
            const $submit = $('#fstu-applications-accept-submit');

            if (!userId) {
                showModalMessage('#fstu-applications-accept-message', fstuApplications.strings.acceptError, 'error');
                return;
            }

            if (acceptRequest && typeof acceptRequest.abort === 'function') {
                acceptRequest.abort();
            }

            $submit.prop('disabled', true).addClass('is-loading').text(fstuApplications.strings.loading);
            showModalMessage('#fstu-applications-accept-message', fstuApplications.strings.loading, 'info');

            acceptRequest = $.post(fstuApplications.ajaxUrl, {
                action: 'fstu_applications_accept',
                nonce: fstuApplications.nonce,
                id: userId
            }, function (res) {
                if (res.success) {
                    showModalMessage('#fstu-applications-accept-message', res.data.message || fstuApplications.strings.acceptSuccess, 'success');
                    fetchList();
                    window.setTimeout(function () {
                        closeModal('fstu-applications-modal-accept');
                    }, 500);
                } else {
                    showModalMessage('#fstu-applications-accept-message', (res.data && res.data.message) ? res.data.message : fstuApplications.strings.acceptError, 'error');
                }
            }).fail(function () {
                showModalMessage('#fstu-applications-accept-message', fstuApplications.strings.acceptError, 'error');
            }).always(function () {
                acceptRequest = null;
                $submit.prop('disabled', false).removeClass('is-loading').text(fstuApplications.strings.acceptSubmit);
            });
        });

        $(document).on('submit', '#fstu-applications-change-ofst-form', function (event) {
            event.preventDefault();

            const $form = $(this);
            const $submit = $form.find('button[type="submit"]');
            const honeypotValue = ($form.find('input[name="fstu_website"]').val() || '').toString().trim();
            if (honeypotValue !== '') {
                showModalMessage('#fstu-applications-change-ofst-message', fstuApplications.strings.errorGeneric, 'error');
                return;
            }

            const userId = parseInt($('#fstu-applications-change-ofst-user-id').val(), 10) || 0;
            const unitId = parseInt($('#fstu-applications-change-ofst-unit').val(), 10) || 0;

            if (!userId || !unitId) {
                showModalMessage('#fstu-applications-change-ofst-message', fstuApplications.strings.changeOfstNoUnit, 'error');
                return;
            }

            if (changeOfstRequest && typeof changeOfstRequest.abort === 'function') {
                changeOfstRequest.abort();
            }

            $submit.prop('disabled', true).addClass('is-loading').text(fstuApplications.strings.loading);
            showModalMessage('#fstu-applications-change-ofst-message', fstuApplications.strings.loading, 'info');

            changeOfstRequest = $.post(fstuApplications.ajaxUrl, {
                action: 'fstu_applications_change_ofst',
                nonce: fstuApplications.nonce,
                user_id: userId,
                unit_id: unitId,
                fstu_website: honeypotValue
            }, function (res) {
                if (res.success) {
                    const selectedText = (res.data && res.data.unit_name) ? res.data.unit_name : $('#fstu-applications-change-ofst-unit option:selected').text();
                    $('#fstu-applications-change-ofst-current-unit').text(normalizeFieldValue(selectedText));
                    showModalMessage('#fstu-applications-change-ofst-message', res.data.message || fstuApplications.strings.changeOfstSuccess, 'success');
                    fetchList();
                    window.setTimeout(function () {
                        closeModal('fstu-applications-modal-change-ofst');
                    }, 500);
                } else {
                    showModalMessage('#fstu-applications-change-ofst-message', (res.data && res.data.message) ? res.data.message : fstuApplications.strings.changeOfstError, 'error');
                }
            }).fail(function () {
                showModalMessage('#fstu-applications-change-ofst-message', fstuApplications.strings.changeOfstError, 'error');
            }).always(function () {
                changeOfstRequest = null;
                $submit.prop('disabled', false).removeClass('is-loading').text('Зберегти');
            });
        });

        $(document).on('submit', '#fstu-applications-reject-form', function (event) {
            event.preventDefault();

            const userId = parseInt($('#fstu-applications-reject-user-id').val(), 10) || 0;
            const $submit = $('#fstu-applications-reject-submit');
            const honeypotValue = ($('#fstu-applications-reject-honeypot').val() || '').toString().trim();

            if (honeypotValue !== '') {
                showModalMessage('#fstu-applications-reject-message', fstuApplications.strings.errorGeneric, 'error');
                return;
            }

            if (!userId) {
                showModalMessage('#fstu-applications-reject-message', fstuApplications.strings.rejectError, 'error');
                return;
            }

            if (rejectRequest && typeof rejectRequest.abort === 'function') {
                rejectRequest.abort();
            }

            $submit.prop('disabled', true).addClass('is-loading').text(fstuApplications.strings.loading);
            showModalMessage('#fstu-applications-reject-message', fstuApplications.strings.loading, 'info');

            rejectRequest = $.post(fstuApplications.ajaxUrl, {
                action: 'fstu_applications_reject',
                nonce: fstuApplications.nonce,
                id: userId,
                fstu_website: honeypotValue
            }, function (res) {
                if (res.success) {
                    showModalMessage('#fstu-applications-reject-message', res.data.message || fstuApplications.strings.rejectSuccess, 'success');
                    fetchList();
                    window.setTimeout(function () {
                        closeModal('fstu-applications-modal-message');
                    }, 500);
                } else {
                    showModalMessage('#fstu-applications-reject-message', (res.data && res.data.message) ? res.data.message : fstuApplications.strings.rejectError, 'error');
                }
            }).fail(function () {
                showModalMessage('#fstu-applications-reject-message', fstuApplications.strings.rejectError, 'error');
            }).always(function () {
                rejectRequest = null;
                $submit.prop('disabled', false).removeClass('is-loading').text(fstuApplications.strings.rejectSubmit);
            });
        });

        $(document).on('click', '#fstu-applications-payment-docs-link[aria-disabled="true"], #fstu-applications-accept-payment-docs-link[aria-disabled="true"]', function (event) {
            event.preventDefault();
            const modalSelector = $(this).attr('id') === 'fstu-applications-accept-payment-docs-link'
                ? '#fstu-applications-accept-message'
                : '#fstu-applications-modal-view-message';

            showModalMessage(modalSelector, fstuApplications.strings.paymentDocsNotConfigured, 'error');
        });

        $(document).on('click', '.fstu-app-card__link[aria-disabled="true"]', function (event) {
            event.preventDefault();
        });

        // Інші дії (Зміна ОФСТ, Повідомлення) також прив'язуються тут...
    }

    // 6. Ініціалізація
    syncUnitOptions(state.list.region_id);
    bindEvents();
    fetchList(); // Завантажуємо таблицю при старті
});