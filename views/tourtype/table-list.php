<?php
/**
 * Таблиця списку довідника видів походів.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-12
 *
 * @package FSTU\Dictionaries\TourType
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
				<th class="fstu-th fstu-th--category"><?php esc_html_e( 'Категорія', 'fstu' ); ?></th>
				<th class="fstu-th fstu-th--wide-name">
					<div class="fstu-th-with-search">
						<span><?php esc_html_e( 'Найменування', 'fstu' ); ?></span>
						<input
							type="text"
							id="fstu-tourtype-search"
							class="fstu-input--in-header"
							placeholder="<?php esc_attr_e( '🔍 Пошук...', 'fstu' ); ?>"
							aria-label="<?php esc_attr_e( 'Пошук по найменуванню', 'fstu' ); ?>"
							autocomplete="off"
						>
					</div>
				</th>
				<th class="fstu-th fstu-th--code"><?php esc_html_e( 'Код', 'fstu' ); ?></th>
				<th class="fstu-th fstu-th--day"><?php esc_html_e( 'Дні', 'fstu' ); ?></th>
				<th class="fstu-th fstu-th--actions"><?php esc_html_e( 'Дії', 'fstu' ); ?></th>
			</tr>
		</thead>
		<tbody class="fstu-tbody" id="fstu-tourtype-tbody">
			<tr class="fstu-row">
				<td colspan="6" class="fstu-no-results"><?php esc_html_e( 'Завантаження...', 'fstu' ); ?></td>
			</tr>
		</tbody>
	</table>
</div>

