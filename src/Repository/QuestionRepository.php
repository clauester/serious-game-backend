<?php

require_once __DIR__ . '/../config/database.php';

class QuestionRepository {
    private $pdo;

    public function __construct() {
        $this->pdo = (new Database())->connect();
    }

    public function getAllQuestions( ) {
        
        $stmt = $this->pdo->prepare("CALL sp_get_users()");

        $stmt->execute();
        $users = $stmt->fetchAll();
        $stmt->closeCursor();

        return $users;
    }

    public function createQuestion(
        string $title,
        ?string $description,
        string $typeName,
        string $optionText,
        bool $isCorrect) {

        $stmt = $this->pdo->prepare("CALL sp_create_user(?,?)");
        $stmt->bindParam(1, $title);
        $stmt->bindParam(2, $description);
        $stmt->bindParam(3, $typeName);
        $stmt->bindParam(4, $optionText);
        $stmt->bindParam(5, $isCorrect, PDO::PARAM_BOOL);
        $stmt->execute();

        $stmt->closeCursor(); // tu SP debe retornar el nuevo usuario
    }

}
