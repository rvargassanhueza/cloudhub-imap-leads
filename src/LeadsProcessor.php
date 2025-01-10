<?php

namespace App;

class LeadsProcessor
{
    private $imapHandler;
    private $mondayHandler;

    /**
     * Constructor de LeadsProcessor.
     * 
     * @param ImapHandler $imapHandler Instancia del manejador IMAP.
     * @param MondayAPI $mondayHandler Instancia del manejador de la API de Monday.
     */
    public function __construct(ImapHandler $imapHandler, MondayAPI $mondayHandler)
    {
        $this->imapHandler = $imapHandler;
        $this->mondayHandler = $mondayHandler;
    }

    /**
     * Procesa los leads obtenidos de los correos electrónicos no leídos.
     * 
     * Obtiene los correos electrónicos, extrae los datos necesarios y los envía a Monday.
     */
    public function processLeads()
    {

        $emails = $this->imapHandler->getUnreadEmails();

        foreach ($emails as $email) {

            $leadData = $this->extractLeadData($email);

            try {
               
                $this->mondayHandler->createLead($leadData);
                $this->logProcessedLead($leadData);
            } catch (\Exception $e) {
                $this->logError($e->getMessage());
            }

            unset($leadData);
        }

        unset($emails);
    }

    /**
     * Extrae los datos del lead desde el cuerpo del correo electrónico.
     * 
     * @param array $email Array que representa un correo electrónico.
     * @return array Datos extraídos del correo.
     */
    private function extractLeadData($email)
    {
        $body = $email['body'];

        $proyecto = $this->extractField($body, 'Proyecto:');
        $modelo = $this->extractField($body, 'Modelo a Cotizar:');
        $nombre = $this->extractField($body, 'Nombre:');
        $rut = $this->extractField($body, 'RUT:');
        $email = $this->extractField($body, 'Email:');
        $telefono = $this->extractField($body, 'Teléfono:');

        return [
            'proyecto' => $proyecto,
            'modelo' => $modelo,
            'nombre' => $nombre,
            'rut' => $rut,
            'email' => $email,
            'telefono' => $telefono,
        ];
    }

    /**
     * Extrae un campo específico del texto del cuerpo del correo.
     * 
     * @param string $text Texto del cuerpo del correo.
     * @param string $fieldName Nombre del campo a buscar.
     * @return string Valor extraído del campo, o 'N/A' si no se encuentra.
     */
    private function extractField($text, $fieldName)
    {
        $pattern = '/' . preg_quote($fieldName, '/') . '\s*(.+)/';
        if (preg_match($pattern, $text, $matches)) {
            return trim($matches[1]);
        }
        return 'N/A';
    }

    /**
     * Registra los datos de un lead procesado en un archivo de log.
     * 
     * @param array $lead Datos del lead procesado.
     */
    private function logProcessedLead($lead)
    {
        file_put_contents(__DIR__ . '/../logs/processed.log', json_encode($lead) . PHP_EOL, FILE_APPEND);
    }

    /**
     * Registra un mensaje de error en un archivo de log.
     * 
     * @param string $message Mensaje de error.
     */
    private function logError($message)
    {
        file_put_contents(__DIR__ . '/../logs/errors.log', $message . PHP_EOL, FILE_APPEND);
    }
}
