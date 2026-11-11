<?php
require __DIR__ . '/config.php';
echo 'PHP ' . PHP_VERSION . '<br>';
echo 'pdo_mysql loaded: ' . (extension_loaded('pdo_mysql') ? 'yes' : 'NO') . '<br>';
echo 'Host: ' . DB_HOST . ' | Port: ' . DB_PORT . ' | Database: ' . DB_NAME . ' | User: ' . DB_USER . '<br>';
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo 'Connected. Tables: ' . ($tables ? implode(', ', $tables) : 'none yet');
} catch (Throwable $e) {
    echo 'Failed: ' . htmlspecialchars($e->getMessage());
}
