<?php
session_start();
include("clases.php");
// Luego verificar sesión
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    die(json_encode(['status' => 'error', 'message' => 'Sesión no válida o expirada.']));
}
//Verificar inactividad
CheckInactivityTimer();
include("connect.php");
try {
    $query_manager = new queryManager($connexion, $_SERVER['REQUEST_URI'], $_SESSION['user_id']);
    $query_manager->ParseQuery();
    $query_manager->ConvertQuerytoSQL();
    echo ($query_manager->ConvertQuerySQLtoClient());
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    die(json_encode(['status' => 'error', 'message' => 'Error en el servidor: ' . $e->getMessage()]));
}
$connexion->close();
?>
