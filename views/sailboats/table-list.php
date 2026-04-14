<?php
/**
 * Таблиця списку суден.
 * Пошук вбудовано у шапку таблиці (колонка «Найменування»).
 *
 * Version:     1.3.0
 * Date_update: 2026-04-07
 *
 * @package FSTU\Modules\Registry\Sailboats
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$permissions  = isset( $permissions ) && is_array( $permissions ) ? $permissions : [];
$can_finance  = ! empty( $permissions['canFinance'] );
$is_logged_in = is_user_logged_in();
$colspan      = $can_finance ? 13 : 9;

if ( ! $is_logged_in ) {
    $colspan--;
}
?>

<div class="fstu-table-wrap">
	<table class="fstu-table">
		<thead class="fstu-thead">
			<tr>
				<th class="fstu-th fstu-th--num">#</th>
				<th class="fstu-th fstu-th--wide-name fstu-th--wide-name-search">
					<div class="fstu-th-with-search fstu-th-with-search--stack">
						<span><?php esc_html_e( 'Найменування', 'fstu' ); ?></span>
						<input
							type="text"
							id="fstu-sailboats-search"
							class="fstu-input--in-header fstu-input--in-header-compact"
							placeholder="<?php esc_attr_e( '🔍 Пошук...', 'fstu' ); ?>"
							aria-label="<?php esc_attr_e( 'Пошук за найменуванням судна', 'fstu' ); ?>"
							autocomplete="off"
						>
					</div>
				</th>
				<th class="fstu-th"><?php esc_html_e( '№ реєстрації', 'fstu' ); ?></th>
				<th class="fstu-th"><?php esc_html_e( '№ на вітрилі', 'fstu' ); ?></th>
				<th class="fstu-th"><?php esc_html_e( 'Область', 'fstu' ); ?></th>
				<th class="fstu-th"><?php esc_html_e( 'Власник / капітан', 'fstu' ); ?></th>
				<th class="fstu-th"><?php esc_html_e( 'Виробник', 'fstu' ); ?></th>
                <?php if ( $is_logged_in ) : ?>
                    <th class="fstu-th"><?php esc_html_e( 'Статус', 'fstu' ); ?></th>
                <?php endif; ?>
				<?php if ( $can_finance ) : ?>
					<th class="fstu-th"><?php esc_html_e( 'Дата реєстрації', 'fstu' ); ?></th>
					<th class="fstu-th fstu-th--finance"><?php esc_html_e( 'V1', 'fstu' ); ?></th>
					<th class="fstu-th fstu-th--finance"><?php esc_html_e( 'V2', 'fstu' ); ?></th>
					<th class="fstu-th fstu-th--finance"><?php esc_html_e( 'F1', 'fstu' ); ?></th>
					<th class="fstu-th fstu-th--finance"><?php esc_html_e( 'F2', 'fstu' ); ?></th>
				<?php endif; ?>
				<th class="fstu-th fstu-th--actions"><?php esc_html_e( 'ДІЇ', 'fstu' ); ?></th>
			</tr>
		</thead>
		<tbody class="fstu-tbody" id="fstu-sailboats-tbody">
			<tr class="fstu-row">
                <td colspan="<?php echo esc_attr( (string) $colspan ); ?>" class="fstu-no-results"><?php esc_html_e( 'Завантаження...', 'fstu' ); ?></td>
			</tr>
		</tbody>
	</table>
</div>

