<?php
$servidor = "127.0.0.1";
$puerto = 3310;
$usuario = "root";
$contrasena = "";
$baseDatos = "siac";

require_once __DIR__ . '/config.php';


$conn = mysqli_connect($servidor, $usuario, $contrasena, $baseDatos, $puerto);

if (!$conn) {
    die("Error al conectar: " . mysqli_connect_error());
}

$conn->set_charset("utf8");
?>