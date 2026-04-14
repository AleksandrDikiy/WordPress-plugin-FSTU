/**
 * Скрипти адмін-панелі (Хаб шорткодів).
 * Version: 1.1.0
 * Date_update: 2026-04-13
 */
jQuery(document).ready(function($) {
    'use strict';

    // 1. Вкладки (Tabs)
    $(document).on('click', '.fstu-nav-tabs .nav-tab', function(e) {
        e.preventDefault();
        var targetTab = $(this).data('tab');

        $('.fstu-nav-tabs .nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        $('.fstu-tab-pane').hide();
        $('#tab-' + targetTab).css('display', 'grid');
    });

    // 2. Live Search
    $('#fstu-shortcode-search').on('input', function() {
        var searchTerm = $(this).val().toLowerCase().trim();

        $('.fstu-shortcode-card').each(function() {
            var searchData = $(this).data('search');
            if (searchData.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // 3. Копіювання простих шорткодів
    $(document).on('click', '.fstu-btn--copy-simple', function() {
        var textToCopy = $(this).data('clipboard');
        navigator.clipboard.writeText(textToCopy).then(() => {
            fstuShowToast('Шорткод скопійовано!');
        });
    });

    // 4. Генератор: Відкриття модалки
    var currentGeneratorTag = '';

    $(document).on('click', '.fstu-btn--open-generator', function() {
        currentGeneratorTag = $(this).data('tag');
        var config = $(this).data('config');
        var fieldsHtml = '';

        // Динамічна генерація полів на основі JSON конфігурації
        $.each(config, function(attrName, attrData) {
            fieldsHtml += '<div style="margin-bottom: 15px;">';
            fieldsHtml += '<label style="display: block; font-weight: bold; margin-bottom: 5px;">' + attrData.label + '</label>';

            if (attrData.type === 'select') {
                fieldsHtml += '<select class="fstu-generator-input" data-attr="' + attrName + '" data-default="' + attrData.default + '" style="width: 100%;">';
                $.each(attrData.options, function(val, text) {
                    var selected = (val === attrData.default) ? 'selected' : '';
                    fieldsHtml += '<option value="' + val + '" ' + selected + '>' + text + '</option>';
                });
                fieldsHtml += '</select>';
            } else {
                fieldsHtml += '<input type="text" class="fstu-generator-input fstu-input" data-attr="' + attrName + '" data-default="' + attrData.default + '" value="' + attrData.default + '" style="width: 100%;">';
            }
            fieldsHtml += '</div>';
        });

        $('#fstu-generator-fields').html(fieldsHtml);
        updateShortcodePreview();
        $('#fstu-generator-modal').css('display', 'flex').hide().fadeIn(200);
    });

    // 5. Генератор: Оновлення прев'ю при зміні полів
    $(document).on('change input', '.fstu-generator-input', function() {
        updateShortcodePreview();
    });

    function updateShortcodePreview() {
        var finalShortcode = '[' + currentGeneratorTag;

        $('.fstu-generator-input').each(function() {
            var val = $(this).val();
            var def = $(this).data('default');
            var attr = $(this).data('attr');

            // Додаємо атрибут тільки якщо він відрізняється від дефолтного (щоб не засмічувати код)
            if (val !== def && val !== '') {
                finalShortcode += ' ' + attr + '="' + val + '"';
            }
        });

        finalShortcode += ']';
        $('#fstu-generator-result').val(finalShortcode);
    }

    // 6. Генератор: Копіювання та закриття
    $('#fstu-generator-copy-btn').on('click', function() {
        var textToCopy = $('#fstu-generator-result').val();
        navigator.clipboard.writeText(textToCopy).then(() => {
            fstuShowToast('Згенерований шорткод скопійовано!');
            $('#fstu-generator-modal').fadeOut(200);
        });
    });

    // Закриття модалки
    $('.fstu-modal-close, .fstu-modal-close-btn').on('click', function() {
        $('#fstu-generator-modal').fadeOut(200);
    });

    // Простий Toast для повідомлень (за потреби перенесіть у глобальний JS)
    function fstuShowToast(message) {
        var toast = $('<div style="position: fixed; bottom: 20px; right: 20px; background: #4CAF50; color: white; padding: 10px 20px; border-radius: 4px; z-index: 999999; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">' + message + '</div>');
        $('body').append(toast);
        setTimeout(function() { toast.fadeOut(400, function() { $(this).remove(); }); }, 3000);
    }
});