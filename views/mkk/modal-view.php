<?php
/**
 * Модальне вікно перегляду запису МКК.
 *
 * Version:     1.0.1
 * Date_update: 2026-04-12
 *
 * @package FSTU\Modules\UserFstu\MKK
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-mkk-view-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--compact fstu-modal--mkk-view" role="dialog" aria-modal="true" aria-labelledby="fstu-mkk-view-title">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-mkk-view-title"><?php esc_html_e( 'Перегляд запису МКК', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-mkk-view-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body" id="fstu-mkk-view-body">
			<p class="fstu-loader-inline"><?php esc_html_e( 'Завантаження...', 'fstu' ); ?></p>
		</div>
		<div class="fstu-modal__footer" id="fstu-mkk-view-footer"></div>
	</div>
</div>

