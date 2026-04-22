<?php
/**
 * Ультра-компактне модальне вікно форми "Мерилка" (Обмірне свідоцтво).
 *
 * Version:     1.0.0
 * Date_update: 2026-04-09
 *
 * @package FSTU\Modules\UserFstu\Merilkas
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="fstu-merilkas-form-modal" class="fstu-modal-overlay fstu-hidden" aria-hidden="true">
	<div class="fstu-modal fstu-modal--merilkas" role="dialog" aria-modal="true" aria-labelledby="fstu-merilkas-form-title">
		<div class="fstu-modal__header">
			<h3 id="fstu-merilkas-form-title" class="fstu-modal__title">Обмірне свідоцтво вітрильного судна</h3>
			<button type="button" class="fstu-modal__close" data-close-modal="fstu-merilkas-form-modal" aria-label="Закрити" title="Закрити вікно">×</button>
		</div>
		
		<div class="fstu-modal__body">
			<div id="fstu-merilkas-form-message" class="fstu-form-message fstu-hidden"></div>

			<form id="fstu-merilkas-form" novalidate>
				<input type="hidden" id="fstu-merilkas-item-id" name="mr_id" value="0">
				<input type="hidden" id="fstu-merilkas-sailboat-id" name="sailboat_id" value="0">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'fstu_merilkas_nonce' ) ); ?>">

				<div class="fstu-merilkas-layout">
					
					<div class="fstu-merilkas-column">
						<div class="fstu-merilkas-card">
							<h4 class="fstu-merilkas-card__title">Екіпаж та Корпус</h4>
							<div class="fstu-merilkas-grid">
								<div class="fstu-form-group"><label class="fstu-label">Дата обміру</label><input type="date" class="fstu-input" name="MR_DateObmera" id="MR_DateObmera" required></div>
								<div class="fstu-form-group"><label class="fstu-label">Членів екіпажу</label><input type="number" class="fstu-input fstu-merilka-calc-input" name="MR_GrevNumber" id="MR_GrevNumber" required></div>
								<div class="fstu-form-group"><label class="fstu-label">Вага екіпажу (кг)</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_CrewWeight" id="MR_CrewWeight" required></div>
								<div class="fstu-form-group"><label class="fstu-label">Вага корпусу (кг)</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Weight" id="MR_Weight" required></div>
								<div class="fstu-form-group"><label class="fstu-label">Вага мотору (кг)</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_WeightMotor" id="MR_WeightMotor"></div>
								<div class="fstu-form-group"><label class="fstu-label">Довжина AL (м)</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Length" id="MR_Length" required></div>
							</div>
						</div>

						<div class="fstu-merilkas-card">
							<h4 class="fstu-merilkas-card__title">Щогла</h4>
							<div class="fstu-merilkas-grid">
								<div class="fstu-form-group"><label class="fstu-label">Поперечний D (м)</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Machta_PPD" id="MR_Machta_PPD" required></div>
								<div class="fstu-form-group"><label class="fstu-label">Поздовжній D1 (м)</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Machta_PRD" id="MR_Machta_PRD" required></div>
								<div class="fstu-form-group"><label class="fstu-label">Ліктрос d (м)</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Liktros" id="MR_Liktros" required></div>
							</div>
						</div>
					</div>

					<div class="fstu-merilkas-column">
						<div class="fstu-merilkas-card">
							<h4 class="fstu-merilkas-card__title">Грот</h4>
							<div class="fstu-merilkas-grid">
								<div class="fstu-form-group"><label class="fstu-label">P (передня шкаторина)</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Grot_P" id="MR_Grot_P" required></div>
								<div class="fstu-form-group"><label class="fstu-label">B (задня шкаторина)</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Grot_B" id="MR_Grot_B" required></div>
								<div class="fstu-form-group"><label class="fstu-label">E (нижня шкаторина)</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Grot_E" id="MR_Grot_E" required></div>
								<div class="fstu-form-group"><label class="fstu-label">hp (висота 1)</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Grot_HP" id="MR_Grot_HP"></div>
								<div class="fstu-form-group"><label class="fstu-label">hb (висота 2)</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Grot_HB" id="MR_Grot_HB"></div>
								<div class="fstu-form-group"><label class="fstu-label">he (висота 3)</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Grot_HE" id="MR_Grot_HE"></div>
								<div class="fstu-form-group"><label class="fstu-label">VLM (висота вітрила)</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Grot_VLM" id="MR_Grot_VLM" required></div>
								<div class="fstu-form-group"><label class="fstu-label fstu-text-primary">Площа (кв.м)</label><input type="text" class="fstu-input" name="MR_Area_Grot" id="MR_Area_Grot" readonly tabindex="-1"></div>
							</div>
						</div>

						<div class="fstu-merilkas-card">
							<h4 class="fstu-merilkas-card__title">Стаксель</h4>
							<div class="fstu-merilkas-grid">
								<div class="fstu-form-group"><label class="fstu-label">P</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Staksel_P" id="MR_Staksel_P"></div>
								<div class="fstu-form-group"><label class="fstu-label">B</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Staksel_B" id="MR_Staksel_B"></div>
								<div class="fstu-form-group"><label class="fstu-label">E</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Staksel_E" id="MR_Staksel_E"></div>
								<div class="fstu-form-group"><label class="fstu-label">hp</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Staksel_HP" id="MR_Staksel_HP"></div>
								<div class="fstu-form-group"><label class="fstu-label">hb</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Staksel_HB" id="MR_Staksel_HB"></div>
								<div class="fstu-form-group"><label class="fstu-label">he</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Staksel_HE" id="MR_Staksel_HE"></div>
								<div class="fstu-form-group"><label class="fstu-label">VLJ</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Staksel_VLM" id="MR_Staksel_VLM"></div>
								<div class="fstu-form-group"><label class="fstu-label fstu-text-primary">Площа (кв.м)</label><input type="text" class="fstu-input" name="MR_Area_Staksel" id="MR_Area_Staksel" readonly tabindex="-1"></div>
							</div>
						</div>
					</div>

					<div class="fstu-merilkas-column">
						<div class="fstu-merilkas-card">
							<h4 class="fstu-merilkas-card__title">Клівер</h4>
							<div class="fstu-merilkas-grid">
								<div class="fstu-form-group"><label class="fstu-label">P</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Kliver_P" id="MR_Kliver_P"></div>
								<div class="fstu-form-group"><label class="fstu-label">B</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Kliver_B" id="MR_Kliver_B"></div>
								<div class="fstu-form-group"><label class="fstu-label">E</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Kliver_E" id="MR_Kliver_E"></div>
								<div class="fstu-form-group"><label class="fstu-label">hp</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Kliver_HP" id="MR_Kliver_HP"></div>
								<div class="fstu-form-group"><label class="fstu-label">hb</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Kliver_HB" id="MR_Kliver_HB"></div>
								<div class="fstu-form-group"><label class="fstu-label">he</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Kliver_HE" id="MR_Kliver_HE"></div>
								<div class="fstu-form-group"><label class="fstu-label">VLM</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Kliver_VLM" id="MR_Kliver_VLM"></div>
								<div class="fstu-form-group"><label class="fstu-label fstu-text-primary">Площа (кв.м)</label><input type="text" class="fstu-input" name="MR_Area_Kliver" id="MR_Area_Kliver" readonly tabindex="-1"></div>
							</div>
						</div>

						<div class="fstu-merilkas-card">
							<h4 class="fstu-merilkas-card__title">Спінакер</h4>
							<div class="fstu-merilkas-grid">
								<div class="fstu-form-group"><label class="fstu-label">P</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Spinaker_P" id="MR_Spinaker_P"></div>
								<div class="fstu-form-group"><label class="fstu-label">B</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Spinaker_B" id="MR_Spinaker_B"></div>
								<div class="fstu-form-group"><label class="fstu-label">E</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Spinaker_E" id="MR_Spinaker_E"></div>
								<div class="fstu-form-group"><label class="fstu-label">SMW</label><input type="text" class="fstu-input fstu-merilka-calc-input" name="MR_Spinaker_SMW" id="MR_Spinaker_SMW"></div>
								<div class="fstu-form-group"><label class="fstu-label fstu-text-primary">Площа (кв.м)</label><input type="text" class="fstu-input" name="MR_Area_Spinaker" id="MR_Area_Spinaker" readonly tabindex="-1"></div>
							</div>
						</div>
					</div>
				</div>

				<div class="fstu-merilkas-live-footer">
					<div class="fstu-merilkas-results-grid">
						<div class="fstu-result-item">
							<span class="fstu-result-label">ГБ (базовий)</span>
							<input type="text" class="fstu-result-value" id="MR_GB" readonly tabindex="-1">
						</div>
						<div class="fstu-result-item">
							<span class="fstu-result-label">ГБ (зі спінакером)</span>
							<input type="text" class="fstu-result-value" id="MR_GB_Spinaker" readonly tabindex="-1">
						</div>
						<div class="fstu-result-item">
							<span class="fstu-result-label">ГБ (з екіпажем)</span>
							<input type="text" class="fstu-result-value" id="MR_GB_CrewWeight" readonly tabindex="-1">
						</div>
						<div class="fstu-result-item">
							<span class="fstu-result-label">ГБ (екіп. + спін.)</span>
							<input type="text" class="fstu-result-value" id="MR_GB_CrewWeight_Spinaker" readonly tabindex="-1">
						</div>
					</div>
					<div class="fstu-merilkas-actions">
						<button type="submit" class="fstu-btn fstu-btn--primary" id="fstu-merilkas-form-submit">💾 Зберегти</button>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>