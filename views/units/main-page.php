<?php
/**
 * Основний шаблон модуля "Довідник осередків ФСТУ".
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\Units
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$permissions = isset( $permissions ) && is_array( $permissions ) ? $permissions : [];
$can_add     = ! empty( $permissions['canManage'] );
?>

<div id="fstu-units" class="fstu-units-wrap">

	<!-- Заголовок та опис -->
	<h2 class="fstu-units-title"><?php esc_html_e( 'Довідник осередків ФСТУ', 'fstu' ); ?></h2>

	<!-- Панель дій (Додати, Оновити) -->
	<div class="fstu-action-bar">
		<?php if ( $can_add ) : ?>
			<button
				type="button"
				class="fstu-btn fstu-btn--secondary"
				id="fstu-units-add-btn"
				title="<?php esc_attr_e( 'Додати новий осередок', 'fstu' ); ?>"
			>
				<span class="fstu-btn__icon">➕</span>
				<?php esc_html_e( 'Додати осередок', 'fstu' ); ?>
			</button>
		<?php endif; ?>

		<button
			type="button"
			class="fstu-btn fstu-btn--secondary"
			id="fstu-units-refresh-btn"
			title="<?php esc_attr_e( 'Оновити список', 'fstu' ); ?>"
		>
			<span class="fstu-btn__icon">🔄</span>
			<?php esc_html_e( 'Оновити', 'fstu' ); ?>
		</button>
	</div>

	<!-- Панель фільтрів -->
	<?php include FSTU_PLUGIN_DIR . 'views/units/filter-bar.php'; ?>

	<!-- Таблиця -->
	<?php include FSTU_PLUGIN_DIR . 'views/units/table-list.php'; ?>

	<!-- Пагінація -->
	<div class="fstu-pagination">
		<div class="fstu-pagination__info">
			<span id="fstu-units-pagination-info"></span>
		</div>
		<div class="fstu-pagination__controls">
			<button type="button" class="fstu-btn--page" id="fstu-units-prev-page" title="<?php esc_attr_e( 'Попередня сторінка', 'fstu' ); ?>">
				«
			</button>
			<div id="fstu-units-pagination-pages"></div>
			<button type="button" class="fstu-btn--page" id="fstu-units-next-page" title="<?php esc_attr_e( 'Наступна сторінка', 'fstu' ); ?>">
				»
			</button>
		</div>
	</div>

</div>

<!-- Модальні вікна -->
<?php include FSTU_PLUGIN_DIR . 'views/units/modal-view.php'; ?>
<?php include FSTU_PLUGIN_DIR . 'views/units/modal-form.php'; ?>

