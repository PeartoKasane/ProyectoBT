const checkMostrar = document.getElementById('verPass');
const inputPass = document.getElementById('passAdmin');
const inputCedula = document.getElementById('cedulaAdmin');

// Lógica para mostrar/ocultar contraseña (Checkbox)
checkMostrar.addEventListener('change', function() {
    if (this.checked) {
        inputPass.type = "text";
    } else {
        inputPass.type = "password";
    }
});

// Lógica de inicio de sesión
document.getElementById('formLogin').addEventListener('submit', function(event) {
    // Esto es lo que frena que la página avance sin preguntar
    event.preventDefault(); 

    const cedulaIngresada = inputCedula.value;
    const passIngresada = inputPass.value;
    
    const cedulaCorrecta = "22222222";
    const passCorrecta = "admin1234"; 

    // Verificamos si los datos son correctos
    if (cedulaIngresada === cedulaCorrecta && passIngresada === passCorrecta) {
        
        // Si todo está bien, lo enviamos a la página del administrador
        window.location.href = "administrador.html";
        
    } else {
        // Si se equivoca, le avisamos y no avanza
        alert("Cédula o contraseña incorrecta.");
    }
});