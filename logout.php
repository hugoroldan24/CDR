<?php
session_start();   //Para eliminar la sesion, hay que tener esta iniciada

session_unset();   //Vacía el array de session, borras todas las variables almacenadas

if (ini_get("session.use_cookies")) {
    setcookie(
        session_name(),    // normalmente "PHPSESSID"
        '',                // Ponemos el valor de la cookie a vacío ''
      time() - 1,          //Ponemos que la fecha de expiracion fue hace 1 segundo, por tanto se descarta la cookie
      //Le decimos las caracteristicas de la cookie a eliminar. Como hacemos ini_get, haremos que las caracteristicas de la cookie a eliminar coincidan con las de la cookie que estabamos usando.
      ini_get("session.cookie_path"),
      ini_get("session.cookie_domain"),
      ini_get("session.cookie_secure"),
      ini_get("session.cookie_httponly")
    );
}
header('Content-Type: application/json');
die(json_encode(['status' => 'expired', 'message' => 'Sesión expirada']));
?>
