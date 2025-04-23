
<?php   
   session_start();
   include("clases.php");  
   include("connect.php");   //Importamos la conexión
   

   // Validar si el parámetro 'id' está presente en la URL
   if (!isset($_GET['id']) || empty($_GET['id'])) {
       die("ID de usuario no proporcionado.");
   }                                                        
   $user_id = $_GET['id'];

   $login = new LogIn($connexion);   
   $_SESSION['user_id'] = $user_id;
   $_SESSION['last_activity'] = time(); // Iniciar el temporizador de inactividad 

   echo($login->getUsername($user_id));                                              						           

   $connexion->close();
   
?>
