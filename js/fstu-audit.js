/**
 * JS логіка для модуля "Ревізійна комісія"
 * Version: 1.0.0
 * Date_update: 2026-04-25
 */

jQuery(document).ready(function($) {
    var $container = $('#fstu-audit-grid-container');
    var $loader    = $('.fstu-loader');

    function escapeHtml(text) {
        if (!text) return '';
        var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function loadMembers() {
        $loader.show();
        $container.css('opacity', '0.5');

        $.ajax({
            url: fstuAuditObj.ajaxUrl,
            type: 'POST',
            data: {
                action: 'fstu_audit_get_members',
                nonce: fstuAuditObj.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderGrid(response.data.items);
                } else {
                    alert(response.data.message || fstuAuditObj.i18n.error);
                }
            },
            error: function() {
                alert(fstuAuditObj.i18n.error);
            },
            complete: function() {
                $loader.hide();
                $container.css('opacity', '1');
            }
        });
    }

    function renderGrid(items) {
        $container.empty();
        if (!items || items.length === 0) {
            $container.html('<p>Записів не знайдено.</p>');
            return;
        }

        var html = '';
        var canDelete   = fstuAuditObj.permissions.canDelete;
        var canContacts = fstuAuditObj.permissions.canViewContacts;

        $.each(items, function(index, item) {
            var photoUrl = fstuAuditObj.ajaxUrl + '?action=fstu_get_photo&id=' + item.User_ID;
            var profileUrl = '/userfstu?ViewID=' + item.User_ID;

            html += '<div class="fstu-audit-card" data-guidance-id="' + escapeHtml(item.Guidance_ID) + '" data-user-id="' + escapeHtml(item.User_ID) + '">';

            if (canDelete) {
                html += '<button type="button" class="fstu-audit-card__delete" title="Видалити">✖</button>';
            }

            html += '<div class="fstu-audit-card__photo-wrapper">';
            html += '<img src="' + escapeHtml(photoUrl) + '" alt="Photo" loading="lazy">';
            html += '</div>';

            html += '<div class="fstu-audit-card__role">' + escapeHtml(item.MemberGuidance_Name) + '</div>';
            html += '<div class="fstu-audit-card__name"><a href="' + escapeHtml(profileUrl) + '" target="_blank">' + escapeHtml(item.FIO) + '</a></div>';

            if (item.email) {
                if (canContacts) {
                    html += '<div class="fstu-audit-card__email-box" data-copy="' + escapeHtml(item.email) + '" title="Клікніть, щоб скопіювати">';
                    html += '✉ ' + escapeHtml(item.email);
                    html += '</div>';
                } else {
                    html += '<span class="fstu-hidden-contact">[Приховано для гостей]</span>';
                }
            }

            html += '</div>';
        });

        $container.html(html);
    }

    $(document).on('click', '.fstu-audit-card__email-box', function() {
        var textToCopy = $(this).data('copy');
        var $btn = $(this);
        navigator.clipboard.writeText(textToCopy).then(function() {
            var originalHtml = $btn.html();
            $btn.addClass('copied').html('✓ ' + fstuAuditObj.i18n.copied);
            setTimeout(function() {
                $btn.removeClass('copied').html(originalHtml);
            }, 2000);
        });
    });

    $(document).on('click', '.fstu-audit-card__delete', function() {
        if (!confirm(fstuAuditObj.i18n.confirm_del)) return;

        var $card = $(this).closest('.fstu-audit-card');
        var guidanceId = $card.data('guidance-id');
        var userId = $card.data('user-id');

        $.ajax({
            url: fstuAuditObj.ajaxUrl,
            type: 'POST',
            data: {
                action: 'fstu_audit_delete',
                nonce: fstuAuditObj.nonce,
                guidance_id: guidanceId,
                user_id: userId
            },
            success: function(response) {
                if (response.success) {
                    loadMembers();
                } else {
                    alert(response.data.message || fstuAuditObj.i18n.error);
                }
            },
            error: function() { alert(fstuAuditObj.i18n.error); }
        });
    });

    loadMembers();
});