<?php

require_once __DIR__ . '/../config/database.php';

class UserRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = (new Database())->connect();
    }

    public function getUserById($id)
    {
        $stmt = $this->pdo->prepare("CALL sp_get_user_by_id(:id)");
        $stmt->bindParam(":id", $id, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function getAllUsers($name,  $status_id)
    {

        $stmt = $this->pdo->prepare("CALL sp_get_users(:p_name, :p_status_id)");
        $stmt->bindValue(':p_name', $name, $name === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':p_status_id', $status_id, $status_id === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

        $stmt->execute();
        $users = $stmt->fetchAll();
        $stmt->closeCursor();

        return $users;
    }

    public function getAllRoles()
    {
        $stmt = $this->pdo->prepare("CALL sp_get_all_roles()");
        $stmt->execute();
        $roles = $stmt->fetchAll();
        $stmt->closeCursor();

        return $roles;
    }

    public function createUser(
        string $name,
        string $email,
        string $password,
        string $rol_id,
        string $status_id
    ) {

        $stmt = $this->pdo->prepare("CALL sp_create_user(:p_name, :p_email, :p_password, :p_rol_id, :p_status_id)");
        $stmt->bindParam(":p_name", $name);
        $stmt->bindParam(":p_email", $email);
        $stmt->bindParam(":p_password", $password);
        $stmt->bindParam(":p_rol_id", $rol_id);
        $stmt->bindParam(":p_status_id", $status_id);
        $stmt->execute();

        return $stmt->fetch(); // tu SP debe retornar el nuevo usuario
    }

    public function updateUser(
        string $id,
        ?string $name,
        ?string $email,
        ?string $password,
        ?string $rol_id,
        ?string $status_id
    ) {

        $stmt = $this->pdo->prepare("CALL sp_update_user(:p_id, :p_name, :p_email, :p_password, :p_rol_id, :p_status_id)");
        $stmt->bindParam(":p_id", $id);
        $stmt->bindParam(":p_name", $name);
        $stmt->bindParam(":p_email", $email);
        $stmt->bindParam(":p_password", $password);
        $stmt->bindParam(":p_rol_id", $rol_id);
        $stmt->bindParam(":p_status_id", $status_id);
        $stmt->execute();

        return $stmt->fetch(); // tu SP debe retornar el nuevo usuario
    }

    public function getStatuses()
    {
        $stmt = $this->pdo->prepare("CALL sp_get_all_status()");
        $stmt->execute();
        $statuses = $stmt->fetchAll();
        $stmt->closeCursor();

        return $statuses;
    }

    public function deleteUser(string $id)
    {
        $stmt = $this->pdo->prepare("CALL sp_delete_user(:p_id)");
        $stmt->bindParam(":p_id", $id);
        return $stmt->execute();
    }

    public function getUserProfileById(string $userId): ?array
    {
        $stmt = $this->pdo->prepare("CALL sp_get_user_profile(:id)");
        $stmt->bindParam(":id", $userId, PDO::PARAM_STR);
        $stmt->execute();

        $profileData = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return $profileData ?: null;
    }

    public function updateUserPassword(string $id, string $passwordHash): bool
    {
        $stmt = $this->pdo->prepare("CALL sp_update_user_password(:p_id, :p_password)");
        $stmt->bindParam(":p_id", $id, PDO::PARAM_STR);
        $stmt->bindParam(":p_password", $passwordHash, PDO::PARAM_STR);

        $ok = $stmt->execute();

        while ($stmt->nextRowset()) {
        }
        $stmt->closeCursor();

        return (bool)$ok;
    }
}
