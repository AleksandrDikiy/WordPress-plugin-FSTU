<?php
/**
 * Основний шаблон модуля «Реєстратори».
 *
 * Version:     1.0.0
 * Date_update: 2026-04-11
 *
 * @package FSTU\Modules\UserFstu\Recorders
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$permissions    = isset( $permissions ) && is_array( $permissions ) ? $permissions : [];
$guest_mode     = ! empty( $guest_mode );
$no_access_mode = ! empty( $no_access_mode );
$guest_login_url = isset( $guest_login_url ) ? (string) $guest_login_url : wp_login_url();
$can_view       = ! empty( $permissions['canView'] );
$can_manage     = ! empty( $permissions['canManage'] );
$can_protocol   = ! empty( $permissions['canProtocol'] );
?>

<?php if ( ! $can_view ) : ?>
	<div class="fstu-recorders-wrap">
		<h2 class="fstu-recorders-title"><?php esc_html_e( 'Реєстратори', 'fstu' ); ?></h2>
		<div class="fstu-recorders-empty-state">
			<?php if ( $guest_mode ) : ?>
				<p><?php esc_html_e( 'Щоб працювати з модулем «Реєстратори», будь ласка, авторизуйтесь.', 'fstu' ); ?></p>
				<a class="fstu-btn fstu-btn--secondary" href="<?php echo esc_url( $guest_login_url ); ?>"><?php esc_html_e( 'Авторизуватись', 'fstu' ); ?></a>
			<?php elseif ( $no_access_mode ) : ?>
				<p><?php esc_html_e( 'У вас немає прав для перегляду цього модуля.', 'fstu' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
	<?php return; ?>
<?php endif; ?>

<div id="fstu-recorders" class="fstu-recorders-wrap">
	<h2 class="fstu-recorders-title"><?php esc_html_e( 'Реєстратори', 'fstu' ); ?></h2>

	<div class="fstu-action-bar">
		<div class="fstu-recorders-action-bar__actions">
			<?php if ( $can_manage ) : ?>
				<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-recorders-add-btn">
					<span class="fstu-btn__icon">➕</span>
					<?php esc_html_e( 'Додати запис', 'fstu' ); ?>
				</button>
			<?php endif; ?>

			<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-recorders-refresh-btn">
				<span class="fstu-btn__icon">🔄</span>
				<?php esc_html_e( 'Оновити', 'fstu' ); ?>
			</button>

			<?php if ( $can_protocol ) : ?>
				<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-recorders-protocol-btn">
					<span class="fstu-btn__icon">📋</span>
					<?php esc_html_e( 'ПРОТОКОЛ', 'fstu' ); ?>
				</button>
				<button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-recorders-protocol-back-btn">
					<span class="fstu-btn__icon">↩</span>
					<?php esc_html_e( 'ДОВІДНИК', 'fstu' ); ?>
				</button>
			<?php endif; ?>
		</div>

		<div class="fstu-recorders-action-bar__filters" id="fstu-recorders-filter-wrap">
			<label class="fstu-label fstu-label--compact" for="fstu-recorders-unit-filter"><?php esc_html_e( 'Осередок ФСТУ', 'fstu' ); ?></label>
			<select id="fstu-recorders-unit-filter" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Фільтр по осередку ФСТУ', 'fstu' ); ?>">
				<option value="0"><?php esc_html_e( 'Усі осередки', 'fstu' ); ?></option>
			</select>
		</div>
	</div>

	<div id="fstu-recorders-main">
		<?php include FSTU_PLUGIN_DIR . 'views/recorders/table-list.php'; ?>

		<div class="fstu-pagination fstu-pagination--compact">
			<div class="fstu-pagination__left">
				<label class="fstu-pagination__per-page-label" for="fstu-recorders-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
				<select id="fstu-recorders-per-page" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Кількість записів на сторінці', 'fstu' ); ?>">
					<option value="10" selected>10</option>
					<option value="15">15</option>
					<option value="25">25</option>
					<option value="50">50</option>
				</select>
			</div>
			<div class="fstu-pagination__controls">
				<button type="button" class="fstu-btn--page" id="fstu-recorders-prev-page">«</button>
				<div id="fstu-recorders-pagination-pages"></div>
				<button type="button" class="fstu-btn--page" id="fstu-recorders-next-page">»</button>
			</div>
			<div class="fstu-pagination__info">
				<span id="fstu-recorders-pagination-info" aria-live="polite"></span>
			</div>
		</div>
	</div>

	<?php include FSTU_PLUGIN_DIR . 'views/recorders/protocol-table-list.php'; ?>
</div>

<?php include FSTU_PLUGIN_DIR . 'views/recorders/modal-view.php'; ?>
<?php include FSTU_PLUGIN_DIR . 'views/recorders/modal-form.php'; ?>

