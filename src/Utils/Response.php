<?php

class Response
{

    public static function json2(int $status, string $message, $data = null): void
    {
        // forzar tipo int
        $status = (int) $status;
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        $payload = [
            'status'  => $status,
            'message' => $message,
        ];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
    public static function json($data, $status = 200)
    {
        http_response_code($status);
        header("Content-Type: application/json");
        echo json_encode([
            "success" => $status < 400,
            "data" => $data
        ]);
    }
}
