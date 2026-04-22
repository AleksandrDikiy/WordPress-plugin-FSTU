<?php
/**
 * Partial-шаблон блоку даних власника / капітана.
 *
 * Version:     1.3.0
 * Date_update: 2026-04-08
 *
 * @package FSTU\Modules\UserFstu\Sailboats
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="fstu-form-group">
	<label class="fstu-label fstu-label--required" for="fstu-sailboats-last-name"><?php esc_html_e( 'Прізвище', 'fstu' ); ?></label>
	<input type="text" class="fstu-input" id="fstu-sailboats-last-name" name="appshipticket_last_name" maxlength="100" required>
</div>

<div class="fstu-form-group">
	<label class="fstu-label fstu-label--required" for="fstu-sailboats-first-name"><?php esc_html_e( 'Ім\'я', 'fstu' ); ?></label>
	<input type="text" class="fstu-input" id="fstu-sailboats-first-name" name="appshipticket_first_name" maxlength="100" required>
</div>

<div class="fstu-form-group">
	<label class="fstu-label" for="fstu-sailboats-patronymic"><?php esc_html_e( 'По батькові', 'fstu' ); ?></label>
	<input type="text" class="fstu-input" id="fstu-sailboats-patronymic" name="appshipticket_patronymic" maxlength="100">
</div>

<div class="fstu-form-group">
	<label class="fstu-label" for="fstu-sailboats-last-name-eng"><?php esc_html_e( 'Прізвище (ENG)', 'fstu' ); ?></label>
	<input type="text" class="fstu-input" id="fstu-sailboats-last-name-eng" name="appshipticket_last_name_eng" maxlength="100">
</div>

<div class="fstu-form-group">
	<label class="fstu-label" for="fstu-sailboats-first-name-eng"><?php esc_html_e( 'Ім\'я (ENG)', 'fstu' ); ?></label>
	<input type="text" class="fstu-input" id="fstu-sailboats-first-name-eng" name="appshipticket_first_name_eng" maxlength="100">
</div>