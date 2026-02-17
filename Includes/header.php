<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}


$home = BASE_URL . 'index.php';

$nombreCompleto = trim(
    ($_SESSION['nombre'] ?? 'Usuario') . ' ' .
    ($_SESSION['apellido_p'] ?? '') . ' ' .
    ($_SESSION['apellido_m'] ?? '')
);

if (isset($_SESSION['rol'])) {
    switch ($_SESSION['rol']) {
        case 'Administrador':
            $home = BASE_URL . 'Vistas/Administrador/index.php';
            break;
        case 'Docente':
            $home = BASE_URL . 'Vistas/Docente/index.php';
            break;
        case 'Alumno':
            $home = BASE_URL . 'Vistas/Alumno/index.php';
            break;
    }
}
?>

<header class="bg-blue-700 fixed w-full z-50 shadow-lg">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center gap-4">

        <div class="flex items-center gap-2 md:gap-4 flex-shrink-0">

            <button id="menuButton" class="text-white text-2xl md:hidden focus:outline-none p-2">
                <i class="fa-solid fa-bars"></i>
            </button>
            <a href="<?= $home ?>" class="flex items-center gap-2 md:gap-3 text-white hover:opacity-90 transition">
                <img src="https://www.tecnm.mx/images/tecnm_virtual/tecnm.png" alt="Logo TECNM"
                    class="h-8 md:h-12 w-auto object-contain">
                <span class="text-sm md:text-2xl font-bold tracking-wide block truncate max-w-[150px] sm:max-w-none">
                    Sistema de Créditos
                </span>
            </a>
        </div>

        <div class="relative flex items-center gap-3">

            <a href="<?= $home ?>" class="text-white text-xl hover:text-blue-300 transition" title="Inicio">
                <i class="fa-solid fa-house"></i>
            </a>

            <button id="userMenuButton" class="flex items-center gap-2 text-white font-medium
               px-3 py-2 md:px-4 md:py-2 rounded-lg hover:bg-blue-800 transition">

                <span class="hidden md:block"><?= htmlspecialchars($nombreCompleto) ?></span>
                <span class="md:hidden"><i class="fas fa-user"></i></span>
                <i class="fa-solid fa-chevron-down text-sm"></i>
            </button>

            <div id="userMenu" class="hidden absolute right-0 top-full mt-2 w-52
               bg-white rounded-lg shadow-lg overflow-hidden">

                <a href="<?= BASE_URL ?>Perfil/perfil.php" class="flex items-center gap-3 px-4 py-3
                  text-gray-700 hover:bg-blue-50 transition">
                    <i class="fa-solid fa-user text-blue-600"></i>
                    Ver perfil
                </a>

                <a href="<?= BASE_URL ?>Login/logout.php" class="flex items-center gap-3 px-4 py-3
                  text-gray-700 hover:bg-red-50 transition">
                    <i class="fa-solid fa-right-from-bracket text-red-600"></i>
                    Cerrar sesión
                </a>
            </div>

        </div>

    </div>
</header>

<div class="h-20"></div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const userMenuButton = document.getElementById('userMenuButton');
        const userMenu = document.getElementById('userMenu');
        const menuButton = document.getElementById('menuButton');
        const sidebar = document.getElementById('sidebar');

        if (userMenuButton && userMenu) {
            userMenuButton.addEventListener('click', (e) => {
                e.stopPropagation();
                userMenu.classList.toggle('hidden');
            });

            document.addEventListener('click', (e) => {
                if (!userMenu.contains(e.target) && !userMenuButton.contains(e.target)) {
                    userMenu.classList.add('hidden');
                }
            });
        }


        if (menuButton && sidebar) {
            menuButton.addEventListener('click', (e) => {
                e.stopPropagation();
                sidebar.classList.toggle('hidden');
            });

            document.addEventListener('click', (e) => {
                if (window.innerWidth < 768) {
                    if (!sidebar.contains(e.target) && !menuButton.contains(e.target)) {
                        if (!sidebar.classList.contains('hidden')) {
                            sidebar.classList.add('hidden');
                        }
                    }
                }
            });
        }
    });
</script>