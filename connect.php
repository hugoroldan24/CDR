<?php
try {
    $connexion = new mysqli('localhost', 'alvaro', '', 'Afrodita');

    if ($connexion->connect_error) {
        throw new Exception("Connection failed: " . $connexion->connect_error);
    }

    // Configurar el conjunto de caracteres
    $connexion->set_charset("utf8mb4");
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    http_response_code(500);
    die(json_encode(['status' => 'error', 'message' => 'Error de conexiÃ³n con la base de datos']));
}
?>
