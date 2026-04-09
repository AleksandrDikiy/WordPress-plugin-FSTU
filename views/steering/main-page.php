<?php
/**
 * Основний шаблон модуля «Реєстр стернових ФСТУ».
 *
 * Version:     1.4.0
 * Date_update: 2026-04-08
 *
 * @package FSTU\Modules\Registry\Steering
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$permissions     = isset( $permissions ) && is_array( $permissions ) ? $permissions : [];
$submit_blocked  = ! empty( $submit_blocked );
$can_submit      = ! empty( $permissions['canSubmit'] );
$can_manage      = ! empty( $permissions['canManage'] );
$can_manage_status = ! empty( $permissions['canManageStatus'] );
$can_protocol    = ! empty( $permissions['canProtocol'] );
$can_see_finance = ! empty( $permissions['canSeeFinance'] );
$show_submit_btn = $can_manage || ( $can_submit && ! $submit_blocked );
$status_options  = isset( $status_options ) && is_array( $status_options ) ? $status_options : [];
$policy_url      = isset( $policy_url ) ? (string) $policy_url : '';
?>

<div id="fstu-steering" class="fstu-steering-wrap">
	<h2 class="fstu-steering-title"><?php esc_html_e( 'Реєстр стернових ФСТУ', 'fstu' ); ?></h2>
	<div id="fstu-steering-notice" class="fstu-steering-notice fstu-hidden" aria-live="polite"></div>

	<div class="fstu-action-bar fstu-action-bar--steering">
		<div class="fstu-action-bar__group fstu-action-bar__group--buttons">
			<?php if ( $show_submit_btn ) : ?>
				<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-steering-add-btn">
					<?php esc_html_e( 'ПОДАТИ ЗАЯВКУ', 'fstu' ); ?>
				</button>
			<?php endif; ?>
			<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-steering-refresh-btn">
				<?php esc_html_e( 'ОНОВИТИ', 'fstu' ); ?>
			</button>
		</div>

		<div class="fstu-action-bar__group fstu-action-bar__group--filters">
			<?php if ( $can_manage || $can_manage_status ) : ?>
				<label class="fstu-steering-filter-label" for="fstu-steering-status-filter"><?php esc_html_e( 'СТАТУС:', 'fstu' ); ?></label>
				<select id="fstu-steering-status-filter" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Фільтр по статусу', 'fstu' ); ?>">
					<option value="0"><?php esc_html_e( 'усі статуси', 'fstu' ); ?></option>
					<?php foreach ( $status_options as $status_option ) : ?>
						<option value="<?php echo esc_attr( (string) ( $status_option['AppStatus_ID'] ?? 0 ) ); ?>"><?php echo esc_html( (string) ( $status_option['AppStatus_Name'] ?? '' ) ); ?></option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>

			<?php if ( $can_see_finance ) : ?>
				<label class="fstu-steering-filter-label" for="fstu-steering-dues-filter"><?php esc_html_e( 'ВНЕСКИ:', 'fstu' ); ?></label>
				<select id="fstu-steering-dues-filter" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Фільтр по внесках', 'fstu' ); ?>">
					<option value="all"><?php esc_html_e( 'весь реєстр', 'fstu' ); ?></option>
					<option value="full_paid"><?php esc_html_e( 'сплатили внески', 'fstu' ); ?></option>
					<option value="fstu_paid"><?php esc_html_e( 'сплатили ФСТУ', 'fstu' ); ?></option>
					<option value="sail_paid"><?php esc_html_e( 'сплатили вітрильні', 'fstu' ); ?></option>
				</select>
			<?php endif; ?>
		</div>

		<?php if ( $can_protocol ) : ?>
			<div class="fstu-action-bar__group fstu-action-bar__group--protocol">
				<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-steering-protocol-btn">
					<?php esc_html_e( 'ПРОТОКОЛ', 'fstu' ); ?>
				</button>
				<button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-steering-protocol-back-btn">
					<?php esc_html_e( 'ДОВІДНИК', 'fstu' ); ?>
				</button>
			</div>
		<?php endif; ?>
	</div>

	<div id="fstu-steering-main">
		<?php include FSTU_PLUGIN_DIR . 'views/steering/table-list.php'; ?>

		<div class="fstu-pagination fstu-pagination--compact">
			<div class="fstu-pagination__left">
				<label class="fstu-pagination__per-page-label" for="fstu-steering-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
				<select id="fstu-steering-per-page" class="fstu-select fstu-select--compact" aria-label="<?php esc_attr_e( 'Кількість записів на сторінці', 'fstu' ); ?>">
					<option value="10" selected>10</option>
					<option value="15">15</option>
					<option value="25">25</option>
					<option value="50">50</option>
				</select>
			</div>
			<div class="fstu-pagination__controls">
				<button type="button" class="fstu-btn--page" id="fstu-steering-prev-page">«</button>
				<div id="fstu-steering-pagination-pages"></div>
				<button type="button" class="fstu-btn--page" id="fstu-steering-next-page">»</button>
			</div>
			<div class="fstu-pagination__info"><span id="fstu-steering-pagination-info" aria-live="polite"></span></div>
		</div>

		<div class="fstu-steering-legend">
			<div class="fstu-steering-legend__item"><?php esc_html_e( 'сплатили членські внески вітрильників в поточному році', 'fstu' ); ?></div>
			<div class="fstu-steering-legend__item"><?php esc_html_e( 'сплатили в попередньому році', 'fstu' ); ?></div>
			<div class="fstu-steering-legend__item"><?php esc_html_e( 'не сплатили ні в поточному ні в попередньому році', 'fstu' ); ?></div>
			<div class="fstu-steering-legend__item">
				<a href="<?php echo esc_url( $policy_url ); ?>" class="fstu-steering-policy-link" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'ПОЛОЖЕННЯ про Реестрацію', 'fstu' ); ?></a>
			</div>
		</div>
	</div>

	<?php if ( $can_protocol ) : ?>
		<?php include FSTU_PLUGIN_DIR . 'views/steering/protocol-table-list.php'; ?>
	<?php endif; ?>
</div>

<?php include FSTU_PLUGIN_DIR . 'views/steering/modal-view.php'; ?>
<?php include FSTU_PLUGIN_DIR . 'views/steering/modal-form.php'; ?>

