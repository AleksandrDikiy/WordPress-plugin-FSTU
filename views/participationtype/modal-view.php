<?php
/**
 * Модальне вікно перегляду виду участі.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-12
 *
 * @package FSTU\Dictionaries\ParticipationType
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-participationtype-view-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--compact fstu-modal--participationtype-view" role="dialog" aria-modal="true" aria-labelledby="fstu-participationtype-view-title">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-participationtype-view-title"><?php esc_html_e( 'Перегляд запису', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-participationtype-view-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body" id="fstu-participationtype-view-body">
			<p class="fstu-loader-inline"><?php esc_html_e( 'Завантаження...', 'fstu' ); ?></p>
		</div>
		<div class="fstu-modal__footer" id="fstu-participationtype-view-footer"></div>
	</div>
</div>

