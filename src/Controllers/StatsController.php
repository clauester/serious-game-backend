<?php

require_once __DIR__ . '/../Service/StatsService.php';
require_once __DIR__ . '/../Utils/Response.php';

class StatsController
{

    private $service;

    public function __construct()
    {
        $this->service = new StatsService();
    }

    public function getStatsByGroupId(string $groupId): void
    {
        $stats = $this->service->getStatsByGroupId($groupId);

        Response::json2(200, 'Estadísticas obtenidas exitosamente', $stats);
    }


    public function getUserGameReport(string $groupId, string $userId, string $gameId): void
    {
        try {
            $uuidRegex = '/^[0-9a-fA-F-]{36}$/';

            if (!preg_match($uuidRegex, $groupId)) {
                Response::json2(400, 'groupId inválido', null);
                return;
            }
            if (!preg_match($uuidRegex, $userId)) {
                Response::json2(400, 'userId inválido', null);
                return;
            }
            // no game id vacio
            if (!preg_match($uuidRegex, $gameId)) {
                Response::json2(400, 'gameId inválido', null);
                return;
            }

            $report = $this->service->getUserGameReport($groupId, $userId, $gameId);

            if (!$report) {
                Response::json2(404, 'No se encontró reporte de juego para esos parámetros', null);
                return;
            }

            Response::json2(200, 'Reporte de juego obtenido exitosamente', $report);
        } catch (Throwable $e) {
            Response::json2(500, 'Error interno del servidor', null);
        }
    }
}
