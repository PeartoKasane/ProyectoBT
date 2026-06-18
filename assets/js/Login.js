document.getElementById("form-login").addEventListener("submit", function(event) {
    event.preventDefault();

    const ci = document.getElementById("ci").value.trim();
    const contrasena = document.getElementById("contrasena").value.trim();

    if (ci === "22222222" && contrasena === "admin1234") {
        window.location.href = "Admin.html";
    } else if (ci === "77777777" && contrasena === "docente1234") {
        window.location.href = "Docente.html";
        } else if (ci === "66666666" && contrasena === "direccion1234") {
        window.location.href = "Direccion.html";
    } else {
        alert("Cédula o contraseña incorrectas. Intente nuevamente.");
    }
});