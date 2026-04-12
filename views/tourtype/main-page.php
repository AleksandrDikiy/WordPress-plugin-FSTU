<?php
/**
 * Основний шаблон модуля "Довідник видів походів".
 *
 * Version:     1.0.2
 * Date_update: 2026-04-12
 *
 * @package FSTU\Dictionaries\TourType
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$permissions  = isset( $permissions ) && is_array( $permissions ) ? $permissions : [];
$can_manage   = ! empty( $permissions['canManage'] );
$can_protocol = ! empty( $permissions['canProtocol'] );
?>

<div id="fstu-tourtype" class="fstu-tourtype-wrap">
	<h2 class="fstu-tourtype-title"><?php esc_html_e( 'Довідник видів походів', 'fstu' ); ?></h2>

	<div id="fstu-tourtype-page-message" class="fstu-page-message fstu-hidden" aria-live="polite"></div>

	<div class="fstu-action-bar">
		<div class="fstu-tourtype-action-bar__left">
			<?php if ( $can_manage ) : ?>
				<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-tourtype-add-btn">
					<span class="fstu-btn__icon">➕</span>
					<?php esc_html_e( 'Додати запис', 'fstu' ); ?>
				</button>
			<?php endif; ?>
		</div>

		<div class="fstu-tourtype-action-bar__center" id="fstu-tourtype-filter-wrap">
			<label class="fstu-label" for="fstu-tourtype-category-filter"><?php esc_html_e( 'Категорія', 'fstu' ); ?></label>
			<select id="fstu-tourtype-category-filter" class="fstu-select fstu-select--compact fstu-tourtype-filter-select" aria-label="<?php esc_attr_e( 'Фільтр за категорією', 'fstu' ); ?>">
				<option value="0"><?php esc_html_e( 'Усі категорії', 'fstu' ); ?></option>
			</select>
		</div>

		<div class="fstu-tourtype-action-bar__right">
			<?php if ( $can_protocol ) : ?>
				<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-tourtype-protocol-btn">
					<span class="fstu-btn__icon">📋</span>
					<?php esc_html_e( 'ПРОТОКОЛ', 'fstu' ); ?>
				</button>
				<button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-tourtype-protocol-back-btn">
					<span class="fstu-btn__icon">↩</span>
					<?php esc_html_e( 'ДОВІДНИК', 'fstu' ); ?>
				</button>
			<?php endif; ?>
		</div>
	</div>

	<div id="fstu-tourtype-main">
		<?php include FSTU_PLUGIN_DIR . 'views/tourtype/table-list.php'; ?>

		<div class="fstu-pagination fstu-pagination--compact">
			<div class="fstu-pagination__left">
				<label class="fstu-pagination__per-page-label" for="fstu-tourtype-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
				<select id="fstu-tourtype-per-page" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Кількість записів на сторінці', 'fstu' ); ?>">
					<option value="10" selected>10</option>
					<option value="15">15</option>
					<option value="25">25</option>
					<option value="50">50</option>
				</select>
			</div>
			<div class="fstu-pagination__controls">
				<button type="button" class="fstu-btn--page" id="fstu-tourtype-prev-page">«</button>
				<div id="fstu-tourtype-pagination-pages"></div>
				<button type="button" class="fstu-btn--page" id="fstu-tourtype-next-page">»</button>
			</div>
			<div class="fstu-pagination__info">
				<span id="fstu-tourtype-pagination-info" aria-live="polite"></span>
			</div>
		</div>
	</div>

	<?php include FSTU_PLUGIN_DIR . 'views/tourtype/protocol-table-list.php'; ?>
</div>

<?php include FSTU_PLUGIN_DIR . 'views/tourtype/modal-view.php'; ?>
<?php include FSTU_PLUGIN_DIR . 'views/tourtype/modal-form.php'; ?>

