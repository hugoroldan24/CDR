#login.php

<?php
include("clases.php");
session_start();

// Configuración segura de cookies de sesión
#session_set_cookie_params([
 #   'lifetime' => 3600,
 #   'path' => '/',
 #   'secure' => true,
 #   'httponly' => true,
 #   'samesite' => 'Strict'
#]);

// Validar y sanitizar el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    die(json_encode(['status' => 'error', 'message' => 'ID de usuario no proporcionado.']));
}

$user_id = trim($_GET['id']);
if ($user_id === false) {
    http_response_code(400);
    die(json_encode(['status' => 'error', 'message' => 'ID de usuario no válido.']));
}

include("connect.php");

$login = new LogIn($connexion);
$_SESSION['user_id'] = $user_id;
$_SESSION['connexion'] = true;
$_SESSION['last_activity'] = time();

header('Content-Type: application/json');
echo $login->getUsername($user_id);

$connexion->close();
?>
