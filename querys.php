<?php
   include("classes.php");
   session_start();
   CheckInactivityTimer();
   
   $data_base_link = $_SESSION['connexion_db'];  
   $query_manager = new queryManager($data_base_link->$connexion,$_SERVER['REQUEST_URI'],$_SESSION['user_id']); 
   $query_manager->obtainTable();
   $query_manager->parseQuery();  
   $data_base_link->connect();  
   $query_manager->ConvertQuerytoSQL();  
   echo($query_manager->ConvertQuerySQLtoClient());   
   $data_base_link->disconnect();
?>
