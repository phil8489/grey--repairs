<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = 'localhost';
$username = 'root'; // For production, replace with a dedicated least-privilege DB user.
$password = '';
$database = 'electronics_repair';

try {
    $conn = new mysqli($host, $username, $password, $database);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('Grey Repairs database connection error: ' . $e->getMessage());
    http_response_code(500);
    exit('The service is temporarily unavailable. Please try again later.');
}
