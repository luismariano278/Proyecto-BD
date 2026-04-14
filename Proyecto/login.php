<?php
include("conexion.php");
include("auth.php");

function normalizar_usuario_demo($username)
{
	$aliases = [
		'admin' => 'admin_app',
		'docente' => 'docente_app',
		'alumno' => 'alumno1'
	];

	$username = trim($username);
	return $aliases[$username] ?? $username;
}

function asegurar_usuarios_demo($conn)
{
	$usuariosDemo = [
		['username' => 'admin_app', 'contrasena' => 'Admin123$', 'id_rol' => 1],
		['username' => 'docente_app', 'contrasena' => 'Docente123$', 'id_rol' => 2],
		['username' => 'alumno1', 'contrasena' => 'Alumno123$', 'id_rol' => 3]
	];

	$sqlExiste = "SELECT id_usuario FROM usuarios WHERE username = ? LIMIT 1";
	$stmtExiste = $conn->prepare($sqlExiste);
	if (!$stmtExiste) {
		return;
	}

	$sqlInsert = "INSERT INTO usuarios (username, contrasena, id_rol) VALUES (?, ?, ?)";
	$stmtInsert = $conn->prepare($sqlInsert);
	if (!$stmtInsert) {
		$stmtExiste->close();
		return;
	}

	foreach ($usuariosDemo as $usuarioDemo) {
		$usernameDemo = $usuarioDemo['username'];
		$contrasenaDemo = $usuarioDemo['contrasena'];
		$idRolDemo = (int)$usuarioDemo['id_rol'];

		$stmtExiste->bind_param("s", $usernameDemo);
		$stmtExiste->execute();
		$stmtExiste->store_result();

		if ($stmtExiste->num_rows === 0) {
			$stmtInsert->bind_param("ssi", $usernameDemo, $contrasenaDemo, $idRolDemo);
			$stmtInsert->execute();
		}
	}

	$stmtInsert->close();
	$stmtExiste->close();
}

asegurar_usuarios_demo($conn);

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

$user = normalizar_usuario_demo($_POST['username'] ?? '');
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