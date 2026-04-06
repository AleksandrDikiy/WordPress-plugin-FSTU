<?php
/**
 * View: Головний каркас модуля "Довідник клубів ФСТУ".
 * Тільки HTML-розмітка. Жодних запитів до БД.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-05
 *
 * @package FSTU\Clubs\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_admin  = current_user_can( 'administrator' );
$user      = wp_get_current_user();
$can_edit  = $is_admin || in_array( 'userregistrar', (array) $user->roles, true );
?>

<div class="fstu-clubs-wrap" id="fstu-clubs">

	<h1 class="fstu-registry-title">Довідник клубів ФСТУ</h1>

	<?php include __DIR__ . '/action-bar.php'; ?>
	<?php include __DIR__ . '/filter-bar.php'; ?>
	<?php include __DIR__ . '/table-list.php'; ?>

	<?php include __DIR__ . '/modal-view.php';  ?>
	<?php include __DIR__ . '/modal-form.php';  ?>

</div><!-- .fstu-clubs-wrap -->
