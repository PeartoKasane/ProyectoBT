# Requerimientos

## Propósito
El SGRSI será una aplicación web de tipo intranet orientada a la gestión de recursos tecnológicos, control de inventario y administración de incidencias dentro de la institución.
	Este documento define las funcionalidades principales, reglas de negocio, restricciones técnicas, interfaces y carácteristicas generales que deberá cumplir el software durante su desarrollo e implementación.


### El alcance de este incluye: 
- Gestión de inventario tecnológico.
- Gestión de préstamos y devoluciones de equipos.
- Registro y seguimiento de incidencias técnicas.
- Administración de solicitudes de preparaciones.
- Gestión de usuarios y roles.
- Generación de reportes y estadísticas.
- Control de acceso y seguridad básica.


La finalidad de nuestro sistema es mejorar la organización, trazabilidad y eficiencia de los procesos relacionados con soporte técnico y recursos informáticos dentro de la institución.












## Alcance del producto / Software
El SGRSI va a ser una aplicación web de tipo intranet dedicada a optimizar la gestión de recursos tecnológicos y las actividades de soporte técnico dentro de la institución.


## El sistema va a permitir: 
- Registrar y administrar equipos y recursos tecnológicos.
- Gestionar préstamos y devoluciones de
dispositivos.
- Registrar incidencias técnicas mediante tickets.
- Administrar solicitudes de servicio realizadas por
 docentes.
- Gestionar usuarios mediante roles y permisos.
- Generar reportes, estadísticas y métricas relacionadas al
uso de recursos e incidencias.
- Facillitar el seguimiento del estado de tickets y 
solicitudes.
- Mantener un historial de actividades realizadas.

El sistema va a estar orientado principalmente al personal de soporte técnico, coordinación, dirección y usuarios solicitantes de la institución.
    



## Requerimientos funcionales
### 1.Gestión de inventario tecnológico
-	Descripción:
- Esta funcionalidad permitirá registrar, administrar y consultar los recursos tecnológicos pertenecientes a la institución.

-	Prioridad: Alta

-	Acciones iniciadoras y comportamiento esperado:
El administrador o personal autorizado registrará equipos y recursos tecnológicos dentro del sistema. El sistema almacenará la información correspondiente y permitirá realizar modificaciones, consultas y actualizaciones posteriores.

####	Requerimientos funcionales:
##### RF1:
El sistema permitirá registrar equipos tecnológicos.
##### RF2:
El sistema permitirá modificar la información de los equipos registrados.
##### RF3:
El sistema le permitirá eliminar registros de equipos a administradores.
##### RF4:
El sistema permitirá consultar el inventario.
##### RF5:
El sistema deberá almacenar el historial de utilización y modificaciones.
##### RF6:
El sistema permitirá agregar o eliminar laboratorios.
##### RF7: 
El sistema permitirá asociar múltiples equipos a un laboratorio determinado.
##### RF8: 
El sistema deberá validar campos obligatorios antes de registrar un equipo.

### 2.Gestión de préstamos y devoluciones
-	Descripción:
- Esta funcionalidad permitirá administrar préstamos y devoluciones de equipos tecnológicos utilizados dentro de la institución.

-	Prioridad: Alta
	

- Acciones iniciadoras y comportamiento esperado:
El usuario autorizado va a registrar préstamos o devoluciones de equipos. El sistema almacenará la información correspondiente y actualizará el estado de disponibilidad del recurso.

####	Requerimientos funcionales:
##### RF9: 
El sistema permitirá registrar préstamos de equipos tecnológicos.
#### RF10: 
El sistema permitirá registrar devoluciones de equipos.
##### RF11: 
El sistema deberá registrar fecha y hora de inicio y finalización del préstamo.
##### RF12: 
El sistema deberá registrar la identificación del solicitante del préstamo.
##### RF13: 
El sistema permitirá utilizar distintos tipos de documento de identificación.
##### RF14: 
El sistema permitirá registrar la opción “No se usó” para determinados equipos.
##### RF15: 
El sistema permitirá seleccionar múltiples equipos en una misma operación.
##### RF16: 
El sistema deberá mantener un historial de préstamos y devoluciones realizados.

### 3.Gestión de incidencias y técnicas
-	Descripción:
Esta funcionalidad permitirá registrar, gestionar y realizar seguimiento de incidencias técnicas mediante tickets.

-	Prioridad: Alta

- Acciones iniciadoras y comportamiento esperado:
Los usuarios podrán registrar incidencias técnicas. El sistema generará un ticket y permitirá al personal técnico realizar seguimiento hasta su resolución.

####	Requerimientos funcionales:
##### RF17: 
El sistema permitirá registrar incidencias técnicas.
##### RF18: 
El sistema asignará un identificador único a cada ticket registrado.
##### RF19: 
El sistema permitirá clasificar incidencias según su gravedad.
##### RF20: 
El sistema permitirá actualizar el estado de los tickets.
##### RF21: 
El sistema manejará estados como pendiente, en proceso y resuelto.
##### RF22: 
Solamente usuarios autorizados podrán modificar el estado de los tickets.
##### RF23: 
El sistema permitirá registrar comentarios y diagnósticos a incidencias.
##### RF24: 
El sistema aumentará la prioridad de incidencias repetidas durante varias semanas.
##### RF25: 
El sistema aplicará filtros para evitar el ingreso de palabras ofensivas en formularios y comentarios.
##### RF26: 
El sistema permitirá consultar el historial completo de tickets registrados.

### 4.Gestión de solicitudes de servicio
-	Descripción:
- Esta funcionalidad permitirá administrar solicitudes relacionadas con soporte técnico y preparación de recursos tecnológicos.

-	Prioridad: Alta
-	Acciones iniciadoras y comportamiento esperado:
Los usuarios van a poder generar solicitudes de servicio que serán evaluadas y gestionadas por el personal técnico.
	
####	Requerimientos funcionales:
##### RF27: 
El sistema permitirá registrar solicitudes de servicio.
##### RF28: 
El sistema permitirá registrar solicitudes de preparación de laboratorios.
##### RF29: 
El sistema permitirá consultar el estado de las solicitudes realizadas.
##### RF30: 
El sistema permitirá asignar solicitudes al personal técnico correspondiente.
##### RF31: 
El sistema almacenará el historial de solicitudes y acciones realizadas.

### 5.Gestión de usuarios y roles
-	Descripción:
- Esta funcionalidad va permitir administrar usuarios, autenticación y permisos dentro del sistema.
-	Prioridad: Alta

-	Acciones iniciadoras y comportamiento esperado:
Los administradores van a poder gestionar cuentas y permisos de acceso según el rol de cada usuario.




####	Requerimientos funcionales:
##### RF32: 
El sistema permitirá autenticación.
##### RF33: 
El sistema denegará el acceso a usuarios no autenticados.
##### RF34: 
El sistema permitirá gestionar usuarios y roles.
##### RF35: 
El sistema permitirá asignar permisos según el tipo de usuario.
##### RF36: 
El sistema permitirá habilitar o deshabilitar cuentas de usuario.
##### RF37: 
El sistema registrará el estado de autenticación del usuario.
##### RF38:
El sistema permitirá a administradores modificar permisos y configuraciones.

#### 6.Generación de reportes y estadísticas
-	Descripción:
- Esta funcionalidad permitirá generar información estadística y reportes relacionados con el funcionamiento del sistema.
-	Prioridad: Media

-	Acciones iniciadoras y comportamiento esperado:
Los usuarios autorizados podrán consultar reportes y métricas generadas automáticamente por el sistema.
	




#### Requerimientos funcionales:
##### RF39: 
El sistema permitirá generar reportes sobre incidencias técnicas.
##### RF40: 
El sistema permitirá generar estadísticas relacionadas con uso de equipos y recursos tecnológicos.
##### RF41:
El sistema permitirá consultar métricas relacionadas con préstamos y devoluciones.
##### RF42: 
El sistema permitirá visualizar información resumida para apoyo en la toma de decisiones.
##### RF43: 
El sistema permitirá filtrar reportes.





## Requerimientos no funcionales
### 1.Seguridad
#### RNF1: 
El sistema deberá requerir autenticación.
#### RNF2: 
El sistema deberá restringir funcionalidades según los permisos. 
#### RNF3: 
El sistema deberá proteger la información almacenada contra accesos no autorizados.
#### RNF4: 
El sistema deberá mantener registro de actividades realizadas por los usuarios para asegurar trazabilidad.
#### RNF5: 
El servidor deberá contar con firewall configurado para permitir únicamente los servicios necesarios.
#### RNF6:
El sistema deberá permitir la realización periódica de respaldos de información.
#### RNF7: 
Las conexiones de administración remota deberán realizarse mediante SSH.


### 2.Usabilidad
#### RNF8: 
La interfaz del sistema deberá ser intuitiva y de fácil utilización para usuarios con conocimientos informáticos básicos.
#### RNF9: 
El sistema deberá mantener una estructura visual clara y organizada.
#### RNF10: 
El sistema deberá mostrar mensajes de validación y error comprensibles para el usuario.
#### RNF11: 
La navegación entre módulos deberá ser simple y consistente.
#### RNF12: 
El sistema deberá encontrarse disponible en idioma español e inglés.


### 3.Rendimiento
#### RNF13: 
El sistema deberá responder adecuadamente ante múltiples consultas simultáneas dentro del entorno institucional.
#### RNF14: 
El tiempo de carga de las páginas deberá ser razonable para uso interno en red local.
#### RNF15: 
El sistema deberá optimizar el uso de recursos del servidor para mantener estabilidad operativa.
#### RNF16: 
La base de datos deberá permitir consultas eficientes sobre inventario, incidencias y préstamos.




### 4.Disponibilidad
#### RNF17: 
El sistema deberá encontrarse disponible durante el horario operativo institucional.
#### RNF18: 
El servidor deberá mantener funcionamiento continuo y estable para evitar interrupciones frecuentes.
#### RNF19: 
Los servicios principales del sistema deberán iniciarse automáticamente junto con el sistema operativo.


### 5.Mantenibilidad
#### RNF20: 
El sistema deberá desarrollarse de forma modular para facilitar futuras modificaciones y ampliaciones.
#### RNF21: 
El código fuente deberá mantenerse organizado y documentado.
#### RNF22: 
La estructura del sistema deberá permitir agregar nuevos laboratorios, equipos y funcionalidades en futuras versiones.
#### RNF23: 
El sistema deberá facilitar tareas de mantenimiento y administración del servidor.


### 6.Compatibilidad
#### RNF24: 
El sistema deberá ser compatible con navegadores web modernos.
#### RNF25: 
El sistema deberá funcionar correctamente sobre Debian GNU/Linux en el entorno servidor definido para el proyecto.
#### RNF26: 
El sistema deberá ser accesible desde equipos conectados a la red institucional mediante protocolo HTTP/HTTPS.


### 7.Escalabilidad
#### RNF27: 
El sistema deberá permitir crecimiento futuro en cantidad de usuarios, equipos y registros almacenados.
#### RNF28: 
La arquitectura deberá permitir incorporación de nuevos módulos o funcionalidades sin afectar significativamente el funcionamiento existente.
#### RNF29: 
El sistema deberá soportar futuras mejoras relacionadas con reportes, estadísticas y administración de recursos tecnológicos.
#### RNF30: 
El sistema deberá contar con un manual de usuario que explique el funcionamiento general de la plataforma, incluyendo acceso, navegación y utilización de sus principales funcionalidades.



