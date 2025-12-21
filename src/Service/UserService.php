<?php

require_once __DIR__ . '/../Repository/UserRepository.php';

class UserService
{

    private $repo;

    public function __construct()
    {
        $this->repo = new UserRepository();
    }

    public function findById($id)
    {
        return $this->repo->getUserById($id);
    }

    public function findAll($name, $status_id)
    {
        return $this->repo->getAllUsers($name, $status_id);
    }

    function findAllRoles()
    {
        return $this->repo->getAllRoles();
    }

    public function create($data)
    {
        return $this->repo->createUser(
            $data["name"],
            $data["email"],
            $data["password"],
            $data["rol_id"],
            $data["status_id"]
        );
    }

    public function update($data, $id)
    {
        return $this->repo->updateUser(
            $id,
            $data["name"],
            $data["email"],
            $data["password"],
            $data["rol_id"],
            $data["status_id"]
        );
    }

    public function delete($id)
    {
        return $this->repo->deleteUser($id);
    }

    public function findAllStatuses()
    {
        return $this->repo->getStatuses();
    }

    public function getProfileById(string $userId): ?array
    {
        $profileData = $this->repo->getUserProfileById($userId);

        if (!$profileData) return null;

        // en caso de que el sp retorne datos sensibles
        unset($profileData['password'], $profileData['rol_id'], $profileData['status_id']);

        return $profileData;
    }
}
