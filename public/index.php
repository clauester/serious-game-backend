<?php

require_once __DIR__ . '/../src/Controllers/UserController.php';

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

$basePath = "/seriousgame/public";
$uri = str_replace($basePath, "", $uri);

// 🔥 quitar query params:
$cleanUri = explode("?", $uri)[0];
$cleanUri = rtrim($cleanUri, "/");

$controller = new UserController();

// GET /users
if ($cleanUri === "/users" && $method === "GET") {

    $name = $_GET['name'] ?? null;
    $status = $_GET['status'] ?? null;

    $name = $name === "" ? null : $name;
    $status = $status === "" ? null : $status;

    $controller->getAll($name, $status);
    exit;
}

// GET /users/{uuid}
else if (preg_match("#^/users/([a-fA-F0-9\-]{36})$#", $cleanUri, $matches)
    && $method === "GET") {

    $controller->getById($matches[1]);
    exit;
}

// POST /users
else if ($cleanUri === "/users" && $method === "POST") {
    $controller->create();
    exit;
}

// PUT /users/{id}
else if (preg_match("#^/users/([0-9a-zA-Z-]+)$#", $cleanUri, $matches) && $method === "PUT") {
    $controller->update($matches[1]);
    exit;
}


echo "404 Not Found";
