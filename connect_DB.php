<?php
	
	$host = 'localhost'; //BD y Servidor están en la misma máquina 
	$dbname = 'nombre_base_datos';
	$username = 'usuari';
	$password = 'contrasenya';
	//Aqui tambien me falta definir las variables de contraseña y usuario de la BD , que se pondran al crearla
	
	$users_table = 'usuaris';  	    //usuarios será el nombre de la tabla de la BD que tendrá las parejas (Nombre, ID)
	$timetables = 'horaris';	    //Nombre de la tabla en la BD correspondiente a los horarios
	$marks = 'calificacions';	    //Nombre de la tabla en la BD correspondiente a las calificaciones
	$taks = 'tasques';		    //Nombre de la tabla en la BD correspondiente a las tareas
	
	$connexion = new mysqli($host,$username,$password,$dbname);  //Creamos conexion a la BD

	if($connexion->connect_errno){
	   echo "Error trying to connect to the DB";
	   exit();
	}
	
	
?>
	
	

