<?php   
   include("clases.php");  
   session_start();

   // Validar si el parámetro 'id' está presente en la URL
   if (!isset($_GET['id']) || empty($_GET['id'])) {
       die("ID de usuario no proporcionado.");
   }

   // Sanitizar el parámetro 'id' para mayor seguridad
                                                        
   $user_id = $_GET['id'];
   $data_base_link = new connexionDB('192.168.1.49', 'Afrodita', 'alvaro', 'afrodita');
   $login = new LogIn($data_base_link->connexion);
   
   $_SESSION['connexion_db'] = $data_base_link->connexion;
   $_SESSION['user_id'] = $user_id;
   $_SESSION['last_activity'] = time(); // Iniciar el temporizador de inactividad
   
   $data_base_link->connect(); 
       
   echo($login->getUsername($_SESSION['user_id'])); 
                                                						           
   $data_base_link->disconnect();

?>
