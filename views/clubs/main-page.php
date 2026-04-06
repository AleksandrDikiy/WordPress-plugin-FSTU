<?php
/**
 * View: Головний каркас модуля "Довідник клубів ФСТУ".
 * Тільки HTML-розмітка. Жодних запитів до БД.
 *
 * Version:     1.2.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Clubs\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user      = wp_get_current_user();
$roles     = (array) $user->roles;
$is_admin  = in_array( 'administrator', $roles, true ) || current_user_can( 'manage_options' );
$can_edit  = $is_admin || in_array( 'userregistrar', $roles, true );
$can_protocol = $is_admin;
?>

<div class="fstu-clubs-wrap" id="fstu-clubs">

	<h1 class="fstu-registry-title"><?php esc_html_e( 'Довідник клубів ФСТУ', 'fstu' ); ?></h1>

	<?php include __DIR__ . '/action-bar.php'; ?>

	<div id="fstu-clubs-main">
		<?php include __DIR__ . '/table-list.php'; ?>

		<!-- Пагінація з вибором per-page -->
		<div class="fstu-pagination fstu-pagination--compact" id="fstu-clubs-pagination">
			<div class="fstu-pagination__left">
				<label class="fstu-pagination__per-page-label" for="fstu-clubs-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
				<select id="fstu-clubs-per-page" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Кількість записів на сторінці', 'fstu' ); ?>">
					<option value="10" selected>10</option>
					<option value="15">15</option>
					<option value="25">25</option>
					<option value="50">50</option>
				</select>
			</div>
			<div class="fstu-pagination__controls">
				<button type="button" class="fstu-btn--page" id="fstu-clubs-first" aria-label="<?php esc_attr_e( 'Перша сторінка', 'fstu' ); ?>" disabled>«</button>
				<button type="button" class="fstu-btn--page" id="fstu-clubs-prev" aria-label="<?php esc_attr_e( 'Попередня сторінка', 'fstu' ); ?>" disabled>‹</button>
				<span class="fstu-pagination__pages" id="fstu-clubs-pages" role="group" aria-label="<?php esc_attr_e( 'Сторінки', 'fstu' ); ?>"></span>
				<button type="button" class="fstu-btn--page" id="fstu-clubs-next" aria-label="<?php esc_attr_e( 'Наступна сторінка', 'fstu' ); ?>">›</button>
				<button type="button" class="fstu-btn--page" id="fstu-clubs-last" aria-label="<?php esc_attr_e( 'Остання сторінка', 'fstu' ); ?>">»</button>
			</div>
			<div class="fstu-pagination__info" id="fstu-clubs-pag-info" aria-live="polite"></div>
		</div>
	</div>

	<?php include __DIR__ . '/protocol-table-list.php'; ?>

	<?php include __DIR__ . '/modal-view.php';  ?>
	<?php include __DIR__ . '/modal-form.php';  ?>

</div><!-- .fstu-clubs-wrap -->
