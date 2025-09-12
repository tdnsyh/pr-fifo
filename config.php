<?php

return [
    'db' => [
        'dsn' => 'mysql:host=localhost;dbname=fifo_inventory;charset=utf8mb4',
        'user' => 'myuser',
        'pass' => 'mypassword',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ],
    ],
    'app' => [
        'name' => 'FIFO Inventory',
        'base_url' => '',
        'allow_registration' => true,
    ]
];
