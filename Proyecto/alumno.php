<?php
include("conexion.php");
include("auth.php");
require_roles([1,3]);

function clase_calificacion_alumno($valor)
{
if (!is_numeric($valor)) {
return "empty";
}

return ((float)$valor < 70) ? "fail" : "pass";
}

$id = 0;
if (current_role() === 3) {
$id = isset($_SESSION['id_alumno']) ? (int)$_SESSION['id_alumno'] : 0;
} else {
$id = isset($_GET['id_alumno']) ? (int)$_GET['id_alumno'] : 0;
}

$perfil = null;
$promedioGlobalAlumno = null;
$estatusGlobalAlumno = "Sin datos";

$sqlPerfil = "SELECT
a.id_alumno,
a.nombre,
u.username,
COUNT(DISTINCT i.id_inscripcion) AS materias_cursadas,
ROUND(AVG(c.calificacion), 2) AS promedio
FROM alumnos a
INNER JOIN usuarios u ON a.id_usuario = u.id_usuario
LEFT JOIN inscripciones i ON a.id_alumno = i.id_alumno
LEFT JOIN calificaciones c ON i.id_inscripcion = c.id_inscripcion
WHERE a.id_alumno = $id
GROUP BY a.id_alumno, a.nombre, u.username";

if ($id > 0) {
$resultPerfil = $conn->query($sqlPerfil);
if ($resultPerfil && $resultPerfil->num_rows > 0) {
$perfil = $resultPerfil->fetch_assoc();
$promedioGlobalAlumno = $perfil['promedio'];
if ($promedioGlobalAlumno !== null) {
$estatusGlobalAlumno = ((float)$promedioGlobalAlumno >= 70) ? "Aprobado" : "Reprobado";
}
}
}

$result = false;
if ($id > 0) {
$sql = "SELECT a.id_alumno, a.nombre, m.nombre AS materia, i.id_inscripcion, c.calificacion
FROM alumnos a
JOIN inscripciones i ON a.id_alumno = i.id_alumno
JOIN grupo_materia_docente gmd ON i.id_gmd = gmd.id_gmd
JOIN materias m ON gmd.id_materia = m.id_materia
JOIN calificaciones c ON i.id_inscripcion = c.id_inscripcion
WHERE a.id_alumno = $id";
$result = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Historial Academico</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>

<main class="page">
<div class="topbar">
<h1>Historial del Alumno</h1>
<div class="topbar-actions">
<span class="chip soft">Usuario: <?php echo htmlspecialchars($_SESSION['username'] ?? 'alumno'); ?></span>
<?php if (current_role() === 1) { ?>
<a class="link" href="admin.php">Volver al Dashboard Admin</a>
<?php } ?>
<a class="btn-ghost" href="logout.php">Cerrar sesion</a>
</div>
</div>

<?php if ($perfil) { ?>
<section class="card">
<div class="section-head">
<div>
<h2>Ficha del Alumno</h2>
<p class="subtitle">Informacion general y estatus academico actual</p>
</div>
<span class="chip soft">ID Alumno: <?php echo htmlspecialchars((string)$perfil['id_alumno']); ?></span>
</div>

<div class="summary-grid">
<div class="summary-card">
<span class="summary-label">Nombre</span>
<span class="summary-value" style="font-size:1.15rem;"><?php echo htmlspecialchars($perfil['nombre']); ?></span>
</div>
<div class="summary-card">
<span class="summary-label">Usuario</span>
<span class="summary-value" style="font-size:1.15rem;"><?php echo htmlspecialchars($perfil['username']); ?></span>
</div>
<div class="summary-card">
<span class="summary-label">Materias cursadas</span>
<span class="summary-value"><?php echo htmlspecialchars((string)$perfil['materias_cursadas']); ?></span>
</div>
<div class="summary-card">
<span class="summary-label">Promedio global</span>
<span class="summary-value"><?php echo $promedioGlobalAlumno !== null ? htmlspecialchars(number_format((float)$promedioGlobalAlumno, 2)) : "N/A"; ?></span>
<p><span class="badge <?php echo $estatusGlobalAlumno === 'Reprobado' ? 'fail' : 'ok'; ?>"><?php echo htmlspecialchars($estatusGlobalAlumno); ?></span></p>
</div>
</div>
</section>
<?php } ?>

<section class="card">
<h2>Historial Academico</h2>
<p class="subtitle">Consulta de materias y calificaciones registradas</p>

<div class="table-wrap">
<table>

<tr>
<th>Materia</th>
<th>ID Inscripcion</th>
<th>Calificacion</th>
<th>Estatus</th>
</tr>

<?php

if ($id <= 0) {
?>
<tr>
<td colspan="4">No se encontro tu identificador de alumno. Contacta al administrador.</td>
</tr>
<?php
} elseif (!$result) {
?>
<tr>
<td colspan="4">No se pudo cargar la informacion academica.</td>
</tr>
<?php
} else {
while($row=$result->fetch_assoc()){
?>

<tr>
<td><?php echo $row['materia']; ?></td>
<td><?php echo htmlspecialchars((string)$row['id_inscripcion']); ?></td>
<td>
<?php $claseCal = clase_calificacion_alumno($row['calificacion']); ?>
<span class="score-badge <?php echo $claseCal; ?>"><?php echo htmlspecialchars(number_format((float)$row['calificacion'], 2)); ?></span>
</td>
<td><span class="badge <?php echo $claseCal === 'fail' ? 'fail' : 'ok'; ?>"><?php echo $claseCal === 'fail' ? 'Reprobatoria' : 'Aprobatoria'; ?></span></td>
</tr>

<?php }
} ?>

</table>
</div>

</section>
</main>

</body>
</html>