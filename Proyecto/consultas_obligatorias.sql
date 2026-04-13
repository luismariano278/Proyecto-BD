-- CONSULTAS OBLIGATORIAS

-- 1) Historial academico de un alumno (JOIN alumnos, materias, calificaciones)
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

-- 2) Promedio por alumno (AVG + GROUP BY)
SELECT
a.id_alumno,
a.nombre,
AVG(c.calificacion) AS promedio
FROM alumnos a
JOIN inscripciones i ON a.id_alumno = i.id_alumno
JOIN calificaciones c ON i.id_inscripcion = c.id_inscripcion
GROUP BY a.id_alumno, a.nombre;

-- 3) Alumnos con promedio mayor al promedio general (subconsulta en WHERE)
SELECT *
FROM vista_promedios
WHERE promedio > (SELECT AVG(promedio) FROM vista_promedios);

-- 4) Alumnos reprobados (promedio < 70)
SELECT *
FROM vista_promedios
WHERE promedio < 70;

-- PROCEDIMIENTOS
CALL sp_consultar_desempeno(1);
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
