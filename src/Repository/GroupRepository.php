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
        string $description,
        string $created_by
    ){
        try {
            $stmt = $this->pdo->prepare("CALL sp_create_game_group(:p_name,:p_description, :p_code, :p_created_by)");
            $stmt->bindParam(":p_code", $code);
            $stmt->bindParam(":p_name", $name);
            $stmt->bindParam(":p_description", $description);
            $stmt->bindValue(":p_created_by", $created_by);

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            return $result;
            
        } catch (PDOException $e) {
            if ($e->getCode() === '45000') {
                throw new RuntimeException($e->getMessage());
            }
            
            throw new RuntimeException(
                'Error al crear el grupo: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }


    public function getAllGroups() {
        try {
            $stmt = $this->pdo->prepare("CALL sp_list_game_groups()");
            $stmt->execute();
            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            return $groups;
            
        } catch (PDOException $e) {
            if ($e->getCode() === '45000') {
                throw new RuntimeException($e->getMessage());
            }
            
            throw new RuntimeException(
                'Error al obtener la lista de grupos: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function searchGroups(?string $query, ?string $status = null)
    {
        try {
            $trimmed = $query === null ? null : trim($query);

            // Si no se envía query
            if ($trimmed === null || $trimmed === '') {
                // Si tampoco se envía status, devolver todos
                if ($status === null) {
                    return $this->getAllGroups();
                }

                // Si se envía sólo status, devolver grupos con ese status
                $sqlStatusOnly = "SELECT id, name, description, created_on, code, status, created_by
                    FROM game_group
                    WHERE status = :status
                    ORDER BY created_on DESC";

                $stmt = $this->pdo->prepare($sqlStatusOnly);
                $stmt->bindValue(':status', $status, PDO::PARAM_STR);
                $stmt->execute();
                $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                return $groups;
            }

            $sql = "SELECT id, name, description, created_on, code, status, created_by
                    FROM game_group
                    WHERE (name LIKE :q1 OR description LIKE :q2 or code LIKE :q3)";

            // Si se especifica status, filtrar por él; si no, incluir tanto active como inactive
            if ($status === null) {
                $sql .= " AND status IN ('active','inactive')";
            } else {
                $sql .= " AND status = :status";
            }

            $sql .= " ORDER BY created_on DESC";

            $stmt = $this->pdo->prepare($sql);

            $like = '%' . $trimmed . '%';
            $stmt->bindValue(':q1', $like, PDO::PARAM_STR);
            $stmt->bindValue(':q2', $like, PDO::PARAM_STR);
            $stmt->bindValue(':q3', $like, PDO::PARAM_STR);

            if ($status !== null) {
                $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            }

            $stmt->execute();
            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            return $groups;
        } catch (PDOException $e) {
            if ($e->getCode() === '45000') {
                $msg = $e->errorInfo[2] ?? $e->getMessage();
                throw new RuntimeException($msg);
            }

            throw new RuntimeException(
                'Error al buscar grupos: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    //get group questions by group id
    public function getGroupQuestions($groupId) {
        try {
            $stmt = $this->pdo->prepare("CALL sp_get_group_questions(:p_id)");
            $stmt->bindParam(":p_id", $groupId);
            $stmt->execute();
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            return $questions;
            
        } catch (PDOException $e) {
            if ($e->getCode() === '45000') {
                throw new RuntimeException($e->getMessage());
            }
            
            throw new RuntimeException(
                'Error al obtener las preguntas del grupo: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function getAllGroupQuestions($groupId) {
        try {
            $stmt = $this->pdo->prepare("CALL sp_get_all_group_questions(:p_id)");
            $stmt->bindParam(":p_id", $groupId);
            $stmt->execute();
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            return $questions;
            
        } catch (PDOException $e) {
            if ($e->getCode() === '45000') {
                throw new RuntimeException($e->getMessage());
            }
            
            throw new RuntimeException(
                'Error al obtener todas las preguntas del grupo: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    //get group questions by group id
    public function getQuestionsToAdd($accion, $group_id, $question_id) {
        try {
            $stmt = $this->pdo->prepare("CALL sp_question_group_accion(:accion_name, :p_group_id, :p_question_id)");
            $stmt->bindParam(":accion_name", $accion);
            $stmt->bindParam(":p_group_id", $group_id);
            $stmt->bindParam(":p_question_id", $question_id);

            $stmt->execute();
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            return $questions;
            
        } catch (PDOException $e) {
            if ($e->getCode() === '45000') {
                throw new RuntimeException($e->getMessage());
            }
            
            throw new RuntimeException(
                'Error al obtener preguntas para agregar: ' . $e->getMessage(),
                0,
                $e
            );
        }
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

    public function getGroupByCode(string $groupCode)
    {
        try {
            $stmt = $this->pdo->prepare('CALL sp_get_group_by_code(:p_group_code)');
            $stmt->bindParam(':p_group_code', $groupCode, PDO::PARAM_STR);
            $stmt->execute();
            
            $groupData = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            return $groupData;
            
        } catch (PDOException $e) {
            if ($e->getCode() === '45000') {
                $msg = $e->errorInfo[2] ?? $e->getMessage();
                throw new RuntimeException($msg);
            }
            
            throw new RuntimeException(
                'Error al obtener el grupo por código: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function deactivateGroup(string $groupId)
    {
        try {
            $stmt = $this->pdo->prepare('CALL sp_delete_group(:p_group_id)');
            $stmt->bindParam(':p_group_id', $groupId, PDO::PARAM_STR);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            
            return $result;
            
        } catch (PDOException $e) {
            if ($e->getCode() === '45000') {
                $msg = $e->errorInfo[2] ?? $e->getMessage();
                throw new RuntimeException($msg);
            }
            
            throw new RuntimeException(
                'Error al desactivar el grupo: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

 public function updateGroup(
    string $groupId,
    ?string $name,
    ?string $description,
    ?string $code,
    ?string $status
): bool {
    try {
        $stmt = $this->pdo->prepare(
            'CALL sp_update_game_group(:p_id, :p_name, :p_description, :p_code, :p_status)'
        );

        $params = [
            ':p_id' => [$groupId, PDO::PARAM_STR],
            ':p_name' => [$name, $name === null ? PDO::PARAM_NULL : PDO::PARAM_STR],
            ':p_description' => [$description, $description === null ? PDO::PARAM_NULL : PDO::PARAM_STR],
            ':p_code' => [$code, $code === null ? PDO::PARAM_NULL : PDO::PARAM_STR],
            ':p_status' => [$status, $status === null ? PDO::PARAM_NULL : PDO::PARAM_STR],
        ];

        foreach ($params as $key => [$value, $type]) {
            $stmt->bindValue($key, $value, $type);
        }

        $stmt->execute();
        $stmt->closeCursor();

        return true;

    } catch (PDOException $e) {
        if ($e->getCode() === '45000') {
            throw new RuntimeException($e->errorInfo[2] ?? $e->getMessage());
        }

        throw new RuntimeException(
            'Error al actualizar el grupo: ' . $e->getMessage(),
            0,
            $e
        );
    }
}


}
