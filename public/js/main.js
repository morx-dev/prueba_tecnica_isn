/**
 * Hogares ISN - Inventario de Herramientas
 * Script unico compartido por todas las vistas (JS puro, sin librerias).
 */
document.addEventListener('DOMContentLoaded', function () {
    inicializarConfirmaciones();
    inicializarAutoDesaparicionDeAlertas();
    inicializarBuscadorDeTablas();
    inicializarValidacionDeFormularios();
});

/**
 * Reemplaza los onclick="return confirm(...)" sueltos en el HTML.
 * Cualquier enlace con data-confirm="mensaje" pide confirmacion antes de seguir.
 * Ej: <a href="/taller/desactivar/3" data-confirm="¿Desactivar este taller?">Desactivar</a>
 */
function inicializarConfirmaciones() {
    document.querySelectorAll('[data-confirm]').forEach(function (enlace) {
        enlace.addEventListener('click', function (evento) {
            const mensaje = enlace.getAttribute('data-confirm');
            if (!window.confirm(mensaje)) {
                evento.preventDefault();
            }
        });
    });
}

/**
 * Los mensajes flash (.alerta) desaparecen solos despues de unos segundos,
 * para que no se queden pegados en pantalla si el usuario no navega.
 */
function inicializarAutoDesaparicionDeAlertas() {
    document.querySelectorAll('.alerta').forEach(function (alerta) {
        setTimeout(function () {
            alerta.style.transition = 'opacity 0.6s ease';
            alerta.style.opacity = '0';
            setTimeout(function () {
                alerta.remove();
            }, 600);
        }, 4000);
    });
}

/**
 * Buscador en vivo para tablas de listado.
 * Requiere: <input class="buscador" data-tabla="idDeLaTabla" placeholder="Buscar...">
 * y la tabla correspondiente con ese id. Filtra por texto en cualquier columna.
 */
function inicializarBuscadorDeTablas() {
    document.querySelectorAll('.buscador').forEach(function (input) {
        const idTabla = input.getAttribute('data-tabla');
        const tabla = document.getElementById(idTabla);
        if (!tabla) {
            return;
        }

        input.addEventListener('input', function () {
            const texto = input.value.trim().toLowerCase();
            tabla.querySelectorAll('tbody tr').forEach(function (fila) {
                const coincide = fila.textContent.toLowerCase().includes(texto);
                fila.style.display = coincide ? '' : 'none';
            });
        });
    });
}

/**
 * Validacion de formularios en el cliente, ademas de la validacion required de HTML5
 * y de la validacion del lado del servidor (que sigue siendo la que de verdad protege
 * los datos -- esto es solo para mejor experiencia de usuario, avisando antes de enviar).
 */
function inicializarValidacionDeFormularios() {
    document.querySelectorAll('form').forEach(function (formulario) {
        formulario.addEventListener('submit', function (evento) {
            let esValido = true;

            formulario.querySelectorAll('[required]').forEach(function (campo) {
                quitarError(campo);
                if (campo.value.trim() === '') {
                    mostrarError(campo, 'Este campo es obligatorio.');
                    esValido = false;
                }
            });

            const campoPrecio = formulario.querySelector('#precio_compra');
            if (campoPrecio && campoPrecio.value !== '' && parseFloat(campoPrecio.value) <= 0) {
                mostrarError(campoPrecio, 'El precio debe ser mayor a cero.');
                esValido = false;
            }

            if (!esValido) {
                evento.preventDefault();
            }
        });
    });
}

function mostrarError(campo, mensaje) {
    campo.classList.add('campo-invalido');
    const error = document.createElement('span');
    error.className = 'error-campo';
    error.textContent = mensaje;
    campo.insertAdjacentElement('afterend', error);
}

function quitarError(campo) {
    campo.classList.remove('campo-invalido');
    const siguiente = campo.nextElementSibling;
    if (siguiente && siguiente.classList.contains('error-campo')) {
        siguiente.remove();
    }
}