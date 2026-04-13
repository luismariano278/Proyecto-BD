<?php
include("conexion.php");
include("auth.php");
require_roles([1]);

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