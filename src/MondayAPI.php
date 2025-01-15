<?php

namespace App;

use Exception;

class MondayAPI
{
    /**
     * @var string $apiToken Token de autenticación para la API de Monday.com.
     */
    private $apiToken;

    /**
     * @var string $baseUrl URL base para las solicitudes a la API de Monday.com.
     */
    private $baseUrl = 'https://api.monday.com/v2';

    /**
     * Constructor de la clase MondayAPI.
     * 
     * @param string $apiToken Token de la API para autenticación.
     */
    public function __construct($apiToken)
    {
        $this->apiToken = $apiToken;
    }

    /**
     * Crea un lead en Monday.com.
     * 
     * @param array $leadData Datos del lead a crear.
     * @return string|null ID del lead creado o null en caso de error.
     */
    public function createLead(array $leadData)
    {
        $query = <<<GRAPHQL
        mutation CreateItem(\$boardId: ID!, \$itemName: String!, \$columnValues: JSON!) {
            create_item(board_id: \$boardId, item_name: \$itemName, column_values: \$columnValues) {
                id
            }
        }
GRAPHQL;

        $leadData['email'] = $leadData['email'] ?? 'no-reply@example.com';
        if (!filter_var($leadData['email'], FILTER_VALIDATE_EMAIL)) {
            $leadData['email'] = 'no-reply@example.com';
        }

        $columnValues = [
            'name' => $leadData['nombre'],
            'texto__1' => $leadData['proyecto'] ?? 'N/A',
            'lead_company' => $leadData['modelo'] ?? 'N/A',
            'text' => $leadData['forma_contacto'] ?? 'N/A',
            'lead_email' => [
                'email' => $leadData['email'],
                'text' => $leadData['nombre'] ?? 'Sin nombre',
            ],
            'lead_phone' => $leadData['telefono'] ?? 'N/A',
            'lead_status' => 'Lead nuevo',
        ];

        $variables = [
            'boardId' => "7174246074",
            'itemName' => $leadData['nombre'],
            'columnValues' => json_encode($columnValues),
        ];

        try {
            // Enviar la consulta GraphQL y procesar la respuesta.
            $response = $this->sendGraphQLQuery($query, $variables);

            if (isset($response['create_item']['id'])) {
                return $response['create_item']['id'];
            } else {
                throw new Exception("Error al crear el lead en Monday.com");
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
            return null;
        }
    }

    /**
     * Envia una consulta GraphQL a Monday.com.
     * 
     * @param string $query Consulta GraphQL a ejecutar.
     * @param array $variables Variables para la consulta GraphQL.
     * @return array Respuesta decodificada de la API.
     * @throws Exception Si ocurre un error en la solicitud o en la respuesta.
     */
    public function sendGraphQLQuery($query, $variables = [])
    {
        $url = $this->baseUrl;
        $headers = [
            'Authorization: ' . $this->apiToken,
            'Content-Type: application/json',
        ];

        $data = [
            'query' => $query,
            'variables' => $variables,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception('Error cURL: ' . curl_error($ch));
        }

        curl_close($ch);

        $decodedResponse = json_decode($response, true);

        if (isset($decodedResponse['errors'])) {
            throw new Exception('Error GraphQL: ' . json_encode($decodedResponse['errors']));
        }

        return $decodedResponse['data'] ?? [];
    }
}
