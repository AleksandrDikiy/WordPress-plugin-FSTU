<?php
namespace FSTU\Modules\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="fstu-applications-wrap" id="fstu-applications-app">


    <?php include __DIR__ . '/action-bar.php'; ?>

    <div id="fstu-view-directory">
        <?php include __DIR__ . '/filter-bar.php'; ?>
        <?php include __DIR__ . '/table-list.php'; ?>
    </div>

    <div id="fstu-view-protocol" class="fstu-hidden">
        <?php include __DIR__ . '/protocol-list.php'; ?>
    </div>

    <div id="fstu-modals-container">
        <?php include __DIR__ . '/modal-view.php'; ?>
        <?php include __DIR__ . '/modal-change-ofst.php'; ?>
        <?php include __DIR__ . '/modal-accept.php'; ?>
        <?php include __DIR__ . '/modal-message.php'; ?>
    </div>

</div>