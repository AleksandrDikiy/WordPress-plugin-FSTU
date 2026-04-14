<?php
/**
 * Модальне вікно перегляду заявки Calendar_Applications.
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

<div id="fstu-calendar-application-view-modal" class="fstu-modal-overlay fstu-hidden">
	<div class="fstu-modal fstu-calendar-modal fstu-calendar-modal--view">
		<div class="fstu-modal-header">
			<h3><?php esc_html_e( 'Перегляд заявки', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal-close" data-close-modal="fstu-calendar-application-view-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal-body">
			<div id="fstu-calendar-application-view-content" class="fstu-calendar-view-grid"></div>
			<h4><?php esc_html_e( 'Учасники заявки', 'fstu' ); ?></h4>
			<div class="fstu-form-grid fstu-form-grid--two-columns fstu-calendar-participants-manage">
				<form id="fstu-calendar-application-add-participant-form" class="fstu-calendar-inline-form" method="post">
					<input type="hidden" name="application_id" id="fstu-calendar-participant-application-id" value="0">
					<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ?? '' ); ?>">
					<label class="screen-reader-text" for="fstu-calendar-participant-add-honeypot"><?php esc_html_e( 'Website', 'fstu' ); ?></label>
					<input type="text" id="fstu-calendar-participant-add-honeypot" name="fstu_website" class="fstu-hidden-field" tabindex="-1" autocomplete="off">
					<h5><?php esc_html_e( 'Додати існуючого учасника', 'fstu' ); ?></h5>
					<div class="fstu-form-field">
						<label for="fstu-calendar-existing-participant-user-id"><?php esc_html_e( 'ID користувача', 'fstu' ); ?></label>
						<input type="number" id="fstu-calendar-existing-participant-user-id" name="user_id" class="fstu-input" min="1" value="0">
					</div>
					<div class="fstu-form-field">
						<label for="fstu-calendar-existing-participant-type"><?php esc_html_e( 'Тип участі', 'fstu' ); ?></label>
						<select id="fstu-calendar-existing-participant-type" name="participation_type_id" class="fstu-select">
							<option value="0"><?php esc_html_e( 'Оберіть тип участі', 'fstu' ); ?></option>
							<?php foreach ( (array) ( $datasets['participation_types'] ?? [] ) as $item ) : ?>
								<option value="<?php echo esc_attr( (string) (int) ( $item['ParticipationType_ID'] ?? 0 ) ); ?>"><?php echo esc_html( (string) ( $item['ParticipationType_Name'] ?? '' ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="fstu-form-field fstu-form-field--full">
						<button type="submit" class="fstu-btn fstu-btn--secondary"><?php esc_html_e( 'Додати до заявки', 'fstu' ); ?></button>
					</div>
				</form>

				<form id="fstu-calendar-create-participant-form" class="fstu-calendar-inline-form" method="post">
					<input type="hidden" name="application_id" id="fstu-calendar-create-participant-application-id" value="0">
					<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ?? '' ); ?>">
					<label class="screen-reader-text" for="fstu-calendar-participant-create-honeypot"><?php esc_html_e( 'Website', 'fstu' ); ?></label>
					<input type="text" id="fstu-calendar-participant-create-honeypot" name="fstu_website" class="fstu-hidden-field" tabindex="-1" autocomplete="off">
					<h5><?php esc_html_e( 'Створити нового учасника', 'fstu' ); ?></h5>
					<div class="fstu-form-field">
						<label for="fstu-calendar-participant-last-name"><?php esc_html_e( 'Прізвище', 'fstu' ); ?></label>
						<input type="text" id="fstu-calendar-participant-last-name" name="last_name" class="fstu-input" maxlength="100">
					</div>
					<div class="fstu-form-field">
						<label for="fstu-calendar-participant-first-name"><?php esc_html_e( 'Ім’я', 'fstu' ); ?></label>
						<input type="text" id="fstu-calendar-participant-first-name" name="first_name" class="fstu-input" maxlength="100">
					</div>
					<div class="fstu-form-field">
						<label for="fstu-calendar-participant-patronymic"><?php esc_html_e( 'По батькові', 'fstu' ); ?></label>
						<input type="text" id="fstu-calendar-participant-patronymic" name="patronymic" class="fstu-input" maxlength="100">
					</div>
					<div class="fstu-form-field">
						<label for="fstu-calendar-participant-email"><?php esc_html_e( 'Email', 'fstu' ); ?></label>
						<input type="email" id="fstu-calendar-participant-email" name="email" class="fstu-input" maxlength="120">
					</div>
					<div class="fstu-form-field">
						<label for="fstu-calendar-participant-phone"><?php esc_html_e( 'Телефон', 'fstu' ); ?></label>
						<input type="text" id="fstu-calendar-participant-phone" name="phone" class="fstu-input" maxlength="20">
					</div>
					<div class="fstu-form-field">
						<label for="fstu-calendar-participant-birth-date"><?php esc_html_e( 'Дата народження', 'fstu' ); ?></label>
						<input type="date" id="fstu-calendar-participant-birth-date" name="birth_date" class="fstu-input">
					</div>
					<div class="fstu-form-field">
						<label for="fstu-calendar-participant-sex"><?php esc_html_e( 'Стать', 'fstu' ); ?></label>
						<select id="fstu-calendar-participant-sex" name="sex" class="fstu-select">
							<option value=""><?php esc_html_e( 'Не обрано', 'fstu' ); ?></option>
							<option value="Ч"><?php esc_html_e( 'Ч', 'fstu' ); ?></option>
							<option value="Ж"><?php esc_html_e( 'Ж', 'fstu' ); ?></option>
						</select>
					</div>
					<div class="fstu-form-field">
						<label for="fstu-calendar-participant-city-id"><?php esc_html_e( 'Місто', 'fstu' ); ?></label>
						<select id="fstu-calendar-participant-city-id" name="city_id" class="fstu-select">
							<option value="0"><?php esc_html_e( 'Не обрано', 'fstu' ); ?></option>
							<?php foreach ( (array) ( $datasets['cities'] ?? [] ) as $item ) : ?>
								<option value="<?php echo esc_attr( (string) (int) ( $item['City_ID'] ?? 0 ) ); ?>"><?php echo esc_html( (string) ( $item['City_Name'] ?? '' ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="fstu-form-field">
						<label for="fstu-calendar-participant-unit-id"><?php esc_html_e( 'ID осередку (необов’язково)', 'fstu' ); ?></label>
						<input type="number" id="fstu-calendar-participant-unit-id" name="unit_id" class="fstu-input" min="0" value="0">
					</div>
					<div class="fstu-form-field">
						<label for="fstu-calendar-new-participant-type"><?php esc_html_e( 'Тип участі', 'fstu' ); ?></label>
						<select id="fstu-calendar-new-participant-type" name="participation_type_id" class="fstu-select">
							<option value="0"><?php esc_html_e( 'Оберіть тип участі', 'fstu' ); ?></option>
							<?php foreach ( (array) ( $datasets['participation_types'] ?? [] ) as $item ) : ?>
								<option value="<?php echo esc_attr( (string) (int) ( $item['ParticipationType_ID'] ?? 0 ) ); ?>"><?php echo esc_html( (string) ( $item['ParticipationType_Name'] ?? '' ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="fstu-form-field fstu-form-field--full">
						<button type="submit" class="fstu-btn fstu-btn--secondary"><?php esc_html_e( 'Створити і додати', 'fstu' ); ?></button>
					</div>
				</form>
			</div>
			<div class="fstu-table-wrap fstu-table-wrap--compact">
				<table class="fstu-table fstu-table--compact">
					<thead class="fstu-thead">
						<tr>
							<th class="fstu-th"><?php esc_html_e( 'ПІБ', 'fstu' ); ?></th>
							<th class="fstu-th"><?php esc_html_e( 'Тип участі', 'fstu' ); ?></th>
							<th class="fstu-th"><?php esc_html_e( 'Місто', 'fstu' ); ?></th>
							<th class="fstu-th"><?php esc_html_e( 'Стать', 'fstu' ); ?></th>
							<th class="fstu-th"><?php esc_html_e( 'Дата народження', 'fstu' ); ?></th>
							<th class="fstu-th fstu-th--actions"><?php esc_html_e( 'Дії', 'fstu' ); ?></th>
						</tr>
					</thead>
					<tbody id="fstu-calendar-application-participants-tbody" class="fstu-tbody">
						<tr>
							<td colspan="6" class="fstu-no-results"><?php esc_html_e( 'Завантаження...', 'fstu' ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<div class="fstu-modal-footer">
			<button type="button" class="fstu-btn fstu-btn--cancel" data-close-modal="fstu-calendar-application-view-modal"><?php esc_html_e( 'Закрити', 'fstu' ); ?></button>
		</div>
	</div>
</div>

