<?php
/**
 * Таблиця списку посад федерацій.
 *
 * Version:     1.1.0
 * Date_update: 2026-04-18
 *
 * @package FSTU\Dictionaries\MemberRegional
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$permissions     = isset( $permissions ) && is_array( $permissions ) ? $permissions : [];
$show_admin_meta = ! empty( $permissions['canAdminMeta'] );
$can_manage      = ! empty( $permissions['canManage'] );
$colspan         = ( $show_admin_meta ? 5 : 3 ) + ( $can_manage ? 1 : 0 );
?>

<div class="fstu-table-wrap">
    <table class="fstu-table">
        <thead class="fstu-thead">
        <tr>
            <?php if ( $can_manage ) : ?>
                <th class="fstu-th fstu-th--drag" style="width: 40px;"></th>
            <?php endif; ?>
            <th class="fstu-th fstu-th--num">#</th>
            <th class="fstu-th fstu-th--wide-name">
                <div class="fstu-th-with-search">
                    <span><?php esc_html_e( 'Найменування', 'fstu' ); ?></span>
                    <input
                            type="text"
                            id="fstu-member-regional-search"
                            class="fstu-input--in-header"
                            placeholder="<?php esc_attr_e( '🔍 Пошук...', 'fstu' ); ?>"
                            aria-label="<?php esc_attr_e( 'Пошук за найменуванням', 'fstu' ); ?>"
                            autocomplete="off"
                    >
                </div>
            </th>
            <?php if ( $show_admin_meta ) : ?>
                <th class="fstu-th fstu-th--date"><?php esc_html_e( 'Дата/час', 'fstu' ); ?></th>
                <th class="fstu-th fstu-th--user"><?php esc_html_e( 'Хто додав запис', 'fstu' ); ?></th>
            <?php endif; ?>
            <th class="fstu-th fstu-th--actions"><?php esc_html_e( 'ДІЇ', 'fstu' ); ?></th>
        </tr>
        </thead>
        <tbody class="fstu-tbody" id="fstu-member-regional-tbody">
        <tr class="fstu-row">
            <td colspan="<?php echo esc_attr( (string) $colspan ); ?>" class="fstu-no-results"><?php esc_html_e( 'Завантаження...', 'fstu' ); ?></td>
        </tr>
        </tbody>
    </table>
</div>