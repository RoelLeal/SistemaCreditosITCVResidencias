<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
require_once '../Configuracion/config.php';
require '../Configuracion/conexion.php';

$error = '';

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: ../Vistas/Panel/index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    if (is_numeric($usuario)) {
        $sql = "SELECT 
                    u.id_usuario,
                    u.nombres,
                    u.apellido_p,
                    u.apellido_m,
                    u.password,
                    u.id_rol,
                    u.id_unidad,
                    u.id_carrera,
                    r.nombre AS rol
                FROM usuarios u
                INNER JOIN roles r ON u.id_rol = r.id_rol
                WHERE u.numero_control = ? OR u.correo = ? OR u.id_usuario = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param("ssi", $usuario, $usuario, $usuario);
    } else {

        $sql = "SELECT 
                    u.id_usuario,
                    u.nombres,
                    u.apellido_p,
                    u.apellido_m,
                    u.password,
                    u.id_rol,
                    u.id_unidad,
                    u.id_carrera,
                    r.nombre AS rol
                FROM usuarios u
                INNER JOIN roles r ON u.id_rol = r.id_rol
                WHERE u.numero_control = ? OR u.correo = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $usuario, $usuario);
    }

    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $row = $resultado->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['loggedin'] = true;
            $_SESSION['id_usuario'] = $row['id_usuario'];
            $_SESSION['nombre'] = $row['nombres'];
            $_SESSION['apellido_p'] = $row['apellido_p'];
            $_SESSION['apellido_m'] = $row['apellido_m'];
            $_SESSION['rol'] = $row['rol'];
            $_SESSION['id_carrera'] = $row['id_carrera'];

            $_SESSION['id_unidad'] = $row['id_unidad'] ?? 0;

            switch ($row['rol']) {
                case 'Administrador':
                    header("Location: ../Vistas/Administrador/index.php");
                    break;
                case 'Docente':
                    header("Location: ../Vistas/Docente/index.php");
                    break;
                case 'Alumno':
                    header("Location: ../Vistas/Alumno/index.php");
                    break;
                default:
                    header("Location: ../index.php");
                    break;
            }
            exit;

        } else {
            $error = "Usuario o contraseña incorrectos";
        }
    } else {
        $error = "Usuario o contraseña incorrectos";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Acceso al Sistema | Créditos Académicos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= BASE_URL ?>CSS/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: #1e3a8a;
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-container {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .login-card {
            background: #ffffff;
            width: 100%;
            max-width: 400px;
            border-radius: 1rem;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.4);
            padding: 2.5rem;
            border: 1px solid #e2e8f0;
        }

        .input-focus-ring:focus {
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }

        .btn-elegant {
            background: #2563eb;
            transition: all 0.2s ease;
        }

        .btn-elegant:hover {
            background: #1d4ed8;
        }

        .btn-elegant:active {
            transform: scale(0.98);
        }

        .text-tiny {
            font-size: 9px;
        }

        .text-ultra-tiny {
            font-size: 8px;
        }

        .tracking-ultra-wide {
            letter-spacing: 0.2em;
        }

        .tracking-mega-wide {
            letter-spacing: 0.3em;
        }
    </style>
</head>

<body>

    <div class="card-container">
        <div class="login-card">

            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center mb-4">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/9/9d/TecNM_logo.png"
                        class="w-24 h-24 object-contain" alt="Logo">
                </div>
                <h1 class="text-xl font-bold text-gray-800 mb-1 tracking-tight">Acceso al Sistema</h1>
                <p class="text-gray-400 text-tiny font-bold uppercase tracking-ultra-wide">Créditos Académicos</p>
            </div>

            <?php if ($error): ?>
                <div
                    class="bg-red-50 border border-red-100 text-red-600 px-4 py-2.5 rounded-lg mb-4 text-xs font-bold flex items-center shadow-sm">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div class="space-y-1.5">
                    <label
                        class="block text-tiny font-bold text-gray-500 uppercase tracking-widest ml-1">Usuario</label>
                    <div class="relative group">
                        <span
                            class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-blue-600 transition-colors">
                            <i class="fa-regular fa-user text-xs"></i>
                        </span>
                        <input type="text" name="usuario" required autocomplete="username"
                            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600 focus:bg-white transition-all text-sm text-gray-700 placeholder-gray-300"
                            placeholder="Correo o No. de Control">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label
                        class="block text-tiny font-bold text-gray-500 uppercase tracking-widest ml-1">Contraseña</label>
                    <div class="relative group">
                        <span
                            class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 group-focus-within:text-blue-600 transition-colors">
                            <i class="fa-solid fa-key text-xs"></i>
                        </span>
                        <input type="password" name="password" id="password" required
                            class="w-full pl-10 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600 focus:bg-white transition-all text-sm text-gray-700 placeholder-gray-300"
                            placeholder="••••••••">
                        <button type="button" onclick="togglePassword()"
                            class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-blue-600 transition-colors">
                            <i class="fa-regular fa-eye text-xs" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit"
                    class="w-full py-3.5 btn-elegant text-white font-bold rounded-lg shadow-lg flex items-center justify-center text-xs mt-2 uppercase tracking-widest">
                    <span>Entrar al Sistema</span>
                    <i class="fa-solid fa-arrow-right ml-2 text-[9px] opacity-70"></i>
                </button>
            </form>

            <div class="mt-8 text-center">
                <p class="text-ultra-tiny text-gray-400 font-bold uppercase tracking-mega-wide">
                    Tecnológico Nacional de México
                </p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>

</html>