<?php

declare(strict_types=1);
require_once __DIR__ . '/../Controllers/StatsController.php';

class StatsRoutes
{

    public static function handle(string $cleanUri, string $method): bool
    {
        $controller = new StatsController();

        // GET /stats/game/group/{id} - Endpoint para obtener estadísticas por grupo
        if (preg_match('#^/stats/game/group/([a-zA-Z0-9\-]+)$#', $cleanUri, $matches) && $method === "GET") {
            $groupId = $matches[1];
            $controller->getStatsByGroupId($groupId);
            return true;
        }

        // GET /stats/game/group/{groupId}/user/{userId}/game/{gameId}/report
        if (preg_match(
            '#^/stats/game/group/([a-zA-Z0-9\-]+)/user/([a-zA-Z0-9\-]+)/game/([a-zA-Z0-9\-]+)/report$#',
            $cleanUri,
            $matches
        ) && $method === "GET") {
            $groupId = $matches[1];
            $userId  = $matches[2];
            $gameId  = $matches[3];
            $controller->getUserGameReport($groupId, $userId, $gameId);
            return true;
        }

        return false;
    }
}
