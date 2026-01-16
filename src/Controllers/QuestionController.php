<?php

declare(strict_types=1);

require_once __DIR__ . '/../Utils/CsvReader.php';
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
            // 1) Si se subió un archivo por form-data (csv_file)
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

            // 2) Si se envía JSON con filePath en el body
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

    // ...existing code...

    public function uploadCsv()
    {
        try {
            // Archivo enviado en form-data con key 'csv_file'
            if (!isset($_FILES['csv_file'])) {
                $this->response->json2(400, 'No se recibió ningún archivo. Use la clave "csv_file" en form-data.');
                return false;
            }

            $file = $_FILES['csv_file'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $this->response->json2(400, 'Error en la subida del archivo: ' . $this->fileUploadErrorMessage($file['error']));
                return false;
            }

            // Validación simple de extensión
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            if (strtolower($ext) !== 'csv') {
                $this->response->json2(400, 'El archivo debe tener extensión .csv');
                return false;
            }

            // Campo adicional enviado en form-data: 'csv_type' (texto)
            $csvType = $_POST['csv_type'] ?? null;

            $tmpPath = $file['tmp_name'];

            // Leer CSV desde el archivo temporal
            $data = $this->csvReader->readCsv($tmpPath);

            // // Devolver tipo y datos (ajusta según necesites)
            // $this->response->json2(200, 'CSV leído correctamente', [
            //     'csv_type' => $csvType,
            //     'rows' => $data
            // ]);

            $response = $this->questionService->create($data);
            $this->response->json2(200, 'Proceso completado', $response);
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

    // Helper para mensajes de error de subida
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

    /*
    // Generación preguntas en json con IA (version anterior 15-01-26)
    public function generateWithAI(): void
    {
        $body = json_decode(file_get_contents("php://input"), true) ?? [];

        $count = (int)($body["count"] ?? 5);
        $difficulty = $body["difficulty"] ?? "baja"; // baja|media|alta

        if ($count < 2 || $count > 25) {
            Response::json2(400, "cantidad debe estar entre 5 y 25", null);
            return;
        }
        if (!in_array($difficulty, ["baja", "media", "alta"], true)) {
            Response::json2(400, "dificultad no válida (baja|media|alta)", null);
            return;
        }

        // Schema del formato que se requiere recibir de Gemini AI
        $schema = [
            "type" => "array",
            "minItems" => $count,
            "maxItems" => $count,
            "items" => [
                "type" => "object",
                "properties" => [
                    "TITULO_PREGUNTA" => ["type" => "string", "minLength" => 10, "maxLength" => 50],
                    "DESCRIPCION_PREGUNTA" => ["type" => "string", "minLength" => 10, "maxLength" => 150],
                    "NOTA_CONSEJO" => ["type" => "string", "minLength" => 10, "maxLength" => 80],
                    "OPCIONES" => [
                        "type" => "array",
                        "minItems" => 3,
                        "maxItems" => 3, // fijo a 3
                        "items" => [
                            "type" => "object",
                            "properties" => [
                                "TEXTO_OPCION" => ["type" => "string", "minLength" => 3, "maxLength" => 90],
                                "ES_CORRECTA" => ["type" => "boolean"],
                            ],
                            "required" => ["TEXTO_OPCION", "ES_CORRECTA"],
                            //"additionalProperties" => false
                        ]
                    ],
                ],
                "required" => ["TITULO_PREGUNTA", "DESCRIPCION_PREGUNTA", "NOTA_CONSEJO", "OPCIONES"],
                //"additionalProperties" => false
            ]
        ];


        // Prompt base (luego se pule)
        $prompt =
            "Genera {$count} preguntas tipo test para concientizar sobre periodontitis.\n" .
            "Dificultad: {$difficulty}.\n" .
            "Formato de salida: DEVUELVE SOLO un JSON válido cuyo valor raíz sea un ARRAY [].\n" .
            "Cada elemento del array es un objeto con: TITULO_PREGUNTA, DESCRIPCION_PREGUNTA, NOTA_CONSEJO, OPCIONES.\n" .
            "Reglas:\n" .
            "- TITULO_PREGUNTA es un texto breve que categoriza la pregunta.\n" .
            "- DESCRIPCION_PREGUNTA es el enunciado completo de la pregunta.\n" .
            "- NOTA_CONSEJO es un texto breve tipo pista para ayudar a responder.\n" .
            "- OPCIONES debe tener EXACTAMENTE 3 elementos.\n" .
            "- EXACTAMENTE 1 opción con ES_CORRECTA=TRUE.\n" .
            "- No uses markdown, no agregues texto fuera del JSON.\n";

        try {
            require_once __DIR__ . '/../Service/GeminiAIService.php';
            $client = new GeminiAIService();
            $result = $client->generateJson($prompt, $schema);

            // Compatibilidad por si Gemini devuelve { "questions": [...] }
            if (is_array($result) && isset($result["questions"]) && is_array($result["questions"])) {
                $result = $result["questions"];
            }

            // Validación básica del resultado
            if (!is_array($result) || count($result) === 0) {
                throw new Exception("La IA no devolvió un array de preguntas");
            }

            foreach ($result as $i => $q) {
                if (!isset($q["OPCIONES"]) || !is_array($q["OPCIONES"]) || count($q["OPCIONES"]) !== 3) {
                    throw new Exception("Pregunta #" . ($i + 1) . " no tiene 3 opciones");
                }

                $correct = 0;
                foreach ($q["OPCIONES"] as $opt) {
                    if (!empty($opt["ES_CORRECTA"])) $correct++;
                }
                if ($correct !== 1) {
                    throw new Exception("Pregunta #" . ($i + 1) . " no tiene exactamente 1 opción correcta");
                }
            }

            $data = $this->questionService->saveAiQuestions($result);


            // devolver JSON al front
            Response::json2(200, "Preguntas generadas con Gemini AI exitosamente", $data);
        } catch (Exception $e) {
            Response::json2(500, "Error de Gemini AI: " . $e->getMessage(), null);
        }
    } */

    // Generación de preguntas en json con IA (Gemini)
    public function generateWithAI(): void
    {
        $body = json_decode(file_get_contents("php://input"), true) ?? [];

        $count = (int)($body["count"] ?? 5);
        $difficulty = $body["difficulty"] ?? "baja"; // baja|media|alta
        $lang = strtolower(trim((string)($body["lang"] ?? "es"))); // es|en

        if (!in_array($lang, ["es", "en"], true)) {
            Response::json2(400, "language no válido (es|en)", null);
            return;
        }

        if ($count < 2 || $count > 10) {
            Response::json2(400, "cantidad debe estar entre 5 y 10", null);
            return;
        }
        if (!in_array($difficulty, ["baja", "media", "alta"], true)) {
            Response::json2(400, "dificultad no válida (baja|media|alta)", null);
            return;
        }
        // ejes temáticos según idioma
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

        // Generar detalle de la distribución
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
            "minItems" => $count,
            "maxItems" => $count,
            "items" => [
                "type" => "object",
                "properties" => [
                    "EJE" => [ // eje temático
                        "type" => "string",
                        "enum" => $axes // ejes segun idioma
                    ],
                    "TITULO_PREGUNTA" => ["type" => "string", "minLength" => 10, "maxLength" => 50],
                    "DESCRIPCION_PREGUNTA" => ["type" => "string", "minLength" => 10, "maxLength" => 150],
                    "NOTA_CONSEJO" => ["type" => "string", "minLength" => 15, "maxLength" => 75],
                    "OPCIONES" => [
                        "type" => "array",
                        "minItems" => 4,
                        "maxItems" => 4, // fijo a 4
                        "items" => [
                            "type" => "object",
                            "properties" => [
                                "TEXTO_OPCION" => ["type" => "string", "minLength" => 10, "maxLength" => 100],
                                "ES_CORRECTA" => ["type" => "boolean"],
                            ],
                            "required" => ["TEXTO_OPCION", "ES_CORRECTA"],
                            //"additionalProperties" => false
                        ]
                    ],
                    "RETROALIMENTACION" => ["type" => "string", "minLength" => 40, "maxLength" => 170],
                ],
                "required" => ["EJE", "TITULO_PREGUNTA", "DESCRIPCION_PREGUNTA", "NOTA_CONSEJO", "OPCIONES", "RETROALIMENTACION"],
                //"additionalProperties" => false
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

            // Compatibilidad por si Gemini devuelve { "questions": [...] }
            if (is_array($result) && isset($result["questions"]) && is_array($result["questions"])) {
                $result = $result["questions"];
            }

            // Validación básica del resultado
            if (!is_array($result) || count($result) === 0) {
                throw new Exception("La IA no devolvió un array de preguntas");
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

            foreach ($result as $i => $q) {
                if (!isset($q["OPCIONES"]) || !is_array($q["OPCIONES"]) || count($q["OPCIONES"]) !== 4) {
                    throw new Exception("Pregunta #" . ($i + 1) . " no tiene 4 opciones");
                }

                // Validar RETROALIMENTACION
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

            /* foreach ($result as &$q) { // quitar campo eje (temporal)
                unset($q["EJE"]);
            }
            unset($q); */

            // guardar preguntas generadas
            $data = $this->questionService->saveAiQuestions($result);


            // devolver JSON al front ($result para postman)
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
}
