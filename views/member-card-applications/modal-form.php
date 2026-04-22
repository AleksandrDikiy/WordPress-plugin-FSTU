<?php
/**
 * Модальне вікно форми модуля «Посвідчення членів ФСТУ».
 *
 * Version:     1.4.0
 * Date_update: 2026-04-10
 *
 * @package FSTU\Modules\UserFstu\MemberCardApplications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$permissions      = isset( $permissions ) && is_array( $permissions ) ? $permissions : [];
$can_manage_card_number = ! empty( $permissions['canManageCardNumber'] );
$filter_regions   = isset( $filter_regions ) && is_array( $filter_regions ) ? $filter_regions : [];
$filter_statuses  = isset( $filter_statuses ) && is_array( $filter_statuses ) ? $filter_statuses : [];
$filter_types     = isset( $filter_types ) && is_array( $filter_types ) ? $filter_types : [];
?>

<div id="fstu-member-card-applications-form-modal" class="fstu-modal fstu-hidden" aria-hidden="true">
	<div class="fstu-modal__overlay" data-modal-close="form"></div>
	<div class="fstu-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="fstu-member-card-applications-form-title">
		<button type="button" class="fstu-modal__close" data-modal-close="form" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		<h3 id="fstu-member-card-applications-form-title" class="fstu-modal__title"><?php esc_html_e( 'Посвідчення члена ФСТУ', 'fstu' ); ?></h3>
		<div class="fstu-modal__body">
			<form id="fstu-member-card-applications-form" class="fstu-form" novalidate enctype="multipart/form-data">
				<div id="fstu-member-card-applications-form-note" class="fstu-form__note fstu-hidden" aria-live="polite"></div>
				<div id="fstu-member-card-applications-photo-panel" class="fstu-member-card-applications-photo-panel fstu-hidden">
					<div class="fstu-member-card-applications-photo-panel__preview">
						<img id="fstu-member-card-applications-photo-preview" class="fstu-member-card-applications-photo-panel__image" src="" alt="<?php esc_attr_e( 'Фото посвідчення', 'fstu' ); ?>">
					</div>
					<div class="fstu-member-card-applications-photo-panel__meta">
						<div id="fstu-member-card-applications-photo-status" class="fstu-member-card-applications-photo-panel__status"></div>
						<a id="fstu-member-card-applications-photo-link" class="fstu-link-button fstu-member-card-applications-photo-panel__link fstu-hidden" href="#" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Відкрити фото', 'fstu' ); ?></a>
					</div>
				</div>
				<input type="hidden" name="fstu_website" value="">
				<input type="hidden" name="mode" id="fstu-member-card-applications-mode" value="create">
				<input type="hidden" name="member_card_id" id="fstu-member-card-applications-member-card-id" value="0">
				<input type="hidden" name="user_id" id="fstu-member-card-applications-user-id" value="0">
				<div class="fstu-form__grid">
					<?php if ( ! empty( $permissions['canManage'] ) ) : ?>
						<div class="fstu-form__field">
							<label class="fstu-label" for="fstu-member-card-applications-user-id-manual"><?php esc_html_e( 'User ID', 'fstu' ); ?></label>
							<input type="number" min="1" id="fstu-member-card-applications-user-id-manual" class="fstu-input" placeholder="<?php esc_attr_e( 'Вкажіть ID користувача або відкрийте форму з реєстру', 'fstu' ); ?>">
						</div>
					<?php endif; ?>
					<div class="fstu-form__field">
						<label class="fstu-label" for="fstu-member-card-applications-last-name"><?php esc_html_e( 'Прізвище', 'fstu' ); ?></label>
						<input type="text" id="fstu-member-card-applications-last-name" name="last_name" class="fstu-input" required>
					</div>
					<div class="fstu-form__field">
						<label class="fstu-label" for="fstu-member-card-applications-first-name"><?php esc_html_e( 'Ім’я', 'fstu' ); ?></label>
						<input type="text" id="fstu-member-card-applications-first-name" name="first_name" class="fstu-input" required>
					</div>
					<div class="fstu-form__field">
						<label class="fstu-label" for="fstu-member-card-applications-patronymic"><?php esc_html_e( 'По батькові', 'fstu' ); ?></label>
						<input type="text" id="fstu-member-card-applications-patronymic" name="patronymic" class="fstu-input" required>
					</div>
					<div class="fstu-form__field">
						<label class="fstu-label" for="fstu-member-card-applications-last-name-eng"><?php esc_html_e( 'Прізвище (ENG)', 'fstu' ); ?></label>
						<input type="text" id="fstu-member-card-applications-last-name-eng" name="last_name_eng" class="fstu-input">
					</div>
					<div class="fstu-form__field">
						<label class="fstu-label" for="fstu-member-card-applications-first-name-eng"><?php esc_html_e( 'Ім’я (ENG)', 'fstu' ); ?></label>
						<input type="text" id="fstu-member-card-applications-first-name-eng" name="first_name_eng" class="fstu-input">
					</div>
					<div class="fstu-form__field">
						<label class="fstu-label" for="fstu-member-card-applications-birth-date"><?php esc_html_e( 'Дата народження', 'fstu' ); ?></label>
						<input type="date" id="fstu-member-card-applications-birth-date" name="birth_date" class="fstu-input">
					</div>
					<div class="fstu-form__field">
						<label class="fstu-label" for="fstu-member-card-applications-user-email"><?php esc_html_e( 'Email', 'fstu' ); ?></label>
						<input type="email" id="fstu-member-card-applications-user-email" name="user_email" class="fstu-input">
					</div>
					<div class="fstu-form__field">
						<label class="fstu-label" for="fstu-member-card-applications-phone-mobile"><?php esc_html_e( 'Мобільний', 'fstu' ); ?></label>
						<input type="text" id="fstu-member-card-applications-phone-mobile" name="phone_mobile" class="fstu-input">
					</div>
					<div class="fstu-form__field">
						<label class="fstu-label" for="fstu-member-card-applications-phone-2"><?php esc_html_e( 'Додатковий телефон', 'fstu' ); ?></label>
						<input type="text" id="fstu-member-card-applications-phone-2" name="phone_2" class="fstu-input">
					</div>
					<div class="fstu-form__field">
						<label class="fstu-label" for="fstu-member-card-applications-region-id"><?php esc_html_e( 'Регіон', 'fstu' ); ?></label>
						<select id="fstu-member-card-applications-region-id" name="region_id" class="fstu-select" required>
							<option value="0"><?php esc_html_e( 'оберіть регіон', 'fstu' ); ?></option>
							<?php foreach ( $filter_regions as $region ) : ?>
								<option value="<?php echo esc_attr( (string) ( $region['Region_ID'] ?? 0 ) ); ?>"><?php echo esc_html( (string) ( $region['Region_Name'] ?? '' ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="fstu-form__field">
						<label class="fstu-label" for="fstu-member-card-applications-status-card-id"><?php esc_html_e( 'Статус', 'fstu' ); ?></label>
						<select id="fstu-member-card-applications-status-card-id" name="status_card_id" class="fstu-select" required>
							<option value="0"><?php esc_html_e( 'оберіть статус', 'fstu' ); ?></option>
							<?php foreach ( $filter_statuses as $status ) : ?>
								<option value="<?php echo esc_attr( (string) ( $status['StatusCard_ID'] ?? 0 ) ); ?>"><?php echo esc_html( (string) ( $status['StatusCard_Name'] ?? '' ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="fstu-form__field">
						<label class="fstu-label" for="fstu-member-card-applications-type-card-id"><?php esc_html_e( 'Тип посвідчення', 'fstu' ); ?></label>
						<select id="fstu-member-card-applications-type-card-id" name="type_card_id" class="fstu-select" required>
							<option value="0"><?php esc_html_e( 'оберіть тип', 'fstu' ); ?></option>
							<?php foreach ( $filter_types as $type ) : ?>
								<option value="<?php echo esc_attr( (string) ( $type['TypeCard_ID'] ?? 0 ) ); ?>"><?php echo esc_html( (string) ( $type['TypeCard_Name'] ?? '' ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="fstu-form__field">
						<label class="fstu-label" for="fstu-member-card-applications-card-number"><?php esc_html_e( 'Номер картки', 'fstu' ); ?></label>
						<input type="number" id="fstu-member-card-applications-card-number" name="card_number" class="fstu-input" <?php echo $can_manage_card_number ? '' : 'readonly'; ?>>
					</div>
					<div class="fstu-form__field">
						<label class="fstu-label" for="fstu-member-card-applications-summa"><?php esc_html_e( 'Сума', 'fstu' ); ?></label>
						<input type="number" step="0.01" id="fstu-member-card-applications-summa" name="summa" class="fstu-input">
					</div>
					<div class="fstu-form__field">
						<label class="fstu-label" for="fstu-member-card-applications-number-np"><?php esc_html_e( 'Номер НП / примітка', 'fstu' ); ?></label>
						<input type="text" id="fstu-member-card-applications-number-np" name="number_np" class="fstu-input">
					</div>
					<div class="fstu-form__field">
						<label class="fstu-label" for="fstu-member-card-applications-photo-file"><?php esc_html_e( 'Фото посвідчення', 'fstu' ); ?></label>
						<input type="file" id="fstu-member-card-applications-photo-file" name="photo_file" class="fstu-input" accept="image/jpeg,image/png,image/webp">
					</div>
					</div>
				<div class="fstu-form__actions">
					<button type="submit" class="fstu-btn fstu-btn--primary" id="fstu-member-card-applications-submit-btn"><?php esc_html_e( 'ЗБЕРЕГТИ', 'fstu' ); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>

