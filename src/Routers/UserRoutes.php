<?php
declare(strict_types=1);

require_once __DIR__ . '/../Controllers/UserController.php';

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Credentials: true");

class UserRoutes {

    public static function handle(string $uri, string $method): bool {
        $controller = new UserController();

        // GET /users
        if ($uri === "/users" && $method === "GET") {
            $controller->getAll($_GET['name'] ?? null, $_GET['status'] ?? null);
            return true;
        }

        // GET /users/{uuid}
        if (preg_match("#^/users/([0-9a-fA-F-]{36})$#", $uri, $matches) && $method === "GET") {
            $controller->getById($matches[1]);
            return true;
        }

        if($uri === "/users/roles" && $method === "GET"){
            $controller->getAllRoles();
            return true;
        }

        // Delete /users/{uuid}
        if (preg_match("#^/users/([0-9a-fA-F-]{36})$#", $uri, $matches) && $method === "DELETE") {
            $controller->delete($matches[1]);
            return true;
        }

        // POST /users
        if ($uri === "/users" && $method === "POST") {
            $controller->create();
            return true;
        }

        // PUT /users/{uuid}
        if (preg_match("#^/users/([0-9a-fA-F-]{36})$#", $uri, $matches) && $method === "PUT") {
            $controller->update($matches[1]);
            return true;
        }
        if($uri === "/users/statuses" && $method === "GET"){
            $controller->getAllStatuses();
            return true;
        }

        return false; // no coincidió ninguna ruta
    }
}
