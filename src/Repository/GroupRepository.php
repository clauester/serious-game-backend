<?php

require_once __DIR__ . '/../config/database.php';

class GroupRepository {
    private $pdo;

    public function __construct() {
        $this->pdo = (new Database())->connect();
    }

    public function createGroup(
        string $code,
        string $name,
        string $description
    ) {
        $stmt = $this->pdo->prepare("CALL sp_create_game_group(:p_name,:p_description, :p_code)");
        $stmt->bindParam(":p_code", $code);
        $stmt->bindParam(":p_name", $name);
        $stmt->bindParam(":p_description", $description);
        $stmt->execute();

        return $stmt->fetch(); // tu SP debe retornar el nuevo grupo usuario
    }


    public function getAllGroups() {
        $stmt = $this->pdo->prepare("CALL sp_list_game_groups()");
        $stmt->execute();
        $groups = $stmt->fetchAll();
        $stmt->closeCursor();

        return $groups;
    }

    //get group questions by group id
    public function getGroupQuestions($groupId) {
        $stmt = $this->pdo->prepare("CALL sp_get_group_questions(:p_id)");
        $stmt->bindParam(":p_id", $groupId);
        $stmt->execute();
        $questions = $stmt->fetchAll();
        $stmt->closeCursor();

        return $questions;
    }

}
