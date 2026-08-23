<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LabCheck</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">
    <header class="w-100 bg-secondary text-white text-center py-4">
        <h1 class="display-5 fw-bold">LabCheck</h1>
    </header>

    <main class="container d-flex flex-grow-1 justify-content-center align-items-center my-4">
        <section class="card shadow-sm p-4 w-100" style="max-width: 400px;">
            <div class="card-body">
                <h2 id="login-title" class="text-center h4 mb-3">Iniciar Sesión</h2>
                <p class="text-muted text-center small mb-4">Ingresa tus credenciales para acceder al sistema</p>

                <form id="form-login" action="../app/controlador/procesarLogin.php" method="POST">
                    <div class="mb-3">
                        <label for="ci" class="form-label">Cédula de Identidad</label>
                        <input type="text" class="form-control" id="ci" name="ci" placeholder="Ej: 12345678" required>
                    </div>

                    <div class="mb-3">
                        <label for="contrasena" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="contrasena" name="contrasena" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Ingresar</button>
                </form>
            </div>
        </section>
    </main>

    <footer class="w-100 bg-secondary text-white text-center py-3 mt-auto">
        <p class="mb-0 small">&copy; PeartoS.A - Todos los derechos reservados</p>
    </footer>
</body>
</html>