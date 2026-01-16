<?php
declare(strict_types=1);

require_once __DIR__ . '/../Routers/UserRoutes.php';
require_once __DIR__ . '/../Routers/QuestionRoutes.php';
require_once __DIR__ . '/../Routers/GroupRoutes.php';
require_once __DIR__ . '/../Routers/AuthRoutes.php';
require_once __DIR__ . '/../Routers/GameRoutes.php';
require_once __DIR__ . '/../Routers/StatsRoutes.php';

class Router {

    public function handle(string $uri, string $method): void {
        $basePath = "/seriousgame/public";
        $cleanUri = rtrim(explode("?", str_replace($basePath, "", $uri))[0], "/");

        // Delegar a cada grupo de rutas
        if (UserRoutes::handle($cleanUri, $method)) return;
        if (QuestionRoutes::handle($cleanUri, $method)) return;
        if (GroupRoutes::handle($cleanUri, $method)) return;
        if (AuthRoutes::handle($cleanUri, $method)) return;
        if (GameRoutes::handle($cleanUri, $method)) return;
        if (StatsRoutes::handle($cleanUri, $method)) return;

        echo "404 Not Found - endpoint no definido";
    }
}
