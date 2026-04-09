<?php
/**
 * Модальне вікно форми заявки стернового.
 *
 * Version:     1.2.0
 * Date_update: 2026-04-08
 *
 * @package FSTU\Modules\Registry\Steering
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$permissions    = isset( $permissions ) && is_array( $permissions ) ? $permissions : [];
$can_manage     = ! empty( $permissions['canManage'] );
$type_options   = isset( $type_options ) && is_array( $type_options ) ? $type_options : [];
?>

<div id="fstu-steering-form-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--wide" role="dialog" aria-modal="true" aria-labelledby="fstu-steering-form-title">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-steering-form-title"><?php esc_html_e( 'Заявка на посвідчення стернового', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-steering-form-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body">
			<div id="fstu-steering-form-message" class="fstu-form-message fstu-hidden"></div>
			<form id="fstu-steering-form" class="fstu-form-grid fstu-form-grid--two-cols" novalidate>
				<input type="hidden" id="fstu-steering-form-mode" name="form_mode" value="create">
				<input type="hidden" id="fstu-steering-form-steering-id" name="steering_id" value="0">
				<input type="hidden" id="fstu-steering-form-user-id" name="user_id" value="0">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( \FSTU\Modules\Registry\Steering\Steering_List::NONCE_ACTION ) ); ?>">
				<div class="fstu-honeypot" aria-hidden="true">
					<label for="fstu-steering-form-website">Website</label>
					<input type="text" id="fstu-steering-form-website" name="fstu_website" class="fstu-hidden-field" tabindex="-1" autocomplete="off">
				</div>

				<?php if ( $can_manage ) : ?>
					<div class="fstu-form-group fstu-form-group--full-width" id="fstu-steering-user-group">
						<label class="fstu-label fstu-label--required" for="fstu-steering-form-user-select"><?php esc_html_e( 'Користувач', 'fstu' ); ?></label>
						<select id="fstu-steering-form-user-select" class="fstu-select" name="user_select" required>
							<option value="0"><?php esc_html_e( 'Оберіть користувача', 'fstu' ); ?></option>
						</select>
					</div>
				<?php endif; ?>

				<div class="fstu-form-group">
					<label class="fstu-label fstu-label--required" for="fstu-steering-form-surname-ukr"><?php esc_html_e( 'Прізвище (укр)', 'fstu' ); ?></label>
					<input type="text" class="fstu-input" id="fstu-steering-form-surname-ukr" name="surname_ukr" maxlength="100" required>
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label fstu-label--required" for="fstu-steering-form-name-ukr"><?php esc_html_e( 'Ім’я (укр)', 'fstu' ); ?></label>
					<input type="text" class="fstu-input" id="fstu-steering-form-name-ukr" name="name_ukr" maxlength="100" required>
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-steering-form-patronymic-ukr"><?php esc_html_e( 'По батькові (укр)', 'fstu' ); ?></label>
					<input type="text" class="fstu-input" id="fstu-steering-form-patronymic-ukr" name="patronymic_ukr" maxlength="100">
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-steering-form-birth-date"><?php esc_html_e( 'Дата народження', 'fstu' ); ?></label>
					<input type="date" class="fstu-input" id="fstu-steering-form-birth-date" name="birth_date">
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-steering-form-surname-eng"><?php esc_html_e( 'Прізвище (ENG)', 'fstu' ); ?></label>
					<input type="text" class="fstu-input" id="fstu-steering-form-surname-eng" name="surname_eng" maxlength="100">
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-steering-form-name-eng"><?php esc_html_e( 'Ім’я (ENG)', 'fstu' ); ?></label>
					<input type="text" class="fstu-input" id="fstu-steering-form-name-eng" name="name_eng" maxlength="100">
				</div>
				<div class="fstu-form-group fstu-form-group--full-width">
					<label class="fstu-label fstu-label--required" for="fstu-steering-form-type-app"><?php esc_html_e( 'Підстава для посвідчення', 'fstu' ); ?></label>
					<select class="fstu-select" id="fstu-steering-form-type-app" name="type_app" required>
						<option value="0"><?php esc_html_e( 'Оберіть підставу', 'fstu' ); ?></option>
						<?php foreach ( $type_options as $type_option ) : ?>
							<option value="<?php echo esc_attr( (string) ( $type_option['value'] ?? 0 ) ); ?>"><?php echo esc_html( (string) ( $type_option['label'] ?? '' ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label fstu-label--required" for="fstu-steering-form-city-id"><?php esc_html_e( 'Місто Нової пошти', 'fstu' ); ?></label>
					<select class="fstu-select" id="fstu-steering-form-city-id" name="city_id" required>
						<option value="0"><?php esc_html_e( 'Оберіть місто', 'fstu' ); ?></option>
					</select>
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label fstu-label--required" for="fstu-steering-form-number-np"><?php esc_html_e( '№ відділення НП', 'fstu' ); ?></label>
					<input type="text" class="fstu-input" id="fstu-steering-form-number-np" name="number_np" maxlength="100" required>
				</div>
				<div class="fstu-form-group fstu-form-group--full-width">
					<label class="fstu-label" for="fstu-steering-form-url"><?php esc_html_e( 'Посилання на підтверджуючий документ', 'fstu' ); ?></label>
					<input type="url" class="fstu-input" id="fstu-steering-form-url" name="url" maxlength="255" placeholder="https://...">
					<div class="fstu-form-help"><?php esc_html_e( 'Необов’язкове поле. Вкажіть пряме посилання на документ або матеріал, що підтверджує кваліфікацію.', 'fstu' ); ?></div>
				</div>
				<div class="fstu-form-group fstu-form-group--full-width">
					<label class="fstu-label" id="fstu-steering-form-photo-label" for="fstu-steering-form-photo"><?php esc_html_e( 'Фото', 'fstu' ); ?></label>
					<input type="file" class="fstu-input" id="fstu-steering-form-photo" name="photo" accept="image/jpeg,image/png,image/webp" required>
					<div class="fstu-form-help" id="fstu-steering-form-photo-help"><?php esc_html_e( 'Підтримуються JPG, PNG, WEBP. Після збереження файл буде конвертований і збережений у legacy-шлях `/photo_steering/{User_ID}.jpg`.', 'fstu' ); ?></div>
				</div>
				<div class="fstu-form-actions fstu-form-group--full-width">
					<button type="submit" class="fstu-btn fstu-btn--primary" id="fstu-steering-form-submit"><?php esc_html_e( 'Відправити', 'fstu' ); ?></button>
					<button type="button" class="fstu-btn fstu-btn--secondary" data-close-modal="fstu-steering-form-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>

