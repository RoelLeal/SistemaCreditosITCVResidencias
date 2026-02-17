<?php
require_once '../../Login/sesion.php';

if (!isset($_SESSION['rol'])) {
    header('Location: ../../Login/login.php');
    exit;
}

switch ($_SESSION['rol']) {

    case 'Administrador':
        header('Location: ../Administrador/index.php');
        exit;

    case 'Docente':
        header('Location: ../Docente/index.php');
        exit;

    case 'Alumno':
        header('Location: ../Alumno/index.php');
        exit;

    default:
        session_unset();
        session_destroy();
        header('Location: ../../Login/login.php');
        exit;
}
