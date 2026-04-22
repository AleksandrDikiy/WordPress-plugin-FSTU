<?php
/**
 * Основний шаблон модуля «Посвідчення членів ФСТУ».
 *
 * Version:     1.2.1
 * Date_update: 2026-04-10
 *
 * @package FSTU\Modules\UserFstu\MemberCardApplications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$permissions         = isset( $permissions ) && is_array( $permissions ) ? $permissions : [];
$guest_mode          = ! empty( $guest_mode );
$no_access_mode      = ! empty( $no_access_mode );
$self_service_only   = ! empty( $self_service_only );
$can_view            = ! empty( $permissions['canView'] );
$can_manage          = ! empty( $permissions['canManage'] );
$can_self_service    = ! empty( $permissions['canSelfService'] );
$can_protocol        = ! empty( $permissions['canProtocol'] );
$bootstrap_notice    = isset( $bootstrap_notice ) ? (string) $bootstrap_notice : '';
$bootstrap_return_url = isset( $bootstrap_return_url ) ? (string) $bootstrap_return_url : '';
$bootstrap_return_label = isset( $bootstrap_return_label ) && '' !== (string) $bootstrap_return_label ? (string) $bootstrap_return_label : __( 'ПОВЕРНУТИСЬ', 'fstu' );
$self_service_has_member_card = ! empty( $self_service_has_member_card );
$self_service_member_card_id  = isset( $self_service_member_card_id ) ? (int) $self_service_member_card_id : 0;
$self_service_card_number     = isset( $self_service_card_number ) ? (string) $self_service_card_number : '';
$filter_regions      = isset( $filter_regions ) && is_array( $filter_regions ) ? $filter_regions : [];
$filter_statuses     = isset( $filter_statuses ) && is_array( $filter_statuses ) ? $filter_statuses : [];
$filter_types        = isset( $filter_types ) && is_array( $filter_types ) ? $filter_types : [];
?>

<div id="fstu-member-card-applications" class="fstu-member-card-applications-wrap">
	<h2 class="fstu-member-card-applications-title"><?php esc_html_e( 'Посвідчення членів ФСТУ', 'fstu' ); ?></h2>
	<div id="fstu-member-card-applications-notice" class="fstu-member-card-applications-notice fstu-hidden" aria-live="polite"></div>

	<?php if ( '' !== $bootstrap_notice ) : ?>
		<div class="fstu-member-card-applications-notice fstu-member-card-applications-notice--success"><?php echo esc_html( $bootstrap_notice ); ?></div>
	<?php endif; ?>

	<?php if ( $guest_mode ) : ?>
		<div class="fstu-member-card-applications-notice fstu-member-card-applications-notice--error">
			<?php esc_html_e( 'Щоб працювати з модулем посвідчень, будь ласка, авторизуйтесь.', 'fstu' ); ?>
		</div>
		<?php if ( ! empty( $guest_login_url ) ) : ?>
			<a class="fstu-btn fstu-btn--secondary" href="<?php echo esc_url( (string) $guest_login_url ); ?>"><?php esc_html_e( 'УВІЙТИ', 'fstu' ); ?></a>
		<?php endif; ?>
	<?php elseif ( $no_access_mode ) : ?>
		<div class="fstu-member-card-applications-notice fstu-member-card-applications-notice--error">
			<?php esc_html_e( 'У вас немає прав для перегляду цього модуля.', 'fstu' ); ?>
		</div>
	<?php else : ?>
		<div class="fstu-action-bar fstu-action-bar--member-card-applications">
			<div class="fstu-action-bar__group fstu-action-bar__group--buttons">
				<?php if ( '' !== $bootstrap_return_url ) : ?>
					<a class="fstu-btn fstu-btn--secondary" href="<?php echo esc_url( $bootstrap_return_url ); ?>"><?php echo esc_html( $bootstrap_return_label ); ?></a>
				<?php endif; ?>
				<?php if ( $can_manage || ( $can_self_service && ! $self_service_only ) ) : ?>
					<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-member-card-applications-add-btn"><?php esc_html_e( 'НОВЕ ПОСВІДЧЕННЯ', 'fstu' ); ?></button>
				<?php endif; ?>
			</div>

			<?php if ( $can_view ) : ?>
				<div class="fstu-action-bar__group fstu-action-bar__group--filters">
					<label class="fstu-label" for="fstu-member-card-applications-region-filter"><?php esc_html_e( 'РЕГІОН:', 'fstu' ); ?></label>
					<select id="fstu-member-card-applications-region-filter" class="fstu-select fstu-select--compact fstu-member-card-applications-filter-select">
						<option value="0"><?php esc_html_e( 'усі регіони', 'fstu' ); ?></option>
						<?php foreach ( $filter_regions as $region ) : ?>
							<option value="<?php echo esc_attr( (string) ( $region['Region_ID'] ?? 0 ) ); ?>"><?php echo esc_html( (string) ( $region['Region_Name'] ?? '' ) ); ?></option>
						<?php endforeach; ?>
					</select>

					<label class="fstu-label" for="fstu-member-card-applications-status-filter"><?php esc_html_e( 'СТАТУС:', 'fstu' ); ?></label>
					<select id="fstu-member-card-applications-status-filter" class="fstu-select fstu-select--compact fstu-member-card-applications-filter-select">
						<option value="0"><?php esc_html_e( 'усі статуси', 'fstu' ); ?></option>
						<?php foreach ( $filter_statuses as $status ) : ?>
							<option value="<?php echo esc_attr( (string) ( $status['StatusCard_ID'] ?? 0 ) ); ?>"><?php echo esc_html( (string) ( $status['StatusCard_Name'] ?? '' ) ); ?></option>
						<?php endforeach; ?>
					</select>

					<label class="fstu-label" for="fstu-member-card-applications-type-filter"><?php esc_html_e( 'ТИП:', 'fstu' ); ?></label>
					<select id="fstu-member-card-applications-type-filter" class="fstu-select fstu-select--compact fstu-member-card-applications-filter-select">
						<option value="0"><?php esc_html_e( 'усі типи', 'fstu' ); ?></option>
						<?php foreach ( $filter_types as $type ) : ?>
							<option value="<?php echo esc_attr( (string) ( $type['TypeCard_ID'] ?? 0 ) ); ?>"><?php echo esc_html( (string) ( $type['TypeCard_Name'] ?? '' ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endif; ?>

			<?php if ( $can_protocol ) : ?>
				<div class="fstu-action-bar__group fstu-action-bar__group--protocol">
					<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-member-card-applications-protocol-btn"><?php esc_html_e( 'ПРОТОКОЛ', 'fstu' ); ?></button>
					<button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-member-card-applications-protocol-back-btn"><?php esc_html_e( 'ДОВІДНИК', 'fstu' ); ?></button>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $self_service_only ) : ?>
			<div class="fstu-member-card-applications-notice">
				<?php esc_html_e( 'Для self-service сценарію вже підготовлено окремий flow через особистий кабінет. Повний список посвідчень доступний службовим ролям.', 'fstu' ); ?>
			</div>

			<div class="fstu-member-card-applications-self-service">
				<h3 class="fstu-member-card-applications-self-service__title"><?php esc_html_e( 'Мій сценарій посвідчення', 'fstu' ); ?></h3>
				<p class="fstu-member-card-applications-self-service__text">
					<?php if ( $self_service_has_member_card ) : ?>
						<?php echo esc_html( sprintf( __( 'Для вашого профілю вже знайдено актуальне посвідчення%s. Ви можете переглянути його, виконати перевипуск або оновити фото.', 'fstu' ), '' !== $self_service_card_number ? ' № ' . $self_service_card_number : '' ) ); ?>
					<?php else : ?>
						<?php esc_html_e( 'Для вашого профілю актуальне посвідчення ще не знайдено. Ви можете відкрити форму оформлення нового посвідчення.', 'fstu' ); ?>
					<?php endif; ?>
				</p>
				<div class="fstu-member-card-applications-self-service__actions">
					<?php if ( $self_service_has_member_card ) : ?>
						<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-member-card-applications-self-view-btn" data-member-card-id="<?php echo esc_attr( (string) $self_service_member_card_id ); ?>"><?php esc_html_e( 'ПЕРЕГЛЯНУТИ ПОСВІДЧЕННЯ', 'fstu' ); ?></button>
						<?php if ( ! empty( $permissions['canReissue'] ) ) : ?>
							<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-member-card-applications-self-reissue-btn" data-member-card-id="<?php echo esc_attr( (string) $self_service_member_card_id ); ?>"><?php esc_html_e( 'ПЕРЕВИПУСТИТИ', 'fstu' ); ?></button>
						<?php endif; ?>
						<?php if ( ! empty( $permissions['canUpdatePhoto'] ) ) : ?>
							<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-member-card-applications-self-photo-btn" data-member-card-id="<?php echo esc_attr( (string) $self_service_member_card_id ); ?>"><?php esc_html_e( 'ОНОВИТИ ФОТО', 'fstu' ); ?></button>
						<?php endif; ?>
					<?php else : ?>
						<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-member-card-applications-self-create-btn"><?php esc_html_e( 'ОФОРМИТИ ПОСВІДЧЕННЯ', 'fstu' ); ?></button>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( $can_view ) : ?>
			<div id="fstu-member-card-applications-main">
				<?php include FSTU_PLUGIN_DIR . 'views/member-card-applications/table-list.php'; ?>

				<div class="fstu-pagination fstu-pagination--compact">
					<div class="fstu-pagination__left">
						<label class="fstu-pagination__per-page-label" for="fstu-member-card-applications-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
						<select id="fstu-member-card-applications-per-page" class="fstu-select fstu-select--compact">
							<option value="10" selected>10</option>
							<option value="15">15</option>
							<option value="25">25</option>
							<option value="50">50</option>
						</select>
					</div>
					<div class="fstu-pagination__controls" id="fstu-member-card-applications-pagination-pages"></div>
					<div class="fstu-pagination__info"><span id="fstu-member-card-applications-pagination-info" aria-live="polite"></span></div>
				</div>
			</div>

			<?php if ( $can_protocol ) : ?>
				<?php include FSTU_PLUGIN_DIR . 'views/member-card-applications/protocol-table-list.php'; ?>
			<?php endif; ?>
		<?php endif; ?>
	<?php endif; ?>
</div>

<?php include FSTU_PLUGIN_DIR . 'views/member-card-applications/modal-view.php'; ?>
<?php include FSTU_PLUGIN_DIR . 'views/member-card-applications/modal-form.php'; ?>

