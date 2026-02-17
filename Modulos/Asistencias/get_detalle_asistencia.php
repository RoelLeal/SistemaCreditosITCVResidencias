<?php
require_once '../../Login/sesion.php';
require_once '../../Configuracion/conexion.php';

header('Content-Type: application/json');

if ($_SESSION['rol'] !== 'Docente') {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$id_docente = $_SESSION['id_usuario'];
$id_carrera = $_GET['id_carrera'] ?? 0;
$fecha = $_GET['fecha'] ?? '';

if (!$id_carrera || !$fecha) {
    echo json_encode(['error' => 'Parámetros insuficientes']);
    exit;
}

$sql = "
    SELECT 
        u.numero_control,
        CONCAT(u.nombres, ' ', u.apellido_p, ' ', u.apellido_m) AS nombre_completo,
        ad.presente
    FROM asistencia_detalle ad
    INNER JOIN asistencias a ON ad.id_asistencia = a.id_asistencia
    INNER JOIN usuarios u ON ad.id_alumno = u.id_usuario
    WHERE a.id_docente = ? 
      AND a.fecha = ? 
      AND u.id_carrera = ?
    ORDER BY u.nombres ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("isi", $id_docente, $fecha, $id_carrera);
$stmt->execute();
$resultado = $stmt->get_result();

$detalle = [];
while ($row = $resultado->fetch_assoc()) {
    $detalle[] = $row;
}

echo json_encode($detalle);
?>
