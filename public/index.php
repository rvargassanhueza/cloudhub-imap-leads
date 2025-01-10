<?php

ini_set('memory_limit', '256M');


require_once '../vendor/autoload.php';

use Dotenv\Dotenv;
use App\ImapHandler;
use App\MondayAPI;
use App\LeadsProcessor;


$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

/**
 * Configuración de conexión IMAP
 * @var array $imapConfig
 */
$imapConfig = [
    'host' => $_ENV['IMAP_HOST'],
    'port' => $_ENV['IMAP_PORT'], 
    'username' => $_ENV['IMAP_USER'],
    'password' => $_ENV['IMAP_PASSWORD']
];

/**
 * Token de la API de Monday
 * @var string $apiToken
 */
$apiToken = $_ENV['MONDAY_API_TOKEN'];


$imapHandler = new ImapHandler(
    $imapConfig['host'], 
    $imapConfig['port'], 
    $imapConfig['username'], 
    $imapConfig['password']
);


$imapHandler->connect();

/**
 * Crear una instancia de MondayAPI
 * @var MondayAPI $mondayHandler
 */
$mondayHandler = new MondayAPI($apiToken);

$leadsProcessor = new LeadsProcessor($imapHandler, $mondayHandler);

$leadsProcessor->processLeads();

echo "Leads procesados con éxito!";
