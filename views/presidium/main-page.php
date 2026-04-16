<?php
/**
 * View: Головна сторінка модуля Presidium.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-16
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
?>

<div id="fstu-presidium-module" class="fstu-module">

    <div class="fstu-module__header">
        <h1 class="fstu-title"><?php echo esc_html( get_the_title() ); ?></h1>
    </div>

    <div id="fstu-presidium-content" class="fstu-module__content">
        <div class="fstu-loader" style="text-align: center; padding: 40px 0;">
            <span class="spinner is-active" style="float:none; margin:0;"></span> <?php esc_html_e( 'Завантаження складу Президії...', 'fstu' ); ?>
        </div>

        <div id="fstu-presidium-view-container" style="display:none;"></div>
    </div>

</div>