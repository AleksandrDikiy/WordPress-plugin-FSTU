<?php
/**
 * Основний шаблон модуля "Довідник міст".
 *
 * Version:     1.1.0
 * Date_update: 2026-04-07
 *
 * @package FSTU\Dictionaries\City
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$permissions     = isset( $permissions ) && is_array( $permissions ) ? $permissions : [];
$guest_mode      = ! empty( $guest_mode );
$no_access_mode  = ! empty( $no_access_mode );
$guest_login_url = isset( $guest_login_url ) ? (string) $guest_login_url : wp_login_url( home_url( '/' ) );
$can_manage      = ! empty( $permissions['canManage'] );
$can_protocol    = ! empty( $permissions['canProtocol'] );
?>

<div id="fstu-city" class="fstu-city-wrap">
	<h2 class="fstu-country-title"><?php esc_html_e( 'Довідник міст', 'fstu' ); ?></h2>

	<?php if ( $guest_mode ) : ?>
		<div class="fstu-city-access-state fstu-country-access-state--guest">
			<div class="fstu-country-access-state__icon">🔐</div>
			<div class="fstu-country-access-state__content">
				<h3 class="fstu-country-access-state__title"><?php esc_html_e( 'Щоб працювати з довідником міст, будь ласка, авторизуйтесь.', 'fstu' ); ?></h3>
				<div class="fstu-country-access-state__actions">
					<a class="fstu-btn fstu-btn--primary" href="<?php echo esc_url( $guest_login_url ); ?>"><?php esc_html_e( 'УВІЙТИ', 'fstu' ); ?></a>
				</div>
			</div>
		</div>
		<?php return; ?>
	<?php endif; ?>

	<?php if ( $no_access_mode ) : ?>
		<div class="fstu-city-access-state fstu-country-access-state--deny">
			<div class="fstu-country-access-state__icon">⛔</div>
			<div class="fstu-country-access-state__content">
				<h3 class="fstu-country-access-state__title"><?php esc_html_e( 'Немає доступу до довідника міст.', 'fstu' ); ?></h3>
			</div>
		</div>
		<?php return; ?>
	<?php endif; ?>

	<div class="fstu-action-bar">
		<?php if ( $can_manage ) : ?>
			<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-city-add-btn"><?php esc_html_e( 'ДОДАТИ', 'fstu' ); ?></button>
		<?php endif; ?>
		<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-city-refresh-btn"><?php esc_html_e( 'Оновити', 'fstu' ); ?></button>
		<div class="fstu-city-filter-wrap">
			<label class="fstu-city-filter-label" for="fstu-city-region-filter"><?php esc_html_e( 'Фільтр:', 'fstu' ); ?></label>
			<select id="fstu-city-region-filter" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Вибір області', 'fstu' ); ?>">
				<option value="0"><?php esc_html_e( 'Всі області', 'fstu' ); ?></option>
			</select>
		</div>
		<?php if ( $can_protocol ) : ?>
			<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-city-protocol-btn"><?php esc_html_e( 'ПРОТОКОЛ', 'fstu' ); ?></button>
			<button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-city-protocol-back-btn"><?php esc_html_e( 'ДОВІДНИК', 'fstu' ); ?></button>
		<?php endif; ?>
	</div>

	<div id="fstu-city-main">
		<?php include FSTU_PLUGIN_DIR . 'views/city/table-list.php'; ?>
		<div class="fstu-pagination fstu-pagination--compact">
			<div class="fstu-pagination__left">
				<label class="fstu-pagination__per-page-label" for="fstu-city-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
				<select id="fstu-city-per-page" class="fstu-select fstu-select--compact">
					<option value="10" selected>10</option>
					<option value="15">15</option>
					<option value="25">25</option>
					<option value="50">50</option>
				</select>
			</div>
			<div class="fstu-pagination__controls">
				<button type="button" class="fstu-btn--page" id="fstu-city-prev-page">«</button>
				<div id="fstu-city-pagination-pages"></div>
				<button type="button" class="fstu-btn--page" id="fstu-city-next-page">»</button>
			</div>
			<div class="fstu-pagination__info"><span id="fstu-city-pagination-info"></span></div>
		</div>
	</div>

	<?php if ( $can_protocol ) : ?>
		<?php include FSTU_PLUGIN_DIR . 'views/city/protocol-table-list.php'; ?>
	<?php endif; ?>
</div>

<?php include FSTU_PLUGIN_DIR . 'views/city/modal-form.php'; ?>

