<?php
include("conexion.php");
include("auth.php");
require_roles([1,2]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header("Location: docente.php");
	exit;
}

$idInscripcion = isset($_POST['id_inscripcion']) ? (int)$_POST['id_inscripcion'] : 0;

if ($idInscripcion <= 0 && isset($_POST['id'])) {
	$idInscripcion = (int)$_POST['id'];
}

$idAlumno = isset($_POST['id_alumno']) ? (int)$_POST['id_alumno'] : 0;
$idGmd = isset($_POST['id_gmd']) ? (int)$_POST['id_gmd'] : 0;
$calificacion = isset($_POST['calificacion']) ? (float)$_POST['calificacion'] : -1;

if ($calificacion < 0 || $calificacion > 100) {
	$mensaje = "La calificacion debe estar entre 0 y 100.";
	$tipo = "error";
	$alumnoNombre = '';
	$materiaNombre = '';
	$grupoNombre = '';
} else {
	$ok = false;
	$mensaje = "No se pudo guardar la calificacion.";
	$tipo = "error";
	$alumnoNombre = '';
	$materiaNombre = '';
	$grupoNombre = '';

	$conn->begin_transaction();

	if ($idInscripcion <= 0) {
		if ($idAlumno > 0 && $idGmd > 0) {
			$sqlBuscar = "SELECT id_inscripcion FROM inscripciones WHERE id_alumno = ? AND id_gmd = ? LIMIT 1";
			$stmtBuscar = $conn->prepare($sqlBuscar);

			if ($stmtBuscar) {
				$stmtBuscar->bind_param("ii", $idAlumno, $idGmd);
				if ($stmtBuscar->execute()) {
					$stmtBuscar->store_result();
					$stmtBuscar->bind_result($idInscripcionEncontrada);

					if ($stmtBuscar->fetch()) {
						$idInscripcion = (int)$idInscripcionEncontrada;
					}
				}

				$stmtBuscar->close();
			}

			if ($idInscripcion <= 0) {
				$sqlInsertInscripcion = "INSERT INTO inscripciones (id_alumno, id_gmd) VALUES (?, ?)";
				$stmtInsert = $conn->prepare($sqlInsertInscripcion);

				if ($stmtInsert) {
					$stmtInsert->bind_param("ii", $idAlumno, $idGmd);
					if ($stmtInsert->execute()) {
						$idInscripcion = (int)$conn->insert_id;
					}
					$stmtInsert->close();
				}
			}
		}
	}

	if ($idInscripcion > 0) {
		$sqlDetalle = "SELECT a.nombre AS alumno, m.nombre AS materia
		FROM inscripciones i
		INNER JOIN alumnos a ON i.id_alumno = a.id_alumno
		INNER JOIN grupo_materia_docente gmd ON i.id_gmd = gmd.id_gmd
		INNER JOIN materias m ON gmd.id_materia = m.id_materia
		WHERE i.id_inscripcion = ?
		LIMIT 1";
		$stmtDetalle = $conn->prepare($sqlDetalle);

		if ($stmtDetalle) {
			$stmtDetalle->bind_param("i", $idInscripcion);
			if ($stmtDetalle->execute()) {
				$stmtDetalle->store_result();
				$stmtDetalle->bind_result($alumnoNombre, $materiaNombre);
				$stmtDetalle->fetch();
				$grupoNombre = 'ID ' . $idInscripcion;
			}
			$stmtDetalle->close();
		}

		$sqlProc = "CALL sp_registrar_calificacion(?, ?)";
		$stmtProc = $conn->prepare($sqlProc);

		if ($stmtProc) {
			$stmtProc->bind_param("id", $idInscripcion, $calificacion);
			if ($stmtProc->execute()) {
				$ok = true;
				$tipo = "success";
				$mensaje = "Calificacion guardada correctamente.";
			}
			$stmtProc->close();

			while ($conn->more_results() && $conn->next_result()) {
				$extra = $conn->use_result();
				if ($extra instanceof mysqli_result) {
					$extra->free();
				}
			}
		}
	}

	if ($ok) {
		$conn->commit();
	} else {
		$conn->rollback();
	}
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Resultado del registro</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<main class="card narrow">
<h2>Resultado del registro</h2>
<p class="alert <?php echo $tipo; ?>"><?php echo htmlspecialchars($mensaje); ?></p>

<?php if ($alumnoNombre !== '' || $materiaNombre !== '') { ?>
<div class="summary-grid" style="margin-top:16px;">
<div class="summary-card">
<span class="summary-label">Alumno</span>
<span class="summary-value" style="font-size:1.15rem;"><?php echo htmlspecialchars($alumnoNombre !== '' ? $alumnoNombre : 'Sin dato'); ?></span>
</div>
<div class="summary-card">
<span class="summary-label">Materia</span>
<span class="summary-value" style="font-size:1.15rem;"><?php echo htmlspecialchars(trim($materiaNombre . ' ' . $grupoNombre)); ?></span>
</div>
<div class="summary-card">
<span class="summary-label">Calificacion</span>
<span class="summary-value" style="font-size:1.15rem;"><?php echo htmlspecialchars(number_format($calificacion, 2)); ?></span>
</div>
</div>
<?php } ?>

<div class="actions">
<a class="link" href="docente.php">Volver al panel docente</a>
</div>
</main>
</body>
</html>
