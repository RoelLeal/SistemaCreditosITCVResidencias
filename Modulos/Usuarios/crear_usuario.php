<?php
require_once '../../Login/sesion.php';
require_once '../../Configuracion/conexion.php';

$rolUsuario = $_SESSION['rol'] ?? '';
if (!in_array($rolUsuario, ['Administrador', 'Docente'])) {
    header('Location: usuarios.php');
    exit;
}

$error = '';
$success = '';

$roles = $conn->query("SELECT * FROM roles WHERE nombre != 'Baja'");
$unidades = $conn->query("SELECT * FROM unidades");
$carreras = $conn->query("SELECT * FROM carreras");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombres = trim($_POST['nombres']);
    $apellido_p = trim($_POST['apellido_p']);
    $apellido_m = trim($_POST['apellido_m']);
    $password = $_POST['password'] ?? '';
    $passGenerada = '';
    
    $numero_control = trim($_POST['numero_control'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $id_carrera = !empty($_POST['id_carrera']) ? (int)$_POST['id_carrera'] : null;

    if ($rolUsuario === 'Administrador') {
        $id_rol = (int) $_POST['id_rol'];
        $id_unidad = (int) $_POST['id_unidad'];
    } else {
        $resAlumno = $conn->query("SELECT id_rol FROM roles WHERE nombre = 'Alumno'");
        $rowAlumno = $resAlumno->fetch_assoc();
        $id_rol = $rowAlumno['id_rol'];
        $id_unidad = $_SESSION['id_unidad'];
    }

    $nombreRolSeleccionado = '';
    if ($rolUsuario === 'Administrador') {
        $stmtRol = $conn->prepare("SELECT nombre FROM roles WHERE id_rol = ?");
        $stmtRol->bind_param("i", $id_rol);
        $stmtRol->execute();
        $stmtRol->bind_result($nombreRolSeleccionado);
        $stmtRol->fetch();
        $stmtRol->close();
    } else {
        $nombreRolSeleccionado = 'Alumno';
    }

    if (empty($nombres) || empty($apellido_p) || empty($apellido_m)) {
        $error = "Todos los campos marcados con * son obligatorios.";
    } elseif ($nombreRolSeleccionado !== 'Alumno' && empty($password)) {
        $error = "La contraseña es obligatoria para este rol.";
    } elseif (!empty($password) && (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || !preg_match("/[0-9]/", $password))) {
        $error = "La contraseña debe tener al menos 8 caracteres, incluir una mayúscula y un número.";
    } elseif ($nombreRolSeleccionado === 'Alumno' && strlen($numero_control) !== 8) {
        $error = "El Número de Control debe ser de exactamente 8 caracteres.";
    } elseif ($nombreRolSeleccionado !== 'Alumno' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "El formato del correo electrónico no es válido.";
    } elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/", $nombres) || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/", $apellido_p) || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/", $apellido_m)) {
        $error = "Los nombres y apellidos solo pueden contener letras y espacios.";
    } else {
        if ($nombreRolSeleccionado === 'Alumno') {
            $stmtCheck = $conn->prepare("SELECT id_usuario FROM usuarios WHERE numero_control = ?");
            $stmtCheck->bind_param("s", $numero_control);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->num_rows > 0) {
                $error = "El Número de Control ya está en uso.";
            }
            $stmtCheck->close();
        } else {
            $stmtCheck = $conn->prepare("SELECT id_usuario FROM usuarios WHERE correo = ?");
            $stmtCheck->bind_param("s", $correo);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->num_rows > 0) {
                $error = "Ese correo ya está en uso.";
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

            if ($nombreRolSeleccionado === 'Alumno') {
                $passGenerada = "TEC" . $numero_control . date('Y');
                $password = $passGenerada;
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            $sqlInsert = "INSERT INTO usuarios (nombres, apellido_p, apellido_m, id_rol, id_unidad, id_carrera, numero_control, correo, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sqlInsert);
            $stmt->bind_param("sssiiisss", $nombres, $apellido_p, $apellido_m, $id_rol, $id_unidad, $id_carrera, $numero_control, $correo, $hash);

            if ($stmt->execute()) {
                if ($nombreRolSeleccionado === 'Alumno') {
                    $_SESSION['user_creado'] = [
                        'nombres' => $nombres . ' ' . $apellido_p,
                        'usuario' => $numero_control,
                        'password' => $passGenerada
                    ];
                }
                $redirectUrl = 'usuarios.php?msg=creado&filter=' . urlencode($nombreRolSeleccionado);
                header("Location: " . $redirectUrl);
                exit;
            } else {
                $error = "Error al crear usuario: " . $conn->error;
            }
        }
    }
}

$initialClassCorreo = '';
$initialClassAlumno = 'hidden';
$initialReqCorreo = 'required';
$initialReqAlumno = '';
$initialPasswordClass = '';
$initialMsgClass = 'hidden';

if ($rolUsuario === 'Docente') {
    $initialClassCorreo = 'hidden';
    $initialClassAlumno = '';
    $initialReqCorreo = '';
    $initialReqAlumno = 'required';
    $initialPasswordClass = 'hidden';
    $initialMsgClass = '';
}

ob_start();
?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-extrabold text-gray-800">Crear Nuevo Usuario</h1>
        <a href="usuarios.php" class="text-blue-600 hover:underline">
            <i class="fas fa-arrow-left hidden md:inline"></i> Volver
        </a>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-md p-4 md:p-8">
        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">

            <?php if ($rolUsuario === 'Administrador'): ?>
                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 pb-6 border-b border-gray-100">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Rol *</label>
                        <select name="id_rol" id="selectRol" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
                            <?php while ($r = $roles->fetch_assoc()): ?>
                                <option value="<?= $r['id_rol'] ?>" data-nombre="<?= $r['nombre'] ?>">
                                    <?= $r['nombre'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Unidad Académica *</label>
                        <select name="id_unidad" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
                            <?php while ($u = $unidades->fetch_assoc()): ?>
                                <option value="<?= $u['id_unidad'] ?>">
                                    <?= $u['nombre'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            <?php else: ?>
                <div class="md:col-span-2 bg-blue-50 p-4 rounded-lg mb-4">
                    <p class="text-blue-800 text-sm">
                        <i class="fas fa-info-circle mr-2"></i>
                        Usuario se creará como <strong>Alumno</strong> en tu unidad.
                    </p>
                    <input type="hidden" id="rolDocenteContext" value="Alumno">
                </div>
            <?php endif; ?>

            <div>
                <label class="block text-gray-700 font-bold mb-2">Nombres *</label>
                <input type="text" name="nombres" required pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+" title="Solo letras y espacios" autocomplete="off"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
            </div>

            <div>
                <label class="block text-gray-700 font-bold mb-2">Apellido Paterno *</label>
                <input type="text" name="apellido_p" required pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+" title="Solo letras y espacios" autocomplete="off"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
            </div>

            <div>
                <label class="block text-gray-700 font-bold mb-2">Apellido Materno *</label>
                <input type="text" name="apellido_m" required pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+" title="Solo letras y espacios" autocomplete="off"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
            </div>

            <div>
                <label class="block text-gray-700 font-bold mb-2">Contraseña *</label>
                <div class="relative <?= $initialPasswordClass ?>" id="containerPassword">
                    <input type="password" name="password" id="inputPassword" minlength="8" <?= $rolUsuario !== 'Docente' ? 'required' : '' ?> autocomplete="new-password"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
                    <button type="button" onclick="togglePassword('inputPassword', 'eyeIcon')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                        <i id="eyeIcon" class="fas fa-eye"></i>
                    </button>
                    <p class="text-xs text-gray-500 mt-1"><i class="fas fa-info-circle mr-1"></i>Mín. 8 caracteres, 1 mayúscula, 1 número.</p>
                </div>
                <p id="msgPasswordAlumno" class="text-xs text-blue-600 mt-2 <?= $initialMsgClass ?>">
                    <i class="fas fa-magic mr-1"></i> La contraseña se generará automáticamente: <strong>TEC + No. Control + Año</strong>
                </p>
            </div>

            <div id="fieldCorreo" class="md:col-span-2 <?= $initialClassCorreo ?>">
                <label class="block text-gray-700 font-bold mb-2">Correo Electrónico *</label>
                <input type="email" name="correo" id="inputCorreo" <?= $initialReqCorreo ?>
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
            </div>

            <div id="fieldNoControl" class="md:col-span-2 <?= $initialClassAlumno ?>">
                <label class="block text-gray-700 font-bold mb-2">Número de Control *</label>
                <input type="text" name="numero_control" id="inputNoControl" minlength="8" maxlength="8" pattern="\d*" title="Solo números" oninput="this.value = this.value.replace(/[^0-9]/g, '')" <?= $initialReqAlumno ?>
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
            </div>

            <div id="fieldCarrera" class="md:col-span-2 <?= $initialClassAlumno ?>">
                <label class="block text-gray-700 font-bold mb-2">Carrera (Programa Educativo)</label>
                <select name="id_carrera" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
                    <option value="">-- Seleccionar --</option>
                    <?php while ($c = $carreras->fetch_assoc()): ?>
                        <option value="<?= $c['id_carrera'] ?>">
                            <?= $c['nombre'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="md:col-span-2 flex flex-col md:flex-row justify-end gap-3 md:gap-4 mt-6">
                <a href="usuarios.php"
                    class="order-2 md:order-1 px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 text-center">Cancelar</a>
                <button type="submit" class="order-1 md:order-2 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Crear
                    Usuario</button>
            </div>

        </form>
    </div>
</div>

<?php
$scriptName = 'Usuarios.js';
$contenido = ob_get_clean();
include '../../Layout/layout.php';
?>
