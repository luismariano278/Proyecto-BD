<?php
include("conexion.php");
include("auth.php");
require_roles([1,2]);

$sqlAlumnos = "SELECT * FROM alumnos";
$resultAlumnos = $conn->query($sqlAlumnos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrar Calificacion</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>

<main class="page">
<section class="card">
<h2>Docente | Alumnos</h2>
<p class="subtitle">Consulta de alumnos y captura de calificaciones</p>

<?php if ($resultAlumnos && $resultAlumnos->num_rows > 0) { ?>
<div class="table-wrap">
<table>
<tr>
<?php foreach ($resultAlumnos->fetch_fields() as $field) { ?>
<th><?php echo htmlspecialchars($field->name); ?></th>
<?php } ?>
</tr>

<?php while ($row = $resultAlumnos->fetch_assoc()) { ?>
<tr>
<?php foreach ($row as $value) { ?>
<td><?php echo htmlspecialchars((string)$value); ?></td>
<?php } ?>
</tr>
<?php } ?>
</table>
</div>
<?php } elseif ($resultAlumnos) { ?>
<p class="alert error">No hay alumnos registrados.</p>
<?php } else { ?>
<p class="alert error">No se pudo consultar la tabla de alumnos.</p>
<?php } ?>

</section>

<section class="card" style="margin-top:16px;">
<h2>Registrar Calificacion</h2>
<form action="guardar_calificacion.php" method="POST" class="form-grid">
<label for="id">ID Inscripcion</label>
<input id="id" type="number" name="id" min="1" required>

<label for="calificacion">Calificacion</label>
<input id="calificacion" type="number" name="calificacion" min="0" max="100" step="0.01" required>

<button type="submit">Guardar</button>
</form>
</section>
</main>

</body>
</html>