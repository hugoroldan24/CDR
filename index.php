<?php   
   session_start();
   if(isset($_GET('id'))){  				//Esto nos verifica que la peticion http de la RP contiene un parámetro 'ip'
      include("connect_DB.php");			//Iniciamos una conexion con la BD
      $id = $_GET['id'];				//Obtenim la id enviada pel client per http GET
      $select_id = "SELECT * FROM $users_table where id = $id";
      
      //La variable $result es un objeto que contiene en este caso las filas donde la id coincide, sobre esta variable podemos iterrar.
      $result = $connexion->query($select_id); 		//Busca la fila on el paràmetre id coincidex amb el de $id

	 
      if($result->num_rows == 1)			//Si ha coincidit el valor de la ID (usuari existeix a la BD) 
      	 $_SESSION['user_id'] = $id;			//Guardem la ID del usuari identificat a una super variable
	 $user_info = $result->fetch_assoc();		//Obtenim la fila on es troba la parella (name,id);
	 $response = [					//$response es un array associatiu on cada element es guarda com un key-pair
	   'status' => 'id_matched',
	   'data' => $user_info['name']			//Posem el nom associat a la ID trobada
	 ];
      else //No existeix la ID, donará error
	 $response = [
	   'status' => 'id_not_matched'
	 ];
	 
      echo json_encode($response);			//Enviem al Client la resposta convertida en format JSON
      include("disconnect_DB.php");			//Tanquem la connexió amb la BD	
   }
   else{} //Sempre enviarem un paràmetre id	

?>
