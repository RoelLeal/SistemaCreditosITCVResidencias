<?php
require_once '../../Login/sesion.php';
require_once '../../Configuracion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['rol'] !== 'Docente') {
    header('Location: asistencias.php');
    exit;
}

$id_docente = $_SESSION['id_usuario'];
$id_carrera = $_POST['id_carrera'] ?? 0;
$fecha = $_POST['fecha'] ?? '';
$asistencia_input = $_POST['asistencia'] ?? [];
$descripcion_input = $_POST['descripcion'] ?? [];

if (!$id_carrera || !$fecha) {
    header('Location: asistencias.php?msg=error');
    exit;
}

$conn->begin_transaction();

try {
    $stmtCheck = $conn->prepare("SELECT id_asistencia FROM asistencias WHERE id_docente = ? AND fecha = ?");
    $stmtCheck->bind_param("is", $id_docente, $fecha);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();

    if ($resCheck->num_rows > 0) {
        $id_asistencia = $resCheck->fetch_assoc()['id_asistencia'];
        $esEdicion = true;
    } else {
        $stmtInsertBase = $conn->prepare("INSERT INTO asistencias (id_docente, fecha) VALUES (?, ?)");
        $stmtInsertBase->bind_param("is", $id_docente, $fecha);
        $stmtInsertBase->execute();
        $id_asistencia = $conn->insert_id;
        $stmtInsertBase->close();
        $esEdicion = false;
    }
    $stmtCheck->close();

    $id_unidad = $_SESSION['id_unidad'];
    $sqlAlumnos = "SELECT id_usuario FROM usuarios WHERE id_carrera = ? AND id_unidad = ? AND id_rol = 3";
    $stmtAlumnos = $conn->prepare($sqlAlumnos);
    $stmtAlumnos->bind_param("ii", $id_carrera, $id_unidad);
    $stmtAlumnos->execute();
    $resAlumnos = $stmtAlumnos->get_result();

    $stmtUpsert = $conn->prepare("
        INSERT INTO asistencia_detalle (id_asistencia, id_alumno, presente, descripcion) 
        VALUES (?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE presente = VALUES(presente), descripcion = VALUES(descripcion)
    ");

    while ($row = $resAlumnos->fetch_assoc()) {
        $id_alumno = $row['id_usuario'];

        $presente = (isset($asistencia_input[$id_alumno]) && $asistencia_input[$id_alumno] == 1) ? 1 : 0;

        $descripcion = $descripcion_input[$id_alumno] ?? null;

        $stmtUpsert->bind_param("iiis", $id_asistencia, $id_alumno, $presente, $descripcion);
        $stmtUpsert->execute();
    }

    $stmtAlumnos->close();
    $stmtUpsert->close();

    $conn->commit();

    if ($esEdicion) {
        header("Location: ver_asistencia.php?id_carrera=$id_carrera&fecha=$fecha&msg=guardado");
    } else {
        header('Location: asistencias.php?msg=guardado');
    }

} catch (Exception $e) {
    $conn->rollback();
    error_log($e->getMessage());
    header('Location: asistencias.php?msg=error');
}
?>