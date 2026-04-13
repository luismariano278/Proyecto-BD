<?php
include("conexion.php");
include("auth.php");

function buscar_id_alumno($conn, $username)
{
$sql = "SELECT a.id_alumno
FROM alumnos a
JOIN usuarios u ON a.id_usuario = u.id_usuario
WHERE u.username = ?
LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
return null;
}

$stmt->bind_param("s", $username);
if (!$stmt->execute()) {
$stmt->close();
return null;
}

$stmt->store_result();
if ($stmt->num_rows > 0) {
$stmt->bind_result($idAlumno);
$stmt->fetch();
$stmt->close();
return (int)$idAlumno;
}

$stmt->close();
return null;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
header("Location: login.html");
exit;
}

$user = $_POST['username'] ?? '';
$pass = $_POST['password'] ?? '';

$sql = "SELECT id_rol FROM usuarios WHERE username = ? AND contrasena = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
$stmt->bind_param("ss", $user, $pass);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($rol);
$tieneUsuario = $stmt->fetch();
} else {
$tieneUsuario = false;
}

if ($tieneUsuario) {
session_regenerate_id(true);
$_SESSION['rol'] = (int)$rol;
$_SESSION['username'] = $user;

if ((int)$rol === 3) {
$idAlumno = buscar_id_alumno($conn, $user);
if ($idAlumno !== null && $idAlumno > 0) {
$_SESSION['id_alumno'] = $idAlumno;
}
}

if ($rol == 1) {
header("Location: admin.php");
exit;
}

if ($rol == 2) {
header("Location: docente.php");
exit;
}

if ($rol == 3) {
header("Location: alumno.php");
exit;
}

echo "Rol no reconocido";
exit;

} else {
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Acceso denegado</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<main class="card narrow">
<h2>No se pudo iniciar sesion</h2>
<p class="alert error">Usuario o contrasena incorrectos.</p>
<div class="actions">
<a class="link" href="login.html">Volver al login</a>
</div>
</main>
</body>
</html>
<?php
}

?>