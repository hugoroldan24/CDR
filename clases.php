<?php

class connexionDB {

   public $connexion;
   public $host;
   public $dbname;
   public $username;
   public $password;
   
   public function __construct($host, $dbname, $username, $password) {
      $this->host = $host;
      $this->dbname = $dbname;
      $this->username = $username;
      $this->password = $password;
   } 
   public function connect() {
      $this->connexion = new mysqli($this->host, $this->username, $this->password, $this->dbname);
      if ($this->connexion->connect_error) {
         die("Connexió fallida " . $this->connexion->connect_error);
      }	
   }
   public function disconnect() {
      $this->connexion->close();
   }
}

class queryManager {

   public $connexion; 
   public $uri;
   public $id;
   public $sql_rows;
   public $status;
   public $table;
   public $query_array;
  
   public function __construct($conn, $uri, $id) {
      $this->status = "valid_query";
      $this->connexion = $conn;
      $this->uri = $uri;
      $this->id = $id;
   }
   public function parseQuery() {
      parse_str(parse_url($this->uri, PHP_URL_QUERY), $this->query_array); // Parsear la query y guardarla en un array
   }
   public function obtainTable() {
      $this->table = str_replace('/querys.php/', '', parse_url($this->uri, PHP_URL_PATH));
   }
   public function ConvertQuerytoSQL() {
      switch ($this->table) {
         case 'tasks':
            $query_data = $this->getOperatorAndValue($this->query_array['date']);
            $operator = $this->convertOperator($query_data['operator']);
                 
            $date = ($query_data['value'] === 'now') ? date('Y-m-d') : $query_data['value'];
            $this->sql_query = "SELECT day,subject,name FROM tasks WHERE (date $operator $date) AND (uid = $this->id) ORDER BY date";  
            break;
            
         case 'marks':
            $this->sql_query = "SELECT subject,name,mark FROM marks WHERE id = $this->id ORDER BY subject";           
            break;
            
         case 'timetables':
            $query_day = $this->getOperatorAndValue($this->query_array['day']);
            $query_hour = $this->getOperatorAndValue($this->query_array['hour']);
            
            $day_operator = $this->convertOperator($query_day['operator']);
            $hour_operator = $this->convertOperator($query_hour['operator']);
            
            $hour = ($query_hour['value'] === 'now') ? date('H') : $query_hour['value'];
            $day_week = ($query_day['value'] === 'now') ? date('N') : $this->ConvertDaytoNum($query_day['value']);
            $this->sql_query = "SELECT day,hour,subject,room FROM timetables WHERE ((day_num > $day_week) OR (day_num = $day_week AND hour $hour_operator $hour) OR (day_num < $day_week)) AND uid = $this->id ORDER BY ((day_num - $day_week)%5), hour";           
            break;
            
         default:
            $this->status = "not_valid_query";
            break;
      }
           
      if (!empty($this->sql_query)) {
         $this->sql_rows = $this->connexion->query($this->sql_query);
      }                      
   }
   public function ConvertQuerySQLtoClient() {
      $row_vector = [];
      if ($this->status == "valid_query") {
         while ($row = $this->sql_rows->fetch_assoc()) { // Iterar por las filas y guardarlas en un vector
            $row_vector[] = $row;
         }
      }
      $response = [
         'status' => $this->status,
         'data' => $row_vector
      ];
      return json_encode($response);
   }
   public function ConvertDaytoNum($day_string){
      switch($day_string){
         case 'Mon':
            return 1;
         case 'Tue':
            return 2;
         case 'Wed':
            return 3;
         case 'Thu':
            return 4;
         default:
            return 5;   
      }
   }
   public function getOperatorAndValue($array){
      foreach ($array as $operator => $value) {
         return [
            'operator' => $operator,			// Obtienes gte, lt o el operando correspondiente 	
            'value' => $value				// Obtienes el valor asociado, por ejemplo now o el valor indicado en la query
         ];        
      }
   }
   public function convertOperator($operator){
      switch($operator){
         case 'gte':
            return '>=';
         case 'gt':
            return '>';
         case 'lte':
            return '<=';
         case 'lt':
            return '<';
         default:
            return '=';
      }     
   }
}

class LogIn {

   public $connexion;
      
   public function __construct($conn) {
      $this->connexion = $conn;
   }
   
   public function getUsername($id) {
      $id_query = "SELECT name FROM users WHERE id = $id";
      $sql_row = $this->connexion->query($id_query);
      
      if ($sql_row->num_rows == 1) {
         $user_name = $sql_row->fetch_assoc();
         $response = [
            'status' => 'id_matched',
            'data' => $user_name['name']
         ];
      } else {
         $response = [
            'status' => 'id_not_matched'
         ];
         session_unset(); // Eliminar todas las variables de sesión
         session_destroy();
      }
      return json_encode($response);
   }

}

function CheckInactivityTimer(){
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time(); // Inicializar si no está definido
    }

    $time_inactive = time() - $_SESSION['last_activity'];
    $timeout_duration = 300; // Definir el tiempo de inactividad permitido

    if ($time_inactive > $timeout_duration) {
        session_unset(); // Eliminar todas las variables de sesión
        session_destroy(); // Destruir la sesión
        die("Sesión expirada. Por favor, vuelve a iniciar sesión.");
    }

    // Si no se cumple la condición, reiniciar el temporizador
    $_SESSION['last_activity'] = time();
}

?>
