<?php

return [
    'client_id' => $_ENV('MONDAY_CLIENT_ID')?? '',
    'client_secret' => $_ENV('MONDAY_CLIENT_SECRET')?? '',
    'signing_secret' => $_ENV('MONDAY_SIGNING_SECRET')
];
