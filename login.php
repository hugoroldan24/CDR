<?php
session_start();
include("clases.php");

// Validar y sanitizar el ID
//if (!isset($_GET['id']) || empty($_GET['id'])) {
//    http_response_code(400);
//    die(json_encode(['status' => 'error', 'message' => 'ID de usuario no proporcionado.']));
//}

$user_id = $_GET['id'];
include("connect.php");
$login = new LogIn($connexion,$user_id);
$_SESSION['user_id'] = $user_id;
$_SESSION['connexion'] = true;
$_SESSION['last_activity'] = time();
echo($login->getUsername());


$connexion->close();
?>
