<?php

require_once __DIR__ . '/../Service/AuthService.php';
require_once __DIR__ . '/../Utils/Response.php';

class AuthController {
    private AuthService $service;
    private Response $response;

    public function __construct() {
        $this->service = new AuthService();
        $this->response = new Response();
    }

    public function login(): void {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!is_array($data)) {
                $this->response->json2(400, 'JSON inválido');
                return;
            }

            $user = $this->service->login($data);

            if (!$user) {
                $this->response->json2(401, 'Credenciales incorrectas');
                return;
            }

            $this->response->json2(200, 'Sesión iniciada', $user);

        } catch (InvalidArgumentException $e) {
            $this->response->json2(400, $e->getMessage());
        } catch (RuntimeException $e) {
            $this->response->json2(403, $e->getMessage());
        } catch (Exception $e) {
            $this->response->json2(500, 'Error en login: ' . $e->getMessage());
        }
    }

    public function register(): void {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!is_array($data)) {
                $this->response->json2(400, 'JSON inválido');
                return;
            }

            $created = $this->service->register($data);

            $this->response->json2(201, 'Cuenta registrada exitosamente', $created);
        } catch (InvalidArgumentException $e) {
            $this->response->json2(400, $e->getMessage());
        } catch (RuntimeException $e) {
            // email ya registrado
            $this->response->json2(409, $e->getMessage());
        } catch (Exception $e) {
            $this->response->json2(500, 'Error en registro: ' . $e->getMessage());
        }
    }
}
