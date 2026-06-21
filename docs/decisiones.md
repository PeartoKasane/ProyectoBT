# Decisiones técnicas del proyecto

## Framework CSS utilizado

Para el desarrollo de la interfaz se utilizará Bootstrap como framework CSS.

La elección se basa en que nos permite acelerar el proceso de diseño mediante componentes reutilizables y herramientas orientadas al desarrollo responsive.

Además, facilita la implementación del enfoque responsive solicitado, permitiendo adaptar la interfaz a distintosr tamaños de pantalla.

## Organización por capas

El proyecto se organiza separando las responsabilidades principales:

### Capa de pre:
Encargada de la interfaz visual mediante HTML5 y CSS3.

### Capa de lógica:
Encargada de la interacción con el usuario mediante JavaScript, incluyendo eventos y validaciones.

### Capa de persistencia:
Representada por ahora mediante el modelo entidad-relación, donde se definen las entidades, atributos y relaciones necesarias para la gestión de datos.

### capa de documentación
Contiene documentación complementaria, incluye configuración del repositorio, decisiones técnicas, enlaces y documentación necesaria para el desarrollo.

El proyecto se encuentra organizado mediante una separación lógica de responsabilidades. Los recursos de interfaz se encuentran dentro de la carpeta assets, separando estilos CSS y lógica JavaScript. La carpeta database contiene la definición del modelo de persistencia, mientras que docs tiene la documentación técnica y administrativa del desarrollo.

## Diseño responsive

La interfaz va a ser desarrollada siguiendo el enfoque mobile first, comenzando por dispositivos de menor tamaño y agregando adaptaciones progresivas para pantallas más grandes.

Se consideran principalmente:

- Dispositivos móviles hasta 576px.
- Tabletas hasta 768px.

## Control de versiones

El proyecto utiliza Git como sistema de control de versiones y GitHub como repositorio remoto.

Esto permite mantener un historial de cambios, trabajar mediante ramas independientes y registrar versiones estables mediante etiquetas.

