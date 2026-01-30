<?php

require_once __DIR__ . '/../Repository/UserRepository.php';
require_once __DIR__ . '/../Utils/PasswordGenerator.php';

class UserService
{

    private $repo;
    private PasswordGenerator $passwordGenerator;

    public function __construct()
    {
        $this->repo = new UserRepository();
        $this->passwordGenerator = new PasswordGenerator();
    }

    public function findById($id)
    {
        return $this->repo->getUserById($id);
    }

    public function findAll($q, $status_id)
    {
        return $this->repo->getAllUsers($q, $status_id);
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

        // prevenir que sp retorne datos sensibles
        unset($profileData['password'], $profileData['rol_id'], $profileData['status_id']);

        return $profileData;
    }

    public function updatePassword(string $userId, string $currentPass, string $newPass, ?string $confirmPass = null): void
    {
        $currentPassword = (string)$currentPass;
        $newPassword = (string)$newPass;


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


        try {
            $ok = $this->repo->updateUserPassword($userId, $hash);
        } catch (Throwable $e) {
            // Mapear mensajes del SP
            $msg = (string)$e->getMessage();

            if (str_contains($msg, 'PASSWORD_REQUIRED')) {
                throw new RuntimeException('SP: Nueva contraseña requerida');
            }
            throw $e;
        }

        if (!$ok) {
            throw new RuntimeException("No se pudo actualizar la contraseña");
        }
    }

    public function resetPassword(string $userId): string
    {
        // generar password
        try {
            $plainPass = $this->passwordGenerator->generate(10);
        } catch (Throwable $e) {
            throw new RuntimeException("Fallo al generar la nueva contraseña");
        }

        // hash pass
        $hashPass = password_hash($plainPass, PASSWORD_BCRYPT);

        try {
            $ok = $this->repo->updateUserPassword($userId, $hashPass);
        } catch (Throwable $e) {
            // Mapear mensajes del SP
            $msg = (string)$e->getMessage();

            if (str_contains($msg, 'USR_NOT_FOUND')) {
                throw new RuntimeException('SP: Usuario no encontrado');
            }
            if (str_contains($msg, 'PASSWORD_REQUIRED')) {
                throw new RuntimeException('SP: Nueva contraseña requerida');
            }
            throw $e;
        }

        if (!$ok) {
            throw new RuntimeException('No se pudo restablecer la contraseña');
        }

        // clave en texto plano
        return $plainPass;
    }
}
