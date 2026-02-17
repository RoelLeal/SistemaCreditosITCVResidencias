<?php
require_once __DIR__ . '/../Login/sesion.php';
require_once __DIR__ . '/../Configuracion/conexion.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= isset($titulo) ? htmlspecialchars($titulo) : 'Sistema de Créditos' ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>CSS/tailwind.min.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body {
            opacity: 0;
            transition: opacity 0.2s ease-in-out;
        }


        #sidebar {
            height: calc(100vh - 5rem) !important;
        }

        @media (min-width: 768px) {
            #sidebar {
                height: calc(100vh - 6rem) !important;
            }
        }


        header span.truncate {
            max-width: 150px;
        }

        @media (min-width: 640px) {
            header span.truncate {
                max-width: none;
            }
        }
    </style>

<body class="bg-gray-100 min-h-screen flex flex-col">

    <?php include __DIR__ . '/../Includes/header.php'; ?>

    <div class="flex flex-col md:flex-row flex-1 gap-4 px-2 md:px-4">

        <?php include __DIR__ . '/../Includes/sidebar.php'; ?>

        <main class="flex-1 px-4 py-6 md:px-6 md:py-10 w-full">
            <?= $contenido ?>
        </main>

    </div>

    <?php include __DIR__ . '/../Includes/footer.php'; ?>

    <script src="<?= BASE_URL ?>JS/global.js"></script>

    <?php if (isset($scriptName)): ?>
        <script src="<?= BASE_URL ?>JS/Modulos/<?= $scriptName ?>"></script>
    <?php endif; ?>

</body>

</html>