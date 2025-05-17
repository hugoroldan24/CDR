<?php
define('TIMEOUT_DURATION', 300); // Definir el tiempo de inactividad permitido
class queryManager {
    public $connexion; 
    public $uri;
    public $id;
    public $sql_rows;
    public $status;
    public $table;
    public $query_array;
    public $error;
    public $sql_query;
    
    public $operandos;
    public $valores;
    public $params;
    public $num_constraints;
  
    public function __construct($conn, $uri,$id) {
        $this->status = "valid_query";
        $this->connexion = $conn;
        $this->uri = $uri;
        $this->id = $id;
        
        
        $this->operandos = [];
        $this->valores = [];
        $this->params = [];
    }
    // Funció que separa la query en totes les seves restriccions i en extreu els elements necessaris per fer una consulta SQL. Aquests elements se li aplicaran les funcions corresponents per deixarles en format
    // per introduir directament a una consulta SQL.
    
    public function ParseQuery(){ //Al sacar la URI por ejemplo, si tu URL és https://192.168.1.1:8000/servidor.php/table?date[gte]=now, la URI será /querys.php/table?date[gte]=now
      $this->table = trim(str_replace('/Servidor/querys.php/', '', parse_url($this->uri, PHP_URL_PATH))); //Obtenir la taula el PATH será x ejemplo /querys.php/table
      if(parse_url($this->uri, PHP_URL_QUERY) === ''){ //EN EL CLIENTE RPi HAY QUE IGUALAR LA CONDICIÓN A NULL Y NO A '' 
         $this->num_constraints = 0;   //Si devuelve null querrá decir que no hay querys, por tanto num_constraints = 0 (de esta forma no entraremos en el for del proccessQuery)   
      }
      else{
        $total_constraints = explode("&",parse_url($this->uri, PHP_URL_QUERY));  //Separa la query entre les constraints.
        $this->num_constraints = count($total_constraints);                      //Guardem el número de constraints
        foreach($total_constraints as $constraint){
           $exploded_query = explode("=",$constraint); //Ejemplo date[gte] , now
           $exploded_data_operand = explode("[",$exploded_query[0]);    //date ,gte]

           $this->operandos[] = $this->convertOperator(rtrim($exploded_data_operand[1],"]"));     //Obtenim l'operand 
           $this->params[] = $exploded_data_operand[0];                                    //Obtenim el paràmetre (date,hour...)
           $this->valores[] = $this->modifyValue($exploded_data_operand[0],$exploded_query[1]);            //Passem per paràmetre el paràmetre y el valor (el que ve despres del =) que es troba a $exploded_query[0]           
       }
     }
    }
    //La ideia será construir de forma dinàmica la petició SQL, anirem afegint les strings fins aconseguir la petició completa.
    public function ConvertQuerytoSQL() {
        $add_limit = false;
        $is_timetable = false;
        $op = $this->operandos;
        $params = $this->params;
        $val = $this->valores;
        
        $query_sql = "SELECT * FROM {$this->table} WHERE (uid = '{$this->id}') "; //Ojo , estoy poniendo * en el SELECT, por tanto me devolverá las filas con el uid, esto habrá que gestionarlo en el cliente

        for($i=0;$i<$this->num_constraints;$i++){    //El numero de constraints coincidirà amb la quantitat de elements als vectores operandos,param,valores
            if($params[$i] === "limit"){ //Si la constraint és un limit, activem un flag per tal de que al final de la SQL query introduim el LIMIT 
                $add_limit = true;
                $num_limit = $val[$i]; //Ens guardem el valor del limit 
            }
            elseif($this->table != 'timetables'){ //Si no es timetable, SI afegirem constraints
                $query_sql .= " AND ({$params[$i]} {$op[$i]} '{$val[$i]}')"; //Alomejor aqui hay que ponerlo asi '{variable}' , con los ' '
            }                                              
        } //Los ORDER BY es lo único que aun no se como hacerlo sin particularizar por tablas...
        if($this->table === 'timetables'){ //Aquí asumimos que el orden de las constraints en la query de timetables será primero dia y después hora  ex: timetables?day=Fri&hour=now
           $query_sql .=" ORDER BY 
           		    CASE 
           		    	WHEN ((day_int - {$val[0]} + 7)%7) = 0 
           		    		AND hour {$op[1]} CAST('{$val[1]}' AS TIME)
           		    	THEN 5 
           		    	ELSE ((day_int - {$val[0]} + 7)%7)
           		    END, 
           		    hour ";
        }
        elseif($this->table === 'marks'){
           $query_sql .=" ORDER BY mark ";
        }
        elseif($this->table === 'tasks'){
           $query_sql .=" ORDER BY date ";
        }
        else{
           header('Content-Type: application/json'); 
           die(json_encode(['status' => 'error', 'message' => 'Invalid table name']));
        } 

        if($add_limit){ //Al final de todo añadimos el LIMIT si se ha especificado en la constraint
            $query_sql .= " LIMIT '{$num_limit}'";
        }
       
        $this->sql_rows = $this->connexion->query($query_sql); //Hacemos la petición a la base de datos
        
        if ($this->sql_rows === false) {
            $this->status = "query_error";
            $this->error = $this->connexion->error;
        }    
    }
        //Para ordenar ciclicamente la timetables: ORDER BY @ciclo := (day_num - {$day_param} + 5)%5, CASE WHEN (@ciclo = 0) AND hour < {$hour_param} THEN 5 ELSE @ciclo, hour    
        //Básicamente lo que hace esa linea es, si el dia es el actual, pero la hora de la clase ya ha pasado, se le asigna un 5, por tanto se colocará el último. Luego ordenamos por hora normal 
    
    public function ConvertQuerySQLtoClient() {
        $row_vector = [];
        if ($this->status == "valid_query") {
            while ($row = $this->sql_rows->fetch_assoc()) {
                $row_vector[] = $row;
            }
        }
        $response = [
            'status' => $this->status,
            'data' => $row_vector
        ];
        if ($this->status == "query_error") { // ?
            $response['error'] = $this->error; 
        }
        header('Content-Type: application/json; charset=utf-8');
        return json_encode($response);
    }
    
    public function ConvertDaytoNum($day_string) {
        switch($day_string) {
            case 'Mon': return 1;
            case 'Tue': return 2;
            case 'Wed': return 3;
            case 'Thu': return 4;
            case 'Fri': return 5;   
            default:  header('Content-Type: application/json'); 
                      die(json_encode(['status' => 'error', 'message' => 'Invalid query format']));
        }
    }
    
    public function getOperatorAndValue($array) {
        foreach ($array as $operator => $value) {
            return [
                'operator' => $operator,
                'value' => $value
            ];        
        }
    }
    
    public function convertOperator($operator) {
        switch($operator) {
            case 'gte': return '>=';
            case 'gt': return '>';
            case 'lte': return '<=';
            case 'lt': return '<';
            default: return '=';         
        }  
    }
    
    //Aquesta funció es per convertir la paraula reservada 'now' en la forma de temps actual especifiada al paràmetre. Si el valor no es now, es retorna el mateix valor que hi havia,
    //però si el paràmetre es day, el converteix a int de totes maneres.
    public function modifyValue($param,$value){
        if($value === 'now'){    //Si el parámetro es now, seguro que el valor estará relacionado con alguna medida de tiempo
            switch($param){
                case 'date': return date('Y-m-d');
                case 'hour':  return date('H:i');
                case 'day': return date('N');
                case 'month': return date('m');
                case 'year': return date('Y');
                default: header('Content-Type: application/json');  die(json_encode(['status' => 'error', 'message' => 'Invalid query format']));
            }              
        }
        else{
            if($param === 'day'){
                return  ConvertDaytoNum($value);
            }
            return $value;
        }
    }

}
//CLASE LOGIN PARA EL CLIENTE WEB
class LogIn {
    public $connexion;
    public $id;
    public $password;
    public $username;
    
    public function __construct($conn,$password,$username) {
        $this->connexion = $conn;
        $this->password = $password;
        $this->username = $username;
        
    }
    
    public function getUsername() {
        
        $query = "SELECT name,uid FROM students WHERE password = '{$this->password}' AND username = '{$this->username}'";
        
        
        $result = $this->connexion->query($query);
        
        if ($result->num_rows == 1) {
            $result = $result->fetch_assoc();
            $response = [
                'status' => 'id_matched',
                'data' => $result
            ];
           
        } else {
            session_unset();
            session_destroy();
            $response = [
                'status' => 'id_not_matched'
            ];                                
        }
        header('Content-Type: application/json; charset=utf-8');
        return $response;
    }
}

//CLASE LOGIN PARA EL CLIENTE RPi
class LogIn {
    public $connexion;
    public $id;
    
    public function __construct($conn,$id) {
        $this->connexion = $conn;
        $this->id = $id;
    }
    
    public function getUsername() {
        
        $query = "SELECT name FROM students WHERE uid = '{$this->id}'";
        $result = $this->connexion->query($query);
        
        if ($result->num_rows == 1) {
            $user_name = $result->fetch_assoc();
            $response = [
                'status' => 'id_matched',
                'data' => $user_name['name']
            ];
           
        } else {
            session_unset();
            session_destroy();
            $response = [
                'status' => 'id_not_matched'
            ];                                
        }
        header('Content-Type: application/json; charset=utf-8');
        return json_encode($response);
    }
}

function CheckInactivityTimer() {
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
    }

    $time_inactive = time() - $_SESSION['last_activity'];

    if ($time_inactive > TIMEOUT_DURATION) {
        include("logout.php");
    }
    $_SESSION['last_activity'] = time();
}
?>
