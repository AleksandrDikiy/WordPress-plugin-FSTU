<?php
/**
 * Модальна форма додавання / редагування посади федерації.
 * Використовується також для режиму перегляду без окремого modal-view.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\MemberRegional
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-member-regional-form-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--compact" role="dialog" aria-modal="true" aria-labelledby="fstu-member-regional-form-title">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-member-regional-form-title"><?php esc_html_e( 'Додавання запису', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-member-regional-form-modal" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		</div>
		<div class="fstu-modal__body">
			<div id="fstu-member-regional-form-message" class="fstu-form-message fstu-hidden"></div>

			<form id="fstu-member-regional-form" class="fstu-app-form" novalidate>
				<input type="hidden" id="fstu-member-regional-id" name="member_regional_id" value="0">
				<input type="hidden" id="fstu-member-regional-mode" value="create">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( \FSTU\Dictionaries\MemberRegional\Member_Regional_List::NONCE_ACTION ) ); ?>">
				<div class="fstu-honeypot" aria-hidden="true">
					<label for="fstu-member-regional-website">Website</label>
					<input type="text" id="fstu-member-regional-website" name="fstu_website" tabindex="-1" autocomplete="off">
				</div>

				<div class="fstu-form-group">
					<label class="fstu-label fstu-label--required" for="fstu-member-regional-name"><?php esc_html_e( 'Найменування', 'fstu' ); ?></label>
					<input type="text" class="fstu-input" id="fstu-member-regional-name" name="member_regional_name" maxlength="255" required>
				</div>

				<div class="fstu-form-group">
					<label class="fstu-label" for="fstu-member-regional-order"><?php esc_html_e( 'Сортування', 'fstu' ); ?></label>
					<input type="number" class="fstu-input" id="fstu-member-regional-order" name="member_regional_order" min="0" step="1">
					<div class="fstu-hint"><?php esc_html_e( 'Службове поле. Не відображається в основній таблиці.', 'fstu' ); ?></div>
				</div>

				<div id="fstu-member-regional-meta" class="fstu-member-regional-meta fstu-hidden">
					<table class="fstu-info-table">
						<tr>
							<th><?php esc_html_e( 'Дата/час створення', 'fstu' ); ?></th>
							<td id="fstu-member-regional-meta-date">—</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Хто додав запис', 'fstu' ); ?></th>
							<td id="fstu-member-regional-meta-user">—</td>
						</tr>
					</table>
				</div>

				<div class="fstu-form-actions">
					<button type="submit" class="fstu-btn fstu-btn--primary" id="fstu-member-regional-form-submit"><?php esc_html_e( 'Зберегти', 'fstu' ); ?></button>
					<button type="button" class="fstu-btn fstu-btn--secondary" data-close-modal="fstu-member-regional-form-modal"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>

