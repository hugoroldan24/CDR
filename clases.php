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
  
    public function __construct($conn, $uri, $id) {
        $this->status = "valid_query";
        $this->connexion = $conn;
        $this->uri = $uri;
        $this->id = $id;
        
        $this->operandos = [];
        $this->valores = [];
        $this->params = [];
    }
    //Función que te separa la query en todas sus constraints y extrae de ella todos los elementos para realizar una consulta SQL
    
    public function ParseQuery(){
       $i = 0;
       $this->table = trim(str_replace('/querys.php/', '', parse_url($this->uri, PHP_URL_PATH))); //Obtenir la taula       
       $total_constraints = explode("&",parse_url($this->uri, PHP_URL_QUERY));  //Separa la query entre les constraints
       $this->num_constraints = count($total_constraints);                      //Guardem el número de constraints
       foreach($total_constraints as $constraint){
           $exploded_query = explode("=",$constraint); //Ejemplo date[gte] , now
           $exploded_data_operand = explode("[",$exploded_query[0]);    //date ,gte]

           $this->operandos[$i] = convertOperator(rtrim($exploded_data_operand[1],"]"));     //Obtenim l'operand 
           $this->params[$i] = $exploded_data_operand[0];                                    //Obtenim el paràmetre (date,hour...)
           $this->valores[$i] = modifyValue($this->params[i],$exploded_query[0]);            //Passem per paràmetre el paràmetre y el valor (el que ve despres del =) que es troba a $exploded_query[0]
           $i++;
       }
    }
    //La ideia será construir de forma dinàmica la petició SQL, anirem afegint les strings fins aconseguir la petició completa.
    public function ConvertQuerytoSQL() {
        $add_limit = false;
        $query_sql = "SELECT * FROM {$this->table} WHERE ";
        //Las siguientes 3 lineas de código son para no poner nombres de variables tan largos todo el rato
        $op = $this->operandos;
        $params = $this->params;
        $val = $this->valores;
    
        for($i=0;$i<$this->num_constraints;$i++){    //El numero de constraints coincidirà amb la quantitat de elements als vectores operandos,param,valores
            if($i > 0){
                $query_sql .=" AND ";
            }
            if($params[$i] === "limit"){
                $add_limit = true;
                $num_limit = $val[i];
            }
            $where = "({$params[$i]} {$op[$i]} {$val[$i]})";    
            $query_sql .=$where
        }
        $query_sql .=" AND (uid = {$this->id})";  //Esto se pone en todos los casos para consultar las tablas propias del usuario.    

        if($add_limit){
            $query_sql .= " {$num_limit}; 
        }




        
        switch ($this->table) {
            case 'tasks':
                if (!isset($this->query_array['date'])) {
                    $this->status = "invalid_parameters";
                    return;
                }
                
                $query_data = $this->getOperatorAndValue($this->query_array['date']);
                $operator = $this->convertOperator($query_data['operator']);
                     
                $date = ($query_data['value'] === 'now') ? date('Y-m-d') : $this->connexion->real_escape_string($query_data['value']);
                
                $stmt = $this->connexion->prepare("SELECT day, subject, name FROM tasks WHERE (date $operator ?) AND (uid = ?) ORDER BY date");
                $stmt->bind_param("si", $date, $this->id);
                $stmt->execute();
                $this->sql_rows = $stmt->get_result();
                break;
                
            case 'marks':
                $stmt = $this->connexion->prepare("SELECT subject, name, mark FROM marks WHERE id = ? ORDER BY subject");
                $stmt->bind_param("i", $this->id);
                $stmt->execute();
                $this->sql_rows = $stmt->get_result();
                break;
                
            case 'timetables':
                if (!isset($this->query_array['day']) || !isset($this->query_array['hour'])) {
                    $this->status = "invalid_parameters";
                    return;
                }
                
                $query_day = $this->getOperatorAndValue($this->query_array['day']);
                $query_hour = $this->getOperatorAndValue($this->query_array['hour']);
                
                $day_operator = $this->convertOperator($query_day['operator']);
                $hour_operator = $this->convertOperator($query_hour['operator']);
                
                $hour = ($query_hour['value'] === 'now') ? date('H') : $this->connexion->real_escape_string($query_hour['value']);
                $day_week = ($query_day['value'] === 'now') ? date('N') : $this->ConvertDaytoNum($query_day['value']);
                
                $sql = "SELECT day, hour, subject, room FROM timetables WHERE ((day_num > ?) OR (day_num = ? AND hour $hour_operator ?) OR (day_num < ?)) AND uid = ? ORDER BY ((day_num - ?)%5), hour";
                $stmt = $this->connexion->prepare($sql);
                $stmt->bind_param("iiiii", $day_week, $day_week, $hour, $day_week, $this->id, $day_week);
                $stmt->execute();
                $this->sql_rows = $stmt->get_result();
                break;
                
            default:
                $this->status = "not_valid_query";
                break;
        }
        
        if ($this->sql_rows === false) {
            $this->status = "query_error";
            $this->error = $this->connexion->error;
        }
    }
    
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
        if ($this->status == "query_error") {
            $response['error'] = $this->error;
        }
        return json_encode($response);
    }
    
    public function ConvertDaytoNum($day_string) {
        switch($day_string) {
            case 'Mon': return 1;
            case 'Tue': return 2;
            case 'Wed': return 3;
            case 'Thu': return 4;
            default: return 5;   
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
            default return '='; //Per exemple si la constraint es limit = 1 , entrariem en aquesta opció, per tant al vector de operands es guardaria com '=' el qual és util alhora de construir la petició SQL
            
        }     
    }
    //Aquesta funció es per convertir la paraula reservada 'now' en la forma de temps actual especifiada al paràmetre. Si el valor no es now, es retorna el mateix valor que hi havia,
    //però si el paràmetre es day, el converteix a int de totes maneres.
    public function modifyValue($param,$value){
        if($value == 'now'){
            switch($param){
                case 'date': return date('Y-m-d');
                case 'hour':  return date('H');
                case 'day': return date('N');
                case 'month': return date('m');
                case 'year': return date('Y');
                default:  die(json_encode(['status' => 'error', 'message' => 'Invalid query format']));
            }              
        }
        else{
            if($param == 'day'){
                return  ConvertDaytoNum($value);
            }
            return $value;
        }
    }
}

class LogIn {
    public $connexion;
      
    public function __construct($conn) {
        $this->connexion = $conn;
    }
    
    public function getUsername($id) {
        $stmt = $this->connexion->prepare("SELECT name FROM students WHERE uid = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user_name = $result->fetch_assoc();
            $response = [
                'status' => 'id_matched',
                'data' => $user_name['name']
            ];
        } else {
            $response = [
                'status' => 'id_not_matched'
            ];
            session_unset();
            session_destroy();
        }
        return json_encode($response);
    }
}

function CheckInactivityTimer() {
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
    }

    $time_inactive = time() - $_SESSION['last_activity'];

    if ($time_inactive > TIMEOUT_DURATION) {
        session_unset();
        session_destroy();
        http_response_code(401);
        die(json_encode(['status' => 'error', 'message' => 'Sesión expirada. Por favor, vuelve a iniciar sesión.']));
    }

    $_SESSION['last_activity'] = time();
}
?>



__________________________________________________________________________
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
  
    public function __construct($conn, $uri, $id) {
        $this->status = "valid_query";
        $this->connexion = $conn;
        $this->uri = $uri;
        $this->id = $id;
        
        $this->operandos = [];
        $this->valores = [];
        $this->params = [];
    }
    //Función que te separa la query en todas sus constraints y extrae de ella todos los elementos para realizar una consulta SQL
    
    public function parse_query(){
       $i = 0;
       $this->table = trim(str_replace('/querys.php/', '', parse_url($this->uri, PHP_URL_PATH))); //Obtenir la taula       
       $total_constraints = explode("&",parse_url($this->uri, PHP_URL_QUERY));  //Separa la query entre les constraints
       
       foreach($total_constraints as $constraint){
           $exploded_query = explode("=",$constraint); //Ejemplo date[gte] , now
           $exploded_data_operand = explode("[",$exploded_query[0]);    //date ,gte]

           $this->operandos[$i] = convertOperator(rtrim($exploded_data_operand[1],"]"));    //Obtenim l'operand 
           $this->params[$i] = $exploded_data_operand[0];                                   //Obtenim el paràmetre (date,hour...)
           $this->valores[$i] = $exploded_query[0];                                         //Obtenim el valor (now,8:00)
           $i++;
       }
    }
    
    public function ConvertQuerytoSQL() {
        switch ($this->table) {
            case 'tasks':
                if (!isset($this->query_array['date'])) {
                    $this->status = "invalid_parameters";
                    return;
                }
                
                $query_data = $this->getOperatorAndValue($this->query_array['date']);
                $operator = $this->convertOperator($query_data['operator']);
                     
                $date = ($query_data['value'] === 'now') ? date('Y-m-d') : $this->connexion->real_escape_string($query_data['value']);
                
                $stmt = $this->connexion->prepare("SELECT day, subject, name FROM tasks WHERE (date $operator ?) AND (uid = ?) ORDER BY date");
                $stmt->bind_param("si", $date, $this->id);
                $stmt->execute();
                $this->sql_rows = $stmt->get_result();
                break;
                
            case 'marks':
                $stmt = $this->connexion->prepare("SELECT subject, name, mark FROM marks WHERE id = ? ORDER BY subject");
                $stmt->bind_param("i", $this->id);
                $stmt->execute();
                $this->sql_rows = $stmt->get_result();
                break;
                
            case 'timetables':
                if (!isset($this->query_array['day']) || !isset($this->query_array['hour'])) {
                    $this->status = "invalid_parameters";
                    return;
                }
                
                $query_day = $this->getOperatorAndValue($this->query_array['day']);
                $query_hour = $this->getOperatorAndValue($this->query_array['hour']);
                
                $day_operator = $this->convertOperator($query_day['operator']);
                $hour_operator = $this->convertOperator($query_hour['operator']);
                
                $hour = ($query_hour['value'] === 'now') ? date('H') : $this->connexion->real_escape_string($query_hour['value']);
                $day_week = ($query_day['value'] === 'now') ? date('N') : $this->ConvertDaytoNum($query_day['value']);
                
                $sql = "SELECT day, hour, subject, room FROM timetables WHERE ((day_num > ?) OR (day_num = ? AND hour $hour_operator ?) OR (day_num < ?)) AND uid = ? ORDER BY ((day_num - ?)%5), hour";
                $stmt = $this->connexion->prepare($sql);
                $stmt->bind_param("iiiii", $day_week, $day_week, $hour, $day_week, $this->id, $day_week);
                $stmt->execute();
                $this->sql_rows = $stmt->get_result();
                break;
                
            default:
                $this->status = "not_valid_query";
                break;
        }
        
        if ($this->sql_rows === false) {
            $this->status = "query_error";
            $this->error = $this->connexion->error;
        }
    }
    
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
        if ($this->status == "query_error") {
            $response['error'] = $this->error;
        }
        return json_encode($response);
    }
    
    public function ConvertDaytoNum($day_string) {
        switch($day_string) {
            case 'Mon': return 1;
            case 'Tue': return 2;
            case 'Wed': return 3;
            case 'Thu': return 4;
            default: return 5;   
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
            case '=' return '=';
            default: return ''; //Per exemple si la constraint es limit = 1 , entrariem en aquesta opció, per tant al vector de operands es guardaria com ''.
        }     
    }
}

class LogIn {
    public $connexion;
      
    public function __construct($conn) {
        $this->connexion = $conn;
    }
    
    public function getUsername($id) {
        $stmt = $this->connexion->prepare("SELECT name FROM students WHERE uid = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user_name = $result->fetch_assoc();
            $response = [
                'status' => 'id_matched',
                'data' => $user_name['name']
            ];
        } else {
            $response = [
                'status' => 'id_not_matched'
            ];
            session_unset();
            session_destroy();
        }
        return json_encode($response);
    }
}

function CheckInactivityTimer() {
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
    }

    $time_inactive = time() - $_SESSION['last_activity'];

    if ($time_inactive > TIMEOUT_DURATION) {
        session_unset();
        session_destroy();
        http_response_code(401);
        die(json_encode(['status' => 'error', 'message' => 'Sesión expirada. Por favor, vuelve a iniciar sesión.']));
    }

    $_SESSION['last_activity'] = time();
}
?>

