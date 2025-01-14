<?php

ini_set('memory_limit', '256M');
require_once '../vendor/autoload.php';

use Dotenv\Dotenv;
use App\ImapHandler;
use App\MondayAPI;
use App\LeadsProcessor;
use App\AIModel;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$imapConfig = [
    'host' => $_ENV['IMAP_HOST'],
    'port' => $_ENV['IMAP_PORT'],
    'username' => $_ENV['IMAP_USER'],
    'password' => $_ENV['IMAP_PASSWORD'],
];

$apiTokenMonday = $_ENV['MONDAY_API_TOKEN'];
$openAIToken = $_ENV['OPENAI_API_KEY'];

$imapHandler = new ImapHandler(
    $imapConfig['host'], 
    $imapConfig['username'], 
    $imapConfig['password']
);

$mondayHandler = new MondayAPI($apiTokenMonday);
$aiProcessor = new AIModel($openAIToken);

$leadsProcessor = new LeadsProcessor($mondayHandler, $aiProcessor);

try {
    $imapHandler->connect();
    $emails = $imapHandler->getUnreadEmails();

    foreach ($emails as $email) {
        $leadsProcessor->processLead($email['body']);
    }

    echo "Leads procesados con éxito!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
