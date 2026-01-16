<?php 

require_once __DIR__ . '/../Service/StatsService.php';
require_once __DIR__ . '/../Utils/Response.php';

class StatsController {

    private $service;

    public function __construct() {
        $this->service = new StatsService();
    }

    public function getStatsByGroupId(string $groupId): void {
        $stats = $this->service->getStatsByGroupId($groupId);
        
        Response::json2(200, 'Estadísticas obtenidas exitosamente', $stats);
    }
}
