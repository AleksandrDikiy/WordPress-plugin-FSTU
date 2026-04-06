<?php
/**
 * Таблиця списку осередків.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\Units
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$permissions  = isset( $permissions ) && is_array( $permissions ) ? $permissions : [];
$show_actions = ! empty( $permissions['canView'] );
$colspan      = $show_actions ? 9 : 8;
?>

<div class="fstu-table-wrap">
	<table class="fstu-table">
		<thead class="fstu-thead">
			<tr>
				<th class="fstu-th fstu-th--num">#</th>
				<th class="fstu-th fstu-th--ofst"><?php esc_html_e( 'Найменування', 'fstu' ); ?></th>
				<th class="fstu-th"><?php esc_html_e( 'Короткo', 'fstu' ); ?></th>
				<th class="fstu-th"><?php esc_html_e( 'ОПФ', 'fstu' ); ?></th>
				<th class="fstu-th"><?php esc_html_e( 'Тип', 'fstu' ); ?></th>
				<th class="fstu-th"><?php esc_html_e( 'Регіон', 'fstu' ); ?></th>
				<th class="fstu-th"><?php esc_html_e( 'Місто', 'fstu' ); ?></th>
				<th class="fstu-th"><?php esc_html_e( 'Річний внесок', 'fstu' ); ?></th>
				<?php if ( $show_actions ) : ?>
					<th class="fstu-th fstu-th--actions"><?php esc_html_e( 'Дії', 'fstu' ); ?></th>
				<?php endif; ?>
			</tr>
		</thead>
		<tbody class="fstu-tbody" id="fstu-units-tbody">
			<tr class="fstu-row">
				<td colspan="<?php echo esc_attr( (string) $colspan ); ?>" class="fstu-no-results">
					<?php esc_html_e( 'Завантаження...', 'fstu' ); ?>
				</td>
			</tr>
		</tbody>
	</table>
</div>

