<?php   
   include("classes.php");  
   session_start();

   $data_base_link = new connexionDB('localhost','dbname','username','password');
   $login = new LogIn($data_base_link);
   
   $_SESSION['connexion_db'] = $data_base_link;
   $_SESSION['user_id'] = $_GET['id'];
   $_SESSION['last_activity'] = time(); //Iniciamos el temporizador de inactividad
   
   $data_base_link->connect(); 
       
   echo($login->getUsername($_SESSION['user_id'])); 
         						               						           
   $data_base_link->disconnect();

?>
