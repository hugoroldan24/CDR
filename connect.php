<?php
session_start();
$connexion = new mysqli('192.168.1.49','Afrodita','alvaro','afrodita);   
   if ($connexion->connect_error) {
         die("Connexió fallida " . $connexion->connect_error);
   } 
$_SESSION['connexion_db'] = $connexion;
?>
