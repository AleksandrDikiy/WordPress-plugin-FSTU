/**
 * Логіка модуля Presidium (Президія ФСТУ).
 *
 * Version:     1.0.0
 * Date_update: 2026-04-16
 */

jQuery(document).ready(function($) {
    'use strict';

    var $module = $('#fstu-presidium-module');
    if (!$module.length) return;

    var config = window.fstuPresidiumObj || {};
    var $container = $('#fstu-presidium-view-container');
    var $loader = $('.fstu-loader');

    // Ініціалізація
    loadPresidium();

    function loadPresidium() {
        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'fstu_presidium_get_members',
                nonce: config.nonce
            },
            success: function(response) {
                $loader.hide();
                if (response.success) {
                    renderGrid(response.data.items || []);
                } else {
                    $container.html('<div class="fstu-alert fstu-alert--danger">' + config.i18n.error + '</div>').show();
                }
            },
            error: function() {
                $loader.hide();
                $container.html('<div class="fstu-alert fstu-alert--danger">' + config.i18n.error + '</div>').show();
            }
        });
    }

    function renderGrid(items) {
        if (!items.length) {
            $container.html('<div class="fstu-alert fstu-alert--info">Дані про склад Президії тимчасово відсутні.</div>').show();
            return;
        }

        var svgAvatar = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iI2NiZDVlMSI+PHBhdGggZD0iTTEyIDEyYzIuNzYgMCA1LTIuMjQgNS01cy0yLjI0LTUtNS01LTUgMi4yNC01IDUgMi4yNCA1IDUgNXptMCAyYy0zLjMzIDAtMTAgMS42Ny0xMCA1djJoMjB2LTJjMC0zLjMzLTYuNjctNS0xMC01eiIvPjwvc3ZnPg==';
        var html = '<div class="fstu-presidium-grid">';

        $.each(items, function(i, item) {
            // Використовуємо проксі-ендпоїнт для фото
            var photoUrl = config.ajaxUrl + '?action=fstu_presidium_get_photo&id=' + item.User_ID + '&nonce=' + config.nonce;
            var personalUrl = '/personal/?ViewID=' + item.User_ID;

            html += '<a href="' + personalUrl + '" target="_blank" class="fstu-member-card">';
            html += '<div class="fstu-member-card__photo"><img src="' + photoUrl + '" alt="Photo" onerror="this.onerror=null; this.src=\'' + svgAvatar + '\'"></div>';
            html += '<div class="fstu-member-card__content">';
            html += '<div class="fstu-member-card__role">' + escapeHtml(item.MemberGuidance_Name) + '</div>';
            html += '<div class="fstu-member-card__name">' + escapeHtml(item.FIO) + '</div>';

            if (item.email) {
                html += '<div class="fstu-contact"><span class="dashicons dashicons-email"></span> ' + escapeHtml(item.email) + '</div>';
            }

            html += '</div></a>';
        });

        html += '</div>';

        // Додаємо підсумок: загальна кількість
        html += '<div class="fstu-presidium-total" style="margin-top: 20px; font-weight: 500; text-align: right; color: #64748b; font-size: 14px; padding-right: 10px;">';
        html += 'Загальна кількість: <strong>' + items.length + '</strong>';
        html += '</div>';

        $container.html(html).fadeIn(300);
    }

    function escapeHtml(text) {
        if (!text) return '';
        var map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});