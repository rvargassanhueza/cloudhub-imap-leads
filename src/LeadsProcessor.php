<?php

namespace App;

use Exception;

class LeadsProcessor
{
    /**
     * @var MondayAPI $mondayApi Instancia de la API de Monday.com para gestionar leads.
     */
    private $mondayApi;

    /**
     * @var AIModel $aiModel Instancia del modelo de IA utilizado para analizar contenido de email.
     */
    private $aiModel;

    /**
     * Constructor de la clase LeadsProcessor.
     * 
     * @param MondayAPI $mondayApi Instancia de la clase para interactuar con Monday.com.
     * @param AIModel $aiModel Instancia de la clase para análisis de contenido mediante IA.
     */
    public function __construct(MondayAPI $mondayApi, AIModel $aiModel)
    {
        $this->mondayApi = $mondayApi;
        $this->aiModel = $aiModel;
    }

    /**
     * Procesa el contenido de un email para extraer información del lead
     * y enviarlo a Monday.com.
     * 
     * @param string $emailContent Contenido del email a analizar.
     * @return string|null ID del lead creado en Monday.com, o null en caso de error.
     * @throws Exception Si no se pueden extraer datos del lead.
     */
    public function processLead($emailContent)
    {
        $leadData = $this->extractLeadData($emailContent);

        if (!$leadData) {
            throw new Exception("No se pudieron extraer los datos del lead del contenido del email.");
        }

        return $this->mondayApi->createLead($leadData);
    }

    /**
     * Extrae datos de un lead del contenido de un email utilizando un modelo de IA.
     * 
     * @param string $emailContent Contenido del email a analizar.
     * @return array|null Datos del lead extraídos o null si no se pueden extraer.
     */
    private function extractLeadData($emailContent)
    {
        $extractedData = $this->aiModel->analyzeEmailContent($emailContent);


        if (!$extractedData || !is_array($extractedData)) {
            return null;
        }

        return [
            'nombre' => $extractedData['Nombre'] ?? 'Sin nombre',
            'proyecto' => $extractedData['Proyecto'] ?? '',
            'modelo' => $extractedData['Tipo_departamento'] ?? '',
            'forma_contacto' => $extractedData['Forma_contacto'] ?? '',
            'email' => $extractedData['Email'] ?? '',
            'telefono' => $extractedData['Telefono'] ?? '',
        ];
    }
}
