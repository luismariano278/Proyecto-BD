<?php

$host = "localhost";
$user = "root";
$db = "sistema_academico";

mysqli_report(MYSQLI_REPORT_OFF);

$credenciales = [
	"Luneta211206$",
	"root",
	""
];

$conn = null;
foreach ($credenciales as $pass) {
	$conexion = @new mysqli($host, $user, $pass, $db);
	if (!$conexion->connect_error) {
		$conn = $conexion;
		break;
	}
}

if (!$conn) {
	http_response_code(500);
	die("No se pudo conectar a la base de datos. Verifica usuario/password de MySQL en conexion.php");
}

?>