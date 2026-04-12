<?php
/**
 * View: Головна сторінка модуля «Особистий кабінет ФСТУ».
 *
 * Version:     1.6.0
 * Date_update: 2026-04-12
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="fstu-personal-cabinet" id="fstu-personal-cabinet-app">
	<?php if ( ! empty( $guest_mode ) ) : ?>
		<div class="fstu-alert fstu-alert--info">
			Щоб переглядати особистий кабінет і працювати з власними даними, будь ласка, авторизуйтесь.
		</div>
		<p class="fstu-personal-cabinet__auth-row">
			<a class="fstu-btn fstu-btn--primary" href="<?php echo esc_url( (string) $guest_login_url ); ?>">Увійти на сайт</a>
		</p>
	<?php elseif ( ! empty( $profile_not_found_mode ) ) : ?>
		<div class="fstu-alert fstu-alert--error">
			Профіль користувача не знайдено або посилання містить некоректний параметр ViewID.
		</div>
	<?php elseif ( ! empty( $no_access_mode ) ) : ?>
		<div class="fstu-alert fstu-alert--error">
			У вас немає доступу до модуля «Особистий кабінет ФСТУ».
		</div>
	<?php else : ?>
		<div class="fstu-personal-cabinet__action-bar">
			<div class="fstu-personal-cabinet__action-bar-left">
				<h2 class="fstu-personal-cabinet__title">Особистий кабінет ФСТУ</h2>
			</div>
			<div class="fstu-personal-cabinet__action-bar-right">
				<button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-personal-show-main">ДОВІДНИК</button>
				<?php if ( ! empty( $permissions['canProtocol'] ) ) : ?>
					<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-personal-show-protocol">ПРОТОКОЛ</button>
				<?php endif; ?>
			</div>
		</div>

		<div class="fstu-personal-cabinet__section" id="fstu-personal-main-section">
			<?php if ( ! empty( $payment_notice['message'] ) ) : ?>
				<div class="fstu-alert fstu-alert--<?php echo esc_attr( (string) ( $payment_notice['type'] ?? 'info' ) ); ?>">
					<?php echo esc_html( (string) $payment_notice['message'] ); ?>
				</div>
			<?php endif; ?>

			<div class="fstu-personal-cabinet__summary">
				<div class="fstu-alert fstu-hidden" id="fstu-personal-alert"></div>
			</div>

			<div class="fstu-personal-tabs">
				<div class="fstu-personal-tabs__nav" role="tablist">
					<button type="button" class="fstu-personal-tabs__btn fstu-personal-tabs__btn--active" data-tab="general">Загальні</button>
					<button type="button" class="fstu-personal-tabs__btn<?php echo empty( $permissions['canViewPrivate'] ) ? ' fstu-hidden' : ''; ?>" data-tab="private">Приватне</button>
					<button type="button" class="fstu-personal-tabs__btn<?php echo empty( $permissions['canViewService'] ) ? ' fstu-hidden' : ''; ?>" data-tab="service">Службове</button>
					<button type="button" class="fstu-personal-tabs__btn" data-tab="clubs">Клуби</button>
					<button type="button" class="fstu-personal-tabs__btn" data-tab="city">Місто</button>
					<button type="button" class="fstu-personal-tabs__btn" data-tab="units">Осередоки</button>
					<button type="button" class="fstu-personal-tabs__btn" data-tab="tourism">Види туризму</button>
					<button type="button" class="fstu-personal-tabs__btn" data-tab="experience">Досвід</button>
					<button type="button" class="fstu-personal-tabs__btn" data-tab="ranks">Розряди</button>
					<button type="button" class="fstu-personal-tabs__btn" data-tab="judging">Суддівство</button>
					<button type="button" class="fstu-personal-tabs__btn" data-tab="dues">Внески</button>
					<button type="button" class="fstu-personal-tabs__btn" data-tab="sailing">Вітрильництво</button>
					<button type="button" class="fstu-personal-tabs__btn<?php echo empty( $permissions['canViewSailDues'] ) ? ' fstu-hidden' : ''; ?>" data-tab="dues_sail">Внески (вітр.)</button>
				</div>

				<div class="fstu-personal-tabs__content">
					<?php foreach ( [ 'general', 'private', 'service', 'clubs', 'city', 'units', 'tourism', 'experience', 'ranks', 'judging', 'dues', 'sailing', 'dues_sail' ] as $tab_slug ) : ?>
						<section class="fstu-personal-tabs__pane<?php echo 'general' === $tab_slug ? ' fstu-personal-tabs__pane--active' : ''; ?>" data-tab-pane="<?php echo esc_attr( $tab_slug ); ?>">
							<div class="fstu-personal-tab-card">
								<h3 class="fstu-personal-tab-card__title"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $tab_slug ) ) ); ?></h3>
								<div class="fstu-personal-tab-card__content" id="fstu-personal-tab-<?php echo esc_attr( $tab_slug ); ?>">
									<div class="fstu-personal-tab-card__placeholder">Завантаження даних вкладки...</div>
								</div>
							</div>
						</section>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<?php if ( ! empty( $permissions['canProtocol'] ) ) : ?>
			<div class="fstu-personal-cabinet__section fstu-hidden" id="fstu-personal-protocol-section">
				<?php include FSTU_PLUGIN_DIR . 'views/personal-cabinet/protocol-list.php'; ?>
			</div>
		<?php endif; ?>

		<?php include FSTU_PLUGIN_DIR . 'views/personal-cabinet/modal-dues.php'; ?>
		<?php include FSTU_PLUGIN_DIR . 'views/personal-cabinet/modal-photo.php'; ?>
		<?php include FSTU_PLUGIN_DIR . 'views/personal-cabinet/modal-edit.php'; ?>
		<?php include FSTU_PLUGIN_DIR . 'views/personal-cabinet/modal-club.php'; ?>
		<?php include FSTU_PLUGIN_DIR . 'views/personal-cabinet/modal-city.php'; ?>
        <?php include FSTU_PLUGIN_DIR . 'views/personal-cabinet/modal-unit.php'; ?>
		<?php include FSTU_PLUGIN_DIR . 'views/personal-cabinet/modal-tourism.php'; ?>
		<?php include FSTU_PLUGIN_DIR . 'views/personal-cabinet/modal-experience.php'; ?>
		<?php include FSTU_PLUGIN_DIR . 'views/personal-cabinet/modal-rank.php'; ?>
		<?php include FSTU_PLUGIN_DIR . 'views/personal-cabinet/modal-judging.php'; ?>

	<?php endif; ?>
</div>