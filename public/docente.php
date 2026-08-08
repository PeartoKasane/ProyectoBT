<?php
session_start();

if (!isset($_SESSION["usuario"]) || $_SESSION["rol"] !== "docente") {
    header("Location: login.php");
    exit();
}

$nombre_docente = $_SESSION["usuario"];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGRSI - Panel Docente</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <header>
        <h1>Panel de Docentes - SGRSI</h1>
        <nav class="user-info">
            <span>Bienvenido, <strong><?php echo htmlspecialchars($nombre_docente); ?></strong></span> | 
            <a href="logout.php" style="color: #ff4d4d; text-decoration: none; font-weight: bold;">Cerrar Sesión</a>
        </nav>
    </header>
    <main>
        <p>Este es un espacio protegido. Solo visible por Docentes autorizados.</p>
    </main>
</body>
</html>