<?php

require_once __DIR__ . '/../Service/GroupService.php';
require_once __DIR__ . '/../Utils/Response.php';

class GroupController {

    private $service;

    public function __construct() {
        $this->service = new GroupService();
    }

    public function createGroup() {
        $data = json_decode(file_get_contents("php://input"), true);
        $result = $this->service->createGroup($data["code"], $data["name"], $data["description"]);
        Response::json2( 201, 'Grupo creado exitosamente', $result);
    }

    public function getAllGroups() {
        $result = $this->service->getAllGroups();
        Response::json2(200, 'Grupos obtenidos exitosamente', $result);
    }

    public function getGroupQuestions($groupId) {
        $result = $this->service->getGroupQuestions($groupId);
        Response::json2(200, 'Preguntas del grupo obtenidas exitosamente', $result);
    }

    public function getQuestionsToAdd($accion, $group_id, $question_id) {
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

    public function deactivateGroup($groupId) {
        // Lógica para desactivar el grupo (cambiar su estado a inactivo)
        // Aquí llamarías a tu modelo o servicio
        $result = $this->service->deactivateGroup($groupId);

        Response::json2(200, 'Grupo desactivado exitosamente', null);
    }
}
