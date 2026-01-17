<?php

class GeminiAIService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = getenv('GEMINI_API_KEY') ?: '';
        $this->model  = getenv('GEMINI_MODEL') ?: 'gemini-2.5-flash';

        if ($this->apiKey === '') {
            throw new Exception("No se encuentra GEMINI_API_KEY");
        }
    }

    public function generateJson(string $prompt, array $jsonSchema): array
    {
        // Endpoint oficial REST: models/{model}:generateContent
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent"; // :contentReference[oaicite:1]{index=1}

        /*
        $payload = [
            "contents" => [
                [
                    "role" => "user",
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ],
            "generationConfig" => [
                // Structured outputs (JSON + schema)
                "response_mime_type" => "application/json",
                "response_json_schema" => $jsonSchema,
                "temperature" => 0.4,
                "maxOutputTokens" => 3200
            ]
        ]; // :contentReference[oaicite:2]{index=2}
*/
        $payload = [
            "contents" => [
                [
                    "role" => "user",
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ],
            "generationConfig" => [
                "responseMimeType" => "application/json",
                "responseSchema"   => $jsonSchema,   // <- schema usado
                "candidateCount"   => 1,
                "temperature"      => 0.4,
                "maxOutputTokens"  => 3900
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL error: {$err}");
        }

        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $resp = json_decode($raw, true);
        if ($http < 200 || $http >= 300) {
            $msg = $resp["error"]["message"] ?? $raw;
            throw new Exception("Gemini HTTP {$http}: {$msg}");
        }

        // Texto generado 
        $text = $resp["candidates"][0]["content"]["parts"][0]["text"] ?? null;
        if (!$text) {
            throw new Exception("Respuesta Gemini inesperada: {$raw}");
        }

        $text = trim($text);

        // quitar fences si aparecieran
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        // Recortar desde el primer inicio de JSON: "{" o "["
        $posObj = strpos($text, '{');
        $posArr = strpos($text, '[');

        $start = null;
        if ($posObj !== false && $posArr !== false) {
            $start = min($posObj, $posArr);
        } elseif ($posObj !== false) {
            $start = $posObj;
        } elseif ($posArr !== false) {
            $start = $posArr;
        }

        if ($start !== null) {
            $text = substr($text, $start);
        }

        $json = json_decode($text, true);
        if (!is_array($json)) {
            throw new Exception("Gemini no devolvió un JSON válido. Texto: {$text}");
        }

        return $json;
    }
}
