<?php
/**
 * Основний шаблон модуля "Довідник суддівських категорій".
 *
 * Version:     1.0.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Dictionaries\RefereeCategory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$permissions    = isset( $permissions ) && is_array( $permissions ) ? $permissions : [];
$can_manage     = ! empty( $permissions['canManage'] );
$can_protocol   = ! empty( $permissions['canProtocol'] );
?>

<div id="fstu-referee-category" class="fstu-referee-category-wrap">
	<h2 class="fstu-referee-category-title"><?php esc_html_e( 'Довідник суддівських категорій', 'fstu' ); ?></h2>

	<div id="fstu-referee-category-page-message" class="fstu-page-message fstu-hidden" aria-live="polite"></div>

	<div class="fstu-action-bar">
		<div class="fstu-referee-category-action-bar__left">
			<?php if ( $can_manage ) : ?>
				<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-referee-category-add-btn">
					<span class="fstu-btn__icon">➕</span>
					<?php esc_html_e( 'Додати запис', 'fstu' ); ?>
				</button>
			<?php endif; ?>
		</div>

		<div class="fstu-referee-category-action-bar__center"></div>

		<div class="fstu-referee-category-action-bar__right">
			<?php if ( $can_protocol ) : ?>
				<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-referee-category-protocol-btn">
					<span class="fstu-btn__icon">📋</span>
					<?php esc_html_e( 'ПРОТОКОЛ', 'fstu' ); ?>
				</button>
				<button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-referee-category-protocol-back-btn">
					<span class="fstu-btn__icon">↩</span>
					<?php esc_html_e( 'ДОВІДНИК', 'fstu' ); ?>
				</button>
			<?php endif; ?>
		</div>
	</div>

	<div id="fstu-referee-category-main">
		<?php include FSTU_PLUGIN_DIR . 'views/referee-category/table-list.php'; ?>

		<div class="fstu-pagination fstu-pagination--compact">
			<div class="fstu-pagination__left">
				<label class="fstu-pagination__per-page-label" for="fstu-referee-category-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
				<select id="fstu-referee-category-per-page" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Кількість записів на сторінці', 'fstu' ); ?>">
					<option value="10" selected>10</option>
					<option value="15">15</option>
					<option value="25">25</option>
					<option value="50">50</option>
				</select>
			</div>
			<div class="fstu-pagination__controls">
				<button type="button" class="fstu-btn--page" id="fstu-referee-category-prev-page">«</button>
				<div id="fstu-referee-category-pagination-pages"></div>
				<button type="button" class="fstu-btn--page" id="fstu-referee-category-next-page">»</button>
			</div>
			<div class="fstu-pagination__info">
				<span id="fstu-referee-category-pagination-info" aria-live="polite"></span>
			</div>
		</div>
	</div>

	<?php include FSTU_PLUGIN_DIR . 'views/referee-category/protocol-table-list.php'; ?>
</div>

<?php include FSTU_PLUGIN_DIR . 'views/referee-category/modal-view.php'; ?>
<?php include FSTU_PLUGIN_DIR . 'views/referee-category/modal-form.php'; ?>

