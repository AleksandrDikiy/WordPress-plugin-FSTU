<?php
/**
 * Основний шаблон модуля "Довідник видів змагань ФСТУ".
 *
 * Version:     1.2.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\TypeEvent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$permissions = isset( $permissions ) && is_array( $permissions ) ? $permissions : [];
$can_add     = ! empty( $permissions['canManage'] );
$can_protocol = ! empty( $permissions['canDelete'] );
?>

<div id="fstu-typeevent" class="fstu-typeevent-wrap">
	<h2 class="fstu-typeevent-title"><?php esc_html_e( 'Довідник видів змагань ФСТУ', 'fstu' ); ?></h2>

	<div class="fstu-action-bar">
		<?php if ( $can_add ) : ?>
			<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-typeevent-add-btn" title="<?php esc_attr_e( 'Додати новий вид змагань', 'fstu' ); ?>">
				<span class="fstu-btn__icon">➕</span>
				<?php esc_html_e( 'Додати вид змагань', 'fstu' ); ?>
			</button>
		<?php endif; ?>

		<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-typeevent-refresh-btn" title="<?php esc_attr_e( 'Оновити список', 'fstu' ); ?>">
			<span class="fstu-btn__icon">🔄</span>
			<?php esc_html_e( 'Оновити', 'fstu' ); ?>
		</button>

		<?php if ( $can_protocol ) : ?>
			<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-typeevent-protocol-btn" title="<?php esc_attr_e( 'Переглянути протокол змін', 'fstu' ); ?>">
				<span class="fstu-btn__icon">📋</span>
				<?php esc_html_e( 'ПРОТОКОЛ', 'fstu' ); ?>
			</button>
			<button type="button" class="fstu-btn fstu-btn--secondary fstu-typeevent-hidden" id="fstu-typeevent-protocol-back-btn" title="<?php esc_attr_e( 'Повернутись до довідника', 'fstu' ); ?>">
				<span class="fstu-btn__icon">↩</span>
				<?php esc_html_e( 'ДОВІДНИК', 'fstu' ); ?>
			</button>
		<?php endif; ?>
	</div>

	<div id="fstu-typeevent-main">
		<?php include FSTU_PLUGIN_DIR . 'views/typeevent/table-list.php'; ?>

		<div class="fstu-pagination fstu-pagination--compact">
			<div class="fstu-pagination__left">
				<label class="fstu-pagination__per-page-label" for="fstu-typeevent-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
				<select id="fstu-typeevent-per-page" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Кількість записів на сторінці', 'fstu' ); ?>">
					<option value="10">10</option>
					<option value="15" selected>15</option>
					<option value="25">25</option>
					<option value="50">50</option>
				</select>
			</div>
			<div class="fstu-pagination__controls">
				<button type="button" class="fstu-btn--page" id="fstu-typeevent-prev-page" title="<?php esc_attr_e( 'Попередня сторінка', 'fstu' ); ?>">«</button>
				<div id="fstu-typeevent-pagination-pages"></div>
				<button type="button" class="fstu-btn--page" id="fstu-typeevent-next-page" title="<?php esc_attr_e( 'Наступна сторінка', 'fstu' ); ?>">»</button>
			</div>
			<div class="fstu-pagination__info">
				<span id="fstu-typeevent-pagination-info"></span>
			</div>
		</div>
	</div>

	<?php include FSTU_PLUGIN_DIR . 'views/typeevent/protocol-table-list.php'; ?>
</div>

<?php include FSTU_PLUGIN_DIR . 'views/typeevent/modal-view.php'; ?>
<?php include FSTU_PLUGIN_DIR . 'views/typeevent/modal-form.php'; ?>

