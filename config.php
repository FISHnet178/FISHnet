<?php
$host     = 'localhost';
$port     = 3306;
$dbname   = 'Cooperativa';
$user     = 'Nahuel';
$password = 'River178334$';
$dsn      = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4;sslmode=DISABLED";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
];

try {
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
