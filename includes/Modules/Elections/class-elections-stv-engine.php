<?php
/**
 * Математичне ядро підрахунку голосів за системою STV (Квота Друпа).
 * * Version: 1.0.0
 * Date_update: 2026-04-22
 */

namespace FSTU\Modules\Elections;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Elections_STV_Engine {

    private int $election_id;
    private int $seats;
    private array $candidates = [];
    private array $ballots = [];
    private int $quota = 0;
    private array $regional_counts = [];

    public function __construct( int $election_id, int $seats ) {
        $this->election_id = $election_id;
        $this->seats       = $seats;
    }

    /**
     * Запускає процес підрахунку та повертає масив з результатами.
     */
    public function calculate(): array {
        $this->load_data();

        $total_valid_votes = count( $this->ballots );
        if ( $total_valid_votes === 0 || empty( $this->candidates ) ) {
            return [ 'error' => 'Немає даних для підрахунку' ];
        }

        // Розрахунок Квоти Друпа: floor(Votes / (Seats + 1)) + 1
        $this->quota = (int) floor( $total_valid_votes / ( $this->seats + 1 ) ) + 1;

        $elected   = [];
        $eliminated = [];
        $rounds_log = [];
        $exhausted = 0;

        $round = 1;

        // Головний цикл STV
        while ( count( $elected ) < $this->seats && count( $this->candidates ) > 0 ) {
            // Скидаємо поточні голоси кандидатів
            foreach ( $this->candidates as &$c ) {
                $c['votes'] = 0.0;
            }
            unset($c);

            // Розподіл голосів
            foreach ( $this->ballots as &$ballot ) {
                if ( $ballot['exhausted'] ) continue;

                $counted = false;
                foreach ( $ballot['preferences'] as $pref_id ) {
                    // Якщо кандидат ще в грі (не обраний і не вибув)
                    if ( isset( $this->candidates[ $pref_id ] ) ) {
                        $this->candidates[ $pref_id ]['votes'] += $ballot['weight'];
                        $counted = true;
                        break;
                    }
                }
                if ( ! $counted ) {
                    $ballot['exhausted'] = true;
                    $exhausted += $ballot['weight'];
                }
            }
            unset($ballot);

            $round_stats = [
                'round'     => $round,
                'exhausted' => $exhausted,
                'actions'   => []
            ];

            // Сортуємо кандидатів за кількістю голосів (від найбільшого до найменшого)
            uasort( $this->candidates, fn($a, $b) => $b['votes'] <=> $a['votes'] );

            $action_taken = false;

            // КРОК 1: Перевірка переможців (хто досяг квоти)
            foreach ( $this->candidates as $id => $c ) {
                if ( $c['votes'] >= $this->quota ) {
                    // Перевірка регіонального ліміту (макс 2 від регіону)
                    $reg_id = $c['region_id'];
                    $current_reg_count = $this->regional_counts[ $reg_id ] ?? 0;

                    if ( $current_reg_count >= 2 ) {
                        // Ліміт вичерпано -> примусове вибування
                        $eliminated[] = $c;
                        unset( $this->candidates[ $id ] );
                        $round_stats['actions'][] = "Кандидат {$c['name']} досяг квоти, але вибув через регіональний ліміт.";
                    } else {
                        // Оголошується переможцем
                        $elected[] = $c;
                        $this->regional_counts[ $reg_id ] = $current_reg_count + 1;
                        $surplus = $c['votes'] - $this->quota;
                        $transfer_value = $surplus > 0 ? ( $surplus / $c['votes'] ) : 0;

                        // Зменшуємо вагу бюлетенів, які голосували за нього
                        if ( $transfer_value > 0 ) {
                            foreach ( $this->ballots as &$ballot ) {
                                if ( ! $ballot['exhausted'] && current( array_filter( $ballot['preferences'], fn($p) => isset($this->candidates[$p]) || $p === $id ) ) === $id ) {
                                    $ballot['weight'] *= $transfer_value;
                                }
                            }
                            unset($ballot);
                        }

                        unset( $this->candidates[ $id ] );
                        $round_stats['actions'][] = "Кандидат {$c['name']} ОБРАНИЙ. Голосів: " . round($c['votes'], 2) . ". Надлишок: " . round($surplus, 2) . " передано далі.";
                    }
                    $action_taken = true;
                    break; // Обробляємо по одному за раунд
                }
            }

            // КРОК 2: Елімінація аутсайдера (якщо ніхто не досяг квоти)
            if ( ! $action_taken && count( $this->candidates ) > 0 ) {
                // Якщо кандидатів залишилося стільки ж, скільки вакантних місць - всі вони автоматично перемагають
                if ( count( $this->candidates ) <= ( $this->seats - count( $elected ) ) ) {
                    foreach ( $this->candidates as $id => $c ) {
                        $elected[] = $c;
                        $round_stats['actions'][] = "Кандидат {$c['name']} ОБРАНИЙ автоматично (вакантних місць достатньо).";
                    }
                    $this->candidates = [];
                } else {
                    // Знаходимо того, у кого найменше голосів
                    $lowest_id = array_key_last( $this->candidates );
                    $lowest_c  = $this->candidates[ $lowest_id ];
                    $eliminated[] = $lowest_c;
                    unset( $this->candidates[ $lowest_id ] );
                    $round_stats['actions'][] = "Кандидат {$lowest_c['name']} ВИБУВ (найменша кількість голосів: " . round($lowest_c['votes'], 2) . "). Його голоси передано далі.";
                }
            }

            $rounds_log[] = $round_stats;
            $round++;
        }

        return [
            'quota'      => $this->quota,
            'turnout'    => $total_valid_votes,
            'exhausted'  => round( $exhausted, 2 ),
            'elected'    => $elected,
            'eliminated' => $eliminated,
            'rounds'     => $rounds_log,
        ];
    }

    private function load_data(): void {
        global $wpdb;

        // Завантаження підтверджених кандидатів (Без SQL-групування)
        $candidates_raw = $wpdb->get_results( $wpdb->prepare(
            "SELECT c.Candidate_ID, u.FIO, u.Region_ID, u.Region_Name 
			 FROM Election_Candidates c
			 JOIN vUserFSTUnew u ON u.User_ID = c.User_ID
			 WHERE c.Election_ID = %d AND c.Status = 'confirmed'",
            $this->election_id
        ), ARRAY_A );

        // Дедуплікація засобами PHP
        $seen = [];
        if ( is_array( $candidates_raw ) ) {
            foreach ( $candidates_raw as $row ) {
                if ( ! isset( $seen[ $row['Candidate_ID'] ] ) ) {
                    $seen[ $row['Candidate_ID'] ] = true;
                    $this->candidates[ (int) $row['Candidate_ID'] ] = [
                        'id'     => (int) $row['Candidate_ID'],
                        'name'   => $row['FIO'],
                        'region' => $row['Region_ID'],
                        'region_name' => $row['Region_Name'],
                        'votes'  => 0.0,
                        'status' => 'hopeful'
                    ];
                }
            }
        }

        // Завантаження бюлетенів
        $ballots_raw = $wpdb->get_results( $wpdb->prepare(
            "SELECT Preferences_JSON FROM Election_Ballots WHERE Election_ID = %d",
            $this->election_id
        ), ARRAY_A );

        foreach ( $ballots_raw as $b ) {
            $prefs = json_decode( $b['Preferences_JSON'], true );
            if ( is_array( $prefs ) && ! empty( $prefs ) ) {
                $this->ballots[] = [
                    'weight'      => 1.0,
                    'exhausted'   => false,
                    'preferences' => array_map( 'intval', $prefs )
                ];
            }
        }
    }
}