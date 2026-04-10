<?php
/**
 * Головний шаблон модуля "Довідник осередків ФСТУ".
 * * Version: 1.0.0
 * Date_update: 2026-04-10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="fstu-module-wrapper fstu-units-module">
	
	<div id="fstu-units-directory-section">
		<div class="fstu-action-bar">
			<?php if ( current_user_can( 'manage_options' ) ) : ?>
				<button type="button" class="fstu-btn fstu-btn--action" id="fstu-units-btn-add">
					<span class="fstu-icon-plus"></span> ДОДАТИ
				</button>
				<button type="button" class="fstu-btn fstu-btn--action" id="fstu-units-btn-protocol">
					<span class="fstu-icon-list-alt"></span> ПРОТОКОЛ
				</button>
			<?php endif; ?>
		</div>

		<?php include plugin_dir_path( __FILE__ ) . 'table-list.php'; ?>
	</div>

	<div id="fstu-units-protocol-section" style="display: none;">
		<div class="fstu-action-bar">
			<button type="button" class="fstu-btn fstu-btn--action" id="fstu-units-btn-back-directory">
				<span class="fstu-icon-arrow-left"></span> ДОВІДНИК
			</button>
		</div>

		<?php include plugin_dir_path( __FILE__ ) . 'protocol-table-list.php'; ?>
	</div>

	<?php include plugin_dir_path( __FILE__ ) . 'modals/modal-edit.php'; ?>
	<?php include plugin_dir_path( __FILE__ ) . 'modals/modal-view.php'; ?>
</div>