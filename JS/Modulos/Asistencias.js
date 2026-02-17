document.addEventListener('DOMContentLoaded', () => {
    initAsistenciasCommon();
    if (document.getElementById('filtroForm')) {
        initAsistenciasCreate();
    } else if (document.getElementById('asistenciaForm') && document.getElementById('studentListBody')) {
        initAsistenciasEdit();
    }
});

function initAsistenciasCommon() {
    const systemMessage = document.getElementById('system-message');
    if (systemMessage) {
        setTimeout(() => systemMessage.remove(), 5000);
    }
}

async function initAsistenciasCreate() {
    const filtroForm = document.getElementById('filtroForm');
    const studentListBody = document.getElementById('studentListBody');
    const placeholder = document.getElementById('placeholder');
    const noResults = document.getElementById('noResults');
    const listaContainer = document.getElementById('listaContainer');

    if (!filtroForm) return;

    filtroForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const idCarrera = document.getElementById('id_carrera').value;
        const fecha = document.getElementById('fecha').value;

        const formIdCarrera = document.getElementById('form_id_carrera');
        const formFecha = document.getElementById('form_fecha');
        if (formIdCarrera) formIdCarrera.value = idCarrera;
        if (formFecha) formFecha.value = fecha;

        try {
            const response = await fetch(`get_alumnos.php?id_carrera=${idCarrera}&fecha=${fecha}`);
            const data = await response.json();

            studentListBody.innerHTML = '';

            if (data.length > 0) {
                renderStudentRows(data, studentListBody);
                listaContainer.classList.remove('hidden');
                placeholder.classList.add('hidden');
                noResults.classList.add('hidden');
            } else {
                noResults.classList.remove('hidden');
                listaContainer.classList.add('hidden');
                placeholder.classList.add('hidden');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al conectar con el servidor.');
        } finally {
            placeholder.classList.add('hidden');
        }
    });
}

async function initAsistenciasEdit() {
    const studentListBody = document.getElementById('studentListBody');

    const form = document.getElementById('asistenciaForm');
    if (!form) return;

    const idCarreraInput = form.querySelector('input[name="id_carrera"]');
    const fechaInput = form.querySelector('input[name="fecha"]');

    if (!idCarreraInput || !fechaInput) return;

    const idCarrera = idCarreraInput.value;
    const fecha = fechaInput.value;

    try {
        const response = await fetch(`get_alumnos.php?id_carrera=${idCarrera}&fecha=${fecha}`);
        const data = await response.json();

        studentListBody.innerHTML = '';
        renderStudentRows(data, studentListBody);

    } catch (error) {
        console.error('Error:', error);
        alert('Error al cargar datos para edición.');
    }
}

function renderStudentRows(data, container) {
    data.forEach(alumno => {
        const isPresente = (alumno.presente == 1);
        const btnClass = isPresente ?
            'bg-green-500 text-white shadow-md hover:bg-green-600' :
            'bg-gray-200 text-gray-500 hover:bg-gray-300';

        const btnText = isPresente ? 'PRESENTE' : 'AUSENTE';
        const icon = isPresente ? '<i class="fas fa-check mr-1"></i>' : '<i class="fas fa-times mr-1"></i>';

        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50 transition-colors';
        row.innerHTML = `
        <td class="px-6 py-4">
            <div class="flex flex-col">
                <span class="font-bold text-gray-700">${alumno.nombre_completo}</span>
                <span class="text-[10px] text-gray-400 font-bold uppercase">${alumno.numero_control}</span>
            </div>
        </td>
        <td class="px-6 py-4 text-center">
            <input type="hidden" name="asistencia[${alumno.id_usuario}]" id="input_${alumno.id_usuario}" value="${isPresente ? 1 : 0}">
            <button type="button" 
                id="btn_${alumno.id_usuario}"
                onclick="toggleAsistencia('${alumno.id_usuario}')"
                class="px-4 py-2 rounded-lg font-bold text-xs transition-all duration-200 w-32 ${btnClass}">
                ${icon} ${btnText}
            </button>
        </td>
        <td class="px-6 py-4">
            <input type="text" id="desc_${alumno.id_usuario}" name="descripcion[${alumno.id_usuario}]" 
                value="${alumno.descripcion || ''}"
                placeholder="Escribe el motivo de inasistencia..."
                ${isPresente ? 'disabled' : ''}
                class="w-full px-4 py-2 rounded-lg border border-gray-200 bg-gray-50 focus:ring-2 focus:ring-purple-500 outline-none text-xs transition-all placeholder:text-gray-300 disabled:opacity-50 disabled:bg-gray-100 disabled:cursor-not-allowed">
        </td>
        `;
        container.appendChild(row);
    });
}

function toggleAsistencia(idAlumno) {
    const input = document.getElementById(`input_${idAlumno}`);
    const btn = document.getElementById(`btn_${idAlumno}`);
    const descInput = document.getElementById(`desc_${idAlumno}`);

    if (!input || !btn) return;

    const isNowPresent = (input.value == "0");

    input.value = isNowPresent ? "1" : "0";

    if (isNowPresent) {
        btn.className = 'px-4 py-2 rounded-lg font-bold text-xs transition-all duration-200 w-32 bg-green-500 text-white shadow-md hover:bg-green-600';
        btn.innerHTML = '<i class="fas fa-check mr-1"></i> PRESENTE';

        if (descInput) {
            descInput.disabled = true;
            descInput.value = '';
            descInput.classList.add('opacity-50', 'bg-gray-100');
        }
    } else {
        btn.className = 'px-4 py-2 rounded-lg font-bold text-xs transition-all duration-200 w-32 bg-gray-200 text-gray-500 hover:bg-gray-300';
        btn.innerHTML = '<i class="fas fa-times mr-1"></i> AUSENTE';

        if (descInput) {
            descInput.disabled = false;
            descInput.classList.remove('opacity-50', 'bg-gray-100');
        }
    }
}
