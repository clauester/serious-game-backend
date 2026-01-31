<?php

require_once __DIR__ . '/../Config/database.php';

class GameRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = (new Database())->connect();
    }


    public function getAllGames(string $action)
    {
        $stmt = $this->pdo->prepare("CALL sp_game_crud(?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bindParam(1, $action);
        $stmt->bindValue(2, null);
        $stmt->bindValue(3, null);
        $stmt->bindValue(4, null);
        $stmt->bindValue(5, null);
        $stmt->bindValue(6, null);
        $stmt->bindValue(7, null);
        $stmt->bindValue(8, null);
        $stmt->execute();
        $games = $stmt->fetchAll();
        $stmt->closeCursor();

        return $games;
    }

    /* Create a new game
        IN p_action   VARCHAR(10),     -- SEL | SEL_ID | INS | UPD | DEL
        IN p_id       CHAR(36),         -- usado en SEL_ID, UPD, DEL
        IN p_user_id  CHAR(36),
        IN p_group_id CHAR(36),
        IN p_status   VARCHAR(100),
        IN p_grade    INT,
        IN p_started_on  DATETIME,
        IN p_finished_on DATETIME
    */

    public function createGame(
        string $action,
        ?string $user_id,
        ?string $group_id,
        ?string $status,
        ?int $grade,
        ?string $gameId

    ) {
        $stmt = $this->pdo->prepare("CALL sp_game_crud(?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bindParam(1, $action);
        $stmt->bindParam(2, $gameId); // p_id is null for create
        $stmt->bindParam(3, $user_id);
        $stmt->bindParam(4, $group_id);
        $stmt->bindParam(5, $status);
        $stmt->bindParam(6, $grade);
        $stmt->bindValue(7, null);
        $stmt->bindValue(8, null);
        $stmt->execute();

        return $stmt->fetch(); // return the created game
    }
}
