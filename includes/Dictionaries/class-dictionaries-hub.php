<?php
namespace FSTU\Modules\Dictionaries;

use FSTU\Dictionaries\Commission\Commission_List;
use FSTU\Dictionaries\Clubs\Clubs_List;
use FSTU\Dictionaries\RefereeCategory\RefereeCategory_List;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Клас-контролер для Хабу довідників (Dashboard).
 * Version:     1.14.1
 * Date_update: 2026-04-13
 */
class Dictionaries_Hub {

    public function init(): void {
        add_shortcode( 'fstu_dictionaries_hub', [ $this, 'render_shortcode' ] );
    }

    public function render_shortcode( array $atts = [] ): string {
        $this->enqueue_assets();

        $categories = $this->get_dictionaries_data();

        ob_start();
        include FSTU_PLUGIN_DIR . 'views/dictionaries/hub-page.php';
        return ob_get_clean();
    }

    private function enqueue_assets(): void {
        wp_enqueue_style(
            'fstu-dictionaries-hub',
            FSTU_PLUGIN_URL . 'css/fstu-dictionaries-hub.css',
            [], // Завжди пустий масив для незалежності модуля
            FSTU_VERSION
        );

        wp_enqueue_script(
            'fstu-dictionaries-hub',
            FSTU_PLUGIN_URL . 'js/fstu-dictionaries-hub.js',
            [ 'jquery' ],
            FSTU_VERSION,
            true
        );
    }

    /**
     * Повний масив усіх 21+ довідників системи, розбитий по категоріях.
     */
    private function get_dictionaries_data(): array {
        $guidance_list_class = 'FSTU\\Modules\\Registry\\Guidance\\Guidance_List';
        $typeguidance_list_class = 'FSTU\\Dictionaries\\TypeGuidance\\TypeGuidance_List';
        $member_regional_list_class = 'FSTU\\Dictionaries\\MemberRegional\\Member_Regional_List';
        $member_guidance_list_class = 'FSTU\\Dictionaries\\MemberGuidance\\Member_Guidance_List';
        $country_list_class = 'FSTU\\Dictionaries\\Country\\Country_List';
        $region_list_class = 'FSTU\\Dictionaries\\Region\\Region_List';
        $city_list_class = 'FSTU\\Dictionaries\\City\\City_List';
            $clubs_list_class = Clubs_List::class;
        $tourtype_list_class = 'FSTU\\Dictionaries\\TourType\\TourType_List';
            $hikingcategory_list_class = 'FSTU\\Dictionaries\\HikingCategory\\HikingCategory_List';
              $hourcategories_list_class = 'FSTU\\Dictionaries\\HourCategories\\HourCategories_List';
                  $sportscategories_list_class = 'FSTU\\Dictionaries\\SportsCategories\\SportsCategories_List';
        		$referee_category_list_class = RefereeCategory_List::class;
            $participationtype_list_class = 'FSTU\\Dictionaries\\ParticipationType\\ParticipationType_List';
        $commission_url = class_exists( Commission_List::class )
            ? Commission_List::get_module_url( 'hub' )
          : '';
        $typeguidance_url = class_exists( $typeguidance_list_class )
          ? $typeguidance_list_class::get_module_url( 'hub' )
          : '';
        $member_regional_url = class_exists( $member_regional_list_class )
          ? $member_regional_list_class::get_module_url( 'hub' )
          : '';
            $member_guidance_url = class_exists( $member_guidance_list_class )
              ? $member_guidance_list_class::get_module_url( 'hub' )
              : '';
                    $guidance_url = class_exists( $guidance_list_class )
                      ? $guidance_list_class::get_module_url( 'hub' )
                      : '';
        $country_url = class_exists( $country_list_class )
          ? $country_list_class::get_module_url( 'hub' )
          : '';
            $clubs_url = class_exists( $clubs_list_class )
              ? $clubs_list_class::get_module_url( 'hub' )
              : '';
        $region_url = class_exists( $region_list_class )
          ? $region_list_class::get_module_url( 'hub' )
          : '';
        $city_url = class_exists( $city_list_class )
          ? $city_list_class::get_module_url( 'hub' )
          : '';
            $tourtype_url = class_exists( $tourtype_list_class )
              ? $tourtype_list_class::get_module_url( 'hub' )
              : '';
              $hikingcategory_url = class_exists( $hikingcategory_list_class )
                ? $hikingcategory_list_class::get_module_url( 'hub' )
                : '';
                      $hourcategories_url = class_exists( $hourcategories_list_class )
                        ? $hourcategories_list_class::get_module_url( 'hub' )
                        : '';
                          $sportscategories_url = class_exists( $sportscategories_list_class )
                            ? $sportscategories_list_class::get_module_url( 'hub' )
                            : '';
            $referee_category_url = class_exists( $referee_category_list_class )
              ? $referee_category_list_class::get_module_url( 'hub' )
              : '';
            $participationtype_url = class_exists( $participationtype_list_class )
              ? $participationtype_list_class::get_module_url( 'hub' )
              : '';

        if ( '' === $commission_url ) {
          $commission_url = '#';
        }

        if ( '' === $typeguidance_url ) {
          $typeguidance_url = '#';
        }

            if ( '' === $member_regional_url ) {
              $member_regional_url = '#';
            }

                if ( '' === $member_guidance_url ) {
                  $member_guidance_url = '#';
                }

                    if ( '' === $guidance_url ) {
                      $guidance_url = '#';
                    }

        if ( '' === $country_url ) {
          $country_url = '#';
        }

            if ( '' === $clubs_url ) {
              $clubs_url = '/adm/Club/';
            }

        if ( '' === $region_url ) {
          $region_url = '#';
        }
        if ( '' === $city_url ) {
          $city_url = '#';
        }

            if ( '' === $tourtype_url ) {
              $tourtype_url = '/adm/tourtype/';
            }

            if ( '' === $participationtype_url ) {
              $participationtype_url = '/adm/ParticipationType/';
            }

              if ( '' === $hikingcategory_url ) {
                $hikingcategory_url = '/adm/HikingCategory/';
              }

                    if ( '' === $hourcategories_url ) {
                    $hourcategories_url = '/adm/HourCategories/';
                    }

                                if ( '' === $sportscategories_url ) {
                                $sportscategories_url = '/adm/SportsCategories/';
                                }

                        if ( '' === $referee_category_url ) {
                          $referee_category_url = '/adm/RefereeCategory/';
                        }

        return [
            'structure' => [
                'title' => 'Структура та Кадри ФСТУ',
                'icon'  => '🏢',
                'items' => [
                    [ 'title' => 'Осередки ФСТУ', 'desc' => 'Довідник регіональних осередків', 'url' => '/adm/Unit/' ],
                              [ 'title' => 'Клуби', 'desc' => 'Довідник туристичних клубів', 'url' => $clubs_url ],
                    [ 'title' => 'Комісії та колегії', 'desc' => 'Довідник комісій ФСТУ', 'url' => $commission_url ],
                          [ 'title' => 'Склад керівних органів ФСТУ', 'desc' => 'Реєстр складу керівних органів', 'url' => $guidance_url ],
                              [ 'title' => 'Керівні органи ФСТУ', 'desc' => 'Довідник керівних органів', 'url' => $typeguidance_url ],
                          [ 'title' => 'Посади у керівних органах', 'desc' => 'Довідник посад', 'url' => $member_guidance_url ],
                                                  [ 'title' => 'Посади федерацій', 'desc' => 'Довідник регіональних посад', 'url' => $member_regional_url ],
                ],
            ],
            'geography' => [
                'title' => 'Географія',
                'icon'  => '🌍',
                'items' => [
                    [ 'title' => 'Країни', 'desc' => 'Довідник країн', 'url' => $country_url ],
                    [ 'title' => 'Області', 'desc' => 'Довідник областей України', 'url' => $region_url ],
                    [ 'title' => 'Населені пункти', 'desc' => 'Довідник міст та сіл', 'url' => $city_url ],
                ],
            ],
            'sports_events' => [
                'title' => 'Спорт, Заходи та Походи',
                'icon'  => '🏆',
                'items' => [
                    [ 'title' => 'Види туризму', 'desc' => 'Довідник видів туризму', 'url' => '/adm/tourismtype/' ],
                    [ 'title' => 'Види змагань', 'desc' => 'Довідник видів змагань', 'url' => '/adm/typeevent/' ],
                    [ 'title' => 'Типи заходів', 'desc' => 'Довідник типів заходів', 'url' => '/adm/EventType/' ],
                              [ 'title' => 'Види участі в заходах', 'desc' => 'Довідник видів участі', 'url' => $participationtype_url ],
                          [ 'title' => 'Види походів', 'desc' => 'Довідник видів походів', 'url' => $tourtype_url ],
                              [ 'title' => 'Категорії походів', 'desc' => 'Довідник категорій походів', 'url' => $hikingcategory_url ],
                              [ 'title' => 'Види складності походів', 'desc' => 'Довідник складності походів', 'url' => $hourcategories_url ],
                              [ 'title' => 'Спортивні розряди', 'desc' => 'Довідник спортивних розрядів', 'url' => $sportscategories_url ],
                      [ 'title' => 'Суддівські категорії', 'desc' => 'Довідник суддівських категорій', 'url' => $referee_category_url ],
                ],
            ],
            'cards_tickets' => [
                'title' => 'Квитки та Картки',
                'icon'  => '🪪',
                'items' => [
                    [ 'title' => 'Типи квитків (карток)', 'desc' => 'Довідник типів карток', 'url' => '/adm/typecard/' ],
                    [ 'title' => 'Статуси карток та квитків', 'desc' => 'Довідник статусів', 'url' => '/adm/StatusCard/' ],
                ],
            ],
            'fleet' => [
                'title' => 'Судновий реєстр ФСТУ',
                'icon'  => '⛵',
                'items' => [
                    [ 'title' => 'Типи вітрильних залікових груп', 'desc' => 'Довідник типів вітрильних груп', 'url' => '/adm/sailgrouptype/' ],
                    [ 'title' => 'Типи суден', 'desc' => 'Катамарани, тримарани, байдарки тощо', 'url' => '?dict=boat-types' ],
                    [ 'title' => 'Виробники', 'desc' => 'Заводи та приватні майстерні', 'url' => '?dict=producers' ],
                    [ 'title' => 'Типи корпусу', 'desc' => 'Надувні, жорсткі, комбіновані', 'url' => '?dict=hull-types' ],
                    [ 'title' => 'Конструкції', 'desc' => 'Типи конструкції суден', 'url' => '?dict=constructions' ],
                    [ 'title' => 'Матеріали корпусу', 'desc' => 'ПВХ, гума, пластик', 'url' => '?dict=materials' ],
                    [ 'title' => 'Кольори', 'desc' => 'Довідник кольорів корпусу', 'url' => '?dict=colors' ],
                    [ 'title' => 'Статуси верифікації', 'desc' => 'Стадії обробки суднових квитків', 'url' => '?dict=verification' ],
                ],
            ],
        ];
    }
}