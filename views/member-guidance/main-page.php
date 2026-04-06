<?php
/**
 * Основний шаблон модуля "Довідник посад у керівних органах ФСТУ".
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\MemberGuidance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$permissions             = isset( $permissions ) && is_array( $permissions ) ? $permissions : [];
$guest_mode              = ! empty( $guest_mode );
$guest_login_url         = isset( $guest_login_url ) ? (string) $guest_login_url : wp_login_url( home_url( '/' ) );
$can_manage              = ! empty( $permissions['canManage'] );
$can_protocol            = ! empty( $permissions['canProtocol'] );
$typeguidance_options    = isset( $typeguidance_options ) && is_array( $typeguidance_options ) ? $typeguidance_options : [];
$initial_typeguidance_id = isset( $initial_typeguidance_id ) ? (int) $initial_typeguidance_id : 1;
?>

<div id="fstu-member-guidance" class="fstu-member-guidance-wrap">
	<h2 class="fstu-member-guidance-title"><?php esc_html_e( 'Довідник посад у керівних органах ФСТУ', 'fstu' ); ?></h2>

	<?php if ( $guest_mode ) : ?>
		<div class="fstu-member-guidance-login-state">
			<div class="fstu-member-guidance-login-state__icon">🔐</div>
			<div class="fstu-member-guidance-login-state__content">
				<h3 class="fstu-member-guidance-login-state__title"><?php esc_html_e( 'Щоб бачити додаткові функції, будь ласка, авторизуйтесь.', 'fstu' ); ?></h3>
				<p class="fstu-member-guidance-login-state__text"><?php esc_html_e( 'Доступ до довідника посад у керівних органах надається лише авторизованим користувачам з правами ФСТУ.', 'fstu' ); ?></p>
				<div class="fstu-member-guidance-login-state__actions">
					<a class="fstu-btn fstu-btn--primary" href="<?php echo esc_url( $guest_login_url ); ?>"><?php esc_html_e( 'УВІЙТИ', 'fstu' ); ?></a>
				</div>
			</div>
		</div>
		<?php return; ?>
	<?php endif; ?>

	<div class="fstu-action-bar">
		<?php if ( $can_manage ) : ?>
			<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-member-guidance-add-btn">
				<span class="fstu-btn__icon">➕</span>
				<?php esc_html_e( 'Додати запис', 'fstu' ); ?>
			</button>
		<?php endif; ?>

		<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-member-guidance-refresh-btn">
			<span class="fstu-btn__icon">🔄</span>
			<?php esc_html_e( 'Оновити', 'fstu' ); ?>
		</button>

		<?php if ( $can_protocol ) : ?>
			<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-member-guidance-protocol-btn">
				<span class="fstu-btn__icon">📋</span>
				<?php esc_html_e( 'ПРОТОКОЛ', 'fstu' ); ?>
			</button>
			<button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-member-guidance-protocol-back-btn">
				<span class="fstu-btn__icon">↩</span>
				<?php esc_html_e( 'ДОВІДНИК', 'fstu' ); ?>
			</button>
		<?php endif; ?>
	</div>

	<div id="fstu-member-guidance-main">
		<?php include FSTU_PLUGIN_DIR . 'views/member-guidance/filter-bar.php'; ?>
		<?php include FSTU_PLUGIN_DIR . 'views/member-guidance/table-list.php'; ?>

		<div class="fstu-pagination fstu-pagination--compact">
			<div class="fstu-pagination__left">
				<label class="fstu-pagination__per-page-label" for="fstu-member-guidance-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
				<select id="fstu-member-guidance-per-page" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Кількість записів на сторінці', 'fstu' ); ?>">
					<option value="10" selected>10</option>
					<option value="15">15</option>
					<option value="25">25</option>
					<option value="50">50</option>
				</select>
			</div>
			<div class="fstu-pagination__controls">
				<button type="button" class="fstu-btn--page" id="fstu-member-guidance-prev-page">«</button>
				<div id="fstu-member-guidance-pagination-pages"></div>
				<button type="button" class="fstu-btn--page" id="fstu-member-guidance-next-page">»</button>
			</div>
			<div class="fstu-pagination__info">
				<span id="fstu-member-guidance-pagination-info" aria-live="polite"></span>
			</div>
		</div>
	</div>

	<?php if ( $can_protocol ) : ?>
		<?php include FSTU_PLUGIN_DIR . 'views/member-guidance/protocol-table-list.php'; ?>
	<?php endif; ?>
</div>

<?php include FSTU_PLUGIN_DIR . 'views/member-guidance/modal-form.php'; ?>

