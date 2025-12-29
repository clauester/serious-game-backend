<?php
declare(strict_types=1);
require_once __DIR__ . '/../Controllers/GameController.php';

class GameRoutes {

    public static function handle(string $cleanUri, string $method): bool {
        $controller = new GameController();

         // GET /games  - Endpoint para obtener todos los juegos
        if ($cleanUri === "/games" && $method === "GET") {

            $controller->getAllGames();
            return true;
        }

        // POST /games/save - Endpoint para crear un nuevo juego
        if ($cleanUri === "/games/save" && $method === "POST") {
            $controller->createGame();
            return true;
        }


        return false;
    }
}