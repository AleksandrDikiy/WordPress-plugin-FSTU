<?php
/**
 * View: Сторінка Хабу Довідників (Картки).
 *
 * @var array $categories Масив категорій з довідниками.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="fstu-hub-wrap">

    <div class="fstu-filter-bar">
        <div class="fstu-search-wrap">
            <span class="fstu-search-icon" aria-hidden="true">🔍</span>
            <input type="text" id="fstu-hub-search" class="fstu-search-input" placeholder="Швидкий пошук довідника..." autocomplete="off">
            <button type="button" id="fstu-hub-search-clear" class="fstu-search-clear fstu-hidden" aria-label="Очистити пошук">✕</button>
        </div>
    </div>

    <div class="fstu-hub-container" id="fstu-hub-container">
        <?php foreach ( $categories as $category ) : ?>

            <div class="fstu-hub-category">
                <h2 class="fstu-hub-category-title">
                    <span aria-hidden="true"><?php echo esc_html( $category['icon'] ); ?></span>
                    <?php echo esc_html( $category['title'] ); ?>
                </h2>

                <div class="fstu-hub-grid">
                    <?php foreach ( $category['items'] as $item ) : ?>
                        <a href="<?php echo esc_url( $item['url'] ); ?>" class="fstu-hub-card" data-title="<?php echo esc_attr( strtolower( $item['title'] ) ); ?>">
                            <div class="fstu-hub-card-content">
                                <h3 class="fstu-hub-card-title"><?php echo esc_html( $item['title'] ); ?></h3>
                                <p class="fstu-hub-card-desc"><?php echo esc_html( $item['desc'] ); ?></p>
                            </div>
                            <div class="fstu-hub-card-arrow" aria-hidden="true">→</div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php endforeach; ?>

        <div id="fstu-hub-no-results" class="fstu-hidden" style="padding: 20px; text-align: center; color: #7f8c8d;">
            Довідників за вашим запитом не знайдено.
        </div>
    </div>

</div>