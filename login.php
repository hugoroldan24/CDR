<?php
session_start();

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
