# Gestión del repositorio Git

## Manejador utilizado

Para el control de versiones del proyecto se utiliza Git como sistema de control distribuido y GitHub como repositorio remoto.

La elección se debe a que permite mantener un historial de cambios, trabajar con ramas independientes y facilitar la colaboración entre integrantes del equipo.

## Convenciones de commits

Los mensajes de commit siguen una convención basada en el tipo de cambio realizado:
Los commits siguen la siguiente estructura: 

### feat: 
incorporación de nuevas funcionalidades.
### fix: 
corrección de errores.
### docs: 
modificaciones relacionadas con documentación.
### style: 
cambios de estilos o interfaz.
### refactor: 
reorganización del código sin modificar funcionalidades.
### chore: 
tareas de configuración o mantenimiento.


## Estrategia de ramas

Se utiliza una rama principal llamada main, destinada a contener versiones estables del proyecto.

Además se utilizan ramas de desarrollo separadas según las responsabilidades:

### frontend-js:
Contiene la implementación de HTML, CSS y JavaScript.

### documentacion-persistencia:
Contiene documentación del proyecto, configuración del repositorio y elementos relacionados con persistencia.

## Versionado

El proyecto utiliza etiquetas para identificar versiones importantes del sistema.
