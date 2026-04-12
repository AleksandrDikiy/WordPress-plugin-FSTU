<?php
/**
 * Основний шаблон модуля «Склад керівних органів ФСТУ».
 *
 * Version:     1.0.1
 * Date_update: 2026-04-12
 *
 * @package FSTU\Modules\Registry\Guidance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$permissions  = isset( $permissions ) && is_array( $permissions ) ? $permissions : [];
$can_manage   = ! empty( $permissions['canManage'] );
$can_protocol = ! empty( $permissions['canProtocol'] );
?>

<div id="fstu-guidance" class="fstu-guidance-wrap">
	<h2 class="fstu-guidance-title"><?php esc_html_e( 'Склад керівних органів ФСТУ', 'fstu' ); ?></h2>
	<div id="fstu-guidance-page-message" class="fstu-page-message fstu-hidden" aria-live="polite"></div>

	<div class="fstu-action-bar">
		<div class="fstu-guidance-action-bar__actions">
			<?php if ( $can_manage ) : ?>
				<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-guidance-add-btn">
					<span class="fstu-btn__icon">➕</span>
					<?php esc_html_e( 'Додати запис', 'fstu' ); ?>
				</button>
			<?php endif; ?>

			<?php if ( $can_protocol ) : ?>
				<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-guidance-protocol-btn">
					<span class="fstu-btn__icon">📋</span>
					<?php esc_html_e( 'ПРОТОКОЛ', 'fstu' ); ?>
				</button>
				<button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-guidance-protocol-back-btn">
					<span class="fstu-btn__icon">↩</span>
					<?php esc_html_e( 'ДОВІДНИК', 'fstu' ); ?>
				</button>
			<?php endif; ?>
		</div>

		<div class="fstu-guidance-action-bar__filters" id="fstu-guidance-filter-wrap">
			<label class="fstu-label fstu-label--compact" for="fstu-guidance-typeguidance-filter"><?php esc_html_e( 'Керівний орган', 'fstu' ); ?></label>
			<select id="fstu-guidance-typeguidance-filter" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Фільтр по керівному органу', 'fstu' ); ?>">
				<option value="1"><?php esc_html_e( 'Виконком', 'fstu' ); ?></option>
			</select>
		</div>
	</div>

	<div id="fstu-guidance-main">
		<?php include FSTU_PLUGIN_DIR . 'views/guidance/table-list.php'; ?>

		<div class="fstu-pagination fstu-pagination--compact">
			<div class="fstu-pagination__left">
				<label class="fstu-pagination__per-page-label" for="fstu-guidance-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
				<select id="fstu-guidance-per-page" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Кількість записів на сторінці', 'fstu' ); ?>">
					<option value="10" selected>10</option>
					<option value="15">15</option>
					<option value="25">25</option>
					<option value="50">50</option>
				</select>
			</div>
			<div class="fstu-pagination__controls">
				<button type="button" class="fstu-btn--page" id="fstu-guidance-prev-page">«</button>
				<div id="fstu-guidance-pagination-pages"></div>
				<button type="button" class="fstu-btn--page" id="fstu-guidance-next-page">»</button>
			</div>
			<div class="fstu-pagination__info">
				<span id="fstu-guidance-pagination-info" aria-live="polite"></span>
			</div>
		</div>
	</div>

	<?php if ( $can_protocol ) : ?>
		<?php include FSTU_PLUGIN_DIR . 'views/guidance/protocol-table-list.php'; ?>
	<?php endif; ?>
</div>

<?php include FSTU_PLUGIN_DIR . 'views/guidance/modal-view.php'; ?>
<?php include FSTU_PLUGIN_DIR . 'views/guidance/modal-form.php'; ?>

