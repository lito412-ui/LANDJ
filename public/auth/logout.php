<?php
//Iniciamos la sesión para poder acceder a ella
session_start();

//Vaciamos todas las variables de sesión
$_SESSION = array();

//Destruimos la sesión en el servidor
session_destroy();

//Borrar la cookie de sesión del navegador para mayor seguridad
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

//Redirigimos al usuario al login (index.html)
header("Location: /modules/site/login.html");
exit;
?>