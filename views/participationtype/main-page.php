<?php
/**
 * Основний шаблон модуля "Довідник видів участі в заходах".
 *
 * Version:     1.0.1
 * Date_update: 2026-04-12
 *
 * @package FSTU\Dictionaries\ParticipationType
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$permissions     = isset( $permissions ) && is_array( $permissions ) ? $permissions : [];
$guest_mode      = ! empty( $guest_mode );
$no_access_mode  = ! empty( $no_access_mode );
$guest_login_url = isset( $guest_login_url ) ? (string) $guest_login_url : wp_login_url();
$can_view        = ! empty( $permissions['canView'] );
$can_manage      = ! empty( $permissions['canManage'] );
$can_protocol    = ! empty( $permissions['canProtocol'] );
?>

<?php if ( ! $can_view ) : ?>
	<div class="fstu-participationtype-wrap">
		<h2 class="fstu-participationtype-title"><?php esc_html_e( 'Довідник видів участі в заходах', 'fstu' ); ?></h2>
		<div class="fstu-participationtype-empty-state">
			<?php if ( $guest_mode ) : ?>
				<p><?php esc_html_e( 'Щоб працювати з модулем, будь ласка, авторизуйтесь.', 'fstu' ); ?></p>
				<a class="fstu-btn fstu-btn--secondary" href="<?php echo esc_url( $guest_login_url ); ?>"><?php esc_html_e( 'Авторизуватись', 'fstu' ); ?></a>
			<?php elseif ( $no_access_mode ) : ?>
				<p><?php esc_html_e( 'У вас немає прав для перегляду цього модуля.', 'fstu' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
	<?php return; ?>
<?php endif; ?>

<div id="fstu-participationtype" class="fstu-participationtype-wrap">
	<h2 class="fstu-participationtype-title"><?php esc_html_e( 'Довідник видів участі в заходах', 'fstu' ); ?></h2>

	<div id="fstu-participationtype-page-message" class="fstu-page-message fstu-hidden" aria-live="polite"></div>

	<div class="fstu-action-bar">
		<?php if ( $can_manage ) : ?>
			<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-participationtype-add-btn">
				<span class="fstu-btn__icon">➕</span>
				<?php esc_html_e( 'Додати запис', 'fstu' ); ?>
			</button>
		<?php endif; ?>


		<?php if ( $can_protocol ) : ?>
			<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-participationtype-protocol-btn">
				<span class="fstu-btn__icon">📋</span>
				<?php esc_html_e( 'ПРОТОКОЛ', 'fstu' ); ?>
			</button>
			<button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-participationtype-protocol-back-btn">
				<span class="fstu-btn__icon">↩</span>
				<?php esc_html_e( 'ДОВІДНИК', 'fstu' ); ?>
			</button>
		<?php endif; ?>
	</div>

	<div id="fstu-participationtype-main">
		<?php include FSTU_PLUGIN_DIR . 'views/participationtype/table-list.php'; ?>

		<div class="fstu-pagination fstu-pagination--compact">
			<div class="fstu-pagination__left">
				<label class="fstu-pagination__per-page-label" for="fstu-participationtype-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
				<select id="fstu-participationtype-per-page" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Кількість записів на сторінці', 'fstu' ); ?>">
					<option value="10" selected>10</option>
					<option value="15">15</option>
					<option value="25">25</option>
					<option value="50">50</option>
				</select>
			</div>
			<div class="fstu-pagination__controls">
				<button type="button" class="fstu-btn--page" id="fstu-participationtype-prev-page">«</button>
				<div id="fstu-participationtype-pagination-pages"></div>
				<button type="button" class="fstu-btn--page" id="fstu-participationtype-next-page">»</button>
			</div>
			<div class="fstu-pagination__info">
				<span id="fstu-participationtype-pagination-info" aria-live="polite"></span>
			</div>
		</div>
	</div>

	<?php include FSTU_PLUGIN_DIR . 'views/participationtype/protocol-table-list.php'; ?>
</div>

<?php include FSTU_PLUGIN_DIR . 'views/participationtype/modal-view.php'; ?>
<?php include FSTU_PLUGIN_DIR . 'views/participationtype/modal-form.php'; ?>

