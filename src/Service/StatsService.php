<?php

require_once __DIR__ . '/../Repository/StatsRepository.php';

class StatsService {

    private $repo;

    public function __construct() {
        $this->repo = new StatsRepository();
    }

    public function getStatsByGroupId(string $groupId) {
        return $this->repo->getStatsByGroupId($groupId);
    }
}
