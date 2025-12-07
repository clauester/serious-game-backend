<?php
declare(strict_types=1);

require_once __DIR__ . '/../Utils/CsvReader.php';
require_once __DIR__ . '/../Utils/Response.php';
require_once __DIR__ . '/../Service/QuestionService.php';

class QuestionController {

    private CsvReader $csvReader;
    private Response $response;
    private QuestionService $questionService;

    public function __construct() {
        $this->csvReader = new CsvReader();
        $this->response = new Response();
        $this->questionService = new QuestionService();
    }

    public function findAll() {
        $questions = $this->questionService->findAll();
        $this->response->json2(200, 'Listado de preguntas', $questions);
    }

     public function showCsvData(): void {
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

   public function uploadCsv() {
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

    // Helper para mensajes de error de subida
    private function fileUploadErrorMessage(int $code): string {
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
}