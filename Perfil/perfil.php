<?php
require_once '../Login/sesion.php';
require_once '../Configuracion/conexion.php';

$titulo = 'Perfil de Usuario';

$idUsuario = $_SESSION['id_usuario'];

$sql = "
    SELECT 
        u.id_usuario,
        u.nombres,
        u.apellido_p,
        u.apellido_m,
        u.correo,
        u.numero_control,
        r.nombre AS rol,
        un.nombre AS unidad,
        c.nombre AS carrera,
        u.fecha_registro
    FROM usuarios u
    INNER JOIN roles r ON u.id_rol = r.id_rol
    INNER JOIN unidades un ON u.id_unidad = un.id_unidad
    LEFT JOIN carreras c ON u.id_carrera = c.id_carrera
    WHERE u.id_usuario = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

$nombreCompleto = trim($usuario['nombres'] . ' ' . $usuario['apellido_p'] . ' ' . $usuario['apellido_m']);
$inicial = mb_strtoupper(mb_substr($usuario['nombres'], 0, 1, 'UTF-8'), 'UTF-8');

$msg = $_SESSION['flash_msg'] ?? '';
$msgType = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $stmtPass = $conn->prepare("SELECT password FROM usuarios WHERE id_usuario = ?");
    $stmtPass->bind_param("i", $idUsuario);
    $stmtPass->execute();
    $resPass = $stmtPass->get_result();

    if ($rowPass = $resPass->fetch_assoc()) {
        if (password_verify($current_password, $rowPass['password'])) {
            if ($new_password === $confirm_password && !empty($new_password)) {

                if (strlen($new_password) < 8 || !preg_match("/[A-Z]/", $new_password) || !preg_match("/[0-9]/", $new_password)) {
                    $_SESSION['flash_msg'] = "La nueva contraseña debe tener al menos 8 caracteres, una mayúscula y un número.";
                    $_SESSION['flash_type'] = "error";
                } else {
                    $newHash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmtUpdate = $conn->prepare("UPDATE usuarios SET password = ? WHERE id_usuario = ?");
                    $stmtUpdate->bind_param("si", $newHash, $idUsuario);
                    if ($stmtUpdate->execute()) {
                        $_SESSION['flash_msg'] = "Contraseña actualizada correctamente.";
                        $_SESSION['flash_type'] = "success";
                    }
                }
            } else {
                $_SESSION['flash_msg'] = $new_password !== $confirm_password ? "Las contraseñas no coinciden." : "La nueva contraseña no puede estar vacía.";
                $_SESSION['flash_type'] = "error";
            }
        } else {
            $_SESSION['flash_msg'] = "La contraseña actual es incorrecta.";
            $_SESSION['flash_type'] = "error";
        }
    }
    header("Location: perfil.php");
    exit;
}

ob_start();
?>

<div class="max-w-5xl mx-auto pb-10">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">

        <div class="px-8 py-8">
            <div class="flex flex-col md:flex-row items-center md:items-end gap-6 mb-8">
                <div class="w-32 h-32 rounded-xl bg-white p-2 shadow-md">
                    <div
                        class="w-full h-full rounded-lg bg-blue-600 flex items-center justify-center text-white text-5xl font-bold">
                        <?= $inicial ?>
                    </div>
                </div>
                <div class="flex-1 pb-4 text-center md:text-left">
                    <h1 class="text-2xl font-bold text-slate-800">
                        <?= htmlspecialchars($nombreCompleto) ?>
                    </h1>
                    <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 mt-2">
                        <span
                            class="px-3 py-1 rounded-md text-xs font-bold uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-100">
                            <?= htmlspecialchars($usuario['rol']) ?>
                        </span>
                        <span class="flex items-center text-slate-500 text-sm font-medium">
                            <i class="fas fa-university mr-2 text-slate-400"></i>
                            <?= htmlspecialchars($usuario['unidad']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-8">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center">
                            <i class="fas fa-user-circle mr-3 text-blue-600"></i>
                            Información General
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php if ($usuario['rol'] !== 'Alumno' && !empty($usuario['correo'])): ?>
                                <div class="p-4 rounded-lg bg-slate-50 border border-slate-200">
                                    <p class="text-xs font-bold text-slate-400 uppercase mb-1">Correo Institucional</p>
                                    <p class="text-slate-700 font-semibold"><?= htmlspecialchars($usuario['correo']) ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if ($usuario['rol'] === 'Alumno' && !empty($usuario['numero_control'])): ?>
                                <div class="p-4 rounded-lg bg-slate-50 border border-slate-200">
                                    <p class="text-xs font-bold text-slate-400 uppercase mb-1">Número de Control</p>
                                    <p class="text-slate-700 font-semibold">
                                        <?= htmlspecialchars($usuario['numero_control']) ?>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <?php if ($usuario['rol'] === 'Alumno' && !empty($usuario['carrera'])): ?>
                                <div class="p-4 rounded-lg bg-slate-50 border border-slate-200">
                                    <p class="text-xs font-bold text-slate-400 uppercase mb-1">Carrera</p>
                                    <p class="text-slate-700 font-semibold"><?= htmlspecialchars($usuario['carrera']) ?></p>
                                </div>
                            <?php endif; ?>

                            <div class="p-4 rounded-lg bg-slate-50 border border-slate-200">
                                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Miembro desde</p>
                                <p class="text-slate-700 font-semibold">
                                    <?php
                                    $fecha = strtotime($usuario['fecha_registro']);
                                    $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                                    echo date('d', $fecha) . ' ' . $meses[date('n', $fecha) - 1] . ', ' . date('Y', $fecha);
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-8 rounded-xl bg-white border border-slate-200 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center">
                            <i class="fas fa-shield-alt mr-3 text-blue-600"></i>
                            Seguridad de la Cuenta
                        </h3>

                        <?php if ($msg): ?>
                            <div id="alert-msg"
                                class="mb-6 p-4 rounded-lg text-sm font-medium <?= $msgType === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200' ?>">
                                <?= htmlspecialchars($msg) ?>
                            </div>
                            <script>setTimeout(() => document.getElementById('alert-msg')?.remove(), 5000);</script>
                        <?php endif; ?>

                        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <input type="hidden" name="action" value="change_password">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2 ml-1">Contraseña
                                    Actual</label>
                                <input type="password" name="current_password" required
                                    class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                    placeholder="••••••••">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2 ml-1">Nueva
                                    Contraseña</label>
                                <input type="password" name="new_password" required
                                    class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                    placeholder="Mín. 8 caracteres">
                                <p class="text-xs text-slate-400 mt-1"><i class="fas fa-info-circle mr-1"></i>Min. 8
                                    chars, 1 mayusc, 1 num</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2 ml-1">Confirmar
                                    Nueva</label>
                                <input type="password" name="confirm_password" required
                                    class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                    placeholder="Repite la clave">
                            </div>
                            <div class="md:col-span-2 pt-2">
                                <button type="submit"
                                    class="w-full md:w-auto px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-sm">
                                    Actualizar Seguridad
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="p-6 rounded-xl border border-slate-200">
                        <i class="fas fa-info-circle text-slate-400 mb-4 block text-2xl"></i>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Si necesitas actualizar tus datos personales o académicos, por favor contacta al
                            administrador del sistema.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$contenido = ob_get_clean();
include '../Layout/layout.php';
