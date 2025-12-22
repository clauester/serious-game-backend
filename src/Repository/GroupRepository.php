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
    ){
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

    //get group questions by group id
    public function getQuestionsToAdd($accion, $group_id, $question_id) {
        $stmt = $this->pdo->prepare("CALL sp_question_group_accion(:accion_name, :p_group_id, :p_question_id)");
        $stmt->bindParam(":accion_name", $accion);
        $stmt->bindParam(":p_group_id", $group_id);
        $stmt->bindParam(":p_question_id", $question_id);

        $stmt->execute();
        $questions = $stmt->fetchAll();
        $stmt->closeCursor();

        return $questions;
    }

    //insert array of questions to group
    public function registerGroupQuestions(string $groupId, array $questionIds, array $deleteIds): array
    {
        if (empty($questionIds) && empty($deleteIds)) {
            throw new InvalidArgumentException(
                'Debe enviar preguntas para insertar o eliminar'
            );
        }


        $deleteCsv = empty($deleteIds) ? null : implode(',', $deleteIds);
        $questionsCsv = empty($questionIds) ? null : implode(',', $questionIds);


    try {
        $stmt = $this->pdo->prepare(
            'CALL sp_register_group_questions_bulk(:group_id, :question_ids, :delete_ids)'
        );

        $stmt->bindValue(':group_id', $groupId, PDO::PARAM_STR);
        $stmt->bindValue(':question_ids', $questionsCsv, PDO::PARAM_STR);
        $stmt->bindValue(':delete_ids', $deleteCsv, PDO::PARAM_STR);


        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return $result;

    } catch (PDOException $e) {
    if ($e->getCode() === '45000') {
        throw new RuntimeException($e->getMessage());
    }

    throw new RuntimeException(
        'Error al registrar preguntas en el grupo',
        0,
        $e
    );
    }
}

public function deactivateGroup(string $groupId)
    {
        $stmt = $this->pdo->prepare('CALL sp_delete_group(:p_group_id)');
        $stmt->bindParam(':p_group_id', $groupId, PDO::PARAM_STR);
        $stmt->execute();
        
        $stmt->closeCursor();
        return $stmt->fetch();
    }

}
