<?php
namespace FSTU\Modules\Dictionaries;

use FSTU\Dictionaries\Commission\Commission_List;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Клас-контролер для Хабу довідників (Dashboard).
 * * Version:     1.5.0
 * Date_update: 2026-04-06
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
        $typeguidance_list_class = 'FSTU\\Dictionaries\\TypeGuidance\\TypeGuidance_List';
        $member_regional_list_class = 'FSTU\\Dictionaries\\MemberRegional\\Member_Regional_List';
        $member_guidance_list_class = 'FSTU\\Dictionaries\\MemberGuidance\\Member_Guidance_List';
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

        return [
            'structure' => [
                'title' => 'Структура та Кадри ФСТУ',
                'icon'  => '🏢',
                'items' => [
                    [ 'title' => 'Осередки ФСТУ', 'desc' => 'Довідник регіональних осередків', 'url' => '/adm/Unit/' ],
                    [ 'title' => 'Клуби', 'desc' => 'Довідник туристичних клубів', 'url' => '/adm/Club/' ],
                    [ 'title' => 'Комісії та колегії', 'desc' => 'Довідник комісій ФСТУ', 'url' => $commission_url ],
                              [ 'title' => 'Керівні органи ФСТУ', 'desc' => 'Довідник керівних органів', 'url' => $typeguidance_url ],
                          [ 'title' => 'Посади у керівних органах', 'desc' => 'Довідник посад', 'url' => $member_guidance_url ],
                                                  [ 'title' => 'Посади федерацій', 'desc' => 'Довідник регіональних посад', 'url' => $member_regional_url ],
                ],
            ],
            'geography' => [
                'title' => 'Географія',
                'icon'  => '🌍',
                'items' => [
                    [ 'title' => 'Країни', 'desc' => 'Довідник країн', 'url' => '/adm/Country/' ],
                    [ 'title' => 'Області', 'desc' => 'Довідник областей України', 'url' => '/adm/Region/' ],
                    [ 'title' => 'Населені пункти', 'desc' => 'Довідник міст та сіл', 'url' => '/adm/City/' ],
                ],
            ],
            'sports_events' => [
                'title' => 'Спорт, Заходи та Походи',
                'icon'  => '🏆',
                'items' => [
                    [ 'title' => 'Види туризму', 'desc' => 'Довідник видів туризму', 'url' => '/adm/tourismtype/' ],
                    [ 'title' => 'Види змагань', 'desc' => 'Довідник видів змагань', 'url' => '/adm/typeevent/' ],
                    [ 'title' => 'Типи заходів', 'desc' => 'Довідник типів заходів', 'url' => '/adm/EventType/' ],
                    [ 'title' => 'Види участі в заходах', 'desc' => 'Довідник видів участі', 'url' => '/adm/ParticipationType/' ],
                    [ 'title' => 'Види походів', 'desc' => 'Довідник видів походів', 'url' => '/adm/tourtype/' ],
                    [ 'title' => 'Категорії походів', 'desc' => 'Довідник категорій походів', 'url' => '/adm/HikingCategory/' ],
                    [ 'title' => 'Види складності походів', 'desc' => 'Довідник складності', 'url' => '/adm/HourCategories/' ],
                    [ 'title' => 'Спортивні розряди', 'desc' => 'Довідник спортивних розрядів', 'url' => '/adm/SportsCategories/' ],
                    [ 'title' => 'Суддівські категорії', 'desc' => 'Довідник суддівських категорій', 'url' => '/adm/RefereeCategory/' ],
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