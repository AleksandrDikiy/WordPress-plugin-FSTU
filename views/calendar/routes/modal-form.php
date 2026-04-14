<?php
/**
 * Модальне вікно форми ділянки маршруту Calendar_Routes.
 *
 * Version: 1.0.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarRoutes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$datasets = isset( $datasets ) && is_array( $datasets ) ? $datasets : [];
?>

<div id="fstu-calendar-route-form-modal" class="fstu-modal-overlay fstu-hidden">
	<div class="fstu-modal fstu-calendar-modal fstu-calendar-modal--form">
		<form id="fstu-calendar-route-form" method="post">
			<div class="fstu-modal-header">
				<h3 id="fstu-calendar-route-form-title"><?php esc_html_e( 'Додавання ділянки маршруту', 'fstu' ); ?></h3>
				<button type="button" class="fstu-modal-close" data-close-modal="fstu-calendar-route-form-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
			</div>
			<div class="fstu-modal-body">
				<input type="hidden" name="pathtrip_id" id="fstu-calendar-route-pathtrip-id" value="0">
				<input type="hidden" name="calendar_id" id="fstu-calendar-route-calendar-id" value="0">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ?? '' ); ?>">
				<label class="screen-reader-text" for="fstu-calendar-route-honeypot"><?php esc_html_e( 'Website', 'fstu' ); ?></label>
				<input type="text" id="fstu-calendar-route-honeypot" name="fstu_website" class="fstu-hidden-field" tabindex="-1" autocomplete="off">

				<div class="fstu-form-grid fstu-form-grid--two-columns">
					<div class="fstu-form-field">
						<label for="fstu-calendar-route-date"><?php esc_html_e( 'Дата', 'fstu' ); ?></label>
						<input type="date" id="fstu-calendar-route-date" name="pathtrip_date" class="fstu-input" required>
					</div>

					<div class="fstu-form-field">
						<label for="fstu-calendar-route-distance"><?php esc_html_e( 'Відстань, км', 'fstu' ); ?></label>
						<input type="number" id="fstu-calendar-route-distance" name="pathtrip_distance" class="fstu-input" min="1" max="999" required>
					</div>

					<div class="fstu-form-field fstu-form-field--full">
						<label for="fstu-calendar-route-note"><?php esc_html_e( 'Відрізок маршруту / перешкода', 'fstu' ); ?></label>
						<input type="text" id="fstu-calendar-route-note" name="pathtrip_note" class="fstu-input" maxlength="200" required>
					</div>

					<div class="fstu-form-field">
						<label for="fstu-calendar-route-vehicle"><?php esc_html_e( 'Засіб пересування', 'fstu' ); ?></label>
						<select id="fstu-calendar-route-vehicle" name="vehicle_id" class="fstu-select">
							<option value="0"><?php esc_html_e( 'Не обрано', 'fstu' ); ?></option>
							<?php foreach ( (array) ( $datasets['route_vehicles'] ?? [] ) as $item ) : ?>
								<option value="<?php echo esc_attr( (string) (int) ( $item['Vehicle_ID'] ?? 0 ) ); ?>"><?php echo esc_html( (string) ( $item['Vehicle_Name'] ?? '' ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="fstu-form-field">
						<label for="fstu-calendar-route-url-treck"><?php esc_html_e( 'URL треку', 'fstu' ); ?></label>
						<input type="url" id="fstu-calendar-route-url-treck" name="pathtrip_url_treck" class="fstu-input">
					</div>
				</div>
			</div>
			<div class="fstu-modal-footer">
				<button type="submit" class="fstu-btn fstu-btn--save"><?php esc_html_e( 'Зберегти', 'fstu' ); ?></button>
				<button type="button" class="fstu-btn fstu-btn--cancel" data-close-modal="fstu-calendar-route-form-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
			</div>
		</form>
	</div>
</div>
