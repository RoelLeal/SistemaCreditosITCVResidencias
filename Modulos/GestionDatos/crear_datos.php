<?php
require_once '../../Login/sesion.php';
require_once '../../Configuracion/conexion.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador') {
    header('Location: ../../index.php');
    exit;
}

$tabla = $_GET['tabla'] ?? '';
$allowedTables = ['actividades', 'carreras', 'tipo_creditos', 'periodo_anio', 'unidades'];

if (!in_array($tabla, $allowedTables)) {
    header('Location: datos.php?msg=error_tabla');
    exit;
}

$error = '';
$success = '';

$tableConfig = [
    'actividades' => [
        'title' => 'Nueva Actividad',
        'fields' => [
            'nombre' => ['label' => 'Nombre de la Actividad', 'type' => 'text', 'required' => true],
            'id_tipo_credito' => ['label' => 'Tipo de Crédito', 'type' => 'select_sql', 'query' => 'SELECT id_tipo_credito AS id, nombre FROM tipo_creditos ORDER BY nombre', 'required' => true]
        ],
        'insert_sql' => "INSERT INTO actividades (nombre, id_tipo_credito) VALUES (?, ?)",
        'types' => "si" 
    ],
    'carreras' => [
        'title' => 'Nueva Carrera',
        'fields' => [
            'nombre' => ['label' => 'Nombre de la Carrera', 'type' => 'text', 'required' => true],
            'descripcion' => ['label' => 'Descripción', 'type' => 'textarea', 'required' => false]
        ],
        'insert_sql' => "INSERT INTO carreras (nombre, descripcion) VALUES (?, ?)",
        'types' => "ss"
    ],
    'tipo_creditos' => [
        'title' => 'Nuevo Tipo de Crédito',
        'fields' => [
            'nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true],
            'descripcion' => ['label' => 'Descripción', 'type' => 'textarea', 'required' => false]
        ],
        'insert_sql' => "INSERT INTO tipo_creditos (nombre, descripcion) VALUES (?, ?)",
        'types' => "ss"
    ],
    'periodo_anio' => [
        'title' => 'Nuevo Año / Periodo',
        'fields' => [
            'anio' => ['label' => 'Año', 'type' => 'number', 'required' => true]
        ],
        'insert_sql' => "INSERT INTO periodo_anio (anio) VALUES (?)",
        'types' => "i"
    ],
    'unidades' => [
        'title' => 'Nueva Unidad Académica',
        'fields' => [
            'nombre' => ['label' => 'Nombre de la Unidad', 'type' => 'text', 'required' => true]
        ],
        'insert_sql' => "INSERT INTO unidades (nombre) VALUES (?)",
        'types' => "s"
    ]
];

$config = $tableConfig[$tabla];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = [];
    $types = $config['types'];
    $isValid = true;

    foreach ($config['fields'] as $fieldName => $fieldConfig) {
        $val = $_POST[$fieldName] ?? null;
        
        if ($fieldConfig['required'] && empty($val)) {
            $error = "El campo '{$fieldConfig['label']}' es obligatorio.";
            $isValid = false;
            break;
        }
        $values[] = $val;
    }

    if ($isValid) {
        $stmt = $conn->prepare($config['insert_sql']);
        if ($stmt) {
            $stmt->bind_param($types, ...$values);
            if ($stmt->execute()) {
                header("Location: datos.php?tabla=$tabla&msg=creado");
                exit;
            } else {
                if ($conn->errno === 1062) {
                    $error = "Error: Este registro ya existe.";
                } else {
                    $error = "Error en BD: " . $conn->error;
                }
            }
        } else {
            $error = "Error al preparar consulta: " . $conn->error;
        }
    }
}

$titulo = $config['title'];
ob_start();
?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-extrabold text-gray-800"><?= $config['title'] ?></h1>
        <a href="datos.php?tabla=<?= $tabla ?>" class="text-blue-600 hover:underline">
            <i class="fas fa-arrow-left hidden md:inline"></i> Volver
        </a>
    </div>

    <?php if ($error): ?>
        <div id="alertError" class="bg-red-100 text-red-700 p-4 rounded mb-4">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-lg p-4 md:p-8 border border-gray-100">
        <form method="POST">
            
            <?php foreach ($config['fields'] as $name => $field): ?>
                <div class="mb-5">
                    <label class="block text-gray-700 font-bold mb-2">
                        <?= $field['label'] ?> <?= $field['required'] ? '*' : '' ?>
                    </label>

                    <?php if ($field['type'] === 'text' || $field['type'] === 'number'): ?>
                        <input type="<?= $field['type'] ?>" name="<?= $name ?>" 
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400"
                               <?= $field['required'] ? 'required' : '' ?>>
                    
                    <?php elseif ($field['type'] === 'textarea'): ?>
                        <textarea name="<?= $name ?>" rows="3"
                                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400"
                                  <?= $field['required'] ? 'required' : '' ?>></textarea>

                    <?php elseif ($field['type'] === 'select_sql'): 
                        $resOptions = $conn->query($field['query']);
                    ?>
                        <select name="<?= $name ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400" <?= $field['required'] ? 'required' : '' ?>>
                            <option value="">-- Seleccionar --</option>
                            <?php while($opt = $resOptions->fetch_assoc()): ?>
                                <option value="<?= $opt['id'] ?>"><?= $opt['nombre'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="flex flex-col md:flex-row justify-end mt-8 gap-3 md:gap-4">
                <a href="datos.php?tabla=<?= $tabla ?>" class="order-2 md:order-1 px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-bold text-center">
                    Cancelar
                </a>
                <button type="submit" class="order-1 md:order-2 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold shadow-lg transform transition hover:scale-105">
                    Guardar
                </button>
            </div>

        </form>
    </div>
</div>

<?php
$scriptName = 'GestionDatos.js';
$contenido = ob_get_clean();
include '../../Layout/layout.php';
?>
