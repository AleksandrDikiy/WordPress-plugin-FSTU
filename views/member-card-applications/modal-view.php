<?php
/**
 * Модальне вікно перегляду посвідчення члена ФСТУ.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-10
 *
 * @package FSTU\Modules\UserFstu\MemberCardApplications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-member-card-applications-view-modal" class="fstu-modal fstu-hidden" aria-hidden="true">
	<div class="fstu-modal__overlay" data-modal-close="view"></div>
	<div class="fstu-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="fstu-member-card-applications-view-title">
		<button type="button" class="fstu-modal__close" data-modal-close="view" aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">×</button>
		<h3 id="fstu-member-card-applications-view-title" class="fstu-modal__title"><?php esc_html_e( 'Картка посвідчення', 'fstu' ); ?></h3>
		<div id="fstu-member-card-applications-view-body" class="fstu-modal__body">
			<p><?php esc_html_e( 'Дані картки буде завантажено після вибору запису.', 'fstu' ); ?></p>
		</div>
	</div>
</div>

