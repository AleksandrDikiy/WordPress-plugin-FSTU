/**
 * Клієнтська логіка модуля Directory (Довідник Виконкому).
 *
 * Version:     1.1.0
 * Date_update: 2026-04-16
 */

jQuery(document).ready(function($) {
    'use strict';

    var $module = $('#fstu-directory-module');
    if (!$module.length) return;

    var config = window.fstuDirectoryObj || {};
    var $viewContainer = $('#fstu-directory-view-container');
    var $loader = $module.find('.fstu-loader');

    // Ініціалізація
    function init() {
        loadMembers();
    }

    // --- AJAX ЗАВАНТАЖЕННЯ ---
    function loadMembers() {
        $viewContainer.hide();
        $loader.show();

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'fstu_directory_get_members',
                nonce: config.nonce
            },
            success: function(response) {
                $loader.hide();
                if (response.success) {
                    renderMembersGrid(response.data.items || []);
                } else {
                    $viewContainer.html('<div class="fstu-alert fstu-alert--danger">' + (response.data.message || config.i18n.error) + '</div>').show();
                }
            },
            error: function() {
                $loader.hide();
                $viewContainer.html('<div class="fstu-alert fstu-alert--danger">' + config.i18n.error + '</div>').show();
            }
        });
    }

    // --- РЕНДЕРІНГ КАРТОК ---
    function renderMembersGrid(items) {
        if (!items.length) {
            $viewContainer.html('<div class="fstu-alert fstu-alert--info">Немає даних для відображення.</div>').show();
            return;
        }

        var fallbackAvatar = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iI2NiZDVlMSI+PHBhdGggZD0iTTEyIDEyYzIuNzYgMCA1LTIuMjQgNS01cy0yLjI0LTUtNS01LTUgMi4yNC01IDUgMi4yNCA1IDUgNXptMCAyYy0zLjMzIDAtMTAgMS42Ny0xMCA1djJoMjB2LTJjMC0zLjMzLTYuNjctNS0xMC01eiIvPjwvc3ZnPg==';
        var html = '<div class="fstu-members-grid">';

        $.each(items, function(index, item) {
            var photoUrl = config.ajaxUrl + '?action=fstu_get_photo&id=' + item.User_ID + '&nonce=' + config.nonce;
            var cabinetUrl = '/personal/?ViewID=' + item.User_ID;

            html += '<a href="' + cabinetUrl + '" target="_blank" class="fstu-member-card">';
            html += '<div class="fstu-member-card__photo"><img src="' + photoUrl + '" alt="Photo" onerror="this.onerror=null; this.src=\'' + fallbackAvatar + '\'"></div>';
            html += '<div class="fstu-member-card__content">';
            html += '<div class="fstu-member-card__role">' + escapeHtml(item.MemberGuidance_Name) + '</div>';
            html += '<div class="fstu-member-card__name">' + escapeHtml(item.FIO) + '</div>';

            if (config.permissions.canViewContactsInList) {
                if (item.email) html += '<div class="fstu-contact"><span class="dashicons dashicons-email"></span> ' + escapeHtml(item.email) + '</div>';
                if (item.PhoneMobile) html += '<div class="fstu-contact"><span class="dashicons dashicons-phone"></span> ' + escapeHtml(item.PhoneMobile) + '</div>';
            }

            html += '</div></a>';
        });

        html += '</div>';

        // Додаємо підсумок: загальна кількість
        html += '<div class="fstu-directory-total" style="margin-top: 20px; font-weight: 500; text-align: right; color: #64748b; font-size: 14px; padding-right: 10px;">';
        html += 'Загальна кількість: <strong>' + items.length + '</strong>';
        html += '</div>';

        $viewContainer.html(html).show();
    }

    // Утиліта екранування (XSS захист)
    function escapeHtml(text) {
        if (!text) return '';
        var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Запуск
    init();
});