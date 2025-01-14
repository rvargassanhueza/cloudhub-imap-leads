<?php

namespace App;

use Exception;

class LeadsProcessor
{
    private $mondayApi;
    private $aiModel;

    public function __construct(MondayAPI $mondayApi, AIModel $aiModel)
    {
        $this->mondayApi = $mondayApi;
        $this->aiModel = $aiModel;
    }

    public function processLead($emailContent)
    {
        $leadData = $this->extractLeadData($emailContent);

        if (!$leadData) {
            throw new Exception("No se pudieron extraer los datos del lead del contenido del email.");
        }

        return $this->mondayApi->createLead($leadData);
    }

    private function extractLeadData($emailContent)
    {
        $extractedData = $this->aiModel->analyzeEmailContent($emailContent);

        if (!$extractedData || !is_array($extractedData)) {
            return null;
        }

        return [
            'nombre' => $extractedData['nombre'] ?? 'Sin nombre',
            'proyecto' => $extractedData['proyecto'] ?? '',
            'modelo' => $extractedData['tipo_depto'] ?? '',
            'forma_contacto' => $extractedData['forma_contacto'] ?? '',
            'email' => $extractedData['email'] ?? '',
            'telefono' => $extractedData['telefono'] ?? '',
        ];
    }
}
