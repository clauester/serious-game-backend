<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Config/env.php';
require_once __DIR__ . '/../src/Routers/Router.php';

loadEnv(__DIR__ . '/../.env');

$router = new Router();
$router->handle($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
