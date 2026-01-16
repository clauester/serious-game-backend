<?php
declare(strict_types=1);
require_once __DIR__ . '/../Controllers/StatsController.php';

class StatsRoutes {

    public static function handle(string $cleanUri, string $method): bool {
        $controller = new StatsController();

        // GET /stats/game/group/{id} - Endpoint para obtener estadísticas por grupo
        if (preg_match('#^/stats/game/group/([a-zA-Z0-9\-]+)$#', $cleanUri, $matches) && $method === "GET") {
            $groupId = $matches[1];
            $controller->getStatsByGroupId($groupId);
            return true;
        }

        return false;
    }
}
