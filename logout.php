<?php
session_start(); // Iniciamos la sesión

// Eliminamos todas las variables de sesión
session_unset();

// Destruimos la sesión actual
session_destroy();

// Redirigimos al usuario al inicio (index.php)
header("Location: index.php");
exit();
?>
 