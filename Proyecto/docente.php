<?php
include("conexion.php");
include("auth.php");
require_roles([1,2]);

function clase_calificacion($valor)
{
	if (!is_numeric($valor)) {
		return "empty";
	}

	return ((float)$valor < 70) ? "fail" : "pass";
}

function clase_fila_calificacion($valor)
{
	if (!is_numeric($valor)) {
		return "";
	}

	return ((float)$valor < 70) ? "row-fail" : "row-pass";
}

$username = $_SESSION['username'] ?? '';
$idDocente = 0;

if ($username !== '') {
	$sqlDocente = "SELECT id_usuario FROM usuarios WHERE username = ? AND id_rol = 2 LIMIT 1";
	$stmtDocente = $conn->prepare($sqlDocente);

	if ($stmtDocente) {
		$stmtDocente->bind_param("s", $username);
		$stmtDocente->execute();
		$stmtDocente->store_result();
		$stmtDocente->bind_result($idUsuarioDocente);

		if ($stmtDocente->fetch()) {
			$idDocente = (int)$idUsuarioDocente;
		}

		$stmtDocente->close();
	}
}

$materiasAsignadas = [];
$sqlMaterias = "SELECT gmd.id_gmd, m.nombre AS materia
FROM grupo_materia_docente gmd
INNER JOIN materias m ON gmd.id_materia = m.id_materia
WHERE gmd.id_docente = " . (int)$idDocente . "
ORDER BY m.nombre";
$resultMaterias = $conn->query($sqlMaterias);

if ($resultMaterias) {
	while ($row = $resultMaterias->fetch_assoc()) {
		$materiasAsignadas[] = $row;
	}
}

$alumnosFormulario = [];
$sqlAlumnos = "SELECT id_alumno, nombre FROM alumnos ORDER BY nombre";
$resultAlumnos = $conn->query($sqlAlumnos);

if ($resultAlumnos) {
	while ($row = $resultAlumnos->fetch_assoc()) {
		$alumnosFormulario[] = $row;
	}
}

$gruposDocente = [];
$sqlGrupos = "SELECT gmd.id_gmd, m.nombre AS materia,
a.id_alumno, a.nombre AS alumno, i.id_inscripcion,
COALESCE(c.calificacion, 'Sin calificacion') AS calificacion
FROM grupo_materia_docente gmd
INNER JOIN materias m ON gmd.id_materia = m.id_materia
LEFT JOIN inscripciones i ON i.id_gmd = gmd.id_gmd
LEFT JOIN alumnos a ON i.id_alumno = a.id_alumno
LEFT JOIN calificaciones c ON i.id_inscripcion = c.id_inscripcion
WHERE gmd.id_docente = " . (int)$idDocente . "
ORDER BY m.nombre, a.nombre";
$resultGrupos = $conn->query($sqlGrupos);

if ($resultGrupos) {
	while ($row = $resultGrupos->fetch_assoc()) {
		$idGrupo = (int)$row['id_gmd'];

		if (!isset($gruposDocente[$idGrupo])) {
			$gruposDocente[$idGrupo] = [
				'id_gmd' => $idGrupo,
				'materia' => $row['materia'],
				'grupo' => 'N/A',
				'periodo' => '',
				'alumnos' => []
			];
		}

		if (!empty($row['id_alumno'])) {
			$gruposDocente[$idGrupo]['alumnos'][] = $row;
		}
	}
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel Docente</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>

<main class="page">
<div class="topbar">
<h1>Panel Docente</h1>
<div class="topbar-actions">
<span class="chip soft">Usuario: <?php echo htmlspecialchars($username !== '' ? $username : 'docente'); ?></span>
<a class="btn-ghost" href="logout.php">Cerrar sesion</a>
</div>
</div>

<section class="card form-shell">
<div class="section-head">
<div>
<h2>Docente | Materias y alumnos</h2>
<p class="subtitle">Consulta por materia, grupo y alumno antes de registrar calificaciones</p>
</div>
<span class="chip"><?php echo count($gruposDocente); ?> grupos asignados</span>
</div>

<div class="summary-grid">
<div class="summary-card">
<span class="summary-label">Materias asignadas</span>
<span class="summary-value"><?php echo count($materiasAsignadas); ?></span>
<p>Materias que actualmente imparte este docente.</p>
</div>
<div class="summary-card">
<span class="summary-label">Alumnos registrados</span>
<span class="summary-value"><?php echo count($alumnosFormulario); ?></span>
<p>Listado general disponible para registrar o actualizar calificaciones.</p>
</div>
</div>

<?php if (!empty($materiasAsignadas)) { ?>
<div class="chip-list">
<?php foreach ($materiasAsignadas as $materiaChip) { ?>
<span class="chip"><?php echo htmlspecialchars($materiaChip['materia']); ?></span>
<?php } ?>
</div>
<?php } else { ?>
<p class="alert error">No hay materias asignadas para este docente.</p>
<?php } ?>
</section>

<section class="card" style="margin-top:16px;">
<div class="section-head">
<div>
<h2>Registrar calificacion</h2>
<p class="subtitle">Selecciona la materia, el alumno y la nota a guardar</p>
</div>
</div>

<?php if (!empty($gruposDocente) && !empty($alumnosFormulario)) { ?>
<form action="guardar_calificacion.php" method="POST" class="form-grid two-col">
<div>
<label for="id_gmd">Materia / grupo</label>
<select id="id_gmd" name="id_gmd" required>
<option value="">Selecciona una materia</option>
<?php foreach ($gruposDocente as $grupo) { ?>
<option value="<?php echo (int)$grupo['id_gmd']; ?>"><?php echo htmlspecialchars($grupo['materia'] . ' (ID grupo-materia: ' . $grupo['id_gmd'] . ')'); ?></option>
<?php } ?>
</select>
<p class="form-note">Se guardará la calificación dentro de la materia seleccionada.</p>
</div>

<div>
<label for="id_alumno">Alumno</label>
<select id="id_alumno" name="id_alumno" required>
<option value="">Selecciona un alumno</option>
<?php foreach ($alumnosFormulario as $alumno) { ?>
<option value="<?php echo (int)$alumno['id_alumno']; ?>"><?php echo htmlspecialchars($alumno['nombre']); ?></option>
<?php } ?>
</select>
<p class="form-note">El sistema buscará la inscripción o la creará si todavía no existe.</p>
</div>

<div class="full-span">
<label for="calificacion">Calificacion</label>
<input id="calificacion" type="number" name="calificacion" min="0" max="100" step="0.01" required>
</div>

<div class="full-span">
<button type="submit">Guardar calificacion</button>
</div>
</form>
<?php } else { ?>
<p class="alert error">No hay datos suficientes para registrar calificaciones.</p>
<?php } ?>
</section>

<section class="card" style="margin-top:16px;">
<div class="section-head">
<div>
<h2>Alumnos por materia</h2>
<p class="subtitle">Clasificado por materia, grupo y alumno</p>
</div>
</div>

<?php if (!empty($gruposDocente)) { ?>
<div class="group-list">
<?php foreach ($gruposDocente as $grupo) { ?>
<article class="group-card">
<div class="group-head">
<div>
<h3><?php echo htmlspecialchars($grupo['materia']); ?></h3>
<p class="meta-line">ID grupo-materia: <?php echo htmlspecialchars((string)$grupo['id_gmd']); ?></p>
</div>
<span class="chip soft"><?php echo count($grupo['alumnos']); ?> alumnos</span>
</div>

<div class="table-wrap">
<table class="table-compact">
<tr>
<th>Alumno</th>
<th>ID Inscripcion</th>
<th>Calificacion</th>
</tr>
<?php if (!empty($grupo['alumnos'])) { ?>
<?php foreach ($grupo['alumnos'] as $alumnoGrupo) { ?>
<?php
$calificacionValor = $alumnoGrupo['calificacion'];
$claseScore = clase_calificacion($calificacionValor);
$claseFila = clase_fila_calificacion($calificacionValor);
$textoScore = is_numeric($calificacionValor)
	? number_format((float)$calificacionValor, 2)
	: (string)$calificacionValor;
?>
<tr class="<?php echo $claseFila; ?>">
<td><?php echo htmlspecialchars($alumnoGrupo['alumno']); ?></td>
<td><?php echo htmlspecialchars((string)$alumnoGrupo['id_inscripcion']); ?></td>
<td><span class="score-badge <?php echo $claseScore; ?>"><?php echo htmlspecialchars($textoScore); ?></span></td>
</tr>
<?php } ?>
<?php } else { ?>
<tr><td colspan="3">No hay alumnos inscritos en esta materia.</td></tr>
<?php } ?>
</table>
</div>
</article>
<?php } ?>
</div>
<?php } else { ?>
<p class="alert error">Este docente todavía no tiene materias o grupos asignados.</p>
<?php } ?>
</section>
</main>

</body>
</html>