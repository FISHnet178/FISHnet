<?php
$host	  = 'localhost';
$dbname	  = 'Cooperativa';
$user     = 'nahuel.resala';
$password = '56843589';
$dsn      = "mysql:host=$host;dbname=$dbname;port=3307;charset=utf8mb4";

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
