<?php
/**
 * Бізнес-логіка модуля Directory.
 *
 * Version:     1.0.0
 * Date_update: 2026-04-16
 *
 * @package FSTU\Modules\Directory
 */

namespace FSTU\Modules\Directory;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Directory_Service {

    private Directory_Repository $repository;

    public function __construct() {
        $this->repository = new Directory_Repository();
    }
    /**
     * Отримання екземпляра репозиторію.
     *
     * @return Directory_Repository
     */
    public function get_repository(): Directory_Repository {
        return $this->repository;
    }

}