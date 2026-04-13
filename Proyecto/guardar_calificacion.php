<?php
include("conexion.php");
include("auth.php");
require_roles([1,2]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
header("Location: docente.php");
exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$cal = isset($_POST['calificacion']) ? (float)$_POST['calificacion'] : -1;

if ($id <= 0 || $cal < 0 || $cal > 100) {
$mensaje = "Datos invalidos. Verifica ID y calificacion";
$tipo = "error";
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Resultado</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<main class="card narrow">
<h2>Resultado del registro</h2>
<p class="alert <?php echo $tipo; ?>"><?php echo $mensaje; ?></p>
<div class="actions">
<a class="link" href="docente.php">Volver al formulario</a>
</div>
</main>
</body>
</html>
<?php
exit;
}

$ok = false;

$sqlProc = "CALL sp_registrar_calificacion(?, ?)";
$stmtProc = $conn->prepare($sqlProc);
if ($stmtProc) {
$stmtProc->bind_param("id", $id, $cal);
$ok = $stmtProc->execute();
$stmtProc->close();
}

if (!$ok && $conn->errno === 1062) {
$sqlUpdate = "UPDATE calificaciones SET calificacion = ? WHERE id_inscripcion = ?";
$stmtUpdate = $conn->prepare($sqlUpdate);
if ($stmtUpdate) {
$stmtUpdate->bind_param("di", $cal, $id);
$ok = $stmtUpdate->execute();
$stmtUpdate->close();
}
}

$mensaje = $ok ? "Calificacion guardada correctamente" : "No se pudo guardar la calificacion";
$tipo = $ok ? "success" : "error";
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Resultado</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<main class="card narrow">
<h2>Resultado del registro</h2>
<p class="alert <?php echo $tipo; ?>"><?php echo $mensaje; ?></p>
<div class="actions">
<a class="link" href="docente.php">Registrar otra calificacion</a>
</div>
</main>
</body>
</html>
<?php

?>