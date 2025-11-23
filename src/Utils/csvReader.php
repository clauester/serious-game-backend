<?php

declare(strict_types=1);


class CsvReader
{

    public static function readCsv(string $filePath, bool $hasHeader = true): array
    {
        $rows = [];
        if (($handle = fopen($filePath, "r")) !== false) {
            $header = [];
            $rowNumber = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                if ($hasHeader && $rowNumber === 0) {
                    $header = $data;
                } else {
                    $rows[] = $hasHeader ? array_combine($header, $data) : $data;
                }
                $rowNumber++;
            }
            fclose($handle);
        }
        return $rows;
    }

    public function rawToJson(array $datos): array
    {
        $preguntas_agrupadas = [];
    
    // 🛑 ATENCIÓN: Eliminamos la verificación de 'rows'
    // porque $datos ahora es directamente el array de filas.

    // Usamos $datos directamente, ya que contiene las filas [0], [1], [2], etc.
    foreach ($datos as $fila) { 
        
        // **Validación de seguridad adicional (Recomendada):**
        // Asegúrate de que $fila sea un array y tenga el ID clave.
        if (!is_array($fila) || !isset($fila['ID_PREGUNTA_UNICA'])) {
             // Si falta el ID, simplemente saltamos esta fila.
             continue;
        }

        // 1. Obtener el ID único de la pregunta
        $id = $fila['ID_PREGUNTA_UNICA'];

        // 2. Definir la estructura de la opción para agruparla
        $opcion = [
            'TEXTO_OPCION' => $fila['TEXTO_OPCION'],
            // Convertimos 'TRUE'/'FALSE' a booleano
            'ES_CORRECTA' => ($fila['ES_CORRECTA'] === 'TRUE')
        ];

        // 3. Verificar si la pregunta ya existe en el nuevo array
        if (!isset($preguntas_agrupadas[$id])) {
            // Inicializar el objeto de la pregunta con datos comunes
            $preguntas_agrupadas[$id] = [
                'ID_PREGUNTA_UNICA' => $id,
                'TITULO_PREGUNTA' => $fila['TITULO_PREGUNTA'],
                'DESCRIPCION_PREGUNTA' => $fila['DESCRIPCION_PREGUNTA'],
                'TIPO_PREGUNTA_ID' => $fila['TIPO_PREGUNTA_ID'],
                'NOTA_CONSEJO' => $fila['NOTA_CONSEJO'],
                'OPCIONES' => [] // Array vacío para almacenar las opciones
            ];
        }

        // 4. Agregar la opción actual
        $preguntas_agrupadas[$id]['OPCIONES'][] = $opcion;
    }

    // Retornamos el array de valores reindexado
    return array_values($preguntas_agrupadas);
    }
}
