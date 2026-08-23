<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador - SGRSI</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../public/assets/css/global.css">
    <link rel="stylesheet" href="../../public/assets/css/globalSistema.css">
    <link rel="stylesheet" href="../../public/assets/css/formularios.css">
</head>
<body id="inicio">
    <header class="barraNavegacion">
        <img src="../../public/assets/img/imagen_generica.png" alt="Logo de PeartoS.A" class="logo">
        <h1>SGRSI - Panel Administrador</h1>
        <nav>
            <button class="btnMenu" id="btnMenu" type="button">
                <img src="../../public/assets/img/list.svg" alt="Abrir menú" class="iconoMenu">
            </button>
            <button class="btnCerrarMenu" id="btnCerrarMenu" type="button">
                <img src="../../public/assets/img/x.svg" alt="Cerrar menú" class="iconoMenu">
            </button>
            <ul class="listaNavegacion">
                <li><a href="../../public/logout.php" class="btnNavegacion">Cerrar sesión</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="seccionTablaEmpleados">
            <header class="cajaEncabezado">
                <h2>Datos de empleados</h2>
                <button type="button" class="btnOperacion" id="btnAltaEmpleado">Alta de empleado</button>
            </header>

            <table>
                <caption>Listado de empleados registrados</caption>
                <thead>
                    <tr>
                        <th scope="col">Cédula</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Apellido</th>
                        <th scope="col">Rol</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody id="cuerpoTablaEmpleados">
                    <?php foreach ($usuarios as $usuario): ?>
                        <?php 
                            $roles = "";
                            if (isset($usuario["administrador"]) && $usuario["administrador"] == 1) {
                                $roles = "Administrador";
                            } else {
                                $roles = htmlspecialchars($usuario["rol"] ?? "Sin rol");
                            }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($usuario["cedula"]) ?></td>
                            <td><?= htmlspecialchars($usuario["nombre"]) ?></td>
                            <td><?= htmlspecialchars($usuario["apellido"]) ?></td>
                            <td><?= htmlspecialchars($roles) ?></td>
                            <td>
                                <div class="cajaOperaciones">
                                    <button type="button" class="btnOperacion btnModificar">Modificar</button>
                                    <button type="button" class="btnOperacion btnEliminar">Eliminar</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <dialog id="dialogGestionarEmpleado" class="dialogGestionarEmpleado seccionFormulario">
            <button class="btnCerrarGestionarEmpleado" id="btnCerrarGestionarEmpleado" type="button">
                <img src="../../public/assets/img/x.svg" alt="Cerrar formulario" class="iconoMenu">
            </button>
            <form action="procesarEmpleado.php" method="post" id="formularioGestionarEmpleado">
                <fieldset>
                    <legend>Gestión de empleado</legend>
                    <fieldset>
                        <legend>Datos del empleado</legend>
                        <div class="cajaEntradaDeDatos">
                            <label for="cedula">Cédula</label>
                            <input type="text" id="cedula" name="cedula" placeholder="Ingrese la cédula" autocomplete="off" pattern="[1-9][0-9]{7}" maxLength="8" required>
                        </div>
                        <div class="cajaEntradaDeDatos">
                            <label for="nombre">Nombre</label>
                            <input type="text" id="nombre" name="nombre" placeholder="Ingrese el nombre" autocomplete="given-name" required>
                        </div>
                        <div class="cajaEntradaDeDatos">
                            <label for="apellido">Apellido</label>
                            <input type="text" id="apellido" name="apellido" placeholder="Ingrese el apellido" autocomplete="family-name" required>
                        </div>
                        <div class="cajaEntradaDeDatos">
                            <label for="cargo">Cargo</label>
                            <select name="cargo" id="cargo" required>
                                <option value="" disabled selected>Seleccione un cargo</option>
                                <option value="Docente">Docente</option>
                                <option value="Dirección">Dirección</option>
                                <option value="Administrador">Administrador</option>
                            </select>
                        </div>
                    </fieldset>
                    <button type="submit">Guardar empleado</button>
                </fieldset>
            </form>
        </dialog>
    </main>

    <a href="#inicio" class="btnSubir"><i class="bi bi-caret-up-fill"></i></a>

    <footer>
        <p>Sistema de administración - SGRSI</p>
        <p>&copy; 2026 PeartoS.A</p>
    </footer>

    <script src="../../public/assets/js/barraNavegacion.js"></script>
    <script src="../../public/assets/js/gestionEmpleados.js"></script>
</body>
</html>