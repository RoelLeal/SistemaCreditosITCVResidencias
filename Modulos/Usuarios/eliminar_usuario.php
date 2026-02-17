<?php
require_once '../../Login/sesion.php';
require_once '../../Configuracion/conexion.php';

$rolUsuario = $_SESSION['rol'] ?? '';
if (!in_array($rolUsuario, ['Administrador', 'Docente'])) {
    header('Location: usuarios.php?msg=error_permiso');
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: usuarios.php?msg=error_id');
    exit;
}

$idEliminar = (int) $_GET['id'];
$accion = $_GET['accion'] ?? 'eliminar';
$sql = "SELECT u.id_usuario, u.nombres, u.apellido_p, u.apellido_m, r.nombre as rol, r.id_rol 
        FROM usuarios u 
        INNER JOIN roles r ON u.id_rol = r.id_rol 
        WHERE u.id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idEliminar);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    header('Location: usuarios.php?msg=error_no_encontrado');
    exit;
}

$targetUser = $res->fetch_assoc();
$targetRol = $targetUser['rol'];

$puedeEliminar = false;

if ($rolUsuario === 'Administrador') {
    $puedeEliminar = true;
} elseif ($rolUsuario === 'Docente') {
    if ($targetRol === 'Alumno') {
        $puedeEliminar = true;
    }
}

if (!$puedeEliminar) {
    header('Location: usuarios.php?msg=error_jerarquia');
    exit;
}

if ($accion === 'dar_baja') {

    $sqlRolBaja = "SELECT id_rol FROM roles WHERE nombre = 'Baja' LIMIT 1";
    $resRolBaja = $conn->query($sqlRolBaja);

    if ($resRolBaja->num_rows === 0) {
        header('Location: usuarios.php?msg=error_rol_baja_no_existe');
        exit;
    }

    $rolBaja = $resRolBaja->fetch_assoc();
    $idRolBaja = $rolBaja['id_rol'];

    $sqlUpdate = "UPDATE usuarios SET id_rol = ? WHERE id_usuario = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("ii", $idRolBaja, $idEliminar);

    try {
        $stmtUpdate->execute();
        $redirectUrl = 'usuarios.php?msg=dado_baja&filter=' . urlencode($targetRol);
        header("Location: " . $redirectUrl);
    } catch (mysqli_sql_exception $e) {
        header('Location: usuarios.php?msg=error_bd');
    }
    exit;
}

$sqlDelete = "DELETE FROM usuarios WHERE id_usuario = ?";
$stmtDelete = $conn->prepare($sqlDelete);
$stmtDelete->bind_param("i", $idEliminar);

try {
    $stmtDelete->execute();
    $redirectUrl = 'usuarios.php?msg=eliminado&filter=' . urlencode($targetRol);
    header("Location: " . $redirectUrl);
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() == 1451) {

        $_SESSION['usuario_con_relaciones'] = [
            'id' => $idEliminar,
            'nombre' => trim($targetUser['nombres'] . ' ' . $targetUser['apellido_p'] . ' ' . $targetUser['apellido_m']),
            'rol' => $targetRol
        ];
        header('Location: usuarios.php?msg=tiene_relaciones&id=' . $idEliminar);
    } else {

        header('Location: usuarios.php?msg=error_bd');
    }
}
exit;
