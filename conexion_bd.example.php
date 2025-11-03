<?php
// Configuración de la conexión a MySQL
// INSTRUCCIONES: Copia este archivo como 'conexion_bd.php' y configura tus credenciales

$DB_HOST = '127.0.0.1';          // Servidor (localhost o 127.0.0.1)
$DB_NAME = 'login_lab';          // Nombre de tu base de datos
$DB_USER = 'root';               // Usuario de MySQL
$DB_PASS = '';                   // Contraseña de MySQL
$DB_PORT = '3306';               // Puerto de MySQL

// DSN (Data Source Name)
$dsn = "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    exit('Error de conexión a MySQL.');
}