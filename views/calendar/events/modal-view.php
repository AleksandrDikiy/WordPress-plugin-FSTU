<?php
/**
 * Модальне вікно перегляду заходу Calendar_Events.
 *
 * Version: 1.0.1
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarEvents
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-calendar-view-modal" class="fstu-modal-overlay fstu-hidden">
	<div class="fstu-modal fstu-calendar-modal fstu-calendar-modal--view">
		<div class="fstu-modal-header">
			<h3 id="fstu-calendar-view-title"><?php esc_html_e( 'Перегляд заходу', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal-close" data-close-modal="fstu-calendar-view-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal-body">
			<div id="fstu-calendar-event-view-content" class="fstu-calendar-view-grid"></div>
		</div>
		<div class="fstu-modal-footer">
			<button type="button" class="fstu-btn fstu-btn--cancel" data-close-modal="fstu-calendar-view-modal"><?php esc_html_e( 'Закрити', 'fstu' ); ?></button>
		</div>
	</div>
</div>

