<?php
/**
 * View: Таблиця клубів.
 * Пошук вбудовано у шапку (колонка «Назва клубу»).
 * <tbody> заповнюється через AJAX.
 *
 * Version:     1.1.1
 * Date_update: 2026-04-13
 *
 * @package FSTU\Dictionaries\Clubs\Views
 *
 * @var bool $can_edit   Чи показувати колонку дій.
 * @var bool $is_admin   Чи є адміністратор.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="fstu-table-wrap" role="region" aria-label="Список клубів" aria-live="polite">

	<div class="fstu-loader fstu-hidden" id="fstu-clubs-loader" aria-hidden="true">
		<span class="fstu-loader__spinner"></span>
		<span class="fstu-loader__text">Завантаження...</span>
	</div>

	<table class="fstu-table" id="fstu-clubs-table">
		<thead class="fstu-thead">
			<tr>
				<th class="fstu-th fstu-th--num" scope="col">№</th>
				<th class="fstu-th fstu-th--name" scope="col">
					<div class="fstu-th-with-search">
						<span><?php esc_html_e( 'Назва клубу', 'fstu' ); ?></span>
						<input
							type="search"
							id="fstu-club-search"
							class="fstu-input--in-header"
							placeholder="<?php esc_attr_e( '🔍 Пошук...', 'fstu' ); ?>"
							aria-label="<?php esc_attr_e( 'Пошук за назвою клубу', 'fstu' ); ?>"
							autocomplete="off"
						>
					</div>
				</th>
				<th class="fstu-th fstu-th--adr" scope="col"><?php esc_html_e( 'Поштова адреса', 'fstu' ); ?></th>
				<?php if ( $can_edit ) : ?>
					<th class="fstu-th fstu-th--actions" scope="col">
						<span class="fstu-sr-only"><?php esc_html_e( 'Дії', 'fstu' ); ?></span>
					</th>
				<?php endif; ?>
			</tr>
		</thead>
		<tbody class="fstu-tbody" id="fstu-clubs-tbody">
			<tr>
				<td colspan="<?php echo $can_edit ? '4' : '3'; ?>" class="fstu-no-results">
					<span class="fstu-loader__spinner"></span> <?php esc_html_e( 'Завантаження...', 'fstu' ); ?>
				</td>
			</tr>
		</tbody>
	</table>

</div>
