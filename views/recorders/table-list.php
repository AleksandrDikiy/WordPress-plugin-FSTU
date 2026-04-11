<?php
/**
 * Таблиця списку реєстраторів.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-11
 *
 * @package FSTU\Modules\Registry\Recorders
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="fstu-table-wrap">
	<table class="fstu-table">
		<thead class="fstu-thead">
			<tr>
				<th class="fstu-th fstu-th--num">#</th>
				<th class="fstu-th fstu-th--region"><?php esc_html_e( 'Область', 'fstu' ); ?></th>
				<th class="fstu-th fstu-th--unit"><?php esc_html_e( 'Осередок', 'fstu' ); ?></th>
				<th class="fstu-th fstu-th--wide-name">
					<div class="fstu-th-with-search">
						<span><?php esc_html_e( 'ПІБ', 'fstu' ); ?></span>
						<input
							type="text"
							id="fstu-recorders-search"
							class="fstu-input--in-header"
							placeholder="<?php esc_attr_e( '🔍 Пошук...', 'fstu' ); ?>"
							aria-label="<?php esc_attr_e( 'Пошук по ПІБ', 'fstu' ); ?>"
							autocomplete="off"
						>
					</div>
				</th>
				<th class="fstu-th fstu-th--email"><?php esc_html_e( 'E-mail', 'fstu' ); ?></th>
				<th class="fstu-th fstu-th--actions"><?php esc_html_e( 'Дії', 'fstu' ); ?></th>
			</tr>
		</thead>
		<tbody class="fstu-tbody" id="fstu-recorders-tbody">
			<tr class="fstu-row">
				<td colspan="6" class="fstu-no-results"><?php esc_html_e( 'Завантаження...', 'fstu' ); ?></td>
			</tr>
		</tbody>
	</table>
</div>

