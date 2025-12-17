<?php
declare(strict_types=1);

require_once __DIR__ . '/../Controllers/AuthController.php';

/*
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Credentials: true");
*/
class AuthRoutes {

    public static function handle(string $cleanUri, string $method): bool {

        $controller = new AuthController();

        // POST /auth/login
        if ($cleanUri === "/auth/login" && $method === "POST") {
            $controller->login();
            return true;
        }

        // POST /auth/register
        if ($cleanUri === "/auth/register" && $method === "POST") {
            $controller->register();
            return true;
        }

        return false;
    }
}