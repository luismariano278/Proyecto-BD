-- SCRIPT COMPLETO DE SISTEMA ACADEMICO
-- Incluye:
-- CREATE
-- INSERT (20+ registros)
-- VISTAS
-- INDICES
-- FUNCIONES
-- PROCEDIMIENTOS
-- TRIGGERS
-- TRANSACCIONES
-- USUARIOS Y PERMISOS

CREATE DATABASE IF NOT EXISTS sistema_academico;
USE sistema_academico;

-- LIMPIEZA PREVIA DE OBJETOS
DROP VIEW IF EXISTS vista_promedios;
DROP VIEW IF EXISTS vista_historial;
DROP PROCEDURE IF EXISTS sp_consultar_desempeno;
DROP PROCEDURE IF EXISTS sp_registrar_calificacion;
DROP FUNCTION IF EXISTS fn_promedio_alumno;
DROP FUNCTION IF EXISTS fn_estatus_promedio;
DROP TRIGGER IF EXISTS trg_calificaciones_bi_validar;
DROP TRIGGER IF EXISTS trg_calificaciones_bu_validar;

-- CREATE: TABLAS BASE DEL MODELO
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    id_rol TINYINT NOT NULL,
    INDEX idx_usuarios_rol (id_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CREATE: TABLA DE ALUMNOS
CREATE TABLE IF NOT EXISTS alumnos (
    id_alumno INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    INDEX idx_alumnos_usuario (id_usuario),
    CONSTRAINT fk_alumnos_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CREATE: TABLA DE MATERIAS
CREATE TABLE IF NOT EXISTS materias (
    id_materia INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CREATE: TABLA DE RELACION GRUPO-MATERIA-DOCENTE
CREATE TABLE IF NOT EXISTS grupo_materia_docente (
    id_gmd INT AUTO_INCREMENT PRIMARY KEY,
    id_materia INT NOT NULL,
    id_docente INT NOT NULL,
    grupo VARCHAR(20) NOT NULL,
    periodo VARCHAR(20) NOT NULL,
    INDEX idx_gmd_materia (id_materia),
    INDEX idx_gmd_docente (id_docente),
    CONSTRAINT fk_gmd_materia
        FOREIGN KEY (id_materia) REFERENCES materias(id_materia)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_gmd_docente
        FOREIGN KEY (id_docente) REFERENCES usuarios(id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CREATE: TABLA DE INSCRIPCIONES
CREATE TABLE IF NOT EXISTS inscripciones (
    id_inscripcion INT AUTO_INCREMENT PRIMARY KEY,
    id_alumno INT NOT NULL,
    id_gmd INT NOT NULL,
    fecha_inscripcion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_inscripciones_alumno_gmd (id_alumno, id_gmd),
    INDEX idx_inscripciones_alumno (id_alumno),
    INDEX idx_inscripciones_gmd (id_gmd),
    CONSTRAINT fk_inscripciones_alumno
        FOREIGN KEY (id_alumno) REFERENCES alumnos(id_alumno)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_inscripciones_gmd
        FOREIGN KEY (id_gmd) REFERENCES grupo_materia_docente(id_gmd)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CREATE: TABLA DE CALIFICACIONES
CREATE TABLE IF NOT EXISTS calificaciones (
    id_inscripcion INT PRIMARY KEY,
    calificacion DECIMAL(5,2) NOT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_calificaciones_calificacion (calificacion),
    CONSTRAINT fk_calificaciones_inscripcion
        FOREIGN KEY (id_inscripcion) REFERENCES inscripciones(id_inscripcion)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- INSERT: USUARIOS (8 REGISTROS)
INSERT INTO usuarios (id_usuario, username, contrasena, id_rol) VALUES
(1, 'admin_app', 'Admin123$', 1),
(2, 'docente_app', 'Docente123$', 2),
(3, 'alumno1', 'Alumno123$', 3),
(4, 'alumno2', 'Alumno123$', 3),
(5, 'alumno3', 'Alumno123$', 3),
(6, 'alumno4', 'Alumno123$', 3),
(7, 'alumno5', 'Alumno123$', 3),
(8, 'alumno6', 'Alumno123$', 3);

-- INSERT: ALUMNOS (6 REGISTROS)
INSERT INTO alumnos (id_alumno, id_usuario, nombre) VALUES
(1, 3, 'Ana Lopez'),
(2, 4, 'Bruno Garcia'),
(3, 5, 'Carla Perez'),
(4, 6, 'Diego Ruiz'),
(5, 7, 'Elena Torres'),
(6, 8, 'Fernanda Diaz');

-- INSERT: MATERIAS (4 REGISTROS)
INSERT INTO materias (id_materia, nombre) VALUES
(1, 'Matematicas'),
(2, 'Programacion'),
(3, 'Base de Datos'),
(4, 'Redes');

-- INSERT: GRUPOS ASIGNADOS A MATERIA Y DOCENTE (4 REGISTROS)
INSERT INTO grupo_materia_docente (id_gmd, id_materia, id_docente, grupo, periodo) VALUES
(1, 1, 2, 'A', '2026-1'),
(2, 2, 2, 'A', '2026-1'),
(3, 3, 2, 'B', '2026-1'),
(4, 4, 2, 'B', '2026-1');

-- INSERT: INSCRIPCIONES (20 REGISTROS)
INSERT INTO inscripciones (id_inscripcion, id_alumno, id_gmd, fecha_inscripcion) VALUES
(1, 1, 1, '2026-01-10 08:00:00'),
(2, 1, 2, '2026-01-10 08:05:00'),
(3, 1, 3, '2026-01-10 08:10:00'),
(4, 1, 4, '2026-01-10 08:15:00'),
(5, 2, 1, '2026-01-11 08:00:00'),
(6, 2, 2, '2026-01-11 08:05:00'),
(7, 2, 3, '2026-01-11 08:10:00'),
(8, 2, 4, '2026-01-11 08:15:00'),
(9, 3, 1, '2026-01-12 08:00:00'),
(10, 3, 2, '2026-01-12 08:05:00'),
(11, 3, 3, '2026-01-12 08:10:00'),
(12, 4, 1, '2026-01-13 08:00:00'),
(13, 4, 2, '2026-01-13 08:05:00'),
(14, 4, 3, '2026-01-13 08:10:00'),
(15, 4, 4, '2026-01-13 08:15:00'),
(16, 5, 1, '2026-01-14 08:00:00'),
(17, 5, 2, '2026-01-14 08:05:00'),
(18, 5, 3, '2026-01-14 08:10:00'),
(19, 6, 1, '2026-01-15 08:00:00'),
(20, 6, 4, '2026-01-15 08:05:00');

-- INSERT: CALIFICACIONES (20 REGISTROS)
INSERT INTO calificaciones (id_inscripcion, calificacion, fecha_registro) VALUES
(1, 92.00, '2026-01-20 12:00:00'),
(2, 88.00, '2026-01-20 12:05:00'),
(3, 85.00, '2026-01-20 12:10:00'),
(4, 90.00, '2026-01-20 12:15:00'),
(5, 75.00, '2026-01-21 12:00:00'),
(6, 68.00, '2026-01-21 12:05:00'),
(7, 72.00, '2026-01-21 12:10:00'),
(8, 70.00, '2026-01-21 12:15:00'),
(9, 60.00, '2026-01-22 12:00:00'),
(10, 58.00, '2026-01-22 12:05:00'),
(11, 62.00, '2026-01-22 12:10:00'),
(12, 45.00, '2026-01-23 12:00:00'),
(13, 55.00, '2026-01-23 12:05:00'),
(14, 50.00, '2026-01-23 12:10:00'),
(15, 52.00, '2026-01-23 12:15:00'),
(16, 78.00, '2026-01-24 12:00:00'),
(17, 80.00, '2026-01-24 12:05:00'),
(18, 76.00, '2026-01-24 12:10:00'),
(19, 69.00, '2026-01-25 12:00:00'),
(20, 65.00, '2026-01-25 12:05:00');

-- FUNCIONES
DELIMITER $$

-- FUNCION 1: PROMEDIO DE UN ALUMNO
CREATE FUNCTION fn_promedio_alumno(p_id_alumno INT)
RETURNS DECIMAL(5,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_promedio DECIMAL(5,2);

    SELECT ROUND(AVG(c.calificacion), 2)
    INTO v_promedio
    FROM inscripciones i
    INNER JOIN calificaciones c ON i.id_inscripcion = c.id_inscripcion
    WHERE i.id_alumno = p_id_alumno;

    RETURN IFNULL(v_promedio, 0.00);
END$$

-- FUNCION 2: ESTATUS SEGUN EL PROMEDIO
CREATE FUNCTION fn_estatus_promedio(p_promedio DECIMAL(5,2))
RETURNS VARCHAR(20)
DETERMINISTIC
BEGIN
    RETURN CASE
        WHEN p_promedio >= 70 THEN 'Aprobado'
        ELSE 'Reprobado'
    END;
END$$

-- PROCEDIMIENTO 1: CONSULTA DE DESEMPENO DE UN ALUMNO
CREATE PROCEDURE sp_consultar_desempeno(IN p_id_alumno INT)
BEGIN
    SELECT
        a.id_alumno,
        a.nombre,
        fn_promedio_alumno(a.id_alumno) AS promedio,
        fn_estatus_promedio(fn_promedio_alumno(a.id_alumno)) AS estatus
    FROM alumnos a
    WHERE a.id_alumno = p_id_alumno;
END$$

-- PROCEDIMIENTO 2: REGISTRO O ACTUALIZACION DE CALIFICACION
CREATE PROCEDURE sp_registrar_calificacion(IN p_id_inscripcion INT, IN p_calificacion DECIMAL(5,2))
BEGIN
    INSERT INTO calificaciones (id_inscripcion, calificacion, fecha_registro)
    VALUES (p_id_inscripcion, p_calificacion, NOW())
    ON DUPLICATE KEY UPDATE
        calificacion = VALUES(calificacion),
        fecha_registro = VALUES(fecha_registro);
END$$

    -- TRIGGER 1: VALIDACION DE CALIFICACION ANTES DE INSERTAR
CREATE TRIGGER trg_calificaciones_bi_validar
BEFORE INSERT ON calificaciones
FOR EACH ROW
BEGIN
    IF NEW.calificacion < 0 OR NEW.calificacion > 100 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'La calificacion debe estar entre 0 y 100';
    END IF;
END$$

-- TRIGGER 2: VALIDACION DE CALIFICACION ANTES DE ACTUALIZAR
CREATE TRIGGER trg_calificaciones_bu_validar
BEFORE UPDATE ON calificaciones
FOR EACH ROW
BEGIN
    IF NEW.calificacion < 0 OR NEW.calificacion > 100 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'La calificacion debe estar entre 0 y 100';
    END IF;
END$$

DELIMITER ;

-- VISTA 1: PROMEDIOS Y ESTATUS DE ALUMNOS
CREATE OR REPLACE VIEW vista_promedios AS
SELECT
    a.id_alumno,
    a.nombre,
    ROUND(AVG(c.calificacion), 2) AS promedio,
    CASE
        WHEN AVG(c.calificacion) >= 70 THEN 'Aprobado'
        ELSE 'Reprobado'
    END AS estatus
FROM alumnos a
INNER JOIN inscripciones i ON a.id_alumno = i.id_alumno
INNER JOIN calificaciones c ON i.id_inscripcion = c.id_inscripcion
GROUP BY a.id_alumno, a.nombre;

-- VISTA 2: HISTORIAL ACADEMICO COMPLETO DEL ALUMNO
CREATE OR REPLACE VIEW vista_historial AS
SELECT
    a.id_alumno,
    a.nombre AS alumno,
    m.nombre AS materia,
    gmd.grupo,
    gmd.periodo,
    c.calificacion,
    i.fecha_inscripcion
FROM alumnos a
INNER JOIN inscripciones i ON a.id_alumno = i.id_alumno
INNER JOIN grupo_materia_docente gmd ON i.id_gmd = gmd.id_gmd
INNER JOIN materias m ON gmd.id_materia = m.id_materia
INNER JOIN calificaciones c ON i.id_inscripcion = c.id_inscripcion;

-- TRANSACCION DE EJEMPLO: ALTA DE INSCRIPCION + CALIFICACION
START TRANSACTION;
INSERT INTO inscripciones (id_alumno, id_gmd)
VALUES (6, 3);
SET @id_inscripcion_nueva = LAST_INSERT_ID();
INSERT INTO calificaciones (id_inscripcion, calificacion)
VALUES (@id_inscripcion_nueva, 80.00);
COMMIT;

-- USUARIOS Y PERMISOS
-- Este bloque se deja comentado porque requiere ejecutarse con un usuario administrador de MySQL.
-- CREATE USER IF NOT EXISTS 'admin_app'@'localhost' IDENTIFIED BY 'Admin123$';
-- CREATE USER IF NOT EXISTS 'docente_app'@'localhost' IDENTIFIED BY 'Docente123$';
-- CREATE USER IF NOT EXISTS 'alumno_app'@'localhost' IDENTIFIED BY 'Alumno123$';
-- GRANT ALL PRIVILEGES ON sistema_academico.* TO 'admin_app'@'localhost';
-- GRANT SELECT ON sistema_academico.alumnos TO 'docente_app'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON sistema_academico.calificaciones TO 'docente_app'@'localhost';
-- GRANT SELECT ON sistema_academico.vista_historial TO 'alumno_app'@'localhost';
