<?php

$host="localhost";
$user="root";
$pass="Luneta211206$";
$db="sistema_academico";

$conn = new mysqli($host,$user,$pass,$db);

if($conn->connect_error){
die("Error de conexión: " . $conn->connect_error);
}

echo "Conexion exitosa 😎";

?>