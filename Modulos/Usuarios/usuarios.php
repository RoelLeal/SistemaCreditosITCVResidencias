<?php
require_once '../../Login/sesion.php';
require_once '../../Configuracion/conexion.php';

$rol = $_SESSION['rol'] ?? '';
if (!in_array($rol, ['Administrador', 'Docente'])) {
    header('Location: ../../index.php');
    exit;
}

$nombreCompleto = $_SESSION['nombre'];
$titulo = 'Gestión de Usuarios - Sistema de Créditos';

if ($rol === 'Docente') {
    $idUnidad = $_SESSION['id_unidad'];
    $sql = "
        SELECT 
            u.id_usuario,
            u.nombres,
            u.apellido_p,
            u.apellido_m,
            u.numero_control,
            u.correo,
            r.nombre AS rol,
            un.nombre AS unidad,
            c.nombre AS carrera,
            u.fecha_registro
        FROM usuarios u
        INNER JOIN roles r ON u.id_rol = r.id_rol
        INNER JOIN unidades un ON u.id_unidad = un.id_unidad
        LEFT JOIN carreras c ON u.id_carrera = c.id_carrera
        WHERE (r.nombre = 'Alumno' OR r.nombre = 'Baja') 
          AND u.id_unidad = ?
          AND (r.nombre != 'Baja' OR u.numero_control IS NOT NULL)
        ORDER BY u.nombres
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idUnidad);
    $stmt->execute();
    $resultado = $stmt->get_result();
} else {
    $idUsuario = $_SESSION['id_usuario'];
    $sql = "
        SELECT 
            u.id_usuario,
            u.nombres,
            u.apellido_p,
            u.apellido_m,
            u.numero_control,
            u.correo,
            r.nombre AS rol,
            un.nombre AS unidad,
            c.nombre AS carrera,
            u.fecha_registro
        FROM usuarios u
        INNER JOIN roles r ON u.id_rol = r.id_rol
        INNER JOIN unidades un ON u.id_unidad = un.id_unidad
        LEFT JOIN carreras c ON u.id_carrera = c.id_carrera
        ORDER BY (u.id_usuario = $idUsuario) DESC, r.id_rol, u.nombres
    ";
    $resultado = $conn->query($sql);
}

$initialFilter = $_GET['filter'] ?? 'Administrador';
if ($rol === 'Docente') {
    $initialFilter = 'Alumno';
}
?>
<input type="hidden" id="initialFilter" value="<?= htmlspecialchars($initialFilter) ?>">
<?php
$unidades = $conn->query("SELECT nombre FROM unidades ORDER BY nombre");


$msg = $_GET['msg'] ?? '';
$alert = '';
if ($msg === 'eliminado') {
    $alert = '<div id="system-message" class="bg-green-100 text-green-700 p-4 rounded mb-4">Usuario eliminado correctamente.</div>';
} elseif ($msg === 'creado') {
    $alert = '<div id="system-message" class="bg-green-100 text-green-700 p-4 rounded mb-4">Usuario creado correctamente.</div>';
} elseif ($msg === 'actualizado') {
    $alert = '<div id="system-message" class="bg-green-100 text-green-700 p-4 rounded mb-4">Usuario actualizado correctamente.</div>';
} elseif ($msg === 'dado_baja') {
    $alert = '<div id="system-message" class="bg-blue-100 text-blue-700 p-4 rounded mb-4"><i class="fas fa-info-circle mr-2"></i>Usuario dado de baja correctamente. El usuario no podrá acceder al sistema pero sus registros se mantendrán.</div>';
} elseif ($msg === 'reactivado') {
    $alert = '<div id="system-message" class="bg-green-100 text-green-700 p-4 rounded mb-4"><i class="fas fa-check-circle mr-2"></i>Usuario reactivado correctamente.</div>';
} elseif ($msg === 'error_jerarquia') {
    $alert = '<div id="system-message" class="bg-red-100 text-red-700 p-4 rounded mb-4">No tienes permiso para eliminar a este usuario.</div>';
} elseif ($msg === 'error_permiso') {
    $alert = '<div id="system-message" class="bg-red-100 text-red-700 p-4 rounded mb-4">No tienes permisos para realizar esta acción.</div>';
} elseif ($msg === 'error_relacion') {
    $alert = '<div id="system-message" class="bg-red-100 text-red-700 p-4 rounded mb-4">
        <i class="fas fa-exclamation-triangle mr-2"></i>No se puede eliminar: El usuario tiene registros asociados (como asistencias) que impiden su eliminación.
    </div>';
} elseif ($msg === 'error_bd') {
    $alert = '<div id="system-message" class="bg-red-100 text-red-700 p-4 rounded mb-4">Ocurrió un error inesperado en la base de datos.</div>';
} elseif ($msg === 'error_rol_baja_no_existe') {
    $alert = '<div id="system-message" class="bg-red-100 text-red-700 p-4 rounded mb-4"><i class="fas fa-exclamation-triangle mr-2"></i>Error: No existe el rol "Baja" en la base de datos. Contacte al administrador del sistema.</div>';
}

if (isset($_SESSION['user_creado'])) {
    $uInfo = $_SESSION['user_creado'];
    $alert = '
    <div id="credentials-banner" class="bg-blue-600 text-white p-6 rounded-xl shadow-lg mb-8 relative overflow-hidden group">

        <button onclick="this.closest(\'#credentials-banner\').remove()" 
                class="absolute top-3 right-4 text-white/50 hover:text-white transition-colors z-20">
            <i class="fas fa-times text-lg"></i>
        </button>

        <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center text-2xl">
                    <i class="fas fa-id-card"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold">¡Usuario Alumno Creado con Éxito!</h3>
                    <p class="text-blue-100 text-sm">Estas son las credenciales de acceso para <strong>' . htmlspecialchars($uInfo['nombres']) . '</strong></p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="bg-white/10 px-4 py-2 rounded-lg border border-white/20">
                    <span class="text-[10px] uppercase font-bold text-blue-200 block">Usuario / No. Control</span>
                    <span class="font-mono font-bold">' . htmlspecialchars($uInfo['usuario']) . '</span>
                </div>
                <div class="bg-white/10 px-4 py-2 rounded-lg border border-white/20">
                    <span class="text-[10px] uppercase font-bold text-blue-200 block">Contraseña Temporal</span>
                    <span class="font-mono font-bold">' . htmlspecialchars($uInfo['password']) . '</span>
                </div>
                <button onclick="copyCredentials(\'' . htmlspecialchars($uInfo['usuario']) . '\', \'' . htmlspecialchars($uInfo['password']) . '\')" 
                        class="bg-white text-blue-600 px-4 py-2 rounded-lg font-bold hover:bg-blue-50 transition flex items-center gap-2">
                    <i class="fas fa-copy"></i> Copiar
                </button>
            </div>
        </div>

        <div class="absolute -right-4 -bottom-4 text-white/10 text-8xl transform -rotate-12">
            <i class="fas fa-check-circle"></i>
        </div>
    </div>';
    unset($_SESSION['user_creado']);
}

ob_start();
?>

<?= $alert ?>

<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
    <div>
        <h1 class="text-3xl font-extrabold text-gray-800">Gestión de Usuarios</h1>
        <p class="text-gray-500 mt-1">
            Bienvenido, <span class="font-semibold"><?= htmlspecialchars($nombreCompleto) ?></span>
        </p>
    </div>

    <a href="crear_usuario.php"
        class="mt-4 md:mt-0 px-5 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700 transition inline-flex items-center">
        <i class="fas fa-user-plus mr-2"></i> Agregar usuario
    </a>
</div>

<div class="mb-4">
    <input type="text" id="searchInput" placeholder="Buscar por nombre, rol o unidad..." class="w-full md:w-1/3 px-4 py-2 border border-gray-300 rounded-lg
               focus:outline-none focus:ring-2 focus:ring-blue-400">
</div>

<?php if ($rol === 'Administrador'): ?>
    <div class="flex flex-col md:flex-row md:items-center gap-4 mb-6">

        <div class="flex flex-wrap gap-2">
            <button data-rol="Administrador"
                class="rol-btn px-4 py-1 rounded-full text-sm bg-red-100 text-red-700">Administradores</button>
            <button data-rol="Docente"
                class="rol-btn px-4 py-1 rounded-full text-sm bg-blue-100 text-blue-700">Docentes</button>
            <button data-rol="Alumno"
                class="rol-btn px-4 py-1 rounded-full text-sm bg-green-100 text-green-700">Alumnos</button>
            <button data-rol="Baja" data-tipo-baja="Alumno"
                class="rol-btn px-4 py-1 rounded-full text-sm bg-gray-100 text-gray-700">
                <i class="fas fa-user-slash mr-1"></i>Alumnos Dados de Baja
            </button>
            <button data-rol="Baja" data-tipo-baja="Docente"
                class="rol-btn px-4 py-1 rounded-full text-sm bg-gray-100 text-gray-700">
                <i class="fas fa-user-slash mr-1"></i>Docentes Dados de Baja
            </button>
        </div>

        <select id="unidadFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400">
            <option value="all">Todas las unidades</option>
            <?php while ($u = $unidades->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($u['nombre']) ?>">
                    <?= htmlspecialchars($u['nombre']) ?>
                </option>
            <?php endwhile; ?>
        </select>

    </div>
<?php else: ?>

    <div class="flex flex-col md:flex-row md:items-center gap-4 mb-6">
        <div class="flex flex-wrap gap-2">
            <button data-rol="Alumno"
                class="rol-btn px-4 py-1 rounded-full text-sm bg-green-100 text-green-700">Alumnos</button>
            <button data-rol="Baja" data-tipo-baja="Alumno"
                class="rol-btn px-4 py-1 rounded-full text-sm bg-gray-100 text-gray-700">
                <i class="fas fa-user-slash mr-1"></i>Alumnos Dados de Baja
            </button>
        </div>
    </div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow-md overflow-x-auto mb-6">
    <table id="usuariosTable" class="min-w-full text-sm">
        <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
            <tr>
                <th class="px-6 py-4 text-center col-no-control">No. Control</th>
                <th class="px-6 py-4 text-center">Nombre completo</th>
                <th class="px-6 py-4 text-center col-correo">Correo</th>
                <th class="px-6 py-4 text-center">Rol</th>
                <th class="px-6 py-4 text-center col-carrera">Carrera</th>
                <th class="px-6 py-4 text-center">Unidad</th>
                <th class="px-6 py-4 text-center">Registro</th>
                <th class="px-6 py-4 text-center">Acciones</th>
            </tr>
        </thead>

        <tbody class="divide-y">
            <?php while ($u = $resultado->fetch_assoc()):
                $nombre = trim($u['nombres'] . ' ' . $u['apellido_p'] . ' ' . $u['apellido_m']);

                $isMe = ($u['id_usuario'] == $_SESSION['id_usuario']);
                $rowClass = $isMe ? 'bg-blue-50 border-l-4 border-blue-500' : 'hover:bg-gray-50';


                $rolOriginal = $u['rol'];

                $rolColor = match ($u['rol']) {
                    'Administrador' => 'bg-red-100 text-red-700',
                    'Docente' => 'bg-blue-100 text-blue-700',
                    'Alumno' => 'bg-green-100 text-green-700',
                    'Baja' => 'bg-gray-100 text-gray-700',
                    default => 'bg-gray-100 text-gray-700'
                };
                ?>
                <tr class="<?= $rowClass ?>" data-rol="<?= htmlspecialchars($u['rol']) ?>"
                    data-rol-original="<?= $u['rol'] === 'Baja' ? (empty($u['numero_control']) ? 'Docente' : 'Alumno') : htmlspecialchars($u['rol']) ?>"
                    data-unidad="<?= htmlspecialchars($u['unidad']) ?>">

                    <td class="px-6 py-4 font-medium text-gray-800 col-no-control text-center">
                        <?php if (!empty($u['numero_control'])): ?>
                            <?= htmlspecialchars($u['numero_control']) ?>
                        <?php endif; ?>
                    </td>

                    <td class="px-6 py-4 font-medium text-gray-800 text-center">
                        <?= htmlspecialchars($nombre) ?>
                        <?php if ($isMe): ?>
                            <span class="ml-2 px-2 py-0.5 rounded-full bg-blue-200 text-blue-800 text-xs font-bold">Tú</span>
                        <?php endif; ?>
                    </td>

                    <td class="px-6 py-4 text-gray-600 col-correo text-center">
                        <?php if (!empty($u['correo'])): ?>
                            <?= htmlspecialchars($u['correo']) ?>
                        <?php else: ?>
                            <span class="text-gray-300 select-none">-</span>
                        <?php endif; ?>
                    </td>

                    <td class="px-6 py-4 text-center">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $rolColor ?>">
                            <?= htmlspecialchars($u['rol']) ?>
                        </span>
                    </td>

                    <td class="px-6 py-4 text-gray-700 col-carrera text-center">
                        <?php if (!empty($u['carrera'])): ?>
                            <?= htmlspecialchars($u['carrera']) ?>
                        <?php endif; ?>
                    </td>

                    <td class="px-6 py-4 text-gray-700 text-center">
                        <?= htmlspecialchars($u['unidad']) ?>
                    </td>

                    <td class="px-6 py-4 text-gray-600 text-center">
                        <?= date('d/m/Y', strtotime($u['fecha_registro'])) ?>
                    </td>

                    <td class="px-6 py-4 text-center space-x-2">
                        <?php
                        $myRank = match ($rol) { 'Administrador' => 3, 'Docente' => 2, 'Alumno' => 1, default => 0};
                        $targetRank = match ($u['rol']) { 'Administrador' => 3, 'Docente' => 2, 'Alumno' => 1, 'Baja' => 0, default => 0};

                        $puedeEliminar = (($myRank > $targetRank) || ($rol === 'Administrador' && !$isMe));

                        $puedeEditar = ($myRank > $targetRank) || ($rol === 'Administrador');


                        $estaDeBaja = ($u['rol'] === 'Baja');
                        $targetRolReactivation = $estaDeBaja ? (empty($u['numero_control']) ? 'Docente' : 'Alumno') : '';

                        $showReactivate = $estaDeBaja && (
                            $rol === 'Administrador' ||
                            ($rol === 'Docente' && $targetRolReactivation === 'Alumno')
                        );
                        ?>

                        <div class="flex items-center justify-center gap-2">
                            <?php if ($showReactivate): ?>
                                <button
                                    onclick="confirmarReactivar(<?= $u['id_usuario'] ?>, '<?= htmlspecialchars($nombre) ?>', '<?= $targetRolReactivation ?>')"
                                    class="inline-flex items-center justify-center w-9 h-9 text-sm 
                                               bg-green-100 text-green-700 rounded 
                                               hover:bg-green-200 transition" title="Reactivar Usuario">
                                    <i class="fas fa-user-check"></i>
                                </button>
                            <?php else: ?>
                                <?php if ($puedeEditar): ?>
                                    <a href="editar_usuario.php?id=<?= $u['id_usuario'] ?>" class="inline-flex items-center justify-center w-9 h-9 text-sm 
                                              bg-yellow-100 text-yellow-700 rounded 
                                              hover:bg-yellow-200 transition" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                <?php endif; ?>

                                <?php if ($puedeEliminar): ?>
                                    <button onclick="confirmarEliminar(<?= $u['id_usuario'] ?>)" class="inline-flex items-center justify-center w-9 h-9 text-sm 
                                                   bg-red-100 text-red-700 rounded 
                                                   hover:bg-red-200 transition" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
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
        de <span id="totalCount" class="font-medium">0</span> usuarios
    </div>

    <div class="flex items-center space-x-2">
        <button id="btnPrev"
            class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed transition">
            <i class="fas fa-chevron-left mr-1"></i> Anterior
        </button>

        <span id="pageIndicator" class="px-4 py-2 text-gray-600 font-medium">
            Página 1
        </span>

        <button id="btnNext"
            class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed transition">
            Siguiente <i class="fas fa-chevron-right ml-1"></i>
        </button>
    </div>
</div>


<div id="modalDarBaja" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 overflow-hidden">
        <div class="bg-yellow-500 p-4 flex items-center gap-3">
            <i class="fas fa-exclamation-triangle text-white text-2xl"></i>
            <h3 class="text-white font-bold text-lg">Usuario con Registros Asociados</h3>
        </div>

        <div class="p-6">
            <p class="text-gray-700 mb-4">
                El usuario <strong id="nombreUsuarioBaja"></strong> tiene créditos u otros registros asociados en el
                sistema.
            </p>
            <p class="text-gray-700 mb-4">
                <strong>No se puede eliminar</strong> porque esto rompería la integridad de los datos.
            </p>
            <p class="text-gray-700 mb-6">
                <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                <strong>¿Desea dar de baja al usuario?</strong><br>
                <span class="text-sm text-gray-600">El usuario no podrá acceder al sistema, pero su nombre seguirá
                    apareciendo en los créditos que haya subido.</span>
            </p>

            <div class="flex gap-3">
                <button onclick="cerrarModalBaja()"
                    class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-semibold">
                    <i class="fas fa-times mr-2"></i>Cancelar
                </button>
                <button onclick="confirmarDarBaja()"
                    class="flex-1 px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition font-semibold">
                    <i class="fas fa-user-slash mr-2"></i>Dar de Baja
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .highlight {
        background-color: #fde68a;
        font-weight: 600;
        border-radius: 3px;
        padding: 0 2px;
    }
</style>

<?php if ($msg === 'tiene_relaciones' && isset($_SESSION['usuario_con_relaciones'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const usuarioRelaciones = <?= json_encode($_SESSION['usuario_con_relaciones']) ?>;
            document.getElementById('nombreUsuarioBaja').textContent = usuarioRelaciones.nombre;
            usuarioIdParaBaja = usuarioRelaciones.id;
            document.getElementById('modalDarBaja').classList.remove('hidden');
        });
    </script>
    <?php unset($_SESSION['usuario_con_relaciones']); ?>
<?php endif; ?>

<?php
$scriptName = 'Usuarios.js';
$contenido = ob_get_clean();
include '../../Layout/layout.php';
