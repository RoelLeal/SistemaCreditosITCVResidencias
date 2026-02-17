document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('dataTable')) {
        initGestionDatosList();
    } else {
        initGestionDatosForm();
    }
});

function initGestionDatosForm() {
    const err = document.getElementById('alertError');
    if (err) {
        setTimeout(() => {
            err.style.display = 'none';
        }, 5000);
    }
}

function initGestionDatosList() {
    const input = document.getElementById('searchInput');
    const table = document.getElementById('dataTable');

    const sysMsg = document.getElementById('system-message');
    if (sysMsg) {
        setTimeout(() => {
            sysMsg.style.display = 'none';
        }, 5000);
    }

    if (!input || !table) return;

    const rows = table.querySelectorAll('tbody tr');

    input.addEventListener('input', function () {
        const term = this.value.toLowerCase();

        rows.forEach(row => {
            let found = false;
            const cells = row.querySelectorAll('td:not(:last-child)');

            cells.forEach(cell => {
                const text = cell.textContent;

                if (term && text.toLowerCase().includes(term)) {
                    found = true;
                    cell.innerHTML = text.replace(
                        new RegExp(`(${term})`, 'gi'),
                        '<span class="highlight">$1</span>'
                    );
                } else {
                    cell.textContent = text;
                }
            });

            row.style.display = found || term === '' ? '' : 'none';
        });
    });
}

function getTableFromDOM() {
    const el = document.getElementById('currentTableType');
    return el ? el.value : '';
}

window.agregar = function () {
    const table = getTableFromDOM();
    if (table) window.location.href = 'crear_datos.php?tabla=' + table;
}

window.editar = function (id) {
    const table = getTableFromDOM();
    if (table) window.location.href = 'editar_datos.php?tabla=' + table + '&id=' + id;
}

window.eliminar = function (id) {
    const table = getTableFromDOM();
    if (table && confirm('¿Seguro que deseas eliminar este registro?')) {
        window.location.href = 'eliminar_datos.php?tabla=' + table + '&id=' + id;
    }
}
