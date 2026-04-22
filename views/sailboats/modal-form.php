<?php
/**
 * Модальне вікно форми судна / заявки на судновий квиток.
 *
 * Version:     1.8.0
 * Date_update: 2026-04-08
 *
 * @package FSTU\Modules\UserFstu\Sailboats
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-sailboats-form-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--wide fstu-modal--sailboats-form" role="dialog" aria-modal="true" aria-labelledby="fstu-sailboats-form-title">
		<div class="fstu-modal__header">
			<h3 id="fstu-sailboats-form-title" class="fstu-modal__title"><?php esc_html_e( 'Заявка на судновий квиток', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-sailboats-form-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body">
			<div id="fstu-sailboats-form-message" class="fstu-form-message fstu-hidden"></div>

			<form id="fstu-sailboats-form" class="fstu-form-grid fstu-form-grid--compact-form fstu-sailboats-form" novalidate>
				<input type="hidden" id="fstu-sailboats-item-id" name="item_id" value="0">
				<input type="hidden" id="fstu-sailboats-form-mode" name="form_mode" value="new">
				<input type="hidden" id="fstu-sailboats-sailboat-id" name="sailboat_id" value="0">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( \FSTU\Modules\Registry\Sailboats\Sailboats_List::NONCE_ACTION ) ); ?>">
				<div class="fstu-honeypot" aria-hidden="true">
					<label for="fstu-sailboats-form-website">Website</label>
					<input type="text" id="fstu-sailboats-form-website" name="fstu_website" class="fstu-hidden-field" tabindex="-1" autocomplete="off">
				</div>

				<div class="fstu-form-section fstu-sailboats-toolbar-card">
					<div class="fstu-form-grid fstu-form-grid--operation-inline">
						<div class="fstu-form-group">
							<div class="fstu-inline-field">
								<label class="fstu-label fstu-label--required" for="fstu-sailboats-create-mode"><?php esc_html_e( 'Тип дії', 'fstu' ); ?></label>
								<select id="fstu-sailboats-create-mode" name="create_mode" class="fstu-select" required>
									<option value="new"><?php esc_html_e( 'Нове судно', 'fstu' ); ?></option>
									<option value="existing"><?php esc_html_e( 'Заявка для існуючого судна', 'fstu' ); ?></option>
								</select>
							</div>
						</div>

						<div class="fstu-form-group" id="fstu-sailboats-existing-id-group">
							<input type="hidden" id="fstu-sailboats-existing-sailboat-id" name="existing_sailboat_id" value="0">
							<div class="fstu-inline-field fstu-sailboats-existing-search">
								<label class="fstu-label" for="fstu-sailboats-existing-search"><?php esc_html_e( 'Пошук існуючого судна', 'fstu' ); ?></label>
								<div class="fstu-search-input-wrapper">
									<input type="text" class="fstu-input" id="fstu-sailboats-existing-search" placeholder="<?php esc_attr_e( 'Введіть назву або номер', 'fstu' ); ?>" autocomplete="off">
									<button type="button" class="fstu-btn fstu-btn--small fstu-hidden" id="fstu-sailboats-existing-clear-btn"><?php esc_html_e( 'Скинути', 'fstu' ); ?></button>
								</div>
							</div>
							<div id="fstu-sailboats-existing-selected" class="fstu-sailboats-existing-selected fstu-hidden"></div>
							<div id="fstu-sailboats-existing-results" class="fstu-sailboats-existing-results fstu-hidden"></div>
						</div>
					</div>
				</div>

				<div class="fstu-sailboats-form-layout">
					
					<div class="fstu-form-section fstu-form-section--card fstu-sailboats-form-card">
						<h4 class="fstu-form-section__title"><?php esc_html_e( 'Основні дані судна', 'fstu' ); ?></h4>
						<div class="fstu-form-grid fstu-sailboats-form-card__fields">
							<div class="fstu-form-group">
								<label class="fstu-label fstu-label--required" for="fstu-sailboats-name"><?php esc_html_e( 'Найменування судна', 'fstu' ); ?></label>
								<input type="text" class="fstu-input" id="fstu-sailboats-name" name="sailboat_name" maxlength="255" required>
							</div>
							<div class="fstu-form-group">
								<label class="fstu-label" for="fstu-sailboats-name-eng"><?php esc_html_e( 'Найменування (ENG)', 'fstu' ); ?></label>
								<input type="text" class="fstu-input" id="fstu-sailboats-name-eng" name="sailboat_name_eng" maxlength="255">
							</div>
							<div class="fstu-form-group">
								<label class="fstu-label" for="fstu-sailboats-number-sail"><?php esc_html_e( '№ на вітрилі', 'fstu' ); ?></label>
								<input type="text" class="fstu-input" id="fstu-sailboats-number-sail" name="sailboat_number_sail" maxlength="100">
							</div>
							<?php include FSTU_PLUGIN_DIR . 'views/sailboats/partials/owner-fields.php'; ?>
						</div>
					</div>

					<div class="fstu-form-section fstu-form-section--card fstu-sailboats-form-card">
						<h4 class="fstu-form-section__title"><?php esc_html_e( 'Класифікація судна', 'fstu' ); ?></h4>
						<div class="fstu-form-grid fstu-sailboats-form-card__fields">
							<div class="fstu-form-group">
								<label class="fstu-label" for="fstu-sailboats-type-hull-id"><?php esc_html_e( 'Тип корпусу', 'fstu' ); ?></label>
								<select id="fstu-sailboats-type-hull-id" name="type_hull_id" class="fstu-select"><option value="0"><?php esc_html_e( 'Виберіть', 'fstu' ); ?></option></select>
							</div>
							<div class="fstu-form-group">
								<label class="fstu-label" for="fstu-sailboats-type-construction-id"><?php esc_html_e( 'Тип конструкції', 'fstu' ); ?></label>
								<select id="fstu-sailboats-type-construction-id" name="type_construction_id" class="fstu-select"><option value="0"><?php esc_html_e( 'Виберіть', 'fstu' ); ?></option></select>
							</div>
							<div class="fstu-form-group">
								<label class="fstu-label" for="fstu-sailboats-type-ship-id"><?php esc_html_e( 'Тип судна', 'fstu' ); ?></label>
								<select id="fstu-sailboats-type-ship-id" name="type_ship_id" class="fstu-select"><option value="0"><?php esc_html_e( 'Виберіть', 'fstu' ); ?></option></select>
							</div>
							<div class="fstu-form-group">
								<label class="fstu-label" for="fstu-sailboats-hull-material-id"><?php esc_html_e( 'Матеріал корпусу', 'fstu' ); ?></label>
								<select id="fstu-sailboats-hull-material-id" name="hull_material_id" class="fstu-select"><option value="0"><?php esc_html_e( 'Виберіть', 'fstu' ); ?></option></select>
							</div>
							<div class="fstu-form-group">
								<label class="fstu-label" for="fstu-sailboats-hull-color-id"><?php esc_html_e( 'Колір корпусу', 'fstu' ); ?></label>
								<select id="fstu-sailboats-hull-color-id" name="hull_color_id" class="fstu-select"><option value="0"><?php esc_html_e( 'Виберіть', 'fstu' ); ?></option></select>
							</div>
							<div class="fstu-form-group">
								<label class="fstu-label fstu-label--required" for="fstu-sailboats-type-boat-id"><?php esc_html_e( 'Тип човна', 'fstu' ); ?></label>
								<select id="fstu-sailboats-type-boat-id" name="type_boat_id" class="fstu-select" required><option value="0"><?php esc_html_e( 'Виберіть', 'fstu' ); ?></option></select>
							</div>
							<div class="fstu-form-group">
								<label class="fstu-label fstu-label--required" for="fstu-sailboats-producer-id"><?php esc_html_e( 'Виробник', 'fstu' ); ?></label>
								<select id="fstu-sailboats-producer-id" name="producer_id" class="fstu-select" required><option value="0"><?php esc_html_e( 'Виберіть', 'fstu' ); ?></option></select>
							</div>
							<div class="fstu-form-group">
								<label class="fstu-label fstu-label--required" for="fstu-sailboats-year"><?php esc_html_e( 'Рік побудови', 'fstu' ); ?></label>
								<input type="number" class="fstu-input" id="fstu-sailboats-year" name="sailboat_year" min="1900" max="2100" step="1" required>
							</div>
						</div>
					</div>

					<div class="fstu-form-section fstu-form-section--card fstu-sailboats-form-card">
						<h4 class="fstu-form-section__title"><?php esc_html_e( 'Технічні характеристики', 'fstu' ); ?></h4>
						<div class="fstu-form-grid fstu-sailboats-form-card__fields">
							<div class="fstu-form-group"><label class="fstu-label" for="fstu-sailboats-sail-main"><?php esc_html_e( 'Площа основного вітрила', 'fstu' ); ?></label><input type="number" class="fstu-input" id="fstu-sailboats-sail-main" name="sailboat_sail_main" min="0" step="0.01" placeholder="м²"></div>
							<div class="fstu-form-group"><label class="fstu-label" for="fstu-sailboats-hill-length"><?php esc_html_e( 'Довжина корпусу', 'fstu' ); ?></label><input type="number" class="fstu-input" id="fstu-sailboats-hill-length" name="sailboat_hill_length" min="0" step="0.01" placeholder="м"></div>
							<div class="fstu-form-group"><label class="fstu-label" for="fstu-sailboats-width-overall"><?php esc_html_e( 'Ширина габаритна', 'fstu' ); ?></label><input type="number" class="fstu-input" id="fstu-sailboats-width-overall" name="sailboat_width_overall" min="0" step="0.01" placeholder="м"></div>
							<div class="fstu-form-group"><label class="fstu-label" for="fstu-sailboats-load-capacity"><?php esc_html_e( 'Вантажопідйомність', 'fstu' ); ?></label><input type="number" class="fstu-input" id="fstu-sailboats-load-capacity" name="sailboat_load_capacity" min="0" step="0.01" placeholder="кг"></div>
							<div class="fstu-form-group"><label class="fstu-label" for="fstu-sailboats-crew-max"><?php esc_html_e( 'Макс. екіпаж', 'fstu' ); ?></label><input type="number" class="fstu-input" id="fstu-sailboats-crew-max" name="sailboat_crew_max" min="0" step="1" placeholder="осіб"></div>
							<div class="fstu-form-group"><label class="fstu-label" for="fstu-sailboats-clearance"><?php esc_html_e( 'Осадка / кліренс', 'fstu' ); ?></label><input type="number" class="fstu-input" id="fstu-sailboats-clearance" name="sailboat_clearance" min="0" step="0.01" placeholder="м"></div>
							<div class="fstu-form-group"><label class="fstu-label" for="fstu-sailboats-motor-power"><?php esc_html_e( 'Потужність двигуна', 'fstu' ); ?></label><input type="number" class="fstu-input" id="fstu-sailboats-motor-power" name="sailboat_motor_power" min="0" step="0.01" placeholder="к.с."></div>
							<div class="fstu-form-group"><label class="fstu-label" for="fstu-sailboats-motor-number"><?php esc_html_e( 'Номер двигуна', 'fstu' ); ?></label><input type="text" class="fstu-input" id="fstu-sailboats-motor-number" name="sailboat_motor_number" maxlength="100"></div>
						</div>
					</div>
				</div>

				<div class="fstu-sailboats-form-bottom-row fstu-form-section--card">
					<div class="fstu-inline-field">
						<label class="fstu-label fstu-label--required" for="fstu-sailboats-region-id"><?php esc_html_e( 'Область', 'fstu' ); ?></label>
						<select id="fstu-sailboats-region-id" name="region_id" class="fstu-select" required>
							<option value="0"><?php esc_html_e( 'Введіть область', 'fstu' ); ?></option>
						</select>
					</div>
					<div class="fstu-inline-field">
						<label class="fstu-label fstu-label--required" for="fstu-sailboats-city-id"><?php esc_html_e( 'Місто', 'fstu' ); ?></label>
						<select id="fstu-sailboats-city-id" name="city_id" class="fstu-select" required>
							<option value="0"><?php esc_html_e( 'Введіть місто', 'fstu' ); ?></option>
						</select>
					</div>
					<div class="fstu-inline-field">
						<label class="fstu-label" for="fstu-sailboats-np"><?php esc_html_e( 'Нова пошта', 'fstu' ); ?></label>
						<input type="text" class="fstu-input" id="fstu-sailboats-np" name="appshipticket_np" maxlength="255" placeholder="<?php esc_attr_e( 'Введіть відділення або примітку', 'fstu' ); ?>">
					</div>
				</div>

				<div class="fstu-modal__footer fstu-modal__footer--sailboats">
					<button type="submit" class="fstu-btn fstu-btn--primary" id="fstu-sailboats-form-submit"><?php esc_html_e( 'Відправити', 'fstu' ); ?></button>
					<button type="button" class="fstu-btn fstu-btn--secondary" data-close-modal="fstu-sailboats-form-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>