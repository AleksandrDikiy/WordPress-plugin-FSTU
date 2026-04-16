<?php
/**
 * Бізнес-логіка модуля Presidium.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-16
 *
 * @package FSTU\Modules\Presidium
 */

namespace FSTU\Modules\Presidium;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Presidium_Service {

    private Presidium_Repository $repository;

    public function __construct() {
        $this->repository = new Presidium_Repository();
    }

    public function get_repository(): Presidium_Repository {
        return $this->repository;
    }

    /**
     * Отримання списку членів для фронтенду.
     */
    public function get_members_list(): array {
        return $this->repository->get_members();
    }
}