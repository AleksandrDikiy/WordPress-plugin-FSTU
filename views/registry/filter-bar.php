<?php
/**
 * View: Фільтри реєстру.
 *
 * @var array  $units
 * @var array  $tourism_types
 * @var array  $clubs
 * @var array  $years
 * @var string $current_year
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Захист від порожніх масивів, якщо контролер їх не передав
$units         = $units ?? [];
$tourism_types = $tourism_types ?? [];
$clubs         = $clubs ?? [];
$years         = $years ?? [];
$current_year  = $current_year ?? gmdate( 'Y' );
?>
<div class="fstu-filter-bar" role="search" aria-label="Фільтри реєстру">
    <div class="fstu-filter-row fstu-filter-row--main" style="align-items: center; flex-wrap: wrap; gap: 10px;">

        <div class="fstu-filter-item">
            <select id="fstu-filter-unit" name="unit_id" class="fstu-select fstu-filter-trigger" data-filter="unit_id">
                <option value="0">УСІ ОСЕРЕДКИ</option>
                <?php foreach ( $units as $unit ) : ?>
                    <option value="<?php echo absint( $unit['Unit_ID'] ); ?>"><?php echo esc_html( $unit['Unit_ShortName'] ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="fstu-filter-item">
            <select id="fstu-filter-tourism" name="tourism_type" class="fstu-select fstu-filter-trigger" data-filter="tourism_type">
                <option value="0">ВСІ ВИДИ</option>
                <?php foreach ( $tourism_types as $tt ) : ?>
                    <option value="<?php echo absint( $tt['TourismType_ID'] ); ?>"><?php echo esc_html( $tt['TourismType_Name'] ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="fstu-filter-item">
            <select id="fstu-filter-club" name="club_id" class="fstu-select fstu-filter-trigger" data-filter="club_id">
                <option value="0">ВСІ КЛУБИ</option>
                <?php foreach ( $clubs as $club ) : ?>
                    <option value="<?php echo absint( $club['Club_ID'] ); ?>"><?php echo esc_html( $club['Club_Name'] ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="fstu-filter-item">
            <select id="fstu-filter-year" name="year" class="fstu-select fstu-filter-trigger" data-filter="year">
                <?php foreach ( $years as $year ) : ?>
                    <option value="<?php echo esc_attr( $year ); ?>" <?php selected( $year, $current_year ); ?>><?php echo esc_html( $year ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="fstu-filter-item" style="padding-top: 5px;">
            <label class="fstu-checkbox-label" for="fstu-filter-fstu-only">
                <input type="checkbox" id="fstu-filter-fstu-only" name="fstu_only" class="fstu-checkbox fstu-filter-trigger" data-filter="fstu_only" value="1" checked>
                <span class="fstu-checkbox-text">ФСТУ</span>
            </label>
        </div>

        <div class="fstu-filter-item" style="position: relative; flex-grow: 1; min-width: 200px;">
            <span class="fstu-search-icon" aria-hidden="true" style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); font-size: 14px;">🔍</span>
            <input type="text" id="fstu-filter-search" class="fstu-search-input fstu-filter-trigger fstu-select" placeholder="пошук за ПІБ" style="padding-left: 28px; width: 100%;">
            <button type="button" class="fstu-search-clear fstu-hidden" id="fstu-search-clear" aria-label="Очистити пошук" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; font-size: 12px; cursor: pointer;">✕</button>
        </div>

    </div>
</div>