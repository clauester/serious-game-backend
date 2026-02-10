<?php

declare(strict_types=1);

require_once __DIR__ . '/../Utils/csvReader.php';
require_once __DIR__ . '/../Utils/Response.php';
require_once __DIR__ . '/../Service/QuestionService.php';

class QuestionController
{

    private CsvReader $csvReader;
    private Response $response;
    private QuestionService $questionService;

    public function __construct()
    {
        $this->csvReader = new CsvReader();
        $this->response = new Response();
        $this->questionService = new QuestionService();
    }

    public function findAll()
    {
        $questions = $this->questionService->findAll();
        $this->response->json2(200, 'Listado de preguntas', $questions);
    }

    public function showCsvData(): void
    {
        try {
            // si archivo por form-data (csv_file)
            if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['csv_file'];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $this->response->json2(400, 'Error en la subida del archivo: ' . $this->fileUploadErrorMessage($file['error']));
                    return;
                }

                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                if (strtolower($ext) !== 'csv') {
                    $this->response->json2(400, 'El archivo debe tener extensión .csv');
                    return;
                }

                $tmpPath = $file['tmp_name'];
                $data = $this->csvReader->readCsv($tmpPath);

                $csvType = $_POST['csv_type'] ?? null;

                $this->response->json2(200, 'CSV leído correctamente (upload)', [
                    'csv_type' => $csvType,
                    'rows' => $data
                ]);
                return;
            }

            // si se envía JSON con filePath en el body
            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input) || !isset($input['filePath']) || empty($input['filePath'])) {
                $this->response->json2(400, 'El parámetro filePath es requerido en JSON o envíe csv_file en form-data');
                return;
            }

            $filePath = $input['filePath'];
            $data = $this->csvReader->readCsv($filePath);

            $this->response->json2(200, 'Datos del CSV cargados exitosamente (filePath)', $data);
        } catch (Exception $e) {
            $this->response->json2(500, 'Error al leer el CSV: ' . $e->getMessage());
        }
    }

    public function uploadCsv()
    {
        try {
            // Archivo enviado en form-data 
            if (!isset($_FILES['csv_file'])) {
                $this->response->json2(400, 'No se recibió ningún archivo. Use la clave "csv_file" en form-data.');
                return false;
            }

            $file = $_FILES['csv_file'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $this->response->json2(400, 'Error en la subida del archivo: ' . $this->fileUploadErrorMessage($file['error']));
                return false;
            }

            // validación de extensión
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            if (strtolower($ext) !== 'csv') {
                $this->response->json2(400, 'El archivo debe tener extensión .csv');
                return false;
            }

            // Campo adicional enviado en form-data
            $csvType = $_POST['csv_type'] ?? null;

            $tmpPath = $file['tmp_name'];

            // Leer CSV desde el archivo temporal
            $data = $this->csvReader->readCsv($tmpPath);


            $response = $this->questionService->create($data);

            $this->response->json2(200, 'Proceso completado', $response);
        } catch (InvalidArgumentException $e) {
            $this->response->json2(400, 'Error al procesar el CSV: ' . $e->getMessage(), null);
        } catch (Exception $e) {
            $this->response->json2(500, 'Error al procesar el CSV: ' . $e->getMessage());
            return false;
        }
    }

    public function saveUserAnswerOption()
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            $result = $this->questionService->saveUserAnswer(
                $data['answer_id'] ?? null,
                $data['group_id'],
                $data['user_id'],
                $data['question_id'],
                $data['q_option_id'],
                $data['game_id']
            );

            $this->response->json2(200, 'Respuesta guardada correctamente', $result);
        } catch (Exception $e) {
            $this->response->json2(500, 'Error al guardar la respuesta: ' . $e->getMessage());
        }
    }

    public function getQuestionStats($id)
    {
        try {
            $result = $this->questionService->getQuestionStats($id);
            $this->response->json2(200, 'Estadísticas de la pregunta obtenidas correctamente', $result);
        } catch (Exception $e) {
            $this->response->json2(500, 'Error al obtener las estadísticas: ' . $e->getMessage());
        }
    }

    // helper para mensajes de error de subida
    private function fileUploadErrorMessage(int $code): string
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'El archivo excede el tamaño permitido.';
            case UPLOAD_ERR_PARTIAL:
                return 'El archivo se subió parcialmente.';
            case UPLOAD_ERR_NO_FILE:
                return 'No se subió ningún archivo.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Falta carpeta temporal.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'No se pudo escribir el archivo en disco.';
            case UPLOAD_ERR_EXTENSION:
                return 'La subida fue detenida por la extensión.';
            default:
                return 'Error desconocido en la subida.';
        }
    }

    // Generación de preguntas en json con IA (Gemini)
    public function generateWithAI(): void
    {
        $body = json_decode(file_get_contents("php://input"), true) ?? [];

        $count = (int)($body["count"] ?? 5); // cantidad de preguntas
        $difficulty = $body["difficulty"] ?? "baja"; // baja|media|alta
        $lang = strtolower(trim((string)($body["lang"] ?? "es"))); // es|en

        if (!in_array($lang, ["es", "en"], true)) {
            Response::json2(400, "language no válido (es|en)", null);
            return;
        }

        if ($count < 5 || $count > 15) {
            Response::json2(400, "cantidad debe estar entre 5 y 15", null);
            return;
        }
        if (!in_array($difficulty, ["baja", "media", "alta"], true)) {
            Response::json2(400, "dificultad no válida (baja|media|alta)", null);
            return;
        }
        // ejes temáticos para las preguntas (español e inglés)
        $axesByLang = [
            "es" => [
                "Conceptos de periodontitis",
                "Factores de riesgo y causas principales",
                "Síntomas de alerta y consecuencias",
                "Medidas preventivas",
            ],
            "en" => [
                "Periodontitis concepts",
                "Risk factors and main causes",
                "Warning signs and consequences",
                "Preventive measures",
            ],
        ];

        $axes = $axesByLang[$lang]; // set de ejes según idioma

        $base = intdiv($count, count($axes));
        $rem  = $count % count($axes);

        $distribution = [];
        foreach ($axes as $idx => $axis) {
            $distribution[$axis] = $base + ($idx < $rem ? 1 : 0);
        }

        // generar detalle de la distribución
        $distribDetail = "";
        foreach ($distribution as $axis => $n) {
            if ($n > 0) { // no listar ejes con 0
                $distribDetail .= "- {$axis}: {$n} preguntas\n";
            }
        }

        $languageRule = ($lang === "en")
            ? "- Write ALL texts in English (neutral).\n"
            : "- Redacta TODO en Español (neutral).\n";

        // Schema del formato que se requiere recibir de Gemini AI
        $schema = [
            "type" => "array",
            "items" => [
                "type" => "object",
                "properties" => [
                    "EJE" => [ // eje temático
                        "type" => "string",
                        "enum" => $axes // ejes segun idioma
                    ],
                    "TITULO_PREGUNTA" => ["type" => "string", "maxLength" => 50],
                    "DESCRIPCION_PREGUNTA" => ["type" => "string", "maxLength" => 150],
                    "NOTA_CONSEJO" => ["type" => "string", "maxLength" => 75],
                    "OPCIONES" => [
                        "type" => "array",
                        "maxItems" => 4, // fijo a 4 opciones x preg.
                        "items" => [
                            "type" => "object",
                            "properties" => [
                                "TEXTO_OPCION" => ["type" => "string", "maxLength" => 100],
                                "ES_CORRECTA" => ["type" => "boolean"],
                            ],
                            "required" => ["TEXTO_OPCION", "ES_CORRECTA"],
                        ]
                    ],
                    "RETROALIMENTACION" => ["type" => "string", "maxLength" => 170],
                ],
                "required" => ["EJE", "TITULO_PREGUNTA", "DESCRIPCION_PREGUNTA", "NOTA_CONSEJO", "OPCIONES", "RETROALIMENTACION"],
            ]
        ];


        // Prompt base 
        $prompt =
            "Genera {$count} preguntas tipo test para concientizar sobre periodontitis.\n" .
            "Dificultad: {$difficulty}.\n" .
            "Formato de salida: DEVUELVE SOLO un JSON válido cuyo valor raíz sea un ARRAY [].\n" .
            "Ejes temáticos y distribución OBLIGATORIA (respeta exactamente estas cantidades):\n" .
            "{$distribDetail}\n" .
            "Cada elemento del array es un objeto con: EJE (debe ser exactamente uno de los ejes listados), TITULO_PREGUNTA, DESCRIPCION_PREGUNTA, NOTA_CONSEJO, OPCIONES, RETROALIMENTACION.\n" .
            "Reglas:\n" .
            "- TITULO_PREGUNTA es un texto breve que categoriza la pregunta.\n" .
            "- DESCRIPCION_PREGUNTA es el enunciado completo de la pregunta.\n" .
            "- NOTA_CONSEJO es un texto breve tipo pista que ayuda a guiar a la opción correcta sin revelar la respuesta ni ser demasiado obvio.\n" .
            "- OPCIONES debe tener EXACTAMENTE 4 elementos.\n" .
            "- EXACTAMENTE 1 opción con ES_CORRECTA=TRUE (boolean real).\n" .
            "- Los textos de las opciones no deben ser muy similares entre sí y deben ser plausibles para evitar respuestas obvias.\n" .
            $languageRule .
            "- El campo EJE debe estar en el mismo idioma indicado.\n" .
            "- RETROALIMENTACION: mensaje informativo que explica la respuesta correcta y amplía el aprendizaje.\n" .
            "- No debe indicar si el usuario acertó o falló (NO usar frases como 'Correcto', 'Incorrecto', '¡Bien!', 'Fallaste', 'Tu respuesta', etc.).\n" .
            "- No juzgues ni regañes; tono educativo, claro y neutral.\n" .
            "- RETROALIMENTACION debe ser coherente con la opción marcada como correcta (ES_CORRECTA=true) y NO debe revelar la respuesta de forma explícita tipo 'La respuesta es la opción X'.\n" .
            "- No incluyas tratamientos o términos clínicos avanzados.\n" .
            "- No repitas preguntas ni opciones demasiado parecidas.\n" .
            "- No uses markdown, no agregues texto fuera del JSON.\n";

        try {
            require_once __DIR__ . '/../Service/GeminiAIService.php';
            $client = new GeminiAIService();
            $result = $client->generateJson($prompt, $schema);

            // si Gemini devuelve { "questions": [...] }
            if (is_array($result) && isset($result["questions"]) && is_array($result["questions"])) {
                $result = $result["questions"];
            }

            // Validación del resultado
            if (!is_array($result) || count($result) === 0) {
                throw new Exception("La IA no devolvió un array de preguntas");
            }

            if (count($result) !== $count) { // cantidad incorrecta
                throw new Exception(
                    "Se obtuvieron menos preguntas de las solicitadas. Obtenidas: " . count($result)
                );
            }

            $counts = array_fill_keys(array_keys($distribution), 0); // iniciar contador segun ejes

            foreach ($result as $i => $q) {
                $axis = $q["EJE"] ?? null;
                if (!isset($distribution[$axis])) {
                    throw new Exception("Pregunta #" . ($i + 1) . " tiene un eje inválido o faltante");
                }
                $counts[$axis]++;
            }

            foreach ($distribution as $axis => $expected) {
                if ($counts[$axis] !== $expected) {
                    throw new Exception("Distribución por eje incorrecta en '{$axis}'. Esperado {$expected}, obtenido {$counts[$axis]}");
                }
            }

            // validación de opciones y retroalimentación
            foreach ($result as $i => $q) {
                if (!isset($q["OPCIONES"]) || !is_array($q["OPCIONES"]) || count($q["OPCIONES"]) !== 4) {
                    throw new Exception("Pregunta #" . ($i + 1) . " no tiene 4 opciones");
                }

                if (!isset($q["RETROALIMENTACION"]) || !is_string($q["RETROALIMENTACION"])) {
                    throw new Exception("Pregunta #" . ($i + 1) . " no tiene RETROALIMENTACION válida");
                }


                $correct = 0;
                foreach ($q["OPCIONES"] as $j => $opt) {
                    if (!array_key_exists("ES_CORRECTA", $opt)) {
                        throw new Exception("Pregunta #" . ($i + 1) . " opción #" . ($j + 1) . " no tiene campo ES_CORRECTA");
                    }

                    if (!is_bool($opt["ES_CORRECTA"])) {
                        throw new Exception("Pregunta #" . ($i + 1) . " opción #" . ($j + 1) . " debe ser booleana (true/false)");
                    }

                    if ($opt["ES_CORRECTA"] === true) {
                        $correct++;
                    }
                }

                if ($correct !== 1) {
                    throw new Exception("Pregunta #" . ($i + 1) . " no tiene exactamente 1 opción correcta");
                }
            }


            foreach ($result as &$q) { // agregar campo lang al resultado
                $q["LANG"] = $lang; // "es" o "en"
            }
            unset($q);

            /* foreach ($result as &$q) { // quitar campo eje (no bd)
                unset($q["EJE"]);
            }
            unset($q); */

            // guardar preguntas generadas
            $data = $this->questionService->saveAiQuestions($result);


            // devolver JSON al front ($result 2 postman)
            Response::json2(200, "Preguntas generadas con Gemini AI exitosamente", $data);
        } catch (Exception $e) {
            Response::json2(500, "Error de Gemini AI: " . $e->getMessage(), null);
        }
    }


    public function deactivateQuestion(string $questionId): void
    {
        try {
            $this->questionService->deactivateQuestion($questionId);
            Response::json2(200, 'Pregunta desactivada exitosamente', null);
        } catch (Exception $e) {
            Response::json2(500, 'Error al desactivar la pregunta: ' . $e->getMessage(), null);
        }
    }

    public function createNewQuestion(): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true) ?? [];

            $title = trim((string)($data['title'] ?? ''));
            if ($title === '' || mb_strlen($title) > 100) {
                Response::json2(400, 'Titulo invalido o excede longitud', null);
                return;
            }

            $description = trim((string)($data['description'] ?? ''));
            if ($description === '' || mb_strlen($description) > 255) {
                Response::json2(400, 'Descripcion invalida o excede longitud', null);
                return;
            }

            $tipNote = $data['tip_note'] ?? null;
            if ($tipNote !== null) {
                $tipNote = trim((string)$tipNote);
                if ($tipNote === '') $tipNote = null;
            }

            $lang = strtolower(trim((string)($data['lang'] ?? '')));
            if (!in_array($lang, ['es', 'en'], true)) {
                Response::json2(400, 'lang no válido (es | en)', null);
                return;
            }

            $feedback = $data['feedback'] ?? null;
            if ($feedback !== null) {
                $feedback = trim((string)$feedback);
                if ($feedback === '') $feedback = null;
            }

            $options = $data['options'] ?? null;
            if (!is_array($options) || count($options) !== 4) {
                Response::json2(400, 'options contiene un número inválido de elementos', null);
                return;
            }

            $correctCount = 0;
            $cleanOptions = [];

            foreach ($options as $idx => $opt) {
                if (!is_array($opt)) {
                    Response::json2(400, "formato inválido - options[$idx]", null);
                    return;
                }

                $text = trim((string)($opt['text_option'] ?? ''));
                if ($text === '' || mb_strlen($text) > 255) {
                    Response::json2(400, "opcion de respuesta invalida o excede longitud - options[$idx]", null);
                    return;
                }

                $isCorrect = (bool)($opt['is_correct'] ?? false);
                if ($isCorrect) $correctCount++;

                $cleanOptions[] = [
                    'text_option' => $text,
                    'is_correct' => $isCorrect
                ];
            }

            if ($correctCount !== 1) {
                Response::json2(400, 'la pregunta no tiene exactamente una opción correcta', null);
                return;
            }

            $question = [
                'title' => $title,
                'description' => $description,
                'tip_note' => $tipNote,
                'lang' => $lang,
                'feedback' => $feedback,
                'options' => $cleanOptions
            ];

            $result = $this->questionService->createNewQuestion($question);
            Response::json2(200, 'Pregunta creada exitosamente', $result);
        } catch (RuntimeException $e) {
            Response::json2(409, $e->getMessage(), null);
        } catch (Exception $e) {
            Response::json2(500, 'Error al crear la pregunta: ' . $e->getMessage(), null);
        }
    }

    public function getById(string $questionId): void
    {
        try {
            // validar ID antes de la consulta
            $questionId = trim($questionId);
            if (!preg_match('/^[0-9a-fA-F-]{36}$/', $questionId)) {
                Response::json2(400, 'ID de pregunta inválido', null);
                return;
            }

            $result = $this->questionService->getById($questionId);

            Response::json2(200, "Datos de pregunta obtenidos", $result);
        } catch (RuntimeException $e) {
            Response::json2(404, $e->getMessage(), null);
        } catch (Throwable $e) {
            Response::json2(500, "Error interno del servidor", null);
        }
    }

    public function updateQuestion(string $questionId): void
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true) ?? [];

            $title = trim((string)($data["title"] ?? ""));
            $description = trim((string)($data["description"] ?? ""));
            $tipNote = trim((string)($data["tip_note"] ?? ""));
            $lang = strtolower(trim((string)($data["lang"] ?? "")));
            $feedback = trim((string)($data["feedback"] ?? ""));

            if ($title === "" || mb_strlen($title) > 100) {
                Response::json2(400, "titulo invalido o excede longitud", null);
                return;
            }
            if ($description === "" || mb_strlen($description) > 255) {
                Response::json2(400, "descripcion invalida o excede longitud", null);
                return;
            }
            if (!in_array($lang, ["es", "en"], true)) {
                Response::json2(400, "lang no valido (es|en)", null);
                return;
            }

            if ($tipNote === "") {
                Response::json2(400, "tip note es invalido", null);
                return;
            }
            if ($feedback === "") {
                Response::json2(400, "feedback es invalido", null);
                return;
            }

            $options = $data["options"] ?? null;
            if (!is_array($options) || count($options) < 2 || count($options) > 4) {
                Response::json2(400, "options contiene un numero invalido de elementos", null);
                return;
            }

            $normalizedOptions = [];
            $correctCount = 0;

            foreach ($options as $i => $opt) {
                $optId = trim((string)($opt["id"] ?? ""));
                if (!preg_match("/^[0-9a-fA-F-]{36}$/", $optId)) {
                    Response::json2(400, "id invalido de options[" . $i . "]", null);
                    return;
                }

                $text = trim((string)($opt["text_option"] ?? ""));
                if ($text === "" || mb_strlen($text) > 255) {
                    Response::json2(400, "options[" . $i . "] no contiene texto válido o excede longitud", null);
                    return;
                }

                $raw = $opt["is_correct"] ?? 0;
                $isCorrect = ($raw === true || $raw === 1 || $raw === "1");
                $correctCount += $isCorrect ? 1 : 0;

                $normalizedOptions[] = [
                    "id" => $optId,
                    "text_option" => $text,
                    "is_correct" => $isCorrect ? 1 : 0
                ];
            }

            if ($correctCount !== 1) {
                Response::json2(400, "debe haber exactamente 1 opcion correcta", null);
                return;
            }

            $result = $this->questionService->updateQuestion(
                $questionId,
                $title,
                $description,
                $tipNote,
                $lang,
                $feedback,
                $normalizedOptions
            );

            Response::json2(200, "Pregunta actualizada", $result);
        } catch (RuntimeException $e) {
            Response::json2(400, $e->getMessage(), null);
        } catch (Throwable $e) {
            Response::json2(500, "Error interno del servidor", null);
        }
    }

    public function searchQuestions(): void
    {
        try {
            // q: búsqueda por title/description (opcional)
            $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
            if ($q !== '' && mb_strlen($q) > 100) {
                $this->response->json2(400, 'La búsqueda excede la longitud permitida', null);
                return;
            }
            $q = ($q === '') ? null : $q;

            // ai: all | 1 | 0 (opcional)
            $aiRaw = isset($_GET['ai']) ? strtolower(trim((string)$_GET['ai'])) : 'all';
            $ai = null;
            if ($aiRaw !== '' && $aiRaw !== 'all') {
                if (!in_array($aiRaw, ['0', '1'], true)) {
                    $this->response->json2(400, 'ai no válido (all | 1 | 0)', null);
                    return;
                }
                $ai = (int)$aiRaw; // 0 o 1
            }

            // lang: all | es | en (opcional)
            $langRaw = isset($_GET['lang']) ? strtolower(trim((string)$_GET['lang'])) : 'all';
            $lang = null;
            if ($langRaw !== '' && $langRaw !== 'all') {
                if (!in_array($langRaw, ['es', 'en'], true)) {
                    $this->response->json2(400, 'lang no válido (all | es | en)', null);
                    return;
                }
                $lang = $langRaw;
            }
            // status: all | active | inactive (opcional)
            $statusRaw = isset($_GET['status']) ? strtolower(trim((string)$_GET['status'])) : 'all'; // default = all
            $status = null;

            if ($statusRaw !== '' && $statusRaw !== 'all') { // si es 'active' o 'inactive'
                if (!in_array($statusRaw, ['active', 'inactive'], true)) {
                    $this->response->json2(400, 'status no válido (all | active | inactive)', null);
                    return;
                }
                $status = $statusRaw;
            }

            $questions = $this->questionService->searchQuestions($q, $ai, $lang, $status);
            $this->response->json2(200, 'Listado de preguntas (filtradas)', $questions);
        } catch (Throwable $e) {
            $this->response->json2(500, 'Error al filtrar preguntas: ' . $e->getMessage(), null);
        }
    }

    public function downloadCsvTemplate(): void
    {
        $root = dirname(__DIR__, 2);
        $path = $root . '/resources/downloads/questions_template_v2.csv'; // ruta al archivo CSV plantilla

        if (!is_file($path)) {
            Response::json2(404, "CSV template file not found");
            return;
        }

        // evita que se imprima cualquier warning/notice en la salida del CSV
        ini_set('display_errors', '0');

        // limpiar buffers para evitar que se corrompa el archivo
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            Response::json2(500, "Error reading CSV template file");
            return;
        }

        // detectar encoding y convertir a UTF-8 si es necesario
        $detected = mb_detect_encoding($raw, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true);
        if ($detected && $detected !== 'UTF-8') {
            $raw = mb_convert_encoding($raw, 'UTF-8', $detected);
        }

        // si el archivo ya trae BOM, se remueve para no duplicarlo
        if (strncmp($raw, "\xEF\xBB\xBF", 3) === 0) {
            $raw = substr($raw, 3);
        }

        $out = "\xEF\xBB\xBF" . $raw; // BOM + contenido

        $downloadFileName = 'questions_template.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $downloadFileName . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Content-Length: ' . strlen($out));

        echo $out;
        exit;
    }

    public function reactivateQuestion(string $questionId): void
    {
        try {
            $this->questionService->reactivateQuestion($questionId);
            Response::json2(200, 'Pregunta reactivada exitosamente', null);
        } catch (Exception $e) {
            Response::json2(500, 'Error al reactivar la pregunta: ' . $e->getMessage(), null);
        }
    }
}
