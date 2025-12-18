<?php

require_once __DIR__ . '/../Service/UserService.php';
require_once __DIR__ . '/../Utils/Response.php';

class UserController {

    private $service;

    public function __construct() {
        $this->service = new UserService();
    }

    public function getById($id) {
        $result = $this->service->findById($id);
        Response::json($result);
    }

    public function getAll($name, $status_id) {
        $result = $this->service->findAll($name, $status_id);
        Response::json($result);
    }

    //get all roles for user select options 
    public function getAllRoles() {
        $result = $this->service->findAllRoles();
        Response::json($result);
    }

    public function create() {
        $data = json_decode(file_get_contents("php://input"), true);
        $result = $this->service->create($data);

        Response::json($result);
    }

    public function update($id){
        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data) {
            echo json_encode(["error" => "Invalid JSON"]);
            return;
        }

    // Aquí llamarías a tu modelo o servicio
    $result = $this->service->update($data, $id);

    Response::json($result);
    }

    public function delete($id) {
            $result = $this->service->delete($id);
            Response::json($result);
        }

    public function getAllStatuses() {
        $result = $this->service->findAllStatuses();
        Response::json($result);
    }
}
