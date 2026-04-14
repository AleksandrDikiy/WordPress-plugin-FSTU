<?php
/**
 * Модальне вікно форми заявки Calendar_Applications.
 *
 * Version: 1.1.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarApplications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$datasets = isset( $datasets ) && is_array( $datasets ) ? $datasets : [];
?>

<div id="fstu-calendar-application-form-modal" class="fstu-modal-overlay fstu-hidden">
	<div class="fstu-modal fstu-calendar-modal fstu-calendar-modal--form">
		<form id="fstu-calendar-application-form" method="post">
			<div class="fstu-modal-header">
				<h3 id="fstu-calendar-application-form-title"><?php esc_html_e( 'Додавання заявки', 'fstu' ); ?></h3>
				<button type="button" class="fstu-modal-close" data-close-modal="fstu-calendar-application-form-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
			</div>
			<div class="fstu-modal-body">
				<input type="hidden" name="application_id" id="fstu-calendar-application-id" value="0">
				<input type="hidden" name="calendar_id" id="fstu-calendar-application-calendar-id" value="0">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ?? '' ); ?>">
				<label class="screen-reader-text" for="fstu-calendar-application-honeypot"><?php esc_html_e( 'Website', 'fstu' ); ?></label>
				<input type="text" id="fstu-calendar-application-honeypot" name="fstu_website" class="fstu-hidden-field" tabindex="-1" autocomplete="off">

				<div class="fstu-form-grid fstu-form-grid--two-columns">
					<div class="fstu-form-field fstu-form-field--full">
						<label for="fstu-calendar-application-name"><?php esc_html_e( 'Назва команди / заявки', 'fstu' ); ?></label>
						<input type="text" id="fstu-calendar-application-name" name="app_name" class="fstu-input" maxlength="30">
					</div>

					<div class="fstu-form-field">
						<label for="fstu-calendar-application-number"><?php esc_html_e( 'Номер команди', 'fstu' ); ?></label>
						<input type="text" id="fstu-calendar-application-number" name="app_number" class="fstu-input" maxlength="10">
					</div>

					<div class="fstu-form-field">
						<label for="fstu-calendar-application-phone"><?php esc_html_e( 'Контактний телефон', 'fstu' ); ?></label>
						<input type="text" id="fstu-calendar-application-phone" name="app_phone" class="fstu-input">
					</div>

					<div class="fstu-form-field fstu-form-field--full">
						<p class="fstu-subpanel-context"><?php esc_html_e( 'Статус заявки визначається автоматично при створенні та змінюється окремо через меню дій.', 'fstu' ); ?></p>
					</div>

					<div class="fstu-form-field">
						<label for="fstu-calendar-application-region"><?php esc_html_e( 'Область', 'fstu' ); ?></label>
						<select id="fstu-calendar-application-region" name="region_id" class="fstu-select">
							<option value="0"><?php esc_html_e( 'Не обрано', 'fstu' ); ?></option>
							<?php foreach ( (array) ( $datasets['regions'] ?? [] ) as $item ) : ?>
								<option value="<?php echo esc_attr( (string) (int) ( $item['Region_ID'] ?? 0 ) ); ?>"><?php echo esc_html( (string) ( $item['Region_Name'] ?? '' ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="fstu-form-field">
						<label for="fstu-calendar-application-sailboat-id"><?php esc_html_e( 'ID судна', 'fstu' ); ?></label>
						<input type="number" id="fstu-calendar-application-sailboat-id" name="sailboat_id" class="fstu-input" min="0" value="0">
					</div>

					<div class="fstu-form-field">
						<label for="fstu-calendar-application-mr-id"><?php esc_html_e( 'ID мірилки', 'fstu' ); ?></label>
						<input type="number" id="fstu-calendar-application-mr-id" name="mr_id" class="fstu-input" min="0" value="0">
					</div>

					<div class="fstu-form-field">
						<label for="fstu-calendar-application-sail-group-id"><?php esc_html_e( 'ID залікової групи', 'fstu' ); ?></label>
						<input type="number" id="fstu-calendar-application-sail-group-id" name="sail_group_id" class="fstu-input" min="0" value="0">
					</div>
				</div>
			</div>
			<div class="fstu-modal-footer">
				<button type="submit" class="fstu-btn fstu-btn--save"><?php esc_html_e( 'Зберегти', 'fstu' ); ?></button>
				<button type="button" class="fstu-btn fstu-btn--cancel" data-close-modal="fstu-calendar-application-form-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
			</div>
		</form>
	</div>
</div>

