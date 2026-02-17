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

require_once __DIR__ . '/../Configuracion/config.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: " . BASE_URL . "Login/login.php");
    exit();
}

// Configuración del tiempo de espera de la sesión (en segundos)
// 3600 segundos = 1 hora
$tiempo_limite = 3600;

// Comprobar si existe la marca de tiempo de "última actividad"
if (isset($_SESSION['ultima_actividad'])) {
    // Calcular el tiempo de vida de la sesión (TTL = Time To Live)
    $tiempo_inactivo = time() - $_SESSION['ultima_actividad'];

    // Si el tiempo de inactividad supera el límite
    if ($tiempo_inactivo > $tiempo_limite) {
        // Destruir la sesión y redirigir
        session_unset();
        session_destroy();

        // Redirigir al login con un mensaje (opcional)
        // Como la sesión se destruyó, podríamos pasar el mensaje por GET
        header("Location: " . BASE_URL . "Login/login.php?msg=timeout");
        exit();
    }
}

// Actualizar la marca de tiempo de la última actividad
$_SESSION['ultima_actividad'] = time();
