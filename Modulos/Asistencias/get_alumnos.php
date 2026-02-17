<?php
require_once '../../Login/sesion.php';
require_once '../../Configuracion/conexion.php';

header('Content-Type: application/json');

if ($_SESSION['rol'] !== 'Docente') {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$id_carrera = $_GET['id_carrera'] ?? 0;
$fecha = $_GET['fecha'] ?? date('Y-m-d');
$id_docente = $_SESSION['id_usuario'];
$id_unidad = $_SESSION['id_unidad'];

$sql = "
    SELECT 
        u.id_usuario,
        u.numero_control,
        CONCAT(u.nombres, ' ', u.apellido_p, ' ', u.apellido_m) AS nombre_completo,
        IFNULL(ad.presente, 0) AS presente,
        IFNULL(ad.descripcion, '') AS descripcion
    FROM usuarios u
    LEFT JOIN asistencias a ON a.fecha = ? AND a.id_docente = ?
    LEFT JOIN asistencia_detalle ad ON ad.id_asistencia = a.id_asistencia AND ad.id_alumno = u.id_usuario
    WHERE u.id_rol = 3 -- Alumno
      AND u.id_carrera = ?
      AND u.id_unidad = ?
    ORDER BY u.nombres ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("siii", $fecha, $id_docente, $id_carrera, $id_unidad);
$stmt->execute();
$resultado = $stmt->get_result();

$alumnos = [];
while ($row = $resultado->fetch_assoc()) {
    $alumnos[] = $row;
}

echo json_encode($alumnos);
?>
