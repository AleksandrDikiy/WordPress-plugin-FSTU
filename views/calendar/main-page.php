<?php
/**
 * Основний шаблон модуля "Календарний план змагань ФСТУ".
 *
 * Version: 1.4.0
 * Date_update: 2026-04-13
 *
 * @package FSTU\Modules\Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$permissions  = isset( $permissions ) && is_array( $permissions ) ? $permissions : [];
$datasets     = isset( $datasets ) && is_array( $datasets ) ? $datasets : [];
$nonce        = isset( $nonce ) ? (string) $nonce : '';
$current_year = isset( $current_year ) ? (int) $current_year : (int) current_time( 'Y' );
$events_permissions = isset( $permissions['events'] ) && is_array( $permissions['events'] ) ? $permissions['events'] : [];
$can_create_event   = ! empty( $events_permissions['canManage'] );
$can_protocol       = ! empty( $events_permissions['canProtocol'] );
?>

<div id="fstu-calendar" class="fstu-calendar-wrap">
	<h2 class="fstu-calendar-title"><?php esc_html_e( 'Календарний план змагань ФСТУ', 'fstu' ); ?></h2>

	<div class="fstu-action-bar">
		<?php if ( $can_create_event ) : ?>
			<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-calendar-add-event-btn">
				<span class="fstu-btn__icon">➕</span>
				<?php esc_html_e( 'Додати захід', 'fstu' ); ?>
			</button>
		<?php endif; ?>

		<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-calendar-refresh-btn">
			<span class="fstu-btn__icon">🔄</span>
			<?php esc_html_e( 'Оновити', 'fstu' ); ?>
		</button>

		<?php if ( $can_protocol ) : ?>
			<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-calendar-protocol-btn">
				<span class="fstu-btn__icon">📋</span>
				<?php esc_html_e( 'ПРОТОКОЛ', 'fstu' ); ?>
			</button>
			<button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-calendar-protocol-back-btn">
				<span class="fstu-btn__icon">↩</span>
				<?php esc_html_e( 'ДОВІДНИК', 'fstu' ); ?>
			</button>
		<?php endif; ?>
	</div>

	<div class="fstu-calendar-shell-tabs" role="tablist" aria-label="Навігація модуля Calendar">
		<button type="button" class="fstu-shell-tab is-active" data-target="registry"><?php esc_html_e( 'Реєстр', 'fstu' ); ?></button>
		<button type="button" class="fstu-shell-tab" data-target="calendar"><?php esc_html_e( 'Календар', 'fstu' ); ?></button>
		<button type="button" class="fstu-shell-tab" data-target="applications"><?php esc_html_e( 'Заявки', 'fstu' ); ?></button>
		<button type="button" class="fstu-shell-tab" data-target="routes"><?php esc_html_e( 'Маршрути', 'fstu' ); ?></button>
		<button type="button" class="fstu-shell-tab" data-target="results"><?php esc_html_e( 'Результати', 'fstu' ); ?></button>
	</div>

	<div id="fstu-calendar-main" class="fstu-shell-panel is-active" data-panel="registry">
		<div class="fstu-calendar-filters">
			<div class="fstu-filter-field">
				<label for="fstu-calendar-year"><?php esc_html_e( 'Рік', 'fstu' ); ?></label>
				<select id="fstu-calendar-year" class="fstu-select">
					<?php foreach ( (array) ( $datasets['years'] ?? [] ) as $year_row ) : ?>
						<?php $year_value = (int) ( $year_row['year_value'] ?? 0 ); ?>
						<?php if ( $year_value > 0 ) : ?>
							<option value="<?php echo esc_attr( (string) $year_value ); ?>" <?php selected( $year_value, $current_year ); ?>><?php echo esc_html( (string) $year_value ); ?></option>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="fstu-filter-field">
				<label for="fstu-calendar-status"><?php esc_html_e( 'Статус', 'fstu' ); ?></label>
				<select id="fstu-calendar-status" class="fstu-select">
					<option value="0"><?php esc_html_e( 'Усі', 'fstu' ); ?></option>
					<?php foreach ( (array) ( $datasets['statuses'] ?? [] ) as $item ) : ?>
						<option value="<?php echo esc_attr( (string) (int) ( $item['CalendarStatus_ID'] ?? 0 ) ); ?>"><?php echo esc_html( (string) ( $item['CalendarStatus_Name'] ?? '' ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="fstu-filter-field">
				<label for="fstu-calendar-region"><?php esc_html_e( 'Область', 'fstu' ); ?></label>
				<select id="fstu-calendar-region" class="fstu-select">
					<option value="0"><?php esc_html_e( 'Усі', 'fstu' ); ?></option>
					<?php foreach ( (array) ( $datasets['regions'] ?? [] ) as $item ) : ?>
						<option value="<?php echo esc_attr( (string) (int) ( $item['Region_ID'] ?? 0 ) ); ?>"><?php echo esc_html( (string) ( $item['Region_Name'] ?? '' ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="fstu-filter-field">
				<label for="fstu-calendar-tourism-type"><?php esc_html_e( 'Вид туризму', 'fstu' ); ?></label>
				<select id="fstu-calendar-tourism-type" class="fstu-select">
					<option value="0"><?php esc_html_e( 'Усі', 'fstu' ); ?></option>
					<?php foreach ( (array) ( $datasets['tourism_types'] ?? [] ) as $item ) : ?>
						<option value="<?php echo esc_attr( (string) (int) ( $item['TourismType_ID'] ?? 0 ) ); ?>"><?php echo esc_html( (string) ( $item['TourismType_Name'] ?? '' ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="fstu-filter-field">
				<label for="fstu-calendar-event-type"><?php esc_html_e( 'Тип заходу', 'fstu' ); ?></label>
				<select id="fstu-calendar-event-type" class="fstu-select">
					<option value="0"><?php esc_html_e( 'Усі', 'fstu' ); ?></option>
					<?php foreach ( (array) ( $datasets['event_types'] ?? [] ) as $item ) : ?>
						<option value="<?php echo esc_attr( (string) (int) ( $item['EventType_ID'] ?? 0 ) ); ?>"><?php echo esc_html( (string) ( $item['EventType_Name'] ?? '' ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<?php include FSTU_PLUGIN_DIR . 'views/calendar/events/table-list.php'; ?>

		<div class="fstu-pagination fstu-pagination--compact">
			<div class="fstu-pagination__left">
				<label class="fstu-pagination__per-page-label" for="fstu-calendar-per-page"><?php esc_html_e( 'Показувати по:', 'fstu' ); ?></label>
				<select id="fstu-calendar-per-page" class="fstu-select fstu-select--compact">
					<option value="10" selected>10</option>
					<option value="15">15</option>
					<option value="25">25</option>
					<option value="50">50</option>
				</select>
			</div>
			<div class="fstu-pagination__controls">
				<button type="button" class="fstu-btn--page" id="fstu-calendar-prev-page">«</button>
				<div id="fstu-calendar-pagination-pages"></div>
				<button type="button" class="fstu-btn--page" id="fstu-calendar-next-page">»</button>
			</div>
			<div class="fstu-pagination__info">
				<span id="fstu-calendar-pagination-info" aria-live="polite"></span>
			</div>
		</div>
	</div>

	<div id="fstu-calendar-view-panel" class="fstu-shell-panel fstu-hidden" data-panel="calendar">
		<div class="fstu-calendar-view-toolbar">
			<button type="button" class="fstu-btn fstu-btn--secondary is-active" data-calendar-view="month"><?php esc_html_e( 'Місяць', 'fstu' ); ?></button>
			<button type="button" class="fstu-btn fstu-btn--secondary" data-calendar-view="week"><?php esc_html_e( 'Тиждень', 'fstu' ); ?></button>
			<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-calendar-view-prev">←</button>
			<button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-calendar-view-next">→</button>
			<span id="fstu-calendar-view-caption" class="fstu-calendar-view-caption"></span>
		</div>

		<div id="fstu-calendar-view-content" class="fstu-calendar-view-content">
			<div class="fstu-calendar-placeholder"><?php esc_html_e( 'Завантаження календаря...', 'fstu' ); ?></div>
		</div>
	</div>

	<div class="fstu-shell-panel fstu-hidden" data-panel="applications">
		<?php include FSTU_PLUGIN_DIR . 'views/calendar/applications/table-list.php'; ?>
		<?php include FSTU_PLUGIN_DIR . 'views/calendar/applications/protocol-table-list.php'; ?>
	</div>

	<div class="fstu-shell-panel fstu-hidden" data-panel="routes">
		<?php include FSTU_PLUGIN_DIR . 'views/calendar/routes/table-list.php'; ?>
		<?php include FSTU_PLUGIN_DIR . 'views/calendar/routes/protocol-table-list.php'; ?>
	</div>

	<div class="fstu-shell-panel fstu-hidden" data-panel="results">
		<?php include FSTU_PLUGIN_DIR . 'views/calendar/results/table-list.php'; ?>
		<?php include FSTU_PLUGIN_DIR . 'views/calendar/results/protocol-table-list.php'; ?>
	</div>

	<?php include FSTU_PLUGIN_DIR . 'views/calendar/events/protocol-table-list.php'; ?>
	<?php include FSTU_PLUGIN_DIR . 'views/calendar/events/modal-view.php'; ?>
	<?php include FSTU_PLUGIN_DIR . 'views/calendar/events/modal-form.php'; ?>
	<?php include FSTU_PLUGIN_DIR . 'views/calendar/applications/modal-view.php'; ?>
	<?php include FSTU_PLUGIN_DIR . 'views/calendar/applications/modal-form.php'; ?>
	<?php include FSTU_PLUGIN_DIR . 'views/calendar/routes/modal-view.php'; ?>
	<?php include FSTU_PLUGIN_DIR . 'views/calendar/routes/modal-form.php'; ?>
	<?php include FSTU_PLUGIN_DIR . 'views/calendar/results/modal-view.php'; ?>
	<?php include FSTU_PLUGIN_DIR . 'views/calendar/results/modal-form.php'; ?>

	<input type="hidden" id="fstu-calendar-form-nonce" value="<?php echo esc_attr( $nonce ); ?>">
</div>

