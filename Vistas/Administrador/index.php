<?php
require_once '../../Login/sesion.php';
require_once '../../Configuracion/conexion.php';

if ($_SESSION['rol'] !== 'Administrador') {
    header('Location: ../../index.php');
    exit;
}

$idUsuario = $_SESSION['id_usuario'] ?? 0;
$stmtUser = $conn->prepare("SELECT nombres, apellido_p, apellido_m FROM usuarios WHERE id_usuario = ?");
$stmtUser->bind_param("i", $idUsuario);
$stmtUser->execute();
$userData = $stmtUser->get_result()->fetch_assoc();
$nombreCompleto = ($userData['nombres'] ?? '') . ' ' . ($userData['apellido_p'] ?? '') . ' ' . ($userData['apellido_m'] ?? '');
$stmtUser->close();

$titulo = 'Panel Alumno - Sistema de Créditos';

$sqlUsuarios = "SELECT COUNT(*) AS total FROM usuarios";
$totalUsuarios = $conn->query($sqlUsuarios)->fetch_assoc()['total'] ?? 0;

$sqlDatos = "SELECT 
                (SELECT COUNT(*) FROM actividades) +
                (SELECT COUNT(*) FROM carreras) +
                (SELECT COUNT(*) FROM tipo_creditos) +
                (SELECT COUNT(*) FROM periodo_anio) +
                (SELECT COUNT(*) FROM unidades) AS total";
$totalDatos = $conn->query($sqlDatos)->fetch_assoc()['total'] ?? 0;

$sqlEvidencias = "SELECT COUNT(*) AS total FROM evidencias_creditos";
$totalEvidencias = $conn->query($sqlEvidencias)->fetch_assoc()['total'] ?? 0;

ob_start();
?>

<div class="bg-white rounded-xl p-8 shadow-sm border border-slate-200 mb-8">
    <div class="flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="flex items-center gap-5">
            <div class="w-14 h-14 rounded-lg bg-blue-600 flex items-center justify-center text-white text-xl shadow-md">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-800">
                    Panel de Administración
                </h1>
                <p class="text-slate-500 font-normal">
                    Bienvenido, <span
                        class="text-blue-600 font-semibold"><?= htmlspecialchars($nombreCompleto) ?></span>
                </p>
            </div>
        </div>
    </div>
</div>

<section class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">

    <div class="bg-white rounded-lg p-6 border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 mb-1">Total Usuarios</p>
                <h3 class="text-3xl font-bold text-slate-800">
                    <?= $totalUsuarios ?>
                </h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg p-6 border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 mb-1">Registros de Datos</p>
                <h3 class="text-3xl font-bold text-slate-800">
                    <?= $totalDatos ?>
                </h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">
                <i class="fas fa-database"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg p-6 border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500 mb-1">Créditos Registrados</p>
                <h3 class="text-3xl font-bold text-slate-800">
                    <?= $totalEvidencias ?>
                </h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center text-lg">
                <i class="fas fa-file-pdf"></i>
            </div>
        </div>
    </div>

</section>

<section>
    <div class="flex items-center gap-3 mb-6">
        <div class="h-6 w-1 bg-blue-600 rounded-full"></div>
        <h2 class="text-lg font-bold text-slate-800 uppercase tracking-wide">Módulos de Control</h2>
    </div>

    <div class="grid md:grid-cols-3 gap-6">

        <a href="<?= BASE_URL ?>Modulos/Usuarios/usuarios.php"
            class="group bg-white rounded-lg border border-slate-200 p-8 text-center shadow-sm hover:border-blue-300">
            <div class="w-20 h-20 mx-auto rounded-lg bg-blue-600 flex items-center justify-center mb-6 shadow-md">
                <i class="fas fa-user-gear text-3xl text-white"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-800 mb-3">Gestión de Usuarios</h3>
            <p class="text-sm text-slate-500 leading-relaxed uppercase font-semibold tracking-tighter">
                Control de accesos y roles
            </p>
        </a>

        <a href="<?= BASE_URL ?>Modulos/GestionDatos/datos.php"
            class="group bg-white rounded-lg border border-slate-200 p-8 text-center shadow-sm hover:border-indigo-300">
            <div class="w-20 h-20 mx-auto rounded-lg bg-indigo-600 flex items-center justify-center mb-6 shadow-md">
                <i class="fas fa-layer-group text-3xl text-white"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-800 mb-3">Gestion de datos</h3>
            <p class="text-sm text-slate-500 leading-relaxed uppercase font-semibold tracking-tighter">
                Catálogos y configuración
            </p>
        </a>

        <a href="<?= BASE_URL ?>Modulos/Evidencias/evidencias.php"
            class="group bg-white rounded-lg border border-slate-200 p-8 text-center shadow-sm hover:border-purple-300 transition block">
            <div class="w-20 h-20 mx-auto rounded-lg bg-purple-600 flex items-center justify-center mb-6 shadow-md">
                <i class="fas fa-file-signature text-3xl text-white"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-800 mb-3">Créditos</h3>
            <p class="text-sm text-slate-500 leading-relaxed uppercase font-semibold tracking-tighter">
                Validación y consulta
            </p>
        </a>

    </div>
</section>

<?php
$contenido = ob_get_clean();
include '../../Layout/layout.php';
