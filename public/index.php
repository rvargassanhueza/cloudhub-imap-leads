<?php

require_once '../vendor/autoload.php';

use Dotenv\Dotenv;
use OpenAI\Client;
use App\ImapHandler;
use App\MondayAPI;
use App\LeadsProcessor;
use App\AIModel;

$logFile = __DIR__ . '/../logs/process.log';
if (!file_exists(dirname($logFile))) {
    mkdir(dirname($logFile), 0777, true);
}
ini_set('log_errors', '1');
ini_set('error_log', $logFile);

/**
 * Registra mensajes en el archivo de log.
 * 
 * @param string $level El nivel de log (INFO, ERROR, etc.).
 * @param string $message El mensaje a registrar.
 */
function logMessage($level, $message) {
    global $logFile;
    $date = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$date] [$level] $message" . PHP_EOL, FILE_APPEND);
}

/**
 * Carga las configuraciones de variables de entorno desde el archivo .env.
 */
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$imapConfig = [
    'host' => $_ENV['IMAP_HOST'] ?? null,
    'port' => $_ENV['IMAP_PORT'] ?? null,
    'username' => $_ENV['IMAP_USER'] ?? null,
    'password' => $_ENV['IMAP_PASSWORD'] ?? null,
];

$apiTokenMonday = $_ENV['MONDAY_API_TOKEN'] ?? null;
$openAIToken = $_ENV['OPENAI_API_KEY'] ?? null;


if (!$imapConfig['host'] || !$imapConfig['username'] || !$imapConfig['password']) {
    logMessage('ERROR', 'Faltan configuraciones de IMAP.');
    exit('Error: Configuraciones de IMAP incompletas.');
}

if (!$apiTokenMonday || !$openAIToken) {
    logMessage('ERROR', 'Faltan claves de API.');
    exit('Error: Configuraciones de API incompletas.');
}

$imapHandler = new ImapHandler(
    $imapConfig['host'], 
    $imapConfig['username'], 
    $imapConfig['password']
);

$mondayHandler = new MondayAPI($apiTokenMonday);
$aiProcessor = new AIModel($openAIToken);

$leadsProcessor = new LeadsProcessor($mondayHandler, $aiProcessor);

/**
 * Limpia el cuerpo del correo, eliminando etiquetas HTML y espacios innecesarios.
 * 
 * @param string $body El cuerpo del correo a limpiar.
 * @return string El cuerpo limpio.
 */
function cleanEmailBody($body) {
    $body = strip_tags($body);
    $body = preg_replace('/\s+/', ' ', $body);
    return trim($body);
}

/**
 * Cuenta el número de tokens en un texto dado.
 * 
 * @param string $text El texto para contar los tokens.
 * @return int El número de tokens.
 */
function countTokens($text) {
    $words = str_word_count($text, 1, 'áéíóúüÁÉÍÓÚÜñÑ');
    return count($words);
}

/**
 * Divide el texto en fragmentos si excede el límite de tokens.
 * 
 * @param string $text El texto a dividir.
 * @param int $maxTokens El número máximo de tokens permitidos (por defecto 8192).
 * @return array Un array de fragmentos de texto.
 */
function splitTextIfTooLong($text, $maxTokens = 8192) {
    $maxMessageTokens = $maxTokens - 50;

    if (countTokens($text) > $maxMessageTokens) {
        $paragraphs = explode("\n", $text);
        $chunks = [];
        $currentChunk = "";

        foreach ($paragraphs as $paragraph) {
            if (countTokens($currentChunk . " " . $paragraph) > $maxMessageTokens) {
                $chunks[] = trim($currentChunk);
                $currentChunk = $paragraph;
            } else {
                $currentChunk .= " " . $paragraph;
            }
        }
        if (!empty(trim($currentChunk))) {
            $chunks[] = trim($currentChunk);
        }
        return $chunks;
    }
    return [$text];
}

/**
 * Regula el uso de tokens para no superar el límite de tokens por minuto.
 * 
 * @param int $tokensUsed El número de tokens utilizados en la última operación.
 * @param int $tokensPerMinute El límite de tokens por minuto (por defecto 1000).
 */
function throttleTokenUsage($tokensUsed, $tokensPerMinute = 1000) {
    static $tokensInLastMinute = 0;
    static $lastRequestTime = 0;
    $currentTime = time();

    if ($currentTime - $lastRequestTime > 60) {
        $tokensInLastMinute = 0;
        $lastRequestTime = $currentTime;
    }

    if ($tokensInLastMinute + $tokensUsed > $tokensPerMinute) {
        sleep(60 - ($currentTime - $lastRequestTime));
        $tokensInLastMinute = 0;
    }

    $tokensInLastMinute += $tokensUsed;
}

/**
 * Procesa los correos no leídos, limpiando y dividiendo el texto, y enviándolo para su procesamiento.
 */
function processEmails() {
    global $imapHandler, $leadsProcessor;

    try {
        logMessage('INFO', 'Iniciando conexión IMAP.');
        $imapHandler->connect();
        logMessage('INFO', 'Conexión IMAP exitosa.');

        $emails = $imapHandler->getUnreadEmails();
        logMessage('INFO', 'Correos no leídos: ' . count($emails));

        foreach ($emails as $index => $email) {
            logMessage('INFO', "Procesando correo #{$index}: " . $email['subject']);
            $cleanedBody = cleanEmailBody($email['body']);

            logMessage('INFO', "Cuerpo limpio del correo #{$index}: " . $cleanedBody);
            
            if (empty($cleanedBody)) {
                logMessage('ERROR', "Correo #{$index} tiene un cuerpo vacío.");
                continue;
            }

            $chunks = splitTextIfTooLong($cleanedBody);

            foreach ($chunks as $chunk) {
                $tokensUsed = countTokens($chunk);
                logMessage('INFO', "Fragmento generado con {$tokensUsed} tokens.");
                throttleTokenUsage($tokensUsed);

                try {
                    $leadsProcessor->processLead($chunk);
                    logMessage('INFO', 'Datos del lead: ' . json_encode($leadData, JSON_PRETTY_PRINT));
                    logMessage('INFO', "Correo #{$index} procesado correctamente.");
                } catch (Exception $e) {
                    logMessage('ERROR', "Error procesando correo #{$index}: " . $e->getMessage());
                }
            }
        }
    } catch (Exception $e) {
        logMessage('ERROR', 'Error general: ' . $e->getMessage());
    } finally {
        $imapHandler->close();
        logMessage('INFO', 'Conexión IMAP cerrada.');
    }
}

processEmails();

?>
