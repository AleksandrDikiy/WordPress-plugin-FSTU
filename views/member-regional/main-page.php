<?php
/**
 * Основний шаблон модуля "Довідник посад федерацій".
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\MemberRegional
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$permissions     = isset( $permissions ) && is_array( $permissions ) ? $permissions : [];
$guest_mode      = ! empty( $guest_mode );
$guest_login_url = isset( $guest_login_url ) ? (string) $guest_login_url : wp_login_url( home_url( '/' ) );
$can_manage      = ! empty( $permissions['canManage'] );
$can_protocol    = ! empty( $permissions['canProtocol'] );
?>

<div id="fstu-member-regional" class="fstu-member-regional-wrap">
	<h2 class="fstu-member-regional-title"><?php esc_html_e( 'Довідник посад федерацій', 'fstu' ); ?></h2>

	<?php if ( $guest_mode ) : ?>
		<div class="fstu-member-regional-login-state">
			<div class="fstu-member-regional-login-state__icon">🔐</div>
			<div class="fstu-member-regional-login-state__content">
				<h3 class="fstu-member-regional-login-state__title"><?php esc_html_e( 'Щоб бачити додаткові функції, будь ласка, авторизуйтесь.', 'fstu' ); ?></h3>
				<p class="fstu-member-regional-login-state__text"><?php esc_html_e( 'Доступ до довідника посад федерацій надається лише адміністраторам та реєстраторам ФСТУ.', 'fstu' ); ?></p>
				<div class="fstu-member-regional-login-state__actions">
					<a class="fstu-btn fstu-btn--primary" href="<?php echo esc_url( $guest_login_url ); ?>"><?php esc_html_e( 'УВІЙТИ', 'fstu' ); ?></a>
				</div>
			</div>
		</div>
		<?php return; ?>
	<?php endif; ?>

	<div class="fstu-action-bar">
		<?php if ( $can_manage ) : ?>
			<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-member-regional-add-btn">
				<span class="fstu-btn__icon">➕</span>
				<?php esc_html_e( 'Додати запис', 'fstu' ); ?>
			</button>
		<?php endif; ?>

		<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-member-regional-refresh-btn">
			<span class="fstu-btn__icon">🔄</span>
			<?php esc_html_e( 'Оновити', 'fstu' ); ?>
		</button>

		<?php if ( $can_protocol ) : ?>
			<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-member-regional-protocol-btn">
				<span class="fstu-btn__icon">📋</span>
				<?php esc_html_e( 'ПРОТОКОЛ', 'fstu' ); ?>
			</button>
			<button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-member-regional-protocol-back-btn">
				<span class="fstu-btn__icon">↩</span>
				<?php esc_html_e( 'ДОВІДНИК', 'fstu' ); ?>
			</button>
		<?php endif; ?>
	</div>

	<div id="fstu-member-regional-main">
		<?php include FSTU_PLUGIN_DIR . 'views/member-regional/filter-bar.php'; ?>
		<?php include FSTU_PLUGIN_DIR . 'views/member-regional/table-list.php'; ?>

		<div class="fstu-pagination fstu-pagination--compact">
			<div class="fstu-pagination__left">
				<label class="fstu-pagination__per-page-label" for="fstu-member-regional-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
				<select id="fstu-member-regional-per-page" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Кількість записів на сторінці', 'fstu' ); ?>">
					<option value="10" selected>10</option>
					<option value="15">15</option>
					<option value="25">25</option>
					<option value="50">50</option>
				</select>
			</div>
			<div class="fstu-pagination__controls">
				<button type="button" class="fstu-btn--page" id="fstu-member-regional-prev-page">«</button>
				<div id="fstu-member-regional-pagination-pages"></div>
				<button type="button" class="fstu-btn--page" id="fstu-member-regional-next-page">»</button>
			</div>
			<div class="fstu-pagination__info">
				<span id="fstu-member-regional-pagination-info" aria-live="polite"></span>
			</div>
		</div>
	</div>

	<?php if ( $can_protocol ) : ?>
		<?php include FSTU_PLUGIN_DIR . 'views/member-regional/protocol-table-list.php'; ?>
	<?php endif; ?>
</div>

<?php include FSTU_PLUGIN_DIR . 'views/member-regional/modal-form.php'; ?>

