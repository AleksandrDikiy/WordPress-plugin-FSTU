<?php
/**
 * Таблиця списку модуля «Реєстр стернових ФСТУ».
 *
 * Version:     1.2.0
 * Date_update: 2026-04-08
 *
 * @package FSTU\Modules\Registry\Steering
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$permissions     = isset( $permissions ) && is_array( $permissions ) ? $permissions : [];
$can_see_finance = ! empty( $permissions['canSeeFinance'] );
$colspan         = $can_see_finance ? 12 : 5;
?>

<div class="fstu-table-wrap">
	<table class="fstu-table fstu-table--steering">
		<thead class="fstu-thead">
			<tr>
				<th class="fstu-th fstu-th--num">#</th>
				<th class="fstu-th fstu-th--wide-name">
					<div class="fstu-th-with-search">
						<label>
							<?php esc_html_e( 'ПІБ', 'fstu' ); ?>
							<input
								type="text"
								id="fstu-steering-search"
								class="fstu-input--in-header"
								placeholder="<?php esc_attr_e( '🔍 Пошук...', 'fstu' ); ?>"
								aria-label="<?php esc_attr_e( 'Пошук за ПІБ стернового', 'fstu' ); ?>"
								autocomplete="off"
							>
						</label>
					</div>
				</th>
				<th class="fstu-th"><?php esc_html_e( '№ посвідчення', 'fstu' ); ?></th>
				<th class="fstu-th"><?php esc_html_e( 'Дата док.', 'fstu' ); ?></th>
				<?php if ( $can_see_finance ) : ?>
					<th class="fstu-th"><?php esc_html_e( 'V1', 'fstu' ); ?></th>
					<th class="fstu-th"><?php esc_html_e( 'V2', 'fstu' ); ?></th>
					<th class="fstu-th"><?php esc_html_e( 'F1', 'fstu' ); ?></th>
					<th class="fstu-th"><?php esc_html_e( 'F2', 'fstu' ); ?></th>
					<th class="fstu-th"><?php esc_html_e( 'Статус', 'fstu' ); ?></th>
					<th class="fstu-th"><?php esc_html_e( 'К-сть', 'fstu' ); ?></th>
					<th class="fstu-th"><?php esc_html_e( 'Сума', 'fstu' ); ?></th>
				<?php endif; ?>
				<th class="fstu-th fstu-th--actions"><?php esc_html_e( 'Дії', 'fstu' ); ?></th>
			</tr>
		</thead>
		<tbody class="fstu-tbody" id="fstu-steering-tbody">
			<tr class="fstu-row">
				<td colspan="<?php echo esc_attr( (string) $colspan ); ?>" class="fstu-no-results">
					<?php esc_html_e( 'Завантаження даних реєстру...', 'fstu' ); ?>
				</td>
			</tr>
		</tbody>
		<?php if ( $can_see_finance ) : ?>
			<tfoot>
				<tr>
					<td colspan="11" class="fstu-steering-footer-cell" id="fstu-steering-footer-summary">
						<?php esc_html_e( 'Підсумки по внесках буде показано після завантаження реєстру.', 'fstu' ); ?>
					</td>
				</tr>
			</tfoot>
		<?php endif; ?>
	</table>
</div>

