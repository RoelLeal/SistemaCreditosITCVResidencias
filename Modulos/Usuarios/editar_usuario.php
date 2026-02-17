<?php
require_once '../../Login/sesion.php';
require_once '../../Configuracion/conexion.php';

$rolUsuario = $_SESSION['rol'] ?? '';
if (!in_array($rolUsuario, ['Administrador', 'Docente'])) {
    header('Location: usuarios.php');
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: usuarios.php');
    exit;
}

$idEditar = (int) $_GET['id'];
$error = '';
$success = '';

$sql = "SELECT * FROM usuarios WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idEditar);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    header('Location: usuarios.php');
    exit;
}

$usuario = $res->fetch_assoc();
$targetRol = '';

$sqlRol = "SELECT nombre FROM roles WHERE id_rol = ?";
$stmtRol = $conn->prepare($sqlRol);
$stmtRol->bind_param("i", $usuario['id_rol']);
$stmtRol->execute();
$resRol = $stmtRol->get_result();
if ($r = $resRol->fetch_assoc()) {
    $targetRol = $r['nombre'];
}

$puedeEditar = false;
if ($rolUsuario === 'Administrador') {
    $puedeEditar = true;
} elseif ($rolUsuario === 'Docente') {
    if ($targetRol === 'Alumno') {
        $puedeEditar = true;
    }
}

if (!$puedeEditar) {
    header('Location: usuarios.php?msg=error_jerarquia');
    exit;
}

$roles = $conn->query("SELECT * FROM roles WHERE nombre != 'Baja'");
$unidades = $conn->query("SELECT * FROM unidades");
$carreras = $conn->query("SELECT * FROM carreras");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombres = trim($_POST['nombres']);
    $apellido_p = trim($_POST['apellido_p']);
    $apellido_m = trim($_POST['apellido_m']);
    $password = $_POST['password'];

    $id_rol = isset($_POST['id_rol']) ? (int) $_POST['id_rol'] : $usuario['id_rol'];
    $id_unidad = isset($_POST['id_unidad']) ? (int) $_POST['id_unidad'] : $usuario['id_unidad'];

    $numero_control = trim($_POST['numero_control'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $id_carrera = !empty($_POST['id_carrera']) ? (int) $_POST['id_carrera'] : null;

    $nombreRolSeleccionado = '';
    if (isset($_POST['id_rol'])) {
        $stmtCheck = $conn->prepare("SELECT nombre FROM roles WHERE id_rol = ?");
        $stmtCheck->bind_param("i", $id_rol);
        $stmtCheck->execute();
        $stmtCheck->bind_result($nombreRolSeleccionado);
        $stmtCheck->fetch();
        $stmtCheck->close();
    } else {
        $nombreRolSeleccionado = $targetRol;
    }

    if (empty($nombres) || empty($apellido_p) || empty($apellido_m)) {
        $error = "Todos los campos obligatorios deben ser llenados.";
    } elseif (!empty($password) && (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || !preg_match("/[0-9]/", $password))) {
        $error = "La nueva contraseña debe tener al menos 8 caracteres, incluir una mayúscula y un número.";
    } elseif ($nombreRolSeleccionado === 'Alumno' && strlen($numero_control) !== 8) {
        $error = "El Número de Control debe ser de exactamente 8 caracteres.";
    } elseif ($nombreRolSeleccionado !== 'Alumno' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "El formato del correo electrónico no es válido.";
    } elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/", $nombres) || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/", $apellido_p) || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/", $apellido_m)) {
        $error = "Los nombres y apellidos solo pueden contener letras y espacios.";
    } else {
        if ($nombreRolSeleccionado === 'Alumno') {
            $stmtCheck = $conn->prepare("SELECT id_usuario FROM usuarios WHERE numero_control = ? AND id_usuario != ?");
            $stmtCheck->bind_param("si", $numero_control, $idEditar);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->num_rows > 0) {
                $error = "El Número de Control ya está en uso por otro usuario.";
            }
            $stmtCheck->close();
        } else {
            $stmtCheck = $conn->prepare("SELECT id_usuario FROM usuarios WHERE correo = ? AND id_usuario != ?");
            $stmtCheck->bind_param("si", $correo, $idEditar);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->num_rows > 0) {
                $error = "Ese correo ya está en uso por otro usuario.";
            }
            $stmtCheck->close();
        }

        if (empty($error)) {
            if ($nombreRolSeleccionado === 'Alumno') {
                $correo = null;
            } else {
                $numero_control = null;
                $id_carrera = null;
            }

            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $sqlUpdate = "UPDATE usuarios SET nombres=?, apellido_p=?, apellido_m=?, id_rol=?, id_unidad=?, id_carrera=?, numero_control=?, correo=?, password=? WHERE id_usuario=?";
                $stmtUp = $conn->prepare($sqlUpdate);
                $stmtUp->bind_param("sssiiisssi", $nombres, $apellido_p, $apellido_m, $id_rol, $id_unidad, $id_carrera, $numero_control, $correo, $hash, $idEditar);
            } else {
                $sqlUpdate = "UPDATE usuarios SET nombres=?, apellido_p=?, apellido_m=?, id_rol=?, id_unidad=?, id_carrera=?, numero_control=?, correo=? WHERE id_usuario=?";
                $stmtUp = $conn->prepare($sqlUpdate);
                $stmtUp->bind_param("sssiisssi", $nombres, $apellido_p, $apellido_m, $id_rol, $id_unidad, $id_carrera, $numero_control, $correo, $idEditar);
            }

            if ($stmtUp->execute()) {
                $redirectUrl = 'usuarios.php?msg=actualizado&filter=' . urlencode($nombreRolSeleccionado);
                header("Location: " . $redirectUrl);
                exit;
            } else {
                $error = "Error al actualizar: " . $conn->error;
            }
        }
    }
}

ob_start();
?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-extrabold text-gray-800">Editar Usuario</h1>
        <a href="usuarios.php" class="text-blue-600 hover:underline">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded mb-4">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-md p-4 md:p-8">
        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">

            <?php $isAdmin = ($rolUsuario === 'Administrador'); ?>

            <?php if ($isAdmin): ?>
                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 pb-6 border-b border-gray-100">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Rol</label>
                        <select name="id_rol" id="selectRol"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
                            <?php
                            $roles->data_seek(0);
                            while ($r = $roles->fetch_assoc()):
                                ?>
                                <option value="<?= $r['id_rol'] ?>" data-nombre="<?= $r['nombre'] ?>"
                                    <?= $r['id_rol'] == $usuario['id_rol'] ? 'selected' : '' ?>>
                                    <?= $r['nombre'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Unidad Académica</label>
                        <select name="id_unidad"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
                            <?php while ($u = $unidades->fetch_assoc()): ?>
                                <option value="<?= $u['id_unidad'] ?>" <?= $u['id_unidad'] == $usuario['id_unidad'] ? 'selected' : '' ?>>
                                    <?= $u['nombre'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            <?php else: ?>
                <input type="hidden" id="rolDocenteContext" value="<?= htmlspecialchars($targetRol) ?>">
            <?php endif; ?>

            <div>
                <label class="block text-gray-700 font-bold mb-2">Nombres *</label>
                <input type="text" name="nombres" value="<?= htmlspecialchars($usuario['nombres']) ?>" required
                    pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+" title="Solo letras y espacios" autocomplete="given-name"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
            </div>

            <div>
                <label class="block text-gray-700 font-bold mb-2">Apellido Paterno *</label>
                <input type="text" name="apellido_p" value="<?= htmlspecialchars($usuario['apellido_p']) ?>" required
                    pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+" title="Solo letras y espacios" autocomplete="family-name"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
            </div>

            <div>
                <label class="block text-gray-700 font-bold mb-2">Apellido Materno *</label>
                <input type="text" name="apellido_m" value="<?= htmlspecialchars($usuario['apellido_m']) ?>" required
                    pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+" title="Solo letras y espacios" autocomplete="off"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
            </div>

            <div>
                <label class="block text-gray-700 font-bold mb-2">Contraseña (Opcional)</label>
                <div class="relative" id="containerPassword">
                    <input type="password" name="password" id="inputPassword" minlength="8" autocomplete="new-password"
                        placeholder="Dejar en blanco para mantener la actual"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
                    <button type="button" onclick="togglePassword('inputPassword', 'eyeIcon')"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                        <i id="eyeIcon" class="fas fa-eye"></i>
                    </button>
                    <p class="text-xs text-gray-500 mt-1"><i class="fas fa-info-circle mr-1"></i>Mín. 8 caracteres, 1 mayúscula, 1 número.</p>
                </div>
            </div>

            <div id="fieldCorreo" class="md:col-span-2">
                <label class="block text-gray-700 font-bold mb-2">Correo Electrónico *</label>
                <input type="email" name="correo" id="inputCorreo"
                    value="<?= htmlspecialchars($usuario['correo'] ?? '') ?>"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
            </div>

            <div id="fieldNoControl" class="md:col-span-2 hidden">
                <label class="block text-gray-700 font-bold mb-2">Número de Control *</label>
                <input type="text" name="numero_control" id="inputNoControl"
                    value="<?= htmlspecialchars($usuario['numero_control'] ?? '') ?>" minlength="8" maxlength="8"
                    pattern="\d*" title="Solo números" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
            </div>

            <div id="fieldCarrera" class="md:col-span-2 hidden">
                <label class="block text-gray-700 font-bold mb-2">Carrera (Programa Educativo)</label>
                <select name="id_carrera" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
                    <option value="">-- Seleccionar --</option>
                    <?php while ($c = $carreras->fetch_assoc()): ?>
                        <option value="<?= $c['id_carrera'] ?>" <?= $c['id_carrera'] == $usuario['id_carrera'] ? 'selected' : '' ?>>
                            <?= $c['nombre'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="md:col-span-2 flex flex-col md:flex-row justify-end gap-3 md:gap-4 mt-6">
                <a href="usuarios.php"
                    class="order-2 md:order-1 px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 text-center">Cancelar</a>
                <button type="submit"
                    class="order-1 md:order-2 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Guardar
                    Cambios</button>
            </div>

        </form>
    </div>
</div>

<?php
$scriptName = 'Usuarios.js';
$contenido = ob_get_clean();
include '../../Layout/layout.php';
?>