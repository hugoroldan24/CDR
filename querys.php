<?php

session_start();
include("clases.php");

// Primero verificar inactividad
CheckInactivityTimer();

// Luego verificar sesión
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['status' => 'error', 'message' => 'Sesión no válida o expirada.']));
}

include("connect.php");
try {
    $query_manager = new queryManager($connexion, $_SERVER['REQUEST_URI'], $_SESSION['user_id']);

    $query_manager->obtainTable();
    $query_manager->parseQuery();
    $query_manager->ConvertQuerytoSQL();

    header('Content-Type: application/json');
    echo $query_manager->ConvertQuerySQLtoClient();
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['status' => 'error', 'message' => 'Error en el servidor: ' . $e->getMessage()]));
}

$connexion->close();
?>
