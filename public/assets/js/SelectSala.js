/*
  este archivo es la script para elegir la sala.
  aca va la logica del JS y no me gusta romperlo.
  pero por ahora sirve para que el sistema funcione.
*/

// Obtiene los elementos del formulario relacionados
// con la selección del tipo y número de sala.
const tipoSala = document.getElementById("tipo_sala");
const numeroSala = document.getElementById("num_sala");

// Actualiza las opciones disponibles para el número de sala
// según el tipo de sala seleccionado por el usuario.
function actualizarNumeroSala() {

    // Obtiene el tipo de sala seleccionado actualmente.
    const tipoSeleccionado = tipoSala.value;

    // Limpia las opciones anteriores para evitar
    // que se acumulen números de una selección previa.
    numeroSala.innerHTML = "";

    // Si no se seleccionó ningún tipo de sala,
    // mantiene deshabilitado el selector de número.
    if (tipoSeleccionado === "") {

        numeroSala.disabled = true;

        const opcionInicial =
            document.createElement("option");

        opcionInicial.value = "";
        opcionInicial.textContent =
            "-- Elija primero el tipo --";

        numeroSala.appendChild(opcionInicial);

        return;
    }

    // Habilita el selector porque ya existe
    // un tipo de sala seleccionado.
    numeroSala.disabled = false;

    // Determina cuántas salas existen según
    // el tipo seleccionado.
    let cantidadSalas = 0;

    if (tipoSeleccionado === "laboratorio") {
        cantidadSalas = 6;
    } else if (tipoSeleccionado === "taller") {
        cantidadSalas = 3;
    }

    // Crea la opción inicial del selector.
    const opcionInicial =
        document.createElement("option");

    opcionInicial.value = "";
    opcionInicial.textContent =
        "-- Seleccione Número --";

    numeroSala.appendChild(opcionInicial);

    // Genera dinámicamente las opciones de números
    // correspondientes al tipo de sala seleccionado.
    for (let i = 1; i <= cantidadSalas; i++) {

        const opcion =
            document.createElement("option");

        opcion.value = i;
        opcion.textContent = i;

        numeroSala.appendChild(opcion);
    }
}

// Ejecuta la actualización cada vez que el usuario
// cambia el tipo de sala.
tipoSala.addEventListener(
    "change",
    actualizarNumeroSala
);