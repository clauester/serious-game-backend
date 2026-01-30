
<?php

require_once __DIR__ . '/../Service/GameService.php';

class GameController
{

    private $service;

    public function __construct()
    {
        $this->service = new GameService();
    }

    public function getAllGames(): void
    {
        $action = 'SEL';
        $games = $this->service->getAllGames($action);

        Response::json2(200, 'Juegos obtenidos exitosamente', $games);
    }

    public function createGame(): void
    {
        try {

            $data = json_decode(file_get_contents("php://input"), true);
            $gameId = $data['game_id'] ?? null;
            $action = $data['action'] ?? 'INS';
            $user_id = $data['user_id'] ?? null;
            $group_id = $data['group_id'] ?? null;
            $status = $data['status'] ?? null;
            $grade = (int)($data['grade'] ?? 0);

            $newGame = $this->service->createGame($action, $user_id, $group_id, $status, $grade, $gameId);

            Response::json2(201, 'Juego creado exitosamente', $newGame);
        } catch (RuntimeException $e) {

            // extraer mensaje relevante del error de MySQL
            $errorMessage = $e->getMessage();
            if (preg_match('/GM_[A-Z_]+:.*/', $errorMessage, $matches)) {
                $errorMessage = $matches[0];
            }

            Response::json2(
                409, // conflicto lógico
                $errorMessage,
                null
            );
        } catch (Throwable $e) {
            Response::json2(
                500,
                'Error interno del servidor',
                null
            );
        }
    }
}
