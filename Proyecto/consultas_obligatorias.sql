-- CONSULTAS OBLIGATORIAS
-- Este archivo contiene las consultas que consumen los objetos creados en el script completo.

-- CONSULTA 1: Historial academico de un alumno (JOIN alumnos, materias, calificaciones)
SELECT
a.id_alumno,
a.nombre,
m.nombre AS materia,
c.calificacion
FROM alumnos a
JOIN inscripciones i ON a.id_alumno = i.id_alumno
JOIN grupo_materia_docente gmd ON i.id_gmd = gmd.id_gmd
JOIN materias m ON gmd.id_materia = m.id_materia
JOIN calificaciones c ON i.id_inscripcion = c.id_inscripcion
WHERE a.id_alumno = 1;

-- CONSULTA 2: Promedio por alumno (AVG + GROUP BY)
SELECT
a.id_alumno,
a.nombre,
AVG(c.calificacion) AS promedio
FROM alumnos a
JOIN inscripciones i ON a.id_alumno = i.id_alumno
JOIN calificaciones c ON i.id_inscripcion = c.id_inscripcion
GROUP BY a.id_alumno, a.nombre;

-- SUBCONSULTA 1: Alumnos con promedio mayor al promedio general
-- Requiere la vista vista_promedios definida en el script completo.
SELECT *
FROM vista_promedios
WHERE promedio > (SELECT AVG(promedio) FROM vista_promedios);

-- VISTA 1: Alumnos reprobados usando la vista vista_promedios
SELECT *
FROM vista_promedios
WHERE promedio < 70;

-- PROCEDIMIENTO 1: Consulta de desempeno por alumno
CALL sp_consultar_desempeno(1);

-- PROCEDIMIENTO 2: Registro de calificacion
CALL sp_registrar_calificacion(1, 95.50);

-- TRANSACCION: proceso de inscripcion
START TRANSACTION;

INSERT INTO inscripciones(id_alumno, id_gmd)
VALUES (1, 2);

SET @id_inscripcion_nueva = LAST_INSERT_ID();

INSERT INTO calificaciones(id_inscripcion, calificacion)
VALUES (@id_inscripcion_nueva, 80);

COMMIT;

-- Si algo falla durante el proceso, usar:
-- ROLLBACK;
