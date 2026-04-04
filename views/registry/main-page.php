<?php
/**
 * View: Головний каркас модуля "Реєстр членів ФСТУ".
 *
 * @package FSTU\Registry\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use FSTU\Registry\Registry_List;

// Дані для фільтрів
$units        = Registry_List::get_units();
$tourism_types = Registry_List::get_tourism_types();
$clubs        = Registry_List::get_clubs();
$years        = Registry_List::get_years();
$current_year = (int) date( 'Y' );
$is_admin     = current_user_can( 'manage_options' );
$is_logged_in = is_user_logged_in();

// Отримуємо посилання з БД
global $wpdb;
// phpcs:disable WordPress.DB.DirectDatabaseQuery
$link_instruction = $wpdb->get_var( "SELECT ParamValue FROM Settings WHERE ParamName = 'LinkInstruction'" )
        ?: 'https://docs.google.com/document/d/1nLpqnfvWs7l5vqO8r-oiaAWOBjVD1aRiRUs22hPCQ8E/edit?usp=sharing';
$link_postanova   = $wpdb->get_var( "SELECT ParamValue FROM Settings WHERE ParamName = 'LinkPostanova'" )
        ?: 'https://drive.google.com/file/d/1YP49GY4yljBVwA-nd6ZxV-JvFA4naJDp/view';
// phpcs:enable
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

    <div class="fstu-registry-stats" style="margin-top: 15px; font-size: 14px; margin-bottom: 15px;">
		<span class="fstu-badge" style="display:inline-block; padding:5px 10px; background:#eef2f5; border-radius:4px; margin-right:10px;">
			Усього : <b id="fstu-stat-total">0</b>
		</span>
        <span class="fstu-badge" style="display:inline-block; padding:5px 10px; background:#eef2f5; border-radius:4px;">
			Сплатили членські внески : <b id="fstu-stat-paid" style="color:#27ae60;">0</b>
		</span>
    </div>

    <?php
    // ── Модальні вікна ───────────────────────────────────────────────────────
    if ( ! $is_logged_in ) {
        include __DIR__ . '/modal-application.php';
    } else {
        include __DIR__ . '/modals/member-card.php';
        include __DIR__ . '/modals/club-info.php';
        include __DIR__ . '/modals/protocol.php';
        include __DIR__ . '/modals/report.php';
        include __DIR__ . '/modals/edit-user.php';
        include __DIR__ . '/modals/add-club.php';
        include __DIR__ . '/modals/change-ofst.php';
        include __DIR__ . '/modals/add-dues.php';
    }
    ?>
</div>