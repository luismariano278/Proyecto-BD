-- Ejecutar como usuario con privilegios para crear usuarios/otorgar permisos.

-- Reemplaza estas contrasenas por las que quieras usar.
CREATE USER IF NOT EXISTS 'admin_app'@'localhost' IDENTIFIED BY 'Admin123$';
CREATE USER IF NOT EXISTS 'docente_app'@'localhost' IDENTIFIED BY 'Docente123$';
CREATE USER IF NOT EXISTS 'alumno_app'@'localhost' IDENTIFIED BY 'Alumno123$';

-- Limpieza de privilegios previos (opcional, pero recomendado)
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'admin_app'@'localhost';
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'docente_app'@'localhost';
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'alumno_app'@'localhost';

-- admin: todos los permisos sobre la base
GRANT ALL PRIVILEGES ON sistema_academico.* TO 'admin_app'@'localhost';

-- docente: SELECT alumnos + INSERT/UPDATE calificaciones
GRANT SELECT ON sistema_academico.alumnos TO 'docente_app'@'localhost';
GRANT SELECT, INSERT, UPDATE ON sistema_academico.calificaciones TO 'docente_app'@'localhost';

-- Si docente usa procedimiento almacenado para registrar calificacion:
-- GRANT EXECUTE ON PROCEDURE sistema_academico.sp_registrar_calificacion TO 'docente_app'@'localhost';

-- alumno: SELECT solo su informacion (recomendado via vista filtrada)
-- Ajusta la vista/tabla segun tu modelo real.
GRANT SELECT ON sistema_academico.vista_historial TO 'alumno_app'@'localhost';

FLUSH PRIVILEGES;
