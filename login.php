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

-----------------------------------------
<?php   
   include("classes.php");  
   session_start();

   // Validar si el parámetro 'id' está presente en la URL
   if (!isset($_GET['id']) || empty($_GET['id'])) {
       die("ID de usuario no proporcionado.");
   }

   // Sanitizar el parámetro 'id' para mayor seguridad
   $user_id = intval($_GET['id']); // Convertir a entero para evitar inyecciones SQL

   $data_base_link = new connexionDB('localhost', 'dbname', 'username', 'password');
   $login = new LogIn($data_base_link);
   
   $_SESSION['connexion_db'] = $data_base_link;
   $_SESSION['user_id'] = $user_id;
   $_SESSION['last_activity'] = time(); // Iniciar el temporizador de inactividad
   
   $data_base_link->connect(); 
       
   echo($login->getUsername($_SESSION['user_id'])); 
                                                						           
   $data_base_link->disconnect();

?>
