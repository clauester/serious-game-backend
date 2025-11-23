<?php
declare(strict_types=1);

require_once __DIR__ . '/../Controllers/UserController.php';

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

        return false; // no coincidió ninguna ruta
    }
}
