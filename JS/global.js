document.addEventListener("DOMContentLoaded", () => {
    requestAnimationFrame(() => {
        document.body.style.opacity = '1';
    });

    const yearEl = document.getElementById("year");
    if (yearEl) {
        yearEl.textContent = new Date().getFullYear();
    }

    const relojEl = document.getElementById('relojFooter');
    if (relojEl) {
        function actualizarRelojFooter() {
            const ahora = new Date();
            const dia = String(ahora.getDate()).padStart(2, '0');
            const mes = String(ahora.getMonth() + 1).padStart(2, '0');
            const año = ahora.getFullYear();
            const hora = String(ahora.getHours()).padStart(2, '0');
            const minutos = String(ahora.getMinutes()).padStart(2, '0');
            const segundos = String(ahora.getSeconds()).padStart(2, '0');
            relojEl.textContent = `${dia}/${mes}/${año} | ${hora}:${minutos}:${segundos}`;
        }
        setInterval(actualizarRelojFooter, 1000);
        actualizarRelojFooter();
    }
});
