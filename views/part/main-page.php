<div class="fstu-module-wrapper fstu-part-module">
    <div class="fstu-kpi-dashboard">
        <div class="fstu-kpi-card">
            <div class="fstu-kpi-title">Заходів</div>
            <div class="fstu-kpi-value" id="kpi-events">0</div>
        </div>
        <div class="fstu-kpi-card">
            <div class="fstu-kpi-title">Пройдено (км)</div>
            <div class="fstu-kpi-value" id="kpi-distance">0.00</div>
        </div>
        <div class="fstu-kpi-card">
            <div class="fstu-kpi-title">Макс. швидкість</div>
            <div class="fstu-kpi-value" id="kpi-speed">0.00 <small>км/год</small></div>
        </div>
    </div>

    <div class="fstu-action-bar">
        <div class="fstu-action-bar__left">
            <select id="fstu-part-year-filter" class="fstu-select">
                <option value="0">Усі роки</option>
                <?php foreach ( $years as $year ) : ?>
                    <option value="<?php echo esc_attr( $year ); ?>"><?php echo esc_html( $year ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="fstu-action-bar__right">
            <?php if ( current_user_can( \FSTU\Core\Capabilities::VIEW_PART_PROTOCOL ) || current_user_can( 'administrator' ) ) : ?>
                <button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-btn-protocol">ПРОТОКОЛ</button>
                <button type="button" class="fstu-btn fstu-btn--secondary" id="fstu-btn-back-to-list" style="display:none;">ДОВІДНИК</button>
            <?php endif; ?>
        </div>
    </div>

    <div id="fstu-part-main-view">
        <?php include FSTU_PLUGIN_DIR . 'views/part/table-list.php'; ?>
    </div>

    <div id="fstu-part-protocol-view" style="display:none;">
        <?php include FSTU_PLUGIN_DIR . 'views/part/protocol-list.php'; ?>
    </div>

    <?php include FSTU_PLUGIN_DIR . 'views/part/modals/modal-form.php'; ?>
</div>