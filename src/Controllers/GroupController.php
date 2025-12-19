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
}
