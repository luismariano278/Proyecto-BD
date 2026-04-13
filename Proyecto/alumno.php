<?php
include("conexion.php");
include("auth.php");
require_roles([1,3]);

$id = 0;
if (current_role() === 3) {
$id = isset($_SESSION['id_alumno']) ? (int)$_SESSION['id_alumno'] : 0;
} else {
$id = isset($_GET['id_alumno']) ? (int)$_GET['id_alumno'] : 0;
}

$result = false;
if ($id > 0) {
$sql = "SELECT a.id_alumno, a.nombre, m.nombre AS materia, c.calificacion
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
<section class="card">
<h2>Historial Academico</h2>
<p class="subtitle">Consulta de materias y calificaciones registradas</p>

<div class="table-wrap">
<table>

<tr>
<th>Materia</th>
<th>Calificacion</th>
</tr>

<?php

if ($id <= 0) {
?>
<tr>
<td colspan="2">No se encontro tu identificador de alumno. Contacta al administrador.</td>
</tr>
<?php
} elseif (!$result) {
?>
<tr>
<td colspan="2">No se pudo cargar la informacion academica.</td>
</tr>
<?php
} else {
while($row=$result->fetch_assoc()){
?>

<tr>
<td><?php echo $row['materia']; ?></td>
<td><?php echo $row['calificacion']; ?></td>
</tr>

<?php }
} ?>

</table>
</div>

</section>
</main>

</body>
</html>