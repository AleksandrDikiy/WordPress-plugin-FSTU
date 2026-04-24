<?php
namespace FSTU\Modules\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="fstu-action-bar">

    <div id="fstu-actions-directory" class="fstu-action-bar__group">
        <button type="button" class="fstu-btn" id="fstu-btn-protocol" title="Відкрити журнал операцій">
            <span aria-hidden="true">📋</span> ПРОТОКОЛ
        </button>
    </div>

    <div id="fstu-actions-protocol" class="fstu-action-bar__group fstu-hidden">
        <button type="button" class="fstu-btn" id="fstu-btn-back">
            <span aria-hidden="true">🔙</span> ДОВІДНИК
        </button>
    </div>

</div>