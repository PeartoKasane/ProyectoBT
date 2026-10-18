/*
    Carga inicial de salas, replicando la cantidad fija que existía en SelectSala.js:
    6 laboratorios y 3 talleres.
*/

INSERT INTO SALA (tipo, numero) VALUES
('Laboratorio', 1), ('Laboratorio', 2), ('Laboratorio', 3),
('Laboratorio', 4), ('Laboratorio', 5), ('Laboratorio', 6),
('Taller', 1), ('Taller', 2), ('Taller', 3);

-- Equipos de ejemplo para el Laboratorio 1 (id = 1, por ser la primera fila insertada)
INSERT INTO EQUIPO (numero, idSala, estado) VALUES
(1, 1, 'Funcionando'),
(2, 1, 'Funcionando'),
(3, 1, 'Funcionando');

-- El historial se completa solo, a medida que se dan de alta o se modifican equipos desde la aplicación.
-- No requiere carga inicial.
