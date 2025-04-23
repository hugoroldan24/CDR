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
  
    public function __construct($conn, $uri, $id) {
        $this->status = "valid_query";
        $this->connexion = $conn;
        $this->uri = $uri;
        $this->id = $id;
    }
    
    public function parseQuery() {
        parse_str(parse_url($this->uri, PHP_URL_QUERY), $this->query_array);
    }
    
    public function obtainTable() {
        $this->table = str_replace('/querys.php/', '', parse_url($this->uri, PHP_URL_PATH));
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
            default: return '=';
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
