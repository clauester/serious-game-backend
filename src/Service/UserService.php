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
        // enviar contraseña hasheada
        $hashPass = password_hash($data["password"], PASSWORD_BCRYPT);

        return $this->repo->createUser(
            $data["name"],
            $data["email"],
            $hashPass,
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
            $data["password"] ?? null, // no cambio de contraseña
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

    public function updatePassword(string $userId, string $currentPass, string $newPass, ?string $confirmPass = null): void
    {
        $currentPassword = (string)$currentPass;
        $newPassword     = (string)$newPass;


        if ($confirmPass !== null && $newPassword !== $confirmPass) {
            throw new InvalidArgumentException("Las contraseñas no coinciden");
        }

        if (mb_strlen($newPassword) < 8) {
            throw new InvalidArgumentException("La contraseña debe tener al menos 8 caracteres");
        }

        // limitar a 72 bytes para evitar truncado
        if (strlen($newPassword) > 32) {
            throw new InvalidArgumentException("La nueva contraseña es demasiado larga (máx. 32)");
        }

        if ($newPassword === $currentPassword) {
            throw new InvalidArgumentException("La nueva contraseña debe ser diferente a la anterior");
        }

        $user = $this->repo->getUserById($userId); // obtener user para validar current password
        if (!$user) {
            throw new RuntimeException("Usuario no encontrado");
        }

        $storedPass = (string)($user["password"] ?? "");
        if ($storedPass === "") {
            throw new RuntimeException("No se pudo validar contraseña actual");
        }

        $looksHashed = str_starts_with($storedPass, '$2y$') || str_starts_with($storedPass, '$argon2');
        $passOk = $looksHashed ? password_verify($currentPassword, $storedPass) : hash_equals($storedPass, $currentPassword);

        if (!$passOk) {
            throw new RuntimeException("Contraseña actual incorrecta");
        }

        // Evitar reutilizar la misma contraseña 
        $sameAsOldPass = $looksHashed ? password_verify($newPassword, $storedPass) : hash_equals($storedPass, $newPassword);
        if ($sameAsOldPass) {
            throw new InvalidArgumentException("La nueva contraseña debe ser diferente a la anterior");
        }

        // hash de la nueva contraseña
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);

        $ok = $this->repo->updateUserPassword($userId, $hash);
        if (!$ok) {
            throw new RuntimeException("No se pudo actualizar la contraseña");
        }
    }
}
