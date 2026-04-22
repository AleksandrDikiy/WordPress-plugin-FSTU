<?php
/**
 * Модальне вікно перегляду призначення реєстратора.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-11
 *
 * @package FSTU\Modules\UserFstu\Recorders
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-recorders-view-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--compact" role="dialog" aria-modal="true" aria-labelledby="fstu-recorders-view-title">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-recorders-view-title"><?php esc_html_e( 'Перегляд призначення', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-recorders-view-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body" id="fstu-recorders-view-body">
			<p class="fstu-loader-inline"><?php esc_html_e( 'Завантаження...', 'fstu' ); ?></p>
		</div>
		<div class="fstu-modal__footer" id="fstu-recorders-view-footer"></div>
	</div>
</div>

