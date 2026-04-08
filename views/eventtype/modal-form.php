<?php 
/**
 * Shared-модалка додавання / редагування / перегляду "Довідник типів заходів".
 *
 * Version:     1.0.0
 * Date_update: 2026-04-07
 *
 * @package FSTU\Dictionaries\EventType
 */
namespace FSTU\Dictionaries\EventType; 

if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div id="fstu-modal-eventtype" class="fstu-modal fstu-hidden">
	<div class="fstu-modal-content">
		<div class="fstu-modal-header">
			<h3 id="fstu-modal-title">Додати тип заходу</h3>
			<button type="button" class="fstu-modal-close">✕</button>
		</div>
		<div class="fstu-modal-body">
			<form id="fstu-eventtype-form">
				<input type="hidden" id="fstu-eventtype-id" value="0">
				<div class="fstu-form-group">
					<label for="fstu-eventtype-name">Найменування <span class="fstu-text-danger">*</span></label>
					<input type="text" id="fstu-eventtype-name" class="fstu-input" required>
				</div>
				<div class="fstu-form-group">
					<label for="fstu-eventtype-order">Сортування</label>
					<input type="number" id="fstu-eventtype-order" class="fstu-input" value="0">
				</div>
				<div class="fstu-form-actions">
					<button type="submit" class="fstu-btn fstu-btn-primary">Зберегти</button>
					<button type="button" class="fstu-btn fstu-modal-close">Скасувати</button>
				</div>
			</form>
		</div>
	</div>
</div>