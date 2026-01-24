<?php

require_once __DIR__ . '/../Service/UserService.php';
require_once __DIR__ . '/../Utils/Response.php';

class UserController
{

    private $service;

    public function __construct()
    {
        $this->service = new UserService();
    }

    public function getById($id)
    {
        $result = $this->service->findById($id);
        Response::json($result);
    }

    public function getAll($q, $status_id)
    {
        $result = $this->service->findAll($q, $status_id);
        Response::json($result);
    }

    //get all roles for user select options 
    public function getAllRoles()
    {
        $result = $this->service->findAllRoles();
        Response::json($result);
    }

    public function create()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data) {
            echo json_encode(["error" => "Invalid JSON"]);
            return;
        }

        $password = (string)($data["password"] ?? "");
        if ($password === "" || mb_strlen($password) < 8) {
            Response::json(["error" => "La contraseña debe tener al menos 8 caracteres"], 400);
            return;
        }

        $result = $this->service->create($data);

        Response::json($result);
    }

    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data) {
            echo json_encode(["error" => "Invalid JSON"]);
            return;
        }

        // Aquí llamarías a tu modelo o servicio
        $result = $this->service->update($data, $id);

        Response::json($result);
    }

    public function delete($id)
    {
        $result = $this->service->delete($id);
        Response::json($result);
    }

    public function getAllStatuses()
    {
        $result = $this->service->findAllStatuses();
        Response::json($result);
    }


    public function getProfile(?string $userId): void
    {
        if (!$userId || !preg_match('/^[0-9a-fA-F-]{36}$/', $userId)) {
            Response::json2(400, 'ID de usuario inválido', null);
            return;
        }

        $profileData = $this->service->getProfileById($userId);

        if (!$profileData) {
            Response::json2(404, 'No se encontraron datos para el usuario', null);
            return;
        }

        Response::json2(200, 'Datos de perfil obtenidos', $profileData);
    }

    public function updatePassword(string $id): void
    {
        try {
            if (!preg_match('/^[0-9a-fA-F-]{36}$/', $id)) {
                Response::json2(400, 'ID de usuario inválido', null);
                return;
            }

            $data = json_decode(file_get_contents("php://input"), true);

            if (!is_array($data)) {
                Response::json2(400, 'JSON inválido', null);
                return;
            }

            $currentPass = isset($data["currentPassword"]) ? trim((string)$data["currentPassword"]) : "";
            $newPass    = isset($data["newPassword"]) ? trim((string)$data["newPassword"]) : "";
            $confirmPass = isset($data["confirmPassword"]) ? trim((string)$data["confirmPassword"]) : null;

            if ($currentPass === "") {
                Response::json2(400, 'Contraseña actual requerida', null);
                return;
            }
            if ($newPass === "") {
                Response::json2(400, 'Nueva contraseña requerida', null);
                return;
            }
            if ($confirmPass !== null && $confirmPass === "") {
                Response::json2(400, 'Confirmación de nueva contraseña requerida', null);
                return;
            }

            $this->service->updatePassword($id, $currentPass, $newPass, $confirmPass);

            Response::json2(200, 'Contraseña actualizada correctamente', null);
        } catch (InvalidArgumentException $e) {
            Response::json2(400, $e->getMessage(), null);
        } catch (RuntimeException $e) {

            Response::json2(403, $e->getMessage(), null);
        } catch (Throwable $e) {
            Response::json2(500, 'Error interno del servidor', null);
        }
    }

    public function resetPassword(string $userId): void
    {
        try {
            if (!preg_match('/^[0-9a-fA-F-]{36}$/', $userId)) {
                Response::json2(400, 'ID de usuario inválido', null);
                return;
            }


            $plainPass = $this->service->resetPassword($userId);

            Response::json2(200, 'Contraseña restablecida correctamente', [
                "userId" => $userId,
                "password" => $plainPass
            ]);
        } catch (RuntimeException $e) {
            Response::json2(400, $e->getMessage(), null);
        } catch (Throwable $e) {
            Response::json2(500, 'Error interno del servidor', null);
        }
    }
}
