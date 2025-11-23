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

    public function createQuestion(array $preguntasArray):bool {

        // 1. Iniciar la Transacción Única
        $this->pdo->beginTransaction(); 
        
        try {
            // 2. Preparar Sentencias (usando el ID_TEMPORAL del array como parámetro)
            $stmt_pregunta = $this->pdo->prepare(
                "CALL serius_game_periodontitits.sp_create_question(?, ?, ?, ?)"
            );
            
            $stmt_opcion = $this->pdo->prepare(
                "CALL serius_game_periodontitits.sp_create_question_option(?, ?, ?)"
            );
            
            // 3. Bucle Externo: Iterar sobre cada Pregunta
            foreach ($preguntasArray as $pregunta) {
                
                // Mapeo para SP de Pregunta (ID_PREGUNTA_UNICA es el ID temporal o de negocio)
                $parametros_pregunta = [
                    //$pregunta['ID_PREGUNTA_UNICA'], // ID temporal
                    $pregunta['TITULO_PREGUNTA'],
                    $pregunta['DESCRIPCION_PREGUNTA'],
                    $pregunta['TIPO_PREGUNTA_ID'],
                    $pregunta['NOTA_CONSEJO']
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
            }
            
            // 5. Commit: Si todo el proceso fue exitoso
            $this->pdo->commit();
            return true;

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

}
