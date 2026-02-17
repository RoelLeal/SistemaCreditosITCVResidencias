<?php
require_once '../../Login/sesion.php';
require_once '../../Configuracion/conexion.php';

if ($_SESSION['rol'] !== 'Docente') {
    header('Location: ../../index.php');
    exit;
}

$idUsuario = $_SESSION['id_usuario'];
$titulo = 'Consultar Asistencias - Sistema de Créditos';

$f_carrera = $_GET['id_carrera'] ?? '';
$f_mes = $_GET['mes'] ?? '';
$f_anio = $_GET['anio'] ?? date('Y');

$sqlCarreras = "SELECT id_carrera, nombre FROM carreras ORDER BY nombre";
$resCarreras = $conn->query($sqlCarreras);

ob_start();
?>

<div class="bg-white rounded-xl p-8 shadow-sm border border-slate-200 mb-8">
    <div class="flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="flex items-center gap-5">
            <div class="w-14 h-14 rounded-lg bg-blue-600 flex items-center justify-center text-white text-xl shadow-md">
                <i class="fas fa-history"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Historial de Asistencias</h1>
                <p class="text-slate-500 font-normal text-sm">Registro completo agrupado por fecha.</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="asistencias.php"
                class="bg-slate-100 text-slate-700 px-5 py-2.5 rounded-lg font-bold hover:bg-slate-200 transition-colors flex items-center gap-2 text-sm border border-slate-200">
                <i class="fas fa-arrow-left"></i> Panel Principal
            </a>
            <a href="crear_asistencia.php"
                class="bg-blue-600 text-white px-5 py-2.5 rounded-lg font-bold shadow-sm hover:bg-blue-700 transition-colors flex items-center gap-2 text-sm">
                <i class="fas fa-plus"></i> Nuevo Registro
            </a>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl p-6 shadow-sm border border-slate-200 mb-8">
    <form method="GET" class="grid md:grid-cols-4 gap-6 items-end">
        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Carrera</label>
            <select name="id_carrera"
                class="w-full px-4 py-2.5 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all text-sm">
                <option value="">Todas las carreras</option>
                <?php $resCarreras->data_seek(0);
                while ($c = $resCarreras->fetch_assoc()): ?>
                    <option value="<?= $c['id_carrera'] ?>" <?= $f_carrera == $c['id_carrera'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nombre']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Mes</label>
            <select name="mes"
                class="w-full px-4 py-2.5 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all text-sm">
                <option value="">Todos los meses</option>
                <?php
                $meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
                foreach ($meses as $i => $m): ?>
                    <option value="<?= $i + 1 ?>" <?= $f_mes == ($i + 1) ? 'selected' : '' ?>><?= $m ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Año</label>
            <input type="number" name="anio" value="<?= $f_anio ?>"
                class="w-full px-4 py-2.5 rounded-lg border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all text-sm">
        </div>

        <button type="submit"
            class="bg-yellow-500 text-white font-bold py-2.5 px-6 rounded-lg shadow-sm hover:bg-yellow-600 transition-colors flex items-center justify-center gap-2 text-sm">
            <i class="fas fa-filter"></i> Filtrar Resultados
        </button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto mb-10">
    <table class="min-w-full text-sm">
        <thead>
            <tr class="bg-slate-50 border-b border-slate-200">
                <th class="px-6 py-4 text-left font-bold text-slate-500 uppercase tracking-wider text-xs">Fecha</th>
                <th class="px-6 py-4 text-left font-bold text-slate-500 uppercase tracking-wider text-xs">Carrera</th>
                <th class="px-6 py-4 text-center font-bold text-slate-500 uppercase tracking-wider text-xs">Estadística
                </th>
                <th class="px-6 py-4 text-center font-bold text-slate-500 uppercase tracking-wider text-xs">Acciones
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php
            $registros_por_pagina = 10;
            $pagina_actual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
            if ($pagina_actual < 1)
                $pagina_actual = 1;
            $offset = ($pagina_actual - 1) * $registros_por_pagina;

            $sqlCount = "
                SELECT COUNT(DISTINCT a.fecha) as total 
                FROM asistencias a
                INNER JOIN asistencia_detalle ad ON a.id_asistencia = ad.id_asistencia
                INNER JOIN usuarios u ON ad.id_alumno = u.id_usuario
                INNER JOIN carreras c ON u.id_carrera = c.id_carrera
                WHERE a.id_docente = " . $idUsuario . "
                " . (!empty($f_carrera) ? "AND c.id_carrera = $f_carrera" : "") . "
                " . (!empty($f_mes) ? "AND MONTH(a.fecha) = $f_mes" : "") . "
                " . (!empty($f_anio) ? "AND YEAR(a.fecha) = $f_anio" : "") . "
            ";
            $resCount = $conn->query($sqlCount);
            $total_registros = ($resCount) ? $resCount->fetch_assoc()['total'] : 0;
            $total_paginas = ceil($total_registros / $registros_por_pagina);

            $sqlHistorial = "
                SELECT 
                    a.fecha, 
                    COUNT(DISTINCT c.id_carrera) AS total_carreras,
                    SUM(ad.presente) AS total_presentes,
                    COUNT(ad.id_alumno) AS total_alumnos
                FROM asistencias a
                INNER JOIN asistencia_detalle ad ON a.id_asistencia = ad.id_asistencia
                INNER JOIN usuarios u ON ad.id_alumno = u.id_usuario
                INNER JOIN carreras c ON u.id_carrera = c.id_carrera
                WHERE a.id_docente = " . $idUsuario . "
                " . (!empty($f_carrera) ? "AND c.id_carrera = $f_carrera" : "") . "
                " . (!empty($f_mes) ? "AND MONTH(a.fecha) = $f_mes" : "") . "
                " . (!empty($f_anio) ? "AND YEAR(a.fecha) = $f_anio" : "") . "
                GROUP BY a.fecha
                ORDER BY a.fecha DESC
                LIMIT $offset, $registros_por_pagina
            ";
            $resH = $conn->query($sqlHistorial);

            if ($resH && $resH->num_rows > 0):
                while ($h = $resH->fetch_assoc()): ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="font-bold text-slate-700"><?= date('d/m/Y', strtotime($h['fecha'])) ?></span>
                                <span
                                    class="text-[10px] text-slate-400 font-bold uppercase"><?= date('l', strtotime($h['fecha'])) ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-slate-600 font-semibold">
                            <?= $h['total_carreras'] ?>         <?= $h['total_carreras'] == 1 ? 'Carrera' : 'Carreras' ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-3">
                                <div class="w-20 bg-slate-100 h-1.5 rounded-full overflow-hidden border border-slate-200">
                                    <div class="bg-purple-600 h-full"
                                        style="width: <?= ($h['total_alumnos'] > 0) ? ($h['total_presentes'] / $h['total_alumnos']) * 100 : 0 ?>%">
                                    </div>
                                </div>
                                <span
                                    class="text-[11px] font-bold text-purple-700"><?= $h['total_presentes'] ?>/<?= $h['total_alumnos'] ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <a href="ver_asistencia.php?fecha=<?= $h['fecha'] ?>"
                                class="inline-flex items-center justify-center px-3 py-2 md:px-4 md:py-1.5 rounded-lg bg-blue-50 text-blue-600 font-bold border border-blue-100 hover:bg-blue-600 hover:text-white transition-colors text-xs"
                                title="Ver Detalles">
                                <i class="fas fa-eye md:mr-1"></i> <span class="hidden md:inline">Detalles</span>
                            </a>
                        </td>
                    </tr>
                <?php endwhile;
            else: ?>
                <tr>
                    <td colspan="4" class="px-6 py-16 text-center">
                        <div
                            class="w-12 h-12 bg-slate-50 rounded-lg flex items-center justify-center mx-auto mb-4 text-slate-300 border border-slate-100">
                            <i class="fas fa-search"></i>
                        </div>
                        <p class="text-slate-500 font-medium">No se encontraron registros con esos filtros.</p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div
        class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
        <p class="text-xs text-slate-500 font-bold uppercase tracking-widest">
            <?php if ($total_registros > 0): ?>
                Mostrando <?= $offset + 1 ?>-<?= min($offset + $registros_por_pagina, $total_registros) ?> de
                <?= $total_registros ?> días registrados
            <?php else: ?>
                0 registros encontrados
            <?php endif; ?>
        </p>

        <?php if ($total_paginas > 1): ?>
            <div class="flex items-center gap-1">
                <?php
                $params = $_GET;
                for ($i = 1; $i <= $total_paginas; $i++):
                    $params['pagina'] = $i;
                    $url = "?" . http_build_query($params);
                    ?>
                    <a href="<?= $url ?>"
                        class="w-9 h-9 flex items-center justify-center rounded-lg text-xs font-bold transition-all <?= ($pagina_actual == $i) ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>


<?php
$contenido = ob_get_clean();
include '../../Layout/layout.php';
?>