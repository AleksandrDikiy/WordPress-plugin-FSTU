<?php
/**
 * View: Панель дій — довідник клубів.
 *
 * Version:     1.1.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Clubs\Views
 *
 * @var bool $can_edit     Чи може поточний користувач редагувати.
 * @var bool $can_protocol Чи може поточний користувач переглядати протокол.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="fstu-action-bar">

	<?php if ( $can_edit ) : ?>
		<button type="button"
		        class="fstu-btn fstu-btn--secondary"
		        id="fstu-club-btn-add"
		        aria-haspopup="dialog">
			<span class="fstu-btn__icon" aria-hidden="true">+</span> Додати клуб
		</button>
	<?php endif; ?>

	<button type="button"
	        class="fstu-btn fstu-btn--secondary"
	        id="fstu-club-btn-refresh"
	        title="Оновити список">
		<span class="fstu-btn__icon" aria-hidden="true">↻</span> Оновити
	</button>

	<?php if ( $can_protocol ) : ?>
		<button type="button"
		        class="fstu-btn fstu-btn--secondary"
		        id="fstu-club-btn-protocol"
		        title="Переглянути протокол змін">
			<span class="fstu-btn__icon" aria-hidden="true">📋</span> ПРОТОКОЛ
		</button>

		<button type="button"
		        class="fstu-btn fstu-btn--secondary fstu-hidden"
		        id="fstu-club-btn-protocol-back"
		        title="Повернутись до довідника">
			<span class="fstu-btn__icon" aria-hidden="true">↩</span> ДОВІДНИК
		</button>
	<?php endif; ?>

</div>
