<?php
/**
 * Шаблон вкладки "Історія мерилок" для судна.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-09
 *
 * @package FSTU\Modules\UserFstu\Merilkas
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// Змінні $items, $can_manage передаються з контролера
?>

<div class="fstu-action-bar fstu-action-bar--merilkas">
	<div class="fstu-action-bar__group">
		<?php if ( ! empty( $can_manage ) ) : ?>
			<button type="button" class="fstu-btn fstu-btn--primary" id="fstu-merilkas-add-btn">
				<span class="fstu-btn__icon">➕</span> ДОДАТИ МЕРИЛКУ
			</button>
		<?php endif; ?>
	</div>
	<div class="fstu-action-bar__group">
		<span class="fstu-text-muted" style="font-size: 12px;">Актуальною для змагань автоматично вважається мерилка з найновішою датою обміру.</span>
	</div>
</div>

<div class="fstu-table-wrap">
	<table class="fstu-table fstu-table--compact">
		<thead class="fstu-thead">
			<tr>
				<th class="fstu-th fstu-th--num">№</th>
				<th class="fstu-th">Дата обміру</th>
				<th class="fstu-th" title="Гоночний бал без спінакера та ваги екіпажу">ГБ</th>
				<th class="fstu-th" title="Гоночний бал ЗІ спінакером">ГБ (спін.)</th>
				<th class="fstu-th" title="Гоночний бал З вагою екіпажу">ГБ (екіп.)</th>
				<th class="fstu-th" title="Гоночний бал ЗІ спінакером та вагою екіпажу">ГБ (спін.+екіп.)</th>
				<th class="fstu-th" title="Статус використання у змаганнях">Статус</th>
				<th class="fstu-th fstu-th--actions">Дії</th>
			</tr>
		</thead>
		<tbody class="fstu-tbody" id="fstu-merilkas-tbody">
			<?php if ( ! empty( $items ) && is_array( $items ) ) : ?>
				<?php foreach ( $items as $index => $item ) : ?>
					<tr class="fstu-row">
						<td class="fstu-td fstu-td--num"><?php echo (int) $index + 1; ?></td>
						<td class="fstu-td"><?php echo esc_html( date( 'd.m.Y', strtotime( $item['MR_DateObmera'] ) ) ); ?></td>
						<td class="fstu-td"><?php echo esc_html( $item['MR_GB'] ?? '0' ); ?></td>
						<td class="fstu-td"><?php echo esc_html( $item['MR_GB_Spinaker'] ?? '0' ); ?></td>
						<td class="fstu-td"><?php echo esc_html( $item['MR_GB_CrewWeight'] ?? '0' ); ?></td>
						<td class="fstu-td"><?php echo esc_html( $item['MR_GB_CrewWeight_Spinaker'] ?? '0' ); ?></td>
						
						<td class="fstu-td" style="text-align: center;">
							<?php if ( ! empty( $item['is_latest'] ) ) : ?>
								<span class="fstu-badge fstu-badge--insert" title="Ця мерилка автоматично використовується для нових змагань">Актуальна</span>
							<?php elseif ( ! empty( $item['is_used'] ) ) : ?>
								<span class="fstu-badge fstu-badge--default" title="Мерилка збережена в історії змагань">В історії</span>
							<?php else : ?>
								<span class="fstu-text-muted">—</span>
							<?php endif; ?>
						</td>
						
						<td class="fstu-td fstu-td--actions">
							<div class="fstu-dropdown fstu-sailboats-dropdown">
								<button type="button" class="fstu-dropdown-toggle fstu-sailboats-dropdown__toggle" title="Дії" aria-expanded="false">▼</button>
								<ul class="fstu-dropdown-menu fstu-sailboats-dropdown__menu">
									
									<li><button type="button" class="fstu-sailboats-dropdown__item fstu-merilkas-edit-btn" data-mr-id="<?php echo esc_attr( $item['MR_ID'] ); ?>">📝 Перегляд / Редагувати</button></li>
									<li><button type="button" class="fstu-sailboats-dropdown__item fstu-merilkas-clone-btn" data-mr-id="<?php echo esc_attr( $item['MR_ID'] ); ?>">📋 Створити копію</button></li>
									
									<li><hr class="fstu-dropdown-divider" style="margin: 4px 0; border: none; border-top: 1px solid #e5e7eb;"></li>
									
									<li><button type="button" class="fstu-sailboats-dropdown__item fstu-merilkas-print-btn" data-mr-id="<?php echo esc_attr( $item['MR_ID'] ); ?>">🖨 Друк (PDF/A4)</button></li>
									
									<?php if ( empty( $item['is_used'] ) && ! empty( $can_manage ) ) : ?>
										<li><hr class="fstu-dropdown-divider" style="margin: 4px 0; border: none; border-top: 1px solid #e5e7eb;"></li>
										<li><button type="button" class="fstu-sailboats-dropdown__item fstu-sailboats-dropdown__item--danger fstu-merilkas-delete-btn" data-mr-id="<?php echo esc_attr( $item['MR_ID'] ); ?>">❌ Видалити</button></li>
									<?php endif; ?>
									
								</ul>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr class="fstu-row">
					<td colspan="8" class="fstu-no-results">Обмірних свідоцтв (мерилок) для цього судна ще не додано.</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>