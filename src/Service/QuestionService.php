<?php

require_once __DIR__ . '/../Repository/QuestionRepository.php';

class QuestionService {

    private $repo;

    public function __construct() {
        $this->repo = new QuestionRepository();
    }

    public function findAll($rows) {

        foreach ($rows as $row) {

            $questionTitle = $row["title"];
            $questionDescription = $row["description"];
            $questionType = $row["type_name"]; // tipo por nombre
            $optionText = $row["option_text"];
            $isCorrect = $row["is_correct"];

            // Aquí llamas al SP del repository
            $this->repo->createQuestion(
                $questionTitle,
                $questionDescription,
                $questionType,
                $optionText,
                $isCorrect
            );
        }
    
    }

    // public function create($data) {
    //     return $this->repo->createUser(
    //         $data["name"],
    //         $data["email"],
    //         $data["password"],
    //         $data["rol_id"],
    //         $data["status_id"]
    //     );
    // }

    // public function update($data, $id) {
    //     return $this->repo->updateUser(
    //         $id,
    //         $data["name"],
    //         $data["email"],
    //         $data["password"],
    //         $data["rol_id"],
    //         $data["status_id"]
    //     );
    // }
    
}
