<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dirección - SGRSI</title>
    <link rel="stylesheet" href="../../public/assets/css/global.css">
    <link rel="stylesheet" href="../../public/assets/css/globalSistema.css">
</head>
<body id="inicio">
    <header class="barraNavegacion">
        <img src="../../public/assets/img/imagen_generica.png" alt="Logo de PeartoS.A" class="logo">
        <h1>SGRSI - Panel Dirección</h1>
        <nav>
            <ul class="listaNavegacion">
                <li><a href="../../public/logout.php" class="btnNavegacion">Cerrar sesión</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="seccionTablaEmpleados">
            <header class="cajaEncabezado">
                <h2>Resumen General de Personal</h2>
            </header>

            <table>
                <caption>Resumen institucional por rol</caption>
                <thead>
                    <tr>
                        <th scope="col">Cédula</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Apellido</th>
                        <th scope="col">Rol</th>
                    </tr>
                </thead>
                <tbody id="cuerpoTablaDireccion">
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?= htmlspecialchars($usuario["cedula"]) ?></td>
                            <td><?= htmlspecialchars($usuario["nombre"]) ?></td>
                            <td><?= htmlspecialchars($usuario["apellido"]) ?></td>
                            <td><?= htmlspecialchars($usuario["rol"]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>

    <footer>
        <p>Sistema de administración - SGRSI</p>
        <p>&copy; 2026 PeartoS.A</p>
    </footer>
</body>
</html>