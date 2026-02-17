<?php
require_once '../../Login/sesion.php';
require_once '../../Configuracion/conexion.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador') {
    header('Location: ../../index.php');
    exit;
}

$tabla = $_GET['tabla'] ?? '';
$id = (int) ($_GET['id'] ?? 0);

$allowedTables = ['actividades', 'carreras', 'tipo_creditos', 'periodo_anio', 'unidades'];

if (!in_array($tabla, $allowedTables) || $id <= 0) {
    header('Location: datos.php?msg=error_params');
    exit;
}

$pkMap = [
    'actividades' => 'id_actividad',
    'carreras' => 'id_carrera',
    'tipo_creditos' => 'id_tipo_credito',
    'periodo_anio' => 'id_anio',
    'unidades' => 'id_unidad'
];

$pkName = $pkMap[$tabla];

$sql = "DELETE FROM $tabla WHERE $pkName = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header('Location: datos.php?tabla=' . $tabla . '&msg=eliminado');
} else {
    if ($conn->errno === 1451) {
        $msg = 'error_dependencia';
    } else {
        $msg = 'error_bd';
    }
    header('Location: datos.php?tabla=' . $tabla . '&msg=' . $msg);
}
exit;
