<?php
declare(strict_types=1);


class CsvReader {

    public static function readCsv(string $filePath, bool $hasHeader = true): array {
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
}
