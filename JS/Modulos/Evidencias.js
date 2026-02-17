document.addEventListener('DOMContentLoaded', () => {
    initEvidencias();
});

function initEvidencias() {
    const input = document.getElementById('searchInput');
    const filterCarrera = document.getElementById('filterCarrera');
    const rows = document.querySelectorAll('#evidenciasTable tbody tr');

    const paginationContainer = document.getElementById('paginationContainer');
    const btnPrev = document.getElementById('btnPrev');
    const btnNext = document.getElementById('btnNext');
    const pageIndicator = document.getElementById('pageIndicator');
    const startCount = document.getElementById('startCount');
    const endCount = document.getElementById('endCount');
    const totalCount = document.getElementById('totalCount');

    let currentPage = 1;
    const itemsPerPage = 10;
    let visibleRows = [];

    if (input) {
        input.addEventListener('input', () => {
            currentPage = 1;
            filtrar();
        });
    }

    if (filterCarrera) {
        filterCarrera.addEventListener('change', () => {
            currentPage = 1;
            filtrar();
        });
    }

    function filtrar() {
        const term = input ? input.value.toLowerCase() : '';
        const idCarrera = filterCarrera ? filterCarrera.value : '0';

        visibleRows = [];
        rows.forEach(row => {
            const rowCarrera = row.dataset.carrera;
            const texto = row.textContent.toLowerCase();

            const matchesSearch = texto.includes(term);
            const matchesCarrera = (idCarrera === '0' || rowCarrera === idCarrera);

            if (matchesSearch && matchesCarrera) {
                visibleRows.push(row);
            } else {
                row.style.display = 'none';
            }
        });

        renderPagination();
    }

    if (btnPrev) {
        btnPrev.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderPagination();
            }
        });
    }

    if (btnNext) {
        btnNext.addEventListener('click', () => {
            const totalPages = Math.ceil(visibleRows.length / itemsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                renderPagination();
            }
        });
    }

    function renderPagination() {
        const totalItems = visibleRows.length;
        const totalPages = Math.ceil(totalItems / itemsPerPage) || 1;

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;

        visibleRows.forEach(row => row.style.display = 'none');

        visibleRows.slice(startIndex, endIndex).forEach(row => {
            row.style.display = '';
        });

        if (totalItems > 0) {
            if (paginationContainer) paginationContainer.classList.remove('hidden');
            if (startCount) startCount.textContent = startIndex + 1;
            if (endCount) endCount.textContent = Math.min(endIndex, totalItems);
            if (totalCount) totalCount.textContent = totalItems;
            if (pageIndicator) pageIndicator.textContent = `Página ${currentPage} de ${totalPages}`;

            if (btnPrev) btnPrev.disabled = (currentPage === 1);
            if (btnNext) btnNext.disabled = (currentPage === totalPages);
        } else {
            if (paginationContainer) paginationContainer.classList.add('hidden');
        }
    }

    if (rows.length > 0) {
        filtrar();
    }

    setTimeout(function () {
        const alerts = document.querySelectorAll('.alert-message');
        alerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
}

function openModal(mode, data = null) {
    const modal = document.getElementById('modalForm');
    const title = document.getElementById('modalTitle');
    const action = document.getElementById('formAction');
    const idInput = document.getElementById('formId');

    const divAlumno = document.getElementById('divAlumno');
    const inputAlumno = document.getElementById('inputAlumno');
    const inputActividad = document.getElementById('inputActividad');
    const inputAnio = document.getElementById('inputAnio');
    const inputArchivo = document.getElementById('inputArchivo');
    const fileHelp = document.getElementById('fileHelp');

    if (!modal) return;

    modal.classList.remove('hidden');

    if (mode === 'edit') {
        title.textContent = 'Editar Crédito';
        action.value = 'edit';
        idInput.value = data.id_evidencia;

        divAlumno.classList.add('hidden');
        if (inputAlumno) inputAlumno.removeAttribute('required');

        inputActividad.value = data.id_actividad;
        inputAnio.value = data.id_anio;

        inputArchivo.removeAttribute('required');
        fileHelp.textContent = 'Deja vacío para mantener el archivo actual (' + data.archivo_pdf.split('/').pop() + ')';
    } else {
        title.textContent = 'Subir Crédito';
        action.value = 'upload';
        idInput.value = '';

        divAlumno.classList.remove('hidden');
        if (inputAlumno) {
            inputAlumno.setAttribute('required', 'true');
            inputAlumno.value = '';
        }

        inputActividad.value = inputActividad.options[0].value;
        inputAnio.value = inputAnio.options[0].value;

        inputArchivo.setAttribute('required', 'true');
        fileHelp.textContent = 'Solo archivos .pdf permitidos.';
    }
}

function closeModal() {
    const modal = document.getElementById('modalForm');
    if (modal) modal.classList.add('hidden');
}

function confirmDelete(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este crédito? Esta acción no se puede deshacer.')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
