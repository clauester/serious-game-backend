<?php

declare(strict_types=1);
require_once __DIR__ . '/../Controllers/GroupController.php';

class GroupRoutes
{

    public static function handle(string $cleanUri, string $method): bool
    {
        $controller = new GroupController();

        // GET /groups - Endpoint para obtener todos los grupos
        if ($cleanUri === "/groups" && $method === "GET") {
            $controller->getAllGroups();
            return true;
        }

        // POST /group/save - Endpoint para crear grupo
        if ($cleanUri === "/groups/save" && $method === "POST") {
            $controller->createGroup();
            return true;
        }

        // GET /groups/{groupId}/questions - Endpoint para obtener preguntas de un grupo mediante id UUID 
        if (preg_match('/^\/groups\/([a-f0-9\-]+)\/questions$/', $cleanUri, $matches) && $method === "GET") {
            $groupId = $matches[1];
            $controller->getGroupQuestions($groupId);
            return true;
        }

        //GET all group questions by id 
        if (preg_match('/^\/groups\/([a-f0-9\-]+)\/questions\/all$/', $cleanUri, $matches) && $method === "GET") {
            $groupId = $matches[1];
            $controller->getAllQuestions($groupId);
            return true;
        }

        // POST /groups/{groupId}/questions/to-add - Endpoint para obtener preguntas para agregar a un grupo
        if ($cleanUri === "/groups/questions/to-add" && $method === "POST") {
            $data = json_decode(file_get_contents("php://input"), true);
            $accion = $data["accion_name"];
            $group_id = $data["group_id"];
            $question_id = $data["question_id"];
            $controller->getQuestionsToAdd($accion, $group_id, $question_id);
            return true;
        }

        // POST /groups/questions/add - Endpoint para agregar preguntas a un grupo
        if ($cleanUri === "/groups/questions/add" && $method === "POST") {
            $data = json_decode(file_get_contents("php://input"), true);
            $group_id = $data["groupId"];
            $question_id = $data["questionIds"];
            $delelete_id = $data["deleteIds"] ?? [];
            $controller->addQuestionToGroup($group_id, $question_id, $delelete_id);
            return true;
        }

        // GET /groups/search?q=... - Buscar grupos por nombre o descripción y status
        if ($cleanUri === "/groups/search" && $method === "GET") {
            $controller->searchGroups();
            return true;
        }

        // PUT /groups/{groupId} - Endpoint para actualizar un grupo (campos pueden ser null)
        if (preg_match('/^\/groups\/([a-f0-9\-]+)$/', $cleanUri, $matches) && $method === "PUT") {
            $groupId = $matches[1];
            $controller->updateGroup($groupId);
            return true;
        }

        // PUT /groups/delete/{groupId}- Endpoint para cambiar status a inactive
        if (preg_match('/^\/groups\/delete\/([a-f0-9\-]+)$/', $cleanUri, $matches) && $method === "PUT") {
            $groupId = $matches[1];
            $controller->deactivateGroup($groupId);
            return true;
        }

        // get group by code
        if (preg_match('/^\/groups\/code\/([A-Za-z0-9]+)$/', $cleanUri, $matches) && $method === "GET") {
            $groupCode = $matches[1];
            $controller->getGroupByCode($groupCode);
            return true;
        }


        return false;
    }
}
