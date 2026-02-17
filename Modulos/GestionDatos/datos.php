<?php
require_once '../../Login/sesion.php';
require_once '../../Configuracion/conexion.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador') {
    header('Location: ../../index.php');
    exit;
}

$nombreCompleto = $_SESSION['nombre'];
$titulo = 'Gestión de Datos - Sistema de Créditos';

$tabla = isset($_GET['tabla']) ? $_GET['tabla'] : 'actividades';

$tablasConfig = [
    'actividades' => [
        'id_col' => 'id_actividad',
        'query' => "SELECT a.id_actividad, a.nombre, t.nombre AS tipo_credito
                    FROM actividades a
                    INNER JOIN tipo_creditos t ON a.id_tipo_credito = t.id_tipo_credito
                    ORDER BY a.nombre",
        'columns' => ['Nombre', 'Tipo de Crédito'],
        'fields' => ['nombre', 'tipo_credito']
    ],
    'carreras' => [
        'id_col' => 'id_carrera',
        'query' => "SELECT id_carrera, nombre, descripcion FROM carreras ORDER BY nombre",
        'columns' => ['Nombre', 'Descripción'],
        'fields' => ['nombre', 'descripcion']
    ],
    'tipo_creditos' => [
        'id_col' => 'id_tipo_credito',
        'query' => "SELECT id_tipo_credito, nombre, descripcion FROM tipo_creditos ORDER BY nombre",
        'columns' => ['Nombre', 'Descripción'],
        'fields' => ['nombre', 'descripcion']
    ],
    'periodo_anio' => [
        'id_col' => 'id_anio',
        'query' => "SELECT id_anio, anio FROM periodo_anio ORDER BY anio DESC",
        'columns' => ['Año'],
        'fields' => ['anio']
    ],
    'unidades' => [
        'id_col' => 'id_unidad',
        'query' => "SELECT id_unidad, nombre FROM unidades ORDER BY nombre",
        'columns' => ['Nombre'],
        'fields' => ['nombre']
    ],
];

if (!isset($tablasConfig[$tabla])) {
    $tabla = 'actividades';
}

$currentConfig = $tablasConfig[$tabla];
$resultado = $conn->query($currentConfig['query']);
$columns = $currentConfig['columns'];
$fields = $currentConfig['fields'];
$idCol = $currentConfig['id_col'];

$msg = $_GET['msg'] ?? '';
$alert = '';
if ($msg === 'eliminado') {
    $alert = '<div id="system-message" class="bg-green-100 text-green-700 p-4 rounded mb-4">Registro eliminado correctamente.</div>';
} elseif ($msg === 'creado') {
    $alert = '<div id="system-message" class="bg-green-100 text-green-700 p-4 rounded mb-4">Registro agregado correctamente.</div>';
} elseif ($msg === 'actualizado') {
    $alert = '<div id="system-message" class="bg-green-100 text-green-700 p-4 rounded mb-4">Registro actualizado correctamente.</div>';
} elseif ($msg === 'error_dependencia') {
    $alert = '<div id="system-message" class="bg-red-100 text-red-700 p-4 rounded mb-4">Error: No se puede eliminar este registro porque está siendo utilizado por otros elementos.</div>';
} elseif ($msg === 'error_bd') {
    $alert = '<div id="system-message" class="bg-red-100 text-red-700 p-4 rounded mb-4">Error en la base de datos.</div>';
}

ob_start();
?>

<?= $alert ?>

<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-3xl font-extrabold text-gray-800">
            Gestión de Datos
        </h1>
        <p class="text-gray-500 mt-1">
            Bienvenido, <span class="font-semibold"><?= htmlspecialchars($nombreCompleto) ?></span>
        </p>
    </div>

    <button onclick="agregar()"
        class="mt-4 md:mt-0 px-5 py-2 bg-blue-600 text-white rounded-lg shadow hover:bg-blue-700 transition">
        <i class="fas fa-plus mr-2"></i> Agregar <?= ucfirst(str_replace(['_', 'anio'], [' ', 'año'], $tabla)) ?>
    </button>
</div>

<div class="mb-4 flex flex-wrap gap-2">
    <?php foreach ($tablasConfig as $key => $cfg): ?>
        <a href="?tabla=<?= $key ?>"
            class="px-3 py-1 rounded-full border
           <?= $key === $tabla ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700' ?> hover:bg-blue-500 hover:text-white transition">
            <?= ucfirst(str_replace(['_', 'anio'], [' ', 'año'], $key)) ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="mb-4">
    <input type="text" id="searchInput"
        placeholder="Buscar en <?= ucfirst(str_replace(['_', 'anio'], [' ', 'año'], $tabla)) ?>..." class="w-full md:w-1/3 px-4 py-2 border border-gray-300 rounded-lg
           focus:outline-none focus:ring-2 focus:ring-blue-400">
    <input type="hidden" id="currentTableType" value="<?= $tabla ?>">
</div>

<div class="bg-white rounded-xl shadow-md overflow-x-auto max-w-6xl mx-auto border border-gray-100">
    <table id="dataTable" class="min-w-full text-lg">
        <thead class="bg-gray-100 text-gray-700 uppercase text-sm font-semibold">
            <tr>
                <?php foreach ($columns as $col): ?>
                    <th class="px-8 py-5 text-center tracking-wider"><?= $col ?></th>
                <?php endforeach; ?>
                <th class="px-8 py-5 text-center tracking-wider">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            <?php while ($row = $resultado->fetch_assoc()):
                $idValue = $row[$idCol];
                ?>
                <tr class="hover:bg-blue-50 transition duration-150">
                    <?php foreach ($fields as $field): ?>
                        <td class="px-8 py-5 text-gray-700 text-center"><?= htmlspecialchars($row[$field]) ?></td>
                    <?php endforeach; ?>
                    <td class="px-8 py-5 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button onclick="editar(<?= $idValue ?>)" class="inline-flex items-center justify-center w-9 h-9 text-sm 
                                           bg-yellow-100 text-yellow-700 rounded 
                                           hover:bg-yellow-200 transition" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="eliminar(<?= $idValue ?>)" class="inline-flex items-center justify-center w-9 h-9 text-sm 
                                           bg-red-100 text-red-700 rounded 
                                           hover:bg-red-200 transition" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<style>
    .highlight {
        background-color: #fde68a;
        font-weight: 600;
        border-radius: 3px;
        padding: 0 2px;
    }
</style>

<?php
$scriptName = 'GestionDatos.js';
$contenido = ob_get_clean();
include '../../Layout/layout.php';
?>