<?php

require_once __DIR__ . '/../Repository/UserRepository.php';

class UserService {

    private $repo;

    public function __construct() {
        $this->repo = new UserRepository();
    }

    public function findById($id) {
        return $this->repo->getUserById($id);
    }

    public function findAll($name, $status_id) {
        return $this->repo->getAllUsers($name, $status_id);
    }

    public function create($data) {
        return $this->repo->createUser(
            $data["name"],
            $data["email"],
            $data["password"],
            $data["rol_id"],
            $data["status_id"]
        );
    }

    public function update($data, $id) {
        return $this->repo->updateUser(
            $id,
            $data["name"],
            $data["email"],
            $data["password"],
            $data["rol_id"],
            $data["status_id"]
        );
    }
    
}
