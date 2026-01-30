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

    public function findAll()
    {
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
                    "lang" => $row["lang"],
                    "feedback" => $row["feedback"],
                    "status" => $row["status"],
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

        // validar integridad de preguntas y opciones antes de guardar
        $validatedQuestionsData = [];
        $idx = 0;
        foreach ($arrayTransformed as $q) {
            $idx++;

            $title = trim((string)($q['TITULO_PREGUNTA'] ?? ''));
            $description = trim((string)($q['DESCRIPCION_PREGUNTA'] ?? ''));
            $tipNote = trim((string)($q['NOTA_CONSEJO'] ?? ''));
            $lang = strtolower(trim((string)($q['LANG'] ?? '')));
            $feedback = trim((string)($q['RETROALIMENTACION'] ?? ''));

            if ($title === '' || mb_strlen($title) > 70) {
                throw new InvalidArgumentException("Pregunta #{$idx} - titulo invalido");
            }
            if ($description === '' || mb_strlen($description) > 240) {
                throw new InvalidArgumentException("Pregunta #{$idx} - descripcion invalida");
            }
            if ($tipNote === '' || mb_strlen($tipNote) > 150) {
                throw new InvalidArgumentException("Pregunta #{$idx} - pista demasiado larga o vacía");
            }
            if ($lang === '' || !in_array($lang, ['es', 'en'], true)) {
                throw new InvalidArgumentException("Pregunta #{$idx} - idioma inválido (es | en)");
            }
            if ($feedback === '' || mb_strlen($feedback) > 230) {
                throw new InvalidArgumentException("Pregunta #{$idx} - retroalimentación demasiado larga o vacía");
            }

            // Opciones de respuesta
            $options = $q['OPCIONES'] ?? null;
            if (!is_array($options) || count($options) !== 4) {
                throw new InvalidArgumentException("Pregunta #{$idx} no contiene 4 opciones de respuesta");
            }

            $cleanOptions = [];
            $correctCount = 0;
            foreach ($options as $j => $opt) {
                $textOpt = trim((string)($opt['TEXTO_OPCION'] ?? ''));
                if ($textOpt === '' || mb_strlen($textOpt) > 170) {
                    throw new InvalidArgumentException("Pregunta #{$idx} opcion " . ($j + 1) . " vacía o demasiado larga");
                }

                $isCorrect = (bool)($opt['ES_CORRECTA'] ?? false);
                if ($isCorrect) {
                    $correctCount++;
                }

                $cleanOptions[] = [
                    'TEXTO_OPCION' => $textOpt,
                    'ES_CORRECTA' => $isCorrect,
                ];
            }

            if ($correctCount !== 1) {
                throw new InvalidArgumentException("Pregunta #{$idx} no contiene exactamente una opción correcta");
            }

            $validatedQuestionsData[] = [
                'ID_PREGUNTA_UNICA' => $q['ID_PREGUNTA_UNICA'],
                'TITULO_PREGUNTA' => $title,
                'DESCRIPCION_PREGUNTA' => $description,
                'NOTA_CONSEJO' => $tipNote === '' ? null : $tipNote,
                'RETROALIMENTACION' => $feedback === '' ? null : $feedback,
                'LANG' => $lang,
                'OPCIONES' => $cleanOptions,
            ];
        }

        return $this->repo->createQuestion($validatedQuestionsData);
    }

    public function getQuestionStats($id)
    {
        return $this->repo->getQuestionStats($id);
    }

    public function deactivateQuestion(string $questionId): void
    {
        $this->repo->deactivateQuestion($questionId);
    }

    public function saveAiQuestions(array $questions): array
    {
        // Add AI_GENERATED flag to each question
        $questionsWithAiFlag = array_map(function ($question) {
            $question['AI_GENERATED'] = 1;

            return $question;
        }, $questions);

        return $this->repo->createQuestion($questionsWithAiFlag);
    }

    public function createNewQuestion(array $question): array
    {
        return $this->repo->createNewQuestion($question);
    }

    public function getById(string $questionId): array
    {
        return $this->repo->getQuestionById($questionId);
    }

    public function updateQuestion(
        string $questionId,
        string $title,
        string $description,
        string $tipNote,
        string $lang,
        string $feedback,
        array $options
    ): array {
        return $this->repo->updateQuestion($questionId, $title, $description, $tipNote, $lang, $feedback, $options);
    }

    public function searchQuestions(?string $q, ?int $ai, ?string $lang, ?string $status): array
    {
        $rows = $this->repo->searchQuestions($q, $ai, $lang, $status);

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
                    "lang" => $row["lang"],
                    "feedback" => $row["feedback"],
                    "status" => $row["status"],
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

    public function reactivateQuestion(string $questionId): void
    {
        $this->repo->reactivateQuestion($questionId);
    }
}
