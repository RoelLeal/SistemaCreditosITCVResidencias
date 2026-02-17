<?php
require_once '../../Login/sesion.php';
require_once '../../Configuracion/conexion.php';

if ($_SESSION['rol'] !== 'Docente') {
    header('Location: ../../index.php');
    exit;
}

$titulo = 'Nueva Asistencia - Sistema de Créditos';

$sqlCarreras = "SELECT id_carrera, nombre FROM carreras ORDER BY nombre";
$resCarreras = $conn->query($sqlCarreras);

ob_start();
?>

<div class="flex items-center justify-between mb-8">
    <a href="consultar_asistencias.php"
        class="text-blue-500 hover:text-purple-600 transition-colors flex items-center gap-2 font-semibold">
        <i class="fas fa-arrow-left hidden md:inline"></i> Volver
    </a>
    <h1 class="text-xl font-bold text-blue-800 tracking-tight">Nueva Lista de Asistencia</h1>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 lg:gap-8">

    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200 sticky top-32">
            <form id="filtroForm" class="flex flex-col gap-5">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Carrera</label>
                    <select id="id_carrera" name="id_carrera" required
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-200 bg-gray-50 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all cursor-pointer text-sm">
                        <option value="">Seleccionar...</option>
                        <?php while ($c = $resCarreras->fetch_assoc()): ?>
                            <option value="<?= $c['id_carrera'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Fecha</label>
                    <input type="date" id="fecha" name="fecha" value="<?= date('Y-m-d') ?>" required
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-200 bg-gray-50 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all text-sm">
                </div>

                <button type="submit"
                    class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg shadow-sm hover:bg-blue-700 transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-users"></i> Cargar Alumnos
                </button>
            </form>
        </div>
    </div>

    <div class="lg:col-span-3">
        <div id="listaContainer" class="hidden">
            <form id="asistenciaForm" action="procesar_asistencia.php" method="POST">
                <input type="hidden" name="id_carrera" id="form_id_carrera">
                <input type="hidden" name="fecha" id="form_fecha">

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto mb-8">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-6 py-4 text-left font-bold text-gray-600 uppercase tracking-wider w-1/3">
                                    Estudiante</th>
                                <th class="px-6 py-4 text-center font-bold text-gray-600 uppercase tracking-wider w-24">
                                    Presente</th>
                                <th class="px-6 py-4 text-left font-bold text-gray-600 uppercase tracking-wider">
                                    Justificación / Observaciones</th>
                            </tr>
                        </thead>
                        <tbody id="studentListBody" class="divide-y divide-gray-100"></tbody>
                    </table>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                        class="bg-green-600 text-white font-bold py-3 px-8 rounded-lg shadow-sm hover:bg-green-700 transition-colors flex items-center gap-2">
                        <i class="fas fa-save"></i>
                        Confirmar Asistencia
                    </button>
                </div>
            </form>
        </div>

        <div id="placeholder" class="bg-white border border-gray-200 rounded-xl py-20 text-center shadow-sm">
            <div
                class="w-14 h-14 bg-gray-50 rounded-lg flex items-center justify-center mx-auto mb-4 text-gray-300 border border-gray-100">
                <i class="fas fa-i-cursor"></i>
            </div>
            <p class="text-gray-500 font-medium px-8">Selecciona una carrera y fecha para comenzar el pase de lista.
            </p>
        </div>

        <div id="noResults" class="hidden bg-red-50 border border-red-200 rounded-xl py-20 text-center shadow-sm">
            <i class="fas fa-user-slash text-3xl text-red-300 mb-4 block"></i>
            <p class="text-red-600 font-bold">No hay alumnos registrados en esta carrera.</p>
        </div>
    </div>
</div>

<?php
$scriptName = 'Asistencias.js';
$contenido = ob_get_clean();
include '../../Layout/layout.php';
?>