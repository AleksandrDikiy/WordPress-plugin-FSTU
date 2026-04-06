<?php
/**
 * Модальне вікно перегляду виду змагань.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\TypeEvent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-typeevent-modal-view" class="fstu-modal-overlay fstu-typeevent-hidden" aria-hidden="true">
	<div class="fstu-modal" role="dialog" aria-modal="true" aria-labelledby="fstu-typeevent-modal-title">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-typeevent-modal-title"><?php esc_html_e( 'Перегляд виду змагань', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal-close-btn" id="fstu-typeevent-modal-view-close" title="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>" aria-label="<?php esc_attr_e( 'Закрити перегляд виду змагань', 'fstu' ); ?>">✕</button>
		</div>
		<div class="fstu-modal__body" id="fstu-typeevent-modal-body"></div>
	</div>
</div>

