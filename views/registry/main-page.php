<?php
/**
 * View: Головний каркас модуля "Реєстр членів ФСТУ".
 * Тільки HTML-розмітка. Жодних запитів до БД.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-03
 *
 * @package FSTU\Registry\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use FSTU\Registry\Registry_List;

// Дані для фільтрів (завантажуються один раз, на сервері, для SEO та швидкості)
$units        = Registry_List::get_units();
$tourism_types = Registry_List::get_tourism_types();
$clubs        = Registry_List::get_clubs();
$years        = Registry_List::get_years();
$current_year = (int) date( 'Y' );
$is_admin     = current_user_can( 'manage_options' );
$is_logged_in = is_user_logged_in();
?>

<div class="fstu-registry-wrap" id="fstu-registry">

	<h1 class="fstu-registry-title">Реєстр членів ФСТУ</h1>

	<?php if ( $is_admin ) : ?>
		<p class="fstu-registry-role-note"><em>Ви увійшли як адміністратор!</em></p>
	<?php endif; ?>

	<?php
	// ── Панель дій (кнопки) ──────────────────────────────────────────────────
	include __DIR__ . '/action-bar.php';

	// ── Панель фільтрів ───────────────────────────────────────────────────────
	include __DIR__ . '/filter-bar.php';

	// ── Таблиця ───────────────────────────────────────────────────────────────
	include __DIR__ . '/table-list.php';
	?>

	<?php
	// ── Модальне вікно заявки на вступ (тільки незареєстровані) ──────────────
	if ( ! $is_logged_in ) :
		include __DIR__ . '/modal-application.php';
	endif;
	?>

</div><!-- .fstu-registry-wrap -->
