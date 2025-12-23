<?php

require_once __DIR__ . '/../Repository/GroupRepository.php';

class GroupService {

    private $repo;

    public function __construct() {
        $this->repo = new GroupRepository();
    }

    public function createGroup($code, $name, $description) {
        return $this->repo->createGroup($code, $name, $description);
    }

    public function getAllGroups() {
        return $this->repo->getAllGroups();
    }

    public function getGroupQuestions($groupId) {
        return $this->repo->getGroupQuestions($groupId);

    }

    public function getAllGroupQuestions($groupId) {
        $rows = $this->repo->getAllGroupQuestions($groupId);

        $questions = [];

        foreach ($rows as $row) {

            $id = $row["id"];

            if (!isset($questions[$id])) {
                $questions[$id] = [
                    "id" => $row["id"],
                    "title" => $row["title"],
                    "description" => $row["description"],
                    "type" => $row["type"],
                    "tip_note" => $row["tip_note"],
                    "created_on" => $row["created_on"],
                    "options" => []
                ];
            }

            $questions[$id]["options"][] = [
                "text_option" => $row["text_option"],
                "is_correct" => (int)$row["is_correct"]
            ];
        }

        return array_values($questions);
    }

    public function getQuestionsToAdd($accion, $group_id, $question_id) {
        return $this->repo->getQuestionsToAdd($accion, $group_id, $question_id);
    }

    public function addQuestionToGroup($groupId, $questionIds, $deleteIds) {
        // Lógica para agregar una pregunta a un grupo
        return $this->repo->registerGroupQuestions($groupId, $questionIds, $deleteIds);
    }
    public function deactivateGroup($groupId) {
        // Lógica para desactivar el grupo (cambiar su estado a inactivo)
        // Aquí llamarías a tu repositorio
        return $this->repo->deactivateGroup($groupId);
    }

}
