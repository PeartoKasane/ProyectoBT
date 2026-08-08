<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Inicio de sesión</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">

    <link rel="stylesheet" href="assets/css/global.css">

    <link rel="stylesheet" href="assets/css/login.css">
</head>

<body id="inicio">

    <header class="barraNavegacion">
        <img src="assets/img/imagen_generica.png" alt="Logo de la empresa" class="logo">

        <h1>Todo en blanco</h1>

        <nav>
            <button class="btnMenu" id="btnMenu" type="button">
                <img src="assets/img/list.svg" alt="Abrir menú" class="iconoMenu">
            </button>

            <button class="btnCerrarMenu" id="btnCerrarMenu" type="button">
                <img src="assets/img/x.svg" alt="Cerrar menú" class="iconoMenu">
            </button>

            <ul class="listaNavegacion">
                <li>
                    <a href="index.html" class="btnNavegacion">
                        Inicio
                    </a>
                </li>

                <li>
                    <a href="sobreNosotros.html" class="btnNavegacion">
                        Sobre nosotros
                    </a>
                </li>

                <li>
                    <a href="contacto.html" class="btnNavegacion">
                        Contáctanos
                    </a>
                </li>

                <li>
                    <a href="trabajaConNosotros.html" class="btnNavegacion">
                        Trabaja con nosotros
                    </a>
                </li>

                <li>
                    <a href="login.php" class="btnNavegacion">
                        Ingresar al sistema
                    </a>
                </li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="seccionLogin">
            <h2>Ingreso al sistema</h2>

            <form action="procesarLogin.php" method="post">
                <fieldset>
                    <legend>Inicio de sesión</legend>

                    <div class="cajaEntradaDeDatos">
                        <label for="cedula">
                            Cédula
                        </label>

                        <input type="text" id="cedula" name="cedula" autocomplete="username" pattern="[1-9][0-9]{7}"
                            title="Ingrese exactamente 8 dígitos sin puntos ni guiones" inputmode="numeric"
                            maxlength="8" required>
                    </div>

                    <div class="cajaEntradaDeDatos">
                        <label for="clave">
                            Contraseña
                        </label>

                        <input type="password" id="clave" name="clave" autocomplete="current-password" minlength="12"
                            required>
                    </div>
                </fieldset>
                <?php
                    //htmlspecialchars: Convierte la información del parámetro a texto en bruto para que no pueda ser interpretado como un elemento HTML.
                    //Previene insercción de código malicioso como <script>codigoMalicioso()</script> y formatos accidentales
                    echo htmlspecialchars($_GET["error"] ?? "");
                ?>
                <button type="submit">
                    Iniciar sesión
                </button>
            </form>
        </section>

    </main>

    <a href="#inicio" class="btnSubir">
        <i class="bi bi-caret-up-fill"></i>
    </a>

    <footer>
        <address>
            <a href="http://instagram.com">
                @todoenblanco
            </a>

            <a href="tel:+45677373">
                +4567 7373
            </a>

            <a href="tel:098318897">
                098 318 897
            </a>

            <a href="mailto:todoenblanco@gmail.com">
                todoenblanco@gmail.com
            </a>
        </address>

        <p>© 2026 Todo en Blanco</p>
    </footer>

    <script src="assets/js/barraNavegacion.js"></script>
</body>

</html>