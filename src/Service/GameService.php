<?php

require_once __DIR__ . '/../Repository/GameRepository.php';

class GameService
{
    private $repo;

    public function __construct()
    {
        $this->repo = new GameRepository();
    }

    public function getAllGames(string $action)
    {
        return $this->repo->getAllGames($action);
    }

    public function createGame(
        string $action,
        ?string $user_id,
        ?string $group_id,
        ?string $status,
        ?int $grade,
        ?string $gameId
    ) {
        // Lógica para crear un juego
        return $this->repo->createGame($action, $user_id, $group_id, $status, $grade, $gameId);
    }
}
