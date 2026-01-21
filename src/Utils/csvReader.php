<?php

declare(strict_types=1);


class CsvReader
{

    public static function readCsv(string $filePath, bool $hasHeader = true, ?string $delimiter = null): array
    {
        $rows = [];
        if (($handle = fopen($filePath, 'r')) === false) {
            return $rows;
        }

        // auto-detección del delimitador (coma por defecto si no se puede detectar)
        $delimiter = $delimiter ?? self::detectDelimiter($handle);
        rewind($handle);

        $header = [];
        $rowNumber = 0;

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            // saltar filas vacías
            if ($data === [null] || (count($data) === 1 && trim((string)$data[0]) === '')) {
                continue;
            }

            // Header
            if ($hasHeader && $rowNumber === 0) {
                // Normalizar encabezados (trim + quitar BOM UTF-8)
                $header = array_map(static function ($h, $idx) {
                    $h = CsvReader::ensureUTF8(trim((string)$h));
                    if ($idx === 0) {
                        // Quitar BOM si existe
                        $h = preg_replace('/^\xEF\xBB\xBF/', '', $h) ?? $h;
                    }
                    return $h;
                }, $data, array_keys($data));

                // validar encabezados requeridos para la data de preguntas
                $requiredColumns = [
                    'ID_PREGUNTA_UNICA',
                    'TITULO_PREGUNTA',
                    'DESCRIPCION_PREGUNTA',
                    'NOTA_CONSEJO',
                    'RETROALIMENTACION',
                    'IDIOMA',
                    'TEXTO_OPCION',
                    'ES_CORRECTA',
                ];
                $missingCols = array_diff($requiredColumns, $header);
                if (!empty($missingCols)) {
                    throw new InvalidArgumentException('Missing required CSV columns: ' . implode(', ', $missingCols));
                }

                // header no tenga columnas adicionales a las requeridas
                $extraCols = array_diff($header, $requiredColumns);

                if (!empty($extraCols)) {

                    throw new InvalidArgumentException('Unexpected CSV columns: ' . implode(' - ', $extraCols));
                }
                $rowNumber++;
                continue;
            }

            // Si hay header, misma longitud para array_combine
            if ($hasHeader) {
                $data = array_map(static fn($v) => is_string($v) ? CsvReader::ensureUTF8(trim($v)) : $v, $data);

                // Validar que la fila tenga exactamente el mismo número de columnas que el header
                $expected = count($header);
                $actual = count($data);
                if ($actual !== $expected) {
                    throw new InvalidArgumentException(
                        "Fila #" . ($rowNumber + 1) . " - {$expected} columnas esperadas, llegaron {$actual} (posible desalineación)"
                    );
                }

                $combined = array_combine($header, $data);
                if (is_array($combined)) {
                    $rows[] = $combined;
                }
            } else {
                $rows[] = $data;
            }

            $rowNumber++;
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Detecta el delimitador leyendo algunas líneas iniciales.
     * Retorna ',' si no encuentra uno claro.
     */
    private static function detectDelimiter($handle): string
    {
        // delimitadores más comunes
        $candidates = [',', ';', "\t", '|'];
        $scores = array_fill_keys($candidates, 0);

        $maxLines = 10;
        $read = 0;

        while (!feof($handle) && $read < $maxLines) {
            $line = fgets($handle);
            if ($line === false) {
                break;
            }
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            foreach ($candidates as $d) {
                $scores[$d] += substr_count($line, $d);
            }
            $read++;
        }

        arsort($scores);
        $bestDelimiter = array_key_first($scores);

        if ($bestDelimiter === null || (int)$scores[$bestDelimiter] === 0) {
            return ',';
        }

        return $bestDelimiter;
    }

    // normaliza strings a UTF-8
    private static function ensureUTF8(string $value): string
    {
        // UTF-8 válido retornar tal cual
        if (mb_detect_encoding($value, ['UTF-8'], true) === 'UTF-8') {
            return $value;
        }
        // detectar y convertir desde Windows-1252 o ISO-8859-1
        $enc = mb_detect_encoding($value, ['Windows-1252', 'ISO-8859-1'], true) ?: 'Windows-1252';
        return mb_convert_encoding($value, 'UTF-8', $enc);
    }

    public function rawToJson(array $datos): array
    {
        $preguntas_agrupadas = [];

        $rowIndex = 0;
        // recorrer cada fila del CSV
        foreach ($datos as $fila) {
            $rowIndex++;

            if (!is_array($fila)) {
                throw new InvalidArgumentException("Fila #{$rowIndex}: formato inválido");
            }

            // ID único de la pregunta
            $id = $fila['ID_PREGUNTA_UNICA'];

            // definir la estructura de la opción para agruparla
            $opcion = [
                'TEXTO_OPCION' => $fila['TEXTO_OPCION'],
                // Convertimos 'TRUE'/'FALSE' a booleano 
                'ES_CORRECTA' => (strtoupper(trim((string)($fila['ES_CORRECTA'] ?? ''))) === 'TRUE')
            ];

            // verificar si la pregunta ya existe en el nuevo array
            if (!isset($preguntas_agrupadas[$id])) {
                // Inicializar el objeto de la pregunta con datos comunes
                $preguntas_agrupadas[$id] = [
                    'ID_PREGUNTA_UNICA' => $id,
                    'TITULO_PREGUNTA' => $fila['TITULO_PREGUNTA'],
                    'DESCRIPCION_PREGUNTA' => $fila['DESCRIPCION_PREGUNTA'],
                    //'TIPO_PREGUNTA_ID' => $fila['TIPO_PREGUNTA_ID'],
                    'NOTA_CONSEJO' => $fila['NOTA_CONSEJO'],
                    'RETROALIMENTACION' => $fila['RETROALIMENTACION'] ?? '',
                    'LANG' => strtolower(trim((string)($fila['IDIOMA'] ?? ''))), // Normalizar a minúsculas
                    'OPCIONES' => [] // Array vacío para almacenar las opciones
                ];
            }

            // agregar la opción actual
            $preguntas_agrupadas[$id]['OPCIONES'][] = $opcion;
        }

        // Retornar el array de valores reindexado
        return array_values($preguntas_agrupadas);
    }
}
