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

if (!$id_carrera || !$fecha) {
    header('Location: asistencias.php');
    exit;
}

$resC = $conn->query("SELECT nombre FROM carreras WHERE id_carrera = " . intval($id_carrera));
$nombreCarrera = $resC->fetch_assoc()['nombre'] ?? 'Desconocida';

$titulo = "Editar Asistencia: $nombreCarrera - " . date('d/m/Y', strtotime($fecha));

ob_start();
?>

<div class="flex items-center justify-between mb-8">
    <a href="consultar_asistencias.php"
        class="text-slate-500 hover:text-purple-600 transition-colors flex items-center gap-2 font-semibold">
        <i class="fas fa-arrow-left hidden md:inline"></i> Volver
    </a>
    <h1 class="text-xl font-bold text-slate-800 tracking-tight">Modificar Lista</h1>
</div>

<div class="bg-white rounded-xl p-6 shadow-sm border border-slate-200 mb-8">
    <div class="flex items-center gap-4">
        <div
            class="w-12 h-12 rounded-lg bg-yellow-50 text-yellow-600 flex items-center justify-center text-xl border border-yellow-100">
            <i class="fas fa-info-circle"></i>
        </div>
        <div>
            <p class="text-slate-500 text-xs font-bold uppercase tracking-wider">Editando sesión de:</p>
            <h2 class="text-lg font-bold text-slate-800"><?= htmlspecialchars($nombreCarrera) ?>
                (<?= date('d/m/Y', strtotime($fecha)) ?>)</h2>
        </div>
    </div>
</div>

<form id="asistenciaForm" action="procesar_asistencia.php" method="POST">
    <input type="hidden" name="id_carrera" value="<?= $id_carrera ?>">
    <input type="hidden" name="fecha" value="<?= $fecha ?>">

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto mb-8">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="px-8 py-5 text-left font-bold text-slate-500 uppercase tracking-wider text-xs w-1/3">
                        Estudiante</th>
                    <th class="px-8 py-5 text-center font-bold text-slate-500 uppercase tracking-wider text-xs w-24">
                        Estado Actual</th>
                    <th class="px-8 py-5 text-left font-bold text-slate-500 uppercase tracking-wider text-xs">
                        Justificación / Observaciones</th>
                </tr>
            </thead>
            <tbody id="studentListBody" class="divide-y divide-slate-100">
            </tbody>
        </table>
    </div>

    <div class="flex justify-end gap-4">
        <a href="ver_asistencia.php?id_carrera=<?= $id_carrera ?>&fecha=<?= $fecha ?>"
            class="bg-slate-100 text-slate-600 font-bold py-2.5 px-8 rounded-lg border border-slate-200 hover:bg-slate-200 transition-colors text-sm">
            Cancelar Edición
        </a>
        <button type="submit"
            class="bg-yellow-500 text-white font-bold py-2.5 px-8 rounded-lg shadow-sm hover:bg-yellow-600 transition-colors flex items-center gap-2 text-sm">
            <i class="fas fa-save"></i>
            Guardar Cambios
        </button>
    </div>
</form>

<?php
$scriptName = 'Asistencias.js';
$contenido = ob_get_clean();
include '../../Layout/layout.php';
?>