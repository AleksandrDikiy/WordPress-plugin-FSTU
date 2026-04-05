<?php
/**
 * View: Сторінка налаштувань плагіна (Таблиця Settings).
 *
 * @package FSTU\Admin\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Налаштування системи ФСТУ</h1>
    <hr class="wp-header-end">

    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-1">
            <div id="post-body-content">

                <div class="postbox">
                    <h2 class="hndle" style="padding: 12px 15px;"><span>Глобальні параметри</span></h2>
                    <div class="inside">

                        <form method="post" action="">
                            <?php wp_nonce_field( 'fstu_save_settings_action', 'fstu_settings_nonce' ); ?>
                            <input type="hidden" name="fstu_save_settings" value="1">

                            <table class="form-table" role="presentation">
                                <tbody>
                                <?php if ( ! empty( $settings ) ) : ?>
                                    <?php foreach ( $settings as $row ) : ?>
                                        <tr>
                                            <th scope="row">
                                                <label for="setting_<?php echo esc_attr( $row['ParamName'] ); ?>" style="font-weight: 600;">
                                                    <?php echo esc_html( $row['ParamName'] ); ?>
                                                </label>
                                            </th>
                                            <td>
                                                <input type="text"
                                                       id="setting_<?php echo esc_attr( $row['ParamName'] ); ?>"
                                                       name="settings[<?php echo esc_attr( $row['ParamName'] ); ?>]"
                                                       value="<?php echo esc_attr( $row['ParamValue'] ); ?>"
                                                       class="regular-text"
                                                       style="width: 100%; max-width: 600px;">

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