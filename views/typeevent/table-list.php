<?php
/**
 * Таблиця списку видів змагань.
 * Пошук вбудовано у шапку (колонка «Найменування»).
 * Колонка «Сортування» прибрана.
 *
 * Version:     1.1.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\TypeEvent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$permissions  = isset( $permissions ) && is_array( $permissions ) ? $permissions : [];
$show_actions = ! empty( $permissions['canView'] );
$colspan      = $show_actions ? 3 : 2;
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
							id="fstu-typeevent-search"
							class="fstu-input--in-header"
							placeholder="<?php esc_attr_e( '🔍 Пошук...', 'fstu' ); ?>"
							aria-label="<?php esc_attr_e( 'Пошук за найменуванням', 'fstu' ); ?>"
							autocomplete="off"
						>
					</div>
				</th>
				<?php if ( $show_actions ) : ?>
					<th class="fstu-th fstu-th--actions"><?php esc_html_e( 'Дії', 'fstu' ); ?></th>
				<?php endif; ?>
			</tr>
		</thead>
		<tbody class="fstu-tbody" id="fstu-typeevent-tbody">
			<tr class="fstu-row">
				<td colspan="<?php echo esc_attr( (string) $colspan ); ?>" class="fstu-no-results"><?php esc_html_e( 'Завантаження...', 'fstu' ); ?></td>
			</tr>
		</tbody>
	</table>
</div>

