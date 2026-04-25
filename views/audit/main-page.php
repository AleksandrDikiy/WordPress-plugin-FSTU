<?php
/**
 * View: Головна сторінка модуля "Ревізійна комісія".
 *
 * Version:     1.0.0
 * Date_update: 2026-04-25
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
?>

<div id="fstu-audit-module" class="fstu-module" data-nonce="<?php echo esc_attr( wp_create_nonce('fstu_module_nonce') ); ?>">
    <div class="fstu-module__header">
        <h1 class="fstu-title"><?php echo esc_html( get_the_title() ); ?></h1>
    </div>

    <div class="fstu-module__content">
        <div class="fstu-loader" style="display:none; text-align:center; padding: 20px;">
            <span class="spinner is-active"></span> <?php esc_html_e( 'Завантаження...', 'fstu' ); ?>
        </div>

        <div id="fstu-audit-grid-container" class="fstu-audit-grid"></div>
    </div>
</div>