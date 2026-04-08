<?php
/**
 * View: Сторінка налаштувань плагіна (Таблиця Settings).
 *
 * Version:     1.2.0
 * Date_update: 2026-04-07
 *
 * @package FSTU\Admin\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap fstu-admin-settings">
    <h1 class="wp-heading-inline">Налаштування системи ФСТУ</h1>
    <hr class="wp-header-end">

    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-1">
            <div id="post-body-content">

                <div class="postbox">
                    <h2 class="hndle fstu-admin-settings__heading"><span>Глобальні параметри</span></h2>
                    <div class="inside">

                        <form method="post" action="">
                            <?php wp_nonce_field( 'fstu_save_settings_action', 'fstu_settings_nonce' ); ?>
                            <input type="hidden" name="fstu_save_settings" value="1">

                            <table class="form-table" role="presentation">
                                <tbody>
                                <?php if ( ! empty( $settings ) ) : ?>
                                    <?php foreach ( $settings as $row ) : ?>
                                        <?php
                                        $param_name   = (string) ( $row['ParamName'] ?? '' );
                                        $param_value  = (string) ( $row['ParamValue'] ?? '' );
                                        $is_long_text = (bool) preg_match( '/^SailboatsMailBody|^SailboatsMailSignature$/', $param_name );
                                        ?>
                                        <tr>
                                            <th scope="row">
                                                <label for="setting_<?php echo esc_attr( $param_name ); ?>" class="fstu-admin-settings__field-label">
                                                    <?php echo esc_html( $param_name ); ?>
                                                </label>
                                            </th>
                                            <td>
                                                <?php if ( $is_long_text ) : ?>
                                                    <textarea
                                                        id="setting_<?php echo esc_attr( $param_name ); ?>"
                                                        name="settings[<?php echo esc_attr( $param_name ); ?>]"
                                                        class="large-text code fstu-admin-settings__textarea"
                                                        rows="8"
                                                    ><?php echo esc_textarea( $param_value ); ?></textarea>
                                                <?php else : ?>
                                                    <input type="text"
                                                           id="setting_<?php echo esc_attr( $param_name ); ?>"
                                                           name="settings[<?php echo esc_attr( $param_name ); ?>]"
                                                           value="<?php echo esc_attr( $param_value ); ?>"
                                                           class="regular-text fstu-admin-settings__input">
                                                <?php endif; ?>

                                                <?php if ( ! empty( $row['Description'] ) ) : ?>
                                                    <p class="description"><?php echo esc_html( $row['Description'] ); ?></p>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="2">Таблиця Settings порожня або не знайдена в базі даних.</td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>

                            <p class="submit">
                                <button type="submit" class="button button-primary">Зберегти зміни</button>
                            </p>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>