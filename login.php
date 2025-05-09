<?php
session_start(); //Si el Cliente no envia ninguna cookie (process_uid, aun no hemos iniciado sesion), esto te genera una nueva PHPSESSID que se devolverá al cliente. Si al hacer esto, tenemos una cookie existente (en querys.php) accederemos a las variablas de sesion guardadas.
session_regenerate_id(true) //Genera un nuevo identificador de sesion (PHPSESSID) para que el anterior deje de ser válido
$user_id = $_GET['id'];
include("connect.php");
include("clases.php");

$login = new LogIn($connexion,$user_id);
$_SESSION['user_id'] = $user_id;
$_SESSION['connexion'] = true;
$_SESSION['last_activity'] = time();
echo($login->getUsername());
$connexion->close();
?>
