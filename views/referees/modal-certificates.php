<?php
/**
 * Модальне вікно довідок за суддівство.
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
?>

<div id="fstu-referees-certificates-modal" class="fstu-modal-overlay fstu-hidden" role="dialog" aria-modal="true" aria-labelledby="fstu-referees-certificates-modal-title">
	<div class="fstu-modal fstu-modal--wide fstu-modal--referees-certificates">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-referees-certificates-modal-title"><?php esc_html_e( 'Довідки за суддівство', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-referees-certificates-modal">×</button>
		</div>
		<div class="fstu-modal__body fstu-modal__body--compact-certificates">
			<div id="fstu-referees-certificates-message" class="fstu-form-message fstu-hidden"></div>
			<div class="fstu-referees-certificates-toolbar">
				<div class="fstu-referees-certificates-toolbar__title">
					<strong id="fstu-referees-certificates-referee-name">—</strong>
				</div>
				<div class="fstu-referees-certificates-toolbar__actions">
					<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-referees-certificates-refresh-btn"><?php esc_html_e( 'ОНОВИТИ', 'fstu' ); ?></button>
					<?php if ( $can_manage_certificates ) : ?>
						<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-referees-certificates-add-btn"><?php esc_html_e( 'ДОДАТИ ДОВІДКУ', 'fstu' ); ?></button>
					<?php endif; ?>
				</div>
			</div>
			<table class="fstu-table fstu-table--compact">
				<thead class="fstu-thead">
					<tr>
						<th class="fstu-th fstu-th--date"><?php esc_html_e( 'Дата', 'fstu' ); ?></th>
						<th class="fstu-th"><?php esc_html_e( 'Захід', 'fstu' ); ?></th>
						<th class="fstu-th"><?php esc_html_e( 'Категорія', 'fstu' ); ?></th>
						<th class="fstu-th"><?php esc_html_e( 'Посилання', 'fstu' ); ?></th>
						<th class="fstu-th fstu-th--actions"><?php esc_html_e( 'Дії', 'fstu' ); ?></th>
					</tr>
				</thead>
				<tbody class="fstu-tbody" id="fstu-referees-certificates-tbody">
					<tr class="fstu-row">
						<td colspan="5" class="fstu-no-results"><?php esc_html_e( 'Завантаження...', 'fstu' ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="fstu-modal__footer">
			<button type="button" class="fstu-btn" data-close-modal="fstu-referees-certificates-modal"><?php esc_html_e( 'Закрити', 'fstu' ); ?></button>
		</div>
	</div>
</div>

