<?php
require_once '../../Login/sesion.php';
require_once '../../Configuracion/conexion.php';

$rolUsuario = $_SESSION['rol'] ?? '';
if (!in_array($rolUsuario, ['Administrador', 'Docente'])) {
    header('Location: usuarios.php?msg=error_permiso');
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id']) || !isset($_GET['rol']) || empty($_GET['rol'])) {
    header('Location: usuarios.php?msg=error_parametros');
    exit;
}

$idUsuario = (int) $_GET['id'];
$nuevoRol = $_GET['rol'];

if ($rolUsuario === 'Docente' && $nuevoRol !== 'Alumno') {
    header('Location: usuarios.php?msg=error_permiso');
    exit;
}

if (!in_array($nuevoRol, ['Docente', 'Alumno'])) {
    header('Location: usuarios.php?msg=error_rol_invalido');
    exit;
}

$sqlVerificar = "SELECT u.id_usuario, r.nombre as rol 
                 FROM usuarios u 
                 INNER JOIN roles r ON u.id_rol = r.id_rol 
                 WHERE u.id_usuario = ?";
$stmtVerificar = $conn->prepare($sqlVerificar);
$stmtVerificar->bind_param("i", $idUsuario);
$stmtVerificar->execute();
$resVerificar = $stmtVerificar->get_result();

if ($resVerificar->num_rows === 0) {
    header('Location: usuarios.php?msg=error_no_encontrado');
    exit;
}

$usuario = $resVerificar->fetch_assoc();

if ($usuario['rol'] !== 'Baja') {
    header('Location: usuarios.php?msg=error_no_esta_de_baja');
    exit;
}

$sqlRol = "SELECT id_rol FROM roles WHERE nombre = ? LIMIT 1";
$stmtRol = $conn->prepare($sqlRol);
$stmtRol->bind_param("s", $nuevoRol);
$stmtRol->execute();
$resRol = $stmtRol->get_result();

if ($resRol->num_rows === 0) {
    header('Location: usuarios.php?msg=error_rol_no_existe');
    exit;
}

$rolData = $resRol->fetch_assoc();
$idNuevoRol = $rolData['id_rol'];

$sqlUpdate = "UPDATE usuarios SET id_rol = ? WHERE id_usuario = ?";
$stmtUpdate = $conn->prepare($sqlUpdate);
$stmtUpdate->bind_param("ii", $idNuevoRol, $idUsuario);

try {
    $stmtUpdate->execute();
    header('Location: usuarios.php?msg=reactivado&filter=' . urlencode($nuevoRol));
} catch (mysqli_sql_exception $e) {
    header('Location: usuarios.php?msg=error_bd');
}
exit;
