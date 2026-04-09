<?php
/**
 * Основний шаблон модуля «Реєстр суддів ФСТУ».
 *
 * Version:     1.1.0
 * Date_update: 2026-04-09
 *
 * @package FSTU\Modules\Registry\Referees
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
$bootstrap_notice = isset( $bootstrap_notice ) ? (string) $bootstrap_notice : '';
$bootstrap_return_url = isset( $bootstrap_return_url ) ? (string) $bootstrap_return_url : '';
?>

<div id="fstu-referees" class="fstu-referees-wrap">
	<h2 class="fstu-referees-title"><?php esc_html_e( 'Реєстр суддів ФСТУ', 'fstu' ); ?></h2>
	<div id="fstu-referees-notice" class="fstu-page-notice fstu-hidden" aria-live="polite"></div>
	<?php if ( '' !== $bootstrap_notice ) : ?>
		<div class="fstu-page-notice fstu-page-notice--success" aria-live="polite"><?php echo esc_html( $bootstrap_notice ); ?></div>
	<?php endif; ?>

	<?php if ( $guest_mode ) : ?>
		<div class="fstu-referees-access-state fstu-referees-access-state--guest">
			<div class="fstu-referees-access-state__icon">🔐</div>
			<div class="fstu-referees-access-state__content">
				<h3 class="fstu-referees-access-state__title"><?php esc_html_e( 'Щоб працювати з реєстром суддів, будь ласка, авторизуйтесь.', 'fstu' ); ?></h3>
				<p class="fstu-referees-access-state__text"><?php esc_html_e( 'Перегляд і службові дії модуля доступні лише авторизованим користувачам із правами ФСТУ.', 'fstu' ); ?></p>
				<div class="fstu-referees-access-state__actions">
					<a class="fstu-btn fstu-btn--primary" href="<?php echo esc_url( $guest_login_url ); ?>"><?php esc_html_e( 'УВІЙТИ', 'fstu' ); ?></a>
				</div>
			</div>
		</div>
		<?php return; ?>
	<?php endif; ?>

	<?php if ( $no_access_mode ) : ?>
		<div class="fstu-referees-access-state fstu-referees-access-state--deny">
			<div class="fstu-referees-access-state__icon">⛔</div>
			<div class="fstu-referees-access-state__content">
				<h3 class="fstu-referees-access-state__title"><?php esc_html_e( 'Немає доступу до реєстру суддів.', 'fstu' ); ?></h3>
				<p class="fstu-referees-access-state__text"><?php esc_html_e( 'Ваш обліковий запис не має необхідних прав для перегляду цього модуля.', 'fstu' ); ?></p>
			</div>
		</div>
		<?php return; ?>
	<?php endif; ?>

	<div class="fstu-action-bar fstu-action-bar--referees">
		<div class="fstu-action-bar__group fstu-action-bar__group--buttons">
			<?php if ( '' !== $bootstrap_return_url ) : ?>
				<a class="fstu-btn fstu-btn--secondary" href="<?php echo esc_url( $bootstrap_return_url ); ?>">
					<span class="fstu-btn__icon">↩</span>
					<?php esc_html_e( 'ПОВЕРНУТИСЬ ДО КАБІНЕТУ', 'fstu' ); ?>
				</a>
			<?php endif; ?>
			<?php if ( $can_manage ) : ?>
				<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-referees-add-btn">
					<span class="fstu-btn__icon">➕</span>
					<?php esc_html_e( 'ДОДАТИ', 'fstu' ); ?>
				</button>
			<?php endif; ?>

			<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-referees-refresh-btn">
				<span class="fstu-btn__icon">🔄</span>
				<?php esc_html_e( 'ОНОВИТИ', 'fstu' ); ?>
			</button>
		</div>

		<div class="fstu-action-bar__group fstu-action-bar__group--filters">
			<select id="fstu-referees-region-filter" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Фільтр по області', 'fstu' ); ?>">
				<option value="0"><?php esc_html_e( 'Усі області', 'fstu' ); ?></option>
			</select>
			<select id="fstu-referees-category-filter" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Фільтр по категорії', 'fstu' ); ?>">
				<option value="0"><?php esc_html_e( 'Усі категорії', 'fstu' ); ?></option>
			</select>
		</div>

		<?php if ( $can_protocol ) : ?>
			<div class="fstu-action-bar__group fstu-action-bar__group--protocol">
				<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-referees-protocol-btn">
					<span class="fstu-btn__icon">📋</span>
					<?php esc_html_e( 'ПРОТОКОЛ', 'fstu' ); ?>
				</button>
				<button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-referees-protocol-back-btn">
					<span class="fstu-btn__icon">↩</span>
					<?php esc_html_e( 'ДОВІДНИК', 'fstu' ); ?>
				</button>
			</div>
		<?php endif; ?>
	</div>

	<div id="fstu-referees-main">
		<?php include FSTU_PLUGIN_DIR . 'views/referees/table-list.php'; ?>

		<div class="fstu-pagination fstu-pagination--compact">
			<div class="fstu-pagination__left">
				<label class="fstu-pagination__per-page-label" for="fstu-referees-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
				<select id="fstu-referees-per-page" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Кількість записів на сторінці', 'fstu' ); ?>">
					<option value="10" selected>10</option>
					<option value="15">15</option>
					<option value="25">25</option>
					<option value="50">50</option>
				</select>
			</div>
			<div class="fstu-pagination__controls">
				<button type="button" class="fstu-btn--page" id="fstu-referees-prev-page">«</button>
				<div id="fstu-referees-pagination-pages"></div>
				<button type="button" class="fstu-btn--page" id="fstu-referees-next-page">»</button>
			</div>
			<div class="fstu-pagination__info">
				<span id="fstu-referees-pagination-info" aria-live="polite"></span>
			</div>
		</div>
	</div>

	<?php if ( $can_protocol ) : ?>
		<?php include FSTU_PLUGIN_DIR . 'views/referees/protocol-table-list.php'; ?>
	<?php endif; ?>
</div>

<?php include FSTU_PLUGIN_DIR . 'views/referees/modal-view.php'; ?>
<?php include FSTU_PLUGIN_DIR . 'views/referees/modal-form.php'; ?>
<?php include FSTU_PLUGIN_DIR . 'views/referees/modal-certificates.php'; ?>
<?php include FSTU_PLUGIN_DIR . 'views/referees/modal-certificate-form.php'; ?>
<?php include FSTU_PLUGIN_DIR . 'views/referees/modal-certificate-bind-category.php'; ?>

