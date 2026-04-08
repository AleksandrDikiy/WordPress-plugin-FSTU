<?php
/**
 * Таблиця списку міст.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-07
 *
 * @package FSTU\Dictionaries\City
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
				<th class="fstu-th"><?php esc_html_e( 'Область', 'fstu' ); ?></th>
				<th class="fstu-th fstu-th--wide-name">
					<div class="fstu-th-with-search">
						<span><?php esc_html_e( 'Найменування', 'fstu' ); ?></span>
						<input type="text" id="fstu-city-search" class="fstu-input--in-header" placeholder="<?php esc_attr_e( '🔍 Пошук...', 'fstu' ); ?>" autocomplete="off">
					</div>
				</th>
				<th class="fstu-th fstu-th--name-eng"><?php esc_html_e( 'Англійською', 'fstu' ); ?></th>
				<th class="fstu-th fstu-th--actions"><?php esc_html_e( 'ДІЇ', 'fstu' ); ?></th>
			</tr>
		</thead>
		<tbody class="fstu-tbody" id="fstu-city-tbody">
			<tr class="fstu-row"><td colspan="5" class="fstu-no-results"><?php esc_html_e( 'Завантаження...', 'fstu' ); ?></td></tr>
		</tbody>
	</table>
</div>

