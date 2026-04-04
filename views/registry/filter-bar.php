<?php
/**
 * View: Панель фільтрів реєстру членів ФСТУ.
 * Жодних кнопок "Пошук" — всі фільтри спрацьовують автоматично через JS (change/keyup).
 *
 * Version:     1.0.0
 * Date_update: 2026-04-03
 *
 * @package FSTU\Registry\Views
 *
 * @var array $units         Список ОФСТ для фільтру.
 * @var array $tourism_types Список видів туризму.
 * @var array $clubs         Список клубів.
 * @var array $years         Список років.
 * @var int   $current_year  Поточний рік.
 * @var bool  $is_logged_in  Чи авторизований.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="fstu-filter-bar" role="search" aria-label="Фільтри реєстру">

	<div class="fstu-filter-row fstu-filter-row--main">

		<!-- ОФСТ (осередок) -->
		<div class="fstu-filter-item">
			<label for="fstu-filter-unit" class="fstu-sr-only">Осередок ОФСТ</label>
			<select id="fstu-filter-unit"
			        name="unit_id"
			        class="fstu-select fstu-filter-trigger"
			        data-filter="unit_id">
				<option value="0">УСІ ОСЕРЕДКИ</option>
				<?php foreach ( $units as $unit ) : ?>
					<option value="<?php echo absint( $unit['Unit_ID'] ); ?>">
						<?php echo esc_html( $unit['Unit_ShortName'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<!-- Вид туризму -->
		<div class="fstu-filter-item fstu-filter-item--wide">
			<label for="fstu-filter-tourism" class="fstu-sr-only">Вид туризму</label>
			<select id="fstu-filter-tourism"
			        name="tourism_type"
			        class="fstu-select fstu-filter-trigger"
			        data-filter="tourism_type">
				<option value="0">ВСІ ВИДИ</option>
				<?php foreach ( $tourism_types as $tt ) : ?>
					<option value="<?php echo absint( $tt['TourismType_ID'] ); ?>">
						<?php echo esc_html( $tt['TourismType_Name'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<!-- Клуб -->
		<div class="fstu-filter-item fstu-filter-item--medium">
			<label for="fstu-filter-club" class="fstu-sr-only">Клуб</label>
			<select id="fstu-filter-club"
			        name="club_id"
			        class="fstu-select fstu-filter-trigger"
			        data-filter="club_id">
				<option value="0">ВСІ КЛУБИ</option>
				<?php foreach ( $clubs as $club ) : ?>
					<option value="<?php echo absint( $club['Club_ID'] ); ?>">
						<?php echo esc_html( $club['Club_Name'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<!-- Рік внесків -->
		<div class="fstu-filter-item fstu-filter-item--year">
			<label for="fstu-filter-year" class="fstu-sr-only">Рік</label>
			<select id="fstu-filter-year"
			        name="year"
			        class="fstu-select fstu-filter-trigger"
			        data-filter="year">
				<?php foreach ( $years as $year ) : ?>
					<option value="<?php echo esc_attr( $year ); ?>"
					        <?php selected( $year, $current_year ); ?>>
						<?php echo esc_html( $year ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<!-- Кількість записів на сторінці -->
		<div class="fstu-filter-item fstu-filter-item--perpage">
			<label for="fstu-filter-perpage" class="fstu-sr-only">Записів на сторінці</label>
			<select id="fstu-filter-perpage"
			        name="per_page"
			        class="fstu-select fstu-filter-trigger"
			        data-filter="per_page">
				<option value="10" selected>10</option>
				<option value="20">20</option>
				<option value="50">50</option>
				<option value="100">100</option>
			</select>
		</div>

		<!-- Чекбокс "ФСТУ" -->
		<div class="fstu-filter-item fstu-filter-item--checkbox">
			<label class="fstu-checkbox-label" for="fstu-filter-fstu-only">
				<input type="checkbox"
				       id="fstu-filter-fstu-only"
				       name="fstu_only"
				       class="fstu-checkbox fstu-filter-trigger"
				       data-filter="fstu_only"
				       value="1"
				       checked>
				<span class="fstu-checkbox-text">ФСТУ</span>
			</label>
		</div>

	</div><!-- .fstu-filter-row--main -->

	<!-- Рядок пошуку по ПІБ -->
	<div class="fstu-filter-row fstu-filter-row--search">
		<div class="fstu-search-wrap">
			<span class="fstu-search-icon" aria-hidden="true">🔍</span>
			<input type="search"
			       id="fstu-filter-search"
			       name="search"
			       class="fstu-input fstu-search-input"
			       placeholder="пошук за ПІБ"
			       autocomplete="off"
			       data-filter="search"
			       aria-label="Пошук за прізвищем, ім'ям, по батькові">
			<button type="button"
			        class="fstu-search-clear fstu-hidden"
			        id="fstu-search-clear"
			        aria-label="Очистити пошук">✕</button>
		</div>
	</div>

</div><!-- .fstu-filter-bar -->
