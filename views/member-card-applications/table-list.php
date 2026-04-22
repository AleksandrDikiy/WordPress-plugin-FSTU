<?php
/**
 * Таблиця списку модуля «Посвідчення членів ФСТУ».
 *
 * Version:     1.1.0
 * Date_update: 2026-04-10
 *
 * @package FSTU\Modules\UserFstu\MemberCardApplications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="fstu-table-wrap">
	<table class="fstu-table fstu-table--member-card-applications">
		<thead class="fstu-thead">
			<tr>
				<th class="fstu-th fstu-th--num">#</th>
				<th class="fstu-th fstu-th--wide-name">
					<div class="fstu-th-with-search">
						<span><?php esc_html_e( 'ПІБ', 'fstu' ); ?></span>
						<input
							type="text"
							id="fstu-member-card-applications-search"
							class="fstu-input--in-header fstu-member-card-applications-search-input"
							placeholder="<?php esc_attr_e( '🔍 Пошук...', 'fstu' ); ?>"
							aria-label="<?php esc_attr_e( 'Пошук за ПІБ', 'fstu' ); ?>"
							autocomplete="off"
						>
					</div>
				</th>
				<th class="fstu-th"><?php esc_html_e( '№ картки', 'fstu' ); ?></th>
				<th class="fstu-th"><?php esc_html_e( 'Регіон', 'fstu' ); ?></th>
				<th class="fstu-th"><?php esc_html_e( 'Статус', 'fstu' ); ?></th>
				<th class="fstu-th"><?php esc_html_e( 'Тип', 'fstu' ); ?></th>
				<th class="fstu-th fstu-th--date"><?php esc_html_e( 'Додано', 'fstu' ); ?></th>
				<th class="fstu-th fstu-th--actions"><?php esc_html_e( 'Дії', 'fstu' ); ?></th>
			</tr>
		</thead>
		<tbody class="fstu-tbody" id="fstu-member-card-applications-tbody">
			<tr class="fstu-row">
				<td colspan="8" class="fstu-no-results"><?php esc_html_e( 'Завантаження даних посвідчень...', 'fstu' ); ?></td>
			</tr>
		</tbody>
	</table>
</div>

