<?php
   include("classes.php");
   session_start();

   // Verificar si la sesión contiene los datos necesarios
   if (!isset($_SESSION['connexion_db']) || !isset($_SESSION['user_id'])) {
       die("Error: Sesión no inicializada correctamente.");
   }

   // Verificar inactividad
   CheckInactivityTimer();

   // Recuperar la conexión desde la sesión
   $data_base_link = $_SESSION['connexion_db'];  

   // Crear una instancia de queryManager
   $query_manager = new queryManager($data_base_link, $_SERVER['REQUEST_URI'], $_SESSION['user_id']); 

   // Ejecutar las operaciones necesarias
   $query_manager->obtainTable();
   $query_manager->parseQuery();  
   $data_base_link->connect();  
   $query_manager->ConvertQuerytoSQL();  
   echo($query_manager->ConvertQuerySQLtoClient());   
   $data_base_link->disconnect();
?>
