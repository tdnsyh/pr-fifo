<?php
// db.php - PDO connection singleton
$config = require __DIR__ . '/config.php';

function db() : PDO {
    static $pdo = null;
    global $config;
    if ($pdo === null) {
        $pdo = new PDO(
            $config['db']['dsn'],
            $config['db']['user'],
            $config['db']['pass'],
            $config['db']['options']
        );
    }
    return $pdo;
}
