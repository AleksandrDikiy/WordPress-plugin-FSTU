<?php
/**
 * Основний шаблон модуля "Довідник комісій та колегій ФСТУ".
 *
 * Version:     1.0.1
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\Commission
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$permissions  = isset( $permissions ) && is_array( $permissions ) ? $permissions : [];
$can_manage   = ! empty( $permissions['canManage'] );
$can_protocol = ! empty( $permissions['canProtocol'] );
?>

<div id="fstu-commission" class="fstu-commission-wrap">
	<h2 class="fstu-commission-title"><?php esc_html_e( 'Довідник комісій та колегій ФСТУ', 'fstu' ); ?></h2>

	<div class="fstu-action-bar">
		<?php if ( $can_manage ) : ?>
			<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-commission-add-btn">
				<span class="fstu-btn__icon">➕</span>
				<?php esc_html_e( 'Додати запис', 'fstu' ); ?>
			</button>
		<?php endif; ?>

		<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-commission-refresh-btn">
			<span class="fstu-btn__icon">🔄</span>
			<?php esc_html_e( 'Оновити', 'fstu' ); ?>
		</button>

		<?php if ( $can_protocol ) : ?>
			<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-commission-protocol-btn">
				<span class="fstu-btn__icon">📋</span>
				<?php esc_html_e( 'ПРОТОКОЛ', 'fstu' ); ?>
			</button>
			<button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-commission-protocol-back-btn">
				<span class="fstu-btn__icon">↩</span>
				<?php esc_html_e( 'ДОВІДНИК', 'fstu' ); ?>
			</button>
		<?php endif; ?>
	</div>

	<div id="fstu-commission-main">
		<?php include FSTU_PLUGIN_DIR . 'views/commission/filter-bar.php'; ?>
		<?php include FSTU_PLUGIN_DIR . 'views/commission/table-list.php'; ?>

		<div class="fstu-pagination fstu-pagination--compact">
			<div class="fstu-pagination__left">
				<label class="fstu-pagination__per-page-label" for="fstu-commission-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
				<select id="fstu-commission-per-page" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Кількість записів на сторінці', 'fstu' ); ?>">
					<option value="10" selected>10</option>
					<option value="15">15</option>
					<option value="25">25</option>
					<option value="50">50</option>
				</select>
			</div>
			<div class="fstu-pagination__controls">
				<button type="button" class="fstu-btn--page" id="fstu-commission-prev-page">«</button>
				<div id="fstu-commission-pagination-pages"></div>
				<button type="button" class="fstu-btn--page" id="fstu-commission-next-page">»</button>
			</div>
			<div class="fstu-pagination__info">
				<span id="fstu-commission-pagination-info" aria-live="polite"></span>
			</div>
		</div>
	</div>

	<?php include FSTU_PLUGIN_DIR . 'views/commission/protocol-table-list.php'; ?>
</div>

<?php include FSTU_PLUGIN_DIR . 'views/commission/modal-view.php'; ?>
<?php include FSTU_PLUGIN_DIR . 'views/commission/modal-form.php'; ?>

