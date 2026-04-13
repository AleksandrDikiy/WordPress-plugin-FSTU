<?php
/**
 * Модальне вікно перегляду категорії походів.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Dictionaries\HikingCategory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-hikingcategory-view-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--compact fstu-modal--hikingcategory-view" role="dialog" aria-modal="true" aria-labelledby="fstu-hikingcategory-view-title">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-hikingcategory-view-title"><?php esc_html_e( 'Перегляд запису', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-hikingcategory-view-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body" id="fstu-hikingcategory-view-body">
			<p class="fstu-loader-inline"><?php esc_html_e( 'Завантаження...', 'fstu' ); ?></p>
		</div>
		<div class="fstu-modal__footer" id="fstu-hikingcategory-view-footer"></div>
	</div>
</div>

