<?php
/**
 * Модальне вікно форми перегону Calendar_Results.
 *
 * Version: 1.0.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarResults
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$datasets = isset( $datasets ) && is_array( $datasets ) ? $datasets : [];
?>

<div id="fstu-calendar-race-form-modal" class="fstu-modal-overlay fstu-hidden">
	<div class="fstu-modal fstu-calendar-modal fstu-calendar-modal--form">
		<form id="fstu-calendar-race-form" method="post">
			<div class="fstu-modal-header">
				<h3 id="fstu-calendar-race-form-title"><?php esc_html_e( 'Додавання перегону', 'fstu' ); ?></h3>
				<button type="button" class="fstu-modal-close" data-close-modal="fstu-calendar-race-form-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
			</div>
			<div class="fstu-modal-body">
				<input type="hidden" name="race_id" id="fstu-calendar-race-id" value="0">
				<input type="hidden" name="calendar_id" id="fstu-calendar-race-calendar-id" value="0">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ?? '' ); ?>">
				<label class="screen-reader-text" for="fstu-calendar-race-honeypot"><?php esc_html_e( 'Website', 'fstu' ); ?></label>
				<input type="text" id="fstu-calendar-race-honeypot" name="fstu_website" class="fstu-hidden-field" tabindex="-1" autocomplete="off">

				<div class="fstu-form-grid fstu-form-grid--two-columns">
					<div class="fstu-form-field">
						<label for="fstu-calendar-race-date"><?php esc_html_e( 'Дата перегону', 'fstu' ); ?></label>
						<input type="date" id="fstu-calendar-race-date" name="race_date" class="fstu-input" required>
					</div>

					<div class="fstu-form-field">
						<label for="fstu-calendar-race-number"><?php esc_html_e( 'Номер перегону', 'fstu' ); ?></label>
						<input type="number" id="fstu-calendar-race-number" name="race_number" class="fstu-input" min="0" max="999">
					</div>

					<div class="fstu-form-field fstu-form-field--full">
						<label for="fstu-calendar-race-name"><?php esc_html_e( 'Найменування', 'fstu' ); ?></label>
						<input type="text" id="fstu-calendar-race-name" name="race_name" class="fstu-input" maxlength="150">
					</div>

					<div class="fstu-form-field">
						<label for="fstu-calendar-race-type-id"><?php esc_html_e( 'Тип перегону', 'fstu' ); ?></label>
						<select id="fstu-calendar-race-type-id" name="race_type_id" class="fstu-select">
							<option value="0"><?php esc_html_e( 'Не обрано', 'fstu' ); ?></option>
							<?php foreach ( (array) ( $datasets['race_types'] ?? [] ) as $item ) : ?>
								<option value="<?php echo esc_attr( (string) (int) ( $item['RaceType_ID'] ?? 0 ) ); ?>"><?php echo esc_html( (string) ( $item['RaceType_Name'] ?? '' ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="fstu-form-field">
						<label for="fstu-calendar-race-description"><?php esc_html_e( 'Опис / примітка', 'fstu' ); ?></label>
						<textarea id="fstu-calendar-race-description" name="race_description" class="fstu-input" rows="3" maxlength="500"></textarea>
					</div>
				</div>
			</div>
			<div class="fstu-modal-footer">
				<button type="submit" class="fstu-btn fstu-btn--save"><?php esc_html_e( 'Зберегти', 'fstu' ); ?></button>
				<button type="button" class="fstu-btn fstu-btn--cancel" data-close-modal="fstu-calendar-race-form-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
			</div>
		</form>
	</div>
</div>

