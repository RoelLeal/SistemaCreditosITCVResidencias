<?php
$rol = $_SESSION['rol'] ?? '';
$paginaActual = basename($_SERVER['PHP_SELF']);

$permisos = [

    'panel' => ['Administrador', 'Docente', 'Alumno'],
    'usuarios' => ['Administrador', 'Docente'],
    'gestion_datos' => ['Administrador'],
    'asistencia' => ['Docente'],
    'evidencias' => ['Administrador', 'Docente', 'Alumno'],
];



function itemSidebar($archivo)
{
    $paginaActual = basename($_SERVER['PHP_SELF']);


    if (strpos($paginaActual, $archivo) !== false) {
        return 'bg-blue-600 text-white shadow-md';
    }

    return 'text-gray-700 hover:bg-blue-100';
}

?>

<nav id="sidebar" class="bg-white w-64 p-6 shadow-xl
            fixed md:sticky top-20 md:top-28 z-40
            overflow-y-auto
            rounded-2xl
            mt-0 md:mt-4 mb-6
            md:block hidden">

    <h2 class="px-4 py-2 font-extrabold text-blue-700 text-2xl
               border-b border-blue-200 mb-6">
        Menú
    </h2>

    <div class="flex flex-col gap-3">

        <?php if (in_array($rol, $permisos['panel'])): ?>
            <a href="<?= BASE_URL ?>Vistas/<?= $rol ?>/index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition
               <?= itemSidebar('index.php') ?>">
                <i class="fas fa-gauge"></i>
                <span class="font-medium">Panel</span>
            </a>
        <?php endif; ?>

        <?php if (in_array($rol, $permisos['usuarios'])): ?>
            <a href="<?= BASE_URL ?>Modulos/Usuarios/usuarios.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition
               <?= itemSidebar('usuarios.php') ?>">
                <i class="fas fa-users"></i>
                <span class="font-medium">Usuarios</span>
            </a>
        <?php endif; ?>

        <?php if (in_array($rol, $permisos['gestion_datos'])): ?>
            <a href="<?= BASE_URL ?>Modulos/GestionDatos/datos.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition
       <?= itemSidebar('datos.php') ?>">
                <i class="fas fa-database"></i>
                <span class="font-medium">Gestión de datos</span>
            </a>
        <?php endif; ?>

        <?php if (in_array($rol, $permisos['asistencia'])): ?>
            <a href="<?= BASE_URL ?>Modulos/Asistencias/asistencias.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition
               <?= itemSidebar('asistencias.php') ?>">
                <i class="fas fa-calendar-check"></i>
                <span class="font-medium">Asistencias</span>
            </a>
        <?php endif; ?>

        <?php if (in_array($rol, $permisos['evidencias'])): ?>
            <a href="<?= BASE_URL ?>Modulos/Evidencias/evidencias.php" class="flex items-center gap-3 px-4 py-3 rounded-xl transition
               <?= itemSidebar('evidencias.php') ?>">
                <i class="fas fa-file-pdf"></i>
                <span class="font-medium">Créditos</span>
            </a>
        <?php endif; ?>

    </div>
</nav>