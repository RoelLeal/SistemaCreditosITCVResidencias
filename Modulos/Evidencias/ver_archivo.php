<?php
require_once '../../Login/sesion.php';
require_once '../../Configuracion/conexion.php';

$rol = $_SESSION['rol'] ?? '';
$idUsuario = $_SESSION['id_usuario'] ?? 0;
$idUnidad = $_SESSION['id_unidad'] ?? 0;

// Validar que el usuario esté logueado
if (empty($idUsuario)) {
    http_response_code(403);
    die("Acceso denegado. Inicie sesión.");
}

$idEvidencia = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($idEvidencia <= 0) {
    http_response_code(400);
    die("ID de evidencia inválido.");
}

// Obtener información del archivo y del alumno propietario
$sql = "
    SELECT 
        e.archivo_pdf,
        e.id_alumno,
        u.id_unidad AS id_unidad_alumno
    FROM evidencias_creditos e
    INNER JOIN usuarios u ON e.id_alumno = u.id_usuario
    WHERE e.id_evidencia = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idEvidencia);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $archivoPdf = $row['archivo_pdf'];
    $idAlumno = $row['id_alumno'];
    $idUnidadAlumno = $row['id_unidad_alumno'];

    $accesoPermitido = false;

    // Verificar permisos según el rol
    if ($rol === 'Administrador') {
        $accesoPermitido = true;
    } elseif ($rol === 'Docente') {
        // Docente solo puede ver archivos de alumnos de su misma unidad
        if ($idUnidad == $idUnidadAlumno) {
            $accesoPermitido = true;
        }
    } elseif ($rol === 'Alumno') {
        // Alumno solo puede ver sus propios archivos
        if ($idUsuario == $idAlumno) {
            $accesoPermitido = true;
        }
    }

    if ($accesoPermitido) {
        $rutaArchivo = "../../Archivos/" . $archivoPdf;

        // Verificar travesía de directorios (Directory Traversal)
        $rutaReal = realpath($rutaArchivo);
        $rutaBase = realpath("../../Archivos/");

        if ($rutaReal && strpos($rutaReal, $rutaBase) === 0 && file_exists($rutaReal)) {
            // Servir el archivo
            $disposition = isset($_GET['download']) ? 'attachment' : 'inline';
            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf'); // Asumiendo que son PDFs como dice el código anterior
            header('Content-Disposition: ' . $disposition . '; filename="' . basename($rutaReal) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($rutaReal));
            readfile($rutaReal);
            exit;
        } else {
            http_response_code(404);
            die("El archivo no existe en el servidor.");
        }
    } else {
        http_response_code(403);
        die("No tienes permisos para ver este archivo.");
    }
} else {
    http_response_code(404);
    die("Archivo no encontrado.");
}
?>