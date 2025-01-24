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

    /**
     * Analiza el contenido de un email utilizando la API de OpenAI para extraer información clave.
     * 
     * @param string $emailContent Contenido del email a analizar.
     * @return array Datos clave extraídos en formato asociativo, o lanza una excepción en caso de error.
     * @throws Exception Si ocurre un error durante la solicitud o procesamiento de la respuesta.
     */
    public function analyzeEmailContent($emailContent)
    {
        try {
            $ch = curl_init('https://api.openai.com/v1/chat/completions');
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
                'max_tokens' => 50,  // Ajuste de max_tokens
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

            // Verificar si la respuesta contiene el formato esperado
            $data = json_decode($response, true);
            if (!isset($data['choices'][0]['message']['content'])) {
                throw new Exception('Respuesta inesperada de la API de OpenAI.');
            }

            $parsedData = json_decode($data['choices'][0]['message']['content'], true);

            // Verificar que los datos están en el formato correcto
            $requiredFields = ['Nombre', 'Proyecto', 'Tipo_departamento', 'Forma_contacto', 'Email', 'Telefono'];
            foreach ($requiredFields as $field) {
                if (!isset($parsedData[$field])) {
                    $parsedData[$field] = null;
                }
            }

            return $parsedData;
        } catch (Exception $e) {
            throw new Exception("Error al analizar el contenido del email con OpenAI: " . $e->getMessage());
        }
    }

    /**
     * Genera el prompt utilizado para extraer datos clave del contenido del email.
     * 
     * @param string $emailContent Contenido del email a analizar.
     * @return string Prompt con instrucciones para OpenAI.
     */
    private function generatePrompt($emailContent)
    {
        return <<<PROMPT
A partir del siguiente contenido de un email, extrae los datos clave en formato JSON:
- Nombre
- Proyecto
- Tipo_departamento
- Forma_contacto
- Email
- Telefono

Contenido del email:
"$emailContent"

Responde solo con un JSON con estos campos, y los datos que no encuentres, por favor, ponlos en `null`.
PROMPT;
    }
}
