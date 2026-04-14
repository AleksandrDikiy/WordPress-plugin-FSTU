<?php
/**
 * Головна сторінка адміністрування плагіна (Хаб шорткодів).
 * * Version: 1.1.0
 * Date_update: 2026-04-13
 */

use FSTU\Admin\Shortcodes_Registry;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$categories = Shortcodes_Registry::get_categories();
$shortcodes = Shortcodes_Registry::get_shortcodes();
?>

<div class="wrap fstu-admin-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Федерація спортивного туризму України', 'fstu' ); ?></h1>
    <p class="fstu-admin-subtitle"><?php esc_html_e( 'Доступні шорткоди системи', 'fstu' ); ?></p>

    <div class="fstu-search-bar" style="display: flex; align-items: center; margin-bottom: 20px;">
        <span class="dashicons dashicons-search" style="margin-right: 8px; color: #888;"></span>
        <input type="text" id="fstu-shortcode-search" class="fstu-input" placeholder="<?php esc_attr_e( 'Швидкий пошук модуля або шорткоду...', 'fstu' ); ?>" style="max-width: 400px; width: 100%;">
    </div>

    <div class="nav-tab-wrapper fstu-nav-tabs">
        <?php $first = true; foreach ( $categories as $slug => $name ) : ?>
            <a href="#tab-<?php echo esc_attr( $slug ); ?>" class="nav-tab <?php echo $first ? 'nav-tab-active' : ''; ?>" data-tab="<?php echo esc_attr( $slug ); ?>">
                <?php echo esc_html( $name ); ?>
            </a>
            <?php $first = false; endforeach; ?>
    </div>

    <div class="fstu-tabs-content" style="margin-top: 20px;">
        <?php $first = true; foreach ( $categories as $slug => $name ) : ?>
            <div id="tab-<?php echo esc_attr( $slug ); ?>" class="fstu-tab-pane" style="display: <?php echo $first ? 'grid' : 'none'; ?>; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px;">

                <?php foreach ( $shortcodes as $tag => $data ) : ?>
                    <?php if ( $data['category'] === $slug ) : ?>
                        <div class="fstu-shortcode-card" style="background: #fff; border: 1px solid #d1d5db; padding: 15px; border-radius: 4px;" data-search="<?php echo esc_attr( strtolower( $data['title'] . ' ' . $tag ) ); ?>">
                            <h3 style="margin-top: 0; color: #d9534f;"><?php echo esc_html( $data['title'] ); ?></h3>
                            <p style="color: #666; font-size: 13px; min-height: 40px;"><?php echo esc_html( $data['description'] ); ?></p>
                            <code style="display: block; margin-bottom: 15px; background: #f8f9fa; padding: 8px; text-align: center;">[<?php echo esc_html( $tag ); ?>]</code>

                            <?php if ( empty( $data['attributes'] ) ) : ?>
                                <button type="button" class="fstu-btn fstu-btn--copy-simple" data-clipboard="[<?php echo esc_attr( $tag ); ?>]" style="width: 100%;">
                                    <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Скопіювати', 'fstu' ); ?>
                                </button>
                            <?php else : ?>
                                <button type="button" class="fstu-btn fstu-btn--save fstu-btn--open-generator" data-tag="<?php echo esc_attr( $tag ); ?>" data-config="<?php echo esc_attr( wp_json_encode( $data['attributes'] ) ); ?>" style="width: 100%; background: #d9534f; color: #fff; border: none; padding: 6px 12px; cursor: pointer;">
                                    <span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'Налаштувати та Копіювати', 'fstu' ); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>

            </div>
            <?php $first = false; endforeach; ?>
    </div>
</div>

<div id="fstu-generator-modal" class="fstu-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 100000; align-items: center; justify-content: center;">
    <div class="fstu-modal" style="background: #fff; width: 500px; border-radius: 4px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); display: flex; flex-direction: column;">
        <div class="fstu-modal-header" style="padding: 15px 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0;"><?php esc_html_e( 'Налаштування шорткоду', 'fstu' ); ?></h3>
            <button type="button" class="fstu-modal-close" style="background: transparent !important; border: none !important; box-shadow: none !important; font-size: 20px; cursor: pointer; color: #666;">&times;</button>
        </div>
        <div class="fstu-modal-body" id="fstu-generator-fields" style="padding: 20px; max-height: 60vh; overflow-y: auto;">
        </div>
        <div class="fstu-modal-footer" style="padding: 15px 20px; border-top: 1px solid #ddd; background: #f8f9fa; display: flex; flex-direction: column; gap: 15px;">
            <div>
                <label style="font-size: 12px; font-weight: bold; color: #555;"><?php esc_html_e( 'Результат:', 'fstu' ); ?></label>
                <input type="text" id="fstu-generator-result" readonly class="fstu-input" style="width: 100%; font-family: monospace; background: #e9ecef; color: #d9534f; font-weight: bold;">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="fstu-btn fstu-btn--cancel fstu-modal-close-btn" style="background: #e9ecef; border: 1px solid #ccc; padding: 6px 15px; cursor: pointer;"><?php esc_html_e( 'Скасувати', 'fstu' ); ?></button>
                <button type="button" class="fstu-btn fstu-btn--save" id="fstu-generator-copy-btn" style="background: #d9534f; color: #fff; border: none; padding: 6px 15px; cursor: pointer;"><?php esc_html_e( 'Згенерувати та Скопіювати', 'fstu' ); ?></button>
            </div>
        </div>
    </div>
</div>