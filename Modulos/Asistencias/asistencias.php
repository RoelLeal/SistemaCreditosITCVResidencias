<?php
require_once '../../Login/sesion.php';
require_once '../../Configuracion/conexion.php';

if ($_SESSION['rol'] !== 'Docente') {
    header('Location: ../../index.php');
    exit;
}

$idUsuario = $_SESSION['id_usuario'];
$titulo = 'Asistencias - Panel de Control';

$msg = $_GET['msg'] ?? '';
$alert = '';
if ($msg === 'guardado') {
    $alert = '<div id="system-message" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><strong class="font-bold">¡Éxito!</strong> <span class="block sm:inline">Asistencia guardada correctamente.</span></div>';
}

ob_start();
?>

<?= $alert ?>

<div class="bg-white rounded-xl p-8 shadow-sm border border-slate-200 mb-8">
    <div class="flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="flex items-center gap-5">
            <div
                class="w-14 h-14 rounded-lg bg-purple-600 flex items-center justify-center text-white text-xl shadow-md">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Módulo de Asistencias</h1>
                <p class="text-slate-500 font-normal">Gestión ordenada de faltas y asistencias.</p>
            </div>
        </div>

        <a href="crear_asistencia.php"
            class="bg-purple-600 text-white font-bold py-3 px-6 rounded-lg shadow-sm hover:bg-purple-700 transition-colors flex items-center gap-2">
            <i class="fas fa-plus-circle"></i>
            Nueva Asistencia
        </a>
    </div>
</div>


<div class="mt-10">
    <div class="flex items-center gap-3 mb-6">
        <div class="h-6 w-1 bg-purple-600 rounded-full"></div>
        <h2 class="text-lg font-bold text-slate-800 uppercase tracking-wide">Actividad Reciente</h2>
    </div>

    <div class="grid gap-4">
        <?php
        $sqlHistorial = "
            SELECT 
                a.fecha, 
                COUNT(DISTINCT c.id_carrera) AS total_carreras,
                SUM(ad.presente) AS total_presentes,
                COUNT(ad.id_alumno) AS total_alumnos
            FROM asistencias a
            INNER JOIN asistencia_detalle ad ON a.id_asistencia = ad.id_asistencia
            INNER JOIN usuarios u ON ad.id_alumno = u.id_usuario
            INNER JOIN carreras c ON u.id_carrera = c.id_carrera
            WHERE a.id_docente = ?
            GROUP BY a.fecha
            ORDER BY a.fecha DESC
            LIMIT 5
        ";
        $stmtH = $conn->prepare($sqlHistorial);
        $stmtH->bind_param("i", $idUsuario);
        $stmtH->execute();
        $resH = $stmtH->get_result();

        if ($resH->num_rows > 0):
            $meses = ['Jan' => 'Ene', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Abr', 'May' => 'May', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Ago', 'Sep' => 'Sep', 'Oct' => 'Oct', 'Nov' => 'Nov', 'Dec' => 'Dic'];
            while ($h = $resH->fetch_assoc()): ?>
                <div
                    class="bg-white rounded-lg p-5 shadow-sm border border-slate-200 flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="flex items-center gap-5">
                        <div class="text-center bg-slate-100 p-2 rounded-lg min-w-[70px] border border-slate-200">
                            <span class="block text-xl font-bold text-slate-800"><?= date('d', strtotime($h['fecha'])) ?></span>
                            <span
                                class="text-[10px] font-bold text-slate-500 uppercase"><?= $meses[date('M', strtotime($h['fecha']))] . ' ' . date('Y', strtotime($h['fecha'])) ?></span>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800 text-base">Asistencias del día</h3>
                            <p class="text-slate-500 text-xs font-medium uppercase">
                                <?= $h['total_carreras'] ?>
                                <?= $h['total_carreras'] == 1 ? 'Carrera atendida' : 'Carreras atendidas' ?>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="ver_asistencia.php?fecha=<?= $h['fecha'] ?>"
                            class="px-4 py-2 rounded-lg bg-blue-50 text-blue-600 text-sm font-bold border border-blue-100 hover:bg-blue-600 hover:text-white">
                            <i class="fas fa-eye mr-1"></i> Ver Detalles
                        </a>
                    </div>
                </div>
            <?php endwhile;
        else: ?>
            <div class="bg-white rounded-lg p-10 text-center border-2 border-dashed border-slate-200">
                <i class="fas fa-calendar-day text-4xl text-slate-200 mb-3 block"></i>
                <p class="text-slate-500 font-medium">Aún no has registrado asistencias.</p>
                <a href="crear_asistencia.php" class="text-purple-600 font-bold mt-2 inline-block hover:underline">Comenzar
                    ahora</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($resH->num_rows > 0): ?>
        <div class="mt-8 flex justify-center">
            <a href="consultar_asistencias.php"
                class="text-xs font-bold text-slate-500 hover:text-purple-600 flex items-center gap-2 uppercase tracking-wider">
                <i class="fas fa-history"></i>
                Ver Historial Completo
            </a>
        </div>
    <?php endif; ?>
</div>

<?php
$scriptName = 'Asistencias.js';
$contenido = ob_get_clean();
include '../../Layout/layout.php';
?>