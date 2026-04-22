<?php
/**
 * Модальне вікно призначення / зняття категорії довідки.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-08
 *
 * @package FSTU\Modules\UserFstu\Referees
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$permissions = isset( $permissions ) && is_array( $permissions ) ? $permissions : [];
$can_manage_certificates = ! empty( $permissions['canManageCertificates'] );
$can_unbind_certificates = ! empty( $permissions['canUnbindCertificates'] );
?>

<div id="fstu-referees-certificate-bind-modal" class="fstu-modal-overlay fstu-hidden" role="dialog" aria-modal="true" aria-labelledby="fstu-referees-certificate-bind-modal-title">
	<div class="fstu-modal fstu-modal--compact">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-referees-certificate-bind-modal-title"><?php esc_html_e( 'Призначення категорії довідці', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-referees-certificate-bind-modal">×</button>
		</div>
		<div class="fstu-modal__body">
			<div id="fstu-referees-certificate-bind-message" class="fstu-form-message fstu-hidden"></div>
			<form id="fstu-referees-certificate-bind-form" class="fstu-form-grid">
				<input type="hidden" name="certificate_id" id="fstu-referees-bind-certificate-id" value="0">
				<div class="fstu-form-group">
					<label class="fstu-label"><?php esc_html_e( 'Довідка', 'fstu' ); ?></label>
					<div class="fstu-view-grid__value" id="fstu-referees-bind-certificate-meta">—</div>
				</div>
				<div class="fstu-form-group">
					<label class="fstu-label fstu-label--required" for="fstu-referees-bind-category-id"><?php esc_html_e( 'Суддівська категорія', 'fstu' ); ?></label>
					<select name="referee_category_id" id="fstu-referees-bind-category-id" class="fstu-select">
						<option value="0"><?php esc_html_e( 'Оберіть категорію', 'fstu' ); ?></option>
					</select>
				</div>
			</form>
		</div>
		<div class="fstu-modal__footer fstu-modal__footer--between">
			<?php if ( $can_unbind_certificates ) : ?>
				<button type="button" class="fstu-btn fstu-btn--danger" id="fstu-referees-unbind-certificate-btn"><?php esc_html_e( 'ЗНЯТИ ПРИВ’ЯЗКУ', 'fstu' ); ?></button>
			<?php else : ?>
				<span></span>
			<?php endif; ?>
			<div class="fstu-modal__footer-actions">
				<button type="button" class="fstu-btn" data-close-modal="fstu-referees-certificate-bind-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
				<?php if ( $can_manage_certificates ) : ?>
					<button type="submit" form="fstu-referees-certificate-bind-form" class="fstu-btn fstu-btn--primary" id="fstu-referees-bind-certificate-submit"><?php esc_html_e( 'ЗБЕРЕГТИ', 'fstu' ); ?></button>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

