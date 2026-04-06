<?php
namespace FSTU\Modules\Applications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="fstu-filter-bar">
    <div class="fstu-filter-wrap">
        <label for="fstu-filter-region" class="screen-reader-text">Фільтр за областю</label>
        <select id="fstu-filter-region" class="fstu-select">
            <option value="0">ВСІ ОБЛАСТІ</option>
            <?php foreach ( $regions ?? [] as $region ) : ?>
                <option value="<?php echo esc_attr( (string) ( $region['Region_ID'] ?? '' ) ); ?>">
                    <?php echo esc_html( (string) ( $region['Region_Name'] ?? '' ) ); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="fstu-filter-unit" class="screen-reader-text">Фільтр за осередком</label>
        <select id="fstu-filter-unit" class="fstu-select">
            <option value="0">ВСІ ОСЕРЕДКИ</option>
            <?php foreach ( $units ?? [] as $unit ) : ?>
                <option
                    value="<?php echo esc_attr( (string) ( $unit['Unit_ID'] ?? '' ) ); ?>"
                    data-region-id="<?php echo esc_attr( (string) ( $unit['Region_ID'] ?? '0' ) ); ?>"
                >
                    <?php echo esc_html( (string) ( $unit['Unit_ShortName'] ?? '' ) ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>