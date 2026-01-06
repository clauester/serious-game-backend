<?php

require_once __DIR__ . '/../Repository/QuestionRepository.php';

class QuestionService
{

    private $repo;
    private $csvReader;
    private $response;

    public function __construct()
    {
        $this->repo = new QuestionRepository();
        $this->csvReader = new CsvReader();
        $this->response = new Response();
    }

    public function findAll(){
        $rows = $this->repo->getAllQuestions();

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
                    "ai_generated" => $row["ai_generated"],
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

    public function saveUserAnswer(?string  $answerId, $groupId, $userId, $questionId, $qOptionId, $gameId)
    {
        return $this->repo->saveUserAnswerOption(
            $answerId,
            $groupId,
            $userId,
            $questionId,
            $qOptionId,
            $gameId
            
        );
    }

    public function create($data)
    {
        $arrayTransformed = $this->csvReader->rawToJson($data);

        // // Devolver tipo y datos (ajusta según necesites)
        // $this->response->json2(200, 'CSV leído correctamente', [
        //     'csv_type' => "csvType",
        //     'rows' => $arrayTransformed
        // ]);
        return $this->repo->createQuestion($arrayTransformed);




        // return $this->repo->createUser(
        //     $data["name"],
        //     $data["email"],
        //     $data["password"],
        //     $data["rol_id"],
        //     $data["status_id"]
        // );
    }

    public function getQuestionStats($id) {
        return $this->repo->getQuestionStats($id);
    }

    public function deactivateQuestion(string $questionId): void
    {
        $this->repo->deactivateQuestion($questionId);
    }

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

    public function saveAiQuestions(array $questions): array
    {
        // Add AI_GENERATED flag to each question
        $questionsWithAiFlag = array_map(function($question) {
        $question['AI_GENERATED'] = 1;
        
        return $question;

        }, $questions);
    
        return $this->repo->createQuestion($questionsWithAiFlag);
    }

}
