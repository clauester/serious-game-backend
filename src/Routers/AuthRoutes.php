<?php

declare(strict_types=1);

require_once __DIR__ . '/../Controllers/AuthController.php';


class AuthRoutes
{

    public static function handle(string $cleanUri, string $method): bool
    {

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
