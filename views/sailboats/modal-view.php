<?php
/**
 * Модальне вікно перегляду судна.
 *
 * Version:     1.0.1
 * Date_update: 2026-04-07
 *
 * @package FSTU\Modules\Registry\Sailboats
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-sailboats-view-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--wide" role="dialog" aria-modal="true" aria-labelledby="fstu-sailboats-view-title">
		<div class="fstu-modal__header">
			<h3 id="fstu-sailboats-view-title" class="fstu-modal__title"><?php esc_html_e( 'Перегляд судна', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-sailboats-view-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body">
			<div id="fstu-sailboats-view-content" class="fstu-placeholder-box">
				<?php esc_html_e( 'Завантаження...', 'fstu' ); ?>
			</div>
		</div>
	</div>
</div>
