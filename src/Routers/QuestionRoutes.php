<?php

declare(strict_types=1);
require_once __DIR__ . '/../Controllers/QuestionController.php';

class QuestionRoutes
{

    public static function handle(string $cleanUri, string $method): bool
    {
        $controller = new QuestionController();

        // POST /questions/upload-csv - Endpoint para cargar CSV y obtener JSON
        if ($cleanUri === "/questions/upload-csv" && $method === "POST") {
            $controller->showCsvData();
            return true;
        }

        // POST /questions 
        if ($cleanUri === "/questions/upload2" && $method === "POST") {
            $controller->uploadCsv();
            return true;
        }

        // GET /questions 
        if ($cleanUri === "/questions/all" && $method === "GET") {
            $controller->findAll();
            return true;
        }

        // POST /questions 
        // update 1 question option
        if ($cleanUri === "/questions/answer/save" && $method === "POST") {
            $controller->saveUserAnswerOption();
            return true;
        }
        // GET /questions/stats/id -> obtener estadísticas de preguntas por grupo
        // null when id is all
        if (
            preg_match("#^/questions/stats/group/(all|[0-9a-fA-F-]{36})$#", $cleanUri, $matches)
            && $method === "GET"
        ) {

            $controller->getQuestionStats($matches[1]);
            return true;
        }

        // // GET /questions/{id} ->  detalles de pregunta
        // if (preg_match("#^/questions/([0-9a-fA-F-]{36})$#", $cleanUri, $matches)
        //     && $method === "GET") {
        //     // $controller->getById($matches[1]);
        //     return true;
        // }

        if ($cleanUri === '/questions/ai/generate'  && $method === 'POST') {
            $controller->generateWithAI();
            return true;
        }

        //change question status to inactive
        if (
            preg_match("#^/questions/delete/([0-9a-fA-F-]{36})$#", $cleanUri, $matches)
            && $method === "PUT"
        ) {
            $controller->deactivateQuestion($matches[1]);
            return true;
        }

        // create question (manual)
        if ($cleanUri === '/questions/create' && $method === 'POST') {
            $controller->createNewQuestion();
            return true;
        }

        // GET /questions/search? - obtener listado con filtros combinables
        if ($cleanUri === '/questions/search' && $method === 'GET') {
            $controller->searchQuestions();
            return true;
        }

        // GET /questions/{uuid}
        if (preg_match("#^/questions/([0-9a-fA-F-]{36})$#", $cleanUri, $matches) && $method === "GET") {
            $controller->getById($matches[1]);
            return true;
        }

        // PUT /questions/{uuid}
        if (preg_match("#^/questions/([0-9a-fA-F-]{36})$#", $cleanUri, $matches) && $method === "PUT") {
            $controller->updateQuestion($matches[1]);
            return true;
        }

        // GET /questions/template/download - descargar plantilla CSV
        if ($cleanUri === '/questions/template/download' && $method === "GET") {
            $controller->downloadCsvTemplate();
            return true;
        }

        // PUT /questions/reactivate/{uuid} - Actualizar status a active (reactivar pregunta)
        if (
            preg_match("#^/questions/reactivate/([0-9a-fA-F-]{36})$#", $cleanUri, $matches)
            && $method === "PUT"
        ) {
            $controller->reactivateQuestion($matches[1]);
            return true;
        }


        return false;
    }
}
