<?php
/**
 * Основний шаблон модуля "Судновий реєстр ФСТУ".
 *
 * Version:     1.4.0
 * Date_update: 2026-04-09
 *
 * @package FSTU\Modules\Registry\Sailboats
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$permissions     = isset( $permissions ) && is_array( $permissions ) ? $permissions : [];
$guest_mode      = ! empty( $guest_mode );
$no_access_mode  = ! empty( $no_access_mode );
$guest_login_url = isset( $guest_login_url ) ? (string) $guest_login_url : wp_login_url( home_url( '/' ) );
$can_submit      = ! empty( $permissions['canSubmit'] );
$can_manage      = ! empty( $permissions['canManage'] );
$can_protocol    = ! empty( $permissions['canProtocol'] );
$bootstrap_notice = isset( $bootstrap_notice ) ? (string) $bootstrap_notice : '';
$bootstrap_return_url = isset( $bootstrap_return_url ) ? (string) $bootstrap_return_url : '';
?>

<div id="fstu-sailboats" class="fstu-sailboats-wrap">
	<h2 class="fstu-sailboats-title"><?php esc_html_e( 'Судновий реєстр ФСТУ', 'fstu' ); ?></h2>
	<?php if ( '' !== $bootstrap_notice ) : ?>
		<div class="fstu-page-notice fstu-page-notice--success" aria-live="polite"><?php echo esc_html( $bootstrap_notice ); ?></div>
	<?php endif; ?>

	<?php if ( $guest_mode ) : ?>
		<div class="fstu-sailboats-access-state fstu-sailboats-access-state--guest">
			<div class="fstu-sailboats-access-state__icon">🔐</div>
			<div class="fstu-sailboats-access-state__content">
				<h3 class="fstu-sailboats-access-state__title"><?php esc_html_e( 'Щоб працювати з реєстром суден, будь ласка, авторизуйтесь.', 'fstu' ); ?></h3>
				<p class="fstu-sailboats-access-state__text"><?php esc_html_e( 'Подання заявки на судновий квиток та службові дії доступні лише авторизованим членам ФСТУ та адміністраторам.', 'fstu' ); ?></p>
				<div class="fstu-sailboats-access-state__actions">
					<a class="fstu-btn fstu-btn--primary" href="<?php echo esc_url( $guest_login_url ); ?>"><?php esc_html_e( 'УВІЙТИ', 'fstu' ); ?></a>
				</div>
			</div>
		</div>
		<?php return; ?>
	<?php endif; ?>

	<?php if ( $no_access_mode ) : ?>
		<div class="fstu-sailboats-access-state fstu-sailboats-access-state--deny">
			<div class="fstu-sailboats-access-state__icon">⛔</div>
			<div class="fstu-sailboats-access-state__content">
				<h3 class="fstu-sailboats-access-state__title"><?php esc_html_e( 'Немає доступу до реєстру суден.', 'fstu' ); ?></h3>
				<p class="fstu-sailboats-access-state__text"><?php esc_html_e( 'Ваш обліковий запис не має необхідних прав для перегляду або керування цим модулем.', 'fstu' ); ?></p>
			</div>
		</div>
		<?php return; ?>
	<?php endif; ?>

	<div class="fstu-action-bar fstu-action-bar--sailboats">
		<div class="fstu-action-bar__group fstu-action-bar__group--buttons">
			<?php if ( '' !== $bootstrap_return_url ) : ?>
				<a class="fstu-btn fstu-btn--secondary" href="<?php echo esc_url( $bootstrap_return_url ); ?>">
					<span class="fstu-btn__icon">↩</span>
					<?php esc_html_e( 'ПОВЕРНУТИСЬ ДО КАБІНЕТУ', 'fstu' ); ?>
				</a>
			<?php endif; ?>
			<?php if ( $can_manage || $can_submit ) : ?>
				<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-sailboats-add-btn">
					<span class="fstu-btn__icon">➕</span>
					<?php esc_html_e( 'ДОДАТИ', 'fstu' ); ?>
				</button>
			<?php endif; ?>

			<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-sailboats-refresh-btn">
				<span class="fstu-btn__icon">🔄</span>
				<?php esc_html_e( 'ОНОВИТИ', 'fstu' ); ?>
			</button>
		</div>

		<div class="fstu-action-bar__group fstu-action-bar__group--filters">
			<select id="fstu-sailboats-region-filter" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Фільтр по області', 'fstu' ); ?>">
				<option value="0"><?php esc_html_e( 'Усі області', 'fstu' ); ?></option>
			</select>

			<select id="fstu-sailboats-status-filter" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Фільтр по статусу', 'fstu' ); ?>">
				<option value="0"><?php esc_html_e( 'Усі статуси', 'fstu' ); ?></option>
			</select>
		</div>

		<?php if ( $can_protocol ) : ?>
			<div class="fstu-action-bar__group fstu-action-bar__group--protocol">
				<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-sailboats-protocol-btn">
					<span class="fstu-btn__icon">📋</span>
					<?php esc_html_e( 'ПРОТОКОЛ', 'fstu' ); ?>
				</button>
				<button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-sailboats-protocol-back-btn">
					<span class="fstu-btn__icon">↩</span>
					<?php esc_html_e( 'ДОВІДНИК', 'fstu' ); ?>
				</button>
			</div>
		<?php endif; ?>
	</div>

	<div id="fstu-sailboats-main">
		<?php include FSTU_PLUGIN_DIR . 'views/sailboats/table-list.php'; ?>

		<div class="fstu-pagination fstu-pagination--compact">
			<div class="fstu-pagination__left">
				<label class="fstu-pagination__per-page-label" for="fstu-sailboats-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
				<select id="fstu-sailboats-per-page" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Кількість записів на сторінці', 'fstu' ); ?>">
					<option value="10" selected>10</option>
					<option value="15">15</option>
					<option value="25">25</option>
					<option value="50">50</option>
				</select>
			</div>
			<div class="fstu-pagination__controls">
				<button type="button" class="fstu-btn--page" id="fstu-sailboats-prev-page">«</button>
				<div id="fstu-sailboats-pagination-pages"></div>
				<button type="button" class="fstu-btn--page" id="fstu-sailboats-next-page">»</button>
			</div>
			<div class="fstu-pagination__info">
				<span id="fstu-sailboats-pagination-info" aria-live="polite"></span>
			</div>
		</div>
	</div>

	<?php if ( $can_protocol ) : ?>
		<?php include FSTU_PLUGIN_DIR . 'views/sailboats/protocol-table-list.php'; ?>
	<?php endif; ?>
</div>

<?php include FSTU_PLUGIN_DIR . 'views/sailboats/modal-view.php'; ?>
<?php include FSTU_PLUGIN_DIR . 'views/sailboats/modal-form.php'; ?>
<?php include FSTU_PLUGIN_DIR . 'views/sailboats/modal-status.php'; ?>
<?php include FSTU_PLUGIN_DIR . 'views/sailboats/modal-payment.php'; ?>
<?php include FSTU_PLUGIN_DIR . 'views/sailboats/modal-received.php'; ?>
<?php include FSTU_PLUGIN_DIR . 'views/sailboats/modal-sale.php'; ?>
<?php include FSTU_PLUGIN_DIR . 'views/sailboats/modal-notification.php'; ?>

