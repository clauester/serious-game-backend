<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Controllers/QuestionController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $controller = new QuestionController();

    // pasar un parámetro para diferenciar CSV
    $type = $_POST['csv_type'] ?? 'question'; // 'question' o 'question_option'

    $controller->showCsv($_FILES['csv_file'], $type);
} else {
    echo "Método no permitido.";
}
