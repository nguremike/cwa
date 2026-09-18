<?php
return [
    'app' => [
        'name'     => 'CWA Member System',
        'env'      => 'development', // development | production
        'url'      => 'http://localhost/cwa',
        'timezone' => 'Africa/Nairobi',
    ],
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'database' => 'cwa_db',
        'username' => 'pos',
        'password' => 'Alaska001',
        'charset'  => 'utf8mb4',
    ],
    'session' => [
        'name'     => 'cwa_session',
        'lifetime' => 7200,
    ],
];
