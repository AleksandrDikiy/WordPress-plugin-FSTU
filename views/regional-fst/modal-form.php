<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div id="fstu-regional-modal" class="fstu-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 100000; align-items: center; justify-content: center;">
    <div class="fstu-modal-dialog" style="background: #fff; width: 100%; max-width: 500px; border-radius: 4px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); display: flex; flex-direction: column;">

        <div class="fstu-modal-header" style="display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #e5e5e5;">
            <h3 class="fstu-modal-title" style="margin: 0; font-size: 18px;">Додавання / Редагування</h3>
            <button type="button" class="fstu-modal-close" style="background: transparent !important; border: none !important; box-shadow: none !important; min-width: 0 !important; margin: 0 !important; font-size: 24px; cursor: pointer;">&times;</button>
        </div>

        <div class="fstu-modal-body" style="padding: 15px; overflow-y: auto; max-height: 70vh;">
            <form id="fstu-regional-form">
                <input type="text" name="fstu_website" class="fstu-hidden-field" style="display:none;" tabindex="-1" autocomplete="off">

                <input type="hidden" name="id" id="fstu-regional-id" value="0">
                <input type="hidden" name="region_id" id="fstu-regional-region-id" value="0">

                <div class="fstu-form-group" style="margin-bottom: 15px;">
                    <label for="fstu-regional-unit" style="display: block; margin-bottom: 5px; font-weight: 500;">Осередок <span style="color:red;">*</span></label>
                    <select name="unit_id" id="fstu-regional-unit" class="fstu-select" style="width: 100%;" required>
                        <option value="">-- Оберіть осередок --</option>
                        <?php if ( ! empty( $units ) ) : ?>
                            <?php foreach ( $units as $u ) : ?>
                                <option value="<?php echo esc_attr( $u['Unit_ID'] ); ?>"><?php echo esc_html( $u['Unit_ShortName'] ); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="fstu-form-group" style="margin-bottom: 15px;">
                    <label for="fstu-regional-role" style="display: block; margin-bottom: 5px; font-weight: 500;">Посада <span style="color:red;">*</span></label>
                    <select name="member_regional_id" id="fstu-regional-role" class="fstu-select" style="width: 100%;" required>
                        <option value="">-- Оберіть посаду --</option>
                        <?php if ( ! empty( $roles ) ) : ?>
                            <?php foreach ( $roles as $r ) : ?>
                                <option value="<?php echo esc_attr( $r['MemberRegional_ID'] ); ?>"><?php echo esc_html( $r['MemberRegional_Name'] ); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="fstu-form-group" style="margin-bottom: 15px;">
                    <label for="fstu-regional-user" style="display: block; margin-bottom: 5px; font-weight: 500;">Користувач (ПІБ) <span style="color:red;">*</span></label>
                    <select name="user_id" id="fstu-regional-user" class="fstu-select" style="width: 100%;" required></select>
                </div>
            </form>
        </div>

        <div class="fstu-modal-footer" style="padding: 15px; border-top: 1px solid #e5e5e5; display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" class="fstu-btn fstu-btn--cancel fstu-modal-close-btn" style="background: #e9ecef; color: #333; border: 1px solid #ccc; padding: 6px 12px; cursor: pointer;">Скасувати</button>
            <button type="submit" form="fstu-regional-form" class="fstu-btn fstu-btn--save" style="background: #d9534f; color: #fff; border: none; padding: 6px 12px; cursor: pointer;">Зберегти</button>
        </div>

    </div>
</div>