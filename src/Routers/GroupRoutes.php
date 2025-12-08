<?php
declare(strict_types=1);
require_once __DIR__ . '/../Controllers/GroupController.php';

class GroupRoutes {

    public static function handle(string $cleanUri, string $method): bool {
        $controller = new GroupController();

         // POST /group/save - Endpoint para cargar CSV y obtener JSON
        if ($cleanUri === "/groups" && $method === "GET") {
            $controller->getAllGroups();
            return true;
        }

        // POST /group/save - Endpoint para cargar CSV y obtener JSON
        if ($cleanUri === "/groups/save" && $method === "POST") {
            $controller->createGroup();
            return true;
        }


        return false;
    }
}