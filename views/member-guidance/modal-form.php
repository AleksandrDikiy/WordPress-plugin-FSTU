<?php
/**
 * Shared-модалка додавання / редагування / перегляду посади у керівному органі.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\MemberGuidance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$typeguidance_options = isset( $typeguidance_options ) && is_array( $typeguidance_options ) ? $typeguidance_options : [];
?>

<div id="fstu-member-guidance-form-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--compact" role="dialog" aria-modal="true" aria-labelledby="fstu-member-guidance-form-title">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-member-guidance-form-title"><?php esc_html_e( 'Додавання запису', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-member-guidance-form-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body">
			<div id="fstu-member-guidance-form-message" class="fstu-form-message fstu-hidden"></div>

			<form id="fstu-member-guidance-form" class="fstu-app-form" novalidate>
				<input type="hidden" id="fstu-member-guidance-id" name="member_guidance_id" value="0">
				<input type="hidden" id="fstu-member-guidance-mode" value="create">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( \FSTU\Dictionaries\MemberGuidance\Member_Guidance_List::NONCE_ACTION ) ); ?>">
				<div class="fstu-honeypot" aria-hidden="true">
					<label for="fstu-member-guidance-website">Website</label>
					<input type="text" id="fstu-member-guidance-website" name="fstu_website" tabindex="-1" autocomplete="off">
				</div>

				<div class="fstu-form-group">
					<label class="fstu-label fstu-label--required" for="fstu-member-guidance-typeguidance-id"><?php esc_html_e( 'Тип керівного органу', 'fstu' ); ?></label>
					<select class="fstu-select fstu-select--modal" id="fstu-member-guidance-typeguidance-id" name="typeguidance_id" required>
						<option value=""><?php esc_html_e( 'Оберіть тип керівного органу', 'fstu' ); ?></option>
						<?php if ( empty( $typeguidance_options ) ) : ?>
							<option value=""><?php esc_html_e( 'Немає доступних типів', 'fstu' ); ?></option>
						<?php else : ?>
							<?php foreach ( $typeguidance_options as $option ) : ?>
								<?php
								$option_id   = (int) ( $option['id'] ?? 0 );
								$option_name = (string) ( $option['name'] ?? '' );
								?>
								<option value="<?php echo esc_attr( (string) $option_id ); ?>"><?php echo esc_html( $option_name ); ?></option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
				</div>

				<div class="fstu-form-group">
					<label class="fstu-label fstu-label--required" for="fstu-member-guidance-name"><?php esc_html_e( 'Найменування', 'fstu' ); ?></label>
					<input type="text" class="fstu-input" id="fstu-member-guidance-name" name="member_guidance_name" maxlength="255" required>
				</div>

				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-member-guidance-order"><?php esc_html_e( 'Сортування', 'fstu' ); ?></label>
					<input type="number" class="fstu-input" id="fstu-member-guidance-order" name="member_guidance_order" min="0" step="1">
					<div class="fstu-hint"><?php esc_html_e( 'Службове поле. Не відображається в основній таблиці.', 'fstu' ); ?></div>
				</div>

				<div class="fstu-form-actions">
					<button type="submit" class="fstu-btn fstu-btn--primary" id="fstu-member-guidance-form-submit"><?php esc_html_e( 'Зберегти', 'fstu' ); ?></button>
					<button type="button" class="fstu-btn fstu-btn--secondary" data-close-modal="fstu-member-guidance-form-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>

