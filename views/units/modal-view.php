<?php
/**
 * Модальне вікно перегляду деталей осередка.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\Units
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-units-modal-view" class="fstu-modal-overlay fstu-units-hidden" aria-hidden="true">
	<div class="fstu-modal" role="dialog" aria-modal="true" aria-labelledby="fstu-units-modal-title">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-units-modal-title"><?php esc_html_e( 'Перегляд осередка', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal-close-btn" id="fstu-units-modal-view-close" title="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>" aria-label="<?php esc_attr_e( 'Закрити перегляд осередка', 'fstu' ); ?>">
				✕
			</button>
		</div>
		<div class="fstu-modal__body" id="fstu-units-modal-body">
			<!-- Контент буде завантажено через JS -->
		</div>
	</div>
</div>

