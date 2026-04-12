<?php
/**
 * Таблиця списку модуля «Склад керівних органів ФСТУ».
 *
 * Version:     1.0.0
 * Date_update: 2026-04-12
 *
 * @package FSTU\Modules\Registry\Guidance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="fstu-table-wrap fstu-table-wrap--compact">
	<table class="fstu-table fstu-table--compact">
		<thead class="fstu-thead">
			<tr>
				<th class="fstu-th fstu-th--num">#</th>
				<th class="fstu-th"><?php esc_html_e( 'Керівний орган', 'fstu' ); ?></th>
				<th class="fstu-th"><?php esc_html_e( 'Посада', 'fstu' ); ?></th>
				<th class="fstu-th fstu-th--wide-name">
					<div class="fstu-th-with-search">
						<span><?php esc_html_e( 'ПІБ', 'fstu' ); ?></span>
						<input type="text" id="fstu-guidance-search" class="fstu-input--in-header" placeholder="<?php esc_attr_e( '🔍 Пошук...', 'fstu' ); ?>" autocomplete="off" aria-label="<?php esc_attr_e( 'Пошук по складу керівних органів', 'fstu' ); ?>">
					</div>
				</th>
				<th class="fstu-th"><?php esc_html_e( 'Телефони', 'fstu' ); ?></th>
				<th class="fstu-th"><?php esc_html_e( 'Email', 'fstu' ); ?></th>
				<th class="fstu-th fstu-th--actions"><?php esc_html_e( 'ДІЇ', 'fstu' ); ?></th>
			</tr>
		</thead>
		<tbody class="fstu-tbody" id="fstu-guidance-tbody">
			<tr class="fstu-row">
				<td colspan="7" class="fstu-no-results"><?php esc_html_e( 'Завантаження...', 'fstu' ); ?></td>
			</tr>
		</tbody>
	</table>
</div>

