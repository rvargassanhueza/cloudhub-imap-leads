<?php

namespace App;

use Exception;

class AIModel
{
    private $apiKey;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function analyzeEmailContent($emailContent)
    {
        try {
            $ch = curl_init('https://api.openai.com/v1/chat/completions');

            echo $emailContent;

            $payload = json_encode([
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Eres un asistente que analiza contenido de emails para extraer información clave.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->generatePrompt($emailContent),
                    ],
                ],
                'max_tokens' => 200,
            ]);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ]);

            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                throw new Exception('Error en la solicitud CURL: ' . curl_error($ch));
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new Exception('Error en la API de OpenAI: ' . $response);
            }

            $data = json_decode($response, true);
            $parsedData = json_decode($data['choices'][0]['message']['content'], true);

            print_r($parsedData);

            return $parsedData;
        } catch (Exception $e) {
            throw new Exception("Error al analizar el contenido del email con OpenAI: " . $e->getMessage());
        }
    }

    private function generatePrompt($emailContent)
    {
        return <<<PROMPT
A partir del siguiente contenido de un email, extrae los datos clave en formato JSON:
- Nombre
- Proyecto
- Tipo de departamento
- Forma de contacto
- Email
- Teléfono

Contenido del email:
"$emailContent"

Responde solo con un JSON con estos campos, y los datos que no encuentres, por favor, ponlos en `null`.
PROMPT;
    }
}
