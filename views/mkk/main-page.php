<?php
/**
 * Основний шаблон модуля «Реєстр членів МКК ФСТУ».
 *
 * Version:     1.0.2
 * Date_update: 2026-04-12
 *
 * @package FSTU\Modules\UserFstu\MKK
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$permissions  = isset( $permissions ) && is_array( $permissions ) ? $permissions : [];
$can_manage   = ! empty( $permissions['canManage'] );
$can_protocol = ! empty( $permissions['canProtocol'] );
?>

<div id="fstu-mkk" class="fstu-mkk-wrap">
	<h2 class="fstu-mkk-title"><?php esc_html_e( 'Реєстр членів МКК ФСТУ', 'fstu' ); ?></h2>

	<div class="fstu-action-bar">
		<div class="fstu-mkk-action-bar__actions">
			<?php if ( $can_manage ) : ?>
				<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-mkk-add-btn">
					<span class="fstu-btn__icon">➕</span>
					<?php esc_html_e( 'Додати запис', 'fstu' ); ?>
				</button>
			<?php endif; ?>


			<?php if ( $can_protocol ) : ?>
				<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-mkk-protocol-btn">
					<span class="fstu-btn__icon">📋</span>
					<?php esc_html_e( 'ПРОТОКОЛ', 'fstu' ); ?>
				</button>
				<button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-mkk-protocol-back-btn">
					<span class="fstu-btn__icon">↩</span>
					<?php esc_html_e( 'ДОВІДНИК', 'fstu' ); ?>
				</button>
			<?php endif; ?>
		</div>

		<div class="fstu-mkk-action-bar__filters" id="fstu-mkk-filter-wrap">
			<label class="fstu-label fstu-label--compact" for="fstu-mkk-region-filter"><?php esc_html_e( 'Область', 'fstu' ); ?></label>
			<select id="fstu-mkk-region-filter" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Фільтр по області', 'fstu' ); ?>">
				<option value="0"><?php esc_html_e( 'Усі області', 'fstu' ); ?></option>
			</select>

			<label class="fstu-label fstu-label--compact" for="fstu-mkk-commission-type-filter"><?php esc_html_e( 'Тип', 'fstu' ); ?></label>
			<select id="fstu-mkk-commission-type-filter" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Фільтр по типу комісії', 'fstu' ); ?>">
				<option value="0"><?php esc_html_e( 'Усі типи', 'fstu' ); ?></option>
			</select>

			<label class="fstu-label fstu-label--compact" for="fstu-mkk-tourism-type-filter"><?php esc_html_e( 'Вид туризму', 'fstu' ); ?></label>
			<select id="fstu-mkk-tourism-type-filter" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Фільтр по виду туризму', 'fstu' ); ?>">
				<option value="0"><?php esc_html_e( 'Усі види', 'fstu' ); ?></option>
			</select>
		</div>
	</div>

	<div id="fstu-mkk-main">
		<?php include FSTU_PLUGIN_DIR . 'views/mkk/table-list.php'; ?>

		<div class="fstu-pagination fstu-pagination--compact">
			<div class="fstu-pagination__left">
				<label class="fstu-pagination__per-page-label" for="fstu-mkk-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
				<select id="fstu-mkk-per-page" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Кількість записів на сторінці', 'fstu' ); ?>">
					<option value="10" selected>10</option>
					<option value="15">15</option>
					<option value="25">25</option>
					<option value="50">50</option>
				</select>
			</div>
			<div class="fstu-pagination__controls">
				<button type="button" class="fstu-btn--page" id="fstu-mkk-prev-page">«</button>
				<div id="fstu-mkk-pagination-pages"></div>
				<button type="button" class="fstu-btn--page" id="fstu-mkk-next-page">»</button>
			</div>
			<div class="fstu-pagination__info">
				<span id="fstu-mkk-pagination-info" aria-live="polite"></span>
			</div>
		</div>
	</div>

	<?php if ( $can_protocol ) : ?>
		<?php include FSTU_PLUGIN_DIR . 'views/mkk/protocol-table-list.php'; ?>
	<?php endif; ?>
</div>

<?php include FSTU_PLUGIN_DIR . 'views/mkk/modal-view.php'; ?>
<?php include FSTU_PLUGIN_DIR . 'views/mkk/modal-form.php'; ?>

