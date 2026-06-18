function Type() {
    const tipoSala = document.getElementById("tipo_sala").value;
    const numSala = document.getElementById("num_sala");

    numSala.innerHTML = '<option value="">-- Seleccione Número --</option>';

    if (tipoSala === "laboratorio") {
        numSala.disabled = false;
        for (let i = 1; i <= 6; i++) {
            numSala.innerHTML += `<option value="${i}">${i}</option>`;
        }
    } else if (tipoSala === "taller") {
        numSala.disabled = false;
        for (let i = 1; i <= 3; i++) {
            numSala.innerHTML += `<option value="${i}">${i}</option>`;
        }
    } else {
        numSala.disabled = true;
        numSala.innerHTML = '<option value="">-- Elija primero el tipo --</option>';
    }
}