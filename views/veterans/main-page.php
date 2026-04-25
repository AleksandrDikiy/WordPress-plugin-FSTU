<?php
/**
 * View: Головна сторінка модуля "Рада ветеранів".
 *
 * Version:     1.1.0
 * Date_update: 2026-04-25
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
?>

<div id="fstu-veterans-module" class="fstu-module" data-nonce="<?php echo esc_attr( wp_create_nonce('fstu_module_nonce') ); ?>">
    <div class="fstu-module__content">
        <div class="fstu-loader" style="display:none; text-align:center; padding: 20px;">
            <span class="spinner is-active"></span> <?php esc_html_e( 'Завантаження...', 'fstu' ); ?>
        </div>

        <div id="fstu-veterans-grid-container" class="fstu-veterans-grid"></div>
    </div>
</div>