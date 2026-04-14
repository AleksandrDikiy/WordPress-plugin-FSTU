<?php
/**
 * Таблиця списку заходів Calendar_Events.
 *
 * Version: 1.0.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar\CalendarEvents
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="fstu-table-wrap">
	<table class="fstu-table fstu-calendar-table">
		<thead class="fstu-thead">
			<tr>
				<th class="fstu-th fstu-th--num">#</th>
				<th class="fstu-th fstu-th--wide-name">
					<div class="fstu-th-with-search">
						<span><?php esc_html_e( 'Найменування', 'fstu' ); ?></span>
						<input type="text" id="fstu-calendar-search" class="fstu-input--in-header" placeholder="<?php esc_attr_e( '🔍 Пошук...', 'fstu' ); ?>" autocomplete="off">
					</div>
				</th>
				<th class="fstu-th fstu-th--date"><?php esc_html_e( 'Початок', 'fstu' ); ?></th>
				<th class="fstu-th fstu-th--date"><?php esc_html_e( 'Завершення', 'fstu' ); ?></th>
				<th class="fstu-th"><?php esc_html_e( 'Статус', 'fstu' ); ?></th>
				<th class="fstu-th"><?php esc_html_e( 'Місто', 'fstu' ); ?></th>
				<th class="fstu-th"><?php esc_html_e( 'Тип', 'fstu' ); ?></th>
				<th class="fstu-th fstu-th--user"><?php esc_html_e( 'Відповідальний', 'fstu' ); ?></th>
				<th class="fstu-th fstu-th--actions"><?php esc_html_e( 'Дії', 'fstu' ); ?></th>
			</tr>
		</thead>
		<tbody id="fstu-calendar-tbody" class="fstu-tbody">
			<tr>
				<td colspan="9" class="fstu-no-results"><?php esc_html_e( 'Завантаження...', 'fstu' ); ?></td>
			</tr>
		</tbody>
	</table>
</div>

