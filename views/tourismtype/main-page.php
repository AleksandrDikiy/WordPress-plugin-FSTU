<?php
/**
 * View: головний каркас модуля "Довідник видів туризму".
 *
 * Не містить жодних запитів до БД — тільки HTML-розмітка.
 * Усі динамічні дані завантажуються через AJAX (JS).
 *
 * Version:     1.0.0
 * Date_update: 2026-04-07
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_admin = current_user_can( 'manage_options' );
?>
<div id="fstu-tourismtype-app" class="fstu-module-wrap">

	<!-- ================================================================ -->
	<!-- СЕКЦІЯ: ДОВІДНИК                                                  -->
	<!-- ================================================================ -->
	<section id="fstu-tourismtype-section-dict" class="fstu-section fstu-section--active">

		<!-- Заголовок -->
		<div class="fstu-module-header">
			<h2 class="fstu-module-title">
				<span class="fstu-module-title__icon">🏔</span>
				<?php esc_html_e( 'Довідник видів туризму', 'fstu' ); ?>
			</h2>
		</div>

		<!-- Панель дій (action-bar) -->
		<div class="fstu-action-bar" id="fstu-tourismtype-action-bar">

			<?php if ( $is_admin ) : ?>
				<button type="button"
				        class="fstu-btn fstu-btn--action"
				        id="fstu-tourismtype-btn-add"
				        title="<?php esc_attr_e( 'Додати новий вид туризму', 'fstu' ); ?>">
					➕ <?php esc_html_e( 'Додати', 'fstu' ); ?>
				</button>
			<?php endif; ?>

			<button type="button"
			        class="fstu-btn fstu-btn--action"
			        id="fstu-tourismtype-btn-refresh"
			        title="<?php esc_attr_e( 'Оновити список', 'fstu' ); ?>">
				🔄 <?php esc_html_e( 'Оновити', 'fstu' ); ?>
			</button>

			<?php if ( $is_admin ) : ?>
				<button type="button"
				        class="fstu-btn fstu-btn--action"
				        id="fstu-tourismtype-btn-protocol"
				        title="<?php esc_attr_e( 'Перейти до журналу операцій', 'fstu' ); ?>">
					📋 <?php esc_html_e( 'ПРОТОКОЛ', 'fstu' ); ?>
				</button>
			<?php endif; ?>

		</div><!-- /.fstu-action-bar -->

		<!-- Повідомлення (успіх / помилка) -->
		<div id="fstu-tourismtype-notice" class="fstu-notice fstu-notice--hidden" role="alert" aria-live="polite"></div>

		<!-- Таблиця довідника -->
		<?php include __DIR__ . '/table-list.php'; ?>

	</section><!-- /#fstu-tourismtype-section-dict -->

	<!-- ================================================================ -->
	<!-- СЕКЦІЯ: ПРОТОКОЛ (прихована за замовчуванням)                    -->
	<!-- ================================================================ -->
	<?php if ( $is_admin ) : ?>
	<section id="fstu-tourismtype-section-protocol" class="fstu-section fstu-hidden">

		<!-- Заголовок -->
		<div class="fstu-module-header">
			<h2 class="fstu-module-title">
				<span class="fstu-module-title__icon">📋</span>
				<?php esc_html_e( 'Протокол операцій — Довідник видів туризму', 'fstu' ); ?>
			</h2>
		</div>

		<!-- Панель дій протоколу -->
		<div class="fstu-action-bar">
			<button type="button"
			        class="fstu-btn fstu-btn--action"
			        id="fstu-tourismtype-btn-back-to-dict"
			        title="<?php esc_attr_e( 'Повернутися до довідника', 'fstu' ); ?>">
				◀ <?php esc_html_e( 'ДОВІДНИК', 'fstu' ); ?>
			</button>
		</div>

		<!-- Повідомлення протоколу -->
		<div id="fstu-tourismtype-protocol-notice" class="fstu-notice fstu-notice--hidden" role="alert" aria-live="polite"></div>

		<!-- Таблиця протоколу -->
		<?php include __DIR__ . '/protocol-list.php'; ?>

	</section><!-- /#fstu-tourismtype-section-protocol -->
	<?php endif; ?>

	<!-- ================================================================ -->
	<!-- МОДАЛЬНЕ ВІКНО: Додавання / Редагування                          -->
	<!-- ================================================================ -->
	<?php if ( $is_admin ) : ?>
	<div id="fstu-tourismtype-modal"
	     class="fstu-modal fstu-hidden"
	     role="dialog"
	     aria-modal="true"
	     aria-labelledby="fstu-tourismtype-modal-title">

		<div class="fstu-modal__overlay" id="fstu-tourismtype-modal-overlay"></div>

		<div class="fstu-modal__dialog">

			<div class="fstu-modal__header">
				<h3 class="fstu-modal__title" id="fstu-tourismtype-modal-title">
					<?php esc_html_e( 'Вид туризму', 'fstu' ); ?>
				</h3>
				<button type="button"
				        class="fstu-modal__close"
				        id="fstu-tourismtype-modal-close"
				        aria-label="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>">✕</button>
			</div>

			<div class="fstu-modal__body">

				<!-- Повідомлення всередині форми -->
				<div id="fstu-tourismtype-form-notice" class="fstu-notice fstu-notice--hidden" role="alert"></div>

				<form id="fstu-tourismtype-form" novalidate>
					<!-- Honeypot: захист від ботів -->
					<input type="text"
					       name="fstu_website"
					       class="fstu-honeypot"
					       style="display:none !important;"
					       tabindex="-1"
					       autocomplete="off"
					       aria-hidden="true">

					<!-- Прихований ID (0 = новий запис) -->
					<input type="hidden" id="fstu-tt-id" name="id" value="0">

					<!-- Найменування -->
					<div class="fstu-form-group">
						<label class="fstu-form-label fstu-form-label--required"
						       for="fstu-tt-name">
							<?php esc_html_e( 'Найменування', 'fstu' ); ?>
						</label>
						<input type="text"
						       id="fstu-tt-name"
						       name="TourismType_Name"
						       class="fstu-form-control"
						       maxlength="200"
						       required
						       placeholder="<?php esc_attr_e( 'Назва виду туризму…', 'fstu' ); ?>">
					</div>

					<!-- Номер статті/сторінки -->
					<div class="fstu-form-group">
						<label class="fstu-form-label"
						       for="fstu-tt-number">
							<?php esc_html_e( 'Номер статті / сторінки', 'fstu' ); ?>
						</label>
						<input type="number"
						       id="fstu-tt-number"
						       name="TourismType_Number"
						       class="fstu-form-control fstu-form-control--short"
						       min="0"
						       step="1"
						       placeholder="0">
					</div>

					<!-- Сортування -->
					<div class="fstu-form-group">
						<label class="fstu-form-label"
						       for="fstu-tt-order">
							<?php esc_html_e( 'Сортування', 'fstu' ); ?>
						</label>
						<input type="number"
						       id="fstu-tt-order"
						       name="TourismType_Order"
						       class="fstu-form-control fstu-form-control--short"
						       min="0"
						       step="1"
						       placeholder="0">
					</div>

				</form><!-- /#fstu-tourismtype-form -->

			</div><!-- /.fstu-modal__body -->

			<div class="fstu-modal__footer">
				<button type="button"
				        class="fstu-btn fstu-btn--primary"
				        id="fstu-tourismtype-btn-save">
					💾 <?php esc_html_e( 'Зберегти', 'fstu' ); ?>
				</button>
				<button type="button"
				        class="fstu-btn fstu-btn--secondary"
				        id="fstu-tourismtype-btn-cancel">
					<?php esc_html_e( 'Скасувати', 'fstu' ); ?>
				</button>
			</div><!-- /.fstu-modal__footer -->

		</div><!-- /.fstu-modal__dialog -->
	</div><!-- /#fstu-tourismtype-modal -->
	<?php endif; ?>

</div><!-- /#fstu-tourismtype-app -->
