<?php
/**
 * Модальне вікно перегляду суддівської категорії.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Dictionaries\RefereeCategory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-referee-category-view-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--compact fstu-modal--referee-category-view" role="dialog" aria-modal="true" aria-labelledby="fstu-referee-category-view-title">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-referee-category-view-title"><?php esc_html_e( 'Перегляд запису', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-referee-category-view-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body" id="fstu-referee-category-view-body">
			<p class="fstu-loader-inline"><?php esc_html_e( 'Завантаження...', 'fstu' ); ?></p>
		</div>
		<div class="fstu-modal__footer" id="fstu-referee-category-view-footer"></div>
	</div>
</div>

