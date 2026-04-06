<?php
/**
 * View: Рядок пошуку — довідник клубів.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-05
 *
 * @package FSTU\Clubs\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="fstu-filter-bar" role="search" aria-label="Пошук клубу">
	<div class="fstu-search-wrap">
		<span class="fstu-search-icon" aria-hidden="true">🔍</span>
		<input type="search"
		       id="fstu-club-search"
		       class="fstu-input fstu-search-input"
		       placeholder="пошук за назвою клубу"
		       autocomplete="off"
		       aria-label="Пошук за назвою клубу">
		<button type="button"
		        class="fstu-search-clear fstu-hidden"
		        id="fstu-club-search-clear"
		        aria-label="Очистити пошук">✕</button>
	</div>
</div>
