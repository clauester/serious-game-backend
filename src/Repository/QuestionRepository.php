<?php

require_once __DIR__ . '/../config/database.php';

class QuestionRepository {
    private $pdo;

    public function __construct() {
        $this->pdo = (new Database())->connect();
    }

    public function getAllQuestions( ) {
        
        $stmt = $this->pdo->prepare("CALL sp_get_all_questions()");

        $stmt->execute();
        $questions = $stmt->fetchAll();
        $stmt->closeCursor();

        return $questions;
    }

    public function createQuestion(array $preguntasArray):array {

        // 1. Iniciar la Transacción Única
        $this->pdo->beginTransaction(); 
        
        $skipped = [];
        $created = 0;

        try {
            // 2. Preparar Sentencias (usando el ID_TEMPORAL del array como parámetro)
            $stmt_pregunta = $this->pdo->prepare(
                "CALL serius_game_periodontitits.sp_create_question(?, ?, ?, ?, ?)"
            );
            
            $stmt_opcion = $this->pdo->prepare(
                "CALL serius_game_periodontitits.sp_create_question_option(?, ?, ?)"
            );
            
            // 3. Bucle Externo: Iterar sobre cada Pregunta
            foreach ($preguntasArray as $pregunta) {

                $titulo = trim($pregunta['TITULO_PREGUNTA']);

                // Evita duplicados por título
                if ($this->questionExistsByTitle($titulo)) {
                    $skipped[] = $titulo;
                    continue;
                }
                
                // Mapeo para SP de Pregunta (ID_PREGUNTA_UNICA es el ID temporal o de negocio)
                $parametros_pregunta = [
                    //$pregunta['ID_PREGUNTA_UNICA'], // ID temporal
                    $titulo,
                    $pregunta['DESCRIPCION_PREGUNTA'],
                   // $pregunta['TIPO_PREGUNTA_ID'],
                    "multiple_option",
                    $pregunta['NOTA_CONSEJO'],
                    $pregunta['AI_GENERATED'] ?? 0
                ];

                // A. **Ejecución 1:** Guardar la Pregunta principal
                if (!$stmt_pregunta->execute($parametros_pregunta)) {
                    throw new \Exception("Fallo al ejecutar SP de Pregunta.");
                }
                
                // B. **Captura del ID Retornado**
                // Asumimos que el SP devuelve una fila con el nuevo ID generado por la DB
                $resultado_sp = $stmt_pregunta->fetch(PDO::FETCH_ASSOC);
                
                if (empty($resultado_sp) || !isset($resultado_sp['id'])) {
                    throw new \Exception("El SP no devolvió el ID generado correctamente.");
                }
                
                // C. Obtener el ID que usaremos como clave foránea
                $nuevo_id_generado = $resultado_sp['id'];
                
                // D. Limpiar el resultado (IMPORTANTE en PDO al usar SPs que devuelven data)
                // Esto libera el cursor para que podamos ejecutar la siguiente sentencia preparada
                $stmt_pregunta->closeCursor();

                
                // 4. Bucle Interno: Iterar sobre las Opciones
                foreach ($pregunta['OPCIONES'] as $opcion) {
                    
                    // Mapeo para SP de Opción
                    $parametros_opcion = [
                        $nuevo_id_generado, // <-- ¡Usamos el ID generado aquí!
                        $opcion['TEXTO_OPCION'],
                        $opcion['ES_CORRECTA'] ? 1 : 0 
                    ];
                    
                    // **Ejecución 2:** Guardar cada Opción
                    if (!$stmt_opcion->execute($parametros_opcion)) {
                        throw new \Exception("Fallo al guardar Opción para Pregunta ID: $nuevo_id_generado");
                    }
                    
                    // Limpiar el resultado (necesario si el SP de opción también devuelve datos)
                    $stmt_opcion->closeCursor();
                }
                $created++;
            }
            
            // 5. Commit: Si todo el proceso fue exitoso
            $this->pdo->commit();

            return [
                'created'        => $created,
                'skipped'        => $skipped,
                'totalReceived' => count($preguntasArray)
            ];

        } catch (\PDOException $e) {
            // 4. Revertir la Transacción (Rollback)
            // Si ocurre cualquier error (Excepción), la base de datos revierte
            // todos los cambios hechos desde beginTransaction().
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            
            // Relanzar la excepción para que la capa de servicio la maneje
            throw new \Exception("Error al guardar datos: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
      
    }

    public function saveUserAnswerOption(
        ?string $answerId,
        string $groupId,
        string $userId,
        string $questionId,
        ?string $qOptionId,
        ?string $gameId
    ): array {
        $stmt = $this->pdo->prepare("CALL sp_register_user_answer(?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $answerId,
            $groupId,
            $userId,
            $questionId,
            $qOptionId,
            $gameId
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        
        return $result ?: [];
    }

    public function getQuestionStats(?string $id) {
        if ($id === 'all') {
            $id = null;
        }
        $stmt = $this->pdo->prepare("CALL sp_get_group_question_stats(:p_group_id)");
        $stmt->bindParam(":p_group_id", $id, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // limpiar más resultsets
        while ($stmt->nextRowset()) {}

        return $data;
    }


    // Comprueba si ya existe una pregunta con el mismo título
private function questionExistsByTitle(string $title): bool
{
    // Ajusta el nombre de la tabla y columna si difieren
    $stmt = $this->pdo->prepare("SELECT id FROM question WHERE title = ? LIMIT 1");
    $stmt->execute([$title]);
    $exists = (bool) $stmt->fetchColumn();
    $stmt->closeCursor();
    return $exists;
}

    public function deactivateQuestion(string $questionId)
    {
        $stmt = $this->pdo->prepare('CALL sp_delete_question(:p_question_id)');
        $stmt->bindParam(':p_question_id', $questionId, PDO::PARAM_STR);
        $stmt->execute();
        
        $stmt->closeCursor();
        return $stmt->fetch();
    }

}
