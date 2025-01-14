<?php

namespace App;

use Exception;

class MondayAPI
{
    private $apiToken;
    private $baseUrl = 'https://api.monday.com/v2';

    public function __construct($apiToken)
    {
        $this->apiToken = $apiToken;
    }

    public function createLead(array $leadData)
    {
        $query = <<<GRAPHQL
        mutation {
            create_item (board_id: 7174246074, group_id: "topics", item_name: "{$leadData['nombre']}", column_values: {
            "name": "{$leadData['nombre']}",
                "texto__1": "{$leadData['proyecto']}",
                "lead_company": "{$leadData['modelo']}",
                "text": "{$leadData['forma_contacto']}",
                "lead_email": {"email": "{$leadData['email']}", "text": "{$leadData['email']}"},
                "lead_phone": "{$leadData['telefono']}",
                "lead_status": {"label": "lead nuevo"},
                "button": null
            }) {
                id
            }
        }
        GRAPHQL;

        $response = $this->makeRequest($query);
        return $response['data']['create_item'] ?? null;
    }

    private function makeRequest($query)
    {
        $headers = [
            'Authorization: ' . $this->apiToken,
            'Content-Type: application/json',
        ];

        $ch = curl_init($this->baseUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $query]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception('Error en la solicitud: ' . curl_error($ch));
        }

        curl_close($ch);
        return json_decode($response, true);
    }
}
