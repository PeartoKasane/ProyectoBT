<?php
session_start();

// Simulamos la base de datos con tus credenciales reales
$usuarios_db = [
    [
        "ci" => "22222222",
        "password" => "admin1234",
        "nombre" => "Administrador",
        "rol" => "admin"
    ],
    [
        "ci" => "77777777",
        "password" => "docente1234",
        "nombre" => "Docente SGRSI",
        "rol" => "docente"
    ],
    [
        "ci" => "66666666",
        "password" => "direccion1234",
        "nombre" => "Director/a",
        "rol" => "direccion"
    ]
];

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ci_ingresada = trim($_POST["ci"]);
    $password_ingresada = trim($_POST["contrasena"]);

    $usuario_encontrado = null;

    foreach ($usuarios_db as $u) {
        if ($u["ci"] === $ci_ingresada && $u["password"] === $password_ingresada) {
            $usuario_encontrado = $u;
            break;
        }
    }

    if ($usuario_encontrado) {
        $_SESSION["usuario"] = $usuario_encontrado["nombre"];
        $_SESSION["rol"] = $usuario_encontrado["rol"];

        if ($usuario_encontrado["rol"] === "admin") {
            header("Location: admin_dashboard.php");
        } elseif ($usuario_encontrado["rol"] === "docente") {
            header("Location: docente.php");
        } elseif ($usuario_encontrado["rol"] === "direccion") {
            header("Location: direccion.php");
        }
        exit();
    } else {
        $error = "Cédula o contraseña incorrectas. Intente nuevamente.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGRSI - Iniciar Sesión</title>
    <link rel="stylesheet" href="assets/css/StyleLogin.css">
</head>
<body>
    <main class="login-container">
        <section class="login-box">
            <h2>SGRSI</h2>
            <p>Sistema de Gestión de Reportes de Salones de Informática</p>
            
            <?php if (!empty($error)): ?>
                <!-- Mensaje de error con estilos movidos a .alert-error-inline en assets/css/global.css -->
                <p role="alert" class="alert-error-inline">
                    <?php echo $error; ?>
                </p>
            <?php endif; ?>

            <form id="form-login" action="login.php" method="POST">
                <p class="input-group">
                    <label for="ci">Cédula de Identidad</label>
                    <input type="text" id="ci" name="ci" required placeholder="Ej: 12345678">
                </p>
                <p class="input-group">
                    <label for="contrasena">Contraseña</label>
                    <input type="password" id="contrasena" name="contrasena" required placeholder="••••••••">
                </p>
                <button type="submit" class="btn-submit">Ingresar</button>
            </form>
        </section>
    </main>
</body>
</html>