/**
 * JS логіка для модуля "Рада ветеранів"
 * Version: 1.1.0
 * Date_update: 2026-04-25
 */

jQuery(document).ready(function($) {
    var $container = $('#fstu-veterans-grid-container');
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
            url: fstuVeteransObj.ajaxUrl,
            type: 'POST',
            data: {
                action: 'fstu_veterans_get_members',
                nonce: fstuVeteransObj.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderGrid(response.data.items);
                } else {
                    alert(response.data.message || fstuVeteransObj.i18n.error);
                }
            },
            error: function() {
                alert(fstuVeteransObj.i18n.error);
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
        var canDelete   = fstuVeteransObj.permissions.canDelete;
        var canContacts = fstuVeteransObj.permissions.canViewContacts;

        $.each(items, function(index, item) {
            var photoUrl = fstuVeteransObj.ajaxUrl + '?action=fstu_get_photo&id=' + item.User_ID;
            var profileUrl = '/userfstu?ViewID=' + item.User_ID;

            html += '<div class="fstu-veterans-card" data-guidance-id="' + escapeHtml(item.Guidance_ID) + '" data-user-id="' + escapeHtml(item.User_ID) + '">';

            if (canDelete) {
                html += '<button type="button" class="fstu-veterans-card__delete" title="Видалити">✖</button>';
            }

            html += '<div class="fstu-veterans-card__photo-wrapper">';
            html += '<img src="' + escapeHtml(photoUrl) + '" alt="Photo" loading="lazy">';
            html += '</div>';

            html += '<div class="fstu-veterans-card__role">' + escapeHtml(item.MemberGuidance_Name) + '</div>';
            html += '<div class="fstu-veterans-card__name"><a href="' + escapeHtml(profileUrl) + '" target="_blank">' + escapeHtml(item.FIO) + '</a></div>';

            // EMAIL (стилізований як на фото)
            if (item.email) {
                if (canContacts) {
                    html += '<div class="fstu-veterans-card__email-box" data-copy="' + escapeHtml(item.email) + '" title="Клікніть, щоб скопіювати">';
                    html += '✉ ' + escapeHtml(item.email);
                    html += '</div>';
                } else {
                    html += '<span class="fstu-hidden-contact">[Приховано для гостей]</span>';
                }
            }

            html += '</div>'; // close card
        });

        $container.html(html);
    }

    // Копіювання Email при кліку на рамку
    $(document).on('click', '.fstu-veterans-card__email-box', function() {
        var textToCopy = $(this).data('copy');
        var $btn = $(this);
        navigator.clipboard.writeText(textToCopy).then(function() {
            var originalHtml = $btn.html();
            $btn.addClass('copied').html('✓ ' + fstuVeteransObj.i18n.copied);
            setTimeout(function() {
                $btn.removeClass('copied').html(originalHtml);
            }, 2000);
        });
    });

    // Видалення
    $(document).on('click', '.fstu-veterans-card__delete', function() {
        if (!confirm(fstuVeteransObj.i18n.confirm_del)) return;

        var $card = $(this).closest('.fstu-veterans-card');
        var guidanceId = $card.data('guidance-id');
        var userId = $card.data('user-id');

        $.ajax({
            url: fstuVeteransObj.ajaxUrl,
            type: 'POST',
            data: {
                action: 'fstu_veterans_delete',
                nonce: fstuVeteransObj.nonce,
                guidance_id: guidanceId,
                user_id: userId
            },
            success: function(response) {
                if (response.success) {
                    loadMembers(); // Оновлюємо список
                } else {
                    alert(response.data.message || fstuVeteransObj.i18n.error);
                }
            },
            error: function() { alert(fstuVeteransObj.i18n.error); }
        });
    });

    // Ініціалізація
    loadMembers();
});