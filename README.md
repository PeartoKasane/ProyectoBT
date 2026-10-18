# SGRSI
## Sistema de Gestión y Recursos de Soporte Informático.

## Descripción

SGRSI es un sistema orientado a la gestión de recursos y soporte informático, permitiendo administrar incidencias, usuarios y recursos tecnológicos.

## Tecnologías utilizadas

### Frontend
- HTML5
- CSS3
- JavaScript
- Bootstrap

### Backend
- PHP (PDO)
- Composer (vlucas/phpdotenv para variables de entorno)

### Persistencia
- MySQL / Modelo Entidad-Relación

### Control de versiones
- Git
- GitHub

## Puesta en marcha del módulo de usuarios

1. Copiar `.env.example` a `.env` y completar las credenciales de la base de datos.
2. Ejecutar `baseDeDatos/schema.sql` contra el servidor MySQL para crear la base `PeartoS.A` con todas sus tablas
   (`USUARIO`, `ADMINISTRADOR`, `DOCENTE`, `DIRECCION`, `TECNICO`, `SALA`, `EQUIPO`, `HISTORIAL_EQUIPO`, `TICKET`,
   `TICKET_EQUIPO`, `TICKET_COMENTARIO`, `SOLICITUD`, `SOLICITUD_COMENTARIO`, `PRESTAMO` y `PRESTAMO_EQUIPO`) y cargar los datos de prueba.
3. Apuntar el document root del servidor web a la carpeta `public/`.
4. Ingresar desde `public/paginas/login.html` (`public/index.php` redirige ahí). `public/` solo contiene HTML y el entrypoint `index.php`, que atiende la API con `?ruta=`.

## Integrantes

- Coordinador: Ismael Terra
- Subcoordinador: Francisco Vázquez

## Documentación

La documentación del proyecto se encuentra organizada en la carpeta `docs`.

Incluye:

- `requerimientos.md`: requerimientos funcionales y no funcionales del sistema.
- `decisiones.md`: decisiones técnicas tomadas durante el desarrollo.
- `api.md`: endpoints de la API, formato de comunicación y estructura backend.
- `manualUsuario.md`, `permisos.md` e `infraestructura.md`: manual de usuario (RNF30), matriz de permisos por rol y guía de configuración del servidor.
- `persistencia.md`: lista de consultas, álgebra relacional, DML y cobertura CRUD por tabla.
- `git.md`: convenciones de commits, estrategia de ramas y gestión del repositorio.
