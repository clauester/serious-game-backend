<?php

require_once __DIR__ . '/../Service/GroupService.php';
require_once __DIR__ . '/../Utils/Response.php';

class GroupController
{

    private $service;

    public function __construct()
    {
        $this->service = new GroupService();
    }

    public function createGroup()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $result = $this->service->createGroup($data["code"], $data["name"], $data["description"], $data["created_by"]);
        Response::json2(201, 'Grupo creado exitosamente', $result);
    }

    public function getAllGroups()
    {
        $result = $this->service->getAllGroups();
        Response::json2(200, 'Grupos obtenidos exitosamente', $result);
    }

    public function searchGroups()
    {
        try {
            $q = $_GET['q'] ?? null;
            $status = array_key_exists('status', $_GET) ? $_GET['status'] : null;

            if ($q === null || trim((string)$q) === '') {
                // Si tampoco se envía status, devolver todos
                if ($status === null) {
                    $result = $this->service->getAllGroups();
                    Response::json2(200, 'Todos los grupos obtenidos exitosamente', $result);
                    return;
                }

                // solo status, filtrar por status
                $result = $this->service->searchGroups(null, $status);
                Response::json2(200, 'Grupos filtrados por status obtenidos exitosamente', $result);
                return;
            }

            $result = $this->service->searchGroups($q, $status);
            Response::json2(200, 'Grupos filtrados obtenidos exitosamente', $result);
        } catch (RuntimeException $e) {
            Response::json2(400, $e->getMessage(), null);
        } catch (Throwable $e) {
            Response::json2(500, 'Error interno del servidor', null);
        }
    }

    public function getGroupQuestions($groupId)
    {
        $result = $this->service->getGroupQuestions($groupId);
        Response::json2(200, 'Preguntas del grupo obtenidas exitosamente', $result);
    }

    public function getAllQuestions($groupId)
    {
        $result = $this->service->getAllGroupQuestions($groupId);
        Response::json2(200, 'Preguntas del grupo obtenidas exitosamente', $result);
    }

    public function getQuestionsToAdd($accion, $group_id, $question_id)
    {
        $result = $this->service->getQuestionsToAdd($accion, $group_id, $question_id);
        Response::json2(200, 'Preguntas para agregar obtenidas exitosamente', $result);
    }

    public function addQuestionToGroup($groupId, $questionIds, $deleteIds)
    {
        try {
            $result = $this->service->addQuestionToGroup(
                $groupId,
                $questionIds,
                $deleteIds
            );

            Response::json2(
                200,
                'Preguntas agregadas al grupo exitosamente',
                $result
            );
        } catch (RuntimeException $e) {

            Response::json2(
                409, // conflicto lógico
                $e->getMessage(),
                null
            );
        } catch (Throwable $e) {

            Response::json2(
                500,
                'Error interno del servidor',
                null
            );
        }
    }

    public function deactivateGroup($groupId)
    {
        // cambiar estado del grupo a inactivo
        $result = $this->service->deactivateGroup($groupId);

        Response::json2(200, 'Grupo desactivado exitosamente', null);
    }

    public function updateGroup($groupId)
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            $name = array_key_exists('name', $data) ? $data['name'] : null;
            $description = array_key_exists('description', $data) ? $data['description'] : null;
            $code = array_key_exists('code', $data) ? $data['code'] : null;
            $status = array_key_exists('status', $data) ? $data['status'] : null;

            $this->service->updateGroup($groupId, $name, $description, $code, $status);

            Response::json2(200, 'Grupo actualizado exitosamente', null);
        } catch (RuntimeException $e) {
            Response::json2(409, $e->getMessage(), null);
        } catch (Throwable $e) {
            Response::json2(500, 'Error interno del servidor', null);
        }
    }

    public function getGroupByCode($groupCode)
    {
        try {
            // normalizar código
            $groupCode = strtoupper(trim((string)$groupCode));

            if ($groupCode === '') {
                Response::json2(400, 'Código de grupo no proporcionado', null);
                return;
            }

            if (strlen($groupCode) !== 6) {
                Response::json2(400, 'Código de grupo no cumple la longitud requerida', null);
                return;
            }

            $result = $this->service->getGroupByCode($groupCode);

            if (empty($result)) {
                Response::json2(404, 'Grupo no disponible', null);
                return;
            }

            Response::json2(200, 'Grupo obtenido exitosamente', $result);
        } catch (RuntimeException $e) {
            Response::json2(400, $e->getMessage(), null);
        } catch (Throwable $e) {
            Response::json2(500, 'Error interno del servidor', null);
        }
    }
}
