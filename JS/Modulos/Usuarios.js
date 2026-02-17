document.addEventListener('DOMContentLoaded', () => {
    initUsuariosList();
    initUsuariosForm();
});

function initUsuariosList() {
    const searchInput = document.getElementById('searchInput');
    const unidadFilter = document.getElementById('unidadFilter');
    const rolButtons = document.querySelectorAll('.rol-btn');
    const rows = document.querySelectorAll('#usuariosTable tbody tr');

    if (!searchInput && !rows.length) return;

    const paginationContainer = document.getElementById('paginationContainer');
    const btnPrev = document.getElementById('btnPrev');
    const btnNext = document.getElementById('btnNext');
    const pageIndicator = document.getElementById('pageIndicator');
    const startCount = document.getElementById('startCount');
    const endCount = document.getElementById('endCount');
    const totalCount = document.getElementById('totalCount');

    let rolActivo = document.getElementById('initialFilter') ? document.getElementById('initialFilter').value : 'all';
    if (rolActivo === '') rolActivo = 'all';

    let tipoBajaActivo = 'Alumno';
    let currentPage = 1;
    const itemsPerPage = 10;
    let visibleRows = [];

    if (rolButtons.length > 0) {
        let matchFound = false;
        rolButtons.forEach(btn => {
            btn.classList.remove('ring-2');
            if (btn.dataset.rol === rolActivo) {
                btn.classList.add('ring-2');
                matchFound = true;
            }
            btn.addEventListener('click', () => {
                rolButtons.forEach(b => b.classList.remove('ring-2'));
                btn.classList.add('ring-2');
                rolActivo = btn.dataset.rol;
                if (btn.dataset.tipoBaja) {
                    tipoBajaActivo = btn.dataset.tipoBaja;
                }
                currentPage = 1;
                filtrar();
            });
        });

        if (!matchFound && rolButtons.length > 0) {
            rolButtons.forEach(btn => {
                if (btn.dataset.rol === 'Administrador') {
                    btn.classList.add('ring-2');
                    rolActivo = 'Administrador';
                }
            });
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            currentPage = 1;
            filtrar();
        });
    }

    if (unidadFilter) {
        unidadFilter.addEventListener('change', () => {
            currentPage = 1;
            filtrar();
        });
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

    function filtrar() {
        const term = searchInput ? searchInput.value.toLowerCase() : '';
        const unidad = unidadFilter ? unidadFilter.value : 'all';
        let rolParaColumnas = rolActivo;

        if (rolActivo === 'Baja') {
            rolParaColumnas = tipoBajaActivo;
        }

        const hideControl = (rolParaColumnas === 'Administrador' || rolParaColumnas === 'Docente');
        document.querySelectorAll('.col-no-control').forEach(el => el.style.display = hideControl ? 'none' : '');

        const hideCorreo = (rolParaColumnas === 'Alumno');
        document.querySelectorAll('.col-correo').forEach(el => el.style.display = hideCorreo ? 'none' : '');

        const hideCarrera = (rolParaColumnas === 'Administrador' || rolParaColumnas === 'Docente');
        document.querySelectorAll('.col-carrera').forEach(el => el.style.display = hideCarrera ? 'none' : '');

        visibleRows = [];
        rows.forEach(row => {
            const rol = row.dataset.rol;
            const rolOriginal = row.dataset.rolOriginal || rol;
            const unidadRow = row.dataset.unidad;
            const texto = row.textContent.toLowerCase();

            let isMatch = texto.includes(term) && (unidad === 'all' || unidadRow === unidad);

            if (rolActivo === 'Baja') {
                isMatch = isMatch && rol === 'Baja';
                isMatch = isMatch && rolOriginal === tipoBajaActivo;
            } else {
                isMatch = isMatch && (rolActivo === 'all' || rol === rolActivo);
            }

            if (isMatch) {
                visibleRows.push(row);
            } else {
                row.style.display = 'none';
            }
        });

        renderPagination();
    }

    function renderPagination() {
        const totalItems = visibleRows.length;
        const totalPages = Math.ceil(totalItems / itemsPerPage) || 1;

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;

        rows.forEach(row => row.style.display = 'none');

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

    filtrar();

    const systemMessage = document.getElementById('system-message');
    if (systemMessage) {
        setTimeout(() => {
            systemMessage.style.display = 'none';
        }, 5000);
    }
}

function initUsuariosForm() {
    const selectRol = document.getElementById('selectRol');
    const rolDocenteContext = document.getElementById('rolDocenteContext');

    if (!selectRol && !rolDocenteContext) return;

    const fieldCorreo = document.getElementById('fieldCorreo');
    const fieldNoControl = document.getElementById('fieldNoControl');
    const fieldCarrera = document.getElementById('fieldCarrera');

    const inputCorreo = document.getElementById('inputCorreo');
    const inputNoControl = document.getElementById('inputNoControl');

    const containerPassword = document.getElementById('containerPassword');
    const msgPasswordAlumno = document.getElementById('msgPasswordAlumno');
    const inputPassword = document.getElementById('inputPassword');

    function updateFields() {
        let rol = '';

        if (selectRol) {
            const selectedOption = selectRol.options[selectRol.selectedIndex];
            rol = selectedOption.dataset.nombre;
        } else if (rolDocenteContext) {
            rol = rolDocenteContext.value;
        }

        if (rol === 'Alumno') {
            if (fieldNoControl) fieldNoControl.classList.remove('hidden');
            if (fieldCarrera) fieldCarrera.classList.remove('hidden');
            if (fieldCorreo) fieldCorreo.classList.add('hidden');

            if (inputNoControl) inputNoControl.required = true;
            if (inputCorreo) inputCorreo.required = false;

            if (containerPassword) {
                if (msgPasswordAlumno) {
                    containerPassword.classList.add('hidden');
                    msgPasswordAlumno.classList.remove('hidden');
                    if (inputPassword) inputPassword.required = false;
                } else {
                    containerPassword.classList.remove('hidden');
                }
            }
        } else {
            if (fieldCorreo) fieldCorreo.classList.remove('hidden');
            if (fieldNoControl) fieldNoControl.classList.add('hidden');
            if (fieldCarrera) fieldCarrera.classList.add('hidden');

            if (inputCorreo) inputCorreo.required = true;
            if (inputNoControl) inputNoControl.required = false;

            if (containerPassword) {
                containerPassword.classList.remove('hidden');
                if (msgPasswordAlumno) msgPasswordAlumno.classList.add('hidden');
                if (inputPassword && inputPassword.getAttribute('data-required-if-not-alumno') === 'true') {
                    inputPassword.required = true;
                }
            }
        }
    }

    if (selectRol) {
        selectRol.addEventListener('change', updateFields);
    }

    updateFields();

    const alerts = document.querySelectorAll('.bg-red-100, .bg-green-100, .bg-blue-50');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
}

let usuarioIdParaBaja = null;

function confirmarEliminar(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.')) {
        window.location.href = 'eliminar_usuario.php?id=' + id;
    }
}

function confirmarReactivar(id, nombre, rolObjetivo) {
    if (confirm(`El usuario "${nombre}" se reactivará como ${rolObjetivo}.\n\n¿Desea continuar?`)) {
        window.location.href = `reactivar_usuario.php?id=${id}&rol=${rolObjetivo}`;
    }
}

function cerrarModalBaja() {
    const modal = document.getElementById('modalDarBaja');
    if (modal) modal.classList.add('hidden');
    usuarioIdParaBaja = null;
}

function confirmarDarBaja() {
    if (usuarioIdParaBaja) {
        window.location.href = 'eliminar_usuario.php?id=' + usuarioIdParaBaja + '&accion=dar_baja';
    }
}

function copyCredentials(user, pass) {
    const text = `Usuario: ${user}\nContraseña: ${pass}`;
    navigator.clipboard.writeText(text).then(() => {
        alert('¡Credenciales copiadas al portapapeles!');
    });
}

function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (!input || !icon) return;

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
