<?php
/**
 * Таблиця списку суддів ФСТУ.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-08
 *
 * @package FSTU\Modules\Registry\Referees
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="fstu-table-wrap">
	<table class="fstu-table fstu-table--referees">
		<thead class="fstu-thead">
			<tr>
				<th class="fstu-th fstu-th--num">#</th>
				<th class="fstu-th fstu-th--wide-name">
					<div class="fstu-th-with-search">
						<span><?php esc_html_e( 'ПІБ', 'fstu' ); ?></span>
						<input
							type="text"
							id="fstu-referees-search"
							class="fstu-input--in-header"
							placeholder="<?php esc_attr_e( '🔍 Пошук...', 'fstu' ); ?>"
							aria-label="<?php esc_attr_e( 'Пошук за ПІБ судді', 'fstu' ); ?>"
							autocomplete="off"
						>
					</div>
				</th>
				<th class="fstu-th"><?php esc_html_e( 'Категорія', 'fstu' ); ?></th>
				<th class="fstu-th"><?php esc_html_e( 'Наказ', 'fstu' ); ?></th>
				<th class="fstu-th fstu-th--count"><?php esc_html_e( 'Довідки', 'fstu' ); ?></th>
				<th class="fstu-th"><?php esc_html_e( 'Регіон', 'fstu' ); ?></th>
				<th class="fstu-th fstu-th--actions"><?php esc_html_e( 'Дії', 'fstu' ); ?></th>
			</tr>
		</thead>
		<tbody class="fstu-tbody" id="fstu-referees-tbody">
			<tr class="fstu-row">
				<td colspan="7" class="fstu-no-results"><?php esc_html_e( 'Завантаження...', 'fstu' ); ?></td>
			</tr>
		</tbody>
	</table>
</div>

