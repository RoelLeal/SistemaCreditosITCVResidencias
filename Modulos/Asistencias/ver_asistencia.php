<?php
require_once '../../Login/sesion.php';
require_once '../../Configuracion/conexion.php';

if ($_SESSION['rol'] !== 'Docente') {
    header('Location: ../../index.php');
    exit;
}

$id_carrera = $_GET['id_carrera'] ?? 0;
$fecha = $_GET['fecha'] ?? '';
$id_docente = $_SESSION['id_usuario'];

if (!$fecha) {
    header('Location: asistencias.php');
    exit;
}

$sqlCarrerasDia = "
    SELECT DISTINCT c.id_carrera, c.nombre 
    FROM asistencias a 
    INNER JOIN asistencia_detalle ad ON a.id_asistencia = ad.id_asistencia
    INNER JOIN usuarios u ON ad.id_alumno = u.id_usuario
    INNER JOIN carreras c ON u.id_carrera = c.id_carrera
    WHERE a.fecha = '$fecha' AND a.id_docente = $id_docente
    ORDER BY c.nombre ASC
";
$resCarrerasDia = $conn->query($sqlCarrerasDia);
$carrerasDisponibles = [];
while ($cd = $resCarrerasDia->fetch_assoc()) {
    $carrerasDisponibles[] = $cd;
}

$id_carrera = $_GET['id_carrera'] ?? ($carrerasDisponibles[0]['id_carrera'] ?? 0);

if (!$id_carrera) {
    header('Location: asistencias.php');
    exit;
}

$nombreCarrera = 'Desconocida';
foreach ($carrerasDisponibles as $cd) {
    if ($cd['id_carrera'] == $id_carrera) {
        $nombreCarrera = $cd['nombre'];
        break;
    }
}

$sql = "
    SELECT 
        u.numero_control,
        CONCAT(u.nombres, ' ', u.apellido_p, ' ', u.apellido_m) AS nombre_completo,
        ad.presente,
        ad.descripcion
    FROM asistencia_detalle ad
    INNER JOIN asistencias a ON ad.id_asistencia = a.id_asistencia
    INNER JOIN usuarios u ON ad.id_alumno = u.id_usuario
    WHERE a.id_docente = $id_docente 
      AND a.fecha = '$fecha' 
      AND u.id_carrera = $id_carrera
    ORDER BY u.nombres ASC
";
$resultado = $conn->query($sql);
$alumnos = [];
$totalPresentes = 0;
while ($row = $resultado->fetch_assoc()) {
    $alumnos[] = $row;
    if ($row['presente'])
        $totalPresentes++;
}
$totalAlumnos = count($alumnos);
$totalAusentes = $totalAlumnos - $totalPresentes;

$sqlFechasRecientes = "
    SELECT DISTINCT fecha 
    FROM asistencias 
    WHERE id_docente = $id_docente 
    ORDER BY fecha DESC 
    LIMIT 5
";
$resFechas = $conn->query($sqlFechasRecientes);
$esEditable = false;

while ($f = $resFechas->fetch_assoc()) {
    if ($f['fecha'] == $fecha) {
        $esEditable = true;
        break;
    }
}

$titulo = "Detalle de Asistencia - " . date('d/m/Y', strtotime($fecha));

$msg = $_GET['msg'] ?? '';
$alert = '';
if ($msg === 'guardado') {
    $alert = '<div id="system-message" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">¡Éxito!</strong> <span class="block sm:inline">Asistencia actualizada correctamente.</span></div>';
}

ob_start();
?>

<?= $alert ?>

<div class="flex items-center justify-between mb-8">
    <a href="consultar_asistencias.php"
        class="text-slate-500 hover:text-purple-600 transition-colors flex items-center gap-2 font-semibold">
        <i class="fas fa-arrow-left hidden md:inline"></i> Volver
    </a>
    <?php if ($esEditable): ?>
        <a href="editar_asistencia.php?id_carrera=<?= $id_carrera ?>&fecha=<?= $fecha ?>"
            class="bg-yellow-500 text-white px-5 py-2.5 rounded-lg font-bold shadow-sm hover:bg-yellow-600 transition-colors flex items-center gap-2 text-sm">
            <i class="fas fa-edit"></i> Editar Lista
        </a>
    <?php endif; ?>
</div>

<div class="bg-white rounded-xl p-8 shadow-sm border border-slate-200 mb-8">
    <div class="flex flex-col lg:flex-row items-center justify-between gap-8">
        <div class="flex items-center gap-6">
            <div
                class="w-16 h-16 rounded-xl bg-blue-600 flex items-center justify-center text-white text-2xl shadow-md">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div>
                <p class="text-slate-500 font-bold text-xs uppercase tracking-wider mb-1">Corte del día:</p>
                <h1 class="text-2xl font-bold text-slate-800">
                    <?php
                    $dias = ['Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles', 'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado', 'Sunday' => 'Domingo'];
                    $meses = ['January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo', 'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio', 'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre', 'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'];
                    echo $dias[date('l', strtotime($fecha))] . ', ' . date('d', strtotime($fecha)) . ' de ' . $meses[date('F', strtotime($fecha))] . ' de ' . date('Y', strtotime($fecha));
                    ?>
                </h1>
            </div>
        </div>

        <div class="w-full lg:w-auto flex flex-col sm:flex-row items-center gap-4">
            <div class="text-right hidden sm:block">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Resumen del Pase</p>
                <div class="flex items-center gap-3">
                    <span
                        class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100"><?= $totalPresentes ?>
                        Presentes</span>
                    <span
                        class="text-xs font-bold text-red-500 bg-red-50 px-2 py-0.5 rounded border border-red-100"><?= $totalAusentes ?>
                        Ausentes</span>
                </div>
            </div>
            <select onchange="window.location.href='?fecha=<?= $fecha ?>&id_carrera=' + this.value"
                class="w-full sm:w-64 px-4 py-2.5 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-blue-500 outline-none transition-all font-bold text-slate-700 text-sm cursor-pointer">
                <?php foreach ($carrerasDisponibles as $cd): ?>
                    <option value="<?= $cd['id_carrera'] ?>" <?= $id_carrera == $cd['id_carrera'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cd['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto mb-10">
    <table class="min-w-full text-sm">
        <thead>
            <tr class="bg-slate-50 border-b border-slate-200">
                <th class="px-8 py-5 text-left font-bold text-slate-500 uppercase tracking-wider text-xs">Estudiante
                </th>
                <th class="px-8 py-5 text-center font-bold text-slate-500 uppercase tracking-wider text-xs">Estado</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($alumnos as $row): ?>
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-8 py-5">
                        <div class="flex flex-col">
                            <span
                                class="text-base font-bold text-slate-700"><?= htmlspecialchars($row['nombre_completo']) ?></span>
                            <span
                                class="text-xs font-bold text-slate-400 uppercase tracking-wider mt-0.5"><?= $row['numero_control'] ?></span>
                        </div>
                    </td>
                    <td class="px-8 py-5 text-center">
                        <?php if ($row['presente']): ?>
                            <div class="flex flex-col items-center">
                                <div
                                    class="inline-flex items-center gap-2 px-4 py-1.5 rounded-lg bg-green-50 text-green-700 border border-green-200 font-bold text-[10px]">
                                    <i class="fas fa-check-circle"></i>
                                    PRESENTE
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="flex flex-col items-center gap-2">
                                <div
                                    class="inline-flex items-center gap-2 px-4 py-1.5 rounded-lg bg-red-50 text-red-600 border border-red-200 font-bold text-[10px]">
                                    <i class="fas fa-times-circle"></i>
                                    AUSENTE
                                </div>
                                <?php if (!empty($row['descripcion'])): ?>
                                    <div class="bg-slate-50 px-3 py-2 rounded-lg border border-slate-100 max-w-[250px] mx-auto">
                                        <p class="text-[10px] text-slate-500 italic flex items-start gap-2 text-left">
                                            <i class="fas fa-info-circle mt-0.5 text-blue-400"></i>
                                            <span><?= htmlspecialchars($row['descripcion']) ?></span>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$scriptName = 'Asistencias.js';
$contenido = ob_get_clean();
include '../../Layout/layout.php';
?>