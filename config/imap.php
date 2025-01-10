<?php

return [
    'host' => $_ENV['IMAP_HOST'] ?? '',
    'port' => $_ENV['IMAP_PORT'] ?? '',
    'username' => $_ENV['IMAP_USER'] ?? '',
    'password' => $_ENV['IMAP_PASSWORD'] ?? ''
];