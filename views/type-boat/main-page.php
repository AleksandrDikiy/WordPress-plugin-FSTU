<?php
/**
 * View: Головна сторінка модуля "Виробники та типи суден".
 * Version: 1.1.5
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$permissions    = \FSTU\Core\Capabilities::get_type_boat_permissions();
$can_manage     = ! empty( $permissions['canManage'] );
$can_protocol   = ! empty( $permissions['canProtocol'] );
$has_action_bar = $can_manage || $can_protocol;
?>

<div id="fstu-typeboat-module" class="fstu-typeboat-wrap">
    <h2 class="fstu-typeboat-title"><?php esc_html_e( 'Довідник типів суден', 'fstu' ); ?></h2>

    <?php if ( $has_action_bar ) : ?>
        <div class="fstu-action-bar fstu-hidden">
            <div class="fstu-typeboat-action-bar__left">
                <?php if ( $can_manage ) : ?>
                    <button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-typeboat-add-producer-btn">
                        <span class="fstu-btn__icon dashicons dashicons-plus"></span>
                        <?php esc_html_e( 'Додати виробника', 'fstu' ); ?>
                    </button>
                <?php endif; ?>
            </div>

            <div class="fstu-typeboat-action-bar__center"></div>

            <div class="fstu-typeboat-action-bar__right">
                <?php if ( $can_protocol ) : ?>
                    <button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-typeboat-protocol-btn">
                        <span class="fstu-btn__icon dashicons dashicons-clipboard"></span>
                        <?php esc_html_e( 'ПРОТОКОЛ', 'fstu' ); ?>
                    </button>
                    <button type="button" class="fstu-btn fstu-btn--secondary fstu-hidden" id="fstu-typeboat-list-btn">
                        <span class="fstu-btn__icon dashicons dashicons-media-spreadsheet"></span>
                        <?php esc_html_e( 'ДОВІДНИК', 'fstu' ); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="fstu-module__content">
        <div id="fstu-producers-section">
            <?php require_once __DIR__ . '/table-producers.php'; ?>
        </div>
        <div id="fstu-boats-section" class="fstu-hidden">
            <?php require_once __DIR__ . '/table-boats.php'; ?>
        </div>
        <div id="fstu-protocol-section" class="fstu-hidden">
            <?php require_once __DIR__ . '/protocol-list.php'; ?>
        </div>
    </div>

    <?php
    require_once __DIR__ . '/modal-producer.php';
    require_once __DIR__ . '/modal-boat.php';
    ?>
</div>