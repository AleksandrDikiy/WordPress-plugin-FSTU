jQuery(document).ready(function($) {
    'use strict';

    const $input = $('#fstu-hub-search');
    const $clearBtn = $('#fstu-hub-search-clear');
    const $cards = $('.fstu-hub-card');
    const $categories = $('.fstu-hub-category');
    const $noResults = $('#fstu-hub-no-results');

    // Фільтрація карток при введенні тексту
    $input.on('input', function() {
        const val = $(this).val().trim().toLowerCase();

        // Показуємо/ховаємо хрестик
        $clearBtn.toggleClass('fstu-hidden', val === '');

        let visibleCardsTotal = 0;

        $categories.each(function() {
            const $cat = $(this);
            let visibleInCat = 0;

            $cat.find('.fstu-hub-card').each(function() {
                const title = $(this).data('title');
                if (title.indexOf(val) !== -1) {
                    $(this).removeClass('fstu-hidden');
                    visibleInCat++;
                    visibleCardsTotal++;
                } else {
                    $(this).addClass('fstu-hidden');
                }
            });

            // Якщо в категорії немає видимих карток, ховаємо всю категорію
            $cat.toggleClass('fstu-hidden', visibleInCat === 0);
        });

        // Показуємо напис "Нічого не знайдено", якщо всі картки сховані
        $noResults.toggleClass('fstu-hidden', visibleCardsTotal > 0);
    });

    // Очищення пошуку
    $clearBtn.on('click', function() {
        $input.val('').trigger('input');
        $input.focus();
    });
});