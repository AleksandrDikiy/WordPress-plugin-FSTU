<?php
/**
 * Основний шаблон модуля "Довідник областей".
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\Region
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

<div id="fstu-region" class="fstu-region-wrap">
	<h2 class="fstu-region-title"><?php esc_html_e( 'Довідник областей', 'fstu' ); ?></h2>

	<?php if ( $guest_mode ) : ?>
		<div class="fstu-region-access-state fstu-region-access-state--guest">
			<div class="fstu-region-access-state__icon">🔐</div>
			<div class="fstu-region-access-state__content">
				<h3 class="fstu-region-access-state__title"><?php esc_html_e( 'Щоб працювати з довідником областей, будь ласка, авторизуйтесь.', 'fstu' ); ?></h3>
				<p class="fstu-region-access-state__text"><?php esc_html_e( 'Доступ до довідника областей надається авторизованим користувачам з відповідними правами ФСТУ.', 'fstu' ); ?></p>
				<div class="fstu-region-access-state__actions">
					<a class="fstu-btn fstu-btn--primary" href="<?php echo esc_url( $guest_login_url ); ?>"><?php esc_html_e( 'УВІЙТИ', 'fstu' ); ?></a>
				</div>
			</div>
		</div>
		<?php return; ?>
	<?php endif; ?>

	<?php if ( $no_access_mode ) : ?>
		<div class="fstu-region-access-state fstu-region-access-state--deny">
			<div class="fstu-region-access-state__icon">⛔</div>
			<div class="fstu-region-access-state__content">
				<h3 class="fstu-region-access-state__title"><?php esc_html_e( 'Немає доступу до довідника областей.', 'fstu' ); ?></h3>
				<p class="fstu-region-access-state__text"><?php esc_html_e( 'Ваш обліковий запис не має необхідних прав для перегляду або керування цим модулем.', 'fstu' ); ?></p>
			</div>
		</div>
		<?php return; ?>
	<?php endif; ?>

	<div class="fstu-action-bar">
		<?php if ( $can_manage ) : ?>
			<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-region-add-btn">
				<span class="fstu-btn__icon">➕</span>
				<?php esc_html_e( 'ДОДАТИ', 'fstu' ); ?>
			</button>
		<?php endif; ?>

		<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-region-refresh-btn">
			<span class="fstu-btn__icon">🔄</span>
			<?php esc_html_e( 'Оновити', 'fstu' ); ?>
		</button>

		<?php if ( $can_protocol ) : ?>
			<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-region-protocol-btn">
				<span class="fstu-btn__icon">📋</span>
				<?php esc_html_e( 'ПРОТОКОЛ', 'fstu' ); ?>
			</button>
			<button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-region-protocol-back-btn">
				<span class="fstu-btn__icon">↩</span>
				<?php esc_html_e( 'ДОВІДНИК', 'fstu' ); ?>
			</button>
		<?php endif; ?>
	</div>

	<div id="fstu-region-main">
		<?php include FSTU_PLUGIN_DIR . 'views/region/table-list.php'; ?>

		<div class="fstu-pagination fstu-pagination--compact">
			<div class="fstu-pagination__left">
				<label class="fstu-pagination__per-page-label" for="fstu-region-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
				<select id="fstu-region-per-page" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Кількість записів на сторінці', 'fstu' ); ?>">
					<option value="10" selected>10</option>
					<option value="15">15</option>
					<option value="25">25</option>
					<option value="50">50</option>
				</select>
			</div>
			<div class="fstu-pagination__controls">
				<button type="button" class="fstu-btn--page" id="fstu-region-prev-page">«</button>
				<div id="fstu-region-pagination-pages"></div>
				<button type="button" class="fstu-btn--page" id="fstu-region-next-page">»</button>
			</div>
			<div class="fstu-pagination__info">
				<span id="fstu-region-pagination-info" aria-live="polite"></span>
			</div>
		</div>
	</div>

	<?php if ( $can_protocol ) : ?>
		<?php include FSTU_PLUGIN_DIR . 'views/region/protocol-table-list.php'; ?>
	<?php endif; ?>
</div>

<?php include FSTU_PLUGIN_DIR . 'views/region/modal-form.php'; ?>

