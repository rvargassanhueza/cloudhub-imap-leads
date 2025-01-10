<?php
namespace App;

class MondayAPI
{
    private $client;
    private $apiToken;

    /**
     * Constructor de la clase
     *
     * @param string $apiToken Token de autenticación de la API de Monday
     */
    public function __construct($apiToken)
    {
        if (empty($apiToken)) {
            throw new \InvalidArgumentException("El token de la API de Monday no puede estar vacío.");
        }
        $this->apiToken = $apiToken;
    }

    /**
     * Método para crear un lead en Monday.com
     *
     * @param array $leadData Datos del lead
     * @return string ID del lead creado
     */
    public function createLead($leadData)
    {
        if (empty($leadData['nombre']) || empty($leadData['email']) || empty($leadData['telefono'])) {
            throw new \InvalidArgumentException("El nombre, el correo electrónico y el teléfono son obligatorios para crear un lead.");
        }

        $query = <<<GRAPHQL
        mutation CreateItem(\$boardId: ID!, \$itemName: String!, \$columnValues: JSON!) {
            create_item(board_id: \$boardId, item_name: \$itemName, column_values: \$columnValues) {
                id
            }
        }
        GRAPHQL;

        $variables = [
            'boardId' => "7174246074",
            'itemName' => $leadData['nombre'],
            'columnValues' => json_encode([
                'nombre' => $leadData['nombre'],
                'email' => $leadData['email'],
                'telefono' => $leadData['telefono'],
                'proyecto' => $leadData['proyecto'] ?? 'N/A',
                'modelo' => $leadData['modelo'] ?? 'N/A',
                'rut' => $leadData['rut'] ?? 'N/A',
            ]),
        ];

        try {
            $response = $this->sendGraphQLQuery($query, $variables);

            if (isset($response['create_item']['id'])) {
                return $response['create_item']['id'];
            } else {
                throw new \Exception("Error al crear el lead en Monday.com");
            }
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage();
            return null;
        }
    }

    /**
     * Método para enviar una consulta GraphQL a Monday.com
     *
     * @param string $query Consulta GraphQL
     * @param array $variables Variables para la consulta
     * @return array Respuesta de la API
     * @throws \Exception En caso de error
     */
    public function sendGraphQLQuery($query, $variables = [])
    {
        $url = 'https://api.monday.com/v2';
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
            throw new \Exception('Error cURL: ' . curl_error($ch));
        }

        curl_close($ch);

        $decodedResponse = json_decode($response, true);

        if (isset($decodedResponse['errors'])) {
            throw new \Exception('Error GraphQL: ' . json_encode($decodedResponse['errors']));
        }

        return $decodedResponse['data'] ?? [];
    }
}
