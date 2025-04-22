<?php
   include("clases.php");
   session_start();

   // Verificar si la sesión contiene los datos necesarios
   if (!isset($_SESSION['connexion_db']) || !isset($_SESSION['user_id'])) {
       die("Error: Sesión no inicializada correctamente.");
   }

   // Verificar inactividad
   CheckInactivityTimer();

  include("connexion.php")

   // Crear una instancia de queryManager
   $query_manager = new queryManager($connexion, $_SERVER['REQUEST_URI'], $_SESSION['user_id']); 

   // Ejecutar las operaciones necesarias
   $query_manager->obtainTable();
   $query_manager->parseQuery();
 
   $query_manager->ConvertQuerytoSQL();  
   echo($query_manager->ConvertQuerySQLtoClient());   

   $connexion->close();
?>
