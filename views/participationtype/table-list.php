<?php
/**
 * Таблиця списку довідника видів участі в заходах.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-12
 *
 * @package FSTU\Dictionaries\ParticipationType
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
				<th class="fstu-th fstu-th--wide-name">
					<div class="fstu-th-with-search">
						<span><?php esc_html_e( 'Найменування', 'fstu' ); ?></span>
						<input
							type="text"
							id="fstu-participationtype-search"
							class="fstu-input--in-header"
							placeholder="<?php esc_attr_e( '🔍 Пошук...', 'fstu' ); ?>"
							aria-label="<?php esc_attr_e( 'Пошук по найменуванню', 'fstu' ); ?>"
							autocomplete="off"
						>
					</div>
				</th>
				<th class="fstu-th fstu-th--participation-type"><?php esc_html_e( 'Тип', 'fstu' ); ?></th>
				<th class="fstu-th fstu-th--actions"><?php esc_html_e( 'Дії', 'fstu' ); ?></th>
			</tr>
		</thead>
		<tbody class="fstu-tbody" id="fstu-participationtype-tbody">
			<tr class="fstu-row">
				<td colspan="4" class="fstu-no-results"><?php esc_html_e( 'Завантаження...', 'fstu' ); ?></td>
			</tr>
		</tbody>
	</table>
</div>

