<?php
require_once '../../Login/sesion.php';
require_once '../../Configuracion/conexion.php';

$rol = $_SESSION['rol'] ?? '';
$idUsuario = $_SESSION['id_usuario'] ?? 0;
$idUnidad = $_SESSION['id_unidad'] ?? 0;

$stmtUser = $conn->prepare("SELECT nombres, apellido_p, apellido_m FROM usuarios WHERE id_usuario = ?");
$stmtUser->bind_param("i", $idUsuario);
$stmtUser->execute();
$userData = $stmtUser->get_result()->fetch_assoc();
$nombreCompleto = trim(($userData['nombres'] ?? '') . ' ' . ($userData['apellido_p'] ?? '') . ' ' . ($userData['apellido_m'] ?? ''));
$stmtUser->close();

$error = '';
$success = '';

if (isset($_SESSION['flash_message'])) {
    $flash = $_SESSION['flash_message'];
    if ($flash['type'] === 'error') {
        $error = $flash['message'];
    } elseif ($flash['type'] === 'success') {
        $success = $flash['message'];
    }
    unset($_SESSION['flash_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $rol === 'Docente') {
    $action = $_POST['action'] ?? '';
    $msgType = 'error';
    $msgText = '';

    if ($action === 'upload') {
        $id_alumno = $_POST['id_alumno'] ?? '';
        $id_actividad = $_POST['id_actividad'] ?? '';
        $id_anio = $_POST['id_anio'] ?? '';

        if (empty($id_alumno) || empty($id_actividad) || empty($id_anio) || empty($_FILES['archivo']['name'])) {
            $msgText = "Todos los campos son obligatorios.";
        } else {
            $stmtTipo = $conn->prepare("SELECT id_tipo_credito FROM actividades WHERE id_actividad = ?");
            $stmtTipo->bind_param("i", $id_actividad);
            $stmtTipo->execute();
            $resTipo = $stmtTipo->get_result();
            $id_tipo_credito = $resTipo->fetch_assoc()['id_tipo_credito'] ?? 0;

            if ($id_tipo_credito == 0) {
                $msgText = "Actividad no válida.";
            } else {
                $stmtVal = $conn->prepare("SELECT numero_control FROM usuarios WHERE id_usuario = ? AND id_unidad = ?");
                $stmtVal->bind_param("ii", $id_alumno, $idUnidad);
                $stmtVal->execute();
                $resVal = $stmtVal->get_result();

                if ($rowAl = $resVal->fetch_assoc()) {
                    $numControl = $rowAl['numero_control'] ?: "ID_" . $id_alumno;
                    $baseDir = "../../Archivos/Evidencias/";
                    $alumnoDir = $baseDir . $numControl . "/";
                    if (!file_exists($alumnoDir))
                        mkdir($alumnoDir, 0777, true);

                    $fileName = $_FILES['archivo']['name'];
                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                    if ($fileExt !== 'pdf') {
                        $msgText = "Solo se permiten archivos PDF.";
                    } else {
                        $newFileName = "Credito_" . $id_actividad . "_" . uniqid() . ".pdf";
                        $destPath = $alumnoDir . $newFileName;
                        $dbPath = "Evidencias/" . $numControl . "/" . $newFileName;

                        if (move_uploaded_file($_FILES['archivo']['tmp_name'], $destPath)) {
                            $stmtIns = $conn->prepare("INSERT INTO evidencias_creditos (id_alumno, id_docente, id_tipo_credito, id_actividad, id_anio, archivo_pdf) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmtIns->bind_param("iiiiis", $id_alumno, $idUsuario, $id_tipo_credito, $id_actividad, $id_anio, $dbPath);
                            if ($stmtIns->execute()) {
                                $msgType = 'success';
                                $msgText = "Crédito subido correctamente.";
                            } else {
                                $msgText = "Error BD: " . $conn->error;
                            }
                        } else {
                            $msgText = "Error al mover archivo.";
                        }
                    }
                } else {
                    $msgText = "Alumno no válido o no pertenece a tu unidad.";
                }
            }
        }
    } elseif ($action === 'edit') {
        $id_evidencia = $_POST['id_evidencia'] ?? 0;
        $id_actividad = $_POST['id_actividad'] ?? '';
        $id_anio = $_POST['id_anio'] ?? '';

        $stmtCheck = $conn->prepare("
            SELECT e.id_evidencia, e.archivo_pdf, u.numero_control, u.id_usuario 
            FROM evidencias_creditos e
            INNER JOIN usuarios u ON e.id_alumno = u.id_usuario
            WHERE e.id_evidencia = ? AND u.id_unidad = ?
        ");
        $stmtCheck->bind_param("ii", $id_evidencia, $idUnidad);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();

        if ($rowCheck = $resCheck->fetch_assoc()) {
            $stmtTipo = $conn->prepare("SELECT id_tipo_credito FROM actividades WHERE id_actividad = ?");
            $stmtTipo->bind_param("i", $id_actividad);
            $stmtTipo->execute();
            $resTipo = $stmtTipo->get_result();
            $id_tipo_credito = $resTipo->fetch_assoc()['id_tipo_credito'] ?? 0;

            $sqlUpdate = "UPDATE evidencias_creditos SET id_tipo_credito=?, id_actividad=?, id_anio=? WHERE id_evidencia=?";
            $stmtUp = $conn->prepare($sqlUpdate);
            $stmtUp->bind_param("iiii", $id_tipo_credito, $id_actividad, $id_anio, $id_evidencia);
            $stmtUp->execute();

            if (!empty($_FILES['archivo']['name'])) {
                $numControl = $rowCheck['numero_control'] ?: "ID_" . $rowCheck['id_usuario'];
                $baseDir = "../../Archivos/Evidencias/";
                $alumnoDir = $baseDir . $numControl . "/";
                if (!file_exists($alumnoDir))
                    mkdir($alumnoDir, 0777, true);

                $fileName = $_FILES['archivo']['name'];
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                if ($fileExt === 'pdf') {
                    $oldPath = "../../Archivos/" . $rowCheck['archivo_pdf'];
                    if (file_exists($oldPath))
                        unlink($oldPath);
                    $newFileName = "Credito_" . $id_actividad . "_" . uniqid() . ".pdf";
                    $destPath = $alumnoDir . $newFileName;
                    $dbPath = "Evidencias/" . $numControl . "/" . $newFileName;

                    if (move_uploaded_file($_FILES['archivo']['tmp_name'], $destPath)) {
                        $stmtFile = $conn->prepare("UPDATE evidencias_creditos SET archivo_pdf=? WHERE id_evidencia=?");
                        $stmtFile->bind_param("si", $dbPath, $id_evidencia);
                        $stmtFile->execute();
                    }
                }
            }
            $msgType = 'success';
            $msgText = "Crédito actualizado.";
        } else {
            $msgText = "No tienes permiso para editar este crédito.";
        }
    } elseif ($action === 'delete') {
        $id_evidencia = $_POST['id_evidencia'] ?? 0;

        $stmtCheck = $conn->prepare("
            SELECT e.id_evidencia, e.archivo_pdf 
            FROM evidencias_creditos e
            INNER JOIN usuarios u ON e.id_alumno = u.id_usuario
            WHERE e.id_evidencia = ? AND u.id_unidad = ?
        ");
        $stmtCheck->bind_param("ii", $id_evidencia, $idUnidad);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();

        if ($rowCheck = $resCheck->fetch_assoc()) {
            $filePath = "../../Archivos/" . $rowCheck['archivo_pdf'];
            if (file_exists($filePath))
                unlink($filePath);

            $stmtDel = $conn->prepare("DELETE FROM evidencias_creditos WHERE id_evidencia = ?");
            $stmtDel->bind_param("i", $id_evidencia);
            if ($stmtDel->execute()) {
                $msgType = 'success';
                $msgText = "Crédito eliminado.";
            } else {
                $msgText = "Error al eliminar de BD.";
            }
        } else {
            $msgText = "No tienes permiso para eliminar este crédito.";
        }
    }

    if ($msgText) {
        $_SESSION['flash_message'] = ['type' => $msgType, 'message' => $msgText];
    }
    header("Location: evidencias.php");
    exit;
}

$alumnos = [];
$tiposCredito = [];
$actividades = [];
$anios = [];

$resTipos = $conn->query("SELECT * FROM tipo_creditos");
$resAct = $conn->query("SELECT * FROM actividades");
$resAnios = $conn->query("SELECT * FROM periodo_anio ORDER BY anio DESC");
$resCarreras = $conn->query("SELECT * FROM carreras ORDER BY nombre ASC");

while ($r = $resTipos->fetch_assoc())
    $tiposCredito[] = $r;
while ($r = $resAct->fetch_assoc())
    $actividades[] = $r;
while ($r = $resAnios->fetch_assoc())
    $anios[] = $r;

$carreras = [];
while ($r = $resCarreras->fetch_assoc())
    $carreras[] = $r;

if ($rol === 'Docente') {
    $stmtAl = $conn->prepare("SELECT id_usuario, nombres, apellido_p, apellido_m, numero_control FROM usuarios WHERE id_rol = 3 AND id_unidad = ?");
    $stmtAl->bind_param("i", $idUnidad);
    $stmtAl->execute();
    $resAlumnos = $stmtAl->get_result();
    while ($r = $resAlumnos->fetch_assoc())
        $alumnos[] = $r;
}

$id_carrera_filtro_inicial = isset($_GET['id_carrera']) && is_numeric($_GET['id_carrera']) ? (int) $_GET['id_carrera'] : 0;

$whereClauses = [];
$params = [];
$types = '';

if ($rol === 'Docente') {
    $whereClauses[] = "al.id_unidad = ?";
    $params[] = $idUnidad;
    $types .= 'i';
} elseif ($rol === 'Alumno') {
    $whereClauses[] = "e.id_alumno = ?";
    $params[] = $idUsuario;
    $types .= 'i';
}
$where = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

$sql = "
    SELECT 
        e.id_evidencia,
        e.archivo_pdf,
        e.fecha_subida,
        e.id_tipo_credito,
        e.id_actividad,
        e.id_anio,
        al.id_carrera,
        CONCAT(al.nombres,' ',al.apellido_p,' ',al.apellido_m) AS alumno,
        al.numero_control,
        CONCAT(doc.nombres,' ',doc.apellido_p) AS docente,
        tc.nombre AS tipo_credito,
        act.nombre AS actividad,
        pa.anio
    FROM evidencias_creditos e
    INNER JOIN usuarios al ON e.id_alumno = al.id_usuario
    INNER JOIN usuarios doc ON e.id_docente = doc.id_usuario
    INNER JOIN tipo_creditos tc ON e.id_tipo_credito = tc.id_tipo_credito
    INNER JOIN actividades act ON e.id_actividad = act.id_actividad
    INNER JOIN periodo_anio pa ON e.id_anio = pa.id_anio
    $where
    ORDER BY e.fecha_subida DESC
";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $resultado = $stmt->get_result();
} else {
    $resultado = $conn->query($sql);
}

ob_start();
?>

<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-3xl font-extrabold text-gray-800">Créditos</h1>
        <?php if ($rol === 'Alumno'): ?>
            <p class="text-gray-500 mt-1">
                <span class="font-semibold text-gray-700">
                    <?= htmlspecialchars($nombreCompleto) ?>
                </span>
            </p>
        <?php endif; ?>
    </div>

    <?php if ($rol === 'Docente'): ?>
        <button onclick="openModal('upload')"
            class="mt-4 md:mt-0 px-5 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700 transition">
            <i class="fas fa-upload mr-2"></i> Subir Crédito
        </button>
    <?php endif; ?>
</div>

<?php if ($error): ?>
    <div class="alert-message bg-red-100 text-red-700 p-4 rounded mb-4 border border-red-200">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert-message bg-green-100 text-green-700 p-4 rounded mb-4 border border-green-200">
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<div class="flex flex-col md:flex-row gap-4 mb-6">
    <div class="flex-1">
        <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                <i class="fas fa-search"></i>
            </span>
            <input type="text" id="searchInput" placeholder="Buscar por alumno, actividad o archivo..."
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
        </div>
    </div>

    <div class="w-full md:w-64">
        <?php if ($rol !== 'Alumno'): ?>
            <select id="filterCarrera"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 transition bg-white">
                <option value="0">Todas las Carreras</option>
                <?php foreach ($carreras as $ca): ?>
                    <option value="<?= $ca['id_carrera'] ?>" <?= $id_carrera_filtro_inicial == $ca['id_carrera'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ca['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
    </div>
</div>

<div class="bg-white rounded-xl shadow-md overflow-x-auto">
    <table id="evidenciasTable" class="min-w-full text-sm">
        <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
            <tr>
                <th class="px-6 py-4 text-left">Alumno</th>
                <th class="px-6 py-4 text-left">Tipo Crédito</th>
                <th class="px-6 py-4 text-left">Actividad</th>
                <th class="px-6 py-4 text-left">Año</th>
                <th class="px-6 py-4 text-left">Registrado por</th>
                <th class="px-6 py-4 text-left">Fecha</th>
                <th class="px-6 py-4 text-center">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            <?php while ($row = $resultado->fetch_assoc()): ?>
                <tr class="hover:bg-gray-50" data-carrera="<?= $row['id_carrera'] ?>">
                    <td class="px-6 py-4">
                        <div class="font-bold text-gray-800">
                            <?= htmlspecialchars($row['alumno']) ?>
                        </div>
                        <div class="text-xs text-gray-500">
                            <?= htmlspecialchars($row['numero_control']) ?>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <?= htmlspecialchars($row['tipo_credito']) ?>
                    </td>
                    <td class="px-6 py-4">
                        <?= htmlspecialchars($row['actividad']) ?>
                    </td>
                    <td class="px-6 py-4">
                        <?= htmlspecialchars($row['anio']) ?>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-gray-600">
                            <?= htmlspecialchars($row['docente']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <?= date('d/m/Y', strtotime($row['fecha_subida'])) ?>
                    </td>
                    <td class="px-6 py-4">
                        <div class="grid grid-cols-2 gap-2 justify-items-center">
                            <a href="ver_archivo.php?id=<?= $row['id_evidencia'] ?>" target="_blank" class="inline-flex items-center justify-center w-9 h-9 text-sm 
                                bg-blue-100 text-blue-700 rounded 
                                hover:bg-blue-200 transition" title="Ver">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="ver_archivo.php?id=<?= $row['id_evidencia'] ?>&download=1" class="inline-flex items-center justify-center w-9 h-9 text-sm 
                                bg-green-100 text-green-700 rounded 
                                hover:bg-green-200 transition" title="Descargar">
                                <i class="fas fa-download"></i>
                            </a>
                            <?php if ($rol === 'Docente'): ?>
                                <button onclick='openModal("edit", <?= json_encode($row) ?>)' class="inline-flex items-center justify-center w-9 h-9 text-sm 
                                    bg-yellow-100 text-yellow-700 rounded 
                                    hover:bg-yellow-200 transition" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="confirmDelete(<?= $row['id_evidencia'] ?>)" class="inline-flex items-center justify-center w-9 h-9 text-sm 
                                    bg-red-100 text-red-700 rounded 
                                    hover:bg-red-200 transition" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="paginationContainer" class="flex flex-col md:flex-row items-center justify-between mt-6 hidden">
    <div class="text-sm text-gray-600 mb-4 md:mb-0">
        Mostrando <span id="startCount" class="font-medium">0</span> a <span id="endCount" class="font-medium">0</span>
        de <span id="totalCount" class="font-medium">0</span> créditos
    </div>

    <div class="flex items-center space-x-2">
        <button id="btnPrev"
            class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed transition">
            <i class="fas fa-chevron-left mr-1"></i> Anterior
        </button>

        <span id="pageIndicator" class="px-4 py-2 text-gray-600 font-medium whitespace-nowrap">
            Página 1
        </span>

        <button id="btnNext"
            class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed transition">
            Siguiente <i class="fas fa-chevron-right ml-1"></i>
        </button>
    </div>
</div>

<?php if ($rol === 'Docente'): ?>
    <div id="modalForm" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 m-4">
            <div class="flex justify-between items-center mb-4">
                <h2 id="modalTitle" class="text-xl font-bold text-gray-800">Subir Crédito</h2>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" id="formAction" value="upload">
                <input type="hidden" name="id_evidencia" id="formId" value="">

                <div id="divAlumno">
                    <label class="block text-gray-700 font-bold mb-2">Alumno</label>
                    <select name="id_alumno" id="inputAlumno" required
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
                        <option value="">Seleccione un alumno...</option>
                        <?php foreach ($alumnos as $al): ?>
                            <option value="<?= $al['id_usuario'] ?>">
                                <?= htmlspecialchars($al['nombres'] . ' ' . $al['apellido_p'] . ' ' . $al['apellido_m'] . ' (' . $al['numero_control'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-bold mb-2">Actividad</label>
                    <select name="id_actividad" id="inputActividad" required
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
                        <?php foreach ($actividades as $act): ?>
                            <option value="<?= $act['id_actividad'] ?>">
                                <?= htmlspecialchars($act['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Año -->
                <div>
                    <label class="block text-gray-700 font-bold mb-2">Año</label>
                    <select name="id_anio" id="inputAnio" required
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
                        <?php foreach ($anios as $an): ?>
                            <option value="<?= $an['id_anio'] ?>">
                                <?= htmlspecialchars($an['anio']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-bold mb-2">Archivo PDF</label>
                    <input type="file" name="archivo" id="inputArchivo" accept=".pdf"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
                    <p id="fileHelp" class="text-xs text-gray-500 mt-1">Solo archivos .pdf permitidos.</p>
                </div>

                <div class="flex flex-col md:flex-row justify-end gap-3 mt-6">
                    <button type="button" onclick="closeModal()"
                        class="order-2 md:order-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 text-center">Cancelar</button>
                    <button type="submit"
                        class="order-1 md:order-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <form id="deleteForm" method="POST" class="hidden">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id_evidencia" id="deleteId">
    </form>
<?php endif; ?>

<?php
$scriptName = 'Evidencias.js';
$contenido = ob_get_clean();
include '../../Layout/layout.php';
