<?php
/**
 * View: Панель дій — довідник клубів.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-05
 *
 * @package FSTU\Clubs\Views
 *
 * @var bool $can_edit Чи може поточний користувач редагувати.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="fstu-action-bar">

	<?php if ( $can_edit ) : ?>
		<button type="button"
		        class="fstu-btn fstu-btn--primary"
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

</div>
