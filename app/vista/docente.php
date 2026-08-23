<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Docente - SGRSI</title>
    <link rel="stylesheet" href="../../public/assets/css/global.css">
    <link rel="stylesheet" href="../../public/assets/css/globalSistema.css">
</head>
<body id="inicio">
    <header class="barraNavegacion">
        <img src="../../public/assets/img/imagen_generica.png" alt="Logo de PeartoS.A" class="logo">
        <h1>SGRSI - Panel Docente</h1>
        <nav>
            <ul class="listaNavegacion">
                <li><a href="../../public/logout.php" class="btnNavegacion">Cerrar sesión</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="seccionTablaEmpleados">
            <header class="cajaEncabezado">
                <h2>Mis Clases y Horarios</h2>
            </header>

            <table>
                <caption>Lista de materias asignadas</caption>
                <thead>
                    <tr>
                        <th scope="col">Materia</th>
                        <th scope="col">Grupo</th>
                        <th scope="col">Horario</th>
                    </tr>
                </thead>
                <tbody id="cuerpoTablaDocente">
                    <?php if (!empty($clases)): ?>
                        <?php foreach ($clases as $clase): ?>
                            <tr>
                                <td><?= htmlspecialchars($clase["materia"]) ?></td>
                                <td><?= htmlspecialchars($clase["grupo"]) ?></td>
                                <td><?= htmlspecialchars($clase["horario"]) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3">No hay materias asignadas actualmente.</td>
                        </tr>
                    <?php endif; ?>
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