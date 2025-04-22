
<?php   
   include("clases.php");  
   session_start();

   // Validar si el parámetro 'id' está presente en la URL
   if (!isset($_GET['id']) || empty($_GET['id'])) {
       die("ID de usuario no proporcionado.");
   }                                                        
   $user_id = $_GET['id'];
   $connexion = new mysqli('192.168.1.49','Afrodita','alvaro','afrodita);   
   if ($connexion->connect_error) {
         die("Connexió fallida " . $connexion->connect_error);
   }   
   $login = new LogIn($_SESSION['connexion_db']);   
   $_SESSION['user_id'] = $user_id;
   $_SESSION['last_activity'] = time(); // Iniciar el temporizador de inactividad       
   echo($login->getUsername($_SESSION['user_id']));                                              						           
   

?>
