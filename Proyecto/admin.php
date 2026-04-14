<?php
include("conexion.php");
include("auth.php");
require_roles([1]);

$stats = [
'usuarios' => 0,
'docentes' => 0,
'alumnos' => 0,
'materias' => 0,
'inscripciones' => 0,
'calificaciones' => 0
];

$sqlStats = "SELECT
(SELECT COUNT(*) FROM usuarios) AS total_usuarios,
(SELECT COUNT(*) FROM usuarios WHERE id_rol = 2) AS total_docentes,
(SELECT COUNT(*) FROM alumnos) AS total_alumnos,
(SELECT COUNT(*) FROM materias) AS total_materias,
(SELECT COUNT(*) FROM inscripciones) AS total_inscripciones,
(SELECT COUNT(*) FROM calificaciones) AS total_calificaciones";
$resultStats = $conn->query($sqlStats);
if ($resultStats && $resultStats->num_rows > 0) {
$rowStats = $resultStats->fetch_assoc();
$stats['usuarios'] = (int)$rowStats['total_usuarios'];
$stats['docentes'] = (int)$rowStats['total_docentes'];
$stats['alumnos'] = (int)$rowStats['total_alumnos'];
$stats['materias'] = (int)$rowStats['total_materias'];
$stats['inscripciones'] = (int)$rowStats['total_inscripciones'];
$stats['calificaciones'] = (int)$rowStats['total_calificaciones'];
}

$sqlDocentes = "SELECT
u.id_usuario,
u.username,
COUNT(DISTINCT gmd.id_gmd) AS grupos_asignados,
COUNT(DISTINCT gmd.id_materia) AS materias_asignadas,
COUNT(DISTINCT i.id_alumno) AS alumnos_atendidos
FROM usuarios u
LEFT JOIN grupo_materia_docente gmd ON u.id_usuario = gmd.id_docente
LEFT JOIN inscripciones i ON gmd.id_gmd = i.id_gmd
WHERE u.id_rol = 2
GROUP BY u.id_usuario, u.username
ORDER BY u.username";
$resultDocentes = $conn->query($sqlDocentes);

$sqlAlumnosControl = "SELECT
a.id_alumno,
a.nombre,
u.username,
COUNT(DISTINCT i.id_inscripcion) AS materias_inscritas,
ROUND(AVG(c.calificacion), 2) AS promedio,
CASE
WHEN AVG(c.calificacion) IS NULL THEN 'Sin datos'
WHEN AVG(c.calificacion) >= 70 THEN 'Aprobado'
ELSE 'Reprobado'
END AS estatus
FROM alumnos a
INNER JOIN usuarios u ON a.id_usuario = u.id_usuario
LEFT JOIN inscripciones i ON a.id_alumno = i.id_alumno
LEFT JOIN calificaciones c ON i.id_inscripcion = c.id_inscripcion
GROUP BY a.id_alumno, a.nombre, u.username
ORDER BY a.nombre";
$resultAlumnosControl = $conn->query($sqlAlumnosControl);

$sqlVistaPromedios = "SELECT * FROM vista_promedios";
$resultVistaPromedios = $conn->query($sqlVistaPromedios);

$sqlPromedioGroup = "SELECT a.id_alumno, a.nombre, AVG(c.calificacion) AS promedio
FROM alumnos a
JOIN inscripciones i ON a.id_alumno = i.id_alumno
JOIN calificaciones c ON i.id_inscripcion = c.id_inscripcion
GROUP BY a.id_alumno, a.nombre";
$resultPromedioGroup = $conn->query($sqlPromedioGroup);

$sqlSobrePromedioGeneral = "SELECT *
FROM vista_promedios
WHERE promedio > (SELECT AVG(promedio) FROM vista_promedios)";
$resultSobrePromedioGeneral = $conn->query($sqlSobrePromedioGeneral);

$sqlReprobados = "SELECT *
FROM vista_promedios
WHERE promedio < 70";
$resultReprobados = $conn->query($sqlReprobados);

$resultDesempeno = false;
$idConsulta = isset($_GET['id_alumno']) ? (int)$_GET['id_alumno'] : 0;
if ($idConsulta > 0) {
$sqlDesempeno = "CALL sp_consultar_desempeno($idConsulta)";
$resultDesempeno = $conn->query($sqlDesempeno);
while ($conn->more_results() && $conn->next_result()) {
$extra = $conn->use_result();
if ($extra instanceof mysqli_result) {
$extra->free();
}
}
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel Admin</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>

<main class="page">
<div class="topbar">
<h1>Panel Admin</h1>
<div class="topbar-actions">
<span class="chip soft">Usuario: <?php echo htmlspecialchars($_SESSION['username'] ?? 'admin'); ?></span>
<a class="btn-ghost" href="logout.php">Cerrar sesion</a>
</div>
</div>

<section class="card">
<div class="section-head">
<div>
<h2>Control Total del Sistema</h2>
<p class="subtitle">Vista global de todo el sistema academico</p>
</div>
<span class="chip">Superusuario</span>
</div>

<div class="summary-grid">
<div class="summary-card"><span class="summary-label">Usuarios</span><span class="summary-value"><?php echo $stats['usuarios']; ?></span></div>
<div class="summary-card"><span class="summary-label">Docentes</span><span class="summary-value"><?php echo $stats['docentes']; ?></span></div>
<div class="summary-card"><span class="summary-label">Alumnos</span><span class="summary-value"><?php echo $stats['alumnos']; ?></span></div>
<div class="summary-card"><span class="summary-label">Materias</span><span class="summary-value"><?php echo $stats['materias']; ?></span></div>
<div class="summary-card"><span class="summary-label">Inscripciones</span><span class="summary-value"><?php echo $stats['inscripciones']; ?></span></div>
<div class="summary-card"><span class="summary-label">Calificaciones</span><span class="summary-value"><?php echo $stats['calificaciones']; ?></span></div>
</div>
</section>

<section class="card" style="margin-top:16px;">
<h2>Vista de Docentes</h2>
<p class="subtitle">Como administrador puedes ver carga academica y actividad docente</p>
<div class="table-wrap">
<table>
<tr>
<th>ID Docente</th>
<th>Usuario</th>
<th>Grupos</th>
<th>Materias</th>
<th>Alumnos atendidos</th>
</tr>
<?php if ($resultDocentes && $resultDocentes->num_rows > 0) { ?>
<?php while($doc=$resultDocentes->fetch_assoc()){ ?>
<tr>
<td><?php echo htmlspecialchars((string)$doc['id_usuario']); ?></td>
<td><?php echo htmlspecialchars($doc['username']); ?></td>
<td><?php echo htmlspecialchars((string)$doc['grupos_asignados']); ?></td>
<td><?php echo htmlspecialchars((string)$doc['materias_asignadas']); ?></td>
<td><?php echo htmlspecialchars((string)$doc['alumnos_atendidos']); ?></td>
</tr>
<?php } ?>
<?php } else { ?>
<tr><td colspan="5">No hay docentes registrados o no tienen grupos asignados.</td></tr>
<?php } ?>
</table>
</div>
</section>

<section class="card" style="margin-top:16px;">
<h2>Vista de Alumnos con Control Admin</h2>
<p class="subtitle">Informacion completa de cada alumno con acceso directo a su historial</p>
<div class="table-wrap">
<table>
<tr>
<th>ID Alumno</th>
<th>Nombre</th>
<th>Usuario</th>
<th>Materias</th>
<th>Promedio</th>
<th>Estatus</th>
<th>Accion</th>
</tr>
<?php if ($resultAlumnosControl && $resultAlumnosControl->num_rows > 0) { ?>
<?php while($al=$resultAlumnosControl->fetch_assoc()){ ?>
<?php
$estatusAl = strtolower((string)$al['estatus']);
$badgeClass = 'warn';
if (strpos($estatusAl, 'aprobad') !== false) { $badgeClass = 'ok'; }
if (strpos($estatusAl, 'reprobad') !== false) { $badgeClass = 'fail'; }
?>
<tr>
<td><?php echo htmlspecialchars((string)$al['id_alumno']); ?></td>
<td><?php echo htmlspecialchars($al['nombre']); ?></td>
<td><?php echo htmlspecialchars($al['username']); ?></td>
<td><?php echo htmlspecialchars((string)$al['materias_inscritas']); ?></td>
<td><?php echo $al['promedio'] !== null ? htmlspecialchars(number_format((float)$al['promedio'], 2)) : 'N/A'; ?></td>
<td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($al['estatus']); ?></span></td>
<td><a class="link" href="alumno.php?id_alumno=<?php echo urlencode((string)$al['id_alumno']); ?>">Ver historial</a></td>
</tr>
<?php } ?>
<?php } else { ?>
<tr><td colspan="7">No hay alumnos disponibles para mostrar.</td></tr>
<?php } ?>
</table>
</div>
</section>

<section class="card">
<h2>Panel Admin</h2>
<p class="subtitle">Resumen de promedios y estatus de alumnos</p>

<div class="table-wrap">
<table>

<tr>
<th>Alumno</th>
<th>Promedio</th>
<th>Estatus</th>
</tr>

<?php

if ($resultVistaPromedios) {
while($row=$resultVistaPromedios->fetch_assoc()){
?>

<tr>
<td><?php echo $row['nombre']; ?></td>
<td><?php echo $row['promedio']; ?></td>
<td>
<?php
$estatus = strtolower((string)$row['estatus']);
$clase = "warn";
if (strpos($estatus, "aprobad") !== false) {
$clase = "ok";
}
if (strpos($estatus, "reprobad") !== false) {
$clase = "fail";
}
?>
<span class="badge <?php echo $clase; ?>"><?php echo $row['estatus']; ?></span>
</td>
</tr>

<?php } ?>
<?php } ?>

</table>
</div>

</section>

<section class="card" style="margin-top:16px;">
<h2>Promedio por Alumno (AVG + GROUP BY)</h2>
<div class="table-wrap">
<table>
<tr>
<th>ID Alumno</th>
<th>Alumno</th>
<th>Promedio</th>
</tr>
<?php if ($resultPromedioGroup && $resultPromedioGroup->num_rows > 0) { ?>
<?php while($row=$resultPromedioGroup->fetch_assoc()){ ?>
<tr>
<td><?php echo $row['id_alumno']; ?></td>
<td><?php echo $row['nombre']; ?></td>
<td><?php echo number_format((float)$row['promedio'], 2); ?></td>
</tr>
<?php } ?>
<?php } else { ?>
<tr><td colspan="3">Sin datos para promedio por alumno.</td></tr>
<?php } ?>
</table>
</div>
</section>

<section class="card" style="margin-top:16px;">
<h2>Alumnos por Encima del Promedio General</h2>
<div class="table-wrap">
<table>
<tr>
<th>ID Alumno</th>
<th>Alumno</th>
<th>Promedio</th>
<th>Estatus</th>
</tr>
<?php if ($resultSobrePromedioGeneral && $resultSobrePromedioGeneral->num_rows > 0) { ?>
<?php while($row=$resultSobrePromedioGeneral->fetch_assoc()){ ?>
<tr>
<td><?php echo $row['id_alumno']; ?></td>
<td><?php echo $row['nombre']; ?></td>
<td><?php echo number_format((float)$row['promedio'], 2); ?></td>
<td><?php echo $row['estatus']; ?></td>
</tr>
<?php } ?>
<?php } else { ?>
<tr><td colspan="4">No hay alumnos por encima del promedio general.</td></tr>
<?php } ?>
</table>
</div>
</section>

<section class="card" style="margin-top:16px;">
<h2>Alumnos Reprobados (Promedio &lt; 70)</h2>
<div class="table-wrap">
<table>
<tr>
<th>ID Alumno</th>
<th>Alumno</th>
<th>Promedio</th>
<th>Estatus</th>
</tr>
<?php if ($resultReprobados && $resultReprobados->num_rows > 0) { ?>
<?php while($row=$resultReprobados->fetch_assoc()){ ?>
<tr>
<td><?php echo $row['id_alumno']; ?></td>
<td><?php echo $row['nombre']; ?></td>
<td><?php echo number_format((float)$row['promedio'], 2); ?></td>
<td><?php echo $row['estatus']; ?></td>
</tr>
<?php } ?>
<?php } else { ?>
<tr><td colspan="4">No hay alumnos reprobados.</td></tr>
<?php } ?>
</table>
</div>
</section>

<section class="card" style="margin-top:16px;">
<h2>Consultar Desempeno por Procedimiento</h2>
<form method="GET" class="form-grid">
<label for="id_alumno">ID Alumno</label>
<input id="id_alumno" type="number" name="id_alumno" min="1" required>
<button type="submit">Consultar</button>
</form>

<?php if ($idConsulta > 0) { ?>
<div class="table-wrap" style="margin-top:14px;">
<table>
<tr>
<th>Alumno</th>
<th>Promedio</th>
<th>Estatus</th>
</tr>
<?php if ($resultDesempeno && $resultDesempeno->num_rows > 0) { ?>
<?php while($row=$resultDesempeno->fetch_assoc()){ ?>
<tr>
<td><?php echo $row['nombre']; ?></td>
<td><?php echo number_format((float)$row['promedio'], 2); ?></td>
<td><?php echo $row['estatus']; ?></td>
</tr>
<?php } ?>
<?php } else { ?>
<tr><td colspan="3">No se encontro informacion para ese alumno.</td></tr>
<?php } ?>
</table>
</div>
<?php } ?>
</section>
</main>

</body>
</html>