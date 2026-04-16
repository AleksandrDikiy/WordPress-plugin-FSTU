<?php
/**
 * View: Головна сторінка модуля Directory.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-16
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** @var bool $can_manage Права на керування */
?>

<div id="fstu-directory-module" class="fstu-module" data-nonce="<?php echo esc_attr( wp_create_nonce('fstu_module_nonce') ); ?>">

    <div class="fstu-module__header">
        <h1 class="fstu-title"><?php echo esc_html( get_the_title() ); ?></h1>
        <?php include FSTU_PLUGIN_DIR . 'views/directory/action-bar.php'; ?>
    </div>

    <div id="fstu-directory-content" class="fstu-module__content">
        <div class="fstu-loader" style="display:none;">
            <span class="spinner is-active"></span> <?php esc_html_e( 'Завантаження...', 'fstu' ); ?>
        </div>

        <div id="fstu-directory-view-container"></div>
    </div>

</div>