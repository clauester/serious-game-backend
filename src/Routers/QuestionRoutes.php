<?php
declare(strict_types=1);
require_once __DIR__ . '/../Controllers/QuestionController.php';

class QuestionRoutes {

    public static function handle(string $cleanUri, string $method): bool {
        $controller = new QuestionController();

        // POST /questions/upload-csv - Endpoint para cargar CSV y obtener JSON
        if ($cleanUri === "/questions/upload-csv" && $method === "POST") {
            $controller->showCsvData();
            return true;
        }

        // GET /questions -> opcional, lista preguntas
        if ($cleanUri === "/questions/upload2" && $method === "POST") {
             $controller->uploadCsv(); // si implementas listado
            return true;
        }

        // GET /questions -> opcional, lista preguntas
        if ($cleanUri === "/questions/all" && $method === "GET") {
             $controller->findAll(); // si implementas listado
            return true;
        }

        // // GET /questions/{id} -> opcional, detalles de pregunta
        // if (preg_match("#^/questions/([0-9a-fA-F-]{36})$#", $cleanUri, $matches)
        //     && $method === "GET") {
        //     // $controller->getById($matches[1]);
        //     return true;
        // }

        return false;
    }
}