<?php
/**
 * Модальне вікно форми заходу Calendar_Events.
 *
 * Version: 1.0.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarEvents
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$datasets = isset( $datasets ) && is_array( $datasets ) ? $datasets : [];
$current_user_id = get_current_user_id();
?>

<div id="fstu-calendar-form-modal" class="fstu-modal-overlay fstu-hidden">
	<div class="fstu-modal fstu-calendar-modal fstu-calendar-modal--form">
		<form id="fstu-calendar-form" method="post">
			<div class="fstu-modal-header">
				<h3 id="fstu-calendar-form-title"><?php esc_html_e( 'Додавання заходу', 'fstu' ); ?></h3>
				<button type="button" class="fstu-modal-close" data-close-modal="fstu-calendar-form-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
			</div>
			<div class="fstu-modal-body">
				<input type="hidden" name="event_id" id="fstu-calendar-event-id" value="0">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ?? '' ); ?>">
				<input type="text" name="fstu_website" class="fstu-hidden-field" tabindex="-1" autocomplete="off">

				<div class="fstu-form-grid fstu-form-grid--two-columns">
					<div class="fstu-form-field fstu-form-field--full">
						<label for="fstu-calendar-name"><?php esc_html_e( 'Найменування', 'fstu' ); ?></label>
						<input type="text" id="fstu-calendar-name" name="name" class="fstu-input" required>
					</div>

					<div class="fstu-form-field">
						<label for="fstu-calendar-date-begin"><?php esc_html_e( 'Дата початку', 'fstu' ); ?></label>
						<input type="date" id="fstu-calendar-date-begin" name="date_begin" class="fstu-input" required>
					</div>

					<div class="fstu-form-field">
						<label for="fstu-calendar-date-end"><?php esc_html_e( 'Дата завершення', 'fstu' ); ?></label>
						<input type="date" id="fstu-calendar-date-end" name="date_end" class="fstu-input" required>
					</div>

					<div class="fstu-form-field">
						<label for="fstu-calendar-status-id"><?php esc_html_e( 'Статус', 'fstu' ); ?></label>
						<select id="fstu-calendar-status-id" name="status_id" class="fstu-select" required>
							<option value="0"><?php esc_html_e( 'Оберіть статус', 'fstu' ); ?></option>
							<?php foreach ( (array) ( $datasets['statuses'] ?? [] ) as $item ) : ?>
								<option value="<?php echo esc_attr( (string) (int) ( $item['CalendarStatus_ID'] ?? 0 ) ); ?>"><?php echo esc_html( (string) ( $item['CalendarStatus_Name'] ?? '' ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="fstu-form-field">
						<label for="fstu-calendar-city-id"><?php esc_html_e( 'Місто', 'fstu' ); ?></label>
						<select id="fstu-calendar-city-id" name="city_id" class="fstu-select" required>
							<option value="0"><?php esc_html_e( 'Оберіть місто', 'fstu' ); ?></option>
							<?php foreach ( (array) ( $datasets['cities'] ?? [] ) as $item ) : ?>
								<option value="<?php echo esc_attr( (string) (int) ( $item['City_ID'] ?? 0 ) ); ?>"><?php echo esc_html( (string) ( $item['City_Name'] ?? '' ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="fstu-form-field">
						<label for="fstu-calendar-tourism-type-id"><?php esc_html_e( 'Вид туризму', 'fstu' ); ?></label>
						<select id="fstu-calendar-tourism-type-id" name="tourism_type_id" class="fstu-select" required>
							<option value="0"><?php esc_html_e( 'Оберіть вид туризму', 'fstu' ); ?></option>
							<?php foreach ( (array) ( $datasets['tourism_types'] ?? [] ) as $item ) : ?>
								<option value="<?php echo esc_attr( (string) (int) ( $item['TourismType_ID'] ?? 0 ) ); ?>"><?php echo esc_html( (string) ( $item['TourismType_Name'] ?? '' ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="fstu-form-field">
						<label for="fstu-calendar-event-type-id"><?php esc_html_e( 'Тип заходу', 'fstu' ); ?></label>
						<select id="fstu-calendar-event-type-id" name="event_type_id" class="fstu-select" required>
							<option value="0"><?php esc_html_e( 'Оберіть тип заходу', 'fstu' ); ?></option>
							<?php foreach ( (array) ( $datasets['event_types'] ?? [] ) as $item ) : ?>
								<option value="<?php echo esc_attr( (string) (int) ( $item['EventType_ID'] ?? 0 ) ); ?>"><?php echo esc_html( (string) ( $item['EventType_Name'] ?? '' ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="fstu-form-field">
						<label for="fstu-calendar-type-event-id"><?php esc_html_e( 'Вид змагань', 'fstu' ); ?></label>
						<select id="fstu-calendar-type-event-id" name="type_event_id" class="fstu-select">
							<option value="0"><?php esc_html_e( 'Не обрано', 'fstu' ); ?></option>
							<?php foreach ( (array) ( $datasets['type_events'] ?? [] ) as $item ) : ?>
								<option value="<?php echo esc_attr( (string) (int) ( $item['TypeEvent_ID'] ?? 0 ) ); ?>"><?php echo esc_html( (string) ( $item['TypeEvent_Name'] ?? '' ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="fstu-form-field">
						<label for="fstu-calendar-tour-type-id"><?php esc_html_e( 'Вид походу', 'fstu' ); ?></label>
						<select id="fstu-calendar-tour-type-id" name="tour_type_id" class="fstu-select">
							<option value="0"><?php esc_html_e( 'Не обрано', 'fstu' ); ?></option>
							<?php foreach ( (array) ( $datasets['tour_types'] ?? [] ) as $item ) : ?>
								<option value="<?php echo esc_attr( (string) (int) ( $item['TourType_ID'] ?? 0 ) ); ?>"><?php echo esc_html( (string) ( $item['TourType_Name'] ?? '' ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="fstu-form-field">
						<label for="fstu-calendar-responsible-user-id"><?php esc_html_e( 'ID відповідального', 'fstu' ); ?></label>
						<input type="number" id="fstu-calendar-responsible-user-id" name="responsible_user_id" class="fstu-input" min="1" value="<?php echo esc_attr( (string) max( 1, $current_user_id ) ); ?>" required>
					</div>

					<div class="fstu-form-field">
						<label for="fstu-calendar-url-reglament"><?php esc_html_e( 'URL регламенту', 'fstu' ); ?></label>
						<input type="url" id="fstu-calendar-url-reglament" name="url_reglament" class="fstu-input">
					</div>

					<div class="fstu-form-field">
						<label for="fstu-calendar-url-protocol"><?php esc_html_e( 'URL протоколу', 'fstu' ); ?></label>
						<input type="url" id="fstu-calendar-url-protocol" name="url_protocol" class="fstu-input">
					</div>

					<div class="fstu-form-field">
						<label for="fstu-calendar-url-map"><?php esc_html_e( 'URL карти', 'fstu' ); ?></label>
						<input type="url" id="fstu-calendar-url-map" name="url_map" class="fstu-input">
					</div>

					<div class="fstu-form-field">
						<label for="fstu-calendar-url-report"><?php esc_html_e( 'URL звіту', 'fstu' ); ?></label>
						<input type="url" id="fstu-calendar-url-report" name="url_report" class="fstu-input">
					</div>
				</div>
			</div>
			<div class="fstu-modal-footer">
				<button type="submit" class="fstu-btn fstu-btn--save"><?php esc_html_e( 'Зберегти', 'fstu' ); ?></button>
				<button type="button" class="fstu-btn fstu-btn--cancel" data-close-modal="fstu-calendar-form-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
			</div>
		</form>
	</div>
</div>

