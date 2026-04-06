<?php
/**
 * Модальне вікно форми додавання/редагування осередка.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-06
 *
 * @package FSTU\Dictionaries\Units
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$regions      = isset( $regions ) && is_array( $regions ) ? $regions : [];
$opfs         = isset( $opfs ) && is_array( $opfs ) ? $opfs : [];
$unit_types   = isset( $unit_types ) && is_array( $unit_types ) ? $unit_types : [];
$parent_units = isset( $parent_units ) && is_array( $parent_units ) ? $parent_units : [];
?>

<div id="fstu-units-modal-form" class="fstu-modal-overlay fstu-units-hidden" aria-hidden="true">
	<div class="fstu-modal" role="dialog" aria-modal="true" aria-labelledby="fstu-units-form-title">
		<div class="fstu-modal__header">
			<h3 class="fstu-modal__title" id="fstu-units-form-title"><?php esc_html_e( 'Додавання осередка', 'fstu' ); ?></h3>
			<button type="button" class="fstu-modal-close-btn" id="fstu-units-modal-form-close" title="<?php esc_attr_e( 'Закрити', 'fstu' ); ?>" aria-label="<?php esc_attr_e( 'Закрити форму осередка', 'fstu' ); ?>">
				✕
			</button>
		</div>
		<div class="fstu-modal__body">
			<form id="fstu-units-form" class="fstu-app-form">
				<?php wp_nonce_field( \FSTU\Dictionaries\Units\Units_List::NONCE_ACTION, 'nonce' ); ?>

				<!-- Hidden field для ID при редаганні -->
				<input type="hidden" id="fstu-units-edit-id" name="unit_id" value="">

				<!-- Honeypot -->
				<label class="fstu-units-hidden" for="fstu-units-honeypot"><?php esc_html_e( 'Не заповнюйте це поле', 'fstu' ); ?></label>
				<input
					type="text"
					id="fstu-units-honeypot"
					name="fstu_website"
					class="fstu-honeypot"
					tabindex="-1"
					autocomplete="off"
					aria-hidden="true"
				>

				<!-- Рядок 1: Найменування повне и скорочене -->
				<div class="fstu-form-row fstu-form-row--2col">
					<div class="fstu-form-group">
						<label class="fstu-label fstu-label--required" for="fstu-units-unit-name">
							<?php esc_html_e( 'Найменування (повне)', 'fstu' ); ?>
						</label>
						<input
							type="text"
							id="fstu-units-unit-name"
							name="unit_name"
							class="fstu-input"
							placeholder="<?php esc_attr_e( 'напр., Спортивний клуб "Таймер"', 'fstu' ); ?>"
							required
						>
					</div>
					<div class="fstu-form-group">
						<label class="fstu-label fstu-label--required" for="fstu-units-unit-short">
							<?php esc_html_e( 'Найменування (скорочене)', 'fstu' ); ?>
						</label>
						<input
							type="text"
							id="fstu-units-unit-short"
							name="unit_short_name"
							class="fstu-input"
							placeholder="<?php esc_attr_e( 'напр., СК "Таймер"', 'fstu' ); ?>"
							required
						>
					</div>
				</div>

				<!-- Рядок 2: Регіон і Місто -->
				<div class="fstu-form-row fstu-form-row--2col">
					<div class="fstu-form-group">
						<label class="fstu-label fstu-label--required" for="fstu-units-region">
							<?php esc_html_e( 'Регіон', 'fstu' ); ?>
						</label>
						<select id="fstu-units-region" name="region_id" class="fstu-select" required>
							<option value=""><?php esc_html_e( 'Виберіть регіон', 'fstu' ); ?></option>
							<?php foreach ( $regions as $region ) : ?>
								<option value="<?php echo absint( $region['Region_ID'] ?? 0 ); ?>">
									<?php echo esc_html( $region['Region_Name'] ?? '' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="fstu-form-group">
						<label class="fstu-label fstu-label--required" for="fstu-units-city">
							<?php esc_html_e( 'Місто', 'fstu' ); ?>
						</label>
						<select id="fstu-units-city" name="city_id" class="fstu-select" required>
							<option value=""><?php esc_html_e( 'Виберіть місто', 'fstu' ); ?></option>
						</select>
					</div>
				</div>

				<!-- Рядок 3: ОПФ і Тип осередка -->
				<div class="fstu-form-row fstu-form-row--2col">
					<div class="fstu-form-group">
						<label class="fstu-label fstu-label--required" for="fstu-units-opf">
							<?php esc_html_e( 'Організаційно-правова форма', 'fstu' ); ?>
						</label>
						<select id="fstu-units-opf" name="opf_id" class="fstu-select" required>
							<option value=""><?php esc_html_e( 'Виберіть ОПФ', 'fstu' ); ?></option>
							<?php foreach ( $opfs as $opf ) : ?>
								<option value="<?php echo absint( $opf['OPF_ID'] ?? 0 ); ?>">
									<?php echo esc_html( $opf['OPF_Name'] ?? '' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="fstu-form-group">
						<label class="fstu-label fstu-label--required" for="fstu-units-type">
							<?php esc_html_e( 'Тип осередка (Ранг)', 'fstu' ); ?>
						</label>
						<select id="fstu-units-type" name="unit_type_id" class="fstu-select" required>
							<option value=""><?php esc_html_e( 'Виберіть тип', 'fstu' ); ?></option>
							<?php foreach ( $unit_types as $type ) : ?>
								<option value="<?php echo absint( $type['UnitType_ID'] ?? 0 ); ?>">
									<?php echo esc_html( $type['UnitType_Name'] ?? '' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<!-- Рядок 4: Вищий осередок -->
				<div class="fstu-form-row">
					<div class="fstu-form-group">
						<label class="fstu-label" for="fstu-units-parent">
							<?php esc_html_e( 'Осередок вищого рівня', 'fstu' ); ?>
						</label>
						<select id="fstu-units-parent" name="unit_parent" class="fstu-select">
							<option value="0"><?php esc_html_e( 'Не обраний', 'fstu' ); ?></option>
							<?php foreach ( $parent_units as $parent ) : ?>
								<option value="<?php echo absint( $parent['Unit_ID'] ?? 0 ); ?>">
									<?php echo esc_html( $parent['Unit_Name'] ?? '' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<!-- Рядок 5: Адреса и ОКПО -->
				<div class="fstu-form-row fstu-form-row--2col">
					<div class="fstu-form-group">
						<label class="fstu-label" for="fstu-units-address">
							<?php esc_html_e( 'Адреса місцезнаходження', 'fstu' ); ?>
						</label>
						<input
							type="text"
							id="fstu-units-address"
							name="unit_adr"
							class="fstu-input"
							placeholder="<?php esc_attr_e( 'адреса', 'fstu' ); ?>"
						>
					</div>
					<div class="fstu-form-group">
						<label class="fstu-label" for="fstu-units-okpo">
							<?php esc_html_e( 'Код ЄДРПОУ', 'fstu' ); ?>
						</label>
						<input
							type="text"
							id="fstu-units-okpo"
							name="unit_okpo"
							class="fstu-input"
							placeholder="<?php esc_attr_e( '10 цифр', 'fstu' ); ?>"
						>
					</div>
				</div>

				<!-- Рядок 6: Вступний та Річний внесок -->
				<div class="fstu-form-row fstu-form-row--2col">
					<div class="fstu-form-group">
						<label class="fstu-label" for="fstu-units-entrance-fee">
							<?php esc_html_e( 'Вступний внесок (грн)', 'fstu' ); ?>
						</label>
						<input
							type="number"
							id="fstu-units-entrance-fee"
							name="entrance_fee"
							class="fstu-input"
							step="0.01"
							min="0"
							placeholder="0.00"
						>
					</div>
					<div class="fstu-form-group">
						<label class="fstu-label" for="fstu-units-annual-fee">
							<?php esc_html_e( 'Річний внесок (грн)', 'fstu' ); ?>
						</label>
						<input
							type="number"
							id="fstu-units-annual-fee"
							name="annual_fee"
							class="fstu-input"
							step="0.01"
							min="0"
							placeholder="0.00"
						>
					</div>
				</div>

				<!-- Рядок 7: URL оплати та номер карти -->
				<div class="fstu-form-row fstu-form-row--2col">
					<div class="fstu-form-group">
						<label class="fstu-label" for="fstu-units-url-pay">
							<?php esc_html_e( 'Посилання на форму оплати', 'fstu' ); ?>
						</label>
						<input
							type="url"
							id="fstu-units-url-pay"
							name="url_pay"
							class="fstu-input"
							placeholder="https://..."
						>
					</div>
					<div class="fstu-form-group">
						<label class="fstu-label" for="fstu-units-payment-card">
							<?php esc_html_e( 'Номер платіжної карти', 'fstu' ); ?>
						</label>
						<input
							type="text"
							id="fstu-units-payment-card"
							name="payment_card"
							class="fstu-input"
							placeholder="<?php esc_attr_e( 'XXXX-XXXX-XXXX-XXXX', 'fstu' ); ?>"
						>
					</div>
				</div>

				<!-- Повідомлення форми (заповнюється через JS) -->
				<div id="fstu-units-form-message" class="fstu-form-message fstu-units-hidden" aria-live="polite"></div>

				<!-- Кнопки -->
				<div class="fstu-form-actions">
					<button type="submit" class="fstu-btn fstu-btn--primary" id="fstu-units-form-submit">
						<span class="fstu-btn__icon">💾</span>
						<span class="fstu-btn-text"><?php esc_html_e( 'Зберегти', 'fstu' ); ?></span>
					</button>
					<button type="button" class="fstu-btn fstu-btn--text" id="fstu-units-form-cancel">
						<?php esc_html_e( 'Скасувати', 'fstu' ); ?>
					</button>
				</div>
			</form>
		</div>
	</div>
</div>

